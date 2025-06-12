<?php
include_once __DIR__.'/../head.php';
include_once __DIR__.'/../../include/UserConnect.php';

$_SESSION = array(); // Reset the session variable

if (
    !isset($_GET['token']) ||
    !($tokenValsStr =  $_GET['token']) ||
    !($tokenVals = UserConnect::DecryptString2Array($tokenValsStr)) ||
    empty($tokenVals['companyid']) ||
    (isset($_COMPANY) && ($_COMPANY->id() != $tokenVals['companyid'])) || // COMPANY is set, check if it matches the token companyid
    (!isset($_COMPANY) && ($_COMPANY = Company::GetCompany($tokenVals['companyid'])) == null) || // COMPANY is not set, check if we can instantiate it token companyi
    empty($tokenVals['external_email']) ||
    ($tokenConnectUser = UserConnect::GetConnectUserByEmail($tokenVals['external_email'])) == null ||
    empty($tokenVals['expires_on']) ||
    time() > intval($tokenVals['expires_on']) ||
    empty($tokenVals['email_verification_code']) ||
    ($tokenConnectUser->val('email_verification_code') != $tokenVals['email_verification_code']) ||
    empty($tokenVals['app_type'])
) {
    error_and_exit('Email verification link is not valid or it has expired.');
}



if ($tokenConnectUser->isEmailVerified()){
    $_SESSION['app_type'] = $tokenVals['app_type'];
    $_SESSION['cid'] = $_COMPANY->id();
    success_and_exit('Your email is already verified','login');
}

if (isset($_POST['submit_ack'])) {
    $externalid = $_POST['externalid'];
    $update = $tokenConnectUser->verifyEmail($tokenVals['email_verification_code'], $externalid);
    if ($update > 0) {
        $_SESSION['app_type'] = $tokenVals['app_type'];
        $_SESSION['connect_verification'] = 'done';
        $_SESSION['verified_external_email'] = $tokenVals['external_email'];
        $_SESSION['cid'] = $_COMPANY->id();
        if (empty($tokenConnectUser->val('password'))) {
            $nextLink = 'setpassword';
            $buttonText = 'Set my password';
        } else {
            $nextLink = 'login';
            $buttonText = 'Sign in page';
        }
        success_and_exit('Your email was verified successfully', $nextLink, $buttonText);
    } elseif ($update == -2) {
        $error_message = 'Exceeded maximum allowed email verification attempts, please ask your platform administrator to send you a new email verification link';
    } else {
        $error_message = 'Unable to verify your email at this time';
    }
}

$title = "Email Verification";
include __DIR__ . '/views/emailverification.html';
