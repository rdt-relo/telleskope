<?php

require_once __DIR__ . '/head.php';

if (!$_COMPANY->getAppCustomization()['points']['enabled']) {
    header(HTTP_BAD_REQUEST);
    exit();
}
