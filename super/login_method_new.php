<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManageLoginMethods);

$db	= new Hems();
$companyid = intval($_SESSION['companyid']);
$curr_company = Company::GetCompany(intval($_SESSION['companyid']));
$row = null;
$id = 0;

$page_title = "Add a Login Method";

if (isset($_GET['id'])){
    $page_title = "Update Login Method";
    $id = intval(base64_decode($_GET['id']));

    if (!isset($_POST['submit'])) {
        $data = $_SUPER_ADMIN->super_get("SELECT * FROM `company_login_settings` WHERE `companyid`='{$companyid}' AND `settingid`='{$id}'");
        if (count($data)) {
            $row = $data[0];
            $attributes = json_decode($row['attributes'],true) ?? array(); // json2Array($row['attributes']);
            $row = array_merge($row,$attributes);
        } else {
            $_SESSION['error'] = time();
            $_SESSION['error_msg'] = 'Bad request.';
            header("location:login_method_list");
        }
    }
}

if (isset($_POST['submit'])){
    $loginmethod = raw2clean($_POST['loginmethod']);
    // Common Fields
    $settingname =raw2clean($_POST['settingname']);
    $scope = raw2clean($_POST['scope']);
    $login_btn_label = raw2clean($_POST['login_btn_label']);
    $login_btn_description = raw2clean($_POST['login_btn_description']);
    $login_silently = intval($_POST['login_silently'] ?? 1);
    $login_screen_heading = raw2clean($_POST['login_screen_heading']);
    // Validate email domains
    $email_domains_arr = !empty($_POST['allowed_email_domains']) ? explode(',',$_POST['allowed_email_domains']) : array();
    $invalid_email_domains = array();
    $valid_email_domains = array();
    foreach ($email_domains_arr as $email_domain) {
        if ($curr_company->isValidEmailDomain($email_domain)) {
            $valid_email_domains[] = $email_domain;
        } else {
            $invalid_email_domains[] = $email_domain;
        }
    }
    $allowed_email_domains = implode(',',$valid_email_domains);

    if ($loginmethod=='saml2'){
        $debug_mode =(int) $_POST['debug_mode'];
        // Attributes
        $entityid = raw2clean($_POST['entityid']);
        $flatten_entityid_parameters = (int)$_POST['flatten_entityid_parameters'];
        $sso_url = raw2clean($_POST['sso_url']);
        $x509_cert = raw2clean($_POST['x509_cert']);
        $nameid_format = isset($_POST['nameid_format']) ? raw2clean($_POST['nameid_format']) : 'unspecified';
        $strict_mode = (int)$_POST['strict_mode'];
        $authn_signed = (int)$_POST['authn_signed'];
        $want_messages_signed = (int)$_POST['want_messages_signed'];
        $want_assertions_encrypted = (int)$_POST['want_assertions_encrypted'];
        $want_assertions_signed = (int)$_POST['want_assertions_signed'];
        $want_nameid_encrypted = (int)$_POST['want_nameid_encrypted'];
        $requested_authn_context = (int)$_POST['requested_authn_context'];
        $sp_or_idp_initated = isset($_POST['sp_or_idp_initated']) && ($_POST['sp_or_idp_initated'] == 'idp') ? 'idp' : 'sp';
        $add_lmid_to_saml_urls = ($sp_or_idp_initated == 'idp'); //always add login method to urls for SAML with IDP only support
        $idp_supports_post_binding_only = (int)$_POST['idp_supports_post_binding_only'];
        $use_affinities_identity =  intval($_POST['use_affinities_identity'] ?? 0);
        $auto_provisioning = (int)$_POST['auto_provisioning'];

        $attributes_array = array(
            'entityid' => $entityid,
            'sso_url' => $sso_url,
            'x509_cert' => $x509_cert,
            'nameid_format' => $nameid_format,
            'strict_mode' => $strict_mode,
            'flatten_entityid_parameters' => $flatten_entityid_parameters,
            'authn_signed' => $authn_signed,
            'want_messages_signed' => $want_messages_signed,
            'want_assertions_encrypted' => $want_assertions_encrypted,
            'want_assertions_signed' => $want_assertions_signed,
            'want_nameid_encrypted' => $want_nameid_encrypted,
            'requested_authn_context' => $requested_authn_context,
            'idp_supports_post_binding_only' => $idp_supports_post_binding_only,
            'sp_or_idp_initated' => $sp_or_idp_initated,
            'add_lmid_to_saml_urls' => $add_lmid_to_saml_urls,
            'auto_provisioning' => $auto_provisioning
        );

        if (in_array($scope, ['talentpeak','officeraven','peoplehero']) && $use_affinities_identity) {
            $attributes_array['use_affinities_identity'] = $use_affinities_identity;
        }

        $attributes = json_encode($attributes_array);

        $customization = json_encode(array('fields'=>array(
            'externalid'=>array('ename'=>'samlNameId'),
            'email'=>array('ename'=>'email'),
            'firstname'=>array('ename'=>'givenName'),
            'lastname'=>array('ename'=>'familyName'),
            'jobtitle'=>array('ename'=>'jobTitle'),
            'employeetype'=>array('ename'=>'employeeType'),
            'department'=>array('ename'=>'department'),
            'branchname'=>array('ename'=>'officeLocation'),
            'opco'=>array('ename'=>'companyName')
        )));
    } elseif ($loginmethod=='microsoft'){
        $tenantguid = raw2clean($_POST['tenantguid']);
        $sync_days = isset($_POST['sync_days']) ? (int)$_POST['sync_days'] : 0;
        $loginmethod ='microsoft';
        $debug_mode =(int) $_POST['debug_mode'];
        $authenticator_version = intval($_POST['authenticator_version'] ?? 2);
        $auto_provisioning = (int)$_POST['auto_provisioning'];
        $attributes = json_encode(array('tenantguid'=>$tenantguid,'sync_days'=>$sync_days, 'authenticator_version' => $authenticator_version, 'auto_provisioning' => $auto_provisioning));
        $customization = json_encode(array('fields'=>array('externalid'=>array('ename'=>'samlNameId'))));
    } elseif ($loginmethod=='connect'){
        $debug_mode =0;
        $attributes = json_encode(array());
        $customization = '';
    } elseif ($loginmethod=='otp'){
        $otp_from_email_label = raw2clean($_POST['otp_from_email_label']);
        $application_name = raw2clean($_POST['application_name']);
        $login_identities = array_intersect($_POST['login_identities'] ?? [], ['email', 'external_email','phone_number']);
        $login_identities = empty($login_identities) ? ['external_email'] : $login_identities;
        $attributes = json_encode(array('login_identities'=>$login_identities, 'otp_from_email_label'=>$otp_from_email_label, 'application_name' => $application_name));
        $debug_mode =0;
        $customization = '';
    } elseif ($loginmethod=='username'){
        $debug_mode =0;
        $attributes = json_encode(array());
        $customization = '';
    } else {
        $_SESSION['error'] = time();
        $_SESSION['error_msg'] = 'Wrong login method selected.';
        header("location:login_method_list");
        exit();
    }

//    $additional_attributes = array ();
//    $attributes = json_encode(array_merge(json_decode($attributes,true), $additional_attributes));

    if ($id>0){
        $_SUPER_ADMIN->super_update("UPDATE `company_login_settings` SET `settingname`='{$settingname}',`scope`='{$scope}',`debug_mode`='{$debug_mode}',`attributes`='{$attributes}',`allowed_email_domains`='{$allowed_email_domains}',`login_btn_label`='{$login_btn_label}',`login_btn_description`='{$login_btn_description}',`login_silently`={$login_silently},`modifiedon`=NOW() WHERE `companyid`='{$companyid}' AND `settingid`='{$id}'");
        $_SESSION['updated'] = time();
    } else {
        if ($loginmethod == 'microsoft' || $loginmethod == 'username' || $loginmethod == 'connect' || $loginmethod == 'otp') {
            // Check if this is the first of this type
            $existing_rows_with_same_method = $_SUPER_ADMIN->super_get("SELECT count(1) as tot FROM company_login_settings WHERE companyid={$companyid} AND scope='{$scope}' AND loginmethod='{$loginmethod}'")[0]['tot'];
            if ($existing_rows_with_same_method) {
                $_SESSION['error'] = time();
                $_SESSION['error_msg'] = 'Another '. $loginmethod.' login method already in use';
                header("location:login_method_list");
                exit();
            }
        }

        $_SUPER_ADMIN->super_insert("INSERT INTO `company_login_settings`(`companyid`, `settingname`, `scope`, `loginmethod`, `debug_mode`, `attributes`, `allowed_email_domains`, `login_btn_label`, `login_btn_description`, `login_silently`, `customization`,`createdon`) VALUES ('{$companyid}','{$settingname}','{$scope}','{$loginmethod}','{$debug_mode}','{$attributes}','{$allowed_email_domains}','{$login_btn_label}','{$login_btn_description}',{$login_silently},'{$customization}', NOW())");
        $_SESSION['added'] = time();
    }
    // Update login_screen_heading 
    $_SUPER_ADMIN->super_update("UPDATE `company_login_settings` SET `login_screen_heading`='{$login_screen_heading}' WHERE `companyid`='{$companyid}' AND `scope`='{$scope}'");
    
    if (!empty($invalid_email_domains)) {
        $_SESSION['error'] = time();
        $_SESSION['error_msg'] = 'Skipped email domains '. implode(',', $invalid_email_domains);
    }
    header("location:login_method_list");
    exit();
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/login_method_new.html');
include(__DIR__ . '/views/footer.html');

?>
