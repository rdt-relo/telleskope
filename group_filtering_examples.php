<?php
/**
 * Get all groups of current zone and filter out groups not in $group_list
 * 
 * Usage Examples for filtering groups based on a comma-separated string of group IDs
 * This is commonly used in reports when doing: $group_list = implode(',', $meta['Filters']['groupids']);
 */

/**
 * Method 1: Get filtered groups using direct SQL (Most efficient)
 * This is the recommended approach for most use cases
 */
function getFilteredGroupsFromCurrentZone($group_list = '') {
    global $_COMPANY, $_ZONE, $db;
    
    $group_filter = '';
    
    // If group_list is provided, add SQL filter
    if (!empty($group_list)) {
        // Sanitize the group_list to ensure it contains only integers
        $group_ids_array = array_map('intval', explode(',', trim($group_list)));
        $group_ids_array = array_filter($group_ids_array); // Remove any empty/zero values
        
        if (!empty($group_ids_array)) {
            $sanitized_group_list = implode(',', $group_ids_array);
            $group_filter = " AND `groupid` IN ({$sanitized_group_list})";
        } else {
            // If group_list is invalid, return empty array
            return array();
        }
    }
    
    // Get groups from current zone with optional filtering
    $sql = "
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
    ";
    
    return $db->ro_get($sql);
}

/**
 * Method 2: Get all groups first, then filter in PHP
 * Use this when you need to do additional processing on all groups
 */
function getAllGroupsThenFilter($group_list = '') {
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
    $group_ids_array = array_map('intval', explode(',', trim($group_list)));
    $group_ids_array = array_filter($group_ids_array); // Remove any empty/zero values
    
    if (empty($group_ids_array)) {
        return array();
    }
    
    // Filter groups to only include those in the group_list
    $filtered_groups = array_filter($all_groups, function($group) use ($group_ids_array) {
        return in_array((int)$group['groupid'], $group_ids_array);
    });
    
    return array_values($filtered_groups); // Re-index array
}

/**
 * Method 3: Using existing Group class methods
 * Use this when you want to leverage existing Group class functionality
 */
function getFilteredGroupsUsingGroupClass($group_list = '') {
    global $_COMPANY, $_ZONE;
    
    // Get all active groups from current zone using Group class
    $all_group_objects = Group::GetAllGroupsByCompanyid($_COMPANY->id(), $_ZONE->id(), true);
    
    // Convert comma-separated string to array of integers
    $group_ids_array = array();
    if (!empty($group_list)) {
        $group_ids_array = array_map('intval', explode(',', trim($group_list)));
        $group_ids_array = array_filter($group_ids_array); // Remove any empty/zero values
    }
    
    // Filter and convert to array format
    $filtered_groups = array();
    foreach ($all_group_objects as $group_obj) {
        // If no filter provided, include all groups
        // If filter provided, only include groups in the list
        if (empty($group_ids_array) || in_array((int)$group_obj->id(), $group_ids_array)) {
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

/**
 * Helper function to create SQL filter string for use in other queries
 * This is commonly used in reports
 */
function createGroupFilterForSQL($group_list = '') {
    global $_COMPANY, $_ZONE;
    
    if (empty($group_list)) {
        // No filter - will match all groups in the zone
        return '';
    }
    
    // Sanitize the group_list
    $group_ids_array = array_map('intval', explode(',', trim($group_list)));
    $group_ids_array = array_filter($group_ids_array); // Remove any empty/zero values
    
    if (empty($group_ids_array)) {
        // Invalid group list - return false condition
        return ' AND FALSE';
    }
    
    $sanitized_group_list = implode(',', $group_ids_array);
    return " AND groupid IN ({$sanitized_group_list})";
}

/**
 * Complete example function as might be used in a report
 * Mimics the pattern used in ReportZoneStatistics.php and similar files
 */
function exampleReportUsage($meta) {
    // Extract group list from report metadata (typical pattern)
    $group_list = '';
    if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
        $group_list = implode(',', $meta['Filters']['groupids']);
    }
    
    // Get filtered groups
    $filtered_groups = getFilteredGroupsFromCurrentZone($group_list);
    
    // Create SQL filter for use in other queries
    $group_filter = createGroupFilterForSQL($group_list);
    
    return array(
        'groups' => $filtered_groups,
        'sql_filter' => $group_filter,
        'group_count' => count($filtered_groups)
    );
}

// ========================================
// USAGE EXAMPLES
// ========================================

/*
// Example 1: Basic usage with group list
$group_list = "1,3,5,7,9"; // This would typically come from: implode(',', $meta['Filters']['groupids'])
$filtered_groups = getFilteredGroupsFromCurrentZone($group_list);

echo "Found " . count($filtered_groups) . " groups\n";
foreach ($filtered_groups as $group) {
    echo "Group: {$group['groupname']} (ID: {$group['groupid']})\n";
}

// Example 2: Get all groups (no filter)
$all_groups = getFilteredGroupsFromCurrentZone('');
echo "Total groups in zone: " . count($all_groups) . "\n";

// Example 3: Use in a SQL query (common in reports)
$group_list = "1,3,5"; 
$group_filter = createGroupFilterForSQL($group_list);

// Then use in your SQL:
$sql = "SELECT * FROM events WHERE companyid = {$_COMPANY->id()} AND zoneid = {$_ZONE->id()} {$group_filter}";

// Example 4: Report-style usage
$meta = array(
    'Filters' => array(
        'groupids' => array(1, 3, 5, 7) // Selected group IDs from report form
    )
);

$result = exampleReportUsage($meta);
echo "Groups found: " . $result['group_count'] . "\n";
echo "SQL Filter: " . $result['sql_filter'] . "\n";

// Example 5: Handle empty or invalid group lists
$empty_result = getFilteredGroupsFromCurrentZone('');     // Returns all groups
$invalid_result = getFilteredGroupsFromCurrentZone('0,'); // Returns empty array
$mixed_result = getFilteredGroupsFromCurrentZone('1,,3,0,5'); // Returns groups 1, 3, 5
*/

?>
