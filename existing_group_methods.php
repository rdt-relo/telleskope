<?php
/**
 * Using existing Group and Company class methods to get and filter groups
 * 
 * Much simpler approach using the built-in functions!
 */

// ====================================================================
// SOLUTION 1: Using Company class method (Simplest)
// ====================================================================

/**
 * Get all active groups from current zone using Company class
 * Then filter by group list if provided
 */
function getFilteredGroupsUsingCompanyClass($group_list = '') {
    global $_COMPANY;
    
    // Use existing Company method to get all active groups from current zone
    $all_groups = $_COMPANY->getAllActiveGroups();
    
    // If no filter provided, convert Group objects to array format and return
    if (empty($group_list)) {
        $result = array();
        foreach ($all_groups as $group) {
            $result[] = array(
                'groupid' => $group->id(),
                'groupname' => $group->val('groupname'),
                'groupname_short' => $group->val('groupname_short'),
                'overlaycolor' => $group->val('overlaycolor'),
                'regionid' => $group->val('regionid'),
                'categoryid' => $group->val('categoryid'),
                'group_type' => $group->val('group_type')
            );
        }
        return $result;
    }
    
    // Parse and filter by group list
    $group_ids_array = array_map('intval', explode(',', trim($group_list)));
    $group_ids_array = array_filter($group_ids_array); // Remove empty values
    
    if (empty($group_ids_array)) {
        return array();
    }
    
    // Filter groups
    $filtered_groups = array();
    foreach ($all_groups as $group) {
        if (in_array((int)$group->id(), $group_ids_array)) {
            $filtered_groups[] = array(
                'groupid' => $group->id(),
                'groupname' => $group->val('groupname'),
                'groupname_short' => $group->val('groupname_short'),
                'overlaycolor' => $group->val('overlaycolor'),
                'regionid' => $group->val('regionid'),
                'categoryid' => $group->val('categoryid'),
                'group_type' => $group->val('group_type')
            );
        }
    }
    
    return $filtered_groups;
}

// ====================================================================
// SOLUTION 2: Using Group::GetAllGroupsByZones (More flexible)
// ====================================================================

/**
 * Get groups using Group::GetAllGroupsByZones method
 * This returns array data directly (no Group objects)
 */
function getFilteredGroupsUsingGroupClass($group_list = '') {
    global $_ZONE;
    
    // Use existing Group method to get all groups from current zone
    // This returns array data directly (groupid, groupname, etc.)
    $all_groups = Group::GetAllGroupsByZones([$_ZONE->id()]);
    
    // If no filter provided, return all groups
    if (empty($group_list)) {
        return $all_groups;
    }
    
    // Parse and filter by group list
    $group_ids_array = array_map('intval', explode(',', trim($group_list)));
    $group_ids_array = array_filter($group_ids_array); // Remove empty values
    
    if (empty($group_ids_array)) {
        return array();
    }
    
    // Filter groups
    return array_filter($all_groups, function($group) use ($group_ids_array) {
        return in_array((int)$group['groupid'], $group_ids_array);
    });
}

// ====================================================================
// SOLUTION 3: Using Group::GetGroups (When you have group IDs)
// ====================================================================

/**
 * If you already have the group IDs from $group_list, 
 * use Group::GetGroups to get the Group objects directly
 */
function getGroupObjectsFromGroupList($group_list) {
    if (empty($group_list)) {
        return array();
    }
    
    // Parse group IDs
    $group_ids_array = array_map('intval', explode(',', trim($group_list)));
    $group_ids_array = array_filter($group_ids_array); // Remove empty values
    
    if (empty($group_ids_array)) {
        return array();
    }
    
    // Use existing Group method to get Group objects
    return Group::GetGroups($group_ids_array, true); // true = active only
}

// ====================================================================
// USAGE EXAMPLES
// ====================================================================

/*

// Example 1: Using Company class (Recommended for most cases)
$group_list = "1,3,5,7"; // Usually from: implode(',', $meta['Filters']['groupids'])
$filtered_groups = getFilteredGroupsUsingCompanyClass($group_list);
echo "Found " . count($filtered_groups) . " groups using Company class\n";

// Example 2: Using Group::GetAllGroupsByZones
$filtered_groups = getFilteredGroupsUsingGroupClass($group_list);
echo "Found " . count($filtered_groups) . " groups using Group class\n";

// Example 3: Get Group objects directly (useful when you need Group methods)
$group_objects = getGroupObjectsFromGroupList($group_list);
foreach ($group_objects as $group) {
    echo "Group: " . $group->val('groupname') . "\n";
    // You can call any Group object methods here
    echo "Members: " . $group->getMemberCount() . "\n";
}

// Example 4: Typical report usage
if (!empty($meta['Filters']) && !empty($meta['Filters']['groupids'])) {
    $group_list = implode(',', $meta['Filters']['groupids']);
    $groups = getFilteredGroupsUsingCompanyClass($group_list);
} else {
    $groups = getFilteredGroupsUsingCompanyClass(''); // Get all groups
}

// Example 5: Get all groups from zone (no filtering)
$all_groups = $_COMPANY->getAllActiveGroups();
// or
$all_groups = Group::GetAllGroupsByZones([$_ZONE->id()]);

*/

// ====================================================================
// RECOMMENDED APPROACH: One-liner solutions
// ====================================================================

/*

// For getting all active groups from current zone:
$all_groups = $_COMPANY->getAllActiveGroups();

// For getting groups by zone ID(s) as arrays:
$groups_array = Group::GetAllGroupsByZones([$_ZONE->id()]);

// For getting specific groups by IDs:
$group_ids = [1, 3, 5, 7]; // From your filter
$group_objects = Group::GetGroups($group_ids, true);

// For filtering existing results:
if (!empty($group_list)) {
    $group_ids = array_map('intval', explode(',', $group_list));
    $filtered_groups = Group::GetGroups($group_ids, true);
}

*/

?>
