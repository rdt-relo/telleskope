<?php

class ReportEventSpeaker extends Report
{
    public const META = array(

        'Fields' => array(
            'enc_eventid' => 'Event ID',
            'speaker_name' => 'Speaker Name',
            'speaker_title' => 'Speaker Title',
            'speaker_fee' => 'Speaker Fee',
            'eventtitle' => 'Event Name',
            'eventtype' => 'Event Type',
            'start' => 'Event Start Datetime',
            'end' => 'Event End Datetime',
            'total_rsvp' => 'Event No of RSVPs',
            'expected_attendees' => 'Event No of Attendees',
            'approved_by' => 'Approved By',
            'approved_on' => 'Speaker Approved On',
            'approval_status' => 'Speaker Approval Status',
            'eventstatus' => 'Event Publish Status'
        ),
        'AdminFields' => array(),
        'Options' => array(),
        'Filters' => array(
            'groupids' => '',
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_EVENT_SPEAKER;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();
        $EventSpeaker_rows = array();


        $groupid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
            $groupid_list = implode(',', $meta['Filters']['groupids']);

            // Note: If groupids are given then we will include events that have collborating groupids in our sql.
            // This is step_1:collaborating_group_filter
            // Since this mechanism includes all events that have collaboration set, we will filter out the events
            // that do not match with selected groups when processing the data (see step_2:collaborating_group_filter)
            $groupid_filter = " AND (e.groupid IN ({$groupid_list}) OR (e.groupid=0 AND e.collaborating_groupids != ''))";
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        $select = "SELECT s.speaker_name,s.speaker_title,st.speaker_fieldlabel as speaker_type,s.speaker_fee, s.expected_attendees, s.approved_by, s.approved_on, s.approval_status,
                    e.eventid, e.eventtitle, e.start, e.end, e.timezone, e.groupid, e.collaborating_groupids, e.isactive as eventstatus, e.eventtype,
                    (ec.num_rsvp_0+ec.num_rsvp_1+ec.num_rsvp_2+ec.num_rsvp_11+ec.num_rsvp_12+ec.num_rsvp_21+ec.num_rsvp_22)AS total_rsvp, 
                    a.firstname,a.lastname, s.custom_fields
        FROM `event_speakers` AS s 
        JOIN `events` AS e ON s.eventid=e.eventid AND s.companyid=e.companyid
        LEFT JOIN `event_counters` AS ec ON ec.eventid=e.eventid
        LEFT JOIN users as a ON s.approved_by=a.userid
        LEFT JOIN event_speaker_fields st ON st.speaker_fieldid=s.speaker_type
        WHERE s.companyid={$_COMPANY->id()} AND s.zoneid={$_ZONE->id()}
        {$groupid_filter}
        AND approval_status > 0 {$this->policy_limit}";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));
        $approvalStatus = [0 => 'Deleted', 1 => 'Requested', 2 => 'Processing', 3 => 'Approved', 4 => 'Denied'];

        while (@$rows = mysqli_fetch_assoc($result)) {

            $rows['enc_eventid'] = $_COMPANY->encodeIdForReport($rows['eventid']);

            // If group filter is given then, skip events for collaborating groupids that do not have a match
            // with provided group ids. This is step_2:collaborating_group_filter
            if (!empty($meta['Filters']['groupids']) && !empty($rows['collaborating_groupids'])) {
                if (array_intersect($meta['Filters']['groupids'], explode(',', $rows['collaborating_groupids']))) {
                    $rows['groupname'] = $this->getGroupNamesAsCSV($rows['collaborating_groupids']);
                } else {
                    continue;
                }
            } else {
                $rows['groupname'] = $this->getGroupName($rows['groupid']);
            }

            // Set fields of csv
            $rows['approved_by'] = $rows['firstname'] . ' ' . $rows['lastname'];
            $rows['approved_on'] = empty($rows['approved_on']) ? " - " : $rows['approved_on'] . ' UTC';
            $rows['approval_status'] = $approvalStatus[$rows['approval_status']];
            $event_tz = (!empty($rows['timezone'])) ? $rows['timezone'] : 'UTC';
            $rows['start'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['start'], $event_tz);
            $rows['end'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['end'], $event_tz);
            $rows['eventstatus'] = self::CONTENT_STATUS_MAP[$rows['eventstatus']];
            $rows['eventtype'] = $this->getEventType($rows['eventtype']);
            $rows = $this->addCustomFieldsToRow($rows, $meta);

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
        $reportmeta = parent::GetDefaultReportRecForDownload();
        
        if(!$_COMPANY->getAppCustomization()['event']['speakers']['approvals']){
            unset($reportmeta['Fields']['approved_by']);
            unset($reportmeta['Fields']['approved_on']);
            unset($reportmeta['Fields']['approval_status']);
        }
        return $reportmeta;
    }

}