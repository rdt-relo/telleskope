jQuery(function() {
	let todayDate = new Date();
	jQuery( "#start_date" ).datepicker({
		prevText:"click for previous months",
		nextText:"click for next months",
		showOtherMonths:true,
		selectOtherMonths: false,
		minDate: todayDate,
		dateFormat: 'yy-mm-dd',
		onSelect: function (date) {
			let date2 = $('#start_date').datepicker('getDate');
			$('#end_date').datepicker('option', 'minDate', date2);
		}
	});
	jQuery( "#end_date" ).datepicker({
		prevText:"click for previous months",
		nextText:"click for next months",
		showOtherMonths:true,
		selectOtherMonths: true,
		dateFormat: 'yy-mm-dd' 
	});
});
var delayAjax = (function(){
	var timer = 0;
	return function(callback, ms){
	clearTimeout (timer);
	timer = setTimeout(callback, ms);
	};
})();

function preventMultiClick(action){
	
	if (action == 1) {
		$('.prevent-multi-clicks').prop('disabled', true);
	} else {
		$('.prevent-multi-clicks').prop('disabled', false);
	}
}

function stripTags(html) {
	return html.replace(/(<([^>]+)>)|(&nbsp;)/gi, "");
}

function addUpdateTeamContent(g){
	let errors = "";
	if(!$("#tasktitle").val().trim()){
		errors += 'Title, ';
	}
	let task_type = $("#task_type").val();
	if (task_type == 'todo'){
		if(!$("#assignedto").val()){
			errors += 'Completed by, ';
		}
	} else if(task_type == 'touchpoint') {
		if(!$("#start_date").val()){
			errors += 'Due date, ';
		}
	} else if(task_type == 'feedback'){
		if(!$("#assignedto").val()){
			errors += 'For , ';
		}
		if(!$("#description").val().trim()){
			errors += 'Feedback, ';
		}
	}

	errors = errors.replace(/, $/, ' ');

	if (errors){
		errors += " input field can't be empty.";
		swal.fire({title: 'Error!',text:errors});
	} else {

		if (task_type == 'feedback'){
			processAddUpdateTeamContent(g,task_type,1)
		} else {
			(async () => {
				const { value: checked } = await Swal.fire({
					title: 'Email notification confirmation',
					showClass: {
						popup: 'swal2-text-custom-class'
					},
					inputPlaceholder: 'Check the box to send email notification',
					input: 'checkbox',
					inputValue: 0,
					confirmButtonText: 'Continue',			
					allowOutsideClick: false
				})
				let sendEmail = 0;
				if (typeof checked !== 'undefined' &&  checked == '1') {
					sendEmail = 1;
				}
				
				processAddUpdateTeamContent(g,task_type,sendEmail);
				
			})();
		}
	}
}

function processAddUpdateTeamContent(g,task_type,sendEmail) {
	let formdata = $('#todoForm')[0];
	let finaldata  = new FormData(formdata);
	finaldata.append("groupid",g);
	finaldata.append("sendEmail",sendEmail);
	
	$.ajax({
		url: 'ajax_native.php?addUpdateTeamContent=1',
		type: 'POST',
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false});
			} catch(e) { 
				if(data){
					swal.fire({title: 'Success',text:data}) .then(function(result) {
						window.location.href= 'success_callback.php';
					});
				} else {
					swal.fire({title: 'Error',text:"Something went wrong. Please try again."});
				}
				setTimeout(() => {
					document.querySelector(".swal2-confirm").focus();   
				}, 500)	
			}		
		}
	});
}

function changeEventAttendenceType(i){
	if (i==1){
		$("#venue_div").show();
		$("#conference_div").hide();
		$("#eventDescriptionNote").hide()
		$("#web_conference_detail_note").hide()
		$("#enable_checkin_part").show().css('display', 'none');
	} else if (i==2 ){
		$("#venue_div").hide();
		$("#conference_div").show();
		$("#eventDescriptionNote").show();
		$("#web_conference_detail_note").show();
		$("#enable_checkin_part").show().css('display', 'inline-block');
	} else if (i==3) {
		$("#venue_div").show();
		$("#conference_div").show();
		$("#eventDescriptionNote").show();
		$("#web_conference_detail_note").show();
		$("#enable_checkin_part").show().css('display', 'inline-block');
	} else if (i==4){
		$("#venue_div").hide();
		$("#conference_div").hide();
		$("#eventDescriptionNote").hide();
		$("#web_conference_detail_note").hide()
		$("#enable_checkin_part").show().css('display', 'none');
	}
	setMaxParticipationState();
}

function setMaxParticipationState() {
	let eventType = $("#event_attendence_type").val();
	$("#participation_limit_div").show().css('display', ((eventType == 1) || (eventType == 2) ||  (eventType == 3))? 'inline-block' : 'none');
	$("#max_inperson").prop("disabled", ((eventType == 1) || (eventType == 3))? false : true);
	$("#max_inperson_waitlist").prop("disabled", ((eventType == 1) || (eventType == 3))? false : true);
	$("#max_online").prop("disabled", ((eventType == 2) || (eventType == 3))? false : true);
	$("#max_online_waitlist").prop("disabled", ((eventType == 2) || (eventType == 3))? false : true);
}

// Show timezone picker
function showTzPicker(){
	$("#tz_div").toggle();
}

// Select timezone
function selectedTimeZone(stz){
	let tz = $("#selected_tz").val();
	if (tz !=""){
		let tt = $("#selected_tz option:selected").text();
		$("#tz_show").html(tt);
		$("#tz_input").val(tz);
	}
	$("#tz_div").hide();
	$("#tz_show").focus();
}


function saveEventPreJoinSurveyResponse(eventid, joinStatus, trigger, eventSurveyResponse) {
	$.ajax({
		url: 'ajax_native.php?joinAndSaveEventSurveyData=1',
		type: "POST",
		data: {
			'eventid':eventid,
			'joinStatus':joinStatus,
			'eventSurveyResponse':eventSurveyResponse,
			'trigger':trigger
		},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message})
				.then(function(result) {
					window.location.href= 'success_callback.php';
				});
			} catch(e) {
				window.location.href= 'success_callback.php';
			}
		}
	});
}
