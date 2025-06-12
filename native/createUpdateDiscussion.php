<?php
include_once __DIR__.'/head.php';
$pageTitle = "Add|Update Discussion";

//Data Validation
if (
    ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
    ($group = Group::GetGroup($groupid)) === null ||
    ($discussionid = $_COMPANY->decodeId($_GET['discussionid']))<0
) {
    error_and_exit(HTTP_BAD_REQUEST);
}

// Authorization Check
// Allow anyone with create permissions to see this form.
$discussion = null;
if ($discussionid){
    $discussion = Discussion::GetDiscussion($discussionid);

    if ($_USER->id()!=$discussion->val('createdby') && !$_USER->canUpdateContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid'),$discussion->val('isactive'))) {
        error_and_exit(HTTP_FORBIDDEN);
    }
} else {
    $discussionSettings = $group->getDiscussionsConfiguration();
    if (!($discussionSettings['who_can_post']=='members' && $_USER->isGroupMember($groupid)) && !$_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        error_and_exit(HTTP_FORBIDDEN);
    }
}
$global = 0;
$chapters = array();
$channels = array();
$selectedChapterIds = array();
$selectedChannelId = 0;

$formTitle = gettext("New Discussion");
$submitButton = gettext("Submit");
$selectedChapterIds = array();
if ($discussionid){
    $formTitle = gettext("Update Discussion");
    $submitButton = gettext("Submit Update");
    $selectedChapterIds = explode(',',$discussion->val('chapterid'));
    $selectedChannelId = $discussion->val('channelid');

}
if($_COMPANY->getAppCustomization()['chapter']['enabled']){
    $chapters = Group::GetChapterListByRegionalHierachies($groupid);
}
if($_COMPANY->getAppCustomization()['channel']['enabled']){
    $channels= Group::GetChannelList($groupid);
}
$displayStyle = 'row';
$fontColors = $groupid > 0 ? json_encode(array($group->val('overlaycolor'),$group->val('overlaycolor2'))) : json_encode(array());
$discussionSettings = $group->getDiscussionsConfiguration();

include __DIR__ . '/views/createUpdateDiscussion_html.php';
