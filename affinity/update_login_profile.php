<?php
require(__DIR__.'/head.php');
global $_COMPANY;
global $_USER;

$action = '';
$zones = array();
$title = gettext('Update Login Profile');

if (!$_ZONE){
    // Check if global $_ZONE is set? If it is not set then it means we do not know users home zone so lets ask the user

    // Handle user prvovided preference for home zone id, persist it in the database.
    if (isset($_GET['set_home_zoneid']) && ($set_home_zoneid = $_COMPANY->decodeId($_GET['set_home_zoneid'])) > 0) {
        if ($_USER->addUserZone($set_home_zoneid, true, true)) {
            $_USER->clearSessionCache();
            echo json_encode(['nextUrl' => Url::GetZoneAwareUrlBase($set_home_zoneid) . 'update_login_profile']);
            exit();
        } else {
            echo 0;
        }
        exit;
    }

    // else, set the stage to request user to choose a home zone.
    $action = "ZONE";
    $zones = $_COMPANY->getHomeZones($_SESSION['app_type']);
    $title = gettext('Set a Zone');

} elseif (empty($_SESSION['timezone']) || !empty($_SESSION['timezone_ask_user'])) {
    // If timezone is not set then lets ask the user to choose a timezone.
    $action = "TIME";
    $title = gettext('Set Time Zone');

} elseif (!Session::GetInstance()->login_disclaimer_shown && Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['LOGIN_FIRST'])) {

    // If there is a disclaimer avilable for first login then take the flow to complete the disclaimer.
    $action = "LOGIN_FIRST_DISCLAIMER";
    $title = gettext('Provide Disclaimer Consent');

} else{

    // Determine if we want to show the zone selector or not.
    if (
        $_COMPANY->val('zone_selector') &&
        $_ZONE->val('app_type') === 'affinities' &&
        count(explode(',',$_COMPANY->val('zone_selector_zoneids'))) >1
    ) {
        $_SESSION['allow_user_to_choose_zone'] = true;
    } else {
        $_SESSION['allow_user_to_choose_zone'] = false;
    }

   // Session::GetInstance()->login_disclaimer_shown = 0; // Reset

    // Next set mylocation_url for officeraven
    if ($_SESSION['app_type'] === 'officeraven') {
        Session::GetInstance()->mylocation_url = '';
        $mygroupid = Group::GetGroupIdForBranchId($_USER->val('homeoffice'), $_ZONE->id());
        if ($mygroupid) {
            Session::GetInstance()->mylocation_url = Url::GetZoneAwareUrlBase($_ZONE->id()) . 'detail?id=' . $_COMPANY->encodeId($mygroupid);
        }
    }

    $zones = $_COMPANY->getHomeZones($_SESSION['app_type']);

    $redirect = !empty($_GET['rurl']) ? Sanitizer::SanitizeRedirectUrl(base64_url_decode($_GET['rurl'])) : '';

    if (!empty($redirect) && !Url::IsUrlPathOfType($redirect, 'index') && !Url::IsUrlPathOfType($redirect, '')) {
        Http::Redirect($redirect);
    } elseif (str_contains($redirect, '/survey/?')) { // Special fix for survey URLs as they have legacy URLs
        Http::Redirect($redirect);
    } else {
        if ($_SESSION['allow_user_to_choose_zone']) {
            // This session variable will be used to check if users home should point to choose_zone link
            Http::Redirect(Url::GetZoneAwareUrlBase($_ZONE->id()) . 'choose_zone');
        }else{
            if (!empty(Session::GetInstance()->mylocation_url)) {
                Http::Redirect(Session::GetInstance()->mylocation_url);
            } else {
                Http::Redirect(Url::GetZoneAwareUrlBase($_ZONE->id()) . 'home');
            }
        }
    }
    
}

include __DIR__ . '/views/update_login_profile_html.php';

