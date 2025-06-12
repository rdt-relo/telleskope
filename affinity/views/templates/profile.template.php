<style>

*:hover{
    -webkit-transition: all 1s ease;
    transition: all 1s ease;
}

/*Profile card 4*/
.profile-card-4 .card-img-block {
    float: left;
    width: 100%;
    height: 150px;
    overflow: hidden;
}
.profile-card-4 .card-body {
    position: relative;
    min-height: 200px;
}
.profile-card-4 .profile {
    border-radius: 50%;
    position: absolute;
    top: -62px;
    left: 50%;
    width: 100px;
    height: 100px;
    border: 3px solid rgba(255, 255, 255, 1);
    margin-left: -50px;
}
.profile-card-4 .card-img-block {
    position: relative;
}
.profile-card-4 .card-img-block > .info-box {
    position: absolute;
    width: 100%;
    height: 100%;
    color: #fff;
    padding: 20px;
    text-align: center;
    font-size: 14px;
    -webkit-transition: 1s ease;
    transition: 1s ease;
    opacity: 0;
}
.profile-card-4 .card-img-block:hover > .info-box {
    opacity: 1;
    -webkit-transition: all 1s ease;
    transition: all 1s ease;
}
.profile-card-4 h5 {
    font-weight: 600;
}
.profile-card-4 .card-text {
    font-weight: 300;
    font-size: 15px;
}
.profile-card-4 .icon-block {
    float: left;
    width: 100%;
}
.profile-card-4 .icon-block a {
    text-decoration: underline !important;
}
.card-block {
    width: 100% !important;
    padding-top:0px !important;
    box-shadow: 0px 0px 0px 0px rgb(0 0 0 / 0%) !important;
}

.modal.fade .modal-dialog.modal-dialog-zoom {-webkit-transform: translate(0,0)scale(.5);transform: translate(0,0)scale(.5);}
.modal.show .modal-dialog.modal-dialog-zoom {-webkit-transform: translate(0,0)scale(1);transform: translate(0,0)scale(1);}

.points_section{
    list-style: none;
}
.profile-card-4 .icon-block .points_section a{
text-decoration:none !important;
}
</style>

<div class="modal" id="profile_detailed_view">
    <div aria-label="<?= $name; ?>" class="modal-dialog modal-dialog-centered modal-dialog-zoom" aria-modal="true" role="dialog">
      <div class="modal-content">
        <div class="modal-body">
            <div class="card card-block profile-card-4 ">
                <div>
                    <img class="card-img-block" src="<?= /*$banner ? $banner : */'img/img.png'?>" alt="">
                </div>

                <div class="card-body pt-5">
                    <?= $profilepic; ?>
                    
                    <h2 class="card-title text-center"><?= $name; ?></h2>
                    
                    <div class="icon-block text-center">
                        <p class="card-textr" style=" font-size:small;"><?= ucwords($requested_user->val('jobtitle')); ?></p>
                        <p class="card-textr" style=" font-size:small;"><a id="userEmail" href="mailto:<?= $requested_user->getEmailForDisplay()?>"><?= $requested_user->getEmailForDisplay(true)?></a></p>
                        <p class="card-textr" style=" font-size:small;"><?= $requested_user->getDepartmentName()=='Unknown' ? '' : $requested_user->getDepartmentName() ?></p>
                        <p class="card-textr" style=" font-size:small;"><?= $requested_user->getBranchName() ?></p>

                        <?php if ($manager_user = ($requested_user->getUserHeirarcyManager())) { ?>
                          <p class="card-textr mt-2" style=" font-size:small;">
                            <?= gettext('Manager:') ?>
                            &nbsp;
                            <?= User::BuildProfilePictureImgTag($manager_user->val('firstname'), $manager_user->val('lastname'), $manager_user->val('picture'), 'memberpic2 skip-custom-style', 'Manager Profile Picture', $manager_user->id(), $profile_info_depth) ?>
                            <?= $manager_user->getFullName() ?>
                          </p>
                        <?php } ?>

                        <?php if ($_COMPANY->getAppCustomization()['profile']['enable_bio'] && ($profile_info_depth === 'profile_full')) { ?>
                        <p class="card-textr" style=" font-size:small;"></p>
                        <p class="card-textr" style=" font-size:small;"><small id="post-inner"><?= $requested_user->getBio() ?? ''; ?></small></p>
                        <?php } ?>

                    </div>
                    
                </div>
            </div>
        </div>
        <div class="text-center m-2 mb-4">
          <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="closeProfileDetailedView()"><?= gettext("Close");?></button>
        </div>
      </div>
    </div>
  </div>

<script>
trapFocusInModal("#profile_detailed_view");

$('#profile_detailed_view').on('shown.bs.modal', function () {
    $('#userEmail').trigger('focus');
});

$('#profile_detailed_view').on('hidden.bs.modal', function (e) { 
    $('.modal').removeClass('js-skip-esc-key');   
}) 

retainFocus("#profile_detailed_view");

</script>
