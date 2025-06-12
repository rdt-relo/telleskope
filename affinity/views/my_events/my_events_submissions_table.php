<div class="col-md-12">
    <div class="row">
    <div class="col-12">
        <h4><?= gettext("Manage Events")?></h4>
        <hr class="lineb" >
      </div>
        <div class="col-md-12">
            <div class="col-md-4">
                <div class="form-group col-md-12 ">
                    <label style="font-size:small;"><?= sprintf(gettext("Filter by %s State"), 'Event');?></label>
                    <select class="form-control" onchange="filterMyEventsSubmissions();" id="filterByState" style="font-size:small;border-radius: 5px;">
                      <option value="<?= $_COMPANY->encodeId(2)?>" <?= $state_filter==2 ? 'selected' : '' ?> ><?= gettext("Draft / Not Published");?></option>
                      <option value="<?= $_COMPANY->encodeId(1)?>" <?= $state_filter==1 ? 'selected' : '' ?> ><?= gettext("Published");?></option>
                      <option value="<?= $_COMPANY->encodeId(0)?>" <?= $state_filter==0 ? 'selected' : '' ?> ><?= gettext("Cancelled");?></option>
                    </select>
                  </div>
            </div>
            <div class="col-md-4">&nbsp;</div>
            <div class="col-md-4 ">
                <div class="form-group col-md-12 " style="float:right !important;">
                    <label style="font-size:small;"><?= gettext("Filter by Calendar Year");?></label>
                    <select class="form-control" id="filterByYear" onchange="filterMyEventsSubmissions();" style="font-size:small;border-radius: 5px;">
                        <?php
                        $current_year = date('Y');
                        for($i=(date("Y")-date("Y",strtotime($_COMPANY->val('createdon')))); $i>=0; $i--){
                            $sel = "";
                            if ($year_filter){
                              if ($year_filter == ($current_year -$i)){
                                $sel = "selected";
                              }
                            } else {
                              if (($current_year -$i) == $current_year){
                                $sel = "selected";
                              }
                            }
                        ?>
                        <option value="<?= $_COMPANY->encodeId($current_year -$i); ?>" <?= $sel; ?> ><?=  $i==0 ? gettext("Current Year"). " (".($current_year -$i).")" : gettext("Calendar Year")." (".($current_year -$i).")"; ?></option>
                        <?php } ?>
                        <option value="<?= $_COMPANY->encodeId($current_year +1); ?>" <?= ($year_filter > $current_year) ? 'selected' : '' ?>>
                            <?= gettext("Future Years");?>  (><?php echo $current_year; ?>)
                        </option>
                    </select>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-md-12">            
            <div class="table-responsive " id="eventTable">
            <table id="my_event_table" class="table table-hover display compact" summary="<?= gettext("Manage Events")?>">
                <thead>
                    <tr>
                        <th width="5%" scope="col"><?= gettext("ID");?></th>
                        <th width="20%" scope="col"><?= gettext("Event");?></th>
                        <th width="20%" scope="col"><?=gettext('Scope')?></th>
                        <th width="15%" scope="col"><?= gettext("Start Date");?></th>
                        <?php if($state_filter == 2){  ?>
                        <th width="5%" scope="col"><?= gettext("Approval status");?></th>
                        <?php }else{?>
                        <th width="5%" scope="col"><?= gettext("RSVPs");?></th>
                        <?php } ?>
                        <th width="10%" scope="col"><?= gettext("Action");?></th>
                    </tr>
                </thead>
                <tbody>
            <?php
                foreach($events as $event){  
                    $encEventid = $_COMPANY->encodeId($event['eventid']);
                    $ev= Event::GetEvent($event['eventid']);
                    $eventTitle = '';
                    if($ev->isSeriesEventSub() || $ev->isSeriesEventHead()){
                        $eventSeries= Event::GetEvent($event['event_series_id']);
                        $eventTitle .= "<small>[".gettext('Event Series')." ]</small> ";
                        $seriesEvents = Event::GetEventsInSeries($event['event_series_id']);
                        $attendees = 0;
                        foreach($seriesEvents as $seriesEvent){
                            $attendees = $attendees+$seriesEvent->getJoinersCount();
                        }
                    } else {
                        $attendees = $ev->getJoinersCount();
                    }
                    if($ev->val('isprivate')){
                        $eventTitle .= '<small style="background-color: lightyellow;">['.gettext("Private Event").']</small>';
                    }
                    if (!empty($eventTitle)) {
                        $eventTitle .= '<br>';
                    }
                    $eventTitle .= '<a  onclick="getEventDetailModal(\''.$encEventid.'\', \''.$_COMPANY->encodeId(0).'\',\''.$_COMPANY->encodeId(0).'\')" href="javascript:void(0);" >';

                    if ($event['isactive'] == Event::STATUS_DRAFT) {
                        $eventTitle .= '<span style="text-align:justify;color:red;">';
                        $eventTitle .= $event['eventtitle'].'&nbsp';
                        $eventTitle .=  '<img src="img/draft_ribbon.png" alt="Draft icon image" height="16px"/>';
                        $eventTitle .= '</span>';
                    } elseif ($event['isactive'] == Event::STATUS_UNDER_REVIEW) {
                        $eventTitle .= '<span style="text-align:justify;color:darkorange;">';
                        $eventTitle .= $event['eventtitle'].'&nbsp';
                        $eventTitle .= '<img src="img/review_ribbon.png" alt="Review icon image" height="16px"/>';
                        $eventTitle .= '</span>';
                    } elseif ($event['isactive'] == Event::STATUS_AWAITING) {
                        $eventTitle .= '<span style="text-align:justify;color:deepskyblue;">';
                        $eventTitle .= $event['eventtitle'].'&nbsp';
                        $eventTitle .= '<img src="img/schedule.png" alt="Schedule icon image" height="16px"/>';
                        $eventTitle .= '</span>';
                    } elseif ($event['isactive'] == Event::STATUS_INACTIVE) {
                        $eventTitle .= '<span style="color:purple;">';
                        $eventTitle .= $event['eventtitle'].'&nbsp';
                        $eventTitle .= '</span>';
                        $eventTitle .= '<sup class="left-ribbon ribbon-purple">' . gettext('Cancelled'). '</sup>';
                    } else {
                        $eventTitle .= '<span style="text-align:justify;">';
                        $eventTitle .= $event['eventtitle'];
                        $eventTitle .= '</span>';
                    }
                    $eventTitle .=  '</a>';
                    $collebratedBetween = '';
                    
                    if($event['collaborating_groupids']){ 
                        
                        $collebratedBetween = $ev->getFormatedEventCollaboratedGroupsOrChapters(true);
                        $eventTitle .= '<p><u><small>'.gettext("Collaboration between").':</small></u></p>';
                        $eventTitle .= '<p><small>'.$collebratedBetween.'</small></p>';
                    }
                    if($event['collaborating_groupids_pending']){ 
                        $collebratedBetweenPending = $ev->getFormatedEventPendingCollaboratedGroups();
                        if ($collebratedBetweenPending) {
                            $eventTitle .= '<p><u><small>'.gettext("Pending Collaboration Requests").':</small></u></p>';
                            $eventTitle .= '<p><small>'.$collebratedBetweenPending.'</small></p>';
                        }
                    }
                    //$startDate = $db->covertUTCtoLocal("M d,Y H:i",$event['start'],$timezone);
                    $startDate = $_USER->formatUTCDatetimeForDisplayInLocalTimezone($event['start'],true,true,true);

                    $actionButton = '<div class="" style="color: #fff; float: left;" >';
                    $actionButton .= '<button id="'.$encEventid.'" onclick="getMyEventActionButton(\''.$encEventid.'\')" class="btn btn-sm btn-affinity dropdown-toggle" title="Action" type="button" data-toggle="dropdown">';
                    $actionButton .=gettext("Action");
                    $actionButton .='&emsp;&#9662';
                    $actionButton .= '</button>';
                    $actionButton .= '<ul class="dropdown-menu dropdown-menu-right" id="dynamicActionButton'.$encEventid.'" style="width: 250px; cursor: pointer;">';
                    $actionButton .= '</ul>';
                    $actionButton .= '</div>';

                    $scope = "";
                    if ($event['groupid']>0){
                        $g = Group::GetGroup($event['groupid']);
                        $scope = $g->val('groupname').'<br>';
                    }
                    
                    if ($_COMPANY->getAppCustomization()['chapter']['enabled'] && $ev->val('chapterid')) {
                        $chapters = Group::GetChapterNamesByChapteridsCsv($ev->val('chapterid'));
                        if (!empty($chapters)) {
                            $chapters = Arr::GroupBy($chapters, 'groupname');
                    
                            foreach($chapters as $gname => $chptrs){
                                $scope .= '<p class="small"><u>'. $gname.' - '.$_COMPANY->getAppCustomization()['chapter']['name-short-plural'].'</u></p>';
                                foreach ($chptrs as $ch1){
                                    if ($ch1['chaptername']){
                                        $scope .= '<li>'.$ch1['chaptername'].'</li>';
                                    } else {
                                        $scope .= '-';
                                    }
                                }
                            }
                        } else {
                            $scope .= '-';
                        }
                    }

                    if($event['collaborating_chapterids_pending']){ 
                        $chaptersCollebratedBetweenPending = $ev->getEventPendingCollabroatedChapters();
                        if ($chaptersCollebratedBetweenPending) {
                            $scope .= '<p><u><small>'.gettext("Pending Collaboration Requests").':</small></u></p>';
                            $scope .= '<p><small>'.$chaptersCollebratedBetweenPending.'</small></p>';
                        }
                    }

                    if ($_COMPANY->getAppCustomization()['channel']['enabled'] && $event['channelid']) {
                        $scope .= '<p>'.$_COMPANY->getAppCustomization()['channel']['name-short']."<p>";
                        $scope .=  $event['channelid'] ?  '<li>'.htmlspecialchars(Group::GetChannelName($event['channelid'],$event['groupid'])['channelname']).'</li>' : '-';
                    }

                    if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled'] && !empty($event['listname'])) { // For now Dynamic list is enabled only on Admin Content
                        $scope .= '<p>'.gettext("Dynamic List").'</p>';
                        if ($event['listname']){
                        
                            $lists = explode('^',$event['listname']);
                            foreach ($lists as $l1){
                                $scope .= '<li>'.htmlspecialchars($l1).'</li>';
                            }
                        } else {
                            $scope .= '-';
                        }
                    }
                    $scope = $scope ?: '-';


                   
                   
            ?>
                    <tr>
                        <td><?= $_COMPANY->encodeIdForReport($event['eventid']); ?></td>
                        <td><?=  $eventTitle; ?></td>
                        <td><?= $scope; ?></td>
                        <td><?= $startDate; ?></td>
                        <?php if($state_filter == 2){ 
                            $approval = $ev->getApprovalObject();
                            $approvalStatus="";
                            if($approval){
                              $approvalStatus =  ucwords($approval->val('approval_status')).'<br>Stage '.$approval->val('approval_stage');
                            }
                            ?>
                        <td><?=$approvalStatus; ?></td>
                        <?php }else{?>
                        <td><?= $attendees; ?></td>
                        <?php }?>
                        <td><?= $actionButton; ?></td>
                    </tr>
            <?php             
                }
            ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<script>
    $(document).ready(function() {
        var searchText = '<?= $_GET['searchText']??'';?>';
        $('#my_event_table').DataTable( {
            "order": [],
			"bPaginate": true,
			"bInfo" : false,
            'language': {
               url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
            },	
            'aoColumnDefs': [{
                'bSortable': false,
                'aTargets': [-1]
             }],
             "initComplete": function(settings, json) {
                // Apply the search value and trigger filtering
                this.api().search(searchText).draw();
                // Update the search box UI
                $('#my_event_table input[type="search"]').val(searchText);
            }
                
        });
    });

    function filterMyEventsSubmissions() {
        var byState = $("#filterByState").val();
	    var byYear = $("#filterByYear").val();
        if (typeof byState === 'undefined' || byState === null){
            byState = '';
        } else {
            localStorage.setItem("state_filter", byState);
        }
        if (typeof byYear === 'undefined'  || byYear === null){
            byYear = '';
        } else {
            localStorage.setItem("year_filter", byYear);
        }

        getMyEventsSubmissions();
    }
  
</script>