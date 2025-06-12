<style>
    div.dt-container.dt-empty-footer .dt-scroll-body{overflow: initial !important;}
</style>
<main>
    <div class="container w2 overlay"
        style="background: url(<?= $_ZONE->val('banner_background') ?: 'img/img.png'?>) no-repeat; background-size:cover; background-position:center;">
        <div class="col-md-12">
            <h1 class="ll icon-pic-custom" >
                <?= $bannerTitle; ?>
            </h1>
        </div>
    </div>
   
    <div class="container inner-background">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="inner-page-title">

				</div>
            </div>
            <div class="col-12">
            <?php
				include(__DIR__ . "/bookings/manage_booking_table_listing.template.php");
			?>
            </div>
        </div>
    </div>
</main>
