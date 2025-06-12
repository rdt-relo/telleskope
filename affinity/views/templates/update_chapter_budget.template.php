<!-- Chapter Budget Modal -->
<style>
    .borderless {
        border: none;
    }
    .group-bdgt {
        height: 30px !important;
    }
    .chapter-card-body {
        padding: .25rem !important;;
    }
</style>
<?php
$chapterOrChannelLabel = $_COMPANY->getAppCustomization()['chapter']['enabled'] ? $_COMPANY->getAppCustomization()['chapter']['name-short'] : '';
?>
<div id="chapterBudgetModal" class="modal fade">
    <div aria-label="<?= $group->val('groupname')?> <?= gettext("Budget Allocation");?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title" class="modal-title" style="text-align:center;"> <?= $group->val('groupname')?> <?= gettext("Budget Allocation");?></h2>
                <button aria-label="<?= gettext("close");?>" type="button" id="btn_close" class="close"  data-dismiss="modal" onclick="manageBudgetExpSection('<?= $_COMPANY->encodeId($groupBudgetObj->val('groupid')); ?>');">×</button>
            </div>
            <p role="heading" aria-level="3" class="mt-2 ml-3" style="font-size: larger;" > <?= gettext("Budget Period");?>: <strong><?= htmlspecialchars($selectedBudgetYear['budget_year_title']) ?> </strong></p>
            <div class="modal-body">
                <div class="container">
                    <div class="row row-no-gutters mb-2">
                        <div class="col-md-8">
                            <strong> <?= gettext("Total Budget");?></strong> <span style="color:grey">[T]</span>
                            <i class="fa fa-question-circle" data-toggle="tooltip" title="Total budget set for the year"></i>
                        </div>
                        <div class="col-md-4">
                            <strong>
                            <?= $_COMPANY->getCurrencySymbol();?> <span id="t-amt"><?= $_USER->formatAmountForDisplay($groupBudgetObj->getTotalBudget()) ?></span>
                            </strong>
                        </div>
                        <div class="col-md-8">
                        &emsp;<?= sprintf(gettext("Budget allocated to %s expenses"),$_COMPANY->getAppCustomization()['group']['name-short']);?> <span style="color:grey">[E]</span>
                        <i class="fa fa-question-circle" data-toggle="tooltip" title="<?= sprintf(gettext("Budget expensed for this %s events and other expenses"),$_COMPANY->getAppCustomization()['group']['name-short']);?>"></i>
                        </div>
                        <div class="col-md-4">
                        <?= $_COMPANY->getCurrencySymbol();?> <span id="assigned-bgt"><?= $_USER->formatAmountForDisplay($groupBudgetObj->getTotalExpenses()['spent_from_allocated_budget']) ?></span>
                        </div>
                        <div class="col-md-8">
                        &emsp;<?= sprintf(gettext("Budget allocated to this %s chapters"),$_COMPANY->getAppCustomization()['group']['name-short-plural']);?> <span style="color:grey">[G]</span>
                        <i class="fa fa-question-circle" data-toggle="tooltip" title="<?= sprintf(gettext("Budget allocated to this %s chapters. The allocation can be changed below."),$_COMPANY->getAppCustomization()['group']['name-short-plural']);?>"></i>
                        </div>
                        <div class="col-md-4">
                        <?= $_COMPANY->getCurrencySymbol();?> <span id="allocated-bgt"><?= $_USER->formatAmountForDisplay($groupBudgetObj->getTotalBudgetAllocatedToSubAccounts()) ?></span>
                        </div>
                        <div class="col-md-8">
                        &emsp;<?= gettext("Budget available for allocation");?> <span style="color:grey">[A = T-(E+G)]</span>
                        <i class="fa fa-question-circle" data-toggle="tooltip" title="<?= sprintf(gettext("Budget available for allocation to this %s events, expenses or chapters."),$_COMPANY->getAppCustomization()['group']['name-short-plural']);?>"></i>
                        </div>
                        <div class="col-md-4">
                        <?= $_COMPANY->getCurrencySymbol();?> <span id="remained-bgt"><?= $_USER->formatAmountForDisplay($groupBudgetObj->getTotalBudgetAvailable()) ?></span>
                        </div>
                    </div>

                    <form id="group-budget" class="form-horizontal" method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                        <input type="hidden" id="year" value="<?= $year;?>">
                        <table class="table table-sm display" style="color: #505050;" summary="This table display the list of allocated and spent budgets">
                            <tr class="borderless">
                                <th width="30%" scope="col"><?= $chapterOrChannelLabel?></th>
                                <th width="20%" scope="col">
                                    <?= gettext("Used");?>
                                    <i class="fa fa-question-circle" data-toggle="tooltip" title="<?= sprintf(gettext("Budget expensed for %s events or expenses"),$chapterOrChannelLabel);?>"></i>
                                </th>
                                <th width="30%" scope="col">
                                    <?= gettext("Total");?>
                                    <i class="fa fa-question-circle" data-toggle="tooltip" title="<?= sprintf(gettext("Total Budget assigned to the %s"),$chapterOrChannelLabel);?>"></i>
                                </th>
                                <th width="20%" scope="col">&nbsp;</th>
                            </tr>
                        <?php
                        $c = 0;
                        foreach ($groupBudgetObj->getChildBudgets() as $chBudgetObject) {
                            $c++;
                        ?>
                            <tr class="borderless">
                                <td>
                                    <span <?= $chBudgetObject->isActive() ? '' : 'style="color:red;"'?> ><?= $chBudgetObject->val('budget_name'); ?></span>
                                </td>
                                <td>
                                    <span id="t-amt"><?= $_USER->formatAmountForDisplay($chBudgetObject->getTotalExpenses()['spent_from_allocated_budget']) ?></span></td>
                                <td>
                                    <input type="number" name="chapterbudgetamt" id="chapterbudgetamt<?= $c; ?>" class="form-control group-bdgt" value="<?= $_USER->formatAmountForDisplay($chBudgetObject->getTotalBudget(),'') ?>" placeholder="" >
                                </td> 

                                <td>
                                    <button onclick="updateGroupChapterBudget(<?=$c?>, '<?= $_COMPANY->encodeId($chBudgetObject->val('groupid')) ?>','<?= $_COMPANY->encodeId($chBudgetObject->val('chapterid')) ?>')" id="chapterbudgetamt<?= $c; ?>-btn" type="button" name="submit"  class="btn btn-sm btn-primary req-up-bt chapter-disable-update-button deluser">
                                        <?= gettext("Update");?>
                                    </button>
                                    <span id="hidechaptermesage<?= $c; ?>" style="color:#63c375; display:none;">&emsp;<?= gettext("Updated");?></span>
                                </td>
                            </tr>
                            <?php   } ?>
                        </table>
                        <div class="text-center">
                            <button type="button" onclick="manageBudgetExpSection('<?= $_COMPANY->encodeId($groupBudgetObj->val('groupid')); ?>');" class="btn req-up-bt"
                                style="width:100px;" data-dismiss="modal"><?= gettext("Close");?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            
            var $button = $('.chapter-disable-update-button');
            $button.prop('disabled', true);
            $("input").focus(function () {
                $button.prop('disabled', true);
                var id = $(this).attr('id');
                $("#" + id + "-btn").prop('disabled', false);
            });
            // $(".deluser").popConfirm({content: ''});
        });
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        })
    </script>
</div>
<script>
$('#chapterBudgetModal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus')
});
</script>