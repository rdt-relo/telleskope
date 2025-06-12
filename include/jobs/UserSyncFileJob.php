<?php

class UserSyncFileJob extends Job
{
    private $userEmailsWithoutExternalIds;
    private $userCatalogs;
    private $restrictedGroups;

    private $userHeirarchy;

    public function __construct()
    {
        parent::__construct();
        $this->jobid = "USERSYNCFILE_{$this->cid}_0_0_{$this->instance}_" . microtime(TRUE);
        $this->jobtype = self::TYPE_USERSYNC_FILE;
        $this->userEmailsWithoutExternalIds = null;
        $this->userCatalogs = array();
        $this->restrictedGroups = null;
        $this->userHeirarchy = null;
    }

    public function __clone()
    {
        parent::__clone();
        $this->jobid = "USERSYNCFILE_{$this->cid}_{$this->createdby}_0_{$this->instance}_" . microtime(TRUE);
    }

    private function isUserWithoutExternalId(?string $email): bool
    {
        if ($this->userEmailsWithoutExternalIds === null)
            $this->userEmailsWithoutExternalIds = User::GetEmailsWithoutExternalIdsAsAMap();

        return !empty($email) && isset($this->userEmailsWithoutExternalIds[strtolower($email)]);
    }

    private function removeUserFromWithoutExternalIdList(string $email)
    {
        if ($this->userEmailsWithoutExternalIds === null)
            $this->userEmailsWithoutExternalIds = User::GetEmailsWithoutExternalIdsAsAMap();

        unset($this->userEmailsWithoutExternalIds[strtolower($email)]);
    }

    /**
     * @param string $local_filename - filename as stored in s3://uploader/{realm}/incoming/user-data-sync/filename
     * @param int $file_format - one of the formats provided in Teleskope::FILE_FORMAT_ e.g. FILE_FORMAT_CSV
     * @param int $repeat_days - no of days after which the job should be repeated.
     * @param array $meta - an associative array of data mapping, same format as what we use for company_saml_settings
     * @param string $notifyEmails - Email addresses who should get notification, comma seperated list
     * @param bool $file_zip - true if file is zipped. Not used at the moment.
     * @param string $json_path - needed only if JSON is used. This tells where to find the records if nested.
     * @param int $daysAfterWhichDeleteMissingEntries - if >0 user not synced in past N days (minimum 3) will be marked for deletion.
     * @param int $source_id - Default 0
     */
    public function saveAsUserDataSyncType(string $local_filename, int $file_format, int $repeat_days, array $meta, string $notifyEmails, bool $file_zip = false, string $json_path = '', int $daysAfterWhichDeleteMissingEntries = 0, int $source_id = 0)
    {
        global $_COMPANY;
        /* @var Company $_COMPANY */
        $details = array();
        $details['LocalFilename'] = $local_filename;
        $details['Action'] = 'user-data-sync';
        $details['FileFormat'] = $file_format;
        $details['Zip'] = $file_zip;
        $details['RepeatDays'] = $repeat_days;
        // Store the realm as password hash.... this is for security checking when processing the job.
        $details['SecurityHash'] = password_hash($this->cid . '_' . $_COMPANY->getRealm(), PASSWORD_BCRYPT);
        $meta['resetEmailIfEmpty'] = $meta['resetEmailIfEmpty'] ?? false;
        $meta['resetExtendedFields'] = $meta['resetExtendedFields'] ?? true;
        $details['Meta'] = $meta;
        $details['NotifyEmails'] = $notifyEmails;
        $details['JSON_Path'] = $json_path;
        $details['DeleteMissingEntries'] = $daysAfterWhichDeleteMissingEntries;
        $details['source_id'] = $source_id;
        $this->details = json_encode($details,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        parent::saveAsPerpetualType();
    }

    /**
     * @param string $local_filename filename as stored in s3://uploader/{realm}/incoming/user-data-delete/filename
     * @param int $file_format one of the formats provided in Teleskope::FILE_FORMAT_ e.g. FILE_FORMAT_CSV
     * @param int $repeat_days no of days after which the job should be repeated.
     * @param array $meta an associative array of data mapping, same format as what we use for Reports
     * @param string $notifyEmails - Email addresses who should get notification, comma seperated list
     * @param bool $file_zip true if file is zipped. Not used at the moment.
     * @param string $json_path , needed only if JSON is used. This tells where to find the records if nested.
     */
    public function saveAsUserDataDeleteType(string $local_filename, int $file_format, int $repeat_days, array $meta, string $notifyEmails, bool $file_zip = false, string $json_path = '')
    {
        global $_COMPANY;
        /* @var Company $_COMPANY */
        $details = array();
        $details['LocalFilename'] = $local_filename;
        $details['Action'] = 'user-data-delete';
        $details['FileFormat'] = $file_format;
        $details['Zip'] = $file_zip;
        $details['RepeatDays'] = $repeat_days;
        // Store the realm as password hash.... this is for security checking when processing the job.
        $details['SecurityHash'] = password_hash($this->cid . '_' . $_COMPANY->getRealm(), PASSWORD_BCRYPT);
        $details['Meta'] = $meta;
        $details['NotifyEmails'] = $notifyEmails;
        $details['JSON_Path'] = $json_path;
        $this->details = json_encode($details,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        parent::saveAsPerpetualType();
    }

    protected function processAsPerpetualType()
    {
        global $_COMPANY;

        global $_ZONE;
        if (!isset($_ZONE)) // $_ZONE is required by emailing functions
            $_ZONE = $_COMPANY->getEmptyZone('teleskope');

        // Set execution time to 720 seconds. Default for jobs is 120 seconds
        // We are doing this to allow jobs enough time to finish processing.
        set_time_limit(720);

        // Add slashes as preg_replace patterns may use back slashes
        //$details = json_decode(str_replace('\\', '\\\\', $this->details), true);
        $details = json_decode($this->details, true);

        $local_filename = $details['LocalFilename'];
        $action = $details['Action'];
        $file_format = (int)$details['FileFormat'];
        $file_zip = $details['Zip'] ?? false;
        $meta = $details['Meta'];
        $notifyEmails = $details['NotifyEmails'] ?? '';
        $json_path = $details['JSON_Path'] ?? '';
        $meta['resetEmailIfEmpty'] = $meta['resetEmailIfEmpty'] ?? false;
        $meta['resetExtendedFields'] = $meta['resetExtendedFields'] ?? true;

        // Update the delay as it will be used to calculate the next job start time.
        $repeat_days = (int)$details['RepeatDays'];
        if ($repeat_days) {
            $this->delay = $repeat_days * 86400;
        } else {
            $this->jobsubtype = 0; // This is the last iteration of the job so convert if from perpetual to done.
        }

        // 0 - Perform a integrity/security check to see
        if (!password_verify($this->cid . '_' . $_COMPANY->getRealm(), $details['SecurityHash'])) {
            Logger::Log("Job {$this->jobid} - Fatal Security Check failed", Logger::SEVERITY['SECURITY_ERROR']);
            return;
        }

        // Fetch restricted groups once as it will be used to determine criteria for all users.
        $this->restrictedGroups = [];
        if (!empty($meta['RemoveNonCompliantGroupMembers'])) {
            $zidlist = Arr::IntValues($meta['RemoveNonCompliantGroupMembers']['ZoneIdList'] ?? []);
            if (!empty($zidlist)) {
                $this->restrictedGroups = Group::GetAllRestrictedGroups($zidlist);
            }
            $gidlist = Arr::IntValues($meta['RemoveNonCompliantGroupMembers']['GroupIdList'] ?? []);
            foreach ($gidlist as $gid) {
                $rg_exists = false;
                foreach ($this->restrictedGroups as $rg) {
                    if ($rg->id() == $gid) {
                        $rg_exists = true;
                        break;
                    }
                }
                if (!$rg_exists) {
                    $gitem = Group::GetGroup($gid);
                    if ($gitem) {
                        $this->restrictedGroups[] = $gitem;
                    }
                }
            }
        }

        try {

            $rowNum = 0;

            if ($file_format === Teleskope::FILE_FORMAT_TSV || $file_format === Teleskope::FILE_FORMAT_CSV) {
                // getFileFromUploader will return file as string. We want to use fgetcsv due to its robust
                // capabilities.
                $fileContents = fopen('php://memory', 'r+');
                // Convert CR to new line and write the stream to in memory file.
                fwrite($fileContents, strtr($_COMPANY->getFileFromUploader($local_filename, $action), array("\r\n" => "\n", "\r" => "\n")));
                rewind($fileContents);

                $delimiter = ($file_format === Teleskope::FILE_FORMAT_TSV) ? "\t" : ',';

                // Get the header row first
                $header = fgetcsv($fileContents, 8192, $delimiter);
                if (empty($header)) {
                    throw new RuntimeException('No records found for processing');
                }

                if ($action === 'user-data-sync') {
                    $this->initializeUserHeirarchyBuild($meta);
                }

                // Very important: Remove the first <feff> character that is included in some CSV's, e.g. Henkel, BASF
                // Not doing this can lead to a lot of bad things
                // Winnebago bug - if the first column is enclosed in quotes then even though we remove <feff>
                // the quotes remain, so we will use same preg_replace to remove "
                $header[0] = preg_replace('/[\x00-\x08\x80-\xFF"]/', '', $header[0]);

                // Redhat file has Carriage return as first row seperator...,
                // the following block extracts header from the first row.
                // Note: it sacrifices the first row though, hopefully Redhat will fix the row in future.
// Since now we are converting all CR to newlines the following code is not needed but just left for future reference
//                {
//                    $header_row = implode(',;,', $header);
//                    $carriage_return = strpos($header_row, "\r");
//                    if (($carriage_return) !== false) {
//                        $header = explode(',;,', substr($header_row, 0, $carriage_return));
//                        //$data_row1 = explode(',;,', substr($header_row, $carriage_return));
//                    }
//                }

                $no_of_cols = count($header);
                while (($data_row = fgetcsv($fileContents, 8192, $delimiter)) !== FALSE) {
                    $rowNum++;

                    if ($rowNum%25 == 0) { // For every 25 records print error log to show the job is in progress
                        Logger::Log("Job {$this->jobid} - Processing record number {$rowNum}", Logger::SEVERITY['INFO']);
                    }

                    $data_row_cols = count($data_row);
                    if ($no_of_cols !== $data_row_cols) {
                        Logger::Log("Job {$this->jobid} - Skipping record, unexpected data in row #{$rowNum}, expecting {$no_of_cols} columns but got {$data_row_cols} columns", Logger::SEVERITY['WARNING_ERROR']);
                        continue;
                    }

                    $data_rec = array_combine($header, $data_row);

                    $record_marked_for_skipping = (User::XtractAndXformValue('skip_record', $data_rec, $meta['Fields']) ?? 'no') === 'yes';
                    if ($record_marked_for_skipping) {
                        Logger::Log("Job {$this->jobid} - Skipping record, row #{$rowNum}, is marked for skip_record", Logger::SEVERITY['WARNING_ERROR']);
                        continue;
                    }

                    $record_marked_for_deletion = (User::XtractAndXformValue('delete_record', $data_rec, $meta['Fields']) ?? 'no') === 'yes';

                    if ($action === 'user-data-delete' || $record_marked_for_deletion)
                        $this->deleteDataRec($data_rec, $meta);
                    elseif ($action === 'user-data-sync')
                        $this->updateDataRec($data_rec, $meta);
                    else
                        throw new RuntimeException('Invalid Operation');
                }

                if ($action === 'user-data-sync') {
                    $this->finalizeUserHeirarchyBuild();
                }
            } elseif ($file_format === Teleskope::FILE_FORMAT_JSON) {
                $data_recs = json_decode($_COMPANY->getFileFromUploader($local_filename, $action), true);

                if (!empty($json_path)) {
                    $json_path_parts = explode('.', $json_path);
                    foreach ($json_path_parts as $json_path_part) {
                        $data_recs = $data_recs[$json_path_part];
                    }
                }

                if (empty($data_recs)) {
                    throw new RuntimeException('No records found for processing');
                }

                foreach ($data_recs as $data_rec) {
                    $data_rec = Arr::Dot($data_rec); // Flatten nesting
                    $rowNum++;
                    if ($rowNum%25 == 0) { // For every 25 records print error log to show the job is in progress
                        Logger::Log("Job {$this->jobid} - Processing record number {$rowNum}", Logger::SEVERITY['INFO']);
                    }
                    if ($action === 'user-data-delete')
                        $this->deleteDataRec($data_rec, $meta);
                    elseif ($action === 'user-data-sync')
                        $this->updateDataRec($data_rec, $meta);
                    else
                        throw new RuntimeException('Invalid Operation');
                }
            } else {
                throw new RuntimeException('Invalid File Type');
            }

            if ($rowNum == 0) {
                throw new RuntimeException('No records found for processing');
            }

            if ($action === 'user-data-sync' && $details['DeleteMissingEntries']) {
                $daysAfterWhichDeleteMissingEntries = (int)$details['DeleteMissingEntries'];
                $daysAfterWhichDeleteMissingEntries = $daysAfterWhichDeleteMissingEntries > 3 ? $daysAfterWhichDeleteMissingEntries : 3;
                User::PurgeUsersNotValidatedSince($daysAfterWhichDeleteMissingEntries);
            }
            User::DeleteUsersMarkedForDeletion();

            $source_id = $details['source_id'] ?? 0;
            foreach ($this->userCatalogs as $category => $catalog) {
                UserCatalog::DeleteAllUserCatalogsByCategoryIdAndSource($category, $source_id);
                $category_internal_id = '';
                foreach ($catalog as $keyname => $userids) {
                    $kt = $this->getCatalogType($category, $meta);
                    $keytype = $kt['catalog_type'];
                    $category_internal_id = $kt['catalog_internal_id'];
                    UserCatalog::DeleteAndSaveCatalog($category, $category_internal_id, $keyname, $keytype, $userids, $source_id);
                    Logger::Log("Job {$this->jobid} - Saving catalog {$category}-{$keyname}-{$keytype}: " . count($userids) . ' records', Logger::SEVERITY['INFO']);
                }
            }
            $_COMPANY->expireRedisCache("UCC:{$_COMPANY->id()}");

        } catch (Exception $e) {
            Logger::Log("Job {$this->jobid} - Fatal Exception while processing {$local_filename}. Exception {$e->getMessage()}");
        } finally {
            if (!empty($notifyEmails)) {
                $subject = 'Completed ' . $action;
                $message = json_encode(array('filename' => $local_filename, 'record_count'=>$rowNum));
                $_COMPANY->emailSend2('Teleskope Scheduler', $notifyEmails, $subject, $message, $_ZONE->val('app_type'));
            }
        }
    }

    private function deleteDataRec(array $data_rec, array $meta)
    {
        global $_COMPANY;
        /* @var Company $_COMPANY */

        $externalid = User::XtractAndXformValue('externalid', $data_rec, $meta['Fields']);
        $email = User::XtractAndXformValue('email', $data_rec, $meta['Fields']);

        if (!empty($externalid) &&
            (
                ($user = User::GetUserByExternalId($externalid)) ||
                ($user = User::GetUserByEmail($email))
            )
        ) {
            $user->purge();
        }
    }

    private function updateDataRec(array $data_rec, array $meta)
    {
        global $_COMPANY;
        /* @var Company $_COMPANY */

        // Update the user
        $fields = $meta['Fields'];
        $externalid = User::XtractAndXformValue('externalid', $data_rec, $fields);
        $email = User::XtractAndXformValue('email', $data_rec, $fields) ?? '';
        $firstname = User::XtractAndXformValue('firstname', $data_rec, $fields);
        $lastname = User::XtractAndXformValue('lastname', $data_rec, $fields);
        $externalEmail = User::XtractAndXformValue('externalemail', $data_rec, $fields, true);

        if (empty($externalid)) {
            //If externalid is not set then skip the record
            Logger::Log("Job:updateDataRec Skipping (no external id provided, email = {$email})", Logger::SEVERITY['INFO']);
            return;
        }

        // If the email address is missing external id then repair it,
        if ($this->isUserWithoutExternalId($email)) {
            User::RepairEmailExternalId($email, $externalid);
            $this->removeUserFromWithoutExternalIdList($email);
            // Since user was repaired, fetch a copy from master db
            $user = User::GetUserByExternalId($externalid, true);
        } else {
            $user = User::GetUserByExternalId($externalid);
        }

        // Kohls usecase: users email address can be taken away, if so reset users email address if HRIS email is empty
        if ($user && empty($email) && $meta['resetEmailIfEmpty']) {
            // We need to reset users emails to internal teleskope email.
            // If users is already assigned internal email assigned, continue using it,
            // else generate a new teleskope email address.
            if (!$_COMPANY->isTeleskopeEmailAddress($user->val('email'))) {
                $user->updateEmail(
                    $_COMPANY->generateTeleskopeEmailAddress($externalid)
                );
            }
        }

        $matchingZones = $this->findMatchingZonesForAutoAddUsers($meta, $data_rec);
        if (!empty($matchingZones)) {
            if (!$user) {
                // Add the user first
                if (empty($email)) {
                    $email = $_COMPANY->generateTeleskopeEmailAddress($externalid);
                }
                $user = User::GetOrCreateUserByExternalId($externalid, $email, $firstname, $lastname, '', $externalEmail);
            }
            if ($user) {
                foreach ($matchingZones as $matchingZone) {
                    if ($matchingZone['zoneid']) {
                        // matching zone can be 0, so dont add the zone.
                        $user->addUserZone($matchingZone['zoneid'], true, $matchingZone['sendWelcomeEmails']);
                    }
                }
            }
        }

        [$addGroups, $removeGroups] = $this->findMatchingAndNonmatchingGroupsForAutoAssignment($meta, $data_rec);

        if (!empty($addGroups)) {
            if (!$user) { // Add the user first if it does not exist
                if (empty($email)) {
                    $email = $_COMPANY->generateTeleskopeEmailAddress($externalid);
                }
                $user = User::GetOrCreateUserByExternalId($externalid, $email, $firstname, $lastname, '', $externalEmail);
            }

            if ($user) {
                foreach ($addGroups as $addGroup) {

                    $group = null;
                    if ($addGroup['groupid']) {
                        $group = Group::GetGroup($addGroup['groupid']);
                    } elseif ($addGroup['groupname']) {
                        $group = Group::GetGroupByNameAndZoneId($addGroup['groupname'], $addGroup['zoneid']);
                    }

                    if ($group && $group->val('groupid')) {
                        // Step 1: Temporarily set the global $_ZONE to target group zone
                        global $_ZONE;
                        $tempZone = $_ZONE ?? null;
                        $_ZONE = $_COMPANY->getZone($addGroup['zoneid']);

                        // Step 2: Get a list of existing memberships in the zone and if the user is already a member
                        // then skip this iteration. Also see if the single user in zone (office raven use case)
                        // constraint is set, if so build a list of existing groups that need to be removed.
                        $existing_member_groupids = Arr::IntValues(Str::ConvertCSVToArray($user->getAllFollowedGroupsInZoneAsCSV($addGroup['zoneid'])));
                        if (!empty($existing_member_groupids)) {
                            if ($existing_member_groupids === array($group->id())) {
                                // If the user is already a member of the group that is being requested, then
                                // we can safely exit this iteration
                                continue;
                            }

                            // If enforceSingleGroupMembershipInZone is set then build a set of groups for which the
                            // membership should be removed and add such groups to $removeGroups array
                            if ($addGroup['enforceSingleGroupMembershipInZone']) {
                                $remove_membership_from_groupids = array_diff($existing_member_groupids, array($group->id()));
                                foreach ($remove_membership_from_groupids as $remove_membership_from_groupid) {
                                    $removeGroups[] = array (
                                        'groupid' => $remove_membership_from_groupid,
                                        'groupname' => null,
                                        'zoneid' => $addGroup['zoneid'],
                                        'sendWelcomeEmails' => $addGroup['sendWelcomeEmails'],
                                        'sendLeaveEmails' => $addGroup['sendLeaveEmails'],
                                        'enforceSingleGroupMembershipInZone' => $addGroup['enforceSingleGroupMembershipInZone'],
                                        'registerForTeamRolename' => $addGroup['registerForTeamRolename'],
                                    );
                                }
                            }
                        }

                        // Step 2: // Assign user to group
                        $user->joinGroup($group->id(), 0, 0, 0, $addGroup['sendWelcomeEmails'], false, 'HRIS_SYNC');
                        //$group->addOrUpdateGroupMemberByAssignment($user->id(), 0, 0, $addGroup['sendWelcomeEmails']);

                        // Step 2b: // Register for a Role
                        if (!empty($addGroup['registerForTeamRolename'])) {
                            $role = Team::GetTeamRoleByName($addGroup['registerForTeamRolename'],$group->id());
                            if (!empty($role)) {
                                $roleid = $role['roleid'];

                                $existing_registrations = Team::GetUserJoinRequests($group->id(), $user->id());
                                $continue_registration = true;
                                if (!empty($existing_registrations)){
                                    $existing_roleids_registered = array_column($existing_registrations,'roleid');
                                    if (in_array($roleid, $existing_roleids_registered)) // Skip: user already registered
                                        $continue_registration = false;

                                    if ($group->getTeamJoinRequestSetting() != 1) // Skip: Only one registration is allowed
                                        $continue_registration = false;
                                }

                                if ($continue_registration) {
                                    Team::SaveTeamJoinRequestData($group->id(), $roleid, '{}', 1, false, $user->id(), '0');
                                }
                            }
                        }

                        // Step 3: // Assign user to zone
                        $user->addUserZone($addGroup['zoneid'], false, false);

                        // Step 4: Clean up, i.e. restore original state of $_ZONE if it was previously defined.
                        $_ZONE = null;
                        if ($tempZone) {
                            $_ZONE = $tempZone;
                        } else {
                            unset($_ZONE);
                        }
                    }
                }
            }
        }

        if (!empty($removeGroups)) {
            if ($user) {
                foreach ($removeGroups as $removeGroup) {

                    $group = null;
                    if ($removeGroup['groupid']) {
                        $group = Group::GetGroup($removeGroup['groupid']);
                    } elseif ($removeGroup['groupname']) {
                        $group = Group::GetGroupByNameAndZoneId($removeGroup['groupname'], $removeGroup['zoneid']);
                    }

                    if ($group && $group->val('groupid')) {
                        // Step 1: Temporarily set the global $_ZONE to target group zone
                        global $_ZONE;
                        $tempZone = $_ZONE ?? null;
                        $_ZONE = $_COMPANY->getZone($removeGroup['zoneid']);

                        // Step 2: // Remove user from group
                        $user->leaveGroup($group->id(), 0, 0, $removeGroup['sendLeaveEmails'], false, 'HRIS_SYNC');

                        // Step 3: // Remove user from zone
                        // Not needed

                        // Step 4: Clean up, i.e. restore original state of $_ZONE if it was previously defined.
                        $_ZONE = null;
                        if ($tempZone) {
                            $_ZONE = $tempZone;
                        } else {
                            unset($_ZONE);
                        }
                    }
                }
            }
        }

        //if ($user && intval($user->val('isactive')) < 101) { // If the user is any state other than voluntary removal 101
        // Note we can now try to sync all users regardless of the isactive status as updateProfile2 has been updated
        // to not reset isactive status to 1 if the user is set for WipeClean or is blocked.
        if ($user) {
            $pronouns = User::XtractAndXformValue('pronouns', $data_rec, $fields);
            $jobTitle = User::XtractAndXformValue('jobtitle', $data_rec, $fields);
            $officeLocation = User::XtractAndXformValue('branchname', $data_rec, $fields);
            $city = User::XtractAndXformValue('city', $data_rec, $fields);
            $state = User::XtractAndXformValue('state', $data_rec, $fields);
            $country = User::XtractAndXformValue('country', $data_rec, $fields);
            $department = User::XtractAndXformValue('department', $data_rec, $fields);
            $region = User::XtractAndXformValue('region', $data_rec, $fields);
            $opco = User::XtractAndXformValue('opco', $data_rec, $fields);
            $employeeType = User::XtractAndXformValue('employeetype', $data_rec, $fields);
            $externalUsername = User::XtractAndXformValue('externalusername', $data_rec, $fields);
            $externalRoles = User::XtractAndXformValue('externalroles', $data_rec, $fields, true);
            $employee_hire_date = User::XtractAndXformValue('employee_hire_date', $data_rec, $fields, true);
            $employee_start_date = User::XtractAndXformValue('employee_start_date', $data_rec, $fields, true);
            $employee_termination_date = User::XtractAndXformValue('employee_termination_date', $data_rec, $fields, true);

            $extended_profile = array();
            if (!empty($fields['extended'])) {

                if ($meta['resetExtendedFields'] === true) {
                    // Remove extended profile so that instead of merge we overwrite it.
                    $user->removeExtendedProfile();
                }

                foreach ($fields['extended'] as $key => $value) {
                    $key_val = User::XtractAndXformValue($key, $data_rec, $fields['extended']);
                    if (!empty($key_val)) {
                        $extended_profile[$key] = $key_val;
                    }
                    // Add to catalog if set
                    if (isset($value['catalog']) && !empty($value['catalog']['keyname'])) {
                        $this->userCatalogs[$value['catalog']['keyname']][$key_val][] = $user->id();
                    }
                }
            }
            $extendedProfile = json_encode($extended_profile);

            $user->updateExternalRoles($externalRoles);

            $user->updateExternalEmailAddress($externalEmail);

            $user->updateProfile2($email, $firstname, $lastname, $pronouns, $jobTitle, $department, $officeLocation, $city, $state, $country, $region, $opco, $employeeType, $externalUsername, $extendedProfile, true, $employee_hire_date, $employee_start_date, $employee_termination_date);

            $this->processUserHeirarchyBuild ($user, $data_rec);

            if ($addGroups) {

            }

            $user->deactivateMembershipForNonCompliantRestrictedGroups($this->restrictedGroups, 'HRIS_SYNC');
        }
    }

    private function initializeUserHeirarchyBuild ($meta) : void
    {
        if (!empty($meta['Fields']['manager'])) {
            $this->userHeirarchy = [
                'manager_meta' => $meta['Fields']['manager'],
                'userid_to_current_managerid_map' => [],
                'userid_to_manager_externalid_map' => [],
                'userid_to_manager_email_map' => []
            ];
        }
    }

    /**
     * This function helps build an
     *  - array of existing managers of users ('userid_to_current_managerid_map)
     *  - array of externalids of new manager of users  (userid_to_manager_externalid_map)
     *  - array of emails of new managers of users (userid_to_manager_email_map)
     * @param User $user
     * @param array $data_rec
     * @return void
     */
    private function processUserHeirarchyBuild (User $user, array $data_rec): void
    {
        if ($this->userHeirarchy === null)
            return;

        $this->userHeirarchy['userid_to_current_managerid_map'][$user->id()] = $user->val('manager_userid');

        $manager_externalid = User::XtractAndXformValue('externalid', $data_rec, $this->userHeirarchy['manager_meta']);
        if ($manager_externalid) {
            $this->userHeirarchy['userid_to_manager_externalid_map'][$user->id()] = $manager_externalid;
            return;
        }

        $manager_email = User::XtractAndXformValue('email', $data_rec, $this->userHeirarchy['manager_meta']);
        if ($manager_email) {
            $this->userHeirarchy['userid_to_manager_email_map'][$user->id()] = $manager_email;
            return;
        }
    }

    private function finalizeUserHeirarchyBuild (): void
    {
        if ($this->userHeirarchy === null)
            return;

        $userid_to_new_managerid_map = array();

        $manager_externalid_map = array();
        foreach ($this->userHeirarchy['userid_to_manager_externalid_map'] as $uid => $manager_externalid) {
            if (!isset($manager_externalid_map[$manager_externalid])) { // Initialize it
                $manager_externalid_map[$manager_externalid] = User::GetUserByExternalId($manager_externalid) ?-> id();
            }
            $manager_userid = $manager_externalid_map[$manager_externalid];
            if ($manager_userid) {
                $userid_to_new_managerid_map[$uid] = $manager_userid;

                // Since we processed this userid, remove it from second map if it exists there. external id takes precedence over email
                unset($this->userHeirarchy['userid_to_manager_email_map'][$uid]);
            }
        }

        $manager_email_map = array();
        foreach ($this->userHeirarchy['userid_to_manager_email_map'] as $uid => $manager_email) {
            if (!isset($manager_email_map[$manager_email])) { // Initialize it
                $manager_email_map[$manager_email] = User::GetUserByEmail($manager_email) ?-> id();
            }
            $manager_userid = $manager_email_map[$manager_email];
            $userid_to_new_managerid_map[$uid] = $manager_userid;
        }

        $userid_to_current_managerid_map = $this->userHeirarchy['userid_to_current_managerid_map'];
        foreach ($userid_to_current_managerid_map as $uid => $curr_mgr_id) {
            // Check if the user exists in the new map
            $new_mgr_id = $userid_to_new_managerid_map[$uid] ?? null;
            if ($curr_mgr_id != $new_mgr_id) {
                $uObj = User::Hydrate($uid,[]);
                $uObj->updateManagerUserId($new_mgr_id);
            }
        }

        $this->userHeirarchy = null; // reset
    }

    /**
     * This method iterates on AssignGroup part of Meta to see if there are groups available for assignment.
     * First understand the format of "AssignGroups". It is an array of entries; and each entry has three components
     * (1) Filters: This is a associative array of filters with Key representing what the desired value should be for
     * the filter to success and Value portion is something that can be sent through standard Xtraction and Xformation of
     * external attributes. If more than one filter is provided then all should match for the filter to succeed.
     * (2) groupname: It is standard external field that will be processed through standard Xtraction and Xformation
     * (3) zoneid: This is an integer that would be used for finding a group by name in the given scope.
     *
     * An example of AssignGroups JSON is
     *
     * "AssignGroups":[{"Filters":{"Northeast":{"ename":"Market Unit Location"}},"groupname":{"ename":"Metro City"},"zoneid":1002},{"Filters":{"South":{"ename":"Market Unit Location"}},"zoneid":1003,"groupname":{"ename":"Metro City"}, "sendWelcomeEmails":false, "autoRemove":false, "enforceSingleGroupMembershipInZone":false, "registerForTeamRolename": "Mentor", "sendLeaveEmails": false}]
     *
     * @param array $meta
     * @param array $data_rec
     * @return array Array of matchingGroups => zoneids
     */
    private function findMatchingAndNonmatchingGroupsForAutoAssignment(array $meta, array $data_rec): array
    {
        global $_COMPANY;
        $addGroups = array();
        $removeGroups = array();
        if (!empty($meta['AssignGroups'])) {
            foreach ($meta['AssignGroups'] as $assignGroup) {
                // Check if the existing record matches the filter
                if (!empty($assignGroup['Filters'])) {

                    $zoneid = (int)$assignGroup['zoneid'];
                    $groupname = User::XtractAndXformValue('groupname', $data_rec, $assignGroup, true);
                    $groupid = User::XtractAndXformValue('groupid', $data_rec, $assignGroup, true);

                    if ($_COMPANY->getZone($zoneid) && // Only if Zone matches one of companys zones
                        (!empty($groupname) || !empty($groupid)) // And one of the group identifier criterias is set
                    ) {

                        $filterMatch = true;
                        $sendWelcomeEmails = $assignGroup['sendWelcomeEmails'] ?? false;
                        $sendLeaveEmails = $assignGroup['sendLeaveEmails'] ?? false;
                        $registerForTeamRolename = $assignGroup['registerForTeamRolename'] ?? '';
                        $enforceSingleGroupMembershipInZone =  $assignGroup['enforceSingleGroupMembershipInZone'] ?? false;

                        foreach ($assignGroup['Filters'] as $fk => $fv) {
                            $filterMatch = $filterMatch && ($fk == User::XtractAndXformValue($fk, $data_rec, array($fk => $fv)));
                        }

                        if ($filterMatch) {
                            $addGroups[] = array('groupid' => $groupid, 'groupname' => $groupname, 'zoneid' => $zoneid, 'sendWelcomeEmails' => $sendWelcomeEmails, 'sendLeaveEmails' => $sendLeaveEmails, 'enforceSingleGroupMembershipInZone' => $enforceSingleGroupMembershipInZone, 'registerForTeamRolename' => $registerForTeamRolename);
                        } elseif ($assignGroup['autoRemove'] ?? false) {
                            $removeGroups[] = array('groupid' => $groupid, 'groupname' => $groupname, 'zoneid' => $zoneid, 'sendWelcomeEmails' => $sendWelcomeEmails, 'sendLeaveEmails' => $sendLeaveEmails, 'enforceSingleGroupMembershipInZone' => $enforceSingleGroupMembershipInZone, 'registerForTeamRolename' => $registerForTeamRolename);
                        }
                    }
                }
            }
        }

        return array($addGroups, $removeGroups);
    }

    /**
     * This method returns zones to which the user should be auto added
     * @param array $meta
     * @param array $data_rec
     * @return array of ['zoneid' => int, 'sendWelcomeEmails' => bool]
     */
    private function findMatchingZonesForAutoAddUsers(array $meta, array $data_rec): array
    {
        global $_COMPANY;
        $matchingZones = array();
        if (!empty($meta['AddUsers'])) {
            foreach ($meta['AddUsers'] as $assignZone) {
                // Check if the existing record matches the filter
                if (!empty($assignZone['Filters'])) {
                    $filterMatch = true;
                    foreach ($assignZone['Filters'] as $fk => $fv) {
                        $filterMatch = $filterMatch && ($fk == User::XtractAndXformValue($fk, $data_rec, array($fk => $fv)));
                    }

                    if ($filterMatch) {
                        $sendWelcomeEmails = $assignZone['sendWelcomeEmails'] ?? false;
                        $matchingZones[] = array('zoneid' => (int)$assignZone['zoneid'], 'sendWelcomeEmails' => $sendWelcomeEmails);
                    }
                }
            }
        }
        return $matchingZones;
    }

    /**
     * Extract Catalog Type from the meta data for a given catalog category
     * @param string $catalog_category
     * @param array $meta
     * @return array
     */
    private function getCatalogType(string $catalog_category, array $meta): array
    {
        $catalog_type = 'string';
        $catalog_internal_id = '-'; // to create a id that will not match
        $extended_fields = $meta['Fields']['extended'] ?? array();
        foreach ($extended_fields as $extended_field_internal_id => $extended_field) {
            if (isset($extended_field['catalog'])
                && $extended_field['catalog']['keyname'] == $catalog_category
                && !empty($extended_field['catalog']['keytype'])) {
                $catalog_type = ($extended_field['catalog']['keytype'] == 'int') ? 'int' : 'string';
                $catalog_internal_id = 'extendedprofile.'. $extended_field_internal_id;
            }
        }
        return [
            'catalog_type' => $catalog_type,
            'catalog_internal_id' => $catalog_internal_id
        ];
    }
}