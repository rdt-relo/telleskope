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
.close span:hover {
    color: #0077b5;
}

.btn-secondary:hover {
    background: #0077b5;
}
.modal.show{
    padding-right: -1px !important;
}
.modal-header{
	padding: 0.5rem 1rem !important;
}

@media(max-width:768px) {
    .modal-body {
    text-align: center;
}
.mn{
    margin: 0 !important;
}
}
</style>

<div class="modal" tabindex="-1" id="event_detail_modal">
  	<div aria-label="<?= $event->val('eventtitle'); ?>" class="modal-dialog modal-xl modal-dialog-w1000" aria-modal="true" role="dialog">
		<div class="modal-content"style="padding: 0 7px;">
			<div class="modal-header">
			<div class="center-button collaboration-title">
			<?php if( $collaboratedWith) { ?>
                     <?php foreach( $collaboratedWith as $cw){ ?>
                        	<a href="detail?id=<?= $_COMPANY->encodeId($cw->val('groupid')); ?>" class="modal-details mn  collaboramargin: 8px 0 0 0;tion-button mr-2 " style="padding: 4px 0;background-color:<?= $cw->val('overlaycolor'); ?> !important;"><?= $cw->val('groupname_short') ?? $cw->val('groupname'); ?>
                            </a>
							
                    <?php 	} ?>
                    <?php } elseif ($groupid) { ?>
                        
                            <a href="detail?id=<?= $_COMPANY->encodeId($groupid); ?>" class="modal-details mn  " style="margin: 8px 0 0 0;padding: 4px 0;background-color:<?= $group->val('overlaycolor'); ?> !important;">
                                <?= $group->val('groupname_short'); ?>
                            </a>
                	<?php	} else { ?>
                        
                            <a class="modal-details mn  " style="margin: 8px 0 0 0;padding: 4px 0;background-color:<?= $_COMPANY->getAppCustomization()['group']['group0_color'] ?> !important; cursor: not-allowed;">
                                <?= $group->val('groupname_short'); ?>
                            </a>
				<?php	} ?>
				
				</div>

				<div class="share ">
                <?php if ($event->isPublished()) { ?>
                    <button aria-label="<?= gettext('Get Shareable Link'); ?>"  class="btn-no-style" onclick="getShareableLink('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($event->id()); ?>','2')" id="getShareableLink" data-toggle="tooltip" title="<?= gettext('Get Shareable Link'); ?>">
                        <i class="far fa-share-square btn-modal-fa-icon text-right"></i>
                    </button>
                <?php } ?>
					<button id="btn_close" type="button" class="close text-right ml-3" data-dismiss="modal" aria-label="Close" onclick="clearInterval(__globalIntervalVariable);">
						<span  style="font-size: 45px;">&times;</span>
					</button>
				</div>
			</div>
      		<div class="modal-body">
				<div id="replace"></div> 
				<div class="row row-no-gutters">
                    <div class="col-md-12">
                        <div class="col-md-10 col-padding">
                            <div class="inner-page-title" style="padding:0;">
                                <?php if ($event_series_name){ $addTitleBreak = 1; ?>
                                    <small>[<?= $event->isSeriesEventHead() ? sprintf(gettext('Head of %s'),$event_series_name) : sprintf(gettext('Part of %s'),$event_series_name); ?>]</small>
                                <?php } ?>
                                <?php if ($event->val('isprivate')){ $addTitleBreak = 1; ?>
                                    <small style="background-color: lightyellow;">[<?= gettext("Private Event"); ?>]</small>
                                <?php } ?>
                                <?= isset($addTitleBreak) ? '<br>' : '' ?>
    
                                <?php if ($event->isDraft()) { ?>
                                    <h3 style="color:red;" >
                                        <?= $event->val('eventtitle'); ?>
                                        <img src="img/draft_ribbon.png" alt="Draft icon image" height="18px"/>
                                    </h3>
                                    <?php } else if ($event->isUnderReview()) { ?>
                                    <h3 style="color:darkorange;" >
                                        <?= $event->val('eventtitle'); ?>
                                        <img src="img/review_ribbon.png" alt="Review icon image" height="20px"/>
                                    </h3>
                                    <?php } else if ($event->isAwaiting()){ ?>
                                        <h3 style="color: deepskyblue;" >
                                            <?= $event->val('eventtitle'); ?>
                                            <img src="img/schedule.png" alt="Schedule icon image" height="16px"/>
                                            &nbsp;
                                            <span style="font-size: 13px;"><small id="publishCountDown">[ Publish in _ _ _ ]</small></span>
                                        </h3>
                                        <script>
                                            countDownTimer(<?= $_COMPANY->getMysqlDatetimeAsTimestamp($event->val('publishdate')) - time(); ?>,"<?= $_COMPANY->encodeId($event->id()); ?>",2);
                                        </script>
                                    <?php } else { ?>
                                    <h3 >
                                        <?= $event->val('eventtitle'); ?>
                                    </h3>
                                        <?php if($event->isCancelled()){ ?>
										   <sup class="left-ribbon ribbon-purple">Cancelled</sup>
										<?php } ?>
                                    <?php } ?>
                            </div>
                        </div>			
                            <!-- Event Menu Start -->
                        <?php
                            $isAllowedToUpdateContent = $event->val('groupid') ? $_USER->canUpdateContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid'), $event->val('isactive')) : $event->loggedinUserCanUpdateEvent();
                            $isAllowedToPublishContent = $event->val('groupid') ? $_USER->canPublishContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')) : $event->loggedinUserCanPublishEvent();
                            $isAllowedToManageContent = $event->val('groupid') ? $_USER->canManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')) : $event->loggedinUserCanManageEvent();
                        ?>
                    
                </div>
                    <div class="col-md-12">
                        <div id="post-inner">
                            <?= $event->val('event_description'); ?>
                        </div>
                    </div>

                    <div class="col-md-12">
                    <?php if ($event->hasEnded() && !$event->isDraft() && !$event->isUnderReview() ){?>
                        <?php if ($event->val('followup_notes')){ ?>
                        <hr class="hr">
                        <div class="text-center"><strong ><?= gettext("Event Series Follow-up Note");?></strong></div>
                        <div id="post-inner">
                            <?= $event->val('followup_notes'); ?>
                        </div>
                        <?php } ?>
                    <?php } ?>
                    </div>

                    <div class="col-md-12 mt-5">
                    <div style="text-align: center; border-top: 1.5px dashed rgb(185, 182, 182)"><strong style="display: inline-block; position: relative; top: -15px; background-color: white; padding: 0px 10px"><?= gettext("Event Series"); ?></strong></div>
                    </div>

                    <?php include(__DIR__ . "/event_series_events.template.php"); ?>
                </div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="clearInterval(__globalIntervalVariable);">Close</button>
			</div>
    	</div>
  	</div>
</div>

<div id="rsvps_modal"></div>

<!-- Append Multiple Data -->
<script>
	$(document).ready(function(){	
		$(function () {
			$('[data-toggle="popover"]').popover({html:true, placement: "top"});  
		})
	});
    function showWebConferenceDetail(eid){
        let conf_detail_div = $('#conference_detail_'+eid);
        conf_detail_div.toggle();
    }
$('#event_detail_modal').on('shown.bs.modal', function () {
   $('.modal-details').eq(0).trigger('focus');
});
</script>
