<div class="modal fade" id="blockedKeywordModal" tabindex="-1" aria-labelledby="blockedKeywordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-top">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="blockedKeywordModalLabel">
          <?= $blocked_keyword ? gettext('Update Blocked Keyword') : gettext('Create Blocked Keyword') ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= gettext('Close') ?>"></button>
      </div>
      <div class="modal-body">
        <form onsubmit="return addUpdateBlockedKeyword(event)">
          <input type="hidden" name="blocked_keyword_id" value="<?= $blocked_keyword?->encodedId() ?? $_COMPANY->encodeId(0) ?>">
          <div class="mb-3">
            <label for="blockedKeywordInput" class="form-label"><?= gettext('Blocked Keyword') ?></label>
            <input type="text" id="blockedKeywordInput" class="form-control" name="blocked_keyword" value="<?= $blocked_keyword?->val('blocked_keyword') ?? '' ?>" required>
            <div class="form-text"><?= gettext('Keywords will be automatically converted to lowercase'); ?></div>
          </div>
          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= gettext('Close') ?></button>
            <button type="submit" class="btn btn-primary">
              <?= $blocked_keyword ? gettext('Update') : gettext('Create') ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

