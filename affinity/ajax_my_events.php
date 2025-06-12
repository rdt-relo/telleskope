<?php

define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__.'/head.php';

global $_COMPANY; /* @var Company $_COMPANY */
global $_USER;/* @var User $_USER */
global $_ZONE;
global $db;

# Module Level Authorization
if (!$_COMPANY->getAppCustomization()['event']['my_events']['enabled']) {
    header(HTTP_BAD_REQUEST);
    exit();
}

###### All Ajax Calls For Events ##########
## OK
## Get All Group Events
if (isset($_GET['filterEventsByZone']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
// This request uses the zoneid from dropdown to show the events by zone.
    if (
        ($zoneid = $_COMPANY->decodeId($_POST['zoneid'])) < 0
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Dates filters
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    
    $page = isset($_POST['page']) && (int)$_POST['page'] > 0 ? (int)$_POST['page'] : 1;  
    $lastMonth = $_POST['lastMonth'] ?? '';
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $timezone = $_SESSION['timezone'] ?: 'UTC';
    $chapterid =0;
    $channelid = 0;
    $pinnedEvents = array();
    $section = Event::MY_EVENT_SECTION['DISCOVER_EVENTS'];
    $type = ($section) ? 1 : 2; // For New Events

    $eventTypeArray = array('all');
    if (!empty($_POST['eventTypes'])){
        if ($_POST['eventTypes'] != 'all') {
            $encodedEventTypeIds = $_POST['eventTypes'];
            if(!is_array($encodedEventTypeIds)){
                $encodedEventTypeIds = json_decode($_POST['eventTypes'], true);
            }
            $eventTypeArray =  $_COMPANY->decodeIdsInArray($encodedEventTypeIds);
        }
    }else{
        $eventTypeArray = array();
    }
    $deepLoad = false;
    if(isset($_POST['newLoad']) && $_POST['newLoad']){
        $deepLoad = true;
    }
    $allEvents = Event::GetDiscoverMyEventsData($zoneid, $eventTypeArray, $page, $limit, $timezone, $startDate, $endDate, $deepLoad);
    $data = $allEvents['events'];
    $show_more = $allEvents['show_more'];
    $max_iter = count($data);
    $max_iter = $show_more ? $limit : $max_iter;
    $volunteerTypes = Event::GetEventVolunteerTypesForCurrentZone(false);;
    // Note: The following template internally builds the page  using get_events_timeline template
    if ($page == 1){
	    include(__DIR__ . "/views/my_events/my_events_timeline.template.php");
    } else {
        include(__DIR__ . "/views/templates/get_events_timeline_rows.template.php");
    }
}
elseif(isset($_GET['refreshEventTypeDropdown']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (
        ($zoneid = $_COMPANY->decodeId($_GET['zoneid'])) < 0
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Fetch event types based on zoneid

    // Event Types Dropdown
    $zoneIdsArray = [];
    if($zoneid){
        $zoneIdsArray = [$zoneid];
    }else{
        $userZoneIds = $_USER->val('zoneids');
        $zoneIdsArray = explode(',',$userZoneIds);
    }
    
    $eventTypes = Event::GetEventTypesByZones($zoneIdsArray);
    $encodedEventTypes = [];
    foreach ($eventTypes as $et) {
        $etEncodedId = $_COMPANY->encodeId($et['typeid']);
        $encodedEventTypes[$etEncodedId] = $et["type"];
    }
    echo json_encode($encodedEventTypes);
}
elseif(isset($_GET['getMyEventsDataBySection']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (
        ($section = $_GET['section']) == '' ||
        (!in_array($section,Event::MY_EVENT_SECTION))
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
  
    $page = 1;
    if (isset($_GET['page']) && (int)$_GET['page'] > 0) {
        $page = (int)$_GET['page'];
    }
    $lastMonth = $_GET['lastMonth'] ?? '';
    $limit = 10;
    $type = ($section == Event::MY_EVENT_SECTION['MY_UPCOMING_EVENTS']) ? 1 : 2; // For New Events
    $pinnedEvents = array();
    $timezone = $_SESSION['timezone'] ?: 'UTC';
    $chapterid =0;
    $channelid = 0;

    /**
     * Get Events of All Zones
     */
    $selected_zone_id = 0;
    $data = Event::GetMyEventsBySection($section, Event::EVENT_CLASS['EVENT'], $selected_zone_id, $page, $limit, $timezone);

    $max_iter = count($data);
    $show_more = ($max_iter > $limit);
    $max_iter = $show_more ? $limit : $max_iter;
    $volunteerTypes = Event::GetEventVolunteerTypesForCurrentZone(false);;
    // Note: The following template internally builds the page  using get_events_timeline template
    if ($page == 1){
	    include(__DIR__ . "/views/my_events/my_events_timeline.template.php");
    } else {
        include(__DIR__ . "/views/templates/get_events_timeline_rows.template.php");
    }
}

elseif (isset($_GET['getMyEventsSubmissions']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (!$_COMPANY->getAppCustomization()['event']['my_events']['event_submissions']) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $state_filter = Event::STATUS_ACTIVE;
    $year_filter = date("Y");

    if (!empty($_GET['state_filter'])){
        $state_filter = $_COMPANY->decodeId($_GET['state_filter']);
    }
   
    if (!empty($_GET['year_filter'])){
        $year_filter = $_COMPANY->decodeId($_GET['year_filter']);
    }
    $groupid = 0;

    /**
     * Get Events of All Zones
     */
    $selected_zone_id = 0;
    $events = Event::GetMyEventSubmissions($state_filter, $year_filter, $selected_zone_id);

  	include(__DIR__ . '/views/my_events/my_events_submissions_table.php');
}

elseif (isset($_GET['getMyEventActionButton']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<0 ||
        ($event = Event::GetEvent($eventid)) === null   ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Check for Request Approval if active
    $approval = null;
    if($_COMPANY->getAppCustomization()['event']['approvals']['enabled']){
        $approval = $event->getApprovalObject() ?: '';
    }
    include(__DIR__ . "/views/my_events/my_event_action_button.template.php"); 
}

else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}
