        <!-- start of IF for printing posts -->
        <?php if(count($posts)>0) { ?>
            <div>
            <!-- start of loop for printing posts -->
            <?php 	for($i=0;$i<$max_iter;$i++) {
                $post = Post::GetPost($posts[$i]['postid']);
            ?>
            <?php
                    if (!$post->isPublished()) {
                        if ($post->val('groupid') == 0) {
                            continue; // Do not show unpublished admin level posts
                        } elseif (!$_USER->canCreateOrPublishContentInScopeCSV($post->val('groupid'),$post->val('chapterid'), $post->val('channelid'))) {
                            continue; // Do not unpublished posts if the user can update them
                        }
                    }
            ?>
            <div class="announcement-block">
                <?php if($page == 1 && $firstBlockPosts++< 3) { ?>
                <!-- Present the first three announcements in detail -->
                <!-- Post header row  -->
                <div class="col-sm-12">
                    <a role="button" style="text-decoration:none;color:#FFFFFF;" onclick="getAnnouncementDetailOnModal('<?= $_COMPANY->encodeId($post->id())?>','<?= $_COMPANY->encodeId($chapterid)?>','<?= $_COMPANY->encodeId($channelid)?>')"
                        href="javascript:void(0);">
                        <?php if ($post->isDraft()) { ?>
                        <h2 class="active" style="color:red;">
                            <?php echo $post->val('title'); ?>
                            <img src="img/draft_ribbon.png" alt="Draft icon image" height="16px"/>
                        </h2>
                        <?php } elseif($post->isUnderReview()){?>
                            <h2 class="active" style="color:darkorange;">
                                <?php echo $post->val('title'); ?>
                                <img src="img/review_ribbon.png" alt="Draft icon image" height="16px"/>
                            </h2>
                        <?php } elseif($post->val('isactive') == Post::STATUS_AWAITING){?>
                        <h2 class="active" style="color:deepskyblue;">
                            <?php echo $post->val('title'); ?>
                            <img src="img/schedule.png" alt="Schedule icon image" height="16px"/>
                        </h2>
                        <?php } else { ?>

                        <h2 class="active">
                           <?php echo $post->val('title'); ?>
                        </h2>
                        <?php if($post->val('pin_to_top')){ ?>
                            <i class="fa fa-thumbtack ml-1" style="color:#0077b5;vertical-align:super;font-size: small" aria-hidden="true"></i>
                        <?php } ?>
                        <?php } ?>
                    </a>
                </div>
                <!-- Post second Row Start -->
                <div class="col-sm-12">
                    <span class="dta-tm">
                    <?= ($post->val('isactive') == Post::STATUS_DRAFT || $post->val('isactive') == Post::STATUS_UNDER_REVIEW)? gettext("Created on "): ( $post->val('isactive') == Post::STATUS_AWAITING ? gettext("Scheduled to publish on") : gettext("Published on ") ) ?>
                    <?php
                    $datetime = (($post->val('isactive') == Post::STATUS_DRAFT || $post->val('isactive') == Post::STATUS_UNDER_REVIEW) ? $post->val('postedon') : $post->val('publishdate'));
                    echo $_USER->formatUTCDatetimeForDisplayInLocalTimezone($datetime,true,true,true);
                    ?>

                    <?php if($post->val('groupid') == 0 ){ ?>
                        <?= gettext("in")?> 
                        <span class="group-label ml-1" style="color:<?= $_COMPANY->getAppCustomization()['group']['group0_color']; ?>"><?= $_COMPANY->getAppCustomization()['group']['groupname0']; ?></span>
                    <?php } ?>

                        <?php if ($post->val('chapterid')) { ?>
                                <?= gettext("in")?>    
                            <?php foreach(explode(',',$post->val('chapterid')) as $chid){ ?>
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
                    <span class="chapter-label ml-1" style="color:<?= $ch['colour'] ?>"><i class="fas fa-layer-group" style="color:<?= $ch['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($ch['channelname']); ?></span>
                    <?php
                        }
                    ?>
                    </span>
                </div>
                <!-- Post second Row End -->
                <div class="col-sm-12" id="post-inner">
                    <?php echo $post->val('post'); ?>
                </div>
                <?php 		} else { ?>
                <!-- Present the remaining  announcements (after first three) in summary -->
                <?php 		preg_match('/(src="[^>]+")/i', $post->val('post'), $src); ?>

                <!-- Post content row of start -->
                <div class="<?= (empty($src[0]))?'col-sm-12':'col-sm-8'; ?>">
                    <!-- Post header row start -->
                    <div class="">
                        <a role="button" style="text-decoration:none;color:#fff;"
                            onclick="getAnnouncementDetailOnModal('<?= $_COMPANY->encodeId($post->id())?>','<?= $_COMPANY->encodeId($chapterid)?>','<?= $_COMPANY->encodeId($channelid)?>')"
                            href="javascript:void(0);"
                        >
                            <?php if ($post->isDraft()) { ?>
                            <h2 class="active" style="color:red;"><?php echo $post->val('title'); ?>
                                <img src="img/draft_ribbon.png" alt="Draft icon image" height="16px"/>
                            </h2>
                            <?php } elseif ($post->isUnderReview()){ ?>
                                <h2 class="active" style="color:darkorange;"><?php echo $post->val('title'); ?>
                                    <img src="img/review_ribbon.png" alt="Draft icon image" height="16px"/>
                                </h2>
                            <?php } elseif ($post->val('isactive') == Post::STATUS_AWAITING){ ?>
                                <h3 style="color:deepskyblue;" >
                                    <?= $post->val('title'); ?>
                                    <img src="img/schedule.png" alt="Schedule icon image" height="16px"/>
                                </h3>
                            <?php } else { ?>
                            <h2 class="active">
                                <?php echo $post->val('title'); ?>
                            </h2>
                            <?php if($post->val('pin_to_top')){ ?>
                            <i class="fa fa-thumbtack ml-1" style="color:#0077b5;vertical-align:super;font-size: small" aria-hidden="true"></i>
                            <?php } ?>

                            <?php } ?>
                        </a>
                    </div>

                    <!-- Post second Row Start -->
                    <div class="">
                    <span class="dta-tm">
                        <?= ($post->val('isactive') == Post::STATUS_DRAFT || $post->val('isactive') == Post::STATUS_UNDER_REVIEW)? gettext("Created on"): ( $post->val('isactive') == Post::STATUS_AWAITING ? gettext("Scheduled to publish on") : gettext("Published on") ) ?> <?=
                            $_USER->formatUTCDatetimeForDisplayInLocalTimezone(( $post->val('isactive') == Post::STATUS_DRAFT ? $post->val('postedon') : $post->val('publishdate') ), true, true, true);
                        ?>
                        <?php if($post->val('groupid') == 0 ){ ?>
                            <?= gettext("in")?>
                            <span class="group-label ml-1" style="color:<?= $_COMPANY->getAppCustomization()['group']['group0_color']; ?>"><?= $_COMPANY->getAppCustomization()['group']['groupname0']; ?></span>
                        <?php } ?>
                        <?php if ($post->val('chapterid')) { ?>
                                in    
                            <?php foreach(explode(',',$post->val('chapterid')) as $chid){ ?>
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
                        <span class="chapter-label ml-1" style="color:<?= $ch['colour'] ?>"><i class="fas fa-layer-group" style="color:<?= $ch['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($ch['channelname']); ?></span>
                        <?php
                            }
                        ?>
                    </span>
                </div>
                    <!-- Post second Row End -->

                    <div class="">
                    <?= substr(strip_tags($post->val('post')),0,400)."..."; ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <a role="button" aria-label="<?= sprintf(gettext('Read more about %s'), $post->val('title'));?>" style="text-decoration:underline;color:#0077B5 !important"
                        onclick="getAnnouncementDetailOnModal('<?= $_COMPANY->encodeId($post->id())?>','<?= $_COMPANY->encodeId($chapterid)?>','<?= $_COMPANY->encodeId($channelid)?>')"
                        href="javascript:void(0);">
                        <?= gettext('Read more'); ?>
                    </a>
                    </div>
                </div>
    <?php 		if(!empty($src[0])){ ?>
                <div class="col-sm-4">
                    <div class="img-ev img-responsive">
                        <a role="button" style="text-decoration:none;color:#fff;"
                            onclick="getAnnouncementDetailOnModal('<?= $_COMPANY->encodeId($post->id())?>','<?= $_COMPANY->encodeId($chapterid)?>','<?= $_COMPANY->encodeId($channelid)?>')"
                            href="javascript:void(0);"
                            >
                            <img <?php echo $src[0]; ?> alt="Announcement picture" class="img-responsive">
                        </a>
                    </div>
                </div>

    <?php 	    } ?>
                <?php 		} ?>
                <!-- Post content row end -->
                <!-- Comments/Likes row start -->
                <div class="col-sm-12">
                    <div class="col-sm-1">
                    </div>

                    <div class="col-sm-11">
                        <div class="link-icons img-down">
                            <?php if($post->val('isactive') == 1){ ?>
                                <?php if ($_COMPANY->getAppCustomization()['post']['likes']) { ?>
                            <div id="x<?= $i; ?>" class="like-2">
                                <span  style="cursor:pointer;">
                                    <a role="button" aria-label="<?= sprintf(gettext('like %1$s %3$s. %1$s has %2$s likes'),$post->val('title'), Post::GetLikeTotals($post->id()), Post::GetCustomName(false));?>" onclick="getAnnouncementDetailOnModal('<?= $_COMPANY->encodeId($post->id())?>','<?= $_COMPANY->encodeId($chapterid)?>','<?= $_COMPANY->encodeId($channelid)?>')"
                                    href="javascript:void(0);">
                                        <i class="fa fa-thumbs-up fa-regular newgrey" title="<?= gettext('Like') ?>"></i>
                                        <span class="gh1"><?= Post::GetLikeTotals($post->id()) ?></span>
                                    </a>
                                </span>
                            </div>
                            <?php 
                            }
                            if ($_COMPANY->getAppCustomization()['post']['comments']) { ?>
                            <div class="review-2">
                                <a role="button" aria-label="<?= sprintf(gettext('comment %1$s %3$s. %1$s has %2$s comments'),$post->val('title'), Post::GetCommentsTotal($post->id()), Post::GetCustomName(false));?>" onclick="getAnnouncementDetailOnModal('<?= $_COMPANY->encodeId($post->id())?>','<?= $_COMPANY->encodeId($chapterid)?>','<?= $_COMPANY->encodeId($channelid)?>')"
                                href="javascript:void(0);">
                                    <i class="fa fa-regular fa-comment-dots newgrey" title="<?= gettext('Total Comments') ?>"></i>
                                    <span class="gh1"><?= Post::GetCommentsTotal($post->id()); ?></span>
                                </a>
                            </div>
                            <?php } 
                         } ?>
                        </div>
                    </div>

                </div>


                <!-- Print seperator between the rows -->
                <div class="col-lg-12"><hr></div>
            </div>
            <?php 	} ?>
            <!-- end of loop for printing posts -->
            </div>
            <?php if ($show_more) { // End fragment with span month element to pass next month ?>
                <span style="display: none">_l_=<?=$show_more?></span>
             <?php } ?>
        <?php }else{ ?>
            <!-- Print no announcements message -->
            <div class="container w6">
                <div class="col-sm-12 bottom-sp">
                    <br/>
                    <p style="text-align:center;margin-top:0px">Whoops!</p>
                    <p style="text-align:center;margin-top:-40px">
                        <img src="../image/nodata/no-discussion.png"  alt="" height="200px;"/>
                    </p>
                    <p style="text-align:center;margin-top:-40px;color:#767676;"><?= sprintf(gettext("Stay tuned for %s to be posted"), Post::GetCustomName(true)); ?></p>
                </div>
            </div>
        <?php } ?>
            <!-- end of if -->


