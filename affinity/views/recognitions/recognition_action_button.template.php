<?php
$opts = 0;
$enc_recognitionid = $_COMPANY->encodeId($recognition->id());
$enc_groupid = $_COMPANY->encodeId($recognition->val('groupid'));
$checkform = (!empty($checkform))?$checkform:0;
?>



<?php if (($_USER->isGrouplead($recognition->val('groupid')) && $_USER->canPublishContentInGroup($recognition->val('groupid'))) || ($_USER->id() == $recognition->val('createdby'))) { $opts++;
    $who_recognitions = Recognition::RECOGNITION_TYPES['recognize_a_colleague'];

    if($recognition->val('createdby') == $recognition->val('recognizedto')){ 
        $who_recognitions = Recognition::RECOGNITION_TYPES['recognize_my_self'];
    }

    $who_recognitions = $_COMPANY->encodeId($who_recognitions);
?>

    <li><a role="button" href="javascript:void(0)"  onclick="editRecognition('<?= $enc_groupid ?>','<?= $enc_recognitionid ?>','<?= $who_recognitions ?>','<?=$checkform;?>')" ><i class="fa fa-edit" aria-hidden="true"></i>&emsp;<?= gettext('Edit'); ?></a></li>
<?php } ?>
<?php if ($_USER->canManageGroupSomething($recognition->val('groupid')) || $_USER->id() == $recognition->val('createdby')) { $opts++; ?>
    <li><a role="button" href="javascript:void(0)" class="confirm" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>"  title="<?= gettext('Are you sure you want to delete this recognition'); ?>?" onclick="deleteRecognition('<?= $enc_recognitionid ?>')" ><i class="fa fa-trash" aria-hidden="true"></i>&emsp;<?= gettext('Delete'); ?></a></li>
<?php } ?>


<?php if (!$opts){  ?>
<li><a role="button" class="disabled"><i class="fa fas fa-exclamation" aria-hidden="true"></i>&emsp;<?= gettext('No options available'); ?></a></li>
<?php } ?>
