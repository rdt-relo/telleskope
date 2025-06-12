<?php
  $discussion_container_classes ??= '';
  $discussion_block_classes ??= 'discussion-block';
?>
    <!-- start of IF for printing discussions -->
        <?php if(!empty($discussions)) { ?>
            <div class="<?= $discussion_container_classes ?>">
            <!-- start of loop for printing discussions -->
            <?php 	for($i=0;$i<$max_iter;$i++) {
                $discussion = Discussion::GetDiscussion($discussions[$i]['discussionid']);
                $handlids = $discussion->val('handleids');
                $hashtags = array();
                if (!empty($handlids)){
                    $hashtags = HashtagHandle::GetHandlesByIds($handlids);
                }
                $creator = User::GetUser($discussion->val('createdby'));

                $latestComment = $discussion->val('anonymous_post') ? Discussion::GetCommentsAnonymized_2($discussion->id()) : Discussion::GetComments_2($discussion->id());
            ?>
            <?php
                    if (!$discussion->isPublished()) {
                        if ($discussion->val('groupid') == 0) {
                            continue; // Do not show unpublished admin level posts
                        } elseif (!$_USER->canCreateOrPublishContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'), $discussion->val('channelid'))) {
                            continue; // Do not unpublished posts if the user can update them
                        }
                    }
            ?>
            <div class="row <?= $discussion_block_classes ?>">
                <?php 	preg_match('/(src="[^>]+")/i', $discussion->val('discussion'), $src); ?>
                    <!-- Discussion content row of start -->
                    <div class="<?= (empty($src[0]))?'col-sm-12':'col-md-10'; ?>">
                        <!-- Discussion header row start -->
                        <div class="">
                            <a role="button" style="text-decoration:none;color:#fff;"
                                href="javascript:void(0);"
                                onclick="getDiscussionDetailOnModal('<?= $_COMPANY->encodeId($discussion->id()) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId(0) ?>')">
                                <?php if ($discussion->isDraft() || $discussion->isUnderReview()) { ?>
                                <h2 class="active" style="color:red;"><?= $discussion->val('title'); ?>
                                    <img src="img/draft_ribbon.png" alt="Draft icon image" height="16px"/>
                                </h2>
                                <?php } else if ($discussion->val('isactive') == Discussion::STATUS_AWAITING){ ?>
                                    <h3 style="color:deepskyblue;" >
                                        <?= $discussion->val('title'); ?>
                                        <img src="img/schedule.png" alt="Schedule icon image" height="16px"/>
                                    </h3>
                                <?php } else { ?>
                                <h2 class="active">
                                    <?= $discussion->val('title'); ?>
                                </h2>
                                </a>
                                <?php if($discussion->val('pin_to_top')){ ?>
                                    <i role='img' aria-label="<?= gettext("Pinned discussion")?>" class="fa fa-thumbtack ml-1" style="color:#0077b5;vertical-align:super;font-size: small"></i>
                                <?php } ?>
                                <?php } ?>
                                
                            
                        </div>

                        <!-- Discussion second Row Start -->
                        <div class="">
                        <span class="dta-tm">
                            
                            <?php if($discussion->val('groupid') == 0 ){ ?>
                                <span class="group-label ml-1" style="color:<?= $_COMPANY->getAppCustomization()['group']['group0_color']; ?>"><?= $_COMPANY->getAppCustomization()['group']['groupname0']; ?></span>
                            <?php } ?>
                            <?php if ($discussion->val('chapterid')) { ?> 
                                <?php foreach(explode(',',$discussion->val('chapterid')) as $chid){ ?>
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
                            <span class="chapter-label ml-1" style="color:<?= $ch['colour'] ?>"><i class="fas fa-layer-group" style="color:<?= $ch['colour'] ?>" aria-hidden="true"></i>&nbsp;<?= htmlspecialchars($ch['channelname']); ?></span>
                            <?php
                                }
                            ?>
                        </span>
                    </div>
                        <!-- Discussion second Row End -->

                        <div class="">
                        <?= substr(strip_tags($discussion->val('discussion')),0,400)."..."; ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <a role="button" aria-label="<?= sprintf(gettext('Read more about %s'),$discussion->val('title'));?>" style="text-decoration:underline;color:#0077B5 !important"
                        href="javascript:void(0);"
                        onclick="getDiscussionDetailOnModal('<?= $_COMPANY->encodeId($discussion->id()) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId(0) ?>')"><?= gettext('Read more'); ?>
                        </a>
                        </div>
                    </div>
        <?php 		if(!empty($src[0])){ ?>
                    <div class="col-md-2">
                        <div class="img-ev img-responsive">
                            <a role="button" style="text-decoration:none;color:#fff;"
                                href="javascript:void(0);"
                                onclick="getDiscussionDetailOnModal('<?= $_COMPANY->encodeId($discussion->id()) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId(0) ?>')"
                            >
                                <img <?= $src[0]; ?> alt="Announcement picture" class="img-responsive">
                            </a>
                        </div>
                    </div>

     <?php 	    } ?>

                    <!-- Discussion content row end -->
                    <!-- Hashtag start -->
                    <?php if(!empty($hashtags)){ ?>

                        <div class="col-sm-12">
                            <?php foreach($hashtags as $hashtag){ ?>
                                <a role="button" href="<?= $_COMPANY->getAppURL($_ZONE->val('app_type'))."hashtag?handle=".$hashtag['handle']; ?>">#<?= $hashtag['handle']; ?></a>&emsp;
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <!-- Hashtag End-->

                    <!-- Comments/Likes row start -->
                    <div class="col-sm-12 col-12 discussion-block-footer">
                        <div>
                            <div class="col-sm-5 col-12 px-2">
                                <a role="button"
                                href="javascript:void(0);"
                                onclick="getDiscussionDetailOnModal('<?= $_COMPANY->encodeId($discussion->id()) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId(0) ?>')"
                                 style="color:#767676 !important;">
                                    <i class="fa fa-user gray"></i>&nbsp;&nbsp;
                                    <?php if ($discussion->val('anonymous_post') == 1){
                                        echo gettext("Anonymous");
                                    }else{
                                        echo $creator ? $creator->getFullName() : 'Deleted User';
                                    } ?>
                                </a>
                            </div>

                            <div class="col-sm-2 col-12 px-2">
                                <span id="x<?= $i; ?>" style="cursor:pointer; padding-right:12px;">
                                    <a role="button" aria-label="<?= sprintf(gettext('like %1$s discussion. %1$s has %2$s likes'),$discussion->val('title'), Discussion::GetLikeTotals($discussion->id()));?>" href="javascript:void(0);"
                                    onclick="getDiscussionDetailOnModal('<?= $_COMPANY->encodeId($discussion->id()) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId(0) ?>')"
                                    >
                                        <i class="fa fa-thumbs-up fa-regular newgrey" title="<?= gettext('Like') ?>"></i>
                                        <span style="margin-left:2px;color:#808080 !important;"><?= Discussion::GetLikeTotals($discussion->id()) ?></span>
                                    </a>
                                </span>
                                <span>
                                    <a role="button" aria-label="<?= sprintf(gettext('Comments %1$s discussion. %1$s has %2$s comments'),$discussion->val('title'), Discussion::GetCommentsTotal($discussion->id()));?>" href="javascript:void(0);"
                                        onclick="getDiscussionDetailOnModal('<?= $_COMPANY->encodeId($discussion->id()) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId(0) ?>')"
                                        >
                                        <i class="fa fa-regular fa-comment-dots newgrey" title="<?= gettext('Total Comments') ?>"></i>
                                        <span style="margin-left:2px;color:#808080 !important;"><?= Discussion::GetCommentsTotal($discussion->id()); ?></span>
                                    </a>
                                </span>
                            </div>

                            <div class="col-sm-5 col-12 px-2" >
                                <?php if(!empty($latestComment)){ ?>
                                <a role="button" 
                                    href="javascript:void(0);"
                                    onclick="getDiscussionDetailOnModal('<?= $_COMPANY->encodeId($discussion->id()) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId(0) ?>')"
                                    style="color:#767676 !important;">
                                    <i class="fal fa fa-comment gray"></i>&nbsp;<?= ($discussion->val('anonymous_post') == 1) ? gettext('Anonymous') : $latestComment[0]['firstname'].' '.$latestComment[0]['lastname']?>
                                </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
            </div>
            <?php 	} ?>
            <!-- end of loop for printing posts -->
            </div>
            <?php if ($show_more) { // End fragment with span month element to pass next month ?>
                <span style="display: none">_l_=<?=$show_more?></span>
             <?php } ?>
        <?php }else{ ?>
            <!-- Print no discussions message -->
            <div class="container w6">
                <div class="col-sm-12 bottom-sp">
                    <br/>
                    <p style="text-align:center;margin-top:0px">Whoops!</p>
                    <p style="text-align:center;margin-top:-40px">
                        <img src="../image/nodata/no-discussion.png"  alt="" height="200px;"/>
                    </p>
                    <p style="text-align:center;margin-top:-40px;color:#767676;"><?= gettext("Looks like there aren't any discussions yet"); ?></p>
                </div>
            </div>
        <?php } ?>
            <!-- end of if -->
<?php
// If there is Event that we need to show... show it now.
if (!empty($_SESSION['show_discussion_id'])) {
    $enc_nid = $_COMPANY->encodeId($_SESSION['show_discussion_id']);
    unset($_SESSION['show_discussion_id']);
    ?>
    <script>
        $(document).ready(function () {
            getDiscussionDetailOnModal('<?= $enc_nid?>', '<?= $_COMPANY->encodeId(0)?>', '<?= $_COMPANY->encodeId(0)?>');
        });
    </script>
<?php } ?>