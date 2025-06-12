<?php
require_once __DIR__.'/head.php';

$edit_template_id = $_GET['source_template_id'] ? base64_url_decode($_GET['source_template_id']) : '';
$edit_template = null;
if ($edit_template_id) {
    $edit_template = TskpTemplate::GetTskpTemplate($edit_template_id);
}

$pageTitle = ($edit_template ? 'Edit' : 'Create') ." a Template";

// Initialize variables
$errorMessage = '';
$successMessage = '';

Auth::CheckPermission(Permission::GlobalManageTemplates);

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $edit_template) {

    // Template Description
    $templateDescription = $_POST['template_description'];
    $templateDescription = preg_replace('#<p></p>#','<p>&nbsp;</p>', $templateDescription);
    $templateName = $_POST['template_name'];

    $retVal = TskpTemplate::UpdateTemplate($edit_template_id, $templateName, $templateDescription);

    if ($retVal) {
        $successMessage = 'Template updated successfully!';
        Http::Redirect('template_manager');
    } else {
        $errorMessage = 'Failed to update template. Please try again.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate the form fields
    $templateType = $_POST['template_type'];
    $templateAppType = $_POST['template_app_type'];
    $templateName = $_POST['template_name'];

    // Template Description
    $templateDescription = $_POST['template_description'];
    $templateDescription = preg_replace('#<p></p>#','<p>&nbsp;</p>', $templateDescription);
    
    // Check if import type is selected
    if (empty($templateType)) {
        $errorMessage = 'Please select a Template type.';
    } elseif (empty($templateName)) {
        $errorMessage = 'Please enter a Template name.';
    } else {
        // Check if a file is uploaded
        if (isset($_FILES['importFile']) && $_FILES['importFile']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['importFile'];

            // Check the file type
            $allowedExtensions = array('json');
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExtension, $allowedExtensions)) {
                $errorMessage = 'Invalid file type. Only JSON files are allowed.';
            } else {
                // Read the file contents
                $jsonData = file_get_contents($file['tmp_name']);

                // Validate the JSON data and get the source template id
                $decodedJsonData = json_decode($jsonData);
                if ($decodedJsonData === null && json_last_error() !== JSON_ERROR_NONE) {
                    $errorMessage = 'Invalid JSON data. Please check the format and try again.';
                } elseif (!isset($decodedJsonData->source_template_id) || empty($decodedJsonData->source_template_id)) {
                    $errorMessage = 'Source template ID not found in the JSON data.';
                } else {
                    // Extract the source template ID from the JSON data
                    $sourceTemplateId = $decodedJsonData->source_template_id;

                    // Import the data into the database
                    $retVal = TskpTemplate::CreateOrUpdateTemplate($sourceTemplateId, $templateName, $templateType, $templateAppType, $jsonData, $templateDescription);

                    if ($retVal === true) {
                        $successMessage = 'Template data imported successfully!';
                        Http::Redirect('template_manager');
                    } else {
                        $errorMessage = $retVal;
                    }
                }
            }
        } else {
            $errorMessage = 'No file uploaded.';
        }
    }
}

include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/new_template_import.html');
include(__DIR__ . '/views/footer.html');
?>