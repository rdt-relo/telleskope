<?php
exit();
require_once __DIR__.'/head.php';
if (!$_COMPANY->getAppCustomization()['event']['speakers']['enabled'] || !$_USER->canManageZoneSpeakers()) {
    header(HTTP_FORBIDDEN);
    echo "403 Forbidden (Access Denied)";
    exit();
}

$pagetitle = "Manage Event Speaker Fields";

$allSpeakersFields = Event::GetEventSpeakerFieldsList();
$approvalStatus = array('0' => 'Delete', '1'=>'Active','2'=>'Draft','3'=>'In-active');

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manage_event_speaker_fields.html');
?>
