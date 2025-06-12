<div class="row row-no-gutters">
<?php

if(!empty($comments)>0){
	$placeholder = GROUP::GROUP_RESOURCE_PLACEHOLDERS;
    foreach($comments as $c) {
        $myLikeStatus = Comment::GetUserLikeStatus($c['commentid']);
        $totalLikes = Comment::GetLikeTotals($c['commentid']);

        # Display comment needs $comment variable, so set it
        $comment = $c;
        include __DIR__ . '/display_comment.php';
        ?>
        <div class="col-12 comment-block" id="comment_common_container_<?= $_COMPANY->encodeId($c['commentid']);?>">
            <?php
                include(__DIR__ . "/likes_sub_comments_container.template.php");
            
                unset($comment);
                # Unset $comment variable as we will be calling display comment again

                $subComments =  array_key_exists('anonymized',$c) ? Comment::GetCommentsAnonymized_2($c['commentid']) : Comment::GetComments_2($c['commentid']);
                if (!empty($subComments)) {
                    foreach ($subComments as $sc) {
                        # Display comment needs $comment variable, so set it
                        $comment = $sc;
                        include __DIR__ . '/display_comment.php';
                        unset($comment);
                        # Unset $comment variable for sake of keeping state clean
                    }
                }

            ?>
        </div>
    <?php                
    }
} else { ?>
    <div class="col-md-12 text-center">
        <p class="pb-5"><?= sprintf(gettext("No %s"), $sectionHeading);?>!</p>
    </div>
<?php } ?>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        //initial for blank profile picture
        $('.initial').initial({
            charCount: 3,
            textColor: '#ffffff',
            color: window.tskp?.initial_bgcolor ?? null,
            seed: 0,
            height: 50,
            width: 50,
            fontSize: 20,
            fontWeight: 300,
            fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
            radius: 0
        });
    });
</script>
<script>
	$('textarea.update-comment-expand').focus(function(){
	$(this).animate({ height: "90px" }, 'slow');	
	
});
	function initUpdateComment(i){
		$("#comment"+i).hide();
		$("#updatecomment"+i).show();
        var preloadLength = 1000-($("#"+i).val().length);
        if (preloadLength < 0){
            preloadLength = 0;
            $("#"+i).val($("#"+i).val().substring(0, 1000)); // Show only 100 characters
            $("#u_character_left_div"+i).css('color', 'red');
        }
        $('#u_characters_left'+i).text(preloadLength);
	}
	function cancelUpdateComment(i){
		$("#comment"+i).show();
		$("#updatecomment"+i).hide();
		$("#"+i).val($("#comment"+i).html());
        if($('.three-dot').is(":visible")){
            $(".three-dot:first").focus();          
        } 
	}
	
</script>

