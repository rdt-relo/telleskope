<?php
include_once __DIR__.'/head.php';

if (isset($_REQUEST['code'])) {
    $request_id = $_REQUEST['code']; // No need to filter Request code as we use prepared statements in the next func.
    $email = User::GetEmailByPasswordResetCode($request_id);

    if (empty($email)) {
        // Password reset code has expired, reject the request with an error message
        $error_message = 'Error: Invalid or expired link. Please request a new password reset link';
        $show_forgot_button = true;
    } elseif (isset($_POST['newpassword'])) {
        if (!verify_recaptcha()) {
            $error_message = "Incorrect reCAPTCHA, try again";
        } else {
            $user = User::GetUserByEmail($email);
            $newPassword = $_POST['newpassword'];
            $confirmPassword = $_POST['confirmpassword'];

            if ($confirmPassword != $newPassword) {
                $error_message = 'New password and confirm password do not match! Try again';
            } elseif (password_verify($newPassword, $user->val('password'))) {
                $error_message = 'New password cannot be the same as old password! Try again';
            } elseif (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\-\ \!\@\#\$\%\^\&\_])[a-zA-Z\d\-\ \!\@\#\$\%\^\&\_]{8,}$/', $newPassword)) {
                if ($user->updatePassword($newPassword)) {
                    // Delete all password reset links for this user; to remove stale links if any.
                    if (!$user->isVerified()) {
                        //Implied verification as the user was able to reset password using the link sent to their email
                        $user->validateConfirmationCode($user->val('confirmationcode'));
                    }
                    User::DeletePasswordResetCode($email);
                    User::DeleteFailedLoginAttempt($email);
                    $exit_message = 'Your password has been reset. Please go back to your organizations Sign In page';
                    $_SESSION['reset_email'] = $email;
                    $link = (isset($_SESSION['rurl']) ? 'login?continue=1' : '');
                    success_and_exit($exit_message, $link);
                } else {
                    $error_message = 'Error: Internal Server Error. Please request a new password reset link';
                    $show_forgot_button = true;
                }
            } else {
                $error_message = 'Error: New password must be minimum 8 characters long and must contain an uppercase letter, a lowercase letter, a number and a special character';
            }
        }
    } else {
        if (!isset($_SESSION['cid'])) {
            $_COMPANY = Company::GetCompanyByEmail($email);
            $logo = $_COMPANY->val('logo');
            $_SESSION['cid'] = $_COMPANY->id();
        } else {
            $logo = '../image/teleskope-logo.png';
        }

        $error_message = 'Enter a password that is minimum 8 characters long, contains an uppercase letter, a lowercase letter, a number and a special character';
    }
} else {
    $error_message = 'Error: Invalid or expired link. Please request a new password reset link';
    $show_forgot_button = true;
}
$title = 'Reset Password';
include __DIR__ . '/views/reset.html';
