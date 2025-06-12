<?php

class ReportEvents extends Report
{
    public const META = array(
        'Fields' => array(
            'enc_eventid' => 'Event ID',
            'enc_groupid' => 'Group ID',
            'groupname' => 'Group',
            'enc_chapterid' => 'Chapter ID',
            'chaptername' => 'Chapter',
            'enc_channelid' => 'Channel ID',
            'channelname' => 'Channel',
            'dynamic_list' => 'Dynamic List',
            'team_name' => 'Team',
            'eventtitle' => 'Event Name',
            'event_description' => 'Event Description',
            'start' => 'Start Date',
            'end' => 'End Date',
            'hours_report' => 'Event Duration Hours',
            'timezone' => 'Event Timezone',
            'publishedby'=>'Published By',
            'publishdate'=>'Publish Date',
            'eventtype' => 'Event Type',
            'rsvpcount' => '# of RSVPs',
            'attendeecount' => '# of Attendees',
            'event_attendence_type' => 'Event Venue Type',
            'eventvanue' => 'Event Venue',
            'vanueaddress' => 'Event Venue Address',
            'web_conference_link' => 'Web Conference Link',
            'web_conference_detail' => 'Web Conference Detail',
            'max_inperson' => 'In-Person limit',
            'max_online' => 'Online limit',
            'disclaimers_waivers' => 'Disclaimers / Waivers',
            'volunteers_requested' => 'Volunteers Requested',
            'volunteers_filled' => 'Volunteers Positions Filled',
            'volunteers_open' => 'Volunteers Positions Open',
            'volunteering_hours_configured' => "Volunteering Hours Configured",
            'total_volunteering_hours' => "Total Volunteering Hours",
            'isactive' => 'Event Publish Status',
            'is_event_reconciled' => 'Is Event Reconciled',
            'createdby' => 'Event Created By Name',
            'createdby_email' => 'Event Created By Email',
            'createdby_externalid' => 'Event Created By ExternalId',
            'region' => 'Region',
            'shareable_link' => 'Event Shareable Link',
            'partner_organizations' => 'Partner Organizations',
            'approval_status' => 'Approval Status',
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

    protected static function GetReportType() : int { return self::REPORT_TYPE_EVENT;}
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
            // Note: If groupids are given then we will include events that have collborating groupids in our sql.
            // This is step_1:collaborating_group_filter
            // Since this mechanism includes all events that have collaboration set, we will filter out the events
            // that do not match with selected groups when processing the data (see step_2:collaborating_group_filter)
            $groupid_filter = "AND (events.groupid IN ({$groupid_list}) OR (events.groupid=0 AND events.collaborating_groupids != ''))";
        }


        $startDateCondtion = "";
        if (!empty($meta['Options']['startDate'])) {
            $startDateCondtion = " AND events.`start` >= '{$meta['Options']['startDate']}' ";
        }

        $endDateCondtion = "";
        if (!empty($meta['Options']['endDate'])) {
            // Note:
            // Below we will check the start date to be less than the end date.
            // The date filter applies only to the event start dates
            $endDateCondtion = " AND events.`start` <= '{$meta['Options']['endDate']}' ";
        }

        $partners_organizations = array();
        if (!empty($meta['Fields']['partner_organizations'])) {
            $partners_organizations = $this->mapPartnerOrganizations($groupid_filter, $startDateCondtion, $endDateCondtion);
        }


        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
        $select = "SELECT events.*, p.approval_stage, p.approval_status
        FROM events LEFT JOIN topic_approvals p ON p.topicid = eventid AND  p.topictype='EVT' AND p.companyid={$this->cid()}
        WHERE events.companyid={$this->cid()} AND events.zoneid={$_ZONE->id()}
        AND (
        events.eventclass IN ('event', 'teamevent')
        {$groupid_filter}
        {$startDateCondtion}
        {$endDateCondtion}
        AND events.event_series_id !=events.eventid )
        ORDER BY events.start DESC {$this->policy_limit}";
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
            $rows['chaptername'] = $this->getChapterNamesAsCSV($rows['chapterid']);
            $rows['enc_chapterid'] = $_COMPANY->encodeIdsInCSVForReport($rows['chapterid']);
            $rows['channelname'] = $this->getChannelNamesAsCSV($rows['channelid']);
            $rows['enc_channelid'] = $_COMPANY->encodeIdsInCSVForReport($rows['channelid']);
            $rows['dynamic_list'] = '-';
            if($rows['listids']){
                $rows['dynamic_list'] = $this->getDynamicListNamesCSV($rows['listids']);
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

            if (isset($meta['Fields']['team_name']) && $rows['teamid']) {
                $rows['team_name'] = $this->getTeamName($rows['teamid']);
            }
            if (isset($meta['Fields']['disclaimers_waivers']) && !empty($rows['disclaimerids'])) {
                $rows['disclaimers_waivers'] = $this->getDisclaimersCSV($rows['disclaimerids']);
            }

            $event_tz = (!empty($rows['timezone'])) ? $rows['timezone'] : 'UTC';
            // calculate hours report
            $event_start_date = new DateTime($rows['start'], new DateTimeZone($event_tz));
            $event_end_date = new DateTime($rows['end'], new DateTimeZone($event_tz));
            $time_difference = $event_end_date->getTimestamp() - $event_start_date->getTimestamp();
            $total_hours = round($time_difference / 3600, 2);
            $rows['hours_report'] = $total_hours;

            if($_COMPANY->getAppCustomization()['event']['volunteers']){
                $rows['volunteers_filled'] = '';
                $rows['volunteers_open'] = '';
                $rows['volunteers_requested'] = '';
                $rows['total_volunteering_hours'] = '';
                $rows['volunteering_hours_configured'] = '';
                if (isset($meta['Fields']['volunteers_filled']) || isset($meta['Fields']['volunteers_open']) || isset($meta['Fields']['volunteers_requested']) || isset($meta['Fields']['total_volunteering_hours']) || isset($meta['Fields']['volunteering_hours_configured'])) {
                    // Prepare the volunteers data
                    if (!empty($rows['attributes'])) {
                        $volunteerData = array();
                        $attributes = json_decode($rows['attributes'], true);
                        if (isset($attributes['event_volunteer_requests'])) {
                            foreach ($attributes['event_volunteer_requests'] as $request) {
                                $volunteerData[] = array(
                                    'volunteertypeid' => $request['volunteertypeid'],
                                    'volunteer_needed_count' => $request['volunteer_needed_count'],
                                    'volunteer_hours' =>  $request['volunteer_hours'] ?? $total_hours,
                                );
                            }
    
                            $signed_volunteers = self::DBROGet("SELECT volunteertypeid,COUNT(1) as tot FROM event_volunteers WHERE eventid={$rows['eventid']} GROUP BY volunteertypeid");
    
                            // Volunteer Open positions logic
                            if (!empty($volunteerData)) {
                                $volunteersRequested = [];
                                $volunteersFilled = [];
                                $volunteersOpen = [];
                                $totalVolunteeringHours = 0;
                                $totalVolunteeringHoursConfigured = [];
    
                                foreach ($volunteerData as $volunteer) {
                                    $volunteertypeid = $volunteer['volunteertypeid'];
                                    $eventVolunteerType = $this->getEventVolunteerType($volunteertypeid);
                                    if (empty($eventVolunteerType)) {
                                        continue;
                                    }
                                    $volunteerNeededCount = intval($volunteer['volunteer_needed_count']);
                                    $volunteerRow = Arr::SearchColumnReturnRow($signed_volunteers, $volunteertypeid, 'volunteertypeid');
                                    $volunteerFilledCount = intval(!empty($volunteerRow) ? $volunteerRow['tot'] : 0);
                                    $volunteerOpenCount = max(0, $volunteerNeededCount - $volunteerFilledCount);
    
                                    $volunteersFilled[] = "[{$eventVolunteerType} = {$volunteerFilledCount}]";
                                    $volunteersOpen[] = "[{$eventVolunteerType} = {$volunteerOpenCount}]";
                                    $volunteersRequested[] = "[{$eventVolunteerType} = {$volunteerNeededCount}]";
                                    $totalVolunteeringHoursConfigured[] = "[{$eventVolunteerType} = {$volunteer['volunteer_hours']}]";

                                    // calculate hours by vilunteer signup
                                    $totalVolunteeringHours += round((($volunteer['volunteer_hours'] * 3600) * $volunteerFilledCount)/3600, 2);
                                }
    
                                $rows['volunteers_filled'] = implode("\n", $volunteersFilled);
                                $rows['volunteers_open'] = implode("\n", $volunteersOpen);
                                $rows['volunteers_requested'] = implode("\n", $volunteersRequested);
                                $rows['volunteering_hours_configured'] = implode("\n", $totalVolunteeringHoursConfigured);
                                $rows['total_volunteering_hours'] = $totalVolunteeringHours;
                            }
                        }
                    }
                }
                
            }

          
            //do something with $rows;
            $row = array();

            if (isset($meta['Fields']['rsvpcount']) || isset($meta['Fields']['attendeecount'])) {
                if ($rows['isactive'] == 1 || $rows['isactive'] == 0) { // counting for published and cancelled events
                    $temp_row = self::DBROGet("select (num_rsvp_1+num_rsvp_2+num_rsvp_11+num_rsvp_12+num_rsvp_21+num_rsvp_22) as rsvpcount,  num_checkedin as attendeecount from event_counters where eventid={$rows['eventid']}");
                    $rows['rsvpcount'] = $temp_row[0]['rsvpcount'] ?? 0;
                    $rows['attendeecount'] = $temp_row[0]['attendeecount'] ?? 0;
                }else {
                    $rows['rsvpcount'] = 0;
                    $rows['attendeecount'] = 0;
                }
            }

            $rows = $this->addCustomFieldsToRow($rows, $meta);

            // Shareable link
            $rows['shareable_link'] = 'NA';
            if($rows['isactive'] == '1'){
                $rows['shareable_link'] = $_COMPANY->getAppURL($_ZONE->val('app_type')).'eventview?id='.$_COMPANY->encodeId($rows['eventid']);
            }

            $created_by = User::GetUser($rows['userid']) ?? '';
            // Created by
            $rows['createdby'] = $created_by ? $created_by->getFullName() : "";
            $rows['createdby_email'] = $created_by ? $created_by->val('email') : "";
            $rows['createdby_externalid'] = $created_by ? $created_by->getExternalId() : "";
            // Get region by chapter
            $rows['region'] = 'Global';
            if(!empty($rows['chapterid'])){
                $rows['region'] = $this->getChapterCSVRegion($rows['chapterid']);
            }elseif (!empty($rows['collaborating_groupids'])) {
                $rows['region'] = $this->getGroupCSVRegion($rows['collaborating_groupids']);
            }elseif (!empty($rows['groupid'])) {
                $rows['region'] = $this->getGroupCSVRegion($rows['groupid']);
            }

            // calculate start and end date
            $rows['start'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['start'], $event_tz);
            $rows['end'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['end'], $event_tz);
            $rows['publishdate'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['publishdate'] ?? '', $event_tz);

            $publishedby = '-';
            if ($rows['publishedby']) {
                $publisher = User::GetUser($rows['publishedby']);
                $publishedby  = $publisher ? $publisher->getFullName() : '';
            }
            $rows['publishedby']  = $publishedby;
            
            $rows['isactive'] = self::CONTENT_STATUS_MAP[$rows['isactive']];
            $rows['event_attendence_type'] = self::EVENT_VENUE_STATUS_MAP[$rows['event_attendence_type']]??'';
            $rows['eventtype'] = $this->getEventType($rows['eventtype']);

            $rows['is_event_reconciled'] = $rows['is_event_reconciled'] ? 'Yes' : 'No';

            $rows['event_description'] = convertHTML2PlainText($rows['event_description']);

            $rows['partner_organizations'] = '';
            if (!empty($meta['Fields']['partner_organizations']) && !empty($partners_organizations[$rows['eventid']])) {
                $rows['partner_organizations'] = implode(",\n", array_column($partners_organizations[$rows['eventid']], 'organization_name'));
            }

            $approvalStatus = '';
            if (!empty($rows['approval_stage'])) {
                $approvalStatus = ucwords($rows['approval_status']);
                $approvalStatus .= in_array($rows['approval_status'], [Approval::TOPIC_APPROVAL_STATUS['PROCESSING'], Approval::TOPIC_APPROVAL_STATUS['REQUESTED']]) ? ( gettext(' Stage ') . $rows['approval_stage']) : '';
            }
            $rows['approval_status'] = $approvalStatus;

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
        $reportmeta['Fields']['team_name'] = $_COMPANY->getAppCustomization()['teams']['name'];

        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            unset($reportmeta['Fields']['chaptername']);
            unset($reportmeta['Fields']['enc_chapterid']);
        }
        if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
            unset($reportmeta['Fields']['channelname']);
            unset($reportmeta['Fields']['enc_channelid']);
        }

        if (!$_COMPANY->getAppCustomization()['teams']['enabled']) {
            unset($reportmeta['Fields']['team_name']);
        }

        if (!$_COMPANY->getAppCustomization()['event']['volunteers']) {
            unset($reportmeta['Fields']['volunteers_filled']);
            unset($reportmeta['Fields']['volunteers_open']);
            unset($reportmeta['Fields']['volunteers_requested']);
        }

        if (!$_COMPANY->getAppCustomization()['event']['partner_organizations']['enabled']) {
            unset($reportmeta['Fields']['partner_organizations']);
        }

        if (!$_COMPANY->getAppCustomization()['event']['approvals']['enabled']) {
            unset($reportmeta['Fields']['approval_status']);
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
                'eventtitle',
                'start',
                'end',
                'timezone',
                'publishdate',
                'eventvanue',
                'vanueaddress',
                'web_conference_link',
                'web_conference_detail',
                'max_inperson',
                'max_online',
                'isactive',
                'publishedby',
                'createdby',
                'createdby_email',
                'createdby_externalid',
                'disclaimers_waivers',
                'shareable_link'
            ),
            'TimeField' => 'start'
        );
    }
}