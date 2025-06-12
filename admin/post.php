<?php
require_once __DIR__.'/head.php';

$pagetitle = sprintf(gettext("Manage %s"),Post::GetCustomName(true));


// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
    header("HTTP/1.1 403 Forbidden (Access Denied)");
    exit();
}

//print_r($rows);exit;
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/post.html');

?>
