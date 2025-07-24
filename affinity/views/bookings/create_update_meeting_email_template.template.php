<div class="modal" id="add_update_meeting_email_template_modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close-button">&times;</span>
            <h2><?= $pageTitle ?></h2>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="emailSubject"><?= gettext("Email Subject:"); ?></label>
                <input type="text" id="emailSubject" name="emailSubject" value="<?= htmlspecialchars($email_template['subject'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="emailBody"><?= gettext("Email Body:"); ?></label>
                <textarea id="emailBody" name="emailBody" required><?= htmlspecialchars($email_template['message'] ?? '') ?></textarea>
            </div>
            <?php if ($template_type === 'meeting_reminder_email_template'): ?>
            <div class="form-group">
                <label for="reminder_days"><?= gettext("Reminder Days Before Meeting:"); ?></label>
                <input type="number" id="reminder_days" name="reminder_days" min="1" max="30" value="<?= htmlspecialchars($email_template['reminder_days'] ?? 1) ?>">
                <small class="form-text text-muted"><?= gettext("Number of days before the meeting to send the reminder."); ?></small>
            </div>
            <?php endif; ?>
            <div class="form-group text-center">
                <button class="btn btn-affinity" onclick="saveBookingsEmailTemplate('<?= $_COMPANY->encodeId($groupid) ?>', '<?= $template_type ?>')"><?= gettext('Save Template'); ?></button>
                <button class="btn btn-affinity" data-dismiss="modal"><?= gettext('Close'); ?></button>
            </div>
        </div>
    </div>
</div>
<script>
function saveBookingsEmailTemplate(groupid, template_type) {
    let email_subject = $('#emailSubject').val();
    let email_message = $('#emailBody').val();
    let data = {
        groupid: groupid,
        booking_email_subject: email_subject,
        booking_message: email_message,
        template_type: template_type
    };
    // Add reminder_days if template_type is meeting_reminder_email_template
    if (template_type === 'meeting_reminder_email_template') {
        data.reminder_days = $('#reminder_days').val();
    }
    $.ajax({
        url: 'ajax_bookings.php?saveBookingsEmailTemplate=1',
        type: 'POST',
        data: data,
        success: function(data) {
            try {
                let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title, text: jsonData.message, allowOutsideClick:false}).then(function(result) {
                    // Optionally refresh or close modal
                    closeAllActiveModal();
                });
            } catch(e) {
                swal.fire({title: "<?= gettext("Error") ?>", text: "<?= gettext('Unknown error.') ?>"});
            }
        }
    });
}
</script>