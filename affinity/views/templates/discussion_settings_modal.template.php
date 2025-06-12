<div id="manageDiscussionSettings" class="modal fade">
    <div aria-label="<?= gettext("Discussion Settings");?>" class="modal-dialog" aria-modal="true" role="dialog">
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title" id="review_publish_title"><?= gettext("Discussion Settings");?></h2>
          <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body" >
            <div class="col-md-12">
                <form  class="" id="general_email_review_form" method="POST">
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <input type="hidden" name="groupid" value="<?= $_COMPANY->encodeId($groupid); ?>">
                        <div class="form-group form-group-scroll">
                        <label for="who_can_post"><?= gettext("Who can post");?></label>
                          <select id="who_can_post" class="form-control" name="who_can_post" required="">
                          <option value="leads" <?= $discussionSettings['who_can_post'] == "leads"  ? 'selected' : ''; ?>>Leaders</option>
                          <option value="members" <?= $discussionSettings['who_can_post'] == "members"  ? 'selected' : ''; ?>>Leaders and Members</option>
                          </select>
                    </div>
                    <?php if($_COMPANY->getAppCustomization()['discussions']['anonymous_post_setting']){ ?>
                        <div class="form-group">
                              <div class="radio" style="">
                                  <label class="checkbox-inline">
                                    <input <?php if ($discussionSettings['allow_anonymous_post'] == 1) echo "checked='checked'"; ?> type="checkbox" id="allow_anonymous_post" name="allow_anonymous_post">&nbsp; <?= gettext("Allow anonymous post");?> </label>
                              </div>
                        </div>
                    <?php } ?>

                      <div class="form-group">
                          <div class="radio" style="">
                              <label class="checkbox-inline">
                              <?php 
                                $disableEmailPublishing = false;
                                if($_COMPANY->getAppCustomization()['discussions']['disable_email_publish']){ 
                                  $disableEmailPublishing = true;
                                }
                              ?>
                                <input <?php if ($disableEmailPublishing) { echo "disabled"; } else { if  ($discussionSettings['allow_email_publish'] == 1){ echo "checked='checked'"; } } ?> type="checkbox" id="allow_email_publish" name="allow_email_publish">&nbsp; <?= gettext("Allow email publish");?><i tabindex="0" role='button' class="fa fa-question-circle" data-toggle="tooltip" title="<?=gettext('The email publishing feature is disabled. For more information, please contact your coordinator.')?>"></i></label>
                          </div>
                      </div>           
                    
                </form>
            </div>
            <div class="clearfix"></div>
        </div>
        <div class="modal-footer text-center">
          <button type="submit" class="btn btn-affinity prevent-multi-clicks"  onclick="updateDiscussionsConfiguration('<?= $_COMPANY->encodeId($groupid); ?>');"><?= gettext("Submit");?></button>
        </div>
      </div>

    </div>
  </div>
<script>
$('#manageDiscussionSettings').on('shown.bs.modal', function () {
  $('#btn_close').trigger('focus');
});

$(function () {
  $('[data-toggle="tooltip"]').tooltip();
})
</script>