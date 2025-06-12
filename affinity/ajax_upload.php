<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
define('AJAX_CSRF_EXEMPT',1); //Create an exception to bypass CSRF checks for images uploaded through content
require_once __DIR__.'/head.php';

$retVal = [];

if (isset($_GET['uploadProfilePicture']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_FILES["picture"]["error"])) {
        $size 		= 	$_FILES['picture']['size'];
        $tmp 		=	$_FILES['picture']['tmp_name'];
        $mimetype   =   mime_content_type($tmp);
        $valid_mimes = array('image/jpeg' => 'jpg','image/png' => 'png');

		if (in_array($mimetype,array_keys($valid_mimes))){
		    $ext = $valid_mimes[$mimetype];

            if($size<(1024*1024)){
                $s3_file = 'profile_'.teleskope_uuid(). "." . $ext;
				$tmp = $_COMPANY->resizeImage($tmp, $ext,240);
                $s3_url = $_COMPANY->saveFile($tmp,$s3_file, 'USER');
                $updateStatus = $_USER->updateProfilePicture($s3_url);
                
                //Next delete the old picture if the update was successful
                if ($updateStatus && $_USER->has('picture')){
                    $_COMPANY->deleteFile($_USER->val('picture'));
                }
                $_USER->clearSessionCache();
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

elseif (isset($_GET['uploadRedactorMedia']) && !empty($_GET['uploadRedactorMedia'])){

    $context = strtoupper($_GET['uploadRedactorMedia']);
    if (!in_array($context, array_keys(Company::S3_AREA))) {
        // If the context is not one of the above then reject the request
        $err = ['error' => true, 'message' => gettext('Internal Error (bad context)')];
        echo stripslashes(json_encode($err));
        exit();
    }

    $file = [];
    if (empty($_FILES['file']['name'][0]) || ((int)$_FILES['file']['size'][0])>5242880) {
        $err = ['error' => true, 'message' => gettext('Upload error, maximum allowed filesize is 5 MB')];
        echo stripslashes(json_encode($err));
        exit();
    }

    $tmp = $_FILES["file"]["tmp_name"][0];
    if (!$tmp) {
        $err = ['error' => true, 'message' => gettext('Unable to upload file, please try again')];
        echo stripslashes(json_encode($err));
        exit();
    }

    $mimetype   =   mime_content_type($tmp);
    $valid_mimes = array('image/jpeg' => 'jpg','image/png' => 'png','image/gif' => 'gif');

    if (in_array($mimetype,array_keys($valid_mimes))) {
        $ext = $valid_mimes[$mimetype];
    } else {
        $err = ['error' => true, 'message' => gettext('Unsupported file type. Only .png, .jpg, .gif or .jpeg files are allowed')];
        echo stripslashes(json_encode($err));
        exit();
    }

    if ($mimetype == 'image/gif' && getimagesize($tmp)[0]>900) {
        $err = ['error' => true, 'message' => sprintf(gettext('gif image width should be less than %s pixels'),900)];
        echo stripslashes(json_encode($err));
        exit();
    }
    $tmp = $_COMPANY->resizeImage($tmp, $ext, 900);

    if ($tmp)
        [$img_width,$img_height] = getimagesize($tmp) ?: ['',''];
    else
        [$img_width,$img_height] = ['',''];
    $s3_file = strtolower($context).'_'.teleskope_uuid(). "." . $ext;
    $s3_url = $_COMPANY->saveFile($tmp, $s3_file, strtoupper($context));

    if (!empty($s3_url)) {
         $file = ['file' => [
             'url' => $s3_url,
             'id' => $s3_file,
             'img_width' => $img_width,
             'img_height' => $img_height
         ]];
         echo stripslashes(json_encode($file));
         exit();
    } else {
        $err = ['error' => true, 'message' => gettext('File upload error')];
        echo stripslashes(json_encode($err));
        exit();
    }
}

else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}
