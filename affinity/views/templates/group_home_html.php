<?php
    $firstBlockPosts = 0;
//include(__DIR__ . "/group_home_upcoming_event.template.php"); @todo remove this upcoming event feature
?>

<!-- Start of Announcements section Section -->
<div class="container inner-background">
    <div class="row row-no-gutters w-100">
        <div class="col-md-12">
            <div class="col-md-10 col-xs-12">
                <div class="inner-page-title">
                    <h1><?php echo $documentTitle = Post::GetCustomName(true).' - '. $group->val('groupname');?></h1>

                    
                </div>
            </div>
        
            <div class="col-md-2 col-xs-12">&nbsp;
                <?php /*
                    $page_tags = 'tag1,tag2,announcement';
                    iewHelper::ShowTrainingVideoButton($page_tags);
                     */
                ?>
            </div>
       
        
        </div>
        <hr class="lineb" >

        <div class="col-md-12" id="loadeMorePostsRows">
        <?php
            include(__DIR__ . "/group_posts_rows.template.php");
        ?>
        <input type="hidden" id='pageNumber' value="2">
        </div>
        <div class="col-md-12 text-center mb-5 mt-3" id="loadeMorePostsAction" style="<?= $show_more ? '' : 'display:none;'; ?>">
            <button class="btn btn-affinity"
                    onclick="loadMorePosts('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>')">
                    <?= gettext('Load more'); ?>...
            </button>
        </div>
    </div>
</div>

<script>
    $('[data-toggle="tooltip"]').tooltip();
    updatePageTitle('<?= addslashes(sprintf(gettext('%1$s - %2$s | %3$s'), Post::GetCustomName(true), $group->val('groupname'), $_COMPANY->val('companyname')));?>');
</script>

<!-- End of Announcements section Section -->

<?php
// If there is announcement that we need to show... show it now.
if (!empty($_SESSION['show_announcement_id'])) {
    $enc_nid = $_COMPANY->encodeId($_SESSION['show_announcement_id']);
    unset($_SESSION['show_announcement_id']);
    ?>
    <script>
        $(document).ready(function () {
            getAnnouncementDetailOnModal('<?= $enc_nid?>', '<?= $_COMPANY->encodeId(0)?>', '<?= $_COMPANY->encodeId(0)?>');
        });
    </script>
<?php } ?>
