<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__.'/head.php';

/* @var Company $_COMPANY */
/* @var User $_USER */

###### All Ajax Calls Regarding Discussions ##########

// GET Discussions

if (isset($_GET['getGroupDiscussions'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $showGlobalChapterOnly = intval($_SESSION['showGlobalChapterOnly'] ?? 0);
    $showGlobalChannelOnly = intval($_SESSION['showGlobalChannelOnly'] ?? 0);
    $chapterid = 0;
    $channelid = 0;
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        (empty($_GET['chapterid']) || ($chapterid = $_COMPANY->decodeId($_GET['chapterid'])) < 0) ||
        (empty($_GET['channelid']) ||  ($channelid = $_COMPANY->decodeId($_GET['channelid'])) < 0)
    ) {
        header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
        exit();
    }

    // Authorization Check
    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $page = 1;
    $limit = 10;

    $discussions = ViewHelper::GetDiscussionsViewData($showGlobalChapterOnly, $chapterid, $showGlobalChannelOnly, $channelid,$groupid, $page, $limit);
    $url_chapter_channel_suffix = '';
    if ($chapterid) {
        $url_chapter_channel_suffix .= '&chapterid='.$_COMPANY->encodeId($chapterid);
    }
    if ($channelid) {
        $url_chapter_channel_suffix .= '&channelid='.$_COMPANY->encodeId($channelid);
    }

    $max_iter = count($discussions);
    $show_more = ($max_iter > $limit);
    $max_iter = $show_more ? $limit : $max_iter;
    $discussionSettings = $group->getDiscussionsConfiguration();
	include(__DIR__ . '/views/discussions/discussion_home.template.php');
}

elseif (isset($_GET['manageGroupDiscussions']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $enc_groupid=$_COMPANY->encodeId(0);
    if ($_GET['manageGroupDiscussions']){
        if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 || ($group = Group::GetGroup($groupid)) === null) {
            header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
            exit();
        }
        $enc_groupid=$_COMPANY->encodeId($groupid);
    } else {
        $groupid = 0;
    }

    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

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
    if (!empty($_GET['erg_filter_section']) && $_GET['erg_filter_section'] !== 'undefined'){ // Todo: Fix 'undefined' usecase at javascript level
        $erg_filter_section = $_COMPANY->decodeId($_GET['erg_filter_section']);
    }


  	include(__DIR__ . '/views/discussions/manage/manage_group_discussion.template.php');
    exit();
}

elseif (isset($_GET['getDiscussionsList']) && $_SERVER['REQUEST_METHOD'] === 'POST' ){

    if (($groupid = $_COMPANY->decodeId($_GET['getDiscussionsList']))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        (isset($_GET['isactive']) && ($isactive = $_COMPANY->decodeId($_GET['isactive'])) < 1) ||
        (isset($_GET['year']) && ($year = $_COMPANY->decodeId($_GET['year'])) < 1)
    ) {
        header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
        exit();
    }
    // Authorization Check
    if ($groupid){
        if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } else {
        if (!$_USER->isAdmin()) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }
    $groupStateId  = 0;
    $groupStateType = 0;
    if(!empty($_GET['groupStateType'])){
        $groupStateType = (int) $_COMPANY->decodeId($_GET['groupStateType']);
    }
    if(!empty($_GET['groupState']) && ($groupStateId = $_COMPANY->decodeId($_GET['groupState'])) <0){
        header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
        exit();
    }
    $input = array('search'=>raw2clean($_POST['search']['value']),'start'=>(int)$_POST['start'],'length'=>(int)$_POST['length'],'draw'=>(int)$_POST['draw']);

    $isactiveCondition = " AND a.isactive='".Post::STATUS_ACTIVE."'";

    $dateField = 'a.modifiedon';
    if ($isactive =='1'){ // Only Active
        $isactiveCondition = " AND a.isactive='".Post::STATUS_ACTIVE."'";
    } elseif ($isactive =='2'){ // Draft
        $isactiveCondition = " AND a.isactive= '".Post::STATUS_DRAFT."'";
    }

    $orderFields = ['a.title',$dateField,'b.firstname'];
    if ($groupid){
        $orderFields = ['a.title','a.title','ch.channelname',$dateField,'b.firstname'];
    }

    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
   
    $search = "";
    if ($input['search']){
        $search = " AND (a.title LIKE '%".$input['search']."%' OR $dateField LIKE '%".$input['search']."%' OR b.firstname LIKE '%".$input['search']."%' OR b.lastname LIKE '%".$input['search']."%' OR b.email LIKE '%".$input['search']."%' OR ch.channelname LIKE '%".$input['search']."%')";
    }

    $groupFilter = "";
    if ($groupStateType){
        if ($groupStateType === 1) { // Chapter filter
            $groupFilter = " AND FIND_IN_SET({$groupStateId}, a.chapterid)";
        } else if ($groupStateType === 2){
            $groupFilter = "AND ch.channelid= '".$groupStateId."'";;
        }
    }

    if ($year > date('Y')){
        $year = date('Y');
    }

    // -- TODO ---
    // Datatable Dependencies
    // Will replaced by Tabulator 
    $totalrows =  $db->get("SELECT count(1) as totalRows FROM discussions a JOIN users b ON b.userid=a.createdby LEFT JOIN `groups` as g ON g.groupid=a.groupid LEFT JOIN group_channels ch ON ch.channelid=a.channelid WHERE a.companyid='{$_COMPANY->id()}' AND ( a.zoneid='{$_ZONE->id()}' AND a.`groupid`={$groupid} $isactiveCondition AND YEAR($dateField)=".$year."  $search $groupFilter)")[0]['totalRows'];
    $rows = $db->get("SELECT a.*,b.firstname,b.lastname,b.jobtitle,b.picture,b.email,b.userid,IFNULL(g.groupname,'Global') as groupname, IFNULL((SELECT GROUP_CONCAT(DISTINCT`chaptername` SEPARATOR '^') FROM `chapters` WHERE FIND_IN_SET(`chapterid`,a.chapterid)),'') as chaptername,ch.channelname FROM discussions a LEFT JOIN users b ON b.userid=a.createdby LEFT JOIN `groups` as g ON g.groupid=a.groupid LEFT JOIN group_channels ch ON ch.channelid=a.channelid WHERE a.companyid='{$_COMPANY->id()}' AND ( a.zoneid='{$_ZONE->id()}' AND a.`groupid`={$groupid} $isactiveCondition AND YEAR($dateField)=".$year."  $search $groupFilter) ORDER BY $orderFields[$orderIndex] $orderDir limit ".$input['start'].",".$input['length']." ");
    $final = [];
    $i=0;
    foreach($rows as $row){  
        $encPostid = $_COMPANY->encodeId($row['discussionid']);
        $discussionTitle = '<a href="viewdiscussion?id='.$encPostid.'" >';
        if ($row['isactive'] == Post::STATUS_DRAFT ) {
            $discussionTitle .= '<span style="text-align:justify;color:red;">';
            $discussionTitle .= $row['title'].'&nbsp';
            $discussionTitle .= '<img src="img/draft_ribbon.png" alt="Draft icon image" height="16px"/>';
            $discussionTitle .= '</span>';
         } else {
            $discussionTitle .=  '<span style="text-align:justify;">';
            $discussionTitle .= $row['title'];
            $discussionTitle .= '</span>';
        } 
        $discussionTitle .= '</a>';
        if ($row['anonymous_post'] == 1){
            $profilepic = User::BuildProfilePictureImgTag("Anonymous","User",'','memberpic2', 'User Profile Picture');
            $profilepic .= '<br/> '. gettext("Anonymous");
        } else {
            $profilepic = User::BuildProfilePictureImgTag($row['firstname'],$row['lastname'], $row['picture'],'memberpic2', 'User Profile Picture', $row['userid'], 'profile_basic');
            $profilepic .= '<br/>'.$row['firstname'].' '.$row['lastname'];
        }

        $actionButton = '<div class="" style="color: #fff; float: left;">';
        $actionButton .= '<button aria-label="'.sprintf(gettext('%1$s Discussion action dropdown'), $row['title']).'" id="'.$encPostid.'" onclick="getDiscussionActionButton(\''.$encPostid.'\')" class="btn-no-style dropdown-toggle fa fa-ellipsis-v mt-3 title="Action" type="button" data-toggle="dropdown"></button>';
        $actionButton .= '<ul class="dropdown-menu dropdown-menu-right" id="dynamicActionButton'.$encPostid.'" style="width: 250px; cursor: pointer;">';
        $actionButton .= '</ul>';
        $actionButton .= '</div>';

        $scope = "";
        if ($row['chaptername']){
            $scope .= '<p>'.$_COMPANY->getAppCustomization()['chapter']['name-short-plural'].'</p>';
            $chapters = explode('^',$row['chaptername']);
            foreach ($chapters as $ch1){
                $scope .= '<li>'.$ch1.'</li>';
            }
        }
        if ($row['channelname']){
            $scope .= '<p>'.$_COMPANY->getAppCustomization()['channel']['name-short']."<p>";        
            $scope .= '<li>'.htmlspecialchars($row['channelname'] ? $row['channelname'] : '').'</li>';
        }
        $scope = $scope ?: '-';
        $discussionRow = array();
        $discussionRow[] = $discussionTitle;
        if ($groupid) {
            $discussionRow[] = $scope;           
        }
        $discussionRow[] = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['modifiedon'],true,true,true);        
        $discussionRow[] = $profilepic;
        $discussionRow[] = $actionButton;

        $final[] = array_merge(array("DT_RowId" => $row['discussionid']), array_values($discussionRow));
        
        $i++;
    }
    $json_data = array(
                    "draw"=> intval( $input['draw'] ), 
                    "recordsTotal"    => intval($totalrows),
                    "recordsFiltered" => intval($totalrows), 
                    "data"            => $final
                );

    echo json_encode($json_data);

}

elseif (isset($_GET['getDiscussionActionButton']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($discussionid = $_COMPANY->decodeId($_GET['discussionid']))<0 ||
        ($discussion = Discussion::GetDiscussion($discussionid)) === NULL  ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $chapterids = empty($discussion->val('chapterid')) ? array(0) : explode(',', $discussion->val('chapterid'));
    $enc_groupid = $_COMPANY->encodeId($discussion->val('groupid'));

    include(__DIR__ . "/views/discussions/discussion_action_button.template.php");
}

elseif (isset($_GET['openCreateDiscussionModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($discussionid = $_COMPANY->decodeId($_GET['discussionid']))<0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    // Allow anyone with create permissions to see this form.
    $discussion = null;
    if ($discussionid){
        $discussion = Discussion::GetDiscussion($discussionid);

        if ($_USER->id()!=$discussion->val('createdby') && !$_USER->canUpdateContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid'),$discussion->val('isactive'))) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } else {
        $discussionSettings = $group->getDiscussionsConfiguration();
        if ($discussionSettings && !($discussionSettings['who_can_post']=='members' && $_USER->isGroupMember($groupid)) && !$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }
    $global = 0;
    $chapters = array();
    $channels = array();
    $selectedChapterIds = array();
    $selectedChannelId = 0;

    $formTitle = gettext("New Discussion");
    $submitButton = gettext("Submit");
    $selectedChapterIds = array();
    if ($discussionid){
        $formTitle = gettext("Update Discussion");
        $submitButton = gettext("Submit Update");
        $selectedChapterIds = explode(',',$discussion->val('chapterid'));
        $selectedChannelId = $discussion->val('channelid');

    }
    if($_COMPANY->getAppCustomization()['chapter']['enabled']){
        $chapters = Group::GetChapterListByRegionalHierachies($groupid);
    }
    if($_COMPANY->getAppCustomization()['channel']['enabled']){
        $channels= Group::GetChannelList($groupid);
    }
    $displayStyle = 'row';
    $fontColors = $groupid > 0 ? json_encode(array($group->val('overlaycolor'),$group->val('overlaycolor2'))) : json_encode(array());
    $discussionSettings = $group->getDiscussionsConfiguration();
	include(__DIR__ . "/views/discussions/new_discussion.template.php");
}

## Submit New/Update Discussion
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

        //ViewHelper::ValidateObjectVersionMatchesWithPostAttribute($discussionObj);

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
    $discussion = ViewHelper::RedactorContentValidateAndCleanup($_POST['discussion']);
    $anonymous_post = empty($_POST['anonymous_post']) ? 0 : 1;

    //Data Validation
	$check = $db->checkRequired(array('Title'=>$title,'Description'=>$discussion));
	if($check){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty!"),$check), gettext('Error!'));
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

        if ($discussionObj) {
            $discussionObj->postUpdate();
        } else {
            $discussionObj = Discussion::GetDiscussion($discussionid);
            $discussionObj->postCreate();
        }

        $_SESSION['show_discussion_id'] = $discussionid;
        AjaxResponse::SuccessAndExit_STRING(1, '', $message, gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
    }
}

## OK
## Delete Discussion
elseif (isset($_GET['deleteDiscussion']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (!isset($_POST['discussionid']) ||
        ($id = $_COMPANY->decodeId($_POST['discussionid']))<1 ||
        (NULL === ($discussion = Discussion::GetDiscussion($id)))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (
        $_USER->id()!=$discussion->val('createdby') &&
        !$_USER->canUpdateContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid'),$discussion->val('isactive')) &&
        !$_USER->canPublishContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid'))
    ) { //Allow creators to edit unpublished content
        header(HTTP_FORBIDDEN);
        exit();
    }
    $encGroupid = $_COMPANY->encodeId($discussion->val('groupid'));
    if($discussion->deleteIt()){
        AjaxResponse::SuccessAndExit_STRING(1, array('groupid'=>$encGroupid,'chapterid'=>$_COMPANY->encodeId(0),'channelid'=>$_COMPANY->encodeId(0)), gettext("Deleted Successfully."), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
    }
}

elseif(isset($_GET['filterDiscussions']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (($groupid = $_COMPANY->decodeId($_GET['filterDiscussions']))<0 )
    {
        header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
        exit();
    }
    $enc_groupid=$_COMPANY->encodeId($groupid);
  	include(__DIR__ . '/views/discussions/manage/group_discussions_table_view.template.php');
}

elseif (isset($_GET['pinUnpinDiscussion']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($discussionid = $_COMPANY->decodeId($_POST['discussionid'])) < 1 ||
        (($discussion = Discussion::GetDiscussion($discussionid))  === NULL) ||
        !$_COMPANY->getAppCustomization()['discussions']['pinning']['enabled']
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $enc_groupid = $_COMPANY->encodeId($groupid);
    $enc_discussionid = $_COMPANY->encodeId($discussionid);
    $type = $_POST['type'] == 2 ? $_POST['type'] : 1; 
  
    // Authorization Check
    if (!$_USER->canPublishContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'), $discussion->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    
    if ($discussion->pinUnpinDiscussion($type)){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Updated successfully."), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Error!'));
    }
}

elseif (isset($_GET['loadMoreDiscussion'])){
    $showGlobalChapterOnly = intval($_SESSION['showGlobalChapterOnly'] ?? 0);
    $showGlobalChannelOnly = intval($_SESSION['showGlobalChannelOnly'] ?? 0);
    //Data Validation
    $chapterid = 0;
    $channelid = 0;
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        (isset($_GET['chapterid']) && ($chapterid = $_COMPANY->decodeId($_GET['chapterid'])) < 0) ||
        (isset($_GET['channelid']) && ($channelid = $_COMPANY->decodeId($_GET['channelid'])) < 0)
    ) {
        header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
        exit();
    }

    // Authorization Check
    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $page = 1;
    if (isset($_GET['page']) && (int)$_GET['page'] > 0) {
        $page = (int)$_GET['page'];
    }
    $limit = 10;

    $discussions = ViewHelper::GetDiscussionsViewData($showGlobalChapterOnly, $chapterid, $showGlobalChannelOnly, $channelid, $groupid, $page, $limit);

    $url_chapter_channel_suffix = '';
    if ($chapterid) {
        $url_chapter_channel_suffix .= '&chapterid='.$_COMPANY->encodeId($chapterid);
    }
    if ($channelid) {
        $url_chapter_channel_suffix .= '&channelid='.$_COMPANY->encodeId($channelid);
    }

    $max_iter = count($discussions);
    $show_more = ($max_iter > $limit);
    $max_iter = $show_more ? $limit : $max_iter;
	include(__DIR__ . '/views/discussions/discussion_rows.template.php');
}

elseif (isset($_GET['getDiscussionDetailOnModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($discussionid = $_COMPANY->decodeId($_GET['discussionid'])) < 1 ||
        ($discussion = Discussion::GetDiscussion($discussionid))  === NULL ||
        ($filterchapterid = $_COMPANY->decodeId($_GET['chapterid']))<0 ||
        ($filterchannelid = $_COMPANY->decodeId($_GET['channelid']))<0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->canViewContent($discussion->val('groupid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $group = NULL;
    $chapters = NULL;
    $channels = NULL;
    $groupid = 0;
    $chapterid = 0;
    $channelid = 0;

    $groupid = $discussion->val('groupid');
    if (isset($_GET['chapterid'])){
        $chapterid = $_COMPANY->decodeId($_GET['chapterid']);
    }
    if (isset($_GET['channelid'])){
        $channelid = $_COMPANY->decodeId($_GET['channelid']);
    }
    $enc_chapterid   = $_COMPANY->encodeId($chapterid);
    $enc_channelid    = $_COMPANY->encodeId($channelid);

    $groupid = $discussion->val('groupid');
    $enc_groupid = $_COMPANY->encodeId($groupid);
    $enc_postid = $_COMPANY->encodeId($discussionid);

    $selected_chapter = null;
    $selected_channel = null;
    if ($groupid) {
        $group = Group::GetGroup($groupid);

        // Set selected_chapter, selected_channel for the dropdown
        if ($chapterid) {
            $selected_chapter = $group->getChapter($chapterid);
        }
        if ($channelid) {
            $selected_channel = $group->getChannel($channelid);
        }

        $chapterid = $discussion->val('chapterid'); //Use the chapter set in the post
        $channelid = $discussion->val('channelid');
        $chapters = Group::GetChapterList($groupid);
        $channels= Group::GetChannelList($groupid);
    }
    $form_title = gettext("Discussion Link");
    $link = $_COMPANY->getAppURL($_ZONE->val('app_type')).'viewdiscussion?id='.$_COMPANY->encodeId($discussionid);

    // unsetting session of discussion id in global $_SESSION to avoid wrong load of detailmodal when discussion is edited from feed
    if($_GET['unsetId']==1){
        unset($_SESSION['show_discussion_id']);
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
     * $sectionHeading (optional )
    */
    $comments = $discussion->val('anonymous_post') ? Discussion::GetCommentsAnonymized_2($discussionid) : Discussion::GetComments_2($discussionid);
    $topicid = $discussionid;
    $commentid = 0;
    $disableAddEditComment = false;
    $submitCommentMethod = "DiscussionComment";
    $mediaUploadAllowed = $_COMPANY->getAppCustomization()['discussions']['enable_media_upload_on_comment'];
    $sectionHeading = gettext('Response');

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
    $myLikeType = Discussion::GetUserReactionType($topicid);
    $myLikeStatus = (int) !empty($myLikeType);
    $latestLikers = $discussion->val('anonymous_post') ? Discussion::GetLatestLikersAnonymized($topicid) : Discussion::GetLatestLikers($topicid);
    $totalLikers = Discussion::GetLikeTotals($topicid);
    $likeTotalsByType = Discussion::GetLikeTotalsByType($topicid);
    $showAllLikers = true;
    $likeUnlikeMethod = 'DiscussionTopic';
        
    include(__DIR__ . "/views/templates/discussion_detail_modal.template.php");
}


else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}

