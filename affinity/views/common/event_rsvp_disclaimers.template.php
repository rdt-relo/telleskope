
<div class="col-12 py-3 " id="accordion<?= $_COMPANY->encodeId($subjectEvent->id())?>">
	<h5 class="py-2 "><?= gettext('Event Waiver');?></h5>
	<div class="col-12 border bg-light rounded p-2">
		
	<?php foreach($disclaimers as $disclaimer){ 

        $disclaimer_language = $_USER->val('language');
        $disclaimerMessage =  $disclaimer->getDisclaimerBlockForLanguage($disclaimer_language);

        if (!empty($disclaimerMessage)){
            $disclaimer_language = $disclaimerMessage['language'];
        }
		$consent_required = $disclaimer->val('consent_required');
		$userConcent = array();
		$concentHelpText = '';
		if($consent_required){
			$concentHelpText = gettext('Before accepting the waiver, please read the disclaimer. Click the disclaimer title to see the full details.');
			$isDisclaimerAvailable = Disclaimer::IsDisclaimerAvailableV2($disclaimer->val('disclaimerid'),$subjectEvent->id());
			if (!$isDisclaimerAvailable){
				$concentHelpText = "";
				$userConcent = $disclaimer->getConsentForUserid($_USER->id(),$subjectEvent->val('eventid'));
			}
		}
	?>
	<div class="card text-left p-2 col-12 w-100 my-1" style="box-shadow: none; border:none;">
		<div class="card-header p-0" style="background-color:#fff;  border-bottom:none;">
			<a class="card-link" data-toggle="collapse" href="#disclaimer_<?= $_COMPANY->encodeId($subjectEvent->id())?>_<?= $_COMPANY->encodeId($disclaimer->val('disclaimerid'))?>" onclick="markAsReadDisclaimer('<?= $_COMPANY->encodeId($disclaimer->val('disclaimerid'))?>', '<?= ($consent_required && empty($userConcent)) ? $disclaimer->val('consent_type') : ''; ?>','<?= $_COMPANY->encodeId($subjectEvent->id())?>')">
				<?= $disclaimerMessage['title']; ?>
			</a>
			<div id="disclaimer_<?= $_COMPANY->encodeId($subjectEvent->id())?>_<?= $_COMPANY->encodeId($disclaimer->val('disclaimerid'))?>" class="collapse" data-parent="#accordion<?= $_COMPANY->encodeId($subjectEvent->id()); ?>">
				<div class="card-body p-0">
					<?= $disclaimerMessage['disclaimer']; ?>
				</div>
			</div>
		<?php 
				if($consent_required){
					if($disclaimer->val('consent_type') == 'checkbox'){
				?>	
					<div class="form-check consent-check">	
						<input aria-labelledby="disclaimerText" <?= empty($userConcent) ? 'disabled' : 'checked disabled'; ?> type="checkbox" class="form-check-input mt-2" name="consent_text_<?= $_COMPANY->encodeId($subjectEvent->id())?>_<?= $_COMPANY->encodeId($disclaimer->val('disclaimerid'))?>" id="consent_text_<?= $_COMPANY->encodeId($subjectEvent->id())?>_<?= $_COMPANY->encodeId($disclaimer->val('disclaimerid'))?>" value="I agree" onclick="initAddConsent('<?= $_COMPANY->encodeId($disclaimer->val('disclaimerid'))?>',1,'<?= $_COMPANY->encodeId($subjectEvent->id())?>'); submitConsent(1, '<?= $_COMPANY->encodeId($disclaimer->val('disclaimerid'))?>', '<?= $disclaimer_language; ?>', '<?= $_COMPANY->encodeId($subjectEvent->val('eventid')); ?>','<?= $_COMPANY->encodeIdsInCSV($subjectEvent->val('disclaimerids')??'')?>');" >
						<label class="form-check-label" for="consentText"><?= gettext('I agree');  ?><?php if($concentHelpText){ ?>&nbsp;<i aria-label="<?=$concentHelpText?>" tabindex="0" class="fa fa-question-circle" data-toggle="tooltip" title="<?= $concentHelpText;?>"></i><?php } ?></label>
						
					</div>
	
				<?php }elseif($disclaimer->val('consent_type') == 'text'){ ?>
					<div class="form-group m-0">
						<input aria-label="" type="hidden" class="form-check-input mt-2" id="consent_text_value_<?= $_COMPANY->encodeId($subjectEvent->id())?>_<?= $_COMPANY->encodeId($disclaimer->val('disclaimerid')); ?>" value="<?= $disclaimerMessage['consent_input_value'];?>">

						<label ><?= sprintf(gettext("By typing in <strong><i>%s</i></strong> below, I provide my consent"),$disclaimerMessage['consent_input_value']);  ?></label><?php if($concentHelpText){ ?>&nbsp;<i aria-label="<?=$concentHelpText?>" tabindex="0" class="fa fa-question-circle" data-toggle="tooltip" title="<?= $concentHelpText;?>"></i><?php } ?>
						<input type="text" id="consent_text_<?= $_COMPANY->encodeId($subjectEvent->id())?>_<?= $_COMPANY->encodeId($disclaimer->val('disclaimerid'))?>" disabled name="consent_text_<?= $_COMPANY->encodeId($subjectEvent->id())?>_<?= $_COMPANY->encodeId($disclaimer->val('disclaimerid'))?>" onkeyup="initAddConsent('<?= $_COMPANY->encodeId($disclaimer->val('disclaimerid'))?>',2,'<?= $_COMPANY->encodeId($subjectEvent->id())?>')"  class="form-control" value="<?= !empty($userConcent) ? $userConcent['consent_text'] : ''; ?>" placeholder="<?= $disclaimerMessage['consent_input_value'];?>">
						
						<button class="btn btn-affinity mt-2 ml-3" id="consent_submit_<?= $_COMPANY->encodeId($subjectEvent->id())?>_<?= $_COMPANY->encodeId($disclaimer->val('disclaimerid'))?>" style="display:none;" onclick="submitConsent(2,'<?= $_COMPANY->encodeId($disclaimer->val('disclaimerid'))?>', '<?= $disclaimer_language; ?>', '<?= $_COMPANY->encodeId($subjectEvent->val('eventid')); ?>','<?= $_COMPANY->encodeIdsInCSV($subjectEvent->val('disclaimerids')??'')?>');"><?= gettext('Submit')?></button>
					</div>
	
			<?php
					}
				}
			?>
		</div>
		
  	</div>
		
		
	<?php } ?>
	</div>
</div>

<script>
	$(function () {
      $('[data-toggle="tooltip"]').tooltip();
  	})
</script>