<?php
ob_start(); 
define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__.'/../affinity/head.php';//Common head.php includes destroying Session for timezone so defining INDEX_PAGE. Need head for logging and protecting

###### All Ajax Calls ##########

/**
 * 
 * ######### Common methods START ====>>>
 * 
 */
if (isset($_GET['updateCommentCommon']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 1 ||
        !isset($_POST['message'])
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $message =(string) $_POST['message'];
    if (Str::IsEmptyHTML($message)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("This field cannot remain empty. To continue input text into this field."), gettext('Error'));
    }
   
    if (Comment::UpdateComment_2($topicid, $commentid, $message)) {
        AjaxResponse::SuccessAndExit_STRING(1, htmlspecialchars($message), gettext("Comment updated"), gettext('Success'));
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
    }

    exit();
}
elseif (isset($_GET['likeDislikeCommentCommon']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $action = $_POST['action'];
    $reactiontype = $_POST['reactiontype'] ?? 'like';
    
    Comment::LikeUnlike($commentid, $reactiontype);
    $likes = $_POST['likes'];
    if ($action == 1) {
        $likes = $likes + 1;

        Points::HandleTrigger('COMMENT_LIKE', [
            'commentId' => $commentid,
        ]);
    } else {
        $likes = $likes - 1;

        Points::HandleTrigger('COMMENT_UNLIKE', [
            'commentId' => $commentid,
        ]);
    }
    ?>

    <span class="comment-like-button">
        <?php if ($action == 1) { ?>
            <button aria-label="liked" class="btn-no-style fa fa-thumbs-up fa-solid" style="min-width:0px; padding:0px !important;"
               onclick="likeDislikeCommentCommon('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($commentid); ?>',2,'<?= $likes; ?>')"
               ></button>
        <?php } else { ?>
            <button aria-label="like" class="btn-no-style fa fa-thumbs-up fa-regular newgrey" style="min-width:0px; padding:0px !important;"
               onclick="likeDislikeCommentCommon('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($commentid); ?>',1,'<?= $likes; ?>')"
               ></button>
        <?php } ?>
            &nbsp;
    </span>
    <span class="comment-like-icon"><?= $likes > 1 ? $likes .' '.gettext("likes") : $likes . ' '.gettext("like"); ?></span>
    <?php

    exit();
}

elseif (isset($_GET['getCommentAttachment']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
 
    if (
        ($commentid = $_COMPANY->decodeId($_GET['getCommentAttachment'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $comment = Comment::GetCommentDetail($commentid);
   
    if ($comment){        
        $jsonData = json_decode($comment['attachment'], true);
        $attachment = Comment::DownloadAttachment($jsonData['file_id']);   
       
        if ($attachment) {
            ob_end_clean(); // Destroy the buffer that was created by included files, inner buffer.
            ob_end_clean(); // Destroy the buffer that we created on the first line, i.e. Top most buffer.
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $attachment['ContentType']);
            header('Content-Disposition: attachment; filename=' . $jsonData['file_id']);
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            echo $attachment['Body'];
        }

    }
}

/**
 * 
 * ############ Commen Methods End <<<=====
 * 
 */

/**
 * 
 * ########## Team Methods START =====>>>
 * 
 */

elseif (isset($_GET['NewTeamsListComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $commentid = $_COMPANY->decodeId($_POST['commentid']);
    $comment = $_POST['message'];

    if (empty($_FILES)){
        if (Str::IsEmptyHTML($comment)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Comment field cannot remain empty. To continue input text into this field."), gettext('Error'));
        }
    }

    if ($commentid>0){ // Sub Comment
        $id = Comment::CreateComment_2($commentid, $comment, $_FILES);
        $commentid = 0; // Reset input
    } else {
        $id = TeamTask::CreateComment_2($topicid, $comment, $_FILES);
    }
    
    if ($id) {
        $comments = TeamTask::GetComments_2($topicid);
        /**
         * Dependencies for Comment Widget
         * $comments
         * $commentid (default 0)
         * $groupid
         * $topicid
         * $disableAddEditComment
         * $submitCommentMethod
         * $mediaUploadAllowed
        */
        $disableAddEditComment = false;
        $submitCommentMethod = "TeamsListComment";
        $mediaUploadAllowed = $_COMPANY->getAppCustomization()['teams']['enable_media_upload_on_comment'];
        include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
        include(__DIR__ . "/views/common/comments_and_likes/get_comments_list.template.php");
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
    }

    exit();
}
elseif (isset($_GET['subTeamsListComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    /**
     * Dependencies for Comment Widget
     * $commentid 
     * $disableAddEditComment
     * $submitCommentMethod
     * $mediaUploadAllowed
     */
    $disableAddEditComment = false;
    $submitCommentMethod = "TeamsListComment";
    $mediaUploadAllowed = $_COMPANY->getAppCustomization()['teams']['enable_media_upload_on_comment'];
    include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
    exit();
}

elseif (isset($_GET['deleteTeamsListComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $parent = $_COMPANY->decodeId($_POST['parent']) > 0 ? $_COMPANY->decodeId($_POST['parent']) : 0;
    if ($parent == 0){
        TeamTask::DeleteComment_2($topicid, $commentid);
    } elseif($parent > 0) {
        Comment::DeleteComment_2($topicid, $commentid);
    } else {
        $parent =  -1;
    }
    echo $parent;
    exit();
}


/**
 * 
 * ######### Team Method END <<<====
 * 
 */


/**
 * 
 * ############ Announcement Methods START ====>>>
 * 
 */

elseif (isset($_GET['NewAnnouncementComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $commentid = $_COMPANY->decodeId($_POST['commentid']);
    $comment = $_POST['message'];

    if (empty($_FILES)){
        if (Str::IsEmptyHTML($comment)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Comment field cannot remain empty. To continue input text into this field."), gettext('Error'));
        }
    }

    if ($commentid>0){ // Sub Comment
        $id = Comment::CreateComment_2($commentid, $comment, $_FILES);
        $commentid = 0; // Reset input
    } else {
        $id = Post::CreateComment_2($topicid, $comment, $_FILES);
    }
    
    if ($id) {
        /**
         * Dependencies for Comment Widget
         * $comments
         * $commentid (default 0)
         * $groupid
         * $topicid
         * $disableAddEditComment
         * $submitCommentMethod
         * $mediaUploadAllowed
        */
        $comments = Post::GetComments_2($topicid);
        $disableAddEditComment = false;
        $submitCommentMethod = "AnnouncementComment";
        $mediaUploadAllowed = $_COMPANY->getAppCustomization()['post']['enable_media_upload_on_comment'];
    
        include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
        include(__DIR__ . "/views/common/comments_and_likes/get_comments_list.template.php");
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
    }

    exit();
}
elseif (isset($_GET['subAnnouncementComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    /**
     * Dependencies for Comment Widget
     * $commentid 
     * $disableAddEditComment
     * $submitCommentMethod
     * $mediaUploadAllowed
     */
    $disableAddEditComment = false;
    $submitCommentMethod = "AnnouncementComment";
    $mediaUploadAllowed = $_COMPANY->getAppCustomization()['post']['enable_media_upload_on_comment'];

    include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
    exit();
}

elseif (isset($_GET['deleteAnnouncementComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $parent = $_COMPANY->decodeId($_POST['parent']);
    if ($parent == 0){
       Post::DeleteComment_2($topicid, $commentid); 
    } elseif($parent > 0) {
        Comment::DeleteComment_2($topicid, $commentid);
    } else {
        $parent =  -1;
    }
    echo $parent;

    exit();
}

elseif (isset($_GET['likeUnlikeAnnouncementTopic']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1 ||
        ($post = Post::GetPost($topicid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $reactiontype = $_POST['reactiontype'] ?? 'like';

    if (Post::LikeUnlike($topicid, $reactiontype)){
        $myLikeType = Post::GetUserReactionType($topicid);
        $myLikeStatus = (int) !empty($myLikeType);
        $latestLikers = Post::GetLatestLikers($topicid);
        $totalLikers = Post::GetLikeTotals($topicid);
        $likeTotalsByType = Post::GetLikeTotalsByType($topicid);
        $showAllLikers = true;
        $likeUnlikeMethod = 'AnnouncementTopic';
        include(__DIR__ . "/views/common/comments_and_likes/topic_like_and_likers_bedge.template.php");
    } else{
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong, please refresh page and try again."), gettext('Error'));
    }
}

elseif (isset($_GET['getLikersListAnnouncementTopic']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($topicid = $_COMPANY->decodeId($_GET['topicid'])) < 1 ||
        ($post = Post::GetPost($topicid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $modalTitle = sprintf(gettext("%s like user list"),$post->val('title'));
    $usersList = Post::GetLatestLikers($topicid,false,1000);
    /**
     * get_users_basic_info_list.template.php Dependency
     * $usersList
     */
    include(__DIR__ . "/views/templates/get_users_basic_info_list.template.php");
}

/**
 *
 * ############ Album Media Methods START ====>>>
 *
 */

elseif (isset($_GET['likeUnlikeAlbumMediaTopic']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
        ||
        ($album = Album::GetMedia($topicid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $reactiontype = $_POST['reactiontype'] ?? 'like';

    if (Album::LikeUnlike($topicid, $reactiontype)){
        $myLikeType = Album::GetUserReactionType($topicid);
        $myLikeStatus = (int) !empty($myLikeType);
        $latestLikers = Album::GetLatestLikers($topicid);
        $totalLikers = Album::GetLikeTotals($topicid);
        $likeTotalsByType = Album::GetLikeTotalsByType($topicid);
        $showAllLikers = true;
        $likeUnlikeMethod = 'AlbumMediaTopic';
        include(__DIR__ . "/views/common/comments_and_likes/topic_like_and_likers_bedge.template.php");
    } else{
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong! Please refresh page and try again."), gettext('Error!'));
    }
}
elseif (isset($_GET['getLikersListAlbumMediaTopic']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($topicid = $_COMPANY->decodeId($_GET['topicid'])) < 1)
    {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $modalTitle = gettext("Album Likes");
    $usersList = Album::GetLatestLikers($topicid,false,1000);
    /**
     * get_users_basic_info_list.template.php Dependency
     * $usersList
     */
    include(__DIR__ . "/views/templates/get_users_basic_info_list.template.php");
}


elseif (isset($_GET['NewAlbumMediaComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $commentid = $_COMPANY->decodeId($_POST['commentid']);
    $comment = trim($_POST['message']);
    if($comment == ''){
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please enter a valid comment."), gettext('Error!'));
    }

    if ($commentid>0){ // Sub Comment
        $id = Comment::CreateComment_2($commentid, $comment, $_FILES);
        $commentid = 0; // Reset input
    } else {
        $id = Album::CreateComment_2($topicid, $comment, $_FILES);
    }

    if ($id) {
        /**
         * Dependencies for Comment Widget
         * $comments
         * $commentid (default 0)
         * $groupid
         * $topicid
         * $disableAddEditComment
         * $submitCommentMethod
         * $mediaUploadAllowed
         */
        $comments = Album::GetComments_2($topicid);
        $disableAddEditComment = false;
        $submitCommentMethod = "AlbumMediaComment";
        $mediaUploadAllowed = false;

        include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
        include(__DIR__ . "/views/common/comments_and_likes/get_comments_list.template.php");
    } else {
        echo 0;
    }

    exit();
}
elseif (isset($_GET['subAlbumMediaComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    /**
     * Dependencies for Comment Widget
     * $commentid
     * $disableAddEditComment
     * $submitCommentMethod
     * $mediaUploadAllowed
     */
    $disableAddEditComment = false;
    $submitCommentMethod = "AlbumMediaComment";
    $mediaUploadAllowed = false;

    include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
    exit();
}

elseif (isset($_GET['deleteAlbumMediaComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 0 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $parent = $_COMPANY->decodeId($_POST['parent']) > 0 ? $_COMPANY->decodeId($_POST['parent']) : 0;
    if ($parent == 0){
        Album::DeleteComment_2($topicid, $commentid);
    } elseif($parent > 0) {
        Comment::DeleteComment_2($topicid, $commentid);
    } else {
        $parent =  -1;
    }
    echo $parent;
    exit();
}

/**
 * 
 * ########## Album Media Methods END  <<<<=====
 * 
 */

/**
 * 
 * ############ Discussion Methods START ====>>>
 * 
 */

elseif (isset($_GET['NewDiscussionComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1 ||
        ($discussion = Discussion::GetDiscussion($topicid)) == null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $commentid = $_COMPANY->decodeId($_POST['commentid']);
    $comment = $_POST['message'];

    if (empty($_FILES)){
        if (Str::IsEmptyHTML($comment)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Response field can't be empty. Please input some text."), gettext('Error'));
        }
    }

    if ($commentid>0){ // Sub Comment
        $id = Comment::CreateComment_2($commentid, $comment, $_FILES);
        $commentid = 0; // Reset input
    } else {
        $id = Discussion::CreateComment_2($topicid, $comment, $_FILES);
    }
    
    if ($id) {
        /**
         * Dependencies for Comment Widget
         * $comments
         * $commentid (default 0)
         * $groupid
         * $topicid
         * $disableAddEditComment
         * $submitCommentMethod
         * $mediaUploadAllowed
        */
        $comments = $discussion->val('anonymous_post') ? Discussion::GetCommentsAnonymized_2($topicid) : Discussion::GetComments_2($topicid);
        $disableAddEditComment = false;
        $submitCommentMethod = "DiscussionComment";
        $mediaUploadAllowed = $_COMPANY->getAppCustomization()['discussions']['enable_media_upload_on_comment'];
        $sectionHeading = gettext('Response');
    
        include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
        include(__DIR__ . "/views/common/comments_and_likes/get_comments_list.template.php");
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
    }

    exit();
}
elseif (isset($_GET['subDiscussionComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    /**
     * Dependencies for Comment Widget
     * $commentid 
     * $disableAddEditComment
     * $submitCommentMethod
     * $mediaUploadAllowed
     */
    $disableAddEditComment = false;
    $submitCommentMethod = "DiscussionComment";
    $mediaUploadAllowed = $_COMPANY->getAppCustomization()['discussions']['enable_media_upload_on_comment'];
    $sectionHeading = gettext('Response');

    include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
    exit();
}

elseif (isset($_GET['deleteDiscussionComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 1 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 0 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $parent = $_COMPANY->decodeId($_POST['parent']) > 0 ? $_COMPANY->decodeId($_POST['parent']) : 0;
    if ($parent == 0){
        Discussion::DeleteComment_2($topicid, $commentid); 
    } elseif($parent > 0) {
        Comment::DeleteComment_2($topicid, $commentid);
    } else {
        $parent =  -1;
    }
    echo $parent;
    exit();
}

elseif (isset($_GET['likeUnlikeDiscussionTopic']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1 ||
        ($discussion = Discussion::GetDiscussion($topicid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $reactiontype = $_POST['reactiontype'] ?? 'like';

    if (Discussion::LikeUnlike($topicid, $reactiontype)){
        $myLikeType = Discussion::GetUserReactionType($topicid);
        $myLikeStatus = (int) !empty($myLikeType);
        $latestLikers = $discussion->val('anonymous_post') ? Discussion::GetLatestLikersAnonymized($topicid) : Discussion::GetLatestLikers($topicid);
        $totalLikers = Discussion::GetLikeTotals($topicid);
        $likeTotalsByType = Discussion::GetLikeTotalsByType($topicid);
        $showAllLikers = true;
        $likeUnlikeMethod = 'DiscussionTopic';
        include(__DIR__ . "/views/common/comments_and_likes/topic_like_and_likers_bedge.template.php");
    } else{
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong! Please refresh page and try again."), gettext('Error!'));
    }
}

elseif (isset($_GET['getLikersListDiscussionTopic']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($topicid = $_COMPANY->decodeId($_GET['topicid'])) < 1 ||
        ($discussion = Discussion::GetDiscussion($topicid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $modalTitle = sprintf(gettext("%s Likes"),$discussion->val('title'));
    // Do not show likers if discussion is anonymous
    $usersList = $discussion->val('anonymous_post') ? Discussion::GetLatestLikersAnonymized($topicid) : Discussion::GetLatestLikers($topicid);

    /**
     * get_users_basic_info_list.template.php Dependency
     * $usersList
     */
    include(__DIR__ . "/views/templates/get_users_basic_info_list.template.php");
}

/**
 * 
 * ########## Discussion Method END  <<<<=====
 * 
 */

/*
 * ########## Newsletter Methods START =====>>>
 *
 */

 elseif (isset($_GET['NewNewsletterComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $commentid = $_COMPANY->decodeId($_POST['commentid']);
    $comment = $_POST['message'];

    if (empty($_FILES)){
        if (Str::IsEmptyHTML($comment)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Comment field cannot remain empty. To continue input text into this field."), gettext('Error'));
        }
    }

    if ($commentid>0){ // Sub Comment
        $id = Comment::CreateComment_2($commentid, $comment, $_FILES);
        $commentid = 0; // Reset input
    } else {
        $id = Newsletter::CreateComment_2($topicid, $comment, $_FILES);
    }

    if ($id) {
        $comments = Newsletter::GetComments_2($topicid);
        /**
         * Dependencies for Comment Widget
         * $comments
         * $commentid (default 0)
         * $groupid
         * $topicid
         * $disableAddEditComment
         * $submitCommentMethod
         * $mediaUploadAllowed
        */
        $disableAddEditComment = false;
        $submitCommentMethod = "NewsletterComment";
        $mediaUploadAllowed = $_COMPANY->getAppCustomization()['newsletters']['enable_media_upload_on_comment'];
        include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
        include(__DIR__ . "/views/common/comments_and_likes/get_comments_list.template.php");
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
    }

    exit();
}
elseif (isset($_GET['subNewsletterComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    /**
     * Dependencies for Comment Widget
     * $commentid
     * $disableAddEditComment
     * $submitCommentMethod
     * $mediaUploadAllowed
     */
    $disableAddEditComment = false;
    $submitCommentMethod = "NewsletterComment";
    $mediaUploadAllowed = $_COMPANY->getAppCustomization()['newsletters']['enable_media_upload_on_comment'];
    include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
    exit();
}

elseif (isset($_GET['deleteNewsletterComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $parent = $_COMPANY->decodeId($_POST['parent']) > 0 ? $_COMPANY->decodeId($_POST['parent']) : 0;
    if ($parent == 0){
        Newsletter::DeleteComment_2($topicid, $commentid);
    } elseif($parent > 0) {
        Comment::DeleteComment_2($topicid, $commentid);
    } else {
        $parent =  -1;
    }
    echo $parent;
    exit();
}

elseif (isset($_GET['likeUnlikeNewsletterTopic']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1 ||
        ($newsletter = Newsletter::GetNewsletter($topicid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $reactiontype = $_POST['reactiontype'] ?? 'like';

    if (Newsletter::LikeUnlike($topicid, $reactiontype)){
        $myLikeType = Newsletter::GetUserReactionType($topicid);
        $myLikeStatus = (int) !empty($myLikeType);
        $latestLikers = Newsletter::GetLatestLikers($topicid);
        $totalLikers = Newsletter::GetLikeTotals($topicid);
        $likeTotalsByType = Newsletter::GetLikeTotalsByType($topicid);
        $showAllLikers = true;
        $likeUnlikeMethod = 'NewsletterTopic';
        include(__DIR__ . "/views/common/comments_and_likes/topic_like_and_likers_bedge.template.php");
    } else{
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong! Please refresh page and try again."), gettext('Error!'));
    }
}

elseif (isset($_GET['getLikersListNewsletterTopic']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($topicid = $_COMPANY->decodeId($_GET['topicid'])) < 1 ||
        ($newsletter = Newsletter::GetNewsletter($topicid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $modalTitle = sprintf(gettext("%s Likes"),$newsletter->val('title'));
    $usersList = Newsletter::GetLatestLikers($topicid);

    /**
     * get_users_basic_info_list.template.php Dependency
     * $usersList
     */
    include(__DIR__ . "/views/templates/get_users_basic_info_list.template.php");
}
elseif (isset($_GET['getLikersListRecognitionTopic']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if(
        ($topicid = $_COMPANY->decodeId($_GET['topicid'])) < 1 ||
        ($newsletter = Recognition::GetRecognition($topicid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $modalTitle = sprintf(gettext("%s Likes"),$newsletter->val('title'));
    $usersList = Recognition::GetLatestLikers($topicid);
    /**
     * get_users_basic_info_list.template.php Dependency
     * $usersList
     */
    include(__DIR__ . "/views/templates/get_users_basic_info_list.template.php");

}
/**
 *
 * ######### Event Method END <<<====
 *
 */


/**
 *
 * ########## Event Methods START =====>>>
 *
 */
 elseif (isset($_GET['NewEventComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $commentid = $_COMPANY->decodeId($_POST['commentid']);
    $comment = $_POST['message'];

    if (empty($_FILES)){
        if (Str::IsEmptyHTML($comment)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Comment field cannot remain empty. To continue input text into this field."), gettext('Error'));
        }
    }

    if ($commentid>0){ // Sub Comment
        $id = Comment::CreateComment_2($commentid, $comment, $_FILES);
        $commentid = 0; // Reset input
    } else {
        $id = Event::CreateComment_2($topicid, $comment, $_FILES);
    }

    if ($id) {
        $comments = Event::GetComments_2($topicid);
        /**
         * Dependencies for Comment Widget
         * $comments
         * $commentid (default 0)
         * $groupid
         * $topicid
         * $disableAddEditComment
         * $submitCommentMethod
         * $mediaUploadAllowed
        */
        $disableAddEditComment = false;
        $submitCommentMethod = "EventComment";
        $mediaUploadAllowed = $_COMPANY->getAppCustomization()['event']['enable_media_upload_on_comment'];
        include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
        include(__DIR__ . "/views/common/comments_and_likes/get_comments_list.template.php");
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
    }

    exit();
}
elseif (isset($_GET['subEventComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    /**
     * Dependencies for Comment Widget
     * $commentid
     * $disableAddEditComment
     * $submitCommentMethod
     * $mediaUploadAllowed
     */
    $disableAddEditComment = false;
    $submitCommentMethod = "EventComment";
    $mediaUploadAllowed = $_COMPANY->getAppCustomization()['event']['enable_media_upload_on_comment'];
    include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
    exit();
}

elseif (isset($_GET['deleteEventComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $parent = $_COMPANY->decodeId($_POST['parent']) > 0 ? $_COMPANY->decodeId($_POST['parent']) : 0;
    if ($parent == 0){
        Event::DeleteComment_2($topicid, $commentid);
    } elseif($parent > 0) {
        Comment::DeleteComment_2($topicid, $commentid);
    } else {
        $parent =  -1;
    }
    echo $parent;
    exit();
}

elseif (isset($_GET['likeUnlikeEventTopic']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1 ||
        ($event = Event::GetEvent($topicid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $reactiontype = $_POST['reactiontype'] ?? 'like';

    if (Event::LikeUnlike($topicid, $reactiontype)){
        $myLikeType = Event::GetUserReactionType($topicid);
        $myLikeStatus = (int) !empty($myLikeType);
        $latestLikers = Event::GetLatestLikers($topicid);
        $totalLikers = Event::GetLikeTotals($topicid);
        $likeTotalsByType = Event::GetLikeTotalsByType($topicid);
        $showAllLikers = true;
        $likeUnlikeMethod = 'EventTopic';
        include(__DIR__ . "/views/common/comments_and_likes/topic_like_and_likers_bedge.template.php");
    } else{
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong, please refresh page and try again."), gettext('Error'));
    }

}elseif (isset($_GET['likeUnlikeRecognitionTopic']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

     $topicid = $_COMPANY->decodeId($_POST['topicid']);
     $event = Recognition::GetRecognition($topicid);

   

    if (
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1 ||
        ($event = Recognition::GetRecognition($topicid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $reactiontype = $_POST['reactiontype'] ?? 'like';

    if (Recognition::LikeUnlike($topicid, $reactiontype)){
        $myLikeType = Recognition::GetUserReactionType($topicid);
        $myLikeStatus = (int) !empty($myLikeType);
        $latestLikers = Recognition::GetLatestLikers($topicid);
        $totalLikers = Recognition::GetLikeTotals($topicid);
        $likeTotalsByType = Recognition::GetLikeTotalsByType($topicid);
        $showAllLikers = true;
        $likeUnlikeMethod = 'RecognitionTopic';
        include(__DIR__ . "/views/common/comments_and_likes/topic_like_and_likers_bedge.template.php");
    } else{
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong, please refresh page and try again."), gettext('Error'));
    }


}
elseif (isset($_GET['NewRecognitionComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
if (
    ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
    ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
) {
    header(HTTP_BAD_REQUEST);
    exit();
}
$commentid = $_COMPANY->decodeId($_POST['commentid']);
$comment = $_POST['message'];

if (empty($_FILES)){
    if (Str::IsEmptyHTML($comment)) {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Comment field cannot remain empty. To continue input text into this field."), gettext('Error'));
    }
}


if ($commentid>0){ // Sub Comment
    $id = Comment::CreateComment_2($commentid, $comment, $_FILES);
    $commentid = 0; // Reset input
} else {
    
    $id = Recognition::CreateComment_2($topicid, $comment, $_FILES);
}

if ($id) {
    $comments = Recognition::GetComments_2($topicid);   
    /**
     * Dependencies for Comment Widget
     * $comments
     * $commentid (default 0)
     * $groupid
     * $topicid
     * $disableAddEditComment
     * $submitCommentMethod
     * $mediaUploadAllowed
    */
    $disableAddEditComment = false;
    $submitCommentMethod = "RecognitionComment";
    $mediaUploadAllowed = false;

    if ($_COMPANY->getAppCustomization()['recognition']['comments']) {
        $mediaUploadAllowed = $_COMPANY->getAppCustomization()['recognition']['enable_media_upload_on_comment'];
      }

      
 


    include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
    include(__DIR__ . "/views/common/comments_and_likes/get_comments_list.template.php");
} else {
    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
}
 exit();
}
elseif (isset($_GET['subRecognitionComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    /**
     * Dependencies for Comment Widget
     * $commentid 
     * $disableAddEditComment
     * $submitCommentMethod
     * $mediaUploadAllowed
     */
    $disableAddEditComment = false;
    $submitCommentMethod = "RecognitionComment";
    $mediaUploadAllowed = $_COMPANY->getAppCustomization()['recognition']['enable_media_upload_on_comment'];
    include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
    exit();
}
elseif (isset($_GET['deleteRecognitionComment']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $parent = $_COMPANY->decodeId($_POST['parent']) > 0 ? $_COMPANY->decodeId($_POST['parent']) : 0;
    if ($parent == 0){
        Recognition::DeleteComment_2($topicid, $commentid);
    } elseif($parent > 0) {
        Comment::DeleteComment_2($topicid, $commentid);
    } else {
        $parent =  -1;
    }
    echo $parent;
    exit();

}
elseif (isset($_GET['getLikersListEventTopic']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        ($topicid = $_COMPANY->decodeId($_GET['topicid'])) < 1 ||
        ($event = Event::GetEvent($topicid)) === NULL
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $modalTitle = sprintf(gettext("%s like user list"), $event->val('eventtitle'));
    $usersList = Event::GetLatestLikers($topicid,false,1000);
    /**
     * get_users_basic_info_list.template.php Dependency
     * $usersList
     */
    include(__DIR__ . "/views/templates/get_users_basic_info_list.template.php");
}


/**
 *
 * ######### Event Method END <<<====
 *
 */


 /**
 * 
 * ########## Team Message Methods START =====>>>
 * 
 */

elseif (isset($_GET['NewTeamMessages']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $commentid = $_COMPANY->decodeId($_POST['commentid']);
    $comment = $_POST['message'];

    if (empty($_FILES)){
        if (Str::IsEmptyHTML($comment)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Message field cannot remain empty. To continue input text into this field."), gettext('Error'));
        }
    }

    if ($commentid>0){ // Sub Comment
        $id = Comment::CreateComment_2($commentid, $comment, $_FILES);
        $commentid = 0; // Reset input
    } else {
        $id = Team::CreateComment_2($topicid, $comment, $_FILES);
    }
    
    if ($id) {
        $comments = Team::GetComments_2($topicid);
        /**
         * Dependencies for Comment Widget
         * $comments
         * $commentid (default 0)
         * $groupid
         * $topicid
         * $disableAddEditComment
         * $submitCommentMethod
         * $mediaUploadAllowed
        */
        $disableAddEditComment = false;
        $submitCommentMethod = "TeamMessages";
        $mediaUploadAllowed = $_COMPANY->getAppCustomization()['teams']['enable_media_upload_on_comment'];
        include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
        include(__DIR__ . "/views/common/comments_and_likes/get_comments_list.template.php");
    } else {
        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Something went wrong. Please try again."), gettext('Error'));
    }

    exit();
}
elseif (isset($_GET['subTeamMessages']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    /**
     * Dependencies for Comment Widget
     * $commentid 
     * $disableAddEditComment
     * $submitCommentMethod
     * $mediaUploadAllowed
     */
    $disableAddEditComment = false;
    $submitCommentMethod = "TeamMessages";
    $mediaUploadAllowed = $_COMPANY->getAppCustomization()['teams']['enable_media_upload_on_comment'];
    include(__DIR__ . "/views/common/comments_and_likes/comment_input.template.php");
    exit();
}

elseif (isset($_GET['deleteTeamMessages']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        ($groupid = $_COMPANY->decodeId($_POST['groupid'])) < 0 ||
        ($topicid = $_COMPANY->decodeId($_POST['topicid'])) < 1 ||
        ($commentid = $_COMPANY->decodeId($_POST['commentid'])) < 0
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    $parent = $_COMPANY->decodeId($_POST['parent']) > 0 ? $_COMPANY->decodeId($_POST['parent']) : 0;
    if ($parent == 0){
        Team::DeleteComment_2($topicid, $commentid);
    } elseif($parent > 0) {
        Comment::DeleteComment_2($topicid, $commentid);
    } else {
        $parent =  -1;
    }
    echo $parent;
    exit();
}


/**
 * 
 * ######### Team Message Method END <<<====
 * 
 */


else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}