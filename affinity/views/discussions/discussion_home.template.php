<style>
    .innar-page-right-button {
    padding-top: 17px;
}
.discussion-heading{
    float:left;
}
</style>
<!-- Start of Discussions section Section -->
<div class="container inner-background">
    <div class="row row-no-gutters w-100">
        <div class="col-md-12">
            <div class="col-md-12">
                <div class="inner-page-title">
                <h1>
                    <span><?php echo $documentTitle = gettext('Discussions') . ' - ' . $group->val('groupname'); ?></span>
                    <?php
                    if (!empty($discussionSettings) && ($discussionSettings['who_can_post'] == 'members' && $_USER->isGroupMember($groupid)) || $_USER->isGroupleadOrGroupChapterleadOrGroupChannellead($groupid)) {
                        $encGroupId = $_COMPANY->encodeId($groupid);
                        $encoded0 = $_COMPANY->encodeId(0);
                        if (Disclaimer::IsDisclaimerAvailable(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['DISCUSSION_CREATE_BEFORE'])) {
                            $callOtherMethod = base64_url_encode(json_encode(array("method" => "openCreateDiscussionModal", "parameters" => array($encGroupId, $encoded0)))); // base64_encode for prevent js parsing error
                            $hookid = $_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['DISCUSSION_CREATE_BEFORE']);
                            $on_create_function = "loadDisclaimerByHook('{$hookid}', '{$encGroupId}', '0', '{$callOtherMethod}')";
                        } else {
                            $on_create_function = "openCreateDiscussionModal('{$encGroupId}', '{$encoded0}')";
                        }

                        ?>
                        <a tabindex="0" aria-label="<?= gettext('Create Discussion') ?>" href="javascript:void(0);"
                           role="button" onclick="<?= $on_create_function ?>">
                            <i class="fa fa-plus-circle link-pointer" title="<?= gettext('Create new discussion') ?>"
                               aria-hidden="true" style="font-size: 1.5rem;"></i>
                        </a>
                    <?php } ?>
                    </h1>
                </div>
            </div>
        </div>
        <hr class="lineb" >
        <div class="col-md-12" id="loadeMoreDiscussionRows">
        <?php
       
            include(__DIR__ . "/discussion_rows.template.php");
          
        ?>
        </div>
        <input type="hidden" id='pageNumber' value="2">
        <div class="col-md-12 text-center mb-5 mt-3" id="loadeMoreDiscussioinAction" style="<?= $show_more ? '' : 'display:none;'; ?>">
            <button class="btn btn-affinity"
                    onclick="loadMoreDiscussion('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>')">
                    <?= gettext('Load more'); ?>...
            </button>
        </div>
    </div>
</div>

<script>
    $('[data-toggle="tooltip"]').tooltip(); 
    updatePageTitle('<?= addslashes(sprintf(gettext('%1$s | %2$s'), $documentTitle, $_COMPANY->val('companyname')));?>');
    
</script>

<!-- End of Discussions section Section -->
