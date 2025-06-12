<?php
require_once __DIR__.'/head.php';
require_once __DIR__.'/../include/libs/vendor/erusev/parsedown/Parsedown.php';

$doc = $_GET['doc'] ?? '';
$docPath = __DIR__ . "/docs/" . basename($doc);

// Security check: ensure file is markdown and exists
if (!preg_match('/\.md$/', $doc) || !file_exists($docPath)) {
    http_response_code(404);
    die("Document not found.");
}

$markdown = file_get_contents($docPath);
$parsedown = new Parsedown();
$html = $parsedown->text($markdown);

include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/view_doc.html.php');
include(__DIR__ . '/views/footer.html'); 
?>