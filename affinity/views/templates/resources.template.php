
<?php $documentTitle = $pageTitle.' - '.$group->val('groupname');?>
<?php if($is_resource_lead_content){ ?>
<div class="col-md-12">
    <div class="row">
        <div class="col-10">
            <h2><?= $pageTitle; ?> - <?= $group->val('groupname_short'); ?> <button class="btn-no-style fa fa-info-circle" style="font-size: 14px !important;" data-toggle="tooltip" title="" data-original-title="<?= sprintf(gettext('This page and any content you upload will be made visible only to %s leaders'),$_COMPANY->getAppCustomization()['group']['name-short'])?>"></button></h2>
        </div>
        <div class="col-2 text-right" style="margin-bottom: -16px;">
            <?php
            $page_tags = 'manage_resources,manage_documents';
            ViewHelper::ShowTrainingVideoButton($page_tags);
            ?>
        </div>
    </div>
    <hr class="lineb" >
    <div class="row row-no-gutters">
<?php } else { ?>

<div class="container inner-background">
    <div class="row row-no-gutters w-100">
        <div class="col-md-12">
            <div class="col-md-10 col-xs-12">
                <div class="inner-page-title">
                    <h1><?= $pageTitle; ?> - <?= $group->val('groupname'); ?> </h1><button aria-label="<?= $pageTitle; ?> - <?= $group->val('groupname'); ?>" class="btn-no-style fa fa-info-circle" style="font-size: 14px !important;" data-toggle="tooltip" title="" data-original-title="<?= sprintf(gettext('%1$s page and any content you upload will be made visible to all %2$s members.'), $pageTitle, $_COMPANY->getAppCustomization()['group']['name-short'])?>"></button>
                </div>
            </div>
        </div>
        <hr class="lineb">
<?php } ?>

    <div class="col-md-12" id="resource_data">
        <?php include __DIR__.'/resources_table.template.php' ?>
    </div>
       
    </div>
</div>

<div id="updateResourceModal"></div>
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
        
        function hideToolTip() {
            $('[data-toggle="tooltip"]').tooltip('hide');    
        }

        $(document).keydown(function (e){
            if (e.key === 'Escape') {
                hideToolTip();
            }
        });

        var hoveredElement = null;
        $('[data-toggle="tooltip"]').on('mouseenter', function(){
            hoveredElement = $(this);
        }).on('mouseleave', function(){
            hoveredElement = null;
        });
        
    });
    updatePageTitle('<?= addslashes(sprintf(gettext('%1$s | %2$s'), $documentTitle, $_COMPANY->val('companyname')));?>');
</script>