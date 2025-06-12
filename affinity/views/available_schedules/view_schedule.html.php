<style>
    .fc-bg-event {border: 1px solid darkgreen;}
</style>
<div class="modal tskp_skip_modal_tab_logic" tabindex="-1" role="dialog" id="viewSchedule">
    <div class="modal-dialog modal-lg modal-dialog-w1000" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= gettext('View Schedule');?></h5>
                <button type="button" id="btn_close" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="js-view-schedule-calendar"></div>
            </div>
        </div>
    </div>
</div>

<script> 
$('#viewSchedule').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus');
});

  setTimeout(function () {
      $(document).ready(function() {
          var calendarEl = document.getElementById('<?= $calendar_container_id?>');
          var calendar = new FullCalendar.Calendar(calendarEl, {
              headerToolbar: {
                  left: 'prev,next today',
                  center: 'title',
                  right: 'timeGridWeek,timeGridDay,listMonth',
              },
              validRange: {
                  start: '<?= $calendar_event_start_date; ?>',
                  end: '<?= $calendar_event_end_date ?>',
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

                  <?php foreach ($free_slots ?? [] as $free_slot) { ?>
                  <?= json_encode($free_slot) ?>,
                  <?php } ?>
              ],

              eventClick: function(info) {
                  /**
                   * In view scheduler, if user clicks on free slot, then do nothing
                   */
                  if (info.event.extendedProps.tskp_free_slot) {
                      return false;
                  }

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
                  var event_tooltip = (function () {
                      let data = info.event.extendedProps;
                      let tooltip;

                      /**
                       * In view scheduler, if user hovers on free slot, then show the tooltip present in the event data object
                       */
                      if (data.tskp_free_slot) {
                          return data.tskp_free_slot_tooltip;
                      }

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

                      return tooltip;
                  })();

                  $(info.el).popover({
                      // title: data.draft,
                      content: event_tooltip,
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
              /**
               * For calendar, the slot duration is the default 30 mins (https://fullcalendar.io/docs/slotDuration)
               * For view schedule, the calendar slot duration depends on the slot duration of the schedule
               */
              <?php if (isset($calendar_slot_duration)) { ?>
              slotDuration: '<?= $calendar_slot_duration ?>',
              <?php } ?>
              /**
               * For calendar, the scroll view starts from 6 AM which is the default (https://fullcalendar.io/docs/scrollTime)
               * For view schedule, the scroll view starts from the current hour
               */
              <?php if ($calendar_scroll_to_current_time ?? false) { ?>
              scrollTime: '<?= (new DateTime('now', new DateTimeZone($_SESSION['timezone'])))->format('H:00:00') ?>',
              <?php } ?>
          });

          calendar.render();
          var calendarLang = '<?=  $calendarLang ?>';
          if (calendarLang) {
              calendar.setOption('locale', calendarLang);
          }

      });

  }, 500);
</script>


