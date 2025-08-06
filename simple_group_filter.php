<?php
/**
 * Simple code to get all groups from current zone and filter based on $group_list
 * 
 * This code assumes you're working within the existing application context where
 * $_COMPANY, $_ZONE, and $db are already available.
 */

// ====================================================================
// MAIN FUNCTION - Copy this into your code
// ====================================================================

/**
 * Get groups from current zone, optionally filtered by group list
 * 
 * @param string $group_list Comma-separated string of group IDs (e.g., "1,3,5,7")
 *                          Usually comes from: implode(',', $meta['Filters']['groupids'])
 * @return array Array of group records
 */
function getFilteredGroupsFromZone($group_list = '') {
    global $_COMPANY, $_ZONE, $db;
    
    $group_filter = '';
    
    // If group_list is provided, create SQL filter
    if (!empty($group_list)) {
        // Clean and validate the group IDs
        $group_ids = array_map('intval', explode(',', trim($group_list)));
        $group_ids = array_filter($group_ids); // Remove empty/zero values
        
        if (!empty($group_ids)) {
            $clean_group_list = implode(',', $group_ids);
            $group_filter = " AND `groupid` IN ({$clean_group_list})";
        } else {
            // Invalid group list - return empty array
            return array();
        }
    }
    
    // Query to get groups from current zone
    return $db->ro_get("
        SELECT 
            `groupid`, 
            `groupname`, 
            `groupname_short`, 
            `overlaycolor`, 
            `regionid`, 
            `categoryid`
        FROM `groups` 
        WHERE `companyid` = {$_COMPANY->id()} 
          AND `zoneid` = {$_ZONE->id()} 
          AND `isactive` = 1
          {$group_filter}
        ORDER BY `groupname` ASC
    ");
}

// ====================================================================
// USAGE EXAMPLES
// ====================================================================

/*

// Example 1: Get all groups from current zone
$all_groups = getFilteredGroupsFromZone();
echo "Total groups in zone: " . count($all_groups);

// Example 2: Filter by specific group IDs
$group_list = "1,3,5,7"; // Usually from: implode(',', $meta['Filters']['groupids'])
$filtered_groups = getFilteredGroupsFromZone($group_list);
echo "Filtered groups: " . count($filtered_groups);

// Example 3: Typical report usage
if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
    $group_list = implode(',', $meta['Filters']['groupids']);
    $groups = getFilteredGroupsFromZone($group_list);
} else {
    $groups = getFilteredGroupsFromZone(); // Get all groups
}

// Example 4: Use results in another query
$group_list = "1,3,5";
$groups = getFilteredGroupsFromZone($group_list);
$group_ids = array_column($groups, 'groupid');
$group_ids_csv = implode(',', $group_ids);

// Now use in another SQL query:
$events = $db->ro_get("
    SELECT * FROM events 
    WHERE companyid = {$_COMPANY->id()} 
      AND zoneid = {$_ZONE->id()} 
      AND groupid IN ({$group_ids_csv})
");

*/

// ====================================================================
// ALTERNATIVE: Direct SQL approach for reports
// ====================================================================

/*
// If you prefer to handle this directly in your SQL queries:

$group_filter = '';
if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
    $group_list = implode(',', $meta['Filters']['groupids']);
    $group_filter = " AND groupid IN ({$group_list})";
}

// Then use in your main query:
$sql = "
    SELECT * FROM your_table 
    WHERE companyid = {$_COMPANY->id()} 
      AND zoneid = {$_ZONE->id()} 
      {$group_filter}
";
$results = $db->ro_get($sql);
*/

?>
