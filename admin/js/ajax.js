// Delay Ajax Call
var delayAjax = (function(){
	var timer = 0;
	return function(callback, ms){
	clearTimeout (timer);
	timer = setTimeout(callback, ms);
	};
})();

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
//Date Picker
jQuery(function() {
	jQuery( "#startdate" ).datepicker({
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
    jQuery( "#start_date2" ).datepicker({
        prevText:"click for previous months",
        nextText:"click for next months",
        showOtherMonths:true,
        selectOtherMonths: false,
        dateFormat: 'yy-mm-dd'
    });
    jQuery( "#end_date2" ).datepicker({
        prevText:"click for previous months",
        nextText:"click for next months",
        showOtherMonths:true,
        selectOtherMonths: true,
        dateFormat: 'yy-mm-dd'
    });
});

jQuery(document).ready(function() {
	jQuery(".deluser").popConfirm({content: ''});
});
//Hide after 5 sec
jQuery(document).ready(function(){
    jQuery("#hidemesage").delay(5000).fadeOut(3000);
});


function deleteUser(r,i){
	var finalData  = new FormData();
	$.ajax({
		url: 'ajax.php?deleteUser='+i,
			type: "POST",
			data: finalData,
			processData: false,
			contentType: false,
			cache: false,
        success : function(data) {
			location.reload();
			//jQuery("#"+r).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
		}
	});
}

function undeleteUser(r,i){
	var finalData  = new FormData();
	$.ajax({
		url: 'ajax.php?undeleteUser='+ i,
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
        success : function(data) {
			location.reload();
			// jQuery("#"+r).animate({ backgroundColor: "#c7fbc7" }, "fast").animate({ opacity: "hide" },  2000);
		}
	});
}

/*
function deleteGroup(r,i){
	$.ajax({
		url: 'ajax.php',
        type: "GET",
		data: 'deleteGroup='+ i,
        success : function(data) {
			jQuery("#"+r).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
		}
	});
}
*/
function initGroupPermanentDeleteConfirmation(g){
	$.ajax({
		url: 'ajax.php?initGroupPermanentDeleteConfirmation=1',
		type: "GET",
		data: {'groupid':g},
		success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title:jsonData.title,text:jsonData.message});
			} catch(e) {
				$('#loadAnyModal').html(data);
				$('#confirmationModal').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		}
	});
}
function deleteGroupPermanently(g,x,ac){
	var input = $("#delete_permanently").val().trim();
	if(input == 'permanently delete'){
		$.ajax({
			url: 'ajax.php?deleteGroupPermanently=1&audit='+g,
			type: "POST",
			data: {'groupid':g,'audit_code':ac},
			success: function(data) {
				if (data) {
					$('#confirmationModal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					let jsonData = JSON.parse(data);
					swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
						location.reload();
					});

				} else {
					swal.fire({title: 'Error!', text: "Unable to delete"});
				}
			}
		});
	} else {
		swal.fire({title: 'Error!',text:"Please type 'permanently delete' to confirm.",focusConfirm:true});	
		setTimeout(() => {
			$(".swal2-confirm").focus();   
		}, 500);	
	}
	
}
function changeGroupStatus(id,status,element){
	$.ajax({
		url: 'ajax.php?changeGroupStatus='+id,
        type: "POST",
		data: {'status':status},

        success : function(data) {
			if(status==1){
				$(element).parent('td').parent('tr').css({'background-color':'#ffffff !important'});
			}else if(status==100){
				$(element).parent('td').parent('tr').css({'background-color':'#fde1e1 !important'});
			}else{
				$(element).parent('td').parent('tr').css({'background-color':'#ffffce !important'});
			}
			$(element).parent('td').html(data);
			jQuery(".deluser").popConfirm({content: ''});
		}
	});
}

function changePointsProgramStatus(id, status) {
	$.ajax({
		url: 'ajax_points.php?changePointsProgramStatus='+id,
    type: 'POST',
		data: {
			status
		},
		success: function () {
			window.location.reload();
		}
	});
}

function changeTaskStatus(id,status,element){
	$.ajax({
		url: 'ajax.php?changeTaskStatus='+id,
        type: "POST",
		data: {'status':status},

        success : function(data) {
			if(status==1){
				$(element).parent('td').parent('tr').css({'background-color':'#ffffff !important'});
			}else if(status==100){
				$(element).parent('td').parent('tr').css({'background-color':'#fde1e1 !important'});
			}else{
				$(element).parent('td').parent('tr').css({'background-color':'#ffffce !important'});
			}
			$(element).parent('td').html(data);
			location.reload();
		}
	});
}

function changeEventOfficeLocationStatus(id, status) {
  $.ajax({
    url: 'event_office_locations?action=changeEventOfficeLocationStatus',
    type: 'POST',
    data: {
      event_office_location_id: id,
      status
    },
    success: function () {
      window.location.reload();
    }
  });
}

function updateGroupleadPriority(priority,gid){
	var finalData  = new FormData();
	finalData.append("prioritylist",priority);
	finalData.append("gid",gid);
	$.ajax({
        url: 'ajax.php?updateGroupleadPriority=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {

		}
	});
}
// change category status
function changeGroupCategoryStatus(categoryId, status){
    $.ajax({
        url: 'ajax.php?changeGroupCategoryStatus',
            type: "POST",
            data: {'categoryId':categoryId, 'status': status},
        success : function(data) {
            let jsonData = JSON.parse(data);
            swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
                location.reload();
              });
        }
    });
}
function updateErgCategoryPriority(sortingOrder){
	var finalData  = new FormData();
	finalData.append("order",sortingOrder);
	// finalData.append("categoryid",categoryid);
	$.ajax({
        url: 'ajax.php?updateErgCategoryPriority=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
		}
	});
}
function updateErgPriority(priority,filter){
	var finalData  = new FormData();
	finalData.append("prioritylist",priority);
	finalData.append("filter",filter);
	$.ajax({
        url: 'ajax.php?updateErgPriority=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
		}
	});
}

function deletePost(r,i){
	var finalData  = new FormData();
	$.ajax({
		url: 'ajax.php?deletePost='+ i,
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
        success : function(data) {
			jQuery("#"+r).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
		}
	});

}
function deleteEvent(r,i){
	var finalData  = new FormData();
	$.ajax({
		url: 'ajax.php?deleteEvent='+ i,
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
        success : function(data) {
			jQuery("#"+r).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
		}
	});

}

// blocks deleted, use git blame to find the previous version
// function deleteReferral(r,i){}
// function deleteRecruiting(r,i){}

function deleteLeads(r,i,g){
	var finalData  = new FormData();
	$.ajax({
		url: 'ajax.php?deleteLeads='+ i,
		type: "POST",
		data: {groupid:g},
        success : function(data) {
			jQuery("#"+i).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
		}
	});


}

// For load table
function showFromFields(){
	$("#display").hide();
	$("#modify").show();

}

//Ajax uplaod

$(document).ready(function (e) {
	$("#inviteusers").on('submit',(function(e) {
		e.preventDefault();
		$.ajax({
			url: "ajax.php",
			type: "POST",
			data: new FormData(this),
			contentType: false,
			cache: false,
			processData:false,
			success: function(data)
			{
				$("#ajax").html(data);
				$("#hidemesage").show();
				$("#hidemesage").delay(5000).fadeOut(3000);
				$("#inviteemail").val('');
				$("#invt-btn").show();
				$("#invt-form").hide();
				$("#invt-btn-cncl").hide();

			}
		});
	}));

});



$(document).ready(function (e) {
	$("#uploadcoverpic").on('submit',(function(e) {
		e.preventDefault();
		$.ajax({
			url: "ajax.php",
			type: "POST",
			data: new FormData(this),
			contentType: false,
			cache: false,
			processData:false,
			beforeSend: function() {
              $("#loading-image").show();
              $("#nomsg").hide();
			},
			success: function(data)
			{
				if ( data == 2 ){
					$("#nomsg").html("<font color='red'>Uploading error! Please try again.</font>");
					$("#nomsg").show();
				} else {
					$("#imagedata").html('<img src="'+ data +'" alt="Group cover picture" style="background-size:100% 100%;height:100px;">');
					$("#already").show();
					$("#newupload").hide();
				}
			}
		});
	}));

});


$(document).ready(function (e) {
	$("#inviteusers1").on('submit',(function(e) {
		e.preventDefault();
		$.ajax({
			url: "ajax.php",
			type: "POST",
			data: new FormData(this),
			contentType: false,
			cache: false,
			processData:false,
			success: function(data)
			{
				$("#ajax").html(data);
				$("#hidemesage").show();
				$("#hidemesage").delay(5000).fadeOut(3000);
				$("#inviteemail").val('');
				$("#invt-btn").show();
				$("#invt-form").hide();
				$("#invt-btn-cncl").hide();

			}
		});
	}));

});

function validateEmail(email) {
    var string = /^([\w-\.']+@([\w-]+\.)+[\w-]{2,4})?$/;
   // return string.test(email);

	var result = email.replace(/\s/g, "").split(/,|;/);

    for(var i = 0;i < result.length;i++) {
        if(string.test(result[i])) {

        }else{
			return result[i] + " not a valid email address.";
		}
    }
}

function validateEmailFromName(fromName) {
	var regex = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
	if (regex.test(fromName)) {
		return -1;
	}
   return 0;
}

function removeAdmin(r,i,s){
	var finalData  = new FormData();
	$.ajax({
		url: 'ajax.php?removeAdmin='+ i+'&section='+s,
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
        success : function(data) {
			jQuery("#"+r).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
		}
	});
}

//delete Branch
function deleteBranch(i,e){
	var finalData  = new FormData();
	$.ajax({
		url: 'ajax.php?deletebranch='+ i,
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success : function(data) {
			e.closest('tr').animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
		}
	});
}

// Show hide event type edit
function editEventType(i){
	$("#e"+i).hide();
	$("#u"+i).show();
}
// Cancel update event type edit
function cancelUpdateEventType(i){
	$("#u"+i).hide();
	$("#e"+i).show();
}
// Manage volunteers of event type
function addUpdateEventTypeVolunteerModal(encodedTypeId){
	$.ajax({
		url: 'ajax.php?addUpdateEventTypeVolunteerModal=1',
        type: "GET",
		data:{'encodedTypeId':encodedTypeId},
		success : function(data) {	
			$('#loadAnyModal').html(data);
			$('#new_volunteer_request_form_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}
function removeVolunteerFromEventType(encodedTypeId,volunteerTypeId) {
    $.ajax({
        url: 'ajax.php?removeEventTypeVolunteer=1',
        type: "POST",
        data: { 'encodedTypeId': encodedTypeId, 'volunteerTypeId': volunteerTypeId },
		success: function (data) {
			// Parse the JSON response
			try {
				let jsonData = JSON.parse(data);

				if (jsonData.status === 1) {
					$('#new_volunteer_request_form_modal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					swal.fire({ title: jsonData.title, text: jsonData.message, allowOutsideClick: false }).then(function (result) {
						// Reload the page or update the volunteer list
						location.reload();
					});
				} else {
					swal.fire({ title: 'Error', text: jsonData.message, allowOutsideClick: false });
				}
			} catch (e) {
				swal.fire({ title: 'Error', text: "Unknown error.", allowOutsideClick: false });
			}
		},
		error: function () {
			swal.fire({ title: 'Error', text: "Unknown error.", allowOutsideClick: false });
		}
    });
}

// New function to handle Edit Volunteer Modal
function openEditEventTypeVolunteerModal(encodedTypeId, volunteerId) {
    // Send an AJAX request to fetch the volunteer data based on the volunteerId
    $.ajax({
        url: 'ajax.php?editEventTypeVolunteerModal=1',
        type: "GET",
        data: { 'encodedTypeId': encodedTypeId, 'volunteerId': volunteerId },
        success: function(data) {
            $('#loadAnyModal').html(data);
            $('#new_volunteer_request_form_modal').modal({
                backdrop: 'static',
                keyboard: false
            });
        }
    });
}
// Update event type
function updateEventType(i){
	var finalData  = new FormData();
	var id = $("#id"+i+"").val();
	var v = $("#etype"+i).val();
	finalData.append("event_id",id);
	finalData.append("event_type",v);
	$.ajax({
		url: 'ajax.php?updateEventType=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
        success : function(data) {
			$("#td" + i).html(data);
			$("#etype" + i).val(v);
			$("#e" + i).show();
			$("#u" + i).hide();
		}
	});

}
// Delete Evetn type
function deleteEventType(r,i,a){

	var finalData  = new FormData();
	finalData.append("status",a);
	$.ajax({
		url: 'ajax.php?deleteEventType='+ i,
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
        success : function(data) {
			if (a) {
			jQuery("#e"+r).animate({ backgroundColor: "#ffffff" }, "fast").animate({ opacity: 1 },  2000);
				$("#benable"+r).hide();
				$("#bedit"+r).show();
				$("#bdisable"+r).show();
			} else {
			jQuery("#e"+r).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: 0.5 },  2000);
				$("#bedit"+r).hide();
				$("#bdisable"+r).hide();
				$("#benable"+r).show();
			}

		}
	});
}

// Delete Evetn type
function deletePreferredTimezone(r,i){
	$.ajax({
		url: 'ajax.php?deletePreferredTimezone='+i,
		type: "GET",
        success : function(data) {
			let retVal  = JSON.parse(data);
			if (retVal == 1) {
			jQuery("#e"+r).animate({ backgroundColor: "#ffffff" }, "fast").animate({ opacity: 1 },  2000);
				$("#bdelete"+r).show();
			} else {
			jQuery("#e"+r).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
				$("#bdelete"+r).hide();
			}

		}
	});
}

function openNewGroupLeadTypeModal(i){

	$.ajax({
		url: 'ajax.php?openNewGroupLeadTypeModal='+ i,
		type: "GET",
        success : function(data) {
			$("#loadModal").html(data);
			$('#new_leadType').modal({
				backdrop: 'static',
				keyboard: false
			});

			$('#new_leadType').on('shown.bs.modal', function () {
				$('.close').trigger('focus');
			});
		}
	});
}
// Add new Lead Type
function addOrUpdateLeadType(){
	var v = $("#lead-type").val();
	var s = $("#sys-lead-type").val();
	
	if ( v == "" || s == ""){
		swal.fire({title: 'Error!',text:"Leader type and System lead type are required fields."});
	}else{
		var formdata = $('#leadTypeForm')[0];
		var finaldata  = new FormData(formdata);

		$.ajax({
			url: 'ajax.php?addOrUpdateLeadType=1',
			type: "POST",
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success : function(data) {
				if ( data >= 0 ){
					if(data >0){
						swal.fire({title: 'Success',text:'Leader type updated successfully.'}).then(function(result) {
							window.location.reload();
						});
					} else {
						swal.fire({title: 'Success',text:'Leader type created successfully.'}).then(function(result) {
							window.location.reload();
						});
					}
				}else if (data == -2) {
					swal.fire({title: 'Error!',text:'Welcome email message exceeds the maximum allowed message length'});
				} else {
					swal.fire({title: 'Error!',text:'There has been an internal server error. Please wait and try again later.'});
				}
			}
		});
	}

}
// Delete grouplead types
function confirmDeleteGroupleadType(usercount,update_user_message,typeid,user_role,users_role_name){
	if(usercount > 0){
		showLeadsTable(typeid,update_user_message,user_role);
		  return;
	}
	$.ajax({
		url: 'ajax.php?confirmDeleteGroupleadType=1',
		type: "GET",
		data: {'usercount':usercount,'typeid':typeid,'user_role':user_role,'users_role_name':users_role_name},
		success: function(data) {
			$('#loadAnyModal').html(data);
			$('#confirmationModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}
function deleteGroupleadType(typeid,ac,user_role){
	var input = $("#confirm_delete").val().trim();
	if(input == 'confirm'){
		$.ajax({
			url: 'ajax.php?deleteGroupleadTypePermanently=1',
			type: "POST",
			data: {'typeid':typeid,'audit_code':ac,'user_role':user_role},
			success: function(data) {
				$('#confirmationModal').modal('hide');
				$('body').removeClass('modal-open');
				$('.modal-backdrop').remove();
				swal.fire({title: 'Success!',text:"Deleted successfully."})
				.then(function(result) {
					location.reload();
				});
			}
		});
	} else {
		swal.fire({title: 'Error!',text:"Please type 'confirm' to delete."});
	}
}
function checkIfConfirm() {
	let input = $("#confirm_delete").val().trim();
	if(input == 'confirm'){
		$("#confirm_and_delete_btn").prop('disabled',false);
	}
}
function showLeadsTable(typeid,update_user_message,user_role){
	$.ajax({
		url: 'ajax.php?showLeadsData='+typeid,
        type: "GET",
		data: {'user_role':user_role,'update_user_message':update_user_message},
	    success : function(data) {
			$('#loadModal').html(data);
			$('#showAllLeads').modal('show');
			
		}
	});
}
// Delete Evetn type
function enableDisableGroupleadType(r,i,a){
	var finalData  = new FormData();
	finalData.append("status",a);
	$.ajax({
		url: 'ajax.php?enableDisableGroupleadType='+ i,
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
        success : function(data) {
			if (a) {
			jQuery("#e"+r).animate({ backgroundColor: "#ffffff" }, "fast").animate({ opacity: 1 },  2000);
				$("#benable"+r).hide();
				$("#bedit"+r).show();
				$("#bdisable"+r).show();
			} else {
			jQuery("#e"+r).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: 0.5 },  2000);
				$("#bedit"+r).hide();
				$("#bdisable"+r).hide();
				$("#benable"+r).show();
			}

		}
	});
}
// Function to disable budget option if the selection is "channel lead" when creating new lead from manage group lead types
function selectedSysLead(v){
	if(v == '5'){
		// disable checkbox manage budget
		document.getElementById("budget-manage-toggle").disabled= true;
		// Uncheck checkbox manage budget if selected previously
		document.getElementById('budget-manage-toggle').checked= false;
		// chancge label color
		$(".manage-budget-label").addClass("gray");
	}else{
		document.getElementById("budget-manage-toggle").disabled= false;
		$(".manage-budget-label").removeClass("gray");
	}
	
}

function deleteDepartment(i){
	var finalData  = new FormData();
	$.ajax({
		url: 'ajax.php?deleteDepartment='+i,
        type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
        success : function(data) {
			jQuery("#ed"+i).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
		}
	});
}

/* LOGO UPLOAD SECTION */

function uploadCompanyLogo(){
	$("#uploadlogo").show();
	$("#already-logo").hide();
	$("#loading-image-logo").hide();
	$("#logo").val('');

}
function CancelUploadLogo(){
	$("#uploadlogo").hide();
	$("#already-logo").show();
}

function deleteLoginScreenBackground() {
	var finaldata = 1;
	$.ajax({
		url: "ajax.php?deleteLoginScreenBackground=1",
		type: "POST",
		data: finaldata,
		contentType: false,
		cache: false,
		processData: false,
		beforeSend: function () {
			$("#loading-image-background2").show();
		},
		success: function (data) {
			$("#loading-image-background2").hide();
			$("#already-background").hide();
			$("#newupload-background").show();
			$("#nomsg-background").show();
		}
	});
}

$(document).ready(function (e) {

	$("#uploadCompanyLogo").on('submit',(function(e) {
		e.preventDefault();
		let company_logo = $("input[name=logo]").val();
		var finaldata  = new FormData();
		if (company_logo){
			var media  = JSON.parse(company_logo);
			var base64 = media.output.image;
			var type   = media.output.type;
			var block  = base64.split(";");

			// get the real base64 content of the file
			var realData = block[1].split(",")[1];

			// Convert to blob
			var blob = b64toBlob(realData, type);

			var logoFile = new File([blob],media.input.name,{ type: type });
			finaldata.append('logo',logoFile);
		}
		$.ajax({
			url: "ajax.php?uploadCompanyLogo=1",
			type: "POST",
			data: finaldata,
			contentType: false,
			cache: false,
			processData:false,
			beforeSend: function() {
              $("#loading-image-logo").show();
              $("#nologomsg").hide();
			},
			success: function(data)
			{
				if ( data == 3 ){
					$("#nologomsg").html("<font color='red'>Invalid File Type</font>");
					$("#nologomsg").show();
				}
				else if ( data == 2 ){
					$("#nologomsg").html("<font color='red'>Uploading error! Please try again.</font>");
					$("#nologomsg").show();
				}
				else {
					$("#newLogo").html('<img src="'+ data +'" alt="Company logo picture" style="background-size:100% 100%;height:50px;">');
					$("#already-logo").show();
					$("#uploadlogo").hide();
				}
			}
		});
	}));

});
/* END LOGO UPLOAD */

/* LOGIN BACK GROUND PICTURE*/

function updateBackground(){
	$("#newupload-background").show();
	$("#already-background").hide();
	$("#loading-image-background").hide();
	$("#loginscreen_background").val('');

}
function CancelUploadBackground(){
	$("#newupload-background").hide();
	$("#already-background").show();

}

$(document).ready(function (e) {
	$("#loginScreenBackground").on('submit',(function(e) {
		e.preventDefault();
		var finaldata  = new FormData();

		let loginscreen_background_name = $("input[name=loginscreen_background]").val();
		if (loginscreen_background_name){
			var media  = JSON.parse(loginscreen_background_name);
			var base64 = media.output.image;
			var type   = media.output.type;
			var block  = base64.split(";");

			// get the real base64 content of the file
			var realData = block[1].split(",")[1];

			// Convert to blob
			var blob = b64toBlob(realData, type);

			var loginscreen_background = new File([blob],media.input.name,{ type: type });
			finaldata.append('loginscreen_background',loginscreen_background);
		}
		$.ajax({
			url: "ajax.php?uploadLoginScreenBackground=1",
			type: "POST",
			data: finaldata,
			contentType: false,
			cache: false,
			processData:false,
			beforeSend: function() {
              $("#loading-image-background").show();
              $("#nomsg-background").hide();
			},
			success: function(data)
			{
				if ( data == 3 ){
					$("#nomsg-background").html("<font color='red'>Invalid File Type</font>");
					$("#nomsg-background").show();
				}
				else if ( data == 2 ){
					$("#nomsg-background").html("<font color='red'>Uploading error! Please try again.</font>");
					$("#nomsg-background").show();
				} else {
					$("#new_background").html('<img src="'+ data +'" alt="Login screen background image" style="background-size:100% 100%;height:100px;width:250px;">');
					$("#already-background").show();
					$("#newupload-background").hide();
				}
			}
		});
	}));

});

/* END LOGIN SCREEN BACKGROUND */

/* My Events Background PICTURE*/

function updateMyEventsBackground(){
	$("#new-events-background").show();
	$("#existing-background").hide();
	$("#loading-image-banner3").hide();
	$("#my_events_background").val('');

}
function CancelUploadBackground(){
	$("#new-events-background").hide();
	$("#existing-background").show();

}

$(document).ready(function (e) {
	$("#myEventsBackground").on('submit',(function(e) {
		e.preventDefault();
		var finaldata  = new FormData();

		let loginscreen_background_name = $("input[name=my_events_background]").val();
		if (loginscreen_background_name){
			var media  = JSON.parse(loginscreen_background_name);
			var base64 = media.output.image;
			var type   = media.output.type;
			var block  = base64.split(";");

			// get the real base64 content of the file
			var realData = block[1].split(",")[1];

			// Convert to blob
			var blob = b64toBlob(realData, type);

			var loginscreen_background = new File([blob],media.input.name,{ type: type });
			finaldata.append('my_events_background',loginscreen_background);
		}
		$.ajax({
			url: "ajax.php?uploadMyEventsBackground=1",
			type: "POST",
			data: finaldata,
			contentType: false,
			cache: false,
			processData:false,
			beforeSend: function() {
              $("#loading-image-banner3").show();
              $("#nomsg-background").hide();
			},
			success: function(data)
			{
				if ( data == 3 ){
					$("#nomsg-background").html("<font color='red'>Invalid File Type</font>");
					$("#nomsg-background").show();
				}
				else if ( data == 2 ){
					$("#nomsg-background").html("<font color='red'>Uploading error! Please try again.</font>");
					$("#nomsg-background").show();
				} else {
					$("#loading-image-banner3").hide();
					$("#new_background").html('<img src="'+ data +'" alt="My events background image" style="background-size:100% 100%;height:100px;width:250px;">');
					$("#existing-background").show();
					$("#new-events-background").hide();
				}
			}
		});
	}));

});
function deleteMyEventsBackground() {
	var finaldata = 1;
	$.ajax({
		url: "ajax.php?deleteMyEventsBackground=1",
		type: "POST",
		data: finaldata,
		contentType: false,
		cache: false,
		processData: false,
		beforeSend: function () {
			$("#loading-image-banner3").show();
		},
		success: function (data) {
			$("#loading-image-banner3").hide();
			$("#existing-background").hide();
			$("#new-events-background").show();
			$("#nomsg-background").show();
			$('#cancelMyeventsImageUpload').hide();
		}
	});
}

/* END My  Events BACKGROUND */




/* AFFNITIES WEB APP BANNER PICTURE*/

function updatebanner(){
	$("#newupload-banner").show();
	$("#already-banner").hide();
	$("#loading-image-banner").hide();
	$("#affinity_home_banner").val('');

}

function updateZoneTileImage(i){
	if (i == 'zone_tile_compact_bg_image'){
		$("#newuploadZoneTileCompactImg").show();
		$("#loading-image-zone-compact-tile").hide();
		$("#oldTileCompactImg").hide();	
		$("#updateZoneCompactTile").hide();
	} else {
		$("#newuploadZoneTileImg").show();
		$("#loading-image-zone-tile").hide();
		$("#oldTileImg").hide();	
		$("#updateZoneTile").hide();
	}
}

function CancelUploadZoneTileImg(i){
	if (i == 'zone_tile_compact_bg_image'){
		$("#newuploadZoneTileCompactImg").hide();
		$("#oldTileCompactImg").show();
		$("#updateZoneCompactTile").show();
	} else {
		$("#newuploadZoneTileImg").hide();
		$("#oldTileImg").show();
		$("#updateZoneTile").show();
	}
}

function deleteBanner() {
	var finaldata = 1;
	$.ajax({
		url: "ajax.php?deleteBanner=1",
		type: "POST",
		data: finaldata,
		contentType: false,
		cache: false,
		processData: false,
		beforeSend: function () {
			$("#loading-image-banner2").show();
		},
		success: function (data) {
			$("#loading-image-banner2").hide();
			$("#already-banner").hide();
			$(".btn-danger").hide();
			$("#newupload-banner").show();
		}
	});
}


function CancelUploadbanner(){
	$("#newupload-banner").hide();
	$("#already-banner").show();

}

$(document).ready(function (e) {
	$("#affnitywebappBanner").on('submit',(function(e) {
		e.preventDefault();
		var finaldata  = new FormData();
		let affinity_home_banner = $("input[name=affinity_home_banner]").val();
		if (affinity_home_banner){
			var media  = JSON.parse(affinity_home_banner);
			var base64 = media.output.image;
			var type   = media.output.type;
			var block  = base64.split(";");

			// get the real base64 content of the file
			var realData = block[1].split(",")[1];

			// Convert to blob
			var blob = b64toBlob(realData, type);

			var affinity_home_banner_file = new File([blob],media.input.name,{ type: type });
			finaldata.append('affinity_home_banner',affinity_home_banner_file);
		}

		$.ajax({
			url: "ajax.php?uploadAffinityHomeBanner=1",
			type: "POST",
			data: finaldata,
			contentType: false,
			cache: false,
			processData:false,
			beforeSend: function() {
              $("#loading-image-banner").show();
              $("#nomsg-banner").hide();
			},
			success: function(data)
			{
				if ( data == 3 ){
					$("#nomsg-banner").html("<font color='red'>Invalid File Type</font>");
					$("#nomsg-banner").show();
				}
				else if ( data == 2 ){
					$("#nomsg-banner").html("<font color='red'>Uploading error! Please try again.</font>");
					$("#nomsg-banner").show();
				} else {
					$('#web_banner_image').attr('src',data);
					$("#already-banner").show();
					$("#newupload-banner").hide();
				}
			}
		});
	}));

});

/* END AFFINITIES WEB APP BANNER BACKGROUND */

/* Delete Hot link*/

function deleteHotLink(i){
	var finalData  = new FormData();
	$.ajax({
		url: 'ajax.php?deleteHotLink='+ i,
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
        success : function(data) {
			jQuery("#"+i).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);

		}
	});


}

function showBudgetForm(){
	$("#manage-budget-edit").hide();
	$("#group-budget").hide();
	$("#form-budget").show();
}

function hideBudgetForm(){
	$("#manage-budget-edit").show();
	$("#group-budget").show();
	$("#form-budget").hide();
}

function submitBudget(){
	var amt = $("#budgetval").val();
	var year = $("#year").val();

	if ( amt == '' && amt == 0 ){
		$("#budgetval").focus();
		$("#budgetval").css({'background-color' : '#eee'});
		swal.fire({title: 'Error!',text:"Amount cannot be empty!"});
	}else {

		var finalData = new FormData();
		finalData.append("amount", amt);
		finalData.append("year", year);
		$.ajax({
			url: 'ajax.php?submitBudget=1',
			type: "POST",
			data: finalData,
			processData: false,
			contentType: false,
			cache: false,
			success: function (data) {
				if($.isNumeric(data)) {
					let remaining_budget = parseFloat(data).toFixed(2);
					amt = parseFloat(amt).toFixed(2);
					$("#t-amt").html(amt);
					$("#hidemesage").show();
					$("#manage-budget-edit").show();
					$("#form-budget").hide();
					$("#group-budget").show();
					$("#remained-bgt").html(remaining_budget);
					setTimeout(function () {
						$("#hidemesage").hide();
					}, 1000);
				} else {
					$("#budgetval").focus();
					$("#budgetval").css({'background-color' : '#f59191'});
					swal.fire({title: 'Error!',text:data});
				}
			}
		});
	}
}

function updateGroupBudget(i,gid){
	var amt = $("#groupbudgetamt"+i).val();
	var year = $("#year").val();
	var notifyleads = 0;
	if ( amt == '' && amt == 0 ){
		$("#groupbudgetamt"+i).focus();
		$("#groupbudgetamt"+i).css({'background-color' : '#eee'});
		swal.fire({title: 'Error!',text:"Amount cannot be empty!"});
	}else{
		Swal.fire({
			allowOutsideClick: false,
			showCancelButton: true,
			input: 'select',
			inputOptions: {
				no: 'Save changes without sending notifications',
				yes: 'Save changes and notify group leaders'
			},
			html:
			  'You can choose to notify the group budget leaders about their new budget. Please select an appropriate option below and click "Submit" to save the budget changes. If you do not wish to save the changes, then click on the "Cancel" button.',
			confirmButtonText:
			  'Submit',
			  inputValidator: (value) => {
				if(value === 'yes'){
					notifyleads = 1;
				}

				var finalData  = new FormData();
				finalData.append("amount",amt);
				finalData.append("groupid",gid);
				finalData.append("year",year);
				finalData.append("notifyleads",notifyleads);

				$.ajax({
					url: 'ajax.php?updateGroupBudget=1',
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
							amt = parseFloat(amt).toFixed(2);
							$('.disable-update-button').prop('disabled', true);

							$("#remained-bgt").html(remaining_budget);
							$("#allocated-bgt").html(allocated_budget);
							$("#done"+i).html('<img src="images/done.png" alt="Success placeholder image" height="14px" id="done-img'+i+'" >');
							$("#jdone"+i).show();
							$("#hidemesage"+i).show();
							$("#groupbudgetamt"+i).val(amt);
							$("#groupbudgetamt"+i+"-btn").hide();
							$("#groupbudgetamt"+i).css({'background-color' : '#63c375'});
							setTimeout(function(){
								$("#hidemesage"+i).hide();
								$("#groupbudgetamt"+i+"-btn").show();
								$("#groupbudgetamt"+i).css({'background-color' : '#fff'});
							}, 1000);
						} else {
							$("#groupbudgetamt"+i).focus();
							$("#groupbudgetamt"+i).css({'background-color' : '#f59191'});
							swal.fire({title: 'Error!',text:retVal.errorMessage});
						}
					}
				});

			}
		});

	}
}

function closeGrupBudgetForm(){
	location.reload();
	$("#show-group-div").hide();
	$("#main-budget-div").show();
}

$(document).ajaxStart(function() {
    $(document.body).css({'cursor' : 'wait'});
}).ajaxStop(function() {
    $(document.body).css({'cursor' : 'default'});
});

function filterEvents(g){
	var v = $("#view_style").val();
	var g = $("#bygroup").val();
	var year = $("#filterByYear").val();
	$.ajax({
        url: 'ajax.php',
        type: 'GET',
        data: 'filterEvents='+g+'&year='+year,
		beforeSend: function() {
           $("#resultLoading").hide();
		},
        success: function(data) {
			$("#ajax").html(data);
			if (v == 2){
				loadCalc();
			} else {
				$('#calendar').hide();
			}
		}
	});
}
function updateEventData(i){
	$.ajax({
        url: 'ajax.php',
        type: 'GET',
        data: 'updateEventData='+i,
		beforeSend: function() {
           $("#resultLoading").hide();
		},
        success: function(data) {
			$("#ajax").html(data);
		}
	});

}

function updateLinkPriority(priority){
	var finalData  = new FormData();
	finalData.append("priority",priority);
	$.ajax({
		url: 'ajax.php?updateLinkPriority=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {

		}
	});
}


function editBannerTitle(){
	$("#banner_title").hide();
	$("#banner_title_edit").hide();
	$("#web_banner_title").show();
	$("#banner_title_update").show();
}

function editZoneStyle(i){
		$("#" + i + "_edit").hide();
		$("#" + i).hide();
		$("#" + i + "_input").show();
		$("#" + i + "_update").show();
}

function updateZoneStyle(i){
	
	var finalData  = new FormData();	
	if(i == 'zone_tile_bg_image'){
		let zone_tile_bg_image = $("input[name=zone_tile_bg_image]").val();
		if (zone_tile_bg_image){
			var media  = JSON.parse(zone_tile_bg_image);
			var base64 = media.output.image;
			var type   = media.output.type;
			var block  = base64.split(";");	
			// get the real base64 content of the file
			var realData = block[1].split(",")[1];	
			// Convert to blob
			var blob = b64toBlob(realData, type);
			var zone_tile_bg_imageFile = new File([blob],media.input.name,{ type: type });
			finalData.append('zone_tile_bg_image',zone_tile_bg_imageFile);
			
		}		
	}
	else if(i == 'remove_img'){
		finalData.append("remove_img",'remove_img');
	} 
	else if (i == 'zone_tile_compact_bg_image') {
		let zone_tile_compact_bg_image = $("input[name=zone_tile_compact_bg_image]").val();
		if (zone_tile_compact_bg_image){
			var media  = JSON.parse(zone_tile_compact_bg_image);
			var base64 = media.output.image;
			var type   = media.output.type;
			var block  = base64.split(";");	
			// get the real base64 content of the file
			var realData = block[1].split(",")[1];	
			// Convert to blob
			var blob = b64toBlob(realData, type);
			var zone_tile_compact_bg_imageFile = new File([blob],media.input.name,{ type: type });
			finalData.append('zone_tile_compact_bg_image',zone_tile_compact_bg_imageFile);
			
		}	

	} 
	else if (i == 'remove_compact_img') {
		finalData.append("remove_compact_img",'remove_compact_img');
	}
	else { // zone_tile_heading or zone_tile_sub_heading
		let val =$("#" + i + "_input").val();
		finalData.append(i,val);
	}
	
	$.ajax({
		url: 'ajax.php?updateZoneStyle=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,		
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				if (jsonData.status == 1){
					window.location.href = 'branding';
				} else {
					swal.fire({title: jsonData.title,text:jsonData.message});
				}
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
			
		}
	});
}

function updateBannerTitle(){
	var finalData  = new FormData();
	var val =$("#web_banner_title").val();
	finalData.append("web_banner_title",val);
	$.ajax({
		url: 'ajax.php?updateBannerTitle=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			$("#banner_title").html("<strong>" + data + "</strong>");
			$("#banner_title").show();
			$("#banner_title_edit").show();
			$("#web_banner_title").hide();
			$("#banner_title_update").hide();
		}
	});
}

function editBannerSubTitle(){
	$("#banner_subtitle").hide();
	$("#banner_subtitle_edit").hide();
	$("#web_banner_subtitle").show();
	$("#banner_subtitle_update").show();
}

function updateBannerSubTitle(){
	var finalData  = new FormData();
	var val =$("#web_banner_subtitle").val();
	finalData.append("web_banner_subtitle",val);
	$.ajax({
		url: 'ajax.php?updateBannerSubTitle=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			$("#banner_subtitle").html("<strong>" + data + "</strong>");
			$("#banner_subtitle").show();
			$("#banner_subtitle_edit").show();
			$("#web_banner_subtitle").hide();
			$("#banner_subtitle_update").hide();
		}
	});
}

// Approve budget  initiates
function approveBudget(table_id,section,id){
	if (section == 3){
		$.ajax({
			url: 'ajax.php',
			type: 'GET',
			data: 'loadBudgetdenied='+id+"&request_status="+section,
			success: function(data) {
				$('#approve-input').html(data);
				$('#request_modal_title').html('Deny Budget Request');
				$('#budgetModel').modal('show');
			}
		});

	}else{
		$.ajax({
			url: 'ajax.php',
			type: 'GET',
			data: 'loadBudgetApprove='+id+"&request_status="+section,
			success: function(data) {
				$('#loadAnyModal').html(data);
				$('#budgetApprovalModel').modal({
					backdrop: 'static',
					keyboard: false
				});
			}
		});

	}
}

//Submit approve budget
function submitApproveForm(){
	$('#request_submit-btn').prop('disabled', true);
	let formdata = $('#approve-budget-form')[0];
	let finaldata  = new FormData(formdata);

	$.ajax({
        url: 'ajax?approveBudget=1',
        type: 'POST',
		processData: false,
        contentType: false,
        cache: false,
        data: finaldata,
        success: function(data) {
			if (data == 1) {
				$('#budgetModel').modal('hide');
				$('body').removeClass('modal-open');
				$('.modal-backdrop').remove();
				swal.fire({title: 'Success',text:"Your request has been processed successfully!"});
				setTimeout(function () {
					location.reload();
				}, 1000);
			} else if (data == 2) {
				swal.fire({title: 'Error',text:"Invalid request"});
				$('#request_submit-btn').prop('disabled', false);
			} else {
				swal.fire({title: 'Error',text:data});
				$('#request_submit-btn').prop('disabled', false);
			}
		}
	});
}
//Submit Denied budget
function submitDeniedForm(){
	$('#request_submit-btn').prop('disabled', true);
	let formdata = $('#approve-budget-form')[0];
	let finaldata  = new FormData(formdata);
	$.ajax({
        url: 'ajax?denyBudget=1',
        type: 'POST',
		processData: false,
        contentType: false,
        cache: false,
        data: finaldata,
        success: function(data) {
			if (data == 1) {
				$('#budgetModel').modal('hide');
				$('body').removeClass('modal-open');
				$('.modal-backdrop').remove();
				swal.fire({title: 'Success',text:"Request rejected successfully"});
				setTimeout(function () {
					location.reload();
				}, 1000);
			} else {
				swal.fire({title: 'Error!',text:"Data Error"});
			}
		}
	});
}

// Load Qr Code in Modal
function generateQrCode(id){
	$.ajax({
        url: 'ajax.php',
        type: 'GET',
		data: "generateQrCode="+id,
        success: function(data) {
			$('#loadQRCode').html(data);
			$('#generate-qr-code').modal('show');
		}
	});
	
}

// Update User timezone
function updateTimeZone(){
	var tz = $("#selected_timezone").val();
	$.ajax({
        url: 'ajax.php?updateTimeZone=1',
        type: 'POST',
		data: "timezone="+tz,
        success: function(data) {
			location.reload();
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
			location.reload();
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
			location.reload();
		}
	});
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
					$("#show_dropdown").html(response);
					var myDropDown=$("#user_search");
					var length = $('#user_search> option').length;
					myDropDown.attr('size',length);
				}
			});
		}
	},500)
}

function closeDropdown(){
	var myDropDown=$("#user_search");
	var length = $('#user_search> option').length;
	myDropDown.attr('size',0);
}

function changeGroupChapterStatus(gid,cid,status,element){
	$.ajax({
		url: 'ajax.php?change_group_chapter_status='+cid,
		type: "POST",
		data: {'gid':gid,'status':status},

		success : function(data) {
			if(status==1){
				$(element).parent('td').parent('tr').css({'background-color':'#ffffff !important'});
			}else{
				$(element).parent('td').parent('tr').css({'background-color':'#ffffce !important'});
			}
			$(element).parent('td').html(data);
			jQuery(".deluser").popConfirm({content: ''});
		}
	});
}
// Search user for lead
function searchUsersToLeadChannel(g,c,k){
	delayAjax(function(){
		if(k.length >= 3){
			$.ajax({
				type: "GET",
				url: "ajax.php?search_users_to_lead_channel="+k,
				data: {'gid':g,'cid':c},
				success: function(response){
					$("#show_dropdown").html(response);
					var myDropDown=$("#user_search");
					var length = $('#user_search> option').length;
					myDropDown.attr('size',length);
				}
			});
		}
	},500)
}

// Search user for lead
function searchUsersToLeadChapter(g,c,k){
	delayAjax(function(){
		if(k.length >= 3){
			$.ajax({
				type: "GET",
				url: "ajax.php?search_users_to_lead_chapter="+k,
				data: {'gid':g,'cid':c},
				success: function(response){
					$("#show_dropdown").html(response);
					var myDropDown=$("#user_search");
					var length = $('#user_search> option').length;
					myDropDown.attr('size',length);
				}
			});
		}
	},500)
}

function deleteChapterLead(i,u,c,g){
	$.ajax({
		type: "POST",
		url: "ajax.php?delete_chapter_lead="+u,
		data: {'gid':g,'cid':c},
		success: function(data){
			if(data == 1){
				jQuery("#"+u).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
			}else{
				swal.fire({title: 'Error',text:data});
			}
		}
	});
}

function searchGroups(value){
	if(value.length >= 1){
		$.ajax({
			type: "GET",
			url: "ajax.php",
			data: {
				'search_keyword_group' : value
			},
			success: function(response){
				$("#show_dropdown").html(response);
				var myDropDown=$("#group_search");
				var length = $('#group_search> option').length;
				myDropDown.attr('size',length);
			}
		});
	}
}
function closeGroupDropdown(){
	var myDropDown=$("#group_search");
	var length = $('#group_search> option').length;
	myDropDown.attr('size',0);
}

function getChapterslist (v) {
	$.ajax({
		type: "GET",
		url: "ajax.php",
		data: {
			'get_group_chapters' : v.value
		},
		success: function(response){
			$("#chapterslist").html(response);
		}
	});

}

function changeGroupSetting(i){
	$.ajax({
		url: "ajax.php?changeGroupSetting=1",
		type: "POST",
		data: {groupid:i},
		success: function(data)
		{
			$('#ergSettingContainer').html(data);
			$('#ergSetting').modal('show');

		}
	});

	
}
function exportGroup(id,filename){
	var jsonData = ""; // This variable will store the JSON data received from the server
	$.ajax({
		url: 'ajax.php?exportGroup',
		data: {groupid:id},
		type: "POST",
		success : function(data) {
				jsonData = JSON.stringify(data);
				// Create a temporary anchor element
				var downloadLink = document.createElement('a');
				downloadLink.href = 'data:application/json;charset=utf-8,' + encodeURIComponent(jsonData);
				downloadLink.download = filename+'_data.json';
				// Trigger the download by programmatically clicking the link
				downloadLink.click();
		},
		error: function (xhr, status, error) {
            if (xhr.responseJSON && xhr.responseJSON.error) {
                var errorMessage = xhr.responseJSON.error;
                swal.fire({
                    title: 'Error',
                    text: errorMessage,
                    icon: 'error'
                });
            } else {
                // If the server response is not in the expected JSON format
                swal.fire({
                    title: 'Error',
                    text: 'Invalid server response',
                    icon: 'error'
                });
            }
        }
	});
}
function createFromTemplate(sourceTemplateId){
	$.ajax({
		url: 'ajax.php?createFromTemplate',
		data: {sourceTemplateId:sourceTemplateId},
		type: "POST",
		success : function(data) {
			if (data){
				swal.fire({title: 'Success',text:"Program Created From Template."}).then(function(result) {
					location.reload();
				});
			} else {
				swal.fire({title: 'Error',text:"Something went wrong. Please try again."});
			}
		}
	});	
}
function cloneGroup(){
	let copyLeads = 0;
	let i = $('#cloned_group').val();
	Swal.fire({
		  title: 'Clone Group',
		  allowOutsideClick: false,
		  showCancelButton: true,
		  input: 'checkbox',
		  inputValue: 1,
		  inputPlaceholder:
			'Do you want to copy the Program Leaders?',
		  confirmButtonText:
			'Continue'
		}).then((res) => {
			if (res.value) {
				copyLeads = 1; 
			}
			if(!res.isDismissed){
				$.ajax({
					url: "ajax.php?cloneGroup=1",
					type: "POST",
					data: {groupid:i, copyLeads:copyLeads},
					success: function(data)
					{
						if (data){
							swal.fire({title: 'Success',text:"Group Cloned successfully."}).then(function(result) {
								location.reload();
							});
						} else {
							swal.fire({title: 'Error',text:"Something went wrong. Please try again."});
						}
					}
				});
			}
			
		});
}

function updateGroupSetting(g){

	var formdata = $('#groupSettingForm')[0];
	var finaldata  = new FormData(formdata);
	$.ajax({
		url: 'ajax.php?updateGroupSetting='+g,
		type: 'POST',
		processData: false,
		contentType: false,
		cache: false,
		data: finaldata,
        success : function(data) {
			$('#ergSetting').modal('hide');
			$('body').removeClass('modal-open');
			$('.modal-backdrop').remove();
			swal.fire({title: 'Success',text:"Group setting updated successfully."});
		}
	});
}

function enableDisableGroupOverlay(){
	var show_group_overlay = 0;
	if ($('#show_group_overlay').is(":checked")){
		show_group_overlay = 0;
		$('#show_group_overlay').prop('checked', false);
	} else {
		show_group_overlay = 1;
		$('#show_group_overlay').prop('checked', true);
	}
	$.ajax({
		url: 'ajax.php?enableDisableGroupOverlay=1',
		type: "POST",
		data: {'show_group_overlay':show_group_overlay},
		success: function(data) {
			swal.fire({title: 'Success',text:"Updated successfully"});
		}
	});
}

function selectRegion(g){
	$.ajax({
		url: 'ajax.php?getRegionsForGroup='+g,
		type: "GET",
        success : function(data) {
			if (data == 0){
				swal.fire({title: 'Error',text:"This Group is not configured to use Regions, please update the group first."});
			} else {
				$('#replace').html(data);
				$('#add-chapter').modal('show');
			}						
		}
	});

	$('#add-chapter').on('shown.bs.modal', function (e) {     					 
		$('.close').focus();
	})
}

function submitSelectRegion(page) {
	var regionid = $("#selectedRegion").val();
	var groupid = $("#encodedId").val();

	if (regionid.length > 0 && groupid.length > 0){
		window.location.href = page+"?gid="+groupid+'&rid='+regionid;
	} else {
		swal.fire({title: 'Error',text:"Please select a region."});
	}
}



function updateMembersReassigned(g){
	$.ajax({
		url: 'ajax.php?updateMembersReassigned='+g,
		type: "POST",
		success : function(data) {
			swal.fire({title: 'Success',text:data +' members reassigned'});
		}
	});
}

function editGroupLandingPage(){
	$("#group_landing_page_title").hide();
	$("#group_landing_page_edit").hide();
	$("#group_landing_page").show();
	$("#group_landing_page_update").show();
}

function updateGroupLandingPage(){
	var finalData  = new FormData();
	var input =$("#group_landing_page").val();
	finalData.append("group_landing_page",input);
	$.ajax({
		url: 'ajax.php?updateGroupLandingPage=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			if (data){
				$("#group_landing_page_title").html('<strong>'+data+'</strong>');
				$("#group_landing_page_title").show();
				$("#group_landing_page_edit").show();
				$("#group_landing_page").hide();
				$("#group_landing_page_update").hide();
			} else {
				swal.fire({title: 'Error',text:'Data not updated. Please try again.'});
			}
		}
	});
}

function deleteExpenseType(i){
	$.ajax({
		url: 'ajax.php?deleteExpenseType='+i,
        type: "POST",
		data: {},
		processData: false,
		contentType: false,
		cache: false,
        success : function(data) {
			jQuery("#ed"+i).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
		}
	});
}

function openSubExpenseItems(i){
	$.ajax({
		url: 'ajax.php?openSubExpenseItems='+i,
        type: "GET",
		data: {},
	    success : function(data) {
			$('#expenses_sub_items').html(data);
			$('#expenses_sub_item_modal').modal('show');
		}
	});
}

function setBudgetYear(y){
	$.ajax({
		url: 'ajax.php?setBudgetYear='+y,
        type: "POST",
		data: {},
	    success : function(data) {
			window.location.reload();
		}
	});
}

function updateYearlyBudget(y){
	$.ajax({
		url: 'ajax.php?updateYearlyBudget='+y,
        type: "GET",
		data: {},
	    success : function(data) {
			$('#yearlyBudget').html(data);
			$('#yearlyBudgetModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function updateGroupChapterBudget(j,c,gid, chp){
	var i = j+'_'+c;
	var amt = $("#chapterbudgetamt_"+i).val();
	var year = $("#year").val();
	var notifyleads = 0;
	if ( amt == '' && amt == 0 ){
		$("#chapterbudgetamt_"+i).focus();
		$("#chapterbudgetamt_"+i).css({'background-color' : '#eee'});
		swal.fire({title: 'Error!',text:"Amount cannot be empty!"});
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
				var finalData  = new FormData();
				finalData.append("amount",amt);
				finalData.append("groupid",gid);
				finalData.append("chapterid",chp);
				finalData.append("year",year);
				finalData.append("notifyleads",notifyleads);

				$.ajax({
					url: 'ajax.php?updateGroupChapterBudget=1',
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
							amt = parseFloat(amt).toFixed(2);
							$("#remained-bgt-"+j).html(remaining_budget);
							$("#allocated-bgt-"+j).html(allocated_budget);
							$('.disable-update-button').prop('disabled', true);
							$("#hidechaptermesage_"+i).show();
							$("#chapterbudgetamt_"+i).val(amt);
							$("#chapterbudgetamt_"+i+"-btn").hide();
							$("#chapterbudgetamt_"+i).css({'background-color' : '#63c375'});
							setTimeout(function(){
								$("#hidechaptermesage_"+i).hide();
								$("#chapterbudgetamt_"+i+"-btn").show();
								$("#chapterbudgetamt_"+i).css({'background-color' : '#fff'});
							}, 1000);
						} else {
							$("#chapterbudgetamt"+i).focus();
							$("#chapterbudgetamt"+i).css({'background-color' : '#f59191'});
							swal.fire({title: 'Error!',text:retVal.errorMessage});
						}
					}
				});
			}
		});
	}
}

function deleteGroupIcon(g){
	let groupicon = $("input[name=groupicon]").val();
		var formdata = $('#newGroupForm')[0];
		var finaldata  = new FormData(formdata);
		$.ajax({
			url: 'ajax.php?deleteGroupIcon='+g,
			type: 'POST',
			enctype: 'multipart/form-data',
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success: function(data) {
				if (data =='1'){
					swal.fire({title: 'Success',text:'Logo removed successfully!'});
					$('#groupicon').val('');
					$('#group_icon_db').hide();
					$('#delete-logo-btn2').hide();
				} else {
					swal.fire({title: 'Error!',text:data});
				}
			}
		});
}

function deleteSliderPhoto(g){
	let groupicon = $("input[name=groupicon]").val();
	var formdata = $('#newGroupForm')[0];
	var finaldata  = new FormData(formdata);
	$.ajax({
		url: 'ajax.php?deleteSliderPhoto='+g,
		type: 'POST',
		enctype: 'multipart/form-data',
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			if (data =='1'){
				swal.fire({title: 'Success',text:'Tile Background removed successfully!'});
				$('#sliderphoto').val('');
				$('#tile_background_db').hide();
				$('#delete-tile-btn2').hide();
			} else {
				swal.fire({title: 'Error!',text:data});
			}
		}
	});
}

function deleteCoverPhoto(g){
	let groupicon = $("input[name=groupicon]").val();
	var formdata = $('#newGroupForm')[0];
	var finaldata  = new FormData(formdata);
	$.ajax({
		url: 'ajax.php?deleteCoverPhoto='+g,
		type: 'POST',
		enctype: 'multipart/form-data',
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			if (data =='1'){
				swal.fire({title: 'Success',text:'Cover Photo Removed Successfully!'});
				$('#Image').val('');
				$('#cover_photo_db').hide();
				$('#delete-cover-btn2').hide();
			} else {
				swal.fire({title: 'Error!',text:data});
			}
		}
	});
}

function deleteAppSliderPhoto(g){
	let groupicon = $("input[name=groupicon]").val();
	let formdata = $('#newGroupForm')[0];
	let finaldata  = new FormData(formdata);
	$.ajax({
		url: 'ajax.php?deleteAppSliderPhoto='+g,
		type: 'POST',
		enctype: 'multipart/form-data',
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			if (data =='1'){
				swal.fire({title:'Success',text:'Application Tile removed successfully!'});
				$('#app_sliderphoto').val('');
				$('#app_sliderphoto_db').hide();
				$('#delete_app_sliderphoto').hide();
			} else {
				swal.fire({title: 'Error!',text:data});
			}
		}
	});
}

function deleteAppCoverPhoto(g){
	let groupicon = $("input[name=groupicon]").val();
	var formdata = $('#newGroupForm')[0];
	var finaldata  = new FormData(formdata);
	$.ajax({
		url: 'ajax.php?deleteAppCoverPhoto='+g,
		type: 'POST',
		enctype: 'multipart/form-data',
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			if (data =='1'){
				swal.fire({title:'Success',text:'Application Group cover photo removed successfully!'});
				$('#app_coverphoto').val('');
				$('#app_coverphoto_db').hide();
				$('#delete_app_coverphoto').hide();
			} else {
				swal.fire({title: 'Error!',text:data});
			}
		}
	});
}

function createNewGroup(g, currentCategoryId){
	let groupname_short = $("input[name=groupname_short]").val();
	let groupname = $("input[name=groupname]").val();
	let groupicon = $("input[name=groupicon]").val();
	let coverphoto = $("input[name=coverphoto]").val();
	let sliderphoto = $("input[name=sliderphoto]").val();
	let from_email_label = $("#from_email_label").val();
	let replyto_email = $("#replyto_email").val();
	let app_sliderphoto = $("input[name=app_sliderphoto]").val();
	let app_coverphoto = $("input[name=app_coverphoto]").val();

	$("#Image").prop('disabled', true);
	$("#sliderphoto").prop('disabled', true);
	$("#groupicon").prop('disabled', true);
	$("#app_sliderphoto").prop('disabled', true);
	$("input[name=groupicon]").prop('disabled', true);
	$("input[name=coverphoto]").prop('disabled', true);
	$("input[name=sliderphoto]").prop('disabled', true);
	$("input[name=app_sliderphoto]").prop('disabled', true);
	$("input[name=app_coverphoto]").prop('disabled', true);

	if(!groupname){
		swal.fire({title: 'Alert',text:'Name is required!'});
	}  else if (!groupname_short){
		swal.fire({title: 'Alert',text:'Short Name is required!'});
	}  else if(from_email_label && validateEmailFromName(from_email_label) == -1){
		swal.fire({title: 'Error',text:'Email address is not allowed in the From Email Label for security reasons'});
	}  else if(replyto_email && validateEmail(replyto_email)){
		swal.fire({title: 'Error',text:validateEmail(replyto_email)});
	}  else {

		let formdata = $('#newGroupForm')[0];
		let finaldata  = new FormData(formdata);
	
		if (groupicon){
			let groupicon_media  = JSON.parse(groupicon);
			let groupicon_base64 = groupicon_media.output.image;
			let groupicon_type   = groupicon_media.output.type;
			let groupicon_block  = groupicon_base64.split(";");

			// get the real base64 content of the file
			let groupicon_realData = groupicon_block[1].split(",")[1];

			// Convert to blob
			let groupicon_blob = b64toBlob(groupicon_realData, groupicon_type);

			let groupiconFile = new File([groupicon_blob],groupicon_media.input.name,{ type: groupicon_type });
			finaldata.append('groupicon',groupiconFile);
		}
		if (coverphoto){
      let coverphotoFile = getSlimJsFile('#cover_img');
			finaldata.append('coverphoto',coverphotoFile);
		}
		if (sliderphoto){
      let sliderphotoFile = getSlimJsFile('#tile_background');
			finaldata.append('sliderphoto',sliderphotoFile);
		}

		if (app_coverphoto){
			let app_coverphoto_media  = JSON.parse(app_coverphoto);
			let app_coverphoto_base64 = app_coverphoto_media.output.image;
			let app_coverphoto_type   = app_coverphoto_media.output.type;
			let app_coverphoto_block  = app_coverphoto_base64.split(";");

			// get the real base64 content of the file
			let app_coverphoto_realData = app_coverphoto_block[1].split(",")[1];

			// Convert to blob
			let app_coverphoto_blob = b64toBlob(app_coverphoto_realData, app_coverphoto_type);

			let app_coverphotoFile = new File([app_coverphoto_blob],app_coverphoto_media.input.name,{ type: app_coverphoto_type });
			finaldata.append('app_coverphoto',app_coverphotoFile);
		}

		if (app_sliderphoto){
			let app_sliderphoto_media  = JSON.parse(app_sliderphoto);
			let app_sliderphoto_base64 = app_sliderphoto_media.output.image;
			let app_sliderphoto_type   = app_sliderphoto_media.output.type;
			let app_sliderphoto_block  = app_sliderphoto_base64.split(";");

			// get the real base64 content of the file
			let app_sliderphoto_realData = app_sliderphoto_block[1].split(",")[1];

			// Convert to blob
			let app_sliderphoto_blob = b64toBlob(app_sliderphoto_realData, app_sliderphoto_type);

			let app_sliderphotoFile = new File([app_sliderphoto_blob],app_sliderphoto_media.input.name,{ type: app_sliderphoto_type });
			finaldata.append('app_sliderphoto',app_sliderphotoFile);
		}

		$.ajax({
			url: 'ajax.php?createNewGroup='+g,
			type: 'POST',
			enctype: 'multipart/form-data',
			data: finaldata,
			processData: false,
			contentType: false,
			cache: false,
			success: function(data) {
				try {
					let jsonData = JSON.parse(data);
					swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
						if (jsonData.status == 1){
							window.location.href = 'group?filter='+currentCategoryId;
						}
					});
				} catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
			}
		});
	}
}

function getSlimJsFile(selector)
{
  var element, max_width, max_height, slim_element, blob, slim_data_json, photo_base64, photo_block, photo_realData;

  element = $(selector);

  [max_width, max_height] = element.data('size').split(',');

  slim_element = Slim.find(element[0]);

  if (
    slim_element.data.input.width == max_width
    && slim_element.data.input.height == max_height
  ) {
    blob = slim_element.data.input.file;
  } else {
    slim_data_json = element.find('input[type="hidden"]').val();
    slim_data_json = JSON.parse(slim_data_json);

    photo_base64 = slim_data_json.output.image;
    photo_block = photo_base64.split(';');
    photo_realData = photo_block[1].split(',')[1];

    blob = b64toBlob(photo_realData, slim_element.data.input.type);
  }

  return new File([blob], slim_element.data.input.name,{ type: slim_element.data.input.type });
}

function addUpdateNewDisclaimer(i,update_consent_version){
	$(document).off('focusin.modal');
	var formdata = $('#newDisclaimer')[0];
	var finaldata  = new FormData(formdata);
	finaldata.append("disclaimerid",i);
	finaldata.append("update_consent_version",update_consent_version);
	$.ajax({
		url: 'ajax.php?addUpdateNewDisclaimer=1',
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
						window.location = 'disclaimers';
					}
				});
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false}); }
		}
	});
	
}



function b64toBlob(b64Data, contentType, sliceSize) {

	contentType = contentType || '';
	sliceSize = sliceSize || 512;

	var byteCharacters = atob(b64Data);
	var byteArrays = [];

	for (var offset = 0; offset < byteCharacters.length; offset += sliceSize) {
		var slice = byteCharacters.slice(offset, offset + sliceSize);

		var byteNumbers = new Array(slice.length);
		for (var i = 0; i < slice.length; i++) {
			byteNumbers[i] = slice.charCodeAt(i);
		}

		var byteArray = new Uint8Array(byteNumbers);

		byteArrays.push(byteArray);
	}

	return new Blob(byteArrays, {type: contentType});
}

function changeGroupChannelStatus(gid,cid,status,element){
	$.ajax({
		url: 'ajax.php?change_group_channel_status='+cid,
		type: "POST",
		data: {'gid':gid,'status':status},

		success : function(data) {
			if(status==1){
				$(element).parent('td').parent('tr').css({'background-color':'#ffffff !important'});
			}else if(status==100){
				$(element).parent('td').parent('tr').css({'background-color':'#fde1e1 !important'});
			}else{
				$(element).parent('td').parent('tr').css({'background-color':'#ffffce !important'});
			}
			$(element).parent('td').html(data);
			jQuery(".deluser").popConfirm({content: ''});
		}
	});
}

function changeIntegrationStatus(gid,intgrationid,status){
	$.ajax({
		url: 'ajax.php?change_integration_status=1',
		type: "POST",
		data: {'gid':gid,'intgrationid':intgrationid,'status':status},

		success : function(data) {				
			location.reload();
		}
	});
}

function deleteIntegration(gid,intgrationid){
	$.ajax({
		url: 'ajax.php?delete_integration=1',
		type: "POST",
		data: {'gid':gid,'intgrationid':intgrationid},

		success : function(data) {				
			location.reload();
		}
	});
}

function changeTabStatus(gid,tid,status){
	$.ajax({
		url: 'ajax.php?change_tab_status=1',
		type: "POST",
		data: {'gid':gid,'tid':tid,'status':status},

		success : function(data) {				
			location.reload();
		}
	});
}
function deleteTab(gid,tid){
	$.ajax({
		url: 'ajax.php?delete_tab=1',
		type: "POST",
		data: {'gid':gid,'tid':tid},

		success : function(data) {				
			location.reload();
		}
	});
}

function deleteChannelLead(i,u,c,g){
	$.ajax({
		type: "POST",
		url: "ajax.php?deleteChannelLead="+u,
		data: {'gid':g,'cid':c},
		success: function(data){
			if(data == 1){
				jQuery("#"+u).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
			}else{
				swal.fire({title: 'Error',text:data});
			}
		}
	});
}

function activateDeactivateEventCustomField(f,s){
	$.ajax({
		type: "POST",
		url: "ajax.php?activateDeactivateEventCustomField=1",
		data: {'custom_field_id':f,'status':s},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){				
						location.reload();
					}
				});
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false}); }
		}
	});
}
function deleteEventCustomField(id, topictype)
{
	$.ajax({
		type: "POST",
		url: "ajax.php?deleteEventCustomField=1",
		data: {
			custom_field_id: id,
			topictype
		},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					if (jsonData.status == 1){				
						location.reload();
					}
				});
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false}); }
		}
	});
}

// Delete Disclaimer
function deleteDisclaimer(i){

	$.ajax({
		type: "POST",
		url: 'ajax.php?deleteDisclaimer='+i,	
		data: {},	
		success: function(data){
			if (data == 1){
				swal.fire({title: 'Success',text:"Deleted successfully."}).then(function(result) {
					location.reload();
				});
			} else {
				swal.fire({title: 'Error',text:"Something went wrong. Please try again."});
			}
		}
	});	
}// Disclaimer
// Active Deactive Disclaimer
function activateDeactiveDisclaimer(i,s){
	$.ajax({
		type: "POST",
		url: 'ajax.php?activateDeactiveDisclaimer='+i,	
		data: {'status':s},
		success: function(data){
			try {
				let jsonData = JSON.parse(data);				
				swal.fire({title: jsonData.title,text:jsonData.message,allowOutsideClick:false}).then(function(result) {
					location.reload();
				});
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error.",allowOutsideClick:false}); }
		
		}
	});	
}// Disclaimer

function deleteChargeCode(i){
	$.ajax({
		url: 'ajax.php?deleteChargeCode='+i,
        type: "POST",
		data: {},
		processData: false,
		contentType: false,
		cache: false,
        success : function(data) {
			jQuery("#ed"+i).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
		}
	});
}

function deleteFooterLink(i, deleteStatus){
	$.ajax({
		url: 'ajax.php?deleteFooterLink='+i,
        type: "POST",
		data: {'deleteStatus':deleteStatus},
        success : function(data) {
			location.reload();
		}
	});
}
function activateDeactiveHotLink(i,s){
	$.ajax({
		url: 'ajax.php?activateDeactiveHotLink='+i,
        type: "POST",
		data: {'status':s},
        success : function(data) {
			location.reload();
		}
	});
}

function activateDeactiveFooterLink(i,s){
	$.ajax({
		url: 'ajax.php?activateDeactiveFooterLink='+i,
        type: "POST",
		data: {'status':s},
        success : function(data) {
			location.reload();
		}
	});
}

function filterByGroupCategory(v){
	window.location.href = "group?filter="+v
}

function updateChapterLeadsPriority(priority,gid,chapterid){
	var finalData  = new FormData();
	finalData.append("prioritylist",priority);
	finalData.append("gid",gid);
	finalData.append("chapterid",chapterid);
	$.ajax({
        url: 'ajax.php?updateChapterLeadsPriority=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {

		}
	});
}

function updateChannelLeadsPriority(priority,gid,channelid){
	var finalData  = new FormData();
	finalData.append("prioritylist",priority);
	finalData.append("gid",gid);
	finalData.append("channelid",channelid);
	$.ajax({
        url: 'ajax.php?updateChannelLeadsPriority=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {

		}
	});
}

function updateZoneEmailSetting(){
	let email_from_label = $("#email_from_label").val();
	if(email_from_label && validateEmailFromName(email_from_label) == -1){
		swal.fire({title: 'Error',text:'Email address is not allowed in the Default From Name for security reasons'});
	} else {
		var formdata = $('#email_setting_form')[0];
		var finalData  = new FormData(formdata);
		$.ajax({
			url: 'ajax.php?updateZoneEmailSetting=1',
			type: "POST",
			data: finalData,
			processData: false,
			contentType: false,
			cache: false,
			success: function(data) {
				if (data.search('Error') === 0) {
					swal.fire({title: 'Error', html: data});
				} else {
					let retval = parseInt(data);
					if (retval) {
						swal.fire({title: 'Success', text: 'Email setting updated successfully!'});
					} else {
						swal.fire({title: 'Error', text: 'Unable to update Email settings'});
					}
				}
			}
		});
	}
}

function viewSpeakerDetail(e,s){
	$.ajax({
		url: 'ajax.php?viewSpeakerDetail=1',
		type: "get",
		data: {eventid:e,speakerid:s},
		success : function(data) {
			$("#speakerDetail").html(data)
			$('#speakerDetailModal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$('.initial').initial({
				charCount: 2,
				textColor: '#ffffff',
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
function viewTopicApprovalDetail(e,s){
	$.ajax({
		url: 'ajax.php?viewTopicApprovalDetail=1',
		type: "get",
		data: {eventid:e,approvalid:s},
		success : function(data) {
			if(data == -1) {
				swal.fire({title: 'Error',text:'Topic not found ... it might have been deleted!'}).then(function(result){
					location.reload();
				});
			} else {
				$("#eventDetail").html(data)
				$('#eventDetailModal').modal({
					backdrop: 'static',
					keyboard: false
				});
				$('.initial').initial({
					charCount: 2,
					textColor: '#ffffff',
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
function selectApprovalDetailView(eventid,approvalid, topicType){
	var quickViewButtonText = 'Quick View';
    var newTabButtonText = 'Open in New Tab';
	Swal.fire({
        text: '<?= addslashes(gettext("Choose approval detail view :"));?>',
        html: "Choose approval detail view :" +
            "<br>" +
            '<button type="button" role="button" class="btn btn-sm btn-primary swal2-confirm modalView" style="margin:10px 5px; font-size:15px; padding: 8px 20px;">'+quickViewButtonText+'</button>' +
            '<button type="button" role="button" tabindex="0" class="btn btn-sm btn-primary swal2-confirm newTabView" style="margin:10px 5px; font-size:15px; padding: 8px 20px;">'+newTabButtonText+'</button>',
        showCancelButton: false,
        showConfirmButton: false,
        showCloseButton: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
        onBeforeOpen: () => {
            const modalView = document.querySelector('.modalView');
            const newTabView = document.querySelector('.newTabView');
            modalView.addEventListener('click', () => {
                Swal.close();
				viewApprovalDetail(eventid,approvalid, topicType);
                    
            });

            newTabView.addEventListener('click', () => {
                Swal.close();
                window.open(`view_approval_data.php?topicTypeId=${eventid}&approvalid=${approvalid}&topicType=${topicType}`,'_blank');
            });
        }
        }).then(() => {
            Swal.close();
        })
}
function viewApprovalDetail(e,s,topicType){
	$.ajax({
		url: 'ajax.php?viewApprovalDetail=1',
		type: "get",
		data: {topicTypeId:e,approvalid:s,topicType:topicType},
		success : function(data) {
			if(data == -1) {
				swal.fire({title: 'Error',text:'Topic not found ... it might have been deleted!'}).then(function(result){
					location.reload();
				});
			} else {
				$("#eventDetail").html(data)
				$('#eventDetailModal').modal({
					backdrop: 'static',
					keyboard: false
				});
				$('.initial').initial({
					charCount: 2,
					textColor: '#ffffff',
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
function editCalendarTitle(){
	$("#calendar_page_banner_title_view").hide();
	$("#calendar_page_banner_title_edit").hide();
	$("#calendar_page_banner_title").show();
	$("#calendar_page_banner_title_update").show();
}

function updateCalendarTitle(){
	var finalData  = new FormData();
	var val =$("#calendar_page_banner_title").val();
	finalData.append("calendar_page_banner_title",val);
	$.ajax({
		url: 'ajax.php?updateCalendarTitle=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			$("#calendar_page_banner_title_view").html("<strong>" + data + "</strong>");
			$("#calendar_page_banner_title_view").show();
			$("#calendar_page_banner_title_edit").show();
			$("#calendar_page_banner_title").hide();
			$("#calendar_page_banner_title_update").hide();
		}
	});
}

function editAdminContentTitle(){
	$("#admin_content_page_title_view").hide();
	$("#admin_content_page_title_edit").hide();
	$("#admin_content_page_title").show();
	$("#admin_content_page_title_update").show();
}

function updateAdminContentTitle(){
	var finalData  = new FormData();
	var val =$("#admin_content_page_title").val();
	finalData.append("admin_content_page_title",val);
	$.ajax({
		url: 'ajax.php?updateAdminContentTitle=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			$("#admin_content_page_title_view").html("<strong>" + data + "</strong>");
			$("#admin_content_page_title_view").show();
			$("#admin_content_page_title_edit").show();
			$("#admin_content_page_title").hide();
			$("#admin_content_page_title_update").hide();
		}
	});
}

function updateZoneHotlinkPlacement(){
	var finalData  = new FormData();
	var val = $("input[name='hot_link_location']:checked").val();
	finalData.append("hot_link_location",val);
	$.ajax({
		url: 'ajax.php?updateZoneHotlinkPlacement=1',
		type: "POST",
		data: finalData,
		processData: false,
		contentType: false,
		cache: false,
		success: function(data) {
			$("#error_hot_link_location").html("<strong>" + data + "</strong>");
			$("#error_hot_link_location").show();
			$('#error_hot_link_location').delay(5000).fadeOut('slow');
		}
	});
}

function activateDeactivateEventSpeakerField(i,a){
	$.ajax({
		url: 'ajax.php?activateDeactivateEventSpeakerField=1',
		type: "POST",
		data: {'id':i,'action':a},
		success: function(data) {
			swal.fire({title: 'Success',text:'Record updated successfully'}).then(function(result) {
				location.reload();
			});	
		}
	});
}

function getTeamRolesList(g){
	$.ajax({
		url: 'ajax.php?getTeamRolesList=1',
		type: "GET",
		data: {'groupid':g},
		success: function(data) {
			$(".deluser").popConfirm({content: ''});
			$('#dynamic_content').html(data);	
		}
	});
}


function getCompanyBudgetYears(){
	$.ajax({
		url: 'ajax.php?getCompanyBudgetYears=1',
		type: "GET",
		data: {},
		success: function(data) {
			$('#yearlyBudget').html(data);
			$('#budgetYearsModal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$(".deluser").popConfirm({content: ''});
		}
	});
}

function openNewBudetYearModal(i){
	$.ajax({
		url: 'ajax.php?openNewBudetYearModal=1',
		type: "GET",
		data: {'budget_year_id':i},
		success: function(data) {
			$('#budgetYearsModal').modal('hide');
			$('body').removeClass('modal-open');
			$('.modal-backdrop').remove();

			$('#yearlyBudget').html(data);
			$('#newBudgetYearsModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}
function saveCompanyBudgetYear(i){
	var budget_year_title = $("#budget_year_title").val();
	var budget_year_start_date = $("#start_date").val();
	var budget_year_end_date = $("#end_date").val();
	if (budget_year_title && budget_year_start_date && budget_year_end_date){
		$.ajax({
			url: 'ajax.php?saveCompanyBudgetYear=1',
			type: "POST",
			data: {'budget_year_id':i,'budget_year_title':budget_year_title,'budget_year_start_date':budget_year_start_date,'budget_year_end_date':budget_year_end_date},
			success: function(data) {
				if (data && data > 0){
					$('#newBudgetYearsModal').modal('hide');
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					swal.fire({title: 'Success',text:'Budget year updated successfully'}).then(function(result) {
						if (data == 2){
							location.reload();
						} else {
							getCompanyBudgetYears();
						}
					});
				} else if (data && data == -1) {
					swal.fire({title: 'Error',text:'The end date should be greater than the start date. Please update the start or end date try again!'})
				} else if (data && data == -2) {
					swal.fire({title: 'Error',text:'The start or end date overlaps with another budget year. Please update the start or end date and try again!'})
				} else {
					swal.fire({title: 'Error',text:'Something went wrong. Please try again!'})
				}	
			}
		});
	} else {
		swal.fire({title: 'Error',text:'All fields are required!'})
	}
}
function deleteCompanyBudgetYear(i){
	$.ajax({
		url: 'ajax.php?deleteCompanyBudgetYear=1',
		type: "POST",
		data: {'budget_year_id':i},
		success: function(data) {
			if(data==0){
				swal.fire({title: 'Error',text:'Unable to delete budget year due to dependent assigned budget, expenses or funding data. Please contact Teleskope support to delete this budget year.'}).then(function (result) {
					$('#newbudget_modal').focus();
				});
			}else{
				swal.fire({title: 'Success',text:'Budget year deleted successfully.'}).then(function (result) {
					$('#newbudget_modal').focus();
				});
				jQuery("#"+i).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
			}
		}
	});
}

function convertToCSV(objArray) {
    var array = typeof objArray != 'object' ? JSON.parse(objArray) : objArray;
    var str = '';

    for (var i = 0; i < array.length; i++) {
        var line = '';
        for (var index in array[i]) {
            if (line != '') line += ','

            line += array[i][index];
        }

        str += line + '\r\n';
    }

    return str;
}

function exportCSVFile(headers, items, fileTitle) {
    if (headers) {
        items.unshift(headers);
    }

    // Convert Object to JSON
    var jsonObject = JSON.stringify(items);

    var csv = convertToCSV(jsonObject);

    var exportedFilenmae = fileTitle + '.csv' || 'export.csv';

    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    if (navigator.msSaveBlob) { // IE 10+
        navigator.msSaveBlob(blob, exportedFilenmae);
    } else {
        var link = document.createElement("a");
        if (link.download !== undefined) { // feature detection
            // Browsers that support HTML5 download attribute
            var url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", exportedFilenmae);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);

			setTimeout(function(){
				link.click();
				document.body.removeChild(link);
			}, 500);

        }
    }
}

function convertArraytoObject(arr) {
	var rv = {};
	for (var i = 0; i < arr.length; ++i)
		rv[i] = arr[i];
	return rv;
}

function initChapterPermanentDeleteConfirmation(g,c){
	$.ajax({
		url: 'ajax.php?initChapterPermanentDeleteConfirmation=1',
		type: "GET",
		data: {'groupid':g,'chapterid':c},
		success: function(data) {
			$('#loadAnyModal').html(data);
			$('#confirmationModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}
function initChannelPermanentDeleteConfirmation(g,c){
	$.ajax({
		url: 'ajax.php?initChannelPermanentDeleteConfirmation=1',
		type: "GET",
		data: {'groupid':g,'channelid':c},
		success: function(data) {
			$('#loadAnyModal').html(data);
			$('#confirmationModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});

	$('#confirmationModal').on('shown.bs.modal', function () {     					 
		$('.close').focus();
	})
}

function deleteChapterPermanently(g,c,ac){
	var input = $("#delete_permanently").val().trim();
	if(input == 'permanently delete'){
		$.ajax({
			url: 'ajax.php?deleteChapterPermanently=1&audit='+g+'-'+c,
			type: "POST",
			data: {'groupid':g,'chapterid':c,'audit_code':ac},
			success: function(data) {
				$('#confirmationModal').modal('hide');
				$('body').removeClass('modal-open');
				$('.modal-backdrop').remove();
				swal.fire({title: 'Success!',text:"Deleted successfully."})
				.then(function(result) {
					location.reload();
				});
			}
		});
	} else {
		swal.fire({title: 'Error!',text:"Please type 'permanently delete' to confirm."});
	}
}

function deleteChannelPermanently(g,c,ac){
	var input = $("#delete_permanently").val().trim();
	if(input == 'permanently delete'){
		$.ajax({
			url: 'ajax.php?deleteChannelPermanently=1&audit='+g+'-'+c,
			type: "POST",
			data: {'groupid':g,'channelid':c,'audit_code':ac},
			success: function(data) {
				$('#confirmationModal').modal('hide');
				$('body').removeClass('modal-open');
				$('.modal-backdrop').remove();
				swal.fire({title: 'Success!',text:"Deleted successfully."})
				.then(function(result) {
					location.reload();
				});
			}
		});

	} else {
		swal.fire({title: 'Error!',text:"Please type 'permanently delete' to confirm."});
	}
}

function checkIfConfirmComplete() {
	let input = $("#delete_permanently").val().trim();
	if(input == 'permanently delete'){
		$("#confirm_and_delete_btn").prop('disabled',false);
	}
}

function checkUncheckAllCheckBoxes (cls,st) {
	$('.'+cls).prop("checked",st);
	$('#submit_action_button').prop('disabled',$('input.metaFields:checked').length == 0);
}

function filterMetaFields(action, excludeMetaFileds){
	$('input.metaFields').prop('checked', true);
	$('#submit_action_button').prop('disabled',false);
	let jsonMetaFields = JSON.parse(excludeMetaFileds);
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



function manageSpeakerApprovalConfiguration(){

	$.ajax({
		url: 'ajax.php?manageSpeakerApprovalConfiguration=1',
		type: "GET",
        success : function(data) {
			$("#loadAnyModal").html(data);
			$('#manageSpeakerApprovalConfigModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}
function eventsReportModal(e){
		$.ajax({
			url: 'ajax.php?downloadEventsReport='+e,
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
function loginReportModal(e){
	$.ajax({
		url: 'ajax.php?loginReportsModal='+e,
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

function usersReportModal(e){
	$.ajax({
		url: 'ajax.php?downloadUsersReport='+e,
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
function zoneUsersModal(){
	$.ajax({
		url: 'ajax.php?downloadZoneUsersReportModal=1',
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
function budgetReportsModal(e){
	$.ajax({
		url: 'ajax.php?budgetReportsModal='+e,
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
function expenseReportsModal(){
	$.ajax({
		url: 'ajax.php?expenseReportsModal=1',
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

function userReportsGraphModal(e){
	$.ajax({
		url: 'ajax.php?usersGraphReport='+e,
		type: "GET",
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#theGraphModal').modal({
				backdrop: false,
				keyboard: false
		});
		}
	});
}
function eventsReportGraphModal(e){
	$.ajax({
		url: 'ajax.php?eventsGraphReport='+e,
		type: "GET",
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#theGraphModal').modal({
				backdrop: false,
				keyboard: false
		});
		}
	});
}
function eventsRSVPReport(e){
	$.ajax({
		url: 'ajax.php?eventsRSVPReport='+e,
		type: "GET",
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#theGraphModal').modal({
				backdrop: false,
				keyboard: false
		});
		}
	});
}

function expenseGraphReportModal(e){
	$.ajax({
		url: 'ajax.php?expenseGraphReportModal='+e,
		type: "GET",
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#theGraphModal').modal({
				backdrop: false,
				keyboard: false
		});
		}
	});
}
function budgetGraphReportModal(e){
	$.ajax({
		url: 'ajax.php?budgetGraphReportModal='+e,
		type: "GET",
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#theGraphModal').modal({
				backdrop: false,
				keyboard: false
		});
		}
	});
}
function loginReportGraphModal(e){
	$.ajax({
		url: 'ajax.php?loginReportGraphModal='+e,
		type: "GET",
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#theGraphModal').modal({
				backdrop: false,
				keyboard: false
		});
		}
	});
}
function directMailsReportModal() {
	$.ajax({
		url: 'ajax.php?directMailsReportModal=1',
		type: 'GET',
		success : function(data) {
		$('#loadAnyModal')
			.html(data)
			.find('.modal')
			.modal();
		}
	});
	}
function getTeamsReportOptions(){
	$.ajax({
		url: 'ajax.php?getTeamsReportOptions=1',
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
function getTeamMembersReportOptions(){
	$.ajax({
		url: 'ajax.php?getTeamMembersReportOptions=1',
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
function getTeamsFeedbackReportOptions(){
	$.ajax({
		url: 'ajax.php?getTeamsFeedbackReportOptions',
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
function surveyReportModal(e){
	$.ajax({
		url: 'ajax.php?downloadSurveysReport='+e,
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
function announcementReportModal(e){
	$.ajax({
		url: 'ajax.php?downloadAnnouncementReport='+e,
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
function newslettersReportModal(e){
	$.ajax({
		url: 'ajax.php?downloadNewsletterReport='+e,
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

function getTeamsJoinRequestSurveyReportOptions(g){
	$.ajax({
		url: 'ajax.php?getTeamsJoinRequestSurveyReportOptions=1',
		type: 'GET',
		data: {groupid:g},
		success : function(data) {
			$('#reportTeamMemberJoinSurveyResponseDownloadOptions').html(data);
			$('#reportTeamMemberJoinSurveyResponseDownloadOptions').show("slow");
		}
	});
}

function getTeamRegistrationReportOptions() {
	$.ajax({
		url: 'ajax.php?getTeamRegistrationReportOptions=1',
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

function deleteTeamJoinRequestSurveyData(g){
	$.ajax({
		url: 'ajax.php?deleteTeamJoinRequestSurveyData=1',
		type: "GET",
		data: {'groupid':g},
		success: function(data) {
			$('#deleteSurveyDataConfirmationModal').modal('hide');
			$('body').removeClass('modal-open');
			$('.modal-backdrop').remove();
			swal.fire({title: 'Success',text:"Data deleted successfully"}).then(function(result) {
				location.reload();
            });
			

		}
	});
}

function manageBudgetCurrenciesModal(i){
	this.closeAllActiveModal();
	$.ajax({
		url: 'ajax.php?manageBudgetCurrenciesModal=1',
		type: "GET",
		data: {'budget_year_id':i},
		success: function(data) {
			$('#yearlyBudget').html(data);
			$('#manage_budget_cureencies_modal').modal({
				backdrop: 'static',
				keyboard: false
			});

			$(".deluser").popConfirm({content: ''});
		}
	});
}

function addUpdateBudgerCurrencyModal(i,c){
	this.closeAllActiveModal();
	$.ajax({
		url: 'ajax.php?addUpdateBudgerCurrencyModal=1',
		type: "GET",
		data: {'budget_year_id':i,'currency_id':c},
		success: function(data) {
			$('#yearlyBudget').html(data);
			$('#add_budget_cureencies_modal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});
}

function addUpdateBudgetForeignCurrency(i,c){
	let foreigncurrency = $("#foreigncurrency").val();
	let conversion_rate = $("#conversion_rate").val();
	if ( foreigncurrency == ""){
		swal.fire({title: 'Error!',text:"Please select a Foreign Currency"});
		return;
	}

	if (conversion_rate == "" || parseFloat(conversion_rate) <= 0 ){
		swal.fire({title: 'Error!',text:"Please input Conversion Rate value greater then 0 (zero)"});
		return;
	}
	
	$.ajax({
		url: 'ajax.php?addUpdateBudgetForeignCurrency=1',
		type: "POST",
		data: {'budget_year_id':i,'currency_id':c,'foreigncurrency':foreigncurrency,'conversion_rate':conversion_rate},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						this.closeAllActiveModal();
						manageBudgetCurrenciesModal(i);
					}
				});
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
		}
	});
}

function deleteBudgetCurrency(i,c){

	$.ajax({
		url: 'ajax.php?deleteBudgetCurrency=1',
		type: "POST",
		data: {'budget_year_id':i,'currency_id':c},
		success: function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						this.closeAllActiveModal();
						manageBudgetCurrenciesModal(i);
					}
				});
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
		}
	});
}



function showSweetAlert(title,message,messageIcon,footerMessage){
	messageIcon = (typeof messageIcon !== 'undefined') ? messageIcon : '';
	footerMessage = (typeof footerMessage !== 'undefined') ? footerMessage : '';
	var swalObj = {title: title,text:message};
	if (messageIcon){
		swalObj.icon = messageIcon;
	}
	if(footerMessage){
		swalObj.footer = footerMessage;
	}
	swal.fire(swalObj);
}
function assignUserForApprovalModal(topicTypeId,approvalId,a,topicType){
	$.ajax({
		url: 'ajax.php?assignUserForApprovalModal='+a,
		type: "GET",
		data: {'topicTypeId':topicTypeId,'approvalid':approvalId, 'topicType':topicType},
		success : function(data) {
			if (data == -1) {
				swal.fire({title: 'Error',text:'Topic not found ... it might have been deleted!'}).then(function(result){
					location.reload();
				});
			} else {
				$("#loadAnyModal").html(data);
				$('#assignTopicEventApproverModal').modal({
					backdrop: 'static',
					keyboard: false
				});
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
		customClass: {
			title: 'cancel-approval-swal2-title',
		},
		showCancelButton: true,
		confirmButtonText: 'Cancel Approval Request',
		cancelButtonText: 'Close'
	}).then((result)=> {
		if(result.isConfirmed){
			const note = result.value ? result.value.trim() : '';
			$.ajax({
				url: 'ajax.php?cancelApprovalRequest',
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
function updateApprovalStatusModal(approvalTopicTypeid,approvalid,approverNote,topicType){
	$.ajax({
		url: 'ajax.php?approvalTasksModal',
		type: "GET",
		data: {'approvalTopicTypeid':approvalTopicTypeid,'approvalid':approvalid,'approverNote':approverNote,'topicType':topicType},
		success : function(data) {
			if(data != -1){
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

			}else{
				swal.fire({title: 'Error',text:'This TopicType might be deleted!'}).then(function(result){ 
					location.reload();
					});
			}
		}

	});
}
function assignUserForSpeakerApprovelModal(e,s){
	$.ajax({
		url: 'ajax.php?assignUserForSpeakerApprovelModal=1',
		type: "GET",
		data: {'eventid':e,'speakerid':s},
		success: function(data) {
			$("#loadAnyModal").html(data);
			$('#assignEventSpeakerApproverModal').modal({
				backdrop: 'static',
				keyboard: false
			});
		}
	});

}

function assignSpeakerApprover(e,s){
	let approverid = $('#selected_approver_id').val();
	$.ajax({
		url: 'ajax.php?assignSpeakerApprover=1',
		type: "POST",
		data: {'eventid':e,'speakerid':s,'approverid':approverid},
		success: function(data) {
			swal.fire({title: 'Success',text:"User assigned successfully."}).then(function(result) {
				$("#loadAnyModal").html('');
				$('#assignEventSpeakerApproverModal').modal('hide');
				$('body').removeClass('modal-open');
				$('.modal-backdrop').remove();
				window.location.reload();
			});
		}
	});
}

function usersLogReportModal(e){
	$.ajax({
		url: 'ajax.php?usersLogReportModal='+e,
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

function connectUserModal(u){
	$.ajax({
		url: 'ajax.php?connectUserModal=1',
		type: "GET",
		data: {'userid':u},
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#connectUserModal').modal({
				backdrop: 'static',
				keyboard: false
		});
		}
	});
}

function manageConnectUserModal(u){
	$.ajax({
		url: 'ajax.php?manageConnectUserModal=1',
		type: "GET",
		data: {'userid':u},
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#manageConnectUserModal').modal({
				backdrop: 'static',
				keyboard: false
			});
			$(".confirm").popConfirm({content: ''});
		}
	});
}

function submitConnectUser(){

	let formdata = $('#connectUserForm')[0];
	let finaldata  = new FormData(formdata);
	$.ajax({
		url: 'ajax.php?submitConnectUser=1',
		type: "POST",
		data: finaldata,
		processData: false,
		contentType: false,
		cache: false,
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						window.location.reload();
					}
				});
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
		}
	});
}

function changeConnectUserEmail(u){
	let connectEmail = $('#connectEmail').val();
	$.ajax({
		url: 'ajax.php?changeConnectUserEmail=1',
		type: "POST",
		data: {'userid':u,'connectEmail':connectEmail},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						window.location.reload();
					}
				});
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
		}
	});
}

function resendConnectEmailVerification(u){
	$.ajax({
		url: 'ajax.php?resendConnectEmailVerification=1',
		type: "POST",
		data: {'userid':u},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						window.location.reload();
					}
				});
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
		}
	});
}


function deleteConnectUser(u){
	$.ajax({
		url: 'ajax.php?deleteConnectUser=1',
		type: "POST",
		data: {'userid':u},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						window.location.reload();
					}
				});
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
		}
	});
}

function blockUser(i,u){
	$.ajax({
		url: 'ajax.php?blockUser=1',
		type: "POST",
		data: {'userid':u},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						window.location.reload();
					}
				});
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
		}
	});
}
function unblockUser(i,u){
	$.ajax({
		url: 'ajax.php?unblockUser=1',
		type: "POST",
		data: {'userid':u},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						window.location.reload();
					}
				});
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
		}
	});
}

function deleteDynamicList(i){
	$.ajax({
		url: 'ajax.php?deleteDynamicList=1',
		type: "POST",
		data: {'listid':i},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message}).then(function(result) {
					if (jsonData.status == 1){
						window.location.reload();
					}
				});
			} catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
		}
	});
}
function getDynamicListUsers(i){
	$.ajax({
		url: 'ajax.php?getDynamicListUsers=1',
		type: "POST",
		data: {'listid':i},
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
				$('.initial').initial({
					charCount: 2,
					textColor: '#ffffff',
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
function getDynamicListRules(i){
	$.ajax({
		url: 'ajax.php?getDynamicListRules=1',
		type: "POST",
		data: {'listid':i},
        success : function(data) {
			try {
				let jsonData = JSON.parse(data);
				swal.fire({title: jsonData.title,text:jsonData.message});
			} catch(e) {
				$("#loadAnyModal").html(data);
				$('#dynamicListRules').modal({
					backdrop: 'static',
					keyboard: false
				});
				$('.initial').initial({
					charCount: 2,
					textColor: '#ffffff',
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

function showReportChartMetaFields(){
	$('#reportchartmetamodal').modal({
		backdrop: 'static',
		keyboard: false
	});
}

function createChart(id, label, data, title) {
	var chartData = {
		labels: JSON.parse(label),
		datasets: [
			{
				fill: false,
				borderColor: '#0077b5',
				backgroundColor: '#2196f3',
				borderWidth: 1,
				data: JSON.parse(data),
				label: title,
			}
		]
	};
	
	var chartCanvas = document.getElementById(id);
	var chart = new Chart(chartCanvas, {
		type: 'line',
		data: chartData,
		options: {
			plugins: {
				datalabels: {
					display: false,
				},
			},
			scales: {
				xAxes: {
					ticks: {
						// userCallback: function(label, index, labels) {
						//     if(label == labels[0] || label == labels.slice(-1)){
						//         return label;
						//     }
						// },
					},
				},
				yAxes: {
					ticks: {
						suggestedMin: 0,
						beginAtZero: true,
						callback: function(value, index, values) {
							if (value > 999) {
								return value / 1000 + 'k';
							}
							return value;
						}
					}
				}
			}
		}
	});
	
	// Create the button container
	var buttonContainer = document.createElement('div');
	buttonContainer.classList.add('dropdown', 'pull-right', 'download-stats');
	
	// Create the button toggle icon
	var buttonToggleIcon = document.createElement('i');
	buttonToggleIcon.classList.add('dropdown-toggle', 'fa', 'fa-ellipsis-v', 'fa-sm', 'no-after');	
	buttonToggleIcon.setAttribute('data-bs-toggle', 'dropdown');
	buttonToggleIcon.setAttribute('role', 'button'); // Add 'role' attribute with value 'button' for accessibility
	buttonToggleIcon.setAttribute('tabindex', '0'); // Add 'tabindex' attribute to make the element focusable
	buttonToggleIcon.setAttribute('aria-label', 'More option for '+title); 
	
	// For accessibility through tab and enter or space key
	buttonToggleIcon.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            buttonToggleIcon.click(); // Programmatically trigger a click event on the buttonToggleIcon         
            }
        });	
	
	buttonContainer.appendChild(buttonToggleIcon);


	
	// Create the dropdown menu
	var dropdownMenu = document.createElement('ul');
	dropdownMenu.classList.add('dropdown-menu', 'dropdown-menu-right');
	buttonContainer.appendChild(dropdownMenu);
	
	// Create the export to CSV button
	var exportButton = document.createElement('li');
	var exportLink = document.createElement('a');
	exportLink.setAttribute('id', id + '-export-csv');
	exportLink.setAttribute('class', 'export-csv-link');
	exportLink.setAttribute('href', 'javascript:void(0)');
	exportLink.innerText = 'Export to CSV';
	exportLink.addEventListener('click', function() {
		exportChartData(chartData);
	});
	exportButton.appendChild(exportLink);
	dropdownMenu.appendChild(exportButton);
	
	// Insert the button container before the chart canvas
	chartCanvas.parentNode.insertBefore(buttonContainer, chartCanvas);
}

function exportChartData(chartData) {
	var csvContent = "data:text/csv;charset=utf-8,";
	
	// Add labels row
	var labels = chartData.labels;
	csvContent += "Label," + labels.join(',') + '\n';
	
	// Add data rows
	var datasets = chartData.datasets;
	for (var i = 0; i < datasets.length; i++) {
		var data = datasets[i].data;
		csvContent += datasets[i].label + ',' + data.join(',') + '\n';
	}
	
	// Create a temporary link element to initiate the download
	var encodedUri = encodeURI(csvContent);
	var link = document.createElement("a");
	link.setAttribute("href", encodedUri);
	link.setAttribute("download", "chart_data.csv");
	
	// Trigger the link to start the download
	link.click();
}



// This global function needs to be there to report ajax errors.
$(document).ajaxError(function( event, jqxhr, settings, thrownError ) {
	var httpMessageObj = {
				'400':'Bad Request (Missing or malformed parameters)',
				'401':'Unauthorized (Please Sign in)',
				'403':'Forbidden (Access Denied)',
				'404':'Not found',
				'500':'Internal server error. Please try again.',
				'503':'Service Unavailable',
				'0':'Something went wrong while loading the page. Press "Ok" button to reload page again.',
			};
	var status = jqxhr.status
	showSweetAlert('Error',httpMessageObj[status],'error');
});
$(document).ajaxSend(function(elm, xhr, s){
	if (s.type == "POST") {
		xhr.setRequestHeader('x-csrf-token', teleskopeCsrfToken);
	}
});

function closeAllActiveModal(){
	$('.modal').modal('hide');
	$('body').removeClass('modal-open');
	$('.modal-backdrop').remove();
}

function formatAmountForDisplayJS(n,roundoff) {
	var parts = n.toFixed(roundoff).split(".");
	return parts[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + 
		(parts[1] ? "." + parts[1] : "");
}

function pointsTransactionsReportModal() {
  $.ajax({
    url: 'ajax_points.php?pointsTransactionsReportModal=1',
    type: 'GET',
    success : function(data) {
      $('#loadAnyModal')
        .html(data)
        .find('.modal')
        .modal();
    }
  });
}

function pointsBalanceReportModal() {
  $.ajax({
    url: 'ajax_points.php?pointsBalanceReportModal=1',
    type: 'GET',
    success : function(data) {
      $('#loadAnyModal')
        .html(data)
        .find('.modal')
        .modal();
    }
  });
}

function topicApprovalsReportModal(topicType) {
	$.ajax({
		url: 'ajax.php?topicApprovalsReportModal=1&topicType=' + topicType,
		type: 'GET',
		success: function (data) {
			$('#loadAnyModal')
				.html(data)
				.find('.modal')
				.modal();
		}
	});
}

function partnerOrganizationsReportModal(){
	$.ajax({
		url: 'ajax.php?partnerOrganizationsReportModal=1',
		type: "GET",
		success : function(data) {
			$("#loadAnyModal").html(data);
			$('#partnerOrganizationsReport').modal({
				backdrop: 'static',
				keyboard: false
		});
		}
	});
}
