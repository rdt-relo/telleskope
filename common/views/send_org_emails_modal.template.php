<div class="modal" id="orgEmailModalForm">
    <div class="modal-dialog modal-xl modal-dialog-w900" aria-modal="true" role="dialog">
      <div class="modal-content">
  
        <!-- Modal Header -->
        <div class="modal-header">
          <h4 id="modal-title" class="modal-title">Send Notification to Organization Contacts</h4>
          <button aria-label="<?= gettext('close');?>" id="btn_close" type="button" class="close" data-dismiss="modal" >&times;</button>
        </div>

        <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <label class="control-label col-md-12" for="to_email"><strong>Send Notification To:</strong> </label>
                        <div id="to_email" class="col-md-12">
                        <input type="text" class="form-control" value="<?= $emailsToString ?>" disabled>
                        </div>
                    </div>

                    <?php if (1 /* Todo: show this only if the organization has not been claimed */) { ?>
                    <div class="col-md-12  mt-3">
                        <label class="control-label col-md-12" for="cc_email"><strong>CC:</strong> </label>
                        <div id="cc_email" class="col-md-12">
                            <input type="text" class="form-control" value="<?= $_USER->val('email') ?>" disabled>
                        </div>
                    </div>
                    <?php } ?>

                    <div class="col-md-12 mt-3">
                        <label class="control-label col-md-12" for="redactor_org_notification_email_subject"><strong> <?= gettext('Notification Email Subject'); ?> </strong></label>
                                    <div id="post-inner" class="col-md-12">
                                        <input type="text" class="form-control" name="welcome_message"  id="redactor_org_notification_email_subject" placeholder="<?= gettext("Notification subject here") . ' ... ' . gettext("(will be reset to default if empty)"); ?>" value="<?= $welcomeEmailSubject ?>">
                                    </div>

                    </div>

                    <div class="col-md-12 mt-3">
                        <label class="control-label col-md-12" for="redactor_org_notification_email"><strong><?= gettext('Notification Email Template'); ?></strong> </label>
                                    <div id="post-inner" class="col-md-12">
                                        <textarea class="form-control" name="welcome_message" rows="10" id="redactor_org_notification_email" maxlength="8000" placeholder="<?= gettext("Notification message here") . ' ... ' . gettext("(will be reset to default if empty)"); ?>"><?= $welcomeEmailMessage ?></textarea>
                                    </div>

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                    </div>
                </div>
        </div>

          <!-- Modal footer -->
          <div class="modal-footer">
                <div class="form-group text-center">

                    <button type="button" onclick="sendOrgEmailsNotification()" class="btn btn-primary prevent-multi-clicks"><?= gettext("Submit");?></button>&emsp;<button type="button" class="btn btn-primary" data-dismiss="modal"><?= gettext("Close");?></button>
                    <!--TODO: add onclick="viewTopicApprovalDetail()" -->
                </div>
            </div>

      </div>
    </div>
</div>
<script>
    $('#redactor_org_notification_email').initRedactor('redactor_org_notification_email','',['counter']);
    
    function sendOrgEmailsNotification(){
        let emails = '<?= $contactEmails ?>';
        let ccEmails = '<?= $_USER->val('email') ?>';
        let subject = $('#redactor_org_notification_email_subject').val();
        let message= $("#redactor_org_notification_email").val();
        let updateOrgId = '<?= $api_org_id ?>';
        let approvalid = '<?= $_COMPANY->encodeId($approval->id()) ?>';
        let company_org_id = '<?= $_COMPANY->encodeId($company_org_id) ?>';
        let eventid = '<?= $_COMPANY->encodeId($approvalEventId) ?>';
            $.ajax({
            url: 'ajax_organizations.php?sendOrgEmailsNotification=1',
            type: "POST",
            data: {
                emails,
                ccEmails,
                subject,
                message,
                api_org_id: updateOrgId,
                approvalid,
                company_org_id,
                eventid,
            },
            success : function(data) {
                if(data == 1){
                        swal.fire({title: 'Success',text:'Notification sent to contacts!'}).then(function(result){
                            location.reload();
                        });
                }else{
                        swal.fire({title: 'Error',text:data.message}).then(function(result){
                        location.reload();
                    });
                }
            }
        });
    }
</script>