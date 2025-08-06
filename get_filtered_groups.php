<?php
/**
 * Get all groups of current zone and filter out groups not in $group_list
 * 
 * This code demonstrates how to:
 * 1. Get all active groups from the current zone
 * 2. Filter them based on a comma-separated string of group IDs
 * 3. Return the filtered groups
 */

// Assuming you have the global variables available in your context
global $_COMPANY, $_ZONE, $db;

// Example $group_list - this would typically come from reports like:
// $group_list = implode(',', $meta['Filters']['groupids']);
$group_list = "1,3,5,7,9"; // Example comma-separated string of group IDs

/**
 * Method 1: Get all groups from current zone and filter them
 */
function getFilteredGroupsFromZone($group_list = '') {
    global $_COMPANY, $_ZONE, $db;
    
    // First, get all active groups from the current zone
    $all_groups = $db->ro_get("
        SELECT 
            `groupid`, 
            `groupname`, 
            `groupname_short`, 
            `overlaycolor`, 
            `regionid`, 
            `categoryid`,
            `group_type`
        FROM `groups` 
        WHERE `companyid` = {$_COMPANY->id()} 
          AND `zoneid` = {$_ZONE->id()} 
          AND `isactive` = 1
        ORDER BY `groupname` ASC
    ");
    
    // If no group_list filter is provided, return all groups
    if (empty($group_list)) {
        return $all_groups;
    }
    
    // Convert comma-separated string to array of integers
    $group_ids_array = array_map('intval', explode(',', $group_list));
    $group_ids_array = array_filter($group_ids_array); // Remove any empty/zero values
    
    // Filter groups to only include those in the group_list
    $filtered_groups = array_filter($all_groups, function($group) use ($group_ids_array) {
        return in_array((int)$group['groupid'], $group_ids_array);
    });
    
    return array_values($filtered_groups); // Re-index array
}

/**
 * Method 2: Use SQL IN clause for more efficient filtering (recommended for large datasets)
 */
function getFilteredGroupsFromZoneOptimized($group_list = '') {
    global $_COMPANY, $_ZONE, $db;
    
    $group_filter = '';
    
    // If group_list is provided, add SQL filter
    if (!empty($group_list)) {
        // Sanitize the group_list to ensure it contains only integers
        $group_ids_array = array_map('intval', explode(',', $group_list));
        $group_ids_array = array_filter($group_ids_array); // Remove any empty/zero values
        
        if (!empty($group_ids_array)) {
            $sanitized_group_list = implode(',', $group_ids_array);
            $group_filter = " AND `groupid` IN ({$sanitized_group_list})";
        } else {
            // If group_list is invalid, return empty array
            return array();
        }
    }
    
    $filtered_groups = $db->ro_get("
        SELECT 
            `groupid`, 
            `groupname`, 
            `groupname_short`, 
            `overlaycolor`, 
            `regionid`, 
            `categoryid`,
            `group_type`
        FROM `groups` 
        WHERE `companyid` = {$_COMPANY->id()} 
          AND `zoneid` = {$_ZONE->id()} 
          AND `isactive` = 1
          {$group_filter}
        ORDER BY `groupname` ASC
    ");
    
    return $filtered_groups;
}

/**
 * Method 3: Alternative approach using Group class methods (if available)
 */
function getFilteredGroupsUsingGroupClass($group_list = '') {
    global $_COMPANY, $_ZONE;
    
    // Get all active groups from current zone using Group class
    $all_group_objects = Group::GetAllGroupsByCompanyid($_COMPANY->id(), $_ZONE->id(), true);
    
    // If no group_list filter is provided, convert objects to arrays and return
    if (empty($group_list)) {
        $all_groups_array = array();
        foreach ($all_group_objects as $group_obj) {
            $all_groups_array[] = array(
                'groupid' => $group_obj->id(),
                'groupname' => $group_obj->val('groupname'),
                'groupname_short' => $group_obj->val('groupname_short'),
                'overlaycolor' => $group_obj->val('overlaycolor'),
                'regionid' => $group_obj->val('regionid'),
                'categoryid' => $group_obj->val('categoryid'),
                'group_type' => $group_obj->val('group_type')
            );
        }
        return $all_groups_array;
    }
    
    // Convert comma-separated string to array of integers
    $group_ids_array = array_map('intval', explode(',', $group_list));
    $group_ids_array = array_filter($group_ids_array); // Remove any empty/zero values
    
    // Filter and convert to array format
    $filtered_groups = array();
    foreach ($all_group_objects as $group_obj) {
        if (in_array((int)$group_obj->id(), $group_ids_array)) {
            $filtered_groups[] = array(
                'groupid' => $group_obj->id(),
                'groupname' => $group_obj->val('groupname'),
                'groupname_short' => $group_obj->val('groupname_short'),
                'overlaycolor' => $group_obj->val('overlaycolor'),
                'regionid' => $group_obj->val('regionid'),
                'categoryid' => $group_obj->val('categoryid'),
                'group_type' => $group_obj->val('group_type')
            );
        }
    }
    
    return $filtered_groups;
}

// Example usage:
try {
    // Method 1: Basic filtering
    echo "=== Method 1: Basic Filtering ===\n";
    $filtered_groups_1 = getFilteredGroupsFromZone($group_list);
    echo "Found " . count($filtered_groups_1) . " groups matching the filter\n";
    foreach ($filtered_groups_1 as $group) {
        echo "- Group ID: {$group['groupid']}, Name: {$group['groupname']}\n";
    }
    echo "\n";
    
    // Method 2: Optimized SQL filtering (recommended)
    echo "=== Method 2: Optimized SQL Filtering (Recommended) ===\n";
    $filtered_groups_2 = getFilteredGroupsFromZoneOptimized($group_list);
    echo "Found " . count($filtered_groups_2) . " groups matching the filter\n";
    foreach ($filtered_groups_2 as $group) {
        echo "- Group ID: {$group['groupid']}, Name: {$group['groupname']}\n";
    }
    echo "\n";
    
    // Method 3: Using Group class
    echo "=== Method 3: Using Group Class ===\n";
    $filtered_groups_3 = getFilteredGroupsUsingGroupClass($group_list);
    echo "Found " . count($filtered_groups_3) . " groups matching the filter\n";
    foreach ($filtered_groups_3 as $group) {
        echo "- Group ID: {$group['groupid']}, Name: {$group['groupname']}\n";
    }
    echo "\n";
    
    // Example with no filter (get all groups)
    echo "=== All Groups (No Filter) ===\n";
    $all_groups = getFilteredGroupsFromZoneOptimized('');
    echo "Found " . count($all_groups) . " total groups in the zone\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

/**
 * Real-world usage example for reports:
 * This is how you would typically use this in a report context
 */
function exampleReportUsage($meta) {
    // Example: In a report where $meta['Filters']['groupids'] contains the selected group IDs
    $group_list = '';
    if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
        $group_list = implode(',', $meta['Filters']['groupids']);
    }
    
    // Get filtered groups
    $filtered_groups = getFilteredGroupsFromZoneOptimized($group_list);
    
    // Create group filter for SQL queries
    $group_filter = '';
    if (!empty($filtered_groups)) {
        $group_ids = array_column($filtered_groups, 'groupid');
        $group_ids_csv = implode(',', $group_ids);
        $group_filter = " AND groupid IN ({$group_ids_csv})";
    } else if (!empty($group_list)) {
        // If group_list was provided but no groups found, don't match anything
        $group_filter = " AND FALSE";
    }
    // If group_list is empty, no filter is applied (shows all groups)
    
    return array(
        'groups' => $filtered_groups,
        'sql_filter' => $group_filter
    );
}

// Example of real-world usage
echo "=== Real-world Report Usage Example ===\n";
$sample_meta = array(
    'Filters' => array(
        'groupids' => array(1, 3, 5) // Example: user selected these group IDs
    )
);

$result = exampleReportUsage($sample_meta);
echo "SQL Filter: " . $result['sql_filter'] . "\n";
echo "Number of groups: " . count($result['groups']) . "\n";

?>
