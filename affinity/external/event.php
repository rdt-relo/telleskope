<?php
include_once __DIR__.'/head.php';

//$_SESSION['input'] = null;
if (empty($_GET['params'])) {
    error_and_exit("Event link is not valid.");
    exit();
}


$event = Event::GetEvent($eventid);
if (!$event){
    error_and_exit("Event link is not valid.");
    exit();
}

if (!$event->val('external_facing_event')) {
    error_and_exit("External access to this event is disabled.");
    exit();
}

$timezone = "UTC";
$enc_eventid   = $_COMPANY->encodeId($eventid);
$_ZONE = $_COMPANY->getZone($event->val('zoneid')); //Temporary
$rsvpOptions    = $event->getMyRSVPOptions();
$joinersCount	= $event->getJoinersCount();
$btnCss         = array('warning','success','info','danger','danger','danger');
$code 			= '';
$collaboratedWithFormated = null;
$group 		= Group::GetGroup($event->val('groupid'));
$chapters	= Group::GetChapterList($event->val('groupid'));
$channels   = Group::GetChannelList($event->val('groupid'));
if ($event->val('collaborating_groupids')){
    $collaboratedWithFormated = $event->getFormatedEventCollaboratedGroupsOrChapters();
}
$rsvpOptions['my_rsvp_status'] = !empty($checkJoinStatus) ? $checkJoinStatus[0]['joinstatus'] : 0;
 
$eventJoiners = array();
$external_user = null;
$rsvpDetail = null;
$userRsvpStatus = 0;
if (isset($_SESSION['external_user'])) {
    $external_user = $_SESSION['external_user'];
    $email = $external_user['email'];
    $joinid = $event->externalCheckin_fetchExistingCheckinRecord($email);
    $rsvpDetail = $event->getEventRsvpDetail($joinid);
    if ($rsvpDetail){
        $userRsvpStatus = $rsvpDetail['joinstatus'];
    }
    
    $timezone = $external_user['timezone'] ?? "UTC";
    $eventJoiners	= $event->getRandomJoiners(12);
}

$eventVolunteerRequests = array();
$eventVolunteers = array();

if ($_COMPANY->getAppCustomization()['event']['volunteers'] && $event->isPublished() && !$event->hasEnded()){ 
    $eventVolunteerRequests = $event->getEventVolunteerRequests();
    $eventVolunteers = $event->getEventVolunteers();
}

include __DIR__ . '/views/event.html';

$_COMPANY = null; // Reset
$_ZONE = null; // Reset
