<?php

// saml_realm can be different from the session realm as some applications such as talentpeak, officerave can
// for authentication against affinities.
$saml_realm = $_SESSION['realm'] ?? '';
if (!empty($_SESSION['saml2_settings']['use_affinities_identity'])) {
    $saml_realm = str_replace(['talentpeak', 'officeraven', 'peoplehero'], ['affinities', 'affinities', 'affinities'], $saml_realm);
}

$spBaseUrl = 'https://'.$_SERVER['HTTP_HOST']. '/1/user/saml2';
$flattenEntityIdParameters = $_SESSION['saml2_settings']['flatten_entityid_parameters'] ?? false;

$lmid_suffix = '';
if (!empty($_SESSION['saml2_settings']['add_lmid_to_saml_urls'])) {
    $lmid_suffix = '&lmid=' . $_SESSION['saml2_settings']['settingid'];
}

$entityIdSuffix = $flattenEntityIdParameters ? '/metadata/realm/' : '/metadata?realm=';

// Service Provider Data that we are deploying
$settingsInfo = array(
    // If 'strict' is True, then the PHP Toolkit will reject unsigned
    // or unencrypted messages if it expects them signed or encrypted
    // Also will reject the messages if not strictly follow the SAML
    // standard: Destination, NameId, Conditions ... are validated too.
    'strict' => ($_SESSION['saml2_settings']['strict_mode'])? true : false,

    // Enable debug mode (to print errors)
    'debug' => ($_SESSION['saml2_settings']['debug_mode'])? true : false,

    // Set a BaseURL to be used instead of try to guess
    // the BaseURL of the view that process the SAML Message.
    // Ex. http://sp.example.com/
    //     http://example.com/sp/
    'baseurl' => null,

    'sp' => array(
        // Identifier of the SP entity  (must be a URI)
        'entityId' => $spBaseUrl . $entityIdSuffix . $saml_realm . $lmid_suffix,
        // Specifies info about where and how the <AuthnResponse> message MUST be
        // returned to the requester, in this case our SP.
        'assertionConsumerService' => array(
            // URL Location where the <Response> from the IdP will be returned
            'url' => $spBaseUrl . '/acs?realm=' . $saml_realm . $lmid_suffix,
            // SAML protocol binding to be used when returning the <Response>
            // message.  Onelogin Toolkit supports for this endpoint the
            // HTTP-POST binding only
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        ),
        // If you need to specify requested attributes, set a
        // attributeConsumingService. nameFormat, attributeValue and
        // friendlyName can be omitted. Otherwise remove this section.
        'attributeConsumingService' => array(
            'serviceName' => 'Teleskope '.ucwords($_SESSION['app_type']??'App'),
            'serviceDescription' => 'Corporate Culture Software',
            'requestedAttributes' => array(
//                array(
//                    'name' => 'id',
//                    'isRequired' => false,
//                    'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified',
//                    'friendlyName' => 'Employee Id (or another unique id)'
//                ),
                array(
                    'name' => 'givenName',
                    'isRequired' => true,
                    'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified',
                    'friendlyName' => 'Given Name or First Name'
                ),
                array(
                    'name' => 'familyName',
                    'isRequired' => true,
                    'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified',
                    'friendlyName' => 'Family Name or Last Name'
                ),
                array(
                    'name' => 'email',
                    'isRequired' => true,
                    'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified',
                    'friendlyName' => 'Email Address'
                ),
//                array(
//                    'name' => 'picture',
//                    'isRequired' => false,
//                    'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
//                    'friendlyName' => 'Profile Picture URL'
//                ),
                array(
                    'name' => 'jobTitle',
                    'isRequired' => true,
                    'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified',
                    'friendlyName' => 'Job Title'
                ),
                array(
                    'name' => 'department',
                    'isRequired' => true,
                    'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified',
                    'friendlyName' => 'Department'
                ),
                array(
                    'name' => 'officeLocation',
                    'isRequired' => true,
                    'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified',
                    'friendlyName' => 'Office Location Name '
                ),
                array(
                    'name' => 'companyName',
                    'isRequired' => false,
                    'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified',
                    'friendlyName' => 'Company Name (Opco)'
                ),
                array(
                    'name' => 'employeeType',
                    'isRequired' => false,
                    'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified',
                    'friendlyName' => 'Employee Type (Employee|Consultant)'
                ),
            )
        ),

        // Specifies info about where and how the <Logout Response> message MUST be
        // returned to the requester, in this case our SP.
        //'singleLogoutService' => array (
        // URL Location where the <Response> from the IdP will be returned
        //    'url' => $spBaseUrl.'/sls?realm='.$realm,
        // SAML protocol binding to be used when returning the <Response>
        // message.  Onelogin Toolkit supports for this endpoint the
        // HTTP-Redirect binding only
        //    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        //),

        // Specifies constraints on the name identifier to be used to
        // represent the requested subject.
        // Take a look on lib/Saml2/Constants.php to see the NameIdFormat supported
        'NameIDFormat' =>  ($_SESSION['saml2_settings']['nameid_format'] === 'persistent')
                            ? 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'
                            : 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',

        // Usually x509cert and privateKey of the SP are provided by files placed at
        // the certs folder. But we can also provide them with the following parameters
        //'x509cert' => '',
        //'privateKey' => '',
        // During Key rollover, set x509certNew
        // 'x509certNew' => '',
    ),
    'idp' => array(
        'entityId' => $_SESSION['saml2_settings']['entityid'],
        'singleSignOnService' => array(
            'url' => $_SESSION['saml2_settings']['sso_url'],
        ),
//            'singleLogoutService' => array (
//                'url' => '',
//            ),
        'x509cert' => $_SESSION['saml2_settings']['x509_cert'],
    ),

    // Compression settings
    'compress' => array (
        'requests' => true,
        'responses' => true
    ),
    // Security settings
    'security' => array (

        /** signatures and encryptions offered */

        // Indicates that the nameID of the <samlp:logoutRequest> sent by this SP
        // will be encrypted.
        'nameIdEncrypted' => false,

        // Indicates whether the <samlp:AuthnRequest> messages sent by this SP
        // will be signed.  [Metadata of the SP will offer this info]
        'authnRequestsSigned' => ($_SESSION['saml2_settings']['authn_signed'])? true : false,

        // Indicates whether the <samlp:logoutRequest> messages sent by this SP
        // will be signed.
        'logoutRequestSigned' => false,

        // Indicates whether the <samlp:logoutResponse> messages sent by this SP
        // will be signed.
        'logoutResponseSigned' => false,

        /* Sign the Metadata
         False || True (use sp certs) || array (
                                                    keyFileName => 'metadata.key',
                                                    certFileName => 'metadata.crt'
                                               )
                                      || array (
                                                    'x509cert' => '',
                                                    'privateKey' => ''
                                               )
        */
        'signMetadata' => false,

        /** signatures and encryptions required **/

        // Indicates a requirement for the <samlp:Response>, <samlp:LogoutRequest>
        // and <samlp:LogoutResponse> elements received by this SP to be signed.
        'wantMessagesSigned' => ($_SESSION['saml2_settings']['want_messages_signed'])? true : false,

        // Indicates a requirement for the <saml:Assertion> elements received by
        // this SP to be encrypted.
        'wantAssertionsEncrypted' => ($_SESSION['saml2_settings']['want_assertions_encrypted'])? true : false,

        // Indicates a requirement for the <saml:Assertion> elements received by
        // this SP to be signed. [Metadata of the SP will offer this info]
        'wantAssertionsSigned' => ($_SESSION['saml2_settings']['want_assertions_signed'])? true : false,

        // Indicates a requirement for the NameID element on the SAMLResponse
        // received by this SP to be present.
        'wantNameId' => true,

        // Indicates a requirement for the NameID received by
        // this SP to be encrypted.
        'wantNameIdEncrypted' => ($_SESSION['saml2_settings']['want_nameid_encrypted'])? true : false,

        // Authentication context.
        // Set to false and no AuthContext will be sent in the AuthNRequest.
        // Set true or don't present this parameter and you will get an AuthContext 'exact' 'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport'.
        // Set an array with the possible auth context values: array ('urn:oasis:names:tc:SAML:2.0:ac:classes:Password', 'urn:oasis:names:tc:SAML:2.0:ac:classes:X509').
        'requestedAuthnContext' => ($_SESSION['saml2_settings']['requested_authn_context'])? true : false,

        // Indicates if the SP will validate all received xmls.
        // (In order to validate the xml, 'strict' and 'wantXMLValidation' must be true).
        'wantXMLValidation' => true,

        // If true, SAMLResponses with an empty value at its Destination
        // attribute will not be rejected for this fact.
        'relaxDestinationValidation' => false,

        // If true, Destination URL should strictly match to the address to
        // which the response has been sent.
        // Notice that if 'relaxDestinationValidation' is true an empty Destintation
        // will be accepted.
        'destinationStrictlyMatches' => false,

        // If true, SAMLResponses with an InResponseTo value will be rejectd if not
        // AuthNRequest ID provided to the validation method.
        'rejectUnsolicitedResponsesWithInResponseTo' => false,

        // Algorithm that the toolkit will use on signing process. Options:
        //    'http://www.w3.org/2000/09/xmldsig#rsa-sha1'
        //    'http://www.w3.org/2000/09/xmldsig#dsa-sha1'
        //    'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256'
        //    'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384'
        //    'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512'
        // Notice that sha1 is a deprecated algorithm and should not be used
        'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',

        // Algorithm that the toolkit will use on digest process. Options:
        //    'http://www.w3.org/2000/09/xmldsig#sha1'
        //    'http://www.w3.org/2001/04/xmlenc#sha256'
        //    'http://www.w3.org/2001/04/xmldsig-more#sha384'
        //    'http://www.w3.org/2001/04/xmlenc#sha512'
        // Notice that sha1 is a deprecated algorithm and should not be used
        'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',

        // ADFS URL-Encodes SAML data as lowercase, and the toolkit by default uses
        // uppercase. Turn it True for ADFS compatibility on signature verification
        'lowercaseUrlencoding' => false,
    ),

    // Contact information template, it is recommended to supply a
    // technical and support contacts.
    'contactPerson' => array (
        'technical' => array (
            'givenName' => 'Teleskope Support Team',
            'emailAddress' => 'support@teleskope.io'
        ),
        'support' => array (
            'givenName' => 'Teleskope Support Team',
            'emailAddress' => 'support@teleskope.io'
        ),
    ),

    // Organization information template, the info in en_US lang is
    // recomended, add more if required.
    'organization' => array (
        'en-US' => array(
            'name' => 'Teleskope',
            'displayname' => 'Teleskope, LLC',
            'url' => 'https://www.teleskope.io'
        ),
    ),

);
