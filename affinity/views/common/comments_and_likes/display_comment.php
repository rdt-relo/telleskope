<style>
.popover, .popover-header{min-width:240px;}
.popover-body {
    text-align: center;
}
</style>
<?php
$encCommentid = $_COMPANY->encodeId($comment['commentid']);
$isSubComment = $comment['topictype'] === 'CMT';
?>
<div class="col-12 <?= $isSubComment ? 'sub-comment-block' : 'comment-block'?>" id="container<?=$encCommentid;?>">
    <div class="col-12 comment-profile-section p-0">
        <div class="col-sm-8 col-7 dark-gray ml-2 comment-profile-block">
            <?= User::BuildProfilePictureImgTag($comment['firstname'],$comment['lastname'],$comment['picture'],'user-img comment-profile-pic m-0',sprintf(gettext('%s Profile Picture'),$comment['firstname']), $comment['userid'], 'profile_basic')?>
            <span class="who-comment ml-3"><?= trim($comment['firstname']." ".$comment['lastname']); ?></span>
        </div>
        <div class="col-sm-4 col-5 dark-gray p-0 comment-action-block">
            <div id="delete-pop-identifier" class="dropdown pull-right mr-4">
            <span class="newdarkgrey"><?= $db->timeago($comment['createdon']); ?>&emsp;</span>
            <?php if (($_USER->id()== $comment['userid'] || $_USER->canManageGroup($groupid)) && !$disableAddEditComment) {
                $newClassBtn = "";
                if($isSubComment){
                    $newClassBtn = "sub-comment-btn";
                } ?>
                <button aria-expanded="false" aria-label="<?= trim($comment['firstname']." ".$comment['lastname']); ?> <?= gettext("comment");?> <?= $db->timeago($comment['createdon']); ?> <?= gettext("ago options");?>" tabindex="0" class="btn-no-style dropdown-toggle three-dot <?= $newClassBtn; ?>" data-toggle="dropdown">
                    <i role="button" class="fa fa-ellipsis-v col-doutd" aria-hidden="true"></i>
            </button>
                <ul class="dropdown-menu comment-dropdown-menu">
                <li><button class="btn-no-style dropdown-item pl-2" onclick="initUpdateComment('<?= $encCommentid; ?>')"><?= gettext("Edit");?></button></li>

                <li><button class="btn-no-style confirm dropdown-item pop-identifier-delete-msg pl-2" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>"  title="<?= gettext("Are you sure you want to delete");?>?" onclick="deleteCommentCommon('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($comment['topicid']); ?>','<?= $encCommentid; ?>','<?= $_COMPANY->encodeId($isSubComment ? 1 : 0); ?>','<?= $submitCommentMethod; ?>')" ><?= gettext("Delete");?></button></li>
                </ul>
            <?php  } ?>
            </div>
        </div>
    </div>
    <div class="col-12 comment-comment-section p-0">
        <div class="comment-container">
            <p id="comment<?= $encCommentid; ?>" class="post-comment break-long-text"><?= htmlspecialchars($comment['comment']); ?></p>
            <?php if($comment['attachment']){
                $jsonData = json_decode($comment['attachment'], true);
                $path_parts = pathinfo($jsonData['file_id']);
                $fileName  =  $jsonData['file_name'];
                $fileSize  =  $jsonData['file_size'];
                $ext = strtolower(substr($path_parts['extension'],0,4));
            ?>
            <a href="ajax_comments_likes.php?getCommentAttachment=<?= $encCommentid; ?>" class="js-download-link">
                <img src="<?= $placeholder[$ext]; ?>" alt="File extension icon image" height="20px">
                <span class="pl-2"><?= htmlspecialchars($fileName); ?><small class="gray m-0 p-0"> (<?= $fileSize; ?>)</small></span>
            </a>
            <?php } ?>
            <?php if (($_USER->id()== $comment['userid'] || $_USER->canManageGroup($groupid)) && !$disableAddEditComment) { ?>
                <div id="updatecomment<?= $encCommentid; ?>" style="display:none;">
                    <div class="col-md-12">
                        <textarea name='message' class="update-comment-input" style="min-width: 100%;" id="<?= $encCommentid; ?>" placeholder=" <?= gettext("Edit comment here");?>..." rows="6" ><?= htmlspecialchars($comment['comment']); ?></textarea>
                        <div aria-live="polite" class="text-right gray" id="u_character_left_div<?= $encCommentid; ?>"><small><?= gettext("Characters left:");?> <span id="u_characters_left<?= $encCommentid; ?>">1000</span></small></div>
                    </div>
                    <div class="text-center">
                        <button type="button" id="updateComment<?= $encCommentid; ?>" onclick="updateCommentCommon('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($comment['topicid']); ?>','<?= $encCommentid; ?>')" class="btn btn-affinity mr-2"><?= gettext("Update");?></button>
                        <button type="button"  onclick="cancelUpdateComment('<?= $encCommentid; ?>')" class="btn btn-affinity"><?= gettext("Cancel");?></button>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $("#<?= $encCommentid; ?>").on('keyup', function(e) {
            var characters =this.value.length;
            var remained = 1000-characters;

            if(characters>0){
                $("#updateComment<?= $encCommentid; ?>").removeAttr('disabled');
            } else {
                $("#updateComment<?= $encCommentid; ?>").attr('disabled','disabled');
            }

            if(remained<1){
                remained = 0;
                $("#u_character_left_div<?= $encCommentid; ?>").css('color', 'red');
            } else {
                $("#u_character_left_div<?= $encCommentid; ?>").css('color', 'gray');
            }
            $('#u_characters_left<?= $encCommentid; ?>').text(remained);

            if (characters > 1000) {
                e.preventDefault();
                this.value = this.value.substring(0, 1000);
                swal.fire({title: 'Error',text:"<?= gettext('Only 1000 characters allowed')?>"});
                return;
            }
        });
    });

	$('.pop-identifier-delete-msg').each(function() {
		$(this).popConfirm({
		container: $("#teamMessages"),
		});
	});
    
    //stop focus within carousel image view.
    $(".three-dot").last().addClass('last-sub-comment');   
    $(".last-sub-comment").on( "keydown", function( event ) {        
        if ( event.which === 9) {
            if($('#album_close').is(":visible")){
                $('#album_viewer_area').focus();          
            }            
        }
    });
    
    $(document).on('keydown','.confirm-dialog-btn-abort',function(e)  {     
    if (e.keyCode == 13) {
        if($('#album_close').is(":visible")){               
                setTimeout(() => {                    
                    $('.popover').popover('hide');
                    $('.comment-dropdown-menu').removeClass('show');
                    $('#album_viewer_area').focus(); 
	            }, 500);                
            }        
        }
    })

    
</script>