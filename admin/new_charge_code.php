<?php
require_once __DIR__.'/head.php';
// Authorization Check
if (!$_USER->canManageZoneBudget()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	echo "403 Forbidden (Access Denied)";
	exit();
}

$pagetitle = "Charge Code";
//Featch Company branches
$edit = null;
$id = 0;
if(isset($_GET['edit'])){
    $id = $_COMPANY->decodeId($_GET['edit']);
    $edit=$db->get("SELECT * FROM budget_charge_codes WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND (`charge_code_id`='{$id}' )");
}
if (isset($_POST['submit'])){
	$check = $db->checkRequired(array('Charge Code'=>$_POST['charge_code']));
	if($check){
		$msg = "Error: Not a valid input on $check.";
		Http::Redirect("new_charge_code?msg=$msg");
	}

    $charge_code 	=	trim($_POST['charge_code']);

	$retVal = Budget2::AddOrUpdateChargeCodes($id,$charge_code);

	if (($id && $retVal) || (!$id && $retVal > 1))
		$_SESSION['updated'] = time();
	elseif (!$id && $retVal)
		$_SESSION['added'] = time();
	else
		$_SESSION['error'] = time();

	Http::Redirect("budget_charge_codes");
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/new_charge_code.html');
?>
