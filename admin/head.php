<?php
ini_set('session.cookie_samesite', 'Lax'); // Added to set samesite cookie param for admin session
require_once __DIR__.'/../include/Company.php'; //This file internally calls dbfunctions, Company etc.

ob_start();
session_start();

$db = new Hems();

$_SESSION['updated'] ?? ($_SESSION['updated']=0); // Init values if not set
$_SESSION['added'] ?? ($_SESSION['added']=0); // Init values if not set
$_SESSION['error'] ?? ($_SESSION['error']=0); // Init values if not set

if(strpos($_SERVER['HTTP_HOST'], ".teleskope.io") == false) { //This check is to block access to admin pages via non https://companyname.teleskope.io domains
    die( header(HTTP_NOT_FOUND) . ' Broken Link');
}
$userid = '';
$companyid = '';
$_COMPANY = null;
$_USER = null;
$_ZONE = null;

/* @var User $_USER */
/* @var Company $_COMPANY */

if (!empty($_SESSION['companyid'])) {
    $_COMPANY = Company::GetCompany($_SESSION['companyid']);
}

if (empty($_SESSION['adminid'])
    || $_COMPANY === null
    || (time() - (int)@$_SESSION['l_a']) > ((int)$_COMPANY->getCompanySecurity()['admin_inactivity_max'])*60
    || (time() - (int)@$_SESSION['s_s']) > ((int)$_COMPANY->getCompanySecurity()['admin_session_max'])*60
//    || (IP::GetRemoteIPAddr() != $_SESSION['remote_ip'])
)
{
	if (!defined('INDEX_PAGE')) {
		session_unset();
        session_destroy();
//		session_start(); Removing as it creates duplicate session cookies
        if (defined('AJAX_CALL')) {
            header('HTTP/1.1 401 Unauthorized (Please Sign in)');
            exit;
        } else {
            ## Redirect URL after login
            $rurl = base64_url_encode("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
            Http::Redirect("index?rurl={$rurl}");
        }
	}
} else {
	$companyid = $_SESSION['companyid'];
	$userid = $_SESSION['adminid'];

	$_USER  = User::GetUser($userid); //Instantiate User only after the company is successfully instantiated as it needs an active company.
	if ($_USER  === null || !$_USER->isActive() || !$_USER->allowAdminPanelLogin()) { // The special case where the user was deleted or their admin settings were changed.
		session_unset();
		session_destroy();
//		session_start(); Removing as it creates duplicate session cookies
        Http::Redirect('index');
	}

    Lang::Init('en');

    if (empty($_SESSION['timezone']))
        $_SESSION['timezone'] = $_USER->val('timezone');

    // This is a fix the problem of invalid timezone due to outdated ICU (International Components for Unicode)
    $_SESSION['timezone'] = TskpTime::OUTDATED_TIMEZONE_MAP[$_SESSION['timezone']] ?? $_SESSION['timezone'];

    date_default_timezone_set($_SESSION['timezone'] ?: 'UTC');

    $url_zoneid = Url::GetZoneidFromRequestURL();
    if ($url_zoneid) {
        $_ZONE = $_COMPANY->getZone($url_zoneid);
    } else {
        // Just assign the first zone that the user can administer
        foreach ($_COMPANY->getZones() as $z) {
            if ($z['isactive'] === "1" && ($_USER->isCompanyAdmin() || $_USER->isZoneAdmin((int)$z['zoneid']))) {
                $_ZONE = $_COMPANY->getZone($z['zoneid']);
                break;
            }
        }
    }

    if (!$_ZONE || !$_USER->isAdmin()) { // Exhausted all attempts to set the sone.... user cannot manage anything.
        session_unset();
        session_destroy();
        Http::Redirect('index');
    }
}

$_SESSION['l_a'] = time();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['uploadEmailTemplateMedia']) ) {
	if (!(isset($_POST['csrf_token']) && hash_equals($_POST['csrf_token'], Session::GetInstance()->csrf))
		&& !(isset($_SERVER['HTTP_X_CSRF_TOKEN']) && hash_equals($_SERVER['HTTP_X_CSRF_TOKEN'], Session::GetInstance()->csrf))
        && !defined('AJAX_CSRF_EXEMPT')) {
        $app_name_l = 'Admin Panel';
        Logger::Log("{$app_name_l} CSRF Mismatch Error, Exiting with 403 Forbidden", Logger::SEVERITY['WARNING_ERROR']);
		header("HTTP/1.1 403 Forbidden (CSRF Mismatch)");
		exit();
	}
}
