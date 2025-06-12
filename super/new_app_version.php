<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::GlobalManageAppVersions);

$db	= new Hems();
$edit  = null;
$id = 0;
//Edit
if (isset($_GET['edit'])){
	$id = (int)base64_decode($_GET['edit']);
    $edit = $_SUPER_ADMIN->super_get("SELECT * FROM `app_versions` WHERE `id`='".$id."'");
   //echo '<pre>'; print_r($edit); exit;
}
//Submit section
if (isset($_POST['submit'])){
	$valid_platforms = array('ios','android','any');
	$platform	 = in_array($_POST['platform'], $valid_platforms) ? $_POST['platform'] : 'any';
	$subdomain	 = isset($_POST['subdomain']) ? raw2clean($_POST['subdomain']) : '';
	$app_version = raw2clean($_POST['app_version']);
	$bundle_id	 = raw2clean($_POST['bundle_id']);
	
	if ($id){
        $update = $_SUPER_ADMIN->super_update("UPDATE `app_versions` SET `app_version`='".$app_version."',`platform`='".$platform."',bundle_id='".$bundle_id."' WHERE id='".$id."'");
		$_SESSION['updated'] = time();
		header("Location:app_versions");
		
	}else{
        $insert = $_SUPER_ADMIN->super_insert("INSERT INTO `app_versions`(`platform`, `app_version`, `bundle_id`, isactive, subdomain, `start_date`) VALUES ('{$platform}','{$app_version}','{$bundle_id}','2','{$subdomain}',NOW())");
		$_SESSION['added'] = time();
		header("Location:app_versions");
		
	}
}

include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/new_app_version.html');
include(__DIR__ . '/views/footer.html');
?>
