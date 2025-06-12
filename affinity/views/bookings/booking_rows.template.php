<div class="col-12">

<?php if(!empty($bookingRequests)){ ?>
    <?php foreach($bookingRequests as $booking){ 
        $event = Event::GetEvent($booking['eventid']);
        $startDateTime = new DateTime($booking['start'], new DateTimeZone($timezone));
        $endDateTime = new DateTime($booking['end'], new DateTimeZone($timezone));
        $joiners = explode(',',$booking['joiners']);

        $date = $db->covertUTCtoLocalAdvance("M j, Y","",  $booking['start'],$_SESSION['timezone'],$_USER->val('language'));
        $startTime = $db->covertUTCtoLocalAdvance("g:i a","",  $booking['start'],$_SESSION['timezone'],$_USER->val('language'));
        $endTime = $db->covertUTCtoLocalAdvance("g:i a T","",  $booking['end'],$_SESSION['timezone'],$_USER->val('language'));
        $bookingStatus = strtoupper($event->getBookingStatus()['resolution']);
        $bookingStatusBadge = array(
                'COMPLETE' => 'badge-success',
                'CANCELED' => 'badge-danger',
                'NOSHOW' => 'badge-warning',
                'SCHEDULED' => 'badge-info',
        )[$bookingStatus];
    ?>
        <div class="col-6 mb-3">
            <div class="card w-100 shadow-sm pb-0">
                <div class="card-body">
                    <h5 class="card-title">
                        <button class="btn btn-link text-primary font-weight-bold p-0" 
                                onclick="getBookingDetail('<?= $_COMPANY->encodeId($booking['eventid']); ?>')">
                            <?= $booking['eventtitle']; ?>
                        </button>
                    </h5>
                    <hr>
                    <p class="card-text text-left">
                        <i class="fas fa-calendar-alt text-secondary"></i> 
                        <strong><?= gettext('Date'); ?>:</strong> <?= $date; ?>
                    </p>
                    <p class="card-text text-left">
                        <i class="fas fa-clock text-secondary"></i> 
                        <strong><?= gettext('Time'); ?>:</strong> <?= $startTime; ?> - <?= $endTime; ?>
                    </p>
                    <p class="card-text text-left">
                        <i class="fas fa-check-circle text-secondary"></i>
                        <strong><?= gettext('Status'); ?>:</strong> <span class="badge <?=$bookingStatusBadge ?>"><?= $bookingStatus ?></span>
                    </p>
                    <p class="card-text text-left">
                        <i class="fas fa-user text-secondary"></i> 
                        <strong><?= gettext('Participants'); ?>:</strong> 
                        <ul class="ml-3">
                    <?php
                        foreach($joiners as $joiner) {
                            $supportExecutive = User::GetUser($joiner);
                    ?>
                      <li>  <?=  $supportExecutive->getFullName(); ?> <small>(<?= $supportExecutive->val('email'); ?>)</small></li>
                    <?php
                        }
                    ?>
                    </ul>
                    </p>
                   
                </div>
                <div class="card-footer">
                    <?php if($event->isCancelled()){ ?>
                        <button class="btn btn-affinity-gray" disabled ><?= gettext('Canceled');?></button>

                    <?php } else { ?>
                        <?php if($event->hasEnded()){ ?>
                            <button class="btn btn-affinity-gray" disabled ><?= gettext('Expired');?></button>
                        <?php } else { ?>
                            <button class="btn btn-affinity" onclick="cancelBooking('<?= $_COMPANY->encodeId($booking['groupid']); ?>','<?= $_COMPANY->encodeId($booking['eventid']); ?>', <?= $event->isPublished(); ?>,<?= $event->hasEnded(); ?>)">
                                <?= gettext('Cancel Booking'); ?>
                            </button>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } ?>
<?php } else { ?>

    <div class="container w6">
        <div class="col-md-12 bottom-sp">
            <br/>           
            <p style="text-align:center;margin-top:-40px;"><?= gettext("There are no booking to display."); ?></p>
            <p style="text-align:center;margin-top:-40px"><img src="../image/nodata/calendar.png" alt="No booking to display placeholder image" height="200px;"/></p>
        </div>
    </div>

<?php } ?>

</div>

<script>

async function cancelBooking(gid,eid,ispublished,has_ended){
	let cancellationReason;
	let sendCancellationEmails = false;
	let checkboxHtml = '';
	let checkboxChecked = false;
	let checkboxDisabled = false;

	if (ispublished && has_ended) {
		checkboxChecked = false;
	} else if (ispublished && !has_ended) {
		checkboxChecked = true;
		checkboxDisabled = true; 
	}

	checkboxHtml = `
        <div class="mt-3 text-left form-check">
		<input class="form-check-input" type="checkbox" id="sendCancellationEmails" ${checkboxChecked ? 'checked' : ''} ${checkboxDisabled ? 'disabled' : ''} />
		<label class="form-check-label" for="sendCancellationEmails"><?= addslashes(gettext("Send cancellation emails"))?></label>
        </div>
	`;

	if (ispublished) {
        const {value: retval, isConfirmed} = await Swal.fire({
            title: '<?= addslashes(gettext("Booking cancellation reason"))?>',
            input: 'textarea',
            inputPlaceholder: '<?= addslashes(gettext("Enter booking cancellation reason"))?>',
            inputAttributes: {
                'aria-label': '<?= addslashes(gettext("Enter booking cancellation reason"))?>',
                maxlength: 200
            },
            showCancelButton: true,
            cancelButtonText: '<?= addslashes(gettext("Close"))?>',
            confirmButtonText: '<?= addslashes(gettext("Cancel Booking"))?>',
            allowOutsideClick: () => false,
            inputValidator: (value) => {
                return new Promise((resolve) => {
                    if (value) {
                        resolve()
                    } else {
                        resolve('<?= addslashes(gettext("Please enter booking cancellation reason"))?>')
                    }
                })
            },
            html: checkboxHtml,
            preConfirm: () => {
                sendCancellationEmails = document.getElementById('sendCancellationEmails').checked;
            }
        });

        if (!isConfirmed) {
            return;
        }
        cancellationReason = retval;
    }

	if (!ispublished || cancellationReason) {
		$.ajax({
			url: 'ajax_bookings.php?deleteBooking=1',
			type: "POST",
			data: {
				'eventid': eid,
				'event_cancel_reason': cancellationReason,
				'sendCancellationEmails': sendCancellationEmails
			},
			success: function (data) {
				try {
					let jsonData = JSON.parse(data);
					swal.fire({title:jsonData.title,text:jsonData.message}).then( function(result) {
                        getMyBookings(gid)
                    });
				} catch (e) {
					swal.fire({ title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>" });
				}
			}
		});
	}
}

function getBookingDetail(eid){
	this.closeAllActiveModal();
	$.ajax({
		url: 'ajax_bookings.php?getBookingDetail=1',
        type: "GET",
		data: {'eventid':eid},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$('#loadAnyModal').html(data);
				$('#booking_detail_modal').modal({
					backdrop: 'static',
					keyboard: false
				});
				$(".confirm").popConfirm({content: ''});
				$('.initial').initial({
					charCount: 2,
					textColor: '#ffffff',
                    color: window.tskp?.initial_bgcolor ?? null,
					seed: 0,
					height: 50,
					width: 50,
					fontSize: 20,
					fontWeight: 300,
					fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
					radius: 0
				});

				$(".modal").removeAttr('aria-modal');
			}
		}
	});
}

</script>