<?php

class ReportAnnouncement extends Report
{
    public const META = array(
            'Fields' => array(
                'enc_postid' => 'Announcement ID',
                'enc_groupid' => 'Group ID',
                'groupname' => 'Group Name',
                'enc_chapterid' => 'Chapter ID',
                'chaptername' => 'Chapter Name',
                'enc_channelid' => 'Channel ID',
                'channelname' => 'Channel Name',
                'dynamic_list' => 'Dynamic List',
                'title' => 'Title',
                'no_of_likes' => 'No of Like reactions',
                'no_of_celebrate_reactions' => 'No of Celebrate reactions',
                'no_of_support_reactions' => 'No of Support reactions',
                'no_of_insightful_reactions' => 'No of Insightful reactions',
                'no_of_love_reactions' => 'No of Love reactions',
                'no_of_gratitude_reactions' => 'No of Gratitude reactions',
                'no_of_comments' => 'No of Comments',
                'createdate' => 'Create Date',
                'publishedby' => 'Published By',
                'publishdate' => 'Publish Date',
                'isactive' => 'Status',
                'recipientcount' => 'Number of Email Recipients',
                'openscount' => 'Total number of Email Opens',
                'uniqueopenscount' => 'Unique number of Email Opens'
            ),
            'Options' => array(
                'startDate' => null,
                'endDate' => null,
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

    protected static function GetReportType() : int { return self::REPORT_TYPE_ANNOUNCEMENT;}
    protected static function GetReportMeta() : array { return self::META;}

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
            if(in_array( 0 , $meta['Filters']['groupids']) !== false){
                $groupid_filter = " AND (post.groupid IN ({$groupid_list}) OR post.groupid=0)";
            }else{ 
                $groupid_filter = " AND post.groupid IN ({$groupid_list})";
            }
        }

        $chapterids_count = count($meta['Filters']['chapterids'] ?? array());
        $channelids_count = count($meta['Filters']['channelids'] ?? array());

        $filter_out_00 = '';
        if ($chapterids_count && $channelids_count) {
            $filter_out_00 = "AND !(chapters.chapterid is null AND group_channels.channelid is null)";
        }

        $startDateCondtion = "";
        if (!empty($meta['Options']['startDate'])) {
            $meta['Options']['startDate'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['startDate']) ?: '2200-12-31 00:00:00';
            $startDateCondtion = " AND post.`postedon` >= '{$meta['Options']['startDate']}' ";
        }

        $endDateCondtion = "";
        if (!empty($meta['Options']['endDate'])) {
            $meta['Options']['endDate'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['endDate']) ?: '1900-12-31 00:00:00';
            $endDateCondtion = " AND post.`postedon` <= '{$meta['Options']['endDate']}' ";
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        $select = "SELECT post.*,post.`isactive` as poststatus,
        IFNULL( groups.groupname,'Global') as groupname,
        IFNULL((SELECT GROUP_CONCAT(`chaptername`) FROM `chapters` WHERE FIND_IN_SET(`chapterid`,post.chapterid)),'') as chaptername,group_channels.channelname
        FROM post 
        LEFT JOIN `groups` ON groups.groupid=post.groupid AND groups.isactive=1
        LEFT JOIN group_channels ON group_channels.channelid=post.channelid
        WHERE post.companyid='{$this->cid()}' AND `post`.zoneid='{$_ZONE->id()}'
        AND (post.`isactive`>0 
        {$groupid_filter}
        {$startDateCondtion}
        {$endDateCondtion}
        {$filter_out_00}
        ) 
        order by post.postid DESC {$this->policy_limit}";
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

            $rows['enc_postid'] = $_COMPANY->encodeIdForReport($rows['postid']);

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

            if (isset($meta['Fields']['chaptername'])) {
                $rows['chaptername'] = $this->getChapterNamesAsCSV($rows['chapterid']);
            }

            if (isset($meta['Fields']['channelname'])) {
                $rows['channelname'] = $this->getChannelNamesAsCSV($rows['channelid']);
            }
            $rows['dynamic_list'] = '-';
            if($rows['listids']){
                $rows['dynamic_list'] = $this->getDynamicListNamesCSV($rows['listids']);
            }

            $timezone = (!empty($rows['timezone'])) ? $rows['timezone'] : 'UTC';

            $rows['createdate'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['postedon'], $timezone);

            if ($rows['publishdate']) {
                $rows['publishdate'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['publishdate'], $timezone);
            } else {
                $rows['publishdate'] = '-';
            }

            $publishedby = '-';
            if ($rows['publishedby']) {
                $publisher = User::GetUser($rows['publishedby']);
                $publishedby  = $publisher ? $publisher->getFullName() : '';
            }
            $rows['publishedby']  = $publishedby;
            
            $rows['no_of_likes'] = 0;
            if (isset($meta['Fields']['no_of_likes']) && $rows['poststatus'] == 1) {
                $rows['no_of_likes'] = Post::GetLikeTotals($rows['postid'], 'like');
            }

            $rows['no_of_celebrate_reactions'] = 0;
            if (isset($meta['Fields']['no_of_celebrate_reactions']) && $rows['poststatus'] == 1) {
                $rows['no_of_celebrate_reactions'] = Post::GetLikeTotals($rows['postid'], 'celebrate');
            }

            $rows['no_of_support_reactions'] = 0;
            if (isset($meta['Fields']['no_of_support_reactions']) && $rows['poststatus'] == 1) {
                $rows['no_of_support_reactions'] = Post::GetLikeTotals($rows['postid'], 'support');
            }

            $rows['no_of_insightful_reactions'] = 0;
            if (isset($meta['Fields']['no_of_insightful_reactions']) && $rows['poststatus'] == 1) {
                $rows['no_of_insightful_reactions'] = Post::GetLikeTotals($rows['postid'], 'insightful');
            }

            $rows['no_of_love_reactions'] = 0;
            if (isset($meta['Fields']['no_of_love_reactions']) && $rows['poststatus'] == 1) {
                $rows['no_of_love_reactions'] = Post::GetLikeTotals($rows['postid'], 'love');
            }

            $rows['no_of_gratitude_reactions'] = 0;
            if (isset($meta['Fields']['no_of_gratitude_reactions']) && $rows['poststatus'] == 1) {
                $rows['no_of_gratitude_reactions'] = Post::GetLikeTotals($rows['postid'], 'gratitude');
            }

            $rows['no_of_comments'] = 0;
            if (isset($meta['Fields']['no_of_comments']) && $rows['poststatus'] == 1) {
                $rows['no_of_comments'] = Post::GetCommentsTotal($rows['postid']);
            }

            $rows['recipientcount'] = 0;
            $rows['openscount'] = 0;
            $rows['uniqueopenscount'] = 0;
            if ((isset($meta['Fields']['recipientcount']) || isset($meta['Fields']['openscount']) || isset($meta['Fields']['uniqueopenscount'])) && $rows['poststatus'] == 1) {
                $domain = $_COMPANY->getAppDomain($_ZONE->val('app_type'));
                $emailLogs = Emaillog::GetAllEmailLogsSummary($domain, EmailLog::EMAILLOG_SECTION_TYPES['post'], $rows['postid']);
                foreach ($emailLogs as $log) {
                    $rows['recipientcount'] += ($log->val('total_rcpts') ?? 0);
                    $rows['openscount'] += ($log->val('total_opens') ?? 0);
                    $rows['uniqueopenscount'] += ($log->val('unique_opens') ?? 0);
                }
            }

            $rows['enc_groupid'] = $_COMPANY->encodeIdsInCSVForReport($rows['groupid']);
            $rows['enc_chapterid'] = $_COMPANY->encodeIdsInCSVForReport($rows['chapterid']);
            $rows['enc_channelid'] = $_COMPANY->encodeIdsInCSVForReport($rows['channelid']);

            $rows['isactive'] = self::CONTENT_STATUS_MAP[$rows['poststatus']];
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

        $reportmeta['Fields']['enc_postid'] = POST::GetCustomName(false) . ' ID';

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