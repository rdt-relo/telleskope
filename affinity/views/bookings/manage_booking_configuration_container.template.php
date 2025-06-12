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
			<div class="col-10">          
				<h2><?= $documentTitle = sprintf(gettext('Manage %s Configuration'),Group::GetBookingCustomName(true)).' - '. $group->val('groupname');?></h2>
			</div>		
    <hr class="lineb">
</div>
<div class="container inner-background mt-0 pt-0">
  <div class="row">
    <div class="col-md-12" style="display: none;"></div>
    <div class="col-md-12">
      <div class="col-md-12 col-xs-12">
          <div class="inner-page-title">
              <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="none"><a id="bookingSetting" role="tab" aria-selected="true" href="javascript:void(0)" class="nav-link active" onclick="manageBookingSetting('<?= $_COMPANY->encodeId($groupid) ?>')" data-toggle="tab" ><?= gettext("Settings");?></a></li>
                <li class="nav-item" role="none"><a id="bookingEmails" role="tab" aria-selected="true" href="javascript:void(0)" class="nav-link" onclick="manageBookingEmailsSetting('<?= $_COMPANY->encodeId($groupid) ?>')" data-toggle="tab" ><?= gettext("Emails");?></a></li>
              
            </ul>
          </div>
      </div>
    </div>
    <div class=" col-md-12  tab-content" id="manageTeamContent">
       
    </div>
  </div>
</div>
        

<script>
  updatePageTitle('<?= addslashes(sprintf(gettext('%1$s | %2$s'), $documentTitle, $_COMPANY->val('companyname')));?>');


  function manageBookingSetting(g) {
    $.ajax({
      url: 'ajax_bookings.php?manageBookingSetting=1',
      type: "GET",
      data: {groupid:g},
      success : function(data) {
        $('#manageTeamContent').html(data);
      }
    });
  }

  function manageBookingEmailsSetting(g) {
    $.ajax({
      url: 'ajax_bookings.php?manageBookingEmailsSetting=1',
      type: "GET",
      data: {groupid:g},
      success : function(data) {
        $('#manageTeamContent').html(data);
      }
    });
  }
 
</script>