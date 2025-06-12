<?php
    if ($next_event) {
    $event_host        = $db->get("SELECT userid,firstname,lastname, picture FROM users where userid='".$next_event->val('userid')."'");
    $event_joiners    = $next_event->getRandomJoiners(4);
    ?>
    <style>
        .margin-left-30 {
            margin-left: 30px;
        }
    </style>
    
    <!-- Start of Next Event section -->
    <div class="container inner-background inner-background-next-event">
        <div class="row">
    
            <div class="col-md-12">
                <div class="col-md-6 col-xs-12">
                    <div class="inner-page-title">
                        <h3><?= gettext('Upcoming Event'); ?></h3>
                    </div>
                </div>


                <div class="col-md-6 col-xs-12">
                    <div class="innar-page-right-button">
                        <a class="btn btn-affinity" onclick="getEvent('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>');"><?= gettext('See All'); ?></a>
                    </div>
                </div>

            </div>
            <div class="col-md-12">
                <div class="row upcoming-event-row">
                    <div class="col-sm-8 col-12 col-padding">
                        <div class="col-lg-12">
                            <div class="col-12 col-sm-2 col-lg-2 col-md-2 col-cd text-center">
                                <?= $db->covertUTCtoLocalAdvance("M","", $next_event->val('start'),$_SESSION['timezone'],$_USER->val('language')); ?>
                                <br>
                                <?= $db->covertUTCtoLocalAdvance("d","", $next_event->val('start'),$_SESSION['timezone'],$_USER->val('language')); ?>
                            </div>
                            <div class="col-12 col-lg-10 col-md-10 col-sm-10">
                                <!-- Start Title row -->
                                <div>
                                    <a href="eventview?id=<?= $_COMPANY->encodeId($next_event->id()).$url_chapter_channel_suffix;?>">
                                        <h4 class="active"><?php echo $next_event->val('eventtitle'); ?></h4>
                                    </a>
                                </div>
                                <!-- End Title row -->
                                <!-- Start Event Contact Info row -->
                                <div class="row-no-gutters">
                                    <div class="img-v">
                                    <?php if (!empty($event_host)) {
                                        echo User::BuildProfilePictureImgTag($event_host[0]['firstname'], $event_host[0]['lastname'], $event_host[0]['picture'],'user-img','Event host profile picture') ;
                                    } ?>
                                    </div>
    
                                    <span class="dta-tm">
                                        <?php if (!empty($event_host)) { ?>
                                    &nbsp;<?= $event_host[0]['firstname']." ".$event_host[0]['lastname'];?>
                                        <?php } ?>

                                        <?php if ($next_event->val('chapterid')) { ?>
                                                in
                                            <?php foreach(explode(',',$next_event->val('chapterid')) as $chid){ ?>
                                                <?php 	$c = Group::GetChapterName($chid,$next_event->val('groupid')); ?>
                                                    <span class="chapter-label" style="color:<?= $c['colour'] ?>">
                                                        <i class="fas fa-globe" style="color:<?= $c['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($c['chaptername']); ?>
                                                    </span>
                                                    &nbsp;
                                            <?php } ?>
                                        <?php } ?>
    
                                        <?php 
                                            if ($next_event->val('channelid') > 0){ 
                                                $ch = Group::GetChannelName($next_event->val('channelid'),$next_event->val('groupid'));        
                                        ?>
                                        <span class="chapter-label ml-1" style="color:<?= $ch['colour'] ?>"><i class="fas fa-layer-group" style="color:<?= $ch['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($ch['channelname']); ?></span>
                                        <?php
                                            }
                                        ?>
                                    </span>
                                </div>
                                <!-- End Event Contact row -->
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <p class="upcoming-event-content">
                                <?php echo substr(strip_tags($next_event->val('event_description')),0,300)."..."; ?>
                            </p>
                        </div>
                        <div class="col-lg-12">
    
                            <?php for($i=0;$i<count($event_joiners);$i++){ ?>
                                <?= User::BuildProfilePictureImgTag($event_joiners[$i]['firstname'], $event_joiners[$i]['lastname'],$event_joiners[$i]['picture'],'user-img')?>
                            <?php } ?>
    
                            <span><?= $next_event->getJoinersCount().' '.  gettext("going"); ?></span>
                        </div>
                        <div class="clearfix">
                        </div>
                    </div>
                    <div class="col-sm-4 col-12">
                        <button class="form-control upcoming-event-attend-btn margin-left-30"
                                onclick="location.href='eventview?id=<?= $_COMPANY->encodeId($next_event->id()).$url_chapter_channel_suffix;?>'">
                            <?= ($next_event->inProgress())? gettext('Attend') : gettext('RSVP') ?>
                        </button>
                        <div>
                            <span aria-label="Time" role="img" class="fa fa-clock tele-title-icon margin-left-30"></span>
                            <div class="tele-title margin-left-30">
                                <p class="font-col"><?= $db->covertUTCtoLocalAdvance('g:i a M d',"",
                                    $next_event->val('start'), $_SESSION['timezone'],$_USER->val('language')); ?></p>
                            </div>
                        </div>
                    <?php if($next_event->val('event_attendence_type')!=4) { ?>
                        <div>
                            <?php if($next_event->val('event_attendence_type')!=1) { ?>
                                <i aria-label="Meeting Platform" role="img" class="fa fa-laptop tele-title-icon  margin-left-30" aria-hidden="true"></i>
                                <div class="tele-title  margin-left-30">
                                    <p class="font-col"><?= $next_event->val('web_conference_sp'); ?></p>
                                </div>
                            <?php } ?>
                            <?php if ($next_event->val('event_attendence_type') !=2){ ?>
                                <span aria-label="Location" role="img" class="fa fa-map-marker map-in tele-title-icon  margin-left-30"></span>
                                <div class="tele-title  margin-left-30">
                                    <p class="font-col"><?= $next_event->val('eventvanue'); ?></p>
                                    <p><?= $next_event->val('vanueaddress'); ?></p>
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