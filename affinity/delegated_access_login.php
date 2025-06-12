<?php
require_once __DIR__.'/head.php';

$self_userid = $_USER->id();
// Check if user still has the grant
if ($_COMPANY === null ||
    $_ZONE === null ||
    empty($_GET['delegate_access_token']) ||
    ($delegatedAccess = DelegatedAccess::GetDelegatedAccessByToken($_GET['delegate_access_token'])) === null ||
    ($delegatedAccess->val('grantee_userid') != $self_userid) ||
    ($delegatedAccess->val('zoneid') != $_ZONE->id()) ||
    ($grantorUser = User::GetUser($delegatedAccess->val('grantor_userid'))) === null ||
    (!$grantorUser->isActive())
) {
    header(HTTP_FORBIDDEN);
    die('Forbidden (Access Denied)');
}

$grantee_session = $_SESSION; // Save the session before destroying it.

# All good so far, now lets reset the $_SESSION
$_USER = null;
$_SESSION = array();
session_destroy();
ini_set('session.cookie_samesite', 'Lax'); // Added to set samesite cookie param for affinity session
session_start();
session_regenerate_id(true); //Regenerate session id


$_SESSION['context_userid'] = $grantorUser->id() . '-D-' . $self_userid;
$_SESSION['delegated_access'] = true;
$_SESSION['delegated_access_id'] = $delegatedAccess->id();
$_SESSION['grantee_userid'] = $self_userid;
$_SESSION['userid'] = $grantorUser->id();
$_SESSION['companyid'] = $_COMPANY->id();

$_SESSION['ip_a'] = $_SERVER['REMOTE_ADDR'];
$_SESSION['u_a'] = $_SERVER['HTTP_USER_AGENT'];

// Restore some values from grantees session as they do not change as part of session migration
$_SESSION['l_a'] = $grantee_session['l_a'];; // Session last acccess time
$_SESSION['s_s'] = $grantee_session['s_s'];; // Session start time
$_SESSION['tz_b'] = $grantee_session['tz_b'];
$_SESSION['timezone'] = $grantee_session['timezone'];
$_SESSION['app_type'] = $grantee_session['app_type'];
// Note the $_COMPANY and $_ZONE do not change.


$zoneid = $grantorUser->getMyConfiguredZone($_SESSION['app_type']);
// The following code should never execute as the grantorUser should have a valid zone if they have provided
// delegated access for the zone, however this is just a safety check.
// Note: The $_ZONE does not change as we are delegating grantee into the same zone.
if ($zoneid == 0) {
    // If Zoneid is not set, try to set it to default zone for the company if one exists
    $zones = array_values($_COMPANY->getHomeZones($_SESSION['app_type']));
    if (count($zones) == 1) {
        $zoneid = $zones[0]['zoneid'];
        $grantorUser->addUserZone($zoneid, true, false);

    }
}
$grantorUser->clearSessionCache();

// Grantee has successfully logged in as grantor, so lets create an entry in the logs table
$delegatedAccess->addAuditLog('USAGE', 'Login');

Logger::AuditLog("Delegated access login - userid {$self_userid} logged in as {$grantorUser->id()}", [
    'grantor_userid' => $grantorUser->id(),
    'grantee_userid' => $self_userid,
]);


Http::Redirect('home');
