<?php
  $event ??= null;
  $event_chapter_ids = explode(',', $event?->val('chapterid') ?? '0');
  $event_chapter_id = $_SESSION['context_chapterid'] ? $_SESSION['context_chapterid'] : (((count($event_chapter_ids) > 1) ? '0' : $event_chapter_ids[0]) ?: ($_SESSION['budget_by_chapter'] ?? 0));
  if (isset($_SESSION['context_chapterid'])) {
	unset($_SESSION['context_chapterid']);
  }
?>
<style>
	.select2-container .select2-selection--single {
		height: calc(1.5em + .75rem + 2px);
	}
	.select2-container--default .select2-selection--single .select2-selection__rendered {
		line-height: 33px;
	}
	.select2-container--default .select2-selection--single {
		border: 1px solid #dbdbdb;
	}
	.field-text .p-1 {
    font-size: 14px;
}
.field-text .p-1.pull-right.text-right.remove_button {
    position: absolute;
    right: 0px;
    top: 0px;
}
.field-text input.form-control {
    font-size: 14px;
}
.field-text select.form-control {
    font-size: 14px;
}
.label-text label{
	width: auto;
    padding-right: 5px;
}
.tool-tip-title-text{
	padding-left:0
}
</style>
<!-- Modal for Add expense  -->
<div id="expenseModal" class="modal fade">
    <div aria-label="<?= gettext("Add Expense Detail");?>" class="modal-dialog modal-xl modal-dialog-w1000" aria-modal="true" role="dialog">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
            	<h2 id="modal-title" class="modal-title"><?= gettext("Add Expense Detail");?></h2>
                <button aria-label="<?= gettext("close");?>" type="button" id="btn_close" class="close" data-dismiss="modal">&times;</button>
                
            </div>
				<div class="modal-body" >
				<div class="row m-2" >
				<form class="form-horizontal" id="budgetExpForm">
				<input type="hidden" name="usesid" value="0">
				  <input type="hidden" name="event_id" value="<?= $event?->id() ? $_COMPANY->encodeId($event?->id()) : '' ?>">
				  <div class="form-group">
					<p class="control-label col-sm-12"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
					</div>
				<div class="form-group">
					<label class="control-label col-sm-12" for="dateInput"><?= gettext("Date");?><span style="color:red"> *</span></label>
					<div class="col-sm-12">
						<input type="text" class="form-control" name="date" id="dateInput" placeholder="YYYY-MM-DD" readonly required value="<?= $event?->getEventDate() ?? '' ?>">
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-12"><?= gettext("Select a Scope");?></label>
					<div class="col-sm-12">
						<select aria-label="<?= gettext("Select a Scope");?>" id="chapterid" type="text" class="form-control" name="chapterid">
							<?php if ($_USER->canManageBudgetGroup($groupid) || $event?->loggedinUserCanManageEventBudget()) { ?>
							<option
							  value="<?= $_COMPANY->encodeId(0) ?>"
							  <?= $event_chapter_id == '0' ? 'selected' : '' ?>
							>
							  <?= $group->val('groupname')." ".$_COMPANY->getAppCustomization()['group']['name-short']; ?>
							</option>
							<?php } ?>
							<?php 
							if (!$event || ($event?->val('listids') == 0)) {
							foreach ($chapters as $ch) { ?>
							<?php if ($_USER->canManageBudgetGroupChapter($groupid,$ch['regionids'],$ch['chapterid']) || $event?->loggedinUserCanManageEventBudget()) {
								?>
								<option
								  value="<?= $_COMPANY->encodeId($ch['chapterid']) ?>"
								  <?= ($ch['chapterid'] == $event_chapter_id) ? 'selected' : '' ?>
								>&emsp;<?= htmlspecialchars($ch['chaptername']); ?>
								</option>
							<?php } ?>
							<?php } ?>
							<?php } ?>
						</select>	
					</div>
				</div>


						<div class="form-group">
						  <label class="control-label col-sm-12"><?= gettext("Event Type");?></label>
						  <div class="col-sm-12">
							  <select aria-label="<?= gettext('Event Type');?>" class="form-control" name="eventtype" id="sel1">
								<option value=""><?= gettext("Select Type");?></option>
								<?php 	if(count($type)>0){ ?>
								<?php		for($ty=0;$ty<count($type);$ty++){ ?>
												<option
												  value="<?= $type[$ty]['type'] ?>"
												  <?= $event?->val('eventtype') === $type[$ty]['typeid'] ? 'selected' : '' ?>
												>
												  <?= $type[$ty]['type']; ?>
												</option>
								<?php		} ?>
								<?php 	}else{ ?>
												<option value="">- <?= gettext("No type to select");?> -</option>
								<?php	} ?>
							  </select>
							</div>
						</div>

						<div class="form-group">
							<label class="control-label col-sm-12"><?= gettext("Charge Code");?></label>
							<div class="col-sm-12">
								<select aria-label="<?= gettext('Charge Code');?>" class="form-control" name="charge_code_id" id="charge_code_id">
								  <option value="0"><?= gettext("Select a Charge Code");?></option>
									<?php 	if(count($charge_codes)>0){ ?>
									<?php		foreach($charge_codes as $code){ ?>
													<option value="<?= $code['charge_code_id'] ?>"><?= htmlspecialchars($code['charge_code']); ?></option>
									<?php		} ?>
									<?php 	}else{ ?>
													<option value="0">- <?= gettext("No charge code to select");?> -</option>
									<?php	} ?>
								</select>
							  </div>
						  </div>

						<div class="form-group">
							<label class="control-label col-sm-12" for="comment"><?= gettext("Description");?><span style="color:red"> *</span></label>
							<div class="col-sm-12">
								<textarea class="form-control" name="description" rows="2" id="comment" required><?=
								  $event?->val('eventtitle') ?? ''
								?></textarea>
							</div>
						</div>
					<?php if($_COMPANY->getAppCustomization()['budgets']['show_expense_budget']){ ?>
                        <div class="form-group">
                            <label class="control-label col-sm-12" for="budgeted_amount"><?= gettext("Planned / Budgeted Amount");?> (<?= $_COMPANY->getCurrencySymbol(); ?>)</label>
                            <div class="col-sm-12">
                                <input type="number" name="budgeted_amount" class="form-control" id="budgeted_amount" value="0" placeholder="" min="0" <?= $isActionDisabledDuringApprovalProcess ? 'readonly' : (empty($usesBudgetRec) ? '' : 'readonly') ?>>
                                <?php if ($isActionDisabledDuringApprovalProcess) { ?>
                                    <div class="alert-warning p-3 text-small">
                                        <?=sprintf(gettext('This event is currently in the approval process or has been approved. %1$s changes are not permitted. To make changes, request the event approver to deny the approval.'), gettext('Budget'))?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
					<?php } ?>
                    <?php if($_COMPANY->getAppCustomization()['budgets']['other_funding']){ ?>
                        <div class="form-group">
						<div class="label-text">
                            <label class="control-label col-sm-12"><?= gettext("Funding Source");?></label>							  
							  <i data-html="true" tabindex="0" class="fa fa-info-circle" data-toggle="tooltip" data-placement="top" title="<ul class='tool-tip-title-text'>
								<li><?= Budget2::GetAllocatedBudgetDefinition(); ?></li>
							  	<li><?= Budget2::GetOtherFundingDefinition(); ?></li>
								</ul> ">
                              </i>
							</div>
                            <div class="col-sm-12">
                                <select aria-label="<?= gettext('Funding Source');?>" class="form-control" name="funding_source" id="funding_source">
                                    <option value="allocated_budget"><?= gettext("Allocated Budget");?></option>
                                    <option value="other_funding"><?= gettext("Other Funding Source");?></option>
                                </select>
                            </div>
                        </div>
                    <?php } ?>

						<div class="form-group">
							<label class="control-label col-sm-12" for="usedamount"><?= gettext("Expense Total");?> (<?= $_COMPANY->getCurrencySymbol(); ?>)</label>
							<div class="col-sm-12">
								<input type="number" name="usedamount" class="form-control" id="usedamount" placeholder="" value="0" required min="0">
							</div>
						</div>

						<?php
						if($_COMPANY->getAppCustomization()['budgets']['vendors']['enabled']){ ?>
						<div class="form-group">
							<label class="control-label col-sm-12"><?= gettext("Vendor Name");?></label>
							<div class="col-sm-12">
							<select aria-label="<?= gettext('Vendor Name');?>" class="form-control" id="vendor_name" name="vendor_name" style="width: 100%;"><option value=""></option></select>
							</div>
						</div>
						<?php } ?>

						<?php
                        // $isActionDisabledDuringApprovalProcess is not applicable to expense type custom fields
                        // so turn it off without impacting the set values
                        if (isset($isActionDisabledDuringApprovalProcess)) {
                            $isActionDisabledDuringApprovalProcess_bkup = $isActionDisabledDuringApprovalProcess;
                            $isActionDisabledDuringApprovalProcess = false;
                        }

                        include(__DIR__ . '/../templates/event_custom_fields.template.php');

                        // restore the original value
                        if (isset($isActionDisabledDuringApprovalProcess_bkup)) {
                            $isActionDisabledDuringApprovalProcess = $isActionDisabledDuringApprovalProcess_bkup;
                            unset($isActionDisabledDuringApprovalProcess_bkup);
                        }
                        ?>

						<div class="form-group">
							<label class="control-label col-sm-12" for="subitem"><?= gettext("Sub Item");?> &emsp;<i role="button" tabindex="0" aria-label="<?= gettext("Add sub item");?>" id="subitem" class="fa fa-plus-circle add_button"></i></label>
                            <div class="col-md-12">
                                <div class="col-12 p-0 m-0" style="background-color: #f8f8f8;">
                                    <div class="field_wrapper">
                                        <!-- Sub item rendering start -->
                                        <!-- Sub item rendering end -->
                                    </div>
								</div>
							</div>
						</div>

						<?= ExpenseEntry::CreateEphemeralTopic()->renderAttachmentsComponent('v9') ?>

						<div class="form-group">
							<div class="col-md-12 text-center mt-3">
								<button type="button" onclick="addUpdateExpenseInfo('<?=$_COMPANY->encodeId($groupid);?>');" class="btn btn-primary prevent-multi-clicks"><?= gettext("Submit");?></button>
								<button type="button" class="btn btn-default btn-secondary" data-dismiss="modal"><?= gettext("Close");?></button>
							</div>
						</div>
					</form>
				</div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
	    var maxField = 10; //Input fields increment limitation
        var addButton = $('.add_button'); //Add button selector
        var wrapper = $('.field_wrapper'); //Input field wrapper
        var fieldHTML = '<div class="col-md-12 m-0 py-3 border field-text"><?php if(!empty($allowed_foreign_currencies)){ ?><div class="checkbox show-foregin-currency small"><label><input class="" onclick="showhideForeignCurrencyInput(this)" type="checkbox"> <input type="hidden"  name="ispaidinforeigncurrency[]" value="0">  <?= gettext("Foreign currency expense")?></label> <div class="foregin-currency-input" style="display:none"></div></div><?php } ?><input type="hidden" value="<?= $_COMPANY->encodeId(0); ?>" name="itemids[]"><div class="col-md-3 p-1"><?= addslashes(gettext("Expense Type"));?><select aria-label="<?= gettext("Expense Type");?>" class="form-control" name="expensetypeid[]"><option value=""><?= addslashes(gettext("Select Expense Type"));?></option> <?php 	if(count($expense_type)>0){ ?> <?php		for($ex=0;$ex<count($expense_type);$ex++){ ?><option value="<?= $_COMPANY->encodeId($expense_type[$ex]['expensetypeid']) ?>"><?= addslashes(htmlspecialchars($expense_type[$ex]['expensetype'])); ?></option> <?php		} ?> <?php 	}else{ ?><option value="">- <?= addslashes(gettext("No expense type to select"));?> -</option> <?php	} ?> </select></div><div class="col-md-5 p-1"><?= addslashes(gettext("Details"));?><input type="text" name="item[]" aria-label="<?= gettext("Details");?>" class="form-control" placeholder="<?= addslashes(gettext("Item name"));?>" /></div><div class="col-md-2 p-1"><?= addslashes(gettext("Budget"));?> (<?= $_COMPANY->getCurrencySymbol();?>)<input aria-label="<?= gettext("Budget");?>" type="number" name="item_budgeted_amount[]" class="form-control" id="item_budgeted_amount" placeholder="" min="0" value="0.00" <?= $isActionDisabledDuringApprovalProcess ? 'readonly' : ''; ?> /></div><div class="col-md-2 p-1"><?= addslashes(gettext("Expense"));?> (<?= $_COMPANY->getCurrencySymbol();?>)<input aria-label="<?= gettext("Expense");?>" type="number" name="item_used_amount[]" class="form-control" id="item_used_amount" placeholder="" min="0" value="0.00"/></div><div class="p-1 pull-right text-right remove_button">&nbsp;<a role="button" aria-label="<?= addslashes(gettext("Remove field"));?> 1" href="javascript:void(0);" class="remove-sub-item-btn" title="<?= addslashes(gettext("Remove field"));?>"><i class="fa fa-times-circle fa-lg" aria-hidden="true"></i></a></div>  </div>'; //New input field html
        var x = 1; //Initial field counter is 1
        $(addButton).click(function(){ //Once add button is clicked
            if(x < maxField){ //Check maximum number of input fields
				$(".remove-sub-item-btn").each(function(i){
					var  newNumber = i+2;
					$(this).attr('aria-label', '<?= addslashes(gettext("Remove field"));?> '+newNumber+'');
				});

                x++; //Increment field counter
                $(wrapper).prepend(fieldHTML); // Add field html

            }
        });
        $(wrapper).on('click', '.remove_button', function(e){ //Once remove button is clicked
            e.preventDefault();
            $(this).parent('div').remove(); //Remove field html
            x--; //Decrement field counter
        });




		setTimeout(() => {
            $('.add_button').trigger("click");
        }, 100);
		
    });
</script>
<script>
	$('#vendor_name').select2({
		data: <?= json_encode($allVendors); ?>,
		tags: true,
		minimumInputLength: 1,
		placeholder: "Vendor Name here...",
		language: {
			inputTooShort: function() {
				return '';
			}
		}
	});

$('#expenseModal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus')
});

$("#expenseModal").scroll(function() {
  var y = $(this).scrollTop();
  if (y > 10) {
	$("#ui-datepicker-div").hide();
  } else {
	$('#dateInput').click(function() {
		$("#ui-datepicker-div").show();
	});
    
  }
});

//On Enter Key...
   $(function(){
        $(".add_button").keypress(function (e) {
            if (e.keyCode == 13) {
                $(this).trigger("click");
            }
        });
    });


$(document).on('keydown', '#dateInput',    function(e) {
	$.datepicker.customKeyPress(e);
});
$.extend($.datepicker, {
	customKeyPress: function (event) {
		let inst = $.datepicker._getInst(event.target);
		let isRTL = inst.dpDiv.is(".ui-datepicker-rtl");
		switch (event.keyCode) {
			case 37:    // LEFT --> -1 day
				$('body').css('overflow','hidden');
			$.datepicker._adjustDate(event.target, (isRTL ? +1 : -1), "D");
			break;
		// case 16:    // UPP --> -7 day
		// 	$('body').css('overflow','hidden');
		// 	$.datepicker._adjustDate(event.target, -7, "D");
		// 	break;
		case 38:    // UPP --> -7 day
			$('body').css('overflow','hidden');
			$.datepicker._adjustDate(event.target, -7, "D");
			break;
		case 39:    // RIGHT --> +1 day
			$('body').css('overflow','hidden');
			$.datepicker._adjustDate(event.target, (isRTL ? -1 : +1), "D");
			break;
		case 40:    // DOWN --> +7 day
			$('body').css('overflow','hidden');
			$.datepicker._adjustDate(event.target, +7, "D");
			break;
		}
		$('body').css('overflow','hidden');
	}
});

$(document).ready(function() {
    $("body").tooltip({ selector: '[data-toggle=tooltip]' });
});


$(document).on('keydown', '.select2-search__field',    function(e) {
	$("#hidden_div_for_notification").html('');
	$("#hidden_div_for_notification").removeAttr('aria-live'); 

	setTimeout(() => {
		if(!$(".select2-results__message").is(":visible")){
			let itemCount = $('#select2-vendor_name-results').find('li:visible').length;			
			$("#hidden_div_for_notification").attr({role:"status","aria-live":"polite"});
			document.getElementById('hidden_div_for_notification').innerHTML=+itemCount+" <?= gettext('record found.') ?>"; 		
		}
	}, 300);
	
});
</script>