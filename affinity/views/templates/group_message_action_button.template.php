<div class="" style="color: #fff; float: left;">
    <button aria-label="<?= sprintf(gettext("%s message action dropdown"), $row['subject']); ?>" id="<?= $_COMPANY->encodeId($row['messageid']); ?>" class="btn-no-style dropdown-toggle fa fa-ellipsis-v mt-3" type="button" data-toggle="dropdown"></button>
    <ul class="dropdown-menu dropdown-menu-right" style="width: 250px; cursor: pointer;">

        <?php if($row['isactive']== 2 || $row['isactive']== 3){ ?>
        <li>
            <a role="button" href="javascript:void(0)" class="" onclick="groupMessageForm('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($row['messageid']); ?>','<?= $groupid ? 1 : 2;?>')"   title="<?= gettext("Edit");?>" ><i class="fa fas fa-edit" aria-hidden="true"></i>
                &emsp;<?= gettext("Edit");?>
            </a>
        </li>
        <?php } ?>

        <?php if($row['isactive']== 2 ){ ?>
        <li>
            <a role="button" href="javascript:void(0)" class="" onclick="openMessageReviewModal('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($row['messageid']); ?>')" title="<?= gettext("Email Review");?>" ><i class="fa fa-tasks" aria-hidden="true"></i>
                &emsp;<?= gettext("Email Review");?>
            </a>
        </li>
        <?php } ?>

        <?php if($row['isactive']== 3 ){ ?>
            <li>
                 <a role="button" <?php if ($row['total_recipients']) { ?>
                    class="" href="javascript:void(0)" onclick="getMessageScheduleModal('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($row['messageid']); ?>')" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<strong><?=gettext('Are you sure you want to send this message?')?></strong>"
                    <?php } else { ?>
                        onclick = "swal.fire({title: 'Error', text: '<?= gettext("No recipients selected for this message. Please add some recipients first.");?>'})";                   
                    <?php } ?>
                 >
                    <i class="fa fa-paper-plane" title="Send Message" aria-hidden="true"></i>&emsp;<?= gettext("Send Message");?>
                </a>
            </li>
        <?php } ?>

        <?php if($row['isactive']== 5){ ?>
            <li><a role="button" href="javascript:void(0)" class="deluser" onclick="cancelMessagePublishing('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId($row['messageid']); ?>');" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>"  title="<?= gettext("Are you sure you want to cancel publishing?");?>" ><i class="fa fas fa-times" aria-hidden="true"></i>&emsp;<?= gettext('Cancel Publishing'); ?></a></li>
        <?php } ?>


        <li>
            <a role="button" href="javascript:void(0)" onclick="groupMessagePreview('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId($row['messageid']); ?>',2)" title="<?= gettext("View");?>" ><i class="fa fas fa-eye" aria-hidden="true"></i>
                &emsp;<?= gettext("View");?>
            </a>
        </li>

        <?php if ($_COMPANY->getAppCustomization()['messaging']['email_tracking']['enabled'] && $row['isactive']== 1){ ?>
        <li>
            <a role="button" href="javascript:void(0)" class="" title="<?= gettext('Email Tracking'); ?>" onclick="getEmailLogstatistics('<?=$_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($row['messageid']); ?>','<?=  $_COMPANY->encodeId(4); ?>')"><i class="fa far fa-chart-bar" aria-hidden="true"></i>
                &emsp;<?= gettext('Email Tracking'); ?>
            </a>
        </li>
        <?php } ?>

        <?php if($row['isactive']!= 5){ ?>
            <li>
                <a role="button" class="deluser" href="javascript:void(0)" onclick="groupMessageDelete('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($row['messageid']); ?>','<?= $i+1;?>')" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>"  title="<?= gettext("Are you sure you want to delete?");?>"><i class="fa fa-trash" title="<?= gettext("Delete");?>" aria-hidden="true"></i>
                    &emsp;<?= gettext("Delete");?>
                </a>
            </li>
        <?php } ?>
    </ul>
</div>