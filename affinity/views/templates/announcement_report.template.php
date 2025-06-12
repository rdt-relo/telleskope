<div id="theReportModal" class="modal fade" >
	<div aria-label="<?= sprintf(gettext("Download %s Report"),POST::GetCustomName(true));?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h2 class="modal-title text-center" id="rsvp_report_title"><?= sprintf(gettext("Download %s Report"),POST::GetCustomName(true));?></h2>
                <button aria-label="<?= gettext("close");?>"  id="btn_close" type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
            <div class="modal-body">
                <div class="col-md-12 mt-3 text-center">
                    <form class="form-horizontal" id="reportsForm" action="ajax_reports?downloadAnouncementsReport=1" method="post" role="form" style="display: block;">
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
                                        <label for="s_group"><?= gettext("From Create Date"); ?></label>
                                        <input aria-label="<?= gettext("From Create Date"); ?>" type="text" class="form-control" id="start_date" name="startDate" value="" readonly placeholder="<?= gettext('YYYY-MM-DD');?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="s_group"><?= gettext("To Create Date"); ?></label>
                                        <input aria-label="<?= gettext("To Create Date"); ?>" type="text" class="form-control" id="end_date"  name="endDate" value="" readonly placeholder="<?= gettext('YYYY-MM-DD');?>">
                                    </div>
                                </div>
                                <div class="col-sm-1"></div>
                                <div class="col-sm-5 text-left">
                                    <fieldset>
                                    <legend style="font-size: 1.2rem;"><?= gettext("Select Fields"); ?></legend>
                                        <div class="mb-2 text-sm">
                                            <a role="button" href="javascript:void(0)" class="link_show" onclick="checkUncheckAllCheckBoxes('postOptionsMultiCheck',true)"> <?= gettext("Select All");?></a> | <a role="button" href="javascript:void(0)" class="link_show" onclick="checkUncheckAllCheckBoxes('postOptionsMultiCheck',false)"> <?= gettext("Deselect All");?></a>
                                        </div>
                                        <?php foreach($fields as $key => $value){
                                            $checked = 'checked';
                                            if (in_array($key, ['no_of_likes','no_of_celebrate_reactions','no_of_support_reactions','no_of_insightful_reactions','no_of_gratitude_reactions','no_of_love_reactions','no_of_comments','recipientcount','openscount','uniqueopenscount'])) {
                                                $checked = '';
                                            }
                                        ?>
                                        <input aria-label="<?= $value; ?>" class="postOptionsMultiCheck" type="checkbox" name="<?= $key; ?>" value="<?= $key; ?>" <?= $checked; ?>>&emsp;<?= $value; ?><br>
                                        <?php } ?>
                                    </fieldset>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <button type="submit" name="submit" id="submit_action_button" class="btn btn-primary"><?= sprintf(gettext("Download %s Report"),POST::GetCustomName(true));?></button>
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
    var monthsAddedDate = new Date(new Date(todayDate).setMonth(todayDate.getMonth() + 12));
    var monthsSubtractedDate = new Date(new Date(todayDate).setMonth(todayDate.getMonth() - 12));
    $("#end_date").datepicker("option", 'maxDate', todayDate);
    $("#start_date").datepicker("option", 'maxDate', todayDate);
    $("#start_date").datepicker('setDate', monthsSubtractedDate);
    $("#end_date").datepicker('setDate', todayDate);
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