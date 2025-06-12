  <!-- Event budget and expense data -->

    <?php
    $eventExpenses = isset($seriesEvent) ? $seriesEvent->getEventExpenseEntries() : $topicTypeObj->getEventExpenseEntries();
    ?>


    <?php if(empty($eventExpenses)) { ?>
        <div class="approval-heading">
            <strong><?=sprintf(gettext('%s Budget'), $topicTypeLabel)?>: </strong>
            [<?=gettext("Not set")?>]
        </div>
    <?php } else { ?>
       <div class="approval-heading">
           <strong><?=sprintf(gettext('%s Budget'), $topicTypeLabel)?>: </strong>
           <button class="btn-link btn-no-style add-expense-open-close-js" name="event_expenses" id="event_expenses">[<?=gettext("View")?>]</button>
       </div>
        <?php foreach ($eventExpenses as $eventExpense) {
            $usesid = $eventExpense['usesid'];
            $sub = Budget2::GetBudgetUsesItems($usesid); 
            $expenseEntryObj = ExpenseEntry::Hydrate($usesid, $eventExpense);
            ?>

    <div>
        <div class="approval-section event-expenses-details" style="display: none;">
            <div class="p-3 my-3 " style="background-color: #f8f8f8;">
            <p>
                <strong><?=gettext("Expense Description")?>:</strong> <?= $eventExpense['description'] ?><br>
                <strong><?=$_COMPANY->getAppCustomization()['group']['name']?>:</strong> <?= $eventExpense['groupname'] . ($eventExpense['chaptername'] ? (' > '. $eventExpense['chaptername']) : '')  ?><br>
                <strong><?=gettext("Total Budget Amount")?>:</strong> <?= $_COMPANY->getCurrencySymbol($eventExpense['zoneid']) . $_USER->formatAmountForDisplay($eventExpense['budgeted_amount']) ?><br>
                <strong><?=gettext("Total Amount Used")?>:</strong> <?= $_COMPANY->getCurrencySymbol($eventExpense['zoneid']) . $_USER->formatAmountForDisplay($eventExpense['usedamount']) ?><br>
                <strong><?=gettext("Vendor Name")?>:</strong> <?= $eventExpense['vendor_name'] ?><br>
                <strong><?=gettext("Use Date")?>:</strong> <?= $eventExpense['date'] ?><br>
                <strong><?=gettext("Entry Create Date")?>:</strong> <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($eventExpense['createdon']) ?><br>

                <?= $expenseEntryObj->renderCustomFieldsComponent('v7') ?>
            </p>

            <?php if (!empty($sub)) { ?>
            <strong><?=gettext("Budget/Expense Items")?>:</strong><br>
            <table class="table table-bordered display bg-white">
                <thead>
                    <tr>
                        <th class="p-1"><?=gettext("Expense Type")?></th>
                        <th class="p-1"><?=gettext("Expense Item")?></th>
                        <th class="p-1"><?=gettext("Budgeted Amount")?></th>
                        <th class="p-1"><?=gettext("Expense Cost")?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sub as $expense) { ?>
                        <tr>
                            <td class="p-1"><?= $expense['expensetype'] ?></td>
                            <td class="p-1"><?= htmlspecialchars($expense['item']) ?></td>
                            <td class="p-1"><?= (isset($expense['item_budgeted_amount'])) ? $_COMPANY->getCurrencySymbol() . $_USER->formatAmountForDisplay($expense['item_budgeted_amount']) : '' ?></td>
                            <td class="p-1"><?= (isset($expense['item_used_amount'])) ? $_COMPANY->getCurrencySymbol() . $_USER->formatAmountForDisplay($expense['item_used_amount']) : '' ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <?php } ?>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php } ?>