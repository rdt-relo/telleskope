<?php include_once __DIR__.'/../../common/init_meeting_link_generator.php'; ?>
<style>
    .pac-container {
        z-index: 10000 !important;
    }
	.time-slot {
        /* margin-bottom: 10px; */
    }
    .time-slot label {
        display: block;
        padding: 3px;
        border: 1px solid #ccc;
        cursor: pointer;
        border-radius: 5px;
    }
    /* .time-slot input[type="radio"] {
        display: none;
    } */
	.time-slot input[type="radio"] {
    opacity: 0;
    width: 1px;
    height: 1px;
    position: absolute;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
}
    .time-slot input[type="radio"]:checked + label {
        background-color: #007bff;
        color: #fff;
        border-color: #007bff;
    }
	.time-slot input[type="radio"]:focus + label {
		outline: 2px solid #007bff;
    }
	.label-text label{
        width: auto;
        padding-right: 5px;
    }
</style>
<div id="teamEventModal" class="modal fade" tabindex="-1">
	<div aria-label="<?= $pageTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
		<div class="modal-content">
			<div class="modal-header">
                <h4 class="modal-title" id="form_title"><?= $pageTitle; ?></h4>
				<button type="button" id="btn_close" class="close" data-dismiss="modal" >&times;</button>
			</div>
			<div class="modal-body">               
                <form class="form-horizontal" method="post" action="" id="teamEventModalForm">
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <input type="hidden" class="form-control" name="version" value="<?= $event ? $_COMPANY->encodeId($event->val('version')) :  $_COMPANY->encodeId(0)?>" />
					<input type="hidden" class="form-control" name="touchpointid" id="touchpointid" value="<?=$_COMPANY->encodeId($touchPointId)?>" />
                    <div class="form-group">
                        <label for="inputEmail" class="col-sm-12 control-label"><?= gettext("Event Title");?><span style="color:red"> *</span></label>
                        <div class="col-sm-12">
                            <input tabindex="0"  type="text" class="form-control" placeholder="<?= gettext('Event title');?>" id="eventtitle" name="eventtitle" value="<?= $event ? $event->val('eventtitle') : ( $touchPoint ? $touchPoint['tasktitle'] : '') ?>" required>
                        </div>
                    </div>


                    <div class="form-group date">
						<div class='mx-3 alert alert-warning p-2'><small ><?= sprintf(gettext("NOTE: As a best practice, you should schedule Touchpoint events in this system, however you should confirm %s members availability using their calendar before scheduling."),Team::GetTeamCustomMetaName($group->getTeamProgramType()))?></small></div>
						<label for="start_date" class="control-lable col-sm-12"><?= gettext('Event Date');?> <span style="font-size: xx-small">[<?= gettext("YYYY-MM-DD");?>]</span> <span style="color: #ff0000;"> *</span></label>
						<div class="col-sm-12">
							<input tabindex="0" type="text" onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" name="eventdate" value="<?= $event ? $s_date : ($touchPoint ? $db->covertUTCtoLocalAdvance("Y-m-d",'',$touchPoint['duedate'],$_SESSION['timezone']) : '') ?>" required id="start_date" class="form-control" autocomplete="off" data-previous-value="" placeholder="YYYY-MM-DD" />
							<span id="start_date_error_msg" class="error-message" role="alert"></span>
						</div>
						<div class="col-12 mt-2" id="availableSlots"></div>
					</div>

					<div class="form-group">
						<label class="col-sm-12 control-lable"><?= gettext('Start Time');?><span style="color: #ff0000;"> *</span></label>
						<div class="col-sm-3 hrs-minutes pr-0">
							<select aria-label="<?= gettext('Start Time Hour');?>" class="form-control" id="start_date_hour" tabindex="0" name='hour' onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" required>
                                <?=getTimeHoursAsHtmlSelectOptions($eventid ? $s_hrs : ($touchPoint ? $db->covertUTCtoLocalAdvance("h",'',$touchPoint['duedate'],$_SESSION['timezone']) : ''));?>
							</select>
						</div>
						<div class="col-sm-3 hrs-minutes pr-0">
							<select aria-label="<?= gettext('minutes');?>" class="form-control" id="start_date_minutes" tabindex="0" name="minutes" onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" required>
                                <?=getTimeMinutesAsHtmlSelectOptions($eventid ? $s_mmt : ($touchPoint ? $db->covertUTCtoLocalAdvance("i",'',$touchPoint['duedate'],$_SESSION['timezone']) : ''));?>
							</select>
						</div>
                        <div class="col-sm-3">
								<label class="radio-inline"><input tabindex="0" type="radio" value="AM" name="period" required <?= $eventid ? ($s_prd=='AM' ? "checked" : '') :  ($touchPoint ? ($db->covertUTCtoLocalAdvance("A",'',$touchPoint['duedate'],$_SESSION['timezone']) =="AM" ? "checked" : 'checked') : 'checked'); ?> onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" >AM</label>
								<label class="radio-inline"><input tabindex="0" type="radio" value="PM" name="period" <?= $eventid ? ($s_prd =='PM' ? "checked" : '') : ($touchPoint ? ($db->covertUTCtoLocalAdvance("A",'',$touchPoint['duedate'],$_SESSION['timezone']) =="PM" ? "checked" : '') : ''); ?> onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')">PM</label>
						</div>
						<div class="col-sm-12">
							<button type="button" class='timezone btn-no-style' onclick="showTzPicker();"  ><a  class="link_show" id="tz_show"><?= $event ? ($event->val('timezone') ? $event->val('timezone') : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' ) ) : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' ); ?> <?= gettext('Time');?></a></button>
						</div>
						<input type="hidden" name="timezone" id="tz_input" value="<?= $event ? ($event->val('timezone') ? $event->val('timezone') : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC')) : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC') ; ?>">
						<div id="tz_div" style="display:none;">
							<label id="Timezone" class="col-sm-12 control-lable"><?= gettext('Timezone');?><span style="color: #ff0000;"> *</span></label>
							<div class="col-sm-12">
								<select aria-label="<?= gettext('Timezone');?>" class="form-control teleskope-select2-dropdown" tabindex="0" id="selected_tz" onchange="selectedTimeZone()" style="width: 100%;">
                                    <?= $event ?  getTimeZonesAsHtmlSelectOptions($event_tz) : getTimeZonesAsHtmlSelectOptions($_SESSION['timezone']); ?>
								</select>
                                <script> $(".teleskope-select2-dropdown").select2({width: 'resolve'});</script>
							</div>
                        </div>
					</div>
				<?php if(0){ ?>
					<div class="form-group">
						<div class="col-sm-12 pt-0">
							<div class="form-check">
								<label class="form-check-label">
								  <input type="checkbox" class="form-check-input multiday" tabindex="0" name="multiDayEvent" id="multiDayEvent" <?= ($event ? (($event->getDurationInSeconds() > 86400) ? 'checked' : '') : ''); ?> ><?= gettext('Multi-day event');?>
								</label>
							  </div>
						</div>
					</div>
				<?php } ?>
					<div class="form-group" id="event_duration" style="display:<?= $event ? ( $event->getDurationInSeconds() > 86400 ? 'none' : 'block') : 'block'; ?>">
						<label class="col-sm-12 control-lable"><?= gettext('Duration');?><span style="color: #ff0000;"> *</span></label>
						<div class="col-sm-3 hrs-minutes pr-0">
							<select aria-label="<?= gettext('Hour Duration');?>" class="form-control" id="hour_duration" tabindex="0" name='hour_duration'  onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" required>
						<?php for ($i=0;$i<25;$i++){ ?>
								<option value="<?= $i; ?>" <?= $eventid ? ($e_hrs==$i ? "selected" : '') : ""; ?> ><?= $i; ?> hr</option>
						<?php } ?>
							</select>
						</div>
						<div class="col-sm-3 hrs-minutes pr-0">
							<select aria-label="<?= gettext('minutes duration');?>" class="form-control" tabindex="0" id="minutes_duration" name="minutes_duration" onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" <?= $eventid ? ( ($e_hrs == '24')?'disabled':'') : '' ?>>
						<?php	for($m=0;$m<60;$m=$m+5){ ?>
								<option value="<?= $m; ?>" <?= $eventid ? ($e_mnt==$m ? "selected" : '') : ""; ?> > <?= $m; ?> min</option>
						<?php	} ?>
							</select>
						</div>
					</div>
				<?php if (0){ ?>
					<div id="multi_day_end" style="display:<?= $event ? ($event->getDurationInSeconds() > 86400 ? 'block' : 'none') : 'none'; ?>">
						<div class="form-group date">
							<label class="control-lable col-sm-12"><?= gettext('End Date');?><span style="color: #ff0000;"> *</span></label>
							<div class="col-sm-12">
								<input type="text" tabindex="0" name="end_date" required id="end_date" value="<?=  $event ? $e_date : ''; ?>"  onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" class="form-control" readonly="readonly" placeholder="YYYY-MM-DD" />
							</div>
						</div>
	
						<div class="form-group">
							<label class="col-sm-12 control-lable"><?= gettext('End Time');?><span style="color: #ff0000;"> *</span></label>
							<div class="col-sm-3 hrs-minutes pr-0">
								<select aria-label="<?= gettext('End Time Hour');?>" class="form-control" id="end_hour" name='end_hour' tabindex="0" onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" required>
                                    <?=getTimeHoursAsHtmlSelectOptions($eventid ? $e_hrs : '');?>
								</select>
							</div>
							<div class="col-sm-3 hrs-minutes pr-0">
								<select aria-label="<?= gettext('End Time Minutes');?>" class="form-control"id="end_minutes" tabindex="0" name="end_minutes" onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" required>
                                    <?=getTimeMinutesAsHtmlSelectOptions($eventid ? $e_mnt : '');?>
								</select>
							</div>
							<div class="col-sm-3">
									<label class="radio-inline"><input tabindex="0" type="radio" value="AM" name="end_period" required <?= $eventid ? ($e_prd=='AM' ? "checked" : 'checked') : "checked"; ?> onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" >AM</label>
									<label class="radio-inline"><input tabindex="0" type="radio" value="PM" name="end_period" <?= $eventid ? ($e_prd =='PM' ? "checked" : '') : ""; ?> onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" >PM</label>
							</div>
						</div>

					</div>
				<?php } ?>
				<?php if (0){ ?>
                    <div class="form-group">
                        <label class="control-lable col-sm-12"><?= gettext('Calendar Blocks:');?></label>
                        <div class="btn-group btn-group-toggle col-sm-2 calendarBlockBtnBlock" data-toggle="buttons" >
                            <label  id="calendarOn" class="btn btn-default  <?= $event ? ($event->val('calendar_blocks')==1 ? 'active' : '') : 'active'; ?>  btn-on btn-xs adv">
                                <input type="radio"tabindex="0"  value="1" name="calendar_blocks" id="calendar_blocks_on" <?= $event && $event->val('calendar_blocks')==1 ? 'checked' : ''; ?>>ON
                            </label>
                            <label id="calendarOff"  class="btn btn-default <?= $event ? ($event->val('calendar_blocks')==0 ? 'active' : '') : ''; ?> btn-off btn-xs adv">
                                <input type="radio"tabindex="0"  value="0" name="calendar_blocks" id="calendar_blocks_off" <?= $event && $event->val('calendar_blocks')==0 ? 'checked' : ''; ?>>OFF
                            </label>
                        </div>
                        <div class="col-sm-10 p-1">
                        <?= gettext('If ON, the event will block attendees calendar after they confirm attendance'); ?>
                        </div>
                    </div>
				<?php } ?>
					<div class="form-group">
						<label class="col-sm-12 control-lable"><?= gettext('Venue Type');?><span style="color: #ff0000;"> *</span></label>
						<div class="col-sm-12">
							<select aria-label="<?= gettext('Venue Type');?>" class="form-control" id="event_attendence_type" tabindex="0" onchange="changeEventAttendenceType(this.value);" name='event_attendence_type' required>
								<option value="1" 
                                    <?php if($event){ ?>
                                        <?=  $event->val('event_attendence_type')==1 ? 'selected' : ''; ?> <?=($event->isPublished() && !in_array((int)$event->val('event_attendence_type'), array(1, 4)))?'disabled':''?>
                                    <?php } ?>
                                 ><?= gettext('In-Person');?></option>
								<option value="2"
                                    <?php if($event){ ?>
                                        <?= $event->val('event_attendence_type')==2 ? 'selected' : ''; ?> <?=($event->isPublished() && !in_array((int)$event->val('event_attendence_type'), array(2, 4)))?'disabled':''?>
                                    <?php } ?>
                                ><?= gettext('Virtual (Web Conference)');?></option>
								<option value="3" 
                                    <?php if($event){ ?>
                                        <?= $event->val('event_attendence_type')==3 ? 'selected' : ''; ?>
                                    <?php } ?>
                                ><?= gettext('In-Person & Virtual (Web Conference)');?></option>
								<option value="4" 
                                    <?php if($event){ ?>
                                        <?= $event->val('event_attendence_type')==4 ? 'selected' : ''; ?> <?=($event->isPublished() && !in_array((int)$event->val('event_attendence_type'), array(4)))?'disabled':''?>
                                    <?php } ?>
                                ><?= gettext('Other');?></option>
							</select>
						</div>
					</div>

					<div id="conference_div" <?= $event ? ( ($event->val('event_attendence_type')==2 || $event->val('event_attendence_type')==3) ? '' : 'style="display:none;"') : 'style="display:none;"' ?> >
						<div class="form-group">
							<label class="control-lable col-sm-12"><?= gettext('Web Conf. Link');?><span style="color: #ff0000;"> *</span></label>
							<div class="col-sm-12">
                                <?php generateMeetingLinkOptionsHTML() ?>
								<input type="url" id="web_conference_link" tabindex="0" onblur="isValidUrl(this.value)"  name="web_conference_link" required class="form-control" value="<?= $event ? $event->val('web_conference_link') : '';?>" placeholder="<?= gettext('Link to join Webex/Zoom/Teams/Hangouts/GotoMeeting, link starts with https://');?>" />
							</div>
						</div>
						<div class="form-group">
							<label class="control-lable col-sm-12" ><?= gettext('Web Conf. Details');?><span style="color: #ff0000;"></span></label>
							<div class="col-sm-12">
								<div id="web_conference_detail_note" style="color:red;display:none;font-size: small"><?= $_COMPANY->getAppCustomization()['event']['web_conf_detail_message_override'] ?: gettext('In order to properly track event attendance, please DO NOT share the actual event link in the body of the invitation.')?></div>
								<textarea class="form-control" tabindex="0" id="web_conference_detail" name="web_conference_detail" placeholder="<?= gettext('Please provide additional details for joining (i.e. meeting ID, password, login information, additional webinar details, requirements, etc.)');?>"><?= $event ? str_replace('<br>', "\r\n", $event->val('web_conference_detail')) : ''?></textarea>
							</div>
						</div>
					</div>
                    <?php $col_12 = true; $show_additional_location_fields = false;?>
					<?php require __DIR__ . '/../../events/event_location_picker.html.php' ?>

                    <div class="form-group">
					<div class="label-text">
					<label for="inputEmail" class="col-sm-12 control-lable"><?= gettext('Event Contact');?><span style="color: #ff0000;"> *</span></label><i tabindex="0" class="fa fa-info-circle" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="The contact field is a free-form field. You can input multiple comma-separated email addresses, or other relevant contact information about coordinators."></i>
					</div>

                        <div class="col-sm-12">
                            <input type="text" tabindex="0" id="event_contact" name="event_contact" value="<?= $event ? htmlspecialchars($event->val('event_contact')) : trim($_USER->val('firstname').' '.$_USER->val('lastname') .' ('.$_USER->val('email').')'); ?>" class="form-control" placeholder="<?= gettext('Contact Name (email)');?>" required/>
                        </div>
                    </div>
		<?php if(!$_COMPANY->getAppCustomization()['teams']['team_events']['disable_event_types']){ ?>
					<div class="form-group">
						<label class="control-lable col-sm-12"><?= gettext('Event Type');?><span style="color: #ff0000;"> *</span></label>
						<div class="col-sm-12">
							<select aria-label="<?= gettext('Event Type');?>" class="form-control" name="eventtype" id="sel1" tabindex="0" required>
								<option value=""><?= gettext('Select Event Type');?></option>
					<?php 	if(count($type)>0){ ?>
					<?php		for($ty=0;$ty<count($type);$ty++){ ?>
									<option value="<?= $type[$ty]['typeid']; ?>" <?=  $event ? ($event->val('eventtype') == $type[$ty]['typeid'] ? "selected" : "") : ''; ?> ><?= $type[$ty]['type']; ?></option>
					<?php		} ?>
					<?php 	}else{ ?>
								<option value="">- <?= gettext('No type to select');?> -</option>
					<?php	} ?>
							</select>
						</div>
					</div>

		<?php } ?>

                    <div class="form-group">
                        <label for="inputEmail" class="col-sm-12 control-label"><?= gettext("Description");?><span style="color:red"> *</span></label>
                        <div class="col-sm-12">
					    	<div id="eventDescriptionNote" style="color:red;display:none;font-size: small"><?= $_COMPANY->getAppCustomization()['event']['web_conf_detail_message_override'] ?: gettext('In order to properly track event attendance, please DO NOT share the actual event link in the body of the invitation.')?></div>
                            <div id="post-inner" class="post-inner-edit">
                            	<textarea class="form-control" tabindex="0" placeholder="<?= gettext('Event Description');?>" name="event_description" rows="3" id="redactor_content" maxlength="2000" ><?= $event ? htmlspecialchars($event->val('event_description')) : ($touchPoint ? htmlspecialchars($touchPoint['description']) : '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    
					<div class="text-center">
						<button type="button" tabindex="0" onclick="addOrUpdateTeamEvent('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($teamid); ?>','<?= $_COMPANY->encodeId($eventid); ?>')" class="btn btn-primary prevent-multi-clicks"><?= $submitButton;?></button>
                        <button type="button" tabindex="0" data-dismiss="modal" class="btn btn-secondary" ><?= gettext("Cancel");?></button>&nbsp;
					</div>
				</form>
			</div>
		</div>  
	</div>
</div>
		
<script>
    $(document).ready(function(){
        $('#redactor_content').initRedactor('redactor_content','event',[],[],'<?= $_COMPANY->getImperaviLanguage(); ?>');
		$(".redactor-voice-label").text("<?= gettext('Add description');?>");
		redactorFocusOut('#sel1'); // function used for focus out from redactor when press shift +tab.

		$(function() {
			// Initialize datepickers
			$("#start_date").datepicker({
				showOtherMonths: true,
				selectOtherMonths: true,
				beforeShow: openDatepicker,
				onClose: closeDatepicker,
				dateFormat: 'yy-mm-dd'
			});

			$("#end_date").datepicker({
				prevText: "click for previous months",
				nextText: "click for next months",
				showOtherMonths: true,
				selectOtherMonths: true,
				dateFormat: 'yy-mm-dd'
			});
		});

        $( "#multiDayEvent" ).click(function() {
            if(this.checked){
                $("#event_duration").hide();
                $("#multi_day_end").show();
            }
            if(!this.checked){
                $("#event_duration").show();
                $("#multi_day_end").hide();
            }
        });

		const timesection = $("#tz_div").children();
		let timeElement = timesection.find(".select2-selection--single");
		// set aria of both section
		timeElement.attr( { 'aria-labelledby':"Timezone", 'aria-expanded':"false", 'aria-readonly':"true", 'aria-disabled':"false" } );
		redactorFocusOut('.timezone');
	});
	
    function showHolidayEndDateInput(e){
        var eday = $('#holidyEndDay');
        if ($(e).is(':checked')) {
            $('#end_date').val('');
            eday.show();
        } else {
            eday.hide();
        }
    }
</script>

<script>
		$(function() {
			$("#hour_duration").change(function() {
				var selectedValue = $(this).val();
				if (selectedValue == 24) {
					$("#minutes_duration").attr('disabled', true);
					$("#minutes_duration").prop('selectedIndex', 0);
				} else {
					$("#minutes_duration").attr('disabled', false);
				}
			});
		});

$('#teamEventModal').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus');
   $('.redactor-in').attr('aria-required', 'true');
});

$(document).ready(function() {
    $("body").tooltip({ selector: '[data-toggle=tooltip]' });
});
</script>
