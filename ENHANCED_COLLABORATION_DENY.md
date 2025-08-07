# Enhanced Collaboration Deny Feature - ViewHelper Integration

## Overview

The collaboration deny feature has been enhanced to use a comprehensive validation approach through ViewHelper.php, addressing complex scenarios that can arise when denying collaboration requests.

## Problem Solved

### Original Issue
The previous approach only did basic validation (minimum 2 groups, zone requirements) but didn't handle complex scenarios such as:
- Event being converted from collaborative to single-group when too many denials occur
- Proper handling of chapter-group relationships during denial
- Comprehensive validation using existing collaboration logic from ViewHelper.php

### Example Scenario
- Event created in Zone A, Group 7
- Collaboration request sent to Zone A, Group 19  
- Group 19 approver denies the collaboration
- Result: Event now only has Group 7 but is still marked as "collaborative"
- **Solution**: Event should be converted to single-group event automatically

## New Architecture

### 1. ViewHelper Function: `ValidateAndProcessCollaborationDenial`

**Location**: `ViewHelper.php`

**Purpose**: Comprehensive validation and processing of collaboration denials

**Parameters**:
- `int $eventid` - The event ID
- `array $deniedGroupIds` - Array of group IDs being denied  
- `array $deniedChapterIds` - Array of chapter IDs being denied

**Returns**: 
```php
[
    bool $success,           // Whether denial is allowed
    string $message,         // Success/error message  
    bool $shouldUpdate,      // Whether database should be updated
    array $newCollaboratingGroupIds,     // Final approved group IDs
    array $newPendingGroupIds,           // Final pending group IDs  
    array $newApprovedChapterIds,        // Final approved chapter IDs
    array $newPendingChapterIds          // Final pending chapter IDs
]
```

**Key Features**:
- **Smart Conversion**: Converts collaborative events to single-group when appropriate
- **Chapter-Group Logic**: Handles complex chapter-group relationships
- **Zone Validation**: Ensures zone requirements are maintained
- **Existing Logic**: Uses established validation patterns from ViewHelper.php

### 2. Enhanced AJAX Endpoints

#### Group Denial (`denyEventGroupCollaboration`)
```php
// Old approach: Manual validation + direct database update
// New approach: 
[$success, $message, $shouldUpdate, $newCollaboratingGroupIds, $newPendingGroupIds, $newApprovedChapterIds, $newPendingChapterIds] = 
    ViewHelper::ValidateAndProcessCollaborationDenial($eventid, [$groupid], []);

if ($success && $shouldUpdate) {
    $event->updateCollaboratingGroupids($newCollaboratingGroupIds, $newPendingGroupIds, $newApprovedChapterIds, $newPendingChapterIds);
}
```

#### Chapter Denial (`denyEventChapterCollaboration`)  
```php
// Similar pattern but for chapter denial
[$success, $message, $shouldUpdate, ...] = 
    ViewHelper::ValidateAndProcessCollaborationDenial($eventid, [], [$chapterid]);
```

## Validation Logic

### 1. Current State Analysis
- Extracts current collaboration state from event
- Identifies which groups/chapters would be removed by denial

### 2. Impact Assessment  
- Calculates final collaboration state after denial
- Handles cascade effects (denied groups → remove their chapters)
- Identifies groups that would lose all chapters

### 3. Validation Rules

#### Minimum Collaboration Requirements
```php
if ($totalRemainingGroups < 2) {
    // Check if event can be converted to single-group
    if ($hostGroupId > 0 && valid_host_group_conditions) {
        // Convert to single-group event
        return [true, "Event converted to single-group", ...];
    } else {
        // Deny the request
        return [false, "Insufficient groups for collaboration", ...];
    }
}
```

#### Zone Requirements
```php
if (!Group::IsGroupInCurrentZone($allRemainingGroups, $eventZoneId)) {
    return [false, "No groups from current zone would remain", ...];
}
```

### 4. Smart Conversion Logic
When denials would leave fewer than 2 groups:
- **Check Host Group**: If event has valid host group (groupid > 0)
- **Convert to Single-Group**: Remove collaboration state, keep only host group
- **Update Message**: Inform user about conversion
- **Trigger Reload**: Frontend reloads to reflect new event structure

## Frontend Enhancements

### JavaScript Updates
```javascript
// Detect event structure changes
if (jsonData.message.includes('converted to a single-group event')) {
    setTimeout(function() { location.reload(); }, 2000);
}
```

**Benefits**:
- **User Awareness**: Users see when event structure changes
- **UI Consistency**: Page reload ensures UI reflects new state
- **Smooth Transition**: 2-second delay allows user to read the message

## Integration with Existing Logic

### ViewHelper.php Methods Used
- **Existing Validation Patterns**: Follows same logic as `ValidateTopicCollaboration`
- **Group-Chapter Logic**: Uses `Group::GetGroupChaptersFromChapterIdsCSV`
- **Zone Validation**: Leverages `Group::IsGroupInCurrentZone`
- **Error Messaging**: Consistent with existing patterns

### Database Integration
- **Same Update Method**: Uses existing `updateCollaboratingGroupids`
- **No New Fields**: Works with current database schema
- **Atomic Updates**: Single database operation for all changes

## Scenarios Handled

### 1. Basic Denial (Valid)
- **Before**: Groups A, B, C collaborating
- **Action**: Deny Group B
- **Result**: Groups A, C remain collaborating
- **Message**: "Collaboration request denied successfully"

### 2. Conversion to Single-Group
- **Before**: Groups A, B collaborating (A is host)
- **Action**: Deny Group B  
- **Result**: Only Group A remains, event converted to single-group
- **Message**: "Event has been converted to a single-group event"

### 3. Zone Violation Prevention
- **Before**: Group A (Zone 1), Group B (Zone 2) collaborating
- **Action**: Try to deny Group A (only Zone 1 group)
- **Result**: Denial blocked
- **Message**: "At least one group from current zone must remain"

### 4. Chapter Impact
- **Before**: Group A (Chapters 1,2), Group B collaborating
- **Action**: Deny Chapter 1 (Group A still has Chapter 2)
- **Result**: Chapter 1 removed, Group A remains with Chapter 2
- **Message**: "Chapter collaboration request denied successfully"

### 5. Chapter Cascade
- **Before**: Group A (Chapter 1 only), Group B collaborating  
- **Action**: Deny Chapter 1 (Group A would lose all chapters)
- **Result**: Depends on minimum group validation
- **Message**: Either denial or conversion based on host group

## Files Modified

### 1. ViewHelper.php
- **Added**: `ValidateAndProcessCollaborationDenial()` function
- **Integration**: Uses existing validation helper functions
- **Logic**: Comprehensive scenario handling

### 2. ajax_events.php  
- **Modified**: `denyEventGroupCollaboration` endpoint
- **Modified**: `denyEventChapterCollaboration` endpoint
- **Simplified**: Removed manual validation logic
- **Enhanced**: Now uses ViewHelper validation

### 3. requested_group_approval_for_event_collaboration.template.php
- **Enhanced**: JavaScript handling for event conversion
- **Added**: Page reload on significant structure changes
- **Improved**: User experience for complex scenarios

## Benefits

### 1. Robustness
- **Comprehensive**: Handles all collaboration scenarios
- **Consistent**: Uses same validation as event creation/editing
- **Reliable**: Prevents invalid event states

### 2. Maintainability  
- **Centralized Logic**: All validation in ViewHelper.php
- **Reusable**: Can be used by other features
- **Testable**: Clear input/output for testing

### 3. User Experience
- **Smart Behavior**: Automatic event conversion when appropriate
- **Clear Messages**: Explains what happened and why
- **Intuitive**: Handles complex scenarios transparently

### 4. Data Integrity
- **Prevents Invalid States**: Events always maintain valid collaboration status
- **Atomic Updates**: All related changes in single database operation  
- **Consistent State**: Event structure always matches collaboration status

## Testing Scenarios

1. **Basic Group Denial**: Standard denial with sufficient remaining groups
2. **Event Conversion**: Denial that triggers single-group conversion
3. **Zone Validation**: Denial blocked due to zone requirements
4. **Chapter Impact**: Chapter denial affecting parent group status
5. **Permission Validation**: Ensuring proper authorization
6. **Already Processed**: Handling duplicate denial attempts
7. **Database Errors**: Graceful handling of update failures
8. **Complex Cascades**: Multiple groups/chapters affected by single denial

This enhanced approach provides a robust, maintainable solution that handles all the complex scenarios that can arise in collaboration management while maintaining consistency with the existing codebase architecture.
