<?php
include_once __DIR__.'/head.php';
$db = new Hems();
$error_message = '';
$uriauthfail = false;
$realm = '';
$subdomain = '';

if (isset($_GET['bundleid']) && isset($_GET['version']) && isset($_GET['platform'])) {
    //bundleid=io.teleskope.affinities&version=mck.3.1.0&platform=android
    $bundleid = $_GET['bundleid'];
    $version = $_GET['version'];
    $platform = $_GET['platform'];

    $app_versions = $db->getPS("SELECT * FROM app_versions WHERE bundle_id=? AND app_version=? AND platform IN (?,'any')", 'xxx', $bundleid, $version, $platform);
    if (!empty($app_versions) && $app_versions[0]['isactive'] == 1 && !empty($app_versions[0]['subdomain'])) {
        // If there is a custom app matching the bundleid and version, then we will mimick setting subdomain as if the
        // user chose a subdomain. Cookie is just used as a placeholder, it will be set later on for browser setting.
        $_COOKIE['subdomain'] = $app_versions[0]['subdomain'];
    }
}

if (isset($_COOKIE['subdomain']) && !isset($_GET['rediscover'])) {
    $subdomain = $_COOKIE['subdomain'];
} elseif (isset($_POST['subdomain'])) {
    $tempsubdomain = raw2clean(strtolower($_POST['subdomain']));
    $company = Company::GetCompanyBySubdomain($tempsubdomain);
    if ($company) {
        $subdomain = $company->val('subdomain');
    } else {
        $error_message = 'Unable to find a account for your company subdomain ' . $tempsubdomain . '. Please check with your company administrator for the correct value';
    }
}

if ($subdomain) {
    $redirect_url = base64_url_encode("https://{$subdomain}.affinities.io");
    header('location:' . BASEURL . '/user/login?rurl=' . $redirect_url . '&ss=native_app_login2');
    exit();
}
$title = 'Login';
include __DIR__ . '/views/discover.html';
