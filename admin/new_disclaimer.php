<?php
require_once __DIR__.'/head.php';
$pagetitle = "Add a disclaimer";
$formTitle = "Add a disclaimer";

if (!$_COMPANY->getAppCustomization()['disclaimer_consent']['enabled']) {
    header(HTTP_FORBIDDEN);
    echo "403 Forbidden (Access Denied)";
    exit();
}
// Todo we need to pull list of disclaimers by invocation type, see GetAllDisclaimersInZone function has been updated.
$allDisclaimers = Disclaimer::GetAllDisclaimersInZone();
$existHook = array();
foreach($allDisclaimers as $disclaimer){
    $existHook[] = $disclaimer->val('hookid');
}
   
$disclaimerHooks = Disclaimer::DISCLAIMER_HOOK_TRIGGERS;
// Remove inapplicable disclaimers
if (!$_COMPANY->getAppCustomization()['event']['enabled']) { unset ($disclaimerHooks['EVENT_CREATE_BEFORE']); unset ($disclaimerHooks['EVENT_PUBLISH_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['post']['enabled']) { unset ($disclaimerHooks['POST_CREATE_BEFORE']); unset ($disclaimerHooks['POST_PUBLISH_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['discussions']['enabled']) { unset ($disclaimerHooks['DISCUSSION_CREATE_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['newsletters']['enabled']) { unset ($disclaimerHooks['NEWSLETTER_CREATE_BEFORE']); unset ($disclaimerHooks['NEWSLETTER_PUBLISH_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['teams']['enabled']) { unset ($disclaimerHooks['TEAMS__CIRCLE_CREATE_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['surveys']['enabled']) { unset ($disclaimerHooks['SURVEY_CREATE_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['budgets']['enabled'] || !$_COMPANY->getAppCustomization()['budgets']['enable_budget_requests']) { unset ($disclaimerHooks['BUDGET_REQUEST_CREATE_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['budgets']['enabled'] || !$_COMPANY->getAppCustomization()['budgets']['enable_budget_expenses']) {unset($disclaimerHooks['BUDGET_EXPENSE_CREATE_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['messaging']['enabled']) { unset ($disclaimerHooks['DIRECT_MESSAGE_CREATE_BEFORE']);}
$allowedLanguages = $_COMPANY->getValidLanguages();

$disclaimerid = 0;
$editDisclaimer = null;
$disclaimers = array();
$usedLanguages = array('en');
if (isset($_GET['disclaimerid'])){
	$formTitle = "Update Disclaimer";
	$disclaimerid = $_COMPANY->decodeId($_GET['disclaimerid']);
	$editDisclaimer = Disclaimer::GetDisclaimerById($disclaimerid);
    $hookName =  array_search($editDisclaimer->val('hookid'),$disclaimerHooks);     
    $disclaimers = json_decode($editDisclaimer->val('disclaimer'), true) ?? array();
    foreach ($disclaimers as $key=>$val) { 
        array_push($usedLanguages,$key);
    }
          
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/new_disclaimer.html');

