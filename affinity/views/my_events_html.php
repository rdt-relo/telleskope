<style>
    .hide-filters{display: none;}
    #filterContainer .dropdown-menu{
transform: translate3d(5px, 35px, 0) !important;
    }
</style>
<?php 
// Flag for discover events
$discoverEventsEnabled = $_COMPANY->getAppCustomization()['event']['my_events']['discover_events'];
?>
<main>
    <div class="as row-no-gutters overlay"
        style="background: url(<?= $_COMPANY->val('my_events_background') ?: 'img/img.png'?>) no-repeat; background-size:cover; background-position:center;">
        <div class="col-md-12">
            <h1 class="ll icon-pic-custom" >
                <?= $bannerTitle ?>
            </h1>
        </div>
    </div>
    <div class="container inner-background" style="margin-top: 0px !important;">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="inner-page-title">
                    <ul class="nav nav-tabs" role="tablist">
                    <?php if ($discoverEventsEnabled) { ?>
                        <li role="none" class="nav-item">
                                <a role="tab" aria-selected="true" class="nav-link inner-page-nav-link active" data-id="1" tabindex="0" href="javascript:void(0)" id="discoverevents" onclick="getMyEventsByZone()" data-toggle="tab" >
                                    <?= gettext('Get Engaged'); ?>
                                </a>
                            </li>
                    <?php } ?>
                        <li role="none" class="nav-item">
                            <a role="tab" aria-selected="true" class="nav-link inner-page-nav-link <?= $discoverEventsEnabled ? '' : 'active' ?>" data-id="1" tabindex="-1" href="javascript:void(0)" id="upcomingEvents" onclick="getMyEventsDataBySection('<?= Event::MY_EVENT_SECTION['MY_UPCOMING_EVENTS']; ?>')" data-toggle="tab" >
                                <?= gettext(' RSVPs'); ?>
                            </a>
                        </li>
                        
                        <li role="none" class="nav-item">
                            <a role="tab" aria-selected="false" data-toggle="tab" class="nav-link inner-page-nav-link" tabindex="-1" href="javascript:void(0)" id="eventsattended" onclick="getMyEventsDataBySection('<?= Event::MY_EVENT_SECTION['MY_PAST_EVENTS']; ?>')">
                                <?= gettext('Events Attended'); ?>
                            </a>
                        </li>
                        <?php if ($_COMPANY->getAppCustomization()['event']['my_events']['event_submissions']){ ?>
                        <li role="none" class="nav-item">
                            <a role="tab" aria-selected="false" data-toggle="tab" class="nav-link inner-page-nav-link" tabindex="-1" href="javascript:void(0)" id="eventsubmissions" onclick="getMyEventsSubmissions()">
                                <?= gettext('Events Submissions'); ?>
                            </a>
                        </li>
                        <?php } ?>
                
					</ul>
				</div>
            </div>

                <div id="filterContainer" class="col-md-12 hide-filters">
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
                    <div class="col-sm-2">
                        <div class="form-group">
                            <label for="zoneDropdown"><?= gettext('Filter by Zone') ?></label>
                            <select aria-label="Show All" id="zoneDropdown" class="form-control">
                            <option value="<?= $_COMPANY->encodeId(0) ?>">Show All</option>
                            <?php foreach($userAllZones as $zone){
                                if($zone == null){continue;}?>
                                <option value="<?= $_COMPANY->encodeId($zone->id())?>"><?= $zone->val('zonename') ?></option>    
                            <?php } ?> 
                        </select>
                        </div>
                    </div>
                    <div class="col-sm-4">
                    <div class="form-group">
                        <label class="control-lable calendar-filter-lable" for="eventTypeDropdown"><?=gettext('Filter by Event Type')?></label>
                        <select class="form-control options-header-option" id="eventTypeDropdown" multiple>
                            <?php foreach ($allEventTypes as $et) { 
                                $selectedEventtype = '';
                                if (!empty($eventTypeArray)) {
                                    if ($eventTypeArray[0] =='all' || in_array($et['typeid'],$eventTypeArray)) {
                                        $selectedEventtype = 'selected';
                                    }
                                }
                            ?>
                                <option value="<?=$_COMPANY->encodeId($et['typeid'])?>" <?= $selectedEventtype; ?> ><?=$et['type'];?></option>
                            <?php  } ?>
                        </select>
                    </div>
                    </div>
                </div>

            <div class="cleafirx"></div>
            <div class="col-12" id="dynamic_data_container">
            </div>
        </div>
    </div>
    <!-- Container div for Datepicker & video tags store all html in it for accessibility -->
	<div class="datepicker-and-video-tags-html-container"></div>
</main>

<script>
    // Updated datepickers
    $(function() {
        let isDatePickerActive = false;
        let todayDate = new Date();
        let monthsAddedDate = new Date(new Date(todayDate).setMonth(todayDate.getMonth() + 3));
        function openDatepicker() {
            $('body').addClass('datepicker-open');
            isDatePickerActive = true;
        }

        function closeDatepicker() {
            $('body').removeClass('datepicker-open');
            isDatePickerActive = false;
        }
        function customKeyPress(event) {
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
                    return false;
                } else {
                    clearErrorAndAriaLive(inputElement);
                    inputElement.value = inputValue;
                    return true;
                }
            } else {
                // Invalid date
                showDateError(inputElement);
                return false;
            }
        } else {
            // Invalid format
            showDateError(inputElement);
            return false;
        }
    }

    function showDateError(inputElement) {
        const inputId = inputElement.id;
        // Reset To default dates
        let todayDate = new Date();
        let monthsAddedDate = new Date(new Date(todayDate).setMonth(todayDate.getMonth() + 3));
        const inputValue = inputElement.value.trim();
        const inputDescriptions = {
            'filter_by_start_date': 'start date',
            'filter_by_end_date': 'end date'
        };
        const inputValueDescription = inputDescriptions[inputId] || 'date';
        const errorMessage = generateErrorMessage(inputValue, inputValueDescription);
        setErrorAndAriaLive(inputElement, errorMessage);
        if(inputId === 'filter_by_start_date'){
            $("#filter_by_start_date").datepicker('setDate', todayDate);
        }else if(inputId === 'filter_by_end_date'){
            $("#filter_by_end_date").datepicker('setDate', monthsAddedDate);
        }
    }

    async function dateOnBlurFn() {
        if(isDatePickerActive){
            return false;
        }
        const isValid = await validateDateInput(this);
        if(isValid){
            let previousValue = this.getAttribute('data-previous-value');
            let inputValue = this.value;
            if (previousValue != inputValue) { // Refresh events only if we are clearing a previously set date
                this.setAttribute('data-previous-value', inputValue);
            }
            handleDateChange(this);
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
            onselect: function(dateText, inst){
                isDatePickerActive = false;
            },
            onClose:  closeDatepicker,
            dateFormat: 'yy-mm-dd',
            onSelect: function(selectedDate, inst){
                $(this).val(selectedDate);
                $('#filter_by_end_date').datepicker("option","minDate",selectedDate);
                if(validateDateInput(this)){
                    handleDateChange(this)
                }

            }
        });

        $("#filter_by_end_date").datepicker({
            showOtherMonths: true,
            selectOtherMonths: true,
            minDate: todayDate,
            beforeShow: openDatepicker,
            onselect: function(dateText, inst){
                isDatePickerActive = false;
            },
            onClose:  closeDatepicker,
            dateFormat: 'yy-mm-dd',
            onSelect: function(selectedDate, inst){
                $(this).val(selectedDate);
                if(validateDateInput(this)){
                    handleDateChange(this)
                }
            }
        });
    });
</script>
<script>
 $(document).ready(function () {
    if(<?= $discoverEventsEnabled ? 'true' : 'false' ?>){
        toggleFilters(true);
        var todayDate = new Date();
        var monthsAddedDate = new Date(new Date(todayDate).setMonth(todayDate.getMonth() + 1));
        $("#filter_by_end_date").datepicker("option", 'minDate', todayDate);
        $("#filter_by_start_date").datepicker('setDate', todayDate);
        $("#filter_by_end_date").datepicker('setDate', monthsAddedDate);
        // On initial load, send encoded id 0 to fetch all zone events of the user 
        let eventTypes = $("#eventTypeDropdown").val();
        $(".options-header-option").attr("tabindex", "-1");
        filterByUserGroupZones('<?= $_COMPANY->encodeId(0) ?>',eventTypes, 1);
    }else{
        getMyEventsDataBySection('<?= Event::MY_EVENT_SECTION['MY_UPCOMING_EVENTS']; ?>')
    }
});
// Listener
function handleDateChange(dateElement){
    let zoneid = $("#zoneDropdown").val();
    let eventTypes = $("#eventTypeDropdown").val();
    filterByUserGroupZones(zoneid, eventTypes)
}
$("#zoneDropdown").on('change', function(){
    let zoneid = $("#zoneDropdown").val();
    let eventTypes = $("#eventTypeDropdown").val();
    updateEventTypes(zoneid);//will also update the results
});
$("#eventTypeDropdown").on('change', function(){
    let zoneid = $("#zoneDropdown").val();
    let eventTypes = $("#eventTypeDropdown").val();
    filterByUserGroupZones(zoneid, eventTypes)
});
</script>
<script>
    // show hide discover events filters
    function toggleFilters(showFilters){
        if(showFilters){
            $('#filterContainer').removeClass('hide-filters');
        }else{
            $('#filterContainer').addClass('hide-filters');
        }
    }
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e){
        var targetTabId = $(e.target).attr('id');
        if(targetTabId == 'discoverevents'){
            toggleFilters(true);
        }else{
            toggleFilters(false);
        }
    });
</script>
<script>
    $('#eventTypeDropdown').multiselect({
        nonSelectedText: "<?= gettext('Filter by event types')?>",
        numberDisplayed: 1,
        nSelectedText: "<?=gettext('Event types selected')?>",
        disableIfEmpty: true,
        allSelectedText: "<?= gettext('All event types selected')?>",
        selectAllText: '<?= gettext("Select All");?>',
        includeSelectAllOption: true,
        includeSelectAllIfMoreThan: 1,
        maxHeight: 400
    });
</script>
<script>
    function getMyEventsByZone() {
        let zoneid = $("#zoneDropdown").val();
        let eventTypes = $("#eventTypeDropdown").val();
        filterByUserGroupZones(zoneid, eventTypes, 0);
    }
    // Update events according to zone
    function updateEventTypes(zoneid){
        var eventTypeDropDowns = $("#eventTypeDropdown");
        $.ajax({
		type: "GET",
		url: "ajax_my_events.php?refreshEventTypeDropdown=1",
		data: {
			'zoneid' : zoneid,
		},
        dataType: 'json',
		success: function(eventTypes){
            // Update event types to zone and select all
            eventTypeDropDowns.empty();
            Object.keys(eventTypes).forEach(function(key){
                var decodedText = $('<textarea/>').html(eventTypes[key]).text();
                eventTypeDropDowns.append($("<option></option>").attr("value",key).text(decodedText));
            });
            $("#eventTypeDropdown").multiselect('rebuild');
            $('#eventTypeDropdown').multiselect({
                nonSelectedText: "<?= gettext('Filter by event types')?>",
                numberDisplayed: 1,
                nSelectedText: "<?=gettext('Event types selected')?>",
                disableIfEmpty: true,
                allSelectedText: "<?= gettext('All event types selected')?>",
                selectAllText: '<?= gettext("Select All");?>',
                includeSelectAllOption: true,
                includeSelectAllIfMoreThan: 1,
                maxHeight: 400
            });
            $("#eventTypeDropdown").multiselect('rebuild');
            $("#eventTypeDropdown").multiselect('selectAll', false);
            $("#eventTypeDropdown").multiselect('updateButtonText');
            $("#eventTypeDropdown").trigger('change')
		}
	});
    }
</script>
<script>
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
    updatePageTitle('<?= addslashes($pageTitle); ?>');
</script>
<script>
// function to add "ESC key" exit fro dropdown
$(document).keyup(function (event) {
    if (event.which === 27) {
        $(".dropdown-menu").removeClass('show')
    }
});
</script>