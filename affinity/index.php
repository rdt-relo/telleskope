<?php
define('INDEX_PAGE', 1);
require(__DIR__.'/head.php');

$suffix_first = '';
$suffix_second = '';

if (isset($_GET['logout'])) {
  $suffix_first .= '?logout=1';
  $suffix_second = '&logout=1';
}

// Set timezone and perform IE11 check
if (isset($_GET['timezone']) && isset($_GET['ie11'])) {
  $tz = $_GET['timezone'];
  if ($tz == "undefined" ||
    !isValidTimeZone($tz)) {
    $tz = "";
  }
  if (isset($_GET['ie11'])) {
    $ie11 = ($_GET['ie11'] === "true");
  } else {
    $ie11 = false; //default is false
  }

  if(!empty($tz)){
      $tz = TskpTime::OUTDATED_TIMEZONE_MAP[$tz] ?? $tz;
  }

  $_SESSION['tz_b'] = $tz; // tz_b is used to store the timezone detected by browser
  $_SESSION['ie11'] = $ie11;
}
// If timezone or IE11 check not set, then get it from browser.
if (!isset($_SESSION['tz_b']) || !isset($_SESSION['ie11'])) {
    /* Get User Current Time Zone */
    echo '<script src="' . TELESKOPE_CDN_STATIC . '/vendor/js/jquery-3.5.1/dist/jquery.min.js"></script>
    <script src="' . TELESKOPE_CDN_STATIC . '/vendor/js/jstz-2.1.0/dist/jstz.min.js"></script>
    <script type="text/javascript">
        function isIE11() {
            return !!window.navigator.userAgent.match(/(MSIE|Trident)/);
        }
    </script>
    <script type="text/javascript">
        $(document).ready(function () {
            tz = jstz.determine().name();
            ie11 = isIE11();
            let glue = "?";
            let frags = window.location.href.split("#");
            if (frags[0].includes("?")) {
                glue = "&";
            }
            window.location.href = frags[0] + glue + "timezone=" + tz + "&ie11=" + ie11;
        });
    </script>';
    exit();
}
// End of TZ and IE11 checks

elseif (empty($_SESSION['userid'])) {
    // Login the user first by redirecting to login URL
    if (isset($_GET['rurl'])) {
        // Double check the URL is for this instance before doing anything with it.
        $decoded_url = base64_url_decode($_GET['rurl']);
        $urlhost = parse_url($decoded_url, PHP_URL_HOST);
        if (strpos($decoded_url,'rurl=') === FALSE && $urlhost === $_SERVER['HTTP_HOST']) {
            // Redirect only if the url does not contain another redirection
            // and if the redirected urls hostname matches with current user domain.
            $redirect_url = base64_url_encode($decoded_url);
        }
        else{
            // In order to reuse index.php across multiple applications, make app_directory a variable
            $app_name_from_host = explode('.',$_SERVER['HTTP_HOST'])[1];
            $app_dir = (array('affinities' => 'affinity', 'officeraven' => 'officeraven', 'talentpeak'=>'talentpeak', 'peoplehero'=>'peoplehero'))[$app_name_from_host];
            $redirect_url = base64_url_encode("https://{$_SERVER['HTTP_HOST']}/1/{$app_dir}/index" . $suffix_first);
        }
    } else {
        $redirect_url = base64_url_encode("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}" . $suffix_second);
    }
    $_SESSION['ss'] = rand();

    Http::Redirect(BASEURL . '/user/login?rurl=' .$redirect_url. '&ss=' .$_SESSION['ss']. $suffix_second);
} else {
    if (empty ($_ZONE)) {
        // User is logged in, but for some reason the $_ZONE is not set, logout and retry
        Http::Redirect('logout');
    } elseif (!empty(Session::GetInstance()->mylocation_url)) {
        // User is already logged in, take him to his home!
        Http::Redirect(Session::GetInstance()->mylocation_url);
    } else {
        Http::Redirect(Url::GetZoneAwareUrlBase($_ZONE->id()) . 'home');
    }
}

