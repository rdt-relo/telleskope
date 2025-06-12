<?php $event ??= null; ?>
<style>
.red {
     background-color: #fbc7c7;
}

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

<div id="expenseModal" class="modal fade" >
    <div aria-label="<?= gettext("Update Expense Detail");?>" class="modal-dialog modal-xl modal-dialog-w1000" aria-modal="true" role="dialog">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title" class="modal-title"><?= gettext("Update Expense Detail");?></h2>
                <button aria-label="<?= gettext("close");?>" type="button" id="btn_close" class="close"  data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
				<div class="row m-2">
					<form class="form-horizontal" id="budgetExpForm">
						<input type="hidden" name="usesid" value="<?= $usesid; ?>">
						<input type="hidden" name="event_id" value="<?= $event?->id() ? $_COMPANY->encodeId($event?->id()) : '' ?>">
						<div class="form-group">
							<p class="control-label col-sm-12"><?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
						</div>
						<div class="form-group">
							<label class="control-label col-sm-12" for="dateInput"><?= gettext("Date");?><span style="color:red"> *</span></label>
							<div class="col-sm-12">
								<input type="text" class="form-control" value="<?= $edit['date']; ?>" name="date" id="dateInput" placeholder="YYYY-MM-DD" readonly required>
							</div>
						</div>
						
						<div class="form-group">
							<label class="control-label col-sm-12"><?= gettext("Select a Scope");?></label>
							<div class="col-sm-12">
								<select aria-label="<?= gettext("Select a Scope");?>" type="text" class="form-control" name="chapterid" id="chapterid">
									<?php if ($_USER->canManageBudgetGroup($groupid) || $event?->loggedinUserCanManageEventBudget()) { ?>
									<option value="<?= $_COMPANY->encodeId(0) ?>" <?= $edit['chapterid']==0 ? 'selected' : ''; ?> ><?= $group->val('groupname')." ".$_COMPANY->getAppCustomization()['group']['name-short']; ?></option>
									<?php } ?>
									<?php 
									if(!$event || $event?->val('listids') == 0){
									for($i=0;$i<count($chapters);$i++){
										$selc = "";
										if ($chapters[$i]['chapterid'] == $edit['chapterid']){
											$selc = "selected ";
										}	
									?>
										
									<?php if ($_USER->canManageBudgetGroupChapter($groupid,$chapters[$i]['regionids'],$chapters[$i]['chapterid']) || $event?->loggedinUserCanManageEventBudget()) { ?>
										<option  value="<?= $_COMPANY->encodeId($chapters[$i]['chapterid']) ?>" <?= $selc; ?>>&emsp;<?= htmlspecialchars($chapters[$i]['chaptername']); ?></option>
									<?php } ?>
									<?php } ?>
									<?php } ?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-sm-12"><?= gettext("Event Type");?></label>
							<div class="col-sm-12">
								<select aria-label="<?= gettext("Event Type");?>" class="form-control" name="eventtype" id="sel1">
									<option value=""><?= gettext("Select Type");?></option>
						<?php 	if(count($type)>0){ ?>
									<?php
									for($ty=0;$ty<count($type);$ty++){							
										$eve_type = htmlspecialchars_decode($type[$ty]['type']);
										?>
										<option value="<?= $type[$ty]['type'] ?>" <?= $edit['eventtype'] == $eve_type ? "selected" : ""; ?>><?= $type[$ty]['type']; ?></option>
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
								<select aria-label="<?= gettext("Charge Code");?>" class="form-control" name="charge_code_id" id="scharge_code_id">
								  <option value="0"><?= gettext("Select a Charge Code");?></option>
				  <?php 	if(count($charge_codes)>0){ ?>
				  <?php		foreach($charge_codes as $code){ ?>
								  <option value="<?= $code['charge_code_id'] ?>" <?= $edit['charge_code_id']== $code['charge_code_id'] ? 'selected' : ''; ?> ><?= htmlspecialchars($code['charge_code']); ?></option>
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
								<textarea class="form-control" name="description" rows="2" id="comment" required><?= $edit['description']; ?></textarea>
							</div>
						</div>
					<?php if($_COMPANY->getAppCustomization()['budgets']['show_expense_budget']){ ?>
                        <div class="form-group">
                            <label class="control-label col-sm-12" for="budgeted_amount"><?= gettext("Planned / Budgeted Amount");?> (<?= $_COMPANY->getCurrencySymbol(); ?>)</label>
                            <div class="col-sm-12">
                                <input type="number" name="budgeted_amount"  value="<?= $_USER->formatAmountForDisplay($edit['budgeted_amount'],''); ?>"  class="form-control" id="budgeted_amount" placeholder="100" <?= $isActionDisabledDuringApprovalProcess ? 'readonly' : (empty($usesBudgetRec) ? '' : 'readonly') ?>>
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
                            <label class="control-label col-sm-12" for="funding_source"><?= gettext("Funding Source");?></label> <i data-html="true" tabindex="0" class="fa fa-info-circle" data-toggle="tooltip" data-placement="top" title="<ul class='tool-tip-title-text'>
								<li><?= Budget2::GetAllocatedBudgetDefinition(); ?></li>
							  	<li><?= Budget2::GetOtherFundingDefinition(); ?></li>
								</ul> ">
							</i>
							</div>
                            <div class="col-sm-12">
                                <select aria-label="<?= gettext("Funding Source");?>" class="form-control" name="funding_source" id="funding_source">
                                    <option value="allocated_budget" <?php if ($edit['funding_source'] ==  "allocated_budget") { ?>selected="selected"<?php } ?>><?= gettext("Allocated Budget");?></option>
                                    <option value="other_funding" <?php if ($edit['funding_source'] ==  "other_funding") { ?>selected="selected"<?php } ?>><?= gettext("Other Funding Source");?></option>
                                </select>
                            </div>
                        </div>
                    <?php } ?>
						<div class="form-group">
							<label class="control-label col-sm-12" for="usedamount"><?= gettext("Expense Total");?> (<?= $_COMPANY->getCurrencySymbol(); ?>)</label>
							<div class="col-sm-12">
								<input type="number" name="usedamount"  value="<?= $_USER->formatAmountForDisplay($edit['usedamount'],''); ?>"  class="form-control" id="usedamount" placeholder="100" required>
                            </div>
						</div>

						<?php
						if($_COMPANY->getAppCustomization()['budgets']['vendors']['enabled']){ ?>
						<div class="form-group">
							<label class="control-label col-sm-12"><?= gettext("Vendor Name");?></label>
							<div class="col-sm-12">
							<select aria-label="<?= gettext("Vendor Name");?>" class="form-control" id="vendor_name" name="vendor_name" style="width: 100%;"><option value="<?= $edit['vendor_name'] ?>"><?= $edit['vendor_name'] ?></option></select>
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
							<label class="control-label col-sm-3" for="email"><?= gettext("Sub Item");?>&emsp;<i role="button" aria-label="<?= gettext("Add sub item");?>" tabindex="0" class="fa fa-plus-circle add_button" aria-hidden="true"></i></label>
                            <div class="col-md-12">
                                <div class="col-12 p-0 m-0" style="background-color: #f8f8f8;">
                                    <div class="field_wrapper">
                                <!-- Sub item rendering start -->
			<?php	if(count($sub)){ ?>
			<?php		for($s=0;$s<count($sub);$s++){
							$enc_sub_itemid = $_COMPANY->encodeId($sub[$s]['itemid']);
			?>
						<div class="form-group mb-0" id="sub-itme<?=$enc_sub_itemid;?>">
							<div class="col-md-12 m-0 py-3 border field-text">
							<div class="checkbox show-foregin-currency small">
								<?php if(!empty($allowed_foreign_currencies)){ ?>
									<label>
										<input class="" onclick="showhideForeignCurrencyInput(this)" type="checkbox" <?= $sub[$s]['foreign_currency'] ? 'checked disabled' : ''; ?> />
										<input type="hidden" name="ispaidinforeigncurrency[]" value="<?= $sub[$s]['foreign_currency'] ? '1' : '0'; ?>" />
										<?= gettext("Foreign currency expense")?>
									</label>
									<div class="foregin-currency-input" style="display: <?= $sub[$s]['foreign_currency'] ? 'block' : 'none'; ?>;">
									<?php if($sub[$s]['foreign_currency']>0){ ?>
										<div class="col-md-4 p-1">
											<?= addslashes(gettext("Foreign Currency"));?>
											<select aria-label="<?= gettext("Foreign Currency");?>" class="form-control" name="foreigncurrency[]">
                                                <?php if ($sub[$s]['foreign_currency']) { ?>
                                                    <option value="<?= $sub[$s]['foreign_currency'] ?>" selected>
                                                        <?= $sub[$s]['foreign_currency'].' ('.Budget2::FOREIGN_CURRENCIES[$sub[$s]['foreign_currency']]['name'].')' ?>
                                                    </option>
                                                <?php } else { ?>
												    <option value="" <?= $sub[$s]['foreign_currency'] ? 'disabled' : ''; ?>>
                                                        <?= addslashes(gettext("Select Currency"));?>
                                                    </option>
												    <?php foreach($allowed_foreign_currencies as $currency){ ?>
												    <option value="<?= $currency['cc'] ?>">
                                                        <?= $currency['cc'].' ('.Budget2::FOREIGN_CURRENCIES[$currency['cc']]['name'].')'; ?>
                                                    </option>
												    <?php } ?>
                                                <?php } ?>
											</select>
										</div>
										<div class="col-md-4 p-1">
											<span onclick="allowConversionRateToChange(this);"><?= addslashes(gettext("Conversion Rate"));?> <i class="fa fas fa-edit" aria-hidden="true"></i></span>
											<input type="text" name="currencyconversionrate[]" onfocusout="$(this).prop('readonly', true);updateForeignCurrencyAmountOnRateChange(this);" class="form-control" id="conversion_rate" placeholder="<?=addslashes(gettext('Conversion rate e.g. 0.1132'))?>" min="0" value="<?= number_format($sub[$s]['conversion_rate'],9); ?>" required readonly />
										</div>
										<div class="col-md-4 p-1"><?= addslashes(gettext("Foreign Currency Amount"));?>
											<input aria-label="<?= gettext("Foreign Currency Amount");?>" type="number" name="foreigncurrencyamount[]" onchange="calculateHomeCurrencyAmount(this)" class="form-control" placeholder="<?= addslashes(gettext("Amount e.g. 10"));?>"  min="0" value="<?= $_USER->formatAmountForDisplay($sub[$s]['foreign_currency_amount'],''); ?>" required />
										</div>
									<?php } ?>
									</div>
								<?php } ?>
								</div>

								<input type="hidden" value="<?= $enc_sub_itemid; ?>" name="itemids[]">
								<div class="col-md-3 p-1" >
									<?= gettext("Expense Type");?>
									<select aria-label="<?= gettext("Expense Type");?>" class="form-control" name="expensetypeid[]">
										<option value=""><?= gettext("Select Expense Type");?></option> 
								<?php 	if(count($expense_type)>0){ ?> 
								<?php	for($ex=0;$ex<count($expense_type);$ex++){ 
											$sel = '';
											if ($sub[$s]['expensetypeid'] == $expense_type[$ex]['expensetypeid']){
												$sel = 'selected';
											}	
								?>
										<option value="<?= $_COMPANY->encodeId($expense_type[$ex]['expensetypeid']) ?>" <?= $sel; ?> ><?= htmlspecialchars($expense_type[$ex]['expensetype']); ?></option>
								 <?php	} ?>
								 <?php 	}else{ ?>
										<option value="">- <?= gettext("No expense type to select");?> -</option> 
								<?php	} ?> 
									</select>
								</div>
								<div class="col-md-5 p-1"><?= gettext("Details");?><input type="text" aria-label="<?= gettext("Details");?>" name="item[]"  value="<?= htmlspecialchars($sub[$s]['item']); ?>" class="form-control"  placeholder="Item name"></div>
								<div class="col-md-2 p-1"><?= gettext("Budget");?> (<?= $_COMPANY->getCurrencySymbol(); ?>) <input aria-label="<?= gettext("Budget");?>" type="number" name="item_budgeted_amount[]" value="<?= $_USER->formatAmountForDisplay($sub[$s]['item_budgeted_amount'],''); ?>" class="form-control" id="item_budgeted_amount" placeholder="100" min="0"  <?= $isActionDisabledDuringApprovalProcess ? 'readonly' : ''; ?> ></div>

								<div class="col-md-2 p-1"><?= gettext("Expense");?> (<?= $_COMPANY->getCurrencySymbol(); ?>) <input aria-label="<?= gettext("Expense");?>" type="number" name="item_used_amount[]" value="<?= $_USER->formatAmountForDisplay($sub[$s]['item_used_amount'],''); ?>" class="form-control" id="item_used_amount" placeholder="100" min="0"></div>
								
								<div class="p-1 pull-right text-right remove_button">&nbsp;<a href="javascript:void(0);" class="remove-sub-item-btn" aria-label="<?= addslashes(gettext("Remove field"));?> <?= $s+1 ?>" title="Remove field"><i class="fa fa-times-circle fa-lg" aria-hidden="true"></i></a></div>
							</div>
						</div>
			<?php		} ?>		
			<?php	} ?>
                                <!-- Sub item rendering end -->
                                    </div>
                                </div>
                            </div>
                        </div>

						<?= $expense_entry->renderAttachmentsComponent('v5') ?>

						<div class="form-group">
							<div class="col-md-12 text-center mt-3">
								<button type="button" onclick="addUpdateExpenseInfo('<?=$_COMPANY->encodeId($edit['groupid']);?>');" class="btn btn-primary prevent-multi-clicks"><?= gettext("Update");?></button>
								<button type="button" class="btn btn-primary" data-dismiss="modal"><?= gettext("Cancel");?></button>
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
    var fieldHTML = '<div class="col-md-12 m-0 py-3 border field-text"><?php if(!empty($allowed_foreign_currencies)){ ?><div class="checkbox show-foregin-currency small"><label><input class="" onclick="showhideForeignCurrencyInput(this)" type="checkbox"> <input type="hidden"  name="ispaidinforeigncurrency[]" value="0">  <?= gettext("Foreign currency expense")?></label> <div class="foregin-currency-input" style="display:none"></div></div><?php } ?><input type="hidden" value="<?= $_COMPANY->encodeId(0); ?>" name="itemids[]"><div class="col-md-3 p-1"><?= addslashes(gettext("Expense Type"));?><select aria-label="<?= gettext("Expense Type");?>" class="form-control" name="expensetypeid[]"><option value=""><?= addslashes(gettext("Select Expense Type"));?></option> <?php 	if(count($expense_type)>0){ ?> <?php		for($ex=0;$ex<count($expense_type);$ex++){ ?><option value="<?= $_COMPANY->encodeId($expense_type[$ex]['expensetypeid']) ?>"><?= addslashes(htmlspecialchars($expense_type[$ex]['expensetype'])); ?></option> <?php		} ?> <?php 	}else{ ?><option value="">- <?= addslashes(gettext("No expense type to select"));?> -</option> <?php	} ?> </select></div><div class="col-md-5 p-1"><?= addslashes(gettext("Details"));?><input type="text" name="item[]" aria-label="<?= gettext("Details");?>" class="form-control" placeholder="<?= addslashes(gettext("Item name"));?>" required /></div><div class="col-md-2 p-1"><?= addslashes(gettext("Budget"));?> (<?= $_COMPANY->getCurrencySymbol();?>)<input aria-label="<?= gettext("Budget");?>" type="number" name="item_budgeted_amount[]" class="form-control" id="item_budgeted_amount" placeholder="" required min="0" value="0.00" <?= $isActionDisabledDuringApprovalProcess ? 'readonly' : ''; ?>/></div><div class="col-md-2 p-1"><?= addslashes(gettext("Expense"));?> (<?= $_COMPANY->getCurrencySymbol();?>)<input aria-label="<?= gettext("Expense");?>" type="number" name="item_used_amount[]" class="form-control" id="item_used_amount" placeholder="" required min="0" value="0.00"/></div><div class="p-1 pull-right text-right remove_button">&nbsp;<a role="button" href="javascript:void(0);" class="remove-sub-item-btn" title="<?= addslashes(gettext("Remove field"));?>"><i class="fa fa-times-circle fa-lg" aria-hidden="true"></i></a></div>  </div>'; //New input field html
    var u = 1; //Initial field counter is 1
    $(addButton).click(function(){ //Once add button is clicked
        if(u < maxField){ //Check maximum number of input fields
            u++; //Increment field counter
            $(wrapper).prepend(fieldHTML); // Add field html
        }
    });
    $(wrapper).on('click', '.remove_button', function(e){ //Once remove button is clicked
        e.preventDefault();
        $(this).parent('div').remove(); //Remove field html
        u--; //Decrement field counter
    });
	
	
	// Delete sub items
	$('.confirm-delete').hover(function(){
		$(this).parent().addClass("red");
	},
	function(){
      $(this).parent().removeClass("red");
	});
	
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

$(document).ready(function() {
	<?php if(!empty($usesBudgetRec)){ ?>
  	$("#chapterid option:not(:selected)").prop("disabled", true);
	<?php } ?>
});

//On Enter Key...
$(function(){
        $(".add_button").keypress(function (e) {
            if (e.keyCode == 13) {        
                $(this).trigger("click");				
            }
        });
    });

trapFocusInModal("#expenseModal");
$(document).ready(function() {
    $("body").tooltip({ selector: '[data-toggle=tooltip]' });
});
</script>
