<?php

define('AJAX_CALL', 1); // Define AJAX call for error handling
require_once __DIR__ . '/head.php'; //Common head.php includes destroying Session for timezone so defining INDEX_PAGE. Need head for logging and protecting

global $_COMPANY;
global $_USER;
global $_ZONE;
global $db;

#### Utility Functions ####
function updateReportMetaFieldsFromPOST(array &$reportMeta)
{
    foreach ($reportMeta['Fields'] as $k => $v) {
        if (str_contains($k, 'extendedprofile.')) {
            $kk = str_replace('extendedprofile.', 'extendedprofile_', $k);
        } else {
            $kk = $k;
        }
        if (!isset($_POST[$kk])) {
            unset($reportMeta['Fields'][$k]);
        }
    }
    unset($reportMeta['AdminFields']);
}

if (isset($_GET['getManageTeamsContainer']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    include(__DIR__ . "/views/talentpeak/teams/manage_teams_container.template.php");
    exit();
}

elseif (isset($_GET['manageTeams']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $teamCustomName = Team::GetTeamCustomMetaName($group->getTeamProgramType());
    $newbtn = (!in_array($group->getTeamProgramType(),array(Team::TEAM_PROGRAM_TYPE['NETWORKING'],Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']))) ? '<div class="btn-group">
                    <button id="newTeamBtn" aria-expanded="false" type="button" class="btn btn-primary dropdown-toggle new-circle-btn" data-toggle="dropdown">
                        '.sprintf(gettext('New %s'), $teamCustomName).' ▾
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right">
                    <li>
                        <a role="button" href="javascript:void(0)" class="dropdown-item" onclick=\'openNewTeamModal("' . $_COMPANY->encodeId($groupid) . '","' . $_COMPANY->encodeId(0) . '","manage")\',>'.sprintf(gettext('New %s'), $teamCustomName) .'</a></li>
                       <li> <a role="button" href="javascript:void(0)" class="dropdown-item" onclick=\'importTeams("' . $_COMPANY->encodeId($groupid) . '")\'>'.sprintf(gettext('Import %s'), Team::GetTeamCustomMetaName($group->getTeamProgramType(),1)) .'</a> 
                       </li>                     
                    </ul>
                </div>' : '';
    $refresh = '<div class="btn-group">
                <button id="teamReportBtn" type="button" class="btn btn-primary"  onclick="getTeamsReportOptions(\'' . $_COMPANY->encodeId($groupid) . '\')" >
                '.sprintf(gettext('%s Report'),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1)).'
                </button>
            </div>';
    include(__DIR__ . "../../affinity/views/templates/manage_section_dynamic_button.html");
    $_SESSION['teamFilterActiveTab'] ??= Team::STATUS_ACTIVE; // Default
    include(__DIR__ . "/views/talentpeak/teams/manage_teams.template.php");
    exit();
}

elseif (isset($_GET['getUnmatchedUsersForTeam']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $registrationType = !empty($_GET['registrationType']) ? $_GET['registrationType'] : '';

    $teamsFilters = array();
    $teamFiltersValue = '';
    if (isset($_GET['teamsFilterValue'])) {
        $teamsFilters = array_intersect([$_GET['teamsFilterValue']], ['unassigned', 'assigned', 'complete', 'incomplete']);
        $teamFiltersValue = $teamsFilters[0] ?? '';
    }

    $totalrows = Team::GetTeamJoinRequests($groupid,0, false,'', '',-1,-1,true, $teamsFilters);

    $newbtn = '';

    if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV'] && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']) {
        $newbtn .= '<div class="btn-group join-request-rep">
                   <button type="button" class="btn btn-primary"  onclick="importTeamRegistrations(\'' . $_COMPANY->encodeId($groupid) . '\')" >' . gettext("Import") . '</button>
               </div>
               ';
    }

    $newbtn .= '<div class="btn-group join-request-rep">
                    <button id="registrationReport" type="button" class="btn btn-primary"  onclick="getTeamsJoinRequestSurveyReportOptions(\'' . $_COMPANY->encodeId($groupid) . '\')" >'.gettext("Registration Report").'</button>
                </div>
                ';

    if ($_COMPANY->getAppCustomization()['teams']['teambuilder_enabled'] && $totalrows && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['NETWORKING'] && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']) {
        $canDownloadSurvey = $group->canDownloadOrViewSurveyReport();
        $disabled = $canDownloadSurvey ? '' : 'disabled';
        $teamBuilderBtnTitle = $canDownloadSurvey ? '' : sprintf(gettext('%s Builder requires allow survey data download option to be enabled.'), $_COMPANY->getAppCustomization()['teams']['name']);
        $newbtn .= '
            <div class="btn-group join-request-rep" onclick="teamBuilder(\''.$_COMPANY->encodeId($groupid).'\')">
                <button type="button" class="btn btn-primary" '.$disabled.' title="\''.$teamBuilderBtnTitle.'\'">'.sprintf(gettext('%s Builder '), $_COMPANY->getAppCustomization()['teams']['name']).'</button>
            </div>
            ';
    }

    include(__DIR__ . "../../affinity/views/templates/manage_section_dynamic_button.html");
    include(__DIR__ . "/views/talentpeak/teams/get_unmatched_users_for_team.template.php");
    exit();
}

elseif (isset($_GET['initMyTeamsContainer']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $chapterid = 0;
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        (isset($_GET['chapterid']) && ($chapterid = $_COMPANY->decodeId($_GET['chapterid'])) < 0)

    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (!$group->getTeamProgramType()){ // Validation
        AjaxResponse::SuccessAndExit_STRING(-1, '', sprintf(gettext('This %1$s is not yet configured to use the %2$s feature. Please contact your administrator.'), $_COMPANY->getAppCustomization()['group']['name-short'], Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Error'));
    }

    if (empty(Team::GetProgramTeamRoles($groupid,1))) { // Validation
        AjaxResponse::SuccessAndExit_STRING(-1, '', sprintf(gettext('No role types have yet been configured in this %1$s. Please contact your administrator.'), $_COMPANY->getAppCustomization()['group']['name-short']), gettext('Error'));
    }

    $showGlobalChapterOnly = intval($_SESSION['showGlobalChapterOnly'] ?? 0);
    $myTeams = Team::GetMyTeams($groupid,$chapterid, $showGlobalChapterOnly);

    if (
        ($_ZONE->val('app_type') !== 'talentpeak' &&  $_ZONE->val('app_type') !== 'peoplehero')
        && $group->isTeamsModuleEnabled()
        && !$_USER->isGroupMember($groupid)
        && empty($myTeams)
    ) {
        AjaxResponse::SuccessAndExit_STRING(-1, '', sprintf(gettext('%1$s page is available only to the members of %2$s %3$s, please join  %2$s %3$s as a member first. '), Team::GetTeamCustomMetaName($group->getTeamProgramType()), $group->val('groupname'), $_COMPANY->getAppCustomization()['group']['name-short']), gettext('Error'));
    }

    if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ // Individual Development
        if (!empty($myTeams)){
            $teamid = $myTeams[0]['teamid'];
            $statusCode = 1;

            AjaxResponse::SuccessAndExit_STRING($statusCode, array($_COMPANY->encodeId($teamid),$_COMPANY->encodeId(1)), '', gettext('Success'));

        } else { // Create Individual Development team

            if($_GET['section'] == 1){

                $enc_chapterid   = $_COMPANY->encodeId($chapterid);
                $enc_groupid    = $_COMPANY->encodeId($groupid);
                include(__DIR__ . "/views/talentpeak/teams/get_my_teams_page.template.php");

            }else{            
            
                $individualDevelopmentRole = Team::GetProgramTeamRoles($groupid,1,4);

                if (!empty($individualDevelopmentRole)){
                    $roleid = $individualDevelopmentRole[0]['roleid'];
                    $teamid = Team::CreateOrUpdateTeam($groupid,0, $_USER->val('firstname').'\'s Individual Development');
                    if ($teamid){
                        $team = Team::GetTeam($teamid);
                        $team->addUpdateTeamMember($roleid, $_USER->id());
                        $statusCode = 2;
                    } else {
                        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. please reload the page.'), gettext('Error'));
                    }
                } else {
                    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('No Individual Development role type found. You should contact your administrator to get help creating the new role.'), gettext('Error'));
                }

                AjaxResponse::SuccessAndExit_STRING($statusCode, array($_COMPANY->encodeId($teamid),$_COMPANY->encodeId(1)), '', gettext('Success'));
            }
            
        }
        

    } else {
        include(__DIR__ . "/views/talentpeak/teams/get_my_teams_container.template.php");
    }
    exit();
}

elseif (isset($_GET['getMyTeams']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $chapterid = 0;
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        (isset($_GET['chapterid']) && ($chapterid = $_COMPANY->decodeId($_GET['chapterid'])) < 0)

    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $showGlobalChapterOnly = intval($_SESSION['showGlobalChapterOnly'] ?? 0);
    $myTeams = Team::GetMyTeams($groupid,$chapterid, $showGlobalChapterOnly);
    $joinRequests = Team::GetUserJoinRequests($groupid);
    $allRoles = Team::GetProgramTeamRoles($groupid, 1);

    $progressBarSetting = $group->getActionItemTouchPointProgressBarSetting();
    $hiddenTabs = $group->getHiddenProgramTabSetting();

    include(__DIR__ . "/views/talentpeak/teams/get_my_teams.template.php");
    
    exit();
}


elseif (isset($_GET['getTeamDetail']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $teamObj= Team::GetTeam($teamid);
    if (!$teamObj || $teamObj->isInactive()) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('This %s link has been expired. Please contact your program co-ordinator'),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Error'));
    }
    $manage_section = intval(($_GET['manage_section'] ?? 0) && $_USER->canManageGroup($teamObj->val('groupid')));
    $activeTab = $_GET['activeTab'] ?: 'todo';

    $showBasicInfoOnly = intval($_GET['showBasicInfo'] ?? 0);
    $teamMembers = $teamObj->getTeamMembers(0);

    if (!$showBasicInfoOnly){

        if (!$teamObj->isTeamMember($_USER->id()) && !$_USER->canManageContentInScopeCSV($groupid,$teamObj->val('chapterid'))) {
            header(HTTP_FORBIDDEN);
            exit();
        }

        if (!in_array($_USER->id(), array_column($teamMembers,'userid')) && !$_USER->canManageContentInScopeCSV($groupid,$teamObj->val('chapterid'))) {
            header(HTTP_FORBIDDEN);
            exit();
        }
        if (!$manage_section) {
            $teamObj->updateTeamMemberLastActiveDate();
        }
    }
    $hiddenTabs = $group->getHiddenProgramTabSetting();
    $hashTagHandles = array();
    if($teamObj->val('handleids')){
        $hashTagHandles = HashtagHandle::GetAllHashTagHandles($teamObj->val('handleids'));
    }

    if ($showBasicInfoOnly){
        $teamWorkflowSetting = $group->getTeamWorkflowSetting();
        $allRoles = Team::GetProgramTeamRoles($groupid, 1);
        $circleRolesCapacity = Team::GetCircleRolesCapacity($groupid,$teamObj->val('teamid'));
        include(__DIR__ . "/views/talentpeak/teams/get_team_detail_modal.template.php");
    } else {
       $progressBarSetting = $group->getActionItemTouchPointProgressBarSetting();
        include(__DIR__ . "/views/talentpeak/teams/get_team_detail.template.php");
    }

    exit();
}

elseif (isset($_GET['openNewTeamModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $chapters= Group::GetChapterList($groupid);

    $section = 'actionBtn';
    if (isset($_GET['section']) && in_array($_GET['section'], ['manage','detail','myTeam', 'actionBtn'])) {
        $section = $_GET['section'];
    }

    $pageTitle = sprintf(gettext("Create New %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    $team = null;
    $selectedHandles = array();

    if ($teamid > 0) {
        $pageTitle = sprintf(gettext("Update %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
        $team = Team::GetTeam($teamid);
        $selectedHandles = explode(',',$team->val('handleids')??'');
    }

    $group = Group::GetGroup($groupid);
    $fontColors = $groupid > 0 ? json_encode(array($group->val('overlaycolor'),$group->val('overlaycolor2'))) : json_encode(array());

    $allRoles = Team::GetProgramTeamRoles($groupid, 1);
    $circleRolesCapacity = Team::GetCircleRolesCapacity($groupid,$teamid);
    $hashTagHandles = HashtagHandle::GetAllHashTagHandles();

    include(__DIR__ . "/views/talentpeak/teams/new_team_form_modal.template.php");
    exit();
}

elseif (isset($_GET['createNewTeam']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $chapterid = 0;
    if (!empty($_POST['chapterid'])){
        $chapterid = $_COMPANY->decodeId($_POST['chapterid']);
    }

    $isManageSection = isset($_POST['section']) && $_POST['section'] === 'manage';

    // Authorization Check
    if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['ADMIN_LED']  && !($teamid && $_USER->isProgramTeamMember($teamid)) && !$_USER->canCreateContentInScopeCSV($groupid,$chapterid, 0)){
        if ((!empty(Group::GetChapterList($groupid)) && !$chapterid)) {
            $contextScope = $_COMPANY->getAppCustomization()['channel']['name-short'];
            AjaxResponse::SuccessAndExit_STRING(-3, '', sprintf(gettext("Please select a %s scope."), $_COMPANY->getAppCustomization()['chapter']['name-short']), gettext('Error'));
        }
    }


    $team_name = $_POST['team_name'];
    $checkDuplicateTeam = Team::GetTeamByTeamName($groupid,$team_name);

    if ($checkDuplicateTeam){
        if (!$teamid  || ($teamid && $checkDuplicateTeam->val('teamid')!=$teamid)){
            AjaxResponse::SuccessAndExit_STRING(0,'', sprintf(gettext('%1$s already exists with "%2$s" name. Please update %1$s name to fix this error!'),Team::GetTeamCustomMetaName($group->getTeamProgramType()), $team_name), gettext('Error'));
        }
    }

    $team_description = ViewHelper::RedactorContentValidateAndCleanup($_POST['team_description']);

    $circleRolesCapacity = ViewHelper::ValidateCircleRolesCapacityInput();


    $handleids = '';
    $tagidsArray = array();
    if (isset($_POST['handleids'])){
        foreach($_POST['handleids'] as $handle){
         $tagidsArray[] = HashtagHandle::GetOrCreateHandle($handle);
        }
    }
    if (!empty($tagidsArray)){
        $handleIdsArray =
        $handleids = implode(',',array_column($tagidsArray,'hashtagid'));
    }

    if (!$teamid && $group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES'] && !$isManageSection) {
        // Init silent registration process of mentor sys role type
        $roleIdOfMentorType = Team::CanCreateCircleByRole($groupid, $_USER->id());
        $join_request_status = Team::CreateAutomaticJoinRequest($group, $_USER->id(), $roleIdOfMentorType);
        if (!$join_request_status['status']) {
            AjaxResponse::SuccessAndExit_STRING(-1, '', sprintf(gettext('Unable to start a %1$s. This might be due to role restrictions or other requests.'), Team::GetTeamCustomMetaName($group->getTeamProgramType())), '');
        }
    }

    $id = Team::CreateOrUpdateTeam($groupid, $teamid, $team_name, $chapterid,0,$team_description,$handleids);
    $team = null;

    if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']) {
        if ($teamid) {
            $id = $teamid;
        }
        $team = Team::GetTeam($id);
        $team->updateCircleMaxRolesCapacity($circleRolesCapacity);
    }


    if ($teamid > 0) {
        AjaxResponse::SuccessAndExit_STRING(1,array($group->getTeamProgramType(),$_COMPANY->encodeId($teamid)), sprintf(gettext("%s updated successfully"),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Success'));
    } else {
        $status = 1;
        if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES'] && !$isManageSection) {
            $roleid = Team::CanCreateCircleByRole($groupid,$_USER->id());
            if ($roleid){
                $team->addUpdateTeamMember($roleid, $_USER->id());
                $team->activate();
                $status = 2;
            }
        }

       AjaxResponse::SuccessAndExit_STRING($status,array($group->getTeamProgramType(),$_COMPANY->encodeId($id)), sprintf(gettext("%s created successfully"),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Success'));
    }
}

elseif (isset($_GET['getTeamsTodoList']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 0 ||
        ($team = Team::GetTeam($teamid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $manage_section = intval(($_GET['manage_section'] ?? 0) && $_USER->canManageGroup($team->val('groupid')));
    $todolist = $team->getTeamsTodoList();
    $taskType = 'todo';
    // Stats parameters
    $statsTitle = gettext("Total Action Items");
    [$statsTotalRecoreds, $statsTotalInProgress, $statsTotalCompleted, $statsTotalOverDues] =  $team->getContentsProgressStats($group, 0,'todo');
    $progressBarSetting = $group->getActionItemTouchPointProgressBarSetting();
    $actionItemConfig = $group->getActionGonfiguration();
    include(__DIR__ . "/views/talentpeak/todo/get_teams_todo_list.template.php");
    exit();
}

elseif (isset($_GET['openCreateTodoModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 1 ||
        ($taskid = $_COMPANY->decodeId($_GET['taskid'])) < 0 ||
        ($team = Team::GetTeam($teamid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $pageTitle = gettext("Add an action item");
    $todo = null;
    $parentid = 0;
    $finalAssignees = $team->getTeamMembers(0);
    $uniqueAssignees = array_intersect_key($finalAssignees, array_unique(array_column($finalAssignees, 'userid')));

    if ($taskid) {
        $team_task_model = $team->getTodoDetail($taskid, true);
        $todo = $team_task_model?->toArray() ?? [];
        $parentid = $todo['parent_taskid'];
        $pageTitle = gettext("Update action item");
        $duedate = '';
        $hour = '';
        $minutes = '';
        $period  = '';
        if ($todo['duedate'] && strtotime($todo['duedate'])> 0 ){
            $timezone = $_SESSION['timezone'] ?: 'UTC';
            $duedate = $db->covertUTCtoLocalAdvance("Y-m-d", '', $todo['duedate'], $timezone);
            $hour = $db->covertUTCtoLocalAdvance("h", '', $todo['duedate'], $timezone);
            $minutes = $db->covertUTCtoLocalAdvance("i", '', $todo['duedate'], $timezone);
            $period = $db->covertUTCtoLocalAdvance("A", '', $todo['duedate'], $timezone);
        }

    }
    $fontColors = json_encode(array($group->val('overlaycolor'), $group->val('overlaycolor2')));
    include(__DIR__ . "/views/talentpeak/todo/team_todo_modal.template.php");
    exit();
}

elseif (isset($_GET['addTeamTodo']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($taskid = $_COMPANY->decodeId($_POST['taskid'])) < 0

    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $tasktitle = $_POST['tasktitle'];
    $sendEmail = $_POST['sendEmail'] == 1 ? 1 : 0;

    $timezone = isset($_POST['timezone']) ? $_POST['timezone'] : (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
    $duedate = null;
    if (!empty($_POST['duedate'])) {
        $duedate = $_POST['duedate'];
        $hour = $_POST['hour'] ?: '00';
        $minutes = $_POST['minutes'] ?: '00';
        $period = (empty($_POST['hour']) && empty($_POST['hour'])) ? '' : $_POST['period'];
        $duedate = $duedate . " " . $hour . ":" . $minutes . " " . $period;
        $duedate = $db->covertLocaltoUTC("Y-m-d H:i:s", $duedate, $timezone);
    }
    $description = ViewHelper::RedactorContentValidateAndCleanup($_POST['description']);

    if (Str::IsEmptyHTML($description)){
        if ($_POST['task_type'] == 'feedback')
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Feedback field can't be empty."), gettext('Error!'));
    }

    $assignedto = 0;
    $task_type = $_POST['task_type'];
    if ($task_type != 'touchpoint') {
        $assignedto = $_COMPANY->decodeId($_POST['assignedto']);
    }

    $visibility = 0;
    if (isset($_POST['visibility'])) {
        $visibility = $_POST['visibility'];
    }
    $parent_taskid = isset($_POST['parent_taskid']) ? $_COMPANY->decodeId($_POST['parent_taskid']) : 0;
    $id = $team->addOrUpdateTeamTask($taskid, $tasktitle, $assignedto, $duedate, $description, $task_type, $visibility,$parent_taskid);
    if ($id) {
        if (!empty($_POST['ephemeral_topic_id'])) {
            $ephemeral_topic_id = $_COMPANY->decodeId($_POST['ephemeral_topic_id']);
            $ephemeral_topic = EphemeralTopic::GetEphemeralTopic($ephemeral_topic_id);

            $team_task = TeamTask::GetTeamTask($id);
            $team_task->moveAttachmentsFrom($ephemeral_topic);
        }

        $assignedUser = User::GetUser($assignedto);
        if ($taskid) {
            $returnMessage = gettext(" updated successfully");
            $emailStatus = "updated";
        } else {
            $returnMessage = gettext(" created successfully");
            $emailStatus = "created";
        }

        if ($task_type == 'todo') {
            if ($sendEmail && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ // Skip email notification if individual development
                $team->sendActionItemEmailToTeamMember($id, $emailStatus);
            }
            $returnMessage = gettext('Action Item') . $returnMessage;
        }

        if ($task_type == 'touchpoint') {
            if ($sendEmail && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ // Skip email notification if individual development
                $team->sendTouchPointEmailToTeamMember($id, $emailStatus);
            }
            $returnMessage = gettext('Touch Point') . $returnMessage;
        }

        if ($task_type == 'feedback') {
            if (1 /*$sendEmail*/) {
                $team->sendFeedbackEmailToTeamMember($id, $emailStatus);
            }
            $returnMessage = gettext('Feedback') . $returnMessage;
        }

        echo $returnMessage;
        exit();
    } else {
        echo "0";
    }
    exit();
}

elseif (isset($_GET['searchUserForTeam']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $searchAllUsers = intval($_GET['searchAllUsers'] == "true");
    $keyword = raw2clean($_GET['keyword']);
    $roleid = $_GET['roleid'] ? $_COMPANY->decodeId($_GET['roleid']) : 0;
    $retrunjon = isset($_GET['retrunjon']) ? 1 : 0;
    $excludeCondition = "";
    $searchAllUsersConditon = "";
    if ($roleid) {
        if ( $group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ // Individual Development
            $excludeCondition = " userid NOT IN(SELECT team_members.`userid` FROM `team_members` JOIN teams ON teams.teamid=team_members.teamid WHERE teams.`groupid`='{$groupid}') ";

        } else {
            $excludeCondition = " userid NOT IN(SELECT `userid` FROM `team_members` WHERE `teamid`='{$teamid}') ";
        }

        if (!$searchAllUsers ){
            $searchAllUsersConditon = " userid IN (SELECT `userid` FROM `member_join_requests` WHERE member_join_requests.`groupid`='{$groupid}' AND `member_join_requests`.`isactive` = 1 AND roleid={$roleid})";
        }
    }


    $activeusers = User::SearchUsersByKeyword($keyword,$searchAllUsersConditon ,$excludeCondition); // Note $excludeCondition is added as " AND ({$excludeCondition}) "
    if ($retrunjon){
        $finalData = [];
        foreach($activeusers as $usr){
            $finalData[] = array('userid'=>$_COMPANY->encodeId($usr['userid']),'username'=>(rtrim(($usr['firstname'] . " " . $usr['lastname']), " ") . " (" . $usr['email'] . ") - " . $usr['jobtitle']));
        }
        echo json_encode($finalData);
        exit();
    }
    $dropdown = '';
    if (count($activeusers) > 0) {
        $dropdown .= "<select class='form-control userdata' name='userid' onchange='closeDropdown()' id='user_search' required >";
        $dropdown .= "<option value=''>".gettext("Select a user (maximum of 20 matches are shown below)")." </option>";

        foreach ($activeusers as $activeuser) {
            $dropdown .= "<option value='" . $_COMPANY->encodeId($activeuser['userid']) . "'>" . rtrim(($activeuser['firstname'] . " " . $activeuser['lastname']), " ") . " (" . $activeuser['email'] . ") - " . $activeuser['jobtitle'] . "</option>";
        }
        $dropdown .= '</select>';
        $dropdown .= "<button type='button' class='btn btn-link' onclick=removeSelectedUser('0') >".gettext("Remove")."</button>";
    } else {
        $dropdown .= "<select class='form-control userdata' name='userid' id='user_search' required>";
        $dropdown .= "<option value=''>".gettext("No match found")."</option>";
        $dropdown .= "</select>";
    }
    echo $dropdown;
    exit();
}

elseif (isset($_GET['openSearchTeamUserModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($team_memberid = $_COMPANY->decodeId($_GET['team_memberid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $memberDetail = null;

    if($team_memberid) {
        $memberDetail = $team->getTeamMemberById($team_memberid);
        $form_title = sprintf(gettext("Update %s member role"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    } else {
        $form_title = sprintf(gettext("Search user to add %s member"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    }
    include(__DIR__ . "/views/talentpeak/common/search_user_form.template.php");
    exit();
}

elseif (isset($_GET['addUpdateTeamMember']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) == null ||
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($searchedUserid = $_COMPANY->decodeId($_POST['userid'])) < 1 ||
        ($roleid = $_COMPANY->decodeId($_POST['type'])) < 1 ||
        ($role = Team::GetTeamRoleType($roleid)) === null ||
        ($team_memberid = $_COMPANY->decodeId($_POST['team_memberid'])) < 0

    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $roletitle = $_POST['roletitle'];

    if (!$team_memberid && $team->isTeamMember($searchedUserid)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Selected user is already a member'), gettext('Error'));
    }

    if (!$team_memberid && !$team->isAllowedNewTeamMemberOnRole($roleid)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Maximum allowed members limit reached for selected role!'), gettext('Error'));
    }

    $requestDetail = Team::GetRequestDetail($groupid,$roleid,$searchedUserid);
    if (!$team_memberid && $requestDetail && $requestDetail['isactive']!=1){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('You cannot assign this role because the role join request for this user is not active.'), gettext('Error'));
    }

    if (!$team_memberid && !Team::CanJoinARoleInTeam($groupid, $searchedUserid, $roleid)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('You cannot assign this role because the maximum requested capacity for this role has been reached. In order to add more of this role to the group, you must either change the maximum number or remove current users from the role.'), gettext('Error'));
    }

    // For circle members create a silent registration
    if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES'] ) {
        if ($role['sys_team_role_type'] == 2) {
            $allowedCircleRoleId = Team::CanCreateCircleByRole($groupid, $searchedUserid);
            if ($allowedCircleRoleId == $roleid){ // if allowed to create a team
                // Init silent registration process
                $join_request_status = Team::CreateAutomaticJoinRequest($group, $searchedUserid, $roleid);
                if (!$join_request_status['status']) {
                    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Unable to update registration for the selected user'), gettext('Error'));
                }
            } else {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext('You cannot assign this role.'), gettext('Error'));
            }
        } else {
            // Init silent registration process
            $join_request_status = Team::CreateAutomaticJoinRequest($group, $searchedUserid, $roleid);
            if (!$join_request_status['status']) {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Unable to update registration for the selected user'), gettext('Error'));
            }
        }
    }

    if ($team->addUpdateTeamMember($roleid, $searchedUserid,$team_memberid,$roletitle)) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Updated successfully'), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Error'));
}

elseif (isset($_GET['updateTaskStatus']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($taskid = $_COMPANY->decodeId($_POST['taskid'])) < 1 ||
        ($status = $_COMPANY->decodeId($_POST['updateStatus'])) < 0 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($task = $team->getTodoDetail($taskid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $send_email_notification = ((int) ($_POST['send_email_notification'] ?? 0) === 1);

    if ($retVal = $team->updateTeamTaskStatus($taskid, $status)) {
        if ($status != 1 && $send_email_notification) {
            if ($task['task_type'] == 'todo') {
                $team->sendActionItemEmailToTeamMember($taskid);
            }
            if ($task['task_type'] == 'touchpoint') {
                $team->sendTouchPointEmailToTeamMember($taskid);
            }
        }
        AjaxResponse::SuccessAndExit_STRING($retVal, '', gettext('Status updated successfully'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong while updating status. Please try again.'), gettext('Error'));
    }
    exit();
}

elseif (isset($_GET['manageTeamMembers']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $section = $_GET['section'] ? intval($_GET['section']) : 0;
    $modalTitle = sprintf(gettext("%s Members"),htmlspecialchars($team->val('team_name')));
    $rows = $team->getTeamMembers(0);
    include(__DIR__ . "/views/talentpeak/teams/manage_team_members.template.php");

    exit();
}

elseif (isset($_GET['deleteTeamMember']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($team_memberid = $_COMPANY->decodeId($_POST['team_memberid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $memberDetail = $team->getTeamMemberById($team_memberid);
    if ($memberDetail['userid']!=$_USER->id()){
        // Authorization Check
        if (!$_USER->canManageContentInScopeCSV($groupid,$team->val('chapterid'))) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }
    if ($team->deleteTeamMember($team_memberid)) {
        echo 1;
    } else {
        echo 0;
    }

    exit();
}

elseif (isset($_GET['deleteTeamTask']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($taskid = $_COMPANY->decodeId($_POST['taskid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if ($team->deleteTeamTask($taskid)) {
        echo 1;
    } else {
        echo 0;
    }

    exit();
}

elseif (isset($_GET['deleteTeamPermanently']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($groupid != $team->val('groupid'))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if($team->deleteTeamPermanently()){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Team deleted successfully.'), gettext('Success'));
    }else{
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Success'));
    }
    exit();
}

elseif (isset($_GET['updateTeamStatus']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($status = $_COMPANY->decodeId($_POST['status'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (in_array($status, array(Team::STATUS_INACTIVE,Team::STATUS_PURGE,Team::STATUS_ACTIVE,Team::STATUS_COMPLETE, Team::STATUS_INCOMPLETE, Team::STATUS_PAUSED))){
        if ($status == Team::STATUS_ACTIVE ){
            
            $reUpdateUsedRoleCapacity = false;
            if ($team->isComplete() || $team->isIncomplete() /*|| $team->isPaused() */){ //This is the case of reopen team after complate/uncomplete marked
                $isRoleCapacityAvailable = $team->isRoleCapacityAvailableOfMembers();
                if (!$isRoleCapacityAvailable) {
                    AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('This %s cannot be reactivated because the maximum number of concurrent programs supported for one member has been reached.'),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Error'));
                }
                $reUpdateUsedRoleCapacity = true;
            }
            $response = $team->activate();
            if ($response['status']){
                if ($reUpdateUsedRoleCapacity) {
                    $team->resetRoleUsedCapacity();
                }
                if ($group->getTeamProgramType()== Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){
                    AjaxResponse::SuccessAndExit_STRING(2, '', gettext('Development program started successfully'), gettext('Success'));
                } else {
                    AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext('%1$s %2$s activated successfully.'),$team->val('team_name'),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Success'));
                }
            } else {
                AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('%1$s %2$s Error'),$team->val('team_name'), Team::GetTeamCustomMetaName($group->getTeamProgramType())).' : '.$response['error'], gettext('Error'));
            }
        } elseif ($status == Team::STATUS_INACTIVE){
            $team->deactivate();
            AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext('%1$s %2$s deactivated successfully.'),$team->val('team_name'),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Success'));
        } elseif ($status == Team::STATUS_COMPLETE){
            $team->complete();
            AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext('%1$s %2$s completed successfully.'),$team->val('team_name'),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Success'));
        } elseif ($status == Team::STATUS_INCOMPLETE){
            $team->incomplete();
            AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext('%1$s %2$s closed as incomplete.'),$team->val('team_name'),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Success'));
        } elseif ($status == Team::STATUS_PAUSED){
            $team->paused();
            AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext('%1$s %2$s paused successfully.'),$team->val('team_name'),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Success'));
        }  elseif($status == Team::STATUS_PURGE){
            ### TODO - cherry pick changes for permanent deletion of team issue fixed on development
            AjaxResponse::SuccessAndExit_STRING(1,'', sprintf(gettext('%1$s %2$s deleted successfully.'),$team->val('team_name'),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Success'));
        }else {
            AjaxResponse::SuccessAndExit_STRING(0, $team->val('team_name'), gettext('Update Failed.'), gettext('Error'));
        }
    } else {
        AjaxResponse::SuccessAndExit_STRING(0,'', gettext("Invalid Action"), gettext('Error'));
    }
}
elseif (isset($_GET['getTeamsTouchPointsList']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 0 ||
        ($team = Team::GetTeam($teamid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $todolist = $team->getTeamsTouchPointsList();
    $touchPointTypeConfig = $group->getTouchpointTypeConfiguration();
    $taskType = 'touchpoint';
    $hiddenTabs = $group->getHiddenProgramTabSetting();
    //Stats parameters
    $statsTitle = gettext("Total Touch Points");
    [$statsTotalRecoreds, $statsTotalInProgress, $statsTotalCompleted, $statsTotalOverDues] =  $team->getContentsProgressStats($group, 0,'touchpoint');
    $progressBarSetting = $group->getActionItemTouchPointProgressBarSetting();   
    include(__DIR__ . "/views/talentpeak/touchpoint/get_teams_touchpoint_list.template.php");
    exit();
}

elseif (isset($_GET['openCreateTouchpointModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($taskid = $_COMPANY->decodeId($_GET['taskid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $pageTitle = gettext("Add a Touch Point");
    $parentid = 0;
    $touchPoint = null;
    if ($taskid) {
        $team_task_model = $team->getTodoDetail($taskid, true);
        $touchPoint = $team_task_model?->toArray() ?? [];
        $parentid = $touchPoint['parent_taskid'];
        $pageTitle = gettext("Update Touch Point");
        $timezone = $_SESSION['timezone'] ?: 'UTC';
        $duedate = $db->covertUTCtoLocalAdvance("Y-m-d", '', $touchPoint['duedate'], $timezone);
        $hour = $db->covertUTCtoLocalAdvance("h", '', $touchPoint['duedate'], $timezone);
        $minutes = $db->covertUTCtoLocalAdvance("i", '', $touchPoint['duedate'], $timezone);
        $period = $db->covertUTCtoLocalAdvance("A", '', $touchPoint['duedate'], $timezone);
    }
    $fontColors = json_encode(array($group->val('overlaycolor'), $group->val('overlaycolor2')));

    include(__DIR__ . "/views/talentpeak/touchpoint/team_touch_point_modal.template.php");
    exit();
}

elseif (isset($_GET['getTeamsFeedback']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $todolist = $team->getTeamsFeedbackList();
    $taskType = 'feedback';

    include(__DIR__ . "/views/talentpeak/feedback/get_teams_feedback_list.template.php");
    exit();
}

elseif (isset($_GET['openNewFeedbackModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 0 ||
        ($feedbackid = $_COMPANY->decodeId($_GET['feedbackid'])) < 0 ||
        ($team = Team::GetTeam($teamid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $pageTitle = gettext("Feedback");

    $todo = null;
    $finalAssignees = $team->getTeamMembers(0);
    $uniqueAssignees = array_intersect_key($finalAssignees, array_unique(array_column($finalAssignees, 'userid')));

    if ($feedbackid) {
        $team_task_model = $team->getTodoDetail($feedbackid, true);
        $todo = $team_task_model?->toArray() ?? [];
        $pageTitle = gettext("Update Feedback");
    }
    $fontColors = json_encode(array($group->val('overlaycolor'), $group->val('overlaycolor2')));

    include(__DIR__ . "/views/talentpeak/feedback/team_feedback_modal.template.php");
    exit();
}

elseif (isset($_GET['getTaskDetailView']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 0 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($taskid = $_COMPANY->decodeId($_GET['taskid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $updateStatus = 0;
    if (!empty($_GET['updateStatus']) && ($s = $_COMPANY->decodeId(ltrim($_GET['updateStatus'],"STATUS_"))) > 0) {
        $updateStatus = $s;
    }

    $data = $team->getTodoDetailWithChild($taskid);

    if (empty($data[0]) ||
        (($data[0]['task_type'] == 'feedback') && !$team->loggedinUserCanViewFeedback($data[0]['visibility'], $data[0]['createdby'], $data[0]['assignedto']))
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $team_task_model = TeamTask::Hydrate($data[0]['taskid'], $data[0]);

    $status = Team::GET_TALENTPEAK_TODO_STATUS;
    $topicid = $taskid;
    if ($_COMPANY->getAppCustomization()['teams']['comments']) {
        /**
         * Dependencies for Comment Widget
         * $comments
         * $commentid (default 0)
         * $groupid
         * $topicid
         * $disableAddEditComment
         * $submitCommentMethod
         * $mediaUploadAllowed
        */
        $comments = TeamTask::GetComments_2($taskid);
        $commentid = 0;
        $disableAddEditComment = false;
        if ($team->isComplete() || $team->isIncomplete() || $team->isInactive() || $team->isPaused()) {
            $disableAddEditComment = true;
        }
        $submitCommentMethod = "TeamsListComment";
        $mediaUploadAllowed = $_COMPANY->getAppCustomization()['teams']['enable_media_upload_on_comment'];
    }

    $hideEventAddEditFromTouchPointActionButton = true;
    $refreshPage = 0;
    $touchPointTypeConfig = $group->getTouchpointTypeConfiguration();
    include(__DIR__ . "/views/talentpeak/common/get_detail_view.tamplate.php");
    exit();
}

elseif (isset($_GET['createSubItemTeamTask']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($taskid = $_COMPANY->decodeId($_POST['taskid'])) < 0

    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $task_type = $_POST['type'];
    $fontColors = json_encode(array($group->val('overlaycolor'), $group->val('overlaycolor2')));
    if ($task_type == 'touchpoint') {
        $team_task_model = $team->getTodoDetail($taskid, true);
        $parentid= $taskid;
        $taskid = 0;
        $touchPoint = $team_task_model?->toArray() ?? [];
        $pageTitle = gettext("Create Touch Point");
        $timezone = $_SESSION['timezone'] ?: 'UTC';
        $duedate = $db->covertUTCtoLocalAdvance("Y-m-d", '', $touchPoint['duedate'], $timezone);
        $hour = $db->covertUTCtoLocalAdvance("h", '', $touchPoint['duedate'], $timezone);
        $minutes = $db->covertUTCtoLocalAdvance("i", '', $touchPoint['duedate'], $timezone);
        $period = $db->covertUTCtoLocalAdvance("A", '', $touchPoint['duedate'], $timezone) ?: 'AM';
        include(__DIR__ . "/views/talentpeak/touchpoint/team_touch_point_modal.template.php");
    } elseif ($task_type == 'todo') {
        $finalAssignees = $team->getTeamMembers(0);
        $uniqueAssignees = array_intersect_key($finalAssignees, array_unique(array_column($finalAssignees, 'userid')));
        $team_task_model = $team->getTodoDetail($taskid, true);
        $todo = $team_task_model?->toArray() ?? [];
        $parentid= $taskid;
        $taskid = 0;
        $pageTitle = gettext("Create action item");
        $duedate = '';
        $hour = '';
        $minutes = '';
        $period  = 'AM';
        if ($todo['duedate'] && strtotime($todo['duedate'])> 0 ){
            $timezone = $_SESSION['timezone'] ?: 'UTC';
            $duedate = $db->covertUTCtoLocalAdvance("Y-m-d", '', $todo['duedate'], $timezone);
            $hour = $db->covertUTCtoLocalAdvance("h", '', $todo['duedate'], $timezone);
            $minutes = $db->covertUTCtoLocalAdvance("i", '', $todo['duedate'], $timezone);
            $period = $db->covertUTCtoLocalAdvance("A", '', $todo['duedate'], $timezone);
        }
        include(__DIR__ . "/views/talentpeak/todo/team_todo_modal.template.php");
    } else {
        AjaxResponse::SuccessAndExit_STRING(0,'', gettext("Invalid Action"), gettext('Error'));
    }

}

elseif (isset($_GET['assignTeamToUserForm']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($roleid = $_COMPANY->decodeId($_GET['roleid'])) < 1 ||
        ($useridSelected = $_COMPANY->decodeId($_GET['userid'])) < 1 ||
        ($section = $_COMPANY->decodeId($_GET['section'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $statusLabel = array('0'=>gettext('Inactive'),'1'=>gettext('Active'),'2'=>gettext("Draft"));
    $joinRequest = Team::GetRequestDetail($groupid, $roleid,$useridSelected);
    $allRoles = Team::GetProgramTeamRoles($groupid, 1);
    $teams = array();
    $pageTitle = "";
    if ($section == 1) {
        $pageTitle = sprintf(gettext("Create new %s and assign"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    } elseif ($section == 2) {
        $pageTitle = sprintf(gettext("Assign a %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
        if ($_USER->canManageGroupSomething($groupid)) {
            $chapterids = 0;
            if ($_COMPANY->getAppCustomization()['chapter']['enabled']){
                $chapterids = $joinRequest['chapterids'];
            }
            [$totalrows, $teams] = Team::GetAllTeamsInGroup($groupid,$chapterids,0,0,'', false,'','',[Team::STATUS_ACTIVE, Team::STATUS_DRAFT, Team::STATUS_INACTIVE]);
        }

    } else {
        echo 0;
    }

    $chapters = array();
    if ($_COMPANY->getAppCustomization()['chapter']['enabled']){
        $requestedChpaters = explode(',',$joinRequest['chapterids']);
        $allchapters = $group->getAllChapters();
        foreach($allchapters as $ch){
            $chapter = $ch;
            $suffix = "";
            if ($chapter['isactive'] == 1 || $chapter['isactive'] == 0){

                if (in_array($chapter['chapterid'],$requestedChpaters)){
                    $suffix = " (Requested to Join)";
                }
                if ($chapter['isactive'] == 0){
                    $suffix .= " (In-active)";
                }
                $chapter['chaptername'] =  htmlspecialchars_decode($chapter['chaptername']).$suffix;
                $chapters[] = $chapter;
            }
        }
    }

    include(__DIR__ . "/views/talentpeak/teams/create_and_assign_input.template.php");
    exit();
}

elseif (isset($_GET['createOrAssignExitingTeamToUser']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($useridSelected = $_COMPANY->decodeId($_POST['userid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($section = $_COMPANY->decodeId($_POST['section'])) < 1 ||
        ($roleid = $_COMPANY->decodeId($_POST['type'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!Team::CanJoinARoleInTeam($groupid,$useridSelected,$roleid)){ // Check for maximum number of concurrent programs support limit
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('You cannot assign this role because the maximum number of concurrent programs that the selected user requested has been reached.'), gettext('Error'));
    }

    if ($section == 1 || $section == 3) { // $section == 3 is Assign to Self feature of People Hero
        $team_name = $_POST['team_name'];
        if ($team_name) {
            $checkDuplicateTeam = Team::GetTeamByTeamName($groupid,$team_name);
            if ($checkDuplicateTeam){
                AjaxResponse::SuccessAndExit_STRING(0,'', sprintf(gettext('%1$s already exists with "%2$s" name. Please update %1$s name to fix this error!'),Team::GetTeamCustomMetaName($group->getTeamProgramType()), $team_name), gettext('Error'));
            }

            $joinRequest = Team::GetRequestDetail($groupid, $roleid,$useridSelected);
            $chapterid = 0;
            if (isset($_POST['chapterid']) && ($chapterid = $_COMPANY->decodeId($_POST['chapterid']))<1 && $joinRequest['chapterids']!='0'){
                AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Please select a %s'),$_COMPANY->getAppCustomization()['chapter']['name-short']), gettext('Error'));
            }

            if (!($_USER->canManageContentInScopeCSV($groupid, $chapterid, 0))) {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Access denied'), gettext('Error'));
            }

            if (($teamid = Team::CreateOrUpdateTeam($groupid, 0, $team_name,$chapterid))) {
                $team = Team::GetTeam($teamid);
                $team->addUpdateTeamMember($roleid, $useridSelected);
                $encodedTeamId = $_COMPANY->encodeId($teamid);
                if ($section == 3 &&  ($mentor_type = $_COMPANY->decodeId($_POST['mentor_type'])) > 0) {
                    $team->addUpdateTeamMember($mentor_type, $_USER->id());
                    // Activate Team
                    $team->activate(false);
                }
                AjaxResponse::SuccessAndExit_STRING(1, $encodedTeamId, gettext('Member added successfully'), gettext('Success'));
            } else {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Internal server error, please try again.'), gettext('Error'));
            }
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please input a team name!'), gettext('Error'));
        }
    } elseif ($section == 2) {
        $teamid = $_POST['teamid'];
        if ($teamid) {
            $team = Team::GetTeam($_COMPANY->decodeId($teamid));
            if ($team->isTeamMember($useridSelected)){
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Selected user is already a member'), gettext('Error'));
            }
            if (!$team->isAllowedNewTeamMemberOnRole($roleid)) {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Maximum allowed members limit reached on this role!'), gettext('Error'));
            }
            $team->addUpdateTeamMember($roleid, $useridSelected);

            AjaxResponse::SuccessAndExit_STRING(1, $teamid, gettext('Member added successfully'), gettext('Success'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please select a team!'), gettext('Error'));
        }
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong, please try again.'), gettext('Error'));
    }
}

elseif (isset($_GET['getSuggestedUserForTeam']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($roleid = $_COMPANY->decodeId($_GET['roleid'])) < 1 ||
        ($teamRoleType = Team::GetTeamRoleType($roleid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $matchAgainstSysRoleId = -1; // Matching will be done against users who are in the team with $matchAgainstSysRoleId.
    if ($teamRoleType['sys_team_role_type'] == '2') { // If Mentor (2 is mentor) then Set Mentee (3 is mentee)
        $matchAgainstSysRoleId = 3;
    } elseif ($teamRoleType['sys_team_role_type'] == '3') { // If Mentee (3 is mentee) then Set Mentor (2 is mentor)
        $matchAgainstSysRoleId = 2;
    }
    if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['NETWORKING']) {// For networking type match against same role
        $matchAgainstSysRoleId = 3;
    }
    // Get user to match against -> START, also get users team_role_type id.
    [$matchAgainstUserId,$matchAgainstRoleid] = $team->discoverBestUserToMatchAgainst($matchAgainstSysRoleId);

    if (empty($matchAgainstUserId)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('No matches found'), gettext('Error'));
    }

    [$status, $roleRequestsWithSuggestions]  = Team::GetTeamMembersSuggestionsForRequestRoles($group, $matchAgainstUserId, $matchAgainstRoleid);

    if ($status != 1) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('No matches found'), gettext('Error'));
    }

    $allsuggestions = $roleRequestsWithSuggestions[0]['suggestions'];
    $members = $team->getTeamMembers(0);
    if (!empty($members)){
        $members = array_column($members,'userid');
    }
    $suggestions = array();
    foreach($allsuggestions as $suggestion){

        if (in_array($suggestion['userid'],$members) || !Team::CanJoinARoleInTeam($groupid,$suggestion['userid'],$suggestion['roleid'])) {
            continue;
        }
        $suggestions[] = $suggestion;
    }

    if (!empty($suggestions)) {
        $totalSuggetions = count($suggestions);
        $suggestions = array_chunk($suggestions,MAX_TEAMS_ROLE_MATCHING_RESULTS_PER_RECOMMEND_PAGE);
        $totalSuggetionsChunks = count($suggestions);
        $matchAgainst = User::GetUser($matchAgainstUserId);
    ?>
    <style>
        .active-page{
            display:block;
        }
        .inactive-page{
            display:none;
        }
        .popover {
            max-width: 400px !important;
        }
    </style>
    <div class="col-md-12 mb-3 suggestion-title">
        <h6 class="mb-3"><?= gettext("Suggestions"); ?></h6>
    <?php
        $p = 1;
        foreach($suggestions as $suggestionsChunk){ ?>
        <div class="div-pagination<?= $p ==1 ? '-active-0' : '-0'; ?> <?= $p ==1 ? 'active-page' : 'inactive-page'; ?>" data-page="<?= $p; ?>" id="page0_<?= $p;?>" >

        <?php
            foreach ($suggestionsChunk as  $suggestion) {
                $matchingDetail = $suggestion['parameterWiseMatchingPercentage'];
            ?>
                <div class="col-md-4 m-0 p-1" id="<?= $_COMPANY->encodeId($suggestion['userid']) ?>">
                    <div class="clearfix"></div>
                    <a class=" badge badge-light text-left p-1 suggestion word-wrap"
                    style="border:1px solid rgb(212,212,212); width:100%; "
                    >
                        <div class="col-md-11 m-0 p-0" style="cursor: pointer;" onclick="selectSuggestedUser('<?= $_COMPANY->encodeId($suggestion['userid']) ?>','<?= $suggestion['firstname'] . ' ' . $suggestion['lastname']; ?>')">
                            <div>
                                <?= User::BuildProfilePictureImgTag($suggestion['firstname'],$suggestion['lastname'],$suggestion['picture'],'memberpicture_small','Suggested User profile picture', $suggestion['userid'], null) ?>
                                <strong><?= $suggestion['firstname'] . ' ' . $suggestion['lastname']; ?></strong>
                            </div>
                            <div class="subrows">
                                <small><?= $suggestion['email']; ?></small>
                                <br/>
                                <small><?= $suggestion['jobtitle']; ?></small>
                                <div>
                                    <small><strong><?= $suggestion['matchingPercentage']; ?>% matched</strong></small>
                                    <!-- <span class="fa fa-star checked"></span> -->
                                </div>
                            </div>
                        </div>
                        <div tabindex="0" class="col-md-1 m-0 p-0"  style="cursor: pointer;" role="button"  title='Match Detail' data-html="true"  data-trigger="focus"  data-toggle="popover"  data-content="<div>
                            <p><strong><?= gettext("Match details for");?> : </strong></p><p><?= $suggestion['firstname'] . ' ' . $suggestion['lastname']; ?></p>
                            <p><strong><?= gettext("when matched against");?> : </strong></p><p><?= $matchAgainst->val('firstname') . ' ' . $matchAgainst->val('lastname'); ?></p>
                            <br/>
                        <?php if(!empty($matchingDetail)){ ?>
                            <table class='table table-sm'>
                                <tr><td class='text-nowrap'><strong>Parameter</strong></td><td>&nbsp;</td><td><strong>Matching %</strong></td></tr>
                                <?php foreach($matchingDetail as $k =>$v){ 
                                    $showPercentage = 'show';
                                    $showValue = 'show';
                                    if ($v['attributeType']){
                                        $showPercentage = $group->getTeamMatchingAttributeKeyVisibilitySetting($v['attributeType'],$k,Group::MATCHING_ATTRIBUTES_VISIBILITY_KEYS['show_matchp_leaders']);
                                        $showValue = $group->getTeamMatchingAttributeKeyVisibilitySetting($v['attributeType'],$k,Group::MATCHING_ATTRIBUTES_VISIBILITY_KEYS['show_value_leaders']);

                                    }
                                    
                                ?>
                                 <?php if($showPercentage == 'show' || $showValue == 'show'){ ?>
                                    <tr style='font-weight: <?=  (!empty($mandatory) && $mandatory[$k]['is_required'] == 1) ? "bold": "normal" ;?>'>
                                        <td><?= $v['title']; ?><?php if ($showValue == 'show'){ echo '<br>[ ' . ($v['value'] ?: '') . ' ]'; } ?></td>
                                        <td>:</td>
                                        <td><?= ($showPercentage == 'show') ? $v['percentage'].'%' : '-'; ?></td>
                                    </tr>
                                <?php } ?>
                                <?php } ?>
                            </table>
                        <?php } ?>
                        </div>"><i class="fa fa-info-circle" style="text-decoration:none;" aria-hidden="true"></i></div>
                    </a>
                </div>
                <?php
            } ?>
        </div>
        <?php
        $p++;
        } ?>
    <?php if($totalSuggetions > MAX_TEAMS_ROLE_MATCHING_RESULTS_PER_RECOMMEND_PAGE){ ?>
        <div class="clearfix"></div>
        <div class="col-md-12">
            <ul class="pagination justify-content-end">
                <li class="page-item prev0 disabled"><a class="page-link" onclick="suggestionsPagination(<?= $totalSuggetionsChunks; ?>,0,1)" href="javascript:void(0)">Previous</a></li>
                <li class="page-item next0"><a class="page-link" onclick="suggestionsPagination(<?= $totalSuggetionsChunks; ?>,0,2)" href="javascript:void(0)">Next</a></li>
            </ul>
        </div>
    <?php } ?>
        <script>
            $(function(){
                $('[data-toggle="popover"]').popover({
                    sanitize:false
                });
            });
        </script>
    </div>
<?php
    } else {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('No suggestions found'), gettext('Error'));
    }
    exit();
}

elseif (isset($_GET['getProgramJoinOptions']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($roleid = $_COMPANY->decodeId($_GET['roleid'])) < 0 ||
        ($preselected = $_COMPANY->decodeId($_GET['preselected'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $teamid = isset($_GET['teamid']) && $_GET['teamid']!=0 ? $_COMPANY->encodeId($_COMPANY->decodeId($_GET['teamid'])) : 0;

    $inviteid = intval($_GET['inviteid'] ?? 0);
    $joinstatus = intval($_GET['joinstatus'] ?? 0);
    $joinRequest = Team::GetRequestDetail($groupid,$roleid);
    $id = $roleid ? $roleid : $preselected;
    $preSelectedRole = Team::GetTeamRoleType($id);
    $program_type_value = $group->getTeamProgramType();
    if (empty($joinRequest)){
        if (!empty($preSelectedRole['registration_start_date']) && $preSelectedRole['registration_start_date'] > date('Y-m-d')){
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Registration is currently unavailable. Please check back after %s'),$preSelectedRole['registration_start_date'] ), '');
        }
        if (!empty($preSelectedRole['registration_end_date']) && $preSelectedRole['registration_end_date'] < date('Y-m-d')){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Registration is now closed, and we are no longer accepting new requests for this role.'), '');
        }
    }

    $all_chapters= Group::GetChapterList($groupid);
    $requested_chapters = [];
    $encodedRequestedChapters = [];
    $questionJson = $group->getTeamMatchingAlgorithmAttributes();
    $isQuestionAvailable = count($questionJson);

    $questionJson = json_encode($questionJson);
    $allRoles = Team::GetProgramTeamRoles($groupid, 1);
   
    $modalTitle = sprintf(gettext('Thank you for your interest in being a <b>%s</b>!'), $preSelectedRole['type']);
    $submitBtn = gettext("Submit"); //gettext("Send Request");
    if (!empty($joinRequest)) {
        $myRequestedRoles =array();
        $modalTitle = sprintf(gettext('Update your <b>%s</b> Registration'),$preSelectedRole['type']);
        $submitBtn = gettext("Update"); //gettext("Update Request");
        $requested_chapters = array_filter(explode(',',$joinRequest['chapterids'] ?: ''));
    } else {
        $myRequestedRoles = array_column(Team::GetUserJoinRequests($groupid,0,0),'roleid');
        $requested_chapters = array_filter(explode(',', $_USER->getFollowedGroupChapterAsCSV($groupid) ?: ''));
    }

    // Auto Assign Chapter
    $autoAssign = null;
    if (count($requested_chapters)){
        foreach($requested_chapters as $chapterId){
            $encodedRequestedChapters[] = $_COMPANY->encodeId($chapterId);
        }

    }
    $roleCapacityTitle = sprintf(gettext('Please select the maximum number of %1$s you can participate in as a %2$s'),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1), $preSelectedRole['type']);

    $version = $_GET['version'] ?? 'v1';

    $chapterSelectionSetting = $group->getTeamRoleRequestChapterSelectionSetting();
    include(__DIR__ . "/views/talentpeak/common/join_program_options.template.php");
    exit();
}

elseif (isset($_GET['saveTeamJoinRequestData']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($roletypeId =  $_COMPANY->decodeId($_POST['roletype'])) < 0 ||
        ($role = Team::GetTeamRoleType($roletypeId)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (
        ($_ZONE->val('app_type') !== 'talentpeak' && $_ZONE->val('app_type') !== 'peoplehero')
        && $group->isTeamsModuleEnabled()
        && !$_USER->isGroupMember($groupid)
    ) {
        Http::Forbidden();
    }

    $decodedChapterids = 0;
    $chapterSelectionSetting = $group->getTeamRoleRequestChapterSelectionSetting();

    if ($chapterSelectionSetting['allow_chapter_selection']){
        $selectedChapters = $_POST['chapterids'] ?? 0;
        if ($selectedChapters==0 && $group->val('chapter_assign_type') == 'by_user_atleast_one') {
            $msg = sprintf(gettext('You need to select one or more %1$s to join.'), $_COMPANY->getAppCustomization()['chapter']['name-short']);
            AjaxResponse::SuccessAndExit_STRING(0, '', $msg, gettext('Error'));
        } elseif ($selectedChapters==0 && $group->val('chapter_assign_type') == 'by_user_exactly_one') {
            $msg = sprintf(gettext('You need to select a %1$s to join.'),$_COMPANY->getAppCustomization()['chapter']['name-short']);
            AjaxResponse::SuccessAndExit_STRING(0, '', $msg, gettext('Error'));
        }
        if ( $selectedChapters !== 0){
            $decodedChapterids = implode(',',$_COMPANY->decodeIdsInArray($selectedChapters));
        }
    }

    $request_capacity =  intval($_POST['request_capacity'] ?? 1);
    $responseJson = $_POST['responseJson'];

    $retVal = Team::SaveTeamJoinRequestData($groupid, $roletypeId, $responseJson,$request_capacity,true,0, $decodedChapterids);

    if ($retVal) {
        if ($retVal == 2 && ($_ZONE->val('app_type') === 'talentpeak' || $_ZONE->val('app_type') == 'peoplehero')) { // If insert then add users to join group
            $_USER->joinGroup($groupid, $decodedChapterids, 0);
        }
        $retrunStatus = 1;
        if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER'] && 
            $retVal == 2 && // Proccess auto match only when first time request is sent.
            $role['sys_team_role_type'] == 3 &&
            $role['auto_match_with_mentor'] == 1
        ){
            AjaxResponse::SuccessAndExit_STRING(3, $_COMPANY->encodeId($roletypeId), sprintf(gettext('Your request for the %1$s role has been received! Next we will match you with a Mentor'), $role['type']), gettext('Success'));
        }
        AjaxResponse::SuccessAndExit_STRING(1, array($retVal,$role['sys_team_role_type']),  gettext('Registration saved successfully'), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, $retVal, gettext('Something went wrong, please try again.'), gettext('Error'));
}

elseif (isset($_GET['cancelTeamJoinRequest']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($roleid = $_COMPANY->decodeId($_POST['roleid'])) < 0 ||
        ($useridSelected = $_COMPANY->decodeId($_POST['userid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if ($useridSelected != $_USER->id()){
        // Authorization Check
        if (!$_USER->canManageGroupSomething($groupid)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }

    // cancellation witout sending email check
    $customSubject = '';
    $customMessage = '';
    $action = !empty($_POST['action']) && $_POST['action'] == 'decline' ? 'decline' : 'cancel';
    $sendEmailNotification = isset($_POST['sendEmail']) && $_POST['sendEmail'] == 'send_email';
    if ($sendEmailNotification) {
        $customSubject = htmlspecialchars(strip_tags(trim($_POST['subject'] ?? '')), ENT_QUOTES, 'UTF-8');
        $customMessage = htmlspecialchars(strip_tags(trim($_POST['message'] ?? '')), ENT_QUOTES, 'UTF-8');
    }

    // Using the parallel cancellation function here 
    if (Team::CancelTeamJoinRequest($groupid, $roleid, $useridSelected, $action, $sendEmailNotification, $customSubject, $customMessage)) {
        AjaxResponse::SuccessAndExit_STRING(1, ($_USER->id() == $useridSelected),  gettext('Registration canceled successfully'), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0,'', gettext('Something went wrong! Please try again.'), gettext('Error!'));
}

elseif (isset($_GET['togglePauseTeamJoinRequest']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($roleid = $_COMPANY->decodeId($_POST['roleid'])) < 0 ||
        ($useridSelected = $_COMPANY->decodeId($_POST['userid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if ($useridSelected != $_USER->id()){
        // Authorization Check
        if (!$_USER->canManageGroupSomething($groupid)) {
            header(HTTP_FORBIDDEN);
            exit();
        }
    }

    if (Team::TogglePauseTeamJoinRequest($groupid, $roleid, $useridSelected)) {
        AjaxResponse::SuccessAndExit_STRING(1, ($_USER->id() == $useridSelected),  gettext('Request updated successfully'), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0,'', gettext('Something went wrong! Please try again.'), gettext('Error!'));
}

elseif (isset($_GET['toggleTeamJoinRequestStatus']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupid = $_COMPANY->decodeId($_POST['groupid']);
    $roleid = $_COMPANY->decodeId($_POST['roleid']);
    $userid = $_COMPANY->decodeId($_POST['userid']);
    $isactive = $_POST['isactive'];

    $success = Team::ToggleActivateTeamJoinRequestStatus($isactive, $groupid, $roleid, $userid);

    if ($success) {
        AjaxResponse::SuccessAndExit_STRING(
            1,
            '',
            $isactive ? gettext('Registration activated successfully') : gettext('Registration deactivated successfully'),
            gettext('Success')
        );
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong! Please try again.'), gettext('Error!'));
}

elseif (isset($_GET['openUpdateTaskStatusModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 0 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($taskid = $_COMPANY->decodeId($_GET['taskid'])) < 0 ||
        ($task = $team->getTodoDetail($taskid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $taskType = $task['task_type'];

    include(__DIR__ . "/views/talentpeak/common/update_task_status_modal.template.php");
    exit();
}

elseif (isset($_GET['getTeamsMembersReportOptions']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $allRoles = Team::GetProgramTeamRoles($groupid, 1);

    include(__DIR__ . "/views/talentpeak/teams/team_members_download_options.template.php");
    exit();
}

elseif (isset($_GET['downloadTeamsReport'])) {
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['downloadTeamsReport'])) < 1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $teamstatus = array();
    $record = array();

    if (!empty($_POST['teamstatus'])) {
        foreach ($_POST['teamstatus'] as $encTeamStatus) {
            $teamstatus[] = $_COMPANY->decodeId($encTeamStatus);
        }
    }

    $reportMeta = ReportTeamTeams::GetDefaultReportRecForDownload();

    $reportMeta['Filters']['groupids'] = array($groupid); // List of groupids, or empty for all groups
    $reportMeta['Filters']['teamstatus'] = $teamstatus;

    updateReportMetaFieldsFromPOST($reportMeta);

    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'teams';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportTeamTeams ($_COMPANY->id(), $record);
    
    $report->downloadReportAndExit(Report::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}

elseif (isset($_GET['downloadTeamsMembersReport'])) {
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['downloadTeamsMembersReport'])) < 1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $teamstatus = array();
    $teamrolids = array();
    $record = array();

    if (!empty($_POST['teamstatus'])) {
        foreach ($_POST['teamstatus'] as $encTeamStatus) {
            $teamstatus[] = $_COMPANY->decodeId($encTeamStatus);
        }
    }
    if (!empty($_POST['roleids'])) {
        foreach ($_POST['roleids'] as $encRoleid) {
            $teamrolids[] = $_COMPANY->decodeId($encRoleid);
        }
    }

    $reportMeta = ReportTeamMembers::GetDefaultReportRecForDownload();


    $reportMeta['Filters']['groupids'] = array($groupid); // List of groupids, or empty for all groups
    $reportMeta['Filters']['teamstatus'] = $teamstatus;
    $reportMeta['Filters']['roleids'] = $teamrolids;

    updateReportMetaFieldsFromPOST($reportMeta);

    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'team_members';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportTeamMembers ($_COMPANY->id(), $record);

    $report->downloadReportAndExit(Report::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}

elseif (isset($_GET['sendEmailNotificationOfComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1 ||
        ($topicType = $_POST['topictype']) == ''

    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($topicType == 'team_lists') { // For Team action points and Touch points
        $row = null;
        $r = $t = $db->get("SELECT team_tasks.*,users.firstname,users.lastname,users.email,users.picture,u.firstname as creator_firstname,u.lastname as creator_lastname,u.email as creator_email,u.picture as creator_picture FROM `team_tasks` LEFT JOIN users ON users.userid=team_tasks.assignedto LEFT JOIN users as u ON u.userid=team_tasks.`createdby` WHERE team_tasks.taskid='{$topicid}'");
        if (!empty($r)) {
            $row = $r[0];
        }

        if ($row) {
            $team = Team::GetTeam($row['teamid']);
            $teamid = $row['teamid'];
            $task_type = $row['task_type'];

            $baseurl = $_COMPANY->getAppURL($_ZONE->val('app_type'));
            $teamUrl = $baseurl . 'detail?id=' . $_COMPANY->encodeId($team->val('groupid')) . '&hash=getMyTeams#getMyTeams';
            $date = $db->covertUTCtoLocalAdvance('M j, Y  T', "", $row['duedate'], 'UTC');
            $message = "<br>";
            if ($task_type == 'todo') {
                $subject = "A comment has been posted on an Action Item in your development team";
                $message .= "<p>The following comment has been posted on an Action Item in your development team.</p>";
                $message .= "Action Item: {$row['tasktitle']}";
                $message .= "<p>Assignee: {$row['creator_firstname']} {$row['creator_lastname']}</p>";

            } elseif ($task_type == 'touchpoint') {
                $subject = "A comment has been posted on a Touch Point in your development team";
                $message .= "<p>The following comment has been posted on a Touch Point in your development team.</p>";
                $message .= "Touch Point: {$row['tasktitle']}";
            }
            $status = Team::GET_TALENTPEAK_TODO_STATUS;
            $message .= "<p>Due Date: {$date}</p>";
            $message .= "Status: {$status[$row['isactive']]}</p>";
            $message .= "<br/>";
            if ($_POST['message']) {
                $comment = $_POST['message'];
            } else {
                $comment = "<a href='{$teamUrl}'>Attachment</a>";
            }
            $message .= "<p>Comment: {$comment}</p>";
            $message .= "<p>Posted by: {$_USER->val('firstname')} {$_USER->val('lastname')}</p>";
            $message .= "<br>";
            $message .= "<p>Click here to view the comment
            <a href='{$teamUrl}'> {$teamUrl}</a></p>";

            $teamMembers = $team->getTeamMembers(0);
            $emailIds = array();
            foreach ($teamMembers as $member) {
                if (!in_array($member['email'], $emailIds) && $member['email'] != $_USER->val('email')) {
                    $emesg = <<< EOMEOM
                <p>Hi {$member['firstname']},
	{$message}
EOMEOM;
                    $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
                    $emesg = str_replace('#messagehere#', $emesg, $template);
                    $_COMPANY->emailSend2('', $member['email'], $subject, $emesg, $_ZONE->val('app_type'), '');
                }
                $emailIds[] = $member['email'];
            }
        }
    }

    exit();
}

elseif (isset($_GET['initDiscoverTeamMembers']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $joinRequests = Team::GetUserJoinRequests($groupid,0,0);

    if (empty($joinRequests)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('To Discover your matches, please Register for a role'), '');
    }

    $matchingParameters = $group->getTeamMatchingAlgorithmParameters();
    $primary_parameters  = array();
    if (array_key_exists('primary_parameters',$matchingParameters)){
        $primary_parameters  = $matchingParameters['primary_parameters'];
    }

    $customParameters = array();
    if (array_key_exists('custom_parameters',$matchingParameters)){
        $customParameters = $matchingParameters['custom_parameters'];
    }
    
    $searchAttributes = array();
    $_SESSION['showAvailableCapacityOnly'] = $group->getProgramDiscoverSearchAttributes()['default_for_show_only_with_available_capacity'];
    $userPlaceHolder = sprintf(gettext('Filter by %s leader'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    $searchSubject = Team::GetTeamCustomMetaName($group->getTeamProgramType()) . ' Leader';
    if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']){
        $searchAttributes = $group->getProgramDiscoverSearchAttributes();
        $userPlaceHolder = gettext("Filter by first name or last name");
        $searchSubject = '';
    }

    include(__DIR__ . "/views/talentpeak/teams/init_discover_team_members.template.php");
    exit();
}
elseif (isset($_GET['discoverTeamMembers']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $_SESSION['showAvailableCapacityOnly'] = isset($_POST['showAvailableCapacityOnly']) ? $_POST['showAvailableCapacityOnly'] : $group->getProgramDiscoverSearchAttributes()['default_for_show_only_with_available_capacity'];
    $showAvailableCapacityOnly = $_SESSION['showAvailableCapacityOnly'];

    $filter_attribute_keyword = array();
    $filter_primary_attribute = array();
    $filter_attribute_type = array();
    $name_keyword = "";
    $oppositeUseridsWithRoles = array();

    if (!empty($_POST['search'])){
        // All search filter attributes are optional
        $filter_attribute_keyword = $_POST['attribute_keyword'] ?? array();
        $filter_primary_attribute = $_POST['primary_attribute'] ?? array();
        $filter_attribute_type    = explode(',', $_POST['filter_attribute_type']??'');
        $name_keyword = trim($_POST['name_keyword'] ?? '');
    }

    [$status, $roleRequestsWithSuggestions]  = Team::GetTeamMembersSuggestionsForRequestRoles($group,$_USER->id(),0,$oppositeUseridsWithRoles,$filter_attribute_keyword, $filter_primary_attribute, $name_keyword, $filter_attribute_type);

    if ($status == 1) {

        if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['NETWORKING']) { // Create Team automatically

            $suggestions = $roleRequestsWithSuggestions[0]['suggestions'];

            if (!empty($suggestions)) {
                $bestSuggestedUserid = Team::GetBestSuggestedUserForNetworking($groupid, $_USER->id(), $suggestions);
                if ($bestSuggestedUserid){

                    if (Team::CreateNetworkingTeam($groupid,$suggestions[0]['roleid'],$_USER->id(),$bestSuggestedUserid)) {
                        AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext('We found the best match for your requested role and created a %s for you.'),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Success'));
                    }
                }
            }
            AjaxResponse::SuccessAndExit_STRING(2, '', gettext('We tried to automatically match you with the best role for your request, but no match was found. Please stay tuned.'), gettext('Info'));

        } else{
            $matchingParameters = $group->getTeamMatchingAlgorithmParameters();
            include(__DIR__ . "/views/talentpeak/teams/discover_team_members.template.php");
        }
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('To Discover your matches, please Register for a role'), gettext('Error'));
    }
    exit();
}

elseif (isset($_GET['openSendDiscoverPairRequestModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($receiver_id = $_COMPANY->decodeId($_GET['receiver_id'])) < 1 ||
        ($receiver_roleid = $_COMPANY->decodeId($_GET['receiver_roleid'])) < 1 ||
        ($sender_roleid = $_COMPANY->decodeId($_GET['sender_roleid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $joinRequestDetail = Team::GetUserJoinRequests($groupid,$receiver_id,1);

    if (!$joinRequestDetail) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('The selected user\'s role currently does not allow them to join %1$s. Therefore, you cannot send a %1$s join request.'),Team::GetTeamCustomMetaName($group->getTeamProgramType())),  gettext('Error'));
    }
    $receiverUser = User::GetUser($receiver_id);
    $role = Team::GetTeamRoleType($receiver_roleid);
    $teamCustomName = Team::GetTeamCustomMetaName($group->getTeamProgramType());
    $teamUrl = Url::GetZoneAwareUrlBase($_ZONE->id()) . 'detail?id=' . $_COMPANY->encodeId($groupid) . '&hash=getMyTeams/getTeamReceivedRequests#getMyTeams/getTeamReceivedRequests';
    $groupName = $group->val('groupname') . ' ' . $_COMPANY->getAppCustomization()['group']['name'];

    $emailTemplate = EmailHelper::InviteUserForTeamByRoleEmailTeamplate($receiverUser, $_USER, $role['type'],$teamUrl,$groupName,$teamCustomName);
    $join_request_subject = $emailTemplate['subject'];
    $join_request_message = $emailTemplate['raw_message'];
    include(__DIR__ . "/views/talentpeak/common/send_request_to_discover_matched_user.template.php");
    exit();
}

elseif (isset($_GET['sendRequestToJoinTeam']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($receiver_id = $_COMPANY->decodeId($_POST['receiver_id'])) < 1 ||
        ($receiver_roleid = $_COMPANY->decodeId($_POST['receiver_roleid'])) < 1 ||
        ($sender_roleid = $_COMPANY->decodeId($_POST['sender_roleid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $subject = htmlspecialchars($_POST['subject']);

    if (Str::IsEmptyHTML($_POST['message'])) {
        $_POST['message'] = '';
    }
    $message = $_POST['message'];

    $sendRequest = Team::SendRequestToJoinTeam($groupid, $receiver_id, $receiver_roleid, $sender_roleid,$subject,$message);

    if ($sendRequest) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Request sent successfully'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please let us know which role do you want to join?'), gettext('Error'));
    }

    exit();
}

elseif (isset($_GET['getTeamReceivedRequests']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $receivedRequests = Team::GetAllTeamRequestsReceivedByUser($groupid, $_USER->id());

    include(__DIR__ . "/views/talentpeak/teams/discover_received_requests_list.template.php");
    exit();
}

elseif (isset($_GET['acceptOrRejectTeamRequest']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($request_id = $_COMPANY->decodeId($_POST['request_id'])) < 1 ||
        ($status = $_COMPANY->decodeId($_POST['status'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $rejectionReason = $_POST['rejectionReason']??'';
    $getRequestDetail = Team::GetTeamRequestDetail($groupid,$request_id);
    $groupName = $group->val('groupname') . ' ' . $_COMPANY->getAppCustomization()['group']['name'];

    if ($getRequestDetail){
        $status = ($status == Team::TEAM_REQUEST_STATUS['ACCEPTED']) ? Team::TEAM_REQUEST_STATUS['ACCEPTED'] : Team::TEAM_REQUEST_STATUS['REJECTED'];

        if ($status == Team::TEAM_REQUEST_STATUS['ACCEPTED']){ // check maximum number of concurrent programs support
            if (!Team::CanJoinARoleInTeam($groupid,$_USER->id(),$getRequestDetail['receiver_role_id'])){
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Unable to process your request because maximum capacity for active matches has been reached!'), gettext('Error'));
            }
            if (!Team::CanJoinARoleInTeam($groupid,$getRequestDetail['senderid'],$getRequestDetail['sender_role_id'])){
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext('This person has already matched with someone else and no longer has availability. Please decline their request under the Action button.'), gettext('Error'));
            }
        }
        $resp = Team::AcceptOrRejectTeamRequest($groupid, $request_id, $status, $rejectionReason);

        if ($resp) {
            $receiverUser = $_USER;
            $senderUser = User::GetUser($getRequestDetail['senderid']);
            $senderRole = Team::GetTeamRoleType($getRequestDetail['sender_role_id']);
            $receiverRole = Team::GetTeamRoleType($getRequestDetail['receiver_role_id']);
            $baseurl = $_COMPANY->getAppURL($_ZONE->val('app_type'));
            $teamUrl = $baseurl . 'detail?id=' . $_COMPANY->encodeId($groupid) . '&hash=getMyTeams/initDiscoverTeamMembers#getMyTeams/initDiscoverTeamMembers';
            if ($status == 2) {
                $reMessage = gettext("Request accepted successfully");
                $teamName = $senderUser->getFullName() .' & '.$receiverUser->getFullName();
                $teamid = Team::CreateOrUpdateTeam($groupid, 0, $teamName);
                if ($teamid){
                    $team = Team::GetTeam($teamid);
                    // Add Members
                    $team->addUpdateTeamMember($getRequestDetail['sender_role_id'], $senderUser->id());
                    $team->addUpdateTeamMember($receiverRole['roleid'], $_USER->id());

                    // Clear Team Join request
                    //Team::DeleteTeamRequest($groupid, $request_id);

                    // Update Team Status
                    $team->activate(); // This method will take care of creating touchpiont and task from templates and send email to members
                }
            
            } else {
                $reMessage = gettext("Request rejected successfully");
                $teamCustomName = Team::GetTeamCustomMetaName($group->getTeamProgramType(), 1);
                $emailTemplate = EmailHelper::DeclineTeamRoleRequestEmailTeamplate($receiverUser, $senderUser, $receiverRole['type'], $teamUrl, $groupName, $teamCustomName, $rejectionReason);
                $join_request_subject = $emailTemplate['subject'];
                $join_request_message = $emailTemplate['message'];
                
                if ($senderUser->val('email')){
                    $_COMPANY->emailSend2('', $senderUser->val('email'), $join_request_subject, $join_request_message, $_ZONE->val('app_type'), '');
                }
            }

            AjaxResponse::SuccessAndExit_STRING(1, '', $reMessage, gettext('Success'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong, please try again.'), gettext('Error'));
        }
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Request not exist any more, please check again.'), gettext('Error'));
    }

    exit();
}

elseif (isset($_GET['manageJoinRequests']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        !$group?->isTeamsModuleEnabled()
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $version = $_GET['version'] ?? 'v1';

    $requestedRoleIds = array_column(Team::GetUserJoinRequests($groupid,0,0),'roleid');
    $myTeams = Team::GetMyTeams($groupid);
    $allRoles = Team::GetProgramTeamRoles($groupid, 1);

    require __DIR__ . "/views/talentpeak/teams/manage_join_requests_{$version}.template.php";
    exit();
}

elseif (isset($_GET['viewUnmachedUserSurveyResponses']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($uid = $_COMPANY->decodeId($_GET['userid'])) < 0 ||
        ($roleid = $_COMPANY->decodeId($_GET['roleid'])) < 0

    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (!$group->canDownloadOrViewSurveyReport()){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('View survey responses feature requires allow survey data download option to be enabled'), gettext('Error'));
    }

    $pageTitle = gettext("Survey Responses");

    // GET Question and Answers
    $Fields = array(
        'firstname' => 'firstname',
        'lastname' => 'lastname',
        'roleType' => 'roleType',
    );

    if ($group->canDownloadOrViewSurveyReport()){
        $Fields['question'] = 'Question';
    }

    $meta = array(
        'Fields' => $Fields,
        'Options' => array(
            'download_matched_users'=>0,
            'download_unmatched_users'=>0,
            'download_active_join_requests' => 1,
            'download_inactive_join_requests' => 1,
            'download_paused_join_requests' => 1,
            'download_active_users_only' => 1,
        ),
        'Filters' => array(
            'groupid' => $groupid,
            'userid' => $uid,
            'roleid'=>$roleid
        )
    );

    $usr = User::GetUser($uid);
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'survey';
    $record['reportmeta'] = json_encode($meta);

    $report = new ReportTeamRegistrations($_COMPANY->id(),$record);
    $resp_vals = $report->generateReportAsAssocArray();

    // parse Data
    $questionAnswers = array();
    $usersProfile = array();
    if (!empty($resp_vals)){
        $usersProfile =  array('firstname'=>$resp_vals[0]['firstname'],'lastname'=> $resp_vals[0]['lastname'],'picture'=> $usr->val('picture'),'roleType'=> $resp_vals[0]['roleType']);
        unset( $resp_vals[0]['firstname'], $resp_vals[0]['lastname'], $resp_vals[0]['picture'], $resp_vals[0]['roleType']);
        $questionAnswers  = $resp_vals[0];
    }

    include(__DIR__ . "/views/talentpeak/common/get_unmached_user_survey_responses.template.php");
    exit();
}

elseif (isset($_GET['getTeamsJoinRequestSurveyReportOptions']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $canDownloadOrViewSurveyReport = $group->canDownloadOrViewSurveyReport();
    $reportMeta = ReportTeamRegistrations::GetDefaultReportRecForDownload();
    if (!$group->canDownloadOrViewSurveyReport()){
        unset($reportMeta['Fields']['question']);
    }
    $fields = array();
    if ($reportMeta) {
        $fields = $reportMeta['Fields'];
    }
    $showOnModel = $_GET['showOnModel'] == 1 ? 1 : 0;
    include(__DIR__ . "/views/talentpeak/common/team_join_request_survey_download_options.template.php");
    exit();
}
elseif (isset($_GET['getTeamsFeedbackReportOptions']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $allRoles = Team::GetProgramTeamRoles($groupid, 1);

    $reportMeta = ReportTeamsFeedback::GetDefaultReportRecForDownload();
    // if (!$group->canDownloadOrViewSurveyReport()){
    //     unset($reportMeta['Fields']['question']);
    // }
    $fields = array();
    if ($reportMeta) {
        $fields = $reportMeta['Fields'];
    }
    $excludeAnalyticMetaFields = json_encode(ReportTeamsFeedback::GetMetadataForAnalytics()['ExludeFields']);
    include(__DIR__ . "/views/talentpeak/teams/team_feedback_download_options.template.php");
    exit();
}
elseif (isset($_GET['downloadTeamsFeedbackReport'])) {
    if (($groupid = $_COMPANY->decodeId($_GET['downloadTeamsFeedbackReport'])) < 1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $teamstatus = array();
    $teamrolids = array();
    $record = array();

    if (!empty($_POST['teamstatus'])) {
        foreach ($_POST['teamstatus'] as $encTeamStatus) {
            $teamstatus[] = $_COMPANY->decodeId($encTeamStatus);
        }
    }
    //if (!empty($_POST['roleids'])) {
    //    foreach ($_POST['roleids'] as $encRoleid) {
    //        $teamrolids[] = $_COMPANY->decodeId($encRoleid);
    //    }
    //}

    $reportMeta = ReportTeamsFeedback::GetDefaultReportRecForDownload();
    $reportMeta['Filters']['groupids'] = array($groupid); // List of groupids, or empty for all groups
    $reportMeta['Filters']['teamstatus'] = $teamstatus;
    $reportMeta['Filters']['roleids'] = $teamrolids;

    updateReportMetaFieldsFromPOST($reportMeta);

    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'teams_feedback';
    $record['reportmeta'] = json_encode($reportMeta);
    
    $report = new ReportTeamsFeedback ($_COMPANY->id(), $record);
    $reportAction = $_POST['reportAction'];
    if ( $reportAction == 'download'){    
        $report->downloadReportAndExit(Report::FILE_FORMAT_CSV);
        echo false;
        exit();
    } else {
        $title = gettext("Feedback Report Analytics");
        $analyticsData = $report->generateAnalyticData($title);
        $pagetitle = "Analytics";
        $_SESSION['analyticsPageRefreshed'] = true;
        $_SESSION['analytics_data'] = array();

        if(empty($analyticsData['questions']) || empty($analyticsData['answers'])){
           echo false;
        }else{
            $analyticsTitle = $analyticsData['title'];
            $questionJson = json_encode($analyticsData['questions']);
            $answerJson = json_encode($analyticsData['answers']);
            $totalResponses = count($analyticsData['answers']);
            include(__DIR__ . '/views/templates/analytics.template.php');
        }
    }
}
elseif (isset($_GET['downloadTeamsRequestReport'])) {
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['downloadTeamsRequestReport'])) < 1) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $reportMeta = ReportTeamRequests::GetDefaultReportRecForDownload();


    $reportMeta['Filters']['groupid'] = $groupid; // single groupid

    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'team_requests';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportTeamRequests ($_COMPANY->id(), $record);

    $report->downloadReportAndExit(Report::FILE_FORMAT_CSV);
    #else echo false
    echo false;
    exit();
}
elseif (isset($_GET['downloadUnmachedUsersSurveyResponses']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    set_time_limit(120);
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['downloadUnmachedUsersSurveyResponses'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($uid = $_COMPANY->decodeId($_POST['userid'])) < 0 ||
        ($roleid = $_COMPANY->decodeId($_POST['roleid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit('Bad Request');
    }

    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $download_unmatched_users = 0; // Default
    $download_matched_users = 0; // Default
    if (isset($_POST['downloadOptions'])){
        $downloadOptions = $_POST['downloadOptions'];
        foreach($downloadOptions as $downloadOption){
            if ( $_COMPANY->decodeId($downloadOption) == 2){
                $download_matched_users = 1;
            }

            if ( $_COMPANY->decodeId($downloadOption) == 1){
                $download_unmatched_users = 1;
            }
        }
    }
    $reportMeta = ReportTeamRegistrations::GetDefaultReportRecForDownload();
    if (!$group->canDownloadOrViewSurveyReport()){
        unset($reportMeta['Fields']['question']);
    }

    updateReportMetaFieldsFromPOST($reportMeta);

    $reportMeta['Options'] = array(
        'download_matched_users'=>$download_matched_users,
        'download_unmatched_users'=>$download_unmatched_users,
        'download_active_join_requests' => 1,
        'download_inactive_join_requests' => 1,
        'download_paused_join_requests' => 1,
        'download_active_users_only' => 1,
    );
    $reportMeta['Filters'] = array(
            'groupid' => $groupid,
            'userid' => $uid,
            'roleid'=>$roleid
    );
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'survey';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportTeamRegistrations ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Report::FILE_FORMAT_CSV, 'team_request_survey');
    #else echo false
    echo false;
    exit();
}

elseif (isset($_GET['downloadUnmachedSingleUserSurveyResponses']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    set_time_limit(120);
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['downloadUnmachedSingleUserSurveyResponses'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($uid = $_COMPANY->decodeId($_GET['userid'])) < 0 ||
        ($roleid = $_COMPANY->decodeId($_GET['roleid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit('Bad Request');
    }


    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $download_unmatched_users = 0; // Default
    $download_matched_users = 0; // Default
    $reportMeta = ReportTeamRegistrations::GetDefaultReportRecForDownload();

    updateReportMetaFieldsFromPOST($reportMeta);

    if (!$group->canDownloadOrViewSurveyReport()){
        unset($reportMeta['Fields']['question']);
    }

    $reportMeta['Options'] = array(
        'download_matched_users'=>$download_matched_users,
        'download_unmatched_users'=>$download_unmatched_users,
        'download_active_join_requests' => 1,
        'download_inactive_join_requests' => 1,
        'download_paused_join_requests' => 1,
        'download_active_users_only' => 1,
    );
    $reportMeta['Filters'] = array(
            'groupid' => $groupid,
            'userid' => $uid,
            'roleid'=>$roleid
    );
    $record = array();
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'survey';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportTeamRegistrations ($_COMPANY->id(),$record);
    $report->downloadReportAndExit(Report::FILE_FORMAT_CSV, 'team_request_survey');
    #else echo false
    echo false;
    exit();
}

elseif (isset($_GET['openNewTeamEventForm']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($eventid = $_COMPANY->decodeId($_GET['eventid'])) < 0 ||
        ($touchPointId = $_COMPANY->decodeId($_GET['touchpointid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $teamCustomName = Team::GetTeamCustomMetaName($group->getTeamProgramType());
    $pageTitle = sprintf(gettext("Create New %s Event"),$teamCustomName);
    $type = Event::GetEventTypesByZones([$_ZONE->id()]);
    $touchPoint = $team->getTodoDetail($touchPointId);
    $submitButton = gettext("Send Invitations");
    $availableDaysToSchedule = array();

    if ($_COMPANY->getAppCustomization()['my_schedule']['enabled']) {
        $mentors = $team->getTeamMembersBasedOnSysRoleid(2);

        $sourceDateTimeRanage = array();
        if (!empty($mentors)) {
            $mentorUserid = $mentors[0]['userid'];
            $availableDaysToSchedule = UserSchedule::GetUsersAvailableDaysToSchedule($mentorUserid,true,'team_event', $groupid);
        }
    }

    $event_tz = (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
    $event = null;
    if ($eventid > 0) {
        $submitButton = gettext("Send Updates");
        $pageTitle = sprintf(gettext("Update %s"),$teamCustomName);
        $event = Event::GetEvent($eventid);
    }
    $touchPointTypeConfig = $group->getTouchpointTypeConfiguration();

    if (!empty($availableDaysToSchedule) && $touchPointTypeConfig['enable_mentor_scheduler']) {
        if ($event) {
            $evtDateObj = Date::ConvertDatetimeTimezone($event->val('start'),$event_tz, $event_tz);
            $preSelectDate = $evtDateObj->format('Y-m-d');
        }
        include(__DIR__ . "/views/talentpeak/teamevent/new_team_event_quick_scheduler.php");
    } else {
        if ($event) {
            $event_tz = (!empty($event->val('timezone'))) ? $event->val('timezone') : $event_tz;
            $s_date = $db->covertUTCtoLocalAdvance("Y-m-d", '', $event->val('start'), $event_tz);
            $s_hrs = $db->covertUTCtoLocalAdvance("h", '', $event->val('start'), $event_tz);
            $s_mmt = $db->covertUTCtoLocalAdvance("i", '', $event->val('start'), $event_tz);
            $s_prd = $db->covertUTCtoLocalAdvance("A", '', $event->val('start'), $event_tz);

            #End Date
            $e_date = '';
            $e_hrs = '';
            $e_mnt = '';
            $e_prd = '';

            if ($event->getDurationInSeconds() > 86400) { #Multiday event
                $e_date = $db->covertUTCtoLocalAdvance("Y-m-d", '', $event->val('end'), $event_tz);
                $e_hrs = $db->covertUTCtoLocalAdvance("h", '', $event->val('end'), $event_tz);
                $e_mnt = $db->covertUTCtoLocalAdvance("i", '', $event->val('end'), $event_tz);
                $e_prd = $db->covertUTCtoLocalAdvance("A", '', $event->val('end'), $event_tz);

            } else { #One day event
                $diff = $db->roundTrimTimeDiff($event->val('start') . ' ' . $event_tz, $event->val('end') . ' ' . $event_tz);
                $e_hrs = $diff[0];
                $e_mnt = $diff[1];
            }
        }

        include(__DIR__ . "/views/talentpeak/teamevent/new_team_event.template.php");
    }
    
    exit();
}

elseif(isset($_GET['addOrUpdateTeamEvent']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    /**
     * ## If there are any database related changes for event, make sure to do same on "cloneEvent" method. ##
     */

    //Data Validation
    $event = null;
    $collaborate = '';
    $chapterids = '0';
    $channelid = 0;
    $regionid = 0;
    $isprivate = 0;
    $seriesEvent = null;
    $add_photo_disclaimer = 0;
    $event_series_id = 0;
    $touchpointid = 0;

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($eventid = $_COMPANY->decodeId($_POST['eventid'])) < 0 ||
        ($touchpointid = $_COMPANY->decodeId($_POST['touchpointid'])) < 0 ||
        (isset($_POST['timezone'])
            && !isValidTimeZone($_POST['timezone'])
        )
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($eventid && ($event=TeamEvent::GetEvent($eventid)) === null){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    ViewHelper::ValidateObjectVersionMatchesWithPostAttribute($event);

    // Authorization Check
    if (!$_USER->isProgramTeamMember($teamid) && !$_USER->canManageGroup($groupid)
     ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $eventtitle = $_POST['eventtitle'];
    $eventtype = (int)($_POST['eventtype']??0);
    $event_description = ViewHelper::RedactorContentValidateAndCleanup($_POST['event_description']);
    $event_attendence_type = (int)$_POST['event_attendence_type'];
    $event_contact = trim($_POST['event_contact']);
    $eventvanue = '';
    $vanueaddress = '';
    $venue_info = '';
    $venue_room = ''; 
    $web_conference_link = '';
    $web_conference_detail = '';
    $web_conference_sp = '';
    $latitude = '';
    $longitude = '';
    $multiDayEvent = 0;
    $invited_groups = '';
    $check = array('Event Name' => @$eventtitle,'Event Start Date' => @$_POST['eventdate'], 'Time Start Hours' => @$_POST['hour'], 'Event Contact' => $event_contact);
    if(!$_COMPANY->getAppCustomization()['teams']['team_events']['disable_event_types']){
        $check = array_merge($check,array('Event Type' => @$_POST['eventtype']));
    }
    if (0){
        if(!empty($_POST['multiDayEvent'])){
            $check = array_merge($check,array('Event End Date' => @$_POST['end_date'],'Event End Time' => @$_POST['end_hour']));
            $multiDayEvent = 1;
        } else {
            $check = array_merge($check,array('Event Duration' => @$_POST['hour_duration']));
        }
    }

    if(!empty($_POST['add_photo_disclaimer'])){
        $add_photo_disclaimer = 1;
    }

    $checkin_enabled = 0;
    if ($event_attendence_type ===1 ){
        $check = array_merge($check,array('Venue' => @$_POST['eventvanue']));
        $eventvanue = $_POST['eventvanue'];
        $vanueaddress = $_POST['vanueaddress'] ?: '';
        $venue_info = trim($_POST['venue_info'] ?? '');
        $venue_room = trim($_POST['venue_room'] ?? '');
    } else if($event_attendence_type ===2) {
        $check = array_merge($check,array('Web Conf. Link' => @$_POST['web_conference_link']));
        $web_conference_link = $_POST['web_conference_link'];
        $web_conference_detail = $_POST['web_conference_detail'] ?: '';
        $checkin_enabled = intval($_COMPANY->getAppCustomization()['event']['checkin'] && $_COMPANY->getAppCustomization()['event']['checkin_default']);
    } else if($event_attendence_type ===3) {
        $check = array_merge($check,array('Venue' => @$_POST['eventvanue'],'Web Conf. Link' => @$_POST['web_conference_link']));
        $eventvanue = $_POST['eventvanue'];
        $vanueaddress = $_POST['vanueaddress'] ?: '';
        $venue_info = trim($_POST['venue_info'] ?? '');
        $venue_room = trim($_POST['venue_room'] ?? '');
        $web_conference_link = $_POST['web_conference_link'];
        $web_conference_detail = $_POST['web_conference_detail'] ?: '';
        $checkin_enabled = intval($_COMPANY->getAppCustomization()['event']['checkin'] && $_COMPANY->getAppCustomization()['event']['checkin_default']);
    }

    if (!empty($web_conference_detail)){
        $web_conference_detail = str_replace('\n','<br>',str_replace('\r\n','<br>',$web_conference_detail));
    }

    $checkrequired = $db->checkRequired($check);

    if ($checkrequired) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$checkrequired), gettext('Error'));
    }

    if ($web_conference_link) {

        if (strpos($web_conference_link, '.proofpoint.') !== false ||
            strpos($web_conference_link, '.safelinks.') !== false){
                AjaxResponse::SuccessAndExit_STRING(2, '', gettext("The link provided in the conference link field seems to be invalid. In order to make sure that participants have success, please copy the link directly from the creator's calendar or from the online meeting service provider."), gettext('Error'));
        }

        $web_conference_sp = Event::GetWebConfSPName($web_conference_link);
    }


    $max_inperson = 0;
    $max_inperson_waitlist = 0;
    $max_online = 0;
    $max_online_waitlist = 0;

    #Time zone
    $event_tz = isset($_POST['timezone']) ? $_POST['timezone'] : (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
    $current_timestamp = time();

    #Event Start Date time
    $eventdate = $_POST['eventdate'];
    $hour = $_POST['hour'] ?? '00';
    $minutes = $_POST['minutes'] ?? '00';
    $period = $_POST['period'];
    $startformat = $eventdate . " " . $hour . ":" . $minutes . " " . $period;
    $start = $db->covertLocaltoUTC("Y-m-d H:i:s", $startformat, $event_tz);

    #Event End Date time
    if ($multiDayEvent) {
        $end_date = $_POST['end_date'];
        $end_hour = $_POST['end_hour'] ?? '00';
        $end_minutes = $_POST['end_minutes'] ?? '00';
        $end_period = $_POST['end_period'];
        $endformat = $end_date . " " . $end_hour . ":" . $end_minutes . " " . $end_period;

        #Check if event start date and end date are valid
        if ((($start_timestamp = strtotime($startformat. ' '.$event_tz)) === false) ||
        ($start_timestamp < $current_timestamp) ||
        (($end_timestamp = strtotime($endformat. ' '.$event_tz)) === false) ||
        ($end_timestamp < $current_timestamp)
        ){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Start or End time cannot be in the past"), gettext('Error'));
        }
        if ($end_timestamp < $start_timestamp){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Event end date time cannot be earlier than start date time"), gettext('Error'));
        }
        if ($end_timestamp-$start_timestamp <=86400){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Multi-day events duration must be more than 24 hours"), gettext('Error'));
        }
    } else {
        $hour_duration = $_POST['hour_duration'] ?? '00';
        $minutes_duration = $_POST['minutes_duration'] ?? '00';
        if ($hour_duration == '24') {
            $minutes_duration = '00';
        }
        $add_time = "+" . $hour_duration . " hour +" . $minutes_duration . " minutes";
        $endformat = date('Y-m-d H:i:s', strtotime($add_time, strtotime($startformat)));

        #Check if event start date and end date are valid
        if ((($start_timestamp = strtotime($startformat. ' '.$event_tz)) === false) ||
        ($start_timestamp < $current_timestamp) ||
        (($end_timestamp = strtotime($add_time, $start_timestamp)) === false) ||
        ($end_timestamp < $current_timestamp)){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Start or End time cannot be in the past"), gettext('Error'));
        }
    }
    $end = $db->covertLocaltoUTC("Y-m-d H:i:s", $endformat, $event_tz);

    // Custom Fields
    $custom_fields_input = array();
    $custom_fields_input = json_encode($custom_fields_input);
    $budgeted_amount = 0;
    $calendar_blocks = (int)($_POST['calendar_blocks'] ?? 1);

    if (!$eventid) {
        $eventid = TeamEvent::CreateNewTeamEvent($teamid, $groupid, $chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $event_series_id, $custom_fields_input, $event_contact, $venue_info, $venue_room, $isprivate, 'teamevent', $add_photo_disclaimer, $calendar_blocks);

        if ($eventid) {
            if (!$touchpointid){ // Create and link Touch point
                $touchpointid = $team->addOrUpdateTeamTask(0, $eventtitle, '0', $start, '', 'touchpoint', 0);
            }
            $team->linkTouchpointEventId($touchpointid,$eventid,$end);
            // Publish Email Update
            $delay = 15;
            $event = TeamEvent::GetEvent($eventid);
            $isactive = Event::STATUS_AWAITING;
            $update_code = $event->updateEventForSchedulePublishing($delay);

            $job = new EventJob($groupid, $eventid);
            if (!empty($publish_date)){
                $job->delay = $delay;
            }
            $job->saveAsBatchCreateType(1,array());

            $redirect_to = "eventview?id=" . $_COMPANY->encodeId($eventid);
            AjaxResponse::SuccessAndExit_STRING(1, $redirect_to, gettext("Event saved successfully"), gettext('Success'));
        }

    } else {
        // Groupid cannot be changed for event, since this is update get the groupid from event
        $groupid = $event->val('groupid');
        $doWhat = intval($_POST['do_what'] ?? 1);

        if ($event->isPublished()) {
            // Once published event attendance cannot be changed or downgraded
            // However it can be upgraded, 1->3, 2->3, 4->1, 4->2 is valid. 1->2 or 2->1 or 3->1 or 3->2, 4->!4 is invalid
            if ($event_attendence_type == 1 && !in_array((int)$event->val('event_attendence_type'), array(1, 4))) {
                $event_attendence_type = (int)$event->val('event_attendence_type');
                $eventvanue = '';
                $vanueaddress = '';
                $venue_info = '';
                $venue_room = '';
                $max_inperson = 0;
                $max_inperson_waitlist = 0;
            } elseif ($event_attendence_type == 2 && !in_array((int)$event->val('event_attendence_type'), array(2, 4))) {
                $event_attendence_type = (int)$event->val('event_attendence_type');
                $web_conference_link = '';
                $web_conference_detail = '';
                $web_conference_sp = '';
                $max_online = 0;
                $max_online_waitlist = 0;
            } elseif ($event_attendence_type == 4 && !in_array((int)$event->val('event_attendence_type'), array(4))) {
                $event_attendence_type = (int)$event->val('event_attendence_type');
                $eventvanue = $event->val('eventvanue');
                $vanueaddress = $event->val('vanueaddress');
                $venue_info = '';
                $venue_room = '';
                $max_inperson = $event->val('max_inperson');
                $max_inperson_waitlist = $event->val('max_inperson_waitlist');
                $web_conference_link = $event->val('web_conference_link');
                $web_conference_detail = $event->val('web_conference_detail');
                $web_conference_sp = $event->val('web_conference_sp');
                $max_online = $event->val('max_online');
                $max_online_waitlist = $event->val('max_online_waitlist');
            }

            if (($event_attendence_type == 2 || $event_attendence_type == 3)
                && !filter_var($web_conference_link, FILTER_VALIDATE_URL) ) {
                AjaxResponse::SuccessAndExit_STRING(0,'', gettext("The Web Conference Link is invalid. Please update the Link and try again"), gettext('Error'));
            }

            // Once published checkin cannot be enabled or disabled
            $checkin_enabled = $event->val('checkin_enabled');
        }

        $update  = $event->updateTeamEvent($chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $custom_fields_input, $event_contact, $add_photo_disclaimer, $venue_info, $venue_room, $isprivate, $calendar_blocks);
        $team->linkTouchpointEventId($touchpointid,$eventid,$end);
        // Send Email Update
        $job = new EventJob($groupid, $eventid);
        $job->saveAsBatchUpdateType(1,array(1,2),array());
        $redirect_to = "eventview?id=" . $_COMPANY->encodeId($eventid);
        AjaxResponse::SuccessAndExit_STRING(1, $redirect_to, gettext("Event updated successfully."), gettext('Success'));
    }
}

elseif (isset($_GET['getTeamEventActionButton']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_GET['eventid']))<0 ||
        ($event = TeamEvent::GetEvent($eventid)) === null   ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $showBackLink = false;
    include(__DIR__ . "/views/talentpeak/teamevent/teamevent_action_button.template.php");

}

elseif(isset($_GET['deleteTeamEvent'])){
    //Data Validation
    if (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1 ||
        ($event = TeamEvent::GetEvent($eventid)) === NULL ||
        ($team = Team::GetTeam($event->val('teamid'))) == null
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->isProgramTeamMember($event->val('teamid')) && !$_USER->canManageGroup($event->val('groupid'))
    ) { //Allow creators to delete unpublished content
        header(HTTP_FORBIDDEN);
        exit();
    }

    $event_cancel_reason = $_POST['event_cancel_reason'] ?? '';
    $sendCancellationEmails = true; // For team events always send cancellation emails.
    if ($event->cancelEvent($event_cancel_reason, $sendCancellationEmails)) {
        $team->unlinkEventFromTouchpoint($eventid);
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Event deleted successfully.'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Success'));
    }
}

elseif (isset($_GET['getTeamsEventsTimeline'])){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 || ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $showGlobalChapterOnly = false;
    $showGlobalChannelOnly =false;
    $chapterid = 0;
    $channelid = 0;

    if ($_COMPANY->getAppCustomization()['chapter']['enabled']){
        $showGlobalChapterOnly = boolval($_SESSION['showGlobalChapterOnly'] ?? false);
        if (isset($_GET['chapterid']) && ($chapterid = $_COMPANY->decodeId($_GET['chapterid'])) < 0){
            header(HTTP_BAD_REQUEST);
            exit();
        }
    }
    if ($_COMPANY->getAppCustomization()['channel']['enabled']){
        $showGlobalChannelOnly = boolval($_SESSION['showGlobalChannelOnly'] ?? false);
        if ((isset($_GET['channelid']) && ($channelid = $_COMPANY->decodeId($_GET['channelid'])) < 0)){
            header(HTTP_BAD_REQUEST);
            exit();
        }
    }

    // Authorization Check
    if (!$_USER->canViewContent($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $page = 1;
    if (isset($_GET['page']) && (int)$_GET['page'] > 0) {
        $page = (int)$_GET['page'];
    }
    $lastMonth = $_GET['lastMonth'] ?? '';
    $limit = 10;
    $type = $_GET['type'] == 2 ? 2 : 1;
    $newEventsOnly = $type===1;
    $pinnedEvents = array();
    $filterByStartDateTime = '';
    $filterByEndDateTime = '';
    $filterByVolunteer = 0;
    if (!empty($_GET['by_start_date'])) {
        $filterByStartDateTime = (string)$_GET['by_start_date'].' 00:00:00';
    }
    if (!empty($_GET['by_end_date'])) {
        $filterByEndDateTime = (string)$_GET['by_end_date'].' 23:59:59';
        if ($newEventsOnly && empty($filterByStartDateTime)) {
            $filterByStartDateTime = $_USER->getLocalDateNow().' 00:00:00';
        }
    }
    if (!empty($_GET['by_volunteer'])) {
        $filterByVolunteer = $_COMPANY->decodeId($_GET['by_volunteer']);
    }
    $timezone = $_SESSION['timezone'] ?: 'UTC';
    $data = Event::GetGroupEventsViewData(Event::EVENT_CLASS['TEAMEVENT'], $groupid, $showGlobalChapterOnly, $chapterid, $showGlobalChannelOnly, $channelid, $page, $limit, $newEventsOnly, null, $timezone, $filterByStartDateTime,  $filterByEndDateTime , $filterByVolunteer);
    $max_iter = count($data);
    $show_more = ($max_iter > $limit);
    $max_iter = $show_more ? $limit : $max_iter;
    $section = "teamEvents";

    if ($newEventsOnly) {
        $noDataMessage = sprintf(gettext("Stay tuned for %s upcoming Events to be scheduled"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));
    } else {
        $noDataMessage = sprintf(gettext("No %s past events found"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));
    }
    if ($page === 1) {
        // Note:
        // When fetching the first page, build the frame using get_events_timeline; internally it will call
        // get_events_timeline_rows
        include(__DIR__ . "/views/templates/get_events_timeline.template.php");
    } else {
        // For all other cases, i.e. to load the fragments; call events_timeline_rows
        include(__DIR__ . "/views/templates/get_events_timeline_rows.template.php");
    }
    exit();
}

elseif (isset($_GET['updateTeamBulkAction']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
// Check here
    $action = $_POST['action'];
    if (in_array($action, array('draft_to_active','active_to_inactive','inactive_to_active','active_to_complete','delete_all_draft','active_to_incomplete','active_to_paused','paused_to_active'))){
     
        if ($action == 'draft_to_active') {
            $isActiveStatus = 2;
        } elseif ($action == 'active_to_inactive') {
            $isActiveStatus = 1;
        } elseif ($action == 'inactive_to_active') {
            $isActiveStatus = 0;
        } elseif ($action == 'active_to_complete') {
            $isActiveStatus = 1;
        } elseif ($action == 'active_to_incomplete') {
            $isActiveStatus = 1;
        } elseif ($action == 'active_to_paused') {
            $isActiveStatus = 1;
        } elseif ($action == 'paused_to_active') {
            $isActiveStatus = Team::STATUS_PAUSED;
        } elseif ($action == 'delete_all_draft') {
            $isActiveStatus = 2;
        } else {
            $isActiveStatus = -1;
        }

        $teamsToUpdate = Team::GetTeamIdsToBulkUpdateByAction($groupid,$isActiveStatus);
        if (!empty($teamsToUpdate)){
            $finalTeamsToUpdate = array();
            [$pendingActionItemsCount,$pendingTouchPointsCount] = [0, 0];
            foreach ($teamsToUpdate as $k) {
                if($action == 'active_to_complete'){
                    $team = Team::GetTeam($k['teamid']);
                    [$pendingActionItemsCount,$pendingTouchPointsCount] = $team->GetPendingActionItemAndTouchPoints();
                }
  
                if ($pendingActionItemsCount < 1 && $pendingTouchPointsCount < 1) {
                    if ($_USER->canManageContentInScopeCSV($k['groupid'], $k['chapterid'])) {
                        $k['teamid'] = $_COMPANY->encodeId($k['teamid']);
                        unset($k['groupid'],$k['chapterid']);
                        $k['team_name'] = htmlspecialchars($k['team_name']);
                        $finalTeamsToUpdate[] = $k;
                    }
                }
            }
            if (!empty($finalTeamsToUpdate)){
                AjaxResponse::SuccessAndExit_STRING(1, $finalTeamsToUpdate, gettext('Bulk update started'), gettext('Success'));
            }
        }
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Nothing to update'), gettext('Alert'));

    } else {
        AjaxResponse::SuccessAndExit_STRING(0,'', gettext("Action not valid"), gettext('Error'));
    }
}


elseif (isset($_GET['processTeamBulkAction']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) === null ||
        ($group = Group::GetGroup($team->val('groupid'))) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageContentInScopeCSV($team->val('groupid'), $team->val('chapterid'))) {
        AjaxResponse::SuccessAndExit_STRING(0,$team->val('team_name'), gettext("Access Denied"), gettext('Error'));
    }
// Check here
    $action = $_POST['action'];
    if (in_array($action, array('draft_to_active','active_to_inactive','inactive_to_active','active_to_complete','delete_all_draft','active_to_incomplete','active_to_paused','paused_to_active'))){

        if ($action == 'draft_to_active' || $action == 'inactive_to_active' || $action == 'paused_to_active'){
            $response = $team->activate();

            if ($response['status']){
                AjaxResponse::SuccessAndExit_STRING(1, $team->val('team_name'), gettext('Updated successfully.'), gettext('Success'));
            } else {
                AjaxResponse::SuccessAndExit_STRING(0, sprintf(gettext('%1$s (Error: %2$s)'),htmlspecialchars($team->val('team_name')), trim($response['error'],' .')), gettext('Error'), gettext('Error'));
            }
        } elseif ($action == 'active_to_inactive'){

            $team->deactivate();
            AjaxResponse::SuccessAndExit_STRING(1, $team->val('team_name'), gettext('Updated successfully.'), gettext('Success'));
        } elseif ($action == 'active_to_complete'){

            $team->complete();
            AjaxResponse::SuccessAndExit_STRING(1, $team->val('team_name'), gettext('Updated successfully.'), gettext('Success'));
        } elseif ($action == 'active_to_incomplete'){

            $team->incomplete();
            AjaxResponse::SuccessAndExit_STRING(1, $team->val('team_name'), gettext('Updated successfully.'), gettext('Success'));
        } elseif ($action == 'active_to_paused') {
            $team->paused();
            AjaxResponse::SuccessAndExit_STRING(1, $team->val('team_name'), gettext('Updated successfully.'), gettext('Success'));
        }elseif ($action == 'delete_all_draft'){
            $team->deleteTeamPermanently();
            AjaxResponse::SuccessAndExit_STRING(1, $team->val('team_name'), sprintf(gettext('%s deleted successfully'),Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Success'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, $team->val('team_name'), gettext('Updated Failed.'), gettext('Success'));
        }

    } else {
        AjaxResponse::SuccessAndExit_STRING(0,'', gettext("Action not valid"), gettext('Error'));
    }
}

elseif (isset($_GET['importTeams']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 0 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $pageTitle = sprintf(gettext("Import %s from a CSV file"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));
    $chapterHead = $_COMPANY->getAppCustomization()['chapter']['enabled'] ? ",chapter_name" : '';
    $chapterRowValue = ($_COMPANY->getAppCustomization()['chapter']['enabled'] ? ",ChapterName" : '' );
    
    if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']){
        $csvFormat = "data:text/csv;charset=utf-8,external_id,email,role_name".$chapterHead.",team_name,description,hashtags,role_title%0AE74234,john.doe@domain.com,Mentor".$chapterRowValue.",Team_1,Team description here,#hest;#rest,roleTitle_1%0AE74235,jane.doe@domain.com,Mentee".$chapterRowValue.",Team_2,team_2 description here, #hest;#rest,roleTitle_2";
    } else {
        $csvFormat = "data:text/csv;charset=utf-8,external_id,email,role_name".$chapterHead.",team_name,role_title%0AE74234,john.doe@domain.com,Mentor".$chapterRowValue.",Team_1,roleTitle_1%0AE74235,jane.doe@domain.com,Mentee".$chapterRowValue.",Team_2,roleTitle_2";
    }

    include(__DIR__ . "/views/talentpeak/teams/import_team_form_csv_file.template.php");
    exit();
}

elseif (isset($_GET['importTeamRegistrations']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 0 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $pageTitle = sprintf(gettext("Import %s Registrations from a CSV file"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),0));

    include(__DIR__ . "/views/talentpeak/teams/import_registration_form_csv_file.template.php");
    exit();
}

elseif (isset($_GET['submitImportTeamData']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $response = array();
    if(!empty($_FILES['import_file']['name'])){
        $file 	   		=	basename($_FILES['import_file']['name']);
        $tmp 			=	$_FILES['import_file']['tmp_name'];
        $ext = substr(pathinfo($file)['extension'],0,4);
        // Allow certain file formats
        if($ext != "csv"  ) {
            $error =  gettext("Sorry, only .csv file format allowed");
            $response = array('status'=>0,'title'=>gettext('Error'),'message'=>$error);
        }

        if (empty($response)) {
            try {
                $csv = Csv::ParseFile($tmp);
                if ($csv) {
                    $auto_extend_capacity = $_POST['auto_extend_capacity'] ?? 0;
                    $success = Team::ProcessTeamProvisioningData($group,$csv,$auto_extend_capacity);
                    $response = array('status'=>1,'message'=>gettext('Data imported successfully'), 'data'=>$success );
                } else {
                    $response = array('status'=>0,'title'=>gettext('Success'),'message'=>gettext('CSV file format issue'));
                }
            } catch (Exception $e) {
                //$error = $e->getMessage();
                $response = array('status'=>0,'title'=>gettext('Error'),'message'=>$e->getMessage());
            }
        }
    } else {
        $response = array('status'=>0,'title'=>gettext('Error'),'message'=>gettext('Please select a csv file'));
    }
    echo json_encode($response);
    exit();
}

elseif (isset($_GET['submitImportTeamRegistrationData']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $response = array();
    if(!empty($_FILES['import_file']['name'])){
        $file 	   		=	basename($_FILES['import_file']['name']);
        $tmp 			=	$_FILES['import_file']['tmp_name'];
        $ext = substr(pathinfo($file)['extension'],0,4);
        // Allow certain file formats
        if($ext != "csv"  ) {
            $error =  gettext("Sorry, only .csv file format allowed");
            $response = array('status'=>0,'title'=>gettext('Error'),'message'=>$error);
        }

        $send_emails = !empty($_POST['send_emails']) ? 1 : 0;

        if (empty($response)) {
            try {
                $csv = Csv::ParseFile($tmp);
                if ($csv) {
                    $success = Team::ProcessTeamRegistrationProvisioningData($group,$csv,$send_emails);
                    $response = array('status'=>1,'message'=>gettext('Data imported successfully'), 'data'=>$success );
                } else {
                    $response = array('status'=>0,'title'=>gettext('Success'),'message'=>gettext('CSV file format issue'));
                }
            } catch (Exception $e) {
                //$error = $e->getMessage();
                $response = array('status'=>0,'title'=>gettext('Error'),'message'=>$e->getMessage());
            }
        }
    } else {
        $response = array('status'=>0,'title'=>gettext('Error'),'message'=>gettext('Please select a csv file'));
    }
    echo json_encode($response);
    exit();
}

elseif (isset($_GET['updateTeamJoinRequestSurveyResponseByManagerModal']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $foruid = -1;
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($foruid = $_COMPANY->decodeId($_GET['foruid'])) < 0 ||
        ($roleid = $_COMPANY->decodeId($_GET['roleid'])) < 0 ||
        ($forUser = User::GetUser($foruid)) === null

    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Only program leads can update surveys on behalf of others
    if (!$_USER->canPublishContentInGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if (!$group->canDownloadOrViewSurveyReport()){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Update survey responses feature requires allow survey data download option to be enabled!'), gettext('Error'));
    }

    $preSelectedRole = Team::GetTeamRoleType($roleid);
    $questionJson = $group->getTeamMatchingAlgorithmAttributes();
    $isQuestionAvailable = count($questionJson);
    $questionJson = json_encode($questionJson);
    $joinRequest = Team::GetRequestDetail($groupid,$roleid,$foruid);
    $modalTitle = sprintf(gettext('Update %s role survey responses for %s'),strtolower($preSelectedRole['type']), $forUser->getFullName());
    $submitBtn = gettext("Update Responses");

    $all_chapters= Group::GetChapterList($groupid);
    $requested_chapters = explode(',',$joinRequest['chapterids']);
    $encodedRequestedChapters = $_COMPANY->encodeIdsInArray($requested_chapters);
    $chapterSelectionSetting = $group->getTeamRoleRequestChapterSelectionSetting();
    $roleCapacityTitle = gettext("Please select the number of Mentees you are able to support");
    include(__DIR__ . "/views/talentpeak/common/update_team_join_request_survey_response_by_manager.template.php");
    exit();
}

elseif (isset($_GET['updateTeamJoinRequestSurveyResponseByManager']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $foruid = -1;
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($foruid = $_COMPANY->decodeId($_POST['foruid'])) < 0 ||
        ($roletypeId =  $_COMPANY->decodeId($_POST['roletype'])) < 0 ||
        (($request_capacity = intval($_POST['request_capacity'])) < 1)
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Only program leads can update surveys on behalf of others
    if (!$_USER->canManageGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $selectedChapters = $_POST['chapterids'] ?? 0;

    $decodedChapterids = 0;
    $chapterSelectionSetting = $group->getTeamRoleRequestChapterSelectionSetting();

    if ($chapterSelectionSetting['allow_chapter_selection']){

        if ($selectedChapters==0 && $group->val('chapter_assign_type') == 'by_user_atleast_one') {
            $msg = sprintf(gettext('You need to select one or more %1$s to join.'), $_COMPANY->getAppCustomization()['chapter']['name-short']);
            AjaxResponse::SuccessAndExit_STRING(0, '', $msg, gettext('Error'));
        } elseif ($selectedChapters==0 && $group->val('chapter_assign_type') == 'by_user_exactly_one') {
            $msg = sprintf(gettext('You need to select a %1$s to join.'),$_COMPANY->getAppCustomization()['chapter']['name-short']);
            AjaxResponse::SuccessAndExit_STRING(0, '', $msg, gettext('Error'));
        }

        if ( $selectedChapters !== 0){
            $decodedChapterids = implode(',',$_COMPANY->decodeIdsInArray($selectedChapters));
        }
    }

    $responseJson = $_POST['responseJson'];
    $retVal = Team::SaveTeamJoinRequestData($groupid, $roletypeId, $responseJson,$request_capacity, false, $foruid,$decodedChapterids);
    if ($retVal) {
        AjaxResponse::SuccessAndExit_STRING(1, $retVal,  gettext('Survey response updated successfully'), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, $retVal, gettext('Something went wrong, please try again.'), gettext('Error'));
}

///// TEAM setup feature


elseif (isset($_GET['manageTeamsConfiguration']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $hiddenTabs = $group->getHiddenProgramTabSetting();

    include(__DIR__ . "/views/talentpeak/team_configuration/manage_teams_configuration_container.template.php");
    exit();
}

elseif (isset($_GET['manageProgramTeamSetting']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $program_type_value = $group->getTeamProgramType();
    $hiddenTabs = $group->getHiddenProgramTabSetting();
    $notificationSetting = $group->getTeamInactivityNotificationsSetting();
    $progressBarSetting = $group->getActionItemTouchPointProgressBarSetting();
	include(__DIR__ . "/views/talentpeak/team_configuration/manage_program_team_configuration.template.php");
}


elseif (isset($_GET['manageProgramTeamRoles']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	$data = Team::GetProgramTeamRoles($groupid);

    $showAddRoleButton = true;
    if ($group->getTeamProgramType()== Team::TEAM_PROGRAM_TYPE['NETWORKING'] || $group->getTeamProgramType()== Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){
        if(count(Team::GetProgramTeamRoles($groupid))) {
            $showAddRoleButton = false;
        }
    } elseif($group->getTeamProgramType()== Team::TEAM_PROGRAM_TYPE['PEER_2_PEER']) {
        $activeMentorRoleCount = count(array_filter($data, function($item) {
            return $item['isactive'] == 1 && $item['sys_team_role_type'] == 2;
        }));

        $activeMenteeRoleCount = count(array_filter($data, function($item) {
            return $item['isactive'] == 1 && $item['sys_team_role_type'] == 3;
        }));
       

        if ($activeMentorRoleCount > 0 && $activeMenteeRoleCount > 0) {
            $showAddRoleButton = false;
        }
    }


	include(__DIR__ . "/views/talentpeak/team_configuration/manage_program_team_roles.template.php");
}

elseif (isset($_GET['showAddUpdateProgramRoleModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($id = $_COMPANY->decodeId($_GET['roleid']))<0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $pageTitle = sprintf(gettext('New %s Role Type'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    $edit = null;
    $editRestrictions = array();
    $showRoleCapacity = 'none';
    $catalog_categories = array_values(UserCatalog::GetAllCatalogCategories());

    $restrictSystemRoles = array();
    if (!$id && $group->getTeamProgramType()== Team::TEAM_PROGRAM_TYPE['PEER_2_PEER']) {
        $restrictSystemRoles[] = 1; // Do not allow admin role
        $activeRoles = Team::GetProgramTeamRoles($groupid);
        $activeMentorRoleCount = count(array_filter($activeRoles, function($item) {
            return $item['isactive'] == 1 && $item['sys_team_role_type'] == 2;
        }));
        if ($activeMentorRoleCount >0) {
            $restrictSystemRoles[] = 2; // Do not allow Mentor role
        }
    
        $activeMenteeRoleCount = count(array_filter($activeRoles, function($item) {
            return $item['isactive'] == 1 && $item['sys_team_role_type'] == 3;
        }));
        if ($activeMenteeRoleCount >0) {
            $restrictSystemRoles[] = 3; // Do not allow Mentee role
        }
    }

    if ($id >0 ){
        $edit = Team::GetTeamRoleType($id);
        $editRestrictions = json_decode($edit['restrictions'],true);
        if ($edit['sys_team_role_type'] == 2){ // Mentor only
            $showRoleCapacity = 'block';
        }
        $pageTitle = sprintf(gettext('Update %s Role Type'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    }



    include(__DIR__ . "/views/talentpeak/team_configuration/add_update_team_roles.template.php");

}

elseif (isset($_GET['addUpdateProgramRole']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($roleid = $_COMPANY->decodeId($_POST['roleid']))<0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $checkrequired = $db->checkRequired(array('Team Role type' => $_POST['type'],'System Role Type'=>$_POST['sys_team_role_type'],'Minimum Required'=>$_POST['min_required'], 'Maximum Allowed'=>$_POST['max_allowed']));

    if ($checkrequired) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$checkrequired), gettext('Error'));
    }

    $type  =  	Sanitizer::SanitizeRoleName($_POST['type']);
    $teamCustomName = Team::GetTeamCustomMetaName($group->getTeamProgramType());
    if($type === $_POST['type']){
        $sys_team_role_type = $_COMPANY->decodeId($_POST['sys_team_role_type']);
        $welcome_message = ViewHelper::RedactorContentValidateAndCleanup($_POST['welcome_message']);
        $welcome_email_subject = $_POST['welcome_email_subject'] ?: "Your {$group->val('groupname')} {$teamCustomName} is now Active";

        $completion_message = ViewHelper::RedactorContentValidateAndCleanup($_POST['completion_message']);
        $completion_email_subject = $_POST['completion_email_subject'] ?: "Your {$group->val('groupname')} {$teamCustomName} is now Completed";

        $min_required = intval($_POST['min_required'] ?? 0);
        $max_allowed = intval($_POST['max_allowed'] ?? 1);
        $discover_tab_show = intval($_POST['discover_tab_show'] ?? 1);
        $discover_tab_html = $_POST['discover_tab_html'] ?? '';
        $joinrequest_email_subject = $_POST['joinrequest_email_subject'];
        $joinrequest_message = ViewHelper::RedactorContentValidateAndCleanup($_POST['joinrequest_message']);
        $catalog_categories = array_values(UserCatalog::GetAllCatalogCategories());
        $restrictions = array();
        foreach($catalog_categories as $category){
            $categoryPoststr = str_replace(' ','_',$category); // Convert _ to space.
            if (isset($_POST[$categoryPoststr])){
                $logicType = (int) $_POST[$categoryPoststr];
                $keys = array();
                if (isset($_POST[$categoryPoststr.'_val'])){
                    $keys =  (array) $_POST[$categoryPoststr.'_val'];
                }
                $restrictions[$category]  = array('type'=>$logicType,'keys'=>$keys); // Do not use categoryname_str here
            }
        }
        $restrictions = json_encode($restrictions);
        $registration_start_date = "";
        $registration_end_date = "";
        $role_capacity =  (int)$_POST['role_capacity'];
        $role_request_buffer  = intval($_POST['role_request_buffer'] ?? 0);

        if (!empty($_POST['registration_start_date'])) {
            $registration_start_date = date_format(date_create($_POST['registration_start_date']),"Y-m-d");
        }
        if (!empty($_POST['registration_end_date'])) {
            $registration_end_date = date_format(date_create($_POST['registration_end_date']),"Y-m-d");
        }

        $hide_on_request_to_join = 0;
        if (isset($_POST['hide_on_request_to_join'])){
            $hide_on_request_to_join = $_POST['hide_on_request_to_join'];
        }

        $action_on_member_termination = isset(Team::GetRoleOptionsOnLeaveMemberTermination($group->id())[$_POST['action_on_member_termination']]) ? $_POST['action_on_member_termination'] : 'leave_as_is';
        $member_termination_email_subject=$_POST['member_termination_email_subject']??'';
        $member_termination_message = ViewHelper::RedactorContentValidateAndCleanup($_POST['member_termination_message']);

        if ($min_required > $max_allowed){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Minimum required value can not greater than Max allowed value"), gettext('Error'));
        } else {
            $role = Team::GetTeamRoleByName($type,$groupid,false);
            if ($role && $role['roleid'] != $roleid){
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Role name must be unique"), gettext('Error'));
            } else{

                $email_on_member_termination = 0;
                if (isset($_POST['email_on_member_termination'])){
                    $email_on_member_termination = $_POST['email_on_member_termination'];
                }

                $auto_match_with_mentor = isset($_POST['auto_match_with_mentor']) ? 1 : 0;
                $maximum_registrations = isset($_POST['maximum_registrations']) ? intval($_POST['maximum_registrations']) : 0;

                if (Team::AddOrUpdateTeamRole($roleid,$groupid,$type,$sys_team_role_type,$min_required,$max_allowed,$discover_tab_show,$discover_tab_html,$welcome_message,$restrictions,$welcome_email_subject,$registration_start_date,$registration_end_date,$role_capacity,$role_request_buffer,$hide_on_request_to_join,$joinrequest_email_subject,$joinrequest_message,$completion_message,$completion_email_subject, $action_on_member_termination, $member_termination_email_subject, $member_termination_message,$email_on_member_termination,$auto_match_with_mentor,$maximum_registrations)){
                    $reload = 0;
                    if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['NETWORKING'] && $roleid == 0){
                        $reload = 1;
                    }

                    if ($roleid) {
                        $msg = gettext("Role updated successfully");
                    } else {
                        $msg = gettext("Role created successfully");
                    }

                    AjaxResponse::SuccessAndExit_STRING(1, $reload, $msg, gettext('Success'));
                } else {
                    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again"), gettext('Error'));
                }
            }
        }
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Invalid Role Type: only alpha numeric characters are allowed."), gettext('Error'));
    }
}

elseif (isset($_GET['enableDisableTeamRoleType']) && $_SERVER['REQUEST_METHOD'] === 'POST'){


    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL ||
        ($roleid = $_COMPANY->decodeId($_POST['id']))<1 ||
        ($teamRole = Team::GetTeamRoleType($roleid)) === NULL
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $status = $_POST['status'];
    if ($status == 1 && $group->getTeamProgramType()== Team::TEAM_PROGRAM_TYPE['PEER_2_PEER']) {
        $activeRoles = Team::GetProgramTeamRoles($groupid);
        $activeRoleCount = count(array_filter($activeRoles, function($item) use ($teamRole) {
            return $item['isactive'] == 1 && $item['sys_team_role_type'] == $teamRole['sys_team_role_type'];
        }));

        if ($activeRoleCount>0) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Action cannot be completed because only one role per system role type is allowed for this %s.'),$_COMPANY->getAppCustomization()['group']['name-short']), gettext('Error'));
        }
    }
    
    Team::UpdateTeamRoleStatus($groupid,$roleid,$status);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Updated successfully"), gettext('Success'));
}

elseif (isset($_GET['showHideProgramTabSetting']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $hidden_tabs =  isset($_POST['hidden_tabs']) ? (array) $_POST['hidden_tabs'] : [];

    echo $group->updateHiddenProgramTabSetting($hidden_tabs);


}
elseif (isset($_GET['saveTeamInactivityNotificationsSetting']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($notification_days_after = $_POST['notification_days_after'])<0 ||
        ($notification_frequency =$_POST['notification_frequency'])<0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    echo $group->saveTeamInactivityNotificationsSetting($notification_days_after,$notification_frequency);
}

elseif (isset($_GET['manageTeamActionItemsTemplates']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
	$actionItems = $group->getTeamActionItemTemplate();
    if (!empty($actionItems)) {
        usort($actionItems, function ($a, $b) {
            return ($a['tat'] <=> $b['tat']);
        });
    }
	include(__DIR__ . "/views/talentpeak/team_configuration/manage_team_action_items_templates.php");
}

elseif (isset($_GET['showAddUpdateProgramActionItemTemplateModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $edit = null;
    $id = -1;
    $pageTitle = sprintf(gettext('New %s Action Item Template'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));

    if (isset($_GET['taskid']) && ($id = $_COMPANY->decodeId($_GET['taskid']))>=0){
        $actionItems = $group->getTeamActionItemTemplate();
        if (!empty($actionItems)){
			usort($actionItems, function ($a, $b) {
				return ($a['tat'] <=> $b['tat']);
			});
		}
        $edit = $actionItems[$id];
        $pageTitle = sprintf(gettext('Update %s Action Item Template'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    }
    $teamRoles = Team::GetProgramTeamRoles($groupid,1);

    include(__DIR__ . "/views/talentpeak/team_configuration/add_update_program_action_item.template.php");

}

elseif (isset($_GET['addUpdateProgramActionItemTemplate']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $id = -1;
    if (!empty($_POST['taskid'])){
        $id = $_COMPANY->decodeId($_POST['taskid']);
    }

    $checkrequired = $db->checkRequired(array('Action Item Title' =>  @$_POST['title'],'Assigned To'=>@$_POST['assignedto']));

    if ($checkrequired) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$checkrequired), gettext('Error'));
    }
    $title = Sanitizer::SanitizeActionItemTitle($_POST['title']);
    if($title === $_POST['title']){
        $tat 	        =	(int)$_POST['tat'];
        $assignedto     =	$_COMPANY->decodeId($_POST['assignedto']);
        $description    =   ViewHelper::RedactorContentValidateAndCleanup($_POST['description']);
        if ($group->addUpdateTeamActionItemTemplateItem($id,$title,$assignedto,$tat,$description)){
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Action Item created/updated successfully"), gettext('Success'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again"), gettext('Error'));
        }
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Invalid Action Item Title: only alpha numeric characters and characters like #, &, _, -, (, ), [, ], . and quotes are allowed."), gettext('Error'));
    }
}

elseif (isset($_GET['viewTodoOrTouchPointTemplateDetail']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL ||
        ($id = $_COMPANY->decodeId($_GET['id']))<0 ||
        ($section = $_COMPANY->decodeId($_GET['section']))<1
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $section = $section == 2 ? 2 : 1;
    $data = array();
    $modalTitle= "Detail";
    if ($section == 1){
        $touchpoints = $group->getTeamTouchPointTemplate();
        usort($touchpoints,function($a,$b) {
            return (intval($a['tat'])<=>intval($b['tat']));
        });
       
        $data = $touchpoints[$id];
        $modalTitle= "Touch Point Detail";
    } else {
        $actionItems = $group->getTeamActionItemTemplate();
        usort($actionItems,function($a,$b) {
            return (intval($a['tat'])<=>intval($b['tat']));
        });
        $data = $actionItems[$id];
        $modalTitle= "Action Item Detail";
        $teamRoles = Team::GetProgramTeamRoles($groupid,1);
    }

    include(__DIR__ . "/views/talentpeak/team_configuration/view_todo_or_touchpoint_template_detail.php");
}


elseif (isset($_GET['deleteTeamActionIteamTemplate']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($id = $_COMPANY->decodeId($_POST['id']))<0 ||
        ($group = Group::GetGroup($groupid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	echo $group->deleteTeamActionItemTemplateItem($id);
}

elseif (isset($_GET['manageTeamTouchpointsTemplates']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	$touchpoints = $group->getTeamTouchPointTemplate();

    if (!empty($touchpoints)) {
        usort($touchpoints, function ($a, $b) {
            return ($a['tat'] <=> $b['tat']);
        });
    }
    include(__DIR__ . "/views/talentpeak/team_configuration/manage_team_touchpoints_templates.php");
}


elseif (isset($_GET['showAddUpdateProgramTouchPointTemplateModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $edit = null;
    $id = -1;
    $pageTitle = sprintf(gettext('New %s Touch Point Template'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
   
    $touchpoints = $group->getTeamTouchPointTemplate();
    usort($touchpoints,function($a,$b) {
        return (intval($a['tat'])<=>intval($b['tat']));
    });
    if (isset($_GET['taskid']) && ($id = $_COMPANY->decodeId($_GET['taskid']))>=0){
        $edit = $touchpoints[$id];
        $pageTitle = sprintf(gettext('Update %s Touch Point Template'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    }

    $lastTat = 1;
    if(!empty($touchpoints)){
        $lastTat = (end($touchpoints)['tat'])+1;// increase by one day for UI
    }

    include(__DIR__ . "/views/talentpeak/team_configuration/add_update_program_touch_point.template.php");

}

elseif (isset($_GET['addUpdateProgramTouchPointTemplate']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $id = -1;
    if (!empty($_POST['taskid'])){
        $id = $_COMPANY->decodeId($_POST['taskid']);
    }

    $checkrequired = $db->checkRequired(array('Touch Point Title' =>  @$_POST['title']));

    if ($checkrequired) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$checkrequired), gettext('Error'));
    }

    $title = Sanitizer::SanitizeTouchPointTitle($_POST['title']);
    if($title === $_POST['title']){
        $description = ViewHelper::RedactorContentValidateAndCleanup($_POST['description']);
        $tat = $_POST['tat'];
        if ($group->addUpdateTouchPointTemplateItem($id,$title,$description,$tat)){
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Touch point created/updated successfully"), gettext('Success'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again"), gettext('Error'));
        }
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Invalid Touch Point Title: only alpha numeric characters and characters like #, &, -, (, ), . and quotes are allowed."), gettext('Error'));
    }
}

elseif (isset($_GET['deleteTeamTouchPointTemplate']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($id = $_COMPANY->decodeId($_POST['id']))<0 ||
        ($group = Group::GetGroup($groupid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    // Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($group->deleteTeamTouchPointTemplateItem($id)){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Touch Point deleted successfully"), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again"), gettext('Error'));
    }
}

elseif (isset($_GET['manageMatchingAlgorithmSetting']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL ||
        in_array($group->getTeamProgramType(), [Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV'],Team::TEAM_PROGRAM_TYPE['CIRCLES']])
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $matchingParameters = $group->getTeamMatchingAlgorithmParameters();
    $customAttributes = $group->getTeamMatchingAlgorithmAttributes();
    $customAttributes = isset($customAttributes['pages'][0]['elements']) ? $customAttributes['pages'][0]['elements'] : $customAttributes;

    $primaryParameters = null;
    $customParameters = null;
    $mandatoryPrimaryParameters = null;
    $mandatoryCustomParameters = null;
    if ($matchingParameters){
        $primaryParameters = $matchingParameters['primary_parameters'] ?? null;
        $customParameters = $matchingParameters['custom_parameters'] ?? null;
        $mandatoryPrimaryParameters = $matchingParameters['mandatory_primary_parameters'] ?? null;
        $mandatoryCustomParameters = $matchingParameters['mandatory_custom_parameters'] ?? null;
    }
    $surveyDownloadSetting = $group->getSurveyDownloadSetting();
    $catalog_categories = array_values(UserCatalog::GetAllCatalogCategories());
    $customAttributesMatchingOptions = TEAM::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS;

    $algorithmMatchingBetweenLabel = array(
        Team::TEAM_PROGRAM_TYPE['ADMIN_LED'] => array ('Mentor','Mentee'),
        Team::TEAM_PROGRAM_TYPE['PEER_2_PEER'] => array ('Mentor','Mentee'),
        Team::TEAM_PROGRAM_TYPE['NETWORKING'] => array ('Participant 1','Participant 2')
    );

    include(__DIR__ . "/views/talentpeak/team_configuration/manage_team_matching_algorithm_templates.php");
}

elseif (isset($_GET['openTouchpointConfigurationModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    // Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $touchPointTypeConfig = $group->getTouchpointTypeConfiguration();

	include(__DIR__ . "/views/talentpeak/team_configuration/manage_team_touchpoint_type_configuration.php");
}



elseif (isset($_GET['updateTouchPointTypeConfig']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    // Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $touchpointtype = $_POST['touchpointtype'];
    $show_copy_to_outlook = filter_var($_POST['show_copy_to_outlook'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $enable_mentor_scheduler = filter_var($_POST['enable_mentor_scheduler'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $auto_approve_proposals = filter_var($_POST['auto_approve_proposals'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
    if ($group->updateTouchpointTypeConfiguration($touchpointtype, $show_copy_to_outlook, $enable_mentor_scheduler, $auto_approve_proposals)){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Touch points configuration updated successfully"), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again"), gettext('Error'));
    }
}

elseif (isset($_GET['updateTeamMatchingAlgorithmParameters'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

	if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $catalog_categories = array_values(UserCatalog::GetAllCatalogCategories());
    $primaryParameters = array();
    $mandatoryPrimaryParameters = array();

    foreach($catalog_categories as $category){
     
        $category_without_spaces = str_replace(' ','_',$category); // PHP POST replaces spaces with _
        if (isset($_POST[$category_without_spaces])){
            $value = $_POST[$category_without_spaces];
            if ($value == 'equals') {
                $value = 1;
            } elseif($value == 'notEquals') {
                $value = 2;
            }
            $primaryParameters[$category] = $value;
        } else {
            $primaryParameters[$category] = -1;
        }

        //if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']){ /* Commenting this as we will diable Matching algo tab for Circles */

        $is_required = isset($_POST[$category_without_spaces.'_is_required']) ? $_POST[$category_without_spaces.'_is_required'] :  -1;
        $visibility_setting = isset($_POST[$category_without_spaces.'_visibility_setting']) ? $_POST[$category_without_spaces.'_visibility_setting'] : array();
        $show_matchp_users = in_array('show_matchp_users',$visibility_setting) ? 'show' : 'hide';
        $show_value_users = in_array('show_value_users',$visibility_setting) ? 'show' : 'hide';
        $show_matchp_leaders = in_array('show_matchp_leaders',$visibility_setting) ? 'show' : 'hide';
        $show_value_leaders = in_array('show_value_leaders',$visibility_setting) ? 'show' : 'hide';
        $matching_adjustment = array();
        if (UserCatalog::GetCategoryKeyType($category) == 'int' && $primaryParameters[$category] == '11'){
            $matching_min_adjustment =  $_POST[$category_without_spaces.'_matching_min_adjustment'] ?? 0;
            $matching_max_adjustment =  $_POST[$category_without_spaces.'_matching_max_adjustment'] ?? 0;
            if (filter_var($matching_min_adjustment, FILTER_VALIDATE_INT) === false || filter_var($matching_max_adjustment, FILTER_VALIDATE_INT) === false){
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("The range match values must be a valid integer"), gettext('Error'));
            }
            if ($matching_min_adjustment>$matching_max_adjustment) {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("The range match minimum value must be less than or equal to the maximum value"), gettext('Error'));
            } 

            $matching_adjustment = array($matching_min_adjustment,$matching_max_adjustment);
        } 

        $mandatoryPrimaryParameters[$category] = array('is_required'=>$is_required,'show_matchp_users'=>$show_matchp_users,'show_value_users'=>$show_value_users,'show_matchp_leaders'=>$show_matchp_leaders,'show_value_leaders'=>$show_value_leaders, 'matching_adjustment'=>$matching_adjustment);

        //}
    }

    $customAttributes = $group->getTeamMatchingAlgorithmAttributes();
    $customAttributes = isset($customAttributes['pages'][0]['elements']) ? $customAttributes['pages'][0]['elements'] : $customAttributes;

    $customParameters = array();
    $mandatoryCustomParameters = array();
    foreach($customAttributes as $attribute){
        if (isset($_POST[$attribute['name']])){
            $customParameters[$attribute['name']] = $_POST[$attribute['name']];
        } else {
            $customParameters[$attribute['name']] = -1;
        }

        //if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']){ /* Commenting this as we will diable Matching algo tab for Circles */

        $is_required = isset($_POST[$attribute['name'].'_is_required']) ? $_POST[$attribute['name'].'_is_required'] :  -1;
        $visibility_setting = isset($_POST[$attribute['name'].'_visibility_setting']) ? $_POST[$attribute['name'].'_visibility_setting'] : array();
        $show_matchp_users = in_array('show_matchp_users',$visibility_setting) ? 'show' : 'hide';
        $show_value_users = in_array('show_value_users',$visibility_setting) ? 'show' : 'hide';
        $show_matchp_leaders = in_array('show_matchp_leaders',$visibility_setting) ? 'show' : 'hide';
        $show_value_leaders = in_array('show_value_leaders',$visibility_setting) ? 'show' : 'hide';
        $matching_adjustment = array();
        if($attribute['type'] == 'rating' &&  $customParameters[$attribute['name']] =='11'){
            $matching_min_adjustment =  $_POST[$attribute['name'].'_matching_min_adjustment'] ?? 0;
            $matching_max_adjustment =  $_POST[$attribute['name'].'_matching_max_adjustment'] ?? 0;
            if (filter_var($matching_min_adjustment, FILTER_VALIDATE_INT) === false || filter_var($matching_max_adjustment, FILTER_VALIDATE_INT) === false){
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("The range match values must be a valid integer"), gettext('Error'));
            }
            if ($matching_min_adjustment>$matching_max_adjustment) {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("The range match minimum value must be less than or equal to the maximum value"), gettext('Error'));
            }
            $matching_adjustment = array($matching_min_adjustment, $matching_max_adjustment);
        }
        $mandatoryCustomParameters[$attribute['name']] = array('is_required'=>$is_required,'show_matchp_users'=>$show_matchp_users,'show_value_users'=>$show_value_users,'show_matchp_leaders'=>$show_matchp_leaders,'show_value_leaders'=>$show_value_leaders, 'matching_adjustment'=>$matching_adjustment);

        //}
    }
    $allow_chapter_selection = 0;
    $chapter_selection_label = '';
    $chapter_selection_label = sprintf(gettext("Select %s of this %s"),$_COMPANY->getAppCustomization()['chapter']['name-short'], $_COMPANY->getAppCustomization()['group']['name-short']);
    if (isset($_POST['allow_chapter_selection'])) {
        $allow_chapter_selection = 1;
        $chapter_selection_label = isset($_POST['chapter_selection_label']) && trim($_POST['chapter_selection_label']) ? trim($_POST['chapter_selection_label']) : $chapter_selection_label;
        // $group->updateTeamRoleRequestChapterSelectionSetting($allow_chapter_selection, $chapter_selection_label);
    }

    $group->updateTeamMatchingAlgorithmParameters($primaryParameters,$customParameters,$mandatoryPrimaryParameters,$mandatoryCustomParameters, $allow_chapter_selection, $chapter_selection_label);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Matching algorithm updated successfully"), gettext('Success'));

}


elseif (isset($_GET['deleteTeamJoinRequestSurveyData']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if($group->deleteTeamJoinRequestSurveyData()){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Data deleted successfully"), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again"), gettext('Error'));
    }
}

elseif (isset($_GET['updateSurveyDownloadSetting'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL ||
        ($status = (int) $_POST['status'])<0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    // Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }


    if($group->updateSurveyDownloadSetting($status)){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Updated successfully"), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again"), gettext('Error'));
    }
}

elseif (isset($_GET['submitMatchingCustomAttributes']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    // Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $removeForm = false;
    if (empty($_POST['quesionJSON'])&& $_POST['removeCustomAttributes'] == 'remove') {
        $questionJSON = '';
        $removeForm = true;
    } else {
        $questionJSON = json_decode($_POST['quesionJSON'], true);
        // Remove navigateToUrl attribute if set. navigateToUrl feature conflicts with survey response storage
        if (array_key_exists('navigateToUrl', $questionJSON)) {
            unset($questionJSON['navigateToUrl']);
        }
        $questionJSON = json_encode($questionJSON);
    }

    if($group->updateTeamMatchingAlgorithmAttributes($questionJSON)){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Custom attributes updated successfully"), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again"), gettext('Error'));
    }

}

elseif (isset($_GET['updateProgramTypeSetting'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

	if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($program_type_value = intval($_POST['team_program_type'])) < 1 ||
        (!in_array($program_type_value,array_values(Team::TEAM_PROGRAM_TYPE)))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $return = 0;
    if($group->updateTeamProgramType($program_type_value)){
        if ($program_type_value == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ // Hide Touch point, feedback  and Messages by default
            // Re initialize Group
            $group = Group::GetGroup($groupid, true);
            $group->updateHiddenProgramTabSetting(array(TEAM::PROGRAM_TEAM_TAB['TOUCH_POINT'],TEAM::PROGRAM_TEAM_TAB['FEEDBACK'], TEAM::PROGRAM_TEAM_TAB['TEAM_MESSAGE']));
        }

        if ($program_type_value != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV'] &&
            $program_type_value != Team::TEAM_PROGRAM_TYPE['NETWORKING']) {
            // Re initialize Group
            $group = Group::GetGroup($groupid, true);
            $group->saveTeamWorkflowSetting('automatically_close_team_after_n_days', 365);
        }

        $return = 1;
    }
    echo $return;
    exit();
}
elseif (isset($_GET['search_network_member_match'])){
    $searchResponses['results'] = array();
    if (isset($_GET['keyword']) && strlen(trim($_GET['keyword']))>=2) {

        $keyword = trim($_GET['keyword']);
        $searchResults = User::SearchUsersByKeyword($keyword);
        if (!empty($searchResults)) {
            $formatedData = array();
            foreach( $searchResults as $result) {
                if ($result['userid'] == $_USER->id()) { continue; }
                $formatedData[] = array (
                    "id"=>$_COMPANY->encodeId($result['userid']),
                    "text" =>$result['firstname'].' '.$result['lastname']
                );
            }
            $searchResponses['results'] = $formatedData;
        }
    }
    echo json_encode($searchResponses);
}

elseif (isset($_GET['canJoinMultipleTeamRole'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

	if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($canJoinMultipleRole = intval($_POST['canJoinMultipleRole'])) > 1 ||
        (!in_array($canJoinMultipleRole,array(0,1)))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $return = 0;
    if($group->updateTeamJoinRequestSetting($canJoinMultipleRole)){
        $return = 1;
    }
    echo $return;
    exit();
}

elseif (isset($_GET['saveNetworkingProgramStartSetting']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $program_start_date = $_POST['program_start_date']??'';
    $team_match_cycle_days = $_POST['team_match_cycle_days'];
    if (!is_int($team_match_cycle_days) && $team_match_cycle_days <= 0) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Days value must be greater than zero and a valid numeric value."), gettext('Error'));
    }

    $group->saveNetworkingProgramStartSetting($program_start_date,$team_match_cycle_days);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Setting updated successfully"), gettext('Success'));

}

elseif (isset($_GET['initDiscoverCircles']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $search_filters = $_GET['search_filters'] ?? '';
    $primary_parameters  = array_flip(array_values(UserCatalog::GetAllCatalogCategories()));
    $customParameters = array();
    $searchAttributes =  $group->getProgramDiscoverSearchAttributes();
    $userPlaceHolder = sprintf(gettext('Filter by %s leader'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    $searchSubject = Team::GetTeamCustomMetaName($group->getTeamProgramType()) . ' Leader';

    $_SESSION['showAvailableCapacityOnly'] = $searchAttributes['default_for_show_only_with_available_capacity'];
    
    include(__DIR__ . "/views/talentpeak/teams/discover_circles_container.template.php");
    exit();
}

elseif (isset($_GET['discoverCircles']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $page = (int) ($_POST['page'] ?? 1);
    $per_page = 10;

    // Logic commented out as per #3289 issue Point no 3 as we will do a silent join request
    // if ($page === 1) {
    //     $joinRequests = Team::GetMyJoinRequests($groupid);
    //     if (empty($joinRequests)) {
    //         AjaxResponse::SuccessAndExit_STRING(0, '', gettext('To Discover your matches, please Register for a role'), gettext('Error'));
    //     }
    // }

   
    $filter_attribute_keyword = $_POST['attribute_keyword'] ?? array();
    $filter_primary_attribute = $_POST['primary_attribute'] ?? array();
    $filter_attribute_type    = explode(',', $_POST['filter_attribute_type']??'');
    $name_keyword = trim($_POST['name_keyword'] ?? '');

    $_SESSION['showAvailableCapacityOnly'] = isset($_POST['showAvailableCapacityOnly']) ? (int)$_POST['showAvailableCapacityOnly'] : $group->getProgramDiscoverSearchAttributes()['default_for_show_only_with_available_capacity'];
    $showAvailableCapacityOnly = $_SESSION['showAvailableCapacityOnly'];

    $allRoles = Team::GetProgramTeamRoles($groupid, 1);

    $search_filters = json_decode($_POST['search_filters'] ?? '', true);
    $availableTeamIds = NULL;

    $search_str = '';
    $hashtag_ids = [];
    if ($search_filters) {
        $search_str = trim($search_filters['q'] ?? '');
        $hashtag_ids = array_map(function (string $hashtag_id) {
            global $_COMPANY;

            if (is_numeric($hashtag_id)) {
                return (int) $hashtag_id;
            }

            return $_COMPANY->decodeId($hashtag_id);
        }, $search_filters['hashtag_ids'] ?? []);
    }

    [$availabeTeams,$show_more,$total_count] = Team::DiscoverAvailableCircles($groupid,$_USER->id(), $page, $per_page, $filter_attribute_keyword, $filter_primary_attribute, $name_keyword, $showAvailableCapacityOnly, $search_str, $hashtag_ids, $filter_attribute_type);

    $availabeTeams = array_values($availabeTeams); // Remove nulls that can be introduced as a result of typesense.

    $title = sprintf(gettext('Found %s results'),$total_count);
    $teamWorkflowSetting = $group->getTeamWorkflowSetting();
    
    if ($page == 1){
        if (empty($availabeTeams) && ($filter_attribute_keyword || $filter_primary_attribute || $name_keyword)) {
            $emptyMessage = gettext('Your search criteria did not return any matching result. Please change your search criteria and try again. ');
        }
        // ob_start();
        // include(__DIR__ . "/views/talentpeak/teams/discover_circles_rows.template.php");
        // $listing_html = ob_get_clean();

        if ((int) ($_GET['include_tabs_html'] ?? 0)) {
            $chapterid = 0;
            $hashTagHandles = HashtagHandle::GetAllHashTagHandles();
            include(__DIR__ . '/views/talentpeak/teams/get_my_teams.template.php');
        } else {
            // echo $listing_html;
            include(__DIR__ . "/views/talentpeak/teams/discover_circles_rows.template.php");
        }
    } else {
        $title = '';
        include(__DIR__ . "/views/talentpeak/teams/discover_circles_rows.template.php");
    }

}
elseif (isset($_GET['proceedToJoinCircle']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) === null ||
        ($roleid = $_COMPANY->decodeId($_POST['roleid']))<1 ||
        ($teamRole = Team::GetTeamRoleType($roleid)) === NULL || 
        ($group = Group::GetGroup($team->val('groupid'))) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($team->isTeamMember($_USER->id())){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('You are already a member'), gettext('Error'));
    }

    if (!$team->isAllowedNewTeamMemberOnRole($roleid)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Maximum allowed members limit reached for selected role!'), gettext('Error'));
    }

    if (!Team::CanJoinARoleInTeam($team->val('groupid'), $_USER->id(), $roleid)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Maximum requested capacity for this role has been reached.'), gettext('Error'));
    }

    if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES'] ) {
        // Init silent registration process of mentee sys role type
        $join_request_status = Team::CreateAutomaticJoinRequest($group, $_USER->id(), $roleid);
        if (!$join_request_status['status']) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Unable to update Registration'), gettext('Error'));
        }
    }

    if($team->addUpdateTeamMember($roleid, $_USER->id())){
        $refreshDetail = $_POST['refreshDetail'] == 1 ? 1 :0;
        $mentor = $team->getTeamMembersBasedOnSysRoleid(2);
        if (!empty($mentor) && $teamRole['sys_team_role_type'] != 2){ // Send Email Notification to Mentor
            $role = Team::GetTeamRoleType($mentor[0]['roleid']);
            $app_type = $_ZONE->val('app_type');
            $reply_addr = $group->val('replyto_email');
            $from = $group->val('from_email_label') . sprintf(gettext('%s  Joined'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
            $mentorEmail = $mentor[0]['email'];
            $mentorName = $mentor[0]['firstname'] . ' ' . $mentor[0]['lastname'];
            $menteeName = $_USER->getFullName();
            $teamMetaName = $_COMPANY->getAppCustomization()['teams']['name'];
            $groupMetaName = $_COMPANY->getAppCustomization()['group']['name'];
            $temp = EmailHelper::JoinCircleNotificationToMentorTemplate($groupMetaName, $teamMetaName, $team->val('team_name'), $role['type'], $mentorName, $menteeName, $group->val('groupname'), date("Y-m-d"));
            $_COMPANY->emailSend2($from, $mentorEmail, $temp['subject'], $temp['message'], $app_type,$reply_addr);
        }

        if ($refreshDetail) {
            $teamObj = Team::GetTeam($teamid);
            $groupid = $teamObj->val('groupid');
            $group = Group::GetGroup($groupid);
            $manage_section = 0;
            $showBasicInfoOnly = 1;
            $teamMembers = $teamObj->getTeamMembers(0);
            $hashTagHandles = array();
            if($teamObj->val('handleids')){
                $hashTagHandles = HashtagHandle::GetAllHashTagHandles($teamObj->val('handleids'));
            }
            $allRoles = Team::GetProgramTeamRoles($groupid, 1);
            $circleRolesCapacity = Team::GetCircleRolesCapacity($groupid,$teamObj->val('teamid'));
            $teamWorkflowSetting = $group->getTeamWorkflowSetting();
            include(__DIR__ . "/views/talentpeak/teams/get_team_basic_detail.template.php");
            exit();
        } else {
            AjaxResponse::SuccessAndExit_STRING(1, $_COMPANY->encodeId($team->val('groupid')), gettext("You have successfully joined this circle. You can find it now in the 'My Circle' tab."), gettext('Success'));
   
        }
    }

    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));

}

elseif (isset($_GET['leaveCircleMembership']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) === null ||
        ($group = Group::GetGroup($team->val('groupid'))) === null ||
        ($memberid = $_COMPANY->decodeId($_POST['memberid'])) < 1 ||
        ($member = $team->getTeamMemberById($memberid )) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($team->deleteTeamMember($memberid)){
        $mentor = $team->getTeamMembersBasedOnSysRoleid(2);
        if (!empty($mentor) && $member['sys_team_role_type'] != 2){ // Send Email Notification to Mentor
            $team->sendLeaveCircleNotificationToMentor($group,$mentor[0]);  
        }

        $refreshDetail = $_POST['refreshDetail'] == 1 ? 1 :0;
        if ($refreshDetail) {
            $teamObj = Team::GetTeam($teamid);
            $groupid = $teamObj->val('groupid');
            $manage_section = 0;
            $showBasicInfoOnly = 1;
            $teamMembers = $teamObj->getTeamMembers(0);
            $hashTagHandles = array();
            if($teamObj->val('handleids')){
                $hashTagHandles = HashtagHandle::GetAllHashTagHandles($teamObj->val('handleids'));
            }
            $allRoles = Team::GetProgramTeamRoles($groupid, 1);
            $circleRolesCapacity = Team::GetCircleRolesCapacity($groupid,$teamObj->val('teamid'));
            $teamWorkflowSetting = $group->getTeamWorkflowSetting();
            include(__DIR__ . "/views/talentpeak/teams/get_team_basic_detail.template.php");
            exit();
        } else{
            if ($team->isCircleCreator()){
                AjaxResponse::SuccessAndExit_STRING(2, '', sprintf(gettext("Member removed from this %s"), Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Success'));
            } else {
                AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext("%s left successfully"), Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Success'));
            }
        }

    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
}

elseif (isset($_GET['getTeamAttributeValuesByKey']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($attributeKey = $_GET['attributeKey']) == '' ||
        (!in_array($_GET['keyType']??'', array('primary','custom'))) 
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    $attributeValues = array();
    $keyType = $_GET['keyType'];
    $attributes_index = (int)$_GET['attributes_index'];

    // Validation to check if $attributeKey is a valid string - SonarCloud Inspection recommends checking value
    if ($keyType == 'primary'){
        $primary_parameters  = UserCatalog::GetAllCatalogCategories();
        if (!in_array($attributeKey,$primary_parameters)) {
            echo "<option value=''>".gettext('Not options available')."</option>";
            exit();
        }
        $attributeValues =  UserCatalog::GetAllCategoryKeys($attributeKey);
    ?>
    <select name="attribute_keyword[]" id="attribute_keyword_<?= $attributes_index; ?>" aria-label="<?= sprintf(gettext('Select Filter %s Attribute First -'), $attributes_index); ?>" class="form-control">
        <?php if (!empty($attributeValues)){  
            sort($attributeValues);
        ?>
            <option value=""><?= sprintf(gettext('Select %s'),$attributeKey); ?></option>
            <?php foreach($attributeValues as $value){ ?>
                <option value="<?= $value; ?>"><?= $value; ?></option>
            <?php } ?>
    <?php
        } else {
            echo "<option value=''>".gettext('Not options available')."</option>";
    
        } ?>
    </select>
    <?php    
    } elseif($keyType == 'custom') {
        $q = $group->getTeamCustomAttributesQuestion($attributeKey);
        if(array_key_exists('choices',$q) || $q['type'] == 'rating') {
            if($q['type'] == 'rating') {

                if (array_key_exists('rateValues',$q)){
                    $attributeValues = array();
                    foreach($q['rateValues'] as $key => $rv) {
                        if (is_array($rv)) {
                            $attributeValues[] = $rv;
                        } else {
                            $attributeValues[] = array('value'=>$rv, 'text'=>$rv);
                        }
                    }
                } else {
                    $rateCount = 5;
                    if (array_key_exists('rateCount',$q)){
                        $rateCount = $q['rateCount'];
                    }
                    $attributeValues = array();
                    for($i=1;$i<=$rateCount;$i++){
                        $attributeValues[] = array('value'=>$i, 'text'=>$i);
                    }
                }

            
            } else {
                $choices = $q['choices'];
                foreach($choices as $key => $value) {
                    if (is_array($value)) {
                        $attributeValues[] =  $value;
                    } else {
                        $attributeValues[] = array('value'=>$value, 'text'=>$value);
                    }
                }
            }
    ?>
        <select name="attribute_keyword[]" id="attribute_keyword_<?= $attributes_index; ?>" aria-label="<?= sprintf(gettext('Select Filter %s Attribute First -'), $attributes_index); ?>" class="form-control">
            <?php if (!empty($attributeValues)){ ?>
                <option value=""><?= sprintf(gettext('Select an Option')); ?></option>
                <?php foreach($attributeValues as $key => $value){ ?>
                    <option value="<?= $value['value']; ?>"><?= $value['text']; ?></option>
                <?php } ?>
            
            <?php } else { ?>
                <option value=''><?= gettext('Not options available'); ?></option>
            <?php } ?>
            </select>
    <?php } else { ?>
        <input type="text" class="form-control" name="attribute_keyword[]" placeholder="<?= gettext('Input words to search')?>" id="attribute_keyword_<?= $attributes_index; ?>" value=''>
    <?php }
    }
    exit();
}

elseif (isset($_GET['getTeamsMessages']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
     /**
     * Dependencies for Comment Widget
     * $comments
     * $commentid (default 0)
     * $groupid
     * $topicid
     * $disableAddEditComment
     * $submitCommentMethod
     * $mediaUploadAllowed
    */
    $topicid = $teamid;
    $comments = Team::GetComments_2($topicid);
    $commentid = 0;
    $disableAddEditComment = false;
    if ($team->isComplete() || $team->isIncomplete() || $team->isInactive() || $team->isPaused()) {
        $disableAddEditComment = true;
    }
    $submitCommentMethod = "TeamMessages";
    $mediaUploadAllowed = $_COMPANY->getAppCustomization()['teams']['enable_media_upload_on_comment'];
    $refreshPage = 0;
    $sectionHeading = sprintf(gettext("%s Messages"), Team::GetTeamCustomMetaName($group->getTeamProgramType()));

    include(__DIR__ . "/views/talentpeak/team_message/get_team_messages.template.php");
    exit();
}

elseif (isset($_GET['checkPendingActionItemAndTouchPoints']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $teamStatus = $_COMPANY->decodeId($_GET['status']);
    if( $teamStatus < 1 ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    
    [$pendingActionItemsCount,$pendingTouchPointsCount] = $team->GetPendingActionItemAndTouchPoints();

    if ($pendingActionItemsCount>0 || $pendingTouchPointsCount > 0) {

        $actionItem = $pendingActionItemsCount>0 ? gettext('Action Items') : '';
        $touchPoints = $pendingTouchPointsCount > 0 ? gettext('Touch Points') : '';
        $divider =  ($pendingActionItemsCount>0  && $pendingTouchPointsCount > 0) ? ' ' .gettext('and'). ' ' : '';
        $finalString = $actionItem.$divider.$touchPoints;
        $message = sprintf(gettext('You have not completed all required %1$s, so the %2$s cannot be marked as complete'), $finalString,Team::GetTeamCustomMetaName($group->getTeamProgramType()));
        $status = 1;
        if($teamStatus == Team::STATUS_INCOMPLETE) {
            $message = sprintf(gettext('You have not completed all required %1$s, are you sure you want to close this %2$s as incomplete?'), $finalString,Team::GetTeamCustomMetaName($group->getTeamProgramType()));
            $status = 2;
        }
        AjaxResponse::SuccessAndExit_STRING($status, '', $message, gettext('Success'));
        
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("There are no action items or touch points to complete."), gettext('Error'));
    }
}

elseif (isset($_GET['manageSearchConfiguration']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $availableAttributes = array_values(UserCatalog::GetAllCatalogCategories());
    $selectedAttributes = $group->getProgramDiscoverSearchAttributes();
    $customAttributes = $group->getTeamMatchingAlgorithmAttributes();
    $surveyQuestions = array();
    if (!empty($customAttributes)){
        $customAttributes = $customAttributes['pages'];
        foreach ($customAttributes as $key => $elements) {
            if (array_key_exists('elements',$elements)) {
                $surveyQuestions = array_merge($surveyQuestions,$elements['elements']);
            }
        }
    } 
    include(__DIR__ . "/views/talentpeak/team_configuration/manage_discover_search_configuration_templates.php");
}


elseif (isset($_GET['saveDiscoverSearchAttributes']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $primary_attributes = $_POST['primary_attributes'] ?? array();
    $custom_attributes = $_POST['custom_attributes'] ?? array();
    $default_for_show_only_with_available_capacity = $_POST['default_for_show_only_with_available_capacity'] ?? 0;
    $update_result = $group->saveDiscoverSearchAttributes($primary_attributes, $custom_attributes, $default_for_show_only_with_available_capacity);
    if ($update_result) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Search attributes updated successfully"), gettext('Success'));
    }
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. please reload the page.'), gettext('Error'));
}


elseif (isset($_GET['getInviteUserManuallyForTeamModel']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL ||
        ($userRoleid = $_COMPANY->decodeId($_GET['userRoleid']))<1 ||
        ($subjectRoleid = $_COMPANY->decodeId($_GET['subjectRoleid']))<1 
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    if (!Team::CanJoinARoleInTeam($groupid,$_USER->id(),$userRoleid)){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('You\'ve reached the maximum number of %1$s you can participate in as a %2$s. Therefore, you can\'t send requests or invite users to form new %1$s where you take on the %2$s role.'),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1),$joinRequest['type']), gettext('Success'));
    }

    $form_title = sprintf(gettext("Search user to join the %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    
    include(__DIR__ . "/views/talentpeak/common/search_user_to_invite_for_team.template.php");
}

elseif (isset($_GET['searchUsertoInviteForTeam']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($roleid = $_COMPANY->decodeId($_GET['roleid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $keyword = raw2clean($_GET['keyword']);
    $roleid = $_GET['roleid'] ? $_COMPANY->decodeId($_GET['roleid']) : 0;
    
    $excludeCondition = "";
    $searchAllUsersConditon = "";
    $activeusers = User::SearchUsersByKeyword($keyword,$searchAllUsersConditon ,$excludeCondition); // Note $excludeCondition is added as " AND ({$excludeCondition}) "
    $dropdown = '';
    if (count($activeusers) > 0) {
        $dropdown .= "<select class='form-control userdata' name='receiver_id' onchange='closeDropdown()' id='receiver_id' required >";
        $dropdown .= "<option value=''>".gettext("Select a user (maximum of 20 matches are shown below)")." </option>";

        foreach ($activeusers as $activeuser) {
            if ($activeuser['userid'] == $_USER->id()) {
                continue;
            }
            $dropdown .= "<option value='" . $_COMPANY->encodeId($activeuser['userid']) . "'>" . rtrim(($activeuser['firstname'] . " " . $activeuser['lastname']), " ") . " (" . $activeuser['email'] . ") - " . $activeuser['jobtitle'] . "</option>";
        }
        $dropdown .= '</select>';
    } else {
        $dropdown .= "<select class='form-control userdata' name='receiver_id' id='receiver_id' required>";
        $dropdown .= "<option value=''>".gettext("No match found")."</option>";
        $dropdown .= "</select>";
    }
    echo $dropdown;
    exit();
}


elseif (isset($_GET['inviteUserForTeamByRole']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($receiver_id = $_COMPANY->decodeId($_POST['receiver_id'])) < 1 ||
        ($receiver_roleid = $_COMPANY->decodeId($_POST['receiver_roleid'])) < 1 ||
        ($sender_roleid = $_COMPANY->decodeId($_POST['sender_roleid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null || 
        ($receiverRole = Team::GetTeamRoleType($receiver_roleid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!empty($receiverRole['registration_start_date']) && $receiverRole['registration_start_date'] > date('Y-m-d')){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Registration is currently unavailable. Please check back after %s'),$receiverRole['registration_start_date'] ), '');
    }
    if (!empty($receiverRole['registration_end_date']) && $receiverRole['registration_end_date'] < date('Y-m-d')){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Registration is now closed for this role.'), '');
    }

    $common_teams = Team::GetCommonTeamsBetweenUsers($groupid, $_USER->id(), $receiver_id, $sender_roleid, $receiver_roleid, true);
    if (count($common_teams)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Unable to invite! User is already a member of one of your active %1$s.'), Team::GetTeamCustomMetaName($group->getTeamProgramType())), '');
    }

    if ($group->getTeamJoinRequestSetting()!=1){ // If setting is not allowing multiple role requests
        $requestedRoleIds = array_column(Team::GetUserJoinRequests($groupid,$receiver_id,0),'roleid');
        if (count($requestedRoleIds) && !in_array($receiverRole['roleid'],$requestedRoleIds)){ //if user have already requests and invited role is not in existing requests
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Users can only have one %1$s role in this %2$s. This role is unavailable'), Team::GetTeamCustomMetaName($group->getTeamProgramType()), $_COMPANY->getAppCustomization()['group']['name-short']), '');
        }
    }

    if (!Team::CanJoinARoleInTeam($groupid, $receiver_id, $receiver_roleid)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("User has reached the limit for %s with this role."),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1)), '');
    }

    list(
        $roleSetCapacity,
        $roleUsedCapacity,
        $roleRequestBuffer,
        $roleAvailableCapacity,
        $roleAvailableRequestCapacity,
        $roleAvailableBufferedRequestCapacity,
        $pendingSentOrReceivedRequestCount
    ) = Team::GetRoleCapacityValues($groupid, $receiver_roleid, $receiver_id);

    $roleRequest = Team::GetRequestDetail($groupid, $receiver_roleid, $receiver_id);

    if ($roleRequest && $roleSetCapacity !=0 && $roleAvailableRequestCapacity < 1){ // Check available request capacity only if user already requested for this role and $roleSetCapacity = 0 means unlimited
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('User cannot join more programs with this role.'), gettext('Error'));
    }

    $sendRequest = Team::SendRequestToJoinTeam($groupid, $receiver_id, $receiver_roleid, $sender_roleid, '', '');

    if ($sendRequest) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Request sent!'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. please reload the page.'), gettext('Error'));
    }

    exit();
}

elseif (isset($_GET['getTeamInvites']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $invitedLists = Team::GetTeamInvites($groupid, $_USER->id());

    include(__DIR__ . "/views/talentpeak/teams/discover_team_invites_list.template.php");
    exit();
}
elseif (isset($_GET['resendTeamInvite']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($team_request_id = $_COMPANY->decodeId($_POST['team_request_id'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $requestDetail = Team::GetTeamRequestDetail($groupid,$team_request_id);

    if ($requestDetail) {

        $sendRequest = Team::SendRequestToJoinTeam($groupid, $requestDetail['receiverid'], $requestDetail['receiver_role_id'], $requestDetail['sender_role_id'], '', '');

        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Request sent successfully'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. please reload the page.'), gettext('Error'));
    }
}

elseif (isset($_GET['deleteTeamRequest']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($team_request_id = $_COMPANY->decodeId($_POST['team_request_id'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $deleteRequest = Team::DeleteTeamRequest($groupid,$team_request_id);

    if ($deleteRequest) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Request deleted successfully'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. please reload the page.'), gettext('Error'));
    }
}

elseif (isset($_GET['cancelTeamRequest']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($team_request_id = $_COMPANY->decodeId($_POST['team_request_id'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $getRequestDetail = Team::GetTeamRequestDetail($groupid,$team_request_id);
    $deleteRequest = Team::CancelTeamRequest($groupid,$team_request_id);
    if ($deleteRequest) {

         // Send cancellation email
            $reMessage = gettext("Request canceled successfully");
            $teamCustomName = Team::GetTeamCustomMetaName($group->getTeamProgramType(), 1);

            $groupName = $group->val('groupname');
            $from = $group->val('from_email_label');
            $reply_addr = $group->val('replyto_email');
            $senderUser = User::GetUser($getRequestDetail['senderid']);
            $receiverUser = User::GetUser($getRequestDetail['receiverid']);
            $senderRole = Team::GetTeamRoleType($getRequestDetail['sender_role_id']);
            $receiverRole = Team::GetTeamRoleType($getRequestDetail['receiver_role_id']);
            $app_type = $_ZONE->val('app_type');
            $baseurl = $_COMPANY->getAppURL($app_type);
            $groupCustomName = $_COMPANY->getAppCustomization()['group']['name-short'];
            $requestUrl = $baseurl . 'detail?id=' . $_COMPANY->encodeId($groupid) . '&hash=getMyTeams/initDiscoverTeamMembers';

            if ($receiverUser) { // If receiver user no longer exists, then no need to send cancelation email.
                $temp = EmailHelper::CancelTeamRoleRequestEmailToRecipientTemplate($senderUser->getFullName(), $receiverUser->getFullName(), $senderRole['type'], $receiverRole['type'], $requestUrl, $groupCustomName, $groupName, $teamCustomName);
                $_COMPANY->emailSend2($from, $receiverUser->val('email'), $temp['subject'], $temp['message'], $app_type, $reply_addr);
            }
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Request canceled successfully'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. please reload the page.'), gettext('Error'));
    }
}

elseif (isset($_GET['getUnmatchedUsersJoinRequests'])){

    if (($groupid = $_COMPANY->decodeId($_GET['getUnmatchedUsersJoinRequests']))<0 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $roleid = !empty($_GET['registrationType'])?$_COMPANY->decodeId($_GET['registrationType']):0;

    $teamsFilters = array();
    $teamFiltersValue = '';
    if (isset($_GET['teamsFilterValue'])) {
        $teamsFilters = array_intersect([$_GET['teamsFilterValue']], ['unassigned', 'assigned', 'complete', 'incomplete']);
        $teamFiltersValue = $teamsFilters[0] ?? '';
    }

    $myRequests = Team::GetUserJoinRequests($groupid,$_USER->id(),1);
    $filteredData = array_filter($myRequests, function($item) {
        return $item['sys_team_role_type'] == 2;
    });
    // Re-index the array to ensure keys are continuous
    $mentorRequestDetail = array_values($filteredData);
    $canMentorJoinTeam = (!empty($mentorRequestDetail) && Team::DoesJoinRequestHaveActiveCapacity($mentorRequestDetail[0])); 

    $questionJson = $group->getTeamMatchingAlgorithmAttributes();
    $isQuestionAvailable = count($questionJson);

    $input = array('search'=>raw2clean($_POST['search']['value']),'start'=>(int)$_POST['start'],'length'=>(int)$_POST['length'],'draw'=>(int)$_POST['draw']);


    if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { 
        $orderFields = ['users.firstname','users.firstname','users.firstname','member_join_requests.used_capacity','users.firstname'];
    } else{
        $orderFields = ['users.firstname','users.firstname','member_join_requests.used_capacity','users.firstname'];
    }
    $orderBy='';
    if (isset($_POST['order'])){
        $orderIndex = (int) $_POST['order'][0]['column'];
        $orderBy = $orderFields[$orderIndex];

        $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
        $orderBy = $orderBy.' '.$orderDir;
    }
    $search = "";
    if ($input['search']){
        $search = $input['search'];
    }
	
    $totalrows = Team::GetTeamJoinRequests($groupid,$roleid, false,$search, $orderBy,$input['start'],$input['length'],true, $teamsFilters);
    $rows  = Team::GetTeamJoinRequests($groupid,$roleid, false,$search, $orderBy,$input['start'],$input['length'],false, $teamsFilters);
    $final = [];
    $activeStatus = array(0=>gettext('Deactivate'),1=>gettext('Active'), 2=>gettext('Paused'));
    foreach($rows as $row){
        if ($row['external_email']) {
            $row['email'] = User::PickEmailForDisplay($row['email'], $row['external_email'], true);
        }
        $dataRow = array();
        $dataRow[] = '<span class="col-md-2 m-0 p-0">'.
        User::BuildProfilePictureImgTag($row['firstname'],$row['lastname'],$row['picture'],'memberpic2', 'User Profile Picture', $row['userid'], 'profile_full').
        '</span>'.
        '<span class="col-md-10 m-0 p-0 " style="word-break:break-word">'.$row['firstname'].' '.$row['lastname'].
        '<p>'.$row['jobtitle'].'</p>'.
        '<p style="word-break:break-word">'.$row['email'].'</p>'.
        '</span>';

        $usedCapacity = '-';
        if (!empty ($row['roleType'])) {
            $usedCapacity = $row['roleType'];
            $usedCapacity .= ($row['isactive'] == 2 ? '[Paused]' : '');
            $usedCapacity .= '<br>('.$row['used_capacity']. '/'.($row['request_capacity'] == 0 ? gettext('Unlimited') :  $row['request_capacity']).')';
        }

        if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER']){
            $totalPendingSentRequests = Team::GetPendingSentRequestCount($groupid,$row['userid'],$row['roleid']);
            $totalPendingReceivedRequests = Team::GetPendingReceivedRequestCount($groupid,$row['userid'],$row['roleid']);
            $usedCapacity .= '<br>('.$totalPendingSentRequests. '/'.$totalPendingReceivedRequests.')';

        }
        $dataRow[] = $usedCapacity;
        if ($_COMPANY->getAppCustomization()['chapter']['enabled']) {
            $chaptersName  = '-';
            if ($group->getTeamRoleRequestChapterSelectionSetting()['allow_chapter_selection']){ 
                $chapters = Group::GetChaptersCSV($row['chapterids'],$groupid);
                if(!empty($chapters)){
                    $chaptersName  = '';
                    foreach($chapters as $chapter){
                    $status = "";
                    if ($chapter['isactive'] == 0){
                        $status = ' <font color="red">(In-active)</font>';
                    }
                    $chaptersName .= '<li>'.$chapter['chaptername'].$status.'</li>';
                    }
                }
            }
            $dataRow[] = $chaptersName;
        }

        if ($_ZONE->val('app_type') === 'peoplehero') {
            $dataRow[] = $row['employee_start_date'];
        }

        $userTeams = Team::GetUserTeamsList($groupid,$row['userid'],false);

        
        $matchingRoles = array();
        if(!empty($userTeams) && !empty($rows)){
            $userTeamRolesSys = array_column($userTeams, 'sys_team_role_type');
            $rowRolesSys = array_column($rows, 'sys_team_role_type');
            $matchingRoles = array_intersect($userTeamRolesSys,$rowRolesSys);
        }

        $assignedTeams = "";
        if(!empty($userTeams) && !empty($matchingRoles)){
            $assignedTeams .= "<ul>";
            foreach($userTeams as $userTeamItem){

                if ($row['roleid'] != $userTeamItem['roleid'])
                    continue; // Do not show teams where user is assigned a role other than the one that we are printing.

                if($userTeamItem['isactive'] == Team::STATUS_COMPLETE){
                    $teamNameLabel = '<strong>'   .
                                    htmlspecialchars($userTeamItem['team_name'])." (".$userTeamItem['type'].')' .
                                    '<sup style="color: darkgreen;"> ['. gettext("Complete") . ']</sup>' .
                                    '</strong>';
                } elseif ($userTeamItem['isactive'] == Team::STATUS_INCOMPLETE) {
                    $teamNameLabel = '<strong>'   .
                                    htmlspecialchars($userTeamItem['team_name']).' ('.$userTeamItem['type'].')' .
                                    '<sup style="color: green;"> ['. gettext('Incomplete') . ']</sup>' .
                                    '</strong>';
                } elseif ($userTeamItem['isactive'] == Team::STATUS_PAUSED) {
                    $teamNameLabel = '<strong>'   .
                                    htmlspecialchars($userTeamItem['team_name']).' ('.$userTeamItem['type'].')' .
                                    '<sup style="color: red;"> ['. gettext('Paused') . ']</sup>' .
                                    '</strong>';
                } elseif ($userTeamItem['isactive'] == Team::STATUS_DRAFT){
                    $teamNameLabel = htmlspecialchars($userTeamItem['team_name']).' ('.$userTeamItem['type'].')' .
                                    '<sup style="color: darkorange;"> ['. gettext('Draft') . ']</sup>';
                } elseif ($userTeamItem['isactive'] == Team::STATUS_INACTIVE){
                    $teamNameLabel = htmlspecialchars($userTeamItem['team_name']).' ('.$userTeamItem['type'].')' .
                                    '<sup style="color: red;"> ['. gettext('Inactive') . ']</sup>';
                } else {
                    $teamNameLabel = htmlspecialchars($userTeamItem['team_name']).' ('.$userTeamItem['type'].')' .
                                    '<sup style=""> ['. gettext('Active') . ']</sup>';
                }
                $assignedTeams .='<li>'.$teamNameLabel.'</li>';
            }
            $assignedTeams .= "</ul>";
        } else {
            $assignedTeams =  gettext('Not Assigned');
        }

        $dataRow[] = $assignedTeams;

        $actionButton  = '<div class="" style="color: #fff; float: left;" >';
        $actionButton  .= '<button aria-label="User '.$row['firstname'].' '.$row['lastname'].'" class="btn btn-sm btn-affinity dropdown-toggle" title="Action" type="button" data-toggle="dropdown">';
        $actionButton  .= $activeStatus[$row['isactive']].'&emsp;&#9662;</button>';
        $actionButton  .= '<ul class="dropdown-menu dropdown-menu-right dropdown-action-menu-list" style="width: 250px; cursor: pointer;">';
        $canJoinARole = Team::DoesJoinRequestHaveActiveCapacity($row);
        $opts = 0;
        
        if ($canJoinARole && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES'] && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['NETWORKING']) { $opts++;
            
            if ($row['sys_team_role_type'] == 3 && $_ZONE->val('app_type') == 'peoplehero' && $row['userid'] != $_USER->id() && $canMentorJoinTeam){
                $actionButton  .= '<li><a role="button" class="confirm" href="javascript:void(0)" title="'.sprintf(gettext('Are you sure you want to create a %1$s with %2$s and activate it?'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),$row['firstname']. ' ' .$row['lastname'] ).'" onclick="selfAssignTeamToUserForm(\''.$_COMPANY->encodeId($groupid).'\',\''.$_COMPANY->encodeId($row['userid']).'\',\''.$_COMPANY->encodeId($row['roleid']).'\')" ><i class="fa fa-user-plus" aria-hidden="true"></i>&emsp;'.gettext("Assign to Self & Activate").'</a></li>';
            }
            $actionButton  .= '<li><a role="button" href="javascript:void(0)" title="'.sprintf(gettext("Create %s and Assign"),Team::GetTeamCustomMetaName($group->getTeamProgramType())).'" onclick="assignTeamToUserForm(\''.$_COMPANY->encodeId($groupid).'\',\''.$_COMPANY->encodeId($row['userid']).'\',\''.$_COMPANY->encodeId($row['roleid']).'\',\''.$_COMPANY->encodeId(1).'\')" ><i class="fa fa-plus" aria-hidden="true"></i>&emsp;'.sprintf(gettext("Create %s and Assign"),Team::GetTeamCustomMetaName($group->getTeamProgramType())).'</a></li>';
            $actionButton  .= '<li><a role="button" href="javascript:void(0)" title="'.sprintf(gettext("Assign Existing %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType())).'" onclick="assignTeamToUserForm(\''.$_COMPANY->encodeId($groupid).'\',\''.$_COMPANY->encodeId($row['userid']).'\',\''.$_COMPANY->encodeId($row['roleid']).'\',\''.$_COMPANY->encodeId(2).'\')"><i class="fa fa-users" aria-hidden="true"></i>&emsp;'.sprintf(gettext("Assign Existing %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType())).'</a></li>';

        }
        if ($isQuestionAvailable && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']) {  $opts++;
            if (0){
            $actionButton  .= '<li><a role="button" class="js-download-link" href="ajax_talentpeak.php?downloadUnmachedSingleUserSurveyResponses='.$_COMPANY->encodeId($groupid).'&userid='. $_COMPANY->encodeId($row['userid']).'&roleid='.$_COMPANY->encodeId($row['roleid']).'"><i class="fa fa-download" title="Download" aria-hidden="true"></i>&emsp;'. gettext("Download Survey Responses").'</a></li>';
            }

            $actionButton  .= '<li><a role="button" href="javascript:void(0)" class="" onclick="viewUnmachedUserSurveyResponses(\''.$_COMPANY->encodeId($groupid).'\',\''.$_COMPANY->encodeId($row['userid']).'\',\''.$_COMPANY->encodeId($row['roleid']).'\')"><i class="fa fa-eye" title="View" aria-hidden="true"></i>&emsp;'.gettext("View Survey Responses").'</a></li>';

            if ((int) $row['isactive'] !== 0) {
                $actionButton  .= '<li><a role="button" href="javascript:void(0)" class="" onclick="updateTeamJoinRequestSurveyResponseByManagerModal(\''.$_COMPANY->encodeId($groupid).'\',\''. $_COMPANY->encodeId($row['userid']).'\',\''.$_COMPANY->encodeId($row['roleid']).'\')"><i class="fa fa-edit" title="View" aria-hidden="true"></i>&emsp;'.gettext("Update Survey Responses").'</a></li>';
            }
        }

        if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']) {
            $actionButton .= '<li> <a role="button" href="javascript:void(0)" class="confirm"';
            if ((int)$row['isactive'] === 0) {
                $opts++;
                $actionButton .= 'data-confirm-noBtn="' . gettext('No') . ' " data-confirm-yesBtn="' . gettext('Yes') . '" title="' . gettext("Are you sure you want to activate this registration?") . '" ';

                $actionButton .= 'onclick="activateTeamJoinRequest(\'' . $_COMPANY->encodeId($groupid) . '\',\'' . $_COMPANY->encodeId($row['roleid']) . '\',\'' . $_COMPANY->encodeId($row['userid']) . '\')"';
            } else {
                $opts++;
                $actionButton .= 'data-confirm-noBtn="' . gettext('No') . '" data-confirm-yesBtn="' . gettext('Yes') . '"';
                $actionButton .= 'title="' . gettext('Are you sure you want to deactivate this registration?') . '"';
                $actionButton .= 'onclick="deactivateTeamJoinRequest(\'' . $_COMPANY->encodeId($groupid) . '\',\'' . $_COMPANY->encodeId($row['roleid']) . '\',\'' . $_COMPANY->encodeId($row['userid']) . '\')"';
            }
            $actionButton .= '>';
            if ((int)$row['isactive'] === 0) {
                $actionButton .= '<i class="fa fa-unlock-alt" title="' . gettext('Activate') . '" aria-hidden="true"></i>';
                $actionButton .= '&emsp;' . gettext('Activate Registration');
            } else {
                $actionButton .= '<i class="fa fa-lock" title="' . gettext('Activate') . '" aria-hidden="true"></i>';
                $actionButton .= '&emsp;' . gettext('Deactivate Registration');
            }
            $actionButton .= '</a>';
            $actionButton .= '</li>';
        }

        if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']) {
            if ($canJoinARole) {
                $opts++;
                $actionButton .= '<li><a role="button" href="javascript:void(0)" onclick="onCancelRegistrationClickByProgramLeader(\'' . $_COMPANY->encodeId($groupid) . '\',\'' . $_COMPANY->encodeId($row['roleid']) . '\',\'' . $_COMPANY->encodeId($row['userid']) . '\', \'' . addslashes($group->val('groupname')) . '\', \'' . $row['roleType'] . '\')" ><i class="fa fa-trash" aria-hidden="true"></i>&emsp;' . gettext("Cancel Registration") . '</a></li>';
            }
        }

            if (!$opts){
                $actionButton  .= '<li><a role="button" class="disabled"><i class="fa fas fa-exclamation" aria-hidden="true"></i>&emsp;'.gettext("No options available").'</a></li>';
            }
        $actionButton  .='</ul>';
        $actionButton  .='</div>';

        $dataRow[] = $actionButton;

        $final[] = array_merge(array("DT_RowId" => $row['roleid']), array_values($dataRow));
    }
    $json_data = array(
                    "draw"=> intval( $input['draw'] ), 
                    "recordsTotal"    => intval($totalrows),
                    "recordsFiltered" => intval($totalrows),
                    "data"            => $final
                );

    echo json_encode($json_data);
}

elseif (isset($_GET['showAddUpdateProgramDisclaimerModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $modalTitle = sprintf(gettext('Create %s Disclaimer'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    $programDisclaimer = $group->getProgramDisclaimer();
    if ($programDisclaimer) {
        $modalTitle = sprintf(gettext('Update %s Disclaimer'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));
    }

    include(__DIR__ . "/views/talentpeak/team_configuration/add_update_program_team_disclaimer.php");
}

elseif (isset($_GET['addUpdateProgramDisclaimer']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $program_disclaimer = ViewHelper::RedactorContentValidateAndCleanup($_POST['program_disclaimer']);
    $group->saveProgramDisclaimer($program_disclaimer);
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Disclaimer data saved successfully'), gettext('Success'));
}

elseif(isset($_GET['addOrUpdateTeamScheduleEvent']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    $event = null;
    $collaborate = '';
    $chapterids = '0';
    $channelid = 0;
    $regionid = 0;
    $isprivate = 0;
    $seriesEvent = null;
    $add_photo_disclaimer = 0;
    $event_series_id = 0;
    $touchpointid = 0;

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($teamid = $_COMPANY->decodeId($_POST['teamid'])) < 1 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($eventid = $_COMPANY->decodeId($_POST['eventid'])) < 0 ||
        ($touchpointid = $_COMPANY->decodeId($_POST['touchpointid'])) < 0 ||
        ($schedule_id = $_COMPANY->decodeId($_POST['schedule_id'])) < 0 ||
        (isset($_POST['timezone'])
            && !isValidTimeZone($_POST['timezone'])
        )
    ){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($eventid && ($event=TeamEvent::GetEvent($eventid)) === null){
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if ($event){
        $version = (int) $_COMPANY->decodeId($_POST['version']);
        if ($event->val('version') >$version){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("You are not editing the latest version and your changes cannot be saved. In order to not lose your work, please copy your changes locally, re-edit the event, apply your changes, and try to save again."), gettext('Error'));
        }
    }

    // Authorization Check
    if (!$_USER->isProgramTeamMember($teamid) && !$_USER->canManageGroup($groupid)
     ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $eventtitle = $_POST['eventtitle'];
    $eventtype = 0; 
    $event_description = ViewHelper::RedactorContentValidateAndCleanup( $_POST['event_description']);
    $event_attendence_type = 2;// default Virtual (Web Conference) only
    $event_contact = '';
    $eventvanue = '';
    $vanueaddress = '';
    $venue_info = '';
    $venue_room = '';
    $web_conference_link = '';
    $web_conference_detail = '';
    $web_conference_sp = '';
    $latitude = '';
    $longitude = '';
    $multiDayEvent = 0;
    $invited_groups = '';
    $checkin_enabled = intval($_COMPANY->getAppCustomization()['event']['checkin'] && $_COMPANY->getAppCustomization()['event']['checkin_default']);;
    $check = array('Event Name' => @$eventtitle,'Event time Slot' => @$_POST['time_slot']);
   
    $checkrequired = $db->checkRequired($check);

    if ($checkrequired) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"),$checkrequired), gettext('Error'));
    }

    $max_inperson = 0;
    $max_inperson_waitlist = 0;
    $max_online = 0;
    $max_online_waitlist = 0;

    #Time zone
    $event_tz = isset($_POST['timezone']) ? $_POST['timezone'] : (@$_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC');
    #Event Start Date time
    $time_slot = $_POST['time_slot'];
    $startDatetimeObj = Date::ConvertDatetimeTimezone($time_slot,$event_tz, "UTC");
    $start = $startDatetimeObj->format("Y-m-d H:i:s");
    $hour_duration = 0;
    $minutes_duration =  15; // Default
    $mentors  = $team->getTeamMembersBasedOnSysRoleid(2);
    $isAllSlotsBooked = false;
    if (!empty($mentors)) {
        $mentorUserid = $mentors[0]['userid'];
        $schedule = UserSchedule::GetSchedule($schedule_id);

        if ($schedule) {
            $schedule_slot_duration_in_minutes = intval($schedule->val('schedule_slot'));
            [$hour_duration, $minutes_duration] = Date::ConvertMinutesToHoursMinutes($schedule_slot_duration_in_minutes);
            $meetingLink = $schedule->getLeastUsedMeetingLink($start);
            if ($meetingLink){
                $web_conference_link = $meetingLink['meetinglink'];
                $web_conference_sp = "Meeting Link";
            }
            [$totalSlotsArray,$totalUpcomingSlotsArray,$grossBookedSlotsArray,$upcomingBookedSlotsArray] = UserSchedule::GetAvailableAndBookedScheduleSlots($schedule->val('schedule_id'));

            if ((count($totalSlotsArray)-(count($grossBookedSlotsArray)+1)) < 1){ // IN grossBookedSlotsArrayare checking only published event, as this event will be published shortly so adding 1 on $grossBookedSlotsArray) count
                $isAllSlotsBooked = true;
            }
        }
    }
   
    #Event End Date time
    $endDatetimeObj = Date::IncrementDatetime($start, "UTC", $hour_duration, $minutes_duration);
    $end = $endDatetimeObj->format("Y-m-d H:i:s");
    $invited_locations = 0;
    // Custom Fields
    $custom_fields_input = json_encode(array());
    $budgeted_amount = 0;
    $calendar_blocks = 1;

    if (!$eventid) {
        $eventid = TeamEvent::CreateNewTeamEvent($teamid, $groupid, $chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $event_series_id, $custom_fields_input, $event_contact, $venue_info, $venue_room, $isprivate, 'teamevent', $add_photo_disclaimer, 1);
        if ($eventid) {
            $event = TeamEvent::GetEvent($eventid);

            // For new events process createEventScheduleData after event creation
            $eventScheduleDataRetVal = $event->createEventScheduleData($schedule_id,$start);
            if (!$eventScheduleDataRetVal) {
                // We could not create event schedule data for the given start date, do not allow this event and error out.
                $event->deleteIt();
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Sorry, we couldn't book the slot, it may have already been taken."), gettext('Error'));
            }
            $event->updateScheduleId($schedule_id);

            if (!$touchpointid){ // Create and link Touch point
                $touchpointid = $team->addOrUpdateTeamTask(0, $eventtitle, '0', $start, '', 'touchpoint', 0);
            }
            $team->linkTouchpointEventId($touchpointid,$eventid,$end);

            // Add Event Members to prevent users to schedule same slot again.
            $teamMembers = $team->getTeamMembers(0);
            foreach($teamMembers as $member) {
                $event->joinEvent($member['userid'],1,1,0);
            }
            // Publish Email Update
            $delay = 15;
           
            $isactive = Event::STATUS_AWAITING;
            $update_code = $event->updateEventForSchedulePublishing($delay);

            $job = new EventJob($groupid, $eventid);
            if (!empty($publish_date)){
                $job->delay = $delay;
            }
            $job->saveAsBatchCreateType(1,array());

            if ($isAllSlotsBooked) {
                UserSchedule::SendAllSlotsBookedNotification($schedule_id);
            }


            $redirect_to = "eventview?id=" . $_COMPANY->encodeId($eventid);
            AjaxResponse::SuccessAndExit_STRING(1, $redirect_to, gettext("Event saved successfully"), gettext('Success'));
        }

    } else {

        // For updates process updateOrCreateEventScheduleData before updating the event
        $eventScheduleDataRetVal = $event->updateOrCreateEventScheduleData($schedule_id, $start);
        if (!$eventScheduleDataRetVal) {
            // We could not update event schedule data for the given start date, do not allow this event and error out.
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Sorry, we couldn't book the slot, it may have already been taken."), gettext('Error'));
        }

        // Groupid cannot be changed for event, since this is update get the groupid from event
        $groupid = $event->val('groupid');
        $update  = $event->updateTeamEvent($chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $custom_fields_input, $event_contact, $add_photo_disclaimer, $venue_info, $venue_room, $isprivate, $calendar_blocks);
        $event->updateScheduleId($schedule_id);
        $team->linkTouchpointEventId($touchpointid,$eventid,$end);
        // Send Email Update
        $job = new EventJob($groupid, $eventid);
        $job->saveAsBatchUpdateType(1,array(1,2),array());
        $redirect_to = "eventview?id=" . $_COMPANY->encodeId($eventid);
        AjaxResponse::SuccessAndExit_STRING(1, $redirect_to, gettext("Event updated successfully."), gettext('Success'));
    }
}

elseif (isset($_GET['initTouchPointDetailToOutlook']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($teamid = $_COMPANY->decodeId($_GET['teamid'])) < 0 ||
        ($team = Team::GetTeam($teamid)) == null ||
        ($touchpointid = $_COMPANY->decodeId($_GET['touchpointid'])) < 1 ||
        ($touchpoint = TeamTask::GetTeamTask($touchpointid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $markCompleteUrl = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'detail?id=' . $_COMPANY->encodeId($team->val('groupid')) . '&hash=getMyTeams-'.$_COMPANY->encodeId($teamid).'-'.$_COMPANY->encodeId($touchpointid).'-STATUS_'.$_COMPANY->encodeId(52);
    $teamMembers = $team->getTeamMembers(0);
    include(__DIR__ . "/views/talentpeak/teamevent/copy_touchpoint_detail_to_outlook_email.template.php");
    exit();
}

elseif (isset($_GET['appendNewAttributeFilter']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $primary_parameters  = array();
    $searchAttributes = array();
    if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']){
        $primary_parameters  = array_flip(array_values(UserCatalog::GetAllCatalogCategories()));
        $searchAttributes =  $group->getProgramDiscoverSearchAttributes();

    } else{
        $matchingParameters = $group->getTeamMatchingAlgorithmParameters();
        if (array_key_exists('primary_parameters',$matchingParameters)){
            $primary_parameters  = $matchingParameters['primary_parameters'];
        }
        $customParameters = array();
        if (array_key_exists('primary_parameters',$matchingParameters)){
            $customParameters = $matchingParameters['custom_parameters'];
        }
        $searchAttributes = $group->getProgramDiscoverSearchAttributes();
    }

    $add_more_attributes_index = (int)$_GET['add_more_attributes_index'];
?>
    <div class="col-12 mx-0 p-0 mt-2">
        <div class="col-12 col-sm-6 px-md-1">
            <select name="primary_attribute[]" id="primary_attribute_<?= $add_more_attributes_index; ?>" aria-label="<?= sprintf(gettext('Filter %s - Select a Filter Attribute'), $add_more_attributes_index); ?>" 
             class="form-control" onchange="getTeamAttributeValuesByKey('<?= $_COMPANY->encodeId($groupid); ?>',this.value,<?= $add_more_attributes_index; ?>)">
                <option data-keyType='' value=""><?= gettext("Select a Filter")?></option>
                <?php foreach($primary_parameters as $key => $value){ 
                    if(!in_array($key,$searchAttributes['primary'])) {
                        continue;
                    }
                ?>
                    <option data-keyType='primary' value="<?=$key;?>"><?=$key;?></option>
                <?php } ?>
                <?php foreach($customParameters as $key => $value) { 
                    $q = $group->getTeamCustomAttributesQuestion($key);
                    if (empty($q)) {
                        continue;
                    }
                    if ($q['type'] == 'comment') { continue; }

                    if(!in_array($key,$searchAttributes['custom'])) {
                        continue;
                    }
                ?>
                    <option data-keyType='custom' value="<?=$key;?>"><?=$q['title']??$q['name'];?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-12 col-sm-6 px-md-1" id="attribute_keyword_div_<?= $add_more_attributes_index; ?>">
            <select name="attribute_keyword[]" id="attribute_keyword_<?= $add_more_attributes_index; ?>" aria-label="<?= sprintf(gettext('Select Filter %s Attribute First -'), $add_more_attributes_index); ?>" class="form-control">
                <option data-keyType='' value=""><?= gettext("Select a Filter First")?></option>
            </select>
        </div>
    </div>
<?php
    exit();
}

elseif (isset($_GET['updateProgressBarSetting'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

	if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($contextKey = $_POST['contextKey']) == '' ||
        (!in_array($contextKey,array('show_actionitem_progress_bar','show_touchpoint_progress_bar')))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $status = $_POST['status'] ? 1 : 0;
    echo $group->updateActionItemTouchPointProgressBarSetting($contextKey,$status);
    exit();
}

elseif (isset($_GET['getTeamsTableList'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

    if (
        ($groupid = $_COMPANY->decodeId($_GET['getTeamsTableList'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $searchKeyword  = $_POST['search']['value'];
    $startLimit = $_POST['start'];
    $endLimit = $_POST['length'];
    $reloadData = $_POST['reloadData']??0;

    if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { 
        $orderFields = ['team_name','chaptername','', 'last_activity','feedback_count','isactive'];
    } else{
        $orderFields = ['team_name','','last_activity','feedback_count','isactive'];
    }
    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderBy = $orderFields[$orderIndex];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';

    $_SESSION['teamFilterActiveTab'] ??= Team::STATUS_ACTIVE; // Default
    $statusFilter = array();
    if (isset($_POST['statusFilter'])) {
        $_SESSION['teamFilterActiveTab'] = ($_POST['statusFilter'] == 'all') ? 'all' : $_COMPANY->decodeId($_POST['statusFilter']);
    }
    
    if ($_SESSION['teamFilterActiveTab'] != 'all') {
        $statusFilter[] = $_SESSION['teamFilterActiveTab'];
    }

    [$totalrows, $teams] = Team::GetAllTeamsInGroup($groupid, '0', $startLimit, $endLimit, $searchKeyword, $reloadData, $orderBy, $orderDir, $statusFilter);
   
    $final = [];
    foreach($teams as $team){
        if (!$_USER->canManageContentInScopeCSV($team['groupid'], $team['chapterid'])) {
            continue;
        }
        $tableRow = array();
        $teamObj = Team::Hydrate($team['teamid'], $team);
        $teamMembers = $team['team_members'];
        
        $status = array('0'=>gettext("In-active"),'100'=>gettext("Deleted"),'1'=>gettext("Active"),'2'=>gettext("Draft"),'110'=>gettext("Complete"),'108'=>gettext("Paused"),'109'=>gettext("Incomplete"));
        
        $teamNameLabel = htmlspecialchars($team['team_name']);

        if(in_array($team['isactive'], [Team::STATUS_ACTIVE,Team::STATUS_COMPLETE,Team::STATUS_INCOMPLETE, Team::STATUS_INACTIVE,Team::STATUS_PAUSED])){ 
        $teamName = '<a role="button" href="javascript:getTeamDetail(\''.$_COMPANY->encodeId($groupid).'\',\''.$_COMPANY->encodeId($team['teamid']).'\',0)">'.$teamNameLabel.'</a>';
        } else {
            $teamName = $teamNameLabel; 
        }
        $teamName .= '<span class="hidden">'.implode(',',array_column($teamMembers,'email')).'</span>';
        $tableRow[] = $teamName;


        if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { 
            $chaptername = '-';
            if ($team['chaptername']){
                $chaptername = $team['chaptername'];
                if ($team['chapterStatus'] == 0){
                $chaptername .= "<font color='red'> (In-active)</font>";
                }
            }
            $tableRow[]  = $chaptername;
        }


        $roles = array_column($teamMembers, 'role');
        $roleCounts = array_count_values($roles);
        $rolesList = '';
        $list = '';
        if (!empty($roleCounts)) {
            $list .= '<ul>';
            foreach ($roleCounts as $role => $count) {
                $list .= '<li class="m-0"><a role="button" class="menu-li-list" href="javascript:void(0)" onclick="manageTeamMembers(\''. $_COMPANY->encodeId($groupid).'\',\''.$_COMPANY->encodeId($team['teamid']).'\',0)">'.htmlspecialchars($role)." : ".$count.'</a></li>';
            }
            $list .= '</ul>';
        } else {
            $list = '<a role="button" class="menu-li-list" href="javascript:void(0)" onclick="manageTeamMembers(\''. $_COMPANY->encodeId($groupid).'\',\''.$_COMPANY->encodeId($team['teamid']).'\',0)">'.gettext('Not Assigned').'</a>';
        }
        $rolesList .= $list;
        $rolesList .= '';
        $tableRow[] = $rolesList;


        $last_activity = $team['last_activity'];
        $last_activity_ago = $last_activity ? $db->timeago($last_activity) : '';
        $tableRow[] =  '<span class="hide">'.$last_activity.'</span>'.$last_activity_ago;
        

        $tableRow[] =  $team['feedback_count'] ?? 0;

        $tableRow[] = $status[$team['isactive']];
        
        $actionBtn = '<div class="" style="color: #fff; float: right;">';
        $actionBtn .= '<button id="teamBtn'.$_COMPANY->encodeId($team['teamid']).'" aria-label="'.sprintf(gettext('Action %1$s'),$team['team_name']).'" class="btn btn-sm btn-affinity dropdown-toggle" title="Action" type="button" data-toggle="dropdown">';
        $actionBtn .= gettext('Action').'&emsp;&#9662</button>';
        $actionBtn .= '<ul class="dropdown-menu dropdown-menu-right dropdown-action-menu-list" id="dynamicActionButton'.$team['teamid'].'" style="width:250px; cursor: pointer;">';
        ob_start();
        include(__DIR__ . "/views/talentpeak/teams/team_action_button.template.php");
        $aBtn = ob_get_clean();    
        $actionBtn .= $aBtn;
        $actionBtn .= '</ul>';
        $actionBtn .= '</div>';
        $tableRow[] = $actionBtn;

        $final[] = array_merge(array("DT_RowId" => $team['teamid'],"DT_RowClass"=>'background'.$team['isactive']), array_values($tableRow));
    }
    $json_data = array(
                    "draw"=> intval( $_POST['draw'] ), 
                    "recordsTotal"    => intval($totalrows),
                    "recordsFiltered" => intval($totalrows), 
                    "data"            => $final
                );

    echo json_encode($json_data);
}

elseif (isset($_GET['viewRequestMatchingStats']) && $_SERVER['REQUEST_METHOD'] === 'GET') {

    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($request_id = $_COMPANY->decodeId($_GET['request_id'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $requestDetail = Team::GetTeamRequestDetail($groupid,$request_id);

    if ($requestDetail){

        $senderid       = $requestDetail['senderid'];
        $sender_role_id = $requestDetail['sender_role_id'];
        $receiverid     = $requestDetail['receiverid'];
        $receiver_role_id = $requestDetail['receiver_role_id'];
        $oppositeUseridsWithRoles = array(
                array(
                    'userid'=>$senderid,
                    'roleid'=>$sender_role_id
                )
        );

        $oppositeUseridsWithRoles = [
            'oppositeUserids' => $oppositeUseridsWithRoles,
            'skipJoinRequestCapacityCheck'=>true
        ];
        [$status, $matchingStats]  = Team::GetTeamMembersSuggestionsForRequestRoles($group, $receiverid, $receiver_role_id, $oppositeUseridsWithRoles);
        $matchedUser = array();
        $statsMatched = false;
        if (empty($matchingStats) || empty($matchingStats[0]['suggestions'][0])) {
            $senderRequestDetail = Team::GetRequestDetail($groupid, $sender_role_id, $senderid);
            $sender = User::GetUser($senderid);
            $matchedUser['firstname'] = $sender->val('firstname');
            $matchedUser['lastname'] = $sender->val('lastname');
            $matchedUser['picture'] = $sender->val('picture');
            $matchedUser['email'] = $sender->val('email');
            $matchedUser['jobtitle'] = $sender->val('jobtitle');
            $matchedUser['department'] = $sender->getDepartmentName();
            $matchedUser['userid'] = $sender->id();

            $roleName = $senderRequestDetail['type']; 
            $pageTitle = sprintf(gettext("Matching Detail for the %s Role"),$roleName);

        } else {
            $matchedUser = $matchingStats[0]['suggestions'][0];
            $roleName = $matchingStats[0]['oppositRolesType']; 
            $pageTitle = sprintf(gettext("Matching Detail for the %s Role"),$roleName);
            $matchingPercentage = $matchedUser['matchingPercentage'];
            $parameterWiseMatchingPercentage = $matchedUser['parameterWiseMatchingPercentage'];
            $statsMatched = true;
        }

        include(__DIR__ . "/views/talentpeak/teams/team_member_matching_stats.template.php");
        
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("The request does not exist anymore"), gettext('Error'));
    }
}

elseif (isset($_GET['loadMoreDiscoverSuggestions']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null || 
        ($roleit_to_match = $_COMPANY->decodeId($_POST['roleit_to_match'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $showAvailableCapacityOnly = $_SESSION['showAvailableCapacityOnly'];

    $filter_attribute_keyword = array();
    $filter_primary_attribute = array();
    $filter_attribute_type = array();
    $name_keyword = "";
    $oppositeUseridsWithRoles = array();

    if (!empty($_POST['search'])){
        // All search filter attributes are optional
        $filter_attribute_keyword = $_POST['attribute_keyword'] ?? array();
        $filter_primary_attribute = $_POST['primary_attribute'] ?? array();
        $filter_attribute_type    = explode(',', $_POST['filter_attribute_type']??'');
        $name_keyword = trim($_POST['name_keyword'] ?? '');
    }

    $suggestion_page_counter = $_POST['suggestion_page_counter']?:2;
    
    [$status, $roleRequestsWithSuggestions]  = Team::GetTeamMembersSuggestionsForRequestRoles($group,$_USER->id(),$roleit_to_match,$oppositeUseridsWithRoles,$filter_attribute_keyword, $filter_primary_attribute, $name_keyword, $filter_attribute_type,$suggestion_page_counter);

    if ($status == 1) {
        $matchingParameters = $group->getTeamMatchingAlgorithmParameters();
        $suggestion_counter = $_POST['suggestion_counter']?:1;
        foreach($roleRequestsWithSuggestions as $joinRequest){
            $matchedUsers = $joinRequest['suggestions'];
            $totalSuggetions = $matchedUsers['totalSuggestionsCount'];
            $loadMoreDataAvailable = $joinRequest['loadMoreDataAvailable'];
            $oppositeRole = Team::GetTeamRoleType($joinRequest['oppositRoleId']);
            $canSendRequest = true;
            $bannerHoverTextSenderCapacity = '';
            if(!Team::CanSendP2PTeamJoinRequest($groupid,$_USER->id(),$joinRequest['roleid'])){
                $canSendRequest = false;
                $bannerHoverTextSenderCapacity = sprintf(gettext('You can\'t send a request to this user as you\'ve reached your maximum available capacity limit. This limit is based on the number of %1$s you\'re already in and any outstanding %2$s join requests'), Team::GetTeamCustomMetaName($group->getTeamProgramType(), 1), Team::GetTeamCustomMetaName($group->getTeamProgramType(), 0));
            }
            $totalSuggetionsPerCall = 0;
            if (!empty($matchedUsers)){
                if ($showAvailableCapacityOnly) {  // Fiter 
                    $availableRequestCapacityMatchedUsers = array();
                    foreach($matchedUsers as $matchedUser) {
                        list(
                            $roleSetCapacity,
                            $roleUsedCapacity,
                            $roleRequestBuffer,
                            $roleAvailableCapacity,
                            $roleAvailableRequestCapacity,
                            $roleAvailableBufferedRequestCapacity,
                            $pendingSentOrReceivedRequestCount
                        ) = Team::GetRoleCapacityValues($groupid, $joinRequest['oppositRoleId'], $matchedUser['userid']);

                        if ($roleSetCapacity== 0 || $roleAvailableRequestCapacity > 0){
                            $availableRequestCapacityMatchedUsers[] = $matchedUser;
                        }
                    }
                    $matchedUsers = $availableRequestCapacityMatchedUsers;
                }
                $totalSuggetionsPerCall = count($matchedUsers);
                $matchedUsers = array_chunk($matchedUsers,MAX_TEAMS_ROLE_MATCHING_RESULTS_PER_DISCOVER_PAGE);
                $totalSuggetionsChunks = count($matchedUsers);

                $p = 1;
                foreach($matchedUsers as $matchedUserChunk){ ?>
                    <div data-page="<?= $p; ?>" class="pagination_container_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>" >
    
                    <?php foreach($matchedUserChunk as $matchedUser){
                        $matchingPercentage = $matchedUser['matchingPercentage'];
                        $parameterWiseMatchingPercentage = $matchedUser['parameterWiseMatchingPercentage'];
                        $requestDetail = Team::GetTeamJoinRequestDetail($groupid,$joinRequest['userid'],$joinRequest['roleid'],$matchedUser['userid'],$joinRequest['oppositRoleId']);
    
                        list(
                            $roleSetCapacity,
                            $roleUsedCapacity,
                            $roleRequestBuffer,
                            $roleAvailableCapacity,
                            $roleAvailableRequestCapacity,
                            $roleAvailableBufferedRequestCapacity,
                            $pendingSentOrReceivedRequestCount
                        ) = Team::GetRoleCapacityValues($groupid, $joinRequest['oppositRoleId'], $matchedUser['userid']);
    
                        $canAcceptRequest = true;
                        $bannerHeading = gettext('Accepting New Requests');
                        $bannerSubHeading = ($roleAvailableRequestCapacity == 1) ? sprintf(gettext('%s spot available'),$roleAvailableRequestCapacity) : sprintf(gettext('%s spots available'),$roleAvailableRequestCapacity);
                        $bannerHoverText = '';
                        if ($roleSetCapacity !=0 && $roleAvailableRequestCapacity < 1){ // $roleSetCapacity = 0 means unlimited
                            $canAcceptRequest = false;
                            $bannerHeading = gettext('Not Accepting New Requests');
                            $bannerSubHeading = gettext('No spots available');
                            $bannerHoverText = gettext('You cannot send request as user\'s maximum outstanding requests have been reached.');
                        }
                        include(__DIR__ . "/views/talentpeak/teams/discover_team_member_card.template.php");
                   } ?>
                    <!-- </div> -->
                </div>
        <?php $p++; }
            } ?>
            <script>
                  $(function() {
                    $("#suggestion_counter_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>").val(<?= $p; ?>);
                    // Number of items and limits the number of items per page
                    var numberOfItems = $(".pagination_container_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?> .content<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>").length;
                    var limitPerPage = <?=MAX_TEAMS_ROLE_MATCHING_RESULTS_PER_DISCOVER_PAGE?>;
                    // Total pages rounded upwards
                    var totalPages = Math.ceil(numberOfItems / limitPerPage);
                    var paginationSize = 8;
                    var currentPage;

                    function showPage(whichPage) {
                        if (whichPage < 1 || whichPage > totalPages) return false;
                        currentPage = whichPage;
                        $("#suggestion_active_pagination_page_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>").val(whichPage);
                        $(".pagination_container_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?> .content<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>")
                        .hide()
                        .slice((currentPage - 1) * limitPerPage, currentPage * limitPerPage)
                        .show();

                        
                        // Replace the navigation items (not prev/next):
                        $(".pagination<?= $_COMPANY->encodeId($joinRequest['roleid']); ?> li").slice(1, -1).remove();
                        getPageList(totalPages, currentPage, paginationSize).forEach(item => {
                            var activeItem = (item === currentPage ? "true" : "");

                        $("<li>")
                            .addClass(
                            "page-item " +
                                (item ? "current-page " : "") +
                                (item === currentPage ? "active " : "")
                            )
                            .append(
                            $("<a>")
                                .attr("aria-label","page "+item)
                                .attr("aria-current",activeItem)
                                .addClass("page-link")                                                              
                                .attr({
                                    id: "page_link_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>"+item,
                                href: "javascript:void(0)"
                                })
                                .text(item || "...")
                            )
                            .insertBefore("#next-page<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>");
                        });
                        return true;
                    }

                    // Include the prev/next buttons:
                    $(".pagination<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>").append(
                        $("<li>").addClass("page-item").attr({ id: "previous-page<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>" }).append(
                        $("<a>")
                            .addClass("page-link")
                            .attr("aria-label","<?= gettext('Previous Page');?>")
                            .attr({
                            href: "javascript:void(0)"
                            })
                            .text("Prev")
                        ),
                        $("<li>").addClass("page-item").attr({ id: "next-page<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>" }).append(
                        $("<a>")
                            .addClass("page-link")
                            .attr("aria-label","<?= gettext('Next Page');?>")
                            .attr({
                            href: "javascript:void(0)"
                            })
                            .text("Next")
                        )
                    );
                    // Show the page links
                    $(".pagination_container_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>").show();

                    let selectedPage = parseInt($("#suggestion_active_pagination_page_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>").val());
                    showPage(selectedPage);

                    // Use event delegation, as these items are recreated later
                    $(
                        document
                    ).on("click", ".pagination<?= $_COMPANY->encodeId($joinRequest['roleid']); ?> li.current-page:not(.active)", function() {
                        return showPage(+$(this).text());
                    });
                    $("#next-page<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>").on("click", function() {
                        return showPage(currentPage + 1);
                    });

                    $("#previous-page<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>").on("click", function() {
                        return showPage(currentPage - 1);
                    });

                    $('[data-toggle="popover"]').popover({
                        sanitize:false                    
                    });  
                });

                <?php if(!$loadMoreDataAvailable){ ?>
                    $("#load_more_container_<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>").hide();
                    var totalSuggetionCards = parseInt($('#suggetionCards<?= $_COMPANY->encodeId($joinRequest['roleid']); ?> > span.totalSuggetionCards').html()) + parseInt(<?= $totalSuggetionsPerCall; ?>);

                    $('#suggetionCreds<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>').html('<?=  sprintf(gettext('Found <span class="totalSuggetionCards">%s</span> matches'),$totalSuggetionsPerCall)?>');

                    $('#suggetionCards<?= $_COMPANY->encodeId($joinRequest['roleid']); ?> > span.totalSuggetionCards').html(totalSuggetionCards);

                <?php } else { ?>
                    var totalSuggetionCards = parseInt($('#suggetionCards<?= $_COMPANY->encodeId($joinRequest['roleid']); ?> > span.totalSuggetionCards').html()) + parseInt(<?= $totalSuggetionsPerCall; ?>);
                    $('#suggetionCards<?= $_COMPANY->encodeId($joinRequest['roleid']); ?> > span.totalSuggetionCards').html(totalSuggetionCards);

                    <?php if (empty($matchedUsers)){ ?>
                        loadMoreDiscoverSuggestions('<?= $_COMPANY->encodeId($groupid); ?>',1,'<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>')
                    <?php } ?>

                <?php } ?>

            </script>

<?php
        }
    
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('To Discover your matches, please Register for a role'), gettext('Error'));
    }
    exit();
}


elseif (isset($_GET['openActionConfigurationModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    // Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $actionItemConfig = $group->getActionGonfiguration();

	include(__DIR__ . "/views/talentpeak/team_configuration/manage_team_action_item_configuration.php");
}


elseif (isset($_GET['updateActionItemConfig']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === NULL
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
    // Authorization Check
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $action_item_visibility = $_POST['action_item_visibility'];
    if (!isset(Group::ACTION_ITEM_VISIBILITY_SETTING[$action_item_visibility])) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again"), gettext('Error'));
    }
    
    if ($group->updateActionItemConfiguration($action_item_visibility)){
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Action item visibility configuration updated successfully"), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again"), gettext('Error'));
    }
}


elseif (isset($_GET['selfAssignTeamToUserForm']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($menteeRoleId = $_COMPANY->decodeId($_GET['menteeRoleId'])) < 1 ||
        ($useridSelected = $_COMPANY->decodeId($_GET['menteeUserId'])) < 1 
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $section = 3;

    $joinRequest = Team::GetRequestDetail($groupid, $menteeRoleId,$useridSelected);
    $canMenteeJoinTeam = (!empty($joinRequest) && Team::DoesJoinRequestHaveActiveCapacity($joinRequest)); 
    if (!$canMenteeJoinTeam){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("The maximum available capacity limit has been reached for this user."), gettext('Error'));
    }

    $myRequests = Team::GetUserJoinRequests($groupid,$_USER->id(),1);
    $filteredData = array_filter($myRequests, function($item) {
        return $item['sys_team_role_type'] == 2;
    });
    // Re-index the array to ensure keys are continuous
    $mentorRequestDetail = array_values($filteredData);
    $canMentorJoinTeam = (!empty($mentorRequestDetail) && Team::DoesJoinRequestHaveActiveCapacity($mentorRequestDetail[0])); 
    if (!$canMentorJoinTeam){
       AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Your maximum available capacity limit has been reached for this role.'), gettext('Error'));
    }
    $mentorJoinRequest = $mentorRequestDetail[0];
    $selectedUser = USER::GetUser($useridSelected);

    $suggestedTeamName = $_USER->getFullName() .' & '.$selectedUser->getFullName();

    $allRoles = Team::GetProgramTeamRoles($groupid, 1);
    $teams = array();
    $pageTitle = sprintf(gettext("Create new %s and assign"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));

    $chapters = array();
    if ($_COMPANY->getAppCustomization()['chapter']['enabled']){
        $requestedChpaters = explode(',',$joinRequest['chapterids']);
        $allchapters = $group->getAllChapters();
        foreach($allchapters as $ch){
            $chapter = $ch;
            $suffix = "";
            if ($chapter['isactive'] == 1 || $chapter['isactive'] == 0){

                if (in_array($chapter['chapterid'],$requestedChpaters)){
                    $suffix = " (Requested to Join)";
                }
                if ($chapter['isactive'] == 0){
                    $suffix .= " (In-active)";
                }
                $chapter['chaptername'] =  htmlspecialchars_decode($chapter['chaptername']).$suffix;
                $chapters[] = $chapter;
            }
        }
    }

    include(__DIR__ . "/views/talentpeak/teams/create_and_assign_input.template.php");
    exit();
}

elseif (isset($_GET['autoMatchWithMentorRole']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($roleid = $_COMPANY->decodeId($_POST['roleid'])) < 1 ||
        ($role = Team::GetTeamRoleType($roleid)) === null 
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $_SESSION['showAvailableCapacityOnly'] = isset($_POST['showAvailableCapacityOnly']) ? $_POST['showAvailableCapacityOnly'] : $group->getProgramDiscoverSearchAttributes()['default_for_show_only_with_available_capacity'];
    $showAvailableCapacityOnly = $_SESSION['showAvailableCapacityOnly'];

    $filter_attribute_keyword = array();
    $filter_primary_attribute = array();
    $filter_attribute_type = array();
    $name_keyword = "";
    $oppositeUseridsWithRoles = array();

    [$status, $roleRequestsWithSuggestions]  = Team::GetTeamMembersSuggestionsForRequestRoles($group,$_USER->id(),$roleid,$oppositeUseridsWithRoles,$filter_attribute_keyword, $filter_primary_attribute, $name_keyword, $filter_attribute_type);

    if ($status == 1) {
        $suggestions = $roleRequestsWithSuggestions[0]['suggestions'];

        if (!empty($suggestions)) {
            $bestSuggestedUserid = Team::GetBestSuggestedUserForNetworking($groupid, $_USER->id(), $suggestions);
            if ($bestSuggestedUserid){

                $matchedUser = User::GetUser($bestSuggestedUserid);
                $teamName = $_USER->getFullName().' & '.$matchedUser->getFullName();

                $teamid = Team::CreateOrUpdateTeam($groupid,0,$teamName);

                $team = Team::GetTeam($teamid);
                if ($team) {
                    $team->addUpdateTeamMember($roleid, $_USER->id());
                    $oppositRoleId = $roleRequestsWithSuggestions[0]['oppositRoleId'];
                    $team->addUpdateTeamMember($oppositRoleId, $bestSuggestedUserid);
                    $team->activate(false);
                    AjaxResponse::SuccessAndExit_STRING(1, '', sprintf(gettext('We found the best match for your requested role and created a %1$s %2$s for you.'),$team->val('team_name'), Team::GetTeamCustomMetaName($group->getTeamProgramType())), gettext('Success'));
                } 
            }
        }
        AjaxResponse::SuccessAndExit_STRING(2, '', gettext('We tried to automatically match you with the best role for your request, but no match was found. Please stay tuned.'), gettext('Info'));

    
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('To Discover your matches, please Register for a role'), gettext('Error'));
    }
    exit();
}

elseif (isset($_GET['updateTeamWorkflowSetting'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

	if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($contextKey = $_POST['contextKey']) == '' ||
        (!in_array($contextKey,array('hide_member_in_discover_tab','any_mentor_can_complete_team','any_mentee_can_complete_team', 'auto_complete_team_on_action_touchpoints_complete')))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $status = (int) $_POST['status'];
    echo $group->saveTeamWorkflowSetting($contextKey,$status);
    exit();
}
elseif (isset($_GET['saveTeamAutoCompleteSetting'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){

	if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $automatically_close_team_after_n_days = (int) $_POST['automatically_close_team_after_n_days'];
    if ( $automatically_close_team_after_n_days < 0) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter a value greater than or equal to zero. Zero indicates disabled.'), gettext('Error'));
    }
    $contextKey = 'automatically_close_team_after_n_days';
    echo $group->saveTeamWorkflowSetting($contextKey,$automatically_close_team_after_n_days);
    exit();
}
elseif (isset($_GET['saveTeamAutoCompleteOnMenteeStartDateSetting'])  && $_SERVER['REQUEST_METHOD'] === 'POST'){
	if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<0 ||
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    if (!$_USER->canManageGroup($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $auto_complete_team_ndays_after_mentee_start_date = (int) $_POST['auto_complete_team_ndays_after_mentee_start_date'];
    if ($auto_complete_team_ndays_after_mentee_start_date < 0) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please enter a value greater than or equal to zero. Zero indicates disabled.'), gettext('Error'));
    }
    $contextKey = 'auto_complete_team_ndays_after_mentee_start_date';
    echo $group->saveTeamWorkflowSetting($contextKey, $auto_complete_team_ndays_after_mentee_start_date);
    exit();
}
else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");

    exit();
}
