<?php

class ReportTeamsFeedback extends Report
{
    public const META = array(
        'Fields' => array(
            'enc_teamid' => 'Team ID',
            'enc_groupid' => 'Group ID',
            'groupname' => 'Program Name',
            'team_name' => 'Team Name',
            'team_status' => 'Team Status',
            'provided_by' => 'Feedback Provided By',
            'provided_by_email' => 'Feedback Provided By Email',
            'provided_on' => 'Feedback Provided On',
            'feedback_for' => 'Feedback For',
            'provided_for_email' => 'Feedback Provided For Email',
            'feedback_visibility' => 'Feedback Visibility',
            'feedback' => 'Feedback',
        ),
        'AdminFields' => array(
            'provided_by_externalid' => 'Feedback Provided By - Employee Id',
            'provided_for_externalid' => 'Feedback Provided For - Employee Id',
        ),
        'Filters' => array(
            'groupids' => array(),
            'teamstatus' => array(),
        )
    );
  

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_TEAM_FEEDBACK;}
    protected static function GetReportMeta() : array { return self::META;}

     /**
     * Generates and writes the report in the provided file handler.
     * @param $file_h
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape_char
     */
    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $_USER, $db;
        // Step 0 -
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }

        $group_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
            $groupids = implode(',', Sanitizer::SanitizeIntegerArray($meta['Filters']['groupids']));
            $group_filter = " AND `groups`.groupid IN ({$groupids})";
        }

        $team_status_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['teamstatus'])) {
            $teamstatus = implode(',', Sanitizer::SanitizeIntegerArray($meta['Filters']['teamstatus']));
            $team_status_filter = " AND teams.isactive IN ({$teamstatus})";
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.

        $select = "SELECT teams.team_name, teams.teamid, teams.isactive as teams_isactive, `groups`.groupname, `groups`.`groupid`
                        FROM `teams` JOIN `groups` USING (groupid)
                        WHERE `groups`.`companyid`={$this->cid()} AND `teams`.`companyid`={$this->cid()} AND ( `groups`.`zoneid`='{$_ZONE->id()}' {$group_filter} {$team_status_filter}) {$this->policy_limit}";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$teams = mysqli_fetch_assoc($result)) {

            $query = "SELECT * FROM `team_tasks` WHERE `teamid`='{$teams['teamid']}' AND `task_type`='feedback' AND `isactive`='1' {$this->policy_limit}";

            $data = mysqli_query($dbc, $query) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $query]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

            while (@$feedbackData = mysqli_fetch_assoc($data)) {
                $teamFeedbackData['enc_teamid'] = $_COMPANY->encodeIdForReport($teams['teamid']);
                $teamFeedbackData['team_name'] = $teams['team_name'];
                $teamFeedbackData['team_status'] = self::TEAM_STATUS_MAP[$teams['teams_isactive']];
                $teamFeedbackData['groupname'] = $teams['groupname'];
                $feedbackByUser = User::GetUser($feedbackData['createdby']);
                $teamFeedbackData['provided_by'] = $feedbackByUser ? $feedbackByUser->getFullName() : '-';
                $teamFeedbackData['provided_by_externalid'] = $feedbackByUser ? $feedbackByUser->getExternalId() : '';
                $teamFeedbackData['provided_by_email'] = $feedbackByUser ? $feedbackByUser->val('email') : '';

                $teamFeedbackData['provided_on'] = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($feedbackData['createdon'],true,true,true);
                $feedbackToUser = User::GetUser($feedbackData['assignedto']);
                $teamFeedbackData['feedback_for'] = $feedbackData['assignedto'] ? ($feedbackToUser ? $feedbackToUser->getFullName() : '-') : ' Program Leaders ';
                $teamFeedbackData['provided_for_externalid'] = $feedbackToUser ? $feedbackToUser->getExternalId() : '';
                $teamFeedbackData['provided_for_email'] = $feedbackToUser ? $feedbackToUser->val('email') : '';
                $teamFeedbackData['feedback'] = $feedbackData['tasktitle'];
                $visibilityValue =  $feedbackData['visibility'];
                $teamFeedbackData['feedback_visibility'] = array_flip(Team::TEAM_TASK_VISIBILITY)[$visibilityValue] ?? '-';
                $teamFeedbackData['enc_groupid'] = $_COMPANY->encodeIdsInCSVForReport($teams['groupid']);

                $row = [];
                foreach ($meta['Fields'] as $key => $value) {
                    $row[] = html_entity_decode($teamFeedbackData[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
                }
                fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
            }
        }

    }

    public static function GetDefaultReportRecForDownload(): array
    {
        global $_COMPANY;
        global $_ZONE;

        $reportmeta = parent::GetDefaultReportRecForDownload();

        $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']["name-short"];
        $reportmeta['Fields']['enc_groupid'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' ID';
        $reportmeta['Fields']['team_name'] = $_COMPANY->getAppCustomization()['teams']['name'];
        return $reportmeta;
    }

    public static function GetMetadataForAnalytics () : array
    {
        return array (
            'ExludeFields' => array(),
        );
    }

}