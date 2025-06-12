<style>
    .profile-card-4 .card-img-block {
        float: left;
        width: 100%;
        height: 150px;
        overflow: hidden
    }

    .profile-card-4 .card-body {
        position: relative;
        min-height: 200px
    }

    .profile-card-4 .profile {
        border-radius: 50%;
        position: absolute;
        top: -62px;
        left: 50%;
        width: 100px;
        height: 100px;
        border: 3px solid #fff;
        margin-left: -50px
    }

    .profile-card-4 .card-img-block {
        position: relative
    }

    .profile-card-4 .card-img-block > .info-box {
        position: absolute;
        width: 100%;
        height: 100%;
        color: #fff;
        padding: 20px;
        text-align: center;
        font-size: 14px;
        -webkit-transition: 1s;
        transition: 1s;
        opacity: 0
    }

    .profile-card-4 .card-img-block:hover > .info-box {
        opacity: 1;
        -webkit-transition: 1s;
        transition: 1s
    }

    .profile-card-4 h5 {
        font-weight: 600
    }

    .profile-card-4 .card-text {
        font-weight: 300;
        font-size: 15px
    }

    .profile-card-4 .icon-block {
        float: left;
        width: 100%
    }

    .profile-card-4 .icon-block a {
        text-decoration: underline !important
    }

    .card-block {
        width: 100% !important;
        padding-top: 0 !important;
        box-shadow: 0 0 0 0 rgb(0 0 0 / 0%) !important
    }

    .modal.fade .modal-dialog.modal-dialog-zoom {
        -webkit-transform: translate(0, 0) scale(.5);
        transform: translate(0, 0) scale(.5)
    }

    .modal.show .modal-dialog.modal-dialog-zoom {
        -webkit-transform: translate(0, 0) scale(1);
        transform: translate(0, 0) scale(1)
    }

    .points_section {
        list-style: none
    }

    .profile-card-4 .icon-block .points_section a {
        text-decoration: none !important
    }

    .prf {
        font-size: 1.5rem;
        font-weight: 400
    }

    .rectxt {
        background-color: #f6f6f6;
        border-radius: 5px;
        margin: 15px 0;
    }

    .rectxt img {
        max-width: 100%
    }
</style>
<?php

$current_custom_fields = $recognition->val('attributes') ? json_decode($recognition->val('attributes'),true) : array();
$recognizedUser = User::GetUser($recognition->val('recognizedto')) ?? User::GetEmptyUser();
$recognizedBy = User::GetUser($recognition->val('recognizedby')) ?? User::GetEmptyUser();

?>

<div class="modal show" id="viewRecognitionDetialModal" aria-modal="true" style="padding-right: 15px; display: block;">
    <div class="modal-dialog modal-dialog-centered modal-dialog-zoom  modal-lg" aria-modal="true" role="dialog">
      <div class="modal-content">
        <div class="modal-body">
            <div class="card card-block profile-card-4 ">
                <div>
                    <img class="card-img-block" src="img/img.png" alt="">
                </div>

                <div class="card-body pt-5">
                    <div class="prf">
                     <?php  
                      $alt_tag = $recognizedUser->getFullName().' Profile Picture';
                      echo $profilepic = User::BuildProfilePictureImgTag($recognizedUser ? $recognizedUser->val('firstname') : 'Deleted',$recognizedUser ? $recognizedUser->val('lastname') : 'User', $recognizedUser ? $recognizedUser->val('picture') : '', 'profile',$alt_tag, $recognizedUser->id(), 'profile_basic');
                      ?>
                      <?= ($recognizedUser ? $recognizedUser->val('firstname') : 'Deleted').' '.($recognizedUser ? $recognizedUser->val('lastname') : 'User') ;?>
                    </div>
                    <div class="icon-block text-center">
                        <p class="card-textr" style=" font-size:small;"><?= gettext("Recognized By:");?> <?php if($recognition->val('recognizedby') == 0){  echo $recognition->val('recognizedby_name'); }elseif($recognition->val('recognizedto') == $recognition->val('recognizedby')){ echo gettext("Self");  }else{ ?>
                                    <?= ($recognizedBy ? $recognizedBy->val('firstname') : 'Deleted').' '.($recognizedBy ? $recognizedBy->val('lastname') : 'User') ;?>
                                    <?php } ?></p>
                    </div>

                    <div class="col-12 text-left py-3 rectxt">
                           <?= $recognition->val('description');?>
                    </div>

                <div class="col-12 text-left">
                    <?= $recognition->renderCustomFieldsComponent('v9') ?>
            
             <?php
             $myLikeStatus = 0;
             $latestLikers = 0;
             $totalLikers = 0;
             $showAllLikers = "";
             $likeUnlikeMethod = "";
             $topicid = $recognition->val('recognitionid');

              if ($_COMPANY->getAppCustomization()['recognition']['likes']) { 
                    $myLikeType = Recognition::GetUserReactionType($topicid);
                    $myLikeStatus = (int) !empty($myLikeType);
                    $latestLikers = Recognition::GetLatestLikers($topicid);
                    $totalLikers = Recognition::GetLikeTotals($topicid);
                    $likeTotalsByType = Recognition::GetLikeTotalsByType($topicid);
                    $showAllLikers = true;
                    $likeUnlikeMethod = 'RecognitionTopic';

                } 

         
             if ($_COMPANY->getAppCustomization()['recognition']['comments']) {
                $comments = Recognition::GetComments_2($topicid);            
                $commentid = 0;
                $disableAddEditComment = false; 
                $submitCommentMethod = "RecognitionComment";
                $mediaUploadAllowed = $_COMPANY->getAppCustomization()['recognition']['enable_media_upload_on_comment'];

              }

                 $groupid = $recognition->val('groupid'); 
          
               ?>
             
                    <?php if ($_COMPANY->getAppCustomization()['recognition']['likes']) { ?>
                        <!-- Like Widget Start -->
                        <div class="col-md-12 mt-1 p-0" id="likeUnlikeWidget">
                            <?php  include (__DIR__.'/../common/comments_and_likes/topic_like_and_likers_bedge.template.php'); ?>
                        </div>
                    <?php } ?>
                        <!-- Like Widget End -->
                    <?php if ($_COMPANY->getAppCustomization()['recognition']['comments']) { ?>
                        <!-- Start of comments Widget -->
                        <?php    include(__DIR__ . "/../common/comments_and_likes/comments_container.template.php"); ?>
                    <?php } ?>
                    </div> 
                        
        <div class="text-center m-2 mb-4">
          <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="closeProfileDetailedView()">Close</button>
        </div>
      </div>
    </div>
  </div>
<script>
    function closeProfileDetailedView(){
	if (document.body.contains(__previousActiveElementProfile)) {
		__previousActiveElementProfile.focus();
	}
}

$('#viewRecognitionDetialModal').on('shown.bs.modal', function () {   
    $('#likeUnlikeTopicCommon').trigger('focus');    
});

$('#viewRecognitionDetialModal').on('hidden.bs.modal', function () {      
    $("div").removeClass("modal-backdrop show");
});
</script>