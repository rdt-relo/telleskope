<?php
/** 
 * Note : Any changes in this file related to php side data veriable 
 * needs changes at two places:
 * 1. ajax_events.php - on getEventsTimeline condition
 * 2. ajax_my_events.php - on getMyEventsDataBySection condition
 *  
 * */
?>

<?php if (!empty ($data)){
    
     $url_chapter_channel_suffix = '';
     if ($chapterid > 0) {
         $url_chapter_channel_suffix .= '&chapterid=' . $_COMPANY->encodeId($chapterid);
     }
 
     if ($channelid > 0) {
         $url_chapter_channel_suffix .= '&channelid=' . $_COMPANY->encodeId($channelid);
     }
                                                                                                                                                                          
    $month = "";
    for($i=0;$i<$max_iter;$i++){
        $evt = $data[$i];
        $month = $evt['month'];
    ?>
        <div class="event-block-container">
            <?php if($i==0){ ?>
            <?php   if ($lastMonth !=$evt['month']){ ?>
            <div class="row">
                <div class="col-md-12">
                    <p class="heading"><strong><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['start'], false, false, false, 'F Y') ?></strong></p>
                    <hr class="linec">
                </div>
            </div>
            <?php   } ?>
            <?php }else{ ?>
            <?php   if($data[($i-1)]['month']!=$evt['month']){ ?>
            <div class="row">
                <div class="col-md-12">
                    <p class="heading"><strong><?= $_USER->formatUTCDatetimeForDisplayInLocalTimezone($evt['start'], false, false, false, 'F Y') ?></strong></p>
                    <hr class="linec">
                </div>
            </div>
            <?php   } ?>
            <?php } ?>

            <?php include(__DIR__ . "/get_events_timeline_event_display.template.php"); ?>
        </div>
    <?php	} ?>
    <!-- end of for loop; add new last month -->

    <?php if (!empty($month) && !empty($lastMonth) && $show_more) { // End fragment with span month element to pass next month ?>
            <span style="display: none">_l_=<?=$month?></span>
    <?php } ?>

<?php } else{
        echo 1;
    } ?>