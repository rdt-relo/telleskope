<div class="modal fade">
  <div aria-label=" <?= $event->val('event_recording_link') ? gettext('Update Event Recording Link') : gettext('Add Event Recording Link') ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title js-add-update-labels" data-add-txt="<?= gettext('Add Event Recording Link') ?>" data-update-txt="<?= gettext('Update Event Recording Link') ?>">
          <?= $event->val('event_recording_link') ? gettext('Update Event Recording Link') : gettext('Add Event Recording Link') ?>
        </h4>
        <button aria-label="Close dialog" type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">

          <div class="col-12 form-group-emphasis p-3">
          <label for="event_recording_link"><?=gettext('Event Recording Link')?></label>
          <div class="input-group mb-3">
            <input type="text" id="event_recording_link" name="event_recording_link" class="form-control" placeholder="<?= gettext("Enter Event Recording Link");?>" value="<?= $event->val('event_recording_link') ?? '' ?>">
          </div>

          <label for="event_recording_note"><?= gettext('Event Recording Note'); ?></label>
          <div class="input-group mb-3">
            <textarea name="event_recording_note" class="form-control" id="event_recording_note" maxlength="128" cols="30" rows="2"  placeholder="<?= gettext("Enter Event Recording Note");?>"><?= $event->val('event_recording_note') ? trim(htmlspecialchars($event->val('event_recording_note'))) : ''?></textarea>
            <br>
            <small style="color:#222222; width:100%;"><?= gettext('Maximum 128 characters allowed') ?></small>
          </div>

          <div class="text-center">
              <button class="btn btn-affinity" onclick="submitUpdateEventRecordingLinkForm('<?= $event->encodedId() ?>')"
                      data-add-txt="<?= gettext('Add') ?>" data-update-txt="<?= gettext('Update') ?>">
                  <?= $event->val('event_recording_link') ? gettext('Update') : gettext('Add') ?>
              </button>
          </div>
        </div>

        <div class="col-12 form-group-emphasis p-3">
          <label>Shareable Link</label>
          <div class="input-group mb-3">
            <input
              type="text"
              id="shareableLink"
              class="form-control"
              readonly
              value="<?= $event->val('event_recording_link') ? $event->getEventRecordingShareableLink() : '' ?>"
              placeholder="<?= gettext('Shareable link will be auto-generated here') ?>"
            >
            <div class="input-group-append">
              <button
                type="button"
                class="input-group-text btn btn-affinity justify-content-center js-copy-link-btn"
                onclick="copyShareableLink('<?=gettext('Link copied to clipboard.')?>', 'shareableLink')"
                <?= $event->val('event_recording_link') ? '' : 'disabled' ?>
              >
                <?= gettext('Copy Link') ?>
              </button>
            </div>
          </div>

          <?= gettext('Why use the shareable link instead of the direct recording link?')?>
          <ul>
            <li><?=gettext('The shareable link, once generated, does not change. This allows you to update the Event Recording Link without the need to reshare the recording link.')?></li>
            <li><?=gettext('Users who were unable to check in to the live event can check in after viewing the recording.')?></li>
            <li><?=gettext('Collect click analytics')?>
                <ul>
                  <li>
                      See who all clicked the Event Recording Link
                      <form method="POST" action="ajax_reports?event_recording_link_clicks_report=1&event_id=<?= $event->encodedId() ?>" class="d-inline">
                          <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
                          <button
                                  type="submit"
                                  class="btn btn-affinity js-download-report-btn"
                              <?= $event->val('event_recording_link') ? '' : 'disabled' ?>
                          >
                              <?= gettext('Download Report') ?>
                          </button>
                      </form>
                  </li>
              </ul>
            </li>
          </ul>
        </div>

      </div>
    </div>
  </div>
</div>

