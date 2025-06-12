
<style>
    .iframe-xmp {
        white-space: pre-wrap; 
        white-space: -moz-pre-wrap;
        white-space: -pre-wrap;
        white-space: -o-pre-wrap;
        word-wrap: break-word;
        background-color:#000;
        color:#fff;
        padding:20px;
    }
</style>
<div id="calendarIframeModal" class="modal fade">
<div aria-label="<?=$form_title?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
    <div class="modal-content">
        <div class="modal-header">
    <h4 class="modal-title" id="form_title"><?=$form_title?></h4>
          <button aria-label="close" type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="col-md-12">
                <xmp class="iframe-xmp" id="iframe_content"><iframe src="<?= $iframeLink; ?>" title="Calendar Iframe" width="800" height="660"></iframe></xmp>
                <p style="color:red;"><?= gettext("Note: Place this iframe anywhere in your html code to access calendar.") ?></p>
                <?php if ($params['requireAuthToken']) { ?>
                    <p style="color:red;"><?= gettext("Note 2: For this iFrame to work you also need to provide an authentication token. Please contact your Teleskope support team for details on how to generate and send an authentication token.") ?></p>
                <?php } ?>
                <div class="col-md-12 text-center mt-3">
                    <button class="btn btn-affinity" onclick="copyIframe();">Copy iFrame</button>
                </div>
            </div>
        </div>
    </div>  
</div>
</div>

<script>
    function copyIframe(){
        var dummy = document.createElement('input'),
		text = $("#iframe_content").html();
        document.body.appendChild(dummy);
        dummy.value = text;
        dummy.select();
        dummy.setSelectionRange(0, 99999);  /* For mobile devices */
        document.execCommand('copy');
        document.body.removeChild(dummy);
        swal.fire({title: '<?= gettext("Success");?>',text:'<?= gettext("iFrame copied successfully to your clipboard"); ?>'}).then(function(result) {
            $('#calendarIframeModal').modal('hide');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        });
    }
</script>
              