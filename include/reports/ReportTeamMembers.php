<?php

class ReportTeamMembers extends Report
{

    public const META = array(
        'Fields' => array(
            'enc_teammemberid' => 'Team Member ID',
            'enc_teamid' => 'Team ID',
            'enc_groupid' => 'Group ID',
            'groupname' => 'Program Name',
            'program_type' => 'Program Type',
            'enc_chapterid' => 'Chapter ID',
            'chaptername' => 'Chapter Name',
            'team_name' => 'Team Name',
            'team_status' => 'Team Status',
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'pronouns' => 'Pronouns',
            'email' => 'Email',
            'jobtitle' => 'Job Title',
            'employeetype' => 'Employee Type',
            'department' => 'Department',        
            'branchname' => 'Office Location',
            'city' => 'City',
            'state' => 'State',
            'country' => 'Country',
            'region' => 'Region',
            'opco' => 'Company',
            'role_name' => 'Role Name',
            'roletitle' => 'Role Title',
            'team_join_date' => 'Join Date',
            'total_action_items' => 'Total number of Action Items',
            'count_actionitem_pending' => 'Count of Action Items - Pending',
            'count_actionitem_inprogress' => 'Count of Action Items - In Progress',
            'count_actionitem_complete' => 'Count of Action items - Complete',
            'count_feedback_received' => 'Count of Feedbacks Received',
            'count_feedback_provided' => 'Count of Feedbacks Provided',
            'count_messages' => 'Total Number of Messages',
            'last_active_date' => 'Last Active Date'
        ),
        'AdminFields' => array(
            'externalid' => 'Employee Id',
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

    protected static function GetReportType() : int { return self::REPORT_TYPE_TEAM_MEMBERS;}
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
        
        $teamrole_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['roleids'])) {
            $roleids = implode(',', Sanitizer::SanitizeIntegerArray($meta['Filters']['roleids']));
            $teamrole_filter = " AND team_members.roleid IN ({$roleids})";
        }
        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.

       $select = "SELECT teams.*, teams.isactive as teams_isactive, `groups`.groupname, `chapters`.chaptername
        FROM `teams` JOIN `groups` USING (groupid)
        LEFT JOIN `chapters` USING(chapterid)
        WHERE `groups`.`companyid`='{$this->cid()}' AND `teams`.`companyid`='{$this->cid()}' AND ( `groups`.`zoneid`='{$_ZONE->id()}' {$group_filter} {$team_status_filter}) {$this->policy_limit}";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        

        $timezone = "UTC";

        while (@$teams = mysqli_fetch_assoc($result)) {

            // Get counts topic comments
            $teamMessages = [];
            $showMessageCount = false;
            if (isset($meta['Fields']['count_messages']) && $meta['Fields']['count_messages']) {
                $showMessageCount = true;
                $teamMessages = Arr::GroupBy(Team::GetComments_2($teams['teamid']), 'userid');
            }
            // Get program type
            $groupObj = Group::GetGroup($teams['groupid']);
            $program_type = $groupObj->getTeamProgramType();
            $program_type_value = array_flip(Team::TEAM_PROGRAM_TYPE)[$program_type] ?? '-';

           $selectMembers = "SELECT team_members.*,IFNULL(team_role_type.`type`,'Other') as `role_name`, team_role_type.sys_team_role_type, IFNULL(users.firstname,'Deleted') as firstname, users.pronouns, users.employeetype, users.opco, users.department, users.homeoffice, IFNULL(users.lastname,'User') as lastname, users.extendedprofile, IFNULL(users.email,'') as email, external_email, IFNULL(users.picture,'') as picture, externalid, IF (users.jobtitle='','Job title unavailable',users.jobtitle) as jobtitle, 
            (SELECT COUNT(1) FROM `team_tasks` WHERE `teamid`=team_members.teamid AND  `assignedto`= team_members.userid AND `task_type`='todo' AND `isactive`='1') as count_actionitem_pending,
            (SELECT COUNT(1) FROM `team_tasks` WHERE `teamid`=team_members.teamid AND  `assignedto`= team_members.userid AND `task_type`='todo' AND `isactive`='51') as count_actionitem_inprogress,
            (SELECT COUNT(1) FROM `team_tasks` WHERE `teamid`=team_members.teamid AND  `assignedto`= team_members.userid AND `task_type`='todo' AND `isactive`='52') as count_actionitem_complete,
            (SELECT COUNT(1) FROM `team_tasks` WHERE `teamid`=team_members.teamid AND  `assignedto`= team_members.userid AND `task_type`='feedback' ) as count_feedback_received,
            (SELECT COUNT(1) FROM `team_tasks` WHERE `teamid`=team_members.teamid AND  `createdby`= team_members.userid AND `task_type`='feedback' AND `isactive`='1') as count_feedback_provided
           FROM `team_members`
               LEFT JOIN users ON users.userid=team_members.userid AND users.companyid={$_COMPANY->id()}
               LEFT JOIN team_role_type ON team_role_type.roleid= team_members.roleid
           WHERE team_members.teamid='{$teams['teamid']}'
                 $teamrole_filter {$this->policy_limit}";

            $teamMembers = mysqli_query($dbc, $selectMembers) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $selectMembers]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

            while (@$members = mysqli_fetch_assoc($teamMembers)) {
                $teamMembersData = array_merge($teams, $members);

                if ($teamMembersData['external_email']) {
                    $teamMembersData['email'] = User::PickEmailForDisplay($teamMembersData['email'], $teamMembersData['external_email'], true);
                }

                $teamMembersData['enc_teamid'] = $_COMPANY->encodeIdForReport($teamMembersData['teamid']);
                $teamMembersData['team_status'] = self::TEAM_STATUS_MAP[$teams['teams_isactive']];
                $teamMembersData['firstname'] = $members['firstname'];
                $teamMembersData['lastname'] = $members['lastname'];
                $teamMembersData['jobtitle'] = $members['jobtitle'];
                $teamMembersData['employeetype'] = $members['employeetype'];
                $teamMembersData['opco'] = $members['opco'];

                $teamMembersData = array_merge(
                    $teamMembersData,
                    $this->getDepartmentValues($members['department'] ?: 0),
                    $this->getBranchAndRegionValues($members['homeoffice'] ?: 0 ),
                );

                $teamMembersData['team_join_date'] = $db->covertUTCtoLocalAdvance("Y-m-d", ' T', $members['createdon'], $timezone);
                $teamMembersData['total_action_items'] = $members['count_actionitem_pending'] + $members['count_actionitem_inprogress'] + $members['count_actionitem_complete'];
                $teamMembersData['count_actionitem_pending'] = $members['count_actionitem_pending'];
                $teamMembersData['count_actionitem_inprogress'] = $members['count_actionitem_inprogress'];
                $teamMembersData['count_actionitem_complete'] = $members['count_actionitem_complete'];
                $teamMembersData['count_feedback_received'] = $members['count_feedback_received'];
                $teamMembersData['count_feedback_provided'] = $members['count_feedback_provided'];
                $teamMembersData['last_active_date'] = $teams['isactive'] != 2 ? $db->covertUTCtoLocalAdvance("Y-m-d h:i A", ' T', $members['last_active_date'], $timezone) : '-';

                //do something with $members;
                $teamMembersData['externalid'] = explode(':', $members['externalid'] ?? '')[0];
                if (!empty($members['extendedprofile'])) {
                    $profile = User::DecryptProfile($members['extendedprofile']);
                    foreach ($profile as $pk => $value) {
                        $teamMembersData['extendedprofile.' . $pk] = $value;
                    }
                }

                $teamMembersData['program_type'] = $program_type_value;

                // Message Count
                if ($showMessageCount) {
                    $teamMembersData['count_messages'] = count($teamMessages[$members['userid']] ?? []);
                }

                $teamMembersData['enc_groupid'] = $_COMPANY->encodeIdsInCSVForReport($teamMembersData['groupid']);
                $teamMembersData['enc_chapterid'] = $_COMPANY->encodeIdsInCSVForReport($teamMembersData['chapterid']);
                $teamMembersData['enc_teammemberid'] = $_COMPANY->encodeIdsInCSVForReport($teamMembersData['team_memberid']);

                $row = [];
                foreach ($meta['Fields'] as $key => $value) {
                    $row[] = html_entity_decode($teamMembersData[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
                }
                fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
            }
        }

    }

    public static function GetDefaultReportRecForDownload(): array
    {
        global $_COMPANY;
        /* @var Company $_COMPANY */
        global $_ZONE;
        $reportmeta = null;
        $reportType = static::GetReportType();
        $row = self::DBGet("SELECT * FROM company_reports WHERE companyid={$_COMPANY->id()} AND (zoneid={$_ZONE->id()} AND reporttype={$reportType} AND purpose='download' AND isactive=1) LIMIT 1");
        if (count($row) && $row[0]['reportmeta']) {
            $reportmeta = json_decode($row[0]['reportmeta'], true);
        } else {
            $reportmeta = self::META;
        }
        $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']["name-short"];
        $reportmeta['Fields']['enc_groupid'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' ID';
        $reportmeta['Fields']['chaptername'] = $_COMPANY->getAppCustomization()['chapter']['name-short'];
        $reportmeta['Fields']['enc_chapterid'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' ID';

        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            unset($reportmeta['Fields']['chaptername']);
            unset($reportmeta['Fields']['enc_chapterid']);
        }

        if (!$_COMPANY->getAppCustomization()['profile']['enable_pronouns']) {
            unset($reportmeta['Fields']['pronouns']);
        }

        $reportmeta['Fields']['team_name'] = $_COMPANY->getAppCustomization()['teams']['name'];
        $reportmeta['Fields']['enc_teamid'] = $_COMPANY->getAppCustomization()['teams']['name'] . ' ID';
        $reportmeta['Fields']['enc_teammemberid'] = $_COMPANY->getAppCustomization()['teams']['name'] . ' Member ID';
        return $reportmeta;
    }
}