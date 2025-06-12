<div class="modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <?= gettext('Manage External Event Volunteers') ?>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="col-md-12">
          <form onsubmit="window.tskp.event_volunteer.addOrEditExternalEventVolunteerByLeader(event)">
            <input type="hidden" name="eventid" value="<?= $_COMPANY->encodeId($event->id()) ?>">
            <?php if ($volunteer) { ?>
              <input type="hidden" name="volunteerid" value="<?= $_COMPANY->encodeId($volunteer?->id() ?? 0) ?>">
            <?php } ?>

            <?php if (!$volunteer) { ?>
              <div class="form-group">
                <div class="list-group list-group-horizontal">
                  <button
                    type="button"
                    class="list-group-item"
                    onclick="addUpdateEventVolunteerModal('<?= $_COMPANY->encodeId($event->id())?>','<?= $_COMPANY->encodeId(0); ?>','<?= $_COMPANY->encodeId(0); ?>')"
                  >
                    <?= gettext('Internal Volunteer') ?>
                  </button>
                  <button
                    type="button"
                    class="list-group-item active"
                    data-eventid="<?= $_COMPANY->encodeId($event->id()) ?>"
                  >
                    <?= gettext('External Volunteer') ?>
                  </button>
                </div>
              </div>
            <?php } ?>

            <div class="form-group">
              <label><?= gettext('First Name') ?><span style="color: #ff0000;"> *</span></label>
              <input type="text" class="form-control" placeholder="<?= gettext('First Name') ?>" name="firstname" value="<?= $volunteer?->getFirstName() ?? '' ?>">
            </div>
            <div class="form-group">
              <label><?= gettext('Last Name') ?><span style="color: #ff0000;"> *</span></label>
              <input type="text" class="form-control" placeholder="<?= gettext('Last Name') ?>" name="lastname" value="<?= $volunteer?->getLastName() ?? '' ?>">
            </div>
            <div class="form-group">
              <label><?= gettext('Email') ?><span style="color: #ff0000;"> *</span></label>
              <input type="email" class="form-control" placeholder="<?= gettext('Enter Email') ?>" name="email" value="<?= $volunteer?->getVolunteerEmail() ?? '' ?>">
            </div>
            <div class="form-group">
              <label><?= gettext('Volunteering Role') ?><span style="color: #ff0000;"> *</span></label>
              <select
                class="form-control"
                name="volunteertypeid"
                <?= isset($volunteer) ? 'disabled' : '' ?>
              >
                <?php foreach ($external_volunteer_roles as $role) { ?>
                  <option
                    value="<?= $_COMPANY->encodeId($role['volunteertypeid']) ?>"
                    <?= $volunteer?->val('volunteertypeid') == $role['volunteertypeid'] ? 'selected' : '' ?>
                  >
                    <?= $role['type'] ?>
                  </option>
                <?php } ?> ?>
              </select>
            </div>

            <div class="form-group">
              <label class="control-label" for="user_search2">
                <?= gettext('Assign Contact Person') ?>
                <span style="color: #ff0000;"> *</span>
              </label>
              <div>
                <input
                  class="form-control"
                  tabindex="0"
                  id="user_search2"
                  autocomplete="off"
                  onkeyup="searchUsersForEventVolunteer(this.value)"
                  placeholder="<?= gettext('Search person by name or email') ?>"
                  type="text"
                  required
                  <?php if ($volunteer) { ?>
                    value="<?= $volunteer->getCareofUser()->val('firstname') . ' ' . $volunteer->getCareofUser()->val('lastname') . '(' . $volunteer->getCareofUser()->val('email') . ')' ?>"
                    disabled
                  <?php } ?>
                >
                <div id="show_dropdown"></div>
              </div>
          </div>

            <button type="submit" class="btn btn-primary">Submit</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
