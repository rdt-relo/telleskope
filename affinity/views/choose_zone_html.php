<style>
 
    #zoneTile .container {
        position: relative;
        text-align: center;
        color: white;
        padding:0px;
    }
    #zoneTile .centered {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);	
}

    .zone-tile {
        display: block;
    }
    .container-height {
        height: 188px;
    }
    .container-height-compact {
        height: 175px;
    }
    .zone-tile-heading {
        font-size: 2rem;
    }
    .zone-tile-heading-compact {
        font-size: 1.3rem;
    }

    .centered-compact{
        display: flex;
        align-items: flex-start; 
        justify-content: center; 
        height: 175px; 
        flex-direction: column;
    }
    .zone-tile-sub-heading {
        font-size: 1rem;
    }
    .zone-tile-compact {
        margin: 1rem auto;
    }
    @media (max-width: 1000px)  { /* scale down under 1000 px view port */
        .container-height {
            height: calc(100vw * 188/1000);
        }
        .zone-tile-heading {
            font-size: calc(100vw * 24/1000);
        }
        .zone-tile-sub-heading {
            font-size: calc(100vw * 20/1000);
        }
        .zone-tile-compact {
            margin: 0.5rem;
        }
    }
    @media (max-width: 560px)  {
        .zone-tile-compact {
            margin: 0.5rem auto;
        }
    }
</style>
<main>
    <!-- banner -->
    <div class="as row-no-gutters" style="background:url(<?= $_COMPANY->val('zone_banner_image') ?: 'img/img.png'; ?>) no-repeat; background-size:cover; background-position:center;">
        <h1 class="h-sp" style="padding-top:56px;"><?= $_COMPANY->val('zone_heading'); ?></h1>
        <p class="pt-1"><?= $_COMPANY->val('zone_sub_heading'); ?></p>
    </div>

    <!-- manage profile -->
    <div id="main_section" class="container inner-background p-0">
        <div class="row row-no-gutters">
            <div class="col-12 mt-4"  id="zoneTile">
        <?php if ( $_COMPANY->val('zone_selector_page_layout') == 'compact_tiles'){ 
            $zoneObjArray = array();
            foreach($zoneids as $zoneid){
                $zone = $_COMPANY->getZone($zoneid);  
                if($zone->val('app_type') != $_SESSION['app_type']){
                    //continue;
                }
                $zoneObjArray[] = $zone;
            }
            $zoneChunks = array_chunk($zoneObjArray, 3); // split into 3 grid            
        ?>
        <div class="container p-4">
        <?php foreach ($zoneChunks as $chunk) { ?>
            <div class="row justify-content-center">
                <?php foreach ($chunk as $zone) { ?>
                    <?php
                    $zone_url = Url::GetZoneAwareUrlBase($zone->id()) . 'home';

                    # The following line was added to support Fedex under construction tiles, remove
                    #if ($_COMPANY->id() === 3140 && $zone->id() != 3230) $zone_url="#";
                    ?>
                <div class="col-xs-12 col-sm-4 p-0 m-0" >
                    <a class="zone-tile zone-tile-compact" href="<?= $zone_url ?>" style="max-width:300px; max-height:175px; background:  url(<?= $zone->val('customization')['style']['zone_tile_compact_bg_image'] ?: './img/img-blue.png';?>) no-repeat;  background-size:cover; box-shadow: 0 4px 8px rgba(0.5, 0.5, 0.5, 0.5);">
                        <div role="navigation" aria-label="<?= htmlspecialchars($zone->val('zonename'))?> <?= gettext(' Zone');?>" class="container container-height-compact p-0" >
                            <div class="centered-compact pl-2">                           
                                <p aria-hidden="true" class="card-text zone-tile-heading-compact"><?= htmlspecialchars($zone->val('customization')['style']['zone_tile_heading']);?></p>
                                <p aria-hidden="true" class="card-text zone-tile-sub-heading"><?= htmlspecialchars($zone->val('customization')['style']['zone_tile_sub_heading']); ?></p>
                            </div>
                        </div>
                    </a> 
                </div>
            <?php } ?>
            </div>
        <?php } ?>
        </div>

        <?php } else { ?>
        
            <?php foreach($zoneids as $zoneid){
                $zone = $_COMPANY->getZone($zoneid);  
               
                if($zone->val('app_type') != $_SESSION['app_type']){
                    //continue;
                }
                ?>
                <a class="zone-tile mb-3" href="<?= Url::GetZoneAwareUrlBase($zone->id()) . 'home' ?>">
                    <div role="navigation" aria-label="<?= htmlspecialchars($zone->val('zonename'))?> <?= gettext(' Zone');?>" class="container container-height p-0" style="background:  url(<?= $zone->val('customization')['style']['zone_tile_bg_image'] ?: './img/img-blue.png';?>) no-repeat; background-position:center; background-size:100% auto;">
                        <div class="centered">                           
                            <p aria-hidden="true" class="card-text zone-tile-heading"><?= htmlspecialchars($zone->val('customization')['style']['zone_tile_heading']);?></p>
                            <p aria-hidden="true" class="card-text zone-tile-sub-heading"><?= htmlspecialchars($zone->val('customization')['style']['zone_tile_sub_heading']); ?></p>
                        </div>
                    </div>
                </a>                             
                       
        <?php } ?>
    <?php } ?>

            </div>
        </div>
    </div>



</main>

