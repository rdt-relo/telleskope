<?php

require_once __DIR__ . '/../head.php';

$check2 = date('Ymd');
if ($check2 > '20250531') {
    echo "<p>Migration expired</p>";
    echo "<p>Exiting</p>";
    exit();
}

Auth::CheckSuperSuperAdmin();

function migrateAllData()
{
    global $_SUPER_ADMIN;

    $companyids = $_SUPER_ADMIN->super_roget('SELECT `companyid` FROM `companies` WHERE `isactive` = 1 AND `status` = 1');
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
        migrateZoneData();
    }

    $_COMPANY = null;
    $_ZONE = null;
}

function migrateZoneData()
{
    global $_COMPANY, $_ZONE;
    global $_SUPER_ADMIN;

    $recognition_custom_fields = $_SUPER_ADMIN->super_get("SELECT * FROM `recognition_custom_fields` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}'");

    if (empty($recognition_custom_fields)) {
        return;
    }

    $recognition_custom_fields = array_map(function (array $custom_field) {
        if ($custom_field['custom_fields_type'] !== 'input') {
            $custom_field['custom_fields_options'] = json_decode(html_entity_decode($custom_field['custom_fields_options']), true);
        } else {
            $custom_field['custom_fields_options'] = null;
        }

        if ($custom_field['custom_fields_type'] === 'select') {
            $custom_field['new_custom_fields_type'] = 1;
        } elseif ($custom_field['custom_fields_type'] === 'checkbox') {
            $custom_field['new_custom_fields_type'] = 2;
        }

        return $custom_field;
    }, $recognition_custom_fields);

    echo "Migration started - companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()} <br>";

    // Migrate the custom_fields table first
    foreach ($recognition_custom_fields as $i => $custom_field) {
        $results = $_SUPER_ADMIN->super_getps("
            SELECT *
            FROM    `event_custom_fields`
            WHERE   `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
            AND     `topictype` = 'REC'
            AND     `custom_field_name` = ?
        ", 's', $custom_field['custom_field_name']);

        $new_custom_field_id = $results[0]['custom_field_id'] ?? 0;

        if ($new_custom_field_id) {
            echo "Migration skipped - recognitions_custom_fields_id {$custom_field['custom_field_id']} -> event_custom_fields_id {$new_custom_field_id} <br>";
        }

        if (!$new_custom_field_id) {
            $new_custom_field_id = $_SUPER_ADMIN->super_insert_ps("
                INSERT INTO `event_custom_fields` (
                    `companyid`,
                    `zoneid`,
                    `topictype`,
                    `custom_field_name`,
                    `custom_fields_type`,
                    `is_required`,
                    `custom_field_note`,
                    `visible_if`,
                    `sorting_order`,
                    `isactive`
                )
                VALUES (
                    {$_COMPANY->id()},
                    {$_ZONE->id()},
                    'REC',
                    ?,
                    {$custom_field['new_custom_fields_type']},
                    {$custom_field['is_required']},
                    ?,
                    '[]',
                    (SELECT IFNULL(MAX(inner_ecf.sorting_order),0)+1 FROM `event_custom_fields` inner_ecf WHERE inner_ecf.companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()}),
                    {$custom_field['isactive']}
                )
            ", 'ss', $custom_field['custom_field_name'], $custom_field['custom_field_note']);

            echo "Migration successful - recognitions_custom_fields_id {$custom_field['custom_field_id']} -> event_custom_fields_id {$new_custom_field_id} <br>";
        }

        foreach ($custom_field['custom_fields_options'] as $j => $custom_fields_option) {
            $results = $_SUPER_ADMIN->super_getps("
                SELECT *
                FROM    `event_custom_field_options`
                WHERE   `custom_field_id` = {$new_custom_field_id}
                AND     `custom_field_option` = ?
            ", 's', $custom_fields_option);

            $new_custom_field_option_id = $results[0]['custom_field_option_id'] ?? 0;

            if ($new_custom_field_option_id) {
                echo "Migration skipped - recognitions_custom_fields_id {$custom_field['custom_field_id']}, option {$custom_fields_option} -> event_custom_fields_id {$new_custom_field_id}, option {$new_custom_field_option_id} <br>";
            }

            if (!$new_custom_field_option_id) {
                $new_custom_field_option_id = $_SUPER_ADMIN->super_insert_ps("
                    INSERT INTO `event_custom_field_options` (
                        `custom_field_id`,
                        `custom_field_option`,
                        `custom_field_option_note`
                    )
                    VALUES (
                        {$new_custom_field_id},
                        ?,
                        ''
                    )
                ", 's', $custom_fields_option);

                echo "Migration successful - recognitions_custom_fields_id {$custom_field['custom_field_id']}, option {$custom_fields_option} -> event_custom_fields_id {$new_custom_field_id}, option {$new_custom_field_option_id} <br>";
            }

            $custom_field['new_custom_fields_options'] ??= [];
            $custom_field['new_custom_fields_options'][] = [
                'custom_field_option' => $custom_fields_option,
                'new_custom_field_option_id' => $new_custom_field_option_id,
            ];
        }

        $custom_field['new_custom_field_id'] = $new_custom_field_id;

        $recognition_custom_fields[$i] = $custom_field;
    }

    $recognition_custom_fields = array_column($recognition_custom_fields, null, 'custom_field_id');

    // Migrate all recognitions having old custom fields
    $results = $_SUPER_ADMIN->super_roget("
        SELECT  *
        FROM    `recognitions`
        WHERE   `companyid` = {$_COMPANY->id()}
        AND     `zoneid` = {$_ZONE->id()}
        AND     `attributes` IS NOT NULL
        AND     `attributes` != ''
        AND     `attributes` != '[]'
        AND     (`custom_fields` IS NULL OR `custom_fields` = '')
    ");

    if (empty($results)) {
        echo "No recognition to migrate<br>";
    }

    foreach ($results as $result) {
        $old_custom_fields = json_decode($result['attributes'] ?? '', true) ?? [];
        $updated_custom_fields = [];
        foreach ($old_custom_fields as $field) {
            $old_custom_field_id = $field['custom_field_id'];
            $custom_field = $recognition_custom_fields[$old_custom_field_id];

            $updated_value = [];

            if (is_string($field['value'])) {
                $field['value'] = [$field['value']];
            }

            foreach ($field['value'] ?? [] as $old_custom_field_option) {
                foreach ($custom_field['new_custom_fields_options'] as $option) {
                    if ($option['custom_field_option'] === $old_custom_field_option) {
                        $updated_value[] = $option['new_custom_field_option_id'];
                        break;
                    }
                }
            }

            $new_custom_field_id = $custom_field['new_custom_field_id'];

            $updated_custom_fields[] = [
                'custom_field_id' => $new_custom_field_id,
                'value' => $updated_value,
            ];
        }

        $updated_custom_fields = json_encode($updated_custom_fields);
        $_SUPER_ADMIN->super_update_ps("UPDATE `recognitions` SET `custom_fields` = ? WHERE `recognitionid` = {$result['recognitionid']}", 'x', $updated_custom_fields);
        echo "Migration successful - recognitionid {$result['recognitionid']} <br>";
    }

    echo "Migration completed - companyid {$_COMPANY->id()}, zoneid {$_ZONE->id()} <br>";
}

migrateAllData();
