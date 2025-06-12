<style>
  .background0, .background100 {
    background-color: rgb(255, 233, 233) !important;
  }
  .background1,.background110,.background108, .background109 {
    background-color: none !important;
  }
  .background2{
    background-color: rgb(252, 252, 217) !important;
  }
  .manage-booking-table ul {
    padding-left: 0px;
  }
  .manage-booking-table a {
      text-decoration: underline;
  }
  .manage-booking-table .show a {
      text-decoration: none;
  }
  
</style>
<div class="table-responsive" id="eventTable">
    <table id="booking_list" class="table display manage-booking-table table-hover compact" summary="This table display the list of booking">
        <thead>
            <tr>
            <th width="20%" class="color-black" scope="col"><?= gettext("Meeting Scheduler");?></th>
            <th width="28%" class="color-black" scope="col"><?= gettext('Meeting Date & Time') ?></th>
            <th width="20%" class="color-black" scope="col"><?= gettext('Schedule Name') ?></th>
            <th width="20%" class="color-black" scope="col"><?= gettext('Support User') ?></th>
            <th width="10%" class="color-black" scope="col"><?= gettext('Status') ?></th>
            <th width="2%" class="color-black" scope="col"></th>
            </tr>
        </thead>
    </table>
</div>

<script>
  $(document).ready(function() {
      var orderBy = 0;
      var x = parseInt(localStorage.getItem("local_variable_for_table_pagination")); 
      var reloadTriggeredManually = 0;  
      var dtable = $('#booking_list').DataTable( {
          serverSide: true,
          processing: true,
          bFilter: true,
          bInfo : false,
          bDestroy: true,
          pageLength: x,
          order: [[ orderBy, "ASC" ]],
          language: {
              url: '../vendor/js/datatables-lang/i18n/<?= $_COMPANY->getDatatableLanguage($_USER->val('language')); ?>.json'
          },
          "columnDefs": [
              { "searchable": false, "targets": [0,-3,-2,-1], orderable: false }
          ],
          ajax:{
              url :"ajax_bookings.php?getBookingsList=<?= $_COMPANY->encodeId($groupid); ?>", // json datasource
              type: "POST",  // method  , by default get
              "data": function (d) {
                  if (reloadTriggeredManually) {
                      d.reloadData = 1; // Add flag indicating manual reload
                  }
              },
              error: function(data){  // error handling
                  $(".table-grid-error").html("");
                  $("#table-members-server").append('<tbody class="table-grid-error"><tr><th colspan="5"><?= gettext("No data found");?>!</th></tr></tbody>');
                  $("#table-grid_processing").css("display","none");
              },complete : function(){
                    $(".confirm").popConfirm({content: ''}); 
                    $('[data-toggle="popover"]').popover({
                        sanitize:false
                    });                   
              }
          }, 
          "stateSave": true
      });

  });

function copyBookingLink(text){
    var tempInput = $("<input>");
    $("body").append(tempInput);
    tempInput.val(text).select();
    document.execCommand("copy");
    tempInput.remove();
    swal.fire({title: '<?=gettext("Meeting link copied");?>', html: '<p style="word-wrap: break-word; overflow-wrap: break-word; font-size: small;">'+text+'</p>'});
}

function getBookingScheduleActionButton(e){
	$.ajax({
		url: 'ajax_bookings.php?getBookingScheduleActionButton=1',
        type: "GET",
		data: {eventid:e},
        success : function(data) {
			$('#dynamicBookingActionButton'+e).html(data);
			$(".confirm").popConfirm({content: ''});
		}
	});
}

async function cancelEventBooking(i,g){
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
        }
    });

    if (!isConfirmed) {
        return;
    }
    let cancellationReason = retval;
    $.ajax({
        url: 'ajax_events.php?cancelEventBooking=1',
        type: "POST",
        data: {
            'eventid': i,
            'booking_cancel_reason': cancellationReason
        },
        success: function (data) {
            try {
                let jsonData = JSON.parse(data);
                swal.fire({title:jsonData.title,text:jsonData.message}).then(function (result) {
                    if (jsonData.status == 1){    
                       window.location.reload();
                    }
                });
            } catch (e) {
                swal.fire({ title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>" });
            }
        }
    });
	
}

async function updateEventBookingResolutionData(i,g,resolution){
    const {value: retval, isConfirmed} = await Swal.fire({
        title: '<?= addslashes(gettext("Add a comment"))?>',
        input: 'textarea',
        inputPlaceholder: '<?= addslashes(gettext("Add a resolution comment..."))?>',
        inputAttributes: {
            'aria-label': '<?= addslashes(gettext("Add a resolution comment..."))?>',
            maxlength: 200
        },
        showCancelButton: true,
        cancelButtonText: '<?= addslashes(gettext("Close"))?>',
        confirmButtonText: '<?= addslashes(gettext("Submit Resolution"))?>',
        allowOutsideClick: () => false,
        inputValidator: (value) => {
            return new Promise((resolve) => {
                if (value) {
                    resolve()
                } else {
                    resolve('<?= addslashes(gettext("Add a resolution comment..."))?>')
                }
            })
        }
    });

    if (!isConfirmed) {
        return;
    }
    let completeComment = retval;

	if (completeComment) {
		$.ajax({
			url: 'ajax_events.php?updateEventBookingResolutionData=1',
			type: "POST",
			data: {
				'eventid': i,
				'schedule_completion_comment': completeComment,
                'resolution':resolution
			},
			success: function (data) {
				try {
					let jsonData = JSON.parse(data);
					swal.fire({title:jsonData.title,text:jsonData.message}).then(function (result) {
                        if (jsonData.status == 1){    
                            window.location.reload();
                        }
                    });
				} catch (e) {
					swal.fire({ title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>" });
				}
			}
		});
	}
}


function rescheduleBooking(i){
    $.ajax({
		url: 'ajax_bookings.php?rescheduleBooking=1',
        type: "GET",
		data: {'eventid':i},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$('#modal_over_modal').html(data);
                $('#booking_reschedule_model').modal({
                    backdrop: 'static',
                    keyboard: false
                });
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
			}
		}
	});
}

function reassignBooking(i){
    $.ajax({
		url: 'ajax_bookings.php?reassignBooking=1',
        type: "GET",
		data: {'eventid':i},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$('#modal_over_modal').html(data);
                $('#booking_reschedule_model').modal({
                    backdrop: 'static',
                    keyboard: false
                });
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
			}
		}
	});
}
</script>