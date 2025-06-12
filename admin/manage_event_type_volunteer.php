<?php
require_once __DIR__.'/head.php';
// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
    header(HTTP_FORBIDDEN);
    exit();
}

// Data Validation
if (!isset($_GET['event_type_id']) || ($typeid = $_COMPANY->decodeId($_GET['event_type_id'])) < 1) {
    header(HTTP_BAD_REQUEST);
    exit();
}

// Fetch the existing event type data before processing form submission
$eventTypeData = Event::GetEventTypeById($typeid);
if (!$eventTypeData['zoneid']) {
    // Disable for events belonging to global zone
    header(HTTP_BAD_REQUEST);
    exit();
}

$eventType = $eventTypeData['type'];

if (!empty($eventTypeData['attributes'])) {
    $eventTypeVolunteerData = json_decode($eventTypeData['attributes'], true);
    $eventVolunteerRequests = $eventTypeVolunteerData['event_volunteer_requests'];
} else {
    // If no existing data, initialize the eventVolunteerRequests array
    $eventVolunteerRequests = array();
}


// Allow volunteer edits only if the event type is a zone event type or if the Admin is a Company Level Admin
$allow_volunteer_edits = $eventTypeData['zoneid'] > 0 || $_USER->isCompanyAdmin();

$pagetitle = "Predefined Volunteer Requests for ".$eventType." Event Type";
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manage_event_type_volunteer.html');
?>