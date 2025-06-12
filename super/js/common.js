
//-----------------COMMON JS FUNCTIONS OF TELESKOPE APP----------------------------//
//Date Picker
jQuery(function() {
        jQuery( "#start" ).datepicker({
			minDate:0,
            prevText:"click for previous months",
            nextText:"click for next months",
            showOtherMonths:true,
            selectOtherMonths: false,
			dateFormat: 'yy-mm-dd' 
        });
		
		
    });	
	
	
//start date and end date 

jQuery(function() {
	jQuery( "#start_date" ).datepicker({
		prevText:"click for previous months",
		nextText:"click for next months",
		showOtherMonths:true,
		selectOtherMonths: false,
		dateFormat: 'yy-mm-dd' 
	});
	jQuery( "#end_date" ).datepicker({
		prevText:"click for previous months",
		nextText:"click for next months",
		showOtherMonths:true,
		selectOtherMonths: true,
		dateFormat: 'yy-mm-dd' 
	});
});
	
//tooltip
jQuery(document).ready(function(){
    jQuery('[data-toggle="tooltip"]').tooltip();   
});


//Hide after 5 sec
jQuery(document).ready(function(){
    jQuery("#hidemesage").delay(10000).fadeOut(3000);   
});


jQuery(document).ready(function(){
    jQuery('[data-toggle="assignpub"]').popover();   
});

// Confirmation to Delete Company
jQuery(document).ready(function() {
	jQuery(".confirm").popConfirm({content: ''});
});

//go back
function goBack() {
    window.history.back();
}

//function for get all liker of leadergrams
function GetAllLeadergramLikes(x){
	var id = jQuery('.leaderclass'+x).attr('value');
	jQuery.ajax({
		type: 'GET',
		url:'action.php',
		data: 'leadergramid=' + id,
		success: function(data){
			jQuery("#leaderreplace").html(data);
		}
	});
}


//function for get all Upvoter of Opinions
function GetAllUpvoter(x){
	var id = jQuery('.opinionclass'+x).attr('value');
	jQuery.ajax({
		type: 'GET',
		url:'action.php',
		data: 'opinionid=' + id,
		success: function(data){
			jQuery("#opinionreplace").html(data);
		}
	});
}

//function for get all attendee of activity
function GetAllAttendee(x){
	var id = jQuery('.activityclass'+x).attr('value');
	jQuery.ajax({
		type: 'GET',
		url:'action.php',
		data: 'activityid=' + id,
		success: function(data){
			jQuery("#activityattendee").html(data);
		}
	});
}

function check(){
	 var newpassword = $("#new").val();
     var confirmPassword = $("#confirm").val();
     if (newpassword != confirmPassword){
		 $("#submit").click(function(event){
			event.preventDefault();
		});
          $("#divCheckPasswordMatch").html("Password do not match!");
		  
     }else{
		 $('#submit').unbind('click');
          $("#divCheckPasswordMatch").html("");
    }
} 

function modall(){
	jQuery('#my-modal').modal({
		backdrop: 'static',
		keyboard: true
	})
}

jQuery(document).ready(function(){ 
    jQuery('#characterLeft').text('2000 characters left');
    jQuery('#message').keyup(function () {
        var max = 2000;
        var len = jQuery(this).val().length;
        if (len >= max) {
            jQuery('#characterLeft').text('You have reached the limit');
            jQuery('#characterLeft').addClass('red');
			jQuery('input[type="submit"]').prop('disabled', true);
                      
        } else {
            var ch = max - len;
            jQuery('#characterLeft').text(ch + ' characters left');
            jQuery('input[type="submit"]').prop('disabled', false);
            jQuery('#characterLeft').removeClass('red');            
        }
    });
	
	jQuery('#reset').click(function () {
		jQuery('#characterLeft').text('2000 characters left');
        jQuery('input[type="submit"]').prop('disabled', false);
        jQuery('#characterLeft').removeClass('red');
		 
	});
});
jQuery(document).ready(function(){ 
    $(".td").each(
		function( intIndex ) {
			var textToHide = $(this).text().substring(150);
			var visibleText = $(this).text().substring(0, 150);
			var count = $(this).text().length;
			if (count >150) {
				$(this)
					.html(visibleText + ('<span>' + textToHide + '</span>'))
				
						.append('<a id="read-more" title="Read More" style="display: block; cursor: pointer;">Read More&hellip;</a>')
						.click(function() {
							$(this).find('span').toggle();
							$(this).find('a:last').toggle();
						});
					
				$(this).find("span").hide();
			}else if (count == 0 ){
				$(this)
				.html(' ---')
			}else{
				$(this)
					.html(visibleText)
			}
		}
	)

});



