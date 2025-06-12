<?php if ($this->getCurrentTopicType() === 'TMP') { ?>
  <input type="hidden" name="ephemeral_topic_id" value="<?= $this->encodedId() ?>">
<?php } ?>

<div class="form-group <?= $div_classes ?? '' ?>">
  <label class="<?= $label_classes ?? '' ?>"><?= gettext('File Attachments');?></label>
  <button
    type="button"
    class="btn prevent-multi-clicks js-view-attachments-btn"
    onclick="window.tskp.attachments.viewAttachments(event)"
    data-topictype="<?= $this->getCurrentTopicType() ?>"
    data-topicid="<?= $this->encodedId() ?>"
  >
    <i class="fa fa-paperclip" aria-hidden="true"></i> <?= gettext('View Files') ?>
  </button>
  <button
    type="button"
    class="btn prevent-multi-clicks js-upload-attachments-btn"
    onclick="window.tskp.attachments.openAttachmentsUploader(event)"
    data-topictype="<?= $this->getCurrentTopicType() ?>"
    data-topicid="<?= $this->encodedId() ?>"
  >
    <i class="fa fa-upload" aria-hidden="true"></i> <?= gettext('Upload Files'); ?>
  </button>
</div>
