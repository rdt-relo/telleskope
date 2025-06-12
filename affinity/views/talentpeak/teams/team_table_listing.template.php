<style>
  .background0, .background100 {
    background-color: rgb(255, 233, 233) !important;
  }
  .background1,.background110,.background108, .background109 {
    background-color: none !important;
  }
  .background2{
    background-color: rgb(252, 252, 217) !important;
  }
  .manage-team-table ul {
    padding-left: 0px;
  }
  .manage-team-table a {
      text-decoration: underline;
  }
  .manage-team-table .show a {
      text-decoration: none;
  }
  
</style>
<?php
$teamCustomNamePlural = Team::GetTeamCustomMetaName($group->getTeamProgramType(),true);
?>

<div class="table-responsive " id="eventTable">
    <div class="form-group col-md-4 ">
        &nbsp;
    </div>
    <div class="form-group col-md-4 ">
        <label for="FilterByTeamStatus" style="font-size:small;"><?= sprintf(gettext("Filter By %s Status"), $teamCustomNamePlural)?></label>
        <select id="FilterByTeamStatus" onchange="filterTeamByStatus(this.value);" style="font-size:small;border-radius: 5px;" class="form-control">
          <option value="all" <?= $_SESSION['teamFilterActiveTab'] == 'all' ? 'selected' : ''; ?>><?= sprintf(gettext("All %s"), $teamCustomNamePlural);?></option>
            <option value="<?= $_COMPANY->encodeId(Team::STATUS_ACTIVE); ?>" <?= $_SESSION['teamFilterActiveTab'] == Team::STATUS_ACTIVE ? 'selected' : ''; ?>><?= sprintf(gettext("Active %s"), $teamCustomNamePlural);?></option>
            <option value="<?= $_COMPANY->encodeId(Team::STATUS_DRAFT); ?>" <?= $_SESSION['teamFilterActiveTab'] == Team::STATUS_DRAFT ? 'selected' : ''; ?>><?= sprintf(gettext("Draft %s"), $teamCustomNamePlural);?></option>
            <option value="<?= $_COMPANY->encodeId(Team::STATUS_INACTIVE); ?>" <?= $_SESSION['teamFilterActiveTab'] == Team::STATUS_INACTIVE ? 'selected' : ''; ?>><?= sprintf(gettext("In-Active %s"), $teamCustomNamePlural);?></option>
            <option value="<?= $_COMPANY->encodeId(Team::STATUS_COMPLETE); ?>" <?= $_SESSION['teamFilterActiveTab'] == Team::STATUS_COMPLETE ? 'selected' : ''; ?>><?= sprintf(gettext("Completed %s"), $teamCustomNamePlural);?></option>
            <option value="<?= $_COMPANY->encodeId(Team::STATUS_INCOMPLETE); ?>" <?= $_SESSION['teamFilterActiveTab'] == Team::STATUS_INCOMPLETE ? 'selected' : ''; ?>><?= sprintf(gettext("Incomplete %s"), $teamCustomNamePlural);?></option>
            <option value="<?= $_COMPANY->encodeId(Team::STATUS_PAUSED); ?>" <?= $_SESSION['teamFilterActiveTab'] == Team::STATUS_PAUSED ? 'selected' : ''; ?>><?= sprintf(gettext("Paused %s"), $teamCustomNamePlural);?></option>
        </select>
    </div>
    <div class="form-group col-md-4 ">
        &nbsp;
    </div>

    <table id="team_list" class="table display manage-team-table table-hover compact" summary="This table display the list of teams">
        <thead>
            <tr>
            <th width="15%" class="color-black" scope="col"><?= gettext("Title");?></th>
            <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
                <th width="15%" class="color-black" scope="col"><?= $_COMPANY->getAppCustomization()['chapter']['name-short'];?></th>
            <?php } ?>
            <th width="15%" class="color-black" scope="col"><?= gettext('Members') ?></th>
            <th width="15%" class="color-black" scope="col"><?= gettext('Last Activity') ?></th>
            <th width="10%" class="color-black" scope="col"><?= gettext('Program Feedback') ?></th>
            <th width="10%" class="color-black" scope="col"><?= gettext('Status') ?></th>
            <th width="20%" class="color-black" scope="col">
                <div class="">
                <select aria-label="<?= gettext("Select Bulk Action");?>" class="" id="updateBulkAction" onchange="teamBulkActionConfirmation(this.value)">
                    <option value=""><?= gettext("Select Bulk Action");?></option>
                    <option value="draft_to_active" ><?= gettext("Change all Draft to Active");?></option>
                    <option value="active_to_inactive" ><?= gettext("Change all Active to Inactive");?></option>
                    <option value="inactive_to_active" ><?= gettext("Change all Inactive to Active");?></option>
                    <option value="active_to_complete" ><?= gettext("Change all Active to Complete");?></option>
                    <option value="active_to_incomplete" ><?= gettext("Change all Active to Incomplete");?></option>
                    <option value="active_to_paused" ><?= gettext("Change all Active to Paused");?></option>
                    <option value="paused_to_active" ><?= gettext("Change all Paused to Active");?></option>
                    <option value="delete_all_draft" ><?= gettext("Delete all Draft");?></option>
                </select>
                </div>
            </th>
            </tr>
        </thead>
    </table>
</div>

<script>

  <?php
  $searchPlaceholder = gettext('Team name');
  $nonSearchablColumn = json_encode([1,5]);
  if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { 
    $searchPlaceholder = sprintf(gettext("Team name or %s"), $_COMPANY->getAppCustomization()['chapter']['name-short']);
    $nonSearchablColumn = json_encode([2,6]);
   } ?>

        var orderBy = 0;
        var x = parseInt(localStorage.getItem("local_variable_for_table_pagination")); 
        var reloadTriggeredManually = 0;  
        var dtable = $('#team_list').DataTable( {
            serverSide: true,
            processing: true,
            bFilter: true,
            bInfo : false,
            bDestroy: true,
            pageLength: x,
            order: [[ orderBy, "ASC" ]],
            language: {
                searchPlaceholder: " <?=$searchPlaceholder; ?>",
                url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },
            "columnDefs": [
                { "searchable": false, "targets": <?= $nonSearchablColumn; ?>, orderable: false }
            ],
            ajax:{
                url :"ajax_talentpeak.php?getTeamsTableList=<?= $_COMPANY->encodeId($groupid); ?>", // json datasource
                type: "POST",  // method  , by default get
                "data": function (d) {
                    if (reloadTriggeredManually) {
                        d.reloadData = 1; // Add flag indicating manual reload
                    }
                    d.statusFilter = $("#FilterByTeamStatus").val();
                },
                error: function(data){  // error handling
                    $(".table-grid-error").html("");
                    $("#table-members-server").append('<tbody class="table-grid-error"><tr><th colspan="6"><?= gettext("No data found");?>!</th></tr></tbody>');
                    $("#table-grid_processing").css("display","none");
                },complete : function(){
                    $(".confirm").popConfirm({content: ''});                    
                }
            }, 
            "stateSave": true
        });
        
        screenReadingTableFilterNotification('#team_list',dtable);

        // Create custom refresh button
        var refreshButton = $('<div class="col-12 mb-3 px-0"><p class="text-sm"><?= addslashes(gettext("Table data is cached for 5 minutes. Newly made changes will be reflected after the cache expires."))?> <button class="dt-reload-data-button btn btn-link text-sm"><i class="fas fa-sync-alt"></i> <?= addslashes(gettext("Clear Cache")); ?></button></p></div>');
        // Add click event to refresh button
        refreshButton.find('.dt-reload-data-button').on('click', function () {
          $("#hidden_div_for_notification").html('');
		      $("#hidden_div_for_notification").removeAttr('aria-live'); 

          reloadTriggeredManually = 1; // Set flag when refresh button is clicked
          dtable.ajax.reload(function () {
            reloadTriggeredManually = 0; // Reset flag after reload completes
          });

          $("#hidden_div_for_notification").attr({role:"status","aria-live":"polite"});
          document.getElementById('hidden_div_for_notification').innerHTML="<?= gettext('Loading') ?>"; 

        });
        // Insert the refresh button before the length control
        $('#eventTable').prepend(refreshButton);

        $(".dataTables_filter input")
        .unbind()
        .bind("input", function(e) {
            if(this.value.length >= 2 || e.keyCode == 13) {
                dtable.search(this.value).draw();
            }
            if(this.value == "") {
                dtable.search("").draw();
            }
            return;
        });

        $('#team_list').on( 'length.dt', function ( e, settings, len ) {
            localStorage.setItem("local_variable_for_table_pagination", len);
        });
        function filterTeamByStatus() {
          if (dtable) {
            dtable.ajax.reload();
          }
        }
    function teamBulkActionConfirmation(v){
      if (v){
        var message = "";
        if (v == "draft_to_active"){
          message = '<?= addslashes(sprintf(gettext('I understand this Bulk Update action will activate all draft %s which may also generate emails to members of all impacted %s. Type "I agree" below to provide your consent and proceed.'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),Team::GetTeamCustomMetaName($group->getTeamProgramType()))); ?>';
        } else if(v == "inactive_to_active"){
          message = '<?= addslashes(sprintf(gettext('I understand this Bulk Update action will activate all inactive %s which may also generate emails to members of all impacted %s. Type "I agree" below to provide your consent and proceed.'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),Team::GetTeamCustomMetaName($group->getTeamProgramType()))); ?>';
        } else if(v == "active_to_inactive"){
          message = '<?= addslashes(sprintf(gettext('I understand this Bulk Update action will deactivate all active %s which may also generate emails to members of all impacted %s. Type "I agree" below to provide your consent and proceed.'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),Team::GetTeamCustomMetaName($group->getTeamProgramType()))); ?>';
        } else if(v == "active_to_complete"){
          message = '<?= addslashes(sprintf(gettext('I understand this Bulk Update action will complete all active %s which may also generate emails to members of all impacted %s. Type "I agree" below to provide your consent and proceed.'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),Team::GetTeamCustomMetaName($group->getTeamProgramType()))); ?>';
        }else if(v == "active_to_incomplete"){
          message = '<?= addslashes(sprintf(gettext('I understand this Bulk Update action will mark incomplete all active %s which may also generate emails to members of all impacted %s. Type "I agree" below to provide your consent and proceed.'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),Team::GetTeamCustomMetaName($group->getTeamProgramType()))); ?>';
        }else if(v == "active_to_paused"){
          message = '<?= addslashes(sprintf(gettext('I understand this Bulk Update action will pause all active %s which may also generate emails to members of all impacted %s. Type "I agree" below to provide your consent and proceed.'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),Team::GetTeamCustomMetaName($group->getTeamProgramType()))); ?>';
        }else if(v == "paused_to_active"){
            message = '<?= addslashes(sprintf(gettext('I understand this Bulk Update action will activate all paused %s which may also generate emails to members of all impacted %s. Type "I agree" below to provide your consent and proceed.'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),Team::GetTeamCustomMetaName($group->getTeamProgramType()))); ?>';
        }else if (v == "delete_all_draft"){
          message = '<?= addslashes(sprintf(gettext('I understand this Bulk Delete action will delete all draft %s which may also generate emails to members of all impacted %s. Type "I agree" below to provide your consent and proceed.'),Team::GetTeamCustomMetaName($group->getTeamProgramType()),Team::GetTeamCustomMetaName($group->getTeamProgramType()))); ?>';
        }
        if (message){
          $("#confirmationMessage").html(message);
          $("#bulk_action").val(v);
          $("#confirmChange").val('');
          $('#confirmChangeModal').modal({
            backdrop: 'static',
            keyboard: false
          });
        }
      }
    }  
 </script>