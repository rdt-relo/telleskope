<?php

Class ReportEventsInternal extends Report
{
        public const META = array(
            'Fields' => array(
                'groupname' => 'Group',
                'chaptername' => 'Chapter',
                'channelname' => 'Channel',
                'eventtitle' => 'Event Name',
                'start' => 'Start date',
                'end' => 'End date',
                'timezone' => 'Event timezone',
                'eventtype' => 'Event type',
                'rsvpcount' => '# of RSVPs',
                'attendeecount' => '# of Attendees',
                'eventvanue' => 'Event venue',
                'vanueaddress' => 'Venue address',
                'web_conference_link' => 'Web conference link',
                'web_conference_detail' => 'Web conference detail',
                'max_inperson' => 'In-Person limit',
                'max_online' => 'Online limit',
                'isactive' => 'Status',
                'recipientcount' => 'Number of Email Recipients',
                'openscount' => 'Total number of Email Opens',
                'uniqueopenscount' => 'Unique number of Email Opens',
//            'clickscount' => 'Total number of Clicks',
            ),
            'Options' => array(),
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

        protected static function GetReportType() : int { return self::REPORT_TYPE_EVENTS_INTERNAL;}
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
                $groupid_filter = " AND (events.groupid IN ({$groupid_list}) OR (events.groupid=0 AND events.collaborating_groupids != ''))";
            }
    
    
            $startDateCondtion = "";
            if (!empty($meta['Options']['startDate'])) {
                $startDateCondtion = " AND events.`start` >= '{$meta['Options']['startDate']} 00:00:00' ";
            }
    
            $endDateCondtion = "";
            if (!empty($meta['Options']['endDate'])) {
                // Note:
                // Below we will check the start date to be less than the end date.
                // The date filter applies only to the event start dates
                $endDateCondtion = " AND events.`start` <= '{$meta['Options']['endDate']} 23:59:59' ";
            }
    
            $custom_fields = array();
            if (!empty($meta['Options']['includeCustomFields'])) {
                $custom_fields = Event::GetEventCustomFields();
            }
    
            // Step 1 - Write Header Row
            fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.
            $select = "SELECT events.*, et.type as eventtype
            FROM events 
            LEFT JOIN event_type et on `events`.eventtype = et.typeid
            WHERE events.companyid='{$this->cid()}' AND events.zoneid={$_ZONE->id()}
            AND (events.`isactive`>0 
            {$groupid_filter}
            {$startDateCondtion}
            {$endDateCondtion})
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
                $rows['channelname'] = $this->getChannelNamesAsCSV($rows['channelid']);
    
                if (!empty($meta['Filters']['groupids']) && !empty($rows['collaborating_groupids'])) {
                    if (array_intersect($meta['Filters']['groupids'], explode(',', $rows['collaborating_groupids']))) {
                        $rows['groupname'] = $this->getGroupNamesAsCSV($rows['collaborating_groupids']);
                    } else {
                        continue;
                    }
                } else {
                    $rows['groupname'] = $this->getGroupName($rows['groupid']);
                }

                // get email log data
                $rows['recipientcount'] = 0;
                $rows['openscount'] = 0;
                $rows['uniqueopenscount'] = 0;
                $rows['clickscount'] = 0;
                if ($rows['isactive'] == 1) {
                    $domain = $_COMPANY->getAppDomain($_ZONE->val('app_type'));
                    $emailLogs = Emaillog::GetAllEmailLogsSummary($domain,EmailLog::EMAILLOG_SECTION_TYPES['event'],$rows['eventid']);
                    foreach($emailLogs as $log){
                        $rows['recipientcount'] += $log->val('total_rcpts') ?? 0;
                        $rows['openscount'] += $log->val('total_opens') ?? 0;
                        $rows['uniqueopenscount'] += $log->val('unique_opens') ?? 0;
                        $rows['clickscount'] += $log->val('total_clicks') ?? 0;
                    }
                }
    
                //do something with $rows;
                $row = array();
                $event_custom_fields = array();
    
                if (isset($meta['Fields']['rsvpcount']) || isset($meta['Fields']['attendeecount'])) {
                    if ($rows['isactive'] == 1) {
                        $temp_row = self::DBROGet("select (num_rsvp_1+num_rsvp_2+num_rsvp_11+num_rsvp_12+num_rsvp_21+num_rsvp_22) as rsvpcount,  num_checkedin as attendeecount from event_counters where eventid={$rows['eventid']}");
                        $rows['rsvpcount'] = $temp_row[0]['rsvpcount'] ?? 0;
                        $rows['attendeecount'] = $temp_row[0]['attendeecount'] ?? 0;
                    } else {
                        $rows['rsvpcount'] = 0;
                        $rows['attendeecount'] = 0;
                    }
                }
    
                if ($rows['custom_fields']) {
                    $event_custom_fields = json_decode($rows['custom_fields'], true);
                }
    
                foreach ($custom_fields as $custom_field) {
    
                    $current_values = array_filter( // Find matching field values if set
                        $event_custom_fields, function ($value) use ($custom_field) {
                        return ($value['custom_field_id'] == $custom_field['custom_field_id']);
                    });
                    if ($custom_field['custom_fields_type'] == 1) { // Single Value
                        $filedVals = (empty($current_values) ? '' : array_column($current_values, 'value')[0]);
                    } else if ($custom_field['custom_fields_type'] == 2) { //Multiple Values
                        $filedVals = empty($current_values) ? array() : array_column($current_values, 'value')[0];
                    } else if ($custom_field['custom_fields_type'] == 3) { // Text box
                        $filedVals = (empty($current_values) ? '' : array_column($current_values, 'value')[0]);
                    } else {
                        $filedVals = '';
                    }
                    if (is_array($filedVals)) {
                        $filedVals = (implode(', ', $filedVals));
                    }
                    $rows['custom' . $custom_field['custom_field_id']] = $filedVals;
                }
                $event_tz = (!empty($rows['timezone'])) ? $rows['timezone'] : 'UTC';
                $rows['start'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['start'], $event_tz);
                $rows['end'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['end'], $event_tz);
                $rows['isactive'] = self::CONTENT_STATUS_MAP[$rows['isactive']];
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
            $reportmeta['Fields']['chaptername'] = $_COMPANY->getAppCustomization()['chapter']["name-short"];
            $reportmeta['Fields']['channelname'] = $_COMPANY->getAppCustomization()['channel']["name-short"];

            if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
                unset($reportmeta['Fields']['chaptername']);
            }
            if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
                unset($reportmeta['Fields']['channelname']);
            }
    
            return $reportmeta;
        }
    }