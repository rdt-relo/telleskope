<?php
require_once __DIR__.'/head.php';

$pagetitle = "Company Department";
$id = 0;
$edit = null;
$contactrole = '';

if (isset($_GET['type']) && in_array($_GET['type'], ['Business','Technical','Security'])){
    $contactrole = raw2clean($_GET['type']);
} else {
    $_SESSION['error']= time();
    $_SESSION['form_error'] = "Contact type not defined. Please try again";
	Http::Redirect("manage_contacts");
}

$id = (isset($_GET['edit'])) ? $_COMPANY->decodeId($_GET['edit']) : 0;

if (isset($_POST['submit'])){
    // Start of Basic Data Validation
    $validator = new Rakit\Validation\Validator;

    $validation = $validator->validate($_POST, [
        'firstname'      => 'required|regex:/^[\s\d\pL\pM]+$/u|min:1|max:32', //Alpha numeric with spaces and . _ -
        'lastname'       => 'required|regex:/^[\s\d\pL\pM]+$/u|min:1|max:32',
        'email'          => 'required|email|regex:/[\w\.]+@[\w\.\-]+$/|max:64', // Using double check as burp reports DAST errors
        'phonenumber'    => 'required|regex:/^[\s\d\+\-x]+$/u|min:4|max:32',
        'title'          => 'required|regex:/^[\s\w]+$/u|min:1|max:64'
    ]);

    if ($validation->fails()) {
        // handling errors
        $errors = array_values($validation->errors()->firstOfAll());
        // Return error message
        $_SESSION['error'] = time();
        $_SESSION['form_error'] = '<pre>' . implode('<br>', $errors) . '</pre>';

    }
    // End of Basic Data Validation

    elseif (!$_COMPANY->isValidEmail($_POST['email'])){
        $_SESSION['error'] = time();
        $_SESSION['form_error'] = "Invalid email address. Please provide a valid email address that matches one of your configured company domain";
    }

    else {
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $email = $_POST['email'];
        $phonenumber = $_POST['phonenumber'];
        $title = $_POST['title'];


        if ($id) {
            if ($_COMPANY->updateCompanyContact($contactrole, $firstname, $lastname, $email, $phonenumber, $title)) {
                $_SESSION['updated'] = time();
            } else {
                $_SESSION['error'] = time();
                $_SESSION['form_error'] = "Internal error, unable to update contact";
            }
        } else {
            $_COMPANY->addCompanyContact($contactrole, $firstname, $lastname, $email, $phonenumber, $title);
            //$_SESSION['added'] = time();
        }

        Http::Redirect("manage_contacts");
    }
	
} elseif(isset($_GET['edit'])){
    $edit =  $_COMPANY->GetCompanyContact($contactrole);
}

include(__DIR__ . '/views/header_new.html');
include(__DIR__ . '/views/add_company_contact.html');
include(__DIR__ . '/views/footer.html');
?>
