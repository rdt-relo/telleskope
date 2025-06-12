<?php
if (!$_COMPANY->getAppCustomization()['group']['homepage']['show_chapter_content_in_global_feed'] && !$_COMPANY->getAppCustomization()['group']['homepage']['show_channel_content_in_global_feed']) {
	$default_title = gettext('Global Feeds');
} else {
	$chapterOrChannelLabel = '';
	if ($_COMPANY->getAppCustomization()['group']['homepage']['show_chapter_content_in_global_feed'] && $_COMPANY->getAppCustomization()['chapter']['enabled']) {
		$chapterOrChannelLabel .=  $_COMPANY->getAppCustomization()['chapter']['name-short'];
	}
	if (($_COMPANY->getAppCustomization()['group']['homepage']['show_channel_content_in_global_feed'] && $_COMPANY->getAppCustomization()['channel']['enabled'])) {
		$chapterOrChannelLabel .= !empty($chapterOrChannelLabel) ? '/' : '';
		$chapterOrChannelLabel .= $_COMPANY->getAppCustomization()['channel']['name-short'];
	}
	$default_title = sprintf(gettext('Global and %s Feeds'), $chapterOrChannelLabel);
}

$title ??= $default_title;
$show_home_feed_filters ??= true;
$show_more ??= true;//(!empty($groups) && !empty($feeds));
$myGroupsTab = empty($myGroupsOnly) ? false : true;
$activate_last_selected_btn ??= true;
?>

<style>
	.slider-container{
		max-height: 175px;
	}
	.group-block {
		padding: 0 0;
	}
	.ch-cp{
		margin-top:-20px;
	}
	.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    margin: -1px;
    border: 0;
    padding: 0;
    white-space: nowrap;
    clip: rect(0 0 0 0);
    overflow: hidden;
}


.js-arrow-down {
    display: inline-block;
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 6px solid #233354;
    position: relative;
    left: 4px;
}

.js-arrow-up {
    display: inline-block;
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-bottom: 6px solid #233354;
    position: relative;
    left: 4px;
    bottom: 4px;
}
.toggle-btn-active .js-arrow-down{ border-top: 6px solid #fff;}
.toggle-btn-active .js-arrow-up{ border-bottom: 6px solid #fff;}
.toggle-btn:hover .js-arrow-down { border-top: 6px solid #fff;}
.toggle-btn:hover .js-arrow-up { border-bottom: 6px solid #fff;}

.toggle-btn:hover {
    background-color: #0077B5 !important;
    color: #ffffff !important;
}
</style>

<?php
$groupname = $_COMPANY->getAppCustomization()['group']['name'] ?? $_COMPANY->getAppCustomization()['group']['name-short'];
?>
	<?php $c = 0; foreach ($groups as $g){ ?>
		<?php if ($_ZONE->val('show_group_overlay')){ ?>
			<style>
				.red<?=$c;?>:before {background-color: <?=$g->val('overlaycolor');?>;}
			</style>
		<?php } ?>
	<?php $c++; } ?>
    <?php if ($show_homepage_group_tiles ?? true) { ?>
    <div class="container-fluid">
        <div class="row row-no-gutters">
			<div class="col-md-12 col-12 erg-block-view" style="padding:0px;" >
				<?php
                $currentCategroyId = 0;
				$i=0;
                if (empty($groups)) {
                ?>
                    <div class="col-md-12 alert-warning">
                        <p style="text-align:center;"><img height="150px" src="../image/nodata/no-group.png" alt="" ></p>
                        <?php if(isset($myGroupsTab) && $myGroupsTab){ ?>
                            <p style="text-align:center;color:#4D4D4D;">
                                <?= sprintf(gettext('You have not joined any %1$s yet. Explore available %2$s under the \'Discover\' tab.'),$_COMPANY->getAppCustomization()['group']['name-short'], $_COMPANY->getAppCustomization()['group']['name-short-plural']); ?>
                            </p>
                        <?php } else{ ?>
                            <p style="text-align:center;color:#4D4D4D;">
                                <?= sprintf(gettext('Stay tuned for %s to be created'),$_COMPANY->getAppCustomization()['group']['name-short']); ?>
                            </p>
                        <?php } ?>
                    </div>
                <?php
                } else {
                foreach ($groups as $group) {
                    $chapterCount = 0;
                    $channelCount = 0;
                    $groupMemberCount = $group->getGroupMembersCount();
                    if ($_COMPANY->getAppCustomization()['group']['homepage']['show_chapter_channel_count_in_tile']) {
                        $chapterCount = $group->getChapterCount();
                        $channelCount = $group->getChannelCount();
                    }

					if ($currentCategroyId !== $group->val('categoryid')) {
						$currentCategroyId = $group->val('categoryid');
						$categoryLabel = '';
						$categoryDescription = '';
						foreach ($groupCategoryRows as $groupCategoryRow) {
							if ($groupCategoryRow['categoryid'] == $currentCategroyId) {
								$categoryLabel = $groupCategoryRow['category_label'];
								$categoryDescription = $groupCategoryRow['category_description'];
								break;
							}
						}

                        if (!empty($categoryLabel)) { ?>
						    <h2 class="erg-headings"> <?= htmlspecialchars($categoryLabel); ?></h2>
						    <?php if (!empty($categoryDescription)) { ?>
						    <div class="erg-desc mb-3"><?= $categoryDescription ?></div>
                            <?php } ?>
					    <?php }
                    }
                ?>

					<div class="col-md-4 col-12 group-block moblie-view">
						<div class=" group-tile" >
							<a tabindex="0" style='text-decoration:none' href="detail?id=<?= $_COMPANY->encodeId($group->val('groupid')); ?>" aria-label="<?= htmlspecialchars($group->val('groupname')).' '.$groupname;?> <?= $groupMemberCount ?> <?= $_COMPANY->getAppCustomization()['group']['memberlabel'].'s'; ?> <?= $channelCount ?> <?= $_COMPANY->getAppCustomization()['channel']['name-short-plural']?> <?= $chapterCount ?> <?= $_COMPANY->getAppCustomization()['chapter']['name-short-plural']?> ">
								<div class="col-md-12 px-0 we overlay red<?= $i; ?>" style="border-radius:5px; max-height:175px;width:300px; background:url(<?= $group->val('sliderphoto') ? $group->val('sliderphoto') :  $group->val('coverphoto'); ?>) no-repeat;<?php if(!$group->val('sliderphoto')){ ?>background-size:cover;background-position: center center;<?php } ?>"><!-- <img src="img/flag.png" class="fl"> -->
									<div class="sp px-3" style="padding-left:0px" data-toggle="tooltip" data-html="true" data-placement="bottom" title="<?= htmlspecialchars($group->val('groupname'))?>" >
										<span style="display:<?= ($group->val('show_overlay_logo') == 1 || $group->val('show_overlay_logo') == 2) ? 'block' : 'none' ?>;">
										<?php if ($group->val('groupicon')) { ?>
											<img class="icon-img" src="<?= $group->val('groupicon'); ?>" alt="<?= sprintf(gettext('%s'),$group->val('groupname')); ?>">

										<?php }else{ ?>
											<?= $group->val('groupname'); ?>
										<?php } ?>
										</span>
										</div>
									<div class="slide-box px-2">
										<div class="col-md-6 col-6 vv" style="padding-left:0px !important;" >
											<?php if($_COMPANY->getAppCustomization()['group']['homepage']['show_member_count_in_tile'] && $groupMemberCount>0){ ?><?= $groupMemberCount ?> <?= $_COMPANY->getAppCustomization()['group']['memberlabel'].'s'; ?><?php }else{ echo "&nbsp;"; }?>
										</div>
										<div class="col-md-6 col-6 vv <?= ($group->getChapterCount() && $channelCount) ? 'ch-cp' : ''; ?> text-right" style="padding-right:0px !important;">
											<?php if($_COMPANY->getAppCustomization()['channel']['enabled'] && $channelCount){ ?>
												<p>
													<?= $channelCount ?>
													<?= $channelCount > 1 ? $_COMPANY->getAppCustomization()['channel']['name-short-plural'] : $_COMPANY->getAppCustomization()['channel']['name-short']?>
									            </p>
											<?php } ?>
											<?php if($_COMPANY->getAppCustomization()['chapter']['enabled'] && $chapterCount){ ?>
												<p>
													<?= $chapterCount ?>
													<?= $chapterCount > 1 ? $_COMPANY->getAppCustomization()['chapter']['name-short-plural'] : $_COMPANY->getAppCustomization()['chapter']['name-short']; ?>
												</p>
											<?php } ?>
										</div>
									</div>
								</div>
							</a>
						</div>
					</div>
		    <?php
                    $i++;
                }
                }
            ?>

			</div>
        </div>
    </div>
    <?php } ?>
    <?php 	if ($_COMPANY->getAppCustomization()['group']['homepage']['show_global_feed']){ ?>
		<div aria-atomic="true" role="alert" aria-live="assertive" class="col-md-12 impact2">
        <div role="heading" aria-level="2">
            <span><?= $title ?></span>
        </div>
	</div>
	<?php if ($show_home_feed_filters) { ?>
        <?php $available_content_types = Content::GetAvailableContentTypes(); ?>
        <?php $no_of_modules_enabled = count($available_content_types); ?>
        <?php if (in_array('upcomingEvents', $available_content_types)) { $no_of_modules_enabled = $no_of_modules_enabled - 1; } ?>
		<div class="col-12 text-center">
            <div class="btn-group btn-group-sm p-1 border center-button" style="background-color: #eeeeee; border-radius: 6px;">
                <div role="tablist" aria-label="Global and Chapter/Sub Group Feeds Button group with nested dropdown" >
                <?php if (in_array('event', $available_content_types)) { ?>
				<button role="tab" type="button" class="btn btn-primary btn-sm toggle-btn <?=$no_of_modules_enabled > 1 ? 'mr-1' : ''?>" id="upcomingEventsButton" onclick='handleHomepageFilterButtonClick("upcomingEventsButton", "<?= $group_category_id ?>", 0); '><?= gettext("Upcoming Events") ?></button>
				<?php } ?>
				<?php if ($no_of_modules_enabled > 1) { ?>
                <button role="tab" type="button" class="btn btn-primary btn-sm toggle-btn mr-2" id="showEverythingButton" onclick='handleHomepageFilterButtonClick("showEverythingButton", "<?= $group_category_id ?>", 0);'><?= gettext("Show Everything") ?></button>
				</div>
                <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-primary btn-sm dropdown-toggle toggle-btn toggle-btn-dropdown" data-toggle="dropdown" aria-expanded="false" style="border-radius: .25rem;">
                    <?= gettext("Show ") ?>&nbsp;<span class="js-arrow-down"></span>
                </button>
                <div class="dropdown-menu show-btn">
                    <?php if (in_array('post', $available_content_types)) { ?>
                    <button type="button" class="dropdown-item toggle-btn toggle-btn-item" id="postButton" onclick='handleHomepageFilterButtonClick("postButton", "<?= $group_category_id ?>", 0);'><?= Post::GetCustomName(true) ?></button>
                    <?php } ?>
                    <?php if (in_array('event', $available_content_types)) { ?>
                    <button type="button" class="dropdown-item toggle-btn toggle-btn-item" id="eventButton" onclick='handleHomepageFilterButtonClick("eventButton", "<?= $group_category_id ?>", 0);'><?= gettext("Events") ?></button>
                    <?php } ?>
                    <?php if (in_array('newsletter', $available_content_types)) { ?>
                    <button type="button" class="dropdown-item toggle-btn toggle-btn-item" id="newsletterButton" onclick='handleHomepageFilterButtonClick("newsletterButton", "<?= $group_category_id ?>", 0);'><?= gettext("Newsletters") ?></button>
                    <?php } ?>
					<?php if (in_array('discussion', $available_content_types)) { ?>
                    <button type="button" class="dropdown-item toggle-btn toggle-btn-item" id="discussionsButton" onclick='handleHomepageFilterButtonClick("discussionsButton", "<?= $group_category_id ?>", 0);'><?= gettext("Discussions") ?></button>
                    <?php } ?>
					<?php if (in_array('album', $available_content_types)) { ?>
                    <button type="button" class="dropdown-item toggle-btn toggle-btn-item" id="albumsButton" onclick='handleHomepageFilterButtonClick("albumsButton", "<?= $group_category_id ?>", 0);'><?= gettext("Albums") ?></button>
                    <?php } ?>
                </div>
                <?php } ?>
			 </div>
			</div>
		</div>
	<?php } ?>
	<div class="row row-no-gutters" id="feed_rows">
      <?php if (!empty($feed_listing_html_first_page)) { ?>
        <?= $feed_listing_html_first_page ?>
      <?php } else { ?>
    <div class="container w6">
        <div class="col-md-12">
            <p style="text-align:center;"><img height="200px" src="../image/nodata/no-group.png" alt="" ></p>
            <p style="text-align:center;color:#4D4D4D;"><?= $empty_results_msg ?? gettext('Stay tuned for announcements, events and newsletters'); ?></p>
            <hr>
        </div>
    </div>
      <?php } ?>
		<?php	} ?>
	</div>
	<input type="hidden" id='feedPageNumber' value="2">
	<?php if ($show_more) { ?>
		<div class="col-md-12 text-center mb-5 mt-3" id="loadeMoreFeedsAction" style="<?= $show_more ? '' : 'display:none;'; ?>">
			<button aria-label="Load more feeds" class="btn btn-affinity"
					onclick="loadMoreHomeFeeds('<?= $group_category_id; ?>')">
					<?= gettext('Load more'); ?>...
			</button>
		</div>
	<?php } ?>

<div id="rsvps_modal"></div>
<script>

    $(document).ready(function(){

		$(function () {
			$('[data-toggle="tooltip"]').tooltip()
		})

        $(".panel-heading .panel-title .collapsed").click(function(){
            $("li.panel.panel-default").addClass("active");
        });

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


        $('.progress .progress-bar').progressbar();

    });
</script>


<?php if ($activate_last_selected_btn) { ?>
<script>
    $(document).ready(function ()   {
		activateLastSelectedButton('<?= $group_category_id ?>');
    });
</script>
<?php } ?>

<script>
$('.toggle-btn-dropdown').click(function(){
	$(this).children('span').toggleClass('js-arrow-up js-arrow-down');
})

$(function() {  
  $(document).on("click", function(a) {
    if ($(a.target).is(".toggle-btn-dropdown") === false) {
      $('.toggle-btn-dropdown span').removeClass('js-arrow-up').addClass('js-arrow-down');
    }
  });
});

$('.toggle-btn').keydown(function(e) {  
    if (e.keyCode == 39) {       
        $(this).next('.toggle-btn').focus();       
    }else if(e.keyCode == 37){       
        $(this).prev('.toggle-btn').focus(); 
    }
});
</script>