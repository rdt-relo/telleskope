<?php
Lang::Init($_GET['lang'] ?? null);
header('Content-Type: application/javascript');
if (!Env::IsLocalEnv()) {
  header('Cache-Control: public, max-age=604800');
}
?>

var __globalIntervalVariable = null;
var __autosaveNewsletterInterval = null;
var __methodAllowedForInterval = ["checkPublishStatus"];
var __previousActiveElement = null;
var __previousActiveElementProfile = null;
var isPastRequestsRendered = 0;
// Delay Ajax Call
var delayAjax = (function(){
	var timer = 0;
	return function(callback, ms){
	clearTimeout (timer);
	timer = setTimeout(callback, ms);
	};
})();

//start date and end date
jQuery(document).ready(function() {
	jQuery(".confirm").popConfirm({content: ''});
	
	jQuery("#group_id_in").click(function(){
		$("#email_in").val(""); 
	});
	
	jQuery("#email_in").click(function(){
		$("#group_id_in").val(""); 
	});
});

// Set last focus to last active buttion if press No button.
$(document).on('keypress','.dropdown-toggle', function(){		
	$(this).addClass('focus-active-btn');	
});
$(document).on('keypress','.confirm-dialog-btn-abort', function(){  
	  $('.focus-active-btn').focus();
	  $('.focus-active-btn').removeClass('focus-active-btn'); 
}); // Set last focus to last active buttion if press No button.


// Set focus for popconfirm yes/no button.
$(document).on('show.bs.popover', function(e) { 
    setTimeout(() => {
		$('p.button-group').removeAttr('tabindex');
        $('.confirm-dialog-btn-abort').focus(); 
	}, 100);
});

// Stop focus within popover
$(document).on('keydown','.confirm-dialog-btn-abort',function(e)  {    
    if (e.keyCode==9) {
        setTimeout(function(){
            $('.confirm-dialog-btn-confirm').focus();
		}, 100);
       
    }
})
$(document).on('keydown','.confirm-dialog-btn-confirm',function(e)  {    
    if (e.keyCode==9) {
        e.preventDefault();        
    }else if(e.keyCode==16){
        setTimeout(function(){
            $('.confirm-dialog-btn-abort').focus();
		}, 400);
    }
})
// End Stop focus within popover.

// Append form
$(document).ready(function() {

    var max_fields      = 10; //maximum input boxes allowed
    var wrapper         = $(".input_fields_wrap"); //Fields wrapper
    var add_button      = $(".add_field_button"); //Add button ID
   
    var x = 1; //initlal text box count
    $(add_button).click(function(e){ //on add input button click
        e.preventDefault();
        if(x < max_fields){ //max input box allowed
            x++; //text box increment
            $(wrapper).append('<div><input type="text" name="mytext[]"/><a href="#" class="remove_field">Remove</a></div>'); //add input box
        }
    });
   
    $(wrapper).on("click",".remove_field", function(e){ //user click on remove text
        e.preventDefault(); $(this).parent('div').remove(); x--;
    });
	
});



//start date and end date 

jQuery(function() {
	jQuery( "#start_date" ).datepicker({
		prevText:"click for previous months",
		nextText:"click for next months",
		showOtherMonths:true,
		selectOtherMonths: false,
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

$(document).on('keydown', '#start_date',    function(e) {
	$.datepicker.customKeyPress(e);
});
$(document).on('keydown', '#end_date',    function(e) {
	$.datepicker.customKeyPress(e);
});
$.extend($.datepicker, {
	customKeyPress: function (event) {
		let inst = $.datepicker._getInst(event.target);
		let isRTL = inst.dpDiv.is(".ui-datepicker-rtl");
		switch (event.keyCode) {
			case 37:    // LEFT --> -1 day
				$('body').css('overflow','hidden');
			$.datepicker._adjustDate(event.target, (isRTL ? +1 : -1), "D");
			break;
		// case 16:    // UPP --> -7 day
		// 	$('body').css('overflow','hidden');
		// 	$.datepicker._adjustDate(event.target, -7, "D");
		// 	break;
		case 38:    // UPP --> -7 day
			$('body').css('overflow','hidden');
			$.datepicker._adjustDate(event.target, -7, "D");
			break;
		case 39:    // RIGHT --> +1 day
			$('body').css('overflow','hidden');
			$.datepicker._adjustDate(event.target, (isRTL ? -1 : +1), "D");
			break;
		case 40:    // DOWN --> +7 day
			$('body').css('overflow','hidden');
			$.datepicker._adjustDate(event.target, +7, "D");
			break;
		}
		$('body').css('overflow','hidden');
	}
});

/*--------------Date Picker Functions Start -----------------*/	
    function openDatepicker() {
        $('body').addClass('datepicker-open');
    }

    function closeDatepicker() {
        $('body').removeClass('datepicker-open');
    }
    function customKeyPress(event) {      
        let inst = $.datepicker._getInst(event.target);
        let isRTL = inst.dpDiv.is(".ui-datepicker-rtl");

        switch (event.keyCode) {
            case 37:    // LEFT --> -1 day
                $('body').css('overflow','hidden');
                $.datepicker._adjustDate(event.target, (isRTL ? +1 : -1), "D");
                break;
            // case 16:    // UPP --> -7 day
            //     $('body').css('overflow','hidden');
            //     $.datepicker._adjustDate(event.target, -7, "D");
            //     break;
            case 38:    // UPP --> -7 day
                $('body').css('overflow','hidden');
                $.datepicker._adjustDate(event.target, -7, "D");
                break;
            case 39:    // RIGHT --> +1 day
                $('body').css('overflow','hidden');
                $.datepicker._adjustDate(event.target, (isRTL ? -1 : +1), "D");
                break;
            case 40:    // DOWN --> +7 day
                $('body').css('overflow','hidden');
                $.datepicker._adjustDate(event.target, +7, "D");
                break;
        }
        $('body').css('overflow','hidden');
    }

    function generateErrorMessage(inputValue, identifier) {
        return `You entered ${inputValue} as the ${identifier}. This is an invalid date. The date should be in the format YYYY-MM-DD and greater than or equal to todays date.`;
    }

	

	function setErrorAndAriaLive(inputElement, errorMessage) {
		const errorElement = inputElement.nextElementSibling;
		if (errorElement) {
			errorElement.textContent = errorMessage;
			errorElement.setAttribute('aria-live', 'assertive');
		}
	}

	function clearErrorAndAriaLive(inputElement) {
		const errorElement = inputElement.nextElementSibling;
		if (errorElement) {
			errorElement.textContent = '';
			errorElement.removeAttribute('aria-live');
		}
	}

	// Date input validation
	function validateDateInput(inputElement) {
		let inputValue = inputElement.value.trim();
		let errorElement = inputElement.nextElementSibling;

		if (!inputValue) {
			return;
		}

		// Reformat YYYYMMDD to YYYY-MM-DD if applicable
		if (/^\d{8}$/.test(inputValue)) {
			inputValue = inputValue.replace(/^(\d{4})(\d{2})(\d{2})$/, '$1-$2-$3');
			inputElement.value = inputValue;
		}

		if (/^\d{4}-\d{2}-\d{2}$/.test(inputValue)) { // Validate format
			// Valid format, check if it's a valid date
			let parts = inputValue.split('-');
			let year = parseInt(parts[0], 10);
			let month = parseInt(parts[1], 10);
			let day = parseInt(parts[2], 10);
			let inputDate = new Date(year, month - 1, day);
			if (!isNaN(inputDate) && inputDate.getFullYear() === year && inputDate.getMonth() === month - 1 && inputDate.getDate() === day) {

				let inputDateAsInt = inputDate.toISOString().slice(0,10).replaceAll('-','');
				let yesterdaysDateAsInt = new Date(Date.now() - 86400000).toISOString().slice(0,10).replaceAll('-','');
				if (inputDateAsInt < yesterdaysDateAsInt) {
					showDateError(inputElement);
				} else {
					clearErrorAndAriaLive(inputElement);
					inputElement.value = inputValue;
				}

			} else {
				// Invalid date
				showDateError(inputElement);
			}
		} else {
			// Invalid format
			showDateError(inputElement);
		}
	}

	function showDateError(inputElement) {
		const inputId = inputElement.id;
		const inputValue = inputElement.value.trim();
		const inputDescriptions = {
			'start_date': 'start date'        
		};
		const inputValueDescription = inputDescriptions[inputId] || 'date';
		const errorMessage = generateErrorMessage(inputValue, inputValueDescription);
		setErrorAndAriaLive(inputElement, errorMessage);
		inputElement.value = '';
	}

	function dateOnBlurFn() {
		validateDateInput(this);
		let previousValue = this.getAttribute('data-previous-value');
		let inputValue = this.value;
		if (previousValue != inputValue) { // Refresh events only if we are clearing a previously set date
			this.setAttribute('data-previous-value', inputValue);
		}
	}
/*--------------Date Picker Functions End -----------------*/	

function redactorFocusOut(id){	
	$('.redactor-in').on('keydown', function(e) {	
		if (e.shiftKey && e.key === 'Tab') {           
			$(id).focus();    
		}
	})
}
//Add document title to page
function updatePageTitle(title){	
	document.title = title;
  window.tskp?.analytics?.triggerPageLoadEvent();
}

function getHome(gid,cid,chid){
	window.location.hash = "announcements";
	localStorage.setItem("manage_active", "manageGlobalAnnouncements");
	$.ajax({
		url: 'ajax_groupHome.php',
        type: "GET",
		data: 'getgrouphome='+gid+'&chapterid='+cid+'&channelid='+chid,
        success : function(data) {
			$('#ajax').html(data);	
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
	} );

	
}

function getEvent(gid,cid,chid){
	window.location.hash = "events";
	localStorage.setItem("manage_active", "manageGlobalEvents");
	$.ajax({
		url: 'ajax_events.php',
        type: "GET",
		data: 'getEvent='+gid+'&chapterid='+cid+'&channelid='+chid,
        success : function(data) {
			$('#ajax').html(data);
		}
	});
}

function getGroupMembersTab(i) {
	localStorage.setItem("manage_active", "getGroupMembersTab");
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: 'getGroupMembersTab='+ i,
        success : function(data) {
			$('#ajax').html(data);
			$(".deluser").popConfirm({content: ''});
		}
	});
}

function mangeGroupLeads(g) {
	localStorage.setItem("manage_active", "getGroupMembersTab");
	localStorage.setItem("is_resource_lead_content", 1);
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: 'mangeGroupLeads='+g,
        success : function(data) {
			$('#leads').html(data);
			$(".deluser").popConfirm({content: ''});
		}
	});
}

function manageBudgetExpSection(i){
	localStorage.setItem("manage_active", "manageBudgetExpSection");
	$.ajax({
		url: 'ajax_budget.php?managesection='+i,
		type: 'POST',
		data: {}, // get Data in html page by "managesection" Var
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					$("#manageDashboard").trigger("click");
					$("#manageDashboard_li").trigger("click");
				});
			} catch(e) { 
				$('#ajax').html(data);
			}
		}
	});
}

function addUpdateExpenseInfo(i){

	let calling_path = new URL(window.location.href).pathname;
	let calling_page = calling_path.substring(calling_path.lastIndexOf('/') + 1);

	$(document).off('focusin.modal');
	let m = 1;
	
	let formdata =	$('#budgetExpForm')[0];
	let finaldata  = new FormData(formdata);
	finaldata.append("calling_page", calling_page);
	
	// Budget Amount Validation
	if ($("#budgeted_amount").length){ // Because budgeted amount field is configurable
		let budgeted_amount = $("#budgeted_amount").val();
		if(!$.isNumeric(budgeted_amount)){

			swal.fire({title: 'Error',text:"Please enter a valid value for budgeted amount",allowOutsideClick:false});
			return;
		}
		let subBudgetTotal = 0;
		let subBudgetVal = $("input[name='item_budgeted_amount[]']")
				.map(function(){return $(this).val();}).get();
		let invalidSubBudgetAmount = false;	
		$.each(subBudgetVal,function(){
			if (!$.isNumeric(this)) {
				invalidSubBudgetAmount = true;
			}
			subBudgetTotal+=parseFloat(this) || 0;
		});
		if (invalidSubBudgetAmount){
			swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Please enter a valid value for subitem budget amount.'))?>",allowOutsideClick:false});
			return;
		}
		
		if ((subBudgetTotal > 0 || subBudgetTotal < 0 ) && subBudgetTotal>budgeted_amount){
			swal.fire({text:"<?= addslashes(gettext('The sub item budget totals are more than budgeted amount. Continue to automatically update the budgeted amount and review.'))?>",allowOutsideClick:false}).then( function(result) {
				// Custom logic will be here
				$("#budgeted_amount").val(subBudgetTotal);
			});
			return;
		} 
	}

	// Expense Amount validation
	let subTotal = 0;
	let usedamount = $("#usedamount").val();
	if(!$.isNumeric(usedamount)){
		swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Please enter a valid value for expense total'))?>",allowOutsideClick:false});
		return;
	}
	let subVal = $("input[name='item_used_amount[]']")
			.map(function(){return $(this).val();}).get();
	let invalidSubAmount = false;	
	$.each(subVal,function(){
		if (!$.isNumeric(this)) {
			invalidSubAmount = true;
		}
		subTotal+=parseFloat(this) || 0;
	});
	if (invalidSubAmount){
		swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Please enter a valid value for subitem expense amount.'))?>",allowOutsideClick:false});
		return;
	}
	if ((subTotal > 0 || subTotal < 0 ) && subTotal!=usedamount){
		swal.fire({text:"<?= addslashes(gettext('The sub item expense totals do not match the expensed amount. Continue to automatically update the expensed amount and review.'))?>",allowOutsideClick:false}).then( function(result) {
			// Custom logic will be here
			$("#usedamount").val(subTotal);
		});
		return;
	} 
	preventMultiClick(1);
	$.ajax({
		url: 'ajax_budget.php?addUpdateExpenseInfo='+i,
		type: 'POST',
		data: finaldata, // get Data in html page by "managesection" Var
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						$('#expenseModal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						if (localStorage.getItem("manage_active") == 'manageBudgetExpSection' && !finaldata.get('event_id')){
							manageBudgetExpSection(i);
						} else if(localStorage.getItem("manage_active") == 'manageGlobalEvents') {
							manageEventExpenseEntries(finaldata.get('event_id'));
						}
					}
					setTimeout(() => {
						$(".close").focus();
					}, 500);
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});
	

}

function createExpenseFromApprovedBudget(i,g){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_budget.php?createExpenseFromApprovedBudget=1',
		type: 'GET',
		data: 	{ groupid:g,rid:i },
		success: function(data) {			
			try {
				let jsonData = JSON.parse(data);

				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){	
						let values = jsonData.val;					
						manageBudgetExpSection(g);
						isPastRequestsRendered = 1;
						setTimeout(() => {							
							showRequstTable();  
							addUpdateExpenseInfoModal(g,values.lastId); 
						}, 300)						
					}
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});	
		
}

function addUpdateExpenseInfoModal(g,i, event_id = '', view_expense_detail = false, chapter_id = '', force_new_event_expense_entry = false){
	closeAllActiveModal();
	let calling_path = new URL(window.location.href).pathname;
	let calling_page = calling_path.substring(calling_path.lastIndexOf('/') + 1);

	$.ajax({
		url: 'ajax_budget.php?addUpdateExpenseInfoModal='+g,
        type: "POST",
		data: {
			id: i,
			event_id: event_id,
			view_expense_detail: +view_expense_detail,
			calling_page: calling_page,
			chapter_id:chapter_id,
			force_new_event_expense_entry:force_new_event_expense_entry
		},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$('#loadAnyModal').html(data);
				$('#expenseModal').modal({
					backdrop: 'static',
					keyboard: false
				});
				$( "#dateInput" ).datepicker({
					prevText:"click for previous months",
					nextText:"click for next months",
					showOtherMonths:true,
					selectOtherMonths: true,
					dateFormat: 'yy-mm-dd'
				});
			}
		}
	});
}

// Affinity reports
function filterMetaFields(action, excludeMetaFileds){
	$('input.metaFields').prop('checked', true);
	$('#submit_action_button').prop('disabled',false);
	let jsonMetaFields = JSON.parse(excludeMetaFileds);
	console.log(jsonMetaFields);
	$.each( jsonMetaFields, function( i, v ){
		if (action == 'analytic'){
			$("#id_"+v).hide();
		} else {
			$("#id_"+v).show();
		}
	});

	if (action == 'analytic'){
		$("#analytic_note").show();
		$("#submit_action_button").html("Analytics");
	} else {
		$("#analytic_note").hide();
		$("#submit_action_button").html("Download");
	}
}
function getAllReports(gid){
	localStorage.setItem("manage_active", "manageAllReports");
	$.ajax({
		url: 'ajax_reports.php',
        type: "GET",
		data: 'getAllReports='+gid,
        success : function(data) {
			$('#ajax').html(data);
		}
	});

}
function getUserReports(g,a){
	$.ajax({
		url: 'ajax_reports.php?getUserReports='+g,
		type: "GET",
		data: {'reportType':a},
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#theReportModal').modal({
				backdrop: 'static',
				keyboard: false
		});
		}
	});
}

function getEventsReportModal(g,e){
	$.ajax({
		url: 'ajax_reports.php?downloadEventsReport='+e,
		type: "GET",
		data: {'groupid':g},
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#theReportModal').modal({
				backdrop: 'static',
				keyboard: false
		});
		}
	});
}		

function getEventSpeakerReports(g){
	$.ajax({
		url: 'ajax_reports.php?download_event_speaker_report='+g,
		type: "GET",
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#theReportModal').modal({
				backdrop: 'static',
				keyboard: false
		});
		}
	});

	setTimeout(() => {
		$(".close").focus();    
	}, 500);
}

function getRecognitionReports(g){
	$.ajax({
		url: 'ajax_reports.php?getRecognitionReports='+g,
		type: "GET",
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#theReportModal').modal({
				backdrop: 'static',
				keyboard: false
		});
		}
	});
}
function getAnnouncementsReport(e){
	$.ajax({
		url: 'ajax_reports.php?getAnnouncementsReport='+e,
		type: "GET",
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#theReportModal').modal({
				backdrop: 'static',
				keyboard: false
		});
		}
	});
}
function getNewslettersReport(e){
	$.ajax({
		url: 'ajax_reports.php?getNewslettersReport='+e,
		type: "GET",
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#theReportModal').modal({
				backdrop: 'static',
				keyboard: false
		});
		}
	});
}
function getBudgetReports(e,a){
	$.ajax({
		url: 'ajax_reports.php?budgetReportsModal='+e,
		type: "GET",
		data: {'reportType':a},
		success : function(data) {
			if (data == 100){
				swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('No budget year found configured yet. Please contact your administrator'))?>"});
			}else {

				$("#loadAnyModal").html(data);
				$('#theBudgetReportModal').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});
}
function getGroupJoinRequestReportOptions(g){
	$.ajax({
		url: './ajax_reports.php?getGroupJoinRequestReportOptions=1',
		type: 'GET',
		data: {groupid:g},
		success: function(data) {
			$("#loadAnyModal").html(data);
			$('#theReportModal').modal({
				backdrop: 'static',
				keyboard: false,
			});
		}
	});
}
// Affinity reports end here
// Talentpeak Reports
function getTeamsReportOptions(g){
	$.ajax({
		url: './ajax_reports.php?getTeamsReportOptions=1',
		type: 'GET',
		data: {groupid:g},
		success: function(data) {
			$("#loadAnyModal").html(data);
			$('#theReportModal').modal({
				backdrop: 'static',
				keyboard: false,
			});
		}
	});
}
function getTeamMemberReportOptions(g){
	$.ajax({
		url: './ajax_reports.php?getTeamMemberReportOptions=1',
		type: 'GET',
		data: {groupid:g},
		success: function(data) {
			$("#loadAnyModal").html(data);
			$('#theReportModal').modal({
				backdrop: 'static',
				keyboard: false,
			});
		}
	});
}
// Talentpeak Reports end Here  
function getAboutusTabs(gid,cid,chid){	
	window.location.hash = "about";
	localStorage.setItem("manage_active", "updateAboutUsData");
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: 'getAboutusTabs='+gid+'&chapterid='+cid+'&channelid='+chid,
        success : function(data) {
			$('#ajax').html(data);
		}
	});		
}

function getAboutus(gid,cid,chid){
	window.location.hash = "about";
	localStorage.setItem("manage_active", "updateAboutUsData");
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: 'getAboutus='+gid+'&chapterid='+cid+'&channelid='+chid,
        success : function(data) {
			$('#AboutUS').html(data);
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
	});
	
}

function donation(gid){

	window.location.hash = "donation";
	localStorage.setItem("manage_active", "donation");
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: 'donation='+gid,
        success : function(data) {
			$('#ajax').html(data);
		}
	});	
	newPageTitle = 'Donation';
	document.title = newPageTitle;
	
}

// blocks deleted, use git blame to find the previous version
// function newRecruiting(i){}
// function submitRecruiting(){}

function b64toBlob(b64Data, contentType, sliceSize) {

	contentType = contentType || '';
	sliceSize = sliceSize || 512;

	let byteCharacters = atob(b64Data);
	let byteArrays = [];

	for (var offset = 0; offset < byteCharacters.length; offset += sliceSize) {
		let slice = byteCharacters.slice(offset, offset + sliceSize);

		let byteNumbers = new Array(slice.length);
		for (var i = 0; i < slice.length; i++) {
			byteNumbers[i] = slice.charCodeAt(i);
		}

		let byteArray = new Uint8Array(byteNumbers);

		byteArrays.push(byteArray);
	}

	let blob = new Blob(byteArrays, {type: contentType});
	return blob;
}


// blocks deleted, use git blame to find the previous version
// function manageRecruiting(i){}
// function deleteRecruiting(r,i){}
// function newgetReferrals(i){}
// function submitReferral(){}
// function manageReferral(i){}


function getManage(i){
	
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: 'manage='+ i,
        success : function(data) {
			$('#ajax').html(data);
		}
	});
	
}

function updateAnnouncement(i){
	this.closeAllActiveModal();
	$.ajax({
		url: 'ajax_announcement.php',
        type: "GET",
		data: 'updateAnnouncement='+ i,
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});

			} catch(e) {
				$('#ajax').html(data);
			}
		}
	});

	setTimeout(function () {
		$('.redactor-statusbar').attr('id',"redactorStatusbar");
	}, 100);	
}
// Delete announcement pic
function deleteAnnouncement(i,g,cp,ch,userview,isadmin){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_announcement.php?deleteAnnouncement='+g,
        type: "POST",
		data: {'postid':i},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (userview) {
						if (isadmin) {
							goto_homepage();
						} else {
							window.location.href = "detail?id=" + g + '&chapterid=' + cp + '&channelid=' + ch + '#announcements';
						}
					} else {
						location.reload();
					}
				});
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}

function joinGroup(i){
	
	$.ajax({
		url: 'ajax.php?joinGroup='+i,
        type: "POST",
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message})
				.then(function(result) {
					if(jsonData.status == 1){
						$("#join").hide();
						location.reload();
					}
				});
			} catch(e) {
				// Handle Ajax HTML
				$('#modal_replace').html(data);
				$('#follow_chapter').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});
}

function leaveGroup(i){
	$.ajax({
		url: 'ajax.php?leaveGroup='+ i,
        type: "POST",
        success : function(data) {

			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message})
				.then(function(result) {
					if(jsonData.status == 1){
						$("#join").hide();
						location.reload();
					}
				});
			} catch(e) {
				// Handle Ajax HTML
				$('#modal_replace').html(data);
				$('#follow_chapter').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});
	
}

function joinGroupAndAutoAssignChapter(i){

	$.ajax({
		url: 'ajax.php?joinGroupAndAutoAssignChapter='+i,
		type: "POST",
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status>0){
					swal.fire({
						title: jsonData.title,
						html: jsonData.message,
						showConfirmButton: true,
						confirmButtonText: 'Ok'
					}).then(function(result) {
						location.reload();
					});
				} else {
					swal.fire({title:jsonData.title,text:jsonData.message});
				}
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
			}
		}
	});
}

function getMyGroups(f){
	let groupTags = $("#group_tags").val();
	const buttonToFilterMap = {
        "upcomingEventsButton": ['upcomingEvents'],
        "showEverythingButton": ['event', 'post', 'newsletter', 'discussion','album'],
        "postButton": ['post'],
        "newsletterButton": ['newsletter'],
        "eventButton": ['event'],
		"discussionsButton": ['discussion'],
		"albumsButton": ['album'],
    };
	const lastSelectedButton = localStorage.getItem("selectedButton");
	var contentFilter = [];
	if(lastSelectedButton){
		contentFilter = buttonToFilterMap[lastSelectedButton];
	}else{
		contentFilter = ['event', 'post', 'newsletter','discussion','album'];
	}
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: {'getMyGroups':f,'groupTags':groupTags,'contentFilter':contentFilter},
        success : function(data) {

			
			$("#btnone").removeClass('activebtn');
			$("#btntwo").removeClass('inactivebtn');
			$("#btnone").addClass('inactivebtn');
			$("#btntwo").addClass('activebtn');
			$('#ajaxreplace').html(data);
			jQuery(".confirm").popConfirm({content: ''});
			$('#btntwo').attr('aria-selected', 'true');
			$('#btnone').attr('aria-selected', 'false');
		}
	});
}

function discoverGroups(f){
	let groupTags = $("#group_tags").val();
	const buttonToFilterMap = {
        "upcomingEventsButton": ['upcomingEvents'],
        "showEverythingButton": ['event', 'post', 'newsletter', 'discussion','album'],
        "postButton": ['post'],
        "newsletterButton": ['newsletter'],
        "eventButton": ['event'],
		"discussionsButton": ['discussion'],
		"albumsButton": ['album'],
    };
	const lastSelectedButton = localStorage.getItem("selectedButton");
	var contentFilter = [];
	if(lastSelectedButton){
		contentFilter = buttonToFilterMap[lastSelectedButton];
	}else{
		contentFilter = ['event', 'post', 'newsletter', 'discussion', 'album'];
	}
	
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: {'discoverGroups':f,'groupTags':groupTags,'contentFilter':contentFilter},
        success : function(data) {
			
			$("#btnone").removeClass('inactivebtn') ;
			$("#btntwo").removeClass('activebtn');
			$("#btnone").addClass('activebtn');
			$("#btntwo").addClass('inactivebtn');
			$('#ajaxreplace').html(data);
			jQuery(".confirm").popConfirm({content: ''});

			$('#btntwo').attr('aria-selected', 'false');
			$('#btnone').attr('aria-selected', 'true');
		}
	});
}

function loadMoreHomeFeeds(f,ignoreFocus) {
	ignoreFocus = (typeof ignoreFocus !== 'undefined') ? true : false;
	window.location.hash = "announcements";
	let page = $("#feedPageNumber").val();
	let groupTags = $("#group_tags").val();
	if (!ignoreFocus){
		localStorage.setItem("home_feeds_autoload_count", 1); // Reset
		localStorage.setItem("home_feeds_count", 0); // Reset
	}
	const buttonToFilterMap = {
        "upcomingEventsButton": ['upcomingEvents'],
        "showEverythingButton": ['event', 'post', 'newsletter', 'discussion', 'album'],
        "postButton": ['post'],
        "newsletterButton": ['newsletter'],
        "eventButton": ['event'],
		"discussionsButton": ['discussion'],
		"albumsButton": ['album'],
    };
	const lastSelectedButton = localStorage.getItem("selectedButton");
	var contentFilter = [];
	if(lastSelectedButton){
		contentFilter = buttonToFilterMap[lastSelectedButton];
	}else{
		contentFilter = ['event', 'post', 'newsletter', 'discussion', 'album'];
	}
    
	var pagination_url = window.tskp?.pagination?.url ?? 'ajax.php?loadMoreHomeFeeds=1';
	var payload = window.tskp?.pagination?.payload
		?? {'filter':f,'groupTags':groupTags,'contentFilter':contentFilter};
	payload.page = page;
	$("#showNoDataByContentFilter").hide();
	$.ajax({
		url: pagination_url,
		type: "GET",
		data: payload,
		success: function (data) {
			if (data == 1) {
				$('#loadeMoreFeedsAction').hide();
			} else {
				$("#feedPageNumber").val((parseInt(page) + 1));

				const regex = /<span.*>_l_=(.*)<\/span>$/gm;
				let m;
				if ((m = regex.exec(data)) === null) {
					$('#loadeMoreFeedsAction').hide();
				} 
				$('#feed_rows').append(data);
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

				if ($('.home-announcement-block').length==0){
					$("#showNoDataByContentFilter").show();
				}
			}
		}
	});
	if (!ignoreFocus){
		let lastListItem  = document.querySelectorAll('.home-announcement-block');
		let last = (lastListItem[lastListItem.length -1]);
		last.querySelector(".home-announcement-block .text-asd a").focus();
	}
}

function joinEvent(i,j,r,t,rd){
	$("body").css("cursor", "progress");
	let eventSurveyResponse = (typeof r !== 'undefined') ? r : '';
	let trigger = (typeof r !== 'undefined') ? t : '';
	let reloadEventDetail = (typeof rd !== 'undefined') ? 1 : 0;
	$.ajax({
		url: 'ajax_events.php?joinEvent='+i,
        type: "POST",
		data: {
			'js':j,
			'eventSurveyResponse':eventSurveyResponse,
			'trigger':trigger
		},
        success : function(data) {
			$("body").css("cursor", "default");
			if (reloadEventDetail) {
				getEventDetailModal(i,rd,rd);
			} else{
				try {
					let jsonData = JSON.parse(data);
					swal.fire({title:jsonData.title,text:jsonData.message})
					.then(function(result) {					
						if(jsonData.status == -100){
							window.location.href=jsonData.val;
						}
					});
				} catch(e) {
					// Change RSVP YES,NO,TENTIVE button on ajax call
					$('#joinEventRsvp'+i).html(data);
				}
				setTimeout(() => {
					$(".swal2-confirm").focus();
				}, 100)
				$('#btn_close').focus();
			}
		}
	});
}


function updateAboutUsData(i,finalData){
	$(document).off('focusin.modal');
	localStorage.setItem("manage_active", "updateAboutUsData");
	let e = 'false';
	let f = '';
	if(finalData) {
		e = 'true';
	}
	
	$.ajax({
        url: 'ajax.php?updateboutus='+i+'&edit='+e,
        type: 'POST',
        data: finalData, // get Data in Ajax page by "updateboutus" Var
		processData: false,
		contentType: false,
		cache: false,
        success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false});
			} catch(e) {
				// Handle Ajax HTML
				$('#ajax').html(data);
				if( e == 'true'){
					$("#showmessage").show();
					setTimeout(function(){
						$("#showmessage").hide();
					}, 4000);
				}
			}
		}
	});
}

async function deleteEvent(r, i, g, cp, ch, userview, isadmin, ispublished, has_ended,has_expense_entry,ispublishedtoemail,ispublishedical){
	let cancellationReason;
	let sendCancellationEmails = false;
	let checkboxHtml = '';
	let checkboxChecked = false;

	if (ispublished && has_ended) {
		checkboxChecked = false;
	} else if (ispublished && !has_ended && ispublishedtoemail && ispublishedical) {
		checkboxChecked = true;
	}

    if (!ispublishedtoemail) {
        checkboxHtml = `
        <div class="hidden">
        <input class="form-check-input" type="checkbox" id="sendCancellationEmails" />
        </div>
        `
    } else {
        checkboxHtml = `
        <div class="mt-3 text-left form-check">
		<input class="form-check-input" type="checkbox" id="sendCancellationEmails" ${checkboxChecked ? 'checked' : ''} />
		<label class="form-check-label" for="sendCancellationEmails"><?= addslashes(gettext("Send cancellation emails to all invited"))?></label>
		<br>
		<br>
		<small><?= addslashes(gettext('Note: Cancellation emails will always be sent to users who RSVPed, even if you opt not to notify invited users.'));?></small>
        <br>
        <br>
        </div>
	`;
    }

	if (ispublished) {
        const {value: retval, isConfirmed} = await Swal.fire({
            title: '<?= addslashes(gettext("Event cancellation reason"))?>',
            input: 'textarea',
            inputPlaceholder: '<?= addslashes(gettext("Enter event cancellation reason"))?>',
            inputAttributes: {
                'aria-label': '<?= addslashes(gettext("Enter event cancellation reason"))?>',
                maxlength: 200
            },
            showCancelButton: true,
            cancelButtonText: '<?= addslashes(gettext("Close"))?>',
            confirmButtonText: '<?= addslashes(gettext("Cancel Event"))?>',
            allowOutsideClick: () => false,
            inputValidator: (value) => {
                return new Promise((resolve) => {
                    if (value) {
                        resolve()
                    } else {
                        resolve('<?= addslashes(gettext("Please enter event cancellation reason"))?>')
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


        if (has_expense_entry) {
            const result = await Swal.fire({
                title: '<?= addslashes(gettext("Are you sure?"))?>',
                text: '<?= addslashes(gettext("This event has an expense entry which will not be deleted"))?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<?= addslashes(gettext("I understand, cancel event!"))?>'
            });

            if (!result.value) {
                return;
            }
        }
    }

	if (!ispublished || cancellationReason) {
		$.ajax({
			url: 'ajax_events.php?deleteEvent=1',
			type: "POST",
			data: {
				'eventid': i,
				'event_cancel_reason': cancellationReason,
				'sendCancellationEmails': sendCancellationEmails
			},
			success: function (data) {
				try {
					let jsonData = JSON.parse(data);
					if(jsonData.status == 2){
						showDeleteConfirmationModal(i, cancellationReason, sendCancellationEmails, g, cp, ch, userview, isadmin, jsonData.message)
						return;
					}
					handleEventDeletionResponse(jsonData, isadmin, userview, g, cp, ch)
				} catch (e) {
					swal.fire({ title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>" });
				}
			}
		});
	}
}

function showDeleteConfirmationModal(eventId, cancellationReason, sendCancellationEmails, g, cp, ch, userview, isadmin, dependenciesMessage){
	// delete confirmation logic here
	Swal.fire({
		title: 'Event has dependencies!',
		html: `<p>${dependenciesMessage}</p>
		<input id="confirmDeleteInput" class="swal2-input" placeholder="I agree">`,
		showCancelButton: true,
		confirmButtonText: 'Delete Event',
		preConfirm: () => {
			const inputVaue = Swal.getPopup().querySelector("#confirmDeleteInput").value.trim();
			if(inputVaue !== "I agree"){
				Swal.showValidationMessage('You must type "I agree" to proceed');
			}
		}
	}).then(async (result) => {
		if(result.isConfirmed){
			try {
				const confirmResponse = await $.ajax({
					url: 'ajax_events.php?deleteEvent=1',
					type: "POST",
					data: {
						'eventid': eventId,
						'event_cancel_reason': cancellationReason,
						'sendCancellationEmails': sendCancellationEmails,
						'confirmDelete' : true
					},
				});

				const confirmJsonData = JSON.parse(confirmResponse);
				await handleEventDeletionResponse(confirmJsonData, isadmin, userview, g, cp, ch);
			} catch (error) {
				swal.fire({ title: '<?=gettext("Error");?>', text: "<?= gettext('Failed to delete event.');?>" });
			}
		}
	})
		
}
function handleEventDeletionResponse(jsonData, isadmin, userview, g, cp, ch){
	swal.fire({
		title: jsonData.title,
		text: jsonData.message
	}).then(function (result) {
		if (userview) {
			if (isadmin) {
				goto_homepage();
			} else {
				window.location.href = "detail?id=" + g + "&chapterid=" + cp + "&channelid=" + ch + "#events";
			}
		} else {
			location.reload();
		}
	});
}
function updateEventForm(i,c,g){
	let parent_groupid = (typeof g !== 'undefined') ? g : '';
	this.closeAllActiveModal();
	let endpoint = 'ajax_events.php';
	let parms = {'getEventCreateUpdateForm':1,'eventid':i, 'call':c, 'parent_groupid':parent_groupid};
	
	$.ajax({
		url: endpoint,
		type: 'GET',
		data: parms,
		success: function(data) {
			if ( data == 1){
				swal.fire({title: 'Error',text:"Event not exist"});
			} else {	
				$('#ajax').html(data);
			}
		}
	});
}


// Read Group and post Notification
function readNotification(s,i,n){
	$.ajax({
        url: 'ajax.php',
        type: 'GET',
        data: 'readNotification='+n+'&s='+s+'&i='+i,
        success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status == 2){
					swal.fire({title:jsonData.title,text:jsonData.message});
					return false;
				} else {
					window.location.href = data;
				}
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
			}
		}
	});
	e.preventDefault();
}

// Set All Read Notification
function setAllReadNotification(){
	$.ajax({
        url: 'ajax.php?setAllReadNotification=1',
        type: 'POST',
        data: 'setAllReadNotification=1',
        success: function(data) {
			location.reload();
		}
	});
	
}

//Ajax Pagination  HOME page
function homePagePagination(lim, off, condition, eventlast) {
	$.ajax({
		type: "GET",
		async: false,
		url: "ajax.php",
		data: "homePagePagination=" + lim + "&offset=" + off + "&condition=" + condition + "&eventlast="+eventlast,
		cache: false,
		beforeSend: function() {
			$("#loader_message").html("").hide();
			$('#loader_image').show();
		},
		success: function(html) {
			$('#loader_image').hide();
			$("#results").append(html);
			
			if ( html.search("window.eventlast = 0")>0 ){
				window.busy = true;
				$("#loader_message").html('<button data-atr="nodata" class="btn btn-default no-data" type="button">No more records.</button>').show();
			} else {
				window.busy = false;
				$("#loader_message").html('<button class="btn btn-default" type="button">Scroll down to load more records</button>').show();
				
			}
			$(document).ready(function(){
				//initial for blank profile picture
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

	});
			
		}
	});
}

function resetFromData(){
	$("#new-event-data").trigger( "reset" );
}

/*  
	#### Function for Accessiblity screen reading. ####
	### Parameter #1 is Table ID. ###
	### Parameter #2 is Table Object. ###
*/
function screenReadingTableFilterNotification(id,dtable){
	$(id).on( 'draw.dt', function(){            			
		$("#hidden_div_for_notification").html('');
		$("#hidden_div_for_notification").removeAttr('aria-live');  

		$("#hidden_div_for_notification").attr({role:"status","aria-live":"polite"});
		document.getElementById('hidden_div_for_notification').innerHTML="<?= gettext('Processing') ?>"; 

		var totalRows = dtable.page.info().end;			   
		if (totalRows === 0 ) { 
			setTimeout(function() {	                  
				document.getElementById('hidden_div_for_notification').innerHTML="<?= gettext('No data available in table.') ?>"; 
			}, 300); 
		}else{  
			setTimeout(function() {	                            
				document.getElementById('hidden_div_for_notification').innerHTML=+totalRows+" <?= gettext('rows data available in table.') ?>"; 
			}, 300); 
		}		
	});
}


//go back
function goBack() {
    window.history.back();
}

function getGlobalCalendarEventsByFilters(){
	let g = $("#bygroup").val().join();
	let e = $("#byEventType").val().join();
	let c = $("#byCategory").val().join();
	let r = $("#byregion").val().join();
	//let d = $("#bydistance").val();
	let ch = $("#byChapter").val().join();
	let z = $("#byZones").val().join();

	let gurl = g
	if ($("#bygroup option:not(:selected)").length == 0) {
		gurl = 'all';
	}
	let eurl = e;
	if ($("#byEventType option:not(:selected)").length == 0) {
		eurl = 'all';
		e = 'all';
	}
	let curl = c;
	if ($("#byCategory option:not(:selected)").length == 0) {
		curl = 'all'
	}
	let rurl = r;
	if ($("#byregion option:not(:selected)").length == 0) {
		rurl = 'all';
	}
	let churl = ch;
	if ($("#byChapter option:not(:selected)").length == 0) {
		churl = 'all'
		//ch = 'all';
	}
	let zurl = z;
	// if ($("#byZones option:not(:selected)").length == 0) {
	// 	zurl = 'all';
	// }

	let calendarDefaultView = $("#calendarDefaultView").val();
	let calendarDefaultDate = $("#calendarDefaultDate").val();
	
	let params = {'groups':gurl,'eventType':eurl, 'category':curl, 'regionids':rurl, 'chapterid':churl,'calendarDefaultDate':calendarDefaultDate,'calendarDefaultView':calendarDefaultView,'zoneids':zurl};
	$.ajax({
        url: 'ajax_events.php?getGlobalCalendarEventsByFilters=1',
        type: 'POST',
        data: {'groups':g,'eventType':e, 'category':c, 'regionids':r, 'chapterid':ch,'calendarDefaultDate':calendarDefaultDate,'calendarDefaultView':calendarDefaultView,'zoneids':z},
		beforeSend: function() {
           $("#resultLoading").hide();
		},
        success: function(data) {
			updateCurrentUrlParameters(params);
			$("#ajax").html(data);
                let state_filter = localStorage.getItem('calendar_state_filter');
				if (state_filter){
					if(state_filter === "byZones"){
						$('.by-zones .multiselect').focus();
					}else if(state_filter === "byregion"){
						$('.by-region .multiselect').focus();
					}else  if(state_filter === "bygroup"){
						$('.by-group .multiselect').focus();						
					}else if(state_filter === "byCategory"){
						$('.by-category .multiselect').focus();						
					}else if(state_filter === "byEventType"){
						$('.by-event-type .multiselect').focus();
					}else if(state_filter === "byChapter"){
						$('.by-chapter .multiselect').focus();
					}else if(state_filter === "bydistance"){
						$('.by-distance .multiselect').focus();
					}					
					localStorage.removeItem("calendar_state_filter");	
					
					$('.multiselect-container').attr('role', 'listbox');
					$('.multiselect-container').attr('aria-multiselectable', true);
					$('.multiselect-option').attr('role', 'option');
					$('.multiselect-option').attr('aria-selected', false);	
					$('.multiselect-option.active').attr('aria-selected', true);
				}
				$('.by-chapter .multiselect').attr('tabindex', '0'); 
		}
	});
	
}

// Request Budget
function requestBudget(g,r){
	$(document).off('focusin.modal');
	let formdata = $('#budget-request-form')[0];
	let finaldata  = new FormData(formdata);
  $.ajax({
        url: 'ajax_budget.php?requestBudget='+g+'&request_id='+r,
        type: 'POST',
		processData: false,
        contentType: false,
        cache: false,
        data: finaldata,
        tskp_submit_btn: $('#request_budget_btn'),
        success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function (result) {
					if (jsonData.status == 1){
						$('#budget_request_form').html('');
						$('#budget_request').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						manageBudgetExpSection(g);
						isPastRequestsRendered = 1;
						showRequstTable();
					}
				});
			} catch(e) {}
		}
	});
}
function showRequstTable(){
	$("#show-request-table").toggle();
	if(isPastRequestsRendered == 0)
	{
		pastRequests();
	}
	let btn = $("#request-view-btn").text().trim();
	if(btn == 'View Past Requests'){
		$("#request-view-btn").html('Hide Past Requests')
	}else{
		$("#request-view-btn").html('View Past Requests')
	}
    isPastRequestsRendered = 1;

	setTimeout(function(){
		$(".details-control-btn").attr({ "aria-expanded":"false", "aria-label":"<?= gettext('Request Detail');?>" });		
	}, 500);
	
}
// Delete Budget Requests
function deleteBudgetRequest(tr,id,g){
	$.ajax({
        url: 'ajax_budget.php?deleteBudgetRequest='+g,
        type: 'POST',
        data: 'id='+id,
		success: function(data) {
			jQuery("#req"+tr).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
			setTimeout(function(){
				manageBudgetExpSection(g);
				isPastRequestsRendered = 1;
				setTimeout(function(){
					showRequstTable();
				}, 3000);
			}, 1000);
			swal.fire({title: '<?=gettext("Success");?>',text: " <?= addslashes(gettext('Budget request deleted successfully.'))?>"});
		}
	});
}
// Archive Budget Requests
function archiveBudgetRequest(tr,id,g){
	$.ajax({
        url: 'ajax_budget.php?archiveBudgetRequest='+g,
        type: 'POST',
        data: 'id='+id,
		success: function(data) {
			jQuery("#req"+tr).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
			setTimeout(function(){
				manageBudgetExpSection(g);
				isPastRequestsRendered = 1;
				setTimeout(function(){
					showRequstTable();
				}, 3000);
			}, 1000);
		}
	});
}

// Update User timezone
function updateTimeZone(){
	let tz = $("#selected_timezone").val();
	$.ajax({
        url: 'ajax.php?updateTimeZone=1',
        type: 'POST',
		data: "timezone="+tz,
        success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message}).then( function(result) {
					location.reload();
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }
		}
	});
}

// Use browser Timezone
function useBrowserTimezone(tz){
	$.ajax({
        url: 'ajax.php',
        type: 'GET',
		data: "useBrowserTimezone="+tz,
        success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message}).then( function(result) {
					location.reload();
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }
		}
	});
}

// Use Profile Timezone
function useProfileTimezone(tz){
	$.ajax({
        url: 'ajax.php',
        type: 'GET',
		data: "useProfileTimezone="+tz,
        success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message}).then( function(result) {
					location.reload();
				});
			} catch(e) {  swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }
		}
	});
}

// Show timezone picker
function showAdvEventPicker(){
	$("#adv_event_div").toggle();
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

// Get Event Joiners
function eventRSVPsForCheckIn(i,s) {
	$.ajax({
		url: 'ajax_events.php',
        type: "GET",
		data: 'eventRSVPsForCheckIn='+i+'&section='+s,
        success : function(data) {
			if ($('#dynamic_data_container').length) {
				$('#dynamic_data_container').html(data);
			} else {
				$('#ajax').html(data);	
			}
			closeAllActiveModal();	
		}
	});	
}
// // Get Event Joiners
// function refresh(i) {
// 	$.ajax({
// 		url: 'ajax_events.php',
//         type: "GET",
// 		data: 'eventRSVPsForCheckIn='+ i,
//         success : function(data) {
// 			$('#ajax').html(data);

// 		}
// 	});
// }



// Update event check IN
function updateEventCheckIn(jsevent, i,s,j,e) {
	$.ajax({
		url: 'ajax_events.php?updateEventCheckIn='+ e,
        type: "POST",
		data: {'checkin':s,'joineeid':j},
		tskp_submit_btn: jsevent.target,
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				let checkin_count = parseInt($("#checkin-count").html());
				if (jsonData.status == 1){
					$('#check_'+i).html('<button type="button" class="btn btn-secondary btn-xs" onclick="updateEventCheckIn(event, ' + i + ',' + 1 + ',\'' + j + '\',\'' + e + '\')">'+jsonData.message+'</button>');
					checkin_count = checkin_count-1;
				}else{
					$('#check_'+i).html('<button type="button" class="btn btn-success btn-xs" onclick="updateEventCheckIn(event, ' + i + ',' + 0 + ',\'' + j + '\',\'' + e + '\')">'+jsonData.message+'</button>');
					checkin_count = checkin_count+1;
				}
				$("#checkin-count").html(checkin_count);
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
			}
		}
	});
}

// Show Non rsvp user check in form
function  showCheckInForm(e){
	$.ajax({
		url: 'ajax_events.php',
        type: "GET",
		data: 'showCheckInForm='+e,
        success : function(data) {
			$("#showcheckin_form").html(data);
			$('#nonRsvpform').modal();
		}
	});
	setTimeout(() => {
		$("#btn_close").focus();
	}, 500);
}


// Import CSV for check ins.
function importCSVForCheckIn(g,e) {	
	$.ajax({
		url: 'ajax_events.php?gid='+g,
        type: "GET",
		data: 'importCSVForCheckInModal='+e,
		success : function(data) {
			$("#import_csv_checkin_modal").html(data);
			$('#importCSVForCheckInModal').modal();
		}
	});
}

// Submit event check in data
function submitEventCheckinForm(){
	$(document).off('focusin.modal');
	let formdata = $('#event_check_in_form')[0];
	let finaldata  = new FormData(formdata);

	$.ajax({
        url: 'ajax_events.php?submitEventCheckinForm=1',
        type: 'POST',
		processData: false,
        contentType: false,
        cache: false,
        data: finaldata,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						$('#nonRsvpform').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						$('#loadAnyModal').html('');
						eventRSVPsForCheckIn(jsonData.val,'');
					}
				});
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}

function inviteGroupMembers(i){
	$.ajax({
        url: 'ajax.php',
        type: 'GET',
        data: 'inviteGroupMembers='+i, // get Data in Ajax page by "invitation" Var
        success: function(data) { 
			$('#inviteUsers').html(data);
			jQuery(".deluser").popConfirm({content: ''});
		}
	});
}

function processGroupMemberInvite(gid) {
	$(document).off('focusin.modal');
	let emailsToSendStr = $('#inviteUserTextArea').val();
	let emailsToSend = emailsToSendStr.match(/[\._a-zA-Z0-9-\+']+@[\._a-zA-Z0-9-]+/ig);

	if (!emailsToSend || !emailsToSend.length) {

		swal.fire({title: '<?= gettext("Error");?>', text: '<?= addslashes(gettext("Please enter one or more valid emails."))?>', allowOutsideClick:false});
		return;
	} else if (emailsToSend.length > 1000) {
		swal.fire({title: '<?= gettext("Error");?>', text: '<?= addslashes(gettext("Too many emails, you can enter a maximum of 1000 emails"))?>', allowOutsideClick:false});
		return;
	}

	let noOfEmailsToSend = emailsToSend.length;
	let invitesSent = '';
	let invitesFailed = '';
	let alreadySent = '';
	let skipped = '';
	let restricted = '';
	let itemCurrentlyProcessing = 1;
	let group_chapter_channel_id = $("#group_chapter_channel_id").val();
	let section = $('#group_chapter_channel_id').find(':selected').data('section');

	$("body").css("cursor", "progress");
	$('#form-invitation')[0].reset();
	$("#inviteSent").html('');
	$("#againSent").html('');
	$("#inviteFailed").html('');
	$("#totalBulkRecored").html(noOfEmailsToSend);
	$(".progress_status").html("Processing 0/" + noOfEmailsToSend + " email(s)");
	$('div#prgress_bar').width('0%');
	$('#progress_bar_invite').show();

	if (noOfEmailsToSend) {
		let p = Math.round((1 / noOfEmailsToSend) * 100);
		$(".progress_status").html("<?=gettext('Processing');?> " + 1 + "/" + noOfEmailsToSend);
		$('div#prgress_bar').width(p + '%');
	}

	emailsToSend.forEach( function (item, index) {
		preventMultiClick(1);
		 $.ajax({
			url: 'ajax.php?sendGroupMemberInvite=' + gid,
			type: "POST",
			global: false, /* stop global error handling, add csrf in parameters */
			data: {'email':item,'group_chapter_channel_id':group_chapter_channel_id,'section':section,'csrf_token':teleskopeCsrfToken},
			success: function (data) {
				if (data == 1) {
					invitesSent += item + ', ';
				} else if (data == 2) {
					alreadySent += item + ', ';
				} else if (data == 3) {
					skipped += item + ', ';
				} else if (data == 5) {
					restricted += item + ', ';
				} else if (data == 0) {
					invitesFailed += item + ', ';
				}
			},
			error: function(e) {
				 invitesFailed += item + ', ';
			 }
		}).always( function fn (){
			itemCurrentlyProcessing++;
			if (itemCurrentlyProcessing > noOfEmailsToSend) {
				$("body").css("cursor", "default"); 
				$(".progress_status").html("<i class='fa fa-check-circle' aria-hidden='true'></i><?=gettext('Completed');?> ");
				setTimeout(function () {
					$('#manageWaitList').modal('hide');
					$('#inviteUserTextArea').val('');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();

					$("#inviteSent_i").html(invitesSent.replace(/,\s*$/, ""));
					if (invitesFailed) {
						$("#inviteFailed_i").html(invitesFailed.replace(/,\s*$/, ""));
					}
					if (alreadySent) {
						$("#alreadySent_i").html(alreadySent.replace(/,\s*$/, ""));
					}
					if (skipped) {
						$("#skipped_i").html(skipped.replace(/,\s*$/, ""));
					}
					if (restricted) {
						$("#restricted_i").html(restricted.replace(/,\s*$/, ""));
					}
					$(".prevent-multi-clicks").removeAttr("disabled");
					$("#close_show_progress_btn").show();
					$(".progress_done").show();
				}, 250);
			}else{
				let p = Math.round((itemCurrentlyProcessing / noOfEmailsToSend) * 100);
				$(".progress_status").html("<?=gettext('Processing');?> " + itemCurrentlyProcessing + "/" + noOfEmailsToSend);
				$('div#prgress_bar').width(p + '%');
			}
		});
	});
}

function withdrawGroupMemberInvite(gid,mid,rid) {
	$.ajax({
		url: 'ajax.php?withdrawGroupMemberInvite=' + gid,
		type: "POST",
		data: {'mid':mid},
		success: function (data) {
			if (data == 1) {
				jQuery("#" + rid).animate({backgroundColor: "#fbc7c7"}, "fast").animate({opacity: "hide"}, 2000);
			}
		}
	});
}
// list of other groups
function notifyOtherGroups(eid,type){
	$('#notifyOtherGroupModal').modal('show');
}

//  open invite users to event pop up
function inviteEventUsers(){
	$('#eventInviteGroup').modal('show');
}

// invite to event 
function inviteEvent(){

	let invite_who = $('input[name="invite_who"]:checked').val();
	if(!invite_who){
		invite_who = $('input[name="invite_who"]').val();
	}

	if (invite_who == 'email_in'){
		processEventUserInvites();
	} else {
		let finaldata =	$('#inviteEvents').serialize();
		$.ajax({
			url: 'ajax_events.php?inviteToEvent=1',
			type: 'POST',
			data: finaldata,
			success: function(data) {
				try {
					let jsonData = JSON.parse(data);
					if (jsonData.status == 1){
						$('#eventInviteGroup').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						$('#loadAnyModal').html('');
						if (jsonData.message){
							swal.fire({title:jsonData.title,text:jsonData.message});
						}
					} else {
						swal.fire({title:jsonData.title,text:jsonData.message});
					}
				} catch(e) {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
				}
			}
		});
	}
}

function processEventUserInvites() {

	let eventid = $('#eventid').val();
	let emailsToSendStr = $('#invited_email_ids').val();
	let emailsToSend = emailsToSendStr.match(/[\._a-zA-Z0-9-\+']+@[\._a-zA-Z0-9-]+/ig);

	if (!emailsToSend || !emailsToSend.length) {
		swal.fire({title: '<?=gettext("Error");?>', text: '<?= addslashes(gettext("Please enter one or more valid emails"))?>'});
		return;
	} else if (emailsToSend.length > 1000) {
		swal.fire({title: '<?=gettext("Error");?>', text: '<?= addslashes(gettext("Too many emails, you can enter a maximum of 1000 emails"))?>'});
		return;
	}

	let noOfEmailsToSend = emailsToSend.length;
	let invitesSent = '';
	let invitesFailed = '';
	let againSent = '';
	let itemCurrentlyProcessing = 1;
	$('#invited_email_ids').val('');
	$("body").css("cursor", "progress");
	$("#inviteSent").html('');
	$("#againSent").html('');
	$("#inviteFailed").html('');
	$("#totalBulkRecored").html(noOfEmailsToSend);
	$(".progress_status").html("Processing 0/" + noOfEmailsToSend + " email(s)");
	$('div#prgress_bar').width('0%');
	$('#progress_bar').show();

	if (noOfEmailsToSend) {
		let p = Math.round((1 / noOfEmailsToSend) * 100);
		$(".progress_status").html("<?=gettext('Processing');?> " + 1 + "/" + noOfEmailsToSend);
		$('div#prgress_bar').width(p + '%');
	}

	emailsToSend.forEach( function (item, index) {
		 $.ajax({
			url: 'ajax_events.php?inviteByEmailToEvent=1',
			type: "POST",
			global:false,
			data: {'eventid':eventid,'email':item,'csrf_token':teleskopeCsrfToken},
			success: function (data) {
				if (data == 1) {
					invitesSent += item + ', ';
				} else if (data == 2) {
					againSent += item + ', ';
				} else if (data == 0) {
					invitesFailed += item + ', ';
				}
			},
			 error: function(e) {
				 invitesFailed += item + ', ';
			 }
		}).always( function fn(){
			itemCurrentlyProcessing++;
			if (itemCurrentlyProcessing > noOfEmailsToSend) {
				$("body").css("cursor", "default");
			   $(".progress_status").html("<i class='fa fa-check-circle' aria-hidden='true'></i> <?=gettext('Completed');?> ");
			   setTimeout(function () {
				   $("#inviteSent").html(invitesSent.replace(/,\s*$/, ""));
				   if (invitesFailed) {
					   $("#inviteFailed").html(invitesFailed.replace(/,\s*$/, ""));
				   }
				   if (againSent) {
					   $("#againSent").html(againSent.replace(/,\s*$/, ""));
				   }
				   $("#close_show_progress_btn").show();
				   $(".progress_done").show();
			   }, 250);
		   } else {
				let p = Math.round((itemCurrentlyProcessing / noOfEmailsToSend) * 100);
				$(".progress_status").html("<?=gettext('Processing');?> " + itemCurrentlyProcessing + "/" + noOfEmailsToSend);
				$('div#prgress_bar').width(p + '%');
			}
		});
	});
}

function closeEventInvitesStats(){
	$("#inviteSent").html('');
	$("#inviteFailed").html('');
	$("#againSent").html('');
	$('#progress_bar').hide();
	$(".progress_done").hide();
}

function newAnnouncement(gid,global){
	global = (typeof global !== 'undefined') ? global : 0;
	$.ajax({
		url: 'ajax_announcement.php',
        type: "GET",
		data: 'newAnnouncement='+gid+'&global='+global,
        success : function(data){ 
			$('#ajax').html(data);
			
		}
	});
	setTimeout(function () {
		$('.redactor-statusbar').attr('id',"redactorStatusbar");
	}, 100);	
}

function newEventForm(gid, global,event_series_id = undefined){
	global = (typeof global !== 'undefined') ? global : 0;
	let endpoint = 'ajax_events.php';
	let parms = {'getEventCreateUpdateForm':gid,'global':global,event_series_id};
	
	$.ajax({
		url: endpoint,
		type: "GET",
		data: parms,
		success : function(data){ 
			$('#loadCompanyDisclaimerModal').hide();
			$('#ajax').html(data);
		}
	});
}

function updateComment(i){
	let comment = $("#"+i).val();
	let postid =  $("#post"+i).val();
	$.ajax({
		url: 'ajax_announcement.php?updateComment='+i,
        type: "POST",
		data: {'comment':comment,'postid':postid},
        success : function(displayComment){
			$("#comment"+i).html(displayComment);
			$("#comment"+i).show();
			$("#updatecomment"+i).hide();
		}
	});

} 

function deleteComment(i,p){
	p = (typeof p !== 'undefined') ? p : 0;
	let postid =  $("#post"+i).val();
	if (p!=0){
		postid = p;
	}
	
	$.ajax({
		url: 'ajax_announcement.php?deleteComment='+i,
        type: "POST",
		data: {'postid':postid},
        success : function(data){
			jQuery("#container"+i).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
		}
	});

}

//--- Reminder email of event ----//
function sendReminderForm(eid){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_events.php',
        type: "GET",
		data: 'sendReminderForm=1&eventid='+eid,
        success : function(data){ 
			if(data==1){
				swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Something has gone wrong. Please try again later.'))?>",allowOutsideClick:false});
			}else{
				$('#loadAnyModal').html(data);
					
				$('#send_reminder').modal({
					backdrop: 'static',
					keyboard: false
				});			
			}
		}
	});
	jQuery(".confirm").popConfirm({content: ''});
}

//--- Reminder History ----//
function viewReminderHistory(eid){	
	$(document).off('focusin.modal');	
	$('.modal-backdrop').remove();	
	$.ajax({
		url: 'ajax_events.php',
        type: "GET",
		data: 'viewReminderHistory=1&eventid='+eid,
        success : function(data){ 
			$('#loadAnyModal').html(data);
			$('#view_reminder_history').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

//--- Reminder History ----//
function deleteReminderHistory(eid,rid){	    
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_events.php',
        type: "GET",
		data: 'deleteReminderHistory=1&reminderid='+rid+'&eventid='+eid,
        success : function(data){ 			
			let jsonData = JSON.parse(data);
			swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false});
			viewReminderHistory(eid);
			$('body').removeClass('modal-open');
			$('.modal-backdrop').remove();
			$(".confirm").popConfirm({content: ''});
		}
	});
}

//--process email reminder action -- //
function sendReminderEmail(i){	 
	$(document).off('focusin.modal');
	let formdata = $('#send_reminder_form')[0];
	let finaldata= new FormData(formdata);
	$.ajax({
		url: 'ajax_events.php?sendReminderEmail=1',
		type: 'POST',
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {			
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status == 1){
					$('#send_reminder').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();	
					swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
						viewReminderHistory(i);
					});								
				}else{
					swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false});
				}
					
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}		

			$('.modal-backdrop').remove();
		}
	});
}

//Send email review to test.
function sendReminderEmailReview(){
	$(document).off('focusin.modal');
	let formdata = $('#send_reminder_form')[0];
	let finaldata= new FormData(formdata);
	$.ajax({
		url: 'ajax_events.php?sendReminderEmailReview=1',
		type: 'POST',
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {		
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false});
				setTimeout(() => {
					$(".swal2-confirm").focus();   
				}, 300)			
			}
	});
}

function followUnfollowChapter(gid,cid,sel_no,maxc){
	
	$.ajax({
		url: 	'ajax.php?followUnfollowChapter=1',
		type: 	'POST',
		data: 	{ groupid:gid,chapterid:cid  },
		beforeSend: function(){
	        $('#follow-btn'+sel_no).html('<span class="glyphicon glyphicon-repeat loder"></span>');
	    },
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status == 1){
					if (sel_no==0) {
						for (let ii=0;ii<maxc;ii++) {
							$('#follow-btn'+ii).html(jsonData.val);
						}
					} else {
						$('#follow-btn'+sel_no).html(jsonData.val);
					}
				} else if(jsonData.status == 2){
					$('#follow-btn'+sel_no).html(jsonData.val);
					if (sel_no!=0) {
						$('#follow-btn0').html(jsonData.val);
					}
				} else {
					swal.fire({title:jsonData.title,text:jsonData.message});
					$('#follow-btn'+sel_no).html(jsonData.val);
				}
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
			}
		}
	});
}

function loadSurveyModal(i){
	$.ajax({
		url: 'ajax.php?loadSurveyModal=true',
		type: 'GET',
		data: {groupid:i},
		success: function(data) {
			if (data != 0){ 
				$('#survey_content').html(data);
				$('#showSurveyModal').modal({
					backdrop: 'static',
					keyboard: false
				});
				$('#showSurveyModal').css('overflow-y', 'auto')
			}
			
		}
	});
}

function updateGroupleadPriorityFrontEnd(priority,gid){
	let finalData  = new FormData();
	finalData.append("prioritylist",priority);
	finalData.append("gid",gid);
	$.ajax({
		url: 'ajax.php?updateGroupleadPriorityFrontEnd=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {

		}
	});
}

function updateChapterleadPriorityFrontEnd(priority,gid,filterChapterId){
	let finalData  = new FormData();
	finalData.append("prioritylist",priority);
	finalData.append("gid",gid);
	finalData.append("filterChapterId",filterChapterId);
	$.ajax({
		url: 'ajax.php?updateChapterleadPriorityFrontEnd=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {

		}
	});
}

function updateChannelleadPriorityFrontEnd(priority,gid,filterChannelId){
	let finalData  = new FormData();
	finalData.append("prioritylist",priority);
	finalData.append("gid",gid);
	finalData.append("filterChannelId",filterChannelId);
	$.ajax({
		url: 'ajax.php?updateChannelleadPriorityFrontEnd=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {

		}
	});
}

function closeJoinGroupModal(g,i,m){
	$(document).off('focusin.modal');
	m = (typeof m !== 'undefined') ? m : '';
	if (i){
		let chapterids = $('#chapter_select_dropdown').val();
		if (chapterids == null){
			chapterids = [];
		}

		if (chapterids != null){
			$.ajax({
				url: 'ajax.php?closeJoinGroupModal=true',
				type: 'POST',
				data: { 'groupid':g,'chapterids':chapterids.length,memberUserid:m},
				success: function(data) {
					try {
						let jsonData = JSON.parse(data);
						swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
							$(".swal2-confirm").focus();
						});
					} catch(e) {
						if (m!=''){
							closeAllActiveModal();
							mangeGroupMemberList(g);
						} else {
							//location.reload();
							updateGroupJoinLeaveButton(g);
						}
					}
				}
			});

		} else {
			if (m!=''){
				this.closeAllActiveModal();
				mangeGroupMemberList(g);
			} else {
				//location.reload();
				updateGroupJoinLeaveButton(g);
			}
		}
	} else {
		if (m!=''){
			this.closeAllActiveModal();
			mangeGroupMemberList(g);
		} else {
			//location.reload();
			updateGroupJoinLeaveButton(g);
		}
	}
}

function manageChapterLeads(cid){
	$.ajax({
		url: 'ajax.php',
		type: 'GET',
		data: {manageChapterLeads:cid},
		success: function(data){
			if(data==1){
				swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Something has gone wrong. Please try again later.'))?>"});
			}else{
				$('#replace').html(data);
				$('#add-chapter').modal('show');
				jQuery(".confirm").popConfirm({content: ''});
			}
		}

	});
}

function deleteChapterLead(uid,cid,id){
	$.ajax({
		url: 'ajax.php',
		type:'POST',
		data:{deleteChapterLead:cid,userid:uid},
		success: function(data){
			if(data==1){
				$("#"+id).hide(500);
			}else{
				swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Something has gone wrong. Please try again later.'))?>"});
			}
		}
	});
}
function getAlbums(gid, cid, chid, page = 1) {
    window.location.hash = "albums";
    localStorage.setItem("manage_active", "manageDashboard");
    $('#upload_modal').modal('hide');
    $('body').removeClass('modal-open');
    $('.modal-backdrop').remove();
    var albumsPerPage = 10;
    $.ajax({
        url: 'ajax_albums.php',
        type: "GET",
        data: {
            getAlbums: gid,
            chapterid: cid,
            channelid: chid,
            page: page,
        },
		success: function (data) {
			var isFirstLoad = page == 1;
			if (isFirstLoad) {
				$("#ajax").html(data);
				$(".confirm").popConfirm({ content: '' });
			} else {
				var newAlbums = $(data).find('.highlight-block');
				$("#albumContainer").append(newAlbums);
			}
		
			// Retrieve the updated albumDataCount from the hidden input field
			var albumDataCountObj = $(data).find('#albumDataCount');
			var albumDataCount = albumDataCountObj.val()
		
			if (albumDataCount == albumsPerPage) {
				$('#loadMoreButton').show();
			} else {
				console.log("loadMoreButton is hidden");
				$('#loadMoreButton').hide();
			}
		}
		
    });   
}
function createUpdateAlbum(g,c,ch){
	$(document).off('focusin.modal');
	preventMultiClick(1);
	$('#upload_modal').modal('hide');
	$('body').removeClass('modal-open');
	$('.modal-backdrop').remove();
	var title = $('#album_title').val();
	if (title.trim().length === 0) {
		swal.fire({title: '<?= gettext("Error");?>',text:'<?= gettext("Title is a required field");?>',allowOutsideClick:false});
		preventMultiClick(0)
		return false;
	}

	let formdata = $("#albumForm")[0];
	let finaldata  = new FormData(formdata);
	finaldata.append('groupid',g);

	$.ajax({
		url: 'ajax_albums.php?createUpdateAlbum=1',
		type: 'POST',
		enctype: 'multipart/form-data',
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
		success : function(response) {
			try {
				let jsonData = JSON.parse(response);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						$('[data-dismiss="modal"]').trigger("click");
						getAlbums(g,c,ch);
					}
				});
		
			} catch(e) {
				swal.fire({title: '<?= gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
			}
		},
		error: function() {
			swal.fire({title: 'Error', text: "Error creating new album.",allowOutsideClick:false}).then(function (result) {
				getAlbums(g, c, ch);
				$('#new_album_modal').modal('hide');
				$('body').removeClass('modal-open');
				$('.modal-backdrop').remove();
			});
		}
	});
}

function deleteAlbumMedia(albumid, media_id, gid,cid,chid,index,album_media_array, backToGallery='') {

    let file_data = JSON.stringify({"action":"deleteAlbumMedia","album_id":albumid, "media_id":media_id});
    $.ajax({
        url: 'ajax_albums.php?albumDeleteAlbumMedia=1',
        type: "POST",
        data: {data: file_data,
            'groupid': gid,
            'chapterid': cid,
            'channelid': chid
        },
        success : function(album_data_response) {    
			//$('#album_viewer_container').remove();       
			swal.fire({title: 'Success',text:"<?= addslashes(gettext('Media deleted successfully.'))?>"}).then(function(result) {               
                //getAlbums(gid,cid,chid); //album_prev_item				
				let am_array = JSON.parse(album_media_array);
				if(am_array.length>1){
					let obj = document.getElementsByClassName("album_arrow");					
					if(obj[0].id == "album_next_item"){					
						index = parseInt(index);
					}else{
						index = parseInt(index)-1;										
					}				
					am_array = am_array.filter(function(elem){
						return elem != media_id; 
					})				
				 	viewAlbumMedia(albumid,index,am_array,gid,cid,chid,backToGallery);
				}else{								
					$("#album_close").trigger("click");
				}					
            });
        },
        error: function() {
            swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Something went wrong. Please try again.'))?>"});
        }
    });	
}

function setAlbumImageAsCover(albumid, media_id, gid,cid,chid) {

    let file_data = JSON.stringify({"action":"setAlbumMediaCover","album_id":albumid, "media_id":media_id});
    $.ajax({
        url: 'ajax_albums.php?albumSetAlbumMediaCover=1',
        type: "POST",
        data: {data: file_data,
            'groupid': gid,
            'chapterid': cid,
            'channelid': chid
        },
        success : function(album_data_response) {
            if (album_data_response) {
                album_data_response = JSON.parse(album_data_response);
                if ("success" == album_data_response.status) {
					swal.fire({title: 'Success',text:"<?= addslashes(gettext('Cover image is set.'))?>"});
                }
            }
        },
        error: function() {
            swal.fire({title: '<?= gettext("Error") ?>',text:"<?= addslashes(gettext('Something went wrong. Please try again.'))?>"}).then(function(result) {
                $('#album_viewer_container').remove();
                getAlbums(gid,cid,chid);
            });
        }
    });
}

function deleteAlbum(albumid, gid,cid,chid) {
    let file_data = JSON.stringify({"action":"deleteAlbum","album_id":albumid});

    $.ajax({
        url: 'ajax_albums.php?albumDeleteAlbum=1',
        type: "POST",
        data: {data: file_data },
        success : function(album_data_response) {
            if (album_data_response) {
                album_data_response = JSON.parse(album_data_response);
                if ("success" == album_data_response.status) {
                    swal.fire({title: '<?= gettext("Success") ?>',text:"<?= addslashes(gettext('Album deleted.'))?>"}).then(function(result) {
                        getAlbums(gid,cid,chid);
                    });
                } else {
                    swal.fire({title: '<?= gettext("Error") ?>', text: "<?= addslashes(gettext('Error deleting an album.'))?>"}).then(function (result) {
                        getAlbums(gid, cid, chid);
                    });
                }
            }
        },
        error: function() {
            swal.fire({title: '<?= gettext("Error") ?>', text: "<?= addslashes(gettext('Error deleting an album.'))?>"}).then(function (result) {
                getAlbums(gid, cid, chid);
            });
        }
    });
}

function getMediaLikesAndComments(albumid, album_media) {
    let file_data = JSON.stringify({"action":"getAlbumMediaLikesAndComments","media_id":album_media,"album_id":albumid});

    $.ajax({
        url: 'ajax_albums.php?albumGetAlbumMediaLikesAndComments=1',
        type: "POST",
        data: {data: file_data},
        success : function(album_data_response) {
           $('#album_comments_area').html(album_data_response);
		   $(".confirm").popConfirm({content: ''});
        },
        error: function() {
            swal.fire({title: '<?= gettext("Error") ?>',text:"<?= addslashes(gettext('Something went wrong. Please try again.'))?>"}).then(function(result) {
                window.location.reload();
            });
        }
    });
}


function viewAlbumMedia(albumid, media_id_index, album_media_array, g,ch,chnl, backToGallery) {
	
	$(".gallery-card-title").css({'position':'relative','z-index':'0'});
	backToGallery = (typeof backToGallery !== 'undefined') ? backToGallery : 0;
	$("#loadAnyModal").html('');
	// album_media_array is empty - call to get all images from album, and call itself with album_media_array set and media_id_index = 0
	if (typeof album_media_array === "undefined" || album_media_array.constructor !== Array) {

		let file_data = JSON.stringify({"action":"getAlbumMediaIDs","album_id":albumid});
		$.ajax({
			url: 'ajax_albums.php?albumGetAlbumMediaIDs=1',
			type: "POST",
			data: {data: file_data},
			success : function(album_data_response) {
				if (album_data_response) {
					album_data_response = JSON.parse(album_data_response);
					if ("success" == album_data_response.status) {
						$('#album_viewer_container').remove();
						viewAlbumMedia(albumid, 0, album_data_response.media_ids, g,ch,chnl, backToGallery);
					}
				}
			},
			error: function() {
				swal.fire({title: '<?= gettext("Error") ?>',text:"<?= addslashes(gettext('Something went wrong. Please try again.'))?>"}).then(function(result) {
					$('#album_viewer_container').remove();
					getAlbums(g,ch,chnl);
				});
			}
		});
	}
	// else if media_id is empty  - then call itself with album_media_array set and media_id_index = 0
	else if (typeof media_id_index === "undefined" || parseInt(media_id_index) < 0) {
		viewAlbumMedia(albumid, 0, album_media_array, g,ch,chnl, backToGallery);
	}
	// else show a specific image by media_id
	else {

		let file_data = JSON.stringify({"action":"getPreviewAlbumMedia","media_id":album_media_array[media_id_index],"album_id":albumid});

		$.ajax({
			url: 'ajax_albums.php?albumGetPreviewAlbumMedia=1',
			type: "POST",
			data: {data: file_data},
			success : function(album_data_response) {
				if (album_data_response) {
					album_data_response = JSON.parse(album_data_response);
					if ("success" == album_data_response.status) {

                        let album_viewer_container = $('<div id="album_viewer_container"></div>');

						let album_viewer_area = $('<div tabindex="0" id="album_viewer_area"></div>');
						album_viewer_area.html(album_data_response.widget_code);
						album_viewer_area.appendTo(album_viewer_container);
					
						// Add Album Carousel View Page Title
						let carousel_view_area_title = $(updatePageTitle(album_data_response.documentTitlee_short));

						carousel_view_area_title.appendTo(album_viewer_container);

							 // close button
							 let album_close = $('<button onclick="setFocusOnAlbumMenu()" aria-label="Close window" id="album_close" tabindex="0"  title="Close window" class="btn-no-style btn album-close" style="min-width:0px; padding:0px !important;"><i class="far fa-times"></i></button>');
							 album_close.bind('click', function(){
								 if (backToGallery == 1){
									 albumMediaGalleryView(albumid,ch,chnl);
								 } else if(backToGallery == 0){
									 getAlbums(g,ch,chnl);
								 }else{
									console.log("closed");
								 }
								 $('#album_viewer_container').remove();
								 $('.skip-to-content-link').show(); 
							 });

							album_close.appendTo(album_viewer_area);
							 let album_next_item = '';

						//For screen reading add hidden div to modal with notification.
						let mediaCount =  media_id_index + 1;
						$('#album_media_count').html('');						

						

						if (album_data_response.media_type == 'video'){
								let media_iframe = $('<button id="media_iframe"  class="btn-no-style btn" style="min-width:0px; padding:0px !important;" aria-label="get video iframe" tabindex="0"><i class="fa fa-code" aria-hidden="true"></i></button>');
							if (album_data_response.widget_code.indexOf('mp4') > 10) {
								media_iframe.bind('click', function () {
									getAlbumMediaIframe(albumid, album_media_array[media_id_index]);
								});
								media_iframe.appendTo(album_viewer_area);
							}
						}
						// if media type is image (not video) show "set as album cover"
						if ('image' == album_data_response.media_type && album_data_response.can_change_cover) {
							if (!album_data_response.is_cover) {
								let album_set_cover = $('<button id="album_set_cover" class="btn-no-style btn" tabindex="0" style="min-width:0; padding:0 !important;" aria-label="Set this photo as album cover" title="Set this photo as album cover"><i class="far fa-image"></i></button>');
								album_set_cover.bind('click', function () {
									setAlbumImageAsCover(albumid, album_media_array[media_id_index], g, ch, chnl);
								});
								album_set_cover.appendTo(album_viewer_area);
							}
						}

						// delete image button
						let album_delete_media = $('<div  id="album_delete_media" ></div>');
						let album_delete_media_link = $('<button tabindex="0" data-toggle="popover" aria-label="Delete this media" class="confirm btn-no-style btn delete_media_img" data-confirm-noBtn="No" style="min-width:0px; padding:0px !important;" data-confirm-yesBtn="Yes" title="<?= addslashes(gettext("Are you sure you want to delete this media?"))?>" onclick=deleteAlbumMedia(\'' + albumid + '\',\'' + album_media_array[media_id_index] + '\',\'' + g + '\',\'' + ch + '\',\'' + chnl + '\',\'' + media_id_index + '\',\'' + JSON.stringify(album_media_array) + '\',\'' + backToGallery + '\')><i class="far fa-trash"></i></button>');

						album_delete_media_link.popConfirm({content: ''});
						album_delete_media_link.appendTo(album_delete_media);

						if (album_data_response.can_delete) {
							album_delete_media.appendTo(album_viewer_area);
						}		
											
						/*album_delete_media_link.on('hidden.bs.popover', function () {
							album_delete_media_link.focus();
						});*/
						
						// download link
						let downloadlink = $('<a tabindex="0" aria-label="download this media" id="album_media_download" href="'+album_data_response.media_download_url+'" class="btn js-download-link" style="min-width:0px; padding:0px !important;" ><i class="fa fa-download" style="color:#fff;" aria-hidden="true"></i></a>');
						downloadlink.appendTo(album_viewer_area);

						// if is set prev item - show prev arrow
						let album_prev_item = '';
						if (media_id_index-1 >= 0) {
								album_prev_item = $('<button aria-label="Previous Media" id="album_prev_item" class="album_arrow btn-no-style" title="Previous Media" tabindex="0"><i role="button" class="far fa-chevron-left"></i></button>');
							album_prev_item.bind('click', function(){
								viewAlbumMedia(albumid, media_id_index-1, album_media_array, g,ch,chnl, backToGallery);
							});
							album_prev_item.appendTo(album_viewer_area);
						}				

						// if is set next item - show next arrow
						if (media_id_index+1 < album_media_array.length) {
							album_next_item = $('<button aria-label="Next Media" id="album_next_item" class="album_arrow btn-no-style" style="min-width:0px; padding:0px !important;" title="Next Media"  tabindex="0"><i role="button" class="far fa-chevron-right"></i></button>');

						album_next_item.bind('click', function(){
							viewAlbumMedia(albumid, media_id_index+1, album_media_array, g,ch,chnl, backToGallery);
						});
						album_next_item.appendTo(album_viewer_area);
					}

					

						$('<div id="album_comments_area"></div>').appendTo(album_viewer_container);

						$('#album_viewer_container').remove();
						album_viewer_container.appendTo('body');
						getMediaLikesAndComments(albumid, album_media_array[media_id_index]);
						album_close.focus();
						
							setTimeout(() => {
								if (album_next_item && album_prev_item == '') {
									album_close.focus(); // starting focus
								}else if(album_next_item && album_prev_item){
									$(album_next_item).focus(); // focus on next item arrow
								}else if(album_next_item == '' && album_prev_item){
									$(album_prev_item).focus();
								}else{
									console.log("no condition met");
									album_close.focus();
								}

								$("#album_media_count").attr("role","status");
								$("#album_media_count").attr("aria-live","polite");
								let mCount = "you are on media "+mediaCount;
								$("#album_media_count").append(mCount);	

							}, 500);	
							
						
							
						return false;
					}
				}
				swal.fire({title: '<?= gettext("Error") ?>',text:"<?= addslashes(gettext('Something went wrong. Please try again.'))?>"}).then(function(result) {
					//window.location.reload();
				});
			},
			error: function() {
				swal.fire({title: '<?= gettext("Error") ?>',text:"<?= addslashes(gettext('Something went wrong. Please try again.'))?>"}).then(function(result) {
					window.location.reload();
				});
			}
		});

	}

	
}

function setFocusOnAlbumMenu(){
	$('#getAlbumsMenu').focus(); 
}

function getAlbumMediaIframe(a,m){
	$.ajax({
		url: 'ajax_albums.php?getAlbumMediaIframe=1',
		type: "GET",
		data: {'albumid': a,'mediaid':m},
		success : function(data) {
			try {
                let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message});
            } catch(e) { 
				$('#modal_over_modal').html(data);
				$('#videoIframeModal').modal({
					backdrop: 'static',
					keyboard: false
				});		
			}

		}

	})
}

function getComonGroupResources(gid,ch,chn){
	window.location.hash = "resources";
	localStorage.setItem("manage_active", "manageLeadResources");
	localStorage.setItem("is_resource_lead_content", 0);
	$.ajax({
		url: 'ajax_resources.php',
        type: "GET",
		data: {getGroupResources:gid,chapterid:ch,channelid:chn},
        success : function(data) {
			$('#ajax').html(data);
			jQuery(".deluser").popConfirm({content: ''});
		}
	});
}

function getGroupResources(gid,ch,chn){

	let is_resource_lead_content = 0;
	if (localStorage.getItem("is_resource_lead_content") !== null){
		is_resource_lead_content = localStorage.getItem("is_resource_lead_content");
	}
	if (is_resource_lead_content == 1) {
		manageLeadResources(gid);
	} else {
		getComonGroupResources(gid,ch,chn);
		localStorage.setItem("is_resource_lead_content", 0);
	}
}

function addUpdateGroupResource(id,update,ch,chnl,is_resource_lead_content){
	$(document).off('focusin.modal');
	let parent_id = $('#parent_id').val();
	let resource_type = $('#resource_type').val();
	let formdata = $('#resourceForm')[0];
	let finaldata  = new FormData(formdata);

	if (!$('#title').val()){
		swal.fire({title: '<?= gettext("Error") ?>',text:'<?= gettext("Resource name is required!");?>',allowOutsideClick:false});
		setTimeout(() => {
			$(".swal2-confirm").focus();
		}, 500)
		return false;
	}else if ($("#title").val().length <= 2) { 
		swal.fire({title: '<?= gettext("Error") ?>',text:'<?= gettext("Resource name is too short. The minimum required length is 3 characters.");?>',allowOutsideClick:false});
		setTimeout(() => {
			$(".swal2-confirm").focus();
		}, 500)
		return false;
	} 
	if (resource_type == 2){ 
		let resource_file = $("input[name=resource_file_data]").val();
		if (resource_file){
			let media  = JSON.parse(resource_file);
			let base64 = media.image;
			let type   = media.type;
			let block  = base64.split(";");

			// get the real base64 content of the file
			let realData = block[1].split(",")[1];

			// Convert to blob
			let blob = b64toBlob(realData, type);

			const fileData = new File([blob],media.name,{ type: type });
			finaldata.append('resource_file',fileData);

      if (update) {
        if (!$(formdata).find('input[name="overwrite_resource_file"]').is(':checked')) {
          Swal.fire({
            title: '<?= gettext("Error") ?>',
            text: '<?= gettext("Please select the checkbox to confirm you want to overwrite the resource file") ?>',
          });

          setTimeout(() => {
            $(".swal2-confirm").focus();
          }, 500);

          return false;
        }
      }
		} else if (!update) {
			swal.fire({title: '<?= gettext("Error")?>',text:'<?= gettext("Please select a file to upload!");?>',allowOutsideClick:false});
			setTimeout(() => {
				$(".swal2-confirm").focus();
			}, 500)
			return false;
		}
	}
	finaldata.append("parent_id",parent_id);
	let section = "";
	if (typeof $('#group_chapter_channel_id').find(':selected').data('section') !== 'undefined') {
		section = $('#group_chapter_channel_id').find(':selected').data('section');
	}
	finaldata.append("section",section);
	finaldata.append("is_resource_lead_content",is_resource_lead_content);
	
	$("#resource_file").prop('disabled', true);
	$("#attachment").prop('disabled', true);
	preventMultiClick(1);
	$.ajax({
        url: 'ajax_resources.php?addUpdateGroupResource='+id,
        type: 'POST',
		enctype: 'multipart/form-data',
        data: finaldata,
        processData: false,
        contentType: false,
        cache: false,
		success: function(data) {
			try {
                let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status>0){
						$('#resourceModal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();

						let dataVal = jsonData.val;
						getResourceChildData(dataVal.parent_id, dataVal.groupid,ch,chnl);
					}
                });
            } catch(e) { swal.fire({title: '<?= gettext("Error") ?>', text: "<?= addslashes(gettext('Unknown error.'))?>",allowOutsideClick:false}); }
			setTimeout(() => {
				$(".swal2-confirm").focus();
			}, 500)
		}
	});
}
function getResourceChildData(fid,gid,ch,chnl){
	let is_resource_lead_content = 0;
	if (localStorage.getItem("is_resource_lead_content") !== null){
		is_resource_lead_content = localStorage.getItem("is_resource_lead_content");
	}
	$.ajax({
		url: 'ajax_resources.php?getResourceChildData='+gid,
        type: "GET",
		data:{'folderid':fid,'chapterid':ch,'channelid':chnl,'is_resource_lead_content':is_resource_lead_content},
        success : function(data) {
			$('#resource_data').html(data);
			jQuery(".deluser").popConfirm({content: ''});
		}
	});
}

function deleteResource(g,r,i, type){
	$.ajax({
		url: 'ajax_resources.php?deleteResource=1',
        type: "POST",
		data:{'groupid':g,'resource_id':r},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status == 1){
					let elementId = "#" + type + "-"+i;
						jQuery(elementId).animate({backgroundColor: "#fbc7c7"}, "fast").animate({opacity: "hide"}, 2000);
				} else {
					swal.fire({title:jsonData.title,text:jsonData.message});
				}
			} catch(e) { swal.fire({title: '<?= gettext("Error") ?>', text: "<?= addslashes(gettext('Unknown error.'))?>"}); }
		}
	});
}

function updateUsersEmailNotifictionSetting(v,m,t){

	$.ajax({
		url: 'ajax.php?updateUserEmailSetting=1',
        type: "POST",
		data:{'value':v,'memberid':m,'key':t},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				swal.fire({title: '<?= gettext("Error") ?>', text: "<?= addslashes(gettext('Unknown error.'))?>"});
			}
		}
	});
}

function getGroupNewsletters(i) {
	localStorage.setItem("manage_active", "getGroupNewsletters");

	let state_filter = '';
	if (localStorage.getItem("state_filter") !== null){
		state_filter = localStorage.getItem("state_filter");
	}
	let erg_filter = '';
	if (localStorage.getItem("erg_filter") !== null){
		erg_filter = localStorage.getItem("erg_filter");
	}
	let erg_filter_section = '';
	if (localStorage.getItem("erg_filter_section") !== null){
		erg_filter_section = localStorage.getItem("erg_filter_section");
	}
	let year_filter = '';
	if (localStorage.getItem("year_filter") !== null){
		year_filter = localStorage.getItem("year_filter");
	}

	$.ajax({
		url: 'ajax_newsletters.php',
        type: "GET",
		data: 'getGroupNewsletters='+i+'&state_filter='+state_filter+'&erg_filter='+erg_filter+'&year_filter='+year_filter+'&erg_filter_section='+erg_filter_section,
        success : function(data) {
			$('#ajax').html(data);
			jQuery(".deluser").popConfirm({content: ''});
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
	});
}
function openReviewNewsletterModal(g,i){
	
	$.ajax({
		url: 'ajax_newsletters.php?openReviewNewsletterModal='+g,
        type: "GET",
		data: {'newsletterid':i},
        success : function(data) {
			$("#review_or_publish_modal").html(data);
			$('#general_email_review_modal').modal({
				backdrop: 'static',
				keyboard: false
			});			
			$('.selectpicker').multiselect({
				includeSelectAllOption: true,
			});
			$('.multiselect-container input[type="checkbox"]').each(function(index,input){
				$(input).after( "<span></span>" );
			});
			
		}
	});
}

function openPublishNewsletterModal(g,i){
	$.ajax({
		url: 'ajax_newsletters.php?openPublishNewsletterModal='+g,
        type: "GET",
		data: {'newsletterid':i},
        success : function(data) {
			$("#review_or_publish_modal").html(data);
			$('#general_schedule_publish_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}



function previewNewsletter(g,i){
	$.ajax({
		url: 'ajax_newsletters.php?previewNewsletter='+g,
        type: "GET",
		data: {'newsletterid':i},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message});
            } catch(e) {
				$("#loadAnyModal").html(data);
				$('#previewNewsletter').modal({
					backdrop: 'static',
					keyboard: false
				});				
            }
		}
	});	
}

function openUnPublishNewsletterModal(g,n) {
	$.ajax({
		url: 'ajax_newsletters.php?openUnPublishNewsletterModal=1',
        type: "GET",
		data: {'groupid':g,'newsletterid':n},
        success : function(data) {
			$("#loadAnyModal").html(data);
			$('#unpublishNewsletter').modal({
			backdrop: 'static',
			keyboard: false
			});
		}
	});
}

function unpublishNewsletter(g,n) {
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_newsletters.php?unPublishNewsletter=1',
        type: "GET",
		data: {'groupid':g,'newsletterid':n},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status == 1){					
					if (jsonData.message){
						swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function (result) {
							$('#unpublishNewsletter').modal('hide');
							getGroupNewsletters(g);
						});
					}
				} else {
					swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false});
				}
            } catch(e) {
                swal.fire({title: '<?= gettext("Error") ?>', text: "<?= addslashes(gettext('Unknown error.'))?>",allowOutsideClick:false});
            }
		}
	});
}

function sendNewsletterToReview(i){
	$(document).off('focusin.modal');
	let formdata =	$('#general_email_review_form').serialize();
	$.ajax({
		url: 'ajax_newsletters.php?sendNewsletterToReview='+i,
        type: "POST",
		data: formdata,
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status == 1){
					$('#general_email_review_modal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					$('#review_or_publish_modal').html('');
					if (jsonData.message){
						swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function (result) {
							getGroupNewsletters(i);
						});
					}
				} else {
					swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false});
				}
            } catch(e) {
                swal.fire({title: '<?= gettext("Error") ?>', text: "<?= addslashes(gettext('Unknown error.'))?>",allowOutsideClick:false});
            }
		}
	});
}

function publishNewsletter(i){
	$(document).off('focusin.modal');
	let formdata =	$('#schedulePublishForm').serialize();
	$.ajax({
		url: 'ajax_newsletters.php?publishNewsletter=true',
		type: "POST",
		data: formdata,
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status>0){
					$('#general_schedule_publish_modal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					$('#review_or_publish_modal').html('');
				}
                swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status>0){
						getGroupNewsletters(i);
					}
                });
            } catch(e) {  swal.fire({title: '<?= gettext("Error") ?>', text: "<?= addslashes(gettext('Unknown error.'))?>",allowOutsideClick:false}); }
		}
	});
}

function confirmDelete(method,parameters){
	Swal.fire({
		title: 'Are you sure?',
		text: "You won't be able to revert this!",
		showCancelButton: true,
		confirmButtonColor: '#d33',
		cancelButtonColor: '#0077B5',
		confirmButtonText: 'Yes, delete it!'
	  }).then(function(result)  {
		if (result.value) {
			method(parameters);
		}
	  });
}

function deleteNewsletter(g,i){
	$.ajax({
		url: 'ajax_newsletters.php?deleteNewsletter='+g,
        type: "POST",
		data: {'newsletterid':i},
        success : function(data) {
			$('#table-event').DataTable().ajax.reload();
		}
	});
}

function cancelNewsletterPublishing(g,i){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_newsletters.php?cancelNewsletterPublishing='+g,
		type: "POST",
		data: {'newsletterid':i},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status>0){
                    	getGroupNewsletters(g);
					}
                });
            } catch(e) { swal.fire({title: '<?= gettext("Error") ?>', text: "<?= addslashes(gettext('Unknown error.'))?>",allowOutsideClick:false}); }
		}
	});
}

function initUpdateNewsletter(g,i){
	$.ajax({
		url: 'ajax_newsletters.php?initUpdateNewsletter='+g,
        type: "GET",
		data: {'newsletterid':i},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message});
            } catch(e) {
                $('#ajax').html(data);
				// Fix for newsletters end content being hidden. 
				var renderedIframe = document.querySelector('iframe');
				renderedIframe.onload = function () {
					var groupedContentTables = renderedIframe.contentDocument.querySelector('td');
					var adjustedHeight = groupedContentTables.clientHeight;
					renderedIframe.style.height = adjustedHeight + 'px';
				}
            }
			$(".rex-editor").removeAttr("role");
			$(".rex-editor").attr("title","presentation");

		}
	});	
}

function getGroupSurveys(g,f){
	localStorage.setItem("manage_active", "getGroupSurveys");
	f = (typeof f !== 'undefined') ? f : 0;
	if (f){
		var byState = $("#filterByState").val();
		var groupStateType = $('#filterByGroup').find(':selected').data('section');
		var groupState = $('#filterByGroup').val();
	} else {
		var byState = localStorage.getItem("state_filter");
		var groupStateType = localStorage.getItem("erg_filter_section");
		var groupState = localStorage.getItem("erg_filter");
	}

	if (typeof byState === 'undefined' || byState === null){
		byState = '';
	} else {
		localStorage.setItem("state_filter", byState);
	}
	if (typeof groupState === 'undefined'  || groupState === null ){
		groupState ='';
	} else {
		localStorage.setItem("erg_filter", groupState);
	}
	if (typeof groupStateType === 'undefined' || groupStateType === null){
		groupStateType ='';
	} else {
		localStorage.setItem("erg_filter_section", groupStateType);
	}

	$.ajax({
		url: 'ajax.php?getGroupSurveys='+g,
        type: "GET",
		data:{state_filter:byState, erg_filter: groupState, erg_filter_section:groupStateType},
		success : function(data) {		
			$('#ajax').html(data);
			$(".confirm").popConfirm({content: ''});
			$('#filterByState').focus();
		}
	});	
}

function getAboutUsFields(g){
	let id = $("#groupChapterId").val();
	let section = $('#groupChapterId').find(':selected').data('section');
	$.ajax({
		url: 'ajax.php?getAboutUsFields='+g+'&id='+id+'&section='+section,
        type: "GET",
		success : function(data) {		
			try {
				let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					updateAboutUsData(g);
				});
				
            } catch(e) {				
                $('#ajaxFields').html(data);
            }
			
		}
	});
}

function getGroupMembersListTable(g){

	$.ajax({
		url: 'ajax.php?getGroupMembersListTable=1',
		type: 'GET',
		data: {groupid:g},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) { 

				$("#list_view").html(data);
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
			}
		}
	});

}

function membersByChapters(g,c,f){
	let section = $('#chapter').find(':selected').data('section');

	var notOrderable = [1,2,5];
	if (f == 5){
		notOrderable = [1,4];
	} else if(f == 4){
		notOrderable = [3];
	}
	else if(f == 3){
		notOrderable = [];
	}

	$("#table-members-server").DataTable().clear().destroy();
	let dtable = $('#table-members-server').DataTable({
		serverSide: true,
		bFilter: true,
		bInfo : false,
		bDestroy: true,
		order: [[ 0, "asc" ]],
		language: {
				searchPlaceholder: "name or email",
				url: '../vendor/js/datatables-lang/i18n/en-gb.json'
			},
		columnDefs: [
			{ targets: notOrderable, orderable: false }
			],
		ajax:{
				url :"ajax.php?getGroupMembersList="+g+"&chapter="+c+'&section='+section, // json datasource
				type: "post",  // method  , by default get
				error: function(data){  // error handling
					$(".table-grid-error").html("");
					$("#table-members-server").append('<tbody class="table-grid-error"><tr><th colspan="6">No data found!</th></tr></tbody>');
					$("#table-grid_processing").css("display","none");
				},complete : function(){
					$('.initial').initial({
						charCount: 2,
						textColor: '#ffffff',
						color: window.tskp?.initial_bgcolor ?? null,
						seed: 0,
						height: 30,
						width: 30,
						fontSize: 15,
						fontWeight: 300,
						fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
						radius: 0
					});
				}
			},

	});
	$(".dataTables_filter input")
	.unbind()
	.bind("input", function(e) { 
		if(this.value.length >= 3 || e.keyCode == 13) {
			dtable.search(this.value).draw();
		}
		if(this.value == "") {
			dtable.search("").draw();
		}
	});
		
}
function groupLeadRoleModal(g,i){
	$.ajax({
		url: 'ajax.php?groupLeadRoleModal='+g+'&id='+i,
        type: "GET",
		success : function(data) {		
			$('#lead_form_contant').html(data);
			$('#lead_form_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}
function searchUsers(value){
	delayAjax(function(){
		if(value.length >= 3){
			$.ajax({
				type: "GET",
				url: "ajax.php",
				data: {
					'search_keyword_user' : value
				},
				success: function(response){
					if (response.includes('Internal Server Error')){
						showSweetAlert("","Internal server error. Please try later.",'error');
					} else {
						$("#show_dropdown").html(response);
						let myDropDown=$("#user_search");
						let length = $('#user_search> option').length;
						myDropDown.attr('size',length);
					}
				}
			});
		}
	}, 500 );
}
function updateGroupLeadRole(g){
	$(document).off('focusin.modal');
	let userid = $("#user_search").val();
	let userid2 = $("#user_search2").val();
	let role_type = $("#role_type").val();
	if (userid2 == '') {
		swal.fire({title: '<?= gettext("Error");?>',text:"<?= gettext('Please select a user');?>",allowOutsideClick:false});
	} else if (userid == '') {
		swal.fire({title: '<?= gettext("Error");?>',text:"<?= gettext('Please select a user');?>",allowOutsideClick:false});
	} else if (role_type == '') {
		swal.fire({title: '<?= gettext("Error");?>',text:"<?= gettext('Please select a role');?>",allowOutsideClick:false});
	} else {
		let formdata =	$('#group_lead_role_form').serialize();
		// preventMultiClick(1);
		$.ajax({
			url: 'ajax.php?updateGroupLeadRole='+g,
			type: "POST",
			data: formdata,
			success : function(data) {
				try {
					let jsonData = JSON.parse(data);
					if (jsonData.status == 1) {	
						setTimeout(() => {
							$(".swal2-confirm").focus();
						}, 500)
						$('#lead_form_modal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();					
						swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {							
							let leadId = jsonData.val;	
							if(leadId){				
							$('#grouplead_'+leadId).focus();
								}else{
								$('.lead-button button').focus();
							}
						});
						mangeGroupLeads(g);
					} else {
						swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false});
					}
				} catch(e) {
					swal.fire({title: '<?= gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
				}
			}
		});
	}

	
}
function openChapterLeadRole(g,c,i){
	$.ajax({
		url: 'ajax.php?openChapterLeadRole='+g+'&cid='+c+'&id='+i,
        type: "GET",
		success : function(data) {		
			$('#lead_form_contant').html(data);
			$('#chapter_lead_form_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

// Search user for lead
function searchUsersToLeadChapter(g,c,k){
	delayAjax(function(){
		if(k.length >= 3){
			$.ajax({
				type: "GET",
				url: "ajax.php?search_users_to_lead_chapter=1",
				data: {'gid':g,'cid':c,'keyword':k},
				success: function(response){
					$("#show_dropdown").html(response);
					let myDropDown=$("#user_search");
					let length = $('#user_search> option').length;
					myDropDown.attr('size',length);
				}
			});
		}
	}, 500 );
}

function updateChapterLeadRole(g){
	$(document).off('focusin.modal');
	let userid2 = $("#user_search2").val();
	let userid = $("#user_search").val();
	let role_type = $("#role_type").val();
	let chapterid = $("#chapterid").val();

	if (userid2 == '') {
		swal.fire({title: '<?= gettext("Error");?>',text:"<?= gettext('Please select a user');?>",allowOutsideClick:false});
	} else if (!userid || userid == '') {
		swal.fire({title: '<?= gettext("Error");?>',text:"<?= gettext('Please select a user');?>",allowOutsideClick:false});
	} else if (role_type == '') {
		swal.fire({title: '<?= gettext("Error");?>',text:"<?= gettext('Please select a role');?>",allowOutsideClick:false});
	} else if (chapterid==''){
		swal.fire({title: '<?= gettext("Error");?>',text:"<?= gettext('Please select a chapter');?>",allowOutsideClick:false});
	}else {
		let formdata =	$('#chapter_lead_role_form').serialize();
		preventMultiClick(1);
		$.ajax({
			url: 'ajax.php?updateChapterLeadRole='+g,
			type: "POST",
			data: formdata,
			success : function(data) {
				try {
					let jsonData = JSON.parse(data);
					$('#chapter_lead_form_modal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();					
					swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false});
					mangeChapterLeads(g);
				} catch(e) {
					swal.fire({title: '<?= gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
				}
			}
		});
	}
}

function deleteGroupLeadRole(r,g,i){
	$.ajax({
		url: 'ajax.php?deleteGroupLeadRole=1',
		type: "POST",
		data: {'groupid':g,'leadid':i},
		success : function(data) {
			jQuery("#"+r).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
		}
	});
}

function deleteChapterLeadRole(r,g,c,i){
	$.ajax({
		url: 'ajax.php?deleteChapterLeadRole=1',
		type: "POST",
		data: {'groupid':g,'chapterid':c,'leadid':i},
		success : function(data) {
			jQuery("#"+i).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
		}
	});
}

function manageDashboard(g){
	localStorage.setItem("manage_active", "manageDashboard");
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: 'manageDashboard='+ g,
        success : function(data) {
			$('#ajax').html(data);
			$(".deluser").popConfirm({content: ''});
		}
	});
}

function mangeChapterLeads(i) {
	let chapter_filter = $('#chapter_filter').val();
	chapter_filter = (typeof chapter_filter !== 'undefined') ? chapter_filter : '';
	$.ajax({
		url: 'ajax.php',
        type: "GET",	
		data: 'mangeChapterLeads='+i+'&chapter_filter='+chapter_filter,
        success : function(data) {
			$('#chapterLeads').html(data);
			$(".deluser").popConfirm({content: ''});
		}
	});
}

function mangeChannelLeads(i) {
	let channel_filter = $('#channel_filter').val();
	channel_filter = (typeof channel_filter !== 'undefined') ? channel_filter : '';
	$.ajax({
		url: 'ajax.php',
        type: "GET",	
		data: 'mangeChannelLeads='+i+'&channel_filter='+channel_filter,
        success : function(data) {
			$('#chanelLeads').html(data);
			$(".deluser").popConfirm({content: ''});
		}
	});
}


function mangeGroupMemberList(i) {
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: 'mangeGroupMemberList='+ i,
        success : function(data) {
			$('#members').html(data);
			$(".deluser").popConfirm({content: ''});
		}
	});
}

function manageNewsletterAttachments(i,n) {
	$.ajax({
		url: 'ajax_newsletters.php',
        type: "GET",
		data: 'manageNewsletterAttachments='+ i+'&id='+n,
        success : function(data) {
			$('#review_or_publish_modal').html(data);
			$('#newsletterAttachmeentModal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$(".deluser").popConfirm({content: ''});
		}
	});
}

function upoadNewsLetterAttachments(g,n){
	$(document).off('focusin.modal');
	let formdata = $('#newsletterAttachmentForm')[0];
	let finaldata  = new FormData(formdata);
	finaldata.append("groupid",g);
	finaldata.append("newsletterid",n);
	$.ajax({
        url: 'ajax_newsletters.php?upoadNewsLetterAttachments=1',
        type: 'POST',
		enctype: 'multipart/form-data',
        data: finaldata,
        processData: false,
        contentType: false,
        cache: false,
		success: function(data) {
			$(".add_button").show();
			$('#btn_close').focus();			
			try {
				let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false});
            } catch(e) {
                swal.fire({title: '<?=gettext("Success");?>',text: "<?=gettext('Attachment uploaded successfully.');?>",allowOutsideClick:false});
				$("#attachment_contents").html(data);
            }			
		}
	});
}

function deletNewsLetterAttachment(g,n,i){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_newsletters.php?deletNewsLetterAttachment=1',
		type: "POST",
		data: {'groupid':g,'newsletterid':n,'attachment_id':i},
		success : function(data) {
			$('#btn_close').focus();
			swal.fire({title: '<?=gettext("Success");?>',text: "<?=gettext('Attachment deleted successfully.');?>",allowOutsideClick:false});
			$("#attachment_contents").html(data);
			
		}
	});
	setTimeout(() => {
		$(".swal2-confirm").focus();
	}, 500)
}

function manageCommunicationsTemplates(i) {
	localStorage.setItem("manage_active", "manageCommunicationsTemplates");
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: 'manageCommunicationsTemplates='+ i,
        success : function(data) {
			$('#ajax').html(data);
			$(".deluser").popConfirm({content: ''});
		}
	});
	$("#emails_li_menu").addClass("submenuActive");
}

function loadNewCommunicationForm(g,c,t) {

	let chapterid = $("#newsletter_chapter").val();
	let trigger = $("#communication_trigger").val();

	if (chapterid !='' && trigger!=''){
		$.ajax({
			url: 'ajax.php',
			type: "GET",
			data: 'loadNewCommunicationForm='+ g+'&chapterid='+c+'&trigger='+t,
			success : function(data) {
				$('#action_button').html(data);
				$(".deluser").popConfirm({content: ''});
			}
		});

	} else {
		if (chapterid !='' && trigger=='') {
			swal.fire({title: '<?=gettext("Alert");?>',text: "<?= addslashes(gettext(' In order to submit, please select a communication trigger. A communication trigger will be the time/action that will cause the communication to send (ie. a welcome email will automatically be sent if the communication trigger is “on join”'))?>"});
		} else {
			swal.fire({title: '<?=gettext("Alert");?>',text: "<?= addslashes(gettext('Please select a group or chapter to continue.'))?>"});
		}
	}
}

function processCommunicationData(i) {
	let chapterid = $("#newsletter_chapter").val();
	let trigger = $("#communication_trigger").val();
	if (chapterid !='' && trigger!=''){
		let section = $('#newsletter_chapter').find(':selected').data('section');
		if (section == null) {
			section = chapterid;
		}
		$.ajax({
			url: 'ajax.php',
			type: "GET",
			data: 'processCommunicationData='+ i+'&chapterid='+chapterid+'&trigger='+trigger+'&section='+section,
			success : function(data) {
				$('#newsletter_chapter').prop('disabled',false);
				$('#communication_trigger').prop('disabled',false);
				$('#action_button').html(data);
				$(".deluser").popConfirm({content: ''});
			}
		});
	}
}

function loadUpdateCommunicationForm(g,i){
	$.ajax({
		url: 'ajax.php?loadUpdateCommunicationForm='+g,
        type: "GET",
		data: {'communicationid':i},
        success : function(data) {
			try {
				$('#newsletter_chapter').prop('disabled',true);
				$('#communication_trigger').prop('disabled',true);
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$('#action_button').html(data);
			}
		}
	});
}

function previewCommunicationTemplate(g,i){
	$.ajax({
		url: 'ajax.php?previewCommunicationTemplate='+g,
        type: "GET",
		data: {'communicationid':i},
        success : function(data) {

			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$("#loadAnyModal").html(data);
				$('#previewTemplate').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});	
}

function openDiscussionsConfigurationModal(g){
	$.ajax({
		url: 'ajax.php?manageDiscussionSettings=1',
		type: "GET",
		data: {'groupid':g},
		success : function(data) {			
			$("#loadAnyModal").html(data);
			$('#manageDiscussionSettings').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});	
}

function updateDiscussionsConfiguration(g){
	$(document).off('focusin.modal');
	let who_can_post = $("#who_can_post").val();
	let allow_anonymous_post=$("#allow_anonymous_post").is(":checked");
	let allow_email_publish=$("#allow_email_publish").is(":checked");
	$.ajax({
		url: 'ajax.php?updateDiscussionsConfiguration=1',
		type: "POST",
		data: {'groupid':g,'who_can_post':who_can_post,'allow_anonymous_post':allow_anonymous_post,'allow_email_publish':allow_email_publish},
		success : function(data) {
			$('#manageDiscussionSettings').modal('hide');
			if (data =='1'){
				swal.fire({title: '<?=gettext("Success");?>',text:"<?= gettext('Settings Updated Successfully.');?>",allowOutsideClick:false}).then(function(result) {
					$('.settings-btn').trigger('focus');	
				});;	
						
			}
		}
	});	
}

function activateCommunicationTemplate(g,i){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax.php?activateCommunicationTemplate='+g,
        type: "POST",
		data: {'communicationid':i},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						processCommunicationData(g);
					}
				});
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});	
}

function deactivateCommunicationTemplate(g,i){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax.php?deactivateCommunicationTemplate='+g,
        type: "POST",
		data: {'communicationid':i},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						processCommunicationData(g);
					}
				});
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});	
}


function deleteCommunicationTemplate(g,i){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax.php?deleteCommunicationTemplate='+g,
        type: "POST",
		data: {'communicationid':i},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						processCommunicationData(g);
					}
				});
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});	
}

function changeEventAttendenceType(i){
	if (i==1){
		$("#venue_div").show();
		$("#venue_info").show(); 
		$("#venue_room").show(); 
		$("#conference_div").hide();
		$("#eventDescriptionNote").hide()
		$("#web_conference_detail_note").hide()
		$("#enable_checkin_part").show().css('display', 'none');
	} else if (i==2 ){
		$("#venue_div").hide();
		$("#venue_info").hide(); 
		$("#venue_room").hide();
		$("#conference_div").show();
		$("#eventDescriptionNote").show();
		$("#web_conference_detail_note").show();
		$("#enable_checkin_part").show().css('display', 'inline-block');
	} else if (i==3) {
		$("#venue_div").show();
		$("#venue_info").show(); 
		$("#venue_room").show();
		$("#conference_div").show();
		$("#eventDescriptionNote").show();
		$("#web_conference_detail_note").show();
		$("#enable_checkin_part").show().css('display', 'inline-block');
	} else if (i==4){
		$("#venue_div").hide();
		$("#venue_info").hide(); 
		$("#venue_room").hide();
		$("#conference_div").hide();
		$("#eventDescriptionNote").hide();
		$("#web_conference_detail_note").hide()
		$("#enable_checkin_part").show().css('display', 'none');
	}
	setMaxParticipationState();
}

function setMaxParticipationState() {
	let eventType = $("#event_attendence_type").val();
  var container = $("#participation_limit_div");
  container.show().css('display', ((eventType == 1) || (eventType == 2) ||  (eventType == 3))? 'inline-block' : 'none');
  container.find('.js-max-inperson').prop("disabled", ((eventType == 1) || (eventType == 3))? false : true);
  container.find('.js-max-inperson-waitlist').prop("disabled", ((eventType == 1) || (eventType == 3))? false : true);
  container.find('.js-max-online').prop("disabled", ((eventType == 2) || (eventType == 3))? false : true);
  container.find('.js-max-online-waitlist').prop("disabled", ((eventType == 2) || (eventType == 3))? false : true);
  container.find('.js-participation-limit-input[data-unlimited="1"]').prop('disabled', true);
  container.find('.js-participation-limit-unlimited-chk').prop('checked', false);
  container.find('.js-participation-limit-unlimited-chk[data-unlimited="1"]').prop('checked', true);
  container.find('.js-participation-limit-unlimited-chk[data-published="1"]').prop('disabled', true);
}

function participationLimitUnlimitedToggle(event) {
  var chk = $(event.target);
  var input = $(chk.data('target'));

  if (chk.is(':checked')) {
    input
      .prop('disabled', true)
      .val(null)
      .removeAttr('placeholder');
  } else {
    input
      .prop('disabled', false)
      .val(chk.data('value'));
  }
}

function updateWaitlist(obj){
	let val = Math.abs(obj.value);
	let tid = obj.id + '_waitlist';
	let tv = Math.ceil(val /5);
	$("#"+obj.id).val(val); // Use abs value to remove -ve

  let waitlist_input = $("#"+tid);
  if (!waitlist_input.prop('disabled')) {
    waitlist_input.val(tv);
  }
}

function getGroupChaptersNewsletters(i,c,chid,year,page=1) {	
	window.location.hash = "newsletters";
	localStorage.setItem("manage_active", "getGroupNewsletters");
	$.ajax({
		url: 'ajax_newsletters.php',
        type: "GET",
		data: {
			getGroupChaptersNewsletters: i,
			chapterid: c,
			channelid: chid,
			year,
			page
		},
        success : function(data) {
			var container = $('#ajax');
			if (page === 1) {
				container.html(data);
			} else {
				var listingContainer = container.find('.js-newsletter-listing');

				var div = $(document.createElement('div'));
				div.html(data);

				var newsletters = div.find('.js-newsletter-row');

				var loadMoreBtn = container.find('.js-load-more-btn');

				if (newsletters.length < loadMoreBtn.data('per-page')) {
					loadMoreBtn.remove();
				} else {
					loadMoreBtn.data('page-number', page);
				}

				var lastNewsletterYear = listingContainer.find('.js-newsletter-year').last().data('newsletter-year');
				var lastNewsletterMonth = listingContainer.find('.js-newsletter-month').last().data('newsletter-month');

				var newsletterYearContainer = newsletters.first().find('.js-newsletter-year');
				if (newsletterYearContainer.data('newsletter-year') === lastNewsletterYear) {
					newsletterYearContainer.remove();
				}

				var newsletterMonthContainer = newsletters.first().find('.js-newsletter-month');
				if (newsletterMonthContainer.data('newsletter-month') === lastNewsletterMonth) {
					newsletterMonthContainer.remove();
				}

				listingContainer.append(div.html());
			}

			jQuery(".deluser").popConfirm({content: ''});
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
	});
}

function emaildialog(t,g,l){
	Swal.fire({
		title: t,
		showCancelButton: true,
		confirmButtonText: 'Yes',
		cancelButtonText: 'No',
		customClass: {
		  actions: 'my-actions',
		  cancelButton: 'order-1 right-gap',
		  confirmButton: 'order-2',
		},
	  }).then((result) => {
		if (result.value) {
			$.ajax({
				url: 'ajax_newsletters.php',
				type: "GET",
				data: 'emailMeNewsletter='+g+'&newsletterid='+l,
				success : function(data) {
					let jsonData = JSON.parse(data);
					swal.fire({title:jsonData.title,text:jsonData.message});
				}
			});
		}
	  });

}
// function emailMeNewsletter(g,l) {
// 	$.ajax({
// 		url: 'ajax_newsletters.php',
//         type: "GET",
// 		data: 'emailMeNewsletter='+g+'&newsletterid='+l,
//         success : function(data) {
// 			swal.fire({title: 'Success',text:"Newsletter emailed."});
// 		}
// 	});
// }

function uploadProfilePicture(){
	let profileINput = $("input[name=userProfile]").val();
	if (profileINput){
		let media  = JSON.parse(profileINput);
		let base64 = media.output.image;
		let type   = media.output.type;
		let block  = base64.split(";");

        // get the real base64 content of the file
        let realData = block[1].split(",")[1];

        // Convert to blob
		let blob = b64toBlob(realData, type);

		const file = new File([blob],media.input.name,{ type: type });

        let finaldata     = new FormData();
		finaldata.append('picture',file);
		$.ajax({
			url: 'ajax_upload.php?uploadProfilePicture=1',
			type: 'POST',
			enctype: 'multipart/form-data',
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success: function(data) {
				if (data =='0'){
					swal.fire({title: '<?= gettext("Error")?>',text:'<?= addslashes(gettext("Invalid file. The file size is too big (2MB maximum)."))?>'});
				} else if (data =='1') {
					swal.fire({title: '<?= gettext("Error")?>',text:'<?= addslashes(gettext("Maximum allowed size of profile picture is 2MB"))?>'});
				} else if (data =='2'){
					swal.fire({title: '<?= gettext("Error")?>',text:'<?= addslashes(gettext("Only .jpg,.jpeg,.png files are allowed"))?>'});
				} else { // success
					swal.fire({title: '<?= gettext("Success")?>',text:'<?= addslashes(gettext("Profile picture changed successfully."))?>'}).then(function(result) {
						location.reload();
					});
				}

				// for album sweet alert
				setTimeout(() => {
					$(".swal2-confirm").focus();
				}, 300)
			}
		});
	}else{
		swal.fire({title: '<?= gettext("Error")?>',text:'<?= addslashes(gettext("Please upload your profile image"))?>'});
	}
	
	setTimeout(() => {
		$(".swal2-confirm").focus();
	}, 300)
}


function initDeleteAccount(){
	let v = $("#confirm_delete_account").val();
	if (v =='Delete'){
		$("#action_button").html('<button class="btn btn-danger" onclick="confirmDeleteMyAccount()" >Submit</button>');
	} else {
		$("#action_button").html('<button class="btn btn-danger no-drop" disabled >Submit</button>');
	}
}
function confirmDeleteMyAccount(){
	let v = $("#confirm_delete_account").val();
	if (v =='Delete'){
		$.ajax({
			url: 'ajax.php?confirmDeleteMyAccount=1',
			type: 'POST',
			data :{'confirm':v},
			success: function (data) {
				try {
					let jsonData = JSON.parse(data);
					swal.fire({title:jsonData.title,text:jsonData.message}).then(function (result) {
						location.reload();
					});
				} catch(e) {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
				}
			}
		});
	} else {
		swal.fire({title: '<?=gettext("Error");?>',text:'<?= addslashes(gettext("Please type (Delete) in text box to provide your consent to delete your account."))?>'});
	}
}

function loadMoreMembers(g,c,t){
	let old_page = $("#member_page_number").val();
	let page = parseInt(old_page) + 1;
	$.ajax({
		url: 'ajax.php?loadMoreMembers='+g,
		type: 'GET',
		data: {'chapterid':c,'page':page},
		success: function (data) {
			if (data==0){
				$('#load_more').hide();
			} else {
				$("#member_page_number").val(page);
				$('#members_list').append(data);
				let total = 60 * (page + 1);
				if (total>=t){
					$('#load_more').hide();
				}
			}
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
	});
}

function openBudgetRequestForm(g,r){
	$.ajax({
		url: 'ajax_budget.php?openBudgetRequestForm=1',
        type: "GET",
		data: {'groupid':g,'request_id':r},
        success : function(data) {
			$("#budget_request_form").html(data);
			$('#budget_request').modal({
				backdrop: 'static',
				keyboard: false
			});
			$( "#start_date" ).datepicker({
				prevText:"click for previous months",
				nextText:"click for next months",
				showOtherMonths:true,
				selectOtherMonths: false,
				dateFormat: 'yy-mm-dd',
				beforeShow:function(textbox, instance){
					$('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
				}
			});
			$( "#end_date" ).datepicker({
				prevText:"click for previous months",
				nextText:"click for next months",
				showOtherMonths:true,
				selectOtherMonths: true,
				dateFormat: 'yy-mm-dd',
				beforeShow:function(textbox, instance){
					$('.datepicker-and-video-tags-html-container').append($('#ui-datepicker-div'));
				}
			});
		}
	});
}
function viewApprovalStatus(e, topicType='EVT'){
	$.ajax({
		url: 'ajax_approvals.php?viewApprovalStatus=1',
		type: "GET",
		data: {'topicTypeId':e, 'topicType':topicType},
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#approvalStatusModal').modal({
				backdrop: 'static',
				keyboard: false
			});

		}
	});
}
function openApprovalNoteModal(e, topicType='EVT'){
	$.ajax({
		url: 'ajax_approvals.php?openApprovalNoteModal=1',
		type: "GET",
		data: {'topicTypeId':e, 'topicType': topicType},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({
						title: jsonData.title,
						showCancelButton: true,
                		confirmButtonColor: "#3085d6",
                		cancelButtonColor: "#d33",
                		confirmButtonText: "<?= gettext('Send Collaboration Request'); ?>",
						text: jsonData.message,
						allowOutsideClick:false
					}).then(function (result) {
						if (result.isConfirmed) {
							if (jsonData.status == 2){ // Event Collaboration
								let dataVal= jsonData.val;
								if (dataVal){
									getCollaborationRequestApprovers(dataVal,topicType);
								}
							}
						}
				});
			} catch (e) {
				$("#loadAnyModal").html(data);
				$('#requestApprovalNoteModal').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});
}
function requestEventApproval(jsevent, topicType='EVT') {
	$(document).off('focusin.modal');
	let topicTypeId = $("#topicTypeId").val();
    let requestNote = $("#requesterNote").val();
	let selectedApprovers = $("#selectApprovers").val();
	let ephemeral_topic_id = $('#requestApprovalNote input[name="ephemeral_topic_id"]').val();

	const selectApproversSelector = $("#selectApprovers");
          if(selectApproversSelector.length > 0){
            const selectedOptions = $("#selectApprovers option:selected").length;
                if(selectedOptions === 0){
                    Swal.fire({
                    icon: 'warning',
                    text: `You must select at least one approver`,
                    confirmButtonText: 'Ok'
                    });
                return;
                }
          }

	$.ajax({
		url: 'ajax_approvals.php?requestTopicApproval=1',
		type: "POST",
		data: {
			'topicTypeId':topicTypeId,
			'requestNote':requestNote,
			'selectedApprovers':selectedApprovers,
			ephemeral_topic_id,
			'topicType': topicType
		},
    tskp_submit_btn: jsevent.target,
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false}).then(function (result) {
					location.reload();
				});
			} catch (e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});

}
function openEventReviewModal(g,e){
	$.ajax({
		url: 'ajax_events.php?openEventReviewModal=1',
		type: "GET",
		data: {'groupid':g,'eventid':e},
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#general_email_review_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$('.selectpicker').multiselect({
				includeSelectAllOption: true,
			});
			$('.multiselect-container input[type="checkbox"]').each(function(index,input){
				$(input).after( "<span></span>" );
			});
		}
	});
}

function sendEventForReview(g){
	$(document).off('focusin.modal');
	let formdata =	$('#general_email_review_form').serialize();
	$.ajax({
		url: 'ajax_events.php?sendEventForReview='+g,
		type: "POST",
		data: formdata,
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status == 1) {
					$('#general_email_review_modal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					$('#loadAnyModal').html('');

					if (jsonData.message){
						swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function (result) {
							location.reload();
						});
					}
				} else {
					swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false});
				}
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}

function getEventScheduleModal(g,e){
	$.ajax({
		url: 'ajax_events.php?getEventScheduleModal=1',
		type: "GET",
		data: {'groupid': g, 'eventid': e},
		success: function (data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$("#loadAnyModal").html(data);
				$('#general_schedule_publish_modal').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});
}

function saveScheduleEventPublishing(g){
	$(document).off('focusin.modal');
	let formdata =	$('#schedulePublishForm').serialize();
	$.ajax({
		url: 'ajax_events.php?saveScheduleEventPublishing='+g,
		type: "POST",
		data: formdata,
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status == 1 || jsonData.status == 2){
					$('#general_email_review_modal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					$('#loadAnyModal').html('');
					swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function (result) {
						location.reload();
					});
				} else{
					swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false});
				}
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}
function cancelApprovalStatus(topicTypeId, topicType='EVT'){
	Swal.fire({
		title: 'Please specify reason for cancelling',
		input: 'textarea',
		inputPlaceholder: 'Enter reason here...',
		inputAttributes: {
			'aria-label': 'Type your reason here'
		},
		showCancelButton: true,
		confirmButtonText: '<?= addslashes(gettext("Cancel Approval Request"))?>',
		cancelButtonText: 'Close'
	}).then((result)=> {
		if(result.isConfirmed){
			const note = result.value ? result.value.trim() : '';
			$.ajax({
				url: 'ajax_approvals.php?cancelApprovalRequest',
				type: "POST",
				data: {'topicTypeId':topicTypeId, 'topicType':topicType, 'note':note},
				success : function(data) {
					if(data == 1){
						swal.fire({title: 'Success',text:"Updated successfully"}).then(function (result) {
							location.reload();
						});
					} else {
						swal.fire({title: 'Error!',text:"Something went wrong. Please try again"});
					}
					setTimeout(() => {
							$(".swal2-confirm").focus();
					}, 100);

				}
			});
		}
	});
}
function cancelEventPublishing(g,e){
	$.ajax({
		url: 'ajax_events.php?cancelEventPublishing='+g,
		type: "POST",
		data: {groupid:g,eventid:e},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message}).then(function (result) {
					location.reload();
				});
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
			}
		}
	});
}

function setBudgetYear(y,g){
	$.ajax({
		url: 'ajax_budget.php?setBudgetYear='+y,
        type: "POST",
		data: {},
	    success : function(data) {
			manageBudgetExpSection(g);
		}
	});
}
function openAnnouncementReviewModal(g,p){
	$.ajax({
		url: 'ajax_announcement.php?openAnnouncementReviewModal=1',
		type: "GET",
		data: {'groupid':g,'postid':p},
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#general_email_review_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$('.selectpicker').multiselect({
				includeSelectAllOption: true,
			});
			$('.multiselect-container input[type="checkbox"]').each(function(index,input){
				$(input).after( "<span></span>" );
			});
		}
	});
}

function sendAnnouncementForReview(g){
	$(document).off('focusin.modal');
	let formdata =	$('#general_email_review_form').serialize();
	$.ajax({
		url: 'ajax_announcement.php?sendAnnouncementForReview='+g,
		type: "POST",
		data: formdata,
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status == 1){
					$('#general_email_review_modal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					$('#loadAnyModal').html('');
					if (jsonData.message){
						swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function (result) {
							location.reload();
						});
					}
				} else {
					swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false});
				}
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}

function getChapterBudget(g,c){
	$.ajax({
		url: 'ajax_budget.php?getChapterBudget='+g,
        type: "GET",
		data: {'chapter':c},
	    success : function(data) {
			manageBudgetExpSection(g);
		}
	});
}

function updateChapterBudgetForm(g,y){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_budget.php?showUpdateChapterBudgetForm='+g,
		type: "GET",
		data: 'year='+ y,
		success : function(data) {
		try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$('#loadAnyModal').html(data);
				$('#chapterBudgetModal').modal({
					backdrop: 'static',
					keyboard: false
				});
			}			
		}
	});
}

function updateGroupChapterBudget(i,gid,chp){
	$(document).off('focusin.modal');
	let amt = $("#chapterbudgetamt"+i).val();
	let year = $("#year").val();

	var notifyleads = 0;

	if ( amt == '' && amt == 0 ){
		$("#chapterbudgetamt"+i).focus();
		$("#chapterbudgetamt"+i).css({'background-color' : '#eee'});
		swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Amount cannot be empty'))?>",allowOutsideClick:false});
	}else{
		
		Swal.fire({
			allowOutsideClick: false,
			showCancelButton: true,
			input: 'select',
			inputOptions: {
				no: 'Save changes without sending notifications',
				yes: 'Save changes and notify chapter leaders'
			},
			html:
				'You can choose to notify the chapter budget leaders about their new budget. Please select an appropriate option below and click "Submit" to save the budget changes. If you do not wish to save the changes, then click on the "Cancel" button.',
			confirmButtonText:
				'Submit',
			  inputValidator: (value) => {
				if(value === 'yes'){
					notifyleads = 1;
				}

				let finalData  = new FormData();
				finalData.append("amount",amt);
				finalData.append("groupid",gid);
				finalData.append("chapterid",chp);
				finalData.append("year",year);
				finalData.append("notifyleads",notifyleads);
				$.ajax({
					url: 'ajax_budget.php?updateGroupChapterBudget=1',
					type: "POST",
					data: finalData,
					processData: false,
					contentType: false,
					cache: false,
					success : function(data) {
						let retVal  = JSON.parse(data);
						if(retVal.returnCode>0){
							let remaining_budget = parseFloat(retVal.remaining_budget).toFixed(2);
							let allocated_budget = parseFloat(retVal.allocated_budget).toFixed(2);
							$('.disable-update-button').prop('disabled', true);
							$("#hidechaptermesage"+i).show();
							$("#chapterbudgetamt"+i+"-btn").hide();
							$("#chapterbudgetamt"+i).css({'background-color' : '#63c375'});

							$("#allocated-bgt").html(allocated_budget);
							$("#remained-bgt").html(remaining_budget);
							$("#chapterbudgetamt"+i).val(parseFloat(amt).toFixed(2));
					$("#chapterbudgetamt"+i).focus();
							setTimeout(function(){
								$("#hidechaptermesage"+i).hide();
								$("#chapterbudgetamt"+i+"-btn").show();
								$("#chapterbudgetamt"+i).css({'background-color' : '#fff'});
							}, 2000);
						} else {
							$("#chapterbudgetamt"+i).focus();
							$("#chapterbudgetamt"+i).css({'background-color' : '#f59191'});
							swal.fire({title: '<?=gettext("Error");?>',text:retVal.errorMessage,allowOutsideClick:false});
						}
					}
				});

			  }
		}).then(function(result){ 		
			$('.swal2-select').trigger('focus');			
		});
	}	
	$(".swal2-select").attr("aria-label","<?=gettext('Notification Preference')?>");
}

function closeModal(container, id){
	if (container){
		$('#'+container).html('');
	}
	$('#'+id).modal('hide');
	$('body').removeClass('modal-open');
	$('.modal-backdrop').remove();
}

function trapFocusInModal(modalID){
	const  ___focusableElements = 'button, a, input, select';
	const modal = document.querySelector(modalID);
	const ___firstFocusableElement = modal.querySelectorAll(___focusableElements)[0];
	
	const ___focusableContent = modal.querySelectorAll(___focusableElements);
	const ___lastFocusableElement = ___focusableContent[___focusableContent.length - 1];
	
	document.addEventListener('keydown', function(e) {
	let ___isTabPressed = e.key === 'Tab' || e.keyCode === 9;
	
	if (!___isTabPressed) {
		return;
	}
	
	if (e.shiftKey) { 
		if (document.activeElement === ___firstFocusableElement) {
		___lastFocusableElement.focus(); 
		e.preventDefault();
		}
	} else { 
		if (document.activeElement === ___lastFocusableElement) { 
		___firstFocusableElement.focus();
		e.preventDefault();
		}
	}
	});
	
	___firstFocusableElement.focus();
}
function trapFocusWithInModal(){
// Trap focus in modal 
	$('.modal').keydown(function(e){
		var $focusable = $(this).find("button, [href], input, select, textarea, [tabindex]:not([tabindex='-1'])");
		if($focusable.last().is(":focus") && !e.shiftKey && e.key == "Tab"){
				e.preventDefault();
				$focusable.first().focus();
		}
		else
			if($focusable.first().is(":focus") && e.shiftKey  && e.key == "Tab"){
			e.preventDefault();
			$focusable.last().focus();
		}

	});
}

function retainFocus(modalID){
		// for second modal
		let lastFocus;
		$(modalID).on('show.bs.modal', function (e) {
			lastFocus = $(':focus');
		})
		$(modalID).on('hidden.bs.modal', function (e) {
			if(lastFocus)
				lastFocus.focus();
		})
}

function retainPopoverLastFocus(){ 
	//When Cancel the popover then retain the last focus.
	$('[data-toggle="popover"]').on('hidden.bs.popover', function () {    
		$(this).trigger('focus');
	})
}


function getAnnouncementScheduleModal(g,p){
	$.ajax({
		url: 'ajax_announcement.php?getAnnouncementScheduleModal=1',
		type: "GET",
		data: {'groupid': g, 'postid': p},
		success: function (data) {
			$("#loadAnyModal").html(data);
			$('#general_schedule_publish_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function saveScheduleAnnouncementPublishing(g){
	$(document).off('focusin.modal');
	let formdata =	$('#schedulePublishForm').serialize();
	$.ajax({
		url: 'ajax_announcement.php?saveScheduleAnnouncementPublishing='+g,
		type: "POST",
		data: formdata,
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status == 1){
					$('#general_schedule_publish_modal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					$('#loadAnyModal').html('');
					swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function (result) {
						location.reload();
					});
				} else {
					swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false});
				}
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}

function cancelAnnouncementPublishing(g,p){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_announcement.php?cancelAnnouncementPublishing='+g,
		type: "POST",
		data: {groupid:g,postid:p},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function (result) {
					location.reload();
				});
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}


function changePrivacySetting(e,m){
	let v = 0;

	if (e.checked) {
		v = 1;
	}
	$.ajax({
		url: 'ajax.php?changePrivacySetting=1',
        type: "POST",
		data:{'value':v,'memberid':m},
        success : function(data) {
			swal.fire({title: '<?=gettext("Success");?>',text:'<?= addslashes(gettext("Privacy setting updated successfully."))?>'});
		}
	});
}
function getProfileDetailedView(e,input) {
	__previousActiveElementProfile =document.activeElement;
	$.ajax({
		url: 'ajax.php?getProfileDetailedView=1',
        type: "POST",
		data:input,
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false});
			} catch(e) {
				$('.modal').addClass('js-skip-esc-key');
				openNestedModal($("#load_profile"), data);
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
function closeProfileDetailedView(){
	// $("#load_profile").html('');
	if (document.body.contains(__previousActiveElementProfile)) {
		__previousActiveElementProfile.focus();
	}
}

function cloneNewsLetterForm(g,n) {
	$.ajax({
		url: 'ajax_newsletters.php?cloneNewsLetterForm='+g,
        type: "POST",
		data: {newsletterid:n},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						let values = jsonData.val;
						let encnewsletterid = values.newsletterid;
						initUpdateNewsletter(g,encnewsletterid);
					}
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});
}

function cloneAnnouncementForm(g,n) { 

	$.ajax({
		url: 'ajax_announcement.php?cloneAnnouncementForm='+g,
        type: "POST",
		data: {announcementid:n},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						let values = jsonData.val;
						let clonedAnnouncementid = values.clonedAnnouncementid;
						updateAnnouncement(clonedAnnouncementid);
					}
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});
} 


function deleteExpenseInfo(g,e,i){
	$.ajax({
        url: 'ajax_budget.php?deleteExpenseInfo='+g,
        type: 'POST',
        data: 'id='+e,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						$("#"+i).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
						setTimeout(() => {
							if (localStorage.getItem("manage_active") == 'manageBudgetExpSection'){
								manageBudgetExpSection(g);
							}
						}, 2000);
					}
				});
			} catch(e) { 
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}


function loadViewEventRSVPsModal(gid,e){
	this.closeAllActiveModal();
	$.ajax({
		url: 'ajax_events.php',
        type: "GET",
		data: 'loadViewEventRSVPsModal='+gid+'&eventid='+e,
        success : function(data) {
			$("#loadAnyModal").html(data);
			$('#users_basic_list').modal({
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
	});
}

function loadMoreRsvps(g,e,t){
	let old_page = $("#rsvp_page_number").val();
	let page = parseInt(old_page) + 1;
	$.ajax({
		url: 'ajax_events.php?loadMoreRsvps='+g,
		type: 'GET',
		data: {'eventid':e,'page':page},
		success: function (data) {
			if (data==0){
				$('#load_more').hide();
			} else {
				$("#rsvp_page_number").val(page);
				$('#rsvp_content').append(data);
				let total = 2 * (page + 1);
				if (total>=t){
					$('#load_more').hide();
				}
			}
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
	});
}

function isValidUrl(u) {
	try {
		new URL(u);
		return true;

	} catch (_) {
		swal.fire({title: '<?=gettext("Error");?>', text: "<?= addslashes(gettext('Please enter a valid Web Conference Link'))?>"}).then(function (result) {
			//e.focus();
			$("#GoogleSignIn").focus(); 
			return false;
		});
	}
	setTimeout(() => {  
		$(".swal2-confirm ").focus(); 
	}, 100);
}
function updateRSVPsListSettingModal(e){
	$.ajax({
		url: 'ajax_events.php?updateRSVPsListSettingModal='+e,
		type: "POST",
		data: {'eventid':e},
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#manageRSVPModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

// function showHideRSVPsList(e,s){

// 	$.ajax({
// 		url: 'ajax_events.php?showHideRSVPsList=1',
//         type: "POST",
// 		data: {eventid:e,status:s},
//         success : function(data){
// 			location.reload();
// 		}
// 	});
// }

function loadCreateNewsletterModal(i,global) {
	global = (typeof global !== 'undefined') ? global : 0;
	$.ajax({
		url: 'ajax_newsletters.php?createNewsletterModal='+i+'&global='+global,
        type: "GET",
		data: '',
        success : function(data) {
			$("#loadAnyModal").html(data);
			$('#createNewsletterFormModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}
function createNewsletter(g) {
	$(document).off('focusin.modal');
	if ($("#newslettername").val()==''){
		swal.fire({title: '<?= gettext("Error");?>',text:"<?= gettext('Please enter newsletter name');?>",allowOutsideClick:false});
	} else if ($("#newsletter_template").val()==''){
		swal.fire({title: '<?= gettext("Message");?>',text:"<?= gettext('Please select a template');?>",allowOutsideClick:false});
	} else {
		var app = Revolvapp('#sample_email_template');
        var editorContent = app ? app.editor.getHtml().replace(/(\r\n|\n|\r)/gm, "") : '';
        let formData = $('#createNewsletterForm').serialize();
        formData += '&newsletter=' + encodeURIComponent(editorContent);
		preventMultiClick(1);
		$.ajax({
			url: 'ajax_newsletters?createNewsletter='+g,
			type:'POST',
			data:formData,
			success: function(data){
				try {
					let jsonData = JSON.parse(data);
						if (jsonData.status == 1){
							let values = jsonData.val;
							closeAllActiveModal();
							let encnewsletterid = values.newsletterid;
							initUpdateNewsletter(g,encnewsletterid);
						}else{
							swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false})
						}

				} catch(e) { swal.fire({title: '<?= gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
			}
		});
	 }
}

function getFollowChapterChannelAnonymously(group,action,modalTitle,inputTitle,buttonTitle,text){
	(async () => {
		const { value: anonymous } = await Swal.fire({
			title: modalTitle,
			showClass: {
				popup: 'swal2-text-custom-class'
			  },
			html: text,
			input: 'checkbox',
			inputValue: 0,
			inputPlaceholder: inputTitle,
			confirmButtonText: buttonTitle,			
			allowOutsideClick: false
			// showCancelButton: true,
			// cancelButtonText: 'Cancel'
		})

		if (typeof anonymous !== 'undefined' ) {
			$.ajax({
				url: 'ajax.php?getFollowChapterChannel',
				type: "GET",
				data: {getFollowChapterChannel: group, action: action, anonymous: anonymous},
				success: function (data) {
					try {
						let jsonData = JSON.parse(data);
						swal.fire({title: jsonData.title, text: jsonData.message}).then(function (result) {
							//location.reload();
							updateGroupJoinLeaveButton(group)
						});
					} catch (e) {
            $('#modal_replace')
              .html(data)
              .find('.confirm').popConfirm({content: ''});

						$('#follow_chapter').modal({
							backdrop: 'static',
							keyboard: false
						});
					}


				}
			});
		}
	})();

	$('#loadCompanyDisclaimerModal').hide();
}
function getFollowChapterChannel(gid,action){
	$.ajax({
		url: 	'ajax.php',
		type: 	'GET',
		data: 	{ getFollowChapterChannel:gid,action:action,anonymous:0},
		success: function(data){

			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 11) {
						window.location.replace(window.location.href.split('#')[0]); // Reload page without #frag
					} else {
						updateGroupJoinLeaveButton(gid)
					}
					
				});
			} catch(e) {
				$('#modal_replace').html(data);
				$('#follow_chapter').modal({
					backdrop: 'static',
					keyboard: false
				});
				$('.confirm').popConfirm({content: ''});
			}

			$("#chapter_select_dropdown_section .multiselect").attr("id","chapter_select_dropdown_field");
			$("#channel_select_dropdown_section .multiselect").attr("id","channel_select_dropdown_field");
			$(".multiselect").attr("aria-expanded","false");
		}
	});

	$('#loadCompanyDisclaimerModal').hide();
}

function followUnfollowGroupchapter(gid){
	$(document).off('focusin.modal');
	let chapterIds = $('#chapter_select_dropdown').val();
	$.ajax({
		url: 	'ajax.php?followUnfollowGroupchapter=1',
		type: 	'POST',
		data: 	{ groupid:gid,chapterIds:chapterIds  },
		beforeSend: function(){
			$('#follow_btn_chapter').html('<span class="glyphicon glyphicon-repeat loder"></span>');
		},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				let val = jsonData.val;
				if (jsonData.status == 1){
                    swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result){if(result.isConfirmed){$('#followUnfollowGroupchapterID').prop('disabled',true);}$("#follow-btn0").focus();});
					$('#follow-btn0').html(val);
				} else {
					swal.fire({title: jsonData.title,html:jsonData.message,allowOutsideClick:false}).then(function(result) {
						let dataarray=val.split(",");
						$("#chapter_select_dropdown").val(dataarray);
						$("#chapter_select_dropdown").multiselect("refresh");
						$("#follow-btn0").focus();
					});
				}
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }			
		}
	});
}

function followUnfollowChannel(gid,sel_no){
	$(document).off('focusin.modal');
	let channelIds = $('#channel_select_dropdown').val();
	$.ajax({
		url: 	'ajax.php?followUnfollowChannel=1',
		type: 	'POST',
		data: 	{ groupid:gid,channelIds:channelIds  },
		beforeSend: function(){
	        $('#follow-btn'+sel_no).html('<span class="glyphicon glyphicon-repeat loder"></span>');
	    },
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,html:jsonData.message,allowOutsideClick:false})
				.then(function(result){
					if(result.isConfirmed) {
						$('#followUnfollowChannelID').prop('disabled',true);
					}
					$('#btn_close2').focus();

					if (jsonData.status === 0) {
						let dataarray = jsonData.val.split(",");
						if (dataarray) {
							$("#channel_select_dropdown").val(dataarray);
							$("#channel_select_dropdown").multiselect("refresh");
						}
					}
				});
				$('#follow-btn0').html('<strong>Leave</strong>');
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }
			setTimeout(() => {
				$(".swal2-confirm").focus();   
			}, 500)
		}
	});
}

function updateGroupChapterMembership(m,gid){
	$(document).off('focusin.modal');
	let chapterIds = $('#chapter_select_dropdown').val();
	$.ajax({
		url: 	'ajax.php?updateGroupChapterMembership=1',
		type: 	'POST',
		data: 	{ memberUserid:m,groupid:gid,chapterIds:chapterIds  },
		beforeSend: function(){
			$('#follow_btn_chapter').html('<span class="glyphicon glyphicon-repeat loder"></span>');
		},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				let val = jsonData.val;
				if (jsonData.status == 1){
					swal.fire({title: jsonData.title, text:jsonData.message,allowOutsideClick:false}).then(function(result) {
						$('#follow-btn0').html(val);
						$('#btn_close2').focus();
					});

				} else {
					swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
						let dataarray=val.split(",");
						$("#chapter_select_dropdown").val(dataarray);
						$("#chapter_select_dropdown").multiselect("refresh");
					});
				}
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}).then(function(result) {
				$('#btn_close2').focus();
			}); }

		}
	});
}

function updateGroupChannelMembership(m,gid,sel_no){
	$(document).off('focusin.modal');
	let channelIds = $('#channel_select_dropdown').val();
	$.ajax({
		url: 	'ajax.php?updateGroupChannelMembership=1',
		type: 	'POST',
		data: 	{memberUserid:m, groupid:gid,channelIds:channelIds  },
		beforeSend: function(){
	        $('#follow-btn'+sel_no).html('<span class="glyphicon glyphicon-repeat loder"></span>');
	    },
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					$('#follow-btn0').html('<strong>Leave</strong>');
					$('#btn_close2').focus();
				});

			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}).then(function(result) {
				$('#btn_close2').focus();
			}); }

		}
	});
}


function getFollowUnfollowGroup(gid){
	$(document).off('focusin.modal');
	$("#loadCompanyDisclaimerModal").modal("hide");	
	
	$.ajax({
		url: 	'ajax.php?getFollowUnfollowGroup='+gid,
		type: 	'POST',
		data: 	{},
		beforeSend: function(){
	        $('#follow-btn0').html('<span class="glyphicon glyphicon-repeat loader"></span>');
	    },
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				let val = jsonData.val;
				let status = jsonData.status;

				swal.fire({title: jsonData.title, text:jsonData.message, allowOutsideClick:false}).then(function(result) {
					if (status == 11) {
						window.location.replace(window.location.href.split('#')[0]); // Reload page without #frag
					} else if (status == 1) { // Left Group
						closeJoinGroupModal(gid); // See other #1795 - Fixes
					} else if (status == 2) { // Group joined
						$('#follow-btn0').html(val.btn);
					} else if (status == 3) { // Group joined and chapter assigned
						$('#chapter_select_dropdown').val(val.what);
						$("#chapter_select_dropdown").multiselect("refresh");
						$('#follow-btn0').html(val.btn);
					} else if (status == 4) { // Left group that has chapter auto assign
						closeJoinGroupModal(gid); // See other #1795 - Fixes
					} else {
						$('#follow-btn0').html(val.btn);
					}
					$(".swal2-confirm").focus();
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>", allowOutsideClick:false}); }
			
		}
	});
	
}


function loadCreateEventGroupModal(g,e){
	$.ajax({
		url: 'ajax_events.php',
        type: "GET",
		data: 'loadCreateEventGroupModal='+ g +'&edit='+e,
        success : function(data) {

			if (e!=0){ // Clear if existing modal is open
				$('#neweventgroup').modal('hide');
				$('body').removeClass('modal-open');
				$('.modal-backdrop').remove();
				$('#loadAnyModal').html('');
			}

			$('#loadAnyModal').html(data);
			$('#neweventgroup').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});

}

function createEventGroup(g){
	$(document).off('focusin.modal');
	let event_series_name = $("#event_series_name").val();
	let event_series_id = $("#event_series_id").val();
	let rsvp_restriction =  $('input[name="rsvp_restriction"]:checked').val();
	let isprivate =0;
	if($('#isprivate').prop('checked')){
		isprivate = 1;
	}
	
	if (event_series_name.trim() && rsvp_restriction){
		let formdata = $('#create_event_group_form')[0];
		let finaldata  = new FormData(formdata);
		preventMultiClick(1);
		$.ajax({
			url: 'ajax_events.php?createEventGroup='+ g,
			type: "POST",
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success : function(data) {
				try {
					let jsonData = JSON.parse(data);
					swal.fire({title: jsonData.title, text:jsonData.message,allowOutsideClick:false}).then(function(result) {
						if (jsonData.status==1){
							$('#neweventgroup').modal('hide');
							$('body').removeClass('modal-open');
							$('.modal-backdrop').remove();
							$('#loadAnyModal').html('');

							if (event_series_id!='0'){ // Update event group value realtime
								$("#"+event_series_id).html(event_series_name);
							}
							// Populate Series event group
							manageSeriesEventGroup(g,jsonData.val);
						
						} else if (jsonData.status == -3) {
							$("#chapter_input").focus();
							$("#channel_input").focus();
						}
						
						
					});
				} catch(e) {
					// Nothing to do
					swal.fire({title: '<?= gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
				}
				$(".submenuActive").focus();
			}
			
		});
	} else {
		swal.fire({title: '<?= gettext("Error");?>',text:"<?= gettext('All fields are required');?>",allowOutsideClick:false});
	}

}

function manageSeriesEventGroup(g,s){
	$(document).off('focusin.modal');
	localStorage.setItem("manage_active", "manageGlobalEvents-"+s);
	$.ajax({
		url: 'ajax_events.php',
        type: "GET",
		data: 'manageSeriesEventGroup='+ g +'&series_id='+s,
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false});
			} catch(e) {
				closeAllActiveModal();
				// Ajax Html Data
				$('#ajax').html(data);
				$(".deluser").popConfirm({content: ''});
			}
			
		}
	});
}
function createNewEventOfEventSeriesForm(g,s){
	$.ajax({
		url: 'ajax_events.php',
        type: "GET",
		data: 'newEventForm='+g+'&event_series_id='+s,
        success : function(data){
			$('#neweventgroup').modal('hide');
			$('body').removeClass('modal-open');
			$('.modal-backdrop').remove();
			$('#loadAnyModal').html('');

			$('#ajax').html(data)
		}
	});
}

async function deleteEventSeriesGroup(g,s,userview,isadmin,ispublished){
	$(document).off('focusin.modal');
	let cancellationReason;
	if (ispublished) {
		const { value: retval } = await Swal.fire({
			title: '<?= addslashes(gettext("Event series cancellation reason"));?>',
			input: 'textarea',
			inputPlaceholder: '<?= addslashes(gettext("Enter event series cancellation reason"));?>',
			inputAttributes: {
				'aria-label': '<?= addslashes(gettext("Enter event series cancellation reason"));?>',
				maxlength: 200
			},
			showCancelButton: true,
            cancelButtonText: '<?= addslashes(gettext("Close"))?>',
			confirmButtonText: '<?= addslashes(gettext("Cancel Event Series"));?>',
			allowOutsideClick: () => false,
			inputValidator: (value) => {
				return new Promise((resolve) => {
					if (value) {
						resolve()
					} else {
						resolve('<?= addslashes(gettext("Please enter event series cancellation reason"));?>')
					}
				})
			}
		});
		cancellationReason = retval;
	}

	if (!ispublished || cancellationReason) {
		$.ajax({
			url: 'ajax_events.php?deleteEventSeriesGroup=1',
			type: "POST",
			data: {groupid: g, event_series_id: s, event_cancel_reason: cancellationReason},
			success: function (data) {
				
				try {
					let jsonData = JSON.parse(data);
					swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false}).then(function (result) {
						if (userview) {
							if (isadmin) {
								goto_homepage();
							} else {
								window.location.href = "detail?id=" + g + "#events";
							}
						} else {
							manageGlobalEvents(g);
						}
					});
				} catch (e) {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
				}
			}
		});
	}
}




function openChannelLeadRole(g,c,i){
	$.ajax({
		url: 'ajax.php?openChannelLeadRole='+g+'&cid='+c+'&id='+i,
        type: "GET",
		success : function(data) {
			$('#lead_form_contant').html(data);
			$('#channel_lead_form_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

// Search user for lead
function searchUsersToLeadChannel(g,c,k){
	delayAjax(function(){
		if(k.length >= 3){
			$.ajax({
				type: "GET",
				url: "ajax.php?search_users_to_channel_lead=1",
				data: {'gid':g,'cid':c,'keyword':k},
				success: function(response){
					$("#show_dropdown").html(response);
					let myDropDown=$("#user_search");
					let length = $('#user_search> option').length;
					myDropDown.attr('size',length);
				}
			});
		}
	},500);
}

function updateChannelLeadRole(g){
	$(document).off('focusin.modal');
	let formdata =	$('#channel_lead_role_form').serialize();
	preventMultiClick(1);
	$.ajax({
		url: 'ajax.php?updateChannelLeadRole='+g,
		type: "POST",
		data: formdata,
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false});
				if (jsonData.status>0){		
					$('#channel_lead_form_modal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();			
					mangeChannelLeads(g);
				}
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});

}
function deleteChannelLeadRole(r,g,c,i){
	$.ajax({
		url: 'ajax.php?deleteChannelLeadRole=1',
		type: "POST",
		data: {'groupid':g,'channelid':c,'leadid':i},
		success : function(data) {
			jQuery("#"+i).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
		}
	});
}

function relinkOfficeRavenGroupLinkedGroups(g) {
	$.ajax({
		type: "POST",
		url: "ajax.php?relinkOfficeRavenGroupLinkedGroups="+g,
		data: {groupid:g},
		success: function(response){
			try {
				let jsonData = JSON.parse(response);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					getOfficeRavenGroups(g);
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }
		}
	});
}
function getOfficeRavenGroups(g) {
	window.location.hash = "linkedGroups";
	let filter = $("#group_category_filter_or").val();
	if (typeof filter === "undefined"){
		filter = 1;
	}
	$.ajax({
		type: "GET",
		url: "ajax.php?getOfficeRavenGroups="+g+"&filter="+filter,
		data: {},
		success: function(response){
			$("#ajax").html(response);
		}
	});
}

function loadNewPageWithSelectedTabState(e){
	let hash = window.location.hash.substr(1);
	let href = $(e).data("href");
	window.location.href = href+'#'+hash;
}

function checkEventsByDate(g,e,d){
	let start_date = d;
	let start_date_label = $('#start_date').labels().text();	
	start_date_label = start_date_label.replace("*","");
	start_date_label = start_date_label.replace("[YYYY-MM-DD]","");	
	let validation_msg = start_date_label+ " field date format should be [YYYY-MM-DD].";
	if(!isValidDateString(start_date)){
		$('#start_date').val('');
		swal.fire({title: 'Error', text: validation_msg}).then(function(result) {
			$('#start_date').focus();
		});
		return;
	}
	$.ajax({
		url: 'ajax_events.php?checkEventsByDate=1',
		type: "GET",
		data: {'groupid':g,'eventid':e,'date':d},
		success : function(data) {
			if (data != 1){
				$('#loadAnyModal').html(data);
				$('#eventsByDate').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});
}

function startCarousel(id){
	$('#'+id).carousel('cycle');
}
// Share Post 
function sharePost(){
	let posttoshare = $('#posttoshare').val();
	let emailsToSendStr = $('#email_in').val();
	let emailsToSend = emailsToSendStr.match(/[\._a-zA-Z0-9-\+']+@[\._a-zA-Z0-9-]+/ig);

	if (!emailsToSend || !emailsToSend.length) {
		swal.fire({title: '<?=gettext("Error");?>', text: '<?= addslashes(gettext("Please enter one or more valid emails"))?>'});
		return;
	} else if (emailsToSend.length > <?=MAX_POST_SHARE_EMAILS?>) {
		swal.fire({title: '<?=gettext("Error");?>', text: '<?= addslashes(sprintf(gettext("Too many emails, you can enter a maximum of %s emails"), MAX_POST_SHARE_EMAILS))?>'});
		return;
	}
	let noOfEmailsToSend = emailsToSend.length;
	let invitesSent = '';
	let invitesFailed = '';
	let againSent = '';
	let itemCurrentlyProcessing = 1;
	$('#email_in').val('');
	$("body").css("cursor", "progress");
	$("#inviteSent").html('');
	$("#againSent").html('');
	$("#inviteFailed").html('');
	$("#totalBulkRecored").html(noOfEmailsToSend);
	$(".progress_status").html("Processing 0/" + noOfEmailsToSend + " email(s)");
	$('div#prgress_bar').width('0%');
	$('#progress_bar').show();

	if (noOfEmailsToSend) {
		let p = Math.round((1 / noOfEmailsToSend) * 100);
		$(".progress_status").html("Processing " + 1 + "/" + noOfEmailsToSend);
		$('div#prgress_bar').width(p + '%');
	}

	emailsToSend.forEach( function (item, index) {
		 $.ajax({
			url: 'ajax_announcement.php?sharePost=1',
			type: "POST",
			global:false,
			data: {'postid':posttoshare,'email_in':item,'csrf_token':teleskopeCsrfToken},
			success: function (data) {
				if (data == 1) {
					invitesSent += item + ', ';
				} else if (data == 2) {
					againSent += item + ', ';
				} else if (data == 0) {
					invitesFailed += item + ', ';
				}
			},
			 error: function(e) {
				 invitesFailed += item + ', ';
			 }
		}).always( function fn(){
			itemCurrentlyProcessing++;
			if (itemCurrentlyProcessing > noOfEmailsToSend) {
				$("body").css("cursor", "default");
			   $(".progress_status").html("<i class='fa fa-check-circle' aria-hidden='true'></i> Completed");
			   setTimeout(function () {
				   $("#inviteSent").html(invitesSent.replace(/,\s*$/, ""));
				   if (invitesFailed) {
					   $("#inviteFailed").html(invitesFailed.replace(/,\s*$/, ""));
				   }
				   if (againSent) {
					   $("#againSent").html(againSent.replace(/,\s*$/, ""));
				   }
				   $("#close_show_progress_btn").show();
				   $(".progress_done").show();
			   }, 250);
		   } else {
				let p = Math.round((itemCurrentlyProcessing / noOfEmailsToSend) * 100);
				$(".progress_status").html("Processing " + itemCurrentlyProcessing + "/" + noOfEmailsToSend);
				$('div#prgress_bar').width(p + '%');
			}
		});
	});
}

function getGroupChaptersAboutUs(gid,ch){
	window.location.hash = "about";
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: 'getGroupChaptersAboutUs='+gid+'&chapterid='+ch,
        success : function(data) {
			$('#AboutUS').html(data);
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
	});
}
function getGroupChannelsAboutUs(gid,ch){
	window.location.hash = "about";
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: 'getGroupChannelsAboutUs='+gid+'&channelid='+ch,
        success : function(data) {
			$('#AboutUS').html(data);
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
	});
}

function getChapterAboutUs(gid,c){
	window.location.hash = "about";
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: 'getChapterAboutUs='+gid+'&chapterid='+c,
        success : function(data) {
			$('#ChapterAboutUs').html(data);
		}
	});
}
function getChannelAboutUs(gid,c){
	window.location.hash = "about";
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: 'getChannelAboutUs='+gid+'&channelid='+c,
        success : function(data) {
			$('#ChannelAboutUs').html(data);
		}
	});
}

function manageGlobalAnnouncements(g){
	localStorage.setItem("manage_active", "manageGlobalAnnouncements");
	let state_filter = '';
	if (localStorage.getItem("state_filter") !== null){
		state_filter = localStorage.getItem("state_filter");
	}
	let erg_filter = '';
	if (localStorage.getItem("erg_filter") !== null){
		erg_filter = localStorage.getItem("erg_filter");
	}
	let erg_filter_section = '';
	if (localStorage.getItem("erg_filter_section") !== null){
		erg_filter_section = localStorage.getItem("erg_filter_section");
	}
	let year_filter = '';
	if (localStorage.getItem("year_filter") !== null){
		year_filter = localStorage.getItem("year_filter");
	}
	$('.action-menu').hide();
	g = (typeof g !== 'undefined') ? g : '';
	$.ajax({
		url: 'ajax_groupHome.php',
        type: "GET",
		data: 'manageGlobalAnnouncements='+g+'&state_filter='+state_filter+'&erg_filter='+erg_filter+'&year_filter='+year_filter+'&erg_filter_section='+erg_filter_section,
        success : function(data) {
			$('#ajax').html(data);
		}
	});
}


function manageGlobalEvents(g){
	localStorage.setItem("manage_active", "manageGlobalEvents");
	$('.action-menu').hide();
	g = (typeof g !== 'undefined') ? g : '';
	let state_filter = '';
	if (localStorage.getItem("state_filter") !== null){
		state_filter = localStorage.getItem("state_filter");
	}
	let erg_filter = '';
	if (localStorage.getItem("erg_filter") !== null){
		erg_filter = localStorage.getItem("erg_filter");
	}
	let erg_filter_section = '';
	if (localStorage.getItem("erg_filter_section") !== null){
		erg_filter_section = localStorage.getItem("erg_filter_section");
	}
	let year_filter = '';
	if (localStorage.getItem("year_filter") !== null){
		year_filter = localStorage.getItem("year_filter");
	}
	$.ajax({
		url: 'ajax_groupHome.php',
        type: "GET",
		data: 'manageGlobalEvents='+g+'&state_filter='+state_filter+'&erg_filter='+erg_filter+'&year_filter='+year_filter+'&erg_filter_section='+erg_filter_section,
        success : function(data) {
			$('#ajax').html(data);
			$(".confirm").popConfirm({content: ''});
		}
	});
}


function manageGlobalNewsletters(){
	localStorage.setItem("manage_active", "manageGlobalNewsletters");
}

function showSharePostFormDynamic(id){
	$.ajax({
		url: 'ajax_groupHome.php',
        type: "GET",
		data: 'showSharePostFormDynamic='+id,
        success : function(data) {
			$('#loadAnyModal').html(data);
			$('#sharePostWithUser').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function getAnnouncementActionButton(p){
	$.ajax({
		url: 'ajax_announcement.php',
        type: "GET",
		data: 'getAnnouncementActionButton='+p,
        success : function(data) {
			$('#dynamicActionButton'+p).html(data);
			$(".confirm").popConfirm({content: ''});
		}
	});
}

function getEventActionButton(g,e){
	$.ajax({
		url: 'ajax_events.php',
        type: "GET",
		data: {groupid:g,'getEventActionButton':e} ,
        success : function(data) {
			$('#dynamicActionButton'+e).html(data);
			$(".confirm").popConfirm({content: ''});
		}
	});
}

function inviteEventUsersForm(e){	
		$.ajax({
		url: 'ajax_events.php',
        type: "GET",
		data: 'inviteEventUsersForm='+e,
        success : function(data) {		
			$('.modal').addClass('js-skip-esc-key');	
			$('#modal_over_modal').html(data);			
				$('#eventInviteGroup').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
	});
}

function activateDeactivateSurvey(g,s,u){
	$(document).off('focusin.modal');
	let update = (typeof u !== 'undefined') ? u : 0;
	$.ajax({
		url: 'ajax.php?activateDeactivateSurvey=1',
		type: "post",
		data: {'groupid':g,'surveyid':s,'update_publish_date':update},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status>0){
						if (jsonData.val>0){
							getGroupSurveys(g);
						} else {
							getAdminSurveys();
						}
					}
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});
}

function deleteSurvey(g,s){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax.php?deleteSurvey=1',
		type: "post",
		data: {'groupid':g,'surveyid':s},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status>0){
						this.closeAllActiveModal();
						location.reload();
					}
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});
}

function openSurveySettingForm(g,s){	
	$.ajax({
		url: 'ajax.php?openSurveySettingForm=1',
		type: "get",
		data: {'groupid':g,'surveyid':s},
		success : function(data) {
			$('#loadAnyModal').html(data);
			$('#surveySettingFormModal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$('.modal').addClass('js-skip-esc-key');
		}
	});
}

function updateSurveyInfoModal(g,s){	
	$.ajax({
		url: 'ajax_survey.php?updateSurveyInfoModal=1',
		type: "get",
		data: {'groupid':g,'surveyid':s},
		success : function(data) {
			$('#loadAnyModal').html(data);
			$('#updateSurveySettingFormModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function previewSurvey(g,s){
	$.ajax({
		url: 'ajax.php?previewSurvey=1',
		type: "get",
		data: {'groupid':g,'surveyid':s},
		success : function(data) {
			$('#loadAnyModal').html(data);
			$('#surveyPreview').modal({
				backdrop: 'static',
				keyboard: false
			});
			setTimeout(()=>{
                $(".modal").not("#surveyPreview").css("z-index", "1040");
                $("#surveyPreview").css("z-index", "1050");
                $(".modal-backdrop").last().css("z-index","1045");
            });
            $("#surveyPreview").on('hidden.bs.modal', function(){
                $(".modal").not("#surveyPreview").css("z-index", "1050");
				if($('.modal.show').length){
					$('body').addClass('modal-open');
				}
            });
		}
	});
}

function importSurveyDataModal(g,s){
	$.ajax({
		url: 'ajax_survey.php?importSurveyDataModal=1',
		type: "get",
		data: {'groupid':g,'surveyid':s},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message});
			} catch(e) {
				$('#loadAnyModal').html(data);
				$('#importSurveyModal').modal({
					backdrop: 'static',
					keyboard: false
				});

			}
		}
	});
}

function deleteSurveyDataConfirmation(g,s){
	$.ajax({
		url: 'ajax_survey.php?deleteSurveyDataConfirmationModal=1',
		type: "get",
		data: {'groupid':g,'surveyid':s},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message});
			} catch(e) {
				$('#loadAnyModal').html(data);
				$('#deleteSurveyDataConfirmationModal').modal({
					backdrop: 'static',
					keyboard: false
				});

			}
		}
	});
}

function checkSurveyValidations(g){
	$(document).off('focusin.modal');
	let surveyname =	$('#surveyname').val();
	let surveyTrigger =	$('#surveyTrigger').val();
	let anonymityCheckbox = document.getElementById("anonymity");
	let anonymity = 0;
	if (anonymityCheckbox.checked == true){
		anonymity = 1;
	}
	if (surveyTrigger == '-2'){
		let surveyTriggerValue = $('#surveyTriggerValue').val();
		if (surveyTriggerValue<1){
			swal.fire({title: '<?=gettext("Error");?>', text: "<?= addslashes(gettext('Please enter valid number of days'))?>",allowOutsideClick:false});
			return false;
		} 
		surveyTrigger = surveyTriggerValue;
	}
	let section = $('#group_chapter_channel_id').find(':selected').data('section');
	let group_chapter_channel_id = $("#group_chapter_channel_id").val();
	$.ajax({
		url: 'ajax.php?checkSurveyValidations=1',
		type: 'POST',
		data: {'surveyname':surveyname,'trigger':surveyTrigger,'anonymity':anonymity,'groupid':g,'section':section,'group_chapter_channel_id':group_chapter_channel_id},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status) {
					$(".surveyMainFields, .survey-btn-submit").hide(1000, function () {
						$("#templatelistNewsurveyDiv").show();
						$('#form_title').trigger('focus');
					});
				} else {
					swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false});
				}
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});
}

function showSelectFromPastSurveyDiv(){
	$(".surveyMainFields, .survey-btn-submit").hide(1000, function () {
		$("#templatelistNewsurveyDiv").show();
	});
}

function shareUnshareSurvey(g,s,it){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax.php?shareUnshareSurvey=1',
		type: "post",
		data: {'groupid':g,'surveyid':s,'isTemplate':it},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status>0){
						if (jsonData.val>0){
							getGroupSurveys(g);
						} else {
							getAdminSurveys();
						}
					}
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});
}

function submitSurveySetting(g,cs){
	let surveyname =	$('#surveyname').val();
	let surveyTrigger =	$('#surveyTrigger').val();
	let anonymityCheckbox = document.getElementById("anonymity");
	let anonymity = 0;
	if (anonymityCheckbox.checked == true){
		anonymity = 1;
	}
	if (surveyTrigger == '-2'){
		let surveyTriggerValue = $('#surveyTriggerValue').val();
		if (surveyTriggerValue<1){
			swal.fire({title: '<?=gettext("Error");?>', text: "<?= addslashes(gettext('Please enter valid number of days'))?>"});
			return false;
		} 
		surveyTrigger = surveyTriggerValue;
	}
	let requiredCheckbox = document.getElementById("is_required");
	let is_required = 0;
	if (requiredCheckbox.checked == true){
		is_required = 1;
	}
	let multipleCheckbox = document.getElementById("allow_multiple");
	let allow_multiple = 0;
	if (multipleCheckbox.checked == true){
		allow_multiple = 1;
	}
	let section = $('#group_chapter_channel_id').find(':selected').data('section');
	let group_chapter_channel_id = $("#group_chapter_channel_id").val();
	$.ajax({
		url: 'ajax.php?submitSurveySetting=1',
		type: 'POST',
		data: {'surveyname':surveyname,'trigger':surveyTrigger,'anonymity':anonymity,'groupid':g,'section':section,'group_chapter_channel_id':group_chapter_channel_id,'cloned_json_surveyid':cs,'is_required':is_required,'allow_multiple':allow_multiple},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				resetContentFilterState(2);
				if (jsonData.status) {
					$('#surveySettingFormModal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					window.location.href = jsonData.val;
				} else {
					swal.fire({title: jsonData.title, text: jsonData.message});
				}
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }
		}
	});
}

function updateSurveySetting(g,s){
	$(document).off('focusin.modal');
	let formdata = $('#updateSurveySettingForm')[0];
	let finaldata= new FormData(formdata);
	finaldata.append("groupid",g);
	finaldata.append("surveyid",s);
	$.ajax({
		url: 'ajax_survey.php?updateSurveySetting=1',
		type: 'POST',
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status>0){
						resetContentFilterState(2);
						$('#updateSurveySettingFormModal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						if (jsonData.status==1){
							getGroupSurveys(g);
						} else {
							getAdminSurveys();
						}
					}
				}); 
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});
}

function getAdminSurveys(f){
	localStorage.setItem("manage_active", "getGroupSurveys");

	f = (typeof f !== 'undefined') ? f : 0;
	if (f){
		var byState = $("#filterByState").val();
	} else {
		var byState = localStorage.getItem("state_filter");
	}

	if (typeof byState === 'undefined' || byState === null){
		byState = '';
	} else {
		localStorage.setItem("state_filter", byState);
	}

	$.ajax({
		url: 'ajax_groupHome.php',
        type: "GET",
		data: {'getAdminSurveys':1,state_filter:byState},
        success : function(data) {
			$('#ajax').html(data);
			$(".confirm").popConfirm({content: ''});			
			$('#filterByState').focus();			
		}
	});

	
}

function openAdminSurveySettingForm(){
	$.ajax({
		url: 'ajax_groupHome.php?openAdminSurveySettingForm=1',
		type: "get",
		data: {},
		success : function(data) {
			$('#loadAnyModal').html(data);
			$('#surveySettingFormModal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$('.modal').addClass('js-skip-esc-key');
		}
	});
}

function submitGlobalSurveySetting(t){
	let surveyname =	$('#surveyname').val();
	let surveyTrigger =	$('#surveyTrigger').val();
	let anonymityCheckbox = document.getElementById("anonymity");
	let anonymity = 0;
	if (anonymityCheckbox.checked == true){
		anonymity = 1;
	}
	let requiredCheckbox = document.getElementById("is_required");
	let is_required = 0;
	if (requiredCheckbox.checked == true){
		is_required = 1;
	}
	let multipleCheckbox = document.getElementById("allow_multiple");
	let allow_multiple = 0;
	if (multipleCheckbox.checked == true){
		allow_multiple = 1;
	}
	if (surveyname && surveyTrigger && !(anonymity && surveyTrigger == 3)){
		$.ajax({
			url: 'ajax_groupHome.php?submitGlobalSurveySetting=1',
			type: 'POST',
			data: {'surveyname':surveyname,'trigger':surveyTrigger,'anonymity':anonymity,'templateid':t,'is_required':is_required,'allow_multiple':allow_multiple},
			success : function(data) {
				try {
					let jsonData = JSON.parse(data);
					if (jsonData.status>0){
						resetContentFilterState(2);
						$('#surveySettingFormModal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						window.location.href = jsonData.val;
					} else {
						swal.fire({title: jsonData.title,text:jsonData.message});
					}
				} catch(e) {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
				}
			}
		});
	} else {
		let err = '';
		if (!surveyname){
			err  = "<?= addslashes(gettext('Please enter survey name'))?>"
		} else if (!surveyTrigger){
			err  = "<?= addslashes(gettext('Please select survey trigger'))?>"
		} else if (anonymity && surveyTrigger == 3) {
			err  = "<?= addslashes(gettext('Login surveys cannot be anonymous'))?>"
		}
		if (err){
			swal.fire({title: '<?= gettext("Error")?>',text:err});
		}
	}
}
function closeTrainingVideoModal(){
	$('#trainingVideosModal').modal('hide');
	$("body").removeClass('modal-open');	
}

function copyShareableLink(sMessage,id){
	let shareableLink = document.getElementById(id);
	shareableLink.select();
	shareableLink.setSelectionRange(0, 99999)
	document.execCommand("copy");
	
	var anchors = document.querySelectorAll('.get-shareable-link');	
    anchors.forEach(function(a) {
        a.text = sMessage;		
    });
	$(".get-shareable-link").attr("style","background-color:green;");
	
}
function filterByGroupCategory(v){
	window.location.href = "home?filter="+v
}

function getEventsTimeline(gid,cid,chid,type){
	window.location.hash = "events";
	let by_start_date = '';
	let by_end_date = '';
	let by_volunteer = '';

	if(type==1){
		$('#event_filter_container').show();
		by_start_date = $("#filter_by_start_date").val();
		by_end_date = $("#filter_by_end_date").val();
		by_volunteer = $("#filter_by_volunteer").val();
		$('#upcomingEvents').attr('aria-selected', 'true');
		$('#pastevents').attr('aria-selected', 'false');
	} else{
		$('#event_filter_container').hide();
		$('#pastevents').attr('aria-selected', 'true');
		$('#upcomingEvents').attr('aria-selected', 'false');
	}
	$.ajax({
		url: 'ajax_events.php',
        type: "GET",
		data: 'getEventsTimeline='+gid+'&chapterid='+cid+'&channelid='+chid+'&type='+type+'&by_start_date='+by_start_date+'&by_end_date='+by_end_date+'&by_volunteer='+by_volunteer,
        success : function(data) {
			$('#loadEventsData').html(data);
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
	});
}
function loadMoreEvents(gid,cid,chid,type,ap) {
	let lastListItem  = document.querySelectorAll('.event-block');	
	window.location.hash = "events";
	let page = $("#pageNumber").val();
	let lastMonth = $("#lastMonth").val();
	let by_start_date = "";
	let by_end_date = "";
	let by_volunteer = "";
	if(type==1){
		by_start_date = $("#filter_by_start_date").val();
		by_end_date = $("#filter_by_end_date").val();
		by_volunteer = $("#filter_by_volunteer").val();
	} 

	let params = 'ajax_events.php?getEventsTimeline=' + gid + '&chapterid=' + cid + '&channelid=' + chid + '&page=' + page + '&type=' + type + '&lastMonth=' + lastMonth+'&by_start_date='+by_start_date +'&by_end_date='+by_end_date+'&by_volunteer='+by_volunteer;
	
	if (ap == 'teamEvents'){
		params = 'ajax_talentpeak.php?getTeamsEventsTimeline=1&groupid='+gid+'&type='+type+'&page=' + page +'&lastMonth=' + lastMonth + '&chapterid=' + cid + '&channelid=' + chid + '&by_start_date='+by_start_date +'&by_end_date='+by_end_date+'&by_volunteer='+by_volunteer;
	}

	$.ajax({
		url: params,
		type: "GET",
		success: function (data) {
			if (data == 1) {
				$('#loadmore' + type).hide();
			} else {
				$("#pageNumber").val((parseInt(page) + 1));
				const regex = /<span.*>_l_=(.*)<\/span>$/gm;
				let m;
				if ((m = regex.exec(data)) !== null) {
					lastMonth = m[1];
					$("#lastMonth").val(lastMonth);
				} else {
					$('#loadmore' + type).hide();
				}
				$('#loadMoreEvents' + type).append(data);

				let newfocusList = document.querySelectorAll('.event-block');
				let newfocusItem = newfocusList[lastListItem.length];
				newfocusItem.querySelector("a").focus();
				
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

function loadMorePosts(gid,cid,chid) {
	let lastListItem  = document.querySelectorAll('.announcement-block');
	window.location.hash = "announcements";
	let page = $("#pageNumber").val();
	$.ajax({
		url: 'ajax_groupHome.php',
		type: "GET",
		data: 'loadMorePosts=' + gid + '&chapterid=' + cid + '&channelid=' + chid + '&page=' + page,
		success: function (data) {
			if (data == 1) {
				$('#loadeMorePostsAction').hide();
			} else {
				$("#pageNumber").val((parseInt(page) + 1));

				const regex = /<span.*>_l_=(.*)<\/span>$/gm;
				let m;
				if ((m = regex.exec(data)) === null) {
					$('#loadeMorePostsAction').hide();
				} 
				$('#loadeMorePostsRows').append(data);
								
				let newfocusList = document.querySelectorAll('.announcement-block');
				let newfocusItem = newfocusList[lastListItem.length];
				newfocusItem.querySelector("a").focus();
			
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

function getAnnouncementByFilter(type){
	window.location.hash = "adminContents";
	let byState = $("#filterByState").val();
	let byYear = $("#filterByYear").val();
	$.ajax({
		url: 'ajax_groupHome.php?getAnnouncementByFilter='+type,
        type: "GET",
		data: {'state':byState,'year':byYear},
        success : function(data) {
			$('#ajax').html(data);
			$(".confirm").popConfirm({content: ''});
		}
	});
}
function filterAnnouncements(g){
	$.ajax({
		url: 'ajax_groupHome.php?filterAnnouncements='+g,
        type: "GET",
        success : function(data) {
			$('#announcementTable').html(data);
		}
	});
}
function filterEvents(g){
    var searchText = $('input[type="search"]').val();
	filterByStateVal = $("#filterByState").val();
	publishedStateEnId = $("#publishedStateEnId").val();
	if(filterByStateVal != publishedStateEnId)
	{
		$("#upcomingEvents").prop("checked",false);
		$("#pastEvents").prop("checked",false);
		$("#reconciledEvent").prop("checked",false);
		$("#notReconciledEvent").prop("checked",false);
		$("#filterEventsCheckbox").hide();
	}else
	{
		 $("#filterEventsCheckbox").show();
	}

	let upcomingEvents = false;
	let pastEvents = false;
	let reconciledEvent = false;
	let notReconciledEvent = false;
	if($("#upcomingEvents").prop('checked') == true){
		upcomingEvents = true;
	}
	if($("#pastEvents").prop('checked') == true){
    	pastEvents = true;
	}
	if($("#reconciledEvent").prop('checked') == true){
		reconciledEvent = true;
	}
	if($("#notReconciledEvent").prop('checked') == true){
		notReconciledEvent = true;
	}
	$.ajax({
		url: 'ajax_groupHome.php?filterEvents='+g,
        type: "GET",
		data : {upcomingEvents:upcomingEvents,pastEvents:pastEvents,reconciledEvent:reconciledEvent,notReconciledEvent:notReconciledEvent,searchText:searchText},
        success : function(data) {
			$('#eventTable').html(data);
			$("#hidden_div_for_notification").attr({role:"status","aria-live":"polite"}); 
		}
	});
}

function getNewsletterActionButton(e){
	$.ajax({
		url: 'ajax_newsletters.php',
        type: "GET",
		data: 'getNewsletterActionButton='+e,
        success : function(data) {
			$('#dynamicActionButton'+e).html(data);
			$(".confirm").popConfirm({content: ''});
		}
	});
}

function filterNewsletters(g){

	$.ajax({
		url: 'ajax_groupHome.php?filterNewsletters='+g,
        type: "GET",
        success : function(data) {
			$('#newsletterTable').html(data);
		}
	});
}
function showSharePostForm(){
	$('#sharePostWithUser').modal({
		backdrop: 'static',
		keyboard: false
	});
}

/**
 * 
 * @param {*} g (group id)
 * @param {*} i (announcment or event or newsleter or surveyid)
 * @param {*} s (section 1= announcement, 2 event, 3 newsletter, 4 survey, 5 resource, 6 discussions, 7 resource folder)
 */
function getShareableLink(g,i,s){	
	$(document).off('focusin.modal');		
	$.ajax({
		url: 'ajax_groupHome.php?getShareableLink='+g,
		type: "get",
		data: {'id':i,'section':s},
		success : function(data) {
			if (data){
				$('.modal').addClass('js-skip-esc-key');
				$('#modal_over_modal').html(data);
				$('#copySurvey').modal({
					backdrop: 'static',
					keyboard: false
				});
			} else {
				swal.fire({title: '<?= gettext("Error")?>',text:'<?= addslashes(gettext("Something went wrong. Please try again"))?>',allowOutsideClick:false});
			}
		}
	});
	
}

/**
 * @param e (event_id)
 */
function updateEventRecordingLink(e) {
	closeAllActiveModal();
	$.ajax({
		url: 'ajax_events?updateEventRecordingLink=1&eventid='+e,
		success: function (data) {
			var container = $('#modal_over_modal');
			container.html(data);
			container.find('.modal').modal({
				backdrop: 'static',
				keyboard: false
			});
			setTimeout(function() {
				container.find('input[name="event_recording_link"]').focus();
			}, 500);
		}
	});
}

/**
 * @param e (event_id)
 */
function submitUpdateEventRecordingLinkForm(e) {
	var container = $('#modal_over_modal');
	$.ajax({
		url: 'ajax_events?submitUpdateEventRecordingLinkForm=1&eventid='+e,
		method: 'POST',
		data: {
			event_recording_link: container.find('input[name="event_recording_link"]').val(),
			event_recording_note: container.find('textarea[name="event_recording_note"]').val()
		},
		dataType: 'json',
		success: function (jsonData) {
			swal.fire({title:jsonData.title,text:jsonData.message})
			.then(function(result) {
				if (jsonData.status == 1) {
					let data = jsonData.val;
					container.find('#shareableLink').val(data.event_recording_shareable_link);
					container.find('.js-copy-link-btn, .js-download-report-btn').prop('disabled', !data.event_recording_shareable_link);
					container.find('.js-add-update-labels').each(function () {
						var $this = $(this);
						$this.html($this.data(data.event_recording_shareable_link ? 'updateTxt' : 'addTxt'));
					});
				}
			});
        }
	});
}

function confirmEventRecordingAttendance(e) {
	$.ajax({
		url: 'ajax_events?confirmEventRecordingAttendance=1&eventid='+e,
		method: 'POST',
		data: {},
		dataType: 'json',
		success: function (jsonData) {
			swal.fire({title:jsonData.title,text:jsonData.message}).then(function(result) {
				closeAllActiveModal();
			});
		}
	});
}
/**
 * 
 * @param {*} e (eventid)
 */
function updateEventFollowUpNoteForm(e){
	this.closeAllActiveModal();
	$.ajax({
		url: 'ajax_events.php',
        type: "GET",
		data: 'updateEventFollowUpNoteForm=1&eventid='+e,
        success : function(data) {
			$('#loadAnyModal').html(data);
			$('#event_folloup_form_model').modal({
				backdrop: 'static',
				keyboard: false
			});
			
		}
	});
}

/**
 *
 * @param distance
 * @param id
 * @param type
 */
function countDownTimer(distance,id,type){
	let statusCheckDelay = 15;
	// Update the count down every 1 second
	__globalIntervalVariable = setInterval(function() {
	if (distance > 0) {
		distance--;
		let totalSeconds = distance;
		let days = Math.floor(totalSeconds / 86400);
		totalSeconds %= 86400;
		let hours = Math.floor(totalSeconds / 3600);
		totalSeconds %= 3600;
		let minutes = Math.floor(totalSeconds / 60);
		let seconds = totalSeconds % 60;

		let formatedCountDownDate = seconds + "s";

		if (minutes) {formatedCountDownDate = minutes + "m " + formatedCountDownDate;}
		if (hours) {formatedCountDownDate = hours + "h " + formatedCountDownDate;}
		if (days) {formatedCountDownDate = days + "d " + formatedCountDownDate;}

		// Output the result in an element with id="demo"
		document.getElementById("publishCountDown").innerHTML = "[ Publish in " + formatedCountDownDate + " ]";
	} else {
		if (statusCheckDelay-- < 1) {
			$.ajax({
				url: 'ajax_groupHome.php?checkPublishStatus=1',
				type: "get",
				data: {'id':id,'type':type},
				success : function(data) {
					if (data){
						clearInterval(__globalIntervalVariable);
						location.reload();
					} else {
						statusCheckDelay = 15;
					}
				}
			});
		}
		let countDown = statusCheckDelay < 1 ? 0 : statusCheckDelay;
		document.getElementById("publishCountDown").innerHTML = "[ In publish queue. Checking in "+countDown+" second(s) ]";
	}
	}, 1000);
}

function pinUnpinAnnouncement(g,p,t){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_announcement.php?pinUnpinAnnouncement=1',
		type: "post",
		data: {'groupid':g,'postid':p,'type':t},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					location.reload();
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});
}

function pinUnpinNewsletter(g,n,t){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_newsletters.php?pinUnpinNewsletter=1',
		type: "post",
		data: {'groupid':g,'newsletterid':n,'type':t},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					location.reload();
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});
}

function cloneEvent(e,s,g) {
	let parent_groupid = (typeof g !== 'undefined') ? g : '';
	$(document).off('focusin.modal');
	localStorage.setItem("state_filter", s);
	$.ajax({
		url: 'ajax_events.php?cloneEvent='+e,
        type: "POST",
		data: {parent_groupid:parent_groupid},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status>0){
						updateEventForm(jsonData.val,false,parent_groupid);
					}
				});
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}

function manageWaitList(e){
	closeAllActiveModal();

	$.ajax({
		url: 'ajax_events.php?manageWaitList='+e,
        type: "GET",
		data: {},
        success : function(data) {
			$("#loadAnyModal").html(data);
			$('#manageWaitList').modal({
			backdrop: 'static',
			keyboard: false
			});
			$(".confirm").popConfirm({content: ''});
		}
	});	
}

function pinUnpinResource(g,r,t,ch,chnl){
	$(document).off('focusin.modal');
	let parent_id = $("#parent_id").val();
	$.ajax({
		url: 'ajax_resources.php?pinUnpinResource=1',
        type: "post",
		data: {'groupid':g,'resource_id':r,'type':t,'parent_id':parent_id,'chapterid':ch,'channelid':chnl},
		success : function(data) {

			try {
				let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						getGroupResources(g,jsonData.val,jsonData.val);
					} else {
						getResourceChildData(parent_id,g,jsonData.val,jsonData.val);
					}
					setTimeout(() => {
						$('#rid_'+r).trigger('focus');
					}, 700);  
                });
            } catch(e) {
                swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
            }
		}
	});
}

function newHolidayModal(g,e){
	$.ajax({
		url: 'ajax_events.php?newHolidayModal=1',
        type: "GET",
		data: {groupid:g,eventid:e},
        success : function(data) {
			$("#modal_over_modal").html(data);
			$('#holidayModal').modal({
			backdrop: 'static',
			keyboard: false
			});
		}
	});
}

function addOrUpdateHoliday(g,e){
	var eventid = e;
	$(document).off('focusin.modal');
	let eventtitle= $("#eventtitle").val();
	let start_date= $("#start_date").val();
	let event_description= $("#redactor_content").val();

	if (!eventtitle || !start_date || !event_description){
		swal.fire({title: '<?= gettext("Error");?>',text:'<?= gettext("All fields are required.");?>',allowOutsideClick:false});
		$('.modal').css('overflow-y', 'auto');
		$('body').addClass('modal-open');
	} else {
		let formdata = $('#holidayModalForm')[0];
		let finaldata= new FormData(formdata);
		finaldata.append("groupid",g);
		finaldata.append("eventid",e);
		preventMultiClick(1);
		$.ajax({
			url: 'ajax_events.php?addOrUpdateHoliday=1',
			type: "POST",
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success : function(data) {
				try {
					let jsonData = JSON.parse(data);
					if (jsonData.status == 0){
						swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false});
					}	
				} catch(e) {
					if(data){								
						swal.fire({title: '<?= gettext("Success");?>',text:"<?= gettext('Cultural observances updated successfully.');?>"}).then(function(result) {				

							$('#holidayModal').modal('hide');
							$('body').removeClass('modal-open');
							$('.modal-backdrop').remove();

							$('#holidaysContainer').html(data);	

							setTimeout(function(){	
								$('.new-holiday-btn').trigger('focus');							
								$('#holiday_'+eventid).focus();
							},500);

						});
						
					} else {
						swal.fire({title: '<?= gettext("Error");?>',text:"<?= gettext('Something went wrong. Please try again.');?>"});
					}
				}
			}
		});
	}
}


function manageHolidays(g){
	$('#holidayModal').modal('hide');
	$('body').removeClass('modal-open');
	$('.modal-backdrop').remove();

	$.ajax({
		url: 'ajax_events.php?manageHolidays=1',
        type: "GET",
		data: {groupid:g},
        success : function(data) {
			$("#loadAnyModal").html(data);
			$('#manageHolidaysModal').modal({
			backdrop: 'static',
			keyboard: false
			});
			$(".confirm").popConfirm({content: ''});
		}
	});
}

function activateOrDeactivateHoliday(e,s,g){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_events.php?activateOrDeactivateHoliday=1',
        type: "POST",
		data: {eventid:e,status:s},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					manageHolidays(g);				
				});
				
			} catch(e) {
				// Handle Ajax HTML
				$("#holidaysContainer").html(data);
				$(".confirm").popConfirm({content: ''});
			}

			setTimeout(() => {
				document.querySelector(".swal2-confirm").focus();   
			}, 200)
		}
	});
}
function deleteHoliday(e,g){
	$(document).off('focusin.modal');
	$('.modal-backdrop').remove();
	$.ajax({
		url: 'ajax_events.php?deleteHoliday=1',
        type: "POST",
		data: {eventid:e},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					manageHolidays(g);				
				});				
			} catch(e) {
				// Handle Ajax HTML
				$("#holidaysContainer").html(data);
				$(".confirm").popConfirm({content: ''});
			}

			setTimeout(() => {
				document.querySelector(".swal2-confirm").focus();   
			}, 200)
		}
	});
}

function viewHolidayDetail(e,c){
	let cb = (typeof c !== 'undefined') ? c : 0;
	$.ajax({
		url: 'ajax_events.php?viewHolidayDetail=1',
        type: "GET",
		data: {eventid:e, callback:cb},
        success : function(data) {
			$("#modal_over_modal").html(data);
			$('#holidayDetailModal').modal({
			backdrop: 'static',
			keyboard: false
			});
		}
	});
}

function closeHolidayDetailView(g){

	$('#holidayDetailModal').modal('hide');
	$('body').removeClass('modal-open');
	$('.modal-backdrop').remove();	
	$('.modal').css('overflow-y', 'auto');
	$('body').addClass('modal-open');
	manageHolidays(g);		
}

function manageEventSpeakers(e){
	$.ajax({
		url: 'ajax_events.php?manageEventSpeakers='+e,
        type: "GET",
		data: {},
        success : function(data) {
			$("#loadAnyModal").html(data);
			$('#manageEventSpeakerModal').modal({
			backdrop: 'static',
			keyboard: false
			});
			$(".confirm").popConfirm({content: ''});
			$('.initialpic').initial({
                charCount: 2,
                textColor: '#ffffff',
                seed: 0,
                fontSize: 20,
                fontWeight: 300,
                fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
                radius: 5
            });
		}
	});
}
function showSelectFromPastSpeakerDiv(e) {
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_events.php?showSelectFromPastSpeakerDiv=1',
		type: 'get',
		data: {eventid:e},
		success: function(data) {
			$("#selectFromPastSpeakerDivOptions").html(data);
			try {
				let items = JSON.parse(data);
				$.each(items, function (i, item) {
					$('#selected_approved_speaker').append($('<option>', {
						value: item.v,
						text: item.t
					}));
				});

				$("#speaker_card").hide(1000, function () {
					$("#selectFromPastSpeakerDiv").show();	
					document.querySelector("#selected_approved_speaker").focus(); 				
				});
				
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}

function openAddOrUpdateEventSpeakerModal(e,s,c){
	$.ajax({
		url: 'ajax_events.php?openAddOrUpdateEventSpeakerModal=1',
		type: 'get',
		data: {eventid:e,speakerid:s,clone:c},
		success: function(data) {
			$('#manageEventSpeakerModal').modal('hide');
			$('body').removeClass('modal-open');
			$('.modal-backdrop').remove();

			$("#loadAnyModal").html(data);
			$('#eventSpeakerModalForm').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function addOrUpdateEventSpeaker(e){
	$(document).off('focusin.modal');
	let errors = "";
	let errors1 = "";

	if(!$("#expected_attendees").val()){
		errors += '<?= addslashes(gettext("Expected Attendees"));?>, ';
	}
	if($("#expected_attendees").val() < 1){
		errors1 += '<?= addslashes(gettext("Expected Number of Attendees cannot have a negative or zero value"));?>';
	}
	if(!$("#speech_length").val()){
		errors += '<?= addslashes(gettext("Speech length"));?>, ';
	}
	if(!$("#speaker_name").val()){
		errors += '<?= addslashes(gettext("Speaker name"));?>, ';
	}
	if(!$("#speaker_title").val()){
		errors += '<?= addslashes(gettext("Speaker title"));?>, ';
	}
	if(!$("#speaker_fee").val()){
		errors += '<?= addslashes(gettext("Speaker fee"));?>, ';
	}
	if (errors){
		errors += " <?= addslashes(gettext('field(s) can not be empty'));?>";
		swal.fire({title: '<?=gettext("Error");?>',text:errors,allowOutsideClick:false});
	}else if(errors1){
		swal.fire({title: '<?=gettext("Error");?>',text:errors1,allowOutsideClick:false});
	} else {
		let formdata = $('#eventSpeakerForm')[0];
		let finaldata  = new FormData(formdata);
		finaldata.append("eventid",e);
		$.ajax({
			url: 'ajax_events.php?addOrUpdateEventSpeaker=1',
			type: 'POST',
			enctype: 'multipart/form-data',
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success: function(data) {
				try {
					let jsonData = JSON.parse(data);
					swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
						if (jsonData.status>0) {
							$('#eventSpeakerModalForm').modal('hide');
							$('body').removeClass('modal-open');
							$('.modal-backdrop').remove();
							manageEventSpeakers(e);
						}
					});
				} catch(e) {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
				}
			}
		});
	}
}

function deleteEventSpeaker(e,s){
	let rowCount = $('.speaker_card_container').length;
	$.ajax({
		url: 'ajax_events.php?deleteEventSpeaker=1',
		type: 'post',
		data: {eventid:e,speakerid:s,rows:rowCount},
		success: function(data) {
			$("#"+s).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
			setTimeout(function(){
				$("#"+s).remove();
				if(data != 1){
					$("#speaker_card").html(data);
				}
			}, 1500);
			$('.plus-icon').focus();
		}
	});
}

function addEventExpenseEntry(group_id, event_id, chapter_id, forece_new_entry = false) {
	chapter_id = (typeof chapter_id !== 'undefined') ? chapter_id : '';
	addUpdateExpenseInfoModal(group_id, '', event_id, false, chapter_id,forece_new_entry);
}

function groupMessageList(g,s){
	localStorage.setItem("manage_tab_section",s);
	localStorage.setItem("manage_active", "groupMessageList");
	if (s == 2){
		$('.action-menu').hide();
		$('#newMessage').show();
	}
	$.ajax({
		url: 'ajax_message.php?groupMessageList=1',
		type: 'GET',
		data: {groupid:g},
		success: function(data) {
			$('#ajax').html(data);
			$(".deluser").popConfirm({content: ''});
		}
	});
	$("#emails_li_menu").addClass("submenuActive");
}
function groupMessageDelete(g,m,i){
	$.ajax({
		url: 'ajax_message.php?groupMessageDelete=1',
		type: "POST",
		data:{groupid:g,messageid:m},
		success : function(data) {
			if (i>0){
				jQuery("#"+i).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
			} else {
				let s = localStorage.getItem("manage_tab_section");
				groupMessageList(g,s)
			}
		}
	});
}
function groupMessageForm(g,m,s){
	$.ajax({
		url: 'ajax_message.php?groupMessageForm=1',
		type: 'GET',
		data: {groupid:g,messageid:m},
		success: function(data) {
			$('#ajax').html(data);
			$('.redactor-in').attr('aria-required', 'true');
			$(".confirm").popConfirm({content: ''});
		}
	});
}


function groupMessagePreview(g,m,action){
	$.ajax({
		url: 'ajax_message.php?groupMessagePreview='+action,
		type: 'GET',
		data: {groupid:g,messageid:m},
		success: function(data) {
			if (action == '1'){
				$('#groupMessagePreview').html(data);
				$('#MessageComposer').hide();
				$('#groupMessagePreview').show();
				$(".confirm").popConfirm({content: ''});
			} else {
				$('#viewMessage').html(data);
				$('#viewMessageModal').modal('show');
			}			
			
		}
	});

	$('#viewMessageModal').on('hidden.bs.modal', function (e) {
		$('#'+m).trigger('focus');
	})
}

function groupMessageSave(g,s, jsevent = null) {
	$(document).off('focusin.modal');
	let additionalRecipients = $('#additionalRecipients').val();
	if (additionalRecipients.trim() != ''){
		additionalRecipients = additionalRecipients.match(/[\._a-zA-Z0-9-\+']+@[\._a-zA-Z0-9-]+/ig);
		if (!additionalRecipients || !additionalRecipients.length) {
			swal.fire({title: '<?=gettext("Error");?>', text: '<?= addslashes(gettext("Please enter one or more valid emails"))?>',allowOutsideClick:false});
			return;
		}
		// convert back to string
		additionalRecipients = additionalRecipients.join(", ");
	}
	
	let formdata = $('#send_message_form_composer')[0];
	let finaldata  = new FormData(formdata);
	finaldata.append("section",s);
	finaldata.append("groupid",g);
	finaldata.append("additionalRecipients",additionalRecipients);
	
	$.ajax({
		url: 'ajax_message.php?groupMessageSave=1',
		type: 'POST',
		data: finaldata,
		processData: false,
        contentType: false,
        cache: false,
    tskp_submit_btn: jsevent.target,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status == 1){
					$("#messageid").val(jsonData.val);
					$('#send_message_form_composer')
						.find('.js-view-attachments-btn, .js-upload-attachments-btn')
						.data({
							topictype: 'MSG',
							topicid: jsonData.val,
						});

					groupMessagePreview(g,jsonData.val,1);
				} else {
					swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false});
				}
            } catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});
}
function backToMessageComposer(){
	$("#groupMessagePreview").html('');
	$('#MessageComposer').show();
	$('#groupMessagePreview').hide();
}

// function groupMessageSend(g,m,s){
// 	$.ajax({
// 		url: 'ajax_message.php?groupMessageSend=1',
// 		type: 'POST',
// 		data: {groupid:g,messageid:m},
// 		success: function(data) {
// 			try {
// 				let jsonData = JSON.parse(data);
//                 swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
// 					if (jsonData.status == 1){
//                     	groupMessageList(g,s);
// 					}
//                 });
//             } catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
// 		}
// 	});
// }

function pinUnpinEvent(e,t){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_events.php?pinUnpinEvent=1',
		type: "post",
		data: {'eventid':e,'status':t},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					location.reload();
				});
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}

function showSurveyPublishRestrictionModal(g,s){
	$.ajax({
		url: 'ajax_survey.php?showSurveyPublishRestrictionModal=1',
		type: 'get',
		data: {groupid:g,surveyid:s},
		success: function(data) {
			$("#loadAnyModal").html(data);
			$('#showModal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$(".confirm").popConfirm({content: ''});
			$('.initial').initial({
                charCount: 2,
                textColor: '#ffffff',
				color: window.tskp?.initial_bgcolor ?? null,
                seed: 0,
                height: 40,
                width: 40,
                fontSize: 20,
                fontWeight: 300,
                fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
                radius: 0
            });
		}
	});
}
function sendRequestForSurveyAction(g,s,a){
	$.ajax({
		url: 'ajax_survey.php?sendRequestForSurveyAction=1',
		type: 'post',
		data: {groupid:g,surveyid:s,adminid:a},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
			}
		}
	});
}

function getEmailLogstatistics(g,i,s){
	$.ajax({
		url: 'ajax_emaillogs.php?getEmailLogstatistics=1',
		type: "GET",
		data: {'groupid':g,'id':i,'section':s},
		success : function(data) {
			$('#loadAnyModal').html(data);
			$('#email_logs_statistics').modal('show');
		}
	});
}
function readUrl(input,id,maxsz) {
	id = (typeof id !== 'undefined') ? id : '';
	maxsz = + maxsz;
	if (input.files && input.files[0]) {

        let ext = (input.files[0].name).split('.').pop();
		ext = ext.toLowerCase();
        if (!["pdf","xls","xlsx","ppt","pptx","doc","docx","png","jpeg","jpg"].includes(ext)){
            return false;
        }

        let reader = new FileReader();
		reader.onload = (e) => {
			let imgData = e.target.result;
			let fileData = input.files[0];
			if (fileData.size > maxsz*1024*1024) { alert("Error! File size limit ("+maxsz+"Mb) exceeded"); return false;}
			let fileObj = {};
			fileObj.name = fileData.name;
			fileObj.type = fileData.type;
			fileObj.image = e.target.result;
			let imgName = fileData.name;
			let fakecontainer = $("#fake_drag_drop_container"+id);
			fakecontainer.html(encodeHTMLEntities(imgName));
			fakecontainer.css({'text-align':"center","width":"100%"}	);
			$("#resource_file"+id).val(JSON.stringify(fileObj));

      $(input).closest('form')
        .find('.js-overwrite-resource-file-chk').show()
        .find('input[name="overwrite_resource_file"]').prop('disabled', false);
		}
		reader.readAsDataURL(input.files[0]);

	}
}

function bulkAlbumUpload(input) {
	let maxsz = 1000;
    let album_upload_submit_button = $('#album_upload_submit');

   	album_upload_submit_button.hide();
    if (!input.files || !(input.files.length)) {
        // do nothing
    } else if (input.files.length > 100) {
        swal.fire({title: '<?=gettext("Error");?>', text: "<?= addslashes(gettext('Please choose a maximum of 100 files for the upload.'))?>"});
    } else {
        album_upload_submit_button.show();
        //$('#bulk_album_media_table').remove();
        let fileList = input.files;
		let fileContainer = $('<table id="bulk_album_media_table">' +
							'<caption><?= gettext("Uploaded Media")?></caption>' +
							'<thead>' +
							'<tr>' +
							'<th scope="col"><?= gettext("Name")?></th>' +
							'<th scope="col"><?= gettext("Size")?></th>' +
							'<th scope="col"><?= gettext("Alt Tags")?> <i tabindex="0" class="fa fa-info-circle" aria-label="<?= addslashes(gettext("Alt text is a contraction of alternative text. It is a short written description of an image, which makes sense of that image when it can not be viewed for some reason."))?>" data-toggle="tooltip" data-placement="top" title="<?= addslashes(gettext("Alt text is a contraction of alternative text. It is a short written description of an image, which makes sense of that image when it can not be viewed for some reason."))?>"></i><span class="alt-tag-validation ml-2" role="alert" style="color:#ee0000c2;font-size: 14px;"></span></th>' +
							'<th scope="col" class="removable"></th>' +
							'</tr>' +
							'</thead>' +
							'</table>');
		let fileContainerBody = $('<tbody></tbody>');
		let filecontainerlastfile = 0;
   		if($('#bulk_album_media_table tr').length > 0){
			fileContainer = $('#bulk_album_media_table');
			fileContainerBody = $('#bulk_album_media_table tbody');
			let getLastDataIndex = parseInt($('#bulk_album_media_table tr:last td:first').attr('data-index'));
			filecontainerlastfile = getLastDataIndex+1;

		}

        for (let i = 0; i < fileList.length; i++)  //for multiple files
        {
            let fileRow = $("<tr></tr>");
            let fileLabel = fileList[i].name;

            //let ext = fileLabel.split('.').pop();
			let ext = fileLabel.substr(fileLabel.lastIndexOf("."));
			ext = ext.toLowerCase();
			/* define allowed file types */
            let allowedExtensionsRegx = /(\.jpg|\.jpeg|\.png|\.gif|\.avi|\.mov|\.mpeg|\.mp4|\.wmv)$/i;
			/* testing extension with regular expression */
            let isAllowed = allowedExtensionsRegx.test(ext);

			if(!isAllowed){
				alert("Skipping - "+fileLabel+"! Invalid extension");
				continue;
			}
			
            // if (!["png","jpeg","jpg","gif","avi","mov","mpeg","mp4","wmv"].includes(ext)){
			// 	alert("Skipping - "+fileLabel+"! Invalid extension");
			// 	continue;
            // }

			if (fileList[i].size > maxsz*1024*1024) {
				alert("Skipping - "+fileLabel+"! File size limit ("+maxsz+"Mb) exceeded");
				continue;
			}


			if (fileLabel.length > 20) {
                fileLabel = fileLabel.slice(0, 20) + "...";
            }

            $('<td data-processed="0" data-index="' + filecontainerlastfile + '" data-name="'+fileList[i].name+'" data-type="'+fileList[i].type+'">' + fileLabel + "</td>").appendTo(fileRow);
            $('<td>' + fileSizeFormatter(fileList[i].size) + '</td>').appendTo(fileRow);
			$('<td><input type="text" aria-label="<?= gettext("Alt tags"); ?>" class="form-control mb-2 media-title" name="media_alt_text"> </td>').appendTo(fileRow);
            // $('<td> <div id="media-spinner' + filecontainerlastfile + '" class="spinner-border spinner-border-sm hidden"  style="margin-left: 6px; color: #c6c6c6;" role="status"></div> \n' +
            //     '<svg xmlns="http://www.w3.org/2000/svg" id="media-success' + filecontainerlastfile + '" width="30" height="30" fill="green" class="bi bi-check hidden" viewBox="0 0 16 16">\n' +
            //     '<path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>\n' +
            //     '</svg> </td>').appendTo(fileRow);
            // if (fileList[i].type.indexOf('video') > -1 || true) {   // cropping disabled for multi-upload
            //     $('<td class="td_icons removable"></td>').appendTo(fileRow);
            // } else {
            //     $('<td class="td_icons removable"><i data-item="'+filecontainerlastfile+'" class="edit-file fa fas fa-edit" aria-hidden="true"></i></td>').appendTo(fileRow);
            // }

            $('<td class="td_icons removable"><div id="media-spinner' + filecontainerlastfile + '" class="spinner-border spinner-border-sm hidden"  style="margin-left: 6px; color: #c6c6c6;" role="status"></div> \n' +
			'<svg xmlns="http://www.w3.org/2000/svg" id="media-success' + filecontainerlastfile + '" width="30" height="30" fill="green" class="bi bi-check hidden" viewBox="0 0 16 16">\n' +
			'<path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>\n' +
			'</svg><i role="button" aria-label="<?= gettext("Delete "); ?>'+fileList[i].name+'" tabindex="0" data-item="'+filecontainerlastfile+'" class="remove-file fa fa-trash"></i></td>').appendTo(fileRow);
            fileRow.appendTo(fileContainerBody);

              window.Tksp ||= {};
              window.Tksp.album ||= {};
              window.Tksp.album.selected_files ||= [];
              window.Tksp.album.selected_files.push(fileList[i]);
			filecontainerlastfile++;
        }

        fileContainerBody.appendTo(fileContainer);
        fileContainer.appendTo($('#updateAlbumModal .modal-body'));

        /* Delete file */
        $('.remove-file').bind('click', function(){
            var tr = $(this).parent().parent();
              var tr_index = tr.prevAll().length;
              window.Tksp.album.selected_files.splice(tr_index, 1);
            tr.remove();
            if(!$('.remove-file').length) {
                $('#album_upload_submit').addClass('hidden');
				$('.file_data').remove();
				$('#bulk_album_media_table').remove();
				$('input[type="file"]').val(null);	
            }
        });

		$('.remove-file').bind('keypress', function(event){
			if (event.key === "Enter") {
				$(this).parent().parent().remove();
				if(!$('.remove-file').length) {
					$('#album_upload_submit').addClass('hidden');
				}
			}
		});

        /* Edit file */
        $('.edit-file').bind('click', function(){
            // ToDo implement image cropping
        });

    }
}

function bulkFileUpload(input) {

	let bulkFileUploadSubmit = $('#bulkFileUploadSubmit');
	bulkFileUploadSubmit.addClass('hidden');
	let maxsz = 50;
	if (!input.files || !(input.files.length)) {
		// do nothing
	} else if (input.files.length > 10) {
		swal.fire({title: 'Error', text: "<?= addslashes(gettext('Please select 10 files or less.'))?>"});

	} else {
		// $('#bulk_file_table').remove();
		bulkFileUploadSubmit.removeClass('hidden');
		let fileList = input.files;
		let fileContainer = $('<table id="bulk_file_table"><caption><?= gettext("Uploaded file details")?></caption>' +
		'<thead>' +
		'<tr>' +
			'<th scope="col"><?= gettext("Name");?></th>' +
			'<th scope="col"><?= gettext("Size");?></th>' +		
			'<th scope="col" class="progress_header"><?= gettext("Progress");?></th>' +			
			'<th scope="col" class="removable"><?= gettext("Edit");?></th>' +
			'<th scope="col" class="removable"><?= gettext("Delete");?></th>' +
		'</tr>' +
		'</thead>' +
		'</table>');

		let fileContainerBody = $('<tbody></tbody>');
		let filecontainerlastfile = 0;
		if($('#bulk_file_table tr').length > 0){
			fileContainer = $('#bulk_file_table');
			fileContainerBody = $('#bulk_file_table tbody');
			let getLastDataIndex = parseInt($('#bulk_file_table tr:last td:first').attr('data-index'));
			filecontainerlastfile = getLastDataIndex+1;

		}

		for (let i = 0; i < fileList.length; i++)  //for multiple files
		{
		let fileRow = $("<tr></tr>");
		let fileLabel = fileList[i].name;

		let ext = fileLabel.split('.').pop();
		ext = ext.toLowerCase();
		
		if (!["pdf","xls","xlsx","ppt","pptx","doc","docx","png","jpeg","jpg"].includes(ext)){
			alert("Skipping - "+fileLabel+"! Invalid extension");
			continue;
		}

		if (fileList[i].size > maxsz*1024*1024) {
			alert("Skipping - "+fileLabel+"! File size limit ("+maxsz+"Mb) exceeded");
			continue;
		}

		if (fileLabel.length > 20) {
			fileLabel = fileLabel.slice(0, 20) + "...";
		}

		$('<td data-index="' + filecontainerlastfile + '" data-name="'+fileList[i].name+'" data-type="'+fileList[i].type+'">' + fileLabel + "</td>").appendTo(fileRow);
		$('<td>' + fileSizeFormatter(fileList[i].size) + '</td>').appendTo(fileRow);		
		$('<td class="progress_row"><div class="progress">\n' +
			'  <div aria-live="polite" class="progress-bar" id="progress-bar-' + filecontainerlastfile + '" style="width:0%"><span>0</span></div>\n' +
			'</div></td>').appendTo(fileRow);		
		$('<td class="td_icons removable"><i role="button" aria-label="<?= gettext("Edit")?> '+fileList[i].name+'" id="edit-file-title" tabindex="0" data-item="'+filecontainerlastfile+'" class="edit-file fa fas fa-edit"></i></td>').appendTo(fileRow);
		$('<td class="td_icons removable"><i role="button" tabindex="0" aria-label="<?= gettext("Delete")?> '+fileList[i].name+'" data-item="'+filecontainerlastfile+'" class="remove-file fa fa-trash"></i></td>').appendTo(fileRow);
		fileRow.appendTo(fileContainerBody);

		let reader = new FileReader();
		reader.onload = (function(file, filecontainerlastfile){
			return function(e){
			let fileObj = {};
			fileObj.image = e.target.result;
			let file_input = $('<input class="file_data" type="hidden" data-index="' + filecontainerlastfile + '">');
			file_input.val(JSON.stringify(fileObj));
			file_input.appendTo($('#updateResourceModal .modal-body'));

			};
		})(fileList[i],filecontainerlastfile);
		reader.readAsDataURL(fileList[i]);
		filecontainerlastfile++;
		}

		fileContainerBody.appendTo(fileContainer);
		fileContainer.appendTo($('#updateResourceModal .modal-body'));

		/* Delete file */
		$('.remove-file').bind('click', function(event){
			$(this).parent().parent().remove();
			if(!$('.remove-file').length) {
                $('#bulkFileUploadSubmit').addClass('hidden');
            }
		});
		$('.remove-file').bind('keypress', function(event){
			if (event.key === "Enter") {
				$(this).parent().parent().remove();
				if(!$('.remove-file').length) {
					$('#bulkFileUploadSubmit').addClass('hidden');
				}
			}
		});

		/* Edit file */
		$('.edit-file').bind('click', function(){
		let fileName = $(this).parent().parent().children(0).attr('data-name');
		$(this).parent().parent().hide();

		$('.edit-file').hide();
		$('.remove-file').hide();
		$('.bulkFileUploadSubmit').hide();

		let edit_row = $('<div id="file_edit_row"></div>')
		$('<input aria-label="name" id="file_name_edit" value="' + fileName + '">').appendTo(edit_row);

		let ok_button =  $('<button type="button" class="btn-primary btn-sm">Ok</button>');
		let cancel_button =  $('<button type="button" class="btn-secondary btn-sm">Cancel</button>');

		cancel_button.bind('click', function(){
			$('#file_edit_row').remove();
			let blank_row = $('#blank_row');
			blank_row.next().show();
			blank_row.remove();
			$('.edit-file').show();
			$('.remove-file').show();
			$('.bulkFileUploadSubmit').show();
		});

		ok_button.bind('click', function(){
			let blank_row = $('#blank_row');
			let new_filename = $('#file_name_edit').val();
			let fileLabel = new_filename;
			if (fileLabel.length > 20) {
			fileLabel = fileLabel.slice(0, 20) + "...";
			}
			blank_row.next().children().first().html(fileLabel);
			blank_row.next().children().first().attr("data-name", new_filename);

			$('#file_edit_row').remove();
			blank_row.next().show();
			blank_row.remove();
			$('.edit-file').show();
			$('.remove-file').show();
			$('.bulkFileUploadSubmit').show();
		});

		ok_button.appendTo(edit_row);
		cancel_button.appendTo(edit_row);
		edit_row.insertBefore($(this).parent().parent());
		$('<tr id="blank_row"><td style="height:45px;"></td></tr>').insertBefore($(this).parent().parent());

		});

	}

	//On Enter Key...
	$(function(){ 
		$("#edit-file-title").keypress(function (e) {
			if (e.keyCode == 13) {
				$(this).trigger("click");
			}
		});
	});
}

function submitFolderIcon(id, update, ch, chnl, is_resource_lead_content) {
	$(document).off('focusin.modal');
	let parent_id = $('#parent_id').val();
	// let resource_type = $('#resource_type').val();
	let formdata = $('#resourceForm')[0];
	let finaldata = new FormData(formdata);


	let resource_file = $("input[name=resource_file_data]").val();
	if (resource_file) {
		let media = JSON.parse(resource_file);
		let base64 = media.image;
		let type = media.type;
		let block = base64.split(";");

		// get the real base64 content of the file
		let realData = block[1].split(",")[1];

		// Convert to blob
		let blob = b64toBlob(realData, type);

		const fileData = new File([blob], media.name, {type: type});
		finaldata.append('resource_file', fileData);
	} else if (!update) {
		swal.fire({title: '<?=gettext("Error");?>', text: '<?= gettext("Please select a file to upload");?>', allowOutsideClick: false});
		setTimeout(() => {
			$(".swal2-confirm").focus();
		}, 500)
		return false;
	}

	finaldata.append("parent_id", parent_id);
	let section = "";
	if (typeof $('#group_chapter_channel_id').find(':selected').data('section') !== 'undefined') {
		section = $('#group_chapter_channel_id').find(':selected').data('section');
	}
	finaldata.append("section", section);
	finaldata.append("is_resource_lead_content", is_resource_lead_content);

	$("#resource_file").prop('disabled', true);
	$("#attachment").prop('disabled', true);
	preventMultiClick(1);
	$.ajax({
		url: 'ajax_resources.php?submitFolderIcon=' + id,
		type: 'POST',
		enctype: 'multipart/form-data',
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
		success: function (data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({
					title: jsonData.title,
					text: jsonData.message,
					allowOutsideClick: false
				}).then(function (result) {
					if (jsonData.status > 0) {
						$('#resourceIconModal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();

						let dataVal = jsonData.val;
						getResourceChildData(dataVal.parent_id, dataVal.groupid, dataVal.chapterid, data.channelid);
					}
				});
			} catch (e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>", allowOutsideClick: false});
			}
			setTimeout(() => {
				$(".swal2-confirm").focus();
			}, 500);
			$("#resource_file").prop('disabled', false);
			$("#attachment").prop('disabled', false);
		}
	});
}

function uploadFileObject(finaldataArray, i, parent_id, resource_id, id, ch, chnl) {

	if (i >= finaldataArray.length) {
		$('#close_button').bind('click', function () {
			$('#resourceModal').modal('hide');
			$('body').removeClass('modal-open');
			$('.modal-backdrop').remove();
			getResourceChildData(parent_id, id, ch, chnl);
		}).show();
	} else {

		$.ajax({
			url: 'ajax_resources.php?addUpdateGroupResource=' + id,
			type: 'POST',
			enctype: 'multipart/form-data',
			data: finaldataArray[i],
			processData: false,
			contentType: false,
			cache: false,
			xhr: function () {
				let myXhr = $.ajaxSettings.xhr();
				if (myXhr.upload) {
					// For handling the progress of the upload
					myXhr.upload.addEventListener('progress', function (e) {
						if (e.lengthComputable) {
							let percent = e.loaded * 100 / e.total;
							$('#progress-bar-' + finaldataArray[i].get('index') + '').css("width", percent + "%");
							$('#progress-bar-' + finaldataArray[i].get('index') + ' span').text(percent + "%");

						}
					}, false);
				}
				return myXhr;
			},
			success: function (data) {
				try {
					if (null == data || "" == data || 0 == (JSON.parse(data)).status) {
						$('#bulk_file_table td[data-index=' + finaldataArray[i].get('index') + ']').css('color', 'red');
						$('#progress-bar-' + finaldataArray[i].get('index') + ' span').text("failed");
					}
				} catch (e) {
					$('#bulk_file_table td[data-index=' + finaldataArray[i].get('index') + ']').css('color', 'red');
					$('#progress-bar-' + finaldataArray[i].get('index') + ' span').text("failed");
				}

				setTimeout(function () {
					uploadFileObject(finaldataArray, i + 1, parent_id, resource_id, id, ch, chnl);
				}, 500);
			}
		});
	}

}

function bulkAlbumUploadSubmit(album_id,gid,cid,chid){
    $('#updateAlbumModal button').hide();
    $('.bulkDnD').hide();
    $('.edit-file, .remove-file').hide();
	$(".alt-tag-validation").html("");
    //$('.removable').remove();
	
    setTimeout(function(){

        let albumDataArray = [];
		let startIndex = 0;
        $('#bulk_album_media_table tbody tr').each(function(i){

            /* let file_metadata_row = $('#bulk_album_media_table tbody tr td'); */
            let file_metadata_row = $(this).children().first();
			let alt_tag = $(this).find('.media-title').val();
			
			let processed = file_metadata_row.attr("data-processed");
			if (processed == "0"){
				$(file_metadata_row).attr('data-processed', "1");
				let index = file_metadata_row.attr("data-index");
				let filename = file_metadata_row.attr("data-name");
				let type = file_metadata_row.attr("data-type");
				let blob = null;

				let file_name_data = JSON.stringify({"action":"getUploadURL","filename":filename,"type":type,"album_id":album_id,"alt_tag":alt_tag});

				albumDataArray[i] = {
					"file_name_data": file_name_data,
					"blob": blob					
				};
			} else {
				startIndex++;
			}
        });
        uploadAlbumMediaObject(albumDataArray, startIndex, gid,cid,chid);
		
    },100);

}


function uploadAlbumMediaObject(albumDataArray, i, gid,cid,chid){	
	
    if (i >= albumDataArray.length) {
        $("#close_button2").show();
    } else {

        // add spinner to item i
        $("#media-spinner" + i).removeClass('hidden');

        $.ajax({
            url: 'ajax_albums.php?albumGetUploadURL=1',
            type: "POST",
            data: {
                data: albumDataArray[i]["file_name_data"]
            },
            success: function (file_url_data) {
                file_url_data = JSON.parse(file_url_data);


                if ("success" === file_url_data.status) {
                    var file_data = window.Tksp.album.selected_files[i];

                    $.ajax({
                        url: file_url_data.presigned_url,
                        type: "PUT",
                        data: file_data,
                        processData: false,
                        contentType: false,
                        success: function (data) {

                            // finalize upload
                            let finalize_data = JSON.stringify({
                                "action":"finalizeAlbumMediaUpload",
                                "media_uuid":file_url_data.media_uuid,
                                "ext":file_url_data.ext,
                                "album_id":file_url_data.album_id,
								"alt_tag":file_url_data.alt_tag
                            });
                            $.ajax({
                                url: 'ajax_albums.php?albumFinalizeAlbumMediaUpload=1',
                                type: "POST",
                                data: {
                                    data: finalize_data
                                },
                                success: function (response) {
									response = JSON.parse(response);									
                                    if ("success" === response.status) { 									              
										                        
										// replace spinner for item i with "done" checkmark
										$("#media-spinner" + i).addClass('hidden');										
										$("#media-success" + i).removeClass('hidden');
										uploadAlbumMediaObject(albumDataArray, i + 1, gid, cid, chid);

										$("#hidden_div_for_notification").attr("role","status");
										$("#hidden_div_for_notification").attr("aria-live","polite");
										$('#hidden_div_for_notification').html(""+file_url_data.filename+" <?= gettext('File upload complete.');?>");
										

                                    } else {
                                        swal.fire({
                                            title: 'Error',
                                            text: response.message
                                        }).then(function (result) {
                                            getAlbums(gid, cid, chid);
                                        });
                                    }
                                }
                            });
                        }
                    });
                } else {
                    swal.fire({
                        title: '<?=gettext("Error");?>',
                        text: "<?= addslashes(gettext('Something went wrong. Please try again.'))?>"
                    }).then(function (result) {
                        getAlbums(gid, cid, chid);
                    });
                }
            }
        });
    }
}

function bulkFileUploadSubmit(id,update,ch,chnl,is_resource_lead_content){
	preventMultiClick(1);
  let parent_id = $('#parent_id').val();
	let resource_id = $('input[name="resource_id"]').val();
		$('.removable').remove();
		$('.progress_row').show();
		$('.progress_header').show();

	let finaldataArray = [];
	setTimeout(function(){

    $('#bulk_file_table tbody tr').each(function(i){
      let file_metadata_row = $(this).children().first();
      let index = file_metadata_row.attr("data-index");
      let resource_file = $('input.file_data[data-index=' + index + ']').val();
      let type = file_metadata_row.attr("data-type");

      let media  = JSON.parse(resource_file);
      let base64 = media.image;
      let block  = base64.split(";");

      // get the real base64 content of the file
      let realData = block[1].split(",")[1];

      // Convert to blob
      let blob = b64toBlob(realData, type);

      let finaldata  = new FormData();

      finaldata.append("resource_name",file_metadata_row.attr("data-name"));
      finaldata.append("resource_description","");
      finaldata.append("resource_type", 2);
      finaldata.append("resource_typename", type);
      finaldata.append("parent_id", parent_id);
      finaldata.append("resource_id", resource_id);
      finaldata.append("index", index);
	  finaldata.append("is_resource_lead_content", is_resource_lead_content);

      let fileData = new File([blob],file_metadata_row.attr("data-name"),{ type: type });
      finaldata.append('resource_file', fileData);
      finaldataArray[i] = finaldata;
    });

	if(finaldataArray.length > 0){	
		$('.bulkFileUploadSubmit').hide();		
		$('#resourceModal button').hide();
		$('.bulkDnD').hide();
		uploadFileObject(finaldataArray, 0, parent_id, resource_id, id, ch,chnl);
	}else{		
		swal.fire({title: '<?= gettext("Error")?>', text: "<?= gettext('Please select a file to upload');?>"});
	}

	$(".swal2-confirm").focus(); 
  },100);
  

}

function initMyTeamsContainer(g,c,section){
	section = (typeof section !== 'undefined') ? 1 : 0;
	localStorage.setItem("manage_active", "getManageTeamsContainer");
			
			$.ajax({
				url: './ajax_talentpeak.php?initMyTeamsContainer=1',
				type: 'get',
				data: {groupid:g,chapterid:c,section:section},
				success: function(data) {
					try {
						let jsonData = JSON.parse(data);
				
						if (jsonData.status > 0){
							if (jsonData.status == 2){
								updateTeamStatus(g,jsonData.val[0],jsonData.val[1],0);								
							}							
							getTeamDetail(g,jsonData.val[0]);
							
						} else if (jsonData.status == -1) {
							
							swal.fire({title:jsonData.title,text:jsonData.message}).then(function(result) {
								window.location.href = window.location.href.replace('#getMyTeams','#about');
								location.reload();
							});
						} else {
							swal.fire({title:jsonData.title,text:jsonData.message});
						}
					} catch(e) {
						// Handle Ajax HTML
						$('#ajax').html(data);
					}
				}
			});
}

function getMyTeams(g,c){
	window.location.hash = "getMyTeams";
	localStorage.setItem("manage_active", "getManageTeamsContainer");
	$.ajax({
		url: './ajax_talentpeak.php?getMyTeams=1',
		type: 'get',
		data: {groupid:g,chapterid:c},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);

				if (jsonData.status > 0){
					if (jsonData.status == 2){
						updateTeamStatus(g,jsonData.val[0],jsonData.val[1],0);
					}
					getTeamDetail(g,jsonData.val[0]);
				} else if (jsonData.status == -1) {
					swal.fire({title:jsonData.title,text:jsonData.message}).then(function(result) {
						window.location.href = window.location.href.replace('#getMyTeams','#about');
						location.reload();
					});
				} else {
					swal.fire({title:jsonData.title,text:jsonData.message});
				}
			} catch(e) {
				// Handle Ajax HTML
				$('#dynamicContent').html(data);
				$(".confirm").popConfirm({content: ''});
				$('.initial').initial({
					charCount: 2,
					textColor: '#ffffff',
					color: window.tskp?.initial_bgcolor ?? null,
					seed: 0,
					height: 30,
					width: 30,
					fontSize: 15,
					fontWeight: 300,
					fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
					radius: 0
				});
			}
		}
	});
}

function getUnmatchedUsersForTeam(g,rt="",tfv=''){
	enableDisableTabs('manage-teams-section-tab',1);
	localStorage.setItem("manage_active", "getManageTeamsContainer");
    //const tf = (tfv == '') ? [] : [tfv]; // In the future, when team filters is converted into multi-select we will not need to convert string to array.
	$.ajax({
		url: './ajax_talentpeak.php?getUnmatchedUsersForTeam='+g,
        type: "GET",
		data: {groupid:g, registrationType:rt, teamsFilterValue:tfv},
		success : function(data) {
			$('#manageTeamContent').html(data);
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
			enableDisableTabs('manage-teams-section-tab',0);
		}
	});
}

function getTeamDetail(g,t,i,v,s){	
	let contentId = (typeof i !== 'undefined') ? i : '0';
	let showBasicInfo = (typeof v !== 'undefined') ? v : '0';
	let updateStatus =  (typeof s !== 'undefined') ? s : '';
	let manage_section = $(location).attr('pathname').endsWith('manage') ? 1 : 0;
	if (showBasicInfo!='1'){
		window.location.hash = 'getMyTeams';
	} else {
		let hsh = "getMyTeams-"+t;
		if (manage_section) {
			hsh = "getMyTeams";
		} else if(showBasicInfo){
			hsh = "getMyTeams/initDiscoverCircles-"+t;
		}
		window.location.hash = hsh;
	}
	let activeTab = localStorage.getItem("team_detail_active_tab");
	$.ajax({
		url: './ajax_talentpeak.php?getTeamDetail=1',
		type: 'get',
		data: {groupid:g,teamid:t,manage_section:manage_section,'activeTab':activeTab,'showBasicInfo':showBasicInfo},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message}).then(function(result) {
					location.reload();
				});
				
			} catch(e) {
				if (showBasicInfo !='0') {
					$('#loadAnyModal').html(data);
					$('#team_detal_modal').modal({
						backdrop: 'static',
						keyboard: true
					});
				} else {
					$('#ajax').html(data);
					if (contentId !='0'){
						setTimeout(() => {
							getTaskDetailView(g,t,i,updateStatus);
						}, 200);
					}
				}
				
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
			}
			
		}
	});
}

function openNewTeamModal(g,t,s){
	$.ajax({
		url: './ajax_talentpeak.php?openNewTeamModal=1',
		type: 'get',
		data: {groupid:g,teamid:t,section:s},
		success: function(data) {
			$('#loadAnyModal').html(data);
			$('#newTeamModal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$('.modal').addClass('js-skip-esc-key');
		}
	});
}

function importTeams(g){
	$.ajax({
		url: './ajax_talentpeak.php?importTeams=1',
		type: 'get',
		data: {groupid:g},
		success: function(data) {
			$('#loadAnyModal').html(data);
			$('#importTeamsData').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function importTeamRegistrations(g){
	$.ajax({
		url: './ajax_talentpeak.php?importTeamRegistrations=1',
		type: 'get',
		data: {groupid:g},
		success: function(data) {
			$('#loadAnyModal').html(data);
			$('#importTeamRegistrationData').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function createNewTeam(g,s){
	$(document).off('focusin.modal');
	let formdata = $('#create_team_form')[0];
	let finaldata  = new FormData(formdata);
	finaldata.append("groupid",g);
	finaldata.append("section",s);
	let team_nm = $("#team_name");
	let team_nm_val = team_nm.val().trim();
	if (team_nm_val.length<3) {
		team_nm.focus();
		swal.fire({title: '',text:'<?= gettext("Name required (minimum 3 characters)")?>',allowOutsideClick:false});
		return;
	}
	let team_desr = $("#team_description");
	if ((typeof team_desr.val() !== 'undefined')) {
		let team_desr_val = stripTags(team_desr.val()).trim();
		if (team_desr_val.length < 24) {			
			$('.redactor-in').focus(); 
			swal.fire({title: '',text:'<?= gettext("Description required (minimum 24 characters)")?>',allowOutsideClick:false});
			return;
		}
	}


	$.ajax({
		url: './ajax_talentpeak.php?createNewTeam=1',
		type: 'POST',
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						closeAllActiveModal();
						if (s == 'detail'){
							getTeamDetail(g,jsonData.val[1]);
						} else if(s == 'list') {
							manageTeams(g)
						}
					} else if(jsonData.status == 2) {
						closeAllActiveModal();
						$("#my_team_menu").trigger("click");						
						setTimeout(function () {
							$('.js-start-circles-btn .btn-affinity').focus();
						}, 500);
						
					} else if (jsonData.status == -1) {
						window.location.reload();
					}
					$('.prevent-multi-clicks').focus();

				});
			} catch(e) { swal.fire({title: '<?= gettext("Error")?>', text: "<?= gettext('Unknown error.')?>",allowOutsideClick:false}); }
		}
	});
}

function getTeamsTodoList(g,t){
	let manage_section = $(location).attr('pathname').endsWith('manage') ? 1 : 0;
	window.location.hash = (manage_section) ? "getMyTeams" : "getMyTeams-"+t;
	localStorage.setItem("team_detail_active_tab", "todo");
	$.ajax({
		url: './ajax_talentpeak.php?getTeamsTodoList=1',
		type: 'get',
		data: {groupid:g,teamid:t,manage_section:manage_section},
		success: function(data) {
			$('#loadTeamData').html(data);
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
		}
	});
}
function openCreateTodoModal(g,t,i){
	$.ajax({
		url: './ajax_talentpeak.php?openCreateTodoModal=1',
		type: 'get',
		data: {groupid:g,teamid:t,taskid:i},
		success: function(data) {
			$('#loadAnyModal').html(data);
			$('#todoModal').modal({
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
	});
}
function addTeamTodo(g){
	let errors = "";
	if(!$("#tasktitle").val().trim()){
		errors += '<?= addslashes(gettext("Title,"));?> ';
	}
	let task_type = $("#task_type").val();
	if (task_type == 'todo'){
		if(!$("#assignedto").val()){
			errors += '<?= addslashes(gettext("Completed by,"));?> ';
		}
	} else if(task_type == 'touchpoint') {
		if(!$("#start_date").val()){
			errors += '<?= addslashes(gettext("Due date,"));?> ';
		}
	} else if(task_type == 'feedback'){
		if(!$("#assignedto").val()){
			errors += '<?= addslashes(gettext("For,"));?> ';
		}
		if(!$("#description").val().trim()){
			errors += '<?= addslashes(gettext("Feedback,"));?> ';
		}
	}

	errors = errors.replace(/, $/, ' ');
	
	if (errors){
		errors += " <?= addslashes(gettext('input field cant be empty.'));?>";
		swal.fire({title: '<?= gettext("Error");?>',text:errors});
		setTimeout(() => {
			$(".swal2-confirm ").focus(); 			
		}, 100);
	} else {
		if (task_type == 'feedback'){
			processAddTeamTodo(g,task_type,1)
		} else {
			$('#todoModal').modal('hide');				
			(async () => {
				const { value: checked } = await Swal.fire({
					title: '<?= addslashes(gettext("Email notification confirmation"))?>',
					showClass: {
						popup: 'swal2-text-custom-class'
					},
					inputPlaceholder: '<?= addslashes(gettext("Check the box to send email notification"));?>',
					input: 'checkbox',
					inputValue: 0,
					confirmButtonText: '<?= addslashes(gettext("Continue"))?>',			
					allowOutsideClick: false				
					
				})
				let sendEmail = 0;
				if (typeof checked !== 'undefined' &&  checked == '1') {
					sendEmail = 1;
				}				
				processAddTeamTodo(g,task_type,sendEmail);								
			})();
		}
	}	
	setTimeout(() => {
		$(".swal2-confirm ").focus(); 
		$("#swal2-checkbox").focus();  
	}, 500);
}

function processAddTeamTodo(g,task_type,sendEmail) {
	let formdata = $('#todoForm')[0];
	let finaldata  = new FormData(formdata);
	finaldata.append("groupid",g);
	finaldata.append("sendEmail",sendEmail);
	var submitButton = $(".prevent-multiple-submit");
	submitButton.prop('disabled', 'disabled');
	$.ajax({
		url: './ajax_talentpeak.php?addTeamTodo=1',
		type: 'POST',
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false})				
			} catch(e) {
				if(data){
					swal.fire({title: 'Success',text:data}).then(function(result) {
						if (task_type == 'todo'){
							$('#todo_tab').trigger('click');
							setTimeout(() => {
								$('.action-item').focus();  
							}, 500);
							
						} else if(task_type == 'touchpoint') {
							$('#team_touch_points').trigger('click');												
							setTimeout(() => {
								$('.touch-point').focus();  
							}, 500);
						} else {
							$('#feedback_tab').trigger('click');							
							setTimeout(() => {
								$('.new-feedback').focus();  
							}, 500);
						}
					});					
					$('#todoModal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					let teamid = $("#teamid").val();					
					submitButton.removeAttr('disabled');
				} else {
					swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Something went wrong. Please try again.'))?>"});
					submitButton.removeAttr('disabled');
				}
				setTimeout(() => {
					document.querySelector(".swal2-confirm").focus();   
				}, 500)
			}
			submitButton.removeAttr('disabled');			
		},
		error: function (error) {
			submitButton.removeAttr('disabled');
		}
	});
}

function openSearchTeamUserModal(g,t,m){
	$.ajax({
		url: './ajax_talentpeak.php?openSearchTeamUserModal=1',
		type: 'GET',
		data: {groupid:g,teamid:t,'team_memberid':m},
		success: function(data) {
			$('#manage_team_members_modal').modal('hide');
			$('body').removeClass('modal-open');
			$('.modal-backdrop').remove();
			$('#loadAnyModal').html('');
			$('#loadAnyModal').html(data);
			$('#search_user_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function addUpdateTeamMember(g){
	$(document).off('focusin.modal');
	let formdata = $('#serachUserForm')[0];
	let finaldata  = new FormData(formdata);
	finaldata.append("groupid",g);
	if (!$("#type").val()){
		swal.fire({title: '<?=gettext("Error");?>',text:'<?= addslashes(gettext("Please select a member type."))?>',allowOutsideClick:false});
		return false;
	}
	if (!$("#user_search").val()){
		swal.fire({title: '<?=gettext("Error");?>',text:'<?= addslashes(gettext("Please search a user"))?>',allowOutsideClick:false});
		return false;
	}
	$.ajax({
		url: './ajax_talentpeak.php?addUpdateTeamMember=1',
		type: 'POST',
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false})
				.then(function(result) {
					if(jsonData.status == 1){
						let teamid = $("#teamid").val();
						$('#search_user_modal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						$('#loadAnyModal').html('');
						manageTeamMembers(g,teamid,'0');
					}
				});
			} catch(e) {
				// Nothing to do
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
	
}
function manageTeamMembers(g,t,s){
	$.ajax({
		url: './ajax_talentpeak.php?manageTeamMembers=1',
		type: 'GET',
		data: {groupid:g,teamid:t,section:s},
		success: function(data) {
			if(data!=0){
				$('#loadAnyModal').html(data);
				$('#manage_team_members_modal').modal({
					backdrop: 'static',
					keyboard: false
				});
				$(".delete").popConfirm({content: ''});
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
			} else {
				swal.fire({title: '<?=gettext("Error");?>',text:'<?= addslashes(gettext("Something went wrong. Please try again."))?>'});
			}
		}
	});
}
function deleteTeamMember(g,t,m){
	$.ajax({
		url: './ajax_talentpeak.php?deleteTeamMember=1',
		type: 'POST',
		data: {groupid:g,teamid:t,team_memberid:m},
		success: function(data) {
			if (data){
				$("#"+m).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
			} else {
				swal.fire({title: '<?=gettext("Error");?>',text:'<?= addslashes(gettext("Something went wrong. Please try again."))?>'});
			}

		}
	});
}
function deleteTeamTask(g,t,tsk,type){
	$.ajax({
		url: './ajax_talentpeak.php?deleteTeamTask=1',
		type: 'POST',
		data: {groupid:g,teamid:t,taskid:tsk},
		success: function(data) {
			if (data){
				swal.fire({title: '<?=gettext("Success");?>',text:"<?= addslashes(gettext('Deleted successfully.'))?>"}).then( function(result) {
					// Reload task list
					if(type=='todo'){
						getTeamsTodoList(g,t);
					} else if(type=='touchpoint') {
						getTeamsTouchPointsList(g,t);
					} else {
						getTeamsFeedback(g,t)
					}
				});
			} else {
				swal.fire({title: '<?=gettext("Error");?>',text:'<?= addslashes(gettext("Something went wrong. Please try again."))?>'});
			}

		}
	});
}

function getManageTeamsContainer(g){
	localStorage.setItem("manage_active", "getManageTeamsContainer");
	$.ajax({
		url: './ajax_talentpeak.php?getManageTeamsContainer=1',
        type: "GET",
		data: {groupid:g},
		success : function(data) {
			$('#ajax').html(data);
			manageTeams(g);
		}
	});
}

function manageTeams(g){
	enableDisableTabs('manage-teams-section-tab',1);
	localStorage.setItem("manage_active", "getManageTeamsContainer");

	let erg_filter = '';
	if (localStorage.getItem("erg_filter") !== null){
		erg_filter = localStorage.getItem("erg_filter");
	}
	let erg_filter_section = '';
	if (localStorage.getItem("erg_filter_section") !== null){
		erg_filter_section = localStorage.getItem("erg_filter_section");
	}
	
	$.ajax({
		url: './ajax_talentpeak.php?manageTeams='+g,
        type: "GET",
		data: {'groupid':g,'erg_filter':erg_filter,'erg_filter_section':erg_filter_section},
		success : function(data) {
			$('#manageTeamContent').html(data);
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
			enableDisableTabs('manage-teams-section-tab',0);
		}
	});
}

function getTeamActionButtons(g,t){
	$.ajax({
		url: './ajax_talentpeak.php?getTeamActionButtons=1',
        type: "GET",
		data: {groupid:g,teamid:t},
		success : function(data) {
			$('#ajax').html(data);
		}
	});
}

function deleteTeamPermanently(g){
	$(document).off('focusin.modal');
	let teamid = $('#teamid').val();
	$('#confirm_delete_team_text').val('');
		$('#teamid').val('');

	$.ajax({
		url: './ajax_talentpeak.php?deleteTeamPermanently=1',
		type: 'POST',
		data: {groupid:g,teamid:teamid},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false}).then(function (result) {					
					$('#deleteTeam').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					getManageTeamsContainer(g);
				});
			} catch (e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}
function updateTeamStatus(g,t,s,r){
	
	$(document).off('focusin.modal');
	$.ajax({
		url: './ajax_talentpeak.php?updateTeamStatus=1',
		type: 'POST',
		data: {groupid:g,teamid:t,status:s},
		success: function(data) {			
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false})
				.then(function(result) {					
					if(jsonData.status >0){						
						//if(jsonData.status == 1){
							if(r == '1'){
								location.reload();
								
							} else {
								manageTeams(g);
							}
						//}
						
					}	
					setTimeout(() => {
						$('#teamBtn'+t).focus();
					}, 1000);				
					
				});
			} catch(e) {
				// Nothing to do
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}

function initCommentFileDragDrop(c){
	$(function(){
		$('.file-drag-drop-area').keyup(function(e) {
			if (e.key == 'Enter') {
				$('.file-drag-drop-input').click();
			}
		});
	});
	return '<div class="file-drag-drop-area form-group">'+
		'<span id="fake_drag_drop_container'+c+'">'+
	  		'<span class="file-drag-drop-area-fake-btn">Choose file</span>'+
	  		'<span class="file-drag-drop-msg">or drag and drop file here</span>'+
		'</span>'+
		'<input type="file" tabindex="-1" class="file-drag-drop-input" id="attachment'+c+'" name="attachment'+c+'" accept=".pdf,.xls,.xlsx,.ppt,.pptx,.doc,.docx,.png,.jpeg,.jpg" onchange="readUrl(this,\''+c+'\',10);allowCommentSubmit(\''+c+'\');" >'+
		'<input type="hidden" id="resource_file'+c+'" name="resource_file'+c+'" >'+
	'</div>'+
	'<p style="color:red;font-size: 10px; margin-top:10px;">Note: Only .pdf, .xls, .xlsx, .ppt, .pptx, .doc, .docx, .png, .jpeg, .jpg files are accepted!</p>';
}

function showCommentAttachmentInput(c){
	$("#att-div"+c).html(initCommentFileDragDrop(c));
	$("#att-div"+c).toggle(10,'swing');
	$("#submitpost"+c).removeClass('hidden');
	$("#attachmentTrigger"+c+' > span').toggle(10,'swing');
	$("#attachmentTrigger"+c).removeClass('hidden');
	if($("#commentarea"+c).val()=="" && $("#attachment"+c)[0].files.length == 0){
		$("#submitpost"+c).attr('disabled','disabled');
		$("#attachmentTrigger"+c).attr('disabled','disabled');
	}
	$('.file-drag-drop-area').focus();
}
function allowCommentSubmit(c){
	$("#submitpost"+c).removeAttr('disabled');
}

function hideCommentBox(c){
	$("#add_sub_comment_"+c).html('');
}

function initSubmitComment(g,t,c,m){
	$("#resource_file"+c).prop('disabled', true);
	$("#attachment"+c).prop('disabled', true);
	let formdata = $('#commentform'+c)[0];
	let finaldata  = new FormData(formdata);
	finaldata.append("groupid",g);
	finaldata.append("topicid",t);
	finaldata.append("commentid",c);
	let resource_file = $("input[name=resource_file"+c+"]").val();
	preventMultiClick(1);
	if (resource_file){
		$("#commentform"+c).hide();
		$("#uploading_loader"+c).show();
		let media  = JSON.parse(resource_file);
		let base64 = media.image;
		let type   = media.type;
		let block  = base64.split(";");

		// get the real base64 content of the file
		let realData = block[1].split(",")[1];

		// Convert to blob
		let blob = b64toBlob(realData, type);

		const fileData = new File([blob],media.name,{ type: type });
		finaldata.append('media',fileData);
	}
	$.ajax({
		url: 'ajax_comments_likes.php?New'+m+'=1',
        type: 'POST',
		enctype: 'multipart/form-data',
        processData: false,
        contentType: false,
        cache: false,
		data: finaldata,
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$('#teamMessages').html(data);
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

				// Send Email notification silently
				//sendEmailNotificationOfComment(finaldata);
			}
			if(m === "AlbumMediaComment"){
				$("#album_close").focus();
			}
		}
	});

	
}

function openSubcommentInputBox(g,t,c,m){
	$.ajax({
		url: 'ajax_comments_likes.php?sub'+m+'=1',
		type:'POST',
		data:{'groupid':g,'topicid':t,'commentid':c},
		success: function(data){
			$("#add_sub_comment_"+c).html(data);
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
	});
}

function sendEmailNotificationOfComment(inputArray){
	$.ajax({
		url: './ajax_talentpeak.php?sendEmailNotificationOfComment=1',
		type: 'POST',
		processData: false,
        contentType: false,
        cache: false,
		data: inputArray,
		success: function(data) {}
	});
}

function updateCommentCommon(g,t,c){
	let message = $("#"+c).val();	
	$.ajax({
		url: 'ajax_comments_likes.php?updateCommentCommon=1',
        type: "POST",
		data: {'groupid':g,'topicid':t,'commentid':c,'message':message},
        success : function(data){
			try {
				let jsonData = JSON.parse(data);
				if(jsonData.status == 1){
					$("#comment"+c).html(jsonData.val);
					$("#comment"+c).show();
					$("#updatecomment"+c).hide();
					if ($('#album_close').is(':visible')) {
						$("#album_close").focus(); 
					}
				} else{
					swal.fire({title:jsonData.title,text:jsonData.message});
				}
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}			
		}
	});
}
function deleteCommentCommon(g,t,c,p,m){
	$.ajax({
		url: 'ajax_comments_likes.php?delete'+m+'=1',
        type: "POST",
		data: {'groupid':g,'topicid':t,'commentid':c,'parent':p},
        success : function(data){
			if (data>=0){
				if (data == 0){ // Comment
					$("#comment_common_container_"+c).hide('slow').remove();
					$("#container"+c).hide('slow').remove();
					$("#like_comment_count"+c).hide('slow').remove();
					$("#subcomment"+c).hide('slow').remove();				
					
				} else { // Sub comment
					$("#container"+c).hide('slow').remove();
					let reply_count = (parseInt($("#reply_count"+t).html()) - 1);
					$("#reply_count"+t).html(reply_count);					
					
				}
				
				if(m === "AlbumMediaComment"){
					setTimeout(() => {
						$("#album_close").focus();  
					}, 300)
					
				}
				
			} else {
				swal.fire({title: '<?=gettext("Error");?>',text:'<?= addslashes(gettext("Something went wrong. Please try again."))?>'});
			}

			
		}
	});
}

function likeDislikeCommentCommon(g,c,a,l){
	$.ajax({
		url:'ajax_comments_likes.php?likeDislikeCommentCommon=1',
		type: 'POST',
		data: {'groupid':g,'commentid':c,'action':a,'likes':l},
		success: function (data) {
			$("#c_like"+c).html(data);
			setTimeout(() => {
				$("#c_like"+c+ " .fa-thumbs-up").focus();
			}, 200)
			
		}
	});
}
function updateTaskStatus(g,type){
	var submit_btn = $(".tskp_submit_btn");
	let formdata = $('#updateTaskStatusForm')[0];
	let finaldata  = new FormData(formdata);
	if($("#updateStatus").val()){
	  $.ajax({
		url: './ajax_talentpeak.php?updateTaskStatus=1',
		type: 'POST',
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
		tskp_submit_btn: submit_btn,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						let teamid = $("#_teamid").val();
						$('#updateStatusModal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						if(type=='todo'){
							getTeamsTodoList(g,teamid);
						} else {
							getTeamsTouchPointsList(g,teamid);
						}
						setTimeout(() => {
							document.querySelector(".swal2-confirm").focus();   
						}, 500)
					} else if(jsonData.status == 2) {
						location.reload();
					}
                });
            } catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	  });
	} else {
	  swal.fire({title: '<?=gettext("Error");?>',text:'<?= addslashes(gettext("Please choose an action"))?>'});
	}
}

function getTeamsTouchPointsList(g,t){
	let manage_section = $(location).attr('pathname').endsWith('manage') ? 1 : 0;
	window.location.hash = (manage_section) ? "getMyTeams" : "getMyTeams-"+t;
	localStorage.setItem("team_detail_active_tab", "touchpoint");
	$.ajax({
		url: './ajax_talentpeak.php?getTeamsTouchPointsList=1',
		type: 'get',
		data: {groupid:g,teamid:t,manage_section:manage_section},
		success: function(data) {
			$('#loadTeamData').html(data);
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
		}
	});
}

function openCreateTouchpointModal(g,t,i){
	$.ajax({
		url: './ajax_talentpeak.php?openCreateTouchpointModal=1',
		type: 'get',
		data: {groupid:g,teamid:t,taskid:i},
		success: function(data) {
			$('#loadAnyModal').html(data);
			$('#todoModal').modal({
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
	});
}
function getTeamsFeedback(g,t){
	let manage_section = $(location).attr('pathname').endsWith('manage') ? 1 : 0;
	window.location.hash = (manage_section) ? "getMyTeams" : "getMyTeams-"+t;
	localStorage.setItem("team_detail_active_tab", "feedback");
	$.ajax({
		url: './ajax_talentpeak.php?getTeamsFeedback=1',
		type: 'get',
		data: {groupid:g,teamid:t,manage_section:manage_section},
		success: function(data) {
			$('#loadTeamData').html(data);
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
			
		}
	});
}

function openNewFeedbackModal(g,t,i){
	$.ajax({
		url: './ajax_talentpeak.php?openNewFeedbackModal=1',
		type: 'get',
		data: {groupid:g,teamid:t,feedbackid:i},
		success: function(data) {
			$('#loadAnyModal').html(data);
			$('#todoModal').modal({
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
	});
}

function getTaskDetailView(g,t,i, updateStatus){
	updateStatus =  (typeof updateStatus !== 'undefined') ? updateStatus : '';
	if (updateStatus) {
		window.location.hash = "getMyTeams-"+t+"-"+i+"-"+updateStatus;
	} else {
		window.location.hash = "getMyTeams-"+t+"-"+i;
	}
	
	$.ajax({
		url: './ajax_talentpeak.php?getTaskDetailView=1',
		type: 'get',
		data: {groupid:g,teamid:t,taskid:i,updateStatus:updateStatus},
		success: function(data) {
			$('#loadTeamData').html(data);
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
		}
	});
}

function createSubItemTeamTask(g,t,i,s){
	$.ajax({
		url: './ajax_talentpeak.php?createSubItemTeamTask=1',
		type: 'post',
		data: {groupid:g,teamid:t,taskid:i,type:s},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false});
            } catch(e) { 

				$('#loadAnyModal').html(data);
				$('#todoModal').modal({
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

function assignTeamToUserForm(g,u,r,s){
	$.ajax({
		url: './ajax_talentpeak.php?assignTeamToUserForm=1',
		type: 'GET',
		data: {groupid:g,userid:u,roleid:r,section:s},
		success: function(data) {
			$('#loadAnyModal').html(data);
			$('#team_assignment').modal('show');
		}
	});
}

function createOrAssignExitingTeamToUser(g){
	$(document).off('focusin.modal');
	let formdata = $('#create_or_assign_team')[0];
	let finaldata  = new FormData(formdata);
	let type = $("#type").val();
	finaldata.append("groupid",g);
	finaldata.append("type",type);
	$.ajax({
		url: './ajax_talentpeak.php?createOrAssignExitingTeamToUser=1',
		type: 'POST',
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						$('#team_assignment').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						
						let teamid = jsonData.val;
						manageTeamMembers(g,teamid,'0');
						getUnmatchedUsersForTeam(g);
					}
                });
            } catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});
}

function getSuggestedUserForTeam(g,t,tr){
	$.ajax({
		url: './ajax_talentpeak.php?getSuggestedUserForTeam=1',
		type: 'GET',
		data: {groupid:g,teamid:t,roleid:tr},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);

				if (jsonData.status == 1){
					$("#suggetion_container").show();
					$('#suggested_content').html("<p class='pl-5'>-"+jsonData.message+"!-</p>");
				} else {
					$("#suggetion_container").hide();
					$("#suggested_content").html('');
				}

            } catch(e) {
				$("#suggetion_container").show();
				$('#suggested_content').html(data);
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

function getProgramJoinOptions(gid,id,pre,version,t,i,js){
	let teamid = (typeof t !== 'undefined') ? t : 0;
	let inviteid = (typeof i !== 'undefined') ? i : 0;  
	$.ajax({
		url: 	'./ajax_talentpeak.php?getProgramJoinOptions=1',
		type: 	'GET',
		data: 	{ groupid:gid,roleid:id,preselected:pre,version,'teamid':teamid, 'inviteid':inviteid,'joinstatus':js},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						location.reload();
					}
                });
            } catch(e) {
				closeAllActiveModal();
				
				$('#modal_replace').html(data);
				$('#follow_program').modal({
					backdrop: 'static',
					keyboard: false
				});
				$(".confirm").popConfirm({content: ''});
			}
		}
	});
}

function manageJoinRequests(gid, version = 'v1'){
	$.ajax({
		url: 	'./ajax_talentpeak.php?manageJoinRequests=1',
		type: 	'GET',
		data: 	{
			groupid: gid,
			version
		},
		success: function(data){
			switch (version) {
				case 'v1':
			$('#loadAnyModal').html(data);
			$('#joinRequestModal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$(".confirm").popConfirm({content: ''});
					return;

				case 'v2':
					$('.js-team-join-requests-container').html(data);
					return;
			}
		}
	});
}


function joinProgram(gid,action){
	$.ajax({
		url: 	'ajax.php',
		type: 	'GET',
		data: 	{ getFollowChapterChannel:gid,action:action},
		success: function(data){
			swal.fire({title: '<?=gettext("Success");?>', text: "<?= addslashes(gettext('Join request sent successfully'))?>"}).then(function (result) {
				location.reload();
			});
		}
	});
}

function onCancelRegistrationClickByProgramLeader(groupid, roleid, userid, groupname, rolename) {
    let subject = `Cancelled: Join request to ${groupname} (${rolename} role)`;
    let message = `Your request to join ${groupname} for the ${rolename} role has been cancelled by program administrator.`;

    $('#cancel-email-subject').val(subject);
    $('#cancel-email-body').val(message);
    $('#cancelEmailModal').modal('show');

    $('input[name="cancel-email-option"]').on('change', function () {
        if ($(this).val() === 'send_email') {
            $('#cancelRegistrationEmailFields').show();
        } else {
            $('#cancelRegistrationEmailFields').hide();
        }
    });

    $('#send-cancel-email').off('click').on('click', function () {
        $('#cancelEmailModal').modal('hide');
        let sendEmail = $('input[name="cancel-email-option"]:checked').val();
        let subject = $('#cancel-email-subject').val().trim();
        let message = $('#cancel-email-body').val().trim();
        cancelTeamJoinRequest(groupid, roleid, userid, undefined, 'v1', 'decline', sendEmail, subject, message);
    });
}

function cancelTeamJoinRequest(g,r,u,table,version='v1', action='cancel', sendEmail = 'send_email', subject='', message=''){
	$(document).off('focusin.modal');
	table = (typeof table !== 'undefined') ? table : 0;
	$.ajax({
		url: './ajax_talentpeak.php?cancelTeamJoinRequest=1',
		type: 'POST',
		data: {groupid:g,roleid:r,userid:u, action:action, sendEmail: sendEmail, subject: subject, message:message},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						if (table){
							getUnmatchedUsersForTeam(g);
						} else {
							if (version === 'v1') {
							location.reload();
							} else {
								closeAllActiveModal();
								$("#join").focus();
								getFollowChapterChannel(g,2);
							}
						}
					}
                });
            } catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});
}

function togglePauseTeamJoinRequest(g,r,u,table,version='v1'){
	$(document).off('focusin.modal');
	table = (typeof table !== 'undefined') ? table : 0;
	$.ajax({
		url: './ajax_talentpeak.php?togglePauseTeamJoinRequest=1',
		type: 'POST',
		data: {groupid:g,roleid:r,userid:u},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						// if (table){
						// 	getUnmatchedUsersForTeam(g);
						// } else {
							if (version === 'v1') {
								location.reload();
							} else {
								closeAllActiveModal();
								$("#join").focus();
							}
						// }
					}
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});
}

// function openCollaborationInviteModal(e){
// 	$.ajax({
// 		url: 'ajax_events.php?openCollaborationInviteModal=1',
//         type: "GET",
// 		data: 'eventid='+e,
//         success : function(data) {
// 			$('#loadAnyModal').html(data);
// 			$('#CollaborationInviteModal').modal({
// 				backdrop: 'static',
// 				keyboard: false
// 			});
// 		}
// 	});
// }

function checkPermissionAndMultizoneCollaboration(e,g){
	let cg = $("#collaborate").val();
	let eventid = $("#eventid").val();
	let collaborating_chapterids = $("#collaborating_chapterids").val();
	$.ajax({
		url: 'ajax_events.php?checkPermissionAndMultizoneCollaboration=1',
		type: 'POST',
		data: {eventid:eventid,collaboratedGroupIds:cg,collaborating_chapterids:collaborating_chapterids,host_groupid:g},
		success: function(data) {
			$("#infoMessage").html('');
			$("#infoMessage").hide();
			$("#sendCollaborationRequestText").val('');
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.infoMessage){
					$("#infoMessage").show();
					$("#infoMessage").html(jsonData.infoMessage);
				} 
				if (jsonData.sendCollaborationRequestMessage){
					$("#sendCollaborationRequestText").val(jsonData.sendCollaborationRequestMessage);
				}
			} catch(e) {}
		}
	});
}

function sendEventCollaborationRequest(e){
	let collaborationIds = $("#collaborate").val();
	if (collaborationIds.length>0){
		$.ajax({
			url: 'ajax_events.php?sendEventCollaborationRequest=1',
			type: "POST",
			data: {'eventid':e,'collaborationIds':collaborationIds},
			success : function(data) {
				try {
					let jsonData = JSON.parse(data);

					if (jsonData.status>0){
						$("#loadAnyModal").html('');
						$('#CollaborationInviteModal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
					}

					swal.fire({title:jsonData.title,text:jsonData.message})
					.then(function(result) {
						if(jsonData.status >0){
							let urlString = window.location.pathname;
							let splitUrlArray = urlString.split('/');
							let lastPart = splitUrlArray.pop();
							if (lastPart == 'manage') {
								location.reload();
							}
						}
					});
				} catch(e) {
					// Nothing to do
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
				}
			}
		});
	} else {
		swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Collaboration with groups cannot be empty'))?>"});
	}
}


function openUpdateTaskStatusModal(g,tm,t,v){
	$.ajax({
		url: './ajax_talentpeak.php?openUpdateTaskStatusModal=1',
		type: 'GET',
		data: {groupid:g,teamid:tm,taskid:t},
		success: function(data) {
			$('#loadAnyModal').html(data);
			$("#_teamid").val(tm);
			$("#_taskid").val(t);
			$("#_groupid").val(g);
			$('#updateStatus').val(v);
			$('#updateStatusModal').modal({
			backdrop: 'static',
			keyboard: false
			});
		}
	});
  }
function openMessageReviewModal(g,m){
	$.ajax({
		url: 'ajax_message.php?openMessageReviewModal=1',
		type: "GET",
		data: {'groupid':g,'messageid':m},
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#general_email_review_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$('.selectpicker').multiselect({
				includeSelectAllOption: true,
			});
			$('.multiselect-container input[type="checkbox"]').each(function(index,input){
				$(input).after( "<span></span>" );
			});
		}
	});
}
function sendMessageForReview(g){
	$(document).off('focusin.modal');
	let formdata =	$('#general_email_review_form').serialize();
	$.ajax({
		url: 'ajax_message.php?sendMessageForReview='+g,
		type: "POST",
		data: formdata,
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status) {
					$('#general_email_review_modal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					$('#loadAnyModal').html('');

					if (jsonData.message){
						swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function (result) {
							groupMessageList(g,jsonData.status)
						});
					}
				} else {
					swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false});
				}
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}
  function getLikersList(t,m){
	$.ajax({
		url: 'ajax_comments_likes.php',
        type: "GET",
		data: 'getLikersList'+m+'=1&topicid='+t,
        success : function(data) {
			$('.modal').addClass('js-skip-esc-key');	
			$("#modal_over_modal").html(data);
			$('#users_basic_list').modal({
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
	});
}

function getGroupCustomTabs(gid,tid){
	window.location.hash = "customtab_"+tid;
	localStorage.setItem("manage_active", "manageDashboard");
	$.ajax({
		url: 'ajax_groupHome.php',
        type: "GET",
		data: {getGroupCustomTabs:gid,tabid:tid},
        success : function(data) {
			$('#ajax').html(data);
			newPageTitle = $('#tab_name').html();
			document.title = newPageTitle;
		}
	});
}
function manageOtherFunding(g){
	$.ajax({
        url: 'ajax_budget.php?manageOtherFunding=1',
        type: 'GET',
        data: {'groupid':g},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message})
				.then(function(result) {
					location.reload();
				});
			} catch(e) {
				// Handle Ajax HTML
				$('#loadAnyModal').html('');
				$('#newOtherFundingModal').modal('hide');
				$('body').removeClass('modal-open');
				$('.modal-backdrop').remove();

				$('#loadAnyModal').html(data);
				$('#otherFundingModal').modal({
					backdrop: 'static',
					keyboard: false
				});
				jQuery(".confirm").popConfirm({content: ''});
			}
		}
	});
}

function addEditOtherBudgetModal(g,c,i){
	$.ajax({
        url: 'ajax_budget.php?addEditOtherBudgetModal=1',
        type: 'GET',
        data: {'groupid':g,'chapterid':c,'funding_id':i},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				// Handle Ajax HTML
				$('#loadAnyModal').html('');
				$('#otherFundingModal').modal('hide');
				$('body').removeClass('modal-open');
				$('.modal-backdrop').remove();

				$('#loadAnyModal').html(data);
				$('#newOtherFundingModal').modal({
					backdrop: 'static',
					keyboard: false
				});

				jQuery( "#start_date" ).datepicker({
					prevText:"click for previous months",
					nextText:"click for next months",
					showOtherMonths:true,
					selectOtherMonths: false,
					dateFormat: 'yy-mm-dd'
				});
			}
		}
	});
}

function saveGroupOtherFund(g,c){
	$(document).off('focusin.modal');
	let formdata = $('#otherFundingForm')[0];
	let finaldata  = new FormData(formdata);
	finaldata.append('groupid', g);
	finaldata.append('chapterid', c);
	preventMultiClick(1);
	$.ajax({
        url: 'ajax_budget.php?saveGroupOtherFund=1',
        type: 'POST',
		enctype: 'multipart/form-data',
        data: finaldata,
        processData: false,
        contentType: false,
        cache: false,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						manageOtherFunding(g)
					}
					$('#btn_close').focus();
                });
            } catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }
		}
	});
}

function deleteGroupOtherFund(s,f){
	$.ajax({
		url: 'ajax_budget.php?deleteGroupOtherFund=1',
		type: 'POST',
		data: {'fid':f},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				$("#s"+s).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
			}
			setTimeout(() => {
				$("#btn_close2").focus();
			}, 1000);
		}
	});
}

	function isValidDate(stringDate) {
		const regex = /^\d{4}-\d{2}-\d{2}$/;
		return regex.test(stringDate);
	}
	function isValidDateString(dtStr) {	
		if(isValidDate(dtStr)){
		const [y, m, d] = dtStr.split(/-/);	
		const date = new Date(y,m-1,d);
		const is_valid_year = (date.getYear()+1900 == parseInt(y));
		const is_valid_month = (date.getMonth()+1 == parseInt(m));				
		const is_valid_date = (date.getDate() == parseInt(d));
		return is_valid_year && is_valid_month && is_valid_date;
	}
		
}
function updateCalendarBlockSetting(g,onload){
	onload = (typeof onload !== 'undefined') ? onload : 0;
	let multidays = $('#multiDayEvent').is(":checked");
	let start_date = $('#start_date').val();
	let end_date = $('#end_date').val();

	if(start_date){
		let start_date_label = $('#start_date').labels().text();	
		start_date_label = start_date_label.replace("*","");
		start_date_label = start_date_label.replace("[YYYY-MM-DD]","");	
		let validation_msg = start_date_label+ " field date format should be [YYYY-MM-DD].";
		if(!isValidDateString(start_date)){
			$('#start_date').val('');
			swal.fire({title: '<?=gettext("Error");?>', text: validation_msg}).then(function(result) {
				$('#start_date').focus();
			});
			return;
		}
	}
	if(end_date){
		let end_date_label = $('#end_date').labels().text();
		end_date_label = end_date_label.replace("*","");
		end_date_label = end_date_label.replace("[YYYY-MM-DD]","");	
		let end_date_validation_msg = end_date_label+ " field date format should be [YYYY-MM-DD].";
		if(!isValidDateString(end_date)){
			$('#end_date').val('');
			swal.fire({title: '<?=gettext("Error");?>', text: end_date_validation_msg}).then(function(result) {
				$('#end_date').focus();
			});
			return;
		}
	}	
	

	let start_date_h = $('#start_date_hour').val();
	let start_date_m = $('#start_date_minutes').val();
	let start_period = $("input:radio[name=period]:checked").val()
	let tz_input = $('#tz_input').val();
	if (multidays) { // Multi days event
		let end_date = $('#end_date').val();
		let end_date_h = $('#end_hour').val();
		let end_date_m = $('#end_minutes').val();
		let end_period = $("input:radio[name=end_period]:checked").val()

		if (start_date && end_date && start_date_h && start_date_m && start_period && end_date_h && end_date_m &&  end_period){
			let s_date = start_date+' '+start_date_h+':'+start_date_m+':00 '+start_period;
			let e_date = end_date+' '+end_date_h+':'+end_date_m+':00 '+end_period;
			$.ajax({
				url: 'ajax_events.php?updateCalendarBlockSetting=1',
				type: 'GET',
				data: {groupid:g,section:1,start_date:s_date,end_date:e_date,timezone:tz_input},
				success: function(data) {
					let jsonData = JSON.parse(data);
					$("#is_it_past_date_event").val(jsonData.is_it_past_date_event);
					if (jsonData.status){
						setCalendarBlockPermissionAndValues(jsonData.radio,jsonData.disabled,jsonData.tooltop,onload);
					}
				}
			});
		}
	} else {
		let h_duration = $("#hour_duration").val();
		let m_duration = $('#minutes_duration').val();
		if (start_date_h == ''){
			start_date_h = '01'; // To override the temporary interserver error due to missing hour format on datetime string.
		}
		let s_date = start_date+' '+start_date_h+':'+start_date_m+':00 '+start_period;

		$.ajax({
			url: 'ajax_events.php?updateCalendarBlockSetting=1',
			type: 'GET',
			data: {groupid:g,section:2,start_date:s_date,duration:h_duration+'.'+m_duration,timezone:tz_input},
			success: function(data) {
				let jsonData = JSON.parse(data);
				$("#is_it_past_date_event").val(jsonData.is_it_past_date_event);
				if (jsonData.status){
					setCalendarBlockPermissionAndValues(jsonData.radio,jsonData.disabled,jsonData.tooltop,onload);
				}
			}
		});
	}
}

// date format validation for Registration Window start & end date input field.
function validateRegistrationWindow(){
	let start_date = $('#start_date').val();
	let end_date = $('#end_date').val();

	if(start_date){
		let start_date_label = $('#start_date').labels().text();	
		start_date_label = start_date_label.replace("*","");
		start_date_label = start_date_label.replace("[YYYY-MM-DD]","");	
		let validation_msg = start_date_label+ " <?= gettext('field date format should be [YYYY-MM-DD]')?>.";
		if(!isValidDateString(start_date)){
			$('#start_date').val('');
			swal.fire({title: '<?=gettext("Error");?>', text: validation_msg}).then(function(result) {
				$('#start_date').focus();
			});
			return;
		}
	}
	if(end_date){
		let end_date_label = $('#end_date').labels().text();
		end_date_label = end_date_label.replace("*","");
		end_date_label = end_date_label.replace("[YYYY-MM-DD]","");	
		let end_date_validation_msg = end_date_label+ " <?= gettext('field date format should be [YYYY-MM-DD]')?>.";
		if(!isValidDateString(end_date)){
			$('#end_date').val('');
			swal.fire({title: '<?=gettext("Error");?>', text: end_date_validation_msg}).then(function(result) {
				$('#end_date').focus();
			});
			return;
		}
	}	
}

function setCalendarBlockPermissionAndValues(s,d,m,onload){
	onload = (typeof onload !== 'undefined') ? onload : 0;
	let onLabel = $('#calendarOn');
	let offLabel = $('#calendarOff');
	let onInput = $("#calendar_blocks_on");
	let offInput = $("#calendar_blocks_off");
	let action = $("#action").val();
	let onCheck = false;
	let offCheck = false;
	if(onInput.is(":checked")){
		onCheck = true
	}
	if(offInput.is(":checked")){
		offCheck = true;
	}
	if (s == 1) { //ON
		onLabel.addClass('active');
		offLabel.removeClass('active');
		onCheck = true
		if (action =='update' && offCheck==true && onload){
			onCheck = false;
			offLabel.addClass('active');
			onLabel.removeClass('active');
		}
		offInput.attr('checked', offCheck);
		onInput.attr('checked', onCheck);
	} else if(s == 2){ //OFF
		offLabel.addClass('active');
		onLabel.removeClass('active');
		offCheck = true;
		if (action =='update' && onCheck==true && onload){
			offCheck = false;
			onLabel.addClass('active');
			offLabel.removeClass('active');
		}
		offInput.attr('checked', offCheck);
		onInput.attr('checked', onCheck);
	}

	if (d == 1) {
		onLabel.addClass('disabled');
		offLabel.addClass('disabled');
		if (m){
			setCalendarBlockInfoTooltip(m);
		} else {
			clearCalendarBlockInfoPopover();
		}
	} else {
		onLabel.removeClass('disabled');
		offLabel.removeClass('disabled');
        clearCalendarBlockInfoPopover();
	}
	
	if (!$('#multiDayEvent').is(":checked")){
		clearCalendarBlockInfoPopover();
	}
}

function setCalendarBlockInfoTooltip(m){
	let c_block = $('.calendarBlockBtnBlock');
	c_block.attr("data-original-title",m);
	c_block.attr("data-toggle",'tooltip');
	c_block.attr("data-placement",'top');
	$('[data-toggle="tooltip"]').tooltip();
}

function clearCalendarBlockInfoPopover(){
	let c_block = $('.calendarBlockBtnBlock');
	c_block.removeAttr("data-original-title");
	c_block.removeAttr("data-toggle");
	c_block.removeAttr("data-placement");
	c_block.attr("data-toggle",'buttons');
	$('[data-toggle="tooltip"]').tooltip('dispose');
}


function initDiscoverTeamMembers(g){
	window.location.hash = "getMyTeams/initDiscoverTeamMembers";
	$.ajax({
		url: './ajax_talentpeak.php?initDiscoverTeamMembers=1',
		type: 'GET',
		data: {groupid:g},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 0){
						$("#myteams").trigger("click");
						$("#reamRoleRequest").trigger("click");
					}  else {
						if (jsonData.status != 2){
							$("#my_team_menu").trigger("click");
						}	
					}
				});
			} catch(e) {
				$('#dynamicContent').html(data);
				$(".confirm").popConfirm({content: ''});
				$('.initial').initial({
					charCount: 2,
					textColor: '#ffffff',
					color: window.tskp?.initial_bgcolor ?? null,
					seed: 0,
					height: 30,
					width: 30,
					fontSize: 15,
					fontWeight: 300,
					fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
					radius: 0
				});
			}

		}
	});
}

function discoverTeamMembers(g,s){
	$("#hidden_div_for_notification").html('');
	$("#hidden_div_for_notification").removeAttr('aria-live');  

	let filter_attribute_type = [];
	$('select[name="primary_attribute[]"]').each(function(index,input){
		filter_attribute_type.push($(input).find('option:selected').attr('data-keytype'));
	});
	
	let search = (typeof s !== 'undefined') ? s : 0;
	let form_data = $('#filterByNameForm');
	let isFormExist = (form_data.length > 0);
	let finalData;
	if (isFormExist){
		if (!search) {
			$('#filterByNameForm')[0].reset();
			$('#filter_clear_button').hide();
		} else {
			$('#filter_clear_button').show();
		}
		finalData  = new FormData($('#filterByNameForm')[0]);
		finalData.append('search', search);
		if ($("#showAvailableCapacityOnly").is(":checked")) {
			finalData.append('showAvailableCapacityOnly', 1);
		} else {
			finalData.append('showAvailableCapacityOnly', 0);
		}
		finalData.append('groupid', g);
		finalData.append('filter_attribute_type', filter_attribute_type);
	} else {
		finalData = new FormData(); // start empty Form object this is the case of Networking program type
		finalData.set('search', search);
		finalData.set('groupid', g);
		finalData.set('filter_attribute_type', filter_attribute_type);
	}

	$('#discover_matches').html('');
	$.ajax({
		url: './ajax_talentpeak.php?discoverTeamMembers=1',
		type: 'POST',
		processData: false,
        contentType: false,
        cache: false,
		data: finalData,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					
					if (jsonData.status == 0){
						$("#myteams").trigger("click");
						$("#reamRoleRequest").trigger("click");
					}  else {
						if (jsonData.status != 2){
							$("#my_team_menu").trigger("click");
						}	
					}
				});
			} catch(e) {
				$('#discover_matches').html(data);
				$(".confirm").popConfirm({content: ''});
				$('.initial').initial({
					charCount: 2,
					textColor: '#ffffff',
					color: window.tskp?.initial_bgcolor ?? null,
					seed: 0,
					height: 30,
					width: 30,
					fontSize: 15,
					fontWeight: 300,
					fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
					radius: 0
				});
			}
			if(s === 0){
				document.querySelector("#filterBtn").focus(); 
			}

			setTimeout(() => {	
				$('#hidden_div_for_notification').attr('aria-live', 'polite');    
				$('#hidden_div_for_notification').attr('role', 'status');
				
				let box_Mentee = document.getElementsByClassName("box_Mentee");   
				if(box_Mentee.length >0){ 
					var addedText = document.getElementById("hidden_div_for_notification");
					var newText = '<?= gettext("Found");?> '+box_Mentee[0].innerHTML+' <?= gettext("matches for Mentee and ");?>';
					addedText.append(newText);  
				} else{
					var addedText = document.getElementById("hidden_div_for_notification");
					var newText = '<?= gettext("Found 0 matches for Mentee and");?>';
					addedText.append(newText);
				}
		
				let box_Mentor = document.getElementsByClassName("box_Mentor");       
				if(box_Mentor.length >0){ 
					var addedText = document.getElementById("hidden_div_for_notification");
					var newText =  '<?= gettext("Found");?> '+box_Mentor[0].innerHTML+' <?= gettext("matches for Mentor");?>';
					addedText.append(newText);            
				} else{
					var addedText = document.getElementById("hidden_div_for_notification");
					var newText = '<?= gettext("Found 0 matches for Mentor");?>';
					addedText.append(newText);
				}  
			}, 500);   

		}
	});
}
function openSendDiscoverPairRequestModal(g,r,t,s){
	$.ajax({
		url: 'ajax_talentpeak.php?openSendDiscoverPairRequestModal=1',
		type: 'GET',
		data: {groupid:g,receiver_id:r,receiver_roleid:t,sender_roleid:s},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					discoverTeamMembers(g);
				});
			} catch(e) {
				$('#loadAnyModal').html(data);
				$('#send_discover_pair_request').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
			
		}
	});
}

function sendRequestToJoinTeam(g){
	preventMultiClick(1)
	let formdata = $('#send_discover_pair_request_form')[0];
	let finalData  = new FormData(formdata);
	finalData.append("groupid",g);
	$.ajax({
		url: 'ajax_talentpeak.php?sendRequestToJoinTeam=1',
		type: 'POST',
		processData: false,
        contentType: false,
        cache: false,
		data: finalData,
		success: function(data) {
			preventMultiClick(0)
			closeAllActiveModal();
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					discoverTeamMembers(g);
				});
			} catch(e) { }
		}
	});
}

function getTeamReceivedRequests(g){
	window.location.hash = "getMyTeams/getTeamReceivedRequests";
	$.ajax({
		url: './ajax_talentpeak.php?getTeamReceivedRequests=1',
		type: 'GET',
		data: {groupid:g},
		success: function(data) {
			$('#dynamicContent').html(data);
			jQuery(".confirm").popConfirm({content: ''});
			$('.initial').initial({
				charCount: 2,
				textColor: '#ffffff',
				color: window.tskp?.initial_bgcolor ?? null,
				seed: 0,
				height: 30,
				width: 30,
				fontSize: 15,
				fontWeight: 300,
				fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
				radius: 0
			});
		}
	});
}

function deleteTeamRequest(g,r,src) {
	$.ajax({
		url: 'ajax_talentpeak.php?deleteTeamRequest=1',
		type: "POST",
		data: {'groupid':g,'team_request_id':r},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						if (src == 'requests_sent') {
							getTeamInvites(g);
						} else if(src == 'requests_received'){
							getTeamReceivedRequests(g);
						} else {
							discoverTeamMembers(g);
						}
					}
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }
		}
	});
}

function cancelTeamRequest(g,r,src) {
	$.ajax({
		url: 'ajax_talentpeak.php?cancelTeamRequest=1',
		type: "POST",
		data: {'groupid':g,'team_request_id':r},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						if (src == 'requests_sent') {
							getTeamInvites(g);
						} else {
							getTeamReceivedRequests(g);
						}
					}					
					setTimeout(() => {
						$('#actionBtn_'+r).focus();
					}, 1000);
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }

		}
	});
}

function acceptOrRejectTeamRequest(g,r,s,rejectionReason=''){
	$.ajax({
		url: './ajax_talentpeak.php?acceptOrRejectTeamRequest=1',
		type: 'POST',
		data: {groupid:g,request_id:r,status:s,rejectionReason:rejectionReason},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if(jsonData.status == 1) {
						getTeamReceivedRequests(g);
					}
				});
			} catch(e) { }
		}
	});
}


async function rejectTeamRequest(g,r,s){
	let rejectionReason='';
	
	const { value: retval, isConfirmed } = await Swal.fire({
		title: '<?= addslashes(gettext("Request rejection reason"))?>',
		input: 'textarea',
		inputPlaceholder: '<?= addslashes(gettext("Enter request rejection reason"))?>',
		inputAttributes: {
			'aria-label': '<?= addslashes(gettext("Enter request rejection reason"))?>',
			maxlength: 200
		},
		showCancelButton: true,
		cancelButtonText: '<?= addslashes(gettext("Cancel"))?>',
		confirmButtonText: '<?= addslashes(gettext("Reject Request"))?>',
		allowOutsideClick: () => false,
		inputValidator: (value) => {
			return new Promise((resolve) => {
				if (value) {
					resolve()
				} else {
					resolve('<?= addslashes(gettext("Please enter the reason for the request rejection"))?>')
				}
			})
		},
	});
	if (!isConfirmed) {
		return;
	}
	rejectionReason = retval;
	acceptOrRejectTeamRequest(g,r,s,rejectionReason);
}


function likeUnlikeTopicCommon(t,m, lt = 'like')
{
	$("#like_unlike_notification").html('');
	$("#like_unlike_notification").attr("aria-live","assertive");
	$("#like_unlike_notification").attr("role","alert");

	return new Promise(function (resolve, reject) {
	$.ajax({
		url: 'ajax_comments_likes.php?likeUnlike'+m+'=1',
        type: "POST",
		data: {'topicid':t, 'reactiontype':lt},
			success: function (data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message});
					reject();
			} catch(e) { 
				$("#likeUnlikeWidget").html(data);
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
					resolve();
			}	
			
			setTimeout(() => {
				$('#likeUnlikeTopicCommon').focus();
			}, 500);
			},
			error: function () {
				reject();
			}
	})
	});
}

function viewUnmachedUserSurveyResponses(g,u,t){
	$.ajax({
		url: './ajax_talentpeak.php?viewUnmachedUserSurveyResponses=true',
		type: 'GET',
		data: {groupid:g,userid:u,roleid:t},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message});
			} catch(e) { 
				$('#loadAnyModal').html(data);
				$('#showSurveyResponses').modal({
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

function approveBudgetRequest(g,r){
	$(document).off('focusin.modal');
	let formdata = $('#approveBudgetRequestForm')[0];
	let finalData  = new FormData(formdata);
	finalData.append("groupid",g);
	finalData.append("request_id",r);
	preventMultiClick(1);
	$.ajax({
        url: 'ajax_budget.php?approveBudgetRequest=1',
        type: 'POST',
		processData: false,
        contentType: false,
        cache: false,
		data: finalData,
        success: function(data) {
			try {
				
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if(jsonData.status == 1) {
						manageBudgetExpSection(g);
						isPastRequestsRendered = 1;
						setTimeout(function(){
							showRequstTable();
							}, 1000);

						$('#approveBudgetModal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
					}
				});
			} catch(e) { }
		}
	});

}

function rejectBudgetRequest(g,r){
	$(document).off('focusin.modal');
	let formdata = $('#budgetRequestRejectForm')[0];
	let finalData  = new FormData(formdata);
	finalData.append("groupid",g);
	finalData.append("request_id",r);
	preventMultiClick(1);
	$.ajax({
		url: 'ajax_budget.php?rejectBudgetRequest=1',
        type: 'POST',
		processData: false,
        contentType: false,
        cache: false,
		data: finalData,
        success: function(data) {
			try {
				let jsonData = JSON.parse(data);

				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if(jsonData.status == 1) {
						manageBudgetExpSection(g);
						isPastRequestsRendered = 1;
						setTimeout(function(){
							showRequstTable();
						}, 1000);
						$('#rejectBudgetModal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
					}
				});
			} catch(e) { }

		}
	});
}

function approveBudgetRequestForm(g,r){
	$.ajax({
		url: 'ajax_budget.php?approveBudgetRequestForm=1',
		type: 'GET',
		data: {'groupid':g,'request_id':r},
		success: function(data) {

			$("#loadAnyModal").html(data);
			$('#approveBudgetModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function rejectBudgetRequestForm(g,r){
	$.ajax({
		url: 'ajax_budget.php?rejectBudgetRequestForm=1',
		type: 'GET',
		data: {'groupid':g,'request_id':r},
		success: function(data) {
			$("#loadAnyModal").html(data);
			$('#rejectBudgetModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function getTeamsJoinRequestSurveyReportOptions(g, m){	
	let showOnModel = (typeof m !== 'undefined') ? 1: 0;
	$.ajax({
		url: './ajax_talentpeak.php?getTeamsJoinRequestSurveyReportOptions=1',
		type: 'GET',
		data: {groupid:g,showOnModel:showOnModel},
		success: function(data) {
			if (showOnModel){
				$("#loadAnyModal").html(data);
				$('#download_join_request_options').modal({
					backdrop: 'static',
					keyboard: false
				});
				$("#btn_close").focus();
			} else {
				$('#reportDownLoadOptions').html(data);
				$('#reportDownLoadOptions').show("slow");
				$("#downloadOptions").focus();
			}
			
		}
	});
}

function getTeamsFeedbackReportOptions(g){
	$.ajax({
		url: './ajax_talentpeak.php?getTeamsFeedbackReportOptions=1',
		type: 'GET',
		data: {groupid:g},
		success: function(data) {
			$("#loadAnyModal").html(data);
			$('#theReportModal').modal({
				backdrop: 'static',
				keyboard: false,
			});
		}
	});
}

function teamBuilder(groupid) {
  $.ajax({
    url: './ajax_talentpeak_team_builder.php',
    type: 'GET',
    data: {
      teamBuilder: 1,
      groupid: groupid,
    },
    success: function (data) {
      if (!data) {
        return Swal.fire({
          icon: 'info',
          title: 'Info',
          html: 'There are no unmatched users'
        });
      }
      $('#reportDownLoadOptions').html(data);
      $('#reportDownLoadOptions').show('slow');
    },
  });
}

function openAddMemberForm(){
	$('#addMembersMembers, #addMemberBtnDiv').toggle();
	setTimeout(() => {
		$("#group_chapter_channel_id").focus();
	}, 500);

}

function addNewGroupMember(g){
	$(document).off('focusin.modal');
	let emailsToSendStr = $('#inviteMembersTextArea').val();
	let emailsToSend = emailsToSendStr.match(/[\._a-zA-Z0-9-\+']+@[\._a-zA-Z0-9-]+/ig);

	if (!emailsToSend || !emailsToSend.length) {
		swal.fire({title: '<?= gettext("Error")?>', text: '<?= addslashes(gettext("Please enter one or more valid emails."))?>',allowOutsideClick:false});
		return;
	} else if (emailsToSend.length > 1000) {
		swal.fire({title: '<?= gettext("Error")?>', text: '<?= addslashes(gettext("Too many emails, you can enter a maximum of 1000 emails"))?>',allowOutsideClick:false});
		return;
	}
	let group_chapter_channel_id = $("#group_chapter_channel_id").val();
	let section = $('#group_chapter_channel_id').find(':selected').data('section');

	let noOfEmailsToSend = emailsToSend.length;
	let invitesSent = '';
	let invitesFailed = '';
	let invitesSkipped = '';
	let invitesRestricted = '';
	let itemCurrentlyProcessing = 1;
	$('#form-addMembers')[0].reset();
	$("body").css("cursor", "progress");
	$("#inviteSent").html('');
	$("#inviteFailed").html('');
	$("#totalBulkRecored").html(noOfEmailsToSend);
	$(".progress_status").html("Processing 0/" + noOfEmailsToSend + " email(s)");
	$('div#prgress_bar').width('0%');
	$('#progress_bar').show();

	if(noOfEmailsToSend){
		let p = Math.round((1 / noOfEmailsToSend) * 100);
		$(".progress_status").html("Processing " + 1 + "/" + noOfEmailsToSend);
		$('div#prgress_bar').width(p + '%');
	}

	emailsToSend.forEach( function (item, index) {
		preventMultiClick(1);
		 $.ajax({
			url: 'ajax.php?addNewGroupMember=1',
			type: "POST",
			global: false,
			data: {'groupid':g,'email':item,'group_chapter_channel_id':group_chapter_channel_id,'section':section,'csrf_token':teleskopeCsrfToken},
			success: function (data) {
				if (data == 1) {
					invitesSent += item + ', ';
				}else if (data == 0) {
					invitesFailed += item + ', ';
				}else if (data == 2) {
					invitesSkipped += item + ', ';
				}else if (data == 3) {
					invitesRestricted += item + ', ';
				}
			},
			error: function(e) {
				invitesFailed += item + ', ';
			 }
		}).always(function fn(){
			itemCurrentlyProcessing++;
			if (itemCurrentlyProcessing > noOfEmailsToSend) {
				$("body").css("cursor", "default");
				$(".progress_status").html("<i class='fa fa-check-circle' aria-hidden='true'></i> Completed");
				setTimeout(function () {
					$("#inviteSent").html(invitesSent.replace(/,\s*$/, ""));
					if (invitesFailed) {
						$("#inviteFailed").html(invitesFailed.replace(/,\s*$/, ""));
					}
					if(invitesSkipped){
						$("#invitesSkipped").html(invitesSkipped.replace(/,\s*$/, ""));
					}
					if(invitesRestricted){
						$("#invitesRestricted").html(invitesRestricted.replace(/,\s*$/, ""));
					}
					$("#close_show_progress_btn").show();
					$(".progress_done").show();
				}, 250);
				$(".prevent-multi-clicks").removeAttr("disabled");
			}else{
				let p = Math.round((itemCurrentlyProcessing / noOfEmailsToSend) * 100);
				$(".progress_status").html("Processing " + itemCurrentlyProcessing + "/" + noOfEmailsToSend);
				$('div#prgress_bar').width(p + '%');
		
			}
		});
	});	
}

function updateGroupMemberMembership(g,m){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax.php?updateGroupMemberMembership=1',
		type: 'POST',
		data: {groupid:g,memberUserid:m},
		success: function(data) {
			try {
                let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						$('#table-members-server').DataTable().ajax.reload();
					}
                });
            } catch(e) { 
				$('#loadAnyModal').html(data);
				$('#update_chapter_channel_membership').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});

}

function removeGroupMember(g,m){
	$.ajax({
		url: 'ajax.php?removeGroupMember=1',
		type: 'POST',
		data: {groupid:g,memberUserid:m},
		success: function(data) {
			try {
                let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						$('#table-members-server').DataTable().ajax.reload();
					}
                });
            } catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }			
			setTimeout(() => {
				$(".swal2-confirm").focus();
			}, 500);
		}
	});

}
function requestGroupMembership(g){
	$("#join").off('click');
	$.ajax({
		url: 'ajax.php?requestGroupMembership=1',
		type: 'POST',
		data: {groupid:g},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message})
				.then(function(result) {
					if(jsonData.status == 1){
						closeAllActiveModal();
						location.reload();
					}
					$("#join").on('click');
				});
			} catch(e) { $("#join").on('click');; swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }
		}
	});
}
function getGroupJoinRequests(g){
	$.ajax({
		url: 'ajax.php?getGroupJoinRequests=1',
		type: 'GET',
		data: {groupid:g},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) { 
				$("#joinRequests").html(data);
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
			}
		}
	});
}
function acceptRejectGroupJoinRequest(g,u,a){
	$.ajax({
		url: 'ajax.php?acceptRejectGroupJoinRequest=1',
		type: 'POST',
		data: {groupid:g,userid:u,action:a},
		success: function(data) {
			try {
                let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						getGroupJoinRequests(g);
					}
                });
            } catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
		}
	});
}
function manageJoinRequestEmailSettings(g){
	$.ajax({
		url: 'ajax.php?manageJoinRequestEmailSettings='+g,
        type: "GET",
		success : function(data) {		
			$('#lead_form_contant').html(data);
			$('#joinRequestSettingModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}
function suggestionsPagination(c,r,s){
	let page = $('.div-pagination-active-'+r).data("page");
	let next = page+1;
	let prev = page-1;
	if (next == c){
		$(".next"+r).addClass('disabled');
		$("#modal_close_btn").show();
	} else {
		$("#modal_close_btn").hide();
	}
	if (prev == 1){
		$(".prev"+r).addClass('disabled');
	}
	$(".div-pagination-active-"+r).removeClass('active-page');
	$(".div-pagination-active-"+r).addClass('inactive-page');
	$("#page"+r+'_'+page).removeClass('div-pagination-active-'+r);
	$("#page"+r+'_'+page).addClass('div-pagination-'+r);
	if (s == 1){ // prev
		$(".next"+r).removeClass('disabled');
		$("#page"+r+'_'+prev).addClass('active-page');
		$("#page"+r+'_'+prev).removeClass('inactive-page');
		$("#page"+r+'_'+prev).addClass('div-pagination-active-'+r);
	} else { // next
		$(".prev"+r).removeClass('disabled');
		$("#page"+r+"_"+next).addClass('active-page');
		$("#page"+r+'_'+next).removeClass('inactive-page');
		$("#page"+r+'_'+next).addClass('div-pagination-active-'+r);
	}
}

function resetContentFilterState(s){
	$.ajax({
		url: 'ajax.php?resetContentFilterState=1',
		type: 'GET',
		data: {state:s},
		success: function(data) {
			try {
                let jsonData = JSON.parse(data);
				localStorage.setItem("state_filter", jsonData.state);
				localStorage.setItem("year_filter", jsonData.year);
			} catch(e) {  }
		}
	});
}

function goto_homepage(){
	$.ajax({
		url: 'ajax.php?goto_homepage=1',
		type: 'GET',
		data: {},
		success: function(data) {
			window.location.href = data;
		}
	});
}

function getGroupDiscussions(gid,cid,chid){
	window.location.hash = "discussion";
	localStorage.setItem("manage_active", "manageGroupDiscussions");
	$.ajax({
		url: 'ajax_discussions.php?getGroupDiscussions=1',
        type: "GET",
		data: {'groupid':gid,'chapterid':cid,'channelid':chid},
        success : function(data) {
			$('#ajax').html(data);	
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
	} );
}

function manageGroupDiscussions(g){
	localStorage.setItem("manage_active", "manageGroupDiscussions");
	let state_filter = '';
	if (localStorage.getItem("state_filter") !== null){
		state_filter = localStorage.getItem("state_filter");
	}
	let erg_filter = '';
	if (localStorage.getItem("erg_filter") !== null){
		erg_filter = localStorage.getItem("erg_filter");
	}
	let erg_filter_section = '';
	if (localStorage.getItem("erg_filter_section") !== null){
		erg_filter_section = localStorage.getItem("erg_filter_section");
	}
	let year_filter = '';
	if (localStorage.getItem("year_filter") !== null){
		year_filter = localStorage.getItem("year_filter");
	}
	$('.action-menu').hide();
	$('#newpost').show();
	g = (typeof g !== 'undefined') ? g : '';
	$.ajax({
		url: 'ajax_discussions.php?manageGroupDiscussions=1',
        type: "GET",
		data: 'groupid='+g+'&state_filter='+state_filter+'&erg_filter='+erg_filter+'&year_filter='+year_filter+'&erg_filter_section='+erg_filter_section,
        success : function(data) {
			$('#ajax').html(data);
		}
	});
}

function openCreateDiscussionModal(g,i){
	// Pre check if discussion form is loading from ERG
	var discussionRenderElement = $('#ajax');
	if(discussionRenderElement.length){
		this.closeAllActiveModal();
	}
	
	$.ajax({
		url: 'ajax_discussions.php?openCreateDiscussionModal=1',
		type: "GET",
		data: {'groupid':g,'discussionid':i},
		success : function(data) {

			if(discussionRenderElement.length){
				discussionRenderElement.html(data)
			}else{
				var newDiscussionRenderElement = $('#discussion_detail_modal');
				newDiscussionRenderElement.html(data);
			}
			 
		}
  	});
}

function getDiscussionActionButton(i){
	$.ajax({
		url: 'ajax_discussions.php?getDiscussionActionButton=1',
        type: "GET",
		data: {'discussionid':i},
        success : function(data) {
			$('#dynamicActionButton'+i).html(data);
			$(".confirm").popConfirm({content: ''});
		}
	});
}
function deleteDiscussion(i,s){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_discussions.php?deleteDiscussion=1',
        type: "POST",
		data: {'discussionid':i,'sectioin':s},
        success : function(data) {
			try {
                let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){
						if (s){
							let values = jsonData.val;
							getGroupDiscussions(values.groupid,values.chapterid,values.channelid);
						} else {
							$('#discussion_table').DataTable().ajax.reload();
						}
						let m = $('#confirmationModal');
                        m.modal('hide');
                        $('.modal-backdrop').hide();
						location.reload();
					}
                });
            } catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false}); }

		}
	});
}

function filterDiscussions(g){
	$.ajax({
		url: 'ajax_discussions.php?filterDiscussions='+g,
        type: "GET",
        success : function(data) {
			$('#discussionTableContainer').html(data);
		}
	});
}

function pinUnpinDiscussion(g,i,t){
	$.ajax({
		url: 'ajax_discussions.php?pinUnpinDiscussion=1',
		type: "post",
		data: {'groupid':g,'discussionid':i,'type':t},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					location.reload();
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }
			setTimeout(() => {
				$(".swal2-confirm").focus();
			}, 100)
		}
	});
}

function loadMoreDiscussion(gid,cid,chid) {
	window.location.hash = "announcements";
	let page = $("#pageNumber").val();
	$.ajax({
		url: 'ajax_discussions.php?loadMoreDiscussion=1',
		type: "GET",
		data: {'groupid':gid ,'chapterid':cid,'channelid':chid,'page':page},
		success: function (data) {
			if (data == 1) {
				$('#loadeMoreDiscussioinAction').hide();
			} else {
				$("#pageNumber").val((parseInt(page) + 1));

				const regex = /<span.*>_l_=(.*)<\/span>$/gm;
				let m;
				if ((m = regex.exec(data)) === null) {
					$('#loadeMoreDiscussioinAction').hide();
				} 
				$('#loadeMoreDiscussionRows').append(data);
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
	let lastListItem  = document.querySelectorAll('.discussion-block');
	let last = (lastListItem[lastListItem.length -1]);
	last.querySelector("a").focus();
}

function updateCurrentUrlParameters(data){
	let currentUrl = window.location.href;
	let url = new URL(currentUrl);
	for (var key in data) {
		url.searchParams.set(key, data[key]); 
	}
	let newUrl = url.href; 
	window.history.replaceState(null, null,newUrl);
}

function removeUrlParam(parameter) {
    var url = window.location.href;
    // Check if the URL has the parameter
    var urlParts = url.split('?');
    if (urlParts.length >= 2) {
        // Get the URL parameters
        var params = urlParts[1].split('&');

        // Remove the target parameter
        params = params.filter(function(param) {
            return param.split('=')[0] !== parameter;
        });

        // Reconstruct the URL
        url = urlParts[0] + (params.length > 0 ? '?' + params.join('&') : '');
        window.history.replaceState(null, null, url);
    }
}

function openReviewSurveyModal(g,i){
	
	$.ajax({
		url: 'ajax_survey.php?openReviewSurveyModal='+g,
        type: "GET",
		data: {'surveyid':i},
        success : function(data) {
			$("#loadAnyModal").html(data);
			$('#general_email_review_modal').modal({
				backdrop: 'static',
				keyboard: false
			});			
			$('.selectpicker').multiselect({
				includeSelectAllOption: true,
			});
			$('.multiselect-container input[type="checkbox"]').each(function(index,input){
				$(input).after( "<span></span>" );
			});
			
		}
	});
}

function sendSurveyToReview(i){
	$(document).off('focusin.modal');
	let formdata =	$('#general_email_review_form').serialize();
	$.ajax({
		url: 'ajax_survey.php?sendSurveyToReview='+i,
        type: "POST",
		data: formdata,
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status == 1){
					$('#general_email_review_modal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					$('#loadAnyModal').html('');
					if (jsonData.message){
						swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function (result) {

							if (parseInt(jsonData.val)>0){
								getGroupSurveys(i);
							} else {
								getAdminSurveys();
							}
						});
					}
				} else {
					swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false});
				}
            } catch(e) {
                swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false});
            }
		}
	});
}
function getTeamsEventsTimeline(gid, cid, chid, type){
	window.location.hash = "events";
	let by_start_date = "";
	let by_end_date = "";
	let by_volunteer = "";
	if(type==1){
		$('#event_filter_container').show();
		by_start_date = $("#filter_by_start_date").val();
		by_end_date = $("#filter_by_end_date").val();
		by_volunteer = $("#filter_by_volunteer").val();
	} else{
		$('#event_filter_container').hide();
	}
	
	$.ajax({
		url: 'ajax_talentpeak.php?getTeamsEventsTimeline=1',
        type: "GET",
		data: {'groupid':gid,'type':type,'by_start_date':by_start_date,'by_end_date':by_end_date,'by_volunteer':by_volunteer,'chapterid':cid,'channelid':chid},
		success : function(data) {
			$('#loadEventsData').html(data);
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
	})
}
function openNewTeamEventForm(g,t,i,tpid){
	$.ajax({
		url: './ajax_talentpeak.php?openNewTeamEventForm=1',
		type: 'get',
		data: {groupid:g,teamid:t,eventid:i,touchpointid:tpid},
		success: function(data) {
			$('#loadAnyModal').html(data);
			$('#teamEventModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function addOrUpdateTeamEvent(g,t,e){
	let eventtitle= $("#eventtitle").val();
	let start_date= $("#start_date").val();
	let event_description= $("#redactor_content").val();

	if (!eventtitle || !start_date || !event_description){
		swal.fire({title: '<?= gettext("Error")?>',text:'<?= gettext("All fields are required.")?>'});
		setTimeout(() => {
			$(".swal2-confirm").focus();   
		}, 500)
		$('.modal').css('overflow-y', 'auto');
		$('body').addClass('modal-open');
	} else {
		let formdata = $('#teamEventModalForm')[0];
		let finaldata= new FormData(formdata);
		finaldata.append("groupid",g);
		finaldata.append("teamid",t);
		finaldata.append("eventid",e);
		$.ajax({
			url: 'ajax_talentpeak.php?addOrUpdateTeamEvent=1',
			type: "POST",
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success : function(data) {				
				try {
					let jsonData = JSON.parse(data);
					swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
						if (jsonData.status==1){
							$('#teamEventModal').modal('hide');
							$('body').removeClass('modal-open');
							$('.modal-backdrop').remove();
							$("#team_touch_points").trigger('click');	
							setTimeout(() => {
								$('.touch-point').focus();  
							}, 500);
						}
						$('.prevent-multi-clicks').focus(); 
					});					
					$(".swal2-confirm").focus();
				} catch(e) {
					swal.fire({title: '<?= gettext("Error")?>', text: "<?= gettext('Unknown error.')?>"});
				}
								 
			}
		});
	}
}

function getTeamEventActionButton(e){
	$.ajax({
		url: 'ajax_talentpeak.php?getTeamEventActionButton=1',
        type: "GET",
		data: {'eventid':e},
        success : function(data) {
			$('#dynamicActionButton'+e).html(data);
			$(".confirm").popConfirm({content: ''});
		}
	});
}

async function deleteTeamEvent(i,g,t,userview,ispublished){
	let cancellationReason;
	if (ispublished) {
		 const { value: retval } = await Swal.fire({
			title: 'Event cancellation reason',
			input: 'textarea',
			inputPlaceholder: 'Enter event cancellation reason',
			inputAttributes: {
				'aria-label': 'Enter event cancellation reason',
				maxlength: 200
			},
			showCancelButton: true,
			confirmButtonText: 'Delete Event',
			allowOutsideClick: () => false,
			inputValidator: (value) => {
			 return new Promise((resolve) => {
				 if (value) {
					 resolve()
				 } else {
					 resolve('Please enter event cancellation reason')
				 }
			 })
			}
		});
		cancellationReason = retval;
	}

	if (!ispublished || cancellationReason) {
		$.ajax({
			url: 'ajax_talentpeak.php?deleteTeamEvent=1',
			type: "POST",
			data: {'eventid': i, 'event_cancel_reason': cancellationReason},
			success: function (data) {
				try {
					let jsonData = JSON.parse(data);
					swal.fire({title: jsonData.title, text: jsonData.message}).then(function (result) {
						if (userview) {
							goto_homepage();
						} else {
							$("#team_touch_points").trigger('click');
						}
					});
				} catch (e) {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
				}
			}
		});
	}
}
function volunteerSwitchChange(eid){
	let volunteerCheckbox = document.getElementById('volunteerSwitch');
	let volunteerManage = document.getElementById('manageVolunteerTab');
    let volunteerSwitchBlock = document.getElementById('volunteerSwitchBlock');
	if(volunteerCheckbox.checked){
		volunteerManage.style.display="block";
        //volunteerSwitchBlock.style.display="none";
	}else{
		// hide
		$.ajax({
			url: 'ajax_events.php?canDisableEventVolunteerAssociation=1',
			type: "GET",
			data: {'eventid': eid},
			success: function (data) {
				try {
					let jsonData = JSON.parse(data);
					if (jsonData.status == '1') {
						volunteerManage.style.display="none";
					} else {
						volunteerCheckbox.checked = true;
						swal.fire({title: jsonData.title, text: jsonData.message})
					}
				} catch (e) {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
				}
			}
		});
		
	}
}
function speakerSwitchChange(eid){
	let speakerCheckbox = document.getElementById('speakerSwitch');
	let speakerManage = document.getElementById('manageSpeakerTab');
    let speakerSwitchBlock = document.getElementById('speakerSwitchBlock');
	if(speakerCheckbox.checked){
		speakerManage.style.display="block";
        //speakerSwitchBlock.style.display="none";
	}else{
		// hide
		$.ajax({
			url: 'ajax_events.php?canDisableEventSpeakersAssociation=1',
			type: "GET",
			data: {'eventid': eid},
			success: function (data) {
				try {
					let jsonData = JSON.parse(data);
					if (jsonData.status == '1') {
						speakerManage.style.display="none";
					} else {
						speakerCheckbox.checked = true;
						swal.fire({title: jsonData.title, text: jsonData.message})
					}
				} catch (e) {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
				}
			}
		});
	}
}
function budgetSwitchChange(eid){
	let budgetCheckbox = document.getElementById('budgetSwitch');
	let budgetManage = document.getElementById('manageBudgetTab');
    let budgetSwitchBlock = document.getElementById('budgetSwitchBlock');
	if(budgetCheckbox.checked){
		budgetManage.style.display="block";
        //budgetSwitchBlock.style.display="none";
	} else {
		// hide
		$.ajax({
			url: 'ajax_events.php?canDisableEventBudgetModuleAssociation=1',
			type: "GET",
			data: {'eventid': eid},
			success: function (data) {
				try {
					let jsonData = JSON.parse(data);
					if (jsonData.status == '1') {
						budgetManage.style.display="none";
					} else {
						budgetCheckbox.checked = true;
						swal.fire({title: jsonData.title, text: jsonData.message})
					}
				} catch (e) {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
				}
			}
		});
	}
}
function orgSwitchChange(eid){
	let orgCheckbox = document.getElementById('orgSwitch');
	let orgManage = document.getElementById('manageOrgTab');
    let orgSwitchBlock = document.getElementById('orgSwitchBlock');
	if(orgCheckbox.checked){
		orgManage.style.display="block";
        //orgSwitchBlock.style.display="none";
	}else{


		$.ajax({
			url: 'ajax_events.php?canDisablePartnerOrgAssociation=1',
			type: "GET",
			data: {'eventid': eid},
			success: function (data) {
				try {
					let jsonData = JSON.parse(data);
					if (jsonData.status == '1') {
						orgManage.style.display="none";
					} else {
						orgCheckbox.checked = true;
						swal.fire({title: jsonData.title, text: jsonData.message})
					}
				} catch (e) {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
				}
			}
		});
	}
}
function manageOrganizations(e){
	$.ajax({
		url: 'ajax_events.php?manageOrganizations=1',
        type: "GET",
		data: {'eventid':e},
        success : function(data) {
			$('#loadAnyModal').html(data);
			$('#manageOrganizationModal').modal({
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
			$(".confirm").popConfirm({content: ''});
		}
	});
}
function addUpdateEventOrgModal(e){
	$.ajax({
		url: 'ajax_events.php?showOrgDropdown=1',
		type: 'get',
		data: {eventid:e},
		success: function(data) {
			$("#selectOrgDivOptions").html(data);
			try {
				let items = JSON.parse(data);
				$.each(items, function (i, item) {
					$('#selected_org').append($('<option>', {
						value: item.v,
						text: item.t
					}));
				});

				$("#org_card").hide(1000, function () {
					$("#selectOrgDiv").show();	
					document.querySelector("#selected_org").focus(); 				
				});
				
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}
function openAddOrUpdateOrgModal(e,s,c){
	$.ajax({
		url: 'ajax_events.php?openAddOrUpdateOrgModal=1',
		type: 'get',
		data: {eventid:e,organizationid:s,clone:c},
		success: function(data) {
			$('#manageOrganizationModal').modal('hide');
			$('body').removeClass('modal-open');
			$('.modal-backdrop').remove();

			$("#loadAnyModal").html(data);
			$('#orgModalForm').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}
function addOrUpdateOrg(e){

	var inputs = document.querySelectorAll('#orgForm [data-required="true"]:not(.exclude)');
	var emptyFields = [];
	var invalidEmails = [];
	var emailsRegex = /[\w\.]+@[\w\.\-]+$/;
	var isValid = true;	

	inputs.forEach(function(input){
		if(input.value.trim() === ''){
			var inputLabel = document.querySelector(`label[for="${input.id}"]`);
			var cleanInputLabel = inputLabel ? inputLabel.innerText.trim().replace(/[:\*]/g, '') : input.id.trim();
			if(input.tagName == 'SELECT'){
				if(input.value.trim() == ''){
					isValid = false;
					emptyFields.push(cleanInputLabel);
				}
			} else if (input.type == 'checkbox'){
				const customCheckboxGroup = element.name;
				const customCheckboxes = document.querySelectorAll(`input[name="${customCheckboxGroup}"]`);
				const isCustomCheckboxChecked = Array.from(customCheckboxes).some(cb => cb.checked);
				if(!isCustomCheckboxChecked){
					isValid = false;
					emptyFields.push(cleanInputLabel);
				}
			} else if (input.type == 'email'){
				if(!emailsRegex.test(input.value)){
					isValid = false;
					invalidEmails.push(cleanInputLabel)	
				}
			}else{
				if(input.value.trim() == ''){
					isValid = false;
					emptyFields.push(cleanInputLabel);
				}
			}
			// to avoid any duplicacy
			emptyFields = emptyFields.filter((label, index, self) => self.indexOf(label) == index)
		}
	});

	// check if any empty.
	if(emptyFields.length > 0){
		swal.fire({
			title: 'Error!',
			text: emptyFields.join(', ') + ' cannot be empty',
			allowOutsideClick:false
		});
	}else if(invalidEmails.length > 0){
		swal.fire({
			title: 'Error!',
			text: invalidEmails.join(', ') + ' does not have a valid email',
			allowOutsideClick:false
		});
	}else{
		// ajax 
		let formdata = $('#orgForm')[0];
		let finaldata  = new FormData(formdata);
		finaldata.append("eventid",e);
		$.ajax({
			url: 'ajax_events.php?addOrUpdateOrg=1',
			type: 'POST',
			enctype: 'multipart/form-data',
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success: function(data) {
				try {
					let jsonData = JSON.parse(data);
					swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
						if (jsonData.status>0) {
							$('#orgModalForm').modal('hide');
							$('body').removeClass('modal-open');
							$('.modal-backdrop').remove();
							manageOrganizations(e);
						}
					});
				} catch(e) {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
				}
			}
		});
	}
}
function deleteOrgFromEvent(orgId, eventId){
	$('body').css('cursor', 'wait');
	$.ajax({
		url: 'ajax_events.php?deleteOrgFromEvent=1',
        type: "GET",
		data:{'eventid':eventId,'organization_id':orgId},
		success : function(data) {	
			$('body').css('cursor', 'auto');
			$('.modal-backdrop').remove();
			manageOrganizations(eventId);
		}
	});
}
function manageVolunteers(e,r){
	$('.popover').popover('hide');	
	let refreshPage = (typeof r !== 'undefined') ? r : 1;
	// $('#manageEventVolunteerRequestsModal').modal('hide');
	$('body').removeClass('modal-open');
	$('.modal-backdrop').remove();
	$.ajax({
		url: 'ajax_events.php?manageVolunteers=1',
        type: "GET",
		data: {'eventid':e,'refreshPage':refreshPage},
        success : function(data) {
			$('#loadAnyModal').html(data);
			$('#manageVolunteersModal').modal({
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
			$(".confirm").popConfirm({content: ''});
		}
	});
}
function addUpdateEventVolunteerModal(e,i,v){
	$('#manageVolunteersModal').modal('hide');
	$('body').removeClass('modal-open');
	$('.modal-backdrop').remove();
	$.ajax({
		url: 'ajax_events.php?addUpdateEventVolunteerModal=1',
        type: "GET",
		data:{'eventid':e,'userid':i,'volunteertypeid':v},
		success : function(data) {	
			$('#loadAnyModal').html(data);
			$('#new_volunteer_form_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}
function searchUsersForEventVolunteer(value){
	delayAjax(function(){
		if(value.length >= 3){
			$.ajax({
				type: "GET",
				url: "ajax_events.php?searchUsersForEventVolunteer=1",
				data: {
					'search_keyword_user' : value
				},
				success: function(response){
					if (response.includes('Internal Server Error')){
						showSweetAlert("","Internal server error. Please try later.",'error');
					} else {
						$("#show_dropdown").html(response);
						let myDropDown=$("#user_search");
						let length = $('#user_search> option').length;
						myDropDown.attr('size',length);
					}
				}
			});
		}
	}, 500 );
}

function addOrUpdateEventVolunteer(e){
	$(document).off('focusin.modal');
	let userid = $("#user_search").val();
	let userid2 = $("#user_search2").val();
	let volunteertypeid = $("#volunteertypeid").val();
	if (userid2 == '') {
		swal.fire({title: '<?=gettext("Error");?>',text:"<?= gettext('Please select a user');?>",allowOutsideClick:false});
	} else if (userid == '') {
		swal.fire({title: '<?=gettext("Error");?>',text:"<?= gettext('Please select a user');?>",allowOutsideClick:false});
	} else if (volunteertypeid == '') {
		swal.fire({title: '<?=gettext("Error");?>',text:"<?= gettext('Please select a volunteer type');?>",allowOutsideClick:false});
	} else {
		let formdata =	$('#event_volunteer_form').serialize();
		$.ajax({
			url: 'ajax_events.php?addOrUpdateEventVolunteer=1',
			type: "POST",
			data: formdata+'&eventid='+e,
			success : function(data) {	
				try {
					let jsonData = JSON.parse(data);
					if (jsonData.status == 1) {						
						$('#lead_form_modal').modal('hide');
						$('#new_volunteer_form_modal').modal('hide')
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						swal.fire({title:jsonData.title,text:jsonData.message,focusConfirm:true,allowOutsideClick:false}).then(function (result) {
							manageVolunteers(e,0);							
						});						
					} else {
						swal.fire({title:jsonData.title,text:jsonData.message,focusConfirm:true,allowOutsideClick:false});
					}
				} catch(e) {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",focusConfirm:true,allowOutsideClick:false});
				}				
			}
		});
	}	

}

function deleteEventVolunteer(e,i,volunteerid){
	$('body').css('cursor', 'wait');
	$.ajax({
		url: 'ajax_events.php?deleteEventVolunteer=1',
        type: "GET",
		data: {
			eventid: e,
			userid: i,
			volunteerid
		},
		success : function(data) {	
			$('body').css('cursor', 'auto');
			manageVolunteers(e,0);
		}
	});
}

function addUpdateEventVolunteerRequestModal(e,v){
	$('body').removeClass('modal-open');
	$('.modal-backdrop').remove();
	$.ajax({
		url: 'ajax_events.php?addUpdateEventVolunteerRequestModal=1',
        type: "GET",
		data:{'eventid':e,'volunteertypeid':v},
		success : function(data) {	
			$('#loadAnyModal').html(data);
			$('#new_volunteer_request_form_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function addUpdateEventVolunteerRequest(e){
	$(document).off('focusin.modal');
	let volunteer_needed_count = $("#volunteer_needed_count").val();
	let volunteer_type = $("#volunteer_types").val();
	let cc_email = $("#cc_email").val();
	if (volunteer_needed_count == '') {
		swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Please input volunteer needed count'))?>",allowOutsideClick:false});
	} else if (volunteer_type == '') {
		swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Please select a volunteer type'))?>",allowOutsideClick:false});
	}
	else {
		let formdata =	$('#event_volunteer_request_form').serialize();
		$.ajax({
			url: 'ajax_events.php?addUpdateEventVolunteerRequest=1',
			type: "POST",
			data: formdata+'&eventid='+e,
			success : function(data) {	
				try {
					let jsonData = JSON.parse(data);
					if (jsonData.status == 1) {
						$('#new_volunteer_request_form_modal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function (result) {
							manageVolunteers(e,0);				
						});
						
					} else {
						swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false});
					}
				} catch(e) {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
				}
			}
		});
	}
}
function deleteEventVolunteerRequest(e,i){
	// $('#manageEventVolunteerRequestsModal').modal('hide');
	$('body').removeClass('modal-open');
	$('.modal-backdrop').remove();
	$.ajax({
		url: 'ajax_events.php?deleteEventVolunteerRequest=1',
        type: "GET",
		data:{'eventid':e,'volunteertypeid':i},
		success : function(data) {	
			manageVolunteers(e,0);		
		}
	});
}

function approveEventVolunteer(e,i){
	$.ajax({
		url: 'ajax_events.php?approveEventVolunteer=1',
        type: "GET",
		data:{'eventid':e,'volunteerid':i},
		success : function(data) {	
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message}).then(function (result) {
					$('#manageVolunteersModal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					manageVolunteers(e,0);
				});
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
			}
		}
	});
}

function getMessageScheduleModal(g,m){
	$.ajax({
		url: 'ajax_message.php?getMessageScheduleModal=1',
		type: "GET",
		data: {'groupid': g, 'messageid': m},
		success: function (data) {
			$("#loadAnyModal").html(data);
			$('#general_schedule_publish_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function saveScheduleMessagePublishing(g){
	$(document).off('focusin.modal');
	let formdata =	$('#schedulePublishForm').serialize();
	$.ajax({
		url: 'ajax_message.php?saveScheduleMessagePublishing='+g,
		type: "POST",
		data: formdata,
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function (result) {
					if (jsonData.status == 1){
						$('#general_email_review_modal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						$('#loadAnyModal').html('');
						groupMessageList(g,jsonData.val);
					}
				});

			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}

function cancelMessagePublishing(g,m){
	$.ajax({
		url: 'ajax_message.php?cancelMessagePublishing=1',
		type: "POST",
		data: {groupid:g,messageid:m},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message}).then(function (result) {
					groupMessageList(g,jsonData.val);
				});
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
			}
		}
	});
}


function getRecognitions(gid,t,r){
	let reload = (typeof r !== 'undefined') ? 1 : 0;
	window.location.hash = "recognition";
	localStorage.setItem("manage_active", "manageRecognitions");
	$.ajax({
		url: 'ajax_recognition.php?getRecognitions=1',
        type: "GET",
		data: {'groupid':gid,'recognition_type':t,'reload':reload},
        success : function(data) {
			if (reload){
				$('#ajax').html(data);
			} else {
				$('#loadeMoreRecognitionRows').html(data);
				$("#pageNumber").val(2);
			}
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
			$('.recognition-listing-tab').attr('aria-selected', 'false');
			$('#'+t).attr('aria-selected', 'true');
		}
	} );
}
function filterRecognitions(g){
	$.ajax({
		url: 'ajax_recognition.php?filterRecognitions=1',
		type: "GET",
		data: {'groupid':g},
		success : function(data) {
			$('#recognitionTableContainer').html(data);
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
	} );
}
function manageRecognitions(g){
	localStorage.setItem("manage_active", "manageRecognitions");
	let state_filter = '';
	if (localStorage.getItem("state_filter") !== null){
		state_filter = localStorage.getItem("state_filter");
	}
	let erg_filter = '';
	if (localStorage.getItem("erg_filter") !== null){
		erg_filter = localStorage.getItem("erg_filter");
	}
	let erg_filter_section = '';
	if (localStorage.getItem("erg_filter_section") !== null){
		erg_filter_section = localStorage.getItem("erg_filter_section");
	}
	let year_filter = '';
	if (localStorage.getItem("year_filter") !== null){
		year_filter = localStorage.getItem("year_filter");
	}
	$('.action-menu').hide();
	g = (typeof g !== 'undefined') ? g : '';
	$.ajax({
		url: 'ajax_recognition.php?manageRecognitions=1',
        type: "GET",
		data: 'groupid='+g+'&state_filter='+state_filter+'&erg_filter='+erg_filter+'&year_filter='+year_filter+'&erg_filter_section='+erg_filter_section,
        success : function(data) {
			$('#ajax').html(data);
		}
	});
}


function openNewRecognitioinModal(g,i,t){
	$.ajax({
		url: 'ajax_recognition.php?openNewRecognitioinModal=1',
		type: "GET",
		data: {'groupid':g,'recognitionid':i,'recognition_type':t},
		success : function(data) {
			$('#loadAnyModal').html(data);
			$('#loadNewRecognitioinModal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$( "#start_date" ).datepicker({
				prevText:"click for previous months",
				nextText:"click for next months",
				showOtherMonths:true,
				selectOtherMonths: false,
				dateFormat: 'yy-mm-dd' 
			});
		}
  	});
}
function configureRecognitionCustomFields(g){
	$.ajax({
		url: 'ajax_recognition.php?configureRecognitionCustomFields=1',
		type: "GET",
		data: {'groupid':g},
		success : function(data) {
			$('#loadAnyModal').html(data);
			$('#configurationRecognitioinModal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$(".confirm").popConfirm({content: ''});
		}
  	});
}

function updateRecognitionCustomFields(g,i,a){
	$.ajax({
		url: 'ajax_recognition.php?updateRecognitionCustomFields=1',
		type: "POST",
		data: {'groupid':g,'custom_field_id':i,'action':a},
		success : function(data) {
			$("#td_"+i).html(data);
			$(".confirm").popConfirm({content: ''});		
		}
  	});

	  $("#btn_close").focus();
}

function getRecognitionActionButton(r,evtl,evtform=0){
	var evt = $(evtl).next('#dynamicActionButton' + r);
	$.ajax({
		url: 'ajax_recognition.php?getRecognitionActionButton=1&checkform='+evtform,
        type: "GET",
		data: {'recognitionid':r},
        success : function(data) {
			setTimeout(() => {
				evt.html(data);
				$(".confirm").popConfirm({content: ''});
			}, 100);
			
		}
	});
}

function deleteRecognition(r){
	$(document).off('focusin.modal');
	$.ajax({
		url: 'ajax_recognition.php?deleteRecognition=1',
        type: "POST",
		data: {'recognitionid':r},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false}).then(function (result) {
					if (jsonData.status == 1){
						window.location.reload();
					}
				});
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}

function editRecognition(g,i,t,cform=0){
	$('#viewRecognitionDetialModal').modal("hide");
	$('.modal-backdrop').remove();
	$.ajax({
		url: 'ajax_recognition.php?openRecognitioinModalForUpdate=1&checkform='+cform,
		type: "GET",
		data: {'groupid':g,'recognitionid':i,'recognition_type':t},
		success : function(data) {
			$('#loadAnyModal').html(data);
			$('#loadNewRecognitioinModal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$( "#start_date" ).datepicker({
				prevText:"click for previous months",
				nextText:"click for next months",
				showOtherMonths:true,
				selectOtherMonths: false,
				dateFormat: 'yy-mm-dd' 
			});
		}
  	});
}



function loadMoreRecognition(g,t) {
	window.location.hash = "recognition";
	let page = $("#pageNumber").val();
	$.ajax({
		url: 'ajax_recognition.php?loadMoreRecognition=1',
		type: "GET",
		data: {'groupid':g ,'recognition_type':t,'page':page},
		success: function (data) {
			if (data == 1) {
				$('#loadeMoreRecognitionAction').hide();
			} else {
				$("#pageNumber").val((parseInt(page) + 1));

				const regex = /<span.*>_l_=(.*)<\/span>$/gm;
				let m;
				if ((m = regex.exec(data)) === null) {
					$('#loadeMoreRecognitionAction').hide();
				}
				$('#loadeMoreRecognitionRows').append(data);
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

function viewRecognitionDetial(r){
	$.ajax({
		url: 'ajax_recognition.php?viewRecognitionDetial=1',
        type: "GET",
		data: {'recognitionid':r},
        success : function(data) {
			$('#loadAnyModal').html(data);
			$('#viewRecognitionDetialModal').modal({
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
			setTimeout(() => {
				$('#close_btn_one').focus();
			}, 500);
		}
	});

}

function manageLeadResources(gid){
	localStorage.setItem("manage_active", "manageLeadResources");
	localStorage.setItem("is_resource_lead_content", 1);
	$.ajax({
		url: 'ajax_resources.php?manageLeadResources=1',
        type: "GET",
		data: {groupid:gid},
        success : function(data) {
			$('#ajax').html(data);
			jQuery(".deluser").popConfirm({content: ''});
		}
	});
}

function updateTeamBulkAction(g){
	let a = $("#bulk_action").val();
	$("#bulk_action").val('');
	$("#confirmChange").val('');
	$('#confirmChangeModal').modal('hide');
	$('body').removeClass('modal-open');
	$('.modal-backdrop').remove();
	if (a!=''){
		$.ajax({
			url: 'ajax_talentpeak.php?updateTeamBulkAction=1',
			type: "POST",
			data: {'groupid':g,'action':a},
			success : function(data) {
				try {
					let jsonData = JSON.parse(data);
					if (jsonData.status == 1){
						const teams = jsonData.val;
						const noOfUpdate = teams.length;
						let itemCurrentlyProcessing = 1;
						let updateDone = '';
						let updateFailed = '';
						$("body").css("cursor", "progress");
						$("#updateDone").html('');
						$("#updateFailed").html('');
						$("#totalBulkRecored").html(noOfUpdate);
						$(".progress_status").html("Processing 0/" + noOfUpdate + " bulk update");
						$('div#prgress_bar').width('0%');
						$('#progress_bar').show();
						if (noOfUpdate){
							let p = Math.round((1 / noOfUpdate) * 100);
							$(".progress_status").html("Processing " + 1 + "/" + noOfUpdate+" bulk update");
							$('div#prgress_bar').width(p + '%');
						}
						teams.forEach(function (item, index) {
							 $.ajax({
								url: 'ajax_talentpeak.php?processTeamBulkAction=1' + '&idx='+index,
								type: "POST",
								global: false, /* stop global error handling, add csrf in parameters */
								data: {'groupid':g,'teamid':item.teamid,'action':a, 'csrf_token':teleskopeCsrfToken},
								success: function (data) {
									try {
										let jsonData = JSON.parse(data);

										if (jsonData.status == 1){
											updateDone += jsonData.val + ', ';
										} else {
											updateFailed += jsonData.val + ', ';
										}
									} catch(e) {
										updateFailed += item.team_name + ', ';
									}
								},
								error: function(e) {
									let errorCode = e.status ? (' (HTTP error ' + e.status + ')') : '';
									updateFailed += item.team_name + errorCode + ', ';
								}
							}).always( function fn () {
								 itemCurrentlyProcessing++;
								 $("#updateDone").html(updateDone.replace(/,\s*$/, ""));
								 if (updateFailed) {
									 $("#updateFailed").html(updateFailed.replace(/,\s*$/, ""));
								 }
								 if (itemCurrentlyProcessing > noOfUpdate) {
									 setTimeout(function() {
										 $("body").css("cursor", "default");
										 $('div#prgress_bar').width('100%');
										 $(".progress_status").html("<i class='fa fa-check-circle' aria-hidden='true'></i> Completed");
										 $("#close_show_progress_btn").show();
									 }, 800);
								 } else {
									 let p = Math.round((itemCurrentlyProcessing / noOfUpdate) * 100);
									 $(".progress_status").html("Processing " + itemCurrentlyProcessing + "/" + noOfUpdate + " bulk update");
									 $('div#prgress_bar').width(p + '%');
								 }
								 $(".progress_done").show();
							 });
						});
					} else {
						swal.fire({title: jsonData.title, text: jsonData.message});
					}
				} catch(e) {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"});
				}
			}
		});

	}
}

function getAnnouncementDetailOnModal(p,c,ch){
	this.closeAllActiveModal();
	$.ajax({
		url: 'ajax_announcement.php?getAnnouncementDetailOnModal=1',
        type: "GET",
		data: {'postid':p,'chapterid':c,'channelid':ch},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$('#loadAnyModal').html(data);
				$('#post_detail_modal').modal({
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

function getEventDetailModal(eid,cid,chid){
	this.closeAllActiveModal();
	$.ajax({
		url: 'ajax_events.php?getEventDetailModal=1',
        type: "GET",
		data: {'eventid':eid,'chapterid':cid,'channelid':chid},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$('#loadAnyModal').html(data);
				$('#event_detail_modal').modal({
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

function getDiscussionDetailOnModal(d,c,ch, unsetId=0){
	this.closeAllActiveModal();
	$.ajax({
		url: 'ajax_discussions.php?getDiscussionDetailOnModal=1',
        type: "GET",
		data: {'discussionid':d,'chapterid':c,'channelid':ch, 'unsetId':unsetId},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$('#loadAnyModal').html(data);
				$('#discussion_detail_modal').modal({
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
			}
		}
	});
}


function getGroupChaptersToInvite(g,e){
	$.ajax({
		url: 'ajax_events.php?getGroupChaptersToInvite=1',
        type: "GET",
		data:{'groupid':g,'eventid':e},
		success : function(data) {	
			try {
				$('#invitation-submit-button').prop('disabled', true);
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message});
				$("#group_id_in").html('<option value="0">'+jsonData.message+'</option>');
			} catch(e) {
				$("#group_id_in").html(data);
				$('#invitation-submit-button').prop('disabled', false);
			}
		}
	});
}

<?php require __DIR__ . '/../../include/common/js/common.js.php'; ?>

function albumMediaGalleryView(albumid,ch,chnl){
	$('body').removeClass('modal-open');
	$('.modal-backdrop').remove();
	$.ajax({
		url: 'ajax_albums.php?albumMediaGalleryView=1',
        type: "GET",
		data:{'albumid':albumid,'chapterid':ch,'channelid':chnl},
		success : function(data) {	
			$(".gallery-card-title").css({'position':'relative','z-index':'1'});
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message});
			} catch(e) {
				$('#ajax').html(data);
			}
		}
	});
}

function updateTeamJoinRequestSurveyResponseByManagerModal(g,u,t){
	$.ajax({
		url: './ajax_talentpeak.php?updateTeamJoinRequestSurveyResponseByManagerModal=true',
		type: 'GET',
		data: {groupid:g,foruid:u,roleid:t},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message});
			} catch(e) { 
				$('#loadAnyModal').html(data);
				$('#updateSurveyResponses').modal({
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



function showhideForeignCurrencyInput(e){
	if($(e).is(":checked")) {
		let dateInput = $("#dateInput").val();
		if (dateInput == ''){
			swal.fire({title: "Success", text: "Please select expense date to show configured foreign currencies for the applicable budget year"})
				.then(
					function () {
						$(e).prop( "checked", false);
					});
			return;
		}
		$.ajax({
			url: 'ajax_budget.php?showhideForeignCurrencyInput=1',
			type: "GET",
			data:{'expenseDate':dateInput},
			success : function(data) {	
				try {
					let jsonData = JSON.parse(data);
					swal.fire({title: jsonData.title,text:jsonData.message});
					$(e).prop( "checked", false);
				} catch(a) {
					$(e).parent().next('.foregin-currency-input').html(data);
					$(e).parent().next('.foregin-currency-input').show();
					$(e).next('input').val(1)
				}
			}
		});
	} else {
		$(e).parent().next('.foregin-currency-input').hide();
		$(e).next('input').val(0)
	}
}

function calculateHomeCurrencyAmount(e){
	let foreign_amount = $(e).val();
	let home_amount =  $(e).parent().parent().parent().next('input').next('div').next('div').next('div').next('div').find('input');
	let conversion_rate  = $(e).parent().prev("div").find('input').val();
	let conversion_rate_formatted = Number(conversion_rate.replace(/,/g, ''));
	let converted_amount = Math.round(((conversion_rate_formatted*foreign_amount) + Number.EPSILON) * 100) / 100;
	home_amount.val(converted_amount);
}

function prePopulateConversionRate(e){
	$.ajax({
		url: 'ajax_budget.php?prePopulateConversionRate=1',
		type: "GET",
		data:{'currencyCode':e.value},
		success : function(data) {
			$(e).children().first().attr("disabled", "true");
			$(e).parent().next("div").find('input').val(data);
			$(e).parent().next("div").find('input').prop('readonly', true);
			let default_amount =  $(e).parent().parent().parent().prev('div').prev('div').find('input').val();
			if (default_amount == ''){
				default_amount = 0;
			}
			let dataValue = Number(data.replace(/,/g, ''));
			let converted_amount = Math.round(((default_amount/dataValue) + Number.EPSILON) * 100) / 100;
			$(e).parent().next("div").next("div").find('input').val(converted_amount);
			
		}
	});
}


function allowConversionRateToChange(e){
	$(e).next("input").prop('readonly', false);
	$(e).next("input").focus();
}


function updateForeignCurrencyAmountOnRateChange(e){
	let fmt = $(e).parent().next('div').find('input');
	if (fmt.val()){
		calculateHomeCurrencyAmount(fmt);
	}
}

function searchUsersForEventCheckin(value){
	delayAjax(function(){
		if(value.length >= 3){
			$.ajax({
				type: "GET",
				url: "ajax_events.php",
				data: {
					'searchUsersForEventCheckin' : value
				},
				success: function(response){
					if (response.includes('Internal Server Error')){
						showSweetAlert("","Internal server error. Please try later.",'error');
					} else {
						$("#show_dropdown").html(response);
						let myDropDown=$("#user_search");
						let length = $('#user_search> option').length;
						myDropDown.attr('size',length);
					}
				}
			});
		}
	}, 500 );
}

function filterHomeFeedsByContentTypes(selectedButtonId, f, status) {
	let page = 1;
	let groupTags = $("#group_tags").val();
    const buttonToFilterMap = {
        "upcomingEventsButton": ['upcomingEvents'],
        "showEverythingButton": ['event', 'post', 'newsletter', 'discussion', 'album'],
        "postButton": ['post'],
        "newsletterButton": ['newsletter'],
        "eventButton": ['event'],
		"discussionsButton": ['discussion'],
		"albumsButton": ['album'],
    };
    let contentFilter = buttonToFilterMap[selectedButtonId] || [];
	$('body').css("pointer-events", "none");
	localStorage.setItem("home_feeds_count", 0); // Reset
	$("#showNoDataByContentFilter").hide();
	$.ajax({
		url: 'ajax.php?filterHomeFeedsByContentTypes=1',
		type: "GET",
		data: {'filter':f,'page':page,'groupTags':groupTags,'contentFilter':contentFilter},
		success: function (data) {
			if (data === '0') {
				swal.fire({
				  title: '<?=gettext("Error");?>',
				  text: '<?= addslashes(gettext("An error occurred while fetching data. Please try again later."))?>',
				  icon: 'error'
				});
			  } else {				
					$("#feedPageNumber").val((parseInt(page) + 1));
					$('#loadeMoreFeedsAction').show();
					const regex = /<span.*>_l_=(.*)<\/span>$/gm;
					let m;
					if ((m = regex.exec(data)) === null) {
						$('#loadeMoreFeedsAction').hide();
						$("#hidden_div_for_notification").hide();
					}
					$('#feed_rows').html(data);
					
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
					

					setTimeout(() => { // To prevent fast clicking, set delay for let data to be rendered first
						$('body').css("pointer-events", "auto");					

						if(status != 1){ // sattus is used to handle the focus for first feeds on home page.
							if (typeof $('.text-asd a.active')[0] !== 'undefined') {
								$('.text-asd a.active')[0].focus();
							}
						}

						
					}, 200);

					if ($('.home-announcement-block').length==0){
						$("#showNoDataByContentFilter").show();						
					} 										
					
					setTimeout(() => {
					// For screen reading purpose add aria live attribute to load more button on home screen //
						$("#hidden_div_for_notification").css('display', 'block');
						$('#hidden_div_for_notification').attr('aria-live', 'polite');
						$('#hidden_div_for_notification').attr('aria-atomic', 'true');
						$('#hidden_div_for_notification').attr('role', 'status');
						var numItems = $('#feed_rows').find('.home-announcement-block').length;					
						$('#hidden_div_for_notification').html(+numItems+'<p> '+contentFilter+' found - Load More</p>');
					}, 1000);
			  }
		},
		error: function() {
			swal.fire({
			  title: '<?=gettext("Error");?>',
			  text: '<?= addslashes(gettext("An error occurred while fetching data. Please try again later."))?>',
			  icon: 'error'
			});
		  }
	});

	
	
}
function handleHomepageFilterButtonClick(buttonId, filter, status) {
	$(".toggle-btn").removeClass("toggle-btn-active");
	$(".toggle-btn").removeAttr("aria-current");
	$(".toggle-btn").attr('aria-selected', 'false');
	$(".btn-group .toggle-btn-dropdown").removeAttr("aria-selected");
	$(".dropdown-menu .toggle-btn-item").removeAttr("aria-selected");
	let btnObj = $("#" + buttonId);
	btnObj.addClass("toggle-btn-active");
	if (btnObj.hasClass("toggle-btn-active")) {
		var attr = document.createAttribute('aria-selected');
		attr.value="true";
		document.getElementById(buttonId).setAttributeNode(attr);
		$(".dropdown-menu .toggle-btn-active").removeAttr("aria-selected");
	}
	if (btnObj.hasClass("toggle-btn-item")) {
		$(".toggle-btn-dropdown").addClass("toggle-btn-active");		
		$(".toggle-btn-active").attr('aria-current', 'true');		
	}
	localStorage.setItem("selectedButton", buttonId); // Store the selected button in local storage
	filterHomeFeedsByContentTypes(buttonId, filter, status);	

	
}

function setAriaLabelForTablePagination(){	
	var paginationLinks = $(".paging_full_numbers > button");
	paginationLinks.each(function() {	
	  var pNo = $(this).html();	
		if (!isNaN(pNo)) {
			$(this).attr('aria-label', 'Page ' + pNo);
		}else{	
			$(this).attr("aria-label",pNo+" Page");
		}																
	});
}

function activateLastSelectedButton(filter) {
	const lastSelectedButton = localStorage.getItem("selectedButton");
	const status = 1; // sattus is used to handle the focus for first feeds on home
	if (lastSelectedButton) {
		handleHomepageFilterButtonClick(lastSelectedButton, filter, status);
	}else{
		handleHomepageFilterButtonClick('showEverythingButton', filter, status);
	}
}
function updateEventExternalFacing(e,s){
	
	$.ajax({
		type: "POST",
		url: "ajax_events.php?updateEventExternalFacing=1",
		data: {
			'eventid' : e,'status':s
		},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false}).then(function (result) {
					if (jsonData.status == 1){
						window.location.reload();
					}
				});
			} catch(e) {
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}

function manageEventSurvey(id){
	$.ajax({
		type: "GET",
		url: "ajax_events.php?manageEventSurvey=1",
		data: {
			'eventid' : id
		},
		success: function(data){
			$('#loadAnyModal').html(data);
			$('#manage_event_survey_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$(".confirm").popConfirm({content: ''});
		}
	});
}

function previewEventSurvey(id,t){
	closeAllActiveModal();
	$.ajax({
		type: "GET",
		url: "ajax_events.php?previewEventSurvey=1",
		data: {
			'eventid' : id,
			'trigger' : t
		},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false});
			} catch(e) {
				$('#loadAnyModal').html(data);
				$('#surveyPreview').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});
}

function activateEventSurvey(id,t){
	$.ajax({
		type: "POST",
		url: "ajax_events.php?activateEventSurvey=1",
		data: {
			'eventid' : id,
			'trigger' : t
		},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false}).then(function (result) {
					manageEventSurvey(id);					
				});
				
			} catch(e) { }
		}
	});
	setTimeout(() => {			
		$(".swal2-confirm").focus();
	}, 100)
}

function deActivateEventSurvey(id,t){
	$.ajax({
		type: "POST",
		url: "ajax_events.php?deActivateEventSurvey=1",
		data: {
			'eventid' : id,
			'trigger' : t
		},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false}).then(function (result) {
					manageEventSurvey(id);
				});
			} catch(e) { }
		}
	});
	setTimeout(() => {		
		$(".swal2-confirm").focus();
	}, 100)
}

function getPreJoinEventSurvey(id,s){
	closeAllActiveModal();
	$.ajax({
		type: "GET",
		url: "ajax_events.php?getPreJoinEventSurvey=1",
		data: {
			'eventid' : id,
			'joinStatus' : s
		},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false});
			} catch(e) {
				$('#loadAnyModal').html(data);
				$('#eventSurveyModal').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});
}

function initUpdateEventSurveyResponses(e,t,r){
	closeAllActiveModal();
	$.ajax({
		type: "GET",
		url: "ajax_events.php?initUpdateEventSurveyResponses=1",
		data: {
			'eventid' : e,
			'survey_trigger' : t,
			'joinStatus': r
		},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false});
			} catch(e) {
				$('#loadAnyModal').html(data);
				$('#eventSurveyModal').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});
}
function getMyTopicApprovalsData(topicType){

	let approvalStatus = '';
	if ($('#approval_status_filter').length) {
		approvalStatus = $('#approval_status_filter').val();
	}
	let requestYear = '';
	if ($('#request_year_filter').length) {
		requestYear = $('#request_year_filter').val();
	}
	$.ajax({
		type: "GET",
		url: "ajax_approvals.php?getMyTopicApprovalsData="+topicType,
		data:{approvalStatus:approvalStatus,requestYear:requestYear},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false});
			} catch(e) {
				$('#dynamic_data_container').html(data);
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
				$(".confirm").popConfirm({content: ''});
			}
		}
	});
}

function viewApprovalDetail(topicTypeId,approvalId,topicType,topicTypeLabel,pageSource){
	$.ajax({
		url: 'ajax_approvals.php?viewApprovalDetail=1',
		type: "get",
		data: {topicTypeId:topicTypeId,approvalid:approvalId,topicType:topicType,pageSource:pageSource},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$("#approvalDetail").html(data);
                $("#topicTypeEnglish").html(topicTypeLabel);
				$('#approvalDetailModal').modal({
					backdrop: 'static',
					keyboard: false
				});
				$('.initial').initial({
					charCount: 2,
					textColor: '#ffffff',
					color: window.tskp?.initial_bgcolor ?? null,
					seed: 0,
					height: 30,
					width: 30,
					fontSize: 15,
					fontWeight: 300,
					fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
					radius: 0
				});
			}
		}
	});
}
function assignUserForApprovalModal(topicTypeId,approvalId,a,topicType){
	closeAllActiveModal();
	$.ajax({
		url: 'ajax_approvals.php?assignUserForApprovalModal='+a,
		type: "GET",
		data: {'topicTypeId':topicTypeId,'approvalid':approvalId, 'topicType':topicType},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$("#loadAnyModal").html(data);
				$('#assignTopicApproverModal').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});
}
function updateApprovalStatusModal(approvalTopicId,approvalid,approverNote, topicType){

	$.ajax({
		url: 'ajax_approvals.php?approvalTasksModal=1',
		type: "GET",
		data: {'approvalTopicId':approvalTopicId,'approvalid':approvalid,'approverNote':approverNote,'topicType':topicType},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$("#loadAnyModal").html(data);
				if($("#approvalTasksModal").length){
					$('#approvalTasksModal').modal({
						backdrop: 'static',
						keyboard: false
					});
				}else if($("#topic_approval_note_modal").length){
					$('#topic_approval_note_modal').modal({
						backdrop: 'static',
						keyboard: false
					});
				}
			}
		}

	});
}
function filterByUserGroupZones(zoneid, eventTypes, newload=0){
	var startDate = $("#filter_by_start_date").val();
	var endDate = $("#filter_by_end_date").val();
	$.ajax({
		type: "POST",
		url: "ajax_my_events.php?filterEventsByZone=1",
		data: {
			'zoneid' : zoneid,
			'eventTypes': eventTypes,
			'startDate': startDate,
			'endDate': endDate,
			'newLoad': newload,
		},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false});
			} catch(e) {
			$('#dynamic_data_container').html(data);
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

function loadMoreMyEventsByZone(zoneid,eventTypes,s) {
	window.location.hash = "events";
	let page = $("#pageNumber").val();
	let lastMonth = $("#lastMonth").val();

	var startDate = $("#filter_by_start_date").val();
	var endDate = $("#filter_by_end_date").val();
	$.ajax({
		url: "ajax_my_events.php?filterEventsByZone=1",
		type: "POST",
		data: {
			'zoneid' : zoneid,
			'eventTypes': eventTypes,
			'startDate': startDate,
			'endDate': endDate,
			'section' : s,
			'page':page,
			'lastMonth':lastMonth
		},
		success: function (data) {
			if (data == 1) {
				$('#loadmore' + s).hide();
			} else {
				$("#pageNumber").val((parseInt(page) + 1));
				const regex = /<span.*>_l_=(.*)<\/span>$/gm;
				let m;
				if ((m = regex.exec(data)) !== null) {
					lastMonth = m[1];
					$("#lastMonth").val(lastMonth);
				} else {
					$('#loadmore' + s).hide();
				}
				$('#loadMoreEvents' + s).append(data);

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

	let lastListItem  = document.querySelectorAll('.event-block');
	let last = (lastListItem[lastListItem.length -1]);
	last.querySelector("a").focus();
}


function getMyEventsDataBySection(s){
	$.ajax({
		type: "GET",
		url: "ajax_my_events.php?getMyEventsDataBySection=1",
		data: {
			'section' : s
		},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false});
			} catch(e) {
				$('#dynamic_data_container').html(data);
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


function loadMoreMyEventsBySection(s) {
	window.location.hash = "events";
	let page = $("#pageNumber").val();
	let lastMonth = $("#lastMonth").val();
	$.ajax({
		url: "ajax_my_events.php?getMyEventsDataBySection=1",
		type: "GET",
		data: {
			'section' : s,
			'page':page,
			'lastMonth':lastMonth
		},
		success: function (data) {
			if (data == 1) {
				$('#loadmore' + s).hide();
			} else {
				$("#pageNumber").val((parseInt(page) + 1));
				const regex = /<span.*>_l_=(.*)<\/span>$/gm;
				let m;
				if ((m = regex.exec(data)) !== null) {
					lastMonth = m[1];
					$("#lastMonth").val(lastMonth);
				} else {
					$('#loadmore' + s).hide();
				}
				$('#loadMoreEvents' + s).append(data);

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

	let lastListItem  = document.querySelectorAll('.event-block');
	let last = (lastListItem[lastListItem.length -1]);
	last.querySelector("a").focus();
}

function getMyEventsSubmissions(){
	closeAllActiveModal();
	searchText = '';
    if($('input[type="search"]').length)
	 searchText = $('input[type="search"]').val();

	let state_filter = '';
	if (localStorage.getItem("state_filter") !== null){
		state_filter = localStorage.getItem("state_filter");
	}
	let year_filter = '';
	if (localStorage.getItem("year_filter") !== null){
		year_filter = localStorage.getItem("year_filter");
	}
	$.ajax({
		url: 'ajax_my_events.php?getMyEventsSubmissions=1',
        type: "GET",
		data: 'state_filter='+state_filter+'&year_filter='+year_filter+'&searchText='+searchText,
        success : function(data) {
			$('#dynamic_data_container').html(data);
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
		}
	});
}

function getMyEventActionButton(e){
	$.ajax({
		url: 'ajax_my_events.php?getMyEventActionButton=1',
        type: "GET",
		data: {'eventid':e},
        success : function(data) {
			$('#dynamicActionButton'+e).html(data);
			$(".confirm").popConfirm({content: ''});
		}
	});
}


function manageTeamsConfiguration(g){
	localStorage.setItem("manage_active", "manageTeamsConfiguration");
	$.ajax({
		url: 'ajax_talentpeak.php?manageTeamsConfiguration='+g,
        type: "GET",
		data: {groupid:g},
		success : function(data) {
			$('#ajax').html(data);
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
		}
	});
}

function manageProgramTeamSetting(g){
	$.ajax({
		url: 'ajax_talentpeak.php?manageProgramTeamSetting=1',
		type: "GET",
		data: {'groupid':g},
		success: function(data) {
			$('#dynamic_content').html(data);
			$(".deluser").popConfirm({content: ''});
		}
	});
}

function manageProgramTeamRoles(g){
	$.ajax({
		url: 'ajax_talentpeak.php?manageProgramTeamRoles=1',
		type: "GET",
		data: {'groupid':g},
		success: function(data) {
			$(".deluser").popConfirm({content: ''});
			$('#dynamic_content').html(data);	
		}
	});
}

function showAddUpdateProgramRoleModal(g,r){
	$.ajax({
		url: 'ajax_talentpeak.php?showAddUpdateProgramRoleModal=1',
		type: "GET",
		data: {'groupid':g,'roleid':r},
		success: function(data) {
			$('#loadAnyModal').html(data);
			$('#addUpdateRoleModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function addUpdateProgramRole(g,r){
	var formdata =	$('#addUpdateTeamRoleForm')[0];
	var finaldata  = new FormData(formdata);
	finaldata.append("groupid",g);
	finaldata.append("roleid",r);
	var submitButton = $(".prevent-multiple-submit");
	submitButton.prop('disabled', 'disabled');
	$.ajax({
		url: 'ajax_talentpeak.php?addUpdateProgramRole=1',
		type: 'POST',
		data: finaldata, // get Data in html page by "managesection" Var
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						$('#addUpdateRoleModal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						$('#AddUpdateProgramRole').focus();
						submitButton.removeAttr('disabled');
					}

				});
				if (jsonData.status == 1){
					if (jsonData.val=='1'){
						manageTeamsConfiguration(g);
						setTimeout(() => {				
							$('#teamroletab').trigger('click');
						}, 600);
					} else {
						manageProgramTeamRoles(g);
					}
					setTimeout(() => {
						$(".swal2-confirm").focus();
					}, 100);

					submitButton.removeAttr('disabled');
				}
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
			submitButton.removeAttr('disabled');
		},
		error: function(error){
			submitButton.removeAttr('disabled');
		}
	});

	
}

function showHideProgramTabSetting(g,value){

	var hidden_tabs = new Array();	
	$.each($("input[name='programTab[]']:checked"), function() {
		hidden_tabs.push($(this).val());		
	});
	if($.inArray("2", hidden_tabs) == -1) { // todo
		localStorage.setItem("team_detail_active_tab", "todo");
	} else if($.inArray("3", hidden_tabs) == -1) { // touchpoint
		localStorage.setItem("team_detail_active_tab", "touchpoint");
	} else if($.inArray("4", hidden_tabs) == -1) { // feedback
		localStorage.setItem("team_detail_active_tab", "feedback");
	}

	$.ajax({
		url: 'ajax_talentpeak.php?showHideProgramTabSetting=1',
		type: "POST",
		data: {'groupid':g,'hidden_tabs':hidden_tabs},
		success: function(data) {
			swal.fire({title: '<?= gettext("Success")?>',text:"<?= addslashes(gettext('Setting updated successfully.'))?>"}).then(function(result) {
				manageTeamsConfiguration(g);			
				setTimeout(() => {
					$('.hide_checkbox_'+value).focus();
				}, 1000);
			});
		}
	});

	setTimeout(() => {		
		$(".swal2-confirm").focus();
	}, 500)
}

function saveTeamInactivityNotificationsSetting(g){
	var notification_days_after = $('#notification_days_after').val();
	var notification_frequency =  $('#notification_frequency').val();
	$.ajax({
		url: 'ajax_talentpeak.php?saveTeamInactivityNotificationsSetting=1',
		type: "POST",
		data: {'groupid':g,'notification_days_after':notification_days_after,'notification_frequency':notification_frequency},
		success: function(data) {
			swal.fire({title: '<?= gettext("Success")?>',text:"<?= gettext('Updated successfully.')?>"});
		}
	});
}

function manageTeamActionItemsTemplates(g){
	$.ajax({
		url: 'ajax_talentpeak.php?manageTeamActionItemsTemplates=1',
		type: "GET",
		data: {'groupid':g},
		success: function(data) {
			$('#dynamic_content').html(data);
			$(".deluser").popConfirm({content: ''});
		}
	});
}

function showAddUpdateProgramActionItemTemplateModal(g,t){
	var taskid = (typeof t !== 'undefined') ? t : false;
	var params = {'groupid':g}
	if (taskid){
		params = {'groupid':g,'taskid':taskid};
	}
	$.ajax({
		url: 'ajax_talentpeak.php?showAddUpdateProgramActionItemTemplateModal=1',
		type: "GET",
		data: params,
		success: function(data) {
			$('#loadAnyModal').html(data);
			$('#addUpdateActionItemModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function addUpdateProgramActionItemTemplate(g,t){
	
	var formdata =	$('#addUpdateActionItemForm')[0];
	var finaldata  = new FormData(formdata);
	finaldata.append("groupid",g);
	finaldata.append("taskid",t);
	var submitButton = $(".prevent-multiple-submit");
	submitButton.prop('disabled', 'disabled');
	$.ajax({
		url: 'ajax_talentpeak.php?addUpdateProgramActionItemTemplate=1',
		type: 'POST',
		data: finaldata, // get Data in html page by "managesection" Var
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						$('#addUpdateActionItemModal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						manageTeamActionItemsTemplates(g);
						$('#showAddUpdateProgram').focus();
						submitButton.removeAttr('disabled');
					}
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }
			submitButton.removeAttr('disabled');
		},
		error: function(error){
			submitButton.removeAttr('disabled');
		}
	});
	setTimeout(() => {		
		$(".swal2-confirm").focus();
	}, 500)
}

function viewTodoOrTouchPointTemplateDetail(g,i,s,r){
	var sortbyTAT = (typeof r !== 'undefined') ? 1 : 0;
	$.ajax({
		url: 'ajax_talentpeak.php?viewTodoOrTouchPointTemplateDetail=1',
		type: "GET",
		data: {'groupid':g,'id':i,'section':s,'sortbyTAT':sortbyTAT},
		success: function(data) {
			$('#loadAnyModal').html(data);
			$('#detailedView').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function manageTeamTouchpointsTemplates(g){
	$.ajax({
		url: 'ajax_talentpeak.php?manageTeamTouchpointsTemplates=1',
		type: "GET",
		data: {'groupid':g},
		success: function(data) {
			$('#dynamic_content').html(data);
			$(".deluser").popConfirm({content: ''});
		}
	});
}

function showAddUpdateProgramTouchPointTemplateModal(g,t){
	var taskid = (typeof t !== 'undefined') ? t : false;
	var params = {'groupid':g}
	if (taskid){
		params = {'groupid':g,'taskid':taskid};
	}
	$.ajax({
		url: 'ajax_talentpeak.php?showAddUpdateProgramTouchPointTemplateModal=1',
		type: "GET",
		data: params,
		success: function(data) {
			$('#loadAnyModal').html(data);
			$('#addUpdateTouchPointModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function addUpdateProgramTouchPointTemplate(g,t){
	var formdata =	$('#addUpdateTouchPointForm')[0];
	var finaldata  = new FormData(formdata);
	finaldata.append("groupid",g);
	finaldata.append("taskid",t);
	var submitButton = $(".prevent-multiple-submit");
	submitButton.prop('disabled', 'disabled');
	$.ajax({
		url: 'ajax_talentpeak.php?addUpdateProgramTouchPointTemplate=1',
		type: 'POST',
		data: finaldata, // get Data in html page by "managesection" Var
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						$('#addUpdateTouchPointModal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						manageTeamTouchpointsTemplates(g);
						$('#add_update_program_touch_point').focus();
						submitButton.removeAttr('disabled');
					}
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }
			submitButton.removeAttr('disabled');
		},
		error: function(data){
			submitButton.removeAttr('disabled');
		}
	});

}

function manageMatchingAlgorithmSetting(g){
	$.ajax({
		url: 'ajax_talentpeak.php?manageMatchingAlgorithmSetting=1',
		type: "GET",
		data: {'groupid':g},
		success: function(data) {
			$('#dynamic_content').html(data);
			$(".deluser").popConfirm({content: ''});
		}
	});
}



function openTouchpointConfigurationModal(g){
	$.ajax({
		url: 'ajax_talentpeak.php?openTouchpointConfigurationModal=1',
		type: "GET",
		data: {'groupid':g},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message});
			} catch(e) { 
				$("#loadAnyModal").html(data);
				$('#touchpointConfigModal').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});
}


function updateTouchPointTypeConfig(g){
	let touchpointtype = $("#touchpointtype").val();
	let show_copy_to_outlook = $("#show_copy_to_outlook").is(":checked");
	let enable_mentor_scheduler = $("#enable_mentor_scheduler").is(":checked");
	let auto_approve_proposals = $("#auto_approve_proposals").is(":checked");

	$.ajax({
		url: 'ajax_talentpeak.php?updateTouchPointTypeConfig=1',
		type: "POST",
		data: {'groupid':g,'touchpointtype':touchpointtype, 'show_copy_to_outlook':show_copy_to_outlook,'enable_mentor_scheduler':enable_mentor_scheduler, 'auto_approve_proposals':auto_approve_proposals},
		success: function(data) {

			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						$("#loadAnyModal").html('');
						$('#touchpointConfigModal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
					}
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }

		}
	});
}

function updateGroupJoinLeaveButton(gid){
	$.ajax({
		url: 	'ajax.php?updateGroupJoinLeaveButton=1',
		type: 	'GET',
		data: 	{ groupid:gid},
		success: function(data){
			$("#membership_btn_container").html(data);
			closeAllActiveModal();
			$(".confirm").popConfirm({content: ''});
			$("#join").focus();
		}
	});
}

function updateTeamMatchingAlgorithmParameters(g){
	var formdata = $('#matchingAlgorithmForm')[0];
	var finalData  = new FormData(formdata);
	finalData.append("groupid",g);
	$.ajax({
		url: 'ajax_talentpeak.php?updateTeamMatchingAlgorithmParameters=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {

			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						manageMatchingAlgorithmSetting(g);
					}
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }
		}
	});
}

function deleteTeamJoinRequestSurveyData(g){
	$.ajax({
		url: 'ajax_talentpeak.php?deleteTeamJoinRequestSurveyData=1',
		type: "GET",
		data: {'groupid':g},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						$('#deleteSurveyDataConfirmationModal').modal('hide');
						$('body').removeClass('modal-open');
						$('.modal-backdrop').remove();
						manageMatchingAlgorithmSetting(g);
					}
				});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }
		}
	});
}
function openAlbumBulkUploadModal(r,g,ch,chnl,v){   
	let is_gallery_view = (typeof v !== 'undefined') ? v : 0;
    $.ajax({
        url: 'ajax_albums.php?openBulkUploadModal='+g,
        type: "GET",
        data: {'album_id':r,'chapterid':ch,'channelid':chnl,'is_gallery_view':is_gallery_view},
        success : function(data) {

            try {
                let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title,text:jsonData.message});
            } catch(e) {
                window.Tksp ||= {};
                window.Tksp.album ||= {};
                window.Tksp.album.selected_files = [];

                $("#updateAlbumModal").html(data);
                $('#albumModal').modal({
                    backdrop: 'static',
                    keyboard: false
                });                    
            }
        }
    });
}

function createEventSurveyCreateUpdateURL(e,t,templateid) {
    var parentUrl = window.parent.location.href;
    $.ajax({
		url: 'ajax_events.php?createEventSurveyCreateUpdateURL=1',
        type: "GET",
		data: {'eventid':e,'trigger':t,'parentUrl':parentUrl,'templateid':templateid},
        success : function(data) {
			window.location.href = data;
		}
	});
}
function getGroupsChaptersForCollaboration(e,g,s) {
    $.ajax({
		url: 'ajax_events.php?getGroupsChaptersForCollaboration=1',
        type: "GET",
		data: {'eventid':e,'groupid':g,'section':s},
        success : function(data) {
			$('#collaboration_selection').html(data);
			$("#collaboration_selection").show();
		}
	});
}



function saveNetworkingProgramStartSetting(g){
	var program_start_date = $('#program_start_date').val();
	var team_match_cycle_days =  $('#team_match_cycle_days').val();
	$.ajax({
		url: 'ajax_talentpeak.php?saveNetworkingProgramStartSetting=1',
		type: "POST",
		data: {'groupid':g,'program_start_date':program_start_date,'team_match_cycle_days':team_match_cycle_days},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message});
			} catch(e) { swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); }
		}
	});
}
function copyEventSurveyShareableLink(sMessage,id){
    $("#"+id).show();
	let shareableLink = document.getElementById(id);
	shareableLink.select();
	shareableLink.setSelectionRange(0, 99999)
	document.execCommand("copy");
	$("#"+id).hide();
	swal.fire({icon: 'success',text:sMessage});
 
	setTimeout(() => {
		$(".swal2-confirm").focus();
	}, 500)
}

function discoverCircles(g, search_filters = undefined)
{
	let filter_attribute_type = [];
	$('select[name="primary_attribute[]"]').each(function(index,input){
		filter_attribute_type.push($(input).find('option:selected').attr('data-keytype'));
	});
	window.location.hash = "getMyTeams/initDiscoverCircles";
	var include_tabs_html = $('#dynamicContent').length ? 0 : 1
	let formdata = $('#filterByNameForm')[0];
	let finalData  = new FormData(formdata);
	finalData.append('search_filters', (typeof search_filters === 'string') ? search_filters: JSON.stringify(search_filters));
	finalData.append('groupid', g);
	finalData.append('include_tabs_html', include_tabs_html);
	finalData.append('filter_attribute_type', filter_attribute_type);
	if ($("#showAvailableCapacityOnly").is(":checked")) {
		finalData.append('showAvailableCapacityOnly', 1);
	} else {
		finalData.append('showAvailableCapacityOnly', 0);
	}
	
	$.ajax({
		url: './ajax_talentpeak.php?discoverCircles=1',
		type: 'POST',
		processData: false,
        contentType: false,
        cache: false,
		data: finalData,
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					
					if (jsonData.status == 0){
						$("#myteams").trigger("click");
						$("#reamRoleRequest").trigger("click");
					}  else {
						if (jsonData.status != 2){
							$("#my_team_menu").trigger("click");
						}	
					}
				});
			} catch(e) {
				if (include_tabs_html) {
					$('#ajax').html(data);
				} else {
					$('#discover_circle_card_container').html(data);
				}

				$(".confirm").popConfirm({content: ''});
				$('.initial').initial({
					charCount: 2,
					textColor: '#ffffff',
					color: window.tskp?.initial_bgcolor ?? null,
					seed: 0,
					height: 30,
					width: 30,
					fontSize: 15,
					fontWeight: 300,
					fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
					radius: 0
				});
			}
		}
	});
}

function refreshEventRSVPWidget(e) {
	$.ajax({
		url: 'ajax_events.php?refreshEventRSVPWidget=1',
		type: "GET",
		data: {'eventid':e},
		success: function(data) {
			$('#joinEventRsvp'+e).html(data);
		}
	});
}

function filterCirclesBySearch(groupid)
{
	var q = $('#js-search-circle-input').val();

	if (q && (q.trim()).length < 3) {
		return Swal.fire({
			title: '<?=gettext("Error");?>',
			text: '<?= addslashes(gettext("Please enter atleast 3 chars to search circles"))?>'
		});
	}

	var hashtag_ids = $('#js-hashtag-ids').val();
	$("#discoverCirclePageNumber").val(2);
	if (!q && !hashtag_ids.length) {
		discoverCircles(groupid);
	} else {
		discoverCircles(groupid, {
			q,
			hashtag_ids,
		});
	}
}

function proceedToJoinCircle(t,r,refreshDetail){
	refreshDetail = (typeof refreshDetail !== 'undefined') ? 1 : 0;
	$.ajax({
		url: 'ajax_talentpeak.php?proceedToJoinCircle=1',
		type: "POST",
		data: {'teamid':t,'roleid':r,'refreshDetail':refreshDetail},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						//discoverCircles(jsonData.val)
						filterCirclesBySearch(jsonData.val);
					} 
				});
			} catch(e) { 
				if (refreshDetail == 1) {
					$("#teamFullDetail").html(data);
				} else {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); 
				}
			
			}
		}
	});
}
function leaveCircleMembership(t,m,refreshDetail){
	refreshDetail = (typeof refreshDetail !== 'undefined') ? 1 : 0;
	$.ajax({
		url: 'ajax_talentpeak.php?leaveCircleMembership=1',
		type: "POST",
		data: {'teamid':t,'memberid':m,'refreshDetail':refreshDetail},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						$("#my_team_menu").trigger("click");
					}  else if(jsonData.status == 2) {
						window.location.reload();
					}
				});
			} catch(e) { 
				if (refreshDetail == 1) {
					$("#teamFullDetail").html(data);
				} else {
					swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>"}); 
				}
			}
		}
	});
}

function getTeamsMessages(g,t){
	let manage_section = $(location).attr('pathname').endsWith('manage') ? 1 : 0;
	window.location.hash = (manage_section) ? "getMyTeams" : "getMyTeams-"+t;
	localStorage.setItem("team_detail_active_tab", "team_message");
	$.ajax({
		url: './ajax_talentpeak.php?getTeamsMessages=1',
		type: 'get',
		data: {groupid:g,teamid:t,manage_section:manage_section},
		success: function(data) {
			$('#loadTeamData').html(data);
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
			
		}
	});
}



function loadMoreDiscoverCircles(g) {	
	
	//retain last focus on loadmore
	let lastListItem  = document.querySelectorAll('.discover_circle_card_list');	
	let last = (lastListItem[lastListItem.length -1]);
	last.querySelector(".card-title button").focus();
	//retain last focus on loadmore.

	let search_filters = {};
	var q = $('#js-search-circle-input').val();
	if (q && (q.trim()).length < 3) {
		q = '';
	}
	var hashtag_ids = $('#js-hashtag-ids').val();
	if (q || hashtag_ids.length) {
		search_filters =  {
			q,
			hashtag_ids,
		};
	}
	let page = $("#discoverCirclePageNumber").val();
	let formdata = $('#filterByNameForm')[0];
	let finalData  = new FormData(formdata);
	finalData.append('search_filters', (search_filters.length ? search_filters: JSON.stringify(search_filters)));

	if ($("#showAvailableCapacityOnly").is(":checked")) {
		finalData.append('showAvailableCapacityOnly', 1);
	} else {
		finalData.append('showAvailableCapacityOnly', 0);
	}
	finalData.append('groupid', g);
	finalData.append('page', page);
	
	$.ajax({
		url: './ajax_talentpeak.php?discoverCircles=1',
		type: 'POST',
		processData: false,
        contentType: false,
        cache: false,
		data: finalData,
		success: function(data) {
			$("#discoverCirclePageNumber").val((parseInt(page) + 1));
			const regex = /<span.*>_l_=(.*)<\/span>$/gm;
			let m;
			if ((m = regex.exec(data)) === null) {
				$('#loadeMoreDiscoverCircle').hide();
			} 
			$('#discover_circle_card_container').append(data);
			$(".confirm").popConfirm({content: ''});
			$('.initial').initial({
				charCount: 2,
				textColor: '#ffffff',
				color: window.tskp?.initial_bgcolor ?? null,
				seed: 0,
				height: 30,
				width: 30,
				fontSize: 15,
				fontWeight: 300,
				fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
				radius: 0
			});

		}
	});
}



function removeURLParameter(url, parameter) {
	var hash = url.split("#").pop();
    //prefer to use l.search if you have a location/link object
    var urlparts= url.split('?');
    if (urlparts.length>=2) {

        var prefix= encodeURIComponent(parameter)+'=';
        var pars= urlparts[1].split(/[&;]/g);

        //reverse iteration as may be destructive
        for (var i= pars.length; i-- > 0;) {
            //idiom for string.startsWith
            if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                pars.splice(i, 1);
            }
        }
        url= urlparts[0]+'?'+pars.join('&');
    }
	if (hash) {
		url = url+"#"+hash;
	}
	return url;
}

function getGroupsForCollaboration(e,g) {
    $.ajax({
		url: 'ajax_events.php?getGroupsForCollaboration=1',
        type: "GET",
		data: {'eventid':e,'groupid':g},
        success : function(data) {
			$('#collaboration_selection').html(data);
			$("#collaboration_selection").show();
		}
	});
}

function getChaptersForCollaborations(g,e){
	let gids = $("#collaborate").val();
	if (gids.length){
		$.ajax({
			url: 'ajax_events.php?getChaptersForCollaborations=1',
			type: 'GET',
			data: {'eventid':e,'host_groupid':g,'groupids':gids.join()},
			success: function(data) {
				try {
					let jsonData = JSON.parse(data);
					swal.fire({title:jsonData.title,text:jsonData.message});
				} catch(e) {
					$("#chapter_colleboration").html(data);
				}
			}
		});
	} else {
		$("#chapter_colleboration").html('');
	}

}

function formatAmountForDisplayJS(n,roundoff) {
	var parts = n.toFixed(roundoff).split(".");
	return parts[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + 
		(parts[1] ? "." + parts[1] : "");
}

function manageSearchConfiguration(g){
	$.ajax({
		url: 'ajax_talentpeak.php?manageSearchConfiguration=1',
		type: "GET",
		data: {'groupid':g},
		success: function(data) {
			$('#dynamic_content').html(data);
			$(".deluser").popConfirm({content: ''});
		}
	});
}

function getTeamInvites(g){
	window.location.hash = "getMyTeams/getTeamInvites";
	$.ajax({
		url: './ajax_talentpeak.php?getTeamInvites=1',
		type: 'GET',
		data: {groupid:g},
		success: function(data) {
			$('#dynamicContent').html(data);
			jQuery(".confirm").popConfirm({content: ''});
			$('.initial').initial({
				charCount: 2,
				textColor: '#ffffff',
				color: window.tskp?.initial_bgcolor ?? null,
				seed: 0,
				height: 30,
				width: 30,
				fontSize: 15,
				fontWeight: 300,
				fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
				radius: 0
			});
		}
	});
}

function initDiscoverCircles(g, search_filters = undefined){
	$.ajax({
		url: './ajax_talentpeak.php?initDiscoverCircles=1',
		type: 'GET',
		data: {groupid:g,search_filters: (typeof search_filters === 'string') ? search_filters: JSON.stringify(search_filters)},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					
					if (jsonData.status == 0){
						$("#myteams").trigger("click");
						$("#reamRoleRequest").trigger("click");
					}  else {
						if (jsonData.status != 2){
							$("#my_team_menu").trigger("click");
						}	
					}
				});
			} catch(e) {
				$('#dynamicContent').html(data);
				$(".confirm").popConfirm({content: ''});
				$('.initial').initial({
					charCount: 2,
					textColor: '#ffffff',
					color: window.tskp?.initial_bgcolor ?? null,
					seed: 0,
					height: 30,
					width: 30,
					fontSize: 15,
					fontWeight: 300,
					fontFamily: 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,Helvetica, Arial,Lucida Grande, sans-serif',
					radius: 0
				});
			}
		}
	});
}

function enableDisableTabs(className, action) {
	// Commented out as it is not working.
	// if (action) { // Disable
	// 	setTimeout(() => {
	// 		$('.'+className).addClass(' disabled');
	// 	}, 10);
	// } else { // enable
	// 	$('.'+className).removeClass(' disabled');
	// }
}



function sortGroupResoures(groupid, chapterid, channelid,isParent, sortby){

	$.ajax({
		url: 'ajax_resources.php?setResourceSortbyState=1',
		type: 'GET',
		data: {sortby:sortby},
		success: function(data) {
			let parent_id = $('#parent_id').val();
			if (isParent) {
				getResourceChildData(parent_id,groupid,chapterid,channelid)
			} else {
				getGroupResources(groupid,chapterid,channelid)
				
			}

			if(sortby === "created"){
				sortby = "Created On";
			}else if(sortby === "modified"){
				sortby = "Modified On";
			}
			setTimeout(() => {
				$("#resourcesListOrder").attr("role","status");
				$("#resourcesListOrder").attr("aria-live","polite");
				$("#resourcesListOrder").html('Sort by ' + sortby); 
				$("#sortListOrder").focus();
			}, 200);
			
		}
	});	
}

function initTouchPointDetailToOutlook(g,t,tp) {
	$.ajax({
		url: 'ajax_talentpeak.php?initTouchPointDetailToOutlook=1',
		type: "GET",
		data: {'groupid':g,'teamid':t,'touchpointid':tp},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message});
			} catch(e) { 
				$("#loadAnyModal").html(data);
				$('#touch_point_copy_detail_model').modal({
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

function getCollaborationRequestApprovers(e,t) {
	$.ajax({
		url: 'ajax_events.php?getCollaborationRequestApprovers=1',
		type: 'GET',
		data: {'topicId':e,topicType:t},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
			} catch(e) {
				$("#loadAnyModal").html(data);
				$('#eventCollaborationApproverSelectionModal').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});
}


function manageEventExpenseEntries(e){
	this.closeAllActiveModal();
	$.ajax({
		url: 'ajax_events.php',
        type: "GET",
		data: 'manageEventExpenseEntries=1&eventid='+e,
        success : function(data) {
			$('#loadAnyModal').html(data);
			$('#manage_event_expense_entries_model').modal({
				backdrop: 'static',
				keyboard: false
			});
			$(".deluser").popConfirm({content: ''});
		}
	});
}
function markAsReadDisclaimer(i,c,e) {
	if (!c){ return; }
	if (c == 'checkbox') {
		$("#consent_text_"+e+"_"+i).removeAttr("disabled");
		if ($("#consent_text_"+e+"_"+i).is(":checked")) {
			$("#consent_text_"+e+"_"+i).attr("disabled", true);
		}
	} else if(c == 'text') {
		$("#consent_text_"+e+"_"+i).removeAttr("disabled");
		if ( $("#consent_text_"+e+"_"+i).val()) {
			$("#consent_text_"+e+"_"+i).attr("disabled", true);
		}
	}
}

function initAddConsent(i,s,e){
	let concentDone = false;
	if (s == 1){
		if ($("#consent_text_"+e+"_"+i).is(":checked")) {
			concentDone = true;
		}
	} else {
		let vi = $("#consent_text_"+e+"_"+i).val();
		let v = $("#consent_text_value_"+e+"_"+i).val();
		if (v == vi && !$('#consent_text_'+e+'_'+i).prop('disabled')) {
			concentDone = true;
		}
	}
	if (concentDone){
		$("#consent_submit_"+e+"_"+i).show();
	} else {           
		$("#consent_submit_"+e+"_"+i).hide();      
	}
}

function submitConsent(section, disclaimerId,consentLang,consentContextId,disclaimerIds){  
	let consentText = $("#consent_text_"+consentContextId+"_"+disclaimerId).val();
	
	if (!consentText) { $("#consent_submit_"+consentContextId+"_"+disclaimerId).hide(); return; }
	$.ajax({
			url: 'ajax_disclaimer.php?addConsent=1',
			type: "POST",
			data: {'disclaimerId': disclaimerId, 'consentText': consentText, 'consentLang':consentLang, 'consentContextId' : consentContextId},
			success: function (data) {
				try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message}).then(function (result) {
					if (jsonData.status == 1){    
						$("#consent_text_"+consentContextId+"_"+disclaimerId).attr("disabled", true);
						checkIfAllConcentAccepted(consentContextId,disclaimerIds);
					}
					$("#consent_submit_"+consentContextId+"_"+disclaimerId).hide()
				});
				} catch(e) {}
			}
		});

}

function checkIfAllConcentAccepted(consentContextId, disclaimerIds){  
	$.ajax({
		url: 'ajax_disclaimer.php?checkIfAllConcentAccepted=1',
		type: "GET",
		data: {'disclaimerIds': disclaimerIds,'consentContextId' : consentContextId},
		success: function (data) {
			if (data == 1) {
				// Remove all elements with the class "locked-container-overlay"
				$('#eventDetail'+consentContextId).find('.locked-container-overlay').remove();
				
			}
		}
	});

}

function reconcileEvent(e){
	$.ajax({
		url: 'ajax_events.php?reconcileEvent=1',
        type: "POST",
		data: {'eventid':e},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {}
		}
	});
}

function getMyBookings(gid){
	window.location.hash = "bookings";
	localStorage.setItem("manage_active", "getManageBookingConfigurationContainer");
	$.ajax({
		url: 'ajax_bookings.php?getMyBookings=1',
        type: "GET",
		data: {'groupid':gid},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$('#ajax').html(data);
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
	} );
}


function getReceivedBookings(gid){
	window.location.hash = "bookings";
	localStorage.setItem("manage_active", "getManageBookingConfigurationContainer");
	$.ajax({
		url: 'ajax_bookings.php?getReceivedBookings=1',
        type: "GET",
		data: {'groupid':gid},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$('#loadeBookingRows').html(data);
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
	} );
}
function newSupportBookingForm(gid){
	this.closeAllActiveModal();
	$.ajax({
		url: 'ajax_bookings.php?newSupportBookingForm=1',
        type: "GET",
		data: {'groupid':gid},
        success : function(data) {
			$('#loadeBookingRows').html(data);
		}
	});
}


function getManageBookingConfigurationContainer(g){
	localStorage.setItem("manage_active", "getManageBookingConfigurationContainer");
	$.ajax({
		url: 'ajax_bookings.php?getManageBookingConfigurationContainer=1',
        type: "GET",
		data: {groupid:g},
		success : function(data) {
			$('#ajax').html(data);
			manageBookingSetting(g);
		}
	});
}

function getManageBookingContainer(g){
	localStorage.setItem("manage_active", "getManageBookingContainer");
	$.ajax({
		url: 'ajax_bookings.php?getManageBookingContainer=1',
        type: "GET",
		data: {groupid:g},
		success : function(data) {
			$('#ajax').html(data);
		}
	});
}

const helpTextMap = {
      "equals": "Mentor and mentee must have the same value for this field.",
      "notEquals": "Mentor and mentee must have different values.",
      "2": "Mentor's value must be equal to mentee's.",
      "4": "Mentor's value must not be equal to mentee's.",
      "1": "Mentor's value must be greater than mentee's.",
      "5": "Mentor's value must be greater than or equal to mentee's.",
      "3": "Mentor's value must be less than mentee's.",
      "6": "Mentor's value must be less than or equal to mentee's.",
    };

	const suveryHelpTextMap = {
		"4": "Mentor's value should be greater than the mentee's value.",
		"5": "Mentor's value should be equal to the mentee's.",
		"6": "Mentor's value should be less than the mentee's.",
		"7": "Mentor's value should not be equal to the mentee's.",
		"8": "Mentor's value should be greater than or equal to the mentee's.",
		"9": "Mentor's value should be less than or equal to the mentee's.",
	  };

    function updateHelpText(row) { // Matching Algo form
        let criterion = row.find(".criteria-dropdown").val();
        let helpCell = row.find(".help-text");

        if (criterion === "11") {
            let min = parseInt(row.find(".range-min").val(), 10);
            let max = parseInt(row.find(".range-max").val(), 10);

            row.find(".range-match-config").show();

            if (isNaN(min) || isNaN(max)) {
            helpCell.text("Please enter valid range difference values.");
            } else if (min > max) {
            helpCell.text("Minimum difference cannot be greater than maximum.");
            } else {
            let rangeText = "";
            if (min === 0 && max === 0) {
                rangeText = "Mentor and mentee must have same value.";
            } else if (min === max) {
                rangeText = `Mentor's value must be ${min > 0 ? "+" + min : min} levels compared to mentee.`;
            } else {
                rangeText = `Mentor's value can be between ${min >= 0 ? "+" + min : min} and ${max >= 0 ? "+" + max : max} levels compared to mentee.`;
            }
            helpCell.text(rangeText);
            }
        } else {
            row.find(".range-match-config").hide();
			let dataType = row.data('type'); // or row.attr('data-type')
			if (dataType =='survey') {
				helpCell.text(suveryHelpTextMap[criterion] || "");
			} else {
				helpCell.text(helpTextMap[criterion] || "");
			}
        }
    }

// End of functions, for new application functions add them before this.

function closeAllActiveModal(){
	$('.modal').modal('hide');
	$('body').removeClass('modal-open');
	$('.modal-backdrop').remove();
	$('#loadAnyModal').html('');
}


let isMobileView = {
    Android: function() {
        return navigator.userAgent.match(/Android/i);
    },
    BlackBerry: function() {
        return navigator.userAgent.match(/BlackBerry/i);
    },
    iOS: function() {
        return navigator.userAgent.match(/iPhone/i);
    },
    Opera: function() {
        return navigator.userAgent.match(/Opera Mini/i);
    },
    Windows: function() {
        return navigator.userAgent.match(/IEMobile/i);
    },
    any: function() {
        return (isMobileView.Android() || isMobileView.BlackBerry() || isMobileView.iOS() || isMobileView.Opera() || isMobileView.Windows());
    }
};

var screenWidth = {
	isXtraLargeOrMore: function() { return window.innerWidth >= 1200;},
	isOneKOrMore: function() { return window.innerWidth >= 1024;},
	isLarge: function() { return window.innerWidth >= 992 && window.innerWidth < 1200;},
	isLargeOrMore: function() { return window.innerWidth >= 992;},
	isLargeOrLess: function() { return window.innerWidth < 1200;},
	isMedium: function() { return window.innerWidth >= 768 && window.innerWidth < 992;},
	isMediumOrMore: function() { return window.innerWidth >= 768;},
	isMediumOrLess: function() { return window.innerWidth < 992;},
	isSmall: function() { return window.innerWidth >= 576 && window.innerWidth < 768;},
	isSmallOrMore: function() { return window.innerWidth >= 576;},
	isSmallOrLess: function() { return window.innerWidth < 768;},
	isXtraSmallOrLess: function() { return window.innerWidth < 576;},
	isPhonePotrait: function() { return window.innerWidth <= 428;},
	isPhoneLandscape : function() { return isMobileView.any() && window.matchMedia("(orientation: landscape)").matches;} 
}
// Keep this section at the end
function getUrlVars(url,index){ // index -1 for all quary parameters
    let vars = [], hash;
    let hashes = url.slice(url.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
	if (index >= 0){
		return vars[index];
	}
    return vars;
}

function stripTags(html) {
	return html.replace(/(<([^>]+)>)|(&nbsp;)/gi, "");
}

function validateEmail(emailaddress){
	let emailReg = /^([\w-\.']+@([\w-]+\.)+[\w-]{2,4})?$/;
	if(!emailReg.test(emailaddress)) {
		return false
	}
	return true;
 }

function checkUncheckAllCheckBoxes (cls,st) {
	$('.'+cls).prop("checked",st);
}

function initPermanentDeleteConfirmation(id,s,r){	
	let redirect = (typeof r !== 'undefined') ? r : false;
	$.ajax({
		url: 'ajax.php?initPermanentDeleteConfirmation=1',
		type: "GET",
		data: {'id':id,"section":s,'redirect':redirect},
		success: function(data) {
			$('#modal_over_modal').html(data);
			$('#confirmationModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function loadDisclaimerByHook(hook, consentContextId, reloadOnClose, callOtherMethodOnClose){
	// <!-- $("#follow_chapter").modal("hide");	 -->
	closeAllActiveModal();
	if (hook){
		$.ajax({
			url: 'ajax_disclaimer.php?loadDisclaimerByHook=1',
			type: "GET",
			data: {'disclaimerHook':hook,'consentContextId': consentContextId, 'reloadOnClose':reloadOnClose,'callOtherMethodOnClose':callOtherMethodOnClose},
			success: function(data) {
				try {
					let jsonData = JSON.parse(data);
				} catch(e) {
					$('#loadAnyModal').html(data);
					$('#loadCompanyDisclaimerModal').modal({
						backdrop: 'static',
						keyboard: false
					});
				}
			}
		});
	}
}

function showImportResponseStats (data){
	var msg = '';
	msg +='<div id="" class="col-md-12" style="border:1px solid rgb(192, 189, 189);">'+
		'<div class="alert alert-dismissible alert-default mb-0">'+
		'<div class="border:1px sold gray;" >'+
		'<p style="color:green;"><strong>Total Processed: ' + data.totalProcessed + '</strong> </p>'+
		'<p style="color:green;"><strong>Total Success: ' + data.totalSuccess + '</strong> </p>'+
		'<p style="color:red;"><strong>Total Failed: ' + data.totalFailed + '</strong> </p>'+
		'</div>'+
		'</div>'+
		'</div>';
	$("#showImportResponseStats").html(msg);
}

function showImportFailedData(data){

	var keys = Object.keys(data[0]);
	var table = '';
	var th = '';
	var tr = '';

	for(var i = 0; i < keys.length; i++){
		var keyVal=keys[i];
		if(i == 0){
			keyVal = "Error";
		}
		th += '<th>'+encodeHTMLEntities(keyVal)+'</th>';
	}

	$.each(data, function (key, val) {
		var td = '';
		$.each(val, function (valkey, fval) {
			td += '<td>'+encodeHTMLEntities(fval)+'</td>';
		});
		tr += '<tr>'+td+'</tr>';
	});
	table += '<div tabindex="0" class="p-1 text-left mt-3" style="border:1px solid rgb(192, 189, 189); overflow: scroll !important; white-space: nowrap; max-height:200px;">'+		
		'<table id="table" class="small table table-hover display compact">'+
		'<caption>Failed data</caption>'+
		'<thead>'+
		'<tr>'
		+th+
		'</tr>'+
		'</thead>'+
		'<tbody>'+
		tr
	'</tbody>'+
	'</table>'+
	'</div>';
	$("#showImportFailedData").html(table);
}

function encodeHTMLEntities(rawStr) {
	return rawStr.replace(/[\u00A0-\u9999<>\&]/g, ((i) => `&#${i.charCodeAt(0)};`));
}

//bar loader
function loadAjaxLoader(){
	(function(window, document) {

		let requestAnimationFrame = window.requestAnimationFrame || window.webkitRequestAnimationFrame;
		let container = document.getElementById("container");
		let stepper   = document.getElementById("stepper");
		let div = container.offsetWidth / 40;
		let count = 0;
		function step() {
			count++;
			//stepper.style.width = (count*20)+"px";
			stepper.style.width = (div*count) +"px";
			if ((div*count) <= container.offsetWidth) {
				requestAnimationFrame(step);
			} else {
				$("#container").hide();
			}
		}
		requestAnimationFrame(step);

	})(window, document);
}
$(document).ready(function () {
	$(document).ajaxStart(function () {
		ajaxindicatorstart('');
	}).ajaxStop(function () {
		ajaxindicatorstop();
	});
});
function ajaxindicatorstart(text) {
	jQuery('body').css('cursor', 'wait');
}

function ajaxindicatorstop() {
	jQuery('body').css('cursor', 'default');
}
// add footer download mobile app function
function downloadMobileApp(){
	$.ajax({
		url: 'ajax.php?downloadMobileApp',
		type: "GET",
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#downloadAppModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}
function showSweetAlert(title,message,messageIcon,footerMessage,refreshPage){
	refreshPage = (typeof refreshPage !== 'undefined') ? refreshPage : 0;
	messageIcon = (typeof messageIcon !== 'undefined') ? messageIcon : '';
	footerMessage = (typeof footerMessage !== 'undefined') ? footerMessage : '';

	let swalObj = {title: title,text:message};
	if (messageIcon){
		swalObj.icon = messageIcon;
	}
	if(footerMessage){
		swalObj.footer = footerMessage;
	}

	if (refreshPage == 1){
		swal.fire(swalObj).then(function(result) {
			window.location.reload();
		});
	} else {
		swal.fire(swalObj);
	}
}

$(document).ajaxComplete(function() {
	preventMultiClick(0); // Remove multi-click disable if it exists anywhere
});

// This global function needs to be there to report ajax errors.
$(document).ajaxError(function( event, jqxhr, settings, thrownError ) {
	let httpMessageObj = {
				'400':'Bad Request (Missing or malformed parameters)',
				'401':'Unauthorized (Please Sign in)',
				'403':'Forbidden (Access Denied)',
				'404':'Not found',
				'500':'Internal server error.',
                '503':'Service Unavailable',
				'0':'Something went wrong while loading the page. Press "Ok" button to reload page again.',
			};
	let status = jqxhr.status;
	let refreshPage = 0;
	if (status == 0){
		refreshPage = 1;
	}

    if (settings.error && jqxhr.skip_default_error_handler) {
        return;
    }

	showSweetAlert('Error',httpMessageObj[status],'','',refreshPage);
});
$(document).ajaxSend(function(elm, xhr, s){
	if (s.type == "POST") {
		xhr.setRequestHeader('x-csrf-token', teleskopeCsrfToken);
	}
	if($.inArray(getUrlVars(s.url,0), __methodAllowedForInterval) === -1){
		clearInterval(__globalIntervalVariable);
	}
});

function showStatistics(g,r){
	$.ajax({
		url: 'ajax_resources.php?showStatistics='+g,
		type: "GET",
		data: {'resource_id':r},
    success : function(response) {
      let jsonResponse = JSON.parse(response);

      if ("success" == jsonResponse.status) {
        let data = jsonResponse.data;
        let message = '<table class="table table-striped text-left mt-5 display" style="font-size: small;"><caption><?= gettext("Folder details")?></caption>';

        $.each( data, function( key, value ) {
		    message += "<tr><td>" + key  + " : </td> <td> " + value + "</td></tr>";
        });
        message += '</table>';

        try {
          	swal.fire({title: jsonResponse.title, html:message}).then(function(result) {
				$('#rid_'+r).focus();
			});
        } catch(e) {
          $("#updateResourceModal").html(data);
          $('#resourceModal').modal({
            backdrop: 'static',
            keyboard: false
          });
        }
      }
    }
  });

}
function parseURLParams(url) {
    let queryStart = url.indexOf("?") + 1,
        queryEnd   = url.indexOf("#") + 1 || url.length + 1,
        query = url.slice(queryStart, queryEnd - 1),
        pairs = query.replace(/\+/g, " ").split("&"),
        parms = {}, i, n, v, nv;

    if (query === url || query === "") return;

    for (i = 0; i < pairs.length; i++) {
        nv = pairs[i].split("=", 2);
        n = decodeURIComponent(nv[0]);
        v = decodeURIComponent(nv[1]);

        if (!parms.hasOwnProperty(n)) parms[n] = [];
        parms[n].push(nv.length === 2 ? v : null);
    }
    return parms;
}
function backToTeamDetail(g,t){
	$(".getMyTeams").trigger('click');
	$(".getMyTeams a").trigger('click');
	getTeamDetail(g,t);
}

function getCalendarIframe(require_auth_token) {
	let params = parseURLParams(window.location.href);

	Swal.fire({
		html: `
		<label for="ancestor-url"><?= gettext('Enter the URL of the page where the iframe will be embedded');?></label>
		<input type="text" id="ancestor-url" class="swal2-input" placeholder="<?= gettext('Enter the URL of the page where the iframe will be embedded');?>" />
		<br>
		<input type="checkbox" id="include-encryption" class="swal2-checkbox" ${require_auth_token ? 'checked disabled' : ''} />
		&emsp;<label for="include-encryption"><?= gettext('Require authentication token');?></label>
	  `,
	  confirmButtonText: '<?= addslashes(gettext("Submit"))?>',
	  showCancelButton: false,
	  preConfirm: () => {
		const ancestorUrl = document.getElementById('ancestor-url').value;
		const includeEncryption = document.getElementById('include-encryption').checked;
		return { ancestorUrl, includeEncryption };
	  },
	  focusConfirm: false,
	  allowOutsideClick: false,
	}).then((result) => {
	  if (result.value) {
		const ancestorUrl = result.value.ancestorUrl;
		const includeEncryption = result.value.includeEncryption;

		let ancestorDomain = (new URL(ancestorUrl)).hostname;
		$.ajax({
		  url: 'ajax_events.php?getCalendarIframe=' + ancestorDomain,
		  type: 'GET',
		  data: { ...params, includeEncryption },
		  success: function (data) {
			$('#loadAnyModal').html(data);
			$('#calendarIframeModal').modal({
			  backdrop: 'static',
			  keyboard: false,
			});
		  },
		});
	  }
	});
  }
// Training Videos JavaScript

function startTrainingVideoModal(tags) {

    // ajax to get modal body
    $.ajax({
        url: 'ajax.php?getTrainingVideoModal=1',
        type: "POST",
        data: {action: "getTrainingVideoModal", tags: tags},
        success : function(video_modal_data_response) {
            if (video_modal_data_response) {
                let response_object = JSON.parse(video_modal_data_response);
                if ("success" === response_object.status) {

                    //  append modal html to document.html.body
                    $('body').append($(response_object.dialog));
                    let video_player = $('#video-player');
                    video_player.html('');
                    video_player.hide();
                    let backtolist_video_button = $('#backtolist_video_button');
                    backtolist_video_button.hide();
                    $('#video-tag-search').show();
                    $('#video-list').show();

                    let video_tag_selector = $('#video-tag-selector');

                    //all below event handlers need to be set on success of ajax, when modal body is received from server and populated

                    video_tag_selector.select2({
                        dropdownAutoWidth: true,
                        multiple: true,
                        width: '100%',
                        placeholder: "Select tags" ,
                        allowClear: false,
						dropdownParent: $('.datepicker-and-video-tags-html-container')
                    });

                    video_tag_selector.on('change', function (e) {
						$(".select2-container .select2-search--inline .select2-search__field").attr("aria-label","Search or select video tag from the dropdown list") ;
                        let selected_tags = $(this).val();
                        $('.video-btn-iterator').each(function(i){
                            let row_tags = $(this).attr('tag-data').split(",");
                            let match = false;
                            $.each( selected_tags, function( key, value ) {
                                let index = $.inArray( value, row_tags );
                                if( index !== -1 ) {
                                    match = true;
                                }
                            });
                            if (match) {
                                $(this).show();
                            } else if(selected_tags.length < 1) {
                                $(this).show();
                            } else {
                                $(this).hide();
                            }
                        });
                    });

                    video_tag_selector.change();

                    backtolist_video_button.bind('click',function(){
                        $(this).hide();
                        let video_player = $('#video-player');
                        video_player.html('');
                        video_player.hide();
                        $('#backtolist_video_button').hide();
                        $('#video-tag-search').show();
                        $('#video-list').show();
                    });

                    $('.close-video-player').bind('click',function(){
                        let training_videos_modal = $('#trainingVideosModal');
                        training_videos_modal.modal('hide');
                        $('.modal-backdrop').hide();
						$("body").removeClass('modal-open');						
                        training_videos_modal.remove();
                    });

                    $('#trainingVideosModal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
					trapFocusInModal("#trainingVideosModal");
                    return false;
                }
            }
            swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Something went wrong. Please try again.'))?>"}).then(function(result) {
                window.location.reload();
            });
        },
        error: function() {
            swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Something went wrong. Please try again.'))?>"}).then(function(result) {
                window.location.reload();
            });
        }
    });

}

function viewTrainingVideo(video_id) {
    console.log('viewTrainingVideo');

    $('#video-player').html('');
    $('#bulk_file_table').remove();
    $('.file_data').val('');
    $('#bulkfileupload').val('');

    $.ajax({
        url: 'ajax.php?getTrainingVideo=1',
        type: "POST",
        data: {video_id: video_id},
        success : function(video_data_response) {
            if (video_data_response) {
                video_data_response = JSON.parse(video_data_response);
                if ("success" == video_data_response.status) {
                    $('#backtolist_video_button').show();
                    $('#video-tag-search').hide();
                    $('#video-list').hide();
                    let video_player = $('#video-player');
                    video_player.html(video_data_response.widget_code);
                    video_player.show();
                    return false;
                }
            }
            swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Something went wrong. Please try again.'))?>"}).then(function(result) {
                window.location.reload();
            });
        },
        error: function() {
            swal.fire({title: '<?=gettext("Error");?>',text:"<?= addslashes(gettext('Something went wrong. Please try again.'))?>"}).then(function(result) {
                window.location.reload();
            });
        }
    });

}

function joinMailingList() {
	// check previous subscription
	$.ajax({
		url: 'ajax.php?checkCurrentMailingList=1',
		type: 'GET',
		success: function(data) {
			const obj = JSON.parse(data);
			// populate sweetalert
			Swal.fire({
				html:
					'<div style="line-height: 1.5em;text-align:left;">' +
					'<p>' + obj.text_heading + ':</p><br>' +
					'<div style="line-height: 2em; border:1px solid #dedede; background:#efefef; padding: 10px;">' +
					'<p style="font-weight: 600;"> <input aria-label="Send me product updates" type="checkbox" id="productupdates" value="" ' + obj.join_product_list + ' />&nbsp;' + obj.text_join_product_list + '</p>' +
					'<p style="font-weight: 600;"> <input aria-label="Send me upcoming training updates" type="checkbox" id="trainingupdates" value="" ' + obj.join_training_list + ' />&nbsp;' + obj.text_join_training_list + '</p>' +
					'<p style="font-weight: 600;"> <input aria-label="Send me webinar updates" type="checkbox" id="webinarupdates" value="" ' + obj.join_webinar_list + ' />&nbsp;' + obj.text_join_webinar_list + '</p>' +
					'</div>' +
					'</div>',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Confirm',
				cancelButtonText: 'Cancel',
				preConfirm: () => {
					var productUpdates = Swal.getPopup()
						.querySelector('#productupdates')
						.checked
					var trainingUpdates = Swal.getPopup()
						.querySelector('#trainingupdates')
						.checked
					var webinarUpdates = Swal.getPopup()
						.querySelector('#webinarupdates')
						.checked

					return {
						productUpdates: productUpdates,
						trainingUpdates: trainingUpdates,
						webinarUpdates: webinarUpdates
					}
				}
			})
				.then((result) => {
					if (result.value) {
						$.ajax({
							url: 'ajax.php?joinMailingList',
							type: "POST",
							data: {
								productUpdates: result.value.productUpdates,
								trainingUpdates: result.value.trainingUpdates,
								webinarUpdates: result.value.webinarUpdates
							},
							success: function(data) {
								let jsonData = JSON.parse(data);
								swal.fire({title:jsonData.title,text:jsonData.message}).then( function(result) {
									// location.reload();
								});
							}
						});
					}

				})
		}
	});

}


function extractVariableEmbeddedInComment(varName,data) {
    const regex = new RegExp(`:::::${varName}=(.*?):::::`);
    const match = regex.exec(data);
    if (match) {
        return match[1]; // Extract the captured group (the variable value)
    } else {
        return null; // No match found
    }
}

// Auto foucs to parent START
let ___lastClickedElement = null;
$(document).click(function(event) {
	if (!__previousActiveElement){
		let pNode = $(event.target.parentNode)[0];
		let cNode = $(event.target)[0]
		___lastClickedElement = [pNode,cNode];
	}
});
$(document).on('show.bs.modal', '.modal', function (jsevent) {
  var modal = $(jsevent.target);
  if (modal.hasClass('tskp_skip_modal_tab_logic')) {
    return;
  }


	if (___lastClickedElement){
    	__previousActiveElement = ___lastClickedElement;
		___lastClickedElement = null;
	}

	// Trap focus inside modal
	// add all the elements inside modal which you want to make focusable
	let  ___focusableElements =
	'button, [href], input, select, textarea, radio,checkbox,rating,[tabindex]:not([tabindex="-1"])';
	let ___modal = document.querySelector('.modal'); // select the modal by class

	if (___modal != null || false){

		let ___firstFocusableElement = ___modal.querySelectorAll(___focusableElements)[0]; // get first element to be focused inside modal
		let ___focusableContent = ___modal.querySelectorAll(___focusableElements);
		let ___lastFocusableElement = ___focusableContent[___focusableContent.length - 1]; // get last element to be focused inside modal


		document.addEventListener('keydown', function(e) {
		let ___isTabPressed = e.key === 'Tab' || e.keyCode === 9;

		if (!___isTabPressed) {
			return;
		}

		if (e.shiftKey) { // if shift key pressed for shift + tab combination
			if (document.activeElement === ___firstFocusableElement) {
			___lastFocusableElement.focus(); // add focus for the last focusable element
			e.preventDefault();
			}
		} else { // if tab key is pressed
			if (document.activeElement === ___lastFocusableElement) { // if focused has reached to last focusable element then focus first focusable element after pressing tab
			___firstFocusableElement.focus(); // add focus for the first focusable element
			e.preventDefault();
			}
		}
		});
		if (typeof ___firstFocusableElement !== 'undefined'){
			___firstFocusableElement.focus();
		}
	}
});
$(document).on('click', '[data-dismiss="modal"]', function(){
	if (__previousActiveElementProfile){
		__previousActiveElement = null;
	} else {
		if (document.body.contains(__previousActiveElement[0]) || document.body.contains(__previousActiveElement[1])) {
			__previousActiveElement[0].focus();
			__previousActiveElement[1].focus();

			setTimeout(() => {
				if ($(__previousActiveElement[0]).is(":focus")) {
					__previousActiveElement[0].focus();
				} else {
					__previousActiveElement[1].focus();
				}
				__previousActiveElement = null;
			}, 500);
			
		}
	}
});
// Auto focus to parent END
// Disable enter key on checkboxes
$(document).on('keyup keypress', 'form input[type="checkbox"]', function(e) {
	if(e.which == 13) {
		e.preventDefault();
		return false;
	}
});

<?php require __DIR__ . '/../../include/common/js/attachments.js.php'; ?>

function activateTeamJoinRequest(...args)
{
	toggleTeamJoinRequestStatus(1, ...args)
}

function deactivateTeamJoinRequest(...args)
{
	toggleTeamJoinRequestStatus(0, ...args)
}

function toggleTeamJoinRequestStatus(isactive, groupid, roleid, userid)
{
	$.ajax({
		url: 'ajax_talentpeak.php?toggleTeamJoinRequestStatus=1',
		type: 'POST',
		data: {
			isactive,
			groupid,
			roleid,
			userid
		},
		dataType: 'json',
		success: function (data) {
			Swal.fire({
				title: data.title,
				text: data.message
			}).then(function () {
				getUnmatchedUsersForTeam(groupid);
			});
		}
	});
}

function updateTeamStatusToComplete(g,t,s,r) {
	$.ajax({
		url: './ajax_talentpeak.php?checkPendingActionItemAndTouchPoints=1',
		type: 'GET',
		data: {groupid:g,teamid:t, status:s},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				if(jsonData.status == 1){

					swal.fire({title: 'Error', text: jsonData.message, allowOutsideClick:false});
				} else if(jsonData.status == 2){

					Swal.fire({
						title: 'Confirmation!',
						text: jsonData.message,
						allowOutsideClick:false,
						showCancelButton: true,
						confirmButtonText: 'Yes, I am sure'
					}).then((result) => {
						if (result.value) {
							updateTeamStatus(g,t,s,r)
						}
					})

				} else {
					updateTeamStatus(g,t,s,r)
				}
			} catch(e) {
				// Nothing to do
				swal.fire({title: '<?=gettext("Error");?>', text: "<?= gettext('Unknown error.');?>",allowOutsideClick:false});
			}
		}
	});
}

window.tskp ||= {};

window.tskp.analytics = {
  user_id: '',
  is_initialised: false,

  init: function (user_id) {
    if (this.is_initialised) {
      return;
    }

    this.user_id = user_id;
    this.is_initialised = true;

    this.addEventListeners();
    this.triggerPageLoadEvent();
  },

  addEventListeners: function () {
    this.addFileDownloadEventListener();
    this.addPageExitEventListener();
  },

  addFileDownloadEventListener: function () {
    $('body').on('click', 'a.js-download-link', function (e) {
      e.preventDefault();

      var anchor = $(e.currentTarget);
      window.location.href = anchor.attr('href');

      window.tskp.analytics.triggerFileDownloadEvent({
        pageName: anchor.attr('aria-label') ?? document.title,
        downloadUrl: anchor.attr('href'),
      });
    });
  },

  addPageExitEventListener: function () {
    $('body').on('click', 'a[href^="http"]:not([href*="//' + window.location.host + '"])', function (e) {
      var anchor = $(e.currentTarget);

      if (anchor.hasClass('js-download-link')) {
        return;
      }

      window.tskp.analytics.triggerPageExitEvent({
        exitUrl: anchor.attr('href').match(/\/\/([^\/]+)/)[1],
        exitName: anchor.attr('aria-label') ?? anchor.text(),
      });
    });
  },

  triggerPageLoadEvent: function () {
    if (!this.is_initialised) {
      return;
    }

    window.aadata ||= [];
    window.aadata.push({
      event: 'Page Load',
      pageInfo: {
        pageName: document.title,
      },
      userInfo: {
        sid: this.user_id,
      },
    });
  },

  triggerPageExitEvent: function (data) {
    window.aadata ||= [];
    window.aadata.push({
      event: 'Exit',
      pageInfo: {
        pageName: document.title,
      },
      userInfo: {
        sid: this.user_id,
      },
      exitInfo: {
        exitUrl: data.exitUrl,
        exitName: data.exitName,
      },
    });
  },

  triggerFileDownloadEvent: function (data) {
    window.aadata ||= [];
    window.aadata.push({
      event: 'Download',
      pageInfo: {
        pageName: data.pageName,
      },
      userInfo: {
        sid: this.user_id,
      },
      downloadInfo: {
        downloadUrl: data.downloadUrl,
      },
    });
  },
};

function toggleChapterChannelBoxVisibility() {
	var isChecked = $('#list_scope option:selected').length > 0;
	if (isChecked) {
		handleCreationScopeDropdownChange('#post_scope');
		$("#chapter").prop("disabled", true);
		$("#channels_selection_div").hide();
		$("#chapters_selection_div").hide();
	} else {
		$("#chapter").prop("disabled", false);
		$("#channels_selection_div").show();
		$("#chapters_selection_div").show();
	}
}
// Manage FE dynamic list modal
function manageDynamicListModal(groupid){
	closeAllActiveModal();
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: 'manageDynamicListModal=1&groupid='+groupid,
        success : function(data) {
			$('#loadAnyModal').html(data);
			$('#dynamic_lists_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}
function addNewDynamicList(groupId){
	closeAllActiveModal();
	$.ajax({
		url: 'ajax.php?addNewDynamicList',
        type: "GET",
		data:{'groupid':groupId},
        success : function(response){ 
			$('#loadAnyModal').html(response);
			$('#addDynamicList').modal({
					backdrop: 'static',
					keyboard: false
				});	
		}
	});
}
function refreshDynamicList(groupId){
	$.ajax({
	url: '../common/ajax_dynamic_list.php?refreshDynamicList',
		type: "GET",
		data: {'groupid': groupId},
		dataType: 'json',
	success : function(response) {
			$('#list_scope').multiselect('destroy');
			$('#list_scope').empty();
			// Rebuilding the multiselect
			$.each(response, function(index, item){
				$('#list_scope').append($('<option>',{
					value: item.list_id,
					text: item.list_name
				}));
			});

			$('#list_scope').multiselect({
				nonSelectedText: '<?=gettext("No list selected"); ?>',
				numberDisplayed: 3,
				nSelectedText: '<?= gettext("List selected");?>',
				disableIfEmpty: true,
				allSelectedText: '<?= gettext("Multiple lists selected"); ?>',
				enableFiltering: true,
				maxHeight: 400,
				enableClickableOptGroups: true
			});
		}
	});
}
// This function handles chapter channel selection for newsletter and announcement. Moved from announcement tempate to global scope
function checkCheckboxes() {
	var isChecked = $('#list_scope option:selected').length > 0;
	if (isChecked) {
		$("#chapter").prop("disabled", true);
		$("#channels_selection_div").hide();
		$("#chapters_selection_div").hide();
	} else {
		$("#chapter").prop("disabled", false);
		$("#channels_selection_div").show();
		$("#chapters_selection_div").show();
	}
}
function getDynamicListUsers(groupid){
	const listMultiselect = $("#list_scope");
	const selectedListIds = listMultiselect.next().find('input[type=checkbox]:checked').map(function(){
		return this.value
	}).get();
	if(selectedListIds.length === 0){
		Swal.fire({title:'', text:'<?= addslashes(gettext("Please select atleast 1 dynamic list to see users list"));?>'});
		return;
	}
	$.ajax({
		url: 'ajax.php?getDynamicListUsers=1',
		type: "POST",
		data: {'listid':selectedListIds, 'groupid': groupid},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message});
			} catch(e) {
				$("#loadAnyModal").html(data);
				$('#dynamicListUsers').modal({
					backdrop: 'static',
					keyboard: false
				});
			 }
		}
	});
}
function handleCreationScopeDropdownChange(mainDropdownId){

	const mainDropdown = $(mainDropdownId);
	if(!mainDropdown){
		console.error(`Dropdown with id ${mainDropdownId} not found.`);
		return;
	}
	const mainDropdownValue = mainDropdown.val();
	if(mainDropdownValue === 'dynamic_list'){
		const multiSelectDropdown = $("#chapter_input");
		if(multiSelectDropdown.length){
			$("#chapter_input").multiselect('refresh');
			$("#chapter_input").multiselect('deselectAll', false);
			$('#chapter_input').multiselect('updateButtonText');
			$("#chapter_input").trigger('change');
		} else {
			console.error(`Dropdown with id chapter_input not found.`);
		}

		const radioSelectDropdown = $("#channel_input");
		if(radioSelectDropdown.length){
			console.log(radioSelectDropdown.val());
			const firstOption = radioSelectDropdown.find('option').first().val();
			console.log('first option is'+ firstOption)
			$("#channel_input").multiselect('refresh');
			$("#channel_input").multiselect('deselectAll', false);
			$("#channel_input").multiselect('select', firstOption);
			$("#channel_input").multiselect('rebuild');
			$('#channel_input').multiselect('updateButtonText');
			console.log(radioSelectDropdown.val());
		} else {
			console.error(`Dropdown with id channel_input not found.`);
		}
		// hide the alert box too
		$('#js_use_and_chapter_connector').hide();
	}

}

function fixRevolvAppAnchorLinksWithoutProtocol(html)
{
  // Fix anchor links that do not have the protocol in the href attribute
  // Make them https links
  var div = $('<div></div>').html(html);

  div.find('a, re-button').each(function (index, element) {
    var anchor = $(element);
    var href = anchor.attr('href');

    if (!href) {
      return;
    }

    /**
     * Skip links
     * 1. that already have the protocol http/https
     * 2. OR that use parent-page protocol which is https for our case
     * 3. OR are javascript attr
     * 4. OR are hash fragment
     */
    if (
      href.startsWith('http')
      || href.startsWith('//')
      || href.startsWith('javascript:')
      || href.startsWith('#')
      || href.startsWith('mailto:')
      || href.startsWith('[%GROUP_URL%]')
      || href.startsWith('[%COMPANY_URL%]')
    ) {
      return;
    }

    // Add https protocol to link
    anchor.attr('href', 'https://' + href);
  });

  return div.html();
}

<?php require __DIR__ . '/event_volunteer.js.php'; ?>

<?php require __DIR__ . '/../../include/common/js/custom_fields.js.php'; ?>

<?php require __DIR__ . '/like_reactions.js.php'; ?>

// This is the last line.... do not add anything after this. Add it before the end section.