<?php
global $_USER, $_COMPANY;
$opts = 0;
$enc_discussionid = $_COMPANY->encodeId($discussion->id());

$isAllowedToUpdateContent =
    $_USER->id()==$discussion->val('createdby') ||
    $_USER->canUpdateContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid'),$discussion->val('isactive'));
$isAllowedToPublishContent = $_USER->canPublishContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid'));
$isAllowedToManageContent = $_USER->canManageContentInScopeCSV($discussion->val('groupid'),$discussion->val('chapterid'),$discussion->val('channelid'));

?>
<?php if($isAllowedToPublishContent && $_COMPANY->getAppCustomization()['discussions']['pinning']['enabled']) { ?>

<?php if ($discussion->isActive() && !$discussion->val('pin_to_top')){ $opts++; ?>
<li><a tabindex="0" class="pop-identifier confirm"
            data-toggle="popover" href="javascript:void(0);" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to pin this discussion to show on top in the Home tab'); ?>?" onclick="pinUnpinDiscussion('<?= $enc_groupid; ?>','<?=$enc_discussionid ?>','1')"><i class="fa fa-thumbtack" aria-hidden="true"></i>&emsp;<?= gettext('Pin Discussion'); ?></a></li>
<?php } ?>

<?php if ($discussion->isActive() && $discussion->val('pin_to_top')){ $opts++; ?>
    <li><a tabindex="0" data-toggle="popover" class="pop-identifier confirm" href="javascript:void(0);" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to unpin this discussion?'); ?>" onclick="pinUnpinDiscussion('<?= $enc_groupid; ?>','<?=$enc_discussionid ?>','2')"><i class="fa fa-thumbtack" aria-hidden="true"></i>&emsp;<?= gettext('Unpin Discussion'); ?></a></li>
<?php } ?>
<?php } ?>

<?php if ($isAllowedToUpdateContent) { $opts++; ?>
<li><a tabindex="0" class="" href="javascript:void(0);" onclick="openCreateDiscussionModal('<?= $enc_groupid ?>','<?= $enc_discussionid ?>')"><i class="fa fas fa-edit" aria-hidden="true"></i>&emsp;<?= gettext('Edit'); ?></a></li>
<?php } ?>

<?php if ($discussion->isActive()){ $opts++; ?>
<li><a tabindex="0" class="" href="javascript:void(0);" onclick="getShareableLink('<?= $enc_groupid; ?>','<?=$enc_discussionid ?>','6')" onkeypress="getShareableLink('<?= $enc_groupid; ?>','<?=$enc_discussionid ?>','6')"><i class="fa fa-share-square" aria-hidden="true"></i>&emsp;<?= gettext('Get Shareable Link'); ?></a></li>
<?php } ?>

<?php if (($isAllowedToUpdateContent || $isAllowedToPublishContent)) { $opts++; ?>
    <li><a tabindex="0" href="javascript:void(0);" class="pop-identifier confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>"  title="<?= gettext('Are you sure you want to delete this discussion'); ?>?" onclick="initPermanentDeleteConfirmation('<?= $enc_discussionid ?>','<?= Teleskope::TOPIC_TYPES['DISCUSSION']; ?>',<?= (basename($_SERVER['PHP_SELF']) =='viewdiscussion.php')?'true':'false'?>)" ><i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= gettext('Delete'); ?></a></li>
<?php } ?>

<?php if (!$opts){  ?>
<li><a tabindex="0" class="disabled"><i class="fa fas fa-exclamation" aria-hidden="true"></i>&emsp;<?= gettext('No options available'); ?></a></li>
<?php } ?>
