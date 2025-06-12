<div id="resourceIconModal" class="modal fade" tabindex="-1" >
  <div aria-label="<?= $modalTitle; ?>" class="modal-dialog" aria-modal="true" role="dialog">
    <!-- Modal content-->
    <div class="modal-content">
        <div class="modal-header">
        <h2 id="modal-title" class="modal-title"><?= $modalTitle;?></h2>
        <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="resourceForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                <input type="hidden" name="resource_id" value="<?=$_COMPANY->encodeId($resource_id);?>">
                <input type="hidden" name="resource_type" id="resource_type"  value="<?=$resource_type;?>">
               <?php if($resource_type ==3){ ?>
                <div class="form-group">
                <div class="file-drag-drop-area form-group" id='att-div'>
                    <span id="fake_drag_drop_container">
                    <span class="file-drag-drop-area-fake-btn"><?= gettext("Choose file")?></span>
                    <span class="file-drag-drop-msg"><?= gettext("or drag and drop file here"); ?></span>
                    </span>
                    <input aria-describedby="fake_drag_drop_container docNote" type="file" class="file-drag-drop-input" id="attachment" name="attachment" accept=".png,.jpeg,.jpg" onchange="readUrl(this,'',50)" aria-required="true">
                    <input type="hidden" id="resource_file" name="resource_file_data" >
                </div>
                <p id="docNote" style="color:red;font-size: 10px; margin-top:10px;"><?= gettext('Only .png, .jpeg, and .jpg files are accepted. Maximum allowed file size is 1MB. Ideally, images must be 1024 x 1024 pixels in height and width'); ?></p>
                </div>
            <?php } ?>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="submitFolderIcon('<?= $_COMPANY->encodeId($groupid); ?>',<?=$resource?'1':'0'?>,'<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>','<?= $_COMPANY->encodeId($is_resource_lead_content); ?>');" class="btn btn-affinity prevent-multi-clicks"><?= gettext('Submit'); ?></button>
            <button type="button" id="close_button" class="btn btn-secondary" data-dismiss="modal"><?= gettext('Close'); ?></button>
        </div>
    </div>  
    </div>
</div>
<script>
    $( "#title" ).on('input', function() {
        if ($(this).val().length>128) {
            $(this).val($(this).val().substring(0,128));
            swal.fire({title: 'Error!',text:"<?= gettext('Maximum 128 characters allowed'); ?>"});
        }
    });
    $(document).ready(function(){ // Handle drogdrom by Enter key press
        $(function(){
            $('.file-drag-drop-area').keyup(function(e) { 
                if (e.key == 'Enter') {
                    $('.file-drag-drop-input').click();
                }   
            });
        });
    })

$('#resourceIconModal').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus')
});

$('#resourceIconModal').on('hidden.bs.modal', function (e) {
  $('#rid_<?=$_COMPANY->encodeId($resource_id);?>').trigger('focus');
  
});

retainFocus('#resourceIconModal');
</script>