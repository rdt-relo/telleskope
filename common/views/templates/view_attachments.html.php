<style>  
caption {
    caption-side: top;
}
</style>
<div tabindex="-1" aria-modal="true" role="dialog" class="modal fade attachment_modal tskp-app" id="attachment_modal" aria-label="<?= gettext('View Attached Files') ?>">
  <div  class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">
          <?= gettext('View Attached Files') ?>
        </h2>
        <button id="btn_close" aria-label="Close dialog" type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <?php if (empty($attachments)) { ?>
          <?= gettext('There are no attachments') ?>
        <?php return; } ?>
        <table class="table table-sm display">
        <caption style="font-size:18px; font-weight: normal; color: #212529;"><?= gettext('Attachments') ?></caption>
            <thead>
                <tr>
                    <th><?= gettext('File Name') ?></th>
                    <th><?= gettext('Size') ?></th>
                    <th><?= gettext('Upload Date') ?></th>
                    <th><?= gettext('Action') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attachments as $attachment) { ?>
                    <tr id="js-attachment-<?= $attachment->encodedId() ?>">
                        <td>
                          <a id="att_<?= $attachment->encodedId() ?>"
                            href="<?= $attachment->getDownloadUrl() ?>"
                            class="js-download-link"
                          >
                            <?= $attachment->getImageIcon() ?>
                             &nbsp;
                            <?= $attachment->getDisplayName(32) ?>
                          </a>
                        </td>
                        <td>
                          <?= $attachment->getReadableSize() ?>
                        </td>
                        <td>
                          <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($attachment->val('createdon'),true,true,false) ?>
                        </td>
                        <td>

                          <div class="dropdown show">
                            <a aria-label="<?= $attachment->getDisplayName() ?>" class="dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="dropdownMenuLink">
                              <i class="fa fa-ellipsis-v"></i>
                            </a>

                            <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">

                              <?php if ($attachment->val('topictype') != 'TMP' && $attachment->val('topictype') != 'APRTSK' ) { ?>
                              <a role="button" class="dropdown-item" href="javascript:void(0);" onclick="getShareableLink('<?= $_COMPANY->encodeId(0); ?>', '<?= $attachment->encodedId(); ?>', '12')">
                                <i class="fa fa-share-square" aria-hidden="true"></i>&nbsp;&nbsp;<?= gettext('Get Shareable Link') ?>
                              </a>
                              <?php } ?>

                              <a role="button"
                                class="dropdown-item confirm"
                                href="javascript:void(0);"
                                data-confirm-noBtn="<?= gettext('No') ?>"
                                data-confirm-yesBtn="<?= gettext('Yes') ?>"
                                title="<?= gettext('Are you sure you want to delete this attachment?') ?>"
                                onclick="window.tskp.attachments.deleteAttachment('<?= $attachment->encodedId(); ?>')"
                              >
                                <i class="fa fa-trash" aria-hidden="true"></i>&nbsp;&nbsp;<?= gettext('Delete'); ?>
                              </a>
                            </div>
                          </div>
                          </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
  $('.confirm').popConfirm({content: ''});
</script>
