<style>
    .select2-container--default .select2-selection--single {
        border: 1px solid #ced4da;
        height: calc(1.5em + .75rem + 2px)
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 35px;
    }
</style>
<div aria-label="external user information" id="externalUserInformation" class="modal fade">
    <div aria-label="<?= gettext('Please provide your basic information')?>" class="modal-dialog modal-lg" aria-modal="true" role="dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="form_title"><?= gettext('Please provide your basic information')?></h4>
            </div>
            <div class="modal-body">
                <div class="col-md-12">
                    <form action="" id="externalUserInfoForm">
                        <div class="form-group">
                            <label for="firstname"><?= gettext("First Name");?><span style="color: #ff0000;"> *</span></label>
                            <input type="text" class="form-control" placeholder="<?= gettext("First Name");?>" name="firstname" >
                        </div>
                        <div class="form-group">
                            <label for="email"><?= gettext("Last Name");?><span style="color: #ff0000;"> *</span></label>
                            <input type="text" class="form-control" placeholder="<?= gettext("First Name");?>" name="lastname">
                        </div>
                        <div class="form-group">
                            <label for="email"><?= gettext("Email");?><span style="color: #ff0000;"> *</span></label>
                            <input type="email" class="form-control" placeholder="<?= gettext("Enter Email");?>" name="email">
                        </div>
                        <div class="form-group">
                            <label class=""><?= gettext("Your Timezone");?><span style="color: #ff0000;"> *</span></label>
                            <select class="form-control teleskope-select2-dropdown" name="timezone" style="width: 100%;">
                                <?php echo getTimeZonesAsHtmlSelectOptions(''); ?>
                            </select>
                        </div>
                    </form>
                </div>
                    
            </div>
            <div class="modal-footer">
                <button type="button" onclick="saveExternalUserInformation('<?= $_COMPANY->encodeId($eventid);?>')" class="btn btn-affinity"><?= gettext("Submit")?></button>
            </div>
        </div>
    </div>
</div>


<script>
    $(document).ready(function () {
        $(".teleskope-select2-dropdown").select2({width: 'resolve'});
    });

    function saveExternalUserInformation(e){
        let formdata = $("#externalUserInfoForm")[0];
        let finaldata  = new FormData(formdata);
        finaldata.append('eventid',e);
        
        $.ajax({
            url: 'ajax.php?saveExternalUserInformation=1',
            type: "POST",
            data: finaldata,
            processData: false,
            contentType: false,
            cache: false,
        success : function(data) {
            try {
                let jsonData = JSON.parse(data);
                swal.fire({title: jsonData.title, text: jsonData.message,allowOutsideClick:false}).then(function (result) {
                    if (jsonData.status == 1){
                        $('.modal').modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                        location.reload();
                    }
                });
            } catch(e) {}
            }
      });

    }
</script>