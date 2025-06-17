<div id="addUpdateScheduleModal" class="modal fade" tabindex="-1">
    <div aria-label="<?= $modalTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="modal-title" class="modal-title"><?= $modalTitle; ?></h4>
                <button type="button" id="btn_close" class="close" aria-label="Close Modal Box" data-dismiss="modal" onclick="manageAvailalbeSchedules()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="col-md-12" id="schedule_container"></div>
                <div class="clearfix"></div>
            </div>
            <div class="modal-footer text-center" style='text-align:center !important;display: block;'>
                <button type="button" class="btn btn-affinity prevent-multiple-submit" onclick="addUpdateSchedule('<?= $_COMPANY->encodeId($schedule_id); ?>')"><?= gettext("Submit"); ?></button>
                <button type="button" class="btn btn-affinity" aria-hidden="true" data-dismiss="modal" onclick="manageAvailalbeSchedules()"><?= gettext("Close"); ?></button>
            </div>
        </div>
    </div>
</div>
<script>
    var containerElement = $('#schedule_container');
    var schedule = <?= json_encode($scheduleArray); ?>;
    var scheduleDetails = <?= json_encode($scheduleDetails); ?>;
    var weekDays = <?= json_encode($weekDays); ?>;
    if (typeof ejs === 'undefined') {
        $.getScript('<?= TELESKOPE_.._STATIC ?>/vendor/js/ejs-3.1.10/ejs.min.js', renderSchedulerForm);
    } else {
        renderSchedulerForm();
    }

    function addInputField(day,addNtn,removeParent) {
        removeParent = (typeof removeParent !== 'undefined') ? removeParent : 0;
        let inputFieldsDiv = document.getElementById('scheduleStartEndTime'+day);
        let newInputGroup = document.createElement('div');
        newInputGroup.classList.add('parent-input-group');
        newInputGroup.innerHTML = `
        <div class="clearfix"></div>
        <div class="">
            <div class="col-5">
                <input class="form-control" type="time" id="startTime${day}" name="weeklySchedule[${day}][startTime][]" required onchange="initCheckOverLapTiming('${day}')">
            </div>
            <div class="col-5">
                <input class="form-control" type="time" id="endTime${day}" name="weeklySchedule[${day}][endTime][]" required onchange="initCheckOverLapTiming('${day}')">
            </div>
            <div class="col-2">
                <button type="button" class="btn-no-style" onclick="removeInputField(this,'${day}')">
                    <span class="fa fa-times-circle fa-lg"></span>
                    <span class="sr-only">Remove Row</span>
                </button>  
                <button type="button" class="btn-no-style" onclick="addInputField('${day}',this)">
                    <span class="fa fa-plus-circle fa-lg"></span>
                    <span class="sr-only">Add Row</span>
                </button>
            </div>
        </div>
    `;
        inputFieldsDiv.appendChild(newInputGroup);

        if (removeParent) {
            $(addNtn).closest('.parent-input-group').remove();
        } else {
            addNtn.remove();
        }
    }

    function removeInputField(element,day) {
        var bchildCount = $("#scheduleStartEndTime"+day).find(".parent-input-group").length;
        let parentInputGroup = element.closest('.parent-input-group');
        parentInputGroup.remove();

        var achildCount = $("#scheduleStartEndTime"+day).find(".parent-input-group").length;
        console.log('bchildCount=>',bchildCount, 'after=>',achildCount);
        if (achildCount == 0) {
            
            let inputFieldsDiv = document.getElementById('scheduleStartEndTime'+day);
            let newInputGroup = document.createElement('div');
            newInputGroup.classList.add('parent-input-group');
            newInputGroup.innerHTML = `
                    <div class="clearfix"></div>
                    <div class="">
                        <div class="col-11 text-right">
                        <button type="button" class="btn-no-style" onclick="addInputField('${day}',this,1)">
                            <span class="fa fa-plus-circle fa-lg"></span>
                            <span class="sr-only">Add Row</span>
                        </button>
                        </div>
                    </div>
                `;
            inputFieldsDiv.appendChild(newInputGroup);
        }
    }

    function addUpdateSchedule(i) {

        let formdata = $('#scheduleForm')[0];
        let finaldata = new FormData(formdata);
        finaldata.append("schedule_id", i);

        if (validateFormData(finaldata)){
            preventMultiClick(1);
            $.ajax({
                url: 'ajax_user_schedule.php?addUpdateSchedule=1',
                type: 'POST',
                processData: false,
                contentType: false,
                cache: false,
                data: finaldata,
                success: function(data) {
                    console.log(data);
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
                    } catch (e) {}
                }
            });
        }
        
    }

    function validateFormData(finaldata) {
        let errorCount = 0;
        $("#schedule_name_er").html('&nbsp;');
        $("#schedule_slot_er").html('&nbsp;');
        $("#start_date_in_user_tz_rt").html('&nbsp;');
        $("#end_date_in_user_tz_er").html('&nbsp;');
        $(".startEndTimeEr").html('');
        $("#user_meeting_link_er").html('&nbsp;');

        if (!finaldata.get('schedule_name')) {
            $("#schedule_name_er").html('<?= addslashes(gettext("Schedule name is required")); ?>!');
            errorCount++;
        }

        if (finaldata.get('link_generation_method')!=1){
            if (!finaldata.get('user_meeting_link')) {
                $("#user_meeting_link_er").html('<?= addslashes(gettext("Meeting link is required")); ?>!');
                errorCount++;
            } else {
                var urlPattern = /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/i;
                if (!finaldata.get('user_meeting_link').match(urlPattern)) {
                    $("#user_meeting_link_er").html('<?= addslashes(gettext("Meeting link is not valid")); ?>!');
                    errorCount++;
                }
            }
        }
        if (!finaldata.get('schedule_slot')) {
            $("#schedule_slot_er").html('<?= addslashes(gettext("Schedule slot is required")); ?>!');
            errorCount++;
        }
        if (!finaldata.get('start_date_in_user_tz')) {
            $("#start_date_in_user_tz_er").html('<?= addslashes(gettext("Start date is required")); ?>!');
            errorCount++;
        }
        if (!finaldata.get('end_date_in_user_tz')) {
            $("#end_date_in_user_tz_er").html('<?= addslashes(gettext("End date is required")); ?>!');
            errorCount++;
        }
        if (finaldata.get('schedule_scope') == '') {
            $("#schedule_scope_er").html('<?= gettext("Please select a scope"); ?>!');
            errorCount++;
        }

        if (finaldata.get('schedule_scope') && finaldata.get('schedule_scope') =='team_event' && finaldata.get('schedule_group_restriction_for_team_event_schedule')) {
            if (!finaldata.get('schedule_team_groups[]') || finaldata.get('schedule_team_groups[]').length==0) {
                $("#schedule_team_groups_er").html('<?= sprintf(gettext("Please select at least one %s"),$_COMPANY->getAppCustomization()['group']['name-short']); ?>!');
                errorCount++;
            }
        }

        if (finaldata.get('schedule_scope') && finaldata.get('schedule_scope') =='group_support' && finaldata.get('schedule_group_restriction_for_support_schedule')) {
            if (!finaldata.get('schedule_group_support_groups[]') || finaldata.get('schedule_group_support_groups[]').length==0) {
                $("#schedule_group_support_groups_er").html('<?= sprintf(gettext("Please select at least one %s"),$_COMPANY->getAppCustomization()['group']['name-short']); ?>!');
                errorCount++;
            }
        }

        for(let day in weekDays) {
            let timeErKey = '#startEndTime'+day+'_er';
            let startKey = 'weeklySchedule['+day+'][startTime][]';
            let startData = finaldata.getAll(startKey);
            if (startData.length) {
                let allStartEmpty = startData.every(function(element) {
                    return element === "";
                });

                let endKey = 'weeklySchedule['+day+'][endTime][]';
                let endData = finaldata.getAll(endKey);

                let allEndEmpty = endData.every(function(element) {
                    return element === "";
                });

                if (allStartEmpty || allEndEmpty) {
                    $(timeErKey).html('<?= addslashes(gettext("Start and End time are required")); ?>!');
                    errorCount++;
                }  else {
                    if (!initCheckOverLapTiming(day)) {
                        errorCount++;
                    }
                }
            }
        ;
        };
        
        if (errorCount) {
            return false;
        }
       
        return true;
    }

    function initCheckOverLapTiming(day) {
        let starTimes = $('input[name="weeklySchedule['+day+'][startTime][]"]');
        let endTimes = $('input[name="weeklySchedule['+day+'][endTime][]"]');
       
        let slot1Start = '';
        let slot1End = '';
        let slot2Start = '';
        let slot2End = '';

        starTimes.each(function( index ) {
            let sTime = $(this).val();
            let eTime = endTimes.eq(index).val();

            if (!sTime && !eTime) {
                return false;
            }

            if (!validateTime(sTime, eTime)) {
                $('#startEndTime'+day+'_er').html('<?= addslashes(gettext("Start time cannot be greater than end time")); ?>!');
                return false;
            }

            if (index === 0) {
                slot2Start = sTime;
                slot2End = eTime;
            } else {
                slot1Start = slot2Start;
                slot1End = slot2End;
                slot2Start = sTime;
                slot2End = eTime;
            }
           
            if (checkOverlap(slot1Start, slot1End, slot2Start, slot2End)) {
                $('#startEndTime'+day+'_er').html('<?= addslashes(gettext("Start and end time slots overlapped")); ?>!');
                return false;
            } else {
                $('#startEndTime'+day+'_er').html('');
            }   
        });
        return true;
    }

    function validateTime (strtTime, endTime) {
        let formatedDate = "<?= date("Y-m-d")?>";
        // Convert time strings to Date objects for easy comparison
        let st = new Date(formatedDate+" " + strtTime);
        let et = new Date(formatedDate+" " + endTime);

        if (st > et) {
            return false
        }
        return true;

    }
    function checkOverlap(slot1Start, slot1End, slot2Start, slot2End) {

        if (!slot1Start || !slot1End || !slot2Start || !slot2End) {
            return false;
        }
        let formatedDate = "<?= date("Y-m-d")?>";
        // Convert time strings to Date objects for easy comparison
        let startTime1 = new Date(formatedDate+" " + slot1Start);
        let endTime1 = new Date(formatedDate+" " + slot1End);
        let startTime2 = new Date(formatedDate+" " + slot2Start);
        let endTime2 = new Date(formatedDate+" " + slot2End);

        // Check if any of the conditions for overlap are met
        if ((startTime1 < endTime2 && startTime1 >= startTime2) || (endTime1 > startTime2 && endTime1 <= endTime2) ||
            (startTime2 < endTime1 && startTime2 >= startTime1) || (endTime2 > startTime1 && endTime2 <= endTime1)) {
            return true; // Slots overlap
        }
        return false; // Slots do not overlap
    }

    function toggleManualMeetingLinkInput()
    {
        if ($('#link_generation_method').is(':checked')) {
            $('#manual_link_container').hide();
        } else {
            $('#manual_link_container').show();
        }
    }

    function renderSchedulerForm() {

        var weeklyTimeSelection = `
                <% for(let day in weekDays) { %>
                <div class="form-group mb-0" id="scheduleStartEndTime<%= day %>">
                    <label class="col-12" for="startTime<%= day %>"><%= day %>:</label>
                <% if (schedule.schedule_id > 0){  %>
                    <% let filteredSchedules = scheduleDetails.filter(schedule => schedule.day_of_week === weekDays[day]); %>
                    <% if (filteredSchedules.length > 0){  %>
                    <% for (let scheduleTime of filteredSchedules) { %>
                        <div class="parent-input-group">
                            <div class="clearfix"></div>
                            <div class="pt-3">
                                <div class="col-5">
                                    <input class="form-control" type="time" step="900" name="weeklySchedule[<%= day %>][startTime][]" required onchange="initCheckOverLapTiming('<%= day %>')" value="<%= scheduleTime.daily_start_time_in_user_tz %>">
                                </div>
                                <div class="col-5">
                                    <input class="form-control" step="900" type="time"  name="weeklySchedule[<%= day %>][endTime][]" required onchange="initCheckOverLapTiming('<%= day %>')" value="<%= scheduleTime.daily_end_time_in_user_tz %>">
                                </div>
                                <div class="col-2">
                                
                                <button type="button" class="btn-no-style" onclick="removeInputField(this,'<%= day %>')">
                                    <span class="fa fa-times-circle fa-lg"></span>
                                    <span class="sr-only"><?= addslashes(gettext('Remove Row'));?></span>
                                </button>  
                                <button type="button" class="btn-no-style" onclick="addInputField('<%= day %>',this)">
                                    <span class="fa fa-plus-circle fa-lg"></span>
                                    <span class="sr-only"><?= addslashes(gettext('Add Row'));?></span>
                                </button>
                                </div>
                            </div>
                        </div>
                    <% } %>

                    <% } else { %>
                        <div class="parent-input-group">
                            <div class="clearfix"></div>
                            <div class="">
                                <div class="col-11 text-right">
                                <button type="button" class="btn-no-style" onclick="addInputField('<%= day %>',this,1)">
                                    <span class="fa fa-plus-circle fa-lg"></span>
                                    <span class="sr-only"><?= addslashes(gettext('Add Row'));?></span>
                                </button>

                                </div>
                            </div>
                        </div>
                    <% } %>
                <% } else { %>
                    <div class="parent-input-group">
                        <div class="col-5">
                            <input class="form-control" type="time" step="900" name="weeklySchedule[<%= day %>][startTime][]" required onchange="initCheckOverLapTiming('<%= day %>')">
                        </div>
                        <div class="col-5">
                            <input class="form-control" type="time" step="900" name="weeklySchedule[<%= day %>][endTime][]" required onchange="initCheckOverLapTiming('<%= day %>')">
                        </div>
                        <div class="col-2">
                                <button type="button" class="btn-no-style" onclick="removeInputField(this,'<%= day %>')">
                                    <span class="fa fa-times-circle fa-lg"></span>
                                    <span class="sr-only"><?= addslashes(gettext('Remove Input'));?></span>
                                </button>  
                                <button type="button" class="btn-no-style" onclick="addInputField('<%= day %>',this)">
                                    <span class="fa fa-plus-circle fa-lg"></span>
                                    <span class="sr-only"><?= addslashes(gettext('Add Input'));?></span>
                                </button>
                        </div>
                    </div>
                <% } %>
                </div>
                <small id="startEndTime<%= day %>_er" class="form-text text-danger col-12 startEndTimeEr"></small>
                <% } %>
            `;

        
        containerElement.html(ejs.render(`
        <form id="scheduleForm">
            <div class="col-12 form-group-emphasis p-2">
                <div class="form-group col-12 mb-0">
                    <label for="scheduleTitle"><?= addslashes(gettext('Schedule Title'));?>:</label>
                    <input class="form-control" type="text" id="schedule_name" name="schedule_name" value="<%= schedule.schedule_name %>" required>
                    <small id="schedule_name_er" class="form-text text-danger">&nbsp;</small>
                </div>
            <?php  if ($_COMPANY->getAppCustomization()['event']['meeting_links']['msteams']) { ?>
                <div class="form-check col-12 mb-0 ml-3">
                    <input class="form-check-input" type="checkbox" name="link_generation_method"  <%= schedule.link_generation_method && schedule.link_generation_method == 'automatic' ? 'checked' : '' %> value="1" id="link_generation_method" onchange="toggleManualMeetingLinkInput()">
                    <label class="form-check-label" for="link_generation_method">
                        <?= gettext('Use Pre-Generated Meeting Links')?>
                    </label>
                    <br>
                    <small><?=gettext('If the "Pre-Generated Meeting Links" checkbox is checked, the system can create a pool of pre-generated meeting links that can be assigned and recycled for various meetings in your schedule. This means you will always have a meeting link ready to go, without having to create one each time. Meeting Links can be generated using the option in Action menu')?></small>
                </div>
            <?php } ?>
                <div class="form-group col-12 mt-3 mb-0" id="manual_link_container" style="display:<%= schedule.link_generation_method && schedule.link_generation_method == 'automatic' ? 'none' : 'block' %>">
                    <label for="scheduleTitle"><?= addslashes(gettext('Meeting Link'));?>:</label>
                    <input class="form-control" type="text" id="user_meeting_link" name="user_meeting_link" value="<%= schedule.user_meeting_link %>" required>
                    <small id="user_meeting_link_er" class="form-text text-danger">&nbsp;</small>
                </div>
            </div>
             <div class="col-12 form-group-emphasis p-2">
                <div class="form-group col-6 mb-0">
                    <?php
                        $schedule_scope_tooltip = gettext('Select the type of schedule you want to manage: (a) Team Events – For scheduling regular meetings between mentors and mentees, (b) Support Module – For setting up availability for support-related meetings.');
                        // Note scope once set cannot be changed.
                    ?>
                    <label for="schedule_scope"><?= addslashes(gettext('Schedule Scope'));?>:</label> <i aria-label="<?=$schedule_scope_tooltip?>" tabindex="0" class="fa fa-question-circle" data-toggle="tooltip" title="<?=$schedule_scope_tooltip?>"></i>

                    <select class="form-control" name="schedule_scope" id="schedule_scope" onchange="changeScheduleScope(this.value)">

                        <?php if (!$schedule) { ?>
                        <option value="" <?=  $schedule ? 'disabled' : ''; ?>>Select an option</option>
                        <?php } ?>

                        <?php if (
                            (!$schedule && $_COMPANY->getAppCustomization()['teams']['enabled'])
                            || ($schedule && $schedule->val('schedule_scope') == 'team_event')
                        ) { ?>
                        <option value="team_event" <?=  $schedule ? ($schedule->val('schedule_scope')=='team_event' ? 'selected' : 'disabled') : '';?>>
                            <?= gettext('Team Event');?>
                        </option>
                        <?php } ?>

                        <?php if (
                            (!$schedule && $_COMPANY->getAppCustomization()['booking']['enabled'])
                            || ($schedule && $schedule->val('schedule_scope') == 'group_support')
                        ) { ?>
                        <option value="group_support"
                            <?=  $schedule ? ($schedule->val('schedule_scope')=='group_support' ? 'selected' : 'disabled') : '';?>>
                            <?= gettext('Support Module');?>
                        </option>
                        <?php } ?>

                    </select>

                    <small id="schedule_scope_er" class="form-text text-danger">&nbsp;</small>
                </div>

                <div class="form-group col-12 mb-0" id='booking_group_scope_selector' style='display:<%= schedule.schedule_scope == 'group_support'  ? 'block' : 'none' %>;'>

                    <div class="custom-control custom-switch">
                      <input type="checkbox" class="custom-control-input" id="scheduleGroupRestrictionForSupportSchedule" name="schedule_group_restriction_for_support_schedule" <?= !empty($scheduleArray['schedule_groups']) ? 'checked' : '' ?>>
                      <label class="custom-control-label" for="scheduleGroupRestrictionForSupportSchedule">Restrict Schedule to Groups</label>
                    </div>

                    <div id="dropdown_scheduleGroupRestrictionForSupportSchedule" <?= empty($scheduleArray['schedule_groups']) ? 'style="display:none;"' : '' ?>>
                    <label for="schedule_group_support_groups"><?= gettext('Select Group');?>:</label>
                    <select class="form-control" name="schedule_group_support_groups[]" id="schedule_group_support_groups" multiple>
                    <?php foreach($groupsForSupportSchedule as $g){
                        $sel = '';
                        if (!empty($scheduleArray) && in_array($g['groupid'],$scheduleArray['schedule_groups'])){
                            $sel = 'selected';
                        }
                    ?>
                        <option value="<?= $_COMPANY->encodeId($g['groupid']); ?>" <?= $sel; ?> ><?= $g['groupname'];?></option>
                    <?php } ?>
                    </select>
                    <small id="schedule_group_support_groups_er" class="form-text text-danger">&nbsp;</small>
                    </div>
                </div>

                 <div class="form-group col-12 mb-0" id='team_group_scope_selector' style='display:<%= schedule.schedule_scope == 'team_event' ? 'block' : 'none' %>;'>

                    <div class="custom-control custom-switch">
                      <input type="checkbox" class="custom-control-input" id="scheduleGroupRestrictionForTeamEventSchedule" name="schedule_group_restriction_for_team_event_schedule" <?= !empty($scheduleArray['schedule_groups']) ? 'checked' : '' ?>>
                      <label class="custom-control-label" for="scheduleGroupRestrictionForTeamEventSchedule">Restrict Schedule to Groups</label>
                    </div>

                    <div id="dropdown_scheduleGroupRestrictionForTeamEventSchedule" <?= empty($scheduleArray['schedule_groups']) ? 'style="display:none;"' : '' ?>>
                    <label for="schedule_team_groups"><?= gettext('Select Group');?>:</label>
                    <select class="form-control" name="schedule_team_groups[]" id="schedule_team_groups" multiple>
                    <?php foreach($groupsForTeamSchedule as $g){
                        $sel = '';
                        if (!empty($scheduleArray) && in_array($g['groupid'],$scheduleArray['schedule_groups'])){
                            $sel = 'selected';
                        }
                    ?>
                        <option value="<?= $_COMPANY->encodeId($g['groupid']); ?>" <?= $sel; ?> ><?= $g['groupname'];?></option>
                    <?php } ?>
                    </select>
                    <small id="schedule_team_groups_er" class="form-text text-danger">&nbsp;</small>
                    </div>
                </div>
            </div>
            <div class="col-12 form-group-emphasis p-2">
                <div class="form-group col-6 mb-0">
                    <label for="schedule_slot"><?= addslashes(gettext('Slot Duration (in minutes)'));?>:</label>
                    <select class="form-control" name="schedule_slot" id="schedule_slot">
                        <option value="10" <%= schedule.schedule_slot == 10 ? 'selected' : '' %>>10 Minutes</option>
                        <option value="15" <%= schedule.schedule_slot == 15 ? 'selected' : '' %>>15 Minutes</option>
                        <option value="20" <%= schedule.schedule_slot == 20 ? 'selected' : '' %>>20 Minutes</option>
                        <option value="30" <%= schedule.schedule_slot == 30 ? 'selected' : '' %>>30 Minutes</option>
                        <option value="45" <%= schedule.schedule_slot == 45 ? 'selected' : '' %>>45 Minutes</option>
                        <option value="60" <%= schedule.schedule_slot == 60 ? 'selected' : '' %>>60 Minutes</option>
                    </select>
                    <small id="schedule_slot_er" class="form-text text-danger">&nbsp;</small>

                </div>
                <div class="form-group col-6 mb-0">
                    <label for="selected_tz"><?= addslashes(gettext('Change Timezone'));?></label>
                    <select class="form-control teleskope-select2-dropdown" id="selected_tz" name="user_tz" onchange="selectedTimeZone();" style="width: 100%;">
                        <?= getTimeZonesAsHtmlSelectOptions($timezone); ?>
                    </select>
                    <small id="selected_tz_er" class="form-text text-danger">&nbsp;</small>
                </div>
                <div class="clearfix"></div>
                <div class="form-group col-6 mb-0">
                    <label for="start_date_in_user_tz"><?= addslashes(gettext('Start Date'));?> <?= gettext('[mm/dd/yyyy]');?> </label>
                    <input class="form-control" type="date" id="start_date_in_user_tz" name="start_date_in_user_tz" value="<%= schedule.start_date_in_user_tz %>" required>
                    <small id="start_date_in_user_tz_er" class="form-text text-danger">&nbsp;</small>
                </div>
                <div class="form-group col-6 mb-0">
                    <label for="end_date_in_user_tz"><?= addslashes(gettext('End Date'));?> <?= gettext('[mm/dd/yyyy]');?></label>
                    <input class="form-control" type="date" id="end_date_in_user_tz" name="end_date_in_user_tz" value="<%= schedule.end_date_in_user_tz %>" required>
                    <small id="end_date_in_user_tz_er" class="form-text text-danger">&nbsp;</small>
                </div>
                <div class="form-group col-6 mb-0">
                    <label for="start_time_buffer"><?= addslashes(gettext('Minimum Start Time Buffer'));?>:</label> <i aria-label="<?=gettext('This is the minimum lead time you require for someone to schedule an appointment with you. Increase this to give yourself more time to prepare for appointments or decrease it to allow more last minute bookings')?>" tabindex="0" class="fa fa-question-circle" data-toggle="tooltip" title="<?=gettext('This is the minimum lead time you require for someone to schedule an appointment with you. Increase this to give yourself more time to prepare for appointments or decrease it to allow more last minute bookings')?>"></i>
                    <select class="form-control" name="start_time_buffer" id="start_time_buffer">
                    <?php for($i=0;$i<25;$i++){ 
                        $hr = gettext('Hour');
                        if($i >1){
                            $hr = gettext('Hours');
                        }
                    ?>
                        <option value="<?= $i; ?>" <%= schedule.start_time_buffer == <?= $i; ?> ? 'selected' : ''; %>><?= $i; ?> <?= $hr; ?></option>
                    <?php } ?>
                    </select>
                    <small id="start_time_buffer_er" class="form-text text-danger">&nbsp;</small>

                </div>
                
            </div>
            <div class="col-12 form-group-emphasis p-2">
                ${weeklyTimeSelection}
            </div>
        </form>`));
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();

            $('#schedule_group_support_groups').multiselect({
                nonSelectedText: "<?= gettext("Please select a group"); ?>",
                numberDisplayed: 3,
                nSelectedText: "<?= gettext('groups selected')?>",
                disableIfEmpty: true,
                allSelectedText: "<?= gettext('All groups selected')?>",
                enableFiltering: true,
                maxHeight: 400
            });

            $('#schedule_team_groups').multiselect({
                nonSelectedText: "<?= gettext("Please select a group"); ?>",
                numberDisplayed: 3,
                nSelectedText: "<?= gettext('groups selected')?>",
                disableIfEmpty: true,
                allSelectedText: "<?= gettext('All groups selected')?>",
                enableFiltering: true,
                maxHeight: 400
            });

             $('#scheduleGroupRestrictionForSupportSchedule').change(function () {
                if ($(this).is(':checked')) {
                    $('#dropdown_scheduleGroupRestrictionForSupportSchedule').slideDown();
                } else {
                    $('#dropdown_scheduleGroupRestrictionForSupportSchedule').slideUp();
                }
            });
            $('#scheduleGroupRestrictionForTeamEventSchedule').change(function () {
                if ($(this).is(':checked')) {
                    $('#dropdown_scheduleGroupRestrictionForTeamEventSchedule').slideDown();
                } else {
                    $('#dropdown_scheduleGroupRestrictionForTeamEventSchedule').slideUp();
                }
            });
        })
    }

    function changeScheduleScope(v) {
        if (v == 'team_event') {
            $("#booking_group_scope_selector").hide();
            $("#team_group_scope_selector").show();
        } else {
            $("#booking_group_scope_selector").show();
            $("#team_group_scope_selector").hide();
        }
    }

$('#addUpdateScheduleModal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus');
});
</script>
<script> $(".teleskope-select2-dropdown").select2({width: 'resolve'});</script>
