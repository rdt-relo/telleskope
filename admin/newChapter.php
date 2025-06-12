<?php
require_once __DIR__.'/head.php';

// There are four use cases here
// 1 - Generate form to add chapter -  If GET is true and chapterid is not provided - need gid and rid
// 2 - Generate form for update chapter - If GET is true and chapterid is *provided*
// 3 - Process Submit for Add chapter - If $_POST['submit'] is true and chapterid is not provided - need gid and cid
// 4 - Process Submit for Update Chapter - If $_POST['submit'] is true and chapterid is *provided*

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}
$groupid    = 0;
$chapterid  = 0;
$regionid   = 0;
//Data Validation

if (!isset($_GET['gid']) ||
    ($groupid = $_COMPANY->decodeId($_GET['gid']))<1 ||
    (isset($_GET['rid']) && ($regionid = $_COMPANY->decodeId($_GET['rid']))<1) ||
    (isset($_GET['cid']) && ($chapterid = $_COMPANY->decodeId($_GET['cid']))<1) ||
    ($group = Group::GetGroup($groupid)) == null) {
    header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
    exit();
}
$encGroupId = $_COMPANY->encodeId($groupid);

if (isset($_POST['submit'])) { // Add or Update
    $chaptername = 	Sanitizer::SanitizeGroupName($_POST['chapter_name']);
    $chaptercolor	= Sanitizer::SanitizeColor($_POST['chapter_color']);
    //$about 			= $_POST['about_chapter'];
    $branchids      = '0';

    if (isset($_POST['branchids'])) {
        $branchids_arr  = array();
        foreach ($_POST['branchids'] as $br){
            $branchids_arr[] = $_COMPANY->decodeId($br);
        }
        $branchids = implode(',', array_unique($branchids_arr)) ?? '0';
    }
    # Virtual Event Address
    $virtual_event_location = $_POST['virtual_event_location'] ?? '';
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';

    if ($chapterid){
        $group->updateChapter($chapterid, $chaptername,$chaptercolor,$branchids,$virtual_event_location,$latitude,$longitude);
        $_SESSION['updated'] = time();
    } else {
        $leads = '';
        $add =  $group->addChapter($chaptername,$chaptercolor,$leads,$branchids,$regionid,$virtual_event_location,$latitude,$longitude);
        if ($add==1){
            $_SESSION['added'] = time();
            $_SESSION['add-msg'] ="New ". $_COMPANY->getAppCustomization()['chapter']["name-short"]." was created successfully. A defult ". $_COMPANY->getAppCustomization()['chapter']["name-short"] ." was also created";
        } else {
            $_SESSION['added'] = time();
            $_SESSION['add-msg'] ="New ". $_COMPANY->getAppCustomization()['chapter']["name-short"]." was created successfully";
        }
    }
    Http::Redirect("manageChapters?gid={$encGroupId}");
}
// Else generate data needed to show the form.

$form = "Add";
$mybranches = '';
if ($chapterid) {
    // Get the branches already assigned to the chapter
    // Also get the regionid
    $form = "Edit";
    $edit = $group->getChapter($chapterid, true);
    if (!empty($edit)) {
        $regionid = $edit['regionids'];
        $mybranches = $edit['branchids'];
    } else { // Chapter id provided but chapter not found (in matching group)
        header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
        exit();
    }
}

$pagetitle = $group->val('groupname')." > ".$form. " ". $_COMPANY->getAppCustomization()['chapter']["name-short"];

$branches = $db->get("SELECT companybranches.`branchid`,companybranches.`branchname`,companybranches.`country`,companybranches.city, companybranches.state, regions.region FROM `companybranches` left join regions on companybranches.regionid=regions.regionid  WHERE companybranches.`companyid`='{$_COMPANY->id()}' AND companybranches.`regionid` IN (".$regionid.") AND companybranches.`isactive`='1'");

$usedbranches = $db->get("SELECT `branchids` FROM `chapters` WHERE chapters.companyid='{$_COMPANY->id()}' AND ( chapters.zoneid='{$_ZONE->id()}' AND `groupid`='{$groupid}' AND chapterid != '{$chapterid}' AND `branchids` != 0)");
$usedbranches_arr = array();
foreach ($usedbranches as $usedbranch) {
    $usedbranches_arr = array_merge($usedbranches_arr, explode(',', $usedbranch['branchids']));
}
$usedbranches_arr = Arr::IntValues($usedbranches_arr);

$branches_count = count($branches);

$mybranches_arr = Arr::IntValues(explode(',', $mybranches)); // this array will be empty when adding chapter (i.e. chapterid == 0)

for ($c = 0; $c < $branches_count; $c++) {
    $br_id = intval($branches[$c]['branchid']);
    if (in_array($br_id, $usedbranches_arr)) {
        $branches[$c]['alreadyUsed'] = $chapterid+100;
    } elseif (in_array($br_id, $mybranches_arr)) {
        $branches[$c]['alreadyUsed'] = $chapterid;
    } else {
        $branches[$c]['alreadyUsed'] = 0;
    }
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/newChapter.html');

?>
