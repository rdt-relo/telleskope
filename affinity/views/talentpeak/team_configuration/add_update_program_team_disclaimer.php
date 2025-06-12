<div id="loadProgramDisclaimerModal" class="modal fade" role="dialog">
    <div aria-label="<?= $modalTitle; ?>" class="modal-dialog modal-lg">
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><?= $modalTitle ;?></h4>
          <button id="btn_close" type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <div class="col-md-12">
              <form id="addUpdateProgramDisclaimerFrom" class="form-horizontal" method="post" action="">
                <div class="form-group">
                  <label class="control-label col-md-12" for="redactor_content_request_email"><?= gettext("Disclaimer"); ?>:</label>
                  <div id="post-inner" class="col-md-12">
                      <textarea class="form-control" name="program_disclaimer" rows="10" id="redactor_content_program_disclaimer" maxlength="8000" placeholder="<?= gettext('Type in your disclaimer message ...'); ?>"><?= htmlspecialchars($group->getProgramDisclaimer()) ?></textarea>
                  </div>
                </div>
              </form>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary prevent-multiple-submit" onclick="addUpdateProgramDisclaimer('<?= $_COMPANY->encodeId($groupid); ?>')"><?= gettext("Submit")?></button>
            <button type="button" class="btn btn-primary" data-dismiss="modal"><?= gettext("Close")?></button>
        </div>
      </div>
    </div>
  </div>
<script>
$('#touchpointConfigModal').on('shown.bs.modal', function () {
    $('.close').trigger('focus');
});

$(document).ready(function(){
  $('#redactor_content_program_disclaimer').initRedactor('redactor_content_program_disclaimer',false,['counter']);
    redactorFocusOut('#btn_close'); // function used for focus out from redactor when press shift + tab.
});

function addUpdateProgramDisclaimer(g) {
  var formdata =	$('#addUpdateProgramDisclaimerFrom')[0];
	var finaldata  = new FormData(formdata);
  finaldata.append("groupid",g);
	var submitButton = $(".prevent-multiple-submit");
	submitButton.prop('disabled', 'disabled');
	$.ajax({
		url: 'ajax_talentpeak.php?addUpdateProgramDisclaimer=1',
		type: 'POST',
		data: finaldata, // get Data in html page by "managesection" Var
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
      submitButton.removeAttr('disabled');
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						window.location.reload();
					}
				});
				
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
		},
		error: function(error){
			submitButton.removeAttr('disabled');
		}
	});
}

$('#loadProgramDisclaimerModal').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus');
});

</script>