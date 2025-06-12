<?php
require_once __DIR__.'/head.php';

$topicType = $_GET['topicType'] ?? '';
if ($topicType == Teleskope::TOPIC_TYPES['EVENT']) {
    $authCheck = $_COMPANY->getAppCustomization()['event']['approvals']['enabled'] && $_USER->canManageZoneEvents();
    $TopicClass = 'Event';
    $topicName = 'Event';
    $appCustomizationTitle = 'event';

} elseif ($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']) {
    $authCheck = $_COMPANY->getAppCustomization()['newsletters']['approvals']['enabled'] && $_USER->canManageZoneNewsletter();
    $TopicClass = 'Newsletter';
    $topicName = 'Newsletter';
    $appCustomizationTitle = 'newsletters';

} elseif ($topicType == Teleskope::TOPIC_TYPES['POST']) {
    $authCheck = $_COMPANY->getAppCustomization()['post']['approvals']['enabled'] && $_USER->canManageZonePosts();
    $TopicClass = 'Post';
    $topicName = Post::GetCustomName(false);
    $appCustomizationTitle = 'post';

} elseif ($topicType == Teleskope::TOPIC_TYPES['SURVEY']) {
    $authCheck = $_COMPANY->getAppCustomization()['surveys']['approvals']['enabled'] && $_USER->canManageZonePosts();
    $TopicClass = 'Survey2';
    $topicName = Survey2::GetCustomName(false);
    $appCustomizationTitle = 'surveys';

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


if (isset($_GET['approval_stage'])) {
    $stage = $_COMPANY->decodeId($_GET['approval_stage']);
    //config id
    $configId = $_COMPANY->decodeId($_GET['approval_config_id']);
}

$topic_config_redirect =  "topic_approval_config?topicType={$topicType}";

// Get the Approval Tasks data for the stage
$data = array();
$allTasks = TopicApprovalTask::GetAllTasksByConfig($configId);

$pagetitle = "Manage {$topicName} Approval Tasks - Stage {$stage}";

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manage_approval_tasks.html');