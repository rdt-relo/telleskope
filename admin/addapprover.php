<?php
require_once __DIR__.'/head.php';
global $_COMPANY, $_ZONE;

if (
    !isset($_GET['topicType']) ||
    !isset($_GET['config_id']) || ($approvalConfigId = $_COMPANY->decodeId($_GET['config_id'])) < 1
) {
    header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
    exit();
}
$topicType = $_GET['topicType'];
$pagetitle = '';

if ($topicType == TELESKOPE::TOPIC_TYPES['EVENT']) { // Since this add approver file can be reused, we will add each allowed block here
    $pagetitle = gettext("Add Event Approver");
    $authCheck = $_COMPANY->getAppCustomization()['event']['approvals']['enabled'] && $_USER->canManageZoneEvents();
}elseif($topicType == TELESKOPE::TOPIC_TYPES['NEWSLETTER']){
    $pagetitle = gettext("Add Newsletter Approver");
    $authCheck = $_COMPANY->getAppCustomization()['newsletters']['approvals']['enabled'] && $_USER->canManageAffinitiesContent();
}elseif($topicType == TELESKOPE::TOPIC_TYPES['POST']){
    $pagetitle = gettext("Add Announcements Approver");
    $authCheck = $_COMPANY->getAppCustomization()['post']['approvals']['enabled'] && $_USER->canManageAffinitiesContent();
}elseif($topicType == TELESKOPE::TOPIC_TYPES['SURVEY']){
    $pagetitle = gettext("Add Surveys Approver");
    $authCheck = $_COMPANY->getAppCustomization()['surveys']['approvals']['enabled'] && $_USER->canManageAffinitiesContent();
}

$topic_config = "topic_approval_config?topicType={$topicType}";
 
if (!$authCheck) {
    header(HTTP_FORBIDDEN);
    echo "403 Forbidden (Access Denied)";
    exit();
}

// Authorization Check - need suggestions if any specific permissions needed to do this

if(isset($_POST['approver_assign'])){
	if (
        !isset($_POST['userid']) ||
        ($approverUserId = $_COMPANY->decodeId($_POST['userid'])) < 1 
    ) {
		header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
		exit();
	}
    // Role title
    $role_title = $_POST['role-title'] ?? "";
    if($topicType == TELESKOPE::TOPIC_TYPES['EVENT']){
        $addApprover = Event::AddApproverToApprovalConfiguration($approvalConfigId, $approverUserId, $role_title);
    }elseif($topicType == TELESKOPE::TOPIC_TYPES['NEWSLETTER']){
        $addApprover = Newsletter::AddApproverToApprovalConfiguration($approvalConfigId, $approverUserId, $role_title);
    }elseif($topicType == TELESKOPE::TOPIC_TYPES['POST']){
        $addApprover = Post::AddApproverToApprovalConfiguration($approvalConfigId, $approverUserId, $role_title);
    }elseif($topicType == TELESKOPE::TOPIC_TYPES['SURVEY']){
        $addApprover = Survey2::AddApproverToApprovalConfiguration($approvalConfigId, $approverUserId, $role_title);
    }else {
        $addApprover = null;
    }

    if ($addApprover) {
        Http::Redirect($topic_config);
    } else {
        $error = 'Unable to add the user as an approver';
        $_SESSION['error'] = time();
        //header("refresh: 5; url = event_approval_config.php");
    }
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/addapprover.html');