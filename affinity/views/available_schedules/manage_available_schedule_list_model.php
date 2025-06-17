<?php include_once __DIR__.'/../common/init_meeting_link_generator.php'; ?>
<style>
    li > a{
        color:black;
    }
#form_title {
    float: left;
}
</style>
<div id="manageAvailableScheduleModal" class="modal fade" tabindex="-1">
	<div aria-label="<?= gettext("Manage Available Schedules");?>" class="modal-dialog modal-lg modal-dialog-w1000" aria-modal="true" role="dialog">
		<div class="modal-content">
			<div class="modal-header">
                <div><h4 class="modal-title" id="form_title"><?= gettext("Manage Available Schedules");?>&nbsp;</h4>
                    <a class="new-schedule-btn" href="javascript:void(0);" onclick="addUpdateNewScheduleModal('<?= $_COMPANY->encodeId(0); ?>')"><i aria-label="<?= gettext("Add New Schedule");?>" class="fa fa-plus-circle mt-3"></i></a>
                    </div>

                  <div class="ml-auto">
                    <button class="btn btn-affinity" href="view_schedule" onclick="viewSchedule(event)">
                      <?= gettext('View Schedule') ?>
                    </button>
                    &nbsp;&nbsp;&nbsp;
                  </div>

				<button id="btn_close" aria-label="close" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">               
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive" id="availableScheduleContainer">
                            <table id="table_available_schedules" class="table table-hover display compact" style="width:100%" summary="This table display the list of available schedules">
                                <thead>
                                    <tr>
                                        <th style="width:20%;" class="text-left"><?= gettext("Schedule Title");?></th>
                                        <th style="width:20%;" class="text-left"><?= gettext("Schedule Scope");?></th>
                                        <th style="width:20%;" class="text-left"><?= gettext("Date");?></th>
                                        <th style="width:38%;" class="text-left"><?= gettext("Slots Summary");?></th>
                                        <th class="action-no-sort text-right" style="width:2%;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach($availableSchedules as $schedule){
                                    $schedule_scope = gettext('Team Event');
                                    if($schedule['schedule_scope'] == 'group_support'){
                                        $schedule_scope = gettext('Support Module');
                                    }
                                    $scheduleGroupsIds = Str::ConvertCSVToArray($schedule['groupids']);
                                    if ($scheduleGroupsIds) {
                                        $schedule_scope .= '<small>';
                                        $schedule_scope .= '<br>'.gettext('Restricted to:');
                                        //$schedule_scope .= '<ul>';
                                        foreach ($scheduleGroupsIds as $gid) {
                                            $g = Group::GetGroup($gid);
                                            $schedule_scope .= '<li>' . $g->val('groupname') . '</li>';
                                        }
                                        //$schedule_scope .= '</ul>';
                                        $schedule_scope .= '</small>';
                                    }
                                    
                                ?>
                                    <tr style="background-color:<?= $schedule['isactive'] == 1 ? '' : '#ffffce'; ?>;">
                                        <td class="text-left">
                                            <?= htmlspecialchars($schedule['schedule_name']); ?>
                                        </td>
                                        <td class="text-left">
                                            <?= $schedule_scope; ?>
                                        </td>
                                        <td class="text-left">
                                            <p><?= $schedule['start_date_in_user_tz']; ?></p>
                                            <p class="pl-4">to</p>
                                            <p><?= $schedule['end_date_in_user_tz']; ?></p>
                                        </td>
                                        <td class="text-left">
                                            
                                            <p><?= gettext('Duration'); ?>: <?= $schedule['schedule_slot']; ?> <?= gettext("Minutes")?></p>
                                            
                                            <p><?= gettext('Start Time Buffer'); ?>: <?= $schedule['start_time_buffer']; ?> <?= gettext("Hour(s)")?></p>

                                            <p><?= gettext('Timezone'); ?>: <?= $schedule['user_tz']; ?></p>
                                            <button class="btn btn-sm btn-link" onclick="viewScheduleStats('<?= $_COMPANY->encodeId($schedule['schedule_id']); ?>')" ><?= gettext("View Stats");?></button>
                                        </td>
                                        <td >
                                            <div class="">
                                                <button class="btn-no-style dropdown-toggle fa fa-ellipsis-v mt-3" data-toggle="dropdown"></button>
                                                <ul class="dropdown-menu" style="width:200px;">
                                                <?php if($schedule['isactive'] == 1){ ?>

                                                    <li>
                                                        <a href="javascript:void(0)" class="confirm pop-identifier" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to deactivate this schedule");?>?" onclick="activateDeactivateSchedule('<?= $_COMPANY->encodeId($schedule['schedule_id']); ?>','<?= $_COMPANY->encodeId(0); ?>')" ><i class="fa fa-lock" aria-hidden="true" ></i>&emsp;<?= gettext("Deactivate");?></a>
                                                    </li>

                                                <?php } else{ ?>
                                                    <li>
                                                        <a href="javascript:void(0)" class="confirm pop-identifier" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to activate this schedule");?>?" onclick="activateDeactivateSchedule('<?= $_COMPANY->encodeId($schedule['schedule_id']); ?>','<?= $_COMPANY->encodeId(1); ?>')" ><i class="fa fa-unlock-alt" aria-hidden="true" ></i>&emsp;<?= gettext("Activate");?></a>
                                                    </li>
                                                <?php  if ($_COMPANY->getAppCustomization()['event']['meeting_links']['msteams'] && $schedule['link_generation_method'] == 'automatic') { ?>
                                                    <li>
                                                        <a href="javascript:void(0)" class="confirm pop-identifier" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to Generate New Meeting Links?");?>" onclick="TeamsSignInScheduler('<?= $_COMPANY->encodeId($schedule['schedule_id']); ?>'); $('#active_schedule_id').val('<?= $_COMPANY->encodeId($schedule['schedule_id']); ?>');" ><i class="fa fa-link" aria-hidden="true" ></i>&emsp;<?= gettext("Generate Meeting Links");?></a>
                                                    </li>
                                                <?php } ?>
                                                    <li>
                                                        <a href="javascript:void(0)" class="" onclick="addUpdateNewScheduleModal('<?= $_COMPANY->encodeId($schedule['schedule_id']); ?>')"><i class="fa fas fa-edit" aria-hidden="true"></i>&emsp;<?= gettext("Update");?></a>
                                                    </li>
                                                <?php if(0){ // Disable the copy feature because it doesn't make sense anymore. ?>
                                                    <li>
                                                        <a href="javascript:void(0)" class="" onclick="copyMeetingLink('<?= $schedule['user_meeting_link']; ?>')"><i class="fa fas fa-clipboard" aria-hidden="true"></i>&emsp;<?= gettext("Copy meeting Link");?></a>
                                                    </li>
                                                <?php } ?>
                                                    <li>
                                                        <a href="javascript:void(0)" class="confirm pop-identifier" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to delete this schedule");?>?" onclick="deleteSchedule('<?= $_COMPANY->encodeId($schedule['schedule_id']); ?>')" ><i class="fa fa-trash" aria-hidden="true" ></i>&emsp;<?= gettext("Delete");?></a>
                                                    </li>
                                                <?php } ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
			</div>
		</div>  
	</div>
</div>

<input type="hidden" name="active_schedule_id" id="active_schedule_id">
<input type="hidden" name='scheduler_meetinglinks' id="scheduler_meetinglinks">

<script>
    $(document).ready(function () {
        const target = document.getElementById("scheduler_meetinglinks");

        // MutationObserver to monitor changes to the hidden input's value
        const observer = new MutationObserver(() => {
            saveScheduleMeetingLinks();
        });

        // Observe the hidden input's attributes for changes
        observer.observe(target, { attributes: true, attributeFilter: ['value'] });

    });
</script>

<script>
    function viewScheduleStats(i){
        var container_id = 'js-view-schedule_stats';
        var container = $('#' + container_id);
        if (!container.length) {
            $('#loadAnyModal').after(`<div id="${container_id}"></div>`);
            container = $('#' + container_id);
        }
        $.ajax({
            url: 'ajax_user_schedule.php?viewScheduleStats=1',
            type: 'GET',
            data: {'schedule_id':i},
            success: function(data) {
                try {
                    var json = JSON.parse(data);
                    Swal.fire({
                        title: json.title,
                        text: json.message
                    });
                } catch (e) {
                    openNestedModal(container, data);
                }
            }
        });
    }

    function saveScheduleMeetingLinks(){
        Swal.fire({
            title: '<?= addslashes(gettext('Saving Meeting Links')); ?>',
            html: '<?= addslashes(gettext('Your scheduled meeting links are being saved, this may take a moment.')); ?>',
            timer: 30000,
            timerProgressBar: true,
        });

        let active_schedule_id = $('#active_schedule_id').val();
        let scheduler_meetinglinks = $('#scheduler_meetinglinks').val();
        if (!active_schedule_id ||  !scheduler_meetinglinks) {
            swal.fire({title:"Error",text:'<?= addslashes(gettext("Something went wrong while saving the links. Please try again.")); ?>'});
            return;
        }

        $.ajax({
            url: 'ajax_user_schedule.php?saveScheduleMeetingLinks=1',
            type: 'POST',
            data: {'schedule_id':active_schedule_id,'scheduler_meetinglinks': scheduler_meetinglinks},
            success: function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title:jsonData.title,text:jsonData.message});
                    
                } catch(e) {
                    closeAllActiveModal();
                    $('#modal_over_modal').html(data);
                    $('#addUpdateScheduleModal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                }
            }
        });

    }

function copyMeetingLink(link) {  
    // Create a temporary textarea element
    var tempTextArea = $("<textarea>");
    $("#manageAvailableScheduleModal").append(tempTextArea);
    // Set the value of the temporary textarea to the text to copy
    tempTextArea.val(link).select();
    try {
        // Execute the copy command
        document.execCommand("copy");
        // Show success message
        swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            customClass: {popup: 'colored-toast'},
            timer: 5000
        }).fire({
            text: 'Meeting link has been copied to clipboard!',
            icon: 'success'
        });
    } catch (err) {
        swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            customClass: {popup: 'colored-toast'},
            timer: 5000
        }).fire({
            text: 'Unable to copy meeting link!',
            icon: 'warning'
        });
    }
    // Remove the temporary textarea
    tempTextArea.remove();
      
}
$('#manageAvailableScheduleModal').on('shown.bs.modal', function () {
    $('.new-schedule-btn').trigger('focus')
});
</script>

<script>
    $(document).ready(function(){
        $('#table_available_schedules').DataTable( {
			"order": [],
			"bPaginate": true,
			"bInfo" : false,
            'language': {
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },	
            'aoColumnDefs': [{
                'bSortable': false,
                'aTargets': ['action-no-sort']
             }],
			
		});
    });

$('.pop-identifier').each(function() {
	$(this).popConfirm({
	container: $("#manageAvailableScheduleModal"),
	});
});

function addUpdateNewScheduleModal(i) {
    $.ajax({
        url: 'ajax_user_schedule.php?addUpdateNewScheduleModal=1',
        type: 'GET',
        data: {'schedule_id':i},
        success: function(data) {
            try {
                let jsonData = JSON.parse(data);
                swal.fire({title:jsonData.title,text:jsonData.message});
                
            } catch(e) {
                closeAllActiveModal();
                $('#modal_over_modal').html(data);
                $('#addUpdateScheduleModal').modal({
                    backdrop: 'static',
                    keyboard: false
                });
            }
        }
    });
}

function deleteSchedule(i) {
    $.ajax({
        url: 'ajax_user_schedule.php?deleteSchedule=1',
        type: 'GET',
        data: {'schedule_id':i},
        success: function(data) {
            try {
                let jsonData = JSON.parse(data);
                swal.fire({
                    title: jsonData.title,
                    text: jsonData.message,
                    allowOutsideClick: false
                }).then(function(result) {
                    if (jsonData.status == 1) {
                        manageAvailalbeSchedules();
                    }
                });
                $(".swal2-confirm").focus();  
            } catch (e) {
                swal.fire({title: 'Error', text: "Unknown error."});
            }
        }
    });
}

function activateDeactivateSchedule(i,s) {
    $.ajax({
        url: 'ajax_user_schedule.php?activateDeactivateSchedule=1',
        type: 'POST',
        data: {'schedule_id':i,'status':s},
        success: function(data) {
            try {
                let jsonData = JSON.parse(data);
                swal.fire({
                    title: jsonData.title,
                    text: jsonData.message,
                    allowOutsideClick: false
                }).then(function(result) {
                    if (jsonData.status == 1) {
                        manageAvailalbeSchedules();
                    }
                });
                $(".swal2-confirm").focus();  
            } catch (e) {
                swal.fire({title: 'Error', text: "Unknown error."});
            }
        }
    });

}

function viewSchedule(jsevent) {
  var container_id = 'js-view-schedule';
  var container = $('#' + container_id);
  if (!container.length) {
    $('#loadAnyModal').after(`<div id="${container_id}"></div>`);
    container = $('#' + container_id);
  }

  $.ajaxSetup({cache: true});

  // fullcalendar lib
  if (typeof FullCalendar === 'undefined') {
    $.getScript("<?=TELESKOPE_.._STATIC?>/vendor/js/fullcalendar-6.1.15/dist/index.global.min.js", function () {
      viewSchedule(jsevent);
    });
    return;
  }

  /**
   * TODO for later
   * We are using momentjs in our current calendar page
   * We should probably remove its usage as its deprecated
   * https://momentjs.com/docs/#/-project-status/
   */
  if (typeof moment === 'undefined') {
    $.getScript("<?=TELESKOPE_.._STATIC?>/vendor/js/moment-2.30.1/min/moment.min.js", function () {
      viewSchedule(jsevent);
    });
    return;
  }

  var btn = $(jsevent.target);

  $.ajax({
    url: 'ajax_user_schedule.php?viewSchedule=1',
    type: 'GET',
    tskp_submit_btn: btn,
    success: function (data) {
      try {
          var json = JSON.parse(data);
          Swal.fire({
            title: json.title,
            text: json.message
          }).then(function () {
            btn.focus();
          });
          setTimeout(() => {
            $('.swal2-confirm').focus();
          }, 500);
      } catch (e) {
        openNestedModal(container, data);
      }
    },
  });
}

</script>