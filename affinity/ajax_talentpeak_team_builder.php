<?php

define('AJAX_CALL', 1); // Define AJAX call for error handling
require_once __DIR__ . '/head.php'; //Common head.php includes destroying Session for timezone so defining INDEX_PAGE. Need head for logging and protecting

global $_COMPANY;
global $_USER;
global $_ZONE;
global $db;

if (!$_COMPANY->getAppCustomization()['teams']['teambuilder_enabled']) {
    header(HTTP_BAD_REQUEST);
    exit();
}

function getUsers(int $groupid)
{
    global $_COMPANY;

    $reportMeta = ReportTeamRegistrations::GetDefaultReportRecForDownload();
    if (!$reportMeta) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Override critical field names with names that are used in this file
    $reportMeta['Fields']['userid'] = 'user_id';
    $reportMeta['Fields']['externalid'] = 'external_id'; // To all team imports to work without issues
    $reportMeta['Fields']['email'] = 'email'; // To all team imoprts to work without issues
    $reportMeta['Fields']['requestCapacity'] = 'Request Capacity';
    $reportMeta['Fields']['availableCapacity'] = 'Available Capacity';
    $reportMeta['Fields']['roleType'] = 'Requested Role';

    $catalogCategories = UserCatalog::GetAllCatalogCategories();
    $reportMeta['Fields'] = array_merge($reportMeta['Fields'], $catalogCategories);

    $reportMeta['Options'] = [
        'download_matched_users' => 1,
        'download_unmatched_users' => 1,
        'download_active_join_requests' => 1,
        'download_inactive_join_requests' => 0,
        'download_paused_join_requests' => 0,
        'download_active_users_only' => 1,
    ];

    $reportMeta['Filters'] = [
        'groupid' => $groupid,
        'userid' => 0,
        'roleid' => 0,
    ];

    $record = [];
    $record['companyid'] = $_COMPANY->id();
    $record['reportid'] = -1;
    $record['reportname'] = 'survey';
    $record['reportmeta'] = json_encode($reportMeta);

    $report = new ReportTeamRegistrations($_COMPANY->id(), $record);

    $users = $report->generateReportAsAssocArray();

    $users = array_values(array_filter($users, function ($user) {
        if ((int) $user['user_id'] === -1) {
            return false;
        }

        if ((int) $user['Available Capacity'] <= 0) {
            return false;
        }

        if (empty($user['Requested Role'])) {
            return false;
        }

        return true;
    }));

    $users = array_map(function ($user) {
        foreach ($user as $key => $val) {
            $user[$key] = Csv::ParseCell($val);
        }
        return $user;
    }, $users);

    return [
        'users' => $users,
        'primaryAttributes' => array_values($catalogCategories),
    ];
}

function getColumnCountByValue(array $users, string $column): array
{
    $counts = [];
    foreach ($users as $user) {
        if (!is_array($user[$column])) {
            $user[$column] = [$user[$column]];
        }
        foreach ($user[$column] as $val) {
            $counts[$val] = $counts[$val] ?? 0;
            $counts[$val] += (1/count($user[$column]));
        }
    }
    return $counts;
}

function applyMinRule(array $input): array
{
    global $MIN_RULES_APPLIED;
    $MIN_RULES_APPLIED ??= [];

    $users = $input['users'];
    $usersCount = count($users);
    $offset = $input['offset'] ?? 0;
    $teamRoles = $input['teamRoles'];
    $teamSetup = $input['teamSetup'];
    $teamSize = $input['teamSize'];
    $userFieldValues = $input['userFieldValues'];
    $teamsCount = $input['teamsCount'];
    $teamRoles = $input['teamRoles'];
    $checkboxAttributeMatchingLevel = $input['checkboxAttributeMatchingLevel'];

    while (true) {
        $anyAssignmentsInThisPass = false;
        for (
            $index = 0, $i = 0;
            ($index < ($input['count'] * $teamsCount))
            && ($i < $usersCount);
            $i++
        ) {
            if (isset($users[$i]['team_number'])) {
                continue;
            }

            $user_attribute = $users[$i][$input['minRuleField']];
            $user_attribute = is_array($user_attribute) ? $user_attribute : [$user_attribute ?? ''];
            if (!array_intersect($input['minRuleFieldValues'], $user_attribute)) {
                continue;
            }

            $teamNumber = (($index + $offset) % $teamsCount) + 1;

            if (!checkMaxRoleCondition($users, $teamNumber, $teamRoles[$users[$i]['Requested Role']])) {
                continue;
            }

            if (!canAssignTeam($users, $users[$i], $teamNumber, $teamSetup, $teamSize, $userFieldValues, $teamRoles, $checkboxAttributeMatchingLevel)) {
                continue;
            }

            $users[$i]['team_number'] = $teamNumber;
            $index++;
            $anyAssignmentsInThisPass = true;
        }

        if ($index == ($input['count'] * $teamsCount)) {
            break;
        }

        $attempts = ($attempts ?? 0);
        if ($attempts === $teamsCount) {
            break;
        }

        if (!$anyAssignmentsInThisPass) {
            $offset++;
            $attempts++;
        }
    }

    $MIN_RULES_APPLIED[] = [
        'minRuleField' => $input['minRuleField'],
        'count' => $input['count'],
        'minRuleFieldValues' => $input['minRuleFieldValues'],
    ];

    return [
        $users,
        $teamNumber ?? $offset,
    ];
}

function checkMaxRoleCondition(array $users, int $teamNumber, array $teamRole): bool
{
    $count = count(array_values(array_filter($users, function ($user) use ($teamNumber, $teamRole) {
        return
            ($user['team_number'] ?? 0) === $teamNumber
            && $user['Requested Role'] === $teamRole['type'];
    })));

    return $teamRole['max_allowed'] > $count;
}

function getTeamRoles(int $groupid): array
{
    $teamRoles = Team::GetProgramTeamRoles($groupid, 1);
    return array_column($teamRoles, null, 'type');
}

function canAssignTeam(
    array $users,
    array $user,
    int $teamNumber,
    array $teamSetup,
    int $teamSize,
    array $userFieldValues,
    array $teamRoles,
    string $checkboxAttributeMatchingLevel
): bool {
    global $MIN_RULES_APPLIED;

    $userId = $user['user_id'];

    if (!empty($user['team_number'])) {
        return false;
    }

    $userAssignedTeams = array_filter($users, function ($user) use ($userId) {
        return ($user['user_id'] === $userId) && !empty($user['team_number']);
    });

    $teamNumbers = array_column($userAssignedTeams, 'team_number');

    if (in_array($teamNumber, $teamNumbers)) {
        return false;
    }

    $teamUsers = array_filter($users, function ($user) use ($teamNumber) {
        return ($user['team_number'] ?? 0) === $teamNumber;
    });

    if (count($teamUsers) >= $teamSize) {
        return false;
    }

    $selectedAttributes = array_keys($userFieldValues);

    $attributesToMatch = array_intersect($teamSetup['attributesToMatch'], $selectedAttributes);
    $attributesToMatch = array_diff($attributesToMatch, $teamSetup['checkboxAttributesToMatch']);

    foreach ($attributesToMatch as $attribute) {
        $teamUserAttributes = array_column($teamUsers, $attribute);
        if (($teamUserAttributes[0] ?? $user[$attribute]) !== $user[$attribute]) {
            return false;
        }
    }

    $attributesToNotMatch = array_intersect($teamSetup['attributesToNotMatch'], $selectedAttributes);
    foreach ($attributesToNotMatch as $attribute) {
        $teamUserAttributes = array_column($teamUsers, $attribute);
        if (in_array($user[$attribute], $teamUserAttributes)) {
            return false;
        }
    }

    foreach ($MIN_RULES_APPLIED as $minRule) {
        $count = count(
            array_filter(
                array_column($teamUsers, $minRule['minRuleField']),
                function ($value) use ($minRule) {
                    $value = is_array($value) ? $value : [$value ?? ''];
                    return array_intersect($value, $minRule['minRuleFieldValues']);
                },
            )
        );

        if ($count < $minRule['count']) {
            return false;
        }
    }

    // check for max capacity, if its above max, return false
    $userAssignedTeamsWithSameRole = array_filter($userAssignedTeams, function ($userA) use ($user) {
        return $userA['Requested Role'] === $user['Requested Role'];
    });

    if (count($userAssignedTeamsWithSameRole) >= $user['Available Capacity']) {
        return false;
    }

    // If user is already assigned to a team, then check if all users of that role have a team or not?
    // Is this user taking any other user's place?
    // If all other users of that role have a team, then continue else stop
    if ($userAssignedTeamsWithSameRole) {
        $userGroups = array_filter($users, function ($userA) use ($user) {
            return
                (int) $userA['user_id'] !== (int) $user['user_id']
                && $userA['Requested Role'] === $user['Requested Role'];
        });

        $userGroups = Arr::GroupBy($userGroups, 'user_id');

        foreach ($userGroups as $user_id => $userGroup) {
            $userTeamNumbers = array_column($userGroup, 'team_number');
            $count = count(array_filter($userTeamNumbers, function ($teamNumber) {
                return !empty($teamNumber);
            }));

            if (!$count) {
                return false;
            }
        }
    }

    foreach ($teamSetup['checkboxAttributesToMatch'] as $attribute) {
        $user[$attribute] = is_array($user[$attribute]) ? $user[$attribute] : [$user[$attribute] ?? ''];
        $matches = 0;

        foreach ($teamUsers as $teamUser) {
            $team_user_attribute = is_array($teamUser[$attribute]) ? $teamUser[$attribute] : [$teamUser[$attribute] ?: ''];
            $common_checkboxes = array_intersect($user[$attribute], $team_user_attribute);

            if (!empty($common_checkboxes)
                || (empty($team_user_attribute) && empty($user[$attribute]))
            ) {
                $matches++;
            }
        }

        switch ($checkboxAttributeMatchingLevel) {
            case 'MATCH_ALL':
                if (count($teamUsers) !== $matches) {
                    return false;
                }

                break;

            case 'MATCH_ATLEAST_HALF':
                if (count($teamUsers) > ($matches * 2)) {
                    return false;
                }
                break;

            case 'MATCH_ATLEAST_ONE':
                if (count($teamUsers) && !$matches) {
                    return false;
                }
                break;

            case 'NONE':
                break;
        }
    }

    $mentorTeamUsers = array_filter([...$teamUsers, $user], function ($teamUser) use ($teamRoles) {
        return (int) $teamRoles[$teamUser['Requested Role']]['sys_team_role_type'] === array_flip(Team::SYS_TEAMROLE_TYPES)['Mentor'];
    });

    $menteeTeamUsers = array_filter([...$teamUsers, $user], function ($teamUser) use ($teamRoles) {
        return (int) $teamRoles[$teamUser['Requested Role']]['sys_team_role_type'] === array_flip(Team::SYS_TEAMROLE_TYPES)['Mentee'];
    });

    $reverse_operators = [
        '<' => '>=',
        '<=' => '>',
        '>' => '<=',
        '>=' => '<',
    ];

    if ($mentorTeamUsers && $menteeTeamUsers) {
        foreach (['<' => $teamSetup['numericAttributesLessThan'], '<=' => $teamSetup['numericAttributesLessThanEq']] as $operator => $attributes) {
            foreach ($attributes as $attribute) {
                $max_mentor_value = max(array_column($mentorTeamUsers, $attribute));
                $min_mentee_value = min(array_column($menteeTeamUsers, $attribute));

                if (version_compare($max_mentor_value, $min_mentee_value, $reverse_operators[$operator])) {
                    return false;
                }
            }
        }

        foreach (['>' => $teamSetup['numericAttributesGreaterThan'], '>=' => $teamSetup['numericAttributesGreaterThanEq']] as $operator => $attributes) {
            foreach ($attributes as $attribute) {
                $min_mentor_value = min(array_column($mentorTeamUsers, $attribute));
                $max_mentee_value = max(array_column($menteeTeamUsers, $attribute));

                if (version_compare($min_mentor_value, $max_mentee_value, $reverse_operators[$operator])) {
                    return false;
                }
            }
        }
    }

    return true;
}

function expandUsers(array $users, array $teamRoles): array
{
    $newUsers = [];
    foreach ($users as $user) {
        for ($i = 1; $i <= $user['Available Capacity']; $i++) {
            $newUsers[] = $user;
        }
    }
    return $newUsers;
}

function mergeUsers(array $users): array
{
    $userGroups = Arr::GroupBy($users, 'user_id');

    $newUsers = [];
    foreach ($userGroups as $userId => $userGroupByUserId) {
        // Do not merge same user with different roles
        // For eg, do not merge {user-id = 1, role = mentor} with {user-id = 1, role = mentee}
        $userGroupByUserIdAndRole = Arr::GroupBy($userGroupByUserId, 'Requested Role');
        foreach ($userGroupByUserIdAndRole as $userRole => $userGroup) {
            $assignedTeamNumbers = [];
            $assignedUsers = array_filter($userGroup, function ($user) use (&$assignedTeamNumbers) {
                $teamNumber = ($user['team_number'] ?? 0);

                if (!$teamNumber) {
                    return false;
                }

                if (in_array($teamNumber, $assignedTeamNumbers)) {
                    return false;
                }

                $assignedTeamNumbers[] = $teamNumber;
                return true;
            });

            if ($assignedUsers) {
                array_push($newUsers, ...$assignedUsers);
            } else {
                $newUser = $userGroup[0];
                $newUser['team_number'] = 0;
                $newUsers[] = $newUser;
            }
        }
    }
    return $newUsers;
}

function addScoreToUsers(array $users, array $userFieldValues, array $userFields): array
{
    $usersCount = count($users);

    foreach ($users as $index => $user) {
        $debug = ['scores' => []];
        $score = 0;

        foreach ($userFields as $userField) {
            if (!is_array($user[$userField])) {
                $user[$userField] = [$user[$userField]];
            }
            foreach ($user[$userField] as $userFieldValue) {
                $userFieldScore = (1 - ($userFieldValues[$userField][$userFieldValue] / $usersCount)) * (100 / count($user[$userField]));
                $userFieldScore = round($userFieldScore, 2);

                if (trim($userFieldValue) === '') {
                    $userFieldScore = 0;
                }

                if (str_starts_with(strtolower(trim($userFieldValue)), 'other')) {
                    $userFieldScore = 0;
                }

                $score += $userFieldScore;

                $debug['scores'][$userField] = $debug['scores'][$userField] ?? [];
                $debug['scores'][$userField][$userFieldValue] = $userFieldScore;
            }
        }

        $score = round($score/count($userFields), 2);
        $users[$index]['score'] = $score;

        $debug['score'] = $score;
        $users[$index]['debug'] = json_encode($debug);
    }

    $userScores = array_column($users, 'score');
    array_multisort($userScores, SORT_DESC, $users);

    return $users;
}

function reduceUserFields(array $users, array $primaryAttributes, Group $group): array
{
    $teamSetup = getTeamSetup($primaryAttributes, $group);
    $selectableAttributes = $teamSetup['selectableAttributes'];

    return array_map(function ($user) use ($selectableAttributes) {
        $userSubset = [];
        foreach ($selectableAttributes as $attribute) {
            $userSubset[$attribute] = $user[$attribute];
        }
        return $userSubset;
    }, $users);
}

function getTeamSetup(array $primaryAttributes, Group $group, array $userSelectedAttributes = []): array
{
    global $_COMPANY;

    $customAttributes = $group->getTeamMatchingAlgorithmAttributes();

    $matchingParameters = $group->getTeamMatchingAlgorithmParameters();
    $matchingPrimaryParameters = $matchingParameters['primary_parameters'] ?? [];
    $matchingCustomParameters = $matchingParameters['custom_parameters'] ?? [];

    $selectableAttributes = [];
    $attributesToMatch = [];
    $attributesToNotMatch = [];
    $checkboxAttributesToMatch = [];

    $numericAttributesLessThan = [];
    $numericAttributesGreaterThan = [];
    $numericAttributesLessThanEq = [];
    $numericAttributesGreaterThanEq = [];

    $selectablePrimaryAttributes = $primaryAttributes;
    $selectableCustomAttributes = ['Requested Role'];

    if ($_COMPANY->getAppCustomization()['chapter']['enabled']) {
        $selectableAttributes[] = $_COMPANY->getAppCustomization()['chapter']['name-short'];
        $attributesToMatch[] = $_COMPANY->getAppCustomization()['chapter']['name-short'];
        $selectableCustomAttributes[] = $_COMPANY->getAppCustomization()['chapter']['name-short'];
    }

    $selectableAttributes = [
        ...$selectableAttributes,
        'Requested Role',
        ...$primaryAttributes,
    ];

    foreach ($matchingPrimaryParameters as $attribute => $value) {
        if (UserCatalog::GetCategoryKeyType($attribute) === 'string') {
            if ((array_flip(UserCatalog::STRING_TYPE_OPERATORS))['=='] == $value) {
                $attributesToMatch[] = $attribute;
            }
            if ((array_flip(UserCatalog::STRING_TYPE_OPERATORS))['!='] == $value) {
                $attributesToNotMatch[] = $attribute;
            }
        } else {
            if ((array_flip(UserCatalog::INT_TYPE_OPERATORS))['=='] == $value) {
                $attributesToMatch[] = $attribute;
            }
            if ((array_flip(UserCatalog::INT_TYPE_OPERATORS))['!='] == $value) {
                $attributesToNotMatch[] = $attribute;
            }
            if ((array_flip(UserCatalog::INT_TYPE_OPERATORS))['<'] == $value) {
                $numericAttributesLessThan[] = $attribute;
            }
            if ((array_flip(UserCatalog::INT_TYPE_OPERATORS))['>'] == $value) {
                $numericAttributesGreaterThan[] = $attribute;
            }
            if ((array_flip(UserCatalog::INT_TYPE_OPERATORS))['>='] == $value) {
                $numericAttributesGreaterThanEq[] = $attribute;
            }
            if ((array_flip(UserCatalog::INT_TYPE_OPERATORS))['<='] == $value) {
                $numericAttributesLessThanEq[] = $attribute;
            }
        }
    }

    foreach (($customAttributes['pages'] ?? []) as $pageAttributes) {
        foreach ($pageAttributes['elements'] as $attribute) {
            if (in_array($attribute['type'], ['checkbox', 'radiogroup', 'rating', 'dropdown'])) {
                $attributeName = $attribute['title'] ?? $attribute['name'];
                $selectableAttributes[] = $attributeName;
                $selectableCustomAttributes[] = $attributeName;

                if ($matchingCustomParameters[$attribute['name']] === Team::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['MATCH']) {
                    // For radio buttons and dropdowns
                    $attributesToMatch[] = $attributeName;
                } elseif ($matchingCustomParameters[$attribute['name']] === Team::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['MATCH_N_NUMBERS']) {
                    // For checkboxes
                    $attributesToMatch[] = $attributeName;
                    $checkboxAttributesToMatch[] = $attributeName;
                } elseif ($matchingCustomParameters[$attribute['name']] === Team::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['DONOT_MATCH']) {
                    $attributesToNotMatch[] = $attributeName;
                } elseif ($matchingCustomParameters[$attribute['name']] === Team::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['GREATER_THAN']) {
                    $numericAttributesGreaterThan[] = $attributeName;
                } elseif ($matchingCustomParameters[$attribute['name']] === Team::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['LESS_THAN']) {
                    $numericAttributesLessThan[] = $attributeName;
                } elseif ($matchingCustomParameters[$attribute['name']] === Team::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['GREATER_THAN_OR_EQUAL_TO']) {
                    $numericAttributesGreaterThanEq[] = $attributeName;
                } elseif ($matchingCustomParameters[$attribute['name']] === Team::CUSTOM_ATTRIBUTES_MATCHING_OPTIONS['LESS_THAN_OR_EQUAL_TO']) {
                    $numericAttributesLessThanEq[] = $attributeName;
                }
            }
        }
    }

    if ($userSelectedAttributes) {
        return [
            'selectableAttributes' => array_intersect($userSelectedAttributes, $selectableAttributes),
            'attributesToMatch' => array_intersect($userSelectedAttributes, $attributesToMatch),
            'attributesToNotMatch' => array_intersect($userSelectedAttributes, $attributesToNotMatch),
            'checkboxAttributesToMatch' => array_intersect($userSelectedAttributes, $checkboxAttributesToMatch),
            'numericAttributesLessThan' => array_intersect($userSelectedAttributes, $numericAttributesLessThan),
            'numericAttributesGreaterThan' => array_intersect($userSelectedAttributes, $numericAttributesGreaterThan),
            'numericAttributesLessThanEq' => array_intersect($userSelectedAttributes, $numericAttributesLessThanEq),
            'numericAttributesGreaterThanEq' => array_intersect($userSelectedAttributes, $numericAttributesGreaterThanEq),
            'selectablePrimaryAttributes' => $selectablePrimaryAttributes,
            'selectableCustomAttributes' => $selectableCustomAttributes,
        ];
    }

    return [
        'selectableAttributes' => $selectableAttributes,
        'attributesToMatch' => $attributesToMatch,
        'attributesToNotMatch' => $attributesToNotMatch,
        'checkboxAttributesToMatch' => $checkboxAttributesToMatch,
        'numericAttributesLessThan' => $numericAttributesLessThan,
        'numericAttributesGreaterThan' => $numericAttributesGreaterThan,
        'numericAttributesLessThanEq' => $numericAttributesLessThanEq,
        'numericAttributesGreaterThanEq' => $numericAttributesGreaterThanEq,
        'selectablePrimaryAttributes' => $selectablePrimaryAttributes,
        'selectableCustomAttributes' => $selectableCustomAttributes,
    ];
}

function assignTeamToUsers(array $userBatch, array $userFieldValues, array $userFields, array $teamRoles, array $teamSetup, int $teamSize, string $checkboxAttributeMatchingLevel): array
{
    $users = addScoreToUsers($userBatch, $userFieldValues, $userFields);
    $users = expandUsers($users, $teamRoles);

    $teamsCount = (int)ceil(count($users)/$teamSize);
    $teambuilder_analytics = [];

    /**
     * Try to find the optimal teams-count which yields maximum team-assignments
     * Try with Teams Count, (Teams-Count - 1), .... till (Teams-Count - 50)
     *
     * Disabled this optimisation
     * As its taking a lot of processing time and causing timeouts
     * Have set the $optimal_team_count_attempts to 1 now, earlier it was 50
     *
     * $optimal_team_count_attempts = 50;
     */
    $optimal_team_count_attempts = 1;
    for ($i = 0; $i < $optimal_team_count_attempts; $i++) {
        $newTeamsCount = ($teamsCount - $i);
        if ($newTeamsCount <= 0) {
            break;
        }

        $newUsers = assignTeamToUsersWithTeamCount($newTeamsCount, $users, $userFieldValues, $userFields, $teamRoles, $teamSetup, $teamSize, $checkboxAttributeMatchingLevel);

        $teambuilder_analytics[] = analyseTeamBuilderResults($newUsers, $newTeamsCount);
    }

    $unassignedUsers = array_column($teambuilder_analytics, 'unassigned_users');
    $assignedUsers = array_column($teambuilder_analytics, 'assigned_users');
    $teamsCountArr = array_column($teambuilder_analytics, 'teams_count');
    array_multisort($unassignedUsers, SORT_ASC, $assignedUsers, SORT_DESC, $teamsCountArr, SORT_ASC, $teambuilder_analytics);

    $json = file_get_contents($teambuilder_analytics[0]['tmpfile']);
    return json_decode($json, true);
}

function assignTeamToUsersWithTeamCount(int $teamsCount, array $users, array $userFieldValues, array $userFields, array $teamRoles, array $teamSetup, int $teamSize, string $checkboxAttributeMatchingLevel): array
{
    global $MIN_RULES_APPLIED;
    $MIN_RULES_APPLIED = [];

    if ($_POST['apply-min-rule'] ?? false) {
        [$users, $teamNumber] = applyMinRule([
            'users' => $users,
            'teamsCount' => $teamsCount,
            'count' => $_POST['min-rule-count'],
            'minRuleField' => $_POST['min-rule-field'],
            'minRuleFieldValues' => $_POST['min-rule-field-values'],
            'offset' => $teamNumber ?? 0,
            'teamRoles' => $teamRoles,
            'teamSetup' => $teamSetup,
            'teamSize' => $teamSize,
            'userFieldValues' => $userFieldValues,
            'checkboxAttributeMatchingLevel' => $checkboxAttributeMatchingLevel,
        ]);
    }

    foreach ($teamRoles as $roleName => $teamRole) {
        [$users, $teamNumber] = applyMinRule([
            'users' => $users,
            'teamsCount' => $teamsCount,
            'count' => $teamRole['min_required'],
            'minRuleField' => 'Requested Role',
            'minRuleFieldValues' => [$roleName],
            'offset' => $teamNumber ?? 0,
            'teamRoles' => $teamRoles,
            'teamSetup' => $teamSetup,
            'teamSize' => $teamSize,
            'userFieldValues' => $userFieldValues,
            'checkboxAttributeMatchingLevel' => $checkboxAttributeMatchingLevel,
        ]);
    }

    $users = deleteInvalidTeamsNotMeetingMinRules($users);

    $attempts = 0;
    while (true) {
        $index = $teamNumber ?? 0;
        $anyAssignmentsInThisPass = false;

        foreach ($users as $i => $user) {
            if (!empty($user['team_number'])) {
                continue;
            }

            $teamNumber = ($index % $teamsCount) + 1;
            if (!checkMaxRoleCondition($users, $teamNumber, $teamRoles[$user['Requested Role']])) {
                continue;
            }

            if (!canAssignTeam($users, $users[$i], $teamNumber, $teamSetup, $teamSize, $userFieldValues, $teamRoles, $checkboxAttributeMatchingLevel)) {
                continue;
            }

            $users[$i]['team_number'] = $teamNumber;
            $index++;
            $anyAssignmentsInThisPass = true;
        }

        if (!array_values(array_filter($users, function ($user) {
            return !($user['team_number'] ?? 0);
        }))) {
            break;
        }

        if ($attempts === $teamsCount) {
            break;
        }

        if ($anyAssignmentsInThisPass) {
            $attempts = 0;
        } else {
            $teamNumber = $attempts;
            $attempts++;
        }
    }

    return mergeUsers($users);
}

function analyseTeamBuilderResults(array $users, int $teamsCount): array
{
    $assigned_users = 0;
    $unassigned_users = 0;
    foreach ($users as $user) {
        if (empty($user['team_number'])) {
            $unassigned_users++;
        } else {
            $assigned_users++;
        }
    }

    $tmpfile = TmpFileUtils::GetTemporaryFile();
    file_put_contents($tmpfile, json_encode($users));

    return [
        'teams_count' => $teamsCount,
        'assigned_users' => $assigned_users,
        'unassigned_users' => $unassigned_users,
        'tmpfile' => $tmpfile,
    ];
}

function getUserBatches(array $users, array $teamSetup, array $userFieldValues)
{
    $selectedAttributes = array_keys($userFieldValues);
    $attributesToMatch = array_intersect($teamSetup['attributesToMatch'], $selectedAttributes);
    $attributesToMatch = array_diff($attributesToMatch, $teamSetup['checkboxAttributesToMatch']);

    $batches = [];
    foreach ($users as $user) {
        $groupBy = [];
        foreach ($attributesToMatch as $attribute) {
            $groupBy[] = $user[$attribute];
        }
        $key = implode('~~', $groupBy);
        $batches[$key] ??= [];
        $batches[$key][] = $user;
    }

    return $batches;
}

function deleteInvalidTeamsNotMeetingMinRules(array $inputUsers): array
{
    global $MIN_RULES_APPLIED;

    $assignedUsers = array_filter($inputUsers, function ($user) {
        return ($user['team_number'] ?? 0);
    });

    $userGroups = Arr::GroupBy($assignedUsers, 'team_number');
    $deleteTeams = [];

    foreach ($userGroups as $teamNumber => $users) {
        foreach ($MIN_RULES_APPLIED as $minRule) {
            $count = count(
                array_filter(
                    array_column($users, $minRule['minRuleField']),
                    function ($value) use ($minRule) {
                        $value = is_array($value) ? $value : [$value ?? ''];
                        return array_intersect($value, $minRule['minRuleFieldValues']);
                    },
                )
            );

            if ($count < $minRule['count']) {
                $deleteTeams[] = $teamNumber;
            }
        }
    }

    foreach ($inputUsers as $i => $user) {
        if (in_array(($user['team_number'] ?? 0), $deleteTeams)) {
            $inputUsers[$i]['team_number'] = 0;
        }
    }

    return $inputUsers;
}

if (isset($_GET['teamBuilder']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Data Validation
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1
        || ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$_USER->canManageGroupSomething($groupid) || !$group->canDownloadOrViewSurveyReport()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $result = getUsers($groupid);

    if (empty($result['users'])) {
        exit();
    }

    $users = reduceUserFields($result['users'], $result['primaryAttributes'], $group);

    $userFieldValues = [];
    $userFields = array_keys($users[0]);
    foreach ($userFields as $userField) {
        $userFieldValues[$userField] = array_keys(getColumnCountByValue($users, $userField));
    }

    unset($userFieldValues['Requested Role']);

    $teamRoles = getTeamRoles($groupid);
    $companyTeamName = $_COMPANY->getAppCustomization()['teams']['name'];
    $teamSetup = getTeamSetup($result['primaryAttributes'], $group);

    include(__DIR__.'/views/talentpeak/team_builder/index.html.php');

    exit();
}

elseif (isset($_GET['getSuggestedTeams']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Data Validation
    if (
        ($uid = $_COMPANY->decodeId($_POST['userid'])) < 0 ||
        ($roleid = $_COMPANY->decodeId($_POST['roleid'])) < 0 ||
        ($groupid = $_COMPANY->decodeId($_GET['groupid'])) < 1
        || ($group = Group::GetGroup($groupid)) === null
    ) {
        header(HTTP_BAD_REQUEST);
        exit();
    }

    // Authorization Check
    if (!$group->canDownloadOrViewSurveyReport()) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $teamSize = (int)$_POST['team-size'];
    $teamRoles = getTeamRoles($groupid);

    $result = getUsers($groupid);

    if (empty($result['users'])) {
        Http::Redirect("manage?id={$_COMPANY->encodeId($groupid)}");
    }

    $users = $result['users'];
    $selectedAttributes = [
        'Requested Role',
        ...($_POST['user-fields'] ?? []),
    ];

    $teamSetup = getTeamSetup($result['primaryAttributes'], $group, $selectedAttributes);

    $userFieldValues = [];
    foreach ($selectedAttributes as $userField) {
        $userFieldValues[$userField] = getColumnCountByValue($users, $userField);
    }

    $userBatches = getUserBatches($users, $teamSetup, $userFieldValues);
    $maxTeamNumber = 0;
    $prevMaxTeamNumberNumber = 0;
    $users = [];
    foreach ($userBatches as $userBatch) {

        $userFieldValues = [];
        foreach ($selectedAttributes as $userField) {
            $userFieldValues[$userField] = getColumnCountByValue($userBatch, $userField);
        }

        $levels = ['NONE'];
        if (!empty($teamSetup['checkboxAttributesToMatch'])) {
            $levels = [
                'MATCH_ALL',
                'MATCH_ATLEAST_HALF',
                'MATCH_ATLEAST_ONE',
                'NONE',
            ];
        }

        foreach ($levels as $checkboxAttributeMatchingLevel) {
            $userBatch = assignTeamToUsers($userBatch, $userFieldValues, $selectedAttributes, $teamRoles, $teamSetup, $teamSize, $checkboxAttributeMatchingLevel);
        }

        foreach ($userBatch as $i => $user) {
            if (!empty($userBatch[$i]['team_number'])) {
                $userBatch[$i]['team_number'] += $prevMaxTeamNumberNumber;
                $maxTeamNumber = max($maxTeamNumber, $userBatch[$i]['team_number']);
            }
        }

        $users = [
            ...$users,
            ...$userBatch
        ];

        $prevMaxTeamNumberNumber = $maxTeamNumber ?? $prevMaxTeamNumberNumber;
    }

    if (empty($users)) {
        Http::Redirect('manage?id=' . $_COMPANY->encodeId($groupid));
    }

    $userTeams = array_column($users, 'team_number');
    $userScores = array_column($users, 'score');
    array_multisort($userTeams, SORT_ASC, $userScores, SORT_DESC, $users);

    $teamNamePrefix = trim($_POST['team-name-prefix']);
    if (ctype_alpha(substr($teamNamePrefix, -1))) {
        $teamNamePrefix = $teamNamePrefix . '-';
    }
    $startingTeamNumber = (int) $_POST['starting-team-number'];
    $debug = $_COMPANY->getAppCustomization()['teams']['teambuilder_debug'];
    $users = array_map(function ($user) use ($teamNamePrefix, $startingTeamNumber, $debug) {
        $user['role_name'] = $user['Requested Role'];
        $user['team_name'] = '';
        if ($user['team_number']) {
            $user['team_name'] = $teamNamePrefix . ($user['team_number'] + $startingTeamNumber - 1);
        }
        unset($user['team_number'], $user['score'], $user['user_id'], $user['Requested Role']);
        if (!$debug) {
            unset($user['debug']);
        }
        $user['role_title'] = '';
        return $user;
    }, $users);

    $csv = [];
    $columns = array_keys($users[0]);
    $csv[] = $columns;
    foreach ($users as $user) {
        $row = [];
        foreach ($columns as $column) {
            $row[] = Csv::GetCell($user[$column]);
        }
        $csv[] = $row;
    }

    // Not sure why we had this array_flip here, it was causing column names to be incorrectly show internal names.
    //$column_names = array_flip(ReportTalentPeakUserJoinRequestSurvey::GetDefaultReportRecForDownload()['Fields']);
    //$csv[0] = array_map(function ($column) use ($column_names) {
    //    return $column_names[$column] ?? $column;
    //}, $csv[0]);

    $fp = tmpfile();
    $path = stream_get_meta_data($fp)['uri'];
    foreach ($csv as $rows) {
        fputcsv($fp, $rows);
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="results.csv"');
    readfile($path);

    exit();
}
