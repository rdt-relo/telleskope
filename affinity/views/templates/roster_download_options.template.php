
<div id="theReportModal" class="modal fade">
	<div aria-label="<?= sprintf(gettext("%s Report Download Options"),$reportTitle);?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h2 class="modal-title text-center" id="rsvp_report_title"><?= sprintf(gettext("%s Report Download Options"),$reportTitle);?></h2>
                <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
            <div class="modal-body">
   <div class="col-md-12">
	    <div class=" manage-page-bttn row">
            <form class="form-horizontal" id="reportsForm" action="ajax_reports?excel_roster_new=<?= $_COMPANY->encodeId($groupid); ?>" method="post" role="form" style="display: block;width:100% !important" target="_self">
                <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
                <input type="hidden" name="reportType" id="reportType" value="<?= $reportType ?>">
                <input type="hidden" name="sectionSelect" id="sectionSelect">
                <div id="rosterOptions" style="padding: 2px; padding-top:10px;">
                    <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                <?php if($reportType != 'groupleads'){ ?>
                                    <label class="control-lable" ><?= gettext("Scope"); ?></label>

                                    <?php include_once __DIR__.'/../common/init_reports_chapter_channel_selection_box.php'; ?>
                                <?php } ?>
                            </div>
                            <div class="form-group">
                                        <label for="filterType"><?= gettext("Filter By:"); ?> </label>
                                        <br>
                                        <label class="radio pl-3">
                                            <input type="radio" name="filterType" value="date" checked> <?= gettext("User Join Date"); ?>
                                        </label>
                                        <br>
                                        <label class="radio pl-3">
                                            <input type="radio" name="filterType" value="month"><?= gettext("Anniversary Month (UTC)"); ?> 
                                        </label>
                            </div>
                                <div class="form-group">
                                    <label for="start_date"><?= gettext("From Date"); ?></label>
                                    <input aria-label="<?= gettext("From Date"); ?>" type="text" class="form-control" id="start_date" name="startDate" value="" readonly placeholder="<?= gettext('YYYY-MM-DD');?>">
                                </div>
                                <div class="form-group">
                                    <label for="end_date"><?= gettext("To Date"); ?></label>
                                    <input aria-label="<?= gettext("To Date"); ?>" type="text" class="form-control" id="end_date"  name="endDate" value="" readonly placeholder="<?= gettext('YYYY-MM-DD');?>">
                                </div>
                                <div class="form-group">
										<div class="form-check">
											<label class="form-check-label">
												<input type="checkbox" class="form-check-input" name="onlyActiveUsers" value="1" checked> <?= gettext('Show Active Users only');?> <br>
											</label>
										</div>
									</div>
                                <div class="form-group" style="display: none;">
                                    <label for="anniversary_month"><?= gettext("Anniversary Month"); ?> </label>
                                    <select class="form-control" name="anniversaryMonth" id="anniversary_month">
                                        <?php
                                        $currentMonth = date('m');
                                        for ($i = 1; $i <= 12; $i++) {
                                            $month = date('F', mktime(0, 0, 0, $i, 1));
                                            $selected = ($i == $currentMonth) ? 'selected' : '';
                                            echo "<option value=\"$i\" $selected>$month</option>";
                                        }
                                        ?>
                                    </select>
                                    </div>
                                    <div class="form-group">
										<label for="s_group"><?= gettext("Select Action");?></label>
										<select id="s_group" class="form-control" name="reportAction" onchange="filterMetaFields(this.value,'<?= htmlspecialchars($excludeAnalyticMetaFields); ?>')" >
											<option value="download" selected><?= gettext("Download Report");?></option>
                                        <?php if($_COMPANY->getAppCustomization()['reports']['analytics']){  ?>
                                            <option value="analytic"><?= gettext("Analytics");?></option>
                                        <?php } ?>
										</select>
									</div>
                        </div>

                        <div class="col-sm-1"></div>
                        <div class="col-sm-5">
                        <fieldset>
                        <legend style="font-size: 1.2rem;"><?= gettext("Select Fields"); ?></legend>
                                    <div class="mb-2 text-sm">
                                        <a role="button" href="javascript:void(0)" class="link_show" onclick="checkUncheckAllCheckBoxes('userOptionsMultiCheck',true)"> <?= gettext("Select All");?></a> | <a role="button" href="javascript:void(0)" class="link_show" onclick="checkUncheckAllCheckBoxes('userOptionsMultiCheck',false)"> <?= gettext("Deselect All");?></a>
                                    </div>
                                    <?php
                                        foreach($fields as $key => $value){ ?>
                                            <span id="id_<?= $key; ?>"><input aria-label="<?= $value; ?>" class="userOptionsMultiCheck metaFields" type="checkbox" name="<?= $key; ?>" value="<?= $key; ?>" checked>&emsp;<?= $value; ?><br></span>
                                        <?php } ?>
                            </fieldset>
                        </div>
                        <p class="red" id="analytic_note" style="display:none; margin:0 15px;"><?= $cpuUsageMessage; ?></p>
                    </div>
                </div>

                <div class="form-group mt-2" style="text-align:center;">
                                     
                        <button type="submit" id="submit_action_button" name="submit" class="btn btn-primary"><?= gettext("Download");?></button>                        
              
                </div>
            </form>
        </div>
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
    $(document).ready(function() {
        var defaultValue = $("#selectedId").val();
        var sectionValue= $('option[value='+defaultValue+']').data('section');
            $("#sectionSelect").val(sectionValue);
    });
    $("#selectedId").on("change",function(){
        var sectionValue=$("#selectedId").find(':selected').data('section');
        $("#sectionSelect").val(sectionValue);
    });

    $('#theReportModal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus')
    });  
$(function() {
    $('#reportsForm').submit(function(){         
        $('#btn_close').focus();      
    });
});
</script>
<script>
    document.getElementById('reportsForm').addEventListener('submit', function(event){
       
       var formData = new FormData(this);
       var action = formData.get('reportAction');
       if(action !== 'download'){
           event.preventDefault();
           
           $.ajax({
               url: this.action,
               type: "POST",
               data: formData,
               processData: false,
               contentType: false,
               cache: false,
                   success : function(data) {
                       if(data){
                           // viewAnalytic
                           $('body').removeClass('modal-open');
                           $('.modal-backdrop').remove();
                           $("#loadAnyModal").html(data);
                           $('#viewAnalytic').modal({
                               backdrop: 'static',
                               keyboard: false
                           });
                       } else {
                        swal.fire({title: 'Error!', text: "<?= gettext('Not enough data. Please select higher range of dates');?>"});
                           console.log('error');
                       }
                                       
                   }
           });
       }
   
   });
</script>