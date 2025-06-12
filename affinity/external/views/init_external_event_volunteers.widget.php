<?php
/**
 * This widget is for managing volunteer role
 *  Dependencies
 * $event : Event Object
 * $eventVolunteerRequests : Array of required volunteers
 * $eventVolunteers : Array of enrolled volunteers
 */
?>
<style>
     .popover, .popover-header{
        min-width:320px;
    }
</style>
<div class="col-md-12" >
    <p class="alert alert-info mt-2 py-0">
        
        <span class="btn text-decoration-none"><?= count($eventVolunteerRequests) > 1 ? gettext("Would you like to volunteer for one of these roles?") : gettext("Would you like to volunteer for this role?");?></span>

        <br/>
        <span class="btn text-decoration-none"><?=gettext("Volunteer as ");?></span>
        <?php 
            $externalUserid = 0;
        foreach ($eventVolunteerRequests as $key => $volunteer) { 
            $volunteerCount = $event->getVolunteerCountByType($volunteer['volunteertypeid']);
            $volunteerRequestCount = $volunteer['volunteer_needed_count'];
            $volunteerStatus = $event->isExternalEventVolunteerSignup($external_user['email'], $volunteer['volunteertypeid']);
            $disabled = false;
            $volunteerHoverText = null;
            $volunteerNeededCountText =null;
            $showLeaveRole = 0;
            if ($volunteerStatus){
                $volunteerHoverText = gettext("You already signed up for this role");
                $disabled = true;
                $showLeaveRole = 1;
            } else {
                $volunteerHoverText = gettext("Click on the link to Volunteer");
            }
            if ($volunteerCount >= $volunteerRequestCount && !$volunteerStatus){
                $volunteerHoverText = gettext("Volunteer request capacity has been met");
                $disabled = true;
            }
            $volunteerSeatsLeftText = sprintf(gettext('%s out of %s roles available!'),($volunteerRequestCount - $volunteerCount),$volunteerRequestCount);

        ?>
        <button id="tooltip_info"
        <?php
        $v = "";
        $count = 0;
        if(!empty($eventVolunteers)){
            foreach($eventVolunteers as $ev){
                $leaveEnrollBtn = "";
                $other_data = $ev['other_data'] ? json_decode($ev['other_data'],true) : array();
                if ($showLeaveRole && in_array($external_user['email'],$other_data)){
                    $leaveEnrollBtn = "<span class='link-pointer link-affinity pull-right' onclick=leaveExternalEventVolunteerEnrollment('".$_COMPANY->encodeId($event->id())."')>".gettext("Decline")."</span>";
                }
                if ($ev['volunteertypeid'] == $volunteer['volunteertypeid']){
                    $volunteerName = $ev['firstname'].' '.$ev['lastname'];
                    if (!$ev['userid']){
                        $volunteerName = $other_data['firstname'].' '.$other_data['lastname'].' (External)';
                    }

                    $v .= "<li>";
                    $v .= $volunteerName . $leaveEnrollBtn;
                    $v .= "</li>";
                    $count++;
                }
            }
        }
        if ($count){
            $v = "<div>".gettext("Volunteer List")."<br>".$v."</div>";
        }
        if($disabled){  ?>
            class="btn btn-link readonly"
            data-toggle="popover"
            data-trigger="click focus"
            data-html="true"
            data-content="<?= $v; ?>"
            class="btn btn-link confirm"
            title='<?= $volunteerHoverText;?><button style="margin-top: -7px;" onclick=$(this).closest("div.popover").popover("hide")  type="button" class="close" aria-hidden="true">&times;</button><p class="pt-2"> <small> <?= $volunteerSeatsLeftText; ?></small></p>';
        <?php 	} else{  ?>
            class="btn pop-identifier btn-link confirm"
            data-toggle="popover"
            data-trigger="hover"
            data-html="false"
            data-confirm-noBtn="<?= gettext('No') ?>"
            data-confirm-yesBtn="<?= gettext('Yes') ?>"
            data-confirm-title="<?= gettext("Are you sure you want to sign up for this role? If you've already signed up for any other Volunteering Roles for this event, you will be removed from those roles and added to this one.");?><p class='pt-2'> <small> <?= $volunteerSeatsLeftText; ?></small></p>";
            onclick="joinExternalEventAsEventVolunteer('<?= $_COMPANY->encodeId($event->id()) ?>','<?= $_COMPANY->encodeId($volunteer['volunteertypeid']) ?>')"
        <?php 	} ?>
        >
       
        <?= htmlspecialchars($event->getVolunteerTypeValue($volunteer['volunteertypeid'])); ?>
        </button>
        <?php } ?>

        <?php if($userRsvpStatus == 0){ ?>
        <br>
        <span class="btn text-decoration-none" style="font-size: smaller;"> <?=gettext("Note: If you sign up for a role, your RSVP for this event will be automatically added.");?> </span>
        <?php } ?>
    </p>
</div>


<script>
    $(document).ready(function(){	
       $(function () {
			$('[data-toggle="popover"]').popover({html:true, placement: "top",sanitize : false,container: 'body'});  
		})
	});

    function joinExternalEventAsEventVolunteer(e,i){
        $.ajax({
            url: 'ajax.php?joinExternalEventAsEventVolunteer=1',
            type: "GET",
            data:{'eventid':e,'volunteertypeid':i},
            success : function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title, text: jsonData.message}) .then(function(result) {
                        location.reload();
                    })
                } catch(e) {}
            }
        });
    }
    function leaveExternalEventVolunteerEnrollment(e){
        $.ajax({
            url: 'ajax.php?leaveExternalEventVolunteerEnrollment=1',
            type: "POST",
            data:{'eventid':e},
            success : function(data) {	
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title, text: jsonData.message}) .then(function(result) {
                        location.reload();
                    })
                } catch(e) {}
            }
        });
    }
</script>
<script>
// function to add "ESC key" exit on popover of tooltip
$(document).keyup(function (event) {
    if (event.which === 27) {
        $('#tooltip_info').popover('hide');
    }
});

$('body').on('click', function (e) {
    $('[data-toggle=popover]').each(function () {
        // hide any open popovers when the anywhere else in the body is clicked
        if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
            $(this).popover('hide');
        }
    });
});
</script>