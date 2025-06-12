<?php

require_once __DIR__ . '/head.php';

$db = new Hems();
$companies = $_SUPER_ADMIN->super_get('SELECT `companyid` FROM `companies` WHERE `isactive` = 1 AND `status` = 1');
$company_ids = array_column($companies, 'companyid');

$_COMPANY = null;
foreach ($company_ids as $company_id) {
    $_COMPANY = Company::GetCompany($company_id);
    CompanyEncKey::CreateNewCompanyEncKey();
    echo 'Log rotated for company subdomain - ' . $_COMPANY->val('subdomain') . "\n";
    $_COMPANY = null;
}
