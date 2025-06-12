<style>
    .checkbox-btn{
        border: 1px solid rgb(157, 157, 157);
    }
    .checkbox-btn.active    {
        border: 2px solid #0077b5;
        background: rgba(238, 238, 238, 0.82);
    }
    .sv-btn.sv-action-bar-item, .sv-btn{
        border-radius: 5px !important;
    }
</style>
<div id="updateSurveyResponses" class="modal fade" tabindex="-1">
	<div aria-label="<?= $modalTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h2 id="modal-title" class="modal-title"><?= $modalTitle; ?></h2>
				<button id="btn_close" type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
			</div>

			<div class="modal-body">
            <form id="requestRoleJoinForm">
                <div class="col-md-12 pb-5">
                    <input type="hidden" name="roletype" id="roletype" value="<?= $_COMPANY->encodeId($roleid)?>" >
                    <input type="hidden" name="foruid" id="foruid" value="<?= $_COMPANY->encodeId($joinRequest['userid'])?>" >
                    <div class="col-md-12 form-group">
                        <?php if($preSelectedRole['role_capacity'] > 1){ ?>
                        <p ><strong><?=$roleCapacityTitle?></strong></p>
                        <div class="btn-group-toggle">
                        <select aria-label="<?=$roleCapacityTitle?>" name="request_capacity" tabindex="0" id="request_capacity" class="form-control" >
                        <?php for($i=1;$i<=$preSelectedRole['role_capacity']; $i++ ){ ?>
                            <option value="<?= $i; ?>" <?= $joinRequest && $joinRequest['request_capacity']== $i ? 'selected' : '' ?>><?= $i; ?></option>
                        <?php } ?>
                        </select>
                        </div>
                        <?php } else { ?>
                            <input type="hidden" name="request_capacity" id="request_capacity" value="1" >
                        <?php } ?>
                    </div>

                    <?php if (!empty($all_chapters) && $chapterSelectionSetting['allow_chapter_selection']){ ?>
                    <div class="col-md-12 form-group">
                        <p class="mb-1">
                        <strong>
                        <?= $chapterSelectionSetting['chapter_selection_label']; ?>
                        </strong>
                        </p>

                        <div class="chapter_selection">
                            <select tabindex="-1" class="form-control selectpicker" id="chapter_select_dropdown" style="width:100%; border:none !important;" name="chapterids[]" data-live-search="true">
                                <?php foreach($all_chapters as $chapter){ 
                                    if (!$_USER->canManageContentInScopeCSV($groupid,$chapter['chapterid'])){
                                        continue;
                                    }
                                ?>
                                    <option data-tokens="<?= htmlspecialchars($chapter['chaptername']); ?>" value="<?=  $_COMPANY->encodeId($chapter['chapterid']);?>"> <?= htmlspecialchars($chapter['chaptername']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <?php
                        $showMsg = sprintf(gettext("If you request this %1\$s role, you are required to select one %2\$s."),$_COMPANY->getAppCustomization()['group']['name-short'],$_COMPANY->getAppCustomization()['chapter']['name-short']);
                        ?>
                        <small class="pl-2 mb-3 mt-0 pt-0 " style="color:gray;"><?= $showMsg; ?></small>
                    </div>
                    <?php } ?>

                    <div class="col-md-12 m-0 p-0" id="interst" >
                        <div id="surveyContainer">
                            <div class="p-3"><?= gettext("Loading data");?> ....</div>
                        </div>
                    </div>
                </div>
            </form>
            </div>
		</div>
	</div>
</div>

<script>
    $(document).ready(function () {
        setTimeout(function () {
            // Color customization
            var defaultThemeColorsSurvey = Survey.StylesManager.ThemeColors["modern"];
            defaultThemeColorsSurvey["$main-color"] = "#0077b5";
            defaultThemeColorsSurvey["$main-hover-color"] = "#0D5380";
            defaultThemeColorsSurvey["$text-color"] = "#505050";
            defaultThemeColorsSurvey["$header-color"] = "#0077b5";
            defaultThemeColorsSurvey["$header-background-color"] = "#505050";
            defaultThemeColorsSurvey["$body-container-background-color"] = "#f8f8f8";
            Survey.StylesManager.applyTheme("Default");
            Survey.slk('<?= Config::Get('SURVEY_JS_LICENSE_KEY') ?>');
            var surveyJSON = <?= $questionJson; ?>;
            $("#loadingSpinner").hide();
            var survey = new Survey.Model(surveyJSON);
            survey.completeText = '<?= $submitBtn; ?>';

            Survey.surveyLocalization.locales["local"] = {emptySurvey :"Setup questions for selected role is not created yet. Please contact to administrator."};
            survey.locale = "local";
        <?php if($joinRequest){ ?>
            survey.data = <?= $joinRequest['role_survey_response']; ?>;
        <?php } ?>

            $("#surveyContainer").Survey({
                model: survey,
                onComplete: updateTeamJoinRequestSurveyResponseByManager
            });
        }, 1000);


        $('#chapter_select_dropdown').multiselect({nonSelectedText: "<?= ($group->val('chapter_assign_type') =='auto') ? gettext("Not Assigned") : sprintf(gettext('Select %s to request'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",numberDisplayed: 3,nSelectedText  : '<?=sprintf(gettext("%ss Requested"), $_COMPANY->getAppCustomization()['chapter']['name-short'])?>',allSelectedText: '<?=sprintf(gettext("All %ss Requested"), $_COMPANY->getAppCustomization()['chapter']['name-short'])?>',maxHeight:200});
        $('#chapter_select_dropdown').val(<?= json_encode($encodedRequestedChapters); ?>);
        $("#chapter_select_dropdown").multiselect("refresh");
    });


    function updateTeamJoinRequestSurveyResponseByManager(survey) {

        let formdata = $('#requestRoleJoinForm')[0];
	    let finaldata  = new FormData(formdata);
        let roletype = $("#roletype").val();
        let request_capacity = $("#request_capacity").val();
        if (roletype){
            let responseJson = JSON.stringify(survey.data);
            finaldata.append("responseJson",responseJson);
            finaldata.append("groupid",'<?= $_COMPANY->encodeId($groupid); ?>');
            finaldata.append("roletype",roletype);
            finaldata.append("request_capacity",request_capacity);
            $.ajax({
                url:'ajax_talentpeak.php?updateTeamJoinRequestSurveyResponseByManager=1',
                type: "post",
                data: finaldata,
                processData: false,
                contentType: false,
                cache: false,
                success: function (data) {
                    try {
                        let jsonData = JSON.parse(data);
                        swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
                            if(jsonData.status == 1) {
                                closeAllActiveModal();
                                getUnmatchedUsersForTeam('<?= $_COMPANY->encodeId($groupid); ?>');
                            }
                        });
                    } catch(e) { swal.fire({title: 'Error', text: "Unknown error."});}
                }
            });
        } else {
            swal.fire({title: 'Error',text:'Please select an option of "Join as"!'});
        }
    }

$('#updateSurveyResponses').on('shown.bs.modal', function () {
    $('#btn_close').focus(); 
});

$(document).click(function(event) {
    var submitBtn = $(event.target).hasClass('sv-footer__complete-btn');
    if(submitBtn){
        setTimeout(() => {
			$(".swal2-confirm").focus();   
		}, 600);	
    }
});

$(document).ready(function (){
    $("#request_capacity").bind("keydown", function (e) {    
        var keyCode = e.keyCode || e.which;
        if(keyCode === 9) {
            e.preventDefault();
            $('input, select, textarea')
            [$('input,select,textarea').index(this)+1].focus();
        }
    });
});
</script>

