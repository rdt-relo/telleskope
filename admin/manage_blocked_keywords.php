<?php
require_once __DIR__ . '/head.php';

$pagetitle = gettext('Manage Blocked Keywords');

$blocked_keywords = BlockedKeyword::GetAllBlockedKeywords();
$include_datatables_js = true;

include __DIR__ . '/views/header_new.html';
include __DIR__ . '/views/manage_blocked_keywords.html.php';
include __DIR__ . '/views/footer.html';
