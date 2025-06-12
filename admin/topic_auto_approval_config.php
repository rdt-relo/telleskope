<?php
require_once __DIR__.'/head.php';
if (
    !($_COMPANY->getAppCustomization()['post']['approvals']['enabled'] || $_COMPANY->getAppCustomization()['event']['approvals']['enabled'] || $_COMPANY->getAppCustomization()['newsletters']['approvals']['enabled']) ||
    !$_USER->canManageZoneEvents() ||
    empty($topicType = $_GET['topicType'])
) {
    header(HTTP_FORBIDDEN);
    echo "403 Forbidden (Access Denied)";
    exit();
}

$pagetitle = "Manage Auto Approval Configuration";

if (isset($_GET['approval_stage'])) {
    $stage = $_COMPANY->decodeId($_GET['approval_stage']);
    //config id
    $configId = $_COMPANY->decodeId($_GET['approval_config_id']);
}

// Get the Auto approval config data for the stage above
$data = array();

if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
    $autoApprovalConfigData = Event::GetAutoApprovalDataByStage($stage);
    $topicTypeLabel = 'Event';
}elseif($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']){
    $autoApprovalConfigData = Newsletter::GetAutoApprovalDataByStage($stage);
    $topicTypeLabel = 'Newsletter';
}elseif($topicType == Teleskope::TOPIC_TYPES['POST']){
    $autoApprovalConfigData = Post::GetAutoApprovalDataByStage($stage);
    $topicTypeLabel = Post::GetCustomName(false);
}elseif($topicType == Teleskope::TOPIC_TYPES['SURVEY']){
    $autoApprovalConfigData = Survey2::GetAutoApprovalDataByStage($stage);
    $topicTypeLabel = Survey2::GetCustomName(false);
}
$topic_config_redirect = "topic_approval_config?topicType={$topicType}";
$type = ['','Single','Multiple','Open Ended'];
$pagetitle = "Auto Approval Configuration - Stage $stage ";

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manage_auto_approval_configuration.html');