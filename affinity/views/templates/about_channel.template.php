
    <!-- In chapter about us section -->
    <!-- Print the Chapter name-->
    <div class="row">
        <div class="col-md-12">
            <div class="mt-3">
                <h2><?= htmlspecialchars($channel['channelname']); ?></h2>
            </div>
        </div>
        <!-- Print the Chapter mission-->
        <div class="col-md-12">
            <div id="post-inner" class="px-3"><?= $channel['about']; ?></div>
        </div>
    </div>

    <!-- Print the Chapter  Leads if any-->
    <?php if (count($channelLeads)) { ?>
        <!-- Print Chapter Leads -->
        <div class="row">
         <div class="col-md-12" id="jump_to_leaders_list">
            <hr/>
            <h2 class="mb-2"><?= sprintf(gettext("%s Leaders"),$_COMPANY->getAppCustomization()['channel']['name-short']);?></h2>
            <br/>
         </div>
         <div class="col-md-12">
            <?php for ($i = 0; $i < count($channelLeads); $i++) {
                $encMemberUserID = $_COMPANY->encodeId($channelLeads[$i]['userid']);
                ?>
                <div class="col-md-4">
                    <div class="member-card" role="button" tabindex="0"
                        onclick="getProfileDetailedView(this,{'userid':'<?= $encMemberUserID; ?>', profile_detail_level: 'profile_basic'})"
                        onkeypress="getProfileDetailedView(this,{'userid':'<?= $encMemberUserID; ?>', profile_detail_level: 'profile_basic'})"
                    >
                        <?= User::BuildProfilePictureImgTag($channelLeads[$i]['firstname'], $channelLeads[$i]['lastname'], $channelLeads[$i]['picture'],'memberpic','', $channelLeads[$i]['userid'], null);?>
                        <p class="member_name"><?= $channelLeads[$i]['firstname'] . ' ' . $channelLeads[$i]['lastname'] . (!empty($channelLeads[$i]['pronouns']) ? '<span style="font-size: small;"> ('.$channelLeads[$i]['pronouns'].')</span>' : ''); ?></p>
                        <p class="member_jobtitle"
                        style=" font-size:small;"><?= $types[$channelLeads[$i]['grouplead_typeid']]['type']; ?><br><?= htmlspecialchars($channelLeads[$i]['roletitle']); ?></p>
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
        <h2><?= htmlspecialchars($channel['channelname']); ?> <?= $_COMPANY->getAppCustomization()['group']['memberlabel'].'s'; ?></h2>
        <br/>
      </div>
    </div>

    <?php
        $ergName = htmlspecialchars($channel['channelname']).' '. $_COMPANY->getAppCustomization()['group']['memberlabel'].'s';
        $section= $_COMPANY->encodeId(3);
        $encSectionId  = $_COMPANY->encodeId($channel['channelid']);
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
