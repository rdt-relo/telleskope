<?php
require_once __DIR__ . '/EaiPermission.php';

class EaiAuth
{
    private static $eai_account;

    public static function Init(EaiAccount $eai_account): void
    {
        self::$eai_account = $eai_account;
    }

    public static function HasPermission(EaiPermission $permission): bool
    {
        return self::$eai_account->hasPermission($permission);
    }

    public static function CheckPermission(EaiPermission $permission): void
    {
        if (self::HasPermission($permission)) {
            return;
        }

        Http::Forbidden();
    }

    public static function CanAccessZone(?int $zone_id = null): bool
    {
        return self::$eai_account->canAccessZone($zone_id);
    }

    public static function CheckZone(): void
    {
        if (self::$eai_account->canAccessZone()) {
            return;
        }

        Http::Forbidden();
    }

    public static function CheckIP(): void
    {
        $remote_ip = IP::GetRemoteIPAddr();

        // If request is proxied, then each proxy is added as a comma seperated value.
        if (str_contains($remote_ip, ',')) {
            // Leftmost IP is the original IP.
            $remote_ip = Str::GetStringBeforeCharacter($remote_ip, ',');
        }

        $eai_whitelisted_ips = self::$eai_account->getEaiWhitelistedIps();

        if (empty($eai_whitelisted_ips)) {
            return;
        }

        if (Ip::InCIDRList($remote_ip, $eai_whitelisted_ips)) {
            return;
        }

        Http::Forbidden();
    }
}
