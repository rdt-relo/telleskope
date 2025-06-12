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
	.time-slot input[type="radio"]:focus + label {
		outline: 2px solid #007bff;
    }
    .time-slot input[type="radio"]:checked + label {
        background-color: #007bff;
        color: #fff;
        border-color: #007bff;
    }
</style>
<div id="teamEventModal" class="modal fade" tabindex="-1">
	<div aria-label="<?= $pageTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
		<div class="modal-content">
			<div class="modal-header">
                <h4 class="modal-title" id="form_title"><?= $pageTitle; ?></h4>
				<button type="button" class="close" data-dismiss="modal" >&times;</button>
			</div>
			<div class="modal-body">               
                <form class="form-horizontal" method="post" action="" id="teamEventModalForm">
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <input type="hidden" class="form-control" name="version" value="<?= $event ? $_COMPANY->encodeId($event->val('version')) :  $_COMPANY->encodeId(0)?>" />
					<input type="hidden" class="form-control" name="touchpointid" id="touchpointid" value="<?=$_COMPANY->encodeId($touchPointId)?>" />
                    <div class="form-group">
                        <label for="inputEmail" class="col-sm-12 control-label"><?= gettext("Event Title");?><span style="color:red"> *</span></label>
                        <div class="col-sm-12">
                            <input tabindex="0"  type="text" class="form-control" placeholder="<?= gettext('Event title');?>" id="eventtitle" name="eventtitle" value="<?= $event ? $event->val('eventtitle') : ( $touchPoint ? $touchPoint['tasktitle'] : '') ?>" >
                        </div>
                    </div>
                    
                    <div class="form-group ">
                        <div class="col-5">
                            <div class="col-12 p-0 m-0 text-left"><p class="control-label"><?= gettext("Select Date") ?></p></div>
                            <div class="col-12 p-0 m-0" id="start_date"></div>
                        </div>
                        <div class="col-7">
                            <div class="col-12" id="availableSlots"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputEmail" class="col-sm-12 control-label"><?= gettext("Timezone");?></label>
                        <div class="col-sm-12">
							<button type="button" class='timezone btn-no-style' onclick="showTzPicker();"  ><a  class="link_show" id="tz_show"><?= $event ? ($event->val('timezone') ? $event->val('timezone') : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' ) ) : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' ); ?> <?= gettext('Time');?> </a></button>
						</div>
						<input type="hidden" name="timezone" id="tz_input" value="<?= $event ? ($event->val('timezone') ? $event->val('timezone') : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC')) : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC') ; ?>">
						<div id="tz_div" style="display:none;">
							<label id="Timezone" class="col-sm-12 control-lable"><?= gettext('Change Timezone');?><span style="color: #ff0000;"> *</span></label>
							<div class="col-sm-12">
								<select class="form-control teleskope-select2-dropdown" tabindex="0" id="selected_tz" onchange="selectedTimeZone();resetTimeSlots()" style="width: 100%;">
                                    <?= $event ?  getTimeZonesAsHtmlSelectOptions($event_tz) : getTimeZonesAsHtmlSelectOptions($_SESSION['timezone']); ?>
								</select>
                                <script> $(".teleskope-select2-dropdown").select2({width: 'resolve'});</script>
							</div>
                        </div>
                    </div>
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
						<button type="button" tabindex="0" onclick="addOrUpdateTeamScheduleEvent('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($teamid); ?>','<?= $_COMPANY->encodeId($eventid); ?>')" class="btn btn-primary prevent-multi-clicks"><?= $submitButton;?></button>
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

		$(function() {
			let  availableDaysToSchedule = <?= json_encode($availableDaysToSchedule); ?>;
			function enableAllTheseDays(date) {
				var result = [true];
				if (availableDaysToSchedule.length){
					var result = [false];
					var fDate = $.datepicker.formatDate('dd-mm-yy', date);
					$.each(availableDaysToSchedule, function(k, d) {
						if (fDate === d) {
							result = [true];
						}
					});
				}
				return result;
			}
			let preSelectDate = null;
			<?php if($eventid){ ?>
				preSelectDate = "<?= $preSelectDate; ?>";

			<?php } ?>
			// Initialize datepickers
			$("#start_date").datepicker({
				defaultDate: preSelectDate,
				showOtherMonths: true,
				selectOtherMonths: true,			
				beforeShow: openDatepicker,
				onClose: closeDatepicker,
				dateFormat: 'yy-mm-dd',
				beforeShowDay: enableAllTheseDays,
                onSelect: processDateSelection,
				minDate: 0
			});					
			
		});

		const timesection = $("#tz_div").children();
		let timeElement = timesection.find(".select2-selection--single");
		// set aria of both section
		timeElement.attr( { 'aria-labelledby':"Timezone", 'aria-expanded':"false", 'aria-readonly':"true", 'aria-disabled':"false" } );

		setTimeout(() => {
			processDateSelection();   
		}, 500)

		redactorFocusOut('.timezone');
    });
	
    function processDateSelection () {
        var dateVal = $('#start_date').datepicker({ dateFormat: 'dd-mm-yy' }).val();
        getAvailableSlots('<?= $_COMPANY->encodeId($teamid); ?>',dateVal);
    }

    function showHolidayEndDateInput(e){
        var eday = $('#holidyEndDay');
        if ($(e).is(':checked')) {
            $('#end_date').val('');
            eday.show();
        } else {
            eday.hide();
        }
    }

    function resetTimeSlots(){
        var dateVal = $('#start_date').datepicker({ dateFormat: 'dd-mm-yy' }).val();
        var targetDate = new Date(dateVal);
        // Find the cell representing the target date
        var $targetCell = $('#start_date').find(".ui-datepicker-calendar td[data-year='" + targetDate.getFullYear() + "'][data-month='" + targetDate.getMonth() + "'] a:contains('" + targetDate.getDate() + "')");
        // Trigger a click event on the target date cell
        $targetCell.trigger("click");
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

		function getAvailableSlots(teamid,date) {
            let timezone = $("#tz_input").val();
			let eventStartDate = "<?= $event ? $event->val('start') : ''; ?>"
			$.ajax({
                url: 'ajax_user_schedule.php?getAvailableSlots=1',
                type: 'GET',
                data: {teamid:teamid,date:date,timezone:timezone,eventStartDate:eventStartDate},
                success: function(data) {
					$("#availableSlots").html('');
					if (data) {
						$("#availableSlots").html(data);
					}
                }
            });
		}

		function selectTimeSlot(time,duration) {
			$("#start_date_hour").val(time.hour);
			$("#start_date_minutes").val(time.minutes);
			$("input[name='period'][value='"+time.period+"']").prop("checked", true);
			$("#hour_duration").val(duration.hour);
			$("#minutes_duration").val(duration.minutes);
		}


		function addOrUpdateTeamScheduleEvent(g,t,e){
			let eventtitle= $("#eventtitle").val();
			let start_date_evt= $('input[name="time_slot"]:checked');
			let start_date = start_date_evt.val();
			let schedule_id  = start_date_evt.data('schedule_id');
			let event_description= $("#redactor_content").val();
			if (!eventtitle || !event_description || $("input[type='radio']:checked").length === 0){
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
				finaldata.append("schedule_id",schedule_id);
				$.ajax({
					url: 'ajax_talentpeak.php?addOrUpdateTeamScheduleEvent=1',
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
									$('#teamEventModal').modal('hide');
									$('body').removeClass('modal-open');
									$('.modal-backdrop').remove();
									$("#team_touch_points").trigger('click');
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
