<?php
require_once __DIR__.'/head.php';
/* @var Company $_COMPANY */

if(!empty($_GET['page'])){
	$page = $_GET['page'];
}else{
	$page = 1;
}
$pagetitle = "Company Branches";

// Authorization Check
if (!$_USER->canManageCompanySettings()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}

//edit data
if (isset($_GET['edit'])){
	//Data Validation
	$branchid = $_COMPANY->decodeId($_GET['edit']);
	if ($branchid ===0) {
		header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
		exit();
	}
	$branch = $_COMPANY->getBranch($branchid);

} elseif (isset($_POST['submit'])) {
	$check = $db->checkRequired(array('Branch Name'=>$_POST['branchname']));
    if($check){
        $msg = "Error: Not a valid input on $check.";
		Http::Redirect("office_locations?msg=$msg");
    }

	$branchname 	=	trim($_POST['branchname']);
	$street 		=	trim($_POST['street']);
	$city		 	=	trim($_POST['city']);
	$state		 	=	trim($_POST['state']);
	$zipcode	 	=	trim($_POST['zipcode']);
	$country	 	=	trim($_POST['country']);
	$branchtype	 	=	trim($_POST['branchtype']);
	$regionid		=  	$_COMPANY->decodeId($_POST['regionid']);

	if(empty($_POST['edit'])){
		$lookup_branch = $_COMPANY->getBranchByName2($branchname, $city, $state, $country);
		if ($lookup_branch) {
			$_SESSION['error_message'] = 'Branch with same name exists, choose a unique Branch Name ';
			$_SESSION['error']= time();
		} else {
			$query = $_COMPANY->createBranch($branchname, $street, $city, $state, $zipcode, $country, $branchtype, $regionid);
			$_SESSION['added']= time();
			Http::Redirect("office_locations");
		}
	}else{
		$branchid = $_COMPANY->decodeId($_POST['edit']);
		if ($branchid ===0) {
			header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
			exit();
		}
		$query = $_COMPANY->updateBranch($branchid,$branchname,$street,$city,$state,$zipcode,$country,$branchtype,$regionid);
		$_SESSION['updated']= time();
		Http::Redirect("office_locations");
	}
}

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/companybranches.html');
include(__DIR__ . '/views/footer.html');
?>
