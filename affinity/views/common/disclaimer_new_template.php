
<style>
  .active-page{
      display:block;
  }
  .inactive-page{
      display:none;
  }
  .consent-check {
      margin: 10px 10px 0 10px;
      border-top: 1px lightgrey solid;
      padding: 10px 20px;
}
.on-hover{
  cursor: no-drop;
}
div#loadCompanyDisclaimerModal {
    z-index: 9999;
}
</style>
<div class="modal fade" id="loadCompanyDisclaimerModal" aria-label="<?= $disclaimerMessage['title']; ?>" tabindex="-1" role="dialog" aria-modal="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title"><?= $disclaimerMessage['title']; ?></h2>
        <!-- close button -->
      <?php  if($disclaimer->val('consent_required') != 1){ ?>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeAllActiveModal();">
          <span aria-hidden="true">&times;</span>
        </button>
      <?php } ?>
      </div>
      <div class="modal-body">       
        <div id="disclaimerText"><?= $disclaimerMessage['disclaimer']; ?></div>
        </br>
        </br>
        <input type="hidden" id="disclaimerId" name="disclaimer_id" value="<?= $_COMPANY->encodeId($disclaimer->id()) ?>">
        <input type="hidden" id="disclaimerHook" name="disclaimer_hook" value="<?= $_COMPANY->encodeId($disclaimer->val('hookid')) ?>">
        <input type="hidden" id="consentLang" name="consentLang" value="<?=$disclaimer_language?>">
        <input type="hidden" id="consentContextId" name="consentContextId" value="<?= $_COMPANY->encodeId($consentContextId) ?>">
        <?php
        $disabled = '';
        $modal_proceed_btn = '';
        if($disclaimer->val('consent_required') == 1){
            $disabled = "disabled";
            $modal_proceed_btn = "modal_proceed_btn";
              if($disclaimer->val('consent_type') == 'checkbox'){
              ?>
                <div class="form-check consent-check">
                  <input aria-label="<?= gettext('I agree');  ?>" type="checkbox" class="form-check-input mt-2 consent-type" name="consent_text" id="consentText" value="I agree">
                  <label class="form-check-label" for="check1"><?= gettext('I agree');  ?></label>
                </div>

              <?php }elseif($disclaimer->val('consent_type') == 'text'){ ?>
                  <input aria-label="" type="hidden" class="form-check-input mt-2" name="consent_text" id="consentText" value="<?= $disclaimerMessage['consent_input_value'];?>">
                  <label for="consentTextInput"><?= sprintf(gettext("By typing in <strong><i>%s</i></strong> below, I provide my consent"),$disclaimerMessage['consent_input_value']);  ?></label>
                  <input onkeyup="initAddConsent()" type="text" id="consentTextInput" name="consent_text_input" required="" class="form-control consent-type" placeholder="<?= $disclaimerMessage['consent_input_value'];?>">

              <?php
              }
            } elseif (empty($callOtherMethodOnClose)){ 
              $modal_proceed_btn = "modal_proceed_btn_reload";
            } // end if consent required.
        ?>
          
      
      </div>
      <div class="modal-footer">
      
        <button aria-hidden="true" id="<?= $modal_proceed_btn; ?>" type="button" class="btn btn-affinity on-hover"
       
        <?php if(!empty($callOtherMethodOnClose)){ $i = 1; ?>
          onclick="closeDisclaimerModal();<?=$callOtherMethodOnClose['method']?>(
            <?php foreach($callOtherMethodOnClose['parameters'] as $param){ ?>
                '<?=$param?>'
                <?php if(count($callOtherMethodOnClose['parameters'])>$i){ ?>,<?php } $i++; ?>
            <?php } ?>
              );" <?php } ?>
        <?= $disabled; ?> ><?=gettext('Proceed')?></button>

      <?php  if ($disclaimer->val('hookid') != Disclaimer::DISCLAIMER_HOOK_TRIGGERS['LOGIN_FIRST']) { ?>

            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=gettext('Cancel')?></button>

       <?php } ?>
        
    
          </div>
    </div>
  </div>
</div>



<script>
    retainFocus("#loadCompanyDisclaimerModal");
    <?php if($callOtherMethodOnClose['method'] === "newEventForm"){?>
            $('.create-event-btn').trigger('focus'); 
    <?php }?>
    // get reference to button
    var btn = document.getElementById("modal_proceed_btn");


    // add event listener for the button, for action "click"
    if (btn) {
        //Get input fields value.
        var consentText = $("#consentText").val();
        var disclaimerId = $("#disclaimerId").val();
        var disclaimerHook = $("#disclaimerHook").val();
        var consentLang = $("#consentLang").val();
        var consentContextId = $("#consentContextId").val();

        btn.addEventListener("click", () => addConsent('<?= $reloadOnclose; ?>',consentText,disclaimerId,disclaimerHook,consentLang,consentContextId));  
    }

    var btnreload = document.getElementById("modal_proceed_btn_reload");
    // add event listener for the button, for action "click"
    if (btnreload) {
      btnreload.addEventListener("click", () => updateShowDisclaimerConsentSession());  
    }
    $(function () {
        $("#consentText").click(function () {
            if ($(this).is(":checked")) {            
                $("#modal_proceed_btn").prop( "disabled", false );
                $("#modal_proceed_btn").removeClass('on-hover');  
                $("#modal_proceed_btn").attr('aria-hidden', 'true');              
                setTimeout(() => {
                  $('#modal_proceed_btn').trigger('focus'); 
                }, 500)  
            } else {
                $("#modal_proceed_btn").prop( "disabled", true );
                $("#modal_proceed_btn").addClass('on-hover');  
              }
        });
    });

    // function for proceed btn disabled or unabled.
    function initAddConsent(){
        let vi = $("#consentTextInput").val();
        let v = $("#consentText").val();
        if (v == vi){
            $("#modal_proceed_btn").prop( "disabled", false );
            $("#modal_proceed_btn").removeClass('on-hover');
              setTimeout(() => {
                  $('#modal_proceed_btn').trigger('focus'); 
              }, 500)          
           } else {          
            $("#modal_proceed_btn").prop( "disabled", true );  
            $("#modal_proceed_btn").addClass('on-hover');          
        }
    }

    function updateShowDisclaimerConsentSession(){
      $.ajax({
        url: 'ajax_disclaimer.php?updateShowDisclaimerConsentSession=1',
        type: "POST",
        data: {},
        success: function (data) {
          window.location.reload();
        }
      });
    }

    //This ajax js function is used to add disclaimer consent.    
    function addConsent(reloadPage,consentText,disclaimerId,disclaimerHook,consentLang,consentContextId){         
        $.ajax({
                url: 'ajax_disclaimer.php?addConsent=1',
                type: "POST",
                data: {'disclaimerId': disclaimerId, 'consentText': consentText, 'disclaimerHook': disclaimerHook, 'consentLang':consentLang, 'consentContextId' : consentContextId},
                success: function (data) {
                  try {
                    let jsonData = JSON.parse(data);
                    if (jsonData.status == 1){
                      if (reloadPage) {
                        window.location.reload();
                      }
                    } else {
                      swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false});
                    }
                  } catch(e) {}
                }
            });

    }//end function.   

  //This function is used to close the Disclaimer modal after click on Proceed button.
  function closeDisclaimerModal(){	
      $('#loadCompanyDisclaimerModal').modal('hide');
      $('body').removeClass('modal-open');
	    $('.modal-backdrop').remove();
  }

$('#loadCompanyDisclaimerModal').on('shown.bs.modal', function () {
      $('.consent-type').trigger('focus');  
      $("#modal_proceed_btn").attr('aria-hidden', 'false');             
  });
</script>