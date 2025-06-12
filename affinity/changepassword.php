<?php
require_once __DIR__.'/head.php';
$banner	  = $_ZONE->val('banner_background');
if (isset($_POST['submit'])){
	$oldPassword	= 	$_POST['oldpassword'];
	$newPassword	= 	$_POST['newpassword'];
	$confirm	= 	$_POST['confirmpassword'];
	
	if ($newPassword!=$confirm){
		$err = gettext("New password and confirm password do not match! Try again");
	} elseif ($oldPassword == $newPassword){
		$err = gettext("New password can't be the same as old password");
	} elseif (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\-\ \!\@\#\$\%\^\&\_])[a-zA-Z\d\-\ \!\@\#\$\%\^\&\_]{8,}$/', $newPassword)){
		$userid = $_USER->id();
		$data = $db->get("select password from users where userid='".$userid."' and isactive='1'");

		if(count($data)>0 && password_verify($oldPassword,$data[0]['password'])) {
			$_USER->updatePassword($newPassword);
			$done = gettext("Password changed successfully");
		} else {
			$err = gettext("Incorrect old password provided! Please try again");
		} 
	} else {
		$err = gettext("New password must be a minimum of 8 characters long and must contain an uppercase letter, a lowercase letter, a number and a special character");
	}
}
include(__DIR__ . '/views/header_html.php');
include(__DIR__ . '/views/changepassword.php');
include(__DIR__ . '/views/footer_html.php');
?>
