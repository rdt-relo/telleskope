<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__.'/head.php';

/* @var Company $_COMPANY */
/* @var User $_USER */

$timezone = @$_SESSION['timezone'];

###### All Ajax Calls For Events ##########
## OK
## Get All Group
if (isset($_GET['getgrouphome'])){
    //Data Validation
    $showGlobalChapterOnly = intval($_SESSION['showGlobalChapterOnly'] ?? 0);
    $showGlobalChannelOnly = intval($_SESSION['showGlobalChannelOnly'] ?? 0);
    $chapterid = 0;
    $channelid = 0;
    if (($groupid = $_COMPANY->decodeId($_GET['getgrouphome']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
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
    $limit = 10;

    $posts = Post::GetGroupPostViewData($groupid, $showGlobalChapterOnly, $chapterid, $showGlobalChannelOnly, $channelid, $page, $limit);

// Disabled upcoming event by Aman on 5/22/22
//    $next_event = 0;
//    if ($_COMPANY->getAppCustomization()['event']['enabled'] && $_COMPANY->getAppCustomization()['post']['show_upcoming_event']) {
//        $next_event = Event::GetNextEventInGroup($groupid, 0, $chapterid,$channelid);
//    }

    $url_chapter_channel_suffix = '';
    if ($chapterid) {
        $url_chapter_channel_suffix .= '&chapterid='.$_COMPANY->encodeId($chapterid);
    }
    if ($channelid) {
        $url_chapter_channel_suffix .= '&channelid='.$_COMPANY->encodeId($channelid);
    }

    $max_iter = count($posts);
    $show_more = ($max_iter > $limit);
    $max_iter = $show_more ? $limit : $max_iter;
	include(__DIR__ . '/views/templates/group_home_html.php');
}

elseif (isset($_GET['loadMorePosts'])){
    //Data Validation
    $showGlobalChapterOnly = intval($_SESSION['showGlobalChapterOnly'] ?? 0);
    $showGlobalChannelOnly = intval($_SESSION['showGlobalChannelOnly'] ?? 0);
    $chapterid = 0;
    $channelid = 0;
    if (($groupid = $_COMPANY->decodeId($_GET['loadMorePosts']))<1 ||
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

    $posts = Post::GetGroupPostViewData($groupid, $showGlobalChapterOnly, $chapterid, $showGlobalChannelOnly, $channelid, $page, $limit);

    $url_chapter_channel_suffix = '';
    if ($chapterid) {
        $url_chapter_channel_suffix .= '&chapterid='.$_COMPANY->encodeId($chapterid);
    }
    if ($channelid) {
        $url_chapter_channel_suffix .= '&channelid='.$_COMPANY->encodeId($channelid);
    }

    $max_iter = count($posts);
    $show_more = ($max_iter > $limit);
    $max_iter = $show_more ? $limit : $max_iter;
	include(__DIR__ . '/views/templates/group_posts_rows.template.php');
}

elseif (isset($_GET['manageGlobalAnnouncements'])){
    $encGroupId=$_COMPANY->encodeId(0);
    if ($_GET['manageGlobalAnnouncements']){
        if (($groupid = $_COMPANY->decodeId($_GET['manageGlobalAnnouncements']))<0 ||
            ($group = Group::GetGroup($groupid)) === null ) {
            header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
            exit();
        }
        $encGroupId=$_COMPANY->encodeId($groupid);
    } else {
        $groupid = 0;
        $group = Group::GetGroup($groupid);
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


  	include(__DIR__ . '/views/templates/manage_global_announcements.template.php');
    exit();
}
elseif (isset($_GET['getAnnouncementsList'])){

    if (($groupid = $_COMPANY->decodeId($_GET['getAnnouncementsList']))<0 ||
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
    if ($isactive == '1'){ // Only Active
        $isactiveCondition = " AND a.isactive='".Post::STATUS_ACTIVE."'";
        $dateField = 'a.publishdate';
    } elseif ($isactive == '2'){ // Draft and under review
        $isactiveCondition = ' AND a.isactive IN ('.Post::STATUS_DRAFT.','.Post::STATUS_UNDER_REVIEW.','.Post::STATUS_AWAITING.')';
        $dateField = 'a.modifiedon';
    } elseif ($isactive == '0') { // Draft and under review
        $isactiveCondition = " AND a.isactive='".Event::STATUS_INACTIVE."'";
        $dateField = 'a.modifiedon';
    }

    $orderFields = ['a.title','a.title',$dateField,'b.firstname'];
   
    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';

    if($orderIndex == 4){
         $orderFields[$orderIndex] =" CASE p.approval_status WHEN 'requested' THEN 1 WHEN 'processing' THEN 2 WHEN 'approved' THEN 3 WHEN 'denied' THEN 4 WHEN 'reset' THEN 5 END";
     }
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

   // $totalrows =  $db->get("SELECT count(1) as totalRows FROM post a LEFT JOIN users b ON b.userid=a.userid LEFT JOIN `groups` as g ON g.groupid=a.groupid LEFT JOIN group_channels ch ON ch.channelid=a.channelid WHERE a.companyid='{$_COMPANY->id()}' AND ( a.zoneid='{$_ZONE->id()}' AND a.`groupid`={$groupid} $isactiveCondition AND YEAR($dateField)=".$year."  $search $groupFilter)")[0]['totalRows'];
    $rows = $db->get("SELECT COUNT(*) OVER () AS total_matches, a.*,p.approval_stage, p.approval_status,b.firstname,b.lastname,b.jobtitle,b.picture,b.email,IFNULL(g.groupname,'Global') as groupname, IFNULL((SELECT GROUP_CONCAT(DISTINCT `chaptername` SEPARATOR '^') FROM `chapters` WHERE FIND_IN_SET(`chapterid`,a.chapterid)),'') as chaptername, IFNULL((SELECT GROUP_CONCAT(`list_name` SEPARATOR '^') FROM `dynamic_lists` WHERE FIND_IN_SET(`listid`,a.listids)),'') as listname, ch.channelname FROM post a LEFT JOIN users b ON b.userid=a.userid LEFT JOIN `groups` as g ON g.groupid=a.groupid LEFT JOIN group_channels ch ON ch.channelid=a.channelid LEFT JOIN `topic_approvals` p on p.topicid=a.postid and p.companyid={$_COMPANY->id()} AND p.zoneid={$_ZONE->id()} AND p.topictype='POS' WHERE a.companyid='{$_COMPANY->id()}' AND ( a.zoneid='{$_ZONE->id()}' AND a.`groupid`={$groupid} $isactiveCondition AND YEAR($dateField)=".$year."  $search $groupFilter) ORDER BY $orderFields[$orderIndex] $orderDir limit ".$input['start'].",".$input['length']." ");
    $totalrows = $rows[0]['total_matches'] ?? 0;
    $final = [];
    $i=0;
    foreach($rows as $row){  
        $encPostid = $_COMPANY->encodeId($row['postid']);
        $rowTitle = $row['title'] ?? '';
        $postTitle = '<a  onclick=getAnnouncementDetailOnModal("'.$encPostid.'","'.$_COMPANY->encodeId(0).'","'.$_COMPANY->encodeId(0).'")  href="javascript:void(0);" >';
        if ($row['isactive'] == Post::STATUS_DRAFT) {
            $postTitle .= '<span style="text-align:justify;color:#DB0808;">';
            $postTitle .= $rowTitle.'&nbsp';
            $postTitle .= '<img src="img/draft_ribbon.png" alt="Draft" height="16px"/>';
            $postTitle .= '</span>';
        } elseif ($row['isactive'] == Post::STATUS_UNDER_REVIEW) { 
            $postTitle .= '<span style="text-align:justify;color:darkorange;">';
            $postTitle .= $rowTitle.'&nbsp';
            $postTitle .= '<img src="img/review_ribbon.png" alt="Under Review" height="16px"/>';
            $postTitle .= '</span>';
        } elseif ($row['isactive'] == 5) { 
            $postTitle .= '<span style="text-align:justify;color:deepskyblue;">';
            $postTitle .= $rowTitle.'&nbsp';
            $postTitle .= '<img src="img/schedule.png" alt="Schedule" height="16px"/>';
            $postTitle .= '</span>';
        } else {
            $postTitle .=  '<span style="text-align:justify;">';
            $postTitle .= $rowTitle;
            $postTitle .= '</span>';
        } 
        $postTitle .= '</a>';

        $profilepic = User::BuildProfilePictureImgTag($row['firstname'],$row['lastname'], $row['picture'],'memberpic2', 'User Profile Picture', $row['userid'], 'profile_basic');
        $profilepic .= '<br/>'.$row['firstname'].' '.$row['lastname'];

        $actionButton = '<div class="" style="color: #fff; float: left;">';
        $actionButton .= '<button aria-label="'.sprintf(gettext('%1$s %2$s action dropdown'), $row['title'],Post::GetCustomName(false)).'" id="'.$encPostid.'" onclick="getAnnouncementActionButton(\''.$encPostid.'\')" class="btn-no-style dropdown-toggle fa fa-ellipsis-v mt-3" title="'.gettext("Action").'" type="button" data-toggle="dropdown"></button>';
        $actionButton .= '<ul class="dropdown-menu dropdown-menu-right" id="dynamicActionButton'.$encPostid.'" style="width: 250px; cursor: pointer;">';
        $actionButton .= '</ul>';
        $actionButton .= '</div>';
        $scope = "";
        if ($_COMPANY->getAppCustomization()['chapter']['enabled'] && $groupid && !empty($row['chaptername'])) {
            $scope .= '<p>'.$_COMPANY->getAppCustomization()['chapter']['name-short-plural'].'</p>';
            if ($row['chaptername']){
            
                $chapters = explode('^',$row['chaptername']);
                $scope .= '<ul style="margin-left: -38px;">';
                foreach ($chapters as $ch1){
                    $scope .= '<li>'.$ch1.'</li>';
                }
                $scope .= '</ul>';

            } else {
                $scope .= '-';
            }
        }
        if ($_COMPANY->getAppCustomization()['channel']['enabled'] && $groupid && !empty($row['channelname'])) {
            $scope .= '<p>'.$_COMPANY->getAppCustomization()['channel']['name-short']."<p>";
            $scope .= $row['channelname'] ? '<ul style="margin-left: -38px;"><li>'.htmlspecialchars($row['channelname']).'</li></ul>' : '-';

        }

        if (!empty($row['listname'])) {
            $scope .= '<p>'.gettext("Dynamic List").'</p>';
            if ($row['listname']){
              
                $lists = explode('^',$row['listname']);
                $scope .= '<ul style="margin-left: -38px;">';
                foreach ($lists as $l1){
                    $scope .= '<li>'.htmlspecialchars($l1).'</li>';
                }
                $scope .= '</ul>';
            } else {
                $scope .= '-';
            }
        }

        $approvalStatus = '';
        if ($isactive == '2') {
            if (!empty($row['approval_stage'])) {
                $approvalStatus = ucwords($row['approval_status']);
                $approvalStatus .= in_array($row['approval_status'], [Approval::TOPIC_APPROVAL_STATUS['PROCESSING'], Approval::TOPIC_APPROVAL_STATUS['REQUESTED']]) ? ('<br>'. gettext(' Stage ') . $row['approval_stage']) : '';
            }
        }

        $scope  = $scope ? $scope : '-';

        $postRow = array();
        $postRow[] = $postTitle;
        $postRow[] = $scope;
        $datetime = (($row['isactive'] == Post::STATUS_DRAFT || $row['isactive'] == Post::STATUS_UNDER_REVIEW) ? $row['postedon'] : $row['publishdate']);
        $postRow[] = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($datetime,true,true,true);

        $postRow[] = $profilepic;
        $postRow[] = $approvalStatus;
        $postRow[] = $actionButton;

        $final[] = array_merge(array("DT_RowId" => $row['postid']), array_values($postRow));
        
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

elseif(isset($_GET['filterAnnouncements'])){
    if (($groupid = $_COMPANY->decodeId($_GET['filterAnnouncements']))<0 )
    {
        header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
        exit();
    }
    $encGroupId=$_COMPANY->encodeId($groupid);
  	include(__DIR__ . '/views/templates/group_announcement_table.template.php');
}
elseif (isset($_GET['manageGlobalEvents'])){
    $encGroupId=$_COMPANY->encodeId(0);
    if ($_GET['manageGlobalEvents']){
        if (($groupid = $_COMPANY->decodeId($_GET['manageGlobalEvents']))<0 || ($group = Group::GetGroup($groupid)) === null ) {
            header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
            exit();
        }
        $encGroupId=$_COMPANY->encodeId($groupid);
    } else {
        $groupid = 0;
        $group = Group::GetGroup($groupid);
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
    if (!empty($_GET['erg_filter_section'])){
        $erg_filter_section = $_COMPANY->decodeId($_GET['erg_filter_section']);
    }

	include(__DIR__ . '/views/templates/manage_global_event.template.php');
}

elseif (isset($_GET['getEventsList'])){

    if (($groupid = $_COMPANY->decodeId($_GET['getEventsList']))<0 ||
        (isset($_GET['isactive']) && ($isactive = $_COMPANY->decodeId($_GET['isactive'])) < 0) ||
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

    $orderFields = ['a.eventid','a.eventtitle','b.firstname','a.start','a.eventtitle','a.eventtitle','p.approval_stage'];

    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';

    if($orderIndex == 6){
        $orderFields[$orderIndex] =" CASE p.approval_status WHEN 'requested' THEN 1 WHEN 'processing' THEN 2 WHEN 'approved' THEN 3 WHEN 'denied' THEN 4 WHEN 'reset' THEN 5 END";
    }
    $search = "";
    if ($input['search']){
        $search_by_event_id = $_COMPANY->decodeIdForReport($input['search']);
        if ($search_by_event_id) {
            $search = " AND (a.eventid = {$search_by_event_id})";
        } else {
            $search = " AND (a.eventtitle LIKE '%".$input['search']."%' OR a.start LIKE '%".$input['search']."%' OR b.firstname LIKE '%".$input['search']."%' OR b.lastname LIKE '%".$input['search']."%' OR b.email LIKE '%".$input['search']."%')";
        }
    }
	
    $isactiveCondition = " AND a.isactive='".Event::STATUS_ACTIVE."'";

    if ($isactive == '1'){ // Only Active
        $isactiveCondition = " AND a.isactive='".Event::STATUS_ACTIVE."'";
    } elseif ($isactive == '2'){ // Draft and under review
        $isactiveCondition = ' AND a.isactive IN ('.Event::STATUS_DRAFT.','.Event::STATUS_UNDER_REVIEW.','.Event::STATUS_AWAITING.')';
     } elseif ($isactive == '0'){ // Cancelled Event with status Zero
         $isactiveCondition = " AND a.isactive='".Event::STATUS_INACTIVE."'";
    }

    $yearCondition = " YEAR(a.start)='".$year."'";
    if ($year >date('Y')){
        $yearCondition = " YEAR(a.start)>='".$year."'";
    }
    $yearCondition = " AND ({$yearCondition} OR (a.eventid = a.event_series_id AND {$year} BETWEEN YEAR(a.start) AND YEAR(a.end)))";

    $groupFilter = "";
    if ($groupStateType){
        if ($groupStateType === 1) { // Chapter filter
            $groupFilter = " AND FIND_IN_SET({$groupStateId}, a.chapterid)";
        } else if ($groupStateType === 2){
            $groupFilter = "AND ch.channelid= '".$groupStateId."'";;
        }
    }
   
    if ($groupid) {
        $collaboratingEventCondition = " a.`groupid`={$groupid} OR FIND_IN_SET({$groupid},a.collaborating_groupids) OR FIND_IN_SET({$groupid},a.collaborating_groupids_pending)";
    } else {
        $collaboratingEventCondition = " a.`groupid`={$groupid} AND (a.collaborating_groupids = '' OR a.collaborating_groupids IS NULL) AND ( a.collaborating_groupids_pending='' OR a.collaborating_groupids_pending IS NULL)";
    }

    $encGroupId = $_COMPANY->encodeId($groupid);
    // New Filters
    $upcomingEvents = $_POST['upcomingEvents']?:'false';
    $pastEvents = $_POST['pastEvents']?:'false';
    $upcomingOrPastCondition = "";
    if ($upcomingEvents == 'true' && $pastEvents == 'false') {
        $upcomingOrPastCondition = " AND a.`end` >= NOW()";
    } elseif($upcomingEvents == 'false' && $pastEvents == 'true') {
        $upcomingOrPastCondition = " AND a.`end` < NOW()";
    }

    $reconciledEvent = $_POST['reconciledEvent']??'false';
    $notReconciledEvent = $_POST['notReconciledEvent']??'false';
    $reconciledCondition = "";
    if ($reconciledEvent == 'true' && $notReconciledEvent == 'false') {
        $reconciledCondition = " AND a.`is_event_reconciled` = 1";
    } elseif($reconciledEvent == 'false' && $notReconciledEvent == 'true') {
        $reconciledCondition = " AND a.`is_event_reconciled` = 0";
    }

    $topicType = Teleskope::TOPIC_TYPES['EVENT'];

    // Note:
    // Events that are collaborations across zones can be edited only from their host zone ... hence
    // we are using zoneid filter to show events hosted only in this zone.
    //$totalrows =  $db->get("SELECT count(1) as totalRows FROM events a LEFT JOIN users b ON b.userid=a.userid LEFT JOIN group_channels ch ON ch.channelid=a.channelid WHERE a.companyid={$_COMPANY->id()} AND (a.zoneid={$_ZONE->id()} AND ( {$collaboratingEventCondition} ) AND (a.event_series_id = 0 OR a.event_series_id = a.eventid) $isactiveCondition  $yearCondition  AND a.`eventclass` NOT IN('holiday','teamevent') $search $groupFilter {$upcomingOrPastCondition} {$reconciledCondition} )")[0]['totalRows']; Teleskope::TOPIC_TYPES['EVENT']

    $events = $db->get("SELECT COUNT(*) OVER () AS total_matches,a.*,b.firstname,b.lastname,b.jobtitle,b.picture,b.email,p.approval_status,p.approval_stage,IFNULL((SELECT GROUP_CONCAT(DISTINCT `chaptername` SEPARATOR '^') FROM `chapters` WHERE FIND_IN_SET(`chapterid`,a.chapterid)),'') as chaptername,IFNULL((SELECT GROUP_CONCAT(`list_name` SEPARATOR '^') FROM `dynamic_lists` WHERE FIND_IN_SET(`listid`,a.listids)),'') as listname,ch.channelname FROM events a LEFT JOIN users b ON b.userid=a.userid LEFT JOIN group_channels ch ON ch.channelid=a.channelid LEFT JOIN `topic_approvals` p on p.topicid=a.eventid and p.companyid={$_COMPANY->id()} AND p.zoneid={$_ZONE->id()} AND p.topictype='{$topicType}'  WHERE a.companyid={$_COMPANY->id()} AND (a.zoneid={$_ZONE->id()} AND ( {$collaboratingEventCondition} ) AND (a.event_series_id = 0 OR a.event_series_id = a.eventid) $isactiveCondition $yearCondition AND a.`eventclass` NOT IN('holiday','teamevent') $search $groupFilter {$upcomingOrPastCondition} {$reconciledCondition}) AND a.schedule_id=0 ORDER BY $orderFields[$orderIndex] $orderDir limit ".$input['start'].",".$input['length']." ");
    $totalrows = $events[0]['total_matches'] ?? 0;

    $final = [];
    foreach($events as $event){  
        $encEventid = $_COMPANY->encodeId($event['eventid']);
        $ev= Event::GetEvent($event['eventid']);
        if (!$ev){
            continue;
        }
        $eventTitle = '';
        $attendees_total = 0;
        $attendees_yes = 0;
        $attendees_maybe = 0;
        if($ev->isSeriesEventSub() || $ev->isSeriesEventHead()){
            $eventSeries= Event::GetEvent($event['event_series_id']);
            $eventTitle .= "<small>[".gettext('Event Series')." ]</small> ";
            $seriesEvents = Event::GetEventsInSeries($event['event_series_id']);
            foreach($seriesEvents as $seriesEvent){
                $attendees_total += $seriesEvent->getJoinersCount();
                $attendees_yes += $seriesEvent->getRsvpYesCount();
                $attendees_maybe += $seriesEvent->getRsvpMaybeCount();
            }
        } else {
            $attendees_total = $ev->getJoinersCount();
            $attendees_yes = $ev->getRsvpYesCount();
            $attendees_maybe = $ev->getRsvpMaybeCount();
        }
        $attendees =
            gettext('Total'). ':&nbsp;' . $attendees_total . '<br>' .
            '<small>'. gettext('Yes'). ':&nbsp;' . $attendees_yes . '</small>' . '<br>' .
            '<small>'. gettext('Maybe') . ':&nbsp;' . $attendees_maybe . '</small>';

        if($ev->val('isprivate')){
            $eventTitle .= '<small style="background-color: lightyellow;">['.gettext("Private Event").']</small>';
        }
        if (!empty($eventTitle)) {
            $eventTitle .= '<br>';
        }
        $eventTitle .= '<a role="button" onclick="getEventDetailModal(\''.$encEventid.'\', \''.$_COMPANY->encodeId(0).'\',\''.$_COMPANY->encodeId(0).'\')" href="javascript:void(0);" >';

        if ($event['isactive'] == Event::STATUS_DRAFT) {
            $eventTitle .= '<span style="text-align:justify;color:#DB0808;">';
            $eventTitle .= $event['eventtitle'].'&nbsp';
            $eventTitle .=  '<img src="img/draft_ribbon.png" alt="Draft" height="16px"/>';
            $eventTitle .= '</span>';
        } elseif ($event['isactive'] == Event::STATUS_UNDER_REVIEW) {
            $eventTitle .= '<span style="text-align:justify;color:darkorange;">';
            $eventTitle .= $event['eventtitle'].'&nbsp';
            $eventTitle .= '<img src="img/review_ribbon.png" alt="Under Review" height="16px"/>';
            $eventTitle .= '</span>';
        } elseif ($event['isactive'] == Event::STATUS_AWAITING) {
            $eventTitle .= '<span style="text-align:justify;color:deepskyblue;">';
            $eventTitle .= $event['eventtitle'].'&nbsp';
            $eventTitle .= '<img src="img/schedule.png" alt="Schedule" height="16px"/>';
            $eventTitle .= '</span>';
        } elseif ($event['isactive'] == Event::STATUS_INACTIVE) {
            $eventTitle .= '<span style="text-align:justify;color:purple;">';
            $eventTitle .= $event['eventtitle'].'&nbsp';
            $eventTitle .= '</span>';
            $eventTitle .= '<sup class="left-ribbon ribbon-purple">' . gettext('Cancelled') . '</sup>';

        } else {
            $eventTitle .= '<span style="text-align:justify;">';
            $eventTitle .= $event['eventtitle'];
            $eventTitle .= '</span>';
        }
        $eventTitle .=  '</a>';
        $collebratedBetween = '';
        if($event['collaborating_groupids']){ 
            
            $collebratedBetween = $ev->getFormatedEventCollaboratedGroupsOrChapters(true);
            $eventTitle .= '<p><u><small>'.gettext("Collaboration between").':</small></u></p>';
            $eventTitle .= '<p><small>'.$collebratedBetween.'</small></p>';
        }
        if($event['collaborating_groupids_pending']){ 
            $collebratedBetweenPending = $ev->getFormatedEventPendingCollaboratedGroups();
            if ($collebratedBetweenPending) {
                $eventTitle .= '<p><u><small>'.gettext("Pending Collaboration Requests").':</small></u></p>';
                $eventTitle .= '<p><small>'.$collebratedBetweenPending.'</small></p>';
            }
        }
        //$startDate = $db->covertUTCtoLocal("M d,Y H:i",$event['start'],$timezone);
        $startDate = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($event['start'],true,true,true);

        $name = trim($event['firstname']." ".$event['lastname']);

        $profilepic = User::BuildProfilePictureImgTag($event['firstname'], $event['lastname'], $event['picture'], 'memberpic2', 'User Profile Picture', $event['userid'], 'profile_basic');
        $profilepic .= '<br/>'.$event['firstname'].' '.$event['lastname'];

        $actionButton = '<div class="" style="color: #fff; float: left;" >';
        $actionButton .= '<button aria-label="'.sprintf(gettext('%1$s Event action dropdown'), $event['eventtitle']).'" id="'.$encEventid.'" onclick="getEventActionButton(\''.$encGroupId.'\',\''.$encEventid.'\')" class="btn-no-style dropdown-toggle fa fa-ellipsis-v mt-3" title="Action" type="button" data-toggle="dropdown"></button>';
        $actionButton .= '<ul class="dropdown-menu dropdown-menu-right" id="dynamicActionButton'.$encEventid.'" style="width: 250px; cursor: pointer;">';
        $actionButton .= '</ul>';
        $actionButton .= '</div>';

        $scope = "";       

        //if ($_COMPANY->getAppCustomization()['chapter']['enabled'] && !empty($event['chaptername'])) {
        if ($_COMPANY->getAppCustomization()['chapter']['enabled'] && $ev->val('chapterid')) {
            $chapters = Group::GetChapterNamesByChapteridsCsv($ev->val('chapterid'));
            if (!empty($chapters)) {
                $chapters = Arr::GroupBy($chapters, 'groupname');
          
                foreach($chapters as $gname => $chptrs){
                    $scope .= '<p class="small"><u>'. $gname.' - '.$_COMPANY->getAppCustomization()['chapter']['name-short-plural'].'</u></p>';
                    foreach ($chptrs as $ch1){
                        if ($ch1['chaptername']){
                            $scope .= '<li class="small">'.$ch1['chaptername'].'</li>';
                        } else {
                            $scope .= '-';
                        }
                    }
                }
            } else {
                $scope .= '-';
            }
        }
        
        if($event['collaborating_chapterids_pending']){ 
            $chaptersCollebratedBetweenPending = $ev->getEventPendingCollabroatedChapters();
            if ($chaptersCollebratedBetweenPending) {
                $scope .= '<p><u><small>'.gettext("Pending Collaboration Requests").':</small></u></p>';
                $scope .= '<p><small>'.$chaptersCollebratedBetweenPending.'</small></p>';
            }
        }

        if ($_COMPANY->getAppCustomization()['channel']['enabled'] && $groupid && $event['channelid']) {

            if ($ev->val('groupid')) {
                $channelScope = Group::GetGroupName($ev->val('groupid')).' - '. $_COMPANY->getAppCustomization()['channel']['name-short'];
            } else {
                $channelScope = $_COMPANY->getAppCustomization()['channel']['name-short'];
            }
            $scope .= '<p class="small"><u>'.$channelScope."</u><p>";
           
            $scope .=  $event['channelid'] ?  '<li>'.htmlspecialchars(Group::GetChannelName($event['channelid'],$event['groupid'])['channelname']).'</li>' : '-';
        }

        if (!empty($event['listname'])) {
            $scope .= '<p>'.gettext("Dynamic List").'</p>';
            if ($event['listname']){
              
                $lists = explode('^',$event['listname']);
                $scope .= '<ul style="margin-left: -38px;">';
                foreach ($lists as $l1){
                    $scope .= '<li>'.htmlspecialchars($l1).'</li>';
                }
                $scope .= '</ul>';
            } else {
                $scope .= '-';
            }
        }

        $approvalStatus = '';
        if ($isactive == '2') {
           
            if (!empty($event['approval_stage'])) {
                $approvalStatus = ucwords($event['approval_status']);
                $approvalStatus .= in_array($event['approval_status'], [Approval::TOPIC_APPROVAL_STATUS['PROCESSING'], Approval::TOPIC_APPROVAL_STATUS['REQUESTED']]) ? ('<br>'. gettext(' Stage ') . $event['approval_stage']) : '';
            }
        }

        $scope = $scope ?: '-';
        $eventRow = array();
        $eventRow[] = $_COMPANY->encodeIdForReport($event['eventid']);
        $eventRow[] = $eventTitle;
        $eventRow[] = $scope;
        $eventRow[] = $startDate;
        $eventRow[] = $profilepic;
        $eventRow[] = $attendees;
        $eventRow[] = $approvalStatus;
        $eventRow[] = $actionButton;

        $final[] = array_merge(array("DT_RowId" => $event['eventid']), array_values($eventRow));
    }
    $json_data = array(
                    "draw"=> intval( $input['draw'] ), 
                    "recordsTotal"    => intval($totalrows),
                    "recordsFiltered" => intval($totalrows), 
                    "data"            => $final
                );

    echo json_encode($json_data);

}

elseif(isset($_GET['filterEvents'])){
    if (($groupid = $_COMPANY->decodeId($_GET['filterEvents']))<0 ) {
        header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
        exit();
    }
    $encGroupId=$_COMPANY->encodeId($groupid);

    $upcomingEvents = $_GET['upcomingEvents']??false;
    $pastEvents = $_GET['pastEvents']??false;
    $reconciledEvent = $_GET['reconciledEvent']??false;
    $notReconciledEvent = $_GET['notReconciledEvent']??false;

  	include(__DIR__ . '/views/templates/group_events_table.template.php');
}

elseif (isset($_GET['getNewslettersList'])){

    if (($groupid = $_COMPANY->decodeId($_GET['getNewslettersList']))<0 ||
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

    $orderFields = ['newsletters.newslettername','newsletters.newslettername','users.firstname','newsletters.modifiedon'];

    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
    if($orderIndex == 4){
         $orderFields[$orderIndex] =" CASE p.approval_status WHEN 'requested' THEN 1 WHEN 'processing' THEN 2 WHEN 'approved' THEN 3 WHEN 'denied' THEN 4 WHEN 'reset' THEN 5 END";
     }
   
    $search = "";
    if ($input['search']){
        $search = " AND (newsletters.newslettername LIKE '%".$input['search']."%' OR newsletters.modifiedon LIKE '%".$input['search']."%' OR users.firstname LIKE '%".$input['search']."%' OR users.lastname LIKE '%".$input['search']."%' OR users.email LIKE '%".$input['search']."%')";
    }
	
    $isactiveCondition = " AND newsletters.isactive='".Newsletter::STATUS_ACTIVE."'";

    if ($isactive == '1'){ // Only Active
        $isactiveCondition = " AND newsletters.isactive='".Newsletter::STATUS_ACTIVE."'";
    } elseif ($isactive == '2'){ // Draft and under review
        $isactiveCondition = ' AND newsletters.isactive IN ('.Newsletter::STATUS_DRAFT.','.Newsletter::STATUS_UNDER_REVIEW.','.Newsletter::STATUS_AWAITING.')';
    } elseif ($isactive == '0'){ // Cancelled  with status Zero
        $isactiveCondition = " AND a.isactive='".Event::STATUS_INACTIVE."'";
    }

    $groupFilter = "";
    if ($groupStateType){
        if ($groupStateType === 1) { // Chapter filter
            $groupFilter = " AND FIND_IN_SET('".$groupStateId."',newsletters.chapterid )";
        } else if ($groupStateType === 2){
            $groupFilter = "AND newsletters.channelid= '".$groupStateId."'";;
        }
    }

    if ($year > date('Y')){
        $year = date('Y');
    }
    // Datatalbe dependencies
   // $totalrows =  $db->get("SELECT count(1) as totalRows FROM `newsletters` LEFT JOIN users on users.userid=newsletters.userid LEFT JOIN `groups` on `groups`.groupid=newsletters.groupid LEFT JOIN group_channels ON group_channels.channelid=newsletters.channelid LEFT JOIN `topic_approvals` p on p.topicid=newsletters.newsletterid and p.companyid={$_COMPANY->id()} AND p.zoneid={$_ZONE->id()} AND p.topictype='NWS'  WHERE newsletters.companyid = '{$_COMPANY->id()}' AND newsletters.zoneid='{$_ZONE->id()}' AND (newsletters.groupid='{$groupid}' AND YEAR(newsletters.modifiedon)=".$year." $isactiveCondition  $search  $groupFilter ) ")[0]['totalRows'];
    $rows = $db->get("SELECT COUNT(*) OVER () AS total_matches, newsletters.*,p.approval_stage,p.approval_status,IFNULL(`groups`.groupname,'') as groupname, IFNULL((SELECT GROUP_CONCAT(DISTINCT`chaptername` SEPARATOR '^') FROM `chapters` WHERE FIND_IN_SET(`chapterid`,newsletters.chapterid)),'') as chaptername, IFNULL((SELECT GROUP_CONCAT(`list_name` SEPARATOR '^') FROM `dynamic_lists` WHERE FIND_IN_SET(`listid`,newsletters.listids)),'') as listname, users.firstname,users.lastname,users.picture,users.email, group_channels.channelname FROM `newsletters` LEFT JOIN users on users.userid=newsletters.userid LEFT JOIN `groups` on `groups`.groupid=newsletters.groupid LEFT JOIN group_channels ON group_channels.channelid=newsletters.channelid LEFT JOIN `topic_approvals` p on p.topicid=newsletters.newsletterid and p.companyid={$_COMPANY->id()} AND p.zoneid={$_ZONE->id()} AND p.topictype='NWS' WHERE newsletters.companyid = '{$_COMPANY->id()}' AND newsletters.zoneid='{$_ZONE->id()}' AND (newsletters.groupid='{$groupid}' AND YEAR(newsletters.modifiedon)=".$year." $isactiveCondition  $search  $groupFilter ) ORDER BY $orderFields[$orderIndex] $orderDir limit ".$input['start'].",".$input['length']." ");
    $totalrows = $rows[0]['total_matches'] ?? 0;
    $final = [];
    foreach($rows as $row){  
        $encNewsletterid = $_COMPANY->encodeId($row['newsletterid']);

        if ((int)$row['isactive'] === Newsletter::STATUS_DRAFT) {
            $newslettername  = '<span style="color:#DB0808;">'.$row['newslettername'].'</span>&nbsp;<img src="img/draft_ribbon.png" alt="Draft" height="14px"/>';
        } elseif ((int)$row['isactive'] === Newsletter::STATUS_UNDER_REVIEW) {
            $newslettername  = '<span style="color:darkorange;">'.$row['newslettername'].'</span>&nbsp;<img src="img/review_ribbon.png" alt="Under Review" height="14px"/>';
        } elseif ((int)$row['isactive'] === Newsletter::STATUS_AWAITING) {
            $newslettername  = '<span style="color:deepskyblue;">'.$row['newslettername'].'</span>&nbsp;<img src="img/schedule.png" alt="Schedule" height="14px"/>';
        } else {
            $newslettername  = $row['newslettername'];
        }

        

        $channelName = htmlspecialchars($row['channelname'] ? $row['channelname'] : '-');



        $scope = "";
        if ($_COMPANY->getAppCustomization()['chapter']['enabled'] && $groupid && !empty($row['chaptername'])) {
            $scope .= '<p>'.$_COMPANY->getAppCustomization()['chapter']['name-short-plural'].'</p>';
            if ($row['chaptername']){
                $chapters = explode('^',$row['chaptername']);
                $scope .= '<ul style="margin-left: -38px;">';
                foreach ($chapters as $ch1){
                    $scope .= '<li>'.$ch1.'</li>';
                }
                $scope .= '</ul>';
            } else {
                $scope .= '-';
            }
        }

        if ($_COMPANY->getAppCustomization()['channel']['enabled'] && $groupid && $row['channelid']) {
            $scope .= '<p>'.$_COMPANY->getAppCustomization()['channel']['name-short']."<p>";
            $scope .=  $row['channelid'] ?  '<li>'.htmlspecialchars(Group::GetChannelName($row['channelid'],$row['groupid'])['channelname']).'</li>' : '-';
        }

        if (!empty($row['listname'])) {
            $scope .= '<p>'.gettext("Dynamic List").'</p>';
            if ($row['listname']){
              
                $lists = explode('^',$row['listname']);
                $scope .= '<ul style="margin-left: -38px;">';
                foreach ($lists as $l1){
                    $scope .= '<li>'.htmlspecialchars($l1).'</li>';
                }
                $scope .= '</ul>';
            } else {
                $scope .= '-';
            }
        }
        $scope = $scope ?: '-';
        $profilepic = User::BuildProfilePictureImgTag($row['firstname'],$row['lastname'], $row['picture'],'memberpic2', 'User Profile Picture', $row['userid'], 'profile_basic');
        $profilepic .= '<br/>'.$row['firstname'].' '.$row['lastname'];

        if ((int)$row['isactive'] === Newsletter::STATUS_DRAFT) { 
            $date = $row['modifiedon'] ? '<span class="hide-span-date">'.$row["modifiedon"].'</span>'.sprintf(gettext('Draft saved on <br>%s'), $_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['modifiedon'],true,true,false)) : gettext('Draft saved');           
        } elseif ((int)$row['isactive'] === Newsletter::STATUS_UNDER_REVIEW) {
            if (time() < (strtotime($row['modifiedon'].' UTC') + 180)) { 
                $date = '<span class="hide-span-date">'.$row["modifiedon"].'</span>'. sprintf(gettext('Under Review, <br>try in %s minutes'),(int)((strtotime($row['modifiedon'].' UTC')+180-time())/60+1));
            } else {
                $date = gettext('Ready for publishing');
            }
        } elseif ((int)$row['isactive'] === Newsletter::STATUS_AWAITING) {
            $date = $row['publishdate'] ?  '<span class="hide-span-date">'.$row["publishdate"].'</span>'.sprintf(gettext('Scheduled to Publish on <br>%s'),$_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['publishdate'],true,true,false)) : gettext('Scheduled to Publish');

        } elseif ((int)$row['isactive'] === Newsletter::STATUS_ACTIVE) {
            $date = $row['publishdate'] ? '<span class="hide-span-date">'.$row["publishdate"].'</span>'.sprintf(gettext('Published on <br> %s'),$_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['publishdate'],true,true,false)) : gettext('Published');        
        }

        $actionButton = '<div class="" style="color: #fff; float: left;">';
        $actionButton .= '<button aria-label="'.sprintf(gettext('%1$s Newsletter action dropdown'), $row['newslettername']).'" id="'.$encNewsletterid.'" onclick="getNewsletterActionButton(\''.$encNewsletterid.'\')" class="btn-no-style dropdown-toggle fa fa-ellipsis-v mt-3" title="Action" type="button" data-toggle="dropdown"></button>';
        $actionButton .= '<ul class="dropdown-menu dropdown-menu-right" id="dynamicActionButton'.$encNewsletterid.'" style="width: 250px; cursor: pointer;">';
        $actionButton .= '</ul>';
        $actionButton .= '</div>';
        
        $approvalStatus = '';
        if ($isactive == '2') {
            if (!empty($row['approval_stage'])) {
                $approvalStatus = ucwords($row['approval_status']);
                $approvalStatus .= in_array($row['approval_status'], [Approval::TOPIC_APPROVAL_STATUS['PROCESSING'], Approval::TOPIC_APPROVAL_STATUS['REQUESTED']]) ? ('<br>'. gettext(' Stage ') . $row['approval_stage']) : '';
            }
        }

        $newsletterRow = array();
        $newsletterRow[] = $newslettername;
        $newsletterRow[] = $scope;
        $newsletterRow[] = $profilepic;
        $newsletterRow[] = $date;
        $newsletterRow[] = $approvalStatus;
        $newsletterRow[] = $actionButton;

        $final[] = array_merge(array("DT_RowId" => $row['newsletterid']), array_values($newsletterRow));
    }
    $json_data = array(
                    "draw"=> intval( $input['draw'] ), 
                    "recordsTotal"    => intval($totalrows),
                    "recordsFiltered" => intval($totalrows), 
                    "data"            => $final
                );

    echo json_encode($json_data);
}

elseif(isset($_GET['filterNewsletters'])){
    if (($groupid = $_COMPANY->decodeId($_GET['filterNewsletters']))<0 
    ) {
        header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
        exit();
    }
    $encGroupId=$_COMPANY->encodeId($groupid);
  	include(__DIR__ . '/views/templates/group_newsletter_table.template.php');
}
elseif (isset($_GET['showSharePostFormDynamic'])){
   
    // Authorization Check
    // Not needed
  
    if(($postid = $_COMPANY->decodeId($_GET['showSharePostFormDynamic']))<1 ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
	include(__DIR__ . '/views/templates/post_share.template.php');
}

elseif (isset($_GET['getAdminSurveys'])){
   
    // Authorization Check
    if (!$_USER->isAdmin()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $state_filter = 1; //groupState

    if (!empty($_GET['state_filter'])){
        $state_filter = $_COMPANY->decodeId($_GET['state_filter']);
    }
    $state_filter = $state_filter == 1 ? true : false;
    $surveys = ZoneMemberSurvey::GetAllSurveys(0, $state_filter);
    $surveyType = ['1'=>gettext('On Join'),'2'=>gettext('On Leave'),'3'=>gettext('Login'),'4'=>gettext('Follow Up'),'127'=>gettext("Link")];
	include(__DIR__ . '/views/templates/manage_gloabl_survey.template.php');
}

elseif (isset($_GET['openAdminSurveySettingForm'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->isAdmin()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $form_title = gettext("Survey Setting");

    if ($_ZONE->val('app_type') == 'talentpeak'){
        $surveyType = Survey2::SURVEY_TYPE['TEAM_MEMBER'];
    }  else {
        $surveyType = Survey2::SURVEY_TYPE['GROUP_MEMBER'];
    }

    $templateSurveys= Survey2::GetSurveyTemplate($surveyType);
	include(__DIR__ . "/views/templates/global_survey_setting.template.php");
}
elseif (isset($_GET['submitGlobalSurveySetting'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (!$_USER->isAdmin()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $surveyname = $_POST['surveyname'];
    $trigger    = $_POST['trigger'];
    $anonymity  = (int)$_POST['anonymity'];
    $is_required  = (int)$_POST['is_required'];
    $allow_multiple = (int)$_POST['allow_multiple'];
    $templateid  = (int)$_COMPANY->decodeId($_POST['templateid']);
   
    $survey_json = '';
    if ($templateid > 0){
        $clonedSurvey = Survey2::GetSurvey($templateid);
        $survey_json = $clonedSurvey ->val('survey_json');
    }
    
    $survey = ZoneMemberSurvey::CreateNewSurvey($surveyname, $anonymity, $survey_json, $trigger, $is_required,$allow_multiple);
    if ($survey){
        AjaxResponse::SuccessAndExit_STRING(1, 'create_survey?surveyid='.$_COMPANY->encodeId($survey->id()), gettext('Survey created successfully.'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Error'));
        echo 0;
    }
}

elseif (isset($_GET['getShareableLink'])){   

    if(($groupid = $_COMPANY->decodeId($_GET['getShareableLink']))<0 || ($id = $_COMPANY->decodeId($_GET['id'])) <0 ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
   // Authorization Check
    $linkTwo = null;
    $section = (int) $_GET['section'];
    $form_title = gettext("Shareable Link");
    $copyLinkBtnText = gettext("Copy Link");
    $copyLinkTwoBtnText = '';

    $channelName = "";
    $getChapter = array();
    if ($section === 1){ // Announcement   
        if (($post = Post::GetPost($id)) === NULL){
            exit();
        }
        
        $topicType = gettext("Announcement");
        $title = $post->val('title');
        $groupName = Group::GetGroupName($groupid);        
        $channelid = $post->val('channelid'); 
        
        if ($_COMPANY->getAppCustomization()['channel']['enabled']) {
            $getChannel = Group::GetChannelName($channelid,$groupid);
            $channelName = $getChannel['channelname'];  
        }  
        if ($_COMPANY->getAppCustomization()['chapter']['enabled']) {
            $getChapter = Group::GetChaptersCSV($post->val('chapterid'), $groupid);
        }
             
        $link = $_COMPANY->getAppURL($_ZONE->val('app_type')).'viewpost?id='.$_COMPANY->encodeId($id);
    } elseif ($section === 2) { // Event
        
        $topicType = gettext("Event");
        if (($event=Event::GetEvent($id)) === null){
            exit();
        }              
        $title = $event->val('eventtitle');
        $groupName = Group::GetGroupName($groupid);        
        $channelid = $event->val('channelid'); 
        if ($_COMPANY->getAppCustomization()['channel']['enabled']) {
            $getChannel = Group::GetChannelName($channelid,$groupid);
            $channelName = $getChannel['channelname'];  
        }  
        if ($_COMPANY->getAppCustomization()['chapter']['enabled']) {
            $getChapter = Group::GetChaptersCSV($event->val('chapterid'), $groupid);
        }

        $link = $_COMPANY->getAppURL($_ZONE->val('app_type')).'eventview?id='.$_COMPANY->encodeId($id);
        if ($event->val('external_facing_event')){
            $linkTwo = $event->getExternalFacingLink();
            $copyLinkTwoBtnText = gettext("Copy External Link");
        }

    } elseif ($section === 3){ // Newsletter
        $topicType = gettext("Newsletter");
        if (( $newsletter = Newsletter::GetNewsletter($id)) === null){
            exit();
        }
        $title = $newsletter->val('newslettername');
        $groupName = Group::GetGroupName($groupid);        
        $channelid = $newsletter->val('channelid'); 
        if ($_COMPANY->getAppCustomization()['channel']['enabled']) {
            $getChannel = Group::GetChannelName($channelid,$groupid);
            $channelName = $getChannel['channelname'];  
        }  
        if ($_COMPANY->getAppCustomization()['chapter']['enabled']) {
            $getChapter = Group::GetChaptersCSV($newsletter->val('chapterid'), $groupid);
        }

        $link = $_COMPANY->getAppURL($_ZONE->val('app_type')).'newsletter?id='.$_COMPANY->encodeId($id);
    } elseif ($section === 4){ // Survey       
        $topicType = gettext("Survey Link");
        if (($survey = Survey2::GetSurvey($id)) === null){
            exit();
        }
        $title = $survey->val('surveyname');
        $groupName = Group::GetGroupName($groupid);        
        $channelid = $survey->val('channelid'); 
        if ($_COMPANY->getAppCustomization()['channel']['enabled']) {
            $getChannel = Group::GetChannelName($channelid,$groupid);
            $channelName = $getChannel['channelname'];  
        }  
        if ($_COMPANY->getAppCustomization()['chapter']['enabled']) {
            $getChapter = Group::GetChaptersCSV($survey->val('chapterid'), $groupid);
        }

        $link = $_COMPANY->getSurveyURL($_ZONE->val('app_type')).'?surveyid='.$_COMPANY->encodeId($id);
    } elseif($section === 5){
        $topicType = gettext("Resource");
        if (($resource = Resource::GetResource($id)) === null){
            exit();
        }
        $title = $resource->val('resource_name');
        $groupName = Group::GetGroupName($groupid);        
        $channelid = $resource->val('channelid'); 
        if ($_COMPANY->getAppCustomization()['channel']['enabled']) {
            $getChannel = Group::GetChannelName($channelid,$groupid);
            $channelName = $getChannel['channelname'];  
        }  
        if ($_COMPANY->getAppCustomization()['chapter']['enabled']) {
            $getChapter = Group::GetChaptersCSV($resource->val('chapterid'), $groupid);
        }
        
        $link = $_COMPANY->getAppURL($_ZONE->val('app_type')).'resource?id='.$_COMPANY->encodeId($id);
    } elseif($section === 6) {
        $topicType = gettext("Discussion");
        if (($discussion = Discussion::GetDiscussion($id)) === null){
            exit();
        }
        $title = $discussion->val('title');
        $groupName = Group::GetGroupName($groupid);        
        $channelid = $discussion->val('channelid'); 
        if ($_COMPANY->getAppCustomization()['channel']['enabled']) {
            $getChannel = Group::GetChannelName($channelid,$groupid);
            $channelName = $getChannel['channelname'];  
        }  
        if ($_COMPANY->getAppCustomization()['chapter']['enabled']) {
            $getChapter = Group::GetChaptersCSV($discussion->val('chapterid'), $groupid);
        }
        
        $link = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'viewdiscussion?id=' . $_COMPANY->encodeId($id);
    } elseif($section === 7){
        $topicType = gettext("Resource Folder");
        if (($resource = Resource::GetResource($id)) === null){
            exit();
        }
        $title = $resource->val('resource_name');
        $groupName = Group::GetGroupName($groupid);        
        $channelid = $resource->val('channelid'); 
        if ($_COMPANY->getAppCustomization()['channel']['enabled']) {
            $getChannel = Group::GetChannelName($channelid,$groupid);
            $channelName = $getChannel['channelname'];  
        }  
        if ($_COMPANY->getAppCustomization()['chapter']['enabled']) {
            $getChapter = Group::GetChaptersCSV($resource->val('chapterid'), $groupid);
        }
        
        $link = $_COMPANY->getAppURL($_ZONE->val('app_type')).'resource_folder?id='.$_COMPANY->encodeId($id);
    } elseif($section === 8){
        $topicType = gettext("Album Media");
        if (($album = Album::GetAlbum($id)) === null){
            exit();
        }
        $title = $album->val('title');
        $groupName = Group::GetGroupName($groupid);        
        $channelid = $album->val('channelid'); 
        if ($_COMPANY->getAppCustomization()['channel']['enabled']) {
            $getChannel = Group::GetChannelName($channelid,$groupid);
            $channelName = $getChannel['channelname'];  
        }  
        if ($_COMPANY->getAppCustomization()['chapter']['enabled']) {
            $getChapter = Group::GetChaptersCSV($album->val('chapterid'), $groupid);
        }
        
        $link = $_COMPANY->getAppURL($_ZONE->val('app_type')).'album?id='.$_COMPANY->encodeId($id);
    }elseif($section === 9){
        $topicType = $_COMPANY->getAppCustomization()['group']['name'];
        if (($group = Group::GetGroup($groupid)) === null){
            exit();
        }        
        $id = $groupid;  
        $title = $group->val('groupname');          
        $link = $group->getShareableUrl();
    
    }  elseif($section === 10 || $section === 11){
       
        if (($event=Event::GetEvent($id)) === null){
            exit();
        } 
        
        if ($section === 10) {
            $topicType = gettext("Event Pre Join Survey");
            $appendLink = "&survey_key=".base64_url_encode(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_PRE_EVENT']);
            
        } elseif($section === 11){
            $topicType = gettext("Event Post Join Survey");
            $appendLink = "&survey_key=".base64_url_encode(Event::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT']);
        } else {
            exit();
        }
        
        $link = $_COMPANY->getAppURL($_ZONE->val('app_type')).'eventview?id='.$_COMPANY->encodeId($id).$appendLink;
    
    } elseif ($section === 12) {
        $attachment = Attachment::GetAttachment($id);
        $link = $attachment->getShareableLink();
    } elseif ($section === 13) {
        if (($team = Team::GetTeam($id)) === null){
            exit();
        }
        $title = $team->val('team_name');
        $link = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'detail?id=' . $_COMPANY->encodeId($groupid).'&hash=getMyTeams/initDiscoverCircles-'.$_COMPANY->encodeId($id);

    }
    else{
        exit();
    }
    include(__DIR__ . '/views/templates/copy_shareable_link.template.php');
}
elseif (isset($_GET['checkPublishStatus'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    
    if (($id = $_COMPANY->decodeId($_GET['id']))<0 
    ) {
        header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
        exit();
    }
    $type = (int)$_GET['type'];
    $response = 0;
    $data = NULL;
    if ($type ==1){
        $data = Post::GetPost($id);
    } elseif($type == 2 ){
        $data = Event::GetEvent($id);
    } elseif($type == 2 ){
        $data = Message::GetMessage($id);
    }
    if($data){
        $response = $data->isActive();
    }
    echo $response;
    exit();
}
elseif (isset($_GET['getGroupCustomTabs'])){
  
    if (
        ($groupid = $_COMPANY->decodeId($_GET['getGroupCustomTabs']))<1 ||
        ($group = Group::GetGroup($groupid)) === null  ||
        ($tabid = $_COMPANY->decodeId($_GET['tabid'])) < 0 ||
        ($customTabDetail = $group->getGroupCustomTabDetail($tabid)) === null
    ) {
        header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
        exit();
    }

    // Authorization Check
    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
	include(__DIR__ . '/views/templates/group_custom_tab_content.template.php');
}

else {
    /** @noinspection ForgottenDebugOutputInspection */
    Logger::Log('Nothing to do ...');
    header('HTTP/1.1 501 Not Implemented (Bad Request)');
    exit;
}

