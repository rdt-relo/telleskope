<?php
session_start();
if ($delegated_access_id = ($_SESSION['delegated_access_id'] ?? null)) {
    require_once __DIR__.'/../include/Company.php';
    $_COMPANY = Company::GetCompany($_SESSION['companyid']);
    TskpGlobals::InitZone();
    $delegatedAccessObj = DelegatedAccess::GetDelegatedAccessById($delegated_access_id);
    $delegatedAccessObj ?-> addAuditLog('USAGE', 'Logout');
}

setcookie(session_name(), '', time() - 3600, '/');
$_SESSION = array();
session_destroy();
Http::Redirect('index?logout=1');
