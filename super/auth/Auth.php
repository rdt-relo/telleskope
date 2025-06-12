<?php

require_once __DIR__ . '/Permission.php';

class Auth
{
    private static $permissions = [];

    private static $manage_companyids = [];

    public static function Init(): void
    {
        $permissions = json_decode($_SESSION['permissions'] ?? '', true);
        self::$permissions = $permissions ?: [];

        self::$manage_companyids = $_SESSION['manage_companyids'] ?? [];
    }

    public static function HasPermission(Permission $permission): bool
    {
        if (self::IsSuperSuperAdmin()) {
            return true;
        }
        return in_array($permission->value, self::$permissions);
    }

    public static function CheckPermission(Permission $permission): void
    {
        if (self::HasPermission($permission)) {
            return;
        }

        http_response_code(403);
        exit(1);
    }

    public static function IsSuperSuperAdmin(): bool
    {
        return $_SESSION['manage_super'];
    }

    public static function CheckSuperSuperAdmin(): void
    {
        if (self::IsSuperSuperAdmin()) {
            return;
        }

        http_response_code(403);
        exit(1);
    }

    public static function CanManageCompany(int $companyid): bool
    {
        if (self::IsSuperSuperAdmin()) {
            return true;
        }

        return in_array($companyid, self::$manage_companyids);
    }

    public static function CheckManageCompany(int $companyid): void
    {
        if (self::CanManageCompany($companyid)) {
            return;
        }

        Http::Forbidden();
    }
}
