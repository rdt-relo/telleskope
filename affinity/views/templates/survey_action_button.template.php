<?php 
$showActivateButton = true;
    $isActionDisabledDuringApprovalProcess = $survey->isActionDisabledDuringApprovalProcess();
?>
<div class="" style="color: #fff; float: left;">
    <button aria-label="<?= sprintf(gettext('%1$s Survey action dropdown'), $survey->val('surveyname'))?>" class="btn-no-style dropdown-toggle fa fa-ellipsis-v mt-1" title="Action" type="button" data-toggle="dropdown"></button>
    <ul class="dropdown-menu dropdown-menu-right" style="width: 250px; cursor: pointer;">

        <?php if ($canManage && ($survey->isDeleted())) { ?>
        <li>
            <a role="button" class="confirm" href="javascript:void(0)" onclick="activateDeactivateSurvey('<?= $encGroupId; ?>','<?= $surveyId; ?>',0)" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to undo delete?');?>"><i class="fa fa-undo" title="Undelete" aria-hidden="true"></i>&emsp;<?= gettext("Undo Delete");?></a>
        </li>
        <?php } ?>

        <?php if ($canManage && ($survey->isDraft() || $survey->isInactive() || $survey->isUnderReview())) { ?>
            <li>
            <a role="button" href="javascript:void(0)" class="" onclick="updateSurveyInfoModal('<?= $encGroupId; ?>','<?= $surveyId; ?>')"  title="Edit Survey Settings" ><i class="fa fas fa-edit" title="Edit Survey Settings" aria-hidden="true"></i>&emsp;<?= gettext("Edit Survey Settings");?>
            </a>
        </li>
        <li>
            <a role="button" class=""
            <?php if($isActionDisabledDuringApprovalProcess){?>
               onclick="showApprovalProcessAlert()" href = "javascript:void(0)"
            <?php }else{?>
                href="create_survey?surveyid=<?=$surveyId?>"
            <?php } ?> title="Edit Survey Questions" ><i class="fa fas fa-edit" title="Edit Survey Questions" aria-hidden="true"></i>&emsp;<?= gettext("Edit Survey Questions");?>
            </a>
        </li>
        <?php } ?>

        <?php if ($canManage  && ($survey->isDraft() || $survey->isUnderReview() || $survey->isInactive())) { ?>
            <li><a role="button" href="javascript:void(0)"  title="<?= gettext('Open Review Panel');?>"  onclick="openReviewSurveyModal('<?= $encGroupId; ?>','<?= $surveyId; ?>')"><i class="fa fa-tasks" aria-hidden="true"></i>&emsp;<?= gettext('Email Review');?></a></li>
        <?php } ?>

        <?php if ((!$survey->isDeleted())) { ?>
        <li>
            <a role="button" href="javascript:void(0)" onclick="previewSurvey('<?= $encGroupId; ?>','<?= $surveyId; ?>')" title="Preview" ><i class="fa fas fa-eye" title="view" aria-hidden="true"></i>&emsp;<?= gettext("View");?></a>
        </li>
        <?php } ?>

        <?php if ($_COMPANY->getAppCustomization()['surveys']['approvals']['enabled'] && ($survey->isUnderReview() || $survey->isDraft() ))   { $showActivateButton = false; ?>
                <?php if((empty($approval) || $approval->isApprovalStatusDenied() || $approval->isApprovalStatusReset() || $approval->isApprovalStatusCancelled())){?>
                    <li><a role="button" class=" " href="javascript:void(0);" onclick="openApprovalNoteModal('<?= $surveyId ?>', '<?=$topicType?>' );"><i class="fa fa-check-square" aria-hidden="true"></i>&emsp;<?= empty($approval) ? gettext("Request Approval") : gettext("Request Approval Again"); ?></a></li>
                <?php }?>
                <?php if(!empty($approval)){?>
                    <li><a role="button" class=" " href="javascript:void(0);" role="link" onclick="viewApprovalStatus('<?= $surveyId ?>','<?=$topicType?>')"><i class="fa fa-check-square" aria-hidden="true"></i>&emsp;<?= gettext("View Approval Status"); ?></a></li>
                <?php } ?>
                <?php if(!empty($approval) && ($approval->isApprovalStatusProcessing() || $approval->isApprovalStatusRequested())){?>
                    <li><a role="button" class=" " href="javascript:void(0);" role="link" onclick="cancelApprovalStatus('<?= $surveyId ?>','<?=$topicType?>')"><i class="fa fa-times" aria-hidden="true"></i>&emsp;<?= gettext("Cancel Approval Request"); ?></a></li>
                <?php } ?>
                <?php if (!empty($approval) && $approval->isApprovalStatusApproved() ){ $showActivateButton =true; }?>
        <?php } ?>

        <?php if ($canManage && (!$survey->isDeleted()) && (!$survey->isActive()) && $showActivateButton) { ?>
        <li>
            <a role="button" href="javascript:void(0)" 
            <?php if($_COMPANY->getAppCustomization()['surveys']['restrict_publish_to_admin_only'] && !$_USER->isAdmin()){ ?>
                onclick="showSurveyPublishRestrictionModal('<?= $encGroupId; ?>','<?= $surveyId; ?>')" 
            <?php } else { ?>
                class="confirm"
                data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>"
            <?php if($survey->val('surveytype') == Survey2::SURVEY_TRIGGER['ON_LOGIN'] && $survey->val('publishdate') && $survey->val('anonymous') != 1){ ?>
                onclick="loginSurveyReAnswerConfirmation('<?= $encGroupId; ?>','<?= $surveyId; ?>' )"
            <?php } else { ?>
                onclick="activateDeactivateSurvey('<?= $encGroupId; ?>','<?= $surveyId; ?>',0)"
            <?php } ?>  
                title="<?= gettext('Are you sure you want to activate this survey?');?>"
            <?php } ?>
            ><i class="fa fa-unlock" title="Activate" aria-hidden="true"></i>&emsp;<?= gettext("Activate");?></a>
        </li>
        <?php } ?>

        <?php if ($canManage && (!$survey->isDeleted()) && (!$survey->isActive())) { ?>
        <li>
            <a role="button" class="" href="javascript:deleteSurveyDataConfirmation('<?=$encGroupId?>', '<?=$surveyId?>')"><i class="fa fa-trash" title="Delete" aria-hidden="true"></i>&emsp;<?= gettext("Delete");?></a>

        </li>
        <?php } ?>

        <?php if ($canManage && ($survey->isActive())) { ?>
        <li>
            <a role="button" class="confirm" href="javascript:void(0)" onclick="activateDeactivateSurvey('<?= $encGroupId; ?>','<?= $surveyId; ?>',0)" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>"  title="<?= gettext('Are you sure you want to deactivate this survey?');?>"><i class="fa fa-lock" title="Deactivate" aria-hidden="true"></i>&emsp;<?= gettext("Deactivate");?></a>
        </li>
        <?php } ?>

        <?php if ($canManage && (!$survey->isDeleted()) && (!$survey->isDraft()) && (!$survey->isUnderReview()) ) { ?>
        <li>
            <a role="button" class="<?= $disabled; ?> js-download-link" href="ajax?download_survey2_report=<?= $surveyId; ?>&groupid=<?= $encGroupId; ?>"><i class="fa fa-download" title="Download" aria-hidden="true"></i>&emsp;<?= gettext("Download");?></a>
        </li>
        <?php } ?>

        <?php if ($canManage && (!$survey->isDeleted()) && (!$survey->isDraft()) && (!$survey->isUnderReview()) ) { ?>
            <li>
                <a role="button" class="<?= $disabled; ?>" href="javascript:importSurveyDataModal('<?=$encGroupId?>', '<?=$surveyId?>')"><i class="fa fa-upload" title="Import Survey Responses" aria-hidden="true"></i>&emsp;<?= gettext("Import Survey Responses");?></a>
            </li>
        <?php } ?>

        <?php if ($canManage && (!$survey->isDeleted()) && ($survey->val('is_template') == '0')) { ?>
            <li>
    <a role="button" style="max-width: 250px;" data-toggle="tooltip" class="confirm tool-tip" href="javascript:void(0)" onclick="shareUnshareSurvey('<?= $encGroupId; ?>','<?= $surveyId; ?>','<?= $_COMPANY->encodeId(0); ?>')" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to save this survey as Template?');?>" ><i class="fa fa-save" title="<?= gettext("Save as Template");?>" aria-hidden="true"></i>&emsp;<?= gettext("Save as Template");?></a>
            </li>
        <?php } else if ($canManage && (!$survey->isDeleted()) && ($survey->val('is_template') == '1')){ ?>
            <li>
                <a role="button" style="max-width: 250px;" data-toggle="tooltip" class="confirm tool-tip" href="javascript:void(0)" onclick="shareUnshareSurvey('<?= $encGroupId; ?>','<?= $surveyId; ?>','<?= $_COMPANY->encodeId(1); ?>')" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want this survey to be removed as Template?');?>" ><i class="fa fa-save" title="<?= gettext("Remove from Template");?>" aria-hidden="true"></i>&emsp;<?= gettext("Remove from Template");?></a>
            </li>
        <?php } ?>

        <?php if ($canManage && $survey->isActive() && ($survey->val('surveysubtype') == '127'  || (isset($options) && $options['days_from_start'] == '-2') )) { ?>
        <li>
            <a role="button" class="" href="javascript:void(0)" onclick="getShareableLink('<?= $encGroupId; ?>','<?= $surveyId; ?>',4)" ><i class="fa fa-share-square" title="Share link" aria-hidden="true"></i>&emsp;<?= gettext("Share Survey");?></a>
        </li>
        <?php } ?>
        <?php if ($_COMPANY->getAppCustomization()['surveys']['analytics'] &&
            $canManage && (!$survey->isDeleted()) && (!$survey->isDraft()) && (!$survey->isUnderReview())) { ?>
            <li>
                <a role="button" class="<?= $disabled; ?>" target="_blank" href="survey_analytics?surveyid=<?=  $surveyId;?>"><i class="fa fas fa-chart-pie" aria-hidden="true"></i>&emsp;<?= gettext("Analytics");?></a>
            </li>
        <?php } ?>
        <?php if (!$canManage && ($survey->isDeleted())) { ?>
            <li>
                &emsp;- <?= gettext("No options available");?> -
            </li>
        <?php } ?>
    </ul>
</div>



<script>
    async function loginSurveyReAnswerConfirmation(g,s){
        const { value: requestNew } = await swal.fire({
            title: '<?= addslashes(gettext("Attention!"))?>',
            html: '<p><?= addslashes(gettext("Do you want to request new survey responses from users who responded to this survey in the past?"))?></small>',
            input: 'radio',
            inputOptions : {
                'N': 'No',
                'Y': 'Yes'
            },
            inputValue: 'N',
            confirmButtonText:
            '<?= gettext("Continue")?>'
        });
        if (requestNew == 'Y' ) {
            activateDeactivateSurvey(g,s,1);
        } else {
            activateDeactivateSurvey(g,s,0);
        }
    }
</script>
<script>
    function showApprovalProcessAlert(){
        let message = ` <?=gettext("This survey is currently in the approval process or has been approved. Changes to Survey Questions are not permitted. <br><br>To make changes, request the survey approver to deny the approval.")?>`;
        swal.fire({
            title: '', 
            html: message});
    }
</script>