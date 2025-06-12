<?php
require_once __DIR__.'/head.php';
$pagetitle = "Update Footer Links";

// Authorization Check
if (!$_USER->canManageCompanySettings()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}
$link_id = 0;
$edit = null;

if (isset($_GET['edit'])){
    $link_id = $_COMPANY->decodeId($_GET['edit']);
    $edit = $_COMPANY->getFooterLinkDetail($link_id);

    if($edit['link_type']== 3 && !empty($edit['link'])){
        $pattern1 = '/^mailto:(.*?)\?subject=(.*)&body=(.*)/';
        $pattern2 = '/^mailto:(.*?)\?subject=(.*)$/';
        if (preg_match($pattern1, $edit['link'], $matches)) {
            $edit['mailto'] = $matches[1];
            $edit['subject'] = urldecode($matches[2]);
            $edit['email_body'] = urldecode($matches[3]);
        } elseif (preg_match($pattern2, $edit['link'], $matches)) {
            $edit['mailto'] = $matches[1];
            $edit['subject'] = urldecode($matches[2]);
            $edit['email_body'] = '';
        } else{
            $edit['mailto'] = '';
            $edit['subject'] = '';
            $edit['email_body'] = '';
        }
    }
}

if(isset($_POST['submit'])){
    $error = '';
    $link_title	= 	$_POST['link_title'];
    $link_section	= 	$_POST['link_section'];
    $link_type	= 	$_POST['link_type'];
    $valid_link_sections = array('left','middle','right');
    $link = '';
    $pre_validated_link	= '';
    if (!in_array($link_type,array(1,2,3))) {
        $error = 'Invalid link type selected';
    } elseif (!in_array($link_section, $valid_link_sections)) {
        $error = 'Invalid value for Link Section';
    } elseif ($link_type == 1 || $link_type == 3){
        if($link_type == 1 && empty($_POST['link'])){
            $error = 'Invalid value for Link Section';
        }
        // We will validate link after removing spaced from mailto type links
        if ($link_type == 3) {
            $emailId = implode(';', extractEmailsFrom($_POST['emailId'] ?? ''));
            if(empty($_POST['emailId'])){
                $error = 'Enter email address';
            }
            $subject = trim($_POST['subject'] ?? '');
            if(empty($subject)) {
                $error = 'Subject cannot be empty';
            }

            $pre_validated_link	= 	"mailto:$emailId?subject=$subject";

            if (!empty($_POST['email_body'])) {
                $body = $_POST['email_body'];
                $pre_validated_link .= '&body='.($body);
            }
            $link = $pre_validated_link;
        } else {
            $_POST['validate_link'] = $_POST['link'];
            $validator = new Rakit\Validation\Validator;
            $validation = $validator->validate($_POST, [
                'validate_link' => 'url:http,https,mailto',
            ]);
            if ($validation->fails()) {
                // handling errors
                $errors = array_values($validation->errors()->firstOfAll());
                // Return error message
                //$error = '<pre>'.implode('<br>', $errors).'</pre>';
                $error = 'Invalid link';
            } else {
                $link = $_POST['link'];
            }
        }
    } else {
    
        if (!empty($_FILES['attachment']['name'])){

            // Start of Basic Data Validation
            $validator = new Rakit\Validation\Validator;
            $validation = $validator->validate($_POST + $_FILES, [
                'attachment'         => 'uploaded_file:100,20M|mimes:pdf',
            ]);

            if ($validation->fails()) {
                // handling errors
                $errors = array_values($validation->errors()->firstOfAll());
                // Return error message
                //$error = '<pre>'.implode('<br>', $errors).'</pre>';
                $error = 'Unable to upload attachment<br>Only .pdf attachments with a maximum size of 20 MB are allowed';
            } else {

                $file 	    =	basename($_FILES['attachment']['name']);
                $tmp 		=	$_FILES['attachment']['tmp_name'];
                $ext		=	$db->getExtension($file);
                $actual_name = "footer_link_" . time() . $userid . "." . $ext;
                $s3_url = $_COMPANY->saveFile($tmp, $actual_name, 'ZONE');
                if (!empty($s3_url)) {
                    $link = $s3_url;
                } else {
                    $error = 'Unable to save attachment';
                }
            }
        } else {
            $error = 'Attachment field is required';
        }
    }

	if (empty($error)) {
        $_COMPANY->addOrUpdateFooterLink($link_id, $link_title, $link_section, $link_type, $link);
        $_SESSION['updated'] = time();
        Http::Redirect("branding");
    }
}


include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/update_footer_links.html');

?>
