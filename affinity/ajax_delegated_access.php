<?php

define('AJAX_CALL', 1); // Define AJAX call for error handling
require_once __DIR__ . '/head.php';

if (isset($_GET['searchUsersForDelegatedAccess']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    //Data Validation
    if (
        !$_COMPANY->getAppCustomization()['profile']['allow_delegated_access']
        || $_USER->isDelegatedAccessUser()
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // SearchUsersByKeyword is safe method and can handle unclean data, so no need for raw2clean.
    $activeusers =  User::SearchUsersByKeyword($_GET['search_keyword_user']);
    require __DIR__ . '/views/search_users_for_delegated_access.html.php';
}
elseif (isset($_GET['addNewDelegatedAccess']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    //Data Validation
    if (
        !$_COMPANY->getAppCustomization()['profile']['allow_delegated_access']
        || $_USER->isDelegatedAccessUser()
        || empty($_POST['grantee_userid'])
        || (($grantee_userid = $_COMPANY->decodeId($_POST['grantee_userid'])) < 1)
        || (($grantee_user = User::GetUser($grantee_userid)) === NULL)
        || (!$grantee_user->isActive())
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (DelegatedAccess::GetDelegatedAccessByGrantorGranteeUserId(grantee_userid: $grantee_userid, grantor_userid: $_USER->id())) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Access has already been granted to this user'), gettext('Error'));
    }

    DelegatedAccess::CreateNewDelegatedAccess($grantee_userid, '');
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Successfully granted user access'), gettext('Success'));
}
elseif (isset($_GET['revokeDelegatedAccess']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    //Data Validation
    if (
        !$_COMPANY->getAppCustomization()['profile']['allow_delegated_access']
        || $_USER->isDelegatedAccessUser()
        || empty($_POST['delegated_access_id'])
        || (($delegated_access_id = $_COMPANY->decodeId($_POST['delegated_access_id'])) < 1)
        || (($delegated_access = DelegatedAccess::GetDelegatedAccessById($delegated_access_id)) === NULL)
        || ((int) $delegated_access->val('grantor_userid') !== (int) $_USER->id())
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $delegated_access->deleteIt('');

    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Successfully revoked user access'), gettext('Success'));
}
else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}
