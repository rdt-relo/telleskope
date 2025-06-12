<?php
require_once __DIR__.'/head.php';
if (!$_COMPANY->getAppCustomization()['event']['speakers']['enabled'] || !$_USER->canManageZoneSpeakers()) {
    header(HTTP_FORBIDDEN);
    echo "403 Forbidden (Access Denied)";
    exit();
}

$pagetitle = "Manage Event Speakers";

$allSpeakers = $db->get("SELECT event_speakers.*,events.eventtitle,events.collaborating_groupids,events.start, events.groupid FROM `event_speakers` INNER JOIN events ON events.eventid=event_speakers.eventid AND `events`.zoneid='{$_ZONE->id()}' WHERE event_speakers.companyid={$_COMPANY->id()} AND event_speakers.zoneid={$_ZONE->id()} AND event_speakers.approval_status !=0 ORDER BY approval_status ASC");
$approvalStatus = array('1'=>'Requested','2'=>'Processing','3'=>'Approved','4'=>'Denied');

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/event_speakers.html');
?>
