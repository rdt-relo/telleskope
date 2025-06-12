<?php
 
/**
 *  SAML Metadata view
 */
ob_start();
$_SESSION = array();
// NOTE: DO NOT START SESSION FOR THIS SCRIPT. WE WILL POPUPLATE $_SESSION VARIABLE TEMPORARILY TO ALLOW Settings to
// WORK. The reason we are not using session values is as we do not want to pollute the session data.
// session_start();
//

/********** Part 1: Initialise $_SESSION vairable **************/

require_once __DIR__.'/../../include/Company.php';

// Check if the URL has realm parameter
if (!isset($_GET['realm'])) {
    // Realm is required, without it the URL is invalid.
    header('HTTP/1.1 404 Not Found');
    echo "Invalid URL (SAML2 - Metadata: Missing Realm)";
    exit();
}

// Check if the realm is valid
$realm_parts = explode('.', $_GET['realm']);
$valid_apps = array('affinities', 'officeraven', 'talentpeak', 'peoplehero', 'teleskope');
if (count($realm_parts) != 3 || !in_array($realm_parts[1],$valid_apps) || $realm_parts[2] != 'io') {
    header('HTTP/1.1 404 Not Found');
    echo "Invalid URL (SAML2 - Metadata: Invalid Realm [Error 001])";
    exit();
}

$_SESSION = array();
$_SESSION['app_type'] = strtolower($realm_parts[1]);
$allApps = Company::APP_LABEL;

$company = Company::GetCompanyByUrl("https://{$realm_parts[0]}.{$realm_parts[1]}.{$realm_parts[2]}");
if (!$company) {
    // Cannot continue if company cannot be found
    header('HTTP/1.1 404 Not Found');
    echo "Invalid URL (SAML2 - Metadata: Invalid Realm [Error 002])";
    exit();
}

$_SESSION['loginmethod'] = (int)$company->val('loginmethod'); //Typecast to int is important
$_SESSION['app-name'] = array_key_exists($_SESSION['app_type'],$allApps) ? $allApps[$_SESSION['app_type']] : 'Affinities';
$_SESSION['realm'] = $company->val('subdomain').'.'.$_SESSION['app_type'].'.io';
$_SESSION['saml2_settings'] = $company->getCompanyLoginMethodByScopeAndId($_SESSION['app_type'], (int)($_GET['lmid'] ?: 0));

if (!$_SESSION['saml2_settings']) {
    // Set SAML2
    $_SESSION['saml2_settings'] = array(
        'strict_mode'               => false,
        'debug_mode'                => false,
        'nameid_format'             => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
        'entityid'                  => '',
        'sso_url'                   => '',
        'x509_cert'                 => '',
        'authn_signed'              => false,
        'want_messages_signed'      => false,
        'want_assertions_encrypted' => false,
        'want_assertions_signed'    => false,
        'want_nameid_encrypted'     => false,
        'requested_authn_context'   => false,
        'use_affinities_identity'   => false,
    );
}


/********** Part 2: Generate meta data and display it **************/

require_once __DIR__.'/_toolkit_loader.php';
require_once __DIR__.'/settings.php' ;

try {
    #$auth = new OneLogin_Saml2_Auth($settingsInfo);
    #$settings = $auth->getSettings();
    // Now we only validate SP settings
    $settings = new OneLogin_Saml2_Settings($settingsInfo, true);
    $metadata = $settings->getSPMetadata();
    $errors = $settings->validateMetadata($metadata);
    if (empty($errors)) {
        ob_clean();
        header('Content-Type: text/xml');
        echo $metadata;
    } else {
        throw new OneLogin_Saml2_Error(
            'Invalid SP metadata: '.implode(', ', $errors),
            OneLogin_Saml2_Error::METADATA_SP_INVALID
        );
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

// Very important
unset($_SESSION);
