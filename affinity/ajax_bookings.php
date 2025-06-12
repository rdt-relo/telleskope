<?php

define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__.'/head.php';

global $_COMPANY; /* @var Company $_COMPANY */
global $_USER;/* @var User $_USER */
global $_ZONE;
global $db;

# Module Level Authorization
if (!$_COMPANY->getAppCustomization()['booking']['enabled']) {
    header(HTTP_BAD_REQUEST);
    exit();
}

###### All Ajax Calls For Events ##########
## OK
## Get My Bookings

if (isset($_GET['getMyBookings']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $timezone = (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
    $bookingRequests = Event::GetMySentSupportBookings($groupid);
    $show_more = false;
    include(__DIR__ . "/views/bookings/booking_home.template.php");
}
elseif (isset($_GET['getReceivedBookings']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $timezone = (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
    $bookingRequests = Event::GetMyReceivedSupportBookings($groupid);
    $show_more = false;
    include(__DIR__ . "/views/bookings/booking_rows.template.php");
}

elseif (isset($_GET['newSupportBookingForm']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $section = 'detail';
    if (isset($_GET['section'])){
        $section = $_GET['section'];
    }
    $bookingCreatorId = $_USER->id();
    if ($section == 'schedule_new_booking') {
        $bookingCreatorId = 0;
    }
    $bookingSupportId = 0;
    $event_booking_id = 0;
    $eventBooking = null;
    $timezone = (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
    $groupSupportLeads = $group->getAllGroupLeadsBySupportType();
    $groupSupportLeadsUserIds = array_column($groupSupportLeads,'userid');
    $availableUserIds = UserSchedule::GetGroupSupportUserIdsWithSchedules($groupid);
    $finalAvailableUserids = array_unique(array_intersect($availableUserIds,$groupSupportLeadsUserIds));
   
    $availableDaysToSchedule = array();
    $supportUsers = array();
    foreach($finalAvailableUserids as $uid) {
        if ($uid == $bookingCreatorId){
            continue;
        }
        $supportUsers[] = User::GetUser($uid);
        $availableDays = UserSchedule::GetUsersAvailableDaysToSchedule($uid,true,'group_support', $groupid);
        if (!empty($availableDays)){
            $availableDaysToSchedule = array_merge($availableDaysToSchedule, $availableDays);
        }
    }

    if ($_ZONE->val('app_type') == 'peoplehero' && $_USER->val('employee_start_date') && !empty($availableDaysToSchedule)) {
        $employee_start_date = new DateTime($_USER->val('employee_start_date'));
        $getBookingStartBuffer = $group->getBookingStartBuffer();
        
        if ($getBookingStartBuffer['days_before_start_to_allow_booking']>0){
            $employee_start_date->modify('-'.$getBookingStartBuffer['days_before_start_to_allow_booking'].' days');
        }
        $employee_start_date = $employee_start_date->format('Y-m-d');
        $filteredDates = array_filter($availableDaysToSchedule, function($date) use ($employee_start_date) {
            $dateObj = DateTime::createFromFormat('d-m-Y', $date);
            $slotDate = $dateObj->format('Y-m-d');
            return $slotDate <= $employee_start_date;
        });
        $availableDaysToSchedule = array_values($filteredDates);
    }
    $emailTemplate = $group->getBookingEmailTemplate();
    $submitButton = gettext('Submit Booking');
    $pageTitle = gettext("Schedule Meeting");
    if ($section == 'schedule_new_booking'){
        include(__DIR__ . "/views/bookings/new_booking_in_model.template.php");
    } else {
        include(__DIR__ . "/views/bookings/new_booking.template.php");
    }
}

elseif (isset($_GET['getAvailableBookingSlots'])){
    if (
        ($date = $_GET['date']) == '' ||
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($eventid = $_COMPANY->decodeId($_GET['eventid'])) < 0 ||
        ($group = Group::GetGroup($groupid)) == null 
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
    $eventObj = null;
    if($eventid){
        $eventObj = Event::GetEvent($eventid);
    }
    $section = $_GET['section'];
    $groupSupportLeadsUserIds = !empty($_GET['support_users']) ? $_COMPANY->decodeIdsInCSV($_GET['support_users']) : '';
    $groupSupportLeadsUserIds = array_filter(explode(',',$groupSupportLeadsUserIds));
    
    if (empty($groupSupportLeadsUserIds)){
        $groupSupportLeadsUserIds =  array_column($group->getAllGroupLeadsBySupportType(),'userid');
        $usersWhoHasSchedules = UserSchedule::GetGroupSupportUserIdsWithSchedules($groupid);
        $groupSupportLeadsUserIds = array_unique(array_intersect( $usersWhoHasSchedules,$groupSupportLeadsUserIds));
    }

    $availableSlots = array();
    $bufferSkippedSlots = array();
    $rsvpedEventsDateTime = array();
    
    foreach($groupSupportLeadsUserIds as $uid) {
        if ($section=='detail' && $uid == $_USER->id()){
            continue;
        } elseif($section!='detail' &&  $eventObj &&  $eventObj->val('userid') == $uid) {
            continue;
        }
        [$newAvailableSlots,$newBufferSkippedSlots] = UserSchedule::GetUserAvailableBookingSlots($uid,$date,$selectedTimezone,'group_support', $groupid);
        if (!empty($newAvailableSlots)) {
            $availableSlots = array_merge($availableSlots, $newAvailableSlots);
        }
        if (!empty($newBufferSkippedSlots)) {
            $bufferSkippedSlots = array_merge($bufferSkippedSlots, $newBufferSkippedSlots);
        }

        // Get Mentors RSVPed event for selected date
        $rsvpedEvents = Event::GetJoinedEventsByDate($uid, $date);
        foreach ($rsvpedEvents as $evt) { // Covert event date to selected timezone 
            $rsvpedEventsDateTime[] = array('date'=>Date::ConvertDatetimeTimezone($evt['start'],'UTC',$selectedTimezone),'userid'=>$uid);
        }
    }
    
    $slotHtml = array();
    $i=0;
    $nowDateObj = Date::ConvertDatetimeTimezone(date("Y-m-d H:i:s"),$selectedTimezone, $selectedTimezone);
    $durationInMinutes = array();
    // Order all slots to asc order 
    usort($availableSlots, function ($a, $b) {
        if ($a['date'] == $b['date']) {
            return strcmp($a['start24'], $b['start24']);
        }
        return strcmp($a['date'], $b['date']);
    });
    foreach ($availableSlots as $slot) {
        $slotDateObj = Date::ConvertDatetimeTimezone($slot["date"].' '.$slot["start24"],$selectedTimezone, $selectedTimezone);

        // Hide if the time slot is past now
        if ($nowDateObj > $slotDateObj && $eventDateObj != $slotDateObj) {
            continue;
        }
        // Hide slot if mentor already rsvped save slot event
        $filtered = array_filter($rsvpedEventsDateTime, fn($entry) => $entry['date']->format('Y-m-d H:i:s') === $slotDateObj->format('Y-m-d H:i:s'));
        if (!empty($filtered)) {
            //$result = array_values($filtered)[0]; // Get the first matched item
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

        $u = User::GetUser($slot["userid"]);
        // $supportUserInfo = "<p class='m-0 p-0 text-small'>".$u->getFullName()."</p><p class='m-0 p-0 text-small' ><small>(".$u->val('email').")</small></p>";
        $supportUserInfo  = '';

        $slotHtml[] = '<div class="time-slot col-6 p-2" role="radiogroup" aria-labelledby="slot-label">
            <input type="radio" name="time_slot" id="slot'.$i.'" data-duration="'.$slot['duration']['minutes'].'" value="'.$slot["date"].' '.$slot["start24"].'" '.$checked.' data-schedule_id="'.$_COMPANY->encodeId($slot["schedule_id"]).'" data-support_userid="'.$_COMPANY->encodeId($slot["userid"]).'">
            <label class="d-block text-small"  for="slot'.$i.'">'.$slot["start12"].' | <small><i class="fas fa-clock text-secondary"></i> '.$slot["duration"]['minutes'].' minutes</small>'.$supportUserInfo.'</label>
        </div>';
        $i++;
    }
      
    if (!empty($slotHtml)){
        echo '<div class="col-12"><p class="control-label">'.gettext("Available Time Slots").'</p></div>'.implode(" ", $slotHtml);
    } else {
        echo '<div class="col-12"><p class="control-label text-center py-5 red">'.gettext("No time slots available for selected date").'</p></div>';
    }
}

elseif(isset($_GET['addOrUpdateSupportBooking']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    $event = null;
    $collaborate = '';
    $chapterids = '0';
    $channelid = 0;
    $regionid = 0;
    $isprivate = 0;
    $seriesEvent = null;
    $add_photo_disclaimer = 0;
    $event_series_id = 0;
    $touchpointid = 0;

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($eventid = $_COMPANY->decodeId($_POST['eventid'])) < 0 ||
        ($schedule_id = $_COMPANY->decodeId($_POST['schedule_id'])) < 0 ||
        (isset($_POST['timezone'])
            && !isValidTimeZone($_POST['timezone'])
        )
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($eventid && ($event=Event::GetEvent($eventid)) === null){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($event){
        $version = (int) $_COMPANY->decodeId($_POST['version']);
        if ($event->val('version') >$version){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("You are not editing the latest version and your changes cannot be saved. In order to not lose your work, please copy your changes locally, re-edit the event, apply your changes, and try to save again."), gettext('Error'));
        }
    }

    $eventtitle = $_POST['eventtitle'];
    $eventtype = 0; 
    $event_description = ViewHelper::RedactorContentValidateAndCleanup( $_POST['event_description']);
    $event_attendence_type = 2;// default Virtual (Web Conference) only
    $event_contact = '';
    $eventvanue = '';
    $vanueaddress = '';
    $venue_info = '';
    $venue_room = '';
    $web_conference_link = '';
    $web_conference_detail = '';
    $web_conference_sp = '';
    $latitude = '';
    $longitude = '';
    $multiDayEvent = 0;
    $invited_groups = '';
    $checkin_enabled = intval($_COMPANY->getAppCustomization()['event']['checkin'] && $_COMPANY->getAppCustomization()['event']['checkin_default']);;

    $bookingCreatorId = isset($_POST['bookingCreatorId']) ? $_COMPANY->decodeId($_POST['bookingCreatorId']) : 0;

    if ($bookingCreatorId<1) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Search a user for whom the booking is being created."), gettext('Error'));
    }
   
    $check = array('Event Name' => @$eventtitle,'Event time Slot' => @$_POST['time_slot']);
   
    $checkrequired = $db->checkRequired($check);

    if ($checkrequired) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$checkrequired), gettext('Error'));
    }
    $max_inperson = 0;
    $max_inperson_waitlist = 0;
    $max_online = 0;
    $max_online_waitlist = 0;

    #Time zone
    $event_tz = isset($_POST['timezone']) ? $_POST['timezone'] : (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
    #Event Start Date time
    $time_slot = $_POST['time_slot'];
    $startDatetimeObj = Date::ConvertDatetimeTimezone($time_slot,$event_tz, "UTC");
    $start = $startDatetimeObj->format("Y-m-d H:i:s");
    $hour_duration = 0;
    $minutes_duration =  15; // Default
    $support_userid  = $_COMPANY->decodeId($_POST['support_userid']);
    if (!$support_userid ) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Support not available"), gettext('Error'));
    }

    if ($bookingCreatorId == $support_userid) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("The support user cannot be the same as the booking user. Please select a different user."), gettext('Error'));
    }

    $schedule = UserSchedule::GetSchedule($schedule_id);
    if ($schedule) {
        $schedule_slot_duration_in_minutes = intval($schedule->val('schedule_slot'));
        [$hour_duration, $minutes_duration] = Date::ConvertMinutesToHoursMinutes($schedule_slot_duration_in_minutes);
        $meetingLink = $schedule->getLeastUsedMeetingLink($start);
        if ($meetingLink){
            $web_conference_link = $meetingLink['meetinglink'];
            $web_conference_sp = "Meeting Link";
        }
    }
   
    #Event End Date time
    $endDatetimeObj = Date::IncrementDatetime($start, "UTC", $hour_duration, $minutes_duration);
    $end = $endDatetimeObj->format("Y-m-d H:i:s");
    $invited_locations = 0;
    // Custom Fields
    $custom_fields_input = json_encode(array());
    $budgeted_amount = 0;
    $calendar_blocks = 1;

    if (!$eventtitle) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please input a valid title."), gettext('Error'));
    }
    if (!$event_description) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please input a valid description."), gettext('Error'));
    }

    if (!$eventid) {
        $eventid = Event::CreateNewEvent($groupid, $chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $event_series_id, $custom_fields_input, $event_contact, $venue_info, $venue_room, $isprivate, 'booking', $add_photo_disclaimer, $calendar_blocks, '', '0', false);
        if ($eventid) {
            $event = Event::GetEvent($eventid);

            // For new events process updateScheduleId after event creation
            $eventScheduleDataRetVal = $event->createEventScheduleData($schedule_id,$start);
            if (!$eventScheduleDataRetVal) {
                // We could not create event schedule data for the given start date, do not allow this event and error out.
                $event->deleteIt();
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Sorry, we couldn't book the slot, it may have already been taken."), gettext('Error'));
            }
            $event->updateScheduleId($schedule_id);

            $newJoinerIds = array($support_userid, $bookingCreatorId);
            $event->syncEventCreator($bookingCreatorId); // This changed needed because CreateNewEvent store logined  users id in userid field of
            if ($event->publishEventToUsersImmediately($newJoinerIds, 1)) {
                AjaxResponse::SuccessAndExit_STRING(1, '', gettext("The slot has been booked successfully."), gettext('Success'));
            }
        }
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Unable to book the slot."), gettext('Error'));
    } else {
        $newJoinerIds = array($support_userid,  $event->val('userid'));

        $section = $_POST['section'];
        if($section == 'manage_reassign'){
            $event->removeOldJoinersAndNotify($newJoinerIds);
        }

        // For updates process updateOrCreateEventScheduleData before updating the event
        $eventScheduleDataRetVal = $event->updateOrCreateEventScheduleData($schedule_id, $start);
        if (!$eventScheduleDataRetVal) {
            // We could not update event schedule data for the given start date, do not allow this event and error out.
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Sorry, we couldn't book the slot, it may have already been taken."), gettext('Error'));
        }

        // Groupid cannot be changed for event, since this is update get the groupid from event
        $groupid = $event->val('groupid');
        $retVal = $event->updateEvent($chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $custom_fields_input, $event_contact, $add_photo_disclaimer, $venue_info, $venue_room, $isprivate, $calendar_blocks, '', '0', false);
        $event->updateScheduleId($schedule_id);
        $event->publishEventToUsersImmediately($newJoinerIds, 1);
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Slot booking has been updated successfully."), gettext('Success'));
    }
}
elseif(isset($_GET['deleteBooking'])){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $event_cancel_reason = $_POST['event_cancel_reason'] ?? '';
    $sendCancellationEmails = filter_var($_POST['sendCancellationEmails'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;

    $deleteAfterCancel = true;
    $event->cancelEvent($event_cancel_reason, $sendCancellationEmails, $deleteAfterCancel);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Booking canceled'), gettext('Success'));
}

elseif (isset($_GET['getBookingDetail']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

	if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($event->val('zoneid') != $_ZONE->id()) {
        $_ZONE = $_COMPANY->getZone($event->val('zoneid'));
        Logger::LogInfo("Switched zone from {$_ZONE->id()} to {$event->val('zoneid')}");
    }

    // Authorization Check
    if ($event->val('groupid')){
        if (!$_USER->canViewContent($event->val('groupid'))) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } 
    // Event Detail start
    $groupid 		= $event->val('groupid');
    $topicid = $eventid;
    $group 		= Group::GetGroup($groupid);

    // Allow comment feature on on main event view
    // Comment section
    /**
     * Dependencies for Comment Widget
     * $comments
     * $commentid (default 0)
     * $groupid
     * $topicid
     * $disableAddEditComment
     * $submitCommentMethod
     * $mediaUploadAllowed
    */
    if (0 && $_COMPANY->getAppCustomization()['event']['comments']) {
        $comments = Event::GetComments_2($eventid);            
        $commentid = 0;
        $disableAddEditComment = false;
        $submitCommentMethod = "EventComment";
        $mediaUploadAllowed = $_COMPANY->getAppCustomization()['event']['enable_media_upload_on_comment'];
    }

    /**
     * TOPIC like/unline and show latest 10 likers
     * Dependencies for topic like
     * $topicid (i.e. postid, eventid, newsletterid etc)
     * $myLikeStatus
     * $latestLikers
     * $totalLikers
     * $likeUnlikeMethod
     * $showAllLikers
     * 
     */
    if (0 && $_COMPANY->getAppCustomization()['event']['likes']) { 
        $myLikeStatus = Event::GetUserLikeStatus($topicid);
        $latestLikers = Event::GetLatestLikers($topicid);
        $totalLikers = Event::GetLikeTotals($topicid);
        $showAllLikers = true;
        $likeUnlikeMethod = 'EventTopic';
    } 
    include(__DIR__ . "/views/bookings/booking_detail_modal.template.php");
}

elseif (isset($_GET['getManageBookingConfigurationContainer']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    include(__DIR__ . "/views/bookings/manage_booking_configuration_container.template.php");
    exit();
}

elseif (isset($_GET['getManageBookingContainer']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $newbtn = '<div class="btn-group mr-3">
                    <button type="button" class="btn btn-primary " onclick=\'scheduleOtherSupportBookingForm("' . $_COMPANY->encodeId($groupid) . '")\'>
                        ' . gettext('Schedule Meeting'). '
                    </button>
                </div> ';
    include(__DIR__ . "/views/bookings/manage_booking_container.template.php");
    exit();
}

elseif (isset($_GET['getBookingsList'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (
        ($groupid = $_COMPANY->decodeId($_GET['getBookingsList'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $groupIds = array($groupid);
    if (!$groupid){
        $groupsUserCanManage = $_USER->getAllGroupsUserCanManage('allow_manage');
        $groupIds = array_column($groupsUserCanManage,'groupid');
    }  
    $groupIds = implode(',',$groupIds)?:0;
    $searchKeyword  = $_POST['search']['value'];
    $startLimit = $_POST['start'];
    $endLimit = $_POST['length'];
    $reloadData = $_POST['reloadData']??0;
    $orderFields = ['eventtitle','start','schedule_name'];
    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderBy = $orderFields[$orderIndex];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';

    [$totalrows, $events] = Event::GetEventsByUserSchedule($groupIds,  $startLimit, $endLimit, $searchKeyword, $reloadData, $orderBy, $orderDir);
   
    $final = [];
    foreach($events as $event){
        if (!$_USER->canManageContentInScopeCSV($event['groupid'])) {
            continue;
        }
        $encEventid = $_COMPANY->encodeId($event['eventid']);
        $tableRow = array();
        $eventObj = Event::GetEvent($event['eventid']);
        $eventJoiners = explode(',',Event::GetEventJoinersAsCSV($event['eventid']));
        $userWhoBooked = "";
        if (in_array($event['userid'],$eventJoiners)){
            $ub = User::GetUser($event['userid']);
            $userWhoBooked = $ub->getFullName().'<br>'. $ub->getEmailForDisplay();
        }
        $supportUser = '';
        if (in_array($event['supportUserId'],$eventJoiners)){
            $us = User::GetUser($event['supportUserId']);
            $supportUser = $us->getFullName().'<br>'. $us->getEmailForDisplay();
        }
        $status = $eventObj->getBookingStatus();
        $meetingLink = "";

        if ($status['resolution'] =='Scheduled'){
            $meetingLink = "<br> <span role='button' onclick=copyBookingLink('".$event['web_conference_link']."') class='btn-link'>[".gettext('Get Meeting Link')."]</span>";
        }

        $tableRow[] =  $userWhoBooked;					
        $tableRow[] = $db->covertUTCtoLocalAdvance("M j, Y g:i a","",  $event['start'],$_SESSION['timezone'],$_USER->val('language')).'-'.$db->covertUTCtoLocalAdvance("g:i a T", "",  $event['end'],$_SESSION['timezone'],$_USER->val('language')).$meetingLink;
        $tableRow[] = '<small>['.$event['groupname'].']</small><br>'.htmlspecialchars($event['schedule_name']);
        $tableRow[] = $supportUser;
        $note = $status['resolution'] !='Scheduled' ? '<br><span tabindex="0" class="btn-link" role="button" title="" data-html="true"  data-trigger="focus"  data-toggle="popover" data-placement="top" data-content="'.($status['comment'] ? $status['comment'] : 'No Comment available').'">['.gettext("View Notes").']</span>'  : '';
        $tableRow[] = ($status['resolution'] == 'noshow' ? 'No Show' : ucfirst($status['resolution'])).$note ;
        $actionButton = '<div class="" style="color: #fff; float: left;" >';
        $actionButton .= '<button aria-label="'.sprintf(gettext('%1$s Booking action dropdown'), htmlspecialchars($event['schedule_name'])).'" id="'.$encEventid.'" onclick="getBookingScheduleActionButton(\''.$encEventid.'\')" class="btn-no-style dropdown-toggle fa fa-ellipsis-v mt-3" title="Action" type="button" data-toggle="dropdown"></button>';
        $actionButton .= '<ul class="dropdown-menu dropdown-menu-right" id="dynamicBookingActionButton'.$encEventid.'" style="width: 250px; cursor: pointer;">';
        $actionButton .= '</ul>';
        $actionButton .= '</div>';
        $tableRow[]  = $actionButton;

        $final[] = array_merge(array("DT_RowId" => $event['eventid'],"DT_RowClass"=>'background'.$event['isactive']), array_values($tableRow));
    }
    $json_data = array(
                    "draw"=> intval( $_POST['draw'] ), 
                    "recordsTotal"    => intval($totalrows),
                    "recordsFiltered" => intval($totalrows), 
                    "data"            => $final
                );

    echo json_encode($json_data);
}

elseif (isset($_GET['manageBookingSetting']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $bookingBuffer = $group->getBookingStartBuffer();
    include(__DIR__ . "/views/bookings/booking_configuration_setting.template.php");
    exit();
}



elseif (isset($_GET['manageBookingEmailsSetting']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $emailTemplate = $group->getBookingEmailTemplate();
    include(__DIR__ . "/views/bookings/booking_email_template_setting.template.php");
    exit();
}

elseif (isset($_GET['saveBookingsBufferSetting'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$_USER->canManageContentInScopeCSV($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $days_before_start_to_allow_booking  = $_POST['days_before_start_to_allow_booking'];

    if ($days_before_start_to_allow_booking <0) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please input number of days 0 (zero) ore greater then 0 (zero)."), gettext('Error'));
    }

    $group->updateBookingStartBuffer($days_before_start_to_allow_booking);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Setting saved successfully."), gettext('Success'));
}

elseif (isset($_GET['saveBookingsEmailTemplate'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$_USER->canManageContentInScopeCSV($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $booking_email_subject  = $_POST['booking_email_subject'];
    $booking_message  = ViewHelper::RedactorContentValidateAndCleanup($_POST['booking_message']);

    // We can add blank $booking_email_subject AND $booking_message 
    // So following code commented out.
    // if (!$booking_email_subject) {
    //     AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please input a valid title."), gettext('Error'));
    // }
    // if (!$booking_message) {
    //     AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please input a valid description."), gettext('Error'));
    // }

    $group->updateBookingEmailTemplate($booking_email_subject, $booking_message);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Email template saved successfully."), gettext('Success'));
}

elseif (isset($_GET['getBookingScheduleActionButton']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<0 ||
        ($event = Event::GetEvent($eventid)) === null   ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $isAllowedToUpdateContent = $event->val('groupid') ? $_USER->canUpdateContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid'), $event->val('isactive')) : $event->loggedinUserCanUpdateEvent();
    $isAllowedToPublishContent = $event->val('groupid') ? $_USER->canPublishContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')) : $event->loggedinUserCanPublishEvent();
    $isAllowedToManageContent = $event->val('groupid') ? $_USER->canManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')) : $event->loggedinUserCanManageEvent();

    $status = $event->getBookingStatus();
    $resolution = $status['resolution'];

    include(__DIR__ . "/views/bookings/manage_booking_table_action_items.php");
    
}

elseif (isset($_GET['rescheduleBooking']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (
        ($event_booking_id = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($eventBooking = Event::GetEvent($event_booking_id)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $pageTitle = sprintf(gettext("Re-schedule Booking"));
   
    $timezone = (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
    $groupid = $eventBooking->val('groupid');
    $group = Group::GetGroup($groupid);


    $eventJoiners = explode(',',Event::GetEventJoinersAsCSV($event_booking_id));

    if (empty($eventJoiners)) {
        // Error
    }
    $bookingCreatorId  = $eventBooking->val('userid');
    $bookingCreator = User::GetUser($bookingCreatorId);
    if (empty($bookingCreator)) {
        AjaxResponse::SuccessAndExit_STRING(0, 0, gettext("The subject user is not available in the system anymore."), gettext("Error"));
    }
    $bookingSupportId = ($eventJoiners[0] == $bookingCreatorId) ? $eventJoiners[1] : $eventJoiners[0];
    $bookingSupport = User::GetUser($bookingSupportId);

    $availableDaysToSchedule = array();
    $supportUsers[] = User::GetUser($bookingSupportId);
    $availableDays = UserSchedule::GetUsersAvailableDaysToSchedule($bookingSupportId,true,'group_support', $groupid);
    if (!empty($availableDays)){
        $availableDaysToSchedule = array_merge($availableDaysToSchedule, $availableDays);
    }

    if ($_ZONE->val('app_type') == 'peoplehero' && $bookingCreator->val('employee_start_date') && !empty($availableDaysToSchedule)) {
        $employee_start_date = new DateTime($bookingCreator->val('employee_start_date'));
        $getBookingStartBuffer = $group->getBookingStartBuffer();
        
        if ($getBookingStartBuffer['days_before_start_to_allow_booking']>0){
            $employee_start_date->modify('-'.$getBookingStartBuffer['days_before_start_to_allow_booking'].' days');
        }
        $employee_start_date = $employee_start_date->format('Y-m-d');
        $filteredDates = array_filter($availableDaysToSchedule, function($date) use ($employee_start_date) {
            $dateObj = DateTime::createFromFormat('d-m-Y', $date);
            $slotDate = $dateObj->format('Y-m-d');
            return $slotDate <= $employee_start_date;
        });
        $availableDaysToSchedule = array_values($filteredDates);
    }

    $submitButton = gettext('Reschedule Booking');
    $section = 'manage';
    $emailTemplate = $group->getBookingEmailTemplate();

    $evtDateObj = Date::ConvertDatetimeTimezone($eventBooking->val('start'),'UTC',$timezone);
    $preSelectDate = $evtDateObj->format('Y-m-d');
    include(__DIR__ . "/views/bookings/new_booking_in_model.template.php");
}

elseif (isset($_GET['reassignBooking']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (
        ($event_booking_id = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($eventBooking = Event::GetEvent($event_booking_id)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $pageTitle = sprintf(gettext("Reassign Booking"));
   
    $timezone = (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
    $groupid = $eventBooking->val('groupid');
    $group = Group::GetGroup($groupid);

    $eventJoiners = explode(',',Event::GetEventJoinersAsCSV($event_booking_id));
    if (empty($eventJoiners)) {
        // Error
    }
    $bookingCreatorId  = $eventBooking->val('userid');
    $bookingCreator = User::GetUser($bookingCreatorId);
    $bookingSupportId = ($eventJoiners[0] == $bookingCreatorId) ? $eventJoiners[1] : $eventJoiners[0];
    $bookingSupport = User::GetUser($bookingSupportId);

    $groupSupportLeads = $group->getAllGroupLeadsBySupportType();
    $groupSupportLeadsUserIds = array_column($groupSupportLeads,'userid');
    $availableUserIds = UserSchedule::GetGroupSupportUserIdsWithSchedules($groupid);
    $finalAvailableUserids = array_unique(array_intersect( $availableUserIds,$groupSupportLeadsUserIds));
   
    $availableDaysToSchedule = array();
    $supportUsers = array();
    foreach($finalAvailableUserids as $uid) {
        if ($uid == $bookingCreatorId){
            continue;
        }
        $supportUsers[] = User::GetUser($uid);
        $availableDays = UserSchedule::GetUsersAvailableDaysToSchedule($uid,true,'group_support', $groupid);
        if (!empty($availableDays)){
            $availableDaysToSchedule = array_merge($availableDaysToSchedule, $availableDays);
        }
    }

    if (empty($bookingCreator)) {
        AjaxResponse::SuccessAndExit_STRING(0, 0, gettext("The subject user is not available in the system anymore."), gettext("Error"));
    }

    if ($_ZONE->val('app_type') == 'peoplehero' && $bookingCreator->val('employee_start_date') && !empty($availableDaysToSchedule)) {
        $employee_start_date = new DateTime($bookingCreator->val('employee_start_date'));
        $getBookingStartBuffer = $group->getBookingStartBuffer();
        
        if ($getBookingStartBuffer['days_before_start_to_allow_booking']>0){
            $employee_start_date->modify('-'.$getBookingStartBuffer['days_before_start_to_allow_booking'].' days');
        }
        $employee_start_date = $employee_start_date->format('Y-m-d');
        $filteredDates = array_filter($availableDaysToSchedule, function($date) use ($employee_start_date) {
            $dateObj = DateTime::createFromFormat('d-m-Y', $date);
            $slotDate = $dateObj->format('Y-m-d');
            return $slotDate <= $employee_start_date;
        });
        $availableDaysToSchedule = array_values($filteredDates);
    }

    $submitButton = gettext('Reassign Booking');
    $section = 'manage_reassign';
    $emailTemplate = $group->getBookingEmailTemplate();

    $evtDateObj = Date::ConvertDatetimeTimezone($eventBooking->val('start'),'UTC',$timezone);
    $preSelectDate = $evtDateObj->format('Y-m-d');
    include(__DIR__ . "/views/bookings/new_booking_in_model.template.php");
}

elseif (isset($_GET['searchUserForNewBooking']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $keyword = raw2clean($_GET['keyword']);

    $groupSupportLeads = $group->getAllGroupLeadsBySupportType();
    $groupSupportLeadsUserIds = array_column($groupSupportLeads,'userid');
    $availableUserIds = UserSchedule::GetGroupSupportUserIdsWithSchedules($groupid);
    $allSupportUsers = array_unique(array_intersect($availableUserIds,$groupSupportLeadsUserIds));
    $allSupportUsers = implode(',',$allSupportUsers);
    $searchAllUsersConditon = "";
    $excludeCondition = " userid NOT IN(".($allSupportUsers?:0).") ";

    $activeusers = User::SearchUsersByKeyword($keyword,$searchAllUsersConditon, $excludeCondition); // Note $excludeCondition is added as " AND ({$excludeCondition}) "
    if ($retrunjon){
        $finalData = [];
        foreach($activeusers as $usr){
            $finalData[] = array('userid'=>$_COMPANY->encodeId($usr['userid']),'username'=>(rtrim(($usr['firstname'] . " " . $usr['lastname']), " ") . " (" . $usr['email'] . ") - " . $usr['jobtitle']));
        }
        echo json_encode($finalData);
        exit();
    }
    $dropdown = '';
    if (count($activeusers) > 0) {
        $dropdown .= "<select class='form-control userdata' name='bookingCreatorId' onchange='closeDropdown()' id='user_search' required >";
        $dropdown .= "<option value=''>".gettext("Select a user (maximum of 20 matches are shown below)")." </option>";

        foreach ($activeusers as $activeuser) {
            $dropdown .= "<option value='" . $_COMPANY->encodeId($activeuser['userid']) . "'>" . rtrim(($activeuser['firstname'] . " " . $activeuser['lastname']), " ") . " (" . $activeuser['email'] . ") - " . $activeuser['jobtitle'] . "</option>";
        }
        $dropdown .= '</select>';
        $dropdown .= "<button type='button' class='btn btn-link' onclick=removeSelectedUser('0') >".gettext("Remove")."</button>";
    } else {
        $dropdown .= "<select class='form-control userdata' name='bookingCreatorId' id='user_search' required>";
        $dropdown .= "<option value=''>".gettext("No match found")."</option>";
        $dropdown .= "</select>";
    }
    echo $dropdown;
    exit();
}

else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}
