<?php
require_once __DIR__.'/head.php';
if (!$_USER->isCompanyAdmin()) {
	header(HTTP_FORBIDDEN);
	echo "403 Forbidden (Access Denied)";
	exit();
}

$pagetitle = "Manage Organization Email Template";

$organization_email_type = Organization::ORGANIZATION_EMAIL_TYPES['organization_contact'];
list(
	$organization_email_subject,
	$organization_email_body
	) = Organization::GetOrganizationEmailTemplate($organization_email_type);

include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/update_organization_email_template.html');