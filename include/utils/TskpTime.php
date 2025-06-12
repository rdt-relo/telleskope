<?php

class TskpTime
{

    // Map legacy timezones to current valid IANA list
    public const OUTDATED_TIMEZONE_MAP = array(
        // US Timezones (legacy and shorthand)
        'US/Eastern'            => 'America/New_York',
        'US/Central'            => 'America/Chicago',
        'US/Mountain'           => 'America/Denver',
        'US/Pacific'            => 'America/Los_Angeles',
        'US/Arizona'            => 'America/Phoenix',
        'US/Alaska'             => 'America/Anchorage',
        'US/Hawaii'             => 'Pacific/Honolulu',
        'US/Samoa'              => 'Pacific/Pago_Pago',
        'US/Aleutian'           => 'America/Adak',
        'US/East-Indiana'       => 'America/Indiana/Indianapolis',

        // Deprecated or ambiguous America zones
        'America/Indianapolis'  => 'America/Indiana/Indianapolis',
        'America/Knox_IN'       => 'America/Indiana/Knox',
        'America/Louisville'    => 'America/Kentucky/Louisville',
        'America/Fort_Wayne'    => 'America/Indiana/Indianapolis',
        'America/Atka'          => 'America/Adak',
        'America/Ciudad_Juarez' => 'America/Ojinaga', #see 4174

        // GMT offsets as timezones
        'Etc/GMT+12'            => 'Pacific/Kwajalein',
        'Etc/GMT+11'            => 'Pacific/Midway',
        'Etc/GMT+10'            => 'Pacific/Honolulu',
        'Etc/GMT+9'             => 'America/Anchorage',
        'Etc/GMT+8'             => 'America/Los_Angeles',
        'Etc/GMT+7'             => 'America/Denver',
        'Etc/GMT+6'             => 'America/Chicago',
        'Etc/GMT+5'             => 'America/New_York',
        'Etc/GMT+4'             => 'America/Halifax',
        'Etc/GMT+3'             => 'America/Argentina/Buenos_Aires',
        'Etc/GMT+2'             => 'Atlantic/South_Georgia',
        'Etc/GMT+1'             => 'Atlantic/Azores',
        'Etc/GMT'               => 'Etc/UTC',
        'Etc/GMT-1'             => 'Europe/London',
        'Etc/GMT-2'             => 'Europe/Berlin',
        'Etc/GMT-3'             => 'Europe/Moscow',
        'Etc/GMT-4'             => 'Asia/Dubai',
        'Etc/GMT-5'             => 'Asia/Karachi',
        'Etc/GMT-6'             => 'Asia/Dhaka',
        'Etc/GMT-7'             => 'Asia/Jakarta',
        'Etc/GMT-8'             => 'Asia/Shanghai',
        'Etc/GMT-9'             => 'Asia/Tokyo',
        'Etc/GMT-10'            => 'Australia/Sydney',
        'Etc/GMT-11'            => 'Pacific/Noumea',
        'Etc/GMT-12'            => 'Pacific/Auckland',

        // Europe legacy or alternate names
        'Europe/Nicosia'        => 'Asia/Nicosia',
        'Europe/Kyiv'           => 'Europe/Kiev',
        'Europe/Tiraspol'       => 'Europe/Chisinau',
        'WET'                   => 'Europe/Lisbon',
        'CET'                   => 'Europe/Paris',
        'MET'                   => 'Europe/Amsterdam',
        'EET'                   => 'Europe/Helsinki',

        // Asia
        'Asia/Katmandu'         => 'Asia/Kathmandu',
        'Asia/Calcutta'         => 'Asia/Kolkata',
        'Asia/Chongqing'        => 'Asia/Shanghai',
        'Asia/Harbin'           => 'Asia/Shanghai',
        'Asia/Kashgar'          => 'Asia/Urumqi',
        'Asia/Ujung_Pandang'    => 'Asia/Makassar',
        'Asia/Saigon'           => 'Asia/Ho_Chi_Minh',

        // Africa
        'Africa/Asmera'         => 'Africa/Asmara',
        'Africa/Timbuktu'       => 'Africa/Bamako',

        // Australia (aliases and changes)
        'Australia/ACT'         => 'Australia/Sydney',
        'Australia/NSW'         => 'Australia/Sydney',
        'Australia/North'       => 'Australia/Darwin',
        'Australia/Queensland'  => 'Australia/Brisbane',
        'Australia/South'       => 'Australia/Adelaide',
        'Australia/Tasmania'    => 'Australia/Hobart',
        'Australia/Victoria'    => 'Australia/Melbourne',
        'Australia/West'        => 'Australia/Perth',
        'Australia/Yancowinna'  => 'Australia/Broken_Hill',

        // Pacific (common alternates)
        'Pacific/Yap'           => 'Pacific/Chuuk',
        'Pacific/Truk'          => 'Pacific/Chuuk',
        'Pacific/Ponape'        => 'Pacific/Pohnpei',
        'Pacific/Samoa'         => 'Pacific/Pago_Pago',

        // UTC shorthand
        'UTC'                   => 'Etc/UTC',
        'GMT'                   => 'Etc/UTC',
    );
}
