<style>
.green:before {background-color: <?= ($group && $_ZONE->val('show_group_overlay')) ? $group->val('overlaycolor') : ''; ?>;}
.timepicker{ z-index:9999 !important; }
.rsvpbtn {
	margin: 5px;
}

.rsvpbtn:disabled {
    font-weight: 900;
}

.rsvpbtn:not(:disabled) {
    background-color: floralwhite;
}
.rsvpbtn:hover:not(:disabled)  {
	box-shadow: 0 0 11px rgba(33,33,33,.4);
    color: grey;
}
.btn-outline-success{
    color: #1F7F35 !important;
}
.btn-outline-info {
    color: #117888 !important;
}
.btn-outline-danger {
    color: #DA2B3C !important;
}
.dropdown-menu.dropmenu {
    left: 50% !important;
}
.dropdown-item{
	cursor: pointer;
}
.readonly{
	color:gray;
	cursor: not-allowed !important;
}
.text-decoration-none, .text-decoration-none:hover{
	font-weight:400;
	cursor:default;
	color: inherit;
}
.modal-footer {
    background: #fff;
}
.close span:hover {
    color: #0077b5;
}

button.btn.btn-secondary:hover {
    background: #0077b5;
}
.modal-header{
	padding: 0.5rem 1rem !important;
}
.event-venue-section {
    word-wrap: break-word;
}
@media(max-width:768px) {
	.collaboration-title{
		display: flow-root;
	}

	.announcement-detail {
		text-align: center;
	}
	#event_detail_modal .modal-header .close {
    padding: 0;
    position: absolute;
	right: -11px;
    top: auto;
}
.event-venue-section .tele-title {
    margin-top: 22px;
}
}
@media(max-width:736px) {
.event-venue-section .tele-title {
    margin-top: 0px; 
}
}

.rsvp-approve-btn{
	margin-bottom: 10px;
}
</style>

<div class="modal"  id="event_detail_modal" tabindex="-1">
  	<div aria-label="<?= $event->val('eventtitle'); ?>" class="modal-dialog modal-xl modal-dialog-w1000" aria-modal="true" role="dialog">
		<div class="modal-content" style="padding: 10px 7px;">
			<div class="modal-header">
                <div class="d-flex justify-content-between col-12" style="padding:0;">
                    <div class="center-button collaboration-title mt-1">
                    <?php if($collaboratedWith) {?>
                             <?php foreach( $collaboratedWith as $cw){ ?>
                                    <a aria-label="<?= $cw->val('groupname_short'); ?> <?= $event->val('eventtitle'); ?>" id="setfocus"
                                        href="<?= Url::GetZoneAwareUrlBase($cw->val('zoneid')) . 'detail?id=' . $_COMPANY->encodeId($cw->val('groupid')) ?>" target="_self"
                                       class="modal-details mn mt-1 collaboration-button mr-2 pt-1" style="padding: 5px 0;background-color:<?= $cw->val('overlaycolor'); ?> !important;"
                                    ><?= $cw->val('groupname_short') ?? $cw->val('groupname'); ?></a>
                            <?php 	} ?>
                            <?php } elseif ($groupid) { ?>

                                    <a aria-label="<?= $group->val('groupname_short'); ?> <?= $event->val('eventtitle'); ?>" id="setfocus"
                                       href="<?= Url::GetZoneAwareUrlBase($group->val('zoneid')) . 'detail?id=' . $_COMPANY->encodeId($groupid) ?>" target="_blank"
                                       class="modal-details mn collaboration-button"
                                       style="margin-left: -6px;margin-top: 0; padding: 5px 0;background-color:<?= $group->val('overlaycolor'); ?> !important;"
                                    ><?= $group->val('groupname_short'); ?></a>
                            <?php	} else { ?>

                                    <a tabindex="-1" aria-disabled="true" aria-label="<?= $group->val('groupname_short'); ?> <?= $event->val('eventtitle'); ?>" id="setfocus"
                                       class="modal-details mn"
                                       style="pointer-events: none; margin-top: 0;padding: 5px 0;background-color:<?= $_COMPANY->getAppCustomization()['group']['group0_color'] ?> !important; cursor: not-allowed;"
                                    ><?= $group->val('groupname_short'); ?></a>
                        <?php	} ?>

                        </div>
                    <div class="share d-flex">
                    <?php if ($event->isPublished()) { ?>
                        <button aria-label="<?= gettext('Get Shareable Link'); ?>"  class="btn-no-style right-side-button" id="getShareableLink" onclick="getShareableLink('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($event->id()); ?>','2')"  data-toggle="tooltip" title="<?= gettext('Get Shareable Link'); ?>">
                            <i class="far fa-share-square btn-modal-fa-icon text-right"></i>
                        </button>

                    <?php } ?>
                        <?php if ($event->val('zoneid') == $_ZONE->id() && $_USER->canPublishContentInCompanySomething() && $event->isPublished() && !$event->hasEnded() ) { ?>
                            <button aria-label="<?= gettext('Invite Users'); ?>" class="btn-no-style right-side-button invite-event-users-form" onclick="inviteEventUsersForm('<?= $_COMPANY->encodeId($event->id()); ?>')"  data-toggle="tooltip" title="<?= gettext('Invite Users'); ?>">
                                <i  class="fa fa-envelope-open btn-modal-fa-icon"></i>
                            </button>
                        <?php } ?>

                        <button id="btn_close"  autofocus type="button" class="close text-right ml-3 right-side-button" data-dismiss="modal" aria-label="<?= gettext('close'); ?>" onclick="clearInterval(__globalIntervalVariable);">
                            <span  style="font-size: 41px;">&times;</span>
                        </button>
                    </div>
                </div>
			</div>

      		<div class="modal-body">
				<div id="replace"></div>
				
						<div class="inner-page-title m-0" style="padding:0;">
								<?php if ($event_series_name){ $addTitleBreak = 1; ?>
									<small><a class="" href="javascript:void(0);" onclick="getEventDetailModal('<?= $_COMPANY->encodeId($event->val('event_series_id')); ?>','<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId(0); ?>')" >[<?= $event->isSeriesEventHead() ? sprintf(gettext('Head of %s'),$event_series_name) : sprintf(gettext('Part of %s'),$event_series_name); ?>]</a></small>
								<?php } ?>
								<?php if ($event->val('isprivate')){ $addTitleBreak = 1; ?>
									<small style="background-color: lightyellow;">[<?= gettext("Private Event"); ?>]</small>
								<?php } ?>

								<?php if ($event->val('teamid')){ $addTitleBreak = 1;
									$team = Team::GetTeam($event->val('teamid'));

								?>
									<small style="background-color: lightyellow;">[<?= sprintf(gettext("Part of %s %s"),htmlspecialchars($team->val('team_name')),$_COMPANY->getAppCustomization()['teams']['name']); ?>]</small>
								<?php } ?>
								<?= isset($addTitleBreak) ? '<br>' : '' ?>

								<?php if ($event->isDraft()) { ?>
									<h2 style="color:red;">
										<?= $event->val('eventtitle'); ?>
										<img src="img/draft_ribbon.png" alt="Draft icon image" height="18px"/>
									</h2>
									<?php } else if ($event->isUnderReview()) { ?>
									<h2 style="color:darkorange;">
										<?= $event->val('eventtitle'); ?>
										<img src="img/review_ribbon.png" alt="Review icon image" height="18px"/>
									</h2>
									<?php } else if ($event->isAwaiting()){ ?>
										<h2 style="color: deepskyblue;" >
											<?= $event->val('eventtitle'); ?>
											<img src="img/schedule.png" alt="Schedule icon image" height="16px"/>
											&nbsp;
											<span style="font-size: 13px;"><small id="publishCountDown">[ Publish in _ _ _ ]</small></span>
											<?php if($event->val('pin_to_top') && !$event->hasEnded()){ ?>
												<i class="fa fa-thumbtack ml-1" style="font-size:small;vertical-align:super;" aria-hidden="true" title="Pinned event"></i>
											<?php } ?>
										</h2>
										<script>
											countDownTimer(<?= $_COMPANY->getMysqlDatetimeAsTimestamp($event->val('publishdate')) - time(); ?>,"<?= $_COMPANY->encodeId($event->id()); ?>",2);
										</script>
									<?php } else { ?>
									<h2>
										<?= $event->val('eventtitle'); ?>
											<?php if($event->val('pin_to_top') && !$event->hasEnded()){ ?>
											<i class="fa fa-thumbtack ml-1" style="font-size:small;vertical-align:super;" aria-hidden="true" title="Pinned event"></i>
											<?php } ?>
									</h2>
									<?php if($event->isCancelled()){ ?>
										<sup class="left-ribbon ribbon-purple">Cancelled</sup>
										<?php } ?>
									<?php } ?>
								</div>
                            </div>

						<!-- Event Firstrow Start -->
						<div class="announcement-detail" id="eventDetail<?= $_COMPANY->encodeId($event->id()); ?>" style="padding:0;">

								<!-- Second Row Start -->
								<div class="col-md-12">
									<?php if($collaboratedWithFormated){ ?>
										<p>
											<span class="collaborative_head"><?= gettext("This is a collaborative event between"); ?>:</span>&nbsp;<span class="collaborative_with"><?= $collaboratedWithFormated; ?></span>
										</p>
									<?php } ?>
									<span class="dta-tm">
										<span class="dta-tm">
							<?= ($event->val('isactive') == Event::STATUS_DRAFT || $event->val('isactive') == Event::STATUS_UNDER_REVIEW)? gettext("Created on"): ( $event->val('isactive') == Event::STATUS_AWAITING ? gettext("Scheduled to publish on") : gettext("Published on") ) ?>
                           
							<?php
							$datetime = (($event->val('isactive') == Event::STATUS_DRAFT || $event->val('isactive') == Event::STATUS_UNDER_REVIEW) ? $event->val('addedon') : $event->val('publishdate'));
							echo $_USER->formatUTCDatetimeForDisplayInLocalTimezone($datetime,true,true,true);
							?>

											<?php if ($event->val('chapterid') && empty($event->val('collaborating_groupids')) ) { ?>
												in
											<?php foreach($event->getEventChapterNames() as $chapterName){ ?>
													<span class="chapter-label">
														<i class="fas fa-globe" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($chapterName); ?>
													</span>
													&nbsp;
											<?php } ?>
										<?php } ?>

										<?php 
											if ($event->val('channelid') > 0){ 
												$ch = Group::GetChannelName($event->val('channelid'),$event->val('groupid'));        
										?>
											<span class="chapter-label ml-1" style="color:<?= $ch['colour'] ?>"><i class="fas fa-layer-group" style="color:<?= $ch['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($ch['channelname']); ?></span>
										<?php
											}
										?>
									</span>
								</div>
								<!-- Second Row End -->
								<?php
									if($event->val('form_validated') == 1 && ($event->val('collaborating_groupids_pending') || $event->val('collaborating_chapterids_pending'))){ ?>
									<div class="col-12">
										<?php 
											include_once __DIR__.'/../common/requested_group_approval_for_event_collaboration.template.php';
										?>
									</div>
								<?php } ?>
								
								<!-- Requested approval for Event collaboration End -->
								<!-- Event RSVP Disclaimers Start -->
									<?php 
										// $disclaimers array object dependency
										$subjectEvent = $event; // Event RSVP disclaimer / RSVP template dependency
										if (!empty($disclaimers) && $event->isPublished() && !$event->hasEnded()){
											include_once __DIR__.'/../common/event_rsvp_disclaimers.template.php';
										}
									?>
								<!-- Event  RSVP Disclaimers  End -->
								
									<!-- Event Joinrow Start -->
									<div class="col-12" id="joinEventRsvp<?= $_COMPANY->encodeId($event->id())?>">
										<?php 
											// $subjectEvent object dependancy
											include_once __DIR__.'/../common/join_event_rsvp.template.php';
										?>
									</div>
									<!-- Event Joinrow End -->
								<?php if($_COMPANY->getAppCustomization()['event']['volunteers'] && $event->isPublished() && !$event->hasEnded() && !empty($eventVolunteerRequests) && $event->val('rsvp_enabled')){ ?>
									<div class="col-12 p-0 m-0" id="manage_volunteer_enrollment">
										<?php include_once __DIR__.'/../common/init_event_volunteers.widget.php'; ?>
									</div>
								<?php } ?>
								

									<!-- Time a location block start -->
									<div class="col-md-12 event-venue-section">

											<span aria-label="Time" role="img" class="fa fa-clock tele-title-icon"></span>
											<div class="tele-title" style="width:100% !important">
												<p class="font-col">
													<?= $db->covertUTCtoLocalAdvance("l", "",  $event->val('start'),$_SESSION['timezone'],$_USER->val('language')); ?>
													<br>
													<?= $db->covertUTCtoLocalAdvance("M j, Y g:i a","",  $event->val('start'),$_SESSION['timezone'],$_USER->val('language')) ;?>
													-
													<?= ($event->getDurationInSeconds() > 86400 ? $db->covertUTCtoLocalAdvance("M j, Y g:i a", "",  $event->val('end'),$_SESSION['timezone'],$_USER->val('language')) : $db->covertUTCtoLocalAdvance("g:i a", "",  $event->val('end'),$_SESSION['timezone'],$_USER->val('language')))?>
													<br>
													<?= $db->covertUTCtoLocalAdvance("T", "",  $event->val('end'),$_SESSION['timezone'],$_USER->val('language')); ?>
												</p>
											</div>

											<?php if (!empty($event->val('event_contact')) && strlen($event->val('event_contact')) > 1) { ?>
											<span role='img' aria-label='<?= gettext('User');?>' class="fa fa-user tele-title-icon"></span>
											<div class="tele-title">
												<p class="font-col">
													<?= htmlspecialchars($event->val('event_contact')); ?>
												</p>
											</div>
											<?php } ?>
											<?php if (!empty($event->val('event_contact_phone_number'))) { ?>
											<span role='img' aria-label='<?= gettext('User');?>' class="fa fa-phone-alt tele-title-icon"></span>
											<div class="tele-title">
												<p class="font-col">
													<?= $event->val('event_contact_phone_number'); ?>
												</p>
											</div>
											<?php } ?>

										<?php if($event->val('event_attendence_type')!=4) { ?>
										<div class="">

											<?php if($event->val('event_attendence_type')!=1 && !empty($event->val('web_conference_link'))) { ?>
												<i aria-label="Meeting Platform" role="img" class="fa fa-laptop tele-title-icon"></i>
												<div class="tele-title">
													<a class="link_show" target="_blank" rel="noopener" href="<?= $event->getWebConferenceLink()?>" ><strong><?= $event->val('web_conference_sp'); ?></strong></a>
													&nbsp;
													<?php if ($event->val('web_conference_detail')){ ?>
														<a href="javascript:void(0);" onclick='showWebConferenceDetail()' class="link_show small">[<?= gettext("details");?>]</a>
													<?php } ?>
												</div>
                                                <div class="col-md-12 my-2 p-3" id="conference_detail" style="background-color: #efefef;display: none;">
                                                    <?=$event->val('web_conference_detail')?>
                                                </div>
											<?php } ?>
											<?php if ($event->val('event_attendence_type') !=2){
													if ($event->val('latitude')) {
														$map_location = $event->val('latitude') . ',' . $event->val('longitude');
													} else {
														$map_location = $event->val('vanueaddress');
													}
												?>
												<span aria-label="Location" role="img" class="fa fa-map-marker map-in tele-title-icon"></span>
												<div class="tele-title">
													<p class="font-col"><?= $event->val('eventvanue'); ?><a class="link_show small" target="_blank"  href="https://www.google.com/maps/?q=<?= $map_location; ?>" >&nbsp;[<?= gettext("map"); ?>]</a></p>
													<?php if (!empty($event->val('venue_room'))) { ?>
														<p><?= $event->val('venue_room'); ?></p>
													<?php } ?>
                                                    <p><?= $event->val('vanueaddress'); ?></p>
													<?php if (!empty($event->val('venue_info'))) { ?>
														<p><?= $event->val('venue_info') ?></p>
													<?php } ?>
												</div>
											<?php } ?>

										</div>
										<?php } ?>
									</div>
									<!-- Time a location block end -->

									<!-- Start event details -->
									<div class="col-md-12 evet-description">
										<div id="post-inner">
										<?= $event->val('event_description'); ?>
										</div>
										<?= $event->renderAttachmentsComponent('v4') ?>
									</div>
									<!-- End event details -->
									<!--Start show pictures of event joiners -->
									<div class="col-md-12 bg-color">
									<hr class="hr">
									<?php 
									$isAllowedToUpdateContent = $event->val('groupid') ? $_USER->canUpdateContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid'), $event->val('isactive')) : $event->loggedinUserCanUpdateEvent();
									$isAllowedToPublishContent = $event->val('groupid') ? $_USER->canPublishContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')) : $event->loggedinUserCanPublishEvent();
									$isAllowedToManageContent = $event->val('groupid') ? $_USER->canManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')) : $event->loggedinUserCanManageEvent();
									
									if($event->hasEnded() && !$event->isDraft() && !$event->isUnderReview() ){
										$hidden = "";
										if(!$event->val('followup_notes') ){
											$hidden = "hidden";
										}
									?>
										<div class="evet-description">
											<div class="text-center <?= $hidden; ?>">										
													<strong ><?= gettext('Post Event Follow-up Note'); ?></strong>
											</div>
											<div id="post-inner">
                                                <?= $event->val('followup_notes'); ?>
											</div>
										</div>
										
									<?php } ?>
									<!-- Check for RSVP settings -->
									<?php if($event->val('rsvp_enabled') && ($event->val('rsvp_display')==1 || $event->val('rsvp_display')==2 || $event->val('rsvp_display')==3)){ ?>
										<p>
											<strong><?=  $joinersCount; ?> <?= ($event->hasEnded()) ? gettext("RSVP'd") : gettext('People going') ?></strong><br><br>
										</p>
									<?php } ?>
									<?php if($event->val('rsvp_enabled') && ($event->val('rsvp_display')==2 || $event->val('rsvp_display')==3) ){ ?>
										<?php if(count($eventJoiners)){ ?>
												<?php for($p=0;$p<count($eventJoiners);$p++){ ?>
													<?= User::BuildProfilePictureImgTag($eventJoiners[$p]['firstname'], $eventJoiners[$p]['lastname'], $eventJoiners[$p]['picture'],'memberpic2',sprintf(gettext('%s Profile Picture'),$eventJoiners[$p]['firstname']));?>
												<?php }?>
										<?php } ?>
										<?php if ($event->val('rsvp_display')==3) { ?>
											<button class="btn-no-style view-rsvp-btn pull-right" onclick="loadViewEventRSVPsModal('<?= $_COMPANY->encodeId($event->val('groupid')); ?>','<?= $_COMPANY->encodeId($event->val('eventid')); ?>')" style="font-size: small;"><?= gettext("View RSVP's")?> </button>
										<?php } ?>
									<?php } ?>

								<?php if ($event->isPublished()) { ?>
										
									<?php if ($_COMPANY->getAppCustomization()['event']['likes']) { ?>
										<!-- Like Widget Start -->
										<div class="col-md-12 mt-3 p-0" id="likeUnlikeWidget">
											<?php include (__DIR__.'/../common/comments_and_likes/topic_like_and_likers_bedge.template.php'); ?>
										</div>
									<?php }?>
										<!-- Like Widget End -->
									<?php if ($_COMPANY->getAppCustomization()['event']['comments']) { ?>
										<!-- Start of comments Widget -->
										<?php include(__DIR__ . "/../common/comments_and_likes/comments_container.template.php"); ?>
									<?php } ?>
										
								<?php } ?>

									</div>
									<!--End show pictures of event joiners -->
									<div class="modal-footer">
										<button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="clearInterval(__globalIntervalVariable);"><?= gettext("Close")?></button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>


<div id="rsvps_modal"></div>

<!-- Append Multiple Data -->
<script>
	var h = window.location.hash.substr(1);
	if (!h){
		window.location.hash = "eventDetail";
	}
	
    function showWebConferenceDetail(){
        let conf_detail_div = $('#conference_detail');
        conf_detail_div.toggle();
    }
</script>

<script>
	$('.pop-identifier').each(function() {
		$(this).popConfirm({
		container: $("#manage_volunteer_enrollment"),
		});
	});
		

$('#event_detail_modal').on('shown.bs.modal', function () {	
	if( $('#setfocus').attr('aria-disabled') === 'true') {
    	$('#getShareableLink').focus();
	}else{
		$('#setfocus').eq(0).focus();
	}   

	$('.modal').addClass('js-skip-esc-key');
});

retainFocus("#event_detail_modal");
</script>