<style>
	.dropdown-menu.dropmenu.show {
		left: auto !important;
		transform: none !important;
	}
    .budget-summary {
        margin: 20px 0 20px 0;
        background-color: #f0f0f0;
    }
    .budget-summary-line{
        padding: 10px;
    }
	.budget-summary-line p{
		color: #2791d1;font-size: 20px;
        text-align: center;
	}
    .budget-summary-line h4{
        text-align: center;
    }
    thead td {
        padding: 0px !important;
        height: 12px;
    }
    .expense_head {
        margin-bottom: .75rem;
    }

    .dropdown-toggle:focus {
    outline: none !important;
    border:1px solid #3c8dbc;
    }
 </style>

<script>
$(document).ready(function(){
    jQuery(".deluser").popConfirm({content: ''});
});

</script>
<?php
	$headtitle = "Manage";

	$type = Event::GetEventTypesByZones([$_ZONE->id()]);

    // The following code handles the usecases where the budget year might be deleted or was incorrectly set in the
    // session and in those scenarios it resets the session variable to the current budget year.
    $selectedBudgetYear = Budget2::GetCompanyBudgetYearDetail(Session::GetInstance()->budget_year);
    if (!$selectedBudgetYear) {
        Session::GetInstance()->budget_year = Budget2::GetBudgetYearIdByDate($_USER->getLocalDateNow());
        $selectedBudgetYear = Budget2::GetCompanyBudgetYearDetail(Session::GetInstance()->budget_year);
    }

    $label = $group->val('groupname_short');
    $chapterRegionid = 0;
    if ($chapterid != 0) {
        foreach ($chapters as $ch) {
            if ($ch['chapterid'] == $chapterid) {
                $label = $label . ' > ' . $ch['chaptername'];
                $chapterRegionid = $ch['regionids'];
                break;
            }
        }
    }
    $budget_year_title = htmlspecialchars($selectedBudgetYear['budget_year_title']);
    $label =  $budget_year_title . ' Budget and Expenses - '.$label;

    $allocated_budget_total = $budgetObject->getTotalBudget();
    $allocated_budget_spent = $budgetObject->getTotalExpensesInclChild()['spent_from_allocated_budget'];
    $allocated_budget_remaining = $allocated_budget_total - $allocated_budget_spent;

    $other_funding_total = $groupOtherFundTotal;
    $other_funding_spent = $budgetObject->getTotalExpensesInclChild()['spent_from_other_funding'];
    $other_funding_remaining = $other_funding_total - $other_funding_spent;

    $total_budget = $allocated_budget_total + $other_funding_total;
    $total_spent = $allocated_budget_spent + $other_funding_spent;
    $total_remaining = $allocated_budget_remaining + $other_funding_remaining;

?>
<div class="col-md-12">
    <div class="row">
        <div class="col-12">
            <h2><?=$label?></h2>
        </div>
    </div>
    <hr class="lineb" >
</div>
<div class="inner-page-container">
    <div class="m-2">&nbsp;</div>
    <div class="col-md-12">
    <div class="col-md-6">
        <h3 class="expense_head"><?= gettext("Budget");?></h3>
    </div>
    <div class="col-md-6">
        <div class="pull-right row">
            <div>
                <?php
                $page_tags = 'manage_budget';
                ViewHelper::ShowTrainingVideoButton($page_tags);
                ?>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
    <hr class="linec mt-0">
                
    <div class="col-sm-12 mt-3">
        <div class="col-sm-4">
            <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled'] && $budgetObject->val('chapterid') == 0) { ?>
            <button
                <?php if ($_USER->isAdmin() || $_USER->canManageBudgetGroup($groupid)) { ?>
                    onclick="updateChapterBudgetForm('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($year); ?>')"
                    class="req-up-bt btn btn-sm btn-primary"
                <?php } else {?>
                    onclick=""
                    class="req-up-bt btn btn-sm btn-primary disabled"
                    disabled
                <?php } ?>
                    style="max-height: 34px;"
            >
                <?=$_COMPANY->getAppCustomization()['chapter']['name-short']?>&nbsp;<?= gettext("Budget");?>
            </button>
            <?php } else { ?>
                &nbsp;
            <?php }  ?>
        </div>
		<div class="col-sm-4">
			<select aria-label="<?= gettext("Select a group");?>"  type="text" class="form-control" id="newsletter_chapter" name="chapterid" style="font-size:small;border-radius: 5px; margin: 0 auto;" onchange="getChapterBudget('<?=$encGroupId?>',this.value)" >
                <?php if ($_USER->canManageBudgetGroup($groupid)) { ?>
                <option value="<?= $_COMPANY->encodeId(0) ?>" <?= $chapterid == '0' ? 'selected' : ''; ?> ><?= $group->val('groupname')." ".$_COMPANY->getAppCustomization()['group']['name-short']; ?></option>
                <?php } ?>
                <?php for($i=0;$i<count($chapters);$i++){
                    $selc = "";
                    if ($chapters[$i]['chapterid'] == $chapterid){
                        $selc = "selected";
                    }
                ?>

                <?php if ($_USER->canManageBudgetGroupChapter($groupid,$chapters[$i]['regionids'],$chapters[$i]['chapterid'])) { ?>
                <option  value="<?= $_COMPANY->encodeId($chapters[$i]['chapterid']) ?>" <?= $selc; ?> >&emsp;<?= htmlspecialchars($chapters[$i]['chaptername']); ?></option>
                <?php } ?>
                <?php } ?>
            </select>
		</div>
        <div class="col-sm-4 text-right">
            <select aria-label="<?= gettext("Select Budget Year");?>" class="form-control" required onchange="setBudgetYear(this.value,'<?=$_COMPANY->encodeId($groupid);?>')" style="font-size:small;border-radius: 5px; margin: 0 auto;">
                <?php
                foreach($budgetYears as $budgetYear){
                    $sel = "";
                    if (Session::GetInstance()->budget_year== $budgetYear['budget_year_id']){
                        $sel = "selected";
                    }
                ?>
                <option value="<?= $_COMPANY->encodeId($budgetYear['budget_year_id']); ?>" <?= $sel; ?> ><?= htmlspecialchars($budgetYear['budget_year_title']) ?></option>
                <?php } ?>
            </select>
        </div>
	</div>
    <div class="col-md-12 budget-summary">
        <div class="col-md-12 budget-summary-line">
            <p style="text-align: center; color: #000000; font-size: smaller;"><?= htmlspecialchars(sprintf(gettext("Budget Summary for %s"), $budget_year_title)); ?></p>
        </div>
        <div class="col-md-4 budget-summary-line text-center">
            <h3 style="text-align: center; margin-bottom: 1rem;"><?= gettext("Allocated Budget");?></h3>
            <div class="row text-right no-gutters">
                <div class="col pr-3 text-sm">
                    <?=gettext('Budget')?>
                </div>
                <div class="col pr-3 btn-dark">
                    <?= $_COMPANY->getCurrencySymbol(); ?><?= $_USER->formatAmountForDisplay($allocated_budget_total) ?>
                </div>
            </div>

            <div class="row text-right no-gutters">
                <div class="col pr-3 text-sm">
                    <?=gettext('Expenses')?>
                </div>
                <div class="col pr-3">
                    <?= $_COMPANY->getCurrencySymbol(); ?><?= $_USER->formatAmountForDisplay($allocated_budget_spent) ?>
                </div>
            </div>

            <div class="row text-right no-gutters">
                <div class="col pr-3 text-sm">
                    <?=gettext('Remaining')?>
                </div>
                <div class="col pr-3 <?= ($allocated_budget_remaining>0) ? 'btn-success' : 'btn-warning'?>">
                    <?= $_COMPANY->getCurrencySymbol(); ?><?= $_USER->formatAmountForDisplay($allocated_budget_remaining) ?>
                </div>
            </div>
            <?php
            $call_method_parameters = array(
                $_COMPANY->encodeId($groupid),
                $_COMPANY->encodeId(0),
            );

            $call_other_method = base64_url_encode(json_encode(
                array (
                    "method" => "openBudgetRequestForm",
                    "parameters" => $call_method_parameters
                )
            )); // base64_url_encode for prevent js parsing error
            ?>

            <p class="mt-3">
            <?php if ($_COMPANY->getAppCustomization()['budgets']['enable_budget_requests'] && ($_USER->isAdmin() || $_USER->canManageBudgetGroup($groupid) || $isChapterLeadOnly)) { ?>
                <button class="btn btn-sm btn-link" 
                <?php if(Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['BUDGET_REQUEST_CREATE_BEFORE'], $groupid)){ ?>
            onclick="loadDisclaimerByHook('<?=$_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['BUDGET_REQUEST_CREATE_BEFORE'])?>','<?=$_COMPANY->encodeId($groupid)?>', 0, '<?=$call_other_method?>')"
            <?php } else {?>
                        onclick="openBudgetRequestForm('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId(0); ?>')"
                        <?php } ?>
                >
                    <?= gettext("Request");?>
                    
                </button>

                <span style="font-size: 12px;">|</span>
                <button id="request-view-btn"
                        class="btn btn-sm btn-link <?= count($requests) ? "" : "btn-disabled disabled" ?>" <?= count($requests) ? "" : "disabled" ?>
                        onclick="showRequstTable()"><?= gettext("View Past Requests");?>
                </button>
            <?php } ?>
            </p>

        </div>

        <div class="col-md-4 budget-summary-line text-center">
        <?php if ($_COMPANY->getAppCustomization()['budgets']['other_funding']) { ?>
        <h3 style="text-align: center;margin-bottom: 1rem;"><?= gettext("Other Funding Sources");?></h3>

            <div class="row text-right no-gutters">
                <div class="col pr-3 text-sm">
                    <?=gettext('Funding')?>
                </div>
                <div class="col pr-3 btn-dark">
                    <?= $_COMPANY->getCurrencySymbol(); ?><?= $_USER->formatAmountForDisplay($other_funding_total) ?>
                </div>
            </div>

            <div class="row text-right no-gutters">
                <div class="col pr-3 text-sm">
                    <?=gettext('Expenses')?>
                </div>
                <div class="col pr-3">
                    <?= $_COMPANY->getCurrencySymbol(); ?><?= $_USER->formatAmountForDisplay($other_funding_spent) ?>
                </div>
            </div>

            <div class="row text-right no-gutters">
                <div class="col pr-3 text-sm">
                    <?=gettext('Remaining')?>
                </div>
                <div class="col pr-3 <?= ($other_funding_remaining>0) ? 'btn-success' : 'btn-warning'?>">
                    <?= $_COMPANY->getCurrencySymbol(); ?><?= $_USER->formatAmountForDisplay($other_funding_remaining) ?>
                </div>
            </div>

            <p class="mt-3">
            <?php if (($_USER->isAdmin() || $_USER->canManageBudgetInScope($groupid,$chapterid,0))) { ?>
                <button class="btn btn-sm btn-link"
                        onclick="manageOtherFunding('<?= $_COMPANY->encodeId($groupid); ?>')">
                    <?= gettext("Manage Funding");?>
                </button>
            <?php } ?>
            </p>

        <?php } ?>
        </div>

        <div class="col-md-4 budget-summary-line text-center">
            <h3 style="text-align: right; margin-bottom: 1rem;"><?= gettext("Total");?></h3>
            <div class="row text-right no-gutters">
                <div class="col pr-3 text-sm">
                    <?= gettext("Total Budget");?>
                </div>
                <div class="col pr-3 btn-dark">
                    <?= $_COMPANY->getCurrencySymbol(); ?><?= $_USER->formatAmountForDisplay($total_budget) ?>
                </div>
            </div>
            <div class="row text-right no-gutters">
                <div class="col pr-3 text-sm">
                    <?= gettext("Total Expenses");?>
                </div>
                <div class="col pr-3">
                    <?= $_COMPANY->getCurrencySymbol(); ?><?= $_USER->formatAmountForDisplay($total_spent) ?>
                </div>
            </div>
            <div class="row text-right no-gutters">
                <div class="col pr-3 text-sm">
                    <?= gettext("Total Remaining");?>
                </div>
                <div class="col pr-3 <?= ($total_remaining>0) ? 'btn-success' : 'btn-warning'?>">
                    <?= $_COMPANY->getCurrencySymbol(); ?><?= $_USER->formatAmountForDisplay($total_remaining) ?>
                </div>
            </div>

            <p class="mt-3">
            <?php if ($_COMPANY->getAppCustomization()['budgets']['enable_budget_expenses'] && ($_USER->isAdmin() || $_COMPANY->getAppCustomization()['budgets']['allow_grouplead_to_edit_expense'])) { ?>
            <button class="btn btn-sm btn-link"
            <?php if(Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['BUDGET_EXPENSE_CREATE_BEFORE'], $groupid)){ 
            $call_method_parameters = array(
                $encGroupId,
                $_COMPANY->encodeId(0),
            );

            $call_other_method = base64_url_encode(json_encode(
                array (
                    "method" => "addUpdateExpenseInfoModal",
                    "parameters" => $call_method_parameters
                )
            )); // base64_url_encode for prevent js parsing error
            ?>
                onclick="loadDisclaimerByHook('<?=$_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['BUDGET_EXPENSE_CREATE_BEFORE'])?>','<?=$encGroupId?>', 0, '<?=$call_other_method?>')"
                <?php } else {?>
                    onclick="addUpdateExpenseInfoModal('<?=$encGroupId?>','<?=$_COMPANY->encodeId(0);?>');" 
                    <?php } ?>
                    >
                + <?= gettext("Expense Entry");?>
            </button>
            <?php } ?>
            </p>

        </div>

    </div>
    </div>

	<div class="col-md-12 textalign-table mb-2" id="show-request-table" style="display:none;">
		<div class="table-responsive ">
			<table class="display table table-hover compact text-left" width="100%" id="budget_requests" summary="This table displays the list of budget requests">
				<thead style="margin-left:10px;">
					<tr>
						<th width="5%" scope="col"><span style="visibility:hidden;"><?= gettext("Request Detail");?></span></th>
                        <th width="15%" scope="col"><?= gettext("Planned Use Date");?></th>
                        <th width="15%" scope="col"><?=$_COMPANY->getAppCustomization()['group']['name-short']?></th>
                        <th width="15%" scope="col"><?= gettext("Requester");?></th>
                        <th width="15%" scope="col"><?= gettext("Request Date");?></th>
                        <th width="15%" scope="col"><?= gettext("Requested/Approved Amount");?></th>
						<th width="15%" scope="col"><?= gettext("Action");?></th>
                        <th width="15%" scope="col"><?= gettext("Expense");?></th>				    	
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>
    </div>

    <div class="m-2">&nbsp;</div>
    <?php if($_COMPANY->getAppCustomization()['budgets']['enable_budget_expenses']){ ?>    
	<div class="col-md-12">
		
		<div class="col-md-6">
			<h3 class="expense_head"><?= gettext("Expenses");?></h3>
		</div>
        
		<div class="col-md-6">
            <div class="pull-right row">
                <div>
                        <?php
                        $page_tags = 'manage_budget_expense';
                        ViewHelper::ShowTrainingVideoButton($page_tags);
                        ?>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <hr class="linec mt-0">
        <?php include_once __DIR__.'/../templates/group_expnses_table.template.php'; ?>

	</div>
<?php } ?>
</div>

</div>
</div>
<!-- Request Budget -->
<div id="budget_request_form"></div>

<script>
	function format ( d ) {
		return '<table cellpadding="4" cellspacing="0" border="0" width="100%" style="padding-left:50px;text-align: left;">'+
			'<tr >'+
				'<td width="20%" ><?= gettext('Description'); ?>:</td>'+
				'<td>'+d.description+'</td>'+
			'</tr>'+
            '<tr >'+
            '<td width="20%" ><?= gettext('Purpose'); ?>:</td>'+
            '<td>'+d.purpose+'</td>'+
            '</tr>'+
			'<tr>'+
				'<td  width="20%"><?= gettext('Approved date'); ?>:</td>'+
				'<td>'+d.approved_date+'</td>'+
			'</tr>'+
			'<tr>'+
				'<td  width="20%"><?= gettext('Approver'); ?>:</td>'+
				'<td>'+d.approver+'</td>'+
			'</tr>'+
			'<tr>'+
				'<td  width="20%" ><?= addslashes(gettext("Approver's Comment")); ?>:</td>'+
				'<td>'+d.approver_comment+'</td>'+
			'</tr>'
            + window.tskp.custom_fields.renderBudgetRequestCustomFields(d.custom_fields || [])
           <?php if($_COMPANY->getAppCustomization()['budgets']['attachments']['enabled']){ ?>
            + window.tskp.attachments.renderBudgetRequestAttachments(d.attachments || []) 
            <?php } ?>
            +'</table>';
}

var dt ;
var isPastRequestsRendered = 0;
function pastRequests() {
 // Check if DataTable is already initialized and destroy it
 if ($.fn.DataTable.isDataTable('#budget_requests')) {
        $('#budget_requests').DataTable().clear().destroy();
    }
var x = parseInt(localStorage.getItem("local_variable_for_table_pagination"));
 dt = $('#budget_requests').DataTable( {
    processing: true,
    serverSide: true,
    bInfo:false,
    pageLength:x,
    language: {
        searchPlaceholder: "Search by budget purpose..",
        url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
    },
    columnDefs: [
        { targets: [-1,-2], orderable: false }
    ],
    ajax: {
        url: "ajax_budget?getBudgetRequests=<?= $encGroupId;?>&year=<?= $year; ?>&chapterid=<?= $_COMPANY->encodeId($chapterid) ?>",
        type: "POST"
    },
    columns: [
        {
            "class":          "details-control",
            "orderable":      false,
            "data":           null,
            "defaultContent": '<button type="button" class="btn-no-style details-control-btn"></button>'
        },
        { "data": "planned_use_date" },
        { "data": "erg" },
        { "data": "requester" },
        { "data": "request_date" },
        { "data": "requested_amount" },
        { "data": "action" },
        { "data": "is_expense_created" }
    ],
    order: [[1, 'desc']],
    initComplete: function(settings, json) {
        $(".deluser").popConfirm({content: ''});
    }
} );

screenReadingTableFilterNotification('#budget_requests',dt);
}
$(document).ready(function() {
 
    // Array to track the ids of the details displayed rows
    var detailRows = [];
 
    $('#budget_requests tbody').on( 'click keypress', 'tr td button.details-control-btn', function (event) {        
        var tr = $(this).closest('tr');
        var row = dt.row( tr );
        var idx = $.inArray( tr.attr('id'), detailRows );
 
        if ( row.child.isShown() ) {
            tr.removeClass( 'details' );
            row.child.hide();
 
            // Remove from the 'open' array
            detailRows.splice( idx, 1 );
            $(this).attr({ "aria-expanded":"false" });	
        }
        else {
            tr.addClass( 'details' );
            row.child( format( row.data() ) ).show();
 
            // Add to the 'open' array
            if ( idx === -1 ) {
                detailRows.push( tr.attr('id') );
            }
            $(this).attr({ "aria-expanded":"true" });	
        }

        // On each draw, loop over the `detailRows` array and show any child rows
        dt.on( 'draw', function () {
            $.each( detailRows, function ( i, id ) {
                $('#'+id+' td button.details-control-btn').trigger( 'click' );                
            } );
        } );

        if (event.keyCode === 13) {
            $(this).click();
        }

    } ); 

} );	

$('#budget_requests').on( 'length.dt', function ( e, settings, len ) {
    localStorage.setItem("local_variable_for_table_pagination", len);
} );
$("#li-budget").addClass("active2"); 
</script>
