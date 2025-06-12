<?php

class Session
{
    private static Self $obj;

    private $zone_id;

    public static function GetInstance(): self
    {
        if (isset(self::$obj)) {
            return self::$obj;
        }

        return self::$obj = new Self();
    }

    public function __get(string $name)
    {
        global $_ZONE;

        switch ($name) {
            case 'zoneid':
                // We need to fully test the application without dependency on session zoneid value as it can mask errors.
                //return $_ZONE?->id() ?? $this->zone_id;
                return $_ZONE?->id() ?? 0;

            case 'csrf':
                $zid = $this->zoneid;
                if (!isset($_SESSION['zone_session'][$zid]['csrf'])) {
                    $csrf_data = $zid . '_u_' . time();
                    $this->csrf = hash_hmac('sha256', $csrf_data, bin2hex(random_bytes(32)));
                }
                return $_SESSION['zone_session'][$zid]['csrf'];

            case 'budget_year':
                $zid = $this->zoneid;
                return $_SESSION['zone_session'][$zid]['budget_year'] ?? 0;

            case 'login_disclaimer_shown':
                $zid = $this->zoneid;
                return $_SESSION['zone_session'][$zid]['login_disclaimer_shown'] ?? 0;

            case 'login_survey_checked':
                $zid = $this->zoneid;
                return $_SESSION['zone_session'][$zid]['login_survey_checked'] ?? 0;

            default:
                return $_SESSION[$name] ?? null;
        }
    }

    public function __set(string $name, $value): void
    {
        switch ($name) {
            case 'zoneid':
                $this->zone_id = $value;
                return;

            case 'csrf':
                $zid = intval($this->zoneid);
                $_SESSION['zone_session'][$zid]['csrf'] = $value;
                return;

            case 'budget_year':
                $zid = intval($this->zoneid);
                $_SESSION['zone_session'][$zid]['budget_year'] = intval($value);
                return;
            
            case 'login_disclaimer_shown':
                $zid = intval($this->zoneid);
                $_SESSION['zone_session'][$zid]['login_disclaimer_shown'] = intval($value);
                return;

            case 'login_survey_checked':
                $zid = intval($this->zoneid);
                $_SESSION['zone_session'][$zid]['login_survey_checked'] = intval($value);
                return;

            default:
                $_SESSION[$name] = $value;
        }
    }

    public function __isset(string $name): bool
    {
        switch ($name) {
            case 'zoneid':
                return isset($this->zone_id);

            case 'csrf':
                $zid = intval($this->zoneid);
                return isset($_SESSION['zone_session'][$zid]['csrf']);

            case 'budget_year':
                $zid = intval($this->zoneid);
                return isset($_SESSION['zone_session'][$zid]['budget_year']);

            case 'login_disclaimer_shown':
                $zid = intval($this->zoneid);
                return isset($_SESSION['zone_session'][$zid]['login_disclaimer_shown']);

            case 'login_survey_checked':
                $zid = intval($this->zoneid);
                return isset($_SESSION['zone_session'][$zid]['login_survey_checked']);

            default:
                return isset($_SESSION[$name]);
        }
    }

    public function __unset(string $name): void
    {
        switch ($name) {
            case 'zoneid':
                unset($this->zone_id);
                return;

            case 'csrf':
                $zid = intval($this->zoneid);
                unset($_SESSION['zone_session'][$zid]['csrf']);
                return;

            case 'budget_year':
                $zid = intval($this->zoneid);
                unset($_SESSION['zone_session'][$zid]['budget_year']);
                return;

            case 'login_disclaimer_shown':
                $zid = intval($this->zoneid);
                unset($_SESSION['zone_session'][$zid]['login_disclaimer_shown']);
                return;

            case 'login_survey_checked':
                $zid = intval($this->zoneid);
                unset($_SESSION['zone_session'][$zid]['login_survey_checked']);
                return;

            default:
                unset($_SESSION[$name]);
        }
    }
}
