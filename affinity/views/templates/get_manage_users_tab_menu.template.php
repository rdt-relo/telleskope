<style>
	.active{
		color: #495057;
	}
	.lead-button {
		margin: 10px 0 0 0;
		padding-bottom: 30px;
	}
</style>

<?php

	$chaptersCount = $group->getChapterCount();
	$totalChannels = $group->getChannelCount();

?>

<div class="col-md-12">
    <div class="row">
        <div class="col-md-11">
            <h2><?=gettext("Manage Members and Leaders") .' - '. $group->val('groupname_short');?></h2>
        </div>
        <div class="col-md-1">
            <div class="pull-right text-right" style="margin-bottom: -16px;">
                <div style="margin-left: 10px; margin-bottom: 0; margin-right: 18px;margin-top: 10px;">
                    <?php
                    $page_tags = 'manage_members,manage_group_leads,manage_chapter_leads,manage_channel_leads,manage_user';
                    ViewHelper::ShowTrainingVideoButton($page_tags);
                    ?>
                </div>
            </div>
        </div>        
    </div><hr class="lineb" >
</div>
<div class="m-2">&nbsp;</div>
<div class="col-md-12">
	<div class="col-md-12 member-lead-nav">		
		<ul class="nav nav-tabs" role="tablist">
			<li role="none" class="nav-item"><a role="tab" tabindex="0" class="nav-link manage-nav-link  active" style="color: #111;" data-toggle="tab" href="#leads" onclick="mangeGroupLeads('<?=$encGroupId;?>')"><?= sprintf(gettext("%s Leaders"),$_COMPANY->getAppCustomization()['group']['name-short']);?></a></li>

		    <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled'] && $chaptersCount>0) { ?>
			<li role="none" class="nav-item"><a role="tab" tabindex="-1" class="nav-link manage-nav-link" data-toggle="tab" style="color: #111;" href="#chapterLeads" onclick="mangeChapterLeads('<?=$encGroupId;?>')"><?= sprintf(gettext("%s Leaders"),$_COMPANY->getAppCustomization()['chapter']['name-short']);?></a></li>
		    <?php } ?>

		    <?php if ($_COMPANY->getAppCustomization()['channel']['enabled'] && $totalChannels>0) { ?>
			<li role="none" class="nav-item"><a role="tab" tabindex="-1" class="nav-link manage-nav-link" data-toggle="tab" style="color: #111;" href="#chanelLeads" onclick="mangeChannelLeads('<?=$encGroupId;?>')"><?= sprintf(gettext("%s Leaders"),$_COMPANY->getAppCustomization()['channel']['name-short']);?></a></li>
		    <?php } ?>

			<li role="none" class="nav-item"><a role="tab" tabindex="-1" class="nav-link manage-nav-link" data-toggle="tab" style="color: #111;" href="#members" onclick="mangeGroupMemberList('<?=$encGroupId;?>')"><?= $_COMPANY->getAppCustomization()['group']['memberlabel'].'s'?></a></li>

			<?php if ($group->val("group_type") == Group::GROUP_TYPE_REQUEST_TO_JOIN && ($_USER->isGrouplead($groupid) || $_USER->isAdmin()) && ($_ZONE->val('app_type') !== 'talentpeak' || !$group->isTeamsModuleEnabled())){?>
				<li role="none" class="nav-item"><a role="tab" tabindex="-1" href="#joinRequests" data-toggle="tab" style="color: #111;" class="nav-link manage-nav-link" onclick="getGroupJoinRequests('<?=$encGroupId;?>')"><?=  sprintf(gettext('%s Join Requests'),$_COMPANY->getAppCustomization()['group']["name-short"]); ?></a></li>
			<?php } ?>

            <?php if(($_SESSION['app_type'] == 'affinities' || $_SESSION['app_type'] == 'talentpeak') && $group->val('group_type') != Group::GROUP_TYPE_MEMBERSHIP_DISABLED && $_COMPANY->getAppCustomization()['group']['allow_invite_members']) { ?>
            <li role="none" class="nav-item"><a role="tab" tabindex="-1" class="nav-link manage-nav-link" data-toggle="tab" style="color: #111;" href="#inviteUsers" onclick="inviteGroupMembers('<?=$encGroupId;?>')" ><?= gettext("Invite Users");?></a></li>
            <?php } ?>

		</ul>		
	</div>
	<div class="tab-content col-md-12">
		<div class="tab-pane active" id="leads">
		</div>
		<div class="tab-pane fade in" id="chapterLeads">
		</div>
		<div class="tab-pane fade in" id="chanelLeads">
		</div>		
		<div class="tab-pane fade in" id="members">
		</div>
		<div class="tab-pane fade in" id="joinRequests">
		</div>	
		<div class="tab-pane fade in" id="inviteUsers">
			<div id="inviteUsersContents"></div>
		</div>
	</div>
</div>
<div id="lead_form_contant"></div>
<script>
	$(document).ready(function() {
		mangeGroupLeads("<?= $encGroupId;?>")
	});

 $(function() {                       
	$(".manage-nav-link").click(function() { 
	  $('.manage-nav-link').attr('tabindex', '-1');
	  $(this).attr('tabindex', '0');    
	});
  });
  
  $('.manage-nav-link').keydown(function(e) {  
	  if (e.keyCode == 39) {       
		  $(this).parent().next().find(".manage-nav-link:last").focus();       
	  }else if(e.keyCode == 37){       
		  $(this).parent().prev().find(".manage-nav-link:last").focus();  
	  }
  });
</script> 
