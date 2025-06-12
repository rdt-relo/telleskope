<?php
ini_set('session.cookie_samesite', 'Lax'); // Added to set samesite cookie param for affinity session
ini_set('session.cookie_secure', '1'); // securing session

require_once __DIR__.'/../include/Company.php';
require_once __DIR__.'/ViewHelper.php';

ob_start();
session_start();

if ($_SESSION['is_teams_session'] ?? false) {
    ini_set('session.cookie_samesite', 'None'); // Override for teams
}

$db = new Hems();

if (!strpos($_SERVER['HTTP_HOST'], '.affinities.io') &&
    !strpos($_SERVER['HTTP_HOST'], '.officeraven.io') &&
    !strpos($_SERVER['HTTP_HOST'], '.talentpeak.io') &&
    !strpos($_SERVER['HTTP_HOST'], '.peoplehero.io')
    ) {
    //Block access to admin pages via non https://companyname.affinities.io domains
    header(HTTP_NOT_FOUND);
    die('Error: Invalid URL');
}
$_COMPANY = null;
$_USER = null;
$_ZONE = null;

/* @var User $_USER */
/* @var Company $_COMPANY */

if (!empty($_SESSION['companyid'])) {
    $_COMPANY = Company::GetCompany($_SESSION['companyid']);
}

// Affinity Sessions expires 4 hours after last use
if (empty($_SESSION['userid'])
    || $_COMPANY === null
    || (time() - (int)@$_SESSION['l_a']) > ((int)$_COMPANY->getCompanySecurity()['apps_inactivity_max'])*60
    || (time() - (int)@$_SESSION['s_s']) > ((int)$_COMPANY->getCompanySecurity()['apps_session_max'])*60)
{
    if (!defined('INDEX_PAGE')) {
        session_unset();
        session_destroy();
        //session_start(); Removing as it creates duplicate session cookies
        if (defined('AJAX_CALL')) {
            header('HTTP/1.1 401 Unauthorized (Please Sign in)');
            exit();
        } else {
            ## Redirect URL after login
            $rurl = base64_url_encode("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
            Http::Redirect("index?rurl={$rurl}");
        }
    }
} else {
    $_USER = User::GetUserFromSessionCache($_SESSION['userid']); //Instantiate User only after the company is successfully instantiated as it needs an active company.
    if ($_USER === null || !$_USER->isActive()) { // The special case where the user was deleted;
        session_unset();
        session_destroy();
        //session_start(); Removing as it creates duplicate session cookies
        Http::Redirect("index");
    }

    if (empty($_SESSION['timezone']))
        $_SESSION['timezone'] = $_USER->val('timezone');

    // This is a fix the problem of invalid timezone due to outdated ICU (International Components for Unicode)
    $_SESSION['timezone'] = TskpTime::OUTDATED_TIMEZONE_MAP[$_SESSION['timezone']] ?? $_SESSION['timezone'];

    date_default_timezone_set($_SESSION['timezone'] ?: 'UTC');

    TskpGlobals::InitZone();

    if (!$_ZONE) {
        if (basename($_SERVER['PHP_SELF']) != 'update_login_profile.php') {
            Logger::Log('Unable to set zone ... redirecting to the update_login_profile page', Logger::SEVERITY['INFO']);
            $rurl = base64_url_encode("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
            Http::Redirect("update_login_profile?rurl={$rurl}");
        }
    }

    if ($_ZONE && ($_ZONE->val('app_type') !== $_SESSION['app_type'])) {
        $parsed_url = parse_url("https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}");
        $parsed_url['host'] = $_COMPANY->getAppDomain($_ZONE->val('app_type'));
        Http::Redirect(Url::UnparseUrl($parsed_url));
    }
}

$app_name_l = ucfirst($_SESSION['app_type'] ?? '');
/** @noinspection ForgottenDebugOutputInspection */

$_SESSION['l_a'] = time();

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !isset($_GET['uploadEmailTemplateMedia'])
    && !defined('SKIP_CSRF_CHECK')) {

    if (!(isset($_POST['csrf_token']) && hash_equals($_POST['csrf_token'], Session::GetInstance()->csrf))
        && !(isset($_SERVER['HTTP_X_CSRF_TOKEN']) && hash_equals($_SERVER['HTTP_X_CSRF_TOKEN'], Session::GetInstance()->csrf))
        && !defined('AJAX_CSRF_EXEMPT')) {

        Logger::Log("{$app_name_l} CSRF Mismatch Error, Exiting with 403 Forbidden", Logger::SEVERITY['WARNING_ERROR']);
        header("HTTP/1.1 403 Forbidden (CSRF Mismatch)");
        exit();
    }
}

/**
 * Change for ticket #4179 - Delegated access
 * This is a login via delegated access
 * Check if user still has the grant and the grant isn't revoked by the grantor
 */

if ($_USER ?-> isDelegatedAccessUser()) {

    $authorized_zoneids = $_USER->getDelegatedAccessUserAuthorizedZones();

    if (!in_array($_ZONE->id(), $authorized_zoneids)) {
        /**
         * If code enters this block, it means that the delegated user has lost access to this zone OR all zones
         * If user has lost access to all zones, then logout the user
         * If user still has access to a zone and has accidentally switched to an unauthorized zone, then show forbidden 403 message
         */

        if (empty($authorized_zoneids)) {
            // User does not have access in any zone, so logout the user
            Logger::AuditLog("Delegated access force logout - userid {$_SESSION['grantee_userid']} logged out as {$_USER->id()}", [
                'grantor_userid' => $_USER->id(),
                'grantee_userid' => $_SESSION['grantee_userid'],
            ]);
            Http::Redirect('logout');
        }
        Http::Forbidden(gettext('Access to this zone is restricted. Please request access from the grantor'));
    }
}

require_once __DIR__.'/locales/language_setup.php';
