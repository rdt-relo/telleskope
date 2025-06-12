<div id="resourceModal" class="modal fade" tabindex="-1" >
  <div aria-label="<?= $modalTitle; ?>" class="modal-dialog" aria-modal="true" role="dialog">
    <!-- Modal content-->
    <div class="modal-content">
        <div class="modal-header">
        <h2 id="modal-title" class="modal-title"><?= $modalTitle;?></h2>
        <button id="btn_close" aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="resourceForm" enctype="multipart/form-data" onsubmit="return false;">
                <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                <input type="hidden" name="resource_id" value="<?=$_COMPANY->encodeId($resource_id);?>">
                <input type="hidden" name="resource_type" id="resource_type"  value="<?=$resource_type;?>">
                <p> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></p>
               <?php if($parent_id == 0 && $resource_type ==3){ ?>
                <div class="form-group" id="options">
                    <label class="control-lable" id="scope_owner_label" for="group_chapter_channel_id"><?= gettext('Scope'); ?></label>
                    <div class="">
                      <select type="text" tabindex="0" class="form-control" id="group_chapter_channel_id" name="group_chapter_channel_id" >

                      <?php if ($_USER->canCreateOrPublishContentInScopeCSV($groupid,0,0)) { ?>
                        <option data-section="<?= $_COMPANY->encodeId(0) ?>" value="<?= $_COMPANY->encodeId(0) ?>" ><?= $group->val('groupname')." ".$_COMPANY->getAppCustomization()['group']['name-short']; ?></option>
                      <?php } ?>

                      <?php  // Print allowed chapters as options
                      if(!empty($chapters)) {
                          $allowed_chapters = array_filter($chapters, function ($ch) use ($_USER, $groupid) {
                              return $_USER->canCreateOrPublishContentInScopeCSV($groupid,$ch['chapterid'],0);
                          });
                          if (!empty($allowed_chapters)) {
                      ?>
                          <optgroup label="<?=$_COMPANY->getAppCustomization()['chapter']['name-short']?>">
                              <?php foreach ($allowed_chapters as $chapter){ ?>
                              <option data-section="<?= $_COMPANY->encodeId(1) ?>" <?= $resource && $resource->val('chapterid') ==  $chapter['chapterid'] ? 'selected' : '';  ?> value="<?= $_COMPANY->encodeId($chapter['chapterid']) ?>"  >&emsp;<?= htmlspecialchars($chapter['chaptername']); ?></option>
                              <?php } ?>
                          </optgroup>
                          <?php } ?>
                      <?php } ?>

                      <?php // Print allowed channels as options
                      if(!empty($channels)){
                          $allowed_channels = array_filter($channels,
                                                function ($ch) use ($_USER, $groupid) {
                                                    return $_USER->canCreateOrPublishContentInScopeCSV($groupid,0,$ch['channelid']);
                                                });
                          if (!empty($allowed_channels)) {
                      ?>

                          <optgroup  label="<?=$_COMPANY->getAppCustomization()['channel']['name-short']?>">
                              <?php foreach ($allowed_channels as $channel) { ?>
                              <option  data-section="<?= $_COMPANY->encodeId(2) ?>" <?= $resource && $resource->val('channelid') ==  $channel['channelid'] ? 'selected' : '';  ?> value="<?= $_COMPANY->encodeId($channel['channelid']) ?>">&emsp;<?= htmlspecialchars($channel['channelname']); ?></option>
                              <?php } ?>
                          </optgroup>

                          <?php } ?>
                      <?php } ?>

                    </select>
                  </div>
                </div>
            <?php } ?>
              <?php if ($resource_type <> 4) { ?>
                <div class="form-group" >
                    <label for="title"><?= $resource_type == 3 ? gettext('Folder Name') : gettext('Resource Name'); ?><span style="color: #ff0000;">*</span></label>
                    <input class="form-control" id="title" tabindex="0"  type="text" placeholder="<?= $resource_type == 3 ? gettext('Folder Name') : gettext('Resource Name'); ?>..." name="resource_name" value="<?= $resource ? htmlspecialchars($resource->val('resource_name')) : '';?>" required >
                </div>
              <?php } ?>
                <?php if ($resource_type == 1) { ?>
                <div class="form-group" id='ext-div' >
                    <label for="external"><?= gettext('External Link'); ?></label>
                    <input class="form-control" tabindex="0"  id='external' value="<?= $resource ? htmlspecialchars($resource->val('resource')) : ''; ?>"  type="url" placeholder="https://external.link OR email@example.com" name="resource_url" aria-required="true">
                </div>
                <?php  } elseif($resource_type == 2) { ?>
                  <label id="chooseFileBtn"><?= gettext('Upload Resource File'); ?></label>

                  <div class="file-drag-drop-area form-group" id='att-div' tabindex="0">
                      <span id="fake_drag_drop_container">
                        <span role="button" aria-labelledby="chooseFileBtn" class="file-drag-drop-area-fake-btn"><?= gettext("Choose file")?> <span style="color: #ff0000;">*</span></span>
                        <span class="file-drag-drop-msg"><?= gettext("or drag and drop file here"); ?></span>
                      </span>
                      <input tabindex="-1" aria-describedby="fake_drag_drop_container docNote" type="file" class="file-drag-drop-input" id="attachment" name="attachment" accept=".pdf,.xls, .xlsx,.ppt,.pptx,.doc,.docx,.png,.jpeg,.jpg" onchange="readUrl(this,'',50)" aria-required="true">
                      <input type="hidden" id="resource_file" name="resource_file_data" >
                  </div>
                  <p id="docNote" style="color:red;font-size: 10px; margin-top:10px;"><?= gettext('Note: Only .pdf,.xls, .xlsx,.ppt,.pptx .doc,.docx,.png,.jpeg,.jpg files are accepted'); ?></p>

                  <?php if ($resource) { ?>
                    <div class="form-group js-overwrite-resource-file-chk" style="display:none;">
                      <label>
                        <?= gettext('Are you sure you want to overwrite the resource file?') ?>
                        &nbsp;
                        <input type="checkbox" name="overwrite_resource_file" disabled>
                      </label>
                    </div>
                  <?php } ?>
                <?php  } elseif($resource_type == 4) { ?>
                  <div class="file-drag-drop-area form-group bulkDnD" tabindex="0">
                    <span id="fake_drag_drop_container">
                      <span role="button" class="file-drag-drop-area-fake-btn"><?= gettext("Choose files")?> <span aria-hidden="true" style="color: #ff0000;">*</span></span>
                      <span class="file-drag-drop-msg"><?= gettext("or drag and drop files here"); ?></span>
                    </span>
                    <input tabindex="-1" aria-describedby="fake_drag_drop_container docNote" type="file" class="file-drag-drop-input" id="bulkfileupload" name="bulkfileupload" accept=".pdf,.xls, .xlsx,.ppt,.pptx,.doc,.docx,.png,.jpeg,.jpg" onchange="bulkFileUpload(this)" aria-required="true" multiple >
                  </div>
                  <p id="docNote" style="color:red;font-size: 10px; margin-top:10px;"><?= gettext('Note: Only .pdf,.xls, .xlsx,.ppt,.pptx .doc,.docx,.png,.jpeg,.jpg files are accepted'); ?></p>
                <?php  } ?>
                <?php if ($resource_type <> 4) { ?>
                  <div class="form-group" >
                      <label for="description"><?= gettext('Description'); ?></label>
                      <textarea class="form-control" tabindex="0" id="description" placeholder="<?= gettext('Description'); ?>..." name="resource_description" rows="4" ><?= $resource ? htmlspecialchars($resource->val('resource_description')) : '';?></textarea>
                  </div>
                <?php  } ?>
            </form>
        </div>
        <div class="modal-footer">
          <?php if ($resource_type == 4) { ?>
            <button type="button" onclick="bulkFileUploadSubmit('<?= $_COMPANY->encodeId($groupid); ?>',<?=$resource?'1':'0'?>,'<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>','<?= $_COMPANY->encodeId($is_resource_lead_content); ?>');" class="btn btn-affinity bulkFileUploadSubmit prevent-multi-clicks" id="bulkFileUploadSubmit"><?= gettext('Submit'); ?></button>
          <?php  } else { ?>
            <button type="button" onclick="addUpdateGroupResource('<?= $_COMPANY->encodeId($groupid); ?>',<?=$resource?'1':'0'?>,'<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId($channelid); ?>','<?= $_COMPANY->encodeId($is_resource_lead_content); ?>');" class="btn btn-affinity prevent-multi-clicks"><?= gettext('Submit'); ?></button>
          <?php  } ?>
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

$('#resourceModal').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus');
});

$('#resourceModal').on('hidden.bs.modal', function (e) {
  $('#rid_<?=$_COMPANY->encodeId($resource_id);?>').trigger('focus');  
});
retainFocus('#resourceModal');
</script>