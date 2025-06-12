<?php

require_once __DIR__ . '/head.php';

Auth::CheckSuperSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (($_GET['action'] ?? '') === 'new')) {
    $companies = $_SUPER_ADMIN->super_get('SELECT `companyid`, `companyname`, `subdomain` FROM `companies` WHERE `isactive` = 1');

    $super_admin = null;
    $db = new Hems();
    $password = $db->codegenerate2();

    require __DIR__ . '/views/headermain.html';
    require __DIR__ . '/views/new_super_admin.html.php';
    require __DIR__ . '/views/footer.html';

    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (($_GET['action'] ?? '') === 'edit')) {
    $super_admin = $_SUPER_ADMIN->getSuperAdmin($_GET['superid']);

    if ($super_admin['is_super_super_admin']) {
        echo "Cannot edit a super-super-admin, A super-super-admin has access to ALL companies and ALL permissions";
        exit();
    }

    $companies = $_SUPER_ADMIN->super_get('SELECT `companyid`, `companyname`, `subdomain` FROM `companies` WHERE `isactive` = 1');

    require __DIR__ . '/views/headermain.html';
    require __DIR__ . '/views/new_super_admin.html.php';
    require __DIR__ . '/views/footer.html';

    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'create')) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username) {
        AjaxResponse::SuccessAndExit_STRING(
            0,
            '',
            'Username cannot be empty',
            gettext('Error')
        );
    }

    if (!$email) {
        AjaxResponse::SuccessAndExit_STRING(
            0,
            '',
            'Email cannot be empty',
            gettext('Error')
        );
    }

    if (!Str::ValidatePassword($password)) {
        AjaxResponse::SuccessAndExit_STRING(
            0,
            '',
            'Invalid Password',
            gettext('Error')
        );
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $manage_company_ids = '';
    if (is_array($_POST['manage_company_ids'])) {
        $manage_company_ids = implode(',', array_filter(array_map('intval', $_POST['manage_company_ids'])));
    }

    $permissions = json_encode($_POST['permissions'] ?? []);

    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

    $super_admin_id = $_SUPER_ADMIN->super_insert_ps(
        'INSERT INTO `admin` (
            `username`,
            `email`,
            `password`,
            `google_auth_code`,
            `manage_companyids`,
            `permissions`,
            `last_five_passwords`,
            `expiry_date`,
            `createdon`,
            `modified`,
            `isactive`
        ) VALUES (
            ?,?,?,?,?,?,?,?,?,?,?
        )',
        'sssssxssssi',
        $username,
        $email,
        $password_hash,
        '',
        $manage_company_ids,
        $permissions,
        '',
        $now->modify('+90 days')->format('Y-m-d H:i:s'),
        $now->format('Y-m-d H:i:s'),
        $now->format('Y-m-d H:i:s'),
        Teleskope::STATUS_INACTIVE
    );

    $_AUDIT_META = [
        'object_type' => 'super_admin',
        'operation' => 'create',
        'operation_details' => [
            'super_id' => $super_admin_id,
            'new' => [
                'manage_companyids' => $manage_company_ids,
                'permissions' => $permissions,
            ]
        ]
    ];

    AjaxResponse::SuccessAndExit_STRING(
        1,
        '',
        'Successfully created super-admin',
        gettext('Success')
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'update')) {
    if (!empty($_POST['update_password'])) {
        $password = trim($_POST['password'] ?? '');

        if (!Str::ValidatePassword($password)) {
            AjaxResponse::SuccessAndExit_STRING(
                0,
                '',
                'Invalid Password',
                gettext('Error')
            );
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
    }

    $super_admin = $_SUPER_ADMIN->getSuperAdmin($_POST['super_admin_id']);

    $manage_company_ids = '';
    if (is_array($_POST['manage_company_ids'])) {
        $manage_company_ids = implode(',', array_filter(array_map('intval',$_POST['manage_company_ids'])));
    }

    $_SUPER_ADMIN->super_update_ps(
        'UPDATE `admin` SET `password` = ?, `manage_companyids` = ?, `permissions` = ? WHERE `superid` = ?',
        'ssxi',
        $password_hash ?? $super_admin['password'],
        $manage_company_ids,
        json_encode($_POST['permissions'] ?? []),
        $super_admin['superid']
    );

    $_AUDIT_META = [
        'object_type' => 'super_admin',
        'operation' => 'update',
        'operation_details' => [
            'super_id' => $super_admin['superid'],
            'old' => [
                'manage_companyids' => $super_admin['manage_companyids'],
                'permissions' => $super_admin['permissions'],
            ],
            'new' => [
                'manage_companyids' => $_POST['manage_company_ids'],
                'permissions' => $_POST['permissions'] ?? [],
            ]
        ]
    ];

    AjaxResponse::SuccessAndExit_STRING(
        1,
        '',
        'Successfully updated super-admin',
        gettext('Success')
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'activeToggleSuperAdmin')) {
    $super_admin = $_SUPER_ADMIN->getSuperAdmin($_POST['super_admin_id']);

    $isactive = ((int) $super_admin['isactive']) ? Teleskope::STATUS_INACTIVE : Teleskope::STATUS_ACTIVE;

    $_SUPER_ADMIN->super_update("UPDATE `admin` SET `isactive` = {$isactive} WHERE `superid` = {$super_admin['superid']}");

    AjaxResponse::SuccessAndExit_STRING(
        1,
        '',
        $isactive ? 'Successfully activated' : 'Successfully deactivated',
        gettext('Success')
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'unblockSuperAdmin')) {
    $super_admin = $_SUPER_ADMIN->getSuperAdmin($_POST['super_admin_id']);

    if (!$super_admin['is_blocked']) {
        AjaxResponse::SuccessAndExit_STRING(
            0,
            '',
            'User is already unblocked',
            gettext('Error')
        );
    }

    $_SUPER_ADMIN->super_update("UPDATE `admin` SET `failed_login_attempts` = 0 WHERE `superid` = {$super_admin['superid']}");

    AjaxResponse::SuccessAndExit_STRING(
        1,
        '',
        'Successfully unblocked',
        gettext('Success')
    );

    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'renewPassword')) {
    $super_admin = $_SUPER_ADMIN->getSuperAdmin($_POST['super_admin_id']);

    if (!$super_admin['is_expired']) {
        AjaxResponse::SuccessAndExit_STRING(
            0,
            '',
            "User's password is still active",
            gettext('Error')
        );
    }

    $_SUPER_ADMIN->super_update("UPDATE `admin` SET `expiry_date` = NOW() + INTERVAL 1 DAY WHERE `superid` = {$super_admin['superid']}");

    AjaxResponse::SuccessAndExit_STRING(
        1,
        '',
        'Successfully renewed password for a day',
        gettext('Success')
    );

    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_GET['action'] ?? '') === 'resetGoogleAuthToken')) {
    $super_admin = $_SUPER_ADMIN->getSuperAdmin($_POST['super_admin_id']);

    if (empty($super_admin['google_auth_code'])) {
        AjaxResponse::SuccessAndExit_STRING(
            0,
            '',
            '2FA token already cleared',
            gettext('Error')
        );
    }

    $_SUPER_ADMIN->super_update("UPDATE `admin` SET `google_auth_code` = '' WHERE `superid` = {$super_admin['superid']}");

    AjaxResponse::SuccessAndExit_STRING(
        1,
        '',
        'Successfully cleared the 2FA Token',
        gettext('Success')
    );

    exit();
}
