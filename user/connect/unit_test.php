<?php
exit();
//include_once __DIR__.'/../head.php';
//include_once __DIR__.'/../../include/UserConnect.php';
//
//$_COMPANY = Company::GetCompanyBySubdomain('gmail2');
//
//function e(string $s, bool $line=false) { echo "<p>{$s}</p>".($line? '<hr>' : '');}
//function p($p) { echo '<p><pre>'.print_r($p).'</pre></p>';}
//
//
////// Step 1
//$userid = 153780;
//$email  = 'asbrar@yahoo.com';
//$deleteFirst = true;
//if ($deleteFirst) {
//    $delUser = UserConnect::GetConnectUserByEmail($email);
//    if ($delUser) {
//        $delUser->deleteConnectUser();
//        e('Deleted connect user');
//    }
//}
//$connectUser = UserConnect::GetConnectUserByTeleskopeUserid($userid);
//if (!$connectUser) {
//    $connectid = UserConnect::AddConnectUser($userid, $email,'');
//    $connectUser = UserConnect::GetConnectUser($connectid);
//    $connectUser->sendEmailVerificationCode($_ZONE->val('app_type'));
//    $connectUser->sendMobileVerificationCode();
//    e('Invited user, got connectid='.$connectid. ' .... did you get email?');
//}
//e("Processing user [userid:{$userid}], [email:{$email}], [connectid:{$connectUser->id()}]", true);
//
//
////$connectUser->sendEmailVerificationCode($_ZONE->val('app_type'), true);
////$connectUser->generateAndSendPasswordResetCode();
////$connectUser->verifyEmail('375721');
//$pass = 'AbAbAb1a4&';
//$old_pass = 'AbAbAb1a4&';
//e('external_email: '. $connectUser->val('external_email'));
//e('email_verified: '. $connectUser->isEmailVerified());
//e('email_verification_code: '. $connectUser->val('email_verification_code'));
//e('email_verifiedon'.$connectUser->val('email_verifiedon'));
//e('password: '. $connectUser->val('password'));
//e('password_reset_code: '. $connectUser->val('password_reset_code'));
//e('password_expiry_date: '. $connectUser->val('password_expiry_date'));
//e('failed_login_attempts: '. $connectUser->val('failed_login_attempts'));
//e('last_N_passwords: ');
//p(explode(',',$connectUser->val('last_N_passwords')));
//e('',true);
//
////p($connectUser->updateConnectPassword($pass, "852675"));
////p($connectUser->changePassword('AbAbAb1a4&', $old_pass));
////p($connectUser->login($pass));
//echo file_get_contents('/var/tmp/mail.html');
//exit();
//
//
