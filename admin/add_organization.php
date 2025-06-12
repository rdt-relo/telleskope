<?php
require_once __DIR__.'/head.php';
global $_COMPANY, $_ZONE;

// Authorization Check - works for both zone admin and Global admin

if (!$_USER->isCompanyAdmin()) {
    header(HTTP_FORBIDDEN);
    echo "403 Forbidden (Access Denied)";
    exit();
}
$organization_id = 0;
$organization = null;

if (isset($_GET['id'])) {
    $organization_id = $_COMPANY->decodeId($_GET['id']);
    $latestOrgData = [];
    //step 1: Get the ORG data including api_org_id from our DB
    $org = Organization::GetOrganization($organization_id);

    // Get the organization data from partner path.
    $results = Organization::GetOrganizationFromPartnerPath($org->val('api_org_id'));

    if(isset($results['results']) && is_array($results['results'])){
        foreach ($results['results'] as $orgData) {
            $latestOrgData[] = [
                'orgid' => $orgData['ID'],
                'organization_name' => $orgData['Name'],
                'organization_taxid' => $orgData['TaxID'],
                'org_url' =>$orgData['Website'],
                'organization_type' =>$orgData['OrganizationType'],
                'is_claimed' => $orgData['IsClaimed'],
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
                'organization_mission_statement' => $orgData['MissionStatement'],
                'company_organization_notes' => $org->val('company_organization_notes'),
            ];
        }
    }
}

$pagetitle = $organization_id ? "Update Organization" : "Add Organization";

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/add_organization.html');
