<div class="approval-heading mt-5"><strong><?=gettext("All ".$topicTypeLabel." in series")?>: </strong></div>
<div id="accordion">
<?php  $getSeriesAllEvents = Event::GetEventsInSeries($topicTypeObj->val('event_series_id'));
foreach($getSeriesAllEvents as $seriesEvent){ ?>   
    <div class="card pt-0 mb-3 text-left">
        <div class="card-header" id="<?= $seriesEvent->id() ?>">
            <button class="btn btn-link" data-toggle="collapse" data-target="#detail-<?=$seriesEvent->id() ?>" aria-expanded="true" aria-controls="collapseOne">
            <span><?= $seriesEvent->val('eventtitle'); ?></span>
            </button>
        </div>
    
        <div id="detail-<?=$seriesEvent->id() ?>" class="collapse" aria-labelledby="Details about <?= $seriesEvent->val('eventtitle') ?>" data-parent="#accordion">
        <div class="card-body px-0 pb-3">

            <div class="approval-heading"><strong><?=gettext($topicTypeLabel." Title")?>: </strong> <span><?= $seriesEvent->val('eventtitle'); ?></span></div>
            <div class="approval-heading"><strong><?=gettext($topicTypeLabel." Status")?>: </strong> <span><?= $seriesEvent->val('isactive') == 1 ? 'Published' : 'Not Published'; ?></span></div>
            <?php
            // Event URL
            $event_sub_url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'eventview?id=' . $_COMPANY->encodeId($seriesEvent->id()) . '&approval_review=1';;
            ?>
            <div class="approval-heading"><strong><?=gettext($topicTypeLabel." Link")?>: </strong> <span style="font-size:1rem;"><a href="<?= $event_sub_url ?>" target="_blank" rel="noopener"><?= $event_sub_url ?></a></span></div>

            <div class="approval-heading"><strong><?=gettext($topicTypeLabel." Start")?>: </strong> <span><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($seriesEvent->val('start'),true,true,true) ?>  </span> </div>
            <div class="approval-heading"><strong><?=gettext($topicTypeLabel." End")?>: </strong> <span><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($seriesEvent->val('end'),true,true,true) ?>  </span> </div>

            <div class="approval-heading"><strong><?=gettext($topicTypeLabel." Venue")?>: </strong>
                <span><?= in_array($seriesEvent->val('event_attendence_type'), [1,3]) ? $seriesEvent->val('eventvanue') : ''; ?></span>
            </div>
        <?php 
            $event_series_venue_room = $seriesEvent->val('venue_room') ?? '';
            $event_series_venue_info = $seriesEvent->val('venue_info') ?? '';
            ?>
        <?php if(!empty($event_series_venue_room)){ ?>
            <div class="approval-heading"><strong><?=gettext("Room or Meeting Point")?>: </strong>
                <span> <?= $event_series_venue_room ?>   </span>
            </div>    
        <?php } ?>
        
        <?php if(!empty($event_series_venue_info)){ ?>
            <div class="approval-heading"><strong><?=gettext("Additional Information")?>: </strong>
                <span> <?= $event_series_venue_info ?>   </span>
            </div>    
        <?php } ?>

            <div class="approval-heading"><strong><?=gettext("Web Conference Link")?> :</strong>
                <span><?= in_array($seriesEvent->val('event_attendence_type'), [2,3]) ? (empty($seriesEvent->val('web_conference_link')) ? '' : '<a target="_blank" rel="noopener" href="'. $seriesEvent->val('web_conference_link') .'">' .gettext('Link'). '</a>') : ''; ?></span>
            </div>


            <?php foreach ($seriesEvent->getCustomFieldsAsArray() as $k => $v) { ?>
            <div class="approval-heading"><strong><?=htmlentities($k)?>: </strong> <span><?=htmlentities($v)?></span></div>
            <?php } ?>

            <div class="approval-heading"><strong><?=sprintf(gettext("%s Description"), $topicTypeLabel)?>: </strong><button class="btn-link btn-no-style topic-description-open-close-js" name="event_description" id="event_description" data-id="<?= $seriesEvent->val('eventid')?>">[<?=gettext("View")?>]</button></div>
            <div>
                <div class="topic-description" data-id="<?=$seriesEvent->val('eventid')?>" style="display: none;">
                    <?= $seriesEvent->val('event_description') ?>
                </div>
            </div>

            <!-- Other settings -->
    <div class="approval-heading"><strong><?=sprintf(gettext("Private %s"), $topicTypeLabel)?>: </strong>
        <span> <?= $seriesEvent->val('isprivate') ? 'Yes' : 'No'  ?>   </span>
    </div>
    <div class="approval-heading"><strong><?= gettext("RSVP enabled")?>: </strong>
        <span> <?= $seriesEvent->val('rsvp_enabled') ? 'Yes' : 'No'  ?>   </span>
    </div>
    <!-- Participation Limit -->
    <?php if($seriesEvent->isLimitedCapacity()){?>
            <div class="approval-heading"><strong><?= sprintf(gettext("%s Participation Limit"), $topicTypeLabel)?>: </strong>
            <button class="btn-link btn-no-style topic-participation-limit-open-close-js" name="event_participation_limit" id="event_participation_limit" data-id="<?=$seriesEvent->id()?>">[<?=gettext("View")?>]</button></div>
                <div>
                    <div class="topic-participation-limit ml-5" data-id="<?=$seriesEvent->id()?>" style="display: none;">
                        <table>
                            <tr>
                                <th><?= gettext("Maximum In Person") ?>: </th>
                                <td>&emsp;<?= $seriesEvent->val('max_inperson') ?></td>
                            </tr>
                            <tr>
                                <th><?= gettext("Maximum Online")?>: </th>
                                <td>&emsp;<?= $seriesEvent->val('max_online') ?></td>
                            </tr>
                            <tr>
                                <th><?= gettext("Maximum In Person Waitlist")?>: </th>
                                <td>&emsp;<?= $seriesEvent->val('max_inperson_waitlist') ?></td>
                            </tr>
                            <tr>
                                <th><?= gettext("Maximum Online Waitlist") ?>: </th>
                                <td>&emsp;<?= $seriesEvent->val('max_online_waitlist') ?></td>
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

            <!-- Event series expense entries -->
            <?php
            if ($_COMPANY->getAppCustomization()['budgets']['enabled']) {
                include(__DIR__ . '/topictype_event_expense_data.html.php');
            }
            ?>

            <!-- Speaker data -->
            <?php
            if ($_COMPANY->getAppCustomization()['event']['speakers']['enabled']) {
                include(__DIR__ . '/topictype_event_speakers_data.html.php');
            }
            ?>

            <!-- ORG series data if it exists -->
            <?php
            if ($_COMPANY->getAppCustomization()['event']['partner_organizations']['enabled']) {
                $fetchLatestEventOrganizations = $seriesEvent->getAssociatedOrganization() ?? array();
                $latestOrgData = !empty($fetchLatestEventOrganizations) ? Organization::ProcessOrgData($fetchLatestEventOrganizations) : [];
                include(__DIR__ . '/topictype_event_org_data.html.php');
            }
            ?>

            <!-- ATtachements  -->
            <?= $seriesEvent->renderAttachmentsComponent('v16') ?>
        </div>
        </div>
    </div>
<?php } ?>
</div>