<?php
require_once __DIR__.'/head.php';
require_once __DIR__ . '/../eai/auth/EaiPermission.php';

Auth::CheckPermission(Permission::ManageEaiAccounts);

$cid = (int) $_SESSION['companyid'];
$_COMPANY = Company::GetCompany($cid);

$eai_account_id = $_COMPANY->decodeId($_GET['eai_account_id']);

$pageTitle = "Edit EAI Credential";

$eai_account = EaiAccount::GetEaiAccount($eai_account_id);
$zones = $_COMPANY->getZones();

if (is_null($eai_account)) {
    http_response_code(404);
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require __DIR__ . '/views/header.html';
    require __DIR__ . '/views/edit_eai_credential.html.php';
    include(__DIR__ . '/views/footer.html');
    exit();
}

$updated_authorization_info = [
    'permissions' => $_POST['permissions'] ?? [],
    'zone_ids' => array_map(function (string $zone_id) {
        global $_COMPANY;
        return $_COMPANY->decodeId($zone_id);
    }, $_POST['zone_ids'] ?? []),
    'eai_whitelisted_ips' => $_POST['eai_whitelisted_ips'] ?? [],
];

$_SUPER_ADMIN->super_update_ps(
    'UPDATE `eai_accounts` SET `authorization_info` = ? WHERE `companyid` = ? AND `accountid` = ?',
    'xii',
    json_encode($updated_authorization_info),
    $_COMPANY->id(),
    $eai_account_id
);

$_AUDIT_META = [
    'object_type' => 'eai_credential',
    'operation' => 'update',
    'operation_details' => [
        'eai_account_id' => $eai_account_id,
        'old' => [
            'permissions' => $eai_account->getPermissions(),
            'zone_ids' => $eai_account->getZoneIds(),
            'eai_whitelisted_ips' => $eai_account->getEaiWhitelistedIps(),
        ],
        'new' => $updated_authorization_info,
    ],
];

Http::Redirect('/1/super/manage_eai_credentials');
