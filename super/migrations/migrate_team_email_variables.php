<?php

require_once __DIR__ . '/../head.php';

Auth::CheckSuperSuperAdmin();

global $_SUPER_ADMIN;

$_SUPER_ADMIN->super_update(<<<'SQL'
UPDATE
    `team_role_type`
SET
    `welcome_message` = REPLACE(`welcome_message`, '[[PERSON_NAME]]', '[[PERSON_FIRST_NAME]] [[PERSON_LAST_NAME]]'),
    `joinrequest_message` = REPLACE(`joinrequest_message`, '[[PERSON_NAME]]', '[[PERSON_FIRST_NAME]] [[PERSON_LAST_NAME]]'),
    `completion_message` = REPLACE(`completion_message`, '[[PERSON_NAME]]', '[[PERSON_FIRST_NAME]] [[PERSON_LAST_NAME]]'),
    `member_termination_message` = REPLACE(`member_termination_message`, '[[PERSON_NAME]]', '[[PERSON_FIRST_NAME]] [[PERSON_LAST_NAME]]')
SQL);

echo 'Migration successful';
