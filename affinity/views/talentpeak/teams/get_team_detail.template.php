<style>
    .nav-tabs {
        border:none !important;
    }
    .nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active{
        border-bottom: 2px solid #0077b5 !important;
    }
    .nav-tabs .nav-link{
        border:none !important;
        margin: 0 3px 0 0;
    }   
    .active.small-tab, .small-tab {
        font-size: 11px !important;
        font-weight: 200 !important;
    }
    .nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active.small-tab {
        border-bottom: 0.5px solid gray !important;
    }
</style> 
 <div class="container inner-background">
        <div class="row row-no-gutters w-100 mx-0">
            <div class="col-md-12">
                <div class="col-10 col-md-10 col-xs-12 float-left float-md-left">
                    <div class="inner-page-title">
                    <h3><?= $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['ADMIN_LED'] ? htmlspecialchars($teamObj->val('team_name')) : sprintf(gettext("%s Detail"),htmlspecialchars($teamObj->val('team_name')));?>
                            <?php
                            $sup1 = '';
                            if ($teamObj->val('isactive')==Team::STATUS_COMPLETE) {
                                $sup1 = ' <sup style="font-size:small; color: darkgreen;">['.gettext("Complete").']</sup>';
                            } elseif ($teamObj->val('isactive')==Team::STATUS_INCOMPLETE) {
                                $sup1 = ' <sup style="font-size:small; color: green;">[' . gettext("Incomplete") . ']</sup>';
                            } elseif ($teamObj->val('isactive')==Team::STATUS_PAUSED) {
                                $sup1 = ' <sup style="font-size:small; color: red;">[' . gettext("Paused") . ']</sup>';
                            }
                            echo $sup1;

                            $userDataArr = Arr::SearchColumnReturnRow($teamMembers, $_USER->id(),'userid');
                            $mySysRecordIsMentor = $userDataArr ? ($userDataArr['sys_team_role_type'] == 2) : false;
                            if (
                                    $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['ADMIN_LED'] &&
                                    !in_array($teamObj->val('isactive'), [Team::STATUS_COMPLETE, Team::STATUS_INCOMPLETE]) &&
                                    !($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES'] && !$mySysRecordIsMentor && !$manage_section) // Do not show edit to circle members but show to Program Leads when editing from manage
                            ){ ?>
                                <sup style="font-size: small;">
                                <i title="<?= sprintf(gettext("Edit %s"),$teamObj->val('team_name'));?>" aria-label="<?= sprintf(gettext("Edit %s"),$teamObj->val('team_name'));?>" tabindex="0" class="fa fas fa-edit pl-2 open-new-team-modal" onclick="openNewTeamModal('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($teamObj->val('teamid')); ?>','detail')"></i>
                                </sup>
                            <?php } ?>
                        </h3>
                        <?php if($manage_section){ ?>
                        <small class="mt-3" style="color:red;">
                            <br>
                            <?= sprintf(gettext('You are managing %1$s %2$s as a %3$s Leader'),$teamObj->val('team_name'),Team::GetTeamCustomMetaName($group->getTeamProgramType()), $_COMPANY->getAppCustomization()['group']['name-short'])?>
                        </small>
                        <?php } ?>
                    </div>
                </div>
                <div class="col-2 col-md-2 text-right mt-3 float-left float-md-left">
                    <?php if(in_array($teamObj->val('isactive'),[Team::STATUS_ACTIVE, Team::STATUS_PAUSED]) && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['ADMIN_LED']){ ?>
                    <button id="doutdBtn" class="btn-no-style dropdown-toggle fa fa-ellipsis-v col-doutd" title="<?= gettext("Action button dropdown")?>" aria-label="<?= gettext("button control");?>" data-toggle="dropdown"></button>
                    <ul class="dropdown-menu dropdown-menu-right dropdown-action-menu-list" id="dynamicActionButton<?= $_COMPANY->encodeId($teamObj->val('teamid')); ?>" style="width: 250px; cursor: pointer;">
                    <?php
                        $team  = array('teamid'=>$teamObj->id(),'isactive'=>$teamObj->val('isactive'), 'createdby' => $teamObj->val('createdby'));
                        $reloadAfterAction = 1;
                        $teamMemberAction = 1;
                    ?>
                        <?php include(__DIR__ . "/team_action_button.template.php"); ?>
                    </ul>
                    <?php } ?>
                </div>
            
            </div>

            <div class="col-12">
                <div id="post-inner">
                    <?= $teamObj->val('team_description'); ?>
                </div>
            </div>
            
            <?php if(!empty($hashTagHandles)){ ?>
            <div class="col-12 mb-3">
                <?php foreach($hashTagHandles as $hashtag){ ?>
                    <a class="p-1" href="<?= Team::GetCircleHashTagUrl($hashtag['hashtagid']); ?>">#<?= $hashtag['handle'];?></a>
                <?php } ?>
            </div>

            <?php } ?>
            <?php if($group->getProgramDisclaimer()) { ?>
                <div class="col-md-12 pl-3 pr-3 mb-3 dark-background" style="font-size:14px;">
                    <div id="post-inner" style="color:#fff;">
                        <?= $group->getProgramDisclaimer(); ?>
                    </div>
                </div>
            <?php } ?>
            <div class="col-md-12 pl-3 pr-3 mb-3" style="border:1px solid rgba(212, 212, 212, 0.534)">
            <?php if($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
                <div class="row p-2 mt-0 " style="background: rgba(228, 228, 228, 0.253) !important;">
                <?php if(!empty($teamMembers)){ ?>
                    <?php usort($teamMembers, function($a,$b) { return strcmp($b['roletitle'], $a['roletitle']); }); ?>

                    <?php foreach($teamMembers as $member){
                        $encMemberUserID = $_COMPANY->encodeId($member['userid']);
                        $statsTitle = gettext("Total Assigned Action Items");
                        [$statsTotalRecoreds, $statsTotalInProgress, $statsTotalCompleted, $statsTotalOverDues] =  $teamObj->getContentsProgressStats($group, $member['userid'],'todo');
                    ?>
                        <div class="col-4 text-left p-2 ">
                            <div class="card w-100">
                                <button class="btn w-100" onclick="getProfileDetailedView(this,{'userid': '<?= $encMemberUserID; ?>', profile_detail_level: 'profile_full'})">
                                    <div class="col-2 p-0">
                                        <?= User::BuildProfilePictureImgTag($member['firstname'], $member['lastname'], $member['picture'], 'memberpic2','Profile picture', $member['userid'], null) ?>
                                    </div>
                                    <div class="col-10 py-0 px-1 text-left ">
                                        <span class="col-12 p-0 m-0 ellipsis"><?= $member['firstname'].' '.$member['lastname']?></span>
                                        <span class="col-12 p-0 m-0 ellipsis small"><?= $member['role']?> <?= $member['roletitle'] ? '('.htmlspecialchars($member['roletitle']).')' : '' ?></span>
                                        <span class="col-12 p-0 m-0 ellipsis small"><?= $member['email'] ? htmlspecialchars($member['email']) : '&nbsp;' ?></span>
                                    </div>
                                </button>
                               
                            <?php if (!in_array(TEAM::PROGRAM_TEAM_TAB['ACTION_ITEM'], $hiddenTabs) && $progressBarSetting['show_actionitem_progress_bar']){ ?>
                                <div class="col-12 m-0">
                                    <!-- <ul class="nav nav-tabs" id="myTab" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active small-tab" id="todo-stats-tab-<?= $encMemberUserID; ?>" data-toggle="tab" href="#totostats<?= $encMemberUserID; ?>" role="tab" aria-controls="totostats<?= $encMemberUserID; ?>" aria-selected="true">Action Items</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link small-tab" id="touchPoint-stats-tab-<?= $encMemberUserID; ?>" data-toggle="tab" href="#touchpointstats<?= $encMemberUserID; ?>" role="tab" aria-controls="touchpointstats<?= $encMemberUserID; ?>" aria-selected="false">Touch Points</a>
                                        </li>
                                        
                                    </ul> -->
                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="totostats<?= $encMemberUserID; ?>" role="tabpanel" aria-labelledby="todo-stats-tab-<?= $encMemberUserID; ?>">
                                            <?php include(__DIR__ . "/../common/assinged_task_progress_stats.templagte.php"); ?>
                                        </div>
                                        <!-- <div class="tab-pane fade" id="touchpointstats<?= $encMemberUserID; ?>" role="tabpanel" aria-labelledby="touchPoint-stats-tab-<?= $encMemberUserID; ?>">
                                            
                                        
                                        </div> -->
                                    </div>
                                </div>
                            <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                    <?php } else { ?>
                        <div class="col-md-4 text-left"  tabindex="0">
                            <p><?= gettext("No team member assigned");?></p>
                        </div>
                    <?php } ?>
                </div>
                <?php } ?>
               
                    <div class="col-md-12 mt-4">
                        <ul class="nav nav-tabs" role="tablist">
                        <?php if (!in_array(TEAM::PROGRAM_TEAM_TAB['ACTION_ITEM'], $hiddenTabs)){ ?>
                            <li role="none" class="nav-item-todo"><a href="javascript:void(0);" id="todo_tab" tabindex="0" role="tab" aria-selected="true" class="nav-link active" data-toggle="tab" onclick="getTeamsTodoList('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($teamObj->val('teamid')); ?>');" ><?= gettext("Action Items");?></a></li>
                        <?php } ?>
                        <?php if(!in_array(TEAM::PROGRAM_TEAM_TAB['TOUCH_POINT'], $hiddenTabs)){ ?>
                            <li role="none" class="nav-item-touchpoint"><a href="javascript:void(0);" data-toggle="tab" role="tab" aria-selected="false" id="team_touch_points"  tabindex="0" class="nav-link" onclick="getTeamsTouchPointsList('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($teamObj->val('teamid')); ?>');"><?= gettext("Touch Points");?></a></li>
                        <?php } ?>
                        <?php if(!in_array(TEAM::PROGRAM_TEAM_TAB['FEEDBACK'], $hiddenTabs)){ ?>
                            <li role="none" class="nav-item-feedback"><a href="javascript:void(0);" data-toggle="tab" id="feedback_tab" role="tab" aria-selected="false" tabindex="0" class="nav-link" onclick="getTeamsFeedback('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($teamObj->val('teamid')); ?>');"><?= gettext("Feedback");?></a></li>
                        <?php } ?>
                        <?php if(!in_array(TEAM::PROGRAM_TEAM_TAB['TEAM_MESSAGE'], $hiddenTabs) && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
                            <li role="none" class="nav-item-team-message"><a href="javascript:void(0);" data-toggle="tab" role="tab" aria-selected="false" id="team_message_tab"  tabindex="0" class="nav-link" onclick="getTeamsMessages('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($teamObj->val('teamid')); ?>');"><?= gettext("Messages");?></a></li>
                        <?php } ?>
                        </ul>
                    </div>
                    <div class=" col-md-12 tab-content" id="loadTeamData">
                    </div>
                
            </div>
            <?php if ($manage_section) { ?>
            <div class="col-md-12 text-center mb-3">
                <button class="btn btn-affinity btn-sm" onclick="getManageTeamsContainer('<?=$_COMPANY->encodeId($groupid)?>')"><?=sprintf(gettext("Back to %s list"),Team::GetTeamCustomMetaName($group->getTeamProgramType()))?></button>
            </div>
            <?php } ?>
        </div>
    </div>
<script>
 $(document).ready(function(){
    <?php if (!in_array(TEAM::PROGRAM_TEAM_TAB['ACTION_ITEM'], $hiddenTabs) || !in_array(TEAM::PROGRAM_TEAM_TAB['TOUCH_POINT'], $hiddenTabs) || !in_array(TEAM::PROGRAM_TEAM_TAB['FEEDBACK'], $hiddenTabs) || !in_array(TEAM::PROGRAM_TEAM_TAB['TEAM_MESSAGE'], $hiddenTabs)) { ?>
        <?php if ($activeTab == 'todo'){ ?>
            $("#todo_tab").trigger("click");
        <?php } elseif ($activeTab == 'touchpoint'){ ?>
            $("#team_touch_points").trigger("click");
        <?php  } elseif($activeTab == 'feedback') { ?>
            $("#feedback_tab").trigger("click");
        <?php  } elseif($activeTab == 'team_message') { ?>
            $("#team_message_tab").trigger("click");
        <?php } else { ?>
            <?php if (!in_array(TEAM::PROGRAM_TEAM_TAB['ACTION_ITEM'], $hiddenTabs)){ ?>
                $("#todo_tab").trigger("click");
            <?php } elseif (!in_array(TEAM::PROGRAM_TEAM_TAB['TOUCH_POINT'], $hiddenTabs)){ ?>
                $("#team_touch_points").trigger("click");
            <?php  } elseif(!in_array(TEAM::PROGRAM_TEAM_TAB['FEEDBACK'], $hiddenTabs)) { ?>
                $("#feedback_tab").trigger("click");
            <?php  } elseif(!in_array(TEAM::PROGRAM_TEAM_TAB['TEAM_MESSAGE'], $hiddenTabs)) { ?>
                $("#team_message_tab").trigger("click");
            <?php } ?>
        <?php } ?>
    <?php } else { ?>
        $("#loadTeamData").html("<div class='col-12 my-5 text-center'><?= gettext("The Action Items, Touchpoints, Feedback, and Message tabs are disabled from the configuration settings. Please contact your administrator to resolve this issue.")?></div>")
    <?php } ?>
 });
</script>

<script>
    $(function () {
        $('[data-toggle="popover"]').popover({html:true, placement: "top",sanitize : false,container: 'body'});
    })
</script>

<script>
    $(".confirm").popConfirm({content: ''});
    $('.initial').initial({
        charCount: 2,
        textColor: '#ffffff',
        color: window.tskp?.initial_bgcolor ?? null,
        seed: 0,
        height: 30,
        width: 30,
        fontSize: 15,
        fontWeight: 300,
        fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
        radius: 0
    });

$(function(){ 
    $(".open-new-team-modal").keypress(function (e) {
        if (e.keyCode == 13) {
            $(this).trigger("click");
        }
    });
});

$(function() {                       
        $(".nav-link").click(function() { 
        $('.nav-link').attr('tabindex', '-1');
        $(this).attr('tabindex', '0');    
        });
    });
  
    $('.nav-link').keydown(function(e) {  
        if (e.keyCode == 39) {       
            $(this).parent().next().find(".nav-link:last").focus();       
        }else if(e.keyCode == 37){       
            $(this).parent().prev().find(".nav-link:last").focus();  
        }
}); 
</script>