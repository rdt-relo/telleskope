<?php
    $encCommentid = $_COMPANY->encodeId($commentid);
    $sectionHeading = $sectionHeading ?? gettext('Comments');
        if($_COMPANY->getAppCustomization()['comment']['custom_terms_of_use']['enabled'] && (!empty($_COMPANY->getAppCustomization()['comment']['custom_terms_of_use']['title']) )){
        $termsTitle = $_COMPANY->getAppCustomization()['comment']['custom_terms_of_use']['title'];
        $termsContent = $_COMPANY->getAppCustomization()['comment']['custom_terms_of_use']['html'] ?? '';
        $contentPopover = '';
        if($termsContent){
            $termsContent = "<div class='teleskope-tooltip'>{$termsContent}</div>";
            $contentPopover =
                '<span tabindex="0"  
                    style="cursor: pointer;"
                    role="button"  
                    title=""
                    data-html="true"  
                    data-trigger="focus"
                    data-toggle="popover"
                    data-content="' . $termsContent . '"
                    data-container="body"
                    aria-label="'.$termsTitle.'"
                >
                    <i class="fa fa-info-circle" style="text-decoration:none;" aria-hidden="true"></i>
                </span>';
        }

        $noPIIString = $termsTitle. $contentPopover;

    } else {
        $noPIIString = gettext("Please do not key in any personal data (PII/SPI) belonging to you or any other person(s) which is not related to the business purpose.");
    }
    
?>
<div id="comment_text_aria_<?= $encCommentid; ?>" class="comment-text-area mt-3 mb-1"style="font-size: small; color:#EE0000; line-height: normal;"><?=$noPIIString?></div>
<div class="row row-no-gutters">
    <div class="col-12 comment-input-block p-0">
        <button class="btn btn-link" type="button" disabled id="uploading_loader<?= $encCommentid; ?>" style="display: none;">
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            <?= gettext("Uploading file please wait");?>...
        </button>
        <form class="comment-form" id="commentform<?= $encCommentid; ?>">
            <input type="hidden" name="commentid" value="<?= $encCommentid; ?>">
            <div class="input-group col-12">
                <div class="input-group-prepend input-comment-box-container">
                  <span id="input-group-text<?= $encCommentid; ?>" style="height: 50px !important;">
                      <?= User::BuildProfilePictureImgTag($_USER->val('firstname'), $_USER->val('lastname'), $_USER->val('picture'),'user-img',sprintf(gettext('%s Profile Picture'),$_USER->val('firstname')), $_USER->id(), null);?>
                </span>
               
                <textarea aria-describedby="comment_text_aria_<?= $encCommentid; ?> character_left_div<?= $encCommentid; ?>" type="text" name='message' class="form-control expand pt-2 comment-input-box"  id="commentarea<?= $encCommentid; ?>" placeholder=" <?= sprintf(gettext("Enter your %s here"), strtolower($sectionHeading));?>..." style="height: 50px !important;"></textarea>
                 </div>
            </div>
            <div aria-atomic="true" aria-live="polite" class="text-right newblackgrey pr-3" style="clear:both;" id="character_left_div<?= $encCommentid; ?>" style="display:none;">
                <small><?= gettext("Characters left:");?> <span id="characters_left<?= $encCommentid; ?>">1000</span></small>
            </div>

            <div class="form-group inputDnD col-md-12" id='att-div<?= $encCommentid; ?>' style="display: none;"></div>
            <div class="text-center" style="clear:both;">
            <?php if($mediaUploadAllowed){?>
                <button type="button" id='attachmentTrigger<?= $encCommentid; ?>' class="btn btn-affinity mr-2 prevent-multi-clicks <?= $commentid ? '' : 'hidden'; ?> " onclick="showCommentAttachmentInput('<?= $encCommentid; ?>');" title="<?= gettext("Upload a file");?>" disabled><span data-attachmentbtn='1'><?= gettext("Attach File");?></span><span style="display:none;" data-attachmentbtn='2'><?= gettext("Remove attachment");?></span></button>
            <?php } ?>
                <button type="button" id='submitpost<?= $encCommentid; ?>' onclick="initSubmitComment('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId($topicid)?>','<?= $encCommentid?>','<?= $submitCommentMethod; ?>')" class="comment-input-box-submit btn btn-affinity prevent-multi-clicks <?= $commentid ? '' : 'hidden'; ?>  mr-2" disabled><?= gettext("Submit");?></button>
                <?php if($commentid>0){ ?>
                    <button type="button" id='cancel<?= $encCommentid; ?>' onclick="hideCommentBox('<?= $encCommentid?>')" class="btn btn-affinity"><?= gettext("Cancel");?></button>
                <?php } ?>
            </div>
        </form>
    </div>
</div>

<script>
    $("#att-div<?= $encCommentid; ?>").html(initCommentFileDragDrop('<?= $encCommentid; ?>'));
    $('textarea#commentarea<?= $encCommentid; ?>').focus(function(){
        $("#commentarea<?= $encCommentid; ?>").animate({ height: "120px" }, 'fast');
        $("#input-group-text<?= $encCommentid; ?>").animate({ height: "120px" }, 'fast');
        $("#submitpost<?= $encCommentid; ?>").removeClass('hidden');
        $("#attachmentTrigger<?= $encCommentid; ?>").removeClass('hidden');
        if($("#commentarea<?= $encCommentid; ?>").val()=="" && $("#attachment<?= $encCommentid; ?>")[0].files.length == 0){
            $("#submitpost<?= $encCommentid; ?>").attr('disabled','disabled');
            $("#attachmentTrigger<?= $encCommentid; ?>").attr('disabled','disabled');
        }
        $("#character_left_div<?= $encCommentid; ?>").show();
    }).blur(function() {
        /* lookup the original width */
        if( $("#commentarea<?= $encCommentid; ?>").val()=="" && $("#attachment<?= $encCommentid; ?>")[0].files.length ==0 ){
            $("#commentarea<?= $encCommentid; ?>").animate({ height: "50px" }, 'fast');
            $("#input-group-text<?= $encCommentid; ?>").animate({ height: "50px" }, 'fast');
        <?php if($commentid){ ?>
            $("#submitpost<?= $encCommentid; ?>").attr('disabled','disabled');
            $("#attachmentTrigger<?= $encCommentid; ?>").attr('disabled','disabled');
        <?php } else { ?>
            $("#submitpost<?= $encCommentid; ?>").addClass('hidden');
            $("#attachmentTrigger<?= $encCommentid; ?>").addClass('hidden');
            $("#character_left_div<?= $encCommentid; ?>").hide();
            var attchbtn = $("#attachmentTrigger<?= $encCommentid; ?> > span");
            if (attchbtn.length){
                if (attchbtn[1].style.display === 'none'){
                    $("#att-div<?= $encCommentid; ?>").hide()
                } else if(attchbtn[0].style.display === 'none'){
                    $("#att-div<?= $encCommentid; ?>").hide();
                    $("#attachmentTrigger<?= $encCommentid; ?> > span").toggle(10,'swing');
                }
            }

        <?php } ?>
        }
    });
   
    $(document).ready(function() {
        $(document).off('focusin.modal');
            $("#commentarea<?= $encCommentid; ?>").on('keyup', function(e) {
            var characters =this.value.length;
            var remained = 1000-characters;

            if(characters>0){
                $("#submitpost<?= $encCommentid; ?>").removeAttr('disabled');
                $("#attachmentTrigger<?= $encCommentid; ?>").removeAttr('disabled');
            } else {
                $("#submitpost<?= $encCommentid; ?>").attr('disabled','disabled');
                $("#attachmentTrigger<?= $encCommentid; ?>").attr('disabled','disabled');
            }
            
            if(remained<1){
                remained = 0;
                $("#character_left_div<?= $encCommentid; ?>").css('color', 'red');
            } else {
                $("#character_left_div<?= $encCommentid; ?>").css('color', 'gray');
            }
            $('#characters_left<?= $encCommentid; ?>').text(remained);
            if (characters > 1000) {
                e.preventDefault();
                this.value = this.value.substring(0, 1000);
                swal.fire({title: 'Error',text:"<?= gettext('Only 1000 characters allowed')?>"});
                return;
            }
        });
    }); 
</script>
<script>
    $(function(){
        $('[data-toggle="popover"]').popover({
            sanitize:true
        });  
    });

    $(document).on('keydown','.comment-input-box',function(e)  {     
        if ( e.which === 9) {
            if(!($('.comment-block').is(":visible")) && $(".comment-input-box-submit").is(":disabled") && $('#album_close').is(':visible')){
				$("#album_viewer_area").focus();
			}          
        }
    })

    $(document).on('keydown','.comment-input-box-submit',function(e)  {     
        if ( e.which === 9) {
            if ($('#album_close').is(':visible')) {
                $("#album_viewer_area").focus(); 
            }
        }
    })
</script>
