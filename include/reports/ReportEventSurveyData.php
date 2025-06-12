<?php

class ReportEventSurveyData extends Report
{
    public const META = array(
        'Fields' => array(
            'eventtitle' => 'Event Title',
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'email' => 'Email',
            'jobtitle' => 'Job Title',
            'department' => 'Department',
            'branchname' => 'Office Location',
            'survey_trigger' => 'Survey Trigger',
            'joindate' => 'RSVP On',
            'joinstatus' => 'RSVP Status',
            //'checkin_process' => 'Check In Process', /*skipping as it did not make sense */
            'checkedin_date' => 'Check In On',
            'checkedin_by' => 'Check In By',
        ),
        'Options' => array(),
        'Filters' => array(
            'eventid' => 0,
            'survey_trigger' => '',
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_EVENT_SURVEY;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        $eventid = $meta['Filters']['eventid'];
        $event = Event::GetEvent($eventid);
        $survey_trigger = $meta['Filters']['survey_trigger'];
        $surveyData = $event->getEventSurveyByTrigger($survey_trigger);
        $surveyJson = $surveyData['survey_questions'];
        $surveyQuestionsPages = $surveyJson['pages'];
        $questionMeta = array();
        $fields = $meta['Fields']; // Variable used to store fields so that we can update them.
        $firstQuestionRemoved = false; // First question is used only for logic
        foreach ($surveyQuestionsPages as $surveyQuestions) {
            if (isset($surveyQuestions['elements']))

                foreach ($surveyQuestions['elements'] as $question) {
                    if (!$firstQuestionRemoved){
                        unset( $question);
                        $firstQuestionRemoved = true;
                        continue;
                    }
                    if($question['type'] == 'html'){
                        continue;
                    }
                    $q = $question['name'];
                    if (isset($question['title'])) {
                        $q = $question['title'];
                        if (is_array($q)) {
                            $q = $q['default'];
                        }
                    }
                    $fields = array_merge($fields, array($question['name'] => $q));

                    if (isset($question['hasComment']) && $question['hasComment']) {
                        $fields = array_merge($fields, array($question['name'] . '-Comment' => 'Comment for ' . $q));
                    }

                    $questionMeta[$question['name']] = array('type' => $question['type'], 'choices' => array());

                    if (isset($question['choicesFromQuestion']) && !empty($question['choicesFromQuestion'])) {
                        // If we are here it means the choices for this question are referencing to another question
                        // Just mark it we will process it later
                        $questionMeta[$question['name']]['choicesFromQuestion'] = $question['choicesFromQuestion'];
                    } elseif (!empty ($question['choices']) && $question['type'] != 'matrixdropdown') {
                        foreach ($question['choices'] as $choice) {
                            if (is_array($choice)) {
                                $value = "";
                                if (array_key_exists('text', $choice)) {
                                    $value = is_array($choice['text']) ? $choice['text']['default'] : $choice['text'];
                                } else { // Image picker case
                                    $value = is_array($choice['value']) ? $choice['value']['default'] : $choice['value'];
                                }
                                $questionMeta[$question['name']]['choices'][$choice['value']] = $value;
                            } else {
                                $questionMeta[$question['name']]['choices'][$choice] = $choice;
                            }
                        }
                        if (!empty ($question['hasOther']) || !empty($question['showOtherItem'])) {
                            $questionMeta[$question['name']]['choices']['other'] = 'true';
                        }
                    } elseif ($question['type'] == 'matrix' || $question['type'] == 'matrixdropdown') {

                        foreach ($question['rows'] as $row) {

                            if (is_array($row)) {
                                $value = $row['value'];
                                $text = $row['text'];
                            } else {
                                $value = $row;
                                $text = $row;
                            }
                            $questionMeta[$question['name']]['matrix_rows'][$value] = $text;
                        }

                        if ($question['type'] == 'matrix') {
                            foreach ($question['columns'] as $column) {
                                if (is_array($column)) {
                                    $value = $column['value'];
                                    $text = $column['text'];
                                } else {
                                    $value = $column;
                                    $text = $column;
                                }

                                $questionMeta[$question['name']]['matrix_columns'][$value] = $text;
                            }
                            $questionMeta[$question['name']]['choices'] = array();

                        } else {
                            foreach ($question['columns'] as $column) {

                                if (is_array($column)) {
                                    $value = $column['name'];
                                    $text = array_key_exists('title', $column) ? $column['title'] : $column['name'];
                                } else {
                                    $value = $column;
                                    $text = $column;
                                }
                                $questionMeta[$question['name']]['matrix_columns'][$value] = $text;
                            }
                            $questionMeta[$question['name']]['choices'] = $question['choices'];

                        }
                    }
                }
        }
        // Not process reference choices from another question
        foreach ($questionMeta as &$q) {
            if (!empty($q['choicesFromQuestion'])) {
                $q['choices'] = $questionMeta[$q['choicesFromQuestion']]['choices'];
            }
        }

        $meta['Fields'] = $fields;

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.

        $s = 1;
        $select = "SELECT events.eventtitle, eventjoiners.*,
            firstname,lastname,email,externalid,jobtitle,extendedprofile,employeetype,opco,
            homeoffice, department
            FROM eventjoiners
                JOIN events using (eventid)
                LEFT JOIN users ON eventjoiners.userid=users.userid
            WHERE 
                events.companyid={$this->cid()} 
                AND events.zoneid={$_ZONE->id()}
                AND eventjoiners.eventid={$eventid}
                {$this->policy_limit}";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        $reportTimezone = $_SESSION['timezone'] ?? 'UTC';

        while (@$response = mysqli_fetch_assoc($result)) {
            $other_data = json_decode($response['other_data'] ?? '', true) ?? [];

            if (!array_key_exists($survey_trigger,$other_data)){
                continue;
            }
            $responseJson = $other_data[$survey_trigger];
            $finalRow = array();
            if (empty($response['email'])) {
                
                $response['firstname'] = $other_data['firstname']??'';
                $response['lastname'] = $other_data['lastname']??'';
                $response['email'] = $other_data['email']??'';
                $response['jobtitle'] = '';
                $response['department'] = '';
                $response['branchname'] = '';
                $response['joindate'] = '';
                $response['joinstatus'] = '';
                $response['checkin_process'] = empty($response['checkedin_date']) ? '' : 'Manual';
            }  else {

                $response = array_merge(
                    $response,
                    $this->getDepartmentValues($response['department']),
                    $this->getBranchAndRegionValues($response['homeoffice'])
                );
                $response['joindate'] = empty($response['joindate']) ? '' : $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $response['joindate'], $reportTimezone);
                $response['joinstatus'] = Event::GetRSVPLabel($response['joinstatus']);
                $response['checkin_process'] = empty($response['checkedin_date']) ? '' : 'System';
            }

            $response['checkedin_date'] = empty($response['checkedin_date']) ? '' : $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $response['checkedin_date'], $reportTimezone);
            
            $finalRow = $response;
            $finalRow['sno'] = $s;
            $finalRow['survey_trigger'] = $survey_trigger;
           
            foreach ($surveyQuestionsPages as $surveyQuestions) {
                if (isset($surveyQuestions['elements']))
                    foreach ($surveyQuestions['elements'] as $question) {
                        // We are first iterating over the questions to keep the order intact with the header row.
                        if (isset($responseJson[$question['name']])) {
                            $key = $question['name'];
                            $keyComment = $key . '-Comment';
                            $value = $responseJson[$question['name']];

                            $answer = $value;
                            //Check boolen value and get label type here for csv
                            if ($question['type'] == 'boolean') {
                                if ($value) {
                                    $answer = $question['labelTrue'] ?? 'Yes';
                                } else {
                                    $answer = $question['labelFalse'] ?? 'No';
                                }
                            }

                            if (isset($question['choices']) && ($question['type'] != 'matrix' && $question['type'] != 'matrixdropdown')) {
                                $ans = [];
                                if (is_array($answer)) {
                                    foreach ($answer as $curr_answer) {
                                        $a = $questionMeta[$question['name']]['choices'][$curr_answer];
                                        if ($a && ($curr_answer === 'other')) {
                                            $ans[] = 'Other: ' . $responseJson[$key . '-Comment'];
                                        } else {
                                            $ans[] = $a;
                                        }
                                    }
                                } else {
                                    $a = $questionMeta[$question['name']]['choices'][$answer];
                                    if (($a) && ($value === 'other')) {
                                        $ans[] = 'Other: ' . $responseJson[$key . '-Comment'];
                                    } elseif ($answer == "none") {
                                        $ans[] = 'None'; // None option was selected
                                    } else {
                                        $ans[] = $a;
                                    }
                                }
                                $answer = implode(', ', $ans);
                            } elseif ($question['type'] == 'matrix' || $question['type'] == 'matrixdropdown') {

                                if ($question['type'] == 'matrix') {
                                    $ans = array();
                                    foreach ($answer as $row => $val) {
                                        $ans[] = $questionMeta[$question['name']]['matrix_rows'][$row] . " : " . $questionMeta[$question['name']]['matrix_columns'][$val];
                                    }
                                    $answer = implode(', ', $ans);

                                } else {
                                    // echo "<pre>"; print_r($answer ); die();
                                    $ans = array();
                                    foreach ($answer as $row => $cols) {
                                        $rowVal = $questionMeta[$question['name']]['matrix_rows'][$row];
                                        $colVal = array();
                                        foreach ($cols as $colkey => $val) {
                                            $colVal[] = $questionMeta[$question['name']]['matrix_columns'][$colkey] . ' : ' . $val;
                                        }
                                        $ans[] = $rowVal . ' => [ ' . implode(', ', $colVal) . ' ]';
                                    }
                                    $answer = implode(', ', $ans);
                                }
                            } else {
                                if (is_array($answer)) {
                                    $answer = implode(', ', $answer);
                                }
                            }
                            $answer = empty($answer) ? 'No Response' : $answer;
                            $finalRow[$key] = $answer;

                            if (isset($question['hasComment']) && isset($responseJson[$keyComment])) {
                                $finalRow[$keyComment] = $responseJson[$keyComment];
                            }
                        }
                    }
            }
            
            $row = array();
            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($finalRow[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
            $s++;
        }
    }
}