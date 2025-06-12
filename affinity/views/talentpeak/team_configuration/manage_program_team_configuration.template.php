<div class="row mb-4">

    <div class="col-md-12 form-group-emphasis">
        <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= sprintf(gettext("%s Type"), $_COMPANY->getAppCustomization()['group']['name-short']);?></h3>
        <div role="group" aria-label="<?= sprintf(gettext("%s Type"), $_COMPANY->getAppCustomization()['group']['name-short']);?>" class="pl-2">
        <div class="form-check">
            <input class="form-check-input" type="radio" id="team_program_type_admin" name="team_program_type" value="<?= Team::TEAM_PROGRAM_TYPE['ADMIN_LED'] ?>" <?= $program_type_value == Team::TEAM_PROGRAM_TYPE['ADMIN_LED'] ? 'checked' : ''; ?> <?php if($program_type_value) { ?> disabled style="cursor: not-allowed;" <?php } ?> >
            <label class="form-check-label" for="team_program_type_admin">
                <?= gettext("Admin Led Pairing");?>
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" id="team_program_type_peer" name="team_program_type" value="<?=Team::TEAM_PROGRAM_TYPE['PEER_2_PEER']?>" <?= $program_type_value == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER'] ? 'checked' : ''; ?>  <?php if($program_type_value) { ?> disabled style="cursor: not-allowed;" <?php } ?>>
            <label class="form-check-label" for="team_program_type_peer">
                <?= gettext("Peer to Peer Pairing");?>
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" id="team_program_type_networking" name="team_program_type" value="<?=Team::TEAM_PROGRAM_TYPE['NETWORKING']?>" <?= $program_type_value == Team::TEAM_PROGRAM_TYPE['NETWORKING'] ? 'checked' : ''; ?>  <?php if($program_type_value) { ?> disabled style="cursor: not-allowed;" <?php } ?>>
            <label class="form-check-label" for="team_program_type_networking">
                <?= gettext("Networking");?>
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" id="team_program_type_circles" name="team_program_type" value="<?=Team::TEAM_PROGRAM_TYPE['CIRCLES']?>" <?= $program_type_value == Team::TEAM_PROGRAM_TYPE['CIRCLES'] ? 'checked' : ''; ?>  <?php if($program_type_value) { ?> disabled style="cursor: not-allowed;" <?php } ?>>
            <label class="form-check-label" for="team_program_type_circles">
                <?= gettext("Circles");?>
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" id="team_program_type_individual" name="team_program_type_individual" value="<?=Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']?>" <?= $program_type_value == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV'] ? 'checked' : ''; ?>  <?php if($program_type_value) { ?> disabled style="cursor: not-allowed;" <?php } ?> >
            <label class="form-check-label" for="team_program_type_individual">
                <?= gettext("Individual Development");?>
            </label>
        </div>
        <small>
            <?= sprintf(gettext("Note: Pairing mechanism cannot be changed once the %s are activated"), Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?>
        </small>
        </div>
    </div>

    <div class="col-12 form-group-emphasis">
        <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext("Tab Setting");?></h3>
        <div role="group" aria-label="<?= gettext("Tab Setting");?>" class="pl-2">
            <div class="form-check">
                <input class="form-check-input hide_checkbox_<?= TEAM::PROGRAM_TEAM_TAB['ACTION_ITEM']; ?>" type="checkbox" id="hide_tab_action_item" value="<?= TEAM::PROGRAM_TEAM_TAB['ACTION_ITEM']; ?>" name="programTab[]" onclick="showHideProgramTabSetting('<?= $_COMPANY->encodeId($groupid); ?>','<?= TEAM::PROGRAM_TEAM_TAB['ACTION_ITEM']; ?>')" <?= in_array(TEAM::PROGRAM_TEAM_TAB['ACTION_ITEM'], $hiddenTabs) ? 'checked' : ''; ?>  <?php if($program_type_value == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']) { ?> disabled style="cursor: not-allowed;" <?php } ?> >
                <label class="form-check-label" for="hide_tab_action_item">
                    <?= sprintf(gettext('Hide action item tab from %1$s configuration page and user %1$s detail page'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input hide_checkbox_<?= TEAM::PROGRAM_TEAM_TAB['TOUCH_POINT']; ?>" type="checkbox" id="hide_tab_touch_point" value="<?= TEAM::PROGRAM_TEAM_TAB['TOUCH_POINT']; ?>" name="programTab[]" onclick="showHideProgramTabSetting('<?= $_COMPANY->encodeId($groupid); ?>','<?= TEAM::PROGRAM_TEAM_TAB['TOUCH_POINT']; ?>')" <?= in_array(TEAM::PROGRAM_TEAM_TAB['TOUCH_POINT'], $hiddenTabs) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="hide_tab_touch_point">
                    <?= sprintf(gettext('Hide touch point tab from %1$s configuration page and user %1$s detail page'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input hide_checkbox_<?= TEAM::PROGRAM_TEAM_TAB['FEEDBACK']; ?>" type="checkbox" id="hide_tab_feedback" value="<?= TEAM::PROGRAM_TEAM_TAB['FEEDBACK']; ?>" name="programTab[]" onclick="showHideProgramTabSetting('<?= $_COMPANY->encodeId($groupid); ?>','<?= TEAM::PROGRAM_TEAM_TAB['FEEDBACK']; ?>')" <?= in_array(TEAM::PROGRAM_TEAM_TAB['FEEDBACK'], $hiddenTabs) ? 'checked' : ''; ?> >
                <label class="form-check-label" for="hide_tab_feedback">
                    <?= sprintf(gettext('Hide feedback tab from user %s detail page'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>
                </label>
            </div>

            <div class="form-check">
                <input class="form-check-input hide_checkbox_<?= TEAM::PROGRAM_TEAM_TAB['TEAM_MESSAGE']; ?>" type="checkbox" id="hide_tab_message" value="<?= TEAM::PROGRAM_TEAM_TAB['TEAM_MESSAGE']; ?>" name="programTab[]" onclick="showHideProgramTabSetting('<?= $_COMPANY->encodeId($groupid); ?>','<?= TEAM::PROGRAM_TEAM_TAB['TEAM_MESSAGE']; ?>')" <?= in_array(TEAM::PROGRAM_TEAM_TAB['TEAM_MESSAGE'], $hiddenTabs) ? 'checked' : ''; ?> <?php if($program_type_value == 3) { ?> checked disabled style="cursor: not-allowed;" <?php } ?> >
                <label class="form-check-label" for="hide_tab_message">
                    <?= sprintf(gettext('Hide message tab from user %s detail page'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>
                </label>
            </div>
        </div>
    </div>

    <div class="col-12 form-group-emphasis">
        <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= sprintf(gettext('Progress Bar Setting'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?></h3>
        <div role="group" aria-label="<?= sprintf(gettext('Progress Bar Setting'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>" class="pl-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="show_actionitem_progress_bar" value="1" name="show_actionitem_progress_bar" onclick="updateProgressBarSetting('<?= $_COMPANY->encodeId($groupid); ?>','show_actionitem_progress_bar',this)" <?= $progressBarSetting['show_actionitem_progress_bar'] ? 'checked' : ''; ?>  >
                <label class="form-check-label" for="show_actionitem_progress_bar">
                    <?= sprintf(gettext('Show Action Items Progress Bar on %s Detail'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="show_touchpoint_progress_bar" value="1" name="show_touchpoint_progress_bar" onclick="updateProgressBarSetting('<?= $_COMPANY->encodeId($groupid); ?>','show_touchpoint_progress_bar',this)" <?= $progressBarSetting['show_touchpoint_progress_bar'] ? 'checked' : ''; ?>  >
                <label class="form-check-label" for="show_touchpoint_progress_bar">
                    <?= sprintf(gettext('Show Touch Point Progress Bar on %s Detail'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>
                </label>
            </div>
        </div>
    </div>

    <?php if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['NETWORKING']){ 
            $porgramStartSetting = $group->getNetworkingProgramStartSetting();
    ?>
    <div class="col-12 form-group-emphasis">
        <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext('Auto Recycle Configuration');?></h3>
        <div class="pl-2">
            <div class="form-group ">
                <div class="col-4"> 
                    <p><?= gettext("Start Date"); ?>&nbsp;<i class="fa fa-question-circle" data-toggle="tooltip" title="<?=gettext('Auto Recycle Configuration allows teams to be automatically closed every [X] days, starting on the start date. To disable the Auto Recycle feature, set the start date to empty.')?>"></i></p>
                    <input aria-label="program_start_date" class="form-control form-control-sm" type="text" id="program_start_date" value="<?= $porgramStartSetting['program_start_date']; ?>" placeholder="YYYY-MM-DD">
                </div>
                <div class="col-4"> 
                    <p><?= sprintf(gettext("Recycle %s every [X] days"),Team::GetTeamCustomMetaName($group->getTeamProgramType())); ?></p>
                    <input aria-label="team_match_cycle_days" class="form-control form-control-sm" type="number" id="team_match_cycle_days"  value="<?= $porgramStartSetting['team_match_cycle_days']; ?>">
                </div>
                <div class="col-3 pt-4">
                    <button type="button" onclick="saveNetworkingProgramStartSetting('<?= $_COMPANY->encodeId($groupid); ?>')" class="btn btn-sm btn-primary text-center"><?= gettext('Update');?></button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $( "#program_start_date" ).datepicker({
            prevText:"click for previous months",
            nextText:"click for next months",
            showOtherMonths:true,
            selectOtherMonths: false,
            dateFormat: 'yy-mm-dd'
        });
    </script>
    <?php } ?>

    <?php if ($group->getTeamProgramType()!= Team::TEAM_PROGRAM_TYPE['NETWORKING'] && $group->getTeamProgramType()!= Team::TEAM_PROGRAM_TYPE['CIRCLES'] &&  $group->getTeamProgramType()!= Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
    <div class="col-12 form-group-emphasis">
        <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= sprintf(gettext('Registration Setting'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?></h3>
        <div class="pl-2">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="join_role_request_setting" value="1" name="programTab[]" onclick="canJoinMultipleTeamRole('<?= $_COMPANY->encodeId($groupid); ?>',this)" <?= $group->getTeamJoinRequestSetting() ? 'checked' : ''; ?>  >
            <label class="form-check-label" for="join_role_request_setting">
                <?= sprintf(gettext('Allow users to Register for multiple %s roles'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>
            </label>
        </div>
        </div>
    </div>
    <?php } ?>
    
    <div class="col-12 form-group-emphasis">
        <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext('Inactivity Notifications');?></h3>
        <div class="pl-2">
        <div class="form-group form-inline">
        <p>
            <?= sprintf(gettext('Automatically send inactivity notifications to %1$s members after %2$s days of inactivity and send notifications every %3$s days thereafter.'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),'<input aria-label="'.sprintf(gettext('Notifications to Team members every %1$s day'),$notificationSetting['notification_days_after']).'" class="form-control form-control-sm" type="text" id="notification_days_after" style="width: 35px;" value="'.$notificationSetting['notification_days_after'].'">','<input aria-label="'.sprintf(gettext('Notifications to Team members every %1$s day'),$notificationSetting['notification_frequency']).'" type="text" class="form-control form-control-sm" id="notification_frequency" style="width: 35px;" value="'. $notificationSetting['notification_frequency'].'">');?>
        <button aria-label="<?= gettext('Update Notifications');?>" type="button" onclick="saveTeamInactivityNotificationsSetting('<?= $_COMPANY->encodeId($groupid); ?>')" class="btn btn-sm btn-primary"><?= gettext('Update');?></button>
        </p>
        </div>
        </div>
    </div>
    <?php if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
        <div class="col-12 form-group-emphasis">
            <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= sprintf(gettext('%s Disclaimer'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?></h3>
            <br>
            <small class="pl-2"><?= sprintf(gettext('The following disclaimer message will be shown as part of every %1$s description'), Team::GetTeamCustomMetaName($group->getTeamProgramType())) ?></small>
            <div class="pl-2 mt-2">
            <div class="form-group form-inline">
            <p>
            <?php if ($program_disclaimer = $group->getProgramDisclaimer()){ ?>
                <div class="col-12 pl-3 pr-3 mb-3 dark-background" id="post-inner" style="font-size: 14px;">
                    <?= $program_disclaimer ?>
                </div>

                <button aria-label="<?= gettext('Update'); ?>" class="btn btn-sm btn-affinity px-1" onclick="showAddUpdateProgramDisclaimerModal('<?= $_COMPANY->encodeId($groupid); ?>')" title="<?= gettext('Update'); ?>">
                    <?= gettext('Update'); ?>
                </button>

            <?php } else { ?>
                <?= gettext("No disclaimer found. Please create a new one now.")?>
                <button aria-label="Create new disclaimer" class="btn-no-style px-1" onclick="showAddUpdateProgramDisclaimerModal('<?= $_COMPANY->encodeId($groupid); ?>')" title="Create new disclaimer">
                    <i class="fa fa-plus-circle" aria-hidden="true"></i>
                </button>
            <?php } ?>
            </p>
            </div>
            </div>
        </div>
        <script>
            function showAddUpdateProgramDisclaimerModal(g) {
                $.ajax({
                    url: 'ajax_talentpeak.php?showAddUpdateProgramDisclaimerModal=1',
                    type: "GET",
                    data: {groupid:g},
                    success: function(data) {
                        try {
                            let jsonData = JSON.parse(data);
                        } catch(e) {
                            $('#loadAnyModal').html(data);
                            $('#loadProgramDisclaimerModal').modal({
                                backdrop: 'static',
                                keyboard: false
                            });
                        }
                    }
                });
            }
        </script>
    <?php } ?>

    <?php
    if ($group->getTeamProgramType()!= Team::TEAM_PROGRAM_TYPE['NETWORKING'] && $group->getTeamProgramType()!= Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){
        $teamWorkflowSetting = $group->getTeamWorkflowSetting();
    ?>
    <div class="col-12 form-group-emphasis">
        <h3 class="form-group-emphasis-heading form-group-emphasis-dark"><?= sprintf(gettext('%s Workflow Setting'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?></h3>
        <div class="pl-2">
            <?php if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="hide_member_in_discover_tab" value="1" name="hide_member_in_discover_tab" onclick="updateTeamWorkflowSetting('<?= $_COMPANY->encodeId($groupid); ?>',this)" <?= $teamWorkflowSetting['hide_member_in_discover_tab'] ? 'checked' : ''; ?>  >
                <label class="form-check-label" for="hide_member_in_discover_tab">
                    <?= sprintf(gettext('Hide %1$s members from %1$s Tile view in Discover Tab'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>
                </label>
            </div>
            <?php } ?>
            <?php if($group->getTeamProgramType()!= Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="any_mentor_can_complete_team" value="1" name="any_mentor_can_complete_team" onclick="updateTeamWorkflowSetting('<?= $_COMPANY->encodeId($groupid); ?>',this)" <?= $teamWorkflowSetting['any_mentor_can_complete_team'] ? 'checked' : ''; ?>  >
                <label class="form-check-label" for="any_mentor_can_complete_team">
                    <?= sprintf(gettext('Allow any member with Mentor role type to complete the %s'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>
                </label>
            </div>
            <?php } ?>

            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="any_mentee_can_complete_team" value="1" name="any_mentee_can_complete_team" onclick="updateTeamWorkflowSetting('<?= $_COMPANY->encodeId($groupid); ?>',this)"<?= $teamWorkflowSetting['any_mentee_can_complete_team'] ? 'checked' : ''; ?>  >
                <label class="form-check-label" for="any_mentee_can_complete_team">
                    <?= sprintf(gettext(' Allow any member with Mentee role type to complete the %s'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>
                </label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="auto_complete_team_on_action_touchpoints_complete" value="1" name="auto_complete_team_on_action_touchpoints_complete" onclick="updateTeamWorkflowSetting('<?= $_COMPANY->encodeId($groupid); ?>',this)" <?= $teamWorkflowSetting['auto_complete_team_on_action_touchpoints_complete'] ? 'checked' : ''; ?>  >
                <label class="form-check-label" for="auto_complete_team_on_action_touchpoints_complete">
                    <?= sprintf(gettext('Automatically complete the %s after all the Action Items and Touch Points are marked as Done.'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>
                </label>
            </div>

            <hr>

            <div class="form-group form-inline">
                <p class='pl-3'>
                    <?= sprintf(gettext('Automatically complete %1$s %2$s days from the %1$s start date. Enter 0 to disable this feature.'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),'&nbsp;<input type="number"  aria-label="'.gettext('Automatically complete after days').'" class="form-control form-control-sm" type="text" id="automatically_close_team_after_n_days"  name="automatically_close_team_after_n_days" style="width: 60px;"  min="0" value="'.($teamWorkflowSetting['automatically_close_team_after_n_days'] ?? '0').'">');?>
                    <button aria-label="<?= gettext('Update Notifications');?>" type="button" onclick="saveTeamAutoCompleteSetting('<?= $_COMPANY->encodeId($groupid); ?>')" class="btn btn-sm btn-primary"><?= gettext('Update');?></button>
                </p>
            </div>

            <?php if (
                ($_ZONE->val('app_type') === 'peoplehero')
                && in_array($program_type_value, [Team::TEAM_PROGRAM_TYPE['PEER_2_PEER'], Team::TEAM_PROGRAM_TYPE['ADMIN_LED']])
            ) { ?>
            <div class="form-group form-inline">
              <p class="pl-3">
                <?= sprintf(
                  gettext('Automatically complete %1$s %2$s days after the Mentee\'s start date. Enter 0 to disable this feature.'),
                  Team::GetTeamCustomMetaName($group->getTeamProgramType()),
                  '&nbsp;<input aria-label="'
                  . gettext("Automatically complete Team [[N]] days after the Mentee's start date")
                  . '" class="form-control form-control-sm" type="number" id="auto_complete_team_ndays_after_mentee_start_date"  name="auto_complete_team_ndays_after_mentee_start_date" style="width: 60px;" min="0" value="'
                  . ($teamWorkflowSetting['auto_complete_team_ndays_after_mentee_start_date'] ?? '0')
                  . '">'
                ) ?>
                <button aria-label="<?= gettext('Update') ?>" type="button" onclick="saveTeamAutoCompleteOnMenteeStartDateSetting('<?= $_COMPANY->encodeId($groupid); ?>')" class="btn btn-sm btn-primary"><?= gettext('Update')?></button>
              </p>
            </div>
            <?php } ?>

        </div>
    </div>
    <?php } ?>
   

    <?php if (0) { // commented out on 11/26 as we do not know if we will use this feature yet ?>
    <div class="col-md-12 p-3">
        <h3><?= sprintf(gettext('Other %s Settings'),$_COMPANY->getAppCustomization()['group']['name-short']);?></h3>
        <br>
        <?= sprintf(gettext('I want to call Teams in this %s'),$_COMPANY->getAppCustomization()['group']['name-short']);?>:
        &emsp;
        <span id="team_meta_name"><?= htmlspecialchars($team_meta_name) ?></span>
        &emsp;
        <button onclick="openTeamMetaNameUpateModal('<?= $_COMPANY->encodeId($groupid); ?>')" class="btn-no-style"><i class="fa fa-edit" title="Edit"></i></button>
    </div>
    <?php } ?>
</div>
<div id="loadModal"></div>
<script>

jQuery(function() {
	jQuery( "#start_date" ).datepicker({
		prevText:"click for previous months",
		nextText:"click for next months",
		showOtherMonths:true,
		selectOtherMonths: false,
		dateFormat: 'yy-mm-dd'
	});
	jQuery( "#end_date" ).datepicker({
		prevText:"click for previous months",
		nextText:"click for next months",
		showOtherMonths:true,
		selectOtherMonths: true,
		dateFormat: 'yy-mm-dd'
	});
});

   
<?php if (!$group->getTeamProgramType()){ ?>
    (async () => {
        const { value: program_type } = await Swal.fire({
            title: 'Select program type',
            input: 'select',
            allowOutsideClick:false,
            inputOptions: {
                "Group Development": {
                    "1": "Admin Led Matching",
                    "2": "Peer to Peer Matching",
                    "4": "Networking",
                    "5":"Circles"
                },
                "Individual Development": {
                    "3": "Individual Development"
                }
            },
            inputPlaceholder: 'Please select a program type',
            confirmButtonText: 'Submit',
            showCancelButton: false,
            inputValidator: (value) => {
                return new Promise((resolve) => {
                    if (value) {
                        resolve()
                    } else {
                        resolve('Please select a program type!')
                    }
                })
            }
        })
        

        if (program_type) {
            $.ajax({
                url: 'ajax_talentpeak.php?updateProgramTypeSetting=1',
                type: "POST",
                data: {'groupid':'<?= $_COMPANY->encodeId($groupid); ?>','team_program_type':program_type},
                success: function(data) {
                    swal.fire({title: 'Success',text:"Program type updated successfully"}).then(function(result) {
                        manageTeamsConfiguration('<?= $_COMPANY->encodeId($groupid); ?>');
                    });
                }
            });
        }
    })()

<?php } ?>

function canJoinMultipleTeamRole(g,e){
    var multiple = 0;
    if($(e).is(":checked")) {
        multiple = 1;
    }

    $.ajax({
        url: 'ajax_talentpeak.php?canJoinMultipleTeamRole=1',
        type: "POST",
        data: {'groupid':g,'canJoinMultipleRole':multiple},
        success: function(data) {
            swal.fire({title: 'Success',text:"Program setting updated successfully"});
        }
    });
}

$("input:checkbox").keypress(function (event) {
if (event.keyCode === 13) {
	$(this).click();
}
});

$(function () {
    $('[data-toggle="tooltip"]').tooltip();
})

function updateProgressBarSetting(g,k,e){
    var status = 0;
    if($(e).is(":checked")) {
        status = 1;
    }
    $.ajax({
        url: 'ajax_talentpeak.php?updateProgressBarSetting=1',
        type: "POST",
        data: {'groupid':g,'contextKey':k,'status':status},
        success: function(data) {
            swal.fire({title: '<?= gettext("Success");?>',text:"<?= gettext('Setting updated successfully')?>"});
        }
    });
}

function updateTeamWorkflowSetting(g,e) {
    var k = $(e).attr('name');
    var status = 0;
    if($(e).is(":checked")) {
        status = 1;
    }
    $.ajax({
        url: 'ajax_talentpeak.php?updateTeamWorkflowSetting=1',
        type: "POST",
        data: {'groupid':g,'contextKey':k,'status':status},
        success: function(data) {
            swal.fire({title: '<?= gettext("Success");?>',text:"<?= gettext('Setting updated successfully')?>"});
        }
    });
}
function saveTeamAutoCompleteSetting(g) {
    let automatically_close_team_after_n_days = $("#automatically_close_team_after_n_days").val();
    
    if ($.isNumeric(automatically_close_team_after_n_days) && Number(automatically_close_team_after_n_days) > -1 && Number(automatically_close_team_after_n_days) % 1 === 0) {
        $.ajax({
            url: 'ajax_talentpeak.php?saveTeamAutoCompleteSetting=1',
            type: "POST",
            data: {'groupid':g,automatically_close_team_after_n_days:automatically_close_team_after_n_days},
            success: function(data) {
                swal.fire({title: '<?= gettext("Success");?>',text:"<?= gettext('Setting saved successfully')?>"});
            }
        });
    } else {
        swal.fire({title: '<?= gettext("Error");?>',text:"<?= gettext('Please enter a value greater than or equal to zero. Zero indicates disabled.')?>"});
    }
}

function saveTeamAutoCompleteOnMenteeStartDateSetting(g) {
  let auto_complete_team_ndays_after_mentee_start_date = $("#auto_complete_team_ndays_after_mentee_start_date").val();

  if ($.isNumeric(auto_complete_team_ndays_after_mentee_start_date) && Number(auto_complete_team_ndays_after_mentee_start_date) > -1 && Number(auto_complete_team_ndays_after_mentee_start_date) % 1 === 0) {
    $.ajax({
      url: 'ajax_talentpeak.php?saveTeamAutoCompleteOnMenteeStartDateSetting=1',
      type: "POST",
      data: {
        groupid:g,
        auto_complete_team_ndays_after_mentee_start_date
      },
      success: function(data) {
        swal.fire({title: '<?= gettext("Success");?>',text:"<?= gettext('Setting saved successfully')?>"});
      }
    });
  } else {
    swal.fire({title: '<?= gettext("Error");?>',text:"<?= gettext('Please enter a value greater than or equal to zero. Zero indicates disabled.')?>"});
  }
}


</script>