<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManagePSKs);

{
    $_COMPANY = Company::GetCompany($_SESSION['companyid']);
    $company_psks = CompanyPSKey::GetAllCompanyPSKeys();
}
// Rotate Key or Delete Key
if (isset($_GET['pskeyid']) && isset($_GET['action'])) {
    
    $pskeyid = $_GET['pskeyid'];
    $company_pskey = CompanyPSKey::GetCompanyPSKey($pskeyid);

    if ($company_pskey) {
        if ($_GET['action'] === 're_encrypt_key') {
            // Rotate the key
            $company_pskey->reEncryptKey();
              
            // Redirect to manage_psks.php after rotating the key
            header("Location: manage_psks.php");
            exit();
        } elseif ($_GET['action'] === 'delete_key') {
            // Soft delete the key
            $company_pskey->delete();
            
            // Redirect to manage_psks.php after deleting the key
            header("Location: manage_psks.php");
            exit();
        }
    }
}
//$_COMPANY = null;
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/manage_psks.html');
include(__DIR__ . '/views/footer.html');

