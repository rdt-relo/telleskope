<?php

class UserConnect extends Teleskope
{
    const MAX_REPEAT_PASSWORDS = 4;
    const MAX_PASSWORD_RESET_CODE_ATTEMPTS = 3;
    const MAX_EMAIL_VERIFICATION_ATTEMPTS = 3;
    const MAX_LOGIN_ATTEMPTS = 5;
    const MAX_LOGIN_NOLOGIN_BEFORE_REVERIFICATION_DAYS = 90;
    const MAX_EMAIL_VERIFICATION_VALID_DAYS = 365;
    const MAX_MOBILE_VERIFICATION_VALID_DAYS = 365;

    /**
     * Returns object of type UserConnect if a row with matching connect id is found in $_COMPANY scope
     * @param int $connectid
     * @return UserConnect|null
     */
    public static function GetConnectUser(int $connectid): ?UserConnect
    {
        global $_COMPANY;

        $u = self::DBGet("SELECT * FROM user_connect WHERE `companyid`={$_COMPANY->id()} AND `connectid`='{$connectid}' AND isactive=1");
        if (!empty($u)) {
            return new UserConnect($u[0]['connectid'], $_COMPANY->id(), $u[0]);
        }
        return null;
    }

    /**
     * Returns object of type UserConnect if a row with matching userid is found in $_COMPANY scope
     * @param int $userid
     * @return UserConnect|null
     */
    public static function GetConnectUserByTeleskopeUserid(int $userid): ?UserConnect
    {
        global $_COMPANY;

        $u = self::DBGet("SELECT * FROM user_connect WHERE `companyid`={$_COMPANY->id()} AND `userid`='{$userid}' AND isactive=1");
        if (!empty($u)) {
            return new UserConnect($u[0]['connectid'], $_COMPANY->id(), $u[0]);
        }
        return null;
    }

    /**
     * Returns object of type UserConnect if a row with matching external email address is found in $_COMPANY scope
     * @param string $externalEmail
     * @return UserConnect|null
     */
    public static function GetConnectUserByEmail(string $externalEmail): ?UserConnect
    {
        global $_COMPANY;

        $u = self::DBGetPS("SELECT * FROM user_connect WHERE `companyid`=? AND `external_email`=? AND isactive=1", 'is', $_COMPANY->id(),$externalEmail);
        if (!empty($u)) {
            return new UserConnect($u[0]['connectid'], $u[0]['companyid'], $u[0]);
        }
        return null;
    }

    /**
     * Invites the user with userid to connect using external email and mobile number.
     * @param int $userid
     * @param string $external_email
     * @param string $mobileNumber
     * @return int -1 if user exists and is verified, -2 if user exists but with a different email address, 0 some other error, or connectid
     */
    public static function AddConnectUser(int $userid, string $external_email, string $mobileNumber=''): int
    {
        global $_COMPANY;
        $retVal = 0;

        if ($_COMPANY->isValidEmail($external_email)) { // Cannot connect using valid company emails, email should be external
            return 0;
        }

        $connect = self::DBGetPS("SELECT * FROM user_connect WHERE `companyid`=? AND (`userid`=? OR external_email=?)", 'iix', $_COMPANY->id(), $userid, $external_email);
        if (!empty($connect)) {
            if (count($connect) > 1) {
                $retVal = -2;
            } elseif (strcasecmp($external_email, $connect[0]['external_email']) !== 0) {
                $retVal = -2; // Some other email address in use
            } elseif ($userid != intval($connect[0]['userid'])) {
                $retVal = -3; // Some other user is using the email
            } elseif (strcasecmp($mobileNumber, $connect[0]['mobile_phone']) !== 0) {
                $retVal = -4; // Some other mobile number in use
            } else {
                $retVal = -1; // User already connected
            }
        } else {
            $retVal = self::DBInsertPS("INSERT INTO user_connect (`userid`, `companyid`, `external_email`, `mobile_phone`) VALUES (?,?,?,?)", "iixx", $userid, $_COMPANY->id(), $external_email, $mobileNumber);
        }

        return $retVal;
    }

    public function deleteConnectUser(): bool
    {
        global $_COMPANY;

        $updateStatus = self::DBUpdate("DELETE FROM user_connect WHERE companyid={$_COMPANY->id()} AND connectid={$this->id}");
        $teleskopeUser = User::GetUser($this->val('userid'));
        if ($updateStatus && $teleskopeUser) {
            $subject = "Account Deleted - {$_COMPANY->val('companyname')} account";
            $message = <<< EOMEOM
                <p>Hello {$teleskopeUser->getFullName()},</p>
                <br/>
                <p>Your {$_COMPANY->val('companyname')} account using Teleskope Connect has been deleted. If you feel your account was deleted erroneously, please contact your company administrator.</p>
                <br/>
                <br/>
EOMEOM;

            $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
            $message = str_replace('#messagehere#', $message, $template);
            return $_COMPANY->emailSendExternal('Teleskope Connect', $this->val('external_email'), $subject, $message, 'TELESKOPE', '');
        }
        return false;
    }


    /**
     * This method is used to change password of the logged in user.
     * @param string $newPassword
     * @param string $currentPassword
     * @return array
     */
    public function changePassword(string $newPassword, string $currentPassword): array
    {
        if (password_verify($currentPassword, $this->val('password'))) {
            $temporaryVerificationCode = $this->generateVerificationCode();
            $this->fields['password_reset_code'] = $temporaryVerificationCode; // Temporary
            $this->fields['password_reset_code_attempts'] = 0; // Temporary
            return $this->updateConnectPassword($newPassword, $temporaryVerificationCode);
        }
        return array('message' => 'Incorrect value provided for the current password', 'status' => 0);
    }

    /**
     * Sets the provided password as a new password and also updates the previous 5 passwords and expiry date by 90 days.
     * @param string $newPassword
     * @return array
     */
    public function updateConnectPassword(string $newPassword, ?string $passwordResetCode): array
    {
        global $_COMPANY;

        $retVal = array('message' => 'Unable to change password', 'status' => 0);

        // Validate password reset code
        if ($this->val('password_reset_code_attempts') >= self::MAX_PASSWORD_RESET_CODE_ATTEMPTS) {
            $retVal['message'] = "Maximum code verification attempts reached. Please request a new password reset verification code using password reset link";
            return $retVal;
        }

        if ($passwordResetCode != $this->val('password_reset_code')) {
            $passwordResetCodeAttempts = $this->val('password_reset_code_attempts') + 1;
            self::DBMutate("UPDATE user_connect SET password_reset_code_attempts={$passwordResetCodeAttempts} WHERE companyid={$_COMPANY->id()} AND connectid={$this->id}");
            sleep(pow($passwordResetCodeAttempts,2));
            $retVal['message'] = "Incorrect verification code [{$passwordResetCodeAttempts} incorrect attempt(s)]";
            return $retVal;
        }

        // Validate password strength
        if (strlen($newPassword) < 8) {
            $retVal['message'] = "Password should be at least 8 characters in length";
            return $retVal;
        }

        if (!preg_match('@[A-Z]@', $newPassword)) {
            $retVal['message'] = "New password should include at least one upper case letter";
            return $retVal;
        }

        if (!preg_match('@[a-z]@', $newPassword)) {
            $retVal['message'] = "New password should include at least one lower case letter";
            return $retVal;
        }

        if (!preg_match('@[0-9]@', $newPassword)) {
            $retVal['message'] = "New password should include at least one number";
            return $retVal;
        }

        if (!preg_match('/[~!@#$%^&*-]/', $newPassword)) {
            $retVal['message'] = "New password should include at least one special character, valid special characters are ~!@#$%^&*-";
            return $retVal;
        }

        if (preg_match('/(\w)\1{3,}/', $newPassword)) {
            $retVal['message'] = "New password should not include more than 2 repeating characters";
            return $retVal;
        }

        $emailCheck = explode('@',$this->val('external_email'));
        if (isset($emailCheck[0]) && str_contains($newPassword, $emailCheck[0])) {
            $retVal['message'] = "New password should not include parts of the email address";
            return $retVal;
        }

        // Check if the new password is same as last N passwords
        $last_N_passwords_array = explode(',', $this->val('last_N_passwords'));
        foreach ($last_N_passwords_array as $last_N_password) {
            if (password_verify($newPassword, $last_N_password)) {
                $retVal['message'] = 'New password cannot be the same as last ' . self::MAX_REPEAT_PASSWORDS . ' passwords!';
                return $retVal;
            }
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $last_N_passwords_array = array_slice($last_N_passwords_array, 0, self::MAX_REPEAT_PASSWORDS - 1);
        array_unshift($last_N_passwords_array, $newPasswordHash);
        $last_N_passwords = rtrim(implode(',', $last_N_passwords_array), ',');

        $isUpdated = self::DBUpdatePS("UPDATE user_connect SET password_reset_code=null,email_verification_code=null,password_reset_code_attempts=0,failed_login_attempts=0,`password`=?,last_N_passwords=?,password_expiry_date=(NOW() + INTERVAL 90 DAY),`modifiedon`=NOW() WHERE `companyid`=? AND `connectid`=?", "ssii", $newPasswordHash, $last_N_passwords, $_COMPANY->id(), $this->id());

        if ($isUpdated) {
            $retVal['message'] = "Password changed successfully";
            $retVal['status'] = 1;

            $teleskopeUser = User::GetUser($this->val('userid'));
            if ($teleskopeUser) {
                $subject = "Password Changed - {$_COMPANY->val('companyname')} account";
                $message = <<< EOMEOM
                <p>Hello {$teleskopeUser->getFullName()},</p>
                <br/>
                <p>Your password for {$_COMPANY->val('companyname')} account using Teleskope Connect was changed successfully!</p> 
                <br/>
                <br/>
                <p>If you did not change your password, please reset your password again and contact your company administrator to report the issue.</p>
                <br/>
                <br/>
EOMEOM;

                $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
                $message = str_replace('#messagehere#', $message, $template);
                $_COMPANY->emailSendExternal('Teleskope Connect', $this->val('external_email'), $subject, $message, 'TELESKOPE', '');
            }
        }

        return $retVal;
    }

    public function login(string $app_type, string $password): array
    {
        global $_COMPANY;

        $retVal = array('message' => 'Unable to login', 'status' => 0);

        if (!empty($this->val('lastlogin_at')) && intval(strtotime($this->val('lastlogin_at').' UTC') + self::MAX_LOGIN_NOLOGIN_BEFORE_REVERIFICATION_DAYS*86400) < time()) {
            if (empty($this->val('email_verification_code')))
                $this->sendEmailVerificationCode($app_type);
            $retVal['message'] = "Email verification required! Please verify your email address using the verification link sent by your company administrator";
            return $retVal;
        }

        if (!$this->isEmailVerified()) {
            $retVal['message'] = "Email verification required! Please verify your email address using the verification link sent by your company administrator";
            return $retVal;
        }

        if ($this->val('failed_login_attempts') >= self::MAX_LOGIN_ATTEMPTS) {
            $retVal['message'] = "Account locked! Please reset your password using the Forgot Password option";
            return $retVal;
        }

        if (strtotime($this->val('password_expiry_date'). ' UTC') < time()) {
            $retVal['message'] = "Password expired! Please reset your password using the Forgot Password option";
            return $retVal;
        }

        if (password_verify($password, $this->val('password'))) {
            self::DBMutate("UPDATE user_connect SET password_reset_code_attempts=0, password_reset_code=null, failed_login_attempts=0, lastlogin_at=NOW() WHERE companyid={$_COMPANY->id()} AND connectid={$this->id}");
            $retVal['message'] = "Success";
            $retVal['status'] = 1;
        } else {
            $failedLoginAttempts = intval($this->val('failed_login_attempts')) + 1;
            self::DBMutate("UPDATE user_connect SET failed_login_attempts={$failedLoginAttempts} WHERE companyid={$_COMPANY->id()} AND connectid={$this->id}");
            $retVal['message'] = "Unable to login with the provided credentials";
        }
        return $retVal;
    }

    private function generateVerificationCode(): string
    {
        try {
            return strval(random_int(100000, 999999));
        } catch (Exception $e) {
            return str_shuffle(substr(time(),-6));
        }
    }

    public function sendEmailVerificationCode(string $app_type, bool $generateNew = false): bool
    {
        $link_expiry_days = 45;
        global $_COMPANY;

        if ($generateNew || empty($this->val('email_verification_code'))) {
            $verificationCode = $this->generateVerificationCode();
            self::DBMutate("UPDATE user_connect SET email_verification_code='{$verificationCode}', email_verification_attempts=0 WHERE companyid={$_COMPANY->id()} AND connectid={$this->id}");
            $this->fields['email_verification_code'] = $verificationCode;
        }

        $teleskopeUser = User::GetUser($this->val('userid'));
        if ($teleskopeUser) {
            $tokenVals = self::EncryptArray2String(
                array(
                    'companyid' => $_COMPANY->id(),
                    'external_email' => $this->val('external_email'),
                    'expires_on' => time() + (86400 * $link_expiry_days),
                    'email_verification_code' => $verificationCode,
                    'app_type' => $app_type,
                )
            );
            $url = BASEURL . "/user/connect/emailverification?token={$tokenVals}";

            $subject = "Confirm your email - {$_COMPANY->val('companyname')} account";
            $message = <<< EOMEOM
                <p>Hello {$teleskopeUser->getFullName()},</p>
                <br/>
                <p>You are invited to join {$_COMPANY->val('companyname')} account using Teleskope Connect. Please follow the link provided below to verify your email address.</p> 
                <br/>
                <br/>
                <p></p><a href="{$url}">Verification link</a></p>
                <br/>
                <p>The link expires in {$link_expiry_days} days.</p>
                <br/>
                <br/>
EOMEOM;

            $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
            $message = str_replace('#messagehere#', $message, $template);
            return $_COMPANY->emailSendExternal('Teleskope Connect', $this->val('external_email'), $subject, $message, 'TELESKOPE', '');
        }
        return false;
    }

    public static function DecryptString2Array(string $in): array
    {
        // Do not change the keys
        $aes_key = substr(TELESKOPE_GENERIC_KEY, 4, 18) . 'rUjAdUAw5d';
        $decrypted_value = aes_encrypt($in, $aes_key, '3kIyKEGDfmdYJIhTnhju2Tm6nOV1D2ldSai', true);
        return json_decode($decrypted_value, true) ?? array();
    }

    public static function EncryptArray2String(array $in): string
    {
        // Do not change the keys
        $aes_key = substr(TELESKOPE_GENERIC_KEY, 4, 18) . 'rUjAdUAw5d';
        return aes_encrypt(json_encode($in), $aes_key, '3kIyKEGDfmdYJIhTnhju2Tm6nOV1D2ldSai', false, true);
    }

    public function generateAndSendPasswordResetCode(string $app_type): array
    {
        $link_expiry_minutes = 15;
        global $_COMPANY;

        $retVal = array('message' => 'Unable to send password reset code', 'status' => 0);

        if (!$this->isEmailVerified() && !$this->isPhoneVerified()) {
            $retVal['message'] = 'Please verify your email or phone number';
            return $retVal;
        }

        $passwordResetCode = $this->generateVerificationCode();
        self::DBMutatePS("UPDATE user_connect SET password_reset_code={$passwordResetCode},password_reset_code_attempts=0 WHERE companyid={$_COMPANY->id()} AND connectid={$this->id}");
        $this->fields['password_reset_code'] = $passwordResetCode;


        $teleskopeUser = User::GetUser($this->val('userid'));
        if ($teleskopeUser && $this->isEmailVerified()) {
            $tokenVals = self::EncryptArray2String(
                array(
                    'companyid' => $_COMPANY->id(),
                    'external_email' => $this->val('external_email'),
                    'expires_on' => time() + (60 * $link_expiry_minutes),
                    'password_reset_code' => $passwordResetCode,
                    'app_type' => $app_type,
                    )
            );
            $url = BASEURL . "/user/connect/resetpassword?token={$tokenVals}";
            $subject = "Password Reset - {$_COMPANY->val('companyname')} account";

            $message = <<< EOMEOM
            <p>Hello {$teleskopeUser->getFullName()},</p>
            <br/>
            <p>We received your request to reset password for your Teleskope Connect account for use with {$_COMPANY->val('companyname')}. Please follow the link provided below to verify your email address and reset your password.</p>
            <br/>
            <br/>
            <p><a href="{$url}">Password reset link</a></p>
            <br/>
            <br/>
            <p>The link expires in {$link_expiry_minutes} minutes.</p>
            <br/>
            <br/>
EOMEOM;
            $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
            $emesg = str_replace('#messagehere#', $message, $template);
            $emailStatus = $_COMPANY->emailSendExternal('Teleskope Connect', $this->val('external_email'), $subject, $emesg, 'TELESKOPE', '');
            if ($emailStatus) {
                $retVal['message'] = 'Success';
                $retVal['status'] = 1;
            } else {
                $retVal['message'] = 'Unable to email password reset code';
            }
        }
        return $retVal;
    }

    /**
     * Cleans email_confirmationcode as a result of Connect User Email verification.
     * @param string $emailVerificationCode
     * @return int
     */
    public function verifyEmail(string $emailVerificationCode, string $externalid): int
    {
        global $_COMPANY;

        if ($this->val('email_verification_attempts') >= self::MAX_EMAIL_VERIFICATION_ATTEMPTS) {
            return -2;
        }

        if (
            empty($emailVerificationCode) ||
            empty($externalid) ||
            ($teleskopeUser = User::GetUserByExternalId($externalid)) == NULL ||
            ($teleskopeUser->id() != $this->val('userid'))
        ) {
            // Increment the email_verification_attempts
            self::DBMutate("UPDATE user_connect SET email_verification_attempts=email_verification_attempts+1 WHERE `companyid`={$_COMPANY->id()} AND `connectid`={$this->id}");
            return -1;
        }

        $retVal = self::DBMutatePS("UPDATE user_connect SET email_verification_code=null,`email_verifiedon`=NOW(),email_verification_attempts=0,lastlogin_at=NOW() WHERE `companyid`=? AND `connectid`=? AND email_verification_code=?", "iix", $_COMPANY->id(), $this->id, $emailVerificationCode);

        if ($retVal && empty($teleskopeUser->val('external_email'))) {
            $teleskopeUser->updateExternalEmailAddress($this->val('external_email'));
        }

        return $retVal;
    }

    public function isEmailVerified()
    {
        return empty($this->val('email_verification_code')) &&
            (strtotime($this->val('email_verifiedon')) + (86400*self::MAX_EMAIL_VERIFICATION_VALID_DAYS) > time() );
    }

    public function sendMobileVerificationCode(): bool
    {
        return false;
    }

    public function verifyMobile(string $mobileVerificationCode): int
    {
        global $_COMPANY;
        return 0;
//        return
//            empty($mobileVerificationCode) ?
//                0 :
//                self::DBMutatePS("UPDATE user_connect SET mobile_verification_code='',`mobile_verified`=1,`modifiedon`=NOW() WHERE `companyid`=? AND `connectid`=? AND mobile_verification_code=?", "iix", $_COMPANY->id(), $this->id, $mobileVerificationCode);
    }

    public function isPhoneVerified()
    {
        return empty($this->val('mobile_verification_code')) &&
            (strtotime($this->val('mobile_verifiedon')) > time() + (86400*self::MAX_MOBILE_VERIFICATION_VALID_DAYS));
    }


    public function updateConnectUserPersonalEmail(string $app_type, string $external_email): bool
    {
        global $_COMPANY;

        if ($_COMPANY->isValidEmail($external_email)) { // Cannot connect using valid company emails, email should be external
            return false;
        }

        $retVal = self::DBUpdatePS("UPDATE `user_connect` SET `external_email`=?,`email_verifiedon`=NULL, `modifiedon`=NOW() WHERE `companyid`=? AND `connectid`=?", "xii", $external_email, $_COMPANY->id(), $this->id);
        if ($retVal) {
            return $this->sendEmailVerificationCode($app_type, true);
        }
        return false;
    }
}