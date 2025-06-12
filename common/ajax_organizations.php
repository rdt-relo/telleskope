<?php
if (0) {
    // Dummy if to make every thing else as elseif
}

elseif (isset($_GET['sendOrgEmailsModal']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    if (
        ($approvalEventId = $_COMPANY->decodeId($_POST['eventid']))<1
    ) {
        header(HTTP_FORBIDDEN);
        exit();
    }
    //Data Validation
    if (!isset($_POST['contactEmails'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    $contactEmails = json_encode($_POST['contactEmails']);

    $topicTypeObj = Event::GetEvent($approvalEventId);
    $approval = $topicTypeObj->getApprovalObject();
    // Approvers email
    $approversEmail = $_USER->val('email');
    $zone_aware_url = Url::GetZoneAwareUrlBase($_ZONE->id(), 'admin');
    $approvalURL = $zone_aware_url."view_approval_data.php?topicTypeId={$_COMPANY->encodeId($approvalEventId)}&approvalid={$_COMPANY->encodeId($approval->id())}&topicType={$approval->val('topictype')}";

    $emailsToString = implode(', ', json_decode($contactEmails, true));
    $api_org_id = trim($_POST['api_org_id']);
    $orgName = trim($_POST['orgName']);
    $enc_url = Organization::EncryptOrgId($api_org_id, $approversEmail, $approvalURL);
    $orgParthenrPathUrl= PARTNERPATH_BASE_URI . '/update-organization-info/'.$enc_url;
    list(
        $welcomeEmailSubject,
        $welcomeEmailMessage
        ) = Organization::GetEmailSubjectAndMessageForPartnerPath($orgName, $orgParthenrPathUrl);

    $company_org_id = $_COMPANY->decodeId($_POST['company_org_id']);
    $organization = Organization::GetOrganization($company_org_id);

    include(__DIR__ . "/views/send_org_emails_modal.template.php");
    exit();

}

elseif (isset($_GET['sendOrgEmailsNotification']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    // Authorization Check
    //if (!$_USER->canManageCompanySettings()) {
    //    header(HTTP_FORBIDDEN);
    //    exit();
    //}

    //Data Validation
    if (!isset($_POST['emails'])) {
        header(HTTP_BAD_REQUEST);
        exit();
    }
    // To update the ORG status
    $api_org_id = trim($_POST['api_org_id']);
    $app_type = $_ZONE->val('app_type');
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    $contactEmails = json_decode($_POST['emails'], true);
    $ccEmail = $_POST['ccEmails'] ?? '';

    $response = Organization::sendEmailToOrganization($contactEmails, $ccEmail, $subject, $message);

    if($response['error'] == "1"){
        echo json_encode(['status' => 0, 'message' => $response['message']]);
        exit;
    }

    $orgStatus = '2'; // will always be pening approval
    Organization::UpdateOrgConfirmationStatus($api_org_id, $orgStatus);

    $eventid = $_COMPANY->decodeId($_POST['eventid']);
    $company_org_id = $_COMPANY->decodeId($_POST['company_org_id']);
    $approvalid = $_COMPANY->decodeId($_POST['approvalid']);

    $organization = Organization::GetOrganization($company_org_id, false);
    $approval = Approval::GetApproval($approvalid);
    $additional_data = Organization::FetchAdditionalDataOfOrg($company_org_id, $eventid);
    $additional_contact_details = json_decode($additional_data[0]['additional_contacts'] ?? '{}', true) ?? [];
    $event_contacts = array_column($additional_contact_details, null, 'email');

    $log_title = sprintf(
        gettext("%s emailed organization '%s'"),
        $_USER->getFullName() . ' (' . $_USER->getEmailForDisplay() . ')',
        $organization->val('organization_name')
    );
    $log_notes = sprintf(
        gettext('Email sent to %s'),
        implode(', ', array_map(function ($email) use ($organization, $event_contacts) {
            if ($organization->val('contact_email') === $email) {
                return "{$organization->val('contact_firstname')} {$organization->val('contact_lastname')} ({$organization->val('contact_email')})";
            }

            if (!isset($event_contacts[$email])) {
                return $email;
            }

            $contact = $event_contacts[$email];
            return "{$contact['firstname']} {$contact['lastname']} ({$contact['email']})";
        }, $contactEmails))
    );

    $approval->addApprovalLog($log_title, $log_notes, false, 'general');

    echo 1;
    exit();

}