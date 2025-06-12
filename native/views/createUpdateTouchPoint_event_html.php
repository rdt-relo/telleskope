<?php include __DIR__ . '/header.html'; ?>
<?php //include_once __DIR__.'/common/init_meeting_link_generator.php'; ?>

<!-- New recognition POP UP -->
<style>
.fa.fa-times {
	color: #f80e0e;
	background-color: #fff;
	position: absolute;
	margin-left: 0px;
}
.pac-container {
        z-index: 10000 !important;
    }
</style>
<div class="container">
    <div class="row">
		<div id="teamEventModal" class="modal fade" role="dialog" tabindex="-1">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title" id="form_title"><?= $pageTitle; ?></h4>
						<button type="button" class="close" data-dismiss="modal" onclick="window.location.href='success_callback.php'" >&times;</button>
					</div>
					<div class="modal-body">               
						<form class="form-horizontal" method="post" action="" id="teamEventModalForm">
							<input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
							<input type="hidden" class="form-control" name="version" value="<?= $event ? $_COMPANY->encodeId($event->val('version')) :  $_COMPANY->encodeId(0)?>" />
							<input type="hidden" class="form-control" name="touchpointid" value="<?=$_COMPANY->encodeId($touchPointId)?>" />
							<div class="form-group">
								<label for="inputEmail" class="col-sm-12 control-label"><?= gettext("Event Title");?><span style="color:red"> *</span></label>
								<div class="col-sm-12">
									<input tabindex="0"  type="text" class="form-control" placeholder="<?= gettext('Event title');?>" id="eventtitle" name="eventtitle" value="<?= $event ? $event->val('eventtitle') : ( $touchPointDetail ? $touchPointDetail['tasktitle'] : '') ?>" >
								</div>
							</div>
							<div class="form-group date">
								
								<label for="start_date" class="control-lable col-sm-12"><?= gettext('Event Date');?><small> [YYYY-MM-DD]</small><span style="color: #ff0000;"> *</span></label>
								<div class="col-sm-12">
									<input tabindex="0" type="text" name="eventdate" value="<?= $event ? $s_date : ($touchPointDetail ? $db->covertUTCtoLocalAdvance("Y-m-d",'',$touchPointDetail['duedate'],$timezone) : '') ?>" required id="start_date" class="form-control" readonly="readonly" placeholder="YYYY-MM-DD" />
								</div>
							</div>
							
							<div class="form-group row ml-1">
								<label for="inputEmail" class="col-sm-12 control-lable"><?= gettext('Start Time');?><span style="color: #ff0000;"> *</span></label>
								<div class="col-sm-4 hrs-minutes">
									<select class="form-control" id="start_date_hour" tabindex="0" name='hour' required>
                                        <?=getTimeHoursAsHtmlSelectOptions($eventid ? $s_hrs :  ($touchPointDetail ? $db->covertUTCtoLocalAdvance("h",'',$touchPointDetail['duedate'],$timezone) : ''));?>
									</select>
								</div>
								<div class="col-sm-4 hrs-minutes">
									<select class="form-control" id="start_date_minutes" tabindex="0" name="minutes" required>
                                        <?=getTimeMinutesAsHtmlSelectOptions($eventid ? $s_mmt : ($touchPointDetail ? $db->covertUTCtoLocalAdvance("i",'',$touchPointDetail['duedate'],$timezone) : ''));?>
									</select>
								</div>
								<div class="col-sm-4">
										<label class="radio-inline"><input tabindex="0" type="radio" value="AM" name="period" required <?= $eventid ? ($s_prd=='AM' ? "checked" : '') : ($touchPointDetail ? ($db->covertUTCtoLocalAdvance("A",'',$touchPointDetail['duedate'],$timezone) =="AM" ? "checked" : 'checked') : 'checked'); ?> >AM</label>
										<label class="radio-inline"><input tabindex="0" type="radio" value="PM" name="period" <?= $eventid ? ($s_prd =='PM' ? "checked" : '') : ($touchPointDetail ? ($db->covertUTCtoLocalAdvance("A",'',$touchPointDetail['duedate'],$timezone) =="PM" ? "checked" : '') : ''); ?>>PM</label>
								</div>
								<div class="col-sm-12">
									<button type="button" class='timezone btn btn-link' onclick="showTzPicker();"  ><a  class="link_show" id="tz_show"><?= $event ? ($event->val('timezone') ? $event->val('timezone') : $timezone ) : $timezone; ?> <?= gettext('Time');?></a></button>
								</div>
								<input type="hidden" name="timezone" id="tz_input" value="<?= $event ? ($event->val('timezone') ? $event->val('timezone') : $timezone ) : $timezone ; ?>">
								<div id="tz_div" style="display:none;">
									<label class="col-sm-12 control-lable"><?= gettext('Timezone');?><span style="color: #ff0000;"> *</span></label>
									<div class="col-sm-12">
										<select class="form-control teleskope-select2-dropdown" tabindex="0" id="selected_tz" onchange="selectedTimeZone()" style="width: 100%;">
											<?= $event ?  getTimeZonesAsHtmlSelectOptions($event_tz) : getTimeZonesAsHtmlSelectOptions($timezone); ?>
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
										<input type="checkbox" class="form-check-input multiday" tabindex="0" name="multiDayEvent" id="multiDayEvent" <?= ($event ? ($event->getDurationInSeconds() > 86400) ? 'checked' : '' : ''); ?> ><?= gettext('Multi-day event');?>
										</label>
									</div>
								</div>
							</div>
						<?php } ?>
							<div class="form-group row ml-1" id="event_duration" style="<?= $event ? ( $event->getDurationInSeconds() > 86400 ? 'display:none' : '') : ''; ?>">
								<label for="inputEmail" class="col-sm-12 control-lable"><?= gettext('Duration');?><span style="color: #ff0000;"> *</span></label>
								<div class="col-sm-4 hrs-minutes">
									<select class="form-control" id="hour_duration" tabindex="0" name='hour_duration'  onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" required>
								<?php for ($i=0;$i<25;$i++){ ?>
										<option value="<?= $i; ?>" <?= $eventid ? ($e_hrs==$i ? "selected" : '') : ""; ?> ><?= $i; ?> hr</option>
								<?php } ?>
									</select>
								</div>
								<div class="col-sm-4 hrs-minutes">
									<select class="form-control" tabindex="0" id="minutes_duration" name="minutes_duration" onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" <?= $eventid ? ( ($e_hrs == '24')?'disabled':'') : '' ?>>
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
			
								<div class="form-group row ml-1">
									<label for="inputEmail" class="col-sm-12 control-lable"><?= gettext('End Time');?><span style="color: #ff0000;"> *</span></label>
									<div class="col-sm-4 hrs-minutes">
										<select class="form-control" id="end_hour" name='end_hour' tabindex="0" onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" required>
                                            <?=getTimeHoursAsHtmlSelectOptions($eventid ? $e_hrs : '');?>
										</select>
									</div>
									<div class="col-sm-4 hrs-minutes">
										<select class="form-control"id="end_minutes" tabindex="0" name="end_minutes" onchange="updateCalendarBlockSetting('<?= $_COMPANY->encodeId($groupid); ?>')" required>
                                            <?=getTimeMinutesAsHtmlSelectOptions($eventid ? $e_mnt : '');?>
										</select>
									</div>
									<div class="col-sm-4">
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
								<label for="inputEmail" class="col-sm-12 control-lable"><?= gettext('Venue Type');?><span style="color: #ff0000;"> *</span></label>
								<div class="col-sm-12">
									<select class="form-control" id="event_attendence_type" tabindex="0" onchange="changeEventAttendenceType(this.value);" name='event_attendence_type' required>
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
										<?php //generateMeetingLinkOptionsHTML() ?>
										<input type="url" id="web_conference_link" tabindex="0" onblur="isValidUrl(this.value)"  name="web_conference_link" required class="form-control" value="<?= $event ? $event->val('web_conference_link') : '';?>" placeholder="<?= gettext('Link to join Webex/Zoom/Teams/Hangouts/GotoMeeting, link starts with https://');?>" />
									</div>
								</div>
								<div class="form-group">
									<label class="control-lable col-sm-12" ><?= gettext('Web Conf. Details');?><span style="color: #ff0000;"></span></label>
									<div class="col-sm-12">
										<textarea class="form-control" tabindex="0" id="web_conference_detail" name="web_conference_detail" placeholder="<?= gettext('Please provide additional details for joining (i.e. meeting ID, password, login information, additional webinar details, requirements, etc.)');?>"><?= $event ? str_replace('<br>', "\r\n", $event->val('web_conference_detail')) : ''?></textarea>
									</div>
								</div>
							</div>

							<?php require __DIR__ . '/../../affinity/views/events/event_location_picker.html.php' ?>

							<div class="form-group">
								<label for="inputEmail" class="col-sm-12 control-lable"><?= gettext('Event Contact');?><span style="color: #ff0000;"> *</span></label>
								<div class="col-sm-12">
									<input type="text" tabindex="0" id="event_contact" name="event_contact" value="<?= $event ? htmlspecialchars($event->val('event_contact')) : trim($_USER->val('firstname').' '.$_USER->val('lastname') .' ('.$_USER->val('email').')'); ?>" class="form-control" placeholder="<?= gettext('Contact Name (email)');?>" />
								</div>
							</div>
					<?php if(!$_COMPANY->getAppCustomization()['teams']['team_events']['disable_event_types']){ ?>
							<div class="form-group">
								<label class="control-lable col-sm-12"><?= gettext('Event Type');?><span style="color: #ff0000;"> *</span></label>
								<div class="col-sm-12">
									<select class="form-control" name="eventtype" id="sel1" tabindex="0" required>
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
										<textarea class="form-control" tabindex="0" placeholder="<?= gettext('Event Description');?>" name="event_description" rows="3" id="redactor_content" maxlength="2000" ><?= $event ? htmlspecialchars($event->val('event_description')) : ($touchPointDetail ? htmlspecialchars($touchPointDetail['description']) : '') ?></textarea>
									</div>
								</div>
							</div>
							
							<div class="text-center">
								<button type="button" tabindex="0" onclick="addOrUpdateTeamEvent('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($teamid); ?>','<?= $_COMPANY->encodeId($eventid); ?>')" class="btn btn-affinity "><?= $submitButton;?></button>
								<button type="button" tabindex="0" data-dismiss="modal" class="btn btn-secondary" onclick="window.location.href='success_callback.php'" ><?= gettext("Cancel");?></button>&nbsp;
							</div>
						</form>
					</div>
				</div>  
			</div>
		</div>
    </div>
</div>
<script>
	$(document).ready(function(){
		$('#redactor_content').initRedactor('redactor_content','event',[],[],'<?= $_COMPANY->getImperaviLanguage(); ?>');
   		$(".redactor-voice-label").text("Add event description");

		$(function() {
			$("#start_date").datepicker({
				prevText: "click for previous months",
				nextText: "click for next months",
				showOtherMonths: true,
				selectOtherMonths: false,
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


        $('#teamEventModal').modal({
            backdrop: 'static',
            keyboard: false
        });
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


	function addOrUpdateTeamEvent(g,t,e){
		let eventtitle= $("#eventtitle").val();
		let start_date= $("#start_date").val();
		let event_description= $("#redactor_content").val();

		if (!eventtitle || !start_date || !event_description){
			swal.fire({title: 'Error',text:'All fields are required.'});
			setTimeout(() => {
				$(".swal2-confirm").focus();   
			}, 500)
			$('.modal').css('overflow-y', 'auto');
			$('body').addClass('modal-open');
		} else {
			let formdata = $('#teamEventModalForm')[0];
			let finaldata= new FormData(formdata);
			finaldata.append("groupid",g);
			finaldata.append("teamid",t);
			finaldata.append("eventid",e);
			$.ajax({
				url: 'ajax_native.php?addOrUpdateTeamEvent=1',
				type: "POST",
				data: finaldata,
				processData: false,
				contentType: false,
				cache: false,
				success : function(data) {
					try {
						let jsonData = JSON.parse(data);
						swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
							if (jsonData.status==1){
								window.location.href= 'success_callback.php';
							}
						});
					} catch(e) {
						swal.fire({title: 'Error', text: "Unknown error."});
					}
					setTimeout(() => {
						$(".swal2-confirm").focus();   
					}, 700)
				}
			});
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
</script>

