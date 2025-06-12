<div id="showSurveyResponses" class="modal fade">
    <div aria-label="<?= $pageTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title" class="modal-title"><?= $pageTitle; ?></h2>
                <button type="button" id="btn_close" class="close" aria-hidden="true" data-dismiss="modal">&times;</button>
            </div>
            <div id="modal_body">
                <div class="modal-body">
                  <div class="col-md-12 mt-3 mb-5"> 
                      <div class="col-md-12 text-center">
                        <?= User:: BuildProfilePictureImgTag($usersProfile['firstname'],$usersProfile['lastname'], $usersProfile['picture'], 'memberpicture', $alt_tag="User Profile Picture", $uid, 'profile_full') ; ?>
                        <?= $usersProfile['firstname'].' '.$usersProfile['lastname']; ?>
                      </div>
                      <div class="col-md-12 text-center">
                        <p class="p-2"><?= gettext("Requested Role to Join");?> : <strong><?= $usersProfile['roleType']; ?></strong></p>
                      </div>
                      <hr class="line">
                      <div class="table-responsive " id="eventTable">
                          <table id="view_request_responses" class="table display" summary="This table display the list of teams" width="100%">
                              <thead>
                                <tr>
                                  <th width="5%" class="color-black" scope="col">#</th>
                                  <th width="70%" class="color-black" scope="col"><?= gettext("Question");?></th>
                                  <th width="25%" class="color-black" scope="col"><?= gettext("Answer");?></th>
                                </tr>
                              </thead>
                              <tbody>
                              <?php $i=1; foreach($questionAnswers as $qa => $val){ ?>
                                <tr>
                                  <td><?= $i; ?></td>
                                  <td><?= htmlspecialchars($qa); ?></td>
                                  <td><?= htmlspecialchars($val); ?></td>
                                </tr>
                              <?php $i++; } ?>
                              </tbody>
                            </table>
                      </div>
                  </div>
                </div>
            </div>
        </div>
    </div>
</div>
      
<script>
   var dtable = $('#view_request_responses').DataTable( {
        "order": [],
        "bPaginate": false,
        "bFilter": true,
        "bInfo" : false,
        language: {
              "sZeroRecords": "<?= gettext('No data available in table');?>",
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },
    });
    
// function for Accessiblity screen reading.
screenReadingTableFilterNotification('#view_request_responses',dtable);

$('#showSurveyResponses').on('shown.bs.modal', function () {
    $('#modal-title').trigger('focus');
});
</script>