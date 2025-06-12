<?php
echo "This feature is not available now.";
//This feature is not available now. We will delete this file soon.
die();

require_once __DIR__.'/head.php';
$pagetitle = "Add/Edit User";

// Authorization Check
if (!$_USER->canManageAffinitiesUsers()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}

$id = 0;
if(isset($_GET['userid'])){
	$id=$_COMPANY->decodeId($_GET['userid']);
}

if(isset($_POST['submit'])) {
    $firstName = raw2clean($_POST['firstName']);
	$lastName = raw2clean($_POST['lastName']);

	if ($id === 0) {
	    //Create new record or invite the user
        $email = trim(raw2clean($_POST['email']));

		if ($_COMPANY->isValidEmail($email)) {
			if (User::GetUserByEmail($email) === null) {

				if (0) { /* Commented on 12/27/22 by Aman as a result of removal of Company loginmethod column */
                    $confirmationCode = $db->codegenerate();
                    $password = $db->codegenerate2();
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                    $sql = "INSERT INTO `users` (`firstname`,`lastname`,`email`,`password`,`companyid`, `notification`,`createdon`,`modified`,`verificationstatus`,`confirmationcode`,`isactive`) VALUES ('{$firstName}','{$lastName}','{$email}','{$passwordHash}','{$_COMPANY->id()}','1',now(),now(),'2','{$confirmationCode}','1')";
                    $query = $db->insert($sql);

                    $subject 	= "{$_COMPANY->val('companyname')} {$_COMPANY->getAppCustomization()['group']['name-plural']} login credentials!";
                    $emesg = <<< EOMEOM
    <p>Hello {$firstName} {$lastName},</p>
    <br>
    <p>
    Congratulations, your {$_COMPANY->val('companyname')} {$_COMPANY->getAppCustomization()['group']['name']} account has been created
    successfully! Please login with your company email id and the temporary login password provided below. You can
    login into the application by following the link {$_COMPANY->getAppURL('affinities')} or from Teleskope Affinities
    mobile application. Once you login to your account please change your password from account settings.
    </p>
    <br>
    <p>Temporary Password: {$password}</p>
    <br>
    <p>Your email confirmation code is: {$confirmationCode}</p>
    <br>
    <br>
    <p>Enjoy!</p>
    <br>
    {$_COMPANY->val('companyname')} {$_COMPANY->getAppCustomization()['group']['name']} Administrator
EOMEOM;
                    $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
                    $emesg	= str_replace('#messagehere#',$emesg,$template);
                    $_COMPANY->emailSend2('', $email, $subject, $emesg, $_ZONE->val('app_type'),'');

					$_SESSION['added'] = time();
                    $success = "User '{$firstName} {$lastName}' added and login password has been emailed to '{$email}'";
				} else {
                    $subject 	= "You are invited to join {$_COMPANY->val('companyname')} {$_COMPANY->getAppCustomization()['group']['name-plural']}";
                    $emesg = <<< EOMEOM
    <p>Hello {$firstName} {$lastName},</p>
    <br>
    <p>
    You are invited to join {$_COMPANY->val('companyname')} {$_COMPANY->getAppCustomization()['group']['name']}. Joining is easy, all 
    you need to do is follow the link {$_COMPANY->getAppURL('affinities')} to create your account. 
    </p>
    <br>
    <br>
    <p>Enjoy!</p>
    <br>
    {$_COMPANY->val('companyname')} {$_COMPANY->getAppCustomization()['group']['name']} Administrator
EOMEOM;
                    $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
                    $emesg	= str_replace('#messagehere#',$emesg,$template);
                    $_COMPANY->emailSend2('', $email, $subject, $emesg, $_ZONE->val('app_type'),'');
                    $_SESSION['added'] = time();
                    $success = "User '{$firstName} {$lastName} ({$email})' has been invited to join the platform";
				}
			} else {
				$error = "Account already exists: " . $email;
			}
		} else {
			$error = "The email '{$email}' does not match the company domain settings";
		}

	} else {
        // Update Record
        $homeOffice = (!empty($_POST['homeOffice'])) ? $_COMPANY->decodeId($_POST['homeOffice']) : '';
        $department = (!empty($_POST['department'])) ? $_COMPANY->decodeId($_POST['department']) : '';
        $branch = (!empty($homeOffice)) ? $_COMPANY->getBranch($homeOffice) : null;
        $regionid = ($branch == null) ? 0 : $branch->val('regionid');
        $jobTitle = raw2clean($_POST['jobTitle']);

        // Start
        $user = User::GetUser($id);
        $file = basename(@$_FILES['picture']['name']);
        $update_picture = '';
        if ($file != "") {
            $size = $_FILES['picture']['size'];
            $tmp = $_FILES['picture']['tmp_name'];
            $ext = $db->getExtension($file);
            $update_picture = '';
            $valid_formats = array("jpg", "png", "jpeg", "PNG", "JPG", "JPEG");

            if (in_array($ext, $valid_formats)) {
                if ($size < (1024 * 1024)) {
                    $picture_name = 'profile_' . teleskope_uuid() . '_etag_'.generateRandomToken(10). '.' . $ext;
                    $update_picture = $_COMPANY->saveFile($tmp, $picture_name,'USER');
                } else {
                    $error = "Maximum allowed size of profile picture is 1MB";
                }
            } else {
                $error = "Only .jpg,.jpeg,.png files are allowed!";
            }
        }

        if (empty($error)) {
            $user->updateProfile2('',$firstName,  $lastName,  $jobTitle,  $department,  $homeOffice,  '',  '',  '',  $regionid,  '',  '',  '',  '', '',true, null, null, null);

            if ($update_picture){
                $user->updateProfilePicture($update_picture);
            }
            $_SESSION['updated'] = time();
            User::GetUser($id, true); // Refresh cache after loading from master DB
            Http::Redirect("manageusers");
        }
    }
} else {
    if ($id > 0){
        $users=$db->get("SELECT * FROM `users` WHERE `userid`='{$id}' AND `companyid`='{$_COMPANY->id()}'");
    }
	//Get All Branches of Company
	$branches = $db->get("SELECT * FROM `companybranches` WHERE `companyid`='{$_COMPANY->id()}' AND `isactive`='1'");
	//Get All Departments of Company
	$departments = $db->get("SELECT * FROM `departments` WHERE `companyid`='{$_COMPANY->id()}' AND `isactive`='1'");

}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/adduser.html');
