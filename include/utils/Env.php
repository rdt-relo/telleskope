<?php

class Env
{
    public static function IsLocalEnv(): bool
    {
        self::AbortIfLocalCronJob();

        $local_subdomains = [
            'gmail2',
            'dev2'
        ];
        $subdomain = Str::GetStringBeforeCharacter($_SERVER['SERVER_NAME'], '.');
        return in_array($subdomain, $local_subdomains);
    }

    public static function IsTestEnv(): bool
    {
        self::AbortIfLocalCronJob();

        $test_subdomains = [
            'gmail',
            'dev',
            'yahoo',
            'hotmail',
            'outlook'
        ];
        $subdomain = Str::GetStringBeforeCharacter($_SERVER['SERVER_NAME'], '.');
        return in_array($subdomain, $test_subdomains);
    }

    public static function IsProdEnv(): bool
    {
        self::AbortIfLocalCronJob();

        return !self::IsLocalEnv() && !self::IsTestEnv();
    }

    public static function IsSelenium(): bool
    {
        if (Env::IsCronJob()) {
            return false;
        }

        if (Env::IsProdEnv()) {
            return false;
        }

        return ($_COOKIE['is_selenium'] ?? null) === '1';
    }

    public static function IsCronJob(): bool
    {
        return in_array($_SERVER['REQUEST_URI'], [
            '/1/cron/notifications',
            '/1/cron/notifications.php',
        ]);
    }

    private static function AbortIfLocalCronJob(): void
    {
        if (self::IsCronJob() && $_SERVER['SERVER_NAME'] === 'localhost') {
            throw new Exception('Cannot detect app environment in cron job!');
        }
    }

    public static function Get($env_var): string|array|false
    {
        return getenv($env_var);
    }

    public static function Put($env_var, $value = null): bool
    {
        if (is_null($value)) {
            $assignment = $env_var;
        } else {
            $assignment = "{$env_var}={$value}";
        }

        return putenv($assignment);
    }

    public static function IsSuperAdminDashboard(): bool
    {
        return str_starts_with($_SERVER['REQUEST_URI'], '/1/super/');
    }

    public static function IsAdminPanel(): bool
    {
        return Url::IsValidTeleskopeAdminDomain($_SERVER['HTTP_HOST']);
    }

    public static function IsDebugMode(): bool
    {
        if (Env::IsCronJob()) {
            return false;
        }

        if (Env::IsProdEnv()) {
            return false;
        }

        return true;
    }
}
