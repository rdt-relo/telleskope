<?php
require_once __DIR__.'/head.php';
$db	= new Hems();
$superDb	= new SuperAdminFunctions();
if (!empty($_GET['page'])){
	$page =	(int)$_GET['page'];
}else{
	$page =	1;	
}

//Block company
if (isset($_GET['block'])){
	$companyid 	= 	(int)base64_decode($_GET['block']);
	$today		= 	today();
	$update = "update companies set isactive='2',modified=now() where companyid='{$companyid}'";
	$query = $_SUPER_ADMIN->super_update($update);
	if ($query){
		$_SESSION['updated'] = time();
		header("location:manage");
	}else{
		$_SESSION['ERROR'] = time();
		header("location:manage");
	}
}

//UnBlock company
elseif (isset($_GET['unblock'])){
	$companyid 	= 	(int)base64_decode($_GET['unblock']);
	$today		= 	today();
	$update = "update companies set isactive='1',modified=now() where companyid='{$companyid}'";
	$query = $_SUPER_ADMIN->super_update($update);
	if ($query){
		$_SESSION['updated'] = time();
		header("location:manage");
	}else{
		$_SESSION['ERROR'] = time();
		header("location:manage");
	}
}

//Delete User
elseif (isset($_GET['deleteuser'])){
    /**
	$userid	= $_GET['deleteuser'];
	$delete1	= $_SUPER_ADMIN->update("DELETE FROM `session` WHERE `userid`='".$userid."'");
	$delete2	= $_SUPER_ADMIN->update("DELETE FROM `postcomments` WHERE `userid`='".$userid."'");
	$delete3	= $_SUPER_ADMIN->update("DELETE FROM `post` WHERE `userid`='".$userid."'");
	$delete4	= $_SUPER_ADMIN->update("DELETE FROM `groupleads` WHERE `userid`='".$userid."'");
	$delete5	= $_SUPER_ADMIN->update("DELETE FROM `eventjoiners` WHERE `userid`='".$userid."'");
	$delete6    = $_SUPER_ADMIN->update("DELETE FROM `users` WHERE `userid`='".$userid."'");
	$delete7    = $_SUPER_ADMIN->update("DELETE FROM `groupmembers` WHERE `userid`='".$userid."'");
	$delete8    = $_SUPER_ADMIN->update("DELETE FROM `notifications` WHERE `userid`='".$userid."'");
	//$delete     = $_SUPER_ADMIN->update("DELETE FROM `appusage` WHERE `userid`='".$userid."'");
	//print($delete);
     **/
}




//Delete App FAQ
elseif (isset($_GET['deleteMobFawSuper'])){
	Auth::CheckPermission(Permission::GlobalManageAppFaqs);

	$faqid	=	(int)$_GET['deleteMobFawSuper'];
	$delete = $_SUPER_ADMIN->super_update("delete from faqsmobile where faqid='".$faqid."'");
	print_r(1);
	
}
//Delete Admin FAQ
elseif (isset($_GET['deleteAdminFAQs'])){
	Auth::CheckPermission(Permission::GlobalManageAdminFaqs);

	$faqid	=	(int)$_GET['deleteAdminFAQs'];

	$delete = $_SUPER_ADMIN->super_update("delete from faqsadmin where faqid='".$faqid."'");
	print_r(1);
	
}

elseif (isset($_GET['checkCompanyName'])){
	
	$companyname = raw2clean($_GET['checkCompanyName']);
	$companyid	 = (int)$_GET['id'];
	$buildSubdomain = raw2clean($_GET['buildSubdomain']);
	if($companyname!="") {
        $check = $_SUPER_ADMIN->super_get("SELECT `companyname` FROM `companies` WHERE `companyname`='{$companyname}' AND companyid!='{$companyid}'");

        if (count($check)) {
            print 1;
            exit();
        } elseif ($buildSubdomain) {
            $subdomain = strtolower(str_replace(".", "", str_replace(" ", "", $companyname)));
            $unique = $_SUPER_ADMIN->super_get("SELECT `companyid` FROM `companies` WHERE `subdomain`='{$subdomain}' AND companyid != '{$companyid}'");
            if (count($unique) == 0) {
                echo $subdomain;
                exit();
            }
        }
    }
	print 2;
	exit();
}

elseif (isset($_GET['checkCompanyEmail'])){
	
	$email 		= $_GET['checkCompanyEmail'];
	$companyid 	= $_GET['id'];
	
	if(filter_var($email, FILTER_VALIDATE_EMAIL)){
		
		$check = $_SUPER_ADMIN->super_get("SELECT `email` FROM `companies` WHERE `email`='".$email."' and companyid!='".$companyid."'");
		
		if (count($check)){
			print 1;
		}else{
			$check = $_SUPER_ADMIN->super_get("SELECT `email` FROM `companies` WHERE FIND_IN_SET('".explode("@",$email)[1]."',domain)");
			
			if (count($check)){
				print 4;
			}else{
				$subdomain=explode(".",explode("@",$email)[1])[0];
				
				$unique = $_SUPER_ADMIN->super_get("SELECT `companyid` FROM `companies` WHERE `subdomain`='{$subdomain}'");
				
				if(count($unique)){
					print 2;
				}else{
					echo htmlspecialchars($subdomain);
				}
			}
		}
	}else{
		print 3;
	}
}

elseif (isset($_GET['checkSubdomain'])){
	
	$subdomain = raw2clean($_GET['checkSubdomain']);
	$unique = $_SUPER_ADMIN->super_get("SELECT `companyid` FROM `companies` WHERE `subdomain`='{$subdomain}'");
	if(count($unique)){
		print 2;
	}else{
		echo 1;
	}
}
elseif (isset($_GET['updateEmailSetting']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	Auth::CheckPermission(Permission::ManageEmailSettings);

	$error = "";

	if($_POST['email_protocol'] == 1){
		$error = $db->checkRequired(array(' SMTP From Email'=>$_POST['smtp_from_email'],' SMTP Replyto Email'=>$_POST['smtp_replyto_email'],' SMTP Host'=>$_POST['smtp_host'],' SMTP Port'=>$_POST['smtp_port'],' SMTP Username'=>$_POST['smtp_username']));
		
	}else{
		$error = $db->checkRequired(array(' IMAP Host'=>$_POST['imap_host'],' IMAP Port'=>$_POST['imap_port'],' IMAP Username'=>$_POST['imap_username']));
	} 

	if ($error){
		echo $error." can't be empty!";
	} else {
		if ($_SESSION['companyid']){
			$check = $_SUPER_ADMIN->super_get("SELECT`companyid` FROM `company_email_settings` WHERE `companyid`='{$_SESSION['companyid']}'");
			if (count($check)){
				if ($_POST['email_protocol'] ==1) {
					$custom_smtp = 1;
					$custom_imap = 0;
					$_SUPER_ADMIN->super_update("UPDATE `company_email_settings` SET `custom_smtp`='".$custom_smtp."', `custom_imap`='".$custom_imap."', `smtp_from_email`='".raw2clean($_POST['smtp_from_email'])."', `smtp_replyto_email`='".raw2clean($_POST['smtp_replyto_email'])."', `smtp_host`='".raw2clean($_POST['smtp_host'])."', `smtp_port`='".raw2clean($_POST['smtp_port'])."', `smtp_username`='".raw2clean($_POST['smtp_username'])."', `smtp_secure`='".raw2clean($_POST['smtp_secure'])."', `modifiedon`=NOW() WHERE `companyid`='".$_SESSION['companyid']."'");
					
				} else {
					$custom_smtp = 0;
					$custom_imap = 1;
					$_SUPER_ADMIN->super_update("UPDATE `company_email_settings` SET `custom_smtp`='".$custom_smtp."', `custom_imap`='".$custom_imap."',`imap_host`='".raw2clean($_POST['imap_host'])."',`imap_port`='".raw2clean($_POST['imap_port'])."',`imap_username`='".raw2clean($_POST['imap_username'])."',`modifiedon`=NOW() WHERE `companyid`='".$_SESSION['companyid']."'");			}

			} else {
				if ($_POST['email_protocol'] ==1) {
					$custom_smtp = 1;
					$custom_imap = 0;
					$_SUPER_ADMIN->super_insert("INSERT INTO `company_email_settings`(`companyid`, `custom_smtp`, `custom_imap`, `smtp_from_email`, `smtp_replyto_email`, `smtp_host`, `smtp_port`, `smtp_username`, `smtp_secure`, `createdon`, `modifiedon`, `isactive`) VALUES ('".$_SESSION['companyid']."','".$custom_smtp."','".$custom_imap."','".raw2clean($_POST['smtp_from_email'])."','".raw2clean($_POST['smtp_replyto_email'])."','".raw2clean($_POST['smtp_host'])."','".raw2clean($_POST['smtp_port'])."','".raw2clean($_POST['smtp_username'])."','".raw2clean($_POST['smtp_secure'])."',NOW(),NOW(),'1')");
					
				} else {
					$custom_smtp = 0;
					$custom_imap = 1;
					$_SUPER_ADMIN->super_insert("INSERT INTO `company_email_settings`(`companyid`, `custom_smtp`, `custom_imap`, `imap_host`, `imap_port`, `imap_username`, `createdon`, `modifiedon`, `isactive`) VALUES ('".$_SESSION['companyid']."','".$custom_smtp."','".$custom_imap."','".raw2clean($_POST['imap_host'])."','".raw2clean($_POST['imap_port'])."','".raw2clean($_POST['imap_username'])."',NOW(),NOW(),'1')");
					
				}
			}
			echo 1;
		} else {
			echo "Unauthorized access";
		}
	}

}
elseif (isset($_GET['updateEmailSettingPasssword']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	Auth::CheckPermission(Permission::ManageEmailSettings);
	
	$cid = (int) $_SESSION['companyid'];
	$_COMPANY = Company::GetCompany($cid);

	$password = CompanyEncKey::Encrypt($_POST['password']);

	if ($_SESSION['companyid']){
		$check = $_SUPER_ADMIN->super_get("SELECT`companyid` FROM `company_email_settings` WHERE `companyid`='{$_SESSION['companyid']}'");
		if (count($check)){
			if ($_POST['email_protocol'] ==1) {			
				$_SUPER_ADMIN->super_update("UPDATE `company_email_settings` SET `smtp_password`='".$password."' WHERE `companyid`='".$_SESSION['companyid']."' ");
			} else {			
				$_SUPER_ADMIN->super_update("UPDATE `company_email_settings` SET `imap_password`='".$password."' WHERE `companyid`='".$_SESSION['companyid']."' ");
			}
		} else {
			if ($_POST['email_protocol'] ==1) {				
				$_SUPER_ADMIN->super_insert("INSERT INTO `company_email_settings`(`companyid`,`smtp_password`,`createdon`, `modifiedon`, `isactive`) VALUES ('".$_SESSION['companyid']."','".$password."',NOW(),NOW(),'1')");
				
			} else {
				$_SUPER_ADMIN->super_insert("INSERT INTO `company_email_settings`(`companyid`,`imap_password`,`createdon`, `modifiedon`, `isactive`) VALUES ('".$_SESSION['companyid']."','".$password."',NOW(),NOW(),'1')");
			}
		}
		echo 1;
	} else {
		echo "Unauthorized access";
	}
}

elseif (isset($_GET['setCurrentAppVersion']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	Auth::CheckPermission(Permission::GlobalManageAppVersions);
		
	$id = base64_decode($_POST['id']);
	$status = (int)$_POST['status'];
	if ($id){
		$_SUPER_ADMIN->super_update("UPDATE `app_versions` SET isactive= '".$status."' WHERE `id`='".$id."' ");
		echo 1;
	} else {
		echo 0;
	}
}
elseif (isset($_GET['sendSystemMessage'])){
	Auth::CheckPermission(Permission::GlobalSystemMessaging);

	$message_id 	= 	(int)base64_decode($_GET['sendSystemMessage']);
	$data = $_SUPER_ADMIN->super_get("SELECT `message_id`, `message_type`, `recipients`, `subject`, `message`, `recipient_type`, `createdon`, `updatedon`, `status` FROM `system_messages` WHERE `message_id`='".$message_id."' AND `status`=2");
	if (count($data)>0){
		$from_array = ['','Teleskope System Update','Teleskope Product Update','Teleskope Incident Management'];
		$recipients = $data[0]['recipients'];
		$subject = $data[0]['subject'];
		$message = htmlspecialchars_decode($data[0]['message']);
		$from = $from_array[$data[0]['message_type']];
		$recipients_array = explode(',', $recipients);
		foreach ($recipients_array as $email) {
			$superDb->superEmail($from, $email, $subject, $message);
		}
		$_SUPER_ADMIN->super_update("UPDATE `system_messages` SET `updatedon`=NOW(),`status`='1' WHERE `message_id`='".$data[0]['message_id']."' AND `status`=2");

		$_SESSION['sent'] = time();
		header("location:system_messages");
	}else{
		$_SESSION['error']  = time();
		$_SESSION['error_message'] = "Unable to find the message";
		header("location:system_messages");
	}
}


elseif (isset($_GET['activateZone']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
		
	$zoneid = base64_decode($_GET['activateZone']);
	
	if ($zoneid){
		$_SUPER_ADMIN->super_update("UPDATE `company_zones` SET`isactive`='1' WHERE `companyid`='{$_SESSION['companyid']}'  AND (`zoneid`='{$zoneid}')");
		
		$_SESSION['updated'] = time();
		Company::GetCompany($_SESSION['companyid'], true);
		header("location: manage_zones");
	} else {
		$_SESSION['error'] = time();
		header("location: manage_zones");
	}
}
elseif (isset($_GET['deactivateZone']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
	Auth::CheckPermission(Permission::ManageZones);
		
	$zoneid = base64_decode($_GET['deactivateZone']);
	
	if ($zoneid){
		$_SUPER_ADMIN->super_update("UPDATE `company_zones` SET`isactive`='2' WHERE `companyid`='{$_SESSION['companyid']}'  AND (`zoneid`='{$zoneid}')");
		
		$_SESSION['updated'] = time();
		header("location: manage_zones");
	} else {
		$_SESSION['error'] = time();
		header("location: manage_zones");
	}
}
elseif (isset($_GET['deleteZone']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
	Auth::CheckPermission(Permission::ManageZones);

	$zoneid = base64_decode($_POST['zoneid']);
	
	$_COMPANY = Company::GetCompany($_SESSION['companyid']); // temporary use
		
	$zone = Zone::GetZone($zoneid);
	$reason = $zone->getWhyCannotDeleteIt();

	if ($reason) {
		AjaxResponse::SuccessAndExit_STRING(
			0,
			'',
			$reason,
			gettext('Error')
		);
	}

	$zone->deleteIt();

	$_COMPANY = null;//It was instantiated for temporary use only.

	AjaxResponse::SuccessAndExit_STRING(
		1,
		'',
		gettext('Zone deleted successfully.'),
		gettext('Success')
	);
}
elseif (isset($_GET['deleteCompany']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
	Auth::CheckPermission(Permission::DeleteCompany);

	$companyid = base64_decode($_POST['companyid']);

	Auth::CheckManageCompany($companyid);

	$company = Company::GetCompany($companyid);

	if (!$company) {
		AjaxResponse::SuccessAndExit_STRING(
			0,
			'',
			'Approve the company first before deleting it',
			gettext('Error')
		);
	}
	$reason = $company->getWhyCannotDeleteIt();

	if ($reason) {
		AjaxResponse::SuccessAndExit_STRING(
			0,
			'',
			$reason,
			gettext('Error')
		);
	}

	$company->deleteIt();

	AjaxResponse::SuccessAndExit_STRING(
		1,
		'',
		gettext('Company deleted successfully.'),
		gettext('Success')
	);
}
elseif (isset($_GET['loadLoginSettingForm']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
		
	Auth::CheckPermission(Permission::ManageLoginMethods);

	$type = $_GET['type'];
	$id = base64_decode($_GET['id']);
	$row = array();

	if ($id>0){
		$data = $_SUPER_ADMIN->super_get("SELECT * FROM `company_login_settings` WHERE `companyid`='{$_SESSION['companyid']}' AND `settingid`='{$id}'");
		if (count($data)){
			$row = $data[0];
			$attributes = json_decode($row['attributes'],true) ?? array(); // json2Array($row['attributes']);
			$row = array_merge($row,$attributes);
		}
	}

	if ($type=='saml2'){
		if (!$id) {
			$companyInContext = Company::GetCompany($_SESSION['companyid']);
			$row['login_btn_label'] = 'Login using SAML Single Sign-on';
			$row['login_btn_description'] = $companyInContext->val('companyname').' uses SAML2 Single Sign-on. To login, click on the above button and use your company email if requested.';
			$row['login_silently'] = 1;
			$row['use_affinities_identity'] = 0;
		}
		include(__DIR__ . '/views/login_method_common.html');
		include(__DIR__ . '/views/login_method_saml2.html');
	} elseif ($type=='microsoft'){
		if (!$id) {
			$companyInContext = Company::GetCompany($_SESSION['companyid']);
			$row['login_btn_label'] = 'Login using Microsoft Single Sign-on';
			$row['login_btn_description'] = $companyInContext->val('companyname').' uses Microsoft Single Sign-on. To login, click on the above button and use your company email if requested.';
			$row['login_silently'] = 1;
			$row['authenticator_version'] = 2;
		}
		include(__DIR__ . '/views/login_method_common.html');
		include(__DIR__ . '/views/login_method_o365.html');
	} elseif($type=='connect') {
		if (!$id) {
			$row['login_btn_label'] = 'Sign In';
			$row['login_btn_description'] = '';
		}
		include(__DIR__ . '/views/login_method_common.html');
	} elseif($type=='otp') {
		if (!$id) {
			$row['login_btn_label'] = 'Sign In';
			$row['login_btn_description'] = '';
		}
		include(__DIR__ . '/views/login_method_common.html');
		include(__DIR__ . '/views/login_method_otp.html');
	} elseif($type=='username') {
		if (!$id) {
			$row['login_btn_label'] = 'Sign In';
			$row['login_btn_description'] = '';
		}
		include(__DIR__ . '/views/login_method_common.html');
	}else{
		echo 1;
	}
	
}
elseif (isset($_GET['deactivateReleaseNote']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
	Auth::CheckPermission(Permission::GlobalManageReleaseNotes);

	$id = base64_decode($_GET['deactivateReleaseNote']);
	
	$update = $_SUPER_ADMIN->super_update("UPDATE `release_notes` SET isactive=2,`modifiedon`=NOW() WHERE `releaseid`='".$id."' ");
	$_SESSION['updated'] = time();
	header("Location:manage_release_notes");
}
elseif (isset($_GET['activeReleaseNote']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
	Auth::CheckPermission(Permission::GlobalManageReleaseNotes);

	$id = base64_decode($_GET['activeReleaseNote']);
	
	$update = $_SUPER_ADMIN->super_update("UPDATE `release_notes` SET isactive=1,`modifiedon`=NOW() WHERE `releaseid`='".$id."' ");
	$_SESSION['updated'] = time();
	header("Location:manage_release_notes");
}
elseif (isset($_GET['deleteReleaseNote']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
	Auth::CheckPermission(Permission::GlobalManageReleaseNotes);

	$id = base64_decode($_GET['deleteReleaseNote']);
	
	$update = $_SUPER_ADMIN->super_update("UPDATE `release_notes` SET isactive=0,`modifiedon`=NOW() WHERE `releaseid`='".$id."' ");
	$_SESSION['updated'] = time();
	header("Location:manage_release_notes");
}
elseif (isset($_GET['uplodeGuideFile']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	Auth::CheckPermission(Permission::GlobalManageGuides);

	//Instantiate the client.
	$s3 = Aws\S3\S3Client::factory([
		'version' => 'latest',
		'region' => S3_REGION

	]); 

	$s3_folder	=	'teleskope/guides';
	$actual_name = '';
	if (isset($_POST['section'])){
		$section = $_POST['section'];
		if ($section  == 1) {
			$actual_name = 'quick_start_guide.pdf';
		} else if ($section == 2){
			$actual_name = 'group_lead_guide.pdf';
		} else if ($section == 3 ){
			$actual_name = 'admin_guide.pdf';
		} else if ($section == 4 ){
			$actual_name = 'talent_peak_quick_start_guide.pdf';
		} else if ($section == 5 ){
			$actual_name = 'talent_peak_group_lead_guide.pdf';
		}else if ($section == 6 ){
			$actual_name = 'office_raven_quick_start_guide.pdf';
		}else if ($section == 7 ){
			$actual_name = 'office_raven_group_lead_guide.pdf';
		} else {
			echo 1;
			exit();
		}
	}
	
	if(!empty($_FILES['file']['name']) && $_FILES['file']['type']=="application/pdf"){
		$file 	   		=	basename($_FILES['file']['name']);
		$size 			= 	$_FILES['file']['size'];
		$tmp 			=	$_FILES['file']['tmp_name'];
		$finfo = new finfo(FILEINFO_MIME_TYPE);
        $contentType = $finfo->file($tmp);
		try{
			$s3->putObject([
			'Bucket'=>S3_BUCKET,
			'Key'=>$s3_folder."/".$actual_name,
			'Body'=>fopen($tmp,'rb'),
			'ACL'=>'public-read',
			'ContentType' => $contentType
			]);
			echo "https://".S3_BUCKET.".s3.amazonaws.com/".$s3_folder."/".$actual_name;
			exit();
		}catch(S3Exception $e){
			echo 0;
			exit();
		}	
	} else {
		echo 2;
	}
}

elseif (isset($_GET['expireLoginMethodCache'])) {
	Auth::CheckPermission(Permission::ManageLoginMethods);

	$temp_company = Company::GetCompany($_SESSION['companyid']);
	$key = "LOGINMETHODS:affinities";
	$retVal = (int)(
		$temp_company->expireRedisCache("LOGINMETHODS:affinities") &&
		$temp_company->expireRedisCache("LOGINMETHODS:officeraven") &&
		$temp_company->expireRedisCache("LOGINMETHODS:talentpeak") &&
		$temp_company->expireRedisCache("LOGINMETHODS:peoplehero") &&
		$temp_company->expireRedisCache("LOGINMETHODS:teleskope")
	);
	echo $retVal;
	exit();
}

elseif (isset($_GET['deleteLoginMethod']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	Auth::CheckPermission(Permission::ManageLoginMethods);

	$settingid = (int)base64_decode($_POST['settingid']);
	$retVal = $_SUPER_ADMIN->super_update("DELETE FROM company_login_settings WHERE `companyid`='{$_SESSION['companyid']}' AND `settingid`='{$settingid}'");
	echo $retVal;
	exit();
}

elseif (isset($_GET['activateLoginMethod']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	Auth::CheckPermission(Permission::ManageLoginMethods);

	$settingid = (int)base64_decode($_POST['settingid']);
	$action = intval($_POST['action'] == 1 ? $_POST['action'] : 2);
	$action_allowed = true;

	$row1 = $_SUPER_ADMIN->super_get("SELECT loginmethod,scope FROM company_login_settings WHERE '{$_SESSION['companyid']}' AND settingid='{$settingid}'");
	if ($action == 1) {
		if (!empty($row1)) {
			if (($row1[0]['loginmethod'] === 'username') || ($row1[0]['loginmethod'] === 'microsoft')) {
				// If the method type is username then there can be only one active method
				$already_active = $_SUPER_ADMIN->super_get("SELECT count(1) as already_active FROM company_login_settings WHERE companyid='{$_SESSION['companyid']}' AND loginmethod='{$row1[0]['loginmethod']}' AND scope='{$row1[0]['scope']}' AND isactive=1")[0]['already_active'];
				$action_allowed = $already_active == 0;
				$retVal = -1;
			} elseif ($row1[0]['loginmethod'] === 'connect') {
				$c = $_SUPER_ADMIN->super_get("SELECT connect_attribute FROM companies WHERE companyid='{$_SESSION['companyid']}'");
				if (empty($c) || empty($c[0]['connect_attribute'])) {
					$action_allowed = false;
					$retVal = -2;
				}
			}
		}
	}

	if ($action_allowed) {
		$_SUPER_ADMIN->super_update("UPDATE company_login_settings SET isactive='{$action}' WHERE `companyid`='{$_SESSION['companyid']}' AND `settingid`='{$settingid}'");
		$retVal = 1;
	}
	echo $retVal;
	exit();
}

elseif (isset($_GET['changeCompanyServiceMode']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	if (!isset($_POST['companyid']) ||
		($companyid = (int) base64_decode($_POST['companyid'])) <0 ||
		($company = Company::getCompany($companyid)) === NULL ||
		($mode = (int)base64_decode($_POST['mode'])) > 2	
	){
		echo 0;
		exit();
	}

	Auth::CheckPermission(Permission::ChangeCompanyServiceMode);

	if ($_SUPER_ADMIN->super_update("UPDATE `companies` SET `modified`=NOW(),`in_maintenance`='{$mode}' WHERE `companyid`='{$companyid}'")){
		echo 1;
		die();
	}else{
		echo 2;
		exit();
	}
	exit();
}

elseif (isset($_GET['changeEaiCredentialStatus']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	Auth::CheckPermission(Permission::ManageEaiAccounts);

	if (($id = (int) base64_decode($_POST['id'])) <1 ||
		($status = (int)base64_decode($_POST['status'])) < 0
	){
		echo 0;
		exit();
	}
	if ($status == 100){
		$_SUPER_ADMIN->super_update("DELETE FROM `eai_accounts` WHERE  `companyid`='".$_SESSION['companyid']."' AND `accountid`='".$id."'");
	} else {
		$_SUPER_ADMIN->super_update("UPDATE `eai_accounts` SET `isactive`='".$status."',`modifiedon`=NOW() WHERE `companyid`='".$_SESSION['companyid']."' AND `accountid`='".$id."'");
	}
	echo 1;
	die();
}

elseif (isset($_GET['resetEaiCredentialPassword']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	Auth::CheckPermission(Permission::ManageEaiAccounts);

	if (($id = (int) base64_decode($_POST['id'])) <1){
		echo 0;
		exit();
	}

	$password = randomPasswordGenerator(36);
	$passwordhash = password_hash($password, PASSWORD_BCRYPT);

	if ($_SUPER_ADMIN->super_update("UPDATE `eai_accounts` SET `passwordhash`='".$passwordhash."',`failed_logins`='0',`modifiedon`=NOW() WHERE `companyid`='".$_SESSION['companyid']."' AND `accountid`='".$id."'")){
		echo $password;
		die();
	}else{
		echo 0;
		exit();
	}
}

elseif (isset($_GET['getRandomPassword']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
	echo randomPasswordGenerator(36);
	exit();
}

elseif (isset($_GET['deleteScheduledJob']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	Auth::CheckPermission(Permission::ManageScheduledJobs);

	if (!isset($_POST['jobid']) ||
		($jobid = base64_decode($_POST['jobid'])) <0
	){
		echo 0;
		exit();
	}
	Job::DeleteScheduledJob($_SESSION['companyid'], $jobid);

	echo 1;
	exit();
}

elseif (isset($_GET['rerunScheduledJob']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	Auth::CheckPermission(Permission::ManageScheduledJobs);

	if (!isset($_POST['jobid']) ||
		($jobid = base64_decode($_POST['jobid'])) <0
	){
		echo 0;
		exit();
	}
	Job::RerunScheduledJob($_SESSION['companyid'], $jobid);

	echo 1;
	exit();
}
elseif (isset($_GET['deleteDomain']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
  Auth::CheckPermission(Permission::ManageDomains);

  if (!isset($_POST['domain_id']) ||
    ($domain_id = base64_decode($_POST['domain_id'])) <0
  ){
    echo 0;
    exit();
  }

  $_SUPER_ADMIN->super_update("DELETE FROM `company_email_domains` WHERE  `companyid`='".$_SESSION['companyid']."' AND `domain_id`= ".$domain_id);
  echo 1;
  exit();
}

elseif (isset($_GET['saveSystemMessage']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	Auth::CheckPermission(Permission::GlobalSystemMessaging);

	$checkRequired = $db->checkRequired(array('Message Type'=>$_POST['message_type'],' Subject'=>$_POST['subject'],' Message'=>$_POST['message']));
	if ($checkRequired){
		AjaxResponse::SuccessAndExit_STRING(0, '', $checkRequired." can't be empty!", 'Error!');
	}
	$valid_recipent_types = ['Business','Technical','Security','CompanyAdmins','ZoneAdmins', 'ProductUpdate', 'TrainingUpdate', 'WebinarUpdate'];
	$message_id        = (int) (base64_decode($_POST['message_id']));
    $message_type   = (int)$_POST['message_type'];
    $subject        = raw2clean($_POST['subject']);
	$message = mysqli_real_escape_string($dbrw_conn, $_POST['message']); // $conn is global
	$template = mysqli_real_escape_string($dbrw_conn, $_POST['template']);

	$recipient_type = array();
    foreach ($_POST['recipient_type'] as $item){
        // Validate inputs against valid values.
        if (in_array($item,$valid_recipent_types)) {
            $recipient_type[] = $item;
        }
    }

    if (! empty($recipient_type)){
        $recipients = [];
        for($i=0;$i<count($recipient_type);$i++){
            if ($recipient_type[$i] =='CompanyAdmins'){
                $emails = $_SUPER_ADMIN->super_get("SELECT users.email FROM `company_admins` JOIN users ON users.userid=company_admins.`userid` JOIN companies ON companies.companyid= company_admins.companyid WHERE users.isactive=1 AND companies.isactive=1 AND company_admins.zoneid=0");
            } elseif($recipient_type[$i] == 'ZoneAdmins'){
                $emails = $_SUPER_ADMIN->super_get("SELECT users.email FROM `company_admins` JOIN users ON users.userid=company_admins.`userid` JOIN companies ON companies.companyid= company_admins.companyid WHERE users.isactive=1 AND companies.isactive=1 AND company_admins.zoneid!=0");
            } elseif($recipient_type[$i] == 'ProductUpdate'){
                $emails = TeleskopeMailingList::GetProductMailingList();
            } elseif($recipient_type[$i] == 'TrainingUpdate'){
                $emails = TeleskopeMailingList::GetTrainingMailingList();
            }elseif($recipient_type[$i] == 'WebinarUpdate'){
                $emails = TeleskopeMailingList::GetWebinarMailingList();
            } else {
                $emails = $_SUPER_ADMIN->super_get("SELECT `email` FROM `company_contacts` WHERE `contactrole` = '".$recipient_type[$i]."' ");
            }

			$recipients = array_merge($recipients, array_column($emails,'email'));
        }
		$recipients = array_filter(array_unique($recipients));

        $recipient_type = implode(',',$recipient_type);
        if (count($recipients)) {
            $recipient_str = implode(',', $recipients);
			if ($message_id){
				$success = $_SUPER_ADMIN->super_update("UPDATE `system_messages` SET `message_type`='" . $message_type . "',`recipients`='" . $recipient_str . "',`subject`='" . $subject . "',`message_template`='".$template."',`message`='" . $message . "',`recipient_type`='" . $recipient_type . "',`updatedon`=NOW() WHERE `message_id`='" . $message_id . "' AND `status`=2");
            
			} else {
            	$success = $_SUPER_ADMIN->super_insert("INSERT INTO `system_messages`(`message_type`, `recipients`, `subject`,`message_template`, `message`, `recipient_type`, `createdon`, `updatedon`, `status`) VALUES ('" . $message_type . "','" . $recipient_str . "','" . $subject . "','" . $template . "','" . $message . "','" . $recipient_type . "',NOW(),NOW(),'2')");
			}
            if ($success) {
				AjaxResponse::SuccessAndExit_STRING(1, '', "Message saved successfully",'Success');
            } else {
                AjaxResponse::SuccessAndExit_STRING(0, '', "Something went wrong. Please try again.", 'Error!');
            }
        } else {
			AjaxResponse::SuccessAndExit_STRING(0, '', "No recipients in the list!", 'Error!');
        }

    } else {
		AjaxResponse::SuccessAndExit_STRING(0, '', "Recipient must be selected!", 'Error!');
    }
}

elseif (isset($_GET['viewSystemMessage']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
	$messageid = (int) base64_decode($_GET['messageid']);
	$data = $_SUPER_ADMIN->super_get("SELECT `message` FROM `system_messages` WHERE `message_id`='{$messageid}'");
	if (!empty($data)){
		echo $data[0]['message'];
	} else {
		echo "No message!";
	}
}
elseif (isset($_GET['deleteSystemMessage']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	Auth::CheckPermission(Permission::GlobalSystemMessaging);

	$messageid = (int) base64_decode($_POST['messageid']);
	echo $_SUPER_ADMIN->super_update("UPDATE `system_messages` SET `status`='0' WHERE `message_id`='" . $messageid . "'");
}

elseif (isset($_GET['startUsersMeringProcess']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	global $_COMPANY;
  	$mainUserid = $_POST['mainUserid'];
	$extraUserid = $_POST['extraUserid'];

	if ($mainUserid > 0 && $extraUserid >0){
		$_COMPANY = Company::GetCompany($_SESSION['companyid']);
		$mergeUser = User::MergeUsers($mainUserid,$extraUserid);
		$_COMPANY =null;
		
		echo json_encode($mergeUser);
		exit();

	}
	echo json_encode(array('status'=>0,'message'=>'Please input  two user ids'));
	exit();
}

elseif (isset($_GET['uploadSystemMessageMedia']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
   	$file = [];
    if (empty($_FILES['file']['name']) || ((int)$_FILES['file']['size'])>5242880) {
        $err = ['error' => true, 'message' => gettext('Upload error, maximum allowed filesize is 5 MB')];
        echo stripslashes(json_encode($err));
        exit();
    }

    $tmp = $_FILES["file"]["tmp_name"];
    $mimetype   =   mime_content_type($tmp);
    $valid_mimes = array('image/jpeg' => 'jpg','image/png' => 'png','image/gif' => 'gif');

    if (in_array($mimetype,array_keys($valid_mimes))) {
        $ext = $valid_mimes[$mimetype];
    } else {
        $err = ['error' => true, 'message' => gettext('Unsupported file type. Only .png, .gif, .jpg or .jpeg files are allowed')];
        echo stripslashes(json_encode($err));
        exit();
    }
   	if ( !empty($_FILES['file']['name'])){
		$file 	   		=	basename($_FILES['file']['name']);
		$ext		=	get_safe_extension($file);
		$actual_name = teleskope_uuid().".".$ext;
		$tmp = $superDb->resizeImage($tmp, $ext,600);
		$filelink = $superDb->saveFile($tmp,$actual_name,'SYSTEM_MESSAGES');
		
		if ($filelink ){
			$file = [
				'url' => $filelink
			];
			echo stripslashes(json_encode($file));
			exit();
		} else {
			$err = ['error' => true, 'message' => $e->getMessage()];
			echo stripslashes(json_encode($err));
			exit();
		}
	}
	
}

elseif (isset($_GET['changeTempateState']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

	Auth::CheckPermission(Permission::GlobalManageTemplates);

	$valid_states = ['activate', 'deactivate', 'delete'];

	if (!isset($_POST['source_template_id'], $_POST['state']) ||
    	($sourceTemplateId = base64_url_decode($_POST['source_template_id'])) < 0 ||
		!in_array($_POST['state'], $valid_states)
  	){
    	echo 0;
    	exit();
  	}

	$state = $_POST['state'];
	$retVal = 0;
	if ($state == 'delete') {
		$retVal = TskpTemplate::DeleteTemplate($sourceTemplateId);
	} elseif ($state == 'activate') {
		$retVal = TskpTemplate::SetActiveState($sourceTemplateId, 1);
	} elseif ($state == 'deactivate') {
		$retVal = TskpTemplate::SetActiveState($sourceTemplateId, 2);
	}

	if ($retVal) {
        $response = array('success' => true);
    } else {
        $response = array('success' => false);
    }
	header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
else {
    Logger::Log ("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}
?>
