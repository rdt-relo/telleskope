<?php
include_once __DIR__.'/../head.php';
include_once __DIR__.'/../../include/UserConnect.php';

if (
    !isset($_GET['token']) ||
    !($tokenValsStr =  $_GET['token']) ||
    !($tokenVals = UserConnect::DecryptString2Array($tokenValsStr)) ||
    empty($tokenVals['companyid']) ||
    (isset($_COMPANY) && ($_COMPANY->id() != $tokenVals['companyid'])) || // COMPANY is set, check if it matches the token companyid
    (!isset($_COMPANY) && ($_COMPANY = Company::GetCompany($tokenVals['companyid'])) == null) || // COMPANY is not set, check if we can instantiate it token companyid
    empty($tokenVals['external_email']) ||
    ($connectUser = UserConnect::GetConnectUserByEmail($tokenVals['external_email'])) == null ||
    empty($tokenVals['expires_on']) ||
    time() > intval($tokenVals['expires_on']) ||
    empty($tokenVals['password_reset_code']) ||
    !($resetCode = $tokenVals['password_reset_code'])  || // Reset code should *not* be empty
    ($resetCode != $connectUser->val('password_reset_code')) ||
    empty($tokenVals['app_type']) ||
    (isset($_SESSION['app_type']) && $tokenVals['app_type'] != $_SESSION['app_type'])
) {
    error_and_exit('Error: Invalid or expired link. Please request a new password reset link', 'login', 'Continue');
} else {
    if (isset($_POST['newpassword'])) {
        $newPassword = $_POST['newpassword'];
        $confirmPassword = $_POST['confirmpassword'];

        if ($newPassword != $confirmPassword) {
            $error_message = "New password and confirm password do not match! Try again";
        } elseif (!$connectUser->isEmailVerified()) {
            $connectUser->sendEmailVerificationCode();
            error_and_exit('Email verification required. A verification link was emailed to your registered email. Please follow the instruction in email to verfiy your email');
        } else {
            $retVal = $connectUser->updateConnectPassword($newPassword, $resetCode);
            if ($retVal['status']) {
                unset($_SESSION['verified_external_email']);
                $_SESSION['connect_verification'] = 'done';
                $_SESSION['app_type'] = $tokenVals['app_type'];
                $_SESSION['cid'] = $connectUser->val('companyid');
                $link = 'login.php?email=' . $connectUser->val('external_email');
                success_and_exit('Password changed successfully', $link);
            } else {
                $error_message = $retVal['message'];
            }
        }
    }
}
$title = 'Reset Password';
include __DIR__ . '/views/resetpassword.html';
