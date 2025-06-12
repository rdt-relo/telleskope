<div id="downloadAppModal" class="modal fade">
	<div aria-label="<?= sprintf(gettext("Download the %s today"),$_COMPANY->getAppCustomization()['mobileapp']['custom']['mobile_app_name']);?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">  
		<div class="modal-content">
			<div class="modal-header">
                <h4 class="modal-title text-center" id="download_app_title"><?= sprintf(gettext("Download the %s today"),$_COMPANY->getAppCustomization()['mobileapp']['custom']['mobile_app_name']);?></h4>
			</div>
            <div class="modal-body">
                <div class="col-md-12 mt-3 text-center">
                    <p>
                        <?=sprintf(gettext("Have %1s %2s at your fingertips. Join %3s, read announcements and newsletters, RSVP to events and more."),$_COMPANY->val('companyname'), $_COMPANY->getAppCustomization()['group']["name-plural"], $_COMPANY->getAppCustomization()['group']["name-short-plural"]);?>
                    </p>

                    <div class="col-6 mt-5 mb-5">
                        <?php if (!empty($_COMPANY->getAppCustomization()['mobileapp']['custom']['mobile_app_ios_url'])) { ?>
                        <p><a href="<?=$_COMPANY->getAppCustomization()['mobileapp']['custom']['mobile_app_ios_url'];?>"><?=gettext("Download on iOS mobile phone")?></a></p><br>
                        <!-- QR CODE HERE -->
                        <div style="text-align:center;" id="ios-qrcode"></div>
                        <?php } else { ?>
                            <p style="padding-top: 5em; color: red;"><?=gettext("iOS mobile application is not available at this time")?></p><br>
                        <?php } ?>
                    </div>

                    <div class="col-6 text-center mt-5 mb-5">
                        <?php if (!empty($_COMPANY->getAppCustomization()['mobileapp']['custom']['mobile_app_android_url'])) { ?>
                        <p><a href="<?=$_COMPANY->getAppCustomization()['mobileapp']['custom']['mobile_app_android_url'];?>"><?=gettext("Download on Android mobile phone")?></a></p><br>
                        <!-- QR CODE HERE -->
                        <div style="text-align:center;" id="android-qrcode"></div>
                        <?php } else { ?>
                        <p style="padding-top: 5em; color: red;"><?=gettext("Android mobile application is not available at this time")?></p><br>
                        <?php } ?>
                    </div>
                    <button type="button" id="btn_close" data-dismiss="modal" class="btn btn-affinity-gray"><?= gettext("Cancel");?></button>
                </div> 
            </div>
        </div>
	</div>
</div>

<script>
	$('#ios-qrcode').qrcode("<?php if(!empty($_COMPANY->getAppCustomization()['mobileapp']['custom']['mobile_app_ios_url'])){ echo $_COMPANY->getAppCustomization()['mobileapp']['custom']['mobile_app_ios_url']; }  ?>");
    $('#android-qrcode').qrcode("<?php if(!empty($_COMPANY->getAppCustomization()['mobileapp']['custom']['mobile_app_android_url'])){ echo $_COMPANY->getAppCustomization()['mobileapp']['custom']['mobile_app_android_url']; }  ?>");

    $('#downloadAppModal').on('shown.bs.modal', function () {
		$('#btn_close').trigger('focus');
	});
</script>