<?php
$hash = "events";
include_once __DIR__.'/add_delay_for_deep_linking.php';
require_once __DIR__.'/head.php';

global $_USER; /* @var User $_USER */
global $_ZONE;
$title = gettext('View Event');
//Data Validation
if (!isset($_GET['id']) ||
    ($eventid = $_COMPANY->decodeId($_GET['id']))<1 ||
    ($event = Event::GetEvent($eventid)) === NULL ||
    $event->val('isactive') == '0'){
    $showerror = gettext("Event link is not valid. Click continue to go to the home page");
    $next_link = 'home';
    include(__DIR__ . "/views/showmsg_and_continue.html");
    exit();
}

// Authorization Check
if (!$_USER->canViewContent($event->val('groupid'))) {
    echo "Access Denied (Insufficient permissions)";
    header(HTTP_FORBIDDEN);
    exit();
}

$topicTypes = Event::TOPIC_TYPES;
$groupid = $event->val('groupid');
// check group status
$groupObj = Group::GetGroup($groupid);
if(!$groupObj || !$groupObj->isActive()){
    $showerror = gettext("Event link is not valid. Click continue to go to the home page");
    $next_link = 'home';
    include(__DIR__ . "/views/showmsg_and_continue.html");
    exit();
}
if (!empty($_GET['survey_key'])){
    $_SESSION['show_event_survey'] = $_GET['survey_key'];
}
$_SESSION['show_event_id'] = $event->id();

if ($groupid) {    
    $event_link = "detail?id={$_COMPANY->encodeId($groupid)}&hash=events#events";
} else {
    $event_link = "home?show_admin_content=1&show_event_id=".$_COMPANY->encodeId($event->id());
}

Http::Redirect(Url::GetZoneAwareUrlBase($event->val('zoneid')) . $event_link);
