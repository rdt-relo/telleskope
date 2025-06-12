<!-- In chapter about us section -->
<!-- Print the Chapter name-->
    <div class="row">
        <div class="col-md-12">
            <div class="mt-3">
                <h2><?= htmlspecialchars($chapter['chaptername']); ?></h2>
            </div>
        </div>
        <!-- Print the Chapter mission-->
        <div class="col-md-12">
            <div id="post-inner" class="px-3"><?= $chapter['about']; ?></div>
        </div>
    </div>

    <!-- Print the Chapter  Leads if any-->
    <?php if (count($chapterLeads)) { ?>
        <!-- Print Chapter Leads -->
        <div class="row">
        <div class="col-md-12 pb-4" id="jump_to_leaders_list">
            <hr/>
            <h2><?=sprintf(gettext("%s Leaders"),$_COMPANY->getAppCustomization()['chapter']['name-short']) ;?></h2>
            <br/>
        </div>
        <div class="col-lg-12 col-sm-12 col-12">
            <?php for ($i = 0; $i < count($chapterLeads); $i++) {
                $encMemberUserID = $_COMPANY->encodeId($chapterLeads[$i]['userid']);
                ?>
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="member-card" role="button" tabindex="0"
                        onclick="getProfileDetailedView(this,{'userid':'<?= $encMemberUserID; ?>', profile_detail_level: 'profile_basic'})"
                        onkeypress="getProfileDetailedView(this,{'userid':'<?= $encMemberUserID; ?>', profile_detail_level: 'profile_basic'})"
                    >
                        <?= User::BuildProfilePictureImgTag($chapterLeads[$i]['firstname'], $chapterLeads[$i]['lastname'], $chapterLeads[$i]['picture'],'memberpic','', $chapterLeads[$i]['userid'], null);?>
                        <p class="member_name"><?= $chapterLeads[$i]['firstname'] . ' ' . $chapterLeads[$i]['lastname'] . (!empty($chapterLeads[$i]['pronouns']) ? '<span style="font-size: small;"> ('.$chapterLeads[$i]['pronouns'].')</span>' : ''); ?></p>
                        <p class="member_jobtitle"
                        style=" font-size:small;"><?= $types[$chapterLeads[$i]['grouplead_typeid']]['type']; ?><br><?= htmlspecialchars($chapterLeads[$i]['roletitle']); ?></p>
                    </div>
                </div>
            <?php } ?>
         </div>
        </div>
    <?php } ?>

<?php if (!empty($canViewContent) && ($group->val('about_show_members') === "1" || ($group->val('about_show_members') === "2" && $_USER->isGroupMember($group->id())))) { ?>
    <!-- Print Group Members -->
    <div class="row">
     <div class="col-md-12" id="jump_to_members_list">
        <hr/>
        <h2><?= htmlspecialchars($chapter['chaptername']); ?> <?= $_COMPANY->getAppCustomization()['group']['memberlabel'].'s'; ?></h2>
        <br/>
     </div>
    </div>
    <?php
        $ergName = htmlspecialchars($chapter['chaptername']).' '. $_COMPANY->getAppCustomization()['group']['memberlabel'].'s';
        $section= $_COMPANY->encodeId(2);
        $encSectionId  = $_COMPANY->encodeId($chapter['chapterid']);
        include __DIR__.'/group_chapter_channel_members_list.template.php';
    ?>
<?php } ?>

<script>
    $(document).ready(function () {
        //initial for blank profile picture
        $('.initial').initial({
            charCount: 2,
            textColor: '#ffffff',
            color: window.tskp?.initial_bgcolor ?? null,
            seed: 0,
            height: 50,
            width: 50,
            fontSize: 20,
            fontWeight: 300,
            fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
            radius: 0
        });
    });
</script>
