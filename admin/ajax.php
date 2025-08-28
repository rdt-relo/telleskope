<?php
define('AJAX_CALL',1);
require_once __DIR__.'/head.php';
require_once __DIR__.'/../include/UserConnect.php';

global $_COMPANY; /* @var Company $_COMPANY */
global $_USER; /* @var User $_USER */
global $_ZONE; /* @var Zone $_ZONE */
global $db;


$uid=@$_SESSION['adminid'];
$cid=@$_SESSION['companyid'];

#### Utility Functions ####
function updateReportMetaFieldsFromPOST(array &$reportMeta)
{
    foreach ($reportMeta['Fields'] as $k => $v) {
        if (str_contains($k, 'extendedprofile.')) {
            $kk = str_replace('extendedprofile.', 'extendedprofile_', $k);
        } else {
            $kk = $k;
        }

        if (!isset($_POST[$kk])) {
            unset($reportMeta['Fields'][$k]);
        }
    }

    foreach ($reportMeta['AdminFields'] as $k => $v) {
        if (str_contains($k, 'extendedprofile.')) {
            $kk = str_replace('extendedprofile.', 'extendedprofile_', $k);
        } else {
            $kk = $k;
        }
        if (!isset($_POST[$kk])) {
            unset($reportMeta['AdminFields'][$k]);
        }
    }
}

###### All Ajax Calls ##########
######
if (isset($_GET['timezone'])){
	$tz = $_GET['timezone'];
	if ($tz == "undefined") {
		$tz = "";
	}
	$_SESSION['timezone'] = $tz;
}

## OK
elseif (isset($_GET['deletebranch']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['deletebranch']) || ($id = $_COMPANY->decodeId($_GET['deletebranch']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $delete = $_COMPANY->deleteCompanyBranch($id);
	print($delete);

    exit();
}

## OK
elseif (isset($_GET['deleteUser']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['deleteUser']) || ($id = $_COMPANY->decodeId($_GET['deleteUser']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

	$user = User::GetUser($id);
	if ($user) {
		print($user->purge());
    }else {
        print(0);
    }
    exit();
}

## OK
elseif (isset($_GET['undeleteUser']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['undeleteUser']) || ($id = $_COMPANY->decodeId($_GET['undeleteUser']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $user = User::GetUser($id);
    if ($user){
        print($user->reset());
    }else{
        print(0);
    }
    exit();
}

#change group status isactive 1 ACTIVE, 0 INACTIVE, 100 DELETE
## OK
elseif (isset($_GET['changeGroupStatus']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['changeGroupStatus']) ||
        ($groupid = $_COMPANY->decodeId($_GET['changeGroupStatus']))<1 ||
        ($group = Group::GetGroup($groupid)) == null
        || !isset($_POST['status'])
        || !in_array($_POST['status'], array(Group::STATUS_INACTIVE,Group::STATUS_ACTIVE,Group::STATUS_PURGE))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

	$status		= (int)$_POST['status'];
    $encodedId = $_COMPANY->encodeId($groupid);
    // Update Status
    $group->updateGroupStatus($status);

	if($status==1){
        $btns  = '<a class="" href="addgroup?edit='.$encodedId.'"><i class="fa fa-edit" title="Edit"></i></a>&nbsp;';
		$btns .= '<button aria-label="Deactivate" class="btn deluser" onclick="changeGroupStatus('."'{$encodedId}'".',0,this)" title="<strong>Are you sure you want to deactivate this group?</strong>"><i class="fa fa-lock" aria-hidden="true" title="Deactivate"></i></button>';
	}else if($status==100){
		$btns = '<button aria-label="Undelete" class="btn deluser" onclick="changeGroupStatus('."'{$encodedId}'".',0,this)" title="<strong>Are you sure you want to undo Delete?</strong>"><i class="fa fa-undo" title="Undelete" aria-hidden="true"></i></button>&nbsp;';
        $btns .= '<button aria-label="delete" class="btn deluser" onclick="initGroupPermanentDeleteConfirmation('."'{$encodedId}'".',0,this)" title="<strong>
        Are you sure you want to delete this group permanently?</strong>"><i class="fa fa-trash fa-l" title="delete" aria-hidden="true"></i></button>';
	}else{
	    $btns  = '<a class="" href="addgroup?edit='.$encodedId.'"><i class="fa fa-edit" title="Edit"></i></a>&nbsp;';
		$btns .= '<button aria-label="Activate" class="btn deluser" onclick="changeGroupStatus('."'{$encodedId}'".',1,this)" title="<strong>Are you sure you want to activate this group?</strong>"><i class="fa fa-unlock-alt" aria-hidden="true" title="Activate"></i></button>&nbsp;';
		$btns .= '<button aria-label="Delete" class="btn deluser" onclick="changeGroupStatus('."'{$encodedId}'".',100,this)" title="<strong>Are you sure you want to Delete this group?</strong>"><i class="fa fa-trash fa-l" title="Delete" ></i></button>';
	}
	echo $btns;
    exit();
}

if (isset($_GET['changeTaskStatus']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$_USER->canManageAffinitiesContent()) {
        Http::Forbidden();
    }

    $approval_config_task_id = $_COMPANY->decodeId($_GET['changeTaskStatus']);
    $status	= (int)$_POST['status'];

    echo TopicApprovalTask::UpdateTaskStatus($approval_config_task_id, $status);
    exit();
}
// if (isset($_GET['changeTaskRequired']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
//     if (!$_USER->canManageAffinitiesContent()) {
//         Http::Forbidden();
//     }

//     $approval_config_task_id = $_COMPANY->decodeId($_POST['approval_task_id']);
//     $status	= (int)$_POST['ischecked'];
//     $checkboxType = $_POST['checkboxType'];

//     echo TopicApprovalTask::UpdateTaskRequired($approval_config_task_id, $status, $checkboxType);
//     exit();
// }

## OK
elseif (isset($_GET['updateErgPriority']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (empty($_POST['prioritylist']) || empty($_POST['filter'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $priority = $_COMPANY->decodeIdsInCSV($_POST['prioritylist']);
    $filter = (int)$_POST['filter'];

    Group::UpdateGroupPriorityOrder($priority, $filter);
	echo 1;
}
elseif (isset($_GET['updateErgCategoryPriority']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['order']) || empty($_POST['order'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $enc_priority_array = explode(',',$_POST['order']);
    $newOrder = array();

    foreach ($enc_priority_array as $enc_id) {
        $dec_id = $_COMPANY->decodeId($enc_id);
        if ($dec_id < 1) { //validation 2, for each priority item
            header(HTTP_BAD_REQUEST);
            exit();
        }
        $newOrder[] = $dec_id;
    }
    
    Group::UpdateGroupCategoryOrder($newOrder);
	echo 1;
}
## OK
elseif (isset($_GET['updateGroupleadPriority']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['prioritylist']) ||
        empty($_POST['prioritylist']) ||
        !isset($_POST['gid']) ||
        ($gid=$_COMPANY->decodeId($_POST['gid'])) < 1 ||
        ($group = Group::GetGroup($gid)) == null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $enc_priority_array = explode(',',$_POST['prioritylist']);
    $dec_priority_array = array();

    foreach ($enc_priority_array as $enc_id) {
        $dec_id = $_COMPANY->decodeId($enc_id);
        if ($dec_id < 1) { //validation 2, for each priority item
            header(HTTP_BAD_REQUEST);
            exit();
        }
        $dec_priority_array[] = $dec_id;
    }
    $priority = implode(',',$dec_priority_array);
    $group->updateGroupleadsPriorityOrder($priority);
	echo 1;
}

## OK
elseif (isset($_GET['deletePost']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['deletePost']) || ($postid = $_COMPANY->decodeId($_GET['deletePost']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $post = Post::GetPost($postid);
    $post->deleteIt();
	
	print('1');
}

## OK
elseif (isset($_GET['deleteEvent']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['deleteEvent']) ||
        ($eventid = $_COMPANY->decodeId($_GET['deleteEvent']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($event->cancelEvent('', false)) {
        echo '1';
    }
    exit();
}

## OK
elseif (isset($_GET['deleteLeads']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['deleteLeads']) ||
        ($leadsid = $_COMPANY->decodeId($_GET['deleteLeads']))<1 ||
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    Group::DeleteGroupLead($groupid, $leadsid);

	
	print('1');
}

## OK
elseif (isset($_GET['removeAdmin']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (!isset($_GET['removeAdmin']) || ($id = $_COMPANY->decodeId($_GET['removeAdmin']))<1 ||
        !isset($_GET['section']) || ($_GET['section'] !== 'company' && $_GET['section'] !== 'zone')
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    $for_zone_id = ($_GET['section'] === 'zone') ? $_ZONE->id() : 0;
    if (!$_USER->isCompanyAdmin() && !$_USER->isZoneAdmin($for_zone_id)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $remove_admin = User::GetUser($id);
    $update = $remove_admin->revokeAdminPermissions($for_zone_id);

    print(1);
    exit();
}
## OK
elseif (isset($_GET['deleteApprover']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (!isset($_GET['deleteApprover']) ||
        ($approvalConfigId = $_COMPANY->decodeId($_GET['deleteApprover'])) < 1 ||
        !isset($_POST['approverId']) || ($approverUserId = $_COMPANY->decodeId($_POST['approverId'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageZoneEvents()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    // pass config id 
    $remove_row = Event::DeleteApproverFromApprovalConfiguration($approvalConfigId, $approverUserId);
    if($remove_row){
        print(1);
    }
    exit();
}

## OK
elseif (isset($_GET['updateEventType']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['updateEventType']) || ($id = $_COMPANY->decodeId($_POST['event_id']))<1
        || !isset($_POST['event_type'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $check = $db->checkRequired(array('Event Type'=>$_POST['event_type']));
    if($check){
        header(HTTP_BAD_REQUEST);
        exit();
    }
	$type = trim($_POST['event_type']);
    $zoneid = Event::GetEventTypeById($id)['zoneid'];
    Event::AddUpdateEventType($id, $type, $zoneid);
    $event_type = Event::GetEventTypeById($id) ?: '';
	if (empty($event_type)){
		print (" Error: Cannot update");
    }else{
        print ($event_type['type'] . ($event_type['zoneid'] ? ' (zone)' : ' (global)'));
    }
    exit();
}
elseif (isset($_GET['addUpdateEventTypeVolunteerModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    
	if (($eventTypeId = $_COMPANY->decodeId($_GET['encodedTypeId']))<0
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $eventTypeData = Event::GetEventTypeById($eventTypeId);
    if (empty($eventTypeData)) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $volunterTypeRequestdata = null;
    $eventVolunteerTypes = Event::GetEventVolunteerTypesForCurrentZone(false);

    $form_title = gettext("Add Event Type Volunteer");
    include(__DIR__ . "/views/add_event_type_volunteer.html");
}
// AJAX request handling for Edit Volunteer Modal
elseif (isset($_GET['editEventTypeVolunteerModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (
        ($eventTypeId = $_COMPANY->decodeId($_GET['encodedTypeId'])) < 1 ||
        ($volunteerId = $_COMPANY->decodeId($_GET['volunteerId'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $eventTypeData = Event::GetEventTypeById($eventTypeId);
    $volunterTypeRequestdata = null;
    if (!empty($eventTypeData['attributes'])) {
        $attributes = json_decode($eventTypeData['attributes'], true);
        if (array_key_exists('event_volunteer_requests',$attributes)) {
            foreach($attributes['event_volunteer_requests'] as $event_volunteer_request) {
                if ($event_volunteer_request['volunteertypeid'] == $volunteerId){
                    $volunterTypeRequestdata = $event_volunteer_request;
                    break;
                }
            }
        }
    }
    $eventVolunteerTypes = Event::GetEventVolunteerTypesForCurrentZone(true);
    $form_title = gettext("Edit Event Type Volunteer");
    include(__DIR__ . "/views/add_event_type_volunteer.html");
}
elseif (isset($_GET['submitEventTypeVolunteerForm']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $encodedTypeId = $_POST['encodedTypeId'];

    if (($eventTypeId = $_COMPANY->decodeId($encodedTypeId)) < 0) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // $csrf_token = $_POST['csrf_token'];
    $volunteer_type = intval($_POST['volunteer_type_select']);
    $volunteer_needed_count = intval($_POST['volunteer_count_input']);
    if ( $volunteer_needed_count <=0){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter a valid number of volunteers needed.'), gettext('Error'));
    }


    $result = Event::UpdateEventTypeEventVolunteerRequests($eventTypeId, $volunteer_type, $volunteer_needed_count);

    if(!$result){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('An Error Occured.'), gettext('Error'));
    }
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Updated successfully.'), gettext('Success'));
}
// AJAX request handling for removing a volunteer from event type
elseif (isset($_GET['removeEventTypeVolunteer']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $encodedTypeId = $_POST['encodedTypeId'];
    if (
        ($eventTypeId = $_COMPANY->decodeId($encodedTypeId)) < 1 ||
        ($volunteerTypeId = $_COMPANY->decodeId($_POST['volunteerTypeId'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Call the method to remove the volunteer
    if (Event::RemoveEventTypeVolunteer($eventTypeId, $volunteerTypeId)) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Volunteer removed successfully.'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('An error occurred while removing the volunteer.'), gettext('Error'));
    }
}
elseif (isset($_GET['deletePreferredTimezone']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (!isset($_GET['deletePreferredTimezone']) || ($id = $_COMPANY->decodeId($_GET['deletePreferredTimezone']))<1) {
    header(HTTP_BAD_REQUEST);
    exit();
    }
    if (Event::DeletePreferredTimezone($id)) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Timezone removed successfully.'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('An error occurred while deleting the Timezone.'), gettext('Error'));
    }
}
## OK
elseif (isset($_GET['deleteEventType']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['deleteEventType']) || ($id = $_COMPANY->decodeId($_GET['deleteEventType']))<1
        || !isset($_POST['status'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
	$status = (int)$_POST['status'];
    Event::UpdateEventTypeStatus($id, $status);
	print(1);
}

## OK
elseif (isset($_GET['deleteDisclaimer']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent() || !$_COMPANY->getAppCustomization()['disclaimer_consent']['enabled']) {
        header(HTTP_FORBIDDEN);
        exit();
    }

     //Data Validation
     if (!isset($_GET['deleteDisclaimer']) || ($id = $_COMPANY->decodeId($_GET['deleteDisclaimer']))<1
     ) {
     header(HTTP_BAD_REQUEST);
     exit();
    }
   
    $deleteDisclaimer = Disclaimer::GetDisclaimerById($id);
    $res = $deleteDisclaimer->deleteIt();
    if($res){
        echo 1;
    }else{
        echo 0;
    }
}

## OK
elseif (isset($_GET['activateDeactiveDisclaimer']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent() || !$_COMPANY->getAppCustomization()['disclaimer_consent']['enabled']) {
        header(HTTP_FORBIDDEN);
        exit();
    }

     //Data Validation
     if (!isset($_GET['activateDeactiveDisclaimer']) || ($id = $_COMPANY->decodeId($_GET['activateDeactiveDisclaimer']))<1 || !isset($_POST['status'])) {
     header(HTTP_BAD_REQUEST);
     exit();
    }

    $activateDeactiveDisclaimer = Disclaimer::GetDisclaimerById($id);  
    if(empty($activateDeactiveDisclaimer)){
        AjaxResponse::SuccessAndExit_STRING(0, '', "Disclaimer not found. Please try again later.", 'Error!');
    }

    $status = $_POST['status'] == 1 ? 1 : 2;
    if($status == 1){
        $res = $activateDeactiveDisclaimer->activateIt();
        if($res){
            AjaxResponse::SuccessAndExit_STRING(1, '', "Disclaimer activated successfully.", 'Success');
        }else{
            AjaxResponse::SuccessAndExit_STRING(0, '', "Disclaimer status not changed, Something went wrong. Please try again later.", 'Error!');
        }
        
    }else{
        $res = $activateDeactiveDisclaimer->inactivateIt();
        if($res){
            AjaxResponse::SuccessAndExit_STRING(1, '', "Disclaimer deactivated successfully.", 'Success');
        }else{
            AjaxResponse::SuccessAndExit_STRING(0, '', "Disclaimer status not changed, Something went wrong. Please try again later.", 'Error!');
        }
    }
    
}

## OK
## Load QR Code modal
elseif (isset($_GET['generateQrCode']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (
        !isset($_GET['generateQrCode']) || 
        ($event_id = $_COMPANY->decodeId($_GET['generateQrCode']))===0 ||
        ($event = Event::GetEvent($event_id)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    
    $code = "".$_COMPANY->getAppURL($_ZONE->val('app_type'))."ec2?e=".$_COMPANY->encodeId($event_id);
    ?>
    <div class="modal-header">
        <h4 class="modal-title"> QR Code of <?= $event->val('eventtitle'); ?></h4>
        <button type="button" id="btn_close" class="close" aria-label="close" data-dismiss="modal">&times;</button>
        
    </div>
    <div class="modal-body">
        <div class="container">
            <div style="text-align:center;" id="qr-<?= $event_id ?>"></div>
            <div class="col-md-12">
                <br>
                <p><strong>Location :</strong></p>
                <p><?=  $event->val('eventvanue'); ?></p>
                <p><?=  $event->val('vanueaddress'); ?></p>
                <p>
                    <strong><?= $db->covertUTCtoLocalAdvance("l M j, Y \@ g:i a T","",   $event->val('start'),$_SESSION['timezone']); ?> -
                        <?= $db->covertUTCtoLocalAdvance("g:i a", "",   $event->val('end'),$_SESSION['timezone']); ?></strong>
                </p>
            </div>
        </div>
    </div>
    <script>
        $('#qr-<?= $event_id ?>').qrcode("<?= $code; ?>");
    </script>
    <?php

}

## OK
elseif (isset($_GET['addOrUpdateLeadType']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    $typeid = 0;
    if (empty($_POST['type']) ||
        (!isset($_POST['typeid'] ) || ($typeid = $_COMPANY->decodeId($_POST['typeid'])) < 0) ||
        (!$typeid && (!isset($_POST['sys_leadtype'])))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

	$type = $_POST['type'];
	$sys_leadtype = isset($_POST['sys_leadtype']) ? $_POST['sys_leadtype'] : 0;
    $welcome_message= $_POST['welcome_message'];
    // Add '&nbsp' to all empty <p> tags. This will show empty <p> tags as newlines
    $welcome_message = preg_replace('#<p></p>#','<p>&nbsp;</p>', $welcome_message);
    $allow_create_content = isset($_POST['allow_create_content']) ? 1 : 0;
    $allow_publish_content = isset($_POST['allow_publish_content']) ? 1 : 0;
    if ($allow_publish_content) {
        $allow_create_content = 1; // Force create to 1 for users who have publish
    }
    $allow_manage = isset($_POST['allow_manage']) ? 1 : 0;
    $allow_manage_grant = isset($_POST['allow_manage_grant']) ? 1 : 0;
    $allow_manage_budget = isset($_POST['allow_manage_budget']) ? 1 : 0;
    $show_on_aboutus = isset($_POST['show_on_aboutus']) ? 1 : 0;
    $allow_manage_support = isset($_POST['allow_manage_support']) ? 1 : 0;

    if ($sys_leadtype == 5) {
        $allow_manage_budget = 0;
    }
    if (strlen($welcome_message) > 4000) {
        echo -2;
    } elseif ($_COMPANY->createOrUpdateGroupLeadType($typeid,$type, $sys_leadtype,$welcome_message,$allow_create_content,$allow_publish_content,$allow_manage,$allow_manage_budget,$show_on_aboutus, $allow_manage_grant, $allow_manage_support)){
        echo $typeid;
    } else {
        echo -1;
    }
}

elseif (isset($_GET['confirmDeleteGroupleadType']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['confirmDeleteGroupleadType']) || ($id = $_COMPANY->decodeId($_GET['typeid']))<1){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $leadcount = $_GET['usercount'];
    $users_role = $_GET['user_role'];
    $users_role_name = $_GET['users_role_name'];

    $modalTitle = $users_role_name . ' deletion confirmation';
    $enc_typeId = $_GET['typeid'];
    $audit_code = $_USER->generateAuditCode();
    $functionName = "deleteGroupleadTypePermanently";
    include(__DIR__ . "/views/permanent_delete_leads_modal.html");
}

elseif (isset($_GET['deleteGroupleadTypePermanently']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent() ||
        !($_USER->validateAuditCode($_POST['audit_code']))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (
        !isset($_POST['typeid']) || ($typeid = $_COMPANY->decodeId($_POST['typeid']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $systemLeadTypes = Group::SYS_GROUPLEAD_TYPES;
    $output = 0;
    if ($_POST['user_role'] == $systemLeadTypes[4]) {
        $output =  Group::DeleteChapterLeadType($typeid);
    } elseif ($_POST['user_role'] == $systemLeadTypes[5]) {
        $output =  Group::DeleteChannelLeadType($typeid);
    } elseif (in_array($_POST['user_role'],array($systemLeadTypes[0],$systemLeadTypes[1],$systemLeadTypes[2],$systemLeadTypes[3]))){
        $output =  Group::DeleteGroupLeadType($typeid);
    }
    echo $output;
    exit();
}
elseif (isset($_GET['showLeadsData']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (!isset($_GET['showLeadsData']) || ($typeid = $_COMPANY->decodeId($_GET['showLeadsData']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $leadsData = array();
    $update_user_message = $_GET['update_user_message'] ?? '';
    if($_GET['user_role'] == 'Chapter Leader'){
       $leadsData =  Group::GetAllChapterLeadsByType($typeid);
    }elseif($_GET['user_role'] == 'Channel Leader'){
        $leadsData =  Group::GetAllChannelLeadsByType($typeid);
    }else{
        $leadsData =  Group::GetAllGroupLeadsByType($typeid);
    }

	include(__DIR__ . "/views/leadsdata_modal.html");
}

## OK
elseif (isset($_GET['enableDisableGroupleadType']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['enableDisableGroupleadType']) || ($id = $_COMPANY->decodeId($_GET['enableDisableGroupleadType']))<1
       || !isset($_POST['status'])){
        header(HTTP_BAD_REQUEST);
        exit();
    }
	$status = (int)$_POST['status'];
	Group::UpdateGroupLeadStatus($id,$status);
	print(1);
}

## OK
elseif (isset($_GET['deleteDepartment']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['deleteDepartment']) || ($id = $_COMPANY->decodeId($_GET['deleteDepartment']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
	$_COMPANY->deleteCompanyDepartment($id);
	print(1);
}
//blocks deleted, use git blame to find the previous version
//elseif (isset($_GET['deleteReferral'])){}
//elseif (isset($_GET['deleteRecruiting']) && $_SERVER['REQUEST_METHOD'] === 'POST'){}


## OK
## todo: add confirmation to let user know that they will be taken to Affinities website when event URL is clicked
elseif (isset($_GET['filterEvents']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['filterEvents']) ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // year check
    if (($year = $_COMPANY->decodeId($_GET['year']))<0 ) {
        header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
        exit();
    }

    $groupid	= $_GET['filterEvents'];
	//$regionid	= $_GET['regionid'];
	
	if($groupid == 'all'){
		$condition =  "";
	}else{
		$condition = " and groupid ='".$groupid."'";
	}

    $yearCondition = " AND YEAR(events.start)='".$year."'";
    if ($year >date('Y')){
        $yearCondition = " AND YEAR(events.start)>='".$year."'";
    }

	//Table view
    $data = $db->get("SELECT events.*,IFNULL((SELECT groups.groupname FROM `groups`WHERE groupid=events.groupid),'Global') as groupname, firstname,lastname,picture FROM `events` LEFT JOIN users ON events.userid=users.userid WHERE events.companyid='{$_COMPANY->id()}' AND (`zoneid`='{$_ZONE->id()}' AND events.isactive >'0' AND event_series_id !=eventid $condition $yearCondition) order by eventid DESC");
    
    
?>
    <div class="table-responsive " id="list-view">
        <table id="table" class="table table-hover display compact" summary="This table displays the list of events">
            <thead>
                <tr>
                    <th width="15%" scope="col"><?= $_COMPANY->getAppCustomization()['group']["name-short"]; ?></th>
                    <th width="20%" scope="col">Event</th>
                    <th width="15%" scope="col">Date</th>
                    <th width="20%" scope="col">Location</th>
                    <th width="15%" scope="col">Created By</th>
                    <th width="15%" scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                
        <?php if(count($data)>0){
                for($i=0;$i<count($data);$i++){ ?>
                <tr id="<?= $i+1; ?>">
                    <td><strong><?= $data[$i]['groupname']; ?></strong></td>
                    <td>
                    <?php if ($data[$i]['isactive'] == 2) { ?>
                        <strong  style="color:red;"><?= $data[$i]['eventtitle']; ?>&nbsp;<sup>[draft]</sup></strong>
                    <?php } else { ?>
                        <strong><?= $data[$i]['eventtitle']; ?></strong>
                    <?php } ?>
                    </td>
                    <td> <?= $db->covertUTCtoLocal("M d H:i",$data[$i]['start'],$_SESSION['timezone']); ?> </td>
                    <td><?= $data[$i]['event_attendence_type'] == '4' ? "Other" :  $data[$i]['eventvanue']; ?><br /><?= $data[$i]['vanueaddress']; ?></td>
                    <td>
                    <?=  User::BuildProfilePictureImgTag($data[$i]['firstname'], $data[$i]['lastname'], $data[$i]['picture'],'demo2'); ?>
                <?php
                            echo "<br />".rtrim($data[$i]['firstname']." ".$data[$i]['lastname']," ");
                ?>
                    </td>
                    <td>
                    <?php if ($_COMPANY->getAppCustomization()['group']['qrcode']) { ?>
                        <a class="" onclick="generateQrCode('<?= $_COMPANY->encodeId($data[$i]['eventid']);?>')"><i class="fa fa-qrcode" title="Get QR Code" aria-hidden="true"></i></a>&nbsp;
                    <?php } ?>
                        <a class="deluser" title="<strong>Open application?</strong>" onclick="window.open('<?php echo $_COMPANY->getAppURL($_ZONE->val('app_type')).'eventview?id='.$_COMPANY->encodeId($data[$i]['eventid']); ?>')" target="_blank" rel="noopener noreferrer"><i class="fa fa-edit" title="Edit" aria-hidden="true"></i></a>&nbsp;
                        <a class="deluser" onclick="deleteEvent(<?=($i+1)?>,'<?= $_COMPANY->encodeId($data[$i]['eventid']);?>')" title="<strong>Are you sure you want to delete!</strong>"><i class="fa fa-trash fa-l" title="Delete" aria-hidden="true"></i>
                    </td>
                </tr>
                
        <?php	} ?>
                
    <?php } ?>
            </tbody>
        </table>
    </div>
    <script src="../vendor/js/datatables-2.1.8/datatables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#table').DataTable({
                "order": [],
                "bPaginate": true,
                "bInfo": false,
                "columnDefs": [
                  { targets: [0,1,2,3,4,5], orderable: false }
                ],
                "drawCallback": function( settings ) {
                    $(".deluser").popConfirm({content: ''});
                },
                language: {
                searchPlaceholder: "...",
                url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val("language")); ?>.json'
                },
            });
        });
    </script>
    <script>
            $(document).ready(function () {
            //initial for blank profile picture
            $('.initial').initial({
                charCount: 2,
                textColor: '#ffffff',
                seed: 0,
                height: 50,
                width: 50,
                fontSize: 20,
                fontWeight: 300,
                fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
                radius: 0
            });
        });
    </script>
<?php
}

## OK
elseif (isset($_GET['updateLinkPriority']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['priority']) || empty($_POST['priority'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $priorityList = explode(',', $_POST['priority']);
    $p2 = array();
    foreach ($priorityList as $p) {
        $v = $_COMPANY->decodeId($p);
        if ($v < 1) { //Validation part 2, validate each of the link ids sent.
            header(HTTP_BAD_REQUEST);
            exit();
        }
        $p2[] = $v;
    }
    $priority = implode(',', $p2);
    $_COMPANY->updateHotlinkPriority($priority);
    echo 1;
}

## OK
## Upload company logo
elseif (isset($_GET['uploadCompanyLogo']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	$file 	   		=	basename($_FILES['logo']['name']);
	$tmp 			=	$_FILES['logo']['tmp_name'];

    $mimetype = mime_content_type($tmp);
    $valid_mimes = array('image/jpeg' => 'jpg', 'image/png' => 'png');
    $logo = '';

    if (in_array($mimetype, array_keys($valid_mimes))) {
        $ext = $valid_mimes[$mimetype];
        $actual_name = "logo_" . teleskope_uuid() . "." . $ext;

        if ($_COMPANY->has('logo')){
            $old_logo = $_COMPANY->val('logo');
        } else {
            $old_logo = "";
        }
        $logo = $_COMPANY->saveFile($tmp, $actual_name,'COMPANY');

        if (!empty($logo)) {
            $_COMPANY->updateCompanyBrandingMedia($logo,$_COMPANY->val('loginscreen_background'));
            if (!empty($old_logo)){
                $_COMPANY->deleteFile($old_logo);
            }

            // Reload company by invalidating cache and reloading it from database
            $_COMPANY = Company::GetCompany($_COMPANY->id(), true);

            print_r($logo);
        } else {
            print_r(2);
        }
    } else {
        print_r (3);
    }
}

## OK
## Delete Login Background image
elseif (isset($_GET['deleteLoginScreenBackground']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($_COMPANY->has('loginscreen_background')){
        $old_background = $_COMPANY->val('loginscreen_background');
    }else{
        $old_background = "";
    }
    $_COMPANY->updateCompanyBrandingMedia($_COMPANY->val('logo'),'');

    if (!empty($old_background)){
        $_COMPANY->deleteFile($old_background);
    }

    // Reload company by invalidating cache and reloading it from database
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
}


## OK
## Delete Banner
elseif (isset($_GET['deleteBanner']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $_COMPANY->updateCompanyZoneBrandingMedia('');

    if (!empty($_ZONE->val('banner_background'))){
        $_COMPANY->deleteFile($_ZONE->val('banner_background'));
    }

    // Reload company by invalidating cache and reloading it from database
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
}

## OK
## Upload Login Background image
elseif (isset($_GET['uploadLoginScreenBackground']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $file 	   		=	basename($_FILES['loginscreen_background']['name']);
	$size 			= 	$_FILES['loginscreen_background']['size'];
	$tmp 			=	$_FILES['loginscreen_background']['tmp_name'];

	$mimetype = mime_content_type($tmp);
	$valid_mimes = array('image/jpeg' => 'jpg', 'image/png' => 'png');
	$logo = '';

	if (in_array($mimetype, array_keys($valid_mimes))) {
        $ext = $valid_mimes[$mimetype];
        $actual_name = "loginscreen_background_" . time() . "." . $ext;

        if ($_COMPANY->has('loginscreen_background')){
            $old_background = $_COMPANY->val('loginscreen_background');
        }else{
            $old_background = "";
        }

        $loginscreen_background = $_COMPANY->saveFile($tmp, $actual_name,'COMPANY');

        if (!empty($loginscreen_background)) {

            $_COMPANY->updateCompanyBrandingMedia($_COMPANY->val('logo'),$loginscreen_background);

            if (!empty($old_background)){
                $_COMPANY->deleteFile($old_background);
            }

            // Reload company by invalidating cache and reloading it from database
            $_COMPANY = Company::GetCompany($_COMPANY->id(), true);

            print_r($loginscreen_background);
        } else {
            print_r(2);
        }
    } else {
	    print_r(3);
    }
}
## Upload My events image
elseif (isset($_GET['myEventsImage']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $file 	   		=	basename($_FILES['my_events_background_image']['name']);
	$size 			= 	$_FILES['my_events_background_image']['size'];
	$tmp 			=	$_FILES['my_events_background_image']['tmp_name'];

	$mimetype = mime_content_type($tmp);
	$valid_mimes = array('image/jpeg' => 'jpg', 'image/png' => 'png');
	$logo = '';

	if (in_array($mimetype, array_keys($valid_mimes))) {
        $ext = $valid_mimes[$mimetype];
        $actual_name = "my_events_background_image_" . time() . "." . $ext;

        if ($_COMPANY->has('my_events_background')){
            $old_background = $_COMPANY->val('my_events_background');
        }else{
            $old_background = "";
        }

        $my_events_background = $_COMPANY->saveFile($tmp, $actual_name,'COMPANY');

        if (!empty($my_events_background)) {
            $_COMPANY->updateCompanyMyEventsMedia($my_events_background);
            if (!empty($old_background)){
                $_COMPANY->deleteFile($old_background);
            }

            // Reload company by invalidating cache and reloading it from database
            $_COMPANY = Company::GetCompany($_COMPANY->id(), true);

            print_r($my_events_background);
        } else {
            print_r(2);
        }
    } else {
	    print_r(3);
    }
}
## Delete My Events Background image
elseif (isset($_GET['deleteMyEventsBackground']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($_COMPANY->has('my_events_background')){
        $old_background = $_COMPANY->val('my_events_background');
    }else{
        $old_background = "";
    }
    $_COMPANY->updateCompanyMyEventsMedia('');
    if (!empty($old_background)){
        $_COMPANY->deleteFile($old_background);
    }

    // Reload company by invalidating cache and reloading it from database
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
}

## OK
## Upload Affinity home banner
elseif (isset($_GET['uploadAffinityHomeBanner']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $file 	   		=	basename($_FILES['affinity_home_banner']['name']);
	$size 			= 	$_FILES['affinity_home_banner']['size'];
	$tmp 			=	$_FILES['affinity_home_banner']['tmp_name'];

	$mimetype = mime_content_type($tmp);
	$valid_mimes = array('image/jpeg' => 'jpg', 'image/png' => 'png');

	if (in_array($mimetype, array_keys($valid_mimes))) {
        $ext = $valid_mimes[$mimetype];

        $actual_name = "group_banner_" . teleskope_uuid(). "." . $ext;

        $affinity_home_banner = $_COMPANY->saveFile($tmp, $actual_name,'GROUP');

        if (!empty($affinity_home_banner)) {
            $_COMPANY->updateCompanyZoneBrandingMedia($affinity_home_banner);

            if (!empty($_ZONE->val('banner_background'))){
                $_COMPANY->deleteFile($_ZONE->val('banner_background'));
            }

            // Reload company by invalidating cache and reloading it from database
            $_COMPANY = Company::GetCompany($_COMPANY->id(), true);

            print_r($affinity_home_banner);
        } else {
            print_r(2);
        }
    } else {
        print_r(3);
    }
}

## OK
## Upload Affinity Zone Banner
elseif (isset($_GET['zoneBannerImage']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($_GET['zoneBannerImage'] == 'delete') {
        if (!empty($_COMPANY->val('zone_banner_image'))){
            $_COMPANY->deleteFile($_COMPANY->val('zone_banner_image'));
        }
        $_COMPANY->updateCompanyZoneBannerImage('');
        // Reload company by invalidating cache and reloading it from database
        $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
        print_r(1);
        exit();

    } elseif ($_GET['zoneBannerImage'] == 'update') {

        $file = basename($_FILES['company_zone_banner']['name']);
        $size = $_FILES['company_zone_banner']['size'];
        $tmp = $_FILES['company_zone_banner']['tmp_name'];

        $mimetype = mime_content_type($tmp);
        $valid_mimes = array('image/jpeg' => 'jpg', 'image/png' => 'png');

        if (in_array($mimetype, array_keys($valid_mimes))) {
            $ext = $valid_mimes[$mimetype];

            $actual_name = "zone_banner_" . teleskope_uuid() . "." . $ext;

            $company_zone_banner = $_COMPANY->saveFile($tmp, $actual_name, 'COMPANY');

            if (!empty($company_zone_banner)) {
                $_COMPANY->updateCompanyZoneBannerImage($company_zone_banner);

                if (!empty($_COMPANY->val('zone_banner_image'))) {
                    $_COMPANY->deleteFile($_COMPANY->val('zone_banner_image'));
                }
                $_COMPANY->deleteFile($zone['style']['zone_banner_image'] ?? '');

                // Reload company by invalidating cache and reloading it from database
                $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
                print_r($company_zone_banner);
            } else {
                print_r(2);
            }
        } else {
            print_r(3);
        }
    }
    exit();
}

## OK
## Show zone selected list on zone landing page
elseif (isset($_GET['updateZoneList']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
    header(HTTP_FORBIDDEN);
    exit();
    }
    
    //Data Validation
    if (!isset($_POST['zoneids'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $zone_selector_zoneids = explode(',', $_COMPANY->decodeIdsInCSV($_POST['zoneids']));
    $existing_zoneids = explode(',', $_COMPANY->val('zone_selector_zoneids'));
    $merged_zoneids = [];
    foreach ($existing_zoneids as $zid) {
        if (in_array($zid, $zone_selector_zoneids)) {
            $merged_zoneids[] = $zid;
        }
    }
    foreach ($zone_selector_zoneids as $zid) {
        if (!in_array($zid, $merged_zoneids)) {
            $merged_zoneids[] = $zid;
        }
    }

    $_COMPANY->updateZoneSelectorZoneIds(implode(',', $merged_zoneids));

    // Reload company by invalidating cache and reloading it from database
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);

}

## OK
## Show zone selected list on zone landing page
elseif (isset($_GET['getChangeZoneOrderModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }    
    //Data Validation
    if (!isset($_GET['zoneid'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $zoneId = $_COMPANY->decodeIdsInCSV($_GET['zoneid']);
    $zoneids = $_COMPANY->val('zone_selector_zoneids');
    $newArray = explode(',', $zoneids); 
    $currentPostion = ''; 
    $maxValue = count($newArray)?:1;
    if (in_array($zoneId,$newArray)) {
       $currentPostion = array_search($zoneId, $newArray) + 1;
    }
    
    include(__DIR__ . "/views/company_zone_sorting_order.html");  
  
           
}

## OK
## Show zone selected list on zone landing page
elseif (isset($_GET['changeZonePosition']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }   
    //Data Validation
    if (!isset($_POST['zoneid'], $_POST['newordervalue'], $_POST['currentPostion']) ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $zoneId = $_COMPANY->decodeIdsInCSV($_POST['zoneid']);
    $neworderPostion = $_POST['newordervalue']-1;
    $currentPostion = $_POST['currentPostion']-1;  

    $zone_selector_zoneids = $_COMPANY->val('zone_selector_zoneids');
    $zone_selector_zoneids_array = explode(',', $zone_selector_zoneids);

    $p1 = array_splice($zone_selector_zoneids_array, $currentPostion, 1);
    $p2 = array_splice($zone_selector_zoneids_array, 0, $neworderPostion);
    $zone_selector_zoneids_array = array_merge($p2, $p1, $zone_selector_zoneids_array);

    $list = implode(', ', $zone_selector_zoneids_array);
    $_COMPANY->updateZoneSelectorZoneIds($list);
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
}

## OK
## Delete Hot link
elseif (isset($_GET['deleteHotLink']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['deleteHotLink']) || ($link_id = $_COMPANY->decodeId($_GET['deleteHotLink']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

	$link = $_COMPANY->getHotlink($link_id);

	if (!empty($link[0]['link'])) {
        $_COMPANY->deleteFile($link[0]['link']);
    }
	if (!empty($link[0]['image'])) {
        $_COMPANY->deleteFile($link[0]['image']);
    }

	$_COMPANY->deleteHotlink($link_id);
	print(1);
}

## OK
## Update Zone Style
elseif (isset($_GET['updateZoneStyle']) && $_SERVER['REQUEST_METHOD'] === 'POST'){ 
   
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['zone_tile_heading']) && !isset($_POST['zone_tile_sub_heading']) && !isset($_POST['remove_img']) && !isset($_FILES['zone_tile_bg_image']) && !isset($_POST['remove_compact_img']) && !isset($_FILES['zone_tile_compact_bg_image'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (isset($_FILES['zone_tile_bg_image'])) {
        $file 	   		=	basename($_FILES['zone_tile_bg_image']['name']);
        $size 			= 	$_FILES['zone_tile_bg_image']['size'];
        $tmp 			=	$_FILES['zone_tile_bg_image']['tmp_name'];

        $mimetype = mime_content_type($tmp);
        $valid_mimes = array('image/jpeg' => 'jpg', 'image/png' => 'png');
        $zone = $_ZONE->getZoneCustomization();
        if (in_array($mimetype, array_keys($valid_mimes))) {
            $ext = $valid_mimes[$mimetype];
            $actual_name = "zone_tile_bg_image_" . teleskope_uuid(). "." . $ext;
            $zone_tile_bg_image = $_COMPANY->saveFile($tmp, $actual_name,'ZONE'); 
            if (empty($zone_tile_bg_image)) {
                $error = 'image uploading error! Please try again!';
                AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
            } else {
                $_COMPANY->deleteFile($zone['style']['zone_tile_bg_image'] ?? '');
            }
        }else{
            $error = 'Only .jpg,.jpeg,.png files are allowed!';
            AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
        } 

        $zone_tile_heading = $zone['style']['zone_tile_heading'];
        $zone_tile_sub_heading = $zone['style']['zone_tile_sub_heading'];
        $zone_tile_compact_bg_image = $zone['style']['zone_tile_compact_bg_image'];

    }else{

        if(isset($_POST['remove_img']) && $_POST['remove_img'] == 'remove_img'){
            $zone = $_ZONE->getZoneCustomization();
            $_COMPANY->deleteFile($zone['style']['zone_tile_bg_image'] ?? '');
            $zone_tile_heading = $zone['style']['zone_tile_heading'];
            $zone_tile_sub_heading = $zone['style']['zone_tile_sub_heading'];
            $zone_tile_bg_image = "";
            $zone_tile_compact_bg_image = $zone['style']['zone_tile_compact_bg_image'];
        }
    }

    if (isset($_FILES['zone_tile_compact_bg_image'])) {
        $file 	   		=	basename($_FILES['zone_tile_compact_bg_image']['name']);
        $size 			= 	$_FILES['zone_tile_compact_bg_image']['size'];
        $tmp 			=	$_FILES['zone_tile_compact_bg_image']['tmp_name'];

        $mimetype = mime_content_type($tmp);
        $valid_mimes = array('image/jpeg' => 'jpg', 'image/png' => 'png');

        if (in_array($mimetype, array_keys($valid_mimes))) {
            $ext = $valid_mimes[$mimetype];
            $actual_name = "zone_tile_compact_bg_image_" . teleskope_uuid(). "." . $ext;
            $zone_tile_compact_bg_image = $_COMPANY->saveFile($tmp, $actual_name,'ZONE'); 
            if (empty($zone_tile_compact_bg_image)) {
                $error = 'image uploading error! Please try again!';
                AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
            } else {
                $_COMPANY->deleteFile($zone['style']['zone_tile_compact_bg_image'] ?? '');
            }
        }else{
            $error = 'Only .jpg,.jpeg,.png files are allowed!';
            AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
        } 

        $zone = $_ZONE->getZoneCustomization();
        $zone_tile_heading = $zone['style']['zone_tile_heading'];
        $zone_tile_sub_heading = $zone['style']['zone_tile_sub_heading'];
        $zone_tile_bg_image = $zone['style']['zone_tile_bg_image'];

    }else{

        if(isset($_POST['remove_compact_img']) && $_POST['remove_compact_img'] == 'remove_compact_img'){
            $zone = $_ZONE->getZoneCustomization();
            $_COMPANY->deleteFile($zone['style']['zone_tile_compact_bg_image'] ?? '');
            $zone_tile_heading = $zone['style']['zone_tile_heading'];
            $zone_tile_sub_heading = $zone['style']['zone_tile_sub_heading'];
            $zone_tile_bg_image = $zone['style']['zone_tile_bg_image'];
            $zone_tile_compact_bg_image = "";

        }
    }


    if(isset($_POST['zone_tile_heading'])){       
        
        $zone_tile_heading = Sanitizer::SanitizeGenericLabel($_POST['zone_tile_heading']);
        $zone = $_ZONE->getZoneCustomization();
        $zone_tile_sub_heading = $zone['style']['zone_tile_sub_heading'];
        $zone_tile_bg_image = $zone['style']['zone_tile_bg_image'];
        $zone_tile_compact_bg_image = $zone['style']['zone_tile_compact_bg_image'];

    }elseif(isset($_POST['zone_tile_sub_heading'])){        

        $zone_tile_sub_heading = Sanitizer::SanitizeGenericLabel($_POST['zone_tile_sub_heading']); 
        $zone = $_ZONE->getZoneCustomization();
        $zone_tile_heading = $zone['style']['zone_tile_heading'];
        $zone_tile_bg_image = $zone['style']['zone_tile_bg_image'];
        $zone_tile_compact_bg_image = $zone['style']['zone_tile_compact_bg_image'];

    }
       
    Company::AddUpdateZoneTile($zone_tile_bg_image,$zone_tile_heading,$zone_tile_sub_heading,$zone_tile_compact_bg_image);
            AjaxResponse::SuccessAndExit_STRING(1, '', "Zone Tile Data Updated Successfully.", 'Success');
}

elseif (isset($_GET['enableDisableZoneSelector']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $enable_or_disable = $_GET['enableDisableZoneSelector'] == 1;
    $_COMPANY->enableOrDisableZoneSelector($enable_or_disable);
    Company::GetCompany($_COMPANY->id(), true);
}

## OK
## Update Zone Landing Page Headings
elseif (isset($_GET['updateZoneHeading']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['enable_zone_selector']) && !isset($_POST['zone_heading']) && !isset($_POST['zone_sub_heading'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if(isset($_POST['zone_heading'])){
        $zone_heading = Sanitizer::SanitizeGenericLabel($_POST['zone_heading']);
    }else{
        $zone_heading =  $_COMPANY->val('zone_heading');
    }

    if(isset($_POST['zone_sub_heading'])){
        $zone_sub_heading = Sanitizer::SanitizeGenericLabel($_POST['zone_sub_heading']);
    }else{
        $zone_sub_heading =  $_COMPANY->val('zone_sub_heading');
    }

    $_COMPANY->updateZoneSelectorSettings($zone_heading, $zone_sub_heading);
    Company::GetCompany($_COMPANY->id(), true);
    AjaxResponse::SuccessAndExit_STRING(1, '', "Updated successfully.", 'Success');
}

## OK
## Update Affinities Web App Banner Title
elseif (isset($_GET['updateBannerTitle']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['web_banner_title'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $web_banner_title = Sanitizer::SanitizeGenericLabel($_POST['web_banner_title']);
    Company::UpdateZoneBannerTitle($web_banner_title);
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
    print($web_banner_title);
}

## OK
## Update Affinities Web App Sub Banner Title
elseif (isset($_GET['updateBannerSubTitle']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['web_banner_subtitle'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $web_banner_subtitle = Sanitizer::SanitizeGenericLabel($_POST['web_banner_subtitle']);

    Company::UpdateZoneBannerSubTitle($web_banner_subtitle);
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
    print($web_banner_subtitle);
}

## OK
## Update Affinities Hot Link Location
elseif (isset($_GET['updateZoneHotlinkPlacement']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['hot_link_location'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $web_hot_link_location = $_POST['hot_link_location'];
    $retVal = Company::UpdateZoneHotlinkPlacement($web_hot_link_location);
    if (!$retVal) {
        $row = $db->get("SELECT `hotlink_placement` FROM `company_zones` WHERE zoneid='{$_ZONE->id()}' AND companyid='{$_COMPANY->id()}' LIMIT 1");
        $retVal = $row[0]['hotlink_placement'] == $web_hot_link_location ? 1 : 0;
    }
    if ($retVal) {
        // Reload company by invalidating cache and reloading it from database
        $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
        print (" Hot Link Location Updated Successfully");
    } else {
        print (" Error: Cannot update");
    }
}

## OK
elseif (isset($_GET['submitBudget']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['amount']) ||
        !isset($_POST['year'])
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $budgetamount	= (float)$_POST['amount'];
    $budget_year_id   = $_POST['year'];

    $retVal = Budget2::UpdateBudget($budgetamount, $budget_year_id);
    if ($retVal > 0) {
        $companyBudget = Budget2::GetBudget($budget_year_id);
        echo $companyBudget->getTotalBudgetAvailable();
    } elseif ($retVal == 0) {
        echo 'Unable to update. Please check the amount';
    } else {
        echo 'Internal Server Error';
    }
    exit();
}

## OK
elseif (isset($_GET['updateGroupBudget']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $retVal = ['returnCode' => 0, 'successMessage'=>'', 'errorMessage'=>''];
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['amount']) ||
        !isset($_POST['year']) ||
        !isset($_POST['groupid']) ||
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $notifyleads =  $_POST['notifyleads'] ? 1 : 0;
    $budgetamount	= (float)$_POST['amount'];
    $budget_year_id = (int) $_POST['year'];

    $groupBudget = Budget2::GetBudget($budget_year_id, $groupid);
    $min = $groupBudget->getTotalBudgetAllocatedToSubAccounts() + $groupBudget->getTotalExpenses()['spent_from_allocated_budget'];
    if ($budgetamount < $min) {
        $retVal['errorMessage'] = 'Amount should be greater than or equal to '. $_USER->formatAmountForDisplay($min);
        print json_encode($retVal);
        exit();
    }

    $bid = Budget2::UpdateBudget($budgetamount, $budget_year_id, $groupid);
    $companyBudget = Budget2::GetBudget($budget_year_id);
    if ($bid > 0) {
        $retVal['returnCode'] = 1;
        $retVal['remaining_budget'] = $companyBudget->getTotalBudgetAvailable();
        $retVal['allocated_budget'] = $companyBudget->getTotalBudgetAllocatedToSubAccounts();

        if($notifyleads){
            // Send Budget Update notificaton to all Leads
            $group = Group::GetGroup($groupid);
            $admins = $group->getWhoCanManageGroupBudget();

            $admin_emails = implode(',',array_column($admins,'email'));
            $groupname = $group->val('groupname');
            $who_updated = $_USER->getFullName();
            $formatedBudgetAmount = $_COMPANY->getCurrencySymbol().$_USER->formatAmountForDisplay($budgetamount);
            $fiscalYear = Budget2::GetCompanyBudgetYearDetail($budget_year_id);

            $temp = EmailHelper::GroupBudgetUpdated($groupname, $who_updated,$fiscalYear['budget_year_title'],$formatedBudgetAmount);
            // Set from to Zone From label if available
            $from = $_ZONE->val('email_from_label') . ' Budget Update';

            $_COMPANY->emailSend2($from, $admin_emails, $temp['subject'], $temp['message'], $_ZONE->val('app_type'),'');
        }


    } elseif ($bid == 0) {
        $max = $companyBudget->getTotalBudgetAvailable() + $groupBudget->getTotalBudget();
        if ($max >0) {
            $retVal['errorMessage'] = 'Amount should be less than '. $_USER->formatAmountForDisplay($max);
        } else {
            $retVal['errorMessage'] = 'There is not enough budget available. Please change the amount requested to fit the budget restraints.';
        }
    } else {
        $retVal['errorMessage'] = 'Internal Server Error';
    }
    print json_encode($retVal);
    exit();
}

## OK
## Load Approve Budget Modal
elseif (isset($_GET['loadBudgetApprove']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['loadBudgetApprove']) || ($request_id = $_COMPANY->decodeId($_GET['loadBudgetApprove']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

	$approve = $db->get("SELECT * FROM `budget_requests` WHERE companyid = '{$_COMPANY->id()}' AND (`request_id`='{$request_id}')");

    $date = $_USER->getLocalDateNow();
    $currentBudgetYear = Session::GetInstance()->budget_year ?: Budget2::GetBudgetYearIdByDate($date);


    $budgeRequest = Budget2::GetBudgetRequestDetail($request_id);
    $budget_request = BudgetRequest::Hydrate($request_id, $budgeRequest);

    $groupid = $budgeRequest['groupid'];

    $groupBudget = Budget2::GetBudget($currentBudgetYear, $groupid);
    $budgetYear = Budget2::GetCompanyBudgetYearDetail($currentBudgetYear);
    if ($budgeRequest['chapterid']) {
        $chapterBudget = Budget2::GetBudget($budgetYear['budget_year_id'],$groupid,$budgeRequest['chapterid']);

        $parentAvailableBudget = $groupBudget->getTotalBudgetAvailable();
        $childAllocatedBudget = $chapterBudget->getTotalBudget();
        $childAvailableBudget = $chapterBudget->getTotalBudgetAvailable();
        $parentName = $_COMPANY->getAppCustomization()['group']['name-short'];
        $childName = $_COMPANY->getAppCustomization()['chapter']['name-short'];
    } else {
        $companyBudget = Budget2::GetBudget($budgetYear['budget_year_id']);

        $parentAvailableBudget = $companyBudget->getTotalBudgetAvailable();
        $childAllocatedBudget = $groupBudget->getTotalBudget();
        $childAvailableBudget = $groupBudget->getTotalBudgetAvailable();
        $parentName = gettext('Company');
        $childName = $_COMPANY->getAppCustomization()['group']['name-short'];
    }

    $parentAvailableBudgetTitle = sprintf(gettext('Budget Available at %s level'), $parentName);
    $childAllocatedBudgetTitle = sprintf(gettext('Budget Allocated to the %s'),$childName);
    $childAvailableBudgetTitle = sprintf(gettext('Budget Available at the %s Level'), $childName);
    $moveBudgetParentTochildTitle = sprintf(gettext('Move the approved amount from %1$s budget to %2$s'), $parentName, $childName);
    $parentAvailableBudgetAfterMoveTitle = sprintf(gettext('Budget Available at %s level after this approval request'), $parentName);
    $childAllocatedBudgetAfterMoveTitle = sprintf(gettext('Budget Allocated to %s after this approval request'), $childName);
    $moveBudgetNote = sprintf(gettext('Please note: As of January 2024, there has been a change in the behavior of budget approval and movement. In previous product releases (prior to January 2024), upon approving a budget request, the approved amount would automatically be moved from the %1$s budget to the %2$s budget. <strong>This automatic budget movement is no longer the default behavior.</strong> To allow the approved budget to be moved from the %1$s budget to the %2$s budget, you must now manually select the checkbox provided above.'), $parentName, $childName);

    include(__DIR__ . "/views/templates/budget_approve_input_fields.php");
}

## OK
## Load Denied Budget Modal
elseif (isset($_GET['loadBudgetdenied']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    
?>
<input type="hidden" name="request_status" value="<?= (int)$_GET['request_status']; ?>">
<input type="hidden" name="request_id" value="<?= $_COMPANY->encodeId($_COMPANY->decodeId($_GET['loadBudgetdenied'])); ?>">
<div class="form-group">
	<label class="control-lable col-sm-12" for="date">Comment</label>
	<div class="col-sm-12">
		<textarea  class="form-control" id="approver_comment" name="approver_comment" placeholder="Any Comment" required ></textarea>
	</div>
</div>
<div class="form-group">
	<div class="text-center col-md-12 pt-3">
		<button type="button" id="request_submit-btn" onclick="submitDeniedForm();" class="btn btn-info"><?= gettext("Deny")?></button>
		<button type="button" class="btn btn-info" data-dismiss="modal"><?= gettext("Close")?></button>
	</div>
</div>

<?php
}

## OK
## Approve Budget Request
elseif (isset($_GET['approveBudget']) && $_GET['approveBudget'] == 1 && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $status = array('','Requested','Approved','Denied');
    $request_status = 2; // Approved

    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['request_id']) ||
        ($request_id = $_COMPANY->decodeId($_POST['request_id']))<1 ||
        !isset($_POST['request_status']) || ($_POST['request_status'] != $request_status) ||
        !(isset($_POST['amount_approved'])) ||
        !($budget_request_detail = Budget2::GetBudgetRequestDetail($request_id)) // Only ERG level budget requests can be approved here.
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $groupid = $budget_request_detail['groupid'];
    $amount_approved	= (float)$_POST['amount_approved'];
    $approver_comment	= (string) ($_POST['approver_comment']);
    $move_parent_budget_to_child = isset($_POST['move_parent_budget_to_child']) ? 1 : 0;

    if($amount_approved < 0.01) {
        echo 'Amount cannot be zero or a negative number.';
        exit;
    }

    $budgetYearId = Budget2::GetBudgetYearIdByDate($budget_request_detail['need_by']);
    $budgetObj = Budget2::GetBudget($budgetYearId,$budget_request_detail['groupid'],$budget_request_detail['chapterid']);

    $group = Group::GetGroup($budget_request_detail['groupid']);
    $groupname = $group->val('groupname');
    $chapterName = '';
    if ($budget_request_detail['chapterid']) {
        $chapter = $group->getChapter($budget_request_detail['chapterid']);
        $chapterName = $chapter ? $chapter['chaptername'] : '';
    }
    $requester = User::GetUser($budget_request_detail['requested_by']);
    $requester_name = $requester ? $requester->getFullName() : '';
    $requester_email = $requester ? $requester->val('email') : '';
    $approver_name = $_USER->getFullName();

    if ($move_parent_budget_to_child ){
        if ($budgetObj->moveBudgetFromParentToMe($amount_approved) <= 0) {
            echo " Not enough budget available to service this request";
            exit;
        }
        if ($budget_request_detail['chapterid']) {
            // Next also send budget update emails to all chapter leads to share that the budget has been updated
            $leads = $group->getWhoCanManageChapterBudget($budget_request_detail['chapterid']);
            if (!empty($leads)) {
                $updatedChapterBudgetObj = Budget2::GetBudget($budgetYearId, $budget_request_detail['groupid'], $budget_request_detail['chapterid']);
                $lead_emails = implode(',', array_column($leads, 'email'));
                $formatedBudgetAmount = $_COMPANY->getCurrencySymbol() . number_format($updatedChapterBudgetObj->getTotalBudget(), 2);
                $fiscalYear = Budget2::GetCompanyBudgetYearDetail($budgetYearId);

                $temp = EmailHelper::ChapterBudgetUpdated($chapterName, $groupid, $groupname, $approver_name, $fiscalYear['budget_year_title'], $formatedBudgetAmount);
                // Set from to Zone From label if available
                $from = $group->val('from_email_label') . ' Budget Update';

                $_COMPANY->emailSend2($from, $lead_emails, $temp['subject'], $temp['message'], $_ZONE->val('app_type'), '');
            }
        } else {
            // Next also send an email to all Group Leads / Budgets role to share that the budget has been updated
            $leads = $group->getWhoCanManageGroupBudget();
            if (!empty($leads)) {
                $admin_emails = implode(',', array_column($leads, 'email'));
                $updatedBudgetObj = Budget2::GetBudget($budgetYearId, $budget_request_detail['groupid'], $budget_request_detail['chapterid']);
                $formatedBudgetAmount = $_COMPANY->getCurrencySymbol() . $_USER->formatAmountForDisplay($updatedBudgetObj->getTotalBudget());
                $fiscalYear = Budget2::GetCompanyBudgetYearDetail($budgetYearId);
                $temp = EmailHelper::GroupBudgetUpdated($groupname, $approver_name, $fiscalYear['budget_year_title'], $formatedBudgetAmount);
                // Set from to Zone From label if available
                $from = $_ZONE->val('email_from_label') . ' Budget Update';

                $_COMPANY->emailSend2($from, $admin_emails, $temp['subject'], $temp['message'], $_ZONE->val('app_type'), '');
            }
        }
    }

    Budget2::ApproveOrDenyBudgetRequest($groupid, $request_id, $amount_approved, $approver_comment, $request_status, $budget_request_detail['budget_usesid']??0, $move_parent_budget_to_child );

    // Email Notification
    $requested_amount = $_COMPANY->getCurrencySymbol().$_USER->formatAmountForDisplay($budget_request_detail['requested_amount']);
    $approved_amount = $_COMPANY->getCurrencySymbol().$_USER->formatAmountForDisplay($amount_approved);

    // Set from to Zone From label if available
    $from = $_ZONE->val('email_from_label') . ' Budget Request';

    // Get the email template
        $budget_request = BudgetRequest::GetBudgetRequest($request_id);
    if ($budget_request_detail['chapterid']) {
        $temp = EmailHelper::ChapterBudgetRequestApproved($chapterName, $groupid, $groupname, $requester_name, $budget_request_detail['purpose'], $requested_amount, $approved_amount, $approver_comment, $approver_name, $budget_request);
        $_COMPANY->emailSend2($from, $requester_email, $temp['subject'], $temp['message'], $_ZONE->val('app_type'),'');
    } else {
        $temp = EmailHelper::GroupBudgetRequestApproved($groupname, $requester_name, $budget_request_detail['purpose'], $requested_amount, $approved_amount, $approver_comment, $approver_name, $budget_request);
        $_COMPANY->emailSend2($from, $requester_email, $temp['subject'], $temp['message'], $_ZONE->val('app_type'),'');
    }

    echo 1;
    exit();
}

## OK
## Denied Budget Request
elseif (isset($_GET['denyBudget']) && $_GET['denyBudget'] == 1 && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $status = array('','Requested','Approved','Denied');
    $request_status = 3; // Denied

    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['request_id']) ||
        ($request_id = $_COMPANY->decodeId($_POST['request_id']))<1 ||
        !isset($_POST['request_status']) || ($_POST['request_status'] != $request_status) ||
        !($budget_request_detail = Budget2::GetBudgetRequestDetail($request_id))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $groupid = $budget_request_detail['groupid'];
	$approver_comment = raw2clean($_POST['approver_comment']);

    Budget2::ApproveOrDenyBudgetRequest($groupid, $request_id, 0, $approver_comment, $request_status, $budget_request_detail['budget_usesid']??0, 0);

    // Email Notification
    $group = Group::GetGroup($budget_request_detail['groupid']);
    $groupname = $group->val('groupname');
    $requester = User::GetUser($budget_request_detail['requested_by']);
    $requester_name = $requester ? $requester->getFullName() : '';
    $requester_email = $requester ? $requester->val('email') : '';
    $approver_name = $_USER->getFullName();
    $approver_email = $_USER->val('email');
    $requested_amount = $_COMPANY->getCurrencySymbol().$_USER->formatAmountForDisplay($budget_request_detail['requested_amount']);
    $approved_amount = $_COMPANY->getCurrencySymbol().$_USER->formatAmountForDisplay($budget_request_detail['amount_approved']);

    $budget_request = BudgetRequest::Hydrate($request_id, $budget_request_detail);
    // Get the email template
    $temp = EmailHelper::GroupBudgetRequestDenied($groupname,$requester_name,$approver_name,$approver_email,$budget_request_detail['purpose'], $requested_amount,$approved_amount,$approver_comment, $budget_request);

    // Set from to Zone From label if available
    $from = $_ZONE->val('email_from_label') . ' Budget Request';

    $_COMPANY->emailSend2($from, $requester_email, $temp['subject'], $temp['message'], $_ZONE->val('app_type'),'');
    echo 1;
    exit();
}

## OK
## Update User timezone
elseif (isset($_GET['updateTimeZone']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (!isset($_POST['timezone']) ||
        !(isValidTimeZone($_POST['timezone']))) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $timezone = explode(" ",$_POST['timezone'])[0];
    if ($_USER->updateTimezone($timezone)) {
        $_SESSION['timezone'] = $timezone;
        unset($_SESSION['timezone_ask_user']);
        exit(1);
    }
    exit();
}

## OK
## Use detected timezone
elseif (isset($_GET['useBrowserTimezone']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
	$_SESSION['timezone'] = $_SESSION['tz_b'];
    unset($_SESSION['timezone_ask_user']);
	print 1;
}

## OK
## Use Profile timezone
elseif (isset($_GET['useProfileTimezone']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
	$_SESSION['timezone'] = $_USER->val('timezone');
    unset($_SESSION['timezone_ask_user']);
	print 1;
}

## Ajax Search
## OK
elseif (isset($_GET['search_keyword_user']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    // SearchUsersByKeyword is safe method and can handle unclean data, so no need for raw2clean.
    $activeusers = User::SearchUsersByKeyword($_GET['search_keyword_user']);

	if(count($activeusers)>0){ ?>
		<select class="form-control userdata" name="userid" onchange="closeDropdown()" id="user_search" required >
			<option value="">Select an user</option>
<?php	foreach ($activeusers as $activeuser){ ?>
			<option value="<?= $_COMPANY->encodeId($activeuser['userid']); ?>" ><?= rtrim(($activeuser['firstname']." ".$activeuser['lastname'])," ")." (". $activeuser['email'].") - ".$activeuser['jobtitle']; ?></option>
<?php 	} ?>
		</select>

<?php }else{ ?>
		<select class="form-control userdata" name="userid" id="user_search" required>
			<option value="">No match found.</option>
		</select>
		
<?php	} 
}

## OK
elseif (isset($_GET['change_group_chapter_status']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['gid']) || !isset($_POST['status']) ||
        ($chapterid = $_COMPANY->decodeId($_GET['change_group_chapter_status']))<1 ||
        ($groupid = $_COMPANY->decodeId($_POST['gid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $encodedChapterId = $_COMPANY->encodeId($chapterid );
    $encodedGroupId = $_COMPANY->encodeId($groupid);
    $status = (int)$_POST['status'];

    $group->changeChapterStatus($chapterid,$status);

	if($status==1){
        $btns  = '<a class="" href="newChapter?gid='.$encodedGroupId.'&cid='.$encodedChapterId.'"><i class="fa fa-edit" title="Edit"></i></a>&nbsp;';
        $btns .= '<button aria-label="Deactivate" class="btn btn-no-style deluser" onclick="changeGroupChapterStatus('."'{$encodedGroupId}','{$encodedChapterId}'".',0,this)" title="<strong>Are you sure you want to Deactivate!</strong>"><i class="fa fa-lock" title="Deactivate" aria-hidden="true"></i></button>';
    }else{
        $btns  = '<a class="" href="newChapter?gid='.$encodedGroupId.'&cid='.$encodedChapterId.'"><i class="fa fa-edit" title="Edit"></i></a>&nbsp;';
        $btns .= '<button aria-label="Activate" class="btn btn-no-style deluser" onclick="changeGroupChapterStatus('."'{$encodedGroupId}','{$encodedChapterId}'".',1,this)" title="<strong>Are you sure you want to Activate!</strong>"><i class="fa fa-unlock-alt" aria-hidden="true" title="Activate"></i></button>&nbsp;';
        $btns .= '<button aria-label="Delete" class="btn btn-no-style"  onclick="initChapterPermanentDeleteConfirmation('."'{$encodedGroupId}','{$encodedChapterId}}'".')" ><i class="fa fa-trash fa-l" title="Delete"></i></button>';
    }

	echo $btns;
}

elseif (isset($_GET['search_users_to_lead_channel']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['cid']) || !isset($_GET['gid']) ||
        ($channelid = $_COMPANY->decodeId($_GET['cid']))<1 ||
        ($groupId = $_COMPANY->decodeId($_GET['gid']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // SearchUsersByKeyword is safe method and can handle unclean data, so no need for raw2clean.
    $activeusers = User::SearchUsersByKeyword($_GET['search_users_to_lead_channel']);


    $dropdown = '';
    if(count($activeusers)>0){
        $dropdown .= "<select class='form-control userdata' name='userid' onchange='closeDropdown()' id='user_search' required >";
        $dropdown .= "<option value=''>Select an user</option>";

        for($a=0;$a<count($activeusers);$a++){
            $dropdown .=  "<option value='".$_COMPANY->encodeId($activeusers[$a]['userid'])."'>".rtrim(($activeusers[$a]['firstname']." ".$activeusers[$a]['lastname'])," ")." (".$activeusers[$a]['email'].") - ".$activeusers[$a]['jobtitle']."</option>";
        }
        $dropdown .= '</select>';
    }else{
        $dropdown .= "<select class='form-control userdata' name='userid' id='user_search' required>";
        $dropdown .= "<option value=''>No match found.</option>";
        $dropdown .= "</select>";
    }
    echo $dropdown;
}

## OK
elseif (isset($_GET['search_users_to_lead_chapter']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['cid']) || !isset($_GET['gid']) ||
        ($chapterId = $_COMPANY->decodeId($_GET['cid']))<1 ||
        ($groupId = $_COMPANY->decodeId($_GET['gid']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // SearchUsersByKeyword is safe method and can handle unclean data, so no need for raw2clean.
    $activeusers = User::SearchUsersByKeyword($_GET['search_users_to_lead_chapter']);



	$dropdown = '';
	if(count($activeusers)>0){
		$dropdown .= "<select class='form-control userdata' name='userid' onchange='closeDropdown()' id='user_search' required >";
		$dropdown .= "<option value=''>Select an user</option>";

		for($a=0;$a<count($activeusers);$a++){
		    $dropdown .=  "<option value='".$_COMPANY->encodeId($activeusers[$a]['userid'])."'>".rtrim(($activeusers[$a]['firstname']." ".$activeusers[$a]['lastname'])," ")." (".$activeusers[$a]['email'].") - ".$activeusers[$a]['jobtitle']."</option>";
		}
		$dropdown .= '</select>';
	}else{
        $dropdown .= "<select class='form-control userdata' name='userid' id='user_search' required>";
		$dropdown .= "<option value=''>No match found.</option>";
		$dropdown .= "</select>";
	}
	echo $dropdown;
}

## OK
elseif (isset($_GET['delete_chapter_lead']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['gid']) || !isset($_POST['cid']) ||
        ($leadid = $_COMPANY->decodeId($_GET['delete_chapter_lead']))<1 ||
        ($chapterid = $_COMPANY->decodeId($_POST['cid']))<1 ||
        ($groupid = $_COMPANY->decodeId($_POST['gid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

	if($group->removeChapterLead($chapterid,$leadid)){
		echo 1;
	}else{
		echo "Something went wrong. Please try again.!";
	}
}

elseif (isset($_GET['search_keyword_group']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	$keyword = $_GET['search_keyword_group'];
	$activeGroups = array();
	$activeChapters = array();

	if ($keyword!=""){
		$activeGroups = $db->get("SELECT `groupid`, `groupname` FROM `groups` WHERE `companyid`='".$_COMPANY->id()."' AND `isactive`=1 AND   `groupname` LIKE  '%".$keyword."%' ");
	}

	if (count($activeGroups)>0){ ?>
		<select class="form-control userdata" name="groupid" onchange="getChapterslist(this);closeGroupDropdown()" id="group_search" required >
			<option value="">Select a Group</option>
<?php	for($a=0;$a<count($activeGroups);$a++){  ?>
			<option value="<?= $_COMPANY->encodeId($activeGroups[$a]['groupid']); ?>" ><?= $activeGroups[$a]['groupname']; ?></option>
<?php 	} ?>
		</select>

<?php }else{ ?>
		<select class="form-control userdata" name="groupid" id="group_search" required>
			<option value="">No match found.</option>
		</select>
		
<?php	} 
}

elseif (isset($_GET['get_group_chapters']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	$groupId =  $_COMPANY->decodeId($_GET['get_group_chapters']);
	$activeChapters = array();
	if ($groupId!=""){
		$activeChapters = $db->get("SELECT `chapterid`, `chaptername` FROM `chapters` WHERE chapters.companyid='{$_COMPANY->id()}' AND ( chapters.zoneid='{$_ZONE->id()}' AND `groupid`='".$groupId."' AND `isactive`=1 )");
	}
	if (count($activeChapters)>0){ ?>
		<option value="">-All <?= $_COMPANY->getAppCustomization()['chapter']["name-short-plural"]?>-</option>
<?php	for($a=0;$a<count($activeChapters);$a++){  ?>
			<option value="<?= $_COMPANY->encodeId($activeChapters[$a]['chapterid']); ?>"  ><?= $activeChapters[$a]['chaptername']; ?></option>
<?php 	} ?>
<?php }else{ ?>
	<option value="">No <?= $_COMPANY->getAppCustomization()['chapter']["name-short"]?> found.</option>
		
<?php	} 
} 

elseif (isset($_GET['changeGroupSetting']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
	$group = Group::GetGroupDetail((int) $groupid);
	$grouptype = Group::GROUP_TYPE_LABELS;
	include(__DIR__ . "/views/group_setting.html");
}

elseif (isset($_GET['updateGroupSetting']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['updateGroupSetting']))<1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    $group_type = (int)$_POST['group_type'];
    $about_show_members = intval($_POST['about_show_members'] ?? 0);
    $chapter_assign_type = $_POST['chapter_assign_type'] ?? $group->val('chapter_assign_type');

    if($_COMPANY->getAppCustomization()['group']['allow_anonymous_join']){
        $join_anonymously = intval($_POST['anonymity'] ?? 0);
    }else{
        $join_anonymously = $group->val('join_group_anonymously');
    }

    $content_restrictions = $_POST['content_restrictions'] ?? 'anyone_can_view';

	echo  $group->updateGroupSetting($group_type, $about_show_members, $chapter_assign_type, $join_anonymously, $content_restrictions);
}

elseif (isset($_GET['checkGroupleadType']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (($typeid = $_COMPANY->decodeId($_GET['checkGroupleadType']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
	$checkLeadType = $db->get("SELECT `sys_leadtype` FROM `grouplead_type` WHERE`typeid`='".$typeid."'");

	echo $checkLeadType[0]['sys_leadtype'];
}
elseif (isset($_GET['enableDisableGroupOverlay']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $overlay = (int)$_POST['show_group_overlay'];
    $_COMPANY->updateCompanyZoneSetting($overlay,$_ZONE->val('group_landing_page'));
    // Reload company by invalidating cache and reloading it from database
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
    echo 1;
}
elseif (isset($_GET['exportGroup']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($sourceGroup = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Prepare the template template_type data
    $exportedData = $sourceGroup->export();    

    if ($exportedData === false) {
        header(HTTP_INTERNAL_SERVER_ERROR);
        exit();
    }

    // JSON encode the data
    $programJsonData = json_encode($exportedData);

    // Download the JSON file
    header('Content-Type: application/json');
    echo $programJsonData;
}
elseif (isset($_GET['createFromTemplate']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (
        !isset($_POST['sourceTemplateId']) ||
        ($sourceTemplateId = $_POST['sourceTemplateId']) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

        // Create group from template
        $importedGroup = Group::CreateFromTemplate($sourceTemplateId);

        if (!($importedGroup)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', $_COMPANY->getAppCustomization()['group']["name-short"] . " Unable to create", 'Error!');
        }

        $team_action_item_template = array();
        $attributesArray = json_decode($importedGroup->val('attributes'), true);
        if (array_key_exists('team_action_item_template', $attributesArray)){
            $team_action_item_template  = $attributesArray['team_action_item_template'];
        }
     
        $id = 0;
        if (isset($attributesArray['teamroles']) && !empty($attributesArray['teamroles'])) {
            foreach ($attributesArray['teamroles'] as $teamRole) {
                $welcome_email_subject = $teamRole['welcome_email_subject'] ?? "";
                $joinrequest_email_subject = $teamRole['joinrequest_email_subject'] ?? "";
                $joinrequest_message = $teamRole['joinrequest_message'] ?? "";
                $completion_email_subject = $teamRole['completion_email_subject'] ?? "";
                $completion_message = $teamRole['completion_message'] ?? "";
                $discover_tab_show = $teamRole['discover_tab_show'] ?? 1;
                $discover_tab_text = $teamRole['discover_tab_html'] ?? '';
                $roleid = Team::AddOrUpdateTeamRole($id, $importedGroup->id(), $teamRole['type'], $teamRole['sys_team_role_type'], $teamRole['min_required'], $teamRole['max_allowed'], $discover_tab_show, $discover_tab_text, $teamRole['welcome_message'], $teamRole['restrictions'], $welcome_email_subject, $teamRole['registration_start_date'], $teamRole['registration_end_date'], $teamRole['role_capacity'], $teamRole['role_request_buffer']??0, $teamRole['hide_on_request_to_join'], $joinrequest_email_subject, $joinrequest_message, $completion_message,$completion_email_subject);

                // Update assigned role ids in the action items in the template to point to new roleids.
                $index = array_search($teamRole['roleid'], array_column($team_action_item_template, 'assignedto'));
                if ($index !== false){
                    $team_action_item_template[$index]['assignedto'] = $roleid;
                }
            }
            if (!empty($team_action_item_template)){
                $attributesArray['team_action_item_template'] = $team_action_item_template;
                $attributes  =  json_encode($attributesArray);
                $importedGroup->updateGroupAttributes($attributes);
            }
        }

        // surveys
        if (!empty($attributesArray['surveys']) ) {
            foreach ($attributesArray['surveys'] as $survey) {
                $survey_chapterid = 0;
                $survey_channelid = 0;
                $surveyname = $survey['surveyname'] ;
                $anonymity = (int)$survey['anonymous']; 
                $clonedSurveyJson = $survey['survey_json'];
                $trigger = $survey['surveysubtype'];
                $is_required = $survey['is_required'] ?? 0;
                $allow_multiple = $survey['allow_multiple'] ?? 0;
            // create survey
            $survey = GroupMemberSurvey::CreateNewSurvey($importedGroup->id(), $survey_chapterid, $survey_channelid, $surveyname, $anonymity, $clonedSurveyJson, $trigger, $is_required, $allow_multiple);
            }
        }

        AjaxResponse::SuccessAndExit_STRING(1, '', $_COMPANY->getAppCustomization()['group']["name-short"] . " created successfully", 'Success');
    
}
elseif (isset($_GET['cloneGroup']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($sourceGroup = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $grouptype = Group::GROUP_TYPE_LABELS;
    $groupname = "Clone - " . $sourceGroup->val('groupname') ?? "";
    $groupname_short = "Clone - " .  $sourceGroup->val('groupname_short') ?? "";
    $groupicon = "";
    $coverphoto = "";
    $sliderphoto = "";
    $app_coverphoto = "";
    $app_sliderphoto = "";

    if ($sourceGroup->val('groupicon')){
        $ext = pathinfo($sourceGroup->val('groupicon'), PATHINFO_EXTENSION);
        $actual_name ="groupicon_".teleskope_uuid().".".$ext;
        $groupicon = $_COMPANY->copyS3File($sourceGroup->val('groupicon'),$actual_name,'GROUP');
    }

    if ($sourceGroup->val('coverphoto')){
        $ext = pathinfo($sourceGroup->val('coverphoto'), PATHINFO_EXTENSION);
        $actual_name ="groupcover_".teleskope_uuid().".".$ext;
        $coverphoto = $_COMPANY->copyS3File($sourceGroup->val('coverphoto'),$actual_name,'GROUP');
    }

    if ($sourceGroup->val('sliderphoto')){
        $ext = pathinfo($sourceGroup->val('sliderphoto'), PATHINFO_EXTENSION);
        $actual_name = "group_slider_" . teleskope_uuid() . "." . $ext;
        $sliderphoto = $_COMPANY->copyS3File($sourceGroup->val('sliderphoto'),$actual_name,'GROUP');
    }

    if ($sourceGroup->val('app_coverphoto')){
        $ext = pathinfo($sourceGroup->val('app_coverphoto'), PATHINFO_EXTENSION);
        $actual_name = "group_app_coverphoto_" . teleskope_uuid() . "." . $ext;
        $app_coverphoto = $_COMPANY->copyS3File($sourceGroup->val('app_coverphoto'),$actual_name,'GROUP');
    }

    if ($sourceGroup->val('app_sliderphoto')){
        $ext = pathinfo($sourceGroup->val('app_sliderphoto'), PATHINFO_EXTENSION);
        $actual_name = "group_app_sliderphoto_" . teleskope_uuid() . "." . $ext;
        $app_sliderphoto = $_COMPANY->copyS3File($sourceGroup->val('app_sliderphoto'),$actual_name,'GROUP');
    }

    $permatag = '';
    $attributesArray = json_decode($sourceGroup->val('attributes'), true);
    $attributes = json_encode($attributesArray);
    // fail-safe for group_category_id while cloning
    $group_category_id = $sourceGroup->val('categoryid') ?? (int)Group::GetDefaultGroupCategoryRow()['categoryid'];
    // clone group
    $clone_groupid = Group::CreateGroup($groupname_short, $groupname, $sourceGroup->val('aboutgroup'), $coverphoto, $sourceGroup->val('overlaycolor'), $sourceGroup->val('from_email_label'), $sourceGroup->val('regionid'), $groupicon, $permatag, $sourceGroup->val('overlaycolor2'), $sliderphoto, $sourceGroup->val('show_overlay_logo'), $sourceGroup->val('group_category'), $sourceGroup->val('replyto_email'), $sourceGroup->val('group_type'), $app_sliderphoto, $app_coverphoto, $sourceGroup->val('show_app_overlay_logo'), $sourceGroup->val('tagids'), $group_category_id, $attributes);

    if ($clone_groupid) {
        // Group object
        $newGroup = Group::GetGroup($clone_groupid);

        // Program leads copy request
        if ($_POST['copyLeads']) {
            $program_leads = $db->get("SELECT *,IFNULL((select GROUP_CONCAT('',region) as region from regions where FIND_IN_SET (regionid,a.regionids)),'') as region FROM `groupleads` a  WHERE`groupid`='" . $groupid . "' AND a.isactive='1' ORDER BY leadid ASC");

            if ($program_leads) {
                foreach ($program_leads as $program_lead) {
                    Group::AddGroupLead($clone_groupid, $program_lead['userid'], $program_lead['grouplead_typeid'], $program_lead['regionids'], $program_lead['roletitle']);
                    $newGroup->addOrUpdateGroupMemberByAssignment($program_lead['userid'], 0, 0);
                }
            }
        }

        $team_action_item_template = array();
        if(!empty($attributesArray)){
            if (array_key_exists('team_action_item_template', $attributesArray)){
                $team_action_item_template  = $attributesArray['team_action_item_template'];
            }
        }



        // Team roles
        $teamRoles = Team::GetProgramTeamRoles($groupid);

        $id = 0;
        if ($teamRoles) {
            foreach ($teamRoles as $teamRole) {
                $welcome_email_subject = $teamRole['welcome_email_subject'] ?? "";
                $joinrequest_email_subject = $teamRole['joinrequest_email_subject'] ?? "";
                $joinrequest_message = $teamRole['joinrequest_message'] ?? "";
                $completion_email_subject = $teamRole['completion_email_subject'] ?? "";
                $completion_message = $teamRole['completion_message'] ?? "";
                $discover_tab_show = $teamRole['discover_tab_show'] ?? 1;
                $discover_tab_text = $teamRole['discover_tab_html'] ?? '';
                $roleid = Team::AddOrUpdateTeamRole($id, $clone_groupid, $teamRole['type'], $teamRole['sys_team_role_type'], $teamRole['min_required'], $teamRole['max_allowed'], $discover_tab_show, $discover_tab_text, $teamRole['welcome_message'], $teamRole['restrictions'], $welcome_email_subject, $teamRole['registration_start_date'], $teamRole['registration_end_date'], $teamRole['role_capacity'], $teamRole['role_request_buffer'],$teamRole['hide_on_request_to_join'], $joinrequest_email_subject, $joinrequest_message,$completion_message,$completion_email_subject);

                // Update assigned role ids in the action items in the template to point to new roleids.
                foreach ($team_action_item_template as $k => $v) {
                    if ($teamRole['roleid'] == $v['assignedto']) {
                        $team_action_item_template[$k]['assignedto'] = $roleid;
                    }
                }
                //$index = array_search($teamRole['roleid'], array_column($team_action_item_template, 'assignedto'));
                //if ($index !== false){
                //    $team_action_item_template[$index]['assignedto'] = $roleid;
                //}
            }
            if (!empty($team_action_item_template)){
                $attributesArray['team_action_item_template'] = $team_action_item_template;
                $attributes  =  json_encode($attributesArray);
                $newGroup->updateGroupAttributes($attributes);
            }
        }
        AjaxResponse::SuccessAndExit_STRING(1, '', $_COMPANY->getAppCustomization()['group']["name-short"] . " created successfully", 'Success');
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', $_COMPANY->getAppCustomization()['group']["name-short"] . " Unable to create", 'Error!');
    }
}
elseif (isset($_GET['showProgramDetails']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (
        !isset($_GET['sourceTemplateId']) ||
        ($sourceTemplateId = $_GET['sourceTemplateId']) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Get the details of template
    $templateData = TskpTemplate::GetTskpTemplate($sourceTemplateId);
    if (!$templateData) {
        return false; // Template data not found
    }

    // Decode the JSON data
    $decodedtemplateData = json_decode($templateData['template_data'] ?? '', true) ?? [];

   $programLogo = $decodedtemplateData['groupicon'] ?? '';
   $programType = $decodedtemplateData['attributes']['team_program_type']['value'] ?? 0;
   $programType = Team::GetTeamProgramType($programType);
   $description = $templateData['template_description'] ?? '';
   $teamRoles = array_column($decodedtemplateData['attributes']['teamroles'] ?? [], 'type');
   $actionItems = array_column($decodedtemplateData['attributes']['team_action_item_template'] ?? [], 'title');
   $touchpoints = array_column($decodedtemplateData['attributes']['team_touch_point_template'] ?? [], 'title');
   $ajaxData = [
       'programType' => $programType,
       'programLogo' => $programLogo,
       'description' => $description,
       'teamRoles' => $teamRoles,
       'actionItems' => $actionItems,
       'touchpoints' => $touchpoints,
   ];
   echo json_encode($ajaxData);
   exit();
}
elseif (isset($_GET['getRegionsForGroup'])){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (($groupid = $_COMPANY->decodeId($_GET['getRegionsForGroup']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    $data	= $db->get("SELECT `regionid`,`region` FROM `regions` WHERE FIND_IN_SET (regionid,'".$group->val('regionid')."')");
    if (count($data)) {
 ?>
    <form class="form-horizontal" method="post" >
        <input type="hidden" id="encodedId" value="<?=$_COMPANY->encodeId($groupid);?>">
        <div class="form-group">
            <label class="col-sm-3 control-label">Select Region</label>
            <div class="col-lg-8">
                <select class="form-control" name="selectedRegion" id="selectedRegion" required >
                        <option value=''>Select a Region</option>
                <?php	foreach ($data as $row) { ?>
                            <option  value="<?=$_COMPANY->encodeId($row['regionid'])?>" ><?=  $row['region']?></option>
                <?php	} ?>                
                </select>
            </div>
        </div>				
    </form>
<?php
    } else {
        echo 0;
    }
}

elseif (isset($_GET['getOfficeLocations']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $input = array('search'=>raw2clean($_POST['search']['value']),'start'=>(int)$_POST['start'],'length'=>(int)$_POST['length'],'draw'=>(int)$_POST['draw']);

    $orderFields = ['companybranches.branchname','companybranches.branchtype','companybranches.country','regions.region'];

    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
   
    $search = "";
    if ($input['search']){
        $search = " AND (companybranches.branchname LIKE '%".$input['search']."%' OR companybranches.branchtype LIKE '%".$input['search']."%' OR companybranches.country LIKE '%".$input['search']."%' OR regions.region LIKE '%".$input['search']."%')";
    }

	$totalrows 	= $db->get("SELECT count(1) AS tot FROM `companybranches` lEFT JOIN regions ON regions.regionid=companybranches.regionid WHERE companybranches.companyid='{$_COMPANY->id()}' AND (companybranches.isactive=1 $search)")[0]['tot'];
    
    $data= $db->get("SELECT companybranches.*,regions.region FROM `companybranches` lEFT JOIN regions ON regions.regionid=companybranches.regionid WHERE companybranches.companyid='{$_COMPANY->id()}' AND (companybranches.isactive=1 $search) ORDER BY $orderFields[$orderIndex] $orderDir limit ".$input['start'].",".$input['length']." ");

    $final = [];
  
    foreach($data as $row){  
        $id = $_COMPANY->encodeId($row['branchid']); 
        $final[] = array(
            htmlspecialchars($row['branchname'] ?? ''),
            htmlspecialchars($row['branchtype'] ?? ''),
            htmlspecialchars($row['country'] ?? ''),
            htmlspecialchars($row['region'] ?? ''),
            "<a aria-label='Edit' href='companybranches?edit=".$id."'> <i class='fa fa-edit text-primary' title='Edit'></i></a>&nbsp;&nbsp;<button aria-label='Delete Office Location' class='btn btn-no-style deluser text-primary'  onclick=deleteBranch('".$id."',this)  title='<strong>Are you sure you want to delete the office location?</strong>'> <i class='fa fa-trash fa-l' title='Delete' ></i></button>"
        );
    }

    $json_data = array(
                    "draw"=> intval( $input['draw'] ), 
                    "recordsTotal"    => intval(count($final) ),
                    "recordsFiltered" => intval($totalrows), 
                    "data"            => $final
                );

    echo json_encode($json_data);
}

elseif (isset($_GET['updateMembersReassigned']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (($groupid = $_COMPANY->decodeId($_GET['updateMembersReassigned']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($group->val('chapter_assign_type') != 'auto')) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $total = 0;
    $members = $db->get("SELECT groupmembers.memberid, groupmembers.userid, groupmembers.chapterid, users.homeoffice FROM `groupmembers` LEFT JOIN users USING (userid) WHERE groupmembers.groupid='$groupid'");

    foreach ($members as $member) {
        $newChapteridList = $group->getChaptersMatchingBranchIds($member['homeoffice'],true);
        $newChapterIds = implode(',',array_column($newChapteridList,'chapterid'));
        if ($newChapterIds != $member['chapterid']) {
            $total++;
            $group->updateChapterMemberships($member['memberid'],$newChapterIds);

            // Create group user logs for removed chapters
            $removed = array_diff(explode(',',$member['chapterid']),explode(',',$newChapterIds));
            if (!empty($removed)){
                foreach($removed as $chid){
                    if ($chid){
                        // Create Group user log
                        GroupUserLogs::CreateGroupUserLog($groupid, $member['userid'], GroupUserLogs::GROUP_USER_LOGS_ACTION['REMOVE'], GroupUserLogs::GROUP_USER_LOGS_ROLES['GROUP_MEMBER'], 0, GroupUserLogs::GROUP_USER_LOGS_SUB_SCOPE['CHAPTER'], $chid, GroupUserLogs::GROUP_USER_LOGS_ACTION_REASON['GROUP_MAINTENANCE']);
                    }
                }
            }
        }
    }

    echo $total;
}

elseif (isset($_GET['updateGroupLandingPage']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['group_landing_page'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $valid_values = array('about'=>'about','announcements'=>'announcements','events'=>'events');
	$group_landing_page = $valid_values[$_POST['group_landing_page']] ?? null;

	if ($group_landing_page &&  $_COMPANY->updateCompanyZoneSetting($_ZONE->val('show_group_overlay'),$group_landing_page)) {
	    // Reload company by invalidating cache and reloading it from database
        $_COMPANY = Company::GetCompany($_COMPANY->id(), true);

		print ucfirst($group_landing_page); 
	} else {
		print 0;
	}
}

elseif (isset($_GET['getUsersList']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $show_connect_users_button = $_ZONE->isConnectFeatureEnabled();

    $deleteDays = intval($_COMPANY->getUserLifecycleSettings()['delete_after_days']);
    
    $input = array('search'=>raw2clean($_POST['search']['value']),'start'=>(int)$_POST['start'],'length'=>(int)$_POST['length'],'draw'=>(int)$_POST['draw']);

    $orderFields = ['userid','firstname','firstname','firstname'];

    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';

    $search = '';
    $searchKeyWord = trim($input['search']);
    if (strlen($searchKeyWord) > 2){
        $keyword_list = explode(' ',$searchKeyWord);

        if(count($keyword_list)>1){
           $search = " AND ( CONCAT(firstname,' ',lastname) LIKE '%".$searchKeyWord."%')";
        } else { // For all other cases
            $like_keyword = '%' . $searchKeyWord . '%';
            $search = " AND (firstname LIKE '{$like_keyword}' OR lastname LIKE '{$like_keyword}' OR email LIKE '{$like_keyword}')";
        }
    }
    $section = (string) $_GET['getUsersList'];
    // Zone filter for global user and zone user
    $zonecondition = '';
    if($section == 'zone'){
        $zonecondition = " AND FIND_IN_SET('{$_ZONE->val('zoneid')}',zoneids) ";
    }
    $totalrows = $db->ro_get("SELECT count(1) as totalrows FROM users WHERE companyid='{$_COMPANY->id()}' {$zonecondition} {$search}")[0]['totalrows'];
    $data = $db->ro_get("SELECT * FROM users WHERE companyid='{$_COMPANY->id()}' {$zonecondition} {$search}  ORDER BY {$orderFields[$orderIndex]} {$orderDir} limit {$input['start']}, {$input['length']}");

    $appZones = $_COMPANY->getZones($_ZONE->val('app_type')); // Get all zones of current app_type
    $i=1;
    $final = [];
    foreach($data as $row){
        $encodedId = $_COMPANY->encodeId($row['userid']);
        $manageConnectUser = false;
        $connectUser = null;
        if ($show_connect_users_button && !$_COMPANY->isValidAndRoutableEmail($row['email'])){
            $connectUser = UserConnect::GetConnectUserByTeleskopeUserid($row['userid']);
            $manageConnectUser  = true;
        }

        // $row['memberin'] = $db->get("SELECT group_concat(groupname SEPARATOR '<br>') as memberin FROM groupmembers LEFT JOIN `groups`USING (groupid) WHERE userid = '{$row['userid']}' AND groups.zoneid='{$_ZONE->id()}'")[0]['memberin']??'';

        $getUserGroupNames = Group::GetFormattedListOfGroupnamesByUserMembership($row['userid']);
        $userGroupnamesCSV  = implode(', ',$getUserGroupNames) ?? '';
    
        if($row['firstname']==""){
            $name = $row['email'];
        }else{
            $name = trim($row['firstname']." ".$row['lastname']);
        }
        $profilepic = User::BuildProfilePictureImgTag($row['firstname'],$row['lastname'], $row['picture'],'demo2', 'User Profile Picture');
        //$profilepic .= '<br/>'.$row['firstname'].' '.$row['lastname'];

        $last_active_day = '0';//(int)((time() - strtotime($row['createdon'].' UTC'))/86400);
        $last_active_row = $db->ro_get("SELECT usagetime FROM appusage WHERE companyid='{$_COMPANY->id()}' AND userid='{$row['userid']}' ORDER BY usagetime desc LIMIT 1");

        if (count($last_active_row)){
            $last_active_day = (int)((time() - strtotime($last_active_row[0]['usagetime'].' UTC'))/86400);
        }

        if ($row['isactive'] == "100") {
            if ($_COMPANY->getUserLifecycleSettings()['allow_delete']) {
                $deleteDateString = DateTime::createFromFormat ( "Y-m-d H:i:s", $row["modified"])->modify("+{$deleteDays} day")->format("d-M-Y");
                $status = '<span style="color: red; ">Pending Deletion</span>
                    <br>User will be permanently removed after '. $deleteDateString .' ';
            } else {
                $status = '<span style="color: red; ">Marked as Deleted</span>';
            }
        } elseif ($row['isactive'] == "101") {
            if ($_COMPANY->getUserLifecycleSettings()['allow_delete']) {
                $deleteDateString = DateTime::createFromFormat ( "Y-m-d H:i:s", $row["modified"])->modify("+{$deleteDays} day")->format("d-M-Y");
                $status = '<span style="color: red; ">User Initiated Account Deletion </span>
                    <br>User will be permanently removed after ' . $deleteDateString . ' ';
            } else {
                $status = '<span style="color: red; ">Marked as Deleted By User</span>';
            }

        } elseif ($row['isactive'] == "3") {
            $status = '<span style="color: purple; ">Account Locked</span>
                <br>Due to failed login attempts or rejected emails';

        } elseif ($row['verificationstatus'] == "2") {
            $status = '<span style="color: blue; ">Pending Verification</span>
                <br>User needs to login to verify email';

        } elseif($row['isactive'] == User::STATUS_BLOCKED) {
            $status = '<span style="color: red; ">User blocked</span>
            <br>on '. DateTime::createFromFormat ( "Y-m-d H:i:s", $row["modified"])->format("d-M-Y h:i A T").' ';

        } elseif ($last_active_day) {
            $status = '<span style="color: green; ">Active</span>
                <br>Last Activity : ' . $last_active_day . ' days ago';
        } else{
             $status = '<span style="color: green; ">Active</span>';
        }


        $mainadmin = ($row['email'] == $_COMPANY->val('email'));

        $action = '<a aria-label="'.gettext('view').'" class="btn actbtn text-primary" href="viewuser?userid='.$encodedId.'&section='.$section.'"><i class="fa fa-eye" title="View"></i></a>';
        if ($section == 'global'){ // Edit/block/delete/lock all features are only available on Company level only.
            if ($mainadmin) {
                if (0){ /* Commented on 12/27/22 by Aman as a result of removal of Company loginmethod column */
                    $action .= '&emsp;<i class="fa fa-edit" style="color:#DDDDDD;" title="Edit"></i>';
                }
                $action .= '&emsp;<i class="fa fa-trash" style="color:#DDDDDD;" title="Delete">';
            } elseif ($row['isactive'] == "100") {
                $action .= '&emsp;<button aria-label="Undelete User" class="btn btn-no-style deluser actbtn text-primary" onclick="undeleteUser('.$i.',\''.$encodedId.'\')" title="<strong>Are you sure you want to undelete User!</strong>"><i class="fa fa-undo" title="Undelete" aria-hidden="true"></i></button>';
            } elseif ($row['isactive'] == "101") {
                $action .= '&emsp;<button aria-label="Cancel User Initated Deletion" class="btn btn-no-style deluser actbtn text-primary" onclick="undeleteUser('.$i.',\''.$encodedId.'\')" title="<strong>Are you sure you want to cancel user initiated account deletion?</strong>"><i class="fa fa-undo" title="Cancel Deletion" aria-hidden="true"></i></button>';
            } elseif ($row['isactive'] == "3") {
                $action .= '&emsp;<button aria-label="Unblock User" class="btn btn-no-style deluser actbtn text-primary"  onclick="undeleteUser('.$i.',\''.$encodedId.'\')" title="<strong>Are you sure you want to unlock user account!</strong>"><i class="fa fa-undo" title="Unlock" aria-hidden="true"></i></button>';
            } else {
                if (0){ /* Commented on 12/27/22 by Aman as a result of removal of Company loginmethod column */
                    $action .= '&emsp;<a class="btn actbtn text-primary" href="adduser?userid='.$encodedId.'"><i class="fa fa-edit" title="Edit"></i></a>&ensp;';
                }
                $action .= '&emsp;<button aria-label="Delete User" class="btn btn-no-style deluser actbtn text-primary" onclick="deleteUser('.$i.',\''.$encodedId.'\')" title="<strong>Are you sure you want to delete User!</strong>"><i class="fa fa-trash fa-l" title="Delete" ></i></button>&ensp;';

                if ($row['isactive'] == User::STATUS_ACTIVE){
                    $action .= '&emsp;<button aria-label="Block User" class="btn btn-no-style deluser actbtn text-primary" onclick="blockUser('.$i.',\''.$encodedId.'\')" title="<strong>Are you sure you want to block user!</strong>"><i class="fa fa-solid fa-ban" title="Block User" aria-hidden="true"></i></button>';
                } elseif($row['isactive'] == User::STATUS_BLOCKED) {
                    $action .= '&emsp;<button aria-label="Unblock User" class="btn btn-no-style deluser actbtn" onclick="unblockUser('.$i.',\''.$encodedId.'\')" title="<strong>Are you sure you want to unblock user!</strong>"><i class="fa fa-solid fa-check"" title="Unblock User" aria-hidden="true"></i></button>';
                }
            }
        } else {
            if ($manageConnectUser){
                if ($connectUser){
                    $action .= '&emsp;<a class="" onclick="manageConnectUserModal(\''.$encodedId.'\')"><i class="fa fa-user-plus" aria-hidden="true"></i></a>';
                } else {
                    $action .= '&emsp;<a class="" onclick="connectUserModal(\''.$encodedId.'\')"><i class="fa fa-user-plus" aria-hidden="true"></i></a>';
                }
            }
        }

        // homezone
        $user_zoneids = empty($row['zoneids']) ? [] : explode(',', $row['zoneids']) ;
        $zonenames = array();
        foreach ($appZones as $appZone) {
            if (in_array($appZone['zoneid'], $user_zoneids)) {
                $zonenames[] = $appZone['zonename'];
            }
        }
        $userHomezone = implode(', ',$zonenames);

        $connectEmail = "";
        if($connectUser){
            $verificationStatus = "";
            if (!$connectUser->isEmailVerified()){
                $verificationStatus  = "<small style='color:red;'> [Verification Pending]</small>";
            }
            $connectEmail = "<br><font color='orange'>Connected Email:</font> ".$connectUser->val('external_email').$verificationStatus;
        } elseif ($row['external_email']) {
            $row['email'] = User::PickEmailForDisplay($row['email'], $row['external_email'], false);
        }

        $final[] = array(
            "DT_RowId" => $i,
            $profilepic,
            '<strong>'.$name.'</strong><br/>'.$row['email'].$connectEmail.'<br/>'.$row['jobtitle'],
            $userGroupnamesCSV,
            $userHomezone,
            $_COMPANY->getBranchName($row['homeoffice']) ?: '-',
            $status,
            $action
           );        
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
elseif (isset($_GET['getJoinedEventsActivityList'])){

    $user_id=$_COMPANY->decodeId($_POST['userid']);
    $section = $_GET['section'] ?? 'zone';

    $input = array('search'=>raw2clean($_POST['search']['value']),'start'=>(int)$_POST['start'],'length'=>(int)$_POST['length'],'draw'=>(int)$_POST['draw']);
    $search  = '';
    if ($input['search']){
        $search = " AND (events.eventtitle LIKE '" . $input['search'] . "%' OR eventjoiners.checkedin_date LIKE '" . $input['search'] . "%')";
    }

    $sortingOrder = "desc";
    if($_POST['order']['0']['dir'])
    {
        $sortingOrder = $_POST['order']['0']['dir'];
    }
    $userCondition = " AND (eventjoiners.userid='{$user_id}')";

    $zone_filter = " AND events.zoneid='{$_ZONE->id()}'";
    if ($section == 'global') {
        $zone_filter = '';
    }


    $totalrows =  $db->ro_get("SELECT count(1) as totalRows FROM `eventjoiners` JOIN events USING (eventid) JOIN company_zones USING (zoneid) WHERE eventjoiners.checkedin_date IS NOT NULL AND events.companyid='{$_COMPANY->id()}' AND company_zones.isactive=1 {$zone_filter} AND events.isactive=1 $userCondition $search")[0]['totalRows'];

    $events = $db->ro_get("SELECT eventjoiners.*,events.eventtitle,events.event_attendence_type,events.groupid,events.chapterid,events.channelid FROM `eventjoiners` JOIN events USING (eventid) JOIN company_zones USING (zoneid) WHERE eventjoiners.checkedin_date IS NOT NULL AND events.companyid='{$_COMPANY->id()}' AND company_zones.isactive=1  {$zone_filter} AND events.isactive=1 {$userCondition} {$search}  ORDER BY eventjoiners.checkedin_date {$sortingOrder} limit {$input['start']}, {$input['length']}");


    $final = [];
    foreach($events as $event){
        $encEventid = $_COMPANY->encodeId($event['eventid']);
        $eventTitle = '<strong>'.$event['eventtitle'].'</strong>';

        $groupname = Group::GetGroupName($event['groupid']);
        $chapters = Group::GetChaptersCSV($event['chapterid'], $event['groupid']);
        if(!empty($chapters)){
            $groupname  .= '<br><i class="fas fa-globe pl-2" style="" aria-hidden="true"></i> '.implode('<br/><i class="fas fa-globe" style="" aria-hidden="true"></i> ',array_column($chapters,'chaptername'));
        }

        if ($event['channelid']){
            $groupname  .= '<br/><i class="fas fa-layer-group pl-2" style="" aria-hidden="true"></i> '.Group::GetChannelName($event['channelid'], $event['groupid'])['channelname'];
        }


        $attendedOn = $db->covertUTCtoLocal("Y-m-d",$event['checkedin_date'],($_SESSION['timezone']??'UTC'));

        $where = "In person";
        if ($event['event_attendence_type']==2){
            $where = "Virtual";
        }


        $final[] = array_merge(array("DT_RowId" => $event['eventid']), array($eventTitle,$groupname,$attendedOn,$where ));
    }
    $json_data = array(
        "draw"=> intval( $input['draw'] ),
        "recordsTotal"    => intval($totalrows),
        "recordsFiltered" => intval($totalrows),
        "data"            => $final
    );

    echo json_encode($json_data);
    exit;
}
// Using post in the below method due to the number of fields provided
elseif (isset($_GET['downloadUserRoster']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (empty($_POST['s_type'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $groups = $_POST['s_group'] ?? array();
    $ids = [];
    for($i=0;$i<count($groups);$i++){
        array_push($ids,$_COMPANY->decodeId($groups[$i]));
    }
    $reportMeta = ReportUserMembership::GetDefaultReportRecForDownload();
    $filterType = $_POST['filterType'] ?? '';
    if ($filterType === 'date' || empty($filterType)) {
        if (!empty($_POST['startDate'])) {
            $reportMeta['Options']['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
        }
    
        if (!empty($_POST['endDate'])) {
            $reportMeta['Options']['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
        }
    } elseif ($filterType === 'month') {
        // Converting month into a date for the start and end of the selected month.
        $selectedMonth = (int)$_POST['anniversaryMonth'];
        $reportMeta['Filters']['anniversaryMonth'] = $selectedMonth;
    }

    $reportMeta['Options']['includeMembers'] = in_array('members', $_POST['s_type']) || in_array('uniquemembers', $_POST['s_type']);
    $reportMeta['Options']['includeGroupleads'] = in_array('groupleads', $_POST['s_type']);
    $reportMeta['Options']['includeChapterleads'] = in_array('chapterleads', $_POST['s_type']);
    $reportMeta['Options']['includeChannelleads'] = in_array('channelleads', $_POST['s_type']);
    $reportMeta['Options']['onlyActiveUsers'] = !empty($_POST['onlyActiveUsers']);
    $reportMeta['Options']['uniqueRecordsOnly'] = in_array('uniquemembers', $_POST['s_type']);
    $reportMeta['Options']['includeNonMembers'] = in_array('nonmembers', $_POST['s_type']);
    $reportMeta['Options']['seperateLinesForChaptersChannels'] = false; // Concat Chapter and Channel Names
    
    $filePrefix = '';
    if (in_array($_POST['s_type'][0], array('allleads','members','groupleads','chapterleads','channelleads','nonmembers','uniquemembers'))
    ) {
        $filePrefix = $_POST['s_type'][0];
    }  

    $reportMeta['Filters']['groupids'] =  $ids; // List of groupids, or empty for all groups
    
    $reportAction = $_POST['reportAction'];

    updateReportMetaFieldsFromPOST($reportMeta);

    // We do not provide option to remove groupname, chaptername, role or since date
    // We do not provide external id in the report of extended profile fields in the reports at the moment.
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'user';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportUserMembership ($_COMPANY->id(),$record);

    if ($reportAction == 'download'){
        $report->downloadReportAndExit(Report::FILE_FORMAT_CSV,$filePrefix);
        #else echo false
        echo false;
    } else {
        $title = gettext("Member Report Analytics");
        if (in_array('uniquemembers', $_POST['s_type'])){
            $title = gettext("Unique Member Report Analytics");
        } elseif (in_array('groupleads', $_POST['s_type'])){
            $title = sprintf(gettext("%s Leader Report Analytics"),$_COMPANY->getAppCustomization()['group']['name-short']);
        } elseif (in_array('chapterleads', $_POST['s_type'])){
            $title = sprintf(gettext("%s Leader Report Analytics"),$_COMPANY->getAppCustomization()['chapter']['name-short']);
        } elseif (in_array('channelleads', $_POST['s_type'])){
            $title = sprintf(gettext("%s Leader Report Analytics"),$_COMPANY->getAppCustomization()['channel']['enabled']);
        } elseif (in_array('nonmembers', $_POST['s_type'])){
            $title = gettext("Non Member Report Analytics");
        }
        $_SESSION['analytics_data'] = $report->generateAnalyticData($title);
        Http::Redirect("analytics");
    }
    exit();
}

// Using post in the below method due to the number of fields provided
elseif (isset($_GET['downloadZoneUsersRoster']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }


    $reportMeta = ReportUsers::GetDefaultReportRecForDownload();
    $filterType = $_POST['filterType'] ?? '';
    if ($filterType === 'date' || empty($filterType)) {
        if (!empty($_POST['startDate'])) {
            $reportMeta['Options']['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
        }

        if (!empty($_POST['endDate'])) {
            $reportMeta['Options']['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
        }
    }


    $reportMeta['Options']['onlyActiveUsers'] = !empty($_POST['onlyActiveUsers']);

    $reportMeta['Filters']['zoneid'] =  $_ZONE->id();
    $reportAction = $_POST['reportAction'];
    updateReportMetaFieldsFromPOST($reportMeta);

    // We do not provide option to remove groupname, chaptername, role or since date
    // We do not provide external id in the report of extended profile fields in the reports at the moment.
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'users';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportUsers($_COMPANY->id(),$record);

    if ($reportAction == 'download') {
        $report->downloadReportAndExit(Report::FILE_FORMAT_CSV,'');
        #else echo false
        echo false;
    }
    elseif (0) {
        $title = gettext("Member Report Analytics");
        $_SESSION['analytics_data'] = $report->generateAnalyticData($title);
        Http::Redirect("analytics");
    }
    exit();
}

## OK
elseif (isset($_GET['deleteExpenseType']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['deleteExpenseType']) || ($id = $_COMPANY->decodeId($_GET['deleteExpenseType']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    Budget2::DeleteExpenseType($id);
    print(1);
}

elseif (isset($_GET['getExpensesByYear']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $budget_year_id = (int)($_COMPANY->decodeId($_GET['getExpensesByYear']));
    $srch = raw2clean($_POST['search']['value']);
    $start = (int)$_POST['start'];
    $length = (int)$_POST['length'];
    $draw = (int)$_POST['draw'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
    $orderFields = ['budgetuses.date','groups.groupname','chapters.chaptername','budgetuses.description','bcc.charge_code','budgetuses.budgeted_amount','budgetuses.usedamount'];
    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderBy =  $orderFields[$orderIndex];
    $search = "";
    if ($srch){
        $search = " AND (groups.groupname LIKE '%{$srch}%' OR chapters.chaptername LIKE '%{$srch}%'  OR budgetuses.date LIKE '%{$srch}%' OR events.eventtitle LIKE '%{$srch}%' OR budgetuses.description LIKE '%{$srch}%'  OR budgetuses.usedamount LIKE '%{$srch}%' )";
    }
    $totalrows 	= $db->get("SELECT count(1) as total FROM `budgetuses` JOIN `groups` ON groups.groupid=budgetuses.groupid  AND `groups`.zoneid='{$_ZONE->id()}' left JOIN events ON events.eventid=budgetuses.eventid LEFT JOIN `chapters` ON  `chapters`.chapterid = budgetuses.chapterid  WHERE budgetuses.`companyid`='{$_COMPANY->id()}' AND (budgetuses.`budget_year_id`='{$budget_year_id}' AND budgetuses.`isactive`=1 $search )")[0]['total'];
    $expenses      = $db->get("SELECT budgetuses.*,groups.groupname,events.eventtitle,`chapters`.`chaptername`, bcc.charge_code FROM `budgetuses` LEFT JOIN `groups` ON groups.groupid=budgetuses.groupid LEFT JOIN events ON events.eventid=budgetuses.eventid LEFT JOIN `chapters` ON  `chapters`.chapterid = budgetuses.chapterid  LEFT JOIN budget_charge_codes AS bcc on bcc.charge_code_id=budgetuses.charge_code_id  WHERE budgetuses.`companyid`='{$_COMPANY->id()}' AND budgetuses.zoneid={$_ZONE->id()} AND (budgetuses.`budget_year_id`='{$budget_year_id}' AND budgetuses.`isactive`=1 {$search} ) ORDER BY {$orderBy} {$orderDir} limit {$start},{$length} ");
    $i=1;
    $final = [];
    foreach($expenses as $row){
        $encodedId = $_COMPANY->encodeId($row['usesid']);
        $final[] = array(
            "DT_RowId" => $i,
            $_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['date'],true,false,false,'','UTC'), // Do not convert date to local timezone
            $row['groupname'],
            $row['chaptername'] ?  $row['chaptername'] : '-',
            htmlspecialchars($row['description']),
            $row['charge_code'] ? htmlspecialchars($row['charge_code']) : '-',
            $_COMPANY->getCurrencySymbol().number_format($row["budgeted_amount"],2),
            '<a style="cursor:pointer; color:#3c8dbc;" onclick="openSubExpenseItems(\''.$encodedId.'\');" >'.$_COMPANY->getCurrencySymbol().number_format($row["usedamount"],2).'</a>'
           );
        $i++;
    }

    $json_data = array(
                    "draw"=> intval( $draw ),
                    "recordsTotal"    => intval(count($final) ),
                    "recordsFiltered" => intval($totalrows),
                    "data"            => $final
                );
    echo json_encode($json_data);
}

elseif (isset($_GET['openSubExpenseItems']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (!isset($_GET['openSubExpenseItems']) || ($id = $_COMPANY->decodeId($_GET['openSubExpenseItems']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $data =  $db->get("SELECT *, IFNULL((SELECT `expensetype` FROM budget_expense_types WHERE `expensetypeid`=`budgetuses_items`.expensetypeid ),'-') as expensetype  FROM `budgetuses_items` WHERE  `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND  `usesid`='" .$id."'");

    $expense_entry = ExpenseEntry::GetExpenseEntry($id);

	include(__DIR__ . "/views/expenses_sub_items.template.html");
}

elseif (isset($_GET['setBudgetYear']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    Session::GetInstance()->budget_year = $_COMPANY->decodeId($_GET['setBudgetYear']);
    echo true;
}
elseif (isset($_GET['downloadEventSpeakerReport'])) {
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportEventSpeaker::GetDefaultReportRecForDownload();

    $reportMeta['Options']['topictype'] = 'EVTSPK';
    $reportMeta['Options']['includeCustomFields'] = 1;

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'event_speaker';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportEventSpeaker ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}
elseif (isset($_GET['downloadEventOrganizationReport'])) {
    // Authorization Check
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportEventOrganization::GetDefaultReportRecForDownload();

    $reportMeta['Options']['topictype'] = 'ORG';
    $reportMeta['Options']['includeCustomFields'] = 1;

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'event_organization';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportEventOrganization ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}
// Pass id here
elseif (isset($_GET['downloadDisclaimerConsentReport'])) {
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (($disclaimerId = $_COMPANY->decodeId($_GET['downloadDisclaimerConsentReport']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $reportMeta = ReportDisclaimerConsents::GetDefaultReportRecForDownload();
    $reportMeta['Options']['disclaimerid'] = $disclaimerId;
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'disclaimer_consent';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportDisclaimerConsents ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    echo false;
    exit();
}

elseif (isset($_GET['downloadEventVolunteersReport'])) {
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    global $_ZONE;

    $reportMeta = ReportEventVolunteers::GetDefaultReportRecForDownload();
    $groupids = array();

    if(!empty($_POST['s_group'])){
        foreach($_POST['s_group'] as $g){
            array_push($groupids,$_COMPANY->decodeId($g));
        }
    }

    $reportMeta['Filters']['groupids'] = $groupids;

    $options = array();

     if (!empty($_POST['startDate'])) {
         $options['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
    }
    
    if (!empty($_POST['endDate'])) {
        $options['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
    }

    $customFields = array();
    if(isset($_POST['includeCustomFields'])){
        $options['includeCustomFields'] = $_POST['includeCustomFields'];
    }

    $reportMeta['Options'] = $options;

    if (!isset($_POST['chaptername'])) {
        unset($reportMeta['Fields']['chaptername']);
        unset($reportMeta['Fields']['enc_chapterid']);
    }

    if (!isset($_POST['channelname'])) {
        unset($reportMeta['Fields']['channelname']);
        unset($reportMeta['Fields']['enc_channelid']);
    }

    if (!isset($_POST['groupname'])) {
        unset($reportMeta['Fields']['groupname']);
        unset($reportMeta['Fields']['enc_groupid']);
    }
    $reportAction = $_POST['reportAction'];

    updateReportMetaFieldsFromPOST($reportMeta);

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'event_volunteers';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportEventVolunteers ($_COMPANY->id(),$record);
    if ($reportAction == 'download'){
        $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
        #else echo false
        echo false;
    } else {
        $title = gettext("Event Volunteers Report Analytics");
        $_SESSION['analytics_data'] = $report->generateAnalyticData($title);
        Http::Redirect("analytics");
    }
    exit();
}
elseif (isset($_GET['download_budgetyear_report'])){
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (($budget_year_id = $_COMPANY->decodeId($_POST['year_filter']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $reportMeta = ReportBudgetYear::GetDefaultReportRecForDownload();

    if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
        unset($reportMeta['Fields']['chaptername']);
        unset($reportMeta['Fields']['enc_chapterid']);
    }

    $reportMeta['Filters'] = array(
        'year' => $budget_year_id
    );

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'budget';
    $record['reportmeta'] = json_encode($reportMeta);

    $reportAction = $_POST['reportAction'];
    $report = new ReportBudgetYear ($_COMPANY->id(),$record);
    if ($reportAction == 'download'){
        $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
        #else echo false
        echo false;
    } else {
        $title = gettext("Budget Summary by Year Analytics");
        $_SESSION['analytics_data'] = $report->generateAnalyticData($title);
        Http::Redirect("analytics");
    }
    exit();
}

elseif (isset($_GET['download_budgetyear_chargecode'])){
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }   

    $reportMeta = ReportBudgetChargeCode::GetDefaultReportRecForDownload();
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'budget_chargecode';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportBudgetChargeCode ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}

elseif (isset($_GET['updateYearlyBudget']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (
        !isset($_GET['updateYearlyBudget']) || 
        ($yearid = $_COMPANY->decodeId($_GET['updateYearlyBudget']))<1 ||
        ($budgetYear = Budget2::GetCompanyBudgetYearDetail($yearid)) === null
    
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $budget_year_id = $budgetYear['budget_year_id'];
    $companyBudget = Budget2::GetBudget($budget_year_id);
    
    include(__DIR__ . "/views/update_budget.template.html");
}

elseif (isset($_GET['getBudgetRequests']) ){

    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $yearId = (int)($_COMPANY->decodeId($_GET['getBudgetRequests']));
    //Data Validation
    if (($budgetYear = Budget2::GetCompanyBudgetYearDetail($yearId ))=== null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    
    $srch = $_POST['search']['value'];
    $start = (int)$_POST['start'];
    $length = (int)$_POST['length'];
    $draw = (int)$_POST['draw'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? SORT_ASC : SORT_DESC;
    $orderFields = ['request_id','need_by','groupname','firstname','requested_amount','purpose','request_date'];
    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderBy =  $orderFields[$orderIndex];

    $allRows = BudgetRequest::GetBudgetRequestsData($budgetYear['budget_year_start_date'], $budgetYear['budget_year_end_date'], true, false, false);
    $allRows = Arr::OrderBy(Arr::SearchMultiArray($allRows, $srch), $orderBy, $orderDir);
    $totalrows = count($allRows);
    $requests = array_slice($allRows, $start, $length);

	$final = [];
	for($br=0;$br<count($requests);$br++){
		$request_id = $_COMPANY->encodeId($requests[$br]['request_id']);
        $budget_request = BudgetRequest::Hydrate($requests[$br]['request_id'], $requests[$br]);

	    $edit = '';

        if($_USER->canManageZoneBudget()){
            $edit .= '<div class="btn-group">'                
                .'<button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">'
                .'<span class="caret"></span>'
                .'<span class="sr-only">Toggle Dropdown</span>'
                .'Action </button>'
                .'<ul class="dropdown-menu dropdown-menu-right" role="menu">'
                .'    <li><button class="btn-list-item" onclick="approveBudget( \'req'.$br.'\',2,\''.$request_id .'\')">Approve</button></li>'
                .'    <li><button class="btn-list-item" onclick="approveBudget(\'req'.$br.'\',3,\''.$request_id .'\')">Deny</button></li>'
                .'</ul>'
            .'</div>';
        }

		$final[] = array(
            "DT_RowId" => "req".$br,
            'groupname'=>$requests[$br]['groupname'].($requests[$br]['chaptername'] ? ' > '.$requests[$br]['chaptername'] : ''),
            'requested_by'=>($requests[$br]['firstname'] ? trim($requests[$br]['firstname']." ".$requests[$br]['lastname']) : "-"),
            "request_date"=>$_USER->formatUTCDatetimeForDisplayInLocalTimezone($requests[$br]['request_date'],true,false,true),
            "needed_by"=>$_USER->formatUTCDatetimeForDisplayInLocalTimezone($requests[$br]['need_by'],true,false,true),
			'purpose'=>$requests[$br]['purpose'],
			'requested_amount'=>$_COMPANY->getCurrencySymbol().number_format($requests[$br]['requested_amount'],2),
            'description'=>$requests[$br]['description'],
            'action'=>$edit,
            'custom_fields' => $budget_request->getCustomFieldsAsArray(),
           );
    }

    $json_data = array(
                    "draw"=> intval( $draw ),
                    "recordsTotal"    => intval(count($final)),
                    "recordsFiltered" => intval($totalrows),
                    "data"            => $final
                );


	echo json_encode($json_data);
	exit();
}

elseif (isset($_GET['getAllBudgetRequests']) ){

    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $status = array('','Requested','Approved','Denied');

    $yearId = (int)($_COMPANY->decodeId($_GET['getAllBudgetRequests']));
    //Data Validation
    if (($budgetYear = Budget2::GetCompanyBudgetYearDetail($yearId ))=== null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $srch = $_POST['search']['value'];
    $start = (int)$_POST['start'];
    $length = (int)$_POST['length'];
    $draw = (int)$_POST['draw'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? SORT_ASC : SORT_DESC;
    $orderFields = ['request_id','groupname','firstname','request_date','purpose','requested_amount','amount_approved','request_status','a.firstname'];
    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderBy =  $orderFields[$orderIndex];

    // remove the chapterid condition by using false for chapter and group
    $allRows = BudgetRequest::GetBudgetRequestsData($budgetYear['budget_year_start_date'], $budgetYear['budget_year_end_date'], true, true, true);
    $allRows = Arr::OrderBy(Arr::SearchMultiArray($allRows, $srch), $orderBy, $orderDir);
    $totalrows = count($allRows);
    $requests = array_slice($allRows, $start, $length);

	$final = [];
	for($br=0;$br<count($requests);$br++){
		$request_id = $_COMPANY->encodeId($requests[$br]['request_id']);
        $budget_request = BudgetRequest::Hydrate($requests[$br]['request_id'], $requests[$br]);
        $action = '';

		$final[] = array(
            "DT_RowId" => "all".$br,
            'groupname'=>$requests[$br]['groupname'].($requests[$br]['chaptername'] ? ' > '.$requests[$br]['chaptername'] : ''),
            'requested_by'=>($requests[$br]['firstname'] ? trim($requests[$br]['firstname']." ".$requests[$br]['lastname']) : "-"),
            "request_date"=>$_USER->formatUTCDatetimeForDisplayInLocalTimezone($requests[$br]['request_date'],true,false,true),
            "needed_by"=> $_USER->formatUTCDatetimeForDisplayInLocalTimezone($requests[$br]['need_by'],true,false,true),
			'purpose'=>$requests[$br]['purpose'],
            'requested_amount'=>$_COMPANY->getCurrencySymbol().number_format($requests[$br]['requested_amount'],2),
            'amount_approved'=>($requests[$br]['request_status'] == '3' ? '-' : $_COMPANY->getCurrencySymbol().number_format($requests[$br]['amount_approved'],2)),
            'description'=>$requests[$br]['description'],
            'status'=>$status[$requests[$br]['request_status']],
            "approved_date"=>$_USER->formatUTCDatetimeForDisplayInLocalTimezone($requests[$br]['approved_date'],true,false,true),
            'approved_by'=>($requests[$br]['a_firstname'] ? trim($requests[$br]['a_firstname']." ".$requests[$br]['a_lastname']) : "-"),
            'approver_comment'=>($requests[$br]['approver_comment'] ? trim($requests[$br]['approver_comment']) : "-"),
            'action'=>$action,
            'custom_fields' => $budget_request->getCustomFieldsAsArray(),
           );
    }

    $json_data = array(
                    "draw"=> intval( $draw),
                    "recordsTotal"    => intval(count($final)),
                    "recordsFiltered" => intval($totalrows),
                    "data"            => $final
                );


	echo json_encode($json_data);
	exit();
}

elseif (isset($_GET['getChapterBudgetRequests']) ){

    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $yearId = (int)($_COMPANY->decodeId($_GET['getChapterBudgetRequests']));
    //Data Validation
    if (($budgetYear = Budget2::GetCompanyBudgetYearDetail($yearId ))=== null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }


    $srch = $_POST['search']['value'];
    $start = (int)$_POST['start'];
    $length = (int)$_POST['length'];
    $draw = (int)$_POST['draw'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? SORT_ASC : SORT_DESC;
    $orderFields = ['request_id','need_by','groupname','chaptername','firstname','requested_amount','purpose','request_date'];
    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderBy =  $orderFields[$orderIndex];

    $allRows = BudgetRequest::GetBudgetRequestsData($budgetYear['budget_year_start_date'], $budgetYear['budget_year_end_date'], false, true, false);
    $allRows = Arr::OrderBy(Arr::SearchMultiArray($allRows, $srch), $orderBy, $orderDir);
    $totalrows = count($allRows);
    $requests = array_slice($allRows, $start, $length);

	$final = [];
	for($br=0;$br<count($requests);$br++){
		$request_id = $_COMPANY->encodeId($requests[$br]['request_id']);
        $budget_request = BudgetRequest::Hydrate($requests[$br]['request_id'], $requests[$br]);
	    $edit = '';

        if($_USER->canManageZoneBudget()){
            $edit .= '<div class="btn-group">'
                .'<button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">'
                .'<span class="caret"></span>'
                .'<span class="sr-only">Toggle Dropdown</span>'
                .'Action </button>'
                .'<ul class="dropdown-menu dropdown-menu-right" role="menu">'
                .'    <li><button class="btn-list-item" onclick="approveBudget( \'req'.$br.'\',2,\''.$request_id .'\')">Approve</button></li>'
                .'    <li><button class="btn-list-item" onclick="approveBudget(\'req'.$br.'\',3,\''.$request_id .'\')">Deny</button></li>'
                .'</ul>'
            .'</div>';
        }

		$final[] = array(
            "DT_RowId" => "req".$br,
            'groupname'=>$requests[$br]['groupname'],
            'chaptername' => ($requests[$br]['chaptername'] ?? ''),
            'requested_by'=>($requests[$br]['firstname'] ? trim($requests[$br]['firstname']." ".$requests[$br]['lastname']) : "-"),
            "request_date"=>$_USER->formatUTCDatetimeForDisplayInLocalTimezone($requests[$br]['request_date'],true,false,true),
            "needed_by"=>$_USER->formatUTCDatetimeForDisplayInLocalTimezone($requests[$br]['need_by'],true,false,true),
			'purpose'=>$requests[$br]['purpose'],
			'requested_amount'=>$_COMPANY->getCurrencySymbol().number_format($requests[$br]['requested_amount'],2),
            'description'=>$requests[$br]['description'],
            'action'=>$edit,
            'custom_fields' => $budget_request->getCustomFieldsAsArray(),
           );
    }

    $json_data = array(
                    "draw"=> intval( $draw ),
                    "recordsTotal"    => intval(count($final)),
                    "recordsFiltered" => intval($totalrows),
                    "data"            => $final
                );


	echo json_encode($json_data);
	exit();
}
elseif (isset($_GET['updateGroupChapterBudget']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $retVal = ['returnCode' => 0, 'successMessage'=>'', 'errorMessage'=>''];
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['amount']) ||
        !isset($_POST['year']) ||
        !isset($_POST['groupid']) ||
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||  // Groupid is required
        !isset($_POST['chapterid']) ||  ($chapterid = $_COMPANY->decodeId($_POST['chapterid']))< 0 ||
        ($chapterid < 1) //  chapter id is required
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $budgetamount	= (float)$_POST['amount'];
    $budget_year_id = (int) $_POST['year'];
    $notifyleads =  $_POST['notifyleads'] ? 1 : 0;
    $chapterBudget = Budget2::GetBudget($budget_year_id, $groupid, $chapterid);
    $min = $chapterBudget->getTotalBudgetAllocatedToSubAccounts() + $chapterBudget->getTotalExpenses()['spent_from_allocated_budget'];
    if ($budgetamount < $min) {
        $retVal['errorMessage'] = 'Amount should be greater than or equal to '. $_USER->formatAmountForDisplay($min);
        print json_encode($retVal);
        exit();
    }

    $bid = Budget2::UpdateBudget($budgetamount, $budget_year_id, $groupid, $chapterid);
    $groupBudget = Budget2::GetBudget($budget_year_id,$groupid,0);
    if ($bid > 0) {
        $retVal['returnCode'] = 1;
        $retVal['remaining_budget'] = $groupBudget->getTotalBudgetAvailable();
        $retVal['allocated_budget'] = $groupBudget->getTotalBudgetAllocatedToSubAccounts();

        if($notifyleads){
                        // Send Budget Update notificaton to all chapter Leads
                        $group = Group::GetGroup($groupid);
                        $admins = $group->getWhoCanManageChapterBudget($chapterid);

                        if (!empty($admins )){
                            $admin_emails = implode(',',array_column($admins,'email'));
                            $chapter = $group->getChapter($chapterid);
                            $groupName = $group->val('groupname');
                            $who_updated = $_USER->getFullName();
                            $reply_addr = $group->val('replyto_email');
                            $formatedBudgetAmount = $_COMPANY->getCurrencySymbol().number_format($budgetamount,2);
                            $fiscalYear = Budget2::GetCompanyBudgetYearDetail($budget_year_id);

                            $temp = EmailHelper::ChapterBudgetUpdated($chapter['chaptername'], $groupid, $groupName, $who_updated,$fiscalYear['budget_year_title'],$formatedBudgetAmount);
                            // Set from to Zone From label if available
                            $from = $group->val('from_email_label') .' Budget Update';

                            $_COMPANY->emailSend2($from, $admin_emails, $temp['subject'], $temp['message'], $_ZONE->val('app_type'),'');

                        }
        }

    } elseif ($bid == 0) {
        $max = $groupBudget->getTotalBudgetAvailable() + $chapterBudget->getTotalBudget();
        if ($max > 0) {
            $retVal['errorMessage'] = 'Amount should be less than ' . $_USER->formatAmountForDisplay($max);
        } else {
            $retVal['errorMessage'] = 'There is not enough budget available. Please change the amount requested to fit the budget restraints.';
        }
    } else {
        $retVal['errorMessage'] = 'Internal Server Error';
    }
    print json_encode($retVal);
    exit();
}

elseif (isset($_GET['change_group_channel_status']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['gid']) || !isset($_POST['status']) ||
        ($channelid = $_COMPANY->decodeId($_GET['change_group_channel_status']))<1 ||
        ($groupid = $_COMPANY->decodeId($_POST['gid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $encodedChannelId = $_COMPANY->encodeId($channelid);
    $encodedGroupId = $_COMPANY->encodeId($groupid);
    $status = (int)$_POST['status'];

    $group->changeChannelStatus($channelid,$status);

	if($status==1){
        $btns  = '<a class="" href="newChannel?gid='.$encodedGroupId.'&cid='.$encodedChannelId.'"><i class="fa fa-edit" title="Edit"></i></a>&nbsp;';
        $btns .= '<button aria-label="Deactivate" class="btn btn-no-style deluser" onclick="changeGroupChannelStatus('."'{$encodedGroupId}','{$encodedChannelId}'".',0,this)" title="<strong>Are you sure you want to Deactivate!</strong>"><i class="fa fa-lock" title="Deactivate" aria-hidden="true"></i></button>';
    }else{ // $status = 0
        $btns  = '<a class="" href="newChannel?gid='.$encodedGroupId.'&cid='.$encodedChannelId.'"><i class="fa fa-edit" title="Edit"></i></a>&nbsp;';
        $btns .= '<button aria-label="Activate" class="btn btn-no-style deluser" onclick="changeGroupChannelStatus('."'{$encodedGroupId}','{$encodedChannelId}'".',1,this)" title="<strong>Are you sure you want to Activate!</strong>"><i class="fa fa-unlock-alt" aria-hidden="true" title="Activate"></i></button>&nbsp;';
        $btns .= '<button aria-label="delete" class="btn btn-no-style" onclick="initChannelPermanentDeleteConfirmation('."'{$encodedGroupId}','{$encodedChannelId}}'".')"><i class="fa fa-trash fa-l" title="Delete"></i></button>';
    }

	echo $btns;
}
elseif (isset($_GET['change_integration_status']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['gid']) || !isset($_POST['status']) ||
        ($intgrationid = $_COMPANY->decodeId($_POST['intgrationid']))<1 ||
        ($groupid = $_COMPANY->decodeId($_POST['gid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $status = (int)$_POST['status'];       
    $groupIntegration = GroupIntegration::GetGroupIntegration($intgrationid);
    if($status == 1){
        $groupIntegration->setActive();
    }else{
        $groupIntegration->setInactive();
    }
    

   
}
elseif (isset($_GET['delete_integration']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['gid']) || ($intgrationid = $_COMPANY->decodeId($_POST['intgrationid']))<1 ||
        ($groupid = $_COMPANY->decodeId($_POST['gid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
          
    $groupIntegration = GroupIntegration::GetGroupIntegration($intgrationid);
    if ($groupIntegration->val('isactive') === Teleskope::STATUS_INACTIVE) {
        $groupIntegration->deleteIt();
    }
   
}
elseif (isset($_GET['change_tab_status']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['gid']) || !isset($_POST['status']) ||
        ($tid = $_COMPANY->decodeId($_POST['tid']))<1 ||
        ($groupid = $_COMPANY->decodeId($_POST['gid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $status = (int)$_POST['status'];
    $group->changeTabStatus($tid,$status);   
	
}

elseif (isset($_GET['delete_tab']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['gid']) ||
        ($tid = $_COMPANY->decodeId($_POST['tid']))<1 ||
        ($groupid = $_COMPANY->decodeId($_POST['gid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
   
    $group->deleteTabs($tid);   
	
}

elseif (isset($_GET['deleteChannelLead']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['gid']) || !isset($_POST['cid']) ||
        ($leadid = $_COMPANY->decodeId($_GET['deleteChannelLead']))<1 ||
        ($channelid = $_COMPANY->decodeId($_POST['cid']))<1 ||
        ($groupid = $_COMPANY->decodeId($_POST['gid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

	if($group->removeChannelLead($channelid,$leadid)){
		echo 1;
	}else{
		echo "Something went wrong. Please try again.!";
	}
}

elseif (isset($_GET['createNewGroup']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $res_group = null;
    $groupid=$_COMPANY->decodeId($_GET['createNewGroup']);
    if ($groupid){
        $res_group=$db->get("SELECT * FROM `groups` WHERE `groupid`='{$groupid}' AND `companyid`='{$_COMPANY->id()}'");
    }


    $overlaycolor 		= 	Sanitizer::SanitizeColor($_POST['overlaycolor']);
    $from_email_label	= 	$_POST['from_email_label'];
    $enc_regions = !empty($_POST['regionid']) ? $_POST['regionid'] : [];
    $overlaycolor2 		= 	Sanitizer::SanitizeColor($_POST['overlaycolor2']);

    $show_overlay_logo =  1;
    if (isset($_POST['show_overlay_logo'])){
        $show_overlay_logo = (int)$_POST['show_overlay_logo'];
    }

    $show_app_overlay_logo =  1;
    if (isset($_POST['show_app_overlay_logo'])){
        $show_app_overlay_logo = (int)$_POST['show_app_overlay_logo'];
    }

    $groupname = 	Sanitizer::SanitizeGroupName($_POST['groupname']);
    $groupname_short = 	substr(Sanitizer::SanitizeGroupName($_POST['groupname_short']),0,13);

    if(!empty($enc_regions)){
        $dec_regions = array();
        foreach ($enc_regions as $r){
            $dec_regions[] = $_COMPANY->decodeId($r);
        }
        $regionid = implode(',',$dec_regions);
    }else{
        $regionid = '0';
    }
    $groupicon = "";
    $error ='';
    $check = array();

    if ($groupid>0){
        $getRegions = $db->get("SELECT IFNULL(GROUP_CONCAT(DISTINCT  `regionid`),0) AS `regionids` FROM `companybranches` WHERE `branchid` IN (SELECT IFNULL(GROUP_CONCAT(`branchids`),0) AS `branchids` FROM `chapters` WHERE chapters.companyid='{$_COMPANY->id()}' AND ( chapters.zoneid='{$_ZONE->id()}' AND `groupid`='{$groupid}'));");
        $chapterRegions = array_filter(explode(',',$getRegions[0]['regionids']));
        $groupRegions = array_filter(explode(',',$regionid));
        $difIds = implode(',',array_diff($chapterRegions,$groupRegions));
        $regionids = $difIds ? $difIds : 0;
        $getRegionNames = $db->get("SELECT IFNULL(GROUP_CONCAT(`region`),'') as regions FROM `regions` WHERE `regionid` IN (".$regionids.") ");

        if ($getRegionNames[0]['regions'] !=''){
            $error  = $getRegionNames[0]['regions'] . " region are used by ".$_COMPANY->getAppCustomization()['chapter']["name-short-plural"]." of this ERG. Please remove these regions from ". $_COMPANY->getAppCustomization()['chapter']["name-short"]." first and then try again";
            AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
        } else { // Check Group leads
            $getRegions = $db->get("SELECT IFNULL(GROUP_CONCAT(`regionids`),0) as regionids  FROM `groupleads` WHERE `groupid`='".$groupid."' and `regionids`!='0' " );		
            $groupleadRegions = array_filter(explode(',',$getRegions[0]['regionids']));
            $groupRegions = array_filter(explode(',',$regionid));
            $difIds = implode(',',array_diff($groupleadRegions,$groupRegions));
            $regionids = $difIds ? $difIds : 0;
            $getRegionNames = $db->get("SELECT IFNULL(GROUP_CONCAT(`region`),'') as regions FROM `regions` WHERE `regionid` IN (".$regionids.") ");
            if ($getRegionNames[0]['regions'] !=''){
                $error  = $getRegionNames[0]['regions'] . " region are used by Groupleads of this ERG. Please remove these regions from Groupleads first and then try again";
                AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
            }
        }
    }

        $groupicon = "";
        if (!empty($_FILES['groupicon']['name'])){
            $image_info = getimagesize($_FILES["groupicon"]["tmp_name"]);
            $image_width = $image_info[0];
            $image_height = $image_info[1];
            if ($image_width >150 || $image_height > 100){
                $error = 'Maximum icon size allowed 150px wide x 100px high.';
                AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
            }else{
                $file 	    =	basename($_FILES['groupicon']['name']);
                $tmp 		=	$_FILES['groupicon']['tmp_name'];
                $ext		=	$db->getExtension($file);
                $valid_formats = array("jpg", "png","jpeg","PNG","JPG","JPEG");

                if (in_array($ext,$valid_formats)){
                    $actual_name ="groupicon_".teleskope_uuid().".".$ext;

                    $groupicon = $_COMPANY->saveFile($tmp,$actual_name,'GROUP');

                    if (empty($groupicon)) {
                        $error = 'Icon uploading error! Please try again!';
                        AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
                    }
                }else{
                    $error = 'Only .jpg,.jpeg,.png files are allowed!';
                    AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
                }
            }
        }

        $coverphoto = "";
        if (!empty($_FILES['coverphoto']['name'])){
            $image_info = getimagesize($_FILES["coverphoto"]["tmp_name"]);
            $image_width = $image_info[0];
            $image_height = $image_info[1];
            if ($image_width >1000 || $image_height > 188){
                $error = 'Maximum size allowed 1000px wide x 188px high.';
                AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
            }else{
                $file 	    =	basename($_FILES['coverphoto']['name']);
                $tmp 		=	$_FILES['coverphoto']['tmp_name'];
                $ext		=	$db->getExtension($file);
                $valid_formats = array("jpg", "png","jpeg","PNG","JPG","JPEG");

                if (in_array($ext,$valid_formats)){
                    $actual_name ="group_".teleskope_uuid().".".$ext;

                    $coverphoto = $_COMPANY->saveFile($tmp,$actual_name,'GROUP');

                    if (empty($coverphoto)) {
                        $error = 'Uploading error! Please try again!';
                        AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
                    }
                }else{
                    $error = 'Only .jpg,.jpeg,.png files are allowed!';
                    AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
                }
            }
        }

        $sliderphoto = "";
        if (!empty($_FILES['sliderphoto']['name'])){
            $image_info = getimagesize($_FILES["sliderphoto"]["tmp_name"]);
            $image_width = $image_info[0];
            $image_height = $image_info[1];
            if ($image_width >300 || $image_height > 175){
                $error = 'Maximum size allowed 300px wide x 175px high.';
                AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
            }else {
                $file = basename($_FILES['sliderphoto']['name']);
                $tmp = $_FILES['sliderphoto']['tmp_name'];
                $ext = $db->getExtension($file);
                $valid_formats = array("jpg", "png", "jpeg", "PNG", "JPG", "JPEG");

                if (in_array($ext, $valid_formats)) {
                    $actual_name = "group_slider_" . teleskope_uuid() . "." . $ext;

                    $sliderphoto = $_COMPANY->saveFile($tmp, $actual_name,'GROUP');

                    if (empty($sliderphoto)) {
                        $error = 'Uploading error! Please try again!';
                        AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
                    }
                } else {
                    $error = 'Only .jpg,.jpeg,.png files are allowed!';
                    AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
                }
            }
        }

        $app_sliderphoto = "";
        if (!empty($_FILES['app_sliderphoto']['name'])){
            $image_info = getimagesize($_FILES["app_sliderphoto"]["tmp_name"]);
            $image_width = $image_info[0];
            $image_height = $image_info[1];
            if ($image_width >200 || $image_height > 175){
                $error = 'Maximum size allowed 200px wide x 175px high.';
                AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
            }else {
                $file = basename($_FILES['app_sliderphoto']['name']);
                $tmp = $_FILES['app_sliderphoto']['tmp_name'];
                $ext = $db->getExtension($file);
                $valid_formats = array("jpg", "png", "jpeg", "PNG", "JPG", "JPEG");

                if (in_array($ext, $valid_formats)) {
                    $actual_name = "group_app_sliderphoto_" . teleskope_uuid() . "." . $ext;

                    $app_sliderphoto = $_COMPANY->saveFile($tmp, $actual_name,'GROUP');

                    if (empty($app_sliderphoto)) {
                        $error = 'App group image uploading error! Please try again!';
                        AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
                    }
                } else {
                    $error = 'Only .jpg,.jpeg,.png files are allowed!';
                    AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
                }
            }
        }

        $app_coverphoto = "";
        if (!empty($_FILES['app_coverphoto']['name'])){
            $image_info = getimagesize($_FILES["app_coverphoto"]["tmp_name"]);
            $image_width = $image_info[0];
            $image_height = $image_info[1];
            if ($image_width >1000 || $image_height > 188){
                $error = 'Maximum size allowed 1000px wide x 188px high.';
                AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
            }else {
                $file = basename($_FILES['app_coverphoto']['name']);
                $tmp = $_FILES['app_coverphoto']['tmp_name'];
                $ext = $db->getExtension($file);
                $valid_formats = array("jpg", "png", "jpeg", "PNG", "JPG", "JPEG");

                if (in_array($ext, $valid_formats)) {
                    $actual_name = "group_app_coverphoto_" . teleskope_uuid() . "." . $ext;

                    $app_coverphoto = $_COMPANY->saveFile($tmp, $actual_name,'GROUP');

                    if (empty($app_coverphoto)) {
                        $error = 'App group cover image uploading error! Please try again!';
                        AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
                    }
                } else {
                    $error = 'Only .jpg,.jpeg,.png files are allowed!';
                    AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
                }
            }
        }

        $replyto_email = '';
        if (isset($_POST['replyto_email'])){
            $replyto_email = $_POST['replyto_email'];
        }

        $tagids = '';
        $tagidsArray = array();
        if (isset($_POST['group_tags'])){
            $tagidsArray = Group::GetOrCreateTagidsArrayByTag($_POST['group_tags']);
        }
        if (!empty($tagidsArray)){
            $tagids = implode(',',$tagidsArray);
        }
       
        if($groupid==0){
            $group_category = 'ERG';
            // if (isset($_POST['group_category'])) {
            //     $group_category = $_POST['group_category'] === 'IG' ? 'IG' : 'ERG';
            // }
            $group_category_id = 0;
            if(isset($_POST['group_category_label']) && $_POST['group_category_label'] != 0){
                $group_category_id = $_COMPANY->decodeId($_POST['group_category_label']);
            }
          
            if ($_ZONE->val('app_type') == 'talentpeak'){
                $group_type = Group::GROUP_TYPE_REQUEST_TO_JOIN;
            } elseif($_ZONE->val('app_type') == 'officeraven'){
                $group_type = Group::GROUP_TYPE_MEMBERSHIP_DISABLED;
            } elseif($_ZONE->val('app_type') == 'peoplehero'){
                $group_type = Group::GROUP_TYPE_MEMBERSHIP_DISABLED;
            } else {
                $group_type = Group::GROUP_TYPE_OPEN_MEMBERSHIP;
            }
            $permatag = '';
            $aboutgroup = 'About '.$groupname.' ...';
            $groupid = Group::CreateGroup( $groupname_short, $groupname,  $aboutgroup,  $coverphoto, $overlaycolor, $from_email_label, $regionid, $groupicon, $permatag, $overlaycolor2, $sliderphoto, $show_overlay_logo, $group_category,$replyto_email,$group_type,$app_sliderphoto,$app_coverphoto,$show_app_overlay_logo,$tagids,$group_category_id);
            $_SESSION['added'] = time();
            AjaxResponse::SuccessAndExit_STRING(1, '', $_COMPANY->getAppCustomization()['group']["name-short"]." created successfully", 'Success');
        }else{
            if(empty($groupicon)){
                $groupicon = $res_group[0]['groupicon'];
            } else {
                $_COMPANY->deleteFile($res_group[0]['groupicon']); //Delete old file if it exists
            }
            if(empty($coverphoto)){
                $coverphoto = $res_group[0]['coverphoto'];
            } else {
                $_COMPANY->deleteFile($res_group[0]['coverphoto']); //Delete old file if it exists
            }

            if(empty($sliderphoto)){
                $sliderphoto = $res_group[0]['sliderphoto'];
            } else {
                if ($res_group[0]['sliderphoto']){
                    $_COMPANY->deleteFile($res_group[0]['sliderphoto']); //Delete old file if it exists
                }
            }
            if(empty($app_coverphoto)){
                $app_coverphoto = $res_group[0]['app_coverphoto']?:'';
            } else {
                if ($res_group[0]['app_coverphoto']){
                    $_COMPANY->deleteFile($res_group[0]['app_coverphoto']); //Delete old file if it exists
                }
            }
            if(empty($app_sliderphoto)){
                $app_sliderphoto = $res_group[0]['app_sliderphoto']?:'';
            } else {
                if ($res_group[0]['app_sliderphoto']){
                    $_COMPANY->deleteFile($res_group[0]['app_sliderphoto']); //Delete old file if it exists
                }
            }
            $permatag = $res_group[0]['permatag'];
            // Group category label
            $group_category_id = 0;
            if(isset($_POST['group_category_label']) && !empty($_POST['group_category_label'])){
                $group_category_id = $_COMPANY->decodeId($_POST['group_category_label']);
            }

            $update = Group::UpdateGroup($groupid, $groupname_short, $groupname, $coverphoto, $overlaycolor, $from_email_label, $regionid, $groupicon, $permatag, $overlaycolor2, $sliderphoto, $show_overlay_logo, $replyto_email, $group_category_id, $app_sliderphoto, $app_coverphoto, $show_app_overlay_logo, $tagids);
            Group::DeleteUnusedTags(); // Remove all unused tags
            $_SESSION['updated'] = time();
            AjaxResponse::SuccessAndExit_STRING(1, '', $_COMPANY->getAppCustomization()['group']["name-short"]." updated successfully", 'Success');
        }
}

// Add Update Disclaimer Function...
elseif (isset($_GET['addUpdateNewDisclaimer']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $disclaimerid = 0;
    $editDisclaimer = array();
    if (isset($_POST['disclaimerid'])){        
        $disclaimerid = $_COMPANY->decodeId($_POST['disclaimerid']);
        $editDisclaimer = Disclaimer::GetDisclaimerById($disclaimerid);             
    }

    $update_consent_version = false;
    if (isset($_POST['update_consent_version'])){
        $update_consent_version = filter_var($_POST['update_consent_version'], FILTER_VALIDATE_BOOLEAN);
    }
    $disclaimer_name = $_POST['disclaimer_name'];
    $invocation_type = in_array($_POST['invocation_type'],Disclaimer::DISCLAIMER_INVOCATION_TYPE ) ? $_POST['invocation_type'] : 'TRIGGER';
    $disclaimerHooks = Disclaimer::DISCLAIMER_HOOK_TRIGGERS;  
    $allowedLanguages = $_COMPANY->getValidLanguages();
    
    if (empty($_POST['hookid']) && empty($_POST['link_type'])){
        if ($invocation_type == 'TRIGGER' ) {
            $errorMssage = gettext('Please choose a trigger');
        } else {
            $errorMssage = gettext('Please choose a link type');
        }
        AjaxResponse::SuccessAndExit_STRING(0, '', $errorMssage, 'Error!');
    }

    $hookid = $invocation_type == 'TRIGGER' ? $_COMPANY->decodeId($_POST['hookid']) : $_COMPANY->decodeId($_POST['link_type']);

    $consent_required = 0;
    if(isset($_POST['consent_required'])){
        $consent_required = 1;

        if (empty($_POST['consent_type'])){
            AjaxResponse::SuccessAndExit_STRING(0, '', "Please select a consent tracking type.", 'Error!');
        }

    }

    $consent_type = 'checkbox'; // Default value
    if(isset($_POST['consent_type'])){
        $consent_type = $_POST['consent_type'] == 'text' ? 'text' : 'checkbox';
    }

    $disclaimer = array();  
    $usedLanguages = array();  
    for ($i=0;$i<count($_POST['default_language']);$i++){ 
        if (empty($_POST['default_language'][$i])){
            AjaxResponse::SuccessAndExit_STRING(0, '', "Please select a language.", 'Error!');
        }

        $lang = $_POST['default_language'][$i];
        if (in_array($lang,$usedLanguages)){
            AjaxResponse::SuccessAndExit_STRING(0, '', "Disclaimer language (".$allowedLanguages[$lang].") should be unique.", 'Error!');
        }
        array_push($usedLanguages,$lang);
        if(empty(trim($_POST['title'][$i]))){
            AjaxResponse::SuccessAndExit_STRING(0, '', "Title field is required.", 'Error!');
        }
        $disclaimertitle =  $_POST['title'][$i];

        if(empty($_POST['disclaimer'][$i])){
            AjaxResponse::SuccessAndExit_STRING(0, '', "Disclaimer field is required.", 'Error!');
        }
        $disclaimerText =  $_POST['disclaimer'][$i];

        $consent_input_value = '';
        if ($consent_type == 'text'){
            if(empty(trim($_POST['consent_input_value'][$i]))) {
                AjaxResponse::SuccessAndExit_STRING(0, '', "Consent text is required.", 'Error!');
            }
            $consent_input_value =  $_POST['consent_input_value'][$i];
        }
        
        $disclaimer[$lang]  =  array('title'=>$disclaimertitle,'disclaimer'=>$disclaimerText,'consent_input_value'=>$consent_input_value);
    }

    $enabled_by_default = 0;
    if (isset($_POST['enabled_by_default']) && $_POST['enabled_by_default'] == "on") {
        $enabled_by_default = 1;
    }

    if($disclaimerid >0){      
        $update = $editDisclaimer->updateDisclaimer($disclaimer_name, $disclaimer,$consent_required,$consent_type,$enabled_by_default,$update_consent_version);
        if($update){
            AjaxResponse::SuccessAndExit_STRING(1, '', "Disclaimer updated successfully.", 'Success');
        }else{
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Your request to update disclaimer failed. Please try again later.'), gettext('Error!'));
        }

    }else{
        
        if (Disclaimer::IsHookValidType($hookid)) {
            $insert = Disclaimer::CreateANewDisclaimer($disclaimer_name, $invocation_type, $hookid,$disclaimer,$consent_required,$consent_type,$enabled_by_default);
            if($insert){
                AjaxResponse::SuccessAndExit_STRING(1, '', "Disclaimer added successfully.", 'Success');
            }else{
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Your request to add disclaimer failed. Please try again later.'), gettext('Error!'));
            }
        }else{
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('No trigger hooks found. Please try again later...'), gettext('Error!'));
        }

    }    

}// End Add Update Disclaimer Function.

elseif (isset($_GET['deleteGroupIcon']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (
        ($groupid=$_COMPANY->decodeId($_GET['deleteGroupIcon']))<1 ||
        ($group = Group::GetGroup($groupid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if(!empty($group->val('groupicon'))){
        $_COMPANY->deleteFile($group->val('groupicon')); //Delete old file if it exists
    }

    $group->updateGroupMedia( '', $group->val('sliderphoto'), $group->val('coverphoto'), $group->val('app_sliderphoto'), $group->val('app_coverphoto'));

    $_SESSION['updated'] = time();
    echo 1;
    exit();
}

elseif (isset($_GET['deleteSliderPhoto']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (
        ($groupid=$_COMPANY->decodeId($_GET['deleteSliderPhoto']))<1 ||
        ($group = Group::GetGroup($groupid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if(!empty($group->val('sliderphoto'))){
        $_COMPANY->deleteFile($group->val('sliderphoto')); //Delete old file if it exists
    }

    $group->updateGroupMedia($group->val('groupicon'), '', $group->val('coverphoto'), $group->val('app_sliderphoto'), $group->val('app_coverphoto'));
    $_SESSION['updated'] = time();
    echo 1;
    exit();
}

elseif (isset($_GET['deleteCoverPhoto']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }


    //Data Validation
    if (
        ($groupid=$_COMPANY->decodeId($_GET['deleteCoverPhoto']))<1 ||
        ($group = Group::GetGroup($groupid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if(!empty($group->val('coverphoto'))){
        $_COMPANY->deleteFile($group->val('coverphoto')); //Delete old file if it exists
    }

    $group->updateGroupMedia($group->val('groupicon'), $group->val('sliderphoto'), '', $group->val('app_sliderphoto'), $group->val('app_coverphoto'));

    $_SESSION['updated'] = time();
    echo 1;
    exit();
}

elseif (isset($_GET['activateDeactivateEventCustomField']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['custom_field_id']) || ($custom_field_id = $_COMPANY->decodeId($_POST['custom_field_id']))<1 || !isset($_POST['status'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $status = (int)$_POST['status'];

    if ($status === 1 || $status === 2) {
        if ($status === 2) { // Check if "Visible only if" rule is set based on this field
            $fieldUsedForVisibleLogic = Event::GetFieldsUsingSelectedFieldsForVisiableIfLogic($custom_field_id);
            if (!empty($fieldUsedForVisibleLogic)){
                AjaxResponse::SuccessAndExit_STRING(0, '', "To disable this custom field, first remove/change 'Visible only if' logic on ".$fieldUsedForVisibleLogic." custom field(s).", 'Error');
            }
        }
        Event::ActivateDeactivateEventCustomField($custom_field_id, $status);
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Status updated successfully'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Wrong action selected'), gettext('Error'));
    }
}
elseif (isset($_GET['deleteEventCustomField']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['custom_field_id']) || ($custom_field_id = $_COMPANY->decodeId($_POST['custom_field_id']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $topictype = $_POST['topictype'];
    $topic_class = Teleskope::TOPIC_TYPE_CLASS_MAP[$topictype];

    $deleteCustomField = call_user_func([$topic_class, 'DeleteEventCustomField'], $custom_field_id);
    if($deleteCustomField){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Deleted successfully'), gettext('Success'));
    } else {
        switch ($topictype) {
            case 'EVT':
                $error = gettext('Custom field is in use. Please remove custom field from event and auto approval configuration to deactivate');
                break;

            case 'EXP':
                $error = gettext('Custom field is in use. Please remove custom field from any existing expense entries');
                break;

            case 'BRQ':
                $error = gettext('Custom field is in use. Please remove custom field from any existing budget requests');
                break;

            case 'EVTSPK':
                $error = gettext('Custom field is in use. Please remove custom field from any existing event speakers');
                break;

            case 'ORG':
                $error = gettext('Custom field is in use. Please remove custom field from any existing Organization');
                break;    

            case 'REC':
                $error = gettext('Custom field is in use. Please remove custom field from any existing recognitions');
                break;

        }
        AjaxResponse::SuccessAndExit_STRING(0, '', $error, gettext('Error'));
    }
}

elseif (isset($_GET['updateprivacyPolicy']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $privacy_link_type = $_POST['privacy_link_type'];

    if ($privacy_link_type == 1){
        $customer_privacy_link = $_POST['link'];
    } else {
        if (!empty($_FILES['link_file']['name'])) {
            $file = basename($_FILES['link_file']['name']);
            $tmp = $_FILES['link_file']['tmp_name'];
            $ext = $db->getExtension($file);
            $actual_name = "privacy_link_link_" . teleskope_uuid() . "." . $ext;

           $customer_privacy_link = $_COMPANY->saveFile($tmp, $actual_name, 'COMPANY');
            if (empty($customer_privacy_link)) {
                echo 1;
                exit();
            }
        } else {
            echo 2;
            exit();
        }
    }
    // Update customer privacy link
    $_COMPANY->updateCompanyPrivacyPolicy($customer_privacy_link);
    // Next remove the old file if it was stored in Teleskope S3
    if (strpos($_COMPANY->val('customer_privacy_link'), ('https://'.S3_BUCKET)) === 0) {
        $_COMPANY->deleteFile($_COMPANY->val('customer_privacy_link'));
    }
    // Reload company by invalidating cache and reloading it from database
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
    echo htmlspecialchars($customer_privacy_link);
    exit();
}

elseif (isset($_GET['updateTOS']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $tos_link_type = $_POST['tos_link_type'];

    if ($tos_link_type == 1){
        $tos_link = $_POST['link'];
    } else {
        if (!empty($_FILES['link_file']['name'])) {
            $file = basename($_FILES['link_file']['name']);
            $tmp = $_FILES['link_file']['tmp_name'];
            $ext = $db->getExtension($file);
            $actual_name = "tos_link_" . teleskope_uuid() . "." . $ext;

            $tos_link = $_COMPANY->saveFile($tmp, $actual_name, 'COMPANY');
            if (empty($tos_link)) {
                echo 1;
                exit();
            }
        } else {
            echo 2;
            exit();
        }
    }
    // Update customer privacy link
    $_COMPANY->updateCompanyTermsOfService($tos_link);
    // Next remove the old file if it was stored in Teleskope S3
    if (strpos($_COMPANY->val('customer_tos_link'), ('https://'.S3_BUCKET)) === 0) {
        $_COMPANY->deleteFile($_COMPANY->val('customer_tos_link'));
    }
    // Reload company by invalidating cache and reloading it from database
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
    echo htmlspecialchars($tos_link);
    exit();
}

elseif (isset($_GET['deleteChargeCode']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['deleteChargeCode']) || ($id = $_COMPANY->decodeId($_GET['deleteChargeCode']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    Budget2::DeleteBudgetChargeCodes($id);
	print(1);
}

elseif (isset($_GET['deleteFooterLink']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (!isset($_GET['deleteFooterLink']) || ($link_id = $_COMPANY->decodeId($_GET['deleteFooterLink']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // check for soft delete or permanent delete
    if(!empty($_POST['deleteStatus']) && $_POST['deleteStatus']==100){
        $_COMPANY->updateFooterLinkStatus($link_id,100);
    }else{
        $_COMPANY->deleteFooterLink($link_id);
    }
    
	print(1);
}
elseif (isset($_GET['activateDeactiveHotLink']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['activateDeactiveHotLink']) || ($link_id = $_COMPANY->decodeId($_GET['activateDeactiveHotLink']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $status = (int)($_POST['status']) === 1 ? 1 : 0;

    echo $_COMPANY->updateHotLinkStatus($link_id, $status);
}
elseif (isset($_GET['activateDeactiveFooterLink']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['activateDeactiveFooterLink']) || ($link_id = $_COMPANY->decodeId($_GET['activateDeactiveFooterLink']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $status = (int)($_POST['status']) === 1;

    echo $_COMPANY->updateFooterLinkStatus($link_id,$status);
}
elseif (isset($_GET['updateChapterLeadsPriority']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['prioritylist']) || empty($_POST['prioritylist'])
        || !isset($_POST['gid']) || ($gid=$_COMPANY->decodeId($_POST['gid'])) < 1
        || ($group = Group::GetGroup($gid)) === NULL
        || ($chapterid=$_COMPANY->decodeId($_POST['chapterid'])) < 1
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $enc_priority_array = explode(',',$_POST['prioritylist']);
    $dec_priority_array = array();

    foreach ($enc_priority_array as $enc_id) {
        $dec_id = $_COMPANY->decodeId($enc_id);
        if ($dec_id < 1) { //validation 2, for each priority item
            header(HTTP_BAD_REQUEST);
            exit();
        }
        $dec_priority_array[] = $dec_id;
    }
    $priority = implode(',',$dec_priority_array);
    $group->updateChapterleadsPriorityOrder($chapterid,$priority);
	echo 1;
}

elseif (isset($_GET['updateChannelLeadsPriority']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['prioritylist']) || empty($_POST['prioritylist'])
        || !isset($_POST['gid']) || ($gid=$_COMPANY->decodeId($_POST['gid'])) < 1
        || ($group = Group::GetGroup($gid)) === NULL
        || ($channelid=$_COMPANY->decodeId($_POST['channelid'])) < 1
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $enc_priority_array = explode(',',$_POST['prioritylist']);
    $dec_priority_array = array();

    foreach ($enc_priority_array as $enc_id) {
        $dec_id = $_COMPANY->decodeId($enc_id);
        if ($dec_id < 1) { //validation 2, for each priority item
            header(HTTP_BAD_REQUEST);
            exit();
        }
        $dec_priority_array[] = $dec_id;
    }
    $priority = implode(',',$dec_priority_array);
    $group->updateChannelleadsPriorityOrder($channelid,$priority);
	echo 1;
}

elseif (isset($_GET['download_event_report']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportEvents::GetDefaultReportRecForDownload();
    $Fields = $reportMeta['Fields'];

    $groupids = array();

    if(!empty($_POST['s_group'])){
        foreach($_POST['s_group'] as $g){
            array_push($groupids,$_COMPANY->decodeId($g));
        }
    }

    $options = array();

    if (!empty($_POST['startDate'])) {
        $options['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
    }
    
    if (!empty($_POST['endDate'])) {
        $options['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
    }


    foreach ($Fields as $k => $v) {
        if (!isset($_POST[$k])) {
            unset($Fields[$k]);
        }
    }
    $reportAction = $_POST['reportAction'];
    $customFields = array();
    if(isset($_POST['includeCustomFields'])){
        $options['includeCustomFields'] = $_POST['includeCustomFields'];
    }

    if (!$_COMPANY->getAppCustomization()['chapter']['enabled'] || !isset($_POST['chaptername'])) {
        unset($Fields['chaptername']);
        unset($Fields['enc_chapterid']);
    }

    if (!$_COMPANY->getAppCustomization()['channel']['enabled'] || !isset($_POST['channelname'])) {
        unset($Fields['channelname']);
        unset($Fields['enc_channelid']);
    }

    if (!isset($_POST['groupname'])) {
        unset($Fields['groupname']);
        unset($Fields['enc_groupid']);
    }

    $meta = array(
        'Fields' => $Fields,
        'Options' => $options,
        'Filters' => array(
            'groupids' => $groupids // List of groupids, or empty for all groups
        )
    );

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'event';
    $record['reportmeta'] = json_encode($meta);

    $report = new ReportEvents($_COMPANY->id(),$record);
    if ( $reportAction == 'download'){
        $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
        #else echo false
        echo false;
    } else {
        $title = gettext("Events Report Analytics");
        $_SESSION['analytics_data'] = $report->generateAnalyticData($title);
        Http::Redirect("analytics");
    }
    exit();
}

elseif (isset($_GET['download_rsvp_report']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    global $_ZONE;

    $reportMeta = ReportEventRSVP::GetDefaultReportRecForDownload();

    $groupids = array();

    if(!empty($_POST['s_group'])){
        foreach($_POST['s_group'] as $g){
            array_push($groupids,$_COMPANY->decodeId($g));
        }
    }

    $reportMeta['Filters']['groupids'] = $groupids;

    $options = array();

    if (!empty($_POST['startDate'])) {
        $options['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
    }
    
    if (!empty($_POST['endDate'])) {
        $options['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
    }

    $customFields = array();
    if(isset($_POST['includeCustomFields'])){
        $options['includeCustomFields'] = $_POST['includeCustomFields'];
    }

    $reportMeta['Options'] = $options;

    if (!isset($_POST['chaptername'])) {
        unset($reportMeta['Fields']['chaptername']);
        unset($reportMeta['Fields']['enc_chapterid']);
    }

    if (!isset($_POST['channelname'])) {
        unset($reportMeta['Fields']['channelname']);
        unset($reportMeta['Fields']['enc_channelid']);
    }

    if (!isset($_POST['groupname'])) {
        unset($reportMeta['Fields']['groupname']);
        unset($reportMeta['Fields']['enc_groupid']);
    }

    updateReportMetaFieldsFromPOST($reportMeta);

    $reportAction = $_POST['reportAction'];
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'event_rsvp';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportEventRSVP ($_COMPANY->id(),$record);
    if ($reportAction == 'download'){
        $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
        #else echo false
        echo false;
    } else {
        $title = gettext("Event RSVP Report Analytics");
        $_SESSION['analytics_data'] = $report->generateAnalyticData($title);
        Http::Redirect("analytics");
    }
    exit();
}
elseif (isset($_GET['downloadLoginReport']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->isAdmin()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportLogins::GetDefaultReportRecForDownload();
    $Fields = $reportMeta['Fields'];

    $options = array();
    $filters = array();

    if (!empty($_POST['startDate'])) {
        $options['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
    }
    
    if (!empty($_POST['endDate'])) {
        $options['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
    }

    $filters['application'] = $_ZONE->val('app_type');
    if(!empty($_POST['loginReportType'])) {
        if ($_POST['loginReportType'] == 'native'){
            $filters['application'] = 'native';
        } elseif  ($_POST['loginReportType'] == 'email'){
            $filters['application'] = 'email';
        }
    }

    foreach ($Fields as $k => $v) {
        if (!isset($_POST[$k])) {
            unset($Fields[$k]);
        }
    }

    $customFields = array();

    $meta = array(
        'Fields' => $Fields,
        'Options' => $options,
        'Filters' => $filters
    );

    $reportAction = $_POST['reportAction'];
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'login';
    $record['reportmeta'] = json_encode($meta);

    $report = new ReportLogins($_COMPANY->id(),$record);
    if ($reportAction == 'download'){
        $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
        #else echo false
        echo false;
    } else {
        $title = gettext("Login Reports Analytics");
        $_SESSION['analytics_data'] = $report->generateAnalyticData($title);
        Http::Redirect("analytics");
    }
    exit();

}

elseif (isset($_GET['deleteReportMeta']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    $reportid = $_GET['deleteReportMeta'];
    Report::DeleteReportRec($reportid);
    $_SESSION['updated'] = time();
    $_SESSION['msg'] ="Report meta deleted successfully!";
    Http::Redirect("manage_reports");
}

elseif (isset($_GET['updateZoneEmailSetting']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings() || $_COMPANY->val('in_maintenance') < 2) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $validator = new Rakit\Validation\Validator;
    $validation = $validator->validate($_POST, [
        'email_settings' => 'required|integer|min:0|max:2',
        'email_from_label' => 'regex:/^[A-Za-z0-9 \-_]+$/u|min:3|max:64',
        'email_from' => 'email|max:127',
        'email_reply_to' => 'email|max:127',
    ]);
    if ($validation->fails()) {
        // handling errors
        $errors = array_values($validation->errors()->firstOfAll());
        // Return error message
        $error = 'Error:<br>'.implode('<br>', $errors).'<br>';
        echo $error;
        exit();
    }

    $retVal = $_COMPANY->updateCompanyZoneEmailSetting($_POST['email_settings'],$_POST['email_from_label'],$_POST['email_from'],$_POST['email_reply_to']);
    if ($retVal) {
        // Reload company by invalidating cache and reloading it from database
        $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
    }
    echo $retVal;
    exit();
}

elseif (isset($_GET['getAnnouncements']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->isAdmin()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $input = array('search'=>raw2clean($_POST['search']['value']),'start'=>(int)$_POST['start'],'length'=>(int)$_POST['length'],'draw'=>(int)$_POST['draw']);

    $orderFields = ['post.title','`groups`.groupname','chapters.chaptername','group_channels.channelname','post.postedon','users.firstname'];

    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';

    $search = "";
    if ($input['search']){
        $search = " AND (post.title LIKE '%".$input['search']."%'  OR users.firstname LIKE '%".$input['search']."%' OR users.lastname LIKE '%".$input['search']."%' OR users.email LIKE '%".$input['search']."%' OR groups.groupname LIKE '%".$input['search']."%'  OR chapters.chaptername LIKE '%".$input['search']."%'  OR group_channels.channelname LIKE '%".$input['search']."%' )";
    }
    $totalrows = $db->ro_get("SELECT count(1) as totalrows FROM post LEFT JOIN users ON post.userid=users.userid LEFT JOIN `groups` ON post.groupid=`groups`.groupid LEFT JOIN chapters ON post.chapterid = chapters.chapterid LEFT JOIN group_channels ON post.channelid=group_channels.channelid WHERE post.companyid={$_COMPANY->id()} AND (post.zoneid={$_ZONE->id()} AND post.isactive>0) {$search} ")[0]['totalrows'];
    $data = $db->ro_get("SELECT post.*,users.firstname,users.lastname,users.picture,IFNULL(`groups`.groupname,'Global') as groupname,chapters.chaptername,group_channels.channelname FROM post LEFT JOIN users  ON post.userid=users.userid LEFT JOIN `groups` ON post.groupid=`groups`.groupid LEFT JOIN chapters ON post.chapterid = chapters.chapterid LEFT JOIN group_channels ON post.channelid=group_channels.channelid WHERE post.`companyid`={$_COMPANY->id()} AND (post.`zoneid`={$_ZONE->id()} AND post.isactive>0) {$search}  ORDER BY {$orderFields[$orderIndex]} {$orderDir} limit {$input['start']}, {$input['length']}");

    $i=1;
    $final = [];
    foreach($data as $row){
        $encodedId = $_COMPANY->encodeId($row['postid']);

        if($row['firstname']==""){
            $name = $row['email']??'';
        }else{
            $name = trim($row['firstname']." ".$row['lastname']);
        }
        $profilepic = User::BuildProfilePictureImgTag($row['firstname'],$row['lastname'], $row['picture'],'demo2', 'User Profile Picture');
        $creator = $profilepic."<br><strong>".$name."</strong>";

        if ($row['isactive'] == 2 || $row['isactive'] == 3) {
            $announcement  = '<span style="text-align:justify;color:red;">'.$row['title'].'&nbsp;<sup>[draft]</sup></span>';
        } else {
            $announcement  =  '<span style="text-align:justify;">'.$row['title'].'</span>';
        }

        $editLink = $_COMPANY->getAppURL($_ZONE->val('app_type')).'viewpost?id='.$encodedId;
        $actionButton = '<a class="deluser" title="<strong>Open application?</strong>" onclick="window.open(\''.$editLink.'\')" target="_blank" rel="noopener noreferrer"><i class="fa fa-edit" title="Edit"></i></a>&emsp;';
        $actionButton .= '<a class="deluser" title="<strong>Are you sure you want to delete?</strong>" onclick="deletePost('.($i).',\''.$encodedId.'\')"><i class="fa fa-trash fa-l" title="Delete"></i></a>';

        $final[] = array(
            "DT_RowId" => $i,
            $announcement,
            $row['groupname'],
            $row['chaptername'],
            $row['channelname'],
            $db->covertUTCtoLocalAdvance('Y-m-d H:i'," T(P)", ( ($row['isactive'] == Post::STATUS_DRAFT || $row['isactive'] == Post::STATUS_UNDER_REVIEW) ? $row['postedon'] : $row['publishdate'] ), $_SESSION['timezone']),
            $creator,
            $actionButton
           );
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
elseif (isset($_GET['directMailsReportModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Authorization Check
    if (!$_USER->canManageCompanySettings() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $groups = Group::GetAllGroupsByCompanyid($_COMPANY->id(),$_ZONE->id(),true);
    $reportMeta = ReportDirectMails::GetDefaultReportRecForDownload();
    $fields = array();
    $reportType = array();

    if ($reportMeta) {
        $fields = $reportMeta['Fields'];
        if (!empty($reportMeta['AdminFields'])) {
            $fields = array_merge($fields, $reportMeta['AdminFields']);
        }
    }
    $report_label = 'Members Direct Mails';
    //include the modal
    include(__DIR__ . "/views/templates/direct_mail_report.template.php");
}
elseif (isset($_GET['download_direct_mail_report'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $groupids = array();
    $reportMeta = ReportDirectMails::GetDefaultReportRecForDownload();
   
    $Fields = $reportMeta['Fields'];
    if(!empty($_POST['s_group'])){
        foreach($_POST['s_group'] as $g){
            array_push($groupids,$_COMPANY->decodeId($g));
        }
    }

    foreach ($Fields as $k => $v) {
        if (!isset($_POST[$k])) {
            unset($Fields[$k]);
        }
    }
    $reportAction = $_POST['reportAction'];

    $meta = array(
        'Fields' => $Fields,
        'Filters' => array(
            'groupids' => $groupids, // List of groupids, or empty for all groups
            'is_admin' => true, // Set is_admin to true to get all admin sent messages. This field is only set when report is downloaded from Admin Panel
        )
    );

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'announcement';
    $record['reportmeta'] = json_encode($meta);
    $report = new ReportDirectMails ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}
elseif (isset($_GET['download_officelocations_report'])){
    // Authorization Check
    if (!$_USER->isAdmin()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $ids = [];
//    if (!empty($_POST['r_type']) ) {
//        $regions = $_POST['r_type'];
//        for($i=0;$i<count($regions);$i++){
//            array_push($ids,$_COMPANY->decodeId($regions[$i]));
//        }
//    }

    $reportMeta = ReportOfficeLocations::GetDefaultReportRecForDownload();

    $reportMeta['Filters']['regionids'] = $ids; // List of regionids, or empty for all groups

    // We do not provide option to remove groupname, chaptername, role or since date
    // We do not provide external id in the report of extended profile fields in the reports at the moment.
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'officelocations';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportOfficeLocations ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}

elseif (isset($_GET['download_groupchapters_report'])){
    // Authorization Check
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $ids = []; // All
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;

    $section = $_GET['download_groupchapters_report'] ?? 'group';

    if ($section == 'chapter'){ // Chapter
        $reportMeta = ReportGroupChapterDetails::GetDefaultReportRecForDownload();
        $reportMeta['Filters']['groupids'] = $ids; // List of groupids, or empty for all groups
        $record['reportname'] = 'Group_Chapters';
        $record['reportmeta'] = json_encode($reportMeta);
        $report = new ReportGroupChapterDetails($_COMPANY->id(),$record);
    } elseif ($section == 'channel'){ // Channel
        $reportMeta = ReportGroupChannelDetails::GetDefaultReportRecForDownload();
        $reportMeta['Filters']['groupids'] = $ids; // List of groupids, or empty for all groups
        $record['reportname'] = 'Group_Channels';
        $record['reportmeta'] = json_encode($reportMeta);
        $report = new ReportGroupChannelDetails($_COMPANY->id(),$record);
    } else { // Group default
        $reportMeta = ReportGroupDetails::GetDefaultReportRecForDownload();
        $reportMeta['Filters']['groupids'] = $ids; // List of groupids, or empty for all groups
        $record['reportname'] = 'Groups';
        $record['reportmeta'] = json_encode($reportMeta);
        $report = new ReportGroupDetails ($_COMPANY->id(),$record);
    }


    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}
elseif (isset($_GET['download_groupchapters_location_report'])){
    // Authorization Check
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportGroupChapterLocation::GetDefaultReportRecForDownload();

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'Chapter_locations';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportGroupChapterLocation($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}


elseif (isset($_GET['viewSpeakerDetail']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageZoneSpeakers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($speakerid = $_COMPANY->decodeId($_GET['speakerid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        ($speaker = $event->getEventSpeakerDetail($speakerid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $speaker = $event->getEventSpeakerDetail($speakerid);
    $approvalStatus = array('1'=>'Requested','2'=>'Processing','3'=>'Approved','4'=>'Denied');

    $event_speaker_obj = EventSpeaker::Hydrate($speakerid, $speaker);
?>
<div class="card pt-0 mb-3" style="width:100%">
    <div class="row">
        <div class="col-md-12 text-left py-3">
            <p class="px-2"><strong>Speaker Name:</strong> <?= htmlspecialchars($speaker['speaker_name']); ?></p>
            <p class="px-2"><strong>Speaker Title:</strong> <?= htmlspecialchars($speaker['speaker_title']); ?></p>
        <?php if($_COMPANY->getAppCustomization()['event']['speakers']['approvals']){ ?>
            <p class="px-2"><strong>Approval Status:</strong> <?= $approvalStatus[$speaker['approval_status']]?></p>
            <?php
                if($speaker['approver_note']){
            ?>
                    <p class="px-2"><strong>Approver Note:</strong> <?= htmlspecialchars($speaker['approver_note']); ?></p>
            <?php
                }
            ?>
        <?php } ?>
        </div>
    </div>
    <div class="col-md-12 p-0">
        <div style="text-align: center; border-top: 1.5px dashed rgb(185, 182, 182)">
            <strong style="display: inline-block; position: relative; top: -10px; background-color: white; padding: 0px 0px"></strong>
        </div>
    </div>

    <p class="px-2"><strong>Speech Length:</strong> <?= $speaker['speech_length']; ?> minutes</p>
    <p class="px-2"><strong>Expected Audiences:</strong> <?= $speaker['expected_attendees']; ?></p>
    <p class="px-2"><strong>Speaker Fee:</strong> $<?= $speaker['speaker_fee']; ?></p>
    <p class="px-2"><strong>Bio:</strong> <?= htmlspecialchars($speaker['speaker_bio']); ?></p>
    <!--<p class="px-2"><strong>Other:</strong> <?= htmlspecialchars($speaker['other']); ?></p>-->
    <?= $event_speaker_obj->renderCustomFieldsComponent('v4') ?>
</div>
<?php
}

elseif (isset($_GET['updateEventSpeakerStatus']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageZoneSpeakers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($speakerid = $_COMPANY->decodeId($_POST['speakerid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        ($speaker = $event->getEventSpeakerDetail($speakerid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $action = ($_POST['action'] == 3) ? 3 : 4;
    $approver_note = $_POST['note'];
    $approved_by = $_USER->id();

    if ($event->updateEventSpeakerStatus($speakerid,$action,$approver_note,$approved_by)) {
        $eventZone = $_COMPANY->getZone($event->val('zoneid'));
        $speakerApprovalCCEmails = Event::GetSpeakerApprovalCCEmailsForZone($eventZone);

        $approvelStatus = array('1'=>'Requested','2'=>'Processing','3'=>'Approved','4'=>'Denied');

        $createdByUser = User::GetUser($speaker['createdby']);
        $email = $_USER->val('email');
        if (!$createdByUser) {
            Logger::Log("User who created the speaker request has been deleted, skipping email for userid={$speaker['createdby']}", Logger::SEVERITY['WARNING_ERROR']);
        } else {
            $email = $createdByUser->val('email') . ',' . $_USER->val('email');
        }

        $email = implode(',',array_filter(array_unique(explode(',', $email))));

        $subject = $approvelStatus[$action]. '-Speaker Request for '. htmlspecialchars_decode($event->val('eventtitle'));
        $speakerName = htmlspecialchars($speaker['speaker_name']);
        $speakerFee = htmlspecialchars($speaker['speaker_fee']);
        $approver_note = htmlspecialchars($approver_note);
        //$approved_by = $_USER->val('userid');

        $event_url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'eventview?id=' . $_COMPANY->encodeId($event->id()) . '&approval_review=1';
        if ($action == 3) {
            $nextStep = "Please return to the <a href='{$event_url}'>event</a> and select <strong>Action > Email Review</strong> to send an email to Leaders for Review/Publishing of this event.";
        } elseif ($action == 4) {
            $nextStep = "Please return to the <a href='{$event_url}'>event</a> and select <strong>Action > Manage Event Speakers</strong> to update Speaker(s).";
        } else {
            $nextStep = '';
        }

        $event_speaker_obj = EventSpeaker::Hydrate($speakerid, $speaker);
        $custom_fields_html = $event_speaker_obj->renderCustomFieldsComponent('v5');

        $msg = <<<EOMEOM
            <p>The following request has been <b>{$approvelStatus[$action]}</b> by <b>{$_USER->getFullName()}</b> ({$_USER->val('email')}). {$nextStep}</p>
            <br>
            <p>Approver Note :  {$approver_note}</p>
            <br>
            <p>Event Speaker Request Summary:</p>
            <p>-------------------------------------------------</p>
            <p>Event: {$event->val('eventtitle')}</p>
            <p>Speaker Name    : {$speakerName}</p>
            <p>Speech Length   :  {$speaker['speech_length']} minutes</p>
            <p>Speaker Fee ($) :  {$speakerFee}</p>
            {$custom_fields_html}
            <p>-------------------------------------------------</p>
EOMEOM;
            $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
            $from = $_ZONE->val('email_from_label') . " Speaker Request";
            $emesg	= str_replace('#messagehere#',$msg,$template);
            $_COMPANY->emailSend2($from, $email, $subject, $emesg, $_ZONE->val('app_type'),'','',array(),$speakerApprovalCCEmails);
        echo 1;
    } else {
        echo 0;
    }
    exit();
}

elseif (isset($_GET['deleteEventSpeaker']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageZoneSpeakers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($speakerid = $_COMPANY->decodeId($_POST['speakerid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        ($speaker = $event->getEventSpeakerDetail($speakerid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($event->deleteEventSpeaker($speakerid)){
        echo 1;
    } else {
        echo 0;
    }
}

elseif (isset($_GET['viewApprovalDetail']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (($topicTypeId = $_COMPANY->decodeId($_GET['topicTypeId']))<1 ||
        ($approvalid = $_COMPANY->decodeId($_GET['approvalid']))<1 || 
        ($topicType = $_GET['topicType']) === FALSE
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $latestOrgData = []; // initializing for ORG loop
    $isActiveCheck = true; // for skipping survey topic type
    if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
        $topicTypeObj = Event::GetEvent($topicTypeId);
        $topicTypeLabel = 'Event';
        $viewPath = 'eventview';
        $appCustomizationTitle = 'event';
        // Fetch ORG
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
        $appCustomizationTitle = 'event';
    }elseif($topicType == Teleskope::TOPIC_TYPES['SURVEY']){
        $topicTypeObj = Survey2::GetSurvey(($topicTypeId));
        $topicTypeLabel = Survey2::GetCustomName(false);
        $viewPath = '';
        $appCustomizationTitle = 'surveys';
        $isActiveCheck = false; 
    }

    $enc_topictype_id = $_COMPANY->encodeId($topicTypeId);

    // Check if the object exists
    if($topicTypeObj === NULL || ( $isActiveCheck && !$topicTypeObj->val('isactive'))){
        echo $response = -1;
        exit();
    }

    $encGroupId = $_COMPANY->encodeId($topicTypeObj->val('groupid'));

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
    if($topicTypeObj->val('listids') != 0){
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
    $standalone_page = false;
    include(__DIR__ . '/views/admin_view_approval_details.html');
}
elseif (isset($_GET['downloadEventApprovalAttachments']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $eventid = $_COMPANY->decodeId($_GET['eventid'] ?? '');
    if (!is_numeric($eventid) && $eventid < 1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $event = Event::GetEvent($eventid);
    $approval = $event->getApprovalObject();

    $zip = new ZipArchive();
    $tmp_zip_file = TmpFileUtils::GetTemporaryFile($_COMPANY->val('subdomain'));
    $zip->open($tmp_zip_file, ZipArchive::OVERWRITE);

    $root_folder = slugify($event->val('eventtitle'));

    $approval_notes = $approval?->getApprovalLogs() ?? [];
    foreach ($approval_notes as $note) {
        $attachments = $note->getAttachments();

        $createdby_user = User::GetUser($note->val('createdby')) ?? User::GetEmptyUser();
        $folder_name =
            $root_folder
            . '/'
            . 'Note_'
            . slugify($createdby_user->getFullName())
            . '_'
            . str_replace(['-',':',' '],['','','_'], $note->val('createdon'));

        foreach ($attachments as $attachment) {
            $attachment_downloaded_file = $attachment->download(true);
            $zip->addFile($attachment_downloaded_file, $folder_name . '/' . $attachment->val('attachment_file_name'));
        }
    }

    $approval_tasks = $approval?->GetAllTasksByApproval() ?? [];
    foreach ($approval_tasks as $task) {
        $task = TopicApprovalTask::Hydrate($task['approval_taskid'], $task);
        $attachments = $task->getAttachments();

        $folder_name =
            $root_folder
            . '/'
            . 'Approval_Stage_'
            . $task->val('approval_stage')
            . '/'
            . slugify($task->val('approval_task_name'))
            . '_'
            . str_replace(['-',':',' '], ['','','_'], $task->val('createdon'));;

        foreach ($attachments as $attachment) {
            $attachment_downloaded_file = $attachment->download(true);
            $zip->addFile($attachment_downloaded_file, $folder_name . '/' . $attachment->val('attachment_file_name'));
        }
    }

    if (!$zip->numFiles) {
        $zip->addFromString('README.txt', gettext('No file attachments found'));
    }

    $zip->close();

    Http::Download($tmp_zip_file, 'download.zip');
}
elseif (isset($_GET['saveTopicApprovalNote']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
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

    // assign object
    $approval = $topicTypeObj->getApprovalObject();
    $saveNote = $approval->addGeneralNote($_POST['note']);
    if($saveNote){
        $enc_approvalid = $_COMPANY->encodeId($approval->id());
        AjaxResponse::SuccessAndExit_STRING(1, $enc_approvalid, "Note added successfully", 'Success');
    }else{
        AjaxResponse::SuccessAndExit_STRING(0, '', "Try again later", 'Error');
    }

}
elseif (isset($_GET['updateCalendarTitle']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['calendar_page_banner_title'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $calendar_page_banner_title = Sanitizer::SanitizeGenericLabel($_POST['calendar_page_banner_title']);

    Company::UpdateZoneCalendarPageBannerTitle($calendar_page_banner_title);
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
    print($calendar_page_banner_title);
}

elseif (isset($_GET['updateAdminContentTitle']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['admin_content_page_title'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $admin_content_page_title = Sanitizer::SanitizeGenericLabel($_POST['admin_content_page_title']);

    Company::UpdateZoneAdminContentPageTitle($admin_content_page_title);
    // Reload company by invalidating cache and reloading it from database
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
    print($admin_content_page_title);
}
elseif (isset($_GET['activateDeactivateEventSpeakerField']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageZoneSpeakers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (($speaker_fieldid = $_COMPANY->decodeId($_POST['id']))<1 ||
    ($feildData = Event::GetEventSpeakerFieldDetail($speaker_fieldid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $inArray = array(0,1,2);
    if (!in_array((int)$_POST['action'], $inArray)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $action = (int)$_POST['action'];
    Event::ActivateDeactivateEventSpeakerField($speaker_fieldid, $action);
    echo 1;
}
elseif (isset($_GET['openNewGroupLeadTypeModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    if (($leadTypeId = $_COMPANY->decodeId($_GET['openNewGroupLeadTypeModal']))<0
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $pageTitle = "New Leader type";
    $leadType = null;
    if ($leadTypeId){
        $pageTitle = "Update Leader Type";
        $leadType = $_COMPANY->getGroupLeadType($leadTypeId);
    }
    $sys_lead_types = Group::SYS_GROUPLEAD_TYPES;
    if (!$_COMPANY->getAppCustomization()['chapter']["enabled"]) {
        unset($sys_lead_types[3]); // Unset Regional Lead Type
        unset($sys_lead_types[4]); // Unset Chapter Lead Type
    }
    if (!$_COMPANY->getAppCustomization()['channel']["enabled"]) {
        unset($sys_lead_types[5]); // Unset Channel Lead Type
    }
    $sys_lead_types = array_unique($sys_lead_types); // Using array_unique to fill holes

    include(__DIR__ . "/views/new_grouplead_type.html");

}
elseif (isset($_GET['enableDisableTeamRoleType']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($roleid = $_COMPANY->decodeId($_POST['id']))<1 ||
        ($teamRole = Team::GetTeamRoleType($roleid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $status = $_POST['status'];
    echo Team::UpdateTeamRoleStatus($groupid,$roleid,$status);
}
elseif (isset($_GET['getTeamRolesList']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
	$data = Team::GetProgramTeamRoles($groupid);
	include(__DIR__ . "/views/manage_team_role_types.html");
}

elseif (isset($_GET['getCompanyBudgetYears']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $budgetYears = Budget2::GetCompanyBudgetYears();
    include(__DIR__ . "/views/manage_company_budget_years.html");

}
elseif (isset($_GET['openNewBudetYearModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $budget_year_id = (int) $_COMPANY->decodeId($_GET['budget_year_id']);
    $modalTitle = "New Budget Year";
    $editBudgetYear = null;

    if ($budget_year_id){
        $modalTitle = "Update Budget Year";
        $editBudgetYear = Budget2::GetCompanyBudgetYearDetail($budget_year_id);
    }

    include(__DIR__ . "/views/new_budget_year_modal.html");
}
elseif (isset($_GET['saveCompanyBudgetYear']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $budget_year_id = $_COMPANY->decodeId($_POST['budget_year_id']);
    $budget_year_title = $_POST['budget_year_title'];
    $budget_year_start_date = $_POST['budget_year_start_date'];
    $budget_year_end_date = $_POST['budget_year_end_date'];

    $budgetYears = Budget2::GetCompanyBudgetYears();

    $respCode = Budget2::SaveCompanyBudgetYear($budget_year_id,$budget_year_title,$budget_year_start_date,$budget_year_end_date);
    if($respCode > 0){
        if (empty($budgetYears)){
            echo 2;
        } else {
            echo 1;
        }
        exit();
    } elseif ($respCode < 0) {
        echo $respCode; // -1 for incorrect end date, -2 for overlapping dates
        exit();
    }
    exit(); // Do not echo anything
}
elseif (isset($_GET['deleteCompanyBudgetYear']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $budget_year_id = $_COMPANY->decodeId($_POST['budget_year_id']);

    $deleted = Budget2::DeleteBudgetYear($budget_year_id);
    if($deleted){
        echo 1;
        exit();
    }   
    echo 0;
    exit();
}

elseif (isset($_GET['initChapterPermanentDeleteConfirmation']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['groupid']) || ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1
        || !isset($_GET['chapterid']) || ($chapterid = $_COMPANY->decodeId($_GET['chapterid']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $chapterLabel = $_COMPANY->getAppCustomization()['chapter']['name'];
    $modalTitle = $chapterLabel . ' deletion confirmation';
    $enc_groupId = $_COMPANY->encodeId($groupid);
    $enc_objectId = $_COMPANY->encodeId($chapterid);
    $audit_code = $_USER->generateAuditCode(); // Add the audit code to secure function from being called unsecurely.
    $functionName = "deleteChapterPermanently";
    $whatWillBeDeleted = 'I understand that '.$chapterLabel.' and all of its members, leaders, announcements, events, newsletters and surveys will be permanently deleted.';
    include(__DIR__ . "/views/general_permanent_delete_confirmation_modal.html");
}

elseif (isset($_GET['deleteChapterPermanently']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent() ||
        !($_USER->validateAuditCode($_POST['audit_code']))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (
        !isset($_POST['groupid']) || ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1
        || !isset($_POST['chapterid']) || ($chapterid = $_COMPANY->decodeId($_POST['chapterid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

   echo $group->deleteChapterPermanently($chapterid);

}
elseif (isset($_GET['initChannelPermanentDeleteConfirmation']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['groupid']) || ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1
        || !isset($_GET['channelid']) || ($channelid = $_COMPANY->decodeId($_GET['channelid']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $channelLabel = $_COMPANY->getAppCustomization()['channel']['name'];
    $modalTitle = $channelLabel.' deletion confirmation';
    $enc_groupId = $_COMPANY->encodeId($groupid);
    $enc_objectId = $_COMPANY->encodeId($channelid);
    $audit_code = $_USER->generateAuditCode(); // Add the audit code to secure function from being called unsecurely.
    $functionName = "deleteChannelPermanently";
    $whatWillBeDeleted = 'I understand that '.$channelLabel.' and all of its members, leaders, announcements, events, newsletters and surveys will be permanently deleted.';
    include(__DIR__ . "/views/general_permanent_delete_confirmation_modal.html");
    exit();
}

elseif (isset($_GET['deleteChannelPermanently']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent() ||
        !($_USER->validateAuditCode($_POST['audit_code']))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (
        !isset($_POST['groupid']) || ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1
        || !isset($_POST['channelid']) || ($channelid = $_COMPANY->decodeId($_POST['channelid']))<1 ||
        ($group = Group::GetGroup($groupid)) == null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    echo $group->deleteChannelPermanently($channelid);
    exit();
}
elseif (isset($_GET['initGroupPermanentDeleteConfirmation']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_GET['groupid']) ||
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $groupname = $group->val('groupname');
    $grouplabel = $_COMPANY->getAppCustomization()['group']["name-short"] ?? 'Group';
    // check chapters and channels

    // 30 days after creation, group can be self deleted only after 5 days of wait period after the last state change
    $modified_on_plus_five_days = strtotime($group->val('modifiedon')) + 86400 * 5;
    $created_on_plus_thirty_days = strtotime($group->val('addedon')) + 86400 * 30;
    if ((time() > $created_on_plus_thirty_days) && (time() < $modified_on_plus_five_days)) {
        AjaxResponse::SuccessAndExit_STRING(0, '',
            sprintf(gettext('Sorry, this %1$s cannot be deleted at this time. This %1$s can be permanently deleted 5 days after the last state change. Please try again after %2$s.'), $grouplabel, $_USER->formatUTCDatetimeForDisplayInLocalTimezone(date('Y-m-d H:i:s', $modified_on_plus_five_days),true,true,false)),
            gettext('Unable to delete')
        );
        exit();
    }

    $chapters = Group::GetChapterListDetail($group->id(), true);
    $channels = Group::GetChannelListDetail($group->id(), true);
    if(count($chapters) > 0){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please delete all '.$_COMPANY->getAppCustomization()['chapter']["name-short-plural"].' of this '.$grouplabel.'.'), gettext('Unable to delete'));
        exit();
    }
    if(count($channels) > 0){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please delete all '.$_COMPANY->getAppCustomization()['channel']["name-short-plural"].' of this '.$grouplabel.'.'), gettext('Unable to delete'));
        exit();
    }
    // budget check
    $budgetYears = Budget2::GetCompanyBudgetYears();
    foreach($budgetYears as $budgetYear){
        $groupBudget = Budget2::GetBudget($budgetYear['budget_year_id'], $groupid);
        if((int)$groupBudget->val('budget_amount') > 0){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('This '.$grouplabel.' has budget assigned for budget year '.$budgetYear['budget_year_title'].', please remove the budget for this '.$grouplabel.' first'), gettext('Unable to delete'));
            break;
        }
    }
    $modalTitle = $groupname . ' deletion confirmation';
    $enc_groupId = $_COMPANY->encodeId($groupid);
    $enc_objectId = '';
    $audit_code = $_USER->generateAuditCode(); 
    $functionName = "deleteGroupPermanently";
    $whatWillBeDeleted = 'I understand that '.$groupname.' and all of its members, leaders, announcements, events, newsletters and surveys will be permanently deleted.';
    include(__DIR__ . "/views/general_permanent_delete_confirmation_modal.html");
}
elseif (isset($_GET['deleteGroupPermanently']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent() ||
        !($_USER->validateAuditCode($_POST['audit_code']))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (
        !isset($_POST['groupid']) || ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1
        ||($group = Group::GetGroup($groupid)) == null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }    

   $res = $group->deleteGroupPermanently();
   if($res == 1){
        AjaxResponse::SuccessAndExit_STRING(1, '', "Deleted successfully", 'Success');    
    }else{        
        $grouplabel = $_COMPANY->getAppCustomization()['group']["name-short"] ?? 'Group';     
        
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Sorry, this %1$s cannot be deleted at this time.'),$grouplabel), 'Error!'); 
    }
    
}

elseif (isset($_GET['updateProgramTypeSetting'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

	if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($program_type_value = intval($_POST['team_program_type'])) < 1 ||
        (!in_array($program_type_value,array_values(Team::TEAM_PROGRAM_TYPE)))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $return = 0;
    if($group->updateTeamProgramType($program_type_value)){
        if ($program_type_value == '3'){ // Hide Touch point and feedback by default
            // Re initialize Group
            $group = Group::GetGroup($groupid);
            $group->updateHiddenProgramTabSetting(array(TEAM::PROGRAM_TEAM_TAB['TOUCH_POINT'],TEAM::PROGRAM_TEAM_TAB['FEEDBACK']));
        }
        $return = 1;
    }
    echo $return;
    exit();
}

elseif (isset($_GET['openTeamMetaNameUpateModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $modalTitle = "Update {$_COMPANY->getAppCustomization()['teams']['name']} Name";
    $team_meta_name = $group->getTeamMetaName();

    include(__DIR__ . "/views/update_team_meta_name_modal.html");
}

elseif (isset($_GET['addUpdateTemplate']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (
        ($templateid = $_COMPANY->decodeId($_POST['templateid']))<0
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    $templatename   =   $_POST['templatename'];
    
    if ($_POST['templatetype'] == 'comm_welcome_email' || $_POST['templatetype'] == 'comm_leave_email' || $_POST['templatetype'] == 'communications') {
        $templatetype = 4; 
    } elseif($_POST['templatetype'] == 'newsletter') {
        $templatetype = 1;
    }elseif ($_POST['templatetype'] == 'comm_anniversary_email') {
        $templatetype = 5; 
    }else{
        $templatetype = 0;
    }

    $template       =   $_POST['template'];
    echo $_COMPANY->addUpdateTemplate($templateid,$templatename,$templatetype,$template);
}
elseif (isset($_GET['updateTemplateStatus'])){
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
	$templateid = (int)$_COMPANY->decodeId($_GET['updateTemplateStatus']);
	$status = (int)$_COMPANY->decodeId($_GET['status']);
    $status = $status == 1 ? 1 : 0;

	$_COMPANY->updateTemplateStatus($templateid,$status);
	Http::Redirect("manage_templates");
}


elseif (isset($_GET['uploadEmailTemplateMedia'])){
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
	$file = [];
	if ( !empty($_FILES['file']['name'])){
		$file 	    =	basename($_FILES['file']['name']);
		$tmp 		=	$_FILES['file']['tmp_name'];
		$extention	=	$db->getExtension($file);
		$actual_name ="templatemedia_".time().".".$extention;
		$resource = $_COMPANY->saveFile($tmp,$actual_name,'TEMPLATE');
		if (!empty($resource)) {
			$file = [
				'url' => $resource
			];
		}
	}
	echo stripslashes(json_encode($file));
}
elseif (isset($_GET['updateSurveyDownloadSetting'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL ||
        ($status = (int) $_POST['status'])<0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    echo $group->updateSurveyDownloadSetting($status);
}

elseif (isset($_GET['deleteAppSliderPhoto']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (
        ($groupid=$_COMPANY->decodeId($_GET['deleteAppSliderPhoto']))<1 ||
        ($group = Group::GetGroup($groupid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if(!empty($group->val('app_sliderphoto'))){
        $_COMPANY->deleteFile($group->val('app_sliderphoto')); //Delete old file if it exists
    }

    $group->updateGroupMedia($group->val('groupicon'), $group->val('sliderphoto'), $group->val('coverphoto'), '', $group->val('app_coverphoto'));

    $_SESSION['updated'] = time();
    echo 1;
    exit();
}

elseif (isset($_GET['deleteAppCoverPhoto']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (
        ($groupid=$_COMPANY->decodeId($_GET['deleteAppCoverPhoto']))<1 ||
        ($group = Group::GetGroup($groupid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if(!empty($group->val('app_coverphoto'))){
        $_COMPANY->deleteFile($group->val('app_coverphoto')); //Delete old file if it exists
    }

    $group->updateGroupMedia($group->val('groupicon'), $group->val('sliderphoto'), $group->val('coverphoto'), $group->val('app_sliderphoto'), '');

    $_SESSION['updated'] = time();
    echo 1;
    exit();
}

elseif (isset($_GET['manageSpeakerApprovalConfiguration']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $speakerApprovalCCEmails =  explode(',', Event::GetSpeakerApprovalCCEmailsForZone($_ZONE));
    
    // Get TODO
    $modalTitle = "Manage Speaker Approval Configuration";
    
	include(__DIR__ . "/views/manage_speaker_approval_conf.html");
}

elseif (isset($_GET['updateSpeakersApprovalCCEmails']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

        $emails = $_POST['emails'] ?? '';
        $invalidEmails = array();
        $validEmails = array();
        foreach (array_unique(explode(',',$emails)) as $e) {
            $e = trim($e);
            if (empty($e)){
                continue;
            }
            if (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
                array_push($invalidEmails, $e);
            } elseif (!$_COMPANY->isValidEmail($e)) {
                array_push($invalidEmails, $e);
            } else{
                array_push($validEmails, $e);
            }
        }
        if (!empty($invalidEmails)){
            AjaxResponse::SuccessAndExit_STRING(0, '', implode(', ', $invalidEmails)." are not valid email addresses. Please do correction.", 'Error!');
        }

        Event::UpdateSpeakerApprovelCCEmailsForZone(implode(',',$validEmails));
        AjaxResponse::SuccessAndExit_STRING(1, implode('<br/>', $validEmails), "Speaker approval email(s) updated successfully", 'Success');

}
elseif (isset($_GET['downloadAnnouncementReport'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Authorization Check
    if (!$_USER->canManageCompanySettings() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $timezone = $_SESSION['timezone'] ?? 'UTC';
    // Get group
    $group = Group::GetAllGroupsByCompanyid($_USER->cid(),$_ZONE->id(),true);
    $reportsurveyMeta = ReportAnnouncement::GetDefaultReportRecForDownload();
    $fields = array();
    $reportType = array();
    if ($reportsurveyMeta) {
        $fields = $reportsurveyMeta['Fields'];
        if (!empty($reportsurveyMeta['AdminFields'])) {
            $fields = array_merge($fields, $reportsurveyMeta['AdminFields']);
        }
    }
   
    include(__DIR__ . "/views/templates/announcement_report.template.php");

}
elseif (isset($_GET['download_announcement_report']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportAnnouncement::GetDefaultReportRecForDownload();
   
    $Fields = $reportMeta['Fields'];

    $groupids = array();

    if(!empty($_POST['s_group'])){
        foreach($_POST['s_group'] as $g){
            array_push($groupids,$_COMPANY->decodeId($g));
        }
    }

    $options = array();
    $timeZone = $_SESSION['timezone'] ?? "UTC";

    if (!empty($_POST['startDate'])) {
        $options['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
    }
    
    if (!empty($_POST['endDate'])) {
        $options['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
    }

    foreach ($Fields as $k => $v) {
        if (!isset($_POST[$k])) {
            unset($Fields[$k]);
        }
    }
    $reportAction = $_POST['reportAction'];

    $meta = array(
        'Fields' => $Fields,
        'Options' => $options,
        'Filters' => array(
            'groupids' => $groupids // List of groupids, or empty for all groups
        )
    );

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'announcement';
    $record['reportmeta'] = json_encode($meta);

    $report = new ReportAnnouncement($_COMPANY->id(),$record);
    if ( $reportAction == 'download'){
        $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
        #else echo false
        echo false;
    }
    exit();
}
elseif (isset($_GET['downloadNewsletterReport'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Authorization Check
    if (!$_USER->canManageCompanySettings() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $timezone = $_SESSION['timezone'] ?? 'UTC';
    // Get group
    $group = Group::GetAllGroupsByCompanyid($_USER->cid(),$_ZONE->id(),true);
    $reportsurveyMeta = ReportNewsletter::GetDefaultReportRecForDownload();
    $fields = array();
    $reportType = array();
    if ($reportsurveyMeta) {
        $fields = $reportsurveyMeta['Fields'];
        if (!empty($reportsurveyMeta['AdminFields'])) {
            $fields = array_merge($fields, $reportsurveyMeta['AdminFields']);
        }
    }
   
    include(__DIR__ . "/views/templates/newsletter_report.template.php");

}
elseif (isset($_GET['download_newsletter_report']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportNewsletter::GetDefaultReportRecForDownload();
   
    $Fields = $reportMeta['Fields'];

    $groupids = array();

    if(!empty($_POST['s_group'])){
        foreach($_POST['s_group'] as $g){
            array_push($groupids,$_COMPANY->decodeId($g));
        }
    }

    $options = array();
    $timeZone = $_SESSION['timezone'] ?? "UTC";

    if (!empty($_POST['startDate'])) {
        $options['start_date'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
    }
    
    if (!empty($_POST['endDate'])) {
        $options['end_date'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
    }

    foreach ($Fields as $k => $v) {
        if (!isset($_POST[$k])) {
            unset($Fields[$k]);
        }
    }
    $reportAction = $_POST['reportAction'];

    $meta = array(
        'Fields' => $Fields,
        'Options' => $options,
        'Filters' => array(
            'groupids' => $groupids // List of groupids, or empty for all groups
        )
    );

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'Newsletter';
    $record['reportmeta'] = json_encode($meta);

    $report = new ReportNewsletter($_COMPANY->id(),$record);
    if ( $reportAction == 'download'){
        $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
        #else echo false
        echo false;
    }
    exit();
}
elseif (isset($_GET['downloadSurveysReport'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Authorization Check
    if (!$_USER->canManageCompanySettings()|| !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $timezone = $_SESSION['timezone'] ?? 'UTC';
    // Get group
    $group = Group::GetAllGroupsByCompanyid($_USER->cid(),$_ZONE->id(),true);
    $reportsurveyMeta = ReportSurveyData::GetDefaultReportRecForDownload();
    $fields = array();
    $reportType = array();
    if ($reportsurveyMeta) {
        $fields = $reportsurveyMeta['Fields'];
        if (!empty($reportsurveyMeta['AdminFields'])) {
            $fields = array_merge($fields, $reportsurveyMeta['AdminFields']);
        }
    }
    $cpuUsageMessage = "Note: Selecting too many fields may slow down your system due to high CPU and memory usage for data analysis. So please choose the least fields for analytics for better performance.";
   
    include(__DIR__ . "/views/templates/survey_data_report.template.php");

}
elseif (isset($_GET['download_survey_report']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportSurveyData::GetDefaultReportRecForDownload();
   
    $Fields = $reportMeta['Fields'];

    $groupids = array();

    if(!empty($_POST['s_group'])){
        foreach($_POST['s_group'] as $g){
            array_push($groupids,$_COMPANY->decodeId($g));
        }
    }

    $options = array();
    $timeZone = $_SESSION['timezone'] ?? "UTC";

    if (!empty($_POST['startDate'])) {
        $options['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $_SESSION['timezone']);
    }
    
    if (!empty($_POST['endDate'])) {
        $options['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $_SESSION['timezone']);
    }

    foreach ($Fields as $k => $v) {
        if (!isset($_POST[$k])) {
            unset($Fields[$k]);
        }
    }
    $reportAction = $_POST['reportAction'];
    $options['includeInactiveSurveys'] = !empty($_POST['includeInactiveSurveys']);

    $meta = array(
        'Fields' => $Fields,
        'Options' => $options,
        'Filters' => array(
            'groupids' => $groupids // List of groupids, or empty for all groups
        )
    );

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'survey';
    $record['reportmeta'] = json_encode($meta);

    $report = new ReportSurveyData($_COMPANY->id(),$record);
    if ( $reportAction == 'download'){
        $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
        #else echo false
        echo false;
    }
    exit();
}
elseif (isset($_GET['downloadEventsReport'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Authorization Check
    if (!$_USER->canManageCompanySettings() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $timezone = @$_SESSION['timezone'];
    $startDate = date("Y-m-d",strtotime('-1 month'));
        // Get group
        $group = $db->ro_get("SELECT `groupid`, `groupname` FROM `groups` WHERE `companyid`='{$_COMPANY->id()}' AND (`zoneid`='{$_ZONE->id()}' AND `isactive`='1')");


    $cpuUsageMessage = "Note: Selecting too many fields may slow down your system due to high CPU and memory usage for data analysis. So please choose the least fields for analytics for better performance.";
   
    if ($_GET['downloadEventsReport']=='event_list_report') {
        // Events List checkbox options
        $reportMeta = ReportEvents::GetDefaultReportRecForDownload();
        $fields = array();
        $reportType = array();
        if ($reportMeta) {
            $fields = $reportMeta['Fields'];
            if (!empty($reportMeta['AdminFields'])) {
                $fields = array_merge($fields, $reportMeta['AdminFields']);
            }
        }
        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            unset($fields['chaptername']);
            unset($fields['enc_chapterid']);
        }
        if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
            unset($fields['channelname']);
            unset($fields['enc_channelid']);
        }
        if (!$_COMPANY->getAppCustomization()['event']['volunteers']) {
            unset($fields['total_volunteering_hours']);
            unset($fields['volunteering_hours_configured']);
        }
        if(!$_COMPANY->getAppCustomization()['event']['reconciliation']['enabled']){
            unset($fields['is_event_reconciled']);
        }
        $excludeAnalyticMetaFields = json_encode(ReportEvents::GetMetadataForAnalytics()['ExludeFields']);
        include(__DIR__ . "/views/templates/event_list_report.template.php");
    }
    if ($_GET['downloadEventsReport']=='event_rsvp_report') {

        // Events RSVP checkbox options
        $reportRsvpMeta = ReportEventRSVP::GetDefaultReportRecForDownload();
        $excludeAnalyticMetaFields = json_encode(ReportEventRSVP::GetMetadataForAnalytics()['ExludeFields']);
        $fields_rsvp = array();
        if ($reportRsvpMeta) {
            $fields_rsvp = $reportRsvpMeta['Fields'];
            if (!empty($reportRsvpMeta['AdminFields'])) {
                $fields_rsvp = array_merge($fields_rsvp, $reportRsvpMeta['AdminFields']);
            }
        }
        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            unset($fields_rsvp['chaptername']);
            unset($fields_rsvp['enc_chapterid']);
        }

        if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
            unset($fields_rsvp['channelname']);
            unset($fields_rsvp['enc_channelid']);
        }
        if (!$_COMPANY->getAppCustomization()['event']['volunteers']) {
            unset($fields_rsvp['total_volunteering_hours']);
            unset($fields_rsvp['volunteering_hours_configured']);
            unset($fields_rsvp['volunteers_filled']);
        }
        include(__DIR__ . "/views/templates/event_rsvp_report.template.php");
    }
    if ($_GET['downloadEventsReport']=='event_volunteers_report') {
        // Events List checkbox options
        $reportMeta = ReportEventVolunteers::GetDefaultReportRecForDownload();
        $excludeAnalyticMetaFields = json_encode(ReportEventVolunteers::GetMetadataForAnalytics()['ExludeFields']);
        $volunteer_fields = array();
        $reportType = array();
        if ($reportMeta) {
            $volunteer_fields = $reportMeta['Fields'];
            if (!empty($reportMeta['AdminFields'])) {
                $volunteer_fields = array_merge($volunteer_fields, $reportMeta['AdminFields']);
            }
        }
        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
            unset($fields['chaptername']);
            unset($fields['enc_chapterid']);
        }

        if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
            unset($fields['channelname']);
            unset($fields['enc_channelid']);
        }
        include(__DIR__ . "/views/templates/event_volunteer_report.template.php");
    }
}
elseif (isset($_GET['budgetReportsModal'])) {
    // Authorization Check  
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    // Get the years
    $budgetYears = Budget2::GetCompanyBudgetYears();
    $currentBudgetYearId = Budget2::GetBudgetYearIdByDate($_USER->getLocalDateNow());
    // include the view
    include(__DIR__ . "/views/templates/budget_report_by_year.template.php");

}
elseif (isset($_GET['expenseReportsModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
 
   if (!$_USER->canManageZoneBudget()) {
     header(HTTP_FORBIDDEN);
     exit();
     }
     
    // Get groups
    $group = $db->ro_get("SELECT `groupid`, `groupname` FROM `groups` WHERE `companyid`='{$_COMPANY->id()}' AND (`zoneid`='{$_ZONE->id()}' AND `isactive`='1')");

    // Get the years
    $budgetYears = Budget2::GetCompanyBudgetYears();
    $currentBudgetYearId = Budget2::GetBudgetYearIdByDate($_USER->getLocalDateNow());
    $reportMeta = ReportBudget::GetDefaultReportRecForDownload();
    $excludeAnalyticMetaFields = json_encode(ReportBudget::GetMetadataForAnalytics()['ExludeFields']);
    // include the view
    include(__DIR__ . "/views/templates/expense_report_modal.template.php");
 
 }
 elseif (isset($_GET['download_expense_report'])){
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (($budget_year_id = $_COMPANY->decodeId($_POST['budget_year']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $groupIds = array();
    if(!empty($_POST['s_group'])){
        foreach($_POST['s_group'] as $g){
            array_push($groupIds,$_COMPANY->decodeId($g));
        }
    }

    $reportMeta = ReportBudget::GetDefaultReportRecForDownload();

    if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
        unset($reportMeta['Fields']['chaptername']);
        unset($reportMeta['Fields']['enc_chapterid']);
    }
    if (!$_COMPANY->getAppCustomization()['budgets']['other_funding']) {
        unset($reportMeta['Fields']['funding_source']);
    }
    $reportMeta['Fields']['groupname'] = $_COMPANY->getAppCustomization()['group']["name-short"];

    $reportMeta['Filters'] = array(
        'groupids' => $groupIds,
        'chapterids' => array(),
        'year' => $budget_year_id
    );

    $reportMeta['Options']['topictype'] = 'EXP';
    $reportMeta['Options']['includeCustomFields'] = 1;

    $reportAction = $_POST['reportAction'];
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'expense';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportBudget ($_COMPANY->id(),$record);

    if ($reportAction=='download'){
        $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
        #else echo false
        echo false;
    } else {
        $title = gettext("Expense/Spend Reports Analytics");
        $_SESSION['analytics_data'] = $report->generateAnalyticData($title);
        Http::Redirect("analytics");
    }
    exit();
}
elseif (isset($_GET['downloadRegionList'])) {
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
            header(HTTP_FORBIDDEN);
            exit();
    }

    $reportMeta = ReportRegions::GetDefaultReportRecForDownload();

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'office_locations';
    $record['reportmeta'] = json_encode($reportMeta);


    $report = new ReportRegions ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}

elseif (isset($_GET['downloadUsersReport'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Authorization Check
    if (!$_USER->canManageCompanySettings() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $groups = $db->get("SELECT groupid,groupname FROM `groups` WHERE `companyid`='{$_COMPANY->id()}' AND (zoneid='{$_ZONE->id()}' AND isactive='1')");

    // Company Customized inpts for Reports
    $reportMeta = ReportUserMembership::GetDefaultReportRecForDownload();
    $excludeAnalyticMetaFields = json_encode(ReportUserMembership::GetMetadataForAnalytics()['ExludeFields']);
    $fields = array();
    $reportType = array();

    if ($reportMeta) {
        $fields = $reportMeta['Fields'];

        if (!empty($reportMeta['AdminFields'])) {
            $fields = array_merge($fields, $reportMeta['AdminFields']);
        }
    }

    if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
        unset($fields['chaptername']);
        unset($fields['enc_chapterid']);
    }

    if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
        unset($fields['channelname']);
        unset($fields['enc_channelid']);
    }

    if (!$_COMPANY->isConnectEnabled()) {
        unset($fields['connectemail']);
    }

    $cpuUsageMessage = "Note: Selecting too many fields may slow down your system due to high CPU and memory usage for data analysis. So please choose the least fields for analytics for better performance.";

    //set the type of report to download
    if ($_GET['downloadUsersReport']=='members') {
        $select_type = 'members';
        unset($fields['role']);
        $report_label = 'Members';        
        unset($fields['enc_groupleaderid']);
        unset($fields['enc_chapterleaderid']);
        unset($fields['enc_channelleaderid']);
    }

    if ($_GET['downloadUsersReport']=='groupleads') {
        $select_type = 'groupleads';
        $report_label = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Leaders';
        unset($fields['chaptername']);
        unset($fields['enc_chapterid']);
        unset($fields['channelname']);
        unset($fields['enc_channelid']);
        unset($fields['role']);
        unset($fields['enc_groupmemberid']);
        unset($fields['enc_chapterleaderid']);
        unset($fields['enc_channelleaderid']);
    }
    if ($_GET['downloadUsersReport']=='chapterleads') {
        $select_type = 'chapterleads';
        $report_label = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' Leaders';
        unset($fields['channelname']);
        unset($fields['enc_channelid']);
        unset($fields['role']);
        unset($fields['enc_groupmemberid']);
        unset($fields['enc_groupleaderid']);
        unset($fields['enc_channelleaderid']);
    }
    if ($_GET['downloadUsersReport']=='channelleads') {
        $select_type = 'channelleads';
        $report_label = $_COMPANY->getAppCustomization()['channel']['name-short'] . ' Leaders';
        unset($fields['chaptername']);
        unset($fields['enc_chapterid']);
        unset($fields['role']);
        unset($fields['enc_groupmemberid']);
        unset($fields['enc_groupleaderid']);
        unset($fields['enc_chapterleaderid']);
    }
    if ($_GET['downloadUsersReport']=='nonmembers') {
        $select_type = 'nonmembers';
        $report_label = 'Non Members';
        unset($fields['role']);
        unset($fields['rolename']);
        unset($fields['since']);
        unset($fields['groupname']);
        unset($fields['enc_groupid']);
        unset($fields['chaptername']);
        unset($fields['enc_chapterid']);
        unset($fields['channelname']);
        unset($fields['enc_channelid']);
        unset($fields['groupcategory']);
        unset($fields['enc_groupmemberid']);
        unset($fields['enc_groupleaderid']);
        unset($fields['enc_chapterleaderid']);
        unset($fields['enc_channelleaderid']);
    }

    if ($_GET['downloadUsersReport']=='uniquemembers') {
        $select_type = 'uniquemembers';
        $report_label = 'Unique Members';
        unset($fields['role']);
        unset($fields['rolename']);
        unset($fields['since']);
        unset($fields['chaptername']);
        unset($fields['enc_chapterid']);
        unset($fields['channelname']);
        unset($fields['enc_channelid']);
        unset($fields['groupcategory']);
        unset($fields['enc_groupleaderid']);
        unset($fields['enc_chapterleaderid']);
        unset($fields['enc_channelleaderid']);
    }

    if ($_GET['downloadUsersReport']=='allleads') {
        $select_type = 'allleads';
        $report_label = 'All Leaders';
        unset($fields['enc_groupmemberid']);
    }
    //include the modal
    include(__DIR__ . "/views/templates/user_member_report.template.php");
}

elseif (isset($_GET['downloadZoneUsersReportModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Authorization Check
    if (!$_USER->canManageCompanySettings() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    // Company Customized inpts for Reports
    $reportMeta = ReportUsers::GetDefaultReportRecForDownload();
    $excludeAnalyticMetaFields = json_encode(ReportUsers::GetMetadataForAnalytics()['ExludeFields']);
    $fields = array();
    $reportType = array();

    if ($reportMeta) {
        $fields = $reportMeta['Fields'];

        if (!empty($reportMeta['AdminFields'])) {
            $fields = array_merge($fields, $reportMeta['AdminFields']);
        }
    }
    $cpuUsageMessage = "Note: Selecting too many fields may slow down your system due to high CPU and memory usage for data analysis. So please choose the least fields for analytics for better performance.";
    $report_label = 'Zone Users';

    //include the modal
    include(__DIR__ . "/views/templates/zone_users_report.template.php");
}

elseif (isset($_GET['downloadAdminsReport']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Authorization Check
    if (!$_USER->canManageCompanySettings() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportAdmins::GetDefaultReportRecForDownload();
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'admins';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportAdmins ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}

elseif (isset($_GET['downloadLeadTypeReport']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportLeadType::GetDefaultReportRecForDownload();
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'Lead Type';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportLeadType ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}

elseif (isset($_GET['downloadExpenseTypeReport'])){
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportBudgetExpenseType::GetDefaultReportRecForDownload();
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'Expense Type';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportBudgetExpenseType ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}


##
elseif (isset($_GET['downloadEventTypeReport']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportEventType::GetDefaultReportRecForDownload();
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'Event Type';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportEventType ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}
elseif (isset($_GET['loginReportsModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $timezone = @$_SESSION['timezone'];
    $isEnabled=false;

        $startDate = date("Y-m-d");
        $loginReportType = "";
        $appName = ucwords($_ZONE->val('app_type'));
        if($_GET['loginReportsModal'] == 'native_usage_report'){
            $loginReportType = "native";
            $appName = 'Mobile Application';
        } elseif ($_GET['loginReportsModal'] == 'email_connection_report') {
            $loginReportType = "email";
            $appName = 'Email In-Connection';
        }

        $reportMeta = ReportLogins::GetDefaultReportRecForDownload();
        $excludeAnalyticMetaFields = json_encode(ReportLogins::GetMetadataForAnalytics()['ExludeFields']);

        $fields = array();
        $reportType = array();
        if ($reportMeta) {
            $fields = $reportMeta['Fields'];
            if (!empty($reportMeta['AdminFields'])) {
                $fields = array_merge($fields, $reportMeta['AdminFields']);
            }
        }
        $cpuUsageMessage = "Note: Selecting too many fields may slow down your system due to high CPU and memory usage for data analysis. So please choose the least fields for analytics for better performance.";
        include(__DIR__ . "/views/templates/web_login_report.template.php");
        
}
## For graph Reports
elseif (isset($_GET['usersGraphReport']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $pagetitle = "Members Report";
    $selectedDatetime = time();
    if (isset($_GET['yrFilter']) && is_numeric($_GET['yrFilter']) && date("Y")>$_GET['yrFilter']){
        $selectedDatetime =  strtotime('12/31/'. $_GET['yrFilter']);
    }
    // Set the report title
    $year = date("Y",$selectedDatetime);
    $lastyear = $year-1;
    $_month = date("n",$selectedDatetime);
    $month = date("m",$selectedDatetime);
    $day = date("d",$selectedDatetime);
    $monthName = date("M",$selectedDatetime);

    // check for group categories
    $groupCategoryRows = Group::GetAllGroupCategories(true);
    $groupCategoryIds = array_column($groupCategoryRows, 'categoryid');

    if (isset($_GET['filter']) && in_array($_GET['filter'],$groupCategoryIds)){
        $group_category_id = (int)$_GET['filter'];
    } else {
        $group_category_id = (int)Group::GetDefaultGroupCategoryRow()['categoryid'];
    }

    $groups = $db->ro_get("SELECT `groupid`, `companyid`, `regionid`, `addedby`, `groupname_short`, `aboutgroup`, `coverphoto`, `overlaycolor` FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND zoneid={$_ZONE->id()}  and `isactive`=1 AND categoryid = {$group_category_id}");
    $newmembers					= array();
    $totalmembers				= array();
    $monthlygrowth				= array();
    $region						= array();
    $regionalmembers			= array();
    $lastYearMemerCount =    "";

    if (count($groups)){

        $region = array_merge($db->ro_get("SELECT `regionid`, `region` FROM `regions` WHERE `companyid`={$_COMPANY->id()} AND `isactive`=1"),[['regionid'=>'0','region'=>'Undefined']]);
        $db->ro_get("SET SESSION group_concat_max_len = 1024000");
        for($count=0; $count < count($region); $count++) {
            $branches = $db->ro_get("SELECT GROUP_CONCAT(branchid) AS branches FROM companybranches WHERE regionid={$region[$count]['regionid']} AND `companyid`={$_COMPANY->id()} AND `isactive`=1");
            if ($region[$count]['regionid'] === "0") {
                if (empty($branches[0]['branches'])) {
                    $region[$count]['branches'] = "0,0";
                } else {
                    $region[$count]['branches'] = $branches[0]['branches'].",0";
                }
            } else {
                $region[$count]['branches'] = $branches[0]['branches'];
            }
        }
        for($rg=0;$rg<count($groups);$rg++){
            //New Members per ERG
            $std = array("-01-01 00:00:00","-02-01 00:00:00","-03-01 00:00:00","-04-01 00:00:00","-05-01 00:00:00","-06-01 00:00:00","-07-01 00:00:00","-08-01 00:00:00","-09-01 00:00:00","-10-01 00:00:00","-11-01 00:00:00","-12-01 00:00:00");
            $edd = array("-01-31 23:59:59","-02-28 23:59:59","-03-31 23:59:59","-04-30 23:59:59","-05-31 23:59:59","-06-30 23:59:59","-07-31 23:59:59","-08-31 23:59:59","-09-30 23:59:59","-10-31 23:59:59","-11-30 23:59:59","-12-31 23:59:59");
            $newmemercount = array();
            //$newmemb = $db->ro_get("SELECT count(`memberid`) as total FROM `groupmembers` JOIN users USING (userid) WHERE `groupid` = '".$groups[$rg]['groupid']."' and YEAR(`groupjoindate`)='".$year."' and  (`groupjoindate` < '".$lastyear."-12-31 23:59:59') AND users.isactive=1 AND groupmembers.isactive=1");
            //$newmemercount[] = $newmemb[0]['total'];
            for($mm = 0;$mm<12;$mm++){
                $startd = $year.$std[$mm];
                $endd = $year.$edd[$mm];
                $newmemb = $db->ro_get("SELECT count(`memberid`) as total FROM `groupmembers` JOIN users USING (userid) WHERE `groupid` = '".$groups[$rg]['groupid']."' and  (`groupjoindate` BETWEEN '".$startd."' AND '".$endd."') AND users.isactive=1 AND groupmembers.isactive=1");
                $newmemercount[] = $newmemb[0]['total'];                
            }
            
            $lastYearMemberCount = $db->ro_get("SELECT count(`memberid`) as total FROM `groupmembers` JOIN users USING (userid) WHERE `groupid` = '".$groups[$rg]['groupid']."' and  (year(`groupjoindate`) < {$year}) AND users.isactive=1 AND groupmembers.isactive=1");
            $lastYearMemerCount = $lastYearMemberCount[0]['total'];
            
            $newmembers[] = array('groupname_short'=>$groups[$rg]['groupname_short'],'newmember'=>$newmemercount,'color'=>$groups[$rg]['overlaycolor'],'lastYearMemerCount'=>$lastYearMemerCount);

         

            // Total members per ERG
            $totmemb = $db->ro_get("SELECT count(`memberid`) as total FROM `groupmembers` LEFT JOIN users USING (userid) WHERE `groupid` = '".$groups[$rg]['groupid']."' and YEAR(`groupjoindate`)<='".$year."' AND users.isactive=1 AND groupmembers.isactive=1");
            $totalmembers[] = array('groupname_short'=>$groups[$rg]['groupname_short'],'totalmembers'=>$totmemb[0]['total'],'color'=>$groups[$rg]['overlaycolor']);

            // Group members by Region
            // foreach region, calculate group members
            $rmc = array();
            foreach ($region as $r1) {
                if(empty($r1['branches'])) {
                    $rmc[] = 0;
                } else {
                    $rm = $db->ro_get("SELECT count(`memberid`) AS total FROM `groupmembers` g JOIN `users` u ON g.userid=u.userid WHERE g.groupid = '".$groups[$rg]['groupid']."' and YEAR(`groupjoindate`)<='".$year."' AND u.homeoffice IN (".$r1['branches'].") AND u.isactive=1 AND g.isactive=1");
                    $rmc[] = $rm[0]['total'];
                }
            }
            // and save the regional member count vector by group
            $regionalmembers[] = array('groupname_short'=>$groups[$rg]['groupname_short'],'regionalmembers'=>$rmc);
        } // end of `groups`for loop

    }

    include(__DIR__ . "/views/membership_reporting.html");

}
// events Graph Report
## For graph Reports
elseif (isset($_GET['eventsGraphReport'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    // Set the report title
    $pagetitle = "Events List Report";

    if (isset($_GET['d']) && is_numeric($_GET['d']) && date("Y")>$_GET['d']){
        $date = date('Y-m-d', strtotime('12/31/'. $_GET['d']));
        $year = date("Y",strtotime($date));
        $lastyear = $year-1;

        // Setting the month filter
        $_month = isset($_GET['m']) && is_numeric($_GET['m']) && $_GET['m'] >= 1 && $_GET['m'] <= 12 ? (int)$_GET['m'] : date("n",strtotime($date));

        $month = str_pad($_month, 2, "0", STR_PAD_LEFT);
        $monthName = date("F",mktime(0,0,0,$month,1));
        $day = date("d",strtotime($date));
    } else {
        $year = date("Y");

      // Setting the month filter
      $_month = isset($_GET['m']) && is_numeric($_GET['m']) && $_GET['m'] >= 1 && $_GET['m'] <= 12 ? (int)$_GET['m'] : date("n");

      $month = str_pad($_month, 2, "0", STR_PAD_LEFT);
        $day = date("d");
        $monthName = date("F",mktime(0,0,0,$month,1));
        $lastyear = $year-1;
    }

    // check for group categories
    $groupCategoryRows = Group::GetAllGroupCategories(true);
    $groupCategoryIds = array_column($groupCategoryRows, 'categoryid');

    if (isset($_GET['filter']) && is_array($_GET['filter'])){
        $group_category_id =  array_map('intval', array_intersect($_GET['filter'], $groupCategoryIds));
    }else {
        $group_category_id = [(int)Group::GetDefaultGroupCategoryRow()['categoryid']];
    }

    $groupCategoryFilter = $group_category_id ? " AND categoryid IN(".implode(',', $group_category_id).")" : "";
    
    $groups = $db->ro_get("SELECT `groupid`, `companyid`, `regionid`, `groupname_short`, `overlaycolor` FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} $groupCategoryFilter AND `isactive`=1 ");

    $eventCategories = Event::GetEventTypesByZones([$_ZONE->id()]);

    $categoresNames = [];
    if (count($eventCategories)){
        $categoresNames = array_column($eventCategories,'type');
    }

    $eventsByGroups             = array();
    $eventsByCategories         = array();
    $rsvpByGroups               = array();
    $rsvpByCategories           = array();


    if (count($groups)){
        $groupids = implode(',',array_column($groups,'groupid'));

        if(!empty($_GET['m']) && is_numeric($_GET['m'])) {
            $date = new DateTime("{$year}-{$_GET['m']}-01");
            $lastDayOfMonth = $date->format('Y-m-t');
            $dateCondition = "AND `start` <= '{$lastDayOfMonth}'";
        }else{ 
            $dateCondition = " AND YEAR(`start`) <= {$year}";
        };
        $allEvents = $db->ro_get("SELECT groupid,eventtype,COUNT(1) as totalEvents FROM `events` WHERE `groupid` IN ({$groupids}) AND `companyid`={$_COMPANY->id()}  $dateCondition AND `isactive`=1  GROUP BY groupid,eventtype");

        foreach ($groups as $group) {
            $gid = $group['groupid'];
            $eventsByCat = array();

            $filteredRows = array_filter($allEvents,function ($value) use ($gid) {
                return ($value['groupid'] == $gid);
            });

            foreach ($eventCategories as $category) {
                $catEvents = Arr::SearchColumnReturnColumnVal($filteredRows, $category['typeid'], 'eventtype', 'totalEvents') ?: 0;
                $eventsByCat[] = ['category'=>$category['type'],'totalEvent'=>$catEvents];
            }

            $totalEventsInGroup = array_sum(array_column($filteredRows,'totalEvents'));
            $eventsByGroups[]  = ['groupname_short'=>$group['groupname_short'],'totalEvent'=>$totalEventsInGroup,'color'=>$group['overlaycolor']];
            $eventsByCategories[] = ['groupname_short'=>$group['groupname_short'],'categories'=>$eventsByCat,'color'=>$group['overlaycolor']];
        }
    }
        include_once(__DIR__ . '/views/event_reporting.html');
}
elseif (isset($_GET['eventsRSVPReport'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    // Set the report title
    $pagetitle = "Events RSVP Report";

    if (isset($_GET['d']) && is_numeric($_GET['d']) && date("Y")>$_GET['d']){
        $date = date('Y-m-d', strtotime('12/31/'. $_GET['d']));
        $year = date("Y",strtotime($date));
        $lastyear = $year-1;
        $_month = date("n",strtotime($date));
        $month = date("m",strtotime($date));
        $day = date("d",strtotime($date));
        $monthName = date("M",strtotime($date));
    } else {
        $year = date("Y");
        $_month = date("n");
        $month = date("m");
        $day = date("d");
        $monthName = date("M");
        $lastyear = $year-1;
    }

    $section = "1";
    if (isset($_GET['section'])){
        $section = $_COMPANY->decodeId($_GET['section'] );
    }

    $groupid = 0;
    if (isset($_GET['groupid'])){
        $groupid = $_COMPANY->decodeId($_GET['groupid']);
    }

    // check for group categories
    $groupCategoryRows = Group::GetAllGroupCategories(true);
    $groupCategoryIds = array_column($groupCategoryRows, 'categoryid');

    if (isset($_GET['filter']) && is_array($_GET['filter'])){
        $group_category_id =  array_map('intval', array_intersect($_GET['filter'], $groupCategoryIds));
    }else {
        $group_category_id = [(int)Group::GetDefaultGroupCategoryRow()['categoryid']];
    }

    $groupCategoryFilter = $group_category_id ? " AND categoryid IN(".implode(',', $group_category_id).")" : "";

    $groups = $db->ro_get("SELECT `groupid`, `groupname_short` FROM `groups` WHERE `companyid`={$_COMPANY->id()} AND zoneid={$_ZONE->id()} $groupCategoryFilter AND `isactive`=1");
    usort($groups,function($a,$b) {
        // Sort groups alphabetically
        return strcmp($a['groupname_short'], $b['groupname_short']);
    });
    $departmentid = 0;
    $departments = $_COMPANY->getAllDepartments();

    if (isset($_GET['departmentid'])){
        $departmentid = $_COMPANY->decodeId($_GET['departmentid']);
    }

    $groupids = 0;
    if ($groupid>0){
        $groupids = $groupid;
    } else {
        if(count($groups)){
            $groupids = implode(',',array_column($groups,'groupid'));
        }
    }

    $rsvpNo = Event::RSVP_TYPE['RSVP_NO'];

    $departmentCondition = "";
    if ($departmentid){
        $departmentCondition = " JOIN users ON users.userid=eventjoiners.userid AND users.department='".$departmentid."' ";
    }

    $rows = $db->ro_get("SELECT MONTH(start) AS `month`, count(distinct IF(joinstatus!={$rsvpNo},eventjoiners.userid,-1)) AS unique_rsvps, SUM(IF(joinstatus!={$rsvpNo},1,0)) as total_rsvps, count(distinct(eventjoiners.eventid)) as total_events, sum(IF(checkedin_date,1,0)) as total_attendees, count(distinct IF(checkedin_date,eventjoiners.userid,-1)) as unique_attendees, count(1) as totals_rows  FROM `eventjoiners` LEFT JOIN events USING (eventid) {$departmentCondition} WHERE YEAR(start)='{$year}' AND groupid IN ({$groupids}) GROUP BY MONTH(start)");

    foreach ($rows as &$row) {
        $row['unique_attendees'] = $row['unique_attendees'] ? $row['unique_attendees'] - 1 : 0; // If set, reduce by one as count distinct will provide one additional
        $row['average_rsvps'] = round($row['total_rsvps'] / $row['total_events'], 0);
        $row['average_attendees'] = round($row['total_attendees'] / $row['total_events'], 0);
    }
    unset($row);

    // Print the charts
    include(__DIR__ . '/views/usage_report_event_participants.html');

}
elseif (isset($_GET['expenseGraphReportModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header("HTTP/1.1 403 Forbidden (Access Denied)");
        echo "403 Forbidden (Access Denied)";
        exit();
    }
    
    // Set the report title
    $pagetitle = "Expense Report";

    $eventTypes = [];
    $expenseTypes = [];
    $aggregatedExpenseTypes = [];
    $monthlyExpenses = [];
    $selectedBudgetYear = null;
    $date = $_USER->getLocalDateNow();
    $currentBudgetYearId = Budget2::GetBudgetYearIdByDate($date);
    $yearId = empty($_GET['yearFilter']) ? $currentBudgetYearId : $_COMPANY->decodeId($_GET['yearFilter']);

    // Set date to end of the budget year if selected budget year is not current date year.
    if ($yearId !== $currentBudgetYearId) {
        $selectedBudgetYear = Budget2::GetCompanyBudgetYearDetail($yearId);
        $date = date('Y-m-d', strtotime($selectedBudgetYear['budget_year_end_date']));
    }

    $year = date("Y",strtotime($date));
    $lastyear = $year-1;
    $_month = date("n",strtotime($date));
    $month = date("m",strtotime($date));
    $day = date("d",strtotime($date));
    $monthName = date("M",strtotime($date));
    $budgetYears = Budget2::GetCompanyBudgetYears();
    
    if (!empty($budgetYears)){
    
        // check for group categories
        $groupCategoryRows = Group::GetAllGroupCategories(true);
        $groupCategoryIds = array_column($groupCategoryRows, 'categoryid');
    
        if (isset($_GET['filter']) && in_array($_GET['filter'],$groupCategoryIds)){
            $group_category_id = (int)$_GET['filter'];
        } else {
            $group_category_id = (int)Group::GetDefaultGroupCategoryRow()['categoryid'];
        }
    
        //Company Budget
        $companyBudgetObj = Budget2::GetBudget($yearId);
        $expenseRows = $companyBudgetObj->getItemLevelExpenseRows();
        $totalExpense = Arr::SumColumnValues($expenseRows,'expense_amount');

        // Organize expenses by groups
        $expenseRowsByGroup = Arr::GroupBy($expenseRows, 'groupid');
        $grps = Group::GetAllGroupsByZones([$_ZONE->id()], [$group_category_id], false);
        $groups = array();
        if(!empty($grps)){
            foreach ($grps as $grp) {
                $grpExpenses = 0;
                if (array_key_exists($grp['groupid'], $expenseRowsByGroup)) {
                    $grpExpenses = Arr::SumColumnValues($expenseRowsByGroup[$grp['groupid']], 'expense_amount');
                }
    
                $groups[] = array(
                    'groupid' => $grp['groupid'],
                    'regionid' => $grp['regionid'],
                    'groupname_short' => $grp['groupname_short'],
                    'overlaycolor' => $grp['overlaycolor'],
                    'totalExpenses' => $grpExpenses
                );
            }
            // Add any other unaccounted expeses as other.
            $totalExpensesAccountedForByGroups = Arr::SumColumnValues($groups,'totalExpenses');
            $otherGroupsExpenses = $totalExpense - $totalExpensesAccountedForByGroups;
            if ($otherGroupsExpenses) {
                $groups[] = array(
                    'groupid' => 0,
                    'regionid' => 0,
                    'groupname_short' => 'Unknown Group',
                    'overlaycolor' => 'rgb(0,0,0)',
                    'totalExpenses' => $otherGroupsExpenses
                );
            }
    
            // Organize expenses by Event Types
            $expenseRowsByEventType = Arr::GroupBy($expenseRows, 'eventtype');
            $eventTypes = array();
            foreach ($expenseRowsByEventType as $evType => $evTypeRows) {
                $evTypeExpenses = Arr::SumColumnValues($evTypeRows, 'expense_amount');
                $eventTypes[] = array(
                    'eventtype' => $evType ?: 'Not Set',
                    'total_used_amount' => $evTypeExpenses
                );
            }
    
            // Organize expenses by Expense types
            $expenseRowsByExpenseType = Arr::GroupBy($expenseRows, 'expensetypeid');
            $aggregatedExpenseTypes = array();
            $expenseTypesByZone = Budget2::GetBudgetExpenseTypes();
            foreach ($expenseRowsByExpenseType as $expType => $expTypeRows) {
                $expTypeExpenses = Arr::SumColumnValues($expTypeRows, 'expense_amount');
                $expTypeLabel = Arr::SearchColumnReturnColumnVal($expenseTypesByZone, $expType, 'expensetypeid', 'expensetype') ?: 'Unknown';
                $aggregatedExpenseTypes[] = array(
                    'type' => $expTypeLabel,
                    'item_used_amount' => $expTypeExpenses
                );
            }
    
            // Organize expenses by Month
            $monthlyExpensesTemp = array();
            foreach ($expenseRows as $expenseRow) {
                $expense_year_month = (int)date('m', strtotime($expenseRow['expense_date']. ' UTC'));
                $monthlyExpensesTemp[$expense_year_month] = ($monthlyExpensesTemp[$expense_year_month] ?? 0) + $expenseRow['expense_amount'];
            }
            foreach ($monthlyExpensesTemp as $k => $v) {
                $monthlyExpenses[] = array(
                    'month' => $k,
                    'total_used_amount' => $v,
                );
            }   
        }
    }
    include_once(__DIR__ . '/views/expense_reporting.html');
}

elseif (isset($_GET['budgetGraphReportModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){

// Authorization Check
if (!$_USER->canManageZoneBudget()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	echo "403 Forbidden (Access Denied)";
	exit();
}

// Set the report title
$pagetitle = "Budget Report";

$selectedBudgetYear = null;
$date = $_USER->getLocalDateNow();
$currentYearId  = Budget2::GetBudgetYearIdByDate($date);
$yearId  = $currentYearId;
if (!empty($_GET['d'])){
	$yearId = $_COMPANY->decodeId($_GET['d']);
	$selectedBudgetYear = Budget2::GetCompanyBudgetYearDetail($yearId);
	$date = date('Y-m-d', strtotime($selectedBudgetYear['budget_year_end_date']));
	if ($selectedBudgetYear['budget_year_id'] == $currentYearId  ){
		$date = $_USER->getLocalDateNow();
	}
	$year = date("Y",strtotime($date));
	$lastyear = $year-1;
	$_month = date("n",strtotime($date));
	$month = date("m",strtotime($date));
	$day = date("d",strtotime($date));
	$monthName = date("M",strtotime($date));
} else {
	$year = date("Y",strtotime($date));
	$lastyear = $year-1;
	$_month = date("n",strtotime($date));
	$month = date("m",strtotime($date));
	$day = date("d",strtotime($date));
	$monthName = date("M",strtotime($date));
}
$budgetYears = Budget2::GetCompanyBudgetYears();

if (!empty($budgetYears)){

    // check for group categories
    $groupCategoryRows = Group::GetAllGroupCategories(true);
    $groupCategoryIds = array_column($groupCategoryRows, 'categoryid');

    if (isset($_GET['filter']) && in_array($_GET['filter'],$groupCategoryIds)){
        $group_category_id = (int)$_GET['filter'];
    } else {
        $group_category_id = (int)Group::GetDefaultGroupCategoryRow()['categoryid'];
    }

	//Company Budget
	$companyBudgetObj = Budget2::GetBudget($yearId);
	$companybudget = intval($companyBudgetObj->getTotalBudget());
	$spentbyregion 				= array();
	$quarterlyspend 			= array();

	$groups = array();
	$totalAlloc = 0;
	$regionalUse = array();
	foreach ($companyBudgetObj->getChildBudgets() as $g) {
		$totalBudget = intval($g->getTotalBudget());
		$totalExpenses = intval($g->getTotalExpenses()['spent_from_allocated_budget']);
		$totalBudgetAvailable = intval($g->getTotalBudgetAvailable());
		$percent = ($companybudget) ? round($totalBudget*100/$companybudget,0) : 0;
		$grp = Group::GetGroup($g->val('groupid'));
		if (($grp->val('categoryid') == $group_category_id) &&
			($grp->val('isactive') == 1 || $totalBudget || $totalExpenses)) {
			$totalAlloc += $totalBudget;
			$groups[] = array(
				'groupid' => $grp->val('groupid'),
				'regionid' => $grp->val('regionid'),
				'groupname_short' => $grp->val('groupname_short'),
				'overlaycolor' => $grp->val('overlaycolor'),
				'isactive' => $grp->val('isactive'),
				'totalBudget' => $totalBudget,
				'totalExpenses' => $totalExpenses,
				'percent' => $percent,
				);

			// Split budget by regions as well.
            // Step 1, distribute chapter budget to their regions
			$chapterAndChannelBudgets = $g->getChildBudgets();
			foreach ($chapterAndChannelBudgets as $cg) {
				if (isset($regionalUse[$cg->val('regionids')])) {
					$regionalUse[$cg->val('regionids')] += intval($g->getTotalBudget());
				} else {
					$regionalUse[$cg->val('regionids')] = intval($g->getTotalBudget());
				}
			}
            // Step 2, move any group level budget to groups in the region
			if ($totalBudgetAvailable) {
				$groupRegions = explode(',', $g->val('regionids'));
				$countGR = count($groupRegions);
				foreach ($groupRegions as $r1) {
					if (isset($regionalUse[$r1])) {
						$regionalUse[$r1] += intval($totalBudgetAvailable/$countGR);
					} else {
						$regionalUse[$r1] = intval($totalBudgetAvailable/$countGR);
					}
				}
			}
		}
	}

	if (count($groups)){

		if (0){
			//Quarterly spend per ERG
			for($rg=0;$rg<count($groups);$rg++){
				$st = array("-01-01","-04-01","-07-01","-10-01");
				$ed = array("-03-31","-06-30","-09-30","-12-31");
				$usedamount = array();
				for($sp = 0;$sp<4;$sp++){
					$start = $year.$st[$sp];
					$end = $year.$ed[$sp];
					$spend = $db->ro_get("SELECT IFNULL(sum(`usedamount`),0) as `usedamount` FROM `budgetuses` WHERE `groupid`='".$groups[$rg]['groupid']."' and  `budget_year_id`='".$yearId."' ");
					$usedamount[] = $spend[0]['usedamount'];

				}
				$quarterlyspend[] = array('groupname_short'=>$groups[$rg]['groupname_short'],'spend'=>$usedamount);

			} // end of `groups`for loop
		}

		##Budget Percentage By Regions
		$regions = $db->ro_get("SELECT `regionid`, `region` FROM `regions` WHERE `companyid`={$_COMPANY->id()} AND `isactive`='1'");

		$totalalc = 0;
		foreach ($regions as $region) {
			$aloamt = intval($regionalUse[$region['regionid']] ?? 0);
			$totalalc += $aloamt;
			$spentbyregion[] = array('region'=>$region['region'],'allocated'=>$aloamt,'percent'=>($companybudget ? round(($aloamt*100/$companybudget),0) : 0));
		}

		if($companybudget>$totalalc){
			$spentbyregion[] = array('region'=>'Not allocated','allocated'=>($companybudget-$totalalc),'percent'=>($companybudget ? round((($companybudget-$totalalc)*100/$companybudget),0) : 0));
		}

	}

	if ($companyBudgetObj->getTotalBudgetAvailable()) {
		$groups[] = array('groupid'=>-1,'groupname_short'=>'Not allocated','totalBudget'=>intval($companyBudgetObj->getTotalBudgetAvailable()),'totalExpenses'=>0,'percent'=>round((($companyBudgetObj->getTotalBudgetAvailable())*100/$companybudget),0),'overlaycolor'=>'#636262');
	}
	$totalUnaccounted = $companybudget - ($totalAlloc + $companyBudgetObj->getTotalBudgetAvailable());
	if ($totalUnaccounted) {
		$groups[] = array('groupid'=>-2,'groupname_short'=>'Other','totalBudget'=>intval($totalUnaccounted),'totalExpenses'=>0,'percent'=>($companybudget ? round((($totalUnaccounted)*100/$companybudget),0) : 0),'overlaycolor'=>'#bcbcbc');
	}
}
    include_once(__DIR__ . '/views/budget_reporting.html');
}

elseif (isset($_GET['loginReportGraphModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (isset($_GET['d']) && is_numeric($_GET['d']) && date("Y")>$_GET['d']){
        $date = date('Y-m-d', strtotime('12/31/'. $_GET['d']));
        $year = date("Y",strtotime($date));
        $lastyear = $year-1;
        $_month = date("n",strtotime($date));
        $month = date("m",strtotime($date));
        $day = date("d",strtotime($date));
        $monthName = date("M",strtotime($date));
    } else {
        $year = date("Y");
        $_month = date("n");
        $month = date("m");
        $day = date("d");
        $monthName = date("M");
        $lastyear = $year-1;
    }

    $loginData = array();
    $emailRsvpsData = array();
    $app_type = $_ZONE->val('app_type');
    $appName = ucwords($_ZONE->val('app_type'));
    if ($_GET['loginReportGraphModal']=='native_usage_report') {
        $app_type = 'native';
        $appName = 'Mobile Application';
    } elseif ($_GET['loginReportGraphModal']=='email_connection_report') {
        $app_type = 'email';
        $appName = 'Email Application';
    }
    $pagetitle = "Login Report - {$appName}";
    $rows = $db->ro_get("SELECT  MONTH(`usagetime`) as `month`, sum(IF(usageif='{$app_type}',1,0)) as login from appusage WHERE companyid={$_COMPANY->id()} AND YEAR(`usagetime`)={$year} GROUP BY MONTH(`usagetime`)");
    include(__DIR__ . '/views/usage_report_interactions.html');
}
## OK
elseif (isset($_GET['deleteEventVoluntterType']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (($volunteertypeid = $_COMPANY->decodeId($_GET['volunteertypeid']))<1 || 
        ($type = Event::GetEventVolunteerType($volunteertypeid)) === NULL
        
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
	$action = (string)$_GET['action'];
    Event::DeleteOrUndoDeleteEventVolunteerType($volunteertypeid,$action);
    $_SESSION['updated']= time();
	Http::Redirect("event_volunteer_types");
}

elseif (isset($_GET['getTeamsJoinRequestSurveyReportOptions']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $reportMeta = ReportTeamRegistrations::GetDefaultReportRecForDownload();
    $fields = array();
    if ($reportMeta) {
        $fields = $reportMeta['Fields'];
        if (!empty($reportMeta['AdminFields'])) {
            $fields = array_merge($fields, $reportMeta['AdminFields']);
        }
    }
    include(__DIR__ . "/views/team_join_request_survey_download_options.html");
    exit();
}

elseif (isset($_GET['downloadTeamMemberJoinSurveyResponses']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    set_time_limit(120);
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['downloadTeamMemberJoinSurveyResponses'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($uid = $_COMPANY->decodeId($_POST['userid'])) < 0 ||
        ($roleid = $_COMPANY->decodeId($_POST['roleid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit('Bad Request');
    }
    $download_unmatched_users = 0; // Default
    $download_matched_users = 0; // Default
    if (isset($_POST['downloadOptions'])){
        $downloadOptions = $_POST['downloadOptions'];
        foreach($downloadOptions as $downloadOption){
            if ( $_COMPANY->decodeId($downloadOption) == 2){
                $download_matched_users = 1;
            }

            if ( $_COMPANY->decodeId($downloadOption) == 1){
                $download_unmatched_users = 1;
            }
        }
    }
    $reportMeta = ReportTeamRegistrations::GetDefaultReportRecForDownload();

    updateReportMetaFieldsFromPOST($reportMeta);

    $reportMeta['Fields']['question'] = 'Question';
    $reportMeta['Options'] = array(
        'download_matched_users'=>$download_matched_users,
        'download_unmatched_users'=>$download_unmatched_users,
        'download_active_join_requests' => 1,
        'download_inactive_join_requests' => 1,
        'download_paused_join_requests' => 1,
        'download_active_users_only' => 0,
    );
    $reportMeta['Filters'] = array(
            'groupid' => $groupid,
            'userid' => $uid,
            'roleid'=>$roleid
    );
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'survey';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportTeamRegistrations ($_COMPANY->id(), $record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV, 'team_registration');
    #else echo false
    echo false;
    exit();
}


elseif (isset($_GET['getTeamRegistrationReportOptions']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $groups = $db->get("SELECT groupid,groupname FROM `groups` WHERE `companyid`='{$_COMPANY->id()}' AND (zoneid='{$_ZONE->id()}' AND isactive='1')");

    $modalTitle = sprintf(gettext("%s Registration Download Options"),$_COMPANY->getAppCustomization()['teams']['name']);
    $reportMeta = ReportTeamRegistrations::GetDefaultReportRecForDownload();

    unset($reportMeta['Fields']['question']);

    $fields = array();
    if ($reportMeta) {
        $fields = $reportMeta['Fields'];
        if (!empty($reportMeta['AdminFields'])) {
            $fields = array_merge($fields, $reportMeta['AdminFields']);
        }
    }

    include(__DIR__ . "/views/team_join_request_survey_download_options.template.php");
    exit();

}

elseif (isset($_GET['downloadTeamRegistrationReport']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    $download_unmatched_users = 0; // Default
    $download_matched_users = 0; // Default
    if (isset($_POST['downloadOptions'])){
        $downloadOptions = $_POST['downloadOptions'];
        foreach($downloadOptions as $downloadOption){
            if ( $_COMPANY->decodeId($downloadOption) == 2){
                $download_matched_users = 1;
            }

            if ( $_COMPANY->decodeId($downloadOption) == 1){
                $download_unmatched_users = 1;
            }
        }
    }
    $reportMeta = ReportTeamRegistrations::GetDefaultReportRecForDownload();

    updateReportMetaFieldsFromPOST($reportMeta);

    unset($reportMeta['Fields']['question']);

    $reportMeta['Options'] = array(
        'download_matched_users'=>$download_matched_users,
        'download_unmatched_users'=>$download_unmatched_users,
        'download_active_join_requests' => 1,
        'download_inactive_join_requests' => 1,
        'download_paused_join_requests' => 1,
        'download_active_users_only' => 0,
    );

    $reportMeta['Filters'] = array(
        'groupid' => 0, // Since we are getting all groups
        'userid' => 0,
        'roleid'=> 0
    );

    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'team_registrations';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportTeamRegistrations ($_COMPANY->id(), $record);

    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}


// For talentpeak team report
elseif (isset($_GET['getTeamsReportOptions']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $groups = $db->get("SELECT groupid,groupname FROM `groups` WHERE `companyid`='{$_COMPANY->id()}' AND (zoneid='{$_ZONE->id()}' AND isactive='1')");

    $modalTitle = sprintf(gettext("%s Download Options"),$_COMPANY->getAppCustomization()['teams']['name']);
    $reportMeta = ReportTeamTeams::GetDefaultReportRecForDownload();
    unset($reportMeta['Fields']['circle_max_capacity']);
    unset($reportMeta['Fields']['circle_vacancy']);
    $fields = array();
    if ($reportMeta) {
        $fields = $reportMeta['Fields'];
        if (!empty($reportMeta['AdminFields'])) {
            $fields = array_merge($fields, $reportMeta['AdminFields']);
        }
    }
    include(__DIR__ . "/views/team_reports_download_options.template.php");
    exit();
}

elseif (isset($_GET['getTeamMembersReportOptions']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $groups = $db->get("SELECT groupid,groupname FROM `groups` WHERE `companyid`='{$_COMPANY->id()}' AND (zoneid='{$_ZONE->id()}' AND isactive='1')");

    $modalTitle = sprintf(gettext("%s Members Download Options"),$_COMPANY->getAppCustomization()['teams']['name']);
    $reportMeta = ReportTeamMembers::GetDefaultReportRecForDownload();

    $fields = array();
    if ($reportMeta) {
        $fields = $reportMeta['Fields'];
        if (!empty($reportMeta['AdminFields'])) {
            $fields = array_merge($fields, $reportMeta['AdminFields']);
        }
    }
    include(__DIR__ . "/views/team_members_reports_download_options.template.php");
    exit();
}

elseif (isset($_GET['downloadTeamsReport'])) {

    $groupids = array();
    $teamstatus = array();
    $record = array();

    if (!empty($_POST['teamstatus'])) {
        foreach ($_POST['teamstatus'] as $encTeamStatus) {
            $teamstatus[] = $_COMPANY->decodeId($encTeamStatus);
        }
    }

    if (!empty($_POST['groupids'])) {
        foreach ($_POST['groupids'] as $groupid) {
            $groupids[] = $_COMPANY->decodeId($groupid);
        }
    }

    $reportMeta = ReportTeamTeams::GetDefaultReportRecForDownload();

    $reportMeta['Filters']['groupids'] = $groupids; // List of groupids
    $reportMeta['Filters']['teamstatus'] = $teamstatus;
    updateReportMetaFieldsFromPOST($reportMeta);
    unset($reportMeta['Fields']['circle_max_capacity']);
    unset($reportMeta['Fields']['circle_vacancy']);

    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'teams';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportTeamTeams ($_COMPANY->id(), $record);

    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();

}

elseif (isset($_GET['downloadTeamMembersReport'])) {

    $groupids = array();
    $teamstatus = array();
    $record = array();

    if (!empty($_POST['teamstatus'])) {
        foreach ($_POST['teamstatus'] as $encTeamStatus) {
            $teamstatus[] = $_COMPANY->decodeId($encTeamStatus);
        }
    }

    if (!empty($_POST['groupids'])) {
        foreach ($_POST['groupids'] as $groupid) {
            $groupids[] = $_COMPANY->decodeId($groupid);
        }
    }

    $reportMeta = ReportTeamMembers::GetDefaultReportRecForDownload();

    $reportMeta['Filters']['groupids'] = $groupids; // List of groupids
    $reportMeta['Filters']['teamstatus'] = $teamstatus;

    updateReportMetaFieldsFromPOST($reportMeta);

    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'team_members';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportTeamMembers ($_COMPANY->id(), $record);

    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}

elseif (isset($_GET['getTeamsFeedbackReportOptions']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $groups = $db->get("SELECT groupid,groupname FROM `groups` WHERE `companyid`='{$_COMPANY->id()}' AND (zoneid='{$_ZONE->id()}' AND isactive='1')");

    $reportMeta = ReportTeamsFeedback::GetDefaultReportRecForDownload();
    $fields = array();
    if ($reportMeta) {
        $fields = $reportMeta['Fields'];
        if (!empty($reportMeta['AdminFields'])) {
            $fields = array_merge($fields, $reportMeta['AdminFields']);
        }
    }

    include(__DIR__ . "/views/team_feedback_download_options.template.php");
    exit();
}
elseif (isset($_GET['downloadTeamsFeedbackReport'])) {

    $groupids = array();
    $teamstatus = array();
    $record = array();

    if (!empty($_POST['teamstatus'])) {
        foreach ($_POST['teamstatus'] as $encTeamStatus) {
            $teamstatus[] = $_COMPANY->decodeId($encTeamStatus);
        }
    }

    if (!empty($_POST['groupids'])) {
        foreach ($_POST['groupids'] as $groupid) {
            $groupids[] = $_COMPANY->decodeId($groupid);
        }
    }

    $reportMeta = ReportTeamsFeedback::GetDefaultReportRecForDownload();
    $reportMeta['Filters']['groupids'] = $groupids; // List of groupids
    $reportMeta['Filters']['teamstatus'] = $teamstatus;

    foreach ($reportMeta['Fields'] as $k => $v) {
        if (strpos($k,'extendedprofile.') !== false) {
            $kk = str_replace('extendedprofile.','extendedprofile_', $k);
        } else {
            $kk = $k;
        }
        if (!isset($_POST[$kk])) {
            unset($reportMeta['Fields'][$k]);
        }
    }

    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'team_feedback';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportTeamsFeedback ($_COMPANY->id(), $record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();

}
elseif (isset($_GET['deleteTeamJoinRequestSurveyData']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    return $group->deleteTeamJoinRequestSurveyData();
}

elseif (isset($_GET['updateOrgStatus']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
      // Authorization Check
      if (!$_USER->canManageCompanySettings() ||
      (($api_org_id = $_COMPANY->decodeId($_POST['api_org_id']))<1)
      || (($approvalid = $_COMPANY->decodeId($_POST['approvalid'])) < 1)
      || (($company_org_id = $_COMPANY->decodeId($_POST['company_org_id'])) < 1)
      ) {
          header(HTTP_FORBIDDEN);
          exit();
      }
    //   get the status too
    $orgStatus = $_POST['new_status'];
    //   Update status
    $result = Organization::UpdateOrgStatus($company_org_id, $orgStatus);

    $organization = Organization::GetOrganization($company_org_id, false);
    $approval = Approval::GetApproval($approvalid);

    if ((int) $orgStatus === 1) {
        $log_title = sprintf(
            gettext("%s marked organization '%s' as Approved"),
            $_USER->getFullName() . ' (' . $_USER->getEmailForDisplay() . ')',
            $organization->val('organization_name')
        );
    } elseif ((int) $orgStatus === 0) {
        $log_title = sprintf(
            gettext("%s marked organization '%s' as Not Approved"),
            $_USER->getFullName() . ' (' . $_USER->getEmailForDisplay() . ')',
            $organization->val('organization_name')
        );
    }

    if($result){
        $approval->addApprovalLog($log_title, '', false, 'general');
        AjaxResponse::SuccessAndExit_STRING(1, '', "Organization status updated successfully", 'Success');
    }else{
        AjaxResponse::SuccessAndExit_STRING(0, '', "Try again later", 'Error');
    }

}elseif (isset($_GET['updateOrgConfirmationStatus']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings() ||
    (($api_org_id = $_COMPANY->decodeId($_POST['api_org_id']))<1)
    || (($approvalid = $_COMPANY->decodeId($_POST['approvalid'])) < 1)
    || (($company_org_id = $_COMPANY->decodeId($_POST['company_org_id'])) < 1)
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //   get the status too
    $orgStatus = $_POST['new_status'];
    //   Update status
    $result = Organization::UpdateOrgConfirmationStatus($api_org_id, $orgStatus);

$organization = Organization::GetOrganization($company_org_id, false);
$approval = Approval::GetApproval($approvalid);
$log_title ="";
if ((int) $orgStatus === 1) {
   $log_title = sprintf(
       gettext("%s marked organization '%s' as Confirmed"),
       $_USER->getFullName() . ' (' . $_USER->getEmailForDisplay() . ')',
       $organization->val('organization_name')
   );
} 
if($result){
   $approval->addApprovalLog($log_title, '', false, 'general');
   AjaxResponse::SuccessAndExit_STRING(1, '', "Organization status updated successfully", 'Success');
}else{
   AjaxResponse::SuccessAndExit_STRING(0, '', "Try again later", 'Error');
}

}
elseif (isset($_GET['updateAdminOrgStatus']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings() ||
    ($orgId = $_COMPANY->decodeId($_POST['org_id']))<1
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }
  //   get the status too
  $orgStatus = $_POST['new_status'];
  //   Update status
  $result = Organization::UpdateOrgStatus($orgId, $orgStatus);
  if($result){
      AjaxResponse::SuccessAndExit_STRING(1, '', "Organization status updated successfully", 'Success');
  }else{
      AjaxResponse::SuccessAndExit_STRING(0, '', "Try again later", 'Error');
  }

}elseif (isset($_GET['saveNTopicApprovalNote']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (!isset($_POST['topicid'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $event_id = $_COMPANY->decodeId($_POST['topicid']);
    // check if event deleted
    if(($event = Event::GetEvent($event_id)) === NULL || !$event->val('isactive')){
        echo $response = -1;
        exit();
    }
    // assign object
    $approval = $event->getApprovalObject();
    $saveNote = $approval->addGeneralNote($_POST['note']);
    if($saveNote){
        $enc_approvalid = $_COMPANY->encodeId($approval->id());
        AjaxResponse::SuccessAndExit_STRING(1, $enc_approvalid, "Note added successfully", 'Success');
    }else{
        AjaxResponse::SuccessAndExit_STRING(0, '', "Try again later", 'Error');
    }

}
elseif (isset($_GET['assignUserForApprovalModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
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
        echo $response = -1;
        exit();
    }
    $approvalStage = (int)$_GET['assignUserForApprovalModal'];
    $approvalStage = $approvalStage > Approval::APPROVAL_STAGE_MAX ? Approval::APPROVAL_STAGE_MAX : $approvalStage;

    $modalTitle = gettext("Assign ".$topicTypeLabel." Approver");

    // Get the approvar data according to the stage
    $stageTitle = "Stage {$approvalStage}";
    $TopicClass = Teleskope::TOPIC_TYPE_CLASS_MAP[$topicType];
    $topicTypeLabel = Teleskope::TOPIC_TYPES_ENGLISH[$topicType];

    $approvalStageConfig = TopicApprovalConfiguration::GetApprovalConfigurationByTopicAndStage($topicType,$approvalStage);
    $stageApprovers = $approvalStageConfig->getApprovers();
    $stageApproverUserIds = array_filter(array_column($stageApprovers, 'approver_userid'));

       // Authorization Check
    if (!$_USER->canManageZoneEvents() && !in_array( $_USER->id() , $stageApproverUserIds)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    // check if a user is already assigned
    $approval = $topicTypeObj->getApprovalObject();
    $assigned_to = "";
    if($approval->val('assigned_to') && in_array($approval->val('assigned_to'), $stageApproverUserIds)){
        $assigned_to = $approval->val('assigned_to');
    }
    include(__DIR__ . "/views/assign_event_approval_modal.html");
}
elseif (isset($_GET['approvalTasksModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (   
        ($topicTypeId = $_COMPANY->decodeId($_GET['approvalTopicTypeid']))<1 ||
        ($approvalid = $_COMPANY->decodeId($_GET['approvalid']))<1 ||
        !isset($_GET['approvalTasksModal']) ||
        empty($topicType = $_GET['topicType'])
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    //   Verify topicTypes and then fetch object. If used alot then we can move it to a method
    if($topicType == Teleskope::TOPIC_TYPES['EVENT']){
        $topicTypeObj = Event::GetEvent($topicTypeId);
        $globalConfigurationForAllStages = Event::GetAllApprovalConfigurationRows(0,0,0);
        $topicTypeLabel = $topicTypeObj ?-> isSeriesEventHead() ? 'Event Series' : 'Event';
        $appCustomizationTitle = 'event';
    }elseif($topicType == Teleskope::TOPIC_TYPES['NEWSLETTER']){
        $topicTypeObj = Newsletter::GetNewsletter($topicTypeId);
        $globalConfigurationForAllStages = Newsletter::GetAllApprovalConfigurationRows(0,0,0);
        $topicTypeLabel = 'Newsletter';
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
        echo $response = -1;
        exit();
    }
    $approval = $topicTypeObj->getApprovalObject();
    $approvalStage = (int)$approval->val('approval_stage');
    // extract config id
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
  
    // Move the status from here and use constants
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

    if($nextStageApprovers && $disallow_submitter_approval){
        // filter out the the approval erquest submitter
        $nextStageApprovers = array_values(array_filter(
            $nextStageApprovers, fn($approver) => $approver['approver_userid'] != (int)$approval->val('createdby')
        ));
    }

    $ajaxReqEndpoint = 'ajax.php';
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
// cancel approval request
elseif (isset($_GET['cancelApprovalRequest']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
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
elseif (isset($_GET['updateTaskSortingOrder']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (empty($_POST['task_id'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $task_ids_order = $_COMPANY->decodeIdsInCSV($_POST['task_id']);
    TopicApprovalTask::UpdateTaskSortOrder(explode(',',$task_ids_order) ?: []);
	echo 1;
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
    $TopicClass = Teleskope::TOPIC_TYPE_CLASS_MAP[$topicType];
    $topicTypeLabel = Teleskope::TOPIC_TYPES_ENGLISH[$topicType];
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

     //Data Validation
    if(!$topicTypeObj || 
        $topicTypeObj->isPublished() ||  // If topic is published then do not allow approve/deny/assign
        $topicTypeObj->isAwaiting()  // Same for events scheduled for publishing
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

    $stageRowsData = $TopicClass::GetAllApproversByStage($currentApprovalStage,0,0,0);

    // Selected approver ids
    $selectedApprovers = array();
    if (isset($_POST['selectedApproversId'])) {
        $selectedApprovers = $_COMPANY->decodeIdsInArray($_POST['selectedApproversId'] ?: array());
    } elseif (isset($_POST['selectedApproversIdDefaultList'])) {
        # In some cases where max approver configuration is set to 0, we will get all values in selectedApproversIdDefaultList
        $selectedApprovers = $_COMPANY->decodeIdsInArray(explode(',', $_POST['selectedApproversIdDefaultList'] ?: ''));
    }

    // Set redirect path
    $redirect_path = "topic_approvals?topicType={$topicType}";
      // Check for approved or denied
      if ($_POST['action'] == 'assign' && !empty($_POST['approverId'])) {
        $approverId = $_COMPANY->decodeId($_POST['approverId']);
        if($approval->assignTo($approverId,$approver_note)) {
            Http::Redirect($redirect_path);
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

elseif (isset($_GET['deleteApproval']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($approvalid = $_COMPANY->decodeId($_POST['approvalid']))<1
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    $stageRowsData = Event::GetAllApproversByStage(0,0,0,0);
    $stageApproverUserIds = $stageRowsData['approver_userids'] ?? array();
    if (!in_array( $_USER->id() , $stageApproverUserIds) && !$_USER->canManageZoneEvents()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (Approval::DeleteApproval($approvalid)){
        echo 1;
    } else {
        echo 0;
    }
}
elseif (isset($_GET['combineAutoApprovalCriterion']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        ($approval_config_id = $_COMPANY->decodeId($_POST['approval_config_id']))<1 ||
        ($approvalStage = $_COMPANY->decodeId($_POST['approvalStage']))<1
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // encoded ids check
    $decodedIds = empty($_POST['selectedCriteria']) ? [] : $_COMPANY->decodeIdsInArray($_POST['selectedCriteria']);
    $TopicClass = Teleskope::TOPIC_TYPE_CLASS_MAP[$_POST['topicType']];

    $autoApprovalConfigData = $TopicClass::GetAutoApprovalDataByStage($approvalStage);

    foreach($autoApprovalConfigData as &$entry) {
        if(in_array($entry['custom_field_id'], $decodedIds)) {
            // Set condition_group value to 1. This allows us future evolution where we can support multiple
            // groups with values incrementing 1, 2, 3, 4...
            // When supporting multiple groups we will have to change the logic in
            // TopicApprovalTriat -> isAutoApprovalCriteriaMet
            $entry['condition_group'] = 1;
        } else {
            unset($entry['condition_group']);
        }
    }

    $newJsonData = json_encode($autoApprovalConfigData);
    $updateCritirea = Event::UpdateAutoApprovalConfiguration($approval_config_id, $newJsonData);

    if($updateCritirea){
        AjaxResponse::SuccessAndExit_STRING(1, '', "Grouped Condition Updated ", 'Success');
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', "An error occurred. Please try again later ", 'Error');
}

elseif (isset($_GET['deleteAutoApprovalCriterion']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($custom_field_id = $_COMPANY->decodeId($_POST['custom_field_id']))<1 ||
        ($approval_config_id = $_COMPANY->decodeId($_POST['approval_config_id']))<1 ||
        ($approvalStage = $_COMPANY->decodeId($_POST['approvalStage']))<1
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // added topic type 
    $topic_type = $_GET['topic_type'] ?? 'EVT';
    $TopicClass = Teleskope::TOPIC_TYPE_CLASS_MAP[$topic_type];
    
    // check if the criterion is combined with others. 
    $data = $TopicClass::GetAutoApprovalDataByStage($approvalStage);
    $deleteAllowed = true;
    foreach($data as $entry){
        if(!isset($entry['all']) || !is_array($entry['all'])){
            continue;
        }
        $idsInCriterionGroup = array_column($entry['all'], 'custom_field_id');
        if(in_array($custom_field_id, $idsInCriterionGroup)){
            $deleteAllowed = false;
            break;
        }
    }
    // Allow delete only if the criterion is not combined with others
    if(!$deleteAllowed){
        AjaxResponse::SuccessAndExit_STRING(0, '', "To delete this criterion, please remove this from grouped criterions", '');
    }
    // legacy logic intact
    $deleteCriterion = $TopicClass::DeleteAutoApprovalCriterion($approvalStage,$approval_config_id,$custom_field_id);
    if($deleteCriterion){
        AjaxResponse::SuccessAndExit_STRING(1, '', "Criterion deleted ", 'Success');
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', "An error occurred. Please try again later ", 'Error');

}
elseif (isset($_GET['getEventCustomFieldOption']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($event_custom_field_id = $_COMPANY->decodeId($_POST['event_custom_field_id']))<1
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Data Fetch
    $customFieldData=[];
    $customFieldData = Event::GetEventCustomFieldDetail($event_custom_field_id);
    $customFieldDataType = $customFieldData['custom_fields_type'];
    // Prepare the HTML data
    $options = [];
    $htmlContent = '';
    if (isset($customFieldData) && !empty($customFieldData)) {

        foreach ($customFieldData['options'] as $data) {
            if ($customFieldDataType == 1) { 
                $options[] = '<option value="'. $_COMPANY->encodeId($data['custom_field_option_id']) .'">'.$data['custom_field_option'].'</option>'; 
             } else { 
                $options[] = '<input type="checkbox" id="customFieldOptionsCheckboxes" name="customFieldOption[]" value="'.$_COMPANY->encodeId($data['custom_field_option_id']).'">  '. $data['custom_field_option'].'<br>';
            }
        }
    }
    if(!empty($options)){
        if ($customFieldDataType == 1) { 
        $htmlContent = '<select name="customFieldOption[]" class="form-control" id="customFieldOptionsDropdown">'.implode('',$options).'</select>';
        }else{
        $htmlContent = implode('',$options) . '<small>A match is considered successful if at least one of the selected checkboxes matches the user selected values.</small>';
        }
    }
    echo $htmlContent;
}
elseif (isset($_GET['assignUserForSpeakerApprovelModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    if (
        ($eventid = $_COMPANY->decodeId($_GET['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        ($speakerid = $_COMPANY->decodeId($_GET['speakerid']))<1
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    $modalTitle = "Assign Speaker Approver";
    include(__DIR__ . "/views/assign_speaker_approvel_modal.html");
}

elseif (isset($_GET['assignSpeakerApprover']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (
        ($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = Event::GetEvent($eventid)) === NULL ||
        ($speakerid = $_COMPANY->decodeId($_POST['speakerid']))<1 ||
        ($approverid  =  $_COMPANY->decodeId($_POST['approverid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    echo $event->assignSpeakerApprover($speakerid,$approverid);
}

elseif (isset($_GET['getTemplateData']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if($_GET['type'] == 'newsletter'){
        echo $template = file_get_contents("./newsletter_templates/NEWSLETTER");
    }elseif($_GET['type'] == 'comm_welcome_email'){
        echo $template = file_get_contents("./newsletter_templates/WELCOME_EMAIL");
    }elseif($_GET['type'] == 'comm_leave_email'){
        echo $template = file_get_contents("./newsletter_templates/LEAVE_EMAIL");
    }elseif($_GET['type'] == 'comm_anniversary_email'){
        echo $template = file_get_contents("./newsletter_templates/ANNIVERSARY_EMAIL");
    }else{
        echo "";
    }
}

elseif (isset($_GET['usersLogReportModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Authorization Check
    if (!$_USER->canManageCompanySettings() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $groups = Group::GetAllGroupsByCompanyid($_COMPANY->id(),$_ZONE->id(),true);

    // Company Customized inpts for Reports
    $reportMeta = ReportUserAuditLogs::GetDefaultReportRecForDownload();
    $excludeAnalyticMetaFields = json_encode(ReportUserAuditLogs::GetMetadataForAnalytics()['ExludeFields']);

    $fields = array();
    $reportType = array();

    if ($reportMeta) {
        $fields = $reportMeta['Fields'];
        if (!empty($reportMeta['AdminFields'])) {
            $fields = array_merge($fields, $reportMeta['AdminFields']);
        }
    }

    if (!$_COMPANY->getAppCustomization()['chapter']['enabled']) {
        unset($fields['chaptername']);
        unset($fields['enc_chapterid']);
    }

    if (!$_COMPANY->getAppCustomization()['channel']['enabled']) {
        unset($fields['channelname']);
        unset($fields['enc_channelid']);
    }

    $cpuUsageMessage = "Note: Selecting too many fields may slow down your system due to high CPU and memory usage for data analysis. So please choose the least fields for analytics for better performance.";

    //set the type of report to download
    if ($_GET['usersLogReportModal']=='members') {
        $select_type = 'members';
        unset($fields['role']);
        $report_label = 'Members Audit Logs';
    }

    if ($_GET['usersLogReportModal']=='groupleads') {
        $select_type = 'groupleads';
        $report_label = $_COMPANY->getAppCustomization()['group']['name-short'] . ' Leaders Audit Logs';
        unset($fields['chaptername']);
        unset($fields['enc_chapterid']);
        unset($fields['channelname']);
        unset($fields['enc_channelid']);
        unset($fields['role']);
    }
    if ($_GET['usersLogReportModal']=='chapterleads') {
        $select_type = 'chapterleads';
        $report_label = $_COMPANY->getAppCustomization()['chapter']['name-short'] . ' Leaders Audit Logs';
        unset($fields['channelname']);
        unset($fields['enc_channelid']);
        unset($fields['role']);
    }
    if ($_GET['usersLogReportModal']=='channelleads') {
        $select_type = 'channelleads';
        $report_label = $_COMPANY->getAppCustomization()['channel']['name-short'] . ' Leaders Audit Logs';
        unset($fields['chaptername']);
        unset($fields['enc_chapterid']);
        unset($fields['role']);
    }

    //include the modal
    include(__DIR__ . "/views/templates/user_audit_log_report.template.php");
}

elseif (isset($_GET['downloadUserAuditLogs']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (empty($_POST['s_type'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $groups = $_POST['s_group'] ?? array();
    $ids = [];
    for($i=0;$i<count($groups);$i++){
        array_push($ids,$_COMPANY->decodeId($groups[$i]));
    }
    $reportMeta = ReportUserAuditLogs::GetDefaultReportRecForDownload();
    $tz = $_SESSION['timezone'] ?? 'UTC';

    if (!empty($_POST['startDate'])) {
        $reportMeta['Options']['startDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['startDate']." 00:00:00", $tz);
    }
    
    if (!empty($_POST['endDate'])) {
        $reportMeta['Options']['endDate'] = $db->covertLocaltoUTC("Y-m-d H:i:s", $_POST['endDate']." 23:59:59", $tz);
    }
    $reportMeta['Options']['includeMembers'] = in_array('members', $_POST['s_type']);
    $reportMeta['Options']['includeGroupleads'] = in_array('groupleads', $_POST['s_type']);
    $reportMeta['Options']['includeChapterleads'] = in_array('chapterleads', $_POST['s_type']);
    $reportMeta['Options']['includeChannelleads'] = in_array('channelleads', $_POST['s_type']);
    $reportMeta['Options']['includeDeletedUser'] = !empty($_POST['includeDeletedUser']);

    $filePrefix = '';
    if (count($_POST['s_type']) == 1 &&
        in_array($_POST['s_type'][0], array('members','groupleads','chapterleads','channelleads'))
    ) {
        $filePrefix = $_POST['s_type'][0];
    }

    $reportMeta['Filters']['groupids'] = $ids; // List of groupids, or empty for all groups

    foreach ($reportMeta['Fields'] as $k => $v) {
        $kk = $k;
        if (!isset($_POST[$kk])) {
            if (isset($_POST['analytics'])){
                continue;
            }
            unset($reportMeta['Fields'][$k]);
        }
    }
    // Update Report meta
    updateReportMetaFieldsFromPOST($reportMeta);
    // We do not provide option to remove groupname, chaptername, role or since date
    // We do not provide external id in the report of extended profile fields in the reports at the moment.
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'user_audit_log';
    $record['reportmeta'] = json_encode($reportMeta);

    $reportAction = $_POST['reportAction'];
    $report = new ReportUserAuditLogs($_COMPANY->id(),$record);
    if ($reportAction == 'download'){
        $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV, $filePrefix);
        #else echo false
        echo false;
    } else {
        $title = gettext("Members Audit Logs Report Analytics");
        $_SESSION['analytics_data'] = $report->generateAnalyticData($title);
        Http::Redirect("analytics");
    }
    exit();
}

elseif (isset($_GET['connectUserModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers() || !$_ZONE->isConnectFeatureEnabled()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    if (
        ($uid = $_COMPANY->decodeId($_GET['userid']))<1
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    $modalTitle = "Connect User";
    include(__DIR__ . "/views/connect_user_modal.html");
}

elseif (isset($_GET['manageConnectUserModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    if (
        ($uid = $_COMPANY->decodeId($_GET['userid']))<1 ||
        ($usr = User::GetUser($uid)) === null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    $connectUser = UserConnect::GetConnectUserByTeleskopeUserid($uid);
    $modalTitle = "Manage Connect User";
    include(__DIR__ . "/views/manage_connect_user_modal.html");
}

elseif (isset($_GET['submitConnectUser']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers() || !$_ZONE->isConnectFeatureEnabled()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    if (
        ($uid = $_COMPANY->decodeId($_POST['connectUser']))<1 ||
        ($usr = User::GetUser($uid)) === null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    $externalId = $usr->val('externalid');

    $personalEmail = trim(raw2clean($_POST['personalemail']));

    if ($_COMPANY->isValidAndRoutableEmail($usr->val('email'))) {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Unable to connect user who already has a valid and routable email", 'Error!');
    }

    if ($_COMPANY->isValidEmail($personalEmail)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Unable to use email address that is configured as company email", 'Error!');
    }

    if (!filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Please input a valid email id!", 'Error!');
    } else {
        if (!$externalId){
            AjaxResponse::SuccessAndExit_STRING(0, '', "Unable to connect! User has no external Id!", 'Error!');
        }
        $connectUser = UserConnect::GetConnectUserByTeleskopeUserid($uid);
        if ($connectUser){
            AjaxResponse::SuccessAndExit_STRING(0, '', "Unable to connect! This user is already connected!", 'Error!');
        } else {
            $connectid = UserConnect::AddConnectUser($uid, $personalEmail);
            if ($connectid > 0) {
                $connectUser = UserConnect::GetConnectUser($connectid);
                $connectUser->sendEmailVerificationCode($_ZONE->val('app_type'));
                AjaxResponse::SuccessAndExit_STRING(1, '', "Connect email confirmation email sent successfully", 'Success');
            } elseif ($connectid == -1 || $connectid == -2) {
                AjaxResponse::SuccessAndExit_STRING(0, '', "Unable to connect! This user is already connected!", 'Error!');
            } elseif ($connectid == -3) {
                AjaxResponse::SuccessAndExit_STRING(0, '', "Unable to connect! The email address is already assigned to another user!", 'Error!');
            }
        }
    }

}

elseif (isset($_GET['changeConnectUserEmail']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    if (
        ($uid = $_COMPANY->decodeId($_POST['userid']))<1 ||
        ($connectUser = UserConnect::GetConnectUserByTeleskopeUserid($uid)) === null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    $personalEmail = trim(raw2clean($_POST['connectEmail']));

    if ($_COMPANY->isValidEmail($personalEmail)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Unable to use email address that is configured as company email", 'Error!');
    }

    if (!filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Please input a valid email id!", 'Error!');
    } else {
        if ($personalEmail == $connectUser->val('external_email')){
            AjaxResponse::SuccessAndExit_STRING(0, '', "Please enter an email that is different than the previous one!", 'Error!');
        }
        if($connectUser->updateConnectUserPersonalEmail($_ZONE->val('app_type'), $personalEmail)){
            $connectUser = UserConnect::GetConnectUser($connectUser->id()); // Reinit object
            $connectUser->sendEmailVerificationCode($_ZONE->val('app_type'), true);
            AjaxResponse::SuccessAndExit_STRING(1, '', "Connect email confirmation email sent successfully", 'Success');
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', "Something went wrong while updating connect email!", 'Error!');
        }
    }
}
elseif (isset($_GET['resendConnectEmailVerification']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    if (
        ($uid = $_COMPANY->decodeId($_POST['userid']))<1 ||
        ($connectUser = UserConnect::GetConnectUserByTeleskopeUserid($uid)) === null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    $connectUser->sendEmailVerificationCode($_ZONE->val('app_type'), true);
    AjaxResponse::SuccessAndExit_STRING(1, '', "Verification email sent successfully", 'Success');

}
elseif (isset($_GET['deleteConnectUser']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    if (
        ($uid = $_COMPANY->decodeId($_POST['userid']))<1 ||
        ($connectUser = UserConnect::GetConnectUserByTeleskopeUserid($uid)) === null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    $connectUser->deleteConnectUser();
    AjaxResponse::SuccessAndExit_STRING(1, '', "Connect account deleted successfully", 'Success');
}

elseif (isset($_GET['manageBudgetCurrenciesModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (
        ($budget_year_id = (int) $_COMPANY->decodeId($_GET['budget_year_id']))<0 ||
        ($budgetYear = Budget2::GetCompanyBudgetYearDetail($budget_year_id)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $allowed_foreign_currencies = array();
    if ($budgetYear['allowed_foreign_currencies']){
        $allowed_foreign_currencies = json_decode($budgetYear['allowed_foreign_currencies'],true);
    }
    $modalTitle = "Manage Foreign Currencies ( for {$budgetYear['budget_year_title']})";

    include(__DIR__ . "/views/manage_budget_foreign_currencies.html");
}

elseif (isset($_GET['addUpdateBudgerCurrencyModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (
        ($budget_year_id = (int) $_COMPANY->decodeId($_GET['budget_year_id']))<0 ||
        ($budgetYear = Budget2::GetCompanyBudgetYearDetail($budget_year_id)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $currency_id  = -1;
    if (!empty($_GET['currency_id'])){
        $currency_id = (int) $_COMPANY->decodeId($_GET['currency_id']);
    }
    $modalTitle = "Add Budget Foreign Currency";
    $submitButton = "Add Budget Currency";
    $configured_currency = array();

    $allowed_foreign_currencies = array();
    $allowed_foreign_currencies_codes = array();
    if ($budgetYear['allowed_foreign_currencies']){
        $allowed_foreign_currencies = json_decode($budgetYear['allowed_foreign_currencies'],true);
        $allowed_foreign_currencies_codes = array_column($allowed_foreign_currencies,'cc');
    }
    
    if (!empty($allowed_foreign_currencies) && $currency_id >= 0){
       
        $configured_currency = $allowed_foreign_currencies[$currency_id];
        $modalTitle = "Update Foreign Currency ({$budgetYear['budget_year_title']})";
        $submitButton = "Update Budget Currency";
    }
    include(__DIR__ . "/views/add_update_budget_currency.html");
}

elseif (isset($_GET['addUpdateBudgetForeignCurrency']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (
        ($budget_year_id = (int) $_COMPANY->decodeId($_POST['budget_year_id']))<0 ||
        ($budgetYear = Budget2::GetCompanyBudgetYearDetail($budget_year_id)) === null ||
        ($foreigncurrency = (string) $_POST['foreigncurrency']) == '' ||
        ($conversion_rate = (float) $_POST['conversion_rate']) <=0

    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $currency_id  = -1;
    if (!empty($_POST['currency_id'])){
        $currency_id = (int) $_COMPANY->decodeId($_POST['currency_id']);
    }

    $conversion_rate = round($conversion_rate,9);
    $foreignCurrencyArray = array('cc'=>$foreigncurrency,'conversion_rate'=>$conversion_rate);
    $allowed_foreign_currencies = array();
    if ($budgetYear['allowed_foreign_currencies']){
        $allowed_foreign_currencies = json_decode($budgetYear['allowed_foreign_currencies'],true);
        if ($currency_id >=0){
            $allowed_foreign_currencies[$currency_id] = $foreignCurrencyArray;
        } else {
            $allowed_foreign_currencies[] = $foreignCurrencyArray;
        }
    } else {
        $allowed_foreign_currencies[] = $foreignCurrencyArray;
    }

    $allowed_foreign_currencies = json_encode($allowed_foreign_currencies);
    $success = Budget2::addUpdateBudgetForeignCurrency($budget_year_id,$allowed_foreign_currencies);

    if ($success) {
        AjaxResponse::SuccessAndExit_STRING(1, '', "Budget currency updated successfully", 'Success');
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Something went wrong. Please try again.", 'Error!');
    }
}

elseif (isset($_GET['deleteBudgetCurrency']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageZoneBudget()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (
        ($budget_year_id = (int) $_COMPANY->decodeId($_POST['budget_year_id']))<0 ||
        ($budgetYear = Budget2::GetCompanyBudgetYearDetail($budget_year_id)) === null ||
        ($currency_id = (int) $_COMPANY->decodeId($_POST['currency_id']))<0

    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $allowed_foreign_currencies = array();
    if ($budgetYear['allowed_foreign_currencies']){
        $allowed_foreign_currencies = json_decode($budgetYear['allowed_foreign_currencies'],true);
        unset($allowed_foreign_currencies[$currency_id]);
        $allowed_foreign_currencies = array_values($allowed_foreign_currencies);
    }

    $allowed_foreign_currencies = !empty($allowed_foreign_currencies) ? json_encode($allowed_foreign_currencies) : '';
    $success = Budget2::addUpdateBudgetForeignCurrency($budget_year_id,$allowed_foreign_currencies);

    if ($success) {
        AjaxResponse::SuccessAndExit_STRING(1, '', "Budget currency deleted successfully", 'Success');
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Something went wrong. Please try again.", 'Error!');
    }
}

## OK
elseif (isset($_GET['blockUser']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['userid']) || ($id = $_COMPANY->decodeId($_POST['userid']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

	$user = User::GetUser($id);

    if ($user) {
		if($user->block()){
            AjaxResponse::SuccessAndExit_STRING(1, '', "User blocked successfully", 'Success');
        }
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', "Something went wrong while blocking user. Please try again.", 'Error!');
}


## OK
elseif (isset($_GET['unblockUser']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageAffinitiesUsers()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (!isset($_POST['userid']) || ($id = $_COMPANY->decodeId($_POST['userid']))<1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

	$user = User::GetUser($id);
	if ($user) {
		if($user->unblock()){
            AjaxResponse::SuccessAndExit_STRING(1, '', "User unblocked successfully", 'Success');
        }
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', "Something went wrong while unblocking user. Please try again.", 'Error!');

}

elseif (isset($_GET['deleteDynamicList']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (
        ($listid = (int) $_COMPANY->decodeId($_POST['listid']))<1 ||
        ($list = DynamicList::GetList($listid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }


    if ($list->delete()) {
        AjaxResponse::SuccessAndExit_STRING(1, '', "Dynamic List deleted successfully", 'Success');
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Something went wrong. Please try again.", 'Error!');
    }
}
elseif (isset($_GET['getDynamicListUsers']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
 
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (
        ($listid = (int) $_COMPANY->decodeId($_POST['listid']))<1 ||
        ($list = DynamicList::GetList($listid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $invalidCatalogs = $list->listInvalidCatalogs();
    if (!empty($invalidCatalogs)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', "This list references the following catalogs that are no longer available: " . implode(',', $invalidCatalogs), 'Error!');
    }
    $userids = $list->getUserIds();

    if (!empty($userids)) {
        $modalTitle = 'Global users in <em>' . $list->val('list_name'). '</em> dynamic list';
        $usersList = User::GetZoneUsersByUserids($userids,true);
        include(__DIR__ . "/../common/dynamic_users_list_template.html");
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', "No users found in this dynamic list", 'Error!');
    }
}
elseif (isset($_GET['getDynamicListRules']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

     // Authorization Check
     if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (
        ($listid = (int) $_COMPANY->decodeId($_POST['listid']))<1 ||
        ($list = DynamicList::GetList($listid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // get the data
    $catalog_categories = array_values(UserCatalog::GetAllCatalogCategories());
    $list_criteria = $list->getCriteria();

    if (!empty($list_criteria)) {
        $modalTitle = $list->val('list_name'). " dynamic list rules";
        $invalidCatalogs = $list->listInvalidCatalogs();
        include(__DIR__ . "/views/dynamic_list_rules_preview.html");
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', "No rules found for this dynamic list", 'Error!');
    }
 
}

elseif (isset($_GET['initQuestionVisiblitylogic']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
 
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (
        ($custom_field_id = (int) $_COMPANY->decodeId($_GET['custom_field_id']))<0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $topictype = $_GET['topictype'] ?? 'EVT';
    $topic_class = Teleskope::TOPIC_TYPE_CLASS_MAP[$topictype];

    $parentCustomField = null;
    if ($custom_field_id) {
        $parentCustomField = Event::GetEventCustomFieldDetail($custom_field_id);
        if ($parentCustomField['topictype'] != $topictype) {
            Logger::Log("Unexpected topictype - expecting {$parentCustomField['topictype']}, got {$topictype}");
            header(HTTP_BAD_REQUEST);
            exit();
        }
    }

    $customFields = call_user_func([$topic_class, 'GetEventCustomFields'], false);
    
    if (!empty($customFields)) {
        include(__DIR__ . "/views/custom_fields/select_custom_field.template.php");
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', "No field available to select", 'Error!');
    }
}

elseif (isset($_GET['showAvailableOptionsForLogic']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
 
    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (
        ($parent_custom_field_id = (int) $_COMPANY->decodeId($_GET['parent_custom_field_id']))<0 ||
        ($custom_field_id = (int) $_COMPANY->decodeId($_GET['custom_field_id']))<1 ||
        ($customField = Event::GetEventCustomFieldDetail($custom_field_id)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $parentCustomField = null;
    if ($parent_custom_field_id) {
        $parentCustomField = Event::GetEventCustomFieldDetail($parent_custom_field_id);
    }

    $options = $customField['options'];
    include(__DIR__ . "/views/custom_fields/select_custom_field_options.template.php");
    
}

## OK
elseif (isset($_GET['changeCustomFieldPosition']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    // Authorization Check
    if (!$_USER->canManageAffinitiesContent()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (
        ($custom_field_id = (int) $_COMPANY->decodeId($_POST['custom_field_id']))<0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $currentValue = (int) $_POST['currentValue'];
    $newValue     = (int) $_POST['newValue'];
    $maxValue     = (int) $_POST['maxValue'];
    if (!$newValue || $newValue > $maxValue){
        AjaxResponse::SuccessAndExit_STRING(0, '', "'Please enter a valid number", 'Error');
    }

    Event::UpdateCustomFieldPriority($custom_field_id, $currentValue, $newValue);
    AjaxResponse::SuccessAndExit_STRING(1, '', "Custom filed position changed successfully", 'Success');
}

elseif (isset($_GET['submitAddUpdateOrganization']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (!$_USER->isCompanyAdmin()) {
        header(HTTP_FORBIDDEN);
        echo "403 Forbidden (Access Denied)";
        exit();
    }

    $organization_id = $_COMPANY->decodeId($_POST['organization_id']);
    if(!$organization_id){
        $check = array('Organization name' => @$_POST['organization_name'],'Organization TAX ID' => @$_POST['organization_taxid'],'Street' => @$_POST['address_street'],'City' => @$_POST['address_city'],'State' => @$_POST['address_state'],'Country' => @$_POST['address_country'],'Zip Code' => @$_POST['address_zipcode'],'Contact Firstname' => @$_POST['contact_firstname'], 'Contact Lasstname' => @$_POST['contact_lastname'], 'Contact Email' => @$_POST['contact_email']);
        $checkRequired = $db->checkRequired($check);
        if($checkRequired){
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$checkRequired), gettext('Error'));
        }
    }



    $organization_name = $_POST['organization_name'];
    $organization_taxid = $_POST['organization_taxid'];
    $address_street = $_POST['address_street'];
    $address_city = $_POST['address_city'];
    $address_state = $_POST['address_state'];
    $address_country = $_POST['address_country'];
    $address_zipcode = $_POST['address_zipcode'];

    
    $organization_url =  $_POST['organization_url'];
    $organization_type = $_POST['organization_type'];
    
    $ceo_firstname = $_POST['ceo_firstname'] ?? '';
    $ceo_lastname = $_POST['ceo_lastname'] ?? '';
    $ceo_dob = $_POST['ceo_dob'] ?? $_POST['hidden_ceo_dob'] ?? '';
    $cfo_firstname = $_POST['cfo_firstname'] ?? '';
    $cfo_lastname = $_POST['cfo_lastname'] ?? '';
    $cfo_dob = $_POST['cfo_dob'] ?? $_POST['hidden_cfo_dob'] ?? '';

    $contact_firstname = $_POST['contact_firstname'];
    $contact_lastname = $_POST['contact_lastname'];
    $contact_email = $_POST['contact_email'];


    //Controlling Parties Section
    $bm1_firstname = $_POST['bm1_firstname'];
    $bm1_lastname = $_POST['bm1_lastname'];
    $bm1_dob = $_POST['bm1_dob']?? $_POST['hidden_bm1_dob'] ?? '';
    $bm2_firstname = $_POST['bm2_firstname'];
    $bm2_lastname = $_POST['bm2_lastname'];
    $bm2_dob = $_POST['bm2_dob']?? $_POST['hidden_bm2_dob'] ?? '';
    $bm3_firstname = $_POST['bm3_firstname'];
    $bm3_lastname = $_POST['bm3_lastname'];
    $bm3_dob = $_POST['bm3_dob']?? $_POST['hidden_bm3_dob'] ?? '';
    $bm4_firstname = $_POST['bm4_firstname'];
    $bm4_lastname = $_POST['bm4_lastname'];
    $bm4_dob = $_POST['bm4_dob']?? $_POST['hidden_bm4_dob'] ?? '';
    $bm5_firstname = $_POST['bm5_firstname'];
    $bm5_lastname = $_POST['bm5_lastname'];
    $bm5_dob = $_POST['bm5_dob']?? $_POST['hidden_bm5_dob'] ?? '';

    $organization_mission_statement = $_POST['organization_mission_statement'];
    $company_organization_notes = $_POST['company_organization_notes'];

    // $organization_id = $_COMPANY->decodeId($_POST['organization_id']);
    $api_org_id = (int)$_COMPANY->decodeId($_POST['org_id']) ?? 0;

    Organization::AddUpdateOrganization($organization_id, $organization_name, $organization_taxid, $address_street, $address_city, $address_state, $address_country, $address_zipcode, $organization_url, $organization_type, $contact_firstname, $contact_lastname, $contact_email, $api_org_id, $company_organization_notes);

    AjaxResponse::SuccessAndExit_STRING(1, '', "Organization data saved successfully", 'Success');
}

elseif (isset($_GET['unlinkOrganization']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Authorization Check
    if (!$_USER->isCompanyAdmin()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (
        ($organization_id = (int)$_COMPANY->decodeId($_POST['organization_id'])) < 0 ||
        ($organization = Organization::GetOrganization($organization_id)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $retVal = $organization->unlinkOrganization();
    if ($retVal < 0) {
        AjaxResponse::SuccessAndExit_STRING(-1, '', "This organization cannot be deleted because it still has associated events. Please unlink all events linked to this organization before attempting to delete it.", '');
    }
    AjaxResponse::SuccessAndExit_STRING(1, '', "Organization deleted successfully", 'Success');
}

elseif (isset($_GET['addUpdateHashTagModel']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    if (
        ($hashtagid = $_COMPANY->decodeId($_GET['hashtagid']))<0 
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    $modelTitle = "New Hashtag";
    $hashtag = null;
    if ($hashtagid) {
        $modelTitle = "Update Hashtag";
        $hashtag = HashtagHandle::GetHashTagHandleById($hashtagid);
    }
	include(__DIR__ . "/views/add_update_hashtag.html");
}

elseif (isset($_GET['addUpdateHashtag']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (
        ($hashtagid = $_COMPANY->decodeId($_POST['hashtagid']))<0 ||
        ($handle = $_POST['handle']) == ''
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (HashtagHandle::CheckHashTagIsUnique($hashtagid, $handle)) {
        HashtagHandle::AddOrUpdateHashtag($hashtagid, $handle);
        AjaxResponse::SuccessAndExit_STRING(1, '', "Hashtag saved successfully", 'Success');
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Hashtag is not unique, please choose a unique tag name", 'Error');
    }
    
}
elseif (isset($_GET['showAddUpdateBlockedKeywordModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (($blocked_keyword_id = $_COMPANY->decodeId($_GET['blocked_keyword_id'])) < 0) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $blocked_keyword = null;
    if ($blocked_keyword_id) {
        $blocked_keyword = BlockedKeyword::GetBlockedKeyword($blocked_keyword_id);
    }

    include(__DIR__ . '/views/add_update_blocked_keyword_modal.html.php');
}
elseif (isset($_GET['addUpdateBlockedKeyword']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $blocked_keyword_id = $_COMPANY->decodeId($_POST['blocked_keyword_id']);
    $blocked_keyword = trim($_POST['blocked_keyword'] ?? '');

    if ($blocked_keyword_id < 0) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$blocked_keyword || strpos($blocked_keyword, ' ') !== false) {
        AjaxResponse::SuccessAndExit_STRING(
            0,
            '',
            gettext('Please enter a single word without spaces to block'),
            gettext('Error')
        );
    }

    if (!$blocked_keyword_id) {
        $id = BlockedKeyword::CreateNewBlockedKeyword($blocked_keyword);

        if ($id) {
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Blocked keyword created successfully'), gettext('Success'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('An error occured while creating blocked keyword, please try again later'), gettext('Error'));
        }
    }

    $keyword = BlockedKeyword::GetBlockedKeyword($blocked_keyword_id);
    $keyword->updateBlockedKeyword($blocked_keyword);

    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Blocked keyword updated successfully'), gettext('Success'));
}
elseif (isset($_GET['deleteBlockedKeyword']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$_USER->canManageCompanySettings()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $blocked_keyword_id = $_COMPANY->decodeId($_POST['blocked_keyword_id']);

    $blocked_keyword = BlockedKeyword::GetBlockedKeyword($blocked_keyword_id);
    $is_deleted = $blocked_keyword->deleteIt();

    if ($is_deleted) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Blocked Keyword deleted successfully'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong, please try again later'), gettext('Error'));
    }
}

elseif (isset($_GET['changeGroupCategoryStatus']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($groupCategoryId = $_COMPANY->decodeId($_POST['categoryId'])) < 1 ||
        ($status = (int)$_POST['status']) < 0
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Check if it's a default category as default category status can't be changed
    $isDefaultCategory = Group::GetGroupCategoryById($groupCategoryId)['is_default_category'] ?? 0;

    if($isDefaultCategory){
        AjaxResponse::SuccessAndExit_STRING(0, '', "Default category status cannot be changed.", '');
    }
    // Check for associated groups. Uncomment if we ever disallow changing state of GC with ERGs
    // $checkAssociatedGroups = Group::GetGroupIdsByCategoryId($groupCategoryId);
    // if($checkAssociatedGroups && $status===0){
    //     AjaxResponse::SuccessAndExit_STRING(0, '', sprintf('This category is assigned to %1$s. Please move those %1$s to different category to deactivate this category.', $_COMPANY->getAppCustomization()['group']['name-short-plural']), 'Error!');
    // }

    // update category 
    $changeGroupCategoryStatus = Group::UpdateGroupCategoryStatus($groupCategoryId, $status);
    if($changeGroupCategoryStatus){
        AjaxResponse::SuccessAndExit_STRING(1, '', "Category status updated ", '');
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', "An error occurred. Please try again later ", '');

}

elseif (isset($_GET['deleteGroupCategory']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($groupCategoryId = $_COMPANY->decodeId($_POST['categoryId'])) < 1
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Check if it's a default category as default category can't be deleted
    $isDefaultCategory = Group::GetGroupCategoryById($groupCategoryId)['is_default_category'] ?? 0;

    if($isDefaultCategory){
        AjaxResponse::SuccessAndExit_STRING(0, '', "Default category cannot be deleted.", 'Error!');
    }
    // Check for associated groups
    $checkAssociatedGroups = Group::GetGroupIdsByCategoryId($groupCategoryId);
    if($checkAssociatedGroups){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf('This category is assigned to %1$s. Please move those %1$s to different category to delete this category.', $_COMPANY->getAppCustomization()['group']['name-short-plural']), 'Error!');
    }
    // Delete category 
    $deleteGroupCategory = Group::DeleteGroupCategory($groupCategoryId);
    if($deleteGroupCategory){
        AjaxResponse::SuccessAndExit_STRING(1, '', "Category deleted ", 'Success!');
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', "An error occurred. Please try again later ", 'Error!');

}
elseif (isset($_GET['updateZoneSelectorPageLayout']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->canManageCompanySettings()) {
    header(HTTP_FORBIDDEN);
    exit();
    }
    
    $zone_selector_page_layout = in_array($_POST['zone_selector_page_layout'],array('wide_tiles','compact_tiles')) ? $_POST['zone_selector_page_layout'] : 'wide_tiles';
    $_COMPANY->updateZoneSelectorZoneLayout($zone_selector_page_layout);
    // Reload company by invalidating cache and reloading it from database
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);

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
elseif (isset($_GET['topicApprovalsReportModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        Http::Forbidden();
    }

    $topicType = $_GET['topicType'];

    $topicTypeLabel = Teleskope::TOPIC_TYPES_ENGLISH[$topicType];

    $groups = Group::GetAllGroupsByCompanyid($_COMPANY->id(), $_ZONE->id(), true);

    require __DIR__ . '/views/templates/topic_approvals_report_modal.html.php';
    exit();
}
elseif (isset($_GET['downloadTopicApprovalsReport']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        Http::Forbidden();
    }

    $tz = $_SESSION['timezone'] ?? 'UTC';

    $from_datetime = null;
    if (!empty($_POST['from'])) {
        $from_dt = "{$_POST['from']} 00:00:00";
        $from_datetime = $db->covertLocaltoUTC('Y-m-d H:i:s', $from_dt, $tz);
    }

    $to_datetime = null;
    if (!empty($_POST['to'])) {
        $to_dt = "{$_POST['to']} 23:59:59";
        $to_datetime = $db->covertLocaltoUTC('Y-m-d H:i:s', $to_dt, $tz);
    }

    $selected_group_id = null;
    if (!empty($_POST['selected_group_id'])) {
        $selected_group_id = $_COMPANY->decodeId($_POST['selected_group_id']);
    }

    $topicType = $_POST['topicType'];
    if (!in_array($topicType, array_values(Teleskope::TOPIC_TYPES))) {
        Http::Forbidden();
    }

    $reportMeta = ReportApprovals::GetDefaultReportRecForDownloadByTopicType($topicType);

    $reportMeta['Options']['topictype'] = $topicType;

    if ($from_datetime) {
        $reportMeta['Options']['startDate'] = $from_datetime;
    }
    if ($to_datetime) {
        $reportMeta['Options']['endDate'] = $to_datetime;
    }
    if (isset($selected_group_id)) {
        $reportMeta['Filters']['groupid'] = $selected_group_id;
    }

    $record = [];
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'Approvals Report';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportApprovals($_COMPANY->id(), $record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
}
elseif (isset($_GET['partnerOrganizationsReportModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Authorization Check
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        Http::Forbidden();
    }
    // Company Customized inpts for Reports
    $reportMeta = ReportOrganization::GetDefaultReportRecForDownload();
    $fields = array();
    $reportType = array();

    if ($reportMeta) {
        $fields = $reportMeta['Fields'];

        if (!empty($reportMeta['AdminFields'])) {
            $fields = array_merge($fields, $reportMeta['AdminFields']);
        }
    }
    //include the modal
    include(__DIR__ . "/views/templates/partner_organizations_report.template.php");
}
elseif (isset($_GET['downloadPartnerOrganizationsReport'])) {
    // Authorization Check
    if (!$_USER->isAdmin() || !$_USER->canViewReports()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportOrganization::GetDefaultReportRecForDownload();
    $Fields = $reportMeta['Fields'];
    $options = array();
    foreach ($Fields as $k => $v) {
        if (!isset($_POST[$k])) {
            unset($Fields[$k]);
        }
    }
    $meta = array(
        'Fields' => $Fields,
        'Options' => $options,
        'Filters' => array()
    );

    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'partnerOrganization';
    $record['reportmeta'] = json_encode($meta);
    $report = new ReportOrganization($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);
    exit();
}

elseif (isset($_GET['getOrganizationsList']) && $_SERVER['REQUEST_METHOD'] === 'POST' ){

    // add Authorization Check
    if (!$_USER->isAdmin()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    // - Skipping raw2clean as the GetOrgDataBySearchTerm uses prepared statements for safety
    //$searchValue = raw2clean($_POST['search']['value']);

    $searchValue = $_POST['search']['value'];

    $start = (int)$_POST['start'];
    $length = (int)$_POST['length'];
    $draw = (int)$_POST['draw']; // mandatory dor data table
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
    $orderFields = ['organization_id', 'organization_name', 'organization_taxid', 'City', 'State', 'contact_firstname', 'contact_lastname', 'contact_email'];
    $orderIndex = (int)$_POST['order'][0]['column'];
    $orderBy = $orderFields[$orderIndex];

    $orgData = Organization::GetOrgDataBySearchTerm($searchValue, $start, $length, false);
    $totalrows = 0;
    if (!empty($orgData)) {
        $totalrows = $orgData[0]['total_matches'];
    }

    $finalOrgData = [];
    if (!empty($orgData)) {
        foreach ($orgData as $org) {
            $partnerOrg = Organization::GetOrganizationFromPartnerPath($org['api_org_id']);
            if (!empty($partnerOrg['results'][0])) {
                $org = array_merge($org, $partnerOrg['results'][0]);
                $finalOrgData[] = $org;
            }
        }
    }

    // Sorting after fetching data for datatable
    usort($finalOrgData, function ($a, $b) use ($orderIndex, $orderFields) {
        $field = $orderFields[$orderIndex];
        if (!isset($a[$field]) || !isset($b[$field])) {
            return 0;
        }
        return strcmp($a[$field], $b[$field]);
    });
    $i = 1;
    $final = [];
    foreach ($finalOrgData as $row) {
        $orgType = $row['OrganizationType'] ? Organization::ORGANIZATION_TYPE_MAP[$row['OrganizationType']] : '';
        $encodedId = $_COMPANY->encodeId($row['organization_id']);
        $lastScreenStatus = $row['last_confirmation_status'];
        $isActive = $row['isactive'];
        $actionButtons = '';
        $actionButtons .= '<div class="dropdown" id="action' . $encodedId . '">';
        $actionButtons .= '<button id="dropdownMenuButton_' . $encodedId . '" class="btn btn-primary btn-sm dropdown-toggle" title="Action" type="button" data-bs-toggle="dropdown"> Action </button>';
        $actionButtons .= '<div class="dropdown-menu" aria-labelledby="dropdownMenuButton_' . $encodedId . '">';
        $actionButtons .= '<a aria-label="Edit" class="btn btn-no-style dropdown-item" href="add_organization?id=' . $encodedId . '"><i class="fa fa-edit text-primary px-2" aria-hidden="true"></i> Edit</a>';

        if ($isActive == 1) {
            $actionButtons .= '<button aria-label="Un-Approve"  class="btn btn-no-style" onclick="changeOrgApprovalStatus(\'' . $encodedId . '\', 0)" class=""><i class="fa fa-undo text-primary px-2" aria-hidden="true"></i> Un-Approve</button>';
        } else{   
            $actionButtons .= '<button aria-label="Approve"  class="btn btn-no-style" onclick="changeOrgApprovalStatus(\'' . $encodedId . '\', 1)" class=""><i class="fa fa-check text-primary px-2" aria-hidden="true"></i> Approve</button>';
        }
        $actionButtons .= '<button aria-label="Delete" class="btn btn-no-style confirm" onclick="unlinkOrganization(\'' . $encodedId . '\')" title="" data-original-title="<strong>Are you sure you want to Delete!</strong>"><i class="fa fa-trash fa-l text-primary px-2" title="Delete"></i> Delete</button>';
        $actionButtons .= '</div>';
        $actionButtons .= '</div>';

        $nameAddressArr = array();
        $nameAddressArr[] = '<strong>' . $row['organization_name'] . '</strong>' . (!empty($row['organization_url']) ? '<a href="' . $row['organization_url'] . '" target="_blank" rel="noreferrer noopener"> <i class="fas fa-external-link-alt px-2 text-primary" aria-hidden="true" style="font-size: 12px;"></i></a>' : '');
        if (!empty($row['Street'])) {
            $nameAddressArr[] = '<small>' . $row['Street'] . '</small>';
        }
        if (!empty($row['City']) || !empty($row['State']) || !empty($row['Country'])) {
            $nameAddressArr[] = '<small>' . trim((($row['City'] . ', ' . $row['State'] . ', ' . $row['Country'])), ', ') . '</small>';
        }
        $nameAddress = implode('<br>', $nameAddressArr);

        $aboutOrganization = "Tax ID: {$row['organization_taxid']}";
        $aboutOrganization .= "<br>Type: {$orgType}";
        if (!empty($row['contact_firstname']) || !empty($row['contact_lastname']) || !empty($row['contact_email'])) {
            $aboutOrganization .= "<br>Contact: {$row['contact_firstname']} {$row['contact_lastname']} ({$row['contact_email']})";
        }

          $approvalStatus = '';
        switch ($row['last_confirmation_status']) {
            case 1:  // confirmed
                $approvalStatus = '<span class="label px-2 mx-2" style="background-color: darkgreen; color: white; border-radius: 3px;">Confirmed</span><br><small>confirmed on ' . $_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['last_confirmation_date'], true, true, false) . '</small>';
                break;
            case 2: // in progress, email sent
                $approvalStatus = '<span class="label px-2 mx-2" style="background-color: darkblue; color: white; border-radius: 3px;">Pending Confirmation</span><br><small>email sent on ' . $_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['last_confirmation_date'], true, true, false) . '</small>';
                break;
            default: // reset, no data available for new organization
                $approvalStatus = '<span class="label px-2 mx-2" style="background-color: #767676; color: white; border-radius: 3px;">New Organization</span>';
        }

            $isActive = '<span class="label px-2 mx-2" style="background-color: darkred; color: white; border-radius: 3px;">Not-Approved</span>';
        if($row['isactive'] == 1){
            $isActive = '<span class="label px-2 mx-2" style="background-color: darkgreen; color: white; border-radius: 3px;">Approved</span>';
        }elseif($row['isactive'] == 2){
            $isActive = '<span class="label px-2 mx-2" style="background-color: #767676; color: white; border-radius: 3px;">Draft</span>';
        }
        $org = Organization::GetOrganization($row['organization_id']);
        $associatedEvents = $org->getAssociatedEvents();
        $numberOfAssociatedEvents = !empty($associatedEvents) ? $associatedEvents[0]['total_matches'] : 0;


        $final[] = array(
            "DT_RowId" => $i,
            $_COMPANY->encodeIdForReport($row['organization_id']),
            $nameAddress,
            $aboutOrganization,
            $approvalStatus,
            $isActive,
            $numberOfAssociatedEvents,
            $actionButtons
        );
        $i++;
    }

    $json_data = array(
        "draw" => intval($draw),
        "recordsTotal" => intval(count($final)),
        "recordsFiltered" => intval($totalrows),
        "data" => $final
    );
    echo json_encode($json_data);
}

elseif (isset($_GET['getApprovalEmailConfigurationModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    if (
        ($approval_config_id = $_COMPANY->decodeId($_GET['approval_config_id'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $topic_type = $_GET['topic_type'];
    if (!in_array($topic_type,array_values(TELESKOPE::TOPIC_TYPES))){
        AjaxResponse::SuccessAndExit_STRING(0, '', "Topic Type is not valid", 'Error');
    }
    $approvalConfigurationDetail = null;
    $topicName = TELESKOPE::TOPIC_TYPES_ENGLISH[$topic_type];
    $TopicClass = Teleskope::TOPIC_TYPE_CLASS_MAP[$topic_type];
    [$approver_cc_emails, $approver_min_approvers_limit, $approver_max_approvers_limit, $stage_approval_email_subject, $stage_approval_email_body, $stage_denial_email_subject, $stage_denial_email_body, $disallow_submitter_approval] = $TopicClass::GetApprovalConfigurationEmailSettings($approval_config_id);
    include(__DIR__ . "/views/templates/approval_email_configuration.template.php");
}

elseif (isset($_GET['saveApprovalEmailConfiguration']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (
        ($approval_config_id = $_COMPANY->decodeId($_POST['approval_config_id'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $topic_type = $_POST['topic_type'];
    if (!in_array($topic_type,array_values(TELESKOPE::TOPIC_TYPES))){
        AjaxResponse::SuccessAndExit_STRING(0, '', "Topic Type is not valid", 'Error');
    }

    $disallow_submitter_approval = $_POST['disallow_submitter_approval'] ? 1 : 0;
    $emails = $_POST['approver_cc_emails'] ?? '';
    $invalidEmails = array();
    $validEmails = array();
    foreach (array_unique(explode(',',$emails)) as $e) {
        $e = trim($e);
        if (empty($e)){
            continue;
        }
        if (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
            array_push($invalidEmails, $e);
        } elseif (!$_COMPANY->isValidEmail($e)) {
            array_push($invalidEmails, $e);
        } else{
            array_push($validEmails, $e);
        }
    }
    if (!empty($invalidEmails)){
        AjaxResponse::SuccessAndExit_STRING(0, '', implode(', ', $invalidEmails)." are not valid email addresses. Please correct and try again.", 'Error!');
    }

    $approver_max_approvers_limit = intval($_POST['approver_max_approvers_limit'] ?? Approval::APPROVERS_SELECTED_DEFAULT);
    $stage_approval_email_subject = $_POST['stage_approval_email_subject'] ?? '';
    $stage_approval_email_body = $_POST['stage_approval_email_body'] ?? '';
    $stage_denial_email_subject = $_POST['stage_denial_email_subject'] ?? '';
    $stage_denial_email_body = $_POST['stage_denial_email_body'] ?? '';

    try {
        $stage_approval_email_body = Html::RedactorContentValidateAndCleanup($stage_approval_email_body);
        $stage_denial_email_body = Html::RedactorContentValidateAndCleanup($stage_denial_email_body);
    } catch (Exception $e) {
        $error_message = ($e->getCode() == -1)
            ? gettext('The content contains links to external images which may not display properly on all devices. In order to fix this, attach images from your computer.')
            : gettext('Unknown error.');
        AjaxResponse::SuccessAndExit_STRING(0, '', $error_message, gettext('Error'));
    }

    $TopicClass = Teleskope::TOPIC_TYPE_CLASS_MAP[$topic_type];
    $TopicClass::UpdateApprovalConfigurationEmailSettings($approval_config_id, implode(',',$validEmails), $approver_max_approvers_limit, $stage_approval_email_subject, $stage_approval_email_body, $stage_denial_email_subject, $stage_denial_email_body, $disallow_submitter_approval);

    $topicName = TELESKOPE::TOPIC_TYPES_ENGLISH[$topic_type];
    AjaxResponse::SuccessAndExit_STRING(1, '', $topicName." approval stage configuration updated successfully", 'Success');

}


elseif (isset($_GET['addUpdateEmailEventCommunicationTemplateModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Authorization Check
    if (!$_USER->isCompanyAdmin() && !$_USER->isAdmin()) {
        Http::Forbidden();
    }

    $pagetitle = gettext("Create Event Communication Template");
    $communication_type_key = $_GET['communication_type_key'];
    if ($communication_type_key && !isset(Event::EVENT_COMMUNICATION_TYPES[$communication_type_key])) {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Invalid communication type found.", 'Error');
    }
    $communication_type_keys   =  Event::EVENT_COMMUNICATION_TYPES;
    if (!$_COMPANY->getAppCustomization()['event']['reconciliation']['enabled']) {
        unset($communication_type_keys['reconciliation']);
    }

    $existingTemplates = Event::GetEventCommunicationTemplatesForZone($_ZONE);
 
    $emailTemplate = Event::GetEventCommunicationTemplatesForZone($_ZONE, $communication_type_key);
    //include the modal
    include(__DIR__ . "/views/templates/new_event_communication_template_modal.php");
}

elseif (isset($_GET['addUpdateEventCommunicationTemplate']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->isCompanyAdmin() && !$_USER->isAdmin()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $communication_type_key = $_POST['communication_type_key'];
    if (!$communication_type_key || !isset(Event::EVENT_COMMUNICATION_TYPES[$communication_type_key])) {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Plase select a valid communication template type.", 'Error');
    }

    $event_communication_email_subject	= $_POST['event_communication_email_subject'] ?? '';
    if (empty(trim($event_communication_email_subject))){
        AjaxResponse::SuccessAndExit_STRING(0, '', "Email Subject is required field.", 'Error');
    }

    $event_communication_email_body	= $_POST['event_communication_email_body'] ?? '';
    $event_communication_email_body = Html::RedactorContentValidateAndCleanup($event_communication_email_body);
    if (empty(trim($event_communication_email_body))){
        AjaxResponse::SuccessAndExit_STRING(0, '', "Email Body is required field.", 'Error');
    }

    $event_communication_trigger_days = $_POST['event_communication_trigger_days'] ?: 0;

    if (
        // Update event_communication_email_subject
        Event::SetEventCommunicationTemplateForZone($communication_type_key, $event_communication_email_subject, $event_communication_email_body, $event_communication_trigger_days)
    ){
        AjaxResponse::SuccessAndExit_STRING(1, '', "Email template data saved successfully.", 'Success');
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Something went wrong while saving data. Please try again.", 'Error!');
    }
}

elseif (isset($_GET['updateOrganizationEmailsTemplate']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->isCompanyAdmin() && !$_USER->isAdmin()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $organization_email_type = $_POST['organization_email_type'];
    if ($organization_email_type && !isset(Organization::ORGANIZATION_EMAIL_TYPES[$organization_email_type])) {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Invalid organization email type found.", 'Error');
    }

    $organization_email_subject	= $_POST['organization_email_subject'] ?: null;
    $organization_email_body	= $_POST['organization_email_body'] ?: null;

//    if (empty(trim($organization_email_subject))){
//        AjaxResponse::SuccessAndExit_STRING(0, '', "Email Subject is required field.", 'Error');
//    }
//    if (empty(trim($organization_email_body))){
//        AjaxResponse::SuccessAndExit_STRING(0, '', "Email Body is required field.", 'Error');
//    }

    if (
        Organization::SetOrganizationEmailTemplate($organization_email_type,$organization_email_subject, $organization_email_body)
    ){
        AjaxResponse::SuccessAndExit_STRING(1, '', "Organization email template data saved successfully.", 'Success');
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Something went wrong while saving data. Please try again.", 'Error!');
    }
}

elseif (isset($_GET['deleteEmailEventCommunicationTemplateModal']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (!$_USER->isCompanyAdmin() && !$_USER->isAdmin()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $communication_type_key = $_POST['communication_type_key'];
    if (!$communication_type_key || !isset(Event::EVENT_COMMUNICATION_TYPES[$communication_type_key])) {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Communication template type is not valid.", 'Error');
    }

    if (
        // Remove Event communication Template
        Event::DeleteEventCommunicationTemplate($communication_type_key)
    ){
        AjaxResponse::SuccessAndExit_STRING(1, '', "Event template deleted successfully.", 'Success');
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', "Something went wrong while saving data. Please try again.", 'Error!');
    }
}elseif (isset($_GET['previewSurvey'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
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
elseif (isset($_GET['deleteApprovalStage']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic_type = $_POST['topic_type'];

    if ($topic_type === TELESKOPE::TOPIC_TYPES['EVENT']) {
        $auth_check = $_COMPANY->getAppCustomization()['event']['approvals']['enabled'] && $_USER->canManageZoneEvents();
    } elseif ($topic_type === TELESKOPE::TOPIC_TYPES['NEWSLETTER']) {
        $auth_check = $_COMPANY->getAppCustomization()['newsletters']['approvals']['enabled'] && $_USER->canManageAffinitiesContent();
    } elseif ($topic_type === TELESKOPE::TOPIC_TYPES['POST']) {
        $auth_check = $_COMPANY->getAppCustomization()['post']['approvals']['enabled'] && $_USER->canManageAffinitiesContent();
    }

    if (!$auth_check) {
        AjaxResponse::SuccessAndExit_STRING(0, '', 'You are not authorized to perform this action', 'Error!');
    }

    $approval_config_id = $_COMPANY->decodeId($_POST['approval_config_id']);

    $topic_approval_configuration = TopicApprovalConfiguration::GetApprovalConfiguration($approval_config_id);

    $reason = $topic_approval_configuration->getWhyCannotDeleteIt();

    if ($reason === 'NOT_LAST_STAGE') {
        AjaxResponse::SuccessAndExit_STRING(0, '', 'This approval stage cannot be deleted.  Approval stages must be deleted in reverse order. Please delete the last stage in the sequence first.', 'Error!');
    }

    if ($reason === 'HAS_APPROVERS') {
        AjaxResponse::SuccessAndExit_STRING(0, '', 'This approval stage cannot be deleted because it still has approvers assigned. Please remove all approvers from this stage first, and then try deleting the stage again.', 'Error!');
    }

    if ($reason === 'HAS_AUTO_APPROVAL_CONFIG') {
        AjaxResponse::SuccessAndExit_STRING(0, '', 'This approval stage cannot be deleted because it has an auto-approval configuration.  Please delete the auto-approval configuration first, then try deleting the stage again.', 'Error!');
    }

    if ($reason === 'HAS_APPROVAL_TOPICS') {
        AjaxResponse::SuccessAndExit_STRING(0, '', 'This approval stage cannot be deleted because it contains existing approvals.', 'Error!');
    }

    $topic_approval_configuration->deleteIt();

    AjaxResponse::SuccessAndExit_STRING(1, '', 'Approval stage deleted successfully', 'Success');
}
elseif (isset($_GET['download_delegated_access_report']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Authorization Check
    if (
        !$_USER->canManageAffinitiesUsers()
        || !$_USER->canViewReports()
        || !$_COMPANY->getAppCustomization()['profile']['allow_delegated_access']
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $reportMeta = ReportDelegatedAccessAuditLog::GetDefaultReportRecForDownload();
    $record = [];
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'Delegated Access Audit Logs Report';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportDelegatedAccessAuditLog($_COMPANY->id(), $record);
    $report->downloadReportAndExit(Teleskope::FILE_FORMAT_CSV);

    exit();
}
elseif (isset($_GET['updateZoneVisibilityForCatalogCategories']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Authorization Check
    if (!$_USER->isCompanyAdmin() && !$_USER->isAdmin()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (($catalog_id = $_COMPANY->decodeId($_POST['catalog_id'])) < 1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $zone_ids_arr = isset($_POST['zone_ids']) ? $_COMPANY->decodeIdsInArray($_POST['zone_ids']) : [];
    $zone_ids = implode(',', $zone_ids_arr);

    // Update the catalogid with the newly selected zoneids
     $result = UserCatalog::UpdateZoneVisibility($catalog_id, $zone_ids);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Updated successfully.'), gettext('Success'));
}
elseif (isset($_GET['viewCatalogStatsModal']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Authorization Check
    if (!$_USER->isCompanyAdmin() && !$_USER->isAdmin()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    //Data Validation
    if (($catalog_categoryid = $_COMPANY->decodeId($_POST['catalog_categoryid'])) < 1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $statRows = UserCatalog::FetchAllStatistics($catalog_categoryid);
    $data = array_map(function($row){
        return [
            'user_catalog_category' => $row['user_catalog_category'],
            'user_catalog_keyname' => $row['user_catalog_keyname'],
            'source_id' => $row['source_id'],
            'num_users' => $row['user_count'],
            'last_updated' => $row['createdon'] . ' UTC',
        ];
    }, $statRows);
    if($data){
        $statsData = json_encode($data);
         AjaxResponse::SuccessAndExit_STRING(1, $statsData, gettext('Success.'), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Unknown error'), gettext('Error'));
}
else {
    Logger::Log ("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}
?>
