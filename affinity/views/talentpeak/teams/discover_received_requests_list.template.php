
<div class="col-md-12 mt-3">            
    <div class="table-responsive " id="eventTable">
        <table id="received_requests" class="table display" summary="This table display the list of Received team requests">
            <thead>
              <tr>
                  <th width="40%" class="color-black" scope="col"><?= gettext("Sender");?></th>
                  <th width="30%" class="color-black" scope="col"><?= gettext("Sender Role");?></th>
                  <th width="20%" class="color-black" scope="col"><?= gettext("Status");?></th>
                  <th width="10%" class="color-black" scope="col"><?= gettext("Action");?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($receivedRequests as $receivedRequest){
              ?>
                <tr>
                    <td>
                        <?= User::BuildProfilePictureImgTag($receivedRequest['firstname'], $receivedRequest['lastname'], $receivedRequest['picture'], 'memberpic2', 'User Profile Picture', $receivedRequest['senderid'], 'profile_full')?>
                        <span class="pt-2"><?= $receivedRequest['firstname'].' '.$receivedRequest['lastname'];?></span>
                    </td>
                    <td>
                        <?= $receivedRequest['type']; ?>
                    </td>
                    <td>
                        <?=  Team::GetTeamRequestStatusLabel($receivedRequest['status']); ?>
                    </td>
                  <td>
                    <div class="" style="color: #fff; float: left;">
                        <button class="btn btn-sm btn-affinity dropdown-toggle" title="Action" type="button" data-toggle="dropdown">
                          <?= gettext('Action');?>&emsp;&#9662</button>
                        <ul class="dropdown-menu dropdown-menu-right" id="dynamicActionButton'.$encEventid.'" style="width: 250px; cursor: pointer;">
                            <?php if($receivedRequest['status'] == Team::TEAM_REQUEST_STATUS['PENDING']){ ?>
                            <li>
                                <a href="javascript:void(0);"
                                <?php if(Team::GetRequestDetail($groupid,$receivedRequest['receiver_role_id'],$receivedRequest['receiverid'])){ ?>
                                class="confirm"
                                onclick="acceptOrRejectTeamRequest('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($receivedRequest['team_request_id']); ?>','<?= $_COMPANY->encodeId(2); ?>')"
                                title="<?= gettext("Are you sure you want to accept this request?");?>"
                                <?php } else { ?>
                                onclick="askForTeamRoleJoinRequest('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($receivedRequest['receiver_role_id']); ?>','<?= $_COMPANY->encodeId($receivedRequest['team_request_id']); ?>','<?= $_COMPANY->encodeId(2); ?>')"
                                <?php } ?>
                                >
                                    <i class="fa fa-check" aria-hidden="true"></i>&emsp;<?= gettext("Accept Request");?>
                                </a>
                            </li>
                            <?php } ?>

                            <?php if($receivedRequest['status'] == Team::TEAM_REQUEST_STATUS['PENDING']){ ?>
                            <li>
                                <a href="javascript:void(0);" class=""  onclick="rejectTeamRequest('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($receivedRequest['team_request_id']); ?>','<?= $_COMPANY->encodeId(0); ?>')" >
                                    <i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= gettext("Reject Request");?>
                                </a>
                            </li>
                            <?php } ?>

                            <?php if($receivedRequest['status'] == Team::TEAM_REQUEST_STATUS['PENDING'] || $receivedRequest['status'] == Team::TEAM_REQUEST_STATUS['ACCEPTED']){ ?>
                              <li>
                                  <a href="javascript:void(0);" class=""  onclick="viewRequestMatchingStats('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($receivedRequest['team_request_id']); ?>')">
                                      <i class="fa fas fa-user-friends" aria-hidden="true"></i>&emsp;<?= gettext("View Match");?>
                                  </a>
                              </li>
                            <?php } ?>


                            <?php if($receivedRequest['status'] != Team::TEAM_REQUEST_STATUS['PENDING']){ ?>
                            <li>
                                <a href="javascript:void(0);" class="confirm"  onclick="deleteTeamRequest('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($receivedRequest['team_request_id']); ?>', 'requests_received')" title="<?= gettext("Are you sure you want to delete this request?");?>" >
                                    <i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= gettext("Delete Request");?>
                                </a>
                            </li>
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


    function askForTeamRoleJoinRequest(groupid, roleid, inviteid, joinStatus) {
      Swal.fire({
        text: '<?= addslashes(gettext('Before accepting this request, you need to send a role join request. Press the "Ok" button to access the role join request option.')); ?>',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#0077B5',
        confirmButtonText: 'Ok'
        }).then(function(result)  {
        if (result.value) {
          getProgramJoinOptions(groupid,'<?= $_COMPANY->encodeId(0); ?>',roleid,'v1',undefined,inviteid,joinStatus);
        }
      });
    }


    function viewRequestMatchingStats(g,r) {
      $.ajax({
        url: './ajax_talentpeak.php?viewRequestMatchingStats=1',
        type: 'GET',
        data: {groupid:g,request_id:r},
        success: function(data) {
          try {
            let jsonData = JSON.parse(data);
            swal.fire({title: jsonData.title,text:jsonData.message});
          } catch(e) {
            $("#loadAnyModal").html(data);
            $('#memberMatchingStatsModal').modal({
              backdrop: 'static',
              keyboard: false
            });
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
          }
        }
      });

    }
</script>