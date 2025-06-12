<div class="row mb-4">
    <div class="col-md-12 mb-4 mt-3 pl-0">
    <?php if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?> 
        <h3><?= sprintf(gettext("%s Touch Point Templates"), Team::GetTeamCustomMetaName($group->getTeamProgramType()));?></h3>
    
        <button id="add_update_program_touch_point" class="btn-no-style px-1" title="<?= gettext("Add new touch point")?>" onclick="showAddUpdateProgramTouchPointTemplateModal('<?= $_COMPANY->encodeId($groupid); ?>')">
        <i class="fa fa-plus-circle add-plus-icon"></i>
        </button>
    <?php } ?>

    <?php if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?> 
        <button onclick="openTouchpointConfigurationModal('<?= $_COMPANY->encodeId($groupid );?>')" class="btn btn-affinity pull-right">
            <?= gettext("Configuration"); ?>
        </button>

    <?php } ?>

    
    </div>
    <div class="table-responsive " id="list-view">
        <table id="team_touchoint_template_table" class="table table-hover display compact" summary="This table shows the list of team role types">
            <thead>
                <tr>
                    <th width="50%" scope="col"><?= gettext("Title"); ?></th>
                    <th width="30%" scope="col"><?= gettext("Turnaround&nbsp;Days"); ?></th>
                    <th width="20%" scope="col"><?= gettext("Action"); ?></th>
                </tr>
            </thead>
            <tbody>
        <?php $i = 0;
            foreach($touchpoints as $row){ ?>
                <tr id="id<?= $i; ?>")>
                   <td ><?= $row['title']; ?></td>
                    <td ><?= $row['tat']; ?></td>
                    <td>
                        <button title="view" class="btn-no-style" onclick="viewTodoOrTouchPointTemplateDetail('<?= $_COMPANY->encodeId($groupid); ?>','<?=$_COMPANY->encodeId($i); ?>','<?=$_COMPANY->encodeId(1); ?>');"><i class="fa fa-eye"></i></button>&nbsp;
                        <button title="Edit" class="btn-no-style" onclick="showAddUpdateProgramTouchPointTemplateModal('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($i)?>')" ><i class="fa fa-edit"></i></button>&nbsp;
                        <button aria-label="<?= sprintf(gettext("Delete %s "), $row['title']);?>" class="deluser btn-no-style" onclick="deleteTeamTouchPointTemplate('<?= $_COMPANY->encodeId($groupid); ?>','<?=$_COMPANY->encodeId($i); ?>')" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<strong><?= gettext('Are you sure you want to Delete?')?></strong>" ><i aria-hidden="true" class="fa fa-trash"  title="Delete"></i></button>
                    </td>
                </tr>
            <?php
                $i++;
                    }
             ?>
            </tbody>
        </table>
    </div>
</div>

<script>
	$(document).ready(function() {
		$('#team_touchoint_template_table').DataTable( {
			"order": [[3,"asc"]],
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

    function deleteTeamTouchPointTemplate(g,i){
        $.ajax({
            type: "POST",
            url: "ajax_talentpeak.php?deleteTeamTouchPointTemplate=1",
            data: {'groupid':g,'id':i},
            success: function(data){
                $('#touchpointstab').trigger('click');
                swal.fire({title: 'Success',text:"Deleted successfully."}).then(function(result) {
                    $('#add_update_program_touch_point').focus();
                    
                });
                
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
</script>

</body>
</html>
