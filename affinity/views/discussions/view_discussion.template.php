
<?php if ($groupid > 0) { //If post is associated with a group then show group banner  ?>
    <style>
        .green:before {background-color: <?= $_ZONE->val('show_group_overlay') ? $group->val('overlaycolor') : ''; ?>;}
    </style>

<?php include __DIR__.'/templates/group_banner_menu_html.php' ?>
<?php } else { ?>
	<div class="container w2 overlay green"
     style="background:url(<?= $_ZONE->val('banner_background') ? $_ZONE->val('banner_background') : 'img/img.png' ?>) no-repeat; background-size:cover;background-position: center center;">
		<div class="col-md-12">
			<h1 class="ll">
				<span>
                    <?php
                    if (!empty(trim($_ZONE->val('admin_content_page_title')))) {
					    echo $_COMPANY->getAppCustomization()['group']['groupname0']. ' '. gettext('Discussion');
                    } ?>
				</span>
			</h1>
		</div>
		<div class="chapterbar">
			<div class="pull-right">
				<a  onclick="goBack();" class="btn-affinity btn-sm btn button-manage-padding">
				<?= gettext('Back'); ?>
				</a>
			</div>
		</div>
	</div>
<?php } ?>


	<div id="ajax">
		<div class="container inner-background">
			<div class="row row-no-gutters w-100">
				<div class="col-md-12">
					<div class="col-md-10">
						<div class="inner-page-title">
						<?php if ($discussion->isDraft() || $discussion->isUnderReview()) { ?>
							<h3 style="color:red;" >
								<?= $discussion->val('title'); ?>
								<img src="img/draft_ribbon.png" alt="Draft icon image" height="16px"/>
							</h3>
                        <?php } else if ($discussion->isAwaiting()){ ?>
                            <h3 style="color:deepskyblue;" >
                                <?= $discussion->val('title'); ?>
                                <img src="img/schedule.png" alt="Schedule icon image" height="16px"/>
								&nbsp;
								<span style="font-size: 13px;"><small id="publishCountDown">[ Publish in _ _ _ ]</small></span>
                            </h3>
							<script>
								countDownTimer(<?= $_COMPANY->getMysqlDatetimeAsTimestamp($discussion->val('modifiedon')) - time(); ?>,"<?= $_COMPANY->encodeId($discussion->id()); ?>",0);
							</script>
						<?php } else { ?>
							<h3 >
								<?= $discussion->val('title'); ?>
                                <?php if($discussion->val('pin_to_top')){ ?>
                                    <i class="fa fa-thumbtack ml-1" style="font-size:small;vertical-align:super;" aria-hidden="true"></i>
                                <?php } ?>
							</h3>
						<?php } ?>
						</div>
					</div>

					<!-- Post Menu Start -->
                    <?php
					$isAllowedToUpdateContent = true;
					$isAllowedToPublishContent = true;
					$isAllowedToManageContent = true;
					
					if (!$_USER->isAdmin()){
                        $isAllowedToUpdateContent = $_USER->canUpdateContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid'),$discussion->val('isactive'));
                        $isAllowedToPublishContent = $_USER->canPublishContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid'));
                        $isAllowedToManageContent = $_USER->canManageContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid'));
					}

                    if ($discussion->isActive() || $isAllowedToUpdateContent || $isAllowedToPublishContent || $isAllowedToManageContent) {
                    ?>

                    <div class="col-md-2 innar-page-right-button">
                        <i class="dropdown-toggle  fa fa-ellipsis-v col-doutd mobile-off" data-toggle="dropdown" aria-hidden="true"></i>
                        <ul class="dropdown-menu dropmenu">
                            <?php include(__DIR__ . "/templates/discussion_action_button.template.php"); ?>
                        </ul>
                    </div>

                    <?php } ?>
					<!-- Post Menu End -->
					<!--div class="col-md-1">
						<span onclick="goBack();"><i class="fa fa-arrow-circle-left" aria-hidden="true"></i></span>
					</div-->
				</div>
				<!-- Post Title Row End -->
				<div class="discussion-detail">
					<!-- Second Row Start -->
					<div class="row">
						<div class="col-md-12">
							<span class="dta-tm">
								<?= ($discussion->val('isactive') == Post::STATUS_DRAFT || $discussion->val('isactive') == Post::STATUS_UNDER_REVIEW)? gettext("Created on"): ( $discussion->val('isactive') == Post::STATUS_AWAITING ? gettext("Scheduled to publish on") : gettext("Published on") ) ?>
                                <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone(
                                        ($discussion->val('isactive') == Post::STATUS_DRAFT ? $discussion->val('postedon') : $discussion->val('modifiedon')),
                                        true, false, false)
                                ?>
								
							<?php if (!empty($discussion->val('chapterid'))) { ?>
								<?= gettext("in")?>
								<?php foreach(explode(',', $discussion->val('chapterid')) as $chid){ ?>
										<?php 	$c = Group::GetChapterName($chid,$discussion->val('groupid')); ?>
										<span class="chapter-label" style="color:<?= $c['colour'] ?>">
											<i class="fas fa-globe" style="color:<?= $c['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($c['chaptername']); ?>
										</span>
										&nbsp;

								<?php } ?>
							<?php } ?>
	
									<?php 
										if ($discussion->val('channelid') > 0){ 
											$ch = Group::GetChannelName($discussion->val('channelid'),$discussion->val('groupid'));        
									?>
										<span class="chapter-label ml-1" style="color:<?= $ch['colour'] ?>">
											<i class="fas fa-layer-group" style="color:<?= $ch['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($ch['channelname']); ?></span>
									<?php
										}
									?>
							</span>
						</div>
						<!-- Second Row End -->
	
						<!-- Start of post container -->					
						<!-- Start of post content -->
						<div class="col-md-12 ">		
							<div id="post-inner">
								<?= $discussion->val('discussion'); ?>
							</div>
						</div>
					</div>
                    <div class="col-md-12 bottom_border"></div>
                    <!-- End of post content -->

                    <?php if ($discussion->isPublished()) { ?>
                    
					<!-- Like Widget Start -->
						<div class="col-md-12 px-2 after-comnt" id="likeUnlikeWidget">
                        	<?php include (__DIR__.'/common/comments_and_likes/topic_like_and_likers_bedge.template.php'); ?>
						</div>
					<!-- Like Widget End -->
                    <div class="col-md-12 bottom_border"></div>
                 	<!-- Start of comments Widget -->
					
					<?php include(__DIR__ . "/common/comments_and_likes/comments_container.template.php"); ?>
					
                    <?php } ?>
					<!-- End of comments Widget -->
				</div>			
				<!-- End of post container -->
			</div>
		</div>	
	</div> <!-- end of ajax div -->

    <div id="review_or_publish_discussion_modal"></div>
	<script>
		$('.event-tab').removeClass('submenuActive');
        $('.about-tab').removeClass('submenuActive');
    	$('.home-tab').addClass('submenuActive');
	</script>
