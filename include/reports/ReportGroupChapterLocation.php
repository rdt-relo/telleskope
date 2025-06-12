<?php

class ReportGroupChapterLocation extends Report
{
    public const META = array(
        'Fields' => array(
            'enc_groupid' => 'Group ID',
            'groupname' => 'Group Name',
            'group_regions' => 'Group Regions',
            'group_isactive' => 'Group Status',
            'enc_chapterid' => 'Chapter ID',
            'chaptername' => 'Chapter Name',
            'chapter_regions' => 'Chapter Region',
            'chapter_isactive' => 'Chapter Status',
            'branchname' => 'Location Name',
            'branchtype' => 'Location Type',
            'city' => 'Location City',
            'state' => 'Location State',
            'country' => 'Location Country',
            'branch_region' => 'Location Region',
            'branch_count_of_employees' => 'Location Count of Employees',
            'branch_createdon' => 'Location Created On',
        ),
        'Options' => array(),
        'Filters' => array(
            'groupids' => array(),
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_GROUPCHAPTER_LOCATION;}
    protected static function GetReportMeta() : array {
        global $_COMPANY;
        $reportmeta = self::META;

        // Ignore custom report values for groupname, chaptername and channelname and set them to what is defined in the zone
        $reportmeta['Fields']['enc_groupid'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' ID';
        $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Name';
        $reportmeta['Fields']['group_isactive'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Status';
        $reportmeta['Fields']['group_regions'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Regions';

        $reportmeta['Fields']['enc_chapterid'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' ID';
        $reportmeta['Fields']['chaptername'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' Name';
        $reportmeta['Fields']['chapter_isactive'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' Status';
        $reportmeta['Fields']['chapter_regions'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' Region';

        return $reportmeta;
    }

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        $select = "SELECT 
               `groups`.groupname,
               `groups`.`groupid`,
               `groups`.regionid AS group_regionids,
               `groups`.isactive AS group_isactive,
               chapters.chaptername,
               `chapters`.`chapterid`,
               chapters.regionids as chapter_regionids,
               chapters.isactive as chapter_isactive,
               companybranches.branchname,
               companybranches.branchtype,
               companybranches.city,
               companybranches.state,
               companybranches.country,
               companybranches.regionid AS branch_regionid,
               companybranches.employees AS branch_count_of_employees,
               companybranches.createdon AS branch_createdon
            FROM `groups` 
                JOIN chapters USING (groupid, companyid)
                JOIN companybranches ON find_in_set(branchid,chapters.branchids)
            WHERE `groups`.companyid={$_COMPANY->id()}
                AND `groups`.zoneid={$_ZONE->id()}
                AND `companybranches`.companyid={$_COMPANY->id()}
                ";
            $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
        
        $status = array('1' => 'Active', '2' => "Draft", '0' => "Inactive", '100' => "Pending Deletion");
        $reportTimezone = $_SESSION['timezone'] ?? 'UTC';

        while (@$rows = mysqli_fetch_assoc($result)) {

            if (!empty($rows)) {
                $rows['group_regions'] = $this->getRegionNamesAsCSV($rows['group_regionids']);
                $rows['chapter_regions'] = $this->getRegionNamesAsCSV($rows['chapter_regionids']);
                $rows['branch_region'] = $this->getRegionNamesAsCSV($rows['branch_regionid']);
                $rows['branch_createdon'] = $rows['branch_createdon'] ? $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['branch_createdon'], $reportTimezone) : '';
                $rows['group_isactive'] = $status[$rows['group_isactive']];
                $rows['chapter_isactive'] = $status[$rows['chapter_isactive']];                

                $rows['enc_groupid'] = $_COMPANY->encodeIdForReport($rows['groupid']);
                $rows['enc_chapterid'] = $_COMPANY->encodeIdForReport($rows['chapterid']);
            }
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

        return $reportmeta;
    }
}