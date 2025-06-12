<div class="tab-pane active">

    <?php
    $url_chapter_channel_suffix = '';
    if ($chapterid > 0) {
        $url_chapter_channel_suffix .= '&chapterid=' . $_COMPANY->encodeId($chapterid);
    }

    if ($channelid > 0) {
        $url_chapter_channel_suffix .= '&channelid=' . $_COMPANY->encodeId($channelid);
    }
    ?>

    <?php if(!empty($pinnedEvents)){ ?>
        <?php include(__DIR__ . "/get_events_timeline_pinned_rows.template.php"); ?>
    <?php } ?>
    <?php if (!empty ($data)){ ?>
        <div id="loadMoreEvents<?= $type; ?>">
            <?php include(__DIR__ . "/get_events_timeline_rows.template.php"); ?>
            <input type="hidden" id='lastMonth' value="<?= $month ?>">
            <input type="hidden" id='pageNumber' value="2">
        </div>
        <div class="col-md-12 text-center mb-5 mt-3" id="loadmore<?= $type; ?>" style="<?= $show_more ? '' : 'display:none;'; ?>">
            <button class="btn btn-affinity"
                    onclick="loadMoreEvents('<?= $_COMPANY->encodeId($groupid) ?>','<?= $_COMPANY->encodeId($chapterid) ?>','<?= $_COMPANY->encodeId($channelid) ?>',<?= $type; ?>,'<?= $section; ?>')">
                    <?= gettext('Load more'); ?>...
            </button>
        </div>

    <?php } ?>

    <?php if($page = 1 && empty($pinnedEvents) && empty ($data)) { ?>
    <div class="container w6">
        <div class="col-md-12 bottom-sp" >
            <br/>           
            <p style="text-align:center;margin-top:-40px;"><?= $filterMessage = $noDataMessage ?? gettext("There are no events to display at this time. Please try again later."); ?></p>
            <p style="text-align:center;margin-top:-40px"><img src="../image/nodata/calendar.png" alt="<?=gettext("No events to display placeholder image.")?>" height="200px;"/></p>

            <script>     
                document.querySelector("#filterBtn").addEventListener("click", function() {                
                    $("#filterNotification").html('<?= addslashes($filterMessage);?>');                    
                }); 
            </script>
        </div>
    </div>

    <?php } ?>
</div>