<?php

require_once __DIR__ . '/../head.php';

Auth::CheckSuperSuperAdmin();

function migrateAllData()
{
    global $_SUPER_ADMIN;

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `topic_approvals__logs`
        SET     `approval_log_type` = 'auto_approved'
        WHERE   `approval_log_type` IS NULL
        AND     `log_notes` = 'Your request is pre-approved'
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `topic_approvals__logs`
        SET     `approval_log_type` = 'approved'
        WHERE   `approval_log_type` IS NULL
        AND     (
            `log_title` LIKE 'Approved by%'
            OR `log_title` LIKE 'Stage approval approved by%'
        )
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `topic_approvals__logs`
        SET     `approval_log_type` = 'denied'
        WHERE   `approval_log_type` IS NULL
        AND     `log_title` LIKE 'Denied by%'
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `topic_approvals__logs`
        SET     `approval_log_type` = 'requested'
        WHERE   `approval_log_type` IS NULL
        AND     `log_title` LIKE 'Requested by%'
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `topic_approvals__logs`
        SET     `approval_log_type` = 'assignment'
        WHERE   `approval_log_type` IS NULL
        AND     `log_title` LIKE 'Assigned by%'
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `topic_approvals__logs`
        SET     `approval_log_type` = 'reset'
        WHERE   `approval_log_type` IS NULL
        AND     `log_title` LIKE 'Approval reset by%'
    SQL);

    $_SUPER_ADMIN->super_update(<<<'SQL'
        UPDATE  `topic_approvals__logs`
        SET     `approval_log_type` = 'general'
        WHERE   `approval_log_type` IS NULL
        AND     `log_title` NOT LIKE 'Approval reset by%'
        AND     `log_title` NOT LIKE 'Assigned by%'
        AND     `log_title` NOT LIKE 'Requested by%'
        AND     `log_title` NOT LIKE 'Denied by%'
        AND     `log_title` NOT LIKE 'Approved by%'
        AND     `log_title` NOT LIKE 'Stage approval approved by%'
    SQL);
}

migrateAllData();

echo 'Migration successful <br>';
