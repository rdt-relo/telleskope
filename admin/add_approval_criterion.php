<?php
require_once __DIR__.'/head.php';
if (
    (!$_COMPANY->getAppCustomization()['event']['approvals']['enabled'] ||
    !$_COMPANY->getAppCustomization()['newsletters']['approvals']['enabled']) &&
    !$_USER->canManageZoneEvents()) {
    header(HTTP_FORBIDDEN);
    echo "403 Forbidden (Access Denied)";
    exit();
}

$pagetitle = "Manage Auto Approval Configuration";
if (isset($_GET['approval_stage']) && isset($_GET['topicType'])) {
    $stage = $_COMPANY->decodeId($_GET['approval_stage']);

    if($_GET['topicType'] == Teleskope::TOPIC_TYPES['EVENT']){
        $globalConfigurationForCurrentStage = Event::GetAllApprovalConfigurationRows(0,0,0,$stage);
        $topicTypeLabel = 'Event';
    }elseif($_GET['topicType'] == Teleskope::TOPIC_TYPES['NEWSLETTER']){
        $globalConfigurationForCurrentStage = Newsletter::GetAllApprovalConfigurationRows(0,0,0,$stage);
        $topicTypeLabel = 'Newsletter';
    }elseif($_GET['topicType'] == Teleskope::TOPIC_TYPES['POST']){
        $globalConfigurationForCurrentStage = Post::GetAllApprovalConfigurationRows(0,0,0,$stage);
        $topicTypeLabel = Post::GetCustomName(false);
    }elseif($_GET['topicType'] == Teleskope::TOPIC_TYPES['SURVEY']){
        $globalConfigurationForCurrentStage = Survey2::GetAllApprovalConfigurationRows(0,0,0,$stage);
        $topicTypeLabel = Survey2::GetCustomName(false);
    }  
    $configId = $_COMPANY->decodeId($_GET['approval_config_id']);
}
// Get the Event cusom fields which are dropdowns and checkboxes only
$data = array();
$topicType = '';

if($_GET['topicType'] == Teleskope::TOPIC_TYPES['EVENT']){
    $topicType = Teleskope::TOPIC_TYPES['EVENT'];
    $data = Event::GetEventCustomFields(true,true,true);
    $topicTypeLabel = 'Event';
}elseif($_GET['topicType'] == Teleskope::TOPIC_TYPES['NEWSLETTER']){
    $topicType = Teleskope::TOPIC_TYPES['NEWSLETTER'];
    $data = Newsletter::GetEventCustomFields();
    $topicTypeLabel = 'Newsletter';
}elseif($_GET['topicType'] == Teleskope::TOPIC_TYPES['POST']){
    $topicType = Teleskope::TOPIC_TYPES['POST'];
    $data = Post::GetEventCustomFields();
    $topicTypeLabel = Post::GetCustomName(false);
}elseif($_GET['topicType'] == Teleskope::TOPIC_TYPES['SURVEY']){
    $topicType = Teleskope::TOPIC_TYPES['SURVEY'];
    $data = Survey2::GetEventCustomFields();
    $topicTypeLabel = Survey2::GetCustomName(false);
} 

if (isset($_POST['submit'])){
    // validation
    if(!isset($_POST['customFieldOption']) || !isset($_POST['custom_field_id']) ){
            $_SESSION['error'] = time();
            $_SESSION['form_error'] = "Select at least one custom field and it's option";
    }else{
        $custom_field_id = $_COMPANY->decodeId($_POST['custom_field_id']);
        $custom_field_option = $_COMPANY->decodeIdsInArray($_POST['customFieldOption']);
        $approval_config_id = $_COMPANY->decodeId($_POST['approval_config_id']);
        $approval_stage = $_POST['approval_stage'];
        $data = [];
        $data = Event::GetAutoApprovalDataByStage($stage);
        $fieldExists = false;
        // check for existing data
        foreach ($data as &$item) {
            if ($item['custom_field_id'] == $custom_field_id) {
                // If it exists, update the values
                $item['value'] =  $custom_field_option;
                $fieldExists = true;
                break; // No need to continue searching
            }
        }
       if(!$fieldExists){
            $data[] = [
                'custom_field_id' => $custom_field_id,
                'value' => $custom_field_option
            ];
        }
        $json_data = json_encode($data);
        $addCritirea = Event::UpdateAutoApprovalConfiguration($approval_config_id, $json_data);
        if($addCritirea){
            Http::Redirect("topic_auto_approval_config?topicType={$topicType}&approval_stage={$approval_stage}&approval_config_id={$_COMPANY->encodeId($approval_config_id)}");
        }

    }
}

// Get the Event cusom fields which are dropdowns and checkboxes only
$topic_config_redirect = "topic_approval_config?topicType={$topicType}";
$submitStatus = !empty($data) ? '' : 'disabled';
$pagetitle = "Add Auto Approval Criterion - Stage $stage ";

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/add_auto_approval_configuration.html');