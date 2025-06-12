<?php
require_once __DIR__.'/head.php';
$pagetitle = "Add Link";
$id = 0;

// Authorization Check
if (!$_USER->canManageCompanySettings()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}

if(isset($_GET['edit'])){
	//Data Validation
	$id = $_COMPANY->decodeId($_GET['edit']);
	if ($id===0) {
		header("HTTP/1.1 400 Bad Request (Missing or malformed parameters)");
		exit();
	}
	$edit=$_COMPANY->getHotlink($id);
}


if(isset($_POST['submit'])){

	// Start of Basic Data Validation
	$validator = new Rakit\Validation\Validator;

	$validation = $validator->validate($_POST+$_FILES, [
		'title'      		=> 'required|regex:/^[\s\d\w\-,&]+$/u|min:1|max:24', //Alpha numeric with spaces and . _ -
		'alternate_name'    => 'regex:/^[\s\d\w\-,&]+$/u|min:1|max:64',
		'link_type'         => 'required|integer:1,2',
		'link'				=> 'required_if:resource_type,1|url:http,https|max:255',
		'link_file'			=> 'uploaded_file:100,5M|mimes:pdf,xls,xlsx,ppt,pptx,doc,docx,png,jpeg,jpg',
		'image_file'		=> 'uploaded_file:100,2K|mimes:png,jpeg,jpg,svg'
	]);

	if ($validation->fails()) {
		// handling errors
		$errors = array_values($validation->errors()->firstOfAll());
		// Return error message
		$error = '<pre>' . implode('<br>', $errors) . '</pre>';

	}
	// End of Basic Data Validation
	else {
		$title = raw2clean($_POST['title']);
		$alternate_name = raw2clean($_POST['alternate_name']);
		$link_type = (int)$_POST['link_type'];
		$error = '';
		$link = "";
		$image = "";
		if ($link_type === 1) {
			$link = raw2clean($_POST['link']);
		} else {
			if (!empty($_FILES['link_file']['name'])) {
				$file = basename($_FILES['link_file']['name']);
				$tmp = $_FILES['link_file']['tmp_name'];
				$ext = $db->getExtension($file);
				$actual_name = "link_" . teleskope_uuid() . "." . $ext;

				$link = $_COMPANY->saveFile($tmp, $actual_name, 'ZONE');
				if (empty($link)) {
					$error = 'Unable to upload the linked file';
				} elseif ($id > 0) {
					$_COMPANY->deleteFile($edit[0]['link']); //Remove the old link
				}
			} else {
				if ($id > 0 && !empty($edit[0]['link'])) {
					$link = $edit[0]['link'];
				} else {
					$error = 'Attachment required';
				}
			}
		}

		if (!empty($_FILES['image_file']['name'])) {
			$image_info = getimagesize($_FILES["image_file"]["tmp_name"]);
			$image_width = $image_info[0];
			$image_height = $image_info[1];
			if ($image_width > 80 || $image_height > 80) {
				$error = 'Maximum icon size allowed 80px wide x 80px high.';
			} else {
				$file = basename($_FILES['image_file']['name']);
				$tmp = $_FILES['image_file']['tmp_name'];
				$ext = $db->getExtension($file);
				$valid_formats = array("jpg", "png", "jpeg", "svg");

				if (in_array($ext, $valid_formats)) {
					$actual_name = "link_cover_" . teleskope_uuid() . "." . $ext;
					$image = $_COMPANY->saveFile($tmp, $actual_name, 'ZONE');
					if (empty($image)) {
						$error = "Unable to upload the icon";
					} elseif ($id > 0) {
						$_COMPANY->deleteFile($edit[0]['image']);
					}

				} else {
					$error = 'Only .jpg, .jpeg, .png or .svg icon files are allowed!';
				}
			}
		} else {
			if ($id > 0 && $edit[0]['image'] != '') {
				$image = $edit[0]['image'];
			} else {
				$image = '';
			}

		}

		if ($error == '') {
			if ($id == 0) {
				$_COMPANY->createHotlink($title, $alternate_name, $image, $link_type, $link);
				$_SESSION['added'] = time();
				Http::Redirect("branding");
			} else {
				$_COMPANY->updateHotlink($id, $title, $alternate_name, $image, $link_type, $link);
				$_SESSION['updated'] = time();
				Http::Redirect("branding");
			}
		}
	}
}
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/add_link.html');

?>
