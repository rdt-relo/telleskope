<style>
 .custom-control-label::before {
   background-color: transparent !important;
   border: none !important;
 }
 </style>
 <div class="col-md-12  p-0 mt-5">
     <div class="col-md-3 col-xs-12">
             <h5><?= gettext("Touch Points");?></h5>
     </div>
    <?php if(!$team->isComplete() && !$team->isIncomplete() && !$team->isInactive() && !$team->isPaused()){ ?>
     <div class="col-md-9 col-xs-12 p-0">
         <div class="col-md-12 text-right p-0">
          <div class="dropdown">
          <?php if($touchPointTypeConfig['type'] =='touchpointevents' && $_COMPANY->getAppCustomization()['teams']['team_events']['enabled']){ ?>
              <button class="btn btn-affinity btn-sm touch-point" onclick="openNewTeamEventForm('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>','<?=$_COMPANY->encodeId(0);?>','<?= $_COMPANY->encodeId(0); ?>')">+&nbsp;<?= gettext("New Touch Point Event");?></button>
          <?php } else { ?>
              <button class="btn btn-affinity btn-sm touch-point" onclick="openCreateTouchpointModal('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>','<?=$_COMPANY->encodeId(0);?>')">+&nbsp;<?= gettext("New Touch Point");?></button>
          <?php } ?>
          </div>
         </div>
     </div>
     <?php } ?>
    <?php if ($progressBarSetting['show_touchpoint_progress_bar']){ ?>
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
               <th width="35%" class="color-black" scope="col"><?= gettext("Task");?></th>
               <th width="20%" class="color-black" scope="col"><?= gettext("Created By");?></th>
               <th width="15%" class="color-black" scope="col"><?= gettext("Due Date");?></th>
               <th width="15%" class="color-black" scope="col"><?= gettext("Status");?></th>
               <th width="15%" class="color-black" scope="col"><?= gettext("Comments");?></th>
              <?php if(!$team->isComplete() && !$team->isIncomplete() && !$team->isInactive() && !$team->isPaused()){ ?>
               <th width="5%" class="color-black" scope="col"><?= gettext("Action");?></th>
              <?php } ?>
             </tr>
           </thead>
           <tbody>
             <?php foreach($todolist as $todo){ 
              $status = Team::GET_TALENTPEAK_TODO_STATUS;
              $color = array('0'=>'rgb(255, 233, 233)','1'=>'rgb(252, 252, 217)','51'=>'none','52'=>'none');
             ?>
               <tr style="background-color: <?= $color[$todo['isactive']]?>;">
                 <td>
                  <i class="fa fa-check-circle fa-lg no-hover <?= $todo['isactive'] != 52 ? 'gray' : ''; ?>" aria-hidden="true"></i>&emsp;
                  <a href="javascript:void(0);" class="m-0 p-0" onclick="getTaskDetailView('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>','<?=$_COMPANY->encodeId($todo['taskid']);?>')"><strong><?= htmlspecialchars($todo['tasktitle']) ?></strong></a>
                  <?php if($todo['eventtitle']){ ?>
                    <br>
                    <a href="javascript:void(0);" class="p-0" style="margin-left:32px;" onclick="getEventDetailModal('<?= $_COMPANY->encodeId($todo['eventid']); ?>','<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId(0); ?>')"><i class="fa fa-calendar" aria-hidden="true"></i>&nbsp;<?= $todo['eventtitle']; ?></a>
                  <?php } ?>
                 </td>
                 <td>
                 <?php if($todo['createdby']){ ?>
                     <?= User::BuildProfilePictureImgTag($todo['firstname'],$todo['lastname'],$todo['picture'],'memberpicture_small', 'User Profile Picture', $todo['createdby'], 'profile_full') ?>
                     <?= $todo['firstname'].' '.$todo['lastname'];?>
                 <?php } else { ?>
                     <?= gettext("Not assigned");?>
                 <?php } ?>
                 </td>
                 <td>
                   <span style="display: none;"><?= $todo['duedate']; ?></span>
                   <?= (!$todo['duedate'] || strtotime($todo['duedate']) === false)? '-'.gettext("Not set").'-' :  $_USER->formatUTCDatetimeForDisplayInLocalTimezone($todo['duedate'],true,true,true) ?>
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
                     <button class="btn-no-style dropdown-toggle fa fa-ellipsis-v col-doutd" aria-label="<?= sprintf(gettext('%s more actions'), htmlspecialchars($todo['tasktitle'])); ?>" data-toggle="dropdown"></button>
                     <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton" style="width: 200px;">
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
 