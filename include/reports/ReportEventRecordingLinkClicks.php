<?php

class ReportEventRecordingLinkClicks extends Report
{
    public const META = [
        'Fields' => array(
            'email' => 'Email',
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'jobtitle' => 'Job Title',
            'first_clicked_at' => 'First Clicked At',
            'total_clicks' => 'Total Clicks',
        ),
        'Filters' => array(
            'eventid' => ''
        )
    ];

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_EVENT_RECORDING_LINK_CLICKS;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }

        if (empty($meta['Filters']['eventid'])) {
            return;
        } else {
            $event_id = $meta['Filters']['eventid'];
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.

        $select = "
            SELECT
                `users`.`email`, `users`.`firstname`, `users`.`lastname`, `users`.`jobtitle`,
                MIN(`clicked_at`) AS `first_clicked_at`,
                COUNT(*) AS `total_clicks`
            FROM `event_recording_link_clicks`
                INNER JOIN `events` ON `event_recording_link_clicks`.`eventid` = `events`.`eventid` 
                LEFT JOIN `users` ON `event_recording_link_clicks`.`userid` = `users`.`userid` 
            WHERE `event_recording_link_clicks`.`eventid` = {$event_id} AND `events`.`companyid`={$this->cid()} AND events.zoneid={$_ZONE->id()} 
              AND (isnull(users.companyid) OR users.companyid={$this->cid()}) 
            GROUP BY `event_recording_link_clicks`.`userid`
            ORDER BY `first_clicked_at` ASC
            {$this->policy_limit}
        ";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$rows = mysqli_fetch_assoc($result)) {
            $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
            $rows['first_clicked_at'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['first_clicked_at'], $reportTimezone);
            $rows['email'] = $rows['email'] ?? 'User Deleted';
            $rows['firstname'] = $rows['firstname'] ?? '-';
            $rows['lastname'] = $rows['lastname'] ?? '-';
            $rows['jobtitle'] = $rows['jobtitle'] ?? '-';

            $row = array();
            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }

            fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
        }
    }
}
