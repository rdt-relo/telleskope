<?php

Class ReportSurveyData extends Report{

    public const META = array(
        'Fields' => array(
            'enc_surveyid' => 'Survey ID',
            'surveyname' => 'Survey Title',
            'enc_groupid' => 'Group ID',
            'groupname' => 'Group Name',
            'enc_chapterid' => 'Chapter ID',
            'chaptername' => 'Chapter Name',
            'enc_channelid' => 'Channel ID',
            'channelname' => 'Channel Name',
            'createdon' => 'Created On Date',
            'createdby' => 'Created By',
            'numresponses' => 'Number of Responses',
            'publishedby' => 'Published By',
            'publishdate' => 'Publish Date'
        ),
        'AdminFields' => array(
        ),
        'Options' => array(
            'includeInactiveSurveys' => false,
        ),
        'Filters' => array(
            'groupid' => 0,
            'surveyid' => 0,
        )
    );


    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_SURVEY_DATA;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        $groupid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
            $groupid_list = implode(',', $meta['Filters']['groupids']);
            $groupid_filter = " AND s.groupid IN ({$groupid_list})";
        }

        $startDateCondtion = "";
        if (!empty($meta['Options']['startDate'])) {
            $meta['Options']['startDate'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['startDate']) ?: '2200-12-31 00:00:00';
            $startDateCondtion = " AND s.`publishdate` >= '{$meta['Options']['startDate']}' ";
        }

        $endDateCondtion = "";
        if (!empty($meta['Options']['endDate'])) {
            $meta['Options']['endDate'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['endDate']) ?: '1900-12-31 00:00:00';
            $endDateCondtion = " AND s.`publishdate` <= '{$meta['Options']['endDate']}' ";
        }

        $statusCondtion = "  AND s.isactive = '1'";
        if ($meta['Options']['includeInactiveSurveys']) {
            $statusCondtion = "  AND s.isactive IN(1, 0)";
        }

        $meta['Fields']['surveyTrigger'] = "Survey Trigger";
        $meta['Fields']['surveyStaus'] = "Survey Status";
        $meta['Fields']['anonymous'] = "Is Anonymous";
        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.

        $select = "SELECT
        s.surveyid,s.surveyname,g.groupname,g.groupid,ch.chapterid,ch.chaptername,cn.channelname,cn.channelid,s.createdon,s.createdby,s.surveysubtype,s.surveytype,s.isactive,s.anonymous,s.publishdate,s.publishedby,
        (SELECT COUNT(1) FROM `survey_responses_v2` WHERE `surveyid`=s.surveyid) AS 'numresponses'
        FROM
        surveys_v2 AS s
        LEFT JOIN `groups` AS g ON s.groupid = g.groupid
        LEFT JOIN chapters AS ch ON s.chapterid = ch.chapterid
        LEFT JOIN group_channels AS cn ON s.channelid = cn.channelid
        WHERE
        s.companyid={$this->cid()} AND s.zoneid={$_ZONE->id()}
        {$groupid_filter}
        {$startDateCondtion}
        {$endDateCondtion}
        {$statusCondtion}
        {$this->policy_limit}
        ";

        $surveyTriggers = array_flip(Survey2::SURVEY_TRIGGER);
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$rows = mysqli_fetch_assoc($result)) {
        $rows['enc_surveyid'] = $_COMPANY->encodeIdForReport($rows['surveyid']);
        $rows['groupname'] = $rows['groupname'] ?? 'Global';
        $rows['chaptername'] = $rows['chaptername'] ?? '-';
        $rows['channelname'] = $rows['channelname'] ?? '-';
        $survey_tz = (!empty($_SESSION['timezone'])) ? $_SESSION['timezone'] : 'UTC';
        $rows['createdon'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['createdon'], $survey_tz);
        $rows['publishdate'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['publishdate'], $survey_tz);

        $publishedby = '-';
        if ($rows['publishedby']) {
            $publisher = User::GetUser($rows['publishedby']);
            $publishedby  = $publisher ? $publisher->getFullName() : '-';
        }
        $rows['publishedby']  = $publishedby;

        if (!empty($rows['createdby'])) {
            $user = User::GetUser($rows['createdby']);
            $rows["createdby"] = $user ? $user->getFullName() : 'Deleted User';
        }
        $rows['surveyTrigger'] = ucfirst(strtolower($surveyTriggers[$rows['surveysubtype']]));
        $rows['surveyStaus'] = $rows['isactive'] == 1 ? 'Active' : 'Inactive';
        $rows['anonymous'] = $rows['anonymous'] == 1 ? 'True' : 'False';

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
        /* @var Company $_COMPANY */
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