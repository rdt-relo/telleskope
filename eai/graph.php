<?php
$_COMPANY = null;
$_EAI_MODULE = 'GRAPH'; // Module is needed by head.php
require_once __DIR__.'/head.php'; // $_COMPANY variable will be set after authenticating

function GraphAPIExit(bool $status, string $message, $data=null) {
    header('Content-Type: application/json; charset=utf-8');
    die (json_encode(['status'=>$status,'message'=>$message,'data'=>$data]));
}
/**
 * Note: In this file we use die() instead of exit() as die closes the connection immediately on return.
 */

if (isset($_GET['listZonesOfAppType']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (empty($zonetype = $_GET['listZonesOfAppType']) ||
        !in_array($zonetype, array('affinities','officeraven','talentpeak', 'peoplehero'))
    ){
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Missing or incorrect zone type passed in listZonesOfAppType');
    }

    EaiAuth::CheckPermission(EaiGraphPermission::GetZones);

    $response = array();
    $zones = array();
    $rows = $_COMPANY->getZones($zonetype);

    foreach($rows as $row){
        if ($row['isactive'] == 1 && EaiAuth::CanAccessZone($row['zoneid'])) { // Add to the list only if it is active
            $zones[] = array(
                'zone_id' => $_COMPANY->encodeId($row['zoneid']),
                'zone_name' => $row['zonename'],
                'zone_app_type' => $row['app_type'],
            );
        }
    }

    if  (!empty($zones)){
        GraphAPIExit(true,"All {$zonetype} zones fetched", $zones);
    } else {
        GraphAPIExit(false,"No {$zonetype} zones available");
    }
}
elseif (isset($_GET['listGroupsInZone']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (empty($_GET['listGroupsInZone']) ||
        ($zoneid = $_COMPANY->decodeId($_GET['listGroupsInZone'])) < 1 ||
        !($EAI_ZONE = $_COMPANY->getZone($zoneid))
    ){
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Missing or incorrect zone_id passed in listGroupsInZone');
    }

    EaiAuth::CheckPermission(EaiGraphPermission::GetGroups);

    $_ZONE = $EAI_ZONE; // temporarily set $_ZONE to make the api's work, reset after use

    EaiAuth::CheckZone();

    $groups = array();
    $allGroups = Group::GetAllGroupsByCompanyid($_COMPANY->id(), $EAI_ZONE->id(), true);
    foreach ($allGroups as $group) {
        $groupMembersCount = (int)$group->getGroupMembersCount();

        $groups[] = array(
            'group_id' => $_COMPANY->encodeId($group->val('groupid')),
            'group_name' => $group->val('groupname'),
            'group_name_short' => $group->val('groupname_short'),
            'group_category' => $group->val('group_category'),
            'group_about_html' => $group->val('aboutgroup'),
            'group_cover_photo' => $group->val('coverphoto'),
            'group_icon' => $group->val('groupicon'),
            'group_color_primary' => $group->val('overlaycolor'),
            'group_color_secondary' => $group->val('overlaycolor2'),
            'group_url' => $group->getShareableUrl(),
            'group_email' => $group->val('replyto_email'),
            'group_member_count' => (int)$groupMembersCount,
        );
    }

    $_ZONE = null;

    if  (!empty($groups)){
        GraphAPIExit(true,'All groups fetched', $groups);
    } else {
        GraphAPIExit(false,'No groups found');
    }
}

elseif (isset($_GET['listChaptersInGroup']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (empty($_GET['listChaptersInGroup']) ||
        ($groupid = $_COMPANY->decodeId($_GET['listChaptersInGroup'])) < 1
    ){
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Missing or incorrect group_id passed in listChaptersInGroup');
    }

    EaiAuth::CheckPermission(EaiGraphPermission::GetGroupChapters);

    $group = Group::GetGroup($groupid);
    if (!$group) {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Missing or incorrect group_id passed in listChaptersInGroup');
    }

    // Get zone_id from Group
    $EAI_ZONE = $_COMPANY->getZone($group->val('zoneid'));
    if (!$EAI_ZONE) {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Missing or incorrect group_id passed in listChaptersInGroup');
    }
    $_ZONE = $EAI_ZONE; // temporarily set $_ZONE to make the api's work, reset after use

    EaiAuth::CheckZone();

    $activeChapters = array();
    $allChapters = $group->getAllChapters();

    foreach ($allChapters as $chapter) {
        if ($chapter['isactive'] != 1) {
            continue;
        }

        $chapterMembersCount = $group->getChapterMembersCount($chapter['chapterid']);
        $activeChapters[] = array(
            'chapter_id' => $_COMPANY->encodeId($chapter['chapterid']),
            'chapter_name' => $chapter['chaptername'],
            'chapter_about_html' => $chapter['about'],
            'chapter_color_primary' => $chapter['colour'],
            'chapter_color_secondary' => $chapter['colour'],
            'chapter_member_count' => (int)$chapterMembersCount
            );
    }

    $_ZONE = null; // Reset

    if  (!empty($activeChapters)){
        GraphAPIExit(true,'All chapters fetched', $activeChapters);
    } else {
        GraphAPIExit(false,'No chapters found');
    }
}

elseif (isset($_GET['listChannelsInGroup']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (empty($_GET['listChannelsInGroup']) ||
        ($groupid = $_COMPANY->decodeId($_GET['listChannelsInGroup'])) < 1
    ){
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Missing or incorrect group_id passed in listChannelsInGroup');
    }

    EaiAuth::CheckPermission(EaiGraphPermission::GetGroupChannels);

    $group = Group::GetGroup($groupid);
    if (!$group) {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Missing or incorrect group_id passed in listChannelsInGroup');
    }

    // Get zone_id from Group
    $EAI_ZONE = $_COMPANY->getZone($group->val('zoneid'));
    if (!$EAI_ZONE) {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Missing or incorrect group_id passed in listChannelsInGroup');
    }
    $_ZONE = $EAI_ZONE; // temporarily set $_ZONE to make the api's work, reset after use

    EaiAuth::CheckZone();

    $activeChannels = array();
    $allChannels = Group::GetChannelList($group->val('groupid'));

    foreach ($allChannels as $channel) {
        if ($channel['isactive'] != 1) {
            continue;
        }

        $channelMembersCount = $group->getChannelMembersCount($channel['channelid']);
        $activeChannels[] = array(
            'channel_id' => $_COMPANY->encodeId($channel['channelid']),
            'channel_name' => $channel['channelname'],
            'channel_about_html' => $channel['about'],
            'channel_color_primary' => $channel['colour'],
            'channel_color_secondary' => $channel['colour'],
            'channel_member_count' => (int)$channelMembersCount
        );
    }

    $_ZONE = null; // Reset

    if  (!empty($activeChannels)){
        GraphAPIExit(true,'All channels fetched', $activeChannels);
    } else {
        GraphAPIExit(false,'No channels found');
    }
}

elseif (isset($_GET['getMembersInScope']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_GET['group_id']) ||
        ($groupid = $_COMPANY->decodeId($_GET['group_id'])) < 1)
    {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Missing or incorrect group_id passed in getMembersInScope');
    }

    EaiAuth::CheckPermission(EaiGraphPermission::GetMembers);

    $chapterid = 0;
    if (!empty($_GET['chapter_id']) && ($chapterid = $_COMPANY->decodeId($_GET['chapter_id'])) < 0)
    {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Incorrect chapter_id passed in getMembersInScope');
    }

    $channelid = 0;
    if (!empty($_GET['channel_id']) && ($channelid = $_COMPANY->decodeId($_GET['channel_id'])) < 0)
    {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Incorrect channel_id passed in getMembersInScope');
    }

    $group = Group::GetGroup($groupid);
    if (!$group) {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Incorrect group_id passed in getMembersInScope');
    }

    // Get zone_id from Group
    $EAI_ZONE = $_COMPANY->getZone($group->val('zoneid'));
    if (!$EAI_ZONE) {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Incorrect group_id passed in getMembersInScope');
    }
    $_ZONE = $EAI_ZONE; // temporarily set $_ZONE to make the api's work, reset after use

    EaiAuth::CheckZone();

    if ($chapterid) {
        $chapter_row = $group->getChapter($chapterid);
        if (empty($chapter_row['chapterid']) || $chapter_row['isactive'] != 1) {
            header(HTTP_BAD_REQUEST);
            GraphAPIExit(false,'Incorrect chapter_id passed in getMembersInScope');
        }
    }

    if ($channelid) {
        $channel_row = $group->getChannel($channelid);
        if (empty($channel_row['channelid']) || $channel_row['isactive'] != 1) {
            header(HTTP_BAD_REQUEST);
            GraphAPIExit(false,'Incorrect channel_id passed in getMembersInScope');
        }
    }

    $members = $group->getAllMembers($chapterid, $channelid);

    $membersAry = array();
    foreach ($members as $member) {
        $membersAry[] = array(
            'first_name' => $member['firstname'],
            'last_name' => $member['lastname'],
            'email' => $member['email'],
            'member_since' => $member['groupjoindate'],
            'is_active' => $member['isactive'] == 1,
        );
    }

    $scope = $group->getFromEmailLabel($chapterid, $channelid);

    $_ZONE = null; // Reset

    if  (!empty($membersAry)){
        GraphAPIExit(true,"Members for {$scope}", $membersAry);
    } else {
        GraphAPIExit(false,"No members found in {$scope}");
    }
}

elseif (isset($_GET['getLeadsInScope']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_GET['group_id']) ||
        ($groupid = $_COMPANY->decodeId($_GET['group_id'])) < 1)
    {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Missing or incorrect group_id passed in getLeadsInScope');
    }

    EaiAuth::CheckPermission(EaiGraphPermission::GetLeads);

    $chapterid = 0;
    if (!empty($_GET['chapter_id']) && ($chapterid = $_COMPANY->decodeId($_GET['chapter_id'])) < 0)
    {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Incorrect chapter_id passed in getLeadsInScope');
    }

    $channelid = 0;
    if (!empty($_GET['channel_id']) && ($channelid = $_COMPANY->decodeId($_GET['channel_id'])) < 0)
    {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Incorrect channel_id passed in getLeadsInScope');
    }

    $group = Group::GetGroup($groupid);
    if (!$group) {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Incorrect group_id passed in getLeadsInScope');
    }

    // Get zone_id from Group
    $EAI_ZONE = $_COMPANY->getZone($group->val('zoneid'));
    if (!$EAI_ZONE) {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Incorrect group_id passed in getLeadsInScope');
    }
    $_ZONE = $EAI_ZONE; // temporarily set $_ZONE to make the api's work, reset after use

    EaiAuth::CheckZone();

    if ($chapterid) {
        $chapter_row = $group->getChapter($chapterid);
        if (empty($chapter_row['chapterid']) || $chapter_row['isactive'] != 1) {
            header(HTTP_BAD_REQUEST);
            GraphAPIExit(false,'Incorrect chapter_id passed in getLeadsInScope');
        }
    }

    if ($channelid) {
        $channel_row = $group->getChannel($channelid);
        if (empty($channel_row['channelid']) || $channel_row['isactive'] != 1) {
            header(HTTP_BAD_REQUEST);
            GraphAPIExit(false,'Incorrect channel_id passed in getLeadsInScope');
        }
    }
    if ($chapterid > 0){
        $leads = $group->getChapterLeads($chapterid);
    }elseif($channelid > 0){
        $leads = $group->getChannelLeads($channelid);
    }else{
        $leads = $group->getGroupLeads();
    }

    $leadsAry = array();
    foreach ($leads as $lead) {
        $leadsAry[] = array(
            'first_name' => $lead['firstname'],
            'last_name' => $lead['lastname'],
            'email' => $lead['email'],
            'is_active' => $lead['isactive'] == 1,
            'lead_since' => $lead['assigneddate'],
            'can_manage_budget' => $lead['allow_manage_budget'] == 1,
            'can_create_content' => $lead['allow_create_content'] == 1,
            'can_publish_content' => $lead['allow_publish_content'] == 1,
            'can_manage' => $lead['allow_manage'] == 1,
            'lead_type' => $lead['type']
        );
    }

    $scope = $group->getFromEmailLabel($chapterid, $channelid);

    $_ZONE = null; // Reset

    if  (!empty($leadsAry)){
        GraphAPIExit(true,"Leaders for {$scope}", $leadsAry);
    } else {
        GraphAPIExit(false,"No Leaders found in {$scope}");
    }
}
elseif (isset($_GET['getEventsInScope']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    
    // required params
    if (empty($_GET['zone_id']) ||
    ($zoneid = $_COMPANY->decodeId($_GET['zone_id'])) < 1 ||
    !($EAI_ZONE = $_COMPANY->getZone($zoneid))
    ){
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Missing or incorrect zone_id passed in getEventsInScope');
    }

    EaiAuth::CheckPermission(EaiGraphPermission::GetEvents);

    // optional params
    $groupid = null;
    if (isset($_GET['group_id'])) {
        $groupid = empty($_GET['group_id']) ? 0 : $_COMPANY->decodeId($_GET['group_id']);
        if ($groupid < 0) {
            header(HTTP_BAD_REQUEST);
            GraphAPIExit(false, 'Incorrect group_id passed in getEventsInScope');
        }
    }

    $chapterid = null;
    if (isset($_GET['chapter_id'])) {
        $chapterid = empty($_GET['chapter_id']) ? 0 : $_COMPANY->decodeId($_GET['chapter_id']);
        if ($chapterid < 0) {
            header(HTTP_BAD_REQUEST);
            GraphAPIExit(false, 'Incorrect chapter_id passed in getEventsInScope');
        }
    }

    $channelid = null;
    if (isset($_GET['channel_id'])) {
        $channelid = empty($_GET['channel_id']) ? 0 : $_COMPANY->decodeId($_GET['channel_id']);
        if ($channelid < 0) {
            header(HTTP_BAD_REQUEST);
            GraphAPIExit(false, 'Incorrect channel_id passed in getEventsInScope');
        }
    }

     // Set date range
    $startDate = gmdate("Y-m-d");
     if (!empty($_GET['event_start_date'])) {
        $startDate = gmdate('Y-m-d', strtotime($_GET['event_start_date']));
     }

    $endDate = gmdate("Y-m-d", strtotime('+1 month'));
     if (!empty($_GET['event_end_date'])) {
        $endDate = gmdate('Y-m-d', strtotime($_GET['event_end_date']));
     }

    {
        $_ZONE = $EAI_ZONE; // temporarily set $_ZONE to make the api's work, reset after use

        EaiAuth::CheckZone();

        $event_attendance_type = [
            '1' => 'In Person',
            '2' => 'Online',
            '3' => 'In Person and Online',
            '4' => 'Other'
        ];

        // Create a map of Event Types ... will be used to convert id to type.
        $event_type_mapping = [];
        $event_types_rows = Event::GetEventTypesByZones([$_ZONE->id()]);
        foreach ($event_types_rows as $event_type_row) {
            $event_type_mapping[intval($event_type_row['typeid'])] = $event_type_row;
        }

        // get events
        $events = Event::GetAllEventsInZoneAndScope($groupid,$chapterid, $channelid, $startDate, $endDate);
        // build response
        $eventsArray = array();
        foreach ($events as $event) {
            $event_url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'eventview?id=' . $_COMPANY->encodeId($event->id());

            $event_type_name = '';
            if ($event->val('eventtype')) {
                $event_type_name = $event_type_mapping[$event->val('eventtype')]['type'] ?? '';
            }

            $event_series = null;
            if ($event->isSeriesEventSub()) {
                $event_series = Event::GetEvent($event->val('event_series_id'));
            }

            $eventsArray[] = array(
                'zone_id' => $_COMPANY->encodeId($event->val('zoneid')),
                'group_id' => $event->val('groupid') ? $_COMPANY->encodeIdsInCSV($event->val('groupid')) : NULL,
                'chapter_ids' => $event->val('chapterid') ? $_COMPANY->encodeIdsInCSV($event->val('chapterid')) : NULL,
                'channel_ids' => $event->val('channelid') ? $_COMPANY->encodeIdsInCSV($event->val('channelid')) : NULL,
                'collaborating_groupids' => $event->val('collaborating_groupids') ? $_COMPANY->encodeIdsInCSV($event->val('collaborating_groupids')) : NULL,
                'event_id' => $_COMPANY->encodeId($event->id()),
                'hostedby' => html_entity_decode($event->val('hostedby')),
                'event_type' => html_entity_decode($event_type_name),
                'event_attendence_type' => $event_attendance_type[$event->val('event_attendence_type')],
                'event_series_id' => $event->isSeriesEvent() ? $_COMPANY->encodeId($event->val('event_series_id')) : NULL,
                'event_series_title' => html_entity_decode($event_series?->val('eventtitle')),
                'pin_to_top' => (int)$event->val('pin_to_top'),
                'event_title' => html_entity_decode($event->val('eventtitle')),
                'event_description' => $event->val('event_description'),
                'event_venue' => html_entity_decode($event->val('eventvanue')),
                'event_venue_address' => html_entity_decode($event->val('vanueaddress')),
                'version' => (int)$event->val('version'),
                'web_conference_link' => html_entity_decode($event->val('web_conference_link')),
                'web_conference_detail' => html_entity_decode($event->val('web_conference_detail')),
                'max_inperson' => (int)$event->val('max_inperson'),
                'max_online' => (int)$event->val('max_online'),
                'max_inperson_waitlist' => (int)$event->val('max_inperson_waitlist'),
                'max_online_waitlist' => (int)$event->val('max_online_waitlist'),
                'created_at' => $event->val('addedon'),
                'publish_at' => $event->val('publishdate'),
                'modified_at' => $event->val('modifiedon'),
                'canceled_at' => $event->val('canceledon'),
                'cancel_reason' => $event->val('cancel_reason'),
                'event_start_datetime' => $event->val('start'),
                'event_end_datetime' => $event->val('end'),
                'event_timezone' => $event->val('timezone'),
                'event_url' => $event_url,
                'event_is_private' => (int)$event->val('isprivate')
            );
        }
    }

    $groupname = 'All groups';
    if ($groupid) {
        $group = Group::GetGroup($groupid);
        $groupname = $group->val('groupname');    
    }

   if  (!empty($eventsArray)){
        GraphAPIExit(true,"Events of {$groupname}", $eventsArray);
    } else {
        GraphAPIExit(false,"No Events found in {$groupname}");
    }
}
elseif (isset($_GET['getAuditLogs']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // required params
    if (empty($_GET['zone_id']) ||
    ($zoneid = $_COMPANY->decodeId($_GET['zone_id'])) < 1 ||
    !($EAI_ZONE = $_COMPANY->getZone($zoneid))
    ){
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false,'Missing or incorrect zone_id passed in getAuditLogs');
    }

    if (empty($_GET['start_date']) || empty($_GET['end_date'])) {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false, 'Missing or incorrect start_date or end_date passed in getAuditLogs');
    }
    // Set date range
    $startDate = new DateTime($_GET['start_date'], new DateTimeZone('UTC'));
    $endDate = new DateTime($_GET['end_date'], new DateTimeZone('UTC'));
    $startDateValue = $startDate->format('Y-m-d');
    $endDateValue = $endDate->format('Y-m-d');

    $startDatePlusOneMonth = $startDate; // First make a copy
    $startDatePlusOneMonth->add(new DateInterval('P1M')); // and then add a month.


    // Check if the date range is more than one month apart
    if ($endDate > $startDatePlusOneMonth) {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false, 'Invalid date range. Date range cannot be more than one month apart.');
    }

    EaiAuth::CheckPermission(EaiGraphPermission::GetAuditLogs);
 
    // optional params
    $groupid = 0;
    if (isset($_GET['group_id'])) {
        $groupid = empty($_GET['group_id']) ? 0 : $_COMPANY->decodeId($_GET['group_id']);
        if ($groupid < 0) {
            header(HTTP_BAD_REQUEST);
            GraphAPIExit(false, 'Incorrect group_id passed in getAuditLogs');
        }
    }

    $chapterid = 0;
    if (isset($_GET['chapter_id'])) {
        $chapterid = empty($_GET['chapter_id']) ? 0 : $_COMPANY->decodeId($_GET['chapter_id']);
    if ($chapterid < 0) {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false, 'Incorrect chapter_id passed in getAuditLogs');
    }
    }

    $channelid = 0;
    if (isset($_GET['channel_id'])) {
        $channelid = empty($_GET['channel_id']) ? 0 : $_COMPANY->decodeId($_GET['channel_id']);
    if ($channelid < 0) {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false, 'Incorrect channel_id passed in getAuditLogs');
    }
    }
    $_ZONE = $EAI_ZONE; // temporarily set $_ZONE to make the api's work, reset after use
 
    EaiAuth::CheckZone(); 
    // get Audit Logs
    $auditLogs = GroupUserLogs::GetAuditLogs($startDateValue, $endDateValue, $groupid, $chapterid, $channelid);
    // build response
    $auditLogsArray = array();
    foreach ($auditLogs as $auditLog) {

        $auditLog['chaptername'] = '';
        if ($auditLog['sub_scope'] == GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER']) {
            if ($auditLog['sub_scopeid'] > 0) {
                $chapterData = Group::getChapterName($auditLog['sub_scopeid'],$groupid);
                $auditLog['chaptername'] = $chapterData['chaptername'];
            }
        }

        $auditLog['channelname'] = '';
        if ($auditLog['sub_scope'] == GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHANNEL']) {
        if ($auditLog['sub_scopeid'] > 0) {
            $channelData = Group::getChannelName($auditLog['sub_scopeid'],$groupid);
            $auditLog['channelname'] = $channelData['channelid'] > 0 ? $channelData['channelname'] : '';
        }

    }
    $auditLogsArray[] = array(
        'email' => $auditLog['email'],
        'first_name' => $auditLog['firstname'],
        'last_name' => $auditLog['lastname'],
        'external_id' => User::ExtractExternalId($auditLog['externalid']),
        'group_name' => $auditLog['groupname'] ?? '',
        'chapter_name' => $auditLog['chaptername'] ?? '',
        'channel_name'=> $auditLog['channelname'] ?? '',
        //'rolename' => $role,
        'action' => $auditLog['action'],
        'action_reason' => $auditLog['action_reason'],
        'log_createdon' => $auditLog['createdon']
        );
    }

    $groupname = '-';
    if ($groupid) {
        $group = Group::GetGroup($groupid);
        $groupname = $group->val('groupname');    
    }

    if  (!empty($auditLogsArray)){
         GraphAPIExit(true,"Audit Logs of {$groupname}", $auditLogsArray);
    } else {
         GraphAPIExit(false,"No Logs found in {$groupname}");
    }
}

elseif (isset($_GET['getUser']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (empty($_GET['email']) && empty($_GET['external_id'])) {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false, 'Missing identifier, please provide email or external_id');
    }

    EaiAuth::CheckPermission(EaiGraphPermission::GetUser);

    $user = null;
    $external_id = $_GET['external_id'] ?? '';
    $email = $_GET['email'] ?? '';
    if ($external_id) {
        $user = User::GetUserByExternalId($external_id);
        if ($email && strcasecmp($user->val('email'), $email) !== 0) {
            $user = null;
        }
    } else {
        $user = User::GetUserByEmail($email);
    }

    if  (!empty($user) && $user->cid() == $_COMPANY->id()){
        $userArray = [
            'external_id' => $user->getExternalId(),
            'email' => $user->val('email'),
            'first_name' => $user->val('firstname'),
            'last_name' => $user->val('lastname'),
            'created_at' => $user->val('createdon'),
            'modified_at' => $user->val('modified'),
            'user_status' => User::USER_STATUS_LABEL[$user->val('isactive')]
        ];
        GraphAPIExit(true,"User data for external_id={$external_id}, email={$email}", $userArray);
    } else {
        GraphAPIExit(false,"No data found");
    }
}

elseif (isset($_GET['getAllUsers']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    EaiAuth::CheckPermission(EaiGraphPermission::GetAllUsers);

    $users = [];
    $limit = $_GET['limit'] ?? 100;
    $limit = min(1000, $limit);
    $page = $_GET['page'] ?? 1;
    if ($page < 1) {
        GraphAPIExit(false,"Invalid value for page attribute");
    }

    $users = User::GetAllUsers($page, $limit);

    if (!empty($users)){
        $usersArray = [];
        foreach ($users as $user) {
            if ($user['companyid'] != $_COMPANY->id()) {
                GraphAPIExit(false,"No data found");
            }
            $user_email = '';
            if ($_COMPANY->isValidEmail($user['email'])) {
                $user_email = $user['email'];
            }
            $usersArray[] = [
                'external_id' => ($user['externalid'] === null) ? null : User::ExtractExternalId($user['externalid']),
                'email' => $user_email,
                'first_name' => $user['firstname'],
                'last_name' => $user['lastname'],
                'created_at' => $user['createdon'],
                'modified_at' => $user['modified'],
                'user_status' => User::USER_STATUS_LABEL[$user['isactive']]
            ];
        }
        sleep(1); //  introduce a limit bit of delay
        GraphAPIExit(true,"User data for page={$page}, limit={$limit}", $usersArray);
    } else {
        GraphAPIExit(false,"No data found");
    }
}

elseif (isset($_GET['createUser']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    EaiAuth::CheckPermission(EaiGraphPermission::CreateUser);

    if (empty($_GET['external_id']) || empty($_GET['email'])) {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false, 'Missing identifier, please provide external_id and email');
    }

    $external_id = $_GET['external_id'];
    $email = $_GET['email'];
    $firstname = Sanitizer::SanitizePersonName($_GET['first_name'] ?? '');
    $lastname = Sanitizer::SanitizePersonName($_GET['last_name'] ?? '');

    $user = User::CreateNewUser($firstname, $lastname, $email, '', User::USER_VERIFICATION_STATUS['VERIFIED']);
    if($user){
        $update_external_id = $user->updateExternalId($external_id);        
    }


    if (!empty($user)){
        $userArray = [
                'external_id' => $user->getExternalId(),
                'email' => $user->val('email'),
                'first_name' => $user->val('firstname'),
                'last_name' => $user->val('lastname'),
                'created_at' => $user->val('createdon'),
                'modified_at' => $user->val('modified'),
                'user_status' => User::USER_STATUS_LABEL[$user->val('isactive')]
        ];
        GraphAPIExit(true,"User created", $userArray);
    } else {
        GraphAPIExit(false,"Error creating user");
    }
}

elseif (isset($_GET['updateUser']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    EaiAuth::CheckPermission(EaiGraphPermission::UpdateUser);

    if (empty($_GET['external_id'])) {
        header(HTTP_BAD_REQUEST);
        GraphAPIExit(false, 'Missing identifier, please provide external_id');
    }

    $user_status = null;
    if (!empty($_GET['user_status'])) {
        if (!in_array(strtoupper($_GET['user_status']), ['ACTIVE', 'BLOCK', 'DELETE'])) {
            header(HTTP_BAD_REQUEST);
            GraphAPIExit(false, 'Invalid value for user_status, valid values are ACTIVE, BLOCK, DELETE');
        } else {
            $user_status = strtoupper($_GET['user_status']);
        }
    }

    $external_id = $_GET['external_id'];
    $email = $_GET['email'] ?? null;
    $firstname = $_GET['first_name'] ?? null;
    $lastname = $_GET['last_name'] ?? null;

    $user = User::GetUserByExternalId($external_id);
    $retVal = true;
    $error_message = '';

    if (!$user) {
        GraphAPIExit(false,"Error updating user: " . 'User not found');
    }

    if ($email && strcasecmp($email, $user->val('email'))) {
        if (!$_COMPANY->isValidEmail($email)) {
            GraphAPIExit(false,"Error updating user: " . 'Invalid email address');
        }
        $retVal = $user->updateEmail($email);
        if (!$retVal) {
            GraphAPIExit(false,"Error updating user: " . 'Unable to update email');
        }
    }

    if ($firstname !== null || $lastname !== null) {
        $retVal = $user->updateProfile2(
            $user->val('email'),
            $firstname ? Sanitizer::SanitizePersonName($firstname) : $firstname,
            $lastname ? Sanitizer::SanitizePersonName($lastname) : $lastname,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            false,
            null,
            null,
            null
        );
        if (!$retVal) {
            GraphAPIExit(false, "Error updating user: " . 'Unable to update name');
        }
    }

    if ($user_status) {
        switch ($user_status) {
            case 'ACTIVE' :
                $retVal = $user->isBlocked() ? $user->unblock() : $user->reset();
                break;
            case 'BLOCK' :
                $retVal = $user->block();
                break;
            case 'DELETE' :
                $retVal = $user->isBlocked() ? $user->unblock() && $user->purge(): $user->purge();
                break;
        }
        if (!$retVal) {
            GraphAPIExit(false, "Error updating user status");
        }
    }

    if ($user){
        $user = User::GetUser($user->id());
        $userArray = [
            'external_id' => $user->getExternalId(),
                'email' => $user->val('email'),
                'first_name' => $user->val('firstname'),
                'last_name' => $user->val('lastname'),
                'created_at' => $user->val('createdon'),
                'modified_at' => $user->val('modified'),
                'user_status' => User::USER_STATUS_LABEL[$user->val('isactive')]
        ];
        GraphAPIExit(true,"User updated", $userArray);
    }
}

Logger::Log("EAI - Graph API error, unsupported HTTP operation - {$_SERVER['REQUEST_METHOD']}");
header(HTTP_NOT_FOUND);
die ('Bad Request (004)');