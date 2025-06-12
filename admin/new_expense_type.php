<?php
require_once __DIR__.'/head.php';
// Authorization Check
if (!$_USER->canManageZoneBudget()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	echo "403 Forbidden (Access Denied)";
	exit();
}

$pagetitle = "Expense Type";
//Featch Company branches
$edit = null;
$id = 0;
if(isset($_GET['edit'])){
    $id = $_COMPANY->decodeId($_GET['edit']);
    $edit=$db->get("SELECT * FROM budget_expense_types WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND (`expensetypeid`='{$id}' )");
}
if (isset($_POST['submit'])){

	$check = $db->checkRequired(array('Expense Type'=>$_POST['expensetype']));	
    if($check){
        $msg = "Error: Not a valid input on $check.";
		Http::Redirect("new_expense_type?msg=$msg");
    }
	$expensetype = trim($_POST['expensetype']);
	$retVal = Budget2::AddOrUpdateExpenseType($id, $expensetype);

	if (($id && $retVal) || (!$id && $retVal > 1))
		$_SESSION['updated'] = time();
	elseif (!$id && $retVal)
		$_SESSION['added'] = time();
	else
		$_SESSION['error'] = time();

	Http::Redirect("manage_expense_types");
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/new_expense_type.html');
?>
