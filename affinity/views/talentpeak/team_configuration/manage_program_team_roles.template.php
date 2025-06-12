<div class="row mb-4">
    <div class="col-md-12 mb-4 mt-3 pl-0">
    <h3><?= sprintf(gettext("Manage %s Role Types"), Team::GetTeamCustomMetaName($group->getTeamProgramType()))?></h3>
    <?php if($showAddRoleButton){ ?>
    <button aria-label="<?= gettext('Add new role type'); ?>" id="AddUpdateProgramRole" class="btn-no-style px-1" onclick="showAddUpdateProgramRoleModal('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId(0); ?>')" title="<?= gettext('Add new role type'); ?>">
        <i class="fa fa-plus-circle add-plus-icon"></i>
    </button>
    <?php } ?>
</div>
    <div class="table-responsive " id="list-view">
        <table id="team_role_table" class="table table-hover display compact" width="100%" summary="<?= gettext("This table shows the list of team role types"); ?>">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col"><?= gettext("Role Type"); ?></th>
                    <th scope="col"><?= gettext("System Role Type"); ?></th>
                    <th scope="col"><?= gettext("Minimum Members Required"); ?></th>
                    <th scope="col"><?= gettext("Maximum Members Allowed"); ?></th>
                    <th scope="col"><?= gettext("Role Capacity"); ?></th>
                    <th scope="col"><?= gettext("Registration Start / End Date"); ?></th>
                    <th scope="col"><?= gettext("Role Used By"); ?></th>
                    <th scope="col"><?= gettext("Action"); ?></th>
                </tr>
            </thead>
            <tbody>
        <?php $i = 0;
            foreach($data as $row){
                $roleUsedBy = Team::GetRoleUsesStat($row['groupid'],$row['roleid']);
            ?>
                <tr id="e<?= ($i+1); ?>" <?= ($row['isactive'] == 1)?'':'style="background-color: rgb(251, 199, 199); opacity: 0.5;"' ?> >
                    <td class="text-center"><?= ($i+1); ?></td>
                    <td ><?= $row['type']; ?></td>
                    <td ><?= Team::SYS_TEAMROLE_TYPES["{$row['sys_team_role_type']}"] ?></td>
                    <td class="text-center" ><?= $row['min_required'] ?? ''; ?></td>
                    <td class="text-center" ><?= $row['max_allowed'] ?? ''; ?></td>
                    <td class="text-center" ><?= $row['role_capacity']==0 ? gettext('Unlimited') : ($row['role_capacity'] ?? '') ?></td>
                    <td ><?= $row['registration_start_date'] ? $row['registration_start_date'] : 'NA'; ?> / <?= $row['registration_end_date'] ? $row['registration_end_date'] :  'NA'; ?></td>
                    <td >Members: <?= $roleUsedBy['totalMembersByRole']; ?><br>Registrations: <?= $roleUsedBy['totalMemberRequestsByRole']; ?></td>
                    <td>

                    <?php if($row['isactive'] == 1){ ?>
                        <button aria-label="<?= sprintf(gettext("Edit %s Role Types"), $row['type'])?>" title="<?= gettext('Edit');?>" class="btn-no-style" onclick="showAddUpdateProgramRoleModal('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($row['roleid']); ?>')"><i class="fa fa-edit" ></i></button>&nbsp;
                        <button aria-label="<?= sprintf(gettext("Disable %s Role Types"), $row['type'])?>" class="deluser btn-no-style"  data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" onclick="enableDisableTeamRoleType('<?= $_COMPANY->encodeId($groupid); ?>','<?=$_COMPANY->encodeId($row['roleid'])?>',100,<?= $roleUsedBy['totalMembersByRole']+$roleUsedBy['totalMemberRequestsByRole']; ?>)" title="<strong><?= gettext('Are you sure you want to Disable!'); ?></strong>"  ><i class="fa fa-trash" aria-hidden="true" title="<?= gettext('Disable');?>"></i></button>
                    <?php } else { ?>
                        <button aria-label="<?= sprintf(gettext("Disable %s Role Types"), $row['type'])?>" class="deluser btn-no-style" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" onclick="enableDisableTeamRoleType('<?= $_COMPANY->encodeId($groupid); ?>','<?=$_COMPANY->encodeId($row['roleid'])?>', 1,<?= $roleUsedBy['totalMembersByRole']+$roleUsedBy['totalMemberRequestsByRole']; ?>)" title="<strong><?= gettext('Are you sure you want to Enable'); ?>?</strong>"  ><i aria-hidden="true" class="fa fa-undo" title="<?= gettext('Enable');?>"></i></button>
                    <?php } ?>
                    </td>
                </tr>
                <?php $i++; } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
	$(document).ready(function() {
        var dtable = $('#team_role_table').DataTable( {
            scrollX: true,
			"order": [],
			"bPaginate": true,
            "language": {
                "sZeroRecords": "<?= gettext('No data available in table');?>",
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },
			"bInfo" : false,
            "columnDefs": [
                { "targets": [8], "orderable": false }
            ]
				
		});
        $(".deluser").popConfirm({content: ''});

        // function for Accessiblity screen reading.
        screenReadingTableFilterNotification('#team_role_table',dtable);
	});

    function enableDisableTeamRoleType(g,i,s,c){
        if (c != 0  && s == 100){
            swal.fire({text:"<?= gettext("This role cannot be removed because it's already in use. Please remove all assignments from this role to proceed with the deletion"); ?>"})
        } else{
            $.ajax({
                type: "POST",
                url: "ajax_talentpeak.php?enableDisableTeamRoleType=1",
                data: {'groupid':g,'id':i,'status':s},
                success: function(data){
                    try {
                        let jsonData = JSON.parse(data);
                        swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
                            if (jsonData.status == 1){
                                manageProgramTeamRoles(g);
                            } 
                        });
                    } catch(e) { 
                        swal.fire({title: 'Error', text: "Unknown error."}); 
                    }
                }
            });
        } 

        setTimeout(() => {
			    $(".swal2-confirm").focus();
		}, 500)
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
