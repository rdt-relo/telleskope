<?php
global $_USER, $_COMPANY;
$opts = 0;
$enc_newsletterid = $_COMPANY->encodeId($newsletter->id());
$enc_groupid = $_COMPANY->encodeId($newsletter->val('groupid'));
//$enc_chapterid = $_COMPANY->encodeId($newsletter->val('chapterid'));
//$enc_channelid = $_COMPANY->encodeId($newsletter->val('channelid'));
$showPublishButton = true;
?>

<?php if ($_COMPANY->getAppCustomization()['newsletters']['pinning']['enabled'] && $isAllowedToUpdateContent && $newsletter->isPublished()){ $opts++; ?>
    <?php if ($newsletter->val('pin_to_top')) { ?>
        <li><a role="button" href="javascript:void(0)" id="newsletterActionUnpin_<?=$enc_newsletterid ?>" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to unpin this Newsletter?'); ?>" onclick="pinUnpinNewsletter('<?= $enc_groupid; ?>','<?=$enc_newsletterid ?>','2')"><i class="fa fa-thumbtack" aria-hidden="true"></i>&emsp;<?= sprintf(gettext("Unpin %s"),'Newsletter')?></a></li>
    <?php } else { ?>
        <li><a role="button" href="javascript:void(0)" id="newsletterActionPin_<?=$enc_newsletterid ?>" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to pin this newsletter to show on top in the Home tab'); ?>?" onclick="pinUnpinNewsletter('<?= $enc_groupid; ?>','<?=$enc_newsletterid ?>','1')"><i class="fa fa-thumbtack" aria-hidden="true"></i>&emsp;<?= sprintf(gettext("Pin %s"),'Newsletter')?></a></li>
    <?php } ?>

<?php } ?>

<?php if ($isAllowedToUpdateContent && !$newsletter->isAwaiting() && !$newsletter->isPublished()) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" title="<?= gettext('Edit');?>" onclick=initUpdateNewsletter("<?= $enc_groupid; ?>","<?= $enc_newsletterid; ?>") ><i class="fa fas fa-edit" aria-hidden="true"></i>&emsp;<?= gettext('Edit');?></a></li>
<?php } ?>

<?php if (($isAllowedToUpdateContent || $isAllowedToPublishContent || $isAllowedToManageContent) || $newsletter->isPublished()) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" title="<?= gettext('Preview Newsletter');?>" onclick=previewNewsletter("<?= $enc_groupid; ?>","<?= $enc_newsletterid; ?>") ><i class="fa fa-eye" aria-hidden="true"></i>&emsp;<?= gettext('Preview Newsletter');?></a></li>
<?php } ?>

<?php if (($isAllowedToUpdateContent || $isAllowedToPublishContent || $isAllowedToManageContent) || $newsletter->isPublished()) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" title="<?= gettext('Attachments');?>" onclick=manageNewsletterAttachments("<?= $enc_groupid; ?>","<?= $enc_newsletterid; ?>") ><i class="fa fa-paperclip" aria-hidden="true"></i>&emsp;<?= gettext('Attachments');?></a></li>
<?php } ?>

<?php if ($newsletter->isPublished()){ $opts++; ?>
    <li><a role="button" class="" href="javascript:void(0)" onclick="getShareableLink('<?= $enc_groupid; ?>','<?= $enc_newsletterid; ?>','3')" ><i class="fa fa-share-square" title="Share link" aria-hidden="true"></i>&emsp;<?= gettext('Share Newsletter');?></a></li>
<?php } ?>

<?php if (($isAllowedToPublishContent || $isAllowedToUpdateContent) && ($newsletter->isDraft() || $newsletter->isUnderReview())) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" title="<?= gettext('Open Review Panel');?>"  onclick=openReviewNewsletterModal("<?= $enc_groupid; ?>","<?= $enc_newsletterid; ?>")><i class="fa fa-tasks" aria-hidden="true"></i>&emsp;<?= gettext('Email Review');?></a></li>
<?php } ?>

<?php if ($_COMPANY->getAppCustomization()['newsletters']['approvals']['enabled'] && ($newsletter->isUnderReview() || $newsletter->isDraft() ))   { $opts++; $showPublishButton = false; ?>
    <?php if((empty($approval) || $approval->isApprovalStatusDenied() || $approval->isApprovalStatusReset() || $approval->isApprovalStatusCancelled()) && ($isAllowedToUpdateContent || $isAllowedToPublishContent)){?>
        <li><a role="button" class=" " href="javascript:void(0);" onclick="openApprovalNoteModal('<?= $enc_newsletterid ?>', '<?=$topicType?>' );"><i class="fa fa-check-square" aria-hidden="true"></i>&emsp;<?= empty($approval) ? gettext("Request Approval") : gettext("Request Approval Again"); ?></a></li>
    <?php }?>
    <?php if(!empty($approval)){?>
        <li><a role="button" class=" " href="javascript:void(0);" role="link" onclick="viewApprovalStatus('<?= $enc_newsletterid ?>','<?=$topicType?>')"><i class="fa fa-check-square" aria-hidden="true"></i>&emsp;<?= gettext("View Approval Status"); ?></a></li>
    <?php } ?>
    <?php if(!empty($approval) && ($approval->isApprovalStatusProcessing() || $approval->isApprovalStatusRequested())){?>
        <li><a role="button" class=" " href="javascript:void(0);" role="link" onclick="cancelApprovalStatus('<?= $enc_newsletterid ?>','<?=$topicType?>')"><i class="fa fa-times" aria-hidden="true"></i>&emsp;<?= gettext("Cancel Approval Request"); ?></a></li>
    <?php } ?>
    <?php if (!empty($approval) && $approval->isApprovalStatusApproved() ){ $showPublishButton =true; }?>
<?php } ?>

<?php if($isAllowedToPublishContent && ($newsletter->isUnderReview() || ($newsletter->isDraft() && !$_COMPANY->getAppCustomization()['newsletters']['require_email_review_before_publish']) ) && $showPublishButton ){ $opts++; ?>

    <?php
    // Disclaimer check before publish
    $checkDisclaimerExists = Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['NEWSLETTER_PUBLISH_BEFORE'], $newsletter->val('groupid')); 
    if($checkDisclaimerExists){
        $call_method_parameters = array(
            $enc_groupid,
            $enc_newsletterid,
        );
        $call_other_method = base64_url_encode(json_encode(
            array (
                "method" => "openPublishNewsletterModal",
                "parameters" => $call_method_parameters
            )
        ));
        $onClickFunc = "loadDisclaimerByHook('".$_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['NEWSLETTER_PUBLISH_BEFORE'])."','".$enc_groupid."', 0, '".$call_other_method."');";
    }else{
        $onClickFunc = "openPublishNewsletterModal('".$enc_groupid ."', '".$enc_newsletterid."');";
    }
    ?>
 <li><a role="button" href="javascript:void(0)" title="<?= gettext('Publish Newsletter');?>"  onclick="<?=$onClickFunc?>"><i class="fa fa-mail-bulk" aria-hidden="true"></i>&emsp;<?= gettext('Publish Newsletter');?></a></li>

<?php } elseif($isAllowedToPublishContent && $newsletter->isAwaiting()){ $opts++; ?>
<li><a role="button" href="javascript:void(0)" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Cancel Publishing'); ?>" onclick=cancelNewsletterPublishing("<?= $enc_groupid; ?>","<?= $enc_newsletterid; ?>")><i class="fa fas fa-times" aria-hidden="true"></i>&emsp;<?= gettext('Cancel Publishing');?></a></li>
<?php } elseif ($isAllowedToPublishContent && $newsletter->isPublished()) { $opts++; ?>
<li><a role="button" href="javascript:void(0)" title="<?= gettext('Unpublish Newsletter');?>"  onclick=openUnPublishNewsletterModal('<?= $enc_groupid; ?>','<?= $enc_newsletterid; ?>')><i class="fa fa-mail-bulk" aria-hidden="true"></i>&emsp;<?= gettext('Unpublish Newsletter');?></a></li>
<?php } ?>

<?php if ($_USER->canCreateContentInGroupSomething($newsletter->val('groupid'))) { $opts++; ?>
<li><a role="button" href="javascript:void(0)" title="<?= gettext('Clone');?>" onclick=cloneNewsLetterForm("<?= $enc_groupid; ?>","<?= $enc_newsletterid; ?>") ><i class="fa fa-clone" aria-hidden="true"></i>&emsp;<?= gettext('Clone');?></a></li>
<?php } ?>

<?php if ($_COMPANY->getAppCustomization()['newsletters']['email_tracking']['enabled'] && ($isAllowedToManageContent || $isAllowedToPublishContent) && ($newsletter->isPublished() || ($newsletter->isDraft() && !empty($newsletter->val('publishdate'))))){ $opts++; ?>
<li><a role="button" href="javascript:void(0)" class="" onclick="getEmailLogstatistics('<?= $enc_groupid; ?>','<?= $enc_newsletterid ?>', '<?=  $_COMPANY->encodeId(3)?>')" ><i class="fa far fa-chart-bar" aria-hidden="true"></i>&emsp;<?= gettext('Email Tracking');?></a></li>
<?php } ?>

<?php if (($isAllowedToUpdateContent || $isAllowedToPublishContent) && !$newsletter->isAwaiting()) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" class="confirm"  onclick=deleteNewsletter("<?= $enc_groupid; ?>","<?= $enc_newsletterid; ?>") data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to delete this newsletter?');?>" ><i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= gettext('Delete');?></a></li>
<?php } ?>

<?php if (!$opts){  ?>
    <li><a role="button" href="javascript:void(0)" class="disabled"><i class="fa fas fa-exclamation" aria-hidden="true"></i>&emsp;<?= gettext('No options available');?></a></li>
<?php } ?>
