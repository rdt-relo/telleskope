<div id="budgetApprovalModel" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Approve Budget Request</h4>
                <button type="button" id="btn_close" class="close" aria-hidden="true" data-dismiss="modal">&times;</button>
                
            </div>
            <div class="modal-body">
                <div class="container">
                    <div class="mx-3">
                        <div class="row row-no-gutters">
                            <div class="col-md-6"><strong><?= gettext('Budget Year')?></strong>:</div>
                            <div class="col-md-6"><?= $budgetYear['budget_year_title']; ?></div>
                        </div>
                        <div class="row row-no-gutters">
                            <div class="col-md-6"><strong><?= $parentAvailableBudgetTitle?></strong>:</div>
                            <div class="col-md-6"><?= $_COMPANY->getCurrencySymbol(). ' '. $_USER->formatAmountForDisplay($parentAvailableBudget);?> </div>
                        </div>
                        <div class="row row-no-gutters">
                            <div class="col-md-6"><strong><?= $childAllocatedBudgetTitle?></strong>:</div>
                            <div class="col-md-6"><?= $_COMPANY->getCurrencySymbol(). ' '. $_USER->formatAmountForDisplay($childAllocatedBudget);?> </div>
                        </div>
                        <div class="row row-no-gutters">
                            <div class="col-md-6"><strong><?= $childAvailableBudgetTitle?></strong>:</div>
                            <div class="col-md-6"><?= $_COMPANY->getCurrencySymbol(). ' '. $_USER->formatAmountForDisplay($childAvailableBudget);?> </div>
                        </div>
                        <div class="row row-no-gutters">
                            <div class="col-md-6"><strong><?= gettext('Amount Requested') ?></strong>:</div>
                            <div class="col-md-6"><?= $_COMPANY->getCurrencySymbol(). ' '. $_USER->formatAmountForDisplay($budgeRequest ? $budgeRequest['requested_amount'] : '0.00');?> </div>
                        </div>
                    </div>
                    <br>

                    <form id="approve-budget-form" class="form-horizontal">
                        <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                        <input type="hidden" name="request_status" value="<?= (int)$_GET['request_status']; ?>">
                        <input type="hidden" name="request_id" value="<?= $_COMPANY->encodeId($request_id); ?>">
                        <div class="form-group">
                            <label class="control-lable col-sm-12" for="amount_approved"><?= gettext('Amount Approved')?> (<?= $_COMPANY->getCurrencySymbol(); ?>)</label>
                            <div class="col-sm-12">
                            <input type="number" class="form-control" id="amount_approved" name="amount_approved" onkeyup="showUpdatedAvailableBudget()" placeholder="0.00" min="1" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check-inline ">
                            <label class="form-check-label col-sm-12">
                                <input type="checkbox" class="form-check-input" id="move_parent_budget_to_child" name="move_parent_budget_to_child" value="1" onchange="showUpdatedAvailableBudget();"><?= $moveBudgetParentTochildTitle;?>
                            </label>
                            </div>
                            <div class="col-sm-12" style="color: red; font-size:smaller;"><?=$moveBudgetNote?></div>
                        </div>

                        <div class="mx-3">
                            <div class="row row-no-gutters" id ="budget_available_at_company_level_update_div" style="display:none;">
                                <div class="col-md-9"><strong><?= $parentAvailableBudgetAfterMoveTitle ?> (<?= $_COMPANY->getCurrencySymbol()?>)</strong>: </div>
                                <div class="col-md-3"><span id="budget_available_at_company_level_update"><?=$_USER->formatAmountForDisplay($parentAvailableBudget); ?></span></div>
                            </div>
                            <div class="row row-no-gutters" id ="budget_allocated_at_group_level_update_div" style="display:none;">
                                <div class="col-md-9"><strong><?= $childAllocatedBudgetAfterMoveTitle ?> (<?= $_COMPANY->getCurrencySymbol()?>) </strong>: </div>
                                <div class="col-md-3"><span id="budget_allocated_at_group_level_update"><?=$_USER->formatAmountForDisplay($childAllocatedBudget); ?></span></div>
                            </div>
                            <br>
                        </div>

                        <div class="form-group">
                            <label class="control-lable col-sm-12" for="approver_comment"><?= gettext('Comment')?></label>
                            <div class="col-sm-12">
                            <textarea  class="form-control" id="approver_comment" name="approver_comment" placeholder="<?= gettext('Any Comment')?>" required ></textarea>
                            </div>
                        </div>


                        <div class="form-group">
                            <div class="text-center col-md-12 pt-3">
                                <button type="button" id="request_submit-btn" onclick="submitApproveForm();" class="btn btn-info"><?= gettext('Approve')?></button>
                                <button type="button" class="btn btn-info" data-dismiss="modal" onclick="window.location.reload()"><?= gettext("Close")?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
     function showUpdatedAvailableBudget(){

     let approvedValue = parseFloat($("#amount_approved").val());
     if (isNaN(approvedValue) || approvedValue < 0.01) {
         $("#request_submit-btn").prop("disabled",true);
     } else {
         $("#request_submit-btn").prop("disabled",false);
     }

      if ($('#move_parent_budget_to_child').is(":checked")) {
        if (!isNaN(approvedValue) && approvedValue > 0) {
          let cbvalue = formatAmountForDisplayJS((parseFloat(<?=$parentAvailableBudget?>) - approvedValue),2);
          let gbvalue = formatAmountForDisplayJS((parseFloat(<?=$childAllocatedBudget?>) + approvedValue),2);

          $("#budget_available_at_company_level_update").text(cbvalue);
          $("#budget_allocated_at_group_level_update").text(gbvalue);

          if (parseFloat(cbvalue) < 0.01) {
              $("#budget_available_at_company_level_update").css("color", "red");
              $("#request_submit-btn").prop("disabled",true);
          } else {
              $("#budget_available_at_company_level_update").css("color", "green");
              $("#request_submit-btn").prop("disabled",false);
          }

          $('#budget_available_at_company_level_update_div').show();
          $('#budget_allocated_at_group_level_update_div').show();
        } else {
          $("#budget_available_at_company_level_update").text('<?= $_USER->formatAmountForDisplay($parentAvailableBudget)?>');
          $("#budget_allocated_at_group_level_update").text('<?= $_USER->formatAmountForDisplay($childAllocatedBudget)?>');
        }
        
      } else {
       $('#budget_available_at_company_level_update_div').hide();
       $('#budget_allocated_at_group_level_update_div').hide();
      }
    }
</script>




