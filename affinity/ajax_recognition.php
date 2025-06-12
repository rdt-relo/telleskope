<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__.'/head.php';

/* @var Company $_COMPANY */
/* @var User $_USER */

###### All Ajax Calls Regarding recognitions ##########

// GET recognitions

if (isset($_GET['getRecognitions'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($recognition_type = $_COMPANY->decodeId($_GET['recognition_type']))<0
    ) {
        header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
        exit();
    }

    // Authorization Check
    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $recognize_a_colleague = Recognition::RECOGNITION_TYPES['recognize_a_colleague'];
    $recognize_my_self = Recognition::RECOGNITION_TYPES['recognize_my_self'];
    $received_recognitions = Recognition::RECOGNITION_TYPES['received_recognitions'];
    $sectionTitle = Recognition::GetCustomName(true);
    $page = 1;
    $limit = 10;
    $max_items = $limit + 1; // We are adding 1 additional row to enable 'Show More to work'
    $start = (($page - 1) * $limit);

    $recognitions = Recognition::GetRecognitions($groupid,$recognition_type, true, 0, "", "recognition_date", "DESC", $start, $max_items);
    $max_iter = count($recognitions);
    $show_more = ($max_iter > $limit);
    $max_iter = $show_more ? $limit : $max_iter;

    if ($_GET['reload']){
	    include(__DIR__ . '/views/recognitions/get_recognitions.template.php');
    } else {
        if($show_more){ ?>
        <script>
            $("#loadeMoreRecognitionAction").show();
        </script>
    <?php } else { ?>
        <script>
            $("#loadeMoreRecognitionAction").hide();
        </script>
    <?php }

        include(__DIR__ . '/views/recognitions/recognition_rows.template.php');
    }
}

elseif (isset($_GET['manageRecognitions']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $enc_groupid=$_COMPANY->encodeId(0);
    if ($_GET['manageRecognitions']){
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
    // Company Customized inpts for Reports
    $repofieldsrtMeta = ReportRecognitions::GetDefaultReportRecForDownload();
    $fields = $repofieldsrtMeta['Fields'];
    include(__DIR__ . '/views/recognitions/manage/manage_recognitions.template.php');
    exit();
}

elseif (isset($_GET['getManageRecognitionsList']) && $_SERVER['REQUEST_METHOD'] === 'POST' ){

    if (($groupid = $_COMPANY->decodeId($_GET['getManageRecognitionsList']))<0 ||
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

    if (!empty($_GET['groupStateType'])){
        $groupStateType = (int) $_COMPANY->decodeId($_GET['groupStateType']);
    }

    if(!empty($_GET['groupState']) && ($groupStateId = $_COMPANY->decodeId($_GET['groupState'])) <0){
        header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
        exit();
    }
    $input = array('search'=>raw2clean($_POST['search']['value']),'start'=>(int)$_POST['start'],'length'=>(int)$_POST['length'],'draw'=>(int)$_POST['draw']);

    $isactiveCondition = " AND a.isactive='".Recognition::STATUS_ACTIVE."'";

    if ($isactive =='1'){ // Only Active
        $isactiveCondition = " AND a.isactive='".Recognition::STATUS_ACTIVE."'";
    } elseif ($isactive =='2'){ // Draft
        $isactiveCondition = " AND a.isactive= '".Recognition::STATUS_DRAFT."'";
    }

    $orderFields = ['person_recognized','person_recognizing','recognition_date'];
    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
    $year = $_COMPANY->decodeId($_GET['year']);

    $totalrows = Recognition::GetRecognitions($groupid, 0, $isactive, $year, $input['search'], $orderFields[$orderIndex], $orderDir, $input['start'], $input['length'],1);
    $rows = Recognition::GetRecognitions($groupid, 0, $isactive, $year, $input['search'], $orderFields[$orderIndex], $orderDir, $input['start'], $input['length']);
    $final = [];
    foreach($rows as $row){  
        $recognizedby ="";
        $encRecognitionid = $_COMPANY->encodeId($row['recognitionid']);
         if($row['recognizedby'] != 0){ 
            $recognizedBy = User::GetUser($row['recognizedby']);
            $firstname_by = "";
            $lastname_by = "";
            if($recognizedBy !== null)
            {
              $firstname_by = $recognizedBy->val('firstname');
              $lastname_by = $recognizedBy->val('lastname');
            }
            $recognizedby = User::BuildProfilePictureImgTag($firstname_by,$lastname_by, $row['picture_by'],'memberpic2', 'User Profile Picture', $row['recognizedby'], 'profile_basic');
         }
         if($row['recognizedby'] == 0){ 
            $recognizedby .=  '&nbsp;'.$row['recognizedby_name'];
        }else{ 
         $recognizedby .= '&nbsp;'.$firstname_by.' '.$lastname_by;
         }
        $recognizedby .= '<br/><small>'.$row['email_by'].'</small>';
        if ($row['email_by'] && $row['recognizedby'] != 0){
            $jobTitle = $recognizedBy !== null ? $recognizedBy->val('jobtitle') : '';
            $recognizedby .= '<br/><small>'.$jobTitle.'</small>';
        }

        $recognizedto = User::BuildProfilePictureImgTag($row['firstname_to'],$row['lastname_to'], $row['picture_to'],'memberpic2', 'User Profile Picture', $row['recognizedto'], 'profile_basic');
        $recognizedto .= '&nbsp;'.$row['firstname_to'].' '.$row['lastname_to'];
        $recognizedto .= '<br/><small>'.$row['email_to'].'</small>';
        if ($row['email_to']){
            $recognizedto .= '<br/><small>'.$row['jobtitle_to'].'</small>';
        }

        $recognitiondate = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['recognitiondate'],true,false,false); 
        $actionButton = '';
         if($_USER->canPublishOrManageContentInScopeCSV($row['groupid']) || ($_USER->id() == $row['createdby'])){
        $actionButton = '<div class="" style="color: #fff; float: left;">';
        $actionButton .= '<button aria-label="'.sprintf(gettext('%1$s %2$s Recognition action dropdown'), $row['firstname_to'],$row['lastname_to']).'" onclick="getRecognitionActionButton(\''.$encRecognitionid.'\',this,1)" class="btn-no-style dropdown-toggle fa fa-ellipsis-v mt-3" title="Action" type="button" data-toggle="dropdown"></button>';
        $actionButton .= '<ul class="dropdown-menu dropdown-menu-right" id="dynamicActionButton'.$encRecognitionid.'" style="width: 250px; cursor: pointer;">';
        $actionButton .= '</ul>';
        $actionButton .= '</div>';
        }
        
        $final[] = array("DT_RowId" => $row['recognitionid'],$recognizedto,$recognizedby,$recognitiondate,$actionButton);
    }
    $json_data = array(
                    "draw"=> intval( $input['draw'] ), 
                    "recordsTotal"    => intval($totalrows),
                    "recordsFiltered" => intval($totalrows), 
                    "data"            => $final
                );
      

    

    echo json_encode($json_data);

}

elseif (isset($_GET['openNewRecognitioinModal'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($recognitionid = $_COMPANY->decodeId($_GET['recognitionid']))<0 || 
        ($recognition_type = $_COMPANY->decodeId($_GET['recognition_type']))<0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check is user is a member
    $recognition = null;
    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $formTitle = gettext("New recognition");
    $submitButton = gettext("Submit"); 
    $custom_fields = Recognition::GetEventCustomFields();
    $fontColors = $groupid > 0 ? json_encode(array($group->val('overlaycolor'),$group->val('overlaycolor2'))) : json_encode(array());
	include(__DIR__ . "/views/recognitions/new_recognition.template.php");
}elseif (isset($_GET['openRecognitioinModalForUpdate'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){


     //Data Validation
     if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($recognitionid = $_COMPANY->decodeId($_GET['recognitionid']))<0 || 
        ($recognition_type = $_COMPANY->decodeId($_GET['recognition_type']))<0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $checkform = 0;
    if(!empty($_GET["checkform"]))
    {
        $checkform = 1;
    }

    // Authorization Check is user is a member
    $recognition = null;
    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    
    $submitButton = gettext("Submit"); 
    
     if($recognitionid > 0)
     {
        $formTitle = gettext("Update recognition");  
        $recognition = Recognition::GetRecognition($recognitionid);  
     }
     
    $custom_fields = Recognition::GetEventCustomFields();
    $event_custom_fields = [];
    if ($recognition) {
        $event_custom_fields = json_decode($recognition->val('custom_fields') ?? '', true) ?? [];
    }

    $fontColors = $groupid > 0 ? json_encode(array($group->val('overlaycolor'),$group->val('overlaycolor2'))) : json_encode(array());
	include(__DIR__ . "/views/recognitions/update_recognition.template.php");



}
elseif (isset($_GET['searchUserForRecognition']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $keyword = raw2clean($_GET['keyword']);
    $readonly = $uid = "";
    if(isset($_GET['uid']))
    {
      $uid = $_COMPANY->decodeId($_GET['uid']);
    }

    $recognizeby = 0;
    if(isset($_GET["recognizeby"]))
    {
        $recognizeby = 1;
    }
   
    if(!empty($_GET["editpart"])){ $readonly = "readonly"; }

    
 
    $searchAllUsers = intval($_GET['searchAllUsers'] == "true");


    if ($keyword || !empty($uid)) {
        $excludeCondition = "";
        $searchAllUsersConditon = "";

        if (!$searchAllUsers){
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
                if (($_USER->id()!=$activeuser['userid']) || $recognizeby > 0 || empty($keyword)){
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

elseif (isset($_GET['configureRecognitionCustomFields']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 ||
        ($group = Group::GetGroup($groupid)) === null ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
    }
    $formTitle = gettext("Configure Recognition Module");

    include(__DIR__ . "/views/recognitions/manage/get_group_recongintion_customfields.template.php");
}

elseif(isset($_GET['updateRecognitionCustomFields']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($custom_field_id = $_COMPANY->decodeId($_POST['custom_field_id']))<1
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
    }

    $action = $_POST['action'];
    $group->updateRecognitionCustomFields($custom_field_id,$action);
    if ($action == 'delete') { ?>
        <button class="btn btn-affinity confirm pop-identifier" onclick="updateRecognitionCustomFields('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($custom_field_id); ?>','add')" title="<strong>Are you sure you want to Add!</strong>" ><?=gettext("Add");?></button>    

    <?php } else { ?>
        <button class="btn btn-affinity confirm pop-identifier" onclick="updateRecognitionCustomFields('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($custom_field_id); ?>','delete')" title="<strong>Are you sure you want to remove!</strong>" ><?=gettext("Remove");?></button>
        
    <?php }
}
elseif(isset($_GET['updateRecognitionSettings']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
    }

    $enable_user_view_recognition = !empty($_POST['enable_user_view_recognition']);
    $enable_self_recognition = !empty($_POST['enable_self_recognition']);
    $enable_colleague_recognition = !empty($_POST['enable_colleague_recognition']);

    if (!$enable_user_view_recognition) {
        $enable_self_recognition = false;
        $enable_colleague_recognition = false;
    }

    $group->updateRecognitionSettings($enable_user_view_recognition, $enable_self_recognition, $enable_colleague_recognition);
}
elseif (isset($_GET['getRecognitionActionButton']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($recognitionid = $_COMPANY->decodeId($_GET['recognitionid']))<0 || 
        ($recognition = recognition::Getrecognition($recognitionid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    
      $checkform = 0;
    if(!empty($_GET["checkform"]))
    {
        $checkform = 1;
    }

    if(!$_USER->canPublishOrManageContentInScopeCSV($recognition->val('groupid')) && ($_USER->id() != $recognition->val('createdby'))){
        //Allow creators to delete unpublished content
        header(HTTP_FORBIDDEN);
        exit();
    }
    include(__DIR__ . "/views/recognitions/recognition_action_button.template.php");
}

elseif (isset($_GET['deleteRecognition']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($recognitionid = $_COMPANY->decodeId($_POST['recognitionid']))<0 || 
        ($recognition = recognition::Getrecognition($recognitionid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($_USER->id() != $recognition->val('recognizedby') && !$_USER->canManageGroupSomething($recognition->val('groupid'))
    ) { //Allow creators to delete unpublished content
        header(HTTP_FORBIDDEN);
        exit();
    }
   
    if ($recognition->inactivateIt()){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Recognition deleted successfully"), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
    }

}

elseif (isset($_GET['loadMoreRecognition'])){
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($recognition_type = $_COMPANY->decodeId($_GET['recognition_type']))<0
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
    $start = (($page - 1) * $limit);

    $recognitions = Recognition::GetRecognitions($groupid,$recognition_type, 1, 0, "", "recognition_date", "DESC", $start, $limit);

    $max_iter = count($recognitions);
    $show_more = ($max_iter > $limit);
    $max_iter = $show_more ? $limit : $max_iter;
	include(__DIR__ . '/views/recognitions/recognition_rows.template.php');
}


elseif (isset($_GET['viewRecognitionDetial']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($recognitionid = $_COMPANY->decodeId($_GET['recognitionid']))<0 || 
        ($recognition = recognition::Getrecognition($recognitionid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->canViewContent($recognition->val('groupid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $recognition_type = ($recognition->val('createdby') == $recognition->val('recognizedto')) ? Recognition::RECOGNITION_TYPES['recognize_my_self'] : Recognition::RECOGNITION_TYPES['recognize_a_colleague'];

    $modalTitle = gettext("Recognition Detail");

    include(__DIR__ . "/views/recognitions/view_recognition_modal.template.php");
}

elseif(isset($_GET['filterRecognitions'])){
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 )
    {
        header('HTTP/1.1 400 Bad Request (Missing or malformed parameters)');
        exit();
    }
    $enc_groupid=$_COMPANY->encodeId($groupid);
  	include(__DIR__ . '/views/recognitions/manage/recognition_table_view.template.php');
}


else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}
