<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title><?=$title?></title>

    <!-- Bootstrap -->
    <!--link href="../css/bootstrap.min.css" rel="stylesheet"-->
    <link href="../vendor/js/bootstrap-4.4.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../vendor/css/animate-3.7.2.min.css" rel="stylesheet">
    <link href="../vendor/js/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet" />

    <!-- Teleskope styles, comes after bootstrap from parent folder -->
    <link href="../css/teleskope.css" rel="stylesheet">
    <!-- User Application specific style guides from project folder -->
    <link href="./css/affinity.css?<?=REL_HASH?>" rel="stylesheet">

    <!-- Bootstrap ... in the last -->
    <script src="../vendor/js/jquery-3.5.1/dist/jquery.min.js"></script>
    <script src="../vendor/js/bootstrap-4.4.1/dist/js/bootstrap.bundle.min.js"></script>
    <script defer type="text/javascript" src="../vendor/js/popconfirm-0.4.3/jquery.popconfirm.tele.2023.11.15.js"></script>
    <script src="../vendor/js/jquery-ui-1.14.0/jquery-ui.min.js"></script>
    <script src="../vendor/js/initial-0.2.0/dist/initial.min.js"></script>
   <script defer src="../vendor/js/datatables-2.1.8/datatables.min.js"></script>
    <script src="../vendor/js/sweetalert2/dist/sweetalert2.min.js"></script>
    <script src="../vendor/js/promise-polyfill/dist/polyfill.min.js"></script>

    <!-- select2 -->
    <link href="../vendor/js/select2-4.0.12/dist/css/select2.min.css" rel="stylesheet" />
    <script defer src="../vendor/js/select2-4.0.12/dist/js/select2.min.js"></script>
    <!-- end of select 2 -->

    <script src="./js/index.js.php?v=<?=REL_HASH?>&lang=<?= Lang::GetSelectedLanguage() ?>"></script>

    <style>
        body, html {
            height: 100%;
            min-height: 100%;
        }
    </style>
</head>

<body style="background: url('<?= empty($_COMPANY) ? '../image/login_background.png' : $_COMPANY->val('loginscreen_background')?>') no-repeat; background-size: cover;">

<div class="container">
    <div class="row">
        <div class="col-md-12">

        <?php
        if ($action == "LOGIN_FIRST_DISCLAIMER" ) {
        ?>
        <script>
             loadDisclaimerByHook('<?=$_COMPANY->encodeId(Disclaimer::DISCLAIMER_HOOK_TRIGGERS['LOGIN_FIRST']) ?>','<?=$_COMPANY->encodeId(0)?>', true,'<?= base64_encode('{}')?>');
        </script>
        <?php }
        ?>

        <?php
        if ($action == "ZONE" ){
            ?>
            <div id="updateZone" class="modal fade">
                <div aria-label="<?= gettext("Update your home zone");?>" class="modal-dialog" aria-modal="true" role="dialog">
                    <!-- Modal content-->
                    <div class="modal-content">
                        <?php if (count($zones)) { ?>
                        <div class="modal-header">
                            <h4 class="modal-title"><?= gettext("Update your home zone");?></h4>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <p><?= gettext("We could not detect your home zone. Please select a zone that you would like to be used as your default home zone.");?></p>
                                    <p>&nbsp;</p>
                                    <div class="form-group">
                                        <label class="control-lable col-sm-3"><?= gettext("Zone");?></label>
                                        <div class="col-sm-9">
                                            <select class="form-control" name="homezone" id="selected_homezone">
                                                <option value=""><?= gettext("Select a Zone");?></option>
                                        <?php   foreach($zones as $zone){ ?>
                                                    <option value="<?= $_COMPANY->encodeId($zone['zoneid']); ?>"><?= $zone['zonename']; ?></option>
                                        <?php   } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mb-5">
                            <button type="button" onclick="updateHomeZone();" class="about-button"><?= gettext("Update Home Zone");?></button>
                        </div>
                        <?php } else { ?>
                            <div class="modal-body">
                                <div class="m-5 text-center">
                                    <p><?= gettext("No zones configured, please contact your account administrator.");?></p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <a href="logout?logout=1" class="btn btn-affinity">Close</a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <script>
                $(window).on('load', function () {
                    $('#updateZone').modal({
                        backdrop: 'static',
                        keyboard: false
                    })
                });
            </script>
        <?php }
        ?>

        <!-- Time Zone Setting -->
        <?php 
        if ($action == "TIME" ) {
            if (empty($_USER->val('timezone'))){
        ?>
        <div id="updateTZ" class="modal fade">
            <div aria-label="<?= gettext("Update Timezone");?>" class="modal-dialog" aria-modal="true" role="dialog">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title"><?= gettext("Update Timezone");?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <p><?= gettext("We could not detect your timezone. Please select a timezone that you would like to be used as your default timezone. If you need to change your default timezone in future you can update it from your profile settings.");?></p>
                                <p>&nbsp;</p>
                                <div class="form-group">
                                    <label class="control-lable col-sm-3"><?= gettext("Timezone");?></label>
                                    <div class="col-sm-9">
                                        <select class="form-control teleskope-select2-dropdown" name="timezone" id="selected_timezone" style="width: 100%;">
                                            <?php echo getTimeZonesAsHtmlSelectOptions(''); ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 text-center mb-5">
                       <button type="button" onclick="updateTimeZone();" class="about-button"><?= gettext("Update Timezone");?></button>

                    </div>
                </div>

            </div>
        </div>
        <script>
            $(window).on('load', function () {
                $('#updateTZ').modal({
                    backdrop: 'static',
                    keyboard: false
                })
            });
        </script>
        <?php 
            } else { 
        ?>
        <div id="differentTZ" class="modal fade">
            <div class="modal-dialog" aria-modal="true" role="dialog">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title"><?= gettext("New timezone found");?></h2>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <p><?= sprintf(gettext("We have detected that you are in a new timezone <strong>%s</strong> and your profile timezone is set to <strong>%s</strong>. Choose one of the following options to continue."),$_SESSION['tz_b'],$_USER->val('timezone'));?> </p>
                            </div>
                        </div>
                    </div>
                    <div class="d-none">
                        <select class="form-control" name="timezone" id="selected_timezone">
                            <option value="<?= $_SESSION['tz_b']; ?>" selected></option>
                        </select>
                    </div>
                    <div class=" col-md-12 text-center mb-5">
                        <button type="button" onclick="useBrowserTimezone('<?= $_SESSION['tz_b']; ?>');" class="about-button">
                            <?= gettext("Use my browsers timezone for this session");?>
                        </button>
                        <button type="button" onclick="updateTimeZone();" class="about-button">
                            <?= gettext("Update my profile with the new timezone");?>
                        </button>
                        <button type="button" class="about-button" onclick="useProfileTimezone('<?= $_USER->val('timezone') ?>');">
                            <?= gettext("Use the timezone set in my profile");?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <script>
            $(window).on('load', function () {
                $('#differentTZ').modal({
                    backdrop: 'static',
                    keyboard: false
                })
            });
        </script>
        <?php 
            }
        }
        ?>

        </div>
    </div>
</div>

<script>
    var teleskopeCsrfToken="<?=Session::GetInstance()->csrf;?>";
    function updateHomeZone(){
        var z = $("#selected_homezone").val();
        if(z){
            $.ajax({
                url: 'update_login_profile',
                type: 'GET',
                data: "set_home_zoneid="+z,
                success: function(data) {
                    try {
                        let jsonData = JSON.parse(data);
                        swal.fire({
                            title: 'Success',
                            text: "<?= gettext('Home zone updated successfully.');?>"
                        }).then(function (result) {
                            window.location.href = '' + jsonData.nextUrl;
                        });
                    } catch(e) {
                        swal.fire({
                            title: 'Error',
                            text: "Unknown error, please try again"
                        }).then(function (result) {
                            location.reload();
                        });
                    }
                }
            });
        }else{
            swal.fire({title: 'Error!',text:'<?= gettext("Select a zone");?>'});
        }
    }

    $(document).ready(function () {
        $(".teleskope-select2-dropdown").select2({width: 'resolve'});
    });
</script>
<div id="loadAnyModal"></div>
</body>
</html>
