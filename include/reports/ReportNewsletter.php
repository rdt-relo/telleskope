<?php

Class ReportNewsletter extends Report
{
    public const META = array(
        'Fields' => array(
            'enc_newsletterid' => 'Newsletter ID',
            'enc_groupid' => 'Group ID',
            'groupname' => 'Group',
            'enc_chapterid' => 'Chapter ID',
            'chaptername' => 'Chapter',
            'enc_channelid' => 'Channel ID',
            'channelname' => 'Channel',
            'dynamic_list' => 'Dynamic List',
            'newslettername' => 'Newsletter Name',
            'newsletter_summary' => 'Newsletter Summary',
            'createdate' => 'Create Date',
            'publishedby' => 'Published By',
            'publishdate' => 'Publish Date',
            'isactive' => 'Status',
            'recipientcount' => 'Number of Email Recipients',
            'openscount' => 'Total number of Email Opens',
            'uniqueopenscount' => 'Unique number of Email Opens'
        ),
        'Options' => array(
            'start_date' => null,
            'end_date' => null
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

    protected static function GetReportType() : int { return self::REPORT_TYPE_NEWSLETTERS;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $db, $_COMPANY, $_ZONE;

        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        $groupid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
            $groupid_list = implode(',', $meta['Filters']['groupids']);
            if(in_array( 0 , $meta['Filters']['groupids']) !== false){
                $groupid_filter = " AND (newsletters.groupid IN ({$groupid_list}) OR newsletters.groupid=0)";
            }else{ 
                $groupid_filter = " AND newsletters.groupid IN ({$groupid_list})";
            }
        }
        
        $startDateCondtion = "";
        if (!empty($meta['Options']['start_date'])) {
            $meta['Options']['start_date'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['start_date']) ?: '2200-12-31 00:00:00';
            $startDateCondtion = " AND `newsletters`.createdon >= '{$meta['Options']['start_date']}' ";
        }

        $endDateCondtion = "";
        if (!empty($meta['Options']['end_date'])) {
            $meta['Options']['end_date'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['end_date']) ?: '1900-12-31 00:00:00';
            $endDateCondtion = " AND `newsletters`.createdon <= '{$meta['Options']['end_date']}' ";
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char);

        $select = "SELECT `newsletters`.*,`newsletters`.isactive AS `newsletterstatus` FROM `newsletters` 
        LEFT JOIN `groups` ON groups.groupid=newsletters.groupid AND groups.isactive=1 
        WHERE newsletters.companyid = '{$this->cid()}' AND newsletters.zoneid='{$_ZONE->id()}' AND `newsletters`.isactive > 0 {$groupid_filter} {$startDateCondtion} {$endDateCondtion} ORDER BY newsletterid DESC {$this->policy_limit}";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        $chapterFilter = array();
        if (!empty($meta['Filters']['chapterids'])) {
            $chapterFilter = $meta['Filters']['chapterids'];
        }

        $channelFilter = array();
        if (!empty($meta['Filters']['channelids'])) {
            $channelFilter = $meta['Filters']['channelids'];
        }

        while (@$rows = mysqli_fetch_assoc($result)) {

          $rows['enc_newsletterid'] = $_COMPANY->encodeIdForReport($rows['newsletterid']);
          // Filter out chapter
          if (!empty($chapterFilter) || !empty($channelFilter)) {
            if (
                empty(array_intersect($chapterFilter, explode(',', $rows['chapterid']))) &&
                empty(array_intersect($channelFilter, explode(',', $rows['channelid'])))
            ) {
                // If subfilters (chapterid or channelid) were set and none of them match, then skip the row
                continue;
            }
        }
        $rows['chaptername'] = $this->getChapterNamesAsCSV($rows['chapterid']);
        $rows['channelname'] = $this->getChannelNamesAsCSV($rows['channelid']);
        $rows['dynamic_list'] = '-';
        if($rows['listids']){
            $rows['dynamic_list'] = $this->getDynamicListNamesCSV($rows['listids']);
        }
            // get email log data
            $rows['recipientcount'] = 0;
            $rows['openscount'] = 0;
            $rows['uniqueopenscount'] = 0;
            //rows['clickscount'] = 0;
            if ((isset($meta['Fields']['recipientcount']) || isset($meta['Fields']['openscount']) || isset($meta['Fields']['uniqueopenscount'])) && $rows['newsletterstatus'] == 1) {
                $domain = $_COMPANY->getAppDomain($_ZONE->val('app_type'));
                $emailLogs = Emaillog::GetAllEmailLogsSummary($domain,EmailLog::EMAILLOG_SECTION_TYPES['newsletter'],$rows['newsletterid']);
                foreach($emailLogs as $log){
                        $rows['recipientcount'] += ($log->val('total_rcpts') ?? 0);
                        $rows['openscount'] += ($log->val('total_opens') ?? 0);
                        $rows['uniqueopenscount'] += ($log->val('unique_opens') ?? 0);
                        //$rows['clickscount'] += $log->val('total_clicks') ?? 0;
                    }
            }

            $rows['groupname'] = "Global";
            if ($rows['groupid']) {
                $rows['groupname'] = $this->getGroupNamesAsCSV($rows['groupid']);
            }
            // Set local Timings
            $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
            $rows['createdate'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['createdon'], $reportTimezone);
            if ($rows['publishdate']) {
                $rows['publishdate'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['publishdate'], $reportTimezone);
            } else {
                $rows['publishdate'] = '-';
            }

            $publishedby = '-';
            if ($rows['publishedby']) {
                $publisher = User::GetUser($rows['publishedby']);
                $publishedby  = $publisher ? $publisher->getFullName() : '';
            }
            $rows['publishedby']  = $publishedby;
            
            $rows['isactive'] = self::CONTENT_STATUS_MAP[$rows['newsletterstatus']];

            $rows['enc_groupid'] = $_COMPANY->encodeIdsInCSVForReport($rows['groupid']);
            $rows['enc_chapterid'] = $_COMPANY->encodeIdsInCSVForReport($rows['chapterid']);
            $rows['enc_channelid'] = $_COMPANY->encodeIdsInCSVForReport($rows['channelid']);

            $row = array();
            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
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
        $reportmeta['Fields']['channelname'] = $_COMPANY->getAppCustomization()['channel']["name-short"];
        $reportmeta['Fields']['enc_channelid'] = $_COMPANY->getAppCustomization()['channel']['name-short'] . ' ID';

        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            unset($reportmeta['Fields']['chaptername']);
            unset($reportmeta['Fields']['enc_chapterid']);
        }
        if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
            unset($reportmeta['Fields']['channelname']);
            unset($reportmeta['Fields']['enc_channelid']);
        }

        return $reportmeta;
    }


}