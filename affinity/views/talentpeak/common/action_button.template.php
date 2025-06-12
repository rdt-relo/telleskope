<?php if($todo['isactive'] !=52){ ?>
    <li><a  href="javascript:void(0);" tabindex="0" class="dropdown-item" title="<?= $todo['task_type'] == 'todo' ? gettext("Edit Action Item") : gettext("Edit Touchpoint"); ?>" <?php if($todo['task_type'] == 'todo'){ ?> onclick="openCreateTodoModal('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>','<?=$_COMPANY->encodeId($todo['taskid']);?>')"  <?php } else { ?> onclick="openCreateTouchpointModal('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>','<?=$_COMPANY->encodeId($todo['taskid']);?>')"  <?php } ?> ><i class="fa fas fa-edit" aria-hidden="true"></i>&emsp;<?=  $todo['task_type'] == 'todo' ? gettext("Edit Action Item") : gettext("Edit Touchpoint");?></a></li>
<?php } ?>

<?php if($_COMPANY->getAppCustomization()['teams']['team_events']['enabled'] && $todo['task_type'] == 'touchpoint' && !isset($hideEventAddEditFromTouchPointActionButton) && $todo['isactive'] !=52){ ?>
    <?php if($todo['eventid']){ ?>
        <li><a href="javascript:void(0);" tabindex="0" onclick="openNewTeamEventForm('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($teamid); ?>','<?= $_COMPANY->encodeId($todo['eventid']); ?>','<?= $_COMPANY->encodeId(0); ?>')" class=" dropdown-item" title="<?= gettext("Update Event");?>"><i class="fa fa-calendar" aria-hidden="true"></i>&emsp;<?= gettext("Update Event");?></a></li>

    <?php }else{ ?>
        <?php if (isset($touchPointTypeConfig) && $touchPointTypeConfig['type'] != 'touchpointonly') { ?>
        <li><a href="javascript:void(0);" tabindex="0" onclick="openNewTeamEventForm('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($teamid); ?>','<?= $_COMPANY->encodeId($todo['eventid']); ?>','<?= $_COMPANY->encodeId($todo['taskid']); ?>')" class=" dropdown-item" title="<?= gettext("Create Event");?>"><i class="fa fa-calendar" aria-hidden="true"></i>&emsp;<?= gettext("Create Event");?></a></li>
        <?php } ?>

    <?php } ?>

<?php } ?>

<?php if ($todo['task_type'] == 'touchpoint' && isset($touchPointTypeConfig) && $touchPointTypeConfig['show_copy_to_outlook']) { ?>
    <li><a href="javascript:void(0);" tabindex="0" onclick="initTouchPointDetailToOutlook('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($teamid); ?>','<?= $_COMPANY->encodeId($todo['taskid']); ?>')" class=" dropdown-item" title="<?= gettext("Copy Details");?>"><i class="fa fa-copy" aria-hidden="true"></i>&emsp;<?= gettext("Copy Details");?></a></li>
<?php } ?>

    <li><a href="javascript:void(0);"tabindex="0" onclick="openUpdateTaskStatusModal('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>','<?=$_COMPANY->encodeId($todo['taskid']);?>','<?=$_COMPANY->encodeId($todo['isactive']);?>')" class=" dropdown-item" title="<?= gettext("Update Status");?>"><i class="fa fa-list-ul" aria-hidden="true"></i>&emsp;<?= gettext("Update Status");?></a></li>

<?php if( !in_array(TEAM::PROGRAM_TEAM_TAB['TOUCH_POINT'], $group->getHiddenProgramTabSetting()) && $todo['task_type'] == 'todo' && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
    <li><a href="javascript:void(0);" tabindex="0"  onclick="createSubItemTeamTask('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>','<?=$_COMPANY->encodeId($todo['taskid']);?>','touchpoint')" class=" dropdown-item" title="<?= gettext("Add to Touchpoint");?>"><i class="fa fa-list-ul" aria-hidden="true"></i>&emsp;<?= gettext("Add to Touchpoint");?></a></li>

<?php } ?>
<?php if($todo['isactive'] !=0){ ?>
    <li><a href="javascript:void(0);" tabindex="0" class="confirm dropdown-item" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to delete?');?>"  onclick="deleteTeamTask('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>','<?=$_COMPANY->encodeId($todo['taskid']);?>','<?= $todo['task_type']; ?>')"><i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= gettext("Delete");?></a></li>
<?php } ?>