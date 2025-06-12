<?php
global $_USER, $_COMPANY;
$opts = 0;
$enc_postid = $_COMPANY->encodeId($post->id());
$enc_groupid = $_COMPANY->encodeId($post->val('groupid'));
$enc_chapterid = $_COMPANY->encodeId(0);
$enc_channelid = $_COMPANY->encodeId($post->val('channelid'));
$showPublishButton = true;
?>

<?php if ($_COMPANY->getAppCustomization()['post']['pinning']['enabled']) { ?>
<?php if ($isAllowedToPublishContent && $post->isActive() && empty($post->val('listids')) && !$post->val('pin_to_top')){ $opts++; ?>
<li><a role="button" href="javascript:void(0)" id="postActionPin_<?=$enc_postid ?>" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= sprintf(gettext('Are you sure you want to pin this %s to show on top in the Home tab'),Post::GetCustomName(false)); ?>?" onclick="pinUnpinAnnouncement('<?= $enc_groupid; ?>','<?=$enc_postid ?>','1')"><i class="fa fa-thumbtack" aria-hidden="true"></i>&emsp;<?= sprintf(gettext("Pin %s"),Post::GetCustomName(false))?></a></li>
<?php } ?>

<?php if ($isAllowedToPublishContent && $post->isActive() && empty($post->val('listids')) && $post->val('pin_to_top')){ $opts++; ?>
    <li><a role="button" href="javascript:void(0)" id="postActionUnpin_<?=$enc_postid ?>" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= sprintf(gettext('Are you sure you want to unpin this %s?'),Post::GetCustomName(false)); ?>" onclick="pinUnpinAnnouncement('<?= $enc_groupid; ?>','<?=$enc_postid ?>','2')"><i class="fa fa-thumbtack" aria-hidden="true"></i>&emsp;<?= sprintf(gettext("Unpin %s"),Post::GetCustomName(false))?></a></li>
<?php } ?>
<?php } ?>

<?php if ($isAllowedToUpdateContent && !$post->isAwaiting()) { $opts++; ?>
<li><a role="button" href="javascript:void(0)" id="postActionEdit_<?=$enc_postid ?>" class="" onclick="updateAnnouncement('<?= $enc_postid ?>')"><i class="fa fas fa-edit" aria-hidden="true"></i>&emsp;<?= gettext('Edit'); ?></a></li>
<?php } ?>

<?php if ($_USER->canPublishContentInCompanySomething() && $post->isActive()){ $opts++; ?>
<li><a role="button" href="javascript:void(0)" id="postActionShareByEmail_<?=$enc_postid ?>" class="" onclick="showSharePostFormDynamic('<?= $enc_postid; ?>')"><i class="fa fa-share-alt" aria-hidden="true"></i>&emsp;<?= gettext('Share By email'); ?></a></li>
<?php } ?>

<?php if ($post->isActive()){ $opts++; ?>
<li><a role="button" href="javascript:void(0)" id="postActionGetShareableLink_<?=$enc_postid ?>" class="" onclick="getShareableLink('<?= $enc_groupid; ?>','<?=$enc_postid ?>','1')" onkeypress="getShareableLink('<?= $enc_groupid; ?>','<?=$enc_postid ?>','1')"><i class="fa fa-share-square" aria-hidden="true"></i>&emsp;<?= gettext('Get Shareable Link'); ?></a></li>
<?php } ?>

<?php if(($isAllowedToPublishContent || $isAllowedToUpdateContent) && ($post->isDraft() || $post->isUnderReview())){ $opts++; ?>
<li><a role="button" href="javascript:void(0)" id="postActionEmailReview_<?=$enc_postid ?>" class="" onclick="openAnnouncementReviewModal('<?= $enc_groupid; ?>','<?= $enc_postid ?>');"><i class="fa fa-tasks" aria-hidden="true"></i>&emsp;<?= gettext('Email Review'); ?></a></li>
<?php } ?>

<?php if ($_COMPANY->getAppCustomization()['post']['approvals']['enabled'] && ($post->isUnderReview() || $post->isDraft() ))   { $opts++; $showPublishButton = false; ?>
    <?php if((empty($approval) || $approval->isApprovalStatusDenied() || $approval->isApprovalStatusReset() || $approval->isApprovalStatusCancelled()) && ($isAllowedToUpdateContent || $isAllowedToPublishContent)){?>
        <li><a role="button" class=" " href="javascript:void(0);" onclick="openApprovalNoteModal('<?= $enc_postid ?>', '<?=$topicType?>' );"><i class="fa fa-check-square" aria-hidden="true"></i>&emsp;<?= empty($approval) ? gettext("Request Approval") : gettext("Request Approval Again"); ?></a></li>
    <?php }?>
    <?php if(!empty($approval)){?>
        <li><a role="button" class=" " href="javascript:void(0);" role="link" onclick="viewApprovalStatus('<?= $enc_postid ?>','<?=$topicType?>')"><i class="fa fa-check-square" aria-hidden="true"></i>&emsp;<?= gettext("View Approval Status"); ?></a></li>
    <?php } ?>
    <?php if(!empty($approval) && ($approval->isApprovalStatusProcessing() || $approval->isApprovalStatusRequested())){?>
        <li><a role="button" class=" " href="javascript:void(0);" role="link" onclick="cancelApprovalStatus('<?= $enc_postid ?>','<?=$topicType?>')"><i class="fa fa-times" aria-hidden="true"></i>&emsp;<?= gettext("Cancel Approval Request"); ?></a></li>
    <?php } ?>
    <?php if (!empty($approval) && $approval->isApprovalStatusApproved() ){ $showPublishButton =true; }?>
<?php } ?>

<?php if($isAllowedToPublishContent && ( ($post->isDraft() && !$_COMPANY->getAppCustomization()['post']['require_email_review_before_publish']) || $post->isUnderReview()) && $showPublishButton ){ $opts++; ?>
    <?php
    // Disclaimer check before publish
    $checkDisclaimerExists = Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['POST_PUBLISH_BEFORE'], $post->val('groupid')); 
    if($checkDisclaimerExists){
        $call_method_parameters = array(
            $enc_groupid,
            $enc_postid,
        );
        $call_other_method = base64_url_encode(json_encode(
            array (
                "method" => "getAnnouncementScheduleModal",
                "parameters" => $call_method_parameters
            )
        ));
        $onClickFunc = "loadDisclaimerByHook('".$_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['POST_PUBLISH_BEFORE'])."','".$enc_groupid."', 0, '".$call_other_method."');";
    }else{
        $onClickFunc = "getAnnouncementScheduleModal('".$enc_groupid ."', '".$enc_postid."');";
    }
    ?>
<li><a role="button" href="javascript:void(0)" id="postActionPublish_<?=$enc_postid ?>" class="" onclick="<?= $onClickFunc ?>"  ><i class="fa fa-mail-bulk" aria-hidden="true"></i>&emsp;<?= gettext('Publish'); ?></a></li>
<?php } elseif($isAllowedToPublishContent && $post->isAwaiting()){ $opts++; ?>
<li><a role="button" href="javascript:void(0)" id="postActionCancelPublish_<?=$enc_postid ?>" class="" onclick="cancelAnnouncementPublishing('<?= $enc_groupid; ?>','<?= $enc_postid ?>');"  ><i class="fa fas fa-times" aria-hidden="true"></i>&emsp;<?= gettext('Cancel Publishing'); ?></a></li>
<?php } ?>

<?php if ($_COMPANY->getAppCustomization()['post']['email_tracking']['enabled'] && ($isAllowedToManageContent || $isAllowedToPublishContent) && $post->isActive()){ $opts++; ?>
<li><a role="button" href="javascript:void(0)" id="postActionEmailTracking_<?=$enc_postid ?>" class="" onclick="getEmailLogstatistics('<?= $enc_groupid; ?>','<?= $enc_postid ?>', '<?=  $_COMPANY->encodeId(1)?>')" ><i class="fa far fa-chart-bar" aria-hidden="true"></i>&emsp;<?= gettext('Email Tracking'); ?></a></li>
<?php } ?>
<?php if ($_USER->canCreateContentInGroupSomething($post->val('groupid'))) { $opts++; ?>
<li><a role="button" href="javascript:void(0)" title="<?= gettext('Clone');?>" onclick="cloneAnnouncementForm('<?= $enc_groupid; ?>','<?= $enc_postid; ?>')" ><i class="fa fa-clone" aria-hidden="true"></i>&emsp;<?= gettext('Clone');?></a></li>
<?php } ?>

<?php if (($isAllowedToUpdateContent || $isAllowedToPublishContent) && !$post->isAwaiting()) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" id="postActionDelete_<?=$enc_postid ?>" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>"  title="<?= sprintf(gettext('Are you sure you want to delete this %s'),Post::GetCustomName(false)); ?>?" onclick="deleteAnnouncement('<?= $enc_postid ?>','<?= $enc_groupid ?>','<?= $enc_chapterid; ?>','<?= $enc_channelid; ?>',<?= (basename($_SERVER['PHP_SELF']) =='viewpost.php')?'true':'false'?>,<?= ($post->val('groupid')== 0)?'true':'false' ?>)" ><i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= gettext('Delete'); ?></a></li>
<?php } ?>

<?php if (!$opts){  ?>
<li><a role="button" href="javascript:void(0)" id="postActionNoOptions_<?=$enc_postid ?>" class="disabled"><i class="fa fas fa-exclamation" aria-hidden="true"></i>&emsp;<?= gettext('No options available'); ?></a></li>
<?php } ?>
