<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__ .'/head.php'; //Common head.php includes destroying Session for timezone so defining INDEX_PAGE. Need head for logging and protecting

global $db;
global $_ZONE;
/* @var Company $_COMPANY */
/* @var User $_USER */

//ajax_newsletters.php

## OK
if (isset($_GET['getGroupNewsletters'])){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getGroupNewsletters']))<0 || ($group = Group::GetGroup($groupid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
	
    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
	}
	
	$headtitle	= gettext("Newsletters");
    $encGroupId = $_COMPANY->encodeId($groupid);
    $state_filter = '';
    $erg_filter = '';
    $year_filter = '';
    $erg_filter_section = '';

    if (!empty($_GET['state_filter'])){
        $state_filter = $_COMPANY->decodeId($_GET['state_filter']);
    }
    if (!empty($_GET['erg_filter'])){
        $erg_filter = $_COMPANY->decodeId($_GET['erg_filter']);
    }
    if (!empty($_GET['year_filter'])){
        $year_filter = $_COMPANY->decodeId($_GET['year_filter']);
    }
    if (!empty($_GET['erg_filter_section'])){
        $erg_filter_section = $_COMPANY->decodeId($_GET['erg_filter_section']);
    }

	include(__DIR__ . "/views/templates/group_newsletters.template.php");
}

## OK
elseif (isset($_GET['uploadEmailTemplateMedia']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['uploadEmailTemplateMedia']))<0) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    $encGroupId = $_COMPANY->encodeId($groupid);

	$file = [];
    if (empty($_FILES['file']['name']) || ((int)$_FILES['file']['size'])>26843545600) {
        $err = ['error' => true, 'message' => gettext('Upload error, maximum allowed filesize is 25 MB')];
        echo stripslashes(json_encode($err));
        exit();
    }

    $tmp = $_FILES["file"]["tmp_name"];
    $mimetype   =   mime_content_type($tmp);
    $valid_mimes = array('image/jpeg' => 'jpg','image/png' => 'png','image/gif' => 'gif');

    if (in_array($mimetype,array_keys($valid_mimes))) {
        $ext = $valid_mimes[$mimetype];
    } else {
        $err = ['error' => true, 'message' => gettext('Unsupported file type. Only .png, .gif, .jpg or .jpeg files are allowed')];
        echo stripslashes(json_encode($err));
        exit();
    }
    if ($mimetype == 'image/gif' && getimagesize($tmp)[0]>600) {
        $err = ['error' => true, 'message' => sprintf(gettext('gif image width should be less than %s pixels'),600)];
        echo stripslashes(json_encode($err));
        exit();
    }
    $tmp = $_COMPANY->resizeImage($tmp, $ext, 600);

	if ( !empty($_FILES['file']['name'])){
		$file 	    =	basename($_FILES['file']['name']);
		$ext		=	$db->getExtension($file);
		$actual_name ="templatemedia_".teleskope_uuid().".".$ext;
		$resource = $_COMPANY->saveFile($tmp,$actual_name,'NEWSLETTER');
		if (!empty($resource)) {
			$file = [
				'url' => $resource
			];
		}
	}
	echo stripslashes(json_encode($file));
	exit();
}

## OK
elseif (isset($_GET['fetchTemplate'])) {
    $chapterid = 0;
    if (($groupid = $_COMPANY->decodeId($_GET['fetchTemplate']))<0 ||
        (($group = Group::GetGroup($groupid)) === NULL) ||
        ($templateid = $_COMPANY->decodeId($_GET['templateid']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);

    if (!$_USER->canCreateContentInGroupSomething($groupid) && !$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $cid = isset($_GET['chapterid']) ? $_COMPANY->decodeId($_GET['chapterid']) : 0;
    $section = isset($_GET['section']) ? $_COMPANY->decodeId($_GET['section']) : 0;
    $chapterid = ($section == 2) ? 0 : $cid; // If section was 2 then it is a channel id and not a chapterid.

    $chaptername = Group::GetChapterName($chapterid,$groupid)['chaptername'];
    $groupname = $group->val('groupname');
    $grouplogo = $group->val('groupicon');
    $groupcolor = rgb_to_hex($group->val('overlaycolor'));
    $groupcolor2 = rgb_to_hex($group->val('overlaycolor2'));
    $companyname = $_COMPANY->val('companyname');
    $companylogo = $_COMPANY->val('logo');
    $companyurl = $_COMPANY->getAppURL($_ZONE->val('app_type'));
    $groupurl = $group->getShareableUrl();
    $replace_vars = ['[%COMPANY_NAME%]','COMPANY_LOGO','[%GROUP_NAME%]', 'GROUP_LOGO', '[%CHAPTER_NAME%]', '[%COMPANY_URL%]', '[%GROUP_URL%]', '#000001','#000002'];
    $replacement_vars = [$companyname, $companylogo, $groupname, $grouplogo, $chaptername,$companyurl, $groupurl, $groupcolor, $groupcolor2];
    
    $template = Template::GetTemplate($templateid);

    if ($template) {
        // Replace variables only for newsletter kind of templates.
        if ($template->val('templatetype') === Template::TEMPLATE_TYPE_NEWSLETTER) {
            $template = str_replace($replace_vars,$replacement_vars,$template->val('template'));
        } else {
            $template = $template->val('template');
        }
        echo $template;
        exit();
    }
    echo 'Error';
    exit();
}
elseif (isset($_GET['createNewsletterModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['createNewsletterModal']))<0) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);
    $chapters = Group::GetChapterListByRegionalHierachies($groupid);
    $channels= Group::GetChannelList($groupid);
    $selectedChapterIds = array();
    $selectedChannelId = 0;
    $use_and_chapter_connector = false;

    // Authorization Check
    if (!$_USER->canCreateContentInGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $templates = Template::GetTemplatesByTemplateTypes(Template::TEMPLATE_TYPE_NEWSLETTER);

    $groupname = Group::GetGroupName($groupid);
    $form_title	= addslashes(gettext("Create Newsletter"));
    include(__DIR__ . "/views/templates/create_new_newsletter.php");

}
## OK
elseif (isset($_GET['createNewsletter']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
     //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['createNewsletter']))<0 ||
        !isset($_POST['templateid']) ||
        ($templateid = $_COMPANY->decodeId($_POST['templateid']))<0) { 
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $newsletter_name = $_POST['newslettername'];
    $use_and_chapter_connector = (bool)($_POST['use_and_chapter_connector'] ?? '0');

    //Data Validation
    $check = $db->checkRequired(array(gettext('Newsletter Name')=>@$newsletter_name));
    if($check){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$check), gettext('Error'));
    }
    $newsletter_content = $_POST['newsletter'];
    $newsletter_content = ViewHelper::FixRevolvAppContentForOutlook($newsletter_content);
    $template = "";
    $templateContent = "";
    // get template
    $template = Template::GetTemplate($templateid);
    if ($template) {
        $group = Group::GetGroup($groupid);
    $groupname = $group->val('groupname');
    $grouplogo = $group->val('groupicon');
    $groupcolor = rgb_to_hex($group->val('overlaycolor'));
    $groupcolor2 = rgb_to_hex($group->val('overlaycolor2'));
    $companyname = $_COMPANY->val('companyname');
    $companylogo = $_COMPANY->val('logo');
    $companyurl = $_COMPANY->getAppURL($_ZONE->val('app_type'));
    $groupurl = $group->getShareableUrl();
    $replace_vars = ['[%COMPANY_NAME%]','COMPANY_LOGO','[%GROUP_NAME%]', 'GROUP_LOGO', '[%COMPANY_URL%]', '[%GROUP_URL%]', '#000001', '#000002'];
    $replacement_vars = [$companyname, $companylogo, $groupname, $grouplogo, $companyurl, $groupurl, $groupcolor, $groupcolor2];
        $templateContent = str_replace($replace_vars,$replacement_vars,$template->val('template'));
    }
    $encChapterids = isset($_POST['chapters']) ? $_POST['chapters'] : array($_COMPANY->encodeId(0));
    $chapterids = implode(',', $_COMPANY->decodeIdsInArray($encChapterids) ?: '0');
    $channelid = isset($_POST['channelid']) ? $_COMPANY->decodeId($_POST['channelid']) : 0;

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

    $newsletterid = Newsletter::CreateNewsletter($groupid, $newsletter_name, $templateid, $newsletter_content, $templateContent, $chapterids, $channelid,'','0', $use_and_chapter_connector);
    $newsletterData=array();
    if($newsletterid){
        $newsletterData = array('groupid'=>$_COMPANY->encodeId($groupid), 'newsletterid'=>$_COMPANY->encodeId($newsletterid));
    }
    AjaxResponse::SuccessAndExit_STRING(1, $newsletterData,gettext('Newsletter created successfully.'), gettext('Success'));
}

## OK
elseif (isset($_GET['updateNewsletter']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['updateNewsletter']))<0 ||
        !isset($_POST['newsletterid']) ||
        ($newsletterid = $_COMPANY->decodeId($_POST['newsletterid'])) < 1 ||
        ($newsletter = Newsletter::GetNewsletter($newsletterid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $version = $_COMPANY->decodeId($_POST['version']);
    $use_and_chapter_connector = (bool)($_POST['use_and_chapter_connector'] ?? '0');

    $content_replyto_email = ViewHelper::ValidateAndExtractReplytoEmailFromPostAttribute();

    ViewHelper::ValidateObjectVersionMatchesWithPostAttribute($newsletter);

    $encGroupId = $_COMPANY->encodeId($newsletter->val('groupid'));

     # Start - Get Inputs
    $newsletter_name = $_POST['newslettername'];
    $newsletter_summary = $_POST['newsletter_summary']??'';

    //Data Validation
    $check = $db->checkRequired(array('Newsletter Name'=>@$newsletter_name));
    if($check){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$check), gettext('Error'));
    }

    $newsletter_content = $_POST['newsletter'];
    if(empty($newsletter_content)){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),'Newsletter'), gettext('Error'));
    }

    $template = $_POST['template'];
    if(empty($template)){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),'Newsletter template'), gettext('Error'));
    }
    // remove all new lines from $template
    $template = str_replace(["\n", "\r", "\r\n"], " ", $template);


    $encChapterids = isset($_POST['chapters']) ? $_POST['chapters'] : array($_COMPANY->encodeId(0));
    $chapterids = implode(',', $_COMPANY->decodeIdsInArray($encChapterids) ?: '0');
    $channelid = isset($_POST['channelid']) ? $_COMPANY->decodeId($_POST['channelid']) : 0;
    # End - Get Inputs

    // Authorization Check
    if (!$_USER->canUpdateContentInScopeCSV($groupid,$chapterids, $channelid, $newsletter->val('isactive'))){
        if ((empty($chapterids) && $_COMPANY->getAppCustomization()['chapter']['enabled']) || ($_COMPANY->getAppCustomization()['channel']['enabled'] &&  empty($channelid))) {
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

    $newsletter_content = ViewHelper::FixRevolvAppContentForOutlook($newsletter_content);
    $listids = 0;
    if ((isset($_POST['newsletter_scope'])  && $_POST['newsletter_scope'] == 'dynamic_list') && empty($_POST['list_scope'])) {
        AjaxResponse::SuccessAndExit_STRING(0, '',  gettext("Please select dynamic list scope!"), gettext('Error'));
    } else {
        if (!empty($_POST['list_scope'])) {
            $listids = implode(',', $_COMPANY->decodeIdsInArray($_POST['list_scope']));
        }
    }

    if ($newsletter->updateNewsletter($newsletter_name, $newsletter_content, $template, $chapterids, $channelid, $content_replyto_email,$listids,$use_and_chapter_connector,$newsletter_summary)) {
        AjaxResponse::SuccessAndExit_STRING(1, $_COMPANY->encodeId($version+1), gettext('Newsletter updated successfully.'), gettext('Success'));
    }
    exit();
}

## OK
elseif (isset($_GET['initUpdateNewsletter']) && isset($_GET['newsletterid'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['initUpdateNewsletter']))<0 ||
        ($newsletterid = $_COMPANY->decodeId($_GET['newsletterid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($newsletter = Newsletter::GetNewsletter($newsletterid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $encGroupId = $_COMPANY->encodeId($newsletter->val('groupid'));
    $encNewsletterId = $_COMPANY->encodeId($newsletter->id());

    # Start - Validate the user can perform action in the given chapters and channels
    $selectedChapterIds = explode(',',$newsletter->val('chapterid'));
    if (empty($selectedChapterIds)) {
        $selectedChapterIds = array(0);
    }
    $selectedChannelId = $newsletter->val('channelid');
    if (!$_USER->canUpdateContentInScopeCSV($newsletter->val('groupid'), $newsletter->val('chapterid'), $newsletter->val('channelid'), $newsletter->val('isactive'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    # End - Validate the user can perform action in the given chapters and channels

    if ($newsletter){
        $groupname = $group->val('groupname');
        $cssRule = 'li a { font-size: inherit!important; }';
        $template =  $newsletter->val('template');
        if (strpos($template, '<re-style>') !== false) {
            if (strpos($template, 'li a { font-size: inherit!important; }' , strpos($template,'<re-style>')) === false) {
                // Add CSS rule only if it was not previously added.
                $template = preg_replace('/(<re-style>)/', '$1' . PHP_EOL . $cssRule, $newsletter->val('template'));
            }
        } else {
            $template = preg_replace('/(<re-head>)/', '$1<re-style>' . PHP_EOL . $cssRule . '</re-style>', $template);
        }
        $template = str_replace(["'","\n", "\r", "\r\n"],["\'"," "," "," "],$template);
        $chapters = Group::GetChapterListByRegionalHierachies($groupid);
        $channels= Group::GetChannelList($groupid);
        
        $lists = array();
        if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {
            if ($newsletter->val('groupid')){
                $lists = DynamicList::GetAllLists('group',true);
            } else {
                $lists = DynamicList::GetAllLists('zone',true);
            }
        }

		$headtitle	= gettext("Newsletters");
        $approval = $newsletter->getApprovalObject();
        $newsletter_personalized_html = '';
        $allowTitleUpdate = true;
        $allowDescriptionUpdate = true;
        if ($approval) {
            [$allowTitleUpdate,$allowDescriptionUpdate] = $approval->canUpdateAfterApproval();
            if (!$allowDescriptionUpdate){
                $newsletter_personalized_html = str_replace($replace_vars, $replacement_vars, $newsletter->val('newsletter'));
            }
        }
		include(__DIR__ . "/views/templates/manage_section_dynamic_button.html");
		include(__DIR__ . "/views/templates/group_update_newsletter.template.php");
	} else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("No newsletter found. Please try again."), gettext('Error'));
	}
}

## OK
elseif (isset($_GET['openReviewNewsletterModal']) && isset($_GET['newsletterid'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['openReviewNewsletterModal']))<0 ||
        ($newsletterid = $_COMPANY->decodeId($_GET['newsletterid'])) < 1 ||
        ($newsletter = Newsletter::GetNewsletter($newsletterid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canCreateOrPublishContentInScopeCSV($newsletter->val('groupid'),$newsletter->val('chapterid'), $newsletter->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
	}

    $reviewers = User::GetReviewersByScope($newsletter->val('groupid'), $newsletter->val('chapterid'), $newsletter->val('channelid'));

    // The template needs following inputs in addition to $reviewers set above
    $template_review_what = gettext('Newsletter');
    $enc_groupid = $_COMPANY->encodeId($newsletter->val('groupid'));
    $enc_objectid = $_COMPANY->encodeId($newsletterid);
    $template_email_review_function = 'sendNewsletterToReview';

    include(__DIR__ . "/views/templates/general_email_review_modal.template.php");
	
}

## OK
elseif (isset($_GET['openPublishNewsletterModal']) && isset($_GET['newsletterid'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['openPublishNewsletterModal']))<0 ||
        ($newsletterid = $_COMPANY->decodeId($_GET['newsletterid'])) < 1 ||
        ($newsletter = Newsletter::GetNewsletter($newsletterid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canPublishContentInScopeCSV($newsletter->val('groupid'),$newsletter->val('chapterid'), $newsletter->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    // Do not allow lists to be published on the platform or on integrations.
    if (empty($newsletter->val('listids'))){
        $hidePlatformPublish = false;
        $integrations =   GroupIntegration::GetUniqueGroupIntegrationsByExternalType($groupid,$newsletter->val('chapterid'), $newsletter->val('channelid'),array(),'newsletter');
    } else {
        $hidePlatformPublish = true;
        $integrations = array();
    }

    // The following three parameters are needed by the publishing template.
    $enc_groupid = $_COMPANY->encodeId($newsletter->val('groupid'));
    $enc_objectid = $_COMPANY->encodeId($newsletterid);
    $template_publish_what = gettext('Newsletter');
    $template_publish_js_method = 'publishNewsletter';

    $email_subtext =
        gettext('Deselecting "Email" means newsletter won\'t be distributed automatically via email thus reducing its reach and engagement. ')
        . gettext('To send emails later, you will have to unpublish this newsletter and republish by checking the "Email" option')
        . gettext('Alternatively, use the shareable link to invite directly.');

    $pre_select_publish_to_email = $_COMPANY->getAppCustomization()['newsletters']['publish']['preselect_email_publish'] ?? true;
    $hideEmailPublish = $_COMPANY->getAppCustomization()['newsletters']['disable_email_publish']?? false;
    include(__DIR__ . "/views/templates/general_schedule_publish.template.php");
}

## OK
elseif (isset($_GET['sendNewsletterToReview']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        !isset($_POST['objectid']) ||
        ($newsletterid = $_COMPANY->decodeId($_POST['objectid']))<1 ||
        ($newsletter = Newsletter::GetNewsletter($newsletterid)) == null
    ) {
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
	if (isset($_POST['reviewers']) && !empty(@$_POST['reviewers'][0])){
		$reviewers = $_POST['reviewers'];
	}
	$reviewers[] = $_USER->val('email'); // Add the current user to review list as well.

	$allreviewers = implode(',',array_unique (array_merge ($validEmails , $reviewers)));
	if (!empty($allreviewers)){
		$review_note = raw2clean($_POST['review_note']);
		$newletter_review_job = new NewsLetterJob($groupid,0,$newsletterid);
		$newletter_review_job->sendForReview($allreviewers, $review_note);
        // Update Newsletter as under review
        $newsletter->updateNewsletterForReview();
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Newsletter emailed for review'), gettext('Success'));
	} else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please add some reviewers'), gettext('Error'));
	}
}

## OK
elseif (isset($_GET['publishNewsletter']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        !isset($_POST['objectid']) ||
        ($newsletterid = $_COMPANY->decodeId($_POST['objectid']))<1 ||
        ($newsletter = Newsletter::GetNewsletter($newsletterid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $encGroupId = $_COMPANY->encodeId($newsletter->val('groupid'));
    $encNewsletterId = $_COMPANY->encodeId($newsletter->id());

    // Authorization Check
    if (!$_USER->canPublishContentInScopeCSV($newsletter->val('groupid'),$newsletter->val('chapterid'), $newsletter->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    // If 'publish_where' === 'online', then change isactive=1, publishdate=now(), do not process other fields
    // If 'publish_where' === 'online & email' then change isactive=3, .... find out the value for publish date
    //      If 'publish_when' === 'now', then  publishdate=now()
    //      Else publishdate == calculated value
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
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Newsletter publishing can be scheduled up to 30 days in the future.'), gettext('Error'));
        }
        if ($delay < 0){
            $delay = 15;
        }
    }

    $publish_where_integration = array();
    if (!empty($_POST['publish_where_integration'])){
        $publish_where_integration_input = $_POST['publish_where_integration'];
        foreach($publish_where_integration_input as $encExternalId){
            $externalId = $_COMPANY->decodeId($encExternalId);
            $externalIntegraions = GroupIntegration::GetGroupIntegrationsApplicableForScopeCSV($newsletter->val('groupid'),$newsletter->val('chapterid'),$newsletter->val('channelid'),$externalId,true);
            foreach($externalIntegraions as $externalIntegraion){
                array_push($publish_where_integration,$externalIntegraion->id());
            }
        }
    }

	$update_code = $newsletter->updateNewsletterForSchedulePublishing($delay);
    
    if ($update_code) {
        // foreach ($selectedChapterIds as $selectedChapterId) {
        //     $newletter_review_job = new NewsLetterJob($groupid, $selectedChapterId, $newsletterid);
        //     $newletter_review_job->delay = $delay;
        //     $newletter_review_job->saveAsBatchCreateType();
        // }

        $newletter_review_job = new NewsLetterJob($groupid, 0, $newsletterid);
        $newletter_review_job->delay = $delay;
        $newletter_review_job->saveAsBatchCreateType($sendEmails, $publish_where_integration);
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Newsletter scheduled for publishing.'), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Newsletter published successfully.'), gettext('Success'));
}

## OK
elseif (isset($_GET['previewNewsletter']) && isset($_GET['newsletterid'])) {

	$encGroupId = $_GET['previewNewsletter'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['previewNewsletter']))<0 ||
        ($newsletterid = $_COMPANY->decodeId($_GET['newsletterid']))<1 ||
        ($newsletter = Newsletter::GetNewsletter($newsletterid)) == null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
	}

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
    
    $topicid = $newsletterid;
    if ($newsletter->val('isactive') != Newsletter::STATUS_DRAFT && $_COMPANY->getAppCustomization()['newsletters']['comments']) { 
        $comments = Newsletter::GetComments_2($newsletterid);       
        $commentid = 0;
        $disableAddEditComment = false;
        $submitCommentMethod = "NewsletterComment";
        $mediaUploadAllowed = $_COMPANY->getAppCustomization()['newsletters']['enable_media_upload_on_comment'];
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
    if ($newsletter->val('isactive') != Newsletter::STATUS_DRAFT && $_COMPANY->getAppCustomization()['newsletters']['likes']) { 
        $myLikeType = Newsletter::GetUserReactionType($topicid);
        $myLikeStatus = (int) !empty($myLikeType);
        $latestLikers = Newsletter::GetLatestLikers($topicid);
        $totalLikers = Newsletter::GetLikeTotals($topicid);
        $likeTotalsByType = Newsletter::GetLikeTotalsByType($topicid);
        $showAllLikers = true;
        $likeUnlikeMethod = 'NewsletterTopic';
    }

    $replace_vars = ['[%RECIPIENT_NAME%]', '[%RECIPIENT_FIRST_NAME%]', '[%RECIPIENT_LAST_NAME%]', '[%RECIPIENT_EMAIL%]', '[%PUBLISH_DATE_TIME%]'];
    $publish_date_time  = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($newsletter->val('publishdate'), true, true, true);
    $replacement_vars = ['[%RECIPIENT_NAME%]', '[%RECIPIENT_FIRST_NAME%]', '[%RECIPIENT_LAST_NAME%]', '[%RECIPIENT_EMAIL%]', $publish_date_time]; // We do not know the firstname or lastname
    $newsletter_personalized_html = str_replace($replace_vars, $replacement_vars, $newsletter->val('newsletter'));

    include(__DIR__ . "/views/templates/newsletter_preview.template.php");  
}

## OK
elseif (isset($_GET['openUnPublishNewsletterModal']) && isset($_GET['newsletterid'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 ||
        ($newsletterid = $_COMPANY->decodeId($_GET['newsletterid'])) < 1 ||
        ($newsletter = Newsletter::GetNewsletter($newsletterid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }   

    // The template needs following inputs to call next function.
    $enc_groupid = $_COMPANY->encodeId($groupid);
    $enc_objectid = $_COMPANY->encodeId($newsletterid);
   
    include(__DIR__ . "/views/templates/unpublish_newsletter_modal.template.php");
	
}

## OK
elseif (isset($_GET['unPublishNewsletter']) && isset($_GET['newsletterid'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 ||
        ($newsletterid = $_COMPANY->decodeId($_GET['newsletterid'])) < 1 ||
        ($newsletter = Newsletter::GetNewsletter($newsletterid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }   

    // Authorization Check
    if (!$_USER->canPublishContentInScopeCSV($newsletter->val('groupid'),$newsletter->val('chapterid'), $newsletter->val('channelid'))) {
    header(HTTP_FORBIDDEN);
    exit();
    }

    if ($newsletter->unpublishNewsletter()) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Newsletter unpublished successfully.'), gettext('Success'));
    }
    exit();	
}

## OK
elseif (isset($_GET['deleteNewsletter']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['deleteNewsletter']))<0 ||
        !isset($_POST['newsletterid']) ||
        ($newsletterid = $_COMPANY->decodeId($_POST['newsletterid']))<1 ||
        ($newsletter = Newsletter::GetNewsletter($newsletterid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($newsletter->val('groupid'));

    // Authorization Check
    if (!$_USER->canUpdateContentInScopeCSV($newsletter->val('groupid'),$newsletter->val('chapterid'), $newsletter->val('channelid'), $newsletter->val('isactive')) &&
        !$_USER->canPublishContentInScopeCSV($newsletter->val('groupid'),$newsletter->val('chapterid'), $newsletter->val('channelid'))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	echo $newsletter->inactivateNewsletter();
}

## OK
elseif (isset($_GET['cancelNewsletterPublishing']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['cancelNewsletterPublishing']))<0 ||
        !isset($_POST['newsletterid']) ||
        ($newsletterid = $_COMPANY->decodeId($_POST['newsletterid']))<1 ||
        ($newsletter = Newsletter::GetNewsletter($newsletterid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $encGroupId = $_COMPANY->encodeId($newsletter->val('groupid'));
    $encNewsletterId = $_COMPANY->encodeId($newsletter->id());

    // Authorization Check
    if (!$_USER->canPublishContentInScopeCSV($newsletter->val('groupid'),$newsletter->val('chapterid'), $newsletter->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($newsletter->cancelNewsletterPublishing()) {
            $job = new NewsLetterJob($groupid, 0, $newsletterid);
            $job->cancelAllPendingJobs();
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Newsletter publishing canceled successfully.'), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Unable to cancel newsletter publishing!'), gettext('Error'));
}

## OK
elseif (isset($_GET['manageNewsletterAttachments']) && isset($_GET['id'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['manageNewsletterAttachments']))<0 ||
        ($newsletterid = $_COMPANY->decodeId($_GET['id']))<1 ||
        ($newsletter = Newsletter::GetNewsletter($newsletterid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $encGroupId = $_COMPANY->encodeId($newsletter->val('groupid'));
    $encNewsletterId = $_COMPANY->encodeId($newsletter->id());

    // Authorization Check
    $isAllowedToUpdateContent = $_USER->canUpdateContentInScopeCSV($newsletter->val('groupid'),$newsletter->val('chapterid'), $newsletter->val('channelid'), $newsletter->val('isactive'));

    $attachments = $newsletter->getNewsletterAttachments();
    include(__DIR__ . "/views/templates/newsletter_attachments_modal.template.php");
}

## OK
elseif (isset($_GET['upoadNewsLetterAttachments'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
	//Data Validation
    if (!isset($_POST['groupid']) ||
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        !isset($_POST['newsletterid']) ||
        ($newsletterid = $_COMPANY->decodeId($_POST['newsletterid']))<1 ||
        ($newsletter = Newsletter::GetNewsletter($newsletterid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $encGroupId = $_COMPANY->encodeId($newsletter->val('groupid'));
    $encNewsletterId = $_COMPANY->encodeId($newsletter->id());

    // Authorization Check
    if (!$_USER->canUpdateContentInScopeCSV($newsletter->val('groupid'),$newsletter->val('chapterid'), $newsletter->val('channelid'), $newsletter->val('isactive'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $titles = $_POST['title'];
    //$files = $_FILES['attachment'];
    $attach_count = count($_FILES);
    if ($attach_count > 0){
        $x = 0;
        for($i=0;$i<$attach_count;$i++){
            if ($_FILES['attachment']['name'][$i]!='' &&
                ((int)$_FILES['attachment']['size'][$i]) <= 102400 &&
                ($extension = strtolower($db->getExtension($_FILES['attachment']['name'][$i]))) === 'ics'
            ){
                $file 	    =	basename($_FILES['attachment']['name'][$i]);
                $tmp 		=	$_FILES['attachment']['tmp_name'][$i];
                $extension		=	strtolower($db->getExtension($file));
                $actual_name ="newslettr_attachment_".teleskope_uuid().".".$extension;
                $filelink = $_COMPANY->saveFile($tmp,$actual_name,'NEWSLETTER_ATTACH');
                if (($filelink)) {
                    $newsletter->addNewsletterAttachment($titles[$i],$filelink);
                   $x++;
                }
            } else {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Upload error, maximum allowed .ics filesize is 100 KB!'), gettext('Error'));
            }
        }
        if ($x==0){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('All the files are required!'), gettext('Error'));
        } else {
            $attachments = $newsletter->getNewsletterAttachments();
            $newsletter_status = 2;
            $isAllowedToUpdateContent = true;
            include(__DIR__ . "/views/templates/newsletter_attachment_data.template.php");
            exit;
        }
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('All the files are required!'), gettext('Error'));
    }
 }

## OK
elseif (isset($_GET['deletNewsLetterAttachment'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
	//Data Validation
    if (!isset($_POST['groupid']) ||
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        !isset($_POST['newsletterid']) ||
        ($newsletterid = $_COMPANY->decodeId($_POST['newsletterid']))<1  ||
        !isset($_POST['attachment_id']) ||
        ($attachment_id = $_COMPANY->decodeId($_POST['attachment_id']))<1 ||
        ($newsletter = Newsletter::GetNewsletter($newsletterid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $encGroupId = $_COMPANY->encodeId($newsletter->val('groupid'));
    $encNewsletterId = $_COMPANY->encodeId($newsletter->id());


    // Authorization Check
    if (!$_USER->canUpdateContentInScopeCSV($newsletter->val('groupid'),$newsletter->val('chapterid'), $newsletter->val('channelid'), $newsletter->val('isactive'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $newsletter->deleteNewsletterAttachment($attachment_id);
    $attachments = $newsletter->getNewsletterAttachments();
    $newsletter_status = 2;
    $isAllowedToUpdateContent = true;
    include(__DIR__ . "/views/templates/newsletter_attachment_data.template.php");
 }


elseif (isset($_GET['getGroupChaptersNewsletters']) && isset($_GET['chapterid'])){
    //Data Validation
    $showGlobalChapterOnly = intval($_SESSION['showGlobalChapterOnly'] ?? 0);
    $showGlobalChannelOnly = intval($_SESSION['showGlobalChannelOnly'] ?? 0);

    $chapterid = 0;
    $channelid = 0;

    if (($groupid = $_COMPANY->decodeId($_GET['getGroupChaptersNewsletters']))<0 ||
        (($group = Group::GetGroup($groupid)) === NULL) ||
        ($chapterid = $_COMPANY->decodeId($_GET['chapterid']))<0 ||
        (isset($_GET['channelid']) && ($channelid = $_COMPANY->decodeId($_GET['channelid'])) < 0)
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $enc_groupid = $_COMPANY->encodeId($groupid);
    $enc_chapterid = $_COMPANY->encodeId($chapterid);
    $enc_channelid = $_COMPANY->encodeId($channelid);

    // Authorization Check
    if (!$_USER->canViewContent($groupid)){
	header(HTTP_FORBIDDEN);
	exit();
    }

    $selectedYear = 0;
    $page = (int) ($_GET['page'] ?? 1);
    $per_page = MAX_HOMEPAGE_FEED_ITERATOR_ITEMS;

    $timezone = $_SESSION['timezone']??"UTC";

    $data = Newsletter::GetGroupNewsletterViewData($groupid, $showGlobalChapterOnly, $chapterid, $showGlobalChannelOnly, $channelid, $selectedYear, $timezone, $page, $per_page);
    for($i=0;$i<count($data);$i++){
        $data[$i]['chapters']       = $group->GetChaptersCSV($data[$i]['chapterid'],$groupid);
        
        $utc_tz						= new DateTimeZone('UTC');
        $local_tz					= new DateTimeZone($_SESSION['timezone']);
        $publishedAt				= (new DateTime($data[$i]['publishdate'], $utc_tz))->setTimezone($local_tz);
        $data[$i]['month']			= $publishedAt->format('F');
        $data[$i]['year']			= $publishedAt->format('Y');
    }

    $headtitle	= gettext("Newsletters");
    
    if ($page === 1) {
	include(__DIR__ . "/views/templates/group_newsletter_timeline.template.php");
    } else {
        include(__DIR__ . "/views/templates/group_newsletter_timeline_rows.template.php");
    }
}

## OK
elseif (isset($_GET['emailMeNewsletter']) && isset($_GET['newsletterid'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['emailMeNewsletter']))<0 ||
        ($newsletterid = $_COMPANY->decodeId($_GET['newsletterid']))<1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $enc_groupid = $_COMPANY->encodeId($groupid);

    // Authorization Check
    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $sendMeNewsletterJob = new NewsLetterJob($groupid,0,$newsletterid);
    $sendMeNewsletterJob->sendForReview($_USER->val('email'), '', 'Per your request: ');
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Newsletter emailed successfully.'), gettext('Success'));
}

elseif (isset($_GET['pinUnpinNewsletter']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (!isset($_POST['groupid']) ||
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        !isset($_POST['newsletterid']) ||
        ($newsletterid = $_COMPANY->decodeId($_POST['newsletterid']))<1 ||
        (($newsletter = Newsletter::GetNewsletter($newsletterid)) == null) ||
        !$_COMPANY->getAppCustomization()['newsletters']['pinning']['enabled']
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $enc_groupid = $_COMPANY->encodeId($groupid);
    $enc_newsletterid = $_COMPANY->encodeId($newsletterid);
    $type = $_POST['type'] == 2 ? 0 : 1;  // 2 is for unpin, 1 is for pinning.
  
   // Authorization Check
   if (!$_USER->canPublishContentInScopeCSV($newsletter->val('groupid'),$newsletter->val('chapterid'), $newsletter->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    
    if ($newsletter->pinUnpinNewsletter($type)){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Updated successfully."), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Error'));
    }
}

## OK
elseif (isset($_GET['cloneNewsLetterForm'])  && $_SERVER['REQUEST_METHOD'] === 'POST' ){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['cloneNewsLetterForm']))<0 ||
        ($newsletterid = $_COMPANY->decodeId($_POST['newsletterid']))<1 ||
        ($newsletter = Newsletter::GetNewsletter($newsletterid)) == null
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
    $newsletter_name = "Clone of ". html_entity_decode($newsletter->val('newslettername'));
    $clone_newsletterid = Newsletter::CreateNewsletter($groupid, $newsletter_name, $newsletter->val('templateid'), $newsletter->val('newsletter'), $newsletter->val('template'), $newsletter->val('chapterid'), $newsletter->val('channelid'), $newsletter->val('content_replyto_email'), $newsletter->val('listids'), $newsletter->val('use_and_chapter_connector'), html_entity_decode($newsletter->val('newsletter_summary')??''));
    if($clone_newsletterid){
        $newsletterData = array('groupid'=>$_COMPANY->encodeId($groupid), 'newsletterid'=>$_COMPANY->encodeId($clone_newsletterid));
    }
    AjaxResponse::SuccessAndExit_STRING(1, $newsletterData,gettext('Newsletter Cloned successfully.'), gettext('Success'));
}

## OK
elseif (isset($_GET['getNewsletterActionButton']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($newsletterid = $_COMPANY->decodeId($_GET['getNewsletterActionButton']))<0 ||
        ($newsletter=Newsletter::GetNewsletter($newsletterid)) === null   ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Check for Request Approval if active
    if($_COMPANY->getAppCustomization()['newsletters']['approvals']['enabled']){
        $approval = $newsletter->getApprovalObject() ? $newsletter->getApprovalObject(): '';
        $topicType = Teleskope::TOPIC_TYPES['NEWSLETTER'];
    }

    $chapterids = empty($newsletter->val('chapterid')) ? array(0) : explode(',', $newsletter->val('chapterid'));
    $isAllowedToUpdateContent = true;
    $isAllowedToPublishContent = true;
    $isAllowedToManageContent = true;
    if (!$_USER->isAdmin()){
        foreach ($chapterids as $chapterid) {
            $isAllowedToUpdateContent = ($isAllowedToUpdateContent) ? $_USER->canUpdateContentInScopeCSV($newsletter->val('groupid'),$chapterid,$newsletter->val('channelid'),$newsletter->val('isactive')) : false;
            $isAllowedToPublishContent = ($isAllowedToPublishContent) ? $_USER->canPublishContentInScopeCSV($newsletter->val('groupid'),$chapterid,$newsletter->val('channelid')) : false;
            $isAllowedToManageContent = ($isAllowedToManageContent) ? $_USER->canManageContentInScopeCSV($newsletter->val('groupid'),$chapterid,$newsletter->val('channelid')) : false;
        }
    }

    include(__DIR__ . "/views/templates/newsletter_action_button.template.php");
}
else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}
