<style>
    #modal-title {
    float: left;
}
</style>
<div class="modal" id="manage_event_survey_modal">
    <div aria-label="<?= $modalTitle; ?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 id="modal-title" class="modal-title"><?= $modalTitle; ?>&nbsp;</h2>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeAllActiveModal()">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table id="table_event_surveys" class="table table-hover display compact" style="width:100%" summary="This table display the list of event surveys">
                            <thead>
                                <tr>
                                    <th style="width:30%;"><?= gettext("Survey Type");?></th>
                                    <th style="width:40%;"><?= gettext("Survey Name");?></th>
                                    <th class="action-no-sort" style="width:20%;"><?= gettext("Action");?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php   $action = array('0'=>gettext('Inactive'),'1'=>gettext('Active'),'2'=>gettext("Draft"));


                            foreach(Event::EVENT_SURVEY_TRIGGERS as $key){

                                if (array_key_exists($key,$eventSurveys)){
                                    $eventSurvey = $eventSurveys[$key];
                                    $sharebaleLinkSection = 10;
                                    if ($key == Event::EVENT_SURVEY_TRIGGERS['INTERNAL_POST_EVENT']) {
                                        $sharebaleLinkSection = 11;
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <?= Event::EVENT_SURVEY_TRIGGERS_ENGLISH[$key]; ?>
                                    </td>
                                    <td>
                                        <?= $eventSurvey['survey_title']; ?>
                                    </td>
                                    <td >
                                        <div class="float-right">
                                            <button class="btn btn-affinity" data-toggle="dropdown"><?= $action[$eventSurvey['isactive']]; ?>&nbsp;&nbsp;&#9662;</button>
                                            <ul class="dropdown-menu dropdown-menu-right" style="width:200px;">
                                                <li>
                                                    <button class="btn-list-item"
                                                       onclick="previewEventSurvey('<?= $_COMPANY->encodeId($event->id()); ?>','<?= $key; ?>')">
                                                        <i class="fa fas fa-eye" aria-hidden="true"></i>&nbsp;
                                                        <?= gettext("View"); ?>
                                                    </button>
                                                </li>

                                                <?php if ($eventSurvey['isactive'] != Teleskope::STATUS_ACTIVE) { ?>
                                                    <li>
                                                        <button class="btn-list-item"
                                                            onclick="createEventSurveyCreateUpdateURL('<?= $_COMPANY->encodeId($event->id()) ?>', '<?= $key; ?>','<?= $_COMPANY->encodeId(0) ?>')">
                                                            <i class="fa fas fa-edit" aria-hidden="true"></i>&nbsp;
                                                            <?= gettext("Edit Survey"); ?>
                                                        </button>
                                                    </li>
                                                <?php } ?>

                                                <?php if ($eventSurvey['isactive'] != Teleskope::STATUS_ACTIVE) { ?>
                                                    <li>
                                                        <button class="btn-list-item confirm"
                                                           data-confirm-noBtn="<?= gettext('No') ?>"
                                                           data-confirm-yesBtn="<?= gettext('Yes') ?>"
                                                           title="<?= gettext('Are you sure you want to activate this survey?'); ?>"
                                                           onclick="activateEventSurvey('<?= $_COMPANY->encodeId($event->id()) ?>','<?= $key; ?>')">
                                                            <i class="fa fa-lock" aria-hidden="true"></i>&nbsp;
                                                            <?= gettext('Activate'); ?>
                                                        </button>
                                                    </li>
                                                <?php } else { ?>
                                                    <li>
                                                        <button class="btn-list-item confirm"
                                                                data-confirm-noBtn="<?= gettext('No') ?>"
                                                                data-confirm-yesBtn="<?= gettext('Yes') ?>"
                                                                title="<?= gettext('Are you sure you want to de-activate this survey?'); ?>"
                                                                onclick="deActivateEventSurvey('<?= $_COMPANY->encodeId($event->id()) ?>','<?= $key; ?>')">
                                                            <i class="fa fa-unlock-alt" aria-hidden="true"></i>&nbsp;
                                                            <?= gettext('De-activate'); ?>
                                                        </button>
                                                    </li>
                                                <?php } ?>

                                                <?php if ($eventSurvey['isactive'] == Event::STATUS_ACTIVE) { ?>
                                                    <li>
                                                        <button class="btn-list-item"
                                                           onclick="getShareableLink('<?= $_COMPANY->encodeId($event->val('groupid')); ?>','<?= $_COMPANY->encodeId($event->id()) ?>','<?= $sharebaleLinkSection; ?>')">
                                                            <i class="fa fa-share-square" aria-hidden="true"></i>&nbsp;
                                                            <?= gettext('Get Shareable Link'); ?>
                                                        </button>
                                                    </li>
                                                <?php if($event->val('isactive') == Event::STATUS_ACTIVE){ ?>

                                                    <li>
                                                        <a class="btn-list-item js-download-link" href="ajax_events?downloadEventSurveyResponses=<?= $key; ?>&eventid=<?= $_COMPANY->encodeId($event->id())?>">
                                                            <i class="fa fa-download" aria-hidden="true"></i>&nbsp;
                                                            <?= gettext('Download Responses')?>
                                                        </a>
                                                    </li>
                                                <?php } ?>
                                                <?php } ?>

                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php } else { ?>
                                <tr>
                                    <td>
                                        <?= Event::EVENT_SURVEY_TRIGGERS_ENGLISH[$key]; ?>
                                    </td>
                                    <td>
                                        <?= '-' . gettext ('not set') . '-'?>
                                    </td>
                                    <td>
                                    <button class="btn btn-primary" 
                                    onclick="openChooseSurveyTemplateModal('<?= $_COMPANY->encodeId($event->id())?>','<?= $key; ?>')" ><?= gettext('Create Survey');?></button>
                                    </td>
                                </tr>

                            <?php } ?>
                        <?php } ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-affinity" data-dismiss="modal" onclick="closeAllActiveModal()"><?=gettext('Close');?></button>
            </div>
        </div>
    </div>
</div>
<script>

$('#manage_event_survey_modal').on('shown.bs.modal', function() {
    $('.close').trigger('focus');
});

function openChooseSurveyTemplateModal(e,t) {
    closeAllActiveModal();
    $.ajax({
		url: 'ajax_events.php?openChooseSurveyTemplateModal=1',
        type: "GET",
		data: {'eventid':e,'trigger':t},
        success : function(data) {
            try {
                let jsonData = JSON.parse(data);
                createEventSurveyCreateUpdateURL('<?= $_COMPANY->encodeId($event->id())?>', t,'<?= $_COMPANY->encodeId(0)?>');
            } catch(e) {
                $("#loadAnyModal").html(data);
                $('#eventSurveyTemplateFormModal').modal({
                    backdrop: 'static',
                    keyboard: false
                });	
            }
		}
	});
}
</script>