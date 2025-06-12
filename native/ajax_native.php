<?php
include_once __DIR__.'/head.php';

###### All Ajax Calls ##########

if (isset($_GET['searchUserForRecognition']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
  //Data Validation
  if (
      ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 
  ) {
      header(HTTP_BAD_REQUEST);
      exit();
  }
  $keyword = raw2clean($_GET['keyword']);
  $searchAllUsers = intval($_GET['searchAllUsers'] == "true");
  $uid = isset($_GET['uid']) ? $_COMPANY->decodeId($_GET['uid']) : ""; 
  $readonly = !empty($_GET["editpart"]) ?   "readonly" : "" ;
  if ($keyword || !empty($uid)) {
    $recognizeby = isset($_GET["recognizeby"]) ? 1 : 0 ;

      $excludeCondition = "";
      $searchAllUsersConditon = "";
      if (!$searchAllUsers ){
          $searchAllUsersConditon = " userid IN (SELECT `userid` FROM `groupmembers` WHERE `groupid`='{$groupid}' AND `isactive`=1)";
      }

      $activeusers = array();
      if($uid > 0)
      {
         $u1 =  User::GetUser($uid);
         if ($u1) {
             $activeusers = array($u1->toArray());
         }
      } else {
         $activeusers = User::SearchUsersByKeyword($keyword,$searchAllUsersConditon ,$excludeCondition); // Note $excludeCondition is added as " AND ({$excludeCondition}) "
      }
      $dropdown = '';

      if (!empty($activeusers)) {
        if($recognizeby > 0)
        {
         $dropdown .= "<select tabindex='0' class='form-control userdata' name='recognizeby' onchange='closeDropdown()' id='user_search1' required ".$readonly.">";
        }else{
         $dropdown .= "<select tabindex='0' class='form-control userdata' name='userid' onchange='closeDropdown()' id='user_search' required ".$readonly.">";
        }
          $matchCount = 0;
          foreach ($activeusers as $activeuser) {
              if ($_USER->id()!=$activeuser['userid']  || $recognizeby > 0 || empty($keyword)){
                  $matchCount++;
                  $dropdown .= "<option value='" . $_COMPANY->encodeId($activeuser['userid']) . "'>" . rtrim(($activeuser['firstname'] . " " . $activeuser['lastname']), " ") . " (" . $activeuser['email'] . ") - " . $activeuser['jobtitle'] . "</option>";
              }
          }
          if ($matchCount){
              $dropdown .= '</select>';
              if($recognizeby > 0 && empty($readonly))
              {
                $dropdown .= "<button type='button' class='btn btn-link' onclick=removeSelectedrecognizebyUser('0') >".gettext("Remove")."</button>";
              }elseif(empty($readonly)){
              $dropdown .= "<button type='button' class='btn btn-link' onclick=removeSelectedUser('0') >".gettext("Remove")."</button>";
              }
          
          } else {
              $dropdown .= "<option value=''>".gettext("No match found")."</option>";
              $dropdown .= '</select>';
          }
      } else {
        if($recognizeby > 0){
            $dropdown .= "<select tabindex='0' class='form-control userdata' name='recognizeby' onchange='closeDropdown()' id='user_search1' required ".$readonly.">";
           }else{
               $dropdown .= "<select role='alert' aria-live='polite' class='form-control userdata' name='userid' id='user_search' required ".$readonly.">";
           }
          $dropdown .= "<option value=''>".gettext("No match found")."</option>";
          $dropdown .= "</select>";
      }
      echo $dropdown;

  } else {
      AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please search a user'), gettext('Error'));

  }
}
elseif(isset($_GET['addOrUpdateRecognitioin']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($recognition_type = $_COMPANY->decodeId($_POST['recognition_type']))<1
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $recognitionID = 0;

    if(isset($_POST["recognitionid"]))
    {
        $recognitionID = $_COMPANY->decodeId($_POST['recognitionid']);
    }


    $behalfOf = $_POST["behalfOf"];
    $recognizedbyTeamName =  "";
    $recognizebyID = 0;
    $check = array(gettext('Search User') => @$_POST['userid'],gettext('Recognition Date') => @$_POST['recognitiondate']);

   if(isset( $_POST["recognizedbyTeam"]))
    {
      $recognizedbyTeamName =  $_POST["recognizedbyTeam"];
    }

    if(isset($_POST["recognizeby"]))
    {
        $recognizebyID =  $_COMPANY->decodeId($_POST["recognizeby"]);
    }

    if($behalfOf == "Team" && empty($_POST["recognizedbyTeam"]))
    {
       $check = array(gettext('Team Name') => @$_POST['recognizedbyTeam'],gettext('Recognition Date') => @$_POST['recognitiondate']);
    }elseif($behalfOf == "Person" && empty($_POST["recognizeby"]))
    {
        $check = array(gettext('Who is recognizing') => @$_POST['recognizeby'],gettext('Recognition Date') => @$_POST['recognitiondate']);
    }

    $checkrequired = $db->checkRequired($check);
    if ($checkrequired) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$checkrequired), gettext('Error'));
    }

    $custom_fields = ViewHelper::ValidateAndExtractCustomFieldsFromPostAttribute('REC');

    $recognizedto = 0;
    if(isset($_POST['userid']))
    {
        $recognizedto = $_COMPANY->decodeId($_POST['userid']);
    }
    $recognitiondate    = $_POST['recognitiondate'];
    $description        = ViewHelper::RedactorContentValidateAndCleanup($_POST['description']);

    if (
        (!$group->getRecognitionConfiguration()['enable_self_recognition'] && ($recognizebyID  === $recognizedto))
        || (!$group->getRecognitionConfiguration()['enable_colleague_recognition'] && ($recognizebyID  !== $recognizedto))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (Recognition::AddOrUpdateRecognition($groupid,$recognizedto, $recognitiondate, $description,'',$recognizedbyTeamName,$recognizebyID,$behalfOf,$recognitionID, $custom_fields)){
        AjaxResponse::SuccessAndExit_STRING(1, $_COMPANY->encodeId(0), gettext("Recognition updated successfully"), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
    }

}

elseif (isset($_GET['uploadRedactorMedia']) && !empty($_GET['uploadRedactorMedia'])){

  $context = strtoupper($_GET['uploadRedactorMedia']);
  if (!in_array($context, array_keys(Company::S3_AREA))) {
      // If the context is not one of the above then reject the request
      $err = ['error' => true, 'message' => gettext('Internal Error (bad context)')];
      echo stripslashes(json_encode($err));
      exit();
  }

  $file = [];
  if (empty($_FILES['file']['name'][0]) || ((int)$_FILES['file']['size'])>5242880) {
      $err = ['error' => true, 'message' => gettext('Upload error, maximum allowed filesize is 5 MB')];
      echo stripslashes(json_encode($err));
      exit();
  }

  $tmp = $_FILES["file"]["tmp_name"][0];
  if (!$tmp) {
      $err = ['error' => true, 'message' => gettext('Unable to upload file, please try again')];
      echo stripslashes(json_encode($err));
      exit();
  }

  $mimetype   =   mime_content_type($tmp);
  $valid_mimes = array('image/jpeg' => 'jpg','image/png' => 'png','image/gif' => 'gif');

  if (in_array($mimetype,array_keys($valid_mimes))) {
      $ext = $valid_mimes[$mimetype];
  } else {
      $err = ['error' => true, 'message' => gettext('Unsupported file type. Only .png, .jpg, .gif or .jpeg files are allowed')];
      echo stripslashes(json_encode($err));
      exit();
  }

  if ($mimetype == 'image/gif' && getimagesize($tmp)[0]>900) {
      $err = ['error' => true, 'message' => sprintf(gettext('gif image width should be less than %s pixels'),900)];
      echo stripslashes(json_encode($err));
      exit();
  }
  $tmp = $_COMPANY->resizeImage($tmp, $ext, 900);

  if ($tmp)
      [$img_width,$img_height] = getimagesize($tmp) ?: ['',''];
  else
      [$img_width,$img_height] = ['',''];
  $s3_file = strtolower($context).'_'.teleskope_uuid(). "." . $ext;
  $s3_url = $_COMPANY->saveFile($tmp, $s3_file, strtoupper($context));

  if (!empty($s3_url)) {
       $file = ['file' => [
           'url' => $s3_url,
           'id' => $s3_file,
           'img_width' => $img_width,
           'img_height' => $img_height
       ]];
       echo stripslashes(json_encode($file));
       exit();
  } else {
      $err = ['error' => true, 'message' => gettext('File upload error')];
      echo stripslashes(json_encode($err));
      exit();
  }
}

elseif (isset($_GET['confirmImageCopyright'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    
  if ($_COMPANY->getAppCustomization()['policy']['image_upload_notice']){
      $notice = $_COMPANY->getAppCustomization()['policy']['image_upload_notice'];
  } else {
      $notice = gettext("The images you attached may be copyrighted. Please only continue if you have rights to use the image.");
  }
  echo $notice;
}

elseif (isset($_GET['searchHashTag'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
  //Logger::Log("Handler API - got: {$_POST['handle']}");
  $handle = strtolower(trim($_POST['handle']));
  $handleData = HashtagHandle::GetHandlesObject($handle);
  $jsonData = json_encode((object)($handleData));
  //Logger::Log("Handler API - returning: {$jsonData}");
  die ($jsonData);
}
elseif (isset($_GET['submitNewDiscussion']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($discussionid = $_COMPANY->decodeId($_POST['discussionid']))<0
        
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $chapterids = '0';
    $channelid = '0';
    $anonymous_post = 0;
    if (isset($_POST['chapters'])){
        # Start - Get Chapterids & Validate the user can perform action in the given chapters and channels
        $encChapterids = isset($_POST['chapters']) ? $_POST['chapters'] : array($_COMPANY->encodeId(0));
        $chapterids = implode(',', $_COMPANY->decodeIdsInArray($encChapterids) ?: '0');
    }

    if (isset($_POST['channelid'])){
        $channelid = $_COMPANY->decodeId($_POST['channelid']);
    }

    // Authorization Check -
    $discussionObj = null;
    if ($discussionid) { // Updating a discussion
        $discussionObj = Discussion::GetDiscussion($discussionid);

        // Updates can be done only by (a) user who created the disucussion or (b) leads
        if (
            $_USER->id() != $discussionObj->val('createdby')
            &&
            !$_USER->canUpdateContentInScopeCSV($discussionObj->val('groupid'),$discussionObj->val('chapterid'),$discussionObj->val('channelid'),$discussionObj->val('isactive'))
        ) {
            header(HTTP_FORBIDDEN);
            exit();
        }

        $message = gettext('Discussion updated successfully');
        $chapterids = $discussionObj->val('chapterid');
        $channelid = $discussionObj->val('channelid');

    } else { // Creating a new discussion

        $discussionSettings = $group->getDiscussionsConfiguration();
        $canPostAsMember  = $discussionSettings['who_can_post']=='members' && $_USER->isGroupMember($groupid);

        // Discussion can be created either by (a) members if member posting is allowed or (b) Leads
        if (
            !$canPostAsMember
            &&
            !$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)
        ) {
            header(HTTP_FORBIDDEN);
            exit();
        }

        if (
            !$canPostAsMember
            &&
            !$_USER->canCreateOrPublishContentInScopeCSV($groupid, $chapterids, $channelid)
        ) {

            // Compute error message, which is dependent upon whether chapter is missing or channel is missing
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

        $message = gettext('Discussion created successfully');
    }

	$title = $_POST['title'];
    $discussion = $_POST['discussion'];
    $anonymous_post = empty($_POST['anonymous_post']) ? 0 : 1;

    if (Str::IsEmptyHTML($discussion)) {
        $discussion = '';
    }

    //Data Validation
	$check = $db->checkRequired(array('Title'=>$title,'Description'=>$discussion));
	if($check){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty!"),$check), gettext('Error!'));
	}

    // Check External Images link
    preg_match_all( '@src="([^"]+)"@' , $_POST['discussion'], $match );
    $srcs = array_pop($match);
    $external = false;
    foreach($srcs as $src) {
        if (strpos(trim($src), 'https://'.S3_BUCKET) !== 0){
            $external = true;           
            break;
        }    
    }

    if ($external){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("The content contains links to external images which may not display properly on all devices. In order to fix this, attach images from your computer."), gettext('Error!'));
    }

    $discussionid = Discussion::CreateOrUpdateDiscussion($discussionid,$groupid, $title, $discussion, $chapterids, $channelid,$anonymous_post);
    
    if ($discussionid){ 
            if (isset($_POST['publish_to_email']) && $_POST['publish_to_email'] == 1){
            $job = new DiscussionJob($groupid, $discussionid);
            if ($discussionObj){
                $job->saveAsBatchUpdateType(1);
            } else {
                $job->saveAsBatchCreateType(1);
            }
        }
        $_SESSION['show_discussion_id'] = $discussionid;
        AjaxResponse::SuccessAndExit_STRING(1, '', $message, gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
    }
}

elseif (isset($_GET['addUpdateTeamContent']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($taskid = $_COMPANY->decodeId($_POST['taskid'])) < 0

    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $tasktitle = $_POST['tasktitle'];
    $sendEmail = $_POST['sendEmail'] == 1 ? 1 : 0;
    $timezone = isset($_POST['timezone']) ? $_POST['timezone'] : 'UTC';
    $duedate = null;
    if (!empty($_POST['duedate'])) {
        $duedate = $_POST['duedate'];
        $hour = $_POST['hour'] ?: '00';
        $minutes = $_POST['minutes'] ?: '00';
        $period = (empty($_POST['hour']) && empty($_POST['hour'])) ? '' : $_POST['period'];
        $duedate = $duedate . " " . $hour . ":" . $minutes . " " . $period;
        $duedate = $db->covertLocaltoUTC("Y-m-d H:i:s", $duedate, $timezone);
    }
    $description = ViewHelper::RedactorContentValidateAndCleanup($_POST['description']);
    $assignedto = 0;
    $task_type = $_POST['task_type'];
    if ($task_type != 'touchpoint') {
        $assignedto = $_COMPANY->decodeId($_POST['assignedto']);
    }

    $visibility = 0;
    if (isset($_POST['visibility'])) {
        $visibility = $_POST['visibility'];
    }
    $parent_taskid = 0;
    if (isset($_POST['parent_taskid']) &&   ($decoded_parent_taskid = $_COMPANY->decodeId($_POST['parent_taskid'])) >= 0 ) {
        $parent_taskid = $decoded_parent_taskid;
    }

    $id = $team->addOrUpdateTeamTask($taskid, $tasktitle, $assignedto, $duedate, $description, $task_type, $visibility, $parent_taskid);
    if ($id) {

        $assignedUser = User::GetUser($assignedto);
        if ($taskid) {
            $returnMessage = gettext(" updated successfully");
            $emailStatus = "updated";
        } else {
            $returnMessage = gettext(" created successfully");
            $emailStatus = "created";
        }

        if ($task_type == 'todo') {
            if ($sendEmail && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ // Skip email notification if individual development
                $team->sendActionItemEmailToTeamMember($id,$emailStatus);
            }
            $returnMessage = gettext('Action Item') . $returnMessage;
        }

        if ($task_type == 'touchpoint') {
            if ($sendEmail && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ // Skip email notification if individual development
                $team->sendTouchPointEmailToTeamMember($id, $emailStatus);
            }
            $returnMessage = gettext('Touch Point') . $returnMessage;
        }

        if ($task_type == 'feedback') {
            $team->sendFeedbackEmailToTeamMember($id,$emailStatus);
            $returnMessage = gettext('Feedback') . $returnMessage;
        }
        echo $returnMessage;
        exit();
    } else {
        echo "0";
    }
    exit();
}

elseif(isset($_GET['addOrUpdateTeamEvent']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    /**
     * ## If there are any database related changes for event, make sure to do same on "cloneEvent" method. ##
     */

    //Data Validation
    $event = null;
    $collaborate = '';
    $chapterids = '0';
    $channelid = 0;
    $regionid = 0;
    $isprivate = 0;
    $seriesEvent = null;
    $add_photo_disclaimer = 0;
    $event_series_id = 0;
    $touchpointid = 0;

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($eventid = $_COMPANY->decodeId($_POST['eventid'])) < 0 ||
        ($touchpointid = $_COMPANY->decodeId($_POST['touchpointid'])) < 0 ||
        (isset($_POST['timezone'])
            && !isValidTimeZone($_POST['timezone'])
        )
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($eventid && ($event=TeamEvent::GetEvent($eventid)) === null){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    ViewHelper::ValidateObjectVersionMatchesWithPostAttribute($event);

    // Authorization Check
    if (!$_USER->isProgramTeamMember($teamid) && !$_USER->canManageGroup($groupid)
     ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $eventtitle = $_POST['eventtitle'];
    $eventtype = (int)($_POST['eventtype']??0);
    $event_description = ViewHelper::RedactorContentValidateAndCleanup($_POST['event_description']);
    $event_attendence_type = (int)$_POST['event_attendence_type'];
    $event_contact = trim($_POST['event_contact']);
    $eventvanue = '';
    $vanueaddress = '';
    $web_conference_link = '';
    $web_conference_detail = '';
    $web_conference_sp = '';
    $multiDayEvent = 0;
    $invited_groups = '';
    $check = array('Event Name' => @$eventtitle,'Event Start Date' => @$_POST['eventdate'], 'Time Start Hours' => @$_POST['hour'], 'Event Contact' => $event_contact);
    if(!$_COMPANY->getAppCustomization()['teams']['team_events']['disable_event_types']){
        $check = array_merge($check,array('Event Type' => @$_POST['eventtype']));
    }
    if (0){ 
        if(!empty($_POST['multiDayEvent'])){
            $check = array_merge($check,array('Event End Date' => @$_POST['end_date'],'Event End Time' => @$_POST['end_hour']));
            $multiDayEvent = 1;
        } else {
            $check = array_merge($check,array('Event Duration' => @$_POST['hour_duration']));
        }
    }

    if(!empty($_POST['add_photo_disclaimer'])){
        $add_photo_disclaimer = 1;
    }

    $checkin_enabled = 0;
    if ($event_attendence_type ===1 ){
        $check = array_merge($check,array('Venue' => @$_POST['eventvanue']));
        $eventvanue = $_POST['eventvanue'];
        $vanueaddress = $_POST['vanueaddress'] ?: '';
    } else if($event_attendence_type ===2) {
        $check = array_merge($check,array('Web Conf. Link' => @$_POST['web_conference_link']));
        $web_conference_link = $_POST['web_conference_link'];
        $web_conference_detail = $_POST['web_conference_detail'] ?: '';
        $checkin_enabled = intval($_COMPANY->getAppCustomization()['event']['checkin'] && $_COMPANY->getAppCustomization()['event']['checkin_default']);
    } else if($event_attendence_type ===3) {
        $check = array_merge($check,array('Venue' => @$_POST['eventvanue'],'Web Conf. Link' => @$_POST['web_conference_link']));
        $eventvanue = $_POST['eventvanue'];
        $vanueaddress = $_POST['vanueaddress'] ?: '';
        $web_conference_link = $_POST['web_conference_link'];
        $web_conference_detail = $_POST['web_conference_detail'] ?: '';
        $checkin_enabled = intval($_COMPANY->getAppCustomization()['event']['checkin'] && $_COMPANY->getAppCustomization()['event']['checkin_default']);
    }

    if (!empty($web_conference_detail)){
        $web_conference_detail = str_replace('\n','<br>',str_replace('\r\n','<br>',$web_conference_detail));
    }

    $checkrequired = $db->checkRequired($check);

    if ($checkrequired) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$checkrequired), gettext('Error'));
    }

    if ($web_conference_link) {
        
        if (strpos($web_conference_link, '.proofpoint.') !== false ||
            strpos($web_conference_link, '.safelinks.') !== false){
                AjaxResponse::SuccessAndExit_STRING(2, '', gettext("The link provided in the conference link field seems to be invalid. In order to make sure that participants have success, please copy the link directly from the creator's calendar or from the online meeting service provider."), gettext('Error'));
        }

        $web_conference_sp = Event::GetWebConfSPName($web_conference_link);
    }

    // Check External Images link
    preg_match_all( '@src="([^"]+)"@' , $_POST['event_description'], $match );
    $srcs = array_pop($match);
    $external = false;

    foreach($srcs as $src) {
        if (strpos(trim($src), 'https://'.S3_BUCKET) !== 0){
            $external = true;
            break;
        }
    }
    if ($external){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("The content contains links to external images which may not display properly on all devices. In order to fix this, attach images from your computer."), gettext('Error'));
    }

    $max_inperson = 0;
    $max_inperson_waitlist = 0;
    $max_online = 0;
    $max_online_waitlist = 0;
   
    #Time zone
    $event_tz = isset($_POST['timezone']) ? $_POST['timezone'] : (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
    $current_timestamp = time();

    #Event Start Date time
    $eventdate = $_POST['eventdate'];
    $hour = $_POST['hour'] ?? '00';
    $minutes = $_POST['minutes'] ?? '00';
    $period = $_POST['period'];
    $startformat = $eventdate . " " . $hour . ":" . $minutes . " " . $period;
    $start = $db->covertLocaltoUTC("Y-m-d H:i:s", $startformat, $event_tz);

    #Event End Date time
    if ($multiDayEvent) {
        $end_date = $_POST['end_date'];
        $end_hour = $_POST['end_hour'] ?? '00';
        $end_minutes = $_POST['end_minutes'] ?? '00';
        $end_period = $_POST['end_period'];
        $endformat = $end_date . " " . $end_hour . ":" . $end_minutes . " " . $end_period;

        #Check if event start date and end date are valid
        if ((($start_timestamp = strtotime($startformat. ' '.$event_tz)) === false) ||
        ($start_timestamp < $current_timestamp) ||
        (($end_timestamp = strtotime($endformat. ' '.$event_tz)) === false) ||
        ($end_timestamp < $current_timestamp)
        ){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Start or End time cannot be in the past"), gettext('Error'));
        }
        if ($end_timestamp < $start_timestamp){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Event end date time cannot be earlier than start date time"), gettext('Error'));
        }
        if ($end_timestamp-$start_timestamp <=86400){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Multi-day events duration must be more than 24 hours"), gettext('Error'));
        }
    } else {
        $hour_duration = $_POST['hour_duration'] ?? '00';
        $minutes_duration = $_POST['minutes_duration'] ?? '00';
        if ($hour_duration == '24') {
            $minutes_duration = '00';
        }
        $add_time = "+" . $hour_duration . " hour +" . $minutes_duration . " minutes";
        $endformat = date('Y-m-d H:i:s', strtotime($add_time, strtotime($startformat)));

        #Check if event start date and end date are valid
        if ((($start_timestamp = strtotime($startformat. ' '.$event_tz)) === false) ||
        ($start_timestamp < $current_timestamp) ||
        (($end_timestamp = strtotime($add_time, $start_timestamp)) === false) ||
        ($end_timestamp < $current_timestamp)){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Start or End time cannot be in the past"), gettext('Error'));
        }
    }
    $end = $db->covertLocaltoUTC("Y-m-d H:i:s", $endformat, $event_tz);

    // Custom Fields 
    $custom_fields_input = array();
    $custom_fields_input = json_encode($custom_fields_input);
    $budgeted_amount = 0;
    $calendar_blocks = (int)($_POST['calendar_blocks'] ?? 1);
    $venue_info = '';
    $venue_room = '';


    if (!$eventid) {
        $eventid = TeamEvent::CreateNewTeamEvent($teamid, $groupid, $chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $event_series_id, $custom_fields_input, $event_contact, $venue_info, $venue_room, $isprivate, 'teamevent', $add_photo_disclaimer, $calendar_blocks);

        if ($eventid) {
            if (!$touchpointid){ // Create and link Touch point
                $touchpointid = $team->addOrUpdateTeamTask(0, $eventtitle, '0', $start, '', 'touchpoint', 0);
            }
            $team->linkTouchpointEventId($touchpointid,$eventid, $end);
            // Publish Email Update
            $delay = 15;
            $event = TeamEvent::GetEvent($eventid);
            $isactive = Event::STATUS_AWAITING;
            $update_code = $event->updateEventForSchedulePublishing($delay);
           
            $job = new EventJob($groupid, $eventid);
            if (!empty($publish_date)){
                $job->delay = $delay;
            }
            $job->saveAsBatchCreateType(1,array());

            $redirect_to = "eventview?id=" . $_COMPANY->encodeId($eventid);
            AjaxResponse::SuccessAndExit_STRING(1, $redirect_to, gettext("Event saved successfully"), gettext('Success'));
        }

    } else {
        // Groupid cannot be changed for event, since this is update get the groupid from event
        $groupid = $event->val('groupid');
        $doWhat = intval($_POST['do_what'] ?? 1);

        if ($event->isPublished()) {
            // Once published event attendance cannot be changed or downgraded
            // However it can be upgraded, 1->3, 2->3, 4->1, 4->2 is valid. 1->2 or 2->1 or 3->1 or 3->2, 4->!4 is invalid
            if ($event_attendence_type == 1 && !in_array((int)$event->val('event_attendence_type'), array(1, 4))) {
                $event_attendence_type = (int)$event->val('event_attendence_type');
                $eventvanue = '';
                $vanueaddress = '';
                $max_inperson = 0;
                $max_inperson_waitlist = 0;
            } elseif ($event_attendence_type == 2 && !in_array((int)$event->val('event_attendence_type'), array(2, 4))) {
                $event_attendence_type = (int)$event->val('event_attendence_type');
                $web_conference_link = '';
                $web_conference_detail = '';
                $web_conference_sp = '';
                $max_online = 0;
                $max_online_waitlist = 0;
            } elseif ($event_attendence_type == 4 && !in_array((int)$event->val('event_attendence_type'), array(4))) {
                $event_attendence_type = (int)$event->val('event_attendence_type');
                $eventvanue = $event->val('eventvanue');
                $vanueaddress = $event->val('vanueaddress');
                $max_inperson = $event->val('max_inperson');
                $max_inperson_waitlist = $event->val('max_inperson_waitlist');
                $web_conference_link = $event->val('web_conference_link');
                $web_conference_detail = $event->val('web_conference_detail');
                $web_conference_sp = $event->val('web_conference_sp');
                $max_online = $event->val('max_online');
                $max_online_waitlist = $event->val('max_online_waitlist');
            }

            if (($event_attendence_type == 2 || $event_attendence_type == 3)
                && !filter_var($web_conference_link, FILTER_VALIDATE_URL) ) {
                AjaxResponse::SuccessAndExit_STRING(0,'', gettext("The Web Conference Link is invalid. Please update the Link and try again"), gettext('Error'));
            }

            // Once published checkin cannot be enabled or disabled
            $checkin_enabled = $event->val('checkin_enabled');
        }

        $venue_info = $event->val('venue_info');
        $venue_room = $event->val('venue_room');;

        $update  = $event->updateTeamEvent($chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $custom_fields_input, $event_contact, $add_photo_disclaimer, $venue_info, $venue_room, $isprivate, $calendar_blocks);
        // Send Email Update
        $job = new EventJob($groupid, $eventid);
        $job->saveAsBatchUpdateType(1,array(1,2),array());
        $redirect_to = "eventview?id=" . $_COMPANY->encodeId($eventid);
        AjaxResponse::SuccessAndExit_STRING(1, $redirect_to, gettext("Event updated successfully."), gettext('Success'));
    }
}

elseif (isset($_GET['saveTeamJoinRequestData']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($roletypeId =  $_COMPANY->decodeId($_POST['roletype'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $selectedChapters = $_POST['chapterids'] ?? 0;
    $chapterSelectionSetting = $group->getTeamRoleRequestChapterSelectionSetting();
    if ($chapterSelectionSetting['allow_chapter_selection']){
        if ($selectedChapters==0 && $group->val('chapter_assign_type') == 'by_user_atleast_one') {
            $msg = sprintf(gettext('You need to select one or more %1$s to join.'), $_COMPANY->getAppCustomization()['chapter']['name-short']);
            AjaxResponse::SuccessAndExit_STRING(0, '', $msg, gettext('Error'));
        } elseif ($selectedChapters==0 && $group->val('chapter_assign_type') == 'by_user_exactly_one') {
            $msg = sprintf(gettext('You need to select a %1$s to join.'),$_COMPANY->getAppCustomization()['chapter']['name-short']);
            AjaxResponse::SuccessAndExit_STRING(0, '', $msg, gettext('Error'));
        }
    }

    $request_capacity =  intval($_POST['request_capacity'] ?? 1);
    $responseJson = $_POST['responseJson'];

    $decodedChapterids = 0;
    if ( $selectedChapters !== 0){
        $decodedChapterids = implode(',',$_COMPANY->decodeIdsInArray($selectedChapters));
    }
    $retVal = Team::SaveTeamJoinRequestData($groupid, $roletypeId, $responseJson,$request_capacity,true,0, $decodedChapterids);
    if ($retVal) {
        if ($retVal == 2) { // If insert then add users to join group
            $_USER->joinGroup($groupid, $decodedChapterids, 0);
        }
        AjaxResponse::SuccessAndExit_STRING(1, $retVal,  gettext('Registration saved successfully'), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, $retVal, gettext('Something went wrong, please try again.'), gettext('Error'));
}
elseif (isset($_GET['createNewTeam']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES'] && $teamid == 0 &&  !Team::CanCreateCircleByRole($groupid,$_USER->id())) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('You are not allowed to create %s now!'),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Error'));
    }

    $chapterid = 0;
    if (!empty($_POST['chapterid'])){
        $chapterid = $_COMPANY->decodeId($_POST['chapterid']);
    }

    // Authorization Check
    if (!($teamid && $_USER->isProgramTeamMember($teamid)) && !$_USER->canCreateContentInScopeCSV($groupid,$chapterid, 0)){
        if ((!empty(Group::GetChapterList($groupid)) && !$chapterid)) {
            $contextScope = $_COMPANY->getAppCustomization()['channel']['name-short'];
            AjaxResponse::SuccessAndExit_STRING(-3, '', sprintf(gettext("Please select a %s scope."), $_COMPANY->getAppCustomization()['chapter']['name-short']), gettext('Error'));
        }
    }


    $team_name = $_POST['team_name'];
    $checkDuplicateTeam = Team::GetTeamByTeamName($groupid,$team_name);

    if ($checkDuplicateTeam){
        if (!$teamid  || ($teamid && $checkDuplicateTeam->val('teamid')!=$teamid)){
            AjaxResponse::SuccessAndExit_STRING(0,'', sprintf(gettext('%1$s already exists with "%2$s" name. Please update %1$s name to fix this error!'),Team::GetTeamCustomMetaName($group->getTeamProgramType()), $team_name), gettext('Error'));
        }
    }
    $team_description = ViewHelper::RedactorContentValidateAndCleanup($_POST['team_description']);

    $circleRolesCapacity = ViewHelper::ValidateCircleRolesCapacityInput();


    $handleids = '';
    $tagidsArray = array();
    if (isset($_POST['handleids'])){
        foreach($_POST['handleids'] as $handle){
         $tagidsArray[] = HashtagHandle::GetOrCreateHandle($handle);
        }
    }
    if (!empty($tagidsArray)){
        $handleIdsArray =
        $handleids = implode(',',array_column($tagidsArray,'hashtagid'));
    }

    if (!$teamid && $group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']) {
        // Init silent registration process of mentor sys role type
        $roleIdOfMentorType = Team::CanCreateCircleByRole($groupid, $_USER->id());
        $join_request_status = Team::CreateAutomaticJoinRequest($group, $_USER->id(), $roleIdOfMentorType);
        if (!$join_request_status['status']) {
            AjaxResponse::SuccessAndExit_STRING(-1, '', sprintf(gettext('Unable to start a %1$s. This might be due to role restrictions or other requests.'), Team::GetTeamCustomMetaName($group->getTeamProgramType())), '');
        }
    }

    $id = Team::CreateOrUpdateTeam($groupid, $teamid, $team_name, $chapterid,0,$team_description,$handleids);
    $team = null;

    if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']) {
        if ($teamid) {
            $id = $teamid;
        }
        $team = Team::GetTeam($id);
        $team->updateCircleMaxRolesCapacity($circleRolesCapacity);
    }


    if ($teamid > 0) {
        AjaxResponse::SuccessAndExit_STRING(1,array($group->getTeamProgramType(),$_COMPANY->encodeId($teamid)), sprintf(gettext("%s updated successfully"),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Success'));
    } else {
        if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']) {
            $roleid = Team::CanCreateCircleByRole($groupid,$_USER->id());
            if ($roleid){
                $team->addUpdateTeamMember($roleid, $_USER->id());
                $team->activate();
            }
        }

       AjaxResponse::SuccessAndExit_STRING(1,'', sprintf(gettext("%s created successfully"),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Success'));
    }
}

elseif(isset($_GET['joinAndSaveEventSurveyData']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        !isset($_POST['joinStatus'])
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canViewContent($event->val('groupid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	$joinstatus = (int)($_POST['joinStatus']); // joinstats: 1 for Accept, 2 for Tentative, 3 for Decline, 11 for in-person, 12 for waitlist, 21 for remote
    $sendrsvp = 1;

    $existingJoinStatus = $event->getMyRsvpStatus();
    $v = 1;
    if($existingJoinStatus != $joinstatus){
        $v =  $event->joinEvent($_USER->id(), $joinstatus, 1,1,0);   
    }

    if ($v == -3 || $v == -4) {
        if ($v == -3) {
            $msg = sprintf(gettext("This event is part of an event series with restricted attendance. You may only attend one event in this series. If you wish to join this event, you must first decline your RSVP for %s event in the series"), gettext('other'));
        } else {
            $msg = sprintf(gettext('You have already joined an event that has a schedule conflict with this event. If you wish to join this event, first update your RSVP for %s to decline to join.'), gettext('other'));
        }
        $eid = $event->val('event_series_id') ?: $event->id();
        AjaxResponse::SuccessAndExit_STRING(-100, 'eventview?id='.$_COMPANY->encodeId($eid), $msg, gettext('Error'));
    } else {

        $eventSurveyResponse = '';
        $trigger  = '';
        if (!empty($_POST['trigger']) && !empty($_POST['eventSurveyResponse'])){
            $eventSurveyResponse = $_POST['eventSurveyResponse'];
            $trigger = $_POST['trigger'];
        }
    
        if ( $trigger && $eventSurveyResponse){
            // RSVP is good, save the survey response.
            $survey_response = $event->updateEventSurveyResponse($_USER->id(),$trigger, $eventSurveyResponse);
            if ($survey_response < 0) {
                AjaxResponse::SuccessAndExit_STRING(-100, '', gettext('Unable to save your survey response at this time, please try again later'), gettext('Error'));
            }
        }
    }

    Points::HandleTrigger('EVENT_RSVP', [
        'eventId' => $event->id(),
        'userId' => $_USER->id(),
        'rsvpStatus' => $joinstatus,
    ]);

    $updatedEventData = [
        'eid' => $_COMPANY->encodeId($event->id()),
        'cid' => $_COMPANY->encodeId(0),
        'chid' => $_COMPANY->encodeId(0),
    ];

    if ($v == 0 || $v == -1) {
        AjaxResponse::SuccessAndExit_STRING(0, $updatedEventData, gettext("Your request to update failed. Please check your connection and try again later."), gettext('Error'));

    } elseif ($v == -2) {
        AjaxResponse::SuccessAndExit_STRING(0, $updatedEventData, gettext("Your RSVP was processed, but our system was unable to send a confirmation email. Please check that the email address entered in your profile is correct, and try again. If the error persists, it may be due to the server not processing the email, and, if so, reach out to IT for support."), gettext('Warning!'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Record saved successfully."), gettext('Success'));
    }
}

elseif(isset($_GET['updatePostEventSurveyResponses']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $survey_trigger = $_POST['survey_trigger'];
    if (!in_array($survey_trigger,Event::EVENT_SURVEY_TRIGGERS)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Survey not found!"), gettext('Error'));
    }
    $responseJson = $_POST['responseJson'];
    $survey_response = $event->updateEventSurveyResponse($_USER->id(), $survey_trigger, $responseJson);
    if ($survey_response < 0) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Unable to save your survey response at this time, please try again later'), gettext('Error'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(1, array($_COMPANY->encodeId(0), $_COMPANY->encodeId(0)), gettext("Post-event survey responses saved successfully"), gettext('Success'));
    }
}
elseif (isset($_GET['getAvailableSlots'])){
    if (
        ($date = $_GET['date']) == '' ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null 
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $selectedTimezone = "UTC";
    if (isset($_GET['timezone'])
        && isValidTimeZone($_GET['timezone'])
    ) {
        $selectedTimezone = $_GET['timezone'];
    } 
    $eventStartDate = $_GET['eventStartDate'];
    $eventDateObj = null;
    if ($eventStartDate){ // Convert event start date to selected timezone
        $eventDateObj = Date::ConvertDatetimeTimezone($eventStartDate,"UTC", $selectedTimezone);
    }

    $availableSlots = array();
    $mentors  = $team->getTeamMembersBasedOnSysRoleid(2);
    $rsvpedEventsDateTime = array();

    if (!empty($mentors)) {
        $mentorUserid = $mentors[0]['userid'];
        $timeSchedules = UserSchedule::GetAllUsersSchedules($mentorUserid,true,'team_event', $team->val('groupid'));

       
       
        foreach ($timeSchedules as  $timeSchedule) {
            $sourceDateTimeRanage = array();
            foreach (Date::GetDatesFromRange($timeSchedule['start_date_in_user_tz'], $timeSchedule['end_date_in_user_tz'], 'Y-m-d') as $dt) {
                $weekDay = date('w', strtotime($dt));
                $weekAvailableTimes = $timeSchedule['weekly_available_time'];
                
                foreach($weekAvailableTimes as $timeSlot) {
                    if ($timeSlot['day_of_week'] == $weekDay) {
                        $sourceDateTimeRanage[] = array($dt.' '.$timeSlot['daily_start_time_in_user_tz'], $dt.' '.$timeSlot['daily_end_time_in_user_tz']);
                    }
                }
            }
            // GET all the slots
            $availableSlots = array_merge($availableSlots, UserSchedule::GenerateCrossTimezoneTimeSlots($sourceDateTimeRanage, $date,$timeSchedule['schedule_slot'],$timeSchedule['user_tz'],$selectedTimezone));
        }
       

        // Get Mentors RSVPed event for selected date
        $rsvpedEvents = Event::GetJoinedEventsByDate($mentorUserid, $date);
        foreach ($rsvpedEvents as $evt) { // Covert event date to selected timezone 
            $rsvpedEventsDateTime[] = Date::ConvertDatetimeTimezone($evt['start'],'UTC', $selectedTimezone);
        }
    }
    $slotHtml = array();
    $i=0;
    $nowDateObj = Date::ConvertDatetimeTimezone(date("Y-m-d H:i:s"),$selectedTimezone, $selectedTimezone);
    $durationInMinutes = array();
    foreach ($availableSlots as $slot) {
        $slotDateObj = Date::ConvertDatetimeTimezone($slot["date"].' '.$slot["start24"],$selectedTimezone, $selectedTimezone);
        // Hide if the time slot is past now
        if ($nowDateObj > $slotDateObj) {
            continue;
        }
        // Hide slot if mentor already rsvped save slot event
        if ($eventDateObj != $slotDateObj && Date::IsDateTimeInArray($slotDateObj, $rsvpedEventsDateTime)) { 
            continue;
        }
        $checked = "";
        if ($eventDateObj) {
            if ($eventDateObj == $slotDateObj) {
                $checked = "checked";
            }
        }
        if (!in_array($slot["duration"]['minutes'], $durationInMinutes)) {
            $durationInMinutes[] = $slot["duration"]['minutes'];
        }
        
        $slotHtml[] = '<div class="time-slot col-6 p-2" role="radiogroup" aria-labelledby="slot-label">
            <input type="radio" name="time_slot" id="slot'.$i.'" data-duration="'.$slot['duration']['minutes'].'" value="'.$slot["date"].' '.$slot["start24"].'" '.$checked.'>
            <label class="d-block text-small text-center"  for="slot'.$i.'">'.$slot["start12"].'</label>
        </div>';
        $i++;
    }

   
      
    if (!empty($slotHtml)){
        $durationInMinutes = array_map(function($value) {
            return $value . " minutes";
        }, $durationInMinutes);
        echo '<div class="col-12"><p class="control-label">'.gettext("Available Time Slots"). '<br><span style="background-color: #fadeab; padding-left: 3px; padding-right: 3px;">' .implode(' / ', $durationInMinutes) . ' ' . gettext("duration") .'</span></p></div>'.implode(" ", $slotHtml);
    } else {
        echo '<div class="col-12"><p class="control-label text-center py-5 red">'.gettext("No time slots available for selected date").'</p></div>';
    }
}

elseif(isset($_GET['addOrUpdateTeamScheduleEvent']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    $event = null;
    $collaborate = '';
    $chapterids = '0';
    $channelid = 0;
    $regionid = 0;
    $isprivate = 0;
    $seriesEvent = null;
    $add_photo_disclaimer = 0;
    $event_series_id = 0;
    $touchpointid = 0;

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($eventid = $_COMPANY->decodeId($_POST['eventid'])) < 0 ||
        ($touchpointid = $_COMPANY->decodeId($_POST['touchpointid'])) < 0 ||
        (isset($_POST['timezone'])
            && !isValidTimeZone($_POST['timezone'])
        )
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($eventid && ($event=TeamEvent::GetEvent($eventid)) === null){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($event){
        $version = (int) $_COMPANY->decodeId($_POST['version']);
        if ($event->val('version') >$version){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("You are not editing the latest version and your changes cannot be saved. In order to not lose your work, please copy your changes locally, re-edit the event, apply your changes, and try to save again."), gettext('Error'));
        }
    }

    // Authorization Check
    if (!$_USER->isProgramTeamMember($teamid) && !$_USER->canManageGroup($groupid)
     ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $eventtitle = $_POST['eventtitle'];
    $eventtype = 0; 
    $event_description = ViewHelper::RedactorContentValidateAndCleanup( $_POST['event_description']);
    $event_attendence_type = 2;// default Virtual (Web Conference) only
    $event_contact = '';
    $eventvanue = '';
    $vanueaddress = '';
    $venue_info = '';
    $venue_room = '';
    $web_conference_link = '';
    $web_conference_detail = '';
    $web_conference_sp = '';
    $latitude = '';
    $longitude = '';
    $multiDayEvent = 0;
    $invited_groups = '';
    $checkin_enabled = intval($_COMPANY->getAppCustomization()['event']['checkin'] && $_COMPANY->getAppCustomization()['event']['checkin_default']);;
    $check = array('Event Name' => @$eventtitle,'Event time Slot' => @$_POST['time_slot']);
   
    $checkrequired = $db->checkRequired($check);

    if ($checkrequired) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$checkrequired), gettext('Error'));
    }

    $max_inperson = 0;
    $max_inperson_waitlist = 0;
    $max_online = 0;
    $max_online_waitlist = 0;

    #Time zone
    $event_tz = isset($_POST['timezone']) ? $_POST['timezone'] : (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
    #Event Start Date time
    $time_slot = $_POST['time_slot'];
    $startDatetimeObj = Date::ConvertDatetimeTimezone($time_slot,$event_tz, "UTC");
    $start = $startDatetimeObj->format("Y-m-d H:i:s");
    $hour_duration = 0;
    $minutes_duration =  15; // Default
    $mentors  = $team->getTeamMembersBasedOnSysRoleid(2);
    if (!empty($mentors)) {
        $mentorUserid = $mentors[0]['userid'];
        $schedule = UserSchedule::GetScheduleByDate($mentorUserid, $startDatetimeObj->format("Y-m-d"));
        if ($schedule) {
            [$hour_duration, $minutes_duration] = Date::ConvertMinutesToHoursMinutes($schedule->val('schedule_slot'));
            $web_conference_link = $schedule->val('user_meeting_link');
            $web_conference_sp = "Meeting Link";
        }
    }
   
    #Event End Date time
    $endDatetiimeObj = Date::IncrementDatetime($start, "UTC", $hour_duration, $minutes_duration);
    $end = $endDatetiimeObj->format("Y-m-d H:i:s");
    $invited_locations = 0;
    // Custom Fields
    $custom_fields_input = json_encode(array());
    $budgeted_amount = 0;
    $calendar_blocks = 1;

    if (!$eventid) {
        $eventid = TeamEvent::CreateNewTeamEvent($teamid, $groupid, $chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $event_series_id, $custom_fields_input, $event_contact, $venue_info, $venue_room, $isprivate, 'teamevent', $add_photo_disclaimer, 1);
        if ($eventid) {
            if (!$touchpointid){ // Create and link Touch point
                $touchpointid = $team->addOrUpdateTeamTask(0, $eventtitle, '0', $start, '', 'touchpoint', 0);
            }
            $team->linkTouchpointEventId($touchpointid,$eventid,$end);
            
            $event = TeamEvent::GetEvent($eventid);
            // Add Event Members to prevent users to schedule same slot again.
            $teamMembers = $team->getTeamMembers(0);
            foreach($teamMembers as $member) {
                $event->joinEvent($member['userid'],1,1,0);
            }
            // Publish Email Update
            $delay = 15;
           
            $isactive = Event::STATUS_AWAITING;
            $update_code = $event->updateEventForSchedulePublishing($delay);

            $job = new EventJob($groupid, $eventid);
            if (!empty($publish_date)){
                $job->delay = $delay;
            }
            $job->saveAsBatchCreateType(1,array());

            $redirect_to = "eventview?id=" . $_COMPANY->encodeId($eventid);
            AjaxResponse::SuccessAndExit_STRING(1, $redirect_to, gettext("Event saved successfully"), gettext('Success'));
        }

    } else {
        // Groupid cannot be changed for event, since this is update get the groupid from event
        $groupid = $event->val('groupid');
        $update  = $event->updateTeamEvent($chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $custom_fields_input, $event_contact, $add_photo_disclaimer, $venue_info, $venue_room, $isprivate, $calendar_blocks);
        $team->linkTouchpointEventId($touchpointid,$eventid,$end);
        // Send Email Update
        $job = new EventJob($groupid, $eventid);
        $job->saveAsBatchUpdateType(1,array(1,2),array());
        $redirect_to = "eventview?id=" . $_COMPANY->encodeId($eventid);
        AjaxResponse::SuccessAndExit_STRING(1, $redirect_to, gettext("Event updated successfully."), gettext('Success'));
    }
}


else {
    Logger::Log("Nothing to do ... ");
}
exit();
