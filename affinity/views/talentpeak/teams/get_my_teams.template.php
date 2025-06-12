<style>
.card {
    background-color: #fff;
    color:#505050;
    border-radius: 10px;
    border: none;
    position: relative;
    margin-bottom: 25px;
    box-shadow: 0 0.46875rem 2.1875rem rgba(90,97,105,0.1), 0 0.9375rem 1.40625rem rgba(90,97,105,0.1), 0 0.25rem 0.53125rem rgba(90,97,105,0.12), 0 0.125rem 0.1875rem rgba(90,97,105,0.1);
}
.card:hover{
    background: linear-gradient(to left, #dbdbdb, #f4f7fc) !important;
}

</style>

<?php if ($listing_html ?? '') { ?>
    <?= $listing_html ?>
<?php } else { ?>
        <div class="row">
        <?php if(!empty($myTeams)){ 
            $status = array(
                '0'=>gettext('In-active'),
                '1'=>gettext('Active'),
                '2'=>gettext('Draft'),
                '100'=>gettext('Delete'),
                '110'=>gettext('Complete'),
                '109'=>gettext('Incomplete'),
                '108'=>gettext('Paused')
            );
        ?>
            <div class="col-12 text-center">
                <p><?= gettext("Click on a card to view your progress/milestones")?></p>
            </div>
        
            <?php foreach($myTeams as $team){ 

                if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']) {
                    $circleRolesCapacity = Team::GetCircleRolesCapacity($groupid,$team['teamid']);
                }

                $tm = Team::GetTeam($team['teamid']);
            ?>
            <div class="col-12 col-sm-6 m-0 p-3">
                
            <button class="btn btn-no-style h-100 w-100" style="padding: 0!important;" aria-label="Team <?=htmlspecialchars($team['team_name']);?>"
                <?php if ($team['isactive'] == Team::STATUS_INACTIVE) { ?>
                    style="cursor: not-allowed;"
                    title = "<?= sprintf(gettext('This %s has been disabled. Please contact your program co-ordinator'),Team::GetTeamCustomMetaName($group->getTeamProgramType())) ?>"
                <?php } elseif ($team['isactive'] == Team::STATUS_DRAFT) { ?>
                    style="cursor: not-allowed;"
                    title = "<?= sprintf(gettext('This %s is in the draft stage. Please check back soon'),Team::GetTeamCustomMetaName($group->getTeamProgramType())) ?>"
                <?php } else { ?>
                    style="cursor: pointer;"
                    onclick="getTeamDetail('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($team['teamid']); ?>')"
                <?php } ?>
            >
                <div class="card h-100 w-100 m-0 pb-5">
                    <div class="mb-4">
                        <h2 class="card-title mb-0"><?= htmlspecialchars($team['team_name']); ?></h2>
                        <p class="small">[ <?= $status[$team['isactive']]; ?> ]</p>
                    </div>

                    <div style="text-align: center; border-top: 0.5px solid rgba(192, 192, 192, 0.49)"></div>

                    <?php
                    $total_roles = count($allRoles);
                    foreach($allRoles as $k => $role){
                        $members = $tm->getTeamMembers((int)$role['roleid']);
                        $totalmembers = count($members);
                        if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES'] && $role['sys_team_role_type'] != 2) {
                            $allowedRoleCapacity = $circleRolesCapacity[$role['roleid']]['circle_role_max_capacity'];
                            $availableRoleCapacity = ($allowedRoleCapacity - $totalmembers);
                        }
                        $border_style = ($total_roles == $k+1) ? 'border-top: 0.5px solid rgb(192, 192, 192, 0.49)' : 'border-top: 1px dashed rgb(192, 192, 192)';
                    ?>
                                
                        <div class="row mx-0 my-3 px-1" >
                            <div class="col-5">
                                <h6 class="d-flex align-items-center mb-0">
                                    <?= $role['type'];?>
                                </h6>
                            </div>
                            <div class="col-7 text-right">
                                <small>
                            <?php if($totalmembers){
                                $index = 5;
                                if ($totalmembers < 5){
                                    $index = $totalmembers;
                                }
                                ?>
                                <?php for($i=0; $i<$index; $i++){ ?>
                                    <?= User::BuildProfilePictureImgTag($members[$i]['firstname'], $members[$i]['lastname'], $members[$i]['picture'], 'memberpicture_small', 'User Profile Picture', $members[$i]['userid'], null); ?>
                                <?php } ?>
                                <?php if($totalmembers >5){ ?>
                                        +<?= $totalmembers-5;?> <?= gettext("more");?>
                                <?php } ?>
                            <?php } else { ?>
                                    <?= gettext("Not assigned");?>
                            <?php } ?>
                                </small>
                            </div>

                                <?php if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']  && $role['sys_team_role_type'] != 2){ ?>
                            <div class="col-12 text-right">
                                <small>
                                    <?php if ($availableRoleCapacity == 0) { ?>
                                        <span class="px-2 py-1"><?= sprintf(gettext('%s spots available!'), $availableRoleCapacity);?></span>
                                    <?php } elseif ($availableRoleCapacity == 1) { ?>
                                        <span class="font-weight-bold px-2 py-1"><?= sprintf(gettext('%s spot available!'), $availableRoleCapacity);?></span>
                                    <?php } else { ?>
                                        <span class="font-weight-bold px-2 py-1"><?= sprintf(gettext('%s spots available!'), $availableRoleCapacity);?></span>
                                    <?php }  ?>
                                </small>
                            </div>
                            <?php } ?>
                        </div>

                        <div class="row mx-0 mx-0 px-0" style="text-align: center; <?=$border_style?>"></div>
                                
                    <?php } ?>
                    <div class="col-12 mt-3">
                    <?php if(!in_array(TEAM::PROGRAM_TEAM_TAB['ACTION_ITEM'], $hiddenTabs) && $progressBarSetting['show_actionitem_progress_bar']){ 
                            $statsTitle = gettext("Total Action Items");
                            [$statsTotalRecoreds, $statsTotalInProgress, $statsTotalCompleted, $statsTotalOverDues] =  $tm->getContentsProgressStats($group, 0,'todo');
                    ?>
                            <?php include(__DIR__ . "/../common/assinged_task_progress_stats.templagte.php"); ?>
                            <div class="row my-3 mx-0 px-0" style="text-align: center; border-top: 1px dashed rgb(192, 192, 192);"></div>
                            
                    <?php } ?>

                    <?php if(!in_array(TEAM::PROGRAM_TEAM_TAB['TOUCH_POINT'], $hiddenTabs) && $progressBarSetting['show_touchpoint_progress_bar']){ 
                           $statsTitle = gettext("Total Touch Points");
                           [$statsTotalRecoreds, $statsTotalInProgress, $statsTotalCompleted, $statsTotalOverDues] =  $tm->getContentsProgressStats($group, 0,'touchpoint');
                    ?>
                            <?php include(__DIR__ . "/../common/assinged_task_progress_stats.templagte.php"); ?>
                       
                    <?php } ?>
                    </div>
                </div>
                
            </button>
            

            </div>
        <?php } ?>
    <?php } else { ?>
        <div class="col-md-12 text-center">
            <?php if($chapterid || $showGlobalChapterOnly){ ?>
                <p class="p-5"><?= sprintf(gettext('No %1$s was found using the selected filter. Please update the filter to find the relevant %1$s.'),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?></p>
            <?php } else{ ?>
                <?php if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?>
                    <p class="p-4 font-weight-bold"><?= sprintf(gettext('Join a %1$s from the Discover %2$s tab or start a new %1$s.'),Team::GetTeamCustomMetaName($group->getTeamProgramType(),0), Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></p>
                <?php } else { ?>
                    <?php if(empty($joinRequests)){ ?>
                        <p class="p-4 font-weight-bold"><?= sprintf(gettext("You haven't registered for any %s roles yet"),Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>!</p>
                    <?php } else{ ?>
                        <?php if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER']){ ?>
                            <p class="p-4 font-weight-bold"><?= sprintf(gettext('To create %1$s, go to the Discover tab and review your recommended matches.'), Team::GetTeamCustomMetaName($group->getTeamProgramType(),1))?></p>
                        <?php } else { ?>
                            <p class="p-4 font-weight-bold"><?= gettext('Your request is being processed. Stay tuned for updates.<br/>You can manage your request by clicking the "Manage Registration" button.');?></p>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>
                
                <?php if(empty($joinRequests) && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?>
                    <div class="col-md-12 text-center pb-5">
                        <button
                            id="reamRoleRequest"
                            class="btn btn-affinity mobile-off ml-3 btn-link" 
                            onclick="manageJoinRequests('<?= $_COMPANY->encodeId($groupid); ?>')" 
                        >
                            <?= gettext('Register');?>
                        </button>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
    <?php } ?>  
<?php } ?>
    