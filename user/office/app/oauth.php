<?php
/**
 *  Copyright (c) Microsoft. All rights reserved. Licensed under the MIT license.
 *  See LICENSE in the project root for license information.
 *
 *  PHP version 5
 *
 *  @category Code_Sample
 *  @package  php-connect-rest-sample
 *  @author   Ricardo Loo <ricardol@microsoft.com>
 *  @license  MIT License
 *  @link     http://github.com/microsoftgraph/php-connect-rest-sample
 */
 
/*! 
    @abstract The page that the user will be redirected to after 
              Azure Active Directory (AD) finishes the authentication flow.
 */


use Microsoft\Graph\Connect\AffinitiesConstants;
use Microsoft\Graph\Connect\OfficeRavenConstants;
use Microsoft\Graph\Connect\TeleskopeConstants;
use Microsoft\Graph\Connect\Teleskope_Apps_V2_Constants;
use Microsoft\Graph\Connect\Teleskope_Admin_V2_Constants;

require_once __DIR__ . '/../vendor/autoload.php';

//use Microsoft\Graph\Connect\Constants;

//We store user name, id, and tokens in session variables
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION['app_type']=== 'officeraven') {
    if (isset($_SESSION['authenticator_version']) && $_SESSION['authenticator_version'] == 2) {
        $constants = new Teleskope_Apps_V2_Constants();
    } else {
        $constants = new OfficeRavenConstants();
    }
} elseif ($_SESSION['app_type']=== 'teleskope') {
    if (isset($_SESSION['authenticator_version']) && $_SESSION['authenticator_version'] == 2) {
        $constants = new Teleskope_Admin_V2_Constants();
    } else {
        $constants = new TeleskopeConstants();
    }
} else {
    if (isset($_SESSION['authenticator_version']) && $_SESSION['authenticator_version'] == 2) {
        $constants = new Teleskope_Apps_V2_Constants();
    } else {
        $constants = new AffinitiesConstants();
    }
}

$authorityUrl = 'https://login.microsoftonline.com/'.$_SESSION['tenantguid'];

$provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId'                => $constants::CLIENT_ID,
    'clientSecret'            => $constants::CLIENT_SECRET,
    'redirectUri'             => $constants::REDIRECT_URI,
//    'urlAuthorize'            => $constants::AUTHORITY_URL . $constants::AUTHORIZE_ENDPOINT,
//    'urlAccessToken'          => $constants::AUTHORITY_URL . $constants::TOKEN_ENDPOINT,
    'urlAuthorize'            => $authorityUrl . $constants::AUTHORIZE_ENDPOINT,
    'urlAccessToken'          => $authorityUrl . $constants::TOKEN_ENDPOINT,
    'urlResourceOwnerDetails' => '',
    'scopes'                  => $constants::SCOPES
]);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['code']) && !isset($_GET['error'])) {
    $authorizationUrl = $provider->getAuthorizationUrl();

	//$authorizationUrl = $authorizationUrl."&prompt=consent"; Forces a consent everytime

    // The OAuth library automaticaly generates a state value that we can
    // validate later. We just save it for now.
    $_SESSION['state'] = $provider->getState();


    header('Location: ' . $authorizationUrl);
    exit();
}
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['error'])) {
    // Answer from the authentication service contains an error.
    unset($_SESSION['state']);
    exit("Something went wrong while authenticating: Errors={$_GET['error']}, Description={$_GET['error_description']}");
}
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['code'])) {
    // Validate the OAuth state parameter
    if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['state'])) {
        error_log("Got State: '{$_GET['state']}'', while the session state is '{$_SESSION['state']}'");
        unset($_SESSION['state']);
        exit('State value does not match the one initially sent');
    }

    // With the authorization code, we can retrieve access tokens and other data.
    try {
        // Get an access token using the authorization code grant
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code'     => $_GET['code']
        ]);
        $_SESSION['access_token'] = $accessToken->getToken();
        
        // The id token is a JWT token that contains information about the user
        // It's a base64 coded string that has a header, payload and signature
		$accessTokenValues = $accessToken->getValues();
		if (!empty($accessTokenValues['id_token'])) {
			$idToken = $accessTokenValues['id_token'];
			$decodedAccessTokenPayload = base64_decode(
				explode('.', $idToken)[1]
			);
			$jsonAccessTokenPayload = json_decode($decodedAccessTokenPayload, true);
		
			$_SESSION['office365_data'] =  $jsonAccessTokenPayload;
		} else {
			$_SESSION['office365_data'] = json_decode('{"preferred_username":"not_supplied"}', true);
		}
        // The following user properties are needed in the next page
        //$_SESSION['preferred_username'] = $jsonAccessTokenPayload['preferred_username'];
        //$_SESSION['given_name'] = $jsonAccessTokenPayload['name'];

        if (empty($_SESSION['office365_data'])) {
            printf('Something went wrong, couldn\'t authenticate user');
            exit();
        }

        header('Location: '.$constants::HOME_URI.'');
        exit();
    } catch (League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        printf('Something went wrong, couldn\'t get tokens: %s', $e->getMessage());
    }
}
