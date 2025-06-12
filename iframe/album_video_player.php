<?php
include_once __DIR__.'/iframe_specific_head.php';
$_IFRAME_MODULE = 'ALBUM_VIDEO_PLAYER';
/**
 * This iFrame requires either the user to be logged in (i.e. when called from one of teleskope properties)
 * or provide a valid token
 */
$userid = 0;
if (
    isset($_SESSION) &&
    !empty($_SESSION['companyid']) &&
    !empty($_SESSION['userid']) &&
    $_SESSION['companyid'] == $_IFRAME_COMPANY->id()
) {
    $userid = (int)$_SESSION['userid'];
} elseif (
    isset($_GET['api_auth_token']) &&
    ($auth_token_vals = $_IFRAME_COMPANY->decryptString2Array(urldecode($_GET['api_auth_token']))) &&
    !empty($auth_token_vals['companyid']) && $auth_token_vals['companyid'] == $_IFRAME_COMPANY->id() &&
    !empty($auth_token_vals['valid_for']) && $auth_token_vals['valid_for'] == 'album_video_player' &&
    !empty($auth_token_vals['valid_until']) && $auth_token_vals['valid_until'] > time() &&
    !empty($auth_token_vals['userid'])
) {
    $userid = (int)$auth_token_vals['userid'];
} else {
    die('Invalid or expired iframe link');
}
/*
 * This video player is typically called by a iframe with following specs
 * <iframe width="880" height="510" src="https://{subdomain}.{application}.io/1/iframe/album_video_player?uid=30bbc113_ee56_47bb_ae0f_5d0296ceaf32_6361663e.mp4&aid=3&zid=1&autoplay=off&loop=off&muted=off"></iframe>
 *
 */
$video_uuid = $_GET['uid'] ?: '';
$video_albumid = $_GET['aid'] ?: '';
$video_zoneid = $_GET['zid'] ?: '';
$autoplay = isset($_GET['autoplay']) && $_GET['autoplay'] == 'on';
$loop = isset($_GET['loop']) && $_GET['loop'] == 'on';
$muted = isset($_GET['muted']) && $_GET['muted'] == 'on';
if (substr($video_uuid, -4) !== '.mp4') {
    die();
}

// Semi trusted block;
{
    // We will be setting $_COMPANY and $_ZONE temporarily
    global $_COMPANY;
    if (!isset($_COMPANY)) {
        $_COMPANY = $_IFRAME_COMPANY; // Temporary
        echo Album::GetVideoTagForIframe($video_uuid, $video_albumid, $video_zoneid, $autoplay, $loop, $muted);
        $_COMPANY = null; // Reset it back.
    }
}
