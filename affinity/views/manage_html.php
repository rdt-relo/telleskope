<style>
	.green:before {background-color: <?= $_ZONE->val('show_group_overlay') ? $group->val('overlaycolor') : ''; ?>;}
	.timepicker{ z-index:9999 !important; }
    span.select2-selection.select2-selection--multiple {
        height: auto !important;
        border: 1px solid #ccc !important;
    }
</style>
<main>

<div class="container w2 overlay green"
     style="background:url(<?= $group->val('coverphoto') ?>) no-repeat; background-size:cover;">
    <div class="col-md-12">
        <h1 class="ll">
            <span style="display:<?= ($group->val('show_overlay_logo') == 1 || $group->val('show_overlay_logo') == 3) ? 'block' : 'none' ?>;">
            <?php if ($group->val('groupicon')) { ?>
            <img class="icon-img" alt="<?= sprintf(gettext('%s Network'),$group->val('groupname')); ?>" src="<?= $group->val('groupicon'); ?>">
            <?php }else{ ?>
            <?= $group->val('groupname'); ?>
            <?php } ?>
            </span>
        </h1>
    </div>
    <div class="chapterbar" style="background-color:#66666666">
        <div class="pull-left">
            <p><?= sprintf(gettext('%s Administration Panel'),$group->val('groupname'));?></p>
        </div>
        <div class="pull-right">
            <?php if ($_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) { ?>
                <span class="manage-title">
                <?php
                $showManageButton = true;

                if ($_USER->isCompanyAdmin()) { echo gettext("You are a Global Admin");}
                elseif ($_USER->isZoneAdmin($_ZONE->id())) { echo gettext("You are a Zone Admin");}
                elseif ($_USER->isGrouplead($groupid)) { echo sprintf(gettext("You are a %s Leader"),$_COMPANY->getAppCustomization()['group']['name-short']); }
                elseif ($_USER->isRegionallead($groupid)) { echo gettext("You are a Regional Leader"); }
                elseif ($_USER->isChapterlead($groupid,-1)) { echo sprintf(gettext("You are a %s Leader"),$_COMPANY->getAppCustomization()['chapter']['name-short']); }
                elseif ($_USER->isChannellead($groupid,-1)) { echo sprintf(gettext("You are a %s Leader"),$_COMPANY->getAppCustomization()['channel']['name-short']); }
                else { $showManageButton = false; }
                ?>
                 </span>
            <?php } ?>

            <a tabindex="0" href="javascript:void(0);" onclick="loadNewPageWithSelectedTabState(this)" data-href="detail?id=<?=$_COMPANY->encodeId($group->val('groupid'));?>" class="btn-affinity focus-white-color btn-sm btn">
                <?= sprintf(gettext("Back to %s"),$_COMPANY->getAppCustomization()['group']['name-short']); ?>
            </a>
        </div>
    </div>
</div>

<?php include __DIR__.'/managesidebar_html.php' ?>

<div class="container inner-background" >
    <div class="row row-no-gutters">
        <div class="manage-container">
            <div id="ajax">	            
            </div>
        </div>
    </div>
</div>
<!-- Container div for Datepicker & video tags store all html in it for accessibility -->
<div class="datepicker-and-video-tags-html-container"></div>
</main>

<script>
    var activeTab = localStorage.getItem("manage_active");
    
<?php   if (isset($_GET['survey'])){ ?> // This is the case if survey is sent for review
            localStorage.setItem("manage_active", "getGroupSurveys");
            activeTab = 'getGroupSurveys'; // force to load survey tab.
            var newURL = location.href.split("&")[0]; // remove survey word from url
            window.history.replaceState(null, null, newURL);
<?php   } ?>

    if (activeTab ){
       var seriesId = '';
       if(activeTab.indexOf('manageGlobalEvents-') != -1){
            var tabArray = activeTab.split('-');
           activeTab =tabArray[0];
           seriesId = tabArray[1];
       }

    <?php if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['INDIVIDUAL_DEV']){ ?>
        $("#manageDashboard").trigger("click");
        $("#manageDashboard_li").trigger("click");
    <?php } else { ?>
            $("#"+activeTab).trigger("click");
            $("#"+activeTab+'_li').trigger("click");
            if (seriesId){
                manageSeriesEventGroup('<?= $_COMPANY->encodeId($groupid)?>',seriesId);
            }

        <?php
        if(isset($_GET['subjectEventId'])){
            $decoded_eventid = $_COMPANY->decodeId($_GET['subjectEventId']);
        ?>
           
            let newUrl = removeURLParameter(window.location.href,'subjectEventId');
            window.history.replaceState(null, null,newUrl);
            manageEventSurvey('<?= $_COMPANY->encodeId($decoded_eventid); ?>');
        <?php } ?>

    <?php } ?>
	} else {
       manageGlobalAnnouncements('<?=$_COMPANY->encodeId($groupid)?>');
    }
</script>