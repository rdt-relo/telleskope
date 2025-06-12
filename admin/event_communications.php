<?php
require_once __DIR__.'/head.php';

$pagetitle = "Manage Event Communications";

// Get all Event Email Templates types of companyid
$communication_type_keys   =  Event::EVENT_COMMUNICATION_TYPES;
$emailTemplates = Event::GetEventCommunicationTemplatesForZone($_ZONE);

if (!$_COMPANY->getAppCustomization()['event']['reconciliation']['enabled']) {
    unset($communication_type_keys['reconciliation']);
    unset($emailTemplates['reconciliation']);
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/event_communications.html');

?>