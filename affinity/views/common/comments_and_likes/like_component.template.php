<style>
    .fa-heart:focus {
        box-shadow: 0 0 0 .2rem #000000;
    }
</style>
<span  id="c_like<?= $encCommentid; ?>">
    <span class="comment-like-button">
    <?php if($myLikeStatus){ ?>
        <button role="button" aria-label="<?= $totalLikes>1 ? $totalLikes.' ' . gettext("likes") : $totalLikes.' '. gettext("like");?> for <?= trim($comment['firstname']." ".$comment['lastname']); ?> comment" class="btn-no-style fa fa-thumbs-up fa-solid" style="min-width:0px; padding:0px !important;"
         <?php if(!$disableAddEditComment){ ?> 
            onclick="likeDislikeCommentCommon('<?= $_COMPANY->encodeId($groupid); ?>','<?= $encCommentid; ?>',2,'<?= $totalLikes?>')" onKeyPress="likeDislikeCommentCommon('<?= $_COMPANY->encodeId($groupid); ?>','<?= $encCommentid; ?>',2,'<?= $totalLikes?>')"
        <?php } ?>
        ></button>
    <?php } else { ?>
        <button role="button" aria-label="<?= $totalLikes>1 ? $totalLikes.' ' . gettext("likes") : $totalLikes.' '. gettext("like");?> for <?= trim($comment['firstname']." ".$comment['lastname']); ?> comment" class="btn-no-style fa fa-thumbs-up fa-regular newgrey" style="min-width:0px; padding:0px !important;"
        <?php if(!$disableAddEditComment){ ?> 
            onclick="likeDislikeCommentCommon('<?= $_COMPANY->encodeId($groupid); ?>','<?= $encCommentid; ?>',1,'<?= $totalLikes?>')" onKeyPress="likeDislikeCommentCommon('<?= $_COMPANY->encodeId($groupid); ?>','<?= $encCommentid; ?>',1,'<?= $totalLikes?>')"
        <?php } ?>
        ></button>
    <?php } ?>
        &nbsp;
    </span>
    <span class="comment-like-icon"><?= $totalLikes>1 ? $totalLikes.' ' . gettext("likes") : $totalLikes.' '. gettext("like");?></span>
</span>
