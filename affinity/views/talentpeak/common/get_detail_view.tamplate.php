<div class="row">
    <div class="col-md-12 mt-4 p-0">
        <div class="col-md-12 m-0 pt-4 ">
            <div style="text-align: center; border-top: 1.5px dashed rgb(185, 182, 182)">
                <strong style="display: inline-block; position: relative; top: -15px; background-color: white; padding: 0px 10px">
                    <?= sprintf(gettext("%s Detail"),ucfirst($data[0]['task_type']=='todo' ? gettext('Action Item'): $data[0]['task_type']));?>
                </strong>
            </div>    
        </div>

        <div class="task-title p-0 col-md-12">
            <div class="my-2 row">
                <div class="col-md-11">
                    <h5><?= htmlspecialchars($data[0]['tasktitle']); ?></h5>
                </div>
                <?php if (!$team->isComplete() && !$team->isIncomplete() && !$team->isInactive() && !$team->isPaused()){ ?>
                <div class="col-md-1">
                    <div class="dropdown pull-right">
                        <button id="doutdBtn" class="btn-no-style dropdown-toggle fa fa-ellipsis-v col-doutd" aria-label="<?= sprintf(gettext('%s more actions'),htmlspecialchars($data[0]['tasktitle'])); ?>" data-toggle="dropdown"></button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton" style="width: 200px !important;">
                        <?php if( $data[0]['task_type']!='feedback'){ ?>
                            <?php $todo = $data[0]; include(__DIR__ . "/../common/action_button.template.php"); ?>
                        <?php } else { ?>
                            <?php $todo = $data[0]; include(__DIR__ . "/../feedback/feedback_action_button.template.php"); ?>
                        <?php } ?>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>

            <div class="assigned-to" style="padding: 3px 10px; background-color: #f4f4f4;">
                <p>
                    <small>
                        <strong><?= gettext("Created by");?>:</strong>
                        <?= User::BuildProfilePictureImgTag($data[0]['creator_firstname'], $data[0]['creator_lastname'], $data[0]['creator_picture'], 'memberpicture_small', 'User Profile Picture', $data[0]['createdby'], 'profile_full')?>
                        <?= $data[0]['creator_firstname'].' '.$data[0]['creator_lastname'];?>
                        <?= sprintf(gettext("on %s"),$db->covertUTCtoLocalAdvance("D M j, Y h:i a","",  $data[0]['createdon'],$_SESSION['timezone']));?>
                    </small>
                </p>

                <?php if($data[0]['task_type']!='touchpoint' && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
                <p>
                    <small>
                    <?php if ($data[0]['task_type'] == 'todo'){ ?>
                        <strong><?= gettext("Assigned to").':'?></strong>
                        <?= User::BuildProfilePictureImgTag($data[0]['firstname'], $data[0]['lastname'], $data[0]['picture'], 'memberpicture_small', 'User Profile Picture', $data[0]['assignedto'], 'profile_full')?>
                        <?= $data[0]['firstname'].' '.$data[0]['lastname'];?>
                    <?php } elseif ($data[0]['task_type'] == 'feedback') { ?>
                        <strong><?= gettext("Feedback for").':'?></strong>
                        <?php if (!$data[0]['assignedto']) { ?>
                        <?= sprintf(gettext('%s Leaders'),$_COMPANY->getAppCustomization()['group']['name-short']); ?>
                        <?php } else { ?>
                        <?= User::BuildProfilePictureImgTag($data[0]['firstname'], $data[0]['lastname'], $data[0]['picture'], 'memberpicture_small', 'User Profile Picture', $data[0]['assignedto'], 'profile_full')?>
                        <?= $data[0]['firstname'].' '.$data[0]['lastname'];?>
                        <?php } ?>
                    <?php } ?>
                    </small>
                </p>
                <?php } ?>
            </div>

            <?php if($data[0]['task_type']!='feedback'){ ?>
            <div class="my-2">
                <?php if (!$data[0]['duedate'] || strtotime($data[0]['duedate'])<0){ ?>
                    <span class="px-2 py-1" style="background: gray; color:#fff; border-radius: 5px; vertical-align:super;font-size: smaller;">
                        <?= gettext("No due date set"); ?>
                    </span>
                <?php } else { ?>
                    <?php if($data[0]['isactive'] == 1){ /* not started */ ?>
                        <?php
                        $color = "darkorange";
                        if (date('Y-m-d H:i:s') > $db->covertUTCtoLocalAdvance("Y-m-d H:i:s","",  $data[0]['duedate'],$_SESSION['timezone'])){
                            $color = "red";
                        }
                        ?>
                    <span class="px-2 py-1" style="background: <?= $color;?>; color:#fff; border-radius: 5px; vertical-align:super;font-size: smaller;">
                        <?= $status[$data[0]['isactive']];?>, <?= sprintf(gettext("due on %s"), $db->covertUTCtoLocalAdvance("D M j, Y h:i a","",  $data[0]['duedate'],$_SESSION['timezone']));?>
                    </span>
                    <?php } elseif($data[0]['isactive'] == 51){ /* in progress */ ?>
                        <?php
                        $color = "royalblue";
                        if (date('Y-m-d H:i:s') > $db->covertUTCtoLocalAdvance("Y-m-d H:i:s","",  $data[0]['duedate'],$_SESSION['timezone'])){
                            $color = "red";
                        }
                        ?>
                    <span class="px-2 py-1" style="background: <?= $color; ?>; color:#fff;border-radius: 5px; vertical-align:super;font-size: smaller;">
                        <?= $status[$data[0]['isactive']];?>, <?= sprintf(gettext("due on %s"),$db->covertUTCtoLocalAdvance("D M j, Y h:i a","",  $data[0]['duedate'],$_SESSION['timezone']));?>
                    </span>
                    <?php } elseif($data[0]['isactive'] == 52){ /* done */ ?>
                    <span class="px-2 py-1" style="background: lightgreen;  border-radius: 5px; vertical-align:super;font-size: smaller;">
                        <?= $status[$data[0]['isactive']];?>, <?= sprintf(gettext("completed on %s"),$db->covertUTCtoLocalAdvance("D M j, Y h:i a","",  $data[0]['modifiedon'],$_SESSION['timezone']));?>
                    </span>
                    <?php } ?>
                <?php } ?>
                <?php if (!$team->isComplete() && !$team->isIncomplete() && !$team->isInactive() && !$team->isPaused()){ ?>

                    <?php if ($data[0]['isactive'] < 51) { ?>

                    <span style="vertical-align:super;font-size: smaller;margin-left: 1rem;">
                        <button
                                class="btn btn-sm btn-outline-secondary" style="margin-bottom: 2px; padding: 0 3px!important; border-radius: 5px;"
                                onclick="taskStatusButtonAction('<?=$_COMPANY->encodeId($group->id())?>','<?=$_COMPANY->encodeId($data[0]['teamid'])?>','<?=$_COMPANY->encodeId($data[0]['taskid'])?>','<?=$_COMPANY->encodeId(51)?>')"
                        >
                            <?= sprintf(gettext('Mark as %s'),$status[51])?>
                        </button>
                    </span>
                    <?php } if ($data[0]['isactive'] < 52) { ?>
                    <span style="vertical-align:super;font-size: smaller;margin-left: 1rem;">
                        <button
                                class="btn btn-sm btn-outline-secondary" style="margin-bottom: 2px; padding: 0 3px!important; border-radius: 5px;"
                                onclick="taskStatusButtonAction('<?=$_COMPANY->encodeId($group->id())?>','<?=$_COMPANY->encodeId($data[0]['teamid'])?>','<?=$_COMPANY->encodeId($data[0]['taskid'])?>','<?=$_COMPANY->encodeId(52)?>')"
                        >
                            <?= sprintf(gettext('Mark as %s'),$status[52])?>
                        </button>
                    </span>
                    <?php } ?>
                <?php }?>

            </div>
            <?php } ?>

        </div>

        <?php if($data[0]['task_type'] == 'touchpoint') { ?>
        <div class="assigned-to pt-3">

                <?php
                $touchpointEventid = $data[0]['eventid'];
                if ($touchpointEventid) {
                    // view Dependency
                    $touchpoint_event = Event::GetEvent($touchpointEventid);
                    $url_chapter_channel_suffix = '';
                    include(__DIR__ . "/../teamevent/team_event_embedded.template.php");
                } else {
                ?>
                    <?php if ($_COMPANY->getAppCustomization()['teams']['team_events']['enabled'] && $touchPointTypeConfig['type'] != 'touchpointonly' && !$team->isComplete() && !$team->isIncomplete() && !$team->isInactive() && !$team->isPaused()){ ?>
                    <div class="container inner-background inner-background-next-event">
                        <div class="row">
                            <div class="col-md-12 p-0">
                                <div class=" upcoming-event-row">
                                    <p class="text-center"><?= gettext("This touch point does not have an associated event.")?></p>
                                    <?php if ($data[0]['isactive'] != 52) { ?>
                                    <p class="text-center"><button class="btn btn-link text-center" onclick="openNewTeamEventForm('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($data[0]['teamid']); ?>','<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId($data[0]['taskid']); ?>')"><?= gettext("Create Touch Point Event "); ?><i class="fa fa-plus-circle link-pointer" title="Create new event" aria-hidden="true"></i></button></p>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                <?php } ?>

            <?php if ($touchPointTypeConfig['show_copy_to_outlook'] && $data[0]['isactive'] < 51) {
                $touchpointEventid = $data[0]['eventid'];
                if (!$touchpointEventid) {
                    include(__DIR__ . "/../teamevent/init_copy_touchpoint_detail_to_outlook.template.php");
                }
            } ?>
        </div>
        <?php } ?>

        <div class="task-detail-container p-3 col-md-12">
        <?php if($data[0]['description']){ ?>
            <div class="task-detail pl-2 pt-3" id="post-inner">
                <p>
                    <?= $data[0]['description']; ?>
                </p>
            </div>
        <?php } ?>
        </div>

        <?= $team_task_model?->renderAttachmentsComponent('v23') ?? '' ?>

    <div class="clearfix"></div>
    <?php if(count($data)>1 && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
        <div class="row" style="text-align: center; border-top: 0.5px solid rgba(192, 192, 192, 0.49)"></div>
        <div class="assigned-to pt-3 col-md-12">
            <p>
                <strong>
            <?php
                if($data[0]['task_type'] == 'todo'){
                    echo gettext("Related touchpoints").":";
                } else if($data[0]['task_type'] == 'feedback') {
                    echo gettext("Related tasks").":";
                }
            ?>
                </strong>
            </p>
            <div class="col-md-12">
            
                <div class="table-responsive mb-3">
                    <table id="team_task_list"  summary="This table display the tasks list of a team">
                    <tbody>
                        <?php 
                            $i = 0;
                            foreach($data as $todo) {
                                if ($i == 0){
                                    $i++;
                                    continue;
                                }
                        ?>
                        <ul>
                            <li>
                            <button class="btn btn-link" onclick="getTaskDetailView('<?=$_COMPANY->encodeId($groupid);?>','<?=$_COMPANY->encodeId($todo['teamid']);?>','<?=$_COMPANY->encodeId($todo['taskid']);?>')" style="padding: 0!important;"><?= htmlspecialchars($todo['tasktitle']); ?></button>
                            </li>
                        </ul>
                        <?php
                                $i++;
                            }
                        ?>
                    </tbody>
                    </table>
              </div>
            </div>
        </div>

    <?php } ?>
    </div>
    <?php  if ($_COMPANY->getAppCustomization()['teams']['comments']) { ?>
        <div class="col-md-12 pt-4" style="text-align: center; border-top: 0.5px solid rgba(192, 192, 192, 0.49)"></div>
        <?php  
            /**
             * Comment Widget
             */
            include(__DIR__ . "/../../common/comments_and_likes/comments_container.template.php"); 
        ?>
    <?php } ?>
</div>

<script>
    function taskStatusButtonAction(groupid,teamid,taskid,updateStatus){
        Swal.fire({
          input: 'checkbox',
          title: '<?= gettext('Email notification confirmation') ?>',
          inputPlaceholder: '<?= gettext('Check the box to send email notification') ?>',
          confirmButtonText: '<?= gettext('Continue') ?>',
        }).then(function (result) {
          if (!result.isConfirmed) {
            return;
          }

          finaldata = {
            groupid,
            teamid,
            taskid,
            updateStatus,
            send_email_notification: result.value,
          };

        $.ajax({
            url: './ajax_talentpeak.php?updateTaskStatus=1',
            type: 'POST',
            data: finaldata,
            success: function(data) {
                if (data){
                    getTaskDetailView(groupid,teamid,taskid);
                } else {
                    swal.fire({title: 'Error',text:'Something went wrong. Please try again.'});
                }
                setTimeout(() => {
                    document.querySelector(".swal2-confirm").focus();
                }, 500)
            }
        });
        });
    }

    <?php
    $updateStatus = isset($updateStatus) ? intval($updateStatus) : -1;
    if ($updateStatus> 0 && in_array($updateStatus, [51,52]) && (!$team->isPaused() && !$team->isIncomplete())) {
        $updateStatusMap = [51 => gettext('In Progress'), 52 => gettext('Done')];
        $updateStatusSuccessMessage = sprintf(gettext('Touch Point marked as "%s" sucessfully'),$updateStatusMap[$updateStatus]);
        $updateStatusAlreadyCompletedMessage = sprintf (gettext('\"%1$s\" is already marked as %2$s'), $data[0]['tasktitle'], $updateStatusMap[$updateStatus]);
        $updateStatusMessage = sprintf(gettext('Are you sure you want to mark \"%1$s\" as %2$s?'), $data[0]['tasktitle'], $updateStatusMap[$updateStatus]);

    ?>

        (async () => {
            if (<?= ($updateStatus == $data[0]['isactive']) ? 1 : 0 ?>) {
                swal.fire({
                    text: "<?= $updateStatusAlreadyCompletedMessage ?>",
                    title: 'Alert'
                });
                return;
            }
				const { value: markAsComplete } = await Swal.fire({
                    html: '<?= $updateStatusMessage ?>',
                    confirmButtonText: '<?= gettext("Yes")?>',
                    allowOutsideClick: false,
                    showCancelButton: true,
                    cancelButtonText: '<?= gettext("No")?>'
                })

            if (typeof markAsComplete !== 'undefined' ) {
                $.ajax({
                    url: 'ajax_talentpeak.php?updateTaskStatus=1',
                    type: 'POST',
                    data: {groupid: '<?= $_COMPANY->encodeId($groupid); ?>', teamid: '<?= $_COMPANY->encodeId($teamid); ?>', taskid: '<?= $_COMPANY->encodeId($taskid); ?>', updateStatus: '<?= $_COMPANY->encodeId(52); ?>'},
                    success: function(data) {
                    if (data){
                        swal.fire({
                            text: "<?= gettext('Touch Point marked as done sucessfully'); ?>",
                            title: '<?= gettext("Success")?>'
                        });
                        getTaskDetailView('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($teamid); ?>','<?= $_COMPANY->encodeId($taskid); ?>');
                    } else {
                        swal.fire({title: 'Error',text:'Something went wrong. Please try again.'});
                    }
    
                    }
                });
            }
        })();
    <?php } ?>

    $(document).ready(function () {
        $('.touch-point-event').on('keypress', function (event) {
            if (event.which === 13) {
                $(this).click();
            }
        });
    });
</script>