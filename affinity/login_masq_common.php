<?php
ini_set('session.cookie_samesite', 'Lax'); // Added to set samesite cookie param for affinity session
ob_start();
session_start();
$_SESSION['l_a'] = time(); // Session last acccess time
$_SESSION['s_s'] = time(); // Session start time

require_once __DIR__ . '/../include/Company.php';
global $_COMPANY;

if (empty($_GET[$app_id])) {
    header(HTTP_FORBIDDEN);
    die('Forbidden (Access Denied)');
}

$urlhost = parse_url("https://{$_SERVER['HTTP_HOST']}", PHP_URL_HOST);
$subdomain = explode('.', $urlhost, 2)[0];
if (!($check = Company::GetCompanyBySubdomain($subdomain))) {
    header(HTTP_FORBIDDEN);
    die('Forbidden (Access Denied)');
}

$aes_prefix = substr($app_key, 2, 22);
$aes_suffix = $check->val('aes_suffix');

$val_str = aes_encrypt($_GET[$app_id], $aes_prefix . $aes_suffix, $app_secret, true);
if (empty($val_str)) { // Invalid token
    Logger::Log("Invalid Super Token", Logger::SEVERITY['SECURITY_ERROR']);
    header(HTTP_FORBIDDEN);
    die('Forbidden (Access Denied)');
}
$vals = json_decode($val_str, true);
if (!count($vals) || empty($vals['su']) || empty($vals['u']) || empty($vals['c']) || ($vals['c'] != $check->id()) || (time() - $vals['now'] > 2)) {
    // Invalid token, note:
    // token expires after 2 seconds
    Logger::Log("Expired or Bad Super Token", Logger::SEVERITY['SECURITY_ERROR']);
    header(HTTP_FORBIDDEN);
    die('Forbidden (Access Denied)');
}

$uid = (int)$vals['u'];
$cid = (int)$vals['c'];
if (!($_COMPANY = Company::GetCompany($cid)) || !($user = User::GetUser($uid)) || !($user->isActive())) {
    Logger::Log("Invalid Token ids recieved  for session with secret '" . @$_SESSION['ss'] . "', Token = " . $val_str, Logger::SEVERITY['SECURITY_ERROR']);
    Http::Redirect('index');
}

$_SESSION['context_userid'] = $vals['u'].'-M-'.$vals['su'];
$_SESSION['masq_access'] = true;
$_SESSION['super_userid'] = $vals['su'];
$_SESSION['userid'] = $user->id();
$_SESSION['companyid'] = $_COMPANY->id();
$_SESSION['tz_b'] = (isset($vals['t']) && isValidTimeZone($vals['t'])) ? $vals['t'] : ''; // Validate timezone
$_SESSION['ip_a'] = $_SERVER['REMOTE_ADDR'];
$_SESSION['u_a'] = $_SERVER['HTTP_USER_AGENT'];

if (empty($user->val('timezone'))) {
    if (empty($_SESSION['tz_b'])) {
        $_SESSION['timezone'] = 'Etc/GMT+12'; //Set to UTC
    } else {
        $_SESSION['timezone'] = $_SESSION['tz_b'];
    }
} else {
    $_SESSION['timezone'] = $user->val('timezone');
    $_SESSION['tz_b'] = $user->val('timezone');
}

$_SESSION['app_type'] = $app_name;
$zoneid = $user->getMyConfiguredZone($_SESSION['app_type']);
if ($zoneid == 0) {
    // If Zoneid is not set, try to set it to default zone for the company if one exists
    $zones = array_values($_COMPANY->getHomeZones($_SESSION['app_type']));
    if (count($zones) == 1) {
        $zoneid = $zones[0]['zoneid'];
        $user->addUserZone($zoneid, true, false);
        $user->clearSessionCache();
    }
}
$_ZONE = $_COMPANY->getZone($zoneid);

/**
 * Change for ticket #4179 - Delegated access
 * Here the zoneid is set and not 0, so the above if block doesn't run and the user session cache is not cleared
 * Need to unset $_SESSION['__cachedUser'] which doesn't allow degated user OR grantee to sign in as grantor
 */
$user->clearSessionCache();

session_regenerate_id(true); //Regenerate session id
Http::Redirect('update_login_profile');
