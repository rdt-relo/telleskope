<style>
    body { overflow-x: visible!important;} /* for sticky toolbar */
</style>
<?php if($groupid && $trigger == Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_JOIN']){ ?>
<div class="form-group">
    <label class="col-md-2 control-lable" for="send_upcoming_events_email"><?= gettext("Auto invite to future events");?></label>
    <div class="col-md-10">
        <select class="form-control" id="send_upcoming_events_email" name="send_upcoming_events_email">
            <option value="0"><?=gettext('Do not automatically send invitations for future events (default option)')?></option>
            <option value="30"><?=sprintf(gettext('in %s days'),30)?></option>
            <option value="60"><?=sprintf(gettext('in %s days'),60)?></option>
            <option value="90"><?=sprintf(gettext('in %s days'),90)?></option>
            <option value="180"><?=sprintf(gettext('in %s days'),180)?></option>
            <option value="365"><?=sprintf(gettext('in %s days'),365)?></option>
        </select>
    </div>
</div>
<?php } else { ?>
    <input type="hidden" id="send_upcoming_events_email" name="send_upcoming_events_email" value="0">
<?php } ?>
<div class="clearfix"></div>
<div class="form-group">
    <label class="col-md-2 control-lable" for="newsletter_template"><?= gettext("Template");?></label>
    <div class="col-md-10">
        <select type="text" class="form-control" id="newsletter_template" name="templateid" templateid="templateid" onchange="initRevolappEditor('<?=$encGroupId?>')" >
            <option value=""><?= gettext("Select Template");?></option>
            <?php if(count($templates) == 0){ ?>
                <option disabled value=''><?= gettext("No template available, please configure a template first."); ?> </option>
            <?php } ?>
            <?php for($i=0;$i<count($templates);$i++){ ?>
            <option value='<?= $_COMPANY->encodeId($templates[$i]['templateid']) ?>'><?= htmlspecialchars($templates[$i]['templatename']); ?></option>
            <?php } ?>
        </select>
    </div> 
</div>
<div class="form-group">
    <label class="col-md-2 control-lable" for="email_cc_list"><?= gettext("CC Emails");?></label>
    <div class="col-md-10">
        <input type="text" class="form-control" placeholder="<?= gettext("You may add up to three email addresses on outgoing emails. Place a comma between each email address.");?>" id="email_cc_list" name="email_cc_list" value="" >
    </div>
</div>
<div class="form-group">
    <label class="col-md-2 control-lable" for="emailsubject"><?= gettext("Subject");?><span style="color:red"> *</span></label>
    <div class="col-md-10">
        <input type="text" class="form-control" placeholder="<?= gettext("Email subject here");?>" id="emailsubject" name="emailsubject" value="" required>
    </div>
</div>
<div class="clearfix"></div>
<div id="email_template_note" class="alert-secondary mx-3 px-3"></div>
<div class="clearfix"></div>
<div id="email_template"></div>
<div class="text-center">
    <button class="btn create_template prevent-multi-clicks" type="button" id="submitbutton" disabled  onclick="createCommunicationTemplate('<?= $encGroupId; ?>')"><?= gettext("Create");?></button>
    &emsp;
    <button class="btn create_template " type="button" onclick="manageCommunicationsTemplates('<?= $encGroupId; ?>')"><?= gettext("Cancel");?></button>
</div>
<!-- source: false, -->

<script>
    /*
     * template (string) : template code
     **/
    
    $(document).ready(function(){ 
        $("#submitbutton").prop("disabled",true); 
    });
    function initRevolappEditor (g){
        var c = $("#newsletter_chapter option:selected" ).val();
        var t = $("#newsletter_template option:selected" ).val();
        var section = $('#newsletter_chapter').find(':selected').data('section');
        if (c === "" || t === "") {
            $('#submitbutton').prop("disabled", true);
            return;
        } else {
            $('#submitbutton').prop("disabled", false);
            if (typeof app === 'undefined' || app === null) {
                var app = Revolvapp('#email_template', {
                    source: false,
                    plugins: ['variable', 'reorder'],
                    variable: {
                        items: ['RECIPIENT_NAME', 'RECIPIENT_FIRST_NAME', 'RECIPIENT_LAST_NAME', 'RECIPIENT_EMAIL', 'COMPANY_NAME','GROUP_NAME', 'CHAPTER_NAME', 'COMPANY_URL', 'GROUP_URL']
                    },
                    //content: template,
                    editor: {
                        font: 'TeleskopeNewsletter, Lato,Helvetica, Arial, sans-serif',
                        path: '../vendor/js/revolvapp-2-3-10/',
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
                        }
                    },
                    blocks: {
                        hidden: ['three-text','three-images','three-headings-text','three-images-text','three-images-headings-text','social']
                    }
                });
            }
            $.ajax({
                url: 'ajax_newsletters?fetchTemplate='+g,
                type:'GET',
                data:{'templateid':t, 'chapterid':c, 'section':section},
                success: function(data){
                    if (data.startsWith('Error')) {
                        swal.fire({title: 'Error!',text:"<?= gettext("Unable to load the template");?>"});
                    } else {
                        $("#email_template_note").text('<?= addslashes(gettext('This template may contain variables (displayed in [%VARIABLES%] format) that will be replaced with actual information before sending. To see the final version of the email with all the details filled in, use the Preview function after saving this template'));?>');
                        app.editor.setTemplate(data);
                        $("#newsletter_chapter option:first").attr("disabled", "true");
                        $("#communication_trigger option:first").attr("disabled", "true");
                        $("#newsletter_template option:first").attr("disabled", "true");

                    }
                }
            });
        }
    }

    function createCommunicationTemplate(g) {
        $(document).off('focusin.modal');
        var app = Revolvapp('#email_template');
        var html = app.editor.getHtml().replace(/(\r\n|\n|\r)/gm,"");         
        let source = app.editor.getTemplate().replace(/(\r\n|\n|\r)/gm,"");
       
        if ($("#templateid").val()==''){
            swal.fire({title: 'Message',text:"<?= gettext("Please select an template");?>",allowOutsideClick:false}); 
        } else {
            var section = $('#newsletter_chapter').find(':selected').data('section');
            if (section == null) {
                section = $("#newsletter_chapter").val();
            }
            var formData = $('form#email_template_form').serialize()+'&communication='+encodeURIComponent(html)+'&template='+encodeURIComponent(source)+'&section='+section;
            preventMultiClick(1);
            $.ajax({
                url: 'ajax?createCommunicationTemplate='+g,
                type:'POST',
                data:formData,
                success: function(data){
                    try {
                        let jsonData = JSON.parse(data);
                        swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function (result) {
                            if (jsonData.status == 1){
						        processCommunicationData(g);
                            }
					    });
                    } catch(e) { 
                        swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false});
                    }
                }
            });
         }
    }



</script>