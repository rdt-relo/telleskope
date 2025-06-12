<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::GlobalSystemMessaging);

$db	= new Hems();
unset($_SESSION['companyid']);

$id = 0;
$recipient_type = array();
$edit = null;
$pageTitle = "New System Message";
if (isset($_GET['edit'])){
    $id = base64_decode($_GET['edit']);
    $edit = $_SUPER_ADMIN->super_get("SELECT * FROM `system_messages` WHERE `message_id`='".$id."' ");
    $recipient_type = explode(',',$edit[0]['recipient_type']);
    $pageTitle = "Update System Message";
}
include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/new_system_message.html');
include(__DIR__ . '/views/footer.html');
?>
