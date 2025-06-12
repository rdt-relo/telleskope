<?php
include __DIR__.'/../head.php';

$check2 = date('Ymd');
if ($check2 > '20241101') {
    echo "<p>Migration expired</p>";
    echo "<p>Exiting</p>";
    exit();
}

// PREPARE
//$_SUPER_ADMIN->super_update("update event_speakers set custom_fields='[]' where custom_fields is null");

// Get all speaker fields
$speaker_fields = $_SUPER_ADMIN->super_get("SELECT * from event_speaker_fields order by companyid,zoneid,speaker_fieldtype");
$migrated_fields = array();

// Iterate through each speaker field and migrate it.
foreach($speaker_fields as $speaker_field){
    //echo "<p>Migrating ..." . json_encode($speaker_field) . "</p>";

    $speaker_field_key = "{$speaker_field['companyid']}_{$speaker_field['zoneid']}_{$speaker_field['speaker_fieldtype']}";

    // Note speaker fields have field name and option values
    // So lets first migrate the speaker field as custom field.
    if (empty($migrated_fields[$speaker_field_key])) {
        // Insert a row in event_custom_fields;
        $speaker_field_type = $speaker_field['speaker_fieldtype'];
        $field_name = ['speaker_type'=>'Speaker Type','speech_type'=>'Speech Type','audience_type'=>'Audience Type'][$speaker_field_type];
        $field_sorting_order = ['speaker_type'=>1,'speech_type'=>2,'audience_type'=>3][$speaker_field_type];

        // First check if the field is already there
        $existing_field_vals = $_SUPER_ADMIN->super_get("SELECT * FROM event_custom_fields WHERE companyid={$speaker_field['companyid']} AND zoneid={$speaker_field['zoneid']} AND custom_field_name='{$field_name}' AND topictype='EVTSPK'");
        if (!empty($existing_field_vals)) {
            // Field has already been migrated, skip
            echo "<p>Skipping <b>{$field_name} : {$speaker_field['speaker_fieldlabel']}</b> for companyid={$speaker_field['companyid']} and zoneid={$speaker_field['zoneid']}, it has already been migrated</p>";
            continue;
        } else {
            $migrated_fields[$speaker_field_key] = $_SUPER_ADMIN->super_insert("INSERT INTO event_custom_fields (
                                                     companyid, 
                                                     zoneid, 
                                                     custom_field_name, 
                                                     custom_fields_type, 
                                                     custom_field_note, 
                                                     custom_fields_options, 
                                                     sorting_order, 
                                                     is_required, 
                                                     visible_if, 
                                                     createdon, 
                                                     modifiedon, 
                                                     isactive, 
                                                     topictype
                                            ) 
                                            VALUES (
                                                     '{$speaker_field['companyid']}',
                                                     '{$speaker_field['zoneid']}',
                                                     '{$field_name}',
                                                      '1',
                                                      '',
                                                      NULL,
                                                      '{$field_sorting_order}',
                                                      '1',
                                                      '[]',
                                                      '{$speaker_field['createdon']}',
                                                      '{$speaker_field['createdon']}',
                                                      '1',
                                                      'EVTSPK'
                                            )
                                 ");

            echo "<p style='padding-left: 5px;'>Added field <b>{$field_name}</b> => <b>{$migrated_fields[$speaker_field_key]}</b> for companyid={$speaker_field['companyid']} and zoneid={$speaker_field['zoneid']}</p>";
        }
    }

    $custom_field_id = $migrated_fields[$speaker_field_key];
    $speaker_fieldid_old = $speaker_field['speaker_fieldid'];
    $custom_field_option = $speaker_field['speaker_fieldlabel'];
    $custom_field_option_id = $_SUPER_ADMIN->super_insert("INSERT INTO event_custom_field_options (
                                        custom_field_id, 
                                        custom_field_option, 
                                        custom_field_option_note,
                                        createdon,
                                        isactive
                                        )
                                        VALUES (
                                                {$custom_field_id},
                                                '{$custom_field_option}',
                                                '',
                                                '{$speaker_field['createdon']}',
                                                '{$speaker_field['isactive']}'
                                        )");

    echo "<p style='padding-left: 10px;'>- option <b>{$custom_field_option}</b> => <b>{$custom_field_option_id}</b></p>";
    // Migrate all speakers with old $speaker_fieldid_old for corresponding column to new custom values

    //$_SUPER_ADMIN->super_update("UPDATE event_speakers SET custom_fields=JSON_ARRAY_APPEND(custom_fields,'$', JSON_OBJECT('custom_field_id', $custom_field_id,'value', JSON_ARRAY($custom_field_option_id))) WHERE companyid='{$speaker_field['companyid']}' AND zoneid='{$speaker_field['zoneid']}' AND `{$speaker_field_type}`='{$speaker_fieldid_old}'");
    $migrate_speakers = $_SUPER_ADMIN->super_get("select * from event_speakers WHERE companyid='{$speaker_field['companyid']}' AND zoneid='{$speaker_field['zoneid']}' AND `{$speaker_field_type}`='{$speaker_fieldid_old}'");
    foreach ($migrate_speakers as $migrate_speaker) {
        $custom_fields = json_decode($migrate_speaker['custom_fields'] ?? '[]', true);
        $updated = false;

        // If the field is already set update it
        foreach ($custom_fields as $field) {
            if ($field['custom_field_id'] == $custom_field_id) {
                $field['value'] = array($custom_field_option_id);
                $updated = true;
                break;
            }
        }
        // Otherwise insert a new field
        if (!$updated) {
            $custom_fields[] = [
                'custom_field_id' => $custom_field_id,
                'value' => [$custom_field_option_id]
            ];
        }

        // Now update the database column
        $custom_fields = json_encode($custom_fields);
        $_SUPER_ADMIN->super_update("UPDATE event_speakers SET custom_fields='{$custom_fields}' WHERE speakerid={$migrate_speaker['speakerid']}");
    }
}



