<span id="comment-stats">
    <span class="comment-icon">
        <i role="button" aria-label="<?= $comment['subcomment_count']; ?> <?= gettext("reply");?> for <?= trim($comment['firstname']." ".$comment['lastname']); ?> comment" tabindex="0" class="fa fa-comment steelgray comment-input-toggle"
		<?php if(!$disableAddEditComment){ ?> 
			onclick="openSubcommentInputBox('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($comment['topicid']); ?>','<?= $_COMPANY->encodeId($comment['commentid']); ?>','<?= $submitCommentMethod; ?>');" onKeyPress="openSubcommentInputBox('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($comment['topicid']); ?>','<?= $_COMPANY->encodeId($comment['commentid']); ?>','<?= $submitCommentMethod; ?>');"
		<?php } ?>
		></i>&nbsp;
        </span>
    <span id="replyCommentsCount<?=  $_COMPANY->encodeId($comment['commentid']); ?>" class="comment-counts ">
        <span id="reply_count<?= $_COMPANY->encodeId($comment['commentid']); ?>" >
            <?= $comment['subcomment_count']; ?>
        </span>
        <?= $comment['subcomment_count']>1 ? gettext("replies") : gettext("reply");?>
    </span>
</span>
<!-- Reply to Comment form will populated here -->
<span id="add_sub_comment_<?= $_COMPANY->encodeId($comment['commentid']); ?>" class="pt-3" ></span>
