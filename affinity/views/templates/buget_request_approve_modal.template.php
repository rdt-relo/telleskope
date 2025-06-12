<div class="modal" tabindex="-1" id="approveBudgetModal">
  <div aria-label="<?= gettext('Approve Budget Request')?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title"><?= gettext('Approve Budget Request')?></h2>
        <button aria-label="<?= gettext("close");?>" id="btn_close" type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <div class="mx-5">
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
        <div class="col-md-12">
          <form action="" id="approveBudgetRequestForm">
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
                  <div class="col-md-9"><strong><?= $parentAvailableBudgetAfterMoveTitle ?></strong> (<?= $_COMPANY->getCurrencySymbol()?>):</div>
                  <div class="col-md-3"><span id="budget_available_at_company_level_update"><?=$_USER->formatAmountForDisplay($parentAvailableBudget); ?></span></div>
              </div>
              <div class="row row-no-gutters" id ="budget_allocated_at_group_level_update_div" style="display:none;">
                <div class="col-md-9"><strong><?= $childAllocatedBudgetAfterMoveTitle ?></strong> (<?= $_COMPANY->getCurrencySymbol()?>):</div>
                <div class="col-md-3"><span id="budget_allocated_at_group_level_update"><?=$_USER->formatAmountForDisplay($childAllocatedBudget); ?></span></div>
              </div>
              <br>
            </div>

            <div class="form-group">
              <label class="control-lable col-sm-12" for="approver_comment"><?= gettext('Comment')?></label>
              <div class="col-sm-12">
                <textarea  class="form-control" id="approver_comment" name="approver_comment" placeholder="<?= gettext('Approval Comment')?>" required ></textarea>
              </div>
            </div>
            <?= $budget_request->renderAttachmentsComponent('v12') ?>
          </form>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="request_submit-btn" class="btn btn-primary prevent-multi-clicks" onclick="approveBudgetRequest('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId($request_id); ?>')"><?= gettext('Submit')?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= gettext('Close')?></button>
      </div>
    </div>
  </div>
</div>

<script>
    $('#approveBudgetModal').on('shown.bs.modal', function () {
        $('#btn_close').trigger('focus')
    });

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