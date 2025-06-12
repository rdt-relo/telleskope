<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManageZones);

$id = 0;
$zone_row = null;
$companyid = $_SESSION['companyid'];
$zone_customizations = [];
$default_settings = [];
$default_company_settings = [];

if (isset($_GET['id'])) {
    $id = base64_decode($_GET['id']);
    $zone_row = $_SUPER_ADMIN->super_get("SELECT * FROM `company_zones` WHERE `companyid`='{$companyid}' AND `zoneid`='{$id}' ");
}

if (empty($zone_row) || empty($zone_row[0])){
    $_SESSION['error'] = time();
    header("location:manage_zones");
}

$_COMPANY = Company::GetCompany($companyid);
$zone = $_COMPANY->getZone($id) ?? Zone::GetZone($id);
$zone_customizations = Arr::Dot($zone->getZoneCustomization());
$default_settings = Arr::Dot(Zone::GetZoneSettingsTemplate($zone->val('app_type')));
$default_company_settings = Arr::Dot(Company::DEFAULT_COMPANY_SETTINGS);


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
        $_COMPANY = Company::GetCompany($companyid);
        
        if ($zone->val('modifiedon') == $zone_row[0]['modifiedon']) {
            $retVal = $zone->updateZoneCustomization($expandedCustomization);
            $zone = null;
            $_COMPANY = null;
            $_SESSION['updated'] = time();
        } else {
            Company::GetCompany($companyid, true);
            sleep(5); // Wait for company to reload
            $_SESSION['error'] = time();
        }
        
        header("location: manage_zones");
    } else {
        $_SESSION['error'] = time();
    }
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/updateCompanyOrZoneSetting.html');
include(__DIR__ . '/views/footer.html');

