<style>
   .switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 28px;
}

.switch input { 
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  -webkit-transition: .4s;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 20px;
  width: 20px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
}

input:checked + .slider {
  background-color: #2196F3;
}

input:focus + .slider {
  box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
  -webkit-transform: translateX(26px);
  -ms-transform: translateX(26px);
  transform: translateX(26px);
}

/* Rounded sliders */
.slider.round {
  border-radius: 28px;
}

.slider.round:before {
  border-radius: 50%;
}
.custom-control-label::before {
  background-color: transparent !important;
  border: none !important;
}
</style>
<div class="col-md-12  p-0 mt-5">
    <div class="col-md-3 col-xs-12">
            <h5><?= gettext("Action Items");?></h5>
    </div>
    <div class="col-md-9 col-xs-12 p-0">
        <!-- <div class="col-md-9 text-right p-0">
            <small>Only show my to-do's</small>
            <label class="switch">
                <input type="checkbox" checked onchange="getTeamsTodoList('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>',1)">
                <span class="slider round"></span>
            </label>
        </div> -->
      <?php if (!$team->isComplete() && !$team->isIncomplete() && !$team->isInactive() && !$team->isPaused()){ ?>
        <div class="col-md-12 text-right p-0">
            <button class="btn btn-affinity btn-sm action-item" onclick="openCreateTodoModal('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>','<?=$_COMPANY->encodeId(0);?>')">+&nbsp;<?= gettext("New Action Item");?></button>
        </div>
      <?php } ?>
    </div>
    <?php if($progressBarSetting['show_actionitem_progress_bar']){ ?>
      <div class="col-md-12 text-center bg-light mt-2">
        <?php include(__DIR__ . "/../common/assinged_task_progress_stats.templagte.php"); ?>
      </div>
    <?php } ?>

    <div class="col-md-12 p-0 mt-3">
        <div style="text-align: center; border-top: 1px solid rgb(128, 127, 127)">
            <strong style="display: inline-block; position: relative; top: -15px; background-color: white;"></strong>
        </div>
    </div>

    <div class="row">
      <div class="table-responsive">
        <table id="team_task_list" class="table display" summary="This table display the tasks list of a team">
          <thead>
            <tr>
              <th width="30%" class="color-black" scope="col"><?= gettext("Task");?></th>
              <th width="25%" class="color-black" scope="col"><?= gettext("Assignee");?></th>
              <th width="15%" class="color-black" scope="col"><?= gettext("Due Date");?></th>
              <th width="15%" class="color-black" scope="col"><?= gettext("Status");?></th>
              <th width="15%" class="color-black" scope="col"><?= gettext("Comments");?></th>
              <?php if (!$team->isComplete() && !$team->isIncomplete() && !$team->isInactive() && !$team->isPaused()) { ?>
              <th width="5%" class="color-black" scope="col"><?= gettext("Action");?></th>
              <?php } ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach($todolist as $todo){ 
              $status = Team::GET_TALENTPEAK_TODO_STATUS;
              $color = array('0'=>'rgb(255, 233, 233)','1'=>'rgb(252, 252, 217)','51'=>'none','52'=>'none');

              if ($actionItemConfig['action_item_visibility'] == Group::ACTION_ITEM_VISIBILITY_SETTING['show_to_assignee_and_mentors'] && !$manage_section) {
                $memberDetail = $team->getTeamMembershipDetailByUserId($_USER->id());
                if ($todo['assignedto'] != $_USER->id() && $memberDetail['sys_team_role_type'] != 2){
                  continue;
                }
              }
            ?>
              <tr style="background-color: <?= $color[$todo['isactive']]?>;">
                <td>
                  <i class="fa fa-check-circle fa-lg no-hover <?= $todo['isactive'] != 52 ? 'gray' : ''; ?>" aria-hidden="true"></i>&emsp;
                  <a href="javascript:void(0);" class=" m-0 p-0" onclick="getTaskDetailView('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>','<?=$_COMPANY->encodeId($todo['taskid']);?>')"><strong><?= htmlspecialchars($todo['tasktitle']); ?></strong></a>
                </td>
                <td>
                <?php if($todo['assignedto']){ ?>
                    <?= User::BuildProfilePictureImgTag($todo['firstname'],$todo['lastname'],$todo['picture'],'memberpicture_small', 'User Profile Picture', $todo['assignedto'], 'profile_full') ?>
                    <?= $todo['firstname'].' '.$todo['lastname'];?>
                <?php } else { ?>
                  <?= gettext("Not assigned");?>
                <?php } ?>
                </td>
                <td>
                  <span style="display: none;"><?= ($todo['duedate'] && strtotime($todo['duedate']) >0) ? $todo['duedate'] :'-'; ?></span>
                  <?= ($todo['duedate'] && strtotime($todo['duedate']) >0) ? $_USER->formatUTCDatetimeForDisplayInLocalTimezone($todo['duedate'],true,true,true) : '-' ?>
                </td>
                <td>
                  <?= $status[$todo['isactive']];?>
                </td>
                <td>
                  <?= TeamTask::GetCommentsTotal($todo['taskid']);?>
                </td>
              <?php if (!$team->isComplete() && !$team->isIncomplete() && !$team->isInactive() && !$team->isPaused()){ ?>
                <td>
                  <div class="dropdown text-center">
                    <button id="doutdBtn" class="btn-no-style dropdown-toggle fa fa-ellipsis-v col-doutd"  aria-label="<?= sprintf(gettext('%s more actions'),htmlspecialchars($todo['tasktitle'])); ?>" tabindex="0" data-toggle="dropdown"></button>
                    <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton" style="width: 200px !important;">
                      <?php include(__DIR__ . "/../common/action_button.template.php"); ?>
                    </ul>
                  </div>
                </td>
              <?php } ?>
              </tr>
            <?php } ?>

          </tbody>
        </table>
      </div>
    </div>
</div>
<?php include(__DIR__ . "/../common/update_task_status_modal.template.php"); ?>
<script>

  $('#team_task_list').dataTable({    
    "bInfo": true, /* For accessiblity screen reading purpose we need to on this feature for end users  */  //Dont display info e.g. "Showing 1 to 4 of 4 entries"
    'language': {
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },	
    "paging": true,
    "order": [],
    "lengthChange": false,
    "drawCallback": function() {
        setAriaLabelForTablePagination(); 
     },
     "initComplete": function (settings, json) {     
        setAriaLabelForTablePagination();   	
      },
  <?php if(!$team->isComplete() && !$team->isIncomplete() && !$team->isInactive() && !$team->isPaused()){ ?>
    "columnDefs": [
      { "targets": [-1], "orderable": false }
    ]    
  <?php } ?>
  
  })
</script>

