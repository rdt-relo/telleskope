<?php
include_once __DIR__.'/head.php';

if (!empty($_SESSION['confirmation'])){ //User is awaiting confirmation
	header("location: confirmation");
	exit();
}
if (empty($_SESSION['domainverification'])){ //Domain not verified
	header("location: verification");
	exit();
}
if (empty($_SESSION['policy_accepted'])){ // Policy not accepted
  header("location: policyconsent");
  exit();
}
	
$timezone = @$_SESSION['timezone'];
$message = '';

if (isset($_POST['submit'])){

	$email				=	raw2clean($_POST['email']);
	$password			=	$_POST['password'];
	$firstname			=	Sanitizer::SanitizePersonName(raw2clean($_POST['firstname']));
	$lastname			=	Sanitizer::SanitizePersonName(raw2clean($_POST['lastname']));

	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Your email address is invalid';
    }
	elseif ($_COMPANY->isValidEmail($email) && ($email == $_SESSION['domainverification']) && ($firstname == raw2clean($_POST['firstname'])) && ($lastname == raw2clean($_POST['lastname']))){
        global $_ZONE;
        $_ZONE = $_COMPANY->getEmptyZone($_SESSION['app_type']);

		if(preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\-\ \!\@\#\$\%\^\&\_])[a-zA-Z\d\-\ \!\@\#\$\%\^\&\_]{8,}$/', $password)){
			$user = User::CreateNewUser($firstname,$lastname,$email,'',User::USER_VERIFICATION_STATUS['NOT_VERIFIED']);
            $user->updatePolicyAcceptedDate();
            unset($_SESSION['policy_accepted']); // Unset as the user might use the same session to login which will update the policy acceptance date
			if($user && $user->updatePassword($password) && $user->generateAndSendConfirmationCode()) {
				$_SESSION['confirmation'] = $user->id(); //Save the userid in confirmation code

                header("location:confirmation");
				exit();
			}else{
                $error_message = 'Error: User Account Provisioning Error';
			}
		}else{
			$error_message = 'Password must be minimum 8 characters long and must contain an uppercase letter, a lowercase letter, a number and a special character';
		}
	}else{
		$error_message = 'Error: Invalid Name, Email Address or Login URL';
	}
}

$title = 'Create a new account';
include __DIR__ . '/views/signup.html';

