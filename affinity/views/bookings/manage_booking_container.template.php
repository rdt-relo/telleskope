
 <style>
  .nav-link {
	cursor: pointer;
}
.col-md-12{
  float: none;
}
.dynamic-button-container .pull-right .join-request-rep{
    margin-top: -108px;
}
</style>
<div class="col-md-12">
	<div class="row">
			<div class="col-12">          
				<h2><?= $documentTitle = sprintf(gettext('Manage %s'),Group::GetBookingCustomName(true)).' - '. $group->val('groupname');?></h2>
			</div>		
    <hr class="lineb">
    <?php  include(__DIR__ . "/../../views/templates/manage_section_dynamic_button.html"); ?>
</div>

  <div class="row">
    <div class="col-12">
      <?php
        include(__DIR__ . "/manage_booking_table_listing.template.php");
      ?>
    </div>
  </div>
  
</div>
        

<script>
  updatePageTitle('<?= addslashes(sprintf(gettext('%1$s | %2$s'), $documentTitle, $_COMPANY->val('companyname')));?>');

 function scheduleOtherSupportBookingForm(gid){
    $.ajax({
      url: 'ajax_bookings.php?newSupportBookingForm=1',
          type: "GET",
      data: {'groupid':gid,'section':'schedule_new_booking'},
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