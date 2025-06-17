<?php

$footerLinks = $_ZONE->val('footer_links');
?>
    <style>
    .card-release-notes{
        width: 100%;
        text-align: left;
        box-shadow:none;
        padding-top: 0px;
        padding-bottom: 0px;
    }
    .cookie-notice {
        border-top: grey solid 1px;
        font-size: small;
        color: #767676;
    }
</style>
    <!-- Footer -->
    <div id="survey_content"></div>
    <div id="modal_over_modal"></div>
    <div id="load_profile"></div>
    <div class="footer-space"></div>

    <footer class="footer">
        <div class="container px-3">
            <div class="row ">
                <div class="col-sm-4 mt-4">
                <h2 class="footer-heading"><?= gettext('Support'); ?></h2>
                <ul class="p-0">

                <?php foreach($footerLinks as $footerLink){
                    if ($footerLink['link_section'] == 'left'){ ?>

                <li class="footer-link">
                    <a href="<?= htmlspecialchars($footerLink['link'])?>" target="_blank" rel="noreferrer noopener"><?= htmlspecialchars($footerLink['link_title']); ?></a>
                </li>

                <?php
                    }
                }
                ?>

                <?php if ($_COMPANY->getAppCustomization()['footer']['show_support_link'] && $_COMPANY->val('vendor_support_email')){ ?>
                <li class="footer-link">
                    <a href="javascript:confirmation(1)"><?= gettext('Vendor Technical Support'); ?></a></li>
                <?php } ?>

                <?php if ($_COMPANY->getAppCustomization()['footer']['show_feedback_link'] && $_COMPANY->val('vendor_feedback_email')){ ?>
                <li class="footer-link">
                    <a href="javascript:confirmation(2)" ><?= gettext('Vendor Feedback'); ?></a>
                </li>
                <?php } ?>

                <?php if($_COMPANY->getAppCustomization()['mobileapp']['custom']['enabled'] && (!empty($_COMPANY->getAppCustomization()['mobileapp']['custom']['mobile_app_ios_url']) || !empty($_COMPANY->getAppCustomization()['mobileapp']['custom']['mobile_app_android_url']))) { ?>
                <li class="footer-link">
                    <a href="javascript:downloadMobileApp()" ><?= gettext('Download Mobile App'); ?></a>
                </li>
               <?php } ?>

                </ul>
                </div>


                <div class="col-sm-4 mt-4">
                <h2 class="footer-heading"><?= gettext('Knowledge Base'); ?></h2>
                <ul class="p-0">

                <?php if ($_COMPANY->getAppCustomization()['footer']['show_training_videos'] && $_COMPANY->getAppCustomization()['helpvideos']['enabled']) {?>
                <li class="footer-link">
                    <a aria-label="Training Videos" href="javascript:startTrainingVideoModal('');"><?= gettext('Training Videos'); ?></a>
                </li>
                <?php } ?>

                <?php
                $dt = date("Ymd");
                $custom_middle_links = false;
                foreach ($footerLinks as $footerLink) {
                    if ($footerLink['link_section'] == 'middle') {
                        $custom_middle_links = true;
                ?>
                        <li class="footer-link">

                            <a href="<?= htmlspecialchars($footerLink['link']) ?>" target="_blank"
                               rel="noreferrer noopener"><?= htmlspecialchars($footerLink['link_title']); ?></a>
                    </li>
                <?php
                    }
                }
                ?>

                <?php if ($_COMPANY->getAppCustomization()['footer']['show_guides']) {?>
                    <li class="footer-link">
                        <a href="https://docs.teleskope.io">
                            <?= gettext('Product Guide'); ?>
                            <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                        </a>
                    </li>

            <?php } ?>

                <?php if (0 /* Deprecated - we now link to archbee */ && $_COMPANY->getAppCustomization()['footer']['show_guides']) {?>
                <li class="footer-link">

                    <a
                        <?php if ($_ZONE->val('app_type') == "officeraven") {  ?>
                        href="https://<?= S3_BUCKET ?>.s3.amazonaws.com/teleskope/guides/office_raven_quick_start_guide.pdf?ts<?=$dt?>"
                        <?php } elseif ($_ZONE->val('app_type') == "talentpeak") { ?>
                        href="https://<?= S3_BUCKET ?>.s3.amazonaws.com/teleskope/guides/talent_peak_quick_start_guide.pdf?ts<?=$dt?>"
                        <?php } else { ?>
                        href="https://<?= S3_BUCKET ?>.s3.amazonaws.com/teleskope/guides/quick_start_guide.pdf?ts<?=$dt?>"
                        <?php } ?>
                        target="_blank" rel="noreferrer noopener" aria-label="<?= gettext('Quickstart Guide - opens in new tab');?>"
                    >
                        <?= gettext('Quickstart Guide'); ?>
                        <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                    </a></li>

                <?php } ?>

                <?php if (0 /* Deprecated - we now link to archbee */ && $_COMPANY->getAppCustomization()['footer']['show_guides'] && $_USER->canManageCompanySomething()) {?>
                    <li class="footer-link">
                        <a
                            <?php if ($_ZONE->val('app_type') == "officeraven") {  ?>
                            href="https://<?= S3_BUCKET ?>.s3.amazonaws.com/teleskope/guides/office_raven_group_lead_guide.pdf?ts=<?=$dt?>"
                            <?php } elseif ($_ZONE->val('app_type') == "talentpeak") { ?>
                            href="https://<?= S3_BUCKET ?>.s3.amazonaws.com/teleskope/guides/talent_peak_group_lead_guide.pdf?ts=<?=$dt?>"
                            <?php } else { ?>
                            href="https://<?= S3_BUCKET ?>.s3.amazonaws.com/teleskope/guides/group_lead_guide.pdf?ts<?=$dt?>"
                            <?php } ?>
                            target="_blank" rel="noopener noreferrer" aria-label="<?= gettext('Group Leader Guide - opens in new tab'); ?>"
                        >
                            <?= gettext('Group Leader Guide'); ?>
                            <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                        </a>
                            </li>
                <?php } ?>

                <?php if (0 /* Deprecated - we now link to archbee */ && $_COMPANY->getAppCustomization()['footer']['show_guides'] && $_USER->isAdmin()) { ?>
                    <li class="footer-link">
                        <a aria-label="Admin Guide - opens in new tab" href="https://<?= S3_BUCKET ?>.s3.amazonaws.com/teleskope/guides/admin_guide.pdf?ts<?=$dt?>"
                        target="_blank" rel="noreferrer noopener"><?= gettext('Admin Guide'); ?> <i class="fas fa-external-link-alt" aria-hidden="true"></i></a>
                </li>
                <?php } ?>

                <?php if ($_COMPANY->getAppCustomization()['footer']['show_release_notes']) {?>
                <li class="footer-link">
                    <a href="javascript:showReleaseNotes()"><?= gettext('Release Notes'); ?></a>
                </li>
                <?php } ?>

                <?php if ($_COMPANY->getAppCustomization()['footer']['show_mailing_list'] && ($_USER->canManageCompanySomething() || $_USER->canCreateContentInCompanySomething() || $_USER->canPublishContentInCompanySomething())) { ?>
                <li class="footer-link">
                    <a href="javascript:joinMailingList()"><?= gettext('Teleskope Mailing List'); ?></a>
                </li>
                <?php } ?>

                </ul>
            </div>


                <div class="col-sm-4 mt-4">

                <h2 class="footer-heading footer-heading-logo">
                    <a href="https://www.teleskope.io/" target="_blank" rel="noopener noreferrer">
                        <img src="img/power.png" alt="Powered by Teleskope" height="40px;">
                    </a>
                </h2>
                <ul class="p-0">

                <?php if ($_COMPANY->val('show_teleskope_privacy_link')) { ?>
                    <li class="footer-link">
                    <a href="https://www.teleskope.io/privacypolicy" target="_blank" rel="noreferrer noopener" aria-label="<?= gettext('Teleskope Privacy Policy - opens in new tab'); ?>" ><?= gettext('Teleskope Privacy Policy'); ?> <i class="fas fa-external-link-alt" aria-hidden="true"></i></a>
                    </li>
                <?php } ?>


                <?php if ($_COMPANY->val('show_teleskope_tos_link')) { ?>
                    <li class="footer-link">
                    <a href="https://www.teleskope.io/terms" target="_blank" rel="noreferrer noopener" aria-label=" <?= gettext('Teleskope Terms of Use - opens in new tab'); ?>"><?= gettext('Teleskope Terms of Use'); ?> <i class="fas fa-external-link-alt" aria-hidden="true"></i></a>
                    </li>
                <?php } ?>

            <?php foreach($footerLinks as $footerLink){
                    if ($footerLink['link_section'] == 'right'){ ?>
                    <li class="footer-link">
                        <a href="<?= htmlspecialchars($footerLink['link'])?>" target="_blank" rel="noreferrer noopener"><?= htmlspecialchars($footerLink['link_title']); ?></a>
                    </li>
            <?php } } ?>
                </ul>
            </div>
            </div>
        </div>
        <div class="mt-4 p-3 cookie-notice">
            <p><?= gettext('Cookie Notice: This platform uses cookies classified as Strictly Necessary cookies. Strictly Necessary cookies are essential to enable visitors to navigate the site and use its features. The Strictly Necessary cookies in use are AWSALB, AWSALBCORS and __Secure-PHPSESSID / PHPSESSID and these cookies are used to connect you to the correct server, improve connection security and to maintain your logged in session respectively. These cookies are necessary for this platform to function and cannot be switched off in our systems.'); ?></p>
            <br/>
            <small><?=REL_VERSION?> (<?=PATCH_VERSION?>)</small>
        </div>
    </footer>

    <div id="supportConfirm" class="modal fade">
        <div aria-label="<?= gettext('Notice')?>" class="modal-dialog" aria-modal="true" role="dialog">
            <div class="modal-content">
                <div class="modal-header text-center">
                    <h4 class="modal-title"><?= gettext('Notice')?></h4>
                </div>
                <div class="modal-body">
                    <p><?= gettext('Please do not send sensitive user data (e.g. Group Roster attachment) through email. After you submit a support request, if needed, we will provide a secure channel for uploading such data.');?></p>
                </div>

                <div class="modal-footer text-center">
                    <span id="submit_btn"></span>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= gettext('Cancel');?></button>
                </div>
            </div>
        </div>
    </div>
    <!--- For screen reading add hidden div to modal with notification ---Album Media --->
    <div id="album_media_count"></div>  
    <div id="hidden_div_for_notification" class="visually-hidden"></div>    
    <!-- SHOW ALERT IF ANY AND UNSET IT -->
    <?php if (!empty($_SESSION['show_alert_to_user'])){ ?>
    <script type="text/javascript">
        $(document).ready(function(){
            Swal.fire({ title: '', text: '<?=$_SESSION['show_alert_to_user']?>', icon: 'info'});

        });
    </script>
    <?php unset($_SESSION['show_alert_to_user']); } ?>

    <script>
        function showReleaseNotes(){
            $.ajax({
                url: 'ajax.php',
                type: "GET",
                data: 'showReleaseNotes=1',
                success : function(data){
                    $('#loadAnyModal').html(data)
                    $('#releaseNotes').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                }
            });
            setTimeout(() => {
					$('[data-dismiss="modal"]').focus();
				}, 500);
        }
    </script>
<script>
        $('#manageMenuBar li').click(function() {
            // Clear interval if any
            if (__autosaveNewsletterInterval){
                console.log('Clearing __autosaveNewsletterInterval');
                clearInterval(__autosaveNewsletterInterval);
            }
        });
        function confirmation(i){
            var cn = "<?= $_COMPANY->val('companyname'); ?>";
            var e = '';
            var m = '';
            if (i == 2){
                e = "<?= $_COMPANY->val('vendor_feedback_email'); ?>";
                m = 'Feedback';
            } else {
                e = "<?= $_COMPANY->val('vendor_support_email'); ?>";
                m = 'Support';
            }
            $('#submit_btn').html('<a href="mailto:'+e+'?subject='+cn+' '+m+'" class="btn btn-affinity" onclick="closeModal()" >Proceed</a>');
            $("#supportConfirm").modal();

            setTimeout(() => {
					$('[data-dismiss="modal"]').focus();
				}, 500);

        }
        function closeModal(){
            $('#supportConfirm').modal('hide');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        }

    if (localStorage.getItem("local_variable_for_table_pagination") == null){
		localStorage.setItem("local_variable_for_table_pagination", 25);
	}else if(localStorage.getItem("local_variable_for_table_pagination") !== 25){
        localStorage.setItem("local_variable_for_table_pagination", localStorage.getItem("local_variable_for_table_pagination"));
    }
</script>
<script>
    
  // Listen for DOMNodeInserted event on the document
 // Redactor Table
 const redactorTargetNode = document.body;
    const redactorObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if(mutation.addedNodes.length){
                mutation.addedNodes.forEach(function (addedNode){
                    if ($(addedNode).hasClass('redactor-dropdown-table')) {
                        // Modify the content of the table dropdown items
                            var tableItem = $(addedNode).find('.redactor-dropdown-item-insert-table');
                            var rowAboveItem = $(addedNode).find('.redactor-dropdown-item-insert-row-above');
                            var rowBelowItem = $(addedNode).find('.redactor-dropdown-item-insert-row-below');
                            var columnLeftItem = $(addedNode).find('.redactor-dropdown-item-insert-column-left');
                            var columnRightItem = $(addedNode).find('.redactor-dropdown-item-insert-column-right');
                            var addHeadItem = $(addedNode).find('.redactor-dropdown-item-add-head');
                            var deleteHeadItem = $(addedNode).find('.redactor-dropdown-item-delete-head');
                            var deleteColumnItem = $(addedNode).find('.redactor-dropdown-item-delete-column');
                            var deleteRowItem = $(addedNode).find('.redactor-dropdown-item-delete-row');
                            var deleteTableItem = $(addedNode).find('.redactor-dropdown-item-delete-table');
                            // Set the inner text or HTML of the items
                            tableItem.html('Insert Table');
                            rowAboveItem.html('Insert Row Above');
                            rowBelowItem.html('Insert Row Below');
                            columnLeftItem.html('Insert Column Left');
                            columnRightItem.html('Insert Column Right');
                            addHeadItem.html('Add Head');
                            deleteHeadItem.html('Delete Head');
                            deleteColumnItem.html('Delete Column');
                            deleteRowItem.html('Delete Row');
                            deleteTableItem.html('Delete Table');
                    }
                });
            }
        });
    });
    redactorObserver.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: false,
        characterData: false,
    });

// On press esc key close modal.
$(document).keydown(function(e) {
	var code = e.keyCode || e.which;
	if (code == 27) {
        if (window.tskp?.is_nested_modal_open ?? false) {
            window.tskp.new_modal.modal('hide');
            window.tskp.is_nested_modal_open = false;
        } else {
            $('.modal:not(.js-skip-esc-key)').modal('hide');
        }
    }
});
</script>
<script>
  $(document).ready(function() {
    $(".skip-to-content-link").on("click", function(event) {
      event.preventDefault();

      var target = $($(this).attr("href"));
      var offset = target.offset().top;

      // Check if the target contains the navigation element
      if (target.find("nav").length > 0) {
        // Scroll to the element with class="inner-background"
        target = $(".inner-background");
        offset = target.offset().top;
      }

      $("html, body").animate({ scrollTop: offset }, 300, function() {
        // Focus on the first focusable element after the scroll
        var firstFocusable = target.find(":focusable").first();
        if (firstFocusable.length > 0) {
          firstFocusable.focus();
        }
      });
    });
  });

  window.tskp ||= {};
  window.tskp.env_vars = {
    TELESKOPE_CDN_STATIC: '..'
  }

  <?php if ($_COMPANY->getAppCustomization()['integrations']['analytics']['adobe']['enabled'] ?? false) { ?>
    window.tskp.analytics.init('<?= $_USER->getExternalId() ?>');
  <?php } ?>

    $('#supportConfirm').on('shown.bs.modal', function () {
        $('#btn_close').trigger('focus');
    });

    $(document).on('click','#album_close', function(){
        $('#album_media_count').html('');
        $("#like_unlike_notification").html('');
    });  
  
    $(document).on('hide.bs.modal','.modal', function () {
        $('#album_media_count').html('');
        $("#like_unlike_notification").html('');
    });

    $(document).on('click','.hasDatepicker', function(){
        $('.ui-datepicker').attr('aria-hidden', 'true');
    });  
    
    //Remove focus when modal close.
    document.addEventListener('hidden.bs.modal', function (event) {   
        if (document.activeElement) {
            document.activeElement.blur();
        }
    });
</script>
<script>
    // @Deprecated - Use tskp_submit_btn instead of class 'prevent-multi-clicks' wherever we want to prevent fast clicking,
$(document).ready(function () {
    document.addEventListener('click', function (event){
        if (event.target.classList.contains('prevent-multi-clicks')) {
            <?php if (Env::IsLocalEnv()) { ?>
              console.warn('Tskp Warning: Usage of prevent-multi-clicks is deprecated, use tskp_submit_btn instead');
            <?php } ?>
            var clickedButton = $(event.target);
            if(!clickedButton.data('isAjaxInProgress')){
                clickedButton.attr('isAjaxInProgress', 'true');
                clickedButton.prop('disabled', 'disabled');
                setTimeout(function () {
                    clickedButton.removeAttr('disabled');
                    clickedButton.removeAttr('isAjaxInProgress');
                }, 1000);
            }
        }
    });   
  
    //Hide image resizer
    document.addEventListener('click', function(e) {
    const elementCls = document.querySelector('.redactor-box');
        if (elementCls != null || false){
            if(!elementCls.matches('.redactor-focus')){
                $('#redactor-image-resizer').hide();
            }     
        }
    })   
    
    //ViewPort < 375 CSS pixels not supported 
    if ($(window).width() < 375) {
        Swal.fire({
            title: 'Message',
            text: 'For an optimal user experience, Please use viewport greater than 375 pixels',
        });
    }
});

// redactorImagePostion() is used to show the selected option for image position under the redactor editor.
function redactorImagePostion() {
        setTimeout(() => {                          
            var styleEmpty = document.querySelector('.redactor-component-active').style;
            console.log(styleEmpty);
            if (styleEmpty === undefined || styleEmpty.length == 0) {
                $('.none').attr("selected","selected");
            }else{
                var currentPosition = document.querySelector('.redactor-component-active').style.cssFloat;
                var optionsArray=[]; 
                $("#selectImagePostion").find('option').each(function() { 
                    optionsArray.push($(this).val());
                }); 
               console.log(optionsArray);
                if ($.inArray(currentPosition, optionsArray) != -1)
                {
                    $('.'+currentPosition+'').attr("selected","selected"); 
                }  
            }
        }, 200);
    }

    $(document).keydown(function(e) {
        // ESCAPE key pressed
        if (e.keyCode == 27) {
            $('[data-toggle="tooltip"]').tooltip("hide");
            $('.popover').popover('hide');
        }

        //Stop shif+tab key on album close button.
        $("#album_close").keydown(function(e) {       
            if (e.shiftKey && e.key == "Tab") {                  
                e.preventDefault(); 
            }
        })
    });  

window.tskp ||= {};
window.tskp.initial_bgcolor = null;  // set default initial_bgcolor variable to null for color key in initial object function.
<?php if($_COMPANY->getStyleCustomization()['css']['profile_initial_color']['color']) { ?>
  window.tskp.initial_bgcolor = '<?= $_COMPANY->getStyleCustomization()['css']['profile_initial_color']['color'];?>'
<?php } ?>

</script>
<script>
    $(document).ready(function () {
        function updateManageButtonState() {
            //console.log('width',$(window).width());
            if ($(window).width() < 1024) {
                $('#btn-manage')
                    .addClass('disabled')
                    .attr('title', '<?=gettext("Please maximize your window to enable the Manage feature.")?>')
                    .tooltip('dispose')
                    .tooltip();
            } else {
                $('#btn-manage')
                    .removeClass('disabled')
                    .attr('title', '')
                    .tooltip('dispose');
            }
        }

        // Prevent action if "disabled"
        $('#btn-manage').on('click', function (e) {
            if ($(this).hasClass('disabled')) {
                e.preventDefault();
                return false;
            }
        });

        updateManageButtonState();
        $(window).resize(updateManageButtonState);
    });
</script>
</body>
</html>

