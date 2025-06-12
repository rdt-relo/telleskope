<?php
$opts = 0;
$reloadAfterAction = $reloadAfterAction ?? 0;
$teamMemberAction = $teamMemberAction ?? 0;
$myMembershipRecord = Arr::SearchColumnReturnRow($teamMembers, $_USER->id(),'userid');
$mySysRecordIsMentee = isset($myMembershipRecord['sys_team_role_type']) && ($myMembershipRecord['sys_team_role_type'] == 3);

// Optimization --  Only compute $canUpdateTeamStatus for team member actions otherwise it is not required
$canUpdateTeamStatus = $teamMemberAction && $teamObj->canUpdateTeamStatus($group);

?>

<?php if (!$teamMemberAction && ($team['isactive'] == Team::STATUS_DRAFT || $team['isactive'] == Team::STATUS_INACTIVE)) { $opts++; ?>
    <?php if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']){  ?>
        <li><a role="button" href="javascript:void(0)" tabindex="0" title="Edit" onclick="openNewTeamModal('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($team['teamid']); ?>','actionBtn')" ><i class="fa fas fa-edit" aria-hidden="true"></i>&emsp;<?= gettext("Edit");?></a></li>
    <?php } ?>
    <li><a role="button" href="javascript:void(0)" tabindex="0" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= sprintf(gettext("Are you sure you want to activate this %s?"), Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>"  onclick="updateTeamStatus('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($team['teamid']); ?>','<?= $_COMPANY->encodeId(1); ?>','<?= $reloadAfterAction; ?>')"><i class="fa fa-lock" aria-hidden="true"></i>&emsp;<?= gettext("Activate");?></a></li>
<?php } ?>

<?php if (($team['isactive'] == Team::STATUS_ACTIVE) && $group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" tabindex="0" title="Get Shareable Link" onclick="getShareableLink('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId($team['teamid'])?>',13)" ><i class="fa fa-share-square" aria-hidden="true"></i>&emsp; <?= gettext("Get Shareable Link");?></a>
<?php } ?>

<?php if ($teamMemberAction && ($_USER->id() != $team['createdby']) && ($team['isactive'] == Team::STATUS_ACTIVE) && ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']) && $mySysRecordIsMentee /*mentee*/) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" tabindex="0" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= sprintf(gettext("Are you sure you want to leave this %s?"), Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>" onclick="leaveCircleMembership('<?= $_COMPANY->encodeId($team['teamid']); ?>', '<?= $_COMPANY->encodeId($myMembershipRecord['team_memberid']); ?>')"><i class="fa fa-sign-out-alt" aria-hidden="true"></i>&emsp;<?= sprintf(gettext("Leave %s"), Team::GetTeamCustomMetaName($group->getTeamProgramType()));?></a></li></li>
<?php } ?>

<?php if (!$teamMemberAction && (in_array($team['isactive'],[Team::STATUS_ACTIVE, Team::STATUS_PAUSED]))) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" tabindex="0" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= sprintf(gettext("Are you sure you want to deactivate this %s?"), Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>" onclick="updateTeamStatus('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($team['teamid']); ?>','<?= $_COMPANY->encodeId(Team::STATUS_INACTIVE); ?>','<?= $reloadAfterAction; ?>')"><i class="fa fa-unlock-alt" aria-hidden="true"></i>&emsp;<?= gettext("Deactivate");?></a></li>
<?php } ?>

<?php if ((!$teamMemberAction || $canUpdateTeamStatus) && ($team['isactive'] == Team::STATUS_ACTIVE)) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" tabindex="0" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= sprintf(gettext("Are you sure you want to pause this %s ?"), Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>" onclick="updateTeamStatus('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($team['teamid']); ?>','<?= $_COMPANY->encodeId(Team::STATUS_PAUSED); ?>','<?= $reloadAfterAction; ?>')"><i class="fa fa-pause" aria-hidden="true"></i>&emsp;<?= gettext("Pause");?></a></li>
<?php } ?>

<?php if ((!$teamMemberAction || $canUpdateTeamStatus) && (in_array($team['isactive'],[Team::STATUS_ACTIVE, Team::STATUS_PAUSED]))) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" tabindex="0" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= sprintf(gettext("Are you sure you want to close this %s as incomplete?"), Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>" onclick="updateTeamStatusToComplete('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($team['teamid']); ?>','<?= $_COMPANY->encodeId(Team::STATUS_INCOMPLETE); ?>','<?= $reloadAfterAction; ?>')"><i class="fa fa-exclamation" aria-hidden="true"></i>&emsp;<?= gettext("Close as Incomplete");?></a></li>
<?php } ?>

<?php if ((!$teamMemberAction || $canUpdateTeamStatus) && (in_array($team['isactive'],[Team::STATUS_ACTIVE, Team::STATUS_PAUSED]))) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" tabindex="0" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= sprintf(gettext("Are you sure you want to mark this %s as complete?"), Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>" onclick="updateTeamStatusToComplete('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($team['teamid']); ?>','<?= $_COMPANY->encodeId(Team::STATUS_COMPLETE); ?>','<?= $reloadAfterAction; ?>')"><i class="fa fa-check" aria-hidden="true"></i>&emsp;<?= gettext("Close as Complete");?></a></li>
<?php } ?>

<?php if (!$teamMemberAction && ($team['isactive'] == Team::STATUS_DRAFT || $team['isactive'] == Team::STATUS_PURGE || $team['isactive'] == Team::STATUS_INACTIVE)) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" tabindex="0" onclick="deleteTeamConfirmModal('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($team['teamid']); ?>')"><i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= sprintf(gettext('Delete %s'),Team::GetTeamCustomMetaName($group->getTeamProgramType())); ?></a></li>
<?php } ?>

<?php if (!$teamMemberAction && ($team['isactive'] == Team::STATUS_PURGE)) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" tabindex="0" class="confirm"  onclick="updateTeamStatus('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($team['teamid']); ?>','<?= $_COMPANY->encodeId(2); ?>','<?= $reloadAfterAction; ?>')" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= addslashes(gettext("Are you sure you want to undo delete?"));?>" ><i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= addslashes(gettext("Undo Delete"));?></a></li>
<?php } ?>

<?php if ((!$teamMemberAction && (in_array($team['isactive'],[Team::STATUS_COMPLETE, Team::STATUS_INCOMPLETE, Team::STATUS_PAUSED]))) || (($team['isactive'] == Team::STATUS_PAUSED) && (!$mySysRecordIsMentee)) || (($team['isactive'] == Team::STATUS_PAUSED) && ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['NETWORKING']))) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" tabindex="0" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>"  onclick="updateTeamStatus('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($team['teamid']); ?>','<?= $_COMPANY->encodeId(Team::STATUS_ACTIVE); ?>','<?= $reloadAfterAction; ?>')" title="<?= sprintf(gettext("Are you sure you want to activate this %s?"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>" ><i class="fa fa-lock" aria-hidden="true"></i>&emsp;<?= gettext("Activate (reopen)");?></a></li>
<?php } ?>

<?php if (!$teamMemberAction && (in_array($team['isactive'],[Team::STATUS_COMPLETE, Team::STATUS_INCOMPLETE]))) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" tabindex="0" onclick="deleteTeamConfirmModal('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($team['teamid']); ?>')"><i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= sprintf(gettext('Delete %s'),Team::GetTeamCustomMetaName($group->getTeamProgramType())); ?></a></li>
<?php } ?>

<?php if (!$opts){  ?>
    <li><a role="button" href="javascript:void(0)" tabindex="0" class="disabled"><i class="fa fas fa-exclamation" aria-hidden="true"></i>&emsp;<?= addslashes(gettext("No options available"));?></a></li>
<?php } ?>