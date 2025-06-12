<?php
require_once __DIR__.'/head.php';

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}
$groupid    = 0;
$edit = null;
//Data Validation
$tabid = 0;
if (!isset($_GET['gid']) ||
    ($groupid = $_COMPANY->decodeId($_GET['gid']))<1 ||
    (isset($_GET['tid']) && ($tabid = $_COMPANY->decodeId($_GET['tid']))<1)||
    ($group = Group::GetGroup($groupid)) == null) {
    header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
    exit();
}

$encGroupId = $_COMPANY->encodeId($groupid);
if (isset($_POST['submit'])) { // Add or Update  
    
    if ($tabid){
        $tabArray = $group->getGroupCustomTabDetail($tabid);
        $tabstype = $tabArray['tab_type'];       
    }else{
        if (isset($_POST['tabstype']) && in_array($_POST['tabstype'], ['yammer','streams','custom']) && $_POST['tabstype'] !=''){
            $tabstype = $_POST['tabstype'];
        }else{      
            $_SESSION['error'] = time();
            $_SESSION['add-msg'] = "There is something went wrong. Please try again!";
            Http::Redirect("add_edit_group_tabs?gid={$encGroupId}");
        }    
    }
    $tabname  =  	Sanitizer::SanitizeGroupName($_POST['tabname']);
    $tabdescription = 	$_POST['tabdescription'];  
    // Add '&nbsp' to all empty <p> tags. This will show empty <p> tags as newlines
    $tabdescription = preg_replace('#<p></p>#','<p>&nbsp;</p>', $tabdescription);
    $group->addUpdateGroupCustomTabs($tabid, $tabstype, $tabname, $tabdescription);   
    Http::Redirect("tab_type?gid={$encGroupId}");
}
// Else generate data needed to show the form.

$form = "Add New Tab";


if ($tabid) {
   
    $form = "Edit";
    $edit = $group->getGroupCustomTabDetail($tabid);
    if (empty($edit)) {
        header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
        exit();
    }
}

$pagetitle = $group->val('groupname')." > ".$form. " ";
$fontColors = $group ? json_encode(array($group->val('overlaycolor'),$group->val('overlaycolor2'))) : json_encode(array());

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/add_edit_group_tabs.html');

?>
