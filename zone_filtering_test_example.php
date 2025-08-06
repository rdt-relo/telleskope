<?php
/**
 * Example to demonstrate how the ReportSchedules zone filtering works
 */

// Example: Current zone ($_ZONE->id()) is zone 3
// Zone 3 has groups with IDs: [67, 89, 101, 102]

// Sample data from database:
$sample_records = [
    [
        'schedule_id' => 1,
        'groupids' => '',  // Global schedule
        'schedule_name' => 'Global Support Schedule'
    ],
    [
        'schedule_id' => 2,
        'groupids' => '1,4',  // Zone 1 groups only
        'schedule_name' => 'Zone 1 Schedule'
    ],
    [
        'schedule_id' => 3,
        'groupids' => '67,89',  // Zone 3 groups only
        'schedule_name' => 'Zone 3 Schedule A'
    ],
    [
        'schedule_id' => 4,
        'groupids' => '1,4,67,89',  // Mixed: Zone 1 (1,4) + Zone 3 (67,89)
        'schedule_name' => 'Mixed Zone Schedule'
    ],
    [
        'schedule_id' => 5,
        'groupids' => '101',  // Zone 3 group only
        'schedule_name' => 'Zone 3 Schedule B'
    ],
    [
        'schedule_id' => 6,
        'groupids' => '50,51',  // Zone 2 groups only
        'schedule_name' => 'Zone 2 Schedule'
    ]
];

// Current zone group IDs (Zone 3)
$zone_group_ids = [67, 89, 101, 102];

// Group names mapping (for demonstration)
$group_names = [
    1 => 'Finance Team',
    4 => 'HR Team',
    67 => 'Engineering Team',
    89 => 'Marketing Team', 
    101 => 'Sales Team',
    102 => 'Support Team',
    50 => 'Operations Team',
    51 => 'Legal Team'
];

echo "=== Zone Filtering Example ===\n";
echo "Current Zone Groups: " . implode(', ', $zone_group_ids) . "\n\n";

foreach ($sample_records as $record) {
    echo "Schedule ID: {$record['schedule_id']}\n";
    echo "Schedule Name: {$record['schedule_name']}\n";
    echo "Original groupids: '{$record['groupids']}'\n";
    
    // Apply filtering logic (same as in ReportSchedules.php)
    $include_record = false;
    
    // Always include global data
    if (empty($record['groupids']) || $record['groupids'] === '0') {
        $include_record = true;
        echo "✅ INCLUDED (Global schedule)\n";
        echo "restricted_groups field: ''\n";
    } else {
        // Check if any groups belong to current zone
        $record_group_ids = array_map('trim', explode(',', $record['groupids']));
        $record_group_ids = array_filter($record_group_ids);
        
        if (!empty(array_intersect($record_group_ids, array_map('strval', $zone_group_ids)))) {
            $include_record = true;
            
            // Get zone group names only
            $zone_group_ids_str = array_map('strval', $zone_group_ids);
            $valid_group_ids = array_intersect($record_group_ids, $zone_group_ids_str);
            
            $zone_group_names = array();
            foreach ($valid_group_ids as $group_id) {
                if (isset($group_names[$group_id])) {
                    $zone_group_names[] = $group_names[$group_id];
                }
            }
            
            $restricted_groups_value = implode(', ', $zone_group_names);
            
            echo "✅ INCLUDED (Has zone groups: " . implode(', ', $valid_group_ids) . ")\n";
            echo "restricted_groups field: '{$restricted_groups_value}'\n";
        } else {
            echo "❌ EXCLUDED (No zone groups)\n";
            echo "restricted_groups field: N/A (record excluded)\n";
        }
    }
    
    echo "\n";
}

echo "=== Summary ===\n";
echo "✅ INCLUDED: Records 1, 3, 4, 5 (Global + Zone 3 schedules)\n";
echo "❌ EXCLUDED: Records 2, 6 (Other zone schedules only)\n\n";

echo "=== restricted_groups field values in CSV ===\n";
echo "Record 1: '' (Global)\n";
echo "Record 3: 'Engineering Team, Marketing Team' (Zone 3 groups: 67, 89)\n";
echo "Record 4: 'Engineering Team, Marketing Team' (Zone 3 groups only: 67, 89)\n";
echo "Record 5: 'Sales Team' (Zone 3 group: 101)\n";

?>
