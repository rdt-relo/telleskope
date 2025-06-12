<div id="importTeamsData" class="modal fade">
    <div aria-label="<?= $pageTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title" class="modal-title"><?= $pageTitle; ?></h2>
                <button aria-label="<?= gettext('close');?>" type="button" id="btn_close" class="close"  data-dismiss="modal" onclick="manageTeams('<?= $_COMPANY->encodeId($groupid); ?>')">&times;</button>
            </div>
            <div id="modal_body">
                <div class="modal-body">
                    <div class="col-md-12">
                        <form  class="" id="import_CSV_file_form">
                            <div class="form-group">
                                <label for="import_file"><?= sprintf(gettext("Select a CSV file to import %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></label>
                                <input type="file" accept=".csv" class="form-control p-1" id="import_file" name="import_file" />
                                <a class="small" href="<?= $csvFormat; ?>" download="sample_team.csv"><?=gettext('Download CSV Sample')?></a>
                            </div>
                    <?php if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?>
                            <div class="form-group">
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input" id="auto_extend_capacity" name="auto_extend_capacity" value="1"><?= gettext('Auto extend user role capacity as needed');?>
                                    </label>
                                </div>
                            </div>
                    <?php } ?>
                            <div class="form-group">
                                <p>
                                    <small class="dark-gray">
                                        <p><?= gettext("Notes :") ?></p>
                                        <ul class="pl-2">
                                        <li><?= sprintf (gettext('The CSV file should contain the following columns: %1$s.'),
                                                'external_id, email, role_name' . ($_COMPANY->getAppCustomization()['chapter']['enabled'] ? ', chapter_name' : '' ) . ', team_name, role_title'); ?></li>
                                        <li><?= sprintf (gettext('Please enter unique values in %1$s column. If %1$s already exists, user will be added to the existing %2$s.'),
                                                'team_name', Team::GetTeamCustomMetaName($group->getTeamProgramType())); ?></li>
                                        <li><?= sprintf (gettext('When adding users to %1$s, we identify users by external_id or email column. If both an external_id and email are provided, we prioritize the external_id for finding the user and use email as a backup. external_id a unique identifier such as employee number, employee ID, etc.'),
                                                Team::GetTeamCustomMetaName($group->getTeamProgramType())); ?></li>

                                        <?php if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?>
                                        <li><?= sprintf (gettext('Please provide the description only once for the role that belongs to the system role type Mentor. Description is a required column, please enter valid %1$s description in this column.'),
                                                Team::GetTeamCustomMetaName($group->getTeamProgramType())); ?></li>
                                        <li><?= sprintf (gettext('Please provide the hashtags only once for the role that belongs to the system role type Mentor. Hashtags are an optional column. If adding hashtags, please use the format #hashtag and separate them with semi-colon (;) if you need to include multiple ones.')); ?></li>
                                        <?php } ?>

                                        <li><?= sprintf (gettext('role_name is a required column, and its value should contain a valid role name defined in %1$s configuration.'),
                                                $_COMPANY->getAppCustomization()['group']['name']); ?></li>
                                        <li><?= sprintf (gettext('role_title is an optional column, and it can be left blank.')); ?></li>
                                        </ul>
                                    </small>
                                </p>
                            </div>
                        </form>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-12" id="showImportResponseStats"></div>
                    <div class="col-md-12" id="showImportFailedData"></div>
                    <div class="clearfix"></div>
                </div>
                <div class="modal-footer text-center" style='text-align:center !important;display: block;'>
                    <button type="button" class="btn btn-affinity prevent-multi-clicks" onclick="submitImportTeamData('<?= $_COMPANY->encodeId($groupid); ?>')" ><?= gettext("Import");?></button>
                    <button aria-label="<?= gettext('close');?>" type="button" class="btn btn-affinity" onclick="manageTeams('<?= $_COMPANY->encodeId($groupid); ?>')" data-dismiss="modal" ><?= gettext("Close");?></button>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    function submitImportTeamData(g){
        $(document).off('focusin.modal');
        var formdata = $('#import_CSV_file_form')[0];
		var finaldata  = new FormData(formdata);
		finaldata.append("groupid",g);
		$.ajax({
			url: './ajax_talentpeak.php?submitImportTeamData=1',
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
                            $('#import_file').val('');
                        }
					});
				} catch(e) { swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false}); }
			}
		});
    }

$('#importTeamsData').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus')
});

$('#importTeamsData').on('hidden.bs.modal', function () {   
    $('#newTeamBtn').focus();
})
</script>
