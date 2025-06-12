<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::GlobalManageReleaseNotes);

$db	= new Hems();
unset($_SESSION['companyid']);

$pageTitle = "New Release Note";
$id  = 0;
//Edit
if (isset($_GET['edit'])){
    $pageTitle = "Update Release Note";
	$id = (int)base64_decode($_GET['edit']);
	$edit = $_SUPER_ADMIN->super_get("SELECT `releaseid`, `releasename`, `notes`, `isactive`, `createdon`, `modifiedon` FROM `release_notes` WHERE `releaseid`='".$id."'");
}
//Submit section
if (isset($_POST['submit'])){
	$releasename	= raw2clean($_POST['releasename']);
	$app_type		= 'affinities'; //raw2clean($_POST['app_type']); Commented out on 08/14/2022 as rel notes will be common for all apps
	$notes		    = addslashes(cleanMarkup($_POST['notes']));
	// Add '&nbsp' to all empty <p> tags. This will show empty <p> tags as newlines
    $notes = preg_replace('#<p></p>#','<p>&nbsp;</p>', $notes);
	
	if ($id=="0"){
		$insert = $_SUPER_ADMIN->super_insert("INSERT INTO `release_notes`(`releasename`, `app_type`, `notes`, `isactive`, `createdon`, `modifiedon`) VALUES ('".$releasename."','".$app_type."','".$notes."','2',NOW(),NOW())");
		$_SESSION['added'] = time();
		header("Location:manage_release_notes");
		
	}else{
		$update = $_SUPER_ADMIN->super_update("UPDATE `release_notes` SET `releasename`='".$releasename."',`app_type`='".$app_type."',`notes`='".$notes."',`modifiedon`=NOW() WHERE `releaseid`='".$id."' ");
		$_SESSION['updated'] = time();
		header("Location:manage_release_notes");
	}
}

include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/new_release_note.html');
include(__DIR__ . '/views/footer.html');
?>
