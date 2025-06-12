<style>
#manage_join_requests td button {
    margin-bottom: 6px;
}</style>
<table id="manage_join_requests" class="table display <?= $table_classes ?? '' ?>" summary="This table lists the various team roles and corresponding registration options">
    <thead class="thead-light">
        <tr>
        <th width="40%" class="color-black" scope="col"><?= gettext("Role");?></th>
        <th width="60%" class="color-black" scope="col"><?= gettext("Action");?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($allRoles as $row){
        if (($row['sys_team_role_type'] !=2 && $row['sys_team_role_type'] !=3) || ($row['hide_on_request_to_join'] && !in_array($row['roleid'],$requestedRoleIds)) ){
            continue;
        }
        $isRequestAllowd = true;
        $guardRails = json_decode($row['restrictions'],true);

        if (!empty($guardRails)){
            $isRequestAllowd = Team::CheckRequestRoleJoinAllowed($_USER->id(), $guardRails);
        }
    ?>
        <tr>
            <td>
                <?= $row['type'];?>
            </td>
            <td>
            <?php if($isRequestAllowd){ ?>
                <?php if(in_array($row['roleid'],$requestedRoleIds)){  // User requested to join a team
                    $disabled = '';
                    $requestDetail = Team::GetRequestDetail($groupid,$row['roleid']);

                ?>
                    
                    <button aria-label="<?= gettext("Update Registration For");?> <?= $row['type'];?>" tabindex="0" class="btn btn-affinity btn-sm" onclick="getProgramJoinOptions('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($row['roleid']); ?>','<?= $_COMPANY->encodeId($row['roleid']); ?>', '<?= $version ?>')"
                        <?= (int) $requestDetail['isactive'] === 0 ? 'disabled' : '' ?>>
                        <?= gettext("Update Registration");?>
                    </button>
                    &nbsp;
                    <?php if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['NETWORKING']) { ?>
                    <button aria-label="<?= ($requestDetail['isactive'] == 1) ? gettext("Pause Registration For") : gettext("Resume Registration For");?> <?= $row['type'];?>" tabindex="0" class="btn btn-affinity confirm btn-sm" title="<?= ($requestDetail['isactive'] == 1) ? gettext("Pause registration? You'll be hidden from searches, but can resume anytime.") : gettext("Ready to be seen again? Resume your registration to get matched"); ?>" id="pauseBtn" onclick="togglePauseTeamJoinRequest('<?= $_COMPANY->encodeId($groupid); ?>','<?=  $_COMPANY->encodeId($row['roleid']); ?>','<?=  $_COMPANY->encodeId($_USER->id()); ?>', undefined, '<?= $version ?>')"
                        <?= (int) $requestDetail['isactive'] === 0 ? 'disabled' : '' ?>>
                        <?= ($requestDetail['isactive'] == 1) ? gettext("Pause Registration") : gettext("Resume Registration");?>
                    </button>
                    <?php } ?>
                    &nbsp;
                    <button data-toggle="popover" aria-label="<?= gettext("Cancel Registration For");?> <?= $row['type'];?>" tabindex="0" <?= $disabled; ?> class="btn btn-affinity confirm btn-sm" title="<?= sprintf(gettext('Are you sure you want to cancel your registration? You can still access your existing %1$s in \'My %1$s,\'. However your profile won\'t be shown in new matches until you register again'), Team::GetTeamCustomMetaName($group->getTeamProgramType(),1)); ?>" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" id="cancelBtn" onclick="cancelTeamJoinRequest('<?= $_COMPANY->encodeId($groupid); ?>','<?=  $_COMPANY->encodeId($row['roleid']); ?>','<?=  $_COMPANY->encodeId($_USER->id()); ?>', undefined, '<?= $version ?>')"><?= gettext("Cancel Registration");?></button>

                    <?php if ((int) $requestDetail['isactive'] === 0) { ?>
                        <br>
                        <small><?= gettext('Your registration request is currently under review and has been temporarily deactivated. We apologize for any inconvenience this may cause. Please check back again later.') ?></small>
                    <?php } ?>

                <?php } elseif(in_array($group->getTeamProgramType(),[Team::TEAM_PROGRAM_TYPE['PEER_2_PEER'],Team::TEAM_PROGRAM_TYPE['ADMIN_LED']]) && !Team::IsTeamRoleRequestAllowed($groupid,$row['roleid'])){ ?>
                    <button aria-label="<?= gettext("Register For");?> <?= $row['type'];?>" tabindex="0"  class="btn btn-affinity btn-sm" disabled ><?= gettext("Register");?></button>
                    <br>
                    <small><?= sprintf(gettext('Registration Closed - Maximum Registrations Reached')); ?></small>
                <?php } elseif(!empty($requestedRoleIds) && $group->getTeamJoinRequestSetting()!=1){ ?>
                    <button aria-label="<?= gettext("Register For");?> <?= $row['type'];?>" tabindex="0"  class="btn btn-affinity btn-sm" disabled ><?= gettext("Register");?></button>
                    <br>
                    <small><?= sprintf(gettext('You cannot register for this role because system settings only allow users to register for only one role at a time in this %1$s'),$_COMPANY->getAppCustomization()['group']['name'])?></small>
                    
                <?php } elseif (in_array($row['roleid'], array_column($myTeams,'roleid'))) { // User was assigned a team without join request ?>
                    <button aria-label="<?= gettext("Register For");?> <?= $row['type'];?>" tabindex="0"  class="btn btn-affinity btn-sm" onclick="getProgramJoinOptions('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId($row['roleid']); ?>', '<?= $version ?>')" ><?= /*($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER']) ? gettext("Join") :*/ gettext("Register");?></button>
                    <br>
                    <small><?= sprintf(gettext('You\'re already assigned to a %1$s for this role. But you can still register to get matched for future %1$s assigments'),Team::GetTeamCustomMetaName($group->getTeamProgramType()))?></small>
                <?php } else { ?>
                <?php
                    $disabled = "";
                    if (!empty($requestedRoleIds) && $group->getTeamJoinRequestSetting() != 1){
                        $disabled = 'disabled';
                    }
                ?>
                    <button aria-label="<?= gettext("Register For");?> <?= $row['type'];?>" tabindex="0" <?= $disabled; ?>  class="btn btn-affinity btn-sm" onclick="getProgramJoinOptions('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId($row['roleid']); ?>', '<?= $version ?>')" ><?= /*($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER']) ? gettext('Join') :*/ gettext("Register");?></button>

                <?php } ?>
            <?php } else { ?>
                <?= gettext('Not Allowed'); ?>
            <?php } ?>
            </td>
        </tr>
    <?php  } ?>

    </tbody>
</table>
<script>
jQuery(document).ready(function() {
    jQuery(".confirm").popConfirm({content: ''});
});

retainPopoverLastFocus(); //When Cancel the popover then retain the last focus.
</script>
