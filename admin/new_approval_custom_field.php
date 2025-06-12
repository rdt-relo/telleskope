<?php
require_once __DIR__.'/head.php';

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}
$pagetitle = "Add New Custom Field";

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/new_event_custom_field.html');
?>
