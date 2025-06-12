<?php
require_once __DIR__.'/head.php';

if (isset($_GET['zoneid'])
    &&
    ($zoneid = $_COMPANY->decodeId($_GET['zoneid'])) > 0
    &&
    !empty($zone = $_COMPANY->getZone($zoneid))
) {

    $_ZONE = $zone;
}

Http::Redirect('dashboardres');
