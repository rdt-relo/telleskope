<style>
  .nav-link {
	cursor: pointer;
}
.col-md-12{
  float: none;
}
.dynamic-button-container .pull-right .join-request-rep{
    margin-top: -108px;
}
</style>
<div class="col-md-12">
	<div class="row">
			<div class="col-10">          
				<h2><?= $documentTitle = sprintf(gettext('Manage %s'),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1)).' - '. $group->val('groupname');?></h2>
			</div>		
    <hr class="lineb">
</div>
<div class="container inner-background mt-0 pt-0">
  <div class="row">
    <div class="col-md-12" id="reportDownLoadOptions" style="display: none;"></div>
    <div class="col-md-12">
      <div class="col-md-12 col-xs-12">
          <div class="inner-page-title">
              <ul class="nav nav-tabs" role="tablist">
              <li class="nav-item" role="none"><a id="manageTeamsTab" role="tab" aria-selected="true" href="javascript:void(0)" class="manage-teams-section-tab nav-link active" onclick="manageTeams('<?= $_COMPANY->encodeId($groupid) ?>')" data-toggle="tab" ><?= sprintf(gettext("Manage %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1));?></a></li>
              <?php if($group->getTeamProgramType()!= Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
                <li class="nav-item" role="none"><a role="tab" aria-selected="false" href="javascript:void(0)" class="manage-teams-section-tab nav-link" onclick="getUnmatchedUsersForTeam('<?= $_COMPANY->encodeId($groupid) ?>')" data-toggle="tab" ><?= gettext("Registrations");?></a></li>
              <?php } ?>
            </ul>
          </div>
      </div>
    </div>
    <div class=" col-md-12  tab-content" id="manageTeamContent">
       
    </div>
  </div>
</div>
        

<script>
  updatePageTitle('<?= addslashes(sprintf(gettext('%1$s | %2$s'), $documentTitle, $_COMPANY->val('companyname')));?>');

 
</script>