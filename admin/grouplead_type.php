<?php
require_once __DIR__.'/head.php';

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
    header("HTTP/1.1 403 Forbidden (Access Denied)");
    exit();
}

$grouplabel = $_COMPANY->getAppCustomization()['group']["name"];
$grouplabelshort = $_COMPANY->getAppCustomization()['group']["name-short"];
$pagetitle = "Manage {$grouplabel} Leader Types";

$timezone = @$_SESSION['timezone'];	

// Get all events types of companyid
$data = $db->get("SELECT * FROM `grouplead_type` WHERE `companyid`='{$_COMPANY->id()}' AND (`zoneid`='{$_ZONE->id()}') ORDER BY `typeid` ASC");

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/grouplead_type.html');
?>
