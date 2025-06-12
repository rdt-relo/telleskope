<style>
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
    color: #292525 !important;
}
label.checkbox-inline {
    color: #000000cc !important;
}
span.select2.select2-container {
    width: 100% !important;
	color: #000000cc !important;
}
.select2-container--focus {
    border-color: #80bdff;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

</style>
<div id="general_email_review_modal" class="modal fade">
    <div aria-label="<?= sprintf(gettext("Email %s for review"),$template_review_what);?>" class="modal-dialog" aria-modal="true" role="dialog">
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title" id="review_publish_title"><?= sprintf(gettext("Email %s for review"),$template_review_what);?></h2>
          <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body" >
            <div class="col-md-12">
                <form  class="" id="general_email_review_form">
                    <input type="hidden" name="groupid" value="<?= $enc_groupid  ?>">
                    <input type="hidden" name="objectid" value="<?= $enc_objectid ;?>">
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <p class="alert-info" style="padding:5px;font-size: smaller;"><?= sprintf(gettext("Review email will be sent to you at <strong>%s</strong>."),$_USER->val('email'));?><br><?= gettext("You can also add additional reviewers below.");?></p>
                    <div class="form-group form-group-scroll">
                        <label for="reviewers"><?= gettext("Choose reviewers");?></label>
                        <select class="form-control" id="reviewers" multiple name="reviewers[]" >
                            <?php foreach ($reviewers as $reviewer){ ?>
                                <option value="<?= $reviewer['email']; ?>" ><?= $reviewer['firstname'].' '.$reviewer['lastname']; ?> </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="emails"><?= gettext("Other Reviewers (by email)");?></label>
                        <textarea type="text" class="form-control" rows="2" id="emails" name="emails" placeholder="<?= gettext("Comma separated email addresses");?>"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes"><?= gettext("Note to reviewers");?></label>
                        <textarea class="form-control" id="notes" placeholder="<?= gettext("Add a note for reviewers");?>" rows="4" name="review_note"></textarea>
                    </div>
            
                    
                </form>
            </div>
            <div class="clearfix"></div>
        </div>
        <div class="modal-footer text-center">
          <button id="submitPostReviewBtn" type="submit" class="btn btn-affinity"  onclick='<?=$template_email_review_function?>("<?= $enc_groupid; ?>");' ><?= gettext("Submit");?></button>
        </div>
      </div>

    </div>
  </div>

<script>
    $(document).ready(function() {
        $('#reviewers').select2({
            placeholder: "<?= gettext("Search and Select reviewer");?>",
        });       
    });

$('#general_email_review_modal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus');
    $(".select2-search__field").attr('aria-label','<?= gettext("Choose reviewers");?>');    
});

$('#general_email_review_modal').on('hidden.bs.modal', function (e) {  
    $('#<?= $enc_objectid ;?>').trigger('focus');        
})
</script>
