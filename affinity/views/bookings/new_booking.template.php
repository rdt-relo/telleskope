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
		text-align: center;
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
<div class="col-12 my-3">               
	<form class="form-horizontal" method="post" action="" id="new_support_booking_form">
		<input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
		<input type="hidden" class="form-control" name="version" value="<?= $eventBooking ? $_COMPANY->encodeId($eventBooking->val('version')) :  $_COMPANY->encodeId(0)?>" />
		<div class="col-12 form-group-emphasis">
			<h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?= sprintf(gettext("%s Slot"),Group::GetBookingCustomName(false));?></h5>
			<?php if ($section == 'schedule_new_booking'){ ?>
				<div class="form-group">
					<div class="col-12 ">
						<label class="control-lable"><?= gettext('Create Booking For'); ?></label>
						<div class="">
							<input class="form-control" autocomplete="off" id="searchBox" value="" onkeyup="searchUserForNewBooking('<?= $_COMPANY->encodeId($groupid); ?>',this.value)" placeholder="<?= gettext('Search user');?>"  type="text" required >
                        	<div id="show_dropdown"> </div>
						</div>
					</div>
				</div>
			<?php } else { ?>
				<input type="hidden" name='bookingCreatorId' value='<?= $_COMPANY->encodeId($bookingCreatorId); ?>'>
			<?php } ?>

			<div class="form-group">
				<div class="col-12 ">
					<label class="control-lable"><?= gettext('Support User'); ?></label>
					<div class="">
						<select tabindex="-1" required class="form-control" id="support_users" name="support_users[]" multiple  onchange="processDateSelection()">
					<?php foreach ($supportUsers as $sUser) { 
						$sel = '';
						if ($bookingSupportId ==$sUser->val('userid')){
							$sel = 'selected';
						}
					?>
							<option data-section="1" value="<?= $_COMPANY->encodeId($sUser->val('userid')); ?>" <?= $sel; ?> >
								<?= $sUser->getFullName(); ?>
							</option>
					<?php }	?>
						</select>
					</div>
				</div>
			</div>
			
			<div class="form-group ">
				<div class="col-12">
					<p class=""><?= gettext('Please use the calendar below to select a date and time for your I9 Meeting'); ?></p>
				</div>
				<div class="col-5">
					<div class="col-12 p-0 m-0 text-left"><p class="control-label"><?= gettext("Select Date") ?><span style="color:red"> *</span></p></div>
					<div class="col-12 p-0 m-0" id="start_date"></div>
					<div class="col-12  p-0 mt-3 mx-0">
						<label for="inputEmail" class="col-sm-12 control-label"><?= gettext("Timezone");?></label>
						<div class="col-sm-12">
							<button type="button" class='timezone btn-no-style' onclick="showTzPicker();"  ><a  class="link_show" id="tz_show"><?= $eventBooking ? ($eventBooking->val('timezone') ? $eventBooking->val('timezone') : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' ) ) : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' ); ?> <?= gettext('Time');?> </a></button>
						</div>
						<input type="hidden" name="timezone" id="tz_input" value="<?= $eventBooking ? ($eventBooking->val('timezone') ? $eventBooking->val('timezone') : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC')) : ($_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC') ; ?>">
						<div id="tz_div" style="display:none;">
							<label id="Timezone" class="col-sm-12 control-lable"><?= gettext('Change Timezone');?><span style="color: #ff0000;"> *</span></label>
							<div class="col-sm-12">
								<select class="form-control teleskope-select2-dropdown" tabindex="0" id="selected_tz" onchange="selectedTimeZone();resetTimeSlots()" style="width: 100%;">
									<?= $eventBooking ?  getTimeZonesAsHtmlSelectOptions($timezone) : getTimeZonesAsHtmlSelectOptions($_SESSION['timezone']); ?>
								</select>
								<script> $(".teleskope-select2-dropdown").select2({width: 'resolve'});</script>
							</div>
						</div>
					</div>
				</div>
				<div class="col-7">
					<div class="col-12" id="availableSlots"></div>
				</div>
			</div>
			
		</div>
		<div class="col-12 form-group-emphasis">
			<h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?= sprintf(gettext("%s Info"),Group::GetBookingCustomName(false));?></h5>
			<div class="form-group">
				<label for="inputEmail" class="col-sm-12 control-label"><?= sprintf(gettext("%s Title"),Group::GetBookingCustomName(false));?><span style="color:red"> *</span></label>
				<div class="col-sm-12">
					<input tabindex="0"  type="text" class="form-control" placeholder="<?= sprintf(gettext("%s Title"),Group::GetBookingCustomName(false));?>" id="eventtitle" name="eventtitle" value="<?= $eventBooking ? $eventBooking->val('eventtitle') :  $emailTemplate['booking_email_subject']; ?>" >
				</div>
			</div>

			<div class="form-group">
				<label for="inputEmail" class="col-sm-12 control-label"><?= gettext("Description");?><span style="color:red"> *</span></label>
				<div class="col-sm-12">
					<div id="post-inner" class="post-inner-edit">
						<textarea class="form-control" tabindex="0" placeholder="<?= sprintf(gettext("%s Description"),Group::GetBookingCustomName(false));?>" name="event_description" rows="3" id="redactor_content" maxlength="2000" ><?= $eventBooking ? htmlspecialchars($eventBooking->val('event_description')) : $emailTemplate['booking_message']; ?></textarea>
					</div>
				</div>
			</div>
		</div>
		
		<div class="text-center">
			<button type="button" onclick="addOrUpdateSupportBooking('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($event_booking_id); ?>')" class="btn btn-affinity prevent-multi-clicks"><?= $submitButton;?></button>	
			<?php if($section != 'detail'){ ?>	
				<button type="button" class="btn btn-affinity-gray" data-dismiss="modal"><?= gettext('Cancel')?></button>
			<?php } ?>
		</div>
	</form>
</div>
		
<script>
    $(document).ready(function(){
        $('#redactor_content').initRedactor('redactor_content','event',[],[],'<?= $_COMPANY->getImperaviLanguage(); ?>');

		$(function() {
			let  availableDaysToSchedule = <?= json_encode($availableDaysToSchedule); ?>;
			function enableAllTheseDays(date) {
				var result = [false];
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
			<?php if($event_booking_id){ ?>
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
        let dateVal = $('#start_date').datepicker({ dateFormat: 'dd-mm-yy' }).val();
		let support_users = $('#support_users').val();

		getAvailableBookingSlots('<?= $_COMPANY->encodeId($groupid); ?>',dateVal,support_users.join(),'<?= $section; ?>');
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

		function getAvailableBookingSlots(groupid,date,support_users,section) {
		    let timezone = $("#tz_input").val();
			let eventStartDate = "<?= $eventBooking ? $eventBooking->val('start') : ''; ?>"
			let eventid = "<?= $eventBooking ? $_COMPANY->encodeId($eventBooking->val('eventid')) : $_COMPANY->encodeId(0); ?>"
			$.ajax({
                url: 'ajax_bookings.php?getAvailableBookingSlots=1',
                type: 'GET',
                data: {groupid:groupid,date:date,timezone:timezone,eventStartDate:eventStartDate,support_users:support_users,section:section,eventid:eventid},
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


		function addOrUpdateSupportBooking(g,eid){
			let eventtitle= $("#eventtitle").val();
			let start_date_evt= $('input[name="time_slot"]:checked');
			let start_date = start_date_evt.val();
			let schedule_id  = start_date_evt.data('schedule_id');
			let support_userid  = start_date_evt.data('support_userid');

			let event_description= $("#redactor_content").val();
			if (!eventtitle || !event_description || $("input[type='radio']:checked").length === 0){
				swal.fire({title: 'Error',text:'All fields are required.'});
				setTimeout(() => {
					$(".swal2-confirm").focus();   
				},100)		
			} else {
				let formdata = $('#new_support_booking_form')[0];
				let finaldata= new FormData(formdata);
				finaldata.append("groupid",g);
				finaldata.append("eventid",eid);
				finaldata.append("schedule_id",schedule_id);
				finaldata.append("support_userid",support_userid);
				finaldata.append("section",'<?= $section; ?>');
				$.ajax({
					url: 'ajax_bookings.php?addOrUpdateSupportBooking=1',
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
								<?php if($section != 'detail'){ ?>	
									closeAllActiveModal();
									window.location.reload();
								<?php } else { ?>
									getMyBookings('<?=$_COMPANY->encodeId($groupid);?>');
                					$(".getMyBookings").addClass("submenuActive");
								<?php } ?>
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

		$(document).ready(function(){
			$('#support_users').multiselect({
				nonSelectedText: "<?= gettext("Support Users Not Selected (Default All)")?>",
				numberDisplayed: 3,
				nSelectedText: "<?= gettext('support users selected')?>",
				disableIfEmpty: true,
				allSelectedText: "<?= gettext('Multiple support users selected'); ?>",
				enableFiltering: true,
				maxHeight: 400,
				enableCaseInsensitiveFiltering: true,
			});
		});



    function searchUserForNewBooking(g,k){
        delayAjax(function(){
            if(k.length >= 3){
				$.ajax({
					url: 'ajax_bookings?searchUserForNewBooking=1',
					type: "GET",
					data: {'groupid':g,'keyword':k},
					success: function(response){
						$("#show_dropdown").html(response);
						var myDropDown=$("#user_search");
						var length = $('#user_search> option').length;
						myDropDown.attr('size',length);
					}
				});
                
            }
        }, 500 );
    }

	function closeDropdown(){
		var myDropDown=$("#user_search");
		var length = $('#user_search> option').length;
		myDropDown.attr('size',0);
    }
	function removeSelectedUser(i){
        $("#searchBox").val('');
        $("#show_dropdown").html('');
        $("#"+i).show();
    }
</script>
