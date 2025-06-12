<?php
if (Env::IsAdminPanel()) {
    require_once __DIR__ . '/../admin/head.php';
} else {
    require_once __DIR__ . '/../affinity/head.php';
}


if (0) {
    // Dummy if to make every thing else as elseif
}
elseif (isset($_GET['addDynamicList']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
	$id = $_COMPANY->decodeId($_POST['listId']);
    $check = $db->checkRequired(array('List Name'=>@$_POST['list_name'],'List Description'=>@$_POST['list_description']));
	if($check){
		$error = "Error: Not a valid input on $check.";
	} else {

		// Scope will be used in the future, for now it is hardcoded to zone
		// $scope = 'zone'; $_POST['scope'] == 'zone' ? 'zone' : 'group';
		$scope = in_array($_POST['scope'], ['zone', 'group', 'zone_and_group']) ? $_POST['scope'] : 'group';

		$list_name = $_POST['list_name'];
		$list_description = substr($_POST['list_description'], 0, 300);
		$criteria = array();
		$catalog_categories = array_values(UserCatalog::GetAllCatalogCategories());
        foreach($catalog_categories as $category){
            $categoryPoststr = str_replace(' ','_',$category); // Convert _ to space.
            if (!empty($_POST[$categoryPoststr])){
				$comparator = $_POST[$categoryPoststr];
                $keys = array();
                if (isset($_POST[$categoryPoststr.'_val'])){
                    $keys =  (array) $_POST[$categoryPoststr.'_val'];
                }
				if (!empty($keys))
                	$criteria[$category]  = array('comparator'=>$comparator,'keys'=>$keys); // Do not use categoryname_str here
            }
        }

		if (DynamicList::AddUpdateList($id, $scope, $list_name, $list_description, $criteria) ) {
			$success = $id ? "List updated successfully" : "List created successfully";
			AjaxResponse::SuccessAndExit_STRING(1, '', $success, 'Success');
        }else{
            AjaxResponse::SuccessAndExit_STRING(0, '', "Something went wrong. Please try again later.", 'Error!');
		}
	}
}
elseif (isset($_GET['refreshDynamicList']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
	if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<0) {
		header(HTTP_BAD_REQUEST);
		exit();
	}

	$lists = [];
	if ($groupid){
		$lists = DynamicList::GetAllLists('group',true);
	} else {
		$lists = DynamicList::GetAllLists('zone',true);
	}

	if ($lists) {
		$updatedListData = [];
		foreach ($lists as $list) {
			$updatedListData[] = [
				'list_id' => $_COMPANY->encodeId($list->val('listid')),
				'list_name' => $list->val('list_name')
			];
		}
		echo json_encode($updatedListData);
	}else{
		AjaxResponse::SuccessAndExit_STRING(0, '', "Something went wrong. Please Reload the page and select newly addded dynamic list.", 'Error!');
	}
}
elseif (isset($_GET['activateDeactivateDynamicList']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

	if (
		($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($listid = (int) $_COMPANY->decodeId($_POST['listid']))<1 ||
        ($list = DynamicList::GetList($listid)) === null ||
        !in_array($_POST['action'],['activate','deactivate'])
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check. As this is common module, checking for admins and group leads
    $zoneAccess = $list->isZoneScope() && $_USER->canManageCompanySettings();
    $groupAccess = $list->isGroupScope() && ($_USER->canCreateContentInGroup($groupid) || $_USER->canPublishContentInGroup($groupid) || $_USER->canManageGroup($groupid));
    if (!($zoneAccess || $groupAccess)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($_POST['action'] == 'activate') {
        $list->activate();
        AjaxResponse::SuccessAndExit_STRING(1, '', "Dynamic List activated successfully", 'Success');

    } else {
        $list->deactivate();
        AjaxResponse::SuccessAndExit_STRING(1, '', "Dynamic List deactivated successfully", 'Success');
    }

}
else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}