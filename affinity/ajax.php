<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__ .'/head.php'; //Common head.php includes destroying Session for timezone so defining INDEX_PAGE. Need head for logging and protecting

global $_COMPANY;
global $_USER;
global $_ZONE;
global $db;

/* @var Company $_COMPANY */
/* @var User $_USER */

###### All Ajax Calls ##########

###### AJAX Calls ######
##### Should be in if-elseif-else #####

## OK
## Join Group
if (isset($_GET['joinGroup']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['joinGroup']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ((int)$group->val('group_type') != Group::GROUP_TYPE_OPEN_MEMBERSHIP)) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

	if ($_USER->joinGroup($groupid,0,0)) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('You have successfully joined!'), gettext('Success'));
	} else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Internal server error, please try after some time.'), gettext('Error'));
    }
}

## OK
## Leave Group
elseif (isset($_GET['leaveGroup']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    // Authorization Check
    // Not needed

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['leaveGroup']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

	if ($_USER->leaveGroup($groupid, 0, 0)) {
		AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Left successfully.'), gettext('Success'));
	} else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Internal server error, please try after some time.'), gettext('Error'));
    }
}

## OK
## Join Group
elseif (isset($_GET['joinGroupAndAutoAssignChapter']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    // Authorization Check
    // Not needed

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['joinGroupAndAutoAssignChapter']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ((int)$group->val('group_type') != Group::GROUP_TYPE_OPEN_MEMBERSHIP) ||
        ((int)$group->val('chapter_assign_type') !== 'auto')) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $all_chapter= Group::GetChapterList($groupid);
    $autoAssign = array();

    if (count($all_chapter)>0 && $_USER->val('homeoffice') > 0) {
        foreach ($all_chapter as $chapter) {
            $branchids = explode(',', $chapter['branchids']);
            if (in_array($_USER->val('homeoffice'), $branchids)) {
                $_USER->joinGroup($groupid, (int)$chapter['chapterid'],0);
                $autoAssign[] = $chapter['chaptername'];
            }
        }
        if (count($autoAssign) === 0) {
            $_USER->joinGroup($groupid, 0, 0); // No matching chapter was found, so assign default group chapter
            AjaxResponse::SuccessAndExit_STRING(2, '', sprintf(gettext('There is not a %1$s that matches your work location. You will be added to the global %2$s by default.'),$_COMPANY->getAppCustomization()['chapter']['name-short'],$_COMPANY->getAppCustomization()['group']['name-short']), sprintf(gettext('Join %s'),$_COMPANY->getAppCustomization()['chapter']['name-short']));
            
        } else {
            $m = (implode(', ', $autoAssign). ' '.$_COMPANY->getAppCustomization()['chapter']['name-short']);
            AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext('You have been assigned %s'),$m), gettext('Join Chapter'));
        }
    } elseif ($_USER->joinGroup($groupid, 0, 0)) { // Join the Group
        AjaxResponse::SuccessAndExit_STRING(2, '', sprintf(gettext('There is not a %1$s that matches your work location. You will be added to the global %2$s by default.'),$_COMPANY->getAppCustomization()['chapter']['name-short'],$_COMPANY->getAppCustomization()['group']['name-short']), sprintf(gettext('Join %s'),$_COMPANY->getAppCustomization()['chapter']['name-short']));
    } else{
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Internal server error, please try after some time.'), gettext('Error'));
    }
}
## Get Members List View
elseif (isset($_GET['getGroupMembersList'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getGroupMembersList']))<1 ||
        ($group = Group::GetGroup($groupid)) == null ||
        ($cid = $_COMPANY->decodeId($_GET['chapter']))<0 ||
        ($cid && isset($_GET['section']) &&  // If cid is set then section should be either 1 or 2
            (($section = $_COMPANY->decodeId($_GET['section'])) < 1 || $section > 2)) // Valid values are 1 or 2
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);

    $filter = "";
    $chapter_id = 0;
    $channel_id = 0;
    if ($cid > 0) {
        if ($section === 2) {
            $channel_id = $cid;
            $filter = " AND FIND_IN_SET(". $cid.", a.channelids)";
            if (!$_USER->canManageContentInScopeCSV($groupid, 0,$cid)) {
                header(HTTP_FORBIDDEN);
                exit();
            }
        } else {
            $chapter_id = $cid;
            $filter = " AND FIND_IN_SET(" . $cid . ", a.chapterid)";

        }
    }
    if (!$_USER->canManageContentInScopeCSV($groupid, $chapter_id, $channel_id)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $input = array('search'=>raw2clean($_POST['search']['value']),'start'=>(int)$_POST['start'],'length'=>(int)$_POST['length'],'draw'=>(int)$_POST['draw']);

    $orderFields = ['b.firstname','b.email','a.groupjoindate'];
    $position = 1;
    if ($_COMPANY->getAppCustomization()['chapter']['enabled']){
        array_splice($orderFields, $position, 0, '');
        $position++;
    }

    if ($_COMPANY->getAppCustomization()['channel']['enabled']){ 
        array_splice($orderFields, $position, 0, '');
    }

    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
   
    $search = "";
    if ($input['search']){
        $searchKeyWord = trim ($input['search']);
        if (strlen($searchKeyWord) > 2){
            $keyword_list = explode(' ',$searchKeyWord);
            if(count($keyword_list) == 2){
                $like_keyword1 = $keyword_list[0]. '%'; 
                $like_keyword2 = '%' . $keyword_list[1] . '%';
                $search = " AND ((b.firstname LIKE '".$like_keyword1."' OR b.lastname LIKE '".$like_keyword1."') AND (b.firstname LIKE '".$like_keyword2."' OR b.lastname LIKE '".$like_keyword2."'))";
            }else{
                $like_keyword = $keyword_list[0] . '%';
                $search = " AND (b.firstname LIKE '".$like_keyword."' OR b.lastname LIKE '".$like_keyword."' OR b.email LIKE '".$like_keyword."') ";                
            }
        }        
    }
	$totalrows 	= count($db->get("SELECT a.memberid,b.firstname,b.lastname,b.jobtitle,b.picture,b.email,b.external_email FROM `groupmembers` a JOIN users b ON b.userid=a.userid WHERE b.companyid='{$_COMPANY->id()}' AND ( a.`groupid`='".$groupid."' AND a.`isactive`='1' AND b.`isactive`='1' AND a.`anonymous`!='1' $filter $search )"));
    
    $members= $db->get("SELECT a.*,b.firstname,b.lastname,b.jobtitle,b.picture,b.email,b.external_email FROM `groupmembers` a JOIN users b ON b.userid=a.userid WHERE b.companyid='{$_COMPANY->id()}' AND (a.`groupid`='".$groupid."' AND a.`isactive`='1' AND b.`isactive`='1' AND a.`anonymous`!='1' $filter $search) ORDER BY $orderFields[$orderIndex] $orderDir limit ".$input['start'].",".$input['length']." ");

    $chapters = Group::GetChapterList($groupid);
    $channels= Group::GetChannelList($groupid);
    $final = [];
    $i=0;
    foreach($members as $row){

        if ($row['external_email']) {
            $row['email'] = User::PickEmailForDisplay($row['email'], $row['external_email'], true);
        }
        $encMemberUserid = $_COMPANY->encodeId($row['userid']);
        $ch = '';
        
        $clist = explode(",", $row['chapterid']);
        foreach ($clist as $c) { 
            if(!empty($chapters)) { 
                foreach ($chapters as $chapter) { 
                    if ($chapter['chapterid'] == $c) { 
                        $ch .= "<li>{$chapter['chaptername']}</li>";
                        break;
                    }
                } 
            }
        } 

        $channel = '';
        $channelList = explode(",", $row['channelids']);
        foreach ($channelList as $chn) {
            if(!empty($channels)) {
                foreach ($channels as $chan) { // **TODO** Channel authrization check
                    if ($chan['channelid'] == $chn) {
                        $channel .= '<li>'.htmlspecialchars($chan['channelname']).'</li>';
                        break;
                    }
                }
            }
        }

        $profilepic = User::BuildProfilePictureImgTag($row['firstname'],$row['lastname'], $row['picture'],'memberpic2', 'User Profile Picture', $row['userid'], 'profile_basic');
        
        if($row['anonymous']==1){
            $row['firstname'] = 'anonymous';
            $row['lastname'] = '';
            $row['jobtitle'] = 'anonymous';
            $row['email'] = 'anonymous';
            $row['picture'] = "";
            $profilepic = User::BuildProfilePictureImgTag("Anonymous","User", $row['picture'],'memberpic2', 'User Profile Picture');
           }
        // *** DO NOT REMOVE***
        // array_filter below. array filter is used to remove chapter or channel values
        // if the company cusotmization has disabled chapter or channels
        $userDetail = $profilepic ?? '-' ;
        $memberRow = array_filter(array(
            $userDetail . " <strong> ".
            rtrim(($row['firstname']." ".$row['lastname'])," ")."</strong> <br/>".$row['jobtitle'],
            ($_COMPANY->getAppCustomization()['chapter']['enabled'] ? ( $ch ? $ch : '-') : ''),
            ($_COMPANY->getAppCustomization()['channel']['enabled'] ? ($channel ? $channel :'-') : ''),
            $row['email'] ?? '-',
            $_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['groupjoindate'],true,true,false) ?? '-',
            '<a aria-expanded="false" id="lead_'.$encMemberUserid.'" role="button" tabindex="0" class="dropdown-toggle  fa fa-ellipsis-v col-doutd three-dot-action-btn" data-toggle="dropdown" aria-label="Action dropdown"></a>  <ul class="dropdown-menu dropdown-menu-right" id="dynamicActionButton" style="width: 200px; cursor: pointer;">'.
            (
                
                ($_ZONE->val('app_type') !== 'talentpeak' || !$group->isTeamsModuleEnabled())
                ?
                (
                    (
                        $_COMPANY->getAppCustomization()['chapter']['enabled'] || $_COMPANY->getAppCustomization()['channel']['enabled'])
                        ?
                        (
                            $_USER->canManageContentInScopeCSV($groupid)
                            ?
                            '<li><a role="button" aria-label="Edit" href="javascript:void(0)" class="" onclick="updateGroupMemberMembership(' . "'{$encGroupId}'" . ',' . "'{$encMemberUserid}'" . ')" title="<strong>Update membership</strong>"><i class="fa fa-edit fa-l" title="Edit" ></i>&emsp;'. gettext("Edit") . '</a></li>'
                            :
                            ''
                        )
                        :
                        ''
                ) . '<li><a role="button" data-toggle="popover" aria-label="Delete" href="javascript:void(0)" class="deluser confirm" onclick="removeGroupMember(' . "'{$encGroupId}'" . ',' . "'{$encMemberUserid}'" . ')" title="<strong>' . gettext("Are you sure you want to remove member?") . '</strong>"><i class="fa fa-trash fa-l" title="Delete" ></i>&emsp;'. gettext("Delete") . '</a></li>'
                :
                '-'                
            ).'</ul>'
           ));

        $final[] =   array_merge(array("DT_RowId" =>  "id_".$i), array_values($memberRow));
        
        $i++;
    }

    $json_data = array(
                    "draw"=> intval( $input['draw'] ), 
                    "recordsTotal"    => intval(count($final) ),
                    "recordsFiltered" => intval($totalrows), 
                    "data"            => $final
                );
    echo json_encode($json_data);
}

elseif (isset($_GET['getGroupMembersTab'])){
    $encGroupId = $_GET['getGroupMembersTab'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<1 ||
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
	$headtitle	= gettext("Members");

	include(__DIR__ . "/views/templates/manage_section_dynamic_button.html");
	include(__DIR__ . "/views/templates/get_manage_users_tab_menu.template.php");
}

elseif (isset($_GET['mangeGroupLeads'])){
    $encGroupId = $_GET['mangeGroupLeads'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<1 ||
        ($group = Group::GetGroup($groupid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $canManageGroup = $_USER->canManageGroup($groupid);
    $types = Group::GetGroupleadTypes();
    $leads= $leads= $group->getGroupLeads();
    $leads_count = count($leads);
    
    for ($i = 0; $i < $leads_count; $i++) {
        if (empty($leads[$i]['grouplead_typeid'])){
            $leads[$i]['rolename'] = '';
            $leads[$i]['permissions'] = array();
           
        }else{
            $leads[$i]['rolename'] = $types[$leads[$i]['grouplead_typeid']]['type'];
            $leads[$i]['permissions'] = $types[$leads[$i]['grouplead_typeid']]['permissions'];
            if ($types[$leads[$i]['grouplead_typeid']]['sys_leadtype'] == 3 && $leads[$i]['regionids']){
                $regions = $_COMPANY->getRegionsByCSV($leads[$i]['regionids']);
                if (!empty($regions)) {
                    $leads[$i]['rolename'] = $leads[$i]['rolename'] . ': ' . implode(', ', array_column($regions,'region'));
                }
            }
        }
    }
    $systemLeadType  = Group::SYS_GROUPLEAD_TYPES;
	$headtitle	= gettext("Members");

   
	include(__DIR__ . "/views/templates/manage_group_leads.template.php");
}

## OK
## Get About Us
elseif (isset($_GET['getAboutusTabs']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getAboutusTabs']))<1 ||
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    $encGroupId = $_COMPANY->encodeId($groupid);

    $canViewContent = $_USER->canViewContent($groupid);

    include(__DIR__ . "/views/templates/about_group_tabs.php");
}

elseif (isset($_GET['getAboutus']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getAboutus']))<1  ||
        ($group = Group::GetGroup($groupid)) == null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    $encGroupId = $_COMPANY->encodeId($groupid);
    $types = Group::GetGroupleadTypes();
    $type[0] = '';

    $canViewContent = $_USER->canViewContent($groupid);
    // Leads
	$leads = $group->getGroupLeads('',true);

	include(__DIR__ . "/views/templates/about_group.template.php");
}


elseif (isset($_GET['donation']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['donation']))<1 ||
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);

    include(__DIR__ . "/views/templates/donation.template.php");
}


## OK
## Provide feed for my `groups`tab  inarray
elseif (isset($_GET['getMyGroups'])){

    [$groupCategoryRows, $groupCategoryIds, $group_category_id] = ViewHelper::InitGroupCategoryVariables();

    $page = 1;
    $_USER->setUserPreference(UserPreferenceType::ZONE_ShowMyGroups, 1);
    $tags = array();
    if (isset($_GET['groupTags'])){
        $tags = $_GET['groupTags'];
    }

    $groupIdAry = Group::GetAvailableGroupsForGlobalFeeds($group_category_id, 1, $tags);
    $groups = Group::GetGroups($groupIdAry);
    $groupIdAry[] = 0; // Add groupid zero to the list, only after the groups are fetched.
    $feeds = array();
    $myGroupsOnly = 1;
    $contentsCount = 0;
    $default_content_types = Content::GetAvailableContentTypes();
    $selected_content_types = (array) $_GET['contentFilter'] ?? array();
    $include_content_types = array_intersect($default_content_types,$selected_content_types);
    if(!empty($groups)){
        $feedsData = ViewHelper::GetHomeFeeds($groupIdAry,$myGroupsOnly,$page,MAX_HOMEPAGE_FEED_ITERATOR_ITEMS,$include_content_types);
        $feeds =  $feedsData['feeds'];
        $contentsCount = $feedsData['contents_count_before_processing'];
    }

	include(__DIR__ . "/views/templates/home_html.template.php");
}

## OK
## Provides feed for discover groups
elseif (isset($_GET['discoverGroups'])){
    // check for group categories
    [$groupCategoryRows, $groupCategoryIds, $group_category_id] = ViewHelper::InitGroupCategoryVariables();

    $page = 1;

    $_USER->setUserPreference(UserPreferenceType::ZONE_ShowMyGroups, 0);
    $tags = array();
    if (isset($_GET['groupTags'])){
        $tags = $_GET['groupTags'];
    }
    $groupIdAry = Group::GetAvailableGroupsForGlobalFeeds($group_category_id, 0, $tags);
    $groups = Group::GetGroups($groupIdAry);
    $groupIdAry[] = 0; // Add groupid zero to the list, only after the groups are fetched else it shows blank tile
    $myGroupsOnly = 0;
    $feeds = array();
    $contentsCount = 0;
    $default_content_types = Content::GetAvailableContentTypes();
    $selected_content_types = (array) $_GET['contentFilter'] ?? array();
    $include_content_types = array_intersect($default_content_types,$selected_content_types);
    if(!empty($groups)){
        $feedsData = ViewHelper::GetHomeFeeds($groupIdAry, $myGroupsOnly, $page,MAX_HOMEPAGE_FEED_ITERATOR_ITEMS,$include_content_types);
        $feeds =  $feedsData['feeds'];
        $contentsCount = $feedsData['contents_count_before_processing'];
    }

	include(__DIR__ . "/views/templates/home_html.template.php");
}

elseif (isset($_GET['loadMoreHomeFeeds'])){
    [$groupCategoryRows, $groupCategoryIds, $group_category_id] = ViewHelper::InitGroupCategoryVariables();

    $page = 1;
    if (isset($_GET['page']) && (int)$_GET['page'] > 0) {
        $page = (int)$_GET['page'];
    }

    $landing_page = intval($_COMPANY->getAppCustomization()['group']['homepage']['show_my_groups_option'] ? $_USER->getUserPreference(UserPreferenceType::ZONE_ShowMyGroups) : 0);
    $myGroupsOnly = $landing_page;

    $tags = array();
    if (isset($_GET['groupTags'])){
        $tags = $_GET['groupTags'];
    }
    $default_content_types = Content::GetAvailableContentTypes();
    $selected_content_types = (array) $_GET['contentFilter'] ?? array();
    $include_content_types = array_intersect($default_content_types,$selected_content_types);
    $groupIdAry = Group::GetAvailableGroupsForGlobalFeeds($group_category_id, $myGroupsOnly,$tags);
    $groupIdAry[] = 0; // Add groupid zero to the list, only after the groups are fetched else it shows blank tile
    $feedsData = ViewHelper::GetHomeFeeds($groupIdAry, $myGroupsOnly,$page,MAX_HOMEPAGE_FEED_ITERATOR_ITEMS,$include_content_types);
    $feeds =  $feedsData['feeds'];
    $contentsCount = $feedsData['contents_count_before_processing'];

	include(__DIR__ . '/views/home/feed_rows.template.php');
}


// OK
// Update about group contents
elseif (isset($_GET['updateboutus']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['updateboutus']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);
    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	if (isset($_GET['edit']) && ($_GET['edit'] === 'true')) {
		//$abouttitle = raw2clean($_POST['abouttitle']);
        $aboutgroup	= ViewHelper::RedactorContentValidateAndCleanup($_POST['aboutgroup']);
        $groupChapterId = (int) $_COMPANY->decodeId($_POST['groupChapterId']);
        $section = (int) $_COMPANY->decodeId($_POST['section']);

        $checkArray = array('Description'=>@$aboutgroup);
        $check = $db->checkRequired($checkArray);

		if($check){
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$check), gettext('Error'));
		}else{
            if ( $groupChapterId === 0){
                //$checkArray = array('Heading'=>@$abouttitle,'Description'=>@$aboutgroup);
                // Authorization Check
                if (!$_USER->canManageGroup($groupid)) {
                    header(HTTP_FORBIDDEN);
                    exit();
                }
            } else {
                if ($section == 1) { // Chapter section
                    // Authorization Check
                    if (!$_USER->canManageContentInScopeCSV($groupid,$groupChapterId,0)) {
                        header(HTTP_FORBIDDEN);
                        exit();
                    }
                } elseif ($section ==2) { // Channel section
                    if (!$_USER->canManageContentInScopeCSV($groupid,0,$groupChapterId)) {
                        header(HTTP_FORBIDDEN);
                        exit();
                    }
                }
            }

            if( $groupChapterId ==0){
                $update = $group->updateGroupAboutUs($aboutgroup);
            } else {
                if ($section == 1){
                    $update = $group->updateChapterAboutUs($groupChapterId,$aboutgroup);
                } elseif ($section == 2) {
                    $update = $group->updateChannelAboutUs($groupChapterId,$aboutgroup);
                }
            }
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Updated successfully."), gettext('Success'));
		}
	} else{
        $chapters = Group::GetChapterList($groupid);
        $channels= Group::GetChannelList($groupid);
        $fontColors = $groupid > 0 ? json_encode(array($group->val('overlaycolor'),$group->val('overlaycolor2'))) : json_encode(array());
        $headtitle = gettext("About Us");
        include(__DIR__ . "/views/templates/manage_section_dynamic_button.html");
        include(__DIR__."/views/templates/update_group_aboutus.template.php");
    }
}

## OK
## Read Notification
elseif(isset($_GET['readNotification'])){
    //Data Validation
    $id = 0;
    if (!isset($_GET['s']) || ($section = (int)$_GET['s']) < 1 ||
        !isset($_GET['i']) || ($id = $_COMPANY->decodeId($_GET['i']))<0 ||
        ($notificationid = (int)$_GET['readNotification']) === 0 ||
        ($notification = Notification::GetNotification($notificationid)) === null

        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    // none
	$update = $notification->readNotification();

	if($section===2){
	    // Post
        $postdetail = $db->get("SELECT postid, isactive FROM post WHERE postid='{$id}'");
        if (empty($postdetail) || $postdetail[0]['isactive'] != 1) {
            AjaxResponse::SuccessAndExit_STRING(2, '', gettext("This content is no longer available"), gettext('Error'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(1, "viewpost?id=".$_COMPANY->encodeId($postdetail[0]['postid']), gettext("Success"), gettext('Success'));
        }
	} elseif ($section===3) {
	    // Event
        $eventdetail = $db->get("SELECT eventid, isactive FROM events WHERE eventid='{$id}'");
        if (empty($eventdetail) || $eventdetail[0]['isactive'] != 1) {
            AjaxResponse::SuccessAndExit_STRING(2, '', gettext("This content is no longer available"), gettext('Error'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(1, "eventview?id=".$_COMPANY->encodeId($eventdetail[0]['eventid']), gettext("Success"), gettext('Success'));
        }
	} else {
        AjaxResponse::SuccessAndExit_STRING(2, '', gettext("This content is no longer available"), gettext('Error'));
	}
}

## OK
// Read all Notification
elseif(isset($_GET['setAllReadNotification']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    //none

    // Authorization Check
    // none

	$update = Notification::ReadAllNotifications();
	echo '1';
}

## OK
## Update User timezone
elseif (isset($_GET['updateTimeZone']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (!isset($_POST['timezone']) ||
        !isValidTimeZone($_POST['timezone'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    // not needed

    $timezone = explode(" ",$_POST['timezone'])[0];
    $timezone = raw2clean($timezone);
    if ($_USER->updateTimezone($timezone)) {
        $_SESSION['timezone'] = $timezone;
        unset($_SESSION['timezone_ask_user']);
    }
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Timezone updated successfully"), gettext('Success'));
}

## OK
## Use detected timezone
elseif (isset($_GET['useBrowserTimezone'])){
	$_SESSION['timezone'] = $_SESSION['tz_b'];
    unset($_SESSION['timezone_ask_user']);
	AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Timezone updated successfully"), gettext('Success'));
}

## OK
## Use Profile timezone
elseif (isset($_GET['useProfileTimezone'])){
	$_SESSION['timezone'] = $_USER->val('timezone');
    unset($_SESSION['timezone_ask_user']);
	AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Timezone updated successfully"), gettext('Success'));
}

## OK
## invite users
elseif (isset($_GET['inviteGroupMembers'])){

    //Data Validation
    if (
        !$_COMPANY->getAppCustomization()['group']['allow_invite_members'] ||
        ($groupid = $_COMPANY->decodeId($_GET['inviteGroupMembers']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	// get invitation sent
    $sentInvitation  = $group->getGroupMemberInvites();
	$headtitle = gettext("Invite Users");

    $chapters = Group::GetChapterList($groupid);
    $channels= Group::GetChannelList($groupid);   
	include(__DIR__ . "/views/templates/manage_section_dynamic_button.html");
	include(__DIR__ . "/views/templates/invite_to_group.template.php");
}

## OK
elseif(isset($_GET['sendGroupMemberInvite'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (!$_COMPANY->getAppCustomization()['group']['allow_invite_members'] ||
        ($groupid = $_COMPANY->decodeId($_GET['sendGroupMemberInvite']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $email = $_POST['email'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$_COMPANY->isValidEmail($email)) {
        echo 0;
        exit();
    }

    $group_chapter_channel_id = isset($_POST['group_chapter_channel_id']) ? $_COMPANY->decodeId($_POST['group_chapter_channel_id']) : 0;
    $section = isset($_POST['section']) ? $_COMPANY->decodeId($_POST['section']) : 0;
    $chapterid = 0;
    $channelid = 0;
    if ($section == 1){
        // For auto assign usecases, chapterid is -1 so reset it first.
        $chapterid = max($group_chapter_channel_id, 0);
    } elseif ($section == 2){
        $channelid = max($group_chapter_channel_id, 0);
    }

	$group = Group::GetGroup($groupid);

    $retVal = $group->inviteUserToJoinGroup($email, $chapterid, $channelid);
    echo $retVal;
    exit();
}

## OK
elseif (isset($_GET['updateGroupleadPriorityFrontEnd']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (empty($_POST['prioritylist']) ||
        !isset($_POST['gid']) ||
        ($gid=$_COMPANY->decodeId($_POST['gid'])) < 1 ||
        ($group = Group::GetGroup($gid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageContentInScopeCSV($gid,0,0)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $priority = $_COMPANY->decodeIdsInCSV($_POST['prioritylist']);
    $group->updateGroupleadsPriorityOrder($priority);
    echo 1;
}

elseif (isset($_GET['updateChapterleadPriorityFrontEnd']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (empty($_POST['prioritylist']) ||
        !isset($_POST['gid']) ||
        ($gid=$_COMPANY->decodeId($_POST['gid'])) < 1 ||
        ($group = Group::GetGroup($gid)) === NULL ||
        ($filterChapterId=$_COMPANY->decodeId($_POST['filterChapterId'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }    
    // Authorization Check
    if (!$_USER->canManageContentInScopeCSV($gid,0,0)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $priority = $_COMPANY->decodeIdsInCSV($_POST['prioritylist']);
    $group->updateChapterleadsPriorityOrder($filterChapterId,$priority);
    echo 1;  
}

elseif (isset($_GET['updateChannelleadPriorityFrontEnd']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (empty($_POST['prioritylist']) ||
        !isset($_POST['gid']) ||
        ($gid=$_COMPANY->decodeId($_POST['gid'])) < 1 ||
        ($group = Group::GetGroup($gid)) === NULL ||
        ($filterChannelId=$_COMPANY->decodeId($_POST['filterChannelId'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageContentInScopeCSV($gid,0,0)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $priority = $_COMPANY->decodeIdsInCSV($_POST['prioritylist']);
    $group->updateChannelleadsPriorityOrder($filterChannelId,$priority);
    echo 1;
}

elseif(isset($_GET['withdrawGroupMemberInvite']) && $_SERVER['REQUEST_METHOD'] === 'POST') { //Return 0 on error, 1 on withdraw
    if (
        ($groupid = $_COMPANY->decodeId($_GET['withdrawGroupMemberInvite']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL ||
        ($memberinviteid = $_COMPANY->decodeId($_POST['mid'])) < 1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    
    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $retVal = $group->withdrawGroupMemberInvite($memberinviteid);
    echo intval($retVal == 1); // $db->update can return -1, we only want to return 1 or 0
    exit();
}

## OK
## Process Join/Leave buttons
elseif(isset($_GET['followUnfollowChapter']) && $_SERVER['REQUEST_METHOD'] === 'POST'){ //Return 0 on error, 1 on removal, 2 on addition
	$encGroupId = $_POST['groupid'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<1 ||
        ($chapterid	= $_COMPANY->decodeId($_POST['chapterid'])) <0) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    if ($_USER->isGroupMember($groupid,$chapterid)) {

        if ($_USER->leaveGroup($groupid,$chapterid,0)) {
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Join"), gettext('Success'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, gettext("Join"), gettext('Something has gone wrong. Please try again later.'), gettext('Error'));
        }
    } else {
        if ($_USER->joinGroup($groupid,$chapterid,0)) {
            AjaxResponse::SuccessAndExit_STRING(2, '', gettext("Leave"), gettext('Success'));
        } else{
            AjaxResponse::SuccessAndExit_STRING(0, gettext("Join"), gettext('Something has gone wrong. Please try again later.'), gettext('Error'));
        }
    }
}

elseif(isset($_GET['loadSurveyModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
 	$encGroupId = $_GET['groupid'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<0 ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    $encGroupId = $_COMPANY->encodeId($groupid);

    $page_title =gettext("Survey");

    while (($surveySet = $_USER->popSurveyFromSession()) !== null) {
        if ($surveySet['surveysubtype']== Survey2::SURVEY_TRIGGER['ON_JOIN']){ //On join
            $_USER->canViewContent($surveySet['groupid'], true); // Reset View Content cache
            $surveyData = GroupMemberSurvey::GetActiveGroupMemberSurveyForGroupJoin($surveySet['groupid'], $surveySet['chapterid'],$surveySet['channelid']);
        } elseif ($surveySet['surveysubtype']== Survey2::SURVEY_TRIGGER['ON_LEAVE']){ //On Leave
            $_USER->canViewContent($surveySet['groupid'], true); // Reset View Content cache
            $surveyData = GroupMemberSurvey::GetActiveGroupMemberSurveyForGroupLeave($surveySet['groupid'], $surveySet['chapterid'],$surveySet['channelid']);
        } elseif ($surveySet['surveysubtype']== Survey2::SURVEY_TRIGGER['ON_LOGIN']){ // Login Survey
            $surveyData = ZoneMemberSurvey::GetActiveLoginSurvey();
        } 
        if (!empty($surveyData)) {      
            $survey = $surveyData[0];
            if ($surveySet['surveysubtype']== Survey2::SURVEY_TRIGGER['ON_LOGIN']){
                $surveyid = $survey->val('surveyid');
                $canResponse = $survey->canSurveyRespond();
                if (!$canResponse){
                    echo 0;
                    exit();
                }
            }

            if (!$survey->val('allow_multiple') &&
                !in_array($survey->val('surveysubtype'), array(Survey2::SURVEY_TRIGGER['ON_JOIN'], Survey2::SURVEY_TRIGGER['ON_LEAVE'])) &&
                $survey->isSurveyResponded($_USER->id())
            ){ // If survey is not Join / Leave type and Multiple settings is not on then allow user to respond only if user did not respond in the past.
                echo 0;
                exit();
            } else {
                $surveyLanguages = $survey->getSurveyLanguages();
                include(__DIR__ . "/views/templates/survey.template.php");
                exit();
            }
        }
    }
    exit (0);
}

## OK
elseif (isset($_GET['updateUserEmailSetting']) && $_SERVER['REQUEST_METHOD'] === 'POST'){ //Return 0 on error, 1 on removal, 2 on addition
    //Data Validation
    if (($memberid = $_COMPANY->decodeId($_POST['memberid']))<0 ||
        !isset($_POST['value']) || !isset($_POST['key'])) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

	$value = (int)($_POST['value']);
	$key = raw2clean($_POST['key']);

	if ($_USER->updateNotificationSetting($memberid,$key,$value)) {
	    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Setting updated successfully.'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong, please try after some time.'), gettext('Error'));
    }
}
elseif (isset($_GET['getGroupSurveys'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getGroupSurveys']))<0 || ($group = Group::GetGroup($groupid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $encGroupId = $_COMPANY->encodeId($groupid);

    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $headtitle	= gettext("Surveys");
    $state_filter = 1; //groupState
    $erg_filter = 0;
    $erg_filter_section = 0; //groupStateType

    if (!empty($_GET['state_filter'])){
        $state_filter = $_COMPANY->decodeId($_GET['state_filter']);
    }
    if (!empty($_GET['erg_filter'])){
        $erg_filter = $_COMPANY->decodeId($_GET['erg_filter']);
    }
    if (!empty($_GET['erg_filter_section']) && $_GET['erg_filter_section'] !== 'undefined'){ // Todo: Fix 'undefined' usecase at javascript level
        $erg_filter_section = $_COMPANY->decodeId($_GET['erg_filter_section']);
    }
    $chpaterid = -1;
    $channelid = -1;
    if ($erg_filter_section == 1) {
        $chpaterid = $erg_filter;
    }
    if ($erg_filter_section == 2) {
        $channelid = $erg_filter;
    }

    $state_filter = $state_filter == 1 ? true : false;
    $surveys = GroupMemberSurvey::GetAllSurveys($groupid,$chpaterid,$channelid,0, $state_filter); 
    
    include(__DIR__ . "/views/templates/group_active_surveys_listview.php");
}

elseif (isset($_GET['download_survey2_report']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    set_time_limit(120);
    $encGroupId = $_GET['groupid'];
    //Data Validation
    if ((($groupid = $_COMPANY->decodeId($encGroupId))<0 ) ||
        ($surveyid = $_COMPANY->decodeId($_GET['download_survey2_report']))<1 ||
        ($survey = Survey2::GetSurvey($surveyid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit('Bad Request');
    }
    // Authorization Check
    if (!$_COMPANY->getAppCustomization()['surveys']['enabled']) {
        header(HTTP_FORBIDDEN);
        exit('Forbidden');
    }
    if ($groupid){
        // Authorization Check
        if (!$_USER->canManageContentInScopeCSV($survey->getGroupId(), $survey->getChapterId(),$survey->getChannelId())) {
            header(HTTP_FORBIDDEN);
            exit('Forbidden');
        }
    } else {
        if (!$_USER->isAdmin()) {
            header(HTTP_FORBIDDEN);
            exit('Forbidden');
        }
    }

    $meta = ReportSurvey::GetDefaultReportRecForDownload();
    
    if ($survey->val('anonymous')){
       unset($meta['Fields']['firstname']);
       unset($meta['Fields']['lastname']);
       unset($meta['Fields']['email']);
       unset($meta['Fields']['jobtitle']);
       unset($meta['Fields']['department']);
       unset($meta['Fields']['branchname']);
    }
    if ($survey->val('surveytype')!=4){
        unset($meta['Fields']['context']);
    }

    $meta['Options']['anonymous'] = $survey->val('anonymous');
    $meta['Filters']['groupid'] = $groupid;
    $meta['Filters']['surveyid'] = $surveyid;

    // Lastly remove Admin fields
    unset($meta['AdminFields']);

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'survey';
    $record['reportmeta'] = json_encode($meta);

    $report = new ReportSurvey ($_COMPANY->id(),$record);
    $report_file = $report->generateReport(Report::FILE_FORMAT_CSV, false);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    echo false;
    exit();
}

elseif (isset($_GET['getAboutUsFields']) && isset($_GET['id'])){
	$encGroupId = $_GET['getAboutUsFields'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<1 ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $encGroupId = $_COMPANY->encodeId($groupid);
    $selectedId = $_COMPANY->decodeId($_GET['id']);
    $section = $_COMPANY->decodeId($_GET['section']);
    $group = Group::GetGroup($groupid);
    $fontColors = $groupid > 0 ? json_encode(array($group->val('overlaycolor'),$group->val('overlaycolor2'))) : json_encode(array());
    $aboutus = '';
    if ($selectedId ==0 ){
        // Selected id is groupid
        // Authorization Check
        if (!$_USER->canManageGroup($groupid)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $aboutus = $group->val('aboutgroup');
    } else {
        if ( $section  == 1){ // chapter section
            // Selected id is a chapter id.
            $chapter = $group->getChapter($selectedId);
            if(!$chapter){                   
                AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('This %s is not available. Please try again!'),$_COMPANY->getAppCustomization()['chapter']['name-short']), gettext('Error'));
		    }else{
            // Authorization Check
            if (!$_USER->canManageGroupChapter($groupid, $chapter['regionids'], $chapter['chapterid'])) {
                header(HTTP_FORBIDDEN);
                exit();
            }
        }
            $aboutus =  $chapter['about'];
        } elseif ($section==2) { // Channel section
            $channel = $group->getChannel($selectedId);
            if(!$channel){  
                AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('This %s is not available. Please try again!'),$_COMPANY->getAppCustomization()['channel']['name-short']), gettext('Error'));
            }else{
            if (!$_USER->canManageGroupChannel($groupid, $channel['channelid'])) {
                header(HTTP_FORBIDDEN);
                exit();
            }
        }
            $aboutus =  $channel['about'];
        }
    } ?>
    <div class="form-group">
        <label class="control-lable" ><?= gettext("Description")?></label>
        <div id="post-inner" class="post-inner-edit">
            <textarea aria-label="<?= gettext('Description here');?>" class="form-control editor" name="aboutgroup" id="redactor_content"  placeholder="<?= gettext('Description here'); ?>" rows="10" required><?= htmlspecialchars($aboutus) ?></textarea>
        </div>
    </div>

    <script type="text/javascript">
        setTimeout(function(){ 
            var fontColors = <?= $fontColors; ?>;
		    var resizableimages = true;
		$('#redactor_content').initRedactor('redactor_content','group',['video','fontcolor','counter','fontsize','table'],fontColors,'<?= $_COMPANY->getImperaviLanguage(); ?>',resizableimages);
         $(".redactor-voice-label").text("<?= gettext('Description here');?>");
         redactorFocusOut('#groupChapterId'); // function used for focus out from redactor when press shift + tab.
        }, 100);
      
    </script>
<?php

}
elseif (isset($_GET['groupLeadRoleModal']) && isset($_GET['id'])){
	$encGroupId = $_GET['groupLeadRoleModal'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<1 || ($group = Group::GetGroup($groupid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGrantGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $leadid = $_COMPANY->decodeId($_GET['id']);

    $form_title = gettext("Add a Leader");
    $users = null;
    $checkLeadType = 0;
    $region = null;
    $grouplead_type = null;
    $edit = array();

    $group_regions = explode(',',$group->val('regionid'));
    $allRegions = $_COMPANY->getAllRegions();
    foreach($allRegions as $r){
        if (in_array($r['regionid'],$group_regions)){
            $region[] = $r;
        }
    }
    $all_grouplead_type = $_COMPANY->getAllGroupLeadtypes();

    foreach($all_grouplead_type as $t){
        if ($t['sys_leadtype']<4){
            $grouplead_type[] = $t;
        }
    }

    if($leadid>0){
        $form_title = gettext("Edit Leader");
        $edit = $group->getGroupLead($leadid);
        if (count($edit)){
            $checkLeadType = 0;
            foreach($all_grouplead_type as $t){
                if ($t['typeid']==$edit[0]['grouplead_typeid']){
                    $checkLeadType = $t['sys_leadtype'];
                    break;
                }
            }
        }
    }

    include(__DIR__ . "/views/templates/group_lead_form.php");
}
elseif (isset($_GET['checkGroupleadType']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($typeid = $_COMPANY->decodeId($_GET['checkGroupleadType']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
	$checkLeadType = $db->get("SELECT `sys_leadtype` FROM `grouplead_type` WHERE`typeid`='".$typeid."'");

	echo $checkLeadType[0]['sys_leadtype'];
}

elseif (isset($_GET['search_keyword_user']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    // SearchUsersByKeyword is safe method and can handle unclean data, so no need for raw2clean.
    $activeusers =  User::SearchUsersByKeyword($_GET['search_keyword_user']);

	if(count($activeusers)>0){ ?>
		<select class="form-control userdata" name="userid" onchange="closeDropdown()" id="user_search" required >
			<option value=""><?= gettext('Select a user (maximum of 20 matches are shown below)');?></option>
<?php	for($a=0;$a<count($activeusers);$a++){  ?>
			<option value="<?= $_COMPANY->encodeId($activeusers[$a]['userid']); ?>" ><?= rtrim(($activeusers[$a]['firstname']." ".$activeusers[$a]['lastname'])," ")." (". $activeusers[$a]['email'].") - ".$activeusers[$a]['jobtitle']; ?></option>
<?php 	} ?>
		</select>

<?php }else{ ?>
		<select class="form-control userdata" name="userid" id="user_search" required>
			<option value=""><?= gettext("No match found.");?></option>
		</select>

<?php	}
}

elseif (isset($_GET['updateGroupLeadRole'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
	$encGroupId = $_GET['updateGroupLeadRole'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<1 || ($group = Group::GetGroup($groupid)) === null ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGrantGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }


    $leadid = $_COMPANY->decodeId($_POST['leadid']);
    $typeid = $_COMPANY->decodeId($_POST['typeid']);
    $roletitle = $_POST['roletitle'];
    $check = array('User' => @$_POST['userid']);
    $checkRequired = $db->checkRequired($check);
    if($checkRequired){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty!"),$checkRequired), gettext('Error!'));
    }
    $personid = $_COMPANY->decodeId($_POST['userid']);

    // Check for group restriction before any other processing.
    if(!$group->isUserAllowedToJoin($personid)){
        AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext('User does not meet membership requirements for %1$s %2$s.'), $group->val('groupname'), $_COMPANY->getAppCustomization()['group']['name-short']), gettext('Error'));
    }

    $all_grouplead_type = $_COMPANY->getAllGroupLeadtypes();
    $sysLeadType = 0;
    foreach($all_grouplead_type as $t){
        if ($t['typeid']==$typeid){
            $sysLeadType = $t['sys_leadtype'];
            break;
        }
    }

	if ($sysLeadType != 3 ){
		$regionids = "0";
	} else {
		if(isset($_POST['regionids']) && !empty($_POST['regionids']) ){
			$dec_rid_array = array();
			foreach ($_POST['regionids'] as $enc_rid) {
				$dec_rid = $_COMPANY->decodeId($enc_rid);
				if ($dec_rid < 0) {
					$_SESSION['error'] = time();
					exit();
				}
				$dec_rid_array[] = $dec_rid;
			}
			$regionids = implode(',',$dec_rid_array);
		}else{
			$regionids = "0";
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please select a region'), gettext('Error'));
		}
    }

    if ($leadid > 0) {
        Group::UpdateGroupLead($groupid, $leadid, $typeid, $regionids, $roletitle);
        AjaxResponse::SuccessAndExit_STRING(1, $_POST['leadid'], gettext('Group leader role updated successfully.'), gettext('Success'));
    } else {
        Group::AddGroupLead($groupid, $personid, $typeid, $regionids, $roletitle);
        $group->addOrUpdateGroupMemberByAssignment($personid,0,0);

        # Inform lead by email
        $group->sendGroupLeadAssignmentEmail($groupid, 1, $typeid, $personid);
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Group leader assigned successfully.'), gettext('Success'));
    }
}

elseif (isset($_GET['openChapterLeadRole']) && isset($_GET['cid']) && isset($_GET['id'])){
    $leadid = 0;

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['openChapterLeadRole']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($chapterid = $_COMPANY->decodeId($_GET['cid'])) < 0 ||
        (isset($_GET['id']) && ($leadid = $_COMPANY->decodeId($_GET['id'])) < 0)
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGroupSomeChapter($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    // Reencode Group Id & Chapter Id
    $encGroupId = $_COMPANY->encodeId($groupid);
    $encChapterId = $_COMPANY->encodeId($chapterid);

    $grouplead_type = array();
    $allchapter = null;
    $edit=[];

    $form_title = sprintf(gettext('Add %s Leader'),$_COMPANY->getAppCustomization()['chapter']['name-short']);

    if ($leadid>0 && $chapterid>0){
        $form_title = sprintf(gettext('Edit %1$s %2$s Leader'),Group::GetChapterName($chapterid, $groupid)['chaptername'], $_COMPANY->getAppCustomization()['chapter']['name-short']);

        $edit = $group->getChapterLeadDetail($chapterid,$leadid);

        $ch = $group->getChapter($chapterid);
        $regionid = $ch['regionids'] ?? 0;
        if (!$_USER->canManageGrantGroupChapter($groupid,$regionid,$chapterid)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } else {
        $allchapter = Group::GetChapterList($groupid);
        $chaptercount = count($allchapter);
        for ($i = 0; $i < $chaptercount; $i++) {
            if (!$_USER->canManageGrantGroupChapter($groupid,$allchapter[$i]['regionids'],$allchapter[$i]['chapterid'])) {
                unset($allchapter[$i]);
            }
        }
    }

    $all_grouplead_type = $_COMPANY->getAllGroupLeadtypes();
    foreach($all_grouplead_type as $t){
        if ($t['sys_leadtype']==4){
            $grouplead_type[] = $t;
        }
    }
    
    include(__DIR__ . "/views/templates/chapter_lead_form.php");
}

elseif (isset($_GET['search_users_to_lead_chapter']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (!isset($_GET['cid']) || !isset($_GET['gid']) ||
        ($groupId = $_COMPANY->decodeId($_GET['gid']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->canManageGroupSomeChapter($groupId)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $chapterId = $_COMPANY->decodeId($_GET['cid']);

    // SearchUsersByKeyword is safe method and can handle unclean data, so no need for raw2clean.
    $activeusers = User::SearchUsersByKeyword($_GET['keyword']);

	$dropdown = '';
	if(count($activeusers)>0){
		$dropdown .= "<select class='form-control userdata' name='userid' onchange='closeDropdown()' id='user_search' required >";
		$dropdown .= "<option value=''>".gettext('Select a user (maximum of 20 matches are shown below)')."</option>";

		for($a=0;$a<count($activeusers);$a++){
		    $dropdown .=  "<option value='".$_COMPANY->encodeId($activeusers[$a]['userid'])."'>".rtrim(($activeusers[$a]['firstname']." ".$activeusers[$a]['lastname'])," ")." (".$activeusers[$a]['email'].") - ".$activeusers[$a]['jobtitle']."</option>";
		}
		$dropdown .= '</select>';
	}else{
        $dropdown .= "<select class='form-control userdata' name='userid' id='user_search' required>";
		$dropdown .= "<option value=''>".gettext('No match found.')."</option>";
		$dropdown .= "</select>";
	}
	echo $dropdown;
}

elseif (isset($_GET['updateChapterLeadRole'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['updateChapterLeadRole']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);

    $chapterid  = $_COMPANY->decodeId($_POST['chapterid']);
    $leadid     = $_COMPANY->decodeId($_POST['leadid']);
    $typeid     = $_COMPANY->decodeId($_POST['typeid']);
    $personid     = $_COMPANY->decodeId($_POST['userid']);
    $roletitle = $_POST['roletitle'];

    // Check for group restriction before any other processing.
    if(!$group->isUserAllowedToJoin($personid)){
        AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext('User does not meet membership requirements for %1$s %2$s.'), $group->val('groupname'), $_COMPANY->getAppCustomization()['group']['name-short']), gettext('Error'));
    }

    // Authorization Check
    $ch = $db->get("SELECT `chapterid`,`regionids` FROM `chapters` WHERE chapters.companyid='{$_COMPANY->id()}' AND ( chapters.zoneid='{$_ZONE->id()}' AND `groupid`='{$groupid}' AND `chapterid`='{$chapterid}')");
    $regionid = $ch[0]['regionids'] ?? 0;
    if (!$_USER->canManageGrantGroupChapter($groupid, $regionid, $chapterid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($leadid){
        $group->updateChapterLead($chapterid,$leadid,$typeid,$roletitle);
        AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext("%s leader role updated successfully."),$_COMPANY->getAppCustomization()['chapter']['name-short']), gettext('Success'));

        
    } else {
        $group->addChapterLead($chapterid,$personid,$typeid,$roletitle);
        $group->addOrUpdateGroupMemberByAssignment($personid,$chapterid,0);

        # Inform lead by email
        $group->sendGroupLeadAssignmentEmail($chapterid, 2, $typeid, $personid);
        AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext("%s leader role assigned successfully."),$_COMPANY->getAppCustomization()['chapter']['name-short']), gettext('Success'));
    }
}
elseif (isset($_GET['deleteGroupLeadRole'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 || ($leadid = $_COMPANY->decodeId($_POST['leadid']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);

    // Authorization Check
    if (!$_USER->canManageGrantGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $leadid     = $_COMPANY->decodeId($_POST['leadid']);
    Group::DeleteGroupLead((int) $groupid, (int) $leadid);
    echo 1;
}
elseif (isset($_GET['deleteChapterLeadRole'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 || ($chapterid = $_COMPANY->decodeId($_POST['chapterid']))<1 || ($leadid = $_COMPANY->decodeId($_POST['leadid']))<1 || ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);

    // Authorization Check
    $ch = $group->getChapter($chapterid);
    $regionid = $ch['regionids'] ?? 0;
    if (!$_USER->canManageGrantGroupChapter($groupid, $regionid, $chapterid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    echo $group->removeChapterLead($chapterid,$leadid);
}

elseif (isset($_GET['getRegionsForGroup']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    
    if (($groupid = $_COMPANY->decodeId($_GET['getRegionsForGroup']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    //permission check

    $data	= $db->get("SELECT `regionid`,`region` FROM `regions` WHERE companyid={$_COMPANY->id()} AND FIND_IN_SET (regionid,'".$group->val('regionid')."')");
    if (count($data)) {

    include(__DIR__ . "/views/groups/chapter/select_region_modal.template.php");

    } else {
        echo 0;
    }
}

elseif (isset($_GET['openNewChapterModel']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $groupid    = 0;
    $chapterid  = 0;
    $regionid   = 0;
    //Data Validation

    if (!isset($_GET['gid']) ||
        ($groupid = $_COMPANY->decodeId($_GET['gid']))<1 || 
        ($regionid = $_COMPANY->decodeId($_GET['rid']))<1 ||   
        ($group = Group::GetGroup($groupid)) == null) {       
        header(HTTP_BAD_REQUEST);
        exit();
    }    
    
    //Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if(isset($_GET['cid'])){
        $chapterid = $_COMPANY->decodeId($_GET['cid']);             
    }  
      
    $encGroupId = $_COMPANY->encodeId($groupid);

    // Else generate data needed to show the form.   
    $form = gettext("Add");
    $mybranches = '';
    if ($chapterid) {
        // Get the branches already assigned to the chapter
        // Also get the regionid
        $form = gettext("Edit");
        $edit = $group->getChapter($chapterid, true);

        if (!empty($edit)) {
            $regionid = $edit['regionids'];
            $mybranches = $edit['branchids'];
        } else { // Chapter id provided but chapter not found (in matching group)
            header(HTTP_BAD_REQUEST);
            exit();
        }
    }

    $pagetitle = $group->val('groupname')." > ".$form. " ". $_COMPANY->getAppCustomization()['chapter']["name-short"];

    $branches = $db->get("SELECT companybranches.`branchid`,companybranches.`branchname`,companybranches.`country`,companybranches.city, companybranches.state, regions.region FROM `companybranches` left join regions on companybranches.regionid=regions.regionid  WHERE companybranches.`companyid`='{$_COMPANY->id()}' AND companybranches.`regionid` IN (".$regionid.") AND companybranches.`isactive`='1'");

    $usedbranches = $db->get("SELECT `branchids` FROM `chapters` WHERE chapters.companyid='{$_COMPANY->id()}' AND ( chapters.zoneid='{$_ZONE->id()}' AND `groupid`='{$groupid}' AND chapterid != '{$chapterid}' AND `branchids` != 0)");
    $usedbranches_arr = array();
    foreach ($usedbranches as $usedbranch) {
        $usedbranches_arr = array_merge($usedbranches_arr, explode(',', $usedbranch['branchids']));
    }
    $usedbranches_arr = Arr::IntValues($usedbranches_arr);

    $branches_count = count($branches);

    $mybranches_arr = Arr::IntValues(explode(',', $mybranches)); // this array will be empty when adding chapter (i.e. chapterid == 0)

    for ($c = 0; $c < $branches_count; $c++) {
        $br_id = intval($branches[$c]['branchid']);
        if (in_array($br_id, $usedbranches_arr)) {
            $branches[$c]['alreadyUsed'] = $chapterid+100;
        } elseif (in_array($br_id, $mybranches_arr)) {
            $branches[$c]['alreadyUsed'] = $chapterid;
        } else {
            $branches[$c]['alreadyUsed'] = 0;
        }
    }
   //Include    
    include(__DIR__ . "/views/groups/chapter/add_edit_chapter_modal.template.php");
    
}
//
elseif (isset($_GET['add_update_chapter']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    
    if (($groupid = $_COMPANY->decodeId($_POST['gid']))<1 ||
        ($regionid = $_COMPANY->decodeId($_POST['rid']))<1 ||  
        ($chapterid = $_COMPANY->decodeId($_POST['cid']))<0 ||     
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }  
   
    // Add or Update
        $chaptername = 	Sanitizer::SanitizeGroupName($_POST['chapter_name']);
        $chaptercolor	= Sanitizer::SanitizeColor($_POST['chapter_color']);
        //$about 			= $_POST['about_chapter'];
        $branchids = '0';
    
        if (isset($_POST['branchids'])) {
            $branchids_arr  = array();
            foreach ($_POST['branchids'] as $br){
                $branchids_arr[] = $_COMPANY->decodeId($br);
            }
            $branchids = implode(',', array_unique($branchids_arr)) ?? '0';
        }
        # Virtual Event Address
        $virtual_event_location = $_POST['virtual_event_location'] ?? '';
        $latitude = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';   
     
        
        if ($chapterid){  
          $update = $group->updateChapter($chapterid,$chaptername,$chaptercolor,$branchids,     $virtual_event_location,$latitude,$longitude);         
            AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext('%s updated successfully.'),$_COMPANY->getAppCustomization()['chapter']["name-short"]), gettext('Success'));          
          
        } else {                     
            $leads = '';
            $add =  $group->addChapter($chaptername,$chaptercolor,$leads,$branchids,$regionid,$virtual_event_location,$latitude,$longitude);                        
            AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext(' New %s created successfully'),$_COMPANY->getAppCustomization()['chapter']["name-short"]), gettext('Success'));
        }  
}//end function.

elseif (isset($_GET['openNewChannelModel']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (!isset($_GET['gid']) ||
    ($groupid = $_COMPANY->decodeId($_GET['gid']))<1 ||
    ($channelid = $_COMPANY->decodeId($_GET['cid']))<0 ||    
    ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    //Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }   
    $encGroupId = $_COMPANY->encodeId($groupid);      
    $form = "Add";
    if ($channelid) {
        // Get the branches already assigned to the channel
        // Also get the regionid
        $form = "Edit";
        $edit = $group->getChannel($channelid, true);
        if (empty($edit)) {
            header(HTTP_BAD_REQUEST);
            exit();
        }
    }

    $pagetitle = $group->val('groupname')." > ".$form. " ". $_COMPANY->getAppCustomization()['channel']["name-short"];
    include(__DIR__ . "/views/groups/channel/add_edit_channel_modal.template.php");
}//end function.

elseif (isset($_GET['add_update_channel']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (!isset($_POST['gid']) ||
    ($groupid = $_COMPANY->decodeId($_POST['gid']))<1 ||
    ($channelid = $_COMPANY->decodeId($_POST['cid']))<0 ||
    ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    //Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $channelname = 	Sanitizer::SanitizeGroupName($_POST['channelname']);
    $colour	= Sanitizer::SanitizeColor($_POST['colour']);
    
    if ($channelid){
        $group->updateChannel($channelid, $channelname, $colour);
        AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext('%s Updated successfully'),$_COMPANY->getAppCustomization()['channel']["name-short"]), gettext('Success'));
    } else {
        $about = "About {$channelname} ...";
        $add =  $group->addChannel($channelname,$about, $colour);

        AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext('%s created successfully'),$_COMPANY->getAppCustomization()['channel']["name-short"]), gettext('Success'));
        
    }

}//end function.

## OK
elseif (isset($_GET['change_group_chapter_status']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
   
    $encodedGroupId =0;
    $encodedChapterId=0;
    $encodedRegionId=0;
    //Data Validation
    if (!isset($_POST['gid']) || !isset($_POST['status']) ||
        ($chapterid = $_COMPANY->decodeId($_GET['change_group_chapter_status']))<1 ||       
        ($groupid = $_COMPANY->decodeId($_POST['gid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

     // Authorization Check
     if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if(isset($_POST['rid'])){
        $regionid = $_COMPANY->decodeId($_POST['rid']);    
             
    }  

    $encodedChapterId = $_COMPANY->encodeId($chapterid );
    $encodedGroupId = $_COMPANY->encodeId($groupid);    
    $encodedRegionId = $_COMPANY->encodeId($regionid);   
    $status = (int)$_POST['status'];

    $group->changeChapterStatus($chapterid,$status);

	if($status==1){     

        $btns  = '<a href="javascript:void(0)" onclick="editChapterModal('."'{$encodedGroupId}','{$encodedChapterId}','{$encodedRegionId}'".');"> <i class="fa fa-edit" title="Edit"></i> </a>&nbsp;';  

        $btns .= '<a aria-label='.gettext("Deactivate").'  href="javascript:void(0)" class="deluser" onclick="changeGroupChapterStatus('."'{$encodedGroupId}','{$encodedChapterId}','{$encodedRegionId}'".',0,this)" title="<strong>Are you sure you want to Deactivate!</strong>"><i class="fa fa-lock" title="Deactivate" aria-hidden="true"></i></a>';
    }else{
        $btns  = '<a href="javascript:void(0)" onclick="editChapterModal('."'{$encodedGroupId}','{$encodedChapterId}','{$encodedRegionId}'".');"> <i class="fa fa-edit" title="Edit"></i> </a>&nbsp;';  

        $btns .= '<a aria-label='.gettext("Activate").' href="javascript:void(0)" class="deluser" onclick="changeGroupChapterStatus('."'{$encodedGroupId}','{$encodedChapterId}','{$encodedRegionId}'".',1,this)" title="<strong>Are you sure you want to Activate!</strong>"><i class="fa fa-unlock-alt" aria-hidden="true" title="Activate"></i></a>&nbsp;';
        
    }

	echo $btns;
}

elseif (isset($_GET['change_group_channel_status']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    
    //Data Validation
    if (!isset($_POST['gid']) || !isset($_POST['status']) ||
        ($channelid = $_COMPANY->decodeId($_GET['change_group_channel_status']))<1 ||
        ($groupid = $_COMPANY->decodeId($_POST['gid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $encodedChannelId = $_COMPANY->encodeId($channelid);
    $encodedGroupId = $_COMPANY->encodeId($groupid);
    $status = (int)$_POST['status'];

    $group->changeChannelStatus($channelid,$status);

	if($status==1){        
        
        $btns  = '<a href="javascript:void(0)" onclick="add_edit_channel_modal('."'{$encodedGroupId}','{$encodedChannelId}'".');"> <i class="fa fa-edit" title="Edit"></i> </a>&nbsp;'; 

        $btns .= '<a aria-label='.gettext("Deactivate").' href="javascript:void(0)" class="deluser" onclick="changeGroupChannelStatus('."'{$encodedGroupId}','{$encodedChannelId}'".',0,this)" title="<strong>Are you sure you want to Deactivate!</strong>"><i class="fa fa-lock" title="Deactivate" aria-hidden="true"></i></a>';
    }else{ // $status = 0

        $btns  = '<a href="javascript:void(0)" onclick="add_edit_channel_modal('."'{$encodedGroupId}','{$encodedChannelId}'".');"> <i class="fa fa-edit" title="Edit"></i> </a>&nbsp;'; 

        $btns .= '<a aria-label='.gettext("Activate").' href="javascript:void(0)" class="deluser" onclick="changeGroupChannelStatus('."'{$encodedGroupId}','{$encodedChannelId}'".',1,this)" title="<strong>Are you sure you want to Activate!</strong>"><i class="fa fa-unlock-alt" aria-hidden="true" title="Activate"></i></a>&nbsp;';
        
    }

	echo $btns;
}


## OK
elseif (isset($_GET['manageDashboard'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['manageDashboard']))<1 || ($group = Group::GetGroup($groupid)) === null ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);

    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //$membersCount = $group->getGroupMembersCount();
    $chapters 	= Group::GetChapterListDetail($groupid, true, true);
    if (!empty($chapters)){
        for($i=0;$i<count($chapters);$i++){
            $chapters[$i]['membersCount'] = $group->getChapterMembersCount($chapters[$i]['chapterid']);
        }
    }
    $channels= Group::GetChannelListDetail($groupid, true);

    if ($channels){
        for($i=0;$i<count($channels);$i++){
            $channels[$i]['membersCount'] = $group->getChannelMembersCount($channels[$i]['channelid']);
        }
    }

    //Dashboards Tiles
    $today	=	date("Y-m-d",strtotime(today()[0]));
    $weekEarlier = date("Y-m-d",strtotime("-7 day",strtotime(today()[0])));
    $group_chapters = 0;
    $group_channels = 0;
    $group_admin_1 = 0;
    $group_admin_2 = 0;
    $group_admin_3 = 0;
    $group_admin_4 = 0;
    $group_admin_5 = 0;
    $user_members_group = 0;
    $user_members_chapters = 0;
    $user_members_channels = 0;

    $events_draft = 0;
    $events_published = 0;
    $events_completed = 0;
    $posts_draft = 0;
    $posts_published = 0;
    $newsletters_draft= 0;
    $newsletters_published = 0;
    $resources_published = 0;
    $surveys_draft = 0;
    $surveys_published = 0;
    $album_media_published = 0;

    $total_active_teams = 0;
    $total_completed_teams = 0;

    $total_active_mentor = 0;
    $total_completed_mentor = 0;
    $total_registered_mentor = 0;

    $total_active_mentee = 0;
    $total_completed_mentee = 0;
    $total_registered_mentee = 0;

    #Chart Variable
    $groupTimeLabel = [];
    $c_group_chapters = [];
    $c_group_channels = [];
    $c_group_admin_1 = [];
    $c_group_admin_2 = [];
    $c_group_admin_3 = [];
    $c_group_admin_4 = [];
    $c_group_admin_5 = [];
    $c_user_members_group = [];
    $c_user_members_chapters = [];
    $c_user_members_channels = [];

    $c_events_draft = [];
    $c_events_published = [];
    $c_events_completed = [];
    $c_posts_draft = [];
    $c_posts_published = [];
    $c_newsletters_draft = [];
    $c_newsletters_published = [];
    $c_resources_published = [];
    $c_surveys_draft = [];
    $c_surveys_published = [];
    $c_album_media_published = [];
    $c_program_mentors = [];
    $c_program_mentees = []; 

    $y = date('Y');
    $m = date('m');
    $groupStats = array();
    $lastmonth = "";
    for ($i = 11; $i >= 0; $i--) {
        if($lastmonth){
            $lastmonth = date("Y-m", strtotime("next month",strtotime($lastmonth))); // Generate month in YYYY-mm- format
        } else {
            $lastmonth = date("Y-m", strtotime("-{$i} months",strtotime(date("Y-m-01"))));
        }
        $month = date("Y-m-",strtotime($lastmonth));
        
        $groupStatsMontyly = GroupStatistics::GetGroupMonthlyLatestStatistics($groupid,$month);
        [$totalMentors, $totalMentees] = $group->getProgramMentorMenteeStats($month);

        if (!empty($groupStatsMontyly)){
            $groupStatsRow = $groupStatsMontyly[0];
            $groupStatsAsOf  = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($groupStatsMontyly[0]['createdon'],true,true,true);
        } else {
            $groupStatsRow =  array(
                "stat_date" => date("Y-m-d", strtotime($month."01")),
                "companyid" => 0,
                "zoneid" => 0,
                "groupid" => 0,
                "group_chapters" => 0,
                "group_channels" => 0,
                "group_admin_1" => 0,
                "group_admin_2" => 0,
                "group_admin_3" => 0,
                "group_admin_4" => 0,
                "group_admin_5" => 0,
                "user_members_group" => 0,
                "user_members_chapters" => 0,
                "user_members_channels" => 0,
                "events_draft" => 0,
                "events_published" => 0,
                "events_completed" => 0,
                "posts_draft" => 0,
                "posts_published" => 0,
                "newsletters_draft" => 0,
                "newsletters_published" => 0,
                "resources_published" => 0,
                "surveys_draft" => 0,
                "surveys_published" => 0,
                "album_media_published" => 0,

                "teams_active" => 0,
                "teams_completed" => 0,
                "teams_not_completed" => 0,
                "teams_mentors_active" => 0,
                "teams_mentors_completed" => 0,
                "teams_mentors_not_completed" => 0,
                "teams_mentors_registered" => 0,
                "teams_mentees_active" => 0,
                "teams_mentees_completed" => 0,
                "teams_mentees_not_completed" => 0,
                "teams_mentees_registered" => 0,

                "groupTimeLabel" => date("M y", strtotime($month."01")),
                "createdon" => date("Y-m-d H:i:s", strtotime($month."01"))
            );
            if (!isset($groupStatsAsOf)){
                $groupStatsAsOf  = $_USER->formatUTCDatetimeForDisplayInLocalTimezone(date("Y-m-d"),true,true,true);
            }
        }  
        $groupStatsRow['total_mentors'] = $totalMentors;
        $groupStatsRow['total_mentees'] = $totalMentees;
        $groupStats[] = $groupStatsRow; 
    }

    if (!empty($groupStats)){
        usort($groupStats,function($a,$b) {
            return strcmp($a['stat_date'], $b['stat_date']);
        });

        // Override membership stats for the latest month with some real time values;
        $latest_entry = array_key_last($groupStats);

        $member_count_array = $group->getAllMembersCount();
        $groupStats[$latest_entry]['user_members_group'] = $member_count_array['user_members_group'];
        $groupStats[$latest_entry]['user_members_chapters'] = $member_count_array['user_members_chapters'];
        $groupStats[$latest_entry]['user_members_channels'] = $member_count_array['user_members_channels'];

        $teams_stats = $group->getAllTeamStats();
        $groupStats[$latest_entry]['teams_mentors_active'] = $teams_stats['teams_mentors_active'];
        $groupStats[$latest_entry]['teams_mentors_completed'] = $teams_stats['teams_mentors_completed'];
        $groupStats[$latest_entry]['teams_mentors_not_completed'] = $teams_stats['teams_mentors_not_completed'];
        $groupStats[$latest_entry]['teams_mentees_active'] = $teams_stats['teams_mentees_active'];
        $groupStats[$latest_entry]['teams_mentees_completed'] = $teams_stats['teams_mentees_completed'];
        $groupStats[$latest_entry]['teams_mentees_not_completed'] = $teams_stats['teams_mentees_not_completed'];
        $groupStats[$latest_entry]['teams_active'] = $teams_stats['teams_active'];
        $groupStats[$latest_entry]['teams_completed'] = $teams_stats['teams_completed'];
        $groupStats[$latest_entry]['teams_not_completed'] = $teams_stats['teams_not_completed'];
        $groupStats[$latest_entry]['teams_draft'] = $teams_stats['teams_draft'];
        $groupStats[$latest_entry]['teams_inactive'] = $teams_stats['teams_inactive'];
        $groupStats[$latest_entry]['teams_mentors_registered'] = $teams_stats['teams_mentors_registered'];
        $groupStats[$latest_entry]['teams_mentees_registered'] = $teams_stats['teams_mentees_registered'];

        // End of real time calculations

        $groupStats_end = end($groupStats);
        $group_chapters = $groupStats_end['group_chapters'] ?? 0;
        $group_channels = $groupStats_end['group_channels'] ?? 0;
        $group_admin_1 = $groupStats_end['group_admin_1'];
        $group_admin_2 = $groupStats_end['group_admin_2'];
        $group_admin_3 = $groupStats_end['group_admin_3'];
        $group_admin_4 = $groupStats_end['group_admin_4'];
        $group_admin_5 = $groupStats_end['group_admin_5'];
        $user_members_group = $groupStats_end['user_members_group'];
        $user_members_chapters = $groupStats_end['user_members_chapters'];
        $user_members_channels = $groupStats_end['user_members_channels'];
        $events_draft = $groupStats_end['events_draft'];
        $events_published = $groupStats_end['events_published'];
        $events_completed = $groupStats_end['events_completed'];
        $posts_draft = $groupStats_end['posts_draft'];
        $posts_published = $groupStats_end['posts_published'];
        $newsletters_draft = $groupStats_end['newsletters_draft'];
        $newsletters_published = $groupStats_end['newsletters_published'];
        $resources_published = $groupStats_end['resources_published'];
        $surveys_draft = $groupStats_end['surveys_draft'];
        $surveys_published = $groupStats_end['surveys_published'];
        $album_media_published = intval($groupStats_end['album_media_published']);

        $total_teams_active = $groupStats_end['teams_active'];
        $total_teams_completed = $groupStats_end['teams_completed'];
        $total_teams_not_completed = $groupStats_end['teams_not_completed'];
    
        $total_teams_mentors_active = $groupStats_end['teams_mentors_active'];
        $total_teams_mentors_completed = $groupStats_end['teams_mentors_completed'];
        $total_teams_mentors_not_completed = $groupStats_end['teams_mentors_not_completed'];
        $total_teams_mentors_registered = $groupStats_end['teams_mentors_registered'];
    
        $total_teams_mentees_active = $groupStats_end['teams_mentees_active'];
        $total_teams_mentees_completed = $groupStats_end['teams_mentees_completed'];
        $total_teams_mentees_not_completed = $groupStats_end['teams_mentees_not_completed'];
        $total_teams_mentees_registered = $groupStats_end['teams_mentees_registered'];

        #Chart
        $groupTimeLabel = array_column($groupStats,'groupTimeLabel');
        $c_group_chapters = array_column($groupStats,'group_chapters');
        $c_group_channels = array_column($groupStats,'group_channels');
        $c_group_admin_1 = array_column($groupStats,'group_admin_1');
        $c_group_admin_2 = array_column($groupStats,'group_admin_2');
        $c_group_admin_3 = array_column($groupStats,'group_admin_3');
        $c_group_admin_4 = array_column($groupStats,'group_admin_4');
        $c_group_admin_5 = array_column($groupStats,'group_admin_5');
        $c_user_members_group = array_column($groupStats,'user_members_group');
        $c_user_members_chapters = array_column($groupStats,'user_members_chapters');
        $c_user_members_channels = array_column($groupStats,'user_members_channels');
        $c_events_draft = array_column($groupStats,'events_draft');
        $c_events_published = array_column($groupStats,'events_published');
        $c_events_completed = array_column($groupStats,'events_completed');
        $c_posts_draft = array_column($groupStats,'posts_draft');
        $c_posts_published = array_column($groupStats,'posts_published');
        $c_newsletters_draft = array_column($groupStats,'newsletters_draft');
        $c_newsletters_published = array_column($groupStats,'newsletters_published');
        $c_resources_published = array_column($groupStats,'resources_published');
        $c_surveys_draft = array_column($groupStats,'surveys_draft');
        $c_surveys_published = array_column($groupStats,'surveys_published');
        $c_album_media_published = array_column($groupStats,'album_media_published');

        $c_total_teams_active = array_column($groupStats,'teams_active');
        $c_total_teams_completed = array_column($groupStats,'teams_completed');
        $c_total_teams_not_completed = array_column($groupStats,'teams_not_completed');

        $c_total_teams_mentors_active = array_column($groupStats,'teams_mentors_active');
        $c_total_teams_mentors_completed = array_column($groupStats,'teams_mentors_completed');
        $c_total_teams_mentors_not_completed = array_column($groupStats,'teams_mentors_not_completed');
        $c_total_teams_mentors_registered = array_column($groupStats,'teams_mentors_registered');
    
        $c_total_teams_mentees_active = array_column($groupStats,'teams_mentees_active');
        $c_total_teams_mentees_completed = array_column($groupStats,'teams_mentees_completed');
        $c_total_teams_mentees_not_completed = array_column($groupStats,'teams_mentees_not_completed');
        $c_total_teams_mentees_registered = array_column($groupStats,'teams_mentees_registered');
    }
    $teamsCustomName = Team::GetTeamCustomMetaName($group->getTeamProgramType(),1);
    include(__DIR__ . "/views/templates/manage_section_dynamic_button.html");
    include(__DIR__ . "/views/templates/manage_dashboard.template.php");
}

elseif (isset($_GET['mangeChapterLeads'])){  
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['mangeChapterLeads']))<1  ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

  
    $filterChapterId = 0;
    // Condition to get chapter id
    if(!empty($_GET['chapter_filter'])){
        $filterChapterId = $_COMPANY->decodeId($_GET['chapter_filter']);
    }
    
    $encGroupId = $_COMPANY->encodeId($groupid);

    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $canManageGroup = $_USER->canManageGroup($groupid);
    $chapters = Group::GetChapterList($groupid);
    $types = $group->GetGroupleadTypes();
    $chapterLeads = $group->getChapterLeads($filterChapterId);
    for ($c = 0; $c < count($chapterLeads); $c++) {
        if (empty($chapterLeads[$c]['grouplead_typeid'])){
            $chapterLeads[$c]['rolename'] = '';
            $chapterLeads[$c]['permissions'] = array();
        } else {
            $chapterLeads[$c]['rolename'] = $types[$chapterLeads[$c]['grouplead_typeid']]['type'];
            $chapterLeads[$c]['permissions'] = $types[$chapterLeads[$c]['grouplead_typeid']]['permissions'];
        }   
    }
    
    $systemLeadType  = Group::SYS_GROUPLEAD_TYPES;
 	include(__DIR__ . "/views/templates/group_chapter_leads.template.php");
}

elseif (isset($_GET['mangeGroupMemberList'])){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['mangeGroupMemberList']))<1  ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
 	include(__DIR__ . "/views/templates/group_members.template.php");
}

elseif (isset($_GET['processCommunicationData']) && isset($_GET['chapterid']) && isset($_GET['trigger']) ){
    
    $chapterid = 0;
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['processCommunicationData']))<0 || 
        ($chapterid = $_COMPANY->decodeId($_GET['chapterid']))<0 || 
        ($communication_trigger = $_COMPANY->decodeId($_GET['trigger']))<1 ||
        ($group = Group::GetGroup($groupid)) === null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!($_USER->canManageGroupSomething($groupid))|| //This is a macro check, chapter level check later
        !$_COMPANY->getAppCustomization()['communications']['enabled']) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $encGroupId = $_COMPANY->encodeId($groupid);
    $encChapterid = $_COMPANY->encodeId($chapterid);
    $encTrigger = $_COMPANY->encodeId($communication_trigger);
    $endSection = $_GET['section'];

    $section = $_COMPANY->decodeId($endSection);
    $scope  = "global";

    if ($chapterid > 0) {
        if ($section == 1){
            $scope  = "chapter";
        } elseif ($section ==2){
            $scope  = "channel";
        }
    }
    
    $data = $group->getCommunicationTemplatesByTrigger($scope, $chapterid, $communication_trigger);
    if (count($data)>0){
        if ($data[0]['isactive'] != 1) { ?>
          <div class="text-center">
            <button class="btn btn-affinity" type="button"  onclick=previewCommunicationTemplate("<?= $encGroupId; ?>","<?= $_COMPANY->encodeId($data[0]['communicationid']); ?>") ><?= gettext('Preview'); ?></button>
            &ensp;
            <button class="btn btn-affinity deluser"  data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to activate?'); ?>" type="button"  onclick=activateCommunicationTemplate("<?= $encGroupId; ?>","<?= $_COMPANY->encodeId($data[0]['communicationid']); ?>") ><?= gettext('Activate'); ?></button>
            &ensp;
            <button class="btn btn-affinity" type="button" onclick=loadUpdateCommunicationForm("<?= $encGroupId; ?>","<?= $_COMPANY->encodeId($data[0]['communicationid']); ?>")><?= gettext('Edit'); ?></button>
            &ensp;
             <button class="btn btn-affinity deluser" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" type="button" title="<?= gettext('Are you sure you want to delete?'); ?>" onclick=deleteCommunicationTemplate("<?= $encGroupId; ?>","<?= $_COMPANY->encodeId($data[0]['communicationid']); ?>")><?= gettext('Delete'); ?></button>
        </div>
        <?php } else { ?>
        <div class="text-center">
            <button class="btn btn-affinity" type="button" onclick=previewCommunicationTemplate("<?= $encGroupId; ?>","<?= $_COMPANY->encodeId($data[0]['communicationid']); ?>") ><?= gettext('Preview'); ?></button>
            &ensp;
            <button class="btn btn-affinity deluser" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>"  title="<?= gettext('Are you sure you want to deactivate?'); ?>" type="button"  onclick=deactivateCommunicationTemplate("<?= $encGroupId; ?>","<?= $_COMPANY->encodeId($data[0]['communicationid']); ?>") ><?= gettext('Deactivate'); ?></button>
        </div>

<?php
}
    } else {
        echo "<div class='text-center'><button class='btn btn-affinity' type='button' onclick=loadNewCommunicationForm('".$encGroupId."','".$encChapterid."','".$encTrigger."')>".gettext('Create')."</button></div>";
    }
    exit();
}

elseif (isset($_GET['loadNewCommunicationForm']) && isset($_GET['chapterid']) && isset($_GET['trigger'])){
	$encGroupId = $_GET['loadNewCommunicationForm'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<0 ||
        ($trigger = $_COMPANY->decodeId($_GET['trigger']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!($_USER->canManageGroupSomething($groupid))|| //This is a macro check, chapter level check later
        !$_COMPANY->getAppCustomization()['communications']['enabled']) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $templates = '';
    $anniversaryTriggers = array_keys(Group::GROUP_COMMUNICATION_ANNIVERSARSY_TRIGGER_TO_INTERVAL_DAY_MAP); // Extract valid anniversay triggers from map
    $templates  = in_array($trigger, $anniversaryTriggers) ? Template::GetTemplatesByTemplateTypes(Template::TEMPLATE_TYPE_COMMUNICATION_ANNIVERSARY) :  Template::GetTemplatesByTemplateTypes(Template::TEMPLATE_TYPE_COMMUNICATION);
    
    include(__DIR__ . "/views/templates/group_new_communication.template.php");
	exit();
}

elseif (isset($_GET['createCommunicationTemplate']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	$encGroupId = $_GET['createCommunicationTemplate'];
    $accessDenied = 0;
    $retVal['status'] = 0;

    if ( empty($_POST['chapterid'])){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please select a group or chapter to continue.'), gettext('Error'));
    }

    if ( empty($_POST['communication_trigger'])){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('In order to submit, please select a communication trigger. A communication trigger will be the time/action that will cause the communication to send (i.e. a welcome email will automatically be sent if the communication trigger is "on join").'), gettext('Error'));
    }

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<0 ||
        !isset($_POST['chapterid']) ||
        !isset($_POST['templateid']) ||
        !isset($_POST['communication_trigger']) ||
        !isset($_POST['emailsubject']) ||
        ($cid = $_COMPANY->decodeId($_POST['chapterid']))<0 ||
        ($communication_trigger = $_COMPANY->decodeId($_POST['communication_trigger']))<1 ||
        ($templateid = $_COMPANY->decodeId($_POST['templateid']))<0) { // Chapter id can be 0
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $section = (int)$_COMPANY->decodeId($_POST['section']);
    $chapterid = ($section == 1) ? $cid : 0;
    $channelid = ($section == 2) ? $cid : 0;

    if (!$_USER->canManageContentInScopeCSV($groupid,$chapterid,$channelid) ||
        !$_COMPANY->getAppCustomization()['communications']['enabled']) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    // Anniversary interval
    $anniversary_interval = 0; // Default is Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_ANNIVERSARY']


    $anniversary_interval = Group::GROUP_COMMUNICATION_ANNIVERSARSY_TRIGGER_TO_INTERVAL_DAY_MAP[$communication_trigger]??null;

    $email_cc_list = '';
    if(!empty($_POST['email_cc_list'])){
        $ccEmailsList= $_POST['email_cc_list'];
        $validEmails = array();
        $invalidEmails = array();
        $e_arr = extractEmailsFrom ($ccEmailsList);

        if(count($e_arr) == 0){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter valid CC Email'), gettext('Error'));
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
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Invalid emails: %s'),implode(', ', $invalidEmails)), gettext('Error'));
        }

        if (count($validEmails) > 3) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('There are too many emails entered. You cannot submit more than three emails.'),count($validEmails)), gettext('Error'));
        }

        $email_cc_list = implode(',', $validEmails);
    }

    $communication = ViewHelper::FixRevolvAppContentForOutlook($_POST['communication']);

    $template = $_POST['template'];
    $emailsubject = $_POST['emailsubject'];

    $check = array('Subject' => @$emailsubject);
    $checkRequired = $db->checkRequired($check);
    if($checkRequired){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$checkRequired), gettext('Error'));
    }

    $field = ($section == 2) ? "channelid" : "chapterid";


    $send_upcoming_events_email = 0;
    if (isset($_POST['send_upcoming_events_email'])){
        $send_upcoming_events_email = (int)$_POST['send_upcoming_events_email'];
    }

    if ($comm_id = Group::CreateCommunicationTemplate($groupid, $field, $cid, $communication_trigger, $templateid, $template, $communication, $emailsubject,$email_cc_list,$send_upcoming_events_email, $anniversary_interval)) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Communication template created successfully.'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Internal server error'), gettext('Error'));
    }
}

elseif (isset($_GET['loadUpdateCommunicationForm']) && isset($_GET['communicationid'])){
	$encGroupId = $_GET['loadUpdateCommunicationForm'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<0 ||
        ($communicationid = $_COMPANY->decodeId($_GET['communicationid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($communication = $group->getCommunicationTemplateDetail($communicationid)) === null

        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
	$encCommunicationid = $_GET['communicationid'];

    if (!$_USER->canManageContentInScopeCSV($groupid,$communication['chapterid'],$communication['channelid']) ||
        !$_COMPANY->getAppCustomization()['communications']['enabled']) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $template = str_replace("'","\'",$communication['template']);
    $trigger = $communication['communication_trigger'];
    $emailsubject = $communication['emailsubject'];
    $email_cc_list = $communication['email_cc_list'];
    $curr_end_upcoming_events_email = (int)$communication['send_upcoming_events_email'];

    include(__DIR__ . "/views/templates/group_update_communication.template.php");
	
}

elseif (isset($_GET['updateCommunicationTemplate']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	$encGroupId = $_GET['updateCommunicationTemplate'];
    $accessDenied = 0;
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        !isset($_POST['communicationid']) ||
        !isset($_POST['emailsubject']) ||
        ($communicationid = $_COMPANY->decodeId($_POST['communicationid']))<1 ||
        ($communication = $group->getCommunicationTemplateDetail($communicationid)) === null
        ) { // Chapter id can be 0
        header(HTTP_BAD_REQUEST);
        exit();
    }
     // Authorization Check
    $section = $_COMPANY->decodeId($_POST['section']);

    if (!$_USER->canManageContentInScopeCSV($groupid,$communication['chapterid'],$communication['channelid']) ||
        !$_COMPANY->getAppCustomization()['communications']['enabled']) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $email_cc_list = '';
    if(!empty($_POST['email_cc_list'])){
        $ccEmailsList= $_POST['email_cc_list'];
        $validEmails = array();
        $invalidEmails = array();
        $e_arr = extractEmailsFrom ($ccEmailsList);
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
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Invalid emails: %s'),implode(', ', $invalidEmails)), gettext('Error'));
        }

        if (count($validEmails) > 3) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('There are too many emails entered. You cannot submit more than three emails.'),count($validEmails)), gettext('Error'));
        }

        $email_cc_list = implode(',', $validEmails);
    }
   

    $communication = ViewHelper::FixRevolvAppContentForOutlook($_POST['communication']);

    $emailsubject = $_POST['emailsubject'];

    $check = array('Subject' => @$emailsubject);
    $checkRequired = $db->checkRequired($check);
    if($checkRequired){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$checkRequired), gettext('Error'));
    }

    $template = $_POST['template'];
    $field = ($section == 2) ? "channelid" : "chapterid";

    $send_upcoming_events_email = 0;
    if (isset($_POST['send_upcoming_events_email'])){
        $send_upcoming_events_email = (int)$_POST['send_upcoming_events_email'];
    }

    if (Group::UpdateCommunicationTemplate($communicationid, $groupid, $template, $communication, $emailsubject,$email_cc_list,$send_upcoming_events_email)) {
        $retVal['status'] = 1;
        AjaxResponse::SuccessAndExit_STRING(1, $_COMPANY->encodeId($communicationid), gettext('Success'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Internal server error'), gettext('Error'));
    }
}

elseif (isset($_GET['previewCommunicationTemplate'])) {

	$encGroupId = $_GET['previewCommunicationTemplate'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($communicationid = $_COMPANY->decodeId($_GET['communicationid']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!($_USER->canManageGroupSomething($groupid))|| //This is a macro check, chapter level check later
        !$_COMPANY->getAppCustomization()['communications']['enabled']) {
        header(HTTP_FORBIDDEN);
        exit();
	}

	$template = $group->getCommunicationTemplateDetail($communicationid);
	if ($template ){
        $chapterid = $template['chapterid'];
        $chaptername = Group::GetChapterName($chapterid,$groupid)['chaptername'];
        $groupname = $group->val('groupname');
        $grouplogo = $group->val('groupicon');
        $groupcolor = rgb_to_hex($group->val('overlaycolor'));
        $groupcolor2 = rgb_to_hex($group->val('overlaycolor2'));
        $companyname = $_COMPANY->val('companyname');
        $companylogo = $_COMPANY->val('logo');
        $companyurl = $_COMPANY->getAppURL($_ZONE->val('app_type'));
        $groupurl = $group->getShareableUrl();
        $person_firstname = $_USER->val('firstname');
        $person_lastname = $_USER->val('lastname');
        $person_name = $_USER->getFullName();
        $person_email = $_USER->val('email');
        $publish_date = 'PUBLISH DATE';
        $replace_vars = ['[%COMPANY_NAME%]','COMPANY_LOGO','[%GROUP_NAME%]', 'GROUP_LOGO', '[%CHAPTER_NAME%]', '[%COMPANY_URL%]', '[%GROUP_URL%]', '[%RECIPIENT_NAME%]', '[%RECIPIENT_FIRST_NAME%]', '[%RECIPIENT_LAST_NAME%]', '[%RECIPIENT_EMAIL%]', '[%PUBLISH_DATE_TIME%]', '#000001','#000002'];
        $replacement_vars = [$companyname, $companylogo, $groupname, $grouplogo, $chaptername,$companyurl, $groupurl, $person_name, $person_firstname, $person_lastname, $person_email, $publish_date, $groupcolor, $groupcolor2];

        $template = str_replace($replace_vars,$replacement_vars,$template);

        include(__DIR__ . "/views/templates/automated_email.template.php");
	} else {
		AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Template not found, please try again.'), gettext('Error'));
	}
}

elseif (isset($_GET['manageDiscussionSettings'])) {
        
        //Data Validation
        if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
        }
        // Authorization Check
        if (!$_USER->canManageGroupSomething($groupid)) {
            header(HTTP_FORBIDDEN);
            exit();
        }

        $discussionSettings = $group->getDiscussionsConfiguration();   
        include(__DIR__ . "/views/templates/discussion_settings_modal.template.php");
       
}

elseif (isset($_GET['updateDiscussionsConfiguration']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
    ($group = Group::GetGroup($groupid)) === null) {
    header(HTTP_BAD_REQUEST);
    exit();
    }
    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $allow_anonymous_post = $_POST['allow_anonymous_post'] === 'true'? true: false;
    $allow_email_publish = false;
    if(!$_COMPANY->getAppCustomization()['discussions']['disable_email_publish']){ 
        $allow_email_publish = $_POST['allow_email_publish'] === 'true'? true: false;
    }
    $who_can_post = $_POST['who_can_post'] === 'members' ? 'members' : 'leads';
    $retVal = 0;
    if($group->updateDiscussionsConfiguration($who_can_post,$allow_anonymous_post,$allow_email_publish)){
        $retVal = 1;
    }
    echo $retVal;
    exit();
}

elseif (isset($_GET['activateCommunicationTemplate']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	$encGroupId = $_GET['activateCommunicationTemplate'];
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($encGroupId))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        !isset($_POST['communicationid']) ||
        ($communicationid = $_COMPANY->decodeId($_POST['communicationid']))<1 ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
     // Authorization Check
     if (!($_USER->canManageGroupSomething($groupid))|| //This is a macro check, chapter level check later
        !$_COMPANY->getAppCustomization()['communications']['enabled']) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    if( $group->activateGroupCommunicationTemplate($communicationid )){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Your auto-communication has been successfully activated'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong, please try again.'), gettext('Error'));
    }
}

elseif (isset($_GET['deactivateCommunicationTemplate']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	$encGroupId = $_GET['deactivateCommunicationTemplate'];
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($encGroupId))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        !isset($_POST['communicationid']) ||
        ($communicationid = $_COMPANY->decodeId($_POST['communicationid']))<1 ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
     // Authorization Check
     if (!($_USER->canManageGroupSomething($groupid))|| //This is a macro check, chapter level check later
        !$_COMPANY->getAppCustomization()['communications']['enabled']) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($group->deActivateGroupCommunicationTemplate($communicationid)){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Your auto-communication has been successfully deactivated'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong, please try again.'), gettext('Error'));
    }
}

elseif (isset($_GET['deleteCommunicationTemplate']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	$encGroupId = $_GET['deleteCommunicationTemplate'];
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($encGroupId))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        !isset($_POST['communicationid']) ||
        ($communicationid = $_COMPANY->decodeId($_POST['communicationid']))<1 ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
     // Authorization Check
     if (!($_USER->canManageGroupSomething($groupid))|| //This is a macro check, chapter level check later
        !$_COMPANY->getAppCustomization()['communications']['enabled']) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    if ($group->deleteGroupCommunicationTemplate($communicationid)){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Template deleted successfully'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('No template found. Please try again.'), gettext('Error'));
    }
}

elseif (isset($_GET['manageCommunicationsTemplates'])){
    $encGroupId = $_GET['manageCommunicationsTemplates'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<0 ||
        ($group = Group::GetGroup($groupid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!($_USER->canManageGroupSomething($groupid))|| //This is a macro check, chapter level check later
        !$_COMPANY->getAppCustomization()['communications']['enabled']) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $headtitle	= gettext("Communications");

    $chapters = $groupid ? Group::GetChapterList($groupid) : array();
    $channels= $groupid ? Group::GetChannelList($groupid) : array();
    include(__DIR__ . "/views/templates/group_communications.template.php");
}

elseif (isset($_GET['confirmDeleteMyAccount']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if ($_POST['confirm'] == 'Delete'){
        $delete =  $_USER->wipeClean();
        if ($delete){
            setcookie( session_name(), '', time()-3600, '/');
            $_SESSION = array();
            session_destroy();
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Your account has been marked for deletion and will be permanently deleted, including all information, after thirty days.'), gettext('Success'));
        } else{
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Error'));
        }
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please type 'Delete' in text box to provide your consent to delete your account."), gettext('Error'));
    }
}

elseif (isset($_GET['loadMoreMembers'])){
    $encGroupId = $_GET['loadMoreMembers'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['loadMoreMembers']))<1
        || ($group = Group::GetGroup($groupid)) === null
        || ($chapterid = $_COMPANY->decodeId($_GET['chapterid']))<0
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canViewContent($groupid)
        || ($group->val('about_show_members') == '0')
        || ($group->val('about_show_members') == '2' && !$_USER->isGroupMember($groupid))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $page = (int)$_GET['page'];
    $start 		=	($page)*60;

    $condition = "";
    if($chapterid>0){
        $condition = " AND FIND_IN_SET(".$chapterid.",a.chapterid)";
    }

    $members= $db->get("SELECT a.*,b.firstname,b.lastname,b.jobtitle,b.picture FROM `groupmembers` a JOIN users b ON b.userid=a.userid WHERE b.companyid='{$_COMPANY->id()}' AND (a.`groupid`='{$groupid}' $condition AND a.`isactive`='1' AND b.`isactive`='1' AND a.`anonymous`='0') order by b.firstname ASC LIMIT $start,60");
    if (count($members)>0){
        for($i=0;$i<count($members);$i++){
            $encMemberUserID = $_COMPANY->encodeId($members[$i]['userid']);
            $name = trim($members[$i]['firstname'].' '.$members[$i]['lastname']);
            $profilepic = User::BuildProfilePictureImgTag($members[$i]['firstname'], $members[$i]['lastname'], $members[$i]['picture'], 'memberpic');
        ?>
            <div class="col-md-4">
                <div class="member-card" onclick="getProfileDetailedView(this,{'userid':'<?= $encMemberUserID; ?>'})">
                    <?= $profilepic; ?>
                    <p class="member_name"><?= $name; ?></p>
                    <p class="member_jobtitle" style=" font-size:small;"><?= $members[$i]['jobtitle']; ?></p>
                </div>
            </div>
    <?php	}

    } else {
        echo 0;
    }
}

elseif (isset($_GET['changePrivacySetting']) && $_SERVER['REQUEST_METHOD'] === 'POST'){ //Return 0 on error, 1 on removal, 2 on addition
    //Data Validation
    if (($memberid = $_COMPANY->decodeId($_POST['memberid']))<0 ||
        !isset($_POST['value']) ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    // Authorization check not needed on self changes.

	$anonymous = (int)($_POST['value']);

    if ($_USER->changeGroupMembershipPrivacySetting($memberid, $anonymous)){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Privacy setting updated successfully.'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Error'));
    }
}


elseif (isset($_GET['getProfileDetailedView']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($requested_userid = $_COMPANY->decodeId($_POST['userid']))<0 ||($requested_user = User::GetUser($requested_userid)) === null ) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('User details are not available for the selected user'), '');
    }

    $name = $requested_user->getFullName(true);
    $name = $name ?: 'Not Set';
    $alt_tag = $requested_user->getFullName().' Profile Picture';
    $profilepic = User::BuildProfilePictureImgTag($requested_user->val('firstname'), $requested_user->val('lastname'), $requested_user->val('picture'), 'profile',$alt_tag, $requested_user->id(), null);
    
    $banner	  = $_ZONE->val('banner_background');
    $profile_info_depth = $_POST['profile_detail_level'] ?? 'profile_basic';

    include(__DIR__ . "/views/templates/profile.template.php");
}

elseif(isset($_GET['getFollowChapterChannel'])){
	$encGroupId = $_GET['getFollowChapterChannel'];
    //Data Validation
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $action = $_GET['action'];
    $group_name = $group->val('groupname');
    $all_chapter= Group::GetChapterList($groupid);
    $channels= Group::GetChannelList($groupid);
    $encodedFollowedChapters = [];
    $encodedFollowedChannels = [];
    $joinSuccessMsg = "";
    $anonymous = (int)$_GET['anonymous'] ?: 0;

    if (count($all_chapter) || count($channels) || $group->isTeamsModuleEnabled()){

        // Join Group automatically as per #1795 - Fixes
        if (!$_USER->isGroupMember($groupid)) {
            $_USER->joinGroup($groupid,0,0, $anonymous);

            if (in_array($group->val('chapter_assign_type'), ['by_user_atleast_one', 'by_user_exactly_one'])) {
                if (($k1 = array_search($groupid, $_SESSION['chapter_validation_done'] ?? [])) !== false) {
                    unset($_SESSION['chapter_validation_done'][$k1]);
                }
            }

            $chapterChannelLabel = '';
            if (count($all_chapter) && $group->val('chapter_assign_type') !=  'auto') {
                $chapterChannelLabel = $_COMPANY->getAppCustomization()['chapter']['name-short'];
            }
            if (count($channels)) {
                $chapterChannelLabel = $chapterChannelLabel ? $chapterChannelLabel . '/' : '';
                $chapterChannelLabel = $chapterChannelLabel . $_COMPANY->getAppCustomization()['channel']['name-short'];
            }
            if (empty($chapterChannelLabel)) {
                $joinSuccessMsg = sprintf(gettext('Your %s join request has been processed successfully!'), $_COMPANY->getAppCustomization()['group']['name-short']);
            } else {
                $joinSuccessMsg = sprintf(gettext('Your %s join request has been processed successfully! You can join %s from the options below.'), $_COMPANY->getAppCustomization()['group']['name-short'], $chapterChannelLabel);
            }
        }

        $followed_chapters = [];
        $followed_channels = [];

        // Auto Assign Chapter
        $autoAssign = null;

        $check	= $_USER->getGroupMembershipDetail($groupid);

        if ($group->val('chapter_assign_type')!='by_user_any' && count($all_chapter)>0 && $_USER->val('homeoffice') > 0 && $action ==1) {

            if ($group->val('chapter_assign_type') ==  'auto'){ // Chapter auto assignment

                if (empty($check) || $check['chapterid']=='0'){  //Check for existing membership
                    foreach ($all_chapter as $chapter) {
                        $branchids = explode(',', $chapter['branchids']);

                        if (in_array($_USER->val('homeoffice'), $branchids)) {
                            $_USER->joinGroup($groupid, (int)$chapter['chapterid'],0, $anonymous);
                            $autoAssign[] = $chapter['chaptername'];
                        }
                    }
                }
            }
            // } elseif($group->val('chapter_assign_type')=='by_user_atleast_one' || $group->val('chapter_assign_type')=='by_user_exactly_one'){ // Need to join at least one chapter or exactly one chapter
            //     include(__DIR__ . "/views/templates/join_at_least_one_chapter.template.php");
            //     exit();
            // }
        }
        // Load updated membership data if auto assigned
        $f	= $_USER->getGroupMembershipDetail($groupid);
        
        if (!empty($f)){
            $followed_chapters	= explode(',', $f['chapterid']);
            foreach($followed_chapters as $chapterId){
                $encodedFollowedChapters[] = $_COMPANY->encodeId($chapterId);
            }

            $followed_channels	= explode(',', $f['channelids']);
            foreach($followed_channels as $channelId){
                $encodedFollowedChannels[] = $_COMPANY->encodeId($channelId);
            }
        }
        include(__DIR__ . "/views/templates/follow_chapter_channel.template.php");
    } else {
        if ($_USER->isGroupMember($groupid)) {
            if ($_USER->getWhyCannotLeaveGroup($groupid, 0, 0) === 'LEADER_CANNOT_LEAVE_MEMBERSHIP') {
                AjaxResponse::SuccessAndExit_STRING(
                    0,
                    '',
                    sprintf(gettext('You are assigned a leadership role in this %1$s, so cannot leave the %1$s'), $_COMPANY->getAppCustomization()['group']['name-short']),
                    gettext('Error')
                );
            }

            if ($_USER->leaveGroup($groupid, 0, 0)) {
                $message = gettext('Group left successfully.');
                if ($group->isContentRestrictedToMembers()) {
                    $message .= ' ' . gettext('This page will refresh to show the updated content.');
                    AjaxResponse::SuccessAndExit_STRING(11, '', $message, gettext('Success'));
                } else {
                    AjaxResponse::SuccessAndExit_STRING(1, '', $message, gettext('Success'));
                }
            } else {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Error'));
            }
        } else {
            if ($_USER->joinGroup($groupid,0,0,$anonymous)) {
                AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Group joined successfully.'), gettext('Success'));
            } else{
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Error'));
            }
        }
    }
}

elseif(isset($_GET['followUnfollowGroupchapter']) && $_SERVER['REQUEST_METHOD'] === 'POST'){ //Return 0 on error, 1 on removal, 2 on addition
	$encGroupId = $_POST['groupid'];
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($encGroupId))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    if (isset($_POST['chapterIds']) && is_array($_POST['chapterIds'])) {
        $chapterIds = $_POST['chapterIds'];
    } elseif (!empty($_POST['chapterIds'])) {
        $chapterIds = array($_POST['chapterIds']);
    } else {
        $chapterIds = array();
    }

    $joined = 0;
    $existing_membership = $_USER->getGroupMembershipDetail($groupid);
    $existing_chapters = empty($existing_membership) ? array() : explode(',', $existing_membership['chapterid']);

    if (in_array($group->val('chapter_assign_type'), ['by_user_atleast_one', 'by_user_exactly_one']) && count($chapterIds) <1 && !empty($existing_chapters)) { // Check for at least one chapter need to join
        $enc_existing_chapters = array();
        foreach ($existing_chapters as $chapterid) {
            $enc_existing_chapters[] = $_COMPANY->encodeId($chapterid);
        }

        $error_message = gettext('You need to join a chapter');
        if ($group->val('chapter_assign_type') === 'by_user_atleast_one') {
            $error_message = gettext('You need to join at least one chapter!');
        }

        AjaxResponse::SuccessAndExit_STRING(0, implode(',', $enc_existing_chapters), $error_message, gettext('Error'));
    }

        $successfully_joined_chapters = [];
        if (!empty($chapterIds)){
            foreach($chapterIds as $chapterid){
                $chapterid = $_COMPANY->decodeId($chapterid);
                if (($key = array_search($chapterid, $existing_chapters)) !== false) {
                    unset($existing_chapters[$key]);
                    continue;
                }
                if ($_USER->joinGroup($groupid,$chapterid,0)) {
                    $successfully_joined_chapters[] = $chapterid;
                    $joined = $joined+1;
                }
            }
        }

        $successfully_left_chapters = [];
        $unsuccessfully_left_chapters = [];
        foreach ($existing_chapters as $chapterid) {
            if ($chapterid != 0) {
                if ($_USER->leaveGroup($groupid,$chapterid,0)) {
                    $successfully_left_chapters[] = $chapterid;
                } else {
                  if ($_USER->getWhyCannotLeaveGroup($groupid, $chapterid, 0) === 'LEADER_CANNOT_LEAVE_MEMBERSHIP') {
                    $unsuccessfully_left_chapters[] = $chapterid;
        }
                }
            }
        }

        $modal_status = 1;
        $modal_title = gettext('Success');
        $modal_body = gettext('Your request has been processed successfully!');
        $modal_val = gettext('<strong>Leave</strong>');

        if (count($unsuccessfully_left_chapters)) {
            $all_chapter = Group::GetChapterList($groupid);
            $all_chapter = array_column($all_chapter, null, 'chapterid');

            $successfully_joined_chapters_names = implode(', ', array_map(function ($chapterid) use ($all_chapter) {
                return $all_chapter[$chapterid]['chaptername'];
            }, $successfully_joined_chapters));

            $successfully_left_chapters_names = implode(', ', array_map(function ($chapterid) use ($all_chapter) {
                return $all_chapter[$chapterid]['chaptername'];
            }, $successfully_left_chapters));

            $unsuccessfully_left_chapters_names = implode(', ', array_map(function ($chapterid) use ($all_chapter) {
                return $all_chapter[$chapterid]['chaptername'];
            }, $unsuccessfully_left_chapters));

            if (count($successfully_left_chapters) || count($successfully_joined_chapters)) {
                $modal_status = 0;
                $modal_title = gettext('Result');
                $modal_body = '<ul>';
                if (count($successfully_joined_chapters)) {
                    $modal_body .= '<li>' . sprintf(gettext('Successfully joined %s'), $successfully_joined_chapters_names) . '</li>';
                }

                if (count($successfully_left_chapters)) {
                    $modal_body .= '<li>' . sprintf(gettext('Succesfully left %s'), $successfully_left_chapters_names) . '</li>';
                }

                if (count($unsuccessfully_left_chapters)) {
                    $modal_body .= '<li>' . sprintf(gettext('Could not leave %s as you are a leader there'), $unsuccessfully_left_chapters_names) . '</li>';
                }

                $modal_body .= '</ul>';
            } else {
                $modal_status = 0;
                $modal_title = gettext('Error');
                $modal_body = sprintf(gettext('Could not leave %s as you are a leader there'), $unsuccessfully_left_chapters_names);
            }

            $existing_membership = $_USER->getGroupMembershipDetail($groupid);
            $existing_chapters = empty($existing_membership) ? array() : explode(',', $existing_membership['chapterid']);
            $modal_val = implode(',', array_map(function ($chapterid) {
                global $_COMPANY;
                return $_COMPANY->encodeId($chapterid);
            }, $existing_chapters));
        }

        AjaxResponse::SuccessAndExit_STRING($modal_status, $modal_val, $modal_body, $modal_title);
    }

elseif(isset($_GET['followUnfollowChannel']) && $_SERVER['REQUEST_METHOD'] === 'POST'){ //Return 0 on error, 1 on removal, 2 on addition
	$encGroupId = $_POST['groupid'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    $channelIds	= $_POST['channelIds'] ?? null;
    $joined = 0;
    $existing_membership = $_USER->getGroupMembershipDetail($groupid);
    $existing_channels = empty($existing_membership) ? array() : explode(',', $existing_membership['channelids']);

    $successfully_joined_channels = [];
    if ($channelIds){
        foreach($channelIds as $channelid){
            $channelid = $_COMPANY->decodeId($channelid);
            if (($key = array_search($channelid, $existing_channels)) !== false) {
                unset($existing_channels[$key]);
                continue;
            }

            if ($_USER->joinGroup($groupid,0, $channelid)) {
                $joined = $joined+1;
                $successfully_joined_channels[] = $channelid;
            }
        }
    }

    $successfully_left_channels = [];
    $unsuccessfully_left_channels = [];
    foreach ($existing_channels as $channelid) {
        if ($channelid != 0) {
            if ($_USER->leaveGroup($groupid,0,$channelid)) {
                $successfully_left_channels[] = $channelid;
            } elseif ($_USER->getWhyCannotLeaveGroup($groupid, 0, $channelid) === 'LEADER_CANNOT_LEAVE_MEMBERSHIP') {
                $unsuccessfully_left_channels[] = $channelid;
            }
        }
    }

    $modal_status = 1;
    $modal_title = gettext('Success');
    $modal_body = gettext('Your request has been processed successfully!');
    $modal_val = gettext('<strong>Leave</strong>');

    if (count($unsuccessfully_left_channels)) {
        $all_channel = Group::GetChannelList($groupid);
        $all_channel = array_column($all_channel, null, 'channelid');
        $successfully_joined_channels_names = implode(', ', array_map(function ($channelid) use ($all_channel) {
            return $all_channel[$channelid]['channelname'];
        }, $successfully_joined_channels));
        $successfully_left_channels_names = implode(', ', array_map(function ($channelid) use ($all_channel) {
            return $all_channel[$channelid]['channelname'];
        }, $successfully_left_channels));
        $unsuccessfully_left_channels_names = implode(', ', array_map(function ($channelid) use ($all_channel) {
            return $all_channel[$channelid]['channelname'];
        }, $unsuccessfully_left_channels));
        if (count($successfully_left_channels) || count($successfully_joined_channels)) {
            $modal_status = 0;
            $modal_title = gettext('Result');
            $modal_body = '<ul>';
            if (count($successfully_joined_channels)) {
                $modal_body .= '<li>' . sprintf(gettext('Successfully joined %s'), $successfully_joined_channels_names) . '</li>';
    }
            if (count($successfully_left_channels)) {
                $modal_body .= '<li>' . sprintf(gettext('Succesfully left %s'), $successfully_left_channels_names) . '</li>';
            }
            if (count($unsuccessfully_left_channels)) {
                $modal_body .= '<li>' . sprintf(gettext('Could not leave %s as you are a leader there'), $unsuccessfully_left_channels_names) . '</li>';
            }
            $modal_body .= '</ul>';
        } else {
            $modal_status = 0;
            $modal_title = gettext('Error');
            $modal_body = sprintf(gettext('Could not leave %s as you are a leader there'), $unsuccessfully_left_channels_names);
        }
        $existing_membership = $_USER->getGroupMembershipDetail($groupid);
        $existing_channels = empty($existing_membership) ? array() : explode(',', $existing_membership['channelids']);
        $modal_val = implode(',', array_map(function ($channelid) {
            global $_COMPANY;
            return $_COMPANY->encodeId($channelid);
        }, $existing_channels));
    }

    AjaxResponse::SuccessAndExit_STRING($modal_status, $modal_val, $modal_body, $modal_title);
}


elseif(isset($_GET['updateGroupChapterMembership']) && $_SERVER['REQUEST_METHOD'] === 'POST'){ //Return 0 on error, 1 on removal, 2 on addition
	$encGroupId = $_POST['groupid'];
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($encGroupId))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL ||
        ($memberUserid = $_COMPANY->decodeId($_POST['memberUserid']))<1 ||
        ($memberUser = User::GetUser($memberUserid)) === null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $joined = 0;
    $existing_membership = $memberUser->getGroupMembershipDetail($groupid);

    if ($existing_membership) {
        if (!$_USER->canManageContentInScopeCSV($groupid,$existing_membership['chapterid'],$existing_membership['channelids'])) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }
    $existing_chapters = empty($existing_membership) ? array() : explode(',', $existing_membership['chapterid']);

    if (isset($_POST['chapterIds']) && is_array($_POST['chapterIds'])) {
        $chapterIds = $_COMPANY->decodeIdsInArray($_POST['chapterIds']);
    } elseif (!empty($_POST['chapterIds'])) {
        $chapterIds = array($_COMPANY->decodeId($_POST['chapterIds']));
    } else {
        $chapterIds = array();
    }

    if ($group->val('chapter_assign_type') == 'by_user_atleast_one' && count($chapterIds)<1 && !empty($existing_chapters)){ // Check for at least one chapter need to join
        $enc_existing_chapters = $_COMPANY->encodeIdsInArray($existing_chapters);
        AjaxResponse::SuccessAndExit_STRING(0, implode(',',$enc_existing_chapters), gettext('Needs to join at least one chapter!'), gettext('Error'));
    } else {
        if (!empty($chapterIds)){
            foreach($chapterIds as $chapterid){
                // Skip chapters of which the user is not a member
                if (($key = array_search($chapterid, $existing_chapters)) !== false) {
                    unset($existing_chapters[$key]);
                    continue;
                }
                // Make user join all new chapters of which the user is not a member
                if ($chapterid) {
                    $memberUser->joinGroup($groupid, $chapterid, 0);
                }
            }
        }
        // Make the user leave all chapters that the user has indicated membership removal
        foreach ($existing_chapters as $chapterid) {
            if ($chapterid) {
                $memberUser->leaveGroup($groupid, $chapterid, 0);
            }
        }
        AjaxResponse::SuccessAndExit_STRING(1, gettext('<strong>Leave</strong>'), gettext('Membership updated successfully!'), gettext('Success'));
    }
}

elseif(isset($_GET['updateGroupChannelMembership']) && $_SERVER['REQUEST_METHOD'] === 'POST'){ //Return 0 on error, 1 on removal, 2 on addition
	$encGroupId = $_POST['groupid'];
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($encGroupId))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL ||
        ($memberUserid = $_COMPANY->decodeId($_POST['memberUserid']))<1 ||
        ($memberUser = User::GetUser($memberUserid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $channelIds	= $_POST['channelIds'] ? $_COMPANY->decodeIdsInArray($_POST['channelIds']) : [];
    $joined = 0;
    $existing_membership = $memberUser->getGroupMembershipDetail($groupid);
    
    if ($existing_membership) {
        if (!$_USER->canManageContentInScopeCSV($groupid,$existing_membership['chapterid'],$existing_membership['channelids'])) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }
    $existing_channels = empty($existing_membership) ? array() : explode(',', $existing_membership['channelids']);

    foreach($channelIds as $channelid){
        // Skip all channels that user is already a member of
        if (($key = array_search($channelid, $existing_channels)) !== false) {
            unset($existing_channels[$key]);
            continue;
        }
        // Make user a member of all new channels
        if ($channelid) {
            $memberUser->joinGroup($groupid, 0, $channelid, 0, true, true, 'LEAD_INITIATED');
        }
    }
    // For all remaining channels remove the user membership.
    foreach ($existing_channels as $channelid) {
        if ($channelid) {
            $memberUser->leaveGroup($groupid, 0, $channelid, true, false, 'LEAD_INITIATED');
        }
    }
    AjaxResponse::SuccessAndExit_STRING(1, gettext('<strong>Leave</strong>'), gettext('Membership updated successfully!'), gettext('Success'));
}


elseif(isset($_GET['getFollowUnfollowGroup'])){
	$encGroupId = $_GET['getFollowUnfollowGroup'];
    //Data Validation
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if ($_USER->isGroupMember($groupid)) {

        if ($_USER->getWhyCannotLeaveGroup($groupid, 0, 0) === 'LEADER_CANNOT_LEAVE_MEMBERSHIP') {
            AjaxResponse::SuccessAndExit_STRING(
                0,
                ['btn' => gettext('<strong>Leave</strong>')],
                sprintf(gettext('You are assigned a leadership role in this %1$s, so cannot leave the %1$s'), $_COMPANY->getAppCustomization()['group']['name-short']),
                gettext('Error')
            );
        }

        if ($_USER->leaveGroup($groupid, 0, 0)) {
            $message = gettext('Your leave request has been processed successfully!');
            if ($group->isContentRestrictedToMembers()) {
                $message .= ' ' . gettext('This page will refresh to show the updated content.');
                AjaxResponse::SuccessAndExit_STRING(11, array('btn'=>gettext('<strong>Join</strong>')), $message, gettext('Success'));
            } elseif ($group->val('chapter_assign_type')=='auto'){
                AjaxResponse::SuccessAndExit_STRING(4, array('btn'=>gettext('<strong>Join</strong>')), $message, gettext('Success'));
            } else {
                AjaxResponse::SuccessAndExit_STRING(1, array('btn'=>gettext('<strong>Join</strong>')), $message, gettext('Success'));
            }
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, array('btn'=>gettext('<strong>Join</strong>')), gettext('Something went wrong, please try again.'), gettext('Error'));
        }

    } else {
        $all_chapter= Group::GetChapterList($groupid);
        if (count($all_chapter)){
            $autoAssign = null;
            if ($group->val('chapter_assign_type')=='auto' &&  $_USER->val('homeoffice') ) {
                foreach ($all_chapter as $chapter) {
                    $branchids = explode(',', $chapter['branchids']);

                    if (in_array($_USER->val('homeoffice'), $branchids)) {
                        $_USER->joinGroup($groupid, (int)$chapter['chapterid'],0);
                        $autoAssign[] = $_COMPANY->encodeId($chapter['chapterid']);
                    }
                }
            }
            if ($autoAssign){
                AjaxResponse::SuccessAndExit_STRING(3, array('what'=>$autoAssign, 'btn'=>gettext('<strong>Leave</strong>')), gettext('Your join request has been processed successfully!'),gettext('Success'));
            } else {
                if ($_USER->joinGroup($groupid, 0, 0)) {
                    // Remove the groupid from validated list
                    if ($group->val('chapter_assign_type') == 'by_user_atleast_one' || $group->val('chapter_assign_type') == 'by_user_exactly_one') {
                        if (($k1 = array_search($groupid, $_SESSION['chapter_validation_done'] ?? array())) !== false) {
                            unset($_SESSION['chapter_validation_done'][$k1]);
                        }
                    }
                    AjaxResponse::SuccessAndExit_STRING(2, array('btn'=>gettext('<strong>Leave</strong>')), gettext('Your join request has been processed successfully!'), gettext('Success'));
                } else{
                    AjaxResponse::SuccessAndExit_STRING(0, array('btn'=>gettext('<strong>Join</strong>')), gettext('Something went wrong, please try again.'), gettext('Error'));
                }
            }
        } else {
            if ($_USER->joinGroup($groupid, 0, 0)) {
                AjaxResponse::SuccessAndExit_STRING(2, array('btn'=>gettext('<strong>Leave</strong>')), gettext('Your join request has been processed successfully!'), gettext('Success'));
            } else{
                AjaxResponse::SuccessAndExit_STRING(0, array('btn'=>gettext('<strong>Join</strong>')), gettext('Something went wrong, please try again.'), gettext('Error'));
            }
        }
    }
}

elseif (isset($_GET['mangeChannelLeads'])){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['mangeChannelLeads']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $filterChannelId = 0;
    //And condiction for get chapter id data
    if(!empty($_GET['channel_filter'])){
        $filterChannelId = $_COMPANY->decodeId($_GET['channel_filter']);
    }

    $encGroupId = $_COMPANY->encodeId($groupid);

    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $canManageGroup = $_USER->canManageGroup($groupid);
    $channels = Group::GetChannelList($groupid);
    $types = $group->GetGroupleadTypes();
    $channelLeads = $group->getChannelLeads($filterChannelId);
    for ($c = 0; $c < count($channelLeads); $c++) {
        if (empty($channelLeads[$c]['grouplead_typeid'])){
            $channelLeads[$c]['rolename'] = '';
            $channelLeads[$c]['permissions'] = array();
        }else{
            $channelLeads[$c]['rolename'] = $types[$channelLeads[$c]['grouplead_typeid']]['type'];
            $channelLeads[$c]['permissions'] = $types[$channelLeads[$c]['grouplead_typeid']]['permissions'];
        }
    }

    $systemLeadType  = Group::SYS_GROUPLEAD_TYPES;
 	include(__DIR__ . "/views/templates/get_channel_leads.template.php");
}

elseif (isset($_GET['openChannelLeadRole']) && isset($_GET['cid']) && isset($_GET['id'])){
	$encGroupId = $_GET['openChannelLeadRole'];
    $encChannelId = $_GET['cid'];
    $leadid = 0;

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($channelid = $_COMPANY->decodeId($encChannelId)) < 0 ||
        (isset($_GET['id']) && ($leadid = $_COMPANY->decodeId($_GET['id'])) < 0)
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGroupSomeChannel($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $grouplead_type = array();
    $allChannels = null;
    $edit=[];

    $form_title = sprintf(gettext("Add %s Leader"),$_COMPANY->getAppCustomization()['channel']['name-short']);

    $all_grouplead_type = $_COMPANY->getAllGroupLeadtypes();
    foreach($all_grouplead_type as $t){
        if ($t['sys_leadtype']==5){
            $grouplead_type[] = $t;
        }
    }

    if ($leadid>0 && $channelid>0){
        if (!$_USER->canManageGrantGroupChannel($groupid, $channelid)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $form_title = sprintf(gettext('Edit %1$s %2$s Leader'), htmlspecialchars(Group::GetChannelName($channelid, $groupid)['channelname']), $_COMPANY->getAppCustomization()['channel']['name-short']);

        $edit = $group->getChannelLeadDetail($channelid,$leadid);
    } else {
        $allChannels= Group::GetChannelList($groupid);
        $count =  count($allChannels);
        for($i=0;$i<$count;$i++){
            if (!$_USER->canManageGrantGroupChannel($groupid, $allChannels[$i]['channelid'])) {
                unset($allChannels[$i]);
            }
        }
    }

    include(__DIR__ . "/views/templates/channel_lead_form.template.php");
}

elseif (isset($_GET['search_users_to_channel_lead']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (!isset($_GET['cid']) || !isset($_GET['gid']) ||
        ($groupId = $_COMPANY->decodeId($_GET['gid']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGroupSomeChannel($groupId)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $channelid = $_COMPANY->decodeId($_GET['cid']);

    // SearchUsersByKeyword is safe method and can handle unclean data, so no need for raw2clean.
    $activeusers = User::SearchUsersByKeyword($_GET['keyword']);

	$dropdown = '';
	if(count($activeusers)>0){
		$dropdown .= "<select class='form-control userdata' name='userid' onchange='closeDropdown()' id='user_search' required >";
		$dropdown .= "<option value=''>".gettext('Select a user (maximum of 20 matches are shown below)')." </option>";

		for($a=0;$a<count($activeusers);$a++){
		    $dropdown .=  "<option value='".$_COMPANY->encodeId($activeusers[$a]['userid'])."'>".rtrim(($activeusers[$a]['firstname']." ".$activeusers[$a]['lastname'])," ")." (".$activeusers[$a]['email'].") - ".$activeusers[$a]['jobtitle']."</option>";
		}
		$dropdown .= '</select>';
	}else{
        $dropdown .= "<select class='form-control userdata' name='userid' id='user_search' required>";
		$dropdown .= "<option value=''>".gettext('No match found.')."</option>";
		$dropdown .= "</select>";
	}
	echo $dropdown;
}
elseif (isset($_GET['updateChannelLeadRole'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
	$encGroupId = $_GET['updateChannelLeadRole'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<1 || 
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!isset($_POST['channelid']) || ( $channelid  = $_COMPANY->decodeId($_POST['channelid'])) <1){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please select a channel!'), gettext('Error'));
    }

    if (!isset($_POST['userid']) || ($personid  = $_COMPANY->decodeId($_POST['userid'])) < 1){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please select a user!'), gettext('Error'));
    }

    if (!isset($_POST['typeid']) || ($typeid     = $_COMPANY->decodeId($_POST['typeid'])) < 0){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please select a role!'), gettext('Error'));
    }

    $roletitle = $_POST['roletitle'];

    // Authorization Check
    if (!$_USER->canManageGrantGroupChannel($groupid, $channelid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    // Check for group restriction before any other processing.
    if(!$group->isUserAllowedToJoin($personid)){
        AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext('User does not meet membership requirements for %1$s %2$s.'), $group->val('groupname'), $_COMPANY->getAppCustomization()['group']['name-short']), gettext('Error'));
    }
    $leadid  = $_COMPANY->decodeId($_POST['leadid']);
    if ($leadid){
        $group->updateChannelLead($channelid,$leadid,$typeid,$roletitle);
        AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext("%s leader role updated successfully."),$_COMPANY->getAppCustomization()['channel']['name-short']), gettext('Success'));
    } else {
        $group->addChannelLead($channelid,$personid,$typeid,$roletitle);
        $group->addOrUpdateGroupMemberByAssignment($personid,0,$channelid);
        $group->sendGroupLeadAssignmentEmail($channelid, 3, $typeid, $personid);
        AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext("%s leader role assigned successfully."),$_COMPANY->getAppCustomization()['channel']['name-short']), gettext('Success'));
    }
}

elseif (isset($_GET['deleteChannelLeadRole'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
	$encGroupId = $_POST['groupid'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<1 || ($channelid = $_COMPANY->decodeId($_POST['channelid']))<1 || ($leadid = $_COMPANY->decodeId($_POST['leadid']))<1 || ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGrantGroupChannel($groupid, $channelid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    echo $group->removeChannelLead($channelid,$leadid);
}

elseif (isset($_GET['relinkOfficeRavenGroupLinkedGroups'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        !$_COMPANY->getAppCustomization()['linked-group']['enabled']
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->isAdmin()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $group->autoLinkOtherRegionalGroupsChapters();
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Updated successfully'), gettext('Success'));
}

elseif (isset($_GET['getOfficeRavenGroups'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getOfficeRavenGroups']))<1 ||
        ($group = Group::GetGroup($groupid)) === null  ||
        !$_COMPANY->getAppCustomization()['linked-group']['enabled']
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);

    $links = Group::GetAllLinkedGroupsChaptersChannels($groupid);
    $linkedGroupids = $links['linkedGroupIds'];
    $linkedRows = $links['linkedRows'];

    $localGroups = array();
    foreach ($linkedGroupids as $g0) {
        $localGroups[] = Group::GetGroup($g0);
    }
    usort($localGroups,function($a,$b) {
        // Sort groups alphabetically
        return strcmp($a->val('groupname'), $b->val('groupname'));
    });

    include(__DIR__ . '/../officeraven/views/templates/get_office_raven_groups.template.php');
    exit();
}

elseif (isset($_GET['getGroupChaptersAboutUs'])){
    $encGroupId = $_GET['getGroupChaptersAboutUs'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getGroupChaptersAboutUs']))<1 || ($group = Group::GetGroup($groupid)) == null || ($chapterid = $_COMPANY->decodeId($_GET['chapterid']))<0 ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $types = Group::GetGroupleadTypes();
    $type[0] = '';

    $chapter 		=	array();
    $chapterLeads 	=	array();
    $chapters = Group::GetChapterList($groupid);

    // Chapter & Chapterleads
	if ($chapters){
        if ($chapterid == 0){
            $chapterid = $chapters[0]['chapterid'];
        }
        $chapter 		=	$group->getChapter($chapterid);
        $chapterLeads 	=	$group->getChapterLeads($chapterid,'',true);
	}

    $canViewContent = $_USER->canViewContent($groupid);

	include(__DIR__ . "/views/templates/about_group_chapters.template.php");
}

elseif (isset($_GET['getChapterAboutUs'])){
    $encGroupId = $_GET['getChapterAboutUs'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getChapterAboutUs']))<1  || ($chapterid = $_COMPANY->decodeId($_GET['chapterid']))<1
    || ($group = Group::GetGroup($groupid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $types = Group::GetGroupleadTypes();
    $type[0] = '';

    $isGroupMember = $_USER->isGroupMember($groupid);
    $totalmembers = array();
    $chapter 		=	$group->getChapter($chapterid);
    $chapterLeads 	=	$group->getChapterLeads($chapterid,'',true);

    if ($group->val('about_show_members') === "1" || ($group->val('about_show_members') === "2" && $isGroupMember)) {
        $totalmembers = $group->getChapterMembersCount($chapterid);
    }

	include(__DIR__ . "/views/templates/about_chapter.template.php");
}

elseif (isset($_GET['getGroupChannelsAboutUs'])){
    $encGroupId = $_GET['getGroupChannelsAboutUs'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getGroupChannelsAboutUs']))<1
    || ($group = Group::GetGroup($groupid)) == null
    || ($channelid = $_COMPANY->decodeId($_GET['channelid']))< 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $types = Group::GetGroupleadTypes();
    $type[0] = '';

    $channel 		=	array();
    $channelLeads 	=	array();
    $channels= Group::GetChannelList($groupid);

	if($channels){
        if($channelid == 0){
            $channelid = $channels[0]['channelid'];
        }
		$channel 		=	$group->getChannel($channelid);
        $channelLeads = $group->getChannelLeads($channelid,'',true);

	}
    $canViewContent = $_USER->canViewContent($groupid);
    include(__DIR__ . "/views/templates/about_group_channels.template.php");

}

elseif (isset($_GET['getChannelAboutUs'])){
    $encGroupId = $_GET['getChannelAboutUs'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getChannelAboutUs']))<1 || ($channelid = $_COMPANY->decodeId($_GET['channelid']))< 1
        || ($group = Group::GetGroup($groupid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $types = Group::GetGroupleadTypes();
    $type[0] = '';

    $channel        =	$group->getChannel($channelid);
    $channelLeads   = $group->getChannelLeads($channelid,'',true);

    include(__DIR__ . "/views/templates/about_channel.template.php");
    
}

elseif (isset($_GET['getGroupChapterChannelMembersList'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getGroupChapterChannelMembersList']))<1 ||
        ($id = $_COMPANY->decodeId($_GET['sectionid']))<1 || ($section = $_COMPANY->decodeId($_GET['section'])) < 1  ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    
    $filter = "";
    if ($section ==2){
        $filter = " AND FIND_IN_SET(". $id.", a.chapterid)";
    }

    if ($section ==3){
        $filter = " AND FIND_IN_SET(". $id.", a.channelids)";
    }

    $input = array('search'=>raw2clean($_POST['search']['value']),'start'=>(int)$_POST['start'],'length'=>(int)$_POST['length'],'draw'=>(int)$_POST['draw']);

    $orderFields = ['b.firstname','b.jobtitle','','a.groupjoindate'];

    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
   
    $search = "";
    if ($input['search']){
        $search = " AND (b.firstname LIKE '%".$input['search']."%' OR b.lastname LIKE '%".$input['search']."%' OR b.email LIKE '%".$input['search']."%')";
    }
	$totalrows 	= count($db->get("SELECT a.memberid,b.firstname,b.lastname,b.jobtitle,b.picture,b.email FROM `groupmembers` a JOIN users b ON b.userid=a.userid WHERE b.companyid='{$_COMPANY->id()}' AND ( a.`groupid`='".$groupid."' AND a.`isactive`='1' AND b.`isactive`='1' AND a.`anonymous`!='1' $filter  $search )"));
    
    $members= $db->get("SELECT a.*,b.firstname,b.lastname,b.jobtitle,b.picture,b.email,b.homeoffice FROM `groupmembers` a JOIN users b ON b.userid=a.userid WHERE b.companyid='{$_COMPANY->id()}' AND (a.`groupid`='".$groupid."' AND a.`isactive`='1' AND b.`isactive`='1' AND a.`anonymous`!='1' $filter $search) ORDER BY $orderFields[$orderIndex] $orderDir limit ".$input['start'].",".$input['length']." ");

    $final = [];
    $i=0;
    foreach($members as $row){  
        $encMemberUserID = $_COMPANY->encodeId($row['userid']);
        $branchName = $_COMPANY->getBranchName($row['homeoffice']);        

       if($row['anonymous']==1){
        $row['firstname'] = 'anonymous';
        $row['lastname'] = '';
        $row['jobtitle'] = 'anonymous';
        $row['email'] = 'anonymous';
        $branchName = 'anonymous';
        $final[] = array(
            "DT_RowId" => "id_".$i,
            '<strong><button class="btn-no-style" role="button" style="cursor:pointer;">'.rtrim(($row['firstname']." ".$row['lastname'])," ").'</button></strong><br><span>'.$row['email'].'</span>',
            $row['jobtitle'] ?$row['jobtitle'] :'-',
            $branchName,
            $_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['groupjoindate'],true,true, false)
           );
       }else{
        $final[] = array(
            "DT_RowId" => "id_".$i,
            '<strong><button class="btn-no-style" role="button" style="cursor:pointer;" onclick=getProfileDetailedView(this,{"userid":"'.$encMemberUserID.'"}) >'.rtrim(($row['firstname']." ".$row['lastname'])," ").'</button></strong><br><span>'.$row['email'].'</span>',
            $row['jobtitle'] ?$row['jobtitle'] :'-',
            $branchName,
            $_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['groupjoindate'],true,true, false)
           );
       }
        $i++;
    }

    $json_data = array(
                    "draw"=> intval( $input['draw'] ), 
                    "recordsTotal"    => intval(count($final) ),
                    "recordsFiltered" => intval($totalrows), 
                    "data"            => $final
                );

    echo json_encode($json_data);
}

elseif (isset($_POST['removeProfilePicture'])){
    $_USER->updateProfilePicture('');
    if ($_USER->has('picture')){
        $_COMPANY->deleteFile($_USER->val('picture'));
    }
    $_USER->clearSessionCache();
    echo 1;
    
}

elseif (isset($_GET['deleteSurvey'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $encGroupId = $_POST['groupid'];

    if (($groupid = $_COMPANY->decodeId($encGroupId))<0 ||
        ($surveyid = $_COMPANY->decodeId($_POST['surveyid']))<0 ||
        ($survey = Survey2::GetSurvey($surveyid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if ($groupid){
        // Authorization Check
        if (!$_USER->canManageContentInScopeCSV($survey->getGroupId(), $survey->getChapterId(),$survey->getChannelId())) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } else {
        if (!$_USER->isAdmin()) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }
    if ($survey->deleteIt()) {
        AjaxResponse::SuccessAndExit_STRING(1,  $groupid, gettext('Survey deleted successfully.'), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong, please try again.'), gettext('Error'));
}

elseif (isset($_GET['activateDeactivateSurvey'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $encGroupId = $_POST['groupid'];
    if (($groupid = $_COMPANY->decodeId($encGroupId))<0 ||
        ($surveyid = $_COMPANY->decodeId($_POST['surveyid']))<0 ||
        ($survey = Survey2::GetSurvey($surveyid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);
   
    if ($groupid){
        // Authorization Check
        if (!$_USER->canManageContentInScopeCSV($survey->getGroupId(), $survey->getChapterId(),$survey->getChannelId())) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } else {
        if (!$_USER->isAdmin()) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }
    $updatePublishDate = 0;
    if (isset($_POST['update_publish_date'])){
        $updatePublishDate = (int)$_POST['update_publish_date'];
    }
    $r =  $survey->activateDeactivateSurvey($updatePublishDate,false);
    if ($r < 0){
        AjaxResponse::SuccessAndExit_STRING(0, $groupid, gettext('There is already an active survey with the same trigger. Inactivate the existing survey or change the trigger to continue adding a new survey.'), gettext('Error'));
    }
    AjaxResponse::SuccessAndExit_STRING(1, $groupid, gettext('Updated successfully.'), gettext('Success'));
}

elseif (isset($_GET['shareUnshareSurvey'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $encGroupId = $_POST['groupid'];
    $encIsTemplate = $_POST['isTemplate'];

    if (($groupid = $_COMPANY->decodeId($encGroupId))<0 ||
        ($isTemplate = $_COMPANY->decodeId($encIsTemplate))<0 ||
        ($surveyid = $_COMPANY->decodeId($_POST['surveyid']))<0 ||
        ($survey = Survey2::GetSurvey($surveyid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($groupid){
        // Authorization Check
        if (!$_USER->canManageContentInScopeCSV($survey->getGroupId(), $survey->getChapterId(),$survey->getChannelId())) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } else {
        if (!$_USER->isAdmin()) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }

    $isTemplate  = $isTemplate == 1 ? 0 : 1; // Flip the flag
    if ($survey->updateSurveyTemplateFlag($isTemplate)) {
        if ($isTemplate == 0){
            $msg = gettext('The survey has been removed from survey templates.');
        } else {
            $msg = gettext('Survey saved as Template successfully.');
        }
        AjaxResponse::SuccessAndExit_STRING(1, $groupid, $msg, gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong, please try again.'), gettext('Error'));
    }
}

elseif (isset($_GET['openSurveySettingForm'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $encGroupId = $_GET['groupid'];
    if (
        ($groupid = $_COMPANY->decodeId($encGroupId))<1 ||
        ($group = Group::GetGroup($groupid)) === null  ||
        ($surveyid = $_COMPANY->decodeId($_GET['surveyid']))<0
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $surveyType = Survey2::SURVEY_TYPE['GROUP_MEMBER'];
    $survey = null;
    if ($surveyid){
        $survey = Survey2::GetSurvey($surveyid);
    }

    $templateSurveys= Survey2::GetSurveyTemplate($surveyType);
    $chapters = [];
    if ($_COMPANY->getAppCustomization()['surveys']['allow_create_chapter_scope']) {
        $chapters = Group::GetChapterList($groupid);
    }

    $channels = [];
    if ($_COMPANY->getAppCustomization()['surveys']['allow_create_channel_scope']) {
        $channels = Group::GetChannelList($groupid);
    }

    $form_title = gettext("Create a Survey");
	include(__DIR__ . "/views/templates/survey_setting.template.php");
}

elseif (isset($_GET['checkSurveyValidations'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    //Data Validation
    $check = $db->checkRequired(array('Survey Name'=>@$_POST['surveyname']));
    if($check){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$check), gettext('Error'));
    }

    if (empty($_POST['trigger'])){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please select survey trigger!'), gettext('Error'));
    }

    if (empty($_POST['group_chapter_channel_id']) && $_POST['trigger']!=127){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please select scope!'), gettext('Error'));
    }

    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Validated successfully.'), gettext('Success'));
}

elseif (isset($_GET['submitSurveySetting'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($cloned_json_surveyid = $_COMPANY->decodeId($_POST['cloned_json_surveyid']))<0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $chapterid = 0;
    $channelid = 0;
    $section = $_COMPANY->decodeId($_POST['section']);

    if (($section == 1 && !$_COMPANY->getAppCustomization()['surveys']['allow_create_chapter_scope']) || ($section == 2 && !$_COMPANY->getAppCustomization()['surveys']['allow_create_channel_scope'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($section  == 1){
        $chapterid = $_COMPANY->decodeId($_POST['group_chapter_channel_id']);
    } elseif ($section == 2){
        $channelid = $_COMPANY->decodeId($_POST['group_chapter_channel_id']);
    }

    if (!$_USER->canManageContentInScopeCSV($groupid,$chapterid, $channelid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (!isset($_POST['surveyname']) || ($surveyname = $_POST['surveyname']) == ''){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter survey name!'), gettext('Error'));
    }

    if (!isset($_POST['trigger']) || ($trigger    = $_POST['trigger']) == ''){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please select survey trigger!'), gettext('Error'));
    }

    if($cloned_json_surveyid == 0){
        $clonedSurveyJson = '';
    } elseif($cloned_json_surveyid > 0){
        $clonedSurvey = Survey2::GetSurvey($cloned_json_surveyid);
        $clonedSurveyJson = $clonedSurvey ->val('survey_json');
    }
    $anonymity  = (int)$_POST['anonymity'];
    $is_required = (int)$_POST['is_required'];
    $allow_multiple = (int)$_POST['allow_multiple'];

    $survey = GroupMemberSurvey::CreateNewSurvey($groupid, $chapterid, $channelid, $surveyname, $anonymity, $clonedSurveyJson, $trigger, $is_required, $allow_multiple);

    if ($survey){
        AjaxResponse::SuccessAndExit_STRING(1, 'create_survey?surveyid='.$_COMPANY->encodeId($survey->id()), gettext('Continue to create survey'), '');
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong, please try again.'), gettext('Error'));
    }
}

elseif (isset($_GET['previewSurvey'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (($surveyid = $_COMPANY->decodeId($_GET['surveyid']))<0  
    || ($groupid = $_COMPANY->decodeId($_GET['groupid']))<0  ) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $survey = Survey2::GetSurvey($surveyid);
    $surveyLanguages = $survey->getSurveyLanguages();
    $survey_json = $survey->val('survey_json');
    $form_title = gettext("Preview Survey");
    include(__DIR__ . "/views/templates/survey_preview.template.php");
    
}

elseif (isset($_GET['saveSurveyResponse'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    
	if (($surveyid = $_COMPANY->decodeId($_POST['surveyid']))<1
    || ($survey = Survey2::GetSurvey($surveyid)) === null ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $responseJson = $_POST['responseJson'];
    $objectId = $_POST['objectId'] ?? '';
    $anonymous = $survey ->val('anonymous');
    $profile_json = '';
    $userid = 0;
    if(!$anonymous){
        $userid = $_USER->id();
        $profile_json = json_encode(array('firstname'=>$_USER->val('firstname'),'lastname'=>$_USER->val('lastname'),'email'=>$_USER->getEmailForDisplay(),'jobTitle'=>$_USER->val('jobtitle'),'officeLocation'=>$_USER->getBranchName(),'department'=>$_USER->getDepartmentName()));
    }
    $responseid = $survey->saveOrUpdateSurveyResponse($userid,$responseJson,$profile_json,'', $objectId);

    echo $responseid;

    exit();
}

elseif (isset($_GET['showReleaseNotes'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $modalTitle = "Release Notes";
    $rows = $db->ro_get("SELECT * FROM `release_notes` WHERE `isactive`=1 ORDER BY `releaseid` DESC LIMIT 20");
    include(__DIR__ . "/views/templates/release_notes.template.php");
}
elseif (isset($_GET['confirmImageCopyright'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    
    if ($_COMPANY->getAppCustomization()['policy']['image_upload_notice']){
        $notice = $_COMPANY->getAppCustomization()['policy']['image_upload_notice'];
    } else {
        $notice = gettext("The images you attached may be copyrighted. Please only continue if you have rights to use the image.");
    }
    echo $notice;
}

elseif (isset($_GET['closeJoinGroupModal'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1
    || ($group = Group::GetGroup($groupid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!empty($_POST['memberUserid']) && ($memberUserid = $_COMPANY->decodeId($_POST['memberUserid']))>0){
        $memberUser = User::GetUser($memberUserid);
        $isMember = $memberUser->isGroupMember($groupid);
    } else {
        $isMember = $_USER->isGroupMember($groupid);
    }


    if ($isMember){
        $selectedChapters = $_POST['chapterids'] ?? 0;
        if ($selectedChapters==0 && $group->val('chapter_assign_type') == 'by_user_atleast_one') {
            $msg = sprintf(gettext('You need to select one or more %1$s to join.'), $_COMPANY->getAppCustomization()['chapter']['name-short']);
            AjaxResponse::SuccessAndExit_STRING(0, '', $msg, gettext('Error'));
        } elseif ($selectedChapters==0 && $group->val('chapter_assign_type') == 'by_user_exactly_one') {
            $msg = sprintf(gettext('You need to select a %1$s to join.'),$_COMPANY->getAppCustomization()['chapter']['name-short']);
            AjaxResponse::SuccessAndExit_STRING(0, '', $msg, gettext('Error'));
        }
    }
} elseif (isset($_GET['getTrainingVideo'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
  if (($video_id = $_COMPANY->decodeId($_POST['video_id']))<1) {
    header(HTTP_BAD_REQUEST);
    exit();
  }

  $rows=$db->get("SELECT * FROM `training_videos` WHERE `video_id` = " . $video_id);

  if(!isset($rows) || !isset($rows[0])) {
    ob_clean();
    echo '{"status":"error", "details":"video_id not found in database"}';
    exit;
  }

  // get S3 GET URL
  $preview_url = TrainingVideo::GetPreSignedURL($rows[0]["filename"], 'GetObject', 0);

  // replace the placeholder in widget code
  $widget_code = str_replace('[TRAINING_VIDEO_PLACEHOLDER]', $preview_url, $rows[0]["widget_code"]);
  $label = $rows[0]["label"];

  // return JSON with label and widget_code
  ob_clean();
  echo json_encode(array(
      "status" => "success",
      "label" => $label,
      "widget_code" => $widget_code
    )
  );
  exit;

} elseif (isset($_GET['getTrainingVideoModal'])  && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $selected_tags = $_POST["tags"];
    include(__DIR__ . "/views/templates/training_videos_modal.template.php");

    ob_clean();
    echo $training_video_modal_data;
    exit;
}
elseif(isset($_GET['addNewGroupMember'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null
    
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid) ||
        !$_COMPANY->getAppCustomization()['group']['manage']['allow_add_members']
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $email = $_POST['email'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$_COMPANY->isValidEmail($email)) {
        echo 0;
        exit();
    }
    $group_chapter_channel_id = isset($_POST['group_chapter_channel_id']) ? $_COMPANY->decodeId($_POST['group_chapter_channel_id']) : 0;
    $section = isset($_POST['section']) ? $_COMPANY->decodeId($_POST['section']) : 0;
    $chapterid = 0;
    $channelid = 0;
    if ($section == 1){
        // For auto assign usecases, chapterid is -1 so reset it first.
        $chapterid = max($group_chapter_channel_id, 0);
    } elseif ($section == 2){
        $channelid = max($group_chapter_channel_id, 0);
    }
    $assignedUser = User::GetUserByEmail($email);

    if (!$assignedUser){
        echo 0;
        return 0;
    }
    if(!$assignedUser->isAllowedToJoinGroup($groupid)){
        echo 3;
        return;
    }
    // Calculate if we need auto assign chapter if so create one.
    $retVal = 0;
    if ($assignedUser) {
        if ($_COMPANY->getAppCustomization()['chapter']['enabled'] && $group->val('chapter_assign_type') == 'auto') {
            $all_chapter = Group::GetChapterList($groupid);
            // Go through each chapter and if the homeoffice matches list of branchids of chapter then assign chapter
            foreach ($all_chapter as $chapter) {
                $branchids = explode(',', $chapter['branchids']);
                if (in_array($assignedUser->val('homeoffice'), $branchids)) {
                    $retVal = $assignedUser->joinGroup($groupid, $chapter['chapterid'], 0, 0, true, true, 'LEAD_INITIATED');
                }
            }
        }

        $retVal = $assignedUser->joinGroup($groupid, $chapterid, $channelid, 0, true, true, 'LEAD_INITIATED');
    }

    if($retVal){
        $group->deleteGroupJoinRequest($assignedUser->id());
        echo 1; // 1 for new member.
    } else {
        echo 2; // 2 for existing member.
    }
}

elseif (isset($_GET['updateGroupMemberMembership'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null ||
        ($memberUserid = $_COMPANY->decodeId($_POST['memberUserid']))<1 ||
        ($memberUser = User::GetUser($memberUserid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $existingMembership = $memberUser->getGroupMembershipDetail($groupid);

    if ($existingMembership) {
        if (!$_USER->canManageContentInScopeCSV($groupid,$existingMembership['chapterid'],$existingMembership['channelids'])) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        
        $group_name = $group->val('groupname');
        $all_chapter= Group::GetChapterList($groupid);
        $channels= Group::GetChannelList($groupid);
        $encodedFollowedChapters = [];
        $encodedFollowedChannels = [];
        $joinSuccessMsg = "";
        $anonymous = $existingMembership['anonymous'];

        $followed_chapters = [];
        $followed_channels = [];

        // Auto Assign Chapter
        $autoAssign = null;

       
        if ($group->val('chapter_assign_type')!='by_user_any' && count($all_chapter)>0 && $memberUser->val('homeoffice') > 0 ) {

            if ($group->val('chapter_assign_type') ==  'auto'){ // Chapter auto assignment

                if (!$existingMembership || ($existingMembership && $existingMembership['chapterid']=='0')){  //Check for existing membership
                    foreach ($all_chapter as $chapter) {
                        $branchids = explode(',', $chapter['branchids']);

                        if (in_array($memberUser->val('homeoffice'), $branchids)) {
                            $memberUser->joinGroup($groupid, (int)$chapter['chapterid'],0, $anonymous);
                            $autoAssign[] = $chapter['chaptername'];
                        }
                    }
                }
            }
        }

        if ($existingMembership){
            $followed_chapters	= explode(',', $existingMembership['chapterid']);
            foreach($followed_chapters as $chapterId){
                $encodedFollowedChapters[] = $_COMPANY->encodeId($chapterId);
            }

            $followed_channels	= explode(',', $existingMembership['channelids']);
            foreach($followed_channels as $channelId){
                $encodedFollowedChannels[] = $_COMPANY->encodeId($channelId);
            }
        }
        include(__DIR__ . "/views/templates/update_chapter_channel_membership.template.php");
  
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s member not exist"),$_COMPANY->getAppCustomization()['group']["name-short"]), gettext('Error'));
    }
}
elseif (isset($_GET['removeGroupMember'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($memberUserid = $_COMPANY->decodeId($_POST['memberUserid']))<1 ||
        ($memberUser = User::GetUser($memberUserid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $memberRec = $memberUser->getGroupMembershipDetail($groupid);
    if ($memberRec) {
        if ($_USER->canManageContentInScopeCSV($groupid,$memberRec['chapterid'],$memberRec['channelids'])) {
            if ($memberUser->getWhyCannotLeaveGroup($groupid, 0, 0) === 'LEADER_CANNOT_LEAVE_MEMBERSHIP') {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Leader cannot leave membership'), gettext('Error'));
            }
            $memberUser->leaveGroup($groupid, 0, 0, true, false, 'LEAD_INITIATED');
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Member removed successfully'), gettext('Success'));
        } else {
            header(HTTP_FORBIDDEN);
            exit();
        }
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s member not exist"),$_COMPANY->getAppCustomization()['group']["name-short"]), gettext('Error'));
    }
}
elseif (isset($_GET['requestGroupMembership'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1
    || ($group = Group::GetGroup($groupid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($_USER->isGroupMember($groupid)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('You are already a member!'), gettext('Error'));
    } else {
        $checkRequest = Team::GetRequestDetail($groupid,0);
        if ($checkRequest){
            $sendRequest = Team::CancelTeamJoinRequest($groupid,0, $_USER->id());
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Your request was canceled successfully.'), gettext('Success'));
        } else {
            $sendRequest = Team::SaveTeamJoinRequestData($groupid,0,'{}');
            if ($sendRequest) {
                // Send email notifications based on ERG settings
                $joinRequestEmailSettings = $group->getJoinRequestMailSettings();
                $emails=[];
                if(empty($joinRequestEmailSettings) || $joinRequestEmailSettings['mail_to_leader']){
                    $leads = $group->getGroupLeads(Group::LEADS_PERMISSION_TYPES['MANAGE']); // Only with manage permissions
                    if (!empty($leads)){
                        $emails = array_column($leads,'email');
                    }
                }
                if (!empty($joinRequestEmailSettings) && $joinRequestEmailSettings['mail_to_specific_emails'] && !empty($joinRequestEmailSettings['specific_emails'])) {
                    $specific_emails =  explode(',', $joinRequestEmailSettings['specific_emails']);
                    $emails = array_merge($emails, $specific_emails);

                }
                if(!empty($emails)){
                    $emails = implode(',', array_unique($emails));
                    $app_type = $_ZONE->val('app_type');
                    $reply_addr = $group->val('replyto_email');
                    $from = $group->val('from_email_label') .' '. $_COMPANY->getAppCustomization()['group']["name-short"]. ' Join Request';
                    $requestrName = $_USER->getFullName();
                    $subject = "New Request to Join";
                    $appUrl = $_COMPANY->getAppURL($_ZONE->val('app_type')).'manage?id='.$_COMPANY->encodeId($groupid);
            
                    $msg = <<<EOMEOM
                        <p>{$requestrName} requested to join the {$group->val('groupname')} {$_COMPANY->getAppCustomization()['group']["name-short"]}.</p>
                        <br/>
                        <p>Please login to the application and point to <strong>Manage > Users > {$_COMPANY->getAppCustomization()['group']["name-short"]} Join Requests</strong> to Approve or Deny the request.</p>
                        <br/>
                        <p>Link : <a href="{$appUrl}">{$appUrl}</a></p>
    EOMEOM;
                        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
                        $emesg	= str_replace('#messagehere#',$msg,$template);
                        $_COMPANY->emailSend2($from, $emails, $subject, $emesg, $app_type,$reply_addr);
                }
                AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext("%s join request sent successfully"),$_COMPANY->getAppCustomization()['group']["name-short"]), gettext('Success'));
            } else {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong, please try again.'), gettext('Error'));
            }
        }
    }
}
elseif (isset($_GET['getGroupJoinRequests'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $joinRequests = Team::GetTeamJoinRequests($groupid);
    include(__DIR__ . "/views/templates/get_group_join_requests.template.php");  
}

elseif (isset($_GET['acceptRejectGroupJoinRequest'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null ||
        ($requesterid = $_COMPANY->decodeId($_POST['userid']))<1 ||
        ($requester = User::GetUser($requesterid)) == null ||
        ($action = $_COMPANY->decodeId($_POST['action']))<1

    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $action = $action == 1 ? 1 : 2;
    $sendEmail = false;
    if ($action == 1) {
        if(!$requester->isAllowedToJoinGroup($groupid)){
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('User does not meet membership requirements for %1$s %2$s.'), $group->val('groupname'), $_COMPANY->getAppCustomization()['group']['name-short']), gettext('Error'));

        }
        $retVal = $requester->joinGroup($groupid, 0, 0);
        if ($retVal) {
            $sendEmail = true;
            $group->deleteGroupJoinRequest($requesterid);
            $message = gettext("Request accepted successfully");
            $appUrl = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'detail?id=' . $_COMPANY->encodeId($groupid);
            $emailMessage = "<p>Your request to join " . $group->val('groupname') . " " . $_COMPANY->getAppCustomization()['group']["name-short"] . " has been approved!</p><br/><p>You can access the " . $group->val('groupname') . " " . $_COMPANY->getAppCustomization()['group']["name-short"] . " by <a href='" . $appUrl . "' >" . $appUrl . "</a> link. </p>";
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong while accepting request. Please try again.'), gettext('Error'));
        }
    } else  {
        $sendEmail = true;
        $group->deleteGroupJoinRequest($requesterid);
        $message = gettext("Request rejected successfully");
        $emailMessage = "<p>Your request to join ".$group->val('groupname')." ".$_COMPANY->getAppCustomization()['group']["name-short"]." was denied!</p><br/><p>Please check with the ".$group->val('groupname')." ".$_COMPANY->getAppCustomization()['group']["name-short"]." admin if you have further questions.</p>";
    }

    if ($sendEmail) {
        $email = $requester->val('email');
        $app_type = $_ZONE->val('app_type');
        $reply_addr = $group->val('replyto_email');
        $from = $group->val('from_email_label') . ' ' . $_COMPANY->getAppCustomization()['group']["name-short"] . ' Join Request';
        $subject = "Your request has been processed";

        $msg = <<<EOMEOM
        {$emailMessage}
EOMEOM;
        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $emesg = str_replace('#messagehere#', $msg, $template);
        $_COMPANY->emailSend2($from, $email, $subject, $emesg, $app_type, $reply_addr);
    }

    AjaxResponse::SuccessAndExit_STRING(1, '', $message, gettext('Success'));
}
elseif (isset($_GET['manageJoinRequestEmailSettings'])   && $_SERVER['REQUEST_METHOD'] === 'GET'){
	$encGroupId = $_GET['manageJoinRequestEmailSettings'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<1 || ($group = Group::GetGroup($groupid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $modal_title = gettext("Send Join Requests to");    
    $joinRequestSetting = $group->getJoinRequestMailSettings();
    include(__DIR__ . "/views/templates/manage_join_requests_settings.php");
}
elseif (isset($_GET['updateJoinRequestSetting']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    // Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $enable_mail_to_leaders = filter_var($_POST['leaders'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $enable_specific_emails = filter_var($_POST['specific_emails'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $specific_emails = $_POST['emailInputValue'] ?? '';
    if($specific_emails){
        $emailsArray = explode(',',$specific_emails);
        if(count($emailsArray) > 3){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Enter only 3 comma separated emails."), gettext('Error'));
        }
        $validEmails = [];
        foreach ($emailsArray as $email) {
            $email = trim($email);
            if(filter_var($email, FILTER_VALIDATE_EMAIL) && $_COMPANY->isValidAndRoutableEmail($email)){
                $validEmails[] = $email;
            } else{
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Enter valid Emails"), gettext('Error'));
            }
        }
        // Convert it back
        $specific_emails = implode(',',$validEmails);
    }

    if ($group->updateJoinRequestMailSettings($enable_mail_to_leaders, $enable_specific_emails, $specific_emails)){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Join request email settings updated successfully"), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again"), gettext('Error'));
    }
}
elseif (isset($_GET['resetContentFilterState'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($state = (int) $_GET['state']) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $filter = array('state'=>$_COMPANY->encodeId($state), 'year'=>$_COMPANY->encodeId(date('Y')));
    echo json_encode($filter);
}
elseif (isset($_GET['goto_homepage'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    echo 'home';
}
elseif (isset($_GET['initPermanentDeleteConfirmation']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (
        ($id = $_COMPANY->decodeId($_GET['id']))<1 ||
        ($section = $_GET['section']) == ''

    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $dataObj = null;
    if ($section == Teleskope::TOPIC_TYPES['POST']){
        $dataObj = Post::GetPost($id);
        if($dataObj){
            //TODO
        }
    } elseif($section == Teleskope::TOPIC_TYPES['EVENT']){
        $dataObj = Event::GetEvent($id);
        if($dataObj){
            //TODO
        }
    } elseif($section == Teleskope::TOPIC_TYPES['NEWSLETTER']){
        $dataObj = Newsletter::GetNewsletter($id);
        if($dataObj){
            //TODO
        }
    } elseif ($section == Teleskope::TOPIC_TYPES['RESOURCE']){
        $dataObj = Resource::GetResource($id);
        if($dataObj){
           //TODO
        }
    } elseif($section == Teleskope::TOPIC_TYPES['DISCUSSION']){
        $dataObj = Discussion::GetDiscussion($id);
        if($dataObj){
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : false;
            $createdby = $dataObj->val('createdby');
            $modalTitle = gettext('Discussion deletion confirmation');
            $enc_groupId = $_COMPANY->encodeId($dataObj->val('groupid'));
            $enc_objectId = $_COMPANY->encodeId($id);
            $functionName = "deleteDiscussion('".$enc_objectId."',".$redirect.")";
            $whatWillBeDeleted = gettext('I understand that discussion will be permanently deleted.');
        }
    } else {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if ($dataObj && !$_USER->canCreateOrPublishOrManageContentInScopeCSV($dataObj->val('groupid'), $dataObj->val('chapterid'),$dataObj->val('channelid')) && $createdby != $_USER->id()
    ) { //Allow creators to edit unpublished content
        header(HTTP_FORBIDDEN);
        exit();
    }
    include(__DIR__ . "/views/common/general_permanent_delete_confirmation_modal.php");
}

elseif (isset($_GET['downloadMobileApp'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization check
    // Not Needed
    // gets the modal box from this view
    include(__DIR__ . "/views/templates/download_mobile_app.template.php");

}

elseif (isset($_GET['getGroupMembersListTable'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
   
    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    } 
    $encGroupId = $_COMPANY->encodeId($groupid);

    $chapters = Group::GetChapterList($groupid);
    $channels= Group::GetChannelList($groupid);

    include(__DIR__ . "/views/templates/group_members_list_table_view.template.php");
 
}
// check previous subscription
elseif (isset($_GET['checkCurrentMailingList']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Add Data Validation

    // Add Authorization Check
    if (!$_USER->canManageCompanySomething() && !$_USER->canCreateContentInCompanySomething() && !$_USER->canPublishContentInCompanySomething()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    // Check previously subscribed mailing list
    $subsciption = TeleskopeMailingList::GetMyMailingList();
    $currentSubscription = array('join_product_list' => '', 'join_training_list'=> '', 'join_webinar_list'=> '' );
    
    if($subsciption){        
        $currentSubscription['join_product_list'] = $subsciption[0]['join_product_list'] ? 'checked' : '';
        $currentSubscription['join_training_list'] = $subsciption[0]['join_training_list'] ? 'checked' : '';
        $currentSubscription['join_webinar_list'] = $subsciption[0]['join_webinar_list'] ? 'checked' : '';
    }
    $currentSubscription['text_heading'] = gettext('By checking the boxes below, you can join one of our mailing lists and stay up to date with the latest product updates, upcoming trainings and webinars. If you no longer wish to receive content from either list, you can always uncheck the corresponding box at any time. Please select one or more of the following options');
    $currentSubscription['text_join_product_list'] = gettext('Send me product updates');
    $currentSubscription['text_join_training_list'] = gettext('Send me upcoming training updates');
    $currentSubscription['text_join_webinar_list'] = gettext('Send me webinar updates');


    echo json_encode($currentSubscription); 

}
// Add or update subscription
elseif (isset($_GET['joinMailingList']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Add Data Validation

    // Add Authorization Check
    if (!$_USER->canManageCompanySomething() && !$_USER->canCreateContentInCompanySomething() && !$_USER->canPublishContentInCompanySomething()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    // grab values
    $join_product_list = $_POST["productUpdates"] === 'true'? 1: 0;
    $join_training_list = $_POST["trainingUpdates"] === 'true'? 1: 0;
    $join_webinar_list = $_POST["webinarUpdates"] === 'true'? 1: 0;

    // add subscription or update
    $subsciption = TeleskopeMailingList::AddOrUpdateUserMailingList($_USER->id(), $join_product_list, $join_training_list, $join_webinar_list);

	if ($join_product_list || $join_training_list || $join_webinar_list) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Your mailing list subscription has been updated'), gettext('Success'));
    }else{
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('You have successfully unsubscribed from mailing list'), gettext('Success'));
    }

}
elseif (isset($_GET['filterHomeFeedsByContentTypes'])){

    [$groupCategoryRows, $groupCategoryIds, $group_category_id] = ViewHelper::InitGroupCategoryVariables();

    $page = 1;

    $landing_page = intval($_COMPANY->getAppCustomization()['group']['homepage']['show_my_groups_option'] ? $_USER->getUserPreference(UserPreferenceType::ZONE_ShowMyGroups) : 0);
    $myGroupsOnly = $landing_page;

    $tags = array();
    if (isset($_GET['groupTags'])){
        $tags = $_GET['groupTags'];
    }
    $groupIdAry = Group::GetAvailableGroupsForGlobalFeeds($group_category_id, $myGroupsOnly,$tags);
    $groupIdAry[] = 0; // Add groupid zero to the list, only after the groups are fetched else it shows blank tile
    $default_content_types = Content::GetAvailableContentTypes();
    $selected_content_types = (array) $_GET['contentFilter'] ?? array();
    $include_content_types = array_intersect($default_content_types,$selected_content_types);
    $feedsData = ViewHelper::GetHomeFeeds($groupIdAry, $myGroupsOnly,$page,MAX_HOMEPAGE_FEED_ITERATOR_ITEMS,$include_content_types);
    $feeds =  $feedsData['feeds'];
    $contentsCount = $feedsData['contents_count_before_processing'];
    include(__DIR__ . '/views/home/feed_rows.template.php');
}

elseif (isset($_GET['updateGroupJoinLeaveButton'])){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $enc_groupid    = $_COMPANY->encodeId($groupid);
    $chapters = Group::GetChapterList($groupid);
    $channels= Group::GetChannelList($groupid);

    include(__DIR__ . "/views/templates/group_manage_membership.template.php");
}
elseif (isset($_GET['configureInbox']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
        // Allow external_email to be changed only if current value is empty. Allowing external_email changes to users who
        // already have external email set conflicts with OTP based login feature.
       if ($_USER->isUserInboxEnabled() && empty($_USER->val('external_email'))) {
            $providedExternalEmail = trim($_POST['external_email'] ?? '');
            if (Sanitizer::SanitizeEmail($providedExternalEmail) === $providedExternalEmail) { // It is a allowed email
                $update = $_USER->updateExternalEmailAddress($providedExternalEmail);
                if($update){
                    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('External Email updated successfully !'), gettext('Success'));
                }elseif($update == 0){
                    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('A valid company email cannot be set as an external email.'), gettext('Error'));
                }
            }  
        }
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Try again later!'), gettext('Error'));
}
elseif (isset($_GET['readInboxMessage'])){
     //Data Validation
     if (($messageid = $_COMPANY->decodeId($_GET['messageid']))<1) {
     header(HTTP_BAD_REQUEST);
     exit();
    }
     // mark as read if unread
     $messageData = UserInbox::GetMessage($messageid);
     if(!$messageData['readon']){
        UserInbox::ReadInboxMessage($messageid);
    }
    include(__DIR__ . "/views/inbox_message_modal.html");
}
elseif (isset($_GET['performBulkAction']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'];
    $messageIds = $_POST['messageIds'];

    if ($action === 'mark_as_read') {
        foreach ($messageIds as $messageId) {
            $messageId = $_COMPANY->decodeId($messageId);
            UserInbox::ReadInboxMessage((int)$messageId);
        }
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Messages Marked as Read successfully !'), gettext('Success'));
    } elseif ($action === 'delete') {
        foreach ($messageIds as $messageId) {
            $messageId = $_COMPANY->decodeId($messageId);
            UserInbox::DeleteInboxMessage((int)$messageId);
        }
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Messages deleted successfully !'), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Try again later!'), gettext('Error'));
   
}
elseif (isset($_GET['searchOrganizations']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchTerm = $_POST['searchTerm'];
    // $searchField = $_POST['searchField'];

    $searchResults = Organization::SearchOrganizationsInPartnerPath($searchTerm);
    if (empty($searchResults['errors']['code'])) {
        $returnResults = array_map(function ($org) use ($_COMPANY) {
            $org['organization_id'] = $_COMPANY->encodeId($org['organization_id']);
            $org['orgid'] = $_COMPANY->encodeId($org['orgid']);
            return $org;
        }, $searchResults['results']);
        echo json_encode($returnResults);
        exit();
    } else {
        Http::Unavailable($searchResults['errors']['message']);
        exit();
    }
    exit();
}
elseif(isset($_GET['manageDynamicListModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (!isset($_GET['groupid']) ||
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))< 0 
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Fetch dynamic list based on zone/group
    $listScope = $groupid ? 'group' : 'zone'; 
    $lists = DynamicList::GetAllLists($listScope);
    // Fiter by user created by
    $filteredLists = array_filter($lists, function($list) use ($_USER){
        return $list->val('createdby') == $_USER->id();
    });
    $status = array('0'=>'Inactive','1'=>"Active",'2'=>'Draft');
    $bgcolor = array('0'=>'#fde1e1','1'=>"#ffffff",'2'=>'#ffffce');
    $modalTitle = gettext('Manage Dynamic Lists');
    // Table headers with width
    $tableHeaders = [
        'List Name <small>(uses)</small>' => '40%',
        'List Description' => '50%',
        'Action ' => '10%'
     ];
    // gets the modal box from this view
    include(__DIR__ . "/views/manage_dynamic_lists_modal.template.php");
}
elseif (isset($_GET['addNewDynamicList']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<0) {
		header(HTTP_BAD_REQUEST);
		exit();
	}
    $pagetitle = "New Dynamic List";
    $success = null;
    $error = null;
    $edit = null;
    $id = 0;
    $catalog_categories = array_values(UserCatalog::GetAllCatalogCategories());
    $postCreationActivate = true;
    $scope_lock = ($groupid) ? 'group' : 'zone';
    include(__DIR__ . "/views/common/dynamic_list_modal.php");
}
elseif (isset($_GET['getDynamicListUsers']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Data Check
    if (
        empty($listids = $_COMPANY->decodeIdsinArray($_POST['listid'])) ||
        empty($_POST['groupid']) ||
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    foreach ($listids as $lid) {
        $list = DynamicList::GetList($lid);
        $invalidCatalogs = $list?->listInvalidCatalogs();
        if ($list && !empty($invalidCatalogs)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', "The {$list->val('list_name')} list references the following catalogs that are no longer available: " . implode(',', $invalidCatalogs), 'Error!');
        }
    }

    $userIds = DynamicList::GetUserIdsByListIds(implode(',', $listids));
    if (!empty($userIds)) {
        $groupOrZoneUserIds = explode(',', Group::GetGroupMembersAsCSV($groupid, 0, 0));
        $userIds = array_intersect($userIds, $groupOrZoneUserIds);
    }

    $groupOrZoneName = ($groupid) ? Group::GetGroupName($groupid) . ' ' . $_COMPANY->getAppCustomization()['group']['name-short'] : 'Zone';

    if (!empty($userIds)) {
        $modalTitle = sprintf(gettext("Selected dynamic lists users in %s"), $groupOrZoneName);
        $usersList = User::GetZoneUsersByUserids($userIds,true);
        include(__DIR__ . "/../common/dynamic_users_list_template.html");
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("No %s users found in the selected dynamic lists."), $groupOrZoneName), '');
    }
}

elseif (isset($_GET['updateUserBio']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $bio    =   ViewHelper::RedactorContentValidateAndCleanup($_POST['bio']);
    // if (empty($bio)) {  // Allow Blank/remove bio
    //     AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Bio can't be empty"), gettext('Error'));
    // }
    if ($_USER->updateBio($bio)) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Bio updated successfully"), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong while updating the bio. Please try again."), gettext('Error'));
}

else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}
