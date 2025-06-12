<div class="col-md-12 mt-4 min-vh-75">
    
    <?php include(__DIR__ . "/../common/get_discover_or_disover_circle_filter.tamplate.php"); ?>

    <div id="discover_matches">
        <div class="text-center pt-5">
            <span class="spinner-border spinner-border-sm mr-1"></span>
            <?= gettext('Almost there! We\'re fetching your data, and it should be ready soon'); ?> ...
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        discoverTeamMembers('<?= $_COMPANY->encodeId($groupid); ?>')
    });
</script>
