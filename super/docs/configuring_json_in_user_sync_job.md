# Provisioning System JSON Configuration Guide

This document outlines the structure and configuration options within the JSON file used to drive the provisioning system. This system processes records from a CSV file (produced by the HRIS system) and provisions them into the target database based on the rules defined in this JSON.

The JSON file contains a top-level object that can include the `"Fields"`, `"AddUsers"`, `"AssignGroups"`, `"resetEmailIfEmpty"`, `"resetExtendedFields"`, and `"RemoveNonCompliantGroupMembers"` sections.

## `"Fields"` Object

The `"Fields"` object contains a set of key-value pairs, where each key represents the target database field name (either a primary field or a key for an extended field). The value associated with each key is another object that defines how to extract and transform data from the CSV file for that specific field.

### Primary (First Class) Fields

These are direct mappings to primary fields in Teleskope database schema. The following primary fields are available in user class
`"externalid"`, `"firstname"`, `"lastname"`, `"pronouns"`, `"email"`, `"jobtitle"`, `"employeetype"`, `"department"`, `"branchname"`, `"city"`, `"state"`, `"country"`, `"region"`, `"opco"`, `"externalroles"`,`"employee_hire_date"`,`"employee_start_date"`,`"employee_termination_date"`

Each primary field configuration object can have the following keys:

* **`"ename"` (Required unless "constant" is present):** This string specifies the exact name of the corresponding column in the provisioning CSV file. The system will look for a column with this name to retrieve the data for this field.

* **`"or_ename"` (Optional):** This string provides a backup column name in the CSV file. If the column specified by `"ename"` is missing or empty for a particular record, the system will attempt to retrieve the value from the column specified by `"or_ename"`.

* **`"pattern"` (Optional):** This is an array of regular expression patterns. These patterns will be applied sequentially to the value retrieved from the CSV file (after considering `"ename"` and `"or_ename"`).

* **`"replace"` (Optional):** This is an array of replacement strings corresponding to the `"pattern"` array. If a pattern in the `"pattern"` array matches a part of the retrieved value, that matched part will be replaced by the corresponding string in the `"replace"` array. The number of elements in `"pattern"` and `"replace"` arrays must be the same. The replacement can use standard regular expression backreferences (e.g., `$1`, `$2`).

* **`"constant"` (Optional unless `"ename"` is present):** Instead of mapping to a CSV column, this allows you to define a static value that will be used for this field for all processed records.

**Examples of Primary Field Configurations:**

* **Mapping a direct column:**
    ```json
    "externalid": {
        "ename": "ExternalID"
    }
    ```
    This configuration maps the value from the "ExternalID" column in the CSV to the `externalid` field in the database.


* **Using a preferred name with a fallback:**
    ```json
    "firstname": {
        "ename": "Preferred First Name",
        "or_ename": "Legal First Name"
    }
    ```
    The system will first try to get the value from the "Preferred First Name" column. If that's empty, it will use the value from the "Legal First Name" column.


* **Applying data transformation using regular expressions:**
    ```json
    "lastname": {
        "ename": "Last Name",
        "pattern": ["/(\\w).* (\\w).*/", "/(\\w).*/"],
        "replace": ["$1$2", "John $1"]
    }
    ```
    Here, two transformations are defined for the "Last Name" column. First, it  capture the first letter of the first word and the first letter of the last word (assuming a space separates them). The matching value is then evaluated against the second regular expression which captures the first letter of the entire name and prepend "John " to it.
    Note: The pattern can an array of regular expressions or just a single regular expression. Similarly the replace values can be an array or single value to match the corresponding regular expressions.


* **Simple mapping:**
    ```json
    "email": {
        "ename": "email"
    }
    ```
    This directly maps the "email" column from the CSV to the `email` field.


* **Constant values:**
    ```json
    "region": {
        "constant": "Global"
    }
    ```
  This directly maps the value corresponding to the constant, "Global" to the `region` field.

### Extended Fields

The `"extended"` object within `"Fields"` is used to define how to populate customer-specific fields in your database. These fields are likely stored in a separate structure (e.g., a JSON blob or a related table) associated with the primary record. Each key within the `"extended"` object represents the *key* of the extended field in your database. The value associated with each of these keys is another object defining how to retrieve the corresponding value from the CSV.

Each extended field configuration object can have the following keys:

* **`"ename"` (Required unless `"constant"` is present):** This string specifies the name of the column in the provisioning CSV file that contains the value for this extended field.

* **`"or_ename"` (Optional):** Similar to primary fields, this provides a backup column name in the CSV file to retrieve the value from if the `"ename"` column is missing or empty.

* **`"pattern"` (Optional):** An array of regular expression patterns to transform the retrieved value.

* **`"replace"` (Optional):** An array of corresponding replacement strings for the patterns.

* **`"constant"` (Optional unless `"ename"` is present):** Instead of mapping to a CSV column, this allows you to define a static value that will be used for this extended field for all processed records.

* **`"catalog"` (Optional):** This object provides metadata about how the extended field might relate to a catalog or lookup table in your system. It can contain the following:
    * **`"keyname"`:** The name of the key used to identify the value in the catalog.
    * **`"keytype"`:** The expected data type of the key in the catalog (e.g., `"string or int"`).

**Examples of Extended Field Configurations:**

* **Mapping an extended field directly from a CSV column:**
    ```json
    "extended": {
        "MU": {
            "ename": "Market Unit Location",
            "catalog": {
                "keyname": "Market Unit",
                "keytype": "string or int"
            }
        }
    }
    ```
    This configuration maps the value from the "Market Unit Location" column in the CSV to an extended field with the key `"MU"`. The `"catalog"` information suggests that the value might correspond to a "Market Unit" key in an internal catalog, which could be a string or an integer.

* **Applying a transformation to an extended field:**
    ```json
    "extended": {
        "Initials": {
            "ename": "Firstname",
            "pattern": "/(\\w).*/",
            "replace": "I$1"
        }
    }
    ```
    This takes the value from the "Firstname" column, extracts the first letter, and prepends "I" to it. This transformed value will be stored in the extended field with the key `"Initials"`.

* **Using a constant value for an extended field:**
    ```json
    "extended": {
        "CON": {
            "constant": "Some constant value"
        }
    }
    ```
    For every record processed, the extended field with the key `"CON"` will be set to "Some constant value", regardless of the CSV file content.

* **Mapping an extended field with the same CSV column name as a primary field:**
    ```json
    "extended": {
        "STRT_DT": {
            "ename": "Employee Start Date"
        }
    }
    ```
    This maps the "Employee Start Date" column to an extended field with the key `"STRT_DT"`. Note that this is distinct from the primary field `"employee_start_date"` defined earlier.

### Control Fields (`delete_record`, `skip_record`)

These special fields allow you to define criteria for skipping or marking records for deletion during the provisioning process.

* **`"delete_record"` (Optional):** If this section is present, the system will evaluate the specified CSV column. If the value in that column matches the provided regular expression `"pattern"`, the record might be flagged for deletion (depending on your system's logic). The `"replace"` value is typically used to set a flag value (e.g., "yes") in an internal processing variable.

* **`"skip_record"` (Optional):** Similar to `"delete_record"`, if this section is present and the value in the specified CSV column matches the `"pattern"`, the system will likely skip processing this entire record. The `"replace"` value might be used to set a flag indicating the record should be skipped.

Each of these control field configurations can have the following keys:

* **`"ename"` (Required):** The name of the column in the CSV file to evaluate.
* **`"pattern"` (Required):** The regular expression pattern to match against the value in the specified column.
* **`"replace"` (Required):** The value to use if the pattern matches (often used internally as a flag).

**Examples of Control Field Configurations:**

* **Marking records for deletion:**
    ```json
    "delete_record": {
        "ename": "Status",
        "pattern": "/^Terminated$/i",
        "replace": "yes"
    }
    ```
    If the value in the "Status" column (case-insensitive match for "Terminated") is found, an internal flag associated with `delete_record` might be set to "yes".

* **Skipping records based on a value:**
    ```json
    "skip_record": {
        "ename": "ImportAction",
        "pattern": "/^DoNotImport$/",
        "replace": "true"
    }
    ```
    If the "ImportAction" column contains "DoNotImport", an internal flag for `skip_record` might be set to "true", causing the system to skip processing this record.

## `"AddUsers"` Array (Optional)

By default, the provisioning system updates data for existing users if a match is found (typically based on a unique identifier like `externalid`). The optional `"AddUsers"` array allows you to define specific conditions under which new users should be added to the system. This array contains a list of objects, each defining a set of filters and actions for adding new users.

Each object within the `"AddUsers"` array can have the following keys:

* **`"Filters"` (Required):** This object contains one or more filter criteria that must *all* be met for a new user to be added according to this configuration block. Each key within the `"Filters"` object represents the expected value (or a flag based on transformation) of a specific field. The value associated with each key is another object with the following structure:
    * **`"ename"` (Required unless `"constant"` is present):** The name of the column in the provisioning CSV file to filter on. This should correspond to an `"ename"` defined within the `"Fields"` section (or a transformed version of it).
    * **`"pattern"` (Optional):** A regular expression pattern to match against the value of the `"ename"` column.
    * **`"replace"` (Optional):** The replacement string to use if the pattern matches. Often used to set a flag value.
    * **`"constant"` (Optional unless `"ename"` is present):** A static value to compare against. If the value of the (potentially transformed) field from the CSV matches this constant, the filter condition is met.

* **`"zoneid"` (Required):** A numeric identifier of the zone to which the new user should be added if all the filters in the `"Filters"` object are satisfied.

* **`"sendWelcomeEmails"` (Optional, defaults to `false`):** A boolean value indicating whether welcome emails should be sent to the newly added user in the specified `"zoneid"`.

**Examples of `"AddUsers"` Configurations:**

* **Adding users with a specific email domain to zone 2 and sending welcome emails:**
    ```json
    "AddUsers": [
        {
            "Filters": {
                "1": {
                    "ename": "email",
                    "pattern": "/.*@teleskope.io/",
                    "replace": "1"
                }
            },
            "zoneid": 2,
            "sendWelcomeEmails": true
        }
    ]
    ```
    In this example, if the `email` field (after any potential transformation defined in the `"Fields"` section) matches the pattern `.*@teleskope.io`, a new user will be added to zone `2`, and welcome emails will be sent. The key `"1"` within `"Filters"` is arbitrary and serves as an identifier for this specific filter. The `"replace": "1"` suggests that if the pattern matches, the internal value associated with this filter becomes "1", and the overall filter condition for this `"AddUsers"` block is met if this "1" exists as a key in `"Filters"`.

* **Adding users based on a constant value to zone 3 without sending welcome emails:**
    ```json
    {
        "Filters": {
            "7": {
                "constant": "7"
            }
        },
        "zoneid": 3,
        "sendWelcomeEmails": false
    }
    ```
    Here, if an internal processing variable or a transformed field has the constant value `"7"`, a new user will be added to zone `3`, and no welcome emails will be sent. Again, `"7"` is an arbitrary key for the filter.

**Important Note:** The order of the objects within the `"AddUsers"` array might be significant depending on your system's logic. The system will likely evaluate these conditions sequentially.

## `"AssignGroups"` Array (Optional)

The `"AssignGroups"` array allows you to define rules for automatically assigning users to groups within specific zones based on filter criteria derived from the processed CSV data. This array contains a list of objects, each specifying the filters, the target group, and various group membership options.

Each object within the `"AssignGroups"` array can have the following keys:

* **`"Filters"` (Required):** Similar to the `"Filters"` in `"AddUsers"`, this object contains one or more filter criteria that must *all* be met for a user to be assigned to the specified group in this configuration block. The structure of each filter within this object is the same as described in the `"AddUsers"` section (using `"ename"`, `"pattern"`, `"replace"`, or `"constant"`). The keyname in the `"Filters"` object represents the expected value (or a flag based on transformation) of the filtered field.

* **`"groupname"` (Optional, but either `"groupname"` or `"groupid"` is required):** An object that specifies how to determine the name of the group to which the user should be assigned. It typically has an `"ename"` key pointing to the CSV column containing the group name.
    * **`"ename"` (Required if `"groupname"` is used):** The name of the column in the provisioning CSV file that holds the group name.

* **`"groupid"` (Optional, but either `"groupname"` or `"groupid"` is required):** An object that specifies a constant group identifier.
    * **`"constant"` (Required if `"groupid"` is used):** A static value representing the ID of the group to which the user should be assigned.

* **`"zoneid"` (Required):** The numeric identifier of the zone where the group assignment should occur.

* **`"sendWelcomeEmails"` (Optional, defaults to `false`):** A boolean value indicating whether welcome emails should be sent to the user upon being added to this group. This is independent of the `"sendWelcomeEmails"` setting in the `"AddUsers"` section.

* **`"autoRemove"` (Optional, defaults to `false`):** A boolean value indicating whether the user should be automatically removed from this group if they no longer meet the filter criteria in subsequent provisioning runs.

* **`"enforceSingleGroupMembershipInZone"` (Optional, defaults to `false`):** A boolean value indicating whether the user should be removed from any other groups within the specified `"zoneid"` before being added to this group.

* **`"registerForTeamRolename"` (Optional):** A string specifying a team role name that the user should be registered for within the assigned group (if applicable in your system).

* **`"sendLeaveEmails"` (Optional, defaults to `false`):** A boolean value indicating whether leave emails should be sent to the user upon being removed from this group (if `autoRemove` is `true` and the user is removed).

**Examples of `"AssignGroups"` Configurations:**

* **Assigning users to a group based on "Market Unit Location" and "Market Location", with the group name derived from "Metro City":**
    ```json
    "AssignGroups": [
        {
            "Filters": {
                "Northeast": {
                    "ename": "Market Unit Location"
                },
                "North America": {
                    "ename": "Market Location"
                }
            },
            "groupname": {
                "ename": "Metro City"
            },
            "zoneid": 3,
            "sendWelcomeEmails": false,
            "autoRemove": false,
            "enforceSingleGroupMembershipInZone": false,
            "sendLeaveEmails": false
        }
    ]
    ```
    In this example, if the "Market Unit Location" is "Northeast" AND the "Market Location" is "North America", the user will be added to a group whose name is taken from the "Metro City" column in zone `3`. No welcome or leave emails will be sent, and the user will not be automatically removed or have single group membership enforced in this zone.

* **Assigning users from "USA" to a group with a constant ID, enforcing single membership and registering for a team role:**
    ```json
    {
        "Filters": {
            "USA": {
                "ename": "Country"
            }
        },
        "groupid": {
            "constant": "1"
        },
        "zoneid": 3,
        "sendWelcomeEmails": true,
        "autoRemove": true,
        "enforceSingleGroupMembershipInZone": true,
        "registerForTeamRolename": "Mentor",
        "sendLeaveEmails": false
    }
    ```
    If the "Country" is "USA", the user will be added to the group with ID `1` in zone `3`. A welcome email will be sent, the user will be automatically removed if they no longer meet the criteria, they will be removed from any other groups in zone `3`, and they will be registered with the "Mentor" team role in this group. No leave emails will be sent upon removal.

## `"resetEmailIfEmpty"` (Optional)

A boolean value that determines whether the email address of an existing user should be reset to a default value if the corresponding `"email"` field in the CSV is empty. Defaults to `false`.

  ```json
  "resetEmailIfEmpty": true
  ```

If set to true, and the "email" column in the CSV is empty for an existing user, their email address in the target system will be reset.

If set to true, and the "email" column in the CSV is empty for an existing user, their email address in the target system will be reset.

## "resetExtendedFields" (Optional)

A boolean value that determines whether all extended fields for an existing user should be reset to their default values (or cleared) if no corresponding data is found in the CSV for those fields. Defaults to false. This applies to all keys defined within the "extended" section of the "Fields" object.

```json
"resetExtendedFields": true
```

If set to true, and there are no corresponding columns in the CSV or no transformations result in a value for an extended field for an existing user, those extended fields will be reset.

## "RemoveNonCompliantGroupMembers" (Optional)
This object defines settings for identifying and potentially removing users from groups based on their current attributes not matching the group's filter criteria.

It can contain the following keys, with one of them being required:

* "ZoneIdList" (Optional): An array of numeric zone identifiers. If provided, the system will process all restricted groups within these specified zones for non-compliant member removal.
* "GroupIdList" (Optional): An array of numeric group identifiers. If provided, the system will process these specific groups (plus any other groups identified by ZoneIdList) for non-compliant member removal.

Example of "RemoveNonCompliantGroupMembers" Configuration:

```json
    "RemoveNonCompliantGroupMembers": {
        "ZoneIdList": [1, 2, 3],
        "GroupIdList": [10, 25]
    }
```

In this example, the system would check for and potentially remove non-compliant members from groups  within zones 1, 2, and 3. It will also process groups with ids 10 and 25.

#### Important Considerations for Integration Engineers:

Carefully design the filters in "AddUsers" and "AssignGroups" to ensure users are added and assigned to the correct zones and groups based on your business logic.
Understand the implications of "resetEmailIfEmpty" and "resetExtendedFields" before enabling them, as this can lead to data loss if not configured correctly.
The "RemoveNonCompliantGroupMembers" section should be used with caution, as it can automatically remove users from groups. Ensure the filter logic for group assignment is accurate to avoid unintended removals.
The order of operations between adding users, assigning groups, and potentially removing non-compliant members will be determined by your system's implementation.
By incorporating these additional sections into the JSON configuration, you gain more control over how new users are added, how users are automatically assigned to groups, and how existing data is managed during the provisioning process. Remember to test all configurations thoroughly in a non-production environment before deploying them to production.
