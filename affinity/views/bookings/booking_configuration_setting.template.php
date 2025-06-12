<div class="col-12 form-group-emphasis">
    <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext('Booking Setting');?></h5>
    <div class="pl-2">
        <div class="form-group form-inline">
            <p>
                <?= sprintf(gettext('Allow meetings to be booked up to %1$s days before the start date.'),'<input aria-label="'.gettext('Enter the number of days before the start date that the meeting can be booked.').'" class="form-control form-control-sm" type="text" id="days_before_start_to_allow_booking" style="width: 35px;" value="'.$bookingBuffer['days_before_start_to_allow_booking'].'">');?>
            <button aria-label="<?= gettext('Update Setting');?>" type="button" onclick="saveBookingsBufferSetting('<?= $_COMPANY->encodeId($groupid); ?>')" class="btn btn-sm btn-primary"><?= gettext('Update');?></button>
            </p>
        </div>
    </div>
</div>

<script>
    function saveBookingsBufferSetting(g)
    {   
        let days_before_start_to_allow_booking = $('#days_before_start_to_allow_booking').val();
        if (days_before_start_to_allow_booking == "" || parseFloat(days_before_start_to_allow_booking) < 0 ){
            swal.fire({title: 'Error!',text:"Please input number of days 0 (zero) ore greater then 0 (zero)"});
            return;
        }
        $.ajax({
            url: 'ajax_bookings.php?saveBookingsBufferSetting=1',
            type: 'POST',
            data: {groupid:g,days_before_start_to_allow_booking:days_before_start_to_allow_booking},
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