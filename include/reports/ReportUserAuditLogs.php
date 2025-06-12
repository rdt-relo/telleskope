<?php

class ReportUserAuditLogs extends Report
{

    public const META = array(
        'Fields' => array(
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'email' => 'Email',
            //'jobtitle' => 'Job Title',
            'enc_groupid' => 'Group ID',
            'groupname' => 'Group Name',
            'enc_chapterid' => 'Chapter ID',
            'chaptername' => 'Chapter Name',
            'enc_channelid' => 'Channel ID',
            'channelname' => 'Channel Name',
            'teamname' => 'Team Name',
            'rolename' => 'Role',
            'createdon' => 'Action Date'
        ),
        'AdminFields' => array(
            'externalid' => 'Employee Id',
        ),
        'Options' => array(
            'includeMembers' => true,
            'includeGroupleads' => true,
            'includeChapterleads' => true,
            'includeChannelleads' => true,
            'includeDeletedUser' => false //@todo for Hem
        ),
        'Filters' => array(
            'groupids' => array(),
            'chapterids' => array(),
            'channelids' => array()
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_USER_AUDIT_LOGS;}
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
        global $_COMPANY, $_ZONE, $db;
        // Step 0 -
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();  
        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }

        $groupid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
            $groupid_list = implode(',', $meta['Filters']['groupids']);
            $groupid_filter = " AND groups.groupid IN ({$groupid_list})";
        }

        $chapterids_count = count($meta['Filters']['chapterids'] ?? array());
        $channelids_count = count($meta['Filters']['channelids'] ?? array());

        $chapterid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['chapterids'])) {
            $chapterids = $meta['Filters']['chapterids'];
            $chapterid_list = implode(',', $chapterids);
            if ($channelids_count) {
                $chapterid_filter = " AND (chapters.chapterid IN ({$chapterid_list}) OR chapters.chapterid is null)";
            } else {
                $chapterid_filter = " AND (chapters.chapterid IN ({$chapterid_list}))";
            }
        }

        $channelid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['channelids'])) {
            $channelids = $meta['Filters']['channelids'];
            $channelid_list = implode(',', $channelids);
            if ($chapterids_count) {
                $channelid_filter = " AND (group_channels.channelid IN ({$channelid_list}) OR group_channels.channelid is null)";
            } else {
                $channelid_filter = " AND group_channels.channelid IN ({$channelid_list})";
            }
        }

        $includeDeletedUserJoin = "";
        if ($meta['Options']['includeDeletedUser']) {
            $includeDeletedUserJoin = "LEFT";
        }
        $orderByField = 'ORDER BY group_user_logs.createdon DESC';
        $startDateCondtion = "";
        if (!empty($meta['Options']['startDate'])) {
            $startDate = date('Y-m-d H:i:s', strtotime($meta['Options']['startDate']));
            $startDateCondtion = " AND group_user_logs.`createdon` >= '{$startDate}'";
        }

        $endDateCondtion = "";
        if (!empty($meta['Options']['endDate'])) {
            $endDate = date('Y-m-d H:i:s', strtotime($meta['Options']['endDate']));
            $endDateCondtion = " AND group_user_logs.`createdon` <= '{$endDate}' ";
        }

        // Add Internal fields
        $meta['Fields']['action'] = 'Action';
        $meta['Fields']['action_reason'] = 'Action Reason';
        $reportTimezone = $_SESSION['timezone'] ?? 'UTC';

        if ($meta['Options']['includeGroupleads']) {
            $roleFilter = GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_LEAD'];
        } elseif ($meta['Options']['includeChapterleads']) {
            $roleFilter = GroupUserLogs::GROUP_USER_LOGS_ROLES['CHAPTER_LEAD'];
        } elseif ($meta['Options']['includeChannelleads']) {
            $roleFilter = GroupUserLogs::GROUP_USER_LOGS_ROLES['CHANNEL_LEAD'];
        } else {
            $roleFilter = GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'];
        }
        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.

        // Step 2 - Write Data Rows
        $select = "SELECT group_user_logs.*, users.zoneids,users.firstname,users.lastname,users.email,users.jobtitle,users.isactive,users.externalid,`groups`.groupname,'Member' as 'rolename' 
                    FROM group_user_logs 
                        {$includeDeletedUserJoin} JOIN users USING (userid) 
                        JOIN `groups` using (groupid) 
                    WHERE `group_user_logs`.companyid='{$this->cid()}' 
                      AND`groups`.companyid = '{$this->cid()}'
                      {$startDateCondtion} 
                      {$endDateCondtion} 
                      AND (`groups`.isactive=1 {$groupid_filter}) 
                      AND group_user_logs.role='{$roleFilter}' 
                      {$chapterid_filter} 
                      {$channelid_filter} 
                      {$orderByField} {$this->policy_limit}";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        $lead_type_roles = ['group_lead','chapter_lead','channel_lead'];

        while (@$rows = mysqli_fetch_assoc($result)) {
            $rows['externalid'] = explode(':', $rows['externalid'] ?? '')[0];

            // timezone conversion
            $rows['createdon'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['createdon'], $reportTimezone);

            $rows['chaptername'] = '';
            if (!empty($meta['Fields']['chaptername']) && $rows['sub_scope'] == GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER']) {
                if ($rows['sub_scopeid'] > 0) {
                    $rows['chaptername'] = $this->getChapterName($rows['sub_scopeid']);
                    $rows['enc_chapterid'] = $_COMPANY->encodeIdsInCSVForReport($rows['sub_scopeid']);
                }
            }

            $rows['channelname'] = '';
            if (!empty($meta['Fields']['channelname']) && $rows['sub_scope'] == GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL']) {
                if ($rows['sub_scopeid'] > 0) {
                    $rows['channelname'] = $this->getChannelName($rows['sub_scopeid']);
                    $rows['enc_channelid'] = $_COMPANY->encodeIdsInCSVForReport($rows['sub_scopeid']);
                }
            }

            if ($rows['roleid'] > 0) {
                if (in_array($rows['role'], $lead_type_roles)) {
                    $role = $this->getLeadTypeValues($rows['roleid']);
                    if (!empty($role)) {
                        $rows['rolename'] = $role['rolename'];
                    }
                }
            }

            $rows['teamname'] = '';
            if (!empty($meta['Fields']['teamname']) && $rows['sub_scope'] == GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['TEAM']) {
                if ($rows['sub_scopeid'] > 0) {
                    $rows['teamname'] = 2;//$this->getTeamName($rows['sub_scopeid']);
                }
                $role = $this->getTeamRoleTypeValues($rows['roleid']);
                if (!empty($role)) {
                    $rows['rolename'] = 3;// $role['rolename'];
                }
            }

            if ($meta['Options']['includeDeletedUser'] && ((!$rows['firstname'] && !$rows['lastname'] && !$rows['email']) || $rows['isactive'] == 100 || $rows['isactive'] == 101)) {
                $rows['firstname'] = 'Deleted User';
                $rows['lastname'] = 'Deleted User';
                $rows['email'] = '-';
            }
            $rows['action'] = ucfirst($rows['action']);

            $rows['enc_groupid'] = $_COMPANY->encodeIdsInCSVForReport($rows['groupid']);

            $row = array();
            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
        }
        mysqli_free_result($result);

    }

    public static function GetDefaultReportRecForDownload(): array
    {
        global $_COMPANY;
        global $_ZONE;

        $reportmeta = parent::GetDefaultReportRecForDownload();

        // Ignore custom report values for groupname, chaptername and channelname and set them to what is defined in the zone
        $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']['name-short'];
        $reportmeta['Fields']['enc_groupid'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' ID';
        $reportmeta['Fields']['chaptername'] = $_COMPANY->getAppCustomization()['chapter']['name-short'];
        $reportmeta['Fields']['enc_chapterid'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' ID';
        $reportmeta['Fields']['channelname'] = $_COMPANY->getAppCustomization()['channel']['name-short'];
        $reportmeta['Fields']['enc_channelid'] = $_COMPANY->getAppCustomization()['channel']['name-short'] . ' ID';
        $reportmeta['Fields']['teamname'] = $_COMPANY->getAppCustomization()['teams']['name'];

        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            unset($reportmeta['Fields']['chaptername']);
            unset($reportmeta['Fields']['enc_chapterid']);
        }
        if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
            unset($reportmeta['Fields']['channelname']);
            unset($reportmeta['Fields']['enc_channelid']);
        }
        if (!$_COMPANY->getAppCustomization()['teams']['enabled']) {
            unset($reportmeta['Fields']['teamname']);
        }

        return $reportmeta;
    }

    public static function GetMetadataForAnalytics () : array
    {
        return array (
            'ExludeFields' => array (
                'firstname',
                'lastname',
                'email',
                'externalid',
                'createdon',
                'Action',
                'Action Reason'
            ),
            'TimeField' => 'createdon'
        );
    }
}