<?php
require_once __DIR__.'/head.php';

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}
$error = "";
$leadid = 0;
$edit = array();
//Data Validation
if (!isset($_GET['gid']) || ($groupid = $_COMPANY->decodeId($_GET['gid']))<1
	|| ($group = Group::GetGroup($groupid)) == null
	|| (isset($_GET['lid']) && ($leadid = $_COMPANY->decodeId($_GET['lid']))<1)){
	header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
	exit();
}
$encGroupId = $_COMPANY->encodeId($groupid);
$function_title = (isset($_GET['lid'])) ? 'Edit Leader' : 'Add Leader';
$pagetitle = $group->val('groupname')." > {$function_title}";

$group_regions = $group->val('regionid') ?: '0';
$region = $db->get("SELECT `regionid`, `region` FROM `regions` WHERE `regionid` IN (".$group_regions.") AND companyid={$companyid} AND `isactive`='1'");
$users = null;
$checkLeadType = null;
$grouplead_type = $db->get("SELECT * FROM `grouplead_type` WHERE `companyid`='{$companyid}' AND (`zoneid`='{$_ZONE->id()}' AND `isactive`=1 AND sys_leadtype<4)");
usort($grouplead_type, function($a, $b) {
	return $a['type'] <=> $b['type'];
});

// Edit existing group lead
if($leadid>0){
	$edit=$db->get("SELECT * FROM `groupleads` WHERE `leadid`='{$leadid}'");
	if (count($edit)){
		$users = $db->get("SELECT `userid`,`firstname`,`lastname` FROM `users`  WHERE `userid`='{$edit[0]['userid']}'");
		$checkLeadType = $db->get("SELECT `sys_leadtype` FROM `grouplead_type` WHERE`typeid`='".$edit[0]['grouplead_typeid']."'")[0]['sys_leadtype'];

	}
}


if (isset($_POST['submit'])){
	// Add or update group lead

	if (!isset($_POST['typeid']) || ($typeid = $_COMPANY->decodeId($_POST['typeid']))<1) {
		$_SESSION['error'] = time();
		exit();
	}
	$roletitle = $_POST['roletitle'];
	$checkLeadType = $db->get("SELECT `typeid`, `sys_leadtype`, `companyid`, `type`, `allow_publish_content`, `allow_create_content`, `allow_manage`, `modifiedon`, `isactive` FROM `grouplead_type` WHERE`typeid`='".$typeid."' AND `companyid`='{$companyid}'");

    $regionids = "0";
	if ($checkLeadType[0]['sys_leadtype'] == 3 ){
		if(isset($_POST['regionids']) && !empty(array_filter($_POST['regionids'])) ){
			$dec_rid_array = array();
			foreach ($_POST['regionids'] as $enc_rid) {
				$dec_rid = $_COMPANY->decodeId($enc_rid);
				if ($dec_rid < 1) {
					$_SESSION['error'] = time();
					exit();
				}
				$dec_rid_array[] = $dec_rid;
			}
			$regionids = implode(',',$dec_rid_array);
		}else{
			$error = "Please select at least one region for regional leader";
		}
	}

	if (!$error){
        if ($leadid > 0) {
            Group::UpdateGroupLead($groupid, $leadid, $typeid, $regionids, $roletitle);
            $_SESSION['updated'] = time();
        } else {
            if (!isset($_POST['userid']) || ($newleadid = $_COMPANY->decodeId($_POST['userid']))<1) {
                $_SESSION['error'] = time();
                exit();
            }
            Group::AddGroupLead($groupid, $newleadid, $typeid, $regionids, $roletitle);
            $group->addOrUpdateGroupMemberByAssignment($newleadid,0,0);

            # Inform lead by email
            $group->sendGroupLeadAssignmentEmail($groupid, 1, $typeid, $newleadid);
            $_SESSION['added'] = time();
        }

		Http::Redirect("manageleads?gid={$encGroupId}");
	}
}


include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/newleads.html');


