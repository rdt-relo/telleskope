<div id="updateStatusModal" class="modal" tabindex="-1">
    <div aria-label="<?= gettext("Update Status");?>" class="modal-dialog" aria-modal="true" role="dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><?= gettext("Update Status");?></h5>
          <button type="button" id="btn_close" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form id="updateTaskStatusForm">
            <input type="hidden" id="_teamid" name="teamid">
            <input type="hidden" id="_taskid" name="taskid">
            <input type="hidden" id="_groupid" name="groupid">
            <div class="form-group">
              <label><?= gettext("Action");?></label>
              <select class="form-control" name="updateStatus" id="updateStatus" tabindex="0">
                  <option value=""><?= gettext("Select an action");?></option>
                  <?php foreach(Team::GET_TALENTPEAK_TODO_STATUS as $typeKey => $typeValue){ ?>
                      <option value="<?= $_COMPANY->encodeId($typeKey); ?>"><?= $typeValue; ?></option>
                  <?php } ?>
              </select>
          </div>
          <div class="form-group">
            <label>
              <input type="checkbox" name="send_email_notification" value="1">
              <?= gettext('Check the box to send email notification') ?>
            </label>
          </div>
          </form>
        </div>
        <div class="modal-footer ">
          <div class=" text-center">
            <button type="button" class="btn btn-affinity tskp_submit_btn" onclick="updateTaskStatus('<?=$_COMPANY->encodeId($groupid);?>','<?= $taskType; ?>')"><?= gettext("Update");?></button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= gettext("Close");?></button>
          </div>
          
        </div>
      </div>
    </div>
  </div>

<script>
  $('#updateStatusModal').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus')
});
  </script>