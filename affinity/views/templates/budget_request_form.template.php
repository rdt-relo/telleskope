<div id="budget_request" class="modal fade">
    <div aria-label="<?= $modalTitle;?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
            	<h2 id="modal-title" class="modal-title"><?= $modalTitle;?></h2>
                <button aria-label="<?= gettext("close");?>" type="button" id="btn_close" class="close" data-dismiss="modal">&times;</button>                
            </div>
            <div class="modal-body">
				<div class="row m-1">
					<form autocomplete="off" class="form-horizontal" id="budget-request-form">
                        <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
					<div >
					<div class="form-group">
							<p class="control-lable col-sm-12"><?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
					</div>
						<div class="form-group">
							<label class="control-lable col-sm-12"><?= gettext("For");?></label>
							<div class="col-sm-12">
								<select aria-label="<?= gettext('For');?>" id="chapterid" type="text" class="form-control" name="chapterid" style="font-size:small;border-radius: 5px; margin: 0 auto;">
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
						</div>
						<div class="form-group">
							<label class="control-lable col-sm-12" for="amount"><?= gettext("Amount");?> (<?= $_COMPANY->getCurrencySymbol(); ?>)<span style="color:red"> *</span></label>
							<div class="col-sm-12">
								<input type="number" value="<?= $request_id ? $_USER->formatAmountForDisplay($edit['requested_amount'],'') : ''; ?>" class="form-control" id="amount" name="requested_amount" placeholder="eg. 150.25" min="0" required>
							</div>
						</div>
						
						<div class="form-group">
							<label class="control-lable col-sm-12" for="purpose"><?= gettext("Purpose");?><span style="color:red"> *</span></label>
							<div class="col-sm-12">
								<input class="form-control" maxlength="24" name="purpose" value="<?= $request_id ? $edit['purpose'] : '' ?>" id="purpose" required placeholder="Purpose ... up to 24 characters" />
							</div>
						</div> 
						<div class="form-group">
							<label class="control-lable col-sm-12" for="description"><?= gettext("Description");?></label>
							<div class="col-sm-12">
								<textarea class="form-control"  maxlength="255"  name="description" rows="4" id="description" required placeholder="Description ... up to 250 characters"><?= $request_id ? $edit['description'] : '' ?></textarea>
							</div>
						</div> 
						<div class="form-group">
							<label class="control-lable col-sm-12" for="start_date"><?= gettext("Budget Use Date");?><span style="color:red"> *</span></label>
							<div class="col-sm-12">
								<input type="text" class="form-control" value="<?= $request_id ? $edit['need_by'] : '' ?>" id="start_date" placeholder="YYYY-MM-DD" name="need_by" readonly required>
							</div>
						</div>
						
						<?php include(__DIR__ . '/../templates/event_custom_fields.template.php'); ?>

						<?php if (isset($budget_request)) { ?>
						<?= $budget_request->renderAttachmentsComponent('v10') ?>
						<?php } else { ?>
						  <?= BudgetRequest::CreateEphemeralTopic()->renderAttachmentsComponent('v11') ?>
						<?php } ?>

						<div class="form-group">
							<div class="col-md-12 text-center mt-3">
								<button type="button" id="request_budget_btn" onclick="requestBudget('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($request_id);?>');" class="btn btn-primary"><?= $request_id ? gettext("Update Request"): gettext("Request") ;?></button>
								<button type="button" class="btn btn-default btn-secondary" data-dismiss="modal"><?= gettext("Close");?></button>
							</div>
						</div>
					</div>
					</form>
				</div>
            </div>
            
        </div>

    </div>
</div>

<script>
    $('#budget_request').on('shown.bs.modal', function () {
        $('#btn_close').trigger('focus')
    });
</script>
