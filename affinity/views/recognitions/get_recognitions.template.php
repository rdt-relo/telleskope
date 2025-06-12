<style>
    .lineb {
    background-color: #0077b5;
    min-height: 2px;
    width: 100%;
    border-top-width: 1px;
    border-bottom-width: 1px;
    border-left-width: 1px;
}
</style>
<!-- Start of Recognitions section Section -->
<div class="container inner-background">
    <div class="row row-no-gutters w-100">
        <div class="col-md-12">
            <div class="col-12">
                <div class="inner-page-title">
                    <h1><?php echo $documentTitle = $sectionTitle .' - '. $group->val('groupname'); ?> </h1>
                </div>
            </div>
            
            <div class="col-12 d-flex my-3 px-0" style="justify-content:center;">
                <?php if ($group->getRecognitionConfiguration()['enable_colleague_recognition']) { ?>
                <button type="button" class="btn btn-info btn-affinity mx-2" onclick="openNewRecognitioinModal('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId($recognize_a_colleague) ?>')"><?= gettext("Recognize a Colleague")?></button>
                <?php } ?>

                <?php if ($group->getRecognitionConfiguration()['enable_self_recognition']) { ?>
                <button type="button" class="btn btn-info btn-affinity mx-2" onclick="openNewRecognitioinModal('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId(0) ?>','<?= $_COMPANY->encodeId($recognize_my_self) ?>')"><?= gettext("Recognize My Self")?></button>
                <?php } ?>

            </div>
        </div>
        <hr class="lineb">

        <div class="col-md-12">
            <div class="inner-page-title">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="none" id="li_<?= $_COMPANY->encodeId(0) ?>">
                        <a aria-selected="true" role="tab" id="<?= $_COMPANY->encodeId(0) ?>" class="nav-link inner-page-nav-link active recognition-listing-tab" href="javascript:void(0);" onclick="getRecognitions('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId(0) ?>',1)" data-toggle="tab" ><?= gettext('Recognition Feeds'); ?></a>
                    </li>
                    <li class="nav-item" role="none" id="li_<?= $_COMPANY->encodeId($recognize_a_colleague) ?>">
                        <a role="tab" aria-selected="false" data-toggle="tab" id="<?= $_COMPANY->encodeId($recognize_a_colleague) ?>" class="nav-link inner-page-nav-link recognition-listing-tab" href="javascript:void(0);" onclick="getRecognitions('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($recognize_a_colleague) ?>')"><?= gettext('Recognitions Given'); ?></a>
                    </li>
                    <li class="nav-item" role="none" id="li_<?= $_COMPANY->encodeId($received_recognitions) ?>">
                        <a role="tab" aria-selected="false" id="<?= $_COMPANY->encodeId($received_recognitions) ?>" class="nav-link inner-page-nav-link recognition-listing-tab" href="javascript:void(0);" onclick="getRecognitions('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($received_recognitions) ?>')" data-toggle="tab" ><?= gettext('Recognitions Received') ?></a>
                    </li>
                </ul>
            </div>
       
        </div>
        <input type="hidden" id='pageNumber' value="2">
        <div class="col-md-12 tab-content" id="loadeMoreRecognitionRows">
        <?php
       
            include(__DIR__ . "/recognition_rows.template.php");
          
        ?>

       
        </div>
        <div class="col-md-12 text-center mb-5 mt-3" id="loadeMoreRecognitionAction" style="<?= $show_more ? '' : 'display:none;'; ?>">
            <button class="btn btn-affinity"
                    onclick="loadMoreRecognition('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($recognition_type) ?>')">
                    <?= gettext('Load more'); ?>...
            </button>
        </div>
    </div>
</div>

<script>
    $('[data-toggle="tooltip"]').tooltip();
    updatePageTitle('<?= addslashes(sprintf(gettext('%1$s | %2$s'), $documentTitle, $_COMPANY->val('companyname')));?>');
</script>

<!-- End of Recognitions section Section -->
