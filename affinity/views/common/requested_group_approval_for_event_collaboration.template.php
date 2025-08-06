<?php 

/**
 * Dependencies
 * 
 * $event object
 * 
 */

$collaborating_groupids_pending = explode(',', $event->val('collaborating_groupids_pending')??'');
/**
 * 
 * Calculating the pending chapters group and merging it with the pending group is necessary 
 * because we want to display the pending chapter approval for that particular group.
 * This change requred due to, if any chapter is approved, the parent group will be auto-approved, 
 * as discussed in the Engineering call on November 27th, 2024.
 * 
 */
if(!empty($event->val('collaborating_chapterids_pending'))) { 
    $chapters = Group::GetChapterNamesByChapteridsCsv($event->val('collaborating_chapterids_pending'));
    $chaptersGroupIds = array_column($chapters,'groupid');
    $collaborating_groupids_pending = array_filter(array_unique(array_merge($collaborating_groupids_pending,$chaptersGroupIds)));
}

$groupsWithPendingApprovals = Group::GetGroups($collaborating_groupids_pending);
if (!empty($groupsWithPendingApprovals)){ 
?>
<div class="col-12 alert-info p-3 mt-3" >
	<p class="text-center"><?= sprintf(gettext('The organizer of this event has requested authorization to collaborate with the following %s.'),$_COMPANY->getAppCustomization()['group']['name-short-plural']); ?></p>
    <hr>
    <?php foreach($groupsWithPendingApprovals as $groupWithPendingApproval){ 
        $approvers = $groupWithPendingApproval->getGroupApproversToAcceptTopicCollaborationInvites();
        
        $disabled ='';
        if ( !$_USER->isCompanyAdmin() && !$_USER->isZoneAdmin($groupWithPendingApproval->val('zoneid')) && !in_array($_USER->id(),array_column($approvers,'userid'))) {
            $disabled = 'disabled';
        }
    ?>
        <div class="col-6">

            <div class="col-6">
                <p><?= $groupWithPendingApproval->val('groupname'); ?></p>
            </div>
            <div class="col-6 text-center" id="approveBtn<?= $_COMPANY->encodeId($groupWithPendingApproval->id())?>">
                <button aria-label="<?= sprintf(gettext("Accept collaboration for %s"),$groupWithPendingApproval->val('groupname'))?>" class="btn btn-primary confirm rsvp-approve-btn btn-sm btn-inline mr-2" <?= $disabled; ?> onclick="approveEventGroupCollaboration('<?= $_COMPANY->encodeId($event->id())?>','<?= $_COMPANY->encodeId($groupWithPendingApproval->id())?>')" title="<?= gettext("Are you sure you want to accept collaboration?")?>">
                    <?= gettext("Accept Collaboration")?>
                </button>
                <button aria-label="<?= sprintf(gettext("Deny collaboration for %s"),$groupWithPendingApproval->val('groupname'))?>" class="btn btn-danger confirm rsvp-deny-btn btn-sm btn-inline" <?= $disabled; ?> onclick="denyEventGroupCollaboration('<?= $_COMPANY->encodeId($event->id())?>','<?= $_COMPANY->encodeId($groupWithPendingApproval->id())?>')" title="<?= gettext("Are you sure you want to deny collaboration?")?>">
                    <?= gettext("Deny Collaboration")?>
                </button>
            </div>
        <?php
            $filteredCollaboaringChapterids = Group::GetGroupChaptersFromChapterIdsCSV($groupWithPendingApproval->id(),$event->val('collaborating_chapterids_pending') );
            if (!empty($filteredCollaboaringChapterids)) {
                $chaptersWithPendingApprovals = Group::GetChapterNamesByChapteridsCsv($filteredCollaboaringChapterids);
        
        ?>

            <div class="col-12">
            <?php foreach($chaptersWithPendingApprovals as $chapter){ 
//                $approvers = Group::GetChaptersApproversToAcceptTopicCollaborationInvites($chapter['chapterid']);
//                $approvers  = array_column($approvers,'userid');
//                $disableAction = ' disabled';
//                if ($_USER->isCompanyAdmin() || $_USER->isZoneAdmin($groupWithPendingApproval->val('zoneid')) || in_array($_USER->id(),$approvers)) {
//                    $disableAction  = '';
//                }
//                if ($_USER->isRegionallead($chapter['groupid'], $chapter['regionids']) || $_USER->canCreateContentInGroupChapter($chapter['groupid'],$chapter['regionids'], $chapter['chapterid'])) {
//                   $disableAction  = '';
//                }
                $disableAction = $_USER->canPublishContentInGroupChapterV2($chapter['groupid'],$chapter['regionids'],$chapter['chapterid']) ? '' : 'disabled';
            ?>
                <div class="col-6">
                    <p><i class="fas fa-globe" style="" aria-hidden="true"></i> <?=  $chapter['chaptername']; ?></p>
                </div>
                <div class="col-6 text-center chapters_approver_<?= $_COMPANY->encodeId($chapter['groupid'])?>" id="approveBtnChapter<?= $_COMPANY->encodeId($chapter['chapterid'])?>">
                    <button aria-label="<?= sprintf(gettext("Accept collaboration for %s"),$chapter['chaptername'])?>" class="btn btn-primary rsvp-approve-btn confirm btn-sm btn-inline mr-2" <?= $disableAction; ?> onclick="approveEventChapterCollaboration('<?= $_COMPANY->encodeId($event->id())?>','<?= $_COMPANY->encodeId($chapter['groupid'])?>','<?= $_COMPANY->encodeId($chapter['chapterid'])?>')" title="<?= gettext("Are you sure you want to accept collaboration?")?>">
                        <?= gettext("Accept Collaboration")?>
                    </button>
                    <button aria-label="<?= sprintf(gettext("Deny collaboration for %s"),$chapter['chaptername'])?>" class="btn btn-danger rsvp-deny-btn confirm btn-sm btn-inline" <?= $disableAction; ?> onclick="denyEventChapterCollaboration('<?= $_COMPANY->encodeId($event->id())?>','<?= $_COMPANY->encodeId($chapter['groupid'])?>','<?= $_COMPANY->encodeId($chapter['chapterid'])?>')" title="<?= gettext("Are you sure you want to deny collaboration?")?>">
                        <?= gettext("Deny Collaboration")?>
                    </button>
                </div>
               
            <?php } ?>
            </div>
        <?php } ?>

        </div>
    <?php } ?>
</div>


<script>
    function approveEventGroupCollaboration(eventid, groupid) {
        $.ajax({
            url: 'ajax_events.php?approveEventGroupCollaboration=1',
            type: 'GET',
            data: {'eventid':eventid,'groupid':groupid},
            success: function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
                        if (jsonData.status == 1){
                            $("#approveBtn"+groupid).html('<small><i class="fa fa-check"></i></small>');
                        } else if (jsonData.status == 2) {
                            $("#approveBtn"+groupid).html('<small><i class="fa fa-check"></i></small>');
                            $(".chapters_approver_"+groupid).html('<small><i class="fa fa-check"></i></small>');
                        }
                    });
                } catch(e) {
                    swal.fire({title: 'Error', text: "Unknown error."}); 
                }
            }
        });
    }
    function approveEventChapterCollaboration(eventid, groupid, chapterid) {
        $.ajax({
            url: 'ajax_events.php?approveEventChapterCollaboration=1',
            type: 'GET',
            data: {'eventid':eventid,'groupid':groupid,'chapterid':chapterid},
            success: function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
                        if (jsonData.status == 1){
                            $("#approveBtnChapter"+chapterid).html('<small><i class="fa fa-check"></i></small>');
                        }
                    });
                } catch(e) {
                    swal.fire({title: 'Error', text: "Unknown error."}); 
                }

                
            }
        });
    }
    function denyEventGroupCollaboration(eventid, groupid) {
        $.ajax({
            url: 'ajax_events.php?denyEventGroupCollaboration=1',
            type: 'GET',
            data: {'eventid':eventid,'groupid':groupid},
            success: function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
                        if (jsonData.status == 1){
                            $("#approveBtn"+groupid).html('<small><i class="fa fa-times text-danger"></i> Denied</small>');
                        } else if (jsonData.status == 2) {
                            $("#approveBtn"+groupid).html('<small><i class="fa fa-times text-danger"></i> Denied</small>');
                            $(".chapters_approver_"+groupid).html('<small><i class="fa fa-times text-danger"></i> Denied</small>');
                        }
                    });
                } catch(e) {
                    swal.fire({title: 'Error', text: "Unknown error."}); 
                }
            }
        });
    }
    function denyEventChapterCollaboration(eventid, groupid, chapterid) {
        $.ajax({
            url: 'ajax_events.php?denyEventChapterCollaboration=1',
            type: 'GET',
            data: {'eventid':eventid,'groupid':groupid,'chapterid':chapterid},
            success: function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
                        if (jsonData.status == 1){
                            $("#approveBtnChapter"+chapterid).html('<small><i class="fa fa-times text-danger"></i> Denied</small>');
                        }
                    });
                } catch(e) {
                    swal.fire({title: 'Error', text: "Unknown error."}); 
                }
            }
        });
    }
</script>
<?php } ?>