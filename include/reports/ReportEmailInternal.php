<?php

Class ReportEmailInternal extends Report
{
    public const META = array(
        'Fields' => array(
            'groupname' => 'Group',
            'chaptername' => 'Chapter',
            'channelname' => 'Channel',
            'subject' => 'Email Title',
            'publishdate' => 'Publish Date',
            'isactive' => 'Status',
            'recipientcount' => 'Number of Email Recipients',
            'openscount' => 'Total number of Email Opens',
            'uniqueopenscount' => 'Unique number of Email Opens',
//            'clickscount' => 'Total number of Clicks',
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

    protected static function GetReportType() : int { return self::REPORT_TYPE_EMAIL_INTERNAL;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $db, $_COMPANY, $_ZONE;

        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char);

        $select = "SELECT * FROM `messages` WHERE `companyid` = '{$this->cid()}' AND zoneid='{$_ZONE->id()}' AND isactive > 0 ORDER BY messageid DESC {$this->policy_limit}";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$rows = mysqli_fetch_assoc($result)) {
            // Set group, chapter and channel names
            $rows['groupname'] = !empty($meta['Fields']['groupname']) ? $this->getGroupNamesAsCSV($rows['groupids']) : '';
            $rows['chaptername'] = !empty($meta['Fields']['groupname']) ? $this->getChapterNamesAsCSV($rows['chapterids']) : '';
            $rows['channelname'] = !empty($meta['Fields']['channelname']) ? $this->getChannelNamesAsCSV($rows['channelids']) : '';
            
            // get email log data
            $rows['recipientcount'] = 0;
            $rows['openscount'] = 0;
            $rows['uniqueopenscount'] = 0;
            $rows['clickscount'] = 0;
            if ($rows['isactive'] == 1) {
            $domain = $_COMPANY->getAppDomain($_ZONE->val('app_type'));
            $emailLogs = Emaillog::GetAllEmailLogsSummary($domain,EmailLog::EMAILLOG_SECTION_TYPES['message'],$rows['messageid']);
            foreach($emailLogs as $log){
                    $rows['recipientcount'] += $log->val('total_rcpts') ?? 0;
                    $rows['openscount'] += $log->val('total_opens') ?? 0;
                    $rows['uniqueopenscount'] += $log->val('unique_opens') ?? 0;
                    $rows['clickscount'] += $log->val('total_clicks') ?? 0;
                }
            }

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