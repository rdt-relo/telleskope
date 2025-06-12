<style>
	.shareablelink {
    max-width: 800px;      
    }  
    .table thead tr th{
      padding: .75rem 0;
    } 
    #shareQRCode canvas {
    width: 100%;
}
</style>

<div id="copySurvey" class="modal" aria-label="<?=$form_title?>" aria-modal="true" role="dialog">
	<div  class="modal-dialog modal-lg shareablelink">  
		<div class="modal-content">
			<div class="modal-header">
        <h2 class="modal-title" id="modal-title"><?=$form_title?></h2>
			  <button tabindex="0" type="button" aria-label="Close dialog" class="close" id="closeShareableModal" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
        <div class="col-md-12">
        <?php if(!empty($linkTwo)){ ?>
          <h5><?= gettext("Internal Link");?></h5>
          <hr>
        <?php } ?>
          <div class="input-group mb-3">
            <input tabindex="-1" type="text" id="shareableLink" name="shareableLink" class="form-control" readonly placeholder="<?= gettext("Shareable Link");?>" value="<?= $link; ?>">
            <div class="input-group-append">
              <a aria-live="polite" tabindex="0" role="button" class="input-group-text btn btn-affinity get-shareable-link" onclick="copyShareableLink('<?=gettext('Copied!')?>','shareableLink')" onKeyPress="copyShareableLink('<?=gettext('Copied!')?>','shareableLink')" id="basic-addon2"><?= $copyLinkBtnText;?></a>
            </div>
          </div>
        </div>

        <?php
       if ($_COMPANY->getAppCustomization()['group']['qrcode']) {
        if ($section === 4 || $section === 9 || $section === 2){?>
        <div class="col-md-12 mt-5 p-0">          
          <div class="col-md-7">
          <div class="col-md-12 m-0 p-0 mb-2">
          <h3><?php echo $topicType; ?></h3>
            <p class="ml-2"><?php echo $title; ?></p>
          </div>
          <?php if($section != 9){?>
          <table role="presentation" class="table display" role="grid" tabindex="-1">
            <thead>
            <tr>
              <th><?= $_COMPANY->getAppCustomization()['group']['name'];?> :-</br>
              <?php echo $groupName; ?></th>
            </tr>
            <?php if (!empty($getChapter) && $_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
            <tr>
              <th><?= $_COMPANY->getAppCustomization()['chapter']['name-short-plural']; ?> :-<ul><?php foreach($getChapter as $chaptersName){
                echo "<li>". htmlspecialchars($chaptersName['chaptername']) ."</li>";
              }?></ul></th>
            </tr> <?php } 
            if ($_COMPANY->getAppCustomization()['channel']['enabled'] && $channelName) {?>
            <tr>
              <th><?= $_COMPANY->getAppCustomization()['channel']['name-short'] ?> :-</br><?php echo $channelName; ?></th>
            </tr><?php }?>         
             
            </thead>
            </table>
            <?php } ?>
          </div>
          <div class="col-md-5">
            <div id="shareQRCode"></div>                
          </div>
        </div>
          <?php } ?>

          <?php if(!empty($linkTwo)){ ?>
            <div class="col-md-12">
              <hr>
              <h5><?= gettext("External Link");?></h5>
              <hr>
              <div class="input-group mb-3">
                <input tabindex="-1" type="text" id="shareableLinktwo" name="shareableLinktwo" class="form-control" readonly placeholder="<?= gettext("Shareable Link");?>" value="<?= $linkTwo; ?>">
                <div class="input-group-append">
                  <a tabindex="0" role="button" class="input-group-text btn btn-affinity" onclick="copyShareableLink('<?=gettext('Link copied to clipboard.')?>','shareableLinktwo')" onKeyPress="copyShareableLink('<?=gettext('Link copied to clipboard.')?>', 'shareableLinktwo')" id="basic-addon2"><?= $copyLinkTwoBtnText;?></a>
                </div>
              </div>
              <div class="col-md-12 mt-5">
              <div class="col-md-7">
                <strong class="">External link QR code:</strong>
                <hr>
              </div>
              <div class="col-md-5">
                <div id="shareQRCodeTwo"></div>                
              </div>

              </div>
            



          </div>
          <script>
            $('#shareQRCodeTwo').qrcode("<?= $linkTwo; ?>");
            $('#shareQRCodeTwo > canvas').attr("aria-label","<?= gettext('Scan QR code for')?><?php echo $title; ?>") ;
            $('#shareQRCodeTwo > canvas').attr("role","img");
          </script>
          <?php } ?>
        <?php } ?>
			</div>
		</div>  
	</div>
</div>
<script>
<?php  if ($section === 4 || $section === 9 || $section === 2){ ?>
  <?php  if ($_COMPANY->getAppCustomization()['group']['qrcode']) {?>
	        $('#shareQRCode').qrcode("<?= $link; ?>");
          $('#shareQRCode > canvas').attr("aria-label","<?= gettext('Scan QR code for')?><?php echo $title; ?>") ;
          $('#shareQRCode > canvas').attr("role","img");
    <?php } ?>
<?php } ?>
</script>
<script>
$('#copySurvey').on('shown.bs.modal', function () {
   $('#closeShareableModal').trigger('focus');
});

$('#copySurvey').on('hidden.bs.modal', function (e) {
  $('.modal').removeClass('js-skip-esc-key');
    $('#<?=$_COMPANY->encodeId($id);?>').trigger('focus');
    $('#rid_<?=$_COMPANY->encodeId($id);?>').trigger('focus'); // focus set back for resources table action 
    if ($('.modal').is(':visible')){
        $('body').addClass('modal-open');
    }   
   
    setTimeout(function(){
      $('#getShareableLink').focus();      
    },100);

    if($("#doutdBtn").css("visibility") !== "hidden") {     
      setTimeout(function(){
            $('#doutdBtn').focus();       
        },600);
    }  
    
    setTimeout(function(){
      $('#att_<?=$_COMPANY->encodeId($id);?>').trigger('focus');       
    },600);
    
})
trapFocusInModal("#copySurvey");
</script>