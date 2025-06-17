<div class="modal" id="showModal">
    <div aria-label="<?= gettext("Access Denied");?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><?= gettext("Access Denied");?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <div class="col-md-12">
                <p class="mb-3"><?= gettext("You do not have access rights to activate this survey. Please contact one of the system administrators below to perform this action.");?></p>
              <?php if(!empty($adminstrators)){ ?>
                <strong ><?= gettext("Zone Adminstrators List");?> : </strong>
                <div class="table-responsive mt-3">
                    
                    <table  class="table display" id="statistics" style="width:100%;" summary="This table display the adminstrator list">
                        <thead>
                            <tr>
                              <th width="5%" scope="col"></th>
                              <th width="40%" scope="col"><?= gettext("Name");?></th>
                              <th width="55%" scope="col"><?= gettext("Email");?></th>
                              <th width="55%" scope="col"><?= gettext("Action");?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($adminstrators as $admin){
                            $profilepic = User::BuildProfilePictureImgTag($admin['firstname'], $admin['lastname'],$admin['picture'],'memberpic2', "Administrators Profile Picture", $admin['userid'], 'profile_basic');
                          ?>
                            <tr>
                              <td><?= $profilepic; ?></td> 
                              <td><?= $admin['firstname'].' '.$admin['lastname']; ?></td>  
                              <td><?= $admin['email']; ?></td>
                              <td><button class="btn btn-affinity confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>"  title="<?= gettext('Are you sure you want to send request?');?></button>" onclick="sendRequestForSurveyAction('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($surveyid)?>','<?= $_COMPANY->encodeId($admin['userid'])?>')"><?= gettext("Send&nbsp;Request");?></button></td>
                            </tr>                          
                        <?php } ?>
                        </tbody>
                    </table>
                </div>	
            <?php } ?>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= gettext("Close");?></button>
        </div>
      </div>
    </div>
  </div>
  <script src="../vendor/js/datatables-2.1.8/datatables.min.js"></script>
  <script>
      $(document).ready(function() {
          
          $('#statistics').DataTable( {
            "info":     false,
            language: {
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },	
          } );
      } );
  </script>