<?php
    if ($touchpoint_event) {
    $event_host        = $db->get("SELECT userid,firstname,lastname, picture FROM users where userid='".$touchpoint_event->val('userid')."'");
    $event_joiners    = $touchpoint_event->getRandomJoiners(4);
    ?>
    <style>
        .margin-left-30 {
            margin-left: 30px;
        }
    </style>
    
    <!-- Start of Next Event section -->
    <div class="container inner-background inner-background-next-event">
        <div class="row">
            <div class="col-md-12 p-0">
                <div class="row upcoming-event-row">

                <?php
                    $event = $touchpoint_event; // Veriable $event dependency
                    $isAllowedToUpdateContent = $event->val('groupid') ? $_USER->canUpdateContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid'), $event->val('isactive')) : $event->loggedinUserCanUpdateEvent();
                    $isAllowedToPublishContent = $event->val('groupid') ? $_USER->canPublishContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')) : $event->loggedinUserCanPublishEvent();
                    $isAllowedToManageContent = $event->val('groupid') ? $_USER->canManageContentInScopeCSV($event->val('groupid'), $event->val('chapterid'), $event->val('channelid')) : $event->loggedinUserCanManageEvent();
                    $showBackLink = false;
                    if (($event->isPublished() || $isAllowedToUpdateContent || $isAllowedToPublishContent || $isAllowedToManageContent) && !$team->isComplete() && !$team->isIncomplete() && !$team->isPaused()) {
                    ?>

						<div class="col-12" style="margin-top: -15px;margin-bottom: 9px;">
							<i class="dropdown-toggle fa fa-ellipsis-v col-doutd touch-point-event" tabindex="0" title="<?= gettext("event action buttons dropdown")?>" aria-label="Action button dropdown" data-toggle="dropdown"></i>
                            <ul class="dropdown-menu dropmenu">
                                <?php include(__DIR__ . "/teamevent_action_button.template.php"); ?>
                            </ul>
						</div>
				<?php	} ?>
			
                    <div class="col-sm-8 col-12 col-padding">
                        <div class="col-12">
                            <div class="col-2 col-sm-2 col-lg-2 col-md-2 col-cd text-center">
                                <?= $db->covertUTCtoLocalAdvance("M","", $touchpoint_event->val('start'),$_SESSION['timezone'],$_USER->val('language')); ?>
                                <br>
                                <?= $db->covertUTCtoLocalAdvance("d","", $touchpoint_event->val('start'),$_SESSION['timezone'],$_USER->val('language')); ?>
                            </div>
                            <div class="col-12 col-lg-10 col-md-10 col-sm-10 mt-2 mt-md-0">
                                <!-- Start Title row -->
                                <div>
                                    <a 
                                        onclick="getEventDetailModal('<?= $_COMPANY->encodeId($touchpoint_event->id()); ?>','<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId(0); ?>')"
                                        href="javascript:void(0)">
                                        <h4 class="active"><?php echo $touchpoint_event->val('eventtitle'); ?></h4>
                                    </a>
                                </div>
                                <!-- End Title row -->
                                <!-- Start Event Contact Info row -->
                                <div class="row-no-gutters">
                                    <div class="img-v">
                                    <?php if (!empty($event_host)) {
                                        echo User::BuildProfilePictureImgTag($event_host[0]['firstname'], $event_host[0]['lastname'], $event_host[0]['picture'],'memberpicture_small','Event host profile picture', $event_host[0]['userid'], 'profile_full') ;
                                    } ?>
                                    </div>

                                    <div class="dta-tm">
                                        <?php if (!empty($event_host)) { ?>
                                    &nbsp;<?= $event_host[0]['firstname']." ".$event_host[0]['lastname'];?>
                                        <?php } ?>

                                        <?php if ($touchpoint_event->val('chapterid')) { ?>
                                                in
                                            <?php foreach(explode(',',$touchpoint_event->val('chapterid')) as $chid){ ?>
                                                <?php 	$c = Group::GetChapterName($chid,$touchpoint_event->val('groupid')); ?>
                                                    <span class="chapter-label" style="color:<?= $c['colour'] ?>">
                                                        <i class="fas fa-globe" style="color:<?= $c['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($c['chaptername']); ?>
                                                    </span>
                                                    &nbsp;
                                            <?php } ?>
                                        <?php } ?>
    
                                        <?php 
                                            if ($touchpoint_event->val('channelid') > 0){
                                                $ch = Group::GetChannelName($touchpoint_event->val('channelid'),$touchpoint_event->val('groupid'));
                                        ?>
                                        <span class="chapter-label ml-1" style="color:<?= $ch['colour'] ?>"><i class="fas fa-layer-group" style="color:<?= $ch['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($ch['channelname']); ?></span>
                                        <?php
                                            }
                                        ?>
                                    </div>
                                </div>
                                <!-- End Event Contact row -->
                            </div>
                        </div>
                        <div class="col-12">
                            <p class="upcoming-event-content">
                                <?php echo substr(strip_tags($touchpoint_event->val('event_description')),0,300)."..."; ?>
                            </p>
                        </div>
                        <div class="col-12">
    
                            <?php for($i=0;$i<count($event_joiners);$i++){ ?>
                                <?= User::BuildProfilePictureImgTag($event_joiners[$i]['firstname'], $event_joiners[$i]['lastname'],$event_joiners[$i]['picture'],'user-img')?>
                            <?php } ?>
    
                            <span><?= $touchpoint_event->getJoinersCount().' '.  gettext("People going"); ?></span>
                        </div>
                        <div class="clearfix">
                        </div>
                    </div>
                    <div class="col-sm-4 col-12">
                        <button class="form-control upcoming-event-attend-btn margin-left-30"
                            onclick="getEventDetailModal('<?= $_COMPANY->encodeId($touchpoint_event->id()); ?>','<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId(0); ?>')">
                            <?= ($touchpoint_event->inProgress())? gettext('Attend') : gettext('RSVP') ?>
                        </button>
                        <div>
                            <span aria-label="Time" role="img" class="fa fa-clock tele-title-icon margin-left-30"></span>
                            <div class="tele-title margin-left-30">
                                <p class="font-col"><?= $db->covertUTCtoLocalAdvance('g:i a M d'," T",
                                    $touchpoint_event->val('start'), $_SESSION['timezone'],$_USER->val('language')); ?></p>
                            </div>
                        </div>
                    <?php if($touchpoint_event->val('event_attendence_type')!=4) { ?>
                        <div>
                            <?php if($touchpoint_event->val('event_attendence_type')!=1) { ?>
                                <i aria-label="Meeting Platform" role="img" class="fa fa-laptop tele-title-icon  margin-left-30" aria-hidden="true"></i>
                                <div class="tele-title  margin-left-30">
                                    <p class="font-col"><?= $touchpoint_event->val('web_conference_sp'); ?></p>
                                </div>
                            <?php } ?>
                            <?php if ($touchpoint_event->val('event_attendence_type') !=2){ ?>
                                <span aria-label="Location" role="img" class="fa fa-map-marker map-in tele-title-icon  margin-left-30"></span>
                                <div class="tele-title  margin-left-30">
                                    <p class="font-col"><?= $touchpoint_event->val('eventvanue'); ?></p>
                                    <p><?= $touchpoint_event->val('vanueaddress'); ?></p>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    </div>
                    
                </div>
                <br/>
            </div>
        </div>
    </div>
    <!-- End of Next Event Section -->
    <?php
        }
    ?>