<div class="row">
    <div class="col-md-12">
        <div class="tab-content">
            <?php if ($action == 1 && ($message->val('isactive') == 2 || $message->val('isactive') == 3)){ ?>
            <div class="col-md-12 text-center p-5">
                <h2><?= gettext("Message preview");?></h2>
            </div>
            <?php } ?>
          <div class="col-md-12">
            <p><strong><?= gettext("From");?></strong></p>
            <p><?= $message->val('from_name'); ?></p>
            <br>
          </div>

            <div class="col-md-12">
                <p><strong><?= gettext("To");?></strong></p>
                <div class="form-group">
                    <?php if ($message->val('recipients_base') == 1) { ?>
                        <p><strong>&emsp;<?= gettext("All Users in the Zone");?></strong></p>
                    <?php } elseif ($message->val('recipients_base') == 2) { ?>
                        <p><strong>&emsp;<?= sprintf(gettext("All Users in the Zone who are not a member of any %s"),$_COMPANY->getAppCustomization()['group']["name-short"]);?></strong></p>
                    <?php } elseif($message->val('recipients_base') == 4){ ?> 
                        <p><strong>&emsp;<?= gettext("Dynamic Lists");?>: </strong> <?= $listsNameCsv; ?></p>
                    <?php } else {
                        $membersType = explode(',',$message->val('sent_to'));
                    ?>
                        <p>&emsp;
                            <strong>
                                <?= in_array('1',$membersType) ? sprintf(gettext("%s Leaders,"), $_COMPANY->getAppCustomization()['group']["name-short"]) : "" ?>
                                <?= in_array('2',$membersType) ? sprintf(gettext("%s Members,"), $_COMPANY->getAppCustomization()['group']["name-short"]) : "" ?>
                                <?= in_array('3',$membersType) ? sprintf(gettext("%s Leaders, "), $_COMPANY->getAppCustomization()['chapter']["name-short"]): "" ?>
                                <?= in_array('4',$membersType) ? sprintf(gettext("%s Leaders, "), $_COMPANY->getAppCustomization()['channel']["name-short"]) : "" ?>
                                <?= in_array('5',$membersType) ? sprintf(gettext('%1$s Members (in Active %1$ss)'), $_COMPANY->getAppCustomization()['teams']["name"]) : "" ?>
                                <?= in_array('0',$membersType) ? gettext("Other") : ""; ?>
                            </strong>
                        </p>
                        <p>&emsp;<strong><?= sprintf(gettext("Of %s"),$_COMPANY->getAppCustomization()['group']["name-short"]) ;?>: </strong><?= $groupnames; ?></p>
                        <?php if ($roleNames){ ?>
                            <p>&emsp;<strong><?= sprintf(gettext("%s Roles"),$_COMPANY->getAppCustomization()['teams']['name']) ;?>: </strong><?= $roleNames; ?></p>
                        <?php } ?>
                        <?php if($chapterNames){ ?>
                            <p>&emsp;<strong><?=$_COMPANY->getAppCustomization()['chapter']["name-short"]; ?>: </strong><?= $chapterNames; ?></p>
                        <?php } ?>
                        <?php if($channelNames){ ?>
                            <p>&emsp;<strong><?=$_COMPANY->getAppCustomization()['channel']["name-short"]; ?>: </strong><?= $channelNames; ?></p>
                        <?php } ?>
                    <?php } ?>

                    <p>&emsp;<strong><?= gettext("Total Recipients");?>: </strong>
                        <?php if ($message->val('total_recipients')) { ?>
                        <?= $message->val('total_recipients'); ?>
                        <button id="show-recipients" class="btn btn-link btn-no-style text-left" style="font-size: small !important; padding-left: 0.5rem!important;" onclick="showRecipients('<?= (empty($messageid)?'':$_COMPANY->encodeId($messageid)); ?>')">
                            [<?= gettext("View") ?>]
                        </button>
                        <?php } else { ?>
                            <span style="color: red;">0</span>
                        <?php } ?>
                    </p>
                    <p id="recipients" style="padding:1em;font-size: x-small;border:1px solid #bcbcbc; display:none"></p>
                </div>
                <p><strong><?= gettext("Subject");?></strong></p>
                <div class="form-group">
                    <p><?= htmlspecialchars($message->val('subject')) ?></p>
                </div>
                <p><strong><?= gettext("Message");?></strong></p>
                <div id="post-inner">
                    <?= ($message->val('message')) ?>
                </div>
                <?= $message->renderAttachmentsComponent('v20') ?>

            </div>
            <?php if ($action == 1 && ($message->val('isactive') == 2 || $message->val('isactive') == 3)){ ?>
            <div class="col-md-12 text-center mb-5 mt-5">
              <button class="btn btn-primary prev-step mb-2" onclick="backToMessageComposer()" type="button"><?= gettext("Back to composer");?></button>
              &emsp;
              <button class="btn btn-primary btn-info-full next-step mb-2" type="button" onclick="openMessageReviewModal('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId($message->val('messageid'))?>')" ><?= gettext("Send Message for Review");?></button>
              &emsp;
              <button class="btn btn-warning btn-info-full next-step  mb-2" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to send this message?');?>" type="button" onclick="getMessageScheduleModal('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId($message->val('messageid'))?>')" <?= ($message->val('total_recipients')) ? '' : 'disabled'?> ><?= gettext("Send Message");?></button>
              &emsp;
              <button class="btn btn-primary btn-info-full next-step mb-2" type="button" onclick="groupMessageList('<?= $_COMPANY->encodeId($groupid); ?>',<?= $groupid ? 1 : 2; ?>)" ><?= gettext("Save Draft");?></button>
              &emsp;
              <button class="btn btn-primary btn-info-full next-step confirm  mb-2" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>"  title="<strong><?= gettext('Are you sure you want to delete this message?');?></strong>" type="button" onclick="groupMessageDelete('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($message->val('messageid')); ?>',0)" ><?= gettext("Delete Message");?></button>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<script>
    function showRecipients(value){
        let id_recipients = $("#recipients");
        if (id_recipients.is(":hidden")) {
            $.ajax({
                type: "GET",
                url: "ajax_message.php",
                data: {
                    'show_recipients': value
                },
                success: function (response) {
                    id_recipients.html(response);
                    id_recipients.show();
                }
            });
        } else {
            id_recipients.hide();
        }
    }
</script>
