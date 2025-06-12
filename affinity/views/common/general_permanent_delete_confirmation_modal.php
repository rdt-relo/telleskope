<div class="modal" id="confirmationModal" tabindex="-1">
    <div aria-label="<?= $modalTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title"><?= $modalTitle; ?></h2>
          <button type="button" id="btn_close" class="close" aria-hidden="true" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <p><?= sprintf(gettext("Note : By typing %s below"),"'permanently delete'") ?>, <?=$whatWillBeDeleted?></p>
                        <hr>
                        <label><strong><?= sprintf(gettext("Type %s below to confirm"),"'permanently delete'")?></strong></label>
                        <input type="text" class="form-control" onkeyup="checkIfConfirmComplete()" name="delete_permanently" id="delete_permanently" value="" placeholder="permanently delete">
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" id="confirm_and_delete_btn" onclick="<?= $functionName; ?>;" disabled ><?= gettext("Confirm and delete")?></button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= gettext("Close")?></button>
        </div>
      </div>
    </div>
</div>
<script>
function checkIfConfirmComplete() {
    let input = $("#delete_permanently").val().trim();
    console.log(input);
    if(input == 'permanently delete'){
      $("#confirm_and_delete_btn").prop('disabled',false);
    }
  }

$('#confirmationModal').on('shown.bs.modal', function () {    
    setTimeout(function(){
      $('.close').trigger('focus');
    },100);    
});

$('#confirmationModal').on('hidden.bs.modal', function () {    
    if ($('.modal').is(':visible')){
        $('body').addClass('modal-open');
    }      
    setTimeout(function(){
      $('.close').trigger('focus');
    },100);  
})
</script>
