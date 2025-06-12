<?php
require_once __DIR__ . '/../include/Company.php';
$_LAMBDA_MODULE = 'EMAIL_HANDLER';

Logger::Log ("LambdaBot:EmailHandler: Processing", Logger::SEVERITY['INFO'], $_POST);

if (!isset($_SERVER['HTTP_X_API_KEY']) || ($_SERVER['HTTP_X_API_KEY']) !== TELESKOPE_LAMBDA_API_KEY) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

$realm = $_SERVER['HTTP_HOST'];
$realm_parts = explode('.', $realm, 2);
$subdomain = $realm_parts[0];
$app_type = $realm_parts[1];
$_COMPANY = Company::GetCompanyBySubdomain($subdomain);

if (isset($_GET['handle_rsvp']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($fromAddr = $_POST['fromAddr'] ?? '')
        || empty($toAddr = $_POST['toAddr'] ?? '')
        || empty($subject = $_POST['subject'] ?? '')
        || empty($udate = $_POST['udate'] ?? '')
        || empty($rsvp = !empty($_POST['rsvp']) ? $_POST['rsvp'] : ($_POST['fuzzyRsvp'] ?? ''))
        || !isset($_POST['uid'])
        || empty($version = $_POST['version'] ?? '')
    ){
        Logger::Log("LambdaBot:EmailHandler: Unable to handle RSVP", Logger::SEVERITY['FATAL_ERROR'], $_POST);
        header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
        exit();
    }

    $uid = $_POST['uid'] ?? '';
    $rrule_string = $_POST['rrule_string'] ?? '';
    $rsvp = strtoupper($rsvp);
    $rsvp_status = 0;
    if ($rsvp === 'TENTATIVE') {
        $rsvp_status = Event::RSVP_TYPE['RSVP_MAYBE'];
    } elseif ($rsvp === 'ACCEPTED') {
        $rsvp_status = Event::RSVP_TYPE['RSVP_YES'];
    } elseif ($rsvp === 'DECLINED') {
        $rsvp_status = Event::RSVP_TYPE['RSVP_NO'];
    }

    if ($rsvp_status) {
        $retVal = Event::ProcessRsvpForBot('LambdaBot', $fromAddr, $udate, $subject, $uid, $rsvp_status, $realm, $rrule_string);
    } else {
        $retVal = 1; // Invalid RSVP status provided, just consider it success.
        Logger::Log("LambdaBot:EmailHandler: handle_rsvp got empty rsvp status", Logger::SEVERITY['WARNING_ERROR']);
    }

    // Note the return values are
    //    *                  1: Procesed Sucessfully
    //    *                  0: not processed
    //    *                 -1: User/Company not found
    //    *                 -2: Event not found


    if ($retVal === 1) {
        echo 'OK';
    } else {
        header("HTTP/1.1 400 Cannot Process (Code {$retVal})");
    }
    exit();
}

elseif (isset($_GET['handle_bounce']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($badAddr = $_POST['badAddr'] ?? '')
        || empty($toAddr = $_POST['toAddr'] ?? '')
        || empty($udate = $_POST['udate'] ?? '')
        || empty($version = $_POST['version'] ?? '')
    ){
        Logger::Log("LambdaBot:EmailHandler: Unable to handle bounce", Logger::SEVERITY['FATAL_ERROR'], $_POST);
        header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
        exit();
    }

    $bounceType = $_POST['bounceType'] ?? '';

    // Note we set the $_COMPANY in global context at the start of this file
    //Logger::Log("Company is =".$_COMPANY->val('subdomain'));
    if ($_COMPANY->isValidAndRoutableEmail($badAddr)) {
        $bad_user = User::GetUserByEmail($badAddr);
    } else {
        $bad_user = User::GetUserByExternalEmail($badAddr);
    }

    if ($bounceType == 'Permanent') {
        if ($bad_user) {
            $bad_user->lock();
            Logger::Log("LambdaBot:EmailHandler: Lock User Success", Logger::SEVERITY['WARNING_ERROR'], ['EMAIL' => $badAddr, 'REASON'=>'Permanent email bounce back']);
        } else {
            Logger::Log("LambdaBot:EmailHandler: Lock User Ignore", Logger::SEVERITY['WARNING_ERROR'], ['EMAIL' => $badAddr, 'REASON'=>'Ignored as we were unable to update']);
        }
    } 

    echo 'OK';
    exit();
}

elseif (isset($_GET['handle_counter']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($fromAddr = $_POST['fromAddr'] ?? '')
        || empty($toAddr = $_POST['toAddr'] ?? '')
        || empty($subject = $_POST['subject'] ?? '')
        || empty($udate = $_POST['udate'] ?? '')
        || !isset($_POST['uid'])
        || empty($counter_dtstart = $_POST['counter_dtstart'] ?? '')
        || !isset($_POST['counter_dtend'])
        || empty($version = $_POST['version'] ?? '')
    ){
        Logger::Log("LambdaBot:EmailHandler: Unable to handle COUNTER", Logger::SEVERITY['FATAL_ERROR'], $_POST);
        header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
        exit();
    }

    $rrule_string = $_POST['rrule_string'] ?? '';
    $uid = $_POST['uid'] ?? '';
    $counter_dtend = $_POST['counter_dtend'] ?? '';

    $retVal = Event::ProcessCounterForBot('LambdaBot', $fromAddr, $udate, $subject, $uid, $counter_dtstart, $counter_dtend, $realm, $rrule_string);
    // Note the return values are
    //    *                  1: Procesed Sucessfully
    //    *                  0: not processed
    //    *                 -1: User/Company not found
    //    *                 -2: Event not found


    if ($retVal === 1) {
        echo 'OK';
    } else {
        header("HTTP/1.1 400 Cannot Process (Code {$retVal})");
    }
    exit();
}

else {
    Logger::Log("LambdaBot:EmailHandler: Nothing to do", Logger::SEVERITY['WARNING_ERROR']);
    header("HTTP/1.1 404 Not found");
}
