<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::GlobalManageReleaseNotes);

$db	= new Hems();
unset($_SESSION['companyid']);


$select = "SELECT * FROM `release_notes` WHERE `isactive`>0 ORDER BY `releaseid` DESC";
$rows=$_SUPER_ADMIN->super_get($select);

include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/manage_release_notes.html');
include(__DIR__ . '/views/footer.html');
?>
