<div class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <?= gettext('Download Points Transactions Report') ?>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form action="ajax_points?downloadPointsTransactionsReport=1" method="POST" onsubmit="">
          <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">

          <div class="form-group">
            <label for="from-datepicker">
              <?= gettext('From:') ?>
            </label>
            <input class="form-control" type="text" id="from-datepicker" readonly autocomplete="off" value="<?= $from ?>" name="from">
          </div>

          <div class="form-group">
            <label for="to-datepicker">
              <?= gettext('To:') ?> &nbsp;
            </label>
            <input class="form-control" type="text" id="to-datepicker" readonly autocomplete="off" value="<?= $to ?>" name="to"> &nbsp;
            <small>
              <?= gettext('Note: You can select a date range of up to 60 days') ?>
            </small>
          </div>

          <div class="form-group">
            <label for="group-selector">
              <?= sprintf(gettext('Select %s'), $_COMPANY->getAppCustomization()['group']['name-plural']) ?>
            </label>
            <select class="form-control" id="group-selector" name="selected_group_id" onchange="onSelectedGroupChange(this)">
              <option value="">
                <?= sprintf(gettext('All %s'), $_COMPANY->getAppCustomization()['group']['name-plural']) ?>
              </option>
              <option value="<?= $_COMPANY->encodeId(0) ?>">
                <?= gettext('Global Points Transactions') ?>
              </option>
              <?php foreach ($groups as $group) { ?>
                <option value="<?= $_COMPANY->encodeId($group->id()) ?>">
                  <?= $group->val('groupname') ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <div class="form-group" style="display:none;">
            <label for="group-users-selector">
              <?= sprintf(gettext('Select %s users'), $_COMPANY->getAppCustomization()['group']['name-short']) ?>
            </label>
            <select class="form-control" id="group-users-selector" name="group_users_selector">
              <option value="ALL"><?= gettext('Both Leaders and Members') ?></option>
              <option value="GROUP_LEADERS"><?= gettext('Leaders only') ?></option>
              <option value="GROUP_MEMBERS"><?= gettext('Members only') ?></option>
            </select>
          </div>

          <div class="row mt-3">
            <div class="col text-center">
              <button type="submit" class="btn btn-primary m-1"><?= gettext('Download') ?></button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var from_datepicker = $('#from-datepicker');
  var to_datepicker = $('#to-datepicker');

  from_datepicker.datepicker({
    dateFormat: 'yy-mm-dd',
    onSelect: function (date) {
      restrictMinMax();
      var from_date = $.datepicker.parseDate('yy-mm-dd', date);
      var max_to_date = new Date(from_date.getTime());
      max_to_date.setDate(from_date.getDate() + 60);
      if (to_date = to_datepicker.datepicker('getDate')) {
        if (to_date > max_to_date) {
          to_datepicker.datepicker('setDate', max_to_date);
        }
      }
    },
  });

  to_datepicker.datepicker({
    dateFormat: 'yy-mm-dd',
    onSelect: function (date) {
      restrictMinMax();
      var to_date = $.datepicker.parseDate('yy-mm-dd', date);
      var min_from_date = new Date(to_date.getTime());
      min_from_date.setDate(to_date.getDate() - 60);
      if (from_date = from_datepicker.datepicker('getDate')) {
        if (from_date < min_from_date) {
          from_datepicker.datepicker('setDate', min_from_date);
        }
      }
    },
  });

  function restrictMinMax() {
    to_datepicker.datepicker('option', 'maxDate', '<?= $today->format('Y-m-d') ?>');
    if (from_date = from_datepicker.datepicker('getDate')) {
      to_datepicker.datepicker('option', 'minDate', from_date);
    }
    if (to_date = to_datepicker.datepicker('getDate')) {
      from_datepicker.datepicker('option', 'maxDate', to_date);
    }
  }

  restrictMinMax();
})();

function onSelectedGroupChange(select_input) {
  var selected_group_id = $(select_input).val();

  var group_users_selector_container = $('#group-users-selector').closest('div.form-group');

  if (!selected_group_id || (selected_group_id === '<?= $_COMPANY->encodeId(0) ?>')) {
    group_users_selector_container.hide();
  } else {
    group_users_selector_container.show();
  }
}
</script>
