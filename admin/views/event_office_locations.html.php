<div class="container col-md-offset-2 margin-top" >
  <div class="row">
    <div class="col-md-12">
      <?php if (isset($_SESSION['error']) && ((time()- $_SESSION['error']) < 5)) { ?>
        <div id="hidemesage" class="alert alert-danger alert-dismissable">
          <a href="#" class="close" data-dismiss="alert" aria-label="close">x</a>
          <?= $_SESSION['form_error'] ?>
        </div>
      <?php } ?>

      <?php if (isset($_SESSION['updated']) && ((time()- $_SESSION['updated']) < 5)) { ?>
        <div id="hidemesage" class="alert alert-info alert-dismissable">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">X</a>
            <?= UPDATED; ?>
        </div>
      <?php } elseif (isset($_SESSION['added']) && ((time()- @$_SESSION['added']) < 5)) { ?>
        <div id="hidemesage" class="alert alert-info alert-dismissable">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">X</a>
            <?= ADDED; ?>
        </div>
      <?php } ?>
      <div class="widget-simple-chart card-box">
        <div class="col-md-12 divider">
          <h6>
            <?= gettext('Manage Event Locations') ?>
            <a aria-label="<?= gettext('Add Event Office-Location') ?>" class="add-plus-circle-icon" href="event_office_locations?action=new">
              <i class="fa fa-plus-circle" title="<?= gettext('Add Event Office-Location') ?>" aria-hidden="true"></i>
            </a>
          </h6>
        </div>
        <div class="clearfix"></div>
        <div class="col-md-12" >
          <div class="table-responsive">
            <table id="event-office-locations-list" class="table display table-hover compact" summary="<?= gettext('Event Locations List') ?>">
              <thead>
                <tr>
                  <th width="35%" scope="col"><?= gettext('Location Name') ?></th>
                  <th width="50%" scope="col"><?= gettext('Location Address') ?></th>
                  <th width="15%" scope="col"><?= gettext('Action') ?></th>
                </tr>
              </thead>

              <tbody>
                <?php foreach ($event_office_locations as $office_location) { ?>
                  <tr
                    <?= $office_location->isInactive() ? 'style="background-color: #fde1e1;"' : '' ?>
                  >
                    <td><?= $office_location->val('location_name') ?></td>
                    <td><?= $office_location->val('location_address') ?></td>
                    <td>
                      <a
                        aria-label="<?= gettext('Edit') ?>"
                        href="event_office_locations?event_office_location_id=<?= $office_location->encodedId() ?>"
                      >
                        <i class="fa fa-edit" title="Edit"></i>
                      </a>
                      &nbsp;&nbsp;
                      <button
                        class="btn btn-no-style js-pop-confirm"
                        <?php if ($office_location->isActive()) { ?>
                          <?php
                            $updated_status = EventOfficeLocation::STATUS_INACTIVE;
                            $icon_class = 'fa-lock';
                            $icon_title = gettext('Deactivate');
                          ?>
                          title="<?= gettext('Are you sure you want to Deactivate!') ?>"
                        <?php } else { ?>
                          <?php
                            $updated_status = EventOfficeLocation::STATUS_ACTIVE;
                            $icon_class = 'fa-unlock-alt';
                            $icon_title = gettext('Activate');
                          ?>
                          title="<?= gettext('Are you sure you want to Activate!') ?>"
                          <?php } ?>
                          aria-label="<?= $icon_title ?>"
                          onclick="changeEventOfficeLocationStatus('<?= $office_location->encodedId() ?>', <?= $updated_status ?>)"
                      >
                        <i class="fa <?= $icon_class ?>" aria-hidden="true" title="<?= $icon_title ?>"></i>
                      </button>
                    </td>
                  </tr>
                <?php }	?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/datatables-2.1.8/datatables.min.js"></script>
<script>
  $(document).ready(function() {
    $('#event-office-locations-list').DataTable({
      pageLength: 50,
      "columnDefs": [
                    { targets: [-1], orderable: false }
                ],
      language: {
            searchPlaceholder: "...",
            url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val("language")); ?>.json'
        },
    });

    $('.js-pop-confirm').popConfirm({content: ''});
  });
</script>

</body>
</html>
