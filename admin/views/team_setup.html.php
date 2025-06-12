<style>

    .nav-tabs .nav-link:focus, .nav-tabs .nav-link:hover {
        border-color: #ffff #ffff #ffff;
    }
    .nav-tabs .nav-link.active:focus, .nav-tabs .nav-link.active:hover {
        border-color: #e9ecef #e9ecef #dee2e6
    }
    .nav-item:hover{
        cursor: pointer;
    }

    .switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 22px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 13px;
        width: 13px;
        left: 7px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked + .slider {
        background-color: #0077B5;
    }

    input:focus + .slider {
        box-shadow: 0 0 1px #0077B5;
    }

    input:checked + .slider:before {
        -webkit-transform: translateX(13px);
        -ms-transform: translateX(13px);
        transform: translateX(13px);
    }

    /* Rounded sliders */
    .slider.round {
        border-radius: 17px;
    }

    .slider.round:before {
        border-radius: 50%;
    }
</style>
AMAN
<div class="container col-md-offset-2 margin-top">
    <div class="row">
        <div class="col-md-12">
            <div class="widget-simple-chart card-box">
                <div class="col-md-12">
                    <h5 ><?= $pagetitle; ?>&nbsp;</h5>
                    <hr style="width: 100%; margin-top: 1rem;">
                </div>
                

                <div class="clearfix"></div>

                <?php if((time()- @$_SESSION['updated']) < 5){ ?>
                <div id="hidemesage" class="alert alert-info alert-dismissable">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">x</a>
                    <?= UPDATED; ?>
                </div>
                <?php }elseif((time()- @$_SESSION['added']) < 5) { ?>
                <div id="hidemesage" class="alert alert-info alert-dismissable">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">x</a>
                    <?= ADDED; ?>
                </div>
                <?php }elseif((time()- @$_SESSION['error']) < 5){ ?>
                <div id="hidemesage" class="alert alert-danger alert-dismissable">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">x</a>
                        <?= ERROR; ?>
                    </div>
                <?php } ?>

                <?php if (!$group->isTeamsModuleAllowed()) { ?>
                <div class="col-md-12 mt-2 mb-5 alert-error p-4">
                    <h5>
                        <?= $_COMPANY->getAppCustomization()['teams']['name']." module is not available for this group"; ?>
                    </h5>
                    <p class="mt-3">
                        The <?=$_COMPANY->getAppCustomization()['teams']['name']?> feature is available only for:
                        <ul>
                            <li>Talent Peak Programs, or</li>
                            <li>Affinity Groups that meet the following criteria:
                                <ul>
                                    <li>Have open membership</li>
                                    <li>Have anonymous joining disabled</li>
                                    <li>Do not have membership join restrictions</li>
                                </ul>
                            </li>
                        </ul>
                    </p>
                </div>

                <?php } else { ?>

                <div class="col-md-12 my-2">

                    <?php if (0) { ?>
                    <h5 class="alert-secondary p-4">
                        <?= $_COMPANY->getAppCustomization()['teams']['name']." setting feature has been moved to <strong>" . ucwords($_ZONE->val('app_type')) . ' > ' . $_COMPANY->getAppCustomization()['group']['name-short']. " > Manage </strong> section. Please configure the settings from there."; ?>
                    </h5>
                    <?php } ?>
                

                <div class="my-4">
                   
                    <h5><?= "Enable or Disable {$_COMPANY->getAppCustomization()['teams']['name-plural']} Module"?></h5>
                    <div class="col-md-12 my-3">
                        <p>
                            The <?=$_COMPANY->getAppCustomization()['teams']['name-plural']?> module can be enabled at the <?=$_COMPANY->getAppCustomization()['group']['name-short']?> level.
                        </p>
                        <p>
                            Enabling / Disabling the <?=$_COMPANY->getAppCustomization()['teams']['name-plural']?> module will add / remove access to all <?=$_COMPANY->getAppCustomization()['teams']['name-plural']?> and <?=$_COMPANY->getAppCustomization()['teams']['name']?>-related features for users of this <?=$_COMPANY->getAppCustomization()['group']['name-short']?>, respectively.
                        </p>
                        <span>
                            <strong> Enable Teams&emsp; : &emsp;</strong>
                            <label class="switch">
                                <input
                                    type="checkbox"
                                    <?= $group->isTeamsModuleActivated() ? 'checked': '' ?>
                                    onchange="enableTeamsToggle(this, '<?= $_COMPANY->encodeId($groupid); ?>')"
                                >
                                <span class="slider round"></span>
                            </label>
                        </span>
                    </div>

                    <hr style="width: 100%;">

                    <h5><?= gettext('Manage Join Request Survey Response Settings') ?></h5>
                    <div class="col-md-12 my-3">
                        <p>Member Join Request survey data may contain sensitive or PII information. You can disallow <?=strtolower($_COMPANY->getAppCustomization()['group']['name-short'])?> leaders from downloading this data by toggling the switch below.
                            However, if you want your <?=strtolower($_COMPANY->getAppCustomization()['group']['name-short'])?> leaders to use the Team Builder function, you will need to grant them access to this data, as Team Builder needs access to the data.
                        </p>
                        <span>
                            <strong>Allow <?=strtolower($_COMPANY->getAppCustomization()['group']['name-short'])?> leaders to download the join request survey data&emsp; : &emsp;</strong>
                            <label class="switch">
                                <input
                                    type="checkbox"
                                    id="settingCheckbox"
                                    <?= !empty($surveyDownloadSetting) && $surveyDownloadSetting['allowed'] == 1 ? 'checked' : ''; ?>
                                    onchange="confirmationInput(this)">
                                <span class="slider round"></span>
                            </label>
                        </span>
                    </div>

                    <div class="col-md-12" id="reportTeamMemberJoinSurveyResponseDownloadOptions" style="display: none;"></div>
                    <?php if($group->val('isactive') == 1){ ?>
                        <div class="col-md-12 my-3">
                            <p>If you need to download the survey data or delete the survey data permanently, you can do so below.</p>
                            <button type="button" class="btn btn-primary mr-3" onclick="getTeamsJoinRequestSurveyReportOptions('<?= $_COMPANY->encodeId($groupid); ?>')"><?= gettext('Download Survey Data') ?></button>
                            <button class="btn btn-danger" type="button" onclick="deleteSurveyDataConfirmation('<?= $_COMPANY->encodeId($groupid); ?>')"><?= gettext('Delete Survey Data') ?></button>
                        </div>
                    <?php } ?>

                    <hr style="width: 100%;">

                </div>

                <div id="confirmChangeModal" class="modal fade" role="dialog">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header text-center">
                                <h2 class="modal-title"><?= gettext("Confirmation"); ?></h2>
                            </div>
                            <div class="modal-body">
                                <p><?= gettext("I understand the survey may contain senstive data and allowing survey data to be downloaded is ok. Type 'I agree' below to provide your consent.");?></p>
                                <div class="form-group">
                                    <label><?= gettext("Confirmation"); ?></label>
                                    <input type="text" class="form-control" id="confirmChange" onkeyup="initDeleteAccount()" placeholder="I agree" name="confirmChange">
                                  </div>
                            </div>
                            <div class="modal-footer text-center">
                                <span id="action_button"><button class="btn btn-primary" disabled >Submit</button></span>
                                <button type="button" class="btn btn-info" onclick="$('#settingCheckbox').prop('checked', false);" data-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="deleteSurveyDataConfirmationModal" class="modal fade" role="dialog">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header text-center">
                                <h2 class="modal-title"><?= gettext("Confirmation"); ?></h2>
                            </div>
                            <div class="modal-body">
                                <p><?= gettext('I understand the survey response data will be immediately and permanently deleted. By typing "<b>Yes, permanently delete the survey responses</b>" below, I am providing my consent to delete the responses!');?></p>
                                <div class="form-group">
                                    <label><?= gettext("Confirmation"); ?>:</label>
                                    <input type="text" class="form-control" id="confirmChangeSurvey" onkeyup="initDeleteSurveyResponse()" placeholder="" name="confirmChangeSurvey">
                                  </div>
                            </div>
                            <div class="modal-footer text-center">
                                <span id="action_button_survey"><button class="btn btn-outline-danger" disabled >Submit</button></span>
                                <button type="button" class="btn btn-info" data-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
<script>
    function deleteSurveyDataConfirmation(){
        $('#deleteSurveyDataConfirmationModal').modal({
            backdrop: 'static',
            keyboard: false
        });
    }
    function initDeleteSurveyResponse(){
        var v = $("#confirmChangeSurvey").val();
        if (v =='Yes, permanently delete the survey responses'){
            $("#action_button_survey").html('<button class="btn btn-danger" onclick="deleteTeamJoinRequestSurveyData(\'<?= $_COMPANY->encodeId($groupid);?>\');" >Submit</button>');
        } else {
            $("#action_button_survey").html('<button class="btn btn-outline-danger no-drop" disabled >Submit</button>');
        }
    }

    function confirmationInput(v){
        if ($(v).is(':checked')) {
            $('#confirmChangeModal').modal({
				backdrop: 'static',
				keyboard: false
			});
        } else {
            updateSurveyDownloadSetting('<?= $_COMPANY->encodeId($groupid);?>',0);
        }
    }

    function initDeleteAccount(){
        var v = $("#confirmChange").val();
        if (v =='I agree'){
            $("#action_button").html('<button class="btn btn-primary" onclick="updateSurveyDownloadSetting(\'<?= $_COMPANY->encodeId($groupid);?>\',1);" >Submit</button>');
        } else {
            $("#action_button").html('<button class="btn btn-primary no-drop" disabled >Submit</button>');
        }
    }

    function updateSurveyDownloadSetting(g,s){
        $("#confirmChange").val('');
        $.ajax({
            url: 'ajax.php?updateSurveyDownloadSetting=1',
            type: "POST",
            data: {'groupid':g,'status':s},
            success : function(data) {
                swal.fire({title: 'Success',text:'Updated successfully'});
                $('#confirmChangeModal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            }
        });
    }

    function enableTeamsToggle(checkbox, groupid) {
        $.ajax({
            url: `team_setup.php?groupid=${groupid}`,
            type: 'POST',
            data: {
                enable_teams: Number(checkbox.checked)
            },
            success : function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title,text:jsonData.message}).then(function () {
                        if (jsonData.title === 'Error') {
                            location.reload();
                        }
                    });
                } catch(e) {
                    swal.fire({title: 'Error', text: "Unknown error."});
                }
            }
        });
    }

</script>

                <?php } ?>
                <div class="col-md-2">
                    <a class="btn btn-info btn-sm" href="group" >Back</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
