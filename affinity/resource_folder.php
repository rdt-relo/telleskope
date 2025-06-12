<?php
require_once __DIR__.'/head.php';

global $_USER; /* @var User $_USER */
global $_ZONE;
$title = gettext('View Resource');
//Data Validation
if (!isset($_GET['id']) ||
    ($resourceid = $_COMPANY->decodeId($_GET['id']))<1 ||
    ($resource = Resource::GetResource($resourceid, true)) === NULL ||
    $resource->val('isactive') == '0'){
    $showerror = gettext("Resource link is not valid. Click continue to go to the home page");
    $next_link = 'home';
    include(__DIR__ . "/views/showmsg_and_continue.html");
    exit();
}

// Authorization Check
if (!$_USER->canViewContent($resource->val('groupid'))) {
    header(HTTP_FORBIDDEN);
    exit();
}

$groupid = $resource->val('groupid');
$_SESSION['show_resource_folder_id'] = $resource->id();

$resource_link = "detail?id={$_COMPANY->encodeId($groupid)}&hash=resources#resources";

Http::Redirect( Url::GetZoneAwareUrlBase($resource->val('zoneid')) . $resource_link);
