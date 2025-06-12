<?php
    $speakerData = array();
    $eventSpeakers = $_COMPANY->getAppCustomization()['event']['speakers']['enabled'] ? (isset($seriesEvent) ? $seriesEvent->getEventSpeakers() : $topicTypeObj->getEventSpeakers()) : array();
    if (count($eventSpeakers)) {
        // Iterate through each speaker
        foreach ($eventSpeakers as $speaker) {
            $speakerid = $speaker['speakerid'];
            $speakerDetail = isset($seriesEvent) ? $seriesEvent->getEventSpeakerDetail($speakerid) : $topicTypeObj->getEventSpeakerDetail($speakerid);
            $event_speaker_obj = EventSpeaker::Hydrate($speakerid, $speaker);
            $speakerData[] = [
                'details' => $speakerDetail,
                'obj' => $event_speaker_obj
            ];
        }
    }
    ?>

    <?php if (empty($speakerData)) { ?>
    <div class="approval-section">
        <div>
            <strong><?=gettext("Event Speakers")?>: </strong>
            [<?=gettext("Not set")?>]
        </div>
    </div>
    <?php } else { ?>

    <div class="approval-section">
    
    <div>
        <strong><?=gettext("Event Speakers")?>: </strong>
        <button class="btn-link btn-no-style speaker-data-open-close-js" id="speaker_details">[<?=gettext("View")?>]</button>
    </div>
   
        <?php foreach ($speakerData as $index => $speaker) { 
            $speaker_obj = $speaker['obj']; ?>
            <div class="speaker-details approval-section-sub-block p-3 my-3 " style="display: none;">
            <div>
                <p>
                <strong><?=gettext("Speaker Name")?>: </strong><?= $speaker['details']['speaker_name'] ?><br>
                <strong><?=gettext("Speaker Title")?>: </strong><?= $speaker['details']['speaker_title'] ?><br>
                <strong><?=gettext("Speech Length")?>: </strong> <?= $speaker['details']['speech_length'] .' '. gettext('minutes') ?><br>
                <strong><?=gettext("Expected Attendees")?>: </strong><?= $speaker['details']['expected_attendees'] ?><br>
                <strong><?=gettext("Speaker Fee")?>: </strong>$ <?= $speaker['details']['speaker_fee']?><br>
                <?= $speaker_obj->renderCustomFieldsComponent('v7') ?>
                </p>
            </div>
        </div>
        <?php } ?>


    </div>
    <?php } ?>