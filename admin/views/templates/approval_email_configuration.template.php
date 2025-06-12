<div class="modal" id="approvalEmailConfigModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header">
        <h4 class="modal-title"><?= sprintf(gettext("Update %s Approval Stage Configuration"),$topicName); ?></h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>

        <!-- Modal body -->
        <div class="modal-body">

            <div class="col-12">

                <form id="emailConfigruationForm">
                    <div class="col-12 form-group-emphasis">
                        <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Configure Email Recipients")?></h5>
                        <!-- <p class="form-group-emphasis-heading-text"></p> -->
                        <div class="col-12">
                            <div class="form-group">
                                <label for="email">CC recipients for approval updates</label>
                                <input type="email" class="form-control" name='approver_cc_emails' id="approver_cc_emails" value="<?= $approver_cc_emails; ?>" placeholder="name@example.com">
                                <small>Note: Maximum 3 comma separated emails are allowed.</small>
                            </div>
                            <div class="form-group">
                                <label for="email">Maximum Approvers That Can Be Selected When Requesting Approval</label>
                                <input type="number" class="form-control" name='approver_max_approvers_limit' id="approver_max_approvers_limit" step="1" min="<?=$approver_min_approvers_limit?>" max="<?=Approval::APPROVERS_SELECTED_MAX_LIMIT?>" onblur="validateLimitValue(this)" value="<?= $approver_max_approvers_limit; ?>" placeholder="<?=Approval::APPROVERS_SELECTED_DEFAULT?>">
                                <small>To streamline the approval process and prevent overload when numerous approvers are available, you can limit the number of approvers that can be selected for each request. This ensures a more focused and efficient approval workflow. Please choose a number between 1 and <?=Approval::APPROVERS_SELECTED_MAX_LIMIT?> to enable this feature.</small>
                                <?php if ($approver_min_approvers_limit == 0) { ?>
                                <small>Choose 0 to disable this feature; if 0 is chosen, the dropdown to select approvers will not be shown, and the system will notify all approvers in the stage.</small>
                                <?php } ?>
                            </div>
                            <div class="form-group">
                                <input type="checkbox" name="disallow_submitter_approval" id="disallow_submitter_approval" value="1" <?= $disallow_submitter_approval ? 'checked' : '' ?>>
                                <label for="disallow_submitter_approver">Disallow approval submitter from being approver</label>
                            </div>
                        </div>
                    </div>
                   

                    <div class="col-12 form-group-emphasis">
                        <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Stage Approval Emails")?></h5>
                        <!-- <p class="form-group-emphasis-heading-text"></p> -->
                        <div class="col-12">
                            
                            <div class="form-group">
                                <label class="control-label" for="stage_approval_email_subject"><?= gettext("Email Subject"); ?></label>
                                <input id="stage_approval_email_subject" class="form-control" name="stage_approval_email_subject" placeholder="" value="<?= htmlspecialchars($stage_approval_email_subject); ?>">
                                <p class="text-sm">
                                    <?=sprintf(
                                        gettext('You can use the following variables: %s which will be replaced by actual values when sending out the emails.'),
                                        '[[APPROVER_FIRST_NAME]], [[APPROVER_LAST_NAME]], [[REQUESTER_FIRST_NAME]], [[REQUESTER_LAST_NAME]], [[APPROVAL_TOPIC_TITLE]], [[APPROVAL_STAGE]], [[APPROVAL_STAGE_STATUS]]'
                                    )?>
                                </p>
                               
                            </div>

                            <div class="form-group">
                                <label class="control-label" for="stage_approval_email_body"><?= gettext("Email Template"); ?></label>
                                <div id="post-inner" class="post-inner-edit">
                                    <textarea class="form-control" name="stage_approval_email_body" rows="10" id="stage_approval_email_body" maxlength="8000" placeholder="<?= gettext("You can customize the email message that will be sent out when someone requests to join this role"); ?>"><?= $stage_approval_email_body; ?></textarea>
                                    <p class="text-sm">
                                    <?=sprintf(
                                        gettext('You can use the following variables: %s which will be replaced by actual values when sending out the emails.'),
                                        '[[APPROVER_FIRST_NAME]], [[APPROVER_LAST_NAME]], [[REQUESTER_FIRST_NAME]], [[REQUESTER_LAST_NAME]], [[APPROVAL_TOPIC_TITLE]], [[APPROVAL_TOPIC_ID]], [[APPROVAL_STAGE]], [[APPROVAL_STAGE_STATUS]], [[APPROVER_NOTE]], [[APPROVAL_LOG_ATTACHMENTS]]'
                                    )?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 form-group-emphasis">
                        <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Stage Denial Emails")?></h5>
                        <!-- <p class="form-group-emphasis-heading-text"></p> -->
                        <div class="col-12">

                            <div class="form-group">
                                <label class="control-label" for="stage_denial_email_subject"><?= gettext("Email Subject"); ?></label>
                                <input id="stage_denial_email_subject" class="form-control" name="stage_denial_email_subject" placeholder="" value="<?= htmlspecialchars($stage_denial_email_subject); ?>">
                                <p class="text-sm">
                                    <?=sprintf(
                                        gettext('You can use the following variables: %s which will be replaced by actual values when sending out the emails.'),
                                        '[[APPROVER_FIRST_NAME]], [[APPROVER_LAST_NAME]], [[REQUESTER_FIRST_NAME]], [[REQUESTER_LAST_NAME]], [[APPROVAL_TOPIC_TITLE]], [[APPROVAL_STAGE]], [[APPROVAL_STAGE_STATUS]]'
                                    )?>
                                </p>

                            </div>

                            <div class="form-group">
                                <label class="control-label" for="stage_denial_email_body"><?= gettext("Email Template"); ?></label>
                                <div id="post-inner" class="post-inner-edit">
                                    <textarea class="form-control" name="stage_denial_email_body" rows="10" id="stage_denial_email_body" maxlength="8000" placeholder="<?= gettext("You can customize the email message that will be sent out when someone requests to join this role"); ?>"><?= $stage_denial_email_body; ?></textarea>
                                    <p class="text-sm">
                                        <?=sprintf(
                                            gettext('You can use the following variables: %s which will be replaced by actual values when sending out the emails.'),
                                            '[[APPROVER_FIRST_NAME]], [[APPROVER_LAST_NAME]], [[REQUESTER_FIRST_NAME]], [[REQUESTER_LAST_NAME]], [[APPROVAL_TOPIC_TITLE]], [[APPROVAL_TOPIC_ID]], [[APPROVAL_STAGE]], [[APPROVAL_STAGE_STATUS]], [[APPROVER_NOTE]], [[APPROVAL_LOG_ATTACHMENTS]]'
                                        )?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group text-center">
                        <button type="button" class="btn btn-primary" onclick="saveApprovalEmailConfiguration('<?= $_COMPANY->encodeId($approval_config_id); ?>')" ><?= gettext("Submit");?></button>
                        <button type="submit" class="btn btn-primary" data-dismiss="modal" ><?= gettext("Close");?></button>
                    </div>
                </form>
            </div>

        </div>

        </div>
    </div>
</div>

<script>

    $(document).ready(function(){
        $('stage_approval_email_body').initRedactor('stage_approval_email_body', 'zone',['counter']);
        $('stage_denial_email_body').initRedactor('stage_denial_email_body', 'zone',['counter']);
    });

    function saveApprovalEmailConfiguration(id) {

        let emails = $("#approver_cc_emails").val();
        let emailsArray = emails.split(",");

        if (emailsArray.length > 3) {
            swal.fire({title: 'Error', text: "Maximum 3 comma separated emails allowed"});
            return;
        }
        if (emails){
            var validateEmail = this.validateEmail(emails);
            if (validateEmail) {
                swal.fire({title: 'Error', text: validateEmail});
                return;
            }
        }

        var formdata =	$('#emailConfigruationForm')[0];
        var finaldata  = new FormData(formdata);
        finaldata.append("approval_config_id",id);
        finaldata.append("topic_type",'<?= $topic_type; ?>')
        $.ajax({
            url: 'ajax.php?saveApprovalEmailConfiguration=1',
            type: 'POST',
            data: finaldata, // get Data in html page by "managesection" Var
            processData: false,
            contentType: false,
            cache: false,
            success: function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
                        if (jsonData.status == 1){
                            location.reload();
                        }

                    });
                    
                } catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
            },
            error: function(error){}
        });

    }

    function validateLimitValue(input){
        const minl = parseInt('<?= $approver_min_approvers_limit ?>');
        const maxl = parseInt('<?= Approval::APPROVERS_SELECTED_MAX_LIMIT?>');
        input.value = input.value.replace(/\D+/g, '');
        if(input.value < minl){
            input.value = minl;
        } else if (input.value > maxl) {
            input.value = maxl;
        }
    }
</script>