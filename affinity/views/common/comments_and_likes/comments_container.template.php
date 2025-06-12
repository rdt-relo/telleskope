<style>
    .comment-container {
    padding: 13px 7px;
    margin: 0 18px;
}
</style>
<!--
    ## Widget Parameters ##
    /**
        * Dependencies for Comment Widget
        * $comments
        * $commentid (default 0)
        * $groupid
        * $topicid
        * $disableAddEditComment
        * $submitCommentMethod
        * $mediaUploadAllowed
        * $sectionHeading (optional)
        * $wide_layout (Optional default false)
    */
 -->
<?php
$encCommentid = $_COMPANY->encodeId($commentid);
$sectionHeading = $sectionHeading ?? gettext('Comments'); ?>
<div class="<?=isset($wide_layout)?"":"col-md-12 p-0 mt-3"?> comment-counter">
    <h2><label for="commentarea<?= $encCommentid; ?>" id="commentsHeading"><?= $sectionHeading ?></label></h2>
</div>
<div class="<?=isset($wide_layout)?"":"col-md-12 p-0"?> mt-3">
    <div id="teamMessages">
    <?php if(!$disableAddEditComment){ ?>
        <?php include(__DIR__ . "/comment_input.template.php"); ?>
    <?php } ?>
        <?php include(__DIR__ . "/get_comments_list.template.php"); ?>
    </div>
</div>