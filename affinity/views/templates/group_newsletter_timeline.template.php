<div class="container inner-background">
    <div class="row row-no-gutters w-100">
		<div class="col-md-12">
            <div class="col-md-12 col-xs-12">
                <div class="inner-page-title">
                    <h1><?php echo $documentTitle = gettext("Newsletters").' - '. $group->val('groupname'); ?></h1>
                </div>
			</div>
        </div><hr class="lineb" >
        <div class=" col-md-12 js-newsletter-listing">
		    <?php if(count($data)>0){ ?>
				<?php include __DIR__ . '/group_newsletter_timeline_rows.template.php'; ?>
				<?php } else{ ?>

				<div class="container w6">
					<div class="col-md-12 bottom-sp mt-5">
						<br/>
						<p style="text-align:center;margin-top:-40px">Whoops!</p>
						<p style="text-align:center;margin-top:0px"><img src="../image/nodata/no-newsletter.png" alt="No newsletter placeholder image" height="100px;" /></p>
                        <br>
						<p style="text-align:center;margin-top:-40px;color:#767676;"><?= gettext("Stay tuned for Newsletters to be posted")?></p>
					</div>
				</div>
				<?php } ?>
			</div>
      <?php if ($page === 1 && count($data) >= $per_page) { ?>
          <div class="col-md-12 text-center mb-5 mt-3">
            <button
              class="btn btn-affinity js-load-more-btn"
              onclick="getGroupChaptersNewsletters('<?= $enc_groupid ?>', '<?= $enc_chapterid ?>', '<?= $enc_channelid ?>', '', $(event.target).data('page-number') + 1)"
              data-page-number="1"
              data-per-page="<?= $per_page ?>"
            >
              <?= gettext('Load more') ?>...
            </button>
          </div>
        <?php } ?>
	</div>
</div>
<?php
if (!empty($_SESSION['show_newsletter_id'])) {
    $enc_nid = $_COMPANY->encodeId($_SESSION['show_newsletter_id']);
    unset($_SESSION['show_newsletter_id']);
    ?>
    <script>
        $(document).ready(function () {
            previewNewsletter('<?= $enc_groupid?>', '<?= $enc_nid?>');
        });
    </script>
<?php } ?>
<script>
    updatePageTitle('<?= addslashes(sprintf(gettext('%1$s | %2$s'), $documentTitle, $_COMPANY->val('companyname')));?>');

  $('.js-load-more-btn').click(function() {
      let lastListItem  = document.querySelectorAll('.js-newsletter-row');
      let last = (lastListItem[lastListItem.length -1]);
      if (typeof last  !== "undefined"){				
        last.querySelector(".newsletter-row a").focus();
      }
  });
</script>
