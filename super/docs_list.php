<?php
require_once __DIR__.'/head.php';
//Auth::CheckPermission(Permission::ViewDocs); add view Permission
$docsDir = __DIR__ . '/docs';
$files = array_diff(scandir($docsDir), ['.', '..']);
include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/docs_list.html.php');
include(__DIR__ . '/views/footer.html'); 
?>