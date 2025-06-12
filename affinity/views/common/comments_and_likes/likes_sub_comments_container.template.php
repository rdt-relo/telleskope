
<?php
$isLikesEnabled = true; // TODO - Refine customization based logic for get this status
$isSubCommentEnabled = true; // TODO - Refine customization based logic for get this status
?>
<div class="col-12 like-comment-block" id="like_comment_count<?= $encCommentid; ?>">
    <div class="col-12 newgrey like-comment-container">
    
    <?php if ($isLikesEnabled){ ?>
        <!-- Like component -->
        <?php include(__DIR__ . "/like_component.template.php"); ?>
    <?php } ?>
    <?php if ($isSubCommentEnabled){ ?>
        <!-- Sub comment component -->
        &emsp;
        <?php include(__DIR__ . "/sub_comment_component.template.php"); ?>
    <?php } ?>
    </div>
</div>

