<?php

class ReportTeamTeams extends Report
{

    public const META = array(
        'Fields' => array(
            'enc_teamid' => 'Team ID',
            'enc_groupid' => 'Group ID',
            'groupname' => 'Program Name',
            'program_type' => 'Program Type',
            'enc_chapterid' => 'Chapter ID',
            'chaptername' => 'Chapter Name',
            'team_name' => 'Team Name',
            'team_status' => 'Team Status',
            'team_create_date' => 'Create Date',
            'team_start_date' => 'Start Date',
            'team_complete_date' => 'Complete Date',
            'count_members' => 'Total Number of Participants',
            'circle_max_capacity' => 'Circle Max Capacity',
            'circle_vacancy' => 'Circle Vacancy',
            'total_action_items' => 'Total number of Action Items',
            'count_actionitem_pending' => 'Count of Action Items - Pending',
            'count_actionitem_inprogress' => 'Count of Action Items - In Progress',
            'count_actionitem_complete' => 'Count of Action items - Complete',
            'total_touchpoints' => 'Total number of Touchpoints',
            'count_touchpoints_pending' => 'Count of Touch Points - Pending',
            'count_touchpoints_inprogress' => 'Count of Touch Points - In Progress',
            'count_touchpoints_complete' => 'Count of Touch Points - Complete',
            'count_feedback' => 'Count of Feedbacks',
            'count_messages' => 'Total Number of Messages',
            'hashtags' => 'Hashtags',
            'team_description' => 'Team Description',
            'last_active_date' => 'Last Active Date',
        ),
        'AdminFields' => array(
        ),
        'Filters' => array(
            'groupids' => array(),
            'teamstatus' => array(),
            'roleids' => array()
        )
    );
  

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_TEAM_TEAMS;}
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

       $select = "SELECT teams.*, `groups`.groupname, `chapters`.chaptername
        FROM `teams` JOIN `groups` USING (groupid)
        LEFT JOIN `chapters` USING(chapterid)
        WHERE `groups`.`companyid`='{$this->cid()}' AND `teams`.`companyid`='{$this->cid()}' AND ( `groups`.`zoneid`='{$_ZONE->id()}' {$group_filter} {$team_status_filter}) {$this->policy_limit}";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        
        // $timezone = $_SESSION['timezone'] ?? "UTC";
        $timezone = "UTC";

        while (@$teams = mysqli_fetch_assoc($result)) {

            // Get counts topic comments
            $teamMessages = [];
            $showMessageCount = false;
            if (isset($meta['Fields']['count_messages']) && $meta['Fields']['count_messages']) {
                $showMessageCount = true;
                $teamMessages = Arr::GroupBy(Team::GetComments_2($teams['teamid']), 'topicid');
            }

            // Get program type
            $groupObj = Group::GetGroup($teams['groupid']);
            $program_type_value = $groupObj->getTeamProgramType();

            $selectTeamTasks = "SELECT teams.teamid,
                 (SELECT COUNT(1) FROM `team_tasks` WHERE `task_type`='todo' AND teamid=teams.teamid AND `isactive`='1') as count_actionitem_pending,
                (SELECT COUNT(1) FROM `team_tasks` WHERE `task_type`='todo' AND teamid=teams.teamid AND `isactive`='51') as count_actionitem_inprogress,
                (SELECT COUNT(1) FROM `team_tasks` WHERE `task_type`='todo' AND teamid=teams.teamid AND `isactive`='52') as count_actionitem_complete,
                (SELECT COUNT(1) FROM `team_tasks` WHERE `task_type`='touchpoint' AND teamid=teams.teamid AND `isactive`='1') as count_touchpoints_pending,
                (SELECT COUNT(1) FROM `team_tasks` WHERE `task_type`='touchpoint' AND teamid=teams.teamid AND `isactive`='51') as count_touchpoints_inprogress,
                (SELECT COUNT(1) FROM `team_tasks` WHERE `task_type`='touchpoint' AND teamid=teams.teamid AND `isactive`='52') as count_touchpoints_complete,
                (SELECT COUNT(1) FROM `team_tasks` WHERE `task_type`='feedback' AND teamid=teams.teamid AND `isactive`='1') as count_feedback,
                (SELECT modifiedon FROM team_tasks WHERE teamid=teams.teamid ORDER BY `modifiedon` DESC LIMIT 1 ) as last_active_date,
                (SELECT COUNT(1) FROM team_members WHERE teamid=teams.teamid ) as count_members
            FROM teams
            WHERE teams.companyid={$_COMPANY->id()} 
              AND teams.teamid='{$teams['teamid']}'
                 {$this->policy_limit}";

            $teamTasksResult = mysqli_query($dbc, $selectTeamTasks) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $selectTeamTasks]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

            while (@$teamTaskCounts = mysqli_fetch_assoc($teamTasksResult)) {
                $teamsData = array_merge($teams, $teamTaskCounts);
                $teamsData['enc_teamid'] = $_COMPANY->encodeIdForReport($teamsData['teamid']);
                $teamsData['team_status'] = self::TEAM_STATUS_MAP[$teams['isactive']];
                $teamsData['team_create_date'] = $db->covertUTCtoLocalAdvance("Y-m-d", ' T', $teams['createdon'], $timezone);;
                $teamsData['team_start_date'] = !empty($teams['team_start_date']) ? $db->covertUTCtoLocalAdvance("Y-m-d", ' T', $teams['team_start_date'], $timezone) : '-';
                $teamsData['team_complete_date'] = !empty($teams['team_complete_date']) ? $db->covertUTCtoLocalAdvance("Y-m-d", ' T', $teams['team_complete_date'], $timezone) : '-';

                $teamsData['total_action_items'] = $teamTaskCounts['count_actionitem_pending'] + $teamTaskCounts['count_actionitem_inprogress'] + $teamTaskCounts['count_actionitem_complete'];
                $teamsData['count_actionitem_pending'] = $teamTaskCounts['count_actionitem_pending'];
                $teamsData['count_actionitem_inprogress'] = $teamTaskCounts['count_actionitem_inprogress'];
                $teamsData['count_actionitem_complete'] = $teamTaskCounts['count_actionitem_complete'];
                $teamsData['total_touchpoints'] = $teamTaskCounts['count_touchpoints_pending'] + $teamTaskCounts['count_touchpoints_inprogress'] + $teamTaskCounts['count_touchpoints_complete'];
                $teamsData['count_touchpoints_pending'] = $teamTaskCounts['count_touchpoints_pending'];
                $teamsData['count_touchpoints_inprogress'] = $teamTaskCounts['count_touchpoints_inprogress'];
                $teamsData['count_touchpoints_complete'] = $teamTaskCounts['count_touchpoints_complete'];
                $teamsData['count_feedback'] = $teamTaskCounts['count_feedback'];
                $teamsData['program_type'] = array_flip(Team::TEAM_PROGRAM_TYPE)[$program_type_value] ?? '-';
                $teamsData['last_active_date'] = $db->covertUTCtoLocalAdvance("Y-m-d h:i A", ' T', $teamTaskCounts['last_active_date'], $timezone);

                // Special cases
                if ($program_type_value == Team::TEAM_PROGRAM_TYPE['CIRCLES']) {
                    if (isset($meta['Fields']['hashtags']) && $meta['Fields']['hashtags']) {
                        $teamsData['hashtags'] = self::getHashtagsAsCSV($teams['handleids']);
                    }
                    if (isset($meta['Fields']['team_description']) && $meta['Fields']['team_description']) {
                        $teamsData['team_description'] = Html::SanitizeHtml($teamsData['team_description']);
                    }
                    // circle max capacity and circle vacancy
                    if (isset($meta['Fields']['circle_max_capacity']) || isset($meta['Fields']['circle_vacancy'])) {
                        $circleRolesCapacity = Team::GetCircleRolesCapacity($teams['groupid'],$teamsData['teamid']);
                        $circle_max_capacity = [];
                        $circle_vacancy = [];
                        foreach($circleRolesCapacity as $k => $role){
                            $tm = Team::GetTeam($teamsData['teamid']);
                            $members = $tm->getTeamMembers((int)$role['roleid']);
                            $totalmembers = count($members);
                                $circle_role_max_capacity = $role['circle_role_max_capacity'];
                                $availableRoleCapacity = ($circle_role_max_capacity - $totalmembers);

                            // Add data in array.
                            $circle_max_capacity[] = "{$role['type']}: {$circle_role_max_capacity}";
                            $circle_vacancy[] = "{$role['type']}: {$availableRoleCapacity}";
                        }

                        $teamsData['circle_max_capacity'] = implode(', ', $circle_max_capacity);
                        $teamsData['circle_vacancy'] = implode(', ', $circle_vacancy);
                    }
                } else {
                    $teamsData['hashtags'] = '';
                    $teamsData['team_description'] = '';
                }

                // Message Count
                if ($showMessageCount) {
                    $teamsData['count_messages'] = count($teamMessages[$teamTaskCounts['teamid']] ?? []);
                }

                $teamsData['enc_groupid'] = $_COMPANY->encodeIdsInCSVForReport($teamsData['groupid']);
                $teamsData['enc_chapterid'] = $_COMPANY->encodeIdsInCSVForReport($teamsData['chapterid']);

                $row = [];
                foreach ($meta['Fields'] as $key => $value) {
                    $row[] = html_entity_decode($teamsData[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
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
        $reportmeta['Fields']['chaptername'] = $_COMPANY->getAppCustomization()['chapter']['name-short'];
        $reportmeta['Fields']['enc_chapterid'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' ID';

        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            unset($reportmeta['Fields']['chaptername']);
            unset($reportmeta['Fields']['enc_chapterid']);
        }

        $reportmeta['Fields']['team_name'] = $_COMPANY->getAppCustomization()['teams']['name'];
        return $reportmeta;
    }
}