<?php


namespace Microsoft\Graph\Connect;

require_once __DIR__.'/../../../include/init.php';
/**
 * @deprecated This class is deprecate and will be removed in the future, use Teleskope_Apps_V2_Constants.php
 */
class OfficeRavenConstants extends Constants {
    const CLIENT_ID 	= OFFICE365_CLIENT_ID_OFFICERAVEN;
    const CLIENT_SECRET = OFFICE365_CLIENT_SECRET_OFFICERAVEN;
    const REDIRECT_URI	= OFFICE365_REDIRECT_URI; // OFFICE365_REDIRECT_URI_OFFICERAVEN;
    const HOME_URI 		= OFFICE_HOME_URI; //OFFICE_HOME_URI_OFFICERAVEN;
    const SCOPES = 'openid profile user.read'; //openid profile provides identity token

    public function __construct() { }
}