<div id="newsletterAttachmeentModal" class="modal fade">
    <div aria-label="<?=$newsletter->val('newslettername');?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="modal-title" class="modal-title"><?=$newsletter->val('newslettername');?> > <?= gettext("Attachments");?></h4>
                <button aria-label="<?= gettext("close");?>" type="button" id="btn_close" class="close" data-dismiss="modal">&times;</button>
                
            </div>
            <div class="modal-body">
				<div class="container" id="attachment_contents">
                <?php
                    include __DIR__.'/newsletter_attachment_data.template.php';
                ?>
                </div>
            </div>
            <div class="modal-footer text-center">
                <button type="button" class="btn btn-affinity" data-dismiss="modal"><?= gettext("Close");?></button>
              </div>
        </div>
    </div>
</div>

<script>
$('#newsletterAttachmeentModal').on('shown.bs.modal', function () {
   $('#btn_close').focus();
});

$('#newsletterAttachmeentModal').on('hidden.bs.modal', function (e) {
   $('#<?=$encNewsletterId?>').focus();
})
</script>