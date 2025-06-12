<table id="event_series_table" class="table table-hover display compact" summary="This table display the list of events of a group">
    <thead>
        <tr>
            <th><?= gettext("ID");?></th>

            <th width="35%" scope="col"><?= gettext("Event");?></th>

            <?php if($groupid && $eventSeries->val('listids')==0){ ?>
            <th width="15%" scope="col"><?=$_COMPANY->getAppCustomization()['chapter']['name-short']?></th>
            <th width="15%" scope="col"><?=$_COMPANY->getAppCustomization()['channel']['name-short']?></th>
            <?php }elseif($eventSeries->val('listids')!=0){?>
                <th width="30%" scope="col"><?=gettext('Scope')?></th>
            <?php } ?>

            <th width="20%" scope="col"><?= gettext("Start Date");?></th>

            <?php if($eventSeries->isDraft()){ ?>
            <th width="15%" scope="col"><?= gettext("Approval Status");?></th> <?php }else{ ?>
            <th width="15%" scope="col"><?= gettext("RSVPs");?></th>
            <?php } ?>

            <?php if($canCreate || $canPublish || $canManage){ ?>
            <th width="3%"scope="col"></th>
            <?php } ?>
        </tr>
    </thead>
    <tbody>

    <?php 
        foreach($sub_events as $event) {
            $encEventid = $_COMPANY->encodeId($event->val('eventid'));
            $attendees_total = $event->getJoinersCount();
            $attendees_yes = $event->getRsvpYesCount();
            $attendees_maybe = $event->getRsvpMaybeCount();
            $attendees =
                gettext('Total'). ':&nbsp;' . $attendees_total . '<br>' .
                '<small>'. gettext('Yes'). ':&nbsp;' . $attendees_yes . '</small>' . '<br>' .
                '<small>'. gettext('Maybe') . ':&nbsp;' . $attendees_maybe . '</small>';

    ?>
        <tr>
            <td><?=$_COMPANY->encodeIdForReport($event->val('eventid'));?></td>

            <td>
                <?php if ($event->val('isprivate')){ ?>
                    <small style="background-color: lightyellow;">[<?= gettext("Private Event");?>]</small><br>
                <?php } ?>
                <button class="btn-no-style" style="text-align:left;" onclick="getEventDetailModal('<?= $encEventid;?>','<?= $_COMPANY->encodeId(0);?>','<?= $_COMPANY->encodeId(0);?>')">
                    <?php if ($event->val('isactive') == Event::STATUS_DRAFT) { ?>
                        <span style="color:red;">
                            <?= $event->val('eventtitle'); ?>&nbsp
                            <img src="img/draft_ribbon.png" alt="Draft icon image" height="16px"/>
                        </span>
                    <?php } elseif ($event->val('isactive') == Event::STATUS_UNDER_REVIEW) { ?>
                        <span style="color:darkorange;">
                            <?= $event->val('eventtitle'); ?>&nbsp
                            <img src="img/review_ribbon.png" alt="Draft icon image" height="16px"/>
                        </span>
                    <?php } elseif ($event->val('isactive') == 5) { ?>
                        <span style="color:deepskyblue;">
                            <?= $event->val('eventtitle'); ?>&nbsp;
                            <img src="img/schedule.png" alt="Schedule icon image" height="16px"/>
                        </span>
                    <?php } elseif ($event->val('isactive') == 0) { ?>
                        <span style="color:purple;">
                            <?= $event->val('eventtitle'); ?>&nbsp;
                        </span>
                        <sup class="left-ribbon ribbon-purple"><?=gettext('Cancelled')?></sup>

                    <?php } else { ?>
                        <span>
                            <?= $event->val('eventtitle'); ?>
                        </span>
                    <?php } ?>
                </button>
                <?php 
                    $collebratedBetween = '';
                    if($event->val('collaborating_groupids')){ 
                        $collebratedBetween = $event->getFormatedEventCollaboratedGroupsOrChapters();
                ?>
                    <p><small><?= gettext("Collaboration between");?>:</small></p>
                    <p><small><?= $collebratedBetween; ?></small></p>
                <?php } ?>
            </td>
            <?php
            $chapterName = '';
            $channelName = '';
            if($groupid && $eventSeries->val('listids')==0){
                $channelName = $event->val('channelid') ?  htmlspecialchars(Group::GetChannelName($event->val('channelid'),$event->val('groupid'))['channelname']) : '-';
                $chapters =  $event->val('chapterid')  ? explode(',',$event->val('chapterid')) : array();
                foreach($chapters as $chapter){
                    $ch1 = Group::GetChapterName($chapter, $event->val('groupid'))['chaptername'];
                    $chapterName .= '<li>'.htmlspecialchars($ch1).'</li>';
                }
            ?>
            <td><?= $chapterName ?: '-' ?></td>
            <td><?= $channelName; ?></td>
            <?php }elseif($eventSeries->val('listids')!=0){
                $listNamesArr = DynamicList::GetFormatedListNameByListids($eventSeries->val('listids'),true);
                ?>
                <td>
                    <p><small>
                        <?= gettext("Dynamic List") ?>

                    <?php foreach($listNamesArr as $listName){ ?>
                        <li><?= htmlspecialchars($listName) ?> </li>                        
                    <?php } ?>
                    </small></p>
                </td>
            <?php } ?>

            <td><span style="display: none;"><?= $event->val('start'); ?></span><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($event->val('start'),true,true,true);?></td>

            <?php
            if($eventSeries->isDraft()){
                $approvalStatus = "";
                $ev= Event::GetEvent($eventSeries->val('eventid'));
                $approval = $ev->getApprovalObject();
                if($approval){
                    $approvalStatus =  ucwords($approval->val('approval_status')).'<br>Stage '.$approval->val('approval_stage');
                }
            ?>
            <td><?=$approvalStatus;?></td>
            <?php }else{ ?>
            <td><?= $attendees; ?></td>
            <?php } ?>

            <?php if($canCreate || $canPublish || $canManage){ ?>
            <td>
                <div class="" style="color: #fff; float: left;">
                    <button onclick="getEventActionButton('<?= $_COMPANY->encodeId($groupid); ?>','<?= $encEventid; ?>')" class="btn-no-style dropdown-toggle fa fa-ellipsis-v mt-3" title="Action" type="button" data-toggle="dropdown"></button>
                    <ul class="dropdown-menu dropdown-menu-right" id="dynamicActionButton<?= $encEventid; ?>" style="width: 250px; cursor: pointer;">
                    </ul>
                 </div>
            </td>
            <?php } ?>
        </tr>
    <?php } ?>
    </tbody>
</table>
<script>
    var x = parseInt(localStorage.getItem("local_variable_for_table_pagination")); 
     $('#event_series_table').dataTable({
        "bInfo": false,
        "paging": true,
        pageLength: x,
         lengthChange:true,
        "order": [[ 0, "desc" ]],
        "aoColumnDefs": [{
        "bSortable": false,         
        }],    
        columnDefs: [
                	{ targets: [-1], orderable: false }
                ],    
        language: {
            url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
        }           		   
    })
    $('#event_series_table').on( 'length.dt', function ( e, settings, len ) {
        localStorage.setItem("local_variable_for_table_pagination", len);
    } );
</script>