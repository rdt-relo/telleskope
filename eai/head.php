<?php
require_once __DIR__.'/../include/Company.php'; //This file internally calls dbfunctions, Company etc.
require_once __DIR__ . '/auth/EaiAuth.php';

//This check is to block access to this pages via non https://companyname.teleskope.io domains
if(strpos($_SERVER['HTTP_HOST'], ".teleskope.io") == false) {
    Logger::Log("Enterprise API Security Error - Invalid URL (001) [{$_EAI_MODULE}|{$_SERVER['REQUEST_METHOD']}|{$_SERVER['HTTP_HOST']}|{$_SERVER['REQUEST_URI']}]", Logger::SEVERITY['SECURITY_ERROR']);
    header(HTTP_NOT_FOUND);
    die ('Invalid URL (001)');
}

$_COMPANY = null;
$_USER = null; // We will not set $_USER global for security reasons, explicit null
$_ZONE = null; // We will not set $_ZONE global for security reasons, explicit null

if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
    Logger::Log("Enterprise API Security Error - Bad Request (002): Missing Authentication parameters [{$_EAI_MODULE}|{$_SERVER['REQUEST_METHOD']}|{$_SERVER['HTTP_HOST']}|{$_SERVER['REQUEST_URI']}]", Logger::SEVERITY['SECURITY_ERROR']);
    header(HTTP_UNAUTHORIZED);
    die ('Bad Request (002): Missing Authentication parameters');
}

$eaiAccount = EaiAccount::GetEaiAccountAfterAuthentication($_EAI_MODULE, $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
if (!$eaiAccount) {
    Logger::Log("Enterprise API Security Error - Bad Request (003): Invalid Authentication parameters [{$_EAI_MODULE}|{$_SERVER['REQUEST_METHOD']}|{$_SERVER['HTTP_HOST']}|{$_SERVER['REQUEST_URI']}]", Logger::SEVERITY['SECURITY_ERROR']);
    header(HTTP_NOT_FOUND);
    die ('Bad Request (003): Invalid Authentication parameters');
}
$companyObject = Company::GetCompany($eaiAccount->cid());
if ($companyObject->val('subdomain').'.teleskope.io' !== $_SERVER['HTTP_HOST']) {
    Logger::Log("Enterprise API Security Error - Bad Request (004): Invalid Authentication parameters [{$_EAI_MODULE}|{$_SERVER['REQUEST_METHOD']}|{$_SERVER['HTTP_HOST']}|{$_SERVER['REQUEST_URI']}]", Logger::SEVERITY['SECURITY_ERROR']);
    header(HTTP_NOT_FOUND);
    die ('Bad Request (004): Invalid Authentication parameters');
}

//User authenticated, set $_COMPANY
$_COMPANY = $companyObject;
EaiAuth::Init($eaiAccount);
EaiAuth::CheckIP();
