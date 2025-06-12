<?php


namespace Microsoft\Graph\Connect;

require_once __DIR__.'/../../../include/init.php';
class Teleskope_Apps_V2_Constants extends Constants {
    const CLIENT_ID 	= OFFICE365_CLIENT_ID_APPS_V2;
    const CLIENT_SECRET = OFFICE365_CLIENT_SECRET_APPS_V2;
    const REDIRECT_URI	= OFFICE365_REDIRECT_URI; // OFFICE365_REDIRECT_URI_AFFINITY;
    const HOME_URI 		= OFFICE_HOME_URI; //OFFICE_HOME_URI_AFFINITY;
    const SCOPES = 'openid profile user.read'; //openid profile provides identity token

    public function __construct() { }
}