<div tabindex= "-1" id="previewNewsletter" class="modal">
    <div aria-label="<?=$newsletter->val('newslettername');?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
      <div class="modal-content " >
        <div class="modal-header">
          <h2 id="modal-title" class="modal-title" ><?=$newsletter->val('newslettername'); ?><?php if($newsletter->val('pin_to_top')){ ?>
            <i class="fa fa-thumbtack ml-1" style="font-size:small;vertical-align:super;" aria-hidden="true"></i>
          <?php } ?></h2>
            <button aria-label="Close dialog" type="button" class="close" data-dismiss="modal" id="close_btn_1">&times;</button>
          
        </div>
        <div class="modal-body" >
          <style>
            .main.container, .footer.container{
              padding:0 !important;
            }
            .divider {
              margin:0 !important;
            }
            #newsletterContent h1, h2, h3, h4, h5, h6 {
                display: block;
            }
          </style>
          <div id="newsletterContent">
            <?= $newsletter_personalized_html; ?>
          </div>
         
          <?php if ($newsletter->val('isactive') != Newsletter::STATUS_DRAFT ){ ?>
            <?php if($_COMPANY->getAppCustomization()['newsletters']['likes']) { ?>
              <!-- Like Widget Start -->
                <div class="col-md-12 p-0 after-comnt" id="likeUnlikeWidget">
                  <?php include (__DIR__.'/../common/comments_and_likes/topic_like_and_likers_bedge.template.php'); ?>
                </div>
              <!-- Like Widget End -->
            <?php } ?>
            <?php if($_COMPANY->getAppCustomization()['newsletters']['comments']) { ?>
                <!-- Start of comments Widget -->
                <?php include(__DIR__ . "/../common/comments_and_likes/comments_container.template.php"); ?>
            <?php } ?>
          <?php } ?>

        </div>
        <div class="modal-footer text-center">
          <button  id="closeButton" type="button" class="btn btn-affinity"  data-dismiss="modal" ><?= gettext("Close");?></button>
        </div>
      </div>
  
    </div>
</div>
<script>

$('#previewNewsletter').on('shown.bs.modal', function () {
    $('.modal').addClass('js-skip-esc-key');
    $('#close_btn_1').trigger('focus');
});

$('#previewNewsletter').on('hidden.bs.modal', function (e) {
    $('#<?=$_COMPANY->encodeId($newsletterid);?>').trigger('focus');
})

retainFocus("#previewNewsletter");
</script>
