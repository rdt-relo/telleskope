<?php

require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/TmpFileUtils.php';

class Logger
{
    public static $reservedMemory;

    public const SEVERITY = [
        'WARNING_ERROR' => 'WARNING',
        'FATAL_ERROR' => 'FATAL',
        'INFO' => 'INFO',
        'SECURITY_ERROR' => 'SECURITY',
        'AUDIT' => 'AUDIT',
        'ALARM' => 'ALARM',
        'DEBUG' => 'DEBUG'
    ];

    public const MODULE = [
        'JOB' => 'JOB',
        'MAIL' => 'MAIL',
        'SUPER_USER' => 'SUPER_USER',
        'API' => 'API',
        'EAI' => 'EAI',
        'AUTH' => 'AUTH',
        'LAMBDA' => 'LAMBDA',
        'ADMIN' => 'ADMIN',
        'IFRAME' => 'IFRAME',
        'UNFURL' => 'UNFURL',
    ];

    private const MAX_FIELD_LENGTH = 1000;

    private static $start_timestamp;

    /**
     * @param string $message
     * @param string $severity valid values are 'WARNING_ERROR','FATAL_ERROR', 'INFO' ... see Logger::SEVERITY values
     * @param array $meta
     * @return bool
     */
    public static function Log(string $message, string $severity = '', array $meta = []): bool
    {
        global $_COMPANY, $_ZONE, $_USER, $_LOGGER_META_JOB, $_LOGGER_META_MAIL;

        if (!ENABLE_JSON_LOGS) {
            return false; //error_log($message); # Disabling all logs that are not JSON
        }

        if (!in_array($severity, array_values(self::SEVERITY))) {
            $severity = self::SEVERITY['FATAL_ERROR'];
        }

        $data = [
            'company_id' => $_COMPANY ?-> id(),
            'zone_id' => $_ZONE ?-> id(),
            'user_id' => $_USER ?-> id(),
            'message' => $message,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'host' => $_SERVER['HTTP_HOST'] ?? null,
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'],
            'tz' => $_SESSION['timezone'] ?? null,
            's_s' => $_SESSION['s_s'] ?? null,
            'meta' => $meta,
            'severity' => $severity,
        ];

        if (!empty($_SESSION['super_userid'])) {
            $data['masq_access'] = true;
            $data['super_user_id'] = $_SESSION['super_userid'];
        }

        if (!empty($_SESSION['grantee_userid'])) {
            $data['delegated_access'] = true;
            $data['grantee_user_id'] = $_SESSION['grantee_userid'];
        }

        if (strpos($data['uri'], '/1/api/') !== false) {
            $data['module'] = self::MODULE['API'];

        } elseif (strpos($data['uri'], '/1/super/') !== false) {
            $data['module'] = self::MODULE['SUPER_USER'];
            $data['meta']['super_user_id'] = $_SESSION['superid'] ?? null;

        } elseif (strpos($data['uri'], '/1/eai/') !== false) {
            $data['module'] = self::MODULE['EAI'];
            $data['meta']['eai_module'] = $_EAI_MODULE ?? null;
            $data['meta']['eai_user'] = $_SERVER['PHP_AUTH_USER'];

        } elseif (strpos($data['uri'], '/1/user/') !== false) {
            $data['module'] = self::MODULE['AUTH'];

        } elseif (strpos($data['uri'], '/1/iframe/') !== false) {
            global $_IFRAME_COMPANY, $_IFRAME_MODULE;
            $data['module'] = self::MODULE['IFRAME'];
            $data['company_id'] = $_IFRAME_COMPANY ? $_IFRAME_COMPANY->id() : null;
            $data['meta']['iframe_module'] = $_IFRAME_MODULE ?? null;

        } elseif (strpos($data['uri'], '/1/unfurl/') !== false) {
            global $_UNFURL_COMPANY;
            $data['module'] = self::MODULE['UNFURL'];
            $data['company_id'] = $_UNFURL_COMPANY ? $_UNFURL_COMPANY->id() : null;

        } elseif (strpos($data['uri'], '/1/lambda/') !== false) {
            global $_LAMBDA_MODULE;
            $data['module'] = self::MODULE['LAMBDA'];
            $data['meta']['lambda_module'] = $_LAMBDA_MODULE ?? null;

        } elseif (strpos($data['uri'], '/1/admin-') !== false) {
            $data['module'] = self::MODULE['ADMIN'];
        }

        if (!empty($_LOGGER_META_JOB)) {
            $data['module'] = self::MODULE['JOB'];
            $data['meta']['job'] = $_LOGGER_META_JOB;
        }

        if (!empty($_LOGGER_META_MAIL)) {
            $data['module'] = self::MODULE['MAIL'];
            $data['meta']['mail'] = $_LOGGER_META_MAIL;
        }

        if (empty($data['meta']['stackTrace'])) {
            $e = new Exception();
            $data['meta']['stackTrace'] = $e->getTraceAsString();
        }

        if ($severity !== self::SEVERITY['FATAL_ERROR']) {
            unset($data['meta']['stackTrace']);
        }

        if ($severity === self::SEVERITY['FATAL_ERROR']) {
            $data['meta']['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? null;
        }

        $data = self::ProcessLogData($data);
        $json = json_encode($data, JSON_UNESCAPED_SLASHES);
        return error_log($json);
    }

    public static function Init(): void
    {
        self::ResetStartTimer();
        self::$reservedMemory = str_repeat('x', 32768);
        set_error_handler('Logger::HandleError');
        set_exception_handler('Logger::HandleException');
        register_shutdown_function('Logger::HandleShutdown');
    }

    public static function HandleError(int $level, string $message, string $file, int $line): bool
    {
        $fatalErrors = [
            E_COMPILE_ERROR,
            E_CORE_ERROR,
            E_ERROR,
            E_PARSE,
            E_RECOVERABLE_ERROR,
        ];

        $e = new ErrorException($message, 0, $level, $file, $line);
        if (in_array($level, $fatalErrors)) {
            throw $e;
        }

        self::Log($message, self::SEVERITY['WARNING_ERROR'], [
            'level' => $level,
            'file' => $file,
            'line' => $line,
            'stackTrace' => $e->getTraceAsString(),
        ]);

        return true;
    }

    public static function HandleException(Throwable $e, bool $abort = true, array $meta = []): void
    {
        if (is_subclass_of(get_class($e), 'TeleskopeException')) {
            // Prevent any exception to be thrown from the custom exception handler
            try {
                TeleskopeException::CustomExceptionHandler($e);
            } catch (Throwable $th) {
                self::LogException($th);
            }
        }

        self::Log($e->getMessage(), self::SEVERITY['FATAL_ERROR'], $meta + [
            'exception_class' => $e::class,
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'stackTrace' => $e->getTraceAsString(),
        ]);

        if ($abort) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            exit(1);
        }
    }

    public static function LogException(Throwable $e, array $meta = []): void
    {
        self::HandleException($e, false, $meta);
    }

    public static function LogDebug(string $what, array $meta = []) : bool
    {
        return self::Log($what, Logger::SEVERITY['DEBUG'], $meta);
    }

    public static function LogInfo(string $what, array $meta = []) : bool
    {
        return self::Log($what, Logger::SEVERITY['INFO'], $meta);
    }

    public static function HandleShutdown()
    {
        global $_AUDIT_META;
        self::$reservedMemory = null;

        TmpFileUtils::CleanupTemporaryFiles();

        $_AUDIT_META['http_response_code'] = http_response_code();
        $_AUDIT_META['peak_mem_bytes'] = memory_get_peak_usage();
        $_AUDIT_META['curr_mem_bytes'] =  memory_get_usage();
        $_AUDIT_META['duration'] = floor(microtime(true) * 1000) - self::$start_timestamp;

        self::AuditLog('shutdown', $_AUDIT_META);

        $error = error_get_last();
        if (!is_null($error)) {
            self::HandleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    private static function ProcessLogData(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            if (!is_string($value)) {
                return;
            }

            $value = str_replace(['"', "\r\n", "\n", "\r", '\\'], ['', '', '', '', '~'], $value);

            if (strlen($value) > self::MAX_FIELD_LENGTH) {
                $value = substr($value, 0, self::MAX_FIELD_LENGTH - 3) . '...';
            }
        });

        return $data;
    }

    private static function ResetStartTimer(): void
    {
        self::$start_timestamp = floor(microtime(true) * 1000);
    }

    public static function AuditLog(string $message, array $audit_data): bool
    {
        return self::Log($message, self::SEVERITY['AUDIT'], $audit_data);
    }
}
