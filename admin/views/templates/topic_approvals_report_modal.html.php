<div class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
        <?= sprintf(gettext('Download %s Approvals Report'), $topicTypeLabel) ?>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form action="ajax?downloadTopicApprovalsReport=1" method="POST" onsubmit="">
          <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
          <input type="hidden" name="topicType" value="<?= $topicType; ?>">
          <div class="form-group">
            <label for="start_date">
              <?= gettext('From Date (Approval Request Submission Date):') ?>
            </label>
            <input class="form-control" type="text" id="start_date" readonly  value="" name="from" placeholder="YYYY-MM-DD">
          </div>

          <div class="form-group">
            <label for="end_date">
              <?= gettext('To Date (Approval Request Submission Date):') ?> &nbsp;
            </label>
            <input class="form-control" type="text" id="end_date" readonly  value="" name="to" placeholder="YYYY-MM-DD"> &nbsp;
            <?php if (0) { ?>
            <small>
              <?= gettext('Note: Please note that the date range pertains to the date on which approval was requested.') ?>
            </small>
            <?php } ?>
          </div>

          <div class="form-group">
            <label for="group-selector">
              <?= sprintf(gettext('Select %s'), $_COMPANY->getAppCustomization()['group']['name-short']) ?>
            </label>
            <select class="form-control" id="group-selector" name="selected_group_id">
              <option value="">
                <?= sprintf(gettext('All %s'), $_COMPANY->getAppCustomization()['group']['name-plural']) ?>
              </option>
              <option value="<?= $_COMPANY->encodeId(0) ?>">
                <?= sprintf(gettext('Global %ss'),$topicTypeLabel) ?>
              </option>
              <?php foreach ($groups as $group) { ?>
                <option value="<?= $_COMPANY->encodeId($group->id()) ?>">
                  <?= $group->val('groupname') ?>
                </option>
              <?php } ?>
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
jQuery(function() {
    jQuery( "#end_date" ).datepicker({
        prevText:"click for previous months",
        nextText:"click for next months",
        showOtherMonths:true,
        selectOtherMonths: true,
        dateFormat: 'yy-mm-dd',
    });
    jQuery( "#start_date" ).datepicker({
		prevText:"click for previous months",
		nextText:"click for next months",
		showOtherMonths:true,
		selectOtherMonths: true,
		dateFormat: 'yy-mm-dd',
        onSelect: function (selectedDate) {
            var orginalDate = new Date(selectedDate);
            var monthsAddedDate = new Date(new Date(orginalDate).setMonth(orginalDate.getMonth() + 12));
            $("#end_date").datepicker("option", 'minDate', orginalDate);
            $("#end_date").datepicker("option", 'maxDate', monthsAddedDate);
        }
	});
});
$(document).ready(function () {
    var todayDate = new Date();
    var monthsAddedDate = new Date(new Date(todayDate).setMonth(todayDate.getMonth() + 12));
    $("#end_date").datepicker("option", 'minDate', todayDate);
    $("#end_date").datepicker("option", 'maxDate', monthsAddedDate);
    $("#start_date").datepicker('setDate', todayDate);
    $("#end_date").datepicker('setDate', monthsAddedDate);
});
</script>
