
<div class="col-md-12 mt-3">            
    <div class="table-responsive " id="eventTable">
        <table id="received_requests" class="table display"summary="This  table display the list of sent team requests">
            <thead>
              <tr>
                  <th width="40%" class="color-black" scope="col"><?= gettext("Invited User");?></th>
                  <th width="30%" class="color-black" scope="col"><?= gettext("Invited Role");?></th>
                  <th width="20%" class="color-black" scope="col"><?= gettext("Status");?></th>
                  <th width="10%" class="color-black" scope="col"><?= gettext("Action");?></th>
              </tr>
            </thead>
            <tbody>
              <?php
                foreach($invitedLists as $item){
                  
                  $invitedUser = User::GetUser($item['receiverid']) ?? User::GetEmptyUser();
              ?>
                <tr>
                    <td>
                        <?= User::BuildProfilePictureImgTag($invitedUser->val('firstname'), $invitedUser->val('lastname'), $invitedUser->val('picture'), 'memberpic2', 'User Profile Picture', $invitedUser->id(), 'profile_full')?>
                        <span class="pt-2"><?=$invitedUser->getFullName();?></span>
                    </td>
                    <td>
                        <?= Team::GetTeamRoleType($item['receiver_role_id'])['type']; ?>
                    </td>
                    <td>
                        <?= Team::GetTeamRequestStatusLabel($item['status']); ?>
                    </td>
                    <td>
                    <div class="" style="color: #fff; float: left;">
                        <button id="actionBtn_<?= $_COMPANY->encodeId($item['team_request_id']); ?>" class="btn btn-sm btn-affinity dropdown-toggle" title="Action" type="button" data-toggle="dropdown">
                          <?= gettext('Action');?>&emsp;&#9662</button>
                        <ul class="dropdown-menu dropdown-menu-right" id="dynamicActionButton_<?= $_COMPANY->encodeId($item['team_request_id']); ?>" style="width: 250px; cursor: pointer;">
                        
                            <?php if ($invitedUser->val('userid') && $item['status'] == Team::TEAM_REQUEST_STATUS['PENDING']){ ?>
                            <li><a role="button" href="javascript:void(0);" class="confirm" onclick="resendTeamInvite('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($item['team_request_id']); ?>')" title="<?= gettext("Are you sure you want to resend request?");?>" ><i class="fa fa-paper-plane" aria-hidden="true"></i>&emsp;<?= gettext("Resend Request");?></a></li>
                            <?php } ?>

                            <?php if ($item['status'] == Team::TEAM_REQUEST_STATUS['PENDING']){ ?>
                            <li><a role="button" href="javascript:void(0);" class="confirm"  onclick="cancelTeamRequest('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($item['team_request_id']); ?>', 'requests_sent')" title="<?= gettext("Are you sure you want to cancel this request?");?>" ><i class="fa fa-stop-circle" aria-hidden="true"></i>&emsp;<?= gettext("Cancel Request");?></a></li>
                            <?php } ?>

                            <?php if ($item['status'] != Team::TEAM_REQUEST_STATUS['PENDING']){ ?>
                            <li><a role="button"  href="javascript:void(0);" class="confirm"  onclick="deleteTeamRequest('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($item['team_request_id']); ?>', 'requests_sent')" title="<?= gettext("Are you sure you want to delete this request?");?>" ><i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= gettext("Delete Request");?></a></li>
                            <?php } ?>
                        </ul>
                    </div>
                
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
    </div>
</div>
   
<script>
  $('#received_requests').DataTable( {
      "order": [],
      "bPaginate": false,
      "searching": false,
      "bInfo" : false,
      "ordering": false,
  });

  function resendTeamInvite(g,r) {
    $.ajax({
      url: 'ajax_talentpeak.php?resendTeamInvite=1',
      type: "POST",
      data: {'groupid':g,'team_request_id':r},
      success: function(data) {
        try {
          let jsonData = JSON.parse(data);
          swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
            if (jsonData.status == 1){
              getTeamInvites(g);
            }            
            setTimeout(() => {
              $('#actionBtn_'+r).focus();
            }, 1000);

          });
        } catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }

		  }      
    });
  }

  $(document).on('click','.confirm-dialog-btn-abort', function(){
        setTimeout(function () {
            $('#getTeamInvites').focus();
        }, 100);
    });
</script>