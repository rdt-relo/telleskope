<?php

class ReportEventVolunteers extends Report
{
    public const META = array(

        'Fields' => array(
            'enc_eventid' => 'Event ID',
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'jobtitle' => 'Job Title',
            'email' => 'Email',
            'type' => 'Volunteer Type',
            'volunteering_hours' => "Volunteering Hours",
            'eventtitle' => 'Event Name',
            'start' => 'Event Start Datetime',
            'end' => 'Event End Datetime',
            'hours_report' => 'Event Duration Hours',
            'rsvpcount' => 'Event No of RSVPs',
            // 'expected_attendees' => 'Event No of Attendees',
            //'approved_by' => 'Volunteer Approved By',
            'enc_groupid' => 'Group ID',
            'groupname' => 'Group Name',
            'enc_chapterid' => 'Chapter ID',
            'chaptername' => 'Chapter Name',
            'enc_channelid' => 'Channel ID',
            'channelname' => 'Channel Name',
            'dynamic_list'=>'Dynamic List',
            'createdon' => 'Volunteer Approved On',
            'approval_status' => 'Volunteer Approval Status',
            'eventstatus' => 'Event Publish Status',
            'eventtype' => 'Event Type',
            'volunteer_added_by' => 'Added By',
            'external_volunteer_contact' => 'External Volunteer Contact',
        ),
        'AdminFields' => array(),
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

    protected static function GetReportType() : int { return self::REPORT_TYPE_EVENT_VOLUNTEERS;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();
        $EventVolunteer_rows = array();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }

        $groupid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
            $groupid_list = implode(',', $meta['Filters']['groupids']);

            // Note: If groupids are given then we will include events that have collborating groupids in our sql.
            // This is step_1:collaborating_group_filter
            // Since this mechanism includes all events that have collaboration set, we will filter out the events
            // that do not match with selected groups when processing the data (see step_2:collaborating_group_filter)
            $groupid_filter = " AND (e.groupid IN ({$groupid_list}) OR (e.groupid=0 AND e.collaborating_groupids != ''))";
        }

        $startDateCondtion = "";
        if (!empty($meta['Options']['startDate'])) {
            $meta['Options']['startDate'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['startDate']) ?: '2200-12-31 00:00:00';
            $startDateCondtion = " AND e.`start` >= '{$meta['Options']['startDate']}' ";
        }

        $endDateCondtion = "";
        if (!empty($meta['Options']['endDate'])) {
            $meta['Options']['endDate'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['endDate']) ?: '1900-12-31 00:00:00';
            $endDateCondtion = " AND e.`start` <= '{$meta['Options']['endDate']}' ";
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        $select = "SELECT u.firstname,u.lastname,u.jobtitle,u.email,vt.type, e.eventid,e.eventtype, e.eventtitle, e.groupid, e.listids, e.collaborating_groupids, e.chapterid, e.channelid, e.start, e.end, e.isactive as eventstatus, e.custom_fields, (ec.num_rsvp_0+ec.num_rsvp_1+ec.num_rsvp_2+ec.num_rsvp_11+ec.num_rsvp_12+ec.num_rsvp_21+ec.num_rsvp_22) AS rsvpcount, v.createdby, v.createdon, v.approval_status,v.volunteertypeid,v.volunteerid
        FROM `events` AS e 
        INNER JOIN `event_volunteers` AS v ON e.eventid = v.eventid
        INNER JOIN event_volunteer_type AS vt ON v.volunteertypeid = vt.volunteertypeid
        LEFT JOIN `event_counters` AS ec ON ec.eventid=e.eventid
        LEFT JOIN `users` AS u ON u.userid = v.userid
        WHERE e.companyid={$_COMPANY->id()} AND e.zoneid={$_ZONE->id()}
        AND v.isactive = 1
        {$groupid_filter}
        {$startDateCondtion}
        {$endDateCondtion} {$this->policy_limit}";

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

            $rows['enc_eventid'] = $_COMPANY->encodeIdForReport($rows['eventid']);

            $volunteer_obj = EventVolunteer::GetEventVolunteer($rows['volunteerid']);

            if (isset($meta['Fields']['volunteer_added_by'])) {
                if ($volunteer_obj->getCreatedByUser()) {
                    $rows['volunteer_added_by'] =
                        $volunteer_obj->getCreatedByUser()->val('firstname')
                        . ' '
                        . $volunteer_obj->getCreatedByUser()->val('lastname')
                        . ' ('
                        . $volunteer_obj->getCreatedByUser()->val('email')
                        . ')';
                } else {
                    $rows['volunteer_added_by'] = 'User Deleted';
                }

                $rows['external_volunteer_contact'] = '';
                if ($volunteer_obj->isExternalVolunteer()) {
                    $rows['firstname'] = $volunteer_obj->getFirstName();
                    $rows['lastname'] = $volunteer_obj->getLastName();
                    $rows['email'] = $volunteer_obj->getVolunteerEmail();

                    $rows['external_volunteer_contact'] = ' - ';
                    $careOfUser = $volunteer_obj->getCareofUser();
                    if($careOfUser){
                        $rows['external_volunteer_contact'] = $careOfUser->getFullName() . ' (' . $careOfUser->val('email') . ')';
                    } 
                }
            }

            // If group filter is given then, skip events for collaborating groupids that do not have a match
            // with provided group ids. This is step_2:collaborating_group_filter
            if (!empty($meta['Filters']['groupids']) && !empty($rows['collaborating_groupids'])) {
                if (array_intersect($meta['Filters']['groupids'], explode(',', $rows['collaborating_groupids']))) {
                    $rows['groupname'] = $this->getGroupNamesAsCSV($rows['collaborating_groupids']);
                    $rows['enc_groupid'] = $_COMPANY->encodeIdsInCSVForReport($rows['collaborating_groupids']);
                } else {
                    continue;
                }
            } else {
                $rows['groupname'] = $this->getGroupName($rows['groupid']);
                $rows['enc_groupid'] = $_COMPANY->encodeIdForReport($rows['groupid']);
            }

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
            $rows = $this->addCustomFieldsToRow($rows, $meta, 'eventid');

            $rows['chaptername'] = $this->getChapterNamesAsCSV($rows['chapterid']);
            $rows['enc_chapterid'] = $_COMPANY->encodeIdsInCSVForReport($rows['chapterid']);
            $rows['channelname'] = $this->getChannelNamesAsCSV($rows['channelid']);
            $rows['enc_channelid'] = $_COMPANY->encodeIdsInCSVForReport($rows['channelid']);
            if($rows['listids']){
                $rows['dynamic_list'] = $this->getDynamicListNamesCSV($rows['listids']);
            }
            //Approval status
            $rows['approval_status'] = self::APPROVAL_STATUS_MAP[$rows['approval_status']];
            // timeconvert
            $reportTimezone = $_SESSION['timezone'] ?? 'UTC';
            // calculate hours report
            $event_start_date = new DateTime($rows['start'], new DateTimeZone($reportTimezone));
            $event_end_date = new DateTime($rows['end'], new DateTimeZone($reportTimezone));
            $time_difference = $event_end_date->getTimestamp() - $event_start_date->getTimestamp(); 
            $total_hours = round($time_difference / 3600, 2);
            $rows['hours_report'] = $total_hours;
            //get volunteer hours assigned
            $event = Event::GetEvent($rows['eventid']);
            $volunteerRequest = $event->getEventVolunteerRequestByVolunteerTypeId($rows['volunteertypeid']);
            // calculate start and end date
            $rows['createdon'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['createdon'], $reportTimezone);
            $rows['start'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['start'], $reportTimezone);
            $rows['end'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['end'], $reportTimezone);
            $rows['eventstatus'] = self::CONTENT_STATUS_MAP[$rows['eventstatus']];
            $rows['eventtype'] = $this->getEventType($rows['eventtype']);
            $rows['volunteering_hours'] = $volunteerRequest['volunteer_hours'] ?? $total_hours;

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

        if (
            !$_COMPANY->getAppCustomization()['event']['volunteers']
            || !$_COMPANY->getAppCustomization()['event']['external_volunteers']
        ) {
            unset(
                $reportmeta['Fields']['volunteer_added_by'],
                $reportmeta['Fields']['external_volunteer_contact']
            );
        }

        return $reportmeta;
    }

    public static function GetMetadataForAnalytics () : array
    {
        return array (
            'ExludeFields' => array (
                'enc_eventid',
                'enc_groupid',
                'enc_chapterid',
                'enc_channelid',
                'start',
                'end',
                //'eventtitle',
                'firstname',
                'lastname',
                'eventtitle',
                'email',
                'createdon',
                'start',
                'end',
                'rsvpcount',
                'createdon',
                'volunteer_added_by',
                'external_volunteer_contact',
            ),
            'TimeField' => 'createdon'
        );
    }
}