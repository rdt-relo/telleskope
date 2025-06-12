<?php
require_once __DIR__.'/head.php';

global $_COMPANY, $_ZONE, $_USER;

if (!defined('INDEX_PAGE') && empty ($_ZONE)) { // User is logged in, but for some reason the $_ZONE is not set, logout and retry
    Http::Redirect('logout');
}

$error = "";
$handle = "";
$feeds = array();
if ( !isset($_GET['handle']) || 
    ($handle = trim($_GET['handle'])) == '' ||
    empty ($hashtag = HashtagHandle::GetHandle($handle))
    ) {
    header(HTTP_NOT_FOUND);
    $error  = "#{$handle} not found";
} else {
    $feeds = Group::GetFeedsByHashtag($hashtag['hashtagid']);
}

$htmlTitle = sprintf(gettext("View content by %s hashtag"), "#{$handle}");

include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/hashtag_html.php');
include(__DIR__ . '/views/footer_html.php');
?>
