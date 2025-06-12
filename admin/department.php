<?php
require_once __DIR__.'/head.php';

$pagetitle = "Company Department";
//Featch Company branches
$edit = null;
$id = 0;
if(isset($_GET['edit'])){
    $id = $_COMPANY->decodeId($_GET['edit']);
    $edit=$db->get("SELECT `departmentid`, `companyid`, `department`, `addedby`, `date`, `isactive` FROM `departments` WHERE `companyid`='{$_COMPANY->id()}' AND (`departmentid`='{$id}' )");
}
if (isset($_POST['submit'])){

	$check = $db->checkRequired(array('Department Name'=>$_POST['department']));	
    if($check){
        $msg = "Error: Not a valid input on $check.";
		Http::Redirect("department?msg=$msg");
    }
	
	$_COMPANY->createOrUpdateDepartment($id,$_POST['department']);
	$_SESSION['updated']= time();
	Http::Redirect("manage_departments");
}

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/department.html');
include(__DIR__ . '/views/footer.html');
?>
