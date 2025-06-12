<div tabindex="-1" id="theReportModal" class="modal fade" role="dialog">
	<div class="modal-dialog modal-lg">  
		<div class="modal-content">
			<div class="modal-header">
                <?php if($report_label == 'Non Members'){ ?>
                        <h4 class="modal-title text-center" id="rsvp_report_title"><?= sprintf(gettext("Download %s Report"),$report_label);?> <i class="fa fa-info-circle info-black" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext('Below, choose the filters you wish to include in the downloaded report. This report will show the members that are part of your organization, but not currently enrolled in an %s. '), $_COMPANY->getAppCustomization()['group']['name-short']); ?>"></i></h4>
                <?php } else{ ?>
                        <h4 class="modal-title text-center" id="rsvp_report_title"><?= sprintf(gettext("Download %s Report"),$report_label);?> <i class="fa fa-info-circle info-black" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext('Below, choose the filters you wish to include in the downloaded report. Please note, that these reports are in live time, and any changes will retroactively change past data, which may result in differences from previously downloaded reports. For example, if a %s is removed in one month, his/her/their data will be removed from past months, as well.'), $report_label); ?>"></i></h4> 
                <?php } ?>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
            <div class="modal-body">
                <div class="col-md-12 mt-3 text-left">
                <form class="form-horizontal" action="ajax.php?downloadZoneUsersRoster=1" method="post" role="form" style="display: block;">
                        <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">

                        <div id="rosterOptions" style="display:block; padding: 0 50px;" id="filterOptions">
                            <div class="row">
                                <div class="col-sm-6 text-left">
                                    <div class="form-group">
                                        <label for="filterType">Filter By:  </label>
                                        <br>
                                        <label class="radio pl-3">
                                            <input type="radio" name="filterType" value="date" checked> User Create Date
                                        </label>
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
												<input type="checkbox" class="form-check-input" name="onlyActiveUsers" value="1" checked> Show Active Users only <br>
											</label>
										</div>
									</div>
                                    <div class="form-group">
										<label for="s_group">Select Action</label>
										<select class="form-control" name="reportAction" onchange=filterMetaFields(this.value,'<?= $excludeAnalyticMetaFields; ?>') >
											<option value="download" selected>Download Report</option>
                                        <?php if(0 && $_COMPANY->getAppCustomization()['reports']['analytics']){  ?>
                                            <option value="analytic">Analytics</option>
                                        <?php } ?>
										</select>
									</div>
                                </div>

                                <div class="col-sm-1"></div>
                                <div class="col-sm-5">
                                    <div class="form-group">
										<label for="s_options">Select Fields</label>
                                        <br>
                                        <div class="mb-2 text-sm">
                                            <button type="button" class="btn btn-no-style link_show" onclick="checkUncheckAllCheckBoxes('userOptionsMultiCheck',true)"> <?= gettext("Select All");?></button> | <button type="button" class="btn btn-no-style link_show" onclick="checkUncheckAllCheckBoxes('userOptionsMultiCheck',false)"> <?= gettext("Deselect All");?></button>
                                        </div>
									<?php
                                        foreach($fields as $key => $value){ ?>
                                            <span  id="id_<?= $key; ?>"><input class="userOptionsMultiCheck metaFields" type="checkbox" name="<?= $key; ?>" value="<?= $key; ?>" checked>&emsp;<?= $value; ?><br></span>
                                        <?php } ?>
                                    </div>
                                </div>
                                <p class="red" id="analytic_note" style="display:none;"><?= $cpuUsageMessage;?></p>
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
        beforeShow:function(textbox, instance){
            $('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
        }
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
        },
        beforeShow:function(textbox, instance){
            $('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
        }
	});
});
</script>
<script>
    $(document).ready(function () {
        $('input[type="radio"][name="filterType"]').change(function () {
            var selectedValue = $(this).val();

            if (selectedValue === "date") {
                $('#start_date, #end_date').closest('.form-group').show();
                $('#anniversary_month').closest('.form-group').hide();
            } else if (selectedValue === "month") {
                $('#start_date, #end_date').closest('.form-group').hide();
                $('#anniversary_month').closest('.form-group').show();
            }
        });
        // setting visibility.
        $('input[type="radio"][name="filterType"]:checked').trigger('change');
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
    retainFocus("#theReportModal");
</script>