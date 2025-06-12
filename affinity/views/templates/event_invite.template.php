<!-- Group notify pop UP -->
<style>
    .hide{
        display:none;
    }
    .progress_bar{
        background-color: #efefef;
        margin: 5px 0px;
        padding: 15px;
    }
</style>

<div id="eventInviteGroup" class="modal">
    <div aria-label="<?= gettext("Invite Users");?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
		<div class="modal-content">

            <div class="modal-header">
            	<h4 id="modal-title" class="modal-title"><?= gettext("Invite Users");?></h4>
                <button aria-label="close" type="button" id="btn_close" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body"> 
				<div class="container container-remove">
					<p style="text-align:center; color:green;display:none" id="showmessage"></p>
					<p style="text-align:center; color:red;display:none" id="showerror"></p>

					<form class="form-horizontal" id="inviteEvents" method="post">
						<input type="hidden" id="eventid"  name="eventid" value="<?= $_COMPANY->encodeId($eventid) ?>">
                        <input type="hidden" id="withChapterId" name="withChapterId" value="">

                        <?php if (empty($groupList) && empty($chapterList)) { ?>
                            <input type="hidden" value="email_in" name="invite_who">
                        <?php } else { ?>
                        <div class="form-group">
                            <div class="row">
                                <label class="col-sm-2"><?= gettext("Invite");?></label>
                                <div class="col-sm-10">
                                    <label class="radio-inline" style="min-width: 145px;">
                                        <input type="radio" value="email_in" id="invite_who" name="invite_who" required checked ><?= gettext("By Email");?>
                                    </label>
                                    <label class="radio-inline" >
                                        <input type="radio" value="group_in" id="invite_who" onclick="closeEventInvitesStats()" name="invite_who" required><?= $triggerTitle; ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php } ?>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="progress_bar form-group hide" id="progress_bar">
                                    <p><?= gettext('Sending invitations to <span id ="totalBulkRecored"></span> email(s). Please wait.');?></p>
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-animated" id="prgress_bar" style="width:0%"></div>
                                    </div>
                                    <div class="text-center progress_status" aria-live="polite"></div>
                                </div>

                                <div class="mb-3 p-4 progress_done progress_bar hide">
                                    <strong><?= gettext("Sent");?> :</strong>
                                    <p class="pl-3" id="inviteSent" style="color: green;"></p>
                                    <p class="pl-3" id="againSent" style="color: darkgreen"></p>
                                    <strong><?= gettext("Failed");?>: </strong>
                                    <p class="pl-3" style="color: red;" id="inviteFailed"></p>
                                    <div class="co-md-12 text-center pt-3">
                                        <button id="close_show_progress_btn" type="button" class="btn btn-affinity hide" onclick="closeEventInvitesStats()"><?= gettext("Close");?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="email_in_form" class="form-group">
                            <label class="control-lable"><?= gettext("Emails");?></label>
                            <div class="">
                                <textarea id="invited_email_ids" rows="5" class="form-control" name="email_in"  value="" placeholder="<?= gettext("Enter up to 1000 emails");?>" required></textarea>
                            </div>
                        </div>

                        <div id="group_in_form" class="form-group" style="display: none;">
                            <div class="">
                                    <?php if ($event->val('teamid') || $event->isSeriesEventSub()) { ?>
                                        <?= gettext("No options available for this invitation");?>
                                    <?php } elseif (!$event->isPublishedToEmail()) { ?>
                                        <p><?= sprintf(gettext("Only events that have been published to email can invite other %ss. You can publish this event to email by updating the event and selecting Email to all members option"),$shortName);?></p>
                                    <?php } else{ ?>
                                        <?php if (!empty($groupList) && $event->val('chapterid') =='0') { ?>
                                                <label class="control-lable"><?= $_COMPANY->getAppCustomization()['group']["name-short-plural"];?>&nbsp;</label>
                                                <select name="selected_groupid" id="group_id_in" class="form-control" required>
                                                <option selected value='' disabled><?=$selectTitle; ?></option>
                                                    <?php foreach($groupList as $item){ ?>
                                                        <option value="<?= $_COMPANY->encodeId($item['id'])?>" <?= $item['disabled']; ?> >
                                                            <?= $item['name']; ?> <?= $item['optionSuffix'] ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                        <?php } elseif ((!empty($chapterList) && $event->val('chapterid') !='0') || !empty($event->val('collaborating_groupids'))) { ?>
                                            <div class="form-group">
                                                <label class="control-lable"><?= sprintf(gettext('Select a %1$s to get %2$s'),$_COMPANY->getAppCustomization()['group']["name-short"],$_COMPANY->getAppCustomization()['chapter']["name-short-plural"]);?>&nbsp;</label>
                                                <select id="selected_groupid_for_chapter" name="selected_groupid_for_chapter" class="form-control" onchange="getGroupChaptersToInvite(this.value,'<?= $_COMPANY->encodeId($event->id())?>')" required>
                                                <option selected value ='' disabled><?= $groupSelectTitle; ?></option>
                                                    <?php foreach($groupList as $item){
                                                        if (!$_USER->canManageGroupSomething($item['id'])){
                                                            continue;
                                                        }
                                                        $sel = '';
                                                        $optionSuffix2 = '';
                                                        //if ($event->val('groupid') == $item['id']){
                                                        //    $sel = "selected";
                                                        //}
                                                        if (in_array($item['id'], $groupIdsWithoutChapters)) {
                                                            $sel = 'disabled';
                                                            $optionSuffix2 = ' ('.gettext('invited').')';
                                                        }
                                                    ?>
                                                        <option value="<?= $_COMPANY->encodeId($item['id'])?>" <?= $sel; ?> >
                                                            <?= $item['name'] . $optionSuffix2; ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div id="chapter-dropdown" class="form-group">
                                                <label class="control-lable"><?= sprintf(gettext("Select a %s"),$_COMPANY->getAppCustomization()['chapter']["name-short"]);?>&nbsp;</label>
                                                <select name="selected_chapterid" id="group_id_in" class="form-control" required></select>
                                            </div>
                                        <?php } else { ?>
                                            <?= gettext("No option available for invitation");?>
                                        <?php } ?>
                                    <?php } ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="text-center">
								<button id="invitation-submit-button" type="button" onclick="inviteEvent();" class="btn btn-affinity" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("You cannot withdraw the invitation. Are you sure with your selection?");?>" ><?= gettext("Submit");?></button>
								<button type="button" class="btn btn-affinity-gray" data-dismiss="modal" ><?= gettext("Close");?></button>
							</div>
						</div>
					</form>
				</div> <!-- end of div container -->
            </div>  <!-- end of div modal body -->
        </div> <!-- end of div modal content -->
	</div> <!-- end of div modal dialog -->
</div> 

<script>
    jQuery(document).on("change", "#group_id_in", function () {
        let val = $("#group_id_in").val();
        if (val == ''){
            $('#invitation-submit-button').prop('disabled', true);
        } else {
            $('#invitation-submit-button').prop('disabled', false);
        }
    });
    jQuery(document).on("change", "#selected_groupid_for_chapter", function(){
            $("#chapter-dropdown").show().css('display','inline-block');
        });
    jQuery(document).on("change", "#invite_who", function () {
        let val = $(this).val();
        if (val == "group_in") {
            $("#email_in_form").show().css('display', 'none');
            $("#group_in_form").show().css('display', 'inline-block');
            var disableSubmit = false;
            <?php if (!$event->isPublishedToEmail() || $event->val('teamid')) { ?>
                disableSubmit = true;
            <?php } ?>
            if ($("#group_id_in").val() == ''){
                disableSubmit = true;
            }
            if (disableSubmit){
                $('#invitation-submit-button').prop('disabled', true);
            }
       } else {
            $('#invitation-submit-button').prop('disabled', false);
            $("#email_in_form").show().css('display', 'inline-block');
            $("#group_in_form").show().css('display', 'none');
        }
    });
</script>
<script>
    $(document).ready(function() {
        var defaultValue = $("#selected_groupid_for_chapter").val();
        var sectionValue= $('option[value='+defaultValue+']').val();
            $("#withChapterId").val(sectionValue);
            $("#chapter-dropdown").show().css('display','none');
    });
    $("#selectedId").on("change",function(){
        var sectionValue=$("#selected_groupid_for_chapter").find(':selected').val();
        $("#withChapterId").val(sectionValue);
    });
</script>
<script>
trapFocusInModal("#eventInviteGroup");

$('#eventInviteGroup').on('shown.bs.modal', function () {
   $('.close').trigger('focus');
});

$('#eventInviteGroup').on('hidden.bs.modal', function (e) {  
    $('.modal').removeClass('js-skip-esc-key');      
    if ($('.modal').is(':visible')){
        $('body').addClass('modal-open');
    }    
    setTimeout(() => {
        $('.invite-event-users-form').focus(); 
    }, 20);  
})
</script>