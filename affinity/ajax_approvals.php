<?php

define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__.'/head.php';

global $_COMPANY; /* @var Company $_COMPANY */
global $_USER;/* @var User $_USER */
global $_ZONE;
global $db;

###### All Ajax Calls For Approvals - Events, Newsletters, Post ##########
## OK
if (isset($_GET['openApprovalNoteModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $enc_topictype_id = $_GET['topicTypeId'];
    //Data Validation
   if (($topicTypeId = $_COMPANY->decodeId($enc_topictype_id)) < 1 ||
        ($topicType = $_GET['topicType']) === NULL) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    //   Verify topicTypes and then fetch object. If used alot then we can move it to a method
    if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
        $topicTypeObj = Event::GetEvent($topicTypeId);
        if ($topicTypeObj->val('collaborating_groupids_pending') || $topicTypeObj->val('collaborating_chapterids_pending')) {
                AjaxResponse::SuccessAndExit_STRING(2, $_COMPANY->encodeId($topicTypeObj->id()), gettext('This is a collaborative event. All collaboration requests must be resolved before submitting for approval.'), '');
        }
        $topicTypeLabel = 'Event';
        $stageRowsData = Event::GetAllApproversByStage(1,0,0,0);
    }elseif($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']){
        $topicTypeObj = Newsletter::GetNewsletter($topicTypeId);
        $topicTypeLabel = 'Newsletter';
        $stageRowsData = Newsletter::GetAllApproversByStage(1,0,0,0);
    }elseif($topicType == Teleskope::TOPIC_TYPES['POST']){
        $topicTypeObj = Post::GetPost($topicTypeId);
        $topicTypeLabel = Post::GetCustomName(false);
        $stageRowsData = Post::GetAllApproversByStage(1,0,0,0);
    }elseif($topicType == Teleskope::TOPIC_TYPES['SURVEY']){
        $topicTypeObj = Survey2::GetSurvey($topicTypeId);
        $topicTypeLabel = Survey2::GetCustomName(false);
        $stageRowsData = Survey2::GetAllApproversByStage(1,0,0,0);
    }
    $TopicClass = Teleskope::TOPIC_TYPE_CLASS_MAP[$topicType];

    [$approver_cc_emails, $approver_min_approvers_limit, $approver_max_approvers_limit, $stage_approval_email_subject, $stage_approval_email_body, $stage_denial_email_subject, $stage_denial_email_body, $disallow_submitter_approval] = $TopicClass::GetApprovalConfigurationEmailSettings($stageRowsData['approval_config_id'] ?? 0);

    // Disallow submitter approval
    if($stageRowsData['approver_userids'] && $disallow_submitter_approval){
        // Here current user will be submitter for approval
        $stageRowsData['approver_userids'] = array_values(array_filter(
            $stageRowsData['approver_userids'], fn($userid) => $userid != $_USER->id()
        ));
    }
    include(__DIR__ . "/views/event_approval_modal.php"); 

}
elseif (isset($_GET['requestTopicApproval']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($topicTypeId = $_COMPANY->decodeId($_POST['topicTypeId'])) < 1 ||
        ($topicType = $_POST['topicType']) === NULL) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    //   Verify topicTypes and then fetch object. If used alot then we can move it to a method
    if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
        $topicTypeObj = Event::GetEvent($topicTypeId);
        $topicTypeLabel = 'Event';
    }elseif($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']){
        $topicTypeObj = Newsletter::GetNewsletter($topicTypeId);
        $topicTypeLabel = 'Newsletter';
    }elseif($topicType == Teleskope::TOPIC_TYPES['POST']){
        $topicTypeObj = Post::GetPost($topicTypeId);
        $topicTypeLabel = Post::GetCustomName(false);
    }elseif($topicType == Teleskope::TOPIC_TYPES['SURVEY']){
        $topicTypeObj = Survey2::GetSurvey($topicTypeId);
        $topicTypeLabel = Survey2::GetCustomName(false);
    }

    // add a note
    $approverNote = $_POST['requestNote'] ?? '';
    // Selected approver ids
    $selectedApprovers = $_COMPANY->decodeIdsInArray($_POST['selectedApprovers'] ?? array());

    // Precheck if approval configuration exists
    $stageConfiguration = TopicApprovalConfiguration::GetApprovalConfigurationByTopicAndStage($topicType, 1); // runs only on stage 1
    if(!$stageConfiguration){
        AjaxResponse::SuccessAndExit_STRING(0, $_COMPANY->encodeId($topicTypeId), sprintf(gettext("Please add %s configuration and approvers from admin panel before requesting for an approval."), $topicTypeLabel), gettext('Error'));
    }
    // create a new approval request
    if($addApproval = $topicTypeObj->requestNewApproval($approverNote, $selectedApprovers)){
        AjaxResponse::SuccessAndExit_STRING(1, $_COMPANY->encodeId($topicTypeId), gettext("Approval request sent"), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, $_COMPANY->encodeId($topicTypeId), gettext("Error creating approval request"), gettext('Error'));
}
elseif (isset($_GET['viewApprovalStatus']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($topicTypeId = $_COMPANY->decodeId($_GET['topicTypeId'])) < 1 ||
        ($topicType = $_GET['topicType']) === FALSE)
       {
          header(HTTP_BAD_REQUEST);
          exit();
      }
    //   Verify topicTypes and then fetch object. If used alot then we can move it to a method
    if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
        $topicTypeObj = Event::GetEvent($topicTypeId);
        $topicTypeLabel = 'Event';
    }elseif($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']){
        $topicTypeObj = Newsletter::GetNewsletter($topicTypeId);
        $topicTypeLabel = 'Newsletter';
    }elseif($topicType == Teleskope::TOPIC_TYPES['POST']){
        $topicTypeObj = Post::GetPost($topicTypeId);
        $topicTypeLabel = Post::GetCustomName(false);
    }elseif($topicType == Teleskope::TOPIC_TYPES['SURVEY']){
        $topicTypeObj = Survey2::GetSurvey($topicTypeId);
        $topicTypeLabel = Survey2::GetCustomName(false);
    }
    // Check if the object exists
    if($topicTypeObj === NULL){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $enc_topictype_id = $_COMPANY->encodeId($topicTypeId);
    // get the approver note
    $approval = $topicTypeObj->getApprovalObject();

    if($approval === NULL){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $approvalNotes = $approval->getApprovalLogs() ?? '';

    include(__DIR__ . "/views/view_approval_status.html");
}

elseif (isset($_GET['getMyTopicApprovalsData']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    // TODO: add Data Validation based on approval
    if (empty($topicType = $_GET['getMyTopicApprovalsData'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
     
    $tableHeaders =[];
    $viewPath='';
    $approvalStatus = !empty($_GET['approvalStatus'])? $_GET['approvalStatus'] :'processing';
    $requestYear =   !empty($_GET['requestYear']) ? $_GET['requestYear'] : date('Y');
    $collaborationStatus = !empty($_GET['collaborationStatus']) ? $_GET['collaborationStatus'] : 'all';
    $_SESSION['approvalStatus'] = $approvalStatus;
    $_SESSION['approvalRequestYear'] = $requestYear;
    $_SESSION['collaborationStatus'] = $collaborationStatus;
    $topicTypeLabel = '';

    if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
        $allApprovals = Event::GetAllApprovalRows($approvalStatus, $requestYear, $_USER->id());
        $approvable_stages_that_user_can_approve = Event::GetAllTheStagesThatUserCanApprove($_USER->id());
        $topicTypeLabel = 'Event';
        $tableHeaders = [
            'ID',
            'Title',
            'Scope',
            'Event Start Date',
            'Publish Status',
            'Approval Status',
            'Action'
        ];
        $viewPath = 'eventview';
    }elseif($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']){
        $allApprovals = Newsletter::GetAllApprovalRows($approvalStatus,$requestYear, $_USER->id());
        $approvable_stages_that_user_can_approve = Newsletter::GetAllTheStagesThatUserCanApprove($_USER->id());
        $topicTypeLabel = 'Newsletter';
        $tableHeaders = [
            'ID',
            'Title',
            'Scope',
            'Publish Status',
            'Approval Status',
            'Action'
        ];
        $viewPath = 'newsletter';
    }elseif($topicType == Teleskope::TOPIC_TYPES['POST']){
        $allApprovals = Post::GetAllApprovalRows($approvalStatus,$requestYear, $_USER->id());
        $approvable_stages_that_user_can_approve = Post::GetAllTheStagesThatUserCanApprove($_USER->id());
        $topicTypeLabel = Post::GetCustomName(false);
        $tableHeaders = [
            'ID',
            'Title',
            'Scope',
            'Publish Status',
            'Approval Status',
            'Action'
        ];
        $viewPath = 'viewpost';
    }elseif($topicType == Teleskope::TOPIC_TYPES['SURVEY']){
        $allApprovals = Survey2::GetAllApprovalRows($approvalStatus,$requestYear, $_USER->id());
        $approvable_stages_that_user_can_approve = Survey2::GetAllTheStagesThatUserCanApprove($_USER->id());
        $topicTypeLabel = Survey2::GetCustomName(false);
        $tableHeaders = [
            'ID',
            'Title',
            'Scope',
            'Publish Status',
            'Approval Status',
            'Action'
        ];
    }
    if(($allApprovals === NULL )){
        echo $response = -1;
        exit();
    }

    $pagetitle = "Manage ".$topicTypeLabel." Approvals";
    include(__DIR__ . "/views/templates/my_approvals.template.php");
}
elseif (isset($_GET['viewApprovalDetail']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($topicTypeId = $_COMPANY->decodeId($_GET['topicTypeId']))<1 ||
        ($approvalid = $_COMPANY->decodeId($_GET['approvalid']))<1 ||
        (empty($topicType = $_GET['topicType']))
        
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $pageSource = '';
    if (isset($_GET['pageSource']) && in_array($_GET['pageSource'], ['my_approvals', 'event_action'])) {
        $pageSource = $_GET['pageSource'];
    }
    // Call org method to get data.
    $latestOrgData = [];

    //   Verify topicTypes and then fetch object. If used alot then we can move it to a method
    $viewPath = '';
    $isActiveCheck = true; // for survey inactive status
    if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
        $topicTypeObj = Event::GetEvent($topicTypeId);
        $topicTypeLabel = 'Event';
        $viewPath = 'eventview';
        $appCustomizationTitle = 'event';
        $fetchLatestEventOrganizations = $topicTypeObj?->getAssociatedOrganization() ?? array();
        //  ORG SPECIFIC - Events Only
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
        $appCustomizationTitle = 'surveys';
        $isActiveCheck = false;
    }
    // Check if the object exists
    if($topicTypeObj === NULL || ($isActiveCheck && !$topicTypeObj->val('isactive'))){
        AjaxResponse::SuccessAndExit_STRING(-1, '', gettext($topicTypeLabel." not found ... it might have been deleted!"), gettext('Error'));
        exit();
    }

    $enc_topictype_id = $_COMPANY->encodeId($topicTypeId); 
    $encGroupId = $_COMPANY->encodeId($topicTypeObj->val('groupid'));  
    // get the approver note
    $approval = $topicTypeObj->getApprovalObject();
    $approvalNotes = $approval->getApprovalLogs() ?? '';
        // scope
        $topicGroupName = Group::GetGroupName($topicTypeObj->val('groupid'));
        if ($topicTypeObj->val('collaborating_groupids')) {
            $topicGroupName = $topicTypeObj->getFormatedEventCollaboratedGroupsOrChapters();
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
        $listsNameCsv = '';
        if($topicTypeObj->val('listids') != 0){
            $listsNameCsv = DynamicList::GetFormatedListNameByListids($topicTypeObj->val('listids'));
        }
        // get approval task details
        $approvalTasks = $approval->GetAllTasksByApproval() ?? array();
        // use this to exclude anything from affinity modal
        $excludeFromView = array();
        $excludeFromView['organization_approvals'] = true; // Disallow organization_approvals from affinities; it is admin panel feature only
        $excludeFromView['organization_send_emails_to_contacts'] = true;
        if ($pageSource == 'my_approvals') {
            unset($excludeFromView['organization_send_emails_to_contacts']);
        }
        include(__DIR__ . '/views/affinity_view_approval_details.html');
}
elseif (isset($_GET['saveTopicApprovalNote']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        !isset($_POST['topicTypeId']) ||
        ($topicType = $_POST['topicType']) === FALSE
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $topicTypeId = $_COMPANY->decodeId($_POST['topicTypeId']);
    // check the topicType and then check if it's deleted
    if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
        $topicTypeObj = Event::GetEvent($topicTypeId);
        $topicTypeLabel = 'Event';
    }elseif($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']){
        $topicTypeObj = Newsletter::GetNewsletter($topicTypeId);
        $topicTypeLabel = 'Newsletter';
    }elseif($topicType == Teleskope::TOPIC_TYPES['POST']){
        $topicTypeObj = Post::GetPost($topicTypeId);
        $topicTypeLabel = Post::GetCustomName(false);
    }elseif($topicType == Teleskope::TOPIC_TYPES['SURVEY']){
        $topicTypeObj = Survey2::GetSurvey($topicTypeId);
        $topicTypeLabel = Survey2::GetCustomName(false);
    }
    if(($topicTypeObj === NULL )  || !$topicTypeObj->val('isactive')){
        echo $response = -1;
        exit();
    }

    // TODO: Modify Authorization Check
    // if (!$event->loggedinUserCanUpdateEvent() && !$event->loggedinUserCanPublishEvent()) {
    //     header(HTTP_FORBIDDEN);
    //     exit();
    // }
    
    // assign object
    $approval = $topicTypeObj->getApprovalObject();
    $saveNote = $approval->addGeneralNote($_POST['note']);
    if($saveNote){
        $enc_approvalid = $_COMPANY->encodeId($approval->id());
        AjaxResponse::SuccessAndExit_STRING(1, $enc_approvalid, gettext('Note added successfully'), gettext('Success'));
    }else{
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Try again later'), gettext('Error'));
    }

}
elseif (isset($_GET['assignUserForApprovalModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageZoneEvents()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    if (($topicTypeId = $_COMPANY->decodeId($_GET['topicTypeId']))<1 ||
    ($approvalid = $_COMPANY->decodeId($_GET['approvalid']))<1 ||
    (empty($topicType = $_GET['topicType'])) ||
    !isset($_GET['assignUserForApprovalModal'])
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    //   Verify topicTypes and then fetch object. If used alot then we can move it to a method
    if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
        $topicTypeObj = Event::GetEvent($topicTypeId);
        $topicTypeLabel = 'Event';
    }elseif($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']){
        $topicTypeObj = Newsletter::GetNewsletter($topicTypeId);
        $topicTypeLabel = 'Newsletter';
    }elseif($topicType == Teleskope::TOPIC_TYPES['POST']){
        $topicTypeObj = Post::GetPost($topicTypeId);
        $topicTypeLabel = Post::GetCustomName(false);
    }elseif($topicType == Teleskope::TOPIC_TYPES['SURVEY']){
        $topicTypeObj = Survey2::GetSurvey($topicTypeId);
        $topicTypeLabel = Survey2::GetCustomName(false);
    }
    // Check if the object exists
    if($topicTypeObj === NULL || !$topicTypeObj->val('isactive') || $topicTypeObj->val('isactive') == Event::STATUS_INACTIVE){
        AjaxResponse::SuccessAndExit_STRING(-1, '', gettext($topicTypeLabel." not found ... it might have been deleted!"), gettext('Error'));
        exit();
    }

    $approvalStage = (int)$_GET['assignUserForApprovalModal'];
    $approvalStage = $approvalStage > Approval::APPROVAL_STAGE_MAX ? Approval::APPROVAL_STAGE_MAX : $approvalStage;

    $modalTitle = gettext("Assign ".$topicTypeLabel." Approver");

    // Get the approvar data according to the stage
    $stageTitle = "Stage {$approvalStage}";

    $approvalStageConfig = TopicApprovalConfiguration::GetApprovalConfigurationByTopicAndStage($topicType,$approvalStage);
    $stageApprovers = $approvalStageConfig->getApprovers();
    $stageApproverUserIds = array_filter(array_column($stageApprovers, 'approver_userid'));

    // check if a user is already assigned
    $approval = $topicTypeObj->getApprovalObject();
    $approvalStage = (int)$approval->val('approval_stage');
    $assigned_to = "";
    if($approval->val('assigned_to') && in_array($approval->val('assigned_to'), $stageApproverUserIds)){
        $assigned_to = $approval->val('assigned_to');
    }

    include(__DIR__ . "/views/assign_topic_approval_modal.html");
}
elseif (isset($_GET['updateApprovalRequest']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        !isset($_POST['action']) ||
        ($topicTypeId = $_COMPANY->decodeId($_POST['topicTypeId']))<1 ||
        (empty($topicType = $_POST['topicType']))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    // check object and status
    if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
        $topicTypeObj = Event::GetEvent($topicTypeId);
        $topicTypeLabel = 'Event';
    }elseif($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']){
        $topicTypeObj = Newsletter::GetNewsletter($topicTypeId);
        $topicTypeLabel = 'Newsletter';
    }elseif($topicType == Teleskope::TOPIC_TYPES['POST']){
        $topicTypeObj = Post::GetPost($topicTypeId);
        $topicTypeLabel = Post::GetCustomName(false);
    }elseif($topicType == Teleskope::TOPIC_TYPES['SURVEY']){
        $topicTypeObj = Survey2::GetSurvey($topicTypeId);
        $topicTypeLabel = Survey2::GetCustomName(false);
    }

    if(
        $topicTypeObj === NULL ||
        $topicTypeObj->isPublished() ||  // If topic type is published then do not allow approve/deny/assign
        $topicTypeObj->isAwaiting()  // Same for topic scheduled for publishing
    ){
        header(HTTP_BAD_REQUEST);
        exit();

    }

    // assign object
    $approval = $topicTypeObj->getApprovalObject();
    // Check for approval stage
    if (
        ($currentApprovalStage = $_COMPANY->decodeId($_POST['approvalStage'])) < 1 &&
        !$approval->isApprovalStage($currentApprovalStage)
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }    

    // set the post data here
    $approver_note = $_POST['approverNote'] ?? '';

    if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
        $stageRowsData = Event::GetAllApproversByStage($currentApprovalStage,0,0,0);
    }elseif($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']){
        $stageRowsData = Newsletter::GetAllApproversByStage($currentApprovalStage,0,0,0);
    }elseif($topicType == Teleskope::TOPIC_TYPES['POST']){
        $stageRowsData = Post::GetAllApproversByStage($currentApprovalStage,0,0,0);
    }elseif($topicType == Teleskope::TOPIC_TYPES['SURVEY']){
        $stageRowsData = Survey2::GetAllApproversByStage($currentApprovalStage,0,0,0);
    }

    // Selected approver ids
    $selectedApprovers = array();
    if (isset($_POST['selectedApproversId'])) {
        $selectedApprovers = $_COMPANY->decodeIdsInArray($_POST['selectedApproversId'] ?: array());
    } elseif (isset($_POST['selectedApproversIdDefaultList'])) {
        # In some cases where max approver configuration is set to 0, we will get all values in selectedApproversIdDefaultList
        $selectedApprovers = $_COMPANY->decodeIdsInArray(explode(',', $_POST['selectedApproversIdDefaultList'] ?: ''));
    }

    // Check for approved or denied
    if ($_POST['action'] == 'assign' && !empty($_POST['approverId'])) {
        $approverId = $_COMPANY->decodeId($_POST['approverId']);
        if($approval->assignTo($approverId,$approver_note)) {
            print(1);
            exit();
        }
    } else { // For approve or deny we will check if the user is approver in the stage.
        // Authorization Check
        $stageApproverUserIds = $stageRowsData['approver_userids'] ?? array();
        if (!in_array( $_USER->id() , $stageApproverUserIds)) {
            header(HTTP_FORBIDDEN);
            exit();
        }

        if ($_POST['action'] == 'approve') {
            // approve
            $approval->recalculateAndUpdateApproverUserids($selectedApprovers);
            $approved = $approval->approve($approver_note);
            // once it's approved, check for pre-approval
            if ($approved) {
                //$updated_approval = $topicTypeObj->getApprovalObject();
                //$updated_approval->checkAutoApproval();
                print(1);
            }
            exit();
        } elseif ($_POST['action'] == 'deny') {
            $approval->deny($approver_note);
            print(1);
            exit();
        }
    }
}

elseif (isset($_GET['approvalTasksModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (
        ($topicTypeId = $_COMPANY->decodeId($_GET['approvalTopicId']))<1 ||
        ($approvalid = $_COMPANY->decodeId($_GET['approvalid']))<1 ||
        !isset($_GET['approvalTasksModal']) ||
        empty($topicType = $_GET['topicType'])
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

     //   Verify topicTypes and then fetch object. If used alot then we can move it to a method
     if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
        $topicTypeObj = Event::GetEvent($topicTypeId);
        $globalConfigurationForAllStages = Event::GetAllApprovalConfigurationRows(0,0,0);
        $topicTypeLabel = $topicTypeObj ?-> isSeriesEventHead() ? gettext('Event Series') : gettext('Event');
        $appCustomizationTitle = 'event';
    }elseif($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']){
        $topicTypeObj = Newsletter::GetNewsletter($topicTypeId);
        $globalConfigurationForAllStages = Newsletter::GetAllApprovalConfigurationRows(0,0,0);
        $topicTypeLabel = gettext('Newsletter');
        $appCustomizationTitle = 'newsletters';
    }elseif($topicType == Teleskope::TOPIC_TYPES['POST']){
        $topicTypeObj = Post::GetPost($topicTypeId);
        $globalConfigurationForAllStages = Post::GetAllApprovalConfigurationRows(0,0,0);
        $topicTypeLabel = Post::GetCustomName(false);
        $appCustomizationTitle = 'post';
    }elseif($topicType == Teleskope::TOPIC_TYPES['SURVEY']){
        $topicTypeObj = Survey2::GetSurvey($topicTypeId);
        $globalConfigurationForAllStages = Survey2::GetAllApprovalConfigurationRows(0,0,0);
        $topicTypeLabel = Survey2::GetCustomName(false);
        $appCustomizationTitle = 'surveys';
    }
    // Check if the object exists
    if($topicTypeObj === NULL || !$topicTypeObj->val('isactive')){
        AjaxResponse::SuccessAndExit_STRING(-1, '', gettext($topicTypeLabel." not found ... it might have been deleted!"), gettext('Error'));
        exit();
    }

    $approval = $topicTypeObj->getApprovalObject();
    $approvalStage = (int)$approval->val('approval_stage');
    
    $approval_config_ids = array_column($globalConfigurationForAllStages, 'approval_config_id', 'approval_stage');
    $approval_config_id = $approval_config_ids[$approvalStage] ?? NULL; 
    if($approval_config_id === NULL){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $allTasks = TopicApprovalTask::GetTasksWithStatusForApproval($approval->id(), $approval_config_id);
    if($_COMPANY->getAppCustomization()[$appCustomizationTitle]['approvals']['tasks']){
        // only showing additional details when tasks are present 
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
    }

       $availableSatuses = TopicApprovalTask::TOPIC_APPROVAL_STATUS;

        // Get the approvers by stage so that tasks can be assigned to them if needed.
        // Get the approvar data according to the stage
        $stageTitle = "Stage {$approvalStage}";

         // Set approvers for current stage
        $approvers = array_column(array_filter($globalConfigurationForAllStages, function ($stage) use ($approvalStage){
            return $stage['approval_stage'] == $approvalStage;
        }), 'approvers');
        $approvers = !empty($approvers) ? $approvers[0] : []; 
        // Set approvers for next stage if it is not the last stage
        $nextStageApprovers = [];
        $nextApprovalStage = Approval::APPROVAL_STAGE_MAX;
        if ($approvalStage < Approval::APPROVAL_STAGE_MAX){
            $nextApprovalStage = $approvalStage + 1;

            $nextStageApprovers = array_column(array_filter($globalConfigurationForAllStages, function ($stage) use ($nextApprovalStage){
                return $stage['approval_stage'] == $nextApprovalStage;
            }), 'approvers');
        }
        // Flattening this
        $nextStageApprovers = !empty($nextStageApprovers) ? $nextStageApprovers[0] : [];
    
        $allAssignees = array_column($approvers, 'approver_userid');
        $showApproveButton = true;
        if(($approval->val('approval_status') == Approval::TOPIC_APPROVAL_STATUS['APPROVED']) && ($approvalStage == Approval::APPROVAL_STAGE_MAX)){
            $showApproveButton = false;
        }

        $TopicClass = Teleskope::TOPIC_TYPE_CLASS_MAP[$topicType];

        $nextApprovalStageConfigurationId = intval(TopicApprovalConfiguration::GetApprovalConfigurationByTopicAndStage($topicType, $approvalStage+ 1) ?-> val('approval_config_id'));
        [$approver_cc_emails, $approver_min_approvers_limit, $approver_max_approvers_limit, $stage_approval_email_subject, $stage_approval_email_body, $stage_denial_email_subject, $stage_denial_email_body, $disallow_submitter_approval] = $TopicClass::GetApprovalConfigurationEmailSettings($nextApprovalStageConfigurationId);

        // Disallow approval request submitter approval
        if($nextStageApprovers && $disallow_submitter_approval){
            // filter out the the approval request submitter
            $nextStageApprovers = array_values(array_filter(
                $nextStageApprovers, fn($approver) => $approver['approver_userid'] != (int)$approval->val('createdby')
            ));
        }

        $ajaxReqEndpoint = 'ajax_approvals.php';
        include(__DIR__ . '../../common/topic_approval_modals.html');
}
elseif (isset($_GET['changeApprovalTasksStatus']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (
        ($approvalid = $_COMPANY->decodeId($_POST['approvalid']))<1 ||
        ($status = $_POST['newStatus'])<1
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    // Taskids
    $taskIdsArr = $_COMPANY->decodeIdsInArray($_POST['taskIds']);
    if( empty($taskIdsArr) || (count($taskIdsArr) < 1) 
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // For ephemeral topic ids
    $ephemeralTopicIds = isset($_POST['ephemeral_topic_id']) ? json_decode($_POST['ephemeral_topic_id']) : [];
    foreach($taskIdsArr as $index => $taskId){
         // Set ephemeral topicid if exist
         $currentEphemeralTopicId =  $ephemeralTopicId ?? ($ephemeralTopicIds[$index] ?? NULL);
         // have to set manually the $_POST for ephemeral;
         $_POST['ephemeral_topic_id'] = $currentEphemeralTopicId ?? NULL;
        $updateStatus = TopicApprovalTask::ChangeApprovalTasksStatus($taskId, $status, $approvalid);
    }
    
    if($updateStatus){
        AjaxResponse::SuccessAndExit_STRING(1, '', "Task status updated successfully. ", 'Status updated!');
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', "An error occurred. Please try again later ", 'Error');

}
elseif (isset($_GET['changeAssignee']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (
        ($taskId = $_COMPANY->decodeId($_POST['taskId']))<1 ||
        ($approvalid = $_COMPANY->decodeId($_POST['approvalId']))<1 ||
        ($assigneeId = $_POST['assigneeId'])<1
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    // Change status of task. 
    $updateStatus = TopicApprovalTask::ChangeAssignee($taskId, $assigneeId, $approvalid);
    if($updateStatus){
        AjaxResponse::SuccessAndExit_STRING(1, '', "Assignee updated successfully. ", 'Assignee updated!');
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', "An error occurred. Please try again later ", 'Error');

}
elseif (isset($_GET['deleteApproval']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($approvalid = $_COMPANY->decodeId($_POST['approvalid']))<1 ||
        (empty($topicType = $_POST['topicType']))
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
        $stageRowsData = Event::GetAllApproversByStage(0,0,0,0);
    }elseif($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']){
        $stageRowsData = Newsletter::GetAllApproversByStage(0,0,0,0);
    }elseif($topicType == Teleskope::TOPIC_TYPES['POST']){
        $stageRowsData = Post::GetAllApproversByStage(0,0,0,0);
    }elseif($topicType == Teleskope::TOPIC_TYPES['SURVEY']){
        $stageRowsData = Survey2::GetAllApproversByStage(0,0,0,0);
    }
    $stageApproverUserIds = $stageRowsData['approver_userids'] ?? array();
    if (!in_array( $_USER->id() , $stageApproverUserIds)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (Approval::DeleteApproval($approvalid)){
        echo 1;
    } else {
        echo 0;
    }
}elseif (isset($_GET['cancelApprovalRequest']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($topicTypeId = $_COMPANY->decodeId($_POST['topicTypeId']))<1 ||
        (empty($topicType = $_POST['topicType']))
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    //   Verify topicTypes and then fetch object. If used alot then we can move it to a method
    if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
        $topicTypeObj = Event::GetEvent($topicTypeId);
        $topicTypeLabel = 'Event';
    }elseif($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']){
        $topicTypeObj = Newsletter::GetNewsletter($topicTypeId);
        $topicTypeLabel = 'Newsletter';
    }elseif($topicType == Teleskope::TOPIC_TYPES['POST']){
        $topicTypeObj = Post::GetPost($topicTypeId);
        $topicTypeLabel = Post::GetCustomName(false);
    }elseif($topicType == Teleskope::TOPIC_TYPES['SURVEY']){
        $topicTypeObj = Survey2::GetSurvey($topicTypeId);
        $topicTypeLabel = Survey2::GetCustomName(false);
    }
    // Check if the object exists
    if($topicTypeObj === NULL){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Processing the note
    $cancellation_note = isset($_POST['note']) ? trim(htmlspecialchars($_POST['note'], ENT_QUOTES, 'UTF-8')) : '';
    $approval = $topicTypeObj->getApprovalObject();
    if($approval === NULL){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $approval->cancel($cancellation_note);
    print(1);
    exit();
}
else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}