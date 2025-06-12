<style>
	.active{
		color: #495057;

	}
.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    margin: -1px;
    border: 0;
    padding: 0;
    white-space: nowrap;
    clip: rect(0 0 0 0);
    overflow: hidden;
}
.datepicker-open {
    overflow: hidden !important;
}
</style>
<div class="container inner-background">
    <div class="row row-no-gutters w-100">
		<div class="col-md-12">
            <div class="col-md-10 col-xs-12">
                <div class="inner-page-title">
                    <h1><?php echo $documentTitle = gettext('Events'). ' - '. $group->val('groupname'); ?></h1>
                </div>
            </div>

       
            <div class="col-md-2 col-xs-12">
                <div class="text-right" style="margin-top:20px">
                <?php 
                    $page_tags = 'global_calendar,event_rsvp';   
                    ViewHelper::ShowTrainingVideoButton($page_tags);              
                ?>
                </div>
            </div>
        
        </div><hr class="lineb" >
        <div class="col-12 px-sm-0 mt-3 mb-4">
            <ul class="nav nav-tabs" role="tablist">
            <?php if ($group->isTeamsModuleEnabled() && $_COMPANY->getAppCustomization()['teams']['team_events']['enabled'] && $_COMPANY->getAppCustomization()['teams']['team_events']['enabled'] && $_COMPANY->getAppCustomization()['teams']['team_events']['event_list']['enabled']){ ?>
                <li role="none" class="nav-item"><a tabindex="0" role="tab" aria-selected="true" class="nav-link inner-page-nav-link active" data-id="1" href="javascript:void(0)" onclick="clearFilters();getEventsTimeline('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>',1)" data-toggle="tab" ><?= sprintf(gettext('%s Upcoming Events'),$_COMPANY->getAppCustomization()['group']['name-short']); ?></a></li>
                <li role="none" class="nav-item"><a tabindex="-1" role="tab" aria-selected="false" data-toggle="tab" class="nav-link inner-page-nav-link" href="javascript:void(0)" onclick="clearFilters();getEventsTimeline('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>',2)"><?= sprintf(gettext('%s Past Events'),$_COMPANY->getAppCustomization()['group']['name-short']); ?></a></li>
                <li role="none" class="nav-item"><a tabindex="-1" role="tab" aria-selected="false" class="nav-link inner-page-nav-link" data-id="2" href="javascript:void(0)" onclick="clearFilters();getTeamsEventsTimeline('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>',1)" data-toggle="tab" ><?= sprintf(gettext('%s Upcoming Events'), Team::GetTeamCustomMetaName($group->getTeamProgramType())); ?></a></li>
                <li role="none" class="nav-item"><a tabindex="-1" role="tab" aria-selected="false" data-toggle="tab" class="nav-link inner-page-nav-link" href="javascript:void(0)" onclick="clearFilters();getTeamsEventsTimeline('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>',2)"><?= sprintf(gettext('%s Past Events'),Team::GetTeamCustomMetaName($group->getTeamProgramType())); ?></a></li>
            <?php } else { ?>
                <li role="none" class="nav-item"><a tabindex="0" role="tab" aria-selected="true" class="nav-link inner-page-nav-link active" data-id="1" href="javascript:void(0)" id="upcomingEvents" onclick="getEventsTimeline('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>',1)" data-toggle="tab" ><?= gettext('Upcoming'); ?></a></li>
                <li role="none" class="nav-item"><a tabindex="-1" role="tab" aria-selected="false" data-toggle="tab" class="nav-link inner-page-nav-link" href="javascript:void(0)" id="pastevents" onclick="getEventsTimeline('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>',2)"><?= gettext('Past'); ?></a></li>                <?php } ?>
            </ul>
        </div>
        <div class="col-12 px-sm-0" id="event_filter_container">
            <div class="col-sm-3">
                <div class="form-group">
                      <label for="filter_by_start_date" id="filter_by_start_date_label"><?= gettext("Start Date")?> <span style="font-size: xx-small"><?= gettext('[YYYY-MM-DD]');?></span></label>
                    <span class="visually-hidden" id="filter_by_start_date_instruction"><?= gettext("Select or enter the event start date in YYYY-MM-DD format")?></span>
                    <input type="text" aria-labelledby="filter_by_start_date_label filter_by_start_date_instruction filter_by_start_date_error_msg" class="form-control" id="filter_by_start_date" placeholder="<?= gettext('YYYY-MM-DD');?>" autocomplete="off" data-previous-value="">
                    <span id="filter_by_start_date_error_msg" class="error-message" role="alert"></span>

                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="filter_by_end_date" id="filter_by_end_date_label"><?= gettext("To End Date")?> <span style="font-size: xx-small"><?= gettext('[YYYY-MM-DD]');?></span></label>
                    <span class="visually-hidden" id="filter_by_end_date_instruction"><?= gettext("Select or enter the event end date in YYYY-MM-DD format")?></span>
                    <input type="text" aria-labelledby="filter_by_end_date_label filter_by_end_date_instruction filter_by_end_date_error_msg" class="form-control" id="filter_by_end_date" placeholder="<?= gettext('YYYY-MM-DD');?>" autocomplete="off" data-previous-value="">
                    <span id="filter_by_end_date_error_msg" class="error-message" role="alert"></span>
                </div>
            </div>
            <div class="col-sm-4">
    <?php if($_COMPANY->getAppCustomization()['event']['volunteers']){ ?>
            
                <label for="filter_by_volunteer"><?= gettext("By Volunteer")?></label>
                <select id="filter_by_volunteer" class="form-control" onchange="showHideClearButton();">
                    <option value=""><?= gettext("Select Volunteer Type"); ?></option>
            <?php foreach($volunteerTypes  as $volunteerType){ ?>
                <option value="<?= $_COMPANY->encodeId($volunteerType['volunteertypeid'])?>"><?= htmlspecialchars($volunteerType['type']) ?></option>
            <?php } ?>
                </select>
            
    <?php } else{ ?>
            <input type="hidden" id="filter_by_volunteer" value="">
    <?php } ?>
        &nbsp;
        </div>
            <div class="col-sm-2 mt-4 pt-1 filter-btn">
                <button class="btn btn-sm btn-affinity" id="filterBtn" onclick="getFilterData();"><?= gettext('Filter')?></button>
                <br>
                <button class="btn btn-sm btn-affinity mt-2" id="clearEventFilter" style="display:none;" onclick="clearFilters()"><?= gettext('Clear Filter')?></button></div>
        </div>
        
		<div class=" col-md-12  tab-content mt-2" id="loadEventsData">
        <?php
            include(__DIR__ . "/get_events_timeline.template.php");
        ?>
		</div>
	</div>
</div>

<div id="rsvps_modal"></div>
<div id="filterNotification" class="visually-hidden"></div>
<script>
       $(document).ready(function() {
			//initial for blank profile picture
			$('.initial').initial({
					charCount: 2,
					textColor: '#ffffff',
                    color: window.tskp?.initial_bgcolor ?? null,
					seed: 0,
					height: 50,
					width: 50,
					fontSize: 20,
					fontWeight: 300,
					fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
					radius: 0
			});
            $('[data-toggle="tooltip"]').tooltip(); 
       });
       function clearFilters() {
            $("#filter_by_start_date").val('');
            $("#filter_by_end_date").val('');
            $("#filter_by_volunteer")[0].selectedIndex = 0;
            getFilterData();
            $("#clearEventFilter").hide();
            document.querySelector("#filterBtn").focus(); 

            $("#filterNotification").removeAttr("role");
            $("#filterNotification").removeAttr("aria-live"); 
            $("#filterNotification").html('');
       }
</script>
<script>
    function getFilterData(){
        var ref_this = $('ul.nav-tabs li a.active');
        if(ref_this.data('id') == 1){
            getEventsTimeline('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>',1)
        }else if(ref_this.data('id') == 2){
            getTeamsEventsTimeline('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>',1)
        }
        $("#filterNotification").attr("role","status");
        $("#filterNotification").attr("aria-live","polite");       					
    }


</script>
<script>
    // Updated datepickers
    $(function() {
    let todayDate = new Date();
    function openDatepicker() {
        $('body').addClass('datepicker-open');
    }

    function closeDatepicker() {
        $('body').removeClass('datepicker-open');
    }
    function customKeyPress(event) {
        showHideClearButton();
        let inst = $.datepicker._getInst(event.target);
        let isRTL = inst.dpDiv.is(".ui-datepicker-rtl");

        switch (event.keyCode) {
            case 37:    // LEFT --> -1 day
                $('body').css('overflow','hidden');
                $.datepicker._adjustDate(event.target, (isRTL ? +1 : -1), "D");
                break;
            // case 16:    // UPP --> -7 day
            //     $('body').css('overflow','hidden');
            //     $.datepicker._adjustDate(event.target, -7, "D");
            //     break;
            case 38:    // UPP --> -7 day
                $('body').css('overflow','hidden');
                $.datepicker._adjustDate(event.target, -7, "D");
                break;
            case 39:    // RIGHT --> +1 day
                $('body').css('overflow','hidden');
                $.datepicker._adjustDate(event.target, (isRTL ? -1 : +1), "D");
                break;
            case 40:    // DOWN --> +7 day
                $('body').css('overflow','hidden');
                $.datepicker._adjustDate(event.target, +7, "D");
                break;
        }
        $('body').css('overflow','hidden');
    }

    function generateErrorMessage(inputValue, identifier) {
        return `You entered ${inputValue} as the ${identifier}. This is an invalid date. The date should be in the format YYYY-MM-DD and greater than or equal to todays date.`;
    }

function setErrorAndAriaLive(inputElement, errorMessage) {
    const errorElement = inputElement.nextElementSibling;
    if (errorElement) {
        errorElement.textContent = errorMessage;
        errorElement.setAttribute('aria-live', 'assertive');
    }
}

function clearErrorAndAriaLive(inputElement) {
    const errorElement = inputElement.nextElementSibling;
    if (errorElement) {
        errorElement.textContent = '';
        errorElement.removeAttribute('aria-live');
    }
}

// Date input validation
function validateDateInput(inputElement) {
    let inputValue = inputElement.value.trim();
    let errorElement = inputElement.nextElementSibling;

    if (!inputValue) {
        return;
    }

    // Reformat YYYYMMDD to YYYY-MM-DD if applicable
    if (/^\d{8}$/.test(inputValue)) {
        inputValue = inputValue.replace(/^(\d{4})(\d{2})(\d{2})$/, '$1-$2-$3');
        inputElement.value = inputValue;
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(inputValue)) { // Validate format
        // Valid format, check if it's a valid date
        let parts = inputValue.split('-');
        let year = parseInt(parts[0], 10);
        let month = parseInt(parts[1], 10);
        let day = parseInt(parts[2], 10);
        let inputDate = new Date(year, month - 1, day);
        if (!isNaN(inputDate) && inputDate.getFullYear() === year && inputDate.getMonth() === month - 1 && inputDate.getDate() === day) {

            let inputDateAsInt = inputDate.toISOString().slice(0,10).replaceAll('-','');
            let yesterdaysDateAsInt = new Date(Date.now() - 86400000).toISOString().slice(0,10).replaceAll('-','');
            if (inputDateAsInt < yesterdaysDateAsInt) {
                showDateError(inputElement);
            } else {
                clearErrorAndAriaLive(inputElement);
                inputElement.value = inputValue;
            }
        } else {
            // Invalid date
            showDateError(inputElement);
        }
    } else {
        // Invalid format
        showDateError(inputElement);
    }
}

function showDateError(inputElement) {
    const inputId = inputElement.id;
    const inputValue = inputElement.value.trim();
    const inputDescriptions = {
        'filter_by_start_date': 'start date',
        'filter_by_end_date': 'end date'
    };
    const inputValueDescription = inputDescriptions[inputId] || 'date';
    const errorMessage = generateErrorMessage(inputValue, inputValueDescription);
    setErrorAndAriaLive(inputElement, errorMessage);
    inputElement.value = '';
}

function dateOnBlurFn() {
    validateDateInput(this);
    let previousValue = this.getAttribute('data-previous-value');
    let inputValue = this.value;
    if (previousValue != inputValue) { // Refresh events only if we are clearing a previously set date
        this.setAttribute('data-previous-value', inputValue);
    }
}

    // Attach event listeners to input fields
    let startDateInput = document.querySelector('#filter_by_start_date');
    let endDateInput = document.querySelector('#filter_by_end_date');

    startDateInput.addEventListener('keydown', customKeyPress);
    endDateInput.addEventListener('keydown', customKeyPress);

    startDateInput.addEventListener('blur', dateOnBlurFn);
    endDateInput.addEventListener('blur', dateOnBlurFn);

    // Initialize datepickers
    $("#filter_by_start_date").datepicker({
        showOtherMonths: true,
        selectOtherMonths: true,
        minDate: todayDate,
        beforeShow: openDatepicker,
        onClose: closeDatepicker,
        dateFormat: 'yy-mm-dd',
        onSelect: function(selectedDate, inst){
            validateDateInput(this);
            $('#filter_by_end_date').datepicker("option","minDate",selectedDate);
            showHideClearButton();
        },
        beforeShow:function(textbox, instance){
            $('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
        }
    });

    $("#filter_by_end_date").datepicker({
        showOtherMonths: true,
        selectOtherMonths: true,
        minDate: todayDate,
        beforeShow: openDatepicker,
        onClose: closeDatepicker,
        dateFormat: 'yy-mm-dd',
        onSelect: function(selectedDate, inst){
            validateDateInput(this);
            showHideClearButton();
        },
        beforeShow:function(textbox, instance){
            $('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
        }
    });
});
    updatePageTitle('<?= addslashes($documentTitle); ?>');


    function showHideClearButton() {
       
        if ($("#filter_by_start_date").val() || $("#filter_by_end_date").val() || $("#filter_by_volunteer").val()){
            $("#clearEventFilter").show();
        } else {
            $("#clearEventFilter").hide();
        }
    }
</script>
<?php
// If there is Event Survey that we need to show... show it now.
if (!empty($_SESSION['show_event_survey']) && !empty($_SESSION['show_event_id'])) {
    $event_survey_key = base64_url_decode($_SESSION['show_event_survey']);
    $enc_eventid = $_COMPANY->encodeId($_SESSION['show_event_id']);
    unset($_SESSION['show_event_survey'],$_SESSION['show_event_id']);
    
?>

<script>
        $(document).ready(function () {
            <?php if ($_COMPANY->getAppCustomization()['event']['enable_event_surveys']) { ?>
                processEventSurveyLink('<?= $enc_eventid?>', '<?= $event_survey_key?>');
            <?php } else { ?>
                swal.fire({title: '<?= gettext("Error") ?>',text:"<?= gettext("Event survey feature is disabled, so you cannot access this survey link.")?>",allowOutsideClick:false}).then(function(result) {
                    getEventDetailModal('<?= $enc_eventid?>', '<?= $_COMPANY->encodeId(0)?>', '<?= $_COMPANY->encodeId(0)?>');
				});
            <?php } ?>
        });
    </script>

<?php


}
// If there is Event that we need to show... show it now.
elseif (!empty($_SESSION['show_event_id'])) {
    $enc_eventid = $_COMPANY->encodeId($_SESSION['show_event_id']);
    unset($_SESSION['show_event_id']);
    ?>
    <script>
        $(document).ready(function () {
            getEventDetailModal('<?= $enc_eventid?>', '<?= $_COMPANY->encodeId(0)?>', '<?= $_COMPANY->encodeId(0)?>');
        });
    </script>
<?php } ?>


<script>
    function processEventSurveyLink(e,t) {
        $.ajax({
		type: "GET",
		url: "ajax_events.php?processEventSurveyLink=1",
		data: {
			'eventid' : e,'trigger':t
		},
		success: function(data){
          	try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false}).then(function (result) {
                    getEventDetailModal(e, '', '');
				});
			} catch(e) {
				$('#loadAnyModal').html(data);
				$('#eventSurveyModal').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});
    }
/*  For Accessibility Browser Zoom part, We have remove the 'mobile-off' class from video helper icon to show the icon*/
$(document).ready(function() {
    $('.fa-question-circle').removeClass('mobile-off');
});

$(function() {                       
  $(".inner-page-nav-link").click(function() { 
    $('.inner-page-nav-link').attr('tabindex', '-1');
    $(this).attr('tabindex', '0');    
  });
});

$('.inner-page-nav-link').keydown(function(e) {  
    if (e.keyCode == 39) {       
        $(this).parent().next().find(".inner-page-nav-link:last").focus();       
    }else if(e.keyCode == 37){       
        $(this).parent().prev().find(".inner-page-nav-link:last").focus();  
    }
});
</script>