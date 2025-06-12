<?php for($a=0;$a<count($feeds);$a++) { ?>
        <?php	if($feeds[$a]['type']==1){ ?>
        <div class="col-md-12 home-announcement-block" id="evnt<?= $feeds[$a]['eventid']; ?>">
            <div class="col-sm-1 col-12">
                <div class="col-cd">
                    <div aria-label="<?= $feeds[$a]['eventtitle']; ?>" style="text-decoration:none; color:#505050;">
									<span style="text-align:center;display: block; height: 22px; font-size: 18px;">
										<?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($feeds[$a]['start'], false, false, false,'M'); ?>
                                    </span>

                        <span style="text-align:center;display: block;">
										<strong><?=$_USER->formatUTCDatetimeForDisplayInLocalTimezone($feeds[$a]['start'], false, false, false,'d'); ?></strong>
                                    </span>
                    </div>
                </div>
            </div>

            <div class="col-md-7 col-12 line-two">

                <h2 class="text-asd" data-id=<?= $a ?>>
                    <?php if($feeds[$a]['isactive'] == Event::STATUS_DRAFT || $feeds[$a]['isactive'] == Event::STATUS_UNDER_REVIEW){?>
                        <a class="active" style="color:red;" onclick="getEventDetailModal('<?= $_COMPANY->encodeId($feeds[$a]['eventid']); ?>','<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId(0); ?>')" href="javascript:void(0);">
                            <strong><?= $feeds[$a]['eventtitle']; ?></strong>
                        </a>
                    <?php } else {?>
                        <a class="active" onclick="getEventDetailModal('<?= $_COMPANY->encodeId($feeds[$a]['eventid']); ?>','<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId(0); ?>')" href="javascript:void(0);">
                            <strong><?= $feeds[$a]['eventtitle']; ?></strong>
                            <?php if($feeds[$a]['pin_to_top']){ ?>
                                <i class="fa fa-thumbtack ml-1" style="font-size:small;vertical-align:super;" aria-hidden="true"></i>
                            <?php } ?>
                        </a>
                    <?php }?>
                </h2>
                <p>
									<span class="dta-tm">
										<?= ($feeds[$a]['isactive'] == Event::STATUS_DRAFT || $feeds[$a]['isactive'] == Event::STATUS_UNDER_REVIEW)? gettext("Created on"): ( $feeds[$a]['isactive'] == Event::STATUS_AWAITING ? gettext('Scheduled to publish on') : gettext('Published on') ) ?>

                                        <?php
                                        $datetime = (($feeds[$a]['isactive'] == Event::STATUS_DRAFT || $feeds[$a]['isactive'] == Event::STATUS_UNDER_REVIEW) ? $feeds[$a]['postedon'] : $feeds[$a]['publishdate']);
                                        echo $_USER->formatUTCDatetimeForDisplayInLocalTimezone($datetime,true,true,true);
                                        ?>



                                        <?php if ($feeds[$a]['chapterid']) { ?>
                                            in
                                            <?php foreach(explode(',',$feeds[$a]['chapterid']) as $chid){ ?>
                                                <?php 	$c = Group::GetChapterName($chid,$feeds[$a]['groupid']); ?>
                                                <span class="chapter-label" style="color:<?= $c['colour'] ?>;">
													<i class="fas fa-globe" style="color:<?= $c['colour'] ?>;" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($c['chaptername']); ?>
												</span>
                                                &nbsp;
                                            <?php } ?>
                                        <?php } ?>

                                        <?php
                                        if ($feeds[$a]['channelid'] > 0){
                                            $ch = Group::GetChannelName($feeds[$a]['channelid'],$feeds[$a]['groupid']);
                                            ?>
                                            <span class="chapter-label ml-1" style="color:<?= $ch['colour'] ?>;">
													<i class="fas fa-layer-group" style="color:<?= $ch['colour'] ?>;" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($ch['channelname']); ?></span>
                                            <?php
                                        }
                                        ?>
									</span>
                </p>

                <div>
                    <span aria-label="Time" role="img" class="fa fa-clock tele-title-icon"></span>
                    <div class="tele-title">
                        <p class="font-col"></p>
                        <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($feeds[$a]['start'], true, true, true, "M j, Y g:i a T(P)") ?>
                    </div>
                </div>
                <?php if($feeds[$a]['event_attendence_type'] !=4){ ?>
                    <?php if($feeds[$a]['event_attendence_type'] !=1){ ?>
                        <div>
                            <i aria-label="Meeting Platform" role="img" class="fa fa-laptop tele-title-icon" aria-hidden="false"></i>
                            <div class="tele-title">
                                <p class="font-col"><?= $feeds[$a]['web_conference_sp']; ?></p>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if($feeds[$a]['event_attendence_type'] !=2){ ?>
                        <div >
                            <span aria-label="Location" role="img" class="fa fa-map-marker map-in tele-title-icon"></span>
                            <div class="tele-title">
                                <p><?= $feeds[$a]['eventvanue']; ?></p>
                                <p><?= $feeds[$a]['vanueaddress']; ?></p>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>

                <div class="col-md-12">
                    <div class="liker-col">
                        <div class=" f-left date_time col-md-4">
                            <img src="img/sdf-1.png" alt="Time" class="clock-img">
                            <span><?= $db->timeago($feeds[$a]['addedon']); ?></span>
                        </div>
                    </div>

                    <?php if ($_COMPANY->getAppCustomization()['event']['likes']) { ?>
                        <div id="x<?= ($a+1); ?>" class="col-4 like-2 h-like">
									<span style="cursor:pointer;">
										<a aria-label="<?= Event::GetLikeTotals($feeds[$a]['eventid']); ?> <?= gettext('like for'); ?> <?= $feeds[$a]['eventtitle']; ?>" onclick="getEventDetailModal('<?= $_COMPANY->encodeId($feeds[$a]['eventid']); ?>','<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId(0); ?>')" href="javascript:void(0);">
                                            <i class="fa fa-thumbs-up fa-regular newgrey" title="<?= gettext('Like') ?>"></i>
											<span class="gh1"><?= Event::GetLikeTotals($feeds[$a]['eventid']); ?></span>
										</a>
									</span>
                        </div>
                    <?php } ?>
                    <?php if ($_COMPANY->getAppCustomization()['event']['comments']) { ?>
                        <div class="col-4 f-right">
                            <a aria-label="<?= Event::GetCommentsTotal($feeds[$a]['eventid']); ?> <?= gettext('comments for'); ?> <?= $feeds[$a]['eventtitle']; ?>" onclick="getEventDetailModal('<?= $_COMPANY->encodeId($feeds[$a]['eventid']); ?>','<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId(0); ?>')" href="javascript:void(0);">
                                <i class="fa fa-regular fa-comment-dots newgrey" title="<?= gettext('Total Comments') ?>"></i>
                                <span class="gh1"><?= Event::GetCommentsTotal($feeds[$a]['eventid']); ?></span>
                            </a>
                        </div>
                    <?php } ?>

                </div>
            </div>

            <div class="col-md-2 col-12">
                <div class="row py-2 px-md-0 px-5">
                    <div class="col-md-12 col-12">
                        <?php if ($feeds[$a]['rsvp_display']==1 || $feeds[$a]['rsvp_display']==2 || $feeds[$a]['rsvp_display']==3){ ?>
                            <p style="font-size: small;"><strong><?= $feeds[$a]['joinerCount'] ?> <?= gettext('People going'); ?></strong></p>
                        <?php } ?>
                    </div>
                    <?php if ($feeds[$a]['rsvp_display']==2 || $feeds[$a]['rsvp_display']==3){ ?>
                        <?php	if(count($feeds[$a]['joinerData'])>0){ ?>
                            <?php		for($p=0;$p<count($feeds[$a]['joinerData']);$p++){ ?>
                                <div class="col-md-3 col-2 p-0">
                                    <?= User::BuildProfilePictureImgTag($feeds[$a]['joinerData'][$p]['firstname'], $feeds[$a]['joinerData'][$p]['lastname'], $feeds[$a]['joinerData'][$p]['picture'],'memberpic2',sprintf(gettext('%s Profile Picture'),$feeds[$a]['joinerData'][$p]['firstname']));?>

                                </div>
                            <?php		} ?>
                            <?php if($feeds[$a]['rsvp_display'] ==3) { ?>
                                <div class="col-md-12 col-12">
                                    <button class="btn-no-style btn-link view-rsvp-btn" onclick="loadViewEventRSVPsModal('<?= $_COMPANY->encodeId($feeds[$a]['groupid']); ?>','<?= $_COMPANY->encodeId($feeds[$a]['eventid']); ?>')" style="font-size: small;"><?= gettext('View RSVP\'s'); ?></button>
                                </div>
                            <?php } ?>
                        <?php	}	?>
                    <?php	}	?>
                </div>
            </div>
            <div class="col-md-2 col-12 pull-right">
                <div class="row row-no-gutters">
                    <?php if(!empty($feeds[$a]['collaboratedWith'])) {
                        $collaboratedWithGroups = $feeds[$a]['collaboratedWith'];
                        ?>
                        <div class="col-12 center-button collaboration-title">
                            <?= gettext('Collaboration between'); ?>
                        </div>
                        <?php foreach($collaboratedWithGroups as $collaboratedWith){ ?>
                            <div class="col-12 center-button">
                                <a aria-label="<?= $collaboratedWith->val('groupname_short') ?? $collaboratedWith->val('groupname'); ?> <?= $feeds[$a]['eventtitle']; ?>" href="detail?id=<?= $_COMPANY->encodeId($collaboratedWith->val('groupid')); ?>" class="mn mt-1 collaboration-button" style="background-color:<?= $collaboratedWith->val('overlaycolor'); ?> !important;"><?= $collaboratedWith->val('groupname_short') ?? $collaboratedWith->val('groupname'); ?>
                                </a>
                            </div>
                        <?php } ?>
                    <?php } elseif ($feeds[$a]['groupid']) { ?>
                        <div class="col-12 center-button">
                            <a aria-label="<?= $feeds[$a]['groupname_short']; ?> <?= $feeds[$a]['eventtitle']; ?>" href="detail?id=<?= $_COMPANY->encodeId($feeds[$a]['groupid']); ?>" class="mn" style="background-color:<?= $feeds[$a]['overlaycolor']; ?> !important;">
                                <?= $feeds[$a]['groupname_short']; ?>
                            </a>
                        </div>
                    <?php	} else { ?>
                        <div class="col-12 center-button">
                            <a aria-label="<?= $feeds[$a]['groupname_short']; ?> <?= $feeds[$a]['eventtitle']; ?>" class="mn" style="background-color:<?= $_COMPANY->getAppCustomization()['group']['group0_color'] ?> !important; cursor: not-allowed;">
                                <?= $feeds[$a]['groupname_short']; ?>
                            </a>
                        </div>
                    <?php	} ?>
                </div>
            </div>
        </div>
        <?php	}else if ($feeds[$a]['type']==2){ ?>

        <div class="col-md-12 home-announcement-block" id="ann<?= $feeds[$a]['postid']; ?>">
            <div class="col-sm-1 col-12">
                <div class="col-an img">
                </div>
            </div>
            <div class="col-sm-9 col-12 line-two">
                <h2 class="text-asd" data-id=<?= $a ?>>
                    <a aria-label="<?= $feeds[$a]['title']; ?>" class="active" onclick="getAnnouncementDetailOnModal('<?= $_COMPANY->encodeId($feeds[$a]['postid']) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId(0)?>')" href="javascript:void(0);">
                        <strong><?= $feeds[$a]['title']; ?></strong>
                        <?php if($feeds[$a]['pin_to_top']){ ?>
                            <i class="fa fa-thumbtack ml-1" style="font-size:small;vertical-align:super;" aria-hidden="true"></i>
                        <?php } ?>
                    </a>
                </h2>
                <p>
							<span class="dta-tm">
								<?= ($feeds[$a]['isactive'] == Post::STATUS_DRAFT || $feeds[$a]['isactive'] == Post::STATUS_UNDER_REVIEW)? gettext("Created on"): ( $feeds[$a]['isactive'] == Post::STATUS_AWAITING ? gettext('Scheduled to publish on') : gettext("Published on") ) ?>
                                <?php
                                $datetime = (($feeds[$a]['isactive'] == Post::STATUS_DRAFT || $feeds[$a]['isactive'] == Post::STATUS_UNDER_REVIEW) ? $feeds[$a]['postedon'] : $feeds[$a]['publishdate']);
                                echo $_USER->formatUTCDatetimeForDisplayInLocalTimezone($datetime,true,true,true);
                                ?>

                                <?php if ($feeds[$a]['chapterid']) { ?>
                                    in
                                    <?php foreach(explode(',',$feeds[$a]['chapterid']) as $chid){ ?>
                                        <?php 	$c = Group::GetChapterName($chid,$feeds[$a]['groupid']); ?>
                                        <span class="chapter-label" style="color:<?= $c['colour'] ?>;">
											<i class="fas fa-globe" style="color:<?= $c['colour'] ?>;" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($c['chaptername']); ?>
										</span>
                                        &nbsp;
                                    <?php } ?>
                                <?php } ?>

                                <?php
                                if ($feeds[$a]['channelid'] > 0){
                                    $ch = Group::GetChannelName($feeds[$a]['channelid'],$feeds[$a]['groupid']);
                                    ?>
                                    <span class="chapter-label ml-1" style="color:<?= $ch['colour'] ?>;">
											<i class="fas fa-layer-group" style="color:<?= $ch['colour'] ?>;" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($ch['channelname']); ?></span>
                                    <?php
                                }
                                ?>
							</span>
                </p>
                <div><i class="fa fa-quote-left  tele-title-icon" aria-hidden="true"></i>
                    <p class="tele-title">
                        <?php
                        $post_val = strip_tags($feeds[$a]['post']);
                        if (strlen($post_val) > 160)
                            echo substr($post_val,0,160)." ...";
                        else
                            echo substr($post_val,0,160);
                        ?>
                    </p>
                </div>
                <div class="col-md-12  mt-3 mob-padding">
                        <div id="x<?= ($a+1); ?>" class="col-4 like-2 h-like">
							<span style="cursor:pointer;">
								<a aria-label="<?= Post::GetLikeTotals($feeds[$a]['postid']); ?> <?= gettext('like for'); ?> <?= $feeds[$a]['title']; ?>" onclick="getAnnouncementDetailOnModal('<?= $_COMPANY->encodeId($feeds[$a]['postid']) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId(0)?>')" href="javascript:void(0);">
                                    <i class="fa fa-thumbs-up fa-regular newgrey" title="<?= gettext('Like') ?>"></i>
							        <span class="gh1"><?= Post::GetLikeTotals($feeds[$a]['postid']); ?></span>
                                </a>
							</span>
                        </div>
                        <div class="col-4 f-right">
                            <a aria-label="<?= Post::GetCommentsTotal($feeds[$a]['postid']); ?> <?= gettext('comments for'); ?> <?= $feeds[$a]['title']; ?>" onclick="getAnnouncementDetailOnModal('<?= $_COMPANY->encodeId($feeds[$a]['postid']) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId(0)?>')" href="javascript:void(0);">
                                <i class="fa fa-regular fa-comment-dots newgrey" title="<?= gettext('Total Comments') ?>"></i>
                                <span class="gh1"><?= Post::GetCommentsTotal($feeds[$a]['postid']); ?></span>
                            </a>
                        </div>

                </div>

                <?php if ($a==(count($feeds)-1)){ ?>

                <?php } ?>
            </div>

            <div class="col-sm-2 col-12 pull-right center-button">
                <?php	if($feeds[$a]['groupid']>0){	?>
                    <a aria-label="<?= $feeds[$a]['groupname_short']; ?> <?= $feeds[$a]['title']; ?>" href="detail?id=<?= $_COMPANY->encodeId($feeds[$a]['groupid']); ?>" class="mn" style="background-color:<?= $feeds[$a]['overlaycolor']; ?> !important;">
                        <?= $feeds[$a]['groupname_short']; ?>
                    </a>
                <?php	}else{							?>
                    <a aria-label="<?= $feeds[$a]['groupname_short']; ?> <?= $feeds[$a]['title']; ?>"  class="mn" style="background-color:<?= $_COMPANY->getAppCustomization()['group']['group0_color'] ?> !important; cursor: not-allowed;">
                        <?= $feeds[$a]['groupname_short']; ?>
                    </a>
                <?php	}								?>
            </div>
        </div>

        <?php	} else if($feeds[$a]['type']==3) { ?>

        <div class="col-md-12 home-announcement-block" id="news<?= $feeds[$a]['newsletterid']; ?>">
            <div class="col-sm-1 col-12">
                <div class="col-nw">
                    <a class="active" aria-label="<?= $feeds[$a]['newslettername']; ?>" onclick="previewNewsletter('<?= $_COMPANY->encodeId($feeds[$a]['groupid']);?>','<?= $_COMPANY->encodeId($feeds[$a]['newsletterid']);?>')"> </a>
                </div>
            </div>
            <div class="col-sm-9 col-12 line-two ">
                <h2 class="text-asd" data-id=<?= $a ?>>
                    <a aria-label="<?= $feeds[$a]['newslettername']; ?>" class="active" onclick="previewNewsletter('<?= $_COMPANY->encodeId($feeds[$a]['groupid']);?>','<?= $_COMPANY->encodeId($feeds[$a]['newsletterid']); ?>')"  href="javascript:void(0);">
                        <strong><?= $feeds[$a]['newslettername']; ?></strong>
                        <?php if($feeds[$a]['pin_to_top']){ ?>
                            <i class="fa fa-thumbtack ml-1" style="font-size:small;vertical-align:super;" aria-hidden="true"></i>
                        <?php } ?>
                    </a>
                </h2>

                <div class="col-md-12 mt-3 mob-padding">
                    <?php if($_COMPANY->getAppCustomization()['newsletters']['likes']) { ?>
                        <div id="x<?= ($a+1); ?>" class="col-4 like-2 h-like">
						<span style="cursor:pointer;">
							<a aria-label="<?= Newsletter::GetLikeTotals($feeds[$a]['newsletterid']); ?> <?= gettext('like for'); ?> <?= $feeds[$a]['newslettername']; ?>" onclick="previewNewsletter('<?= $_COMPANY->encodeId($feeds[$a]['groupid']);?>','<?= $_COMPANY->encodeId($feeds[$a]['newsletterid']); ?>')"href="javascript:void(0);">
                                <i class="fa fa-thumbs-up fa-regular newgrey" title="<?= gettext('Like') ?>"></i>
								<span class="gh1"><?= Newsletter::GetLikeTotals($feeds[$a]['newsletterid']); ?></span>
							</a>
						</span>
                        </div>
                    <?php } ?>
                    <?php if($_COMPANY->getAppCustomization()['newsletters']['comments']) { ?>
                        <div class="col-4 f-right">
                            <a aria-label="<?= Newsletter::GetCommentsTotal($feeds[$a]['newsletterid']); ?> <?= gettext('comments for'); ?> <?= $feeds[$a]['newslettername']; ?>" onclick="previewNewsletter('<?= $_COMPANY->encodeId($feeds[$a]['groupid']);?>','<?= $_COMPANY->encodeId($feeds[$a]['newsletterid']); ?>')" href="javascript:void(0);">
                                <i class="fa fa-regular fa-comment-dots newgrey" title="<?= gettext('Total Comments') ?>"></i>
                                <span class="gh1"><?= Newsletter::GetCommentsTotal($feeds[$a]['newsletterid']); ?></span>
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="col-sm-2 col-12 pull-right center-button">
                <?php	if($feeds[$a]['groupid']>0){	?>
                    <a role="link" aria-label="<?= $feeds[$a]['groupname_short']; ?> <?= $feeds[$a]['newslettername']; ?>" href="detail?id=<?= $_COMPANY->encodeId($feeds[$a]['groupid']); ?>" class="mn" style="background-color:<?= $feeds[$a]['overlaycolor']; ?> !important;">
                        <?= $feeds[$a]['groupname_short']; ?>
                    </a>
                <?php	}else{ ?>
                    <a tabindex="0" role="link" aria-label="<?= $feeds[$a]['groupname_short']; ?> <?= $feeds[$a]['newslettername']; ?>"  class="mn" style="background-color:<?= $_COMPANY->getAppCustomization()['group']['group0_color'] ?> !important; cursor: not-allowed;">
                        <?= $feeds[$a]['groupname_short']; ?>
                    </a>
                <?php	}								?>
            </div>
        </div>

        <?php	}else if ($feeds[$a]['type']==4){ ?>

            <div class="col-md-12 home-announcement-block" id="ann<?= $feeds[$a]['discussionid']; ?>">
                    <div class="col-sm-1 col-12">
                        <div class="col-discussion img col-nw">
                            <a class="active" href="javascript:void(0);" onclick="getDiscussionDetailOnModal('<?= $_COMPANY->encodeId($feeds[$a]['discussionid']) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId(0) ?>')"> </a>
                        </div>
                    </div>
                    <div class="col-sm-9 col-12 line-two ">
                        <p class="text-asd">
                            <a class="active" href="javascript:void(0);" onclick="getDiscussionDetailOnModal('<?= $_COMPANY->encodeId($feeds[$a]['discussionid']) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId(0) ?>')">
                                <strong><?= $feeds[$a]['title']; ?></strong>
                                <?php if($feeds[$a]['pin_to_top']){ ?>
                                    <i class="fa fa-thumbtack ml-1" style="font-size:small;vertical-align:super;" aria-hidden="true"></i>
                                <?php } ?>
                            </a>
                        </p>
                        <p>
                                    <span class="dta-tm">
                                        <?= gettext("Published on") ?>
                                        <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($feeds[$a]['addedon'], true, true, true) ?>
                                        <?php if (($feeds[$a]['chapterid'] > 0)) { ?>
                                            <?php 	$c = Group::GetChapterName($feeds[$a]['chapterid'],$feeds[$a]['groupid']); ?>

                                            <span class="chapter-label" style="color:<?= $c['colour'] ?>">
                                                <i class="fas fa-globe" style="color:<?= $c['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($c['chaptername']); ?>
                                            </span>
                                        <?php } ?>

                                        <?php
                                        if ($feeds[$a]['channelid'] > 0){
                                            $ch = Group::GetChannelName($feeds[$a]['channelid'],$feeds[$a]['groupid']);
                                            ?>
                                            <span class="chapter-label ml-1" style="color:<?= $ch['colour'] ?>">
                                                    <i class="fas fa-layer-group" style="color:<?= $ch['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($ch['channelname']); ?></span>
                                            <?php
                                        }
                                        ?>
                                    </span>
                        </p>
                        <div><i class="fa fa-quote-left  tele-title-icon" aria-hidden="true"></i>
                            <p class="tele-title">
                                <?php
                                $post_val = strip_tags($feeds[$a]['discussion']);
                                if (strlen($post_val) > 160)
                                    echo substr($post_val,0,160)." ...";
                                else
                                    echo substr($post_val,0,160);
                                ?>
                            </p>
                        </div>
                        <div class="col-md-12 mt-3 mob-padding">
                                <div id="x<?= ($a+1); ?>" class="col-4 like-2 h-like">
                                    <span style="cursor:pointer;">
                                        <a aria-label="Like" href="javascript:void(0);" onclick="getDiscussionDetailOnModal('<?= $_COMPANY->encodeId($feeds[$a]['discussionid']) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId(0) ?>')">
                                            <i class="fa fa-thumbs-up fa-regular newgrey" title="<?= gettext('Like') ?>"></i>
                                            <span class="gh1"><?= Discussion::GetLikeTotals($feeds[$a]['discussionid']); ?></span>
                                        </a>
                                    </span>
                                </div>
                                <div class="col-4 f-right">
                                    <a href="javascript:void(0);" onclick="getDiscussionDetailOnModal('<?= $_COMPANY->encodeId($feeds[$a]['discussionid']) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId(0) ?>')">
                                        <i class="fa fa-regular fa-comment-dots newgrey" title="<?= gettext('Total Comments') ?>"></i>
                                        <span class="gh1"><?= Discussion::GetCommentsTotal($feeds[$a]['discussionid']); ?></span>
                                    </a>
                                </div>
                        </div>
                    </div>


                    <div class="col-sm-2 col-12 pull-right center-button">
        <?php	if($feeds[$a]['groupid']>0){	?>
                        <a href="detail?id=<?= $_COMPANY->encodeId($feeds[$a]['groupid']); ?>" class="mn" style="background-color:<?= $feeds[$a]['overlaycolor']; ?> !important;">
                            <?= $feeds[$a]['groupname_short']; ?>
                        </a>
        <?php	}else{							?>
                        <a  class="mn" style="background-color:<?= $_COMPANY->getAppCustomization()['group']['group0_color'] ?> !important; cursor: not-allowed;">
                            <?= $feeds[$a]['groupname_short']; ?>
                        </a>
        <?php	}								?>
                    </div>
                </div>

        <?php }else if ($feeds[$a]['type']==5){ 
                $media_key =  $feeds[$a]['media_ids'];
                $album_id = (int)$feeds[$a]['albumid'];
                $group_id = (int)$feeds[$a]['groupid'];
                $encChapterId = $feeds[$a]['chapterid'] ?? 0;
                $encChannelId = $feeds[$a]['channelid'] ?? 0;
                ?>

            <div class="col-md-12 home-announcement-block" id="alb<?= $feeds[$a]['albumid']; ?>">
        <div class="col-sm-1 col-12">
            <div class="col-album img col-nw">
                <a class="active" href="javascript:void(0);" onclick="viewAlbumMedia('<?= $_COMPANY->encodeId($album_id) ?>',0,'','<?= $_COMPANY->encodeId($group_id) ?>','<?= $_COMPANY->encodeId($encChapterId) ?>','<?= $_COMPANY->encodeId($encChannelId) ?>')"> </a>
            </div>
        </div>
        <div class="col-sm-9 col-12 line-two ">
            <p class="text-asd">
                <a class="active" href="javascript:void(0);" onclick="viewAlbumMedia('<?= $_COMPANY->encodeId($album_id) ?>',0,'','<?= $_COMPANY->encodeId($group_id) ?>','<?= $_COMPANY->encodeId($encChapterId) ?>','<?= $_COMPANY->encodeId($encChannelId) ?>')">
                    <strong><?= $feeds[$a]['title']; ?></strong>
                </a>
            </p>
            <p>
                        <span class="dta-tm">
                            <?= gettext("Published on") ?>
                            <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($feeds[$a]['addedon'], true, true, true) ?>
                            <?php if (($feeds[$a]['chapterid'] > 0)) { ?>
                                <?php 	$c = Group::GetChapterName($feeds[$a]['chapterid'],$feeds[$a]['groupid']); ?>

                                <span class="chapter-label" style="color:<?= $c['colour'] ?>">
                                    <i class="fas fa-globe" style="color:<?= $c['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($c['chaptername']); ?>
                                </span>
                            <?php } ?>

                            <?php
                            if ($feeds[$a]['channelid'] > 0){
                                $ch = Group::GetChannelName($feeds[$a]['channelid'],$feeds[$a]['groupid']);
                                ?>
                                <span class="chapter-label ml-1" style="color:<?= $ch['colour'] ?>">
                                        <i class="fas fa-layer-group" style="color:<?= $ch['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($ch['channelname']); ?></span>
                                <?php
                            }
                            ?>
                        </span>
            </p>
            <div class="feed-album-media-container">
               <?php
               $media_count = 0;
               $total_images = count($feeds[$a]['preview_urls']);
               $max_display = 4;
               $p=1;
               for ($i=0; $i < min($max_display, $total_images); $i++) { 
                $preview_url = $feeds[$a]['preview_urls'][$i];
                ?>
                <a class="mr-3" href="javascript:void(0);" onclick='viewAlbumMedia("<?= $_COMPANY->encodeId($album_id); ?>", <?= ($p-1)*MAX_ALBUM_MEDIA_PAGE_ITEMS + $i; ?> , <?= $media_key; ?>,"<?= $_COMPANY->encodeId($group_id);?>","<?= $encChapterId; ?>","<?= $encChannelId; ?>",2)' ><img width="130" height="130" class="feed-card-image" src="<?= $preview_url ?>"></a>
             <?php } if($total_images > $max_display){ $preview_url = $feeds[$a]['preview_urls'][$i]; ?>  &nbsp; <span class="more-images"  style="cursor:pointer" onclick='viewAlbumMedia("<?= $_COMPANY->encodeId($album_id); ?>", <?= ($p-1)*MAX_ALBUM_MEDIA_PAGE_ITEMS + $i; ?> , <?= $media_key; ?>,"<?= $_COMPANY->encodeId($group_id);?>","<?= $encChapterId; ?>","<?= $encChannelId; ?>",2)'><strong>+ <?= $total_images - $max_display ?></strong></span><?php } ?>
             </div> 
            <div class="col-md-12 mt-3 mob-padding">
                    <div id="x<?= ($a+1); ?>" class="col-4 like-2 h-like">
                        <span style="cursor:pointer;">
                            <a aria-label="Like" href="javascript:void(0);" onclick="viewAlbumMedia('<?= $_COMPANY->encodeId($album_id) ?>',0,'','<?= $_COMPANY->encodeId($group_id) ?>','<?= $_COMPANY->encodeId($encChapterId) ?>','<?= $_COMPANY->encodeId($encChannelId) ?>')">
                                <i class="fa fa-thumbs-up fa-regular newgrey" title="<?= gettext('Like') ?>"></i>
                                <span class="gh1"><?= $feeds[$a]['album_total_likes']; ?></span>
                            </a>
                        </span>
                    </div>
                    <div class="col-4 f-right">
                        <a href="javascript:void(0);" onclick="viewAlbumMedia('<?= $_COMPANY->encodeId($album_id) ?>',0,'','<?= $_COMPANY->encodeId($group_id) ?>','<?= $_COMPANY->encodeId($encChapterId) ?>','<?= $_COMPANY->encodeId($encChannelId) ?>')">
                            <i class="fa fa-regular fa-comment-dots newgrey" title="<?= gettext('Total Comments') ?>"></i>
                            <span class="gh1"><?= $feeds[$a]['album_total_comments']; ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-sm-2 col-12 pull-right center-button">
        <?php if($feeds[$a]['groupid']>0){ ?>
                    <a href="detail?id=<?= $_COMPANY->encodeId($feeds[$a]['groupid']); ?>" class="mn" style="background-color:<?= $feeds[$a]['overlaycolor']; ?> !important;">
                        <?= $feeds[$a]['groupname_short']; ?>
                    </a>
        <?php }else{ ?>
                    <a  class="mn" style="background-color:<?= $_COMPANY->getAppCustomization()['group']['group0_color'] ?> !important; cursor: not-allowed;">
                        <?= $feeds[$a]['groupname_short']; ?>
                    </a>
        <?php } ?>
                </div>
            </div>

            <?php } ?>
<?php }	?>
