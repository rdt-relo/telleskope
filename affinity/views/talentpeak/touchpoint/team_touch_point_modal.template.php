<style>
    .swal2-container.swal2-backdrop-show, .swal2-container.swal2-noanimation {
    background: rgb(0 0 0 / 65%);
}
</style>
<div id="todoModal" class="modal fade" tabindex="-1">
    <div aria-label="<?= $pageTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="modal-title" class="modal-title"><?= $pageTitle; ?></h4>
                <button type="button" id="btn_close" class="close" aria-label="Close Modal Box" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
				<div class="col-md-12">
                    <form id="todoForm">
                        <input type="hidden" id='teamid' name='teamid' value="<?= $_COMPANY->encodeId($teamid); ?>">
                        <input type="hidden" id='taskid' name='taskid' value="<?= $_COMPANY->encodeId($taskid); ?>">
                        <input type="hidden" id='parent_taskid' name='parent_taskid' value="<?= $_COMPANY->encodeId($parentid); ?>">
                        <input type="hidden" id='task_type' name='task_type' value="touchpoint">
                        <p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                        <div class="form-group">
                            <label><?= gettext("Touch Point Title");?><span style="color: #ff0000;"> *</span></label>
                            <input  class="form-control" id="tasktitle"  placeholder="<?= gettext("Touch point title here");?>.." name="tasktitle" value="<?= $touchPoint ? htmlspecialchars($touchPoint['tasktitle']) : ''; ?>" required/>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <label for="start_date" class="col-md-12"> <?= gettext("Due Date");?> <span style="font-size: xx-small">[<?= gettext("YYYY-MM-DD");?>]</span><span style="color: #ff0000;"> *</span></label>
                                <div class="col-md-4 mb-2">
                                    <input type="text" class="form-control" id="start_date" value="<?= $touchPoint ? $duedate : ''; ?>"  placeholder="YYYY-MM-DD" name="duedate" autocomplete="off" data-previous-value="" required/>
                                    <span id="start_date_error_msg" class="error-message" role="alert"></span>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <select aria-label="<?= gettext('hour');?>" class="form-control" id="start_date_hour" name='hour'>
                                        <?=getTimeHoursAsHtmlSelectOptions($touchPoint ? $hour : '');?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <select aria-label="<?= gettext('minutes');?>" class="form-control" id="start_date_minutes" name="minutes">
                                        <?=getTimeMinutesAsHtmlSelectOptions($touchPoint ? $minutes : '');?>
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                        <label class="radio-inline"><input aria-label="<?= gettext('AM');?>" type="radio" value="AM" name="period" <?= $touchPoint ? ($period=='AM' ? "checked" : '') : "checked"; ?> >AM</label>
                                        <label class="radio-inline"><input aria-label="<?= gettext('PM');?>" type="radio" value="PM" name="period" <?= $touchPoint ? ($period =='PM' ? "checked" : '') : ""; ?> >PM</label>
                                </div>
                                <div class="col-md-12">
                                    <p class='timezone' onclick="showTzPicker();" ><a href="javascript:void(0)"  class="link_show" id="tz_show"><?= $_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' ?> Time</a></p>
                                </div>
                                <input type="hidden" name="timezone" id="tz_input" value="<?= $_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' ?>">
                                <div id="tz_div" style="display:none;">
                                    <label class="col-sm-12" for="selected_tz"><?= gettext('Change Timezone');?></label>
                                    <div class="col-sm-12">
                                        <select class="form-control teleskope-select2-dropdown" id="selected_tz" onchange="selectedTimeZone();" style="width: 100%;">
                                            <?php echo getTimeZonesAsHtmlSelectOptions($_SESSION['timezone']); ?>
                                        </select>
                                        <script> $(".teleskope-select2-dropdown").select2({width: 'resolve'});</script>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?= gettext("Description (Optional)");?></label>
                            <div id="post-inner" class="post-inner-edit">
                                <textarea  class="form-control" id="description"  placeholder="<?= gettext("Description here");?>.." name="description" ><?= $touchPoint ? $touchPoint['description'] : ''; ?></textarea>
                            </div>
                        </div>

                        <?php if (!empty($team_task_model)) { ?>
                            <?= $team_task_model->renderAttachmentsComponent('v22') ?>
                        <?php } else { ?>
                            <?= TeamTask::CreateEphemeralTopic()->renderAttachmentsComponent('v22') ?>
                        <?php } ?>
                    </form>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="modal-footer text-center" style='text-align:center !important;display: block;'>
                <button type="button" class="btn btn-affinity prevent-multiple-submit" onclick="addTeamTodo('<?= $_COMPANY->encodeId($groupid); ?>')" ><?= gettext("Submit");?></button>
                <button type="button" class="btn btn-affinity"  aria-hidden="true" data-dismiss="modal" ><?= gettext("Close");?></button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
    var fontColors = <?= $fontColors; ?>;
    $('#description').initRedactor('description', 'teamtasks', ['fontcolor', 'counter','table'], fontColors);

    // Attach event listeners to input fields
   let startDateInput = document.querySelector('#start_date');    
    startDateInput.addEventListener('keydown', customKeyPress);  
    startDateInput.addEventListener('blur', dateOnBlurFn);

	let todayDate = new Date();
	// Initialize datepickers
    $("#start_date").datepicker({
        showOtherMonths: true,
        selectOtherMonths: true,
        minDate: todayDate,
        beforeShow: openDatepicker,
        onClose: closeDatepicker,
        dateFormat: 'yy-mm-dd',
        onSelect: function(selectedDate, inst){
            validateDateInput(this);           
        },
        beforeShow:function(textbox, instance){
            $('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
        }
    });
	// End datepicker   
    redactorFocusOut('#start_date'); // function used for focus out from redactor when press shift +tab.     
});   

$('#todoModal').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus')
});
</script>
