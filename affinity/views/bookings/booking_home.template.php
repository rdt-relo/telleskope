<style>
    .innar-page-right-button {
    padding-top: 17px;
}
.booking-heading{
    float:left;
}
</style>
<!-- Start of Bookings section Section -->
<div class="container inner-background">
    <div class="row row-no-gutters w-100">
        <div class="col-md-12">
            <div class="col-md-12">
                <div class="inner-page-title">
                    <h1>
                        <span><?php echo $documentTitle =  Group::GetBookingCustomName(true) . ' - ' . $group->val('groupname'); ?></span>
                    </h1>
                </div>
            </div>
        </div>
        <hr class="lineb" >
        <div class="col-12 px-sm-0 mt-3 mb-4">
            <ul class="nav nav-tabs" role="tablist">
            
                <li role="none" class="nav-item"><a tabindex="0" role="tab" aria-selected="true" class="nav-link inner-page-nav-link active" data-id="1" href="javascript:void(0)" id="myEvents" onclick="getMyBookings('<?= $_COMPANY->encodeId($groupid) ?>')" data-toggle="tab" ><?= gettext('My Bookings'); ?></a></li>
            <?php if ($_USER->canManageSupportGroup($groupid)){ ?>
                <li role="none" class="nav-item"><a tabindex="-1" role="tab" aria-selected="false" data-toggle="tab" class="nav-link inner-page-nav-link" href="javascript:void(0)" id="pastevents" onclick="getReceivedBookings('<?= $_COMPANY->encodeId($groupid) ?>')"><?= gettext('Received Bookings'); ?></a></li>
            <?php } ?>
            <li role="none" class="nav-item"><a tabindex="0" role="tab" aria-selected="true" class="nav-link inner-page-nav-link" data-id="1" href="javascript:void(0)" id="myEvents" onclick="newSupportBookingForm('<?= $_COMPANY->encodeId($groupid) ?>')" data-toggle="tab" ><?= gettext('Schedule Meeting') ?></a></li>
            </ul>
        </div>
        <div class="col-md-12  tab-content" id="loadeBookingRows">
        <?php
            include(__DIR__ . "/booking_rows.template.php");
        ?>
        </div>
    </div>
</div>

<script>
    $('[data-toggle="tooltip"]').tooltip(); 
    updatePageTitle('<?= addslashes(sprintf(gettext('%1$s | %2$s'), $documentTitle, $_COMPANY->val('companyname')));?>');
    
</script>

<!-- End of Bookings section Section -->
