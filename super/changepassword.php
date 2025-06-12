<?php
require_once __DIR__.'/head.php';

$db	= new Hems();
unset($_SESSION['companyid']);

if (isset($_POST['submit'])){
	$oldPassword	= 	$_POST['oldPassword'];
	$newPassword	= 	$_POST['newPassword'];
	$confiorm		= 	$_POST['confiorm'];

	$data = $_SUPER_ADMIN->super_get("select password,last_five_passwords from admin where superid='".(int)$_SESSION['superid']."' and isactive='1'");
		
	if (!empty($data)>0 ){
		$last_five_passwords = explode(',',$data[0]['last_five_passwords']);
		$repeatedPassword = false;
		if (!empty($last_five_passwords)){
			foreach($last_five_passwords as $last_three_password){
				if (password_verify($newPassword,$last_three_password)) {
					$repeatedPassword = true;
					break;
				}
			}
		}
		if ($repeatedPassword){
			$err = "New password can't be the same as last three passwords!";
		} elseif (password_verify($oldPassword,$data[0]['password'])) {

			// Validate password strength
			$uppercase = preg_match('@[A-Z]@', $newPassword);
			$number    = preg_match('@[0-9]@', $newPassword);
			$specialChars = preg_match('/[~!@#$%^&*-]/', $newPassword);

			if ($newPassword!=$confiorm){
				$err = "New password and confirm password do not match! Try again";
			} elseif ( strlen($newPassword) < 8) {
				$err = "Password should be at least 8 characters in length";
			} elseif(!$uppercase){
				$err = "New password should include at least one upper case letter";
			}  elseif( !$number) {
				$err = "New password should include at least one number";
			} elseif( !$specialChars) {
				$err = "New password should include at least one special character, valid special characters are ~!@#$%^&*-";
			}else{
				$newPasswordHash = password_hash($newPassword,PASSWORD_BCRYPT);
				$last_five_passwords = array_slice($last_five_passwords, 0,4);
				$last_five_passwords[] = $data[0]['password'];
				$last_five_passwords = implode(',',$last_five_passwords);

				$update = $_SUPER_ADMIN->super_update("UPDATE admin SET `password`='".$newPasswordHash."',`modified`=NOW(),`expiry_date`=(NOW() + INTERVAL 90 DAY),`last_five_passwords`='{$last_five_passwords}' where superid='".(int)$_SESSION['superid']."'");
				$done = "Password changed successfully";
				$_SESSION['password_expiry_date']['expires_in_days'] = 90;
				$_SESSION['password_expiry_date']['formated_expires_in_days'] = '';
                Logger::Log("Super Admin: Change Password - For superid {$_SESSION['superid']}, password changed successfully", Logger::SEVERITY['INFO']);
			}
		} else {
			$err = "Incorrect old password provided! Please try again";
            Logger::Log("Super Admin: Change Password - For superid {$_SESSION['superid']}, incorrect old password provided", Logger::SEVERITY['INFO']);
		} 
	} else {
		$err = "Something went wrong. Please try again.";
	}
}
include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/changepassword.html');
include(__DIR__ . '/views/footer.html');

