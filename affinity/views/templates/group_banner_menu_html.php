<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    div.myNavbar {
        position: relative;
        overflow: visible;
    }
    ul.menu {
        max-width: 80%;
        overflow: hidden !important; 
    }
    ul.menu > li {
        display: block; float: left; 
        height: auto; white-space: nowrap;
        /* padding: 4px 8px;  */
    }
    ul.collect {
        position: absolute;
        right: 0; top: 0;
        /* padding: 4px 8px;  */
        overflow: visible;
    }
    ul.collect ul.dropdown-menu { right: 0; left: auto; }
    .dynamicDropdown{
        float:left !important;
    }
    .dynamicDropdown li.moreOption:hover a { color: rgb(66, 66, 66) !important;}
    .active-chapter-li{ background-color: #0077B5;}
    .active-chapter-li a.active-link{color: #fff;  background-color: #0077B5;}
    .chapter-list a.dropdown-item:hover{color: #fff;}
</style>
<style>
    .green-join-us {
        background-color: green !important;
        border-color: green !important;
    }
    .dropdown-item-selected {
        background-color: lightgrey;
    }
    .share-btn-custom{
        background-color: #000;
        border: 1px solid white;
    }
    .share-btn-custom:hover{
        background-color: #0d5380;
        border: 1px solid #0077b5;
    }
    .share-btn-section {
	text-align: right;
	padding-right: 0px;
	padding-top: 8px;
	margin: 0 !important;
	position: relative;
}
    .icon-pic-custom{
        padding-top: 29px;
    }
    ul#innerMenuBar+ul {
        margin-right: 0px;
    }
    .innerMenuMembership{
        margin-top:15px;
    }

    @media (max-width: 768px) {       
           #create_album {
            position: absolute;
            right: 0;
            top: 73px;
        }
    }

    @media (min-width: 834px) {
        .chapterbar-banner {
            display: block !important;
        }
    }

    .swal2-text-custom-class .swal2-html-container span{
        font-size:12px;
    }
    .innerMenu-disabled button{
        padding-top:14px;
        color : #ccc !important;
        cursor:no-drop !important;
        cursor: pointer;
        padding: 6px;
        margin: 6px;
        display: flex;
        background-color: #ffffff;
        border: none;
    }
    p.banner-group-name {
        font-size: 1.5rem;
        margin-top: -26px;
    }

    #btn-manage.disabled[data-toggle="tooltip"] {
        pointer-events: auto; /* Allow hover for tooltip */
    }

</style>
<!--Group  Bar Start -->
<div class="container w2 overlay green group-banner-item-focus"
     style="background:url(<?= $group->val('coverphoto') ? $group->val('coverphoto') : 'img/img.png'; ?>) no-repeat; background-size:cover;">
    <div class="share-btn-section col-md-12">
        <?php
        $encodedId = $_COMPANY->encodeId($group->id());
        ?>

        <button id="<?= $encodedId; ?>" class="btn btn-affinity focus-white-color share-btn-custom" onclick="getShareableLink('<?= $encodedId; ?>','<?= $_COMPANY->encodeId(0)?>','9')"  type="button" >
            <i class="far fa-share-square mainshare" style="" aria-hidden="true"></i>
                &nbsp;   <?= gettext("Share");?>
        </button>

    </div>
    <div class="col-md-12">
        <div class="ll icon-pic-custom" style="display:<?= ($group->val('show_overlay_logo') == 1 || $group->val('show_overlay_logo') == 3) ? 'block' : 'none' ?>;">
            <span style="display:<?= ($group->val('show_overlay_logo') == 1 || $group->val('show_overlay_logo') == 3) ? 'block' : 'none' ?>;">
                <?php if ($group->val('groupicon')) { ?>
                    <img class="icon-img" src="<?= $group->val('groupicon'); ?>" alt="<?= sprintf(gettext('%s Logo'),$group->val('groupname')); ?>">
                <?php } else { ?>
                    <p class="banner-group-name"><?= $group->val('groupname'); ?></p>
                <?php } ?>
            </span>
                </div>
    </div>

    <!--Chapter AND Channel Bar Start -->
    <?php
    if ($chapters || $channels) {
        $fc = $_USER->getFollowedGroupChapterAsCSV($groupid);
        $chapters_followed = null;

        if (strlen($fc)) {
            $chapters_followed = explode(',', $fc);
        }

        # Channels
        $fchannels = $_USER->getFollowedGroupChannels($groupid);
        $channels_followed = null;

        if (strlen($fchannels)) {
            $channels_followed = explode(',', $fchannels);
        }
        
        $select_a_chapter_str = sprintf(gettext('Select a %s'),$_COMPANY->getAppCustomization()['chapter']['name-short']);
        $select_a_channel_str = sprintf(gettext('Select a %s'),$_COMPANY->getAppCustomization()['channel']['name-short']);

        $chapter_btn_aria_label = (empty($selected_chapter) || $selected_chapter['chapterid'] == 0)
                ? ((!empty($_SESSION['showGlobalChapterOnly'])) ? sprintf(gettext('Showing %s level'), $_COMPANY->getAppCustomization()['group']['name-short']) : $select_a_chapter_str)
                : (sprintf(gettext('Selected %s: '), $_COMPANY->getAppCustomization()['chapter']['name-short']) . htmlspecialchars($selected_chapter['chaptername']));

        $channel_btn_aria_label = (empty($selected_channel) || $selected_channel['channelid'] == 0)
            ? ((!empty($_SESSION['showGlobalChannelOnly'])) ? sprintf(gettext('Showing %s level'), $_COMPANY->getAppCustomization()['group']['name-short']) : $select_a_channel_str)
            : (sprintf(gettext('Selected %s: '), $_COMPANY->getAppCustomization()['channel']['name-short']) . htmlspecialchars($selected_channel['channelname']))

        ?>

        <div class="chapterbar chapterbar-banner" id="chapterbar"
             style="background-color: #66666666;">

            <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled'] && $chapters) { ?>
                <div class="dropup" style="color: #fff; float: left;">

                    <button class="btn btn-default dropdown-toggle chapter-btn" type="button" data-toggle="dropdown" aria-label="<?=$chapter_btn_aria_label?>">
                        <i class="fas fa-globe" style="<?= (empty($selected_chapter) || $selected_chapter['chapterid'] == 0) ? '' : 'color:' . $selected_chapter['colour'] ?>" aria-hidden="true"></i>
                        &nbsp;
                        <?= (empty($selected_chapter) || $selected_chapter['chapterid'] == 0 ) ? ((!empty($_SESSION['showGlobalChapterOnly'])) ? sprintf(gettext('Showing %s level'),$_COMPANY->getAppCustomization()['group']['name-short']) : $select_a_chapter_str) : $selected_chapter['chaptername'] ?>
                        &emsp;
                        <i class="fa fa-angle-down" aria-hidden="true"></i>
                    </button>

                    <?php $show_all_selected = empty($_SESSION['showGlobalChapterOnly']) && empty($selected_chapter['chapterid']); ?>

                    <ul role="list" class="dropdown-menu chapter-list">                      
                        <li role="listitem" class="chapter-list <?= $show_all_selected ? 'active-chapter-li':''; ?>">
                        <a class="dropdown-item <?= $show_all_selected ? 'active-link':''; ?>" aria-label="<?= gettext('Show All'); ?> <?= $show_all_selected ? '- Selected':''; ?>" tabindex="0" href="javascript:void(0);" onclick="loadNewPageWithSelectedTabState(this)" data-href="detail?id=<?=$enc_groupid;?>&chapterid=<?= $_COMPANY->encodeId(0); ?>&channelid=<?= $enc_channelid;?>&showAllChapters=1"
                           <?= $show_all_selected ? 'class="dropdown-item-selected"':'' ?>
                        >
                        <?= gettext('Show All'); ?>&emsp;
                        
                            </a>
                        </li>

                        <li role="listitem" class="chapter-list <?= (!empty($_SESSION['showGlobalChapterOnly'])) ? 'active-chapter-li':'' ?>">
                            <a  aria-label="<?= sprintf(gettext('Show %s Level Only'),$_COMPANY->getAppCustomization()['group']['name-short']); ?> <?= (!empty($_SESSION['showGlobalChapterOnly'])) ? '- Selected':'' ?>" class="dropdown-item <?= (!empty($_SESSION['showGlobalChapterOnly'])) ? 'active-link':'' ?>" tabindex="0" href="javascript:void(0);" onclick="loadNewPageWithSelectedTabState(this)" data-href="detail?id=<?=$enc_groupid;?>&chapterid=<?= $_COMPANY->encodeId(0); ?>&channelid=<?= $enc_channelid;?>&showGlobalChapterOnly=1"
                                <?= (!empty($_SESSION['showGlobalChapterOnly'])) ? 'class="dropdown-item-selected"':'' ?>
                            >
                            
                            <?= sprintf(gettext('Show %s Level Only'),$_COMPANY->getAppCustomization()['group']['name-short']); ?>&emsp;
                            </a>
                        </li>
                        <li>
                            <div class="dropdown-header" style="background-color: #f2f2f2;  padding: 1px 10px;"><?= sprintf(gettext('Choose a %s'),$_COMPANY->getAppCustomization()['chapter']['name-short'])?></div>
                        </li>

                        <?php foreach ($chapters as $key=>$chps) {	?>
                            <li>
                                <div class="dropdown-header" style="background-color: #f2f2f2;  padding: 1px 10px;border-top: 5px white solid;"><strong><?= $key; ?> :</strong></div>
                            <ul>
                                <?php foreach($chps as $chp){
                                     $selectedChapterId = $selected_chapter['chapterid'] ?? null;
                                     $activeChapterLiClass = $selectedChapterId == $chp['chapterid'] ? 'active-chapter-li' : '';
                                     $activeLinkClass = $selectedChapterId == $chp['chapterid'] ? 'active-link' : '';
                                     $selected = $selectedChapterId == $chp['chapterid'] ? ' - Selected' : '';
                                     ?>
                                    <li role="listitem" class="chapter-list <?= $activeChapterLiClass; ?>">
                                        <a class="dropdown-item <?= $activeLinkClass; ?>" tabindex="0" aria-label="<?= htmlspecialchars($chp['chaptername']) . $selected ?>" href="#group-chapter-list" onclick="loadNewPageWithSelectedTabState(this)" data-href="detail?id=<?=$enc_groupid;?>&chapterid=<?= $_COMPANY->encodeId($chp['chapterid']); ?>&channelid=<?= $enc_channelid;?>"
                                            <?= (empty($_SESSION['showGlobalChapterOnly']) && !empty($selected_chapter['chapterid']) && $selected_chapter['chapterid'] == $chp['chapterid']) ? 'class="dropdown-item-selected"':'' ?>>
                                            
                                            &emsp;<?= htmlspecialchars($chp['chaptername']) ?>&emsp;
                                            <?php if (!is_null($chapters_followed) && in_array($chp['chapterid'], $chapters_followed)) { ?>
                                                <i aria-label="Joined" class="fa fa-check" role="img" style="color:green; float: right;"></i>
                                            <?php } ?>
                                        </a>
                                    </li>
                                <?php	}	?>	
                                    </ul>					
                                </li>
                        <?php }	?>
                    </ul>
                </div>
            <?php } ?>
            <?php if ($_COMPANY->getAppCustomization()['channel']['enabled'] && $channels) { ?>
                <div class="dropup" style="color: #fff; float: left;">

                    <button class="btn btn-default dropdown-toggle chapter-btn channel-btn" type="button" data-toggle="dropdown" aria-label="<?=$channel_btn_aria_label?>">
                        <i class="fas fa-layer-group" style="<?= (empty($selected_channel) || $selected_channel['channelid'] == 0) ? '' : 'color:' . $selected_channel['colour'] ?>" aria-hidden="true"></i>
                        &nbsp;
                        <?= (empty($selected_channel) || $selected_channel['channelid'] == 0 ) ? ((!empty($_SESSION['showGlobalChannelOnly'])) ? sprintf(gettext('Showing %s level'),$_COMPANY->getAppCustomization()['group']['name-short']): $select_a_channel_str) : htmlspecialchars($selected_channel['channelname']) ?>
                        &emsp;
                        <i class="fa fa-angle-down" aria-hidden="true"></i>
                    </button>

                    <ul role="list" class="dropdown-menu chapter-list">
                        <li role="listitem" class="channel-list <?= (empty($_SESSION['showGlobalChannelOnly']) && empty($selected_channel['channelid'])) ? 'active-chapter-li':''; ?>">
                            <a class="dropdown-item <?= (empty($_SESSION['showGlobalChannelOnly']) && empty($selected_channel['channelid'])) ? 'active-link':''; ?>" aria-label="<?= gettext('Show All'); ?> <?= (empty($_SESSION['showGlobalChannelOnly']) && empty($selected_channel['channelid'])) ? '- Selected':''; ?>" tabindex="0" href="javascript:void(0);" onclick="loadNewPageWithSelectedTabState(this)" data-href="detail?id=<?=$enc_groupid;?>&chapterid=<?= $enc_chapterid; ?>&channelid=<?= $_COMPANY->encodeId(0);?>&showAllChannels=1"
                                <?= (empty($_SESSION['showGlobalChannelOnly']) && empty($selected_channel['channelid'])) ? 'class="dropdown-item-selected"':'' ?>
                            >
                            <?= gettext('Show All'); ?>&emsp;
                            </a>
                        </li>
                        <li role="listitem" class="channel-list <?= (!empty($_SESSION['showGlobalChannelOnly'])) ? 'active-chapter-li':''; ?>">
                            <a class="dropdown-item <?= (!empty($_SESSION['showGlobalChannelOnly'])) ? 'active-link':''; ?>" aria-label=" <?= sprintf(gettext('Show %s Level Only'),$_COMPANY->getAppCustomization()['group']['name-short']); ?> <?= (!empty($_SESSION['showGlobalChannelOnly'])) ? '- Selected':''; ?>" tabindex="0" href="javascript:void(0);" onclick="loadNewPageWithSelectedTabState(this)" data-href="detail?id=<?=$enc_groupid;?>&chapterid=<?= $enc_chapterid; ?>&channelid=<?= $_COMPANY->encodeId(0);?>&showGlobalChannelOnly=1"
                                <?= (!empty($_SESSION['showGlobalChannelOnly'])) ? 'class="dropdown-item-selected"':'' ?>
                            >
                            <?= sprintf(gettext('Show %s Level Only'),$_COMPANY->getAppCustomization()['group']['name-short']); ?>&emsp;
                            </a>
                        </li>
                        <li>
                            <div class="dropdown-header"  style="background-color: #f2f2f2;  padding: 3px 10px;"><?= sprintf(gettext('Choose a %s'),$_COMPANY->getAppCustomization()['channel']['name-short']) ?></div>
                        </li>

                        <?php foreach ($channels as $channel) { 
                             $selectedChannelId = $selected_channel['channelid'] ?? null;
                             $activeChapterLiClass = $selectedChannelId == $channel['channelid'] ? 'active-chapter-li' : '';
                             $activeLinkClass = $selectedChannelId == $channel['channelid'] ? 'active-link' : '';
                             $channelIdEncoded = $_COMPANY->encodeId($channel['channelid']);
                             $selected = $selectedChannelId == $channel['channelid'] ? ' - selected' : '';
                             ?>
                            <li role="listitem" class="chapter-list <?= $activeChapterLiClass; ?>" aria-label="<?= htmlspecialchars($channel['channelname']) . $selected ?>">
                                <a class="dropdown-item <?= $activeLinkClass; ?>" tabindex="0" href="javascript:void(0);" aria-label="<?= htmlspecialchars($channel['channelname']) . $selected ?>" onclick="loadNewPageWithSelectedTabState(this)"
                                onkeypress="loadNewPageWithSelectedTabState(this)" data-href="detail?id=<?=$enc_groupid;?>&chapterid=<?= $enc_chapterid; ?>&channelid=<?=  $_COMPANY->encodeId($channel['channelid']);?>"
                                    <?= (empty($_SESSION['showGlobalChannelOnly']) && !empty($selectedChannelId) && $selectedChannelId == $channel['channelid']) ? 'class="dropdown-item-selected"':'' ?>
                                >
                                    &emsp;<?= htmlspecialchars($channel['channelname']) ?>&emsp;
                                    <?php if (!is_null($channels_followed) && in_array($channel['channelid'], $channels_followed)) { ?>
                                        <i class="fa fa-check" aria-hidden="true" style="color:green; float: right"></i>
                                    <?php } ?>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>

            <div class="pull-right">
                <?php if ($_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) { ?>
                    <span class="manage-title">
           <?php
           $showManageButton = true;
           $canManageSomething = $_USER->canManageGroupSomething($groupid);
           if ($_USER->isCompanyAdmin()) { echo gettext("You are a Global Admin");}
           elseif ($_USER->isZoneAdmin($_ZONE->id())) { echo gettext("You are a Zone Admin");}
           elseif ($_USER->isGrouplead($groupid)) { echo sprintf(gettext('You are a %s Leader'),$_COMPANY->getAppCustomization()['group']['name-short']); }
           elseif ($_USER->isRegionallead($groupid)) { echo gettext("You are a Regional Leader"); }
           elseif ($_USER->isChapterlead($groupid,-1)) { echo sprintf(gettext('You are a %s Leader'),$_COMPANY->getAppCustomization()['chapter']['name-short']); }
           elseif ($_USER->isChannellead($groupid,-1)) { echo sprintf(gettext('You are a %s Leader'),$_COMPANY->getAppCustomization()['channel']['name-short']); }
           else { $showManageButton = false;}
           ?>
            </span>
                    <?php if ($showManageButton) { ?>
                        <a id="btn-manage" data-toggle="tooltip" data-placement="left" title="" tabindex="0" href="javascript:void(0);" onclick="if(!checkDisabled(this)) return false; prepareLoadManageState('<?=$canManageSomething?>');loadNewPageWithSelectedTabState(this)" data-href="manage?id=<?=$_COMPANY->encodeId($groupid);?>" class="btn-affinity focus-white-color btn-sm btn button-manage-padding">
                            <?= gettext('Manage'); ?>
                        </a>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>

    <?php } else { ?>
        <div class="chapterbar" id="chapterbar">
            <div class="pull-right">
                <?php if ($_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) { ?>
                    <span class="manage-title">
           <?php
           $showManageButton = true;
           $canManageSomething = $_USER->canManageGroupSomething($groupid);
           if ($_USER->isCompanyAdmin()) { echo gettext("You are a Global Admin"); }
           elseif ($_USER->isZoneAdmin($_ZONE->id())) { echo gettext("You are a Zone Admin");}
           elseif ($_USER->isGrouplead($groupid)) { echo sprintf(gettext('You are a %s Leader'),$_COMPANY->getAppCustomization()['group']['name-short']); }
           elseif ($_USER->isRegionallead($groupid)) { echo gettext("You are a Regional Leader"); }
           elseif ($_USER->isChapterlead($groupid,-1)) { echo sprintf(gettext('You are a %s Leader'),$_COMPANY->getAppCustomization()['chapter']['name-short']); }
           elseif ($_USER->isChannellead($groupid,-1)) { echo sprintf(gettext('You are a %s Leader'),$_COMPANY->getAppCustomization()['channel']['name-short']); }
           else { $showManageButton = false; }
           ?>
            </span>
                    <?php if ($showManageButton) { ?>
                    <a id="btn-manage" data-toggle="tooltip" data-placement="left" title="" href="javascript:void(0);" onclick="if(!checkDisabled(this)) return false; prepareLoadManageState('<?=$canManageSomething?>');loadNewPageWithSelectedTabState(this)" data-href="manage?id=<?=$_COMPANY->encodeId($groupid);?>" class="btn-affinity focus-white-color btn-sm btn button-manage-padding">
                        <?= gettext('Manage'); ?>
                    </a>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
    <!--Chapter Bar End-->

</div>
<!--Group Bar End-->

<?php
    // Group custom Tabs
    $groupCustomTabs = $group->getGroupCustomTabs(true);
?>

<!--Group Menu Start-->
<div id="main_section" class="container w3 subnav px-3">
    <nav class="navbar-expand-lg navbar-light navbar-group-menu" aria-label="<?= $_COMPANY->getAppCustomization()['group']['name'] ?>">
        <div class="container-fluid">
            <div id="myNavbar">
            <div class="col-md-10 m-0 p-0 menu-container" id="menu_btn_container">
                <ul class="navbar-nav menu" id="innerMenuBar" role="tablist">

                    <?php if ($_COMPANY->getAppCustomization()['aboutus']['enabled']) { ?>
                    <li role="none" data-method="getAboutus" class="innerMenu getAboutus about-tab" >
                        <button tabindex="-1" type="button" class="btn-no-style menu-button" aria-selected="false" onclick="getAboutusTabs('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>')" role="tab">
                            <?= gettext('About Us'); ?>
                        </button>
                    </li>
                    <?php } ?>
                    
                    <?php if ($_COMPANY->getAppCustomization()['donations']['enabled'] && $canViewContent) { ?>
                    <li role="none" data-method="donation" class="innerMenu donation donation-tab">
                                <button tabindex="-1" type="button" class="btn-no-style menu-button" aria-selected="false" onclick="donation('<?=$enc_groupid;?>')" role="tab">
                                    <?= gettext('Donations'); ?>
                                </button>
                            </li>
                    <?php } ?>

                    <?php if ($_COMPANY->getAppCustomization()['post']['enabled'] && $canViewContent) { ?>
                    <li role="none" data-method="getHome" class="innerMenu getHome home-tab">
                                <button tabindex="-1" type="button" class="btn-no-style menu-button" aria-selected="false" onclick="getHome('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>')" role="tab">
                                    <?= Post::GetCustomName(true); ?>
                                </button>
                            </li>
                    <?php } ?>

                    <?php if ($_COMPANY->getAppCustomization()['event']['enabled'] && $canViewContent) { ?>
                    <li role="none" data-method="getEvent" class="innerMenu getEvent event-tab">
                                <button tabindex="-1" type="button" class="btn-no-style menu-button" aria-selected="false" onclick="getEvent('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>')" role="tab">
                                    <?= gettext('Events'); ?>
                                </button>
                            </li>
                    <?php } ?>

                    <?php if ($_COMPANY->getAppCustomization()['newsletters']['enabled'] && $canViewContent) { ?>
                    <li role="none" data-method="getGroupChaptersNewsletters" class="innerMenu getGroupChaptersNewsletters">
                                <button tabindex="-1" type="button" class="btn-no-style menu-button" aria-selected="false" onclick="getGroupChaptersNewsletters('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>',<?= date('Y')?>)" role="tab">
                                    <?= gettext('Newsletters'); ?>
                                </button>
                            </li>
                    <?php } ?>

                    <?php if ($_COMPANY->getAppCustomization()['resources']['enabled'] && $canViewContent) { ?>
                    <li role="none" data-method="getComonGroupResources" class="innerMenu getComonGroupResources" >
                                <button tabindex="-1" type="button" class="btn-no-style menu-button" aria-selected="false" onclick="getComonGroupResources('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>')" role="tab">
                                    <?= gettext('Resources'); ?>
                                </button>
                            </li>
                    <?php } ?>

                    <?php if ($_COMPANY->getAppCustomization()['linked-group']['enabled'] && $_ZONE->val('app_type') == 'officeraven') { ?>
                    <li role="none" class="innerMenu getOfficeRavenGroups" >
                        <button tabindex="-1" type="button" class="btn-no-style menu-button" aria-selected="false" onclick="getOfficeRavenGroups('<?=$enc_groupid;?>')" role="tab">
                            <?= gettext('Groups'); ?>
                        </button>
                    </li>
                    <?php } ?>

                    <?php if ($_COMPANY->getAppCustomization()['albums']['enabled'] && $canViewContent) { ?>
                    <li role="none" data-method="getHighlights" class="innerMenu getAlbums">
                                <button tabindex="-1" id="getAlbumsMenu" type="button" class="btn-no-style menu-button" aria-selected="false" onclick="getAlbums('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>')" role="tab">
                                    <?= gettext('Albums'); ?>
                                </button>
                            </li>
                    <?php } ?>
                    
                    <?php if ($group->isTeamsModuleEnabled() && $canViewContent) {

                        $showGlobalChapterOnly = intval($_SESSION['showGlobalChapterOnly'] ?? 0);
                        $myTeams = Team::GetMyTeams($group->id(),$chapterid, $showGlobalChapterOnly);
                                             
                        ?>
                        <li role="none" class="innerMenu getMyTeams">
                                <button tabindex="-1" type="button" id="my_team_menu" class="btn-no-style menu-button" aria-selected="false" 
                                <?php  if (empty($myTeams)){  ?>
                                    onclick="initMyTeamsContainer('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>',1)" 
                                <?php } else { ?>
                                    onclick="initMyTeamsContainer('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>')" 
                                <?php }
                                ?>
                                
                                role="tab">
                                <?= sprintf(gettext("My %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?>
                                </button>
                            </li>

                    
                   <?php } ?>

                    <?php if ($_COMPANY->getAppCustomization()['discussions']['enabled'] && $canViewContent) { ?>
                    <li role="none" data-method="getGroupDiscussions" class="innerMenu getGroupDiscussions">
                                <button tabindex="-1" type="button" class="btn-no-style menu-button" aria-selected="false" onclick="getGroupDiscussions('<?=$enc_groupid;?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>')" role="button">
                                    <?= gettext('Discussions'); ?>
                                </button>
                            </li>
                    <?php } ?>

                  

                    <?php if ($_COMPANY->getAppCustomization()['recognition']['enabled'] && $canViewContent && $group->getRecognitionConfiguration()['enable_user_view_recognition']) { ?>
                    <li role="none" data-method="getRecognitions" class="innerMenu getRecognitions">
                                <button tabindex="-1" type="button" class="btn-no-style menu-button" aria-selected="false" onclick="getRecognitions('<?=$enc_groupid;?>','<?= $_COMPANY->encodeId(0); ?>',1)" role="button">
                                    <?= Recognition::GetCustomName(true); ?>
                                </button>
                            </li>
                    <?php } ?> 
                    
                    <?php if ($_COMPANY->getAppCustomization()['booking']['enabled'] && $canViewContent) { ?>
                            <li role="none" data-method="getMyBookings" class="innerMenu getMyBookings">
                                <button tabindex="-1" type="button" class="btn-no-style menu-button" aria-selected="false" onclick="getMyBookings('<?=$enc_groupid;?>')" role="tab">
                                    <?= Group::GetBookingCustomName(true); ?>
                                </button>
                            </li>
                    <?php } ?>   

                    <?php if ($canViewContent) { ?>
                    <?php foreach($groupCustomTabs as $customTab){ /* Note: customtab ids are required for page refresh to work */ ?>
                        <li role="none" data-method="getGroupCustomTabs" data-id="customtab_<?= $_COMPANY->encodeId($customTab['tabid']); ?>_li" class="innerMenu getGroupCustomTabs">
                                <button tabindex="-1" type="button" class="btn-no-style menu-button" aria-selected="false" onclick="getGroupCustomTabs('<?=$enc_groupid;?>','<?= $_COMPANY->encodeId($customTab['tabid']); ?>')" data-id="customtab_<?= $_COMPANY->encodeId($customTab['tabid']); ?>" role="button">
                                    <?= $customTab['tab_name']; ?>
                                </button>
                            </li>
                    <?php } ?>
                    <?php } ?>
                </ul>
                <ul role="none" class="navbar-nav m-auto dynamicDropdown navbar-right" >
                    <li role="none" class="dropdown innerMenu  moreOption">
                        <button aria-expanded="false" tabindex="0" data-toggle="dropdown" class="dropdown-toggle">
                            <span id="more_options"><?= gettext("More Options"); ?></span><span aria-hidden="true" class="caret">&nbsp;&#9662;</span>
                        </button>
                        <ul id="submenu" class="dropdown-menu position-absolute submenu-list"></ul>
                    </li>
                </ul>

                </div>
           
                <?php if ($group->val('group_type') != Group::GROUP_TYPE_MEMBERSHIP_DISABLED) { ?>
                <div class="col-md-2 m-0 p-0" id="membership_btn_container">
                    <?php include(__DIR__ . "/group_manage_membership.template.php"); ?>
                </div>
                <?php } ?>

            </div>
        </div>
    </nav>
</div>
<!--Group Menu End-->
<!-- Placeholder for follow/unfollow modal -->
<div id="modal_replace">
</div>

<script>
    function checkDisabled(el) {
        if ($(el).hasClass('disabled')) {
            return false;
        }
        return true;
    }

    function prepareLoadManageState(ismanager) {
        if (!ismanager) {
            let validOptions = ['manageGlobalAnnouncements', 'manageGlobalEvents', 'getGroupNewsletters','manageGroupDiscussions'];
            let item = localStorage.getItem("manage_active");
            if (validOptions.indexOf(item) == -1) {
                localStorage.setItem("manage_active", "manageGlobalAnnouncements");
            }
        }
    }

    function updateClass(e, a, c) {
        for (var x = 0; x < e.length; x++) {
            e[x].classList[a](c);    
        }
    }

    function clearActiveDynamicMenu(i){
        var headers;
        if (i == 1){
            headers = document.getElementById("submenu");
        } else {
            headers = document.getElementById("innerMenuBar");
        }
        updateClass(headers.getElementsByClassName("innerMenu"),'remove','submenuActive');
    }

    function manageMainMenuActiveState(){
        $('#innerMenuBar').on('click', 'li', function() {
            $('#innerMenuBar li.submenuActive').removeClass('submenuActive');
            $(this).addClass('submenuActive');  
            clearActiveDynamicMenu(1);
        });       
    }

    function manageCollapsedMenuActiveState(){
        $('#submenu').on('click', 'li', function() {
            $('.menu-button').attr('aria-selected', 'false');          
            $('#submenu li.submenuActive').removeClass('submenuActive');
            $(this).addClass('submenuActive'); 
            clearActiveDynamicMenu(2);            
        });
    }
    function detectScreenRotation(){
        if (screenWidth.isPhonePotrait() || screenWidth.isPhoneLandscape()){
            $("#membership_action_btns").removeClass("pull-right");
            $("#menu_btn_container").removeClass("col-md-10");
            $("#membership_btn_container").removeClass("col-md-2");
            if (screenWidth.isPhonePotrait()){
                $("#membership_action_btns").addClass("text-right");
                $("#more_options").html("<?= gettext('Menu')?>");
                $("#submenu").css({ marginRight: -60 });
            } else {
                $("#membership_action_btns").addClass("text-right");
                $("#more_options").html("<?= gettext('More Options')?>");
            }
            $('.group-join-leave').css({ marginLeft: 10 });
        }
    }

    function resetCollapsedMenu(){
        var subheaders = document.getElementById("submenu");
        var subbtns = subheaders.getElementsByClassName("innerMenu");
        var activeBtn = "";
        for (var s = 0; s < subbtns.length; s++) {            
            if ($(subbtns[s]).hasClass('submenuActive')){
                activeBtn = $(subbtns[s]).html();               
                
                break;
            }
        }
        var header = document.getElementById("innerMenuBar");
        var btns = header.getElementsByClassName("innerMenu");
        for (var i = 0; i < btns.length; i++) {
            $(btns[i]).css({"display": "block", "width": "auto"});
            if(activeBtn == $(btns[i]).html()){ // set back active state to defalut menu
                $(btns[i]).addClass("submenuActive");                
            }
        }
    }

    function createCollapsibleMenu(){
        
        manageMainMenuActiveState(); // Manage active state of normal menu
        resetCollapsedMenu(); // Reset menu
        var elemWidth, fitCount, varWidth = 0, ctr, 
        $menu = $("ul#innerMenuBar"), $collectedSet;
        ctr = $menu.children().length;
        $menu.children().each(function() {
            varWidth += $(this).outerWidth();
        });
        collect();
        $(window).resize(collect);

        function collect() {
            elemWidth = $menu.width();

            if (varWidth <= Math.ceil(elemWidth)) {
                $("#submenu").empty();
                return;
            }

            fitCount = Math.floor((elemWidth / varWidth) * ctr) - 1;
            $menu.children().css({"display": "block", "width": "auto"});
            if (screenWidth.isPhonePotrait()){
                $collectedSet = $menu.children(":lt(" + ctr + ")");
            } else {   
                if(screenWidth.isPhoneLandscape() && fitCount > 2 ){ // UI dependency for landscape mobile view to prevent cut menu item from main container width 
                    fitCount = fitCount-2;
                }
                $collectedSet = $menu.children(":gt(" + fitCount + ")");
            }
            if($collectedSet.length == 1){
                $("ul.menu").css({"max-width": "100%"});
            } else{
                $("#submenu").empty().append($collectedSet.clone());  
                $collectedSet.css({"display": "none", "width": "0"});
            }
        }
        detectScreenRotation(); // check if window is retorated/resized
        manageCollapsedMenuActiveState(); // Manage active state of collepsed menu
       
        $('#submenu li.innerMenu').attr('aria-current', 'false');
        $('#submenu li.submenuActive').attr('aria-current', 'true');

        if($("#submenu").html()){
            $(".dynamicDropdown").show();
        } else{
            $(".dynamicDropdown").hide();
        }
    }

    $(document).ready(function () {

        createCollapsibleMenu(); // Create Collapible menu on page load

        $('.join-us').click(function () {
            $(".confirm-dialog-btn-confirm").addClass("green-join-us");
        });
        $('#innerMenuBar').click(function(){
            manageMainMenuActiveState(); // manage active state if defalut manu item clicked
        });
        $('#submenu').click(function(){
            manageCollapsedMenuActiveState();// manage active state if collapible manu item clicked
        });

        $('.chapter-btn').attr("aria-expanded","false");
        $('.channel-btn').attr("aria-expanded","false");

    });

    $(window).resize(function(){
       setTimeout(createCollapsibleMenu, 50);
    });

    (new MutationObserver(createCollapsibleMenu)).observe(
    document.head,
        {
            childList: true
        }
    );

  $('.menu-button, .moreOption').keydown(function(e) {  
        if (e.keyCode == 39) {       
            $(this).parent().next().find(".menu-button").focus();    
        }else if(e.keyCode == 37){       
            $(this).parent().prev().find(".menu-button").focus();  
        }
    });     

    $('.moreOption').on('keydown', function (e) {       
        $('.moreOption .menu-button').attr('tabindex', '0');       
    });
</script>

