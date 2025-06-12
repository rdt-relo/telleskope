<div class="row mb-4">
    <div class="col-md-12 mb-4 mt-3 pl-0">
    <?php if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?> 
        <h3><?= sprintf(gettext("%s Action Item Templates"), Team::GetTeamCustomMetaName($group->getTeamProgramType()));?></h3>
        <button aria-label="<?= sprintf(gettext("%s Action Item Templates"), Team::GetTeamCustomMetaName($group->getTeamProgramType()));?>" id="showAddUpdateProgram"  class="btn-no-style px-1" title="<?= gettext('Add new action item')?>" onclick="showAddUpdateProgramActionItemTemplateModal('<?= $_COMPANY->encodeId($groupid); ?>')">
            <i class="fa fa-plus-circle add-plus-icon"></i>
        </button>
    <?php } ?>

    <?php if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV'] && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES'] && $group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['NETWORKING']){ ?> 
        <button onclick="openActionConfigurationModal('<?= $_COMPANY->encodeId($groupid );?>')" class="btn btn-affinity pull-right">
            <?= gettext("Configuration"); ?>
        </button>

    <?php } ?>

</div>
<?php if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?> 
    <div class="table-responsive " id="list-view">
        <table id="team_todo_template_table" class="table table-hover display compact" summary="This table shows the list of team role types">
            <thead>
                <tr>
                    <th width="50%" scope="col"><?= gettext("Title");?></th>
                    <th width="15%" scope="col"><?= gettext("Assigned&nbsp;To");?></th>
                    <th width="20%" scope="col"><?= gettext("Turnaround Days");?></th>
                    <th width="15%" scope="col"><?= gettext("Action");?></th>
                </tr>
            </thead>
            <tbody>
        <?php $i = 0;
            foreach($actionItems as $row){ ?>
                <tr id="id<?= $i; ?>")>
                    <td ><?= $row['title']; ?></td>
                    <td ><?= $row['assignedto']>0 ? ((Team::GetTeamRoleType($row['assignedto']))['type'] ?: '-' ) : '-'; ?></td>
                    <td ><?= $row['tat']??0; ?></td>
                    <td>
                <button aria-label="<?= sprintf(gettext("View %s "), $row['title']);?>" class="btn-no-style" onclick="viewTodoOrTouchPointTemplateDetail('<?= $_COMPANY->encodeId($groupid); ?>','<?=$_COMPANY->encodeId($i); ?>','<?=$_COMPANY->encodeId(2); ?>');" title="<?= gettext('View');?>"><i class="fa fa-eye"></i></button>&nbsp;
                        <button aria-label="<?= sprintf(gettext("Edit %s "), $row['title']);?>" title="<?= gettext('Edit');?>" class="btn-no-style" onclick="showAddUpdateProgramActionItemTemplateModal('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($i)?>')" ><i class="fa fa-edit" ></i></button>&nbsp;
                        <button aria-label="<?= sprintf(gettext("Delete %s "), $row['title']);?>" class="deluser btn-no-style" onclick="deleteTeamActionIteamTemplate('<?= $_COMPANY->encodeId($groupid); ?>','<?=$_COMPANY->encodeId($i); ?>')" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<strong><?= gettext('Are you sure you want to Delete?')?></strong>" ><i aria-hidden="true" class="fa fa-trash" title="<?= gettext('Delete');?>"></i></button>
                    </td>
                </tr>
            <?php
                $i++;
                    }
             ?>
            </tbody>
        </table>
    </div>
<?php }  else { ?>

    <div class="col-12 py-5 text-center">

         <?= sprintf(gettext('Action Items template feature is not available for %1$s %2$s type, however, %1$s members can still create their own action items within their active %1$s.'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),  $_COMPANY->getAppCustomization()['group']['name-short'] )?> 

    </div>


<?php } ?>
</div>

<script>
	$(document).ready(function() {
		$('#team_todo_template_table').DataTable( {
            "order": [[4,"asc"]],
			"bPaginate": true,
            "columnDefs": [
                       { targets: [-1], orderable: false }
                    ],
            "language": {
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },	
			"bInfo" : false
				
		});
	});

    function deleteTeamActionIteamTemplate(g,i){
        $.ajax({
            type: "POST",
            url: "ajax_talentpeak.php?deleteTeamActionIteamTemplate=1",
            data: {'groupid':g,'id':i},
            success: function(data){
                $('#actionItemtab').trigger('click');
                swal.fire({title: "<?= gettext('Success'); ?>",text:"<?= gettext('Deleted successfully');?>"}).then(function(result) {
                    
                    $('#showAddUpdateProgram').focus();
                });
            }
        });
        
    }

    function openActionConfigurationModal(g){
        $.ajax({
            url: 'ajax_talentpeak.php?openActionConfigurationModal=1',
            type: "GET",
            data: {'groupid':g},
            success: function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title,text:jsonData.message});
                } catch(e) { 
                    $("#loadAnyModal").html(data);
                    $('#actionItemConfigModal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                }
            }
        });
    }

//On Enter Key...
$(function(){
        $(".add-plus-icon").keypress(function (e) {
            if (e.keyCode == 13) {
                $(this).trigger("click");
            }
        });
    });

$(document).on('show.bs.popover', function(e) { 
    setTimeout(() => {
        $('.confirm-dialog-btn-abort').focus(); 
}, 100);
});
</script>

</body>
</html>
