<?php
require_once __DIR__.'/head.php';

$db	= new Hems();
$row  = null;
if (isset($_GET['cid'])) {
    Auth::CheckPermission(Permission::EditCompanyInfo);

	$temp_companyid = base64_decode($_GET['cid']);
	if ($_SESSION['manage_super'] || (isset($_SESSION['manage_companyids']) && in_array($temp_companyid,explode(',',$_SESSION['manage_companyids'])))) {
		$companyid = $temp_companyid;
		$select 	= 	"select * from companies where companyid='".$companyid."'";
		$row 		= 	$_SUPER_ADMIN->super_get($select);
	}
}

if (isset($_POST['add'])){
	$companyname	=	raw2clean($_POST['companyname']);
	$contactperson	=	raw2clean($_POST['contactperson']);
	$email			=	raw2clean($_POST['email']);
	$contact		=	raw2clean($_POST['contact']);
	$address		=	raw2clean($_POST['address']);
	$city			=	raw2clean($_POST['city']);
	$state			=	raw2clean($_POST['state']);
	$country		=	raw2clean($_POST['country']);
	$zipcode		=	raw2clean($_POST['zipcode']);
	$subdomain		=	raw2clean($_POST['subdomain']);
	$from_email_prefix=	raw2clean($_POST['from_email_prefix']);
	$connect_attribute = raw2clean($_POST['connect_attribute']);
	$vendor_support_email	=	raw2clean($_POST['vendor_support_email']);
	$show_teleskope_tos_link = intval($_POST['show_teleskope_tos_link'] ?? 0);
	$show_teleskope_privacy_link = intval($_POST['show_teleskope_privacy_link'] ?? 0);
	$companyid		=	($_POST['companyid']);
	$logo			='';
	$loginscreen_background	='';
	$my_events_background ='';


    if (empty($companyid)) {
        Auth::CheckPermission(Permission::CreateNewCompany);
    } else {
        Auth::CheckPermission(Permission::EditCompanyInfo);
    }

	//Instantiate the client.
	$s3 = Aws\S3\S3Client::factory([
		'version' => 'latest',
		'region' => S3_REGION

	]);

	if ($companyid==""){
		$s3_folder = uniqid('S', true);
		$s3_folder = str_replace('.','',$s3_folder);
	} else {
		$s3_folder	=	$row[0]['s3_folder'];
	}

	if(!empty($_FILES['logo']['name'])){
		$file 	   		=	basename($_FILES['logo']['name']);
		$size 			= 	$_FILES['logo']['size'];
		$tmp 			=	$_FILES['logo']['tmp_name'];
		$ext			=	get_safe_extension($file);
		$actual_name 		=	"logo".time()."comp.".$ext;
		$picture = "https://".S3_BUCKET.".s3.amazonaws.com/".$s3_folder."/".$actual_name;
		try{
			$s3->putObject([
			'Bucket'=>S3_BUCKET,
			'Key'=>$s3_folder."/".$actual_name,
			'Body'=>fopen($tmp,'rb'),
			'ACL'=>'public-read'
			]);
			unlink($tmp);
			$logo= $picture;
		}catch(S3Exception $e){
			$error= 'Logo uploading error! Please try again';
		}
	}

	if ($companyid==""){
		if (isset($error)){
			$eror = $error;
		}else{
			$insert = "INSERT INTO `companies`(companyid,
                        `companyname`, `contactperson`, `email`, `contact`, 
						`address`, `city`, `state`, `country`, `zipcode`, 
						`logo`, `status`, `createdon`, `modified`, `isactive`, 
						subdomain, aes_suffix, loginscreen_background, my_events_background, from_email_prefix,
						s3_folder,vendor_support_email, connect_attribute,show_teleskope_tos_link,
						show_teleskope_privacy_link, zone_heading, zone_sub_heading) 
						VALUES ((select max(c.companyid)+10 from companies c), 
							'{$companyname}','{$contactperson}','{$email}','{$contact}',
							'{$address}','{$city}','{$state}','{$country}','{$zipcode}',
							'{$logo}','2',now(),now(),'1',
							'{$subdomain}', left(uuid(),8), '{$loginscreen_background}', '{$my_events_background}','{$from_email_prefix}',
							'{$s3_folder}','{$vendor_support_email}','{$connect_attribute}','{$show_teleskope_tos_link}',
							'{$show_teleskope_privacy_link}', 'Select a zone to continue', ''
						)";
			$companyid = $_SUPER_ADMIN->super_insert($insert);

			CompanyEncKey::CreateCompanyAwsKmsKey($subdomain);

			header("location:manage");
			exit();
		}
	}else{
		$logoUpdate = !empty($logo) ? ", `logo`='{$logo}'" : '';
		$update = "UPDATE `companies` 
					SET `companyname`='{$companyname}',`contactperson`='{$contactperson}',`email`='{$email}',`contact`='{$contact}',
					    `address`='{$address}',`city`='{$city}',`state`='{$state}',`country`='{$country}',`zipcode`='{$zipcode}',
					    `modified`=now(), from_email_prefix='{$from_email_prefix}',vendor_support_email='{$vendor_support_email}',
					    connect_attribute='{$connect_attribute}',show_teleskope_tos_link='{$show_teleskope_tos_link}',
					    show_teleskope_privacy_link='{$show_teleskope_privacy_link}' {$logoUpdate} 
					WHERE `companyid`='".base64_decode($companyid)."' 
					";
		$query=$_SUPER_ADMIN->super_update($update);
		if (empty($connect_attribute)) { // Whenever connect attribute is empty, disable all login methods that are of connect type.
			$_SUPER_ADMIN->super_update("UPDATE company_login_settings SET isactive=2 WHERE companyid='".base64_decode($companyid)."' AND loginmethod='connect'");
		}
		$tempCompanyForReload = Company::GetCompany(base64_decode($companyid),true);
		$_SESSION['updated'] = time();
		header("location:manage");
		exit();
	}
}


include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/company.html');
include(__DIR__ . '/views/footer.html');
?>
