<?php

class Sanitizer
{
    /**
     * For user firstname, lastname, sanitizing.
     * @param string $name
     * @return array|string|string[]|null
     */
    public static function SanitizePersonName(string $name)
    {
        return trim(preg_replace('/[<>&"]/u', "", $name));
    }

    public static function SanitizePersonPronouns(string $val)
    {
        return trim(preg_replace('/[<>&"]/u', "", $val));
    }

    public static function SanitizeGroupName(string $name)
    {
        return trim(preg_replace("/[^\w+_,:@&' \-\/\.\(\)]/u", "", $name));
    }
    
    public static function SanitizeRoleName(string $name){ 
        return trim(preg_replace("/[^\w \-]/u", "", $name));
    }

    public static function SanitizeTouchPointTitle(string $name){ 
        return trim(preg_replace("/[^\w #&-.()'\"\[\]_]/u", "", $name));
    }

    public static function SanitizeActionItemTitle(string $name){ 
        return trim(preg_replace("/[^\w #&-.()'\"\[\]_]/u", "", $name));
    }
    
    public static function SanitizeGenericLabel(string $label)
    {
        return trim(preg_replace('/[<>&"]/u', "", $label));
    }

    public static function SanitizeFilename(string $filename)
    {
        return trim(preg_replace('/[<>&"\'=]/u', "_", $filename));
    }

    /**
     * Cleans the input string to be a valid color and outputs the value as RGB (default) or HEX
     * This method works for both hex and rgb variants and it aware of alpha channels
     * @param string $input
     * @param bool $hexOutput
     * @return string
     */
    public static function SanitizeColor(string $input, bool $hexOutput = false) : string
    {
        $iRed   = 0;
        $iGreen = 0;
        $iBlue  = 0;
        $iAlpha  = 0;
        $input = strtr($input,array(' '=>'', '_'=>''));
        if (strpos($input,'#') === 0) {
            $sRegex = '/#(\w\w)(\w\w)(\w\w)(\w\w)?/i';
            preg_match($sRegex, $input, $matches);
            $iRed   = hexdec($matches[1]) % 256;
            $iGreen = hexdec($matches[2]) % 256;
            $iBlue  = hexdec($matches[3]) % 256;
            $iAlpha  = hexdec($matches[4] ?? 0) % 256;

        } elseif (strpos($input,'r') === 0) {
            $sRegex = '/rgba?\((\d+),(\d+),(\d+),?(\d+)?\)/i';
            preg_match($sRegex, $input, $matches);
            $iRed   = intval($matches[1]) % 256;
            $iGreen = intval($matches[2]) % 256;
            $iBlue  = intval($matches[3]) % 256;
            $iAlpha  = intval($matches[4] ?? 0) % 256;
        }

        if ($hexOutput) {
            $output = '#' . sprintf('%02s', dechex($iRed)) . sprintf('%02s', dechex($iGreen)) . sprintf('%02s', dechex($iBlue)) . ($iAlpha ? sprintf('%02s', dechex($iAlpha)) : '');
        } else {
            $output = 'rgb'.($iAlpha ? 'a' : ''). '('. $iRed . ',' . $iGreen . ',' . $iBlue . ($iAlpha ? ','.$iAlpha : '').')';
        }
        return $output;
    }

    /**
     * This method filters a provided CSV string to keep only integer values,
     * e.g. if input = "2,-1,44a3,3.5", then output = "2,-1,3"
     * @param string $input a comma seperated list of integers
     * @return string sanitized version
     */
    public static function SanitizeIntegerCSV (string $input) : string
    {
        return implode(',', array_map('intval',array_filter(explode(',', $input),'is_numeric')));
    }

    /**
     * This method filters a provided array of values to return only integer values
     * e.g. if input = [2,-1,'44a3','3.5'], then output = [2,-1,3]
     * @param array $input an
     * @return array sanitized version
     */
    public static function SanitizeIntegerArray (array $input) : array
    {
        return array_map('intval',array_filter($input,'is_numeric'));
    }

    /**
     * Validates if the datetime is a valid datetime and returns Y-m-d H:i:s format. All checks are done as if the
     * datetime was UTC.
     * @param string $input
     * @return string empty string if date is not valid
     */
    public static function SanitizeUTCDatetime (string $input, string $format="Y-m-d H:i:s") : string
    {
        try {
            $date = new DateTime($input, new DateTimeZone('UTC'));
            if ($date->format($format) == $input) {
                return $input;
            }
        } catch (Exception $e) {
        }
        return '';
    }

    /**
     * For redirect url sanitizing: parse hostname and check the right side of it.
     * @param string $rurl
     * @return string
     */
    public static function SanitizeRedirectUrl(string $rurl)
    {
        global $_COMPANY;
        $parsed_array = parse_url($rurl);
        if (isset($parsed_array["host"]) && Url::IsValidTeleskopeDomain($parsed_array["host"])) {
            return $rurl;
        }
        return '';
    }

    /**
     * Returns input $email if provided value is safe email otherwise an empty string is returned. This method just
     * checks if the provided email is in the correct format. It differs from FILTER_VALIDATE_EMAIL as this method
     * is more restrictive to allow only simple forms of email vs FILTER_VALIDATE_EMAIL which is too permissive due
     * to RFC compliance.
     * @param string $email
     * @return string
     */
    public static function SanitizeEmail (string $email)
    {
        // Note we do not want to use FILTER_VALIDATE_EMAIL filter_var as it is too permissive;
        if (preg_match('/^[a-zA-Z0-9._\-\+]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/u', $email) === 1)
            return $email;

        return '';
    }

    /**
     * Removes all non alpha numeric characters from the taxid.
     */
    public static function SanitizeTaxId (string $taxid)
    {
        return preg_replace('/[^A-Za-z0-9]/','', $taxid);
    }

    /**
     * This is a very sensitive function. Do not make changes. This function removes the html script tags
     * @param string $input
     * @param $replace_with
     * @return string
     */
    public static function RemoveHTMLScriptTags (string $input, $replace_with = '')
    {
         return preg_replace('@<script\b[^>]*>\X*?</script\b[^>]*>@i',$replace_with, $input) ?? '';
    }
}