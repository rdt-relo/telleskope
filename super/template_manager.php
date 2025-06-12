<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::GlobalManageTemplates);

// Fetch the imported templates
$importedTemplates = TskpTemplate::GetAllTemplates(false);

include(__DIR__ . '/views/headermain.html');
include(__DIR__ . '/views/template_manager.html');
include(__DIR__ . '/views/footer.html');
?>