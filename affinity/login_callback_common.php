<?php
ini_set('session.cookie_samesite', 'Lax'); // Added to set samesite cookie param for affinity session
ob_start();
session_start();
$_SESSION['l_a'] = time(); // Session last acccess time
$_SESSION['s_s'] = time(); // Session start time

// Check if this is teams context
if (isset($_GET['is_teams']) && $_GET['is_teams'] == '1') {
    ini_set('session.cookie_samesite', 'None');
    $_SESSION['is_teams_session'] = true;
}
Session::GetInstance()->mylocation_url = null;

require_once __DIR__ . '/../include/Company.php';
global $_COMPANY;

if (empty($_GET['l'])) {
    header(HTTP_FORBIDDEN);
    die('Forbidden (Access Denied)');
}

$urlhost = parse_url("https://{$_SERVER['HTTP_HOST']}", PHP_URL_HOST);
$subdomain = explode('.', $urlhost, 2)[0];
if (!($check = Company::GetCompanyBySubdomain($subdomain))) {
    header(HTTP_FORBIDDEN);
    die('Forbidden (Access Denied)');
}

$val_str = aes_encrypt($_GET['l'], $app_key, $app_secret, true);
if (empty($val_str)) { // Invalid token
    Logger::Log("Invalid Token", Logger::SEVERITY['WARNING_ERROR']);
    Http::Redirect("index");
}
$vals = json_decode($val_str, true);
$valid_secret = isset($vals['ss']) && (in_array($vals['ss'], ['idp_initiated', 'connect_verified']) || ($vals['ss'] == ($_SESSION['ss'] ?? '')));
if (!count($vals) || empty($vals['u']) || empty($vals['c']) || ($vals['c'] != $check->id()) || (time() - $vals['now'] > 10) || !$valid_secret) {

    // Invalid token, note:
    // 		token expires after 10 seconds
    //		token is valid only for the session that initiatied it, identified with ss key.
    //		ss key will change if the user tried starting the session with one domain and used login username password that takes him to another.

    Logger::Log("Invalid Token received at " . time() . " for session with secret '" . @$_SESSION['ss'] . "', Token = " . $val_str, Logger::SEVERITY['WARNING_ERROR']);
    $redirect_header = !empty($vals['rurl']) ? 'location:index?rurl='.$vals['rurl'] : 'location:index';
    header($redirect_header);
    exit();
}

$uid = (int)$vals['u'];
$cid = (int)$vals['c'];
if (!($_COMPANY = Company::GetCompany($cid)) || !($user = User::GetUser($uid)) || !($user->isActive())) {
    Logger::Log("Invalid Token ids recieved for session with secret '" . @$_SESSION['ss'] . "', Token = " . $val_str, Logger::SEVERITY['WARNING_ERROR']);
    $redirect_header = !empty($vals['rurl']) ? 'location:index?rurl='.$vals['rurl'] : 'location:index';
    header($redirect_header);
    exit();
}

$_SESSION['context_userid'] = $vals['u'];
$_SESSION['userid'] = $user->id();
$_SESSION['companyid'] = $_COMPANY->id();
$_SESSION['ip_a'] = $_SERVER['REMOTE_ADDR'];
$_SESSION['u_a'] = $_SERVER['HTTP_USER_AGENT'];

if (empty($user->val('timezone'))) {
    if (empty($_SESSION['tz_b'])) {
        $_SESSION['timezone'] = '';
        $_SESSION['timezone_ask_user'] = true; //Defer to User Input to choose a timezone
    } else {
        $user->updateTimezone($_SESSION['tz_b']); // Update databse
        $_SESSION['timezone'] = $_SESSION['tz_b'];
    }
} else {
    $_SESSION['timezone'] = $user->val('timezone'); // Set it to users profile timezone
    if ($_SESSION['tz_b'] && $_SESSION['tz_b'] != $user->val('timezone')) {
        $_SESSION['timezone_ask_user'] = true; //Defer to User Input to choose a timezone
    }
}

$_SESSION['app_type'] = $app_name;
$my_configured_zoneid = $user->getMyConfiguredZone($_SESSION['app_type']);
if ($my_configured_zoneid == 0) {
    // If Zoneid is not set, try to set it to default zone for the company if one exists
    $zones = array_values($_COMPANY->getHomeZones($_SESSION['app_type']));
    if (count($zones) == 1) {
        $my_configured_zoneid = $zones[0]['zoneid'];
        $user->addUserZone($my_configured_zoneid, true, true);
        $user->clearSessionCache();
    }
}
$_ZONE = $_COMPANY->getZone($my_configured_zoneid);

session_regenerate_id(true); //Regenerate session id

$url = "update_login_profile";

if ($vals['rurl']) {
    $url .= "?rurl=".$vals['rurl'];
}

// Set a cookie with userid that can be used for event checkins for ~10 days ... avoids login which enhances user experience
$eventCheckinDataVals = array('u'=>$user->id(),'n'=>$user->getFullName());
$encEventCheckinDataVals = $_COMPANY->encryptArray2String($eventCheckinDataVals);
$cookie_path = '/1/' . $app_dir . '/ec2';
setcookie('evt_check', $encEventCheckinDataVals, time() + 900000, $cookie_path);
//setcookie('allow_iframe', $encEventCheckinDataVals,
//    array('expires'=> time() + 600000, 'path' => '/1/iframe/', 'secure' => true, 'samesite' => 'None'));

// Next record the usage.
$user->recordUsage((int)$_ZONE?->id(), $_SESSION['app_type']);

$user->enableDisableInbox(true);

Http::Redirect($url);
