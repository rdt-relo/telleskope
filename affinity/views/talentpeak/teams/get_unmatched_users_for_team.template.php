<?php
    $teamCustomName = Team::GetTeamCustomMetaName($group->getTeamProgramType());
?>
<style>
.manage-team-table ul {
    padding-left: 0px;
}
</style>
<div class="row">
            <div class="col-md-4">
                 <div class="form-group col-md-12 ">
                    <label for="FilterByRegistrationType" style="font-size:small;">Filter By Registration Type</label>
                    <select class="form-control" onchange="getUnmatchedUsersForTeam('<?= $_COMPANY->encodeId($groupid); ?>',this.value,'<?= $teamFiltersValue;?>');" id="FilterByRegistrationType" style="font-size:small;border-radius: 5px;">
                    <option value="">Select Registration Type</option>
                    <?php

                    	$data = Team::GetProgramTeamRoles($groupid,1); //active roles only
                        foreach($data as $row){  ?>

                          <option  <?=($registrationType == $_COMPANY->encodeId($row['roleid']))?'selected':'';?>  value="<?= $_COMPANY->encodeId($row['roleid']); ?>"><?= $row['type']; ?></option>
                    <?php } ?>

                    </select>
                  </div>
            </div>
            <div class="col-md-4">
                <div class="form-group col-md-12 ">
                    <label for="FilterByNumberofAssignedTeams" style="font-size:small;"><?= sprintf(gettext("Filter By Assigned %s Status"), $teamCustomName)?></label>
                    <select id="FilterByNumberofAssignedTeams" onchange="getUnmatchedUsersForTeam('<?= $_COMPANY->encodeId($groupid); ?>','<?= $registrationType;?>',this.value);" style="font-size:small;border-radius: 5px;" class="form-control">
                        <option  value=""><?= sprintf(gettext("Select Assigned %s Status"), $teamCustomName);?></option>
                        <option <?= ($teamFiltersValue == 'unassigned') ? 'selected' : '';?> value="unassigned"><?= sprintf(gettext("%s Not Assigned"), $teamCustomName);?></option>
                        <option <?= ($teamFiltersValue == 'assigned') ? 'selected' : '';?> value="assigned"><?= sprintf(gettext("%s Assigned"), $teamCustomName);?></option>
                        <option <?= ($teamFiltersValue == 'complete') ? 'selected' : '';?> value="complete"><?= sprintf(gettext("%s Complete"), $teamCustomName);?></option>
                        <option <?= ($teamFiltersValue == 'incomplete') ? 'selected' : '';?> value="incomplete"><?= sprintf(gettext("%s Incomplete"), $teamCustomName);?></option>
                    </select>
                </div>
        </div>
</div>

<div class="col-md-12 mt-3 px-0">
    <div class="table-responsive" id="eventTable">
        <table id="unmatched_users_list" class="table display manage-team-table table-hover compact" summary="This table display the list of unmatched users">
            <thead>
              <tr>
                <th width="<?= $_ZONE->val('app_type') === 'peoplehero' ? '20%' : '25%' ?>" class="color-black" scope="col"><?= gettext("User");?></th>
                <th width="<?= $_ZONE->val('app_type') === 'peoplehero' ? '20%' : '25%' ?>" class="color-black" scope="col">
                    <?= gettext('Role') . '<br><small>(' . gettext('Used/Requested Capacity') . ')</small>';?>
                <?php if($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER']){ ?> 
                    <?= '<br><small>(' . gettext('Pending Sent/Received Requests') . ')</small>';?>
                <?php } ?>
                </th>
                <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
                <th width="15%" class="color-black js-chapter-col" scope="col"><?= $_COMPANY->getAppCustomization()['chapter']['name-short-plural'];?></th>
                <?php } ?>
                <?php if ($_ZONE->val('app_type') === 'peoplehero') { ?>
                <th width="10%" class="color-black" scope="col"><?= gettext('Start Date') ?></th>
                <?php } ?>
                <th width="25%" class="color-black" scope="col"><?= Team::GetTeamCustomMetaName($group->getTeamProgramType());?></th>
                <th width="10%" class="color-black" scope="col"><?= gettext("Action");?></th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
    </div>
</div>
   
<!-- Cancel Email Modal -->
<div class="modal fade" id="cancelEmailModal" tabindex="-1" role="dialog" aria-labelledby="cancelEmailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cancelEmailModalLabel"><?= gettext("Cancel Registration"); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <div class="form-group">
          <p><?=gettext('Would you like to send an email?')?></p>
          <label>
              <input type="radio" name="cancel-email-option" value="no_email" checked> <?=gettext('No email')?>
          </label>
              &emsp;&emsp;
          <label>
              <input type="radio" name="cancel-email-option" value="send_email"> <?=gettext('Send email')?>
          </label>
          </div>
          <div id="cancelRegistrationEmailFields" style="display:none;">
            <div class="form-group">
              <label for="cancel-email-subject"><?= gettext("Email Subject"); ?></label>
              <input type="text" class="form-control" id="cancel-email-subject">
            </div>
            <div class="form-group">
              <label for="cancel-email-body"><?= gettext("Email Message"); ?></label>
              <textarea class="form-control" id="cancel-email-body" rows="6"></textarea>
            </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="send-cancel-email"><?= gettext("Cancel Registration") ?></button>
        <button type="button" class="btn btn-secondary" id="cancel-modal-close" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
var x = parseInt(localStorage.getItem("local_variable_for_table_pagination"));
var dtable = $('#unmatched_users_list').DataTable( {
            serverSide: true,
            processing: true,
            bFilter: true,
            bInfo : false,
            bDestroy: true,
            pageLength:x,
            order: [[ 0, "DESC" ]],
            language: {
                    searchPlaceholder: "...",
                    url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
                },
            columnDefs: [
            <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
                { targets: ['.js-chapter-col',-1], orderable: false }
            <?php } else { ?>
                { targets: [-1], orderable: false }
            <?php } ?>
                ],
            ajax:{
                url :"ajax_talentpeak.php?getUnmatchedUsersJoinRequests=<?= $_COMPANY->encodeId($groupid); ?>&registrationType=<?=$registrationType;?>&teamsFilterValue=<?= $teamFiltersValue;?>", // json datasource
                type: "POST",  // method  , by default get
                error: function(data){  // error handling
                    $(".table-grid-error").html("");
                    $("#table-members-server").append('<tbody class="table-grid-error"><tr><th colspan="6"><?= gettext("No data found");?>!</th></tr></tbody>');
                    $("#table-grid_processing").css("display","none");
                },complete : function(){
                    $('.initial').initial({
                        charCount: 2,
                        textColor: '#ffffff',
                        color: window.tskp?.initial_bgcolor ?? null,
                        seed: 0,
                        height: 30,
                        width: 30,
                        fontSize: 15,
                        fontWeight: 300,
                        fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
                        radius: 0
                    });
                    $(".confirm").popConfirm({content: ''});
                }
            },

        } );

    screenReadingTableFilterNotification('#unmatched_users_list',dtable);

    $('#unmatched_users_list').on('draw.dt', function () { 
				$('.initial').initial({
					charCount: 2,
					textColor: '#ffffff',
                    color: window.tskp?.initial_bgcolor ?? null,
					seed: 0,
					height: 50,
					width: 50,
					fontSize: 20,
					fontWeight: 300,
					fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
					radius: 0
				});
			});

      $('#unmatched_users_list').on( 'length.dt', function ( e, settings, len ) {
          localStorage.setItem("local_variable_for_table_pagination", len);
      } );

    function selfAssignTeamToUserForm(g,u,r) {
        $.ajax({
            url: 'ajax_talentpeak.php?selfAssignTeamToUserForm=1',
            type: "GET",
            data: {'groupid':g,'menteeUserId':u, 'menteeRoleId':r},
            success : function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title:jsonData.title,text:jsonData.message});
                } catch(e) {
                    $("#loadAnyModal").html(data);
                    $('#team_assignment').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                }
            }
        });
    }
</script>