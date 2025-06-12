<?php
ini_set('session.cookie_samesite', 'Lax'); // Added to set samesite cookie param for admin session
ob_start();
session_start();
$_SESSION['l_a'] = time(); // Session last acccess time
$_SESSION['s_s'] = time(); // Session start time

require_once __DIR__ . '/../include/Company.php';
global $_COMPANY;

if (empty($_GET['l'])) {
    Http::Forbidden();
}

$urlhost = parse_url("https://{$_SERVER['HTTP_HOST']}", PHP_URL_HOST);
$subdomain = explode('.', $urlhost, 2)[0];
if (!($check = Company::GetCompanyBySubdomain($subdomain))) {
    Http::Forbidden();
}

$val_str = aes_encrypt($_GET['l'], TELESKOPE_USERAUTH_ADMIN_KEY, "rZVO0XlpOKLTyE997HsApytENOxd8lr5", true);
if (empty($val_str)) { // Invalid token
    Logger::Log("Invalid Token", Logger::SEVERITY['WARNING_ERROR']);
    Http::Redirect("index");
}
$vals = json_decode($val_str, true);
if (!count($vals) || empty($vals['u']) || empty($vals['c']) || ($vals['c'] != $check->id()) || (time() - $vals['now'] > 10) || empty($_SESSION['ss']) || ($_SESSION['ss'] != $vals['ss'])) {
    // Invalid token, note:
    //      token expires after 10 seconds
    //      token is valid only for the session that initiatied it, identified with 'as' key.
    //      'as' key will change if the user tried starting the session with one domain and used login username password that takes him to another.

    Logger::Log("Invalid Admin Token received at " . time() . " for session with secret '" . @$_SESSION['ss'] . "', Token = " . $val_str,Logger::SEVERITY['WARNING_ERROR']);
    Http::Redirect('index');
}

$uid = (int)$vals['u'];
$cid = (int)$vals['c'];
if (!($_COMPANY = Company::GetCompany($cid)) || !($user = User::GetUser($uid)) || !($user->isActive())) {
    Logger::Log("Invalid Admin Token ids recieved  for session with secret '" . @$_SESSION['ss'] . "', Token = " . $val_str);
    session_unset();
    session_destroy();
    Http::Redirect('index');
}

if (!$user->allowAdminPanelLogin()) {
    Logger::Log("Unauthorized user, logging out", Logger::SEVERITY['WARNING_ERROR']);
    session_unset();
    session_destroy();
    Http::Forbidden("You do not have permissions to access the Admin Portal. If you feel you got this message in error, then please contact your company platform administrator");
}

$remoteIP = IP::GetRemoteIPAddr();
if (
    ($allowedCidrs = explode(',', $_COMPANY->getCompanySecurity()['admin_whitelist_ip']) ?: array())
    && !IP::InCIDRList($remoteIP, $allowedCidrs)
){
    $_SESSION['remote_ip'] = ''; // Setting it to empty to all it to fail
    Http::Forbidden('Unauthorized IP address');
} else {
    $_SESSION['remote_ip'] = $remoteIP;
}


$_SESSION['context_userid'] = $vals['u'];
$_SESSION['adminid'] = $user->id();
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

session_regenerate_id(true); //Regenerate session id
$user->recordUsage(0, 'admin');

$rurl = base64_url_decode(@$vals['rurl']); //Valid redirect is base64 encoded.
$rurl = Sanitizer::SanitizeRedirectUrl($rurl);
if (!empty($rurl)) {
    Http::Redirect($rurl);
} else {
    Http::Redirect('index');
}