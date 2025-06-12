<div tabindex="-1" id="theReportModal" class="modal fade" role="dialog">
	<div class="modal-dialog modal-lg">  
		<div class="modal-content">
			<div class="modal-header">
                <h4 class="modal-title text-center" id="rsvp_report_title"><?= sprintf(gettext("Download Login Report for %s"),$appName);?>&nbsp;<i class="fa fa-info-circle info-black" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="<?= gettext('Below, choose the filters you wish to include in the downloaded report. '); ?>"></i></h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
            <div class="modal-body">
                <div class="col-md-12 mt-3 text-center">
                    <form class="form-horizontal" action="ajax.php?downloadLoginReport=1" method="post" role="form" style="display: block;">
                        <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
                        <input type="hidden" name="loginReportType" value="<?= $loginReportType ?>">
                        <div id="rosterOptions" style="display:block;">
                            <div class="row pt-3">
                                <div class="col-sm-6 text-left">
                                    <div class="form-group">
                                        <label for="s_group">From Date</label>
                                        <input type="text" class="form-control" id="start_date" name="startDate" value="<?= $startDate; ?>" readonly placeholder="YYYY-MM-DD">
                                    </div>
                                    <div class="form-group">
                                        <label for="s_group">To Date</label>
                                        <input type="text" class="form-control" id="end_date"  name="endDate" value="" readonly placeholder="YYYY-MM-DD">
                                    </div>
                                    <div class="form-group">
										<label for="s_group">Select Action</label>
										<select class="form-control" name="reportAction" onchange=filterMetaFields(this.value,'<?= $excludeAnalyticMetaFields; ?>') >
											<option value="download" selected>Download Report</option>
                                        <?php if($_COMPANY->getAppCustomization()['reports']['analytics'] && false){  ?>
                                            <option value="analytic">Analytics</option>
                                        <?php } ?>
										</select>
									</div>
                                </div>
                                <div class="col-sm-1"></div>
                                <div class="col-sm-5 text-left">
                                    <div class="form-group">
                                        <label for="s_options">Select Fields</label>
                                        <br>
                                        <div class="mb-2 text-sm">
                                        <button type="button" class="btn btn-no-style link_show" onclick="checkUncheckAllCheckBoxes('eventOptionsMultiCheck',true)"> <?= gettext("Select All");?></button> | <button type="button" class="btn btn-no-style link_show" onclick="checkUncheckAllCheckBoxes('eventOptionsMultiCheck',false)"> <?= gettext("Deselect All");?></button>
                                        </div>
                                    <?php foreach($fields as $key => $value){ 
                                        $checked = 'checked';
                                    ?>
                                        <span  id="id_<?= $key; ?>"><input class="eventOptionsMultiCheck metaFields" type="checkbox" name="<?= $key; ?>" value="<?= $key; ?>" <?= $checked; ?>>&emsp;<?= $value; ?><br></span>
                                    <?php } ?>
                                        
                                    </div>
                                </div>
                                <p class="red" id="analytic_note" style="display:none;"><?= $cpuUsageMessage; ?></p>
                            </div>
                        </div>
                        <div class="form-group mt-3">
                            <button type="submit" name="submit" class="btn btn-primary" id="submit_action_button">Download</button>
                        </div>
                        
                    </form>
                </div> 
            </div>
        </div>
	</div>
</div>
<script>
jQuery(function() {
    jQuery( "#end_date" ).datepicker({
        prevText:"click for previous months",
        nextText:"click for next months",
        showOtherMonths:true,
        selectOtherMonths: true,
        dateFormat: 'yy-mm-dd',
        maxDate: '0',
    });
    jQuery( "#start_date" ).datepicker({
		prevText:"click for previous months",
		nextText:"click for next months",
		showOtherMonths:true,
		selectOtherMonths: true,
		dateFormat: 'yy-mm-dd',
        maxDate: '0',
        onSelect: function (selectedDate) {
            var orginalDate = new Date(selectedDate);
            var todayDate = new Date();
            var monthsAddedDate = new Date(new Date(orginalDate).setMonth(orginalDate.getMonth() + 3));
            $("#end_date").datepicker("option", 'minDate', orginalDate);
            if(todayDate > monthsAddedDate){
                $("#end_date").datepicker("option", 'maxDate', monthsAddedDate);
            }else{
                $("#end_date").datepicker("option", 'maxDate', todayDate);
            }
            
        }
	});
});
$(document).ready(function () {
    var todayDate = new Date();
    var monthsDeletedDate = new Date(new Date(todayDate).setMonth(todayDate.getMonth() - 3));
    $("#end_date").datepicker("option", 'minDate', monthsDeletedDate);
    $("#end_date").datepicker("option", 'maxDate', todayDate);
    $("#start_date").datepicker('setDate', monthsDeletedDate);
    $("#end_date").datepicker('setDate', todayDate);
});
</script>
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });
    // This anonymous function needs to init in all report modal for validation
    $(function() {
        $(".metaFields").click(function(){
            $('#submit_action_button').prop('disabled',$('input.metaFields:checked').length == 0);
        });
    });

    $('#theReportModal').on('shown.bs.modal', function () {     					 
    $('.close').focus();
})
retainFocus('#theReportModal');
</script>