<?php

class ReportDirectMails extends Report
{
    public const META = array(
        'Fields' => array(
            'enc_messageid' => 'Message ID',
            'sender_firstname' => 'Creator First Name',
            'sender_lastname' => 'Creator Last Name',
            'sender_email' => 'Creator Email',
            'sender_externalid' =>'Creator Employee Id',
            'enc_groupid' => 'Group ID',
            'groupname' => 'ERG Name',
            'total_recipients' => 'Total Recipients',
            'sent_to' => 'Mail sent to',
            'additional_recipients' => 'Additional Recipients',
            'subject' => 'Subject',
            'publishdate' => 'Mail Sent On',
            'message_status' => 'Status',
        ),
        'Options' => array(),
        'Filters' => array(
            'groupids' => array(),
            'is_admin' => false,
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_DIRECT_MAIL;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }

        $group_filter = '';
        $is_global_filter = '';
        $group_filter_conditions = [];
        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
            $groupids = Sanitizer::SanitizeIntegerArray($meta['Filters']['groupids']);

            // If 0 (Global groupid is not set), then exclude admin messages.

                // Include messages where groupid is not set.
            if(in_array('0', $groupids)) {
                $group_filter_conditions[] = "messages.groupids = ''";
            }

            if(!$meta['Filters']['is_admin']) {
                $is_global_filter = " AND messages.is_admin = 0";
            }


            // Construct a group filter to find matches with all non 0 groups.
            // Note: $group_filter_conditions might already by set above which is valid.
            foreach ($groupids as $groupid) {
                if($groupid){
                    $group_filter_conditions[] = "FIND_IN_SET('{$groupid}', messages.groupids)";
                }
            }
            if (!empty($group_filter_conditions)) {
                $group_filter = ' AND (' . implode(' OR ', $group_filter_conditions) . ')';
            }

        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        $select = "SELECT messages.messageid, messages.groupids, messages.chapterids, messages.channelids, messages.regionids, messages.team_roleids, messages.from_name, messages.sent_to, messages.total_recipients, messages.additional_recipients, messages.subject, messages.is_admin, messages.recipients_base, messages.listids, messages.content_replyto_email, messages.createdon, messages.modifiedon, messages.publishdate, messages.publishedby, messages.isactive as message_status, users.firstname as sender_firstname, users.lastname as sender_lastname,users.email as sender_email,users.externalid as sender_externalid
        FROM messages 
            LEFT JOIN users USING (userid)
        WHERE messages.companyid={$this->cid()} AND messages.zoneid={$_ZONE->id()}
        AND (messages.isactive > 0
            {$group_filter}
            {$is_global_filter}
            )
        ORDER BY publishdate ASC {$this->policy_limit}";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        while (@$rows = mysqli_fetch_assoc($result)) {
            $row = array();
            $rows['enc_messageid'] = $_COMPANY->encodeIdForReport($rows['messageid']);
            $recipientTypesMap = [
                    0 => "Other",
                    1 => sprintf(gettext("%s Leaders"),$_COMPANY->getAppCustomization()['group']['name-short']),
                    3 => sprintf(gettext("%s Leaders"),$_COMPANY->getAppCustomization()['chapter']["name-short"]),
                    4 => sprintf(gettext("%s Leaders"),$_COMPANY->getAppCustomization()['channel']["name-short"]),
                    2 => sprintf(gettext("%s Members"),$_COMPANY->getAppCustomization()['group']['name-short']),
                    5 => sprintf(gettext('%1$s Members'), $_COMPANY->getAppCustomization()['teams']['name']),
            ];
            $rows['firstname'] = $rows['sender_firstname'] ?? 'Deleted User';
            $rows['lastname'] = $rows['sender_lastname'] ?? 'Deleted User';
            $rows['sender_email'] = $rows['sender_email'] ?? '-';
            $rows['sender_externalid'] = $rows['sender_externalid'] ? User::ExtractExternalId($rows['sender_externalid']) : '';
            $rows['groupname'] = $this->getGroupNamesAsCSV($rows['groupids']);
            $rows['enc_groupid'] = $_COMPANY->encodeIdsInCSVForReport($rows['groupids']);
            if($rows['listids']){
                $rows['dynamic_list'] = $this->getDynamicListNamesCSV($rows['listids']);
            }
            $rows['sent_to'] = implode(",",array_values(array_intersect_key($recipientTypesMap, array_flip(explode(',',$rows['sent_to'])))));
            $rows['additional_recipients'] = $rows['additional_recipients'] ?? ' - '; 
            $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
            $rows['publishdate'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['publishdate'], $reportTimezone);
            $rows['message_status'] = self::CONTENT_STATUS_MAP[$rows['message_status']];
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

        // Ignore custom report values for groupname, chaptername and set them to what is defined in the zone
        $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']["name-short"];
        $reportmeta['Fields']['enc_groupid'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' ID';

        return $reportmeta;
    }
}