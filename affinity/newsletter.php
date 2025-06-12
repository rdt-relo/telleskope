<?php
include_once __DIR__.'/add_delay_for_deep_linking.php';
require_once __DIR__.'/head.php';

global $_USER; /* @var User $_USER */
global $_ZONE;
$title = gettext('View Newsletter');
//Data Validation
if (!isset($_GET['id']) ||
    ($newsletterid = $_COMPANY->decodeId($_GET['id']))<1 ||
    ($newsletter = Newsletter::GetNewsletter($newsletterid)) === NULL ||
    $newsletter->val('isactive') == '0' ||
    ($newsletter->val('isactive') != '1' && !($_GET['approval_review'] ?? 0)) // unpublished newsletters can only be opened in approval workflows
){
    $showerror = gettext("Newsletter link is not valid. Click continue to go to the home page");
    $next_link = 'home';
    include(__DIR__ . "/views/showmsg_and_continue.html");
    exit();
}

// Authorization Check
if (!$_USER->canViewContent($newsletter->val('groupid'))) {
    echo "Access Denied (Insufficient permissions)";
    header(HTTP_FORBIDDEN);
    exit();
}

$topicTypes = Newsletter::TOPIC_TYPES;
$groupid = $newsletter->val('groupid');
$_SESSION['show_newsletter_id'] = $newsletter->id();
if ($groupid) {
    $newsletter_link = "detail?id={$_COMPANY->encodeId($groupid)}&hash=newsletters#newsletters";
} else {
    $newsletter_link = "home?show_admin_content=1";
}

Http::Redirect(Url::GetZoneAwareUrlBase($newsletter->val('zoneid')) . $newsletter_link);
