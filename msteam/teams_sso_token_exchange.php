<?php
session_start();
require_once __DIR__.'/../include/Company.php';

$teamsToken = $_POST['token'] ?? null;


if (!$teamsToken) {
    http_response_code(400);
    echo json_encode(['error' => 'No token provided']);
    exit;
}
$teamsToken = $_POST['token'] ?? null;
// CONFIG — will move to secure location and use Constants 
$clientId     = Config::Get('CLIENT_KEY_MS_TEAMS_APP');
$clientSecret = Config::Get('CLIENT_SECRET_MS_TEAMS_APP');
$tenantId     = Config::Get('CLIENT_TENANT_MS_TEAMS_APP');

$graphScopes  = 'https://graph.microsoft.com/.default';

// Step 1: Exchange token via OBO flow to get Graph access token
$tokenEndpoint = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";

$body = http_build_query([
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'requested_token_use' => 'on_behalf_of',
    'scope' => $graphScopes,
    'assertion' => $teamsToken
]);

$ch = curl_init($tokenEndpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo $response;
    exit;
}

$data = json_decode($response, true);
$accessToken = $data['access_token'] ?? null;

if (!$accessToken) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get Graph token']);
    exit;
}
// Step 2: Call Microsoft Graph to get user info
$userdata = User::GetO365User($accessToken);
$email = strtolower($userdata['mail']);
$firstname = $userdata['givenName'];
$lastname = $userdata['surname'];
$aad_oid = $userdata['id'];

$_COMPANY = Company::GetCompanyByEmail($email);
// company check. Redirect to login page as of now
if(!$_COMPANY){
    $error_message = 'MS Teams: The email domain for "' . $email . '" is not registered';
    Logger::Log ($error_message);
    header('Location: unregistered_account.html');
    exit;
    // Http::Forbidden($error_message);
}
$loggedInMsUser = User::GetUserByAadIdOrEmail($aad_oid, $email);

if(!$loggedInMsUser) {
    $error_message = 'MS Teams: The email address "' . $email . '" is not registered';
    Logger::Log ($error_message . ', aad_oid: ' . $aad_oid);
    Http::Forbidden($error_message);
}

// Success
// Setting session for when the code goe to head.php. Do not remove
$_SESSION['cid'] = $_COMPANY->id();
$_SESSION['companyid'] = $_COMPANY->id();
$_SESSION['userid'] = $loggedInMsUser->id();
$_SESSION['l_a'] = time(); // Session last acccess time
$_SESSION['s_s'] = time(); // Session start time
$_SESSION['ss'] = time(); // Session start time
$_SESSION['app_type'] = 'affinities'; //  currently defining manually


// Setting $valswhich is essential for encryption before re-directing to login_callback_affinity
$vals = array();
$vals['i'] = mt_rand();
$vals['ss'] = 'idp_initiated';
$vals['u'] = $loggedInMsUser->id();
$vals['c'] = $_COMPANY->id();
$vals['now'] = time();
$vals['rurl'] = $_SESSION['rurl'];
$vals['app'] = 'affinities';
$vals['nonce'] = base64_encode('A' .mt_rand().mt_rand(). 'Z');

$encrypted_token = aes_encrypt(json_encode($vals), TELESKOPE_USERAUTH_AFFINITY_KEY, '81sCvVX7Chyy04uZmZVMRBHk3XOHg0TZ', false);
$client = $_COMPANY->val('subdomain');
$login_url = 'https://'.$client. '.affinities.io'.BASEDIR. '/affinity/login_callback_affinity?is_teams=1&l=' . $encrypted_token . '';
header('location: ' . $login_url);

exit();