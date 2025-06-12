<?php

class ReportGroupDetails extends Report
{
    public const META = array(
        'Fields' => array(
            'enc_groupid' => 'Group ID',
            'groupname' => 'Group Name',
            'groupname_short' => 'Group Short Name',
            'group_type' => 'Group Type',
            'groupcategory' => 'Group Category',
            'isactive' => 'Status',
            'primarycolor' => 'Primary Color',
            'secondarycolor' => 'Secondary Color',
            'createdon' => 'Group Created On',
            'totalchapters' => 'Total Chapters',
            'totalchannels' => 'Total Channels',
            'totalmembers' => 'Total Members',
            'region' => 'Region(s)',
            'tags' => 'Tags',
        ),
        'Options' => array(

        ),
        'Filters' => array(
            'groupids' => array(),
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_GROUP_DETAILS;}
    protected static function GetReportMeta() : array {
        global $_COMPANY;
        $reportmeta = self::META;
        // Ignore custom report values for groupname, chaptername and channelname and set them to what is defined in the zone
        $reportmeta['Fields']['enc_groupid'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' ID';
        $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Name';
        $reportmeta['Fields']['groupname_short'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Short Name';
        $reportmeta['Fields']['group_type'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Type';
        $reportmeta['Fields']['groupcategory'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Category';
        $reportmeta['Fields']['isactive'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Status';
        $reportmeta['Fields']['createdon'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Created On';
        $reportmeta['Fields']['totalchapters'] = 'Total ' . $_COMPANY->getAppCustomization()['chapter']['name-short-plural'];
        $reportmeta['Fields']['totalchannels'] = 'Total ' . $_COMPANY->getAppCustomization()['channel']['name-short-plural'];
        $reportmeta['Fields']['totalmembers'] = 'Total ' . $_COMPANY->getAppCustomization()['group']['name-short'] . ' ' . $_COMPANY->getAppCustomization()['group']['memberlabel_plural'];
        $reportmeta['Fields']['region'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Region';
        return $reportmeta;
    }

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }

        $groupid_filter = ' AND `groups`.isactive IN(0,1,2,100)';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
            $groupid_list = implode(',', $meta['Filters']['groupids']);
            $groupid_filter = " AND `groups`.groupid IN ({$groupid_list})";
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        // Note group_concat does not work well in subquery, hench using like for regions.
        $select = "
        SELECT  
            `regionid`,
            `groupname`, 
            `groups`.`groupid`,
            group_type, 
            categoryid,
            groupname_short, 
            overlaycolor as primarycolor, 
            overlaycolor2 as secondarycolor,
            addedon as createdon,
            `groups`.`isactive`,
            `groups`.tagids,
             (SELECT COUNT(1) FROM `chapters` WHERE `groupid`=`groups`.groupid AND `isactive`=1) as totalchapters,
             (SELECT COUNT(1) FROM `group_channels` WHERE `groupid`=`groups`.groupid AND `isactive`=1) as totalchannels,
             (SELECT GROUP_CONCAT(`region` separator ', ') FROM `regions` WHERE regions.companyid='{$_COMPANY->id()}' AND (concat(',',groups.regionid,',') like concat('%,',regions.regionid,',%')) AND regions.isactive=1) as region,
             (SELECT COUNT(1) FROM `groupmembers` WHERE `groupmembers`.groupid = `groups`.groupid AND groupmembers.isactive=1) as totalmembers
        FROM `groups` 
        WHERE  groups.companyid='{$_COMPANY->id()}' 
          AND groups.zoneid='{$_ZONE->id()}'  
          {$groupid_filter}
        ";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        $status = array('1' => 'Active', '2' => "Draft", '0' => "Inactive", '100' => "Pending Deletion");
        $grouptype = array('0' => 'Open Membership', '10' => "Invites Only", '30' => "Membership by Request Only", '50' => "Membership Disabled");
        $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
        $groupCategories = Group::GetAllGroupCategories();

        while (@$rows = mysqli_fetch_assoc($result)) {

            if (!empty($rows)) {
                if (!(bool)strtotime($rows['createdon'])) {
                    $rows['createdon'] = date("Y-m-d H:i A", $rows['createdon']);
                }
                $rows['createdon'] = $rows['createdon'] ? $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['createdon'], $reportTimezone) : '';
                $rows['isactive'] = $status[$rows['isactive']];
                $rows['group_type'] = $grouptype[$rows['group_type']];
                $rows['groupcategory'] = Arr::SearchColumnReturnColumnVal($groupCategories, $rows['categoryid'], 'categoryid', 'category_name');
                if(isset($meta['Fields']['tags'])){
                    $rows['tags'] = $this->getTagNameAsCSV($rows['tagids']);
                }
                
                $rows['enc_groupid'] = $_COMPANY->encodeIdForReport($rows['groupid']);
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

        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            unset($reportmeta['Fields']['totalchapters']);
        }
        if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
            unset($reportmeta['Fields']['totalchannels']);
        }

        return $reportmeta;
    }
}