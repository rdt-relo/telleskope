<?php

define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__.'/head.php';

global $_COMPANY; /* @var Company $_COMPANY */
global $_USER;/* @var User $_USER */
global $_ZONE;
global $db;

###### All Ajax Calls For Events ##########
## OK
## Get All Group Events

if (isset($_GET['getEvent'])){

    $showGlobalChapterOnly = boolval($_SESSION['showGlobalChapterOnly'] ?? false);
    $showGlobalChannelOnly = boolval($_SESSION['showGlobalChannelOnly'] ?? false);
    $chapterid = 0;
    $channelid = 0;

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getEvent']))<0 || ($group = Group::GetGroup($groupid)) === null ||
        (isset($_GET['chapterid']) && ($chapterid = $_COMPANY->decodeId($_GET['chapterid'])) < 0) ||
        (isset($_GET['channelid']) && ($channelid = $_COMPANY->decodeId($_GET['channelid'])) < 0)
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $page = 1;
    $lastMonth = '';
    $limit = 10;
    $type = 1; // For New Events
    $newEventsOnly = $type===1;
    $pinnedEvents = array();
    $timezone = $_SESSION['timezone'] ?: 'UTC';

    $data = Event::GetGroupEventsViewData(Event::EVENT_CLASS['EVENT'], $groupid, $showGlobalChapterOnly, $chapterid, $showGlobalChannelOnly, $channelid, $page, $limit, $newEventsOnly, false, $timezone);

    if ($page==1 && $newEventsOnly){
        $pinnedEvents = Event::GetGroupEventsViewData(Event::EVENT_CLASS['EVENT'], $groupid, $showGlobalChapterOnly, $chapterid, $showGlobalChannelOnly, $channelid, $page, 100, $newEventsOnly, true, $timezone);
    }

    $max_iter = count($data);
    $show_more = ($max_iter > $limit);
    $max_iter = $show_more ? $limit : $max_iter;
    $section = "mainEvents";
    $volunteerTypes = Event::GetEventVolunteerTypesForCurrentZone(false);;
    // Note: The following template internally builds the page  using get_events_timeline template
	include(__DIR__ . "/views/templates/get_events.template.php");
	exit();
}

elseif (isset($_GET['getEventsTimeline'])){

    $showGlobalChapterOnly = boolval($_SESSION['showGlobalChapterOnly'] ?? false);
    $showGlobalChannelOnly = boolval($_SESSION['showGlobalChannelOnly'] ?? false);
    $chapterid = 0;
    $channelid = 0;

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getEventsTimeline']))<0 || ($group = Group::GetGroup($groupid)) === null ||
        (isset($_GET['chapterid']) && ($chapterid = $_COMPANY->decodeId($_GET['chapterid'])) < 0) ||
        (isset($_GET['channelid']) && ($channelid = $_COMPANY->decodeId($_GET['channelid'])) < 0)
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $page = 1;
    if (isset($_GET['page']) && (int)$_GET['page'] > 0) {
        $page = (int)$_GET['page'];
    }
    $lastMonth = $_GET['lastMonth'] ?? '';
    $limit = 10;
    $type = $_GET['type'] == 2 ? 2 : 1;
    $newEventsOnly = $type===1;
    $pinnedEvents = array();
    $filterByStartDateTime = '';
    $filterByEndDateTime = '';
    $filterByVolunteer = 0;
    if (!empty($_GET['by_start_date'])) {
        $filterByStartDateTime = (string)$_GET['by_start_date'].' 00:00:00';
    }
    if (!empty($_GET['by_end_date'])) {
        $filterByEndDateTime = (string)$_GET['by_end_date'].' 23:59:59';
        if ($newEventsOnly && empty($filterByStartDateTime)) {
            $filterByStartDateTime = $_USER->getLocalDateNow() .' 00:00:00';
        }
    }
    if (!empty($_GET['by_volunteer'])) {
        $filterByVolunteer = $_COMPANY->decodeId($_GET['by_volunteer']);
    }
    $timezone = $_SESSION['timezone'] ?: 'UTC';
    $data = Event::GetGroupEventsViewData(Event::EVENT_CLASS['EVENT'],$groupid, $showGlobalChapterOnly, $chapterid, $showGlobalChannelOnly, $channelid, $page, $limit, $newEventsOnly, false, $timezone, $filterByStartDateTime,  $filterByEndDateTime , $filterByVolunteer);

    if ($page == 1 && $newEventsOnly) {
        $pinnedEvents = Event::GetGroupEventsViewData(Event::EVENT_CLASS['EVENT'],$groupid, $showGlobalChapterOnly, $chapterid, $showGlobalChannelOnly, $channelid, $page, 100, $newEventsOnly, true, $timezone, $filterByStartDateTime,  $filterByEndDateTime , $filterByVolunteer);
    }

    $max_iter = count($data);
    $show_more = ($max_iter > $limit);
    $max_iter = $show_more ? $limit : $max_iter;
    $section = "mainEvents";

    if ($newEventsOnly) {
        $noDataMessage = gettext("There are no upcoming events scheduled at this time. Please try again later.");
    } else {
        $noDataMessage = gettext("There are no past events to display at this time. Please try again later.");
    }

    if ($page === 1) {
        // Note:
        // When fetching the first page, build the frame using get_events_timeline; internally it will call
        // get_events_timeline_rows
        include(__DIR__ . "/views/templates/get_events_timeline.template.php");
    } else {
        // For all other cases, i.e. to load the fragments; call events_timeline_rows
        include(__DIR__ . "/views/templates/get_events_timeline_rows.template.php");
    }
    exit();
}

## OK
## Join Event
elseif(isset($_GET['joinEvent']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['joinEvent']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        !isset($_POST['js'])
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($event->val('zoneid') != $_ZONE->id()) {
        $_ZONE = $_COMPANY->getZone($event->val('zoneid'));
        Logger::LogInfo("Switched zone from {$_ZONE->id()} to {$event->val('zoneid')}");
    }

    // Authorization Check
    if (!$_USER->canViewContent($event->val('groupid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	$joinstatus = (int)($_POST['js']); // joinstats: 1 for Accept, 2 for Tentative, 3 for Decline, 11 for in-person, 12 for waitlist, 21 for remote
    $sendrsvp = 1;

    $existingJoinStatus = $event->getMyRsvpStatus();
    $v = 1;
    if($existingJoinStatus != $joinstatus){
        $v =  $event->joinEvent($_USER->id(), $joinstatus, 1,1,0);   
    }

    if ($v == -3 || $v == -4) {
        if ($v == -3) {
            $msg = sprintf(gettext("This event is part of an event series with restricted attendance. You may only attend one event in this series. If you wish to join this event, you must first decline your RSVP for %s event in the series"), gettext('other'));
        } else {
            $msg = sprintf(gettext('You have already joined an event that has a schedule conflict with this event. If you wish to join this event, first update your RSVP for %s to decline to join.'), gettext('other'));
        }
        $eid = $event->val('event_series_id') ?: $event->id();
        AjaxResponse::SuccessAndExit_STRING(-100, 'eventview?id='.$_COMPANY->encodeId($eid), $msg, gettext('Error'));
    } else {

        $eventSurveyResponse = '';
        $trigger  = '';
        if (!empty($_POST['trigger']) && !empty($_POST['eventSurveyResponse'])){
            $eventSurveyResponse = $_POST['eventSurveyResponse'];
            $trigger = $_POST['trigger'];
        }
    
        if ( $trigger && $eventSurveyResponse){
            // RSVP is good, save the survey response.
            $survey_response = $event->updateEventSurveyResponse($_USER->id(),$trigger, $eventSurveyResponse);
            if ($survey_response < 0) {
                AjaxResponse::SuccessAndExit_STRING(-100, '', gettext('Unable to save your survey response at this time, please try again later'), gettext('Error'));
            }
        }
    }

    Points::HandleTrigger('EVENT_RSVP', [
        'eventId' => $event->id(),
        'userId' => $_USER->id(),
        'rsvpStatus' => $joinstatus,
        'existingJoinStatus' => $existingJoinStatus,
    ]);

    $updatedEventData = [
        'eid' => $_COMPANY->encodeId($event->id()),
        'cid' => $_COMPANY->encodeId(0),
        'chid' => $_COMPANY->encodeId(0),
    ];

    if ($v == 0 || $v == -1) {
        AjaxResponse::SuccessAndExit_STRING(0, $updatedEventData, gettext("Your request to update failed. Please check your connection and try again later."), gettext('Error'));

    } elseif ($v > 0) { // Change RSVP status realtime.
        $subjectEvent = $event; // Event RSVP template dependency
        $showRsvpSussessMessage = true;
        include(__DIR__.'/views/common/join_event_rsvp.template.php');

    } elseif ($v == -2) {
        AjaxResponse::SuccessAndExit_STRING(0, $updatedEventData, gettext("Your RSVP was processed, but our system was unable to send a confirmation email. Please check that the email address entered in your profile is correct, and try again. If the error persists, it may be due to the server not processing the email, and, if so, reach out to IT for support."), gettext('Warning!'));
    }
    exit();
}

## OK
## Create a new Event or Update and event
elseif(isset($_GET['createANewEvent']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    /**
     * ## If there are any database related changes for event, make sure to do same on "cloneEvent" method. ##
     */

     list(
        $event, $action, $eventid, $groupid, $chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $event_series_id, $custom_fields_input, $event_contact, $venue_info, $venue_room, $isprivate, $add_photo_disclaimer, $calendar_blocks, $content_replyto_email, $listids, $use_and_chapter_connector, $collaboratingGroupIds, $collaboratingGroupIdsPending, $collaborating_chapterids, $collaborating_chapterids_pending, $requestEmailApprovalGroups,$decodedGroupid,$disclaimerids,$rsvp_enabled, $event_contact_phone_number, $event_contributors
    ) = ViewHelper::ValidateEventFormInputs();


    if ($action === 'add') {

        // Authorization Check
        if (!$event_series_id && isset($_POST['event_scope']) && $_POST['event_scope'] == 'group' && !($_USER->canCreateContentInScopeCSV($decodedGroupid, $chapterids, $channelid))) {
            if ((empty($chapterids) && $_COMPANY->getAppCustomization()['chapter']['enabled']) || ($_COMPANY->getAppCustomization()['channel']['enabled'] &&  empty($channelid)) ) {
                $contextScope = $_COMPANY->getAppCustomization()['channel']['name-short'];
                if (empty($chapterids)){
                    $contextScope = $_COMPANY->getAppCustomization()['chapter']['name-short'];
                }
                AjaxResponse::SuccessAndExit_STRING(-3, '', sprintf(gettext("Please select a %s scope."),$contextScope), gettext('Error'));
            } else {
                header(HTTP_FORBIDDEN);
                exit();
            }
        }
        $eventid = Event::CreateNewEvent($groupid, $chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $event_series_id, $custom_fields_input, $event_contact, $venue_info, $venue_room, $isprivate, 'event', $add_photo_disclaimer, $calendar_blocks, $content_replyto_email, $listids, $use_and_chapter_connector, 0, $disclaimerids, $rsvp_enabled, $event_contact_phone_number, $event_contributors);

        if ($eventid) {
            $event = Event::GetEvent($eventid);

            $event->updateRsvpListSetting($_COMPANY->getAppCustomization()['event']['rsvp_display']['default_value'] ?? 2);

            if (!empty($_POST['ephemeral_topic_id'])) {
                $ephemeral_topic_id = $_COMPANY->decodeId($_POST['ephemeral_topic_id']);
                $ephemeral_topic = EphemeralTopic::GetEphemeralTopic($ephemeral_topic_id);

                $event->moveAttachmentsFrom($ephemeral_topic);
            }

            $event->updateCollaboratingGroupids($collaboratingGroupIds, $collaboratingGroupIdsPending, $collaborating_chapterids,$collaborating_chapterids_pending);

            AjaxResponse::SuccessAndExit_STRING(1, $_COMPANY->encodeId($event->id()), gettext("Event saved successfully"), gettext('Success'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong while creating event. Please try again."), gettext('Error'));
        }
    } else {
        $doWhat = intval($_POST['do_what'] ?? 1);
        // Authorization Check
        if ($groupid) {
            if (
                !$event_series_id &&
                !$_USER->canUpdateContentInScopeCSV($groupid,$chapterids,$channelid,$event->val('isactive')) &&
                ($event->isDraft()||$event->isUnderReview())
            ) { //Allow creators to edit unpublished content
                if ((empty($chapterids) && $_COMPANY->getAppCustomization()['chapter']['enabled']) || ($_COMPANY->getAppCustomization()['channel']['enabled'] && empty($channelid))) {
                    $contextScope = $_COMPANY->getAppCustomization()['channel']['name-short'];
                    if (empty($chapterids)){
                        $contextScope = $_COMPANY->getAppCustomization()['chapter']['name-short'];
                    }
                    AjaxResponse::SuccessAndExit_STRING(-3, '', sprintf(gettext("Please select a %s scope"),$contextScope), gettext('Error'));
                } else {
                    header(HTTP_FORBIDDEN);
                    exit();
                }
            }
        } elseif (!$event->loggedinUserCanUpdateEvent()) {
            header(HTTP_FORBIDDEN);
            exit();
        }

        $canUpdateCollaboratingEvent = true;
        if (!(($event->isDraft() || $event->isUnderReview() ) && ($event->loggedinUserCanUpdateEvent()) )) {
            // Only Admin can change collaboration `groups`and that too if the event is draft
            // Otherwise previous event values are used.
            $collaborate = $event->val('collaborating_groupids');
            $invited_groups = $event->val('invited_groups');
            $canUpdateCollaboratingEvent  = false;
        }

        $update  = $event->updateEvent($chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $custom_fields_input, $event_contact, $add_photo_disclaimer, $venue_info, $venue_room, $isprivate, $calendar_blocks, $content_replyto_email, $listids, $use_and_chapter_connector, $disclaimerids, $rsvp_enabled, $event_contact_phone_number, $event_contributors);
        $event = Event::GetEvent($event->id());

        if (!$update) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Unknown Error."), gettext('Error'));
        }
        if (!$event->val('form_validated')) {
            $event->updateFormValidatedStatus();
        }
        if ($update && $event->isPublished()) {
            $sendEmails = ($doWhat === 2);
            $sendUpdateTo	= $_POST['send_update_to'] ?? '1'; // 1 -> All RSVPed, 2 -> All invited; send_update_to is comman seperate string
            $sendUpdateToArr = explode(',',$sendUpdateTo);
            if (!in_array('1',$sendUpdateToArr) ) {
                $sendUpdateToArr[1] = '1';
            }
            
            $publish_where_integration = array();
            if (!empty($_POST['publish_where_integration'])){
                $publish_where_integration_input = $_POST['publish_where_integration'];
                foreach($publish_where_integration_input as $encExternalId){
                    $externalId = $_COMPANY->decodeId($encExternalId);

                    $externalIntegraions = array();
                    if ($event->val('collaborating_groupids') && $event->val('groupid') == 0) { // Collaborating event
                        $collaborating_groupids = explode(',',$event->val('collaborating_groupids'));
                        foreach($collaborating_groupids as $collaborating_groupid){
                            $integ =  GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($collaborating_groupid,$event->val('chapterid'),$event->val('channelid'),$externalId,true);
                            $externalIntegraions = array_merge( $externalIntegraions,$integ);
                        }
                        $externalIntegraions = array_unique($externalIntegraions, SORT_REGULAR);
                    } else { // Global Event or Group Event
                        $externalIntegraions = $event->isPrivateEvent() ? array() : GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($event->val('groupid'),$event->val('chapterid'),$event->val('channelid'),$externalId,true);
                    }
                    foreach($externalIntegraions as $externalIntegraion){
                        $publish_where_integration[] = $externalIntegraion->id();
                    }
                }
                
            }

            // Send updates only if requested, otherwise a silent publish is done.
            $job = new EventJob($groupid, $eventid);
            $job->saveAsBatchUpdateType($sendEmails,$sendUpdateToArr,$publish_where_integration);

            // Upladate publish_to_email value; only if we are sending emails to all should we update the flag.
            if ((in_array('2', $sendUpdateToArr)) && !$event->val('publish_to_email') ) {
                // This is the first time we are processing publish to email so update the flag
                $event->updatePublishToEmail(1);
            }

            if ((int)$event->val('max_inperson') && (int)$event->val('max_inperson') < $max_inperson) {
                // Confirm the seat for next person in the row.
                $event->processWaitlist(1);
            }
            if ((int)$event->val('max_online') && (int)$event->val('max_online') < $max_online) {
                // Confirm the seat for next person in the row.
                $event->processWaitlist(2);
            }
        }
        
        if ($canUpdateCollaboratingEvent){
            $event->updateCollaboratingGroupids($collaboratingGroupIds, $collaboratingGroupIdsPending, $collaborating_chapterids,$collaborating_chapterids_pending);
        }
        AjaxResponse::SuccessAndExit_STRING(1,'', gettext("Event updated successfully."), gettext('Success'));
    }
}

## OK
## Sort data for Calendar
elseif (isset($_GET['getGlobalCalendarEventsByFilters']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Note: If making changes here ensure similar changes are made in iframe/calendar.php as it has similar functionality
    $groupIds = null;

    $groupCategoryRows = Group::GetAllGroupCategoriesByZone($_ZONE, true, true);

    $groupIdsArray = array();
    if (!empty($_POST['groups'])){
        $groupIdsArray = $_COMPANY->decodeIdsInArray(Str::ConvertCSVToArray($_POST['groups']));
    }

    $zoneids = '';
    $zoneIdsArray = [$_ZONE->id()];
    if (!empty($_POST['zoneids'])) {
        $zoneids = $_COMPANY->decodeIdsInCSV($_POST['zoneids']);
        $zoneIdsArray = Str::ConvertCSVToArray($zoneids);
    }

    // If chapters are not enabled then use null to ignore the chapter match
    $chapterIdsArray = null;//array();
    if ($_COMPANY->getAppCustomization()['chapter']['enabled']){
        if(empty($_POST['chapterid'])) {
            $chapterIdsArray = array();
        } elseif ($_POST['chapterid'] == 'all') {
            $chapterIdsArray = null; // In this case set $chapterIdsArray to null to get all values.
        } else {
            $chapterIdsArray = $_COMPANY->decodeIdsInArray(Str::ConvertCSVToArray($_POST['chapterid']));
        }
    }

    // channel filter is currently not enabled on calendar, so set it to null
    $channelIdsArray = null;

    // If event types are not provided in the url, then set it to empty array
    $eventTypeArray = array();
    if (!empty($_POST['eventType'])){
        if ($_POST['eventType'] == 'all') {
            $eventTypeArray = null; // In this case set $eventTypeArray to null to get all values.
        } else {
            $eventTypeArray =  $_COMPANY->decodeIdsInArray(Str::ConvertCSVToArray($_POST['eventType']));
        }
    }

    $events = Event::FilterEvents2(
        $zoneIdsArray,
        $groupIdsArray,
        $chapterIdsArray,
        $channelIdsArray,
        $eventTypeArray,
        $_USER->isGroupleadOrGroupChapterleadOrGroupChannellead(-1)//If user is Admin of any group/chapter then show also all draft events in calendar.
    );

    $requestedCalendarView = $_POST['calendarDefaultView'] ?? '';
    $requestedCalendarDate = $_POST['calendarDefaultDate'] ?? '';

    $distance_radius = intval($_POST['distance_radius'] ?? 100); // If not set or empty assume it is 100
    $current_latitude = $_SESSION['current_latitude'] ?? '';
    $current_longitude = $_SESSION['current_longitude'] ?? '';
    include(__DIR__ . "/views/templates/calendar_sort.template.php");
}

## OK
## Delete Event
elseif(isset($_GET['deleteEvent'])){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Setting delete confirmation if it exists
    $confirmDelete = $_POST['confirmDelete'] ?? false;

    // Authorization Check canPublishOrManageContentInScopeCSV
    if (($event->isDraft()||$event->isUnderReview()) && !$event->loggedinUserCanUpdateOrPublishOrManageEvent()
    ) { //Allow creators to delete unpublished content
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($event->isPublished() && !$event->loggedinUserCanPublishEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }



    if ($event->isDraft() || $event->isUnderReview()){
        $eventDependencies  = $event->listEventDependencies();
        if (!empty($eventDependencies) && !$confirmDelete) {
            $eventDependencies = array_map(
                function ($item) {
                    if (stripos($item, 'expense entries') !== false)
                        return ucwords($item) . ' - ' . gettext(' will be reset');
                    else
                        return ucwords($item) . ' - ' . gettext(' will be removed');
                }, $eventDependencies
            );
            $eventDependenciesList = Arr::AsHtmlList($eventDependencies);
            // Check for expense entry
            $retMsg =  gettext('This event is linked to other items. Deleting it will trigger the actions shown below for each dependency.') . '<br><br>' . $eventDependenciesList . '<br><p>'. gettext('Type "I agree" to proceed'). '</p>';
            AjaxResponse::SuccessAndExit_STRING(2, '', $retMsg, gettext('Error'));
        }
    }

    $event_cancel_reason = $_POST['event_cancel_reason'] ?? '';
    $sendCancellationEmails = filter_var($_POST['sendCancellationEmails'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true; // Set default to true
    $actionText = ($event->isPublished())
                    ? gettext('Event cancelled successfully.')
                    : gettext('Event deleted successfully');

    if ($event->cancelEvent($event_cancel_reason, $sendCancellationEmails)) {
        AjaxResponse::SuccessAndExit_STRING(1, '', $actionText, gettext('Success'));
    }

    exit();
}

## OK
## Export Event Attendees to excel
elseif (isset($_GET['exportAttendeesToExcel'])){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['exportAttendeesToExcel']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanManageEvent() && !$event->isEventContributor()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportEventRSVP::GetDefaultReportRecForDownload();

    if ($event->isSeriesEventHead()) {
        $reportMeta['Filters']['event_series_id'] = $event->id();
    } else {
        $reportMeta['Filters']['eventid'] = $event->id();
    }

    $reportMeta['Filters']['groupid'] = $event->val('groupid');


    $options = array();

    if(!empty($_POST['startDate'])){
        $options['startDate'] = $db->covertLocaltoUTC("Y-m-d", $_POST['startDate'], $_SESSION['timezone']);
    }
    if(!empty($_POST['endDate'])){
        $options['endDate'] = $db->covertLocaltoUTC("Y-m-d", $_POST['endDate'], $_SESSION['timezone']);
    }

    if(isset($_POST['includeCustomFields'])){
        $options['includeCustomFields'] = $_POST['includeCustomFields'];
    }

    $reportMeta['Options'] = $options;

    // Lastly remove Admin fields
    unset($reportMeta['AdminFields']);

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'event_rsvp';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportEventRSVP ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}

## OK
## Event Members
elseif (isset($_GET['eventRSVPsForCheckIn'])){
    $enc_eventid = $_GET['eventRSVPsForCheckIn']; 
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['eventRSVPsForCheckIn']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $utc_tz						= new DateTimeZone('UTC');
    $local_tz					= new DateTimeZone($_SESSION['timezone']);
    $localStart					= (new DateTime($event->val('start'), $utc_tz))->setTimezone($local_tz);
    $localEnd		            = (new DateTime($event->val('end'), $utc_tz))->setTimezone($local_tz);
    $month             		    = $localStart->format('F');

    // Authorization Check
    $groupid = (int)$event->val('groupid');

    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent() && !$event->isEventContributor()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $section = '';
    if (isset($_GET['section'])){
        $section = !empty($_GET['section']) ? 'userend' : '';
    }

    $joinersCount = $event->getJoinersCount();
    $checkinCount = 0;
    $joinersCount = 0;
    $joinerStatuses = array(
        Event::RSVP_TYPE['RSVP_YES'],
        Event::RSVP_TYPE['RSVP_INPERSON_YES'],
        Event::RSVP_TYPE['RSVP_ONLINE_YES'],
        Event::RSVP_TYPE['RSVP_MAYBE'],
        Event::RSVP_TYPE['RSVP_INPERSON_WAIT'],
        Event::RSVP_TYPE['RSVP_ONLINE_WAIT']
    );

    $data = $event->getEventRSVPsList();
    foreach ($data as $item) {
        if (!empty($item['checkedin_date'])) {
            $checkinCount++;
        }
        if (in_array(intval($item['joinstatus']), $joinerStatuses)) {
            $joinersCount++;
        }
    }
	$headtitle	= $event->val('eventtitle')." Check In";
    $checkin = $event->hasCheckinStarted()?1:0;

    $disable_checkin = $checkin? '': 'disabled title="' . gettext('Unfortunately, the check in window for this event has closed. Reach out to the event coordinator for support.') . '"';
	$nonRsvpCheckinButtonLabel = gettext('Non RSVP Check In');
    $nonRsvpCheckinFormButtonLabel = gettext('Individual Check In');
    $nonRsvpCheckinImportButtonLabel = gettext('Import Check Ins');
    $enc_companyid = $_COMPANY->encodeId($eventid);
    $newbtn = <<< EOMEOM
	<div class='btn-group'>
        <button type='button' class='btn btn-primary dropdown-toggle' data-toggle='dropdown' aria-haspopup="true" aria-expanded="false" id="nonRSVPCheckinDropdownMenuLink" {$disable_checkin}>
        {$nonRsvpCheckinButtonLabel} ▾
        </button>
        <div class="dropdown-menu dropdown-menu-right" x-placement="bottom-end" aria-labelledby="nonRSVPCheckinDropdownMenuLink" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(-26px, 38px, 0px);">
            <a class="dropdown-item" href="javascript:showCheckInForm('{$enc_companyid}')">{$nonRsvpCheckinFormButtonLabel}</a>
            <a class="dropdown-item" href="javascript:importCSVForCheckIn('{$enc_companyid}','{$enc_eventid}')">{$nonRsvpCheckinImportButtonLabel}</a>                   
        </div>
    </div>
EOMEOM;

	$code    = "".$_COMPANY->getAppURL($_ZONE->val('app_type'))."ec2?e=".$_COMPANY->encodeId($eventid);
    if ($_COMPANY->getAppCustomization()['group']['qrcode']) {
    $qrcode  = "<button id='join' type='button' class='btn btn-primary' data-toggle='modal' data-target='#generate-qr-code'>".gettext('QR Code')."</button>";
    }

    $refresh   = "<button onclick=\"eventRSVPsForCheckIn('".$enc_eventid."','".$section."');\" class='btn btn-primary'>".gettext('Refresh')." &nbsp;<span style='color: white;' class='fa fa-sync'></span></button>";


	include(__DIR__ . "/views/templates/manage_section_dynamic_button.html");
	include(__DIR__ . "/views/templates/event_rsvps.template.php");
}

## OK
## Update RSVP users Check in status
elseif (isset($_GET['updateEventCheckIn']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['updateEventCheckIn'])) < 1 ||
        !isset($_POST['joineeid']) ||
        ($joineeid = $_COMPANY->decodeId($_POST['joineeid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        !isset($_POST['checkin'])
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent() && !$event->isEventContributor()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $checkinType = $_POST['checkin'] == 1 ? 'checkin' : 'checkout';


    if($event->toggleEventCheckinByJoinid($joineeid,$checkinType,$_USER->getFullName())){
        AjaxResponse::SuccessAndExit_STRING($checkinType == 'checkin' ? 0 : 1, '', gettext('Check-In'), '');
    } 

    AjaxResponse::SuccessAndExit_STRING(0,'', gettext('Something has gone wrong. Please try again later.'), gettext('Error'));
}

## OK
## Self checkin the user
elseif (isset($_GET['autoCheckinEvent']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid'])) < 1 ||
        ($event = Event::GetEventByCompany($_COMPANY, $eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    echo $event->selfCheckIn();
    exit();
}

## OK
## Show Event check in form for non RSVPs
elseif(isset($_GET['showCheckInForm'])){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['showCheckInForm'])) < 1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent() && !$event->isEventContributor()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	include(__DIR__ . "/views/templates/event_checkin_form.template.php");
}

## OK
## Show Event check in Modal for import csv file non RSVPs
elseif(isset($_GET['importCSVForCheckInModal'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['gid'])) < 0 ||
        ($eventid = $_COMPANY->decodeId($_GET['importCSVForCheckInModal'])) < 1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent() && !$event->isEventContributor()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	include(__DIR__ . "/views/templates/event_checkin_import_csv.template.php");
}

elseif (isset($_GET['submitImportCheckInsData']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($eventid = $_COMPANY->decodeId($_POST['eventid'])) < 1 ||       
        ($event = Event::GetEvent($eventid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $response = array();
    $invalid = '';
    
    if(!empty($_FILES['import_file']['name'])){
        $file =	basename($_FILES['import_file']['name']);
        $tmp =	$_FILES['import_file']['tmp_name'];
        $ext = substr(pathinfo($file)['extension'],0,4);
        // Allow certain file formats
        if($ext != "csv"  ) {
            $error =  gettext("Sorry, only .csv file format is allowed");
            $response = array('status'=>0,'title'=>gettext('Error'),'message'=>$error);
            echo json_encode($response);
            exit();
        }
        if (empty($response)) {           
            try {
                $csv = Csv::ParseFile($tmp);
            } catch (Exception $e) {
                $error = gettext('Error reading CSV file: file needs to be UTF-8 or ASCII encoded and must not contain <, > or & characters');
                $response = array('status'=>0,'title'=>gettext('Error'),'message'=>$error);
                echo json_encode($response);
                exit();
            }
            if (!empty($csv)) {
                $failed = array();
                $rowid = 0;
                    foreach($csv as $data){
                        $rowid++;
                        if (!isset($data['email'], $data['firstname'], $data['lastname'])) {
                            array_unshift($data, $rowid . ': Missing field(s)');
                            array_push($failed, $data);
                            continue;
                        }
                        if ($_COMPANY->isValidEmail($data['email'])){
                            $jid = $event->nonAuthenticatedCheckin($data['firstname'], $data['lastname'], $data['email'], $_USER->getFullName(), null);
                            if (!$jid) {
                                array_unshift($data,$rowid.': Unabled to checkin');
                                array_push($failed, $data);
                            }
                        } else {
                            array_unshift($data,$rowid.': Invalid Email');
						    array_push($failed, $data);
                        }

                    } //foerach end;
                
                    $dataArray = ['totalProcessed'=>count($csv),'totalSuccess'=>(count($csv) - count($failed)),'totalFailed'=>count($failed),'failed'=>$failed];
                    $response = array('status'=>1,'message'=>gettext('Data imported successfully'), 'data'=>$dataArray );

                } else {
                    $response = array('status'=>0,'title'=>gettext('Error'),'message'=>gettext('CSV file data issue'));
                }           
        }
    } else {
        $response = array('status'=>0,'title'=>gettext('Error'),'message'=>gettext('Please select a csv file'));
    }
    echo json_encode($response);
    exit();
}

## OK
## Submit event check in data for non RSVP ucser_error
elseif(isset($_GET['submitEventCheckinForm']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid'])) < 1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent() && !$event->isEventContributor()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (isset($_POST['userid']) && $_POST['userid']==""){
        AjaxResponse::SuccessAndExit_STRING(0, '',gettext('Please select a user to proceed!'), gettext('Error'));
    }

    $invalid = '';
    $uid = 0;
    if (!empty($_POST['userid'])){
        $uid  = $_COMPANY->decodeId($_POST['userid']);
        if (!$uid  && empty($_POST['search_users'])){
            $invalid .= ' search keyword,';
        }    
    }
    $adminname = $_USER->getFullName();

    $firstname = rtrim($_POST['firstname']);
    $lastname = rtrim($_POST['lastname']);
   
    
    if (empty($firstname)){
        $invalid .= ' Firstname,';
    }

    if (empty($lastname) && !$uid){
        $invalid .= ' Lastname,';
    }

    if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    }else{
        $invalid .= ' Email,';
    }

    
    if (!empty($invalid)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Error - missing or invalid %s'),rtrim($invalid, ',')), gettext('Error'));
    } 

    if ($uid ){
        // Data is valid, check if the user has already checked in;
        if ($event->getUserCheckinDate($uid)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s is already checked in."),$email), '');
        }
        if ($event->checkInByUserid($uid, $_USER->getFullName())) {
            AjaxResponse::SuccessAndExit_STRING(1, $_COMPANY->encodeId($eventid), sprintf(gettext("All set! %s is checked in."),htmlspecialchars($firstname)." ".htmlspecialchars($lastname)), gettext('Success'));
        }
    } else {
        if ($event->nonAuthenticatedCheckin($firstname, $lastname, $email, $_USER->getFullName(), null)) {
            AjaxResponse::SuccessAndExit_STRING(1, $_COMPANY->encodeId($eventid), sprintf(gettext("All set! %s is checked in."),htmlspecialchars($firstname)." ".htmlspecialchars($lastname)), gettext('Success'));
        }
    }

    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Error - Unknown error'), gettext('Error'));
}

## OK
## event invite
elseif(isset($_GET['inviteToEvent']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $retVal['returnCode'] = 0; //retVal.returnCode, retVal.errorMessage, retVal.successMessage
    $success_message = '';
    $selected_groupid = 0;
    $selected_chapterid = 0;
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        ((!empty($_POST['selected_groupid'])) && ($selected_groupid = $_COMPANY->decodeId($_POST['selected_groupid'])) < 1) ||
        (
        ((!empty($_POST['selected_groupid_for_chapter'])) && ($selected_groupid_for_chapter = $_COMPANY->decodeId($_POST['selected_groupid_for_chapter'])) < 1) ||
        ((!empty($_POST['selected_chapterid'])) && ($selected_chapterid = $_COMPANY->decodeId($_POST['selected_chapterid'])) < 1)
        )
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check - anyone with Publish function can invite by email
    // Only Group Admins (with Publish capability) can invite their groups

    if (
            (!empty($selected_groupid) && !$_USER->canPublishContentInGroup($selected_groupid))
        ||
            (!empty($_POST['withChapterId']) && empty($selected_chapterid) && !$_USER->canPublishContentInGroupSomething($event->val('groupid')))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

       

    $groupid        = $event->val('groupid');
    $email_in       = $_POST['email_in'];
    $invite_who     = $_POST['invite_who'];

    if ($invite_who == 'group_in' && ($selected_groupid || $selected_chapterid)) {
        if ($selected_chapterid){
            $shortName = $_COMPANY->getAppCustomization()['chapter']["name-short"];
            $invited_chapter = Group::GetChapterName($selected_chapterid,$selected_groupid_for_chapter);
            $invited_group_name = Group::GetGroupName($selected_groupid_for_chapter);
            $invited_group_chapter_name = $invited_chapter['chaptername'];
          
            $whoInvited = sprintf(gettext('%1$s invited all members of %2$s to this event'), $_USER->getFullName(), $invited_group_name . ' > ' . $invited_group_chapter_name. ' '.$shortName);

            if (in_array($selected_chapterid, explode(",",$event->val('invited_chapters')))) {
                // Chapter already invited
                $success_message .= sprintf(gettext('%s already invited'),$invited_group_chapter_name.' '. $shortName);
                AjaxResponse::SuccessAndExit_STRING(1, '', $success_message, gettext('Success'));
            } else {
                // Add chapter to the invitation list
                $new_chapterids = trim(($event->val('invited_chapters') . "," . $selected_chapterid), ','); //Note ordering of group invitations is important to avoid sending duplicate notifications
                $notifyUsers = Group::GetDeltaChapterMembersAsCSV($event->val('invited_chapters'), $selected_chapterid, '', 'E');
                $event->inviteChaptersToEvent($new_chapterids);
            }

        } elseif ($selected_groupid) {
            $shortName = $_COMPANY->getAppCustomization()['group']["name-short"];
            $invited_group_chapter_name = Group::GetGroupName($selected_groupid);

            $whoInvited = sprintf(gettext('%1$s invited all members of %2$s to this event'), $_USER->getFullName(), $invited_group_chapter_name. ' '. $shortName);
           
            if (in_array($selected_groupid, explode(",",$event->val('invited_groups')))) {
                // Group already invited
                $success_message .= sprintf(gettext('%s already invited'),$invited_group_chapter_name.' '. $shortName);
                AjaxResponse::SuccessAndExit_STRING(1, '', $success_message, gettext('Success'));
            } else {
                // Add group to the invitation list
                $new_invited_groupids = trim(($event->val('invited_groups') . "," . $selected_groupid), ','); //Note ordering of group invitations is important to avoid sending duplicate notifications
                $notifyUsers = Group::GetDeltaGroupMembersAsCSV($new_invited_groupids, $selected_groupid, '', 'E', $event->val('use_and_chapter_connector'));
                
                $event->inviteGroupsToEvent($new_invited_groupids);
            }
        }

        $job = new EventJob($groupid,$eventid);
        $job->saveAsInviteType($notifyUsers, $whoInvited);
       
        $success_message .= $invited_group_chapter_name.' '. $shortName.' '.gettext('invited');
        AjaxResponse::SuccessAndExit_STRING(1, '', $success_message, gettext('Success'));

    } else {
        if ($event->val('chapterid')!='0'){
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("Please  select a %s to invite"),$_COMPANY->getAppCustomization()['chapter']["name-short"]), gettext('Error'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("Please  select a %s to invite"),$_COMPANY->getAppCustomization()['group']["name-short"]), gettext('Error'));
        }
    }
    AjaxResponse::SuccessAndExit_STRING(1, '', $success_message, gettext('Success'));
}

elseif(isset($_GET['inviteByEmailToEvent']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $success_message = '';
    $selected_groupid = 0;
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        ((!empty($_POST['email'])) && ($email = trim($_POST['email'])) == '')
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canPublishContentInCompanySomething()) {
        header(HTTP_FORBIDDEN);
        exit();
    }


	$groupid        = $event->val('groupid');
    $whoInvited     = $_USER->getFullName(). ' invited you to this event';
    $alreadyMemberEmails = array();
    $invalidEmails = array(); 

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        array_push($invalidEmails, $email);
    } elseif (!$_COMPANY->isValidEmail($email)) {
        array_push($invalidEmails, $email);
    } 

    if (count($invalidEmails)) {
        echo 0;
        exit();
    }

    $person = User::GetUserByEmail($email);

    if ($person &&
        ($invited_user_id = $person->id()) > 0) {
        // check if user already in eventjoiners
        $eventJoinStatus = $event->getUserRsvpStatus($invited_user_id);

        // check if user member of invited groups
        $invitedGroupMemberCount = 0;
        if ($event->val('invited_groups')){
            $m = Group::GetAnyUserGroupMembershipByGroupidCSV($invited_user_id,$event->val('invited_groups'));
            $invitedGroupMemberCount = count($m);
        }

        if (($eventJoinStatus > 0 || ($event->isPublishedToEmail() && $invitedGroupMemberCount > 0))) { // Groupmembers matter only if event published to email
            $job = new EventJob($groupid, $eventid);
            $job->inviteByEmails($email,false,$whoInvited,'Invitation:');
            echo 2;
            exit();
        } else {

            $event->inviteUserToEvent($invited_user_id);
            $job = new EventJob($groupid, $eventid);
            $job->inviteByEmails($email,false,$whoInvited,'Invitation:');
            echo 1;
            exit();
        }
    } else {
        // User by email does not exist, just invite user by email address directly
        $job = new EventJob($groupid, $eventid);
        $job->inviteByEmails($email,false,$whoInvited,'Invitation:', false);
        echo 1;
        exit();
    }
}

## OK
## Form to create a reminder for the event
elseif(isset($_GET['sendReminderForm'])){

    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!(
            $_USER->canPublishOrManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'),$event->val('channelid')) ||
            $event->loggedinUserCanManageEvent() ||
            $event->loggedinUserCanPublishEvent() ||
            ($event->val('teamid') && $_USER->isProgramTeamMember($event->val('teamid')))
    )) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($event->isSeriesEventHead()) {
        $sub_events = Event::GetEventsInSeries($event->id());
        $events_attendence_type = array();
        $events_max_online = 0;
        $events_max_inperson = 0;
        $events_rsvp_yes_no = 0;

        foreach ($sub_events as $sub_event) {
            $events_attendence_type[] = (int)$sub_event->val('event_attendence_type');
            $events_max_online += (int)$sub_event->val('max_online');
            $events_max_inperson += (int)$sub_event->val('max_inperson');
            $events_rsvp_yes_no +=  (int)($sub_event->val('max_online') == 0 && $sub_event->val('max_inperson') == 0);
        }

    } else {
        $events_attendence_type = array( (int)$event->val('event_attendence_type'));
        $events_max_online = (int)$event->val('max_online');
        $events_max_inperson = (int)$event->val('max_inperson');
        $events_rsvp_yes_no = (int)($events_max_online == 0 && $events_max_inperson == 0);
    }

    $preEventSurveyLink = '';
    if ($_COMPANY->getAppCustomization()['event']['enable_event_surveys']){
        $surveyBaseURL = $_COMPANY->getAppURL($_ZONE->val('app_type')).'eventview?id='.$_COMPANY->encodeId($eventid);
        if($event->getEventSurveyResponsesByTrigger(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT'],true)){
            $preEventSurveyLink = $surveyBaseURL.'&survey_key='.base64_url_encode(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT']);
        }
    }
    
    $userPreEventSurveyResponses = $event->getEventSurveyResponsesByTrigger(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT'],true);


    include(__DIR__ . "/views/templates/send_reminder.template.php");
}

## OK
## Form to create a reminder for the event
elseif(isset($_GET['viewReminderHistory'])){

    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!($_USER->canPublishOrManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'),$event->val('channelid')) ||
        $event->loggedinUserCanManageEvent() ||
        $event->loggedinUserCanPublishEvent() ||
        ($event->val('teamid') && $_USER->isProgramTeamMember($event->val('teamid')))
    )) {
        header(HTTP_FORBIDDEN);
        exit();
    }

   $reminderArray =  $event->getEventReminderHistory();
   
    include(__DIR__ . "/views/templates/view_reminder_history.template.php");
}


## OK
## delete reminder history
elseif(isset($_GET['deleteReminderHistory'])){

    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        ($reminderid = $_COMPANY->decodeId($_GET['reminderid']))<1 
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!($_USER->canPublishOrManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'),$event->val('channelid')) ||
        $event->loggedinUserCanManageEvent() ||
        $event->loggedinUserCanPublishEvent() ||
        ($event->val('teamid') && $_USER->isProgramTeamMember($event->val('teamid')))
    )) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $deleteReminder =  $event->deleteReminderHistory($reminderid);
    if($deleteReminder){
        $job = new EventJob($event->val('groupid'), $eventid);
        $job->cancelAsRemindType();
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Event reminder deleted successfully."), gettext('Success'));
    }else{
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
    }
   
}


## OK
## Submit reminder for the event
elseif(isset($_GET['sendReminderEmail']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if ((!isset($_POST['eventid'])) ||
        ($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (Str::IsEmptyHTML($_POST['message'])) {
               $_POST['message'] = '';
    }

    // Start of Basic Data Validation
    $validator = new Rakit\Validation\Validator;

    $validation = $validator->validate($_POST, [
        'reminderTo'   => 'required',
        'subject'  => 'required|min:3|max:255', //Alpha numeric with spaces and . _ -
        'message'  => 'required|min:3|max:8000'
    ]);

    if ($validation->fails()) {
        // handling errors
        $errors = array_values($validation->errors()->firstOfAll());
        // Return error message
        
        $e = implode(', ', $errors);
        AjaxResponse::SuccessAndExit_STRING(0, '', $e, gettext('Error'));
    }

    // Authorization Check
    if (!(
            $_USER->canPublishOrManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'),$event->val('channelid')) ||
            $event->loggedinUserCanManageEvent() ||
            $event->loggedinUserCanPublishEvent() ||
            ($event->val('teamid') && $_USER->isProgramTeamMember($event->val('teamid')))
    )) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	$groupid	= $event->val('groupid');
    // Do not use raw2clean for the following two paramters as it interferes with JSON construction.
	$subject	= htmlspecialchars($_POST['subject']);
	$reminderMessage	= ViewHelper::RedactorContentValidateAndCleanup($_POST['message']);
    $reminderTo	= array_map('intval', $_POST['reminderTo']) ?? array('1'); // All RSVPed options dynamically, (-1) -> All invited
    $futureEventsOnly = intval($_POST['future_events_only'] ?? 0);
    $includeEventDetails = intval($_POST['includeEventDetails'] ?? 0);
    $delay = 0;
    $reminder_date = '';
    if (!empty($_POST['send_reminder_when']) && $_POST['send_reminder_when'] === 'scheduled') {
        $reminder_date_format = $_POST['send_reminder_Ymd']." ".$_POST['send_reminder_h'].":".$_POST['send_reminder_i']." ".$_POST['send_reminder_A'];
        $timezone = isset($_POST['timezone']) ? $_POST['timezone'] : (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
        $reminder_date = $db->covertLocaltoUTC("Y-m-d H:i:s", $reminder_date_format, $timezone);

        $diff_bw_reminder_date_and_end_date = strtotime($event->val('end').' UTC') - strtotime($reminder_date . ' UTC');
        if ($diff_bw_reminder_date_and_end_date < 60) { // Send remider upto 1 minute before event expire
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('A reminder can be scheduled up to 1 minute before the event\'s end time.'), gettext('Error'));
        }
        $delay = strtotime($reminder_date . ' UTC') - time();
    }


    $job = new EventJob($groupid,$eventid);
    if ($delay>0){
        $job->delay = $delay;
    }
	$status =  $job->saveAsBatchRemindType($subject,$reminderMessage,$includeEventDetails,$reminderTo,$futureEventsOnly);
    if ($status == 1){  
        $event->saveEventReminderHistory($subject,$reminderMessage,$includeEventDetails,$reminderTo,$delay);
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Reminder email will be sent out shortly."), gettext('Success'));
    } elseif ($status == -1){
        AjaxResponse::SuccessAndExit_STRING(2, '', gettext("No user emails matching the selected 'Remind To' list were found."), gettext('Error'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please check your connection, refresh the page, and try again."), gettext('Error'));
    }
}

## Submit reminder review for the event
elseif(isset($_GET['sendReminderEmailReview']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if ((!isset($_POST['eventid'])) ||
        ($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (Str::IsEmptyHTML($_POST['message'])) {
        $_POST['message'] = '';
    }

    // Start of Basic Data Validation
    $validator = new Rakit\Validation\Validator;

    $validation = $validator->validate($_POST, [
        'reminderTo'   => 'required',
        'subject'  => 'required|min:3|max:255', //Alpha numeric with spaces and . _ -
        'message'  => 'required|min:3|max:8000'
    ]);

    if ($validation->fails()) {
        // handling errors
        $errors = array_values($validation->errors()->firstOfAll());
        // Return error message

        $e = implode(', ', $errors);
        AjaxResponse::SuccessAndExit_STRING(0, '', $e, gettext('Error'));
    }

    // Authorization Check
    if (!(
            $_USER->canPublishOrManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'),$event->val('channelid')) ||
            $event->loggedinUserCanManageEvent() ||
            $event->loggedinUserCanPublishEvent() ||
            ($event->val('teamid') && $_USER->isProgramTeamMember($event->val('teamid')))
    )) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	$groupid	= $event->val('groupid');
    // Do not use raw2clean for the following two paramters as it interferes with JSON construction.
	$subject	= 'Review: '. htmlspecialchars($_POST['subject']);
	$reminderMessage	= $_POST['message'];
    $reminderTo	= array(); // keep it EMPTY
    $futureEventsOnly = intval($_POST['future_events_only'] ?? 0);
    $includeEventDetails = intval($_POST['includeEventDetails'] ?? 0);
    $reviewerUserIds = ''.$_USER->id();
    $job = new EventJob($groupid,$eventid);
    $status =  $job->saveAsBatchRemindType($subject,$reminderMessage,$includeEventDetails,$reminderTo,$futureEventsOnly,$reviewerUserIds);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Reminder review email will be sent out shortly."), gettext('Success'));

}//end reminder review.

elseif (isset($_GET['openEventReviewModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
	$enc_groupid = $_GET['groupid'];
	$enc_eventid = $_GET['eventid'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($enc_groupid))<0 ||
        ($eventid = $_COMPANY->decodeId($enc_eventid)) < 1 ||
        ($event = Event::GetEvent($eventid)) === NULL) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if ($groupid) {
        if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } elseif (!$event->loggedinUserCanUpdateEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $grpid = $event->val('groupid').','.($event->val('collaborating_groupids') ?? '0');

    $reviewers = User::GetReviewersByScope($grpid, $event->val('chapterid'), $event->val('channelid'));

    $chapterid = $event->val('chapterid');
    $channelid = $event->val('channelid');
    $chapterleads = array();
    $channelleads = array();

    // The template needs following inputs in addition to $reviewers set above
    $template_review_what = gettext('Event');
    $enc_groupid = $_COMPANY->encodeId($groupid);
    $enc_objectid = $_COMPANY->encodeId($eventid);
    $template_email_review_function = 'sendEventForReview';
    
    include(__DIR__ . "/views/templates/general_email_review_modal.template.php");
	
}

elseif (isset($_GET['sendEventForReview']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0    ||
        !isset($_POST['objectid']) ||
        ($eventid = $_COMPANY->decodeId($_POST['objectid']))<1 || ($event = Event::GetEvent($eventid)) === NULL) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if ($groupid) {
        if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } elseif (!$event->loggedinUserCanUpdateEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $validEmails = [];
    $invalidEmails = [];

    $e_arr = explode(',',str_replace(';',',',$_POST['emails']));
    foreach ($e_arr as $e) {
        $e = trim($e);
        if (empty($e)){
            continue;
        }
        if (!filter_var($e, FILTER_VALIDATE_EMAIL)){
            array_push($invalidEmails,$e);
        } elseif (!$_COMPANY->isValidEmail($e)) {
            array_push($invalidEmails,$e);
        } else {
            array_push($validEmails,$e);
        }
    }

    if (count($invalidEmails)){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Invalid emails: %s'),implode(', ',$invalidEmails)), gettext('Error'));
    }

    if (count($validEmails) > 10){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Too many emails. %s emails entered (maximum allowed is 10)'),count($validEmails)), gettext('Error'));
    }

    $reviewers = $_POST['reviewers'] ?? [];
    $reviewers[] = $_USER->val('email'); // Add the current user to review list as well.

    $allreviewers = implode(',',array_unique (array_merge ($validEmails , $reviewers)));
  
    if (!empty($allreviewers)){
        $review_note = raw2clean($_POST['review_note']);
        $job = new EventJob($groupid, $eventid);
        $job->sendForReview($allreviewers,$review_note);
        // Update event under review status
        $event->updateEventUnderReview();
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Event emailed for review'), gettext('Success'));
	} else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please add some reviewers'), gettext('Error'));
	}
}

elseif (isset($_GET['getEventScheduleModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
	$enc_groupid = $_GET['groupid'];
    $enc_eventid = $_GET['eventid'];

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($enc_groupid))<0 ||
        ($eventid = $_COMPANY->decodeId($enc_eventid)) < 1 ||
        ($event = Event::GetEvent($eventid)) === NULL) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
   
    // Authorization Check
    if ($groupid) {
        if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } elseif (!$event->loggedinUserCanUpdateEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (
        $_COMPANY->getAppCustomization()['event']['require_email_review_before_publish'] &&
        $event->isDraft()
    ) {

        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('This event is not yet reviewed, please choose Email Review to review the event.'), gettext('Error!'));

    } elseif (
        $_COMPANY->getAppCustomization()['event']['approvals']['enabled'] &&
        (($approval = $event->getApprovalObject()) == null || $approval->isApprovalStatusApproved() !="APPROVED")
    ) {

        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('This event is not yet approved, please Request Approval or View Approval Status.'), gettext('Error!'));

    } elseif (!empty($event->val('collaborating_groupids_pending'))){

        $pendingCollaboratingRequests  = $event->getPendingCollaboratingRequestsWith();        
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('This is a collaborative event and there are pending collaboration requests with %1$s %2$s. Please resolve or remove the pending requests first.'),implode(",",array_column($pendingCollaboratingRequests,'groupname')),count($pendingCollaboratingRequests) > 1 ? $_COMPANY->getAppCustomization()['group']["name-short-plural"] :  $_COMPANY->getAppCustomization()['group']["name-short"]), gettext('Error!'));

    } elseif( !empty($event->val('collaborating_chapterids_pending'))) {
        $pendingCollaboratingRequests = Group::GetChapterNamesByChapteridsCsv($event->val('collaborating_chapterids_pending'));
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('This is a collaborative event and there are pending collaboration requests with %1$s %2$s. Please resolve or remove the pending requests first.'),implode(",",array_column($pendingCollaboratingRequests,'chaptername')),count($pendingCollaboratingRequests) > 1 ? $_COMPANY->getAppCustomization()['chapter']["name-short-plural"] :  $_COMPANY->getAppCustomization()['chapter']["name-short"]), gettext('Error!'));
    

//    } elseif (!$event->isEventBudgetApproved()) {
//
//        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("This event has a budget entry which is not approved. Please request budget approval first. You can request budget approval by choosing \'Manage Event Budget\' from the Event options and then clicking on \'Request Approval\'"), gettext('Error!'));

    } elseif (!$event->isSeriesEventHead() && !$event->areEventSpeakersApproved()) {

        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("This event has one or more speakers who are not approved. Please request event speaker approval first. You can request event speaker approval by choosing Manage Event Speakers from the Event options and then clicking on Request Approval for each speaker who is not yet approved."), gettext('Error!'));

    } elseif ($event->isSeriesEventHead()) {
        if (!$event->isSeriesEventsFormValidated()) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('This event series has an event with missing information. Please complete the event creation process.'), gettext('Error!'));
        } else {
            $seriesEvents = Event::GetEventsInSeries($event->id());
            foreach($seriesEvents as $sevent ) {
                if (!$sevent->areEventSpeakersApproved()){
                    AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("This event series event '%s' has one or more speakers who are not approved. Please request event speaker approval first. You can request event speaker approval by choosing Manage Event Speakers from the Event options and then clicking on Request Approval for each speaker who is not yet approved."),$sevent->val('eventtitle')), gettext('Error!'));
                }
            }
        }
    }
    $hidePlatformPublish = false;
    $integrations = array();
    if ($event->val('collaborating_groupids') && $event->val('groupid') == 0) { // Collaborating event
        $collaborating_groupids = explode(',',$event->val('collaborating_groupids'));
        foreach($collaborating_groupids as $collaborating_groupid){
            $integ =  $event->isPrivateEvent() ? array() : GroupIntegration::GetUniqueGroupIntegrationsByExternalType($collaborating_groupid,$event->val('chapterid'), $event->val('channelid'),array(),'events');
            $integrations = array_merge( $integrations,$integ);
        }
        $integrations = array_unique($integrations, SORT_REGULAR);
    } elseif (!empty($event->val('listids'))){
        $hidePlatformPublish = true;
    } else { // Global Event or Group Event
        $integrations =  $event->isPrivateEvent() ? array() : GroupIntegration::GetUniqueGroupIntegrationsByExternalType($groupid,$event->val('chapterid'), $event->val('channelid'),array(),'events');
    }
    // The following three parameters are needed by the publishing template.
    $enc_groupid = $_COMPANY->encodeId($groupid);
    $enc_objectid = $_COMPANY->encodeId($eventid);
    $template_publish_what = 'Event';
    $template_publish_js_method = 'saveScheduleEventPublishing';

    if($event->isPrivateEvent()){
        $checked = '';
        $disclaimer = gettext('This event is marked as private and will not be displayed on this platform or the calendar. By default, no emails will be sent out unless the Email check box is checked below. After the event is published, you can use the Invite Users functionality to invite additional users.');
        if ($event->val('listids')) {
            $checked = 'checked';
        }
    }

    if (strtotime($event->val('start') . ' UTC') < time()){
        $hideEmailAndExternalPublishing = true;
        $disclaimer = gettext('This event\'s start time is in the past.');
    }

    $email_subtext =
        gettext('Deselecting "Email" means invitations won\'t be sent automatically via email thus reducing event visibility and participation. ')
        . gettext('To send emails later, Edit the event and choose "Email to all members" option on "Publish Update" screen. ')
        . gettext('Alternatively, use the shareable link to invite directly.');

    $pre_select_publish_to_email = $_COMPANY->getAppCustomization()['event']['publish']['preselect_email_publish'] ?? true;
    $hideEmailPublish = $_COMPANY->getAppCustomization()['event']['disable_email_publish']?? false;
    include(__DIR__ . "/views/templates/general_schedule_publish.template.php");
    exit();
}

elseif (isset($_GET['saveScheduleEventPublishing']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        !isset($_POST['objectid']) ||
        ($eventid = $_COMPANY->decodeId($_POST['objectid']))<1 ||
        (null === ($event = Event::GetEvent($eventid))) ||
        ($groupid != $event->val('groupid'))) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if ($groupid) {
        if (
            !$_USER->canPublishContentInScopeCSV($event->val('groupid'), $event->val('chapterid'),  $event->val('channelid'))
        ) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } elseif (!$event->loggedinUserCanPublishEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (((int)$event->val('event_attendence_type') == 2 || (int)$event->val('event_attendence_type') == 3)
        && !filter_var($event->val('web_conference_link'), FILTER_VALIDATE_URL) ) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('The Web Conference Link is invalid. Please update the Link and try again.'), gettext('Error'));
    }



    // If 'publish_where' === 'online', then change isactive=1, publishdate=now(), do not process other fields
    // If 'publish_where' === 'online & email' then change isactive=3, .... find out the value for publish date
    //      If 'publish_when' === 'now', then  publishdate=now()
    //      Else publishdate == calculated value

    $isactive = Event::STATUS_AWAITING;
    $delay = 0;
    $sendEmails = 0;
    $publish_date = '';
    if (!empty($_POST['publish_where_email'])){
        $sendEmails = 1;
    }

    if (!empty($_POST['publish_when']) && $_POST['publish_when'] === 'scheduled') {
        $publish_date_format = $_POST['publish_Ymd']." ".$_POST['publish_h'].":".$_POST['publish_i']." ".$_POST['publish_A'];
        $timezone = isset($_POST['timezone']) ? $_POST['timezone'] : (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
        $publish_date = $db->covertLocaltoUTC("Y-m-d H:i:s", $publish_date_format, $timezone);

        $diff_bw_publish_date_and_start_date = strtotime($event->val('start').' UTC') - strtotime($publish_date . ' UTC');
        if ($diff_bw_publish_date_and_start_date < 3600) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Event can be scheduled to publish upto 1 hour before the event start time'), gettext('Error'));
        }

        $delay = strtotime($publish_date . ' UTC') - time();
        if ($delay > 2592000 || $delay < -300) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Event can be scheduled to publish up to 30 days in the future'), gettext('Error'));
        }
        if ($delay < 0){
            $delay = 15;
        }
    }
    
    $publish_where_integration = array();
    if (!empty($_POST['publish_where_integration'])){
        $publish_where_integration_input = $_POST['publish_where_integration'];
        foreach($publish_where_integration_input as $encIntegrationid){
            $externalId = $_COMPANY->decodeId($encIntegrationid);

            $externalIntegraions = array();
            if ($event->val('collaborating_groupids') && $event->val('groupid') == 0) { // Collaborating event
                $collaborating_groupids = explode(',',$event->val('collaborating_groupids'));
                foreach($collaborating_groupids as $collaborating_groupid){
                    $integ =  GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($collaborating_groupid,$event->val('chapterid'),$event->val('channelid'),$externalId,true);
                    $externalIntegraions = array_merge( $externalIntegraions,$integ);
                }
            } else {
                $externalIntegraions = GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($event->val('groupid'),$event->val('chapterid'),$event->val('channelid'),$externalId,true);
            }
          
            foreach($externalIntegraions as $externalIntegraion){
                array_push($publish_where_integration,$externalIntegraion->id());
            }
        }
    }
   
	$update_code = $event->updateEventForSchedulePublishing($delay);
    if ($event->isSeriesEventHead()) {
        $update_code = $event->updateEventSeriesForSchedulePublishing($delay);
    }

    if ($update_code) {
        $job = new EventJob($groupid, $eventid);
        if (!empty($publish_date)){
            $job->delay = $delay;
        }
        $job->saveAsBatchCreateType($sendEmails,$publish_where_integration);
    }
    AjaxResponse::SuccessAndExit_STRING(2, '', gettext('Event scheduled for publishing'), gettext('Success'));
}

elseif (isset($_GET['cancelEventPublishing']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0||
        !isset($_POST['eventid']) ||
        ($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if ($groupid) {
        if (
            !$_USER->canPublishContentInScopeCSV($event->val('groupid'), $event->val('chapterid'),  $event->val('channelid'))
        ) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } elseif (!$event->loggedinUserCanPublishEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $status_draft = Event::STATUS_DRAFT;
    $status_awaiting = Event::STATUS_AWAITING;

    $update_code = $event->cancelEventForSchedulePublishing();
    if ($event->isSeriesEventHead()) {
        $update_code = $event->cancelEventSeriesForSchedulePublishing();
    }
    if ($update_code > 0) {
            $job = new EventJob($groupid, $eventid);
            $job->cancelAllPendingJobs();
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Event publishing canceled successfully.'), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Unable to cancel event publishing'), gettext('Error'));
}

elseif (isset($_GET['loadViewEventRSVPsModal'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['loadViewEventRSVPsModal']))<0
        || ($eventid = $_COMPANY->decodeId($_GET['eventid']))<1
        || ($event = Event::GetEvent($eventid)) === NULL) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);
    $encEventId = $_COMPANY->encodeId($eventid);

    // Authorization Check
    // Not needed, if the group can be loaded (above) then the content can be viewed.
    $status = "1,2,11,12,21,22";
    $usersList = $event->getEventRSVPsList( $status,0,1000);
    $modalTitle = sprintf(gettext("%s event RSVP's"),$event->val('eventtitle'));
    /**
     * get_users_basic_info_list.template.php Dependency
     * $usersList
     */
    include(__DIR__ . "/views/templates/get_users_basic_info_list.template.php");
}

elseif (isset($_GET['loadMoreRsvps'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['loadMoreRsvps']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);
    $encEventId = $_COMPANY->encodeId($eventid);
    $page = (int)$_GET['page'];
    $start 		=	($page)*60;
    $status = "1,2,11,12,21";
    $data =  $event->getEventRSVPsList($status,$start,60);

    if (count($data)>0){
        for($i=0;$i<count($data);$i++){
            $encMemberUserID = $_COMPANY->encodeId($data[$i]['userid']);

            if (empty($data[$i]['email'])) {
                $other = json_decode($data[$i]['other_data'], true);
                $data[$i]['firstname'] = htmlspecialchars($other['firstname']);
                $data[$i]['lastname'] = htmlspecialchars($other['lastname']);
                $data[$i]['email'] = htmlspecialchars($other['email']);
                $data[$i]['jobtitle'] = '-';
                $data[$i]['department'] = '-';
            }

            $name = trim($data[$i]['firstname'].' '.$data[$i]['lastname']);
            $profilepic = User::BuildProfilePictureImgTag($data[$i]['firstname'], $data[$i]['lastname'], $data[$i]['picture'], 'memberpic');
            //$data[$i]['joinstatus'] = Event::GetRSVPLabel($data[$i]['joinstatus']);
        ?>
            <div class="col-md-6">
                <div class="member-card" onclick="getProfileDetailedView(this,{'userid':'<?= $encMemberUserID; ?>'})">
                    <?= $profilepic; ?>
                    <p class="member_name"><?= $name; ?></p>
                    <p class="member_jobtitle" style=" font-size:small;"><?= $data[$i]['jobtitle']; ?></p>
                </div>
            </div>
    <?php	}

    } else {
        echo 0;
    }
}

# New code
elseif(isset($_GET['submitRsvpListSetting']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (!isset($_POST['eventid']) ||
        ($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === null ||
        ($rsvp_display = $_COMPANY->decodeId($_POST['rsvpOption']))<0
        ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!($_USER->canPublishOrManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')))) {
        if (empty($event->val('collaborating_groupids'))) {
            header(HTTP_FORBIDDEN);
            exit();
        }

        if (!$event->loggedinUserCanPublishEvent()
            && !$event->loggedinUserCanManageEvent()
        ) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }
    // update rsvp_display in the DB
    $event->updateRsvpListSetting($rsvp_display);
    echo $event->val('groupid');
    exit();

}

elseif(isset($_GET['updateRSVPsListSettingModal']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (!isset($_POST['eventid']) ||
        ($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check

    if (!($_USER->canPublishOrManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')))) {

        if (empty($event->val('collaborating_groupids'))) {
            header(HTTP_FORBIDDEN);
            exit();
        }

        if (!$event->loggedinUserCanPublishEvent()
            && !$event->loggedinUserCanManageEvent()
        ) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }


    // gets the modal box from this view
    include(__DIR__ . "/views/templates/rsvp_manage.template.php");

}

elseif (isset($_GET['loadCreateEventGroupModal'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['loadCreateEventGroupModal']))<0 ||
        ($group = Group::GetGroup($groupid)) === null
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);

    // Authorization Check
    if (!$_USER->canCreateContentInGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $edit = null;
    $id = 0;
    $pageTitle= gettext('Create Event Series');
    $buttonTitle = gettext('Create');
    $fontColors = json_encode(array($group->val('overlaycolor'),$group->val('overlaycolor2')));
    $chapters = array();
    $channels = array();
    $selectedChapterIds = array();
    $selectedChannelId = 0;
    $displayStyle = 'row';

    if($_COMPANY->getAppCustomization()['chapter']['enabled']){
        $chapters = Group::GetChapterListByRegionalHierachies($groupid);
    }
    if($_COMPANY->getAppCustomization()['channel']['enabled']){
        $channels= Group::GetChannelList($groupid);
    }

    if (isset($_GET['edit']) && $_GET['edit']!='0'){
        $id = $_COMPANY->decodeId($_GET['edit']);
        $edit  = Event::GetEvent($id);
        $pageTitle= gettext('Update Event Series');
        $buttonTitle = gettext('Update');

        // chapter ids
        $selectedChapterIds = explode(',',$edit->val('chapterid'));
        if (empty($selectedChapterIds)) {
            $selectedChapterIds = array(0);
        }

        // channel id
        $selectedChannelId = $edit->val('channelid');
    }
    if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {
        if ($groupid){
            $lists = DynamicList::GetAllLists('group',true);
        } else {
            $lists = DynamicList::GetAllLists('zone',true);
        }
    }

    include(__DIR__ . "/views/templates/create_event_group.template.php");
}

elseif (isset($_GET['createEventGroup'])){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['createEventGroup']))<0 ||
        ($event_series_id = $_COMPANY->decodeId($_POST['event_series_id'])) <0
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);

    $chapterids = '0';
    if (!empty($_POST['chapters'])){
        $chapterids = implode(',', $_COMPANY->decodeIdsInArray($_POST['chapters']));
    }

    $channelid = 0;
    if (!empty($_POST['channelid'])){
        $channelid = $_COMPANY->decodeId($_POST['channelid']);
    }

    // Authorization Check
    $eventSeries = null;
    if ($event_series_id){
        $eventSeries = Event::GetEvent($event_series_id);
    }

    if (
        !$_USER->canCreateOrPublishContentInScopeCSV($groupid, $chapterids, $channelid)
    ) { //Allow creators to edit unpublished content
        if (((empty($chapterids) && $_COMPANY->getAppCustomization()['chapter']['enabled']) || ($_COMPANY->getAppCustomization()['channel']['enabled'])) && empty($channelid)) {

            if (!$eventSeries || ($eventSeries && $eventSeries->isDraft())){
                $contextScope = $_COMPANY->getAppCustomization()['channel']['name-short'];
                if (empty($chapterids)){
                    $contextScope = $_COMPANY->getAppCustomization()['chapter']['name-short'];
                }
                AjaxResponse::SuccessAndExit_STRING(-3, '', sprintf(gettext("Please select a %s scope"),$contextScope), gettext('Error'));
            }
        } else {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }

    $event_series_name = $_POST['event_series_name'] ?? '';
    if (!$event_series_name) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Event series name is required"), gettext('Errors!'));
    }

    $event_description = ViewHelper::RedactorContentValidateAndCleanup($_POST['event_description']);

    $listids = 0;
    if (isset($_POST['event_scope']) && $_POST['event_scope'] == 'dynamic_list'){
        if ($eventSeries && $eventSeries->isPublished()) {
            $listids = $eventSeries->val('listids');
        } else {
            if (empty($_POST['list_scope'])) {
                AjaxResponse::SuccessAndExit_STRING(0, '', "Please select a dynamic list scope!", 'Error!');
            }
            $listids = implode(',',$_COMPANY->decodeIdsInArray($_POST['list_scope']));
        }
    }


    if ($groupid) {
        $invited_groups = $groupid;
    } else {
        $allGroups = Group::GetAllGroupsByCompanyid($_COMPANY->id(),$_ZONE->id(),true);
        $gids = array();
        foreach($allGroups as $g){ $gids[] = $g->id();}
        sort($gids);
        $invited_groups = implode(',',$gids);
    }

    $rsvp_restriction = $_COMPANY->decodeId($_POST['rsvp_restriction']);
    $isprivate = (bool)($_POST['isprivate'] ?? '0');
    $use_and_chapter_connector = (bool)($_POST['use_and_chapter_connector'] ?? '0');

    if ($event_series_id){
        $event_series = Event::GetEvent($event_series_id);
        $event_series->updateEventSeries($event_series_name, $event_description, $chapterids, $channelid, $invited_groups, $listids, $rsvp_restriction, $isprivate, $use_and_chapter_connector);
        AjaxResponse::SuccessAndExit_STRING(1, $_COMPANY->encodeId($event_series_id), gettext("Updated Successfully."), gettext('Success'));
    } else {
        $event_series_id = Event::CreateNewEventSeries($groupid, $event_series_name, $event_description, $chapterids, $channelid, $listids, $invited_groups, $rsvp_restriction, $isprivate, $use_and_chapter_connector);
        AjaxResponse::SuccessAndExit_STRING(1, $_COMPANY->encodeId($event_series_id), gettext("Created Successfully."), gettext('Success'));
    }
}

elseif (isset($_GET['manageSeriesEventGroup'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['manageSeriesEventGroup']))<0 ||
        ($event_series_id = $_COMPANY->decodeId($_GET['series_id']))<0
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);
    $encEventSeriesId = $_COMPANY->encodeId($event_series_id);

    // // Authorization Check
    // if (!$_USER->canManageGroupSomething($groupid)) {
    //     header(HTTP_FORBIDDEN);
    //     exit();
    // }
    $eventSeries = Event::GetEvent($event_series_id);

    // If the event series is cancelled, then show all cancelled events.
    //$exclude_cancelled_events = !$eventSeries->isCancelled();
    $exclude_cancelled_events = false;

    $sub_events = Event::GetEventsInSeries($event_series_id, $exclude_cancelled_events);
    if ($eventSeries){
        include(__DIR__ . "/views/templates/manage_series_event_group.template.php");
    }else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Event series does not exist. Please check and try again"), gettext('Errors!'));
    }
}
elseif(isset($_GET['deleteEventSeriesGroup']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($event_series_id = $_COMPANY->decodeId($_POST['event_series_id']))<0 ||
        ($event = Event::GetEvent($event_series_id)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (
            !$event->loggedinUserCanUpdateEvent() && !$event->loggedinUserCanPublishEvent()
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    // Check Series head level dependances
    if ($event->isDraft() || $event->isUnderReview()){
        $eventDependencies  = $event->listEventDependencies();
        if (!empty($eventDependencies)) {
            $englishDependencies = Arr::NaturalLanguageJoin($eventDependencies);
            // Check for expense entry
            $retVal = sprintf(gettext('Unable to delete this event series as it has associated %s. Please remove these associations before proceeding.'), $englishDependencies);
            AjaxResponse::SuccessAndExit_STRING(0, '', $retVal, gettext('Error'));
        }
    }

    // Get all series events including the series itself
    $event_rows = Event::GetEventsInSeries($event_series_id,false,false);

    // Validate Event series deletion
    if ($event->isDraft() || $event->isUnderReview()) {
        foreach ($event_rows as $event_row) {
            $eventDependencies = $event_row->listEventDependencies();
            if (!empty($eventDependencies)) {
                $englishDependencies = Arr::NaturalLanguageJoin($eventDependencies);
                $retVal = sprintf(gettext('Unable to delete this event as it has associated %s. Please remove these associations before proceeding.'), $englishDependencies);
                AjaxResponse::SuccessAndExit_STRING(0, '', $retVal, gettext('Error'));
            }
        }
    }

    $event_cancel_reason = $_POST['event_cancel_reason'] ?? '';
    $retVal = 1;
    foreach ($event_rows as $event_row) {
        $retVal = $retVal * $event_row->cancelEvent($event_cancel_reason, false);
    }

    $actionText = ($event->isPublished())
        ? gettext('Event series cancelled successfully')
        : gettext('Event series deleted successfully');

    if ($retVal) {
        AjaxResponse::SuccessAndExit_STRING(1, '', $actionText, gettext('Success'));
    }
    exit();
}

elseif (isset($_GET['checkEventsByDate']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<0  || !isset($_GET['eventid']) || !isset($_GET['date']) ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $eventid = $_COMPANY->decodeId($_GET['eventid']);
    $dateStart = $_GET['date'].' 00:00:00';
    $dateEnd = $_GET['date'].' 23:59:59';

    $dateStart = $db->covertLocaltoUTC("Y-m-d H:i:s", $dateStart, $_SESSION['timezone']);
    $dateEnd = $db->covertLocaltoUTC("Y-m-d H:i:s", $dateEnd, $_SESSION['timezone']);
    $events = Event::GetEventsByDateFilter($dateStart,$dateEnd,$eventid);
    
    if (count($events)){
        include(__DIR__ . "/views/templates/events_by_date.template.php");
    } else {
        echo 1;
        exit();
    }
}

elseif (isset($_GET['getEventActionButton']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 ||
        ($eventid = $_COMPANY->decodeId($_GET['getEventActionButton']))<0 ||
        ($event = Event::GetEvent($eventid)) === null   ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Check for Request Approval if active
    if($_COMPANY->getAppCustomization()['event']['approvals']['enabled']){
        $approval = $event->getApprovalObject() ? $event->getApprovalObject(): '';
    }
    $parent_groupid = $groupid;
    if (!empty($event->val('collaborating_groupids'))) {
        $isAllowedToUpdateContent = $event->loggedinUserCanUpdateEvent();
        $isAllowedToPublishContent = $event->loggedinUserCanPublishEvent();
        $isAllowedToManageContent = $event->loggedinUserCanManageEvent();
    } else {
        $isAllowedToUpdateContent = $event->val('groupid') ? $_USER->canUpdateContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid'), $event->val('isactive')) : $event->loggedinUserCanUpdateEvent();
        $isAllowedToPublishContent = $event->val('groupid') ? $_USER->canPublishContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')) : $event->loggedinUserCanPublishEvent();
        $isAllowedToManageContent = $event->val('groupid') ? $_USER->canManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')) : $event->loggedinUserCanManageEvent();
    }

    include(__DIR__ . "/views/templates/event_action_button.template.php");
    
}
elseif (isset($_GET['inviteEventUsersForm']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['inviteEventUsersForm']))<1
        || ($event=Event::GetEvent($eventid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if(!$_USER->isAdmin()){
        // Authorization Check
        if (!$_USER->canPublishContentInCompanySomething()) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }
    $groupList = [];
    $chapterList = [];
    $shortName = $_COMPANY->getAppCustomization()['group']["name-short"];
    $triggerTitle = sprintf(gettext("By %s"), $_COMPANY->getAppCustomization()['group']["name-short-plural"]);
    $selectTitle = sprintf(gettext("Select a %s"),$shortName);
    $preSelected = explode(',',$event->val('invited_groups'));
    $groupSelectTitle = sprintf(gettext('Select a %1$s to get %2$s'),$_COMPANY->getAppCustomization()['group']["name-short"],$_COMPANY->getAppCustomization()['chapter']["name-short-plural"]);
    if ($event->val('channelid') || $event->val('teamid') || $event->isSeriesEventSub() || !empty($event->val('listids'))){
        // Disable group and chapter level invites if Event have channel level scope or Team event
        // or Series head or is dynamic list event.
        $groupList = [];
        $chapterList = [];
    } else {
        $allGroups = Group::GetAllGroupsByZones([$_ZONE->id()]);
        foreach($allGroups as $group){
            $disabled = '';
            $allowed = true;
            $optionSuffix = '';
            $helpText = "";
            if (in_array((int)$group['group_type'], array(Group::GROUP_TYPE_INVITATION_ONLY, Group::GROUP_TYPE_MEMBERSHIP_DISABLED))) {
                continue; // Do not show invitation only (hidden groups) or membership disabled groups
            }
            if (!$_USER->canPublishContentInGroup($group['groupid'])){
                    $allowed = false;
                    $disabled = 'disabled';
                    $optionSuffix = '('.gettext('insufficient permissions').')';
                    $helpText = '('.sprintf(gettext('Your role in this organization does not have the needed permissions to complete this action. Contact your %s leader directly for support.'),$_COMPANY->getAppCustomization()['group']['name-short']).')';
            }
            if (in_array($group['groupid'],$preSelected)){
                $disabled = 'disabled';
                $optionSuffix = '('.gettext('invited').')';
            }
            $groupList[] = array('id'=>$group['groupid'],'name'=>$group['groupname'],'disabled'=>$disabled,'allowed'=>$allowed,'optionSuffix'=>$optionSuffix,'helpText' =>$helpText);
        }
        if (!empty($event->val('collaborating_groupids'))) {
            if (!empty($event->val('chapterid'))) {
                $triggerTitle = sprintf(gettext("By %s"),$_COMPANY->getAppCustomization()['chapter']["name-short-plural"]);
            }
        }
        $groupIdsWithoutChapters = Group::FilterGroupsWithoutMatchingChapters($event->val('invited_groups'), $event->val('chapterid'));

        if ($event->val('groupid') && $event->val('chapterid') !='0' && $_COMPANY->getAppCustomization()['chapter']['enabled']){
            $chapterShortName = $_COMPANY->getAppCustomization()['chapter']["name-short"];
            $triggerTitle = sprintf(gettext("By %s"),$_COMPANY->getAppCustomization()['chapter']["name-short-plural"]);
            $chapterSelectTitle = sprintf(gettext("Select a %s"),$chapterShortName);
            $preSelected = array_merge(explode(',',$event->val('invited_chapters')),explode(',',$event->val('chapterid')));
            $chapters = Group::GetChapterList($event->val('groupid'));
            foreach($chapters as $chapter){
                $disabled = '';
                $allowed = true;
                $optionSuffix = '';
                $helpText = "";
                if (!$_USER->canPublishContentInGroupChapterV2($event->val('groupid'),$chapter['regionids'],$chapter['chapterid'])) {
                    $allowed = false;
                    $disabled = 'disabled';
                    $optionSuffix = '('.gettext('insufficient permissions').')';
                    $helpText = '('.sprintf(gettext('Your role in this organization does not have the needed permissions to complete this action. Contact your %s leader directly for support.'),$_COMPANY->getAppCustomization()['group']['name-short']).')';
                }
                if (in_array($chapter['chapterid'],$preSelected)){
                    $disabled = 'disabled';
                    $optionSuffix = '('.gettext('invited').')';
                }
                $chapterList[] = array('id'=>$chapter['chapterid'],'name'=>$chapter['chaptername'],'disabled'=>$disabled,'allowed'=>$allowed,'optionSuffix'=>$optionSuffix,'helpText' =>$helpText);
            }
        }
    }
    include(__DIR__ . "/views/templates/event_invite.template.php");
    
}

elseif (isset($_GET['refreshCalendarDyanamicFilters']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    $selectedZoneIds = !empty($_GET['zoneids']) ? $_COMPANY->decodeIdsInCSV($_GET['zoneids']) : '';
    $zoneIdsArray = Str::ConvertCSVToArray($selectedZoneIds) ?: array($_ZONE->id());

    // The following code should be kept in sync with similar code-001 in calendar.php
    $groupCategoryRows = [];
    $groupCategoryArray = [];
    if (count($zoneIdsArray) <= 1) {
        // If more than one ZoneIds are selected, then we will disable Group Category Filter
        // Else, set $groupCategoryRows and filter
        $zoneForCategory = $zoneIdsArray[0] ? $_COMPANY->getZone($zoneIdsArray[0]) : $_ZONE;
        $groupCategoryRows = Group::GetAllGroupCategoriesByZone($zoneForCategory, true, true);
        $filter = $_GET['category'] ?? $_GET['filter'] ?? '';
        if (empty($filter) || $filter == 'all') {
            // If filter is set to all or no category selected then add all group gategory ids to array.
            $groupCategoryArray = array_values(array_column($groupCategoryRows, 'categoryid'));
        } else {
            $groupCategoryArray = Str::ConvertCSVToArray($filter);
        }
    }

    // Use regionids if provided, e.g. during refresh triggered as a result of change in regionids
    $regionIdsArray = array('all');
    if (isset($_GET['regionids'])) {
        if ($_GET['regionids'] != 'all') {
            $regionIdsArray = $_COMPANY->decodeIdsInArray(Str::ConvertCSVToArray($_GET['regionids']));
            $regionIdsArray[] = 0; // Add back region id 0 to always see events without region
        }
    }

    // Reset the following 3 types on filter reset
    $groupIdsArray = array('all');
    $eventTypeArray = array('all');
    $chapterIdsArray = array('all');

    include(__DIR__ . "/views/templates/calendar_dynamic_filters.template.php");
}
elseif(isset($_GET['updateEventFollowUpNoteForm'])){
    if (($eventid = $_COMPANY->decodeId($_GET['eventid'])) < 1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encEventId = $_COMPANY->encodeId($eventid);

    // Authorization Check
    if (!$event->loggedinUserCanPublishEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    

    $events_attendence_type = array( (int)$event->val('event_attendence_type'));
    $events_max_online = (int)$event->val('max_online');
    $events_max_inperson = (int)$event->val('max_inperson');
    $events_rsvp_yes_no = (int)($events_max_online == 0 && $events_max_inperson == 0);
    
    $postEventSurveyLink = '';
    if ($_COMPANY->getAppCustomization()['event']['enable_event_surveys'] && $event->isEventSurveyAvailable(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT'])){
        $postEventSurveyLink = $_COMPANY->getAppURL($_ZONE->val('app_type')).'eventview?id='.$_COMPANY->encodeId($eventid).'&survey_key='.base64_url_encode(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT']);
    }

	include(__DIR__ . "/views/templates/event_follow_up_note_model.template.php");

} elseif (isset($_GET['updateEventFollowUpNote']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    $event = null;
    $collaborate = '';
    $chapterid = 0;
    $channelid = 0;
    $regionid = 0;

    if (isset($_GET['updateEventFollowUpNote'])
        && (($eventid = $_COMPANY->decodeId($_GET['updateEventFollowUpNote']))<1
            || ($event=Event::GetEvent($eventid)) === null)
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canPublishContentInScopeCSV($event->val('groupid'),$event->val('chapterid'),$event->val('channelid'), $event->val('isactive')) && !$event->loggedinUserCanPublishEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $followup_notes = ViewHelper::RedactorContentValidateAndCleanup($_POST['followup_notes']);

    if (empty($followup_notes)) { // Remove tags and &nbsp; spaces to see if content is empty
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Follow up notes cannot be empty!'), gettext('Error'));
    }


    $doWhat = intval($_POST['do_what'] ?? 1);
    $sendEmails = ($doWhat === 2);
    $update = $event->updateEventFollowUpNote($followup_notes);
    if ($sendEmails) {
        $event = Event::GetEvent($event->id()); // Reload
        $subject = 'Followup: ' . $event->val('eventtitle');
        $comments = $event->val('followup_notes');
        $send_update_to = array();
        if(isset($_POST['send_update_to'])){
          $send_update_to =   $_POST['send_update_to'];
        }
        $reminderTo	= array_map('intval', $send_update_to) ?? array('1'); // All RSVPed options dynamically, (-1) -> All invited
        $job = new EventJob($event->val('groupid'), $eventid);
        if ($job->saveAsBatchRemindType($subject, $comments, 0, $reminderTo, 0)) {
            AjaxResponse::SuccessAndExit_STRING(1, array('eventid'=>$_COMPANY->encodeId($eventid),'chapterid'=>$_COMPANY->encodeId(0),'channelid'=>$_COMPANY->encodeId(0)), gettext("Post Event Follow-up Note updated successfully. Emails matching the selected list will be sent out shortly."), gettext('Success'));
        }
    } else {
        AjaxResponse::SuccessAndExit_STRING(1, array('eventid'=>$_COMPANY->encodeId($eventid),'chapterid'=>$_COMPANY->encodeId(0),'channelid'=>$_COMPANY->encodeId(0)), gettext("Post Event Follow-up Note updated successfully."), gettext('Success'));
    }
}

elseif(isset($_GET['cloneEvent'])){

    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['cloneEvent']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $parent_groupid = $_POST['parent_groupid'] ? $_COMPANY->decodeId($_POST['parent_groupid']) : 0;
    $new_event = $event->cloneEvent([
        'eventtitle' => 'Clone of ' . html_entity_decode($event->val('eventtitle'))
    ]);

    if ($new_event){
        AjaxResponse::SuccessAndExit_STRING(1, $new_event->encodedId(), gettext("Cloned successfully!"), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("An error has occurred when cloning an event. Please check your connection and try again. If you still have errors, please contact IT support."), gettext('Errors!'));
    }
}

elseif(isset($_GET['manageWaitList'])){

    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['manageWaitList']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->isEventContributor() && !$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //nd (j.joinstatus in ({$joinStatus}))

    $joinStatus = Event::RSVP_TYPE['RSVP_INPERSON_YES'].','.Event::RSVP_TYPE['RSVP_ONLINE_YES'].','.Event::RSVP_TYPE['RSVP_INPERSON_WAIT'].','.Event::RSVP_TYPE['RSVP_ONLINE_WAIT'];

    $data = $event->getEventRSVPsList();
    $totalInpersonConfirmed = 0;
    $totalInpersonWaiting = 0;
    $totalOnlineConfirmed = 0;
    $totalOnlineWaiting = 0;

    foreach($data as $row){
        if ($row['joinstatus'] == Event::RSVP_TYPE['RSVP_INPERSON_YES']){
            $totalInpersonConfirmed = $totalInpersonConfirmed+1;
        }
        if ($row['joinstatus'] == Event::RSVP_TYPE['RSVP_ONLINE_YES']){
            $totalOnlineConfirmed = $totalOnlineConfirmed+1;
        }
        if ($row['joinstatus'] == Event::RSVP_TYPE['RSVP_INPERSON_WAIT']){
            $totalInpersonWaiting = $totalInpersonWaiting+1;
        }
        if ($row['joinstatus'] == Event::RSVP_TYPE['RSVP_ONLINE_WAIT']){
            $totalOnlineWaiting = $totalOnlineWaiting+1;
        }
    }
    $rsvpOptions    = $event->getAllRSVPOptionsForManagement();
    include(__DIR__ . "/views/templates/event_manage_waitlist.template.php");
    exit();
}

elseif(isset($_GET['updateInPersonSlots']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['updateInPersonSlots']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent() && !$event->isEventContributor()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $max_inperson = intval($_POST['inperson'] ?? $event->val('max_inperson'));
    $max_inperson_waitlist = intval($_POST['inperson_waitlist'] ?? $event->val('max_inperson_waitlist'));

    if ((int) ($_POST['inperson_limit_unlimited'] ?? 0) === 1) {
        $max_inperson = Event::MAX_PARTICIPATION_LIMIT;
    }

    if ((int) ($_POST['inperson_waitlist_unlimited'] ?? 0) === 1) {
        $max_inperson_waitlist = Event::MAX_PARTICIPATION_LIMIT;
    }

    $retVal = array();
    $retVal['inperson'] = intval($event->val('max_inperson'));
    $retVal['inperson_waitlist'] = intval($event->val('max_inperson_waitlist'));
    if ($max_inperson > 0 && $max_inperson_waitlist >= 0) {
        $error = $event->validateAndGetError([
            'max_inperson' => $max_inperson,
            'max_inperson_waitlist' => $max_inperson_waitlist,
        ]);

        if ($error) {
            AjaxResponse::SuccessAndExit_STRING(
                0,
                '',
                $error,
                gettext('Error')
            );
        }

        // Update max inperson
        if ($event->updateInPersonSlots ($max_inperson, $max_inperson_waitlist)) {
            $retVal['inperson'] = $max_inperson;
            $retVal['inperson_waitlist'] = $max_inperson_waitlist;
        }
    }

    AjaxResponse::SuccessAndExit_STRING(
        1,
        '',
        gettext('Updated successfully.'),
        gettext('Success')
    );
}

elseif(isset($_GET['updateOnlineSlots']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['updateOnlineSlots']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent() && !$event->isEventContributor()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    
    $max_online = (int) $_POST['online'] ?? $event->val('max_online');
    $max_online_waitlist = (int) $_POST['online_waitlist'] ?? $event->val('max_online_waitlist');

    if ((int) ($_POST['online_limit_unlimited'] ?? 0) === 1) {
        $max_online = Event::MAX_PARTICIPATION_LIMIT;
    }

    if ((int) ($_POST['online_waitlist_unlimited'] ?? 0) === 1) {
        $max_online_waitlist = Event::MAX_PARTICIPATION_LIMIT;
    }

    $retVal = array();
    $retVal['online'] = intval($event->val('max_online'));
    $retVal['online_waitlist'] = intval($event->val('max_online_waitlist'));
    if ($max_online > 0 && $max_online_waitlist >= 0) {
        $error = $event->validateAndGetError([
            'max_online' => $max_online,
            'max_online_waitlist' => $max_online_waitlist,
        ]);

        if ($error) {
            AjaxResponse::SuccessAndExit_STRING(
                0,
                '',
                $error,
                gettext('Error')
            );
        }

        // Update max online
        if ($event->updateOnlineSlots($max_online, $max_online_waitlist)) {
            $retVal['online'] = $max_online;
            $retVal['online_waitlist'] = $max_online_waitlist;
        }
    }

    AjaxResponse::SuccessAndExit_STRING(
        1,
        '',
        gettext('Updated successfully.'),
        gettext('Success')
    );
}

elseif(isset($_GET['changeRsvpStatus']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['changeRsvpStatus']))<1 ||
        ($joineeid = $_COMPANY->decodeId($_POST['joineeid']))<1  ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent() && !$event->isEventContributor()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    
    $status = (int) $_POST['status'];
    $RSVPLabel = Event::GetRSVPLabel($status);

    if (!$RSVPLabel){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $joineeData = $event->getEventRsvpDetail($joineeid);
    if ($joineeData){
        // Update Join Status
        $joinEventStatus = $event->joinEvent($joineeData['userid'], $status, 0,1,0,$joineeid);
        if ($joinEventStatus > 0 || $joinEventStatus == -2) {
            // joinEvent returns - 1 on sucess(updated), 2 on success (added), 0 if join was ignored, -1 on internal error, -2 on email error, -3 on schedule error
            echo $RSVPLabel;
            exit();
        }
    }

    exit();
}

elseif(isset($_GET['updateBulkWaitlistCancel']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['updateBulkWaitlistCancel']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canPublishOrManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')) && !$event->isEventContributor()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $option = $_POST['option'] ?? 'not_selected';
    $inperson = array();
    if ($option === 'cancel_inperson_waitlist' || $option === 'cancel_all_waitlist') { #In-person
        $oldStatus = Event::RSVP_TYPE['RSVP_INPERSON_WAIT'];
        $status = Event::RSVP_TYPE['RSVP_INPERSON_WAIT_CANCEL'];
        $cancellist = $event->getEventRsvpsByStatus($oldStatus);
        foreach ($cancellist as $item) {
            $inperson[] = array('j'=>$_COMPANY->encodeId($item['joineeid']),
                                    'g'=>$_COMPANY->encodeId($event->val('groupid')),
                                    'e'=>$_COMPANY->encodeId($eventid),
                                    'u'=>$_COMPANY->encodeId($item['userid']),
                                    's'=>$_COMPANY->encodeId($status),
                                    't'=>1);
        }
    }
    $online = array();
    if ($option === 'cancel_online_waitlist' || $option === 'cancel_all_waitlist'){ #Online
        $oldStatus = Event::RSVP_TYPE['RSVP_ONLINE_WAIT'];
        $status = Event::RSVP_TYPE['RSVP_ONLINE_WAIT_CANCEL'];
        $cancellist = $event->getEventRsvpsByStatus($oldStatus);
        foreach ($cancellist as $item) {
            $online[] = array('j'=>$_COMPANY->encodeId($item['joineeid']),
                                'g'=>$_COMPANY->encodeId($event->val('groupid')),
                                'e'=>$_COMPANY->encodeId($eventid),
                                'u'=>$_COMPANY->encodeId($item['userid']),
                                's'=>$_COMPANY->encodeId($status),
                                't'=>2);
        }
    }

   echo json_encode(array_merge($inperson,$online));
    exit();
}
elseif(isset($_GET['processupdateBulkWaitlistCancel']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (($j = $_COMPANY->decodeId($_POST['j'])) < 1 ||
        ($g = $_COMPANY->decodeId($_POST['g'])) < 0 ||
        ($e = $_COMPANY->decodeId($_POST['e'])) < 1 ||
        ($u = $_COMPANY->decodeId($_POST['u'])) < 1 ||
        ($s = $_COMPANY->decodeId($_POST['s'])) < 1 ||
        !($event = Event::GetEvent($e)) ||
        (!in_array($s, array(Event::RSVP_TYPE['RSVP_ONLINE_WAIT_CANCEL'],Event::RSVP_TYPE['RSVP_INPERSON_WAIT_CANCEL'])))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($event->joinEvent ($u, $s, 0,1) > 0) {
        echo 1;
    } else{
        echo 0;
    }
    exit();
}

elseif(isset($_GET['updateRsvpEndTime']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['updateRsvpEndTime']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent() && !$event->isEventContributor()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    
    
    if ($_POST['rsvp_end_date_time'] === 'scheduled') {
        $endDateTime = $_POST['publish_Ymd']." ".$_POST['publish_h'].":".$_POST['publish_i']." ".$_POST['publish_A'];
    } else {
        $endDateTime  = date("Y-m-d H:i:s");
    }
    $timezone = isset($_POST['timezone']) ? $_POST['timezone'] : (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
    $rsvp_dueby = $db->covertLocaltoUTC("Y-m-d H:i:s", $endDateTime, $timezone);

    // Update Join Status
    $event->updateEventRsvpDuebyDatetime($rsvp_dueby);
    $_COMPANY->expireRedisCache("EVT:{$eventid}");
    $event = Event::GetEvent($eventid);

    echo '<span style="color:'. ($event->hasRSVPEnded() ? 'red':'green') .';">'.
        $_USER->formatUTCDatetimeForDisplayInLocalTimezone($rsvp_dueby,true,true,false) .
        '</span>';
    exit();
}

elseif(isset($_GET['updateCalendarCurrentLocation']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (isset($_POST['new_latitude']) && isset($_POST['new_longitude']) && isset($_POST['current_address'])) {
        $_SESSION['current_latitude'] = $_POST['new_latitude'];
        $_SESSION['current_longitude'] = $_POST['new_longitude'];
        $_SESSION['fullAddress'] = $_POST['current_address'];
        echo 1;
    }
    exit();
}

elseif (isset($_GET['addOrUpdateEventSpeaker']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($speakerid = $_COMPANY->decodeId($_POST['speakerid']))<0 ||
        ($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    // Authorization Check
    if (!$event->loggedinUserCanUpdateEvent() && !$event->loggedinUserCanPublishEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $speech_length = (int) $_POST['speech_length'];
    $expected_attendees = (int) $_POST['expected_attendees'];
    $speaker_fee = (int) $_POST['speaker_fee'];
    $speaker_name =  $_POST['speaker_name'];
    $speaker_title =  $_POST['speaker_title'];
    $speaker_bio =  $_POST['speaker_bio'];
    $other =  $_POST['other'];
    $speaker_picture = '';

    $custom_fields = ViewHelper::ValidateAndExtractCustomFieldsFromPostAttribute('EVTSPK');

    if ($speakerid){
        $updateResult = $event->updateEventSpeaker($speakerid,$speech_length,$speaker_name,$speaker_title,$speaker_fee,$speaker_picture,$speaker_bio,$other,$expected_attendees, $custom_fields);
        if ($updateResult) {
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Information updated successfully"), gettext('Success'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Unable to update speaker"), gettext('Error'));
        }
    } else {
        $speakerid = $event->addEventSpeaker($speech_length,$speaker_name,$speaker_title,$speaker_fee,$speaker_picture,$speaker_bio,$other,$expected_attendees, $custom_fields);
        if ($speakerid) {
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Speaker Added Successfully"), gettext('Success'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Unable to add speaker"), gettext('Error'));
        }
    }
	exit();
}

elseif(isset($_GET['manageEventSpeakers']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['manageEventSpeakers']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateEvent() && !$event->loggedinUserCanPublishEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $approvelStatus = array('0'=>gettext('Draft'),'1'=>gettext('Requested'),'2'=>gettext('Processing'),'3'=>gettext('Approved'),'4'=>gettext('Denied'));
    $eventSpeakers = $event->getEventSpeakers();
    $isActionDisabledDuringApprovalProcess = $event->isActionDisabledDuringApprovalProcess();
    include(__DIR__ . "/views/templates/event_speakers_list.template.php");
    exit();
}

elseif (isset($_GET['showSelectFromPastSpeakerDiv']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateEvent() && !$event->loggedinUserCanPublishEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $allApprovedSpeakers = $event->getApprovedEventSpeakers();

    $items = array();
    foreach ($allApprovedSpeakers as $speaker) {
        $items[] = array('v'=>$_COMPANY->encodeId($speaker['speakerid']),'t'=>$speaker['speaker_name'].' ('.$speaker['speaker_title'].')');
    }
    echo json_encode($items);
    exit();
}

elseif(isset($_GET['openAddOrUpdateEventSpeakerModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($speakerid = $_COMPANY->decodeId($_GET['speakerid']))<0 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        !isset($_GET['clone'])
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateEvent() && !$event->loggedinUserCanPublishEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $custom_fields = EventSpeaker::GetEventCustomFields();
    $event_custom_fields = [];

    $clone = (int)$_GET['clone'];
    $modalTitle = ($speakerid && !$clone) ? gettext("Update Event Speaker") : gettext("New Event Speaker");
    $speaker = array();
    if ($speakerid){
        $speaker = $event->getEventSpeakerDetail($speakerid);

        $event_speaker_obj = EventSpeaker::Hydrate($speakerid, $speaker);
        $event_custom_fields = json_decode($event_speaker_obj->val('custom_fields') ?? '', true);
    }
    $speakerFields = Event::GetEventSpeakerFieldsList(true);

    include(__DIR__ . "/views/templates/event_speaker_modal_form.template.php");
    exit();
}
elseif(isset($_GET['deleteEventSpeaker']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($speakerid = $_COMPANY->decodeId($_POST['speakerid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        ($speaker = $event->getEventSpeakerDetail($speakerid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateEvent() && !$event->loggedinUserCanPublishEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (!empty($speaker['speaker_picture'])) {
        $_COMPANY->deleteFile($speaker['speaker_picture']);
    }
    $remainingRowsCount =  ((int) $_POST['rows']) - 1;
    $event->deleteEventSpeaker($speakerid);

    if ($remainingRowsCount){
        echo 1;
    } else {
?>
    <div class="col-md-12 text-center p-3" id="noSpeaker">
        <p><?= gettext('No event speaker added to this event.')?></p>
        <br>
        <a href="#" onclick="showSelectFromPastSpeakerDiv('<?=$_COMPANY->encodeId($event->id())?>')" >
            <i class="fa fa-plus-circle" aria-hidden="true"></i> <?=gettext('Add')?>
        </a>
    </div>
<?php
    }
    exit();
}

elseif(isset($_GET['updateEventSpeakerStatus']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($speakerid = $_COMPANY->decodeId($_POST['speakerid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateEvent() && !$event->loggedinUserCanPublishEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    
    $approval_status =  $_POST['approval_status'] == 1 ? $_POST['approval_status'] : 0;
    if ($event->updateEventSpeakerStatus($speakerid,$approval_status)){
        if ($approval_status){
            $eventZone = $_COMPANY->getZone($event->val('zoneid'));
            $speakerApprovalCCEmails = Event::GetSpeakerApprovalCCEmailsForZone($eventZone);
            $group = Group::GetGroup($event->val('groupid'));
            $speaker = $event->getEventSpeakerDetail($speakerid);
            $subject = "Speaker Request for " . htmlspecialchars_decode($event->val("eventtitle"));
            $message = "There is a new event speaker request for approval from " . $event->val("eventtitle"). " event. Please login to the admin account and go to the Events section to approve or deny this request. A summary of this event speaker request is listed below:";
            $app_type = $_ZONE->val('app_type');
            $reply_addr = $group->val('replyto_email');
            $from = $group->val('from_email_label')." Speaker Request";
            // Email will sent to users who are added into the speaker cc notification feature. #3841
            // $admins = User::GetAllZoneAdminsWhoCanManageZoneSpeakers();
            // $email = implode(',', array_column($admins, 'email'));
            $username = $_USER->getFullName();

            $speakerName = htmlspecialchars($speaker['speaker_name']);
            $speakerFee = htmlspecialchars($speaker['speaker_fee']);

            $event_speaker_obj = EventSpeaker::Hydrate($speakerid, $speaker);
            $custom_fields_html = $event_speaker_obj->renderCustomFieldsComponent('v2');

            $msg = <<<EOMEOM
            <p>There is a new event speaker request from <strong>{$_USER->getFullName()}</strong> ({$_USER->val('email')})</p>
            <br>
            <p>Please <a href="{$event->getZoneAwareUrlBase('admin')}event_speakers">Approve or Deny</a> this request.</p>
            <br>
            <br>
            <p>Event Speaker Request Summary:</p>
            <p>-------------------------------------------------</p>
            <p>Event: {$event->val('eventtitle')}</p>
            <p>Speaker Name    : {$speakerName}</p>
            <p>Speech Length   :  {$speaker['speech_length']} minutes</p>
            <p>Speaker Fee ($) :  {$speakerFee}</p>
            {$custom_fields_html}
            <p>-------------------------------------------------</p>
EOMEOM;

            $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
            $emesg = str_replace('#messagehere#', $msg, $template);
            $_COMPANY->emailSend2($from, $speakerApprovalCCEmails, $subject, $emesg, $app_type, $reply_addr,'',array());
        }
    }
        echo addslashes(gettext("Requested"));
    exit();
}

elseif (isset($_GET['newHolidayModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 ||
        ($eventid = $_COMPANY->decodeId($_GET['eventid']))<0 ||
        (!$_COMPANY->getAppCustomization()['event']['cultural_observances']['enabled'])
     ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (!$_USER->canCreateContentInScopeCSV($groupid,0,0)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $modalTitle = gettext("New Cultural Observance");
    $event = null;
    if($eventid > 0){
        $event = Event::getEvent($eventid);
        $modalTitle = gettext("Update Cultural Observance");
    }
    include(__DIR__ . "/views/templates/new_holiday_form.template.php");

}

elseif (isset($_GET['addOrUpdateHoliday']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($eventid = $_COMPANY->decodeId($_POST['eventid']))<0 ||
        ($eventid && (($holiday = Event::getEvent($eventid)) === NULL))
     ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (!$_USER->canCreateContentInScopeCSV($groupid,0,0)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $eventtitle = $_POST['eventtitle'];
    $event_tz = 'UTC';
    $start = $_POST['eventdate'].' 00:00:00';
    $event_description = ViewHelper::RedactorContentValidateAndCleanup($_POST['event_description']);
    $multiDayHoliday = 0;

    if (Str::IsEmptyHTML($_POST['event_description'])) {
        $_POST['event_description'] = '';
    }

    if (empty($_POST['event_description'])){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please input cultural observance description"), gettext('Error'));
    }

    if (empty($_POST['eventdate']) || Sanitizer::SanitizeUTCDatetime($start) != $start) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please enter a valid cultural observance start date"), gettext('Error'));
    }

    if (isset($_POST['multiDayHoliday'])){
        $multiDayHoliday = 1;
    }
    $end = '';
    if ($multiDayHoliday){

        if (empty($_POST['enddate'])){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please input cultural observance end date"), gettext('Error'));
        }
        $end = $_POST['enddate'].' 23:59:59';
        if (Sanitizer::SanitizeUTCDatetime($end) != $end) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please enter a valid cultural observance end date"), gettext('Error'));
        }

        if($end < $start){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Cultural observance end date cannot be earlier then start date"), gettext('Error'));
        } elseif(strtotime($end)-strtotime($start) <= 86400){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("In order for a cultural observances to be considered a multi-day cultural observances, the length of the cultural observances must be more than 24 hours. Please adjust the time frame and resubmit."), gettext('Error'));
        } 
    } else {
        $end = $_POST['eventdate'].' 23:59:59'; // Same as start date
    }

    if ($eventid){

        $holiday->updateEvent('0', $eventtitle, $start, $end, $event_tz, '', '', $event_description, 0, '', 0, 0, 0, 0, 0, '', '', '', 0, '', 0, '', '', 0, '', '', 0, 0, '','0',false);

    } else {

        Event::CreateNewEvent($groupid, '0', $eventtitle, $start, $end, $event_tz, '', '', $event_description, 0, '', 0, 0, 0, 0, 0, '', '', '', 0, '', 0, 0, '', '', '','',0, 'holiday', 0, 0, '', '0', false);
    }

   $isAllowedToCreateContent = $_USER->canCreateContentInScopeCSV($groupid,0,0);
   $isAllowedToPublishContent = $_USER->canPublishContentInScopeCSV($groupid,0,0);
   $holidays =  Event::GetGroupHolidays($groupid,true);
   include(__DIR__ . "/views/templates/holidays_table.template.php");
}
elseif (isset($_GET['manageHolidays']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 ||
        (!$_COMPANY->getAppCustomization()['event']['cultural_observances']['enabled'])
     ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $isAllowedToCreateContent = $_USER->canCreateContentInScopeCSV($groupid,0,0);
    $isAllowedToPublishContent = $_USER->canPublishContentInScopeCSV($groupid,0,0);
    if (!($isAllowedToCreateContent || $isAllowedToPublishContent)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $holidays =  Event::GetGroupHolidays($groupid,true);
    include(__DIR__ . "/views/templates/manage_holidays.template.php");
}
elseif (isset($_GET['activateOrDeactivateHoliday']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($holiday = Event::getEvent($eventid)) === NULL
     ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $isAllowedToCreateContent = $_USER->canCreateContentInScopeCSV($holiday->val('groupid'),0,0);
    $isAllowedToPublishContent = $_USER->canCreateOrPublishContentInScopeCSV($holiday->val('groupid'),0,0);

    if (!$isAllowedToPublishContent) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $status_msg = ($_POST['status'] == 1) ? gettext('Cultural Observance Activated Successfully') : gettext('Cultural Observance Deactivated Successfully');

    if ($holiday->activateDeactivateHoliday($_POST['status'])){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext($status_msg), gettext('Success'));
    } else{
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
    }
}
elseif (isset($_GET['deleteHoliday']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($holiday = Event::getEvent($eventid)) === NULL
     ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $isAllowedToCreateContent = $_USER->canCreateContentInScopeCSV($holiday->val('groupid'),0,0);
    $isAllowedToPublishContent = $_USER->canCreateOrPublishContentInScopeCSV($holiday->val('groupid'),0,0);

    if (!($isAllowedToCreateContent || $isAllowedToPublishContent)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($holiday->deleteHoliday()){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Cultural Observance Deleted Successfully"), gettext('Success'));
    } else{
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
    }
}

elseif (isset($_GET['viewHolidayDetail']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($holiday = Event::getEvent($eventid)) === NULL
     ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $callback = !empty($_GET['callback']) ? 1 : 0;
    $groupName = Group::GetGroupName($holiday->val('groupid'));
    include(__DIR__ . "/views/templates/holiday_detail.template.php");
}
elseif (isset($_GET['getFilteredChapterList']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        !isset($_GET['zoneids'],$_GET['regionids'],$_GET['groupids'])
     ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }


    // The following logic is same as what we have in calendar_dynamic_filters.template.php to
    // fetch chapter list.
    $zoneIdsArray = $_COMPANY->decodeIdsInArray(Str::ConvertCSVToArray($_GET['zoneids']));
    $groupIdsArray = $_COMPANY->decodeIdsInArray(Str::ConvertCSVToArray($_GET['groupids']));
    $groupCategories = Str::ConvertCSVToArray($_GET['category']);
    $regionIdsArray = ($_GET['regionids'] == 'all') ? null : $_COMPANY->decodeIdsInArray(Str::ConvertCSVToArray($_GET['regionids']));


    $group_chapter_rows = Group::GetGroupsAndChapterRows($zoneIdsArray, $regionIdsArray, $groupCategories);

    $allChapters = Arr::KeepColumns($group_chapter_rows, ['chapterid', 'chaptername', 'groupid']);
    usort($allChapters, function($a, $b) {
        return $a['chaptername'] <=> $b['chaptername'];
    });

    $allChapters = Arr::GroupBy($allChapters, 'chaptername');
    //   ... keep only the chapters that have one of the selected groups
    $filteredChapters = array_filter($allChapters, function ($value, $key) use ($groupIdsArray) {
        return !empty($key) && (!empty(array_intersect($groupIdsArray, array_column($value,'groupid'))));
    }, ARRAY_FILTER_USE_BOTH);


    echo '<option value="'.$_COMPANY->encodeId(0).'"  selected >'.gettext('Include global events').'</option>';
    foreach($filteredChapters as $key => $row){
        $chapterids = $_COMPANY->encodeIdsInCSV(implode(',',array_column($row,'chapterid')));
        echo "<option value=\"{$chapterids}\" selected>{$key}</option>";
    }

}
elseif (isset($_GET['searchUserForRSVP']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::getEvent($eventid)) === NULL
     ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent() && !$event->isEventContributor()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $keyword= trim($_GET['keyword']);
    $data = $event->searchUsersForEventRSVP($keyword);

    $html_response = '';
    if(!empty($data)) {
        $html_response .= '<select class="form-control userdata" name="userid" onchange="closeDropdown()" id="user_search" required >';
        $html_response .= '<option value="">'.gettext("Select an user (maximum of 10 matches are shown below)").'</option>';
        foreach ($data as $item) {
            $html_response .= '<option value="'. $_COMPANY->encodeId($item['userid']). '" >';
            $html_response .=  rtrim(($item['firstname'].' '.$item['lastname']),' '). ' ('. $item['email'].')';
            $html_response .= '</option>';
        }
        $html_response .= '</select>';

    }else{
        $html_response .= '<select class="form-control userdata" name="userid" id="user_search" required>';
        $html_response .=     '<option value="">'.gettext("No match found.").'</option>';
        $html_response .= '</select>';
    }
    echo $html_response;
}

elseif (isset($_GET['addUsersToEventRSVPsList']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($eventid = $_COMPANY->decodeId($_GET['addUsersToEventRSVPsList']))<1 ||
        ($event = Event::getEvent($eventid)) === NULL
     ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent() && !$event->isEventContributor()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $enc_userid = $_POST['userid'];
    $joinstatus = (int) $_POST['rsvpOption'];
    $uid = 0;

    if (empty($enc_userid) || ($uid = $_COMPANY->decodeId($enc_userid))<1){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please select a user first'), gettext('Error'));
    }

    $status =  $event->joinEvent($uid, $joinstatus, 1,1); // returns 1 on sucess(updated), 2 on success (added), 0 if join was ignored, -1 on internal error, -2 on email error, -3 on schedule conflict.

    if ($status>0 || $status == -2 ){
        $msg = gettext('Event RSVP list updated.');
        if( $status == -2) {
            $msg = gettext('Event RSVP list updated but unable to send RSVP update email.');
        }
        AjaxResponse::SuccessAndExit_STRING(1, '', $msg, gettext('Success'));
    }
    if ( $status == 0 ) {
        $msg = gettext('Unable to set the selected RSVP option.');
    } elseif( $status == -3 ) {
        $msg = gettext("User can only RSVP to one event in the event series.");
    } elseif ( $status == -4 ) {
        $msg = gettext("This event's start date conflicts with an existing RSVP'd event in this event series.");
    } else {
        $msg = gettext("Something went wrong, please try again.");
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', $msg, gettext('Error'));

}
elseif (isset($_GET['pinUnpinEvent']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        (($event = Event::getEvent($eventid)) === NULL) ||
        !$_COMPANY->getAppCustomization()['event']['pinning']['enabled']
     ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (!$_USER->canPublishOrManageContentInScopeCSV($event->val('groupid'),$event->val('chapterid'),$event->val('channelid')) && !$event->loggedinUserCanUpdateEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $status = $_POST['status'] == 1;

    $event->pinUnpinEvent($status);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Updated successfully.'), gettext('Success'));
}

elseif (isset($_GET['openCollaborationInviteModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
     ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
   
    if (!$event->loggedinUserCanUpdateEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $groups = Group::GetAllGroupsForZoneCollaboration(false);
    $alreadyAccepted = explode(',',$event->val('collaborating_groupids')??'');
    $alreadyInvited = explode(',',$event->val('collaborating_groupids_pending')??'');
    $alreadyCollaborating = array_unique(array_merge($alreadyAccepted,$alreadyInvited));
    
    include(__DIR__ . "/views/templates/event_collaboration_request_modal.template.php");
    
}
// This condition deprecated
elseif (0 && isset($_GET['sendEventCollaborationRequest']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::getEvent($eventid)) === NULL
     ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (!$event->loggedinUserCanUpdateEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $encNewCollaborationIds = $_POST['collaborationIds'];
    if (empty($encNewCollaborationIds)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please add at least one group for collaboration'), gettext('Error'));
    }

    $existingCollaboratingGroupIds = empty($event->val('collaborating_groupids')) ? array() : explode(',',$event->val('collaborating_groupids'));
    $collaboratingGroupIds = array();
    $collaboratingGroupIdsPending = array(); // we will build new set
    $requestEmailApprovalGroups = array(); // For pending groups

    if ($event->val('groupid') > 0) {
        $collaboratingGroupIds[] = (int)$event->val('groupid');
    }

    foreach ($encNewCollaborationIds as $encId) {
        $c_gid = $_COMPANY->decodeId($encId);

        if (in_array($c_gid,$existingCollaboratingGroupIds)) {
            $collaboratingGroupIds[] = $c_gid;
            continue; // Already accepted, do not invite again
        }

        if ($c_gid && (($cGroup = Group::GetGroup($c_gid)) !== null )){
            if (
                    $_USER->isCompanyAdmin()
                    ||
                    $_USER->isZoneAdmin($cGroup->val('zoneid'))
                    ||
                    $_USER->canPublishContentInGroupOnly($c_gid)
            ) {
                // User can publish content in the groupid so just move the group to accepted list.
                // User is either Company Admin or Zone Admin of the invited collborating groups zone or for
                // invited collaborating groups in the same, user has publish permissions
                $collaboratingGroupIds[] = $c_gid;
            } else {
                // User cannot publish content in the groupid so move it to pending list and send email request later
                $collaboratingGroupIdsPending[] = $c_gid;
                $requestEmailApprovalGroups[] = $cGroup;
            }
        }
    }

    // If event is collaborating event and collaborating group ids is less than 2 then we cannot proceed
    if (count($collaboratingGroupIds)+count($collaboratingGroupIdsPending) < 2) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please select at least two groups for collaboration'), gettext('Error'));
    }

    // First update the collaborating groupids and pending groupids columns in the events before sending emails
    $event->updateCollaboratingGroupids($collaboratingGroupIds, $collaboratingGroupIdsPending, explode(',',$event->val('chapterid')), explode(',',$event->val('collaborating_chapterids_pending')??''));

    // Next Send Invite Email requests
    foreach ($requestEmailApprovalGroups as $g) {
        //$g->inviteGroupToCollaborateOnTopic('EVT',$eventid);
    }
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Your request was successfully processed. Once approved, approval emails will be sent to the publishers of the selected groups.'), gettext('Success'));
}

elseif(isset($_GET['updateAutomanageWaitlist']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL || 
        ($value = $_POST['value']) < 0 ||
        ($section = (int)$_POST['section']) < 1
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canPublishOrManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')) && !$event->isEventContributor()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($event->updateAutomanageWaitlist($value,$section)){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Updated successfully.'), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
}
elseif (isset($_GET['updateCalendarBlockSetting'])){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 ||
        ($section = (int)$_GET['section'] )<1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
   $startdate = $_GET['start_date'];
    $duration =  0;
    if ($section == 1) { // Multiday event with start and end times
        $enddate = $_GET['end_date'];
        $duration =  round((strtotime($enddate) - strtotime($startdate)) / 3600, 2);
    } elseif($section==2){ // Single day event with duration
        $duration = round($_GET['duration'], 2);
    }
    $timezone = $_GET['timezone'];
    $startTime= strtotime($db->covertLocaltoUTC("Y-m-d H:i:s", $startdate, $timezone));
    $selectedTzTime = new DateTime('now', new DateTimeZone($timezone));
    $now = strtotime($db->covertLocaltoUTC("Y-m-d H:i:s", $selectedTzTime->format("Y-m-d H:i:s"), $timezone));
    $return  = array();
    $return['status'] = 0;


    $return['is_it_past_date_event'] = $now > $startTime ? 1 : 0;

    if ($duration > 0){
        $return['status'] = 1;
        $return['radio'] = 1;
        $return['disabled'] = 0;
        $return['tooltop'] = '';

        if ($duration <=4){
            $return['case'] = 1;
//        } elseif ($duration <=4){
//            $return['radio'] = 2;
//            $return['case'] = 2;
        } else {
            $return['radio'] = 2;
            $return['case'] = 4;
            if (!$_USER->isAdmin()){
                $return['disabled'] = 1;
                $return['tooltop'] = gettext('Only Global Administrators or Zone Administrators can change calendar block settings for events longer than 4 hours');
            }
        }
    }
    echo json_encode($return);
}

elseif (isset($_GET['getCalendarIframe']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    $form_title = "Copy Calendar iFrame";
    $params =  array();
    // Ancestor domain will be used for setting frame-ancestors CSP for iframe.
    $params['ancestor_domain'] = preg_replace('/[^a-z0-9\-\.]/','',strtolower($_GET['getCalendarIframe']));

    $params['created_on'] = time();
    $params['expires_after'] = time()+31536000; // Expire after one year
    $params['companyid'] = $_COMPANY->id();
    $params['zoneid'] = $_ZONE->id();
    $params['zoneids'] = !empty($_GET['zoneids'][0]) ? $_COMPANY->decodeIdsInCSV($_GET['zoneids'][0]) : $_ZONE->id();
    $params['groups'] = !empty($_GET['groups'][0]) ? ($_GET['groups'][0] == 'all' ? 'all' : $_COMPANY->decodeIdsInCSV($_GET['groups'][0])) : '';
    $params['chapterid'] = !empty($_GET['chapterid'][0]) ? ($_GET['chapterid'][0] == 'all' ? 'all' : $_COMPANY->decodeIdsInCSV($_GET['chapterid'][0])) : '';
    $params['eventType'] = !empty($_GET['eventType'][0]) ? ($_GET['eventType'][0] =='all' ? 'all' : $_COMPANY->decodeIdsInCSV($_GET['eventType'][0])) : '';
    $params['regionids'] = !empty($_GET['regionids'][0]) ? ($_GET['regionids'][0] == 'all' ? 'all' : $_COMPANY->decodeIdsInCSV($_GET['regionids'][0])) : '';
    $params['category'] = !empty($_GET['category'][0]) ? ($_GET['category'][0] == 'all' ? 'all' : $_GET['category'][0]) : 'ERG';
    $params['calendarDefaultView'] = !empty($_GET['calendarDefaultView']) ? $_GET['calendarDefaultView'] : 'month';
    $params['timezone']  =$_SESSION['timezone']?:'UTC';
    $params['calendarLang']  =$_COMPANY->getCalendarLanguage();

    $params['requireAuthToken'] = 0;
    if(filter_var($_GET['includeEncryption'], FILTER_VALIDATE_BOOLEAN)){
        $params['requireAuthToken'] = 1;
        $params['expires_after'] = time()+(31536000)*5; // 5 Years validity for secure URL's
    }

    // set everything in json and save in attributes_json
    $iframe_attributes_json = json_encode($params);

    // set iframe link
    $iframeLink = $_COMPANY->getiFrameURL($_ZONE->val('app_type')).'calendar?p='.$_COMPANY->encryptArray2String($params);
    
    include(__DIR__ . "/views/common/calendar_iframe_link.template.php");
    
}
elseif(isset($_GET['manageOrganizations']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $eventOrganizations = $event->getAssociatedOrganization() ?? array();
    $isActionDisabledDuringApprovalProcess = $event->isActionDisabledDuringApprovalProcess();

    // Organization delete is not allowed if (a) the event is in approval process or (b) organization is required and there is only one organization added to the event.
    $isDeleteOrganizationDisabled = $isActionDisabledDuringApprovalProcess || ((count($eventOrganizations) < 2) && $_COMPANY->getAppCustomization()['event']['partner_organizations']['is_required']);

    include(__DIR__ . "/views/templates/manage_organizations.template.php");
    exit();
}
elseif (isset($_GET['showOrgDropdown']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateEvent() && !$event->loggedinUserCanPublishEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    Http::Cache(60); // Cache results on browser side for 1 minute

    $searchTerm = (!empty($_GET['searchTerm'])) ? $_GET['searchTerm'] : '';
    $organizations = Organization::GetOrgDataBySearchTerm($searchTerm, 0, 100, true);

    $items = array();
    // Organization Name / Tax Id: Contact Name (email)
    $items[] = array('v'=>$_COMPANY->encodeId(0),'t'=>'Add New Organization');
   
     foreach ($organizations as $org) {
        //$contact_name = $org['contact_name'] ?? " ";
        $items[] = array('v'=>$_COMPANY->encodeId($org['organization_id']),'t'=>$org['organization_name'].' (Tax ID - '.$org['organization_taxid'].') ');
     }
     echo json_encode($items);
     exit();
}
elseif(isset($_GET['openAddOrUpdateOrgModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($organization_id = $_COMPANY->decodeId($_GET['organizationid']))<0 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        !isset($_GET['clone'])
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateEvent() && !$event->loggedinUserCanPublishEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $clone = (int)$_GET['clone'];
    $modalTitle = ($organization_id && !$clone) ? gettext("Update Partner Organization") : gettext("New Partner Organization");
    $orgFields = array();
    $event_custom_fields = [];
    $additional_contact_details = [];
    $latestOrgData = [];
    $additional_contact_count = 0;
    if ($organization_id){
        //step 1: Get the ORG data including api_org_id from our DB
        $org = Organization::GetOrganization($organization_id);

        // Get the organization data from partner path.
        $results = Organization::GetOrganizationFromPartnerPath($org->val('api_org_id'));
        $additional_data = Organization::FetchAdditionalDataOfOrg($organization_id, $eventid);
        if(isset($results['results']) && is_array($results['results'])){
            foreach ($results['results'] as $orgData) {
                $latestOrgData[] = [
                    'orgid' => $orgData['ID'],
                    'organization_name' => $orgData['Name'],
                    'organization_taxid' => $orgData['TaxID'],
                    'org_url' =>$orgData['Website'],
                    'organization_type' =>$orgData['OrganizationType'],
                    'is_claimed' => $orgData['IsClaimed'],
                    'city' => $orgData['City'],
                    'state' => $orgData['State'],
                    'street' => $orgData['Street'],
                    'country' => $orgData['Country'],
                    'zipcode' => $orgData['Zip'],
                    'contact_firstname' => $orgData['ContactFirstName'],
                    'contact_lastname' =>$orgData['ContactLastName'],
                    'contact_email'=>$orgData['ContactEmail'],  
                    'organisation_street'=>$orgData['Street'],
                    'cfo_firstname' => $orgData['CFOFirstName'],
                    'cfo_lastname' => $orgData['CFOLastName'],
                    'cfo_dob' => $orgData['CFODOB'],
                    'ceo_firstname' => $orgData['CEOFirstName'],
                    'ceo_lastname' => $orgData['CEOLastName'],
                    'ceo_dob' => $orgData['CEODOB'],
                    'bm1_firstname' => $orgData['bm1FirstName'],
                    'bm1_lastname' => $orgData['bm1LastName'],
                    'bm1_dob' => $orgData['bm1DOB'],
                    'bm2_firstname' => $orgData['bm2FirstName'],
                    'bm2_lastname' => $orgData['bm2LastName'],
                    'bm2_dob' => $orgData['bm2DOB'],
                    'bm3_firstname' => $orgData['bm3FirstName'],
                    'bm3_lastname' => $orgData['bm3LastName'],
                    'bm3_dob' => $orgData['bm3DOB'],
                    'bm4_firstname' => $orgData['bm4FirstName'],
                    'bm4_lastname' => $orgData['bm4LastName'],
                    'bm4_dob' => $orgData['bm4DOB'],
                    'bm5_firstname' => $orgData['bm5FirstName'],
                    'bm5_lastname' => $orgData['bm5LastName'],
                    'bm5_dob' => $orgData['bm5DOB'],
                    'number_of_board_members' => $orgData['NumberOfBoardMembers'],
                    'organization_mission_statement' => $orgData['MissionStatement'],
                    'company_organization_notes' => $org->val('company_organization_notes'),
                ];
            }
        }

        // fetch additional contact details if exists
        $additional_contact_details = json_decode($additional_data[0]['additional_contacts'] ?? '{}', true) ?? [];
        $event_custom_fields = json_decode($additional_data[0]['custom_fields'] ?? '{}', true) ?? [];
        // Keep a count of these for JS
        $additional_contact_count = $additional_contact_details ? count($additional_contact_details) : 0;
    }
    // Get all custom fields of ORGs
    $custom_fields = Organization::GetEventCustomFields();
    include(__DIR__ . "/views/templates/organization_modal_form.template.php");
    exit();
}
elseif (isset($_GET['addOrUpdateOrg']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($organizationid = $_COMPANY->decodeId($_POST['organization_id']))<0 ||
        ($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    // Authorization Check
    if (!$event->loggedinUserCanUpdateEvent() && !$event->loggedinUserCanPublishEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $organization_id = $_COMPANY->decodeId($_POST['organization_id']);
    $api_org_id = (int)$_COMPANY->decodeId($_POST['org_id']) ?? 0;

    // contacts data
    $contact_firstname = $_POST['contact_firstname'] ?? '';
    $contact_lastname = $_POST['contact_lastname'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';

    // Additional contacts
    $contacts = [];
    $add_contacts = $_POST['contacts'] ?? [];
    foreach ($add_contacts as $key => $contact) {
        $firstName = trim($contact['firstname']);
        $lastName = trim($contact['lastname']);
        $email = filter_var(trim($contact['email']), FILTER_SANITIZE_EMAIL);

        if(!empty($firstName) && !empty($lastName) && filter_var($email, FILTER_VALIDATE_EMAIL) ){
            $contacts[] = [
                'firstname' => $firstName,
                'lastname' => $lastName,
                'email' => $email,
            ];
        }
    }

    $contactsJSON = json_encode($contacts);
        
    // Basic data
    $organization_name = $_POST['organization_name'];
    $organization_taxid = $_POST['organization_taxid'];
    $organization_url = $_POST['organization_url'];
    $organization_type = $_POST['organization_type'] ?? "1";
    // Address data
    $address_street = $_POST['address_street'];
    $address_city = $_POST['address_city'];
    $address_state = $_POST['address_state'];
    $address_country = $_POST['address_country'];
    $address_zipcode = $_POST['address_zipcode'];

    $company_organization_notes = $_POST['company_organization_notes'];

    $custom_fields = ViewHelper::ValidateAndExtractCustomFieldsFromPostAttribute('ORG');

        // This adds a new ORG in teleskope and partner path.
    $addOrUpdateOrg = Organization::AddUpdateOrganization($organization_id, $organization_name, $organization_taxid, $address_street, $address_city, $address_state, $address_country, $address_zipcode, $organization_url, $organization_type, $contact_firstname, $contact_lastname, $contact_email, $api_org_id, $company_organization_notes);
    if($addOrUpdateOrg === -1){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Unable to update organization. Another organization with the given Tax ID already exists"), gettext('Error'));
    }elseif($addOrUpdateOrg){
        // add the org to event
        $addOrgForEvent = $event->updateEventOrganization($addOrUpdateOrg, $contactsJSON, $custom_fields);
        if($addOrgForEvent){
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Information updated successfully"), gettext('Success'));
        }else{
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Unable to add organization"), gettext('Error'));    
        }
    }else{
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Unable to update organization"), gettext('Error'));
    }
	exit();
}
elseif (isset($_GET['deleteOrgFromEvent'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL || 
        ($orgId = $_COMPANY->decodeId($_GET['organization_id']))<1 
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

     // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $event->removeEventOrganization($orgId);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Event Volunteer assigned successfully.'), gettext('Success'));
    
}
elseif(isset($_GET['manageVolunteers']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $refreshPage = intval($_GET['refreshPage'] ?? 1);
    $eventVolunteers = $event->getEventVolunteers();
    $eventVolunteerRequests = $event->getEventVolunteerRequests();
    include(__DIR__ . "/views/templates/volunteers_manage.template.php");
    exit();
}

elseif (isset($_GET['addUpdateEventVolunteerModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

	if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL || 
        ($volunteerUserId = $_COMPANY->decodeId($_GET['userid']))<0 ||
        ($volunteerTypeId = $_COMPANY->decodeId($_GET['volunteertypeid']))<0
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }


    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $eventVolunteerTypes = $event->getEventVolunteerRequests();
    $form_title = gettext("Add Event Volunteer");
    $volunteer = null;

    if ($volunteerUserId){
        $volunteer = User::GetUser($volunteerUserId);
        $form_title = gettext("Update Event Volunteer");
    }
   
    include(__DIR__ . "/views/templates/volunteer_add_update_form.template.php");
}


elseif (isset($_GET['searchUsersForEventVolunteer']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    // SearchUsersByKeyword is safe method and can handle unclean data, so no need for raw2clean.
    $activeusers =  User::SearchUsersByKeyword($_GET['search_keyword_user']);

	if(count($activeusers)>0){ ?>
		<select class="form-control userdata" name="userid" onchange="closeDropdown()" id="user_search" required >
			<option value=""><?= gettext('Select an user (maximum of 20 matches are shown below)');?></option>
<?php	for($a=0;$a<count($activeusers);$a++){  ?>
			<option value="<?= $_COMPANY->encodeId($activeusers[$a]['userid']); ?>" ><?= rtrim(($activeusers[$a]['firstname']." ".$activeusers[$a]['lastname'])," ")." (". $activeusers[$a]['email'].") - ".$activeusers[$a]['jobtitle']; ?></option>
<?php 	} ?>
		</select>

<?php }else{ ?>
		<select class="form-control userdata" name="userid" id="user_search" required>
			<option value=""><?= gettext("No match found.");?></option>
		</select>

<?php	}
}
elseif (isset($_GET['addOrUpdateEventVolunteer'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
     ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

     // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $check = array('User' => @$_POST['userid'],'Volunteer type'=>@$_POST['volunteertypeid']);
    $checkRequired = $db->checkRequired($check);
    if($checkRequired){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty!"),$checkRequired), gettext('Error!'));
    }

    $volunteertypeid = $_COMPANY->decodeId($_POST['volunteertypeid']);
    $personid = $_COMPANY->decodeId($_POST['userid']);

    $status = $event->addOrUpdateEventVolunteer($personid, $volunteertypeid);
    if ($status == -1) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Event Volunteer request capacity has been met'), gettext('Error'));
    } elseif ($status == 1 || $status == 2) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Event Volunteer assigned successfully.'), gettext('Success'));
    } elseif ($status == 3) {
        AjaxResponse::SuccessAndExit_STRING(1, '', '','Success'); // Already a member
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Internal server error'), gettext('Error'));
    }

}
elseif (isset($_GET['deleteEventVolunteer'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL || 
        ($volunteerUserId = $_COMPANY->decodeId($_GET['userid']))<0 ||
        ($volunteerid = $_COMPANY->decodeId($_GET['volunteerid']))<1
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($volunteerUserId === 0) {
        $volunteer = EventVolunteer::GetEventVolunteer($volunteerid);
        if (!$volunteer->isExternalVolunteer()) {
            header(HTTP_BAD_REQUEST);
            exit();
        }
        $volunteer->deleteIt();
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Event Volunteer deleted successfully'), gettext('Success'));
    }

     // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $event->removeEventVolunteer($volunteerUserId);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Event Volunteer assigned successfully.'), gettext('Success'));
    
}

elseif (isset($_GET['addUpdateEventVolunteerRequestModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

	if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL || 
        ($volunteertypeid = $_COMPANY->decodeId($_GET['volunteertypeid']))<0
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }


    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $eventVolunteerTypes = Event::GetEventVolunteerTypesForCurrentZone();
    $eventVolunteerTypes = array_column($eventVolunteerTypes,'type');

    $form_title = gettext("Volunteer Roles");
    $volunteerRequest = null;
    $type = '';
    if ($volunteertypeid){
        $form_title = gettext("Update Event Volunteer Role");
        $volunteerRequest = $event->getEventVolunteerRequestByVolunteerTypeId($volunteertypeid);
        $type = $event->getVolunteerTypeValue($volunteertypeid);
    }
    // Volunteering hours pre-fill based on event start and end time. check if the value pre exists in attribute.
    if(!$volunteerRequest){
        $event_start = new DateTime($event->val('start'));
        $event_end = new DateTime($event->val('end'));
        $event_duration_interval = $event_start->diff($event_end);

        $totalMinutes = ($event_duration_interval->days * 24 * 60) + ($event_duration_interval->h * 60) + $event_duration_interval->i;
        $totalHours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        
        //Formatting minutes to 0.{minutes}
        $formattedMinutes = $minutes > 0 ? sprintf('.%02d', $minutes) : '';
        $volunteeringHours = $totalHours . $formattedMinutes;
    }
   
    include(__DIR__ . "/views/templates/volunteer_request_add_update_form.template.php");
}

elseif (isset($_GET['addUpdateEventVolunteerRequest'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
     ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

     // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $check = array('Volunteer type'=>@$_POST['volunteer_type'],'Volunteer needed count' => @$_POST['volunteer_needed_count']);
    $checkRequired = $db->checkRequired($check);
    if($checkRequired){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty!"),$checkRequired), gettext('Error!'));
    }

    $volunteer_type = $_POST['volunteer_type'];
    $volunteer_needed_count = $_POST['volunteer_needed_count'];
    if ( $volunteer_needed_count <=0){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter a valid number of volunteers needed.'), gettext('Error'));
    }

    // Volunteer description
    $volunteer_description = $_POST['volunteer_description'];
    //Volunteer hours
    $volunteer_hours = empty($_POST['volunteer_hours']) ? 0 : $_POST['volunteer_hours'];
    $cc_email = '';
    if (!empty($_POST['cc_email']) && filter_var($_POST['cc_email'], FILTER_VALIDATE_EMAIL) && $_COMPANY->isValidEmail($_POST['cc_email'])){
        $cc_email = $_POST['cc_email'];
    } else {
        if (!empty($_POST['cc_email'])){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter a valid email.'), gettext('Error'));
        }
    }

    $hide_from_signup_page = 0;
    if (isset($_POST['hide_from_signup_page'])) {
        $hide_from_signup_page = $_POST['hide_from_signup_page'] == 1 ? 1 : 0;
    }

    $allow_external_volunteers = (int) ($_POST['allow_external_volunteers'] ?? 0) === 1;

    if ($hide_from_signup_page && $allow_external_volunteers) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('To allow employees to bring external volunteers, you need to uncheck "Hide from signup page"'), gettext('Error'));
    }

    $event->addUpdateEventVolunteerRequests($volunteer_type, $volunteer_needed_count, $volunteer_hours, $cc_email, $volunteer_description, $hide_from_signup_page, $allow_external_volunteers);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Updated successfully.'), gettext('Success'));
    
}

elseif (isset($_GET['deleteEventVolunteerRequest'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL || 
        ($volunteertypeid = $_COMPANY->decodeId($_GET['volunteertypeid']))<1 
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

     // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $event->deleteEventVolunteerRequest($volunteertypeid);

    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Event Volunteer request deleted successfully.'), gettext('Success'));
}
elseif (isset($_GET['confirmVolunteerSignup']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
	if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL || 
        ($volunteertypeid = $_COMPANY->decodeId($_GET['volunteertypeid']))<0
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $volunteerTypeData = $event->getEventVolunteerRequestByVolunteerTypeId($volunteertypeid);
    if($volunteerTypeData){
        $message = gettext("Are you sure you want to sign up for this role? If you've already signed up for any other Volunteering Roles for this event, you will be removed from those roles and added to this one.");
        if (array_key_exists('volunteer_description', $volunteerTypeData) && !empty($volunteerTypeData['volunteer_description'])) {
            $description_html = <<< EOMEOM
            <div style="border: 1px solid darkgray; margin: 20px 10px; padding: 10px; text-align: left; font-size: small;">
            <p>
                {$volunteerTypeData['volunteer_description']}
            </p>
            </div>
EOMEOM;
            $message = $description_html . $message;
        }
        //Logger::LogDebug($message);


         // $volunteerRequestCount = $volunteerTypeData['volunteer_needed_count'];
        AjaxResponse::SuccessAndExit_STRING(1, '', $message, gettext('Please confirm!'));
    }

    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Your request can't be processed. Try again later"), gettext('Error'));
    
}
elseif (isset($_GET['joinAsEventVolunteer']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

	if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL || 
        ($volunteertypeid = $_COMPANY->decodeId($_GET['volunteertypeid']))<0
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($event->checkScheduleConflictForSeriesEventForUser($_USER->id())){ // If Series events schedule conflicts, do not process request
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Your request can't be processed because you have a schedule conflict with this event."), gettext('Error'));
    }

    $status = $event->addOrUpdateEventVolunteer($_USER->id(), $volunteertypeid);
    if ($status == -1) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Event Volunteer request capacity has been met'), gettext('Error'));
    } elseif ($status == 1 || $status == 2) {
        $rsvpDetails = $event->getMyRSVPOptions();
        if ($rsvpDetails['my_rsvp_status'] ==0 && $status == 1) {
            if ($rsvpDetails['max_inperson'] >0 || $rsvpDetails['max_online'] >0){
                if ($rsvpDetails['available_inperson'] >0 ) {
                    $event->joinEvent($_USER->id(), Event::RSVP_TYPE['RSVP_INPERSON_YES'], 1,1);
                } elseif ($rsvpDetails['available_online'] >0 ) {
                    $event->joinEvent($_USER->id(), Event::RSVP_TYPE['RSVP_ONLINE_YES'], 1,1);
                } else {
                    if ($rsvpDetails['max_inperson'] >0) {
                        $event->joinEvent($_USER->id(), Event::RSVP_TYPE['RSVP_INPERSON_WAIT'], 1,1);
                    } else {
                        $event->joinEvent($_USER->id(), Event::RSVP_TYPE['RSVP_ONLINE_WAIT'], 1,1);
                    }                    
                }
            } else {
                $event->joinEvent($_USER->id(), Event::RSVP_TYPE['RSVP_YES'], 1,1);
            }
        }                  

        $eventVolunteerRequests = $event->getEventVolunteerRequests();
        $eventVolunteers = $event->getEventVolunteers();
        include(__DIR__.'/views/common/init_event_volunteers.widget.php');
        //AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Event Volunteer assigned successfully.'), gettext('Success'));
    } elseif ($status == 3) {
        AjaxResponse::SuccessAndExit_STRING(1, '', '', 'Success'); // Already a member
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Internal server error'), gettext('Error'));
    }
}

elseif (isset($_GET['approveEventVolunteer'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL || 
        ($volunteerid = $_COMPANY->decodeId($_GET['volunteerid']))<1 
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

     // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $status = 2; // approve; 3 for denied
    $event->updateEventVolunteer($volunteerid, $status);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Event Volunteer approved successfully.'), gettext('Success'));
    
}

elseif (isset($_GET['getEventDetailModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    $filterchapterid = 0;
    $filterchannelid = 0;
	if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        (!empty($_GET['chapterid']) && ($filterchapterid = $_COMPANY->decodeId($_GET['chapterid']))<0)||
        (!empty($_GET['channelid']) && ($filterchannelid = $_COMPANY->decodeId($_GET['channelid']))<0)
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
    } elseif($event->val('collaborating_groupids')){
        $collaboratedGroupIds = array_filter(explode(',',$event->val('collaborating_groupids')));
        $canViewContent = false;
        foreach( $collaboratedGroupIds as $gid) {
            if ($_USER->canViewContent($gid)) {
                $canViewContent = true;
                break;
            }
        }

        if (!$canViewContent) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }

    // Event Detail start
    $group = NULL;
    $chapters = NULL;
    $channels = NULL;
    $groupid = 0;
    $chapterid = 0;
    $channelid = 0;

    $event_series_name = null;
    $checkSeriesJoinStatus = null;
    $rsvp_restriction = Event::EVENT_SERIES_RSVP_RESTRICTION['ANY_NUMBER_OF_EVENTS'];
    $getSeriesAllEvents = [];
    if ($event->val('event_series_id')){
        $eventGroup = Event::GetEvent($event->val('event_series_id'));
        $event_series_name  = $eventGroup->val('eventtitle');
        $rsvp_restriction = $eventGroup->val('rsvp_restriction');
        $getSeriesAllEvents = Event::GetEventsInSeries($event->val('event_series_id'));
        if ($rsvp_restriction == Event::EVENT_SERIES_RSVP_RESTRICTION['SINGLE_EVENT_ONLY']){
            foreach($getSeriesAllEvents as $seriesEvent){
                $checkSeriesJoinStatus = $seriesEvent->getMyEventSeriesRsvpStatus();

                if ($checkSeriesJoinStatus && $checkSeriesJoinStatus['joinstatus'] && $checkSeriesJoinStatus['joinstatus'] != 3){
                    if ($checkSeriesJoinStatus['eventid'] == $eventid){
                        $checkSeriesJoinStatus = null;
                        continue;
                    }
                    break;
                }
            }
        }
    }
    
    $groupid 		= $event->val('groupid');
    $topicid = $eventid;

    if (!empty($_GET['showGlobalChapterOnly'])) {
        $_SESSION['showGlobalChapterOnly'] = 1;
    } elseif ($filterchapterid || !empty($_GET['showAllChapters'])) {
        unset($_SESSION['showGlobalChapterOnly']);
    }

    if (!empty($_GET['showGlobalChannelOnly'])) {
        $_SESSION['showGlobalChannelOnly'] = 1;
    } elseif ($filterchannelid || !empty($_GET['showAllChannels'])) {
        unset($_SESSION['showGlobalChannelOnly']);
    }

    $enc_eventid   = $_COMPANY->encodeId($eventid);
    $enc_groupid    = $_COMPANY->encodeId($groupid);
    $enc_chapterid   = $_COMPANY->encodeId($chapterid);
    $enc_channelid    = $_COMPANY->encodeId($channelid);

    if (!$event->isSeriesEventHead()){
        $joinersCount	= $event->getJoinersCount();
        $eventJoiners	= $event->getRandomJoiners(12);
    }
    $code 			= '';
    $collaboratedWithFormated = null;
    $collaboratedWith = null;
    $group 		= Group::GetGroup($groupid);

    if ($event->val('collaborating_groupids')){
        $collaboratedWithFormated = $event->getFormatedEventCollaboratedGroupsOrChapters();
        $collaboratedWith = Group::GetGroups(explode(',',$event->val('collaborating_groupids')),true, false);
    }

    $form_title = gettext("Event Link");
    $link = $_COMPANY->getAppURL($_ZONE->val('app_type')).'eventview?id='.$enc_eventid;
    $eventVolunteers = $event->getEventVolunteers();

    $allEventVolunteerRequests = $event->getEventVolunteerRequests();
    $eventVolunteerRequests = array();
    foreach($allEventVolunteerRequests as $key => $volunteer){
        if (isset($volunteer['hide_from_signup_page']) && $volunteer['hide_from_signup_page'] == 1) { // hide that role from listing
            continue;
        }
        $eventVolunteerRequests[]  = $volunteer;
    }
    
    if ($event->isSeriesEventHead()){
        include(__DIR__ . '/views/templates/event_series_detail_modal.template.php');
    } else { // Allow comment feature on on main event view
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
        if ($_COMPANY->getAppCustomization()['event']['comments']) {
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
        if ($_COMPANY->getAppCustomization()['event']['likes']) { 
            $myLikeType = Event::GetUserReactionType($topicid);
            $myLikeStatus = (int) !empty($myLikeType);
            $latestLikers = Event::GetLatestLikers($topicid);
            $totalLikers = Event::GetLikeTotals($topicid);
            $likeTotalsByType = Event::GetLikeTotalsByType($topicid);
            $showAllLikers = true;
            $likeUnlikeMethod = 'EventTopic';
        } 
        $showRsvpSussessMessage = false;
        $disclaimers = Disclaimer::GetDisclaimersByIdCsv($event->val('disclaimerids'));

        $lockOptions = false;
        $lockMessage = '';
        if ($event->isPublished() && !$event->hasEnded() && !Disclaimer::IsAllWaiverAccepted($event->val('disclaimerids'),$eventid)){
            $lockOptions = true;
            $lockMessage = gettext('In order to RSVP for this Event please accept the Event Waivers above.');
        }

        include(__DIR__ . "/views/templates/event_detail_modal.template.php");
    }
    exit();
}

elseif (isset($_GET['getGroupChaptersToInvite']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event=Event::GetEvent($eventid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$_USER->isAdmin()){
        // Authorization Check
        if (!$_USER->canPublishContentInCompanySomething()) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }
    $chapterList = [];
    if ($_COMPANY->getAppCustomization()['chapter']['enabled']){
        $chapterShortName = $_COMPANY->getAppCustomization()['chapter']["name-short"];
        $triggerTitle = sprintf(gettext("By %s"),$_COMPANY->getAppCustomization()['chapter']["name-short-plural"]);
        $chapterSelectTitle = sprintf(gettext("Select a %s"),$chapterShortName);
        $preSelected = array_merge(explode(',',$event->val('invited_chapters')),explode(',',$event->val('chapterid')));
        $chapters = Group::GetChapterList($groupid);
        foreach($chapters as $chapter){
            $disabled = '';
            $allowed = true;
            $optionSuffix = '';
            if (!$_USER->canPublishContentInGroupChapterV2($groupid,$chapter['regionids'],$chapter['chapterid'])) {
                $allowed = false;
                $disabled = 'disabled';
                $optionSuffix = '('.gettext('insufficient permissions').')';
                $helpText = '('.sprintf(gettext('Your role in this organization does not have the needed permissions to complete this action. Contact your %s leader directly for support.'),$_COMPANY->getAppCustomization()['group']['name-short']).')';
            }
            if (in_array($chapter['chapterid'],$preSelected)){
                $disabled = 'disabled';
                $optionSuffix = '('.gettext('invited').')';
            }
            $chapterList[] = array('id'=>$chapter['chapterid'],'name'=>$chapter['chaptername'],'disabled'=>$disabled,'allowed'=>$allowed,'optionSuffix'=>$optionSuffix);
        }

        if (!empty($chapterList)){ ?>
            <option value=''><?=$chapterSelectTitle; ?></option>
            <?php foreach($chapterList as $item){ ?>
                <option value="<?= $_COMPANY->encodeId($item['id'])?>" <?= $item['disabled']; ?> >
                    <?= $item['name']; ?> <?= $item['optionSuffix'] ?>
                </option>
            <?php } ?>
    <?php
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('No %s available'),$_COMPANY->getAppCustomization()['chapter']["name-short-plural"]), gettext('Error'));
        }

    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('%s feature is deisabled'),$_COMPANY->getAppCustomization()['chapter']["name-short"]), gettext('Error'));
    }
}

elseif (isset($_GET['leaveEventVolunteerEnrollment'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    /**
     * We are using the Read-Only DB here and not the Read-Write DB
     * As we did not make any INSERT/UPDATE/DELETE query on the event volunteers
     * So the RO-DB should be in-sync with the RW-DB
     */
    $external_volunteers = $event->getMyExternalVolunteers();
    if (count($external_volunteers)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please remove your added external volunteers first before unsigning yourself as a volunteer'), gettext('Error'));
    }

    $event->removeEventVolunteer($_USER->id());
    $eventVolunteerRequests = $event->getEventVolunteerRequests();
    $eventVolunteers = $event->getEventVolunteers();
    include(__DIR__.'/views/common/init_event_volunteers.widget.php');
}
elseif(isset($_GET['sendEmailToVolunteersModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $ventVolunteers = $event->getEventVolunteers();
    $eventVolunteerTypes = $event->getEventVolunteerRequests();
    $volunteerTypesGrouping = array();
    foreach($eventVolunteerTypes as $type){
        $volunteerType = Event::GetEventVolunteerType($type['volunteertypeid']);
        if (!$volunteerType){
            continue;
        }
        $v = array();
        foreach($ventVolunteers as $ventVolunteer){
            if ($ventVolunteer['volunteertypeid'] == $type['volunteertypeid']){
                $v[] = $ventVolunteer;
            }
        }
        $volunteerTypesGrouping[$volunteerType['type']] = $v;
    }
    $modalTitle = gettext("Send email to volunteers");
    include(__DIR__ . "/views/templates/send_email_volunteer_form.template.php");
    exit();
}

elseif(isset($_GET['sendEmailToVolunteers']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (Str::IsEmptyHTML($_POST['message'])) {
        $_POST['message'] = '';
    }

    // Start of Basic Data Validation
    $validator = new Rakit\Validation\Validator;

    $validation = $validator->validate($_POST, [
    'volunteerIds'   => 'required',
    'subject'  => 'required|min:3|max:255', //Alpha numeric with spaces and . _ -
    'message'  => 'required|min:3|max:8000'
    ]);

    if ($validation->fails()) {
        // handling errors
        $errors = array_values($validation->errors()->firstOfAll());
        // Return error message
        
        $e = implode(', ', $errors);
        AjaxResponse::SuccessAndExit_STRING(0, '', $e, gettext('Error'));
    }
	
    list ($companyVoluntererEmails, $externalVoluntererEmails) = (function () {
        global $_COMPANY;

        $companyVoluntererEmails = [];
        $externalVoluntererEmails = [];
        foreach ($_POST['volunteerIds'] as $volunteerid) {
            if (str_starts_with($volunteerid, 'external_volunteer:')) {
                $volunteerid = str_replace('external_volunteer:', '', $volunteerid);
                $volunteerid = $_COMPANY->decodeId($volunteerid);
                $volunteer = EventVolunteer::GetEventVolunteer($volunteerid);
                $externalVoluntererEmails[] = $volunteer->getVolunteerEmail();
            } else {
                $userid = $_COMPANY->decodeId($volunteerid);
                $user = User::GetUser($userid);
                $companyVoluntererEmails[] = $user->val('email');
            }
        }

        return array ($companyVoluntererEmails, $externalVoluntererEmails);
    })();

    $subject	= htmlspecialchars($_POST['subject']);
    $message    = ViewHelper::RedactorContentValidateAndCleanup($_POST['message']);

    // Send email ==> 
    $group = Group::GetGroup($event->val('groupid'));
    $app_type = $_ZONE->val('app_type');
    $reply_addr = $group->val('replyto_email');
    $from = $group->val('from_email_label');
    $email_content = EmailHelper::GetEmailTemplateForMessage('', '', $message, '');

    if ($_ZONE->val('email_settings') >= 2) {
        $emailsFailed = array();
        $emailsSent = array();

        foreach ($companyVoluntererEmails as $email) {
            $emailStatus = $_COMPANY->emailSend2($from, $email, $subject, $email_content, $app_type, $reply_addr);
            if (!$emailStatus) {
                $emailsFailed[] = $email;
            } else {
                $emailsSent[] = $email;
            }
        }
        foreach ($externalVoluntererEmails as $email) {
            $emailStatus = $_COMPANY->emailSendExternal($from, $email, $subject, $email_content, $app_type, $reply_addr);
            if (!$emailStatus) {
                $emailsFailed[] = $email;
            } else {
                $emailsSent[] = $email;
            }
        }

        if (!empty($emailsSent)){
            if (empty($emailsFailed)) {
                AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Message sent successfully") , gettext('Success'));
            } else {
                $failedEmailList = implode(',', $emailsFailed);
                AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext("Message sent successfully to all recipients except the following: %s"), $failedEmailList) , gettext('Success'));
            }
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("An error occurred while sending email. Please try again."), gettext('Error'));
        }
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Mailing service is not available. Please contact your administrator."), gettext('Error'));
    }
}

elseif (isset($_GET['searchUsersForEventCheckin']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    // SearchUsersByKeyword is safe method and can handle unclean data, so no need for raw2clean.
    $activeusers =  User::SearchUsersByKeyword($_GET['searchUsersForEventCheckin']);

	if(count($activeusers)>0){ ?>
		<select class="form-control userdata" name="userid" onchange="prePopulateUserDetail(this.value)" id="user_search" required >
			<option value=""><?= gettext('Select an user (maximum of 20 matches are shown below)');?></option>
<?php	for($a=0;$a<count($activeusers);$a++){  ?>
			<option value="<?= $_COMPANY->encodeId($activeusers[$a]['userid']); ?>" ><?= rtrim(($activeusers[$a]['firstname']." ".$activeusers[$a]['lastname'])," ")." (". $activeusers[$a]['email'].") - ".$activeusers[$a]['jobtitle']; ?></option>
<?php 	} ?>
		</select>
        <script>
            function prePopulateUserDetail(v){
                $.ajax({
                    type: "GET",
                    url: "ajax_events.php",
                    data: {
                        'prePopulateUserDetail' : v
                    },
                    success: function(data){
                        try {
                            let jsonData = JSON.parse(data);
                            $("#email").val(jsonData.email);
                            $("#firstname").val(jsonData.firstname);
                            $("#last_name").val(jsonData.lastname);
                            $("#email").prop("readonly", true);
                            $("#firstname").prop("readonly", true);
                            $("#last_name").prop("readonly", true);

                            var myDropDown=$("#user_search");
                            var length = $('#user_search> option').length;
                            myDropDown.attr('size',0);
                            $("#submit_checkin").show();
                            $("#userDetail").show();
                        } catch(e) {
                            $("#userDetail").hide();
                            $("#email").val('');
                            $("#firstname").val('');
                            $("#last_name").val('');
                            $("#email").prop("readonly", false);
                            $("#firstname").prop("readonly", false);
                            $("#last_name").prop("readonly", false);
                        }
                    }
			    });
            }
        </script>

<?php }else{ ?>
		<div class="text-center">
            <p calss="mt-2 ">
                <?= gettext("No user found with '".$_GET['searchUsersForEventCheckin']."' keyword.") ?> <button type="button" class="btn btn-xs btn-affinity" onclick="$('#userDetail').show();$('#submit_checkin').show();"><?= gettext('Enter user data manually')?></button>
            </p>
        </div>
        <script>
            $("#submit_checkin").hide();
            $("#userDetail").hide();
            $("#email").val('');
            $("#firstname").val('');
            $("#last_name").val('');
            $("#email").prop("readonly", false);
            $("#firstname").prop("readonly", false);
            $("#last_name").prop("readonly", false);
        </script>

<?php	}
}

elseif (isset($_GET['prePopulateUserDetail'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($uid = $_COMPANY->decodeId($_GET['prePopulateUserDetail']))<0 ||
        ($u = User::GetUser($uid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    echo json_encode(array('firstname'=>$u->val('firstname'), 'lastname'=>$u->val('lastname'),'email'=>$u->val('email')));
}

elseif (isset($_GET['checkPermissionAndMultizoneCollaboration']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    
    $eventid = $_COMPANY->decodeId($_POST['eventid']);

    if (empty($_POST['collaboratedGroupIds'])) {
        echo json_encode(array('infoMessage'=>'','sendCollaborationRequestMessage'=>''));
        exit();
    }
    
    $collaboratedGroupIds = $_POST['collaboratedGroupIds'];
    $host_groupid = $_COMPANY->decodeId($_POST['host_groupid']);
    if ($host_groupid) {
        $collaboratedGroupIds[] = $_POST['host_groupid'];
    }
    $event = Event::GetEvent($eventid);
    $existingCollaboratingGroupIds = $event ? (empty($event->val('collaborating_groupids')) ? array() : explode(',',$event->val('collaborating_groupids'))) : array();
    
    $infoMessage = '';
    $approvalNeededGroups = array();
    $approvalNeededChapters = array();
    $autoApproveCollaborationRequests = $_COMPANY->getAppCustomization()['event']['collaborations']['auto_approve'];

    foreach ($collaboratedGroupIds as $encId) {

        // Step G1: get the group and check if it is valid
        if (
            ($c_gid = $_COMPANY->decodeId($encId)) < 1 ||
            ($cGroup = Group::GetGroup($c_gid)) == null
        ) {
            continue;
        }

        // Step G2: check if the groups are the same zones, if not show different zone warning
        $sameZone = $cGroup->val('zoneid') == $_ZONE->id();
        if (!$sameZone && $infoMessage == ''){
            $infoMessage = gettext("This event is a collaboration event between multiple zones which might cover multiple timezones. Please choose a date time by taking different timezones into consideration.");
        }
        // if (isset($_POST['collaborating_chapterids']) && is_array($_POST['collaborating_chapterids']) && !empty($_POST['collaborating_chapterids'])) { // Skip gorup level permission check, we will check chapter level permission.
        //     continue;
        // }

        // Step G3.A: If auto approve is on, then do not process any further
        if ($autoApproveCollaborationRequests) {
            continue; // Auto approved
        }

        // Step G3.B: If group is already approved, then do not process any further
        if (in_array($cGroup->id(),$existingCollaboratingGroupIds)) {
            continue; // Already approved
        }

        // Step G3.C: If user is company admin or zone admin of target group, then do not process any further
        if ($_USER->isCompanyAdmin() || $_USER->isZoneAdmin($cGroup->val('zoneid'))) {
            continue; // Already accepted
        }

        // Step G3.D: If group is in current zone as user can publish content in it, then no further processing needed.
        $canPublish = $_USER->canPublishContentInGroupOnly($cGroup->id());
        if ($sameZone && $canPublish) {
            continue;
        }

        if (!$canPublish){
            $approvalNeededGroups[] = $cGroup->id();
        }
    }

    if (isset($_POST['collaborating_chapterids']) && is_array($_POST['collaborating_chapterids']) && !empty($_POST['collaborating_chapterids'])) 
    {
        $existingCollaboratingChapterids = $event ? (empty($event->val('chapterid')) ? array() : explode(',',$event->val('chapterid'))) : array();
        $collaborating_chapterids = $_COMPANY->decodeIdsInArray($_POST['collaborating_chapterids']);
        $collaborating_chapters = Group::GetChapterNamesByChapteridsCsv(implode(',',$collaborating_chapterids));
        foreach ($collaborating_chapters as $chapter) {

            if ($autoApproveCollaborationRequests) {
                continue; // Auto approved
            }

            if (in_array($chapter['chapterid'],$existingCollaboratingChapterids)) {
                continue; // Already accepted
            }

            if (!$_USER->canPublishContentInGroupChapterV2($chapter['groupid'],$chapter['regionids'], $chapter['chapterid'])) {
                $approvalNeededChapters[] = $chapter['chapterid'];
                // Chapter collaboration special check - Remove the corresponding group from required collaboration requests if we added chapter to required collaboration request
                $approvalNeededGroups = Arr::RemoveByValue($approvalNeededGroups, $chapter['groupid']);
            }
        }
    }

    $sendCollaborationRequestMessage = '';
    if (!empty($approvalNeededGroups)){
        $sendCollaborationRequestMessage = sprintf(gettext("The event includes %s that require approval for collaboration. Click the 'Send Collaboration Request' button to request collaboration."), $_COMPANY->getAppCustomization()['group']['name-short']);
    } elseif (!empty($approvalNeededChapters)){
        $sendCollaborationRequestMessage = sprintf(gettext("The event includes %s that require approval for collaboration. Click the 'Send Collaboration Request' button to request collaboration."), $_COMPANY->getAppCustomization()['chapter']['name-short-plural']);
    }

    echo json_encode(array('infoMessage' => $infoMessage,'sendCollaborationRequestMessage' => $sendCollaborationRequestMessage));
    exit();

}

elseif (isset($_GET['updateEventRecordingLink']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    require __DIR__ . '/views/events/event_recording_link.html.php';
    exit();

}

elseif (isset($_GET['submitUpdateEventRecordingLinkForm']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $event_recording_link = trim($_POST['event_recording_link']);
    $event_recording_note = trim($_POST['event_recording_note']);

    $is_user_trying_to_delete_link = $event->val('event_recording_link') && !$event_recording_link && !$event_recording_note;
    $is_user_trying_to_update_link = $event->val('event_recording_link') && $event_recording_link;
    $is_user_trying_to_add_link = !$event->val('event_recording_link') && $event_recording_link;

    if (!$is_user_trying_to_delete_link) {
        if (!Url::Valid($event_recording_link)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter a valid link'), gettext('Error'));
        } elseif (strlen($event_recording_note) > 128) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Maximum 128 characters allowed for note'), gettext('Error'));
        }
    }


    $event->updateEventRecordingLink($event_recording_link, $event_recording_note);
    $success_message = '';
    $success_message = $is_user_trying_to_update_link
                        ? gettext('Successfully updated event recording link')
                        : ($is_user_trying_to_delete_link
                            ? gettext('Successfully deleted event recording link')
                            : ($is_user_trying_to_add_link
                                ? gettext('Successfully added event recording link')
                                : gettext('Updated successfully')
                            )
                        );

    AjaxResponse::SuccessAndExit_STRING(1,
        ['event_recording_shareable_link' => $event_recording_link ? $event->getEventRecordingShareableLink() : ''],
        $success_message,
        gettext('Success')
    );

}

elseif (isset($_GET['confirmEventRecordingAttendance']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$event->val('rsvp_enabled')) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Unable to mark your attendance because this event does not allow RSVPs.'), gettext('Error'));
    }
    $link_stats = $event->getEventRecordingLinkClickStatsForUser($_USER->id());

    // Allow attendance to be marked after 30 minutes of first click.
    if (empty($link_stats['first_clicked_at']) || strtotime($link_stats['first_clicked_at']. ' UTC') > time() - 1800) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('You can mark your attendance 30 minutes after watching the recording'), gettext('Please try later'));
    }

    if ($event->checkInByUserid($_USER->id(), Event::EVENT_CHECKIN_METHOD['VIEWED_RECORDING'])) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Successfully marked attendance'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Unable to mark your attendance at this time, please try again later'), gettext('Error'));
    }
}

elseif(isset($_GET['updateEventExternalFacing']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        ($status = $_COMPANY->decodeId($_POST['status']))<0

    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canCreateOrPublishOrManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($status == 1 ) {
        $response = $event->enableExternalFacing();
        $message = gettext("External facing enabled successfully");
    } else {
        $response = $event->disableExternalFacing();
        $message = gettext("External facing disabled successfully");
    }
    if ($response){
        AjaxResponse::SuccessAndExit_STRING(1, '', $message, gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("An error occurred while updating status. Please try again."), gettext('Error'));
    }
    
}

elseif(isset($_GET['manageEventSurvey']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $eventSurveys = $event->getEventSurveys();
    $modalTitle = sprintf(gettext("Manage Event Surveys - %s"), $event->val('eventtitle')) ;
    include(__DIR__ . "/views/common/event_surveys/manage_event_surveys.template.php");
    exit();
}

elseif (isset($_GET['previewEventSurvey'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<0 ||
        ($event = Event::GetEvent($eventid)) === NULL  ) {
            header(HTTP_FORBIDDEN);
            exit();
    }
    $trigger = isset($_GET['trigger']) ? $_GET['trigger'] : '';
    $survey_json = json_encode(array());
    $surveyLanguages = array();
    if ($trigger){
        $surveyData = $event->getEventSurveyByTrigger($trigger);
        if ($surveyData) {
            $survey_json = json_encode($surveyData['survey_questions']);
        }
    }  else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Survey trigger missing"), gettext('error'));
    }
   
    $form_title = gettext("Preview Survey");
    include(__DIR__ . "/views/templates/survey_preview.template.php");
    
}

elseif(isset($_GET['saveEventSurvey']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    
    $survey_title = $_POST['survey_title'];
    $survey_trigger = $_POST['survey_trigger'];

    if (empty(trim($survey_title))){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Survey name is a required field"), gettext('Error'));
    }

    if (!in_array($survey_trigger,Event::EVENT_SURVEY_TRIGGERS)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please select a valid survey tirgger"), gettext('Error'));
    }

    $quesionJSON = $_POST['quesionJSON'];
    $quesionJSON = json_decode($quesionJSON,true);
    if (array_key_exists('navigateToUrl',$quesionJSON)){
        unset($quesionJSON['navigateToUrl']);
    }
    $quesionJSON = json_encode($quesionJSON);

    if (Survey2::HasDuplicateQuestionKey($quesionJSON)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Oops! We encountered an issue with your survey. Duplicate question keys, which are used as internal identifiers, were detected. To correct this, please remove all newly added questions, save the survey, and then add them back. We apologize for the inconvenience."), gettext('Error'));
    }
   
    $saveSurvey = $event->addUpdateEventSurvey($survey_title,$survey_trigger,$quesionJSON);
   
    if ($saveSurvey) {
        if ($event->val('groupid')){
            $redirectUrl = Url::GetZoneAwareUrlBase($_ZONE->id()).'manage?id='.$_COMPANY->encodeId($event->val('groupid'));
        } else {
            $redirectUrl = Url::GetZoneAwareUrlBase($_ZONE->id()).'manage_admin_contents';
        }
        AjaxResponse::SuccessAndExit_STRING(1, $redirectUrl, gettext("Event survey data saved successfully"), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again"), gettext('Error'));
    
}


elseif(isset($_GET['activateEventSurvey']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    
   
    $survey_trigger = $_POST['trigger'];

    if (!in_array($survey_trigger,Event::EVENT_SURVEY_TRIGGERS)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please select a valid survey tirgger"), gettext('Error'));
    }
    if ($event->activateDeactivateEventSurvey($survey_trigger,'activate')) {
        AjaxResponse::SuccessAndExit_STRING(1,'', gettext("Event survey activated successfully"), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again"), gettext('Error'));
    
}

elseif(isset($_GET['deActivateEventSurvey']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    
   
    $survey_trigger = $_POST['trigger'];

    if (!in_array($survey_trigger,Event::EVENT_SURVEY_TRIGGERS)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please select a valid survey tirgger"), gettext('Error'));
    }
    if ($event->activateDeactivateEventSurvey($survey_trigger,'deactivate')) {
        AjaxResponse::SuccessAndExit_STRING(1,'', gettext("Event survey de-activated successfully"), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again"), gettext('Error'));
    
}

elseif (isset($_GET['getPreJoinEventSurvey'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<0 ||
        ($event = Event::GetEvent($eventid)) === NULL  ) {
            header(HTTP_FORBIDDEN);
            exit();
    }
    $joinStatus = (int)$_GET['joinStatus'];

    if (in_array($joinStatus,  Event::RSVP_TYPE)){
        $survey_trigger = Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT'];
        $surveyData = $event->getEventSurveyByTrigger($survey_trigger, $joinStatus);


        // If the survey has question based on logic and no questions are visible, then survey.js will show
        // empty survey message and block the user from submitting a response. A work around is to add an empty
        // html question (which does not require a response at the end) of survey question. Since the question is
        // empty it does not impage the visuals.
        $surveyPageCount = count($surveyData['survey_questions']['pages']);
        if ($surveyPageCount) {
            $surveyData['survey_questions']['pages'][$surveyPageCount - 1]['elements'][] = array(
                'type' => 'html',
                'name' => 'thanks_html',
                'html' => '<span style="text-align:center;">&nbsp;</span>');
        }

        if ($surveyData){
            include(__DIR__ . "/views/common/event_surveys/event_join_survey.template.php");
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("No pre event join survey found."), gettext('error'));
        }
       
    }  else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('error'));
    }
    
}

elseif (isset($_GET['initUpdateEventSurveyResponses'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<0 ||
        ($event = Event::GetEvent($eventid)) === NULL  ) {
            header(HTTP_FORBIDDEN);
            exit();
    }
    $survey_trigger = $_GET['survey_trigger'];
    if (!in_array($survey_trigger,Event::EVENT_SURVEY_TRIGGERS)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Survey not found!"), gettext('Error'));
    }

    $joinStatus = (int)$_GET['joinStatus'];

    if (in_array($joinStatus,  Event::RSVP_TYPE)){
        $surveyResponses = $event->getEventSurveyResponsesByTrigger($survey_trigger) ?? array();
        $surveyQuestions = $event->getEventSurveyByTrigger($survey_trigger, $joinStatus);

        // If the survey has questions based on logic and no questions are visible, then survey.js will show
        // empty survey message and block the user from submitting a response. A work around is to add an empty
        // html question (which does not require a response at the end) of survey question. Since the question is
        // empty it does not impage the visuals.
        $surveyPageCount = count($surveyQuestions['survey_questions']['pages']);
        if ($surveyPageCount) {
            $surveyQuestions['survey_questions']['pages'][$surveyPageCount - 1]['elements'][] = array(
                'type' => 'html',
                'name' => 'thanks_html',
                'html' => '<span style="text-align:center;">&nbsp;</span>');
        }

        $form_title = gettext("Update Survey");
        include(__DIR__ . "/views/common/event_surveys/update_event_survey.template.php");
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("RSVP option is not valid. Please try again."), gettext('error'));
    }

}
elseif(isset($_GET['updatePostEventSurveyResponses']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $survey_trigger = $_POST['survey_trigger'];
    if (!in_array($survey_trigger,Event::EVENT_SURVEY_TRIGGERS)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Survey not found!"), gettext('Error'));
    }
    $responseJson = $_POST['responseJson'];
    $survey_response = $updateSurveyResponses = $event->updateEventSurveyResponse($_USER->id(), $survey_trigger, $responseJson);
    if ($survey_response < 0) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Unable to save your survey response at this time, please try again later'), gettext('Error'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(1, array($_COMPANY->encodeId(0), $_COMPANY->encodeId(0)), gettext("Post-event survey responses saved successfully"), gettext('Success'));
    }
    

}
elseif (isset($_GET['createEventSurveyCreateUpdateURL'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<0 ||
        ($event = Event::GetEvent($eventid)) === NULL  ) {
            header(HTTP_BAD_REQUEST);
            exit();
    }
    $eventid    = $_GET['eventid'];
    $trigger    = $_GET['trigger'];
    $parentUrl  = $_GET['parentUrl'];
    $templateid = $_GET['templateid'];
    // Apend evnet id to return UrL
    $parentUrlArray = explode("#",$parentUrl);
    $urlSring = $parentUrlArray[0];
    $parameterSeparator = '&';
    if (Url::IsUrlPathOfType($urlSring, 'manage_admin_contents')) {
        $parameterSeparator  = '?';
    }
    if (count($parentUrlArray)>1) { // if # exist in array
        $hashValue = "#".$parentUrlArray[1];
    } else {
        $hashValue = "";
    }
    $parentUrl = $urlSring.$parameterSeparator."subjectEventId=".$eventid.$hashValue;

    echo "create_event_survey?eventid=".htmlspecialchars($eventid)."&trigger=".htmlspecialchars($trigger)."&templateid=".htmlspecialchars($templateid)."&rurl=".base64_url_encode($parentUrl);
    exit();
}

elseif (isset($_GET['search_partner_organization'])){

    if (!$_COMPANY->getAppCustomization()['event']['partner_organizations']['enabled']) exit();

    $searchResponses = array();
    if (isset($_GET['keyword']) && strlen(trim($_GET['keyword']))>=2) {

        $keyword = trim($_GET['keyword']);
        $searchResults = Organization::GetOrgDataBySearchTerm($keyword, 0, 10, false);
        if (!empty($searchResults)) {
            $formatedData = array();
            foreach( $searchResults as $result) {
                $formatedData[] = array (
                    "id"=>$_COMPANY->encodeId($result['organization_id']),
                    "text" =>$result['organization_name']
                );
            }
            $searchResponses['results'] = $formatedData;
        }
    }
    echo json_encode($searchResponses);
    exit();
}

elseif (isset($_GET['openChooseSurveyTemplateModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<0 ||
        ($event = Event::GetEvent($eventid)) === NULL  ) {
            header(HTTP_FORBIDDEN);
            exit();
    }
    $survey_trigger = $_GET['trigger'];

    if (!in_array($survey_trigger,Event::EVENT_SURVEY_TRIGGERS)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please select a valid survey tirgger"), gettext('Error'));
    }
    $templateSurveys= Survey2::GetSurveyTemplate(Survey2::SURVEY_TYPE['GROUP_MEMBER']);
    if (empty($templateSurveys)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("No survey template found"), gettext('Error'));
    }
    $form_title = gettext("Select survey template");
    include(__DIR__ . "/views/common/event_surveys/choose_survey_template.template.php");
      
}
elseif (isset($_GET['processEventSurveyLink'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (
        ($eventid = $_COMPANY->decodeId($_GET['eventid']))<0 ||
        ($event = Event::GetEvent($eventid)) === NULL  
        )
    {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $survey_trigger = $_GET['trigger'];

    if (!in_array($survey_trigger,Event::EVENT_SURVEY_TRIGGERS)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("This survey link does not have a valid survey trigger"), gettext('Error'));
    }
    
    if (Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT'] == $survey_trigger){
        if (!$event->isActive()){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("This event is not active yet and the survey is not available for response"),'');
        }elseif ($event->hasEnded()){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("This event is over and the survey responses have been closed"),'');
        }
    }

    if (Event::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT'] == $survey_trigger && !$event->hasEnded()){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("This event is not over yet. Please come back and respond to the survey after the event is over."), '');
    }
    $joinStatus = $event->getMyRsvpStatus();
    $isSurveyResponded = !empty($event->getEventSurveyResponsesByTrigger($survey_trigger));
    
    if (Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT'] == $survey_trigger &&  !$isSurveyResponded){

        if (!$joinStatus){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please RSVP to the event first, as the Event survey is only available after your RSVP.'), '');
        }

        $surveyData = $event->getEventSurveyByTrigger(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT']);
        if ($surveyData){
            include(__DIR__ . "/views/common/event_surveys/event_join_survey.template.php");
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("No pre event join survey found."), gettext('error'));
        }
    } else {
        $surveyResponses = $event->getEventSurveyResponsesByTrigger($survey_trigger) ?? array();
        $surveyQuestions = $event->getEventSurveyByTrigger($survey_trigger);
        $form_title = gettext("Update Survey");
        include(__DIR__ . "/views/common/event_surveys/update_event_survey.template.php");
    } 
}

elseif (isset($_GET['downloadEventSurveyResponses']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    set_time_limit(120);
    //Data Validation
    if (
        (($eventid = $_COMPANY->decodeId($_GET['eventid']))<0 ) ||
        ($event = Event::GetEvent($eventid)) === null ||
        ($survey_trigger = $_GET['downloadEventSurveyResponses']) == '' ||
        (!in_array($survey_trigger,Event::EVENT_SURVEY_TRIGGERS)))
    {
        header(HTTP_BAD_REQUEST);
        exit('Bad Request');
    }
    // Authorization Check
    if (!$_COMPANY->getAppCustomization()['event']['enable_event_surveys']) {
        header(HTTP_FORBIDDEN);
        exit('Forbidden');
    }
   
    // Authorization Check
    if ($event->val('groupid')) {
        if (!$_USER->canManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid'))) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } elseif (!$event->loggedinUserCanManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $meta = ReportEventSurveyData::GetDefaultReportRecForDownload();
    

    $meta['Filters']['eventid'] = $eventid;
    $meta['Filters']['survey_trigger'] = $survey_trigger;

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'event_survey';
    $record['reportmeta'] = json_encode($meta);

    $report = new ReportEventSurveyData ($_COMPANY->id(),$record);
    $report_file = $report->generateReport(Report::FILE_FORMAT_CSV, false);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    echo false;
    exit();
}

elseif(isset($_GET['refreshEventRSVPWidget']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($subjectEvent = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $showRsvpSussessMessage = false;
    include(__DIR__.'/views/common/join_event_rsvp.template.php');
}

elseif (isset($_GET['getGroupsForCollaboration'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<0 ||
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<0  ) {
            header(HTTP_FORBIDDEN);
            exit();
    }
    $event= null;
    if ($eventid){
        $event = Event::GetEvent($eventid);
    }
    $alreadyCollaborating = array();
    $acceptancePending = array();
    $groupids_collaborating_chapters  = array();

    if ($event){
        //$groupid = $event->val('groupid');
        $alreadyCollaborating = array_filter(explode(',',$event->val('invited_groups')??''));
        if ($groupid) {
            $alreadyCollaborating[] = $groupid; // Becuase parent group id is not changeable(disabled) now on dropdown, so need to add manually for validation
        }
        $acceptancePending = array_filter(explode(',',$event->val('collaborating_groupids_pending')??''));
    }
    $displayStyle = 'row';

    // For section, exclude groups that the user is not a member of
    $groups = Group::GetAllGroupsForZoneCollaboration();

    include(__DIR__ . "/views/common/collaboration/init_groups_collaboration.template.php");

}


elseif (isset($_GET['getChaptersForCollaborations'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    $event= null;
    $alreadyCollaborating  = array();
    $acceptancePending = array();
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))>0){
        $event = Event::GetEvent($eventid);
        $alreadyCollaborating = array_filter(explode(',',$event->val('invited_chapters')??''));
        $acceptancePending = array_filter(explode(',',$event->val('collaborating_chapterids_pending')??''));
    }

    $decodedGroupidsArray = array();
    if (!empty($_GET['groupids'])){
        $groupidsArray = explode(',',$_GET['groupids']);
        $gids = array();

        foreach($groupidsArray as $groupid){
            $gids[] = $_COMPANY->decodeId($groupid);
        }
        $decodedGroupidsArray = $gids;
    }
    $chapterCollaborationError = null;
    if (empty($decodedGroupidsArray)) {
        $chapterCollaborationError = sprintf(gettext('Please select %1$s to get %2$s for collaboration'),$_COMPANY->getAppCustomization()['group']['name-short-plural'],$_COMPANY->getAppCustomization()['chapter']['name-short-plural']);
    }
    $host_groupid = $_COMPANY->decodeId($_GET['host_groupid'])?:0;
    if (!$chapterCollaborationError){
        $decodedGroupidsArray[] = $host_groupid;

        $groupIdsCsv = implode(',',$decodedGroupidsArray);
        $chaptersForCollaboration = Group::GetChapterByGroupsAndRegion($groupIdsCsv,0,false);
        if (empty($chaptersForCollaboration)){
            $chapterCollaborationError = sprintf(gettext('There are no %s available for collaboration'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural']);
        }
    }
    $displayStyle = 'row';
    include(__DIR__ . "/views/common/collaboration/init_chapters_list_collaboration.template.php");

}

## show a new event create/update form form
elseif (isset($_GET['getEventCreateUpdateForm'])){

    $eventid =0;
    $event = null;
    $chapters = array();
    $channels = array();
    $selectedChapterIds = array();
    $selectedChannelId = 0;
    $global = 0;
    $seriesEvent = null;
    $lists = array();
    $partnerOrganization = null;
    $event_custom_fields = array();
    $isItPastDateEvent = 0;
    $hasLimitedParticipation = $_COMPANY->getAppCustomization()['event']['event_form']['enable_participation_limit_by_default'] ? 1 : 0;
    $eventVersion = 0;
    $action= 'add';
    $displayStyle = 'row12';
    $zoneGroups = array();
    $use_and_chapter_connector = false;
    $allowEventCollaboration = true;
    $disclaimerids = array();
    $parent_groupid = $_GET['parent_groupid'] ? $_COMPANY->decodeId($_GET['parent_groupid']) : 0;

    $zoneGroups = Group::GetAllGroupsByZones([$_ZONE->id()]);
    $allowTitleUpdate = true;
    $allowDescriptionUpdate = true;
    
    if (isset($_GET['eventid']) && ($eventid = $_COMPANY->decodeId($_GET['eventid']))>0){
        //Data Validation
        if (($event = Event::GetEvent($eventid)) === NULL
        ){
            header(HTTP_BAD_REQUEST);
            exit();
        }
        $action= 'update';
        $groupid = $event->val('groupid');
        $selectedChapterIds = explode(',',$event->val('chapterid'));
        if (empty($selectedChapterIds)) {
            $selectedChapterIds = array(0);
        }
        $selectedChannelId = $event->val('channelid');
        // Authorization Check
        if ($event->val('groupid')) {
            if (!$_USER->canUpdateContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid'),$event->val('isactive'))
            ) { //Allow creators to edit unpublished content
                header(HTTP_FORBIDDEN);
                exit();
            }
        } elseif (!$event->loggedinUserCanUpdateEvent()) {
            header(HTTP_FORBIDDEN);
            exit();
        }

        $eventFromTitle = gettext('Update Event');
        $event_series_id = $event->val('event_series_id');
        if ($event_series_id){
            $eventFromTitle = gettext('Update Sub Event');
        }

        if ($event_series_id < 1){
            $chapters = Group::GetChapterListByRegionalHierachies($event->val('groupid'));
            $channels= Group::GetChannelList($event->val('groupid'));
        }

        $event_tz = (!empty($event->val('timezone'))) ? $event->val('timezone') : (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
        #Check either event is multiday or single day
        #Start Date
        $s_date = $db->covertUTCtoLocalAdvance("Y-m-d", '', $event->val('start'), $event_tz);
        $s_hrs = $db->covertUTCtoLocalAdvance("h", '', $event->val('start'), $event_tz);
        $s_mmt = $db->covertUTCtoLocalAdvance("i", '', $event->val('start'), $event_tz);
        $s_prd = $db->covertUTCtoLocalAdvance("A", '', $event->val('start'), $event_tz);

        #End Date
        $e_date = '';
        $e_hrs = '';
        $e_mnt = '';
        $e_prd = '';

        if ($event->getDurationInSeconds() > 86400){ #Multiday event
            $e_date = $db->covertUTCtoLocalAdvance("Y-m-d", '', $event->val('end'), $event_tz);
            $e_hrs = $db->covertUTCtoLocalAdvance("h", '', $event->val('end'), $event_tz);
            $e_mnt = $db->covertUTCtoLocalAdvance("i", '', $event->val('end'), $event_tz);
            $e_prd = $db->covertUTCtoLocalAdvance("A", '', $event->val('end'), $event_tz);

        } else { #One day event
            $diff = $db->roundTrimTimeDiff($event->val('start'). ' '. $event_tz, $event->val('end'). ' '. $event_tz);
            $e_hrs = $diff[0];
            $e_mnt = $diff[1];
        }
        if ($event->val('form_validated')){
            $hasLimitedParticipation = ((int)$event->val('max_inperson')+ (int)$event->val('max_online')) > 0;
        }

        if ($event->val('custom_fields')){
            $event_custom_fields = json_decode($event->val('custom_fields'),true);
        }

        if (strtotime($event->val('start'). ' '.$event_tz) < time()){
            $isItPastDateEvent = 1;
        }

        // Integration
        $published_integrations = GroupIntegration::GetIntegrationidsByRecordKey("EVT_{$eventid}") ?? array();
        $integrations = array();
        if ($event->val('collaborating_groupids') && $event->val('groupid') == 0) { // Collaborating event
            $collaborating_groupids = explode(',',$event->val('collaborating_groupids'));
            foreach($collaborating_groupids as $collaborating_groupid){
                $integ =  $event->isPrivateEvent() ? array() : GroupIntegration::GetUniqueGroupIntegrationsByExternalType($collaborating_groupid,$event->val('chapterid'), $event->val('channelid'),$published_integrations,'events');
                $integrations = array_merge( $integrations,$integ);
            }
            $integrations = array_unique($integrations, SORT_REGULAR);
        } else { // Global Event or Group Event
            $integrations =  $event->isPrivateEvent() ? array() : GroupIntegration::GetUniqueGroupIntegrationsByExternalType($event->val('groupid'),$event->val('chapterid'), $event->val('channelid'),$published_integrations,'events');
        }

        $alreadyCollaborating = array_filter(explode(',',$event->val('invited_groups')??''));
        $acceptancePending = array_filter(explode(',',$event->val('collaborating_groupids_pending')??''));
      
        if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {
            if ($event->val('groupid')){
                $lists = DynamicList::GetAllLists('group',true);
            } else {
                $lists = DynamicList::GetAllLists('zone',true);
            }
        }
        // $partnerOrganization = Organization::GetOrganization($event->val('organization_id'));
        $eventVersion = $event->val('version');

        $use_and_chapter_connector = $event->val('use_and_chapter_connector'); 

        if (!empty($event->val('disclaimerids'))) {
            $disclaimerids = explode(',', $event->val('disclaimerids'));
        }

        $approval = $event->getApprovalObject();
        if ($approval) {
            [$allowTitleUpdate,$allowDescriptionUpdate] = $approval->canUpdateAfterApproval();
        }

    } else {
    
        if(isset($_GET['global']) && $_GET['global'] ==1){
            $global = 1;
            $groupid = 0;
    
            if (!$_USER->isAdmin()) {
                header(HTTP_FORBIDDEN);
                exit();
            }
            if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {
                $lists = DynamicList::GetAllLists('zone',true);
            }
            $allowEventCollaboration = false;
        } else {
            //Data Validation
            if (($groupid = $_COMPANY->decodeId($_GET['getEventCreateUpdateForm']))<0) {
                header(HTTP_BAD_REQUEST);
                exit();
            }
            // Authorization Check
            if (!$_USER->canCreateContentInGroupSomething($groupid)) {
                header(HTTP_FORBIDDEN);
                exit();
            }
            if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {
                $lists = DynamicList::GetAllLists('group',true);
            }
        }
        $parent_groupid = $groupid;
        $event_series_id = 0;
        $eventFromTitle = gettext("New Event");
        if (isset($_GET['event_series_id'])){
            $event_series_id = $_COMPANY->decodeId($_GET['event_series_id']);
            $seriesEvent = Event::GetEvent($event_series_id);
            $global = ($seriesEvent && $seriesEvent->val('groupid') == 0) ? 1 : 0;
            $eventFromTitle = gettext("New Sub Event");
        }
        $event_tz = $_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC';
    }

    $group = Group::GetGroup($groupid);
    $fontColors = $groupid > 0 ? json_encode(array($group->val('overlaycolor'),$group->val('overlaycolor2'))) : json_encode(array());
   
    $custom_fields = Event::GetEventCustomFields();
    $type = Event::GetEventTypesByZones([$_ZONE->id()]);

    if($_COMPANY->getAppCustomization()['chapter']['enabled']){
        $chapters = Group::GetChapterListByRegionalHierachies($groupid);
    }

    if($_COMPANY->getAppCustomization()['channel']['enabled']){
        $channels= Group::GetChannelList($groupid);
    }

    $disclaimerWaivers = array();
    if ($_COMPANY->getAppCustomization()['disclaimer_consent']['enabled']) {
        $disclaimerWaivers = Disclaimer::GetAllDisclaimersInZone(Disclaimer::DISCLAIMER_INVOCATION_TYPE['LINK'],true);
    }

    if ($groupid && $group->val('group_type') == Group::GROUP_TYPE_INVITATION_ONLY) {
        $allowEventCollaboration = false;
    }

    $isActionDisabledDuringApprovalProcess = $event ? $event->isActionDisabledDuringApprovalProcess() : false;

    include(__DIR__ . '/views/events/event_create_update_form.template.php');
    
}

elseif (isset($_GET['getCollaborationRequestApprovers']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    $topicType = $_GET['topicType'];
    $topicId = $_COMPANY->decodeId($_GET['topicId']);

    $topic = null;
    $modelTitle = '';
    if ($topicId){
        if ($topicType == 'EVT') {
            $topic = Event::GetEvent($topicId);
            $modelTitle = sprintf(gettext("Choose the approvers to approve the event collaboration"));
        }
    }
    $topicEnglish = Teleskope::TOPIC_TYPES_ENGLISH[$topicType];
    if (!$topic) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s not found"),$topicEnglish), gettext('error'));
    }
    $groupsWithChapterSelected = array();
    $collaborating_groupids_pending = $topic->val('collaborating_groupids_pending');
    $collaborating_chapterids_pending = $topic->val('collaborating_chapterids_pending');
    
    if ($collaborating_chapterids_pending){
        $chapterToInvites = array();
        $collaborating_chapters = Group::GetChapterNamesByChapteridsCsv($collaborating_chapterids_pending);
        foreach ($collaborating_chapters as $chapter) {
            if (!$_USER->canPublishContentInGroupChapterV2($chapter['groupid'],$chapter['regionids'], $chapter['chapterid'])) {
                $chapterToInvites[$chapter['groupid']][] = $chapter['chapterid'];
            }
            $groupsWithChapterSelected[] = $chapter['groupid'];
        }
    } 
    $groupsWithChapterSelected = array_unique($groupsWithChapterSelected);
    $groupToInvites = array();
    $collaborating_groupids_pending = array_filter(explode(',',$collaborating_groupids_pending));
    if (empty($collaborating_groupids_pending) && !empty($groupsWithChapterSelected)) { // If there are no groups in the pending list but chapters require approval, assign them to a pending group to enable the topic collaboration request feature.
        $collaborating_groupids_pending = $groupsWithChapterSelected;
    }
    foreach ($collaborating_groupids_pending as $c_gid) {
        if ($c_gid && (($cGroup = Group::GetGroup($c_gid)) !== null )){

            if (isset($chapterToInvites[$c_gid])) { // If group have any chapter needs approvel then group level lead can also approve it
                $groupToInvites[$c_gid] = $cGroup;
                continue;
            }

            $canPublish = $_USER->canPublishContentInGroupOnly($c_gid);

            if (
                    $_USER->isCompanyAdmin()
                    ||
                    $_USER->isZoneAdmin($cGroup->val('zoneid'))
                    ||
                    ($canPublish)
            ) {
                continue;
            }
            $groupToInvites[$c_gid] = $cGroup;
        }
    }

     //Add the approved group to the request process if there is a pending chapter for that group.
    $collaborating_groupids = explode(',',$topic->val('collaborating_groupids'));
    foreach($collaborating_groupids as $gid) {
        if ($gid){
            $chapterLevelApprovalNeeded = Group::GetGroupChaptersFromChapterIdsCSV($gid, $topic->val('collaborating_chapterids_pending')??'');
            if (!empty($chapterLevelApprovalNeeded)){
                $groupToInvites[$gid] = Group::GetGroup($gid);
            }
        }
    }

    include(__DIR__ . "/views/common/collaboration/approver_selection_for_topic_group_collaboration.template.php");
    
}
elseif (isset($_GET['approveEventGroupCollaboration'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (
        ($eventid = $_COMPANY->decodeId($_GET['eventid']))<0 ||
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($event = Event::GetEvent($eventid)) == NULL
        ) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $cgids = $event->val('collaborating_groupids');
    $cgidsPending = $event->val('collaborating_groupids_pending');
    $collaboratingGroupIds = empty($cgids) ? array() : explode(',', $cgids);
    $collaboratingGroupIdsPending = empty($cgidsPending) ? array() : explode(',', $cgidsPending);

    if (!$_USER->isCompanyAdmin() && !$_USER->isZoneAdmin($group->val('zoneid')) && !$_USER->canCreateOrPublishOrManageContentInScopeCSV($groupid)) {
        $message = gettext("Your role does not have enough permissions to approve this event");
        $success = 0;
    } else {
        $filterGroupChaptersApprovedCollaboration =  Group::GetGroupChaptersFromChapterIdsCSV($groupid,$event->val('chapterid'));
        if (($key = array_search($groupid, $collaboratingGroupIdsPending)) === false && empty($filterGroupChaptersApprovedCollaboration)) {
            $message = gettext("This approval request was already processed. Thank you for showing interest.");
            $success = 1;
        } else{
            $collaboratingGroupIds[] = $groupid; // Add it to collaborating list
            sort($collaboratingGroupIds);

            unset($collaboratingGroupIdsPending[$key]); // Remove the value from pending list
            
            //Chapter Collaboration
            $approvedChapterIds = explode(',',$event->val('chapterid'));
            $collaborating_chapterids_pending = explode(',', $event->val('collaborating_chapterids_pending')?:'');
            $success = 1;
            if (!empty($event->val('collaborating_chapterids_pending'))){
                $filterGroupChaptersPendingCollaboration =  Group::GetGroupChaptersFromChapterIdsCSV($groupid,$event->val('collaborating_chapterids_pending'));
                if (!empty($filterGroupChaptersPendingCollaboration)) {
                    $chapterIdsToRemoveFromPending = explode(',', $filterGroupChaptersPendingCollaboration);
                    $collaborating_chapterids_pending = array_values(array_diff($collaborating_chapterids_pending, $chapterIdsToRemoveFromPending));
                    sort($collaborating_chapterids_pending);

                    $approvedChapterIds = array_merge($approvedChapterIds,$chapterIdsToRemoveFromPending);
                    sort($approvedChapterIds);
                    $success = 2;
                }
            }

            if ($event->updateCollaboratingGroupids($collaboratingGroupIds, $collaboratingGroupIdsPending, $approvedChapterIds, $collaborating_chapterids_pending)){
                $message = gettext("Approval request processed successfully.");
            } else {
                $message = gettext("We are unable to process your request due to internal system error.");
                $success = 0;
            }
        }
    }  
    AjaxResponse::SuccessAndExit_STRING( $success, '', $message, ($success ? gettext('Success') : gettext('Error')));
}

elseif (isset($_GET['approveEventChapterCollaboration'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (
        ($eventid = $_COMPANY->decodeId($_GET['eventid']))<0 ||
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($chapterid = $_COMPANY->decodeId($_GET['chapterid']))<1 ||
        ($event = Event::GetEvent($eventid)) == NULL
        ) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $chapter = Group::GetChapterName($chapterid,$groupid);
    $success = 0;
    $cgids = $event->val('collaborating_groupids');
    $cgidsPending = $event->val('collaborating_groupids_pending');

    $chaperids = $event->val('chapterid');
    $chapteridsPending = $event->val('collaborating_chapterids_pending');

    $collaboratingGroupIds = empty($cgids) ? array() : explode(',', $cgids);
    $collaboratingGroupIdsPending = empty($cgidsPending) ? array() : explode(',', $cgidsPending);

    $chaperids =  empty($chaperids) ? array() : explode(',', $chaperids);;
    $chapteridsPending =  empty($chapteridsPending) ? array() : explode(',', $chapteridsPending);;

    if (!$_USER->canCreateContentInGroupChapterV2($groupid,$chapter['regionids'], $chapterid)) {
        $message = gettext("Your role does not have enough permissions to approve this event");
    } elseif (($key = array_search($chapterid, $chapteridsPending)) !== false) {

        $chaperids[] = $chapterid; // Add it to collaborating list
        unset($chapteridsPending[$key]); // Remove the value from pending list
        $getApprovedChapterDetail = Group::GetChapterNamesByChapteridsCsv($chapterid);

        if (!empty($chapteridsPending)) { // If there are still pending chapter approvel then recalculate pending groups
            $approvedChapterGroupid = $getApprovedChapterDetail[0]['groupid'];
            $regionid = $getApprovedChapterDetail[0]['regionids'];
            // Get All Chapter of this group
            $allChapters = Group::GetChapterByGroupsAndRegion($approvedChapterGroupid,$regionid,false);
            $chaptersIdsArray = array_column($allChapters,'chapterid');
            // Find pending chapters to approve of approved chapter parent group
            $pendingToApprove = array_intersect($chapteridsPending, $chaptersIdsArray);

            if (!empty($pendingToApprove) && ($key = array_search($approvedChapterGroupid, $collaboratingGroupIdsPending)) !== false){ // Remove approved chapters group from pending groups
                $collaboratingGroupIds[] = $approvedChapterGroupid;
                unset($collaboratingGroupIdsPending[$key]);
            }
        } else { // If all chapters are approved then move last approved chapters group to approved group if available.
            $collaboratingGroupIds = array_merge($collaboratingGroupIds,array_column($getApprovedChapterDetail,'groupid'));
            if (!empty($collaboratingGroupIdsPending) && ($key = array_search($getApprovedChapterDetail[0]['groupid'], $collaboratingGroupIdsPending)) !== false) {
                unset($collaboratingGroupIdsPending[$key]);
            }
        }
        
        sort($chaperids);
        sort($chapteridsPending);
        sort($collaboratingGroupIdsPending);
        sort($collaboratingGroupIds);

        if ($event->updateCollaboratingGroupids($collaboratingGroupIds, $collaboratingGroupIdsPending, $chaperids,$chapteridsPending)){
            $message = gettext("Approval request processed successfully.");
            $success = 1;
        } else {
            $message = gettext("We are unable to process your request due to internal system error.");
            $success = 0;
        }
    } else {
        $message = gettext("This approval request was already processed. Thank you for showing interest.");
        $success = 1;
    }  
    AjaxResponse::SuccessAndExit_STRING( $success, '', $message, ($success ? gettext('Success') : gettext('Error')));
}

elseif(isset($_GET['validateEventFormInputs']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    
    $validateData =  ViewHelper::ValidateEventFormInputs();
    if (is_array($validateData)) {
        echo 1;
    }
}

elseif(isset($_GET['manageEventExpenseEntries']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (!isset($_GET['eventid']) ||
        ($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanManageEventBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $modelTitle = gettext('Manage Event Expense Entries');
    $eventExpenseEntries = $event->getEventExpenseEntries() ?? array();
    $isActionDisabledDuringApprovalProcess = $event->isActionDisabledDuringApprovalProcess();
    // gets the modal box from this view
    include(__DIR__ . "/views/events/manage_event_expense_entries_modal.template.php");

}

elseif(isset($_GET['getGroupChaptersForEventExpenseEntry']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (!isset($_GET['eventid']) ||
        ($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }


    if (!$event->loggedinUserCanManageEventBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $groupIds = array();
    $canManageEventBudget = true;

    if ($event->val('groupid')) {
        $groupIds[] = $event->val('groupid');
    } elseif(!empty($event->val('collaborating_groupids'))) {
        $groupIds = explode(',',$event->val('collaborating_groupids'));
    }
    if (empty($groupIds)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("The option to create event expense entries is currently unavailable."), gettext('Error'));
    }
    $eventGroups = Group::GetGroups($groupIds,true, false);
    $modelTitle = sprintf(gettext('%s Selection'),$_COMPANY->getAppCustomization()['group']["name-short"]);
    // gets the modal box from this view
    include(__DIR__ . "/views/events/choose_group_chapter_for_event_expense.template.php");

}
elseif(isset($_GET['getSelectedGroupsAvailableChapters']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (!isset($_GET['eventid']) ||
        ($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === null || 
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<0
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (empty($event->val('chapterid'))) {
        echo '';
        exit();
    }

    $groupChapters = Group::GetChapterList($groupid);
    if (empty($groupChapters)) {
        echo '';
        exit();
    }
   
    $groupChaptersIds = array_column($groupChapters,'chapterid');
    $collaboratedChapterIds = explode(',',$event->val('chapterid'));

    $availableChapterIds = array_intersect($groupChaptersIds,$collaboratedChapterIds);
    if (empty($availableChapterIds)) {
        echo ''; 
        exit();
    }

    $chapters = Group::GetChapterNamesByChapteridsCsv(implode(',',$availableChapterIds));
    if (empty($chapters)) {
        echo ''; 
        exit();
    }

    $canManageEventBudget = $event?->loggedinUserCanManageEventBudget();
?>
<div class="form-group" >
    <label for="chapter_selection" class="col-12 control-lable"><?= sprintf(gettext('Select a %s to proceed with the event expense entry'),$_COMPANY->getAppCustomization()['chapter']["name-short"]);?></label>
    <div class="col-12">
        <select class="form-control " id="chapter_selection" name='chapter_selection'>
            <option value=""><?= sprintf(gettext('Select %s for event expense entry'),$_COMPANY->getAppCustomization()['chapter']['name-short'])?></option>
            <?php foreach($chapters as $chapter){
                    $disabled = '';
                     if (!$canManageEventBudget && !$_USER->canCreateContentInGroupChapterV2($chapter['groupid'],$chapter['regionids'], $chapter['chapterid'])){
                         $disabled = 'disabled';
                     }
            ?>
                
                <option value="<?= $_COMPANY->encodeId($chapter['chapterid']); ?>" <?= $disabled; ?>><?= $chapter['chaptername'].($disabled ? ' ['.gettext("Read-only").']' : ''); ?></option>

            <?php } ?>
        </select>
    </div>
</div>
<script>
    $('#chapter_selection').multiselect('destroy');
    $('#chapter_selection').multiselect({
        nonSelectedText: "<?= sprintf(gettext('Select %s for event expense entry'),$_COMPANY->getAppCustomization()['chapter']['name-short'])?>",
        disableIfEmpty:true,
        enableFiltering: true,
        maxHeight:400,
        enableClickableOptGroups: true,
    });
</script>


<?php
}

elseif(isset($_GET['proceedToEventExpenseEntryForm']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (!isset($_GET['eventid']) ||
        ($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === null || 
        ($groupid = $_COMPANY->decodeId($_GET['group_selection']))<0 ||
        ($group = Group::GetGroup($groupid)) === null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Zone check and assignment are required for expense entries, as they can be added across zones in a collaborated event.
    if ($group->val('zoneid')!=$_ZONE->id()){
        $_ZONE = $_COMPANY->getZone($group->val('zoneid'));
    }

    $chapterid = !empty($_GET['chapter_selection']) ? $_COMPANY->decodeId($_GET['chapter_selection']) : 0;

    if ($_USER->isOnlyChaptersLeadCSV($groupid) && $chapterid == 0){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Select %s for event expense entry'),$_COMPANY->getAppCustomization()['chapter']['name-short']),gettext('Error'));
    }
    $enc_groupid = $_COMPANY->encodeId($groupid);
    $enc_eventid = $_COMPANY->encodeId($eventid);
    $enc_chapterid = $_COMPANY->encodeId($chapterid);
    $expense = $event->getEventBudgetedDetail(true, $groupid, $chapterid);
    // Disclaimer check
    $checkDisclaimerExists = Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['BUDGET_EXPENSE_CREATE_BEFORE'], $event->val('groupid'));
    if(!$expense && $checkDisclaimerExists){
        $call_method_parameters = array(
            $enc_groupid,
            $enc_eventid,
            $enc_chapterid
        );
        $call_other_method = base64_url_encode(json_encode(
            array (
                "method" => "addEventExpenseEntry",
                "parameters" => $call_method_parameters
            )
        ));
    ?>
    <script>
        $(document).ready(function(){
            loadDisclaimerByHook('<?= $_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['BUDGET_EXPENSE_CREATE_BEFORE']);?>','<?= $enc_groupid; ?>', 0, '<?= $call_other_method; ?>');
        });
    </script>
<?php  
    }else{
?>
     <script>
         $(document).ready(function(){
<?php if ($expense){ ?>
            Swal.fire({
                title:'<?= gettext("Confirmation")?>',
				text: '<?= gettext("Expense entry for the given scope already exists. Do you want to create a new one?")?>',
				showCloseButton: false,
				showCancelButton: true,
				showConfirmButton: true,
				focusConfirm: true,
				allowOutsideClick:false,
                confirmButtonText: 'Create New',
			}).then(function(result) {

                if (result.isConfirmed) {
                    addEventExpenseEntry('<?= $enc_groupid; ?>', '<?= $enc_eventid; ?>','<?= $enc_chapterid; ?>', true);
                } else {
                    manageEventExpenseEntries('<?= $enc_eventid; ?>');
                }
			});

<?php } else { ?>
        addEventExpenseEntry('<?= $enc_groupid; ?>', '<?= $enc_eventid; ?>','<?= $enc_chapterid; ?>', false);
<?php } ?>
        });
    </script>
<?php
    }
} elseif (isset($_GET['getMyExternalVolunteers']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($eventid = $_COMPANY->decodeId($_GET['eventid'])) < 1 ||
        ($event = Event::GetEvent($eventid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $volunteerid = 0;
    if (!empty($_GET['volunteerid'])) {
        $volunteerid = $_COMPANY->decodeId($_GET['volunteerid']);
    }

    $volunteer = null;
    if ($volunteerid) {
        $volunteer = EventVolunteer::GetEventVolunteer($volunteerid);
    }

    $external_volunteer_roles = $event->getExternalVolunteerRoles();

    /**
     * We are using the Read-Only DB here and not the Read-Write DB
     * As we did not make any INSERT/UPDATE/DELETE query on the event volunteers
     * So the RO-DB should be in-sync with the RW-DB
     */
    $external_volunteers = $event->getMyExternalVolunteers();

    require __DIR__ . '/views/events/external_event_volunteers_modal.html.php';
} elseif (isset($_GET['addExternalEventVolunteer']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($eventid = $_COMPANY->decodeId($_POST['eventid'])) < 1 ||
        ($event = Event::GetEvent($eventid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $volunteertypeid = !empty($_POST['volunteertypeid']) ? $_COMPANY->decodeId($_POST['volunteertypeid']) : 0;
    $careof_userid = $_USER->id();

    /**
     * Here, we could had used the Read-Only DB here instead of the Read-Write DB
     * Because we haven't made the INSERT/UPDATE query yet
     * But here as its a POST request, we can use the RW-DB only throughout
     * That way we can reuse that same connection to the RW-DB instead of having to make 2 connections, 1 to RW-DB and 1 to RO-DB
     */
    $external_volunteers = $event->getMyExternalVolunteers(true);

    if (empty($firstname)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter first name'), gettext('Error'));
    }

    if (empty($lastname)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter last name'), gettext('Error'));
    }

    if (empty($email)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter email'), gettext('Error'));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter a valid email'), gettext('Error'));
    }

    if ($event->hasEnded()) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('This event has ended'), gettext('Error'));
    }

    $volunteer = null;
    if (!empty($_POST['volunteerid'])) {
        $volunteerid = $_COMPANY->decodeId($_POST['volunteerid']);
        $volunteer = EventVolunteer::GetEventVolunteer($volunteerid);
    }

    if ($volunteer) {
        $volunteertypeid = $volunteer->val('volunteertypeid');
    }

    if (!$event->isEventVolunteerSignup($_USER->id())) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please sign up as a volunteer first before adding someone else'), gettext('Error'));
    }

    if ($volunteer) {
        $status = $volunteer->updateExternalEventVolunteer(
            $eventid,
            $firstname,
            $lastname,
            $email
        );

        if ($status === -2) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('This email address is already added as an external volunteer'), gettext('Error'));
        }

        $show_update_success_banner = true;
        $volunteer = null;
    } else {
        if (count($external_volunteers) >= 5) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Cannot add more than 5 external volunteers'), gettext('Error'));
        }

        $status = $event->addExternalEventVolunteer(
            $firstname,
            $lastname,
            $email,
            $volunteertypeid,
            $careof_userid
        );

        if ($status === -1) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Event Volunteer request capacity has been met'), gettext('Error'));
        }

        if ($status === -2) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('This email address is already added as an external volunteer'), gettext('Error'));
        }

        $show_create_success_banner = true;
    }

    $external_volunteer_roles = $event->getExternalVolunteerRoles();
    /**
     * We just made an INSERT/UPDATE query
     * So we are fetching from Read-Write DB instead of the Read-Only DB
     * to avoid issues with replication lag
     */
    $external_volunteers = $event->getMyExternalVolunteers(true);

    require __DIR__ . '/views/events/external_event_volunteers_modal.html.php';
} elseif (isset($_GET['deleteExternalEventVolunteer']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($eventid = $_COMPANY->decodeId($_POST['eventid'])) < 1 ||
        ($event = Event::GetEvent($eventid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $volunteerid = $_COMPANY->decodeId($_POST['volunteerid']);
    $volunteer = EventVolunteer::GetEventVolunteer($volunteerid);

    $volunteer->deleteIt();

    $volunteer = null;
    $external_volunteer_roles = $event->getExternalVolunteerRoles();
    /**
     * We just made a DELETE query
     * So we are fetching from Read-Write DB instead of the Read-Only DB
     * to avoid issues with replication lag
     */
    $external_volunteers = $event->getMyExternalVolunteers(true);
    $show_delete_success_banner = true;

    require __DIR__ . '/views/events/external_event_volunteers_modal.html.php';
}
elseif (isset($_GET['openAddOrEditExternalVolunteerByLeaderModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($eventid = $_COMPANY->decodeId($_GET['eventid'])) < 1 ||
        ($event = Event::GetEvent($eventid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $external_volunteer_roles = $event->getExternalVolunteerRoles();
    if (!$external_volunteer_roles) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $volunteerid = 0;
    if (!empty($_GET['volunteerid'])) {
        $volunteerid = $_COMPANY->decodeId($_GET['volunteerid']);
    }

    $volunteer = null;
    if ($volunteerid) {
        $volunteer = EventVolunteer::GetEventVolunteer($volunteerid);
    }

    require __DIR__ . '/views/events/external_event_volunteers_leader_modal.html.php';
}
elseif (isset($_GET['addOrEditExternalEventVolunteerByLeader']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($eventid = $_COMPANY->decodeId($_POST['eventid'])) < 1 ||
        ($event = Event::GetEvent($eventid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $volunteertypeid = !empty($_POST['volunteertypeid']) ? $_COMPANY->decodeId($_POST['volunteertypeid']) : 0;

    $volunteer = null;
    if (!empty($_POST['volunteerid'])) {
        $volunteerid = $_COMPANY->decodeId($_POST['volunteerid']);
        $volunteer = EventVolunteer::GetEventVolunteer($volunteerid);
    }

    if ($volunteer) {
        $volunteertypeid = $volunteer->val('volunteertypeid');
        $careof_userid = $volunteer->val('external_user_careofid');
    } else {
        if (empty($_POST['userid'])) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please assign a contact person'), gettext('Error'));
        }

        $careof_userid = $_COMPANY->decodeId($_POST['userid']);
    }

    if (empty($firstname)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter first name'), gettext('Error'));
    }

    if (empty($lastname)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter last name'), gettext('Error'));
    }

    if (empty($email)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter email'), gettext('Error'));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter a valid email'), gettext('Error'));
    }

    $external_volunteers = $event->getUsersExternalVolunteers($careof_userid);

    if (!$event->isEventVolunteerSignup($careof_userid)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please sign up the contact person as a volunteer first before adding an external-volunteer'), gettext('Error'));
    }

    if ($event->hasEnded()) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('This event has ended'), gettext('Error'));
    }

    if ($volunteer) {
        $status = $volunteer->updateExternalEventVolunteer(
            $eventid,
            $firstname,
            $lastname,
            $email
        );

        if ($status === -2) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('This email address is already added as an external volunteer'), gettext('Error'));
        }

        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('External Event Volunteer updated successfully.'), gettext('Success'));
    } else {
        if (count($external_volunteers) >= 5) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Cannot add more than 5 external volunteers to a contact'), gettext('Error'));
        }

        $status = $event->addExternalEventVolunteer(
            $firstname,
            $lastname,
            $email,
            $volunteertypeid,
            $careof_userid
        );

        if ($status === -1) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Event Volunteer request capacity has been met'), gettext('Error'));
        }

        if ($status === -2) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('This email address is already added as an external volunteer'), gettext('Error'));
        }

    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('External Event Volunteer created successfully.'), gettext('Success'));
}
}
elseif(isset($_GET['search_event_contributors']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['search_event_contributors']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $activeusers =  User::SearchUsersByKeyword($_GET['keyword']);
    $items = array();
    foreach($activeusers as $u) {
        if ($u['userid'] == $_USER->id()) { continue; }
        $items[] = ['id' => $_COMPANY->encodeId($u['userid']), 'text' => $u['firstname'].' '.$u['lastname'].' ('.$u['email'].')'];
    }
    Http::Cache(30);
    echo json_encode(['items' => $items]);
}

elseif(isset($_GET['proceedToEventApprovalModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($event->val('form_validated') == 0 ) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please fill in all the mandatory fields and save it before sending the event for approval.'), gettext('Error'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(1, $_COMPANY->encodeId($event->val('version')), gettext('Successfully validated.'), gettext('Success'));
    }
}

elseif(isset($_GET['reconcileEvent']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $is_event_reconciled = 1;
    if ($event->val('is_event_reconciled')) {
        $is_event_reconciled = 0;
    }
    if ($event->reconcileEvent($is_event_reconciled)) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Event reconciled successfully.'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong while updating status.'), gettext('Error'));
    }
}

elseif(isset($_GET['sendTopicCollaborationApproverRequest']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($topicId = $_COMPANY->decodeId($_POST['topicId']))<1 ||
        ($topicType = $_POST['topicType']) == ''
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($topicType == 'EVT') {
        $topic = Event::GetEvent($topicId);
    }
    $topicEnglish = strtolower(Teleskope::TOPIC_TYPES_ENGLISH[$topicType]);
        
    $collaborating_groupids_pending = $topic->val('collaborating_groupids_pending');
    $collaborating_groupids_pending = explode(',',$collaborating_groupids_pending??'');
    $requestEmailApprovalGroups = array();
    foreach($collaborating_groupids_pending as $gid) {
        if ($gid){
            $requestEmailApprovalGroups[] = Group::GetGroup($gid);
        }
    }

    if ($topic->val('collaborating_chapterids_pending')){ //Add the approved group to the request process if there is a pending chapter for that group.
        $collaborating_groupids = explode(',',$topic->val('collaborating_groupids'));
        foreach($collaborating_groupids as $gid) {
            if ($gid){
                $chapterLevelApprovalNeeded = Group::GetGroupChaptersFromChapterIdsCSV($gid, $topic->val('collaborating_chapterids_pending')??'');
                if (!empty($chapterLevelApprovalNeeded)){
                 
                    $requestEmailApprovalGroups[] = Group::GetGroup($gid);
                }
            }
        }
    }
    $collaborating_chapterids_pending = array_filter(explode(',',$topic->val('collaborating_chapterids_pending')??''));
   
    // Validate data
    ViewHelper::ValidateTopicGroupCollaborationApproverSelection($requestEmailApprovalGroups,$collaborating_chapterids_pending);

    $canUpdateCollaboratingTopic = true;

    /**
     * 
     * TODO -  This condition needs to be improve 
     * while other content type collaboration will impliment 
     * because loggedinUserCanUpdateEvent() is event spacific
     * 
     **/

    if (!(($topic->isDraft() || $topic->isUnderReview() ) && ($topic->loggedinUserCanUpdateEvent()) )) {
        $canUpdateCollaboratingTopic  = false;
    }

    $emailsSent = [];
    if ($canUpdateCollaboratingTopic){
        // Next Send Invite Email requests
        $emailsSent = ViewHelper::SendTopicCollborationApprovalRequest('EVT',$topicId,$requestEmailApprovalGroups,$collaborating_chapterids_pending);
    }

    if (empty($emailsSent))
        AjaxResponse::SuccessAndExit_STRING(1,'', gettext("No collaboration request emails were sent as no approver emails were available"), gettext('Success'));
    else
        AjaxResponse::SuccessAndExit_STRING(1,'', gettext("Collaboration request has been sent successfully to the following emails:") . ' ' . implode(", ", $emailsSent), gettext('Success'));
}
elseif(isset($_GET['canDisableEventBudgetModuleAssociation']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    
    if (!$event->getEventBudgetedDetail()) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Can disable'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Budget setting cannot be disabled. Please remove the associated expense entries first.'), gettext('Error'));
    }
}

elseif(isset($_GET['canDisablePartnerOrgAssociation']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (!$event->getAssociatedOrganization()) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Can disable'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Partner organization settings cannot be disabled. Please remove the associated organizations first.'), gettext('Error'));
    }
}
elseif(isset($_GET['canDisableEventSpeakersAssociation']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (empty($event->getEventSpeakers())) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Can disable'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Speaker settings cannot be disabled. Please remove the associated speakers first.'), gettext('Error'));
    }
}
elseif(isset($_GET['canDisableEventVolunteerAssociation']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (empty($event->getEventVolunteerRequests())) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Can disable'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Volunteer settings cannot be disabled. Please remove the associated volunteers first.'), gettext('Error'));
    }
}

elseif(isset($_GET['updateEventBookingResolutionData']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $resolution = $_POST['resolution'];
    if (!in_array($resolution,array('complete','noshow'))) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('The resolution type is not valid.'), gettext('Error'));
    }
    $schedule_completion_comment = $_POST['schedule_completion_comment'] ?:'';
    
    // Authorization Check canPublishOrManageContentInScopeCSV
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) { //Allow creators to delete unpublished content
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($event->updateEventBookingResolutionData($resolution, $schedule_completion_comment)) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Booking status updated successfully.'), gettext('Success'));
    }

    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Error'));
}

elseif(isset($_GET['cancelEventBooking']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $booking_cancel_reason = $_POST['booking_cancel_reason'] ?:'';

    // Authorization Check canPublishOrManageContentInScopeCSV
    if (!$event->loggedinUserCanUpdateOrPublishOrManageEvent()) { //Allow creators to delete unpublished content
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($event->cancelEvent($booking_cancel_reason, false)) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Booking has been canceled successfully.'), gettext('Success'));
    }

    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Error'));
}

else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}