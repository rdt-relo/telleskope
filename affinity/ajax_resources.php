<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__ .'/head.php'; //Common head.php includes destroying Session for timezone so defining INDEX_PAGE. Need head for logging and protecting
global $_COMPANY, $_USER, $_ZONE;
///ajax_resources

if (isset($_GET['getGroupResources'])){
    //Data Validation
    $chapterid = 0;
    $channelid = 0;
    $folder = null;
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getGroupResources']))<1 ||
    ($group = Group::GetGroup($groupid)) === null ||
    (isset($_GET['chapterid']) && ($chapterid = $_COMPANY->decodeId($_GET['chapterid'])) < 0) ||
    (isset($_GET['channelid']) && ($channelid = $_COMPANY->decodeId($_GET['channelid'])) < 0)
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
	}
    $_SESSION['resource_sortby'] = $_SESSION['resource_sortby'] ?? 'default';
    $pageTitle = gettext('Resources');
    $canCreateOrPublishContentInScope = $_USER->canCreateOrPublishContentInScopeCSV($groupid, 0, 0);
    $canCreateFolder = $_USER->canCreateContentInGroupSomething($groupid) || $_USER->canPublishContentInGroupSomething($groupid);
    $canCreateFile = $canCreateOrPublishContentInScope;

    $folderid = 0;
    $is_resource_lead_content = 0;
    include(__DIR__ . "/views/templates/resources.template.php");
}

elseif(isset($_GET['getResourceChildData'])){
    $chapterid = 0;
    $channelid = 0;
    $folder = null;
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['getResourceChildData']))<1 ||
        ($folderid = $_COMPANY->decodeId($_GET['folderid'])) < 0 ||
        ($folderid && ($folder = Resource::GetResource($folderid)) === null) ||
        (isset($_GET['chapterid']) && ($chapterid = $_COMPANY->decodeId($_GET['chapterid'])) < 0) ||
        (isset($_GET['channelid']) && ($channelid = $_COMPANY->decodeId($_GET['channelid'])) < 0)
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $_SESSION['resource_sortby'] = $_SESSION['resource_sortby'] ?? 'default';
    
    $folder_chapterid = $folder ? $folder->val('chapterid') : 0;
    $folder_channelid = $folder ? $folder->val('channelid') : 0;

    $canCreateOrPublishContentInScope = $_USER->canCreateOrPublishContentInScopeCSV($groupid, $folder_chapterid,$folder_channelid);
    $canCreateFolder = $folderid
                            ? $canCreateOrPublishContentInScope // User is in folder scope
                            : ($_USER->canCreateContentInGroupSomething($groupid) || $_USER->canPublishContentInGroupSomething($groupid)) // User is at the root scope
                        ;
    $canCreateFile = $canCreateOrPublishContentInScope;

    $is_resource_lead_content = isset($_GET['is_resource_lead_content']) ? (int) $_GET['is_resource_lead_content'] : 0;

    include(__DIR__ . "/views/templates/resources_table.template.php");
}

elseif(isset($_GET['submitFolderIcon']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $resource = null;
    // Start of Basic Data Validation
    $validator = new Rakit\Validation\Validator;
    $validation = $validator->validate($_POST, [
        'resource_type'         => 'required|integer|in:1,2,3',
        'resource_url'          => 'required_if:resource_type,1|url:http,https|max:1000',
    ]);
    if ($validation->fails()) {
        // handling errors
        $errors = array_values($validation->errors()->firstOfAll());
        // Return error message
        AjaxResponse::SuccessAndExit_STRING(0, '', implode(', ', $errors), gettext('Error'));
    }
    
    if (($groupid = $_COMPANY->decodeId($_GET['submitFolderIcon']))<1 ||
        ($parent_id = $_COMPANY->decodeId($_POST['parent_id'])) < 0 ||
        ($resource_id = $_COMPANY->decodeId($_POST['resource_id'])) < 0 ||
        ($resource_id > 0 && ($resource = Resource::GetResource($resource_id)) === null)
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $resource_type = (int)$_POST['resource_type'];
    $resource_typename = (array('1'=>'Link','2'=>'File','3'=>'Folder'))[strval($resource_type)];
    $result = 0;
    $is_resource_lead_content = isset($_POST['is_resource_lead_content']) ? $_COMPANY->decodeId($_POST['is_resource_lead_content']):0;


    // Create call flow.
    $section = 0;
    $chapterid = 0;
    $channelid = 0;
    if ($parent_id){
        $parent = Resource::GetResource($parent_id);
        $groupid = $parent->val('groupid');
        $chapterid = $parent->val('chapterid');
        $channelid = $parent->val('channelid');
    } elseif (!empty($_POST['section'])){
        // For root folder types, check if the folder is being created in chapter or channel context
        $section = $_COMPANY->decodeId($_POST['section']);
        if ($section  == '1'){
            $chapterid = $_COMPANY->decodeId($_POST['group_chapter_channel_id']);
        } else if ($section == '2'){
            $channelid = $_COMPANY->decodeId($_POST['group_chapter_channel_id']);
        }
    }
   
    $responseData = array('parent_id'=>$_COMPANY->encodeId($parent_id),'groupid'=>$_COMPANY->encodeId($groupid),'chapterid'=>$_COMPANY->encodeId($chapterid),'channelid'=>$_COMPANY->encodeId($channelid));
    // Fetch the resource based on resource type
    if ($resource) {
        // Update call flow
        $link = $_POST['resource_url'] ?? '';
        // Authorization Check
        if (!$_USER->canCreateOrPublishContentInScopeCSV($resource->val('groupid'),$resource->val('chapterid'),$resource->val('channelid'))) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        $validator2 = new Rakit\Validation\Validator;
        $validation2 = $validator2->validate($_POST + $_FILES, [
            'resource_file'         => 'uploaded_file:100,1M|mimes:png,jpeg,jpg',
        ]);
        if ($validation2->fails()) {
            $errors = array_values($validation2->errors()->firstOfAll());
            AjaxResponse::SuccessAndExit_STRING(0, '', implode(', ', $errors), gettext('Error'));
        }
        if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] == UPLOAD_ERR_OK) {
            $result = $resource->updateFolderIcon($_FILES['resource_file']);   
        }else{
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please upload a file'), gettext('Error'));
        }
       
        if ($result){
            Resource::UpdateTopicUsageLogs($resource->id(), TopicUsageLogsActionType::UPDATED);
            AjaxResponse::SuccessAndExit_STRING(1, $responseData, sprintf(gettext("%s updated successfully."),$resource_typename), gettext('Success'));
        }

    } 
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Error'));
}
elseif(isset($_GET['addUpdateGroupResource']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $resource = null;
    // Start of Basic Data Validation
    $validator = new Rakit\Validation\Validator;
    $validation = $validator->validate($_POST, [
        'resource_name'         => 'required|min:3|max:128', //Alpha numeric with spaces and . _ -
        'resource_type'         => 'required|integer|in:1,2,3',
        'resource_url'          => [
                                        'required_if:resource_type,1',
                                        'max:1000',
                                        'callback' => function($val){
                                            if(!filter_var($val, FILTER_VALIDATE_EMAIL) && !filter_var($val, FILTER_VALIDATE_URL)){
                                                return ":attribute must be a valid url or email address.";
                                            }
                                        }
                                ],
        'resource_description'  => 'max:255'
    ]);
    if ($validation->fails()) {
        // handling errors
        $errors = array_values($validation->errors()->firstOfAll());
        // Return error message
        AjaxResponse::SuccessAndExit_STRING(0, '', implode(', ', $errors), gettext('Error'));
    }
    // End of Basic Data Validation

    if (($groupid = $_COMPANY->decodeId($_GET['addUpdateGroupResource']))<1 ||
        ($parent_id = (int)$_COMPANY->decodeId($_POST['parent_id'])) < 0 ||
        ($resource_id = (int)$_COMPANY->decodeId($_POST['resource_id'])) < 0 ||
        ($resource_id > 0 && ($resource = Resource::GetResource($resource_id)) === null)
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

	$resource_description = $_POST['resource_description'];
    $resource_name = $_POST['resource_name'];
    $resource_type = (int)$_POST['resource_type'];
    $resource_typename = (array('1'=>'Link','2'=>'File','3'=>'Folder'))[strval($resource_type)];
    $result = 0;
    $is_resource_lead_content = isset($_POST['is_resource_lead_content']) ? $_COMPANY->decodeId($_POST['is_resource_lead_content']):0;

    // Initialize resource groupid, chapterid and channelid.
    // If the resource is getting updated then the chapterid, channelid from parent will be used as initial value
    // If the reource does not have a parent, i.e. it is at the root level then the posted value will be used.
    // from the parent
    $section = 0;
    $chapterid = 0;
    $channelid = 0;
    if ($parent_id){
        $parent = Resource::GetResource($parent_id);
        $groupid = $parent->val('groupid');
        $chapterid = $parent->val('chapterid');
        $channelid = $parent->val('channelid');
    } elseif (!empty($_POST['section'])){
        // For root folder types, check if the folder is being created in chapter or channel context
        $section = $_COMPANY->decodeId($_POST['section']);
        if ($section  == '1'){
            $chapterid = $_COMPANY->decodeId($_POST['group_chapter_channel_id']);
        } else if ($section == '2'){
            $channelid = $_COMPANY->decodeId($_POST['group_chapter_channel_id']);
        }
    }
   
    $responseData = array('parent_id'=>$_COMPANY->encodeId($parent_id),'groupid'=>$_COMPANY->encodeId($groupid),'chapterid'=>$_COMPANY->encodeId($chapterid),'channelid'=>$_COMPANY->encodeId($channelid));
    // Fetch the resource based on resource type
    if ($resource) {
        // Update call flow
        $link = $_POST['resource_url'] ?? '';
        // Authorization Check
        if (!$_USER->canCreateOrPublishContentInScopeCSV($resource->val('groupid'),$resource->val('chapterid'),$resource->val('channelid'))
            ||
            ( // If changing chapter or channel then make sure the user can publish in the selected chapter or channel
                ($resource->val('chapterid') != $chapterid || $resource->val('channelid') != $channelid) &&
                !$_USER->canCreateOrPublishContentInScopeCSV($resource->val('groupid'),$chapterid,$channelid)
            )
        ) {
            header(HTTP_FORBIDDEN);
            exit();
        }

        $result =
            $resource->updateResource($resource_name, $resource_description, $link, $_FILES['resource_file'] ?? null) &&
            $resource->updateResourceScope($chapterid,$channelid);

        if ($result){
            Resource::UpdateTopicUsageLogs($resource->id(), TopicUsageLogsActionType::UPDATED);
            AjaxResponse::SuccessAndExit_STRING(1, $responseData, sprintf(gettext("%s updated successfully."),$resource_typename), gettext('Success'));
        }

    } else {

        // Authorization Check
        if (!$_USER->canCreateOrPublishContentInScopeCSV($groupid,$chapterid,$channelid)) {
            header(HTTP_FORBIDDEN);
            exit();
        }

        if ($resource_type === Resource::RESOURCE_TYPE['RESOURCE_FILE']) {
            $validator2 = new Rakit\Validation\Validator;
            $validation2 = $validator2->validate($_POST + $_FILES, [
                'resource_file'         => 'uploaded_file:100,50M|mimes:pdf,xls,xlsx,ppt,pptx,doc,docx,png,jpeg,jpg',
            ]);
            if ($validation2->fails()) {
                $errors = array_values($validation2->errors()->firstOfAll());
                AjaxResponse::SuccessAndExit_STRING(0, '', implode(', ', $errors), gettext('Error'));
            }
            $result = Resource::CreateNewFile($groupid, $resource_name, $resource_description, $parent_id, $_FILES['resource_file'],$chapterid,$channelid, $is_resource_lead_content);
        } elseif ($resource_type === Resource::RESOURCE_TYPE['RESOURCE_LINK']) {
            $link = $_POST['resource_url'];
            $result = Resource::CreateNewLink($groupid, $resource_name, $resource_description, $parent_id, $link,$chapterid,$channelid, $is_resource_lead_content);
        } elseif ($resource_type === Resource::RESOURCE_TYPE['RESOURCE_FOLDER']) {
            $result = Resource::CreateNewFolder($groupid, $resource_name, $resource_description, $parent_id,$chapterid,$channelid, $is_resource_lead_content);
        }

        if ($result){
            Resource::UpdateTopicUsageLogs($result, TopicUsageLogsActionType::CREATED);
            AjaxResponse::SuccessAndExit_STRING(1, $responseData, sprintf(gettext("%s added successfully."),$resource_typename), gettext('Success'));
        }
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Error'));
}
elseif(isset($_GET['deleteResource']) ){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($resource_id =$_COMPANY->decodeId($_POST['resource_id']))<1 ||
        ($resource = Resource::GetResource($resource_id)) === null ||
        $groupid != $resource->val('groupid')
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canCreateOrPublishContentInScopeCSV($resource->val('groupid'),$resource->val('chapterid'),$resource->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
	}

	$status =  $resource->deleteIt();
  $log_result =  Resource::DeleteTopicUsageLogs($resource_id);

    if ($status == 1){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Deleted successfully'), gettext('Success'));
    } elseif($status == -1){
        AjaxResponse::SuccessAndExit_STRING(-1, '', gettext('Unable to delete this folder. Note: only empty folders can be deleted. If you wish to delete a folder, you must first delete or move the contents of the folder.'), gettext('Error'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Unable to delete'), gettext('Error'));
    }
    
}

elseif (isset($_GET['pinUnpinResource']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($resource_id = $_COMPANY->decodeId($_POST['resource_id']))<1 ||
        ($resource = Resource::GetResource($resource_id))  === NULL ||
        (($parent_id = $_COMPANY->decodeId($_POST['parent_id']))<0) ||
        !$_COMPANY->getAppCustomization()['resources']['pinning']['enabled']
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canCreateOrPublishContentInScopeCSV($resource->val('groupid'),$resource->val('chapterid'),$resource->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $type = $_POST['type'] == 2 ? $_POST['type'] : 1; 
    $responeCode = 1;
    if($resource->pinUnpinResource($type)){
        if ($parent_id){
            $responeCode = 2;
        }
    } 
    // Return 0 encoded id becuase it will assined to chapter and channel. This will help on sort pinned resources.
    $zeroEncodedid = $_COMPANY->encodeId(0);
    AjaxResponse::SuccessAndExit_STRING($responeCode, $zeroEncodedid, gettext('Updated successfully.'), gettext('Success'));
}
elseif (isset($_GET['moveResourceIntoFolder']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    $drop_resource = null;
    if (
        ($drop_resource_id = $_COMPANY->decodeId($_POST['drop_id']))<0 ||
        ($drag_resource_id = $_COMPANY->decodeId($_POST['drag_id']))<1 ||
        ($drop_resource_id && ($drop_resource = Resource::GetResource($drop_resource_id))  === NULL) ||
        ($drag_resource = Resource::GetResource($drag_resource_id))  === NULL ||
        ($parent_id = $_COMPANY->decodeId($_POST['parent_id']))<0
        ) {
        //header(HTTP_BAD_REQUEST); We are not showing bad request, silently ignoring
        echo -2;
        exit('');
    }

    // Authorization Check
    if (
        !$_USER->canCreateOrPublishContentInScopeCSV($drag_resource->val('groupid'),$drag_resource->val('chapterid'),$drag_resource->val('channelid')) ||
        !$_USER->canCreateOrPublishContentInScopeCSV($drop_resource ? $drop_resource->val('groupid') : 0, $drop_resource ? $drop_resource->val('chapterid') : 0, $drop_resource ? $drop_resource->val('channelid') : 0)
    ) {
        echo -3;
        exit();
    }

    if ($drag_resource->moveResourceIntoFolder($drop_resource_id) === -1) {
        echo -1;
        exit();
    }

    echo $parent_id;
    exit();
}
elseif (isset($_GET['openResourceModal']) && isset($_GET['resource_id'])){

    $chapterid=0;
    $channelid=0;
    $resource = null;
    $parent = null;
    $is_resource_lead_content = 0;
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['openResourceModal']))<1 ||
        ($resource_id = $_COMPANY->decodeId($_GET['resource_id']))<0 ||
        ($resource_id && ($resource = Resource::GetResource($resource_id)) === null) ||
        ($parent_id = $_COMPANY->decodeId($_GET['parent_id']))<0 ||
        ($parent_id && ($parent = Resource::GetResource($parent_id)) === null) ||
        ($group = Group::GetGroup($groupid)) === null ||
        (isset($_GET['chapterid']) && ($chapterid = $_COMPANY->decodeId($_GET['chapterid'])) < 0) ||
        (isset($_GET['channelid']) && ($channelid = $_COMPANY->decodeId($_GET['channelid'])) < 0)
        
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if ($resource && !$_USER->canCreateOrPublishContentInScopeCSV($resource->val('groupid'),$resource->val('chapterid'),$resource->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($parent && !$_USER->canCreateOrPublishContentInScopeCSV($parent->val('groupid'),$parent->val('chapterid'),$parent->val('channelid'))) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Your role in this organization does not have the needed permissions to complete this action. Contact your %s leader directly for support.'),$_COMPANY->getAppCustomization()['group']['name-short']), gettext('Error'));
    }

    $resource_type = $resource ? $resource->val('resource_type') : (int) $_GET['resource_type'];
    $is_resource_lead_content = isset($_GET['is_resource_lead_content']) ? (int) $_GET['is_resource_lead_content'] : 0;

    if (!$resource && !$parent && !$_USER->canCreateOrPublishContentInScopeCSV($groupid,0,0) && $resource_type != Resource::RESOURCE_TYPE['RESOURCE_FOLDER']) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Your role in this organization does not have the needed permissions to complete this action. Contact your %s leader directly for support.'),$_COMPANY->getAppCustomization()['group']['name-short']), gettext('Error'));
    }

    if ($resource_type == Resource::RESOURCE_TYPE['RESOURCE_LINK']){
        $modalTitle = $resource ? gettext('Update Link') : gettext('Create New Link');
    } elseif($resource_type == Resource::RESOURCE_TYPE['RESOURCE_FOLDER']){
        $modalTitle = $resource ? gettext('Update Folder') : gettext('Create New Folder');
    } elseif($resource_type == Resource::RESOURCE_TYPE['RESOURCE_FILE']){
        $resource_type = Resource::RESOURCE_TYPE['RESOURCE_FILE'];
        $modalTitle = $resource ? gettext('Update Resource') : gettext('Upload Resource');
    } else {
      $resource_type = 4;
      $modalTitle = gettext('Bulk File Upload');
    }
    $chapters = Group::GetChapterList($groupid);
    $channels= Group::GetChannelList($groupid);
    include(__DIR__ . "/views/templates/resource_add_update.template.php");

}
elseif (isset($_GET['updateFolderIconModal']) && isset($_GET['resource_id'])){
    $chapterid=0;
    $channelid=0;
    $resource = null;
    $parent = null;
    $is_resource_lead_content = 0;
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['updateFolderIconModal']))<1 ||
        ($resource_id = $_COMPANY->decodeId($_GET['resource_id']))<0 ||
        ($resource_id && ($resource = Resource::GetResource($resource_id)) === null) ||
        ($parent_id = $_COMPANY->decodeId($_GET['parent_id']))<0 ||
        ($parent_id && ($parent = Resource::GetResource($parent_id)) === null) ||
        ($group = Group::GetGroup($groupid)) === null ||
        (isset($_GET['chapterid']) && ($chapterid = $_COMPANY->decodeId($_GET['chapterid'])) < 0) ||
        (isset($_GET['channelid']) && ($channelid = $_COMPANY->decodeId($_GET['channelid'])) < 0)
        
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if ($resource && !$_USER->canCreateOrPublishContentInScopeCSV($resource->val('groupid'),$resource->val('chapterid'),$resource->val('channelid'))) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($parent && !$_USER->canCreateOrPublishContentInScopeCSV($parent->val('groupid'),$parent->val('chapterid'),$parent->val('channelid'))) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Your role in this organization does not have the needed permissions to complete this action. Contact your %s leader directly for support.'),$_COMPANY->getAppCustomization()['group']['name-short']), gettext('Error'));
    }
    
    $resource_type = $resource ? $resource->val('resource_type') : (int) $_GET['resource_type'];
    $is_resource_lead_content = isset($_GET['is_resource_lead_content']) ? (int) $_GET['is_resource_lead_content'] : 0;

    if($resource_type == Resource::RESOURCE_TYPE['RESOURCE_FOLDER']){
        $modalTitle = gettext('Update Folder Icon');
    }

    $chapters = Group::GetChapterList($groupid);
    $channels= Group::GetChannelList($groupid);
    include(__DIR__ . "/views/templates/resource_update_icon.template.php");
}

elseif (isset($_GET['updateTopicUsageLogs']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($resource_id = $_COMPANY->decodeId($_POST['resource_id']))<0 
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $action_type = TopicUsageLogsActionType::tryFrom($_POST['action']);
    if ($action_type){
        Resource::UpdateTopicUsageLogs($resource_id, $action_type);
    }
    echo 1;
    exit();
    
}

elseif (isset($_GET['showStatistics']) && isset($_GET['resource_id'])) {
    if (($resource_id = $_COMPANY->decodeId($_GET['resource_id']))<0 ||
        ($resource_id && ($resource = Resource::GetResource($resource_id)) === null)
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $statistics = Resource::getStatistics($resource_id);

    $response_array = array(
        "status" => "success",
        "title" => gettext("Resource Statistics"),
        "data" => array(
        gettext("Added On") => $statistics["added_on"] !='N/A' ? $_USER->formatUTCDatetimeForDisplayInLocalTimezone($statistics["added_on"], true, true, false) : $statistics["added_on"],
        gettext("Added By")  => $statistics["added_by"],
        gettext("Total Updates") => $statistics["total_updates"],
        gettext("Last Updated On") => $statistics["last_updated_on"] !='N/A' ? $_USER->formatUTCDatetimeForDisplayInLocalTimezone($statistics["last_updated_on"], true, true, false) : $statistics["last_updated_on"],
        gettext("Last Update By") => $statistics["last_updated_by"],
        $resource->isLink() ? gettext("Total Visits") : ($resource->isFile() ? gettext("Total Downloads") : gettext("Total Opens"))  => $statistics["total_downloads"],
        $resource->isLink() ? gettext("Unique Visits") : ($resource->isFile() ? gettext("Unique Downloads") : gettext("Unique Opens")) => $statistics["unique_downloads"]
        )
  );
    if ($resource->isFolder()) {
        unset($response_array['data']['Total Opens']);
        unset($response_array['data']['Unique Opens']);
    }

  echo json_encode($response_array);
  exit();
}

elseif (isset($_GET['manageLeadResources'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    $chapterid = 0;
    $channelid = 0;
    $folder = null;
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 || ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (! $_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
	}
    //echo basename($_SERVER['PHP_SELF']); die();
    
    $canCreateOrPublishContentInScope = $_USER->canCreateOrPublishContentInScopeCSV($groupid, 0, 0);
    $canCreateFolder = $_USER->canCreateContentInGroupSomething($groupid) || $_USER->canPublishContentInGroupSomething($groupid);
    $canCreateFile = $canCreateOrPublishContentInScope;

    $pageTitle = gettext('Documents');
    $folderid = 0;
    $is_resource_lead_content = 1;
    include(__DIR__ . "/views/templates/resources.template.php");
}
elseif (isset($_GET['setResourceSortbyState'])){
    
    $_SESSION['resource_sortby'] = in_array($_GET['sortby'],array('default','name','size','type','created','modified')) ? $_GET['sortby'] : 'default';  
}
else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit();
}
