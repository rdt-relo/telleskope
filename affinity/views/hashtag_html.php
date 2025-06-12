<style>
	.green:before {}
	.timepicker{ z-index:9999 !important; }
</style>

<main>
<div class="container w2 overlay green"
     style="background:url(<?= $_ZONE->val('banner_background') ? $_ZONE->val('banner_background') : 'img/img.png' ?>) no-repeat; background-size:cover;background-position: center center;">
		<div class="col-md-12">
			<h1 class="ll">
				<span>
					#<?= $handle ? $handle : 'tag';?>
				</span>
			</h1>
		</div>
	</div>
<div class="container inner-background">
	<div class="row row-no-gutters">
        <?php 	if(count($feeds)>0){ ?>
            <p class="impact2"><span><?= sprintf(gettext("%s, Events and Discussion related to %s"),Post::GetCustomName(true), '#'.$hashtag['handle']); ?></span></p>
            <?php require __DIR__ . '/hashtag_rows.html.php' ?>
        <?php 	}else{ ?>
                <div class="container w6">
                    <div class="col-md-12">
                        <p style="text-align:center;"><img height="200px" src="../image/nodata/no-group.png" alt="" ></p>	
                        <p style="text-align:center;color:gray;"><?= gettext("Looks like there aren't any announcements, events and discussions related to this #tag")?></p>
                        <hr>				
                    </div>
                </div>
        <?php	} ?>
            </div>
</div>
</main>

<?php if($error){ ?>
    <script>
        swal.fire({title: 'Error', text: '<?= $error; ?>'})
    </script>
<?php }  ?>
