<?php include __DIR__ . '/header.html'; ?>


<!-- New recognition POP UP -->
<style>
.fa.fa-times {
	color: #f80e0e;
	background-color: #fff;
	position: absolute;
	margin-left: 0px;
}
</style>
<div class="container">
    <div class="row">
		<div id="follow_program" class="modal fade" tabindex="0">
			<div class="modal-dialog modal-lg" aria-modal="true" role="dialog">
				<div class="modal-content">
					
					<div class="modal-header">
						<h1 class="modal-title"><?= $modalTitle; ?></h1>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="window.location.href='success_callback.php'">&times;</button>
					</div>

					<div class="modal-body">
						<form id="requestRoleJoinForm">
						<div class="col-md-12 pb-5">
							<input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
							<input type="hidden" name="roletype" id="roletype" value="<?= $_COMPANY->encodeId($id)?>" >
							<div class="col-md-12 form-group">
								<?php if($preSelectedRole && $preSelectedRole['role_capacity'] > 1){ ?>
								<p ><strong><?=$roleCapacityTitle?></strong></p>
								<div class="btn-group-toggle">
                                    <select name="request_capacity" tabindex="0" id="request_capacity" class="form-control" onchange="showHideSurvey(1)" >
                                    <?php for($i=1;$i<=$preSelectedRole['role_capacity']; $i++ ){ ?>
                                        <option value="<?= $i; ?>" <?= $joinRequest && $joinRequest['request_capacity']== $i ? 'selected' : '' ?>><?= $i; ?></option>
                                    <?php } ?>
                                    </select>
								</div>
								<?php } else { ?>
									<input type="hidden" name="request_capacity" id="request_capacity" value="1" >
								<?php }  ?>
							</div>
						<?php if (!empty($all_chapters) && $chapterSelectionSetting['allow_chapter_selection']){ ?>
							<div class="col-md-12 form-group">
								<p class="mb-1">
								    <?= $chapterSelectionSetting['chapter_selection_label']; ?>
								</p>

								<div class="chapter_selection">
									<select tabindex="-1" class="form-control selectpicker" id="chapter_select_dropdown" style="width:100%; border:none !important;" name="chapterids[]" data-live-search="true">
										<?php foreach($all_chapters as $chapter){ ?>
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

							<div class="col-md-12 m-0 p-0" id="interst" style="display: <?= $joinRequest ? 'block' : 'none'; ?>">
							<?php if($isQuestionAvailable){ ?> 
								<div id="surveyContainer">
									<div class="p-3"><?= gettext("Loading data");?> ....</div>
								</div>
							<?php } else { ?>
							<div class="text-right mt-3">
								<button tabindex="0" type="button" class="btn btn-primary" onclick="saveTeamJoinRequestData({data:{}})"><?= $submitBtn;?></button>
							</div>
							<?php } ?>
							</div>
						</div>
						</form>
					</div>
				</div>
			</div>
		</div>
    </div>
</div>
<script>
   $(document).ready(function () {
		$('#follow_program').modal({
			backdrop: 'static',
			keyboard: false
		});
	
        // Chapter Section
        $('#chapter_select_dropdown').multiselect({nonSelectedText: "<?= ($group->val('chapter_assign_type') =='auto') ? gettext("Not Assigned") : sprintf(gettext('Select %s to request'),$_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",numberDisplayed: 3,nSelectedText  : '<?=sprintf(gettext("%ss Requested"), $_COMPANY->getAppCustomization()['chapter']['name-short'])?>',allSelectedText: '<?=sprintf(gettext("All %ss Requested"), $_COMPANY->getAppCustomization()['chapter']['name-short'])?>',maxHeight:200});
        $('#chapter_select_dropdown').val(<?= json_encode($encodedRequestedChapters); ?>);
        $("#chapter_select_dropdown").multiselect("refresh");

    });
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

            Survey.slk('<?= Config::Get('SURVEY_JS_LICENSE_KEY') ?>');
            // Color customization
            var defaultThemeColorsSurvey = Survey.StylesManager.ThemeColors["modern"];
            defaultThemeColorsSurvey["$main-color"] = "#0077b5";
            defaultThemeColorsSurvey["$main-hover-color"] = "#0D5380";
            defaultThemeColorsSurvey["$text-color"] = "#505050";
            defaultThemeColorsSurvey["$header-color"] = "#0077b5";
            defaultThemeColorsSurvey["$header-background-color"] = "#505050";
            defaultThemeColorsSurvey["$body-container-background-color"] = "#f8f8f8";
            Survey.StylesManager.applyTheme("Default");

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
                onComplete: saveTeamJoinRequestData
            });
        }, 1000);
    }

    function saveTeamJoinRequestData(survey) {
        
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
                url:'ajax_native.php?saveTeamJoinRequestData=1',
                type: "post",
                data: finaldata,
                processData: false,
                contentType: false,
                cache: false,
                success: function (data) {
                    console.log(data);
                    try {
                        let jsonData = JSON.parse(data);
                        if (jsonData.status == 0){
                            initSurveyData();
                        }
                        swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
                            if(jsonData.status == 1) {
								window.location.href= 'success_callback.php';
                            }
                        });
                    } catch(e) { swal.fire({title: 'Error', text: "Unknown error."});}
                }
            });
        } else {
            swal.fire({title: 'Error',text:'Please select an option of "Join as"!'});
        }
    }
</script>
