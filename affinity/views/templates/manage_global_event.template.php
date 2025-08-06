<div class="col-md-12">
    <div class="row">
    <div class="col-12">
        <h2><?= gettext("Manage Events")?> - <?php 
       echo $res =  $groupid !=0  ? $group->val('groupname_short') : $_COMPANY->getAppCustomization()['group']['groupname0'];       
        ?></h2>
        <hr class="lineb" >
      </div>
      <?php
        // Commented by Aman on 4/19
        //<a class="dropdown-item" href=\'event_analytics?groupid='.$_COMPANY->encodeId($groupid).'\'>Analytics</a>
        $refresh = '';
        $newbtn = '';
          if ($_USER->canCreateContentInGroupSomething($groupid)) {
              if ($_USER->canCreateOrPublishContentInScopeCSV($groupid, 0, 0) &&
                  $_COMPANY->getAppCustomization()['event']['cultural_observances']['enabled']
              ) { // Holidays can be created at group level only
                  $newbtn = '<div class="btn-group mr-3">
                                <button type="button" class="btn btn-primary manage-cultural-ob-btn" onclick=\'manageHolidays("' . $_COMPANY->encodeId($groupid) . '")\'>
                                    ' . gettext("Manage Cultural Observances") . '
                                </button>
                            </div> ';
              }
              
            if(Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['EVENT_CREATE_BEFORE'])){
                $callOtherMethod = base64_url_encode(json_encode(array("method"=>"newEventForm","parameters"=>array($_COMPANY->encodeId($groupid),(!$groupid ? 1 : 0))))); // base64_encode for prevent js parsing error
                $eventnewbtn = '<a href="javascript:void(0)" class="dropdown-item" onclick="loadDisclaimerByHook(\'' . $_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['EVENT_CREATE_BEFORE']) .  '\', \'' . $_COMPANY->encodeId(0) . '\',0, \'' . $callOtherMethod. '\')" >' . gettext("Create Event") . '</a>';
            } else {
                $eventnewbtn = '<a href="javascript:void(0)" class="dropdown-item" onclick=\'newEventForm("' . $_COMPANY->encodeId($groupid) . '",'.(!$groupid ? 1 : 0).')\' >' . gettext("Create Event") . '</a>';
            }

              $newbtn .= '<div class="btn-group">
                            <button type="button" class="btn btn-primary dropdown-toggle create-event-btn" data-toggle="dropdown">
                                ' . gettext("Create Event") . ' &#9662;
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                
                                '.$eventnewbtn.'

                                <a href="javascript:void(0)" class="dropdown-item" onclick=\'loadCreateEventGroupModal("' . $_COMPANY->encodeId($groupid) . '",0)\'>' . gettext("Create Event Series") . '</a>
                            </div>
                         </div>';

          }
        include(__DIR__ . "/manage_section_dynamic_button.html");
   
      
      ?>
        <div class="col-md-12">
            <div class="col-md-4">
                <div class="form-group col-md-12 ">
                    <label for="filterByState" style="font-size:small;"><?= sprintf(gettext("Filter by %s State"), 'Event');?></label>
                    <select aria-label="<?= sprintf(gettext("Filter by %s state"), 'Event');?>" class="form-control" onchange="filterEvents('<?= $encGroupId; ?>');" id="filterByState" style="font-size:small;border-radius: 5px;">
                      <option value="<?= $_COMPANY->encodeId(2)?>" data-state="draft" <?= $state_filter == 2 ? 'selected' : '' ?> ><?= gettext("Draft / Not Published");?></option>
                      <option value="<?= $_COMPANY->encodeId(1)?>" data-state="published" <?= $state_filter == 1 ? 'selected' : '' ?> ><?= gettext("Published");?></option>
                      <option value="<?= $_COMPANY->encodeId(0)?>" data-state="cancelled" <?= $state_filter == 0 ? 'selected' : '' ?> ><?= gettext("Cancelled");?></option>
                    </select>
                    <input type="hidden" name="publishedStateEnId" value="<?= $_COMPANY->encodeId(1)?>" id="publishedStateEnId"> <? //  Added a hidden field to serve as a reference for comparison in the filterEvents js method.  ?>
                  </div>
            </div>
            <div class="col-md-4">
                <div class="form-group col-md-12 ">
                <?php if($groupid>0){ 
                  $chapters = Group::GetChapterList($groupid);
                  $channels= Group::GetChannelList($groupid);
                  ?>
                  
                    <label for="filterByGroup" style="font-size:small;"><?= gettext("Filter by Scope");?></label>
                    <select id="filterByGroup"  onchange="filterEvents('<?= $encGroupId; ?>');" style="font-size:small;border-radius: 5px;" class="form-control" >
                      <?php if ($_USER->canCreateOrPublishOrManageContentInScopeCSV($groupid,0,0)) { ?>
                      <option data-section="<?= $_COMPANY->encodeId(0) ?>" value="<?= $_COMPANY->encodeId(0);?>" <?= $erg_filter_section == 0 && $erg_filter == 0 ? 'selected' : '' ?> ><?= Group::GetGroupName($groupid)?></option>
                      <?php } ?>
                  <?php if ($chapters) { ?>
                    <optgroup label="<?=$_COMPANY->getAppCustomization()['chapter']['name-short-plural']?>">
                    <?php foreach ($chapters as $chapter) { ?>
                      <?php if ($_USER->canCreateOrPublishOrManageContentInScopeCSV($groupid,$chapter['chapterid'],0)){ ?>
                        <option data-section="<?= $_COMPANY->encodeId(1) ?>" value="<?= $_COMPANY->encodeId($chapter['chapterid'])?>" <?= $erg_filter_section == 1 && $erg_filter == $chapter['chapterid'] ? 'selected' : '' ?> ><?= htmlspecialchars($chapter['chaptername']); ?></option>
                      <?php } ?>
                    <?php
                    }
                    ?>
                    </optgroup>
                  <?php } ?>
                  <?php if ($channels) { ?>
                    <optgroup label="<?=$_COMPANY->getAppCustomization()['channel']['name-short-plural']?>">
                    <?php foreach ($channels as $channel) {     ?>
                      <?php if ($_USER->canCreateOrPublishOrManageContentInScopeCSV($groupid,0,$channel['channelid'])){ ?>
                        <option data-section="<?= $_COMPANY->encodeId(2) ?>" value="<?= $_COMPANY->encodeId($channel['channelid'])?>" <?= $erg_filter_section == 2 && $erg_filter == $channel['channelid'] ? 'selected' : '' ?> ><?= htmlspecialchars($channel['channelname']); ?></option>
                      <?php } ?>
                    <?php
                    }
                    ?>
                    </optgroup>
                  <?php } ?>
                  </select>
                  
                <?php } ?>
                </div>
        
            </div>
            <div class="col-md-4 ">
                <div class="form-group col-md-12 " style="float:right !important;">
                    <label for="filterByYear" style="font-size:small;"><?= gettext("Filter by Calendar Year");?></label>
                    <select aria-label="<?= gettext("Filter by calendar year");?>" class="form-control" id="filterByYear" onchange="filterEvents('<?= $encGroupId; ?>'
                    );" style="font-size:small;border-radius: 5px;">
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
            <div class="col-12 px-5" id="filterEventsCheckbox" style="<?=($state_filter != 1)?'display:none':''?>">
              
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="upcomingEvents" value="upcomingEvents" onchange="filterEvents('<?= $encGroupId; ?>');">
                <label class="form-check-label" for="upcomingEvents"><?= gettext('Upcoming Events')?></label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="pastEvents" value="pastEvents" onchange="filterEvents('<?= $encGroupId; ?>');">
                <label class="form-check-label" for="pastEvents"><?= gettext('Past Events')?></label>
              </div>
            <?php  if($_COMPANY->getAppCustomization()['event']['reconciliation']['enabled']){ ?>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="reconciledEvent" value="reconciledEvent" onchange="filterEvents('<?= $encGroupId; ?>');"> 
                <label class="form-check-label" for="reconciledEvent"><?= gettext('Reconciled Events')?></label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="notReconciledEvent" value="notReconciledEvent" onchange="filterEvents('<?= $encGroupId; ?>');"> 
                <label class="form-check-label" for="notReconciledEvent"><?= gettext('Not Reconciled Events')?></label>
              </div>
            <?php } ?>
            </div>
            
            <!-- Draft State Filters -->
            <div class="col-12 px-5" id="filterDraftEventsCheckbox" style="<?=($state_filter == 1)?'display:none':''?>">
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="collabEvents" value="collabEvents" onchange="filterEvents('<?= $encGroupId; ?>');">
                <label class="form-check-label" for="collabEvents"><?= gettext('Collaboration Events Only')?></label>
              </div>
            </div>       
        </div>
        <div class="clearfix"></div>
        <div class="col-md-12">            
            <div class="table-responsive " id="eventTable">
                <?php
                    include(__DIR__ . "/group_events_table.template.php");
                ?>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="approvalDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl modal-dialog-w900" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Event Approval Detail</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="col-md-12 text-center p-0" id="approvalDetail">
              
          </div>
        </div>
        <div class="modal-footer">
          
        </div>
      </div>
    </div>
  </div>

<script>
    $(document).ready(function() {
        //initial for blank profile picture
        $('.initial').initial({
            charCount: 2,
            textColor: '#ffffff',
            color: window.tskp?.initial_bgcolor ?? null,
            seed: 0,
            height: 50,
            width: 50,
            fontSize: 20,
            fontWeight: 300,
            fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
            radius: 0
        });
    });
</script>