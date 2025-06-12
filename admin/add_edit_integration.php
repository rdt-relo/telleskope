<?php
require_once __DIR__.'/head.php';

// Authorization Check
if (!$_USER->canManageAffinitiesContent() || !$_COMPANY->getAppCustomization()['integrations']['group']['enabled']) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}
$groupid    = 0;
$chapterid = 0;
$channelid = 0;
$edit = null;
$integrationid = 0;
$fb_groupid = "";
$slack_groupid= "";
$link_unfurling = false;
$integration_json_arr= array();
$editintegration =array();
$integrationtype = array();
foreach (Integration::EXTERNAL_TYPES as $k => $v) { // Keep only the enabled integrations
    if ($_COMPANY->getAppCustomization()['integrations']['group'][$k]) {
        $integrationtype[$k] = $v;
    }
}
if (!isset($_GET['gid']) ||
    ($groupid = $_COMPANY->decodeId($_GET['gid']))<1 ||
    (isset($_GET['integrationid']) && ($integrationid = $_COMPANY->decodeId($_GET['integrationid']))<0)||
    (isset($_GET['cid']) && ($chapterid = $_COMPANY->decodeId($_GET['cid']))<0)||
    (isset($_GET['chid']) && ($channelid = $_COMPANY->decodeId($_GET['chid']))<0)||
    ($group = Group::GetGroup($groupid)) == null) {
    header(HTTP_BAD_REQUEST);
    exit();
}

    $encGroupId = $_COMPANY->encodeId($groupid);
    $encchapterId = $_COMPANY->encodeId($chapterid);
    $encchannelId = $_COMPANY->encodeId($channelid);


$form = "Add New Integration";
if ($integrationid > 0) {   
    $form = "Edit";
    $editintegration = GroupIntegration::GetGroupIntegration($integrationid);   
    if (!$editintegration) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $integration_json_arr = json_decode($editintegration->val('integration_json'),true);
    if (empty($integration_json_arr['external']['access_token'])) {
        $integration_json_arr['external']['access_token_mask'] = 'not ************************ set';
    } else {
        $integration_json_arr['external']['access_token_mask'] = substr($integration_json_arr['external']['access_token'], 0, 5);
        $integration_json_arr['external']['access_token_mask'] .= '********************************';
        if (strlen($integration_json_arr['external']['access_token']) > 5) {
            $roffset = min(strlen($integration_json_arr['external']['access_token'])-5 , 5);
            $integration_json_arr['external']['access_token_mask'] .= substr($integration_json_arr['external']['access_token'], -($roffset));
        }
    }
}

if (isset($_POST['submit'])) { // Add or Update  

    $selectedintegrationtype = 0;
    if(isset($_POST['integrationtype'])){
        $selectedintegrationtype = $_COMPANY->decodeId($_POST['integrationtype']);
    }

    $data = array();     
    $groupData =array();
    $groupData['post'] = isset($_POST['post']) && in_array(1, $_POST['post']) ? true : false;
    $groupData['events'] = isset($_POST['events']) && in_array(1, $_POST['events']) ? true : false;
    $groupData['newsletter'] = isset($_POST['newsletter']) && in_array(1, $_POST['newsletter']) ? true : false;  

    $chapter =array();    
    $chapter['post'] = isset($_POST['post']) && in_array(2, $_POST['post']) ? true : false;
    $chapter['events'] = isset($_POST['events']) && in_array(2, $_POST['events']) ? true : false; 
    $chapter['newsletter'] = isset($_POST['newsletter']) && in_array(2, $_POST['newsletter']) ? true : false;

    $channel=array();    
    $channel['post'] = isset($_POST['post']) && in_array(3, $_POST['post']) ? true : false;
    $channel['events'] = isset($_POST['events']) && in_array(3, $_POST['events']) ? true : false;  
    $channel['newsletter'] = isset($_POST['newsletter']) && in_array(3, $_POST['newsletter']) ? true : false;

    $integrationname = isset($_POST['integrationname']) ? $_POST['integrationname'] : '';
    $preselectpublish = isset($_POST['preselectpublish']) && $_POST['preselectpublish'] == 1 ? true : false;

   
if($selectedintegrationtype == Integration::EXTERNAL_TYPES['workplace']){
         
    if(isset($_POST['worklinkunfurling']) && $_POST['worklinkunfurling'] == 1){
        $link_unfurling = true;
    }else{
        $link_unfurling = false;
    } 
    $access_token = $_POST['workaccesstoken'];

    if($integrationid > 0 ){       

        // Facebook groupid cannot be changed if set.
        $fb_groupid = $integration_json_arr['external']['fb_groupid'] ?: $_POST['workgroupid'];

        // Check if the access token was updated, if not then set it to the same value as previous
        if ($access_token == $integration_json_arr['external']['access_token_mask']) {
            $access_token = $integration_json_arr['external']['access_token'];
        }

        $editintegration->updateFbWorkplaceIntegration($integrationname,$access_token,$fb_groupid,$link_unfurling,$groupData,$chapter,$channel,$preselectpublish);     
      
    }else{
        $fb_groupid = $_POST['workgroupid'];       

        GroupIntegration::CreateNewFBWorkplaceIntegration($integrationname,$groupid,$chapterid,$channelid,$access_token,$fb_groupid,$link_unfurling,$groupData,$chapter,$channel,$preselectpublish);     
        
    }
    

 }else if($selectedintegrationtype == Integration::EXTERNAL_TYPES['yammer']){
    
    $yammer_access_token = $_POST['yammeraccesstoken'];

    
    if($integrationid > 0 ){  

        // Yammer Groupid cannot be changed if set.
        $yammer_groupid = $integration_json_arr['external']['yammer_groupid'] ?: $_POST['yammergroupid'];

        // Check if the yammer access token was updated, if not then set it to the same value as previous
        if ($yammer_access_token == $integration_json_arr['external']['access_token_mask']) {
            $yammer_access_token = $integration_json_arr['external']['access_token'];
        }

        $editintegration->updateYammerIntegration($integrationname,$yammer_access_token,$yammer_groupid,$groupData,$chapter,$channel,$preselectpublish);             
       
        
    }else{
        $yammer_groupid = $_POST['yammergroupid'];

        GroupIntegration::CreateNewYammerIntegration($integrationname,$groupid,$chapterid,$channelid,$yammer_access_token,$yammer_groupid,$groupData,$chapter,$channel,$preselectpublish);
     
    }
                   
 }else if($selectedintegrationtype == Integration::EXTERNAL_TYPES['teams']){
    
    $access_token = $_POST['teamsaccesstoken'];

    
    if($integrationid > 0 ){

        // Check if the teams access url was updated, if not then set it to the same value as previous
        if ($access_token == $integration_json_arr['external']['access_token_mask']) {
            $access_token = $integration_json_arr['external']['access_token'];
        }
        $editintegration->updateTeamsIntegration($integrationname,$access_token,$groupData,$chapter,$channel,$preselectpublish);

    }else{

        GroupIntegration::CreateNewTeamsIntegration($integrationname,$groupid,$chapterid,$channelid,$access_token,$groupData,$chapter,$channel,$preselectpublish);
     
    }
                   
 }else if($selectedintegrationtype == Integration::EXTERNAL_TYPES['googlechat']){
    
    $access_token = $_POST['googlechataccesstoken'];


    if($integrationid > 0 ){

        // Check if the google chat access url was updated, if not then set it to the same value as previous
        if ($access_token == $integration_json_arr['external']['access_token_mask']) {
            $access_token = $integration_json_arr['external']['access_token'];
        }
        $editintegration->updateGoogleChatIntegration($integrationname,$access_token,$groupData,$chapter,$channel,$preselectpublish);

    }else{

        GroupIntegration::CreateNewGoogleChatIntegration($integrationname,$groupid,$chapterid,$channelid,$access_token,$groupData,$chapter,$channel,$preselectpublish);
     
    }
                   
 }else if($selectedintegrationtype == Integration::EXTERNAL_TYPES['slack']){
    $slack_groupid = $_POST['slackchannelid'];
    $slack_access_token = $_POST['slackaccesstokenname'];
    

    if($integrationid > 0 ){  
        $link_unfurling = false;

        // Group id cannot be changed if set.
        $slack_groupid = $integration_json_arr['external']['slack_groupid'] ?: $_POST['slackchannelid'];

        // Check if the slack access token was updated, if not then set it to the same value as previous
        if ($slack_access_token == $integration_json_arr['external']['access_token_mask']) {
            $slack_access_token = $integration_json_arr['external']['access_token'];
        }

        $editintegration->updateSlackIntegration($integrationname,$slack_access_token,$slack_groupid,$groupData,$chapter,$channel,$link_unfurling,$preselectpublish);        
     
    }else{

        GroupIntegration::CreateNewSlackIntegration($integrationname,$groupid,$chapterid,$channelid,$slack_access_token,$slack_groupid,$groupData,$chapter,$channel,$preselectpublish);
       
    }
  
 }  
    if($chapterid){          
        Http::Redirect("integration?gid={$encGroupId}&cid=$encchapterId");
    }elseif($channelid){
    
        Http::Redirect("integration?gid={$encGroupId}&chid=$encchannelId");

    }else{
        Http::Redirect("integration?gid={$encGroupId}");
    }

}
// Else generate data needed to show the form.

$subTitle = "";
if ($chapterid){
    $chapter = $group->getChapter($chapterid);
    if ($chapter){
        $subTitle = $chapter['chaptername']." ".$_COMPANY->getAppCustomization()['chapter']["name-short"]. "> ";
    }
}

if ($channelid){
    $channel = $group->getChannel($channelid);
    if ($channel){
        $subTitle = $channel['channelname']." ". $_COMPANY->getAppCustomization()['channel']["name-short"] ." > ";
    }
}

$pagetitle = $group->val('groupname')." > ".$subTitle . $form. " ";

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/add_edit_integration.html');

?>
