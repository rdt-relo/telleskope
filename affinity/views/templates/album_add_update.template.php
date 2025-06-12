<style>
    #bulk_album_media_table{
        width:100%;
    }
    input.media-title {
    width: 94%;
}

.active-parent-div{
    border:2px solid #000;
}
</style>
<div tabindex="-1" id="albumModal" class="modal fade" aria-label="<?= $modalTitle;?>" aria-modal="true" role="dialog">
  <div class="modal-dialog" style="max-width: 600px!important;">
    <!-- Modal content-->
    <div class="modal-content">
        <div class="modal-header">
        <h2 id="modal-title" class="modal-title" aria-label="<?= $modalTitle;?>"><?= $modalTitle;?></h2>
        <button tabindex="0" id="btn_close" aria-label="close" type="button" class="close upload_modal_close" data-dismiss="modal" id="close_btn_one">&times;</button>
        </div>
        <div class="modal-body">
            <form id="albumForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                <input type="hidden" name="album_id" value="<?=$_COMPANY->encodeId($album_id);?>">
                <input type="hidden" name="resource_type" id="resource_type"  value="<?php /* $resource_type; */ ?>">               
                <div class="file-drag-drop-area form-group">
                    <span id="fake_drag_drop_container">
                        <span role="button" class="file-drag-drop-area-fake-btn"><?= gettext("Choose files")?></span>
                        <span class="file-drag-drop-msg"><?= gettext("or drag and drop files here"); ?></span>
                    </span>
                    <input aria-describedby="fake_drag_drop_container fileFormat" type="file" class="file-drag-drop-input" id="bulkalbumupload" name="bulkalbumupload" accept=".png,.jpeg,.jpg,.gif,.avi,.mov,.mpeg,.mp4,.wmv" onchange="bulkAlbumUpload(this)" aria-required="true" multiple>
                </div>
                <p id="fileFormat" style="color:#EE0000;font-size: 12px;"><?= gettext('Acceptable file formats: .png, .jpeg, .jpg, .gif, .avi, .mov, .mpeg, .mp4, .wmv'); ?></p>
                <?php if($_COMPANY->getAppCustomization()['albums']['upload_media_disclaimer']['enabled']){ ?>
                    <p style="color:#000;font-size: 12px;"><?= $_COMPANY->getAppCustomization()['albums']['upload_media_disclaimer']['disclaimer']; ?></p>
                <?php } ?>
            </form>
        </div>
        
        <div class="modal-footer">

            <button tabindex="0" type="button" id="album_upload_submit" onclick="bulkAlbumUploadSubmit('<?= $_COMPANY->encodeId($album_id); ?>','<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>');" onkeypress="bulkAlbumUploadSubmit('<?= $_COMPANY->encodeId($album_id); ?>','<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>');" class="btn btn-affinity hidden"><?= gettext('Submit'); ?></button>
            <button tabindex="0" type="button" id="close_button2" class="btn btn-secondary upload_modal_close" data-dismiss="modal"><?= gettext('Close'); ?></button>
        </div>
    </div>  
    </div>
</div>
<script>
    $( "#title" ).on('input', function() {
        if ($(this).val().length>32) {
            $(this).val($(this).val().substring(0,32));
            swal.fire({title: 'Error!',text:"<?= gettext('Maximum 32 characters allowed'); ?>"});
        }
    });
   
    $(".upload_modal_close").bind('click', function() {
        <?php if($is_gallery_view){?>
            albumMediaGalleryView('<?=$_COMPANY->encodeId($album_id);?>','<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>');
        <?php } else{ ?>
            getAlbums('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>');
        <?php } ?>
    });

    $(document).ready(function(){ // Handle drogdrom by Enter key press
        $(function(){
            $('.file-drag-drop-area').keyup(function(e) { 
                if (e.key == 'Enter') {
                    $('#bulkalbumupload').click();
                }   
            });
        });
    })

$('#albumModal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus');
    $("#mediaUploaded").attr("role","status");	
});

$('#albumModal').on('hide.bs.modal', function (e) {
  e.stopPropagation();
  $('body').css('padding-right','');
    setTimeout(() => {
        $('#album_<?=$_COMPANY->encodeId($album_id);?>').trigger('focus');
    }, 700);     
}); 

$(document).ready(function() {
    $("body").tooltip({ selector: '[data-toggle=tooltip]' });    

    let x = document.getElementById("bulkalbumupload");
    x.addEventListener("focus", inputFocusFunction, true);
    x.addEventListener("blur", inputBlurFunction, true);
});

function inputFocusFunction() {
    $(".file-drag-drop-area").addClass('active-parent-div');
}
function inputBlurFunction() {
    $(".file-drag-drop-area").removeClass('active-parent-div');
}
</script>
