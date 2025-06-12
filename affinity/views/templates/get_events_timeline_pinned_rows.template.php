<style>
    .linec_light {
        background-color: #54BBE7;
        min-height: 2px;
        width: 100%;
    }
    .pinned-event{
        background: rgb(246, 246, 246);
        padding-top: 15px;
        padding-bottom: 15px;
    }
</style>
<?php if (!empty ($pinnedEvents)){  ?>
    <div class="pinned-event">
        <?php
        $month = "";
        foreach ($pinnedEvents as $evt) {
            $month = $evt['month'];
        ?>
            <div class="event-block-container">
                <?php include(__DIR__ . "/get_events_timeline_event_display.template.php"); ?>
            </div>
        <?php	} ?>
    </div>
<?php } ?>