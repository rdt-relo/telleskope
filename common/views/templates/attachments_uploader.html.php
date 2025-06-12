<style>  
caption {
    caption-side: top;
}
</style>
<div class="modal fade attachment_modal tskp-app" id="attachment_modal">
  <div aria-label="<?= gettext('Upload Attachments') ?>" class="modal-dialog" aria-modal="true" role="dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h2 class="modal-title"><?= gettext('Upload Attachments') ?></h2>
      <button aria-label="close" id="btn_close" type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <form>
          <div class="form-group">
            <input aria-describedby="docNote" type="file" class="form-control-file" accept=".pdf,.xls,.xlsx,.ppt,.pptx,.doc,.docx,.png,.jpeg,.jpg" onchange="window.tskp.attachments.onFileSelect(this)" multiple>
          </div>
          <p id="docNote" style="color:red;font-size: 10px; margin-top:10px;"><?= gettext('Note: Only .pdf,.xls,.xlsx,.ppt,.pptx,.doc,.docx,.png,.jpeg,.jpg files are accepted') ?></p>
        </form>
        <div class="js-files-list"></div>
      </div>
      <div class="modal-footer">
        <button type="submit" onclick="window.tskp.attachments.uploadFiles(this)" class="btn btn-affinity prevent-multi-clicks"><?= gettext('Submit') ?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= gettext('Close') ?></button>
      </div>
    </div>
  </div>
</div>
<script>
  window.tskp.templates ||= {};
  window.tskp.templates.attachments_uploader_filelisting_template = `
    <% if (!files.length) { return ''; } %>
    <table class="table table-hover table-sm display compact">
    <caption style="font-size:18px; font-weight: normal; color: #212529;"><?= gettext('Uploaded files') ?></caption>
      <thead>
        <tr>
          <th scope="col"><?= gettext('Name') ?></th>
          <th scope="col"><?= gettext('Size') ?></th>
          <th scope="col">&nbsp;</th>
          <th scope="col"></th>
        </tr>
      </thead>
      <tbody>
        <% for (var i = 0; i < files.length; i++) { %>
          <tr scope="row" data-index=<%= i %>>
            <td data-toggle="tooltip" title="<%= files[i].name %>">
              <%= files[i].name.length > 32 ? files[i].name.substring(0, 29) + '...' : files[i].name  %>
            </td>
            <td><%= fileSizeFormatter(files[i].size) %></td>
            <td>
              <div class="text-center">
                <% if (files[i].status === 'selected') { %>
                  <button aria-label="<?= gettext('Remove ') ?><%= files[i].name %>" class="fa fa-trash btn-no-style" style="cursor: pointer;" onclick="window.tskp.attachments.removeSelectedFile(this)">
                    <span class="sr-only"><?= gettext('Remove') ?></span>
                  </button>
                <% } else if (files[i].status === 'uploaded') { %>
                  <i class="fa fa-check text-success">
                    <span class="sr-only"><?= gettext('Successfully uploaded') ?></span>
                  </i>
                <% } else if (files[i].status === 'uploading') { %>
                  <div class="spinner-border spinner-border-sm text-secondary" role="status">
                    <span class="sr-only"><?= gettext('Loading...') ?></span>
                  </div>
                <% } else if (files[i].status === 'failed') { %>
                  <span class="text-danger"><?= gettext('Failed') ?></span>
                <% } else if (files[i].status === 'big_file') { %>
                  <span class="text-danger"><?= gettext('File size exceeds <%= window.tskp.attachments.max_file_size %> MB') ?></span>
                <% } %>
              </div>
            </td>
          </tr>
        <% } %>
      </tbody>
    </table>
  `;

  window.tskp.attachments.initUploader(
    '<?= $topictype ?>',
    '<?= $_COMPANY->encodeId($topicid) ?>',
    <?= $max_file_attachments ?>
  );

  $('#attachment_modal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus');
    $("#hidden_div_for_notification").attr('role','status');    
});

$('#attachment_modal').on('hidden.bs.modal', function (e) {
    $("#hidden_div_for_notification").html('');
    $("#hidden_div_for_notification").removeAttr('aria-live');
})

</script>
