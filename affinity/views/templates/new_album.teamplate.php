<!-- New Album Modal -->
<div tabindex="-1" id="new_album_modal" class="modal"  aria-label="<?= $modalTitle; ?>" role="dialog" aria-modal="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title" class="modal-title"><?= $modalTitle; ?></h2>
                <button aria-label="close" id="close_button_1" type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="albumForm" enctype="multipart/form-data">
                    <input type="hidden" id="albumid"  name="albumid" value="<?= $_COMPANY->encodeId($albumid) ?>">
                    <p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
                    <div class="form-group">
                        <label><?= gettext("Album Title")?><span style="color: #ff0000;">*</span></label>
                        <input aria-required="true" class="form-control" id="album_title" type="text" placeholder="<?= gettext("Album Title")?>..." name="album_title" value="<?= $album ? $album->val('title') : ''; ?>" >
                    </div>

                    <?php include_once __DIR__.'/../common/init_chapter_channel_selection_box.php'; ?>
                    <div class="form-group">
                        <label id="can_uploade_media" class="control-lable"><?= gettext('Who can upload media')?></label>
                        <div>
                            <select aria-labelledby="can_uploade_media" tabindex="0" required class="form-control" id="whocanuploadmedia" name="whocanuploadmedia">
                                <option value="leads" <?= $album ? ($album->val('who_can_upload_media') == "leads" ? ' selected' : '') : ''; ?>> <?= gettext('Leaders (default)')?>   </option>                    
                                <option value="leads_and_members" <?= $album ? ($album->val('who_can_upload_media') == "leads_and_members" ? ' selected' : '') : ''; ?>><?= gettext('Leaders and Members')?> </option>  
                            </select>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer text-center">
                <button type="button" onclick="createUpdateAlbum('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>');" class="btn btn-affinity prevent-multi-clicks"><?= gettext("Submit")?></button>
                <button type="button" id="close_button" class="btn btn-secondary" data-dismiss="modal"><?= gettext("Close")?></button>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__.'/../common/init_chapter_channel_selection_box.php'; ?>

<script>
$(document).ready(function () {
    $('.multiselect').attr('tabindex', '0');
    $('.multiselect').attr('aria-expanded', 'false');
});	

$('#new_album_modal').on('shown.bs.modal', function () {
   $('#close_button_1').trigger('focus');
});

$('#new_album_modal').on('hidden.bs.modal', function (e) { 
    $('#<?=$_COMPANY->encodeId($albumid);?>').trigger('focus');
});
</script>