<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__ .'/head.php'; //Common head.php includes destroying Session for timezone so defining INDEX_PAGE. Need head for logging and protecting

global $_COMPANY, $_ZONE, $_USER;
global $db;
///ajax_budget

## Request Budget
if (isset($_GET['requestBudget']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    // Module check
    if (!$_COMPANY->getAppCustomization()['budgets']['enabled'] || !$_COMPANY->getAppCustomization()['budgets']['enable_budget_requests']) {
        Http::Forbidden('Module disabled');
    }

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['requestBudget']))<1 ||
        ($group = Group::GetGroup($groupid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $chapterid = (int) ($_COMPANY->decodeId($_POST['chapterid']) ?? 0);
    // Authorization Check
    if (!$_USER->canManageBudgetInScope($groupid,$chapterid,0)){
        header(HTTP_FORBIDDEN);
        exit();
    }
	$request_id = $_COMPANY->decodeId($_GET['request_id']);

    $requested_amount	= (float)($_POST['requested_amount']);
	$purpose	= raw2clean($_POST['purpose']);
	$need_by	= raw2clean($_POST['need_by']);
	$description= raw2clean($_POST['description']);

    if ( empty($need_by)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Budget Use Date cannot be empty.'), gettext('Error'));
    }

	$budget_year_id = Budget2::GetBudgetYearIdByDate($need_by);

    if ($chapterid){ // If Chapter level budget
        $budgetObject = Budget2::GetBudget($budget_year_id,$groupid,0);
        $admins = $group->getWhoCanManageGroupBudget();
        $personName = $group->val('groupname') . ' ' . $_COMPANY->getAppCustomization()['group']['name-short'];
        // Since we changed the budget movement behavior from parent to child from automatic to user defined, we no
        // longer need this check
        //if ($requested_amount > $budgetObject->getTotalBudget()){
        //    AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('There is not enough budget available in %s. Please contact your Global Administrator or Zone Administrator in order to change the budget allocation or request a new budget.'),$_COMPANY->getAppCustomization()['group']["name-short"]), gettext('Error'));
        //}
    } else {
        $admins = User::GetAllZoneAdminsWhoCanManageZoneBudget();
        $personName = $_ZONE->val('zonename') . ' Zone';
    }

    if ($requested_amount< 0.01){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Amount cannot be zero or a negative number.'), gettext('Error'));
    }

    if (!$budget_year_id){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Budget use date cannot be outside one of the available budget periods.'), gettext('Error'));
    }
    $check = $db->checkRequired(array('Amount '=>@$requested_amount,'Purpose'=>@$purpose));

	if ($check){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('%s cannot be empty!'),$check), gettext('Error'));
	} else {
        $groupname = Group::GetGroupName($groupid);
        $chapterName = ($chapterid) ? Group::GetChapterName($chapterid, $groupid)['chaptername'] : '';
        
        $custom_fields = ViewHelper::ValidateAndExtractCustomFieldsFromPostAttribute('BRQ');

        $newBudgetRequestId = Budget2::CreateOrUpdateBudgetRequest($request_id, $groupid, $requested_amount, $purpose, $need_by, $description, $chapterid, $custom_fields);

        $budget_request = BudgetRequest::GetBudgetRequest($newBudgetRequestId);

        if (!empty($_POST['ephemeral_topic_id'])) {
            $ephemeral_topic_id = $_COMPANY->decodeId($_POST['ephemeral_topic_id']);
            $ephemeral_topic = EphemeralTopic::GetEphemeralTopic($ephemeral_topic_id);

            $budget_request->moveAttachmentsFrom($ephemeral_topic);
        }

        $app_type = $_ZONE->val('app_type');
        $reply_addr = $group->val('replyto_email');
        $from = $group->val('from_email_label') . ' Budget Request';
        $username = $_USER->getFullName();
        $requested_amount = $_COMPANY->getCurrencySymbol().$_USER->formatAmountForDisplay($requested_amount);
        $email = implode(',',array_column($admins,'email'));

        // Checking the request if it is update or a new one
        if($request_id > 0){
            if ($chapterid) {
                $temp = EmailHelper::ChapterBudgetRequestUpdate($chapterName, $groupid, $groupname, $personName, $username, $requested_amount, $purpose, $need_by, $description, $budget_request);
            } else {
                $temp = EmailHelper::GroupBudgetRequestUpdate($groupname, $personName, $username, $requested_amount, $purpose, $need_by, $description, $budget_request);
            }
        }else{
            if ($chapterid) {
                $temp = EmailHelper::ChapterBudgetRequest($chapterName, $groupid, $groupname, $personName, $username, $requested_amount, $purpose, $need_by, $description, $budget_request);
            } else {
                $temp = EmailHelper::GroupBudgetRequest($groupname, $personName, $username, $requested_amount, $purpose, $need_by, $description, $budget_request);
            }
        }

        $_COMPANY->emailSend2($from, $email, $temp['subject'], $temp['message'], $app_type,$reply_addr);


        if ($request_id > 0){
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Budget request updated successfully."), gettext('Success'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(1, '', gettext("Budget request sent successfully."), gettext('Success'));
        }
	}
	exit();
}
## Delete Budget Request
elseif (isset($_GET['deleteBudgetRequest']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['deleteBudgetRequest']))<1 ||
        (!isset($_POST['id']) || ($request_id = $_COMPANY->decodeId($_POST['id']))<1)) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageBudgetInScope($groupid,0,0)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    Budget2::DeleteBudgetRequest($groupid,$request_id);
	echo 1;
}
## Archive Budget Request
elseif (isset($_GET['archiveBudgetRequest']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['archiveBudgetRequest']))<1 ||
        (!isset($_POST['id']) || ($request_id = $_COMPANY->decodeId($_POST['id']))<1)) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageBudgetInScope($groupid,0,0)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    Budget2::ArchiveBudgetRequest($groupid,$request_id);
	echo 1;
}
// Manage section
elseif(isset($_GET['managesection']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

	$encGroupId = $_GET['managesection'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<1 ||
	($group = Group::GetGroup($groupid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    if (!$_USER->canManageBudgetGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $encGroupId = $_COMPANY->encodeId($groupid);
    $budgetYears = Budget2::GetCompanyBudgetYears();
    if (empty($budgetYears)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('No budget year found configured yet. Please contact your administrator.'), gettext('Error'));
    }

    // Check if the budget_year is set in the session, if not set then set it to current year.
    if (empty(Session::GetInstance()->budget_year)) {
        Session::GetInstance()->budget_year = Budget2::GetBudgetYearIdByDate($_USER->getLocalDateNow());
    }
    $year = Session::GetInstance()->budget_year;
    if ($year <1 ) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('No budget year was found for today date, please create a current budget year or contact your administrator.'), gettext('Error'));
    }

    $channelid = 0;
    $chapterid = (int)($_SESSION['budget_by_chapter'] ?? 0);
    $chapters= Group::GetChapterList($groupid);

    // The following two if statement blocks basically check if the chapterid set in the session is valid. The usecase
    // it addresses is when the logged in used has session chapterid set to a chapter that it can no longer manage.
    // The first block resets the chapterid to 0 if session chapterid cannot be managed.
    // The second block resets the chapterid to something that the user can manage; if the usercannot manage the group
    if ($chapterid != 0) {
        // Check if the chapterid can be managed. There are usecases where chapterid maybe incorrectly set in the session
        foreach ($chapters as $ch) {
            if ($ch['chapterid'] == $chapterid) {
                if (!$_USER->canManageBudgetGroupChapter($groupid,$ch['regionids'],$ch['chapterid'])) {
                    $_SESSION['budget_by_chapter'] = 0;
                    $chapterid = 0; //
                    break;
                }
            }
        }
    }
    if ($chapterid == 0) {
        if (!$_USER->canManageBudgetGroup($groupid)) {
            // set the chapterid to some thing that the user can manage
            foreach ($chapters as $ch) {
                if ($_USER->canManageBudgetGroupChapter($groupid,$ch['regionids'],$ch['chapterid'])) {
                    $_SESSION['budget_by_chapter'] = $ch['chapterid'];
                    $chapterid = $ch['chapterid'];
                    break;
                }
            }
        }
    }
    $isChapterLeadOnly = false;
    $canManageChapterIds = '0';
    if ($chapterid && !$_USER->canManageBudgetGroup($groupid)) {
        $isChapterLeadOnly = true;
        $canManageChapterIds = array('0');
        foreach ($chapters as $ch) {
            if ($_USER->canManageBudgetGroupChapter($groupid,$ch['regionids'],$ch['chapterid'])) {
                $canManageChapterIds[] = $ch['chapterid'];
            }
        }
        $canManageChapterIds = implode(',',$canManageChapterIds);
    }

	## Budget Request
	$requests = Budget2::GetGroupBudgetRequests($groupid,$canManageChapterIds,5);

    //get $totalBudget, $usedBudget, $remainingBudget
    $budgetObject = Budget2::GetBudget($year,$groupid,$chapterid);
    $groupOtherFundTotal = Budget2::GetGroupTotalOtherFundingByBudgetYear($year, $groupid, $chapterid);
	$status = array('',gettext('Requested'),gettext('Approved'),gettext('Denied'));

    $budget_year_id = Session::GetInstance()->budget_year ?: Budget2::GetBudgetYearIdByDate($_USER->getLocalDateNow());

    $expenses = $budgetObject->getExpenseRows();

	include(__DIR__ . "/views/templates/manage_budget.template.php");
}

elseif(isset($_GET['addUpdateExpenseInfo']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['addUpdateExpenseInfo']))<1 ||
	    ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // ###
    // ### Caution: Special case $_ZONE is assigned for the remainder of this controller to group zoneid
    // ###
    // Zone check and assignment are required for expense entries,
    // as they can be added across zones in a collaborated event.
    $originalZoneId = $_ZONE->id();
    if ($group->val('zoneid') != $_ZONE->id()){
        $_ZONE = $_COMPANY->getZone($group->val('zoneid'));
    }

    $eventid	            =	!empty($_POST['event_id']) ? $_COMPANY->decodeId($_POST['event_id']) : 0;
    $event                  =   Event::GetEvent($eventid);
//     My Events submitter can update budget but only for budget associated with the event.
//    $is_my_events_submitter =   false;
//    if ($_COMPANY->getAppCustomization()['event']['budgets']
//        && ($_POST['calling_page'] ?? '') === 'my_events'
//        && $event ?-> val('userid') == $_USER->id()
//    ) {
//        $is_my_events_submitter = true;
//    }

    if (
            !$_USER->canManageBudgetGroupSomething($groupid)
            &&
            !$event?->loggedinUserCanManageEventBudget()
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }
   
    $encGroupId = $_COMPANY->encodeId($groupid);
    $usesid		=	(int)($_POST['usesid']);
    $date		=	$_POST['date'];
    $description=	$_POST['description'];
    $usedamount	=	(float)($_POST['usedamount']);
    $budgeted_amount	=	(float)(($_POST['budgeted_amount']) ?? 0);
    $eventtype		    =	$_POST['eventtype'];
    $funding_source		=	$_POST['funding_source'] ?? '';
    $charge_code_id		=	(int)$_POST['charge_code_id'];
    $is_paid		    =   filter_var($_POST['is_paid'] ?? '', FILTER_VALIDATE_BOOLEAN); // convert to boolean
    $po_number		    =	$_POST['po_number'] ?? '';
    $invoice_number     =   $_POST['invoice_number'] ?? '';

    $check = $db->checkRequired(array('Date'=>@$_POST['date'],'Description'=>@$_POST['description'], 'Chapter'=>@$_POST['chapterid']));
    if($check) {
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s cannot be empty"), $check), gettext('Error'));
    }
    $uses_chapterid =  $_COMPANY->decodeId($_POST['chapterid']);

        if ($budgeted_amount<0.0){
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Budgeted amount cannot be a negative number"), gettext('Error'));
        }
        // Allowing -ve number per customer request. -ve Numbers are use to show charge reversals
        //            if ($usedamount<0){
        //                echo "Expense amount cannot be a negative number";
        //                exit;
        //            }

        //            if ($budgeted_amount<0){
        //                echo "Budget amount cannot be a negative number";
        //                exit;
        //            }
        //
        //            if (isset($_POST['item'])){
        //				foreach($_POST['item_used_amount'] as $c){
        //                    if ($c<0){
        //                        echo "Sub item item_used_amount amount cannot be a negative number";
        //                        exit;
        //                    }
        //				}
        //			}

        $expense_budget_year_id = Budget2::GetBudgetYearIdByDate($date);
        if (!$expense_budget_year_id) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Expense date cannot be outside one of the available budget periods"), gettext('Error'));
        }
        $vendor_name = $_POST['vendor_name'] ?? '';
        $successMessage = gettext("Expense detail saved.");
        if ($usesid){
            $successMessage = gettext("Expense detail updated.");

            // If this expense item is for an apporoved budget item, then budget amount cannot be set to more than approved amount
            $usesBudgetRec = Budget2::GetBudgetRequestRecByUsesId($usesid);
            if (!empty($usesBudgetRec)) {
                $budgeted_amount	=	$usesBudgetRec['amount_approved'];
            }
        }

        // Validate Items are correctly set.
        [$ispaidinforeigncurrencies,$foreigncurrencies,$foreigncurrencyamounts, $currencyconversionrates] = ViewHelper::ValidateExpenseSubItemData($budgeted_amount,$usedamount);

        $custom_fields = ViewHelper::ValidateAndExtractCustomFieldsFromPostAttribute('EXP');

        $usesid =Budget2::AddOrUpdateBudgetUse($usesid, $groupid, $uses_chapterid, $usedamount, $budgeted_amount, $description, $date, $expense_budget_year_id, $eventtype, $charge_code_id, $vendor_name, $eventid,'', $funding_source, $is_paid, $po_number, $invoice_number, $custom_fields);
        if($usesid){
            $keepItemIds = array();
            ## Add sub items
            if (isset($_POST['item']) && !empty(array_filter($_POST['item']))){
                $item = $_POST['item'];
                $item_budgeted_amounts = $_POST['item_budgeted_amount'] ?? [];
                $item_used_amounts = $_POST['item_used_amount'];
                $expensetypeids = $_POST['expensetypeid'];
                $itemids = $_POST['itemids'];
                for($c=0;$c<count($item);$c++){
                    if(!empty($item[$c]) &&
                        (!empty(floatval($item_budgeted_amounts[$c])) || !empty(floatval($item_used_amounts[$c])))
                    ){
                        $itemid = $_COMPANY->decodeId($itemids[$c]);
                        $expensetypeid = $_COMPANY->decodeId($expensetypeids[$c]);
                        $itemname = $item[$c];
                        $item_budgetval = floatval($item_budgeted_amounts[$c]);
                        $item_costval = floatval($item_used_amounts[$c]);
                        $foreigncurrency = '';
                        $foreigncurrencyamount = 0;
                        $currencyconversionrate = 0;
                        if ($ispaidinforeigncurrencies[$c] && $foreigncurrencyamounts[$c] && $currencyconversionrates[$c]){
                            $foreigncurrency = $foreigncurrencies[$c];
                            $foreigncurrencyamount = $foreigncurrencyamounts[$c];
                            $currencystrreplace = str_replace(',', '', $currencyconversionrates[$c]);
                            $currencyconversionrate = floatval($currencystrreplace);
                        }
                        $keepItemIds[] = Budget2::AddOrUpdateBudgetUseItem($usesid, $itemid, $itemname, $item_budgetval, $item_costval, $expensetypeid,$foreigncurrency,$foreigncurrencyamount,$currencyconversionrate);
                    }
                }

            }

            // Finalize sub items; delete the dropped sub items
            Budget2::FinalizeSubitems($usesid, $keepItemIds);

            if (!empty($_POST['ephemeral_topic_id'])) {
                $ephemeral_topic_id = $_COMPANY->decodeId($_POST['ephemeral_topic_id']);
                $ephemeral_topic = EphemeralTopic::GetEphemeralTopic($ephemeral_topic_id);

                $expense_entry = ExpenseEntry::GetExpenseEntry($usesid);
                $expense_entry->moveAttachmentsFrom($ephemeral_topic);
            }

            AjaxResponse::SuccessAndExit_STRING(1, '', $successMessage, gettext('Success'));
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
        }

        exit();
}
// Load update expense data view
elseif (isset($_GET['addUpdateExpenseInfoModal']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['addUpdateExpenseInfoModal']))<1 || ($group = Group::GetGroup($groupid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $chapterid = !empty($_POST['chapter_id']) ? $_COMPANY->decodeId($_POST['chapter_id']): 0;

    // ###
    // ### Caution: Special case $_ZONE is temporarily assigned for the remainder of this controller to group zoneid
    // ###
    // Zone check and assignment are required for expense entries, as they can be added across zones in a collaborated event.
    $originalZoneId = $_ZONE->id();
    if ($group->val('zoneid') != $_ZONE->id()){
        $_ZONE = $_COMPANY->getZone($group->val('zoneid'));
    }
    $event = null;
    $isActionDisabledDuringApprovalProcess = false;
    if (!empty($_POST['event_id'])) {
        $event = Event::GetEvent($_COMPANY->decodeId($_POST['event_id']));

        // Events can have multiple expense entries - #4136
        $force_new_event_expense_entry = isset($_POST['force_new_event_expense_entry']) ? $_POST['force_new_event_expense_entry'] : false;
        if ($force_new_event_expense_entry){
            $_POST['id'] = $_COMPANY->encodeId(0);
        } else {
            $expense = $event->getEventBudgetedDetail(true, $groupid, $chapterid);
            $_POST['id'] = $_COMPANY->encodeId($expense['usesid'] ?? 0);
        }
    }

//    $is_my_events_submitter = false;
//    if ($_COMPANY->getAppCustomization()['event']['budgets']
//        && ($_POST['calling_page'] ?? '') === 'my_events'
//        && $event ?-> val('userid') == $_USER->id()
//    ) {
//        $is_my_events_submitter = true;
//    }

    if ((!isset($_POST['id']) || ($usesid = $_COMPANY->decodeId($_POST['id']))<0)) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check - if allow_grouplead_to_edit_expense is off then only admins can edit
    if (
        !$_USER->isAdmin()
        &&
        !($_COMPANY->getAppCustomization()['budgets']['allow_grouplead_to_edit_expense']) && $_USER->canManageBudgetGroupSomething($groupid)
        &&
        !$event?->loggedinUserCanManageEventBudget()
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $_SESSION['context_chapterid']  = $chapterid;
	$chapters= Group::GetChapterList($groupid);
    $type = Event::GetEventTypesByZones([$_ZONE->id()]);
    $charge_codes = Budget2::GetBudgetChargeCodes();
    $expense_type = Budget2::GetBudgetExpenseTypes();
    $allVendors = Budget2::GetUniqueVendorNames();  
    $allVendors = array_column($allVendors,'vendor_name');
    sort($allVendors);

    $custom_fields = ExpenseEntry::GetEventCustomFields();
    $event_custom_fields = [];

    if ($usesid ){
        $edit = Budget2::GetBudgetUse($usesid);
        $expense_entry = ExpenseEntry::Hydrate($usesid, $edit);

        if ($expense_entry->val('custom_fields')) {
            $event_custom_fields = json_decode($expense_entry->val('custom_fields'), true);
        }

        if ($edit){
            // when editing; force the event to be the one set in the budgetuses
            $event = $edit['eventid'] ? Event::GetEvent($edit['eventid']) : null;
            $sub = Budget2::GetBudgetUsesItems($usesid);
        }else{
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Data not found! Please try again.'), gettext('Error'));
        }
        $budget_year_id = $edit['budget_year_id'];
        $allowed_foreign_currencies = array();
        $budgetYear = Budget2::GetCompanyBudgetYearDetail($budget_year_id);
        if ($budgetYear['allowed_foreign_currencies']){
            $allowed_foreign_currencies = json_decode($budgetYear['allowed_foreign_currencies'],true);
        }

        $usesBudgetRec = Budget2::GetBudgetRequestRecByUsesId($usesid);

        if ($event) {
            $isActionDisabledDuringApprovalProcess = $event->isActionDisabledDuringApprovalProcess();
        }

        if (($_POST['view_expense_detail'] ?? '') === '1') {
            include(__DIR__ . '/views/templates/view_expense_detail.html.php');
        } else {
	        include(__DIR__ . "/views/templates/update_expense.template.php");
        }
    } else {
        $allowed_foreign_currencies = array();
        $budget_year_id = Budget2::GetBudgetYearIdByDate($_USER->getLocalDateNow());
        $budgetYear = Budget2::GetCompanyBudgetYearDetail($budget_year_id);
        if ($budgetYear['allowed_foreign_currencies']){
            $allowed_foreign_currencies = json_decode($budgetYear['allowed_foreign_currencies'],true);
        }

        if ($event) {
            $isActionDisabledDuringApprovalProcess = $event->isActionDisabledDuringApprovalProcess();
        }

        include(__DIR__ . "/views/templates/add_expense.template.php");
    }
    exit();
}

elseif (isset($_GET['getBudgetRequests']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['getBudgetRequests']))<1 ||
        ($group = Group::GetGroup($groupid)) === null
        ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageBudgetGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
	}

	$year = (int)($_GET['year'] ?? 0); // To prevent sql injection using year paramter;
    $chapterid = (int) ( $_COMPANY->decodeId($_GET['chapterid']) ?? 0); // To prevent sql injection using year paramter;
    $srch = raw2clean($_POST['search']['value']);
    $start = (int)$_POST['start'];
    $length = (int)$_POST['length'];
    $draw = (int)$_POST['draw'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
    $orderFields = ['budget_requests.request_id','budget_requests.request_date','budget_requests.purpose','budget_requests.requested_amount','budget_requests.amount_approved','budget_requests.request_status'];
    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderCriteria = empty($orderFields[$orderIndex]) ? '' : "order by {$orderFields[$orderIndex]} {$orderDir}";

    $search = "";
    if ($srch){
        $search = " AND ( users.firstname LIKE '%{$srch}%' OR users.lastname LIKE '%{$srch}%'  OR budget_requests.request_date LIKE '%{$srch}%'  OR budget_requests.purpose LIKE '%{$srch}%'  OR budget_requests.requested_amount LIKE '%{$srch}%' )";
   
    }

    $chapterCondition = "";
    if ($chapterid){
        $chapterCondition = " AND budget_requests.chapterid='{$chapterid}' ";
    }
    $budget_year = Budget2::GetCompanyBudgetYearDetail($year);
    $budget_year_start_date = ($budget_year) ? $budget_year['budget_year_start_date'] : '0000-00-00';
    $budget_year_end_date = ($budget_year) ? $budget_year['budget_year_end_date'] : '0000-00-00';

    // -- TODO--
    //Datatable dependencies 
    // Will removed if Tabulator 
    $requests_without_limit = $db->get("SELECT count(1) as totals FROM `budget_requests` LEFT JOIN users as r on r.userid= budget_requests.requested_by LEFT JOIN users on users.userid= budget_requests.approved_by LEFT JOIN chapters ON chapters.chapterid=budget_requests.chapterid WHERE budget_requests.companyid='{$_COMPANY->id()}' AND (budget_requests.`groupid`='{$groupid}' AND (budget_requests.need_by BETWEEN '{$budget_year_start_date}' AND '{$budget_year_end_date}') AND budget_requests.`is_active`='1' {$search} $chapterCondition) {$orderCriteria}")[0]['totals'];

    $requests = $db->get("SELECT budget_requests.*,r.firstname as requester_firstname,r.lastname as requester_lastname,r.picture as requester_picture, users.firstname,users.lastname,users.picture, chapters.chaptername FROM `budget_requests` LEFT JOIN users as r on r.userid= budget_requests.requested_by LEFT JOIN users on users.userid= budget_requests.approved_by LEFT JOIN chapters ON chapters.chapterid=budget_requests.chapterid WHERE budget_requests.companyid='{$_COMPANY->id()}' AND (budget_requests.`groupid`='{$groupid}' AND (budget_requests.need_by BETWEEN '{$budget_year_start_date}' AND '{$budget_year_end_date}') AND budget_requests.`is_active`='1' {$search} $chapterCondition) {$orderCriteria} LIMIT {$start},{$length}");
	$status = array('',gettext('Requested'),gettext('Approved'),gettext('Denied'));
	$final = [];

	for($br=0;$br<count($requests);$br++){
        $budget_request = BudgetRequest::Hydrate($requests[$br]['request_id'], $requests[$br]);
		$request_id = $_COMPANY->encodeId($requests[$br]['request_id']);
		$groupid = $_COMPANY->encodeId($requests[$br]['groupid']);
        $requesting_chapter_id = $requests[$br]['chapterid'];
		$edit = '';
		if ($requests[$br]['request_status'] != 2){
			$edit .='<div class="dropdown">'
			.'<button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown">'.$status[$requests[$br]['request_status']].' '
			.'<span class="caret" style="font-size: x-small;">&nbsp;&#9660;</span></button>'
			.'<ul class="dropdown-menu">';
		if($requests[$br]['request_status'] == 1) {
				$edit .=' <li><a href="javascript:void(0)" onclick="openBudgetRequestForm(\''.$groupid.'\',\''.$request_id .'\')">'.gettext("Edit").'</a></li>';
            if ($requesting_chapter_id && $_USER->canManageBudgetInScope($_COMPANY->decodeId($groupid),0,0)){
                $edit .=' <li><a href="javascript:void(0)" onclick="approveBudgetRequestForm(\''.$groupid.'\',\''.$request_id .'\')">'.gettext("Approve").'</a></li>';
                $edit .=' <li><a href="javascript:void(0)" onclick="rejectBudgetRequestForm(\''.$groupid.'\',\''.$request_id .'\')">'.gettext("Deny").'</a></li>';
                }
			}
			if ($requests[$br]['request_status'] == 3){
				$edit .= '  <li><a href="javascript:void(0)" class="deluser" onclick="deleteBudgetRequest('.$br.',\''.$request_id .'\',\''.$groupid.'\')" data-confirm-noBtn="'.gettext('No').'" data-confirm-yesBtn="'.gettext('Yes').'" title="'.gettext("Are you sure you want to delete this Budget Request?").'">'.gettext("Delete").'</a></li>';
			}
			$edit .= '</ul>'
		  .'</div>';
		} else {
            $edit  = $status[$requests[$br]['request_status']];
        }
        if ($requests[$br]['request_status'] == 2){
            if (empty($requests[$br]['budget_usesid'])) {
                if(Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['BUDGET_EXPENSE_CREATE_BEFORE'], $requests[$br]['groupid'])){
                    $call_method_parameters = array(
                        $request_id,
                        $groupid,
                    );
        
                    $call_other_method = base64_url_encode(json_encode(
                        array (
                            "method" => "createExpenseFromApprovedBudget",
                            "parameters" => $call_method_parameters
                        )
                    ));
                    $reloadOnClose = 0;
                    $onClickFunc = 'loadDisclaimerByHook(\'' . $_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['BUDGET_EXPENSE_CREATE_BEFORE']) . '\',\'' . $groupid . '\',\'' . $reloadOnClose . '\',\'' . $call_other_method . '\')';
                }else{
                    $onClickFunc = 'createExpenseFromApprovedBudget(\'' . $request_id . '\',\'' . $groupid . '\')';
                }

                $is_expense_created = '  <a href="javascript:void(0);" id="isCreateExpense" class="deluser btn btn-primary" onclick="'.$onClickFunc.'" data-confirm-noBtn="' . gettext('No') . '" data-confirm-yesBtn="' . gettext('Yes') . '" title="' . gettext("Are you sure you want to Create Expense Request?") . '">' . gettext("Create") . '</a>';
            } else {
                $is_expense_created = ' <a href="javascript:void(0);" onclick="addUpdateExpenseInfoModal(\'' . $groupid . '\',\'' . $_COMPANY->encodeId($requests[$br]['budget_usesid']). '\')">' . gettext('Edit Expense') . '</a>';
            }
        }else{
            $is_expense_created = gettext("Pending");
        }
		$final[] = array(
            "DT_RowId" => "row_".$br,
            "planned_use_date"=>$_USER->formatUTCDatetimeForDisplayInLocalTimezone($requests[$br]['need_by'],true,false,true), // Note for budget planned use date we show the date as is
			'purpose'=>$requests[$br]['purpose'],
            'erg'=>$group->val('groupname').($requests[$br]['chaptername'] ? '<br>('.$requests[$br]['chaptername'].')' : ''),
            'requester'=>$requests[$br]['requester_firstname'].' '.$requests[$br]['requester_lastname'],
			'requested_amount'=>$_COMPANY->getCurrencySymbol().number_format($requests[$br]['requested_amount'],2).($requests[$br]['request_status'] ==1 ? "" : ($requests[$br]['request_status'] ==3 ? "" : ' / '.$_COMPANY->getCurrencySymbol().number_format($requests[$br]['amount_approved'],2))),
            "request_date"=>$_USER->formatUTCDatetimeForDisplayInLocalTimezone($requests[$br]['request_date'],true,false,true),
            'description'=>$requests[$br]['description'],
			'approved_date'=> ($requests[$br]['request_status'] ==1 ? "-" : $_USER->formatUTCDatetimeForDisplayInLocalTimezone($requests[$br]['approved_date'],true,false,true)),
			'approver_comment'=>($requests[$br]['approver_comment'] ? $requests[$br]['approver_comment'] : "-"),
			'approver'=>($requests[$br]['firstname'] ? trim($requests[$br]['firstname']." ".$requests[$br]['lastname']) : "-"),
			'action'=>$edit,
            'is_expense_created'=>$is_expense_created,
            'attachments' => array_map(function (Attachment $attachment) {
                return [
                    'download_url' => $attachment->getDownloadUrl(),
                    'image_icon' => $attachment->getImageIcon(),
                    'display_name' => $attachment->getDisplayName(),
                    'readable_size' => $attachment->getReadableSize(),
                ];
            }, $budget_request->getAttachments()),
            'custom_fields' => $budget_request->getCustomFieldsAsArray(),
           );
    }                
    $json_data = array(
                    "draw"=> intval( $draw),
                    "recordsTotal"    => intval(count($final) ),
                    "recordsFiltered" => intval($requests_without_limit),
                    "data"            => $final
                );
          

	echo json_encode($json_data);
	exit();
}

elseif (isset($_GET['createExpenseFromApprovedBudget']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($request_id = $_COMPANY->decodeId($_GET['rid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageBudgetGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
	}

    $requests =  Budget2::GetBudgetRequestDetail($request_id);   

    $budget_request = BudgetRequest::Hydrate($request_id, $requests);

    $expense_budget_year_id = Budget2::GetBudgetYearIdByDate($requests['need_by']);

    $res = Budget2::AddOrUpdateBudgetUse($usesid=0,$requests['groupid'], $requests['chapterid'], $requests['amount_approved'], $requests['amount_approved'], $requests['description'], $requests['need_by'], $expense_budget_year_id, $requests['purpose'],$charge_code_id= 0, $vendor_name="", $eventid = 0, '','allocated_budget', false, '');
    $encGroupid = $_COMPANY->encodeId($res);
    if ($res) {
        Budget2::UpdateBudgetUsesId($request_id, $res);

        $expense_entry = ExpenseEntry::GetExpenseEntry($res);
        $expense_entry->copyAttachmentsFrom($budget_request);

        $successMessage = gettext("Expense entry added, on the next screen you can update it.");
        AjaxResponse::SuccessAndExit_STRING(1, array('lastId'=>$encGroupid), $successMessage, gettext('Success'));
    }
        
}

elseif (isset($_GET['openBudgetRequestForm']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    // Module check
    if (!$_COMPANY->getAppCustomization()['budgets']['enabled'] || !$_COMPANY->getAppCustomization()['budgets']['enable_budget_requests']) {
        Http::Forbidden('Module disabled');
    }

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageBudgetGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
	$edit = null;
    $chapters= Group::GetChapterList($groupid);
    $chapterid = $_SESSION['budget_by_chapter'] ?? 0;
    $modalTitle = gettext("Request Budget");
	$request_id = $_COMPANY->decodeId($_GET['request_id']);

    $custom_fields = BudgetRequest::GetEventCustomFields();
    $event_custom_fields = [];

	if ($request_id>0){
        $modalTitle = gettext("Update Budget Request");
		$edit = Budget2::GetBudgetRequestDetail($request_id);
        $budget_request = BudgetRequest::Hydrate($request_id, $edit);

        if (!empty($edit)){
            $chapterid = $edit['chapterid'];
        }

        $event_custom_fields = json_decode($budget_request->val('custom_fields'), true);
	}
	include(__DIR__ . "/views/templates/budget_request_form.template.php");
}

elseif (isset($_GET['setBudgetYear']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    Session::GetInstance()->budget_year = $_COMPANY->decodeId($_GET['setBudgetYear']);
    echo true;
}
elseif (isset($_GET['showUpdateChapterBudgetForm']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['showUpdateChapterBudgetForm']))<1  ||  
        ($year = $_COMPANY->decodeId($_GET['year'])) <1 ||     
        ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    
    // Authorization Check
    if (!$_USER->canManageBudgetInScope($groupid,0,0)) {
        header(HTTP_FORBIDDEN);
        exit();
	}

    $selectedBudgetYear = Budget2::GetCompanyBudgetYearDetail($year);
    $groupBudgetObj = Budget2::GetBudget($year, $groupid);
    $groupBudgetObj->getChildBudgets();

	include(__DIR__ . "/views/templates/update_chapter_budget.template.php");
}

elseif (isset($_GET['updateGroupChapterBudget']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $retVal = ['returnCode' => 0, 'successMessage'=>'', 'errorMessage'=>''];
    //Data Validation
    if (!isset($_POST['amount']) ||
        !isset($_POST['year']) ||
        !isset($_POST['groupid']) || ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        !isset($_POST['chapterid']) || ($chapterid = $_COMPANY->decodeId($_POST['chapterid']))<0 ||
        !($chapterid) //should be positive
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // Authorization Check
    if (!$_USER->canManageBudgetInScope($groupid,0,0)) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    $notifyleads = $_POST['notifyleads'] ? 1 : 0;
    $budgetamount	= (float)$_POST['amount'];
    $year = (int) $_POST['year'];

    $chapterBudget = Budget2::GetBudget($year, $groupid, $chapterid);
    $existingExpenses = $chapterBudget->getTotalExpenses()['spent_from_allocated_budget'];
    if ($budgetamount < $existingExpenses) {
        $retVal['errorMessage'] = sprintf(gettext('Amount should be greater than or equal to %s'), $_USER->formatAmountForDisplay($existingExpenses));
        print json_encode($retVal);
        exit();
    }

    $groupBudget = Budget2::GetBudget($year,$groupid,0);
    $groupAvailableBudget = $groupBudget->getTotalBudgetAvailable();
    $chapterAllocatedBudget = $chapterBudget->getTotalBudget();
    $maxBudgetAvailabileForChapterAllocation = $groupAvailableBudget + $chapterAllocatedBudget;

    if (($maxBudgetAvailabileForChapterAllocation - $budgetamount) < 0  && $budgetamount > 0){
        if ($maxBudgetAvailabileForChapterAllocation <= 0){
            $retVal['errorMessage'] = gettext('There is not enough budget available. Please change the amount requested to fit the budget restraints.');
        } else{
            $retVal['errorMessage'] = sprintf(gettext('Amount should be less than or equal to %s'), $_USER->formatAmountForDisplay($maxBudgetAvailabileForChapterAllocation));
        }
        print json_encode($retVal);
        exit();
    }

    $bid = Budget2::UpdateBudget($budgetamount, $year, $groupid, $chapterid);
    $groupBudget = Budget2::GetBudget($year,$groupid,0); // Reint budget object
   
    if ($bid > 0) {
        $retVal['returnCode'] = 1;
        $retVal['remaining_budget'] = $groupBudget->getTotalBudgetAvailable();
        $retVal['allocated_budget'] = $groupBudget->getTotalBudgetAllocatedToSubAccounts();

        // Send Budget Update notificaton to all chapter Leads
        if($notifyleads){
            $group = Group::GetGroup($groupid);
            $chapter = $group->getChapter($chapterid);
            $leads = $group->getWhoCanManageChapterBudget($chapterid);
    
            if ($chapter && !empty($leads)){ // $chapter will be null if chapter is not active, which is a valid use case and for that use case we will not send email notification.
                $lead_emails = implode(',',array_column($leads,'email'));
                $groupName = $group->val('groupname');
                $who_updated = $_USER->getFullName();
    
                $reply_addr = $group->val('replyto_email');
                $formatedBudgetAmount = $_COMPANY->getCurrencySymbol().number_format($budgetamount,2);
                $fiscalYear = Budget2::GetCompanyBudgetYearDetail($year);
    
                $temp = EmailHelper::ChapterBudgetUpdated($chapter['chaptername'], $groupid, $groupName, $who_updated,$fiscalYear['budget_year_title'],$formatedBudgetAmount);
                // Set from to Zone From label if available
                $from = $group->val('from_email_label') .' Budget Update';
    
                $_COMPANY->emailSend2($from, $lead_emails, $temp['subject'], $temp['message'], $_ZONE->val('app_type'),$reply_addr);
            }
        }
       
    } elseif ($bid == 0) {
        $max = $groupBudget->getTotalBudgetAvailable() + $chapterBudget->getTotalBudget();
        if ($max >0 ) {
            $retVal['errorMessage'] = sprintf(gettext('Amount should be less than %s'), $_USER->formatAmountForDisplay($max));
        } else {
            $retVal['errorMessage'] = gettext('There is not enough budget available. Please change the amount requested to fit the budget restraints.');
        }
    } else {
        $retVal['errorMessage'] = gettext('Internal Server Error');
    }
    print json_encode($retVal);
    exit();
}

elseif (isset($_GET['getChapterBudget']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
	if (!isset($_GET['getChapterBudget']) || ($groupid = $_COMPANY->decodeId($_GET['getChapterBudget']))<1 || !isset($_GET['chapter']) || ($chapterid = $_COMPANY->decodeId($_GET['chapter']))<0) {
        header(HTTP_BAD_REQUEST);
        exit();
	}
	$_SESSION['budget_by_chapter'] = $chapterid;
}

elseif (isset($_GET['getExpensesByErg']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    $year = Session::GetInstance()->budget_year ?: Budget2::GetBudgetYearIdByDate($_USER->getLocalDateNow());
	$chapterid = (int)($_SESSION['budget_by_chapter'] ??  0);
	$encGroupId = $_GET['getExpensesByErg'];
    //Data Validation
    if (($groupid = $_COMPANY->decodeId($encGroupId))<1 ||
	($group = Group::GetGroup($groupid)) === null) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageBudgetInScope($groupid, $chapterid,0)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $srch = raw2clean($_POST['search']['value']);
    $start = (int)$_POST['start'];
    $length = (int)$_POST['length'];
    $draw = (int)$_POST['draw'];
    $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
    $orderFields = ['budgetuses.date','chapters.chaptername','budgetuses.description','budgetuses.funding_source','budgetuses.budgeted_amount','budgetuses.usedamount'];

    $orderIndex = (int) $_POST['order'][0]['column'];
    $orderBy =  $orderFields[$orderIndex];

    $search = "";
    if ($srch){
        $searchInFundingSource = '';
        if ($_COMPANY->getAppCustomization()['budgets']['other_funding']) {
            $searchInFundingSource =  " OR budgetuses.funding_source LIKE '%{$srch}%' ";
        }
        $search = " AND (  chapters.chaptername LIKE '%{$srch}%' OR  budgetuses.date LIKE '%{$srch}%' OR budgetuses.description LIKE '%{$srch}%'  OR budgetuses.usedamount LIKE '%{$srch}%' {$searchInFundingSource})";
	}
	$condition = "";
	if ($chapterid){
		$condition = "budgetuses.`chapterid`='{$chapterid}' AND ";
	} else {
		$condition = "budgetuses.`groupid`='{$groupid}' AND ";
	}

    // -- TODO--
    //Datatable dependencies 
    // Will removed if Tabulator 

	$expenses      = $db->get("SELECT budgetuses.*,groups.groupname,`chapters`.`chaptername`,events.eventtitle FROM `budgetuses` left JOIN `groups`ON groups.groupid=budgetuses.groupid left JOIN events ON events.eventid=budgetuses.eventid LEFT JOIN `chapters` ON  `chapters`.chapterid = budgetuses.chapterid WHERE budgetuses.`companyid`='{$_COMPANY->id()}' AND ( $condition budgetuses.`budget_year_id`='{$year}' AND budgetuses.`isactive`=1 {$search} ) ORDER BY {$orderBy} {$orderDir} limit {$start},{$length} ");

    $totalrows = $db->get("SELECT count(1) as total FROM `budgetuses` left JOIN `groups`ON groups.groupid=budgetuses.groupid left JOIN events ON events.eventid=budgetuses.eventid LEFT JOIN `chapters` ON  `chapters`.chapterid = budgetuses.chapterid WHERE budgetuses.`companyid`='{$_COMPANY->id()}' AND ( $condition budgetuses.`budget_year_id`='{$year}' AND budgetuses.`isactive`=1 {$search} )")[0]['total'];

	$i=1;
	$final = [];
    foreach($expenses as $row){
		$encodedId = $_COMPANY->encodeId($row['usesid']);
		$grupname =  $row['chaptername'] ? $row['chaptername'] : '-';
        $funding_source = ($row['funding_source'] == 'allocated_budget') ? gettext('Allocated Budget') : gettext('Other Funding');

        $final[] = array(
            "DT_RowId" => $i,
            $_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['date'],true,false,false,'',"UTC"), // Do not convert date to local timezone
			$grupname ,
            $row['description'] ? $row['description'] : '-',
            $funding_source ? $funding_source : '-',
            $_COMPANY->getCurrencySymbol().number_format($row["budgeted_amount"],2),
            '<a href="javascript:void(0)" style="cursor:pointer; color:#3c8dbc;" onclick="addUpdateExpenseInfoModal(\''.$encGroupId.'\',\''.$encodedId.'\', \'\', true)">'.$_COMPANY->getCurrencySymbol().number_format($row["usedamount"],2).'</a>',
			(($_USER->isAdmin() || $_COMPANY->getAppCustomization()['budgets']['allow_grouplead_to_edit_expense']) ?
                '<div class="col-md-2">
                <button aria-label="Expenses action dropdown" tabindex="0" class="btn-no-style dropdown-toggle fa fa-ellipsis-v" data-toggle="dropdown" aria-expanded="true">                
                </button>                
				<div class="dropdown-menu dropmenu">
					<a href="javascript:void(0)" class="dropdown-item" onclick="addUpdateExpenseInfoModal(\''.$encGroupId.'\',\''.$encodedId.'\')">'.addslashes(gettext("Edit")).'</a>
					<a href="javascript:void(0)" class="dropdown-item deluser" data-confirm-noBtn="'.addslashes(gettext('No')).'" data-confirm-yesBtn="'.addslashes(gettext('Yes')).'" title="" onclick="deleteExpenseInfo(\''.$encGroupId.'\',\''.$encodedId.'\','.$i.')" data-original-title="'.addslashes(gettext("Are you sure you want to delete?")).'">'.addslashes(gettext("Delete")).'</a>
				</div>
			</div>' : '')
           );
        $i++;
    }

    $json_data = array(
                    "draw"=> intval( $draw),
                    "recordsTotal"    => intval(count($final) ),
                    "recordsFiltered" => intval($totalrows),
                    "data"            => $final
				);
	echo json_encode($json_data);
}

elseif (isset($_GET['deleteExpenseInfo']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

	//Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['deleteExpenseInfo']))<1 ||
        ($usesid = $_COMPANY->decodeId($_POST['id']))<1 ||
        ($usesRow = Budget2::GetBudgetUse($usesid)) == NULL ||
        ($usesRow['groupid'] != $groupid)
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
	}

    $event = $usesRow['eventid'] ? Event::GetEvent($usesRow['eventid']) : null;

    // Authorization Check - if allow_grouplead_to_edit_expense is off then only admins can delete
    if (
        (
            (!$_USER->isAdmin() && !$_COMPANY->getAppCustomization()['budgets']['allow_grouplead_to_edit_expense'])
            ||
            !$_USER->canManageBudgetInScope($groupid,$usesRow['chapterid'],0)
        )
        &&
        !$event?->loggedinUserCanManageEventBudget()
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if ($event) {
        if($event->isActionDisabledDuringApprovalProcess() && $usesRow["budgeted_amount"]>0){
            if($event->isSeriesEventSub()) {
                $seriesHead = Event::GetEvent($event->val('event_series_id'));
                $approval = $seriesHead->getApprovalObject();
            } else {
                $approval = $event->getApprovalObject();
            }
            $isApprovalStatusApproved = $approval->isApprovalStatusApproved();
            if ($isApprovalStatusApproved) {
                AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('The expense entry cannot be deleted because it is linked to the %s event, and its budget has already been approved.'),$event->val('eventtitle')), gettext('Error'));
            } else{
                AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('The expense entry cannot be deleted because it is linked to the %s event, which is currently undergoing the approval process.'),$event->val('eventtitle')), gettext('Error'));
            }
        }
    }

 	if (Budget2::DeleteBudgetUse($usesid)) {
        AjaxResponse::SuccessAndExit_STRING(1, '', gettext('The expense entry deleted successfully.'), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Expense entry not deleted due to some reason. Please try again.'), gettext('Error'));
    }
}

elseif (isset($_GET['manageOtherFunding']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        (($group = Group::GetGroup($groupid)) == null) ||
        !$_COMPANY->getAppCustomization()['budgets']['other_funding']
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageBudgetGroupSomething($groupid)) {
        header(HTTP_FORBIDDEN);
        exit();
	}
    $chapterid = 0;
    if(isset($_SESSION['budget_by_chapter'])){
        $chapterid = $_SESSION['budget_by_chapter'];
    }
    $budget_year_id = Session::GetInstance()->budget_year ?: Budget2::GetBudgetYearIdByDate($_USER->getLocalDateNow());
    $otherFunds = Budget2::GetGroupOtherFunding($groupid,$budget_year_id,$chapterid);
   
	include(__DIR__ . "/views/templates/manage_group_other_funding.template.php");
}

elseif (isset($_GET['addEditOtherBudgetModal']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    $selectedChapterId = 0;
    $otherFundingDetail = null;
    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($funding_id = $_COMPANY->decodeId($_GET['funding_id']))<0 ||
        ($funding_id && ($otherFundingDetail = Budget2::GetGroupOtherFundingDetail($funding_id)) === null) ||
        (isset($_GET['chapterid']) && ($selectedChapterId = $_COMPANY->decodeId($_GET['chapterid']))<0 ||
        (isset($otherFundingDetail) && ($selectedChapterId > 0) && ($selectedChapterId != $otherFundingDetail['chapterid']) && ($groupid != $otherFundingDetail['groupid']))
        )
        || !$_COMPANY->getAppCustomization()['budgets']['other_funding']
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageBudgetInScope($groupid,$selectedChapterId,0)) {
        header(HTTP_FORBIDDEN);
        exit();
	}
    $fundingBucket = $group->getFromEmailLabel($selectedChapterId);
    $modalTitle = sprintf(gettext("Add new fund to %s"), $fundingBucket);

    if  ($funding_id){
        $modalTitle = sprintf(gettext("Update fund for %s"), $fundingBucket);
    }
    $chapters = Group::GetChapterList($groupid);

	include(__DIR__ . "/views/templates/add_edit_other_funding_modal.template.php");
}

elseif (isset($_GET['saveGroupOtherFund']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($chapterid = $_COMPANY->decodeId($_POST['chapterid']))<0 // Chapterid can be zero
        || !$_COMPANY->getAppCustomization()['budgets']['other_funding'] ||
        ($funding_scope =  $_COMPANY->decodeId($_POST['funding_scope']))<0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $chapterid = $funding_scope ? $funding_scope : $chapterid;
    // Authorization Check
    if (!$_USER->canManageBudgetInScope($groupid,$chapterid,0)) {
        header(HTTP_FORBIDDEN);
        exit();
	}
    $funding_id = $_COMPANY->decodeId($_POST['funding_id']);
    $funding_source = $_POST['funding_source'];
    $funding_date = $_POST['funding_date'];
    $funding_amount = (float)$_POST['funding_amount'];
    $funding_currency = 'USD';
    $funding_description = $_POST['funding_description'];

    //Data Validation
    $check = $db->checkRequired(array('Funding Source'=>$funding_source,'Funding Description'=>$funding_description));
    if($check){
        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty!"),$check), gettext('Error!'));
    }

    if ( empty($funding_source)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please add funding source.'), gettext('Error'));
    }
    if ( empty($funding_date)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please add funding date.'), gettext('Error'));
    }
    if (empty($funding_amount) || $funding_amount < 0.01 ){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Amount cannot be zero or a negative number.'), gettext('Error'));
    }
    if ( empty($funding_description)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Please add a description.'), gettext('Error'));
    }
    $budget_year_id = Budget2::GetBudgetYearIdByDate($funding_date);
    if ( empty($budget_year_id)){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Unable to find a budget year that matches the funding date.'), gettext('Error'));
    }
    
    if  (Budget2::CreateOrUpdateGroupOtherFunding($funding_id, $groupid, $chapterid, $funding_source,$funding_date,$budget_year_id,$funding_amount,$funding_currency,$funding_description)){
        $msg = gettext("Fund added successfully");
        if ($funding_id){
            $msg = gettext("Fund updated successfully");
        }
        AjaxResponse::SuccessAndExit_STRING(1, '', $msg, gettext('Success'));
    }

    AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Something went wrong. Please try again.'), gettext('Error'));
}

elseif (isset($_GET['deleteGroupOtherFund']) && $_SERVER['REQUEST_METHOD'] === 'POST'){

    //Data Validation
    if (
        ($funding_id = $_COMPANY->decodeId($_POST['fid']))<1 ||
        (($funding = Budget2::GetGroupOtherFundingDetail($funding_id)) === null) ||
        !$_COMPANY->getAppCustomization()['budgets']['other_funding']
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageBudgetInScope($funding['groupid'],$funding['chapterid'],0)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    if  (Budget2::DeleteGroupOtherFunding($funding_id)){
        AjaxResponse::SuccessAndExit_STRING(1, '', '', gettext('Success'));
    }

    exit;
}

elseif (isset($_GET['approveBudgetRequestForm']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($request_id = $_COMPANY->decodeId($_GET['request_id']))<1

    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageBudgetInScope($groupid,0,0)) {
        header(HTTP_FORBIDDEN);
        exit();
	}

    $budgeRequest = Budget2::GetBudgetRequestDetail($request_id);
    $budget_request = BudgetRequest::Hydrate($request_id, $budgeRequest);
    $groupBudget = Budget2::GetBudget(Session::GetInstance()->budget_year, $groupid);
    $budgetYear = Budget2::GetCompanyBudgetYearDetail(Session::GetInstance()->budget_year);
    if ($budgeRequest['chapterid']) {
        $chapterBudget = Budget2::GetBudget($budgetYear['budget_year_id'],$groupid,$budgeRequest['chapterid']);

        $parentAvailableBudget = $groupBudget->getTotalBudgetAvailable();
        $childAllocatedBudget = $chapterBudget->getTotalBudget();
        $childAvailableBudget = $chapterBudget->getTotalBudgetAvailable();
        $parentName = $_COMPANY->getAppCustomization()['group']['name-short'];
        $childName = $_COMPANY->getAppCustomization()['chapter']['name-short'];
    } else {
        $companyBudget = Budget2::GetBudget($budgetYear['budget_year_id']);

        $parentAvailableBudget = $companyBudget->getTotalBudgetAvailable();
        $childAllocatedBudget = $groupBudget->getTotalBudget();
        $childAvailableBudget = $groupBudget->getTotalBudgetAvailable();
        $parentName = gettext('Company');
        $childName = $_COMPANY->getAppCustomization()['group']['name-short'];
    }

    $parentAvailableBudgetTitle = sprintf(gettext('Budget Available at %s level'), $parentName);
    $childAllocatedBudgetTitle = sprintf(gettext('Budget Allocated to the %s'),$childName);
    $childAvailableBudgetTitle = sprintf(gettext('Budget Available at the %s Level'), $childName);
    $moveBudgetParentTochildTitle = sprintf(gettext('Move the approved amount from %1$s budget to %2$s'), $parentName, $childName);
    $parentAvailableBudgetAfterMoveTitle = sprintf(gettext('Budget Available at %s level after this approval request'), $parentName);
    $childAllocatedBudgetAfterMoveTitle = sprintf(gettext('Budget Allocated to %s after this approval request'), $childName);
    $moveBudgetNote = sprintf(gettext('Please note: As of January 2024, there has been a change in the behavior of budget approval and movement. In previous product releases (prior to January 2024), upon approving a budget request, the approved amount would automatically be moved from the %1$s budget to the %2$s budget. <strong>This automatic budget movement is no longer the default behavior.</strong> To allow the approved budget to be moved from the %1$s budget to the %2$s budget, you must now manually select the checkbox provided above.'), $parentName, $childName);

    include(__DIR__ . "/views/templates/buget_request_approve_modal.template.php");
}

elseif (isset($_GET['rejectBudgetRequestForm']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (($groupid = $_COMPANY->decodeId($_GET['groupid']))<1 ||
        ($request_id = $_COMPANY->decodeId($_GET['request_id']))<1

    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageBudgetInScope($groupid,0,0)) {
        header(HTTP_FORBIDDEN);
        exit();
	}

    $budgeRequest = Budget2::GetBudgetRequestDetail($request_id);
    $budget_request = BudgetRequest::Hydrate($request_id, $budgeRequest);

	include(__DIR__ . "/views/templates/budget_request_reject_modal.template.php");
}

elseif (isset($_GET['approveBudgetRequest']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $status = array('','Requested','Approved','Denied');
    $request_status = 2; // Approved

    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($request_id = $_COMPANY->decodeId($_POST['request_id']))<1  ||
        !(isset($_POST['amount_approved'])) ||
        !($budget_request_detail = Budget2::GetBudgetRequestDetail($request_id))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$budget_request_detail['chapterid'] || // Note: Only chapter level budget can be approved from Affinities
        ($budget_request_detail['groupid'] != $groupid) ||
        !$_USER->canManageBudgetInScope($groupid,0,0)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	$amount_approved	= (float)$_POST['amount_approved'];
	$approver_comment	= (string) ($_POST['approver_comment']);
    $move_parent_budget_to_child = isset($_POST['move_parent_budget_to_child']) ? 1 : 0;

	if($amount_approved < 0.01) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Amount cannot be zero or a negative number.'), gettext('Error'));
    }

    $budgetYearId = Budget2::GetBudgetYearIdByDate($budget_request_detail['need_by']);
    $budgetObj = Budget2::GetBudget($budgetYearId,$budget_request_detail['groupid'],$budget_request_detail['chapterid']);

    $group = Group::GetGroup($budget_request_detail['groupid']);
    $groupname = $group->val('groupname');
    $chapter = $group->getChapter($budget_request_detail['chapterid']);
    $chapterName = $chapter ? $chapter['chaptername'] : '';
    $requester = User::GetUser($budget_request_detail['requested_by']);
    $requester_name = $requester ? $requester->getFullName() : '';
    $requester_email = $requester ? $requester->val('email') : '';
    $approver_name = $_USER->getFullName();

    if ($move_parent_budget_to_child){
        if ($budgetObj->moveBudgetFromParentToMe($amount_approved) <= 0) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext('Not enough budget available to service this request'), gettext('Error'));
        }
        // Next also send budget update emails to all Chapter leads / Budget Role to share that the budget has been updated
        $leads = $group->getWhoCanManageChapterBudget($budget_request_detail['chapterid']);
        if (!empty($leads)){
            $updatedChapterBudgetObj = Budget2::GetBudget($budgetYearId,$budget_request_detail['groupid'],$budget_request_detail['chapterid']);
            $lead_emails = implode(',',array_column($leads,'email'));
            $formatedBudgetAmount = $_COMPANY->getCurrencySymbol().number_format($updatedChapterBudgetObj->getTotalBudget(),2);
            $fiscalYear = Budget2::GetCompanyBudgetYearDetail($budgetYearId);

            $temp = EmailHelper::ChapterBudgetUpdated($chapterName, $groupid, $groupname, $approver_name,$fiscalYear['budget_year_title'],$formatedBudgetAmount);
            // Set from to Zone From label if available
            $from = $group->val('from_email_label') .' Budget Update';

            $_COMPANY->emailSend2($from, $lead_emails, $temp['subject'], $temp['message'], $_ZONE->val('app_type'),'');
        }
    }

	Budget2::ApproveOrDenyBudgetRequest($groupid, $request_id, $amount_approved, $approver_comment, $request_status, $budget_request_detail['budget_usesid']??0,$move_parent_budget_to_child);

    $budget_request = BudgetRequest::GetBudgetRequest($request_id);

    // Email Notification
    $requested_amount = $_COMPANY->getCurrencySymbol().$_USER->formatAmountForDisplay($budget_request_detail['requested_amount']);
    $approved_amount = $_COMPANY->getCurrencySymbol().$_USER->formatAmountForDisplay($amount_approved);
    // Get the email template
    $temp = EmailHelper::ChapterBudgetRequestApproved($chapterName, $groupid, $groupname, $requester_name, $budget_request_detail['purpose'], $requested_amount, $approved_amount, $approver_comment, $approver_name, $budget_request);
    // Set from to Group From label if available
    $from = ($group->val('from_email_label') ?? $_ZONE->val('email_from_label')) . ' Budget Request';

    // Send emails
    $_COMPANY->emailSend2($from, $requester_email, $temp['subject'], $temp['message'], $_ZONE->val('app_type'),'');

    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Your request has been processed successfully!'), gettext('Success'));
}

elseif (isset($_GET['rejectBudgetRequest']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    $status = array('','Requested','Approved','Denied');
    $request_status = 3; // Denied

    if (($groupid = $_COMPANY->decodeId($_POST['groupid']))<1 ||
        ($request_id = $_COMPANY->decodeId($_POST['request_id']))<1  ||
        !($budget_request_detail = Budget2::GetBudgetRequestDetail($request_id))
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$budget_request_detail['chapterid'] || // Note: Only chapter level budget can be approved from Affinities
        ($budget_request_detail['groupid'] != $groupid) ||
        !$_USER->canManageBudgetInScope($groupid,0,0)) {
        header(HTTP_FORBIDDEN);
        exit();
    }

	$approver_comment	= $_POST['approver_comment'];

    Budget2::ApproveOrDenyBudgetRequest($groupid, $request_id, 0, $approver_comment, $request_status, $budget_request_detail['budget_usesid']??0, 0);

    // Email Notification
    $group = Group::GetGroup($budget_request_detail['groupid']);
    $groupname = $group->val('groupname');
    $chapter = $group->getChapter($budget_request_detail['chapterid']);
    $chapterName = $chapter ? $chapter['chaptername'] : '';
    $requester = User::GetUser($budget_request_detail['requested_by']);
    $requester_name = $requester ? $requester->getFullName() : '';
    $requester_email = $requester ? $requester->val('email') : '';
    $approver_name = $_USER->getFullName();
    $approver_email = $_USER->val('email');
    $requested_amount = $_COMPANY->getCurrencySymbol().$_USER->formatAmountForDisplay($budget_request_detail['requested_amount']);
    $approved_amount = $_COMPANY->getCurrencySymbol().$_USER->formatAmountForDisplay($budget_request_detail['amount_approved']);

    $budget_request = BudgetRequest::Hydrate($request_id, $budget_request_detail);
    // Get the email template
    $temp=EmailHelper::ChapterBudgetRequestDenied( $chapterName, $groupid, $groupname, $requester_name, $approver_name, $approver_email, $budget_request_detail['purpose'], $requested_amount, $approved_amount, $approver_comment, $budget_request);
    // Set from to Group From label if available
    $from = ($group->val('from_email_label') ?? $_ZONE->val('email_from_label')) . ' Budget Request';

    // Send emails
    $_COMPANY->emailSend2($from, $requester_email, $temp['subject'], $temp['message'], $_ZONE->val('app_type'),'');
    AjaxResponse::SuccessAndExit_STRING(1, '', gettext('Your request has been processed successfully!'), gettext('Success'));
}

elseif (isset($_GET['showhideForeignCurrencyInput']) && $_SERVER['REQUEST_METHOD'] === 'GET'){

    //Data Validation
    if (($expenseDate =  $_GET['expenseDate']) == ''
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $budget_year_id = Budget2::GetBudgetYearIdByDate($expenseDate);

    if ($budget_year_id){
        $_SESSION['tmp_budget_year'] = $budget_year_id;
        $budgetYear = Budget2::GetCompanyBudgetYearDetail($budget_year_id);
        $allowed_foreign_currencies = array();

        if ($budgetYear['allowed_foreign_currencies']){
            $allowed_foreign_currencies = json_decode($budgetYear['allowed_foreign_currencies'],true);
        }
        if ($budgetYear && !empty($allowed_foreign_currencies)){
            $allCurrencies = Budget2::FOREIGN_CURRENCIES;
?>
            <div class="col-md-4 p-1">
                <?= addslashes(gettext("Foreign Currency"));?>
                <select class="form-control" name="foreigncurrency[]" onchange="prePopulateConversionRate(this)">
                    <option value=""><?= addslashes(gettext("Select Foreign Currency"));?></option>
                        <?php foreach($allowed_foreign_currencies as $currency){ ?>
                            <option value="<?= $currency['cc'] ?>"><?= $currency['cc'].' ('.$allCurrencies[$currency['cc']]['name'].')'; ?></option>
                    <?php } ?> 
                </select>
            </div>

            <div class="col-md-4 p-1">
                <span onclick="allowConversionRateToChange(this);"><?= addslashes(gettext("Conversion Rate"));?> <i class="fa fas fa-edit" aria-hidden="true"></i></span>
                <input type="text" onfocusout="$(this).prop('readonly', true);updateForeignCurrencyAmountOnRateChange(this);" name="currencyconversionrate[]" class="form-control" placeholder="<?=addslashes(gettext('Conversion rate e.g. 0.1132'))?>" min="0" value="" required />
            </div>
            <div class="col-md-4 p-1">
                <?= addslashes(gettext("Foreign Currency Amount"));?>
                <input aria-label="<?= gettext("Foreign Currency Amount");?>" type="number" name="foreigncurrencyamount[]" onchange="calculateHomeCurrencyAmount(this)" class="form-control" placeholder="<?= addslashes(gettext('Amount e.g. 10'));?>" required />
            </div>
<?php        
        } else {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Foreign currency not found."), gettext('Error'));
        }
    }  else { 
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Budget year not found for selected date."), gettext('Error'));
    }
}
elseif (isset($_GET['prePopulateConversionRate']) && $_SERVER['REQUEST_METHOD'] === 'GET'){
    //Data Validation
    if (
    ($currencyCode =  $_GET['currencyCode']) == ''
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $budget_year_id = $_SESSION['tmp_budget_year'];
    $conversionRate = 0;
    $budgetYear = Budget2::GetCompanyBudgetYearDetail($budget_year_id);
    if ($budgetYear['allowed_foreign_currencies']){
        $allowed_foreign_currencies = json_decode($budgetYear['allowed_foreign_currencies'],true);
        foreach($allowed_foreign_currencies as $currency){
            if ($currency['cc'] == $currencyCode){
                $conversionRate = number_format($currency['conversion_rate'], 9) ;
                break;
            }
        }
    }
    echo $conversionRate;
}
else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}