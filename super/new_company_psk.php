<?php
require_once __DIR__.'/head.php';

$_COMPANY = Company::GetCompany($_SESSION['companyid']);
$pageTitle = "New Pre Shared Key";
$key_record = null; // Initialize the $key_record variable
$company_pskey = null; // Initialize the $company_pskey variable
$re_encrypted_key = null; // Initialize the $re_encrypted_key variable

// Edit existing purpose
if (isset($_GET['pskeyid'])) {
    $company_pskey = CompanyPSKey::GetCompanyPSKey($_GET['pskeyid']);
    // $company_pskey will have the CompanyPSKey object now.
    // Retrieve the purpose from the CompanyPSKey object
    $purpose = $company_pskey->val('purpose');
}

// Add pskey or Update Purpose
if (isset($_POST['submit'])) {
    $key_purpose = raw2clean($_POST['purpose']);
    
    if (isset($_GET['pskeyid'])) {
        // Update the purpose of the existing CompanyPSKey
        $company_pskey = CompanyPSKey::GetCompanyPSKey($_GET['pskeyid']);
        
        if ($company_pskey) {
            $company_pskey->UpdatePurpose($key_purpose);
        }
        
        // Redirect to manage_psks.php after updating the purpose
        header("Location: manage_psks.php");
        exit();
    } else {
        // Create a new CompanyPSKey
        $key_record = CompanyPSKey::CreateCompanyPSKey($key_purpose);
    }
}

//$_COMPANY = null;
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/new_company_psk.html');
include(__DIR__ . '/views/footer.html');
?>
