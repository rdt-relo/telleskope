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
     .popover, .popover-header {
        min-width:320px !important;
    }
   
    .volunteer-list {
        margin-left: 0px;
        padding-left: 14px;
    }
    .volunteer-list li {	
        text-align: left;
    }
</style>
<div class="col-md-12" >
    <h5 class="py-2 "><?= gettext('Event volunteer options');?></h5>
    <div class="alert alert-info py-0 <?= $lockOptions ? 'locked-container' : ''; ?>">
    <?php if($lockOptions){ ?>
        <div class="locked-container-overlay" z-index='1'><span class="locked-text"><i class="fa fa-lock" style="color:white;" aria-hidden="true"></i> <?= $lockMessage; ?></span></div>
    <?php } ?>
        <span class="btn text-decoration-none"><?= count($eventVolunteerRequests) > 1 ? gettext("Would you like to volunteer for one of these roles?") : gettext("Would you like to volunteer for this role?");?></span>

        <br/>
        <span class="btn text-decoration-none"><?=gettext("Volunteer as ");?></span>
        <?php $c=0; foreach ($eventVolunteerRequests as $key => $volunteer) { 

            if (isset($volunteer['hide_from_signup_page']) && $volunteer['hide_from_signup_page'] == 1) {
                continue;
            }

            $volunteerCount = $event->getVolunteerCountByType($volunteer['volunteertypeid']);
            $volunteerRequestCount = $volunteer['volunteer_needed_count'];
            $volunteerStatus = $event->isEventVolunteerSignup($_USER->id(), $volunteer['volunteertypeid']);
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
                if ($showLeaveRole && $ev['userid'] == $_USER->id()){
                    $leaveEnrollBtn = "<button tabindex='0' class='link-pointer link-affinity btn-no-style pull-right decline-btn-popover' onclick=leaveEventVolunteerEnrollment('".$_COMPANY->encodeId($event->id())."','volunteer-btn-".$c."')>".gettext("Decline")."</button>";
                }
                if ($ev['volunteertypeid'] == $volunteer['volunteertypeid']){
                    $volunteer_obj = EventVolunteer::Hydrate($ev['volunteerid'], $ev);
                    $v .= "<ul class='volunteer-list'><li>";
                    $volunteer_name = ($volunteer_obj->getFirstName()).' '.($volunteer_obj->getLastName()). ($volunteer_obj->isExternalVolunteer() ? ('(' . gettext('External') . ')') : '');
                    $v .= $volunteer_name . $leaveEnrollBtn;
                    $v .= "</li></ul>";
                    $count++;
                }
            }
        }
        if ($count){
            $v = "<div>".gettext("Volunteer List")."<br>".$v."</div>";
        }
        if($disabled){  ?>            
            data-toggle="popover"
            data-trigger="click focus"
            data-html="true"
            data-content="<?= $v; ?>"
            class="btn btn-link confirm volunteer-confirm volunteer-btn-<?=$c;?>"
            title='<p class="col-11 m-0 p-0"><?= $volunteerHoverText;?></p><button tabindex="0" onclick=$(this).closest("div.popover").popover("hide")  type="button" class="close confirm-dialog-btn-abort col-1 p-0 m-0" aria-hidden="true">&times;</button><p class="col-12 m-0 p-0"> <small> <?= $volunteerSeatsLeftText; ?></small></p>';
        <?php 	} else{  ?>
            class="btn btn-link volunteer-btn-<?=$c;?>"
            onclick="confirmVolunteerSignup('<?= $_COMPANY->encodeId($event->id()) ?>','<?= $_COMPANY->encodeId($volunteer['volunteertypeid']) ?>','volunteer-btn-<?= $c ?>')"
        <?php 	} ?>
        >
       
        <?= htmlspecialchars($event->getVolunteerTypeValue($volunteer['volunteertypeid'])); ?>
        <?php if ($showLeaveRole) { ?>
            <sup><i aria-label="Joined" class="fa fa-check" role="img" style="color:green;"></i></sup>
        <?php } ?>
        </button>

        <?php $c++; } ?>

        <?php if($event->getMyRsvpStatus() == 0){ ?>
        <br>
        <span class="btn text-decoration-none" style="font-size: smaller;"> <?=gettext("Note: If you sign up for a role, your RSVP for this event will be automatically added.");?> </span>
        <?php } ?>

        <?php if (count($event->getExternalVolunteerRoles())) { ?>
          <hr>
          <span class="btn text-decoration-none">
            <?= gettext('Add someone you know as a volunteer?') ?>
          </span>
          <button class="btn btn-link volunteer-btn-0" onclick="window.tskp.event_volunteer.openExternalEventVolunteerModal(event)" data-eventid="<?= $_COMPANY->encodeId($event->id()) ?>">
            <?= gettext('Click Here') ?>
          </button>
        <?php } ?>
    </div>
</div>


<script>
    $(document).ready(function() {
        // Select all elements inside the div with the class 'locked-container'
        $('.locked-container, .locked-container *').attr('tabindex', '-1');
    });
    
    $(document).ready(function(){	
       $(function () {
			$('[data-toggle="popover"]').popover({html:true, placement: "top",sanitize : false,container: 'body'});  
		})
	});
    function confirmVolunteerSignup(e,i,volunteerButtonClass){
        $.ajax({
            url: 'ajax_events.php?confirmVolunteerSignup=1',
            type: "GET",
            data:{'eventid':e,'volunteertypeid':i},
            success : function(data) {
                    let jsonData = JSON.parse(data);
                    swal.fire({
                        title: jsonData.title,
                        html:jsonData.message,
                        width:'40em',
                        allowOutsideClick:false,
                        showCancelButton: true,
                        confirmButtonText: 'Yes',
                        cancelButtonText: 'No',
                    }).then(function(result) {
                        if(result.value){
                            joinAsEventVolunteer(e,i,volunteerButtonClass)
                        }
                    });
                    $(".swal2-confirm").focus();
            }
        });
    }
    function joinAsEventVolunteer(e,i,volunteerButtonClass){
        $.ajax({
            url: 'ajax_events.php?joinAsEventVolunteer=1',
            type: "GET",
            data:{'eventid':e,'volunteertypeid':i},
            success : function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title, text: jsonData.message})
                } catch(e) {
                    $("[data-toggle='popover']").popover('hide');
                    swal.fire({title: "<?= gettext('Success');?>",text:"<?= gettext('Event Volunteer assigned successfully')?>"})
                        .then(function(result) {
                            $('#manage_volunteer_enrollment').html(data);
                            $(".confirm").popConfirm({content: ''});
                            refreshEventRSVPWidget('<?= $_COMPANY->encodeId($eventid);?>');
                            setTimeout(() => {
                            $('.'+volunteerButtonClass).focus();  
                        }, 300)                         
                    });
                }
                setTimeout(() => {
                    $(".swal2-confirm").focus();
                }, 500)
            }
        });
    }
    function leaveEventVolunteerEnrollment(e,c){
        $.ajax({
            url: 'ajax_events.php?leaveEventVolunteerEnrollment=1',
            type: "POST",
            data:{'eventid':e},
            success : function(data) {	
                var json = {};
                try {
                    var json = JSON.parse(data);
                    if (json.status === 0) {
                        return Swal.fire({
                          title: json.title,
                          text: json.message
                        });
                    }
                } catch (e) {}

                $("[data-toggle='popover']").popover('hide');
                swal.fire({title: "<?= gettext('Success')?>",text:"<?= gettext('Volunteer role declined successfully')?>"})
                    .then(function(result) {
                        $('#manage_volunteer_enrollment').html(data);
                        $(".confirm").popConfirm({content: ''});
                        setTimeout(() => {
                            $('.'+c).focus();  
                        }, 500)
                    });
                setTimeout(() => {
                    $(".swal2-confirm").focus();
                }, 500)
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

$(document).on('show.bs.popover', function() { 
    setTimeout(() => {
        $('.confirm-dialog-btn-abort').focus(); 
}, 100);    
});

</script>