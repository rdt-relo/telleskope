<div id="theReportModal" class="modal fade">
	<div aria-label="<?= gettext("Download Event RSVP Report");?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h2 class="modal-title text-center" id="rsvp_report_title"><?= gettext("Download Event RSVP Report");?></h2>
                <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
            <div class="modal-body">
                <div class="col-md-12 mt-3 text-center">
                    <form class="form-horizontal" id="reportsForm" action="ajax_reports.php?download_event_rsvp_report=<?= $enc_groupid; ?>" method="post" role="form" style="display: block;" target="_self">
                        <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
                        <input type="hidden" name="groupid" value="<?= $enc_groupid ?>">
                        <input type="hidden" name="sectionSelect" id="sectionSelect">
                        <div id="rosterOptions2" style="display:block;">
                            <div class="row pt-3">
                                <div class="col-sm-6 text-left">
                                <div class="form-group">
                                    <label class="control-lable" ><?= gettext("Scope"); ?></label>
                                    <?php include_once __DIR__.'/../common/init_reports_chapter_channel_selection_box.php'; ?>
                                </div>
                                    <div class="form-group">
                                        <label for="start_date"><?=gettext('From Date')?></label>
                                        <input aria-label="<?= gettext("From Date"); ?>" type="text" class="form-control" id="start_date" name="startDate" value="<?= $startDate; ?>" readonly placeholder="<?= gettext('YYYY-MM-DD');?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="end_date"><?=gettext('To Date')?></label>
                                        <input aria-label="<?= gettext("To Date"); ?>" type="text" class="form-control" id="end_date"  name="endDate" value="" readonly placeholder="<?= gettext('YYYY-MM-DD');?>">
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
                                <div class="col-sm-5 text-left">
                                <fieldset>
                                    <legend style="font-size: 1.2rem;"><?= gettext("Select Fields"); ?></legend>
                                        <div class="mb-2 text-sm">
                                            <a role="button" href="javascript:void(0)" class="link_show" onclick="checkUncheckAllCheckBoxes('eventOptionsMultiCheck',true)"> <?= gettext("Select All");?></a> | <a role="button" href="javascript:void(0)" class="link_show" onclick="checkUncheckAllCheckBoxes('eventOptionsMultiCheck',false)"> <?= gettext("Deselect All");?></a>
                                        </div>
                                        <?php foreach($fields_rsvp as $key => $value){
                                        $checked = 'checked';
                                        if(in_array($key, array('rsvpcount','attendeecount','is_member'))){
                                        $checked = '';
                                        }
                                        ?>
                                        <span id="id_<?= $key; ?>"><input aria-label="<?= $value; ?>" class="eventOptionsMultiCheck metaFields" type="checkbox" name="<?= $key; ?>" value="<?= $key; ?>" checked>&emsp;<?= $value; ?><br></span>
                                        <?php } ?>
                                        <input aria-label="<?=gettext('Include Custom Fields')?>" class="eventOptionsMultiCheck" type="checkbox" name="includeCustomFields" value="1" checked>&emsp;<?=gettext('Include Custom Fields')?>
                                    </fieldset>
                                </div>
                                <p class="red" id="analytic_note" style="display:none;"><?= $cpuUsageMessage; ?></p>
                            </div>
                        </div>
                        <div class="form-group mt-3">
                            <button type="submit" id="submit_action_button" name="submit" class="btn btn-primary"><?= gettext("Download Report");?></button>
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
        onSelect: function (selectedDate) {
            var orginalDate = new Date(selectedDate);
            var monthsAddedDate = new Date(new Date(orginalDate).setMonth(orginalDate.getMonth() + 12));
            $("#end_date").datepicker("option", 'minDate', orginalDate);
            $("#end_date").datepicker("option", 'maxDate', monthsAddedDate);            
        },
        beforeShow:function(textbox, instance){
            $('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
        }
	});
});
$(document).ready(function () {
    var todayDate = new Date();
    var monthsAddedDate = new Date(new Date(todayDate).setMonth(todayDate.getMonth() + 3));
    var monthsDeletedDate = new Date(new Date(todayDate).setMonth(todayDate.getMonth() - 3));
    $("#end_date").datepicker("option", 'minDate', monthsDeletedDate);
    $("#end_date").datepicker("option", 'maxDate', '1y');
    $("#start_date").datepicker('setDate', monthsDeletedDate);
    $("#end_date").datepicker('setDate', monthsAddedDate);
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