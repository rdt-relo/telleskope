<?php
require_once __DIR__.'/head.php';
$pagetitle = "Disclaimers";

if (!$_COMPANY->getAppCustomization()['disclaimer_consent']['enabled']) {
    header(HTTP_FORBIDDEN);
    echo "403 Forbidden (Access Denied)";
    exit();
}

// Todo we need to pull list of disclaimers by invocation type, see GetAllDisclaimersInZone function has been updated.
$disclaimers = Disclaimer::GetAllDisclaimersInZone();

$disclaimerHooks = Disclaimer::DISCLAIMER_HOOK_TRIGGERS;
// Remove inapplicable disclaimers - TODO: add disclaimers here as well
if (!$_COMPANY->getAppCustomization()['event']['enabled']) { unset ($disclaimerHooks['EVENT_CREATE_BEFORE']); unset ($disclaimerHooks['EVENT_PUBLISH_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['post']['enabled']) { unset ($disclaimerHooks['POST_CREATE_BEFORE']); unset ($disclaimerHooks['POST_PUBLISH_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['discussions']['enabled']) { unset ($disclaimerHooks['DISCUSSION_CREATE_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['newsletters']['enabled']) { unset ($disclaimerHooks['NEWSLETTER_CREATE_BEFORE']); unset ($disclaimerHooks['NEWSLETTER_PUBLISH_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['teams']['enabled']) { unset ($disclaimerHooks['TEAMS__CIRCLE_CREATE_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['surveys']['enabled']) { unset ($disclaimerHooks['SURVEY_CREATE_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['budgets']['enable_budget_requests']) { unset ($disclaimerHooks['BUDGET_REQUEST_CREATE_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['budgets']['enable_budget_expenses']) {unset($disclaimerHooks['BUDGET_EXPENSE_CREATE_BEFORE']);}
if (!$_COMPANY->getAppCustomization()['messaging']['enabled']) {unset($disclaimerHooks['DIRECT_MESSAGE_CREATE_BEFORE']);}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/disclaimers.html');
?>
