<?php
// This file is right now being used only for announcement approvals. If works well can be renamed to topic_approval_config and then used for other topictypes. Rename Http::Redirect as well
require_once __DIR__.'/head.php';
$pagetitle = "Manage Approval Configuration";

$topicType = $_GET['topicType'] ?? '';
if ($topicType == Teleskope::TOPIC_TYPES['EVENT']) {
    $authCheck = $_COMPANY->getAppCustomization()['event']['approvals']['enabled'] && $_USER->canManageZoneEvents();
    $TopicClass = 'Event';
    $topicName = 'Event';
    $appCustomizationTitle = 'event';
    $allowAutoApprovalConfiguration = true;

} elseif ($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']) {
    $authCheck = $_COMPANY->getAppCustomization()['newsletters']['approvals']['enabled'] && $_USER->canManageZoneNewsletter();
    $TopicClass = 'Newsletter';
    $topicName = 'Newsletter';
    $appCustomizationTitle = 'newsletters';
    $allowAutoApprovalConfiguration = false; // Currently custom fields are not supported on Newsletters which are requried for auto approvals

} elseif ($topicType == Teleskope::TOPIC_TYPES['POST']) {
    $authCheck = $_COMPANY->getAppCustomization()['post']['approvals']['enabled'] && $_USER->canManageZonePosts();
    $TopicClass = 'Post';
    $topicName = Post::GetCustomName(false);
    $appCustomizationTitle = 'post';
    $allowAutoApprovalConfiguration = false; // Currently custom fields are not supported on Post which are requried for auto approvals

} elseif ($topicType == Teleskope::TOPIC_TYPES['SURVEY']) {
    $authCheck = $_COMPANY->getAppCustomization()['surveys']['approvals']['enabled'] && $_USER->canManageZonePosts();
    $TopicClass = 'Survey2';
    $topicName = Survey2::GetCustomName(false);
    $appCustomizationTitle = 'surveys';
    $allowAutoApprovalConfiguration = false; // Currently custom fields are not supported on Surveys which are required for auto approvals

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
$globalConfigurationForAllStages = $TopicClass::GetAllApprovalConfigurationRows(0, 0, 0);

if (isset($_GET['create_global_stage'])) {
    $stage = $_COMPANY->decodeId($_GET['create_global_stage']);
    if ($stage) {
        // Creating stages for topictypes
        $TopicClass::CreateApprovalConfiguration(0, 0, 0, $stage);
    }
    Http::Redirect("topic_approval_config?topicType={$topicType}");
}
// Sort $globalConfigurationForAllStages by stages 1, 2, 3 and so on.
usort($globalConfigurationForAllStages,function($a,$b) {
    return $a['approval_stage'] - $b['approval_stage'];
});

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manage_approval_configuration.html');