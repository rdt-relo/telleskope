<div id="unpublishNewsletter" class="modal fade" >
    <div aria-label="<?= gettext("Confirmation"); ?>" class="modal-dialog" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header text-center">
                <h4 id="modal-title" class="modal-title"><?= gettext("Confirmation"); ?></h4>
            </div>
            <div class="modal-body">
                <p><?= sprintf(gettext('After unpublishing this newsletter it will no longer be visible to users of this website but authorized group leads can still edit it in the draft folder. If the newsletter was sent by email, users will still be able to see it in their email inbox. By typing "<b>%s</b>" below, I am providing my consent to unpublish this newsletter'), 'I agree');?></p>
                <br>
                <div class="form-group">
                    <label><?= gettext("Confirmation"); ?>:</label>
                    <input type="text" class="form-control" id="confirmChangeNewsletter" onkeyup="unpublishNewsletterConfirm()" placeholder="" name="confirmChangeNewsletter">
                  </div>
            </div>
            <div class="modal-footer text-center">
                <span id="action_button_newsletter"><button class="btn btn-outline-danger" disabled ><?= gettext("Submit"); ?></button></span>
                <button type="button" class="btn btn-info" data-dismiss="modal"><?= gettext("Cancel"); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    function unpublishNewsletterConfirm(){      
        var v = $("#confirmChangeNewsletter").val();       
        if (v =='I agree'){
            $("#action_button_newsletter").html('<button class="btn btn-danger" onclick="unpublishNewsletter(\'<?= $enc_groupid;?>\',\'<?= $enc_objectid;?>\');" ><?= gettext("Submit"); ?></button>');
        } else {
            $("#action_button_newsletter").html('<button class="btn btn-outline-danger no-drop" disabled ><?= gettext("Submit"); ?></button>');
        }
    }

$('#unpublishNewsletter').on('shown.bs.modal', function () {
   $('#confirmChangeNewsletter').trigger('focus')
});

$('#unpublishNewsletter').on('hidden.bs.modal', function (e) {
    $('#<?=$_COMPANY->encodeId($newsletterid);?>').trigger('focus');
})
</script>