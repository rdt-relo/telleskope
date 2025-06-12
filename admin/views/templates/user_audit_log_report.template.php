<div tabindex="-1" id="theReportModal" class="modal fade" role="dialog">
	<div class="modal-dialog modal-lg">  
		<div class="modal-content">
			<div class="modal-header">
             
                <h4 class="modal-title text-center" id="rsvp_report_title"><?= sprintf(gettext("Download %s Report"),$report_label);?> <i class="fa fa-info-circle info-black" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext('Below, choose the filters you wish to include in the downloaded audit logs report. Please note, that these reports may be very big in size, so please use start and end date filter between a small intervel for quick data response.'), $report_label); ?>"></i></h4> 
               
                <button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
            <div class="modal-body">
                <div class="col-md-12 mt-3">
                <form class="form-horizontal" action="ajax.php?downloadUserAuditLogs=1" method="post" role="form" style="display: block;">
                        <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
                        <input class="form-control" type="hidden" name="s_type[]" id="s_type" value="<?= $select_type; ?>">
                        <div id="rosterOptions" style="display:block; padding: 0 50px;">
                            <div class="row">
                                <?php if ($select_type !='nonmembers') { ?>
                                <div class="col-sm-6">
									<div class="form-group">
										<label for="s_group">Select <?= $_COMPANY->getAppCustomization()['group']["name-plural"]; ?></label>
										<select class="form-control" name="s_group[]" id="s_group" multiple size="6">
											<!-- <option value="<?= $_COMPANY->encodeId(0) ?>">All Groups</option> -->
											<?php foreach ($groups as $group) { ?>
												<option value="<?= $_COMPANY->encodeId($group->id()) ?>"
														selected><?= $group->val('groupname'); ?></option>
											<?php } ?>
										</select>
									</div>
                                    <div class="form-group">
                                        <label for="s_group">From Date</label>
                                        <input type="text" class="form-control" id="start_date" name="startDate" value="" readonly placeholder="YYYY-MM-DD">
                                    </div>
                                    <div class="form-group">
                                        <label for="s_group">To Date</label>
                                        <input type="text" class="form-control" id="end_date" name="endDate" value="" readonly placeholder="YYYY-MM-DD">
                                    </div>
									<div class="form-group">
										<div class="form-check">
											<label class="form-check-label">
												<input type="checkbox" class="form-check-input" name="includeDeletedUser" value="1"> Include Deleted Users <br>
											</label>
										</div>
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
                                <?php } ?>
                                <div class="col-sm-1"></div>
                                <div class="col-sm-5 text-left">
                                    <div class="form-group">
										<label for="s_options">Select Fields</label>
                                        <br>
                                        <div class="mb-2 text-sm">
                                        <button type="button" class="btn btn-no-style link_show"  onclick="checkUncheckAllCheckBoxes('userOptionsMultiCheck',true)"> <?= gettext("Select All");?></button> | <button type="button" class="btn btn-no-style link_show"  onclick="checkUncheckAllCheckBoxes('userOptionsMultiCheck',false)"> <?= gettext("Deselect All");?></button>
                                        </div>
									<?php
                                        foreach($fields as $key => $value){ ?>
                                            <span  id="id_<?= $key; ?>"><input class="userOptionsMultiCheck metaFields" type="checkbox" name="<?= $key; ?>" value="<?= $key; ?>" checked>&emsp;<?= $value; ?><br></span>
                                        <?php } ?>
                                    </div>
                                </div>
                                <p class="red" id="analytic_note" style="display:none;"><?= $cpuUsageMessage; ?></p>
                            </div>
                        </div>

                        <div class="form-group mt-3 text-center">
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
            $("#end_date").datepicker("option", 'minDate', orginalDate);
        }
	});
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