<style>
	.hide{
        display:none;
    }
	.progress_bar{
		background-color: #efefef;
		margin: 5px 0px;
		padding: 15px;
	}
</style>
<?php if ( 0 && /* Disabled submenu event for request to join groups */ $group->val("group_type") == Group::GROUP_TYPE_REQUEST_TO_JOIN && ($_USER->isGrouplead($groupid) || $_USER->isAdmin()) && ($_ZONE->val('app_type') !== 'talentpeak' || !$group->isTeamsModuleEnabled())){ ?>
	<div class="col-md-12">
		<div class="col-md-12 col-xs-12">
			<div class="inner-page-title">
				<ul class="nav nav-tabs">
					<li class="nav-item"><a href="javascript:void(0);" data-toggle="tab"  class="nav-link active" onclick="getGroupMembersListTable('<?= $_COMPANY->encodeId($groupid) ?>')"><?= sprintf(gettext('%s Members'),$_COMPANY->getAppCustomization()['group']["name-short"]); ?></a></li>
				</ul>
			</div>
		</div>
	</div>
<?php } ?>
<div class="tab-content" id="list_view">
</div>
<script>
	$(document).ready(function() {
		getGroupMembersListTable('<?= $_COMPANY->encodeId($groupid); ?>');
	});
</script>