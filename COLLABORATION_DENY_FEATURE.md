# Event Collaboration Deny Feature

## Overview
Added functionality to deny event collaboration requests in addition to the existing accept functionality. Users can now either accept or deny collaboration requests for both groups and chapters.

## Changes Made

### 1. Template Updates (`requested_group_approval_for_event_collaboration.template.php`)

#### Group Collaboration Buttons
- Added a red "Deny Collaboration" button next to the existing "Accept Collaboration" button
- Both buttons respect the same permission system (disabled if user lacks permissions)
- Uses Bootstrap classes: `btn btn-danger confirm rsvp-deny-btn btn-sm btn-inline`

#### Chapter Collaboration Buttons  
- Added corresponding deny buttons for chapter-level collaborations
- Maintains the same permission checking as group buttons

#### JavaScript Functions
- Added `denyEventGroupCollaboration(eventid, groupid)` function
- Added `denyEventChapterCollaboration(eventid, groupid, chapterid)` function
- Both functions show success/error messages and update the UI to show "Denied" status

### 2. AJAX Endpoints (`ajax_events.php`)

#### New Endpoint: `denyEventGroupCollaboration`
- **URL**: `ajax_events.php?denyEventGroupCollaboration=1`
- **Method**: GET
- **Parameters**: 
  - `eventid`: Encoded event ID
  - `groupid`: Encoded group ID
- **Functionality**:
  - Validates user permissions (same as approve: Company Admin, Zone Admin, or content management permissions)
  - **Validates collaboration requirements before denial**:
    - Ensures at least 2 groups remain in collaboration after denial
    - Ensures at least one group from the current zone remains in collaboration
  - Removes the group from `collaborating_groupids_pending` list
  - Also removes any pending chapter collaborations for that group
  - Returns JSON response with status and message

#### New Endpoint: `denyEventChapterCollaboration`
- **URL**: `ajax_events.php?denyEventChapterCollaboration=1`  
- **Method**: GET
- **Parameters**:
  - `eventid`: Encoded event ID
  - `groupid`: Encoded group ID
  - `chapterid`: Encoded chapter ID
- **Functionality**:
  - Validates user permissions for chapter-level content management
  - **Validates collaboration requirements before denial**:
    - Checks if denying chapter would remove parent group from collaboration
    - Ensures minimum collaboration requirements are maintained if parent group is affected
  - Removes the chapter from `collaborating_chapterids_pending` list
  - Returns JSON response with status and message

## How It Works

### Permission System
The deny functionality uses the same permission system as the approve functionality:

- **Group Level**: User must be Company Admin, Zone Admin, or have content management permissions for the group
- **Chapter Level**: User must have chapter-level content creation/publishing permissions

### Database Updates
When a collaboration is denied:
1. **Validation**: System validates that denial won't violate collaboration requirements
2. **Removal**: The group/chapter ID is removed from the respective `_pending` field
3. **Update**: The event's collaboration data is updated via the existing `updateCollaboratingGroupids()` method
4. **No New Fields**: No new database fields were added - denial is handled by removal from pending lists
The deny functionality includes comprehensive validation to maintain event collaboration integrity:

#### Minimum Collaboration Requirements
- **Two Group Minimum**: Events must maintain at least 2 collaborating groups (approved + pending)
- **Zone Requirements**: At least one group from the current zone must remain in the collaboration
- **Chapter Impact**: Denying chapters validates if the parent group would be affected

#### Validation Logic
1. **Group Denial**: 
   - Counts remaining collaborating groups (approved + pending after denial)
   - Checks zone requirements for remaining groups
   - Prevents denial if requirements would be violated

2. **Chapter Denial**:
   - Checks if chapter is the only one for its parent group
   - If so, validates that removing the parent group wouldn't violate collaboration minimums
   - Allows denial if parent group has other chapters or if minimums are maintained

#### Error Messages
- **Insufficient Groups**: "Cannot deny this collaboration request. An event must have at least two [groups] for collaboration..."
- **Zone Requirements**: "Cannot deny this collaboration request. At least one [group] from the current zone must remain..."  
- **Chapter Impact**: "Cannot deny this chapter collaboration request. An event must have at least two [groups] for collaboration. Denying this chapter would remove its parent group..."

### User Experience
1. User sees both "Accept" and "Deny" buttons for pending collaboration requests
2. Clicking "Deny" shows a confirmation dialog
3. After denial, the button area shows "Denied" with a red X icon
4. The collaboration request is permanently removed from pending status

## UI Changes

### Button Layout
```html
<!-- Group Collaboration -->
<button class="btn btn-primary confirm rsvp-approve-btn btn-sm btn-inline mr-2">Accept Collaboration</button>
<button class="btn btn-danger confirm rsvp-deny-btn btn-sm btn-inline">Deny Collaboration</button>

<!-- Chapter Collaboration -->  
<button class="btn btn-primary rsvp-approve-btn confirm btn-sm btn-inline mr-2">Accept Collaboration</button>
<button class="btn btn-danger rsvp-deny-btn confirm btn-sm btn-inline">Deny Collaboration</button>
```

### Status Display After Action
- **Accepted**: `<small><i class="fa fa-check"></i></small>`
- **Denied**: `<small><i class="fa fa-times text-danger"></i> Denied</small>`

## Technical Implementation Notes

### Error Handling
- All AJAX calls include try-catch blocks for JSON parsing
- Fallback error messages for unknown errors
- HTTP status codes for validation failures (403 Forbidden, 400 Bad Request)

### Response Codes
- `status = 1`: Single item processed (group or chapter)
- `status = 2`: Multiple items processed (group + associated chapters)  
- `status = 0`: Error occurred

### Backward Compatibility
- All existing functionality remains unchanged
- New deny functionality is additive
- Uses existing database schema and methods

## Testing Scenarios

1. **Group Denial**: User denies a group collaboration request
2. **Chapter Denial**: User denies a chapter collaboration request  
3. **Permission Validation**: Non-authorized users see disabled buttons
4. **Already Processed**: Attempting to deny an already processed request
5. **Minimum Groups Validation**: Attempting to deny when it would leave less than 2 groups
6. **Zone Requirements Validation**: Attempting to deny when it would leave no groups from current zone
7. **Chapter Impact Validation**: Attempting to deny a chapter that would affect parent group collaboration
8. **Error Handling**: Network errors, malformed responses

## Integration with ViewHelper Validation

The deny functionality integrates with the existing collaboration validation logic in `ViewHelper.php`:

### Validation Methods Used
- `Group::IsGroupInCurrentZone()` - Validates zone requirements
- `Group::GetGroupChaptersFromChapterIdsCSV()` - Checks chapter-group relationships
- Standard collaboration counting logic from `ValidateTopicCollaboration()`

### Consistency with Existing Logic
- Uses the same minimum collaboration rules as event creation/editing
- Follows the same zone requirement patterns
- Maintains the same chapter-group relationship logic

## Future Enhancements

If needed in the future, the system could be extended to:
1. Add dedicated database fields to track denied collaborations
2. Implement re-invitation capabilities for denied collaborations
3. Add audit logging for denial actions
4. Send notification emails for denials

## Files Modified

1. `affinity/views/common/requested_group_approval_for_event_collaboration.template.php`
   - Added deny buttons for groups and chapters
   - Added JavaScript functions for deny operations

2. `affinity/ajax_events.php`
   - Added `denyEventGroupCollaboration` endpoint
   - Added `denyEventChapterCollaboration` endpoint
