<?php

/**
 * Utity function for IP Addresses
 */
class IP
{

    public static function GetRemoteIPAddr()
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Returns true if IP is in one of many CIDR ranges provided
     * @param string $IP
     * @param array $CIDRS
     * @return bool
     */
    public static function InCIDRList(string $IP, array $CIDRS)
    {
        foreach ($CIDRS as $CIDR) {
            list ($net, $mask) = explode('/', $CIDR);
            $ip_net = ip2long($net);
            $ip_mask = ~((1 << (32 - $mask)) - 1);
            $ip_ip = ip2long($IP);
            if (($ip_ip & $ip_mask) == ($ip_net & $ip_mask)) {
                return true;
            }
        }
        Logger::Log('IP check failed to match ' . $IP . ' in ' . implode(',', $CIDRS));
        return false;
    }
}
