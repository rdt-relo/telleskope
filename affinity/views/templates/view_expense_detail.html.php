<div class="modal fade" id="expenseModal">
    <div aria-label="<?= gettext('View Expense Detail') ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h2 id="modal-title" class="modal-title"><?= gettext('View Expense Detail') ?></h2>
          <button id="btn_close" aria-label="<?= gettext("close");?>" type="button" class="close" data-dismiss="modal">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <?php if ($event) { ?>
            <div class="row">
                <div class="col m-3 p-2 alert-info border">
                    This expense is linked to an event named '<?= $event->val('eventtitle') ?>'.
                    <a href="<?= $event->getShareableLink() ?>" target="_blank">
                        Go to event
                        <i class="fa fa-external-link-alt"></i>
                    </a>
                </div>
            </div>

            <?php } ?>
            <div class="row">
              <div class="col">
                <?= gettext('Date') ?>
              </div>
              <div class="col-8">
                <?= $edit['date'] ?>
              </div>
            </div>
            <div class="row">
              <div class="col">
                <?= $_COMPANY->getAppCustomization()['chapter']['name-short'] ?>
              </div>
              <div class="col-8">
                <?php if ($edit['chapterid'] === '0') { ?>
                  <?= $group->val('groupname') . ' (All Chapters)' ?>
                <?php } else { ?>
                  <?php foreach ($chapters as $chapter) { ?>
                    <?php if ($chapter['chapterid'] === $edit['chapterid']) { ?>
                      <?= $chapter['chaptername'] ?>
                    <?php } ?>
                  <?php } ?>
                <?php } ?>
              </div>
            </div>
            <div class="row">
              <div class="col">
                <?= gettext('Event Type') ?>
              </div>
              <div class="col-8">
                <?= $edit['eventtype'] ?? '' ?>
              </div>
            </div>
            <div class="row">
              <div class="col">
                <?= gettext('Charge Code') ?>
              </div>
              <div class="col-8">
              <?php foreach ($charge_codes as $code) { ?>
                <?php if ($code['charge_code_id'] === $edit['charge_code_id']) { ?>
                  <?= htmlspecialchars($code['charge_code']) ?>
                <?php } ?>
              <?php } ?>
              </div>
            </div>
            <div class="row">
                <div class="col">
                    <?= gettext('Expense Amount') ?>
                </div>
                <div class="col-8">
                    <?=  $_COMPANY->getCurrencySymbol() . ' '. $_USER->formatAmountForDisplay($edit['usedamount'],'') ?>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <?= gettext('Budget Amount') ?>
                </div>
                <div class="col-8">
                    <?= $_COMPANY->getCurrencySymbol() . ' '. $_USER->formatAmountForDisplay($edit['budgeted_amount'],'') ?>
                </div>
            </div>
            <?php
						if($_COMPANY->getAppCustomization()['budgets']['vendors']['enabled']){ ?>
            <div class="row">
              <div class="col">
                <?= gettext('Vendor Name') ?>
              </div>
              <div class="col-8">
                <?= htmlspecialchars($edit['vendor_name'] ?? '') ?>
              </div>
            </div>
            <?php } ?>

            <?= $expense_entry->renderCustomFieldsComponent('v1') ?>

            <div class="row">
            <div class="col-md-12">
                <div class="table-responsive ">
                    <table  class="table display" id="subItemsTable" style="width:100%;" summary="This table display this list of sub items of an expense">
                    <caption><?= gettext('Sub items list') ?></caption>
                        <thead>
                            <tr>
                              <th width="30%" scope="col"><?= gettext("Expense Item");?></th>
                              <th width="50%" scope="col"><?= gettext("Detail");?></th>
                                <th width="20%" scope="col"><?= gettext("Budget");?></th>
                              <th width="20%" scope="col"><?= gettext("Expense");?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php for($i=0;$i<count($sub);$i++){ ?>
                            <tr>
                              <td><?= htmlspecialchars($sub[$i]['expensetype']); ?></td>
                              <td><?= htmlspecialchars($sub[$i]['item']); ?></td>
                              <td><?= $_COMPANY->getCurrencySymbol() . number_format($sub[$i]['item_budgeted_amount'],2); ?></td>
                              <td><?= $_COMPANY->getCurrencySymbol() . number_format($sub[$i]['item_used_amount'],2); ?></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?= $expense_entry->renderAttachmentsComponent('v6') ?>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= gettext("Close");?></button>
        </div>
      </div>
    </div>
  </div>
  <script src="../vendor/js/datatables-2.1.8/datatables.min.js"></script>
  <script>
      $(document).ready(function() {

          $('#subItemsTable').DataTable( {
            "searching": false,
            "paging":   false,
            "ordering": false,
            "info":     false,
            'language': {
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },

          } );
      } );

$('#expenseModal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus')
});
  </script>
