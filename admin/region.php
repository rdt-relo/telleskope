<?php
require_once __DIR__.'/head.php';
if (!$_USER->isCompanyAdmin()) {
	header(HTTP_FORBIDDEN);
	echo "403 Forbidden (Access Denied)";
	exit();
}

$pagetitle = "Company Region";
$edit = null;
$id = 0;
if(isset($_GET['edit'])){
    $id = $_COMPANY->decodeId($_GET['edit']);
    $edit=$db->get("SELECT `regionid`, `companyid`, `region`, `userid`, `date`, `isactive` FROM `regions` WHERE `companyid`='{$_COMPANY->id()}' AND ( `regionid`='{$id}' )");
}
if (isset($_POST['submit'])){
	
	$check = $db->checkRequired(array('Region Name'=>$_POST['region']));	
	if($check){
        $msg = "Error: Not a valid input on $check.";
		Http::Redirect("region?msg=$msg");
    }
	$region = $_POST['region'];
	$_COMPANY->createOrUpdateRegion($id,$region);

	$_SESSION['updated']= time();
	Http::Redirect("manage_regions");
}

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/region.html');
include(__DIR__ . '/views/footer.html');
?>
