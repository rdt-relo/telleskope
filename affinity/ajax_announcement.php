<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__.'/head.php';

/* @var Company $_COMPANY */
/* @var User $_USER */

###### All Ajax Calls Regarding Announcements ##########

## OK
## Submit New Announcement
if (isset($_GET['submitNewAnnoucement']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupid = 0;
    $chapterids = '0';
    $error = false;
    $insert = 0;
    $channelid = '0';
    $postid = (int) $_COMPANY->decodeId($_POST['edit']);
	$type = (int)@$_POST['type']; // 0 for save draft, 1 for publish on web, 2 for publish and email
    $title = @$_POST['title'];
    $post = ViewHelper::RedactorContentValidateAndCleanup($_POST['post']);
    $add_post_disclaimer = 0;
    $edit_post = null;
    if ($postid){
        $edit_post = Post::GetPost($postid);
    }
    $listids = '0';
    if ($edit_post && $edit_post->val('isactive') == 1){
        $listids = $edit_post->val('listids');
    } elseif ((isset($_POST['post_scope']) && $_POST['post_scope'] == 'dynamic_list') && empty($_POST['list_scope'])) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please select dynamic list scope!"), gettext('Error'));
    } else if(!empty($_POST['list_scope'])){
        $listids = implode(',',$_COMPANY->decodeIdsInArray($_POST['list_scope']));
    }

    $use_and_chapter_connector = (bool)($_POST['use_and_chapter_connector'] ?? '0');

    $content_replyto_email = ViewHelper::ValidateAndExtractReplytoEmailFromPostAttribute();

    if(!empty($_POST['add_post_disclaimer'])){
        $add_post_disclaimer = 1;
    }


    //Data Validation
	$check = $db->checkRequired(array('Title'=>$title,'Description'=>$post));
	if($check){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$check), gettext('Error'));
	}

    if (isset($_POST['groupid'])){
        $groupid = $_COMPANY->decodeId($_POST['groupid']);
    }

    if (isset($_POST['chapters'])){
        # Start - Get Chapterids & Validate the user can perform action in the given chapters and channels
        $encChapterids = isset($_POST['chapters']) ? $_POST['chapters'] : array($_COMPANY->encodeId(0));
        $chapterids = implode(',', $_COMPANY->decodeIdsInArray($encChapterids) ?: '0');
    }

    if (isset($_POST['channelid'])){
        $channelid = $_COMPANY->decodeId($_POST['channelid']);
    }

    if ($postid) {
        if ($edit_post->isActive()) { // Once published chapter, channels cannot be changed.
            $groupid = $edit_post->val('groupid');
            $chapterids = $edit_post->val('chapterid');
            $channelid = $edit_post->val('channelid');
        }

        if (!$_USER->canUpdateContentInScopeCSV($groupid,$chapterids, $channelid,$edit_post->val('isactive'))){
            if (($_COMPANY->getAppCustomization()['chapter']['enabled'] && empty($chapterids)) || ($_COMPANY->getAppCustomization()['channel']['enabled']) && empty($channelid)) {
                $contextScope = $_COMPANY->getAppCustomization()['channel']['name-short'];
                if (empty($chapterids)){
                    $contextScope = $_COMPANY->getAppCustomization()['chapter']['name-short'];
                }
                AjaxResponse::SuccessAndExit_STRING(-3, '', sprintf(gettext("Please select a %s scope"),$contextScope), gettext('Error'));
            } else {
                header(HTTP_FORBIDDEN);
                exit();
            }
        }

        if ($type) { // For publish
            $publish_where_integration = array();
            if (!empty($_POST['publish_where_integration'])){
                $publish_where_integration_input = $_POST['publish_where_integration'];
                
                foreach($publish_where_integration_input as $encExternalId){
                    $externalId = $_COMPANY->decodeId($encExternalId);
                    $externalIntegraions = GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($edit_post->val('groupid'),$edit_post->val('chapterid'),$edit_post->val('channelid'),$externalId,true);
                    foreach($externalIntegraions as $externalIntegraion){
                        array_push($publish_where_integration,$externalIntegraion->id());
                    }
                }
            }
            $sendEmails = 0;
            if (isset($_POST['sendEmails'])){
                $sendEmails = (int) $_POST['sendEmails'];
            }

            Post::UpdatePublishedPost($postid, $title, $post, $content_replyto_email, $add_post_disclaimer);
            $job = new PostJob($groupid, $postid);
            $job->saveAsBatchUpdateType($sendEmails,$publish_where_integration);
            $showmessage = "id=" . $_COMPANY->encodeId($postid);
        } else {  // For save draft
            ViewHelper::ValidateObjectVersionMatchesWithPostAttribute($edit_post);

            Post::UpdateSavedPost($postid, $title, $post, $chapterids, $channelid, $listids, $content_replyto_email, $add_post_disclaimer, $use_and_chapter_connector);
            $showmessage = "id=" . $_COMPANY->encodeId($postid);
        }
    } else {
        if (!$_USER->canCreateContentInScopeCSV($groupid,$chapterids, $channelid)){
            if (($_COMPANY->getAppCustomization()['chapter']['enabled'] && empty($chapterids)) || ($_COMPANY->getAppCustomization()['channel']['enabled']) && empty($channelid)) {
                $contextScope = $_COMPANY->getAppCustomization()['channel']['name-short'];
                if (empty($chapterids)){
                    $contextScope = $_COMPANY->getAppCustomization()['chapter']['name-short'];
                }
                AjaxResponse::SuccessAndExit_STRING(-3, '', sprintf(gettext("Please select a %s scope"),$contextScope), gettext('Error'));
            } else {
                header(HTTP_FORBIDDEN);
                exit();
            }
        }
        //save draft
        $postid = Post::CreateNewPost($groupid, $title, $post, $chapterids, $channelid, $listids, $content_replyto_email, $add_post_disclaimer, $use_and_chapter_connector);

        if (!empty($_POST['ephemeral_topic_id'])) {
            $ephemeral_topic_id = $_COMPANY->decodeId($_POST['ephemeral_topic_id']);
            $ephemeral_topic = EphemeralTopic::GetEphemeralTopic($ephemeral_topic_id);

            $post = Post::GetPost($postid);
            $post->moveAttachmentsFrom($ephemeral_topic);
        }

        $showmessage = "id=" . $_COMPANY->encodeId($postid);
        // We will send notifications only after the post is published
        AjaxResponse::SuccessAndExit_STRING(1, $showmessage, sprintf(gettext("%s created successfully"),Post::GetCustomName(false)), gettext('Success'));
        exit();
    }
    AjaxResponse::SuccessAndExit_STRING(1, $showmessage, sprintf(gettext("%s updated successfully"),Post::GetCustomName(false)), gettext('Success'));
   
}

## OK
## Update Announcement POPUP
elseif (isset($_GET['updateAnnouncement'])){

    //Data Validation
    if (($id = $_COMPANY->decodeId($_GET['updateAnnouncement']))<1 ||
        (NULL === ($edit = Post::GetPost($id)))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $groupid = $edit->val('groupid');
    # Start - Validate the user can perform action in the given chapters and channels
    $selectedChapterIds = explode(',',$edit->val('chapterid'));
    
    if (empty($selectedChapterIds)) {
        $selectedChapterIds = array(0);
    }
    $selectedChannelId = $edit->val('channelid');

    if (!$_USER->canUpdateContentInScopeCSV($groupid,$edit->val('chapterid'), $edit->val('channelid'),$edit->val('isactive'))){
        header(HTTP_FORBIDDEN);
        exit();
    }

	$files = array(); // not used, remove it.
    $chapters = Group::GetChapterListByRegionalHierachies($groupid);
    $channels= Group::GetChannelList($edit->val('groupid'));
    
	if ($edit) {
        $group = Group::GetGroup($edit->val('groupid'));
        $fontColors = $edit->val('groupid') > 0 ? json_encode(array($group->val('overlaycolor'),$group->val('overlaycolor2'))) : json_encode(array());

        $lists = array();
        if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {
            if ($edit->val('groupid')){
                $lists = DynamicList::GetAllLists('group',true);
            } else {
                $lists = DynamicList::GetAllLists('zone',true);
            }
        }
        // Integration
        $published_integrations = GroupIntegration::GetIntegrationidsByRecordKey("POS_{$id}")  ?? array();
        $integrations = GroupIntegration::GetUniqueGroupIntegrationsByExternalType($groupid,$edit->val('chapterid'), $edit->val('channelid'),$published_integrations,'post');
        $displayStyle = 'row';

        $approval = $edit->getApprovalObject();
        $allowTitleUpdate=true;
        $allowDescriptionUpdate = true;
        if ($approval) {
            [$allowTitleUpdate,$allowDescriptionUpdate] = $approval->canUpdateAfterApproval();
        }

		include(__DIR__ . "/views/templates/update_announcement.template.php");
        exit();
	}else{
		AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong, please refresh page and try again."), gettext('Error'));
	}

}
## OK
## Clone Announcement
elseif(isset($_GET['cloneAnnouncementForm'])){
    //Data Validation  
    if (($groupid = $_COMPANY->decodeId($_GET['cloneAnnouncementForm']))<0 ||
        ($id = $_COMPANY->decodeId($_POST['announcementid']))<1 ||
        ($announcement = Post::GetPost($id)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canCreateContentInGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
	}
    // cloning newsletter with name and template here for simplicity. This will enable us to use single file for edit and clone
    $title = "Clone of ". html_entity_decode($announcement->val('title'));
    $postid = Post::CreateNewPost($groupid, $title, $announcement->val('post'), $announcement->val('chapterid'), $announcement->val('channelid'), $announcement->val('listids'), $announcement->val('content_replyto_email'),  $announcement->val('add_post_disclaimer'), $announcement->val('use_and_chapter_connector'));
    if($postid){
        $AnnouncementData = array('groupid'=>$_COMPANY->encodeId($groupid), 'clonedAnnouncementid'=>$_COMPANY->encodeId($postid));
    }
    AjaxResponse::SuccessAndExit_STRING(1, $AnnouncementData, sprintf(gettext("%s cloned successfully."),Post::GetCustomName(false)), gettext('Success'));
}
## OK
## Delete Announcement
elseif (isset($_GET['deleteAnnouncement']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (!isset($_POST['postid']) ||
        ($id = $_COMPANY->decodeId($_POST['postid']))<1 ||
        (NULL === ($post = Post::GetPost($id)))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $groupid = $_COMPANY->decodeId($_GET['deleteAnnouncement']);

    // Authorization Check
    if (!$_USER->canCreateOrPublishOrManageContentInScopeCSV($post->val('groupid'), $post->val('chapterid'),$post->val('channelid'))  && ($post->isDraft() || $post->isUnderReview())
    ) { //Allow creators to edit unpublished content
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($post->isDraft() || $post->isUnderReview()) {
        $post->deleteIt();
    }else{
        $post->inactivateIt();
        $job = new PostJob($groupid, $post->id());
        $job->saveAsBatchDeleteType(0);
	}
    AjaxResponse::SuccessAndExit_STRING(1, $groupid, gettext("Deleted Successfully."), gettext('Success'));
}

## OK
##
elseif (isset($_GET['newAnnouncement'])){

    $chapters = array();
    $channels = array();
    $selectedChapterIds = array();
    $selectedChannelId = 0;
    $lists = array();
    $use_and_chapter_connector = false;

    if(isset($_GET['global']) && $_GET['global'] ==1){
        $global = $_GET['global'];
        $groupid = 0;
        if (!$_USER->isAdmin()) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {
            $lists = DynamicList::GetAllLists('zone',true);
        }
    } else {
        //Data Validation
        if (($groupid = $_COMPANY->decodeId($_GET['newAnnouncement']))<1
        ) {
            header(HTTP_BAD_REQUEST);
            exit();
        }

        // Authorization Check
        // Allow anyone with create permissions to see this form.
        if (!$_USER->canCreateContentInGroupSomething($groupid)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $global = 0;
        if($_COMPANY->getAppCustomization()['chapter']['enabled']){
            $chapters = Group::GetChapterListByRegionalHierachies($groupid);
        }
        if($_COMPANY->getAppCustomization()['channel']['enabled']){
            $channels= Group::GetChannelList($groupid);
        }
        if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {
            $lists = DynamicList::GetAllLists('group',true);
        }
    }
    $group = Group::GetGroup($groupid);
    $fontColors = $groupid > 0 ? json_encode(array($group->val('overlaycolor'),$group->val('overlaycolor2'))) : json_encode(array());
	$displayStyle = 'row';
    include(__DIR__ . "/views/templates/new_announcement.template.php");
}

elseif (isset($_GET['updateComment']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	$post = NULL;

    //Data Validation
    if (!isset($_POST['comment']) ||  ($commentid = $_COMPANY->decodeId($_GET['updateComment']))<0 ||
        ($id = $_COMPANY->decodeId($_POST['postid']))<0 ||
        (NULL === ($post = Post::GetPost($id)))
    ) {
        header(HTTP_BAD_REQUEST);
        print('');
        exit();
    }

    // Authorization Check
    if (!$_USER->canViewContent($post->val('groupid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	if($post->updateComment((int) $commentid, $_POST['comment'])){ //updateComment method is safe to handle raw string
        echo htmlspecialchars($_POST['comment']);
    } else {
        echo 0;
    }
}

elseif (isset($_GET['deleteComment']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	$post = NULL;

    //Data Validation
    if (!isset($_GET['deleteComment']) || ($commentid = $_COMPANY->decodeId($_GET['deleteComment']))<0 ||
        ($id = $_COMPANY->decodeId($_POST['postid']))<0 ||
        (NULL === ($post = Post::GetPost($id)))
    ) {
        header(HTTP_BAD_REQUEST);
        print('');
        exit();
    }


    // Authorization Check
    if (!$_USER->canViewContent($post->val('groupid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	echo $post->deleteComment((int) $commentid);
	
}

elseif (isset($_GET['openAnnouncementReviewModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $enc_groupid = $_GET['groupid'];
    $enc_postid = $_GET['postid'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($enc_groupid))<0 ||
        ($postid = $_COMPANY->decodeId($enc_postid)) < 1 ||
        ($post = Post::GetPost($postid))  === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $chapterids = empty($post->val('chapterid')) ? array(0) : explode(',', $post->val('chapterid'));
    $chapterids = implode(',',$chapterids);
    if (!$_USER->canCreateOrPublishOrManageContentInScopeCSV($groupid,$chapterids, $post->val('channelid'))){
        header(HTTP_FORBIDDEN);
        exit();
    }
    $reviewers = User::GetReviewersByScope($post->val('groupid'), $post->val('chapterid'), $post->val('channelid'));
    // The template needs following inputs in addition to $reviewers set above
    $template_review_what = Post::GetCustomName(false);
    $enc_groupid = $_COMPANY->encodeId($groupid);
    $enc_objectid = $_COMPANY->encodeId($postid);
    $template_email_review_function = 'sendAnnouncementForReview';

    include(__DIR__ . "/views/templates/general_email_review_modal.template.php");

}

elseif (isset($_GET['sendAnnouncementForReview']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
   
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0    ||
        !isset($_POST['objectid']) ||
        ($postid = $_COMPANY->decodeId($_POST['objectid']))<1 || ($post = Post::GetPost($postid)) === NULL) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $validEmails = [];
    $invalidEmails = [];

    $e_arr = explode(',',str_replace(';',',',$_POST['emails']));
    foreach ($e_arr as $e) {
        $e = trim($e);
        if (empty($e)){
            continue;
        }
        if (!filter_var($e, FILTER_VALIDATE_EMAIL)){
            array_push($invalidEmails,$e);
        } elseif (!$_COMPANY->isValidEmail($e)) {
            array_push($invalidEmails,$e);
        } else {
            array_push($validEmails,$e);
        }
    }

    if (count($invalidEmails)){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Invalid emails: %s'),implode(', ',$invalidEmails)), gettext('Error'));
    }

    if (count($validEmails) > 10){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Too many emails... %s emails entered (maximum allowed is 10)'),count($validEmails)), gettext('Error'));
    }

    $reviewers = [];
    if (isset($_POST['reviewers']) && !empty($_POST['reviewers'][0])){
        $reviewers = $_POST['reviewers'];
    }
    $reviewers[] = $_USER->val('email'); // Add the current user to review list as well.

    $allreviewers = implode(',', array_unique (array_merge ($validEmails , $reviewers)));

    if (!empty($allreviewers)){
        $review_note = raw2clean($_POST['review_note']);

        $job = new PostJob($groupid, $postid);
        $job->sendForReview($allreviewers,$review_note);


        // Update announcement under review status
        $post->updatePostUnderReview();
        AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext("Your %s has been emailed for review."),Post::GetCustomName(false)), gettext('Success'));

        
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please add some reviewers'), gettext('Error'));
    }
}

elseif (isset($_GET['getAnnouncementScheduleModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $enc_groupid = $_GET['groupid'];
    $enc_postid = $_GET['postid'];

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($enc_groupid))<0 ||
        ($postid = $_COMPANY->decodeId($enc_postid)) < 1 ||
        ($post = Post::GetPost($postid)) === NULL) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    // Do not allow lists to be published on the platform or on integrations.
    if (empty($post->val('listids'))){
        $hidePlatformPublish = false;
        $integrations = GroupIntegration::GetUniqueGroupIntegrationsByExternalType($groupid,$post->val('chapterid'), $post->val('channelid'),array(),'post');
    } else {
        $hidePlatformPublish = true;
        $integrations = array();
    }
    // The following three parameters are needed by the publishing template.
    $enc_groupid = $_COMPANY->encodeId($groupid);
    $enc_objectid = $_COMPANY->encodeId($postid);
    $template_publish_what = Post::GetCustomName(false);
    $template_publish_js_method = 'saveScheduleAnnouncementPublishing';

    $email_subtext = sprintf(
        gettext('Deselecting "Email" means %1$s won\'t be shared automatically via email thus reducing its reach and engagement. ')
        . gettext('To send emails later, Edit the %1$s and choose the "Email" option on "Publish Update" screen. ')
        . gettext('Alternatively, use the shareable link to share %1$s directly.')
        ,
        Post::GetCustomName()
    );
    $pre_select_publish_to_email = $_COMPANY->getAppCustomization()['post']['publish']['preselect_email_publish'] ?? true;
    $hideEmailPublish = $_COMPANY->getAppCustomization()['post']['disable_email_publish']?? false;
    include(__DIR__ . "/views/templates/general_schedule_publish.template.php");
}

elseif (isset($_GET['saveScheduleAnnouncementPublishing']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        !isset($_POST['objectid']) ||
        ($postid = $_COMPANY->decodeId($_POST['objectid']))<1 ||
        (null === ($post = Post::GetPost($postid))) ||
        ($groupid != $post->val('groupid'))) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $publish_where_integration = array();
    if (!empty($_POST['publish_where_integration'])){
        $publish_where_integration_input = $_POST['publish_where_integration'];
        foreach($publish_where_integration_input as $encExternalId){
            $externalId = $_COMPANY->decodeId($encExternalId);
            $externalIntegraions = GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($post->val('groupid'),$post->val('chapterid'),$post->val('channelid'),$externalId,true);
            foreach($externalIntegraions as $externalIntegraion){
                array_push($publish_where_integration,$externalIntegraion->id());
            }
        }
    }
    // Authorization Check Chapter
    $chapterids = empty($post->val('chapterid')) ? array(0) : explode(',', $post->val('chapterid'));
    $chapterids = implode(',',$chapterids);
    if (!$_USER->canCreateOrPublishOrManageContentInScopeCSV($groupid,$chapterids, $post->val('channelid'))){
        header(HTTP_FORBIDDEN);
        exit();
    }

    // If 'publish_where' === 'online', then change isactive=1, publishdate=now(), do not process other fields
    // If 'publish_where' === 'online & email' then change isactive=3, .... find out the value for publish date
    //      If 'publish_when' === 'now', then  publishdate=now()
    //      Else publishdate == calculated value

    $isactive = Post::STATUS_AWAITING;
    $delay = 0;
    $sendEmails = 0;
    $publish_date = '';
    if (!empty($_POST['publish_where_email'])){
        $sendEmails = 1;
    }

    if (!empty($_POST['publish_when']) && $_POST['publish_when'] === 'scheduled') {
        $publish_date_format = $_POST['publish_Ymd']." ".$_POST['publish_h'].":".$_POST['publish_i']." ".$_POST['publish_A'];
        $timezone = isset($_POST['timezone']) ? $_POST['timezone'] : (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
        $publish_date = $db->covertLocaltoUTC("Y-m-d H:i:s", $publish_date_format, $timezone);
        $delay = strtotime($publish_date . ' UTC') - time();
        if ($delay > 2592000 || $delay < -300) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Note: publishing an %s can only be scheduled up to thirty days in the future.'),Post::GetCustomName(false)), gettext('Error'));
        }
        if ($delay < 0){
            $delay = 15;
        }
    }

    $scheduled = $post->updatePostForSchedulePublishing($delay);
   
    if ($scheduled) {
        $job = new PostJob($groupid, $postid);
        if (!empty($publish_date)) {
            $job->delay = $delay;
        }
        $job->saveAsBatchCreateType($sendEmails,$publish_where_integration);

        AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext("%s scheduled for publishing"),Post::GetCustomName(false)), gettext('Success'));        
    }
    AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext("%s published successfully"),Post::GetCustomName(false)), gettext('Success'));
    
}
elseif (isset($_GET['cancelAnnouncementPublishing']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0||
        !isset($_POST['postid']) ||
        ($postid = $_COMPANY->decodeId($_POST['postid']))<1 ||
        ($post = Post::GetPost($postid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if ($groupid != $post->val('groupid') ||
        !($_USER->canPublishContentInScopeCSV($post->val('groupid'),$post->val('chapterid'),$post->val('channelid')))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $cancelPublishing = $post->cancelPostPublishing();
    if ($cancelPublishing) {
        $job = new PostJob($groupid, $postid);
        $job->cancelAllPendingJobs();
        AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext('%s publishing canceled successfully.'),Post::GetCustomName(false)), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Unable to cancel publishing the %s.'),Post::GetCustomName(false)), gettext('Error'));
    
}

elseif(isset($_GET['sharePost']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($postid = $_COMPANY->decodeId($_POST['postid']))<1 ||
        ($post = Post::GetPost($postid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check - anyone with Publish function can invite by email
    if (!$_USER->canPublishContentInCompanySomething()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $email_in       = $_POST['email_in'];
    // if invitation to email
    $inviteEmails = array();
    $validEmails = array();
    $invalidEmails = array();

    if (empty(trim($email_in))) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter valid emails with whom you would like to share.'), gettext('Error'));
    }
    $e_arr = extractEmailsFrom ($email_in); // Use extractEmailsFrom function to extract email addresses

    if(count($e_arr) == 0){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter valid emails with whom you would like to share.'), gettext('Error'));
    }

    foreach ($e_arr as $e) {
        $e = trim($e);
        if (empty($e)){
            continue;
        }
        if (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
            array_push($invalidEmails, $e);
        } elseif (!$_COMPANY->isValidEmail($e)) {
            array_push($invalidEmails, $e);
        } else {
            array_push($validEmails, $e);
        }
    }

    if (count($invalidEmails)) {
        echo 0; // 0 means email was not sent.
        exit();
    }

    // We have check against 10 for legacy reasons. As per the changes made on Dec 6, 2024 only one email will be sent for each call.
    if (count($validEmails) > 10) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Too many emails... %s emails entered (maximum allowed is 10)'),count($validEmails) ), gettext('Error'));
    }

    // if invitation to email
    foreach ($validEmails as $email) {
        $job = new PostJob($post->val('groupid'), $postid);
        if ($job->sharePostByEmail($email)) {
            $inviteEmails[] = $email;
        }
    }

    $success_message = '';
    if (count($inviteEmails)){        
        $success_message .= sprintf(gettext("%s shared with:"),Post::GetCustomName(true));
    }
    foreach ($inviteEmails as $inviteEmail){
        $success_message .= ' ' . $inviteEmail . ',';
    }
    $success_message = trim($success_message, ',');
    $success_message = strlen($success_message) ? $success_message . '. ' : '';
    $success_message = trim($success_message, ',');
   echo 1; // 1 means email was sent.
   exit();
}
elseif (isset($_GET['getAnnouncementActionButton']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($postid = $_COMPANY->decodeId($_GET['getAnnouncementActionButton']))<0 ||
        ($post = Post::GetPost($postid)) === NULL  ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
     
    // Check for Request Approval if active
    if($_COMPANY->getAppCustomization()['post']['approvals']['enabled']){
        $approval = $post->getApprovalObject() ? $post->getApprovalObject(): '';
        $topicType = Teleskope::TOPIC_TYPES['POST'];
    }
    
    $chapterids = empty($post->val('chapterid')) ? array(0) : explode(',', $post->val('chapterid'));
    $isAllowedToUpdateContent   = $_USER->canUpdateContentInScopeCSV($post->val('groupid'),$post->val('chapterid'),$post->val('channelid'),$post->val('isactive'));
    $isAllowedToPublishContent  = $_USER->canPublishContentInScopeCSV($post->val('groupid'),$post->val('chapterid'),$post->val('channelid'));
    $isAllowedToManageContent   = $_USER->canManageContentInScopeCSV($post->val('groupid'),$post->val('chapterid'),$post->val('channelid'));
    include(__DIR__ . "/views/templates/announcement_action_button.template.php");
}

elseif (isset($_GET['pinUnpinAnnouncement']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($postid = $_COMPANY->decodeId($_POST['postid'])) < 1 ||
        (($post = Post::GetPost($postid))  === NULL) ||
        !$_COMPANY->getAppCustomization()['post']['pinning']['enabled']
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $enc_groupid = $_COMPANY->encodeId($groupid);
    $enc_postid = $_COMPANY->encodeId($postid);
    $type = $_POST['type'] == 2 ? $_POST['type'] : 1; 
  
    // Authorization Check
    if (!$_USER->canPublishContentInScopeCSV($post->val('groupid'),$post->val('chapterid'), $post->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    
    if ($post->pinUnpinAnnouncement($type)){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Updated successfully."), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Error'));
    }
}
elseif (isset($_GET['getAnnouncementDetailOnModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($postid = $_COMPANY->decodeId($_GET['postid']))<0 ||
        ($post = Post::GetPost($postid)) === NULL ||
        ($filterchapterid = $_COMPANY->decodeId($_GET['chapterid']))<0 ||
        ($filterchannelid = $_COMPANY->decodeId($_GET['channelid']))<0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->canViewContent($post->val('groupid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $group = NULL;
    $chapters = NULL;
    $channels = NULL;
    $groupid = 0;
    $chapterid = 0;
    $channelid = 0;

    $topicid = $postid;    
    $groupid = $post->val('groupid');
    $enc_groupid = $_COMPANY->encodeId($groupid);
    $enc_postid = $_COMPANY->encodeId($postid);
    $enc_chapterid   = $_COMPANY->encodeId($filterchapterid);
    $enc_channelid    = $_COMPANY->encodeId($filterchapterid);
    $selected_chapter = null;
    $selected_channel = null;
    $group = Group::GetGroup($groupid);
    if ($groupid) {
        // Set selected_chapter, selected_channel for the dropdown
        if ($filterchapterid) {
            $selected_chapter = $group->getChapter($filterchapterid);
        }
        if ($filterchannelid) {
            $selected_channel = $group->getChannel($filterchannelid);
        }

        if (!empty($_GET['showGlobalChapterOnly'])) {
            $_SESSION['showGlobalChapterOnly'] = 1;
        } elseif ($filterchapterid || !empty($_GET['showAllChapters'])) {
            unset($_SESSION['showGlobalChapterOnly']);
        }

        if (!empty($_GET['showGlobalChannelOnly'])) {
            $_SESSION['showGlobalChannelOnly'] = 1;
        } elseif ($filterchapterid || !empty($_GET['showAllChannels'])) {
            unset($_SESSION['showGlobalChannelOnly']);
        }

        $chapterid = $post->val('chapterid'); //Use the chapter set in the post
        $channelid = $post->val('channelid');

        $chapters = Group::GetChapterList($groupid);
        $channels= Group::GetChannelList($groupid);
    }
    $form_title = gettext("Announcement Link");
    $link = $_COMPANY->getAppURL($_ZONE->val('app_type')).'viewpost?id='.$_COMPANY->encodeId($postid);

    // Comment section
    /**
     * Dependencies for Comment Widget
     * $comments
     * $commentid (default 0)
     * $groupid
     * $topicid
     * $disableAddEditComment
     * $submitCommentMethod
     * $mediaUploadAllowed
    */
    if ($_COMPANY->getAppCustomization()['post']['comments']) { 
        $comments = Post::GetComments_2($postid);        
        $commentid = 0;
        $disableAddEditComment = false;
        $submitCommentMethod = "AnnouncementComment";
        $mediaUploadAllowed = $_COMPANY->getAppCustomization()['post']['enable_media_upload_on_comment'];
    }

    /**
     * TOPIC like/unline and show latest 10 likers
     * Dependencies for topic like
     * $topicid (i.e. postid, eventid, newsletterid etc)
     * $myLikeStatus
     * $latestLikers
     * $totalLikers
     * $likeUnlikeMethod
     * $showAllLikers
     * 
     */
    if ($_COMPANY->getAppCustomization()['post']['likes']) { 
        $myLikeType = Post::GetUserReactionType($topicid);
        $myLikeStatus = (int) !empty($myLikeType);
        $latestLikers = Post::GetLatestLikers($topicid);
        $totalLikers = Post::GetLikeTotals($topicid);
        $likeTotalsByType = Post::GetLikeTotalsByType($topicid);
        $showAllLikers = true;
        $likeUnlikeMethod = 'AnnouncementTopic';
    }
    
    include(__DIR__ . "/views/templates/post_detail_modal.template.php");
}

else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}

