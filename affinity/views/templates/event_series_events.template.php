<style>
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
    #post-inner p {
    margin: 0 0 9px 0;
    }
    .pastEventCls
    {
      display:none;
    }
</style>
<?php
$evtN = 0;
foreach($getSeriesAllEvents as $subevent){ 

    if (($event->isPublished() && !$subevent->isPublished()) )
        continue; // Skip unpublished events.

    $disclaimers = Disclaimer::GetDisclaimersByIdCsv($subevent->val('disclaimerids'));
    $lockOptions = false;
    $lockMessage = '';
    if ($subevent->isPublished() && !$subevent->hasEnded() && !Disclaimer::IsAllWaiverAccepted($subevent->val('disclaimerids'),$subevent->val('eventid'))){
        $lockOptions = true;
        $lockMessage = gettext('In order to RSVP for this Event please accept the Event Waivers above.');
    }
    if($evtN == 0 && $subevent->hasEnded())
    { ?>
        <a href="#" id="ShowHidePastEvents"><?= gettext("Show Past Events");?></a>
    <?php   
     $evtN++; 
    } ?>

    <div class="mt-3 pt-3 pb-4 <?php echo ($subevent->hasEnded())?'pastEventCls':''; ?>" style="border:1px solid rgb(226, 224, 224); width:100%" >
        <div class="col-md-12" >
            <div class="col-md-10 col-padding">
                <div class="inner-page-title">
                    <?php if ($subevent->val('isprivate')){ $addTitleBreak = 1; ?>
                        <small style="background-color: lightyellow;">[<?= gettext("Private Event"); ?>]</small>
                    <?php } ?>
                    <?= isset($addTitleBreak) ? '<br>' : '' ?>
                    <a  href="javascript:void(0);" onclick="getEventDetailModal('<?= $_COMPANY->encodeId($subevent->val('eventid')); ?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>')">
                    <?php if ($subevent->isDraft()) { ?>
                        <h4 style="color:red;" >
                            <?= $subevent->val('eventtitle'); ?>
                            <img src="img/draft_ribbon.png" alt="Draft icon image" height="18px"/>
                        </h4>
                    <?php } else if ($subevent->isUnderReview()){ ?>
                        <h4 style="color:darkorange;" >
                            <?= $subevent->val('eventtitle'); ?>
                            <img src="img/review_ribbon.png" alt="Draft icon image" height="18px"/>
                        </h4>
                        <?php } else if ($subevent->isAwaiting()){ ?>
                            <h4 style="color: deepskyblue;" >
                                <?= $subevent->val('eventtitle'); ?>
                                <img src="img/schedule.png" alt="Schedule icon image" height="16px"/>
                                &nbsp;
                                <span style="font-size: 13px;"><small id="publishCountDown">[ Publish in _ _ _ ]</small></span>
                            </h4>
                            <script>
                                countDownTimer(<?= $_COMPANY->getMysqlDatetimeAsTimestamp($subevent->val('publishdate')) - time(); ?>,"<?= $_COMPANY->encodeId($subevent->id()); ?>",2);
                            </script>
                        <?php } else { ?>
                        <h4 >
                            <?= $subevent->val('eventtitle'); ?>
                        </h4>
                        <?php } ?>
                    </a>
                </div>
            </div>
        </div>
        <!-- Event Firstrow Start -->
        <div class="announcement-detail"  id="eventDetail<?= $_COMPANY->encodeId($subevent->id()); ?>" >
            <div class="row">	
                <?php 
                    // $disclaimers array object dependency
                    $subjectEvent = $subevent; // Event RSVP template dependency
                    if (!empty($disclaimers)  && $subevent->isPublished() && !$subevent->hasEnded()){
                        include __DIR__.'/../common/event_rsvp_disclaimers.template.php';
                    } 
                ?>
                <!-- Event Joinrow Start -->
                <div class="col-12" id="joinEventRsvp<?= $_COMPANY->encodeId($subevent->id())?>">
                    <?php 
                        // $subjectEvent object dependancy
                        include __DIR__.'/../common/join_event_rsvp.template.php';
                    ?>
                </div> 
                <!-- Event Joinrow End -->

                <!-- Time a location block start -->
                <div class="col-md-12">
                    <div class="col-md-6 col-xs-12 p-0">
                        <span aria-label="Time" role="img" class="fa fa-clock tele-title-icon"></span>
                        <div class="tele-title">
                            <p class="font-col">

                            <?=
                                sprintf(gettext("%s From %s to %s"),
                                    $db->covertUTCtoLocalAdvance("l M j, Y","",  $subevent->val('start'),$_SESSION['timezone'],$_USER->val('language')),
                                    $db->covertUTCtoLocalAdvance("g:i a T","",  $subevent->val('start'),$_SESSION['timezone'],$_USER->val('language')),($subevent->getDurationInSeconds() > 86400 ? $db->covertUTCtoLocalAdvance("M j, Y g:i a", "",  $subevent->val('end'),$_SESSION['timezone'],$_USER->val('language')) : $db->covertUTCtoLocalAdvance("g:i a", "",  $subevent->val('end'),$_SESSION['timezone'],$_USER->val('language')))
                                )
                            ?>
                            </p>
                        </div>

                        <?php if (!empty($subevent->val('event_contact')) && strlen($subevent->val('event_contact')) > 1) { ?>
                        <span class="fa fa-user tele-title-icon"></span>
                        <div class="tele-title">
                            <p class="font-col">
                                <?= htmlspecialchars($subevent->val('event_contact')); ?>
                            </p>
                        </div>
                        <?php } ?>
                        <?php if (!empty($subevent->val('event_contact_phone_number'))) { ?>
                            <span role='img' aria-label='<?= gettext('User');?>' class="fa fa-phone-alt tele-title-icon"></span>
                            <div class="tele-title">
                                <p class="font-col">
                                    <?= $subevent->val('event_contact_phone_number'); ?>
                                </p>
                            </div>
                        <?php } ?>
                    </div>
                    <?php if($subevent->val('event_attendence_type')!=4) { ?>
                    <div class="col-md-6 col-xs-12">
                        <div class="col-md-12">
                        <?php if($subevent->val('event_attendence_type')!=1 && !empty($subevent->val('web_conference_link'))) { ?>
                            <i aria-label="Meeting Platform" role="img" class="fa fa-laptop tele-title-icon" aria-hidden="true"></i>
                            <div class="tele-title">
                                <a class="link_show" target="_blank" rel="noopener" href="<?= $subevent->getWebConferenceLink()?>" ><strong><?= $subevent->val('web_conference_sp'); ?></strong></a>
                                &nbsp;
                                <?php if ($subevent->val('web_conference_detail')){ ?>
                                    <a  onclick='showWebConferenceDetail("<?= $_COMPANY->encodeId($subevent->val('eventid')); ?>")' class="link_show small">[<?= gettext('details')?>]</a>
                                <?php } ?>
                            </div>
                            <div class="col-md-12 my-2 p-3" id="conference_detail_<?=$_COMPANY->encodeId($subevent->val('eventid'))?>" style="background-color: #efefef;display: none;">
                                <?=$subevent->val('web_conference_detail')?>
                            </div>
                        <?php } ?>
                        <?php if ($subevent->val('event_attendence_type') !=2){
                                if ($subevent->val('latitude')) {
                                    $map_location = $subevent->val('latitude') . ',' . $subevent->val('longitude');
                                } else {
                                    $map_location = $subevent->val('vanueaddress');
                                }
                            ?>
                            <span aria-label="Location" role="img" class="fa fa-map-marker map-in tele-title-icon"></span>
                            <div class="tele-title">
                                <p class="font-col"><?= $subevent->val('eventvanue'); ?><a class="link_show small" target="_blank"  href="https://www.google.com/maps/?q=<?= $map_location; ?>" >&nbsp;[<?= gettext("map"); ?>]</a></p>
                                <p><?= $subevent->val('vanueaddress'); ?></p>

                                <?php if (!empty($subevent->val('venue_room'))) { ?>
                                    <p><?= $subevent->val('venue_room'); ?></p>
                                <?php } ?>

                                <?php if (!empty($subevent->val('venue_info'))) { ?>
                                    <p><?= $subevent->val('venue_info'); ?></p>
                                <?php } ?>
                            </div>
                        <?php } ?>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <!-- Time a location block end -->

                <!-- Start event details -->
                <?php if($subevent->val('event_description')) { ?>
                <div class="col-md-12 pt-3">
                    <button class="btn btn-link" id="actionId<?= $_COMPANY->encodeId($subevent->val('eventid')); ?>)" onclick="showDescription('<?= $_COMPANY->encodeId($subevent->val('eventid')); ?>')"><span id="action<?= $_COMPANY->encodeId($subevent->val('eventid')); ?>"><?= gettext("Show Event Detail"); ?></span></button>
                </div>
                <?php } ?>
                <div class="col-md-12 evet-description" id="desc<?= $_COMPANY->encodeId($subevent->val('eventid')); ?>" style="display: none;">
                    <div id="post-inner">
                        <?= $subevent->val('event_description'); ?>
                    </div>
                </div>
                <!-- End event details -->
            </div>
        </div>
        
    </div>

<?php if($subevent->val('web_conference_detail')){ ?>
    <div id="web_conference_detail<?= $_COMPANY->encodeId($subevent->val('eventid')); ?>" class="modal fade">
        <div aria-label="<?= sprintf(gettext('%s Details'), $subevent->val('web_conference_sp')) ?>" class="modal-dialog" aria-modal="true" role="dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="location_modal_title"><?= sprintf(gettext('%s Details'), $subevent->val('web_conference_sp')) ?></h4>
                    <button aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
                    
                </div>
                <div class="modal-body modal-max-height">
                    <div class="col-md-12" id="conference_detail">
                        <?=$subevent->val('web_conference_detail')?>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="modal-footer text-center">
                    <button type="button" class="btn btn-primary" data-dismiss="modal"><?= gettext('Close') ?></button>
                    </div>
            </div>
        </div>
    </div>
<?php } ?>

    
<?php } ?>
<script>
    function showDescription(i) {
        var x = document.getElementById("desc"+i+"");
        if (x.style.display === "none") {
            x.style.display = "block";
            $("#action"+i).html("<?= gettext("Hide Event Detail"); ?>");
        } else {
            x.style.display = "none";
            $("#action"+i).html("<?= gettext("Show Event Detail"); ?>");
        }
    }
   
    function showWebConferenceDetail(i){
        $('#web_conference_detail'+i).modal('show');
    }
    
    $("#ShowHidePastEvents").click(function(){
        var x = $(".pastEventCls");
        if (x.is(":visible")) {
            x.hide();  
            $(this).html("<?= gettext("Show Past Events"); ?>");
        } else {
            x.show();  
            $(this).html("<?= gettext("Hide Past Events"); ?>");
        }
        return false;
    });

</script>