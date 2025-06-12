
  <?php if ($title) { ?>
  <div class="col-12">
    <div class="col-md-12 impact2">
      <span><?= $title ?></span>
	</div>
  </div>
  <?php } ?>


<?php if ($page == 1 && empty($availabeTeams)) {  ?>

    <div class="col-12 text-center p-5">
        <p>
            <?= isset($emptyMessage) ? $emptyMessage : sprintf(gettext('There are no active %s right now. Check back soon for updates!'), Team::GetTeamCustomMetaName($group->getTeamProgramType(),1)); ?>
        </p>
    </div>
    <script>
        $("#loadeMoreDiscoverCircle").hide();
    </script>
<?php exit(); } ?>

<?php 
    $requestedRoleIds = array_column(Team::GetUserJoinRequests($groupid,0,0),'roleid');
    foreach($availabeTeams as $team){
    $circleRolesCapacity = Team::GetCircleRolesCapacity($groupid,$team->val('teamid'));
?>
        <div class="col-12 col-sm-6 m-0 p-3 discover_circle_card_list">
            <div class="card m-0 pb-5 h-100 w-100">
                <div class="mb-4">
                    <h2 class="card-title mb-0"><button type="button" onclick="getTeamDetail('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($team->val('teamid')); ?>',0,1)" class="btn btn-link"><?=  htmlspecialchars($team->val('team_name')); ?></button></h2>
                </div>

                <div style="text-align: center; border-top: 0.5px solid rgba(192, 192, 192, 0.49)"></div>

                <?php
                    $total_roles = count($allRoles);
                    $isTeamMember = $team->isTeamMember($_USER->id());
                    foreach($allRoles as $k => $role){
                        $members = $team->getTeamMembers($role['roleid']);
                        $allowedRoleCapacity = $circleRolesCapacity[$role['roleid']]['circle_role_max_capacity'];
                        $availableRoleCapacity = ($allowedRoleCapacity - count($members));
                        $isRoleJoined = $team->isTeamMember($_USER->id(), $role['roleid']);
                        $canJoinRole = Team::CanJoinARoleInTeam($groupid,$_USER->id(),$role['roleid']);
                        $border_style = ($total_roles == $k+1) ? 'border-top: 0.5px solid rgb(192, 192, 192, 0.49)' : 'border-top: 1px dashed rgb(192, 192, 192)';
                        $isRequestAllowd  = true;
                        $guardRails = json_decode($role['restrictions'],true);
                        if (!empty($guardRails)){
                            $isRequestAllowd = Team::CheckRequestRoleJoinAllowed($_USER->id(), $guardRails);
                        }
                ?>

                        <div class="row mx-0 my-3 px-1" >
                            <div class="col-5">
                                <h6 class="d-flex align-items-center mb-0">
                                    <?= $role['type'];?>
                                </h6>
                            </div>
                            <div class="col-7 text-right">
                                <small>
                                <?php if($role['sys_team_role_type'] == 2 && !empty($members)){ ?>
                                    <?= User::BuildProfilePictureImgTag($members[0]['firstname'], $members[0]['lastname'], $members[0]['picture'], 'memberpicture_small','profile picture',$members[0]['userid'], 'profile_full')?>
                                    <?= ($members[0]['firstname']??'Deleted').' '.$members[0]['lastname'];?>
                                    <?= $members[0]['roletitle'] ? ' ('. htmlspecialchars($members[0]['roletitle']) .')' : '' ?>

                                <?php } else { ?>
                                    <?php if($isRoleJoined ){ ?>
                                        <span class=""><i class="fa fa-check" aria-hidden="true"></i> <?= gettext('You Joined')?></span>
                                    <?php } elseif($isTeamMember){ ?>
                                        <button type="button" class="btn-link btn-no-style gray"  data-toggle="popover" data-trigger="hover focus" data-html="true" data-content="<?= sprintf(gettext('You are already part of this %1$s %2$s'), $team->val('team_name'), Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>" style="padding:0px !important; cursor: no-drop !important;"><i class="fa fa-plus-circle gray"  aria-hidden="true"></i> <?= gettext("Join Circle");?></button>

                                    <?php }elseif(!$canJoinRole){ ?>
                                        <button type="button" class="btn-link btn-no-style gray"  data-toggle="popover" data-trigger="hover focus" data-html="true" data-content="<?= sprintf(gettext("You have reached the maximum number of %s you can participate in with this role."),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1))?>" style="padding:0px !important; cursor: no-drop !important;"><i class="fa fa-plus-circle gray"  aria-hidden="true"></i> <?= gettext("Join Circle");?></button>
                                    <?php }elseif($availableRoleCapacity>0){ ?>
                                            <?php if(!empty($role['registration_end_date']) && $role['registration_end_date'] < date('Y-m-d')){ ?>
                                                <button aria-label="<?= sprintf(gettext('Join %1$s %2$s'), $team->val('team_name'), Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>" type="button" class="btn-link btn-no-style gray" data-toggle="popover" data-trigger="hover focus" data-html="true" data-content="<?= gettext("Registration is now closed, and we are no longer accepting new requests for this role.")?>" style="padding:0px !important; cursor: no-drop !important;"><i class="fa fa-plus-circle gray"  aria-hidden="true"></i> <?= gettext("Join Circle");?></button>
                                            <?php }elseif($isRequestAllowd){ ?>
                                                <button type="button" class="btn-link btn-no-style" onclick="getTeamDetail('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($team->val('teamid')); ?>',0,1)"  style="padding:0px !important;"><i class="fa fa-plus-circle" aria-hidden="true"></i> <?= gettext("Join Circle");?></button>
                                            <?php } else { ?>

                                                <button aria-label="<?= sprintf(gettext('Join %1$s %2$s'), $team->val('team_name'), Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>" type="button" class="btn-link btn-no-style gray" data-toggle="popover" data-trigger="hover focus" data-html="true" data-content="<?= gettext("You do not meet the role registration restrictions criteria.")?>" style="padding:0px !important; cursor: no-drop !important;"><i class="fa fa-plus-circle gray"  aria-hidden="true"></i> <?= gettext("Join Circle");?></button>

                                            <?php } ?>
                                  
                                    <?php } else{ ?>
                                        <button aria-label="<?= sprintf(gettext('Join %1$s %2$s'), $team->val('team_name'), Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>" type="button" class="btn-link btn-no-style gray" data-toggle="popover" data-trigger="hover focus" data-html="true" data-content="<?= gettext("All roles are filled")?>" style="padding:0px !important; cursor: no-drop !important;"><i class="fa fa-plus-circle gray"  aria-hidden="true"></i> <?= gettext("Join Circle");?></button>
                                    <?php } ?>
                                <?php } ?>
                                </small>
                            </div>
                        <?php if ($role['sys_team_role_type'] != 2){ ?>
                            <div class="col-12 text-right ml-2">
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

            </div>
        </div>
<?php } ?>

<?php if ($show_more) { // End fragment with span month element to pass next month ?>
    <span style="display: none;">_l_=<?=$show_more?></span>
<?php } ?>

<script>
    $(document).ready(function(){
        $(function () {
			$('[data-toggle="popover"]').popover({html:true, placement: "top",sanitize : false,container: 'body'});
		})
        <?php if ($show_more) { ?>
            $('#loadeMoreDiscoverCircle').show();
        <?php } else { ?>
            $('#loadeMoreDiscoverCircle').hide();
        <?php } ?>
	});
</script>


<script>
	$(document).ready(function(){
		// Script for auto loading home feeds if filtered feeds count is less then MAX_HOMEPAGE_FEED_ITERATOR_ITEMS
		let circle_page_number = parseInt(<?= $page; ?>);
		let circle_suggestion_count = parseInt(<?= count($availabeTeams); ?>);
		let page_rows_limit = parseInt(<?=$per_page; ?>);
	    let show_more = parseInt(<?= $show_more ? 1 : 0; ?>);
		if (show_more){
			if (circle_page_number == 1){
				
				localStorage.setItem("circle_suggestion_count", circle_suggestion_count);
				if (circle_suggestion_count < page_rows_limit) {
					loadMoreDiscoverCircles('<?= $_COMPANY->encodeId($groupid); ?>')
				}
			} else {
				
				let old_circle_suggestion_count = parseInt(localStorage.getItem("circle_suggestion_count"));
				let new_circle_suggestion_count  = circle_suggestion_count+old_circle_suggestion_count;
				localStorage.setItem("circle_suggestion_count", new_circle_suggestion_count);
                if (new_circle_suggestion_count < page_rows_limit){
				    loadMoreDiscoverCircles('<?= $_COMPANY->encodeId($groupid); ?>');
                }
			}
		}
	});
//On Enter Key...
    $(function(){ 
        $("#add_more_attributes_btn").keypress(function (e) {
            if (e.keyCode == 13) {
                $(this).trigger("click");
            }
        });
    });
</script>
