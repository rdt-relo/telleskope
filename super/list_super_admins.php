<?php

require_once __DIR__ . '/head.php';

Auth::CheckSuperSuperAdmin();

$super_admins = $_SUPER_ADMIN->getAllSuperAdmins();

require __DIR__ . '/views/headermain.html';
require __DIR__ . '/views/list_super_admins.html.php';
require __DIR__ . '/views/footer.html';
