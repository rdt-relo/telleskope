<!-- In Group Aboutus section -->

<!-- Print Group Mission -->
<div class="col-md-12">
    <div id="post-inner"><?= $group->val('aboutgroup'); ?></div>
</div>
    <?php if (count($leads) > 0) { ?>
        <!-- Print Group Leads -->
        <div class="row row-no-gutters">
        <div class="col-lg-12 col-sm-12 col-12 pb-4" id="jump_to_leaders_list">
            <hr/>
            <h2><?= sprintf(gettext('%s Leaders'),$_COMPANY->getAppCustomization()['group']['name']); ?></h2>
            <br/>
        </div>

        <div class="col-lg-12 col-sm-12 col-12">
            <?php for ($i = 0; $i < count($leads); $i++) {
                $encMemberUserID = $_COMPANY->encodeId($leads[$i]['userid']);
                ?>
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="member-card" role="button" tabindex="0"
                         onclick="getProfileDetailedView(this,{'userid':'<?= $encMemberUserID; ?>', profile_detail_level: 'profile_basic'})" onkeypress="getProfileDetailedView(this,{'userid':'<?= $encMemberUserID; ?>', profile_detail_level: 'profile_basic'})"
                    >
                        <?= User::BuildProfilePictureImgTag($leads[$i]['firstname'], $leads[$i]['lastname'], $leads[$i]['picture'],'memberpic','', $leads[$i]['userid'], null);?>
                        <p class="member_name"><?= $leads[$i]['firstname'] . ' ' . $leads[$i]['lastname'] . (!empty($leads[$i]['pronouns']) ? '<span style="font-size: small;"> ('.$leads[$i]['pronouns'].')</span>' : ''); ?></p>
                        <p class="member_jobtitle" style=" font-size:small;"><?= $types[$leads[$i]['grouplead_typeid']]['type'] ?>
                            <br>
                            <?= htmlspecialchars($leads[$i]['roletitle']) ?>
                        </p>
                    </div>
                </div>
            <?php } ?>
        </div>
        </div>
    <?php } ?>

    <?php if ($canViewContent && ($group->val('about_show_members') === "1" || ($group->val('about_show_members') === "2" && $_USER->isGroupMember($group->id())))) { ?>
        <!-- Print Group Members -->
       <div class="row">
         <div class="col-md-12" id="jump_to_members_list">
            <hr/>
            <h2><?= $_COMPANY->getAppCustomization()['group']['name'] ?> <?= $_COMPANY->getAppCustomization()['group']['memberlabel'].'s'; ?></h2>
            <br/>
        </div>
       </div>
        <?php
        $ergName = $_COMPANY->getAppCustomization()['group']['name'] .' '. $_COMPANY->getAppCustomization()['group']['memberlabel'].'s';
        $section = $_COMPANY->encodeId(1);
        $encSectionId = $_COMPANY->encodeId($group->val('groupid'));
        include __DIR__ . '/group_chapter_channel_members_list.template.php';
        ?>
    <?php } ?>
    <br>

