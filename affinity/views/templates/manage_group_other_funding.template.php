<div id="otherFundingModal" class="modal fade">
    <div aria-label="<?= sprintf(gettext("Manage funding for %s"), $group->getFromEmailLabel($chapterid));?>" class="modal-dialog modal-xl modal-dialog-w1000" aria-modal="true" role="dialog" style="min-width:70%;">
        <!-- Modal content -->
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title" class="modal-title"><?= sprintf(gettext("Manage funding for %s"), $group->getFromEmailLabel($chapterid));?></h2>
                <button type="button" id="addEditOtherBudgetModalButton"  class="btn btn-affinity" onclick="addEditOtherBudgetModal('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($chapterid); ?>','<?= $_COMPANY->encodeId(0); ?>')" ><?= gettext("Add Other Funding")?></button>
            </div>
            <div class="modal-body">
				<div class="col-md-12">
                    <div class="table-responsive">
                        <table id="otherFundingTable" class="table table-hover display compact" summary="This table displays the other funding list" width="100%">
                            <thead>
                                <tr>
                                    <th width="15%" scope="col"><?=$_COMPANY->getAppCustomization()['group']['name-short']?></th>
                                    <?php  if ($_COMPANY->getAppCustomization()['chapter']['enabled']){ ?>
                                    <th width="15%" scope="col"><?=$_COMPANY->getAppCustomization()['chapter']['name-short']?></th>
                                    <?php } ?>
                                    <th width="10%" class="color-black" scope="col"><?= gettext("Budget Year");?></th>
                                    <th width="10%" class="color-black" scope="col"><?= gettext("Funding Date");?></th>
                                    <th width="10%" class="color-black" scope="col"><?= gettext("Funding Source");?></th>
                                    <th width="10%" class="color-black" scope="col"><?= gettext("Funding Amount");?></th>
                                    <th width="15%" class="color-black" scope="col"><?= gettext("Description");?></th>
                                    <th width="15%" class="color-black" scope="col"><?= gettext("Action");?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $x = 0; foreach($otherFunds as $f){ 
                                    $groupName = Group::GetGroupName($f['groupid']);
                                    $chapterName = "-";
                                    if($f['chapterid']){
                                        $chapterName = Group::GetChapterName($f['chapterid'],$f['groupid'])['chaptername'];
                                    }
                                
                                ?>
                                <tr id="s<?= $x+1; ?>">
                                    <td><?= $groupName; ?></td>
                                    <?php  if ($_COMPANY->getAppCustomization()['chapter']['enabled']){ ?>
                                    <td><?= $chapterName; ?></td>
                                    <?php } ?>
                                    <td><?= htmlspecialchars($f['budget_year_title']); ?></td>
                                    <td><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($f['funding_date'],true,false,true);?></td>
                                    <td><?= $f['funding_source']; ?></td>
                                    <td><?= $_COMPANY->getCurrencySymbol().number_format($f['funding_amount'],2) ?></td>
                                    <td><?= $f['funding_description']; ?></td>
                                    <td>
                                        <div class="" style="color: #fff; float: left;">
                                            <button aria-label=" <?= gettext("budget year").' '. $f['budget_year_title'] .' '. gettext("Action");?>" class="dropdown-toggle btn btn-affinity" data-toggle="dropdown"><?= gettext('Action'); ?> <span class="caret" style="font-size: x-small;">&nbsp;&#9660;</span></button>
                                            <ul class="dropdown-menu dropdown-menu-right" style="width: 180px; cursor: pointer;">
                                                <li><a aria-label="<?= gettext("Edit");?>" href="javascript:void(0)" class="" onclick="addEditOtherBudgetModal('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($f['chapterid']) ?>','<?= $_COMPANY->encodeId($f['funding_id']) ?>')"><i class="fa fas fa-edit"></i>&emsp;<?= gettext('Edit'); ?></a></li>
                                                <li><a aria-label="<?= gettext("Delete");?>" href="javascript:void(0)" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>"  title="<?= gettext('Are you sure you want to delete this fund?'); ?>" onclick="deleteGroupOtherFund('<?= $x+1; ?>','<?= $_COMPANY->encodeId($f['funding_id']) ?>')" ><i class="fa fa-trash"></i>&emsp;<?= gettext('Delete'); ?></a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php $x++; } ?>	
                            </tbody>										
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer text-center">
                <button id="btn_close2" type="button" class="btn btn-affinity" onclick="manageBudgetExpSection('<?= $_COMPANY->encodeId($groupid); ?>')" data-dismiss="modal"><?= gettext("Close");?></button>
              </div>
        </div>
    </div>
</div>
<script>
	$(document).ready(function() {
        var x = localStorage.getItem("local_variable_for_table_pagination"); 
        var targetList = [6,7];      
        <?php 
        if (!$_COMPANY->getAppCustomization()['chapter']['enabled']){ ?>
            targetList = [6,7];
        <?php }  ?>
		var dtable = $('#otherFundingTable').DataTable( {
			"order": [],
			"bPaginate": true,
			"bInfo" : false,
            pageLength:x,
            'language': {
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },
            columnDefs: [
                { targets: targetList, orderable: false }
            ],
			
		});
        screenReadingTableFilterNotification('#otherFundingTable',dtable);
	});    

    $('#otherFundingTable').on( 'length.dt', function ( e, settings, len ) {
    localStorage.setItem("local_variable_for_table_pagination", len);
} );
    $('#otherFundingModal').on('shown.bs.modal', function () {
        $('#addEditOtherBudgetModalButton').trigger('focus')
    });

</script>


