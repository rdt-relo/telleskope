<div class="col-md form-group-emphasis">
    <div class="form-group">
        <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?=gettext("Booking Email")?></h5>
        <div class="col-md-12">
            <div class="form-group">
                <label class="control-label col-md-12" for="booking_email_subject"><?= gettext("Title"); ?></label>
                <div class="col-md-12">
                    <input id="booking_email_subject" class="form-control" name="booking_email_subject" placeholder="<?= gettext('Title here'); ?>" value="<?= htmlspecialchars($emailTemplate['booking_email_subject']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-12" for="booking_email_content_template"><?= gettext("Description"); ?></label>
                <div id="post-inner" class="col-md-12">
                    <textarea class="form-control" name="booking_message" rows="10" id="booking_email_content_template" maxlength="8000" placeholder="<?= gettext("You can customize the description that will prefilled while booking is scheduled."); ?>"><?= htmlspecialchars($emailTemplate['booking_message']); ?></textarea>
                </div>
            </div>

            <div class="form-group">
                <div class="col-12 text-center">
                    <button class="btn btn-affinity" onclick="saveBookingsEmailTemplate('<?= $_COMPANY->encodeId($groupid); ?>');"><?= gettext('Save Template'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    $('#booking_email_content_template').initRedactor('booking_email_content_template','teamtasks',['counter']);


    function saveBookingsEmailTemplate(g)
    {   
        let booking_email_subject = $('#booking_email_subject').val();
        let booking_message = $('#booking_email_content_template').val();
       
        $.ajax({
            url: 'ajax_bookings.php?saveBookingsEmailTemplate=1',
            type: 'POST',
            data: {groupid:g,booking_email_subject:booking_email_subject,booking_message:booking_message},
            success: function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false});
                } catch(e) { 
                    swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
                }
            }
	    });
    }
</script>
