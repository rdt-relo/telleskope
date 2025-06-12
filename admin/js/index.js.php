<?php

header('Content-Type: application/javascript');
if (!Env::IsLocalEnv()) {
  header('Cache-Control: public, max-age=604800');
}
?>

jQuery(document).ready(function(){

    jQuery("#sucessMessage").fadeOut(3000);

     jQuery('#adduser').submit(function(event){

         if(jQuery("#emailIdValue").val()==1 ){
             event.preventDefault();
             return false;
         }else{
            return true
         }

     });

	const url = new URL(document.location.href);
	var lastid = url.pathname.match(/[^\/]+$/)[0];
	var searchParams = url.searchParams;
    $('.myactive').removeClass('myactive');

		if (lastid == 'dashboardres'){

			//Adds it to the current element
			$("#sidebar_item_dashboard").addClass('myactive');
			$("#sidebar_item_dashboard_m").addClass('myactive');

		} else if (lastid == 'group' || lastid == 'addgroup') {
            $("#group_tab").removeClass('collapse');
            $("#group_tab_m").removeClass('collapse');
            $("#group_tab").addClass('in');
            $("#group_tab_m").addClass('in');
            $("#sidebar_item_group").addClass('myactive');
            $("#sidebar_item_group_m").addClass('myactive');

        } else if (lastid == 'group_category'){
            $("#group_tab").removeClass('collapse');
            $("#group_tab_m").removeClass('collapse');
            $("#group_tab").addClass('in');
            $("#group_tab_m").addClass('in');
            $("#sidebar_item_group_categories").addClass('myactive');
            $("#sidebar_item_group_categories_m").addClass('myactive');

        }else if (lastid == 'grouplead_type'){
            $("#group_tab").removeClass('collapse');
            $("#group_tab_m").removeClass('collapse');
            $("#group_tab").addClass('in');
            $("#group_tab_m").addClass('in');
            $("#sidebar_item_manage_roles_and_permissions").addClass('myactive');
            $("#sidebar_item_manage_roles_and_permissions_m").addClass('myactive');

        } else if (lastid == 'erg_reporting'){
            $("#reports_tab").removeClass('collapse');
            $("#reports_tab_m").removeClass('collapse');
            $("#reports_tab").addClass('in');
            $("#reports_tab_m").addClass('in');
            $("#sidebar_item_erg_reporting").addClass('myactive');
            $("#sidebar_item_erg_reporting_m").addClass('myactive');

        } else if (lastid == 'membership_reporting'){
            $("#reports_tab").removeClass('collapse');
            $("#reports_tab_m").removeClass('collapse');
            $("#reports_tab").addClass('in');
            $("#reports_tab_m").addClass('in');
            $("#sidebar_item_membership_reporting").addClass('myactive');
            $("#sidebar_item_membership_reporting_m").addClass('myactive');

        } else if (lastid == 'event_reporting'){
            $("#reports_tab").removeClass('collapse');
            $("#reports_tab_m").removeClass('collapse');
            $("#reports_tab").addClass('in');
            $("#reports_tab_m").addClass('in');
            $("#sidebar_item_event_reporting").addClass('myactive');
            $("#sidebar_item_event_reporting_m").addClass('myactive');

        } else if (lastid == 'budget_reporting'){
            $("#reports_tab").removeClass('collapse');
            $("#reports_tab_m").removeClass('collapse');
            $("#reports_tab").addClass('in');
            $("#reports_tab_m").addClass('in');
            $("#sidebar_item_budget_reporting").addClass('myactive');
            $("#sidebar_item_budget_reporting_m").addClass('myactive');

        } else if (lastid == 'usage_reports'){
            $("#reports_tab").removeClass('collapse');
            $("#reports_tab_m").removeClass('collapse');
            $("#reports_tab").addClass('in');
            $("#reports_tab_m").addClass('in');
            $("#sidebar_item_usage_reports").addClass('myactive');
            $("#sidebar_item_usage_reports_m").addClass('myactive');

        } else if (lastid == 'budget'){
            $("#budget_tab").removeClass('collapse');
            $("#budget_tab_m").removeClass('collapse');
            $("#budget_tab").addClass('in');
            $("#budget_tab_m").addClass('in');
            $("#sidebar_item_budget").addClass('myactive');
            $("#sidebar_item_budget_m").addClass('myactive');

        } else if (lastid == 'budget_charge_codes'){
            $("#budget_tab").removeClass('collapse');
            $("#budget_tab_m").removeClass('collapse');
            $("#budget_tab").addClass('in');
            $("#budget_tab_m").addClass('in');
            $("#sidebar_item_budget_charge_codes").addClass('myactive');
            $("#sidebar_item_budget_charge_codes_m").addClass('myactive');

        } else if (lastid == 'manage_expense_types') {
            $("#budget_tab").removeClass('collapse');
            $("#budget_tab_m").removeClass('collapse');
            $("#budget_tab").addClass('in');
            $("#budget_tab_m").addClass('in');
            $("#sidebar_item_manage_expense_types").addClass('myactive');
            $("#sidebar_item_manage_expense_types_m").addClass('myactive');

        } else if (lastid == 'event_type' || lastid == 'manage_event_type_volunteer'){
            $("#events_tab").removeClass('collapse');
            $("#events_tab_m").removeClass('collapse');
            $("#events_tab").addClass('in');
            $("#events_tab_m").addClass('in');
                $("#sidebar_item_event_type").addClass('myactive');
                $("#sidebar_item_event_type_m").addClass('myactive');

        } else if (lastid == 'event_custom_fields' || lastid == 'new_event_custom_field'){
            var topictype = searchParams.get('topictype') ?? 'EVT';

            if (topictype === 'EVT') {
            $("#events_tab").removeClass('collapse');
            $("#events_tab_m").removeClass('collapse');
            $("#events_tab").addClass('in');
            $("#events_tab_m").addClass('in');
            $("#sidebar_item_event_custom_fields").addClass('myactive');
            $("#sidebar_item_event_custom_fields_m").addClass('myactive');
            } else if (topictype === 'EXP') {
                $("#budget_tab").removeClass('collapse');
                $("#budget_tab_m").removeClass('collapse');
                $("#budget_tab").addClass('in');
                $("#budget_tab_m").addClass('in');
                $("#sidebar_item_exp_custom_fields").addClass('myactive');
                $("#sidebar_item_exp_custom_fields_m").addClass('myactive');
            } else if (topictype === 'BRQ') {
                $("#budget_tab").removeClass('collapse');
                $("#budget_tab_m").removeClass('collapse');
                $("#budget_tab").addClass('in');
                $("#budget_tab_m").addClass('in');
                $("#sidebar_item_brq_custom_fields").addClass('myactive');
                $("#sidebar_item_brq_custom_fields_m").addClass('myactive');
            } else if (topictype === 'EVTSPK') {
              $("#events_tab").removeClass('collapse');
              $("#events_tab_m").removeClass('collapse');
              $("#events_tab").addClass('in');
              $("#events_tab_m").addClass('in');
              $("#sidebar_item_event_speakers").addClass('myactive');
              $("#sidebar_item_event_speakers_m").addClass('myactive');
            } else if (topictype === 'REC') {
              $("#recognition_tab").removeClass('collapse');
              $("#recognition_tab_m").removeClass('collapse');
              $("#recognition_tab").addClass('in');
              $("#recognition_tab_m").addClass('in');
              $("#sidebar_item_recognition_custom_fields").addClass('myactive');
              $("#sidebar_item_recognition_custom_fields_m").addClass('myactive');
            }

        } else if (lastid == 'event_speakers' || lastid == 'manage_event_speaker_fields') {
            $("#events_tab").removeClass('collapse');
            $("#events_tab_m").removeClass('collapse');
            $("#events_tab").addClass('in');
            $("#events_tab_m").addClass('in');
            $("#sidebar_item_event_speakers").addClass('myactive');
            $("#sidebar_item_event_speakers_m").addClass('myactive');
        } else if (lastid == 'event_volunteer_types' || lastid == 'new_event_volunteer_type') {
            $("#events_tab").removeClass('collapse');
            $("#events_tab_m").removeClass('collapse');
            $("#events_tab").addClass('in');
            $("#events_tab_m").addClass('in');
            $("#sidebar_item_event_volunteers").addClass('myactive');
            $("#sidebar_item_event_volunteers_m").addClass('myactive');
        } else if (lastid == 'event') {
            $("#events_tab").removeClass('collapse');
            $("#events_tab_m").removeClass('collapse');
            $("#events_tab").addClass('in');
            $("#events_tab_m").addClass('in');
            $("#sidebar_item_event_list").addClass('myactive');
            $("#sidebar_item_event_list_m").addClass('myactive');
        } else if (lastid == 'event_office_locations') {
            $("#events_tab").removeClass('collapse');
            $("#events_tab_m").removeClass('collapse');
            $("#events_tab").addClass('in');
            $("#events_tab_m").addClass('in');
            $("#sidebar_item_event_office_locations_list").addClass('myactive');
            $("#sidebar_item_event_office_locations_list_m").addClass('myactive');
        }else if ((lastid == 'topic_approvals' || lastid == 'topic_approval_config' || lastid == 'addapprover' || lastid == 'topic_auto_approval_config' || lastid == 'add_approval_task' || lastid == 'topic_approval_tasks')) {
            var topictype = searchParams.get('topicType') ?? 'EVT';

            if (topictype == 'EVT') {
                $("#approvals_tab").removeClass('collapse');
                $("#approvals_tab_m").removeClass('collapse');
                $("#approvals_tab").addClass('in');
                $("#approvals_tab_m").addClass('in');
                $("#sidebar_item_event_approvals").addClass('myactive');
                $("#sidebar_item_event_approvals_m").addClass('myactive');

            } else if (topictype == 'NWS') {
            $("#approvals_tab").removeClass('collapse');
            $("#approvals_tab_m").removeClass('collapse');
            $("#approvals_tab").addClass('in');
            $("#approvals_tab_m").addClass('in');
            $("#sidebar_item_newsletter_approvals").addClass('myactive');
            $("#sidebar_item_newsletter_approvals_m").addClass('myactive');

            } else if (topictype == 'POS') {
                $("#approvals_tab").removeClass('collapse');
                $("#approvals_tab_m").removeClass('collapse');
                $("#approvals_tab").addClass('in');
                $("#approvals_tab_m").addClass('in');
                $("#sidebar_item_post_approvals").addClass('myactive');
                $("#sidebar_item_post_approvals_m").addClass('myactive');
            } else if (topictype == 'SUR') {
                $("#approvals_tab").removeClass('collapse');
                $("#approvals_tab_m").removeClass('collapse');
                $("#approvals_tab").addClass('in');
                $("#approvals_tab_m").addClass('in');
                $("#sidebar_item_survey_approvals").addClass('myactive');
                $("#sidebar_item_survey_approvals_m").addClass('myactive');

            } 
        } else if(lastid == 'event_timezones'){
            $("#events_tab").removeClass('collapse');
            $("#events_tab_m").removeClass('collapse');
            $("#events_tab").addClass('in');
            $("#events_tab_m").addClass('in');
            $("#sidebar_item_event_timezones").addClass('myactive');
            $("#sidebar_item_event_timezones_m").addClass('myactive');
        } else if (lastid == 'usersLookupAdmin'){
                $("#sidebar_item_users_lookup").addClass('myactive');
                $("#sidebar_item_users_lookup_m").addClass('myactive');

        } else if (lastid == 'manageZoneAdmin'){
            $("#sidebar_item_admin").addClass('myactive');
            $("#sidebar_item_admin_m").addClass('myactive');

        } else if (lastid == 'manageusers' || (lastid == 'viewuser' && searchParams.get('section') == 'zone')){
            $("#sidebar_item_manageusers").addClass('myactive');
            $("#sidebar_item_manageusers_m").addClass('myactive');

        } else if (lastid == 'branding') {
            $("#zone_branding_tab").removeClass('collapse');
            $("#zone_branding_tab_m").removeClass('collapse');
            $("#zone_branding_tab").addClass('in');
            $("#zone_branding_tab_m").addClass('in');
            $("#sidebar_item_branding").addClass('myactive');
            $("#sidebar_item_branding_m").addClass('myactive');

        } else if (lastid == 'manage_templates') {
            $("#zone_branding_tab").removeClass('collapse');
            $("#zone_branding_tab_m").removeClass('collapse');
            $("#zone_branding_tab").addClass('in');
            $("#zone_branding_tab_m").addClass('in');
            $("#sidebar_item_templates").addClass('myactive');
            $("#sidebar_item_templates_m").addClass('myactive');

        } else if (lastid == 'zone_regions') {
            $("#zone_configuration_tab").removeClass('collapse');
            $("#zone_configuration_tab_m").removeClass('collapse');
            $("#zone_configuration_tab").addClass('in');
            $("#zone_configuration_tab_m").addClass('in');
            $("#sidebar_item_manage_zone_regions").addClass('myactive');
            $("#sidebar_item_manage_zone_regions_m").addClass('myactive');

        } else if (lastid == 'emailsettings') {
            $("#zone_configuration_tab").removeClass('collapse');
            $("#zone_configuration_tab_m").removeClass('collapse');
            $("#zone_configuration_tab").addClass('in');
            $("#zone_configuration_tab_m").addClass('in');
            $("#sidebar_item_emailsettings").addClass('myactive');
            $("#sidebar_item_emailsettings_m").addClass('myactive');

        } else if (lastid == 'manage_contacts' || lastid == 'manage_regions' || lastid == 'region' || lastid == 'office_locations' || lastid == 'companybranches' || lastid == 'manage_departments' || lastid == 'department') {
            $("#settings_tab").removeClass('collapse');
            $("#settings_tab_m").removeClass('collapse');
            $("#settings_tab").addClass('in');
            $("#settings_tab_m").addClass('in');
            $("#sidebar_item_manage_company_info").addClass('myactive');
            $("#sidebar_item_manage_company_info_m").addClass('myactive');

        } else if (lastid == 'manageglobalusers' || lastid == 'importConnectUsers' || (lastid == 'viewuser' && searchParams.get('section') == 'global')) {
            $("#settings_tab").removeClass('collapse');
            $("#settings_tab_m").removeClass('collapse');
            $("#settings_tab").addClass('in');
            $("#settings_tab_m").addClass('in');
            $("#sidebar_item_manageglobalusers").addClass('myactive');
            $("#sidebar_item_manageglobalusers_m").addClass('myactive');

        } else if (lastid == 'company_branding') {
            $("#settings_tab").removeClass('collapse');
            $("#settings_tab_m").removeClass('collapse');
            $("#settings_tab").addClass('in');
            $("#settings_tab_m").addClass('in');
            $("#sidebar_item_company_branding").addClass('myactive');
            $("#sidebar_item_company_branding_m").addClass('myactive');

        } else if (lastid == 'manageadmin') {
            $("#settings_tab").removeClass('collapse');
            $("#settings_tab_m").removeClass('collapse');
            $("#settings_tab").addClass('in');
            $("#settings_tab_m").addClass('in');
            $("#sidebar_item_company_admin").addClass('myactive');
            $("#sidebar_item_company_admin_m").addClass('myactive');
        } else if (lastid == 'manage_organizations' || lastid == 'add_organization') {
            $("#settings_tab").removeClass('collapse');
            $("#settings_tab_m").removeClass('collapse');
            $("#settings_tab").addClass('in');
            $("#settings_tab_m").addClass('in');
            $("#sidebar_item_manage_organizations").addClass('myactive');
            $("#sidebar_item_manage_organizations_m").addClass('myactive');

        } else if (lastid == 'manage_user_catalogs') {
            $("#settings_tab").removeClass('collapse');
            $("#settings_tab_m").removeClass('collapse');
            $("#settings_tab").addClass('in');
            $("#settings_tab_m").addClass('in');
            $("#sidebar_item_manage_user_catalogs").addClass('myactive');
            $("#sidebar_item_manage_user_catalogs_m").addClass('myactive');

        } else if (lastid == 'manage_company_statistics') {
            $("#settings_tab").removeClass('collapse');
            $("#settings_tab_m").removeClass('collapse');
            $("#settings_tab").addClass('in');
            $("#settings_tab_m").addClass('in');
            $("#sidebar_item_manage_company_statistics").addClass('myactive');
            $("#sidebar_item_manage_company_statistics_m").addClass('myactive');

        } else if (lastid == 'security' || lastid == 'update_security_setting') {
            $("#settings_tab").removeClass('collapse');
            $("#settings_tab_m").removeClass('collapse');
            $("#settings_tab").addClass('in');
            $("#settings_tab_m").addClass('in');
            $("#sidebar_item_security").addClass('myactive');
            $("#sidebar_item_security_m").addClass('myactive');

        } else if (lastid == 'user_guides') {
            $("#support_tab").removeClass('collapse');
            $("#support_tab_m").removeClass('collapse');
            $("#support_tab").addClass('in');
            $("#support_tab_m").addClass('in');
            $("#sidebar_item_user_guides").addClass('myactive');
            $("#sidebar_item_user_guides_m").addClass('myactive');

        }
        else if (lastid == 'tab_type') {
            $("#group_tab").removeClass('collapse');
            $("#group_tab_m").removeClass('collapse');
            $("#group_tab").addClass('in');
            $("#group_tab_m").addClass('in');
            $("#sidebar_item_group").addClass('myactive');
            $("#sidebar_item_group_m").addClass('myactive');

        }
        else if (lastid == 'add_edit_group_tabs') {
            $("#group_tab").removeClass('collapse');
            $("#group_tab_m").removeClass('collapse');
            $("#group_tab").addClass('in');
            $("#group_tab_m").addClass('in');
            $("#sidebar_item_group").addClass('myactive');
            $("#sidebar_item_group_m").addClass('myactive');

        }
        else if (lastid == 'donations.php') {
            $("#sidebar_item_donation_Id").addClass('myactive');
            $("#sidebar_item_donation_Id_m").addClass('myactive');

        }
        else if (lastid == 'points_transactions_list' || lastid == 'points_transactions_list' || lastid =='create_points_program' || lastid == 'points.php' || lastid == 'member_points_configuration' || lastid == 'group_lead_points_configuration' || lastid == 'edit_points_program') {
            $("#sidebar_item_points_Id").addClass('myactive');
            $("#sidebar_item_points_Id_m").addClass('myactive');
        }
        else if (lastid == 'disclaimers' || lastid == 'new_disclaimer' || lastid =='disclaimer_languages') {
            $("#sidebar_item_disclaimer").addClass('myactive');
            $("#sidebar_item_disclaimer_m").addClass('myactive');
        }
        else if (lastid == 'manage_dynamic_lists' || lastid == 'new_dynamic_list') {
            $("#sidebar_item_dynamic_lists").addClass('myactive');
            $("#sidebar_item_dynamic_lists_m").addClass('myactive');
        }
		 // Read more
		$(".td").each(
			function( intIndex ) {
				var textToHide = $(this).text().substring(100);
				var visibleText = $(this).text().substring(0, 100);
				var count = $(this).text().length;
				if (count >100) {
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

// Set focus for popconfirm yes/no button for admin pannel.
$(document).on('show.bs.popover', function(e) {
    setTimeout(() => {
        $('.confirm-dialog-btn-abort').focus();
	}, 100);
});

//go back
function goBack() {
    window.history.back();
}

jQuery(document).ready(function() {
    var max_fields      = 3; //maximum input boxes allowed
    var wrapper         = jQuery(".input_fields_wrap"); //Fields wrapper
    var add_button      = jQuery(".add_field_button"); //Add button ID

    var x = 1; //initlal text box count
    jQuery(add_button).click(function(e){ //on add input button click
        e.preventDefault();
        if(x < max_fields){ //max input box allowed
            x++; //text box increment
            jQuery(wrapper).append('<div><div class="form-group col-md-6"><label>Option '+ (x+3) +':</label><input type="text" name="option1[]" class="form-control input-sm" /><a href="#" class="remove_field">Remove</a></div></div>'); //add input box

        }
    });

    jQuery(wrapper).on("click",".remove_field", function(e){ //user click on remove text
        e.preventDefault(); jQuery(this).parent('div').remove(); x--;
    })
});


jQuery(document).ready(function() {
    var max_fields      = 3; //maximum input boxes allowed
    var wrapper         = jQuery(".input_fields_wrap1"); //Fields wrapper
    var add_button      = jQuery(".add_field_button1"); //Add button ID

    var x = 1; //initlal text box count
    jQuery(add_button).click(function(e){ //on add input button click
        e.preventDefault();
        if(x < max_fields){ //max input box allowed
            x++; //text box increment
            jQuery(wrapper).append('<div><div class="form-group col-md-6"><label>Option '+ (x+3) +':</label><input type="text" name="option2[]" class="form-control input-sm" /><a href="#" class="remove_field1">Remove</a></div></div>'); //add input box

        }
    });

    jQuery(wrapper).on("click",".remove_field1", function(e){ //user click on remove text
        e.preventDefault(); jQuery(this).parent('div').remove(); x--;
    })
});


jQuery(document).ready(function() {
    var max_fields      = 3; //maximum input boxes allowed
    var wrapper         = jQuery(".input_fields_wrap2"); //Fields wrapper
    var add_button      = jQuery(".add_field_button2"); //Add button ID

    var x = 1; //initlal text box count
    jQuery(add_button).click(function(e){ //on add input button click
        e.preventDefault();
        if(x < max_fields){ //max input box allowed
            x++; //text box increment
            jQuery(wrapper).append('<div><div class="form-group col-md-6"><label>Option '+ (x+3) +':</label><input type="text" name="option3[]" class="form-control input-sm" /><a href="#" class="remove_field2">Remove</a></div></div>'); //add input box

        }
    });

    jQuery(wrapper).on("click",".remove_field2", function(e){ //user click on remove text
        e.preventDefault(); jQuery(this).parent('div').remove(); x--;
    })
});

jQuery(document).ready(function() {
    var max_fields      = 3; //maximum input boxes allowed
    var wrapper         = jQuery(".input_fields_wrap3"); //Fields wrapper
    var add_button      = jQuery(".add_field_button3"); //Add button ID

    var x = 1; //initlal text box count
    jQuery(add_button).click(function(e){ //on add input button click
        e.preventDefault();
        if(x < max_fields){ //max input box allowed
            x++; //text box increment
            jQuery(wrapper).append('<div><div class="form-group col-md-6"><label>Option '+ (x+3) +':</label><input type="text" name="option4[]" class="form-control input-sm" /><a href="#" class="remove_field3">Remove</a></div></div>'); //add input box

        }
    });

    jQuery(wrapper).on("click",".remove_field3", function(e){ //user click on remove text
        e.preventDefault(); jQuery(this).parent('div').remove(); x--;
    })
});

jQuery(document).ready(function() {
    var max_fields      = 3; //maximum input boxes allowed
    var wrapper         = jQuery(".input_fields_wrap4"); //Fields wrapper
    var add_button      = jQuery(".add_field_button4"); //Add button ID

    var x = 1; //initlal text box count
    jQuery(add_button).click(function(e){ //on add input button click
        e.preventDefault();
        if(x < max_fields){ //max input box allowed
            x++; //text box increment
            jQuery(wrapper).append('<div><div class="form-group col-md-6"><label>Option '+ (x+3) +':</label><input type="text" name="option5[]" class="form-control input-sm" /><a href="#" class="remove_field4">Remove</a></div></div>'); //add input box

        }
    });

    jQuery(wrapper).on("click",".remove_field4", function(e){ //user click on remove text
        e.preventDefault(); jQuery(this).parent('div').remove(); x--;
    })
});


function clearChildren(element) {
   for (var i = 0; i < element.childNodes.length; i++) {
      var e = element.childNodes[i];
      if (e.tagName) switch (e.tagName.toLowerCase()) {
         case 'input':
            switch (e.type) {
               case "radio":
               case "checkbox": e.checked = false; break;
               case "button":
               case "submit":
               case "image": break;
               default: e.value = ''; break;
            }
            break;
         case 'select': e.selectedIndex = 0; break;
         case 'textarea': e.innerHTML = ''; break;
         default: clearChildren(e);
      }
   }
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

function showTable(){
	jQuery("#myusertable").css("display","block");
	window.setTimeout(function(){
       jQuery("#myusertable").css("display","none");
    }, 100);
}

 // For Redactor Table -  Listen for DOMNodeInserted event on the document
//  CODE COMMENTED AS WE NO LONGER USE REDACTOR FOR ACTION ITEMS OR TOUCHPOINTS IN ADMIN
//   $(document).on('DOMNodeInserted', function(event) {
//     var target = $(event.target);
//     // Check if the target element is the table dropdown
//     if (target.hasClass('redactor-dropdown-table')) {
//         // Modify the content of the table dropdown items
//         var tableItem = target.find('.redactor-dropdown-item-insert-table');
//         var rowAboveItem = target.find('.redactor-dropdown-item-insert-row-above');
//         var rowBelowItem = target.find('.redactor-dropdown-item-insert-row-below');
//         var columnLeftItem = target.find('.redactor-dropdown-item-insert-column-left');
//         var columnRightItem = target.find('.redactor-dropdown-item-insert-column-right');
//         var addHeadItem = target.find('.redactor-dropdown-item-add-head');
//         var deleteHeadItem = target.find('.redactor-dropdown-item-delete-head');
//         var deleteColumnItem = target.find('.redactor-dropdown-item-delete-column');
//         var deleteRowItem = target.find('.redactor-dropdown-item-delete-row');
//         var deleteTableItem = target.find('.redactor-dropdown-item-delete-table');
//         // Set the inner text or HTML of the items
//         tableItem.html('Insert Table');
//         rowAboveItem.html('Insert Row Above');
//         rowBelowItem.html('Insert Row Below');
//         columnLeftItem.html('Insert Column Left');
//         columnRightItem.html('Insert Column Right');
//         addHeadItem.html('Add Head');
//         deleteHeadItem.html('Delete Head');
//         deleteColumnItem.html('Delete Column');
//         deleteRowItem.html('Delete Row');
//         deleteTableItem.html('Delete Table');
//     }
// });

function validatePointsProgramForm(event) {
    var formdata = new FormData(event.target);

    if (!stripHtml(formdata.get('description')).trim()) {
        Swal.fire({
            title: 'Error',
            text: 'Description cannot be empty',
        });
        return false;
    }
    
    let submitButton = $(event.target).find(":submit"); 
    // Prevent double submission
    if (submitButton.prop("disabled")) {
        return false;
    }
    submitButton.prop("disabled", true);
    return true;
}

/**
 * https://stackoverflow.com/a/822486
 */
function stripHtml(html)
{
   var tmp = document.createElement('div');
   tmp.innerHTML = html;
   return tmp.textContent || tmp.innerText || '';
}


function addUpdateBlockedKeyword(event) {
    event.preventDefault();

    $.ajax({
        url: 'ajax.php?addUpdateBlockedKeyword=1',
        type: 'POST',
        data: $(event.target).serialize(),
        success: function(data) {
            try {
                var json = JSON.parse(data);
                Swal.fire({
                    title: json.title,
                    text: json.message,
                }).then(function(result) {
                    if (json.status == 1) {
                        location.reload();
                    }
                });
            } catch(e) {
                Swal.fire({
                    title: 'Error',
                    text: 'Unknown error',
                });
            }
        }
    });

    return false;
}

function deleteBlockedKeyword(blocked_keyword_id)
{
    $.ajax({
        url: 'ajax.php?deleteBlockedKeyword=1',
        type: 'POST',
        data: {
            blocked_keyword_id,
        },
        success: function (data) {
            try {
                var json = JSON.parse(data);
                Swal.fire({
                    title: json.title,
                    text: json.message,
                }).then(function(result) {
                    if (json.status == 1) {
                        location.reload();
                    }
                });
            } catch(e) {
                Swal.fire({
                    title: 'Error',
                    text: 'Unknown error',
                });
            }
        }
    });
}

//Add document title to page
function updatePageTitle(title){	
    document.title = title;
}

function encodeHTMLEntities(rawStr) {
    return rawStr.replace(/[\u00A0-\u9999<>\&]/g, ((i) => `&#${i.charCodeAt(0)};`));
}

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
					swal.fire({title: 'Error!',text:'Invalid file. The file size is too big (2MB maximum).'});
				} else if (data =='1') {
					swal.fire({title: 'Error!',text:'Maximum allowed size of profile picture is 2MB!'});
				} else if (data =='2'){
					swal.fire({title: 'Error!',text:'Only .jpg,.jpeg,.png files are allowed!'});
				} else { // success
					swal.fire({title: 'Success',text:'Profile picture changed successfully.'}).then(function(result) {
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
		swal.fire({title: 'Error!',text:'Please upload your profile image!'});
	}
	
	setTimeout(() => {
		$(".swal2-confirm").focus();
	}, 300)
}


function updateApprovalFilters() {
    let approvalStatus = '';
    if ($('#approval_status_filter').length) {
        approvalStatus = $('#approval_status_filter').val();
    }
    let requestYear = '';
    if ($('#request_year_filter').length) {
        requestYear = $('#request_year_filter').val();
    }

    // Get the current URL
    var currentUrl = window.location.href;

    // Define the parameter and value to be updated or appended
    var approvalStatusParam = 'approvalStatus';
    let requestYearParam = 'requestYear'

    // Create a URL object
    var url = new URL(currentUrl);

    // Check if the parameter exists in the URL
    if (url.searchParams.has(approvalStatusParam)) {
        // Update the existing parameter
        url.searchParams.set(approvalStatusParam, approvalStatus);
    } else {
        // Append the new parameter if it doesn't exist
        url.searchParams.append(approvalStatusParam, approvalStatus);
    }
    if (url.searchParams.has(requestYearParam)) {
        // Update the existing parameter
        url.searchParams.set(requestYearParam, requestYear);
    } else {
        // Append the new parameter if it doesn't exist
        url.searchParams.append(requestYearParam, requestYear);
    }
    // Redirect to the updated URL
    window.location.href = url.toString();

}
function previewSurvey(g,s){
	$.ajax({
		url: 'ajax.php?previewSurvey=1',
		type: "get",
		data: {'groupid':g,'surveyid':s},
		success : function(data) {
			$('#loadAnyModal').html(data);
			$('#surveyPreview').modal({
				backdrop: 'none',
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


<?php require __DIR__ . '/../../include/common/js/common.js.php'; ?>

<?php require __DIR__ . '/../../include/common/js/attachments.js.php'; ?>

<?php require __DIR__ . '/../../include/common/js/custom_fields.js.php'; ?>
