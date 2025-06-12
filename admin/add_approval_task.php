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

} elseif($_GET['topicType'] == Teleskope::TOPIC_TYPES['SURVEY']){
    $authCheck = $_COMPANY->getAppCustomization()['surveys']['approvals']['enabled'] && $_USER->canManageZonePosts(); // todo 4115
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

$taskid=0;
if (isset($_GET['approval_stage'])) {
    $stage = $_COMPANY->decodeId($_GET['approval_stage']);
    $configId = $_COMPANY->decodeId($_GET['approval_config_id']);
}
if (isset($_GET['edit'])) {
    $taskid = $_COMPANY->decodeId($_GET['edit']);
    $stage = $_COMPANY->decodeId($_GET['approval_stage']);
    $configId = $_COMPANY->decodeId($_GET['approval_config_id']);

    // Fetch the existing task details
    $task = TopicApprovalTask::GetTaskConfigurationDetails($taskid);
}

if (isset($_POST['submit'])){
    // validation
    if(!isset($_POST['approval_task_name']) || !isset($_POST['approval_task_details']) ){
            $_SESSION['error'] = time();
            $_SESSION['form_error'] = "Approval Task name and details must be filled out!";
    }else{
        $approval_config_id = $_COMPANY->decodeId($_POST['approval_config_id']);
        $task_details = $_POST['approval_task_details'];
        // Add '&nbsp' to all empty <p> tags. This will show empty <p> tags as newlines
        $task_details = preg_replace('#<p></p>#','<p>&nbsp;</p>', $task_details);
        $task_name = $_POST['approval_task_name'];
        $is_task_required = isset($_POST['isrequired']) ? 1 : 0;
        $is_proof_required = isset($_POST['proof_isrequired']) ? 1 : 0; 

        $addApprovalTask = TopicApprovalTask::AddOrUpdateApprovalTask($approval_config_id, $task_name, $task_details, $is_task_required, $is_proof_required, $taskid);
        $encodedStage = $_COMPANY->encodeId($stage);
        $encodedConfigId = $_COMPANY->encodeId($configId);
        if($addApprovalTask){
            Http::Redirect("topic_approval_tasks?topicType={$topicType}&approval_stage={$encodedStage}&approval_config_id={$encodedConfigId}");
        }

    }
}

// Get the Event cusom fields which are dropdowns and checkboxes only
$pagetitle = "Add {$topicName} Approval Task - Stage {$stage}";

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/add_approval_task.html');