<style>
    .hide{
        display:none;
    }
    .progress_bar{
        background-color: #efefef;
        margin: 5px 0px;
        padding: 15px;
    }
    .dynamic-button-container {
      position: relative;
      top:-56px;
    }
    .dataTables_wrapper .dataTables_filter input {
      width: 300px;
    }
    #updateBulkAction {
	width: 100%;
}
</style>
<div class="col-md-12">
    <div class="progress_bar form-group hide" id="progress_bar">
        <p class="progress_bar_status"><?= sprintf(gettext('Updating  <span id ="totalBulkRecored"></span> %s(s). Please wait.'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?></p>
        <div class="progress">
            <div class="progress-bar progress-bar-animated" id="prgress_bar" style="width:0%"></div>
        </div>
        <div class="text-center progress_status" aria-live="polite"></div>
    </div>

    <div class="mb-3 p-4 progress_done progress_bar hide">
        <strong><?= gettext("Success");?> :</strong>
        <p class="pl-3" id="updateDone" style="color: green;"></p>
        <p class="pl-3" id="againSent" style="color: darkgreen"></p>
        <strong><?= gettext("Failed");?>: </strong>
        <p class="pl-3" style="color: red;" id="updateFailed"></p>
        <div class="co-md-12 text-center pt-3">
            <button type="button" id="close_show_progress_btn" class="btn btn-affinity hide" onclick="closeEventInvitesStats(),manageTeams('<?= $_COMPANY->encodeId($groupid)?>');"><?= gettext("Close");?></button>
        </div>
    </div>

<!-- 
    <div class="mb-3 p-4 progress_bar progress_done_for_bulk_delete progress_bar hide">
          <div class="co-md-12 text-center pt-3">
            <button type="button" class="btn btn-affinity" onclick="closeEventInvitesStats(),manageTeams('<?= $_COMPANY->encodeId($groupid)?>');"><?= gettext("Close");?></button>
        </div>
    </div> -->
    
</div>
<div class="col-md-12 mt-3 px-0">
  <?php
    include(__DIR__ . "/team_table_listing.template.php");
  ?>
  
</div>

<div id="deleteTeam" class="modal">
          <div aria-label="<?= gettext('Confirmation'); ?>" class="modal-dialog" aria-modal="true" role="dialog">
                <div class="modal-content">
                    <div class="modal-header text-center">
                        <h2 class="modal-title"><?= gettext('Confirmation'); ?></h2>
                    </div>
                    <div class="modal-body">
                    <label for="confirm_delete_team_text">  <p><?= sprintf(gettext("I understand the %s and all of its associated data will be permanently deleted from the system. Type '%s' below to provide your consent to delete."),Team::GetTeamCustomMetaName($group->getTeamProgramType()), 'I agree')?></p>
                    </label>
                        <div class="form-group">                            
                          <input type="hidden" id="teamid">
                          <label><?= gettext("Confirmation"); ?>:</label>
                            <input type="text" class="form-control" id="confirm_delete_team_text" onkeyup="initDeleteTeamResponse()" placeholder="I agree" name="confirm_delete_team">
                        </div>
                    </div>
                    <div class="modal-footer text-center">
                    
                    <button class="btn btn-primary" id="deleteSubmitButton" disabled onclick="deleteTeamPermanently('<?= $_COMPANY->encodeId($groupid);?>');" >Submit</button>
                    <button type="button" class="btn btn-affinity" data-dismiss="modal"><?= gettext('Cancel'); ?></button>
                    </div>
                </div>
    </div>
</div>


<div id="confirmChangeModal" class="modal fade"  tabindex="-1">
    <div aria-label="<?= gettext('Confirmation'); ?>" class="modal-dialog" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header text-center">
                <h2 class="modal-title">Confirmation</h2>
            </div>
            <div class="modal-body">
                <p id="confirmationMessage"></p>
                <input type="hidden" id="bulk_action">
                <div class="form-group">
                    <label for="confirmChange"><?= gettext("Confirmation"); ?>:</label>
                    <input type="text" class="form-control" id="confirmChange" onkeyup="initBulkConfirmation()" placeholder="I agree" name="confirmChange">
                  </div>
            </div>
            <div class="modal-footer text-center">
                <button class="btn btn-affinity" id="proceedAction" disabled onclick="updateTeamBulkAction('<?= $_COMPANY->encodeId($groupid)?>')" ><?= gettext("Proceed")?></button>
                <button type="button" class="btn btn-affinity" onclick='$("#updateBulkAction").val("");' data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
  function deleteTeamConfirmModal(gid,tid) {
    $('#teamid').val(tid);
    $('#deleteTeam').modal({
        backdrop: 'static',
        keyboard: false
    });
  }

  function initDeleteTeamResponse(){
    var v = $("#confirm_delete_team_text").val();
    if (v == 'I agree'){
      $('#deleteSubmitButton').prop('disabled', false);
    }else{
      $('#deleteSubmitButton').prop('disabled', true);
    }
  }
  function initBulkConfirmation(){
    var v = $("#confirmChange").val();
    if (v == 'I agree'){
      $( "#proceedAction" ).prop( "disabled", false );
    } else {
      $( "#proceedAction" ).prop( "disabled", true );
    }
  }

  $('#deleteTeam').on('shown.bs.modal', function () {
      $('#confirm_delete_team_text').trigger('focus');
  });

  $('#confirmChangeModal').on('shown.bs.modal', function () {
      $('#confirmChange').trigger('focus');
  });

  $('#deleteTeam').on('hidden.bs.modal', function (e) {
      var teamid = $('#teamid').val();
      $('#teamBtn'+teamid).focus();
  })
  </script>

