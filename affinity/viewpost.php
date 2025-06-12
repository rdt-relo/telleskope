<?php
include_once __DIR__.'/add_delay_for_deep_linking.php';
require_once __DIR__.'/head.php';

global $_USER; /* @var User $_USER */
global $_ZONE;
$title = gettext('View Post');
//Data Validation
if (!isset($_GET['id']) ||
    ($postid = $_COMPANY->decodeId($_GET['id']))<1 ||
    ($post = Post::GetPost($postid)) === NULL ||
    $post->val('isactive') == '0'){
    $showerror = gettext("Announcement link is not valid. Click continue to go to the home page");
    $next_link = 'home';
    include(__DIR__ . "/views/showmsg_and_continue.html");
    exit();
}

// Authorization Check
if (!$_USER->canViewContent($post->val('groupid'))) {
    echo "Access Denied (Insufficient permissions)";
    header(HTTP_FORBIDDEN);
    exit();
}

$topicTypes = Post::TOPIC_TYPES;
$groupid = $post->val('groupid');
$_SESSION['show_announcement_id'] = $post->id();
if ($groupid) {
    $post_link = "detail?id={$_COMPANY->encodeId($groupid)}&hash=announcements#announcements";
} else {
    $post_link = "home?show_admin_content=1";
}

Http::Redirect(Url::GetZoneAwareUrlBase($post->val('zoneid')) . $post_link);
