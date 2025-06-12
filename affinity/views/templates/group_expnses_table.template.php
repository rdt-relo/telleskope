<div class="table-responsive">
    <table id="expenses_table" class="display table table-hover display compact" style="width:100%;table-layout: fixed;" summary="This table displays the list of expenses">
        <thead>
            <tr>
                <th width="15%" scope="col"><?= gettext("Date");?></th>
                <th width="15%" scope="col"><?= gettext("Scope");?></th>
                <th width="30%" scope="col"><?= gettext("Description");?></th>
                <th width="15%" scope="col"><?= gettext("Funding Source");?></th>
                <th width="15%" scope="col"><?= gettext("Planned / Budgeted Amount");?></th>
                <th width="15%" scope="col"><?= gettext("Expensed Amount");?></th>
                <th width="5%" scope="col"></th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $i = 1;
        foreach($expenses as $row) {

            $encodedId = $_COMPANY->encodeId($row['usesid']);
            $grupname =  $row['chaptername'] ? $row['chaptername'] : '-';
            $funding_source = ($row['funding_source'] == 'allocated_budget') ? gettext('Allocated Budget') : gettext('Other Funding');
        ?>
        <tr id="<?= $i; ?>">
            <td>
                <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['date'],true,false,false,'',"UTC"); ?>
            </td>
            <td>
                <?= $grupname; ?>
            </td>
            <td>
                <?= htmlspecialchars($row['description'] ? $row['description'] : '-'); ?>
            </td>
            <td>
                <?= $funding_source ? $funding_source : '-'; ?>
            </td>
            <td>
                <?= $_COMPANY->getCurrencySymbol().number_format($row["budgeted_amount"],2); ?>
            </td>
            <td>
            <a href="javascript:void(0)" style="cursor:pointer; color:#3c8dbc;" onclick="addUpdateExpenseInfoModal('<?= $encGroupId ?>','<?= $encodedId; ?>', '', true)"><?= $_COMPANY->getCurrencySymbol().number_format($row["usedamount"],2); ?></a>
            </td>
            <td>
                <?php if(($_USER->isAdmin() || $_COMPANY->getAppCustomization()['budgets']['allow_grouplead_to_edit_expense']) ){ ?>
                    <div class="col-md-2">
                        <button aria-label="<?= gettext('Date').' '.$_USER->formatUTCDatetimeForDisplayInLocalTimezone($row['date'],true,false,false,'',"UTC") .' '. gettext('Expenses action dropdown');?>" tabindex="0" class="btn-no-style dropdown-toggle fa fa-ellipsis-v" data-toggle="dropdown" aria-expanded="true">                
                        </button>                
                        <div class="dropdown-menu dropmenu">
                            <a href="javascript:void(0)" class="dropdown-item" onclick="addUpdateExpenseInfoModal('<?= $encGroupId; ?>','<?= $encodedId; ?>')"><?= addslashes(gettext("Edit")); ?></a>
                            <a href="javascript:void(0)" class="dropdown-item deluser" data-confirm-noBtn="<?= addslashes(gettext('No')); ?>" data-confirm-yesBtn="<?= addslashes(gettext('Yes')); ?>" title="" onclick="deleteExpenseInfo('<?= $encGroupId; ?>','<?= $encodedId; ?>',<?= $i; ?>)" data-original-title="<?= addslashes(gettext("Are you sure you want to delete?")); ?>"><?= addslashes(gettext("Delete")); ?></a>
                        </div>
                    </div>
                <?php } ?>
            </td>
        </tr>
        <?php $i++; } ?>
        </tbody>
    </table>
</div>	


<?php if($_COMPANY->getAppCustomization()['budgets']['enable_budget_expenses']){ ?>
<script>
    $(document).ready(function() {

        var x = parseInt(localStorage.getItem("local_variable_for_table_pagination"));
		var dtable = $('#expenses_table').DataTable( {
			pageLength:x,
			"order": [],
			"bPaginate": true,
			"bInfo" : false,
			'language': {
                "sZeroRecords": "<?= gettext('No data available in table');?>",
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },
            columnDefs: [
                { targets: [-1], orderable: false }
            ],
		});

        screenReadingTableFilterNotification('#expenses_table',dtable);
	});
    $('#expenses_table').on( 'length.dt', function ( e, settings, len ) {
        localStorage.setItem("local_variable_for_table_pagination", len);
    } );
</script>
<?php } ?>