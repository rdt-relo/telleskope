<?php

require_once __DIR__ . '/head.php';

$event_id = $_COMPANY->decodeId($_GET['e']);
$event = Event::GetEvent($event_id);

if (!$event->val('event_recording_link')) {
    http_response_code(404);
    exit();
}

$event->logEventRecordingLinkClick();

Http::Redirect($event->val('event_recording_link'));
