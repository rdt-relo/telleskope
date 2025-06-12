<div tabindex="-1" id="theReportModal" class="modal fade" role="dialog">
	<div class="modal-dialog modal-lg">  
		<div class="modal-content">
			<div class="modal-header">
            <h4 class="modal-title text-center" id="survey_report_title"><?= gettext("Download Survey Data Report");?> <i class="fa fa-info-circle info-black" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="<?= gettext('Below, choose the filters you wish to include in the downloaded report.'); ?>"></i></h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
            <div class="modal-body">
                <div class="col-md-12 mt-3 text-center">
                    <form class="form-horizontal" action="ajax.php?download_survey_report=1" method="post" role="form" style="display: block;">
                        <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
                        <div id="rosterOptions" style="display:block;">
                            <div class="row pt-3">
                                <div class="col-sm-6 text-left">
                                    <div class="form-group">
                                        <label for="s_group">Select <?= $_COMPANY->getAppCustomization()['group']["name-plural"]; ?></label>
                                        <select class="form-control" name="s_group[]" id="s_group" multiple size="6">
                                            <option selected value="<?= $_COMPANY->encodeId(0) ?>">Global Surveys</option>
                                            <?php foreach ($group as $g) { ?>
                                                <option value="<?= $_COMPANY->encodeId( $g->id()) ?>"
                                                        selected><?=  $g->val('groupname'); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                
                                    <div class="form-group">
										<div class="form-check">
											<label class="form-check-label">
												<input type="checkbox" class="form-check-input" name="includeInactiveSurveys" value="1"> Include Inactive Surveys <br>
											</label>
										</div>
									</div>
                                    <div class="form-group">
                                        <label for="s_group">From Publish Date</label>
                                        <input type="text" class="form-control" id="start_date" name="startDate" value="" readonly placeholder="YYYY-MM-DD">
                                    </div>
                                    <div class="form-group">
                                        <label for="s_group">To Publish Date</label>
                                        <input type="text" class="form-control" id="end_date"  name="endDate" value="" readonly placeholder="YYYY-MM-DD">
                                    </div>
                                    
                                    <div class="form-group">
										<label for="s_group">Select Action</label>
										<select class="form-control" name="reportAction">
											<option value="download" selected>Download Report</option>
                                        </select>
									</div>
                                    
                                </div>
                                <div class="col-sm-1"></div>
                                <div class="col-sm-5 text-left">
                                    <div class="form-group">
                                        <label for="s_options">Select Fields</label>
                                        <br>
                                        <div class="mb-2 text-sm">
                                        <button type="button" class="btn btn-no-style link_show" onclick="checkUncheckAllCheckBoxes('surveyOptionsMultiCheck',true)"> <?= gettext("Select All");?></button> | <button type="button" class="btn btn-no-style link_show" onclick="checkUncheckAllCheckBoxes('surveyOptionsMultiCheck',false)"> <?= gettext("Deselect All");?></button>
                                        </div>
                                    <?php foreach($fields as $key => $value){ 
                                        $checked = 'checked';
                                    ?>
                                        <span  id="id_<?= $key; ?>"><input class="surveyOptionsMultiCheck metaFields" type="checkbox" name="<?= $key; ?>" value="<?= $key; ?>" <?= $checked; ?>>&emsp;<?= $value; ?><br></span>
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
$(document).ready(function () {
    var todayDate = new Date();
    var threeMonthsAgo = new Date();
    threeMonthsAgo.setMonth(todayDate.getMonth() - 3);

    $("#start_date").datepicker({
        prevText: "click for previous months",
        nextText: "click for next months",
        showOtherMonths: true,
        selectOtherMonths: true,
        dateFormat: 'yy-mm-dd',
        maxDate: todayDate,
        onSelect: function (selectedDate) {
            $("#end_date").datepicker("option", 'minDate', selectedDate);
        }
    });

    $("#end_date").datepicker({
        prevText: "click for previous months",
        nextText: "click for next months",
        showOtherMonths: true,
        selectOtherMonths: true,
        dateFormat: 'yy-mm-dd',
        maxDate: todayDate
    });

    $("#start_date").datepicker('setDate', threeMonthsAgo);
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