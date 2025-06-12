<div id="importTeamRegistrationData" class="modal fade">
    <div aria-label="<?= $pageTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title" class="modal-title"><?= $pageTitle; ?></h2>
                <button aria-label="<?= gettext('close');?>" type="button" id="btn_close" class="close"  data-dismiss="modal" onclick="getUnmatchedUsersJoinRequests('<?= $_COMPANY->encodeId($groupid); ?>')">&times;</button>
            </div>
            <div id="modal_body">
                <div class="modal-body">
                    <div class="col-md-12">
                        <form  class="" id="import_CSV_registration_file_form">
                            <div class="form-group">
                                <label for="import_registration_file"><?= sprintf(gettext("Select a CSV file to import %s Registrations"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?></label>
                                <input type="file" accept=".csv" class="form-control p-1" id="import_registration_file" name="import_file" />
                                <a class="small" href="data:text/csv;charset=utf-8,external_id,email,role_name<?= ($_COMPANY->getAppCustomization()['chapter']['enabled'] ? ",chapter_name" : '' ); ?>,role_capacity%0AE74234,john.doe@domain.com,Mentor<?= ($_COMPANY->getAppCustomization()['chapter']['enabled'] ? ",ChapterName" : '' ); ?>,1%0AE74235,jane.doe@domain.com,Mentee<?= ($_COMPANY->getAppCustomization()['chapter']['enabled'] ? ",ChapterName" : '' ); ?>,2" download="sample_registrations.csv"><?=gettext('Download CSV Sample')?></a>
                                <br>
                                <p>
                                    <small class="dark-gray">
                                        <p><?= gettext("Notes :") ?></p>
                                        <ul class="pl-2">
                                        <li><?= sprintf (gettext('The CSV file should contain the following columns: %1$s.'),
                                                'external_id, email, role_name' . ($_COMPANY->getAppCustomization()['chapter']['enabled'] ? ', chapter_name' : '' ) . ', role_capacity'); ?></li>
                                        <li><?= sprintf (gettext('When adding users to %1$s, we identify users by external_id or email column. If both an external_id and email are provided, we prioritize the external_id for finding the user and use email as a backup. external_id a unique identifier such as employee number, employee ID, etc.'),
                                                Team::GetTeamCustomMetaName($group->getTeamProgramType()). ' registration '); ?></li>
                                        <li><?= sprintf (gettext('role_name is a required column, and its value should contain a valid role name defined in %1$s configuration.'),
                                                $_COMPANY->getAppCustomization()['group']['name']); ?></li>
                                        <li><?=  (gettext('role_capacity must be below the defined maximum for the role. Maximum applies if not provided.')); ?></li>
                                        <li><?=  (gettext('Registration survey data import not supported.')); ?></li>
                                        </ul>
                                    </small>
                                </p>
                            </div>
                            <div class="form-check-inline">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input" value="1" name="send_emails" checked><?=gettext('Send registration emails')?>
                                    <i tabindex="0" aria-label="<?=gettext('By default, the system will send out registration email on each sucessful import. Uncheck this checkbox to disable sending automatic emails.')?>" class="fa fa-info-circle small" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="<?=gettext('By default, the system will send out registration email on each sucessful import. Uncheck this checkbox to disable sending automatic emails.')?>"></i>
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-12" id="showImportResponseStats"></div>
                    <div class="col-md-12" id="showImportFailedData"></div>
                    <div class="clearfix"></div>
                </div>
                <div class="modal-footer text-center" style='text-align:center !important;display: block;'>
                    <button type="button" class="btn btn-affinity prevent-multi-clicks" onclick="submitImportTeamRegistrationData('<?= $_COMPANY->encodeId($groupid); ?>')" ><?= gettext("Import");?></button>
                    <button aria-label="<?= gettext('close');?>" type="button" class="btn btn-affinity" onclick="getUnmatchedUsersJoinRequests('<?= $_COMPANY->encodeId($groupid); ?>')" data-dismiss="modal" ><?= gettext("Close");?></button>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    function submitImportTeamRegistrationData(g){
        $(document).off('focusin.modal');
        var formdata = $('#import_CSV_registration_file_form')[0];
		var finaldata  = new FormData(formdata);
		finaldata.append("groupid",g);
		$.ajax({
			url: './ajax_talentpeak.php?submitImportTeamRegistrationData=1',
			type: 'POST',
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success: function(data) {
				try {
					let jsonData = JSON.parse(data);
                    
					swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {

                        if (jsonData.status){
                            var response = jsonData.data;
                            showImportResponseStats(response);
                            if(response.totalFailed>0){
                                showImportFailedData(response.failed);
                            }
                            $('#import_registration_file').val('');
                        }
					});
				} catch(e) { swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false}); }
			}
		});
    }

$('#importTeamRegistrationData').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus');
   $('.modal').addClass('js-skip-esc-key');
});
$(function () {
    $('[data-toggle="tooltip"]').tooltip()
});
</script>
