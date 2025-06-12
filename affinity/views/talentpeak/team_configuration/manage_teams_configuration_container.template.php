<style>
	.active{
		color: #495057;
	}
	.lead-button {
		margin: 10px 0 0 0;
		padding-bottom: 30px;
	}
    .team-tabs a:hover{
        font-weight: unset !important;
    }
    .team-tabs a.active{
        font-weight: unset !important;
    }
    .nav-link-affnity {
        padding: .5rem .8rem !important;
    }
</style>


<div class="col-md-12">
    <div class="row">
        <div class="col-md-11">
            <h2><?php echo $documentTitle = sprintf(gettext("%s Configuration"), Team::GetTeamCustomMetaName($group->getTeamProgramType(),1)) .' - '. $group->val('groupname_short');?></h2>
        </div>
        <div class="col-md-1">
            <div class="pull-right text-right" style="margin-bottom: -16px;">
                <?php
                $page_tags = 'manage_teams';
                ViewHelper::ShowTrainingVideoButton($page_tags);
                ?>
            </div>
        </div>        
    </div>
    <hr class="lineb" >

<div class="m-2">&nbsp;</div>
<div class="col-md-12">
  <ul class="nav nav-tabs team-tabs" role="tablist">
      <li class="nav-item" role="none"><a role="tab" aria-selected="true" href="#teamSettingTab" class="nav-link active nav-link-affnity" data-toggle="tab" id="teamSettingTab" onclick="manageProgramTeamSetting('<?= $_COMPANY->encodeId($groupid);?>')"><?= gettext('Setting'); ?></a></li>

      <li class="nav-item" role="none">
          <a role="tab" aria-selected="false" href="#teamroletab" onclick="manageProgramTeamRoles('<?= $_COMPANY->encodeId($groupid);?>')" class="nav-link nav-link-affnity" data-toggle="tab" id="teamroletab"  ><span><?= sprintf(gettext('%s Role Types'), '' /*Team::GetTeamCustomMetaName($group->getTeamProgramType())*/ )?></span>
          </a>
      </li>

  <?php if(!in_array(TEAM::PROGRAM_TEAM_TAB['ACTION_ITEM'], $hiddenTabs)){ ?>
      <li class="nav-item" role="none"><a role="tab" aria-selected="false" id="actionItemtab" onclick="manageTeamActionItemsTemplates('<?= $_COMPANY->encodeId($groupid);?>')" href="#actionItemtab" class="nav-link nav-link-affnity" data-toggle="tab" ><span><?= sprintf(gettext('%s Action Items'), '' /*Team::GetTeamCustomMetaName($group->getTeamProgramType())*/ )?></span></a></li>

  <?php } ?>

  <?php if(!in_array(TEAM::PROGRAM_TEAM_TAB['TOUCH_POINT'], $hiddenTabs)){ ?>
      <li class="nav-item" role="none"><a role="tab" aria-selected="false" onclick="manageTeamTouchpointsTemplates('<?= $_COMPANY->encodeId($groupid);?>')" href="#touchpointstab" class="nav-link " data-toggle="tab" id="touchpointstab"><span id="" ><?= sprintf(gettext("%s Touch Points"),'' /* Team::GetTeamCustomMetaName($group->getTeamProgramType()) */ )?></span></a>  </li>
  <?php } ?>

  <?php if(!in_array($group->getTeamProgramType(), [Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV'],Team::TEAM_PROGRAM_TYPE['CIRCLES']])){ ?>
      <li class="nav-item" role="none"><a role="tab" aria-selected="false" href="#algorithmtab" class="nav-link nav-link-affnity" data-toggle="tab" id="algorithmtab" onclick="manageMatchingAlgorithmSetting('<?= $_COMPANY->encodeId($groupid);?>')"><?= gettext('Matching Algorithm'); ?></a></li>
  <?php } ?>

  <?php if(in_array($group->getTeamProgramType(), [Team::TEAM_PROGRAM_TYPE['PEER_2_PEER'],Team::TEAM_PROGRAM_TYPE['CIRCLES']])){ ?>
      <li class="nav-item" role="none"><a role="tab" aria-selected="false" href="#searchConfiguration" class="nav-link nav-link-affnity" data-toggle="tab" id="searchConfiguration" onclick="manageSearchConfiguration('<?= $_COMPANY->encodeId($groupid);?>')"><?= gettext('Search Configuration'); ?></a></li>
  <?php } ?>
    </ul>
  </div>
  <div class="col-md-12" id="reportDownLoadOptions"></div>
  <div class="col-md-12">
    <div class="tab-content">
      <div id="dynamic_content" class="tab-pane active col-md-12 mt-3"></div>
    </div>
  </div>

  <script>
    $("document").ready(function() {
        var hash = window.location.hash.substr(1);
        setTimeout(function() {
            if (hash == 'algorithmtab'){
                $("#algorithmtab").trigger('click');
            } else {
                $("#teamSettingTab").trigger('click');
            }
            window.location.hash = "getMyTeams";
        },10);
    });


    updatePageTitle('<?= addslashes(sprintf(gettext('%1$s | %2$s'), $documentTitle, $_COMPANY->val('companyname')));?>');
</script>

