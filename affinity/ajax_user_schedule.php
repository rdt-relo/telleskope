<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__ .'/head.php'; //Common head.php includes destroying Session for timezone so defining INDEX_PAGE. Need head for logging and protecting

global $_COMPANY;
global $_USER;
global $_ZONE;
global $db;

/* @var Company $_COMPANY */
/* @var User $_USER */

###### All Ajax Calls ##########

###### AJAX Calls ######
##### Should be in if-elseif-else #####

## OK
## Manage Slots
if (isset($_GET['manageAvailalbeSchedules'])){

    $availableSchedules = UserSchedule::GetAllUsersSchedules($_USER->id());

    include(__DIR__ . "/views/available_schedules/manage_available_schedule_list_model.php");
    exit();
}
elseif (isset($_GET['addUpdateNewScheduleModal'])){
    //Data Validation
    if (($schedule_id = $_COMPANY->decodeId($_GET['schedule_id']))<0) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $modalTitle = gettext("Add new schedule");
    $timezone = $_SESSION['timezone'];
    $schedule = null;
    $scheduleDetails = array();
    $scheduleArray = array();
    if ($schedule_id) {
        $schedule = UserSchedule::GetSchedule($schedule_id);

        $scheduleArray =  $schedule->toArray();
        $scheduleLink = $schedule->getLeastUsedMeetingLink();
        $scheduleArray['user_meeting_link'] =  $scheduleLink ?  $scheduleLink['meetinglink'] : "";
        $scheduleArray['schedule_groups'] = Str::ConvertCSVToArray($schedule->val('groupids'));

        $scheduleDetails = $schedule->getScheduleDetails();
        $timezone  = $schedule->val('user_tz');
        $modalTitle = gettext("Update schedule");
    }
    $weekDays = UserSchedule::WEEK_DAYS;
    
    $groupsForSupportSchedule = Group::GetAllGroupsForScheduleConfiguration('bookings');
    $groupsForTeamSchedule = Group::GetAllGroupsForScheduleConfiguration('teams');

    include(__DIR__ . "/views/available_schedules/add_update_schedule_model.php");
    exit();
}
elseif (isset($_GET['addUpdateSchedule'])){
    //Data Validation
    if (($schedule_id = $_COMPANY->decodeId($_POST['schedule_id']))<0) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $schedule_name = $_POST['schedule_name'];
    $schedule_slot = $_POST['schedule_slot'];
    $start_date_in_user_tz = $_POST['start_date_in_user_tz'];
    $end_date_in_user_tz = $_POST['end_date_in_user_tz'];
    $timezone = $_POST['user_tz'];
    $schedule_description = "";
    $link_generation_method = 'manual';
    if (isset($_POST['link_generation_method'])) {
        $link_generation_method = $_POST['link_generation_method'] == '1' ? 'automatic' : 'manual' ;
    }
    $user_meeting_link = '';
    if (isset($_POST['user_meeting_link']) && $link_generation_method == 'manual'){
        $user_meeting_link = $_POST['user_meeting_link'];
    }
    // Validation
    $check = array( 'Schedule Name' => $schedule_name,'Start Date' => $start_date_in_user_tz, 'End Date' => $end_date_in_user_tz, 'Schedule Slot' => $schedule_slot, 'Timezone' => $timezone, 'Schedule Scope'=>$_POST['schedule_scope']);
    if ($link_generation_method == 'manual') {
        $check = array_merge($check,array('Meeting Link' => $user_meeting_link));
    }
    $checkRequired = $db->checkRequired($check);
    if($checkRequired){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty!"),$checkRequired), gettext('Error!'));
    }
    
    // Date Validation
    if ($start_date_in_user_tz > $end_date_in_user_tz) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Start date cannot be greater than end date."), gettext('Error!'));
    }

    // if (Date::HasDateConflict($start_date_in_user_tz, $end_date_in_user_tz,UserSchedule::GetUsersSchedulesDates($_USER->id(),$schedule_id))){
    //     AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Start date or end date conflicts with another schedule. Please resolve the conflict."), gettext('Error!'));
    // }
    $schedule_groups = array();
    $schedule_scope = $_POST['schedule_scope'];
    if ($schedule_scope == 'team_event' && $_POST['schedule_group_restriction_for_team_event_schedule']) {
        $schedule_groups = $_POST['schedule_team_groups'];
        if (empty($schedule_groups)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("Please select at least one %s."),$_COMPANY->getAppCustomization()['group']['name-short']), gettext('Error!'));
        }
        $schedule_groups = $_COMPANY->decodeIdsInArray($schedule_groups);
    } elseif ($schedule_scope == 'group_support' && $_POST['schedule_group_restriction_for_support_schedule']) {
        $schedule_groups = $_POST['schedule_group_support_groups'];
        if (empty($schedule_groups)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("Please select at least one %s."),$_COMPANY->getAppCustomization()['group']['name-short']), gettext('Error!'));
        }
        $schedule_groups = $_COMPANY->decodeIdsInArray($schedule_groups);
    }

    $start_time_buffer = $_POST['start_time_buffer'];
    $schedule = null;
    if ($schedule_id) {
        $schedule = UserSchedule::GetSchedule($schedule_id);
        $schedule->updateSchedule($schedule_name, $schedule_slot, $start_date_in_user_tz, $end_date_in_user_tz, $timezone, $schedule_description, $link_generation_method, $start_time_buffer, $schedule_scope);
    } else {
        $schedule_id = UserSchedule::SaveSchedule($schedule_name, $schedule_slot, $start_date_in_user_tz, $end_date_in_user_tz, $timezone, $schedule_description, $link_generation_method, $start_time_buffer, $schedule_scope);
        if ($schedule_id) {
            $schedule = UserSchedule::GetSchedule($schedule_id);
        }
    }
    if ($schedule) {

        if (!empty($user_meeting_link)) { // Add Meeting link if set
            $schedule->saveSchedulelMeetingLinks(array($user_meeting_link));
        }

        $scheduleDetails = $schedule->getScheduleDetails();
        if (!empty($scheduleDetails)) {
            $schedule->cleanScheduleDetails();
        }
        $weeklySchedule = $_POST['weeklySchedule'];

        foreach($weeklySchedule as $day => $sheduleTimes) {
            $dayNumber = date('w', strtotime($day));
            $startTimes = $sheduleTimes['startTime'];
            $endTimes = $sheduleTimes['endTime'];
            $i = 0;
            foreach($startTimes as $key => $sTime) {
                $eTime = $endTimes[$i];
                if ($sTime && $eTime) {
                    $schedule->saveScheduleDetail($dayNumber, $sTime, $eTime);
                }
                $i++;
            }
        }
        // Add Group Schedule
        $schedule->addGroupAssociation($schedule_groups);

        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Schedule saved successfully"), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong while saving the schedule. Please try again after sometime."), gettext('Error!'));
    }
    exit();
}

elseif (isset($_GET['deleteSchedule'])){
    //Data Validation
    if (
        ($schedule_id = $_COMPANY->decodeId($_GET['schedule_id']))<1 ||
        ($schedule = UserSchedule::GetSchedule($schedule_id)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if ($schedule->deleteSchedule()){
        $schedule->deleteAllMeetingLinks();
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Schedule deleted successfully"), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong while deleting the schedule. Please try again after sometime."), gettext('Error!'));
    }
    exit();
}
    
elseif (isset($_GET['getAvailableSlots'])){
    if (
        ($date = $_GET['date']) == '' ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null 
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $selectedTimezone = "UTC";
    if (isset($_GET['timezone'])
        && isValidTimeZone($_GET['timezone'])
    ) {
        $selectedTimezone = $_GET['timezone'];
    } 
    $eventStartDate = $_GET['eventStartDate'];
    $eventDateObj = null;
    if ($eventStartDate){ // Convert event start date to selected timezone
        $eventDateObj = Date::ConvertDatetimeTimezone($eventStartDate,"UTC", $selectedTimezone);
    }

    $availableSlots = array();
    $bufferSkippedSlots = array();
    $mentors  = $team->getTeamMembersBasedOnSysRoleid(2);
    $rsvpedEventsDateTime = array();

    if (!empty($mentors)) {
        $mentorUserid = $mentors[0]['userid'];
        $timeSchedules = UserSchedule::GetAllUsersSchedules($mentorUserid, true, 'team_event', $team->val('groupid'));
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
                return $item;
            }, $newAvailableSlots);
            // GET all the slots
            $availableSlots = array_merge($availableSlots, $newAvailableSlots);
        }

        // Get Mentors RSVPed event for selected date
        $rsvpedEvents = Event::GetJoinedEventsByDate($mentorUserid, $date);
        foreach ($rsvpedEvents as $evt) { // Covert event date to selected timezone 
            $rsvpedEventsDateTime[] = Date::ConvertDatetimeTimezone($evt['start'],'UTC', $selectedTimezone);
        }
    }
    $slotHtml = array();
    $i=0;
    $nowDateObj = Date::ConvertDatetimeTimezone(date("Y-m-d H:i:s"),$selectedTimezone, $selectedTimezone);
    $durationInMinutes = array();

    foreach ($availableSlots as $slot) {
        $slotDateObj = Date::ConvertDatetimeTimezone($slot["date"].' '.$slot["start24"],$selectedTimezone, $selectedTimezone);
        // Hide if the time slot is past now
        if ($nowDateObj > $slotDateObj) {
            continue;
        }
        // Hide slot if mentor already rsvped save slot event
        if ($eventDateObj != $slotDateObj && Date::IsDateTimeInArray($slotDateObj, $rsvpedEventsDateTime)) { 
            continue;
        }

        $skip = !empty(array_filter($bufferSkippedSlots, function($skippedSlot) use ($slot) {
            return $skippedSlot['date'] === $slot['date'] && 
                   $skippedSlot['start24'] === $slot['start24'];
        }));

        $checked = "";
        if ($eventDateObj) {
            if ($eventDateObj == $slotDateObj) {
                $checked = "checked";
            }
        }

        if ($skip &&  $checked =='') {
            continue;
        }
        if (!in_array($slot["duration"]['minutes'], $durationInMinutes)) {
            $durationInMinutes[] = $slot["duration"]['minutes'];
        }
        
        $slotHtml[] = '<div class="time-slot col-6 p-2" role="radiogroup" aria-labelledby="slot-label">
            <input type="radio" name="time_slot" id="slot'.$i.'" data-duration="'.$slot['duration']['minutes'].'" value="'.$slot["date"].' '.$slot["start24"].'" '.$checked.' data-schedule_id="'.$_COMPANY->encodeId($slot["schedule_id"]).'">
            <label class="d-block text-small text-center"  for="slot'.$i.'">'.$slot["start12"].'</label>
        </div>';
        $i++;
    }
      
    if (!empty($slotHtml)){
        $durationInMinutes = array_map(function($value) {
            return $value . " minutes";
        }, $durationInMinutes);
        echo '<div class="col-12"><p class="control-label">'.gettext("Available Time Slots"). '<br><span style="background-color: #fadeab; padding-left: 3px; padding-right: 3px;">' .implode(' / ', $durationInMinutes) . ' ' . gettext("duration") .'</span></p></div>'.implode(" ", $slotHtml);
    } else {
        echo '<div class="col-12"><p class="control-label text-center py-5 red">'.gettext("No time slots available for selected date").'</p></div>';
    }
    exit();
}

elseif (isset($_GET['saveScheduleMeetingLinks']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($schedule_id = $_COMPANY->decodeId($_POST['schedule_id']))<1 ||
        ($schedule = UserSchedule::GetSchedule($schedule_id)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $scheduler_meetinglinks = json_decode($_POST['scheduler_meetinglinks']??'{}',true);

    if (!empty($scheduler_meetinglinks)){
        $schedule->saveSchedulelMeetingLinks($scheduler_meetinglinks);
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Schedule meeting links saved successfully"), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong while deleting the schedule. Please try again after sometime."), gettext('Error!'));
    }
    exit();
}

elseif (isset($_GET['activateDeactivateSchedule']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($schedule_id = $_COMPANY->decodeId($_POST['schedule_id']))<1 ||
        ($schedule = UserSchedule::GetSchedule($schedule_id)) === NULL ||
        ($status = $_COMPANY->decodeId($_POST['status']))<0 
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
   
    if ($status == 1){
        $retVal = $schedule->activate();
        if ($retVal == -1) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Before activating this schedule, you need to add meeting links to it."), gettext('Error!'));
        }
        $message  = gettext("Schedule has been activated successfully.");
    } else {
        $schedule->deactivate();
        $message  = gettext("Schedule has been deactivated successfully.");
    }
    AjaxResponse::SuccessAndExit_STRING(1, '', $message, gettext('Success'));

    exit();
}

elseif (isset($_GET['viewSchedule']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_schedules = UserSchedule::GetAllUsersSchedules($_USER->id(), true);
    if (empty($user_schedules)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('You do not have any active schedule'), gettext('Error!'));
    }

    $user_schedules = array_map(function (array $user_schedule) {
        return UserSchedule::Hydrate($user_schedule['schedule_id'], $user_schedule);
    }, $user_schedules);

    $user_timezone = new DateTimeZone($_SESSION['timezone']);
    $event_start_date =
        (new DateTimeImmutable('first day of last month', $user_timezone))
        ->setTime(0, 0, 0);

    $event_end_date =
        (new DateTimeImmutable('last day of +6 months', $user_timezone))
        ->setTime(23, 59, 59);

    $my_scheduled_team_events = Event::GetMyEventsHavingScheduleId($event_start_date, $event_end_date);
//    $my_team_events = Event::GetMyTeamEvents($event_start_date, $event_end_date);
//    if (empty($my_team_events)) {
//        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('You do not have any scheduled events'), gettext('Error!'));
//    }


//    $my_scheduled_team_events = array_filter($my_team_events, function ($event) use ($user_schedules) {
//        foreach ($user_schedules as $user_schedule) {
//            if ($user_schedule->isEventWithinSchedule($event)) {
//                return true;
//            }
//        }
//
//        return false;
//    });

    // Commented as we do not want to show error, even if there are no events the user may want to look at their
    // schedule to see the empty slots.
    //if (empty($my_scheduled_team_events)) {
    //    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('You do not have any scheduled events'), gettext('Error!'));
    //}

    $calendarDefaultView = 'timeGridWeek';
    $calendarDefaultDate = date('Y-m-d');
    $calendarLang = $_COMPANY->getCalendarLanguage();
    $calendar_header_toolbar = 'timeGridWeek,timeGridDay';
    $calendar_scroll_to_current_time = true;
    $calendar_container_id = 'js-view-schedule-calendar';
    $calendar_event_start_date = $event_start_date->format('Y-m-d');
    // Fullcalendar end date is exclusive
    // https://fullcalendar.io/docs/validRange
    $calendar_event_end_date = $event_end_date->modify('+1 day')->format('Y-m-d');

    foreach ($user_schedules as $user_schedule) {
        $user_schedule_slot_duration = (int) $user_schedule->val('schedule_slot');
        $calendar_slot_duration ??= $user_schedule_slot_duration;
        $calendar_slot_duration = min($calendar_slot_duration, $user_schedule_slot_duration);
    }

    $calendar_slot_duration = "00:{$calendar_slot_duration}:00";

    $events = array_map(function (Event $event) {
        return $event->toArray();
    }, $my_scheduled_team_events);

    $free_slots = [];
    foreach ($user_schedules as $user_schedule) {
        $schedule_slots = $user_schedule->getFreeSlotsRRule();
        $free_slots = array_merge($free_slots, $schedule_slots);
    }

    $showPrivateEventBlocks = true;

    require __DIR__ . '/views/available_schedules/view_schedule.html.php';
    exit();
}

elseif (isset($_GET['viewScheduleStats'])){
    //Data Validation
    if (
        ($schedule_id = $_COMPANY->decodeId($_GET['schedule_id']))<1 ||
        ($schedule = UserSchedule::GetSchedule($schedule_id)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    
    [$totalSlotsArray,$totalUpcomingSlotsArray,$grossBookedSlotsArray,$upcomingBookedSlotsArray] = UserSchedule::GetAvailableAndBookedScheduleSlots($schedule_id);
    $totalSlotsCount = count($totalSlotsArray);
    $totalUpcomingSlotsCount = count($totalUpcomingSlotsArray);
    $totalBookedCount = count($grossBookedSlotsArray);
    $totalUpcomingBookedCount = count($upcomingBookedSlotsArray);
    $totalAvailableCount = $totalSlotsCount - $totalBookedCount;
    $totalUpcomingAvailableCount = $totalUpcomingSlotsCount - $totalUpcomingBookedCount;

    include(__DIR__ . "/views/available_schedules/view_schedule.fullstats.html.php");
    exit();
}
else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit();
}
