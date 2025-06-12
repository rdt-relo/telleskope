<!--
This template is used to Like/unlike Topic (i.e. Announcement, event, newsletter etc) and show latest 10 likers
-->
<?php $maxLikersToShow = $maxLikersToShow ?? 10; ?>

<div class="row">
<div  class="col-12 p-0 like-post-block mt-3 mb-3">

<?php //For accessiblity we have added dynamic aria-label for like/unlike post type.
  $float_toolbar_direction = 'tskp-like-toolbar-top';
    if($likeUnlikeMethod == "EventTopic"){
        $postType = "Event";
        $topictype = 'EVT';
    }elseif($likeUnlikeMethod == "DiscussionTopic"){
        $postType = "Discussion";
        $topictype = 'DIS';
    }elseif($likeUnlikeMethod == "AnnouncementTopic"){
        $postType = Post::GetCustomName(true); 
        $topictype = 'POS';
    }elseif($likeUnlikeMethod == "NewsletterTopic"){
        $postType = "Newsletters";
        $topictype = 'NWS';
    }elseif($likeUnlikeMethod == "AlbumMediaTopic"){
        $postType = "Albums Media";
        $topictype = 'ALM';
        $float_toolbar_direction = 'tskp-like-toolbar-bottom';
    }elseif ($likeUnlikeMethod == "RecognitionTopic"){
        $postType = "Recognition";
        $topictype = 'REC';
    }

    if($totalLikers == 0){
        $ariaLabel = sprintf(gettext('like %s'), $postType);
    }else if($myLikeStatus > 0){
        $ariaLabel = sprintf(gettext('Unlike %1$s, %1$s has %2$s likes'), $postType, $totalLikers);
    }else{
        $ariaLabel = sprintf(gettext('Like %1$s, %1$s has %2$s likes'), $postType, $totalLikers,);
    }
?>
<div class="reactions-topic <?= $topictype === 'NWS' ? 'ml-2 mr-2' : '' ?>" data-topic-type="<?= $topictype ?>" data-topic-id="<?= $_COMPANY->encodeId($topicid) ?>" data-like-unlike-method="<?= $likeUnlikeMethod ?>">
    <div class="reactions-placeholder"></div>
</div>
</div>

<script>
// For accessiblity screen reading notification.
$("#like_unlike_notification").append('<?= $totalLikers.' '.gettext('like'); ?>');

<?php
  $likeTotalsByType = array_map('intval', array_column($likeTotalsByType, 'cc', 'reactiontype'));
  $json = json_encode($likeTotalsByType);
?>
$('.reactions-topic').each(function() {
                const topicType = $(this).data('topic-type');
                const topicId = $(this).data('topic-id');
                const likeUnlikeMethod = $(this).data('like-unlike-method');
                ReactionsModule.init($(this).find('.reactions-placeholder'), {
                    topicType,
                    topicId,
                    likeUnlikeMethod,
                    initialReactions: JSON.parse('<?= $json ?>'),
                    currentReaction: '<?= $myLikeType ?? '' ?>',
                    floatToolbarDirection: '<?= $float_toolbar_direction ?>',
                });
            });
</script>
</div>
