<?php
require_once __DIR__.'/head.php';

$pagetitle = "Manage Hashtags";

$hashTagHandles = HashtagHandle::GetAllHashTagHandles();

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/manage_hashtags.html');
include(__DIR__ . '/views/footer.html');
?>
