<?php
require_once __DIR__.'/head.php';
$db	= new Hems();
$pagetitle = "Update Company Teamplates";
if (!empty($_GET['id'])){
    $id = (int) $_COMPANY->decodeId($_GET['id']);
    $data = $_COMPANY->getTemplateDetail($id);
    
    if (!$data ){
        Http::Redirect("manage_templates");
    }
    $emplate = str_replace("'","\'",$data['template']);
    include(__DIR__ . '/views/header.html');
    include(__DIR__ . '/views/editTemplate.html');
} else {
    Http::Redirect("manage_templates");
}


?>
