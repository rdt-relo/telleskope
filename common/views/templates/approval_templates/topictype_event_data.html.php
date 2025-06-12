
<div class="approval-heading"><strong><?= ($topicTypeObj->val('event_series_id')) ?  gettext($topicTypeLabel." Series Start") : gettext($topicTypeLabel." Start") ?>: </strong> <span><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($topicTypeObj->val('start'),true,true,true) ?>  </span> </div>
<div class="approval-heading"><strong><?= ($topicTypeObj->val('event_series_id')) ?  gettext($topicTypeLabel." Series End") : gettext($topicTypeLabel." End") ?>: </strong> <span><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($topicTypeObj->val('end'),true,true,true) ?>  </span> </div>

    <?php if (!$topicTypeObj->val('event_series_id')) {
        $venue_room = $topicTypeObj->val('venue_room') ?? '';
        $venue_info = $topicTypeObj->val('venue_info') ?? '';
        ?>
    <div class="approval-heading"><strong><?=gettext($topicTypeLabel." Venue")?>: </strong>
        <span><?= in_array($topicTypeObj->val('event_attendence_type'), [1,3]) ? $topicTypeObj->val('eventvanue') : ''; ?></span>
    </div>
    <?php if(!empty($venue_room)){ ?>
        <div class="approval-heading"><strong><?=gettext("Room or Meeting Point")?>: </strong>
            <span> <?= $venue_room ?>   </span>
        </div>    
    <?php } ?>

    <?php if(!empty($venue_info)){ ?>
        <div class="approval-heading"><strong><?=gettext("Additional Information")?>: </strong>
            <span> <?= $venue_info ?>   </span>
        </div>    
    <?php } ?>
    <div class="approval-heading"><strong><?=gettext("Web Conference Link")?>:</strong>
        <span><?= in_array($topicTypeObj->val('event_attendence_type'), [2,3]) ? (empty($topicTypeObj->val('web_conference_link')) ? '' : '<a target="_blank" rel="noopener" href="'. $topicTypeObj->val('web_conference_link') .'">' .gettext('Link'). '</a>') : ''; ?></span>
    </div>
    <?php foreach ($topicTypeObj->getCustomFieldsAsArray() as $k => $v) { ?>
    <div class="approval-heading"><strong><?=htmlentities($k)?>: </strong> <span><?=htmlentities($v)?></span> </div>
    <?php } ?>

    <?php } ?>

    <div class="approval-heading"><strong><?= ($topicTypeObj->val('event_series_id')) ? sprintf(gettext("%s Series Description"), $topicTypeLabel) : sprintf(gettext("%s Description"), $topicTypeLabel)?>: </strong><button class="btn-link btn-no-style topic-description-open-close-js" name="event_description" id="event_description" data-id="<?=$topicTypeObj->id()?>">[<?=gettext("View")?>]</button></div>
    <div>
        <div class="topic-description" data-id="<?=$topicTypeObj->id()?>" style="display: none;">
            <?= $topicTypeObj->val('event_description') ?>
        </div>
    </div>
    <?php if(!$topicTypeObj->val('event_series_id')){?>
    <!-- Other settings -->
    <div class="approval-heading"><strong><?=sprintf(gettext("Private %s"), $topicTypeLabel)?>: </strong>
        <span> <?= $topicTypeObj->val('isprivate') ? 'Yes' : 'No'  ?>   </span>
    </div>
    <div class="approval-heading"><strong><?= gettext("RSVP enabled")?>: </strong>
        <span> <?= $topicTypeObj->val('rsvp_enabled') ? 'Yes' : 'No'  ?>   </span>
    </div>
    <!-- Participation Limit -->
    <?php if($topicTypeObj->isLimitedCapacity()){?>
            <div class="approval-heading"><strong><?= sprintf(gettext("%s Participation Limit"), $topicTypeLabel)?>: </strong>
            <button class="btn-link btn-no-style topic-participation-limit-open-close-js" name="event_participation_limit" id="event_participation_limit" data-id="<?=$topicTypeObj->id()?>">[<?=gettext("View")?>]</button></div>
                <div>
                    <div class="topic-participation-limit ml-5" data-id="<?=$topicTypeObj->id()?>" style="display: none;">
                        <table>
                            <tr>
                                <th><?= gettext("Maximum In Person") ?>: </th>
                                <td>&emsp;<?= $topicTypeObj->val('max_inperson') ?></td>
                            </tr>
                            <tr>
                                <th><?= gettext("Maximum Online")?>: </th>
                                <td>&emsp;<?= $topicTypeObj->val('max_online') ?></td>
                            </tr>
                            <tr>
                                <th><?= gettext("Maximum In Person Waitlist")?>: </th>
                                <td>&emsp;<?= $topicTypeObj->val('max_inperson_waitlist') ?></td>
                            </tr>
                            <tr>
                                <th><?= gettext("Maximum Online Waitlist") ?>: </th>
                                <td>&emsp;<?= $topicTypeObj->val('max_online_waitlist') ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
        <?php }else{?>
            <div class="approval-heading">
                <strong><?=sprintf(gettext('%s Participation Limit'), $topicTypeLabel)?>: </strong>
                [<?=gettext("Not set")?>]
            </div>
        <?php } ?>   
    <?php } ?>              
<?php
if ($_COMPANY->getAppCustomization()['budgets']['enabled'] && $topicTypeObj && !$topicTypeObj->isSeriesEventHead()) {
    include(__DIR__ . '/topictype_event_expense_data.html.php');
}
?>