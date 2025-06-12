<?php
require_once __DIR__.'/head.php';
// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
    header("HTTP/1.1 403 Forbidden (Access Denied)");
    exit();
}
//Data Validation
if (!isset($_GET['gid']) || ($groupid = $_COMPANY->decodeId($_GET['gid']))<1) {
	header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
	exit();
}
$group = Group::GetGroup($groupid);
$groupName = $group->val('groupname');
$pagetitle = $groupName." Join Restrictions";
$subheading = "Set restrictions to ensure only users who meet the criteria specified below can join the ".$groupName;

// Get the restrictions. If empty, show the default 
if (isset($_POST['submit'])) {
    $catalog_categories = array_values(UserCatalog::GetAllCatalogCategories());
    $restrictions = array();
    foreach($catalog_categories as $category){
        $categoryPoststr = str_replace(' ','_',$category); // Convert _ to space.
        if (isset($_POST[$categoryPoststr])){
            $logicType = (int) $_POST[$categoryPoststr];
            if ($logicType == 0) {
                continue; // Do not save ignore logic type criteria
            }
            $keys = array();
            if (isset($_POST[$categoryPoststr.'_val'])){
                $keys =  (array) $_POST[$categoryPoststr.'_val'];
            }
            $restrictions[$category]  = array('type'=>$logicType,'keys'=>$keys); // Do not use categoryname_str here
        }
    }
    // Add the restriction in group > attribute.
    $addRestricitons = $group->applyGroupMemberRestrictions($restrictions);

    if (!empty($_POST['process_existing_groupmembers'])) {
        $group->removeNonCompliantGroupMembers('GROUP_RESTRICTIONS_UPDATE');
    }

    if($addRestricitons){
        $_SESSION['updated'] = time();
        Http::Redirect('group');
    }
}

$catalog_categories = array_values(UserCatalog::GetAllCatalogCategories());

$editRestrictions = $group->getGroupMemberRestrictions();

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/member_restrictions.html');

?>
