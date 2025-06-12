<style>
    div.dt-container.dt-empty-footer .dt-scroll-body{overflow: initial !important;}
</style>
<main>
    <div class="container w2 overlay"
        style="background: url(<?= $_ZONE->val('banner_background') ?: 'img/img.png'?>) no-repeat; background-size:cover; background-position:center;">
        <div class="col-md-12">
            <h1 class="ll icon-pic-custom" >
                <?= $bannerTitle; ?>
            </h1>
        </div>
    </div>
    <?php $tab_activated = false; ?>
    <div class="container inner-background">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="inner-page-title">
                    <ul class="nav nav-tabs" role="tablist">
                        <?php if($eventApprovals){ ?>
                            <li role="none" class="nav-item">
                                <a role="tab" aria-selected="false" data-toggle="tab" class="nav-link inner-page-nav-link <?= !$tab_activated ? 'active' : '' ?>" tabindex="0" href="javascript:void(0)" id="eventapprovalsdata" onclick="getMyTopicApprovalsData('<?=$eventTopicType?>')">
                                    <?= gettext('Event Approvals'); ?>
                                </a>
                            </li>
                    <?php $tab_activated = true; } ?>
                    <?php if($newsletterApprovals){ ?>
                        <li role="none" class="nav-item">
                            <a role="tab" aria-selected="false" data-toggle="tab" class="nav-link inner-page-nav-link <?= !$tab_activated ? 'active' : '' ?>" tabindex="0" href="javascript:void(0)" id="newsletterapprovalsdata" onclick="getMyTopicApprovalsData('<?=$newsletterTopicType?>')">
                                <?= gettext('Newsletter Approvals'); ?>
                            </a>
                        </li>
                     <?php $tab_activated = true; } ?>
                     <?php if($postApprovals){ ?>
                        <li role="none" class="nav-item">
                            <a role="tab" aria-selected="false" data-toggle="tab" class="nav-link inner-page-nav-link <?= !$tab_activated ? 'active' : '' ?>" tabindex="0" href="javascript:void(0)" id="postapprovalsdata" onclick="getMyTopicApprovalsData('<?=$postTopicType?>')">
                                <?= sprintf(gettext('%s Approvals'),Post::GetCustomName(false)); ?>
                            </a>
                        </li>
                     <?php $tab_activated = true; } ?>
                     <?php if($surveyApprovals){ ?>
                        <li role="none" class="nav-item">
                            <a role="tab" aria-selected="false" data-toggle="tab" class="nav-link inner-page-nav-link <?= !$tab_activated ? 'active' : '' ?>" tabindex="0" href="javascript:void(0)" id="surveyapprovalsdata" onclick="getMyTopicApprovalsData('<?=$surveyTopicType?>')">
                                <?= sprintf(gettext('%s Approvals'),Survey2::GetCustomName(false)); ?>
                            </a>
                        </li>
                     <?php $tab_activated = true; } ?>
					</ul>
				</div>
            </div>
            <div class="col-12" id="dynamic_data_container">
            </div>
        </div>
    </div>
</main>
<script>
    $(document).ready(function () {
        let eventTopicType = '<?=$eventTopicType?>';
        let newsletterTopicType = '<?=$newsletterTopicType?>';
        let postTopicType = '<?=$postTopicType?>';
        let surveyTopicType = '<?=$surveyTopicType?>';

        // activate tab here for state maintainence
        let lastApprovalAction = localStorage.getItem('lastApprovalAction');
        let initialTopicType = lastApprovalAction || eventTopicType || newsletterTopicType || postTopicType || surveyTopicType;
        switch (lastApprovalAction) {
            case 'EVT':
                $('#eventapprovalsdata').tab('show')
                break;
            case 'NWS':
                $('#newsletterapprovalsdata').tab('show')
                break;
            case 'POS':
                $('#postapprovalsdata').tab('show')
                break;
            case 'SUR':
                $('#surveyapprovalsdata').tab('show')
                break;    
        }
        getMyTopicApprovalsData(initialTopicType);
        localStorage.setItem('lastApprovalAction', '');
    });
    $(function() {                       
        $(".inner-page-nav-link").click(function() { 
            $('.inner-page-nav-link').attr('tabindex', '-1');
            $(this).attr('tabindex', '0');    
        });
    });
    $('.inner-page-nav-link').keydown(function(e) {  
            if (e.keyCode == 39) {       
                $(this).parent().next().find(".inner-page-nav-link:last").focus();       
            }else if(e.keyCode == 37){       
                $(this).parent().prev().find(".inner-page-nav-link:last").focus();  
            }
    });
    updatePageTitle('<?= addslashes($pageTitle); ?>');
</script>