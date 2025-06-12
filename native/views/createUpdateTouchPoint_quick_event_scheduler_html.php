<?php include __DIR__ . '/header.html'; ?>

<!-- New recognition POP UP -->
<style>
	.fa.fa-times {
		color: #f80e0e;
		background-color: #fff;
		position: absolute;
		margin-left: 0px;
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
						
							<div class="form-group ">
								<div class="col-12">
									<div class="col-12 p-0 m-0 text-left"><p class="control-label"><?= gettext("Select Date") ?></p></div>
									<div class="col-12 p-0 m-0" id="scheduler_date_selector"></div>
								</div>
								<div class="col-12">
									<div class="col-12" id="availableSlots"></div>
								</div>
							</div>

							
							<div class="form-group row ml-1">
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
								<button type="button" tabindex="0" onclick="addOrUpdateTeamScheduleEvent('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($teamid); ?>','<?= $_COMPANY->encodeId($eventid); ?>')" class="btn btn-affinity "><?= $submitButton;?></button>
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

        $('#teamEventModal').modal({
            backdrop: 'static',
            keyboard: false
        });
    });
</script>


<script>
    $(document).ready(function(){
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
			$("#scheduler_date_selector").datepicker({
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
    });
	
	function openDatepicker() {
        $('body').addClass('datepicker-open');
    }

    function closeDatepicker() {
        $('body').removeClass('datepicker-open');
    }
    function processDateSelection () {
	    var dateVal = $('#scheduler_date_selector').datepicker({ dateFormat: 'dd-mm-yy' }).val();
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
        var dateVal = $('#scheduler_date_selector').datepicker({ dateFormat: 'dd-mm-yy' }).val();
        var targetDate = new Date(dateVal);
        // Find the cell representing the target date
        var $targetCell = $('#scheduler_date_selector').find(".ui-datepicker-calendar td[data-year='" + targetDate.getFullYear() + "'][data-month='" + targetDate.getMonth() + "'] a:contains('" + targetDate.getDate() + "')");
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
                url: 'ajax_native.php?getAvailableSlots=1',
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
			let start_date= $('input[name="time_slot"]:checked').val();
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
				$.ajax({
					url: 'ajax_native.php?addOrUpdateTeamScheduleEvent=1',
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

