<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManageEaiAccounts);

$db	= new Hems();
$cid = (int)$_SESSION['companyid'];

$_COMPANY = Company::GetCompany($cid);

$rows=$_SUPER_ADMIN->super_get("SELECT * FROM `eai_accounts` WHERE companyid={$cid}");
$status = array("0"=>"In Active", "1"=>"Active",'2'=>"Draft", "100"=>"Deleted");

$rows = array_map(function ($row) {
    return EaiAccount::Hydrate($row['accountid'],$row)->toArray();
}, $rows);

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manage_eai_credentials.html');
include(__DIR__ . '/views/footer.html');
?>
