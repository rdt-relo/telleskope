<div class="col-md-12">
    <div class="row">
        <div class="col-12">
            <h2><?= gettext("Manage Surveys")?> - <?= $_COMPANY->getAppCustomization()['group']['groupname0'];       
        ?></h2>
            <hr class="lineb" >
        </div>
    <?php
        if(Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['SURVEY_CREATE_BEFORE'])){
            $callOtherMethod = base64_encode(json_encode(array("method"=>"openAdminSurveySettingForm","parameters"=>array()))); // base64_encode for prevent js parsing error
            $newbtn = '<a href="javascript:void(0)" class="btn btn-affinity pull-right mt-0" onclick="loadDisclaimerByHook(\'' . $_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['SURVEY_CREATE_BEFORE']) .  '\', \'' . $_COMPANY->encodeId(0) . '\',0, \'' . $callOtherMethod.'\')" >' . gettext("Create Survey") . '</a>';
    
        } else {
            $newbtn = '<button id="manage-survey" class="btn btn-affinity pull-right mt-0" onclick="openAdminSurveySettingForm()" >'.gettext("Create Survey").'</button>';
        }
        include(__DIR__ . "/manage_section_dynamic_button.html");
    ?>

<div class="col-md-12">
            <div class="col-md-4">
                <div class="form-group col-md-12 ">
                    <label style="font-size:small;"><?= sprintf(gettext("Filter by %s State"), 'Survey');?></label>
                    <select aria-label="<?= sprintf(gettext("Filter by %s state"), 'Survey');?>" class="form-control" onchange="getAdminSurveys(1);" id="filterByState" style="font-size:small;border-radius: 5px;">
                      <option value="<?= $_COMPANY->encodeId(2)?>" <?= $state_filter==2 ? 'selected' : '' ?> ><?= gettext("Draft / Inactive");?></option>
                      <option value="<?= $_COMPANY->encodeId(1)?>" <?= $state_filter==1 ? 'selected' : '' ?> ><?= gettext("Active");?></option>
                    </select>
                  </div>

            </div>
           
        </div>
    <div class="col-md-12">
	  <div class="table-responsive">
            <table id="surveylist" class="table table-hover gg display compact" width="100%" summary="This table displays the list of surveys">
                <thead>
                    <tr>
                        <th width="40%" class="color-black" scope="col"><?= gettext("Survey Name");?></th>
                        <th width="5%" class="color-black" scope="col"><?= gettext("Survey Type");?></th>
                        <th width="10%" class="color-black" scope="col"><?= gettext("Creator");?></th>
                        <th width="5%" class="color-black" scope="col"><?= gettext("Settings");?></th>
                        <th width="5%" class="color-black" scope="col">#&nbsp;<?= gettext("Responses");?></th>
                        <?php if(!$state_filter && $_COMPANY->getAppCustomization()['surveys']['approvals']['enabled']) { ?>
                        <th width="5%" class="color-black" scope="col"><?= gettext("Approval Status");?></th>
                        <?php } ?>
                        <th width="2%" class="color-black" scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Define sorting order
                $orderMap = ['requested' => 1,'processing' => 2,'approved' => 3,'denied' => 4,'reset' => 5];
                $x = 0;	foreach($surveys as $survey){
                    $createdbyUser = User::GetUser($survey->val('createdby'));
                    $createrName = $createdbyUser ? $createdbyUser-> getFullName() : '';

                        // Check for Request Approval if active
                    if($_COMPANY->getAppCustomization()['surveys']['approvals']['enabled']){
                        $approval = $survey->getApprovalObject() ? $survey->getApprovalObject(): '';
                        $topicType = Teleskope::TOPIC_TYPES['SURVEY'];
                    }

                    if (!$state_filter && $survey->val('isactive') == '1') {
                        continue;
                    }

                    $approvalStatus = '';
                    $approval_stage = 0;
                    if(!$state_filter && $approval){
                         if (!empty($approval->val('approval_stage'))) {
                             $approval_stage = $approvalStatus = $approvalStatus = ucwords($approval->val('approval_status'));
                             $approvalStatus .= in_array($approval->val('approval_status'), [Approval::TOPIC_APPROVAL_STATUS['PROCESSING'], Approval::TOPIC_APPROVAL_STATUS['REQUESTED']]) ? ('<br>'. gettext(' Stage ') . $approval->val('approval_stage')) : '';
                         }
                    }
                    $color = "none";
                    $status = "";
                    $disabled = "disabledlink";
                    if ($survey->val('isactive') == '2'){
                        $color = "#00968829";
                        $status = "<small>[".gettext("Draft")."]</small>";
                    } else if ($survey->val('isactive') == '1' ) {
                        $color = "#ffffff";
                        $disabled ='';
                    } else if ($survey->val('isactive') == '0') {
                        $color = "#ffffce";
                        $status = "<small>[".gettext("Inactive")."]</small>";
                    } elseif($survey->val('isactive') == '3') {
                        $color = "#00968829";
                        $status = "<small>[".gettext("Under Review")."]</small>";
                    }else{
                        $color = "#fde1e1";
                    }
                    $surveyId = $_COMPANY->encodeId($survey->val('surveyid'));
                    $encGroupId=$_COMPANY->encodeId(0);
                    $canManage = $_USER->isAdmin();
                ?>
                    <tr id="s<?= $x+1; ?>" style="background-color: <?= $color; ?>;">
                        <td>
                            <strong id="name<?= $surveyId;?>"><?= $survey->val('surveyname'); ?></strong>
                            <?=  $status; ?>
                        </td>
                        <td><?= $surveyType[$survey->val('surveysubtype')]; ?></td>
                        <td class="text-center"><?= $createrName; ?></td>
                        <td style="font-size: small;">
                            <?=gettext("Anonymous")?>:&nbsp;<?= $survey->val('anonymous') ? gettext("Yes") : gettext("No"); ?>,<br>
                            <?=gettext("Is Required")?>:&nbsp;<?= $survey->val('is_required') ? gettext("Yes") : gettext("No"); ?>,<br>
                            <?=gettext("Multiple&nbsp;Responses")?>:&nbsp;<?= $survey->val('allow_multiple') ? gettext("Yes") : gettext("No"); ?>
                        </td>
                        <td class="text-center"><?= $survey->getSurveyResponsesCount(); ?></td>
                        <?php if(!$state_filter && $_COMPANY->getAppCustomization()['surveys']['approvals']['enabled']) { ?>
                            <td class="text-center" data-order="<?=$orderMap[strtolower($approval_stage)];?>" ><?=$approvalStatus; ?></td>
                            <?php } ?>
                        <td>
                        <?php
                            include(__DIR__ . '/survey_action_button.template.php');
                        ?>
                        </td>
                    </tr>
                <?php $x++; } ?>	
                </tbody>										
            </table>
        </div>			
	</div>
</div>
</div>
<script>
	$(document).ready(function() {
		let table = $('#surveylist').DataTable( {
			"order": [],
			"bPaginate": true,
			"bInfo" : false,
            "drawCallback": function() {
                setAriaLabelForTablePagination(); 
            },           
            "initComplete": function(settings, json) {                            
                setAriaLabelForTablePagination(); 
                $('.current').attr("aria-current","true");  
            },
            'language': {
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },	
            columnDefs: [
            { targets: [-1], orderable: false }
            ]
			
		});
        // Code for Accessiblity screen reading.
        screenReadingTableFilterNotification('#surveylist',table); 
	});
    
</script>
