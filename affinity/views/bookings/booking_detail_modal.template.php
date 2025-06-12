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
@media(max-width:768px) {
	.collaboration-title{
		display: flow-root;
	}

	.announcement-detail {
		text-align: center;
	}
	#booking_detail_modal .modal-header .close {
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

<div class="modal"  id="booking_detail_modal" tabindex="-1">
  	<div aria-label="<?= $event->val('eventtitle'); ?>" class="modal-dialog modal-xl modal-dialog-w1000" aria-modal="true" role="dialog">
		<div class="modal-content" style="padding: 10px 7px;">
			<div class="modal-header">
                <div class="d-flex justify-content-between col-12" style="padding:0;">
                    <div class="center-button collaboration-title mt-1">
                        <h1><?= gettext('Booking Detail'); ?></h1>
                    </div>
                    <div class="share d-flex">
                        <button id="btn_close"  autofocus type="button" class="close text-right ml-3 right-side-button" data-dismiss="modal" aria-label="<?= gettext('close'); ?>" onclick="clearInterval(__globalIntervalVariable);">
                            <span  style="font-size: 41px;">&times;</span>
                        </button>
                    </div>
                </div>
			</div>

      		<div class="modal-body">
				        <div id="replace"></div>
						<div class="inner-page-title m-0" style="padding:0;">
								<?php if ($event->val('isprivate')){ $addTitleBreak = 1; ?>
									<small style="background-color: lightyellow;">[<?= gettext("Private Event"); ?>]</small>
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
                                        <!-- <hr class="hr"> -->
                                        <?php if (0 && $event->isPublished()) { ?>
                                                
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
		

$('#booking_detail_modal').on('shown.bs.modal', function () {	
	if( $('#setfocus').attr('aria-disabled') === 'true') {
    	$('#getShareableLink').focus();
	}else{
		$('#setfocus').eq(0).focus();
	}   

	$('.modal').addClass('js-skip-esc-key');
});

retainFocus("#booking_detail_modal");
</script>