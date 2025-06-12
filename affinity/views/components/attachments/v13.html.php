<?php if (!$attachments) { return; } ?>
<br>
<tr>
  <td>
    <div style=" border-top: 1px solid #DDDDDD;">
      <p>
        <strong><?= count($attachments) === 1 ? gettext('One Attachment') : sprintf(gettext('%d Attachments'), count($attachments)) ?></strong>
      </p>
      <p>
        <?php foreach ($attachments as $attachment) { ?>
          <a
            href="<?= $attachment->getDownloadUrl() ?>"
            style="text-decoration: none;"
            class="js-download-link"
            aria-label="<?= gettext('Download Attachment') ?>"
          >
            <?php //echo $attachment->getImageIcon() /* todo:  icons sent in email should point to cloudfront */?>
              &nbsp;
            <?= $attachment->getDisplayName() ?>
            (<?= $attachment->getReadableSize() ?>)
          </a><br>
          <br>
        <?php } ?>
      </p>
    </div>
  </td>
</tr>

