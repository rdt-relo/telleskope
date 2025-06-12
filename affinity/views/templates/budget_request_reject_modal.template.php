<div class="modal" tabindex="-1" id="rejectBudgetModal">
  <div aria-label="<?= gettext('Reject Budget Request')?>" class="modal-dialog" aria-modal="true" role="dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title"><?= gettext('Reject Budget Request')?></h2>
        <button id="btn_close" type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div>
            <form action="" id="budgetRequestRejectForm">
            <div class="form-group">
                <label class="control-lable col-sm-12" for="date"><?= gettext('Comment')?></label>
                <div class="col-sm-12">
                    <textarea  class="form-control" id="approver_comment" name="approver_comment" placeholder="<?= gettext('Any Comment')?>" required ></textarea>
                </div>
            </div>
            <?= $budget_request->renderAttachmentsComponent('v12') ?>
            </form>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary prevent-multi-clicks" id="request_submit-btn"  onclick="rejectBudgetRequest('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId($request_id); ?>')"><?= gettext('Submit')?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= gettext('Close')?></button>
      </div>
    </div>
  </div>
</div>
<script>
    $('#rejectBudgetModal').on('shown.bs.modal', function () {
        $('#btn_close').trigger('focus')
    });
</script>