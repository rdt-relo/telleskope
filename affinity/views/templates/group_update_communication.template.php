<style>
   a.active { display: -webkit-inline-flex;font-size: 14px;}
   body { overflow-x: visible!important;} /* for sticky toolbar */
</style>
<input type="hidden" name="communicationid" value="<?= $encCommunicationid; ?>">
<?php if($groupid && $trigger == Group::GROUP_COMMUNICATION_TRIGGERS['GROUP_JOIN']){ ?>
<div class="form-group">
    <label class="col-md-2 control-lable" ><?= gettext("Auto invite to future events");?></label>
    <div class="col-md-10">
        <select class="form-control" id="send_upcoming_events_email" name="send_upcoming_events_email">
            <option value="0" selected><?=gettext('Do not automatically send invitations for future events (default option)')?></option>
            <option value="30" <?=($curr_end_upcoming_events_email==30)?'selected':''?>><?=sprintf(gettext('in %s days'),30)?></option>
            <option value="60" <?=($curr_end_upcoming_events_email==60)?'selected':''?>><?=sprintf(gettext('in %s days'),60)?></option>
            <option value="90" <?=($curr_end_upcoming_events_email==90)?'selected':''?>><?=sprintf(gettext('in %s days'),90)?></option>
            <option value="180" <?=($curr_end_upcoming_events_email==180)?'selected':''?>><?=sprintf(gettext('in %s days'),180)?></option>
            <option value="365" <?=($curr_end_upcoming_events_email==365)?'selected':''?>><?=sprintf(gettext('in %s days'),365)?></option>
        </select>
    </div>
</div>
<?php } else { ?>
    <input type="hidden" id="send_upcoming_events_email" name="send_upcoming_events_email" value="0">
<?php } ?>
<div class="form-group">
    <label class="col-md-2 control-lable" for="email_cc_list"><?= gettext("CC Emails");?></label>
    <div class="col-md-10">
        <input type="text" class="form-control" placeholder="<?= gettext('Add up to 3 comma separated emails to be CC\'ed on outgoing emails');?>" id="email_cc_list" name="email_cc_list" value="<?= $email_cc_list; ?>">
    </div>
</div>
<div class="form-group">
    <label class="col-md-2 control-lable" for="emailsubject"><?= gettext("Subject");?><span style="color:red"> *</span></label>
    <div class="col-md-10">
        <input type="text" class="form-control" placeholder="<?= gettext('Email subject here');?>" id="emailsubject" name="emailsubject" value="<?= $emailsubject; ?>" required>
    </div>
</div>

<div id="email_template"></div>
<div class="text-center">
    <button class="btn create_template " type="button" id="submitbutton" disabled  onclick="updateCommunicationTemplate('<?= $encGroupId; ?>')"><?= gettext("Update");?></button>
    &emsp;
    <button class="btn create_template " type="button" onclick="processCommunicationData('<?= $encGroupId; ?>')"><?= gettext("Cancel");?></button>
</div>
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
            items: ['RECIPIENT_NAME', 'RECIPIENT_FIRST_NAME', 'RECIPIENT_LAST_NAME', 'RECIPIENT_EMAIL', 'COMPANY_NAME','GROUP_NAME', 'CHAPTER_NAME', 'COMPANY_URL', 'GROUP_URL']
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
            }
        },
        blocks: {
            hidden: ['three-text','three-images','three-headings-text','three-images-text','three-images-headings-text','social']
        }
    });

    function updateCommunicationTemplate(g) {
        $(document).off('focusin.modal');
        var app = Revolvapp('#email_template');
        var html = app.editor.getHtml().replace(/(\r\n|\n|\r)/gm,"");         
        let source = app.editor.getTemplate().replace(/(\r\n|\n|\r)/gm,"");
        var section = $('#newsletter_chapter').find(':selected').data('section');
        if (section == null) {
            section = $("#newsletter_chapter").val();
        }
        var formData = $('form#email_template_form').serialize()+'&communication='+encodeURIComponent(html)+'&template='+encodeURIComponent(source)+'&section='+section;
        $.ajax({
            url: 'ajax?updateCommunicationTemplate='+g,
            type:'POST',
            data:formData,
            success: function(data){
                var retVal = JSON.parse(data);

                if (retVal.status>0){
                    swal.fire({title: 'Success',text:"<?= gettext('Communication template updated successfully.');?>",allowOutsideClick:false});
                    processCommunicationData(g);
                } else {
                    swal.fire({title: 'Error!', text: retVal.message,allowOutsideClick:false});
                }
            }
        });
    }


</script>