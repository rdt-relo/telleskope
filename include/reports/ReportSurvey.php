<?php

class ReportSurvey extends Report
{
    public const META = array(
        'Fields' => array(
            'enc_surveyid' => 'Survey ID',
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'email' => 'Email',
            'jobtitle' => 'Job Title',
            'department' => 'Department',
            'branchname' => 'Office Location',
            'responsedate' => 'Response Date',
            'context' => 'Context',
            //'question'=>'Question'
        ),
        'AdminFields' => array(
            'externalid' => 'Employee Id',
        ),
        'Options' => array(
            'anonymous' => true,
        ),
        'Filters' => array(
            'groupid' => 0,
            'surveyid' => 0,
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_SURVEY;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }

        $groupid = $meta['Filters']['groupid'];
        $surveyid = $meta['Filters']['surveyid'];
        $survey = Survey2::GetSurvey($surveyid);
        $surveyJson = json_decode($survey->val('survey_json'), true);
        $surveyQuestionsPages = $surveyJson['pages'];
        $questionMeta = array();
        $fields = $meta['Fields']; // Variable used to store fields so that we can update them.
        foreach ($surveyQuestionsPages as $surveyQuestions) {
            if (isset($surveyQuestions['elements']))
                foreach ($surveyQuestions['elements'] as $question) {
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

                    if (isset($question['showCommentArea']) && $question['showCommentArea']) {
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

        $select = "SELECT * FROM `survey_responses_v2` WHERE  `survey_responses_v2`.`companyid`='{$_COMPANY->id()}' AND `surveyid`='{$surveyid}' {$this->policy_limit}";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$response = mysqli_fetch_assoc($result)) {
            $responseJson = json_decode($response['response_json'], true);
            $finalRow = array();
            $profileJson = null;

            $finalRow['enc_surveyid'] = $_COMPANY->encodeIdForReport($response['surveyid']);
            if ($response['profile_json']) {
                $profileJson = json_decode($response['profile_json'], true);
            }
            $finalRow['sno'] = $s;
            if (!$survey->val('anonymous') && $profileJson) {
                $finalRow['email'] = $profileJson['email'] ?? '';
                $finalRow['firstname'] = $profileJson['firstname'] ?? '';
                $finalRow['lastname'] = $profileJson['lastname'] ?? '';
                $finalRow['jobtitle'] = $profileJson['jobTitle'] ?? ''; // Report attribute name is jobtitle
                $finalRow['department'] = $profileJson['department'] ?? '';
                $finalRow['branchname'] = $profileJson['officeLocation'] ?? ''; // Report attribute name is branchname
            }

            if ($survey->val('surveytype') == 4) { // Team Survey
                $context = '';
                if ($response['objectid']) {
                    preg_match_all('(-?\d+(?:\.\d+)?+)', $response['objectid'], $objectIdArray);
                    if (!empty($objectIdArray)) {

                        $team = Team::GetTeam($objectIdArray[0][0]);
                        if ($team) {
                            $context .= "Team : " . $team->val('team_name') . PHP_EOL;
                        }
                        $teamRole = Team::GetTeamRoleType($objectIdArray[0][1]);
                        if ($teamRole) {
                            $context .= "Team Role : " . $teamRole['type'] . PHP_EOL;
                        }
                        $objectDaysFromStart = $objectIdArray[0][2];
                        if ($objectDaysFromStart >= 0) {
                            $context .= "Trigger : " . $objectDaysFromStart . ' day(s) after team start date';
                        } else {
                            $context .= "Trigger : On team complete";
                        }
                    }
                }
                $finalRow['context'] = $context;
            }

            $finalRow['responsedate'] = $db->covertUTCtoLocalAdvance("Y-m-d h:i A", ' T', $response['createdon'], $reportTimezone);

            foreach ($surveyQuestionsPages as $surveyQuestions) {
                if (isset($surveyQuestions['elements']))
                    foreach ($surveyQuestions['elements'] as $question) {
                        // We are first iterating over the questions to keep the order intact with the header row.
                        $foundMatch = false;
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
                                        $hasOther = $questionMeta[$question['name']];
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

                            if (isset($question['showCommentArea']) && isset($responseJson[$keyComment])) {
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