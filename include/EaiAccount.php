<?php
class EaiAccount extends Teleskope
{
    protected $appends = [
        'username',
    ];

    public static function GetEaiAccount(int $eai_account_id): ?self
    {
        global $_COMPANY;

        $results = self::DBROGetPS(
            'SELECT * FROM `eai_accounts` WHERE `companyid` = ? AND `accountid` = ?',
            'ii',
            $_COMPANY->id(),
            $eai_account_id
        );

        $eai_account = $results[0] ?? null;

        if (empty($eai_account)) {
            return null;
        }

        return new self($eai_account_id, $_COMPANY->id(), $eai_account);
    }

    /**
     * @param string $module_name - a valid module name, e.g. 'uploader
     * @param string $realm - realm is {subdomain}.teleskope.io
     * @param string $password - API password
     * @return mixed|null - null on error, or Company object decorated with attributes
     */
    public static function GetEaiAccountAfterAuthentication (string $module_name, string $realm, string $password)
    {
        $realm_parts = explode('.', $realm);

        $subdomain = $realm_parts[0];
        $username_prefix = null;
        if (count($realm_parts) === 4) {
            $username_prefix = $realm_parts[0];
            $subdomain = $realm_parts[1];
        }

        if (($company = Company::GetCompanyBySubdomain($subdomain)) === null) {
            Logger::Log("Enterprise API Security Error: Company not found", Logger::SEVERITY['SECURITY_ERROR']);
            return null;
        }

        $query = '
            SELECT *
            FROM `eai_accounts`
            WHERE `companyid` = ?
            AND `module` = ?
            AND `isactive` = 1
        '
            . (
            $username_prefix
                ?
                'AND `username_prefix` = ?'
                :
                'AND `username_prefix` IS NULL'
            );

        $bind_types = 'ix';
        $bind_params = [
            $company->id(),
            $module_name,
        ];

        if ($username_prefix) {
            $bind_types .= 's';
            $bind_params[] = $username_prefix;
        }

        $eai_account = self::DBROGetPS($query, $bind_types , ...$bind_params);
        if (!empty($eai_account)) {
            if (password_verify($password,$eai_account[0]['passwordhash']) && $eai_account[0]['failed_logins'] < 3) {
                self::DBMutate("UPDATE eai_accounts SET last_used=now(),failed_logins=0 WHERE accountid={$eai_account[0]['accountid']}");
                return new self($eai_account[0]['accountid'], $eai_account[0]['companyid'], $eai_account[0]);
            } else {
                $failed_logins = $eai_account[0]['failed_logins'] + 1;
                self::DBMutate("UPDATE eai_accounts SET last_used=now(),failed_logins={$failed_logins} WHERE accountid={$eai_account[0]['accountid']}");
            }
        }
        Logger::Log("Enterprise API Security Error: Account not found", Logger::SEVERITY['SECURITY_ERROR']);
        return  null;
    }

    public function getUsername(): string
    {
        global $_COMPANY;

        $username = $_COMPANY->val('subdomain') . '.teleskope.io';
        if (empty($this->val('username_prefix'))) {
            return $username;
        }

        return $this->val('username_prefix') . '.' . $username;
    }

    public function hasPermission(EaiPermission $permission): bool
    {
        return in_array($permission->value, $this->getPermissions());
    }

    public function getPermissions(): array
    {
        $authorization_info = json_decode($this->val('authorization_info') ?? '', true) ?? [];
        return $authorization_info['permissions'] ?? [];
    }

    public function getZoneIds(): array
    {
        $authorization_info = json_decode($this->val('authorization_info') ?? '', true) ?? [];
        return $authorization_info['zone_ids'] ?? [];
    }

    public function canAccessZone(?int $zone_id = null): bool
    {
        global $_ZONE;
        $zone_id ??= $_ZONE->id();
        return in_array($zone_id, $this->getZoneIds());
    }

    public function getEaiWhitelistedIps(): array
    {
        $authorization_info = json_decode($this->val('authorization_info') ?? '', true) ?? [];
        return $authorization_info['eai_whitelisted_ips'] ?? [];
    }
}
