<?php
    // Some groups require the user to select atleast one or exactly one chapter.
    // Here we are going to check if the user is member of such a group (that has chapter selection requirements)
    // and if the user has selected a chapter. If the validation fails the user will be requested to update membership
    // and choose a chapter. Otherwise we will add the group to chapter_validation_done list.
    // If during the course of the session the user joins/rejoins the getFollowUnfollowGroup removes the groupid from
    // the chapter_validation_done list.
    $chapterNeedsToJoin = null;
    if(!in_array($groupid, ($_SESSION['chapter_validation_done'] ?? array())) && $groupid && $group && Group::GetChapterList($groupid)) {
        if ($_USER->isGroupMember($groupid)){
            $selectedChapters = $_USER->getFollowedGroupChapterAsCSV($groupid) ?? '';
            if (!$selectedChapters && $group->val('chapter_assign_type') == 'by_user_atleast_one') {
                $chapterNeedsToJoin = sprintf(gettext('You need to join one or more %1$s.'), $_COMPANY->getAppCustomization()['chapter']['name-short']);
            } elseif (!$selectedChapters && $group->val('chapter_assign_type') == 'by_user_exactly_one') {
                $chapterNeedsToJoin = sprintf(gettext('You need to join a %1$s.'),$_COMPANY->getAppCustomization()['chapter']['name-short']);
            } else {
                $_SESSION['chapter_validation_done'][] = (int)$groupid; // Chapter validation was done for this group.
            }
        } else {
            $_SESSION['chapter_validation_done'][] = (int)$groupid; // Chapter validation was done for this group.
        }
    }
?>

<ul role ="none" id="membership_action_btns" class="navbar-nav ml-auto pull-right navbar-right" >
    <?php if ($_USER->isGroupMember($groupid) && (( $_ZONE->val('app_type') !== 'talentpeak' &&  $_ZONE->val('app_type') !== 'peoplehero') || !$group->isTeamsModuleEnabled())) { ?>
    <li role ="none" class="innerMenuMembership">
    <?php
        $call_method_parameters = array(
            $enc_groupid,
            2
        );
        $call_other_method = base64_url_encode(json_encode(
            array(
                "method"=>"getFollowChapterChannel",
                "parameters"=>$call_method_parameters
            )
        )); // base64_encode for prevent js parsing error
        ?>
        <button
            id="join" type="button" data-confirm-nobtn="<?= gettext("No");?>" data-confirm-yesbtn="<?= gettext("Yes");?>"

            <?php if(Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_LEAVE_BEFORE'], $groupid) && empty($chapters) && empty($channels) && !$group->isTeamsModuleEnabled()){ ?>
                onclick="loadDisclaimerByHook('<?=$_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_LEAVE_BEFORE'])?>','<?=$_COMPANY->encodeId($groupid)?>', 0, '<?=$call_other_method?>')"

            <?php } else {?>

                onclick="getFollowChapterChannel('<?= $enc_groupid; ?>',2)"           

            <?php } ?>    
            class="button-leave btn-info btn navbar-btn group-join-leave <?= (empty($chapters) && empty($channels) && !$group->isTeamsModuleEnabled()) ? 'confirm' : '' ?>"
            title="<?php if((empty($chapters) && empty($channels) && !$group->isTeamsModuleEnabled())) {  echo sprintf(gettext('Are you sure you want to leave this %s'),$_COMPANY->getAppCustomization()['group']['name-short']);  } ?>"        
        >
            <?= (empty($chapters) && empty($channels)) && !$group->isTeamsModuleEnabled() ? gettext('Leave') : gettext('Update&nbsp;Membership'); ?>
        </button>
    </li>

    <?php } elseif ($group->val('group_type') == Group::GROUP_TYPE_OPEN_MEMBERSHIP && !$_USER->isGroupMember($groupid) && $_USER->isAllowedToJoinGroup($groupid) && (($_ZONE->val('app_type') !== 'talentpeak' &&  $_ZONE->val('app_type') !== 'peoplehero') || !$group->isTeamsModuleEnabled())) { ?>

    <li class="innerMenuMembership">
        <?php
        if (empty($chapters) && empty($channels)) {
            $className = "confirm";
            $confirmMessage = sprintf(gettext('Are you sure you want to join this %s'),$_COMPANY->getAppCustomization()['group']['name-short']);

        } else if (!empty($chapters)) {
            $className = "confirm";
            if ($group->val('chapter_assign_type') == 'auto'){ // Auto Assignment
                $confirmMessage = sprintf(gettext('Are you sure you want to join this %1$s? If you continue you will join the %1$s and a %2$s will be assigned to you!'),$_COMPANY->getAppCustomization()['group']['name-short'],$_COMPANY->getAppCustomization()['chapter']['name-short']) ;
            } else {
                $className = "";
                $confirmMessage = '';
            }
        } else {
            $className = "";
            $confirmMessage = '';
        }
        ?>

        <?php if ($group->val('join_group_anonymously') && $_COMPANY->getAppCustomization()['group']['allow_anonymous_join']) { ?>

            <?php
            $call_method_parameters = array(
                $enc_groupid,
                1,
                addslashes(sprintf(gettext('Do you want to join this %1s anonymously?'), $_COMPANY->getAppCustomization()['group']['name-short'])),
                addslashes(gettext('Yes, I want to join anonymously')),
                addslashes(gettext('Continue')),
                addslashes(sprintf(gettext('<span>Please be mindful that this does not guarantee your full anonymity. %1s would still be able to technically identify you if need be.</span>'), '<br>'. $_COMPANY->val('companyname')))
            );

            $call_other_method = base64_url_encode(json_encode(
                array (
                    "method" => "getFollowChapterChannelAnonymously",
                    "parameters" => $call_method_parameters
                )
            )); // base64_url_encode for prevent js parsing error
            ?>

        <button id="joingroup" type="button" class="button-manage btn-info btn navbar-btn group-join-leave"

            <?php if(Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_JOIN_BEFORE'], $groupid)){ ?>
            onclick="loadDisclaimerByHook('<?=$_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_JOIN_BEFORE'])?>','<?=$_COMPANY->encodeId($groupid)?>', 0, '<?=$call_other_method?>')"

            <?php } else {?>
            onclick="getFollowChapterChannelAnonymously('<?=implode("','", $call_method_parameters)?>')"

            <?php } ?>
        >
            <?= (($_ZONE->val('app_type') !== 'talentpeak' && $_ZONE->val('app_type') !== 'peoplehero') || !$group->isTeamsModuleEnabled()) ? gettext("Join") : gettext("Request&nbsp;to&nbsp;Join") ?>
        </button>

    <?php }elseif($_USER->isAllowedToJoinGroup($groupid)){?>
        <?php
        $call_method_parameters = array(
            $enc_groupid,
            1
        );

        $call_other_method = base64_url_encode(json_encode(
            array(
                "method"=>"getFollowChapterChannel",
                "parameters"=>$call_method_parameters
            )
        )); // base64_encode for prevent js parsing error
        ?>
        <button id="join" class="button-manage btn-info btn navbar-btn group-join-leave <?=$className?>"  type="button" data-confirm-nobtn="<?= gettext("No");?>" data-confirm-yesbtn="<?= gettext("Yes");?>" title="<?= sprintf(gettext('Are you sure you want to join this %s'),$_COMPANY->getAppCustomization()['group']['name-short']); ?>"

            <?php if(Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_JOIN_BEFORE'], $groupid)){ ?>
                onclick="loadDisclaimerByHook('<?=$_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_JOIN_BEFORE'])?>','<?=$_COMPANY->encodeId($groupid)?>', 0,'<?=$call_other_method?>')"
                title="<?= $confirmMessage; ?>"
            <?php } else { ?>
                onclick="getFollowChapterChannel('<?=implode("','", $call_method_parameters)?>')"   title="<?= $confirmMessage; ?>"

            <?php } ?>
        >
            <?= (($_ZONE->val('app_type') !== 'talentpeak' && $_ZONE->val('app_type') !== 'peoplehero') || !$group->isTeamsModuleEnabled()) ? gettext("Join") : gettext("Request&nbsp;to&nbsp;Join") ?>
        </button>

    <?php } ?>

    </li>

    <?php } elseif($group->val('group_type') == Group::GROUP_TYPE_REQUEST_TO_JOIN && !$_USER->isGroupMember($groupid) && (($_ZONE->val('app_type') !== 'talentpeak' && $_ZONE->val('app_type') !== 'peoplehero') || !$group->isTeamsModuleEnabled())) { ?>

    <?php if (Team::GetRequestDetail($groupid,0)){ ?>
    <li class="innerMenuMembership">

        <button
            id="join" type="button"
            onclick="requestGroupMembership('<?= $enc_groupid; ?>')"
            class="button-leave btn-info btn navbar-btn group-join-leave confirm"
            title="<?= gettext('Are you sure you want to cancel join request?'); ?>"
        >
            <?=  gettext('Cancel&nbsp;Join&nbsp;Request'); ?>
        </button>

    </li>
    <?php } elseif($_USER->isAllowedToJoinGroup($groupid)){ ?>

    <?php
    $call_method_parameters = array(
        $enc_groupid
    );

    $call_other_method = base64_url_encode(json_encode(
        array(
            "method"=>"requestGroupMembership",
            "parameters"=>$call_method_parameters
        )
    )); // base64_encode for prevent js parsing error
    ?>

    <li class="innerMenuMembership">

        <button id="join" type="button " class="button-manage btn-info btn navbar-btn group-join-leave confirm" title="<?= gettext("Are you sure you want to send join request?");?>"
            <?php if (Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_JOIN_BEFORE'], $groupid)){ ?>
                onclick="loadDisclaimerByHook('<?=$_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['GROUP_JOIN_BEFORE'])?>','<?=$_COMPANY->encodeId($groupid)?>', 0,'<?=$call_other_method?>')"
            <?php } else { ?>
                onclick="requestGroupMembership('<?=implode("','", $call_method_parameters)?>')"

            <?php } ?>
            >
                <?= gettext("Request&nbsp;to&nbsp;Join"); ?>
            </button>

    </li>
    <?php }?>
    <?php } elseif((($_ZONE->val('app_type') === 'talentpeak' || $_ZONE->val('app_type') === 'peoplehero') && $group->isTeamsModuleEnabled()) && ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV'] && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES'])){
            $requestedRoleIds = array_column(Team::GetUserJoinRequests($groupid,0,0),'roleid');
    ?>
    <li class="innerMenuMembership">
        <?php if (!empty($requestedRoleIds)){ ?>

        <button
            id="reamRoleRequest"
            class="button-leave btn-info btn navbar-btn group-join-leave text-nowrap"
            onclick="manageJoinRequests('<?= $_COMPANY->encodeId($groupid); ?>')"
            type="button"
        >
            <?= gettext('Manage Registration'); ?>
        </button>

        <?php } elseif($_USER->isAllowedToJoinGroup($groupid)) { ?>

        <button
            id="reamRoleRequest"
            class="button-manage btn-info btn navbar-btn group-join-leave "
            onclick="manageJoinRequests('<?= $_COMPANY->encodeId($groupid); ?>')"
            type="button"
        >
            <?= gettext('Register'); ?>
        </button>

    <?php } ?>
    </li>
    <?php } ?>
</ul>
<script>

<?php if ($_COMPANY->getAppCustomization()['surveys']['enabled'] && !$chapterNeedsToJoin) { ?>
        // Do not attempt to load survey if the user is still in join_leave modal
        var hash = window.location.hash.substr(1);
        if(hash != 'join_leave') {
            loadSurveyModal("<?= $enc_groupid ?>");
        }
<?php } ?>

<?php if ($chapterNeedsToJoin){ ?>
    swal.fire({title: "<?= gettext('Action pending'); ?>",text:"<?= $chapterNeedsToJoin;?>"}).then(function(result) {
        
        getFollowChapterChannel("<?=$enc_groupid;?>",1);
    });
 <?php } ?>
 
setTimeout(() => {
    $(".swal2-confirm").focus();
}, 100)
</script>