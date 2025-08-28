<?php

class ReportTeamRegistrations extends Report
{

    public const META = array(
        'Fields' => array(
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'email' => 'Email',
            'jobtitle' => 'Job Title',
            'employeetype' => 'Employee Type',
            'department' => 'Department',
            'branchname' => 'Office Location',
            'city' => 'City',
            'state' => 'State',
            'country' => 'Country',
            'opco' => 'Company',
            'enc_groupid' => 'Group ID',
            'groupname' => 'Group Name',
            'enc_chapterid' => 'Chapter ID',
            'chaptername' => 'Chapter Name',
            'last_updated_on' => 'Registration Updated On',
            'roleType' => 'Registered Role',
            'requestCapacity' => 'Registered Capacity',
            'availableCapacity' => 'Available Capacity',
            'requestStatus' => 'Registration Status',
            'question' => 'Registration Survey Response',
        ),
        'AdminFields' => array(
            'externalid' => 'Employee Id',
        ),
        'Options' => array(
            'download_matched_users' => 1,
            'download_unmatched_users' => 1,
            'download_active_join_requests' => 1,
            'download_inactive_join_requests' => 1,
            'download_paused_join_requests' => 1,
            'download_active_users_only' => 1,
        ),
        'Filters' => array(
            'groupid' => 0,
            'chapterids' => array(),
            'userid' => 0,
            'roleid' => 0,
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_TEAM_REGISTRATIONS;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {

        global $_COMPANY, $_ZONE, $_USER, $db;
        $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }

        $groupid = $meta['Filters']['groupid'];
        $userid = $meta['Filters']['userid'];
        $roleid = $meta['Filters']['roleid'];

        $download_matched_users = intval($meta['Options']['download_matched_users'] ?? 0);
        $download_unmatched_users = intval($meta['Options']['download_unmatched_users'] ?? 0);

        $download_join_requests_by_isactive_state = [];
        if ($meta['Options']['download_active_join_requests'] ?? 0)
            $download_join_requests_by_isactive_state[] = 1;
        if ($meta['Options']['download_inactive_join_requests'] ?? 0)
            $download_join_requests_by_isactive_state[] = 0;
        if ($meta['Options']['download_paused_join_requests'] ?? 0)
            $download_join_requests_by_isactive_state[] = 2;

        $download_active_users_only = intval ($meta['Options']['download_active_users_only'] ?? 0);

        $group = Group::GetGroup($groupid);
        $questionsJson = $group->getTeamMatchingAlgorithmAttributes();

        $surveyQuestionsPages = array();
        if (!empty($questionsJson)) {
            $surveyQuestionsPages = $questionsJson['pages'];
        }
        $questionMeta = array();
        $fields = $meta['Fields']; // Variable used to store fields so that we can update them.

        $includeSurvey = false;
        if (isset($fields['question'])) {
            $includeSurvey = true;
            unset($fields['question']);
        }
        if ($includeSurvey) {
            foreach ($surveyQuestionsPages as $surveyQuestions) {
                foreach ($surveyQuestions['elements'] as $question) {
                    if($question['type'] == 'html'){
                        continue;
                    }
                    $q = $question['name'];
                    if (isset($question['title'])) {
                        $q = $question['title'];
                    }
                    $fields = array_merge($fields, array($question['name'] => $q));
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
                        if (!empty ($question['hasOther'])) {
                            $questionMeta[$question['name']]['choices']['other'] = 'true';
                        }
                    } elseif ($question['type'] == 'matrix' || $question['type'] == 'matrixdropdown') {

                        foreach ($question['rows'] as $row) {
                            $questionMeta[$question['name']]['matrix_rows'][$row['value']] = $row['text'];

                        }

                        if ($question['type'] == 'matrix') {
                            foreach ($question['columns'] as $column) {
                                $questionMeta[$question['name']]['matrix_columns'][$column['value']] = $column['text'];
                            }
                            $questionMeta[$question['name']]['choices'] = array();

                        } else {
                            foreach ($question['columns'] as $column) {
                                $questionMeta[$question['name']]['matrix_columns'][$column['name']] = $column['title'];
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
        }
        $meta['Fields'] = $fields;
        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        $condition = "";
        if ($userid && $roleid) {
            $condition = " AND member_join_requests.userid='{$userid}' AND member_join_requests.roleid='{$roleid}'";
        }

        $matched_unmatched_users_condition = '';
        if ($download_matched_users && !$download_unmatched_users) {
            $matched_unmatched_users_condition = " AND used_capacity > 0";
        } elseif (!$download_matched_users && $download_unmatched_users) {
            $matched_unmatched_users_condition = " AND used_capacity = 0";
        }

        $download_join_requests_by_isactive_state_filter = '';
        if (!empty($download_join_requests_by_isactive_state)) {
            $download_join_requests_by_isactive_state_csv = implode(',', $download_join_requests_by_isactive_state);
            $download_join_requests_by_isactive_state_filter = " AND member_join_requests.isactive IN ({$download_join_requests_by_isactive_state_csv})";
        }

        $download_active_users_only_filter = $download_active_users_only ? ' AND users.isactive = 1' : '';

        $groupid_filter = " AND false "; // Initialize groupid_filter to false value;
        if ($groupid) {
            $groupid_filter = " AND member_join_requests.groupid={$groupid}";
        } else {
            // If groupid is not provided, it means we are looking for registration requests in the entire zone
            // so finds all active groups in the zone and apply a filter on it.
            $groupid_rows = self::DBROGet("SELECT groupid from `groups` WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND isactive=1");
            if (empty($groupid_rows)) {
                return;
            }
            $groupid_csv = implode(',', array_column($groupid_rows, 'groupid'));
            $groupid_filter = " AND member_join_requests.groupid IN ({$groupid_csv})";
        }

        $select = "SELECT member_join_requests.roleid, member_join_requests.groupid, member_join_requests.chapterids, member_join_requests.role_survey_response, member_join_requests.isactive, team_role_type.type as roleType, users.`userid`, users.firstname, users.lastname, users.email, users.external_email, users.externalid, users.jobtitle, users.extendedprofile, users.employeetype, users.homeoffice, users.department,users.opco,member_join_requests.request_capacity as requestCapacity, member_join_requests.used_capacity, member_join_requests.modifiedon as last_updated_on, member_join_requests.chapterids as chapterid
            FROM member_join_requests 
            LEFT JOIN users ON member_join_requests.userid=users.userid AND member_join_requests.companyid=users.companyid
            LEFT JOIN team_role_type ON member_join_requests.roleid = team_role_type.roleid AND member_join_requests.companyid=team_role_type.companyid
            WHERE member_join_requests.companyid={$_COMPANY->id()} {$groupid_filter} {$matched_unmatched_users_condition} {$download_join_requests_by_isactive_state_filter} {$download_active_users_only_filter} {$condition} {$this->policy_limit}";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$response = mysqli_fetch_assoc($result)) {

            if ($response['external_email']) {
                $response['email'] = User::PickEmailForDisplay($response['email'], $response['external_email'], false);
            }

            $response['requestStatus'] = self::REQUEST_STATUS_MAP[(int)$response['isactive']];

            if ($response['userid'] == null) {
                $response['userid'] = -1;
                $response['firstname'] = 'User Deleted';
                $response['lastname'] = 'User Deleted';
                $response['email'] = 'User Deleted';
                $response['jobtitle'] = 'User Deleted';
                $response['homeoffice'] = 0;
                $response['department'] = 0;
                $response['requestStatus'] = '-';
            }
            $response['externalid'] = explode(':', $response['externalid'] ?? '')[0];
            if (!empty($response['extendedprofile'])) {
                $profile = User::DecryptProfile($response['extendedprofile']);
                foreach ($profile as $pk => $value) {
                    $response['extendedprofile.' . $pk] = $value;
                }
            }

            //Decorate with additional fields
            $response = array_merge(
                $response,
                $this->getBranchAndRegionValues($response['homeoffice']),
                $this->getDepartmentValues($response['department'])
            );
            $responseJson = json_decode($response['role_survey_response'], true);

            if ($response['requestCapacity'] > 0){
                $availableCapacity = intval($response['requestCapacity']) - intval($response['used_capacity']);
                $response['availableCapacity'] = $availableCapacity >= 0 ? $availableCapacity : 0;
            } else {
                $response['requestCapacity'] = 'Unlimited';
                $response['availableCapacity']  = 'Unlimited';
            }

            $response['last_updated_on'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $response['last_updated_on'], $reportTimezone);

            if (!empty($meta['Fields']['groupname'])) {
                $response['groupname'] = $this->getGroupName($response['groupid']);
                $response['enc_groupid'] = $_COMPANY->encodeIdsInCSVForReport($response['groupid']);
            }
            // Next we want to replace the chapterids with the chapternames
            // If chapterid or channelid filter is set,
            // We will also remove the chaptername and channel name that we not requested in the filter.
            // Step (a) Get an array of all chapternames that the person is a member of, chapterid 0 is ignored.
            $chp_names = array();
            $enc_chapterids = [];
            if (!empty($meta['Fields']['chaptername'])) {
                if ($group->getTeamRoleRequestChapterSelectionSetting()['allow_chapter_selection']){
                    $user_chapters = explode(',', $response['chapterid']);
                    foreach ($user_chapters as $user_chapter) {
                        if ($user_chapter
                            && (empty($chapterids) || in_array($user_chapter, $chapterids)) // Chapter filter not set or chapter filter matches
                        ) {
                            $chap_name = $this->getChapterName($user_chapter);
                            if (!empty($chap_name)) {
                                $chp_names[] = $chap_name;
                                $enc_chapterids[] = $_COMPANY->encodeIdsInCSVForReport($user_chapter);
                            }
                        }
                    }
                }
                $response['chaptername'] = implode(", ", $chp_names);
                $response['enc_chapterid'] = implode(',', $enc_chapterids);
            }


            if ($includeSurvey) {
                foreach ($surveyQuestionsPages as $surveyQuestions) {
                    foreach ($surveyQuestions['elements'] as $question) {
                        // We are first iterating over the questions to keep the order intact with the header row.
                        $foundMatch = false;
                        if (empty($responseJson)) {
                            continue;
                        }
                        foreach ($responseJson as $key => $value) {

                            $showValue = $group->getTeamMatchingAttributeKeyVisibilitySetting('custom_parameters',$key,Group::MATCHING_ATTRIBUTES_VISIBILITY_KEYS['show_value_leaders']);
                            if ($showValue == 'hide' && $question['type'] != 'comment' && $question['type'] != 'text'){
                                continue;
                            }

                            if ($key == $question['name']) {
                                $answer = $value;

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
                                        } else {
                                            $ans[] = $a;
                                        }
                                    }
                                    $answer = Csv::GetCell($ans);
                                } elseif ($question['type'] == 'matrix' || $question['type'] == 'matrixdropdown') {

                                    if ($question['type'] == 'matrix') {
                                        $ans = array();
                                        foreach ($answer as $row => $val) {
                                            $ans[] = $questionMeta[$question['name']]['matrix_rows'][$row] . " : " . $questionMeta[$question['name']]['matrix_columns'][$val];
                                        }
                                        $answer = Csv::GetCell($ans);
                                    } else {

                                        $ans = array();
                                        foreach ($answer as $row => $cols) {
                                            $rowVal = $questionMeta[$question['name']]['matrix_rows'][$row];
                                            $colVal = array();
                                            foreach ($cols as $colkey => $val) {
                                                $colVal[] = $questionMeta[$question['name']]['matrix_columns'][$colkey] . ' : ' . $val;
                                            }
                                            $ans[] = $rowVal . ' => [ ' . implode(', ', $colVal) . ' ]';
                                        }
                                        $answer = Csv::GetCell($ans);
                                    }
                                } else {
                                    if (is_array($answer)) {
                                        $answer = Csv::GetCell($ans);
                                    }
                                }
                                $answer = empty($answer) ? 'No Response' : $answer;
                                $response[$key] = $answer;
                                break;
                            }
                        }
                    }
                }
            }
            $row = array();
            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($response[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
        }
    }

    public static function GetDefaultReportRecForDownload(): array
    {
        global $_COMPANY;
        global $_ZONE;

        $reportmeta = parent::GetDefaultReportRecForDownload();

        // Ignore custom report values for groupname, chaptername and channelname and set them to what is defined in the zone
        $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']["name-short"];
        $reportmeta['Fields']['enc_groupid'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' ID';
        $reportmeta['Fields']['chaptername'] = $_COMPANY->getAppCustomization()['chapter']["name-short"];
        $reportmeta['Fields']['enc_chapterid'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' ID';
        //$reportmeta['Fields']['channelname'] = $_COMPANY->getAppCustomization()['channel']["name-short"];

        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            unset($reportmeta['Fields']['chaptername']);
            unset($reportmeta['Fields']['enc_chapterid']);
        }

        return $reportmeta;
    }

}