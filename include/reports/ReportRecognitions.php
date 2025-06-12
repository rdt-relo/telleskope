<?php

class ReportRecognitions extends Report
{
    public const META = array(
        'Fields' => array(
            'enc_groupid' => 'Group ID',
            'groupname' => 'Group',
            'recognized_to' => 'Person Recognized',
            'recognized_by' => 'Recognized by',
            'recognitiondate' => 'Recognition date',
        ),
        'Options' => array(),
        'Filters' => array(
            'groupids' => array()
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_RECOGNITION;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();
        $groupCondtion = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
            $groupid_list = implode(',', $meta['Filters']['groupids']);
            $groupCondtion = " AND a.groupid IN ({$groupid_list})";
        }

        $startDateCondtion = "";
        if (!empty($meta['Options']['startDate'])) {
            $meta['Options']['startDate'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['startDate']) ?: '2200-12-31 00:00:00';
            $startDateCondtion = " AND a.`recognitiondate` >= '{$meta['Options']['startDate']}' ";
        }

        $endDateCondtion = "";
        if (!empty($meta['Options']['endDate'])) {
            $meta['Options']['endDate'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['endDate']) ?: '1900-12-31 00:00:00';
            $endDateCondtion = " AND a.`recognitiondate` <= '{$meta['Options']['endDate']}' ";
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        $select = "SELECT a.*,g.groupname, b.firstname as firstname_by,b.lastname as lastname_by,b.jobtitle as jobtitle_by,b.picture as picture_by,b.email as email_by,c.firstname as firstname_to,c.lastname as lastname_to,c.jobtitle as jobtitle_to,c.picture as picture_to,c.email as email_to,a.custom_fields
                    FROM recognitions a 
                        JOIN `groups` g ON a.groupid=g.groupid 
                        LEFT JOIN users b ON b.userid=a.recognizedby 
                        LEFT JOIN `users` as c ON c.userid=a.recognizedto 
                        WHERE a.companyid='{$_COMPANY->id()}' AND a.zoneid={$_ZONE->id()} 
                          AND a.isactive='1'
                          {$groupCondtion}
                          {$startDateCondtion}
                          {$endDateCondtion} {$this->policy_limit}";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        $chapterFilter = array();
        if (!empty($meta['Filters']['chapterids'])) {
            $chapterFilter = $meta['Filters']['chapterids'];
        }
        while (@$rows = mysqli_fetch_assoc($result)) {
            // Filter out chapter
            if (!empty($chapterFilter)) {
                if (empty(array_intersect($chapterFilter, explode(',', $rows['chapterid'])))) {
                    continue;
                }
            }
            //do something with $rows;
            $row = array();

            $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
            $rows['recognitiondate'] = $db->covertUTCtoLocalAdvance("Y-m-d", ' T', $rows['recognitiondate'], $reportTimezone);
            $rows['recognized_by'] = $rows['firstname_by'] . " " . $rows['lastname_by'];
            $rows['recognized_to'] = $rows['firstname_to'] . " " . $rows['lastname_to'];

            $rows['enc_groupid'] = $_COMPANY->encodeIdForReport($rows['groupid']);
            $rows = $this->addCustomFieldsToRow($rows, $meta);

            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
        }
    }

    public static function GetDefaultReportRecForDownload(): array
    {
        global $_COMPANY;

        $reportmeta = parent::GetDefaultReportRecForDownload();

        $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']["name-short"];
        $reportmeta['Fields']['enc_groupid'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' ID';

        return $reportmeta;
    }
}