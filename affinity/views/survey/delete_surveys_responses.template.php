<div id="deleteSurveyDataConfirmationModal" class="modal fade">
    <div aria-label="<?= gettext("Confirmation");?>" class="modal-dialog" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header text-center">
                <h4 class="modal-title"><?= gettext("Confirmation");?></h4>
            </div>
            <div class="modal-body">
            <label for="confirmChangeSurvey"> <p><?= sprintf(gettext('I understand the survey response data will be immediately and permanently deleted. By typing %s below, I am providing my consent to delete the responses!'), '"<b>Yes, permanently delete the survey and all its responses</b>"');?></p>
            </label>
                <div class="form-group mt-3">
                    <input type="text" class="form-control" id="confirmChangeSurvey" onkeyup="initDeleteSurveyResponse()" placeholder="" name="confirmChangeSurvey" required>
                    
                    <span style="color: #ff0000;font-size: small;"><?= gettext("This is a required field.");?> </span>
  
                </div>
            </div>
            <div class="modal-footer text-center">
                <span id="action_button_survey"><button class="btn btn-outline-danger" disabled ><?= gettext("Submit");?></button></span>
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?= gettext("Cancel");?></button>
            </div>
        </div>
    </div>
</div>
<script>


    function initDeleteSurveyResponse(){
        var v = $("#confirmChangeSurvey").val();
        if (v =='Yes, permanently delete the survey and all its responses'){
            $("#action_button_survey").html('<button class="btn btn-danger" onclick="deleteSurvey(\'<?= $_COMPANY->encodeId($groupid); ?>\',\'<?= $_COMPANY->encodeId($surveyid); ?>\');" >Submit</button>');
        } else {
            $("#action_button_survey").html('<button class="btn btn-outline-danger no-drop" disabled >Submit</button>');
        }
    }

    $('#deleteSurveyDataConfirmationModal').on('shown.bs.modal', function () {
    $('#confirmChangeSurvey').trigger('focus')
});
</script>