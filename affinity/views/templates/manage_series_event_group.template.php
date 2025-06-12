<?php
    $canCreate = $_USER->canCreateContentInGroupSomething($eventSeries->val('groupid'));
    $canPublish = $_USER->canPublishContentInGroupSomething($eventSeries->val('groupid'));
    $canManage = $_USER->canManageGroupSomething($eventSeries->val('groupid'));
?>

<div class="container inner-background">
    <div class="row pb-5">
        <div class="col-md-12">
            <div class="col-md-6">
                <div class="event-series-title">
                    <?= $eventSeries->val('eventtitle') ?>
                    &emsp;
                    <?php if($canCreate && !$eventSeries->isCancelled()){ ?><a aria-label="<?= gettext('Edit');?>" href="javascript:void(0)" onclick="loadCreateEventGroupModal('<?= $encGroupId;?>','<?= $encEventSeriesId;?>');"><i class="fa fa-sm fas fa-edit"></i></a><?php } ?>
                </div>
            </div>
        
            <?php if ($canCreate && !$eventSeries->isCancelled()) { ?>
                <div class="col-md-6 text-right inner-page-title">
                    <button class="btn btn-affinity"
                    <?php
                        if(Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['EVENT_CREATE_BEFORE'])){
                            $callOtherMethod = base64_url_encode(
                                json_encode(
                                    array(
                                        "method"=>"newEventForm",
                                        "parameters"=>array(
                                            $encGroupId,
                                            0,
                                            $encEventSeriesId
                                        )
                                    )
                                )
                            ); // base64_encode for prevent js parsing error
                    ?>
                    onclick="loadDisclaimerByHook('<?= $_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['EVENT_CREATE_BEFORE']); ?>', '<?=$_COMPANY->encodeId(0)?>',0, '<?= $callOtherMethod ?>')"
                <?php } else { ?>
                     onclick="newEventForm('<?= $encGroupId; ?>',0,'<?= $encEventSeriesId; ?>');"
                <?php } ?>
                     ><?= gettext('Add new event to series');?></button>
                </div>
            <?php } ?>
            <div class="col-md-12 evet-description">
                <div id="post-inner">
                    <?= $eventSeries->val('event_description'); ?>
                </div>
            </div>
        </div>
    <?php
        if (!empty($sub_events)){
    ?>
    <div class="col-md-12 mt-3" style="text-align: center; border-top: 1px solid rgb(200,200,200)"></div>
        <div class="col-md-12">         
            <div class="table-responsive pt-3" id="eventTable">
                <?php
                    include(__DIR__ . "/group_event_series_table.template.php");
                ?>
            </div>
        </div>
        <?php } else { ?>
            <div class="text-center col-md-12 p-5">
                <span><?= gettext('No event added in this series.');?>   <?php if($canCreate){ ?> <a href="#"
                    <?php
                        if(Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['EVENT_CREATE_BEFORE'])){
                            $callOtherMethod = base64_url_encode(json_encode(array("method"=>"newEventForm","parameters"=>array($encGroupId,0,$encEventSeriesId)))); // base64_encode for prevent js parsing error
                    ?>
                    onclick="loadDisclaimerByHook('<?= $_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['EVENT_CREATE_BEFORE']); ?>', '<?=$_COMPANY->encodeId(0)?>', 0, '<?= $callOtherMethod ?>')"
                <?php } else { ?>
                     onclick="newEventForm('<?= $encGroupId; ?>',0,'<?= $encEventSeriesId; ?>');"
                <?php } ?>
                    > <?= gettext('Create Now');?></a> <?php } ?></span>
            </div>
        <?php } ?>
        <div class="col-md-12 mt-3 mb-3" style="text-align: center; border-top: 1px solid rgb(200,200,200)"></div>
            <div class="col-md-12 text-center">
        <?php if($canPublish || $canCreate){ ?>
            <?php if ($eventSeries->showPublishSeriesButton($sub_events)) { ?>
                <?php // Disclaimer check
                    $checkDisclaimerExists = Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['EVENT_PUBLISH_BEFORE'], $event->val('groupid'));
                    if($checkDisclaimerExists){
                        $call_method_parameters = array(
                            $encGroupId,
                            $encEventSeriesId,
                        );
                        $call_other_method = base64_url_encode(json_encode(
                            array (
                                "method" => "getEventScheduleModal",
                                "parameters" => $call_method_parameters
                            )
                        ));
                        $onClickFunc = "loadDisclaimerByHook('".$_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['EVENT_PUBLISH_BEFORE'])."','".$encGroupId."', 0, '".$call_other_method."');";
                    }else{
                        $onClickFunc = "getEventScheduleModal('".$encGroupId ."', '".$encEventSeriesId."');";
                    }
                ?>
                    <button class="deluser btn btn-affinity" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to publish');?>?" onclick="<?=$onClickFunc ?>"><?=  ($eventSeries->isDraft() || $eventSeries->isUnderReview()) ? gettext("Publish") : gettext("Publish Updates")?></button>
                <?php } ?>

                <?php if ($eventSeries->isAwaiting()) { ?>
                    <button class="deluser btn btn-affinity" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to cancel publishing');?>?" onclick="cancelEventPublishing('<?= $encGroupId; ?>','<?= $encEventSeriesId ?>');"><?= gettext('Cancel Publishing');?></button>
                <?php } ?>

                <?php if ($eventSeries->isPublished()) { ?>
                    <button type="button" class="deluser btn btn-affinity" onclick="deleteEventSeriesGroup('<?= $encGroupId; ?>','<?= $encEventSeriesId; ?>',<?= (basename($_SERVER['PHP_SELF']) =='eventview.php')?'true':'false' ?>, <?= ($eventSeries->val('groupid')== 0)?'true':'false' ?>, '<?= $eventSeries->val('isactive')==1 ?>');" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to cancel this series'); ?>?"><?= gettext('Cancel Event Series'); ?></button>
                <?php } elseif ($eventSeries->isDraft() || $eventSeries->isUnderReview()) { ?>
                    <button type="button" class="deluser btn btn-affinity" onclick="deleteEventSeriesGroup('<?= $encGroupId; ?>','<?= $encEventSeriesId; ?>',<?= (basename($_SERVER['PHP_SELF']) =='eventview.php')?'true':'false' ?>, <?= ($eventSeries->val('groupid')== 0)?'true':'false' ?>, '<?= $eventSeries->val('isactive')==1 ?>');" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="<?= gettext('Are you sure you want to delete this series'); ?>?"><?= gettext('Delete Event Series'); ?></button>
                <?php } ?>

        <?php } ?>

            <button class=" btn btn-affinity" onclick="manageGlobalEvents('<?= $encGroupId; ?>')"><?= gettext("Back")?></button>
            </div>
    </div>
</div>