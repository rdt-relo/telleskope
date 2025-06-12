<?php
include_once __DIR__.'/head.php';

$error_message = '';
$uriauthfail = false;
$companyLoginMethods = array();

/**
 * Session Lifecycle
 * A new session is created in head.php but the data is populated only when this URL is accessed by passing
 * the 'rurl' and 'ss' GET parameters.
 * The session is destroyed when the user is logged in, i.e. login_and_exit is called.
 * O365 uses its own  microsoft domain session for O365 data
 */

/**
 * After successfully Authenticating the subscriber, callback the redirect uri to login the user into the application
 * Build the redirect URI for the calling Application (e.g. Admin or Affinities), passes encrypted token with session
 * data. Needs the $_SESSION variable to be properly set before calling this function.
 */
function login_and_exit()
{
    $vals = array();
    $vals['i'] = mt_rand();
    $vals['ss'] = $_SESSION['ss'];
    $vals['u'] = $_SESSION['userid'];
    $vals['c'] = $_SESSION['cid'];
    $vals['now'] = time();
    $vals['rurl'] = $_SESSION['rurl'];
    $vals['app'] = $_SESSION['app_type'];
    $vals['nonce'] = base64_encode('A' .mt_rand().mt_rand(). 'Z');
    $login_url = '';

    $application = $_SESSION['app_type'];
    $client = $_SESSION['client'];
    // Delete the session before leaving & remove PHPSESSID from browser
    setcookie( session_name(), '', time()-3600, '/');
    $_SESSION = array();
    session_destroy();

    if ($application === 'affinities') {
        if (strpos($vals['ss'], 'native_app_login') === 0) {
            $encrypted_token = aes_encrypt(json_encode($vals), TELESKOPE_USERAUTH_API_KEY, 'u7KD33py3JsrPPfWCilxOxojsDDq0D3M', false);
            $response = json_encode(array('message' => 'Session started successfully', 'method' => 'nativeLogin', 'success' => '1', 'auth_token' => $encrypted_token));
            // Set cookie for easier future discovery
            $cookie_path = str_replace('login', 'discover_v2', $_SERVER['REQUEST_URI']);
            setcookie('subdomain', $client, time() + 31536000, $cookie_path);

            $title = 'login successful';
            if ($vals['ss']=== 'native_app_login2') {
                include __DIR__ . '/views/native_login_success2.html';
            }
        } else {
            // Non-native login into affinties
            $encrypted_token = aes_encrypt(json_encode($vals), TELESKOPE_USERAUTH_AFFINITY_KEY, '81sCvVX7Chyy04uZmZVMRBHk3XOHg0TZ', false);
            $login_url = 'https://'.$client. '.affinities.io'.BASEDIR. '/affinity/login_callback_affinity?l=' . $encrypted_token . '';
            header('location: ' . $login_url);
        }
    } elseif ($application === 'officeraven') {
        $encrypted_token = aes_encrypt(json_encode($vals), TELESKOPE_USERAUTH_OFFICERAVEN_KEY, 'FtpfUZNAiWJMpUR1EdmbvRMuCM7SOcL4', false);
        $login_url = 'https://'.$client. '.officeraven.io'.BASEDIR.'/officeraven/login_callback_officeraven?l=' . $encrypted_token . '';
        header('location: ' . $login_url);
    } elseif ($application === 'teleskope') {
        $encrypted_token = aes_encrypt(json_encode($vals), TELESKOPE_USERAUTH_ADMIN_KEY, 'rZVO0XlpOKLTyE997HsApytENOxd8lr5', false);
        $login_url =  'https://'.$client. '.teleskope.io'.BASEDIR.'/admin/login_callback_admin?l=' . $encrypted_token . '';
        header('location: ' . $login_url);

    } elseif ($application === 'talentpeak') {
        $encrypted_token = aes_encrypt(json_encode($vals), TELESKOPE_USERAUTH_TALENTPEAK_KEY, 'FtpfUZNAiWJMpUR1EdmbvRMuCM7SOcL4', false);
        $login_url = 'https://'.$client. '.talentpeak.io'.BASEDIR.'/talentpeak/login_callback_talentpeak?l=' . $encrypted_token . '';
        header('location: ' . $login_url);
    } elseif ($application === 'peoplehero') {
        $encrypted_token = aes_encrypt(json_encode($vals), TELESKOPE_USERAUTH_PEOPLEHERO_KEY, 'RamfUZNAiWJMpUR1EdmbvRMuCM7SOcL4', false);
        $login_url = 'https://'.$client. '.peoplehero.io'.BASEDIR.'/peoplehero/login_callback_peoplehero?l=' . $encrypted_token . '';
        header('location: ' . $login_url);
    } else {
        error_and_exit('Internal Server Error!');
    }
    exit();
}

if (isset($_GET['rurl'], $_GET['ss'])) {
    // Just got redirected here. Clear the session.
    session_unset();

    // Extract and save the redirect URL
    $_SESSION['rurl'] = $_GET['rurl'];  //Save it in session, we will need it. url is base64 encoded
    $urlhost = parse_url(base64_url_decode($_SESSION['rurl']), PHP_URL_HOST);
    $_SESSION['app_type'] = strtolower(explode('.', $urlhost, 3)[1]);
    $_SESSION['client'] = strtolower(explode('.', $urlhost, 3)[0]);
    $allApps = Company::APP_LABEL;
    $_COMPANY = Company::GetCompanyByUrl(base64_url_decode($_SESSION['rurl']));
    if ($_COMPANY) {
        $_SESSION['ss'] = $_GET['ss'];
        $_SESSION['app-name'] = array_key_exists($_SESSION['app_type'],$allApps) ? $allApps[$_SESSION['app_type']] : 'Affinities';
                                //($_COMPANY->custom['affinity']['erg']['name-short']));
        $_SESSION['cid'] = (int)$_COMPANY->id(); //Typecast to int
        $_SESSION['realm'] = $_COMPANY->val('subdomain').'.'.$_SESSION['app_type'].'.io';
        $logo = $_COMPANY->val('logo');

        // check for silent login criteria
        $companyLoginMethods = $_COMPANY->getCompanyLoginMethods($_SESSION['app_type'], 1);
        if (
            empty($_GET['logout'])                  // logout parameter is not set, meaning user is not here because of logout
            && (1 == count($companyLoginMethods))   // and there is only one login method
            && (1 == $companyLoginMethods[0]['login_silently']) // and silent login is turned on
        ) {
            // do a silent login, if login method supports it.
            if ('saml2' == $companyLoginMethods[0]['loginmethod']) {
                header('Location: login_re?lm_type=saml2&lm_id=' . $companyLoginMethods[0]['settingid'] ?? 0);
                exit();
            }
            if ('microsoft' == $companyLoginMethods[0]['loginmethod']) {
                header('Location: login_re?lm_type=microsoft&lm_id=' . $companyLoginMethods[0]['settingid'] ?? 0);
                exit();
            }
            if ('connect' == $companyLoginMethods[0]['loginmethod']) {
                header('Location: login_re?lm_type=connect&lm_id=' . $companyLoginMethods[0]['settingid'] ?? 0);
                exit();
            }
        }
      } else {
        Logger::Log("User Login Error [001]: Attempted to login using URL ".base64_url_decode($_SESSION['rurl']) . "companyid={$_SESSION['cid']}", Logger::SEVERITY['WARNING_ERROR']);
        error_and_exit('Error [001]:Your company domain is not registered!');
    }
}

elseif (!empty($_SESSION['userid']) && !empty($_SESSION['user_email']) && !empty($_SESSION['policy_accepted'])){
  global $_COMPANY;
  if ($_COMPANY->isValidEmail($_SESSION['user_email']) && ($user = User::GetUserByEmail($_SESSION['user_email'])) !== null) {
    
    // Check if user blocked. Error and Exit
    if ($user->isBlocked()) {
        error_and_exit('Your account is blocked!');
    }
    
    $user->updatePolicyAcceptedDate();
    login_and_exit();
  }
}

elseif (isset($_POST['email'])) {
    // username and password authentication mechanism
    global $_COMPANY; /* @var Company $_COMPANY */
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!verify_recaptcha()) {
        $error_message = "Incorrect reCAPTCHA, try again";
    } elseif (empty($email) || empty($password)) {
        $error_message = 'Email and Password cannot be empty';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Your email address is invalid';
    } elseif ($_COMPANY->isValidEmail($email) && ($user = User::GetUserByEmail($email)) !== null) {
        $failed_attempts = User::GetFailedLoginAttempts($email);
        
        // Check if user blocked. Error and Exit
        if ($user->isBlocked()) {
            error_and_exit('Your account is blocked!');
        }
        
        if ($failed_attempts >= 5) {
            User::CreateOrUpdateFailedLoginAttempts($email);
            $error_message = 'Account locked due to many failed attempts! <br>Click <b>Forgot Password</b> to reset password.';
        } elseif (!$user->isActive()) {
            $error_message = 'Your account is not in active status! Please contact your administrator';
        } elseif (!$user->isVerified()) {
            $_SESSION['userid'] = '';
            // Note when setting email in domainverification session variable, it needs to be first processed
            // using raw2clean to convert special characters to the htmlspecial characters equivalent so that they
            // can match the email address stored in the database.
            $_SESSION['domainverification'] = $email ; //raw2clean($email);
            $_SESSION['confirmation'] = $user->id();
            // If there is no confirmation code on record, send a new one.
            if (empty($user->val('confirmationcode'))) {
                $_ZONE = $_COMPANY->getEmptyZone($_SESSION['app_type']);
                $user->generateAndSendConfirmationCode();
            }
            header('location:confirmation');
            exit();
        } elseif (password_verify($password, $user->val('password'))) {
            $_SESSION['userid'] = $user->id();
            if ($failed_attempts) {
                // Delete failed attempts row if it exists
                User::DeleteFailedLoginAttempt($email);
            }
            User::DeletePasswordResetCode($email);

          if (empty($_SESSION['policy_accepted']) &&
            (empty($user->val('policy_accepted_on'))  ||
              (!empty($_COMPANY->val('customer_policy_updated_on')) && $_COMPANY->val('customer_policy_updated_on') > $user->val('policy_accepted_on')))
          ) {
            $_SESSION['user_email'] = $email;
            header("location: policyconsent?re=1");   // send user to policy acceptance page
            exit();
          }

          if (!empty($_SESSION['policy_accepted'])){
            $user->updatePolicyAcceptedDate();
          }

            login_and_exit();
        } else {
            User::CreateOrUpdateFailedLoginAttempts($email);
            $error_message = 'Invalid Email or Incorrect Password';
        }
    } else {
        $error_message = 'Invalid Email or Incorrect Password';
    }
}

elseif (isset($_SESSION['office365_data'])) {
    // Office 365 Auth
    global $_COMPANY; /* @var Company $_COMPANY */

    $o365Settings = $_COMPANY->getO365Settings($_SESSION['app_type']);
    if ($o365Settings === null || !$o365Settings['customization']) {
        Logger::Log("Fatal Error [200A]: Something is wrong, login method is not properly configured");
        error_and_exit('Error [200A]: Unable to Sign In (Authenticator Error)');
    }

    $userdata = $_SESSION['office365_data'];
    $email = $userdata['preferred_username'];
    $firstname = '';
    $lastname = '';
    $aad_oid = '';

    if ($email === 'not_supplied') {
        // Flow without 'oid profile' scope
        //Use the access_token to get user profile
        $userdata = User::GetO365User($_SESSION['access_token']);
        $email = strtolower($userdata['userPrincipalName']);
        $firstname = $userdata['givenName'];
        $lastname = $userdata['surname'];
        $aad_oid = $userdata['id'];
    } else {
        // Flow with 'oid profile' scope
        $fullname = explode(' ', $userdata['name']);
        $firstname = $fullname[0] ?? '';
        $lastname = $fullname[1] ?? '';
        $aad_oid = $userdata['oid'] ?? '';
    }

    if ($_COMPANY->isValidEmail($email)) {

        // 1. try getting the user
        try {
            $user = User::GetUserByAadIdOrEmail($aad_oid, $email);
        } catch (DuplicateAccountException $e) {
            $debug_code = base64_encode('#' . $e->getUser1Id() . '#' . $e->getUser2Id());
            $emesg = 'Error [206]: User account conflict error <br><br><sup>code: ' . $debug_code . '</sup><br><br>';
            $log_meta = ['exception' => $e->getMessage(), 'user_1_id' => $e->getUser1Id(), 'user_2_id' => $e->getUser2Id()];
            Logger::Log(strip_tags($emesg), Logger::SEVERITY['FATAL_ERROR'], $log_meta);
            error_and_exit ($emesg);
        }

        if (empty($user)) {  // user does not exist

            if (empty($_SESSION['policy_accepted'])) {
                header("location: policyconsent?re=1");   // send user to policy acceptance page
                exit();
            }

        } else {  // user exists,  check if policy acceptance date is not null and not older than last date of policy change
            // Check if user blocked. Error and Exit
            if ($user->isBlocked()) {
                error_and_exit('Your account is blocked!');
            }
            if (empty($_SESSION['policy_accepted']) &&
                (empty($user->val('policy_accepted_on'))  ||
                    (!empty($_COMPANY->val('customer_policy_updated_on')) && $_COMPANY->val('customer_policy_updated_on') > $user->val('policy_accepted_on')))
            ) {
                header("location: policyconsent?re=1");   // send user to policy acceptance page
                exit();
            }
        }

        if (!$user){
            if ($_SESSION['auto_provisioning']){
                $user = User::CreateNewUser($firstname, $lastname, $email, '', User::USER_VERIFICATION_STATUS['VERIFIED']);
                $user ?-> updateAadOid($aad_oid);
            } else {
                Logger::Log("User Login 0365 Error [204]: {company={$_SESSION['cid']}, AAD_OID={$aad_oid}, email={$email}, firstname={$firstname}, lastname={$lastname}}", Logger::SEVERITY['INFO']);
                $error_message = 'Error [204]: Auto provisioning is disabled and unable to find a matching user';
            }
        }

        if ($user) {

            // Check if user blocked. Error and Exit
            if ($user->isBlocked()) {
                error_and_exit('Your account is blocked!');
            }

            if (!$user->isActive() || !$user->isVerified()) {
                if ($user->val('isactive') != Teleskope::STATUS_WIPE_CLEAN) {
                    $user->reset(); // We *CANNOT* auto reset user active status if the user deleted their account
                }
            }

            if ($user->isActive() && $user->isVerified()) {
                $graph_api_code = $user->updateO365Profile($_SESSION['access_token'], $o365Settings['customization']);
                if ($graph_api_code === 1) {
                    $_SESSION['userid'] = $user->id();
                    //Create UserSync Job
                    if ($_SESSION['app_type'] === 'teleskope' && $user->isAdmin() && (int)$o365Settings['sync_days']) {
                        $_USER = $user;
                        $user_sync_job = new UserSync365Job ();
                        $user_sync_job->saveAsUserSyncUpdateType($_SESSION['access_token'], $o365Settings['customization'], (int)$o365Settings['sync_days']);
                        $_USER = null; // Reset $_USER as it is not be needed anymore
                    }

                    if (!empty($_SESSION['policy_accepted'])){
                        $user->updatePolicyAcceptedDate();
                    }

                    login_and_exit();
                } elseif ($graph_api_code < 1) {
                    Logger::Log("User Login 0365 Fatal Error [200B]: {company={$_SESSION['cid']}, AAD_OID={$aad_oid}, email={$email}, firstname={$firstname}, lastname={$lastname}}");
                    $error_message = 'Error [200B]: Cannot connect to Microsoft Graph API';
                } else {
                    Logger::Log("User Login 0365 Error [202A]: {company={$_SESSION['cid']}, AAD_OID={$aad_oid}, email={$email}, firstname={$firstname}, lastname={$lastname}}");
                    $error_message = 'Error [202A]: User Account Provisioning Error';
                }
            } else {
                Logger::Log("User Login 0365 Error [201]: {company={$_SESSION['cid']}, AAD_OID={$aad_oid}, email={$email}, firstname={$firstname}, lastname={$lastname}}", Logger::SEVERITY['WARNING_ERROR']);
                $error_message = 'Error [201]: Inactive Account';
            }
        } else {
            if (!$error_message){
                Logger::Log("User Login 0365 Error [202B]: {company={$_SESSION['cid']}, AAD_OID={$aad_oid}, email={$email}, firstname={$firstname}, lastname={$lastname}}");
                $error_message = 'Error [202B]: User Account Provisioning Error';
            }
        }
    } else {
        Logger::Log("User Login 0365 Fatal Error [203]: {company={$_SESSION['cid']}, AAD_OID={$aad_oid}, email={$email}, firstname={$firstname}, lastname={$lastname}}");
        error_and_exit('Error [203]: Invalid login URL (user email domain not allowed)');
    }
}

elseif (isset($_SESSION['saml2_data'])) {
    global $_COMPANY; /* @var Company $_COMPANY */

    $samlSetting = $_SESSION['saml2_settings'];
    if ($samlSetting === null) {
        Logger::Log("Fatal Error - Session Expired");
        error_and_exit('Error [300A]: Unable to Sign In (Session Expired Error)');
    } elseif (!$samlSetting['customization']) {
        Logger::Log("Fatal Error - Something is wrong, login method is not properly configured");
        error_and_exit('Error [300B]: Unable to Sign In (Authenticator Error)');
    }

    // SAML2 Data

    $userdata = array();

    // The following removes URL extensions from key name, i.e. it changes
    // http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname  to givenname
    // This usecase is related to microsoft SAML2 providing long names for attributes
    // Issue #1320
    foreach ($_SESSION['saml2_data']['samlUserdata'] as $k => $v) {
        $k_parts = explode('/', $k);
        $userdata[end($k_parts)] = $v;
    }
    //Logger::Log("IN ".json_encode($_SESSION['saml2_data']['samlUserdata'])) ;
    //Logger::Log("OUT ".json_encode($userdata)) ;

    if (!empty($_SESSION['saml2_settings']['debug_mode'])) {
        Logger::Log("SAML2: ACS for {$_SESSION['realm']}", Logger::SEVERITY['INFO'], [
            'samlNameId' => $_SESSION['saml2_data']['samlNameId'],
            'samlNameIdFormat' => $_SESSION['saml2_data']['samlNameIdFormat'],
            'attributes' => $userdata
        ]);
    }

    $externalid = '';
    $email = '';
    $firstname = '';
    $lastname = '';
    $jobTitle ='';
    $department = '';
    $branchname ='';
    $city = '';
    $state = '';
    $country = '';
    $region = '';
    $opco = '';
    $employeeType='';
    $externalUsername = '';
    $externalRoles = null;

    if (!empty($samlSetting['customization']['fields'])){
        ##### Dummy Data START #####
        #$samalData = json_decode('{ "samlNameId": "181491", "samlUserdata": { "employeeType": [ "Employee - Regular" ], "officeLocation": [ "Reston, VA" ], "displayName": [ "Aman S. Brar " ], "givenName": [ "Amanpreet" ], "familyName": [ "Singh Brar" ], "jobTitle": [ "Director, Technology" ], "id": [ "181491" ], "department": [ "Information Technology" ], "email": [ "aman@teleskope.io" ] }, "samlNameIdFormat": "urn:oasis:names:tc:SAML:2.0:nameid-format:persistent", "samlNameIdNameQualifier": null, "samlNameIdSPNameQualifier": null, "samlSessionIndex": "JdVpVX--hp4" }',true);
        #$externalId = $samalData['samlNameId'];
        #$userdata = $samalData['samlUserdata'];
        ##### Dummy Data END #####

        // First add nameid to userdata attributes to allow it to be run through transformations
        $userdata['samlNameId'] = $_SESSION['saml2_data']['samlNameId'];
        $externalid = User::XtractAndXformValue('externalid',$userdata, $samlSetting['customization']['fields'] );
        $email = User::XtractAndXformValue('email',$userdata, $samlSetting['customization']['fields'] );
        //$picture = isset($userdata['picture']) ? raw2clean(is_array($userdata['picture']) ? $userdata['picture'][0] : $userdata['picture']) : '';
        $firstname = User::XtractAndXformValue('firstname',$userdata, $samlSetting['customization']['fields'] );
        $lastname = User::XtractAndXformValue('lastname',$userdata, $samlSetting['customization']['fields'] );
        $jobTitle = User::XtractAndXformValue('jobtitle',$userdata, $samlSetting['customization']['fields'] );
        $department =User::XtractAndXformValue('department',$userdata, $samlSetting['customization']['fields'] );
        $branchname = User::XtractAndXformValue('branchname',$userdata, $samlSetting['customization']['fields'] );
        $city = User::XtractAndXformValue('city',$userdata, $samlSetting['customization']['fields'] );
        $state = User::XtractAndXformValue('state',$userdata, $samlSetting['customization']['fields'] );
        $country = User::XtractAndXformValue('country',$userdata, $samlSetting['customization']['fields'] );
        $region = User::XtractAndXformValue('region',$userdata, $samlSetting['customization']['fields'] );
        $opco = User::XtractAndXformValue('opco',$userdata, $samlSetting['customization']['fields'] );
        $employeeType = User::XtractAndXformValue('employeeType',$userdata, $samlSetting['customization']['fields'] );
        $externalUsername = User::XtractAndXformValue('externalusername',$userdata, $samlSetting['customization']['fields'] );
        $externalRoles = User::XtractAndXformValue('externalroles',$userdata, $samlSetting['customization']['fields'], true);
    } else {
        $externalid = $_SESSION['saml2_data']['samlNameId'];
        $email = isset($userdata['email']) ? (is_array($userdata['email']) ? $userdata['email'][0] : $userdata['email']) : '';
    }

    if (empty($externalid)) {
        error_and_exit('Invalid SAML response - external id is empty');
    }

    /*** Warning ****
     * The following method should be called before making a call to generateTeleskopeEmailAddress method.
     * **/
    try {
        $user = User::GetUserByExternalIdOrEmail($externalid, $email);
    } catch (DuplicateAccountException $e) {
        $debug_code = base64_encode('#' . $e->getUser1Id() . '#' . $e->getUser2Id());
        $emesg = 'Error [306]: User account conflict error <br><br><sup>code: ' . $debug_code . '</sup><br><br>';
        $log_meta = ['exception' => $e->getMessage(), 'user_1_id' => $e->getUser1Id(), 'user_2_id' => $e->getUser2Id()];
        Logger::Log(strip_tags($emesg), Logger::SEVERITY['FATAL_ERROR'], $log_meta);
        error_and_exit ($emesg);
    }

    if (empty($email)) {
        // See Warning above Before creating a new email for user first run GetUserByExternalIdOrEmail to see if it results in a merge.
        $email = ($user) ? $user->val('email') : $_COMPANY->generateTeleskopeEmailAddress($externalid);

        // Assign an internal teleskope email, only for new users if they come in with no email address.
        //$user ?-> updateEmail($email);

    } elseif (!$_COMPANY->isValidEmail($email)) {
        Logger::Log("User Login SAML2 Fatal Error [303]: {company={$_SESSION['cid']}, externalid={$externalid}, email={$email}, firstname={$firstname}, lastname={$lastname}}");
        error_and_exit ('Error [303]: Your company domain is not registered!');
    }


    if (!$user){
        if ($_SESSION['auto_provisioning']){
            if (empty($_SESSION['policy_accepted'])) {
                header("location: policyconsent?re=1");   // send user to policy acceptance page
                exit();
            }
            $user = User::CreateNewUser($firstname, $lastname, $email, '', User::USER_VERIFICATION_STATUS['VERIFIED']);
            $user ?-> updateExternalId($externalid);
        } else {
            Logger::Log("User Login SAML2 Error [304]: {company={$_SESSION['cid']}, externalid={$externalid}, email={$email}, firstname={$firstname}, lastname={$lastname}}", Logger::SEVERITY['INFO']);
            error_and_exit ('Error [304]: Auto provisioning is disabled and unable to find a matching user record');
        }
    }

    if (!$user){ // Could not find a user, could not create a user ... so lets error out.
        Logger::Log("User Login SAML2 Error [302]: {company={$_SESSION['cid']}, externalid={$externalid}, email={$email}, firstname={$firstname}, lastname={$lastname}}");
        error_and_exit ('Error [302]: User Account Provisioning Error');
    }

    $user->updateExternalRoles($externalRoles);

    if ($user->isBlocked()) {
        error_and_exit('Your account is blocked!');
    }


    if (empty($_SESSION['policy_accepted']) &&
        (empty($user->val('policy_accepted_on')) ||
            (!empty($_COMPANY->val('customer_policy_updated_on')) && $_COMPANY->val('customer_policy_updated_on') > $user->val('policy_accepted_on')))
    ) {
        header("location: policyconsent?re=1");   // send user to policy acceptance page
        exit();
    }

    if (!$user->isActive() || !$user->isVerified()) {
        if ($user->val('isactive') != Teleskope::STATUS_WIPE_CLEAN) {
            $user->reset(); // We *CANNOT* auto reset user active status if the user deleted their account
        }
    }


    if ($user->isActive() && $user->isVerified()) {
        $user->updateProfile2($email, $firstname, $lastname, '', $jobTitle, $department, $branchname, $city, $state, $country, $region, $opco, $employeeType, $externalUsername, '', true, null, null, null);
        $_SESSION['userid'] = $user->id();


         if (!empty($_SESSION['policy_accepted'])){
            $user->updatePolicyAcceptedDate();
        }

        login_and_exit();
    } else {
        Logger::Log("User Login SAML2 Error [301]: {company={$_SESSION['cid']}, externalid={$externalid}, email={$email}, firstname={$firstname}, lastname={$lastname}}", Logger::SEVERITY['WARNING_ERROR']);
        $error_message = 'Error [301]: Inactive Account';
    }

}

elseif (isset($_SESSION['connect_data'])) {
    global $_COMPANY; /* @var Company $_COMPANY */

    if (!$_COMPANY->isConnectEnabled()) {
        error_and_exit('Error [400Z]: Unable to Sign In (Connect method is not enabled)');
    }

    $connectData = $_SESSION['connect_data'];
    if (empty($connectData['teleskope_company_id']) || $connectData['teleskope_company_id'] != $_SESSION['cid']) {
        Logger::Log("Fatal Error - Unable to login");
        error_and_exit('Error [400A]: Unable to Sign In (Authenticator Error)');
    }

    if (!$connectData['is_logged_in']) {
        Logger::Log("Fatal Error - Unable to login");
        error_and_exit('Error [400B]: Unable to Sign In (Authenticator Error)');
    }

    if (empty($connectData['external_email'])) {
        Logger::Log("Fatal Error - Unable to login");
        error_and_exit('Error [400D]: Unable to Sign In (Authenticator Error)');
    }

    if (empty($connectData['teleskope_user_id']) ||
        ($user = User::GetUser($connectData['teleskope_user_id'], true)) == null) {
        Logger::Log("Fatal Error - Unable to login");
        error_and_exit('Error [402]: User Account Provisioning Error');
    }

    if (empty($connectData['app_type'])) {
        Logger::Log("Fatal Error - app_type is missing");
        error_and_exit('Error [403]: Missing app_type setting');
    }

    // Check if user blocked. Error and Exit
    if ($user->isBlocked()) {
        error_and_exit('Your account is blocked!');
    }

    if (!$user->isActive()) {
        Logger::Log("User Login Connect Error [401]: {company={$_SESSION['cid']}, external_email={$connectData['external_email']}", Logger::SEVERITY['WARNING_ERROR']);
        error_and_exit('Error [401]: Inactive Account');
    }

    if ($_COMPANY->isValidAndRoutableEmail($user->val('email'))) {
        Logger::Log("User Login Connect Error [407]: {company={$_SESSION['cid']}, email={$user->val('email')}, external_email={$connectData['external_email']}", Logger::SEVERITY['WARNING_ERROR']);
        error_and_exit('Error [407]: This login method is unavailable for your account, please use another login method');
    }

    if (empty($_SESSION['policy_accepted']) &&
        (empty($user->val('policy_accepted_on'))  ||
            (!empty($_COMPANY->val('customer_policy_updated_on')) && $_COMPANY->val('customer_policy_updated_on') > $user->val('policy_accepted_on')))
    ) {
        header("location: policyconsent?re=1");   // send user to policy acceptance page
        exit();
    }

    $_SESSION['userid'] = $user->id();
    $_SESSION['app_type'] = $connectData['app_type']; // @Todo in future ask user which application they want to login .
    $_SESSION['client'] = $_COMPANY->val('subdomain');

    if (!empty($_SESSION['policy_accepted'])){
        $user->updatePolicyAcceptedDate();
    }

    if (!empty($_SESSION['connect_verification']) && $_SESSION['connect_verification'] == 'done') {
        $_SESSION['ss'] = 'connect_verified';
    }
    login_and_exit();
}

elseif (isset($_SESSION['otp_data'])) {
    global $_COMPANY; /* @var Company $_COMPANY */


    $otpData = $_SESSION['otp_data'];

    if (!$otpData['is_logged_in']) {
        Logger::Log("Fatal Error - Unable to login");
        error_and_exit('Error [400B]: Unable to Sign In (Authenticator Error)');
    }

    if (empty($otpData['otp_login_email'])) {
        Logger::Log("Fatal Error - Unable to login");
        error_and_exit('Error [400D]: Unable to Sign In (Authenticator Error)');
    }

    if (empty($otpData['otp_login_userid']) ||
        ($user = User::GetUser($otpData['otp_login_userid'], true)) == null) {
        Logger::Log("Fatal Error - Unable to login");
        error_and_exit('Error [402]: User Account Provisioning Error');
    }

    if (empty($otpData['app_type'])) {
        Logger::Log("Fatal Error - app_type is missing");
        error_and_exit('Error [403]: Missing app_type setting');
    }

    // Check if user blocked. Error and Exit
    if ($user->isBlocked()) {
        error_and_exit('Your account is blocked!');
    }

    if (!$user->isActive()) {
        Logger::Log("User Login Connect Error [401]: {company={$_SESSION['cid']}, external_email={$otpData['otp_login_email']}", Logger::SEVERITY['WARNING_ERROR']);
        error_and_exit('Error [401]: Inactive Account');
    }

    if (empty($_SESSION['policy_accepted']) &&
        (empty($user->val('policy_accepted_on'))  ||
            (!empty($_COMPANY->val('customer_policy_updated_on')) && $_COMPANY->val('customer_policy_updated_on') > $user->val('policy_accepted_on')))
    ) {
        header("location: policyconsent?re=1");   // send user to policy acceptance page
        exit();
    }

    $_SESSION['userid'] = $user->id();
    $_SESSION['app_type'] = $otpData['app_type']; // @Todo in future ask user which application they want to login .
    $_SESSION['client'] = $_COMPANY->val('subdomain');

    if (!empty($_SESSION['policy_accepted'])){
        $user->updatePolicyAcceptedDate();
    }

    //$_SESSION['ss'] = 'otp_verified';

    login_and_exit();
}

elseif (!(isset($_GET['continue'], $_SESSION['rurl']) && $_GET['continue'] === '1')) {
    Logger::Log("User Login Error [002]: Attempted to login using URL = ".base64_url_decode($_SESSION['rurl'] ?? '') . ", companyid={$_SESSION['cid']}", Logger::SEVERITY['WARNING_ERROR']);
    error_and_exit('Error [002]: Unauthorized Access');
}

$title = 'Teleskope - Sign In';
include __DIR__ . '/views/login.html';
