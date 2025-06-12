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
.after-comnt {
    border-top: 1px solid #cccc !important;
    border-bottom: 1px solid #cccc !important;
}
.like-padding {
    margin: 15px 0 15px 0px;
}
@media (max-width: 767px){
	.announcement-detail {
        padding: 0 16px
}
}
#discussion_detail_modal button:focus {
  outline: #000 solid 2px;
}
#discussion_detail_modal .three-dot:focus {
     border: none; 
}
div#swal2-content {   
    max-width: 450px;   
}

</style>
<div class="modal" tabindex="-1" id="discussion_detail_modal">
  	<div aria-label="<?= $discussion->val('title'); ?>" class="modal-dialog modal-xl modal-dialog-w1000" aria-modal="true" role="dialog">
		<div class="modal-content">
			<div class="modal-header">
			<?php	if($groupid){	?>
                <a href="detail?id=<?= $_COMPANY->encodeId($group->val('groupid')); ?>" class="modal-details mn collaboration-button" style="margin: 7px 0;background-color:<?= $group->val('overlaycolor'); ?> !important;">
					<?= $group->val('groupname'); ?>
				</a>
				<?php	}else{	?>
                <a role="button" tabindex="-1" aria-disabled="true" href="javascript:void(0);" class="modal-details mn" style="background-color:<?= $_COMPANY->getAppCustomization()['group']['group0_color'] ?> !important; cursor: not-allowed;margin: 7px 0;">
					<?= $group->val('groupname_short'); ?>
				</a>
				<?php	}	?>
             <div class="d-flex justify-content-end">
			    <?php if ($discussion->isActive()) { ?>
                <button aria-label="<?= gettext('Get Shareable Link'); ?>" class="btn-no-style" onclick="getShareableLink('<?= $_COMPANY->encodeId($group->val('groupid')); ?>','<?= $_COMPANY->encodeId($discussion->id()); ?>','6')"  id="getShareableLink" data-toggle="tooltip" title="<?= gettext('Get Shareable Link'); ?>">
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
								<div id="popover-box">
								<div class="inner-page-title">
									<div class="col-md-11 ml-0 pl-0">
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
											countDownTimer(<?= $_COMPANY->getMysqlDatetimeAsTimestamp($discussion->val('publishdate')) - time(); ?>,"<?= $_COMPANY->encodeId($discussion->id()); ?>",1);
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
									<div class="col-md-1">
										<button role="button" aria-label="Discussions Action button dropdown" class="dropdown-toggle btn-no-style pull-right" data-toggle="dropdown" title="<?= gettext("discussion action buttons dropdown")?>" ><i class="fa fa-ellipsis-v col-doutd" ></i></button>
										<ul class="dropdown-menu dropmenu">
											<?php include(__DIR__ . "/../discussions/discussion_action_button.template.php"); ?>
										</ul>
									</div>
								</div>
								</div>
							</div>

							
							<!-- Discussion Menu Start -->
							<?php
							$isAllowedToUpdateContent = true;
							$isAllowedToPublishContent = true;
							$isAllowedToManageContent = true;
							
							if ($_USER->id()!=$discussion->val('createdby')){
								$isAllowedToUpdateContent = $_USER->canUpdateContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid'),$discussion->val('isactive'));
								$isAllowedToPublishContent = $_USER->canPublishContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid'));
								$isAllowedToManageContent = $_USER->canManageContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid'));
							} ?>
							<!-- Discussion Menu End -->
							<!--div class="col-md-1">
								<span onclick="goBack();"><i class="fa fa-arrow-circle-left" aria-hidden="true"></i></span>
							</div-->
						
						<!-- Discussion Title Row End -->
						<div class="announcement-detail">
							<!-- Second Row Start -->
							<div class="row">
								<div class="col-md-12 p-0">
									<span class="dta-tm">
									<?= ($discussion->val('isactive') == Discussion::STATUS_DRAFT || $discussion->val('isactive') == Discussion::STATUS_UNDER_REVIEW)? gettext("Created on "): ( $discussion->val('isactive') == Discussion::STATUS_AWAITING ? gettext("Scheduled to post on") : gettext("Posted on ") ) ?>
									<?php
									$datetime = (($discussion->val('isactive') == Discussion::STATUS_DRAFT || $discussion->val('isactive') == Discussion::STATUS_UNDER_REVIEW) ? $discussion->val('modifiedon') : $discussion->val('modifiedon'));
									echo $_USER->formatUTCDatetimeForDisplayInLocalTimezone($datetime,true,true,true);
									?>										
										
									<?php if (!empty($discussion->val('chapterid'))) { ?>
										in
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
								<div class="col-md-12 p-0">		
									<div id="post-inner">
										<?= $discussion->val('discussion'); ?>
									</div>
								</div>
							</div>
						
							<!-- End of post content -->

							<?php if ($discussion->isPublished()) { ?>
							
							<!-- Like Widget Start -->
								<div class="col-md-12 p-0 after-comnt" id="likeUnlikeWidget">
									<?php include (__DIR__.'/../common/comments_and_likes/topic_like_and_likers_bedge.template.php'); ?>
								</div>
							<!-- Like Widget End -->
						
							<!-- Start of comments Widget -->
							
							<?php include(__DIR__ . "/../common/comments_and_likes/comments_container.template.php"); ?>
							
							<?php } ?>
							<!-- End of comments Widget -->
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
    	$('.getGroupDiscussions').addClass('submenuActive');
	</script>
<script>
$('#discussion_detail_modal').on('shown.bs.modal', function () {
	$('.modal').addClass('js-skip-esc-key');
   	$('.modal-details').eq(0).trigger('focus');
});

</script>
<script>
	$('.pop-identifier').each(function() {
		$(this).popConfirm({
		container: $("#popover-box"),
		});
	});
		
	retainFocus("#discussion_detail_modal");
</script>