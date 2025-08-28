<?php
include_once __DIR__ . '/../UserConnect.php';

class ReportUserMembership extends Report
{

    public const META = array(
        'Fields' => array(
            'enc_groupmemberid' => 'Group Member ID',
            'enc_groupleaderid' => 'Group Leader ID',
            'enc_chapterleaderid' => 'Chapter Leader ID',
            'enc_channelleaderid' => 'Channel Leader ID',
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'pronouns' => 'Pronouns',
            'email' => 'Email',
            'connectemail' => 'Connect Email',
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
            'enc_channelid' => 'Channel ID',
            'channelname' => 'Channel Name',
            'groupcategory' => 'Group Category',
            'role' => 'Role',
            'roletitle' => 'Role Title',
            'region' => 'Region',
            'homezone' => 'Home Zone',
            'rolename' => 'Rolename',
            'since' => 'User Join Date',
            'modified' => 'User Last Updated On',
            'user_status' => 'User Status',
            'externalroles' => 'External Roles',
            'employee_hire_date' => 'Hire Date',
            'employee_start_date' => 'Start Date',
            'employee_termination_date' => 'Termination Date',
            'user_profile_bio' => 'User Profile Bio',
            //'extendedprofile.cai' => 'CAI'
        ),
        'AdminFields' => array(
            'externalid' => 'Employee Id',
        ),
        'Options' => array(
            'includeMembers' => true,
            'includeGroupleads' => true,
            'includeChapterleads' => true,
            'includeChannelleads' => true,
            'includeNonMembers' => true,
            'onlyActiveUsers' => true,
            'uniqueRecordsOnly' => false,
            'seperateLinesForChaptersChannels' => false,
            'startDate' => null,
            'endDate' => null,
        ),
        'Filters' => array(
            'groupids' => array(),
            'chapterids' => array(),
            'channelids' => array(),
            'anniversaryMonth' => 0
        )
    );

    const ATTRIBUTES_TO_BE_ANONYMIZED = array (
        'enc_groupmemberid',
        'enc_groupleaderid',
        'enc_chapterleaderid',
        'enc_channelleaderid',
        'connectemail',
        'externalid',
        'firstname',
        'lastname',
        'pronouns',
        'email',
        'connectemail',
        'jobtitle',
        'employeetype',
        'department',
        'branchname',
        'city',
        'state',
        'country',
        'opco',
        'externalroles',
        'employee_hire_date',
        'employee_start_date',
        'employee_termination_date',
        'user_profile_bio',
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int {return self::REPORT_TYPE_USER_MEMBERSHIP;}
    protected static function GetReportMeta() : array { return self::META;}

    public function getMetaArray(): array
    {
        global $_COMPANY;

        $meta = parent::getMetaArray();

        if (!$meta['Options']['includeMembers']) {
            unset($meta['Fields']['enc_groupmemberid']);
        }

        if (!$meta['Options']['includeGroupleads']) {
            unset($meta['Fields']['enc_groupleaderid']);
        }

        if (!$meta['Options']['includeChapterleads']) {
            unset($meta['Fields']['enc_chapterleaderid']);
        }

        if (!$meta['Options']['includeChannelleads']) {
            unset($meta['Fields']['enc_channelleaderid']);
        }

        return $meta;
    }

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

        //        $filter_out_00 = '';
        //        if ($chapterids_count && $channelids_count) {
        //            $filter_out_00 = "AND !(chapters.chapterid is null AND group_channels.channelid is null)";
        //        }

        $chapterid_filter = '';
        $chapterids = [];
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
        $channelids = [];
        if (!empty($meta['Filters']) && !empty($meta['Filters']['channelids'])) {
            $channelids = $meta['Filters']['channelids'];
            $channelid_list = implode(',', $channelids);
            if ($chapterids_count) {
                $channelid_filter = " AND (group_channels.channelid IN ({$channelid_list}) OR group_channels.channelid is null)";
            } else {
                $channelid_filter = " AND group_channels.channelid IN ({$channelid_list})";
            }
        }

        $activeUsersCondition = "";
        if ($meta['Options']['onlyActiveUsers'] ?? false) {
            $activeUsersCondition = "AND users.isactive = 1";
        }        

        $uniqueRecordsOnly = '';
        $groupField = ', groupname, `groups`.`groupid`';
        $chapterField = ', chapterid';
        $channelField = ', channelids';
        $groupJoinDateField = ', groupjoindate AS since';
        $orderByField = 'ORDER BY groupjoindate DESC';
        if ($meta['Options']['uniqueRecordsOnly'] ?? false) {
            $uniqueRecordsOnly = ' GROUP BY (userid) ';
            $groupField = ', group_concat(groupname) AS groupname, group_concat(`groups`.`groupid`) AS `groupid`';
            $chapterField = ', group_concat(chapterid) AS chapterid';
            $channelField = ', group_concat(channelids) AS channelids';
            $groupJoinDateField = ', MIN(groupjoindate) AS since';
            $orderByField = ''; // Not applicable
        }

        $memberStartDateCondtion = "";
        $leadStartDateCondtion = "";
        if (!empty($meta['Options']['startDate']) && Sanitizer::SanitizeUTCDatetime($meta['Options']['startDate']) == $meta['Options']['startDate']) {
            $memberStartDateCondtion = " AND groupmembers.`groupjoindate` >= '{$meta['Options']['startDate']}' ";
            $leadStartDateCondtion = " AND `assigneddate` >= '{$meta['Options']['startDate']}' ";
        }

        $memberEndDateCondtion = "";
        $leadEndDateCondtion = "";
        if (!empty($meta['Options']['endDate']) && Sanitizer::SanitizeUTCDatetime($meta['Options']['endDate']) == $meta['Options']['endDate']) {
            $memberEndDateCondtion = " AND groupmembers.`groupjoindate` <= '{$meta['Options']['endDate']}' ";
            $leadEndDateCondtion = " AND `assigneddate` <= '{$meta['Options']['endDate']}' ";
        }

        $memberAnniversaryMonthCondtion = "";
        $leadAnniversaryMonthCondtion = "";
        if (!empty($meta['Filters']['anniversaryMonth'])) {
            $month = intval($meta['Filters']['anniversaryMonth']);
            $memberAnniversaryMonthCondtion = " AND MONTH(groupmembers.`groupjoindate`)={$month} ";
            $leadAnniversaryMonthCondtion = " AND MONTH(`assigneddate`)={$month} ";
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        //
        $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
        $groupCategories = Group::GetAllGroupCategories();
        // Step 2 - Write Member Row

        if ($meta['Options']['includeMembers'] ?? false) {
            $select = "SELECT groupmembers.userid,groupmembers.memberid,zoneids,firstname,lastname,pronouns,email,external_email,externalid,jobtitle,extendedprofile,externalroles,employeetype,department,homeoffice,opco, 'member' AS role,'member' AS rolename, users.modified,  users.isactive as user_status, users.employee_hire_date, users.employee_start_date, users.employee_termination_date,
            groupmembers.anonymous,`groups`.categoryid {$groupJoinDateField} {$groupField} {$chapterField} {$channelField}
            FROM groupmembers 
            JOIN users USING (userid) 
            JOIN `groups` using (groupid) 
            WHERE users.companyid='{$this->cid()}'
              {$memberStartDateCondtion}
              {$memberEndDateCondtion}
              {$memberAnniversaryMonthCondtion}
              AND `groups`.companyid='{$this->cid()}' 
              AND `groups`.zoneid={$_ZONE->id()}
              AND (`groups`.isactive=1 {$activeUsersCondition} {$groupid_filter} AND groupmembers.isactive=1) 
            {$uniqueRecordsOnly}
            {$orderByField} {$this->policy_limit}";
            $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
            
            while (@$rows = mysqli_fetch_assoc($result)) {

                if ($rows['external_email']) {
                    $rows['email'] = User::PickEmailForDisplay($rows['email'], $rows['external_email'], false);
                }

                $rows['user_status'] = self::USER_STATUS_MAP[$rows['user_status']];

                //do something with $rows;
                $rows['externalid'] = explode(':', $rows['externalid'] ?? '')[0];
                if (!empty($rows['extendedprofile'])) {
                    $profile = User::DecryptProfile($rows['extendedprofile']);
                    foreach ($profile as $pk => $value) {
                        $rows['extendedprofile.' . $pk] = $value;
                    }
                }

                //Decorate with additional fields
                $rows = array_merge(
                    $rows,
                    $this->getBranchAndRegionValues($rows['homeoffice']),
                    $this->getDepartmentValues($rows['department']),
                    $this->getHomezoneNamesAsCSV($rows['zoneids'])
                );

                if (!empty($meta['Fields']['connectemail'])) {
                    $connectuser = UserConnect::GetConnectUserByTeleskopeUserid($rows['userid']);
                    $rows['connectemail']  =  $connectuser ? ($connectuser->val('external_email').($connectuser->isEmailVerified() ? '' : ' [Un-verified]')) : '-';
                }

                // timezone conversion
                $rows['since'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['since'], $reportTimezone);
                $rows['modified'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['modified'], $reportTimezone);
                $rows['groupcategory'] = Arr::SearchColumnReturnColumnVal($groupCategories, $rows['categoryid'], 'categoryid', 'category_name');
                // Next we want to replace the chapterids with the chapternames and channelids with channelnames
                // If chapterid or channelid filter is set,
                // We will also remove the chaptername and channel name that we not requested in the filter.
                // Step (a) Get an array of all chapternames that the person is a member of, chapterid 0 is ignored.
                $chp_names = array();
                $enc_chapterids = [];
                if (!empty($meta['Fields']['chaptername'])) {
                    $user_chapters = explode(',', $rows['chapterid']);
                    foreach ($user_chapters as $user_chapter) {
                        if ($user_chapter
                            && (empty($chapterids) || in_array($user_chapter, $chapterids)) // Chapter filter not set or chapter filter matches
                        ) {
                            $chap_name = $this->getChapterName($user_chapter);
                            if (!empty($chap_name)) {
                                $chp_names[] = $chap_name;
                                $enc_chapterids[] = $_COMPANY->encodeIdForReport($user_chapter);
                            }
                        }
                    }
                }

                // Step (b) Get an array of all channelname that the person is a member of, channelid 0 is ignored.
                $chn_names = array();
                $enc_channelids = [];
                if (!empty($meta['Fields']['channelname'])) {
                    $user_channels = explode(',', $rows['channelids']);
                    foreach ($user_channels as $user_channel) {
                        if ($user_channel
                            && (empty($channelids) || in_array($user_channel, $channelids)) // Channel filter not set or channel filter matches
                        ) {
                            $chan_name = $this->getChannelName($user_channel);
                            if (!empty($chan_name)) {
                                $chn_names[] = $chan_name;
                                $enc_channelids[] = $_COMPANY->encodeIdForReport($user_channel);
                            }
                        }
                    }
                }

                // Step (c) If chapterid filter is set or channel id filter is set
                //          Then remove rows that have empty chaptername and empty channel names
                //          or rows which have no chapter names, if chapterid filter is set
                //          or rows which have no channel names, if channelid filter is set
                if (
                    (($chapterids_count || $channelids_count) && empty($chp_names) && empty($chn_names)) ||
                    ($chapterids_count && empty($chp_names)) ||
                    ($channelids_count && empty($chn_names))
                ) {
                    continue; // skip this line.
                }

                // ANONYMIZE ATTRIBUTES
                if ($rows['anonymous'] == 1) {
                    foreach ($rows as $k => $v) {
                        if (in_array($k, self::ATTRIBUTES_TO_BE_ANONYMIZED) || str_starts_with($k,'extendedprofile')) {
                            $rows[$k] = 'Anonymous';
                        }
                    }
                }

                $rows['enc_groupid'] = $_COMPANY->encodeIdsInCSVForReport($rows['groupid']);

                if (isset($meta['Fields']['user_profile_bio'])) {
                    $rows['user_profile_bio'] = Html::HtmlToReportText((User::Hydrate($rows['userid'], []))->getBio());
                }

                // Step (d) Now depending upon the format either we will show the results in multiple rows or single
                //          If seperateLinesForChaptersChannels is requested then there will a seperate line in the
                //          report for each chapter and channel combination
                //          Otherwise
                //          Chapter and channel names will be concatenated with a ','
                //          Processed rows will be written to the file.
                if ($meta['Options']['seperateLinesForChaptersChannels'] ?? false) {
                    // Loop through all combinations of Chapters and Channels and create one row for each combination
                    // Reset chapter and channel name arrays to empty value if they are empty arrays to allow foreach to run
                    $chp_names = (empty($chp_names)) ? [''] : $chp_names;
                    $chn_names = (empty($chn_names)) ? [''] : $chn_names;
                    foreach ($chp_names as $i => $chp_name) {
                        foreach ($chn_names as $j => $chn_name) {
                            $rows['chaptername'] = $chp_name;
                            $rows['enc_chapterid'] = $enc_chapterids[$i] ?? '';
                            $rows['channelname'] = $chn_name;
                            $rows['enc_channelid'] = $enc_channelids[$j] ?? '';
                            $row = array();
                            foreach ($meta['Fields'] as $key => $value) {
                                $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
                            }
                            fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
                        }
                    }
                } else {
                    // Concat Chapters and Channel names and show only one row
                    $rows['chaptername'] = implode(", ", $chp_names);
                    $rows['channelname'] = implode(", ", $chn_names);

                    $rows['enc_chapterid'] = implode(',', $enc_chapterids ?? []);
                    $rows['enc_channelid'] = implode(',', $enc_channelids ?? []);

                    $rows['enc_groupmemberid'] = $_COMPANY->encodeIdForReport($rows['memberid']);

                    $row = array();
                    foreach ($meta['Fields'] as $key => $value) {
                        $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
                    }
                    fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
                }
            }
            mysqli_free_result($result);
        }

        // Step 3 - Write Grouplead Row
        if ($meta['Options']['includeGroupleads'] ?? false) {
            $select = "SELECT groupleads.userid,groupleads.leadid AS groupleads_leadid,groupleads.regionids,groupleads.roletitle,zoneids,firstname,lastname,pronouns,email,external_email,externalid,jobtitle,extendedprofile,externalroles,employeetype,department,homeoffice,opco,groupname,`groups`.`groupid`,'' AS chaptername, '' AS channelname, 'grouplead' AS role, grouplead_typeid, users.modified,  users.isactive as user_status, assigneddate AS since, users.employee_hire_date, users.employee_start_date, users.employee_termination_date,
            `groups`.categoryid
            FROM groupleads 
            JOIN users USING (userid) 
            JOIN `groups` USING (groupid) 
            WHERE users.companyid='{$this->cid()}'
              {$leadStartDateCondtion}
              {$leadEndDateCondtion} 
              {$leadAnniversaryMonthCondtion}
              AND `groups`.companyid='{$this->cid()}' 
              AND `groups`.zoneid={$_ZONE->id()}
              AND (`groups`.isactive=1 {$activeUsersCondition} {$groupid_filter}) 
            ORDER BY assigneddate DESC {$this->policy_limit}";
            $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

            while (@$rows = mysqli_fetch_assoc($result)) {

                if ($rows['external_email']) {
                    $rows['email'] = User::PickEmailForDisplay($rows['email'], $rows['external_email'], false);
                }

                $rows['user_status'] = self::USER_STATUS_MAP[$rows['user_status']];

                //do something with $rows;
                $rows['externalid'] = explode(':', $rows['externalid'] ?? '')[0];
                if (!empty($rows['extendedprofile'])) {
                    $profile = User::DecryptProfile($rows['extendedprofile']);
                    foreach ($profile as $pk => $value) {
                        $rows['extendedprofile.' . $pk] = $value;
                    }
                }
                //Decorate with additional fields
                $rows = array_merge(
                    $rows,
                    $this->getBranchAndRegionValues($rows['homeoffice']),
                    $this->getDepartmentValues($rows['department']),
                    $this->getLeadTypeValues($rows['grouplead_typeid']),
                    $this->getHomezoneNamesAsCSV($rows['zoneids'])
                );

                if (!empty($meta['Fields']['connectemail'])) {
                    $connectuser = UserConnect::GetConnectUserByTeleskopeUserid($rows['userid']);
                    $rows['connectemail']  =  $connectuser ? ($connectuser->val('external_email').($connectuser->isEmailVerified() ? '' : ' [Un-verified]')) : '-';
                }

                $rows['region'] = '';
                if ($rows['regionids']){
                    $regions = $_COMPANY->getRegionsByCSV($rows['regionids']);
                    $rows['region'] = empty($regions) ? '' : implode(',', array_column($regions, 'region'));
                }
                // timezone conversion
                $rows['since'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['since'], $reportTimezone);
                $rows['modified'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['modified'], $reportTimezone);
                $rows['groupcategory'] = Arr::SearchColumnReturnColumnVal($groupCategories, $rows['categoryid'], 'categoryid', 'category_name');

                $rows['enc_groupid'] = $_COMPANY->encodeIdsInCSVForReport($rows['groupid']);
                $rows['enc_groupleaderid'] = $_COMPANY->encodeIdsInCSVForReport($rows['groupleads_leadid']);

                if (isset($meta['Fields']['user_profile_bio'])) {
                    $rows['user_profile_bio'] = Html::HtmlToReportText((User::Hydrate($rows['userid'], []))->getBio());
                }

                $row = array();
                foreach ($meta['Fields'] as $key => $value) {
                    $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
                }
                fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
            }
            mysqli_free_result($result);
        }

        // Step 4 - Write Chapter Row
        // Since chapter leads table is not that big we are doing a direct join on chapter leads
        if ($meta['Options']['includeChapterleads'] ?? false) {
            $select = "SELECT chapterleads.userid,chapterleads.leadid AS chapterleads_leadid,chapterleads.roletitle,zoneids,firstname,lastname,pronouns,email,external_email,externalid,jobtitle,extendedprofile,externalroles,employeetype,department,homeoffice,opco,groupname,`groups`.`groupid`,chaptername, `chapters`.`chapterid`, '' AS channelname, 'chapterlead' AS role, grouplead_typeid, users.modified, users.isactive as user_status, assigneddate AS since, users.employee_hire_date, users.employee_start_date, users.employee_termination_date,
            `groups`.categoryid
            FROM chapterleads 
            JOIN users USING (userid) 
            JOIN `groups` using (groupid) 
            LEFT JOIN chapters ON chapterleads.chapterid=chapters.chapterid 
            WHERE users.companyid='{$this->cid()}' 
              {$leadStartDateCondtion}
              {$leadEndDateCondtion}
              {$leadAnniversaryMonthCondtion}
              AND `groups`.companyid='{$this->cid()}'
              AND `groups`.zoneid={$_ZONE->id()}
              AND (`groups`.isactive=1 AND chapters.isactive=1 {$activeUsersCondition} {$groupid_filter} {$chapterid_filter} ) 
            ORDER BY  assigneddate DESC {$this->policy_limit}";
            $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

            while (@$rows = mysqli_fetch_assoc($result)) {

                if ($rows['external_email']) {
                    $rows['email'] = User::PickEmailForDisplay($rows['email'], $rows['external_email'], false);
                }

                $rows['user_status'] = self::USER_STATUS_MAP[$rows['user_status']];

                //do something with $rows;
                $rows['externalid'] = explode(':', $rows['externalid'] ?? '')[0];
                if (!empty($rows['extendedprofile'])) {
                    $profile = User::DecryptProfile($rows['extendedprofile']);
                    foreach ($profile as $pk => $value) {
                        $rows['extendedprofile.' . $pk] = $value;
                    }
                }

                //Decorate with additional fields
                $rows = array_merge(
                    $rows,
                    $this->getBranchAndRegionValues($rows['homeoffice']),
                    $this->getDepartmentValues($rows['department']),
                    $this->getLeadTypeValues($rows['grouplead_typeid']),
                    $this->getHomezoneNamesAsCSV($rows['zoneids'])
                );

                if (!empty($meta['Fields']['connectemail'])) {
                    $connectuser = UserConnect::GetConnectUserByTeleskopeUserid($rows['userid']);
                    $rows['connectemail']  =  $connectuser ? $connectuser->val('external_email').($connectuser->isEmailVerified() ? '' : ' [Un-verified]') : '-';
                }

                // timezone conversion
                $rows['since'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['since'], $reportTimezone);
                $rows['modified'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['modified'], $reportTimezone);
                $rows['groupcategory'] = Arr::SearchColumnReturnColumnVal($groupCategories, $rows['categoryid'], 'categoryid', 'category_name');

                $rows['enc_groupid'] = $_COMPANY->encodeIdForReport($rows['groupid']);
                $rows['enc_chapterid'] = $_COMPANY->encodeIdForReport($rows['chapterid']);
                $rows['enc_chapterleaderid'] = $_COMPANY->encodeIdForReport($rows['chapterleads_leadid']);

                if (isset($meta['Fields']['user_profile_bio'])) {
                    $rows['user_profile_bio'] = Html::HtmlToReportText((User::Hydrate($rows['userid'], []))->getBio());
                }

                $row = array();
                foreach ($meta['Fields'] as $key => $value) {
                    $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
                }
                fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
            }
            mysqli_free_result($result);
        }

        // Step 5 - Write Channel Row
        if ($meta['Options']['includeChannelleads'] ?? false) {
            $select = "SELECT group_channel_leads.userid,group_channel_leads.leadid AS group_channel_leads_leadid,group_channel_leads.roletitle,zoneids,firstname,lastname,pronouns,email,external_email,externalid,jobtitle,extendedprofile,externalroles,employeetype,department,homeoffice,opco,groupname,`groups`.`groupid`,'' AS chaptername, 'channellead' AS role, grouplead_typeid, users.modified, users.isactive as user_status, assigneddate AS since, channelname, `group_channels`.`channelid`, users.employee_hire_date, users.employee_start_date, users.employee_termination_date,
            `groups`.categoryid
            FROM group_channel_leads 
            LEFT JOIN users USING (userid) 
            LEFT JOIN `groups` using (groupid) 
            LEFT JOIN group_channels ON group_channel_leads.channelid=group_channels.channelid 
            WHERE users.companyid='{$this->cid()}'
              {$leadStartDateCondtion}
              {$leadEndDateCondtion}
              {$leadAnniversaryMonthCondtion}
              AND `groups`.companyid='{$this->cid()}'
              AND `groups`.zoneid={$_ZONE->id()}
              AND (`groups`.isactive=1 AND group_channels.isactive=1 {$activeUsersCondition} {$groupid_filter} {$channelid_filter}) 
            ORDER BY  assigneddate DESC {$this->policy_limit}";
            $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

            while (@$rows = mysqli_fetch_assoc($result)) {

                if ($rows['external_email']) {
                    $rows['email'] = User::PickEmailForDisplay($rows['email'], $rows['external_email'], false);
                }

                $rows['user_status'] = self::USER_STATUS_MAP[$rows['user_status']];

                //do something with $rows;
                $rows['externalid'] = explode(':', $rows['externalid'] ?? '')[0];
                if (!empty($rows['extendedprofile'])) {
                    $profile = User::DecryptProfile($rows['extendedprofile']);
                    foreach ($profile as $pk => $value) {
                        $rows['extendedprofile.' . $pk] = $value;
                    }
                }

                //Decorate with additional fields
                $rows = array_merge(
                    $rows,
                    $this->getBranchAndRegionValues($rows['homeoffice']),
                    $this->getDepartmentValues($rows['department']),
                    $this->getLeadTypeValues($rows['grouplead_typeid']),
                    $this->getHomezoneNamesAsCSV($rows['zoneids'])
                );

                if (!empty($meta['Fields']['connectemail'])) {
                    $connectuser = UserConnect::GetConnectUserByTeleskopeUserid($rows['userid']);
                    $rows['connectemail']  =  $connectuser ? ($connectuser->val('external_email').($connectuser->isEmailVerified() ? '' : ' [Un-verified]')) : '-';
                }
                // timezone conversion
                $rows['since'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['since'], $reportTimezone);
                $rows['modified'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['modified'], $reportTimezone);
                $rows['groupcategory'] = Arr::SearchColumnReturnColumnVal($groupCategories, $rows['categoryid'], 'categoryid', 'category_name');

                $rows['enc_groupid'] = $_COMPANY->encodeIdForReport($rows['groupid']);
                $rows['enc_channelid'] = $_COMPANY->encodeIdForReport($rows['channelid']);
                $rows['enc_channelleaderid'] = $_COMPANY->encodeIdForReport($rows['group_channel_leads_leadid']);

                if (isset($meta['Fields']['user_profile_bio'])) {
                    $rows['user_profile_bio'] = Html::HtmlToReportText((User::Hydrate($rows['userid'], []))->getBio());
                }

                $row = array();
                foreach ($meta['Fields'] as $key => $value) {
                    $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
                }
                fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
            }
            mysqli_free_result($result);
        }

        # Step 6 - include Non Members

        if ($meta['Options']['includeNonMembers'] ?? false) {
            # First get the groupids;
            $groupid_rows = self::DBROGet("SELECT groupid FROM `groups` WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND `groups`.isactive=1");
            $membership_check_filter = '';
            if (count($groupid_rows)) {
                $groupids = implode(',',array_column($groupid_rows, 'groupid'));
                $membership_check_filter = " AND NOT EXISTS (SELECT userid FROM `groupmembers` WHERE `groupmembers`.`userid`=users.userid AND groupmembers.groupid IN ({$groupids}))";
            }
            $select = "SELECT users.userid,zoneids,firstname,lastname,pronouns,email,external_email,externalid,jobtitle,extendedprofile,externalroles,employeetype,department,homeoffice,opco,'' as groupname,'' as chaptername, '' as channelname, '' AS role,'' AS rolename, users.modified, '' AS since, users.isactive as user_status, users.employee_hire_date, users.employee_start_date, users.employee_termination_date
            FROM users 
            WHERE users.companyid='{$this->cid()}'
              {$activeUsersCondition}
              AND (users.zoneids = '' OR FIND_IN_SET('{$_ZONE->id()}', users.zoneids))
              {$membership_check_filter} {$this->policy_limit}";
            $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

            while (@$rows = mysqli_fetch_assoc($result)) {

                if ($rows['external_email']) {
                    $rows['email'] = User::PickEmailForDisplay($rows['email'], $rows['external_email'], false);
                }

                $rows['user_status'] = self::USER_STATUS_MAP[$rows['user_status']];
                //do something with $rows;
                $rows['externalid'] = explode(':', $rows['externalid'] ?? '')[0];
                if (!empty($rows['extendedprofile'])) {
                    $profile = User::DecryptProfile($rows['extendedprofile']);
                    foreach ($profile as $pk => $value) {
                        $rows['extendedprofile.' . $pk] = $value;
                    }
                }

                //Decorate with additional fields
                $rows = array_merge(
                    $rows,
                    $this->getBranchAndRegionValues($rows['homeoffice']),
                    $this->getDepartmentValues($rows['department']),
                    $this->getHomezoneNamesAsCSV($rows['zoneids'])
                );

                if (!empty($meta['Fields']['connectemail'])) {
                    $connectuser = UserConnect::GetConnectUserByTeleskopeUserid($rows['userid']);
                    $rows['connectemail']  =  $connectuser ? ($connectuser->val('external_email').($connectuser->isEmailVerified() ? '' : ' [Un-verified]')) : '-';
                }
                // timezone conversion
                $rows['since'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['since'], $reportTimezone);
                $rows['modified'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['modified'], $reportTimezone);
                $rows['groupcategory'] = '';

                if (isset($meta['Fields']['user_profile_bio'])) {
                    $rows['user_profile_bio'] = Html::HtmlToReportText((User::Hydrate($rows['userid'], []))->getBio());
                }

                $row = array();
                foreach ($meta['Fields'] as $key => $value) {
                    $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
                }
                fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
            }
            mysqli_free_result($result);
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
        $reportmeta['Fields']['channelname'] = $_COMPANY->getAppCustomization()['channel']["name-short"];
        $reportmeta['Fields']['enc_channelid'] = $_COMPANY->getAppCustomization()['channel']['name-short'] . ' ID';

        $reportmeta['Fields']['enc_groupmemberid'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Member ID';
        $reportmeta['Fields']['enc_groupleaderid'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Leader ID';
        $reportmeta['Fields']['enc_chapterleaderid'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' Leader ID';
        $reportmeta['Fields']['enc_channelleaderid'] = $_COMPANY->getAppCustomization()['channel']['name-short'] . ' Leader ID';

        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            unset($reportmeta['Fields']['chaptername']);
            unset($reportmeta['Fields']['enc_chapterid']);
            unset($reportmeta['Fields']['enc_chapterleaderid']);
        }
        if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
            unset($reportmeta['Fields']['channelname']);
            unset($reportmeta['Fields']['enc_channelid']);
            unset($reportmeta['Fields']['enc_channelleaderid']);
        }
        if (!$_COMPANY->getAppCustomization()['profile']['enable_pronouns']) {
            unset($reportmeta['Fields']['pronouns']);
        }
        if (!$_COMPANY->isConnectEnabled()) {
            unset($reportmeta['Fields']['connectemail']);
        }

        if ($_ZONE->val('app_type') !== 'peoplehero') {
            unset($reportmeta['Fields']['employee_hire_date']);
            unset($reportmeta['Fields']['employee_start_date']);
            unset($reportmeta['Fields']['employee_termination_date']);
        }

        if (!$_COMPANY->getAppCustomization()['profile']['enable_bio']) {
            unset($reportmeta['Fields']['user_profile_bio']);
        }

        return $reportmeta;
    }

    public static function GetMetadataForAnalytics () : array
    {
        return array (
            'ExludeFields' => array (
                'enc_groupmemberid',
                'enc_groupleaderid',
                'enc_chapterleaderid',
                'enc_channelleaderid',
                'enc_groupid',
                'enc_chapterid',
                'enc_channelid',
                'firstname',
                'lastname',
                'pronouns',
                'email',
                'modified',
                'connectemail',
                'since',
                'externalid',
                'externalroles',
                'employee_hire_date',
                'employee_start_date',
                'employee_termination_date',
                'user_profile_bio',
            ),
            'TimeField' => 'modified'
        );
    }
}
