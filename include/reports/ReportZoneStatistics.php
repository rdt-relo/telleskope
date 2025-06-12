<?php

class ReportZoneStatistics extends Report
{
    public const META = array(
        'Fields' => array(
            'groupname' => 'Group Name',
            'stat_date' => 'Date',
            'number_of_groups' => 'Total Groups',
            'number_of_groups' => 'Total Office Locations',
            'zone_admins' => 'Total Zone Admins',
            'user_members_total' => 'Group Total Members',
            'user_members_unique' => 'Group Unique Members',
            'user_members_1group' => 'Member one',
            'user_members_2group' => 'Member Two',
            'user_members_3group' => 'Member Two + Groups',
            'events_published' => 'Published Events',
            'posts_published' => 'Published Announcements',
            'newsletters_published' => 'Published Newsletters',
            'album_media_published' => 'Published Album Media',
        ),
        'AdminFields' => array(
        ),
        'Options' => array(
        ),
        'Filters' => array(
        )
    );
    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_ZONE_STATS;}
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
  
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();
        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }
        $groupid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
            $groupid_list = implode(',', $meta['Filters']['groupids']);
            $groupid_filter = " AND `g`.`groupid` IN ({$groupid_list})";
        }
        $startDateCondtion = "";
        $start_date = "";
        if (!empty($meta['Options']['startDate'])) {
            $startDateCondtion = " AND `zg`.`stat_date` >= '{$meta['Options']['startDate']} 00:00:00' ";
            $start_date = $meta['Options']['startDate'];
        }
        $endDateCondtion = "";
        $end_date = "";
        if (!empty($meta['Options']['endDate'])) {
             $endDateCondtion = " AND `zg`.`stat_date` <= '{$meta['Options']['endDate']} 23:59:59' ";
             $end_date = $meta['Options']['endDate'];
        }
        $zoneContentStatsMonthly = "SELECT
        `g`.`groupname`,
        `sgdc`.`stat_date`,
        `zdc`.`number_of_groups`,
        `zg`.`zone_admins`,
        `zg`.`user_members_total`,
        `zg`.`user_members_unique`,
        `zg`.`user_members_1group`,
        `zg`.`user_members_2group`,
        `zg`.`user_members_3group`,
        SUM(`sgdc`.`events_published`) AS `events_published`,
        SUM(`sgdc`.`posts_published`) AS `posts_published`,
        SUM(`sgdc`.`newsletters_published`) AS `newsletters_published`,
        SUM(`sgdc`.`album_media_published`) AS `album_media_published`
      FROM
        `stats_groups_daily_count` `sgdc`
        JOIN `groups` `g` ON `sgdc`.`groupid` = `g`.`groupid` AND `sgdc`.`companyid` = `g`.`companyid`
        JOIN `stats_zones_daily_count` `zg` ON `sgdc`.`zoneid` = `zg`.`zoneid` AND `sgdc`.`companyid` = `zg`.`companyid`
        JOIN (SELECT `zoneid`, `stat_date`, `number_of_groups` FROM `stats_zones_daily_count` WHERE `companyid` = {$_COMPANY->id()} AND `zoneid` = {$_ZONE->id()} AND `stat_date` BETWEEN '{$start_date}' AND '{$end_date}') `zdc` ON `zg`.`zoneid` = `zdc`.`zoneid` AND `sgdc`.`stat_date` = `zdc`.`stat_date`
      WHERE
      `sgdc`.`companyid` = {$_COMPANY->id()}
      AND `sgdc`.`zoneid` = {$_ZONE->id()}
      {$startDateCondtion}
      {$endDateCondtion}
      {$groupid_filter}
      GROUP BY
        `g`.`groupname`,
        `sgdc`.`stat_date`,
        `zdc`.`number_of_groups`,
        `zg`.`zone_admins`,
        `zg`.`user_members_total`,
        `zg`.`user_members_unique`,
        `zg`.`user_members_1group`,
        `zg`.`user_members_2group`,
        `zg`.`user_members_3group`";
        $result_zonecontentstatsmonthly = mysqli_query($dbc, $zoneContentStatsMonthly) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $zoneContentStatsMonthly]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
        
        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
     
        while (@$rows = mysqli_fetch_assoc($result_zonecontentstatsmonthly)) {
            $row = array();
            $stat_tz = (!empty($rows['timezone'])) ? $rows['timezone'] : 'UTC';
            $rows['start'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['start'], $stat_tz);
            $rows['end'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['end'], $stat_tz);
            foreach ($meta['Fields'] as $key => $value) {
                $row[] = html_entity_decode($rows[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $row, $delimiter, $enclosure, $escape_char);
        }
    
    }
}