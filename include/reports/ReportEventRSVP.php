<?php

class ReportEventRSVP extends Report
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
            'start' => 'Start Date',
            'end' => 'End Date',
            'event_attendence_type' => 'Event Venue Type',
            'eventvanue' => 'Event Venue',
            'vanueaddress' => 'Event Venue address',
            'hours_report' => 'Event Duration Hours',
            'volunteered_as' => "Volunteered As",
            'total_volunteering_hours' => "Volunteering Hours",
            'timezone' => 'Event timezone',
            'firstname' => 'First Name',
            'lastname' => 'Last Name',  
            'email' => 'Email',
            'jobtitle' => 'Job Title',
            'department' => 'Department',
            'branchname' => 'Office Location',
            'city' => 'City',
            'state' => 'State',
            'country' => 'Country',
            'opco' => 'Company',
            'joindate' => 'RSVP On',
            'joinstatus' => 'RSVP Status',
            'joinmethod' => 'RSVP Method',
            //'checkin_process' => 'Check In Process', /*skipping as it did not make sense */
            'checkedin_date' => 'Check In On',
            'checkedin_by' => 'Check In By',
            'is_member' => 'Is Member',
            'eventstatus' => 'Event status',
            'eventtype' => 'Event Type',
            'partner_organizations' => 'Partner Organizations',
            'partner_organizations_taxid' => 'Partner Organizations Tax ID',
            'is_event_reconciled' => 'Is Event Reconciled'
        ),
        'AdminFields' => array(
            'externalid' => 'Employee Id',
        ),
        'Options' => array(
            'startDate' => null,
            'endDate' => null,
        ),
        'Filters' => array(
            'groupids' => '',
            'eventid' => '',
            'event_series_id' => '',
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
    }

    protected static function GetReportType() : int { return self::REPORT_TYPE_EVENT_RSVP;}
    protected static function GetReportMeta() : array { return self::META;}

    protected function _generateReport($file_h, string $delimiter = ",", string $enclosure = '"', string $escape_char = "\\"): void
    {
        global $_COMPANY, $_ZONE, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->getMetaArray();

        if (!empty($meta['AdminFields'])) {
            $meta['Fields'] = array_merge($meta['Fields'], $meta['AdminFields']);
        }

        if (!empty($meta['Filters']['event_series_id'])) {
            $eventid_filter = " AND events.event_series_id=" . (int)$meta['Filters']['event_series_id'];
        } elseif (!empty($meta['Filters']['eventid'])) {
            $eventid_filter = " AND events.eventid=" . (int)$meta['Filters']['eventid'];
        } else {
            #4213 - If the report is not for a specific event, then enforce zone id check
            $eventid_filter = " AND events.zoneid={$_ZONE->id()}";
        }

        $groupid_filter = '';
        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
            $groupid_list = implode(',', $meta['Filters']['groupids']);

            // Note: If groupids are given then we will include events that have collborating groupids in our sql.
            // This is step_1:collaborating_group_filter
            // Since this mechanism includes all events that have collaboration set, we will filter out the events
            // that do not match with selected groups when processing the data (see step_2:collaborating_group_filter)
            $groupid_filter = " AND (events.groupid IN ({$groupid_list}) OR (events.groupid=0 AND events.collaborating_groupids != ''))";
        }

        $startDateCondtion = "";
        if (!empty($meta['Options']['startDate'])) {
            $meta['Options']['startDate'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['startDate']) ?: '2200-12-31 00:00:00';
            $startDateCondtion = " AND events.`start` >= '{$meta['Options']['startDate']}' ";
        }

        $endDateCondtion = "";
        if (!empty($meta['Options']['endDate'])) {
            $meta['Options']['endDate'] = Sanitizer::SanitizeUTCDatetime($meta['Options']['endDate']) ?: '1900-12-31 00:00:00';
            $endDateCondtion = " AND events.`start` <= '{$meta['Options']['endDate']}' ";
        }

        $partners_organizations = array();
        if (!empty($meta['Fields']['partner_organizations'])) {
            $partners_organizations = $this->mapPartnerOrganizations($groupid_filter, $startDateCondtion, $endDateCondtion);
        }

        // Step 1 - Write Header Row
        fputcsv($file_h, array_values($meta['Fields']), $delimiter, $enclosure, $escape_char); // Write the values of the fields array as header row.

        #4213 - Zone check removed because My events section can be accessed by other zone - see #4213 above
        $select = "SELECT events.eventid, events.eventtitle, events.start, events.end, events.eventtype, events.listids, events.timezone, events.event_attendence_type, events.eventvanue, events.vanueaddress, eventjoiners.*, 
            events.groupid, events.teamid, events.chapterid, events.channelid, events.collaborating_groupids,events.isactive as eventstatus, events.is_event_reconciled,events.attributes,events.custom_fields, firstname,lastname,email,external_email,externalid,jobtitle,extendedprofile,employeetype,opco,
            homeoffice, department
            FROM eventjoiners
                JOIN events using (eventid)
                LEFT JOIN users ON eventjoiners.userid=users.userid
            WHERE 
                events.companyid={$this->cid()} 
                {$groupid_filter}
              	{$startDateCondtion}
		        {$endDateCondtion}
		        {$eventid_filter} 
                AND events.rsvp_enabled = 1
                AND events.eventid != events.event_series_id
                AND (isnull(users.companyid) OR users.companyid={$this->cid()}) 
                {$this->policy_limit}";

        $result = mysqli_query($dbc, $select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

        $chapterFilter = array();
        if (!empty($meta['Filters']['chapterids'])) {
            $chapterFilter = $meta['Filters']['chapterids'];
        }

        $channelFilter = array();
        if (!empty($meta['Filters']['channelids'])) {
            $channelFilter = $meta['Filters']['channelids'];
        }

        $reportTimezone = $_SESSION['timezone'] ?? 'UTC';

        while (@$rows = mysqli_fetch_assoc($result)) {

            $eventJoinMap = ['0'=>'Unknown', '1'=>'From Email', '2'=> 'From Web'];

            if ($rows['external_email']) {
                $rows['email'] = User::PickEmailForDisplay($rows['email'], $rows['external_email'], false);
            }

            $rows['enc_eventid'] = $_COMPANY->encodeIdForReport($rows['eventid']);

            if (empty($rows['joindate']) && empty($rows['checkedin_date'])) {
                // Skip the rows which have no join date or check in date. These rows were created to save who was
                // invited explicitly or in some cases to save users event survey data without have RSVP or checkin info
                continue;
            }

            // If group filter is given then, skip events for collaborating groupids that do not have a match
            // with provided group ids. This is step_2:collaborating_group_filter
            if (!empty($rows['collaborating_groupids'])) {
                if (empty($meta['Filters']['groupids']) || array_intersect($meta['Filters']['groupids'], explode(',', $rows['collaborating_groupids']))) {
                    $rows['groupname'] = $this->getGroupNamesAsCSV($rows['collaborating_groupids']);
                    $rows['enc_groupid'] = $_COMPANY->encodeIdsInCSVForReport($rows['collaborating_groupids']);
                } else {
                    continue;
                }
            } else {
                $rows['groupname'] = $this->getGroupName($rows['groupid']);
                $rows['enc_groupid'] = $_COMPANY->encodeIdForReport($rows['groupid']);
            }

            //do something with $rows;
            //if (empty($rows['joinstatus'])) {
                // This row is for users who were invited but never RSVP'ed; skip it
                //continue;
            //}
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
            if($rows['listids']){
                $rows['dynamic_list'] = $this->getDynamicListNamesCSV($rows['listids']);
            }

            if (isset($meta['Fields']['team_name']) && $rows['teamid']) {
                $rows['team_name'] = $this->getTeamName($rows['teamid']);
            }

            $event_tz = (!empty($rows['timezone'])) ? $rows['timezone'] : 'UTC';
            $event_start_date = new DateTime($rows['start'], new DateTimeZone($event_tz));
            $event_end_date = new DateTime($rows['end'], new DateTimeZone($event_tz));
            $time_difference = $event_end_date->getTimestamp() - $event_start_date->getTimestamp();
            $total_hours = round($time_difference / 3600, 2);
            $rows['hours_report'] = $total_hours;

            // Update start, end times now.
            $rows['start'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['start'], $event_tz);
            $rows['end'] = $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['end'], $event_tz);

            // total volunteers and volunteering hours
            if($_COMPANY->getAppCustomization()['event']['volunteers']) {
                $rows['total_volunteering_hours'] = '';
                if (isset($meta['Fields']['total_volunteering_hours'])) {
                    // Prepare the volunteers data
                    if (!empty($rows['attributes'])) {
                        $volunteerData = array();
                        $attributes = json_decode($rows['attributes'], true);
                        if (isset($attributes['event_volunteer_requests'])) {
                            foreach ($attributes['event_volunteer_requests'] as $request) {
                                $volunteerData[] = array(
                                    'volunteertypeid' => $request['volunteertypeid'],
                                    'volunteer_hours' => $request['volunteer_hours']
                                );
                            }

    
                            // Volunteer Open positions logic
                            if (!empty($volunteerData)) {
                                // Optimization: DB Check is expensive, do a DB check for event_volunteers only if the volunteers are set for the event.
                                $signed_volunteers = self::DBROGet("SELECT volunteertypeid FROM event_volunteers WHERE eventid={$rows['eventid']} AND userid={$rows['userid']}");
                                $vounteer_hours = 0;
                                $volunteered_as = [];
                                // Event though a user can sign up for only one volunteer role for one event, we are
                                // still running the following logic in a loop for future evolution.
                                foreach ($signed_volunteers as $signed_volunteer) {
                                    $volunteertypeid = $signed_volunteer['volunteertypeid'];
                                    $eventVolunteerType = $this->getEventVolunteerType($volunteertypeid);
                                    if (empty($eventVolunteerType)) {
                                        continue;
                                    }
                                    $volunteerRow = Arr::SearchColumnReturnRow($volunteerData, $volunteertypeid, 'volunteertypeid');
                                    if ($volunteerRow) {
                                        $vounteer_hours += ($volunteerRow['volunteer_hours'] ?? $total_hours);
                                        $volunteered_as[] = $eventVolunteerType;
                                    }
                                }

                                $rows['total_volunteering_hours'] = $vounteer_hours;
                                $rows['volunteered_as'] = implode(",\n", $volunteered_as);
                            }
                        }
                    }
                }
                
            }

            if (!empty($rows['email'])) {
                $rows = array_merge(
                    $rows,
                    $this->getDepartmentValues($rows['department']),
                    $this->getBranchAndRegionValues($rows['homeoffice'])
                );
                $rows['joindate'] = empty($rows['joindate']) ? '' : $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['joindate'], $reportTimezone);
                $rows['joinstatus'] = Event::GetRSVPLabel($rows['joinstatus']);
                //$rows['checkin_process'] = empty($rows['checkedin_date']) ? '' : 'System';
                // Check if the person is Member
                if (isset($meta['Fields']['is_member'])) {
                    // Set the group ids in the event
                    $combineIds = $rows['groupid'] . "," . $rows['collaborating_groupids'];
                    $groupids = array_filter(array_unique(explode(",", $combineIds)));
 
                    // Get the RSVP user's groups if any
                    $userGroupIds = explode(',', $this->getUserGroupIdsAsCSV($rows['userid']));
                    // check the arrays and set the row value is_member

                    // Note: For not a member do not show *No*, leave it empty to match the rows where userid is not
                    // avaialble. This is requriement for data protection of anonymous users.
                    $rows['is_member'] = (empty(array_intersect($userGroupIds, $groupids))) ? '' : 'Yes';
                }

                // Extended profile
                $rows['externalid'] = explode(':', $rows['externalid'] ?? '')[0];
                if (!empty($rows['extendedprofile'])) {
                    $profile = User::DecryptProfile($rows['extendedprofile']);
                    foreach ($profile as $pk => $value) {
                        $rows['extendedprofile.' . $pk] = $value;
                    }
                }
            } else {
                $attendee_data = json_decode($rows['other_data'], TRUE);
                if (empty($attendee_data)) {
                    continue;
                }
                $rows['firstname'] = $attendee_data['firstname'];
                $rows['lastname'] = $attendee_data['lastname'];
                $rows['email'] = $attendee_data['email'];
                $rows['jobtitle'] = '';
                $rows['department'] = '';
                $rows['branchname'] = '';
                $rows['city'] = '';
                $rows['state'] = '';
                $rows['country'] = '';
                $rows['zipcode'] = '';
                $rows['opco'] = '';
                $rows['joindate'] = '';
                $rows['joinstatus'] = '';
                //$rows['checkin_process'] = empty($rows['checkedin_date']) ? '' : 'Manual';
            }
            $rows = $this->addCustomFieldsToRow($rows, $meta, 'eventid');
            $rows['joinmethod'] = $eventJoinMap[$rows['joinmethod']];
            $rows['checkedin_by'] = Event::EVENT_CHECKIN_METHOD_TO_ENGLISH[$rows['checkedin_by']] ?? $rows['checkedin_by'];
            $rows['checkedin_date'] = empty($rows['checkedin_date']) ? '' : $db->covertUTCtoLocalAdvance("Y-m-d H:i A", ' T', $rows['checkedin_date'], $reportTimezone);
            $rows['eventstatus'] = self::CONTENT_STATUS_MAP[$rows['eventstatus']];
            $rows['event_attendence_type'] = self::EVENT_VENUE_STATUS_MAP[$rows['event_attendence_type']]??'';
            $rows['eventtype'] = $this->getEventType($rows['eventtype']);
            $rows['is_event_reconciled'] = $rows['is_event_reconciled'] ? 'Yes' : 'No';

            $rows['partner_organizations'] = '';
            $rows['partner_organizations_taxid'] = '';
            if (!empty($meta['Fields']['partner_organizations']) && !empty($partners_organizations[$rows['eventid']])) {
                $rows['partner_organizations'] = implode(",\n", array_column($partners_organizations[$rows['eventid']], 'organization_name'));
            }
            if (!empty($meta['Fields']['partner_organizations_taxid']) && !empty($partners_organizations[$rows['eventid']])) {
                $rows['partner_organizations_taxid'] = implode(",\n", array_column($partners_organizations[$rows['eventid']], 'organization_taxid'));
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
        if (!$_COMPANY->getAppCustomization()['event']['reconciliation']['enabled']) {
            unset($reportmeta['Fields']['is_event_reconciled']);
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
                'firstname',
                'lastname',
                'email',
                //'jobtitle',
                'joindate',
                'checkedin_date',
                'checkedin_by',
                'is_member',
                'externalid',
                'eventvanue',
                'vanueaddress',
                'is_event_reconciled',
            ),
            'TimeField' => 'joindate'
        );
    }
}