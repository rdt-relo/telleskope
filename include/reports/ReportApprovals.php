<?php

class ReportApprovals extends Report {

    public const META = array(
        'Fields' => array(
            'enc_topicid' => 'ID',
            'title' => 'Title',
            'enc_groupid' => 'Group ID',
            'groupname' => 'Group',
            'enc_chapterid' => 'Chapter ID',
            'chaptername' => 'Chapter',
            'enc_channelid' => 'Channel ID',
            'channelname' => 'Channel',
            'publish_status' => 'Publish Status',
            'event_start' => 'Start Date',
            'event_end' => 'End Date',
            'createdon'=>  'Create Date',
            'submitter_name' => 'Submitter Name',
            'submitter_email' => 'Submitter Email',
            'submitter_externalid' => 'Submitter Employee Id',
            'approval_stage_details' => 'Approval Stage Details',
            'approval_createdon' => 'Approval Request Date',
            'approval_status' => 'Approval Status',
        ),
        'AdminFields' => array(
        ),
        'Options' => array(
            'topictype' => null,
            'startDate' => null,
            'endDate' => null,
        ),
        'Filters' => array(
            'groupid' => ''
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType(): int
    {
        return self::REPORT_TYPE_APPROVALS;
    }

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $_USER, $db;

        $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        $start_date_condition = '';
        if ($meta['Options']['startDate'] && Sanitizer::SanitizeUTCDatetime($meta['Options']['startDate'])) {
            $start_date_condition = " AND `topic_approvals`.`createdon` >= '{$meta['Options']['startDate']}'";
        }

        $end_date_condition = '';
        if ($meta['Options']['endDate'] && Sanitizer::SanitizeUTCDatetime($meta['Options']['endDate'])) {
            $end_date_condition = " AND `topic_approvals`.`createdon` <= '{$meta['Options']['endDate']}'";
        }

        $groupid = $meta['Filters']['groupid'];

        $join_clause = '';
        $groupid_condition = '';

        $TopicClass = Teleskope::TOPIC_TYPE_CLASS_MAP[$meta['Options']['topictype']];

        $table = "";

        if ($meta['Options']['topictype'] == Teleskope::TOPIC_TYPES['POST']) {
            $table = "post";
            $join_clause = " INNER JOIN `post` ON `topic_approvals`.`topicid` = `post`.`postid`";
            if ($groupid) {
                $groupid_condition = "AND (`post`.`groupid` = {$groupid})";
            } elseif ($groupid === 0) {
                $groupid_condition = "AND (`post`.`groupid` = 0)";
            }
        } elseif ($meta['Options']['topictype'] == Teleskope::TOPIC_TYPES['NEWSLETTER']) {
            $table = "newsletters";
            $join_clause = " INNER JOIN `newsletters` ON `topic_approvals`.`topicid` = `newsletters`.`newsletterid`";
            if ($groupid) {
                $groupid_condition = "AND (`newsletters`.`groupid` = {$groupid})";
            } elseif ($groupid === 0) {
                $groupid_condition = "AND (`newsletters`.`groupid` = 0)";
            }
        } elseif ($meta['Options']['topictype'] == Teleskope::TOPIC_TYPES['EVENT']) {
            $table = "events";
            $join_clause = " INNER JOIN `events` ON `topic_approvals`.`topicid` = `events`.`eventid`";
            if ($groupid) {
                $groupid_condition = "AND (
                    (`events`.`groupid` = {$groupid})
                    OR (`events`.`groupid` = 0 AND FIND_IN_SET({$groupid}, `events`.`collaborating_groupids`))
                )";
            } elseif ($groupid === 0) {
                $groupid_condition = "AND (`events`.`groupid` = 0 AND `events`.`collaborating_groupids` = '')";
            }
        } elseif ($meta['Options']['topictype'] == Teleskope::TOPIC_TYPES['SURVEY']) {
            $table = "surveys_v2";
            $join_clause = " INNER JOIN `surveys_v2` ON `topic_approvals`.`topicid` = `surveys_v2`.`surveyid`";
            if ($groupid) {
                $groupid_condition = "AND (`surveys_v2`.`groupid` = {$groupid})";
            } elseif ($groupid === 0) {
                $groupid_condition = "AND (`surveys_v2`.`groupid` = 0)";
            }
        } else {
            return; // Do not allow topic approvals to be downloaded without the topicType
        }

        // Ensure $table is not empty to prevent errors in the SELECT query
        $table_select = $table ? ", {$table}.*" : "";

        // Fixed SQL query to avoid ambiguous column errors for topicid column as we have a topicid varchar column in surveys_v2 table too
        $select = "
            SELECT 
                `topic_approvals`.`topicid` AS topic_approvals_topicid,
                `topic_approvals`.* 
                {$table_select}
            FROM        `topic_approvals`
            {$join_clause}
            WHERE       `topic_approvals`.`companyid` = {$_COMPANY->id()}
            AND         `topic_approvals`.`zoneid` = {$_ZONE->id()}
            AND         `topic_approvals`.`topictype` = '{$meta['Options']['topictype']}'
            {$start_date_condition}
            {$end_date_condition}
            {$groupid_condition}
            ORDER BY    `topic_approvals`.`createdon` DESC
            {$this->policy_limit}
        ";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        $globalConfigurationForAllStages = $TopicClass::GetAllApprovalConfigurationRows(0, 0, 0);

        foreach ($globalConfigurationForAllStages as $stage_config) {
            if (empty($stage_config['approvers'])) {
                continue;
            }

            $stage = $stage_config['approval_stage'];

            if (!isset($meta['Fields']["stage_approval_status_{$stage}"])) {
                $meta['Fields']["stage_approval_status_{$stage}"] = "Stage {$stage} - Approval Status";
            }

            if (!isset($meta['Fields']["stage_approval_notes_{$stage}"])) {
                $meta['Fields']["stage_approval_notes_{$stage}"] = "Stage {$stage} - Approval Notes";
            }

            if (!isset($meta['Fields']["stage_approval_approver_name_{$stage}"])) {
                $meta['Fields']["stage_approval_approver_name_{$stage}"] = "Stage {$stage} - Approver Name";
            }

            if (!isset($meta['Fields']["stage_approval_approver_email_{$stage}"])) {
                $meta['Fields']["stage_approval_approver_email_{$stage}"] = "Stage {$stage} - Approver Email";
            }

            if (!isset($meta['Fields']["stage_approval_approver_externalid_{$stage}"])) {
                $meta['Fields']["stage_approval_approver_externalid_{$stage}"] = "Stage {$stage} - Approver Employee ID";
            }

            if (!isset($meta['Fields']["stage_approval_date_{$stage}"])) {
                $meta['Fields']["stage_approval_date_{$stage}"] = "Stage {$stage} - Approval Date";
            }
        }

        unset($meta['Fields']['approval_stage_details']);

        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char);

        while ($approval = mysqli_fetch_assoc($result)) {
            $topic = Teleskope::GetTopicObj($approval['topictype'], $approval['topic_approvals_topicid']);
            if (!$topic) {
                continue;
            }

            $approval_obj = Approval::Hydrate($approval['approvalid'], $approval, $topic);

            $approval['enc_topicid'] = $_COMPANY->encodeIdForReport($topic->id());

            $approval['title'] = $topic->getTopicTitle();

            if ($topic->val('groupid')) {
                $approval['groupname'] = Group::GetGroupName($topic->val('groupid'));
                $approval['enc_groupid'] = $_COMPANY->encodeIdForReport($topic->val('groupid'));
            } else {
                if (empty($topic->val('collaborating_groupids'))) {
                    $approval['groupname'] = 'Global';
                } else {
                    $groupNames = [];
                    $collaboratedWithGroups = explode(',', $topic->val('collaborating_groupids'));
                    foreach($collaboratedWithGroups as $collaboratedWithGroup) {
                        $groupNames[] = Group::GetGroupName($collaboratedWithGroup);
                    }
                    $approval['groupname'] = Arr::NaturalLanguageJoin($groupNames, ' & ');
                    $approval['enc_groupid'] = $_COMPANY->encodeIdsInCSVForReport($topic->val('collaborating_groupids'));
                }
            }

            $approval['chaptername'] = $this->getChapterNamesAsCSV($topic->val('chapterid'));
            $approval['enc_chapterid'] = $_COMPANY->encodeIdsInCSVForReport($topic->val('chapterid'));
            $approval['channelname'] = $this->getChannelNamesAsCSV($topic->val('channelid'));
            $approval['enc_channelid'] = $_COMPANY->encodeIdsInCSVForReport($topic->val('channelid'));

            $topicStatusMap = array(0 => 'Cancelled', 1 => 'Published', 2 => 'Draft', 3 => 'Under Review', 4 => 'Reviewed', 5 => 'Pending Publish');
            $approval['publish_status'] = $topicStatusMap[$topic->val('isactive')];

            if ($meta['Options']['topictype'] === Teleskope::TOPIC_TYPES['EVENT']) {
                $approval['event_start'] = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($topic->val('start'), true, true, true);
                $approval['event_end'] = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($topic->val('end'), true, true, true);
                $approval['createdon'] = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($topic->val('addedon'), true, true, true);
            }

            if ($meta['Options']['topictype'] === Teleskope::TOPIC_TYPES['POST']) {
                $approval['createdon'] = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($topic->val('postedon'), true, true, true);
            }

            if ($meta['Options']['topictype'] === Teleskope::TOPIC_TYPES['NEWSLETTER']) {
                $approval['createdon'] = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($topic->val('createdon'), true, true, true);
            }

            $approval['approval_status'] = ucwords($approval_obj->val('approval_status'));
            if ($approval_obj->val('approval_status') == 'requested') { // Add more details
                $approval['approval_status'] .= '(Stage ' . $approval_obj->val('approval_stage') . ')';
            }
            $approval['approval_createdon'] = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($approval_obj->val('createdon'), true, true, true);

            $submitted_by_user = User::GetUser($approval['createdby']);
            $approval['submitter_name'] = $submitted_by_user?->getFullName() ?? 'Deleted User';
            $approval['submitter_email'] = $submitted_by_user?->val('email') ?? 'Deleted User';
            $approval['submitter_externalid'] = User::ExtractExternalId($submitted_by_user?->val('externalid') ?? '');

            foreach ($globalConfigurationForAllStages as $stage_config) {
                if (empty($stage_config['approvers'])) {
                    continue;
                }

                $stage = $stage_config['approval_stage'];
                $approval_note = $approval_obj->getMostRecentStageApproval($stage);

                if (!$approval_note) {
                    continue;
                }

                $approval["stage_approval_status_{$stage}"] = ucwords($approval_note->val('approval_log_type'));
                $approval["stage_approval_notes_{$stage}"] = $approval_note->val('log_notes');

                if ($approval_note->val('approval_log_type') === 'auto_approved') {
                    $approval["stage_approval_approver_name_{$stage}"] = 'Auto Approver';
                    $approval["stage_approval_approver_email_{$stage}"] = '';
                    $approval["stage_approval_approver_externalid_{$stage}"] = '';
                } else {
                    $approver = User::GetUser($approval_note->val('createdby'));
                    $approval["stage_approval_approver_name_{$stage}"] = $approver?->getFullName() ?? 'Deleted User';
                    $approval["stage_approval_approver_email_{$stage}"] = $approver?->val('email') ?? 'Deleted User';
                    $approval["stage_approval_approver_externalid_{$stage}"] = User::ExtractExternalId($approver?->val('externalid') ?? '');
                }

                $approval["stage_approval_date_{$stage}"] =  $_USER->formatUTCDatetimeForDisplayInLocalTimezone($approval_note->val('createdon'), true, true, true);
            }

            $csv_row = [];
            foreach ($meta['Fields'] as $key => $value) {
                $csv_row[] = html_entity_decode($approval[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
            }
            fputcsv($file_h, $csv_row, $delimiter, $enclosure, $escape_char);
        }
    }

    protected static function GetReportMeta(): array
    {
        return self::META;
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

    public static function GetDefaultReportRecForDownloadByTopicType(string $topicType): array
    {
        $reportmeta = self::GetDefaultReportRecForDownload();
        if ($topicType) {
            if ($topicType != Teleskope::TOPIC_TYPES['EVENT']) {
                unset($reportmeta['Fields']['event_start']);
                unset($reportmeta['Fields']['event_end']);
            }

            $topicLabel = Teleskope::TOPIC_TYPES_ENGLISH[$topicType];

            if ($reportmeta['Fields']['enc_topicid'] == 'ID') {
                $reportmeta['Fields']['enc_topicid'] = $topicLabel . ' ID';
            }

        }
        return $reportmeta;
    }
}
