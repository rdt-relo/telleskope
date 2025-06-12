<div id="manage_event_expense_entries_model" class="modal fade">
    <div aria-label="<?=$modelTitle;?>" class="modal-dialog modal-lg modal-dialog-w1000" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
            <?php
                //Disclaimer check
                // $onClickFunc = "javascript:void(0);";
                $enc_eventid = $_COMPANY->encodeId($event->val('eventid'));
                if ($event->val('groupid')){
                    $checkDisclaimerExists = Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['BUDGET_EXPENSE_CREATE_BEFORE'], $event->val('groupid'));
                    $enc_groupid = $_COMPANY->encodeId($event->val('groupid'));
                    //$enc_eventid = $_COMPANY->encodeId($event->val('eventid'));
                    if($checkDisclaimerExists){
                        $call_method_parameters = array(
                            $enc_groupid,
                            $enc_eventid,
                        );
                        $call_other_method = base64_url_encode(json_encode(
                            array (
                                "method" => "addEventExpenseEntry",
                                "parameters" => $call_method_parameters
                            )
                        ));
                        $onClickFunc = "loadDisclaimerByHook('".$_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['BUDGET_EXPENSE_CREATE_BEFORE'])."','".$enc_groupid."', 0, '".$call_other_method."');";
                    }else{
                        $onClickFunc = "addEventExpenseEntry('". $enc_groupid ."', '".$enc_eventid."');";
                    }
                } elseif($event->val('collaborating_groupids')) {
                    $onClickFunc = "getGroupChaptersForEventExpenseEntry('".$enc_eventid."');";
                }


                //$enc_eventid = $_COMPANY->encodeId($event->val('eventid'));
                $onClickFunc = "getGroupChaptersForEventExpenseEntry('".$enc_eventid."');";
                ?>
                <h4 id="modal-title" class="modal-title"><?=$modelTitle;?>
                <a class="new-expense-btn" href="javascript:void(0);" onclick="<?= $onClickFunc; ?>"><i aria-label="Add Expense" class="fa fa-plus-circle"></i></a>
                </h4>

                <button aria-label="<?= gettext("close");?>" type="button" id="btn_close" class="close" data-dismiss="modal">&times;</button>
                
            </div>
            <div class="modal-body">
                <?php if ($isActionDisabledDuringApprovalProcess) { ?>
                    <div class="col-md-12 p-0 mb-3">
                        <div class="alert-warning p-3 text-small">
                            <?=sprintf(gettext('This event is currently in the approval process or has been approved. %1$s changes are not permitted. To make changes, request the event approver to deny the approval.'), gettext('Budget'))?>
                            <br>
                            <br>
                            <?= gettext('You can still update the expenses for the event.')?>
                        </div>
                    </div>
                <?php } ?>
                    <div class="table-responsive">
                        <table id="manage_event_expense_entries_table" class="display table table-hover display compact" style="width:100%;table-layout: fixed;" summary="This table displays the list of event expense entries">
                            <thead>
                                <tr>
                                    <th width="15%" scope="col"><?= gettext("Date");?></th>
                                    <th width="15%" scope="col"><?=$_COMPANY->getAppCustomization()['group']['name-short']?></th>
                                    <th width="15%" scope="col"><?= gettext("Scope");?></th>
                                    <th width="30%" scope="col"><?= gettext("Description");?></th>
                                    <th width="15%" scope="col"><?= gettext("Funding Source");?></th>
                                    <th width="15%" scope="col"><?= gettext("Budgeted Amount");?></th>
                                    <th width="15%" scope="col"><?= gettext("Expensed Amount");?></th>
                                    <th width="5%" scope="col"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Expense entries will display here -->

                                <?php 
                                $i = 1;
                                foreach($eventExpenseEntries as $expenseEntry){ 
                                    $encodedId = $_COMPANY->encodeId($expenseEntry['usesid']);
                                    $encGroupId = $_COMPANY->encodeId($expenseEntry['groupid']);
                                ?>
                                    <tr id="<?= $i; ?>">
                                        <td><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($expenseEntry['date'],true,false,false,'',"UTC"); ?></td>
                                        <td><?= $expenseEntry['groupname'] ?></td>
                                        <td><?= $expenseEntry['chaptername']; ?></td>
                                        <td><?= htmlspecialchars($expenseEntry['description'] ? $expenseEntry['description'] : '-'); ?></td>
                                        <td><?= ($expenseEntry['funding_source'] == 'allocated_budget') ? gettext('Allocated Budget') : gettext('Other Funding');; ?></td>
                                        <td><?= $_COMPANY->getCurrencySymbol($expenseEntry['zoneid']).number_format($expenseEntry["budgeted_amount"],2); ?></td>
                                        <td><a href="javascript:void(0)" style="cursor:pointer; color:#3c8dbc;" onclick="addUpdateExpenseInfoModal('<?= $encGroupId ?>','<?= $encodedId; ?>', '', true)"><?= $_COMPANY->getCurrencySymbol($expenseEntry['zoneid']).number_format($expenseEntry["usedamount"],2); ?></a></td>
                                       
                                        <td>
                                            <?php if(($_USER->isAdmin() || $_COMPANY->getAppCustomization()['budgets']['allow_grouplead_to_edit_expense']) ){ ?>
                                                <div class="col-md-2">
                                                    <button aria-label="<?= gettext('Date').' '.$_USER->formatUTCDatetimeForDisplayInLocalTimezone($expenseEntry['date'],true,false,false,'',"UTC") .' '. gettext('Expenses action dropdown');?>" tabindex="0" class="btn-no-style dropdown-toggle fa fa-ellipsis-v" data-toggle="dropdown" aria-expanded="true">                
                                                    </button>                
                                                    <div class="dropdown-menu dropmenu">
                                                        <a href="javascript:void(0)" class="dropdown-item" onclick="addUpdateExpenseInfoModal('<?= $encGroupId; ?>','<?= $encodedId; ?>')"><?= addslashes(gettext("Edit")); ?></a>
                                                        <a href="javascript:void(0)" class="dropdown-item deluser" data-confirm-noBtn="<?= addslashes(gettext('No')); ?>" data-confirm-yesBtn="<?= addslashes(gettext('Yes')); ?>" title="" onclick="deleteExpenseInfo('<?= $encGroupId; ?>','<?= $encodedId; ?>',<?= $i; ?>)" data-original-title="<?= addslashes(gettext("Are you sure you want to delete?")); ?>"><?= addslashes(gettext("Delete")); ?></a>
                                                    </div>
                                                </div>
                                        
                                        </td>
                                        <?php } ?>

                                    </tr>
                                <?php  $i++;  } ?>
                            </tbody>
                        </table>
                    
                </div>
            </div>
            <div class="modal-footer text-center">
                <button type="button" class="btn btn-affinity" data-dismiss="modal"><?= gettext("Close");?></button>
              </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#manage_event_expense_entries_table').DataTable({
            "order": [],
            "bPaginate": true,
            "bInfo": false,
            "columnDefs": [
              { targets: [-1], orderable: false }
                    ],
            "drawCallback": function( settings ) {
                $(".confirm").popConfirm({content: ''});
            },
            language: {
            searchPlaceholder: "...",
            url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val("language")); ?>.json'                
          }

        });
    });
    $('#manage_event_expense_entries_model').on('shown.bs.modal', function () {
        $('.new-expense-btn').focus();
    });


    function getGroupChaptersForEventExpenseEntry(e) {
        closeAllActiveModal();
        $.ajax({
            url: 'ajax_events?getGroupChaptersForEventExpenseEntry=1&eventid='+e,
            success: function (data) {

                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false});
                } catch(e) {
                    var container = $('#modal_over_modal');
                    container.html(data);
                    container.find('.modal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                }
            }
        });

    }
</script>