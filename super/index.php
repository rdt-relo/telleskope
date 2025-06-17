<?php
define ('INDEX_PAGE',1);
require_once __DIR__.'/head.php';

$db	= new Hems();
unset($_SESSION['companyid']);

// Set timezone and perform IE11 check
if (isset($_GET['timezone']) && isset($_GET['ie11'])) {
	$tz = $_GET['timezone'];
	if ($tz == "undefined" ||
		!isValidTimeZone($tz)) {
		$tz = "";
	}
	if (isset($_GET['ie11'])) {
		$ie11 = ($_GET['ie11'] === "true");
	} else {
		$ie11 = false; //default is false
	}
	$_SESSION['tz_b'] = $tz; // tz_b is used to store the timezone detected by browser
	$_SESSION['ie11'] = $ie11;
}
// If timezone or IE11 check not set, then get it from browser.
if (!isset($_SESSION['tz_b']) || !isset($_SESSION['ie11'])) {
	/* Get User Current Time Zone */
	echo '<script src="../vendor/js/jquery-3.5.1/dist/jquery.min.js"></script>
    <script src="../vendor/js/jstz-2.1.0/dist/jstz.min.js"></script>
    <script type="text/javascript">
        function isIE11() {
            return !!window.navigator.userAgent.match(/(MSIE|Trident)/);
        }
    </script>
    <script type="text/javascript">
        $(document).ready(function () {
            tz = jstz.determine().name();
            ie11 = isIE11();
            let glue = "?";
            if ((window.location.href).includes("?")) {
                glue = "&";
            }
            window.location.href += glue + "timezone=" + tz + "&ie11=" + ie11;
        });
    </script>';

}
// End of TZ and IE11 checks
elseif (isset($_SESSION['superid']) && isset($_SESSION['manage_companyids'])){
	header('location:manage.php');
	exit;
}

//Login Section
if (isset($_POST['submit'])){
	$email = raw2clean($_POST['email']);
	$password = $_POST['password'];
	$data = $_SUPER_ADMIN->super_get("SELECT superid, `password`,`expiry_date`,failed_login_attempts,google_auth_code,manage_companyids, `permissions` FROM `admin` WHERE email='{$email}' AND isactive='1'");

	if(!empty($data)) {
		$failed_login_attempts = (int)$data[0]['failed_login_attempts'];
		if (password_verify($password,$data[0]['password'])) {
			if ($failed_login_attempts >= 3){
				$error = "[Error 300]: Your account is blocked because you have entered wrong password 3 times. Please contact administrator to unblock your account!";
			} else {
				//$_SESSION['superid']=$data[0]['superid'];
				$_SESSION['verifyid']=$data[0]['superid'];
				$_SESSION['verify_manage_companyids']=$data[0]['manage_companyids'];
				$_SESSION['email'] = $email;
				$_SESSION['google_auth_code']=$data[0]['google_auth_code'];
				$_SESSION['google_auth_attempts']=0;
				$_SUPER_ADMIN->super_update("UPDATE `admin` SET failed_login_attempts=0 WHERE superid={$data[0]['superid']}");
				$daysRemained = ((int)strtotime($data[0]['expiry_date']) - time())/86400;
				$timezone = $_SESSION['tz_b'] ?: 'UTC';
				$formatedExpiryDate = $db->covertUTCtoLocalAdvance("l M j, Y \@ g:i a T","",  $data[0]['expiry_date'],$timezone);
				$_SESSION['password_expiry_date'] = array('expires_in_days'=>$daysRemained,'formated_expires_in_days'=>$formatedExpiryDate);
				header("location:google_auth");
				exit;
			}
		} else {
			$failed_login_attempts++;
			$_SUPER_ADMIN->super_update("UPDATE admin SET failed_login_attempts={$failed_login_attempts} WHERE superid={$data[0]['superid']}");
			$error = "[Error 100]: Could not find a valid Username & Password combination!";
		}
	}else{
		$error = "[Error 200]: Could not find a valid Username & Password combination!";
		//header("location:index.php?msg=".base64_encode(1)."");
	}
}

include(__DIR__ . '/views/index.html');
?>
