<?php
include_once __DIR__.'/add_delay_for_deep_linking.php';
require_once __DIR__.'/head.php';

global $_USER; /* @var User $_USER */
global $_ZONE;
$title = gettext('View Discussion');
//Data Validation
if (!isset($_GET['id']) ||
    ($discussionid = $_COMPANY->decodeId($_GET['id']))<1 ||
    ($discussion = Discussion::GetDiscussion($discussionid)) === NULL ||
    $discussion->val('isactive') == '0'){
    $showerror = gettext("Discussion link is not valid. Click continue to go to the home page");
    $next_link = 'home';
    include(__DIR__ . "/views/showmsg_and_continue.html");
    exit();
}

// Authorization Check
if (!$_USER->canViewContent($discussion->val('groupid'))) {
    echo "Access Denied (Insufficient permissions)";
    header(HTTP_FORBIDDEN);
    exit();
}

$topicTypes = Discussion::TOPIC_TYPES;
$groupid = $discussion->val('groupid'); 
$_SESSION['show_discussion_id'] = $discussionid;
$discussion_link = "detail?id={$_COMPANY->encodeId($groupid)}&hash=discussion#discussion";
Http::Redirect(Url::GetZoneAwareUrlBase($discussion->val('zoneid')) . $discussion_link);

