<div class="">

    <div class="col-md-12 mt-4 min-vh-75">

		<div id="discover_circle_filter_container">
			<?php include(__DIR__ . "/../common/get_discover_or_disover_circle_filter.tamplate.php"); ?>
		</div>

        <div class="row pb-5" id="discover_circle_card_container">
			<div class="text-center pt-5">
				<span class="spinner-border spinner-border-sm mr-1"></span>
				<?= gettext('Almost there! We\'re fetching your data, and it should be ready in a jiffy'); ?>..
			</div>
		</div>

    </div>
        
    <input type="hidden" id='discoverCirclePageNumber' value="2">
	
	<div class="col-md-12 text-center mb-5 mt-3" id="loadeMoreDiscoverCircle" style="display:none;">
		<button aria-label="Load more circles" class="btn btn-affinity"
				onclick="loadMoreDiscoverCircles('<?= $_COMPANY->encodeId($groupid); ?>')">
				<?= gettext('Load more'); ?>...
		</button>
	</div>

</div>

<script>
    $(document).ready(function(){
		var hash = window.location.hash.substr(1);
		if(hash.startsWith('circles/hashtags')){
			var hashtagid = hash.split('/').pop();
			discoverCircles('<?= $_COMPANY->encodeId($groupid); ?>', {
                hashtag_ids: [hashtagid]
            });
		} else {
			discoverCircles('<?= $_COMPANY->encodeId($groupid); ?>');
		}
        $(function () {
			$('[data-toggle="popover"]').popover({html:true, placement: "top",sanitize : false,container: 'body'});  
		})
	});
</script>