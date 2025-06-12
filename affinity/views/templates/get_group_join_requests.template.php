<div class="col-md-12 mt-3">   
  <div>
      <div class="btn-group mb-3" style="float: right;">
                  <button type="button" class="btn btn-primary" onclick="manageJoinRequestEmailSettings('<?= $_COMPANY->encodeId($groupid); ?>')">
                        <?= gettext("Settings") ?>
                  </button>
      </div>
  </div>         
    <div class="table-responsive ">
        <table id="group_join_requests" class="table display" style="width: 100%;" summary="This table display the list of group join request">
            <thead>
              <tr>
                <th width="50%" class="color-black" scope="col"><?= gettext("Requester");?></th>
                <th width="25%" class="color-black" scope="col"><?= gettext("Job Title");?></th>
                <th width="25%" class="color-black" scope="col"><?= gettext("Requested On");?></th>
                <th width="25%" class="color-black" scope="col"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($joinRequests as $row){ ?>
                <tr>
                  <td>
                      <?= User::BuildProfilePictureImgTag($row['firstname'],$row['lastname'],$row['picture'],'memberpicture', 'User Profile Picture', $row['userid'], 'profile_basic') ?>
                      <span class="pt-2"><?= $row['firstname'].' '.$row['lastname'];?></span>
                  </td>
                  <td>
                    <?= $row['jobtitle']; ?> 
                  </td>
                  <td>
                  <?= $db->covertUTCtoLocalAdvance("l M j, Y \@ g:i a T","",  $row['modifiedon'],$_SESSION['timezone']); ?>
                  </td>
                  <td>
                    <div class="" style="color: #fff; float: left;">
                        <button class="fa fa-ellipsis-v col-doutd three-dot-action-btn dropdown-toggle btn-no-style" title="Action" type="button" data-toggle="dropdown">
                         </button>                         

                        <ul class="dropdown-menu dropdown-menu-right" style="width: 250px; cursor: pointer;">
                          <li><a href="javascript:void(0)" class="confirm" title="<?= gettext("Are you sure you want to accept join request?")?>" onclick="acceptRejectGroupJoinRequest('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($row['userid']); ?>','<?= $_COMPANY->encodeId(1); ?>')" ><i class="fa fa-check" aria-hidden="true"></i>&emsp;<?= gettext("Accept Join Request");?></a></li>
                          <li><a href="javascript:void(0)" class="confirm" title="<?= gettext("Are you sure you want to reject join request?")?>"onclick="acceptRejectGroupJoinRequest('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($row['userid']); ?>','<?= $_COMPANY->encodeId(2); ?>')"><i class="fa fa-times" aria-hidden="true"></i>&emsp;<?= gettext("Reject Join Request");?></a></li>
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
  $(document).ready(function(){
    var dtable = $('#group_join_requests').DataTable( {
        "order": [],
        "bPaginate": true,
        "bInfo" : false,
        language: {
          "sZeroRecords": "<?= gettext('No data available in table');?>",
			    url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
		    },
        "columnDefs": [
            { "targets": [3], "orderable": false }
        ]
    });
    screenReadingTableFilterNotification('#group_join_requests',dtable);
  });
</script>