<?php
/** 
 * Note : Any changes in this file related to php side data veriable 
 * needs changes at two places:
 * 1. ajax_events.php - on getEventsTimeline condition
 * 2. ajax_my_events.php - on getMyEventsDataBySection condition
 *  
 * */
?>
<?php
$event = Event::ConvertDBRecToEvent($evt);
$eventUrl = 'eventview?id='. $_COMPANY->encodeId($evt['eventid']).$url_chapter_channel_suffix;
?>
                <div class="row">
                    <div class="col-md-12 <?= $section == Event::MY_EVENT_SECTION['DISCOVER_EVENTS'] ? 'home-announcement-block' : 'event-block' ?>">

                        <div class="col-md-1 text-center">
                            <?php if($section == Event::MY_EVENT_SECTION['DISCOVER_EVENTS']){ ?>
                                <div class="col-cd" role='img' aria-label="<?= gettext('Events');?> <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['start'], false, false, false,'M'); ?> <?=$_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['start'], false, false, false,'d'); ?>">						
                                    <div style="text-decoration:none; color:#505050;">
                                        <span style="text-align:center;display: block; height: 22px; font-size: 18px;">
                                            <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['start'], false, false, false,'M'); ?>
                                        </span>

                                        <span style="text-align:center;display: block;">
                                            <strong><?=$_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['start'], false, false, false,'d'); ?></strong>
                                        </span>
                                    </div>
                                </div>
                            <?php }else{ ?>
                            <p class="mb-1 text-sm"><strong><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['start'], false, false, false, 'D')?></strong></p>
                            <span class="mb-1"><strong><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['start'], false, false, false, 'M') ?></strong></span>
                            <span><strong><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['start'], false, false, false, 'd') ?></strong></span>
                            <?php } ?>
                        </div>

                        <div class="col-md-8 <?=$section == Event::MY_EVENT_SECTION['DISCOVER_EVENTS'] ? 'ml-md-2' : '' ?>">
                            <!-- Event header row start -->
                            <?php if($section == Event::MY_EVENT_SECTION['DISCOVER_EVENTS']){ ?>
                            <!-- Zone name for the event -->
                             <small>[<?= $evt['zonename']; ?>]</small><br>
                            <?php } ?>
                            <?php if($evt['event_series_name']){ ?>
                                <small><a onclick="getEventDetailModal('<?= $_COMPANY->encodeId($evt['event_series_id']); ?>','<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>')" href="javascript:void(0)">[<?= sprintf(gettext(' Part of %s'),$evt['event_series_name']); ?>]</a></small><br>
                            <?php } ?>
                            <a role="button" class="text-asd" onclick="getEventDetailModal('<?= $_COMPANY->encodeId($evt['eventid']); ?>','<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>')" href="javascript:void(0)">
                                <?php if($evt['isactive'] == Event::STATUS_DRAFT){ ?>
                                    <h2 class="active" style="color:red;">
                                        <?= $evt['eventtitle']; ?>
                                        <img src="img/draft_ribbon.png" alt="Draft icon image" height="16px;"/>
                                    </h2>
                                <?php } else if ($evt['isactive']  == Event::STATUS_UNDER_REVIEW){ ?>
                                    <h2 class="active" style="color:darkorange;">
                                        <?= $evt['eventtitle']; ?>
                                        <img src="img/review_ribbon.png" alt="Draft icon image" height="16px;"/>
                                    </h2>
                                <?php } else if ($evt['isactive']  == Event::STATUS_AWAITING){ ?>
                                    <h2 style="color: deepskyblue;" >
                                        <?= $evt['eventtitle']; ?>
                                        <img src="img/schedule.png" alt="Schedule icon image" height="16px"/>
                                    </h2>
                                <?php } else { ?>
                                    <h2 class="active">
                                        <?= $evt['eventtitle']; ?>
                                        <?php if ($evt['pin_to_top'] && $type == 1) { ?>
                                        <i class="fa fa-thumbtack ml-1" style="font-size:small;vertical-align:super;" aria-hidden="true" title="Pinned event"></i>
                                        <?php } ?>
                                    </h2>
                                <?php } ?>
                            </a>
                            <!-- Event header row end -->
                            <?php if($evt['collaboratedWithFormated']){ ?>
                                <p>
                                    <span class="collaborative_head"><?= gettext('This is a collaborative event between'); ?>:</span>&nbsp;<span class="collaborative_with"><?= $evt['collaboratedWithFormated']; ?></span>
                                </p>
                            <?php } ?>

                            <?php if($section != Event::MY_EVENT_SECTION['DISCOVER_EVENTS']){ ?>
                            <!-- Event second Row Start -->
                            <p>
                            <?= ($evt['isactive'] == Event::STATUS_DRAFT || $evt['isactive'] == Event::STATUS_UNDER_REVIEW)? gettext("Created on"): ( $evt['isactive'] == Event::STATUS_AWAITING ? gettext("Scheduled to publish on") : gettext("Published on") ) ?>
                            <?php
							$datetime = (($evt['isactive'] == Event::STATUS_DRAFT || $evt['isactive'] == Event::STATUS_UNDER_REVIEW) ? $evt['addedon'] : $evt['publishdate']);
							echo $_USER->formatUTCDatetimeForDisplayInLocalTimezone($datetime,true,true,true);
							?>

                                <?php if($evt['groupid'] == 0 && empty($evt['collaborating_groupids'])){ ?>
                                    in <span class="group-label ml-1" style="color:<?= $_COMPANY->getAppCustomization()['group']['group0_color']; ?>"><?= $_COMPANY->getAppCustomization()['group']['groupname0']; ?></span>
                                <?php } ?>
                                
                        <?php if ($evt['chapterid'] && empty($evt['collaborating_groupids'])) { ?>
                                in
                            <?php foreach($event->getEventChapterNames() as $c){ ?>
                                    <span class="chapter-label">
                                        <i class="fas fa-globe" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($c); ?>
                                    </span>
                                    &nbsp;
                            <?php } ?>
                        <?php } ?>
                                <?php
                                if ($evt['channelid'] > 0){
                                    $ch = Group::GetChannelName($evt['channelid'],$evt['groupid']);
                                    ?>
                                    <span class="chapter-label ml-1" style="color:<?= $ch['colour'] ?>"><i class="fas fa-layer-group" style="color:<?= $ch['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($ch['channelname']); ?></span>
                                    <?php
                                }
                                ?>
                            </span>
                            </p>
                            <!-- Event second Row End -->
                            <?php } ?>
                            <!-- Time a location block start -->
                            <p>
                            <div>
                                <span aria-label="Time" role="img" class="fa fa-clock tele-title-icon"></span>
                                <div class="tele-title">
                                    <p class="font-col">
                                    <?php if($section == Event::MY_EVENT_SECTION['DISCOVER_EVENTS']){ ?>

                                        <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['start'], false, false, false, 'g:i a') ?>
                                         - <?= ($evt['multiday']) ? $_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['end'], false, false, false, 'g:i a') :
                                            $_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['end'], false, false, false, 'g:i a')
                                            ?>
                                        <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['end'], false, false, false, 'T (P)') ?>

                                    <?php }else{ ?>
                                        
                                        <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['start'], false, false, false, 'M j, Y g:i a') ?>
                                         - <?= ($evt['multiday']) ? $_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['end'], false, false, false, 'M j, Y g:i a') :
                                            $_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['end'], false, false, false, 'g:i a')
                                            ?>
                                        <br>
                                        <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['end'], false, false, false, 'T (P)') ?>

                                    <?php } ?>
                                    </p>
                                </div>
                            </div>
                            <?php if ($evt['event_attendence_type']!=4){ ?>
                                <?php if ($evt['event_attendence_type']!=1 && $section != Event::MY_EVENT_SECTION['DISCOVER_EVENTS']){ ?>
                                    <div>
                                        <i aria-label="Meeting Platform" role="img" class="fa fa-laptop tele-title-icon" aria-hidden="true"></i>
                                        <div class="tele-title">
                                            <p class="font-col"><?= $evt['web_conference_sp']; ?></p>
                                        </div>
                                    </div>
                                <?php } ?>
                                <?php if($evt['event_attendence_type'] !=2){ ?>
                                    <div>
                                        <span aria-label="Location" role="img" class="fa fa-map-marker map-in tele-title-icon"></span>
                                        <div class="tele-title">
                                            <p class="font-col"><?= $evt['eventvanue']; ?></p>
                                            <p><?= $evt['vanueaddress']; ?></p>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                            </p>
                            <!-- Time a location block end -->
                        </div>
                        <?php if($section != Event::MY_EVENT_SECTION['DISCOVER_EVENTS']){ ?>
                        <div class="col-md-3 mt-4">
                            <?php if ($evt['rsvp_enabled'] && ($evt['rsvp_display']==1 || $evt['rsvp_display']==2 || $evt['rsvp_display']==3)) { ?>
                                <div class="row">
                                    <div class="col-12 px-0">
                                        <strong><?= $evt['joinersCount']; ?> <?=  (isset($_GET['type']) && $_GET['type']==2) ? gettext("RSVP'd") : gettext('People going') ?></strong>
                                    </div>
                                </div>
                            <?php } ?>

                            <?php if ($evt['rsvp_enabled'] && ($evt['rsvp_display']==2 || $evt['rsvp_display']==3) && count($evt['eventJoiners'])) { ?>
                            <div class="row">
                                <?php foreach ($evt['eventJoiners'] as $eventJoiner){ ?>
                                    <div class="col-2 px-0">
                                        <?= User::BuildProfilePictureImgTag($eventJoiner['firstname'], $eventJoiner['lastname'], $eventJoiner['picture'],'memberpic2',sprintf(gettext('%s Profile Picture'),$eventJoiner['firstname']));?>
                                    </div>
                                <?php } ?>
                            </div>

                            <?php if ($evt['rsvp_display']==3){ ?>
                            <div class="row">
                                <div class="col px-0">
                                    <button class="btn-no-style btn-link view-rsvp-btn" onclick="loadViewEventRSVPsModal('<?= $_COMPANY->encodeId($evt['groupid']); ?>','<?= $_COMPANY->encodeId($evt['eventid']); ?>')" style="font-size: small;"><?= gettext("View RSVP's") ?></button>
                                </div>
                            </div>
                            <?php } ?>

                            <?php } ?>

                            <?php
                                $eventVolunteerRequests = $event->getEventVolunteerRequests();
                                $volunteerRequests = array();
                                foreach($eventVolunteerRequests as $key => $volunteer){
                                    if (isset($volunteer['hide_from_signup_page']) && $volunteer['hide_from_signup_page'] == 1) { // hide that role from listing
                                        continue;
                                    }
                                    $volunteerRequests[]  = $volunteer;
                                }
                            ?>

                            <?php if($_COMPANY->getAppCustomization()['event']['volunteers'] && !empty($volunteerRequests) && !$event->hasEnded() && $event->isPublished() && !$event->isAllRequestedVolunteersSignedup() && $event->val('rsvp_enabled')){ ?>
                            <div class="row">
                                <div class="col-12 px-0 mt-4 pt-3">
                                <button class="btn-no-style btn-link" onclick="getEventDetailModal('<?= $_COMPANY->encodeId($evt['eventid']); ?>','<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>')"><?=gettext("Volunteers needed!");?>
                                </button>
                                <ul style="font-size: smaller;display: flex;list-style-position: inside;flex-direction: column;width: 100%; padding-left:3px;">
                                    <?php
                                    foreach ($volunteerRequests as $volunteerRequest) {
                                        if (isset($volunteerRequest['hide_from_signup_page']) && $volunteerRequest['hide_from_signup_page'] == 1) { // hide that role from listing
                                            continue;
                                        }
                                        $volunteerType = Event::GetEventVolunteerType($volunteerRequest['volunteertypeid']);
                                        $volunteerCount = $event->getVolunteerCountByType($volunteerRequest['volunteertypeid']) ?? 0 ;
                                        ?>
                                    <li> <?= $volunteerType ? ucfirst($volunteerType['type']) : '' ?> : <?=  $volunteerRequest['volunteer_needed_count'] - $volunteerCount; ?> </li>
                                    <?php } ?>
                                </ul>
                            </div>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } ?>
                        <?php if($section == Event::MY_EVENT_SECTION['DISCOVER_EVENTS']){?>
                            <!-- Group name for the event -->
                            <div class="col-md-2 center-button">
                            <?php if(!empty($evt['collaboratedWith'])) {
                                        $collaboratedWithGroups = $evt['collaboratedWith'];
                                    ?>
                                        <?php foreach($collaboratedWithGroups as $collaboratedWith){ ?>
                                            
                                                <a aria-label="<?= $collaboratedWith->val('groupname_short') ?? $collaboratedWith->val('groupname'); ?> <?= $evt['eventtitle']; ?>" href="<?= Url::GetZoneAwareUrlBase($collaboratedWith->val('zoneid')) . 'detail?id=' . $_COMPANY->encodeId($collaboratedWith->val('groupid')); ?>" class="mn mt-1 collaboration-button" style="background-color:<?= $collaboratedWith->val('overlaycolor'); ?> !important;"><?= $collaboratedWith->val('groupname_short') ?? $collaboratedWith->val('groupname'); ?>
                                                </a><br>
                                        <?php } ?>
                            <?php } elseif ($evt['groupid']) { ?>
                                            <a aria-label="<?= $evt['groupname']; ?> <?= $event->val('eventtitle'); ?>" href="<?= $event->val('group_zone_url')?>detail?id=<?= $_COMPANY->encodeId($event->val('groupid')) ?>" target="_blank" class="mn mt-1 collaboration-button" style="background-color:<?= $evt['group_overlaycolor']; ?> !important;"><?= $evt['groupname_short'] ?? $evt['groupname']; ?>
                                            </a><br>
                                        
                            <?php } else { ?>

                                            <a aria-disabled="true" aria-label="<?= $evt['groupname_short']; ?> <?= $event->val('eventtitle'); ?>" tabindex="-1" target="_blank" class="mn mt-1 collaboration-button" style="background-color:<?= $_COMPANY->getAppCustomization()['group']['group0_color'] ?> !important; cursor: not-allowed;"><?= $evt['groupname_short'] ?? $evt['groupname']; ?>
                                            </a><br>
                                        
                            <?php } ?>
                                </div>
                        <?php } ?>
                    </div>
                <?php if (($_COMPANY->getAppCustomization()['event']['likes'] || $_COMPANY->getAppCustomization()['event']['comments']) && ($section != Event::MY_EVENT_SECTION['DISCOVER_EVENTS'])) { ?>
                    <div class="col-sm-12  event-block">
                        <div class="col-sm-1"></div>
                        <div class="col-sm-11">
                            <div class="link-icons img-down">
                                <?php if($event->val('isactive') == 1){ ?>
                            <?php if ($_COMPANY->getAppCustomization()['event']['likes']) { ?>
                                <div id="x<?= $event->id(); ?>" class="like-2">
                                    <span style="cursor:pointer;">
                                        <button class="btn btn-no-style" aria-label="<?= sprintf(gettext('like %1$s event. %1$s has %2$s likes'), $event->val('eventtitle'), Event::GetLikeTotals($event->id()));?>" onclick="getEventDetailModal('<?= $_COMPANY->encodeId($event->id())?>','<?= $_COMPANY->encodeId($chapterid)?>','<?= $_COMPANY->encodeId($channelid)?>')"
                                        >
                                            <i class="fa fa-thumbs-up fa-regular newgrey" title="<?= gettext('Like') ?>"></i>
                                            <span class="gh1"><?= Event::GetLikeTotals($event->id()) ?></span>
                                        </button>
                                    </span>
                                </div>
                                <?php } ?>
                                <?php if ($_COMPANY->getAppCustomization()['event']['comments']) { ?>
                                <div class="review-2">
                                    <button class="btn btn-no-style" aria-label="<?= sprintf(gettext('comment %1$s event. %1$s has %2$s comments'),$event->val('eventtitle'), Event::GetCommentsTotal($event->id()));?>" onclick="getEventDetailModal('<?= $_COMPANY->encodeId($event->id())?>','<?= $_COMPANY->encodeId($chapterid)?>','<?= $_COMPANY->encodeId($channelid)?>')"
                                    >
                                        <i class="fa fa-regular fa-comment-dots newgrey" title="<?= gettext('Total Comments') ?>"></i>
                                        <span class="gh1"><?= Event::GetCommentsTotal($event->id()); ?></span>
                                </button>
                                </div>
                                <?php } ?>
                            <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                </div>