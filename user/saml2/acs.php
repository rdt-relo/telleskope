<?php
/**
 *  SAML Handler
 */
require_once __DIR__.'/../../include/Company.php';

session_start();

/*
 * The only way to enter this file is to first start the session by going to https://{subdomain}.teleskope.io or
 * https://{subdomain}.affinities.io as the session needs to be set properly.
 * Do not remove or reduce the checks in the block below.
 */

// Check if the URL has realm parameter
if (!isset($_GET['realm'])) {
    // Realm is required, without it the URL is invalid.
    header('HTTP/1.1 404 Not Found');
    echo "Invalid URL (SAML2 - ACS: Missing Realm)";
    exit();
}

// Check if the realm is valid
$realm_parts = explode('.', $_GET['realm']);
$valid_apps = array('affinities', 'officeraven', 'talentpeak', 'peoplehero', 'teleskope');
if (count($realm_parts) != 3 || !in_array($realm_parts[1],$valid_apps) || $realm_parts[2] != 'io') {
    header('HTTP/1.1 404 Not Found');
    echo "Invalid URL (SAML2 - ACS: Invalid Realm)";
    exit();
}

// If this call is for a IDP initiated SAML2, then reset the session
// IDP initiated sessions have a Login method id included in urls

if (isset($_GET['lmid']) || isset($_GET['amp;lmid'])) {
    $lmid = isset($_GET['lmid']) ? (int)$_GET['lmid'] : (int) $_GET['amp;lmid'];
    reset_session_for_idpini_login_methods($realm_parts[0], $realm_parts[1], $lmid);
}

// Check if the session was correctly set for SAML2 SSO, if not it might be IDP initiated redirecto realm or relay state
if (!isset($_SESSION) ||
    !isset($_SESSION['realm']) ||
    empty($_SESSION['saml2_settings'])
) {
    // If the relay state is given, lets see if we can redirect to it

    // Get the host value in the relay state.
    // While we set the relay state to realm in sso.php, sometime the customers send us RelayState from IDP which
    // can be in the form of https://{subdomain}.{application}.io
    $relay_state_host = preg_replace(['!^https://!', '!^([A-Za-z0-9\-\_\.]*).*!' ], ['','\1'], $_POST['RelayState'] ?? '');

    // Now if we have the host, check if the host is a valid teleskope hostname.
    // If so redirect to it.
    if (!empty($relay_state_host) &&
        preg_match('!^[A-Za-z0-9\-\_\.]*\.(teleskope|affinities|officeraven|talentpeak|peoplehero)\.io$!', $relay_state_host)
    ) {
        Logger::Log('SAML2 - ACS: IDP Initiated or Invalid Session: Redirecting to Relay State Host= '.$relay_state_host . ', RelayState='.$_POST['RelayState'], Logger::SEVERITY['INFO']);
        header("location: https://{$relay_state_host}/");
        exit();
    }

    // Session was not set properly, redirect back to the realm
    Logger::Log('SAML2 - ACS: IDP Initiated or Invalid Session: Missing Relay State, redirecting to realm = '. $_GET['realm'], Logger::SEVERITY['INFO']);
    header("location: https://{$realm_parts[0]}.{$realm_parts[1]}.{$realm_parts[2]}/");
    exit();
}

require_once __DIR__ . '/_toolkit_loader.php';
require_once __DIR__ . '/settings.php';

//Logger::Log ("Request = ".json_encode($_REQUEST));

try {
    $auth = new OneLogin_Saml2_Auth($settingsInfo);
} catch (Exception $exception) {
    $severity = Logger::SEVERITY['FATAL_ERROR'];
    if (str_contains($exception->getMessage(),'Only supported HTTP_POST Binding')) {
        $severity = Logger::SEVERITY['WARNING_ERROR'];
    }
    Logger::Log('SAML2 - ACS: Fatal Error 001, exception '. $exception->getMessage(), $severity);
}

if (isset($_SESSION) && isset($_SESSION['AuthNRequestID'])) {
    $requestID = $_SESSION['AuthNRequestID'];
} else {
    $requestID = null;
}

try {
    $auth->processResponse($requestID);
} catch (Exception $exception) {
    Logger::Log('SAML2 - ACS: Fatal Error 002, exception '. $exception->getMessage());
}

$errors = $auth->getErrors();

if (!empty($errors)) {
    echo '<p>', implode(', ', $errors), '</p>';
}

if (!$auth->isAuthenticated()) {
    echo "<p>Not authenticated</p>";
    exit();
}

$_SESSION['saml2_data'] = array();
$_SESSION['saml2_data']['samlNameId'] = $auth->getNameId();
$_SESSION['saml2_data']['samlUserdata'] = $auth->getAttributes();
$_SESSION['saml2_data']['samlNameIdFormat'] = $auth->getNameIdFormat();
$_SESSION['saml2_data']['samlNameIdNameQualifier'] = $auth->getNameIdNameQualifier();
$_SESSION['saml2_data']['samlNameIdSPNameQualifier'] = $auth->getNameIdSPNameQualifier();
$_SESSION['saml2_data']['samlSessionIndex'] = $auth->getSessionIndex();
unset($_SESSION['AuthNRequestID']);

header('Pragma: no-cache');
header('Cache-Control: no-cache, must-revalidate');
header('Location: ../login.php');
exit();

/**
 * This function resets the session variable on detecting SAML configuration that supports
 * IDP initiated logins only.
 * @param string $subdomain
 * @param string $app_type
 * @param int $lm_id
 * @return void
 */
function reset_session_for_idpini_login_methods(string $subdomain, string $app_type, int $lm_id)
{
    // Recreate session variables
    $app_type = strtolower($app_type);
    $c = Company::GetCompanyBySubdomain($subdomain);
    if ($c) {

        $loginMethod = $c->getCompanyLoginMethodByScopeAndId($app_type, $lm_id);
        if (
            isset($loginMethod['sp_or_idp_initated']) &&
            $loginMethod['sp_or_idp_initated'] == 'idp'
        ) {
            // Destroy the session.
            global $_SESSION;

            $_SESSION = array();
            $_SESSION['saml2_settings'] = $loginMethod;
            $_SESSION['cid'] = (int)$c->id(); //Typecast to int
            $_SESSION['app_type'] = $app_type;
            $_SESSION['realm'] = $c->val('subdomain') . '.' . $app_type . '.io';
            $_SESSION['policy_accepted'] = 0;
            $_SESSION['client'] = $c->val('subdomain');
            $_SESSION['ss'] = 'idp_initiated';
            $allApps = Company::APP_LABEL;
            $_SESSION['app-name'] = array_key_exists($_SESSION['app_type'],$allApps) ? $allApps[$_SESSION['app_type']] : 'Affinities';
        }
    }
}
