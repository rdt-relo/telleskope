<?php
require_once __DIR__.'/head.php';
if (!$_USER->isCompanyAdmin()) {
	header(HTTP_FORBIDDEN);
	echo "403 Forbidden (Access Denied)";
	exit();
}

$pagetitle = "Manage Global Admin";
$section = "company";
$userid=$_SESSION['adminid'];

if(isset($_GET['msg'])){
	$msg = getmsg($_GET['msg']);
}else{
	$msg = "";
}

//Fetch all employee
$rows = User::GetAllCompanyAdmins();

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/manageadmin.html');
include(__DIR__ . '/views/footer.html');
?>
