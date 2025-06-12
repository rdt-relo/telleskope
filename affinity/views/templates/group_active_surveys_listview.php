<div class="col-md-12">
    <div class="row">
        <div class="col-12">
            <h2><?= gettext("Manage Surveys").' - '. $group->val('groupname_short');?></h2>
            <hr class="lineb" >
        </div>
    <?php
        if ($_USER->canManageGroup($groupid) ||
            ($_COMPANY->getAppCustomization()['surveys']['allow_create_chapter_scope'] && $_USER->canManageGroupSomeChapter($groupid)) ||
            ($_COMPANY->getAppCustomization()['surveys']['allow_create_channel_scope'] && $_USER->canManageGroupSomeChapter($groupid))
        ) {
            if(Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['SURVEY_CREATE_BEFORE'])){
                $callOtherMethod = base64_encode(json_encode(array("method"=>"openSurveySettingForm","parameters"=>array($encGroupId,$_COMPANY->encodeId(0))))); // base64_encode for prevent js parsing error
                $newbtn = '<a href="javascript:void(0)" class="btn btn-affinity pull-right mt-0" onclick="loadDisclaimerByHook(\'' . $_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['SURVEY_CREATE_BEFORE']) .  '\', \'' . $_COMPANY->encodeId(0) . '\',0, \'' . $callOtherMethod.'\')" >' . gettext("Create Survey") . '</a>';
           
            } else {
                $newbtn = '<a href="javascript:void(0)" class="btn btn-affinity pull-right mt-0" onclick="openSurveySettingForm(\''.$encGroupId.'\',\''.$_COMPANY->encodeId(0).'\')" >'.gettext("Create Survey").'</a>';
            }
            include(__DIR__ . "/manage_section_dynamic_button.html");
        }
    ?>


        <div class="col-md-12">
            <div class="col-md-4">
                <div class="form-group col-md-12 ">
                    <label for="filterByState" style="font-size:small;"><?= sprintf(gettext("Filter by %s State"), 'Survey');?></label>
                    <select class="form-control" onchange="getGroupSurveys('<?= $encGroupId; ?>',1);" id="filterByState" style="font-size:small;border-radius: 5px;">
                      <option value="<?= $_COMPANY->encodeId(2)?>" <?= $state_filter==2 ? 'selected' : '' ?> ><?= gettext("Draft / Inactive");?></option>
                      <option value="<?= $_COMPANY->encodeId(1)?>" <?= $state_filter==1 ? 'selected' : '' ?> ><?= gettext("Active");?></option>
                    </select>
                  </div>

            </div>
            <div class="col-md-4">
                <div class="form-group col-md-12 ">
                <?php if($groupid>0){ 
                  $chapters = Group::GetChapterList($groupid);
                  $channels= Group::GetChannelList($groupid);
                  ?>
                  
                    <label for="filterByGroup" style="font-size:small;"><?= gettext("Filter by Scope");?></label>
                    <select id="filterByGroup"  onchange="getGroupSurveys('<?= $encGroupId; ?>',1);" style="font-size:small;border-radius: 5px;" class="form-control" >
                      <?php if ($_USER->canCreateOrPublishOrManageContentInScopeCSV($groupid,0,0)) { ?>
                      <option data-section="<?= $_COMPANY->encodeId(0) ?>" value="<?= $_COMPANY->encodeId(0);?>" <?= $erg_filter_section == 0 && $erg_filter == 0 ? 'selected' : '' ?> ><?= Group::GetGroupName($groupid)?></option>
                      <?php } ?>
                  <?php if ($chapters) { ?>
                    <optgroup label="<?=$_COMPANY->getAppCustomization()['chapter']['name-short-plural']?>">
                    <?php foreach ($chapters as $chapter) { ?>
                      <?php if ($_USER->canCreateOrPublishOrManageContentInScopeCSV($groupid,$chapter['chapterid'],0)){ ?>
                        <option data-section="<?= $_COMPANY->encodeId(1) ?>" value="<?= $_COMPANY->encodeId($chapter['chapterid'])?>" <?= $erg_filter_section == 1 && $erg_filter == $chapter['chapterid'] ? 'selected' : '' ?> ><?= htmlspecialchars($chapter['chaptername']); ?></option>
                      <?php } ?>
                    <?php
                    }
                    ?>
                    </optgroup>
                  <?php } ?>
                  <?php if ($channels) { ?>
                    <optgroup label="<?=$_COMPANY->getAppCustomization()['channel']['name-short-plural']?>">
                    <?php foreach ($channels as $channel) {     ?>
                      <?php if ($_USER->canCreateOrPublishOrManageContentInScopeCSV($groupid,0,$channel['channelid'])){ ?>
                        <option data-section="<?= $_COMPANY->encodeId(2) ?>" value="<?= $_COMPANY->encodeId($channel['channelid'])?>" <?= $erg_filter_section == 2 && $erg_filter == $channel['channelid'] ? 'selected' : '' ?> ><?= htmlspecialchars($channel['channelname']); ?></option>
                      <?php } ?>
                    <?php
                    }
                    ?>
                    </optgroup>
                  <?php } ?>
                  </select>
                  
                <?php } ?>
                </div>
            </div>
        </div>
        <div class="col-md-12 mt-2">
        <div class="table-responsive">
                <table id="surveylist" class="display table table-hover compact" summary="This table display the list of surveys">											
                    <thead>
                        <tr>
                            <th width="40%" class="color-black" scope="col"><?= gettext("Survey Name");?></th>
                            <th width="5%" class="color-black" scope="col"><?= gettext("Survey Type");?></th>
                        <?php if ($_ZONE->val('app_type') != 'talentpeak') { ?>
                            <th width="10%" class="color-black" scope="col"><?= gettext("Scope");?></th>
                        <?php } ?>
                            <th width="5%" class="color-black" scope="col"><?= gettext("Creator");?></th>
                            <th width="5%" class="color-black" scope="col"><?= gettext("Settings");?></th>
                            <th width="5%" class="color-black" scope="col">#&nbsp;<?= gettext("Responses");?></th>
                            <?php if(!$state_filter && $_COMPANY->getAppCustomization()['surveys']['approvals']['enabled']) { ?>
                            <th width="5%" class="color-black" scope="col"><?= gettext("Approval Status");?></th>
                            <?php } ?>
                            <th width="2%" scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php   // Define sorting order
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
                        $regionid = 0; // @Todo For future implementation
                        $canManage = $_USER->canManageContentInScopeCSV($survey->getGroupId(), $survey->getChapterId(),$survey->getChannelId());

                        if (!$canManage) {
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
                    ?>
                        <tr id="s<?= $x+1; ?>" style="background-color: <?= $color; ?>;">
                            <td>
                                <strong id="name<?= $surveyId;?>"><?= $survey->val('surveyname'); ?></strong>
                                <?= $status; ?>
                            </td>
                            <td>
                                <?= $survey->getSurveyTriggerLabel(); ?>
                            </td>
                        <?php if ($_ZONE->val('app_type') != 'talentpeak') { ?>
                            <?php if ($survey->getChapterId() ==0 && $survey->getChannelId()==0) { ?>
                            <td>
                                <?=$_COMPANY->getAppCustomization()['group']['name-short']?>
                            </td>
                            <?php } else { ?>
                            <td>
                                <?= $survey->getChapterId() ? '<i class="fas fa-globe" style="" aria-hidden="true"></i> '.Group::GetChapterName($survey->getChapterId(),$survey->getGroupId())['chaptername'] : ''; ?>
                                <?= $survey->getChannelId() ? '<i class="fas fa-layer-group" style="" aria-hidden="true"></i> '.htmlspecialchars(Group::GetChannelName($survey->getChannelId(),$survey->getGroupId())['channelname']) : ''; ?>
                            </td>
                            <?php } ?>
                        <?php } ?>
                            <td class="text-center"><?= $createrName ?></td>
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
  var table = $('#surveylist').DataTable({
    "bPaginate": true,
    "bInfo": false,
    "aoColumnDefs": [{
      "bSortable": false,
      "aTargets": [-1]
    }],
    "drawCallback": function() {
        setAriaLabelForTablePagination(); 
    },
    "initComplete": function(settings, json) {                            
        setAriaLabelForTablePagination(); 
        $('.current').attr("aria-current","true");  
    },
    'language': {
      searchPlaceholder: "...",
      "sZeroRecords": "<?= gettext('No data available in table');?>",
      url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
    }   
  });
  screenReadingTableFilterNotification('#surveylist',table);
});

</script>
