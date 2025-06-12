<?php

[$send_reminder_Ymd,$send_reminder_h,$send_reminder_i,$send_reminder_A] = explode ("%",date("Y-m-d%h%i%A"));
//RoundUp minutes to the nearest 5 in "05" format.
$send_reminder_i = sprintf("%02d",ceil(((int)$send_reminder_i+1)/5)*5);

// Set following to default if there are not preset.
$checked = $checked ?? 'checked';

?>
<div class="modal fade" id="send_reminder" tabindex="-1">
	<div aria-label="<?= gettext("Send Reminder Email");?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
    	<div class="modal-content">				
			<div class="modal-header">
				<h4  id="modal-title" class="modal-title"><?= gettext("Send Reminder Email");?></h4>
				<button onclick="viewReminderHistory('<?= $_COMPANY->encodeId($event->val('eventid')) ?>');" id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
        	</div>
        	<div class="modal-body">
			    <div class="row">
					<div class="col-md-12">  
						<div class="">
							<form id="send_reminder_form" method="post">
								<input type="hidden" name="eventid" value="<?= $_COMPANY->encodeId($event->val('eventid')) ?>">
						<?php if($event->val('schedule_id')>0){  ?>
							<input type="hidden" name="reminderTo[]" value="-1" id="all_invited_checkbox" >
						<?php } else { ?>
								<div class="form-group col-sm-6">
									<div>
										<label><?= gettext("Reminder to");?> : <span style="color:red"> *</span></label>
									<?php if(!empty(array_intersect($events_attendence_type, array(1,2,3,4))) && $events_rsvp_yes_no){ ?>
										<div class="form-check">
											<input id="rsvp-yes" class="form-check-input rsvp_ckbox" name="reminderTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_YES'] ?>" checked >
											<label class="form-check-label" for="rsvp-yes">
												<?= gettext("RSVP - Yes");?>
											</label>
										</div>
									<?php } ?>
									<?php if(!empty(array_intersect($events_attendence_type, array(1,2,3,4))) && $events_rsvp_yes_no){ ?>
										<div class="form-check">
											<input id="rsvp-yetentative" class="form-check-input rsvp_ckbox" name="reminderTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_MAYBE'] ?>" >
											<label class="form-check-label" for="rsvp-yetentative">
												<?= gettext("RSVP - Tentative");?>
											</label>
										</div>
									<?php } ?>
									<?php if(!empty(array_intersect($events_attendence_type, array(1,3))) && $events_max_inperson > 0){ ?>
										<div class="form-check">
											<input id="rsvp-inperson" class="form-check-input rsvp_ckbox" name="reminderTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_INPERSON_YES'] ?>" checked >
											<label class="form-check-label" for="rsvp-inperson">
												<?= gettext("RSVP - In Person Yes");?>
											</label>
										</div>
									<?php } ?>
                                    <?php if(!empty(array_intersect($events_attendence_type, array(2,3))) && $events_max_online > 0){ ?>
                                        <div class="form-check">
                                            <input id="rsvp-online-yes" class="form-check-input rsvp_ckbox" name="reminderTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_ONLINE_YES'] ?>" checked >
                                            <label class="form-check-label" for="rsvp-online-yes">
                                                <?= gettext("RSVP - Online Yes");?>
                                            </label>
                                        </div>
                                    <?php } ?>
									<?php if(!empty(array_intersect($events_attendence_type, array(1,3))) && $events_max_inperson >0){ ?>
										<div class="form-check">
											<input id="rsvp-in-person" class="form-check-input rsvp_ckbox" name="reminderTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_INPERSON_WAIT'] ?>" >
											<label class="form-check-label" for="rsvp-in-person">
												<?= gettext("RSVP - In Person Waitlist");?>
											</label>
										</div>
									<?php } ?>
									<?php if(!empty(array_intersect($events_attendence_type, array(2,3))) && $events_max_online > 0){ ?>
										<div class="form-check">
											<input id="rsvp-online-wait" class="form-check-input rsvp_ckbox" name="reminderTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_ONLINE_WAIT'] ?>" >
											<label class="form-check-label" for="rsvp-online-wait">
												<?= gettext("RSVP - Online Waitlist");?>
											</label>
										</div>
									<?php } ?>
                                    <?php if(!empty(array_intersect($events_attendence_type, array(1,2,3,4)))){ ?>
                                        <div class="form-check">
                                            <input id="rsvp-no" class="form-check-input rsvp_ckbox" name="reminderTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_NO'] ?>" >
                                            <label class="form-check-label" for="rsvp-no">
                                                <?= gettext("RSVP - No");?>
                                            </label>
                                        </div>
                                    <?php } ?>
										<div class="form-check">
                                            <input id="rsvp-not-res"  class="form-check-input rsvp_ckbox" name="reminderTo[]" type="checkbox" value="<?= Event::RSVP_TYPE['RSVP_DEFAULT'] ?>" >
                                            <label class="form-check-label" for="rsvp-not-res">
                                                <?= gettext("RSVP - Not Responded");?>
                                            </label>
                                        </div>
										<div class="form-check">
											<input class="form-check-input" name="reminderTo[]" type="checkbox" value="-1" id="all_invited_checkbox">
											<label class="form-check-label" for="all_invited_checkbox">
												<?= gettext("All Invited");?>
											</label>
										</div>
                                    </div>
                                </div>

                                <div class="form-group col-sm-6">
									<?php if($event->isSeriesEventHead()){ ?>
                                    <label><?= gettext("Scope");?> : </label>
                                    <div class="form-check">
                                        <input id="future_events_only0" class="form-check-input" name="future_events_only" type="radio" checked value="0">
                                        <label class="form-check-label" for="future_events_only0">
                                            <?= gettext("All Events");?>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input id="future_events_only1" class="form-check-input" name="future_events_only" type="radio" value="1">
                                        <label class="form-check-label" for="future_events_only1">
                                            <?= gettext("All Future Events");?>
                                        </label>
                                    </div>
									<?php } else { ?>
                                    &nbsp;
                                    <?php } ?>
								</div>
						<?php } ?>

								<div id="schedule_later_option" class="form-group">
									<label class="col-sm-12"><?= gettext("Send Reminder");?>:</label>
									<div class="col-sm-12">
										<div class="form-check">
											<input class="form-check-input" type="radio" value="now" id="send_reminder_when_now" name="send_reminder_when" required checked>
											<label class="form-check-label" for="send_reminder_when_now">
												<?= gettext("Now");?>
											</label>
										</div>

										<div class="form-check">
											<input class="form-check-input" type="radio" value="scheduled" id="send_reminder_when_scheduled" name="send_reminder_when" required>
											<label class="form-check-label" for="send_reminder_when_scheduled">
												<?= gettext("Schedule for later");?>
											</label>
										</div>
									</div>
								</div>

                        <div id="schedule_later_form" class="schedule_later_box" style="display: none;">
                        <div class="form-group">
							<div class="col-12">
								<label class="col-sm-2" for="start_date"><?= gettext("Date");?></label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="start_date" name="send_reminder_Ymd"
										value="<?= date('Y-m-d'); ?>" placeholder="YYYY-MM-DD" readonly required>
								</div>
							</div>
                        </div>
                        <div class="form-group">
                            <div class="col-12">
								<label for="inputEmail" class="col-sm-2 control-lable"><?= gettext("Time");?></label>
								<div class="col-sm-3 hrs-minutes">
									<select aria-label="<?= gettext("Time");?>" class="form-control" id="publishtime" name='send_reminder_h' required>
										<?=getTimeHoursAsHtmlSelectOptions($send_reminder_h);?>
									</select>
								</div>
								<div class="col-sm-3 hrs-minutes">
									<select aria-label="<?= gettext("hour");?>" class="form-control" name="send_reminder_i" required>
										<?=getTimeMinutesAsHtmlSelectOptions($send_reminder_i);?>
									</select>
								</div>
								<div class="col-sm-4">
									<label class="radio-inline"><input aria-label="<?= gettext("AM");?>" type="radio" value="AM" name="send_reminder_A"
																	required
																	<?= ($send_reminder_A == 'AM') ? 'checked' : '' ?>>AM</label>
									<label class="radio-inline"><input aria-label="<?= gettext("PM");?>" type="radio" value="PM" name="send_reminder_A"
																	<?= ($send_reminder_A == 'PM') ? 'checked' : '' ?>>PM</label>
								</div>
                            </div>
                            <div class="col-12">
								<div class="col-sm-2">&nbsp;</div>
								<div class="col-sm-10">
									<p class='timezone' onclick="showTzPicker();"><a tabindex="0" class="link_show"
																					id="tz_show"><?= $_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' ?>
											<?= gettext("Time");?></a></p>
								</div>
                            </div>
                            <div class="col-12">
								<input type="hidden" name="timezone" id="tz_input"
									value="<?= $_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' ?>">
								<div id="tz_div" style="display:none;">
									<div class="col-sm-2">&nbsp;</div>
									<div class="col-sm-10">
										<select class="form-control teleskope-select2-dropdown" id="selected_tz" onchange="selectedTimeZone()" style="width: 100%;">
											<?php echo getTimeZonesAsHtmlSelectOptions($_SESSION['timezone']); ?>
										</select>
									</div>
								</div>
                            </div>
							<div class="col-12">
								<small class="red"><?= sprintf(gettext('Note: A reminder can be scheduled up to 1 minute before the event\'s end time, %s.'),$db->covertUTCtoLocalAdvance("Y-m-d g:i a T", "",  $event->val('end'),$_SESSION['timezone'],$_USER->val('language'))); ?></small>
							</div>
                        </div>
                        </div>
								<div class="form-group">
									<div>
										<label for="subject"><?= gettext("Subject");?><span style="color:red"> *</span></label>
										<input id="subject" class="form-control" name="subject" maxlength="255" value="Reminder: <?= $event->val('eventtitle') ?>"></input>
									</div>
								</div>
								<?php if($preEventSurveyLink){ ?>
								<input type="text" style="display:none;" id="preShareableLink" name="preShareableLink" value="<?= $preEventSurveyLink; ?>">
								<div class="input-group-append">
									<p class="form-group p-3 border text-center">
										<?= gettext('You can copy the pre-event survey link and use it in the message body.')?>
										
										<button tabindex="0" type="button" class="btn btn-sm btn-affinity" onclick="copyEventSurveyShareableLink('<?= addslashes(gettext('Pre-event survey link copied to clipboard.'))?>','preShareableLink')" onKeyPress="copyEventSurveyShareableLink('<?= addslashes(gettext('Pre-event survey link copied to clipboard.'))?>','preShareableLink')" id="basic-addon2"><?= gettext("Copy Link") ?></button>
									</p>
								</div>
							<?php } ?> 

								<div class="form-group">
                                    <label><?= gettext("Message");?><span style="color:red"> *</span></label>
									<div class="post-inner-edit">
										<textarea class="form-control" maxlength="8000" id="redactor_content" name="message"></textarea>
									</div>
                                </div>

                                <?php if (!$event->isSeriesEventHead()) {  ?>
                                <div class="form-group">
                                    <div class="form-check">
                                        <input id="includeEventDetails" class="form-check-input" name="includeEventDetails" type="checkbox" value="1"
                                               onchange="showEventDetails(this)">
                                        <label class="form-check-label" for="includeEventDetails">
                                            <?= gettext("Add  event details to the reminder.");?><br>
                                            <span style="font-size: small;"><?= gettext("Note: Event link will be added even if this option is unchecked");?></span>
                                        </label>
									</div>
                                </div>
                                <?php } ?>


                                <div class="form-group" id="reminder_email" name="reminder_email" style="display: none;">
									<div>
										<br/><em>--- <?= gettext("The following details will be added automatically");?> ---</em><br/>
                                        <div id="post-inner"><?= $event->val('event_description') ?></div>
									</div> 
								</div>

								<div id="popconfirm-container" class="form-group text-center mt-3">
								    <button type="button" onclick="sendReminderEmailReview();" class="btn btn-affinity confirm popconfirm-selectable-button" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to send this reminder for review to your email?')?>" name="send"><?= gettext("Email Review");?></button>
									<button type="button" onclick="sendReminderEmail('<?= $_COMPANY->encodeId($event->val('eventid')) ?>');" class="btn btn-affinity-gray confirm popconfirm-selectable-button" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to send this reminder?')?>" name="send"><?= gettext("Send Reminder");?></button>
									<button onclick="viewReminderHistory('<?= $_COMPANY->encodeId($event->val('eventid')) ?>');" type="button" class="btn btn-affinity-gray" data-dismiss="modal"><?= gettext("Cancel");?></button>
								</div>
							</form>
			            </div>
			        </div>
				</div>
        	</div>
		</div>
	</div>
</div>
<style type="text/css">
	textarea{
		resize: none;
		display:block;
		height: 100px !important;
	}	
</style>

<script>
    $(document).ready(function() {
        $(".confirm").popConfirm({content: ''});

        $('#all_invited_checkbox').change(function() {
            let chk = $(this).is(':checked');
            if (chk) {
                $('.rsvp_ckbox').prop("disabled", true);
            } else {
                $('.rsvp_ckbox').prop("disabled", false);
            }
        });
    });

    function showEventDetails(e) {
        if (e.checked) {
            $("#reminder_email").show(500);
        } else {
            $("#reminder_email").hide(500);
        }
    }

	$(document).ready(function(){
			$('#redactor_content').initRedactor('redactor_content','event_reminder',[],[],'<?= $_COMPANY->getImperaviLanguage(); ?>');
			$(".redactor-voice-label").text("<?= gettext('Message');?>");
			redactorFocusOut('#subject'); // function used for focus out from redactor when press shift +tab.
	});
	$('.popconfirm-selectable-button').each(function() {
		$(this).popConfirm({
		container: $("#popconfirm-container"),
		});
	});

	$('#send_reminder').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus')
	});

	$(document).on("change", "#send_reminder_when_now, #send_reminder_when_scheduled", function () {
        let val = $(this).val();
        if (val == "scheduled") {
            $("#schedule_later_form").show().css('display', 'inline-block');
        } else {
            $("#schedule_later_form").show().css('display', 'none');
        }
    });
	$(function () {
        $("#start_date").datepicker({
            prevText: "click for previous months",
            nextText: "click for next months",
            showOtherMonths: true,
            selectOtherMonths: false,
            dateFormat: 'yy-mm-dd',
            minDate: 0,
            maxDate: 30
        });
    });

</script>