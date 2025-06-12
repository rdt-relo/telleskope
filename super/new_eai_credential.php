<?php
require_once __DIR__.'/head.php';
require_once __DIR__ . '/../eai/auth/EaiPermission.php';

Auth::CheckPermission(Permission::ManageEaiAccounts);

$db	= new Hems();
$pageTitle = "New EAI Credential";
$successCode = 0;
if (isset($_POST['submit'])){

    $module = raw2clean($_POST['module']);
    $password = $_POST['password'];
    $attributes = Arr::Json2Array($_POST['attributes'], true); // Add backslashes for JSON data coming from browser text window
    $passwordhash = password_hash($password, PASSWORD_BCRYPT);
    $username_prefix = Str::Random(6);
//    $permissions = json_encode($_POST['permissions']);

    $id = $_SUPER_ADMIN->super_insert("INSERT INTO `eai_accounts`(`companyid`, `module`, `passwordhash`, `isactive`, `username_prefix`) VALUES ('".$_SESSION['companyid']."','".$module."','".$passwordhash."',2, '{$username_prefix}')");

    if($id){
        $successCode = 1;
    } else {
        $successCode = 2;
    }

}
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/new_eai_credential.html');
include(__DIR__ . '/views/footer.html');
?>
