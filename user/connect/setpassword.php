<?php
include_once __DIR__.'/../head.php';
include_once __DIR__.'/../../include/UserConnect.php';

if (
    empty($_SESSION['verified_external_email']) ||
    ($connectUser = UserConnect::GetConnectUserByEmail($_SESSION['verified_external_email'])) == null ||
    ($resetCode = $connectUser->val('reset_code')) // Reset code should be empty
) {
    error_and_exit('Password set link is not valid or it has expired.');
}

if (isset($_POST['newpassword'])) {
    $newPassword = $_POST['newpassword'];
    $confirmPassword = $_POST['confirmpassword'];

    if ($newPassword!=$confirmPassword){
        $error_message = "New password and confirm password do not match! Try again";
    } elseif (!$connectUser->isEmailVerified()) {
        $connectUser->sendEmailVerificationCode();
        error_and_exit('Email verification required. A verification link was emailed to your registered email. Please follow the instruction in email to verfiy your email');
    } else {
        $retVal = $connectUser->updateConnectPassword($newPassword, $resetCode);
        if ($retVal['status']) {
            unset($_SESSION['verified_external_email']);
            $link = 'login.php?email='.$connectUser->val('external_email');
            success_and_exit('Password set successfully', $link);
        } else {
            $error_message = $retVal['message'];
        }
    }
}
$title = 'Reset Password';
include __DIR__ . '/views/resetpassword.html';
