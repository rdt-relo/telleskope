<?php
/**
 *  SAML Handler
 */

session_start();

/*
 * The only way to enter this file is to first start the session by going to https://{subdomain}.teleskope.io or
 * https://{subdomain}.{valid-app}.io as the session needs to be set properly.
 * Do not remove or reduce the checks in the block below.
 */

// Check if the URL has realm parameter
if (!isset($_GET['realm'])) {
    // Realm is required, without it the URL is invalid.
    header('HTTP/1.1 404 Not Found');
    echo "Invalid URL (SAML2 - SSO: Missing Realm)";
    exit();
}

// Check if the realm is valid
$realm_parts = explode('.', $_GET['realm']);
$valid_apps = array('affinities', 'officeraven', 'talentpeak', 'peoplehero', 'teleskope');
if (count($realm_parts) != 3 || !in_array($realm_parts[1],$valid_apps) || $realm_parts[2] != 'io') {
    header('HTTP/1.1 404 Not Found');
    echo "Invalid URL (SAML2 - SSO: Invalid Realm)";
    exit();
}

// Check if the session was correctly set for SAML2 SSO, if not redirect back to the application for auto healing
if (!isset($_SESSION) ||
    !isset($_SESSION['realm']) ||
    $_GET['realm'] !== $_SESSION['realm']
) {
    // Session was not set properly, redirect back to the realm
    header("location: https://{$realm_parts[0]}.{$realm_parts[1]}.{$realm_parts[2]}/");
    exit();
}

// Check if the session is set correctly for SAML2 setup
if ((null === $_SESSION['saml2_settings'])) {
    header("HTTP/1.1 403 Forbidden (Access Denied)");
    echo "Unauthorized (SAML2 - SSO: Incorrect session setup)";
    exit();
}

require_once __DIR__.'/_toolkit_loader.php';
require_once __DIR__.'/settings.php';

$auth = new OneLogin_Saml2_Auth($settingsInfo);

$postBindingOnly = !empty($_SESSION['saml2_settings']['idp_supports_post_binding_only']);
if ($postBindingOnly) {
    $authnRequest = new OneLogin_Saml2_AuthnRequest($auth->getSettings(), false, false, true, null);
    $samlRequest = $authnRequest->getRequest(); // This request is encoded for HTTP Redirect
    $samlRequest = base64_encode(gzinflate(base64_decode($samlRequest))); // Convert request to be used with HTTP POST
    $ssoUrl = $auth->getSSOurl();
    $params = [
        'RelayState' => OneLogin_Saml2_Utils::getSelfRoutedURLNoQuery(),
        'SAMLRequest' => $samlRequest
    ];

    echo generateSAMLPostForm($ssoUrl, $params);
    exit();
} else {
    $auth->login($_SESSION['realm']);
    exit();
}

/**
 * This function generates a HTTP POST form to support HTTP POST binding
 * @param string $redirectPostUrl
 * @param array $params array of parameters that will be sent as hidden parameters
 * @return string
 */
function generateSAMLPostForm(string $redirectPostUrl, array $params) : string
{
    $params_div_content = '';
    foreach ($params as $k => $v) {
        $params_div_content .= '<input type="hidden" name="' . $k . '" value="' . $v . '"/>';
    }

    return <<<EOMEOM
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<body onload="document.forms[0].submit()">
<noscript><p><strong>Note:</strong> Since your browser does not support JavaScript, you must press the Continue button once to proceed.</p></noscript>
<form action="{$redirectPostUrl}" method="post">
    <div>{$params_div_content}</div>
    <noscript><div><input type="submit" value="Continue"/></div></noscript>
</form>
</body>
</html>
EOMEOM;
}