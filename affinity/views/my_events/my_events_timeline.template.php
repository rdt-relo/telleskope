<style>
    .mn{
    font-size: 13px;
    height: auto;
    padding: 4px;}
</style>
<div class="tab-pane active">
    <?php if (!empty ($data)){ ?>
        <div id="loadMoreEvents<?= $section; ?>">
            <?php include(__DIR__ . "/../templates/get_events_timeline_rows.template.php"); ?>
            <input type="hidden" id='lastMonth' value="<?= $month ?>">
            <input type="hidden" id='pageNumber' value="2">
        </div>
        <div class="col-md-12 text-center mb-5 mt-3" id="loadmore<?= $section; ?>" style="<?= $show_more ? '' : 'display:none;'; ?>">
            <?php if($section == Event::MY_EVENT_SECTION['DISCOVER_EVENTS']){ 
                $encodedEventTypeArray = $_COMPANY->encodeIdsInArray($eventTypeArray);?>
                    <button class="btn btn-affinity"
                    onclick="loadMoreMyEventsByZone('<?= $_COMPANY->encodeId($zoneid) ?>','<?= htmlspecialchars(json_encode($encodedEventTypeArray), ENT_QUOTES) ?>','<?= $section ?>')">
                    <?= gettext('Load more'); ?>...
            </button>
                <?php }else { ?>
                    <button class="btn btn-affinity"
                    onclick="loadMoreMyEventsBySection('<?= $section; ?>')">
                    <?= gettext('Load more'); ?>...
                    </button>
                <?php } ?>

            
        </div>

    <?php } ?>

    <?php if($page = 1 && empty ($data)) { ?>
    <div class="container w6">
        <div class="col-md-12 bottom-sp">
            <br/>           
            <p style="text-align:center;margin-top:-40px;"><?= $noDataMessage ?? gettext("There are no events to display at this time. Please try again later."); ?></p>
            <p style="text-align:center;margin-top:-40px"><img src="../image/nodata/calendar.png" alt="No events to display placeholder image" height="200px;"/></p>
        </div>
    </div>

    <?php } ?>
</div>