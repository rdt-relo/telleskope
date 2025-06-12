
 <div class="container inner-background">
        <div class="row row-no-gutters w-100 mx-0">
            <div class="col-md-12">
                <div class="col-md-10 col-xs-12">
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
                            ?>
                        </h3>
                    </div>
                </div>
                <div class="col-md-2 text-right mt-3"  >

                <button id="doutdBtn" class="dropdown-toggle  fa fa-ellipsis-v col-doutd btn-no-style" aria-label="<?= sprintf(gettext('%s more actions'),$teamObj->val('team_name')); ?>" data-toggle="dropdown"></button>
                    <ul class="dropdown-menu dropdown-menu-right" style="width: 250px; cursor: pointer;">
                        <li>
                            <a href="javascript:void(0)" tabindex="0" title="Edit" onclick="getShareableLink('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId($teamObj->id())?>',13)" ><i class="fa fa-share-square" aria-hidden="true"></i>&emsp;<?= gettext("Get Shareable Link");?></a>
                        </li>
                    </ul>
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

            <?php if($teamObj->isActive() && $group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']) { ?>
            <div class="col-md-12 py-3 mb-3 text-center" style="border-top:1px solid rgba(212, 212, 212, 0.534)" >
            <?php 
            $requestedRoleIds = array_column(Team::GetUserJoinRequests($groupid,0,0),'roleid');
            foreach($allRoles as $role) {
                if ($role['sys_team_role_type'] != '3') continue; // We will only show mentee type join buttons
                $members = $teamObj->getTeamMembers($role['roleid']);
                $allowedRoleCapacity = $circleRolesCapacity[$role['roleid']]['circle_role_max_capacity'];
                $availableRoleCapacity = ($allowedRoleCapacity - count($members));
                $isRoleJoined = $teamObj->isTeamMember($_USER->id(), $role['roleid']);
                $isTeamMember = $teamObj->isTeamMember($_USER->id());
                $canJoinRole = Team::CanJoinARoleInTeam($groupid,$_USER->id(),$role['roleid']); 

                $isRequestAllowd  = true;
                $guardRails = json_decode($role['restrictions'],true);
                if (!empty($guardRails)){
                    $isRequestAllowd = Team::CheckRequestRoleJoinAllowed($_USER->id(), $guardRails);
                }
            ?>
                <?php if ($isRoleJoined || $isTeamMember) { ?>
                    <button type="button" class="btn btn-affinity disabled" data-toggle="popover" data-trigger="hover focus" data-html="true" data-content="<?= sprintf(gettext("You are already joined this %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType()))?>" style="cursor: no-drop !important;"> <?= sprintf(gettext('Join %1$s as %2$s'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),$role['type']);?></button>
                <?php } elseif (!$canJoinRole) { ?>
                    <button type="button" class="btn btn-affinity disabled" data-toggle="popover" data-trigger="hover focus" data-html="true" data-content="<?= sprintf(gettext("You have reached the maximum number of %s you can participate in with this role."),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1))?>" style=" no-drop !important;"><?= sprintf(gettext('Join %1$s as %2$s'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),$role['type']);?></button>
                <?php } elseif ($isRequestAllowd && $availableRoleCapacity > 0) { ?>
                    <?php if(!empty($role['registration_end_date']) && $role['registration_end_date'] < date('Y-m-d')){ ?>
                        <button type="button" class="btn btn-affinity disabled" data-toggle="popover" data-trigger="hover focus" data-html="true" data-content="<?= sprintf(gettext("Registration is now closed, and we are no longer accepting new requests for this role."),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1))?>" style=" no-drop !important;"><?= sprintf(gettext('Join %1$s as %2$s'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),$role['type']);?></button>

                    <?php } else { ?>
                        <button type="button" class="btn btn-affinity confirm" onclick="proceedToJoinCircle('<?= $_COMPANY->encodeId($teamObj->val('teamid')); ?>', '<?= $_COMPANY->encodeId($role['roleid']); ?>',1)" data-toggle="popover" title="<?= gettext("Are you sure you want to join this circle?")?>" style=""><?= sprintf(gettext('Join %1$s as %2$s'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),$role['type']);?></button>
                    <?php } ?>
                <?php } else { ?>
                    <button type="button" class="btn btn-affinity disabled" data-toggle="popover" data-trigger="hover focus" data-html="true" data-content="<?= gettext("All slots are filled")?>" style="cursor: no-drop !important;"><?= sprintf(gettext('Join %1$s as %2$s'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),$role['type']);?></button>
                <?php } ?>
                &nbsp;
                <?php } ?>
            </div>
            <?php } ?>

    <?php 
    $hideContainer = 'block';
    if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES'] && $teamWorkflowSetting['hide_member_in_discover_tab']) { 
        $hideContainer = 'none';
    }
    ?>

            <div class="col-md-12 pl-3 pr-3 mb-3" style="display:<?= $hideContainer; ?>; border:1px solid rgba(212, 212, 212, 0.534);">
            <?php if($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
                <div class="row p-2 mt-0 " style="background: rgba(228, 228, 228, 0.253) !important;">
                <?php if(!empty($teamMembers)){ ?>
                    <?php foreach($teamMembers as $member){
                        $encMemberUserID = $_COMPANY->encodeId($member['userid']);
                    ?>
                        <div class="col-4 text-left p-2">
                            <button class="btn w-100 border-dark" onclick="getProfileDetailedView(this,{'userid': '<?= $encMemberUserID; ?>', profile_detail_level: 'profile_full'})">
                                <div class="col-2 p-0">
                                    <?= User::BuildProfilePictureImgTag($member['firstname'], $member['lastname'], $member['picture'], 'memberpic2','Profile picture', $member['userid'], null) ?>
                                </div>
                                <div class="col-10 py-0 px-1 text-left ">
                                    <span class="col-12 p-0 m-0 ellipsis"><?= $member['firstname'].' '.$member['lastname']?></span>
                                    <span class="col-12 p-0 m-0 ellipsis small"><?= $member['role']?> <?= $member['roletitle'] ? '('.htmlspecialchars($member['roletitle']).')' : '' ?></span>
                                    <span class="col-12 p-0 m-0 ellipsis small"><?= $member['email'] ? htmlspecialchars($member['email']) : '&nbsp;' ?></span>
                                </div>
                            </button>
                        </div>
                    <?php } ?>
                    <?php } else { ?>
                        <div class="col-md-4 text-left"  tabindex="0">
                            <p><?= gettext("No team member assigned");?></p>
                        </div>
                    <?php } ?>
                </div>
                <?php } ?>
                
            </div>
        </div>
    </div>

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
</script>