<style>
        .ui-autocomplete{
            border: 1px solid #000 !important;
            z-index: 100000 !important;
            font-family: inherit !important;
            font-size: inherit!important;
            background: #fff !important;
        }
        .ui-menu-item-wrapper{
            padding: 6px 12px !important;
            color: #000;
        }
        .ui-menu-item-wrapper:hover, .ui-state-active {
            background: #0077B5 !important;
            color: #fff;
        }
        .ui-autocomplete-input:focus{
            border-color: #4f9fcf;
        }
        /* For clear button in inputs */
        .clear-input{
            position: absolute;
            height: calc(1.5em + .75rem + 1px);
            right: 16px;
            top: 73%;
            transform: translateY(-50%);
            border: none;
            border-radius: 0 .25rem .25rem 0;
            background: #f8f6f6;
            cursor: pointer;
            font-size: 16px;
            color: #aaa;
            border-left: 1px solid #ced4da;
        }
        .clear-input{
            color: #878787;
        }
        .readonly-select{
            pointer-events: none;
            background-color: #e9ecef;
            color: #6c757d;
        }
</style>
<?php
$is_claimed = $organization_id && $latestOrgData[0]['is_claimed'];
?>
<div class="modal" id="orgModalForm">
    <div aria-label="<?= $modalTitle; ?>" class="modal-dialog modal-xl modal-dialog-w1000" aria-modal="true" role="dialog">
      <div class="modal-content">
  
        <!-- Modal Header -->
        <div class="modal-header">
          <h4 id="modal-title" class="modal-title"><?= $modalTitle; ?></h4>
          <button aria-label="<?= gettext('close');?>" id="btn_close" type="button" class="close" data-dismiss="modal" onclick="manageOrganizations('<?= $_COMPANY->encodeId($eventid);?>')">&times;</button>
        </div>
        <form id="orgForm">
            <!-- Modal body -->
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                            <input type="hidden" id="organization_id" name="organization_id" value="<?= $organization_id ? $_COMPANY->encodeId($organization_id) : $_COMPANY->encodeId(0) ?>">
                            <input type="hidden" id="org_id" name="org_id" value="<?= $organization_id ? $_COMPANY->encodeId($org->val('api_org_id') ?? 0) : $_COMPANY->encodeId(0) ?>">
                            <div class="col-12 form-group-emphasis">
                                <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext("Basic Information")?></h5>
                                <div class="alert-danger px-3 mb-3" id="not_approved" style="display: none; font-weight: bold;"><?=gettext('This organization is not approved. Please search for another organization.')?></div>
                                <div class="alert-info px-3 mb-3 show_if_claimed" style="<?= $is_claimed ? '' : 'display: none;' ?>"><small><?=gettext('This data section is managed by the Organization.')?></small></div>
                                <div class="form-group mb-2 col-md-6">
                                    <label for="organization_name"><?= gettext("Organization Name");?>: <span style="color: #ff0000;"> *</span></label>
                                    <input type="text" class="form-control" id="organization_name" name="organization_name" value="<?= $organization_id ? htmlspecialchars($latestOrgData[0]['organization_name'] ?? '') : ''?>" placeholder=" <?= gettext("Organization Name");?>" <?= $is_claimed ? 'data-required="false" readonly' : 'data-required="true"' ?>>
                                    <?php if(!$organization_id){ ?>
                                    <button type="button" class="clear-input" onclick="clearAllFields()">x</button>
                                    <?php } ?>
                                </div>

                                <div class="form-group mb-2 col-md-6">
                                    <label for="organization_taxid"><?= gettext("Organization Tax ID");?>: <span style="color: #ff0000;"> *</span></label>
                                    <input type="text" class="form-control" id="organization_taxid" name="organization_taxid" value="<?= $organization_id ? htmlspecialchars($latestOrgData[0]['organization_taxid'] ?? '') : ''?>" placeholder=" <?= gettext("Organization Tax ID");?>" <?= $is_claimed ? 'data-required="false" readonly' : 'data-required="true"' ?>>
                                    <?php if(!$organization_id){ ?>
                                    <button type="button" class="clear-input" onclick="clearAllFields()">x</button>
                                    <?php } ?>
                                </div>

                                <div class="form-group mb-2 col-md-6">
                                    <label for="organization_url"><?= gettext("Organization Website/URL");?>: <span style="color: #ff0000;"> *</span></label>
                                    <input type="text" class="form-control" id="organization_url" name="organization_url" value="<?= $organization_id ? htmlspecialchars($latestOrgData[0]['org_url'] ?? '') : ''?>" placeholder=" <?= gettext("Organization Website/URL");?>" <?= $is_claimed ? 'data-required="false" readonly' : 'data-required="true"' ?>>
                                </div>

                                <div class="form-group mb-2 col-md-6">
                                    <label for="organization_type"><?= gettext("Organization Type");?>: <span style="color: #ff0000;"> *</span></label>
                                    <select class="form-control" name="organization_type" id="organization_type" placeholder=" <?= gettext("Select Organization Type");?>" <?= $is_claimed ? 'readonly style="pointer-events: none;"' : '' ?>>
                                        <?php $selected = $latestOrgData ? $latestOrgData[0]['organization_type'] : ''; ?>
                                        <option value="2" <?= $selected == 2 ? 'selected' : '' ?> ><?= gettext("Non-Profit");?></option>
                                        <option value="1" <?= $selected == 1 ? 'selected' : '' ?> ><?= gettext("For Profit");?></option>
                                        <option value="3" <?= $selected == 3 ? 'selected' : '' ?>><?= gettext("Government/Public");?></option>
                                    </select>
                                </div>

                            </div> <!-- Basic info section ends -->

                            <div class="col-12 form-group-emphasis">
                                <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext("Address")?></h5>
                                <div class="alert-info px-3 mb-3 show_if_claimed" style="<?= $is_claimed ? '' : 'display: none;'  ?>"><small><?=gettext('This data section is managed by the Organization.')?></small></div>
                                <div class="form-group mb-2 col-md-6">
                                    <label for="address_street"><?= gettext("Street Address");?>: <span style="color: #ff0000;"> *</span></label>
                                    <input type="text" class="form-control" id="address_street" name="address_street" value="<?= $organization_id ?  htmlspecialchars($latestOrgData[0]['street'] ?? '') : ''?>" placeholder=" <?= gettext("Street Name");?>" <?= $is_claimed ? 'data-required="false" readonly' : 'data-required="true"' ?>>
                                </div>

                                <div class="form-group mb-2 col-md-6">
                                    <label for="address_city"><?= gettext("City");?>: <span style="color: #ff0000;"> *</span></label>
                                    <input type="text" class="form-control" id="address_city" name="address_city" value="<?= $organization_id ? htmlspecialchars($latestOrgData[0]['city'] ?? '') : ''?>" placeholder=" <?= gettext("City Name");?>" <?= $is_claimed ? 'data-required="false" readonly' : 'data-required="true"' ?>>
                                </div>
                      
                                <div class="form-group mb-2 col-md-6">
                                    <label for="address_state"><?= gettext("State");?>: <span style="color: #ff0000;"> *</span></label>
                                    <input type="text" class="form-control" id="address_state" name="address_state" value="<?=  $organization_id ? htmlspecialchars($latestOrgData[0]['state'] ?? '') : ''?>" placeholder=" <?= gettext("State Name");?>" <?= $is_claimed ? 'data-required="false" readonly' : 'data-required="true"' ?>>
                                </div>

                                <div class="form-group mb-2 col-md-6">
                                    <label for="address_zipcode"><?= gettext("Zip Code");?>: <span style="color: #ff0000;"> *</span></label>
                                    <input type="text" class="form-control" id="address_zipcode" name="address_zipcode" value="<?= $organization_id ?  htmlspecialchars($latestOrgData[0]['zipcode'] ?? '') : ''?>" placeholder=" <?= gettext("Zip Code");?>" <?= $is_claimed ? 'data-required="false" readonly' : 'data-required="true"' ?>>
                                </div>

                                <div class="form-group mb-2 col-md-6">
                                    <label for="address_country"><?= gettext("Country Name");?>: <span style="color: #ff0000;"> *</span></label>
                                    <input type="text" class="form-control" id="address_country" name="address_country" value="<?= $organization_id ?  htmlspecialchars($latestOrgData[0]['country'] ?? '') : ''?>" placeholder=" <?= gettext("Country Name");?>" <?= $is_claimed ? 'data-required="false" readonly' : 'data-required="true"' ?>>
                                </div>

                            </div><!-- Address section ends -->

                            <div class="col-12 form-group-emphasis">
                                <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext("Organization Contacts")?></h5>
                                <div class="row-no-gutters">
                                <div class="col-md-12 p-0">
                                    <h6 style="font-weight: bold; padding: 15px;"><?=gettext("Main Contact")?></h6>
                                    <div class="alert-info px-3 mx-3 mb-3 show_if_claimed" style="<?= $is_claimed ? '' : 'display: none;' ?>"><small><?=gettext('This data section is managed by the Organization.')?></small></div>
                                </div>
                                <div class="form-group mb-2 col-md-4">
                                    <label for="contact_firstname"><?= gettext("Contact First Name");?>: <span style="color: #ff0000;"> *</span></label>
                                    <input type="text" class="form-control" id="contact_firstname" name="contact_firstname" value="<?= $organization_id ?  htmlspecialchars($latestOrgData[0]['contact_firstname'] ?? '') : ''?>" placeholder=" <?= gettext("Contact First Name");?>" <?= $is_claimed ? 'data-required="false" readonly' : 'data-required="true"' ?>>
                                </div>
                        
                                <div class="form-group mb-2 col-md-4">
                                    <label for="contact_lastname"><?= gettext("Contact Last Name");?>: <span style="color: #ff0000;"> *</span></label>
                                    <input type="text" class="form-control" id="contact_lastname" name="contact_lastname" value="<?= $organization_id ?  htmlspecialchars($latestOrgData[0]['contact_lastname'] ?? '') : ''?>" placeholder=" <?= gettext("Contact Last Name");?>" <?= $is_claimed ? 'data-required="false" readonly' : 'data-required="true"' ?>>
                                </div>

                                <div class="form-group mb-2 col-md-4">
                                    <label for="contact_email"><?= gettext("Contact Email");?>: <span style="color: #ff0000;"> *</span></label>
                                    <input type="text" class="form-control" id="contact_email" name="contact_email" value="<?=  $organization_id ? htmlspecialchars($latestOrgData[0]['contact_email'] ?? '') : ''?>" placeholder=" <?= gettext("Contact Email");?>" <?= $is_claimed ? 'data-required="false" readonly' : 'data-required="true"' ?>>
                                </div>
                                </div><!-- Main contacts sub-section ends -->

                                <div class="row-no-gutters">
                                    <div class="col-md-12 p-0">
                                        <h6 style="font-weight: bold; padding: 15px;margin-top: 15px;"><?=gettext("Event Contacts")?></h6>
                                        <a href="javascript:void(0)" id="add-contact-btn"><i aria-label="Sub Item" tabindex="0" class="fa fa-plus-circle add_button" aria-hidden="true"></i></a>
                                    </div>
                                    <div id="contact-list">
                                        <?php if(!empty($additional_contact_details)){ ?>
                                            <?php
                                            $contactid=0;
                                            foreach ($additional_contact_details as $contact) {
                                                $contactid++
                                            ?>
                                            <div class="col-md-12 contact-row p-0">
                                                <div class="form-group mb-2 col-md-4">
                                                <label for="contact_firstname_<?=$contactid ?>">First Name<span style="color: #ff0000;"> *</span></label>
                                                <input class="form-control" type="text" name="contacts[<?=$contactid?>][firstname]" id="contact_firstname_<?=$contactid ?>" value="<?= htmlspecialchars($contact['firstname'])?>" data-required="true">
                                                </div>
                                                <div class="form-group mb-2 col-md-4">
                                                <label for="contact_lasstname_<?=$contactid ?>">Last Name<span style="color: #ff0000;"> *</span></label>
                                                <input class="form-control" type="text" name="contacts[<?=$contactid?>][lastname]" id="contact_lasstname_<?=$contactid ?>" value="<?= htmlspecialchars($contact['lastname'])?>" data-required="true">
                                                </div>
                                                <div class="form-group mb-2 col-md-3">
                                                <label for="contact_email_<?=$contactid ?>">Email<span style="color: #ff0000;"> *</span></label>
                                                <input class="form-control" type="email" id="contact_email_<?=$contactid ?>" name="contacts[<?=$contactid?>][email]"  value="<?= htmlspecialchars($contact['email'])?>" data-required="true">
                                                </div>
                                                <a href="javascript:void(0)" class="remove-contact-btn col-md-1" style="margin-top:35px;" data-contact-id="<?=$contactid?>"><i class="fa fa-times-circle fa-lg" aria-hidden="true"></i></a>
                                            </div>
                                            <?php } ?>
                                        <?php } ?>
                                    </div>
                                </div><!-- Event contacts sub-section ends -->

                            </div><!-- Organization contacts section ends -->

                            <div class="col-12 form-group-emphasis">
                                <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?= sprintf(gettext("%s notes about this organization"),$_COMPANY->val('companyname'));?></h5>
                                <div class="alert-info px-3 mb-3 show_if_claimed2" style="<?= ($is_claimed && $latestOrgData[0]['company_organization_notes']) ? '' : 'display: none;' ?>"><small><?=gettext('This data section is managed by the platform administrator.')?></small></div>
                                <div class="form-group mb-2 col-md-12 mt-3">
                                    <textarea class="form-control exclude" id="company_organization_notes" name="company_organization_notes" placeholder=" <?= gettext("Enter notes about this organization here");?>" <?= ($is_claimed && $latestOrgData[0]['company_organization_notes']) ? 'readonly' : '' ?>><?= $organization_id ?  htmlspecialchars($latestOrgData[0]['company_organization_notes'] ?? '') : ''?></textarea>
                                </div>

                            </div><!-- Organization about section ends -->
                            <!-- Org custom fields if any-->
                            <?php if (!empty($custom_fields)) { ?>
                                <div class="col-12 form-group-emphasis">
                                <h5 class="form-group-emphasis-heading form-group-emphasis-dark"><?= gettext("Custom Fields")?></h5>
                                <?php include(__DIR__ . '/event_custom_fields.template.php'); ?>
                                </div><!-- Organization custom fields section ends -->
                            <?php } ?>
                    </div>
                </div>
            </div>
    
            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="form-group text-center">
                    <button type="button" id="submitButton" onclick="addOrUpdateOrg('<?= $_COMPANY->encodeId($eventid);?>')" class="btn btn-affinity prevent-multi-clicks"><?= gettext("Submit");?></button>&emsp;<button type="button" class="btn btn-affinity" data-dismiss="modal" onclick="manageOrganizations('<?= $_COMPANY->encodeId($eventid);?>')"><?= gettext("Close");?></button>
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
<script>
$('#orgModalForm').on('shown.bs.modal', function () {
   $('#modal-title').trigger('focus')
});
</script>
<script>
    $( ".datePicker" ).datepicker({
        prevText:"click for previous months",
        nextText:"click for next months",
        showOtherMonths:true,
        selectOtherMonths: true,
        maxDate: 0,
        dateFormat: 'yy-mm-dd'
    });
</script>
<script>
    <?php if($organization_id){ ?>
        <?php if (!empty($latestOrgData[0]['ceo_dob'])) { ?>
            $("#ceo_dob").datepicker("disable");
        <?php }?>

        <?php if (!empty($latestOrgData[0]['cfo_dob'])) { ?>
            $("#cfo_dob").datepicker("disable");
        <?php } ?>  
        
        // sync for hidden fields
        $(".datepicker").on("change", function(){
            const hiddenInputId = "#hidden_"+$(this).attr("id");
            $(hiddenInputId).val($(this).val());
        });

        <?php if (!empty($latestOrgData[0]['bm1_dob'])) { ?>
            $("#bm1_dob").datepicker("disable");
        <?php } ?>  
        <?php if (!empty($latestOrgData[0]['bm2_dob'])) { ?>
            $("#bm2_dob").datepicker("disable");
        <?php } ?>  
        <?php if (!empty($latestOrgData[0]['bm3_dob'])) { ?>
            $("#bm3_dob").datepicker("disable");
        <?php } ?>  
        <?php if (!empty($latestOrgData[0]['bm4_dob'])) { ?>
            $("#bm4_dob").datepicker("disable");
        <?php } ?>  
        <?php if (!empty($latestOrgData[0]['bm5_dob'])) { ?>
            $("#bm5_dob").datepicker("disable");
        <?php } ?>  
        
    <?php } ?>
</script>
<script>
    $(document).ready(function(){
          // For autocomplete on name and org id
          $('#organization_name').autocomplete({

            source: function(request, response){
                $.ajax({
                    url: 'ajax.php?searchOrganizations',
                    type: 'POST',
                    data:{searchTerm: request.term, searchField: 'organization_name'},
                    success: function(data){
                        const suggestions = JSON.parse(data);
                        response(suggestions.map(item => ({
                                label : item.label,
                                value : item.value,
                                organization_id : item.organization_id,
                                organization_not_approved : item.organization_not_approved,
                                orgid: item.orgid,
                                organization_name : item.organization_name,
                                organization_taxid: item.organization_taxid,
                                organization_url: item.org_url,
                                organization_type: item.organization_type,
                                is_claimed: item.is_claimed,
                                street: item.organisation_street,
                                city: item.city,
                                state: item.state,
                                country: item.country,
                                zipcode: item.zipcode,
                                organization_contact_firstname: item.organization_contact_firstname,
                                organization_contact_lastname: item.organization_contact_lastname,
                                contact_email: item.contact_email, 
                                ceo_firstname: item.ceo_firstname,
                                ceo_lastname: item.ceo_lastname,
                                ceo_dob: item.ceo_dob,
                                cfo_firstname: item.cfo_firstname,
                                cfo_lastname: item.cfo_lastname,
                                cfo_dob: item.cfo_dob,
                                bm1_firstname: item.bm1_firstname,
                                bm1_lastname: item.bm1_lastname,
                                bm1_dob: item.bm1_dob,
                                bm2_firstname: item.bm2_firstname,
                                bm2_lastname: item.bm2_lastname,
                                bm2_dob: item.bm2_dob,
                                bm3_firstname: item.bm3_firstname,
                                bm3_lastname: item.bm3_lastname,
                                bm3_dob: item.bm3_dob,
                                bm4_firstname: item.bm4_firstname,
                                bm4_lastname: item.bm4_lastname,
                                bm4_dob: item.bm4_dob,
                                bm5_firstname: item.bm5_firstname,
                                bm5_lastname: item.bm5_lastname,
                                bm5_dob: item.bm5_dob,
                                organization_mission_statement: item.organization_mission_statement,
                                company_organization_notes: item.company_organization_notes,
                        })));
                    },
                    error: function(){
                        console.error('Autocomplete failed to fetch data');
                    }
                });
            }, //debounce delay
            minLength: 3,
            select: function(event, ui){
                $('#organization_id').val(ui.item.organization_id);
                $('#org_id').val(ui.item.orgid);
                $('#organization_taxid').val(ui.item.organization_taxid);
                $('#organization_name').val(ui.item.organization_name);
                $('#organization_url').val(ui.item.organization_url);

                $('#organization_type').val(ui.item.organization_type);
                
                $('#address_city').val(ui.item.city);
                $('#address_state').val(ui.item.state);
                $('#address_street').val(ui.item.street);
                $('#address_country').val(ui.item.country);
                $('#address_zipcode').val(ui.item.zipcode);

                $('#contact_firstname').val(ui.item.organization_contact_firstname);
                $('#contact_lastname').val(ui.item.organization_contact_lastname);
                $('#contact_email').val(ui.item.contact_email);

                $('#ceo_firstname').val(ui.item.ceo_firstname);
                $('#ceo_lastname').val(ui.item.ceo_lastname);
                $('#ceo_dob').val(ui.item.ceo_dob);
                $('#cfo_firstname').val(ui.item.cfo_firstname);
                $('#cfo_lastname').val(ui.item.cfo_lastname);
                $('#cfo_dob').val(ui.item.cfo_dob);
                $('#bm1_firstname').val(ui.item.bm1_firstname);
                $('#bm1_lastname').val(ui.item.bm1_lastname);
                $('#bm1_dob').val(ui.item.bm1_dob);
                $('#bm2_firstname').val(ui.item.bm2_firstname);
                $('#bm2_lastname').val(ui.item.bm2_lastname);
                $('#bm2_dob').val(ui.item.bm2_dob);
                $('#bm3_firstname').val(ui.item.bm3_firstname);
                $('#bm3_lastname').val(ui.item.bm3_lastname);
                $('#bm3_dob').val(ui.item.bm3_dob);
                $('#bm4_firstname').val(ui.item.bm4_firstname);
                $('#bm4_lastname').val(ui.item.bm4_lastname);
                $('#bm4_dob').val(ui.item.bm4_dob);
                $('#bm5_firstname').val(ui.item.bm5_firstname);
                $('#bm5_lastname').val(ui.item.bm5_lastname);
                $('#bm5_dob').val(ui.item.bm5_dob);
                $('#organization_mission_statement').val(ui.item.organization_mission_statement);
                $('#company_organization_notes').val(ui.item.company_organization_notes);
              

                const fieldsToDisable = [
                '#organization_taxid',
                '#organization_name',
                '#organization_url',
                '#organization_type',
                '#address_city',
                '#address_state',
                '#address_street',
                '#address_country',
                '#address_zipcode',
                '#contact_firstname',
                '#contact_lastname',
                '#contact_email',
                '#ceo_firstname',
                '#ceo_lastname',
                '#ceo_dob',
                '#cfo_firstname',
                '#cfo_lastname',
                '#cfo_dob',
                '#bm1_firstname',
                '#bm1_lastname',
                '#bm1_dob',
                '#bm2_firstname',
                '#bm2_lastname',
                '#bm2_dob',
                '#bm3_firstname',
                '#bm3_lastname',
                '#bm3_dob',
                '#bm4_firstname',
                '#bm4_lastname',
                '#bm4_dob',
                '#bm5_firstname',
                '#bm5_lastname',
                '#bm5_dob',
                ];

                if (ui.item.is_claimed || ui.item.organization_not_approved) {
                    $(fieldsToDisable.join(", ")).prop("readonly", true).addClass("exclude");
                    $('#organization_type').addClass('readonly-select');

                    $("#ceo_dob").datepicker("disable");
                    $("#cfo_dob").datepicker("disable");
                    $("#bm1_dob").datepicker("disable");
                    $("#bm2_dob").datepicker("disable");
                    $("#bm3_dob").datepicker("disable");
                    $("#bm4_dob").datepicker("disable");
                    $("#bm5_dob").datepicker("disable");
                    $('.show_if_claimed').show();
                    if (ui.item.company_organization_notes) {
                        $('.show_if_claimed2').show();
                        $("#company_organization_notes").prop("readonly", true);
                    }

                    if (ui.item.organization_not_approved) {
                        $("#submitButton").hide();
                        $("#not_approved").show();
                    }
                }
                return false;
            }    
        }); 

        $('#organization_taxid').autocomplete({
            source: function(request, response){
                $.ajax({
                    url: 'ajax.php?searchOrganizations',
                    type: 'POST',
                    data:{searchTerm: request.term, searchField: 'organization_taxid'},
                    success: function(data){
                        const suggestions = JSON.parse(data);
                        response(suggestions.map(item => ({
                                label : item.label,
                                value : item.value,
                                organization_id : item.organization_id,
                                organization_not_approved : item.organization_not_approved,
                                orgid: item.orgid,
                                organization_name : item.organization_name,
                                organization_taxid: item.organization_taxid,
                                organization_url: item.org_url,
                                organization_type: item.organization_type,
                                is_claimed: item.is_claimed,
                                street: item.organisation_street,
                                city: item.city,
                                state: item.state,
                                country: item.country,
                                zipcode: item.zipcode,
                                organization_contact_firstname: item.organization_contact_firstname,
                                organization_contact_lastname: item.organization_contact_lastname,
                                contact_email: item.contact_email, 
                                ceo_firstname: item.ceo_firstname,
                                ceo_lastname: item.ceo_lastname,
                                ceo_dob: item.ceo_dob,
                                cfo_firstname: item.cfo_firstname,
                                cfo_lastname: item.cfo_lastname,
                                cfo_dob: item.cfo_dob,
                                bm1_firstname: item.bm1_firstname,
                                bm1_lastname: item.bm1_lastname,
                                bm1_dob: item.bm1_dob,
                                bm2_firstname: item.bm2_firstname,
                                bm2_lastname: item.bm2_lastname,
                                bm2_dob: item.bm2_dob,
                                bm3_firstname: item.bm3_firstname,
                                bm3_lastname: item.bm3_lastname,
                                bm3_dob: item.bm3_dob,
                                bm4_firstname: item.bm4_firstname,
                                bm4_lastname: item.bm4_lastname,
                                bm4_dob: item.bm4_dob,
                                bm5_firstname: item.bm5_firstname,
                                bm5_lastname: item.bm5_lastname,
                                bm5_dob: item.bm5_dob,
                                organization_mission_statement: item.organization_mission_statement,
                                company_organization_notes: item.company_organization_notes,
                        })));
                    },
                    error: function(){
                        console.error('Autocomplete failed to fetch data');
                    }
                });
            }, 
            minLength: 3,
            select: function(event, ui){
                $('#organization_id').val(ui.item.organization_id);
                $('#org_id').val(ui.item.orgid);
                $('#organization_taxid').val(ui.item.organization_taxid);
                $('#organization_name').val(ui.item.organization_name);
                $('#organization_url').val(ui.item.organization_url);
                $('#organization_type').val(ui.item.organization_type);
                
                $('#address_city').val(ui.item.city);
                $('#address_state').val(ui.item.state);
                $('#address_street').val(ui.item.street);
                $('#address_country').val(ui.item.country);
                $('#address_zipcode').val(ui.item.zipcode);

                $('#contact_firstname').val(ui.item.organization_contact_firstname);
                $('#contact_lastname').val(ui.item.organization_contact_lastname);
                $('#contact_email').val(ui.item.contact_email);

                $('#ceo_firstname').val(ui.item.ceo_firstname);
                $('#ceo_lastname').val(ui.item.ceo_lastname);
                $('#ceo_dob').val(ui.item.ceo_dob);
                $('#cfo_firstname').val(ui.item.cfo_firstname);
                $('#cfo_lastname').val(ui.item.cfo_lastname);
                $('#cfo_dob').val(ui.item.cfo_dob);

                $('#bm1_firstname').val(ui.item.bm1_firstname);
                $('#bm1_lastname').val(ui.item.bm1_lastname);
                $('#bm1_dob').val(ui.item.bm1_dob);
                $('#bm2_firstname').val(ui.item.bm2_firstname);
                $('#bm2_lastname').val(ui.item.bm2_lastname);
                $('#bm2_dob').val(ui.item.bm2_dob);
                $('#bm3_firstname').val(ui.item.bm3_firstname);
                $('#bm3_lastname').val(ui.item.bm3_lastname);
                $('#bm3_dob').val(ui.item.bm3_dob);
                $('#bm4_firstname').val(ui.item.bm4_firstname);
                $('#bm4_lastname').val(ui.item.bm4_lastname);
                $('#bm4_dob').val(ui.item.bm4_dob);
                $('#bm5_firstname').val(ui.item.bm5_firstname);
                $('#bm5_lastname').val(ui.item.bm5_lastname);
                $('#bm5_dob').val(ui.item.bm5_dob);
                $('#organization_mission_statement').val(ui.item.organization_mission_statement);
                $('#company_organization_notes').val(ui.item.company_organization_notes);
                

                const fieldsToDisable = [
                 '#organization_taxid',
                '#organization_name',
                '#organization_url',
                '#organization_type',
                '#address_city',
                '#address_state',
                '#address_street',
                '#address_country',
                '#address_zipcode',
                '#contact_firstname',
                '#contact_lastname',
                '#contact_email',
                '#ceo_firstname',
                '#ceo_lastname',
                '#ceo_dob',
                '#cfo_firstname',
                '#cfo_lastname',
                '#cfo_dob',
                '#bm1_firstname',
                '#bm1_lastname',
                '#bm1_dob',
                '#bm2_firstname',
                '#bm2_lastname',
                '#bm2_dob',
                '#bm3_firstname',
                '#bm3_lastname',
                '#bm3_dob',
                '#bm4_firstname',
                '#bm4_lastname',
                '#bm4_dob',
                '#bm5_firstname',
                '#bm5_lastname',
                '#bm5_dob',
                ];

                if (ui.item.is_claimed || ui.item.organization_not_approved) {
                    $(fieldsToDisable.join(", ")).prop("readonly", true);
                    $('#organization_type').addClass('readonly-select');

                    $("#ceo_dob").datepicker("disable");
                    $("#cfo_dob").datepicker("disable");
                    $("#bm1_dob").datepicker("disable");
                    $("#bm2_dob").datepicker("disable");
                    $("#bm3_dob").datepicker("disable");
                    $("#bm4_dob").datepicker("disable");
                    $("#bm5_dob").datepicker("disable");
                    $('.show_if_claimed').show();
                    if (ui.item.company_organization_notes) {
                        $('.show_if_claimed2').show();
                        $("#company_organization_notes").prop("readonly", true);
                    }

                    if (ui.item.organization_not_approved) {
                        $("#submitButton").hide();
                        $("#not_approved").show();
                    }
                }
                return false;
            }        
        }); 

        // For dynamic contacts
        var contactCount = '<?= $additional_contact_count ?>';
        var maxContacts = 3;

        function addContactRow(){
            if(contactCount < maxContacts){
                contactCount++;

                var contactRow = `
                <div class="contact-row col-md-12 p-0" id=contact-${contactCount}">
                
                <div class="form-group mb-2 col-md-4">
                    <label for="contact_firstname_${contactCount}">First Name<span style="color: #ff0000;"> *</span></label>
                    <input type="text" class="form-control" name="contacts[${contactCount}][firstname]" id="contact_firstname_${contactCount}" placeholder="First Name" data-required="true">
                </div>

                <div class="form-group mb-2 col-md-4">
                    <label for="contact_lastname_${contactCount}">Last Name<span style="color: #ff0000;"> *</span></label>
                    <input type="text" class="form-control" name="contacts[${contactCount}][lastname]" id="contact_lastname_${contactCount}"  placeholder="Last Name" data-required="true">
                </div>

                <div class="form-group mb-2 col-md-3">
                    <label for="contact_email_${contactCount}">Email<span style="color: #ff0000;"> *</span></label>
                    <input type="email" class="form-control" name="contacts[${contactCount}][email]" id="contact_email_${contactCount}"  placeholder="Email" data-required="true">
                </div>

                <a href="javascript:void(0)" class="remove-contact-btn col-md-1" style="margin-top:35px;" data-contact-id="${contactCount}"><i class="fa fa-times-circle fa-lg" aria-hidden="true"></i></a>
                `;

                $('#contact-list').append(contactRow);
            }else{
                swal.fire({title: 'Notice',text:'You can only add upto 3 contacts'})
            }
        }

        // Add contact button handle
        $('#add-contact-btn').on('click', function(){
            addContactRow();
        });

        $('#contact-list').on('click', '.remove-contact-btn', function(){
            // var contactId = $(this).data('contact-id');
            $(this).parent('.contact-row').remove();
            contactCount--
        });
    });
    function clearAllFields(){
        const form = document.getElementById("orgForm");
            const fieldsToDisable = [
                '#organization_taxid',
                '#organization_name',
                '#organization_url',
                '#address_city',
                '#address_state',
                '#address_street',
                '#address_country',
                '#address_zipcode',
                '#contact_firstname',
                '#contact_lastname',
                '#contact_email',
                '#ceo_firstname',
                '#ceo_lastname',
                '#ceo_dob',
                '#cfo_firstname',
                '#cfo_lastname',
                '#cfo_dob',
                '#bm1_firstname',
                '#bm1_lastname',
                '#bm1_dob',
                '#bm2_firstname',
                '#bm2_lastname',
                '#bm2_dob',
                '#bm3_firstname',
                '#bm3_lastname',
                '#bm3_dob',
                '#bm4_firstname',
                '#bm4_lastname',
                '#bm4_dob',
                '#bm5_firstname',
                '#bm5_lastname',
                '#bm5_dob',
                '#organization_mission_statement',
                ];
                $(fieldsToDisable.join(", ")).prop("readonly", false);
                $(fieldsToDisable.join(", ")).prop("disabled", false);
                $('#organization_type').removeClass('readonly-select');
                $('#orgForm')[0].reset();
                $(".datePicker").datepicker("enable");
                $("#organization_id").val('<?=$_COMPANY->encodeId(0)?>');
                $("#org_id").val('<?=$_COMPANY->encodeId(0)?>');
                $('.show_if_claimed').hide();
                $('.show_if_claimed2').hide();
                $("#not_approved").hide();
                $("#submitButton").show();
    }
</script>