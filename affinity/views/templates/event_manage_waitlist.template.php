<style>
    .hide{
        display:none;
    }
    .progress_bar{
        background-color: #efefef;
        margin: 5px 0px;
        padding: 15px;
    }
</style>
<div id="manageWaitList" class="modal fade">
    <div aria-label="<?= ($event->val('max_inperson') || $event->val('max_online')) ? gettext("Manage RSVP List") : gettext("Manage RSVP List"); ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
            <h4 id="modal-title" class="modal-title">
    <?= ($event->val('max_inperson') || $event->val('max_online')) ? gettext("Manage RSVP List") : gettext("Manage RSVP List"); ?>
            </h4>
              
                <button type="button" id="btn_close" class="close" aria-hidden="true" data-dismiss="modal">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="row mt-2">
                    <div class="col-md-12"><strong><?= gettext("Event Title");?>: </strong><?= $event->val('eventtitle'); ?></div>
                </div>

                <?php  if (($event->val('event_attendence_type') == 1 || $event->val('event_attendence_type') == 3) && $event->val('max_inperson')) { ?>
                <div class="row m-3" style="border: 1px lightgrey solid;">
                    <div class="col-md-6 mt-2"><strong><?= gettext("In-person Capacity Summary");?>: </strong> &emsp;<i class="fa fas fa-edit" aria-hidden="true" onclick="inpersonUpdateForm(1)"></i></div>
                    <div class="col-md-6 mt-2 text-right">
                        <div class="form-group">
							<label class="control-lable"><?= gettext('Automatic Waitlist Processing');?></label>
							<div class="btn-group btn-group-sm btn-group-toggle" id="status" data-toggle="buttons" style="height: 32px; padding: 0!important;">
								<label class="btn btn-default btn-on btn-xs adv <?= $event->val('automanage_inperson_waitlist') == '1' ? 'active' : ''; ?>">
									<input type="radio" value="1" name="automanage_inperson_waitlist"  <?= $event->val('automanage_inperson_waitlist') == '1' ? 'checked' : ''; ?>>ON
								</label>
								<label class="btn btn-default btn-off btn-xs adv <?= $event->val('automanage_inperson_waitlist') == '0' ? 'active' : ''; ?>"">
									<input type="radio" value="0" name="automanage_inperson_waitlist" <?= $event->val('automanage_inperson_waitlist') == '0' ? 'checked' : ''; ?> >OFF
								</label>
							</div>
                        </div>
                    </div>
                    <div class="col-md-12 person" id="inPersonStats">
                        <div class="col-md-6"><?= gettext("In-Person Limit");?> :</div>
                        <div class="col-md-6"><span id="inpersonslots"><?= $event->isParticipationLimitUnlimited('max_inperson') ? 'Unlimited' : $event->val('max_inperson'); ?></span></div>
                        <div class="col-md-6"><?= gettext("In-Person Waitlist Limit");?> :</div>
                        <div class="col-md-6"><span id="inpersonslots_waitlist"><?= $event->isParticipationLimitUnlimited('max_inperson_waitlist') ? 'Unlimited' : $event->val('max_inperson_waitlist'); ?></span></div>
                        <div class="col-md-6"><?= gettext("Total Attend In-Person");?> :</div>
                        <div class="col-md-6"><?= $totalInpersonConfirmed; ?></div>
                        <div class="col-md-6"><?= gettext("Total Waitlist In-Person");?> :</div>
                        <div class="col-md-6 "><?= $totalInpersonWaiting; ?></div>
                    </div>

                    <div class="col-md-12 person hide mb-2"  id="inPersonUpdate">
                        <div class="col-md-4"><label ><?= gettext("Event In-Person Limit");?> : </label><br><input type="number" class="form-control" id="inperson"
                          <?= $event->isParticipationLimitUnlimited('max_inperson')
                            ?
                            'disabled'
                            :
                            'value="' . $event->val('max_inperson') . '"'
                          ?>
                        >
                          <div class="form-check">
                            <label class="form-check-label">
                              <input
                                name="inperson_limit_unlimited"
                                type="checkbox"
                                class="form-check-input"
                                data-target="#inperson"
                                <?= $event->isParticipationLimitUnlimited('max_inperson')
                                  ?
                                  'checked disabled data-value="0"'
                                  :
                                  'data-value="' . $event->val('max_inperson') . '"'
                                ?>
                                onchange="participationLimitUnlimitedToggle(event)"

                              >
                              <?= gettext('Mark as unlimited') ?>
                            </label>
                          </div>
                        </div>
                        <div class="col-md-4"><label ><?= gettext("Event In-Person Waitlist");?> : </label><br><input type="number" class="form-control" min="0"  id="inperson_waitlist"
                          <?= $event->isParticipationLimitUnlimited('max_inperson_waitlist')
                            ?
                            'disabled'
                            :
                            'value="' . $event->val('max_inperson_waitlist') . '"'
                          ?>
                        >
                          <div class="form-check">
                            <label class="form-check-label">
                              <input
                                name="inperson_waitlist_unlimited"
                                type="checkbox"
                                class="form-check-input"
                                data-target="#inperson_waitlist"
                                <?= $event->isParticipationLimitUnlimited('max_inperson_waitlist')
                                  ?
                                  'checked disabled data-value="0"'
                                  :
                                  'data-value="' . $event->val('max_inperson_waitlist') . '"'
                                ?>
                                onchange="participationLimitUnlimitedToggle(event)"
                              >
                              <?= gettext('Mark as unlimited') ?>
                            </label>
                          </div>
                        </div>
                        <div class="col-md-4 mt-4"><button type="button" class="btn btn-sm btn-affinity" onclick="updateInPersonSlots('<?= $_COMPANY->encodeId($event->val('eventid'));?>')"
                          <?= $event->isParticipationLimitUnlimited('max_inperson') && $event->isParticipationLimitUnlimited('max_inperson_waitlist') ? 'disabled' : '' ?>
                          ><?= gettext("Update");?></button>&nbsp;<button type="button" class="btn btn-sm btn-affinity " onclick="inpersonUpdateForm()"><?= gettext("Cancel");?></button></div>
                    </div>
                </div>
                <?php } ?>

                <?php  if (($event->val('event_attendence_type') == 2 || $event->val('event_attendence_type') == 3) && $event->val('max_online')) { ?>
                <div class="row m-3" style="border: 1px lightgrey solid;">
                    <div class="col-md-6 mt-2"><strong><?= gettext("Online Waitlist Summary");?>: </strong>&emsp;<i id="online-update-form" tabindex="0" class="fa fas fa-edit" onclick="onlineUpdateForm(1)"></i></div>
                    <div class="col-md-6 mt-2 text-right">
                        <div class="form-group">
							<label class="control-lable"><?= gettext('Automatic Waitlist Processing');?></label>
							<div class="btn-group btn-group-sm btn-group-toggle" id="status" data-toggle="buttons" style="height: 32px;padding: 0!important;">
								<label class="btn btn-default btn-on btn-xs adv <?= $event->val('automanage_online_waitlist') == '1' ? 'active' : ''; ?>">
									<input type="radio" value="1" name="automanage_online_waitlist"  <?= $event->val('automanage_online_waitlist') == '1' ? 'checked' : ''; ?>>ON
								</label>
								<label class="btn btn-default btn-off btn-xs  adv <?= $event->val('automanage_online_waitlist') == '0' ? 'active' : ''; ?>">
									<input type="radio" value="0" name="automanage_online_waitlist" <?= $event->val('automanage_online_waitlist') == '0' ? 'checked' : ''; ?> >OFF
								</label>
							</div>
                        </div>
                    </div>
                    <div class="col-md-12 online" id="onlineStats">
                        <div class="col-md-6"><?= gettext("Online Limit");?>  :</div>
                        <div class="col-md-6"><span id="onlineslots"><?= $event->isParticipationLimitUnlimited('max_online') ? 'Unlimited' : $event->val('max_online'); ?></span></div>
                        <div class="col-md-6"><?= gettext("Online Waitlist Limit");?>  :</div>
                        <div class="col-md-6"><span id="onlineslots_waitlist"><?= $event->isParticipationLimitUnlimited('max_online_waitlist') ? 'Unlimited' : $event->val('max_online_waitlist'); ?></span></div>
                        <div class="col-md-6"><?= gettext("Total Attend Online");?> :</div>
                        <div class="col-md-6"><?= $totalOnlineConfirmed; ?></div>
                        <div class="col-md-6"><?= gettext("Total Waitlist Online");?> :</div>
                        <div class="col-md-6"><?= $totalOnlineWaiting; ?></div>
                    </div>

                    <div class="col-md-12 mt-2 online hide mb-2" id="onlineUpdate" >
                        <div class="col-md-4"><label ><?= gettext("Event Online Limit");?> : </label><br><input type="number" class="form-control" min="1" maxlength="6" inputmode="numeric" oninput="this.value = parseInt(this.value.replace(/[^0-9]/g, ''));" id="online"
                            <?= $event->isParticipationLimitUnlimited('max_online')
                              ?
                              'disabled'
                              :
                              'value="' . $event->val('max_online') . '"'
                            ?>
                          >
                          <div class="form-check">
                            <label class="form-check-label">
                              <input
                                name="online_limit_unlimited"
                                type="checkbox"
                                class="form-check-input"
                                data-target="#online"
                                <?= $event->isParticipationLimitUnlimited('max_online')
                                  ?
                                  'checked disabled data-value="0"'
                                  :
                                  'data-value="' . $event->val('max_online') . '"'
                                ?>
                                onchange="participationLimitUnlimitedToggle(event)"
                              >
                              <?= gettext('Mark as unlimited') ?>
                            </label>
                          </div>
                        </div>
                        <div class="col-md-4"><label ><?= gettext("Event Online Waitlist");?> : </label><br>
                        <input id="online_waitlist" class="form-control" type="number" inputmode="numeric" min="0" maxlength="6" oninput="this.value = parseInt(this.value.replace(/[^0-9]/g, ''));"
                          <?=
                            $event->isParticipationLimitUnlimited('max_online_waitlist')
                            ?
                            'disabled'
                            :
                            'value="' . $event->val('max_online_waitlist') . '"'
                          ?>
                        />
                          <div class="form-check">
                            <label class="form-check-label">
                              <input
                                name="online_waitlist_unlimited"
                                type="checkbox"
                                class="form-check-input"
                                data-target="#online_waitlist"
                                <?= $event->isParticipationLimitUnlimited('max_online_waitlist')
                                  ?
                                  'checked disabled data-value="0"'
                                  :
                                  'data-value="' . $event->val('max_online_waitlist') . '"'
                                ?>
                                onchange="participationLimitUnlimitedToggle(event)"
                              >
                              <?= gettext('Mark as unlimited') ?>
                            </label>
                          </div>
                        </div>
                        <div class="col-md-4 mt-4"><button type="button" class="btn btn-sm btn-affinity" onclick="updateOnlineSlots('<?= $_COMPANY->encodeId($event->val('eventid'));?>')"
                          <?= $event->isParticipationLimitUnlimited('max_online') && $event->isParticipationLimitUnlimited('max_online_waitlist') ? 'disabled' : '' ?>
                          ><?= gettext("Update");?></button>&nbsp;<button type="button" class="btn btn-affinity" onclick="onlineUpdateForm()"><?= gettext("Cancel");?></button></div>
                    </div>
                </div>
                <?php } ?>

                <div class="row m-3 pt-2 pb-2" style="border: 1px lightgrey solid;">
                    <div class="col-md-12">
                        <strong><?= gettext("RSVP close time");?> :</strong>
                        <span id="dueDate"><span style="color: <?=$event->hasRSVPEnded() ? 'red':'green'?>;"> <?= $event->val('rsvp_dueby') ?  $db->covertUTCtoLocalAdvance("l M j, Y h:i A","",  $event->val('rsvp_dueby'),$_SESSION['timezone']) :'- '.gettext("Not set").' -'; ?></span></span>
                        &emsp;&emsp;&emsp;
                        <button type="button" class="btn btn-sm btn-affinity" data-toggle="modal" data-target="#rsvpCloseTimeModal"><?= gettext("Update");?></button>
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <div class="row mt-2">
                    <div class="col-md-8 mb-2"><strong><?= gettext("RSVP List");?>: </strong></div>
                    <div class="col-md-4 mb-2 text-right" id="add_rsvp_btn"><button type="button" class="btn btn-affinity btn-sm" onclick="$('#add_rsvp_btn').hide();$('#add_rsvp_user').show();">+ <?= gettext("Add RSVP");?></button></div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 progress_bar hide">
                        <p><?= gettext("Updating <span id ='totalBulkRecored'></span> record(s). Please wait.");?></p>
                        <div class="progress">
                            <div class="progress-bar progress-bar-animated" id="prgress_bar" style="width:0%"></div>
                        </div>
                        <div class="text-center progress_status" aria-live="polite"></div>
                    </div>
                    <div class="col-md-11 p-3 ml-3" id="add_rsvp_user" style="display: none;border:1px solid rgb(212, 212, 212);">
                        <p id="updatingRSVP"></p>
                        <form id="eventRSVPform">
                            <div class="form-group">
                                <label for="uname"><?= gettext("Search User for RSVP");?>:</label>
                                <div class="col-md-12">
                                    <input class="form-control" autocomplete="off" onkeyup="searchUsersForRSVP(this.value,'<?=$_COMPANY->encodeId($event->val('groupid'))?>','<?=$_COMPANY->encodeId($event->id())?>')" placeholder="<?= gettext("Search user");?>"  type="text" required>
                                    <div id="show_dropdown"> </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="uname"><?= gettext("RSVP Option");?>:</label>
                                <div class="col-md-12">
                                    <select class="form-control" style="width: 100%;" name="rsvpOption" id="rsvpOption">
                                        <option value=""><?= gettext("Select RSVP option");?></option>
                                    <?php foreach($rsvpOptions['buttons'] as $rsvpId => $buttonLabel){ ?>
                                        <option value="<?= $rsvpId; ?>"><?= $buttonLabel; ?></option>
                                    <?php } ?>
                                    </select>
                                </div>
                               
                            </div>
                            <div class="col-md-12 text-center">
                                <button type="button" class="btn btn-affinity btn-sm" onclick="addUsersToEventRSVPsList('<?= $_COMPANY->encodeId($eventid);?>')"><?= addslashes(gettext("Add RSVP"));?></button>
                                <button type="button" class="btn btn-affinity btn-sm" onclick="$('#add_rsvp_btn').show();$('#add_rsvp_user').hide();$('#searchUsers').empty().trigger('change');"><?= gettext("Cancel");?></button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-12 mt-3">
                        <div class="table-responsive " id="list-view">
                            <table id="event_manage_waitlist_table" class="table table-hover display compact" width="100%" summary="This table displays this list of RSVPs of an event">
                                <thead>
                                <tr>
                                    <th width="25%" scope="col"><?= gettext("First&nbsp;Name");?></th>
                                    <th width="25%" scope="col"><?= gettext("Last&nbsp;Name");?></th>
                                    <th width="30%" scope="col"><?= gettext("Title");?></th>
                                    <th width="20%" scope="col">
                                    <?php  if (($event->val('event_attendence_type') == 1 || $event->val('event_attendence_type') == 3) && $event->val('max_inperson')) { ?>
                                        <div class="">
                                            <select class="" onchange="updateBulkWaitlistCancel('<?= $_COMPANY->encodeId($event->val('eventid'));?>',this.value)">
                                                <option value="">Bulk Update</option>
                                            <?php  if ($event->val('event_attendence_type') == 1 || $event->val('event_attendence_type') == 3) { ?>
                                                <option value="cancel_inperson_waitlist" ><?= gettext("Cancel In-Person Waitlist");?></option>
                                            <?php } ?>

                                            <?php  if ($event->val('event_attendence_type') == 2 || $event->val('event_attendence_type') == 3) { ?>
                                                <option value="cancel_online_waitlist" ><?= gettext("Cancel Online Waitlist");?></option>
                                            <?php } ?>

                                            <?php  if ($event->val('event_attendence_type') == 3) { ?>
                                                <option value="cancel_all_waitlist" ><?= gettext("Cancel All Waitlist");?></option>
                                            <?php } ?>
                                            </select>
                                        </div>
                                    <?php } else { ?>
                                        <?= gettext("Action"); ?>
                                    <?php } ?>
                                    </th>
                                </tr>
                                </thead>
                                <tbody>

                                <?php
                                $i = 0;
                                foreach ($data as $joinee) {
                                ?>
                                    <tr id="<?= $i++; ?>" style="cursor: pointer;">
                                        <td><?= $joinee['firstname']; ?></td>
                                        <td><?= $joinee['lastname']; ?></td>
                                        <td><?= $joinee['jobtitle']; ?></td>
                                        <td>
                                            <div id="<?= $_COMPANY->encodeId($joinee['joineeid']);?>" class='out_of_screen'><?=  Event::GetRSVPLabel($joinee['joinstatus']); ?></div>  <!-- This Div Needs for Sorthing this comumn data-->
                                            <div class="">
                                            <select class="" onchange="changeRsvpStatus('<?= $_COMPANY->encodeId($event->val('eventid'));?>',this.value, '<?= $_COMPANY->encodeId($joinee['joineeid']);?>')">
                                                <?php if (empty($joinee['joinstatus'])) { ?>
                                                <option value="<?= $_COMPANY->encodeId(0) ?>"><?= gettext('No Response') ?></option>
                                                <?php } ?>
                                            <?php foreach($rsvpOptions['buttons'] as $rsvpId => $buttonLabel){ ?>
                                                 <option value="<?= $rsvpId ?>" <?= $rsvpId == $joinee['joinstatus'] ? 'selected' : ''; ?>><?= $buttonLabel ?></option>
                                            <?php } ?>
                                            </select>
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
            <script>

                $("input[name='automanage_inperson_waitlist']").on('change', function() {
                    var automanage_inperson_waitlist  = $('input[name=automanage_inperson_waitlist]:checked').val();
                    $.ajax({
                        url: 'ajax_events.php?updateAutomanageWaitlist=1',
                        type: "POST",
                        data: {eventid:'<?= $_COMPANY->encodeId($event->val('eventid')); ?>','value':automanage_inperson_waitlist,section:1},
                        success : function(data) {
                            try {
                                let jsonData = JSON.parse(data);
                                swal.fire({title: jsonData.title,text:jsonData.message});
                            } catch(e) {
                                swal.fire({title: 'Error', text: "Unknown error."});
                            }
                        }
                    });
                   
                });

                $("input[name='automanage_online_waitlist']").on('change', function() {

                    var automanage_online_waitlist  = $('input[name=automanage_online_waitlist]:checked').val();
                    $.ajax({
                        url: 'ajax_events.php?updateAutomanageWaitlist=1',
                        type: "POST",
                        data: {eventid:'<?= $_COMPANY->encodeId($event->val('eventid')); ?>','value':automanage_online_waitlist,section:2},
                        success : function(data) {
                            try {
                                let jsonData = JSON.parse(data);
                                swal.fire({title: jsonData.title,text:jsonData.message});
                            } catch(e) {
                                swal.fire({title: 'Error', text: "Unknown error."});
                            }
                        }
                    });

                });



                $(document).ready(function () {
                    $('#event_manage_waitlist_table').DataTable({
                        "order": [],
                        "bPaginate": true,
                        "bInfo": false,
                        "columnDefs": [
                         { targets: [-1], orderable: false }
                         ],
                        'language': {
                            url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
                        },
                    });
                });
            </script>
            <div class="modal-footer text-center">
                <button type="button" class="btn btn-affinity" data-dismiss="modal"><?= gettext("Close");?></button>
            </div>
        </div>
    </div>
</div>

<?php

// $event->val('rsvp_dueby').' UTC'
$rsvpCloseTimestamp = time();
if($event->val('rsvp_dueby')){
    $rsvpDueBy = $event->val('rsvp_dueby').' UTC';
    $rsvpCloseTimestamp  = strtotime($rsvpDueBy) ?: time();
}
[$publish_Ymd,$publish_h,$publish_i,$publish_A] = explode ("%",date("Y-m-d%h%i%A", $rsvpCloseTimestamp));
//RoundUp minutes to the nearest 5 in "05" format.
$publish_i = sprintf("%02d",ceil((int)$publish_i/5)*5);

// Set the date for the datpicker as per the event
$closeOnDate = '';
if($event->val('rsvp_dueby')){
    $closeOnDate = $publish_Ymd;
}
?>
<div id="rsvpCloseTimeModal" class="modal fade">
    <div class="modal-dialog" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 tabindex="0" class="modal-title" id="general_schedule_publish_title"><?= gettext("Update RSVP close time");?></h4>
                <button aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="col-sm-12">
                    <form class="" id="rsvpEndDatetime">
                       
                       
                        <div id="schedule_later_option" class="form-group">
                            <label class="col-sm-3"><strong><?= gettext("When");?>:</strong></label>
                            <div class="col-sm-9">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" value="now" id="now_date_time" name="rsvp_end_date_time" required checked>
                                    <label class="form-check-label" for="now_date_time">
                                        <?= gettext("Close Now");?>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" value="scheduled" id="scheduled_date_time" name="rsvp_end_date_time" required>
                                    <label class="form-check-label" for="scheduled_date_time">
                                        <?= gettext("Close at a later time");?>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="schedule_later_form" class="schedule_later_box" style="display: none;">
                        <div class="form-group ">
                            <p><strong><?= gettext("Close On");?></strong></p>
                        </div>
                        <div class="form-group ">
                        <div class="row">
                            <label class="col-sm-2"><?= gettext("Date");?></label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="start_date" name="publish_Ymd"
                                       value="" placeholder="YYYY-MM-DD" readonly required>
                            </div>
                        </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                            <label for="inputEmail" class="col-sm-2 control-lable"><?= gettext("Time");?></label>
                            <div class="col-sm-3 hrs-minutes">
                                <select class="form-control" id="publishtime" name='publish_h' readonly="readonly" required>
                                    <?=getTimeHoursAsHtmlSelectOptions($publish_h);?>
                                </select>
                            </div>
                            <div class="col-sm-3 hrs-minutes">
                                <select class="form-control" name="publish_i" required>
                                    <?=getTimeMinutesAsHtmlSelectOptions($publish_i);?>
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label class="radio-inline"><input type="radio" value="AM" name="publish_A" required <?= ($publish_A == 'AM') ? 'checked' : '' ?>>AM</label>
                                <label class="radio-inline"><input type="radio" value="PM" name="publish_A" <?= ($publish_A == 'PM') ? 'checked' : '' ?>>PM</label>
                            </div>
                            </div>
                            <div class="row">
                            <div class="col-sm-2">&nbsp;</div>
                            <div class="col-sm-10">
                                <p class='timezone' onclick="showTzPicker();">
                                    <a href="#tz_show" class="link_show" id="tz_show">
                                        <?= $_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' ?>Time
                                    </a>
                                </p>
                            </div>
                            </div>
                            <div class="row">
                            <input type="hidden" name="timezone" id="tz_input"
                                   value="<?= $_SESSION['timezone'] ? $_SESSION['timezone'] : 'UTC' ?>">
                            <div id="tz_div" style="display:none;">
                                <div class="col-sm-2">&nbsp;</div>
                                <div class="col-sm-10">
                                    <select class="form-control teleskope-select2-dropdown" id="selected_tz" onchange="selectedTimeZone()" style="width: 100%;">
                                        <?php echo getTimeZonesAsHtmlSelectOptions($_SESSION['timezone']); ?>
                                    </select>
                                </div>
                            </div>
                            </div>
                        </div>
                        </div>
                    </form>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="modal-footer ">
                <button id="updateRsvpEndTime" type="submit" class="btn btn-affinity confirm text-center" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext("Are you sure you want to update RSVP close time?");?>" style="background-color: #0077B5 !important;"
                        onclick="updateRsvpEndTime('<?= $_COMPANY->encodeId($event->val('eventid'));?>')"><?= gettext("Submit");?>
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    $('#rsvpCloseTimeModal').on('shown.bs.modal', function() {
        $('#general_schedule_publish_title').trigger('focus');
    });
</script>
<script>
    function inpersonUpdateForm(){
        $('.person').toggleClass("hide");
    }
    function onlineUpdateForm(){
        $('.online').toggleClass("hide");
    }

    function updateInPersonSlots(e){
        let inperson = $("#inperson").val();
        let inperson_waitlist = $("#inperson_waitlist").val();

        var container = $('#inPersonUpdate');
        var inperson_limit_unlimited = container.find('input[name="inperson_limit_unlimited"]').is(':checked') ? 1 : 0;
        var inperson_waitlist_unlimited = container.find('input[name="inperson_waitlist_unlimited"]').is(':checked') ? 1 : 0;

        if (inperson < 1 && !inperson_limit_unlimited) {
            swal.fire({title: 'Error',text:"<?= gettext("Cannot set new In-Person limit to less than 1");?>"});
            return;
        }

        if (inperson_waitlist < 0 && !inperson_waitlist_unlimited) {
            swal.fire({title: 'Error',text:"<?= gettext("Cannot set new In-Person Waitlist to less than 0");?>"});
            return;
        }

        $.ajax({
            url: 'ajax_events.php?updateInPersonSlots='+e,
            type: "POST",
            data: {
              inperson:inperson,
              inperson_waitlist:inperson_waitlist,
              inperson_limit_unlimited,
              inperson_waitlist_unlimited,
            },
            success : function(data) {
              let  jsonData = JSON.parse(data);

              Swal.fire({
                title: jsonData.title,
                text: jsonData.message
              }).then(function (result) {
                manageWaitList(e);
              })
            }
        });
    }
    function updateOnlineSlots(e){
        let online = $("#online").val();
        let online_waitlist = $("#online_waitlist").val();

        var container = $('#onlineUpdate');
        var online_limit_unlimited = container.find('input[name="online_limit_unlimited"]').is(':checked') ? 1 : 0;
        var online_waitlist_unlimited = container.find('input[name="online_waitlist_unlimited"]').is(':checked') ? 1 : 0;

        if (online < 1 && !online_limit_unlimited) {
            swal.fire({title: 'Error',text:"<?= gettext("Cannot set new Online limit to less than 1");?>"});
            return;
        }
        if (online_waitlist < 0 && !online_waitlist_unlimited) {
            swal.fire({title: 'Error',text:"<?= gettext("Cannot set new Online Waitlist limit to less than 0");?>"});
            return;
        }

        $.ajax({
            url: 'ajax_events.php?updateOnlineSlots='+e,
            type: "POST",
            data: {
              online:online,
              online_waitlist:online_waitlist,
              online_limit_unlimited,
              online_waitlist_unlimited,
            },
            success : function(data) {
              let  jsonData = JSON.parse(data);

              Swal.fire({
                title: jsonData.title,
                text: jsonData.message
              }).then(function (result) {
                manageWaitList(e);
              });
            }
        });
    }

    function changeRsvpStatus(e,s,j){
        $.ajax({
            url: 'ajax_events.php?changeRsvpStatus='+e,
            type: "POST",
            data: {status:s,joineeid:j},
            success : function(data) {
                if (data) {
                    $("#" + j).html(data);
                    swal.fire({title: 'Success', text: "<?= gettext("Updated successfully.");?>"})
                        .then((result) => {
                            if (s == 3) manageWaitList(e);
                            $('.modal-backdrop').remove();
                        } );
                } else {
                    swal.fire({title: 'Error', text: "<?= gettext("Unable to update the record at this time");?>"})
                        .then((result) => {
                            manageWaitList(e);
                            $('.modal-backdrop').remove();
                        } );
                }
            }
        });	
    }

    function updateBulkWaitlistCancel(e,o){
        if (o){
            Swal.fire({
            title: '<?= addslashes(gettext("Please confirm"));?>',
            text: '<?= sprintf(addslashes(gettext("This operation may take a long time to complete depending upon the number of records that need to be processed. Are you sure you want to update RSVP status as a %s?")),$_COMPANY->getAppCustomization()['group']['name-short']);?>',
            showCancelButton: true,
            confirmButtonText: `Update`,
        
            }).then((result) => {
                if(result.value){

                    $.ajax({
                        url: 'ajax_events.php?updateBulkWaitlistCancel='+e,
                        type: "POST",
                        data: {option:o},
                        success : function(data) {
                            let  jsonData = JSON.parse(data);
                            let totalRows = jsonData.length;
                            if (totalRows){
                                $("body").css("cursor", "progress");
                                $(document).unbind('click');
                                $("#totalBulkRecored").html(totalRows);
                                $(".progress_status").html("Updated 0/10 record(s)");
                                $('div#prgress_bar').width('0%');
                                $('.progress_bar').show();
                                jsonData.forEach(function(row,index) {
                                    // Process Data
                                    $.ajax({
                                        url: 'ajax_events.php?processupdateBulkWaitlistCancel=1',
                                        type: "POST",
                                        data: row,
                                        success : function(data) {
                                            var p = Math.round(((index+1) / totalRows) * 100);
                                            $(".progress_status").html("Updated "+(index+1)+"/"+totalRows+" record(s)");
                                            $('div#prgress_bar').width(p+'%');

                                            if ((index+1) == totalRows ){
                                                $("body").css("cursor", "default");
                                                $(".progress_status").html("<i class='fa fa-check-circle' aria-hidden='true'></i> <?= gettext("Completed");?>");
                                                setTimeout(function(){ 
                                                    swal.fire({title: 'Success',text:"<?= gettext("Updated successfully.");?>"}).then( function(result) {
                                                    $('#manageWaitList').modal('hide');
                                                    $('body').removeClass('modal-open');
                                                    manageWaitList(e);
                                                    $('.modal-backdrop').remove();
                                                    });
                                                }, 1500);
                                            }
                                        }
                                    });
                                });
                            } else {
                                swal.fire({title: 'Alert',text:"<?= gettext("No records to update.");?>"})
                            }
                        }
                    });	
                }
            })
        }
    }

    function updateRsvpEndTime(e){
        var formdata =	$('#rsvpEndDatetime').serialize();
        $.ajax({
            url: 'ajax_events.php?updateRsvpEndTime='+e,
            type: "POST",
            data: formdata,
            success : function(data) {
                $("#dueDate").html(data);
                $('#rsvpCloseTimeModal').modal('hide');
            }
        });
    }

    
    function initializeCloseLaterDatepicker(){
        $("#start_date").datepicker({
            prevText: "click for previous months",
            nextText: "click for next months",
            showOtherMonths: true,
            selectOtherMonths: false,
            dateFormat: 'yy-mm-dd',
            minDate: 0,
            maxDate: 30
        });

        var closeDate = '<?= $closeOnDate; ?>';
        if(closeDate.length > 0){
            $("#start_date").datepicker('setDate', closeDate);
        }else{
            $("#start_date").datepicker('setDate', 'today');
        }
        
    }

    jQuery(document).on("change", "#now_date_time, #scheduled_date_time", function () {
        let val = $(this).val();
        if (val == "scheduled") {
            $("#schedule_later_form").show().css('display', 'inline-block');
            initializeCloseLaterDatepicker();
        } else {
            $("#schedule_later_form").show().css('display', 'none');
        }
    });
    
    // Fixes for recover focus of first modal over second modal is opend
    $('.modal').on("hidden.bs.modal", function (e) {
        if($('.modal:visible').length)
        {
            $('.modal-backdrop').first().css('z-index', parseInt($('.modal:visible').last().css('z-index')) - 10);
            $('body').addClass('modal-open');
        }
    }).on("show.bs.modal", function (e) {
        if($('.modal:visible').length)
        {
            $('.modal-backdrop.in').first().css('z-index', parseInt($('.modal:visible').last().css('z-index')) + 10);
            $(this).css('z-index', parseInt($('.modal-backdrop.in').first().css('z-index')) + 10);
            
        }
    });

    
</script>

<script>
    // Search user for lead
    function searchUsersForRSVP(k,g,e){
        delayAjax(function(){
            if(k.length >= 3){
                $.ajax({
                    type: "GET",
                    url: "ajax_events.php?searchUserForRSVP=1",
                    data: {'keyword':k,'groupid':g,'eventid':e},
                    success: function(response){
                        $("#show_dropdown").html(response);
                        var myDropDown=$("#user_search");
                        var length = $('#user_search> option').length;
                        myDropDown.attr('size',length);
                    }
                });
            }
        },500)
    }
    function closeDropdown(){
        var myDropDown=$("#user_search");
        var length = $('#user_search> option').length;
        myDropDown.attr('size',0);
    }

    function addUsersToEventRSVPsList(e){
        if(!$("#user_search").val().length){
            swal.fire({title: 'Error!',text:'<?= addslashes(gettext("Select a user first"));?>'});
        } else if(!$("#rsvpOption").val()){
            swal.fire({title: 'Error!',text:'<?= addslashes(gettext("Select a RSVP option for the user"));?>'});
        } else {

            $("#updatingRSVP").html('<i class="fas fa-spinner fa-spin"></i> <?= addslashes(gettext("Updating RSVP. Please wait"));?>....');
            $("#eventRSVPform").hide();
            var formdata = $('#eventRSVPform')[0];
            var finaldata = new FormData(formdata);
            $.ajax({
                url: 'ajax_events.php?addUsersToEventRSVPsList=' + e,
                type: "POST",
                data: finaldata,
                processData: false,
                contentType: false,
                cache: false,
                success: function (data) {
                    try {
                        let jsonData = JSON.parse(data);
                        swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
                            if (jsonData.status == 1){
                                $('#manageWaitList').modal('hide');
                                $('body').removeClass('modal-open');
                                $('.modal-backdrop').remove();
                                manageWaitList(e)
                                $('.modal-backdrop').remove();
                            } else {
                                $("#updatingRSVP").html('');
                                $("#updatingRSVP").hide();
                                $("#eventRSVPform").show();
                            }
                        });
                    } catch(e) { swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false}); }

                }
            });
        }
    }

$('#manageWaitList').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus');
});

$('#general_schedule_publish_title').on('shown.bs.modal', function () {
    $('.close').trigger('focus');
});

$("#online-update-form").keypress(function (event) {
    if (event.keyCode === 13) {
        $(this).click();
    }
});
</script>