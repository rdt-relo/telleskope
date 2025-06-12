<style>
    .allreports p {
        font-size: 17px;
        margin-bottom: 15px !important;
    }

    .allreports i {
        font-size: 19px;
        margin-left: 4px;
        color: #646569;;
    }

    .box-wrap {
        flex-wrap: wrap;
        display: flex;
    }

    .allreports .report-list-box {    
        list-style: none;    
        padding-left: 4px;
    }
    .allreports h3{
        font-size: 1.25rem;
    }
</style>
<div class="container  col-md-offset-2 margin-top">
    <div class="row">
        <div class="col-12">
            <h2><?=gettext('Reports') .' - '. $group->val('groupname_short');?></h2>
        </div>
    </div>
    <hr class="lineb">
    <div class="row">
        <div class="col-md-12">
            <div class="widget-simple-chart card-box allreports">

                <div class="col-12 box-wrap">
                <div class="col-md-6 col-sm-12 mb-3">
                    <h3 class="mb-3">
                        <?=gettext('Users Reports')?>
                    </h3>                
                    <ul class="report-list-box">                       
                        <li class="m-1 p-0">
                            <a href="#" onclick="getUserReports('<?= $enc_groupid; ?>','members report')">
                                - <?= sprintf(gettext('%s Reports'),$_COMPANY->getAppCustomization()['group']['memberlabel']);?>
                            </a>
                        </li>

                        <?php if ($_USER->canManageGroup($groupid)) { ?>
                            <li class="m-1 p-0">
                                <a href="#" onclick="getUserReports('<?= $enc_groupid; ?>','erg Lead report')">
                                    - <?= sprintf(gettext("%s Leader Reports"), $_COMPANY->getAppCustomization()['group']['name-short']); ?>
                                </a>
                            </li>
                        <?php } ?>
                        

                        <?php for ($i = 0; $i < count($chapters); $i++) {
                            $validated = false; ?>
                            <?php if ($_USER->canManageGroupChapter($groupid, $chapters[$i]['regionids'], $chapters[$i]['chapterid'])) {
                                $validated = true; ?>
                                <li class="m-1 p-0">
                                    <a href="#" onclick="getUserReports('<?= $enc_groupid; ?>','chapter lead report')">
                                       - <?= sprintf(gettext("%s Leader Reports"), $_COMPANY->getAppCustomization()['chapter']['name-short']); ?>
                                    </a>
                                </li>
                            <?php
                                break;
                            } ?>
                            <?php if (!$validated) {
                                continue;
                            } ?>
                        <?php } ?>

                        <?php for ($i = 0; $i < count($channels); $i++) {
                            $validated = false; ?>

                            <?php if ($_USER->canManageGroupChannel($groupid, $channels[$i]['channelid'])) {
                                $validated = true; ?>
                                <li class="m-1 p-0">
                                    <a href="#" onclick="getUserReports('<?= $enc_groupid; ?>','channel lead report')">
                                        - <?= sprintf(gettext("%s Leader Reports"), $_COMPANY->getAppCustomization()['channel']['name-short']); ?>
                                    </a>
                                </li>

                            <?php
                                break;
                            } ?>
                            <?php if (!$validated) {
                                continue;
                            } ?>
                        <?php } ?>
                        <?php if($_USER->canManageGroup($groupid) && $group->val("group_type") == Group::GROUP_TYPE_REQUEST_TO_JOIN) {?>
                            <li class="m-1 p-0">
                                <a href="#" onclick="getGroupJoinRequestReportOptions('<?= $enc_groupid; ?>')">
                                    - <?= sprintf(gettext('%s Join Requests'), $_COMPANY->getAppCustomization()['group']['name-short']); ?>
                                </a>
                            </li>
                            <?php } ?>
                        </ul>
                </div>

                    <?php if ($_COMPANY->getAppCustomization()['event']['enabled']) { ?>
                        <div class="col-md-6 col-sm-12 mb-3">
                            <h3 class="mb-3"><?=gettext('Events Reports')?></h3>
                            <ul class="report-list-box"> 
                            <li class="m-1 p-0">
                                <a href="#" onclick="getEventsReportModal('<?= $enc_groupid; ?>','event_list')">
                                    - <?= gettext('Event List Report') ?>
                                </a>
                            </li>
                            <li class="m-1 p-0">
                                <a href="#" onclick="getEventsReportModal('<?= $enc_groupid; ?>','event_rsvp')">
                                    - <?= gettext('Event RSVP Report') ?>
                                </a>
                            </li>
                            <?php if($_USER->canManageGroup($groupid)) {?>
                            <li class="m-1 p-0">
                                <a href="ajax_reports.php?download_event_speaker_report=<?= $enc_groupid; ?>">
                                    - <?= gettext('Event Speaker Report') ?>
                                </a>
                            </li>
                            <?php } ?>
                            <?php if ($_COMPANY->getAppCustomization()['event']['volunteers']) { ?>
                            <li class="m-1 p-0">
                                <a href="#" onclick="getEventsReportModal('<?= $enc_groupid; ?>','event_volunteer')">
                                    - <?= gettext('Event Volunteers Report') ?>
                                </a>
                            </li>
                            <?php } ?>
                            <?php if($_COMPANY->getAppCustomization()['event']['partner_organizations']['enabled']) {?>
                            <li class="m-1 p-0">
                                <a href="ajax_reports.php?download_event_organization_report=<?= $enc_groupid; ?>">
                                    - <?= gettext('Event Organization Report') ?>
                                </a>
                            </li>
                            <?php } ?>
                            </ul>
                        </div>
                    <?php } ?>
                    <?php if ($_COMPANY->getAppCustomization()['budgets']['enabled'] && $_USER->canManageBudgetGroupSomething($groupid)) { ?>
                        <div class="col-md-6 col-sm-12 mb-3">
                            <h3 class="mb-3"><?=gettext('Budget Reports')?></h3>
                            <ul class="report-list-box">
                            <li class="m-1 p-0">
                                <a href="#" onclick="getBudgetReports('<?= $enc_groupid; ?>','budget')">
                                    - <?= gettext('Budget Summary By Year') ?>
                                </a>
                    </li>
                            <li class="m-1 p-0">
                                <a href="#" onclick="getBudgetReports('<?= $enc_groupid; ?>','expense')">
                                    - <?= gettext('Expense/Spend Report By Year') ?>
                                </a>
                    </li>
                    </ul>
                        </div>
                    <?php } ?>

                    <?php if ($_COMPANY->getAppCustomization()['recognition']['enabled']) { ?>
                        <?php if($_USER->canManageGroup($groupid)) {?>
                        <div class="col-md-6 col-sm-12 mb-3">
                            <h3 class="mb-3"><?=gettext('Recognition Reports')?></h3>
                            <ul class="report-list-box">
                            <li class="m-1 p-0">
                                <a href="#" onclick="getRecognitionReports('<?= $enc_groupid; ?>')">
                                    - <?= gettext('Download Recognition Report') ?>
                                </a>
                            </li>
                        </ul>
                        </div>
                        <?php } ?>
                    <?php } ?>

                    <?php if ($_COMPANY->getAppCustomization()['newsletters']['enabled']) { ?>
                        <div class="col-md-6 col-sm-12 mb-3">
                            <h3 class="mb-3"><?=gettext('Newsletters Reports')?></h3>
                            <ul class="report-list-box">
                            <li class="m-1 p-0">
                                <a href="#" onclick="getNewslettersReport('<?= $enc_groupid; ?>')">
                                    - <?= gettext('Download Newsletters Report') ?>
                                </a>
                            </li>
                            </ul>
                        </div>
                    <?php } ?>

                    <?php if ($_COMPANY->getAppCustomization()['post']['enabled']) { ?>
                        <div class="col-md-6 col-sm-12 mb-3">
                            <h3 class="mb-3"><?= sprintf(gettext("Download %s Report"),POST::GetCustomName(true));?></h3>
                            <ul class="report-list-box">
                            <li class="m-1 p-0">
                                <a href="#" onclick="getAnnouncementsReport('<?= $enc_groupid; ?>')">
                                    - <?= sprintf(gettext("Download %s Report"),POST::GetCustomName(true)); ?>
                                </a>
                            </li>
                            </ul>
                        </div>
                    <?php } ?>

                    <?php if ($_COMPANY->getAppCustomization()['messaging']['enabled']) { ?>
                        <div class="col-md-6 col-sm-12 mb-3">
                            <h3 class="mb-3"><?=gettext('Messages Reports')?></h3>
                            <ul class="report-list-box">
                            <li class="m-1 p-0">
                                <a href="ajax_reports.php?download_direct_mail_report=<?= $enc_groupid; ?>">
                                    - <?= gettext('Download Direct Mail Report') ?>
                                </a>
                            </li>
                            </ul>
                        </div>
                    <?php } ?>

                    <?php if ($group->isTeamsModuleEnabled() && $_USER->canManageGroup($groupid)) { ?>
                        <div class="col-md-6 col-sm-12 mb-3">
                            <h3 class="mb-3"><?=sprintf(gettext('%s Reports'), Team::GetTeamCustomMetaName($group->getTeamProgramType()))?></h3>
                            <ul class="report-list-box">
                            <li class="m-1 p-0">
                                <a href="#" onclick="getTeamsReportOptions('<?= $enc_groupid; ?>')">
                                    - <?= sprintf(gettext('Download %s Report'), Team::GetTeamCustomMetaName($group->getTeamProgramType())) ?>
                                </a>
                            </li>
                            <li class="m-1 p-0">
                                <a href="#" onclick="getTeamMemberReportOptions('<?= $enc_groupid; ?>')">
                                    - <?= sprintf(gettext('Download %s Members Report'), Team::GetTeamCustomMetaName($group->getTeamProgramType())) ?>
                                </a>
                            </li>
                            <li class="m-1 p-0">
                                <a href="#" onclick="getTeamsJoinRequestSurveyReportOptions('<?= $enc_groupid; ?>',1)">
                                    - <?= gettext('Download Registration Report') ?>
                                </a>
                            </li>
                            <?php if(($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER'])  ){?>
                                <li class="m-1 p-0">
                                    <a href="ajax_talentpeak?downloadTeamsRequestReport=<?= $_COMPANY->encodeId($groupid); ?>">
                                        - <?= sprintf(gettext('Download %s Requests Report'), Team::GetTeamCustomMetaName($group->getTeamProgramType())) ?>
                                    </a>
                            </li>
                            <?php } ?>

                            <?php if(!in_array(TEAM::PROGRAM_TEAM_TAB['FEEDBACK'], $group->getHiddenProgramTabSetting())){ ?>
                                <li class="m-1 p-0">
                                  <a href="#" onclick="getTeamsFeedbackReportOptions('<?= $enc_groupid; ?>',1)">
                                    - <?= sprintf(gettext('Download %s Feedback Report'),Team::GetTeamCustomMetaName($group->getTeamProgramType())) ?>
                                  </a>
                            </li>
                             <?php } ?>
                            </ul>
                        </div>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>
</div>