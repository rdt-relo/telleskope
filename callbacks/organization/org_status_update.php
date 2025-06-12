<?php
ob_start();
session_start();
require_once __DIR__.'/../../include/init.php';
require_once __DIR__.'/../../include/Company.php';
require_once __DIR__.'/../../affinity/ViewHelper.php';
$_COMPANY = null; /* @var Company $_COMPANY */

// Step 1: Decrypt the Token we will receive from partnerpath
$partnerPathRawToken = $_POST['token'] ?? null;

if(!$partnerPathRawToken){
    http_response_code(400);
    echo json_encode(['error'=>"invalid token"]);
    // TODO: logger
    exit;
}

$key = PARTNERPATH_AUTH_KEY; 

// Decode the URL-safe base64 string back to its original base64 format
$base64Decoded = str_replace(['-', '_'], ['+', '/'], $partnerPathRawToken);
$decoded = base64_decode($base64Decoded);
$cipher = 'AES-128-CBC';
// IV length must match what was used during encryption
$ivlen = openssl_cipher_iv_length($cipher);
// Extract the IV and encrypted data
$iv = substr($decoded, 0, $ivlen);
$encryptedData = substr($decoded, $ivlen);
// Decrypt the data
$decryptedData = openssl_decrypt($encryptedData, $cipher, $key, OPENSSL_RAW_DATA, $iv);

if(!$decryptedData){
    http_response_code(400);
    // TODO: logger
    echo json_encode(['error'=>"Invalid authenticity"]);
    exit();
}

list($orgId, $hostname, $status, $encCompanyId, $allApproversEmails, $orgName, $approvalPageUrl) = explode('~', $decryptedData, 7);
// Data validation 
if((empty($orgId) || $orgId < 0) && in_array($status, ['0','1'], true)){
    // invalid data
    http_response_code(400);
    echo json_encode(['error'=>"invalid Data"]);
    exit();
}

$_COMPANY = Company::GetCompanyByUrl("https://{$hostname}");

if (!$_COMPANY){
    http_response_code(400);
    echo json_encode(['error'=>"invalid company"]);
    exit();
}

if ($_COMPANY->id() != $_COMPANY->decodeId($encCompanyId)){
    http_response_code(400);
    echo json_encode(['error'=>"company mismatch"]);
    exit();
}

$result = Organization::UpdateOrgConfirmationStatus($orgId, $status,$_COMPANY->decodeId($encCompanyId)); //Changing status through partnerpath's API end

if($result){
    if(!empty($allApproversEmails)){
        $emailsArr = explode(',',$allApproversEmails);
        if(!empty($emailsArr) && is_array($emailsArr)){
                $app_type = 'teleskope';
                $subject = "The requested organization has been updated.";
                $message = "Hi,<br>This ".$orgName." has been updated.<br> You can click on this link ".$approvalPageUrl." to view the organization's details<br>Thank you";
                foreach($emailsArr as $emailReceiver){
                if(!empty(trim($emailReceiver))){
                    try {
                        $_COMPANY->emailSendExternal('', trim($emailReceiver), $subject, $message, $app_type, '');
                    } catch (Exception $e) {
                        echo "Error: " . $e->getMessage();
                    }
                  }
                }
        }
    }
    echo json_encode(['error'=>"Status updated"]);
}else{
    echo json_encode(['error'=>"Failed to update status"]);
}
$_COMPANY = null; 

exit();