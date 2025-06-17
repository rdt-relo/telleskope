<link href='../vendor/js/jquery-ui-1.14.0/themes/ui-lightness/jquery-ui.min.css' rel='stylesheet'  />
<script src='../vendor/js/moment-2.30.1/min/moment.min.js'></script>
<script src='../vendor/js/fullcalendar-6.1.15/dist/index.global.min.js'></script>

<script>
    $("#home-h").removeClass("active-1").addClass('home_nav');
    $("#home-mh").removeClass("active-1").addClass('home_nav');
    $("#home-a").removeClass("active-1").addClass('home_nav');
    $("#home-c").removeClass("home_nav").addClass('active-1');
    $("#home-s-icon").addClass('home_nav');
</script>

<style>
    body {
        margin: 0;
        padding: 0;
        font-size: 14px;
    }
    #calendar {
        max-width: 900px;
        margin: 0 auto;
    }
    .options-header {
        background-color: #FFF;
        padding: 5px 0 5px 0;
    }
    .options-header-option {
        margin: 5px 0;
        width: 100%;
        max-width: 100%;
    }

    .btn-group{
        width: 100%;
        max-width: 100%;
        border: 1px solid rgb(212, 212, 212);
        height: 38px;
        margin: 5px 0;
        border-radius: 5px;
        background: rgb(242, 242, 242);
    }
    .current_location {
        font-size: x-small;
        font-style: italic;
        margin: -5px 0 0 0;
    }
    /*the dropdown properties will keep the styles aligned with our app.*/
    .multiselect.dropdown-toggle {
        text-align: left !important;
    }
    .dropdown-item.active, .dropdown-item:active
    {
        background-color: #FFF !important;
    }
    .calendar-iframe{
        position: absolute;
        right: 0;
        padding-top: 9px;
    }
    .calendar-filter-lable {
        display: flex;
        margin-top: 6px;
        margin-bottom: -6px;
    }
    .popover, .bs-popover-bottom, .bs-popover-bottom{
        z-index: 999999 !important;
    }
    .multiselect-container > li > a {
        white-space: normal;
    }
    .sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    margin: -1px;
    padding: 0;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
    }

    .fc .fc-button-primary:disabled {
    background-color: var(--fc-button-bg-color);
    border-color: #003550;
    color: #000;
    opacity: 0.3;
}
.calendar-title-box button:focus{
    box-shadow: 0 0 0 .2rem #fff !important;
}
        @media print {
            .footer {
                display: none; /* Hides the footer in print mode */
            }
            header { 
                display: none; /* Hide the header during printing */
            }
            body {
                margin: 0;
                padding: 10px;
                -webkit-print-color-adjust: exact; /* Ensures color accuracy */
            }
            #ajax {
                width: 100%;   /* Make the content take the full width */
                font-size: 12pt; /* Adjust the font size */
                line-height: 1.4; /* Adjust line height */
            }
 
            /* Ensure proper table styling */
            table {
                width: 100%; /* Make sure the table takes the full width */
                border-collapse: collapse; /* Merge borders */
               /* page-break-inside: auto; /* Allow table rows to split between pages */
            }

            th, td {
                padding: 8px;
            }

            /* Prevent rows from being cut off during page breaks */
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            /* Optional: Page break before specific elements like h1 */
            h1, h2, h3, p {
                page-break-before: avoid;
                page-break-after: avoid;
                -webkit-region-break-before: avoid;
                -webkit-region-break-after: avoid;
            }
        }
        @page {
            size: auto;
            margin: 10mm; /* Set margins */
        }
    </style>
<main>
<div class="as row-no-gutters calendar-title-box" style="background:url(<?= $_ZONE->val('banner_background') ?: 'img/img.png'; ?>) no-repeat; background-size:cover; background-position:center;">

    <?php if ($_USER->isAdmin() && $_COMPANY->getAppCustomization()['calendar']['allow_embed']) { ?>
      <div class="calendar-iframe share-btn-section mr-5 pull-right mobile-off" style="
    margin-right: 60px !important;
">
            <button onclick="getCalendarIframe('<?=$_COMPANY->getAppCustomization()['calendar']['enable_secure_embed']?>')" class="btn btn-affinity share-btn-custom" type="button">
        <i class="far fa-share-square" style="" aria-hidden="true"></i>
        &nbsp; <?= gettext("Get iFrame");?> </button>
    </div>
    <?php } ?>
    <div class="pull-right row" style="margin-top:12px;margin-right:0px;">
        <div class="col-2 text-right">
            <?php
            $page_tags = 'global_calendar';
            ViewHelper::ShowTrainingVideoButton($page_tags);
            ?>
        </div>
    </div>

    <h1 class="calendar-banner-title" style="width: 100%; padding-top:40px;"><?= $_ZONE->val('calendar_page_banner_title') ? $_ZONE->val('calendar_page_banner_title') : ''; ?></h1>

</div>
<div aria-hidden="true" id="additional-info" class="sr-only"><?= gettext('Clicking this will open the calendar in list view.') ?></div>

<div id="main_section" class="container options-header">
<div id="announcementLiveRegion" aria-live="assertive" style="position: absolute; left: -9999px;"></div>
    <div class="row">
        <div class="col-sm-12 calendarSection">
            <input type="hidden" id="calendarDefaultView" value="<?= htmlspecialchars($requestedCalendarView ?? ''); ?>">
            <input type="hidden" id="calendarDefaultDate" value="<?= htmlspecialchars($requestedCalendarDate ?? ''); ?>">
            <div id="dynamic_filters">
            <?php
                include(__DIR__ . "/templates/calendar_dynamic_filters.template.php");
            ?>
            </div>
        </div>

        <?php if ( 0  // Disabled
            && $_COMPANY->getAppCustomization()['calendar']['location_filter'] === '0'
        ) { ?>
        <div class="col-sm-12 mt-3 calendarSection">
            <div class="col-sm-12 p-3 mb-2" id="changeLocationInput" style="border:1px solid rgb(180, 178, 178); display:none;">
                <div class="input-group mt-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-search" aria-hidden="true"></i>&emsp;<?= gettext('Search Location'); ?></span>
                    </div>
                    <input type="text" id="autocomplete" class="form-control" placeholder="<?= gettext('Enter new address'); ?>">
                    <input type="hidden" id="new_latitude" value="0">
                    <input type="hidden" id="new_longitude" value="0">
                </div>
                <input type="hidden" id="fullAddress">
                <div class="form-group text-center mt-3">
                    <button class="btn btn-affinity btn-sm" onclick="changeLocation()"><?= gettext('Change Current Location'); ?></button>&emsp;<button class="btn btn-affinity btn-sm" onclick="showChangeLocation()"><?= gettext('Cancel'); ?></button>
                </div>
            </div>
        </div>
        <?php } ?>

    </div>
</div>
<div class="container inner-background">
    <div class="bg-color">  
        <div id="ajax" class="pt-4">
            <p>&nbsp;</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 text-right">
            <button class="btn btn-sm btn-link my-3" onclick="return printCalendar();"><i class="fa fa-print"></i> <?= gettext('Print Page');?></button>
        </div>
    </div>
</div>

</main>

<?php if (0 && // Disabled
    $_COMPANY->getAppCustomization()['calendar']['location_filter']
) { ?>
<script>
    function changeLocation(){
        var a =$("#autocomplete").val().trim();
        if(a){
            $("#current_address").html(a);
            var newLat = $("#new_latitude").val();
            var newLong =  $("#new_longitude").val();
            $.ajax({
                url: 'ajax_events.php?updateCalendarCurrentLocation=1',
                type: 'POST',
                data: {current_address:a,new_latitude:newLat,new_longitude:newLong},
                success: function(data) {
                    if (data == 1) {
                        showChangeLocation();
                    } else {
                        swal.fire({title: 'Error!',text:'Unable to update location, please try again with a different address'});
                    }
                }
            });
        } else {
            swal.fire({title: 'Error!',text:'Please search location!'});
        }
    }

    function showChangeLocation(){
        var x = document.getElementById("changeLocationInput");
        if (x.style.display === "none") {
            x.style.display = "block";
        } else {
            x.style.display = "none";
        }
    }

    function initAutocomplete() {
        var input = document.getElementById('autocomplete');
        var autocomplete = new google.maps.places.Autocomplete(input);
        google.maps.event.addListener(autocomplete, 'place_changed', function () {
            var place = autocomplete.getPlace();
            document.getElementById('new_latitude').value = place.geometry.location.lat();
            document.getElementById('new_longitude').value = place.geometry.location.lng();
        });
    }
</script>

<?php if ($_COMPANY->getAppCustomization()['plugins']['google_maps']) { ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?=GOOGLE_MAPS_API_KEY?>&libraries=places&callback=initAutocomplete" async defer></script>
<?php } ?>

<?php } ?>

<script>
    $(document).ready(function(){
        $('.ui-icon-circle-triangle-w').html("Previous Month");
        $('.ui-icon-circle-triangle-e').html("Next Month");
    });
</script>
<script>
// function to add "ESC key" exit on popover of tooltip
$(document).keyup(function (event) {
    if (event.which === 27) {
        $(".dropdown-menu").removeClass('show')
    }
});
</script>
<script>
$(document).ready(function () {
    var checkboxes = $('.multiselect-option input.form-check-input');

    checkboxes.each(function () {
        $(this).attr('aria-checked', this.checked);
        var label = $(this).closest('.multiselect-option').find('label');
        if (label.length > 0) {
            $(this).attr('aria-labelledby', label.attr('id'));
        }
    });

    checkboxes.on('change', function () {
        var isChecked = this.checked;
        if ($(this).attr('aria-checked') !== isChecked) {
            $(this).attr('aria-checked', isChecked);
            $('#announcementLiveRegion').text(isChecked ? 'Selected' : 'Not Selected');
        }
    });
    
    $('.multiselect').attr('aria-expanded', false);

    $('.multiselect-container').attr('role', 'listbox');
    $('.multiselect-container').attr('aria-multiselectable', true);
    $('.multiselect-option').attr('role', 'option');
    $('.multiselect-option').attr('aria-selected', false);
    $('.multiselect-option.active').attr('aria-selected', true);

});

/*  For Accessibility Browser Zoom part, We have remove the 'mobile-off' class from video helper icon to show the icon*/
    $(document).ready(function() {
        $('.fa-question-circle').removeClass('mobile-off');
        setTimeout(() => {
            $('.fc-timegrid-slots table').removeAttr('aria-hidden');
            $('.fc-today-button').removeAttr('title'); 
            $('.fc-day-today a').attr('aria-current', 'date');
        }, 1200);
    });  

    $(document).on('click','.fc-button-primary', function(){
        $('.fc-timegrid-slots table').removeAttr('aria-hidden'); 
        $('.fc-today-button').removeAttr('title'); 
        $('.fc-day-today a').attr('aria-current', 'date');
    });  
    function printCalendar() {
          window.print();
     }
</script>