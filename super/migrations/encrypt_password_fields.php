<?php

require_once __DIR__ . '/../head.php';

Auth::CheckSuperSuperAdmin();

function migrateAllData()
{
    global $_SUPER_ADMIN;

    $companyids = $_SUPER_ADMIN->super_get('SELECT `companyid` FROM `companies` WHERE `isactive` = 1 AND `status` = 1');
    $companyids = array_column($companyids, 'companyid');

    foreach ($companyids as $companyid) {
        migrateCompanyData($companyid);
    }
}

function migrateCompanyData(int $companyid): void
{
    global $_COMPANY, $_ZONE;

    $_COMPANY = null;
    $_ZONE = null;

    $_COMPANY = Company::GetCompany($companyid);

    migrateCompanyEmailSettings();

    migrateJobPasswords();

    migrateAccessTokens();
}

function migrateCompanyEmailSettings()
{
    global $_COMPANY, $_SUPER_ADMIN;

    $result = $_SUPER_ADMIN->super_get("
        SELECT  *
        FROM    `company_email_settings`
        WHERE   `companyid` = {$_COMPANY->id()}
        AND     `isactive` = 1
    ");

    if (empty($result)) {
        return;
    }

    $smtp_password = $result[0]['smtp_password'] ?? '';

    if (!$smtp_password
        || str_starts_with($smtp_password, 'kms:')
    ) {
        return;
    }

    $encrypted_smtp_password = CompanyEncKey::Encrypt($smtp_password);

    $_SUPER_ADMIN->super_update_ps(
        '
            UPDATE  `company_email_settings`
            SET     `smtp_password` = ?
            WHERE   `companyid` = ?
        ',
        'si',
        $encrypted_smtp_password,
        $_COMPANY->id()
    );
}

function migrateJobPasswords(): void
{
    // Here we already have encryption-at-rest, so we can skip this
}

function migrateAccessTokens(): void
{
    global $_COMPANY, $_SUPER_ADMIN;

    $integrations = $_SUPER_ADMIN->super_get("SELECT * FROM `integrations` WHERE `companyid` = {$_COMPANY->id()}");

    foreach ($integrations as $integration) {
        $json = json_decode($integration['integration_json'], true);

        $access_token = $json['external']['access_token'] ?? null;
        if (!$access_token
            || str_starts_with($access_token, 'kms:')
        ) {
            continue;
        }

        $json['external']['access_token'] = CompanyEncKey::Encrypt($access_token);
        $integration_json = json_encode($json);

        $_SUPER_ADMIN->super_update_ps(
            '
                UPDATE  `integrations`
                SET     `integration_json` = ?
                WHERE   `integrationid` = ?
                AND     `companyid` = ?
            ',
            'xii',
            $integration_json,
            $integration['integrationid'],
            $_COMPANY->id()
        );
    }
}

migrateAllData();
