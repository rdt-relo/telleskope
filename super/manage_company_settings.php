<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManageCompanySettings);

$companyid = $_SESSION['companyid'];
$_COMPANY = Company::GetCompany($companyid, true);

$default_settings = Arr::Dot(Company::DEFAULT_COMPANY_SETTINGS);
$config = (json_decode($_COMPANY->val('customization') ?? '', true)) ?: [];
$config = Arr::Unminify($config, $default_settings);
$zone_customizations = Arr::Dot($config);

if (isset($_POST['submit'])) {
    unset($_POST['submit']);

    $unflattenedCustomization = [];

    foreach ($_POST as $key => $value) {
        $key = str_replace('%DOT%', '.', $key);
        if($value == 'true' || $value == 'false'){
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        $unflattenedCustomization[$key] = $value;
    }

    $expandedCustomization = Arr::Undot($unflattenedCustomization);

    if (is_array($expandedCustomization)) {
        $_COMPANY->updateCompanySettings($expandedCustomization);
        $_SESSION['updated'] = time();
    } else {
        $_SESSION['error'] = time();
    }

    Http::Redirect('/1/super/manage_company_settings');
}

$pagetitle = 'Update company settings';

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/updateCompanyOrZoneSetting.html');
include(__DIR__ . '/views/footer.html');
