<style>
    .create_template {
        background-color: #0077B5;
        color: #fff;
        margin-top: 15px;
    }
    div#ui-datepicker-div {
        z-index: 100 !important;
    }
    a.active { display: -webkit-inline-flex;font-size: 14px;}
    body { overflow-x: visible!important;} /* for sticky toolbar */
.auto-save-success-notification, .auto-save-error-notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    display: none;
    z-index: 9999;
    pointer-events: none;
}
.auto-save-success-notification {
    background-color: rgba(0, 96, 0, 0.8);
}
.auto-save-error-notification {
    background-color: rgba(216, 0, 0, 0.8);
}
</style>
<div class="container input_form_container">
	<div class="row row-no-gutters">
		<div class="col-md-12">
			<div class="col-md-10">
				<div class="inner-page-title">
					<h2><?= gettext("Update Newsletter");?></h2>
				</div>
			</div>
		</div>
		<div class="col-md-12">
			<div class="form-container">
            <form class="form-horizontal" id="email_template_form">
                <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                <input type="hidden" id="newsletterid" name="newsletterid" value="<?= $encNewsletterId; ?>">
                <input type="hidden" id="version" name="version" value="<?= $_COMPANY->encodeId($newsletter->val('version') ?? 1); ?>">
                <input type="hidden" id="enableAutosaveNewsletterPopup" name="enableAutosaveNewsletterPopup" value="1">
                <div class="form-group">
                <label class="control-lable col-md-12"> <?= gettext('Required fields are marked with an asterisk (<span aria-hidden="true" style="color: #ff0000;">*</span>).')?></label>
                </div>
                <!-- Option to select dynamic lists if the user in Admin content section. -->
                 
                <div class="form-group">
					<label for="newsletter_scope" class="control-lable col-md-12"><?= gettext('Create In');?></label>
                    <div class="col-md-12">
                        <select class="form-control" name="newsletter_scope" id="newsletter_scope" onchange="postScopeSelector(this.value)">
                        <?php if(($groupid ==0) && $_USER->isAdmin()){ ?>
                            <option value="zone" <?= (  $newsletter->val('listids') == 0  && $newsletter->val('groupid') == 0) ? 'selected':  ($newsletter->val('isactive') == 1 ? 'disabled' : ''); ?> ><?= sprintf(gettext("All %s"),$_COMPANY->getAppCustomization()['group']["name-plural"]);?></option>
                            <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) { ?>
                            <option value="dynamic_list" <?= $newsletter->val('listids') != '0' ? 'selected': ($newsletter->val('isactive') == 1 ? 'disabled' : ''); ?>><?=sprintf(gettext("Dynamic Lists"), $_COMPANY->getAppCustomization()['group']['name']) ?></option>
                            <?php } ?>
                        <?php }else { ?>
                            <option value="group" <?= (  $newsletter->val('listids') == '0'  && $newsletter->val('groupid') > 0) ? 'selected':  ($newsletter->val('isactive') == 1 ? 'disabled' : ''); ?>><?= sprintf(gettext("This %s only"),$_COMPANY->getAppCustomization()['group']["name"]);?></option>
                            <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled'] && ($_USER->isAdmin() || $_USER->isGroupLead($groupid))) { ?>
                            <option value="dynamic_list" <?= $newsletter->val('listids') != '0' ? 'selected': ($newsletter->val('isactive') == 1 ? 'disabled' : ''); ?>><?=sprintf(gettext("This %s Dynamic Lists"), $_COMPANY->getAppCustomization()['group']['name']) ?></option>
                            <?php } ?>
                        <?php } ?>
                         </select>
                    </div>
				</div>

                <?php if ($_COMPANY->getAppCustomization()['dynamic_list']['enabled']) { 
                    // Dynamic list prompt
							if($groupid == 0){
								$dynamic_list_info = gettext("Only the zone members who are also part of users in the selected dynamic lists will receive an email notification when you publish by email.");
							}else{
								$dynamic_list_info = sprintf(gettext("Only the %s members who are also part of users in the selected dynamic lists will receive an email notification when you publish by email."), $group->val('groupname_short'));
							}
                ?>
				<div id="list_selection">
					<div class="form-group">
						<label  class="control-lable col-md-12" ><?= gettext('Select Dynamic Lists');?></label>
                        <div class="col-md-12">
                            <select class="form-control" name="list_scope[]" id="list_scope" multiple style="display:none;">
                                <?php
                                $listids = explode(',',$newsletter->val('listids')); 
                                foreach($lists as $list){ ?>
                                    <option value="<?= $_COMPANY->encodeId($list->val('listid')); ?>" <?= in_array($list->val('listid'),$listids) ? 'selected': ($newsletter->val('isactive') == 1 ? 'disabled' : ''); ?>><?= $list->val('list_name'); ?></option>
                                <?php } ?>
                            </select>

                            <small>
                                <?= gettext("You can choose one or more existing dynamic lists or you can") ?>
                                <a aria-label="Add a new dynamic list" onclick="manageDynamicListModal('<?=$_COMPANY->encodeId($groupid);?>')" href="javascript:void(0)"><?= gettext("create your own.")?></a>
                                <?= $dynamic_list_info ?>
                                <?= gettext("View the users associated with the selected lists: ")?><a role="button" aria-label="View users" onclick="getDynamicListUsers('<?=$_COMPANY->encodeId($groupid);?>')" href="javascript:void(0)"><?= gettext("view users")?></a>
                            </small>
                        </div>
					</div>
				</div>
                <?php } ?>
                
                <!-- End of dynamic lists option -->
                
                <div class="form-group">
                    <label class=" control-lable col-md-12" ><?= gettext("Newsletter Name");?>
                        <?php if($allowTitleUpdate ){ ?>
                        <span style="color: #ff0000;"> *</span>
                        <?php } else { ?>
                        <i class="fa fa-info-circle fa-small info-black" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext('%s editing is disabled because the %s has been submitted for approval.'), gettext("Newsletter Name"), gettext("Newsletter"))?>"></i>
                        <?php } ?>
                    </label>
                    <div class="col-md-12">
                        <input type="text" class="form-control" name="newslettername"  value="<?= $newsletter->val('newslettername') ?>" id="newslettername" placeholder="<?= gettext("Newsletter name here");?>" <?= !$allowTitleUpdate ? 'readonly' : ''; ?> required>
                    </div>
                </div>

                <?php if (!$groupid) { ?>
                    <input type="hidden" name="admin_content" id="admin_content" value="1">
                <?php } ?>

                <?php if($groupid ){ ?>
                    <?php $use_and_chapter_connector = $newsletter->val('use_and_chapter_connector'); ?>
                    <?php $warn_if_all_chapters_are_selected = true; $displayStyle = 'row12'; ?>
                    <?php include_once __DIR__.'/../common/init_chapter_channel_selection_box.php'; ?>
                <?php } ?>

                <?php if (!$groupid && !$_COMPANY->getAppCustomization()['chapter']['enabled'] && empty($chapters) && !$_USER->canCreateContentInGroupSomeChapter($groupid)) { ?>
                    <input type="hidden" name="chapters[]" value="<?=$_COMPANY->encodeId(0);?>">
                <?php } ?>

                <?php if (!$groupid && !$_COMPANY->getAppCustomization()['channel']['enabled'] && empty($channels) && !$_USER->canCreateContentInGroupSomeChannel($groupid)) { ?>
                    <input type="hidden" name="channelid" value="<?=$_COMPANY->encodeId(0);?>">
                <?php } ?>
                
                <div class="form-group">
                    <label class=" control-lable col-md-12" ><?= gettext("Newsletter Summary");?>
                        <i tabindex="0" class="fa fa-info-circle" aria-hidden="true" data-toggle="tooltip" data-placement="top" aria-label="<?= gettext("A brief description that appears next to the subject line in newsletter recipients email inbox. It's designed to grab attention and encourage you to open the email. If the newsletter is published on external platforms like Viva Engage or Slack, the summary is used to describe the newsletter's content.");?>" title="<?= gettext("A brief description that appears next to the subject line in newsletter recipients email inbox. It's designed to grab attention and encourage you to open the email. If the newsletter is published on external platforms like Viva Engage or Slack, the summary is used to describe the newsletter's content.");?>"></i>
                    </label>
                    <div class="col-md-12">
                        <textarea type="text" class="form-control" name="newsletter_summary" id="newsletter_summary" placeholder="<?= gettext("Newsletter summary here, with a maximum of 255 characters allowed.");?>" rows="3" required><?= $newsletter->val('newsletter_summary') ?></textarea>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="inputEmail" class="col-md-12 control-lable"><?= gettext('Other Options');?></label>
                    <div class="col-sm-12 pt-0">
                        <div class="col-12 ml-1 mt-1">
                            <input type="checkbox" class="form-check-input multiday" name="content_replyto_email_checkbox" id="content_replyto_email_checkbox" <?= $newsletter->val('content_replyto_email') ? 'checked' : '' ?>>
                            <label class="form-check-label" for="content_replyto_email_checkbox"> <?= gettext("Custom Reply To Email"); ?></label>
                            <div id="replyto_email" class="" <?= (empty($newsletter->val('content_replyto_email'))) ? 'style="display:none;"' : '' ?> >
                                <input type="email" id="content_replyto_email" name="content_replyto_email" value="<?= $newsletter->val('content_replyto_email') ?>" class="form-control" placeholder="<?= gettext('Add a custom reply to email'); ?>"/>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="clearfix"></div>
                <label class=" control-lable col-md-12" ><?= gettext("Newsletter");?>
                    <?php if(!$allowDescriptionUpdate ){ ?>
                    <i class="fa fa-info-circle fa-small info-black" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="<?= sprintf(gettext('%s editing is disabled because the %s has been submitted for approval.'), gettext("Newsletter"), gettext("Newsletter"))?>"></i>
                    <?php } ?>
                </label>
                <div style="display:<?= $newsletter_personalized_html ? 'none' : ''; ?>;" id="email_template"></div>
                <?php if ($newsletter_personalized_html){ ?>
                <div class="m-5" id="newsletterContent">
                    <?= $newsletter_personalized_html; ?>
                </div>
                <?php } ?>
                <div class="clearfix"></div>
                <div class="text-center mt-3 mb-3">
                    <button class="btn btn-affinity prevent-multi-clicks" type="button" onclick="saveNewsletterDraft('<?= $encNewsletterId; ?>','<?= $encGroupId; ?>')"><?= gettext("Save Draft");?></button>
                    <button class="btn btn-affinity prevent-multi-clicks" type="button" id="submitbutton" disabled  onclick="saveNewsletterClose('<?= $encNewsletterId; ?>','<?= $encGroupId; ?>')"><?= gettext("Save and Close");?></button>
                    <button class="btn btn-affinity-gray" type="button" onclick="closeNewsletterEditor('<?= $encNewsletterId; ?>', '<?= $encGroupId; ?>')"><?= gettext("Close");?></button>
                </div>
        </form>
        </div>
    </div>
</div>
<div id="auto-save-success-notification" class="auto-save-success-notification"><?= gettext('Your work has been saved.') ?></div>
<div id="auto-save-error-notification" class="auto-save-error-notification"><?= gettext('Auto-saving failed.') . ' ' . sprintf(gettext('Unable to save due to outdated version. To avoid losing your changes, copy them locally, re-edit "%s", and try again.'), $newsletter::GetCustomName()) ?></div>
<div id="email_template_bkup" style="display: none;"></div>

<script type="text/javascript">
    $(function () {
        $("#content_replyto_email_checkbox").click(function () {
            if ($(this).is(":checked")) {
                $("#replyto_email").show();
            } else {
                $("#replyto_email").hide();
            }
        });

        $('[data-toggle="tooltip"]').tooltip();
    });

</script>
<!-- source: false, -->
<script>
    /*
     * template (string) : template code
    **/
    $('#submitbutton').prop("disabled", false); 
    var app  =  Revolvapp('#email_template', {   
        source: false,
        plugins: ['variable', 'reorder'],
        variable: {
            items: ['RECIPIENT_NAME', 'RECIPIENT_FIRST_NAME', 'RECIPIENT_LAST_NAME', 'RECIPIENT_EMAIL', 'PUBLISH_DATE_TIME']
        },
        content:   '<?= $template; ?>',     
        editor: {
            font: 'TeleskopeNewsletter, Lato,Helvetica, Arial, sans-serif',
            path: '<?= TELESKOPE_CDN_STATIC ?>/vendor/js/revolvapp-2-3-10/',
            lang: '<?= $_COMPANY->getImperaviLanguage();?>'
        },
        toolbar: {
            sticky:true,
        },
        image: {
            upload: "ajax_newsletters.php?uploadEmailTemplateMedia=<?= $encGroupId; ?>",
            url: false
        },
        subscribe: {
            'image.upload.error': function(error) {
                swal.fire({title: 'Error!',text:error.params.response.message});
            },
            'event.keydown': function() {
                lastKeyPressTime = Date.now();
            },
            'event.paste': function() {
                lastKeyPressTime = Date.now();
            }
        },
        blocks: {
            hidden: ['three-text','three-images','three-headings-text','three-images-text','three-images-headings-text','social']
        }
    });

</script>
<script>
      $(document).ready(function () {
        var currentSelection = $("#newsletter_scope").val();
        postScopeSelector(currentSelection);
        checkCheckboxes();
      });
</script>
<script>
	function postScopeSelector(i) {
        handleCreationScopeDropdownChange('#newsletter_scope');
        $("#chapter_input").prop("disabled", false);
		$("#channel_input").prop("disabled", false);
        if (i == 'dynamic_list') {
			$("#list_selection").show();
            $("#chapter").prop("disabled", true);
            $("#list_scope").prop("disabled", false);
            $('#list_scope').multiselect('refresh');
            $("#channels_selection_div").hide();
            $("#chapters_selection_div").hide();
            $("#chapter_input").prop("disabled", true);
			$("#channel_input").prop("disabled", true);
		}else if(i == 'group'){
            $("#list_selection").hide();
            $("#chapter").prop("disabled", false);
            $("#list_scope").prop("disabled", true);
            $('#list_scope').multiselect('refresh');
            $("#channels_selection_div").show();
            $("#chapters_selection_div").show(); 
        } else {
            $("#list_selection").hide();
            $("#chapter").prop("disabled", false);
            $("#list_scope").prop("disabled", true);
            $('#list_scope').multiselect('refresh');
            $("#channels_selection_div").show();
            $("#chapters_selection_div").show();
        }
	}

	$('#list_scope').multiselect({
		nonSelectedText: "<?=gettext("No list selected"); ?>",
		numberDisplayed: 3,
		nSelectedText: "<?= gettext('List selected');?>",
		disableIfEmpty: true,
		allSelectedText: "<?= gettext('Multiple lists selected'); ?>",
		enableFiltering: true,
		maxHeight: 400,
		enableClickableOptGroups: true
	});
    $('#list_scope').on('change', function() {
        toggleChapterChannelBoxVisibility(); 
    });
</script>
<script>

    $("#newslettername").on("input", function () {
    var updatedName = $(this).val();
    if (!updatedName.trim()) {
            Swal.fire({
                text: 'Please enter a newsletter name',
                allowOutsideClick: false
            });
        }
    });

    var autoSaveInterval; // store auto save timer here
    var lastKeyPressTime = 0;
    var lastSaveTime = 0;

    function showToastError(errorMessage) {
        let notification_div = $("#auto-save-error-notification");
        let errorMessageToDisplay = "<?= addslashes(gettext('Auto-saving failed.')) ?> ";

        if (errorMessage) {
            errorMessageToDisplay += errorMessage;
        } else {
            errorMessageToDisplay += ' <?=  addslashes(sprintf(gettext('Unable to save due to outdated version. To avoid losing your changes, copy them locally, re-edit "%s", and try again.'), $newsletter::GetCustomName())) ?>';
        }

        notification_div.html(errorMessageToDisplay);
        notification_div.css("display", "block");

        let hidden_div_for_notification = $("#hidden_div_for_notification");
        hidden_div_for_notification.attr({role: "status", "aria-live": "polite"});
        hidden_div_for_notification.html(errorMessageToDisplay);

        setTimeout(function () {
            notification_div.css("display", "none");
            notification_div.html("");
        }, 4000);
    }

    function showToastSuccess(successMessage) {
        let notification_div = $("#auto-save-success-notification");
        let successMessageToDisplay = "<?= addslashes(gettext('Your work has been saved.')) ?> ";

        if (successMessage) {
            successMessageToDisplay += " " + successMessage;
        }

        notification_div.html(successMessageToDisplay);
        notification_div.css("display", "block");

        let hidden_div_for_notification = $("#hidden_div_for_notification");
        hidden_div_for_notification.attr({role: "status", "aria-live": "polite"});
        hidden_div_for_notification.html(successMessageToDisplay);

        setTimeout(function() {
            notification_div.css("display", "none");
            notification_div.html("");
        }, 4000);
    }

    async function initSaveNewsletter(newsletterId, groupid)
    {
        stopAutoSave();

        try {
            if (lastKeyPressTime > lastSaveTime) {
                await saveNewsletterGracefully(newsletterId, groupid, 1);
            } else {
                console.log('Skipping autosave, no changes detected');
            }
        } catch (err) {
            console.error('Autosave failed:', err);
        } finally {
            // Always restart the autosave interval
            startAutoSave(60);
        }

        return true;
    }

    async function saveNewsletterDraft(newsletterId, groupid) {
        stopAutoSave();

        try {
            await saveNewsletterGracefully(newsletterId, groupid, 0);
        } catch (err) {
            console.error('Manual draft save failed:', err);
        } finally {
            startAutoSave(60);
        }

        return true;
    }

    async function saveNewsletterClose(newsletterId, groupid) {
        stopAutoSave();

        try {
            await saveNewsletterGracefully(newsletterId, groupid, 0);
            getGroupNewsletters(groupid);
        } catch (err) {
            console.error('Save on close failed:', err);
            startAutoSave(60);
        }

        setTimeout(() => {
            $("#"+newsletterId).focus();
        }, 2000);

        return true;
    }

    function closeNewsletterEditor(newsletterId, groupid) {
        stopAutoSave();
        getGroupNewsletters(groupid);
        return true;
    }

    function saveNewsletterGracefully(newsletterId, groupid, autoSave) {
    return new Promise((resolve, reject) => {
        $("#hidden_div_for_notification").text('');
        $("#hidden_div_for_notification").removeAttr('aria-live');

        console.log('Initiate newsletter save');
        let saveStartTime = Date.now(); // Set the time

        let newsletter_summary = $("#newsletter_summary").val();
        if (newsletter_summary.length > 255) {
            swal.fire({
                title: '<?=gettext("Error");?>',
                text: "<?= gettext('Maximum 255 characters are allowed in the newsletter summary.');?>",
                allowOutsideClick: false
            });
            return reject("validation error");
        }

        // ****************************
        // *** Fixes for newsletter ***
        // ****************************
        let app = Revolvapp('#email_template');
        let source = app.editor.getTemplate().replace(/(\r\n|\n|\r)/gm,"");
        // Check if <re-preheader> already exists
        if (source.includes("<re-preheader>")) {
            let newPreheaderContent = "<re-preheader>"+newsletter_summary+"</re-preheader>";
            source = source.replace(/<re-preheader>.*?<\/re-preheader>/s, newPreheaderContent);
        } else {
            const parser = new DOMParser();
            const doc = parser.parseFromString(source, 'text/html');

            const reBodyElement = doc.querySelector('re-body');

            if (reBodyElement) {
                const rePreheader = doc.createElement('re-preheader');
                rePreheader.innerHTML = newsletter_summary; 
                reBodyElement.insertBefore(rePreheader, reBodyElement.firstChild);
            }
            source = doc.body.outerHTML;
        }

        source = fixRevolvAppAnchorLinksWithoutProtocol(source);

        // Update the editor contents
        // Dont update the source template as it refreshes the newsletter page breaking the flow
        //app.editor.setTemplate(source);
        // Instead use a hidden element for temporarily creating a new editor to extract HTML.
        let app_bkup = Revolvapp('#email_template_bkup');
        app_bkup.editor.setTemplate(source);

        let newsletterContent = app_bkup.editor.getHtml();
        app_bkup.editor.setTemplate('');

        // 1. Fix newsletter content
        let newsletterContentContainer = document.createElement('div');
        newsletterContentContainer.innerHTML = newsletterContent;

        // 2. Update each <a> tag inside the container
        newsletterContentContainer.querySelectorAll('a').forEach(function(anchor) {
            const parent = anchor.parentElement;
            if (parent) {
                const parentFontSize = parent.style.fontSize || window.getComputedStyle(parent).fontSize;
                anchor.style.fontSize = parentFontSize || parentFontSize;
            }
        });

        newsletterContent = newsletterContentContainer.innerHTML;
        newsletterContent = newsletterContent.replace(/width="(\d+)px"/g, 'width="$1"');
        newsletterContent = newsletterContent.replace(/(\r\n|\n|\r)/gm, "");

        // *******************************
        // *** End of newsletter fixes ***
        // *******************************

        let formData = $('form#email_template_form').serialize() +
            '&newsletter=' + encodeURIComponent(newsletterContent) +
            '&template=' + encodeURIComponent(source) +
            '&autoSave=' + autoSave;

        $.ajax({
            url: 'ajax_newsletters?updateNewsletter=' + groupid,
            type: 'POST',
            data: formData,
            success: function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    if (jsonData.status == 1) {

                        lastSaveTime = saveStartTime;
                        $('#version').val(jsonData.val);
                        console.log(`Saved newsletter at ${lastSaveTime}, new version ${jsonData.val}`);

                    }

                    if (!autoSave) {
                        swal.fire({ title: jsonData.title, text: jsonData.message, allowOutsideClick: false })
                            .then(function (result) {
                                if (jsonData.status == 1) {
                                    resetContentFilterState(2);
                                    $(".prevent-multi-clicks:first").focus();
                                    resolve(jsonData); // Resolve after swal interaction
                                } else if (jsonData.status == -3) {
                                    $("#chapter_input").focus();
                                    $("#channel_input").focus();
                                    reject(-3);
                                } else {
                                    $(".prevent-multi-clicks:first").focus();
                                    reject(0);
                                }
                            });
                    } else {
                        if (jsonData.status == 1) {
                            showToastSuccess(jsonData.message);
                        } else {
                            showToastError(jsonData.message);
                        }
                        resolve(jsonData); // Resolve immediately in autoSave mode
                    }
                } catch (e) {
                    swal.fire({
                        title: '<?=gettext("Error");?>',
                        text: "<?= gettext('Unknown error.');?>",
                        allowOutsideClick: false
                    }).then(function (result) {
                        reject(e);
                    });
                }
            },
            error: function(xhr, status, error) {
                reject(error);
            }
        });
    });
}

    
    $(document).ready(function() {
        startAutoSave(60);
        // To clear the auto-save when the user leaves the page:
        $(window).on('beforeunload unload', stopAutoSave);
    });

    function startAutoSave(delayInSeconds) {
        if (!autoSaveInterval) {
            console.log('Starting autosave with interval of', delayInSeconds, 'seconds');
            autoSaveInterval = setInterval(function () {
                initSaveNewsletter('<?= $encNewsletterId; ?>', '<?= $encGroupId ?>');
            }, delayInSeconds * 1000); // Check after 60 seconds
        }
    }
    function stopAutoSave() {
        if (autoSaveInterval !== null) {
            console.log('Stopping autosave, clearing interval:', autoSaveInterval);
            clearInterval(autoSaveInterval);
            autoSaveInterval = null;
        }
    }

    $('.multiselect-container li').keydown(function(e) {
        if (e.keyCode === 27) {
            $('.multiselect-container').removeClass('show');
        }
    });
</script>