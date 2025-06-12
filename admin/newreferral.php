<?php
//require_once __DIR__.'/head.php';
//$pagetitle = "New Business Leads";
//
//$groups = $db->get("SELECT * FROM `groups` WHERE `companyid`='".$companyid."' and isactive='1'");
//
//if (isset($_GET['id'])){ // If Edit:
//	$id = $_COMPANY->decodeId($_GET['id']);
//
//	$edit = $db->get("SELECT `referral_id`, `companyid`, `groupid`, `referral_by`, `firstname`, `lastname`, `email`, `phone`, `compnayname`, `comment`, `added_at`, `updated_at`, `isactive` FROM `referral` WHERE `referral_id`='".$id."'");
//
//
//}else{
//	$id = 0;
//}
//
//if (isset($_POST['submit'])){
//
//	if ($id == 0 ){
//		$insert = $db->insert("INSERT INTO `referral`(`companyid`, `groupid`, `referral_by`, `firstname`, `lastname`, `email`, `phone`, `compnayname`, `comment`, `added_at`, `updated_at`, `isactive`) VALUES ('".$companyid."','".$_POST['groupid']."','".$userid."','".raw2clean($_POST['firstname'])."','".raw2clean($_POST['lastname'])."','".raw2clean($_POST['email'])."','".raw2clean($_POST['phone'])."','".raw2clean($_POST['compnayname'])."','".raw2clean($_POST['comment'])."',now(),now(),'1')");
//
//		$_SESSION['ADDED'] = time();
//	}else{
//		$update = $db->update("UPDATE `referral` SET `groupid`='".$_POST['groupid']."',`firstname`='".raw2clean($_POST['firstname'])."',`lastname`='".raw2clean($_POST['lastname'])."',`email`='".raw2clean($_POST['email'])."',`phone`='".raw2clean($_POST['phone'])."',`compnayname`='".raw2clean($_POST['compnayname'])."',`comment`='".raw2clean($_POST['comment'])."',`updated_at`=now() WHERE referral_id='".$id."'");
//		$_SESSION['updated'] = time();
//	}
//
//	Http::Redirect("referral");
//}
//
//include(__DIR__ . '/views/header.html');
//include(__DIR__ . '/views/newreferral.html');

