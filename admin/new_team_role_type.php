<?php
require_once __DIR__.'/head.php';

exit();// Deprecated code moved to Affinity

$pagetitle = "New {$_COMPANY->getAppCustomization()['teams']['name']} Role Type";
$edit = null;
$error = null;
$groupid = 0;
$editRestrictions = array();
$roleid = 0;
if (!isset($_GET['groupid']) || ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1  || ($group = Group::GetGroup($groupid)) === null ) {
	header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
	exit();
}

$catalog_categories = array_values(UserCatalog::GetAllCatalogCategories());

if (isset($_GET['id'])){
    $roleid = $_COMPANY->decodeId($_GET['id']);
    $edit = Team::GetTeamRoleType($roleid);
    $editRestrictions = json_decode($edit['restrictions'],true);
    $pagetitle = "Update {$_COMPANY->getAppCustomization()['teams']['name']} Role Type";
}
//Featch Company branches
if (isset($_POST['submit'])){
	$type  =  	Sanitizer::SanitizeRoleName($_POST['type']);

    if($type === $_POST['type']){
        $sys_team_role_type = $_COMPANY->decodeId($_POST['sys_team_role_type']);
        $welcome_message = $_POST['welcome_message'];
        // Add '&nbsp' to all empty <p> tags. This will show empty <p> tags as newlines
        $welcome_message = preg_replace('#<p></p>#','<p>&nbsp;</p>', $welcome_message);
        $welcome_email_subject = $_POST['welcome_email_subject'] ?: "Your {$group->val('groupname_short')} {$_COMPANY->getAppCustomization()['teams']['name']} is now Active";
        $min_required = intval($_POST['min_required'] ?? 0);
        $max_allowed = intval($_POST['max_allowed'] ?? 1);
        $joinrequest_email_subject = $_POST['joinrequest_email_subject'];
        $joinrequest_message = $_POST['joinrequest_message'];
        // Add '&nbsp' to all empty <p> tags. This will show empty <p> tags as newlines
        $joinrequest_message = preg_replace('#<p></p>#','<p>&nbsp;</p>', $joinrequest_message);
        $restrictions = array();
        foreach($catalog_categories as $category){
            $categoryPoststr = str_replace(' ','_',$category); // Convert _ to space.
            if (isset($_POST[$categoryPoststr])){
                $logicType = (int) $_POST[$categoryPoststr];
                $keys = array();
                if (isset($_POST[$categoryPoststr.'_val'])){
                    $keys =  (array) $_POST[$categoryPoststr.'_val'];
                }
                $restrictions[$category]  = array('type'=>$logicType,'keys'=>$keys); // Do not use categoryname_str here
            }
        }
        $restrictions = json_encode($restrictions);
        $registration_start_date = "";
        $registration_end_date = "";
        $role_capacity =  (int)$_POST['role_capacity'];

        if (!empty($_POST['registration_start_date'])) {
            $registration_start_date = date_format(date_create($_POST['registration_start_date']),"Y-m-d");
        }
        if (!empty($_POST['registration_end_date'])) {
            $registration_end_date = date_format(date_create($_POST['registration_end_date']),"Y-m-d");
        }

        $hide_on_request_to_join = 0;
        if (isset($_POST['hide_on_request_to_join'])){
            $hide_on_request_to_join = $_POST['hide_on_request_to_join'];
        }

        $allow_matches_within_role = 0;
        if (isset($_POST['allow_matches_within_role'])){
            $allow_matches_within_role = 1;
        }

        if ($min_required > $max_allowed){
            $error = "Minimum required value can not greater than Max allowed value!";
        } else {
            $role = Team::GetTeamRoleByName($type,$groupid,false);
            if ($role && $role['roleid'] != $roleid){
                $error = "Role name must be unique!";
            } else{ 
                if (Team::AddOrUpdateTeamRole($roleid,$groupid,$type,$sys_team_role_type,$min_required,$max_allowed,$welcome_message,$restrictions,$welcome_email_subject,$registration_start_date,$registration_end_date,$role_capacity,$hide_on_request_to_join,$joinrequest_email_subject,$joinrequest_message,$allow_matches_within_role)){
                    $_SESSION['updated']= time();
                    Http::Redirect("manage_team_contents?groupid=".$_COMPANY->encodeId($groupid)."&s=1");
                } else {
                    $error = "Something went wrong. Please try again.";
                }
            }
        }
    }else{
        $error = "Invalid Team Role Type: only alpha numeric characters are allowed.";
    }
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/new_team_role_type.html');

