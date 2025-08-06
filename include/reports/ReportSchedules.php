<?php 
Class ReportSchedules extends Report {
  public const META = array(
        'Fields' => array(
            'support_user_firstname' => 'Support User First Name',
            'support_user_lastname' => 'Support User Last Name',
            'support_user_email' => 'Support User Email',
            'support_user_externalid' => 'Support User Employee Id',
            'support_user_schedule_name' => 'Schedule Name',
            'support_user_schedule_start_time' => 'Schedule Start Time',
            'support_user_schedule_end_time' => 'Schedule End Time',
            'restricted_groups' => 'Restricted Groups',
            'schedule_slot' => 'Slot Duration',
            'number_of_slots' => 'Number of Slots',
            'number_of_booked_slots' => 'Number of Booked Slots',
            'number_of_available_slots' => 'Number of Available Slots',

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

    protected static function GetReportType() : int { return self::REPORT_TYPE_SCHEDULES;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $_USER, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }

        $start_date_condition = '';
        if ($meta['Options']['startDate'] && Sanitizer::SanitizeUTCDatetime($meta['Options']['startDate'])) {
            $start_date_condition = " AND `start_date_in_user_tz` >= '{$meta['Options']['startDate']}'";
        }

        $end_date_condition = '';
        if ($meta['Options']['endDate'] && Sanitizer::SanitizeUTCDatetime($meta['Options']['endDate'])) {
            $end_date_condition = " AND `end_date_in_user_tz` <= '{$meta['Options']['endDate']}'";
        }

        // Get all groups from current zone for filtering
        $zone_groups = Group::GetAllGroupsByCompanyid($_COMPANY->id(), $_ZONE->id(), true);
        $zone_group_ids = array();
        foreach ($zone_groups as $group) {
            $zone_group_ids[] = $group->id();
        }

        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char);
        $select = "SELECT * FROM user_schedules
                   WHERE `companyid` = {$_COMPANY->id()}
                     AND `isactive` = 1
                     {$start_date_condition}
                     {$end_date_condition}
                     ORDER BY `start_date_in_user_tz` DESC {$this->policy_limit}";
         $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

         while ($rows = mysqli_fetch_assoc($result)) {
            $filtered_groupids = $rows['groupids'];
             // Filter data based on current zone groups
             $include_record = false;
             
             // Always include global data (where groupids is empty or 0)
             if (empty($rows['groupids']) || $rows['groupids'] === '0') {
                 $include_record = true;
             } else {
                 // Check if any of the groupids belong to current zone
                 $record_group_ids = array_map('trim', explode(',', $rows['groupids']));
                 $record_group_ids = array_filter($record_group_ids); // Remove empty values
                 
                  // Get only group IDs that belong to current zone
                 $zone_group_ids_str = array_map('strval', $zone_group_ids);
                 $valid_group_ids = array_intersect($record_group_ids, $zone_group_ids_str);

                 // set the include record flag now.
                 if (!empty($valid_group_ids)) {
                     $include_record = true;
                     $filtered_groupids = implode(',', $valid_group_ids);
                 }
             }
             
             // Skip this record if it doesn't belong to current zone
             if (!$include_record) {
                 continue;
             }
             
             // support user details
             $support_user = User::GetUser($rows['userid']);
             $rows['support_user_firstname'] = $support_user->getFirstName();
             $rows['support_user_lastname'] = $support_user->getLastName();
                $rows['support_user_email'] = $support_user->getEmail();
                $rows['support_user_externalid'] = $support_user->getExternalId();
                $rows['support_user_schedule_name'] = $rows['schedule_name'];

                                 $rows['restricted_groups'] = '';
             // Set restricted_groups field with group names from current zone only
             if($rows['groupids'] != '0' && !empty($rows['groupids'])) {

                 // Use the existing method with filtered group IDs
                 $rows['restricted_groups'] = $this->getGroupNamesAsCSV($filtered_groupids);
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