<style>
.close span:hover {
    color: #0077b5;
}

.btn-secondary:hover {
    background: #0077b5;
}
.comment-comment-section {
    margin-top: 0px!important;
    margin-left: 18px;
}
.modal-header{
	padding: 0.5rem 1rem !important;
}
.like-padding {
    margin: 15px 0 15px 0px;
}
@media (max-width: 767px){
	.announcement-detail {
        padding: 0 16px
}
}

</style>
<div class="modal" tabindex="-1" id="post_detail_modal">
  	<div aria-label="<?= $post->val('title'); ?>" class="modal-dialog modal-xl modal-dialog-w1000" role="dialog" aria-modal="true">
		<div class="modal-content">
			<div class="modal-header">
			<?php	if($groupid){	?>
                <a href="detail?id=<?= $_COMPANY->encodeId($group->val('groupid')); ?>" class="modal-details mn collaboration-button" style="margin: 7px 0;background-color:<?= $group->val('overlaycolor'); ?> !important;">
					<?= $group->val('groupname'); ?>
				</a>
				<?php	}else{	?>
                <a role="button" tabindex="-1" aria-disabled="true" class="mn" style="pointer-events: none; background-color:<?= $_COMPANY->getAppCustomization()['group']['group0_color'] ?> !important; cursor: not-allowed;margin: 7px 0;">
					<?= $group->val('groupname_short'); ?>
				</a>
				<?php	}	?>
             <div class="d-flex justify-content-end">
			    <?php if ($post->isActive()) { ?>
                <button aria-label="<?= gettext('Get Shareable Link'); ?>" class="btn-no-style" onclick="getShareableLink('<?= $_COMPANY->encodeId($group->val('groupid')); ?>','<?= $_COMPANY->encodeId($post->id()); ?>','1')"  id="getShareableLink" data-toggle="tooltip" title="<?= gettext('Get Shareable Link'); ?>">
                    <i class="far fa-share-square btn-modal-fa-icon text-right"></i>
                </button>
			    <?php } ?>
				 <button id="btn_close" type="button"  class="close text-right ml-3" data-dismiss="modal" aria-label="<?= gettext('Close'); ?>" onclick="clearInterval(__globalIntervalVariable);">
					 <span aria-hidden="true" style="font-size: 45px;">&times;</span>
				 </button>
			 </div>
			</div>
      		<div class="modal-body">
			  	<div class="row">
				  	<div class="col-md-12">	
							<div class="col-md-12">
								<div class="inner-page-title">
								<?php if ($post->isDraft()) { ?>
									<h2 style="color:red;" >
										<?= $post->val('title'); ?>
										<img src="img/draft_ribbon.png" alt="Draft icon image" height="16px"/>
									</h2>
								<?php } else if ($post->isUnderReview()) { ?>
									<h2 style="color:darkorange;" >
										<?= $post->val('title'); ?>
										<img src="img/review_ribbon.png" alt="Review icon image" height="20px"/>
									</h2>
								<?php }  else if ($post->isAwaiting()){ ?>
									<h2 style="color:deepskyblue;" >
										<?= $post->val('title'); ?>
										<img src="img/schedule.png" alt="Schedule icon image" height="16px"/>
										&nbsp;
										<span style="font-size: 13px;"><small id="publishCountDown">[ Publish in _ _ _ ]</small></span>
									</h2>
									<script>
										countDownTimer(<?= $_COMPANY->getMysqlDatetimeAsTimestamp($post->val('publishdate')) - time(); ?>,"<?= $_COMPANY->encodeId($post->id()); ?>",1);
									</script>
								<?php } else { ?>
									<h2 >
										<?= $post->val('title'); ?>
										<?php if($post->val('pin_to_top')){ ?>
											<i class="fa fa-thumbtack ml-1" style="font-size:small;vertical-align:super;" aria-hidden="true"></i>
										<?php } ?>
									</h2>
								<?php } ?>
								</div>
							</div>

							
							<!-- Post Menu Start -->
							<?php
							$isAllowedToUpdateContent = true;
							$isAllowedToPublishContent = true;
							$isAllowedToManageContent = true;
							
							if (!$_USER->isAdmin()){
								$isAllowedToUpdateContent = $_USER->canUpdateContentInScopeCSV($post->val('groupid'),$post->val('chapterid'),$post->val('channelid'),$post->val('isactive'));
								$isAllowedToPublishContent = $_USER->canPublishContentInScopeCSV($post->val('groupid'),$post->val('chapterid'),$post->val('channelid'));
								$isAllowedToManageContent = $_USER->canManageContentInScopeCSV($post->val('groupid'),$post->val('chapterid'),$post->val('channelid'));
							} ?>
							<!-- Post Menu End -->
							<!--div class="col-md-1">
								<span onclick="goBack();"><i class="fa fa-arrow-circle-left" aria-hidden="true"></i></span>
							</div-->
						
						<!-- Post Title Row End -->
						<div class="announcement-detail">
							<!-- Second Row Start -->
							<div class="row">
								<div class="col-md-12 p-0">
									<span class="dta-tm">
									<?= ($post->val('isactive') == Post::STATUS_DRAFT || $post->val('isactive') == Post::STATUS_UNDER_REVIEW)? gettext("Created on "): ( $post->val('isactive') == Post::STATUS_AWAITING ? gettext("Scheduled to publish on") : gettext("Published on ") ) ?>
									<?php
									$datetime = (($post->val('isactive') == Post::STATUS_DRAFT || $post->val('isactive') == Post::STATUS_UNDER_REVIEW) ? $post->val('postedon') : $post->val('publishdate'));
									echo $_USER->formatUTCDatetimeForDisplayInLocalTimezone($datetime,true,true,true);
									?>										
										
									<?php if (!empty($post->val('chapterid'))) { ?>
										in
										<?php foreach(explode(',', $post->val('chapterid')) as $chid){ ?>
												<?php 	$c = Group::GetChapterName($chid,$post->val('groupid')); ?>
												<span class="chapter-label" style="color:<?= $c['colour'] ?>">
													<i class="fas fa-globe" style="color:<?= $c['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($c['chaptername']); ?>
												</span>
												&nbsp;

										<?php } ?>
									<?php } ?>
			
											<?php 
												if ($post->val('channelid') > 0){ 
													$ch = Group::GetChannelName($post->val('channelid'),$post->val('groupid'));        
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
								<div class="col-md-12 p-0">		
									<div id="post-inner">
										<?= $post->val('post'); ?>
									</div>
									<?= $post->renderAttachmentsComponent('v3') ?>
								</div>
							</div>
						
							<!-- End of post content -->

						<?php if ($post->isPublished()) { ?>
							<?php if ($_COMPANY->getAppCustomization()['post']['likes']) { ?>
								<!-- Like Widget Start -->
								<div class="col-md-12 p-0 after-comnt" id="likeUnlikeWidget">
									<?php include (__DIR__.'/../common/comments_and_likes/topic_like_and_likers_bedge.template.php'); ?>
								</div>
								<!-- Like Widget End -->
							<?php } ?>
						
							<?php if ($_COMPANY->getAppCustomization()['post']['comments']) { ?>
								<!-- Start of comments Widget -->
								
								<?php include(__DIR__ . "/../common/comments_and_likes/comments_container.template.php"); ?>
								
								<!-- End of comments Widget -->
							<?php } ?>
						<?php } ?>
						</div>			
						<!-- End of post container -->
					</div>
				</div>
				<div id="review_or_publish_announcement_modal"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="clearInterval(__globalIntervalVariable);"><?= gettext("Close")?></button>
			</div>
    	</div>
  	</div>
</div>
	<script>
		var h = window.location.hash.substr(1);
		if (!h){
			window.location.hash = "announcementDetail";
		}
		$('.event-tab').removeClass('submenuActive');
        $('.about-tab').removeClass('submenuActive');
    	$('.home-tab').addClass('submenuActive');
	</script>

<script>
	$('#post_detail_modal').on('shown.bs.modal', function () {
		$('.modal').addClass('js-skip-esc-key');
		let buttonElement = $('.modal-details');
		if(buttonElement.length > 0){
			buttonElement.focus();
		}else{
			$('#getShareableLink').focus();
		}
	});
	
	retainFocus("#post_detail_modal");
</script>