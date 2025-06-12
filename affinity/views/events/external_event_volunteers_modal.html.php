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
        <div class="col-md-12 text-center">
          <h5>
            <?= $volunteer ? gettext('Update External Volunteer') : gettext('Add an External Volunteer') ?>
          </h5>
          <hr class="linec">
        </div>

        <?php if ($show_create_success_banner ?? false) { ?>
          <div class="col-md-12">
            <div class="tskp-hidemessage alert alert-info alert-dismissable">
              <?= gettext('Succesfully added a new volunteer') ?>
            </div>
          </div>
        <?php } ?>

        <?php if ($show_update_success_banner ?? false) { ?>
          <div class="col-md-12">
            <div class="tskp-hidemessage alert alert-info alert-dismissable">
              <?= gettext('Succesfully updated volunteer details') ?>
            </div>
          </div>
        <?php } ?>

        <?php if ($show_delete_success_banner ?? false) { ?>
          <div class="col-md-12">
            <div class="tskp-hidemessage alert alert-info alert-dismissable">
              <?= gettext('Succesfully deleted volunteer') ?>
            </div>
          </div>
        <?php } ?>

        <div class="col-md-12">
          <form onsubmit="window.tskp.event_volunteer.addOrEditExternalEventVolunteer(event)">
            <input type="hidden" name="eventid" value="<?= $_COMPANY->encodeId($event->id()) ?>">
            <input type="hidden" name="volunteerid" value="<?= $_COMPANY->encodeId($volunteer?->id() ?? 0) ?>">
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
            <button type="submit" class="btn btn-primary">Submit</button>
          </form>
        </div>

        <?php if (count($external_volunteers) && !$volunteer) { ?>
          <div class="col-md-12 text-center">
            <h5><?= gettext('External Volunteers You\'ve Added') ?></h5>
            <hr class="linec">
          </div>

          <div class="col-md-12">
            <table class="table table-hover responsive display compact" id="externalVolunteers">
              <thead>
                <tr>
                  <th scope="col"><?= gettext('First Name') ?></th>
                  <th scope="col"><?= gettext('Last Name') ?></th>
                  <th scope="col"><?= gettext('Email') ?></th>
                  <th scope="col"><?= gettext('Volunteer Type') ?></th>
                  <th scope="col"><?= gettext('Action') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($external_volunteers as $volunteer) { ?>
                  <tr>
                    <td><?= $volunteer->getFirstName() ?></td>
                    <td><?= $volunteer->getLastName() ?></td>
                    <td><?= $volunteer->getVolunteerEmail() ?></td>
                    <td><?= $event->getVolunteerTypeValue($volunteer->val('volunteertypeid')) ?></td>
                    <td>
                      <a
                        href="javascript:void(0);"
                        onclick="window.tskp.event_volunteer.openExternalEventVolunteerModal(event)"
                        data-eventid="<?= $_COMPANY->encodeId($event->id()) ?>"
                        data-volunteerid="<?= $_COMPANY->encodeId($volunteer?->id() ?? 0) ?>"
                      >
                        <i class="fa fa-edit" title="Edit"></i>
                      </a>
                      &nbsp;
                      <a
                        href="javascript:void(0);"
                        onclick="window.tskp.event_volunteer.deleteExternalEventVolunteer(event, '<?= $_COMPANY->encodeId($event->id()) ?>', '<?= $_COMPANY->encodeId($volunteer?->id() ?? 0) ?>')"
                        class="tskp-popconfirm"
                        data-confirm-title="<?= gettext('Are you sure you want to delete this volunteer?') ?>"
                      >
                        <i class="fa fa-trash" title="Delete"></i>
                      </a>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
            <script>
                $(document).ready(function() {
                  $('#externalVolunteers').DataTable({  
                    destroy: true,
                    order: [],
                    bPaginate: true,
                    bInfo : true,
                    "columnDefs": [
                       { targets: [-1], orderable: false }
                    ],
                    language: {
                          searchPlaceholder: "...",
                          url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
                        },
                  });
                });
              </script>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>

