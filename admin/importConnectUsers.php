<?php
require_once __DIR__.'/head.php';
require_once __DIR__.'/../include/UserConnect.php';

$pagetitle = "Import Connect Users";

// Authorization Check
if (!$_USER->canManageAffinitiesUsers() || !$_ZONE->isConnectFeatureEnabled()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}
$error = "";
$invalidEmail = array();
$invalidExternalId = array();
$alreadyConnected = array();
$successInvited = array();
if(isset($_POST['submit'])) {
    if(!empty($_FILES['connectusercsv']['name'])){
        $file 	   		=	basename($_FILES['connectusercsv']['name']);
        $tmp 			=	$_FILES['connectusercsv']['tmp_name'];
        $ext = substr(pathinfo($file)['extension'],0,4);
        // Allow certain file formats
        if($ext != "csv"  ) {
            $error =  "Sorry, only .csv file format allowed";
        } else {
            try {
                $csv = Csv::ParseFile($tmp);
                if ($csv) {
                    foreach($csv as $row){

                        $externalId = trim(raw2clean($row['externalid']));
                        $personalEmail = trim(raw2clean($row['personalemail']));

                        if ($_COMPANY->isValidEmail($personalEmail)) {
                            $invalidEmail[] = $personalEmail;
                        }

                        if (!filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
                            $invalidEmail[] = $personalEmail;
                        } else {
                            $u = User::GetUserByExternalId($externalId, true);
                            if ($u && !$_COMPANY->isValidAndRoutableEmail($u->val('email'))) {
                                $connectid = UserConnect::AddConnectUser($u->id(), $personalEmail);
                                if ($connectid > 0) {
                                    $connectUser = UserConnect::GetConnectUser($connectid);
                                    $connectUser->sendEmailVerificationCode($_ZONE->val('app_type'));
                                    $successInvited[] = $personalEmail;
                                } elseif ($connectid == -1 || $connectid == -2) {
                                    $alreadyConnected[] = $externalId;
                                } elseif ($connectid == -3) {
                                    $alreadyConnected[] = $personalEmail;
                                }
                            } else {
                                $invalidExternalId[] = $externalId;
                            }
                        }
                    }
                } else {
                    $error = 'CSV file format issue';
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } else {
        $error = 'Please select a CSV file as per described sample format';
    }
}

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/importConnectUsers.html');
