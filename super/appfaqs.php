<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::GlobalManageAppFaqs);

$db	= new Hems();
unset($_SESSION['companyid']);

if (!empty($_GET['page'])){
	$page =	(int)$_GET['page'];
}else{
	$page =	1;	
}
$search = "";

$rows = $_SUPER_ADMIN->super_get("SELECT `faqid`, `question`, `answer` FROM `faqsmobile` WHERE `isactive`='1' order by faqid desc");

//Edit
if (isset($_GET['edit'])){
	$faqid = (int)base64_decode($_GET['edit']);
	$edit = $_SUPER_ADMIN->super_get("SELECT `faqid`, `question`, `answer` FROM `faqsmobile` WHERE `faqid`='".$faqid."'");
}
//Submit section
if (isset($_POST['submit'])){
	$question	= raw2clean($_POST['question']);
	$answer		= raw2clean($_POST['answer']);
	$faqid		= base64_decode($_POST['faqid']);
	
	if ($faqid==""){
		$insert = $_SUPER_ADMIN->super_insert("INSERT INTO `faqsmobile`(`question`, `answer`, `createdon`, `modified`, `isactive`) VALUES ('".$question."','".$answer."',now(),now(),'1')");
		$_SESSION['added'] = time();
		header("Location:appfaqs");
	}else{
		$update = $_SUPER_ADMIN->super_update("UPDATE `faqsmobile` SET `question`='".$question."',`answer`='".$answer."',`modified`=now() WHERE faqid='".$faqid."'");
		$_SESSION['updated'] = time();
		header("Location:appfaqs");
	}
}

include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/appfaqs.html');
include(__DIR__ . '/views/footer.html');
?>
