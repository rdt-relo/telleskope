<?php if (!$attachments) { return; } ?>
<div class="<?= $div_classes ?? '' ?>" <?= $div_attrs ?? '' ?>>
  <strong><?= count($attachments) === 1 ? gettext('One Attachment') : sprintf(gettext('%d Attachments'), count($attachments)) ?></strong>
  <p>
    <?php foreach ($attachments as $attachment) { ?>
      <a
        href="<?= $attachment->getDownloadUrl() ?>"
        class="js-download-link"
        aria-label="<?= sprintf(gettext('Download Attachment %s'), $attachment->getDisplayName())?>"
      >
        <?= $attachment->getImageIcon() ?>
          &nbsp;
        <?= $attachment->getDisplayName() ?>
        (<?= $attachment->getReadableSize() ?>)
      </a>
      <br>
    <?php } ?>
  </p>

  <small><?= $note ?? '' ?></small>
</div>
