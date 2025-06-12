<div id="js-view-user-points-breakup-modal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <?= gettext('View User Points Breakup') ?>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <form action="ajax_points?downloadPointsTransactionsReport=1" method="GET" onsubmit="refreshViewUserPointsBreakupResults(event)">
              <input type="hidden" name="userid" value="<?= $_COMPANY->encodeId($userid) ?>">
              <input type="hidden" name="points_program_id" value="<?= $_COMPANY->encodeId($points_program_id) ?>">

              <div class="form-group">
                  <div class="col-md-3 font-weight-bold p-0"><?= gettext('Person Name') ?> :</div>
                  <div class="col-md-9 p-0"><?= $user ? ($user->getFullName() . ' (' . $user->getEmailForDisplay() . ')') : '' ?></div>
              </div>

              <div class="form-group">
                  <div class="col-md-3 font-weight-bold p-0"><?= gettext('Points Program Name') ?> :</div>
                  <div class="col-md-9 p-0"><?= $points_program->val('title') ?></div>
              </div>

              <div class="form-group">
                  <div class="col-md-3 font-weight-bold p-0"><?= gettext('Earnings Period') ?> :</div>
                  <div class="col-md-9 form-inline p-0">
                      <label class="form-inline" for="from-datepicker"><?= gettext('from') ?></label>&nbsp;<input
                              class="form-inline" type="text" id="from-datepicker" readonly autocomplete="off"
                              value="<?= $from_date ?>" name="from">

                      <label class="form-inline" for="from-datepicker"><?= gettext('to') ?></label>&nbsp;<input
                              class="form-inline" type="text" id="to-datepicker" readonly autocomplete="off"
                              value="<?= $to_date ?>" name="to">

                      <small class="pt-2">
                          <?= gettext('Note: You can select a date range of up to 365 days') ?>
                      </small>
                  </div>
              </div>

              <div class="form-group">
                  <div class="col-md-3 font-weight-bold p-0">
                      <label for="group-selector">
                          <?= $_COMPANY->getAppCustomization()['group']['name-plural'] ?>
                      </label>
                  </div>
                  <div class="col-md-9 form-inline p-0">
                      <select class="form-control form-group-sm" id="group-selector" name="selected_group_id">
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
              </div>

              <div class="row mt-3">
                  <div class="col text-center">
                      <button type="submit" class="btn btn-primary m-1"><?= gettext('Submit') ?></button>
                  </div>
              </div>
          </form>

        <hr class="skip-custom-style">

        <div id="js-view-user-points-breakup-results">
          <?php if (empty($transactions)) { ?>
            No Results Found
          <?php } else { ?>
            <table class="table table-hover display compact" width="100%">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Date</th>
                  <th>Amount</th>
                  <th><?= $_COMPANY->getAppCustomization()['group']['name-short'] ?></th>
                  <th>Description</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($transactions as $transaction) { ?>
                  <tr>
                    <td><?= $_COMPANY->encodeIdForReport($transaction['points_transaction_id']) ?></td>
                    <td><?= $transaction['created_at'] ?></td>
                    <td><?= $transaction['amount'] ?></td>
                    <td><?= $transaction['groupname'] ?></td>
                    <td><?= $transaction['transaction_description'] ?></td>
                    <td><?= $transaction['action'] ?></td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>

            <?php
            /**
             * Do not move this script tag to the bottom
             * It needs to be within the #js-view-user-points-breakup-results container
             * As its used in refreshViewUserPointsBreakupResults() JS function
             */ ?>
            <script>
              var modal = $('#js-view-user-points-breakup-modal');

              var renderDataTable = function () {
                $('#js-view-user-points-breakup-results table').DataTable({
                  order: [[1, 'desc']],
                });
              }

              if ((modal.data('bs.modal') || {})._isShown) {
                renderDataTable();
              } else {
                modal.on('shown.bs.modal', renderDataTable);
              }
            </script>
          <?php } ?>
        </div>
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
      max_to_date.setDate(from_date.getDate() + 365);
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
      min_from_date.setDate(to_date.getDate() - 365);
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

</script>
