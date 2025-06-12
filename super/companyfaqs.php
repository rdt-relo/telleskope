<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::GlobalManageAdminFaqs);

$db	= new Hems();
unset($_SESSION['companyid']);

if (!empty($_GET['page'])){
	$page =	(int)$_GET['page'];
}else{
	$page =	1;	
}
$search = "";
//Get all FAQS

$rows = $_SUPER_ADMIN->super_get("SELECT `faqid`, `question`, `answer` FROM `faqsadmin` WHERE `isactive`='1' order by faqid desc");

//Edit
if (isset($_GET['edit'])){
	$faqid = (int)base64_decode($_GET['edit']);
	$edit = $_SUPER_ADMIN->super_get("SELECT `faqid`, `question`, `answer` FROM `faqsadmin` WHERE `faqid`='".$faqid."'");
}
//Submit section
if (isset($_POST['submit'])){
	$question	= raw2clean($_POST['question']);
	$answer		= raw2clean($_POST['answer']);
	$faqid		= base64_decode($_POST['faqid']);
	
	if ($faqid==""){
		$insert = $_SUPER_ADMIN->super_insert("INSERT INTO `faqsadmin`(`question`, `answer`, `createdon`, `modified`, `isactive`) VALUES ('".$question."','".$answer."',now(),now(),'1')");
		$_SESSION['added'] = time();
		header("Location:companyfaqs");
		
	}else{
		$update = $_SUPER_ADMIN->super_update("UPDATE `faqsadmin` SET `question`='".$question."',`answer`='".$answer."',`modified`= now() WHERE faqid='".$faqid."'");
		$_SESSION['updated'] = time();
		header("Location:companyfaqs");
	}
}

include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/companyfaqs.html');
include(__DIR__ . '/views/footer.html');
?>
