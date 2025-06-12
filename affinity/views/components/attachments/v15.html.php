<?php if (!$attachments) { return; } ?>
<p>
  <strong><?= count($attachments) === 1 ? gettext('One Attachment') : sprintf(gettext('%d Attachments'), count($attachments)) ?></strong>
  <br>
  <?php foreach ($attachments as $attachment) { ?>
    <a
      href="<?= $attachment->getDownloadUrl() ?>"
      style="text-decoration: none;"
      class="js-download-link"
      aria-label="<?= gettext('Download Attachment') ?>"
    >
      <?= $attachment->getDisplayName() ?>
      (<?= $attachment->getReadableSize() ?>)
    </a>
    <br>
  <?php } ?>
</p>
