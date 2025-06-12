<?php


namespace Microsoft\Graph\Connect;
require_once __DIR__.'/../../../include/init.php';

/**
 * @deprecated This class is deprecate and will be removed in the future, use Teleskope_AdminPanel_V2_Constants.php
 */
class TeleskopeConstants extends Constants {
    const CLIENT_ID 	= OFFICE365_CLIENT_ID;
    const CLIENT_SECRET = OFFICE365_CLIENT_SECRET;
    const REDIRECT_URI	= OFFICE365_REDIRECT_URI;
    const HOME_URI 		= OFFICE_HOME_URI;
    const SCOPES = 'openid profile user.read user.read.all'; //openid profile provides identity token

    public function __construct() { }
}