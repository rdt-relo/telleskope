<?php

class DelegatedAccess extends Teleskope
{
    const DELEGATE_ACCESS_TOKEN_ENC_KEY = 'Bgi27nWaRS1PrBmSaWmUf3Kp';
    const DELEGATE_ACCESS_TOKEN_ENC_IV = 'YunYGfpRomFwOoGil00sjCranElnz4dJ';

    public static function GetDelegatedAccessById(int $delegated_access_id): ?DelegatedAccess
    {
        global $_COMPANY, $_ZONE;

        $delegated_access = self::DBGet("
            SELECT  *
            FROM    `users_delegated_access`
            WHERE   `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
            AND     `delegated_access_id` = {$delegated_access_id}
        ");

        if (empty($delegated_access)) {
            return null;
        }

        return new DelegatedAccess($delegated_access_id, $_COMPANY->id(), $delegated_access[0]);
    }

    public static function CreateNewDelegatedAccess(int $grantee_userid, string $action_reason): int
    {
        global $_COMPANY, $_ZONE, $_USER;

        $grantor_userid = $_USER->id();

        $delegated_access_id = self::DBInsert("
            INSERT INTO `users_delegated_access` (
                `companyid`,
                `zoneid`,
                `grantor_userid`,
                `grantee_userid`
            ) VALUES (
                {$_COMPANY->id()},
                {$_ZONE->id()},
                {$grantor_userid},
                {$grantee_userid}
            )
        ");

        if ($delegated_access_id) {
            $delegatedAccessObject = self::GetDelegatedAccessById($delegated_access_id);
            $delegatedAccessObject->addAuditLog('GRANT', $action_reason);
            self::LogObjectLifecycleAudit('create', 'users_delegated_access', $delegated_access_id, 0, [
                'grantor_userid' => $grantor_userid,
                'grantee_userid' => $grantee_userid,
                'zoneid' => $_ZONE->id(),
            ]);
        }
        return $delegated_access_id;
    }

    public static function GetGrantorUsersByUserid(int $grantee_userid, ?int $zoneid): array
    {
        global $_COMPANY;

        $zone_filter = $zoneid ? "AND `zoneid` = {$zoneid}" : '';

        $results = self::DBROGet("
            SELECT  *
            FROM    `users_delegated_access`
            WHERE   `companyid` = {$_COMPANY->id()}
                AND `grantee_userid` = {$grantee_userid}
                {$zone_filter}
        ");

        return array_map(function (array $result) {
            return DelegatedAccess::Hydrate($result['delegated_access_id'], $result);
        }, $results);
    }

    public static function GetGranteeUsersByUserid(int $grantor_userid, ?int $zoneid): array
    {
        global $_COMPANY;
        $zone_filter = $zoneid ? "AND `zoneid` = {$zoneid}" : '';

        $results = self::DBROGet("
            SELECT  *
            FROM    `users_delegated_access`
            WHERE   `companyid` = {$_COMPANY->id()}
                AND `grantor_userid` = {$grantor_userid}
                {$zone_filter}
        ");

        return array_map(function (array $result) {
            return DelegatedAccess::Hydrate($result['delegated_access_id'], $result);
        }, $results);
    }

    public function deleteIt(string $action_reason): int
    {
        global $_COMPANY, $_USER;

        $retval = self::DBMutate("
            DELETE FROM `users_delegated_access`
            WHERE   `companyid` = {$_COMPANY->id()}
            AND     `delegated_access_id` = {$this->id()}
        ");

        if ($retval) {
            $this->addAuditLog('REVOKE', $action_reason);

            self::LogObjectLifecycleAudit('delete', 'users_delegated_access', $retval, 0, [
                'grantor_userid' => (int)$this->val('grantor_userid'),
                'grantee_userid' => (int)$this->val('grantee_userid'),
                'zoneid' => (int)$this->val('zoneid'),
            ]);
        }
        return $retval;
    }

    public static function GetDelegatedAccessByGrantorGranteeUserId(int $grantee_userid, int $grantor_userid): ?DelegatedAccess
    {
        global $_COMPANY, $_ZONE;

        $delegated_access = self::DBGet("
            SELECT  *
            FROM    `users_delegated_access`
            WHERE   `companyid` = {$_COMPANY->id()}
            AND     `zoneid` = {$_ZONE->id()}
            AND     `grantee_userid` = {$grantee_userid}
            AND     `grantor_userid` = {$grantor_userid}
        ");

        if (empty($delegated_access)) {
            return null;
        }

        $delegated_access = new DelegatedAccess($delegated_access[0]['delegated_access_id'], $_COMPANY->id(), $delegated_access[0]);

        return $delegated_access;
    }

    public function getDelegatedAccessToken(): string
    {
        global $_USER;

        if ($_USER->id() !== (int) $this->val('grantee_userid')) {
            return '';
        }

        $_SESSION['rand_tok'] ??= generateRandomToken(8);

        return aes_encrypt($this->val('delegated_access_id'), self::DELEGATE_ACCESS_TOKEN_ENC_KEY . $_SESSION['rand_tok'], self::DELEGATE_ACCESS_TOKEN_ENC_IV, false);
    }

    /**
     * Converts the token to Delegated Access Object.
     * @param string $token
     * @return DelegatedAccess|null
     */
    public static function GetDelegatedAccessByToken(string $token): ?DelegatedAccess
    {
        global $_USER;
        if (empty($_SESSION['rand_tok'])) {
            return null;
        }

        $delegated_access_id = (int)aes_encrypt($token, self::DELEGATE_ACCESS_TOKEN_ENC_KEY.$_SESSION['rand_tok'],self::DELEGATE_ACCESS_TOKEN_ENC_IV,true);
        if (empty($delegated_access_id)) {
            return null;
        }

        return  self::GetDelegatedAccessById($delegated_access_id);
    }

    public static function GetDelegatedZonesByGrantorGranteeUserId(int $grantee_userid, int $grantor_userid): array
    {
        global $_COMPANY;

        $results = self::DBROGet("
            SELECT  zoneid
            FROM    `users_delegated_access`
            WHERE   `companyid` = {$_COMPANY->id()}
            AND     `grantee_userid` = {$grantee_userid}
            AND     `grantor_userid` = {$grantor_userid}
        ");

        return empty($results) ? array() : array_column($results, 'zoneid');
    }

    public static function HandleUserTerminations(int $uid, string $user_details)
    {
        $grantorUsers = self::GetGrantorUsersByUserid($uid, null);
        foreach ($grantorUsers as $delegatedAccess) {
            $delegatedAccess->deleteIt("Delegate User Terminated ({$user_details})");
        }
        $granteeUsers = self::GetGranteeUsersByUserid($uid, null);
        foreach ($granteeUsers as $delegatedAccess) {
            $delegatedAccess->deleteIt("Grantor User Terminated ({$user_details})");
        }
    }

    /**
     * @param string $action valid values are 'GRANT', 'REVOKE', 'USAGE'
     * @param string $action_reason
     * @return void
     */
    public function addAuditLog(string $action, string $action_reason): void
    {
        global $_USER;

        if (!in_array($action, ['GRANT', 'REVOKE', 'USAGE'])) {
            return;
        }

        if ($action === 'USAGE') { // For usage, use grantee_userid
            $action_by_userid = $_SESSION['grantee_userid'] ?? 0;
        } else {
            $action_by_userid = $_USER ? $_USER->id() : 0;
        }

        self::DBInsert("
                INSERT INTO `users_delegated_access_log` (
                    `delegated_access_id`,
                    `companyid`,
                    `zoneid`,
                    `grantor_userid`,
                    `grantee_userid`,
                    `action`,
                    `action_by_userid`,
                    `action_reason`
                ) VALUES (
                    {$this->id()},
                    {$this->val('companyid')},
                    {$this->val('zoneid')},
                    {$this->val('grantor_userid')},
                    {$this->val('grantee_userid')},
                    '{$action}',
                    {$action_by_userid},
                    '{$action_reason}'
                )
            ");
    }
}
