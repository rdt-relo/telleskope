<?php
require_once __DIR__.'/head.php';

if (!defined('INDEX_PAGE') && empty ($_ZONE)) { // User is logged in, but for some reason the $_ZONE is not set, logout and retry
  Http::Redirect('logout');
}
if (!$_USER->isUserInboxEnabled()) {
  header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}
// Get current user message
$page = 1;
$start = ($page - 1) * 30;
$end = 30;
$messages = UserInbox::GetMyMessages('inbox', $start, $end);

include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/my_inbox_html.php');
include(__DIR__ . '/views/footer_html.php');
?>