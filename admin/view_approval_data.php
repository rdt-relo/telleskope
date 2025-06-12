<?php
 require_once __DIR__.'/head.php';

 // Authorization Check
 if (!$_USER->canManageAffinitiesContent()) {
   header("HTTP/1.1 403 Forbidden (Access Denied)");
   exit();
}

  //Data Validation
  if (($topicTypeId = $_COMPANY->decodeId($_GET['topicTypeId']))<1 ||
  ($approvalid = $_COMPANY->decodeId($_GET['approvalid']))<1 || 
  ($topicType = $_GET['topicType']) === FALSE
){
  header(HTTP_BAD_REQUEST);
  exit();
}

$latestOrgData = [];  // initializing for ORG 
$isActiveCheck = true; // for skipping survey inactive check
if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
  $topicTypeObj = Event::GetEvent($topicTypeId);
  $topicTypeLabel = 'Event';
  $viewPath = 'eventview';
  $appCustomizationTitle = 'event';
    //  ORG settings
    $fetchLatestEventOrganizations = $topicTypeObj?->getAssociatedOrganization() ?? array();
    //  ORG SPECIFIC - For Events Only
    $latestOrgData = !empty($fetchLatestEventOrganizations) ? Organization::ProcessOrgData($fetchLatestEventOrganizations) : [];
  
}elseif($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']){
  $topicTypeObj = Newsletter::GetNewsletter($topicTypeId);
  $topicTypeLabel = 'Newsletter';
  $viewPath = 'newsletter';
  $appCustomizationTitle = 'newsletters';
}elseif($topicType == Teleskope::TOPIC_TYPES['POST']){
  $topicTypeObj = Post::GetPost($topicTypeId);
  $topicTypeLabel = Post::GetCustomName(false);
  $viewPath = 'viewpost';
  $appCustomizationTitle = 'post';
}elseif($topicType == Teleskope::TOPIC_TYPES['SURVEY']){
  $topicTypeObj = Survey2::GetSurvey($topicTypeId);
  $topicTypeLabel = Survey2::GetCustomName(false);
  $viewPath = '';
  $appCustomizationTitle = 'surveys';
  $isActiveCheck = false;
}

$enc_topictype_id = $_COMPANY->encodeId($topicTypeId);
// Check if the object exists
if($topicTypeObj === NULL || ($isActiveCheck && !$topicTypeObj->val('isactive'))){
    Http::NotFound("The {$topicTypeLabel} link is invalid or the corresponding {$topicTypeLabel} has been deleted.");
}

$encGroupId = $_COMPANY->encodeId($topicTypeObj->val('groupid'));

$pagetitle = "Approval Log for ".$topicTypeLabel." - " . $topicTypeObj->getTopicTitle();
$timezone = @$_SESSION['timezone'];
// get the approver note
$approval = $topicTypeObj->getApprovalObject();
$approvalNotes = $approval->getApprovalLogs() ?? '';
// get approval task details
$approvalTasks = $approval->GetAllTasksByApproval() ?? array();
// Topic scope
$topicGroupName = Group::GetGroupName($topicTypeObj->val('groupid'));
if ($topicTypeObj->val('collaborating_groupids')) {
  $topicGroupName = $topicTypeObj->getFormatedEventCollaboratedGroupsOrChapters();
}
$listsNameCsv = '';
if($topicTypeObj->val('listids') != 0 ){
    $listsNameCsv = DynamicList::GetFormatedListNameByListids($topicTypeObj->val('listids'));
}
$topicChapterName = "";
$topicChannelName = "";
if($topicTypeObj->val('chapterid')){
  if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
      $topicChapterName = implode(', ', $topicTypeObj->getEventChapterNames());
  }elseif($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']) {
      $topicChapterName = implode(', ', $topicTypeObj->getNewsletterChapterNames());                
  }elseif($topicType == Teleskope::TOPIC_TYPES['POST']) {
    $topicChapterName = implode(', ', $topicTypeObj->getPostChapterNames());                
  }elseif($topicType == Teleskope::TOPIC_TYPES['SURVEY']) {
    $topicChapterName = implode(', ', $topicTypeObj->getSurveyChapterNames());                
  }
}

if($topicTypeObj->val('channelid')){
  $channelNameArr  =  Group::GetChannelName($topicTypeObj->val('channelid'),$topicTypeObj->val('groupid'));
  $topicChannelName = $channelNameArr['channelname'];
}
 $standalone_page = true;

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/admin_view_approval_details.html');
?>