<?php
require_once __DIR__.'/head.php';
$pagetitle = "New Report Customization";

if ($_COMPANY->val('in_maintenance') < 2) {
    echo "Error: Company needs to be in <strong style='color:blue;'>maintenance mode 2 or higher</strong> for creating new reports";
    exit(0);
}
$id = 0;
$edit = null;
if (isset($_GET['id'])){
    $id = $_GET['id'];
    $e = Report::GetReportRec($id);

    if (count($e)){
        $edit = $e[0];
    } else {
        
    }

}

if (isset($_POST['submit'])){

    $reportname = $_POST['reportname'];
    $reportdescription = $_POST['reportdescription'];
    $reporttype = $_POST['reporttype'];
    $purpose = $_POST['purpose'];

    $reportmeta = json_decode($_POST['reportmeta'],true);

    if (is_array($reportmeta)){
        $reportmeta = json_encode($reportmeta);
        if ($id){
            Report::UpdateReportRec( $id, $reportname, $reportdescription, $reporttype, $purpose, $reportmeta);
            $_SESSION['updated'] = time();
            $_SESSION['msg'] ="Report meta updated successfully.";
            Http::Redirect("manage_reports");
        } else {
            Report::CreateReportRec ( $reportname, $reportdescription, $reporttype, $purpose, $reportmeta);
            $_SESSION['added'] = time();
            $_SESSION['msg'] ="Report meta added successfully.";
            Http::Redirect("manage_reports");
        } 
    } else {
        $_SESSION['error'] = time();
        $_SESSION['msg'] ="Report meta data is not in a valid format!";
    }
}


include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/new_company_report.html');
