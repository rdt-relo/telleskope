<div id="joinRequestSettingModal" class="modal fade" role="dialog">
    <div aria-label="<?= $modal_title ?>" class="modal-dialog">
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title"><?= $modal_title ?></h2>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <div class="col-md-12">
              <?php
              $mail_to_leader = $joinRequestSetting['mail_to_leader'] ?? true;
              $mail_to_specific_emails = $joinRequestSetting['mail_to_specific_emails'] ?? false;
              $specific_emails = $joinRequestSetting['specific_emails'] ?? '';
              ?>
                  <div class="form-group form-check">
                      <input class="form-check-input" type="checkbox" id="leaders" name="leaders" <?= $mail_to_leader ? 'checked' : ''; ?>>
                      <label class="form-check-label" for=""><?= gettext('All leaders with Manage Role');?></label>
                  </div>
                  <div class="form-group form-check">
                      <input class="form-check-input" type="checkbox" id="specific_emails" name="specific_emails" <?= $mail_to_specific_emails ? 'checked' : ''; ?>>
                      <label class="form-check-label" for=""><?= gettext('Specified Email Addresses');?></label>
                  </div>
                  <div class="form-group">
                    <input type="text" id="emailInput" name="emailInput" placeholder="Enter upto 3 email addresses" class="form-control" value='<?= $specific_emails ?>' style="display: <?= $mail_to_specific_emails ? 'block':'none'?>;">
                  </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="updatejoinRequestSetting('<?= $_COMPANY->encodeId($groupid); ?>')"><?= gettext("Update")?></button>
            <button type="button" class="btn btn-primary" data-dismiss="modal"><?= gettext("Close")?></button>
        </div>
      </div>
    </div>
  </div>
<script>
  $(document).ready(function() {
    $('#joinRequestSettingModal').on('shown.bs.modal', function () {
        $('.close').trigger('focus');
    });

    const checkbox1 = document.getElementById('leaders');
    const checkbox2 = document.getElementById('specific_emails');
    const emailInput = document.getElementById('emailInput');
    checkbox2.addEventListener('change', function () {
      if(this.checked){
        emailInput.style.display = "block";
      }else{
        emailInput.value = "";
        emailInput.style.display = "none";
      }
    });
  });

function updatejoinRequestSetting(groupid){
  let leaders = document.getElementById('leaders').checked;
	let specific_emails = document.getElementById('specific_emails').checked;
	let emailInputValue = document.getElementById('emailInput').value;
  if(specific_emails && emailInputValue.length < 1){
    swal.fire({title: 'Error', text: "Enter atleast 1 email id."});
    return;
  }
	$.ajax({
		url: 'ajax.php?updateJoinRequestSetting=1',
			type: "POST",
			data: {'leaders':leaders, 'specific_emails':specific_emails, 'emailInputValue':emailInputValue,'groupid':groupid},
        success : function(data) {
          let jsonData = JSON.parse(data);
          if(jsonData.status){
            swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
              $('#joinRequestSettingModal').modal('hide');
					  });
          }else{
            swal.fire({title: jsonData.title,text:jsonData.message});
          }
        },
      error : function(){
        swal.fire({title: 'Error', text: "Unknown error."});
      }
	});

}
</script>