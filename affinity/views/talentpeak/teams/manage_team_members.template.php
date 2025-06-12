<style>
#modal-title{
  float:left;
}
  </style>
<div id="manage_team_members_modal" tabindex="-1" class="modal fade">
    <div aria-label="<?= $modalTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
              <div>
                <h2 id="modal-title" class="modal-title"><?= $modalTitle; ?>&nbsp;</h2>
                <?php if($section =="0" && !$team->isComplete() && !$team->isIncomplete() && !$team->isInactive() && !$team->isPaused()){ ?>
                  <?php if($group->getTeamProgramType()!= Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV'] || ($group->getTeamProgramType()== Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV'] && count($rows) == 0)){ ?>
                        <a role="button" id="setfocus" aria-label="add team member" href="javascript:void(0)" onclick="openSearchTeamUserModal('<?=$_COMPANY->encodeId($groupid);?>','<?= $_COMPANY->encodeId($teamid); ?>','<?= $_COMPANY->encodeId(0); ?>')"><i class="fa fa-plus-circle"></i></a>
                  <?php } ?>
                <?php } ?></div>
                
                <button type="button" id="btn_close" class="close" aria-label="close" data-dismiss="modal" >&times;</button>
            </div>
            <div class="modal-body">
				    <div class="col-md-12 px-0">
                    <div class="table-responsive">
                        <table id="manage_team_members_table" class="table display" summary="This table display the list of members in the team" width="100%">
                          <thead>
                            <tr>
                              <th width="20%" class="color-black" scope="col"><?= gettext("Member Type");?></th>
                              <th width="30%" class="color-black" scope="col"><?= gettext("Name");?></th>
                              <th width="20%" class="color-black" scope="col"><?= gettext("Since");?></th>

                              <?php if ($group->getTeamProgramType()!= Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV'] && $team->val('isactive') != Team::STATUS_COMPLETE && $team->val('isactive') != Team::STATUS_INCOMPLETE && $team->val('isactive') != Team::STATUS_PAUSED){ ?>
                              <th width="5%" class="color-black" scope="col"><?= gettext("Action");?></th>
                              <?php } ?>

                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach($rows as $row){ ?>
                              <tr id="<?= $_COMPANY->encodeId($row['team_memberid']); ?>">
                                <td>
                                  <?= ucfirst($row['role']);?>
                                  <br>
                                  <?= $row['roletitle'] ? ' '. htmlspecialchars($row['roletitle']) : '' ?>
                                </td>
                                <td>
                                  <?= User::BuildProfilePictureImgTag($row['firstname'], $row['lastname'], $row['picture'], 'memberpicture_small', 'User Profile Picture', $row['userid'], 'profile_full')?>
                                  <?= $row['firstname'].' '.$row['lastname'];?>
                                  <br>
                                  <?= $row['jobtitle'];?>
                                  <br>
                                  <?= $row['email'];?>
                                </td>
                                <td>
                                  <span style="display: none;"><?= $row['createdon']; ?></span>
                                  <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['createdon'],true,true,true) ?>
                                 
                                </td>
                                <?php if ($group->getTeamProgramType()!= Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV'] && $team->val('isactive') != Team::STATUS_COMPLETE && $team->val('isactive') != Team::STATUS_INCOMPLETE && $team->val('isactive') != Team::STATUS_PAUSED){ ?>
                                <td>
                                    <?php if (($team->val('isactive') != Team::STATUS_COMPLETE) && ($team->val('isactive') != Team::STATUS_INCOMPLETE) && ($team->val('isactive') != Team::STATUS_PAUSED) && !($row['firstname'] == 'Deleted' && $row['lastname'] == 'User')){ ?>
                                        <button aria-label="<?= gettext('Edit');?>" tabindex="0" class="btn-no-style" onclick="openSearchTeamUserModal('<?=$_COMPANY->encodeId($groupid);?>','<?= $_COMPANY->encodeId($teamid); ?>','<?= $_COMPANY->encodeId($row['team_memberid']); ?>')">
                                            <i class="fa fas fa-edit" aria-hidden="true"></i>
                                        </button>
                                        &nbsp;
                                    <?php } else { ?>
                                        <button aria-label="<?= gettext('Edit');?>" tabindex="-1" class="btn-link disabled btn-no-style">
                                          <i class="fa fas fa-edit gray" aria-hidden="true"></i>
                                        </button>
                                        &nbsp;
                                    <?php } ?>
                                      <?php 
                                        if (0 && $group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES'] && $row['sys_team_role_type'] == 2){ ?>
                                        <i class="fa fas fa-trash gray disabled"  aria-hidden="true"></i>
                                      <?php } else{ ?>
                                        <button tabindex="0" class="delete pop-identifier btn-no-style" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to delete?')?>" aria-label="<?= gettext('delete');?>"  onclick="deleteTeamMember('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($row['teamid']); ?>','<?= $_COMPANY->encodeId($row['team_memberid']); ?>')"> <i class="fa fas fa-trash" aria-hidden="true"></i> </button>
                                      <?php } ?>
                                </td>
                                <?php } ?>
                              </tr>
                            <?php } ?>
                
                          </tbody>
                        </table>
                      </div>
                </div>
            </div>
            <div class="modal-footer text-center" style='text-align:center !important;display: block;'>
                <button aria-label="<?= gettext('close');?>" type="button" class="btn btn-affinity" data-dismiss="modal" ><?= gettext("Close");?></button>
            </div>
        </div>
    </div>
</div>
<script>
  var dtable = $('#manage_team_members_table').DataTable({    
    "bInfo": false,
    "paging": true, 
    "lengthChange": true,
    order: [[0, 'desc']],
    columnDefs: [
			{ targets: [-1], orderable: false }
		],	
    language: {
					url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
				},
  })
  screenReadingTableFilterNotification('#manage_team_members_table',dtable);
</script>

<script>
  $(function(){
    $('[data-toggle="popover"]').popover({
      sanitize:false                    
    });  
  }); 

$('#manage_team_members_modal').on('shown.bs.modal', function () {
  if (!$("#setfocus").is(':visible')) {
    	$('#btn_close').focus();
	}else{
		$('#setfocus').eq(0).focus();
	}  
});

$('.pop-identifier').each(function() {
	$(this).popConfirm({
	container: $("#manage_team_members_modal"),
	});
});

retainFocus('#manage_team_members_modal');
</script>