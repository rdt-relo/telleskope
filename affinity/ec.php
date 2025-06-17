<?php
/**
 * This file is deprecated. Use ec2.php
 * Delete this file after May 1, 2022 as no more references will be left after that date.
 */
define('INDEX_PAGE', 1);

require_once __DIR__ . '/head.php';
global $_COMPANY;

$error_message = '';
$success_message = '';
$allow_join = 1;

$urlcompany = Company::GetCompanyByUrl('https://' . $_SERVER['HTTP_HOST']);
if ($urlcompany=== null) {
    Logger::Log("Event Checkin Fatal Error 1401: Invalid URL host {$_SERVER['HTTP_HOST']}");
    header(HTTP_NOT_FOUND);
    die('Event Checkin Error 1401: Invalid URL host');
}

if ($_COMPANY && ($urlcompany->id() !== $_COMPANY->id())) {
    Logger::Log("Event Checkin Secuirty Error 1402: Invalid URL host {$_SERVER['HTTP_HOST']} in context of {$_COMPANY->val('subdomin')} company");
    header(HTTP_UNAUTHORIZED);
    die('Event Checkin Error 1402: Invalid URL host');
}

if (empty($_GET['e']) ||
    ($eventid = $urlcompany->decodeId($_GET['e'])) < 1
) {
    Logger::Log("Event Checkin Error 1403: Invalid URL, missing or incorrect event identifer");
    header(HTTP_NOT_FOUND);
    die('Event Checkin Error 1403: Invalid URL, missing event identifer');
}
    //(!$urlcompany->custom['affinity']['event']['enabled']) ||
    //(!$urlcompany->custom['affinity']['event']['checkin']) ||

if (($event = Event::GetEventByCompany($urlcompany, $eventid)) === null ||
    $event->isInactive()) {
    Logger::Log("Event Checkin Error 1405: Unable to locate the event ({$eventid}) for checkin");
    header(HTTP_NOT_FOUND);
    die('Event Checkin Error 1405: Unable to locate the event for checkin - event may have been cancelled, please check with the event organizer.');
} else{
    if ($event->hasCheckinEnded()) {
        $error_message = 'You are too late ... checkin has ended';
        $allow_join = 0;
    } elseif (!$event->hasCheckinStarted()) {
        $error_message = 'You are a bit early. Check-in will be available one hour before the meeting begins.';
        $allow_join = 0;
    } else {
        if (isset($_POST['email'])) {

            if (!verify_recaptcha()) {
                $error_message = "Incorrect reCAPTCHA, try again";
            } else {
                $firstname = rtrim($_POST['firstname']);
                $lastname = rtrim($_POST['lastname']);
                $invalid = '';
                if (empty($firstname)){
                    $invalid .= ' Firstname,';
                }

                if (empty($lastname)){
                    $invalid .= ' Lastname,';
                }

                if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
                    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                } else {
                    $invalid .= ' Email,';
                }
                if (!empty($invalid)) {
                    $error_message = 'Missing or invalid ' . rtrim($invalid, ',');
                }

                if (empty($error_message)) {
                    if ($event->nonAuthenticatedCheckin($firstname, $lastname, $email,'Self QR Code', null)){
                        $success_message = "You are checked in!";
                    }
                }
            }
        }
    }
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>Event Check In</title>

    <!-- Bootstrap -->
    <link href="<?=TELESKOPE_.._STATIC?>/vendor/js/bootstrap-4.4.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?=TELESKOPE_.._STATIC?>/vendor/css/animate-3.7.2.min.css" rel="stylesheet">

    <!-- Teleskope styles, comes after bootstrap from parent folder -->
    <link href="../css/teleskope.css" rel="stylesheet">
    <!-- User Application specific style guides from project folder -->

    <!-- Bootstrap ... in the last -->
    <script src="<?=TELESKOPE_.._STATIC?>/vendor/js/jquery-3.5.1/dist/jquery.min.js"></script>
    <script src="<?=TELESKOPE_.._STATIC?>/vendor/js/bootstrap-4.4.1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweeetAlert -->
    <link href="<?=TELESKOPE_.._STATIC?>/vendor/js/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet"/>
    <script src="<?=TELESKOPE_.._STATIC?>/vendor/js/sweetalert2/dist/sweetalert2.min.js"></script>

    <!-- Google Recaptcha v2 -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        function onRecaptchaSubmit(token) {
            document.getElementById("manual_checkin_form").submit();
        }
    </script>
</head>

<style>
    body, html {
        height: 100%;
        min-height: 100%;
    }

    #user-action-box {
        width: 412px;
        padding: 20px;
        background: #fff;
        border-radius: 5px;
        margin: 5% auto;
    }

    .head-logo {
        text-align: center;
        padding-bottom: 30px;
    }

    .head-logo .title {
        font-weight: bold;
        padding-top: 5%;
    }

    .event-details {
        padding: 20px 5px;
        margin: 10px 0;
        background-color: #ececec;
        border-radius: 5px;
        border-style: dashed;
        border-color: #dcd7d7;
    }

    .event-title {
        text-align: center;
        font-size: large;
    }

    .checkin-buttons {
        margin: 20px 0;
    }

    #footer-logo {
        background: url("../image/power-teleskope-blue.png") no-repeat center;
        background-size: contain;
        height: 30px;
        margin: 10px 0 0 0;
    }

    .form-horizontal {
        margin: 20px 0;
    }

    .form-horizontal .control-label {
        font-weight: 400;
        text-align: left;
        font-size: 14px;
    }

    .btn.btn-primary.btn-block {
        height: 45px;
    }

    .btn-link {
        color: #FFFFFF !important;
        width: 100% !important;
        margin: 10px 0;
    }

    .form-control {
        height: 45px !important;
    }

    .user-action-box-bottom-group {
        border-top: 1px solid #888;
        margin-top: 15px;
        padding-top: 15px;
        font-size: .8rem;
    }

    /* Hide the reCaptcha v2 badge */
    .grecaptcha-badge {
        visibility: hidden;
    }
    .grecaptcha-label {
        text-align: center;
        margin-bottom: 20px;
        font-size: 12px;
    }

    /* For > bootstrap sm */

    @media (max-width: 414px) {
        #user-action-box {
            width: auto;
        }
    }
</style>

<body style="background: url('<?= $urlcompany->val('loginscreen_background'); ?>') no-repeat; background-size: cover;">

<div class="container">
    <div id="user-action-box" class="card card-container wow zoomIn animated">
        <div class="head-logo">
            <img src="<?= $urlcompany->val('logo'); ?>" alt="Company logo" height="40px"/>
            <div class="title">Event Check In</div>
        </div>

        <div class="event-details">
            <div class="event-title"><?= $event->val('eventtitle') ?></div>
        </div>

        <?php if (!empty($error_message)) { ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php } ?>

        <?php if (!empty($success_message)) {
            if ($event->val('checkin_enabled') && !empty($event->val('web_conference_link'))) {
                $button_text = 'Next';
                $next_link = "window.location.href = '{$event->val("web_conference_link")}'";
            } else {
                $button_text = 'Reload';
                $next_link = 'window.location.reload()';
            }
        ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    html: '<?=$success_message;?>',
                    showConfirmButton: true,
                    confirmButtonText: '<?=$button_text?>'
                }).then((result) => {
                    <?=$next_link?>;
                });
            </script>
        <?php } elseif ($allow_join) { ?>

        <?php if ($_USER) {
            $checkin_on = $event->getMyCheckinDate(); // Uses $_USER global variable
        ?>
            <div class="checkin-buttons" id="checkin-buttons">
                <?php if (empty($checkin_on)) { ?>
                    <a onclick="auto_checkin('<?= $urlcompany->encodeId($urlcompany->decodeId($_GET['e']))?>');" class="btn btn-primary btn-link center-block">Check In</a>
                <?php } else { ?>
                    <p class="alert-info text-center">You are already checked in</p>
                    <?php if ($event->val('checkin_enabled') && !empty($event->val('web_conference_link'))) { ?>
                        <a href="<?=$event->val("web_conference_link")?>" class="btn btn-primary btn-link center-block">Continue</a>
                    <?php } ?>
                <?php } ?>
            </div>
        <?php } elseif (empty($_POST)) { ?>
            <div class="checkin-buttons" id="checkin-buttons">
                <a href='index?rurl=<?= base64_url_encode("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}") ?>'
                   class="btn btn-primary btn-link center-block">Sign In & Check In</a>
                <a onclick="manual_checkin();" class="btn btn-primary btn-link center-block">Other Check In</a>
            </div>
        <?php } ?>

            <form class="form-horizontal" id="manual_checkin_form" action="" method="post"
                  style="display: <?= empty($_POST) ? 'none' : 'block' ?>;">
                <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
                <p>Please provide your contact information:</p>
                <div class="form-group">
                    <div class="form-check-label">First Name *</div>
                    <input type="text" class="form-control " name="firstname"
                           value="<?= $_POST['firstname'] ?? '' ?>" placeholder="First Name" required>
                </div>
                <div class="form-group">
                    <div class="form-check-label">Last Name *</div>
                    <input type="text" name="lastname" class="form-control"
                           value="<?= $_POST['lastname'] ?? '' ?>" placeholder="Last Name" required>
                </div>

                <div class="form-group">
                    <div class="form-check-label">Email *</div>
                    <input type="text" name="email" class="form-control"
                           value="<?= $_POST['email'] ?? '' ?>" placeholder="Email" required>
                </div>
                <div class="grecaptcha-label">
                    This site is protected by reCAPTCHA and the Google
                    <a href="https://policies.google.com/privacy">Privacy Policy</a> and
                    <a href="https://policies.google.com/terms">Terms of Service</a> apply.
                </div>
                <div class="form-group">
                    <button type="submit" class="prevent-multi-clicks" data-sitekey="<?=RECAPTCHA_SITE_KEY?>" data-callback='onRecaptchaSubmit' class="btn btn-primary btn-block center-block g-recaptcha">
                        Check In
                    </button>
                </div>
            </form>

        <?php } ?>

        <div id="footer-logo"></div>
    </div>
</div>

</body>
<script type="application/javascript">

    // This global function needs to be there to report ajax errors.
    $(document).ajaxError(function (event, jqxhr, settings, thrownError) {
        alert("Error: " + thrownError);
    });
    $(document).ajaxSend(function (elm, xhr, s) {
        if (s.type == "POST") {
            xhr.setRequestHeader('x-csrf-token', '<?=Session::GetInstance()->csrf; ?>');
        }
    });

    //--- Reminder email of event ----//
    function auto_checkin(eid) {
        let success_message = 'You are checked in!';
        $.ajax({
            url: 'ajax_events.php?autoCheckinEvent=1',
            type: "POST",
            data: {eventid: eid},
            success: function (data) {
                if (data == 0) {
                    success_message = 'Check-in in progress';
                }

                Swal.fire({
                    icon: 'success',
                    title: '',
                    html: success_message,
                    showConfirmButton: true,
                    confirmButtonText: 'Continue'
                }).then((result) => {
                <?php if ($event->val('checkin_enabled') && !empty($event->val('web_conference_link'))) { ?>
                    window.location.href = '<?=$event->val("web_conference_link")?>';
                <?php } else { ?>
                    window.location.reload();
                <?php } ?>
                });
            }
        });
    }

    function manual_checkin() {
        grecaptcha.reset();
        $('#checkin-buttons').hide();
        $('#manual_checkin_form').show();
    }
</script>
</html>
