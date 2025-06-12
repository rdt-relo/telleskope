<style>
  .custom-control-label::before {
    background-color: transparent !important;
    border: none !important;
  }
 </style>
 <div class="col-md-12  p-0 mt-5">
     <div class="col-md-3 col-xs-12">
             <h5><?= gettext("Feedback");?></h5>
     </div>
    <?php if(!$team->isComplete() && !$team->isIncomplete() && !$team->isInactive() && !$team->isPaused()){ ?>
     <div class="col-md-9 col-xs-12 p-0">
         <div class="col-md-12 text-right p-0">
             <button class="btn btn-affinity btn-sm new-feedback" onclick="openNewFeedbackModal('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>','<?=$_COMPANY->encodeId(0);?>')">+&nbsp;<?= gettext("New&nbsp;Feedback");?></button>
         </div>
     </div>
    <?php } ?>
     <div class="col-md-12 p-0 mt-3">
         <div style="text-align: center; border-top: 1px solid rgb(128, 127, 127)">
             <strong style="display: inline-block; position: relative; top: -15px; background-color: white;"></strong>
         </div>
     </div>
 
     <div class="row">
       <div class="table-responsive">
         <table id="team_task_list" class="table display" summary="This table display the Feedback list of a team">
           <thead>
             <tr>
                <th width="25%" class="color-black" scope="col"><?= gettext("Title");?></th>
                <th width="25%" class="color-black" scope="col"><?= gettext("For");?></th>
                 <th width="25%" class="color-black" scope="col"><?= gettext("By");?></th>
                <th width="15%" class="color-black" scope="col"><?= gettext("Date");?></th>
                <th width="15%" class="color-black" scope="col"><?= gettext("Comments");?></th>
                <?php if(!$team->isComplete() && !$team->isIncomplete() && !$team->isInactive() && !$team->isPaused()){ ?>
                <th width="10%" class="color-black" scope="col"><?= gettext("Action");?></th>
                <?php } ?>
             </tr>
           </thead>
           <tbody>
             <?php foreach($todolist as $todo){
                 if (!$team->loggedinUserCanViewFeedback($todo['visibility'], $todo['createdby'], $todo['assignedto'])) {
                     continue;
                 }
                 $createdBy = User::GetUser($todo['createdby']);
              ?>
               <tr>
                 <td>
                  <a href="javascript:void(0);" class="m-0 p-0" onclick="getTaskDetailView('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($teamid);?>','<?=$_COMPANY->encodeId($todo['taskid']);?>')"><strong><?= htmlspecialchars($todo['tasktitle']); ?></strong></a>
                 </td>

                <td>
                    <?php if($todo['assignedto']){ ?>
                    <?= User::BuildProfilePictureImgTag($todo['firstname'], $todo['lastname'], $todo['picture'], 'memberpicture_small', 'Profile picture of person who is assigned this feedback', $todo['assignedto'], 'profile_full')?>
                     <?= $todo['firstname'].' '.$todo['lastname'];?>
                    <?php } else { ?>
                    <?= sprintf(gettext('%s Leaders'),$_COMPANY->getAppCustomization()['group']['name-short']); ?>
                    <?php } ?>
                </td>


                <td>
                   <?php if($createdBy){ ?>
                       <?= User::BuildProfilePictureImgTag($createdBy->val('firstname'), $createdBy->val('lastname'), $createdBy->val('picture'), 'memberpicture_small', 'Profile picture of person who created this feedback', $createdBy->id(), 'profile_full')?>
                       <?= $createdBy->getFullName()?>
                   <?php } else { ?>
                      <?= User::BuildProfilePictureImgTag("Deleted", 'User', '', 'memberpicture_small', 'Profile picture of person who created this feedback')?>
                       Deleted user
                   <?php } ?>
                </td>
                
                 <td>
                  <span style="display: none;"><?= $todo['createdon']; ?></span>
                  <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($todo['createdon'],true,true,true) ?>
                 </td>
                 <td>
                   <?= TeamTask::GetCommentsTotal($todo['taskid']);?>
                 </td>
                <?php if (!$team->isComplete() && !$team->isIncomplete() && !$team->isInactive() && !$team->isPaused()){ ?>
                 <td>
                   <div class="dropdown text-center">
                     <button id="doutdBtn" class="btn-no-style dropdown-toggle fa fa-ellipsis-v col-doutd" aria-label="<?= sprintf(gettext('%s more actions'),htmlspecialchars($todo['tasktitle'])); ?>" data-toggle="dropdown"></button>
                     <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton" style="width: 200px;">
                      <?php include(__DIR__ . "/feedback_action_button.template.php"); ?>
                     </div>
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
 
 