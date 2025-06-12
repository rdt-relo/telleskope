<?php
require_once __DIR__.'/head.php';

global $_USER; /* @var User $_USER */
global $_ZONE;
$title = gettext('View Album');
//Data Validation
if (!isset($_GET['id']) || 
    ($albumid = $_COMPANY->decodeId($_GET['id'])) <1 ||
    ($album = Album::GetAlbum($albumid)) === NULL ||
    $album->val('isactive') == '0'){
    $showerror = gettext("Album Media link is not valid. Click continue to go to the home page");
    $next_link = 'home';
    include(__DIR__ . "/views/showmsg_and_continue.html");
    exit();
}

// Authorization Check
if (!$_USER->canViewContent($album->val('groupid'))) {
    echo "Access Denied (Insufficient permissions)";
    header(HTTP_FORBIDDEN);
    exit();
}

//Create session for show album after share
$groupid = $album->val('groupid');
$_SESSION['show_album_id'] = $_COMPANY->encodeId($album->id());
Http::Redirect('detail?id='.$_COMPANY->encodeId($groupid).'&hash=albums#albums');
