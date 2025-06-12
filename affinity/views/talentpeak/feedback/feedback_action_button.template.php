<?php if($todo['createdby'] == $_USER->id()){ ?>
    <li>
    <a tabindex="0" href="javascript:void(0);" class="dropdown-item" title="<?= gettext("Edit Feedback");?>" onclick="openNewFeedbackModal('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>','<?=$_COMPANY->encodeId($todo['taskid']);?>')" ><i class="fa fas fa-edit" aria-hidden="true"></i>&emsp;<?= gettext("Edit Feedback");?></a>
    </li>
    <?php if (!in_array(TEAM::PROGRAM_TEAM_TAB['ACTION_ITEM'], $group->getHiddenProgramTabSetting()) && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
        <li>
        <a  tabindex="0" href="javascript:void(0);" onclick="createSubItemTeamTask('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>','<?=$_COMPANY->encodeId($todo['taskid']);?>','todo')" class=" dropdown-item" title="<?= gettext("Add to Action Items");?>"><i class="fa fa-list-ul" aria-hidden="true"></i>&emsp;<?= gettext("Add to Action Items");?></a>
        </li>
    <?php } ?>
    <li>
    <a tabindex="0" href="javascript:void(0);" class="confirm dropdown-item" title="<?= gettext("Are you sure you want to delete?");?>"  onclick="deleteTeamTask('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>','<?=$_COMPANY->encodeId($todo['taskid']);?>','feedback')"><i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= gettext("Delete");?></a>
    </li>
<?php } else { ?> <p class="text-center"><?= gettext("No action available");?>!</p><?php } ?>