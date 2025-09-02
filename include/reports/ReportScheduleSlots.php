<?php

class ReportScheduleSlots extends Report {
    public const META = array(
        'Fields' => array(
            'groupname' => 'Group Name',
            'slot_date_time' => 'Slot Date Time',
            'scheduler_first_name' => 'Scheduler First Name',
            'scheduler_last_name' => 'Scheduler Last Name',
            'scheduler_email' => 'Scheduler Email',
            'support_user_firstname' => 'Support User First Name',
            'support_user_lastname' => 'Support User Last Name',
            'support_user_email' => 'Support User Email',
            'support_user_externalid' => 'Support User Employee Id',
            'support_user_schedule_name' => 'Schedule Name',
            'slot_status' => 'Slot Status',
            'web_conference_link' => 'Meeting Link',
            'addedon' => 'Meeting Added On',
            'cancel_date_time' => 'Cancellation Date Time',
            'cancel_reason' => 'Cancellation Reason'
        ),
        'Options' => array(
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
        ),
        'Filters' => array(
            'is_admin' => false,
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType(): int { return self::REPORT_TYPE_SCHEDULE_SLOTS; }
    protected static function GetReportMeta(): array { return self::META; }

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE;
        $meta = $this->getMetaArray();
        fputcsv($file_h, array_keys($meta['Fields']), $delimiter, $enclosure, $escape_char);

        // Robust date filtering
        $start_date_condition = '';
        $end_date_condition = '';
        if (!empty($meta['Options']['start_date']) && !empty($meta['Options']['end_date'])) {
            $start_date_condition = " AND `end_date_in_user_tz` <= '{$meta['Options']['start_date']}'";
            $end_date_condition = " AND `start_date_in_user_tz` >= '{$meta['Options']['end_date']}'";
        } elseif (!empty($meta['Options']['start_date'])) {
            $start_date_condition = " AND `end_date_in_user_tz` >= '{$meta['Options']['start_date']}'";
        } elseif (!empty($meta['Options']['end_date'])) {
            $end_date_condition = " AND `start_date_in_user_tz` <= '{$meta['Options']['end_date']}'";
        }

        // Group filtering
        $groupid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
            $groupid_list = implode(',', array_map('intval', $meta['Filters']['groupids']));
            $groupid_filter = " AND (groupids='0' OR FIND_IN_SET(groupids, '{$groupid_list}')) ";
        }

        $dbc = GlobalGetDBROConnection();
        $select = "SELECT * FROM user_schedule WHERE companyid = {$_COMPANY->id()} AND isactive = 1 {$groupid_filter} {$start_date_condition} {$end_date_condition} ORDER BY start_date_in_user_tz DESC {$this->policy_limit}";
        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-12*"));

        while ($schedule = mysqli_fetch_assoc($result)) {
            $schedule_id = $schedule['schedule_id'];
            $userid = $schedule['userid'];
            $slots = UserSchedule::GetAvailableAndBookedScheduleSlots($schedule_id);
            list($allSlots, $upcomingSlots, $bookedSlots, $upcomingBookedSlots) = $slots;

            // Fetch booking events for this schedule using self::DBROGet
            $event_rows = self::DBROGet("SELECT * FROM events WHERE companyid = {$_COMPANY->id()} AND schedule_id = {$schedule_id} AND eventclass = 'booking' AND isactive = 1");
            $booking_events_by_start = array();
            if (is_array($event_rows)) {
                foreach ($event_rows as $event) {
                    $booking_events_by_start[$event['start']] = $event;
                }
            }

            $filtered_groupids = $schedule['groupids'];
            $include_record = false;
            if (empty($schedule['groupids']) || $schedule['groupids'] === '0') {
                $include_record = true;
            } else {
                $record_group_ids = array_map('trim', explode(',', $schedule['groupids']));
                $record_group_ids = array_filter($record_group_ids);
                // If you want zone filtering, add zone_group_ids logic here
                $include_record = true; // For now, include all filtered by SQL
                $filtered_groupids = implode(',', $record_group_ids);
            }
            if (!$include_record) continue;

            foreach ($allSlots as $slot) {
                $row = array();
                $support_user = User::GetUser($userid);
                $row['groupname'] = isset($schedule['groupids']) && $schedule['groupids'] != '0' && !empty($schedule['groupids']) ? $this->getGroupNamesAsCSV($filtered_groupids) : '';
                $row['slot_date_time'] = $slot['date'] . ' ' . $slot['start24'];
                $row['scheduler_first_name'] = '';
                $row['scheduler_last_name'] = '';
                $row['scheduler_email'] = '';
                $row['support_user_firstname'] = $support_user ? $support_user->getFirstName() : '';
                $row['support_user_lastname'] = $support_user ? $support_user->getLastName() : '';
                $row['support_user_email'] = $support_user ? $support_user->getEmail() : '';
                $row['support_user_externalid'] = $support_user ? $support_user->getExternalId() : '';
                $row['support_user_schedule_name'] = $schedule['schedule_name'] ?? '';
                $row['slot_status'] = 'Available';
                $row['web_conference_link'] = '';
                $row['addedon'] = '';
                $row['cancel_date_time'] = '';
                $row['cancel_reason'] = '';

                $now = strtotime('now');
                $slot_start = strtotime($row['slot_date_time']);
                if ($slot_start < $now) {
                    $row['slot_status'] = 'Expired';
                }

                // Check if slot is booked (O(1) lookup)
                if (isset($booking_events_by_start[$row['slot_date_time']])) {
                    $event = $booking_events_by_start[$row['slot_date_time']];
                    $row['slot_status'] = 'Booked';
                    $scheduler = User::GetUser($event['userid']);
                    $row['scheduler_first_name'] = $scheduler ? $scheduler->getFirstName() : '';
                    $row['scheduler_last_name'] = $scheduler ? $scheduler->getLastName() : '';
                    $row['scheduler_email'] = $scheduler ? $scheduler->getEmail() : '';
                    $row['web_conference_link'] = $event['web_conference_sp'] ?? '';
                    $row['addedon'] = $event['createdon'] ?? '';
                    // If cancelled, set cancel info
                    if (!empty($event['is_cancelled'])) {
                        $row['slot_status'] = 'Cancelled';
                        $row['cancel_date_time'] = $event['cancel_time'] ?? '';
                        $row['cancel_reason'] = $event['cancel_reason'] ?? '';
                    }
                }

                $output_row = array();
                foreach ($meta['Fields'] as $key => $label) {
                    $output_row[] = html_entity_decode($row[$key] ?? '', ENT_QUOTES | ENT_HTML401, 'ISO-8859-15');
                }
                fputcsv($file_h, $output_row, $delimiter, $enclosure, $escape_char);
            }
        }
    }

    public static function GetDefaultReportRecForDownload(): array
    {
        global $_COMPANY;
        global $_ZONE;
        $reportmeta = parent::GetDefaultReportRecForDownload();
        $reportmeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']['name-short'];
        $reportmeta['Fields']['enc_groupid'] = $_COMPANY->getAppCustomization()['group']['name-short'] . ' ID';
        $reportmeta['Fields']['chaptername'] = $_COMPANY->getAppCustomization()['chapter']['name-short'];
        $reportmeta['Fields']['enc_chapterid'] = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' ID';
        $reportmeta['Fields']['channelname'] = $_COMPANY->getAppCustomization()['channel']['name-short'];
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
