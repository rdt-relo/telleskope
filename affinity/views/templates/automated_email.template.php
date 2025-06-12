<div tabindex= "-1" id="previewTemplate" class="modal fade">
    <div aria-label="<?= $template['emailsubject']; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
      <div class="modal-content " >
        <div class="modal-header">
          <h2 id="modal-title" class="modal-title" ><?= $template['emailsubject']; ?></h2>
            <button aria-label="Close dialog" type="button" class="close" data-dismiss="modal" id="close_btn_1">&times;</button>
        </div>
        <div class="modal-body" >
          <style>
            .main.container, .footer.container{
              padding:0 !important;
            }
            .divider {
              margin:0 !important;
            }            
          </style>
          <?= $template['communication']; ?>
        </div>
        <div class="modal-footer text-center">
          <button  id="closeButton" type="submit" class="btn btn-affinity"  data-dismiss="modal" ><?= gettext("Close");?></button>
        </div>
      </div>
  
    </div>
</div>
<script>
retainFocus("#previewTemplate");
$('#previewTemplate').on('shown.bs.modal', function () {
   $('#close_btn_1').trigger('focus')
});
</script>
