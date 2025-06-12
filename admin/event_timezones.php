<?php
require_once __DIR__.'/head.php';
$pagetitle = "Manage Preferred Timzones";
$timezone = @$_SESSION['timezone'];

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
    header("HTTP/1.1 403 Forbidden (Access Denied)");
    exit();
}

$stageRows = array();
for ($stage=1; $stage <= Approval::APPROVAL_STAGE_MAX; $stage++) {
    $stageRows[(string)$stage] = Event::GetAllApproversByStage($stage,0,0,0);
}

$data = Event::GetAllPreferredTimezones();

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/event_timezones.html');
?>
