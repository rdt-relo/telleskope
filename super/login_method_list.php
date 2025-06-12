<?php
require_once __DIR__.'/head.php';

Auth::CheckPermission(Permission::ManageLoginMethods);

$db	= new Hems();

$curr_company = Company::GetCompany(intval($_SESSION['companyid']));

$data = $curr_company->getLoginMethods();

usort($data, function($a,$b) {
    return ($a['scope'] === $b['scope']) ?
        strcmp($a['loginmethod'], $b['loginmethod']) :
        strcmp($a['scope'], $b['scope']);
});

$row_scope_colors = array (
    'affinities'=>'#dedeff',
    'officeraven'=>'#dedeaf',
    'talentpeak'=>'#deafaf',
    'peoplehero'=>'#deafff',
    'teleskope'=>'#dedede',
);
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/login_method_list.html');
include(__DIR__ . '/views/footer.html');

?>
