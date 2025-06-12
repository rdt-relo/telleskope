<?php

Class ReportNewsletterInternal extends Report
{
    public const META = array(
        'Fields' => array(
            'groupname' => 'Group',
            'chaptername' => 'Chapter',
            'channelname' => 'Channel',
            'newslettername' => 'Newsletter Name',
            'publishdate' => 'Publish Date',
            'isactive' => 'Status',
            'recipientcount' => 'Number of Email Recipients',
            'openscount' => 'Total number of Email Opens',
            'uniqueopenscount' => 'Unique number of Email Opens',
//            'clickscount' => 'Total number of of Clicks',
        ),
        'Options' => array(),
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

    protected static function GetReportType() : int { return self::REPORT_TYPE_NEWSLETTERS_INTERNAL;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $db, $_COMPANY, $_ZONE;

        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char);

        $select = "SELECT * FROM `newsletters` WHERE `companyid` = '{$this->cid()}' AND zoneid='{$_ZONE->id()}' AND isactive > 0 ORDER BY newsletterid DESC {$this->policy_limit}";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$rows = mysqli_fetch_assoc($result)) {

            // Set chapter and channel names
            $chapter_names = array();
            if (!empty($meta['Fields']['chaptername'])) {
                $chapter_ids = explode(',', $rows['chapterid']);
                foreach ($chapter_ids as $chapter_id) {
                    $chapter_name = $this->getChapterName($chapter_id);
                    if (!empty($chapter_name)){
                        $chapter_names[] = $chapter_name;
                    }
                }
            }

            // Set channel names
            $channel_names = array();
            if (!empty($meta['Fields']['channelname'])) {
                $channel_ids = explode(',', $rows['channelid']);
                foreach ($channel_ids as $channel_id) {
                    $channel_name = $this->getChannelName($channel_id);
                    if (!empty($channel_name)){
                        $channel_names[] = $channel_name;
                    }
                }
            }
            // get email log data
            $rows['recipientcount'] = 0;
            $rows['openscount'] = 0;
            $rows['uniqueopenscount'] = 0;
            $rows['clickscount'] = 0;
            if ($rows['isactive'] == 1) {
            $domain = $_COMPANY->getAppDomain($_ZONE->val('app_type'));
            $emailLogs = Emaillog::GetAllEmailLogsSummary($domain,EmailLog::EMAILLOG_SECTION_TYPES['newsletter'],$rows['newsletterid']);
            foreach($emailLogs as $log){
                    $rows['recipientcount'] += $log->val('total_rcpts') ?? 0;
                    $rows['openscount'] += $log->val('total_opens') ?? 0;
                    $rows['uniqueopenscount'] += $log->val('unique_opens') ?? 0;
                    $rows['clickscount'] += $log->val('total_clicks') ?? 0;
                }
            }
        
            $rows['groupname'] = $this->getGroupNamesAsCSV($rows['groupid']);
            $rows['chaptername'] = implode(", ", $chapter_names);
            $rows['channelname'] = implode(", ", $channel_names);
            // Set local Timings
            $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
            $rows['publishdate'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['publishdate'], $reportTimezone);
            $rows['isactive'] = self::CONTENT_STATUS_MAP[$rows['isactive']];
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
        $reportmeta['Fields']['chaptername'] = $_COMPANY->getAppCustomization()['chapter']["name-short"];
        $reportmeta['Fields']['channelname'] = $_COMPANY->getAppCustomization()['channel']["name-short"];

        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            unset($reportmeta['Fields']['chaptername']);
        }
        if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
            unset($reportmeta['Fields']['channelname']);
        }

        return $reportmeta;
    }


}