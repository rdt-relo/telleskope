<div id="importSurveyModal" class="modal fade">
    <div aria-label="<?= $pageTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="modal-title" class="modal-title"><?= $pageTitle; ?> <sup class="beta-label"><?= gettext('Beta');?></sup></h4>
                <button aria-label="<?= gettext('close');?>" type="button" id="btn_close" class="close" data-dismiss="modal" onclick="getGroupSurveys('<?= $_COMPANY->encodeId($groupid); ?>')">&times;</button>
            </div>
            <div id="modal_body">
                <div class="modal-body">
                    <div class="col-md-12">
                        <form  class="" id="import_CSV_file_form">
                            <div class="form-group">
                                <p class="beta-description my-3"><?= gettext('This feature is in beta testing and can be used to import data into new surveys. Please do not import data into existing active surveys.');?></p>
                                <label for="event_series"><?= gettext("Select a CSV file to import surveys");?></label>
                                <input type="file" accept=".csv" class="form-control" id="import_file" name="import_file" />
                                <p>
                                    <small class="dark-gray">
                                        <?= gettext('To get a template for import, please download the survey. The only columns processed by this import utility are Email, Response Date and Survey Responses. User email addresses must already be provisioned in the system.')?>
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
                    <button type="button" class="btn btn-affinity prevent-multi-clicks" onclick="submitImportSurveysData('<?= $_COMPANY->encodeId($groupid); ?>', '<?= $_COMPANY->encodeId($surveyid)?>')" ><?= gettext("Import");?></button>

                <?php if($groupid){?>
                        <button type="button" class="btn btn-affinity" onclick="getGroupSurveys('<?= $_COMPANY->encodeId($groupid); ?>')"  data-dismiss="modal" ><?= gettext("Close");?></button>
                   <?php }else{?>
                        <button type="button" class="btn btn-affinity" onclick="getAdminSurveys()"  data-dismiss="modal" ><?= gettext("Close");?></button>
                <?php }?>                  

                </div>
            </div>
        </div>
    </div>
</div>


<script>

$('#surveyid').multiselect({
    nonSelectedText: "<?= gettext("Choose a survey")?>",
    numberDisplayed: 1,
    disableIfEmpty: true,
    enableFiltering: true,
    maxHeight: 400
});


    function submitImportSurveysData(g,s){
        $(document).off('focusin.modal');
        var formdata = $('#import_CSV_file_form')[0];
		var finaldata  = new FormData(formdata);
		finaldata.append("groupid",g);
        finaldata.append("surveyid",s);
		$.ajax({
			url: './ajax_survey.php?submitImportSurveysData=1',
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

$('#importSurveyModal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus')
});
</script>
