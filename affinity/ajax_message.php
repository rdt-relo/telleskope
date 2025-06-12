<?php
##
## All Message functions require Manage permission on the scope.
##
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

if (isset($_GET['groupMessageList'])){
    $encGroupId = $_GET['groupid'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<0 ||
        ($group = Group::GetGroup($groupid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid); // Re-encode it

    // Authorization Check
    if (!($_USER->canManageGroupSomething($groupid))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    // Authorization check 2 - if messaging is restricted to admins, then do a admin check
    if($_COMPANY->getAppCustomization()['messaging']['restrict_to_admin_only'] && !$_USER->isAdmin()){
        header(HTTP_FORBIDDEN);
        exit();
    }

    $headtitle	= gettext("Manage Messages");

    $condtion = " AND is_admin = 1";
    if($groupid){ // The context group id
        $condtion = " AND groupids = '{$groupid}' AND is_admin = 0";
    }

    $rows = $db->ro_get("SELECT *,
    (SELECT  GROUP_CONCAT(groupname SEPARATOR ', ') as groupname FROM `groups` WHERE groups.companyid = {$_COMPANY->id()} AND FIND_IN_SET(`groupid`,`groupids`)) AS groupname,
    (SELECT GROUP_CONCAT(distinct chaptername separator ', ') as chaptername FROM `chapters` WHERE chapters.companyid = {$_COMPANY->id()} AND FIND_IN_SET(`chapterid`,chapterids)) AS chaptername,
    (SELECT GROUP_CONCAT(distinct channelname separator ', ') as channelname FROM `group_channels` WHERE group_channels.companyid = {$_COMPANY->id()} AND FIND_IN_SET(`channelid`, channelids)) AS channelname,
    (SELECT  CONCAT(firstname,' ',lastname) FROM users WHERE users.companyid = {$_COMPANY->id()} AND userid=messages.userid) as sent_by
    FROM `messages`
    WHERE `companyid`={$_COMPANY->id()} AND (`zoneid`={$_ZONE->id()} AND `isactive`!=100 $condtion)");
    
    include(__DIR__ . "/views/templates/group_messages.template.php");
}

elseif (isset($_GET['groupMessageDelete']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($messageid = $_COMPANY->decodeId($_POST['messageid']))<1 ||
        ($message = Message::GetMessage($messageid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    $is_admin_message = $message->isAdminMessage();
    if (($is_admin_message && !$_USER->canManageContentInScopeCSV(0,0,0)) ||
        (!$is_admin_message && !$_USER->canManageContentInScopeCSV($message->val('groupids'),$message->val('chapterids'),$message->val('channelids')))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    echo $message->deleteMessage();
}

elseif (isset($_GET['groupMessageForm'])){
    $encGroupId = $_GET['groupid'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($messageid = $_COMPANY->decodeId($_GET['messageid']))<0 ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid); // Re-encode it

    // Authorization Check
    if (!($_USER->canManageGroupSomething($groupid))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    // Authorization check 2 - if messaging is restricted to admins, then do a admin check
    if($_COMPANY->getAppCustomization()['messaging']['restrict_to_admin_only'] && !$_USER->isAdmin()){
        header(HTTP_FORBIDDEN);
        exit();
    }

    $fontColors = $groupid > 0 ? json_encode(array($group->val('overlaycolor'),$group->val('overlaycolor2'))) : json_encode(array());
    if ($groupid){
        $chapters = Group::GetChapterList($groupid);
        $channels= Group::GetChannelList($groupid);
    } else { // Admin content
        $groups = Group::GetAllGroupsByZones([$_ZONE->id()]);
        $groupIds = [];
        foreach ($groups as $grp) {
            $groupIds[] = (int)$grp['groupid'];
        }
        $groupIds = implode(',', $groupIds);

        $filteredChapters = array();
        $filteredChannels = array();
        if ($groupIds){
            $filteredChapters = Group::GetChapterByGroupsAndRegion($groupIds,0);
            $filteredChannels = Group::GetChannelsByGroupIdsCsv($groupIds);
        }
    }

    $groupRegions = [];
    $message = null;
    if ($messageid){
        $message = Message::GetMessage($messageid);
    }

    // When composing a new message:
    // If the user is not a group admin, then pre-select all chapters and channels that the user can manage
    // This is to avoid the default group level option from showing up.
    $preSelectAllChaptersAndChannels = !$message && !$_USER->canManageContentInScopeCSV($groupid,0,0);

    $lists = array();
    if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) {
        if ($groupid>0){
            $lists = DynamicList::GetAllLists('group',true);
        } else {
            $lists = DynamicList::GetAllLists('zone',true);
        }
    }

    include(__DIR__ . "/views/templates/group_new_or_update_message.template.php");
}

elseif (isset($_GET['getFilteredChapterList']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        !isset($_GET['groupids'])
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

   
    $groupids = $_COMPANY->decodeIdsInCSV($_GET['groupids']);

    $regionid = 0;
    if (isset($_GET['regionid']) && $_GET['regionid'] !== 'all'){
        $regionid = $_COMPANY->decodeId($_GET['regionid']);
    }
    $filteredChapters = Group::GetChapterByGroupsAndRegion($groupids,$regionid);
    foreach($filteredChapters as $key => $row){
        $chapterids = $_COMPANY->encodeIdsInCSV(implode(',',array_column($row,'chapterid')));
        ?>
        <option data-chapter="1" value="<?= $chapterids; ?>"><?= $key; ?></option>
        <?php
    }
}

elseif (isset($_GET['getFilteredChannelList']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        !isset($_GET['groupids'])
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $groupids = $_COMPANY->decodeIdsInCSV($_GET['groupids']);
   
    $filteredChannels = Group::GetChannelsByGroupIdsCsv($groupids);
    foreach($filteredChannels as $key => $row){
        $channelids = $_COMPANY->encodeIdsInCSV(implode(',',array_column($row,'channelid')));
        echo "<option data-channel='1' value=\"{$channelids}\">{$key}</option>";
    }
}


elseif (isset($_GET['groupMessageSave']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    // Authorization Check
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization check 2 - if messaging is restricted to admins, then do a admin check
    if($_COMPANY->getAppCustomization()['messaging']['restrict_to_admin_only'] && !$_USER->isAdmin()){
        header(HTTP_FORBIDDEN);
        exit();
    }

    // Authorization Check
    $messageid = 0;
    $subject = '';
    $message = '';
    $groupids = '';
    $chapterids ='';
    $channelids ='';
    $team_member_roles = '';
    $members = array();
    $recipients_base = 3;
    $emails = array();
    $is_admin_message = $groupid ? 0 : 1;

    if (($is_admin_message && !$_USER->canManageContentInScopeCSV(0,0,0)) ||
        (!$is_admin_message && !$_USER->canManageGroupSomething($groupid))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $listids = 0;
    if (isset($_POST['recipients_base']) && $_POST['recipients_base'] == '4'){
        if (empty($_POST['list_scope'])) {
            AjaxResponse::SuccessAndExit_STRING(0, '',  gettext("Please select dynamic list scope!"), gettext('Error'));
        }
        $listids = implode(',',$_COMPANY->decodeIdsInArray($_POST['list_scope']));
    }

    $content_replyto_email = ViewHelper::ValidateAndExtractReplytoEmailFromPostAttribute();

    if (Str::IsEmptyHTML($_POST['message'])) {
        $_POST['message'] = '';
    }

    // Since we calculate from on the view side and we do not want anyone to change the value we presented
    // we will compare the hash of from to ensure from was not changed.
    $from = ($_POST['from'] && $_POST['from_hash'] && $_COMPANY->validateGenericHash($_POST['from'],$_POST['from_hash'])) ? $_POST['from'] : '';

    //Data Validation
    $check = $db->checkRequired(array('Subject'=>@$_POST['subject'],'Email From'=>$from,'Message'=>@$_POST['message']));
    if($check){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$check), gettext('Error'));
    }

    if( (isset($_POST['recipients_base']) && ($recipients_base = $_POST['recipients_base']) < 1 )) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter all required fields!'), gettext('Error'));
    }

    // If the message is being sent to group leads/members then we need the groupids and targets members or leads.
    if ( $recipients_base == 3  && ( empty($_POST['groupids']) || empty($_POST['members_type']) )) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter all required fields!'), gettext('Error'));
    }

    $messageid =  (!empty($_POST['messageid'])) ? $_COMPANY->decodeId($_POST['messageid']) : '0';
    $members_type =  (!empty($_POST['members_type'])) ? $_POST['members_type'] : array(0);
    $members_type = array_map('intval',$members_type);
    $sent_to =  implode(',',$members_type);
    $subject 	= $_POST['subject'];
    $message	= ViewHelper::RedactorContentValidateAndCleanup($_POST['message']);
    $additionalRecipients = array();

    if ($recipients_base == 3 ){ // Members/Groupleads/Chapterleads or channel leads
        $groupids =  empty($_POST['groupids']) ? '' : $_COMPANY->decodeIdsInCSV(implode(',',$_POST['groupids']));
        $chapterids =  empty($_POST['chapterids']) ? '' : $_COMPANY->decodeIdsInCSV(implode(',',$_POST['chapterids']));
        $channelids =  empty($_POST['channelids']) ? '' : $_COMPANY->decodeIdsInCSV(implode(',',$_POST['channelids']));

        // If there is a single groupid in the list, then check if the user is authorized to update content in
        // the chapterids and channelids using canManageContentInScopeCSV. canManageContentInScopeCSV is safe for
        // groupid = 0 as well.
        // If there are more than one groupids then it should be a admin message.
        if (!$is_admin_message &&
            (strpos($groupids,',') === false && !$_USER->canManageContentInScopeCSV($groupids,$chapterids,$channelids))
        ) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("Please select a %s/%s scope"),$_COMPANY->getAppCustomization()['chapter']['name-short'],$_COMPANY->getAppCustomization()['channel']['name-short']), gettext('Error'));
        }

        // Find emails of all the recipients, sort them and save them in the session variable.
        $groupleads = array();
        $groupmembers = array();
        $chapterleads = array();
        $channelleads = array();
        $teamTembersByRoles = array();
        if(in_array('0',$members_type) && !empty($_POST['additionalRecipients'])){
            $allAdditionalRecipients =  $_POST['additionalRecipients'];
            if ($allAdditionalRecipients){
                $invalidEmails = array();
                $e_arr = extractEmailsFrom ($allAdditionalRecipients);
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
                        array_push($additionalRecipients, $e);
                    }
                }
                if (count($invalidEmails)) {
                    AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Invalid emails: %s'),implode(', ', $invalidEmails)), gettext('Error'));
                }
            }
        }

        if (in_array('1',$members_type)) {
            $groupleads = $db->ro_get("SELECT DISTINCT email FROM users JOIN groupleads ON users.userid=groupleads.userid WHERE users.companyid={$_COMPANY->id()} AND (groupleads.groupid IN ({$groupids}) AND users.isactive=1)");
        }

       
        if ($_COMPANY->getAppCustomization()['teams']['enabled'] && $groupid && in_array('5',$members_type)) {
            if (empty($_POST['team_member_roles'])) {
                AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("Please select a %s role"),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Error'));
            }
            // This is a talent peak group, we need to scope the search to defined team member roles.
            // Recipient By Role Types
            $roleIds = $_COMPANY->decodeIdsInArray($_POST['team_member_roles']);
            $team_member_roles = implode(',',$roleIds);
            $teamTembersByRoles = Team::GetUniqueProgramMembersByRoleIds($groupid,$team_member_roles);
        }
        if (in_array('2',$members_type)) {
            // It is fast to get all members matching the group and then filtering out users who do not have chapter and channel intersection
            // **** NOTE: Business Rule added below to exclude anonymous users from recipient list.
            $groupmembers = $db->ro_get("SELECT email,chapterid,channelids FROM groupmembers JOIN users USING(userid) WHERE users.companyid={$_COMPANY->id()} AND (groupmembers.groupid IN ({$groupids})) AND users.isactive=1 AND groupmembers.anonymous=0");
            // Beware: chapterid=0 is a valid value so checking with empty(chapterids) will fail. Hence, we have to
            // first check if chapterids is empty string if so we init $chapter_arr to an empty array else explode
            $chapter_arr = ($chapterids === '') ? array() : explode(',', $chapterids);
            // Beware: channelid=0 is a valid value so checking with empty(channelids) will fail. Hence, we have to
            // first check if channelids is empty string if so we init $channel_arr to an empty array else explode
            $channel_arr = ($channelids === '') ? array() : explode(',', $channelids);
            foreach ($groupmembers as $k => $v) {
                // Since every groupmember will have chapterid=0 as one of the chapter, we are using array_filter
                // to remove '0' from the list of chapterids set for the groupmember to allow us to find a true
                // intersection of chapters targeted in the message and chapters set for the group member.
                // Next, if after removing the chapterid='0', the array is empty we are resetting the chp array
                // to '0' array to allow us to find intersection for messages targeted to members who are not part
                // of any chapter.
                //Usecase 1 $v['chapterid'] = '0,3';
                //Usecase 2 $v['chapterid'] = '0';
                $chp = array_filter(explode(',', $v['chapterid'])) ?: array('0');

                // Since every groupmember will have channelid=0 as one of the channels, we are using array_filter
                // to remove '0' from the list of channelids set for the groupmember to allow us to find a true
                // intersection of channels targeted in the message and channels set for the group member.
                // Next, if after removing the channelid='0', the array is empty we are resetting the chn array
                // to '0' array to allow us to find intersection for messages targeted to members who are not part
                // of any channel.
                //Usecase 1 $v['channelids'] = '0,5';
                //Usecase 2 $v['channelids'] = '0';
                $chn = array_filter(explode(',', $v['channelids'])) ?: array('0');

                // If chapter match was expected and match was not found, or
                // If channel match was expected and match was not found
                // then remove the groupmember.
                if (($chapter_arr && empty(array_intersect($chp, $chapter_arr))) ||
                    ($channel_arr && empty(array_intersect($chn, $channel_arr)))) {
                    unset($groupmembers[$k]);
                }
            }
        }
        

        // Members type 3 is chapter leads
        if (in_array('3',$members_type)){
            $chapter_id_filter = ($chapterids === '') ? '' : "AND chapterleads.chapterid IN ({$chapterids})"; // Only if chapterids were selected, else across all chapters
            $chapterleads = $db->ro_get("SELECT DISTINCT email FROM chapterleads JOIN users ON chapterleads.userid = users.userid  WHERE users.companyid={$_COMPANY->id()} AND (groupid IN ({$groupids}) AND users.isactive=1 {$chapter_id_filter})");
        }

        // Members type 4 is channel leads
        if (in_array('4',$members_type)){
            $channel_id_filter = ($channelids === '') ? '' : "AND group_channel_leads.channelid IN ({$channelids})"; // Only if channelids were selected, else across all channels
            $channelleads = $db->ro_get("SELECT DISTINCT email FROM  group_channel_leads JOIN users ON group_channel_leads.userid = users.userid WHERE users.companyid={$_COMPANY->id()} AND (groupid IN ({$groupids}) AND users.isactive=1 {$channel_id_filter})");
        }

        $emails = array_unique(array_merge(
            array_column($groupleads,'email'),
            array_column($groupmembers,'email'),
            array_column($chapterleads, 'email'),
            array_column($channelleads, 'email'),
            array_column($teamTembersByRoles,'email')
        ));
    } elseif ($recipients_base == 2) { // All users who are not members of any group
        $users = $db->ro_get("SELECT DISTINCT `email` FROM `users` LEFT JOIN groupmembers USING (userid) WHERE `companyid`={$_COMPANY->id()} AND groupmembers.memberid is null AND (FIND_IN_SET({$_ZONE->id()},zoneids) AND users.isactive=1 AND verificationstatus=1)");
        if (!empty($users)){
            $emails = array_column($users,'email');
        }
    } elseif ($recipients_base == 1) { // All users in zone
        $users = $db->ro_get("SELECT DISTINCT `email` FROM `users` WHERE `companyid`={$_COMPANY->id()} AND  (FIND_IN_SET({$_ZONE->id()},zoneids) AND `isactive`=1 AND `verificationstatus`=1)");
        if (!empty($users)){
            $emails = array_column($users,'email');
        }
    } elseif ($recipients_base == 4) {
        $emails = array();
        // for group level DL
        $groupids =  empty($_POST['groupids']) ? '' : $_COMPANY->decodeIdsInCSV(implode(',',$_POST['groupids']));
        $userids = DynamicList::GetUserIdsByListIds($listids);

        if (!empty($userids)) {
            $groupOrZoneUserIds = array();
            $groupids_arr = $groupids ? explode(',',$groupids) : [0];
            foreach ($groupids_arr as $gid) {
                $groupOrZoneUserIds = array_unique(array_merge($groupOrZoneUserIds, explode(',', Group::GetGroupMembersAsCSV($gid, 0, 0))));
            }

            $userids = array_intersect($userids, $groupOrZoneUserIds);
        }

        if (!empty($userids)){
            $users = User::GetZoneUsersByUserids($userids);
            if (!empty($users)){
                $emails = array_column($users,'email');
            }
        }
    }

    $recipients = implode(',',$emails);
    $total_recipients = count($emails);
    if(!empty($additionalRecipients)){
        $total_recipients = count($emails)+count($additionalRecipients);
    }
    $additionalRecipients = implode(',',$additionalRecipients);

    $regionids = "";

    if (($mid = Message::CreateOrUpdateMessage($messageid,$groupids,$regionids,$from, $sent_to,$total_recipients,$recipients,$subject,$message,$chapterids,$channelids,$is_admin_message,$recipients_base,$additionalRecipients,$team_member_roles,$content_replyto_email, $listids))){

        if (!$messageid && !empty($_POST['ephemeral_topic_id'])) {
            $ephemeral_topic_id = $_COMPANY->decodeId($_POST['ephemeral_topic_id']);
            $ephemeral_topic = EphemeralTopic::GetEphemeralTopic($ephemeral_topic_id);

            $message = Message::GetMessage($mid);
            $message->moveAttachmentsFrom($ephemeral_topic);
        }

        AjaxResponse::SuccessAndExit_STRING(1, $_COMPANY->encodeId($mid), gettext('Success'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong! Please try again.'), gettext('Error'));
    }
}

elseif (isset($_GET['groupMessagePreview']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    // Authorization Check
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 ||
        ($messageid = $_COMPANY->decodeId($_GET['messageid']))<1 ||
        ($message = Message::GetMessage($messageid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    $is_admin_message = $message->isAdminMessage();
    if (($is_admin_message && !$_USER->canManageContentInScopeCSV(0,0,0)) ||
        (!$is_admin_message && !$_USER->canManageContentInScopeCSV($message->val('groupids'),$message->val('chapterids'),$message->val('channelids')))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $action = $_GET['groupMessagePreview'] == 1 ? 1 : 2;
   
    $groupnames = "";
    $chapterNames = "";    
    $channelNames = "";
    $roleNames = "";
    $listsNameCsv = "";

    if ($message->val('recipients_base') == 3){
        $groupids = $message->val('groupids');
        $chapterids = $message->val('chapterids');
        $channelids = $message->val('channelids');

        if ($groupids){
            $groupnames = $db->ro_get("SELECT GROUP_CONCAT(groupname separator ', ') as groupname FROM `groups` WHERE `groupid` IN (".$groupids.") AND `companyid`={$_COMPANY->id()}")[0]['groupname'];
        }

        if ($chapterids){
            $chapterNames 	= $db->ro_get("SELECT GROUP_CONCAT(distinct chaptername separator ', ') as chaptername FROM `chapters` WHERE chapters.companyid='{$_COMPANY->id()}' AND (chapters.zoneid='{$_ZONE->id()}' AND `chapterid` IN (".$chapterids."))")[0]['chaptername'];
        }
        // Add option for chapter not assigned if that option was chosen.
        $chapter_arr = ($chapterids === '') ? array() : explode(',', $chapterids);
        if (in_array(0, $chapter_arr)) {
            $labelForChapterNotAssigned = sprintf(gettext('%s not assigned'),$_COMPANY->getAppCustomization()['chapter']['name']);
            $chapterNames = empty($chapterNames) ? $labelForChapterNotAssigned : $chapterNames.', '.$labelForChapterNotAssigned;
        }

        if ($channelids){
            $channelNames 	= $db->ro_get("SELECT GROUP_CONCAT(distinct channelname separator ', ') as channelname FROM `group_channels` WHERE group_channels.`companyid`='{$_COMPANY->id()}' AND group_channels.zoneid='{$_ZONE->id()}' AND `channelid` IN (".$channelids.")")[0]['channelname'];
        }
        // Add label for Channel not assigned, if that option was chosen
        $channel_arr = ($channelids === '') ? array() : explode(',', $channelids);
        if (in_array(0, $channel_arr)) {
            $labelForChannelNotAssigned = sprintf(gettext('%s not assigned'),$_COMPANY->getAppCustomization()['channel']['name']);
            $channelNames = empty($channelNames) ? $labelForChannelNotAssigned : $channelNames.', '.$labelForChannelNotAssigned;
        }


        $roleTypes = $message->val('team_roleids') ? Team::GetTeamRoleTypesByIds($groupid,$message->val('team_roleids')) : [];
        $roleNames = implode(',',array_column( $roleTypes,'type'));

    } else if ($message->val('recipients_base') == 4) {
        $listids = explode(',',$message->val('listids'));
        $listname = array();
        foreach($listids as $listid){
            $l = DynamicList::GetList($listid);
            
            if ($l){
                $listname[] = $l->val('list_name');
            }
        }
        $listsNameCsv = implode(', ',  $listname);
    }
    
    include __DIR__.'/views/templates/group_message_preview.template.php';
}

//elseif (isset($_GET['groupMessageSend']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
//
//    // Authorization Check
//    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
//        ($messageid = $_COMPANY->decodeId($_POST['messageid']))<1 ||
//        ($message = Message::GetMessage($messageid)) === null
//    ) {
//        header(HTTP_BAD_REQUEST);
//        exit();
//    }
//
//    // Authorization Check
//    $is_admin_message = $message->isAdminMessage();
//    if (($is_admin_message && !$_USER->canManageContentInScopeCSV(0,0,0)) ||
//        (!$is_admin_message && !$_USER->canManageContentInScopeCSV($message->val('groupids'),$message->val('chapterids'),$message->val('channelids')))
//    ) {
//        header(HTTP_FORBIDDEN);
//        exit();
//    }
//
//    if ($message->sendMessage()){
//        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Message added to the send queue and will be emailed out shortly.'), gettext('Success'));
//    } else {
//        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Internal server error! Unable to send the message at this time.'), gettext('Error'));
//    }
//}

elseif (isset($_GET['getMessageScheduleModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
	$enc_groupid = $_GET['groupid'];
    $enc_messageid = $_GET['messageid'];

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($enc_groupid))<0 ||
        ($messageid = $_COMPANY->decodeId($enc_messageid)) < 1 ||
        ($message = Message::GetMessage($messageid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    $is_admin_message = $message->isAdminMessage();
    if (($is_admin_message && !$_USER->canManageContentInScopeCSV(0,0,0)) ||
        (!$is_admin_message && !$_USER->canManageContentInScopeCSV($message->val('groupids'),$message->val('chapterids'),$message->val('channelids')))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    // The following three parameters are needed by the publishing template.
    $integrations  = array();
    $enc_groupid = $_COMPANY->encodeId($groupid);
    $enc_objectid = $_COMPANY->encodeId($messageid);
    $template_publish_what = 'Message';
    $template_publish_js_method = 'saveScheduleMessagePublishing';

    $pre_select_publish_to_email = true;
    $hideEmailPublish = false;
    include(__DIR__ . "/views/templates/general_schedule_publish.template.php");
    exit();
}

elseif (isset($_GET['saveScheduleMessagePublishing']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        !isset($_POST['objectid']) ||
        ($messageid = $_COMPANY->decodeId($_POST['objectid']))<1 ||
        (null === ($message = Message::GetMessage($messageid)))
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $publish_where_integration = array();

    // Authorization Check Chapter
    // Authorization Check
    $is_admin_message = $message->isAdminMessage();
    if (($is_admin_message && !$_USER->canManageContentInScopeCSV(0,0,0)) ||
        (!$is_admin_message && !$_USER->canManageContentInScopeCSV($message->val('groupids'),$message->val('chapterids'),$message->val('channelids')))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    // Authorization check 2 - if messaging is restricted to admins, then do a admin check
    if($_COMPANY->getAppCustomization()['messaging']['restrict_to_admin_only'] && !$_USER->isAdmin()){
        header(HTTP_FORBIDDEN);
        exit();
    }

    $delay = 0;
    $timezone = isset($_POST['timezone']) ? $_POST['timezone'] : (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
    $publish_date = $db->covertLocaltoUTC("Y-m-d H:i:s", date("Y-m-d H:i:s"), $timezone);

    if (!empty($_POST['publish_when']) && $_POST['publish_when'] === 'scheduled') {
        $publish_date_format = $_POST['publish_Ymd']." ".$_POST['publish_h'].":".$_POST['publish_i']." ".$_POST['publish_A'];
        $publish_date = $db->covertLocaltoUTC("Y-m-d H:i:s", $publish_date_format, $timezone);
        $delay = strtotime($publish_date . ' UTC') - time();
        if ($delay < 0){
            $delay = 15;
        }
    }

    if ($delay > 2592000 || $delay < -300) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Message can be scheduled to publish up to 30 days in the future'), gettext('Error'));
    }

    if ($message->sendMessage($delay)){
        AjaxResponse::SuccessAndExit_STRING(1, $groupid ? 1 : 2, gettext('Message added to the send queue and will be emailed out shortly.'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Internal server error! Unable to send the message at this time.'), gettext('Error'));
    }
}

elseif (isset($_GET['show_recipients']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Authorization Check
    if (($messageid = $_COMPANY->decodeId($_GET['show_recipients']))<1 ||
        ($message = Message::GetMessage($messageid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    $is_admin_message = $message->isAdminMessage();
    if (($is_admin_message && !$_USER->canManageContentInScopeCSV(0,0,0)) ||
        (!$is_admin_message && !$_USER->canManageContentInScopeCSV($message->val('groupids'),$message->val('chapterids'),$message->val('channelids')))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $allRecipients = '';
    $recipients = $message->getRecipients();
    $class= "";

    if (!empty($recipients)){
        $recipients  = str_replace(',','; ',$recipients);
        $allRecipients = "<p>{$recipients}</p>";
        $class= "mt-3";
    }

    if ($message->val('additional_recipients')){
        $allRecipients .= "<p class={$class} ><strong>".gettext("Additional Recipients").": </strong><br/></p>";
        $allRecipients .= str_replace(',','; ',$message->val('additional_recipients'));
    }
    echo $allRecipients;
}

elseif (isset($_GET['openMessageReviewModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
	$enc_groupid = $_GET['groupid'];
    $enc_messageid = $_GET['messageid'];

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($enc_groupid))<0 ||
        ($messageid = $_COMPANY->decodeId($enc_messageid)) < 1 ||
        ($message = Message::GetMessage($messageid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    $is_admin_message = $message->isAdminMessage();
    if (($is_admin_message && !$_USER->canManageContentInScopeCSV(0,0,0)) ||
        (!$is_admin_message && !$_USER->canManageContentInScopeCSV($message->val('groupids'),$message->val('chapterids'),$message->val('channelids')))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $grpid = $message->val('groupids');
    $chptid = $message->val('chapterids') ?? '0';
    $chnlid = $message->val('channelids') ?? '0';
    $reviewers = User::GetReviewersByScope($grpid, $chptid, $chnlid);

    // The template needs following inputs in addition to $reviewers set above
    $template_review_what = gettext('Message');
    $enc_groupid = $_COMPANY->encodeId($groupid);
    $enc_objectid = $_COMPANY->encodeId($messageid);
    $template_email_review_function = 'sendMessageForReview';
    
    include(__DIR__ . "/views/templates/general_email_review_modal.template.php");
	
}

elseif (isset($_GET['sendMessageForReview']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0    ||
        !isset($_POST['objectid']) ||
        ($messageid = $_COMPANY->decodeId($_POST['objectid']))<1 || ($message = Message::GetMessage($messageid)) === NULL) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    $is_admin_message = $message->isAdminMessage();
    if (($is_admin_message && !$_USER->canManageContentInScopeCSV(0,0,0)) ||
        (!$is_admin_message && !$_USER->canManageContentInScopeCSV($message->val('groupids'),$message->val('chapterids'),$message->val('channelids')))
    ) {
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
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Too many emails. %s emails entered (maximum allowed is 10)'),count($validEmails)), gettext('Error'));
    }

	$reviewers = [];
	if (isset($_POST['reviewers']) && (!empty($_POST['reviewers'][0]))){
		$reviewers = $_POST['reviewers'];
	}
	$reviewers[] = $_USER->val('email'); // Add the current user to review list as well.

    $allreviewers = implode(',',array_unique (array_merge ($validEmails , $reviewers)));
  
    if (!empty($allreviewers)){
        $review_note = raw2clean($_POST['review_note']);
        $job = new MessageJob($messageid,0,0);
        $job->sendForReview($allreviewers,$review_note);
        // Update event under review status
        $message->updateMessageForReview();
        AjaxResponse::SuccessAndExit_STRING(($groupid ? 1 : 2), $_COMPANY->encodeId($messageid), gettext('Message emailed for review'), gettext('Success'));
	} else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please add some reviewers'), gettext('Error'));
	}
}

elseif (isset($_GET['cancelMessagePublishing']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0||
        !isset($_POST['messageid']) ||
        ($messageid = $_COMPANY->decodeId($_POST['messageid']))<1 ||
        ($message = Message::GetMessage($messageid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    $is_admin_message = $message->isAdminMessage();
    if (($is_admin_message && !$_USER->canManageContentInScopeCSV(0,0,0)) ||
        (!$is_admin_message && !$_USER->canManageContentInScopeCSV($message->val('groupids'),$message->val('chapterids'),$message->val('channelids')))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    
    $update_code = $message->cancelMessagePublishing();
    if ($update_code > 0) {
        $job = new MessageJob($messageid, 0,0);
        $job->cancelAllPendingJobs();
        AjaxResponse::SuccessAndExit_STRING(1,  $groupid ? 1 : 2, gettext('Message publishing canceled successfully.'), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Unable to cancel message publishing.'), gettext('Error'));

}


else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}
