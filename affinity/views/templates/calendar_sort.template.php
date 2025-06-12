<?php

$calendarLang = $_COMPANY->getCalendarLanguage();
$calendarDefaultView = 'dayGridMonth';
$showPrivateEventBlocks = $_USER->isGroupleadOrGroupChapterleadOrGroupChannellead(-1);
if(!empty($requestedCalendarView)) {
    $calendarV3toV6Map = [
        'month' => 'dayGridMonth',
        'agendaWeek' => 'dayGridWeek',
        'agendaDay' => 'dayGridDay',
        'dayGridMonth' => 'dayGridMonth',
        'timeGridWeek' => 'timeGridWeek',
        'timeGridDay' => 'timeGridDay',
        'listMonth' => 'listMonth',
    ];
    if (in_array($requestedCalendarView, array_keys($calendarV3toV6Map))){
        $calendarDefaultView = $calendarV3toV6Map[$requestedCalendarView];
    }
}

$calendarDefaultDate = date("Y-m-d");
$calendarDefaultDateRaw = '';
if (!empty($requestedCalendarDate)){
    $calendarDefaultDateRaw = $requestedCalendarDate;
    if (strtotime($calendarDefaultDateRaw) > time()) {
        if ($calendarDefaultDateRaw = date("Y-m-d", strtotime($calendarDefaultDateRaw))) {
            $calendarDefaultDate = $calendarDefaultDateRaw;
        }
    }
}

?>

<style>
    /* custom style for calendar's header buttons */
    :root {
        --fc-button-active-bg-color: #0077B5;
        --fc-button-bg-color: #fff;
        --fc-button-border-color: #0077B5 ;
        --fc-button-text-color: #0077B5;
    }
    .fc .fc-button-primary:not(:disabled).fc-button-active{
        color: #fff;
    }
    .fc-daygrid-event {
        line-height: 1rem;
    }
    .text-color-red { color: red!important;}
    .text-color-blue { color: blue!important; }
</style>
<script>
    $(document).ready(function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            validRange: {
                    start: '<?= date("Y-m-d", strtotime('6 months ago')); ?>'
            },
            locale: 'en',
            initialView: '<?= $calendarDefaultView; ?>',
            initialDate: '<?= $calendarDefaultDate; ?>',
            themeSystem: 'bootstrap',
            navLinks: true, // can click day/week names to navigate views
            editable: false,
            dayMaxEvents: 3, // allow "more" link when too many events
            moreLinkClick: 'popover', // 'day', day is more accessible friendly
            displayEventTime: true,
            eventDisplay: 'auto', // Historically we used 'block', though auto will be better
            nowIndicator: true,
            events: [
                <?php foreach ($events as $event) {
                    $eventObj = Event::ConvertDBRecToEvent($event);
                    if ($event['isprivate']) {
                        if (!$showPrivateEventBlocks) {
                            continue; // Do not show private events
                        } else {
                            // Blind the private event
                            $event['eventtitle'] = gettext('Private Event');
                            $event['eventvanue'] = '';
                            $event['vanueaddress'] = '';
                            $event['event_description'] = '';
                            $event['web_conference_link'] = '';
                            $event['web_conference_detail'] = '';
                            $event['web_conference_sp'] = '';
                        }
                    }

					// Chapter based Region filter
//					 if ($regionid && $event['chapterid']){
//                        $chapterRegionids = Group::GetChapterRegionIdsByChapterIdsCSV($event['chapterid']);
//					 	if (!in_array($regionid, explode(',',$chapterRegionids))){
//					 		continue;
//					 	}
//					 }

                    $ergName = $eventObj->getFormatedEventCollaboratedGroupsOrChapters();
                    $ergColor = $_COMPANY->getGroupColor($event['groupid']);
                    $ergLabel = $_COMPANY->getAppCustomizationForZone($event['zoneid'])['group']['name-short'];
                    if ($ergName == 'Unknown') {
//                        $ergName = Arr::SearchColumnReturnColumnVal($linkedGroupRows, $event['groupid'], 'groupid', 'groupname');
//                        $ergColor = Arr::SearchColumnReturnColumnVal($linkedGroupRows, $event['groupid'], 'groupid', 'overlaycolor');
                        $ergLabel = 'Group';
                    }
                    if ($event['eventclass']=== 'holiday') {
                        $color = '#ffffff';
                        $borderColor = $ergColor;
                        $textColor = '#444444';
                        $event_class = 'holiday';
                        $event_time = 'All Day';
                        $start_date =  $db->covertUTCtoLocalAdvance("Y-m-d","",$event['start'],'UTC');
                        $end_date = ($event['end'] > $event['start'] ? $db->covertUTCtoLocalAdvance("Y-m-d H:i:s","",$event['end'],'UTC') : $start_date);
                    }
                    else {
                        $color = $ergColor;
                        $borderColor = $ergColor;
                        $textColor = '#ffffff';
                        $event_class = 'event';
                        $event_time = $db->covertUTCtoLocalAdvance("g:i A"," T(P)",$event['start'],$_SESSION['timezone']);
                        $start_date =  $db->covertUTCtoLocalAdvance("Y-m-d","",$event['start'],$_SESSION['timezone']) . 'T' . $db->covertUTCtoLocalAdvance("H:i","",$event['start'],$_SESSION['timezone']);
                        $end_date = $db->covertUTCtoLocalAdvance("Y-m-d","",$event['end'],$_SESSION['timezone']). 'T' . $db->covertUTCtoLocalAdvance("H:i","",$event['end'],$_SESSION['timezone']);
                    }
                    $eventAppType = $_COMPANY->getZone($event['zoneid'])->val('app_type');

                    $draftTag = ($event['isactive'] == Event::STATUS_DRAFT || $event['isactive'] == Event::STATUS_UNDER_REVIEW)
                                    ? '<span class="text-color-red">[' . gettext('draft - not published yet') . ']</span><br>'
                                    : (
                                            $event['isactive'] == Event::STATUS_AWAITING
                                                ? '<span class="text-color-blue">[' . gettext('scheduled - not published yet') . ']</span><br>'
                                                : ''
                                    );

                ?>
                {
                    erg : '<?= addslashes(html_entity_decode($ergName));?>',
                    eventid : '<?= $_COMPANY->encodeId($event['eventid']); ?>',
                    erglabel: '<?= $ergLabel ?>',
                    title: '<?= addslashes((html_entity_decode($event['eventtitle']))); ?>',
                    safe_title: '<?= addslashes(htmlspecialchars(html_entity_decode($event['eventtitle']))); ?>',
                    draft: '<?= $draftTag ?>',
                    isprivate: <?= ($event['isprivate'] == 0) ? 0 : 1; ?>,
                    urlWindow: '<?=$eventAppType?>',
                    url: 'javascript:void(0)',
                    start: '<?=$start_date?>',
                    end: '<?=$end_date?>',
                    venue: "<?= addslashes(htmlspecialchars(html_entity_decode($event['eventvanue']))); ?>",
                    address: '<?= addslashes(htmlspecialchars(str_replace(array("\r", "\n", "  "), ' ', html_entity_decode($event['vanueaddress'])))); ?>',
                    event_class: '<?= $event_class ?>',
                    time: '<?=$event_time?>',
                    etime: '<?= $db->covertUTCtoLocalAdvance("g:i A"," T(P)",$event['end'],$_SESSION['timezone']); ?>',
                    color: '<?=$color?>',
                    borderColor: '<?=$borderColor?>',
                    textColor: '<?=$textColor?>',
                    web_conf_link : '<?= addslashes(htmlspecialchars($event['web_conference_link'])); ?>',
                    web_con_sp : '<?= addslashes(htmlspecialchars($event['web_conference_sp'])); ?>',
                    venue_type : '<?= addslashes(htmlspecialchars($event['event_attendence_type'])); ?>',
                    event_type : '<?= addslashes(htmlspecialchars($event['event_type'] ?? ''));?>'
                },
                <?php	} ?>
            ],

            eventClick: function(info) {
                if (info.event.extendedProps.isprivate) {
                    return false;
                }
                if (info.event.url && info.event.extendedProps.event_class === 'event') {
                    getEventDetailModal(info.event.extendedProps.eventid,'<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId(0); ?>');
                    return false;
                } else {
                    viewHolidayDetail(info.event.extendedProps.eventid);
                    return false;
                }
            },
            eventMouseEnter: function(info) {
                let data = info.event.extendedProps;
                let tooltip;
                if (data.event_class === 'holiday') {
                    tooltip =
                        data.draft +
                        '<strong>' + data.safe_title + '</strong>' +
                        '<br>' +
                        '<strong>' + data.erglabel + ':</strong>' + data.erg +
                        '<br>' +
                        '<strong><?= addslashes(gettext("Start"));?>:</strong>' + data.time;
                } else {
                    let venue =
                        '<strong><?= addslashes(gettext("Venue"));?>:</strong>' + data.venue +
                        '<br>' +
                        '<strong><?= addslashes(gettext("Address"));?>: </strong>' + data.address +
                        '<br>';

                    if (data.venue_type == 2){
                        venue = '<strong><?= addslashes(gettext("Web Conf"));?>:</strong>' + data.web_con_sp + '</br>';

                    } else if (data.venue_type == 3){
                        venue += '<strong><?= addslashes(gettext("Web Conf"));?>:</strong>' + data.web_con_sp + '</br>';

                    } else if (data.venue_type == 4){
                        venue = '<strong><?= addslashes(gettext("Venue"));?>:</strong> <?= addslashes(gettext("Other"));?></br>';
                    }

                    if (data.isprivate == 1) {
                        venue = '';
                    }

                    tooltip =
                        data.draft +
                        '<strong>' + data.safe_title + '</strong>' +
                        '<br>' +
                        '<strong>' + data.erglabel + ':</strong> ' + data.erg +
                        '<br>' +
                        '<strong><?= addslashes(gettext("Event Type"));?>:</strong> ' + data.event_type +
                        '<br>' +
                        venue +
                        '<strong><?= addslashes(gettext("Start"));?>:</strong> ' +
                        data.time +
                        '<br>' +
                        '<strong><?= addslashes(gettext("End"));?>:</strong> ' + data.etime;
                }
                $(info.el).popover({
                    // title: data.draft,
                    content: tooltip,
                    html: true,
                    placement: 'top',
                    trigger: 'hover',
                }).popover('show');
            },
            eventMouseLeave: function(info) {
                info.el.removeAttribute('title');
            },
            dateClick: function() {
                tooltip.hide()
            },
            eventResizeStart: function() {
                tooltip.hide()
            },
            eventDragStart: function() {
                tooltip.hide()
            },
            moreLinkClick: function(info) {
                return "listMonth";
            },
            viewDisplay: function() {
                tooltip.hide()
            },
            datesSet: function(info) {
                var view = info.view
                var getDate = view.currentStart;
                var currentView = view.type;

                var currentDate  = moment(getDate).format('L'); // change this
                if (currentDate == '01/01/1970'){ // GET DATE FROM VIEW
                    currentDate = moment(view.start).format('L');
                }
                updateCurrentUrlParameters({'calendarDefaultDate':currentDate,'calendarDefaultView':currentView})
                $("#calendarDefaultView").val(currentView);
                $("#calendarDefaultDate").val(currentDate);
            },
        });

        calendar.render();
        var calendarLang = '<?=  $calendarLang ?>';
        if (calendarLang) {
                calendar.setOption('locale', calendarLang);
        }       
        
    });


    function refreshCalendarDyanamicFilters(reset_regions, trigger_src){
        $("#bygroup").html('');

        let selZones = $("#byZones").val();
        if (selZones.length == 0) {
            swal.fire({title: '<?= gettext("Error")?>',text:'<?= addslashes(gettext("One zone selection is required. If you wish to continue, select a zone or the home zone option will be selected by default."))?>'}).then(function(result) {
                $("#byZones").val('<?=$_COMPANY->encodeId($_ZONE->id())?>');
                $("#byZones").multiselect('refresh');
                $('.by-zones .multiselect').focus();
                refreshCalendarDyanamicFilters(reset_regions, trigger_src);
            });            
            setTimeout(() => {
                $(".swal2-confirm").focus();
            }, 100);

            return; // Return to allow the next recursion of refreshCalendarDyanamicFilters to run
        }

        // Careful in the following code-block position of return statement is very importatnt.
        let selCategories = ['all'];
        if (selZones.length == 1 && trigger_src == 'byCategory') {  // If one zone is selected
            if ($("#byCategory option:not(:selected)").length) { // if not all categories are selected
                selCategories = $("#byCategory").val();
                if (selCategories.length == 0 || selCategories == '0') {
                    swal.fire({
                        title: '<?= gettext("Error")?>',
                        text: '<?= gettext("One category selection is required. If you wish to continue, select a category or the first option will be selected by default.")?>'
                    }).then(function (result) {
                        $("#byCategory").prop("selectedIndex", 0);
                        $("#byCategory").multiselect('refresh');
                        $('.by-category .multiselect').focus();

                        if ($(".by-category .multiselect").is(":disabled")) {
                            $('.by-zones .multiselect').focus();
                        }
                        refreshCalendarDyanamicFilters(reset_regions, trigger_src);
                    });
                    setTimeout(() => {
                        $(".swal2-confirm").focus();
                    }, 100);
                    return; // Return to allow the next recursion of refreshCalendarDyanamicFilters to run
                }
            }
        }

        let selRegions = ['all'];
        if (!reset_regions && $("#byregion option:not(:selected)").length) { // If not all regions are selected
            selRegions = $("#byregion").val();
        }

        delayAjax(function(){
            // re-assign fresh value
            //selZones = $("#byZones").val();

            let params = {'zoneids':selZones.join(),'category':selCategories.join(), 'regionids': selRegions.join()};

            $.ajax({
                url: 'ajax_events.php?refreshCalendarDyanamicFilters=1',
                type: 'GET',
                data: params,
                success: function(data) {
                    $('#dynamic_filters').html(data);
                },
                error: function ( data ) {
                    swal.fire({title: 'Error!',text:'Internal server error, please try after some time.'});
                }
            });
        }, 250 );
    }

    function orderByDistance(){
        $("#byregion").val("all");
        getFilteredChapterList();
    }

    function orderByRegion(){
        $("#bydistance").val("100");
        getFilteredChapterList();
    }

    function getFilteredChapterList(){
        <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled'] && $_SESSION['app_type'] == 'affinities') { ?>
        let z = $("#byZones").val();
        let g = $("#bygroup").val();
        let c = $("#byCategory").val();

        let rval = 'all';
        if ($("#byregion option:not(:selected)").length) {
            rval = $("#byregion").val().join();
        }

        $.ajax({
            url: 'ajax_events.php?getFilteredChapterList=1',
            type: 'GET',
            data: {'zoneids':z.join(),'groupids':g.join(),'regionids':rval,'category':c.join()},
            success: function(data) {
                $('#byChapter').multiselect('destroy');
                $("#byChapter").html(data);
                $('#byChapter').multiselect({
                    nonSelectedText: "<?= sprintf(gettext('Select a %s'), $_COMPANY->getAppCustomization()['chapter']['name-short'])?>",
                    numberDisplayed: 1,
                    nSelectedText: "<?= sprintf(gettext('%s selected'), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'] )?>",
                    disableIfEmpty: true,
                    allSelectedText: "<?= sprintf(gettext('All %s selected'), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'] )?>",
                    includeSelectAllOption: true,
                    maxHeight: 400,
                    selectAllValue: 'multiselect-all'
                });
                getGlobalCalendarEventsByFilters();
            }
        });
        <?php } else { ?>
            getGlobalCalendarEventsByFilters();
        <?php }  ?>
    }

function setCalendarFilterState(s){
    localStorage.setItem("calendar_state_filter", s);
}
</script>
<div id='calendar'></div>