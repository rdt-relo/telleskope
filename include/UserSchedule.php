<?php
// Do no use require_once as this class is included in Company.php.

class UserSchedule extends Teleskope
{
    public const WEEK_DAYS = array(
        'Monday'=> '1', 'Tuesday' => '2', 'Wednesday' => '3', 'Thursday' => '4', 'Friday' => '5', 'Saturday' => '6', 'Sunday'=> '0'
    );

    public const SCHEDULE_SCOPE = array (
        'team_event'=>'team_event',
        'group_support' => 'group_support'
    );

    protected function __construct(int $id,int $cid,array $fields) {
		parent::__construct($id,$cid,$fields);
	}


    public static function GetSchedule(int $id) {
		$obj = null;
        global $_COMPANY;
        
        $r = self::DBGet("SELECT * FROM `user_schedule` WHERE companyid = '{$_COMPANY->id()}' AND `schedule_id`='{$id}'");
        if (!empty($r)) {
            $obj = new UserSchedule($id, $_COMPANY->id(), $r[0]);
        }
		return $obj;
	}

    public function getScheduleDetails() {
        return self::DBGet("SELECT * FROM `user_schedule_details` WHERE `schedule_id`='{$this->id()}'");
    }

    public static function GetAllUsersSchedules(int $userid, bool $activeOnly = false, string $schedule_scope='', ?int $groupid=null) {
        global $_COMPANY, $_ZONE;
        $activeCondition = '';
        if ($activeOnly) {
            $activeCondition = " AND isactive='1'";
        }
        $scopeCondition = '';
        if ($schedule_scope && isset(self::SCHEDULE_SCOPE[$schedule_scope])) {
            $scopeCondition = " AND schedule_scope='{$schedule_scope}'";
        }
        $groupCondition = '';
        if ($groupid) {
            $groupCondition = " AND (groupids='0' OR FIND_IN_SET ({$groupid}, groupids)) ";
        }

        $schedules =  self::DBGet("SELECT * FROM `user_schedule` WHERE companyid = '{$_COMPANY->id()}' AND `userid`='{$userid}' {$activeCondition} {$scopeCondition} {$groupCondition}");
        foreach ($schedules as &$schedule) {
            $schedule['weekly_available_time'] = self::DBGet("SELECT * FROM `user_schedule_details` WHERE `schedule_id`='{$schedule['schedule_id']}'");
            $schedule['schedule_groups'] = Str::ConvertCSVToArray($schedule['groupids']);
        }
        unset($schedule);
        return $schedules;
    }

    public function isEventWithinSchedule(Event $event): bool
    {
        $user_tz = new DateTimeZone($this->val('user_tz'));

        $event_start = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $event->val('start'), new DateTimeZone('UTC'));
        $event_end = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $event->val('end'), new DateTimeZone('UTC'));

        $event_weekday = $event_start->setTimezone($user_tz)->format('w');

        $weekly_available_time = $this->val('weekly_available_time');

        $weekly_available_time = array_column($weekly_available_time, null, 'day_of_week');

        if (!isset($weekly_available_time[$event_weekday])) {
            return false;
        }

        $schedule_start =
            (new DateTimeImmutable($this->val('start_date_in_user_tz') . ' ' . $weekly_available_time[$event_weekday]['daily_start_time_in_user_tz'], $user_tz))
            ->setTimezone(new DateTimeZone('UTC'));

        $schedule_end =
            (new DateTimeImmutable($this->val('end_date_in_user_tz') . ' ' . $weekly_available_time[$event_weekday]['daily_end_time_in_user_tz'], $user_tz))
            ->setTimezone(new DateTimeZone('UTC'));

        if ($event_start < $schedule_start) {
            return false;
        }

        if ($event_end > $schedule_end) {
            return false;
        }

        $daily_slot_start_time = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $event_start->setTimezone($user_tz)->format('Y-m-d') . ' ' . $weekly_available_time[$event_weekday]['daily_start_time_in_user_tz'],
            $user_tz
        );

        $daily_slot_end_time = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $event_start->setTimezone($user_tz)->format('Y-m-d') . ' ' . $weekly_available_time[$event_weekday]['daily_end_time_in_user_tz'],
            $user_tz
        );

        if ($event_start < $daily_slot_start_time) {
            return false;
        }

        if ($event_end > $daily_slot_end_time) {
            return false;
        }

        return true;
    }

    public static function GetScheduleByDate(int $userid, string  $date) {
		$obj = null;
        global $_COMPANY;
        
        $r = self::DBGet("SELECT * FROM `user_schedule` WHERE companyid = '{$_COMPANY->id()}' AND `userid`='{$userid}' AND '{$date}' BETWEEN `start_date_in_user_tz` AND `end_date_in_user_tz`");
        if (!empty($r)) {
            $obj = new UserSchedule($r[0]['schedule_id'], $_COMPANY->id(), $r[0]);
        }
		return $obj;
	}

    public static function SaveSchedule(string $schedule_name, int $schedule_slot, string $start_date_in_user_tz, string $end_date_in_user_tz, string $timezone, string $schedule_description, string $link_generation_method, int $start_time_buffer, string $schedule_scope) {
        global $_COMPANY, $_USER;
       return self::DBInsertPS("INSERT INTO `user_schedule`(`companyid`, `userid`,`schedule_name`, `schedule_slot`, `start_date_in_user_tz`, `end_date_in_user_tz`,`user_tz`,`schedule_description`, `createdon`, `modifiedon`,`link_generation_method`, `start_time_buffer`, `schedule_scope`) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW(),?,?,?)",'iixxxxxxxix',$_COMPANY->id(),$_USER->id(),$schedule_name, $schedule_slot,$start_date_in_user_tz, $end_date_in_user_tz, $timezone, $schedule_description, $link_generation_method,$start_time_buffer,$schedule_scope);
    }

    public function updateSchedule(string $schedule_name, int $schedule_slot, string $start_date_in_user_tz, string $end_date_in_user_tz, string $timezone, string $schedule_description, string $link_generation_method, int $start_time_buffer, string $schedule_scope) {
        global $_COMPANY, $_ZONE, $_USER;
        return self::DBUpdatePS("UPDATE`user_schedule` SET `schedule_name`=?, `schedule_slot`=?, `start_date_in_user_tz`=?, `end_date_in_user_tz`=?, `user_tz`=?,`schedule_description`=?, `link_generation_method`=?, `start_time_buffer`=?, `schedule_scope`=?, `modifiedon`=NOW() WHERE `companyid`=? AND `userid`=? AND schedule_id=?",'xxxxxxxixiii',$schedule_name,$schedule_slot,$start_date_in_user_tz, $end_date_in_user_tz, $timezone, $schedule_description, $link_generation_method, $start_time_buffer, $schedule_scope, $_COMPANY->id(), $_USER->id(),$this->id()) ;
    }


    public function deleteSchedule() 
    {
        if (self::DBMutate("DELETE FROM user_schedule WHERE schedule_id='{$this->id()}' ") ) {
            $this->cleanScheduleDetails();
        }
        return true;
    }

    public function saveScheduleDetail(int $day_of_week, string $daily_start_time_in_user_tz, string $daily_end_time_in_user_tz) {
        return self::DBInsertPS("INSERT INTO `user_schedule_details` (`schedule_id`,`day_of_week`, `daily_start_time_in_user_tz`, `daily_end_time_in_user_tz`) VALUES (?,?,?,?)",'iixx',$this->id(), $day_of_week, $daily_start_time_in_user_tz, $daily_end_time_in_user_tz) ;
    }

    public function cleanScheduleDetails () {
        return self::DBMutate("DELETE FROM user_schedule_details WHERE schedule_id='{$this->id()}' ");
    }
    
    
    public static function GenerateCrossTimezoneTimeSlots (array $sourceDateTimeRanage, string $targetDate, int $slotDuration, string $sourceTimeZone, string $targetTimeZone)
    {
        // Define timezones
        $sourceTimeZone = new DateTimeZone($sourceTimeZone);
        $targetTimeZone = new DateTimeZone($targetTimeZone);
        
        // Define meeting duration
        $meetingDuration = new DateInterval('PT'.$slotDuration.'M');

        // source available slots for May 2nd
        $sourceTimeSlots = [];

        foreach ($sourceDateTimeRanage as $startEndRange) {
            [$start, $end] = $startEndRange;

            $startDate = new DateTime($start, $sourceTimeZone);
            $endDate = new DateTime($end, $sourceTimeZone);
            $interval = $startDate->diff($endDate);
            // Get the difference in minutes
            $minutesDifference = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
            while ($startDate < $endDate && $minutesDifference >= $slotDuration) {
                $sourceTimeSlots[] = clone $startDate;
                $startDate->add($meetingDuration);

                // Recheck if available next slot
                $interval = $startDate->diff($endDate);
                // Get the difference in minutes
                $minutesDifference = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
                if ($minutesDifference < $slotDuration) {
                    break;
                }
            }
        }
        // Convert source slots to target timezone
        $targetTimeSlots = [];
        foreach ($sourceTimeSlots as $sourceSlot) {
            $sourceSlot->setTimezone($targetTimeZone);
            $targetTimeSlots[] = $sourceSlot;
        }

        // Check for overlapping slots (considering date change due to time zone difference)
        $availableSlots = [];
        foreach ($targetTimeSlots as $targetSlot) {
            if ($targetSlot->format('Y-m-d') == $targetDate) {
                $availableSlots[] = $targetSlot;
            }
        }

        $timeSlots = array();
        if (!empty($availableSlots)) {
            foreach ($availableSlots as $slot) {
                $timeSlots[] = [
                    'date' => $slot->format('Y-m-d'),
                    'start24' => $slot->format('H:i'),
                    'start12' => $slot->format('h:i A'),
                    'duration' => array('hour'=>0, 'minutes' => $slotDuration),
                ];
            }
        }

        // Order all slots to asc order 
        usort($timeSlots, function ($a, $b) {
            if ($a['date'] == $b['date']) {
                return strcmp($a['start24'], $b['start24']);
            }
            return strcmp($a['date'], $b['date']);
        });

        return $timeSlots;
    }

    public static function GetUsersSchedulesDates(int $userid, string $excludeSchedules = '0') {
        global $_COMPANY, $_ZONE;
        return self::DBGet("SELECT `start_date_in_user_tz` as `start`, `end_date_in_user_tz` as `end` FROM `user_schedule` WHERE companyid = '{$_COMPANY->id()}' AND `userid`='{$userid}' AND `schedule_id` NOT IN({$excludeSchedules})");
    }

    public function deleteAllMeetingLinks()
    {
        if (self::DBMutate("DELETE FROM user_schedule_links WHERE schedule_id='{$this->id()}' ") ) {
            return true;
        }
        return false;
    }

    public function saveSchedulelMeetingLinks(array $meetingLinks) 
    {
        global $_COMPANY, $_USER;
        if ($this->deleteAllMeetingLinks()){
            foreach($meetingLinks as $meetingLink){
                self::DBInsertPS("INSERT INTO `user_schedule_links`(`schedule_id`, `companyid`, `userid`, `meetinglink`) VALUES (?,?,?,?)",'iiix',$this->id(),$_COMPANY->id(),$_USER->id(),$meetingLink);
            }
        }
        return true;
    }

    public function getUserSchedulesMeetingLinks()
    {   
        global $_COMPANY;
        return self::DBROGet("SELECT * FROM `user_schedule_links` WHERE `companyid`={$_COMPANY->id()} AND `schedule_id`={$this->id()}");
    }

    public function activate()
    {
        global $_COMPANY;
        $meetingLinks = $this->getUserSchedulesMeetingLinks();
        if (empty($meetingLinks)) {
            return -1;
        }
        $status_active = self::STATUS_ACTIVE;
        return self::DBMutate("UPDATE `user_schedule` SET `isactive`={$status_active},`modifiedon`=NOW() WHERE `companyid`={$_COMPANY->id()} AND `schedule_id`={$this->id()}");
    }


    public function deactivate()
    {
        global $_COMPANY;
        $status_active = self::STATUS_INACTIVE;
        return self::DBMutate("UPDATE `user_schedule` SET `isactive`={$status_active},`modifiedon`=NOW() WHERE `companyid`={$_COMPANY->id()} AND `schedule_id`={$this->id()}");
    }


    public function getLeastUsedMeetingLink(?string $startDateInUTC = null)
    {
        global $_COMPANY;


        $rows = self::DBROGet("SELECT * FROM `user_schedule_links` WHERE `companyid`={$_COMPANY->id()} AND `schedule_id`={$this->id()}");

        // If no links are found, return null
        if (empty($rows)) {
            return null;
        }

        // If only one link is available, return it. No further processing is needed.
        if (count($rows) == 1) {
            return $rows[0];
        }

        // If more than one links are available, lets find the best one.

        //
        // Step 1: Sort ascending based on usage_count;
        //
        usort($rows,function($a,$b) {
            return $a['usage_count'] - $b['usage_count'];
        });

        //
        // Step 2: Find a row which has not been used in the last 4 hours
        //
        $notAllowedLinks = array();
        if ($startDateInUTC) {
            try {
                $dt = new DateTime($startDateInUTC, new DateTimeZone('UTC'));
                $startDateInUTC = $dt->format("Y-m-d H:i:s");
                if ($startDateInUTC) {
                    $events_to_investigate = self::DBROGet("SELECT `eventid`, `web_conference_link` FROM `events` WHERE `companyid`={$_COMPANY->id()} AND (events.start > '{$startDateInUTC}' - INTERVAL 4 HOUR AND events.end < '{$startDateInUTC}' + INTERVAL 4 HOUR)");
                    $notAllowedLinks = array_column($events_to_investigate, 'web_conference_link');
                }
            } catch (Exception $e) {
            }
        }
        # Find a row which has not been used in 4 hours before / after
        foreach ($rows as $row) {
            if (in_array($row['meetinglink'], $notAllowedLinks)) { // The meeting link is already used, so skip it
                continue;
            }

            // Check if the link has not already been used by race condition schedule use, if we can update the usage_count that means it has not been used.
            $updatedCounter = self::DBAtomicUpdate("UPDATE `user_schedule_links` SET `usage_count`=`usage_count`+1 WHERE `schedule_id`={$this->id()} AND `schedule_linkid`={$row['schedule_linkid']} AND usage_count={$row['usage_count']}");
            if ($updatedCounter) { // If we could update it then we have found a right value.
                return $row;
            }
        }

        # Last resort option - It looks like all links have been used in the 4 hours before / after, so lets just use the first row
        $row = $rows[0];
        $updatedCounter = self::DBAtomicUpdate("UPDATE `user_schedule_links` SET `usage_count`=`usage_count`+1 WHERE `schedule_id`={$this->id()} AND `schedule_linkid`={$row['schedule_linkid']} AND usage_count={$row['usage_count']}");
        return $row;
    }

    /**
     * For a schedule, for each day in it
     * Create a recurring background event
     * Do not worry about collission
     * The background event will go behind the actual event and would not show to the user if there's a collission
     */
    public function getFreeSlotsRRule(): array
    {
        $weekly_available_time = $this->val('weekly_available_time');

        $user_tz = new DateTimeZone($this->val('user_tz'));

        $schedule_start =
            (new DateTimeImmutable($this->val('start_date_in_user_tz'), $user_tz))
            ->setTimezone(new DateTimeZone('UTC'));

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $recurrence_start = (max($schedule_start, $now))->format('c');

        $schedule_end =
            (new DateTimeImmutable($this->val('end_date_in_user_tz') . ' 23:59:59', $user_tz))
            ->setTimezone(new DateTimeZone('UTC'));

        $recurrence_end = $schedule_end->format('c');

        $slots = [];
        foreach ($weekly_available_time as $day_available_time) {

            $time_slots = Date::PrepareTimeSlots(
                $day_available_time['daily_start_time_in_user_tz'],
                $day_available_time['daily_end_time_in_user_tz'],
                $this->val('schedule_slot')
            );

            foreach ($time_slots as $time_slot) {
                $slot_start_time = (new DateTimeImmutable('now', $user_tz))
                    ->setTime(...explode(':', $time_slot['slot_start_time']))
                    ->setTimezone(new DateTimeZone($_SESSION['timezone']))
                    ->format('H:i');

                $slot_end_time = (new DateTimeImmutable('now', $user_tz))
                    ->setTime(...explode(':', $time_slot['slot_end_time']))
                    ->setTimezone(new DateTimeZone($_SESSION['timezone']))
                    ->format('H:i');

                /**
                 * https://fullcalendar.io/docs/recurring-events
                 * Simple Recurrence
                 */
                $slots[] = [
                    'tskp_free_slot' => true,
                    'daysOfWeek' => [$day_available_time['day_of_week']],
                    'startTime' => $slot_start_time,
                    'endTime' => $slot_end_time,
                    'startRecur' => $recurrence_start,
                    'endRecur' => $recurrence_end,
                    'display' => 'background',
                    'title' => 'Free Slot',
                    'tskp_free_slot_tooltip' => "{$this->val('schedule_slot')} minute free slot in schedule: {$this->val('schedule_name')}",
                ];
            }
        }

        return $slots;
    }

    public static function GetAvailableAndBookedScheduleSlots(int $schedule_id, string $timeZone='')
    {
        global $_USER;
        $grossAvailableSlots = [];
        $upcomingAvailableSlots = [];
        $grossBookedSlots = [];
        $upcomingBookedSlots = [];
       
        $schedule = self::GetSchedule($schedule_id);
        if (!$schedule){
            return [$grossAvailableSlots,$upcomingAvailableSlots,$grossBookedSlots,$upcomingBookedSlots];
        }
        
        if (!$timeZone){
            $timeZone = $schedule->val('user_tz');
        }
        
        $weekAvailableTimes = $schedule->getScheduleDetails();
        $sourceDateTimeRanage = [];
        $rsvpedEventsDateTime = [];
        $dateRange = Date::GetDatesFromRange($schedule->val('start_date_in_user_tz'), $schedule->val('end_date_in_user_tz'), 'Y-m-d');   
        foreach($dateRange as $dt){
            $weekDay = date('w', strtotime($dt));
            foreach($weekAvailableTimes as $timeSlot) {
                if ($timeSlot['day_of_week'] == $weekDay) {
                    $sourceDateTimeRanage[] = array($dt.' '.$timeSlot['daily_start_time_in_user_tz'], $dt.' '.$timeSlot['daily_end_time_in_user_tz']);
                }
            }
            // GET all the slots
            $grossAvailableSlots = array_merge($grossAvailableSlots, UserSchedule::GenerateCrossTimezoneTimeSlots($sourceDateTimeRanage, $dt,$schedule->val('schedule_slot'),$schedule->val('user_tz'),$timeZone));
            // Get Mentors RSVPed event for selected date
            $rsvpedEvents = Event::GetJoinedEventsByDate($_USER->id(), $dt);
            foreach ($rsvpedEvents as $evt) { // Covert event date to selected timezone 
                $rsvpedEventsDateTime[] = Date::ConvertDatetimeTimezone($evt['start'],'UTC', $timeZone);
            }
        }
      
        $nowDateObj = Date::ConvertDatetimeTimezone(date("Y-m-d H:i:s"),$timeZone, $timeZone);
        foreach ($grossAvailableSlots as $slot) {
            $slotDateObj = Date::ConvertDatetimeTimezone($slot["date"].' '.$slot["start24"],$timeZone, $timeZone);
            //Calculate All booked slots
            if (Date::IsDateTimeInArray($slotDateObj, $rsvpedEventsDateTime)) { 
                $grossBookedSlots[] = $slot;
            }
            // Calculate upcoming slots
            if ($nowDateObj <= $slotDateObj) {
                $upcomingAvailableSlots[] = $slot;
                //Calculate upcoming booked slots
                if (Date::IsDateTimeInArray($slotDateObj, $rsvpedEventsDateTime)) { 
                    $upcomingBookedSlots[] = $slot;
                }
            }
        }
        return [$grossAvailableSlots,$upcomingAvailableSlots,$grossBookedSlots,$upcomingBookedSlots];
    }

    public static function FilterSkippedSlotsWithBetweenBuffer(array $slots, int $bufferMinutes, string $selectedTimezone) {
        if (empty($slots)) {
            return [];
        }
        $sourceTimeZone = new DateTimeZone($selectedTimezone);
        //$filteredSlots = [];
        $skippedSlots = [];
        $nextAvailableTime = null;
        foreach ($slots as $slot) {
            $slotDateTime = new DateTime($slot['date'] . ' ' . $slot['start24'], $sourceTimeZone);
            $nowDateTime = new DateTime(date("Y-m-d H:i:s"), $sourceTimeZone);
            if ($slotDateTime < $nowDateTime){
                continue;
            }
            // Convert into time seconds
            $slotStartTime = strtotime($slot['date'] . ' ' . $slot['start24']);
            $slotEndTime = $slotStartTime + ($slot['duration']['hour'] * 3600) + ($slot['duration']['minutes'] * 60);
            
            // If this slot is after the next available time, add it to the result
            if (is_null($nextAvailableTime) || $slotStartTime >= $nextAvailableTime) {
                //$filteredSlots[] = $slot;
                $nextAvailableTime = $slotEndTime + ($bufferMinutes * 60); // Add buffer time after slot end
            } else {
                $skippedSlots[] = $slot; // Store skipped slots
            }
        }
        return $skippedSlots;
    }

    public static function FilterSkippedSlotsWithStartBuffer(array $slots, int $bufferMinutes, string $selectedTimezone) {
        if (empty($slots)) {
            return [];
        }
        $sourceTimeZone = new DateTimeZone($selectedTimezone);
      
        $skippedSlots = [];
        foreach ($slots as $slot) {
            $slotDateTime = DateTime::createFromFormat('Y-m-d H:i', $slot['date'] . ' ' . $slot['start24']);
            $currentDateTime = DateTime::createFromFormat('Y-m-d H:i', date('Y-m-d H:i'));
           
            $bufferDateTime = clone $currentDateTime;
            $bufferDateTime->modify('+'.$bufferMinutes.' minutes');

            if ($slotDateTime < $bufferDateTime) {
                $skippedSlots[] = $slot;
            } 
        }

        return $skippedSlots;
    }

    public static function SendAllSlotsBookedNotification(int $schedule_id) 
    {
        global $_COMPANY, $_ZONE;
        $schedule = self::GetSchedule($schedule_id);
        if (!$schedule){
            return;
        }

        $scheduleName = $schedule->val('schedule_name');
        $scheduleUserid = $schedule->val('userid');
        $scheduleUser = User::GetUser($scheduleUserid);
        $subject = "Your {$scheduleName} schedule is completely booked";
        $message = "<p>Dear {$scheduleUser->getFullName()}</p>";
        $message .= "<p>Your {$scheduleName} schedule is completely booked. To open up more availability, please add new slots.</p>";
        $appType = $_COMPANY::APP_LABEL[$_ZONE->val('app_type')];
        $message .= '<p>From,</p>';
        $message .= "<p>{$_COMPANY->val('companyname')} - {$appType}</p>";

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message = str_replace('#messagehere#', $message, $template);

        return $_COMPANY->emailSend2($_ZONE->val('email_from_label'), $scheduleUser->val('email'), $subject, $message, $_ZONE->val('app_type'));
    }


    public function addGroupAssociation(array $groupidsArray)
    {
        $groupids = '0';
        if ($groupidsArray) {
            $groupidsArray = Arr::IntValues($groupidsArray);
            $groupids = implode(',', $groupidsArray);
        }

        return self::DBUpdatePS("UPDATE user_schedule SET groupids = ? WHERE schedule_id = ?", 'xi', $groupids, $this->id());
    }

    public static function GetAllActiveGroupSupportSchedulesByGroup(int $groupid)
    {
        global $_COMPANY;
        $schedules = array();
        $scheduleRows= self::DBROGet("SELECT * FROM `user_schedule` WHERE companyid={$_COMPANY->id()} AND schedule_scope='group_support' AND (FIND_IN_SET({$groupid}, `groupids`) OR `groupids`='0') AND isactive=1");
        foreach ($scheduleRows as $scheduleRow) {
            $schedules[] = UserSchedule::Hydrate($scheduleRow['schedule_id'],$scheduleRow);
        }
        return  $schedules;
    }

    public static function GetUserAvailableBookingSlots(int $userid, string $date, string $selectedTimezone, string $schedule_scope, int $groupid)
    {
        $availableSlots = array();
        $bufferSkippedSlots = array();
        if (!$userid || empty($date) || empty($selectedTimezone)) {
            return array($availableSlots, $bufferSkippedSlots);
        }
        $timeSchedules = self::GetAllUsersSchedules($userid, true, $schedule_scope, $groupid);
       
        foreach ($timeSchedules as  $timeSchedule) {
            $sourceDateTimeRanage = array();
            foreach (Date::GetDatesFromRange($timeSchedule['start_date_in_user_tz'], $timeSchedule['end_date_in_user_tz'], 'Y-m-d') as $dt) {
                $weekDay = date('w', strtotime($dt));
                $weekAvailableTimes = $timeSchedule['weekly_available_time'];
                
                foreach($weekAvailableTimes as $timeSlot) {
                    if ($timeSlot['day_of_week'] == $weekDay) {
                        $sourceDateTimeRanage[] = array($dt.' '.$timeSlot['daily_start_time_in_user_tz'], $dt.' '.$timeSlot['daily_end_time_in_user_tz']);
                    }
                }
            }

            $newAvailableSlots = UserSchedule::GenerateCrossTimezoneTimeSlots($sourceDateTimeRanage, $date,$timeSchedule['schedule_slot'],$timeSchedule['user_tz'],$selectedTimezone);
            if ($timeSchedule['start_time_buffer']){
                $bufferMinutes = $timeSchedule['start_time_buffer'] * 60; // Convert to minutes
                //Filter Skipped Slots with start Buffer
                $skippedSlots = UserSchedule::FilterSkippedSlotsWithStartBuffer($newAvailableSlots,$bufferMinutes, $selectedTimezone);
                $bufferSkippedSlots = array_merge($bufferSkippedSlots,$skippedSlots);
            }
            // Add Schedule Id to each slot
            $newAvailableSlots = array_map(function ($item) use($timeSchedule) {
                $item['schedule_id'] = $timeSchedule['schedule_id']; // Adding new key
                $item['userid'] = $timeSchedule['userid']; // Adding new key
                return $item;
            }, $newAvailableSlots);
            // GET all the slots
            $availableSlots = array_merge($availableSlots, $newAvailableSlots);
        }

        return array($availableSlots, $bufferSkippedSlots);

    }

    public static function GetUsersAvailableDaysToSchedule (int $subjectUserId, bool $activeOnly, string $schedule_scope, int $groupid) : array
    {   
        $availableDaysToSchedule = array();
        $timeSchedules = self::GetAllUsersSchedules($subjectUserId,$activeOnly, $schedule_scope, $groupid);
        foreach ($timeSchedules as $timeSchedule) {

            $days = Date::GetDatesFromRange($timeSchedule['start_date_in_user_tz'], $timeSchedule['end_date_in_user_tz'], 'd-m-Y');

            foreach (Date::GetDatesFromRange($timeSchedule['start_date_in_user_tz'], $timeSchedule['end_date_in_user_tz'], 'Y-m-d') as $dt) {
                foreach ($timeSchedule['weekly_available_time'] as $time) {
                    $sourceDateTimeRanage[] = array($dt . ' ' . $time['daily_start_time_in_user_tz'], $dt . ' ' . $time['daily_end_time_in_user_tz']);
                }
            }
            $availableDaysToSchedule = array_merge($availableDaysToSchedule, $days);
        }
        return $availableDaysToSchedule;
    }

    public static function GetGroupSupportUserIdsWithSchedules(int $groupid): array
    {
        global $_COMPANY;
        $scheduleRows= self::DBROGet("SELECT userid FROM `user_schedule` WHERE companyid={$_COMPANY->id()} AND schedule_scope='group_support' AND (FIND_IN_SET({$groupid}, `groupids`) OR `groupids`='0') AND isactive=1");
        return array_unique(array_column($scheduleRows, 'userid'));
    }

}