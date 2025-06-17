<div class="modal fade" id="email_logs_statistics">
    <div aria-label="<?= $pageTitle; ?>" class="modal-dialog modal-dialog-w1000" aria-modal="true" role="dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h2 id="modal-title" class="modal-title"><?= $pageTitle; ?></h2>
          <button id="btn_close" type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <div class="col-md-12">
                <div class="table-responsive ">
                    <table  class="table display" id="statistics" style="width:100%;" summary="This table display the list of expenses">
                        <thead>
                            <tr>
                              <th width="30%" scope="col"><?= gettext("Email Date");?></th>
                              <th width="30%" scope="col"><?= gettext("Email Type");?></th>
                              <th width="14%" scope="col"><?= gettext("Total Recipients");?></th>
                              <th width="13%" scope="col"><?= gettext("Unique Opens");?></th>
                              <th width="13%" scope="col"><?= gettext("Unique Clicks");?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($emailLogs as $log){ ?>
                            <tr>
                              <td class="pl-3">
                              <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($log->val('createdon'),true,true,true); ?>
                              </td>
                              <td class="pl-3"><?= $log->val('label'); ?></td>
                              <td class="pl-3"><?= $log->val('total_rcpts'); ?></td>
                              <td class="pl-3">
                                  <?= (intval($log->val('section_type')) == EmailLog::EMAILLOG_SECTION_TYPES['event'] && substr($log->val('label'), -2) == ' *')
                                      ? 'n/a'
                                      : $log->val('unique_opens');
                                  ?>
                              </td>
                              <td class="pl-3">
                                  <?= $urlTrackingEnabled
                                      ? (empty($log->val('unique_clicks')) && empty($log->val('clickDetails')) ? '-' : (int)$log->val('unique_clicks'))
                                      : gettext('disabled');
                                  ?>
                              </td>
                            </tr>                          
                        <?php } ?>
                        </tbody>
                    </table>
                </div>	
            </div>
        </div>
        <div class="modal-footer">
          <button id="postEmailStatisticsBtn" type="button" class="btn btn-secondary" data-dismiss="modal"><?= gettext("Close");?></button>
        </div>
      </div>
    </div>
  </div>
  <script src="<?=TELESKOPE_.._STATIC?>/vendor/js/datatables-2.1.8/datatables.min.js"></script>
  <script>
      $(document).ready(function() {
          
        var dtable = $('#statistics').DataTable( {
            info:     false,
            order: [[ 0, "desc" ]],            
            "initComplete": function(settings, json) {                            
                setAriaLabelForTablePagination(); 
                $('.current').attr("aria-current","true");  
            },
            language: {
              "sZeroRecords": "<?= gettext('No data available in table');?>",
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },
          } );

          // function for Accessiblity screen reading.
        screenReadingTableFilterNotification('#statistics',dtable);
      } );

$('#email_logs_statistics').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus')
});

$('#email_logs_statistics').on('hidden.bs.modal', function (e) {
    $('#<?=$_COMPANY->encodeId($id);?>').trigger('focus');
})
  </script>