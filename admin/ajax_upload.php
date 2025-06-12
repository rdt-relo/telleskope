<?php
define('AJAX_CALL',1);
define('AJAX_CSRF_EXEMPT',1);
require_once __DIR__.'/head.php';

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
    header("HTTP/1.1 403 Forbidden (Access Denied)");
    exit();
}

if (isset($_GET['uploadProfilePicture']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES["picture"]["error"])) {
        $size       =   $_FILES['picture']['size'];
        $tmp        =   $_FILES['picture']['tmp_name'];
        $mimetype   =   mime_content_type($tmp);
        $valid_mimes = array('image/jpeg' => 'jpg','image/png' => 'png');
        
        if (in_array($mimetype,array_keys($valid_mimes))){
            $ext = $valid_mimes[$mimetype];

            if($size<(1024*1024)){
              
                $selectedUserid = $_COMPANY->decodeId($_POST['selectedUserId']);
                $subjectUser = User::GetUser($selectedUserid);
                $s3_file = 'profile_'.teleskope_uuid(). "." . $ext;
                $tmp = $_COMPANY->resizeImage($tmp, $ext,240);
                $s3_url = $_COMPANY->saveFile($tmp,$s3_file, 'USER');
                $updateStatus = $subjectUser->updateProfilePicture($s3_url);
                
                //Next delete the old picture if the update was successful
                if ($updateStatus && $subjectUser->has('picture')){
                    $_COMPANY->deleteFile($subjectUser->val('picture'));
                }
                $_COMPANY->expireRedisCache("USR:{$subjectUser->id()}");
                echo $s3_url;
            }else{
                echo 1;
            }
        }else{
            echo 2;
        }
    } else {
        echo 0;
    }
    exit();
}

if (isset($_GET['removeProfilePicture']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedUserid = $_COMPANY->decodeId($_POST['selectedUserId']);
    $subjectUser = User::GetUser($selectedUserid);
    $subjectUser->updateProfilePicture('');
    if ($subjectUser->has('picture')){
        $_COMPANY->deleteFile($subjectUser->val('picture'));
    }
    $_COMPANY->expireRedisCache("USR:{$subjectUser->id()}");
    exit();
    
}

if (isset($_GET['uploadRedactorMedia']) && !empty($_GET['uploadRedactorMedia'])){

    $context = $_GET['uploadRedactorMedia'];
    if (array_search($context,array('group','post','event','messages','teamtasks','group_tabs','email_template','zone')) === false) {
        // If the context is not one of the above then reject the request
        $err = ['error' => true, 'message' => 'Internal Error(bad context)'];
        echo stripslashes(json_encode($err));
        exit();
    }

    $file = [];
    if (empty($_FILES['file']['name'][0]) || ((int)$_FILES['file']['size'])>5242880) {
        $err = ['error' => true, 'message' => 'Upload error, maximum allowed filesize is 5 MB'];
        echo stripslashes(json_encode($err));
        exit();
    }

    $tmp = $_FILES["file"]["tmp_name"][0];
    $mimetype   =   mime_content_type($tmp);
    $valid_mimes = array('image/jpeg' => 'jpg','image/png' => 'png');

    if (in_array($mimetype,array_keys($valid_mimes))) {
        $ext = $valid_mimes[$mimetype];
    } else {
        $err = ['error' => true, 'message' => 'Unsupported file type. Only .png, .jpg or .jpeg files are allowed'];
        echo stripslashes(json_encode($err));
        exit();
    }
    $tmp = $_COMPANY->resizeImage($tmp, $ext, 900);

    $s3_file = strtolower($context).'_'.teleskope_uuid(). "." . $ext;
    $s3_url = $_COMPANY->saveFile($tmp, $s3_file, strtoupper($context));

    if (!empty($s3_url)) {
        $file = ['file' => [
            'url' => $s3_url,
            'id' => $s3_file
        ]];
        echo stripslashes(json_encode($file));
        exit();
    } else {
        $err = ['error' => true, 'message' => 'File upload error'];
        echo stripslashes(json_encode($err));
        exit();
    }
}

else {
    Logger::Log ("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}

