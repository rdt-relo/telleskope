<?php

// Do no use require_once as this class is included in Company.php.

class Organization extends Teleskope
{
    use TopicCustomFieldsTrait;

    protected function __construct(int $id,int $cid,array $fields)
    {
        parent::__construct($id, $cid, $fields);
    }

    const ORGANIZATION_TYPE_MAP = [
        '1' => 'For Profit',
        '2' => 'Non-Profit',
        '3' => 'Government/Public'
    ];

    const ORGANIZATION_EMAIL_TYPES = array(
        'organization_contact' => 'organization_contact'
    );

    const ORGANIZATION_EMAIL_TEMPLATE_KEYS = array(
        'organization_email_cc' => 'organization_email_cc',
        'organization_email_subject' => 'organization_email_subject',
        'organization_email_body' => 'organization_email_body'
    );

    const ORGANIZATION_CONFIRMATION_STATUS = array(
        'NEW_ORGANIZATION' => 0,
        'CONFIRMED' => 1,
        'PENDING_CONFIRMATION' => 2,
    );

    public static function GetTopicType(): string
    {
        return self::TOPIC_TYPES['ORGANIZATION'];
    }

    public static function GetOrganization(int $organization_id, bool $from_master_db = true): ?Organization
    {
        global $_COMPANY;

        $sql = "SELECT * FROM `company_organizations` WHERE `companyid`={$_COMPANY->id()} AND `organization_id`={$organization_id}";

        $row = $from_master_db ? self::DBGet($sql) : self::DBROGet($sql);

        if (!empty($row)) {
            return new Organization($organization_id,$_COMPANY->id(), $row[0]);
        }
        return null;
    }

    public static function GetOrganizationByAPIOrgId(int $api_org_id, bool $from_master_db = true): ?Organization
    {
        global $_COMPANY;

        $sql = "SELECT * FROM `company_organizations` WHERE `companyid`={$_COMPANY->id()} AND `api_org_id`={$api_org_id}";

        $row = $from_master_db ? self::DBGet($sql) : self::DBROGet($sql);

        if (!empty($row)) {
            return new Organization($row[0]['organization_id'],$_COMPANY->id(), $row[0]);
        }
        return null;
    }

    public static function FetchAdditionalDataOfOrg(int $orgId, int $eventId){
        global $_COMPANY;
        $row = self::DBGet("SELECT eo.additional_contacts, eo.custom_fields FROM `event_organizations` eo JOIN `company_organizations` org ON org.organization_id = eo.organizationid WHERE eo.companyid='{$_COMPANY->id()}' AND eventid='{$eventId}' AND eo.organizationid={$orgId}");
        if (!empty($row)) {
            return $row;
        }
        return null;
    }
    
    /**
     * AddUpdateOrganization
     *
     * @param  int $organization_id
     * @param  string $organization_name
     * @param  string $organization_taxid
     * @param  string $address_street
     * @param  string $address_city
     * @param  string $address_state
     * @param  string $address_country
     * @param  string $address_zipcode
     * @param string $organization_url
     * @param  string $organization_type
     * @param string $contact_firstname
     * @param string $contact_lastname
     * @param string $contact_email
     * @param int $api_org_id
     * @param string $company_organization_notes
     * @return int
     */
    public static function AddUpdateOrganization(int $organization_id, string $organization_name, string $organization_taxid, string $address_street, string $address_city, string $address_state, string $address_country, string $address_zipcode, string $organization_url, string $organization_type, string $contact_firstname ='', string $contact_lastname ='', string $contact_email ='', int $api_org_id=0, string $company_organization_notes=''): int
    {
        global $_COMPANY, $_USER;

        $organization_taxid = Sanitizer::SanitizeTaxId($organization_taxid);

        // save or update in partnerpath API. Running through the partnerpath everytime due to controlling parties data being optional.
        $registered_api_org_id = self::RegisterOrganizationInApi($organization_name, $organization_taxid, $address_street, $address_city, $address_state, $address_country, $address_zipcode, $contact_firstname, $contact_lastname, $contact_email, $organization_url, $organization_type, $api_org_id);
        if(!$registered_api_org_id){
            return -1;
        }

        if ($organization_id){
            $retVal = self::DBUpdatePS("UPDATE `company_organizations` SET `organization_name`=?,`organization_taxid`=?, contact_firstname=?, contact_lastname=?, contact_email=?, company_organization_notes=? WHERE `companyid`=? AND `organization_id`=?",'xxxxxxii',$organization_name, $organization_taxid, $contact_firstname, $contact_lastname, $contact_email, $company_organization_notes, $_COMPANY->id(), $organization_id);
            if ($retVal) {
                // Change modified fields only if the update was successful.
                self::DBUpdatePS("UPDATE `company_organizations` SET modifiedon=now(), modifiedby=? WHERE `companyid`=? AND `organization_id`=?",'iii', $_USER->id(), $_COMPANY->id(), $organization_id);
                self::LogObjectLifecycleAudit('update', 'organization', $organization_id, 0);
            }
        } else {
            // Check if the org from partnerpath already exists. If it does then we won't add it in our DB again. We will return the ORG id
            $checkExistingOrg = self::DBGetPS("SELECT organization_id FROM `company_organizations` WHERE companyid=? AND api_org_id=?", 'ii', $_COMPANY->id(), $registered_api_org_id);
            if($checkExistingOrg){
                return $checkExistingOrg[0]['organization_id'];
            }
            $organization_id =  self::DBInsertPS("INSERT INTO `company_organizations` (`organization_name`, `organization_taxid`, `api_org_id`, company_organization_notes, `contact_firstname`, `contact_lastname`, `contact_email`, `createdby`, `companyid`) VALUES (?,?,?,?,?,?,?,?,?)", 'xxixxxxii', $organization_name, $organization_taxid, $registered_api_org_id, $company_organization_notes, $contact_firstname, $contact_lastname, $contact_email, $_USER->id(), $_COMPANY->id());
            if ($organization_id) {
                self::LogObjectLifecycleAudit('create', 'organization', $organization_id, 0);
            }
        }
        return $organization_id;
    }

    /** Returns an array of organization data matching the provided search. Each returned row will also have as
     * special column called total_matches which provides a count of total matches
     * @param $searchTerm
     * @param $start
     * @param $length
     * @return array
     */
    public static function GetOrgDataBySearchTerm(string $searchTerm='', int $start=0, int $length=10, bool $approvedOnly=false): array
    {
        global $_COMPANY;
        $searchIdFilter = '';
        $searchTermVal = '%';
        if ($searchTerm) {
            $decoded_id = $_COMPANY->decodeIdForReport($searchTerm);
            if ($decoded_id) { // Search is for organization id.
                $decoded_id = intval($decoded_id);
                $searchIdFilter = " AND organization_id='{$decoded_id}'"; // Since id is integer, we can add it directly in Sql query instead of passing as parameter
            } else {
                $searchTermVal = "%{$searchTerm}%"; // If search term is provided wrap it in %'s
            }
        }

        $skipUnApproved = $approvedOnly ? ' AND isactive != 0' : '';

        # Note: count(*) OVER () is a window function to efficiently get the total matches without running another query.
        return self::DBROGetPS("
            SELECT 
                   COUNT(*) OVER () AS total_matches, 
                   company_organizations.*
            FROM `company_organizations` 
            WHERE companyid=? AND (organization_name LIKE ? OR organization_taxid LIKE ? ) {$skipUnApproved} {$searchIdFilter} 
            LIMIT ?,?
            ", 'ixxii', $_COMPANY->id(), $searchTermVal, $searchTermVal, $start, $length);
    }

    public static function SearchOrganizationsInPartnerPath(string $searchTerm) {

        $api_url = PARTNERPATH_BASE_URI.'/api/v1/search-organizations/';
        $headers = array(
            'Content-type: application/json',
        );

        $data = array(
            'org_search' => $searchTerm
        );

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // Set Basic Auth credentials
        curl_setopt($ch, CURLOPT_USERPWD, PARTNERPATH_USERNAME . ":" . PARTNERPATH_PASSWORD);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); // Use Basic Auth
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Execute the request
        $response = curl_exec($ch);

        $formattedResults = array();

        if ($response
            && ($response_data = json_decode($response, true)) !== NULL // Is valid JSON response
        ) {
            if (isset($response_data['results']) &&  is_array($response_data['results'])){
                $formattedResults['errors'] = ['code' => '0', 'message' => ''];
                $formattedResults['results'] = array();
                foreach ($response_data['results'] as $org) {
                    $companyOrganization = self::GetOrganizationByAPIOrgId($org['ID']);
                    $formattedResults['results'][] = [
                        'organization_id' => $companyOrganization ? $companyOrganization->val('organization_id') : 0,
                        'organization_not_approved' => $companyOrganization && $companyOrganization->isNotApproved(),
                        'company_organization_notes' => $companyOrganization ? $companyOrganization->val('company_organization_notes') : '',
                        'label' => $org['Name']. " (TaxID - ".$org['TaxID'].")" . ($companyOrganization ?-> isNotApproved() ? ' *** Not Approved ***' : ''),
                        'value' => $org['TaxID']." - ".$org['Name'],
                        'orgid' => $org['ID'],
                        'is_claimed' => $org['IsClaimed'],
                        'organization_name' => $org['Name'],
                        'organization_taxid' => $org['TaxID'],
                        'org_url' =>$org['Website'],
                        'organization_type' =>$org['OrganizationType'],
                        'city' => $org['City'],
                        'state' => $org['State'],
                        'street' => $org['Street'],
                        'country' => $org['Country'],
                        'zipcode' => $org['Zip'],
                        'organization_contact_firstname' => $org['ContactFirstName'],
                        'organization_contact_lastname' =>$org['ContactLastName'],
                        'contact_email'=>$org['ContactEmail'],
                        'organisation_street'=>$org['Street'],
                        'cfo_firstname' => $org['CFOFirstName'],
                        'cfo_lastname' => $org['CFOLastName'],
                        'cfo_dob' => $org['CFODOB'],
                        'ceo_firstname' => $org['CEOFirstName'],
                        'ceo_lastname' => $org['CEOLastName'],
                        'ceo_dob' => $org['CEODOB'],
                        'bm1_firstname' => $org['bm1FirstName'],
                        'bm1_lastname' => $org['bm1LastName'],
                        'bm1_dob' => $org['bm1DOB'],
                        'bm2_firstname' => $org['bm2FirstName'],
                        'bm2_lastname' => $org['bm2LastName'],
                        'bm2_dob' => $org['bm2DOB'],
                        'bm3_firstname' => $org['bm3FirstName'],
                        'bm3_lastname' => $org['bm3LastName'],
                        'bm3_dob' => $org['bm3DOB'],
                        'bm4_firstname' => $org['bm4FirstName'],
                        'bm4_lastname' => $org['bm4LastName'],
                        'bm4_dob' => $org['bm4DOB'],
                        'bm5_firstname' => $org['bm5FirstName'],
                        'bm5_lastname' => $org['bm5LastName'],
                        'bm5_dob' => $org['bm5DOB'],
                        'organization_mission_statement' => $org['MissionStatement']
                    ];
                }
            }
        } else {
            $formattedResults['errors'] = ['code' => '1', 'message' => 'Error connecting to Organization search service'];
            Logger::Log('Org Search Failed: Unable to connect to Organization Service ');
        }

        return $formattedResults;
    }

    public static function GetOrganizationFromPartnerPath(?int $api_org_id){

        $api_org_id = $api_org_id ?? 0; // Null safe
        $response_data = [];
        $headers = array(
            'Content-type: application/json',
        );

        $api_url = PARTNERPATH_BASE_URI.'/api/v1/organization/'.urlencode($api_org_id);

        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // Set Basic Auth credentials
        curl_setopt($ch, CURLOPT_USERPWD, PARTNERPATH_USERNAME.":".PARTNERPATH_PASSWORD);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); // Use Basic Auth
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $response_data = json_decode($response, true);
        return $response_data;
    }


    public static function sendEmailToOrganization(array $contactEmails, string $ccEmail, string $subject, string $message): array
    {
           // check if ORG exists in partnerpath.
            $api_url = PARTNERPATH_BASE_URI.'/api/v1/send-email-to-organization';
     
            $orgData = array(
                'emailReceivers' => $contactEmails,
                'ccEmail' => $ccEmail,
                'subject' => $subject,
                'messageBody' => $message,
            );
            $headers = array(
                'Content-type: application/json',
            );

          // Initialize cURL session
          $ch = curl_init();

          // Set cURL options
          curl_setopt($ch, CURLOPT_URL, $api_url);
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orgData));
          curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
          // Set Basic Auth credentials
          curl_setopt($ch, CURLOPT_USERPWD, PARTNERPATH_USERNAME.":".PARTNERPATH_PASSWORD);
          curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); // Use Basic Auth        
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          // Execute the request
          $response = curl_exec($ch);
          $data = json_decode($response, true);
          if($data){
                return $data;

          }
        return false;
        
    }


    public function unlinkOrganization()
    {
        global $_COMPANY;
        $ae = $this->getAssociatedEvents();
        if (!empty($ae)) {
            return -1;
        }
        return self::DBUpdate("DELETE FROM `company_organizations` WHERE `companyid`='{$_COMPANY->id()}' AND `organization_id`='{$this->id()}'");
    }

      // Add organisation in partner path
      private static function RegisterOrganizationInApi(
          string $organization_name,
          string $organization_taxid,
          string $address_street,
          string $address_city,
          string $address_state,
          string $address_country,
          string $address_zipcode,
          string $contact_firstname,
          string $contact_lastname,
          string $contact_email,
          string $url,
          string $organization_type,
          int $api_org_id
      )
      {

        // check if ORG exists in partnerpath.
        if($api_org_id){
            $api_url = PARTNERPATH_BASE_URI.'/api/v1/update-organization';
        }else{
            $api_url = PARTNERPATH_BASE_URI.'/api/v1/register-organization';
        }

        $orgData = array(
            'orgID' => $api_org_id ?? '',
            'organizationName' => $organization_name,
            'orgTaxId' => $organization_taxid,
            'streetName' => $address_street,
            'cityName' => $address_city,
            'stateName' => $address_state,
            'countryName' => $address_country,
            'zipcode' => $address_zipcode,
            'email' => $contact_email,
            'contactFirstName' => $contact_firstname,
            'contactLastName' => $contact_lastname,
            'url' => $url,
            'organizationType' => $organization_type,
        );
        $headers = array(
            'Content-type: application/json',
        );

          // Initialize cURL session
          $ch = curl_init();

          // Set cURL options
          curl_setopt($ch, CURLOPT_URL, $api_url);
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orgData));
          curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
          // Set Basic Auth credentials
          curl_setopt($ch, CURLOPT_USERPWD, PARTNERPATH_USERNAME.":".PARTNERPATH_PASSWORD);
          curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); // Use Basic Auth        
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          // Execute the request
          $response = curl_exec($ch);
          $data = json_decode($response, true);
          if($data){
            $registeredOrgid = $data['OrgID'];
            return $registeredOrgid;
          }
        return false;
    }

    // Send organisation notification to partner path Email
    public static function GetEmailSubjectAndMessageForPartnerPath(string $orgName, string $orgPartnerPathUrl): array
    {
        global $_ZONE;

        $organization_email_type   =  self::ORGANIZATION_EMAIL_TYPES['organization_contact'];
        list ($organization_email_subject, $organization_email_body)= self::GetOrganizationEmailTemplate($organization_email_type,true);

        $orgPartnerPathUrl = "<a href='".$orgPartnerPathUrl."' target='_blank'>".$orgPartnerPathUrl."</a>";
        $replace_vars = array('[[COMPANY_NAME]]', '[[ORGANIZATION_NAME]]', '[[ORGANIZATION_UPDATE_URL]]');
        $replacement_vars = [
            $_ZONE->val('email_from_label'),$orgName, $orgPartnerPathUrl
        ];
        $email_subject = str_replace($replace_vars, $replacement_vars,$organization_email_subject);
        $email_body = str_replace($replace_vars, $replacement_vars, $organization_email_body);

        return array($email_subject, $email_body);
    }

    // Function to encrypt data for partner path
    public static function EncryptOrgId(int $orgId, string $approversEmail, string $approvalURL) {
        global $_COMPANY;
        $key = PARTNERPATH_AUTH_KEY;
        $enc_companyid = $_COMPANY->encodeId($_COMPANY->id());
        $data = $orgId.':'.date('Y-m-d').':'.$approversEmail.'~'.$enc_companyid.'~'.$approvalURL;
        if (!is_string($data)) {
            throw new InvalidArgumentException('Data must be a string.');
        }
        // Encrypt the data
        $cipher = 'AES-128-CBC';
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $encrypted = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        // Combine IV and encrypted data
        // Encode the encrypted data in base64
        $base64Encoded = base64_encode($iv . $encrypted);
        // Make the base64 encoded string URL-safe
        $urlSafeToken = str_replace(['+', '/', '='], ['-', '_', ''], $base64Encoded);
        return $urlSafeToken;
    }

    /**
     * Updates organizations screening status, valid values defined in self::ORGANIZATION_SCREENING_STATUS
     * @param int $apiOrgId
     * @param int $screeningStatus
     * @param int $companyId optional, provide when calling this function from non company context.
     * @return false|int
     */
    public static function UpdateOrgConfirmationStatus(int $apiOrgId, int $screeningStatus, int $companyId = 0)
    {
        global $_COMPANY;

        if (!in_array($screeningStatus, array_values(self::ORGANIZATION_CONFIRMATION_STATUS)))
            return false;

        if(!$companyId){ // If $companyId is not provided, it indicates that the status change is being triggered via the system by a logged-in user. instead partnerpath API
            $companyId = $_COMPANY->id();
        }
        return self::DBUpdatePS("UPDATE `company_organizations` SET last_confirmation_status=?,`last_confirmation_date`=NOW(),`modifiedon`=NOW() WHERE `companyid`=? AND `api_org_id`=? ",'iii', $screeningStatus, $companyId, $apiOrgId);
    }
    public static function UpdateOrgStatus(int $orgId, int $orgStatus)
    {
        global $_COMPANY;
        return self::DBUpdatePS("UPDATE `company_organizations` SET `isactive`=?, `modifiedon`=NOW() WHERE `companyid`=? AND `organization_id`=? ",'iii', $orgStatus, $_COMPANY->id(), $orgId);
    }

    public static function ProcessOrgData(array $fetchLatestEventOrganizations):array
    {
        $latestOrgData = [];
        foreach($fetchLatestEventOrganizations as $eventOrganizations){
            // Get latest data for this ORG
            $results = Organization::GetOrganizationFromPartnerPath($eventOrganizations['api_org_id']);
            // Fetch additional contacts and custom fields Data
            $additional_contacts = json_decode($eventOrganizations['additional_contacts'], true) ?? [];
            $custom_fields = json_decode($eventOrganizations['custom_fields'], true) ?? [];
            if(isset($results['results']) && is_array($results['results'])){
                foreach ($results['results'] as $orgData) {
                    $latestOrgData[] = [
                        'organization_id' => $orgData['ID'],
                        'organization_name' => $orgData['Name'],
                        'organization_taxid' => $orgData['TaxID'],
                        'org_url' =>$orgData['Website'],
                        'organization_type' =>$orgData['OrganizationType'],
                        'city' => $orgData['City'],
                        'state' => $orgData['State'],
                        'street' => $orgData['Street'],
                        'country' => $orgData['Country'],
                        'zipcode' => $orgData['Zip'],
                        'contact_firstname' => $orgData['ContactFirstName'],
                        'contact_lastname' =>$orgData['ContactLastName'],
                        'contact_email'=>$orgData['ContactEmail'],  
                        'organisation_street'=>$orgData['Street'],
                        'cfo_firstname' => $orgData['CFOFirstName'],
                        'cfo_lastname' => $orgData['CFOLastName'],
                        'cfo_dob' => $orgData['CFODOB'],
                        'ceo_firstname' => $orgData['CEOFirstName'],
                        'ceo_lastname' => $orgData['CEOLastName'],
                        'ceo_dob' => $orgData['CEODOB'],
                        'bm1_firstname' => $orgData['bm1FirstName'],
                        'bm1_lastname' => $orgData['bm1LastName'],
                        'bm1_dob' => $orgData['bm1DOB'],
                        'bm2_firstname' => $orgData['bm2FirstName'],
                        'bm2_lastname' => $orgData['bm2LastName'],
                        'bm2_dob' => $orgData['bm2DOB'],
                        'bm3_firstname' => $orgData['bm3FirstName'],
                        'bm3_lastname' => $orgData['bm3LastName'],
                        'bm3_dob' => $orgData['bm3DOB'],
                        'bm4_firstname' => $orgData['bm4FirstName'],
                        'bm4_lastname' => $orgData['bm4LastName'],
                        'bm4_dob' => $orgData['bm4DOB'],
                        'bm5_firstname' => $orgData['bm5FirstName'],
                        'bm5_lastname' => $orgData['bm5LastName'],
                        'bm5_dob' => $orgData['bm5DOB'],
                        'number_of_board_members' => $orgData['NumberOfBoardMembers'],
                        'organization_mission_statement' => $orgData['MissionStatement'],
                        'last_confirmation_date' => $eventOrganizations['last_confirmation_date'],
                        'last_confirmation_status' => $eventOrganizations['last_confirmation_status'],
                        'isactive' => $eventOrganizations['isactive'],
                        'company_org_id' => $eventOrganizations['organization_id'],
                        'company_organization_notes' => $eventOrganizations['company_organization_notes'],
                        'additional_contacts' => $additional_contacts,
                        'custom_fields' => $custom_fields,
                    ];
                }
            }
        }
        return $latestOrgData;
    }
    
    /**
     * SetOrganizationEmailTemplateKeyVal
     *
     * @param  string $organization_email_type
     * @param  string $configuration_key
     * @param  mixed $configuration_val
     * @return int
     */
    public static function SetOrganizationEmailTemplate (string $organization_email_type, ?string $organization_email_subject, ?string $organization_email_body): int
    {
        global $_COMPANY;
        $organization_email_type  = self::ORGANIZATION_EMAIL_TYPES[$organization_email_type] ?? '';
        $retVal = 0;

        if ($organization_email_type) {
            $configuration_key = 'organization.email_templates.' . $organization_email_type;
            $configuration_vals = array(
                'subject' => $organization_email_subject,
                'body' => $organization_email_body,
            );
            $retVal = $_COMPANY->updateCompanyAttributesKeyVal($configuration_key, $configuration_vals);
        }
        return $retVal;
    }

    
    /**
     * GetOrganizationEmailTemplates
     *
     * @param  string $organization_email_type
     * @param  bool $forceReload
     * @return array
     */
    public static function GetOrganizationEmailTemplates(string $organization_email_type='')
    {
        global $_COMPANY, $_ZONE;
        $key = null;

        $configuration_key = 'organization.email_templates';
        if ($organization_email_type){ // if $organization_email_type is empty, then all values are fetched
            $organization_email_type = self::ORGANIZATION_EMAIL_TYPES[$organization_email_type] ?? '';
            $configuration_key .= '.' . $organization_email_type;
        }
        $configuration_vals = $_COMPANY->getCompanyAttributesKeyVal($configuration_key) ?? array();

        return $configuration_vals;
    }

    
    /**
     * GetOrganizationEmailTemplate
     *
     * @param  string $organization_email_type
     * @return array
     */
    public static function GetOrganizationEmailTemplate(string $organization_email_type): array
    {
        global $_COMPANY;

        $emailTemplate = self::GetOrganizationEmailTemplates($organization_email_type);
        if (!empty($emailTemplate['subject'])) {
            $subject = $emailTemplate['subject'];
        } else {
            $subject = sprintf('Please validate your organization info for %1$s', $_COMPANY->val('companyname'));
        }

        if (!empty($emailTemplate['body'])) {
            $message = $emailTemplate['body'];
        } else{
            $message = sprintf('<p>Hello,</p><br><p>Someone at %1$s wants to engage with your organization, [[ORGANIZATION_NAME]], for an event. %1$s carefully conducts due diligence on all organizations with which we engage, and therefore we need some additional information from you in order to ensure we can continue. Please use the link below and validate the information at your earliest convenience to avoid delays or a cancellation of the event.</p><br><p>[[ORGANIZATION_UPDATE_URL]]</p><br><p>Please note that the verification link will expire in 7 days.</p><br><p> By providing information about your organization and the personal information of its members, you confirm you are authorized to do so and that you understand, consent, and agree that %1$s may collect, store, use, and transfer this information to ensure due diligence for this engagement. </p>', $_COMPANY->val('companyname'));
        }

        return array($subject, $message);
    }

    public function getAssociatedEvents(int $start=0, int $length=10)
    {
        global $_COMPANY;
        return self::DBROGet("
            SELECT
                   COUNT(*) OVER () AS total_matches, 
                   eventid, eventtitle,`start`, `end`, events.isactive
            FROM event_organizations
                JOIN events USING (eventid)
            WHERE event_organizations.organizationid={$this->id()}
              AND events.companyid={$_COMPANY->id()}
            LIMIT {$start}, {$length}
        ");
    }

    public function isApproved() :bool
    {
        return $this->val('isactive') == 1;
    }

    public function isNotApproved() :bool
    {
        return $this->val('isactive') == 0;
    }
}
