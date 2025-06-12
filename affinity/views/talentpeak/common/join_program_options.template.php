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
    .sv-root-modern .sv-question__title--answer {
        background-color: rgba(255, 255, 255);
    }
    .form-check-input {
        margin-top: 0.5rem;
    }
    .sv-root-modern {
        margin-left:-30px;
    }
    /* Survey next button focus */
    .sv-footer__prev-btn:focus, .sv-footer__next-btn:focus, .sv-footer__complete-btn:focus{
        outline: 2px solid #000;
    }
    .sv-title.sv-container-modern__title {
        color:#505050;
        margin-left: 4px;
        margin-right: 4px;
    }
    .sv-container-modern__title h3 {
        padding-left: 0;
    }
    .sv-header__text h3 span {
        font-size: smaller;
        line-height: normal;
    }
    .sv-header__text h5 span {
        font-size: 17px;
        line-height: normal;
    }
    .sv-question {
        overflow: hidden;
    }
    .sv-container-modern__title h5 {
        display: block;
    }
</style>
<div id="follow_program" class="modal fade">
	<div aria-label="<?= $modalTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
		<div class="modal-content">
			
			<div class="modal-header">
				<h2 class="modal-title"><?= $modalTitle; ?></h2>
				<button type="button" id="btn_close" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
			</div>

			<div class="modal-body">
            <form id="requestRoleJoinForm">
                <div class="col-md-12 pb-5">
                    <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                    <input type="hidden" name="roletype" id="roletype" value="<?= $_COMPANY->encodeId($id)?>" >
                    <?php if (0) { ?>
                    <div class="form-group p-3 mb-2 text-white" style="background-color:gray;">
                        <small class="form-text"><?= sprintf(gettext('This section allows you to request or update a role on a %s that you are not currently a member of.'),Team::GetTeamCustomMetaName($group->getTeamProgramType())); ?></small>
                    </div>
                    <?php } ?>
                <div id="roleRequestStaticFields">
                    <div class="col-md-12 form-group">
                        <?php if($preSelectedRole['role_capacity'] > 1){ ?>
                        <h4 style="font-size: 1.2rem;"><?=$roleCapacityTitle?></h4>
                        <div class="btn-group-toggle">
                        <select aria-label="<?=$roleCapacityTitle?>" name="request_capacity" tabindex="0" id="request_capacity" class="form-control" onchange="showHideSurvey(1)" >
                        <?php for($i=1;$i<=$preSelectedRole['role_capacity']; $i++ ){ ?>
                            <option value="<?= $i; ?>" <?= $joinRequest && $joinRequest['request_capacity']== $i ? 'selected' : '' ?>><?= $i; ?></option>
                        <?php } ?>
                        </select>
                        </div>
                        <?php } else { ?>
                            <input type="hidden" name="request_capacity" id="request_capacity" value="<?= $preSelectedRole['role_capacity'] ? 1 : 0; ?>" >
                        <?php }  ?>
                    </div>
                <?php if (!empty($all_chapters) && $chapterSelectionSetting['allow_chapter_selection']){ ?>
                    <div class="col-md-12 form-group">
                        <p class="mb-1">

                        <?= $chapterSelectionSetting['chapter_selection_label']; ?>

                        </p>

                        <div class="chapter_selection">
                            <select aria-label="<?= $chapterSelectionSetting['chapter_selection_label']; ?>" tabindex="-1" class="form-control selectpicker" id="chapter_select_dropdown" style="width:100%; border:none !important;" name="chapterids[]" data-live-search="true" required>
                                <?php foreach($all_chapters as $chapter){ ?>
                                    <option data-tokens="<?= htmlspecialchars($chapter['chaptername']); ?>" value="<?=  $_COMPANY->encodeId($chapter['chapterid']);?>"> <?= htmlspecialchars($chapter['chaptername']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <?php
                        $showMsg = sprintf(gettext('To request this %1$s role, you must select one %2$s.'),$_COMPANY->getAppCustomization()['group']['name-short'],$_COMPANY->getAppCustomization()['chapter']['name-short']);
                        ?>
                        <small class="pl-2 mb-3 mt-0 pt-0 " style="color:gray;"><?= $showMsg; ?></small>
                    </div>
                <?php } ?>
               

                    <div class="col-md-12 m-0 p-0" id="interst" style="display: <?= $joinRequest ? 'block' : 'none'; ?>">
                    <?php if($isQuestionAvailable){ ?> 
                        <div id="surveyContainer">
                            <div class="p-3"><?= gettext("Loading data");?> ....</div>
                        </div>
                    <?php } else { ?>
                    <div class="text-right mt-3">
                        <button tabindex="0" type="button" class="btn btn-affinity" onclick="saveTeamJoinRequestData({data:{}})"><?= $submitBtn;?></button>
                    </div>
                    <?php } ?>
                    <?php if (0 && $joinRequest && $joinRequest['isactive']=='1'){ ?>
                        <button tabindex="0" class="btn btn-affinity confirm" title="<?= gettext("Are you sure you want to cancel your registration?"); ?>" id="cancelBtn" onclick="cancelTeamJoinRequest('<?= $_COMPANY->encodeId($groupid); ?>','<?=  $_COMPANY->encodeId($joinRequest['roleid']); ?>','<?= $_COMPANY->encodeId($_USER->id()); ?>')" style="display:none;<?= $isQuestionAvailable ? 'margin-left: 44px;position: absolute;margin-top: -53px;' : 'margin-top: -70px;'; ?>"><?= gettext("Cancel Registration");?></button>
                    <?php } ?>
                    </div>
                </div>
                </form>

            </div>

			<!-- <div class="text-center pb-5">
				<button type="button" onclick="joinProgram('<?= $_COMPANY->encodeId($groupid)?>',1)" class="btn btn-affinity" >Send&nbsp;Request</button>
			</div> -->

		</div>
	</div>
</div>

<script>
    // Chapter Section
    $('#chapter_select_dropdown').multiselect({nonSelectedText: "<?= ($group->val('chapter_assign_type') =='auto') ? gettext("Not Assigned") : sprintf(gettext('Select %s to request'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",numberDisplayed: 3,nSelectedText  : '<?=sprintf(gettext("%ss Requested"), $_COMPANY->getAppCustomization()['chapter']['name-short'])?>',allSelectedText: '<?=sprintf(gettext("All %ss Requested"), $_COMPANY->getAppCustomization()['chapter']['name-short'])?>',maxHeight:200});
    $('#chapter_select_dropdown').val(<?= json_encode($encodedRequestedChapters); ?>);
    $("#chapter_select_dropdown").multiselect("refresh");


    function showHideSurvey(a){
      $("#interst").show();
      if ($("#request_capacity").val().length == 0){
        $("#interst").hide();
      }
    }

    <?php if($preselected>0){ ?>
        showHideSurvey(1);
    <?php } ?>
    <?php if ($joinRequest && $joinRequest['isactive']=='1'){ ?>
        setTimeout(function () {
            $("#cancelBtn").show();
        }, <?= $isQuestionAvailable ? 2000 : 0 ;?>);

    <?php } ?>
<?php if ($isQuestionAvailable){ ?>
    $(document).ready(function () {
        initSurveyData();
    });
<?php } ?>

    function initSurveyData(){
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

            Survey.surveyLocalization.locales["local"] = {emptySurvey :"<?= gettext('Setup questions for the selected role have not yet been created. Please contact an administrator for assistance.')?>"};
            survey.locale = "local";
        <?php if($joinRequest){ ?>
            survey.data = <?= $joinRequest['role_survey_response']; ?>;
        <?php } ?>

            $("#surveyContainer").Survey({
                model: survey,
                onComplete: saveTeamJoinRequestData
            });
        
            $(".sv-title.sv-container-modern__title").insertBefore("#roleRequestStaticFields");

        }, 1000);
    }

    function saveTeamJoinRequestData(survey) {
        
        let formdata = $('#requestRoleJoinForm')[0];
	    let finaldata  = new FormData(formdata);
        let roletype = $("#roletype").val();
        let request_capacity = $("#request_capacity").val();
        let program_type = '<?= $program_type_value ?>';
        if (roletype){
            let responseJson = JSON.stringify(survey.data);
            finaldata.append("responseJson",responseJson);
            finaldata.append("groupid",'<?= $_COMPANY->encodeId($groupid); ?>');
            finaldata.append("roletype",roletype);
            finaldata.append("request_capacity",request_capacity);
            $.ajax({
                url:'ajax_talentpeak.php?saveTeamJoinRequestData=1',
                type: "post",
                data: finaldata,
                processData: false,
                contentType: false,
                cache: false,
                success: function (data) {                   
                    try {
                        let jsonData = JSON.parse(data);
                        if (jsonData.status == 0){
                            initSurveyData();
                        }
                        swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
                            <?php if ($version === 'v1') { ?>
                            <?php if ($inviteid != 0 && $joinstatus !=0) { ?>
                                acceptOrRejectTeamRequest('<?= $_COMPANY->encodeId($groupid)?>','<?= $inviteid; ?>', '<?= $joinstatus; ?>');
                                location.reload();
                            <?php } else { ?>
                                if(jsonData.status == 1) {   
                                    if (program_type==2){                                          
                                        window.location.hash = 'getMyTeams/initDiscoverTeamMembers';
                                    } else if(program_type==5) {
                                        let resp = jsonData.val;
                                        if (resp[1] == 3) {
                                            <?php if (isset($teamid) && $teamid != 0){ ?>
                                                window.location.hash = 'getMyTeams/initDiscoverCircles-<?= $teamid?>';
                                            <?php } else { ?>
                                                window.location.hash = 'getMyTeams/initDiscoverCircles';
                                            <?php } ?>
                                        } else {
                                            window.location.hash = 'getMyTeams';
                                        }
                                    }
                                    setTimeout(() => {
                                        $(".swal2-confirm").focus();   
                                    }, 500);  

                                    location.reload();
                                }else if(jsonData.status == 3){
                                    autoMatchWithMentorRole('<?= $_COMPANY->encodeId($groupid)?>',jsonData.val);
                                }
                            <?php } ?>
                            <?php } else { ?>
                                if (jsonData.status == 1){
                                    closeAllActiveModal();
                                    $("#join").focus();
                                    getFollowChapterChannel('<?= $_COMPANY->encodeId($groupid); ?>',2);
                                }
                            <?php } ?>
                        });
                    } catch(e) { swal.fire({title: 'Error', text: "Unknown error."});}

                   
                }
            });
        } else {
            swal.fire({title: 'Error',text:'Please select an option of "Join as"!'});
        }
    }


$('#follow_program').on('shown.bs.modal', function () {
   $('#btn_close').trigger('focus');
});
$('.multiselect').attr( 'tabindex', '0' );
$("#request_capacity").keydown(function(e) {    
    if (e.shiftKey && e.key === 'Tab') { 
        setTimeout(function () {
            $('#btn_close').trigger('focus');             
        },100);
    }else if (e.keyCode === 9) {           
        $(":input")[$(":input").index(document.activeElement) + 1].focus();
        return false;   
    }
})
trapFocusWithInModal();


function autoMatchWithMentorRole(g,r){
    closeAllActiveModal();
    Swal.fire({
        title: '<?= addslashes(gettext('Searching for the best match')); ?>',
        html: '<?= addslashes(gettext('We are searching for the best match for your requested role. Please wait.')); ?>',
        timer: 30000,
        timerProgressBar: true,
    });
    $.ajax({
		url: 'ajax_talentpeak.php?autoMatchWithMentorRole=1',
		type: "POST",
		data: {'groupid':g,'roleid':r},
		success: function(data) {
            try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					$("#my_team_menu").trigger("click");
				});
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});

}
</script>

