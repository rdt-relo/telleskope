<?php
require_once __DIR__.'/head.php';
$pagetitle = "Bulk Provisioning";
$error = null;
$success = null;
global $_COMPANY; /* @var Company $_COMPANY */
global $_ZONE;
global $db;
$zoneid = $_ZONE->id();

if ($_COMPANY->val('in_maintenance') < 2) {
    echo "Error: Company needs to be in <strong style='color:blue;'>maintenance mode 2 or higher</strong> for bulk provisioing";
    exit(0);
}

if (isset($_POST['submit'])){

    $provision = (int) $_POST['provision'];
    $primaryKey = ($_POST['primaryKey'] === 'email') ? 'email' : 'externalId';
    
    if(!empty($_FILES['import_file']['name'])){
		$file 	   		=	basename($_FILES['import_file']['name']);
		$tmp 			=	$_FILES['import_file']['tmp_name'];
        $ext = substr(pathinfo($file)['extension'],0,4);
        // Allow certain file formats
        if($ext != "csv"  ) {
            $error =  "Sorry, only .csv file format allowed.";
        }

        if (!$error) {
            try {
                $csv = Csv::ParseFile($tmp);

                if ($csv) {
                    if ($provision == 5) { // By Office Branches
                        $success = importOfficeBranches($csv);
                    } elseif ($provision == 6) { // Chapter Region and Office braches
                        $success = importChapterRegionAndBranches($csv);
                    } elseif($provision == 7){ // Event Imports
                        $success = importEvents($csv);
                    } elseif($provision == 8){ // Event Imports
                        $success = updateExternalids($csv);
                    } elseif(in_array($provision,array(1,2,3,4,9,14,15))) {
                        $success = importUsersCsvData($csv, $provision, $zoneid, $primaryKey);
                    } elseif(in_array($provision,array(10))) {
                        $success = importExpenses($csv);
                    } elseif(in_array($provision,array(11))) {
                        $mergeDuplicates = isset($_POST['mergeUsersIfDuplicate']) && $_POST['mergeUsersIfDuplicate'] === 'yes';
                        $success = changeEmailAddress($csv, $mergeDuplicates);
                    } elseif(in_array($provision,array(12))){
                        $success = deletUsers($csv,$primaryKey);
                    } elseif(in_array($provision,array(13))){
                        $success = addUpdateGroupTags($csv);
                    } elseif(in_array($provision,array(16))) {
                        $success = updateExternalEmailAddress($csv);
                    } elseif (in_array($provision,array(17))) {
                        $success = updateUserPreferences($csv,$primaryKey);
                    }
                } else {
                    $error = "Empty file";
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

function importUsersCsvData($data,$provision,$zoneid,$primaryKey){
   
    global $_COMPANY,$db;
    $response = ['totalProcessed'=>0,'totalSuccess'=>0,'userCreated'=>0,'memberCreated'=>0,'totalFailed'=>0,'groupLeadCreated'=>0,'chapterLeadCreated'=>0,'channelLeadCreated'=>0,'failed'=>[],'provision'=>$provision];
    if (count($data)){
        $failed = array();
        $userCreated = 0;
        $memberCreated = 0;
        $groupLeadCreated = 0;
        $chapterLeadCreated = 0;
        $channelLeadCreated= 0;
        $rowid = 0;
        $groupids_for_cache_expiration = array();
        $regionid = 0;

        foreach($data as $row){
            $rowid++;
            if ((($primaryKey==='externalId' && !empty($row['externalid'])) || ($primaryKey==='email')) &&
                isset($row['email'])
            ){
                $email = $row['email'];
                if ((!empty($email) && $_COMPANY->isValidEmail($email)) || (empty($email) && $primaryKey==='externalId')){
                    $firstname = $row['firstname'] ?? '';
                    $lastname = $row['lastname'] ?? '';
                    $pronouns = $row['pronouns'] ?? '';
                    if ($primaryKey == 'email'){
                        $externalId = null;
                        $userData = User::GetUserByEmail($email, true);
                        if (!$userData) {
                            $userData = User::CreateNewUser($firstname,$lastname,$email,'',User::USER_VERIFICATION_STATUS['VERIFIED']);
                            $userData->addUserZone($zoneid, true, false);
                        }

                    } else {
                        $externalId = $row['externalid'];
                        $userData = User::GetOrCreateUserByExternalId($externalId, $email, $firstname, $lastname,$zoneid);
                    }
                    
                    if ($userData){
                        $userCreated = $userCreated+1;
                        $jobTitle = $row['jobtitle'] ?? '';
                        $department = $row['department'] ?? '';
                        $officeLocation = $row['officelocation'] ?? '';
                        $opco = $row['opco'] ?? '';
                        $employeeType = $row['employeetype'] ?? '';
                        $externalUsername = $row['externalusername'] ?? '';
                        $employee_hire_date = $row['employeehiredate'] ?? null;
                        $employee_start_date = $row['employeestartdate'] ?? null;
                        $employee_termination_date = $row['employeeterminationdate'] ?? null;

                        // Update User Profile
                        $userData->updateProfile2($email, $firstname, $lastname, $pronouns, $jobTitle, $department, $officeLocation, '','','', '', $opco, $employeeType, $externalUsername, '', true, $employee_hire_date, $employee_start_date, $employee_termination_date);
                    } else {
                        array_unshift($row,$rowid.': Unable to get or create user');
                        array_push($failed,$row);
                        continue;
                    }

                    if (in_array($provision,array(1,2,3,4,14,15))) {
                        // Group/Channel/Chapter data
                        $chapterName = $row['chaptername'] ?? '';
                        $channelName = $row['channelname'] ?? '';
                        $roleName = $row['rolename'] ?? '';
                        $roleTitle = $row['roletitle'] ?? '';

                        // GET Group
                        $chapterid = 0;
                        $channelid = 0;
                        $leadType = array();
                        if (!empty($row['groupname']) && ($group = Group::GetOrCreateGroupByName($row['groupname']))) {
                            $groupids_for_cache_expiration[] = $group->id();
                            if ($roleName) {
                                $sys_leadtype = 2;

                                if ($provision == 3) {
                                    $sys_leadtype = 4;
                                } else if ($provision == 4) {
                                    $sys_leadtype = 5;
                                } else if ($provision == 14) {
                                    $sys_leadtype = 3;
                                } else if ($provision == 15) {
                                    $sys_leadtype = 1;
                                }
                                $leadType = Group::GetOrCreateGroupLeadTypeByType($roleName, $sys_leadtype);
                            }
                            $joinDate = '';
                            if (!empty($row['sincedatetime'])){
                                // UTC added for extra precaution just in case time zone was not provided.
                                $joinDate = gmdate("Y-m-d H:i:s", strtotime($row['sincedatetime'] . ' UTC'));
                            }

                            if ($provision == 1) { // By Group Member

                                if ($chapterName) {
                                    $chapterid = $group->getOrCreateChapterByName($chapterName, 0, 0);
                                }

                                if ($channelName) {
                                    $channelid = $group->getOrCreateChannelByName($channelName);
                                }
                                // Join Group/Chapter
                                $group->addOrUpdateGroupMemberByAssignment($userData->id(), $chapterid, $channelid,false,$joinDate);

                                $memberCreated = $memberCreated + 1;
                            } else if ($provision == 2 || $provision == 14 || $provision == 15) { // By Group Leads

                                if($provision === 14) {
                                    $regionid = $_COMPANY->getRegionByName($row['region'] ?? '');
                                    if (!$regionid) { // region not found
                                        // regional leader requires region
                                        array_unshift($row, $rowid . ': A valid region field is required to create regional lead');
                                        array_push($failed, $row);
                                        continue;
                                    }
                                }

                                if (!empty($leadType)) {
                                    $user = User::GetUser($userData->id());
                                    $group->addOrUpdateGroupLead($userData->id(), $leadType['typeid'], $roleTitle, $joinDate,$regionid);
                                    $group->addOrUpdateGroupMemberByAssignment($userData->id(), $chapterid, $channelid,false,$joinDate);
                                    $groupLeadCreated = $groupLeadCreated + 1;
                                } else {
                                    array_unshift($row, $rowid . ': Unable to get or create group leadtype');
                                    array_push($failed, $row);
                                }
                            } else if ($provision == 3) { // By Chapter Leads

                                if (!empty($leadType) && $leadType['systypeid'] == 4 && $chapterName) {
                                    $chapterid = $group->getOrCreateChapterByName($chapterName, 0, 0);
                                    $group->addChapterLead($chapterid, $userData->id(), $leadType['typeid'],$roleTitle, $joinDate);
                                    $group->addOrUpdateGroupMemberByAssignment($userData->id(), $chapterid, $channelid,false,$joinDate);
                                    $chapterLeadCreated = $chapterLeadCreated + 1;
                                } else {
                                    array_unshift($row, $rowid . ': Unable to get or create chapter leadtype');
                                    array_push($failed, $row);
                                }

                            } else if ($provision == 4) { // By Channel Leads

                                if (!empty($leadType) && $leadType['systypeid'] == 5 && $channelName) {
                                    $channelid = $group->getOrCreateChannelByName($channelName);
                                    $group->addChannelLead($channelid, $userData->id(), $leadType['typeid'],$roleTitle, $joinDate);
                                    $group->addOrUpdateGroupMemberByAssignment($userData->id(), $chapterid, $channelid,false,$joinDate);
                                    $channelLeadCreated = $channelLeadCreated + 1;
                                } else {
                                    array_unshift($row, $rowid . ': Unable to get or create channel leadtype');
                                    array_push($failed, $row);
                                }
                            }
                        } else {
                            array_unshift($row, $rowid . ': Unable to get or create group');
                            array_push($failed, $row);
                        }
                    }
                } else {
                    array_unshift($row,$rowid.': Invalid Email');
                    array_push($failed,$row);
                }
            } else{
                array_unshift($row,$rowid.': Missing required field');
                array_push($failed,$row);
            }
        }
        $response = ['totalProcessed'=>count($data),'totalSuccess'=>(count($data) - count($failed)),'userCreated'=>$userCreated,'memberCreated'=>$memberCreated,'groupLeadCreated'=>$groupLeadCreated,'chapterLeadCreated'=>$chapterLeadCreated,'channelLeadCreated'=>$channelLeadCreated,'totalFailed'=>count($failed),'failed'=>$failed,'provision'=>$provision];
    }

    // Expire redis cache for any touched groups
    foreach ($groupids_for_cache_expiration as $gid) {
        $_COMPANY->expireRedisCache("GRP:{$gid}");
        $_COMPANY->expireRedisCache("GRP_MEM_C:{$gid}");
    }

    return $response;
}

function importOfficeBranches($data){
    global $_COMPANY;
    $response = ['totalProcessed'=>0,'totalSuccess'=>0,'totalFailed'=>0,'failed'=>[],'provision'=>5];
    if (count($data)){
        $failed = array();

        $rowid = 0;
        foreach($data as $row){
            $rowid++;
            $region = isset($row['region']) ? ($row['region']) : '';
            $zipcode = isset($row['zipcode']) ? ($row['zipcode']) : '';
            $city = isset($row['city']) ? ($row['city']) : '';
            $state = isset($row['state']) ? ($row['state']) : '';
            $country = isset($row['country']) ? ($row['country']) : '';
            $branchType = isset($row['branchtype']) ? ($row['branchtype']) : '';
            $branchName = isset($row['officelocation']) ? ($row['officelocation']) : '';
            $street = isset($row['street']) ? ($row['street']) : '';

            $regionid = $_COMPANY->getOrCreateRegion__memoized($region);
            if ($branchName && $regionid){
                $_COMPANY->getOrCreateOrUpdateBranch__memoized($branchName, $city, $state, $country,$branchType,$regionid,$street,$zipcode);
            }else{
                array_unshift($row,$rowid.': Unable to get or create branch');
                array_push($failed,$row);
            }
        }
        $response = ['totalProcessed'=>count($data),'totalSuccess'=>(count($data) - count($failed)),'totalFailed'=>count($failed),'failed'=>$failed,'provision'=>5];

    }
    return $response;
}

function importChapterRegionAndBranches($data){
    global $_COMPANY;
    $response = ['totalProcessed'=>0,'totalSuccess'=>0,'totalGroups'=>0,'totalChapters'=>0,'totalRegions'=>0,'totalBranches'=>0,'totalFailed'=>0,'failed'=>[],'provision'=>6];
    if (count($data)){
        $failed = array();
        $totalGroups = 0;
        $totalChapters = 0;
        $totalRegions = 0;
        $totalBranches = 0;

        $rowid = 0;
        foreach($data as $row){
            $rowid++;
            if (isset($row['groupname']) && isset($row['chaptername']) && isset($row['region']) && isset($row['officelocation'])){
                $groupName = $row['groupname'];
                $chapterName = $row['chaptername'];
                $region = $row['region'];
                $officeLocation = $row['officelocation'];
                $branchType = isset($row['branchtype']) ? ($row['branchtype']) : '';
                $city = isset($row['city']) ? ($row['city']) : '';
                $state = isset($row['state']) ? ($row['state']) : '';
                $country = isset($row['country']) ? ($row['country']) : '';
                $zipcode = isset($row['zipcode']) ? ($row['zipcode']) : '';
                $street = isset($row['street']) ? ($row['street']) : '';
                $regionid = $_COMPANY->getOrCreateRegion__memoized($region);

                $group = Group::GetOrCreateGroupByName($groupName,$regionid);
                $chapterid = 0;

                if ($group){
                    $branch = $_COMPANY->getOrCreateOrUpdateBranch__memoized($officeLocation, $city, $state, $country,$branchType,$regionid,$street,$zipcode);
                    if ($group->getOrCreateChapterByName($chapterName,$regionid,$branch['branchid'])) {
                        $totalGroups = $totalGroups + 1;
                        $totalChapters = $totalChapters + 1;
                        $totalRegions = $totalRegions + 1;
                        $totalBranches = $totalBranches + 1;
                    } else {
                        array_unshift($row,$rowid.': Unable to get or create Chapter');
                        array_push($failed,$row);
                    }
                } else {
                    array_unshift($row,$rowid.': Unable to get or create Group');
                    array_push($failed,$row);
                }
            } else {
                array_unshift($row,$rowid.': Missing required field');
                array_push($failed,$row);
            }
        }
        $response = ['totalProcessed'=>count($data),'totalSuccess'=>(count($data) - count($failed)),'totalGroups'=>$totalGroups,'totalChapters'=>$totalChapters,'totalRegions'=>$totalRegions,'totalBranches'=>$totalBranches,'totalFailed'=>count($failed),'failed'=>$failed,'provision'=>6];

    }
    return $response;
}

function importEvents($data){
   
    global $_COMPANY, $_ZONE, $db;
    $response = ['totalProcessed'=>0,'totalSuccess'=>0,'eventCreated'=>0,'totalFailed'=>0,'failed'=>[],'provision'=>7];
    if (count($data)){
        $failed = array();
        $eventCreated = 0;
        $rowid = 0;
        foreach($data as $row){
            $rowid++;
            if ( 
                !empty($row['groupname']) &&
                !empty($row['startdatetime']) &&
                !empty($row['enddatetime']) &&
                !empty($row['eventtype']) &&
                !empty($row['eventtitle']) &&
                !empty($row['timezone']) &&
                isset($row['chaptername']) &&
                isset($row['channelname'])

            ){

                if (!isValidTimeZone($row['timezone'])) {
                    array_unshift($row,$rowid.': Invalid Timezone');
                    array_push($failed,$row);
                    continue;
                }

                $groupName = $row['groupname'];
                $startDateTime = $row['startdatetime'];
                $endDateTime = $row['enddatetime'];
                $eventType = $row['eventtype'];
                $eventTitle = $row['eventtitle'];
                $timezone = $row['timezone'];

                // strtotime will be capable of converting any date time format
                // gmdate will convert it back into GMT format
                $startDateTime = gmdate('Y-m-d H:i:s', strtotime($startDateTime.' '.$timezone));
                $endDateTime = gmdate('Y-m-d H:i:s', strtotime($endDateTime.' '.$timezone));

                if (strtolower($groupName) == 'global') {
                    $group = Group::GetGroup(0);
                } else {
                    $group = Group::GetOrCreateGroupByName($groupName,0,1);
                }


                if ($group){

                    $eventclass = 'event';
                    $teamid = 0;
                    $team = null;
                    if ($group->id() && !empty($row['teamname'])){
                        $team = Team::GetTeamByTeamName($group->id(),$row['teamname']);
                        if ($team){
                            $teamid  = $team->id();
                            $eventclass = 'teamevent';
                        } else {
                            array_unshift($row, $rowid . ':  Team not found or more than one teams with same name found');
                            array_push($failed, $row);
                            continue;
                        }
                    }

                    $type = $db->get("SELECT `typeid`, `type` FROM `event_type` WHERE `companyid`={$_COMPANY->id()} AND ((`zoneid`={$_ZONE->id()} OR `zoneid`=0) AND `isactive`=1 AND `type`='{$eventType}' ) ");
                    
                    if (count($type)){
                        $eventtype = $type[0]['typeid'];
                       
                    } else {
                        array_unshift($row, $rowid . ':  Event type not found');
                        array_push($failed, $row);
                        continue;
                        
                    }

                    $chapterName = $row['chaptername'] ?? '';
                    $channelName = $row['channelname'] ?? '';
                    $eventDescription = $row['eventdescription'] ?? '';
                    $eventvanue = $row['venue'] ?? '';
                    $venueAddress = $row['venueaddress'] ?? '';
                    $webConferenceLink = $row['webconferencelink'] ?? '';
                    $webConferenceDetail = $row['webconferencedetail'] ?? '';
                    $isPublished = $row['ispublished'] ?? 'No';
                    $isPublished = strtolower($isPublished);
                    $isPublished = ($isPublished == 1 || $isPublished === 'true' || $isPublished === 'yes');
                    $event_contact = $row['eventcontact'] ?? '';
                    $isprivate = filter_var($row['isprivate'], FILTER_VALIDATE_BOOLEAN);

                    $event_attendence_type = 1;
                    if ($eventvanue=='' && $webConferenceLink) {
                        $event_attendence_type = 2;
                    } else if($eventvanue && $webConferenceLink) {
                        $event_attendence_type = 3;
                    } else if ( $eventvanue=='' && $webConferenceLink == ''){
                        $event_attendence_type = 4;
                    }
                    $chapterid = 0;
                    $channelid = 0;

                    if ($chapterName && $group->id()){
                        $chapterid = $group->getOrCreateChapterByName($chapterName,0,0,1);
                        if (!$chapterid){
                            array_unshift($row, $rowid . ':  Chapter not found');
                            array_push($failed, $row);
                            continue;
                        }
                    }

                    if ($channelName && $group->id()){
                        $channelid  = $group->getOrCreateChannelByName($channelName,1);
                        if (!$channelid){
                            array_unshift($row, $rowid . ':  Channel not found');
                            array_push($failed, $row);
                            continue;
                        }
                    }

                    $invited_groups = '';
                    $max_inperson = '0';
                    $max_online = '0';
                    $max_inperson_wl = '0';
                    $max_online_wl = '0';
                    $web_conference_sp = '';
                    $checkin_enabled = '0';
                    $collaborate = '';
                    $event_series_id = 0;

                    $groupid = $group->id();

                    $group2 = !empty($row['groupname2']) ? Group::GetGroupByNameAndZoneId($row['groupname2'], $_ZONE->id()) : null;
                    $group3 = !empty($row['groupname3']) ? Group::GetGroupByNameAndZoneId($row['groupname3'], $_ZONE->id()) : null;
                    if ($group2 || $group3) {
                        $collaborate = $groupid;
                        $groupid = 0; // Reset groupid
                        if ($group2) {
                            $collaborate .= ',' . $group2->id();
                        }
                        if ($group3) {
                            $collaborate .= ',' . $group3->id();
                        }
                    }

                    // Note: Importing custom fields requires the input is already constructed as JSON with appropriate ID's.
                    // Requires expert attention.
                    $custom_fields_input = json_encode(array());
                    if (isset($row['custom_fields'])) {
                        $custom_fields_input = json_encode(json_decode($row['custom_fields'], true) ?? '') ?: json_encode(array());
                    }
                    $venue_info = '';
                    $venue_room = '';

                    // Create Event
                    $eventid = Event::CreateNewEvent($groupid, $chapterid, $eventTitle, $startDateTime, $endDateTime, $timezone, $eventvanue, $venueAddress, $eventDescription, $eventtype, $invited_groups, $max_inperson, $max_inperson_wl, $max_online, $max_online_wl, $event_attendence_type, $webConferenceLink, $webConferenceDetail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $event_series_id, $custom_fields_input, $event_contact,  $venue_info, $venue_room,$isprivate, $eventclass);

                    if ($eventid){
                        $event = Event::GetEvent($eventid);
                        $event->updateRsvpListSetting($_COMPANY->getAppCustomization()['event']['rsvp_display']['default_value'] ?? 2);
                        if ($isPublished) {
                            $event->activateEvent();
                        }
                        if ($team){
                            $touchpointid = $team->addOrUpdateTeamTask(0, $eventTitle, 0, $endDateTime, $eventDescription, 'touchpoint', 0);
                            $event->linkTeamidOnEvent($teamid);
                            $team->linkTouchpointEventId($touchpointid, $eventid, $endDateTime);
                        }
                        $eventCreated = $eventCreated+1;
                    } else {
                        array_unshift($row, $rowid . ': Unable to get or create group');
                        array_push($failed, $row);
                        }
                } else {
                    array_unshift($row, $rowid . ':  Group not found');
                    array_push($failed, $row);
                }
                    
               
            } else{
                array_unshift($row,$rowid.': Missing required field');
                array_push($failed,$row);
            }
        }
        $response = ['totalProcessed'=>count($data),'totalSuccess'=>(count($data) - count($failed)),'eventCreated'=>$eventCreated,'totalFailed'=>count($failed),'failed'=>$failed,'provision'=>7];
    }
    return $response;
}

function updateExternalids($data) {

    $response = ['totalProcessed'=>0,'totalSuccess'=>0,'updated'=>0,'totalFailed'=>0,'failed'=>[],'provision'=>7];
    if (count($data)){
        $failed = array();
        $rowid = 0;
        $rowsUpdated = 0;
        foreach($data as $row){
            $rowid++;
            if (
                !empty($row['email']) &&
                !empty($row['externalid'])
            ){
                $externalId = $row['externalid'];
                $email = $row['email'];

                $u = User::GetUserByEmail($email, true);
                if ($u){
                    if ($externalId === $u->getExternalId()) {
                        // Skip as the value is already set correctly.
                    } else {
                        if ($u->updateExternalId($externalId)) {
                            $rowsUpdated++;
                        } else {
                            array_unshift($row, $rowid . ': Error updating externalid');
                            array_push($failed, $row);
                        }
                    }
                } else {
                    array_unshift($row, $rowid . ':  Email not found');
                    array_push($failed, $row);
                }
            } else{
                array_unshift($row,$rowid.': Missing required field');
                array_push($failed,$row);
            }
        }
        $response = ['totalProcessed'=>count($data),'totalSuccess'=>(count($data) - count($failed)),'rowsUpdated'=>$rowsUpdated,'totalFailed'=>count($failed),'failed'=>$failed,'provision'=>8];
    }
    return $response;
}

function importExpenses($data){

    global $_COMPANY, $_ZONE, $db;
    $channelMetaName = $_COMPANY->getAppCustomization()['channel']['name-short'] ?? '';
    $expensesCreated = 0;
    $response = ['totalProcessed'=>0,'totalSuccess'=>0,'expensesCreated'=>0,'totalFailed'=>0,'failed'=>[],'provision'=>10];
    if (count($data)){
        $failed = array();
        $eventCreated = 0;
        $rowid = 0;
        foreach($data as $row){
            $rowid++;
            if (
                !empty($row['groupname']) &&
//                isset($row['chaptername']) &&
//                isset($row['channelname']) &&
                !empty($row['amount']) &&
                !empty($row['date']) &&
//                isset($row['chargecode']) &&
                !empty($row['description'])

            ){
                $groupName = trim($row['groupname']);
                $chapterName = trim($row['chaptername'] ?? '');
                $channelName = trim($row['channelname'] ?? '');
                $expenseAmount = floatval(preg_replace('/[^\d\.-]+/', '', $row['amount']));
                $expenseDate = trim($row['date']);
                $expenseChargeCode = trim(raw2clean($row['chargecode'] ?? ''));
                $expenseDescription = trim(raw2clean($row['description']));
                $expenseVendorName = trim(raw2clean($row['vendorname'] ?? ''));
                $expenseIsPaid = filter_var($row['ispaid'], FILTER_VALIDATE_BOOLEAN);
                $expensePoNumber = trim(raw2clean($row['ponumber'] ?? ''));
                $expenseInvoiceNumber = trim(raw2clean($row['invoicenumber'] ?? ''));

                // need expenseDate in Y-m-d format
                // Use GMT for calculation as our goal is to just change the date format to Y-m-d
                $expenseDate = gmdate('Y-m-d', strtotime($expenseDate.' GMT'));

                $group = Group::GetOrCreateGroupByName($groupName,0,1);
                if ($group){

                    $chapterid = 0;
                    $channelid = 0;
                    $groupid = $group->id();


                    $budgetYearId = Budget2::GetBudgetYearIdByDate($expenseDate);
                    if (!$budgetYearId){
                        array_unshift($row, $rowid . ':  Budget Year not found');
                        array_push($failed, $row);
                        continue;
                    }
                    $expenseChargeCodeId = 0;
                    if ($expenseChargeCode ) {
                        $expenseChargeCodeId = addOrCreateBudgetChargeCode($expenseChargeCode);
                        if (!$expenseChargeCodeId){
                            array_unshift($row, $rowid . ':  Charge Code not found');
                            array_push($failed, $row);
                            continue;
                        }
                    }

                    if ($chapterName ){
                        $chapterid = $group->getOrCreateChapterByName($chapterName,0,0,1);
                        if (!$chapterid){
                            array_unshift($row, $rowid . ':  Chapter not found');
                            array_push($failed, $row);
                            continue;
                        }
                    }

                    if ($channelName && $channelMetaName != ''){
                        $channelid  = $group->getOrCreateChannelByName($channelName,1);
                        if (!$channelid){
                            array_unshift($row, $rowid . ':  Channel not found');
                            array_push($failed, $row);
                            continue;
                        } else {
                            // Append clean version  to description
                            $channelName = raw2clean($channelName);
                            $expenseDescription .= " [{$channelMetaName} = {$channelName}]";
                        }
                    }
                    $usesid  = Budget2::AddOrUpdateBudgetUse(0, $groupid, $chapterid, $expenseAmount, 0, $expenseDescription, $expenseDate, $budgetYearId, '', $expenseChargeCodeId, $expenseVendorName, 0,'', 'allocated_budget', $expenseIsPaid, $expensePoNumber, $expenseInvoiceNumber);

                    if ($usesid) {
                        $expensesCreated++;
                    } else {
                        array_unshift($row, $rowid . ':  Entry creation failed');
                        array_push($failed, $row);
                    }
                } else {
                    array_unshift($row, $rowid . ':  Group not found');
                    array_push($failed, $row);
                }
            } else{
                array_unshift($row,$rowid.': Missing required field');
                array_push($failed,$row);
            }
        }
        $response = ['totalProcessed'=>count($data),'totalSuccess'=>(count($data) - count($failed)),'expensesCreated'=>$expensesCreated,'totalFailed'=>count($failed),'failed'=>$failed,'provision'=>10];
    }
    return $response;
}

function addOrCreateBudgetChargeCode($chargeCode):int {
    global $_COMPANY;
    global $_ZONE;
    global $_USER;
    global $db;

    $rows = $db->get("SELECT charge_code_id FROM budget_charge_codes WHERE `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()} AND (`charge_code`='{$chargeCode}')");

    if (empty($rows)) {
        return Budget2::AddOrUpdateChargeCodes(0, $chargeCode);
    } else {
        return (int)$rows[0]['charge_code_id'];
    }
}

function changeEmailAddress($data, bool $mergeUsers)
{
    global $_COMPANY;
    global $_ZONE;
    global $_USER;
    global $db;

    $response = ['totalProcessed'=>0,'totalSuccess'=>0,'rowsUpdated'=>0,'totalFailed'=>0,'rowsMerged'=>0,'failed'=>[],'updated'=>[],'merged'=>[], 'provision'=>11];
    if (count($data)){
        $failed = array();
        $updated = array();
        $merged = array();
        $rowid = 0;
        $rowsUpdated = 0;
        $rowsMerged = 0;
        foreach($data as $row){
            $rowid++;
            if (
                !empty($row['oldemailaddress']) &&
                !empty($row['newemailaddress'])
            ){
                $oldEmailAddress = raw2clean($row['oldemailaddress']);
                $newEmailAddress = raw2clean($row['newemailaddress']);

                if (!$_COMPANY->isValidEmail($oldEmailAddress)) {
                    array_unshift($row, $rowid . ':  Old email address is not compliant with company email domains allow list');
                    array_push($failed, $row);
                    continue;
                }

                if (!$_COMPANY->isValidEmail($newEmailAddress)) {
                    array_unshift($row, $rowid . ':  New email address is not compliant with company email domains allow list');
                    array_push($failed, $row);
                    continue;
                }

                if ($oldEmailAddress == $newEmailAddress) {
                    array_unshift($row, $rowid . ':  Skipping new and old email address is same');
                    array_push($failed, $row);
                    continue;
                }

                $oldUserByEmail = User::GetUserByEmail($oldEmailAddress, true);
                $oldUserByExternalId = User::GetUserByExternalId($oldEmailAddress, true); // Sometimes email address is also used as externalid, if so we will update external id as well.
                if ($oldUserByEmail){
                    if ($oldUserByExternalId && ($oldUserByExternalId->id() != $oldUserByEmail->id())) {
                        array_unshift($row, $rowid . ':  User with old email address exists and there is also another user with externalid derived from old email address.');
                        array_push($failed, $row);
                        continue;
                    }
                    $newUserByEmail = User::GetUserByEmail($newEmailAddress, true);
                    $newUserByExternalId = User::GetUserByExternalId($newEmailAddress, true);
                    if ($newUserByEmail) {
                        if ($newUserByExternalId && ($newUserByExternalId->id() != $newUserByEmail->id())) {
                            array_unshift($row, $rowid . ':  User with new email address exists and there is also another user with externalid derived from new email address.');
                            array_push($failed, $row);
                            continue;
                        }
                        if (!$mergeUsers) {
                            array_unshift($row, $rowid . ':  User merge of old email address and new email address is required but the setting is off.');
                            array_push($failed, $row);
                            continue;
                        } else {
                            Logger::Log("Bulk Provisioning merging user identified with userid={$oldUserByEmail->id()}, email='{$oldUserByEmail->val('email')}' externalid={$oldUserByEmail->val('externalid')}, externalusername={$oldUserByEmail->val('externalusername')}", Logger::SEVERITY['INFO']);
                            // Prepare the user for merge by resetting the old user
                            if ($newUserByExternalId && $oldUserByExternalId) {
                                // remove external id that was derived from old email of the old user... otherwise we will not be able to merge the users
                                $oldUserByExternalId->updateExternalId(null);
                            }
                            if ($oldUserByEmail->val('externalusername') == $oldEmailAddress ) {
                                $oldUserByEmail->updateExternalUsername(null);
                            }
                            $oldUserByEmail->updateEmail(null);
                            $retVal = User::MergeUsers($newUserByEmail->id(),$oldUserByEmail->id());
                            if ($retVal['status'] == 1) {
                                $rowsMerged++;
                                $merged[] = $row;
                                continue;
                            } else {
                                array_unshift($row, $rowid . ':  Unable to update email address, error '. $retVal['message']);
                                array_push($failed, $row);
                                continue;
                            }
                        }
                    } elseif ($newUserByExternalId) {
                        array_unshift($row, $rowid . ':  User with new email address not found but a user with externalid derived from new email address was found');
                        array_push($failed, $row);
                        continue;
                    } else {
                        // Simple update ... just update email (and external id directly if needed)
                        $retVal = $oldUserByEmail->updateEmail($newEmailAddress);

                        if ($retVal && $oldUserByExternalId) {
                            // First set the externalid to null in order to change it.
                            $retVal = $oldUserByEmail->updateExternalId(null) && $oldUserByEmail->updateExternalId($newEmailAddress);
                        }

                        if ($retVal && $oldUserByEmail->val('externalusername') === $oldEmailAddress) {
                            $retVal = $oldUserByEmail->updateExternalUsername($newEmailAddress);
                        }

                        if ($retVal) {
                            $rowsUpdated++;
                            $updated[] = $row;
                            continue;
                        } else {
                            array_unshift($row, $rowid . ':  Unable to update email address or other attributes... unknown error');
                            array_push($failed, $row);
                            continue;
                        }
                    }
                } else {
                    array_unshift($row, $rowid . ':  Old Email Address not found');
                    array_push($failed, $row);
                }
            } else{
                array_unshift($row,$rowid.': Missing required field');
                array_push($failed,$row);
            }
        }
        $response = ['totalProcessed'=>count($data),'totalSuccess'=>(count($data) - count($failed)),'rowsUpdated'=>$rowsUpdated,'rowsMerged'=>$rowsMerged,'totalFailed'=>count($failed),'failed'=>$failed,'updated'=>$updated,'merged'=>$merged,'provision'=>11];
    }
    foreach ($response['failed'] as $r) Logger::Log("Bulk Provisiong: Failed changing {$r['oldemailaddress']} to {$r['newemailaddress']}");
    foreach ($response['merged'] as $r) Logger::Log("Bulk Provisiong: Merged {$r['oldemailaddress']} and {$r['newemailaddress']}", Logger::SEVERITY['INFO']);
    foreach ($response['updated'] as $r) Logger::Log("Bulk Provisiong: Updated {$r['oldemailaddress']} to {$r['newemailaddress']}", Logger::SEVERITY['INFO']);
    return $response;
}

function updateExternalEmailAddress($data)
{
    global $_COMPANY;
    global $_ZONE;
    global $_USER;
    global $db;

    $response = ['totalProcessed'=>0,'totalSuccess'=>0,'rowsUpdated'=>0,'totalFailed'=>0,'rowsMerged'=>0,'failed'=>[],'updated'=>[],'merged'=>[], 'provision'=>11];
    if (count($data)){
        $failed = array();
        $updated = array();
        $merged = array();
        $rowid = 0;
        $rowsUpdated = 0;
        $rowsMerged = 0;
        foreach($data as $row){
            $rowid++;
            if (
                !empty($row['externalid']) &&
                !empty($row['externalemail'])
            ){
                $externalId = raw2clean($row['externalid']);
                $externalEmail = raw2clean($row['externalemail']);

                if ($_COMPANY->isValidEmail($externalEmail)) {
                    array_unshift($row, $rowid . ':  External email cannot be a company email address');
                    array_push($failed, $row);
                    continue;
                }

                $externalUserByExternalId = User::GetUserByExternalId($externalId, true);
                if (!$externalUserByExternalId) {
                    array_unshift($row, $rowid . ':  User not found');
                    array_push($failed, $row);
                    continue;
                }

                if ($_COMPANY->isValidAndRoutableEmail($externalUserByExternalId->val('email'))) {
                    array_unshift($row, $rowid . ':  The user already has a routable company email');
                    array_push($failed, $row);
                    continue;
                }

                $externalUserByExternalEmail = User::GetUserByExternalEmail($externalEmail, true);
                if ($externalUserByExternalEmail) {
                    if ($externalUserByExternalId->id() != $externalUserByExternalEmail->id()) {
                        array_unshift($row, $rowid . ':  The external email is already assigned to another user');
                        array_push($failed, $row);
                        continue;
                    }
                    continue; // Users external email is already set correctly.
                }

                $retVal = $externalUserByExternalId->updateExternalEmailAddress($externalEmail);
                if ($retVal) {
                    $rowsUpdated++;
                    $updated[] = $row;
                    continue;
                } else {
                    array_unshift($row, $rowid . ':  Unable to update email address or other attributes... unknown error');
                    array_push($failed, $row);
                    continue;
                }
            } else{
                array_unshift($row,$rowid.': Missing required field');
                array_push($failed,$row);
            }
        }
        $response = ['totalProcessed'=>count($data),'totalSuccess'=>(count($data) - count($failed)),'rowsUpdated'=>$rowsUpdated,'rowsMerged'=>$rowsMerged,'totalFailed'=>count($failed),'failed'=>$failed,'updated'=>$updated,'merged'=>$merged,'provision'=>11];
    }
    return $response;
}

function updateUserPreferences($data, $primaryKey)
{
    global $_COMPANY;
    $response = ['totalProcessed'=>0,'totalSuccess'=>0,'totalFailed'=>0,'failed'=>[],'provision'=>17];
    if (count($data)){
        $failed = array();
        $rowid = 0;
        foreach($data as $row){
            $rowid++;
            $userToUpdate = null;
            if ($primaryKey == 'email'){
                $emailAddress = raw2clean($row['email']??'');
                $userToUpdate = User::GetUserByEmail($emailAddress, true);

            } elseif($primaryKey == 'externalId'){
                $externalId = raw2clean($row['externalid']??'');
                $userToUpdate = User::GetUserByExternalId($externalId, true);
            }
            if ($userToUpdate){
                $preference_set = array();
                $preference_failed = array();
                foreach (UserPreferenceType::cases() as $preferenceType) {
                    $preferenceTypeName = strtolower($preferenceType->name);
                    if (isset($row[$preferenceTypeName]) && !empty($row[$preferenceTypeName])) {
                        $retVal = $userToUpdate->setUserPreference($preferenceType, $row[$preferenceTypeName]);
                        if ($retVal) {
                            $preference_set[] = $preferenceTypeName;
                        } else {
                            $preference_failed[] = $preferenceTypeName;
                        }
                    }
                }
                if (empty($preference_set)) {
                    array_unshift($row,$rowid.': No matching preferences found ');
                    array_push($failed,$row);
                } elseif (!empty($preference_failed)) {
                    array_unshift($row,$rowid.': Unable to set ' . implode(',', $preference_failed));
                    array_push($failed,$row);
                }
            }else{
                array_unshift($row,$rowid.': Unable to get user by '.$primaryKey);
                array_push($failed,$row);
            }
        }
        $response = ['totalProcessed'=>count($data),'totalSuccess'=>(count($data) - count($failed)),'totalFailed'=>count($failed),'failed'=>$failed,'provision'=>12];

    }
    return $response;

}

function printArrayTable ($data, $title)
{
    $keys =  array_keys($data[0]);
    $tbl = '<div class="table-responsive p-1" style="border:1px solid rgb(192, 189, 189);">';
    $tbl .= "<h5> {$title}</h5>";
    $tbl .= '<table id="table" class="table table-hover display compact">';
    $tbl .= '<thead>';
    $tbl .= '<tr>';

    foreach($keys as $key){
        $tbl .= '<th>' . ($key?:'-') . '</th>';
    }

    $tbl .= '</tr>';
    $tbl .= '</thead>';
    $tbl .= '<tbody>';

    foreach($data as $row) {
        $tbl .= '<tr>';
        foreach ($row as $val) {
            $tbl .= "<td >{$val}</td>";
        }
        $tbl .= '</tr>';
    }
    $tbl .= '</tbody>';
    $tbl .= '</table>';
    $tbl .= '</div>';
    return $tbl;
}

function deletUsers($data,$primaryKey){

    global $_COMPANY;
    $response = ['totalProcessed'=>0,'totalSuccess'=>0,'totalFailed'=>0,'failed'=>[],'provision'=>12];
    if (count($data)){
        $failed = array();
        $rowid = 0;
        foreach($data as $row){
            $rowid++;
            $userToDelete = null;
            if ($primaryKey == 'email'){
                $emailAddress = raw2clean($row['email']??'');
                $userToDelete = User::GetUserByEmail($emailAddress, true);
                   
            } elseif($primaryKey == 'externalId'){
                $externalId = raw2clean($row['externalid']??'');
                $userToDelete = User::GetUserByExternalId($externalId, true);
            } 
            if ($userToDelete){
                $userToDelete->purge();
            }else{
                array_unshift($row,$rowid.': Unable to get user by '.$primaryKey);
                array_push($failed,$row);
            }
        }
        $response = ['totalProcessed'=>count($data),'totalSuccess'=>(count($data) - count($failed)),'totalFailed'=>count($failed),'failed'=>$failed,'provision'=>12];

    }
    return $response;

}

function addUpdateGroupTags($data){

    global $_COMPANY,$_ZONE;
    $response = ['totalProcessed'=>0,'totalSuccess'=>0,'totalFailed'=>0,'failed'=>[],'provision'=>13];
    if (count($data)){
        $failed = array();
        $rowid = 0;
        foreach($data as $row){
            $rowid++;

            $groupName = trim($row['groupname']);
            $addTags = trim($row['addtags']);
            $deleteTags =trim($row['deletetags']);
            $setTags = trim($row['settags']);
            $group = Group::GetOrCreateGroupByName($groupName,0,1);
            if ($group){
                if (!empty($setTags)){ 
                    $setTags = explode(',',$setTags);
                    $tagids = Group::GetOrCreateTagidsArrayByTag($setTags);
                    $tagids = implode(',',$tagids)?:'0';
                   
                } else {
                    $addTags = explode(',',$addTags);
                    $deleteTags = explode(',',$deleteTags);
                    $existingTagIds  = explode(',',$group->val("tagids"));
                    $newTagids = Group::GetOrCreateTagidsArrayByTag($addTags);
                    $uniqueTagIds = array_unique(array_merge($existingTagIds, $newTagids));
                    $deleteTagids = Group::GetOrCreateTagidsArrayByTag($deleteTags);
                    $tagIdsToUpdate = array_diff($uniqueTagIds,$deleteTagids);
                    $tagids = implode(',',$tagIdsToUpdate)?:'0';
                }
                $update = $group->updateGroupTags($tagids);
                if (!$update) {
                    array_unshift($row,$rowid.': Unable to get/create group');
                    array_push($failed,$row);
                }
            }else{
                array_unshift($row,$rowid.': Unable to update tags');
                array_push($failed,$row);
            }
        }
        $response = ['totalProcessed'=>count($data),'totalSuccess'=>(count($data) - count($failed)),'totalFailed'=>count($failed),'failed'=>$failed,'provision'=>13];

    }
    return $response;

}


include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/bulk_provisioning.html');
