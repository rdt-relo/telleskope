<?php
ini_set('session.cookie_samesite', 'Lax'); // Added to set samesite cookie param for admin session
ob_start();
session_start();
$_SESSION['l_a'] = time(); // Session last acccess time
$_SESSION['s_s'] = time(); // Session start time

require_once __DIR__ . '/../include/Company.php';
global $_COMPANY;

if (empty($_GET['FT15hgNq5n'])) {
    header(HTTP_FORBIDDEN);
    die('Forbidden (Access Denied)');
}

$urlhost = parse_url("https://{$_SERVER['HTTP_HOST']}", PHP_URL_HOST);
$subdomain = explode('.', $urlhost, 2)[0];
if (!($check = Company::GetCompanyBySubdomain($subdomain))) {
    header(HTTP_FORBIDDEN);
    die('Forbidden (Access Denied)');
}

$aes_prefix = substr(TELESKOPE_USERAUTH_ADMIN_KEY, 2, 22);
$aes_suffix = $check->val('aes_suffix');

$val_str = aes_encrypt($_GET['FT15hgNq5n'], $aes_prefix . $aes_suffix, "naSHgZnovA4hN4UQlGq7GO38TJqKH6", true);
if (empty($val_str)) { // Invalid token
    Logger::Log("Invalid Super Token", Logger::SEVERITY['SECURITY_ERROR']);
    header(HTTP_FORBIDDEN);
    die('Forbidden (Access Denied)');
}
$vals = json_decode($val_str, true);
if (!count($vals) || empty($vals['su']) || empty($vals['a']) || empty($vals['c']) || ($vals['c'] != $check->id()) || (time() - $vals['now'] > 2)) {
    // Invalid token, note:
    // token expires after 2 seconds
    Logger::Log("Expired or Bad Super Token", Logger::SEVERITY['SECURITY_ERROR']);
    header(HTTP_FORBIDDEN);
    die('Forbidden (Access Denied)');
}

$uid = (int)$vals['a'];
$cid = (int)$vals['c'];
if (!($_COMPANY = Company::GetCompany($cid)) || !($user = User::GetUser($uid)) || !($user->isActive())) {
    Logger::Log("Invalid Admin Super Token recieved, Token = " . $val_str, Logger::SEVERITY['SECURITY_ERROR']);
    Http::Redirect('index');
}

$remoteIP = IP::GetRemoteIPAddr();
if (
    ($allowedCidrs = explode(',', $_COMPANY->getCompanySecurity()['admin_whitelist_ip']) ?: array())
    && !IP::InCIDRList($remoteIP, $allowedCidrs)
){
    $_SESSION['remote_ip'] = ''; // Setting it to empty to all it to fail
    header(HTTP_FORBIDDEN);
    die('Forbidden (Access Denied) Unauthorized IP address');
} else {
    $_SESSION['remote_ip'] = $remoteIP;
}

$_SESSION['context_userid'] = $vals['a'].'-M-'.$vals['su'];
$_SESSION['masq_access'] = true;
$_SESSION['super_userid'] = $vals['su'];
$_SESSION['adminid'] = $user->id();
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

session_regenerate_id(true); //Regenerate session id

Http::Redirect('index');
