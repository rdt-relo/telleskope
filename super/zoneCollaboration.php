<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManageZones);

$db	= new Hems();
$id  = 0;
if (isset($_GET['id']) && ($id = base64_decode($_GET['id'])) < 0){
	$_SESSION['error'] = time();
    header("location:manage_zones");

}
$currentzone = $_SUPER_ADMIN->super_get("SELECT * FROM `company_zones` WHERE `companyid`='{$_SESSION['companyid']}' AND `zoneid`='{$id}' ");
$allzones = $_SUPER_ADMIN->super_get("SELECT * FROM `company_zones` WHERE `companyid`='{$_SESSION['companyid']}' AND zoneid!='{$id}' AND isactive='1'");


//Submit section
if (isset($_POST['submit'])){

	$zone_collaboration = Sanitizer::SanitizeIntegerArray($_POST['zone_collaboration'] ?? []);
	$zone_calendar_sharing = Sanitizer::SanitizeIntegerArray($_POST['zone_calendar_sharing'] ?? []);

	$zone_collaboration_ids_csv = implode(',',$zone_collaboration);
	$zone_calendar_sharing_ids_csv = implode(',',$zone_calendar_sharing);

	$_SUPER_ADMIN->super_update("UPDATE `company_zones` SET `collaborating_zoneids`='{$zone_collaboration_ids_csv}', `calendar_sharing_zoneids`='{$zone_calendar_sharing_ids_csv}', `modifiedon`=NOW() WHERE `companyid`='{$_SESSION['companyid']}' AND zoneid='{$id}' ");
	$_SESSION['updated'] = time();
	header("Location:manage_zones");

}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/zoneCollaboration.html');
include(__DIR__ . '/views/footer.html');
?>
