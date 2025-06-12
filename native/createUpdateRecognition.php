<?php
include_once __DIR__.'/head.php';
$pageTitle = "Add|Update Recognition";

//Data Validation
if (
    !isset($_GET['groupid'],$_GET['recognitionid'],$_GET['recognition_type']) ||
    ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
    ($group = Group::GetGroup($groupid)) === null ||
    ($recognitionid = $_COMPANY->decodeId($_GET['recognitionid']))<0 || 
    ($recognition_type = $_COMPANY->decodeId($_GET['recognition_type']))<0
) {
    error_and_exit(HTTP_BAD_REQUEST);
}

// Authorization Check is user is a member

$recognition = $recognitionid ? Recognition::GetRecognition($recognitionid) : null; 
if (!$_USER->canViewContent($groupid)) {
    error_and_exit(HTTP_FORBIDDEN);
}

$formTitle = $recognition ? gettext("Update recognition") : gettext("New recognition");
$submitButton = gettext("Submit");   
$fontColors = $groupid > 0 ? json_encode(array($group->val('overlaycolor'),$group->val('overlaycolor2'))) : json_encode(array());

$custom_fields = Recognition::GetEventCustomFields();
$event_custom_fields = [];
if ($recognition) {
    $event_custom_fields = json_decode($recognition->val('custom_fields') ?? '', true) ?? [];
}

include __DIR__ . '/views/createUpdateRecognition_html.php';
