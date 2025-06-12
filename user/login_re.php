<?php
include_once __DIR__.'/head.php';

// Todo: Use this file to record user_privacy_ consent, timezone and ie_11 setting
// currently timezone and ie_11 setting are stored as
if (empty($_COMPANY)) { // User or bot tried to get in directly
    header(HTTP_UNAUTHORIZED);
    echo('Invalid session state, please use the correct login URL');
    exit();
}

$loginMethodId = intval($_GET['lm_id']);
$loginMethod = $_COMPANY->getCompanyLoginMethodByScopeAndId($_SESSION['app_type'], $loginMethodId);

$_SESSION['policy_accepted' ]= 0;
if (isset($_GET['policy_accepted']) && $_GET['policy_accepted'] == 'yes') {
    $_SESSION['policy_accepted' ]= 1;
}

if ($_GET['lm_type'] === 'saml2') {
    $_SESSION['saml2_settings'] = $loginMethod;
    $_SESSION['use_affinities_identity'] = $loginMethod['use_affinities_identity'] ?? 0;
    $_SESSION['auto_provisioning'] = $loginMethod['auto_provisioning'] ?? 1;
    header('location: saml2/sso?realm=' . $_SESSION['realm']);
} elseif ($_GET['lm_type'] === 'microsoft') {
    $_SESSION['authenticator_version'] = $loginMethod['authenticator_version'] ?? 1;
    $_SESSION['auto_provisioning'] = $loginMethod['auto_provisioning'] ?? 1;
    $_SESSION['tenantguid'] = $loginMethod['tenantguid'];
    header('location: office/app/oauth.php');
} elseif ($_GET['lm_type'] === 'connect') {
    if ($_COMPANY->isConnectEnabled()) {
        $_SESSION['connect_settings'] = $loginMethod;
        header('location: connect/login.php');
    } else {
        error_and_exit("Connect login type is not enabled");
    }
//} elseif ($_GET['lm_type'] === 'username') {
    // not supported
} elseif ($_GET['lm_type'] === 'otp') {
    $_SESSION['otp_settings'] = $loginMethod;
    header('location: otp/login.php');
} else {
    header('location: login');
}
