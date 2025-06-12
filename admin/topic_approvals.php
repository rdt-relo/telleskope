<?php
require_once __DIR__.'/head.php';


$topicType = $_GET['topicType'] ?? '';
if ($topicType == Teleskope::TOPIC_TYPES['EVENT']) {
    $authCheck = $_COMPANY->getAppCustomization()['event']['approvals']['enabled'];
    $canManageTopicApprovalConfig = $_USER->canManageZoneEvents();
    $TopicClass = 'Event';
    $topicName = 'Event';
    $appCustomizationTitle = 'event';
    $viewFilename = 'event_approvals.html';

} elseif ($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']) {
    $authCheck = $_COMPANY->getAppCustomization()['newsletters']['approvals']['enabled'];
    $canManageTopicApprovalConfig = $_USER->canManageZoneNewsletter();
    $TopicClass = 'Newsletter';
    $topicName = 'Newsletter';
    $appCustomizationTitle = 'newsletters';
    $viewFilename = 'newsletter_approvals.html';

} elseif ($topicType == Teleskope::TOPIC_TYPES['POST']) {
    $authCheck = $_COMPANY->getAppCustomization()['post']['approvals']['enabled'];
    $canManageTopicApprovalConfig =  $_USER->canManageZonePosts();
    $TopicClass = 'Post';
    $topicName = Post::GetCustomName(false);
    $appCustomizationTitle = 'post';
    $viewFilename = 'post_approvals.html';

} elseif ($topicType == Teleskope::TOPIC_TYPES['SURVEY']) {
    $authCheck = $_COMPANY->getAppCustomization()['surveys']['approvals']['enabled'];
    $canManageTopicApprovalConfig =  $_USER->canManageZonePosts(); // 4115 todo - permissions change
    $TopicClass = 'Survey2';
    $topicName = Survey2::GetCustomName(false);
    $appCustomizationTitle = 'surveys';
    $viewFilename = 'survey_approvals.html';

} else {
    header(HTTP_FORBIDDEN);
    echo "403 Forbidden (Access Denied) - Invalid TopicType: ". htmlspecialchars($topicType);
    exit();
}

if (!$authCheck) {
    header(HTTP_FORBIDDEN);
    echo "403 Forbidden (Access Denied)";
    exit();
}

$topic_approval_url = "topic_approvals?topicType={$topicType}";
$pagetitle = "Manage {$topicName} Approvals";

$stageRows = array();
for ($stage=1; $stage <= Approval::APPROVAL_STAGE_MAX; $stage++) {
    $stageRows[(string)$stage] = $TopicClass::GetAllApproversByStage($stage,0,0,0);
}
$approvalStatus = !empty($_GET['approvalStatus'])? $_GET['approvalStatus'] :'processing';
$requestYear =   !empty($_GET['requestYear']) ? $_GET['requestYear'] : date('Y');
$allApprovals = $TopicClass::GetAllApprovalRows($approvalStatus, $requestYear, 0);

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/' . $viewFilename);
?>
