<?php
include_once __DIR__.'/head.php';

###### All Ajax Calls ##########

###### AJAX Calls ######
##### Should be in if-elseif-else #####
##### Email Domain  ##########
if (isset($_GET['domainverification']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = raw2clean($_POST['email']);

  if (!verify_recaptcha()) {
    echo 100;
  } elseif (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $company = Company::GetCompanyByEmail($email);

    if ($company) {
      $_SESSION['domainverification'] = $email;
      $_SESSION['verification_emails_limit'] = 0;
      $_SESSION['confirmation'] = '';

      $user = User::GetUserByEmail($email);
      if ($company->val('subdomain') != $_SESSION['client']) {
        echo 5;
      } elseif ($user === null) {
        echo 1;
      } elseif ($user->val('verificationstatus') == 1) {
        echo 3;
      } else {
        $_SESSION['confirmation'] = $user->id();
        // If there is no confirmation code on record, send a new one.
        if (empty($user->val('confirmationcode'))) {
            $_COMPANY = $company;
            $_ZONE = $_COMPANY->getEmptyZone($_SESSION['app_type']);
            $user->generateAndSendConfirmationCode();
            $_COMPANY = null;
        }
        echo 4;
      }
    } else {
      $_SESSION['domainverification'] = '';
      $_SESSION['confirmation'] = '';
      echo 2;
    }
  } else {
    $_SESSION['domainverification'] = '';
    $_SESSION['confirmation'] = '';
    echo 0;
  }
} elseif (isset($_GET['policyconsent']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
  if (!verify_recaptcha()) {
    echo 100;
  } else {
    if (isset($_POST['is_policy_checked']) && "false" != $_POST['is_policy_checked']) {
      $_SESSION['policy_accepted'] = "1";
      echo 7;  // policy accepted
    }
  }
}

// Request new account confirmation code
elseif (isset($_GET['sendNewConfirmationCode'])) {
    global $_ZONE;
    $_ZONE = $_COMPANY->getEmptyZone($_SESSION['app_type']);
    $user = User::GetUser($_GET['sendNewConfirmationCode']);
    $_SESSION['verification_emails_limit'] = $_SESSION['verification_emails_limit'] ?? 0;

    // check if email matches the email session (for new user). Restrict sending more than 5 verification emails in 1 session
    if ($_SESSION['verification_emails_limit'] > 5) {
        echo json_encode(array("status" => "error", "details" => "Error: Too many attempts to send confirmation code. Please try again later."));
    } elseif (!empty($user) && $user->val('email') == $_SESSION['domainverification']) {
        $user->generateAndSendConfirmationCode();
        $_SESSION['verification_emails_limit']++;
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "details" => "Error: Failed to send confirmation email to this address. Please check the address and try again."));
    }
    exit();
} else {
    Logger::Log("Nothing to do ... ");
}
exit();
