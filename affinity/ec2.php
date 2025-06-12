<?php
define('INDEX_PAGE', 1);
define('SKIP_CSRF_CHECK', 1);
require_once __DIR__ . '/head.php';
global $_COMPANY, $_ZONE, $_USER;


/**
 *  Sometimes the URLs have incorrect encoding whch causes PHP to read the paramters incorrectly, e.g.
 * If path is /1/affinity/ec2?e=f1%5fper9rkxz%26u=1, then PHP returns value of 'e' as 'f1_per9rkxz&u=1'
 * This method will update the value of $_GET['e'] and $_GET['u']
 * @return void
 */
function fix_url_get_paramters()
{
    if (isset($_GET['e'])) {
        $get_e = $_GET['e'];
        if (($e_pos = strpos($get_e, '&u=')) > 0) {
            [$get_e, $get_u] = explode('&', $get_e);
            $_GET['e'] = $get_e;
            if ($get_u === 'u=1') {
                $_GET['u'] = 1;
            }
        }
    }
}

/**
 * Usecases:
 * (1) Allow users to check-in and retrieve web-conf URL if event checkin is enabled. This usecase is triggered if
 * u parameter is set (u=1).
 * In this usecase:
 *      (a) if the event is NOT published, then we will always redirect to the web-conf URL,
 *      (b) if event is event is published then we will first check if checkin is open and
 *          (i) If the check is open then we will checkin the user first and then redirect.
 *          (ii) If checkin is not open then we will redirect to the web conf URL as is.
 * (2) Allow users to checkin to in-person events using QR code. This usecase is triggered if
 * u parameter is not set. For this usecase the call flow is to check if the checkin is open, if it is then
 * checkin the user else show an appropriate message.
 */
$error_message = '';
$success_message = '';
$allow_join = 1;

$urlcompany = Company::GetCompanyByUrl('https://' . $_SERVER['HTTP_HOST']);
if ($urlcompany === null) {
    Logger::Log("Event Checkin Fatal Error 1401: Invalid URL host {$_SERVER['HTTP_HOST']}");
    header(HTTP_NOT_FOUND);
    die('Event Checkin Error 1401: Invalid URL');
}

if ($_COMPANY && ($urlcompany->id() !== $_COMPANY->id())) {
    Logger::Log("Event Checkin Security Error 1402: Invalid URL host {$_SERVER['HTTP_HOST']} in context of {$_COMPANY->val('subdomin')} company", Logger::SEVERITY['SECURITY_ERROR']);
    header(HTTP_NOT_FOUND);
    die('Event Checkin Error 1402: Invalid URL');
}

fix_url_get_paramters();

if (empty($_GET['e']) ||
    !str_starts_with($_GET['e'], 'f1_') || // Added to avoid unnecessary logs for bruteforce checks from Proofpoint / Microsoft Defender, Google Abuse checker
    ($eventid = $urlcompany->decodeId($_GET['e'])) < 1 ||
    ($event = Event::GetEventByCompany($urlcompany, $eventid)) === null ||
    ($eventzone = $urlcompany->getZone($event->val('zoneid'))) === null ||
    $event->isInactive()
) {
    $eDetails = '[';
    $eDetails .= $_GET['e'] ?? '.';
    $eDetails .= '|';
    $eDetails .= $eventid ?? '.';
    $eDetails .= '|';
    $eDetails .= isset($event) && $event ? $event->val('isactive') : '.';
    $eDetails .= ']';
    Logger::Log("Event Checkin Error 1403: Invalid URL, invalid event identifer {$eDetails}", Logger::SEVERITY['WARNING_ERROR']);
    header(HTTP_NOT_FOUND);
    die('Event Checkin Error 1403: Invalid URL, invalid event identifer');
}
//(!$urlcompany->custom['affinity']['event']['enabled']) - we will not use zone customization check because if
// user was able to generate check-in link or barcode, it is assumed the feature is turned on.

$checkin_show_signin = $urlcompany->getAppCustomizationForZone($eventzone->id())['event']['checkin_show_signin'];
$redirect_user_to_web_conf_after_checkin = !empty($_GET['u']); //Note: for QR code checkin this value is false

if ($redirect_user_to_web_conf_after_checkin && empty($event->val("web_conference_link"))) {
    Logger::Log("Event Checkin Error 1404: Unable to locate the event web conference URL", Logger::SEVERITY['WARNING_ERROR']);
    header(HTTP_NOT_FOUND);
    die('Event Checkin Error 1404: Unable to locate the event web conference URL, please check with the event organizer.');
}

if (!$event->isPublished()) {
    if ($redirect_user_to_web_conf_after_checkin) {
        Http::Redirect(htmlspecialchars_decode($event->val("web_conference_link")));
    } else {
        Logger::Log("Event Checkin Error 1405: Unable to locate the event ({$eventid}) for checkin", Logger::SEVERITY['WARNING_ERROR']);
        header(HTTP_NOT_FOUND);
        die('Event Checkin Error 1405: Unable to locate the event for checkin');
    }
}

if ($event->hasCheckinEnded()) {
    if ($redirect_user_to_web_conf_after_checkin) {
        Http::Redirect(htmlspecialchars_decode($event->val("web_conference_link")));
    } else {
        $error_message = 'You are too late ... checkin has ended';
        $allow_join = 0;
    }
} elseif (!$event->hasCheckinStarted()) {
    if ($redirect_user_to_web_conf_after_checkin) {
        Http::Redirect(htmlspecialchars_decode($event->val("web_conference_link")));
    } else {
        $error_message = 'You are a bit early. Check-in will be available one hour before the meeting begins.';
        $allow_join = 0;
    }
} else {
    // Event can be checked in.
    $checkin_done = 0;
    if ($_USER) {
        if (/*$event->getMyCheckinDate() ||*/ $event->selfCheckIn()) {
            $success_message = "You are checked in!";
            $checkin_done = 1;
            $allow_join = 0;
        }
    } elseif ($redirect_user_to_web_conf_after_checkin && isset($_COOKIE['evt_check'])) { // For lo
        $encEventCheckinDataVals = $_COOKIE['evt_check'];
        $eventCheckinDataVals = $urlcompany->decryptString2Array($encEventCheckinDataVals);

        /**
         * When a non-logged-in user visits an event's web-conference-link (gmeet/zoom etc) from their calendar OR email OR directly,
         * then we assume he/she is the last-logged-in user
         * and automatically check-in that user to the event
         * and add the EVENT_CHECK_IN points (if any active points-program is running)
         * and redirect the user back to the web-conference-link
         */
        {
            // Save old values
            $prev_company = $_COMPANY ?? null;
            $prev_zone = $_ZONE ?? null;
            $prev_user = $_USER ?? null;

            // load new values
            $_COMPANY = $_COMPANY ?? $urlcompany; // url company is already set by the time we reach here
            $_ZONE = $_ZONE ?? $eventzone; // $eventzone is already set by the time we reach here
            $_USER = $_USER ?? (empty($eventCheckinDataVals['u']) ? null : User::GetUser($eventCheckinDataVals['u']));

            if ($_USER && $event->checkInByUserid($_USER->id(), Event::EVENT_CHECKIN_METHOD['WEB_COOKIE_SIGNIN'])) {
                $success_message = "You are checked in!";
                $checkin_done = 1;
                $allow_join = 0;
            }

            // reset to old values
            $_COMPANY = $prev_company;
            $_ZONE = $prev_zone;
            $_USER = $prev_user;
        }

    } elseif (isset($_POST['email_or_externalid'])) {
        if (!verify_recaptcha()) {
            $error_message = "Incorrect reCAPTCHA, try again";
        } else {
            $firstname = rtrim($_POST['firstname']);
            $lastname = rtrim($_POST['lastname']);
            $invalid = '';
            $email_or_externalid = trim($_POST['email_or_externalid'] ?? '');

            if (empty($firstname)) {
                $invalid .= ' Firstname,';
            }

            if (empty($lastname)) {
                $invalid .= ' Lastname,';
            }

            if (empty($email_or_externalid)) {
                $invalid .= " Email/{$urlcompany->getCompanyCustomization()['user_field']['externalid']['name']},";
            } else {
                if (filter_var($email_or_externalid, FILTER_VALIDATE_EMAIL)) {
                    $email = filter_var($email_or_externalid, FILTER_SANITIZE_EMAIL);
                    $externalid = null;
                } else {
                    // If email is invalid, then the user must have provided the external-ID
                    $email = '';
                    $externalid = $email_or_externalid;
                }
            }

            if (!empty($invalid)) {
                $error_message = 'Missing or invalid ' . rtrim($invalid, ',');
            }

            if (empty($error_message)) {
                // Save old values
                $prev_company = $_COMPANY ?? null;
                $prev_zone = $_ZONE ?? null;

                // load new values
                $_COMPANY = $_COMPANY ?? $urlcompany; // url company is already set by the time we reach here
                $_ZONE = $_ZONE ?? $eventzone; // $eventzone is already set by the time we reach here

                // Data is valid, check if the user has already checked in
                if ($event->nonAuthenticatedCheckin($firstname, $lastname, $email,'Self QR Code', $externalid)) {
                    $success_message = "User with email {$email} is checked in!";
                    $checkin_done = 1;
                    $allow_join = 0;
                } else {
                    $error_message = 'There was an error during check-in. Please verify your input and try again.';
                }

                $_COMPANY = $prev_company;
                $_ZONE = $prev_zone;
            }
        }
    }

    if ($redirect_user_to_web_conf_after_checkin && $checkin_done) { // For web URL's redirect immediately after the checkin is done.
        Http::Redirect(htmlspecialchars_decode($event->val("web_conference_link")));
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

    <link rel="icon" href="data:,">
    <!-- Bootstrap -->
    <link href="<?= TELESKOPE_CDN_STATIC ?>/vendor/js/bootstrap-4.4.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= TELESKOPE_CDN_STATIC ?>/vendor/css/animate-3.7.2.min.css" rel="stylesheet">

    <!-- Teleskope styles, comes after bootstrap from parent folder -->
    <link href="../css/teleskope.css" rel="stylesheet">
    <!-- User Application specific style guides from project folder -->

    <!-- Bootstrap ... in the last -->
    <script src="<?= TELESKOPE_CDN_STATIC ?>/vendor/js/jquery-3.5.1/dist/jquery.min.js"></script>
    <script src="<?= TELESKOPE_CDN_STATIC ?>/vendor/js/bootstrap-4.4.1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweeetAlert -->
    <link href="<?= TELESKOPE_CDN_STATIC ?>/vendor/js/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet"/>
    <script src="<?= TELESKOPE_CDN_STATIC ?>/vendor/js/sweetalert2/dist/sweetalert2.min.js"></script>

    <!-- Google Recaptcha v2 -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        function onRecaptchaSubmit(token) {
            document.getElementById("manual_checkin_form").submit();
        }
    </script>
</head>

<style>
    body, html { height: 100%; min-height: 100%;}
    #user-action-box { width: 412px; padding: 20px; background: #fff; border-radius: 5px; margin: 5% auto;}
    .head-logo { text-align: center; padding-bottom: 30px;}
    .head-logo .title { font-weight: bold; padding-top: 5%;}
    .event-details { padding: 20px 5px; margin: 10px 0; background-color: #ececec; border-radius: 5px; border-style: dashed; border-color: #dcd7d7;}
    .event-title { text-align: center; font-size: large;}
    .checkin-buttons { margin: 20px 0;}
    #footer-logo { background: url("../image/power-teleskope-blue.png") no-repeat center; background-size: contain; height: 30px; margin: 10px 0 0 0;}
    .form-horizontal { margin: 20px 0;}
    .form-horizontal .control-label { font-weight: 400; text-align: left; font-size: 14px;}
    .btn.btn-primary.btn-block { height: 45px;}
    .btn-link { color: #FFFFFF !important; width: 100% !important; margin: 10px 0;}
    .form-control { height: 45px !important;}
    .user-action-box-bottom-group { border-top: 1px solid #888; margin-top: 15px; padding-top: 15px; font-size: .8rem;}
    /* Hide the reCaptcha v2 badge */
    .grecaptcha-badge { visibility: hidden;}
    .grecaptcha-label { text-align: center; margin-bottom: 20px; font-size: 12px;}
    /* For > bootstrap sm */
    @media (max-width: 414px) {
        #user-action-box { width: auto;}
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
            <div class="event-title"><?= $event->val('eventtitle'); ?></div>
        </div>

        <?php if (!empty($error_message)) { ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error_message); ?>
        </div>
        <?php } ?>
        <?php if (!empty($success_message)) { ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message); ?>
            </div>
        <?php } ?>

        <?php if ($allow_join) { ?>
        <?php if (empty($_POST)) { ?>
        <div class="checkin-buttons" id="checkin-buttons">
            <?php if ($checkin_show_signin) { ?>
            <a href='index?rurl=<?= base64_url_encode("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}") ?>'
               class="btn btn-primary btn-link center-block">Sign In & Check In</a>
            <?php } ?>
            <a onclick="manual_checkin();" class="btn btn-primary btn-link center-block">
              <?= $checkin_show_signin ? 'Other Check In' : 'Check In' ?>
            </a>
        </div>
        <?php } ?>

        <form class="form-horizontal" id="manual_checkin_form" action="" method="post"
              style="display: <?= empty($_POST) ? 'none' : 'block' ?>;">
            <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf ?? ''; ?>">
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
                <div class="form-check-label">Email/<?= $urlcompany->getCompanyCustomization()['user_field']['externalid']['name'] ?> *</div>
                <?php if ($urlcompany){?>
                    <small style="font-style: italic;"><?= sprintf(gettext("If you are a %s employee, please use your work email address OR %s."),$urlcompany->val('companyname'), $urlcompany->getCompanyCustomization()['user_field']['externalid']['name']);?></small>
                <?php } ?>

                <input type="text" name="email_or_externalid" class="form-control"
                       value="<?= $_POST['email_or_externalid'] ?? '' ?>" placeholder="<?= sprintf(gettext('Email/%s'), $urlcompany->getCompanyCustomization()['user_field']['externalid']['name']) ?>" required>
            </div>

            <div class="grecaptcha-label">
                This site is protected by reCAPTCHA and the Google
                <a href="https://policies.google.com/privacy">Privacy Policy</a> and
                <a href="https://policies.google.com/terms">Terms of Service</a> apply.
            </div>
            <div class="form-group">
                <button type="submit" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>" data-callback='onRecaptchaSubmit'
                        class="btn btn-primary btn-block center-block g-recaptcha prevent-multi-clicks">
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
    function manual_checkin() {
        grecaptcha.reset();
        $('#checkin-buttons').hide();
        $('#manual_checkin_form').show();
    }
</script>
</html>
