
function deleteQuestion(r,i){
  $.ajax({
    url: 'action.php',
        type: "GET",
    data: 'deletequestion='+ i,
        success : function(data) {
      jQuery("#"+r).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
    }
  });
}



// load Model
function loadModel(q){

  $.ajax({
    url: 'action.php',
        type: "GET",
    data: 'loadmodel='+ q,
        success : function(data) {
      if (data == 2){
        swal.fire({title: 'Message',text:"No new companies found for push this question"});
      }else{
        $("#replace").html(data);
        $("#getCodeModal").modal({backdrop: 'static', keyboard: false});
        $("#getCodeModal").modal('show');

      }
    }
  });
}

function deleteMobFawSuper(r,i){
  $.ajax({
    url: 'action.php',
        type: "GET",
    data: 'deleteMobFawSuper='+ i,
        success : function(data) {
      jQuery("#"+r).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
    }
  });
}
function deleteAdminFAQs(r,i){
  $.ajax({
    url: 'action.php',
        type: "GET",
    data: 'deleteAdminFAQs='+ i,
        success : function(data) {
      jQuery("#"+r).animate({ backgroundColor: "#fbc7c7" }, "fast").animate({ opacity: "hide" },  2000);
    }
  });
}

function checkCompanyName(v,i, domainedit){
  var val = v.value;
  $.ajax({
    url: 'action.php',
        type: "GET",
    data: 'checkCompanyName='+ val+'&id='+i + '&buildSubdomain='+domainedit,
        success : function(data) {
      if (data == 1) {
        $("#companyname").focus();
        swal.fire({title: 'Error!',text:"Company name already registered!"});
        return false;
      } else if (domainedit) {
        if( data == 2){
          $("#subdomain").val('');
        }else{
          $("#subdomain").val(data);
      }
    }

    }
  });
}

function checkSubdomain(v){
  var val = v.value;
  if (val == ""){
    swal.fire({title: 'Error!',text:"Please enter a unique Subdomain."});
  }else{
    $.ajax({
      url: 'action.php',
      type: "GET",
      data: 'checkSubdomain='+ val,
      success : function(data) {
        if (data == 2) {
          swal.fire({title: 'Error!',text:"Subdomain already used. Please choose other one."});
          $("#subdomain").val('');
          $("#subdomain").focus();
          $("#subdomain").val(val);
        }
      }
    });
  }
}

function updateEmailSetting(){
  var formdata =	$('#emailSetting').serialize();
  $.ajax({
    url: 'action.php?updateEmailSetting=1',
        type: "POST",
    data: formdata,
        success : function(data) {
      if (data ==1) {
        swal.fire({title: 'Success',text:"Setting updated successfully"}).then(function(result) {
          window.location.href = "manage";
        });
      } else {
        swal.fire({title: 'Error',text:data});
      }
    }
  });
}

function updateEmailSettingPasssword(){
  var e = $("#email_protocol").val();
  var p = $("#password").val().trim();
  var c = $("#confirm_password").val().trim();
  if (p == ''){
    swal.fire({title: 'Error',text:"Password can't be empty"});
  } else if (p!=c) {
    swal.fire({title: 'Error',text:"Confirm password not matched!"});
  } else {
    $.ajax({
      url: 'action.php?updateEmailSettingPasssword=1',
      type: "POST",
      data: {"email_protocol":e,"password":p},
      success : function(data) {
        if (data ==1) {
          if(e ==1){
            $("#s_smtp").show()
            $("#e_smtp").hide()
          } else {
            $("#s_imap").show()
            $("#e_imap").hide()
          }
          swal.fire({title: 'Success',text:"Password updated successfully"});
          $('#passwordModal').modal('hide');
          $('body').removeClass('modal-open');
          $('.modal-backdrop').remove();

        } else {
          swal.fire({title: 'Error',text:"Something went wrong. Please try again."});
        }
      }
    });
  }
}

function setCurrentAppVersion(i,s){
  $.ajax({
    url: 'action.php?setCurrentAppVersion=1',
    type: "POST",
    data: {"id":i,'status':s},
    success : function(data) {
      if (data){
        var msg = "App version approved successfully";
        if (s == 0){
          msg = "App version disapproved successfully";
        }
        swal.fire({title: 'Success',text:msg}).then(function(result) {
          window.location.reload();
        });

      } else {
        swal.fire({title: 'Error',text:"Something went wrong. Please try again."});
      }
    }
  });


}

function uplodeGuideFile(i){
  $(document.body).css({'cursor' : 'wait'});
  var formdata = $('#form'+i)[0];
  var finaldata  = new FormData(formdata);
  finaldata.append("section",i);

  $.ajax({
        url: 'action.php?uplodeGuideFile=1',
        type: 'POST',
    enctype: 'multipart/form-data',
        data: finaldata,
        processData: false,
        contentType: false,
        cache: false,
    success: function(data) {
      $(document.body).css({'cursor' : 'default'});
      if ( data == '0'){
        swal.fire({title: 'Error',text:"File uploading error! Please try again."});
      } else if (data == '1'){
        swal.fire({title: 'Error',text:"Something went wrong. Please try again."});
      } else if (data == '2'){
        swal.fire({title: 'Error',text:"Please select a .pdf file to upload."});
      } else {
        Swal.fire({
          title: 'Success',
          text: 'Document uploaded successfully.',
          footer: '<a href="'+data+'" target="_blank" >View uploaded file</a>'
        }).then( function(result) {
          $('#form'+i)[0].reset();
          cancelInput(i)
        });
      }
    }
  });
}

function updateGuide(i){
  $("#action"+i).hide();
  $("#input"+i).show();
}

function cancelInput(i){
  $("#action"+i).show();
  $("#input"+i).hide();
}

function loadLoginSettingForm(type,id=0){
  $.ajax({
    url: 'action.php?loadLoginSettingForm=1',
    type: "GET",
    data: {"type":type,"id":id},
    success : function(data) {
      if (data!=1){
        $("#dynamic_form_fields").html(data);
      } else {
        swal.fire({title: 'Error',text:"Something went wrong. Please try again."});
      }
    }
  });
}

function changeEaiCredentialStatus(i,s){
  $.ajax({
    url: 'action.php?changeEaiCredentialStatus=1',
    type: "POST",
    data: {"id":i,"status":s},
    success : function(data) {
      if (data == 1){
        swal.fire({title: 'Success',text:"Status updated successfully"}).then(function(result) {
          window.location.href = "manage_eai_credentials";
        });
      } else {
        swal.fire({title: 'Error',text:"Something went wrong. Please try again."})
      }
    }
  });
}

function resetEaiCredentialPassword(i) {
  $.ajax({
    url: 'action.php?resetEaiCredentialPassword=1',
    type: "POST",
    data: { id: i },
    success: function(data) {
      if (data) {
        swal.fire({ title: 'Success', text: "Password reset successfully" }).then(function(result) {

          // Create the modal HTML
          var copyModal = ''
            + '<div id="passwordModal" class="modal fade" tabindex="-1">'
            + '  <div class="modal-dialog modal-lg">'
            + '    <div class="modal-content">'
            + '      <div class="modal-header">'
            + '        <h5 class="modal-title">Copy password</h5>'
            + '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.reload();"></button>'
            + '      </div>'
            + '      <div class="modal-body">'
            + '        <div class="row">'
            + '          <div class="col-md-8 mb-2">'
            + '            <input type="text" id="passwordToCopy" name="passwordToCopy" class="form-control" readonly placeholder="Password" value="' + data + '">'
            + '          </div>'
            + '          <div class="col-md-4 mb-2">'
            + '            <button class="btn btn-primary w-100" onclick="copyPassword()">Copy Password</button>'
            + '          </div>'
            + '          <div class="col-12">'
            + '            <small>Note: You can\'t view this password again. Please copy and store it for future use.</small>'
            + '          </div>'
            + '        </div>'
            + '      </div>'
            + '    </div>'
            + '  </div>'
            + '</div>';

          // Inject modal into page
          $("#copyPasswordContainer").html(copyModal);

          // Initialize and show Bootstrap 5 modal
          const passwordModal = new bootstrap.Modal(document.getElementById('passwordModal'), {
            backdrop: 'static',
            keyboard: false
          });
          passwordModal.show();
        });
      } else {
        swal.fire({ title: 'Error', text: "Something went wrong. Please try again." });
      }
    }
  });
}

function copyPassword(){
  var passwordToCopy = document.getElementById('passwordToCopy');
  passwordToCopy.select();
  passwordToCopy.setSelectionRange(0, 99999)
  document.execCommand("copy");

  swal.fire({title: 'Success',text:"Password copied to clipboard successfully"}).then(function(result) {
    $('#passwordModal').modal('hide');
    $('body').removeClass('modal-open');
    $('.modal-backdrop').remove();
    window.location.reload()
  });
}


function loadDataExportJobs(c){
  window.location.hash = "exportJobs";
  $.ajax({
    url: 'action.php?loadDataExportJobs=1',
    type: "GET",
    data: {companyid:c},
    success : function(data) {
      $("#jobs_data").html(data);
      $(".confirm").popConfirm({content: ''});
    }
  });
}
function loadDataImportJobs(c){
  window.location.hash = "importJobs";
  $.ajax({
    url: 'action.php?loadDataImportJobs=1',
    type: "GET",
    data: {companyid:c},
    success : function(data) {
      $("#jobs_data").html(data);
      $(".confirm").popConfirm({content: ''});
    }
  });
}

function deleteScheduledJob(id){
  $.ajax({
    url: 'action.php?deleteScheduledJob=1',
    type: "POST",
    data: {jobid:id},
    success : function(data) {
      if (data){
        swal.fire({title: 'Success',text:"Job Deleted successfully"}).then(function(result) {
          window.location.reload();
        });
      }else {
        swal.fire({title: 'Error',text:"Something went wrong. Please try again."});
      }
    }
  });
}


function rerunScheduledJob(id){
  $.ajax({
    url: 'action.php?rerunScheduledJob=1',
    type: "POST",
    data: {jobid:id},
    success : function(data) {
      if (data){
        swal.fire({title: 'Success',text:"Job scheduled to rerun"}).then(function(result) {
          window.location.reload();
        });
      }else {
        swal.fire({title: 'Error',text:"Something went wrong. Please try again."});
      }
    }
  });
}

function deleteDomain(id){
  $.ajax({
    url: 'action.php?deleteDomain=1',
    type: "POST",
    data: {domain_id:id},
    success : function(data) {
      if (data){
        swal.fire({title: 'Success',text:"Domain Deleted successfully"}).then(function(result) {
          window.location.reload();
        });
      }else {
        swal.fire({title: 'Error',text:"Something went wrong. Please try again."});
      }
    }
  });
}

function deleteZone(zoneid)
{
  Swal.fire({
    title: 'Are you sure?',
    text: "You won't be able to revert this!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!',
  }).then(function (result) {
    if (!result.value) {
      return;
    }

    $.ajax({
      url: 'action?deleteZone=1',
      type: 'POST',
      data: {
        zoneid
      },
      dataType: 'json',
      success: function (data) {
        Swal.fire({
          title: data.title,
          text: data.message,
        }).then(function () {
          window.location.reload();
        });
      },
      error: function () {
        Swal.fire({
          title: 'Error',
          text: 'Something went wrong. Please try again later',
        });
      }
    });
  });
}

function deleteCompany(companyid)
{
  Swal.fire({
    title: 'Are you sure?',
    text: "You won't be able to revert this!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!',
  }).then(function (result) {
    if (!result.value) {
      return;
    }

    $.ajax({
      url: 'action?deleteCompany=1',
      type: 'POST',
      data: {
        companyid
      },
      dataType: 'json',
      success: function (data) {
        Swal.fire({
          title: data.title,
          text: data.message,
        }).then(function () {
          window.location.reload();
        });
      },
      error: function () {
        Swal.fire({
          title: 'Error',
          text: 'Something went wrong. Please try again later',
        });
      }
    });
  });
}


function activeToggleSuperAdmin(super_admin_id)
{
  $.ajax({
    url: 'manage_super_admins?action=activeToggleSuperAdmin',
    type: 'POST',
    data: {
      super_admin_id
    },
    dataType: 'json',
    success: function (data) {
      Swal.fire({
        title: data.title,
        text: data.message,
      }).then(function () {
        window.location.reload();
      });
    },
  });
}

function unblockSuperAdmin(super_admin_id)
{
  $.ajax({
    url: 'manage_super_admins?action=unblockSuperAdmin',
    type: 'POST',
    data: {
      super_admin_id
    },
    dataType: 'json',
    success: function (data) {
      Swal.fire({
        title: data.title,
        text: data.message,
      }).then(function () {
        window.location.reload();
      });
    },
  });
}

function renewPassword(super_admin_id)
{
  $.ajax({
    url: 'manage_super_admins?action=renewPassword',
    type: 'POST',
    data: {
      super_admin_id
    },
    dataType: 'json',
    success: function (data) {
      Swal.fire({
        title: data.title,
        text: data.message,
      }).then(function () {
        window.location.reload();
      });
    },
  });
}

function resetGoogleAuthToken(super_admin_id)
{
  $.ajax({
    url: 'manage_super_admins?action=resetGoogleAuthToken',
    type: 'POST',
    data: {
      super_admin_id
    },
    dataType: 'json',
    success: function (data) {
      Swal.fire({
        title: data.title,
        text: data.message,
      }).then(function () {
        window.location.reload();
      });
    },
  });
}

function submitCreateOrUpdateSuperAdminForm(event) {
  event.preventDefault();

  var form = $(event.target);

  $.ajax({
    url: form.attr('action'),
    type: 'POST',
    data: form.serialize(),
    dataType: 'json',
    success: function (data) {
      Swal.fire({
        title: data.title,
        text: data.message,
      }).then(function () {
        if (data.status === 1) {
          window.location = 'list_super_admins';
        }
      });
    },
  });
}
