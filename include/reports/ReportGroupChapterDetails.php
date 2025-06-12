<?php

class ReportGroupChapterDetails extends Report
{
    public const META = array(
        'Fields' => array(
            'enc_groupid' => 'Group ID',
            'groupname' => 'Group Name',
            'groupname_short' => 'Group Short Name',
            'group_type' => 'Group Type',
            'groupcategory' => 'Group Category',
            'group_isactive' => 'Group Status',
            'group_createdon' => 'Group Created On',
            'enc_chapterid' => 'Chapter ID',
            'chaptername' => 'Chapter Name',
            'chapter_isactive' => 'Chapter Status',
            'chapter_createdon' => 'Chapter Created On',
            'totalmembers' => 'Total Members',
            'region' => 'Region(s)',
            'branchname' => 'Location Name(s)',
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

    protected static function GetReportType(): int { return self::REPORT_TYPE_GROUP_CHAPTER_DETAILS;}

    protected static function GetReportMeta(): array {
        global $_COMPANY;
        $reportmeta = self::META;

        // Ignore custom report values for groupname, chaptername and channelname and set them to what is defined in the zone
        $reportmeta['Fields']['enc_groupid'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' ID';
        $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Name';
        $reportmeta['Fields']['groupname_short'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Short Name';
        $reportmeta['Fields']['group_type'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Type';
        $reportmeta['Fields']['groupcategory'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Category';
        $reportmeta['Fields']['group_isactive'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Status';
        $reportmeta['Fields']['group_createdon'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Created On';

        $reportmeta['Fields']['enc_chapterid'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' ID';
        $reportmeta['Fields']['chaptername'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' Name';
        $reportmeta['Fields']['chapter_isactive'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' Status';
        $reportmeta['Fields']['chapter_createdon'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' Created On';
        $reportmeta['Fields']['totalmembers'] = 'Total ' . $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' ' . $_COMPANY->getAppCustomization()['group']['memberlabel_plural'];
        $reportmeta['Fields']['region'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' Region';
        $reportmeta['Fields']['branchname'] = 'Locations Mapped to ' . $_COMPANY->getAppCustomization()['chapter']['name-short'];

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
        $select = "
            SELECT 
                groupname,
                `groups`.`groupid`,
                group_type, 
                categoryid,
                groupname_short,
                `groups`.`addedon` as `group_createdon`,
                `groups`.`isactive` as `group_isactive`,
                chaptername,
                `chapters`.`chapterid`,
                `chapters`.createdon as `chapter_createdon`,
                `chapters`.`isactive` as `chapter_isactive`,
                (SELECT GROUP_CONCAT(`region`) FROM `regions` WHERE `regionid` IN(chapters.regionids)) as region,
                (SELECT COUNT(1) FROM `groupmembers` WHERE groupmembers.groupid=chapters.groupid AND groupmembers.isactive=1 AND FIND_IN_SET(chapters.chapterid,`chapterid`)) as totalmembers,
                (SELECT GROUP_CONCAT(`branchname` separator ',\n') FROM `companybranches` WHERE companybranches.companyid='{$_COMPANY->id()}' AND (concat(',',chapters.branchids,',') like concat('%,',companybranches.branchid,',%'))) as branchname
            FROM chapters 
                JOIN `groups` USING (groupid)
            WHERE chapters.companyid='{$_COMPANY->id()}' 
              AND ( chapters.zoneid='{$_ZONE->id()}' 
            {$groupid_filter})
            ORDER BY groupname,chaptername ASC 
        "; // No policy limit needed here

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt' => $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        $status = array('1' => 'Active', '2' => "Draft", '0' => "Inactive", '100' => "Pending Deletion");
        $grouptype = array('0' => 'Open Membership', '10' => "Invites Only", '30' => "Membership by Request Only", '50' => "Membership Disabled");
        $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
        $groupCategories = Group::GetAllGroupCategories();

        while (@$rows = mysqli_fetch_assoc($result)) {

            if (!empty($rows)) {
                if (!(bool)strtotime($rows['group_createdon'])) {
                    $rows['group_createdon'] = date("Y-m-d H:i A", $rows['group_createdon']);
                }
                $rows['group_createdon'] = $rows['group_createdon'] ? $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['group_createdon'], $reportTimezone) : '';

                if (!(bool)strtotime($rows['chapter_createdon'])) {
                    $rows['chapter_createdon'] = date("Y-m-d H:i A", $rows['chapter_createdon']);
                }
                $rows['chapter_createdon'] = $rows['chapter_createdon'] ? $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['chapter_createdon'], $reportTimezone) : '';

                $rows['group_isactive'] = $status[$rows['group_isactive']];
                $rows['chapter_isactive'] = $status[$rows['chapter_isactive']];

                $rows['group_type'] = $grouptype[$rows['group_type']];
                $rows['groupcategory'] = Arr::SearchColumnReturnColumnVal($groupCategories, $rows['categoryid'], 'categoryid', 'category_name');
                if (isset($meta['Fields']['tags'])) {
                    $rows['tags'] = $this->getTagNameAsCSV($rows['tagids']);
                }

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