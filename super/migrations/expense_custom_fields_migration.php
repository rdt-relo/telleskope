<?php

require_once __DIR__ . '/../head.php';

Auth::CheckSuperSuperAdmin();

function migrateAllData()
{
    $db = new Hems();

    $companyids = $db->ro_get('SELECT `companyid` FROM `companies` WHERE `isactive` = 1 AND `status` = 1');
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

    $zones = $_COMPANY->getZones();
    foreach ($zones as $zone) {
        $_ZONE = $_COMPANY->getZone($zone['zoneid']);
        migrateCompanyZoneData();
    }

    $_COMPANY = null;
    $_ZONE = null;
}

function migrateCompanyZoneData()
{
    addIsPaidCustomField();
    migrateIsPaidCustomField();

    addPONumberCustomField();
    migratePONumberCustomField();

    addInvoiceNumberCustomField();
    migrateInvoiceNumberCustomField();
}

function addIsPaidCustomField()
{
    global $_COMPANY, $_ZONE;
    global $_SUPER_ADMIN;

    $db = new Hems();

    $result = $db->get("
        SELECT EXISTS (
            SELECT  *
            FROM    `budgetuses`
            WHERE   `is_paid` = 1
            AND     `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
        ) AS `row_exists`
    ");

    if ($result[0]['row_exists'] !== '1') {
        echo "Is Paid/Reimbursed? > Skipping companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()}, no expense-entry has this checkbox selected <br>";
        return;
    }

    $result = $db->get("
        SELECT  *
        FROM    `event_custom_fields`
        WHERE   `custom_field_name` = 'Paid/Reimbursed?'
        AND     `custom_fields_type` = 2
        AND     `topictype` = 'EXP'
        AND     `companyid` = {$_COMPANY->id()}
        AND     `zoneid` = {$_ZONE->id()}
    ");

    if (count($result)) {
        echo "Is Paid/Reimbursed? > Skipping companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()}, this zone already has a custom-field of paid/reimbursed, " . json_encode($result[0]) . "<br>";
        return;
    }

    $custom_field_id = 0;
    $custom_field_name = 'Paid/Reimbursed?';
    $custom_fields_type = 2;
    $is_required = false;
    $custom_field_note = '';
    $visibleIfLogic = json_encode([]);
    $custom_field_option_ids = [0];
    $custom_fields_options = ['Yes'];
    $custom_fields_options_note = [''];
    $topictype = 'EXP';

	$custom_field_id = Event::AddUpdateEventCustomField($custom_field_id, $custom_field_name, $custom_fields_type, $is_required, $custom_field_note, $visibleIfLogic, $custom_field_option_ids,$custom_fields_options,$custom_fields_options_note, $topictype);

    $_SUPER_ADMIN->super_update("
        UPDATE  `event_custom_fields`
        SET     `isactive` = 1
        WHERE   `custom_field_id` = {$custom_field_id}
    ");

    $_SUPER_ADMIN->super_update("
        UPDATE  `event_custom_field_options`
        SET     `isactive` = 1
        WHERE   `custom_field_id` = {$custom_field_id}
    ");

    echo "Is Paid/Reimbursed? > Created custom-field Paid/Reimbursed? in companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()} <br>";
}

function migrateIsPaidCustomField()
{
    global $_COMPANY, $_ZONE;
    global $_SUPER_ADMIN;

    $db = new Hems();

    $result = $db->get("
        SELECT  `custom_field_id`
        FROM    `event_custom_fields`
        WHERE   `custom_field_name` = 'Paid/Reimbursed?'
        AND     `custom_fields_type` = 2
        AND     `topictype` = 'EXP'
        AND     `companyid` = {$_COMPANY->id()}
        AND     `zoneid` = {$_ZONE->id()}
    ");

    if (!$result) {
        return;
    }

    $custom_field_id = (int) $result[0]['custom_field_id'];

    $result = $db->get("
        SELECT  `custom_field_option_id`
        FROM    `event_custom_field_options`
        WHERE   `custom_field_option` = 'Yes'
        AND     `custom_field_id` = {$custom_field_id}
    ");

    $custom_field_option_id = (int) $result[0]['custom_field_option_id'];

    $dbc = GlobalGetDBROConnection();

    $sql = "
        SELECT  *
        FROM    `budgetuses`
        WHERE   `is_paid` = 1
        AND     `companyid` = {$_COMPANY->id()}
        AND     `zoneid` = {$_ZONE->id()}
    ";

    $result = mysqli_query($dbc, $sql) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

    while ($row = mysqli_fetch_assoc($result)) {
        $expense_entry = ExpenseEntry::Hydrate($row['usesid'], $row);

        $custom_field = (function (ExpenseEntry $expense_entry, int $custom_field_id) {
            $custom_fields = (json_decode($expense_entry->val('custom_fields') ?? '', true)) ?? [];

            foreach ($custom_fields as $custom_field) {
                if ((int) $custom_field['custom_field_id'] === $custom_field_id) {
                    return $custom_field;
                }
            }

            return null;
        })($expense_entry, $custom_field_id);

        // Skip expense entries that already have the custom-field
        // It means the migration has already been run
        // And now the latest value is the one in the custom_fields column, and the original value might be out-of-date
        if ($custom_field) {
            echo "Skipping expense entry {$expense_entry->id()}, companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()} > This expense entry already has this custom-field, {$expense_entry->val('custom_fields')} <br>";
            continue;
        }

        $custom_fields = (json_decode($expense_entry->val('custom_fields') ?? '', true)) ?? [];
        $custom_fields[] = [
            'custom_field_id' => $custom_field_id,
            'value' => [
                $custom_field_option_id,
            ],
        ];

        $updated_custom_fields_json = json_encode($custom_fields);
        $_SUPER_ADMIN->super_update_ps(
            "UPDATE `budgetuses` SET `custom_fields` = ? WHERE `usesid` = {$expense_entry->id()}",
            'x',
            $updated_custom_fields_json
        );

        echo "Migrated expense entry {$expense_entry->id()}, companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()} > Added the custom-field Paid/Reimbursed?, {$updated_custom_fields_json} <br>";
    }
}

function addPONumberCustomField()
{
    global $_COMPANY, $_ZONE;
    global $_SUPER_ADMIN;

    $db = new Hems();

    $result = $db->get("
        SELECT EXISTS (
            SELECT  *
            FROM    `budgetuses`
            WHERE   `po_number` IS NOT NULL
            AND     `po_number` != ''
            AND     `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
        ) AS `row_exists`
    ");

    if ($result[0]['row_exists'] !== '1') {
        echo "PO Number > Skipping companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()}, no expense-entry has any data for PO Number <br>";
        return;
    }

    $result = $db->get("
        SELECT  *
        FROM    `event_custom_fields`
        WHERE   `custom_field_name` = 'PO Number'
        AND     `custom_fields_type` = 4
        AND     `topictype` = 'EXP'
        AND     `companyid` = {$_COMPANY->id()}
        AND     `zoneid` = {$_ZONE->id()}
    ");

    if (count($result)) {
        echo "PO Number > Skipping companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()}, this zone already has a custom-field of PO Number, " . json_encode($result[0]) . "<br>";
        return;
    }

    $custom_field_id = 0;
    $custom_field_name = 'PO Number';
    $custom_fields_type = 4;
    $is_required = false;
    $custom_field_note = '';
    $visibleIfLogic = json_encode([]);
    $custom_field_option_ids = [];
    $custom_fields_options = [];
    $custom_fields_options_note = [''];
    $topictype = 'EXP';

	$custom_field_id = Event::AddUpdateEventCustomField($custom_field_id, $custom_field_name, $custom_fields_type, $is_required, $custom_field_note, $visibleIfLogic, $custom_field_option_ids,$custom_fields_options,$custom_fields_options_note, $topictype);

    $_SUPER_ADMIN->super_update("
        UPDATE  `event_custom_fields`
        SET     `isactive` = 1
        WHERE   `custom_field_id` = {$custom_field_id}
    ");

    echo "PO Number > Created custom-field PO Number in companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()} <br>";
}

function migratePONumberCustomField()
{
    global $_COMPANY, $_ZONE;
    global $_SUPER_ADMIN;

    $db = new Hems();

    $result = $db->get("
        SELECT  `custom_field_id`
        FROM    `event_custom_fields`
        WHERE   `custom_field_name` = 'PO Number'
        AND     `custom_fields_type` = 4
        AND     `topictype` = 'EXP'
        AND     `companyid` = {$_COMPANY->id()}
        AND     `zoneid` = {$_ZONE->id()}
    ");

    if (!$result) {
        return;
    }

    $custom_field_id = (int) $result[0]['custom_field_id'];

    $dbc = GlobalGetDBROConnection();

    $sql = "
        SELECT  *
        FROM    `budgetuses`
        WHERE   `po_number` IS NOT NULL
        AND     `po_number` != ''
        AND     `companyid` = {$_COMPANY->id()}
        AND     `zoneid` = {$_ZONE->id()}
    ";

    $result = mysqli_query($dbc, $sql) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

    while ($row = mysqli_fetch_assoc($result)) {
        $expense_entry = ExpenseEntry::Hydrate($row['usesid'], $row);

        $custom_field = (function (ExpenseEntry $expense_entry, int $custom_field_id) {
            $custom_fields = (json_decode($expense_entry->val('custom_fields') ?? '', true)) ?? [];

            foreach ($custom_fields as $custom_field) {
                if ((int) $custom_field['custom_field_id'] === $custom_field_id) {
                    return $custom_field;
                }
            }

            return null;
        })($expense_entry, $custom_field_id);

        // Skip expense entries that already have the custom-field
        // It means the migration has already been run
        // And now the latest value is the one in the custom_fields column, and the original value might be out-of-date
        if ($custom_field) {
            echo "Skipping expense entry {$expense_entry->id()}, companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()} > This expense entry already has this custom-field of PO Number, {$expense_entry->val('custom_fields')} <br>";
            continue;
        }

        $custom_fields = (json_decode($expense_entry->val('custom_fields') ?? '', true)) ?? [];
        $custom_fields[] = [
            'custom_field_id' => $custom_field_id,
            'value' => $expense_entry->val('po_number'),
        ];

        $updated_custom_fields_json = json_encode($custom_fields);
        $_SUPER_ADMIN->super_update_ps(
            "UPDATE `budgetuses` SET `custom_fields` = ? WHERE `usesid` = {$expense_entry->id()}",
            'x',
            $updated_custom_fields_json
        );

        echo "Migrated expense entry {$expense_entry->id()}, companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()} > Added the custom-field PO Number, {$updated_custom_fields_json} <br>";
    }
}

function addInvoiceNumberCustomField()
{
    global $_COMPANY, $_ZONE;
    global $_SUPER_ADMIN;

    $db = new Hems();

    $result = $db->get("
        SELECT EXISTS (
            SELECT  *
            FROM    `budgetuses`
            WHERE   `invoice_number` IS NOT NULL
            AND     `invoice_number` != ''
            AND     `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
        ) AS `row_exists`
    ");

    if ($result[0]['row_exists'] !== '1') {
        echo "PO Number > Skipping companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()}, no expense-entry has any data for Invoice Number <br>";
        return;
    }

    $result = $db->get("
        SELECT  *
        FROM    `event_custom_fields`
        WHERE   `custom_field_name` = 'Invoice Number'
        AND     `custom_fields_type` = 4
        AND     `topictype` = 'EXP'
        AND     `companyid` = {$_COMPANY->id()}
        AND     `zoneid` = {$_ZONE->id()}
    ");

    if (count($result)) {
        echo "PO Number > Skipping companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()}, this zone already has a custom-field of Invoice Number, " . json_encode($result[0]) . "<br>";
        return;
    }

    $custom_field_id = 0;
    $custom_field_name = 'Invoice Number';
    $custom_fields_type = 4;
    $is_required = false;
    $custom_field_note = '';
    $visibleIfLogic = json_encode([]);
    $custom_field_option_ids = [];
    $custom_fields_options = [];
    $custom_fields_options_note = [''];
    $topictype = 'EXP';

	$custom_field_id = Event::AddUpdateEventCustomField($custom_field_id, $custom_field_name, $custom_fields_type, $is_required, $custom_field_note, $visibleIfLogic, $custom_field_option_ids,$custom_fields_options,$custom_fields_options_note, $topictype);

    $_SUPER_ADMIN->super_update("
        UPDATE  `event_custom_fields`
        SET     `isactive` = 1
        WHERE   `custom_field_id` = {$custom_field_id}
    ");

    echo "PO Number > Created custom-field Invoice Number in companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()} <br>";
}

function migrateInvoiceNumberCustomField()
{
    global $_COMPANY, $_ZONE;
    global $_SUPER_ADMIN;

    $db = new Hems();

    $result = $db->get("
        SELECT  `custom_field_id`
        FROM    `event_custom_fields`
        WHERE   `custom_field_name` = 'Invoice Number'
        AND     `custom_fields_type` = 4
        AND     `topictype` = 'EXP'
        AND     `companyid` = {$_COMPANY->id()}
        AND     `zoneid` = {$_ZONE->id()}
    ");

    if (!$result) {
        return;
    }

    $custom_field_id = (int) $result[0]['custom_field_id'];

    $dbc = GlobalGetDBROConnection();

    $sql = "
        SELECT  *
        FROM    `budgetuses`
        WHERE   `invoice_number` IS NOT NULL
        AND     `invoice_number` != ''
        AND     `companyid` = {$_COMPANY->id()}
        AND     `zoneid` = {$_ZONE->id()}
    ";

    $result = mysqli_query($dbc, $sql) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbc), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR) . " *Internal Error: R_DBR-11*"));

    while ($row = mysqli_fetch_assoc($result)) {
        $expense_entry = ExpenseEntry::Hydrate($row['usesid'], $row);

        $custom_field = (function (ExpenseEntry $expense_entry, int $custom_field_id) {
            $custom_fields = (json_decode($expense_entry->val('custom_fields') ?? '', true)) ?? [];

            foreach ($custom_fields as $custom_field) {
                if ((int) $custom_field['custom_field_id'] === $custom_field_id) {
                    return $custom_field;
                }
            }

            return null;
        })($expense_entry, $custom_field_id);

        // Skip expense entries that already have the custom-field
        // It means the migration has already been run
        // And now the latest value is the one in the custom_fields column, and the original value might be out-of-date
        if ($custom_field) {
            echo "Skipping expense entry {$expense_entry->id()}, companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()} > This expense entry already has this custom-field of Invoice Number, {$expense_entry->val('custom_fields')} <br>";
            continue;
        }

        $custom_fields = (json_decode($expense_entry->val('custom_fields') ?? '', true)) ?? [];
        $custom_fields[] = [
            'custom_field_id' => $custom_field_id,
            'value' => $expense_entry->val('invoice_number'),
        ];

        $updated_custom_fields_json = json_encode($custom_fields);
        $_SUPER_ADMIN->super_update_ps(
            "UPDATE `budgetuses` SET `custom_fields` = ? WHERE `usesid` = {$expense_entry->id()}",
            'x',
            $updated_custom_fields_json
        );

        echo "Migrated expense entry {$expense_entry->id()}, companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()} > Added the custom-field Invoice Number, {$updated_custom_fields_json} <br>";
    }
}

migrateAllData();

echo 'Migration successful <br>';
