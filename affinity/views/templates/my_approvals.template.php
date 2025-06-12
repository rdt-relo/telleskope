<style>.urgent{color: red;}
#myEventApprovals td {
  white-space:inherit;
}
.dt-layout-cell{
    overflow: visible;
}
#myEventApprovals div.dt-container div.dt-layout-table>div { 
    overflow: auto;
}
.dropdown-menu.show {
    width: max-content;
}
/* For request approval modal to select approvers */
#nextStageApproverSelector .dropdown-menu.show {
    width: 100%; 
}
</style>
<div class="row">
    <div class="col-12">
        <h4><?= gettext("Manage Approvals")?></h4>
        <hr class="lineb" >
    </div>
    
    <div class="col-md-12">   
        <div class="col-sm-6 px-0">
            <div class="form-group">
                <label for="approval_status_filter"><?= gettext('Filter by Approval Status') ?></label>
                <select aria-label="Show All" id="approval_status_filter" class="form-control" onchange="getMyTopicApprovalsData('<?= $topicType; ?>');">
                    <option value="all" <?= $_SESSION['approvalStatus'] == 'all' ? 'selected' : ''; ?>><?= gettext('Show All'); ?></option>
                    <option value="processing" <?= $_SESSION['approvalStatus'] == 'processing'  ? 'selected' : ''; ?>><?= gettext('Processing or Requested'); ?></option>
                    <option value="processed" <?= $_SESSION['approvalStatus'] == 'processed' ? 'selected' : ''; ?>><?= gettext('Processed'); ?></option>
                    <option value="reset" <?= $approvalStatus == 'reset' ? 'selected' : ''; ?>><?= gettext('Cancelled or Reset'); ?></option>
                </select>
            </div>
        </div>


        <div class="col-sm-6">
            <div class="form-group">
                <label for="request_year_filter"><?= gettext('Filter by Request Year') ?></label>
                <select aria-label="Request year" id="request_year_filter" class="form-control" onchange="getMyTopicApprovalsData('<?= $topicType; ?>');">
                <?php
                for($y = 2022; $y<=date('Y'); $y++){
                    $sel = '';
                    if($y == $_SESSION['approvalRequestYear']){ $sel = 'selected'; } ?>
                        <option value="<?= $y; ?>" <?= $sel; ?>><?= $y ?></option>    
                <?php } ?> 
                </select>
            </div>
        </div>
        
       
    </div>
    
   
    <div class="col-md-12">
        <div class="table-responsive " id="list-view">
        <table id="myEventApprovals" class="table table-hover display compact nowrap" style="width: 100%;font-size: 14px; " summary="<?= gettext("Manage Approvals")?>">
            <thead>
                    <tr>
                        <?php foreach ($tableHeaders as $tHeader) { ?>
                            <th><?= gettext($tHeader); ?></th>
                        <?php } ?>     
                    </tr>
                </thead>
                <tbody>
            <?php
            foreach($allApprovals as $approval){
                $topicTypeObj = NULL;
                $topicTypelabel = '';
                if($topicType == TELESKOPE::TOPIC_TYPES['EVENT']){
                    $topicTypeObj = Event::GetEvent($approval['topicid']);
                    $topicTypeLabel = $topicTypeObj ?-> isSeriesEventHead() ? gettext('Event Series') : gettext('Event');
                }elseif($topicType == TELESKOPE::TOPIC_TYPES['NEWSLETTER']){
                    $topicTypeObj = Newsletter::GetNewsletter($approval['topicid']);
                    $topicTypelabel = gettext('Newsletter');
                }elseif($topicType == TELESKOPE::TOPIC_TYPES['POST']){
                    $topicTypeObj = Post::GetPost($approval['topicid']);
                    $topicTypelabel = Post::GetCustomName(false);
                }elseif($topicType == TELESKOPE::TOPIC_TYPES['SURVEY']){
                    $topicTypeObj = Survey2::GetSurvey($approval['topicid']);
                    $topicTypelabel = Survey2::GetCustomName(false);
                }
                
                $topicTypeStatus = $topicTypeObj ? intval($topicTypeObj->val('isactive')) : 0;
                if ($topicTypeStatus == 0 && !$topicTypeObj) {
                    continue;
                }
                $groupObj = Group::GetGroup($topicTypeObj->val('groupid'));
                if(!$groupObj->isActive()){
                    continue;
                }
                $enc_topictype_id = $_COMPANY->encodeId($topicTypeObj->id());
                $encGroupId = $_COMPANY->encodeId($groupObj->id());

                $isStageApprover = in_array($approval['approval_stage'], $approvable_stages_that_user_can_approve);
                $approver = null;
                if ($approval['assigned_to']){
                    $approver = User::GetUser($approval['assigned_to']);
                }
                //getting the last approver only if theere is no current approver
                $lastApprover = null;
                if(!$approver && $approval['approval_stage'] > 1) {
                    $previous_stage = intval($approval['approval_stage']) - 1;
                    $approvalObject = $topicTypeObj->getApprovalObject();
                    $lastApprover = $approvalObject->getLastApproverByStage($previous_stage) ?? '';
                }
                $requestedby = null;
                if ($approval['createdby']){
                    $requestedby = User::GetUser($approval['createdby']);
                }
                    // Initialize chapter and channel scopes
                    $chapterScope = '';
                    $channelScope = '';
                    
                    if ($_COMPANY->getAppCustomization()['chapter']['enabled'] && !empty($topicTypeObj->val('chapterid'))) {
                        
                        if($topicType == TELESKOPE::TOPIC_TYPES['EVENT']){
                            $chapters = $topicTypeObj->getEventChapterNames();
                        }elseif($topicType == TELESKOPE::TOPIC_TYPES['NEWSLETTER']){
                            $chapters = $topicTypeObj->getNewsletterChapterNames();
                        }elseif($topicType == TELESKOPE::TOPIC_TYPES['POST']){
                            $chapters = $topicTypeObj->getPostChapterNames();
                        }elseif($topicType == TELESKOPE::TOPIC_TYPES['SURVEY']){
                            $chapters = $topicTypeObj->getSurveyChapterNames();
                        }

                        $chapterScope = '<p>'.$_COMPANY->getAppCustomization()['chapter']['name-short-plural'].':</p>';
                        foreach ( $chapters  as $chaptername) {
                            $chapterScope .= '<li>'.$chaptername.'</li>';
                        }
                    }
                    
                    if ($_COMPANY->getAppCustomization()['channel']['enabled'] && $topicTypeObj->val('groupid') && !empty($topicTypeObj->val('channelid'))) {
                        $channelNames = [];
                        $channelScope = '<p>'.$_COMPANY->getAppCustomization()['channel']['name-short-plural'].':</p>';
                        $channelDetail = Group::GetChannelName($topicTypeObj->val('channelid'), $topicTypeObj->val('groupid'));
                        $channelScope .= '<li>'.$channelDetail['channelname'].'</li>';
                    }
                    // For mapping status
                    $topicTypeStatusMap = array(0 => 'Inactive', 1 => 'Published', 2 => 'Draft', 3 => 'Under Review', 4 => 'Reviewed', 5 => 'Pending Publish');
                    // Set Event URL
                    $topicType_url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . $viewPath.'?id=' . $_COMPANY->encodeId($topicTypeObj->id()) . '&approval_review=1';
            ?>
           
                <tr id="<?= $_COMPANY->encodeId($approval['approvalid'])?>">
                    <td><?= $_COMPANY->encodeIdForReport($topicTypeObj->id()) ;?></td>
                    <?php
                    $listsNameCsv = '';
                    if(($topicTypeObj->val('groupid') == 0) || ($topicTypeObj->val('listids') != 0) ){
                        $listsNameCsv = ($topicTypeObj->val('listids') != 0) ? DynamicList::GetFormatedListNameByListids($topicTypeObj->val('listids')) : '';
                    }
                    $groupName = $groupObj->val('groupname') ?: '-';
                    // Should be only in event topic
                    $eventSeriesLabel = '';
                    if ($topicType == TELESKOPE::TOPIC_TYPES['EVENT']) {
                        if ($topicTypeObj->val('collaborating_groupids')) {
                            $groupName = $topicTypeObj->getFormatedEventCollaboratedGroupsOrChapters();
                        }
                        $eventSeriesLabel = $topicTypeObj->isSeriesEventHead() ? "<small>[Event series]</small><br>" : '';
                    }

                    ?>
                    <td>
                    <?php if($topicType == TELESKOPE::TOPIC_TYPES['SURVEY']){ ?>
                        <a rel="noopener" onclick="previewSurvey('<?= $encGroupId; ?>','<?= $enc_topictype_id; ?>')" href="javascript:void(0)">
                        <?= $topicTypeObj->getTopicTitle()?><br></a>
                    <?php }else{ ?>
                        <a target="_blank" rel="noopener" href="<?= $topicType_url ?>">
                        <?= $eventSeriesLabel . $topicTypeObj->getTopicTitle()?><br></a>
                    <?php } ?>
                    </td>

                    <td><?= (($groupObj->id() == 0 && $listsNameCsv) || $listsNameCsv) ? '<strong>Dynamic List</strong><br>'. $listsNameCsv : $groupName  . '<hr class="m-0 p-0">' . $chapterScope ." ". $channelScope ?></td>                    
                    <?php if($topicType == TELESKOPE::TOPIC_TYPES['EVENT']){ ?>
                    <td><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($topicTypeObj->val('start'),true,true,true) ?></td>
                    <?php } ?>

                    <td><?= $topicTypeStatusMap[$topicTypeObj->val('isactive')]; ?></td>

                    <td><small>
                        <b><?=gettext("Approval Status")?>:</b>
                            <br>
                            <?= ucwords($approval['approval_status']) ?>
                            <?= in_array($approval['approval_status'], [Approval::TOPIC_APPROVAL_STATUS['PROCESSING'], Approval::TOPIC_APPROVAL_STATUS['REQUESTED']]) ? ('<br>'. gettext(' Stage ') . $approval['approval_stage']) : '' ?>
                        <br>

                        <b><?=gettext("Submitted On")?>:</b>
                            <br>
                            <?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($approval['createdon'],true,true,true) ?>

                        <br>

                        <?php if ($approver) { ?>
                        <b><?=gettext("Assigned To")?>:</b>
                            <br>
                            <?=$approver->getFullName() .' (' . $approver->val('email') . ')' ?>
                        <?php } elseif ($lastApprover) { ?>
                        <b><?=gettext("Last Approver")?>:</b>
                            <br>
                            <?=$lastApprover->getFullName() .' (' . $lastApprover->val('email') . ')' ?>
                        <?php } ?>

                    </small></td>
                    <!-- <td>zip</td> -->

                    <td>
                    <div class="dropdown" id="action<?= $_COMPANY->encodeId($approval['approvalid'])?>">
                    <button id="dropdownMenuButton" class="btn btn-sm btn-affinity dropdown-toggle" title="Action" type="button" data-toggle="dropdown"> <?= gettext('Action');?></button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <button class="btn btn-no-style dropdown-item" onclick="viewApprovalDetail('<?= $_COMPANY->encodeId($approval['topicid'])?>','<?= $_COMPANY->encodeId($approval['approvalid'])?>','<?=$topicType?>','<?=$topicTypelabel?>', 'my_approvals')" ><i class="fa fa-eye" title="View"></i> <?=gettext('View Details');?></button>
                                <?php if (/*$_USER->canManageZoneEvents() && disabled as per ticket #4132 */ in_array($topicTypeStatus, [Teleskope::STATUS_DRAFT, Teleskope::STATUS_UNDER_REVIEW, Teleskope::STATUS_UNDER_APPROVAL]) && ($isStageApprover) && in_array($approval['approval_status'], [Approval::TOPIC_APPROVAL_STATUS['PROCESSING'],Approval::TOPIC_APPROVAL_STATUS['REQUESTED']])){  ?>
                                <button class="btn btn-no-style dropdown-item" onclick="assignUserForApprovalModal('<?= $_COMPANY->encodeId($approval['topicid'])?>','<?= $_COMPANY->encodeId($approval['approvalid'])?>','<?= $approval['approval_stage'] ?>','<?=$topicType?>')" class=""><i class="fa fa-user-plus" aria-hidden="true"></i> <?=gettext('Assign To');?></button>
                                <?php } ?>

                                <?php if (in_array($topicTypeStatus, [Teleskope::STATUS_DRAFT, Teleskope::STATUS_UNDER_REVIEW, Teleskope::STATUS_UNDER_APPROVAL]) && $isStageApprover && !in_array($approval['approval_status'], [Approval::TOPIC_APPROVAL_STATUS['RESET'], Approval::TOPIC_APPROVAL_STATUS['CANCELLED']])){ ?>
                                <button class="btn btn-no-style dropdown-item" onclick="updateApprovalStatusModal('<?= $_COMPANY->encodeId($approval['topicid'])?>','<?= $_COMPANY->encodeId($approval['approvalid'])?>','','<?=$topicType?>')" ><i class="fa fa-check-circle" aria-hidden="true"></i> <?=gettext('Approve');?> / <?=gettext('Deny');?></button>
                                <?php } ?>

                                <?php if (in_array($topicTypeStatus, [Teleskope::STATUS_DRAFT, Teleskope::STATUS_UNDER_REVIEW, Teleskope::STATUS_UNDER_APPROVAL]) && $isStageApprover && !in_array($approval['approval_status'], [Approval::TOPIC_APPROVAL_STATUS['RESET'], Approval::TOPIC_APPROVAL_STATUS['CANCELLED']])){ ?>
                                <button class="btn btn-no-style dropdown-item" onclick="cancelApprovalStatus('<?= $_COMPANY->encodeId($approval['topicid'])?>','<?=$topicType?>')" ><i class="fa fa-times" aria-hidden="true"></i> <?=gettext('Cancel Request');?> </button>
                                <?php } ?>

                                <?php if((($_USER->canManageZoneEvents() && in_array($topicTypeStatus, [Teleskope::STATUS_DRAFT, Teleskope::STATUS_UNDER_REVIEW, Teleskope::STATUS_UNDER_APPROVAL]) && $isStageApprover && in_array($approval['approval_status'], [Approval::TOPIC_APPROVAL_STATUS['DENIED'], Approval::TOPIC_APPROVAL_STATUS['RESET'], Approval::TOPIC_APPROVAL_STATUS['CANCELLED']])) || $topicTypeStatus==0)){ ?>
                                <button class="btn btn-no-style dropdown-item confirm" onclick="deleteApproval('<?= $_COMPANY->encodeId($approval['approvalid'])?>', '<?= $topicType ?>')" data-confirm-noBtn="<?=gettext('No')?>" data-confirm-yesBtn="<?=gettext('Yes')?>" title="Are you sure you want to delete this Approval Request?"><i class="fa fa-trash" aria-hidden="true"></i> <?=gettext('Delete');?></button>
                                <?php } ?>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

   
   
</div>

    <div class="modal" id="approvalDetailModal" tabindex="-1" role="dialog">
    <div aria-label="<?= gettext("Event Approval Detail");?>" class="modal-dialog modal-xl modal-dialog-w900" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><span id="topicTypeEnglish"></span> Approval Detail</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="col-md-12 text-center px-0" id="approvalDetail">
              
          </div>
        </div>
        <div class="modal-footer">
          
        </div>
      </div>
    </div>
  </div>
</body>
<script>
    // for edge case of reinitialising data table on rapid tab switch throwing a warning
    if(typeof myEventApprovalsTable === 'undefined'){
        let myEventApprovalsTable;
    }
    $(document).ready(function() {
        // for edge case of reinitialising data table on rapid tab switch throwing a warning
        if($.fn.DataTable.isDataTable(('#myEventApprovals'))){
            myEventApprovalsTable.destroy();
        }

        myEventApprovalsTable = $('#myEventApprovals').DataTable( {
            responsive: true,    
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
                
        });
    });
    function deleteApproval(s,topicType){
        $.ajax({
            url: 'ajax_approvals.php?deleteApproval=1',
            type: "post",
            data: {topicType:topicType,approvalid:s},
            success : function(data) {
                if(data){
                    $("#"+s).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
                } else {
                    swal.fire({title: 'Error!',text:"Something went wrong. Please try again"});
                }
            }
        });
    }
</script>
</html>
