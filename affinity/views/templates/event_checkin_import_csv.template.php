<div tabindex="-1" id="importCSVForCheckInModal" class="modal fade">
    <div aria-label="<?= gettext("Import Check Ins from a csv file") ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= gettext("Import Check Ins from a csv file") ?></h4>
                <button onclick="eventRSVPsForCheckIn('<?= $_COMPANY->encodeId($eventid); ?>','')" id="btn_close1" aria-label="close" type="button" class="close" data-dismiss="modal">×</button>

            </div>
            <div id="modal_body">
                <div class="modal-body">
                    <div class="col-md-12">
                        <form  class="" id="import_CSV_file_form_checkins">
                            <div class="form-group">
                                <label for="event_series"><?= gettext("Select a CSV file to import");?></label>
                                <input type="file" accept=".csv" class="form-control" id="import_file" name="import_file" />
                                <p>
                                    <small class="dark-gray">
                                        <?= gettext('Note : The CSV file should contain the following three columns: email, firstname and lastname.')?>
                                        <a href="data:text/csv;charset=utf-8,email,firstname,lastname%0Ajohn.doe@teleskope.io,John,Doe%0Amax.lee@teleskope.io,Max,Lee" download="sample_event_check_in_import.csv">Download Sample</a>
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
                    <button type="button" class="btn btn-affinity prevent-multi-clicks" onclick="submitImportCheckInsData('<?= $_COMPANY->encodeId($eventid); ?>')" ><?= gettext("Import");?></button>
                    <button type="button" onclick="eventRSVPsForCheckIn('<?= $_COMPANY->encodeId($eventid); ?>','')" id="btn_close2" class="btn btn-affinity" data-dismiss="modal" ><?= gettext("Close");?></button>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    function submitImportCheckInsData(e){
        $(document).off('focusin.modal');
        var formdata = $('#import_CSV_file_form_checkins')[0];
		var finaldata  = new FormData(formdata);
		finaldata.append("eventid",e);
		$.ajax({
			url: './ajax_events.php?submitImportCheckInsData=1',
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

$('#importCSVForCheckInModal').on('shown.bs.modal', function () {    
   $('#btn_close1').trigger('focus');
});
$('#importCSVForCheckInModal').on('hidden.bs.modal', function (e) {
    $('#nonRSVPCheckinDropdownMenuLink').trigger('focus');
});

</script>