<?php
require_once __DIR__.'/head.php';
global $_COMPANY; /* @var Company $_COMPANY */
global $_USER; /* @var User $_USER */

if (!$_USER->isCompanyAdmin()) {
    header(HTTP_FORBIDDEN);
    echo "403 Forbidden (Access Denied)";
    exit();
}

$pagetitle = "Update Security Settings";
$type = 1;
$security = null;
$whitelisted_ips = null;
if (isset($_GET['type']) && in_array($_GET['type'], ['admin','affinity', 'admin_external_roles'])){
    $type = $_GET['type'];
    $security =  $_COMPANY->getCompanySecurity();
    $mobile_session_logout_time_hr = 0;
    $mobile_session_logout_time_min = 0;
    
    if ($security){
        $whitelisted_ips = explode(',',$security['admin_whitelist_ip']);
        if ($security['mobile_session_logout_time_in_min_utc']){
            $mobile_session_logout_time_hr = (int)(((int)$security['mobile_session_logout_time_in_min_utc']) / 60);
            $mobile_session_logout_time_min = ((int)$security['mobile_session_logout_time_in_min_utc']) % 60;
        }
    }
} else {
    $_SESSION['error']= time();
    $_SESSION['form_error'] = "Page not found. Please check again.";
	Http::Redirect("security");
}

if (isset($_POST['submit'])){
    $mobile_session_logout_time_in_min_utc = 0;
    if ($type=='admin') {
        $admin_inactivity_max   =    max((int)($_POST['admin_inactivity_max']),15);
        $admin_session_max      =    max((int)($_POST['admin_session_max']),60);

        $admin_whitelist_ip_aray = $_POST['admin_whitelist_ip'] ?? array();
        if (count($admin_whitelist_ip_aray) > 9) {
            $_SESSION['error']= time();
            $_SESSION['form_error'] = "Too many IPs added to allow list. Only 9 are allowed";
            Http::Redirect("security");
        }
        $admin_whitelist_ip = implode(',', $admin_whitelist_ip_aray);

        $apps_inactivity_max    =   $security['apps_inactivity_max'];
        $apps_session_max       =   $security['apps_session_max'];
        $mobile_session_max     =   $security['mobile_session_max'];
    } elseif ($type === 'affinity') {
        $apps_inactivity_max   =    max((int)($_POST['apps_inactivity_max']),15);
        $apps_session_max      =    max((int)($_POST['apps_session_max']),60);
        $mobile_session_max     =   max((int)($_POST['mobile_session_max']),1440);
        $admin_inactivity_max   =   $security['admin_inactivity_max'];
        $admin_session_max      =   $security['admin_session_max'];
        $admin_whitelist_ip     =   $security['admin_whitelist_ip'];
        $mobile_session_logout_time_hr = min((int)($_POST['mobile_session_logout_time_hr']),23);
        $mobile_session_logout_time_min = min((int)($_POST['mobile_session_logout_time_min']),59);
        $mobile_session_logout_time_in_min_utc = $mobile_session_logout_time_hr * 60 + $mobile_session_logout_time_min;

    } elseif ($type === 'admin_external_roles') {
        $admin_inactivity_max = $security['admin_inactivity_max'];
        $admin_session_max = $security['admin_session_max'];
        $admin_whitelist_ip = $security['admin_whitelist_ip'];
        $apps_inactivity_max = $security['apps_inactivity_max'];
        $apps_session_max = $security['apps_session_max'];
        $mobile_session_max = $security['mobile_session_max'];
        $mobile_session_logout_time_in_min_utc = $security['mobile_session_logout_time_in_min_utc'];

        $security['company_admin_external_roles'] = implode(',', array_filter(array_map('trim', explode(',', $_POST['company_admin_external_roles'] ?? ''))));
        $security['zone_admin_external_roles'] = implode(',', array_filter(array_map('trim', explode(',', $_POST['zone_admin_external_roles'] ?? ''))));
        $security['group_lead_external_roles'] = implode(',', array_filter(array_map('trim', explode(',', $_POST['group_lead_external_roles'] ?? ''))));
        $security['chapter_lead_external_roles'] = implode(',', array_filter(array_map('trim', explode(',', $_POST['chapter_lead_external_roles'] ?? ''))));
        $security['channel_lead_external_roles'] = implode(',', array_filter(array_map('trim', explode(',', $_POST['channel_lead_external_roles'] ?? ''))));
    }

    $_COMPANY->addOrUpdateCompanySecuritySetting($admin_inactivity_max,$admin_session_max,$admin_whitelist_ip,$apps_inactivity_max,$apps_session_max,$mobile_session_max,$mobile_session_logout_time_in_min_utc,
        $security['company_admin_external_roles'],
        $security['zone_admin_external_roles'],
        $security['group_lead_external_roles'],
        $security['chapter_lead_external_roles'],
        $security['channel_lead_external_roles']
    );
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);

    Http::Redirect("security");
}

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/update_security_setting.html');
include(__DIR__ . '/views/footer.html');
?>
