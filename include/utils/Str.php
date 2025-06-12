<?php

class Str
{
    // Define common connector words
    const STOP_WORDS = array(
        'a','about','accordingly','additionally','after','also','although','an','and','as','at',
        'because','before','besides','but','by','consequently','during','even','finally','for','from','furthermore',
        'hence','however','if','in','indeed','into','just',
        'like','likewise','meanwhile','moreover','nevertheless','nor','not','none',
        'of','on','once','or','otherwise','secondly','similarly','since','so','subsequently',
        'than','the','then','that','therefore','though','to','unless','until','when','where','whereas','while','with','yet'
    );

    public static function ConvertSnakeCaseToCamelCase(string $input, $capitalize_first_character = true): string
    {
        $input = strtolower($input);
        $str = str_replace('_', '', ucwords($input, '-'));

        if (!$capitalize_first_character) {
            $str = lcfirst($str);
        }

        return $str;
    }

    public static function Random(int $length): string
    {
        $characters = [
            ...range('a', 'z'),
            ...range(0, 9),
        ];

        $characters_length = count($characters);
        $random_str = '';

        for ($i = 0; $i < $length; $i++) {
            $random_str .= $characters[random_int(0, $characters_length - 1)];
        }

        return $random_str;
    }

    public static function IsEmptyHTML (string $html) : bool
    {
        // makes '<p></p>' or '<p><br></p>' or other combinations empty.
        // Allow figure and img tags as the content might be just a picture
        return empty(preg_replace('/[[:space:]]+/', '', strip_tags(str_replace('&nbsp;','',$html),['figure','img','hr'])));
    }

    /**
     * @param string $input
     * @param int $start
     * @param int $length
     * @param string $mask
     * @return string
     */
    public static function GenerateMask (string $input, int $start = 3, int $length = 15, string $mask = "*") : string
    {
        $masked = substr($input, 0, $start);
        for ($i = $start; $i < $start + $length; $i++) {
            $masked .= $mask;
        }
        $masked .= substr($input, $start + $length);
        return $masked;
    }

    /**
     * Looks for the given character and returns the string after the matching character,
     * e.g. for arguments 'Washington Post' and ' ' it will return 'Post'
     */
    public static function GetStringAfterCharacter(string $string, string $char) : string
    {
        $pos = strpos($string, $char);
        if ($pos === false) {
        return ''; // Character not found
        }
        return substr($string, $pos + 1);
    }


    /**
     * Looks for the given character and returns the string after the matching character,
     * e.g. for arguments 'Washington Post' and ' ' it will return 'Washington'
     */
    public static function GetStringBeforeCharacter(string $string, string $char) : string
    {
        $pos = strpos($string, $char);
        if ($pos === false) {
            return ''; // Character not found
        }
        return substr($string, 0, $pos);
    }

    /**
     * Returns an array. If input is null or empty i.e. '' then empty array is returned. This is improvement over
     * explode which will by default return array with empty value.
     * @param string|null $s
     * @return array
     */
    public static function ConvertCSVToArray (?string $s) : array
    {
        return ($s && $s != '') ? explode(',', $s) : [];
    }

    /**
     * A regular expression based function to remove all forms of new lines.
     * @param $string
     * @return string on error input string is returned.
     */
    public static function RemoveNewLines($string): string
    {
        return preg_replace('/\R+/', '', $string) ?? $string;
    }

    public static function ValidatePassword(string $password): bool
    {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\-\ \!\@\#\$\%\^\&\_])[a-zA-Z\d\-\ \!\@\#\$\%\^\&\_]{8,}$/', $password);
    }

    public static function WordMatchPercentageOfWordsInText(string $words, string $text) : int
    {
        // Convert strings to lowercase for case-insensitive comparison
        $words_arr = self::TextToWords($words);
        if (!$words_arr)
            return 0;

        $text_arr = self::TextToWords($text);
        if (!$text_arr)
            return 0;

        // Find the intersection of words (common words)
        $common_words_arr = array_intersect($words_arr, $text_arr);

        // Calculate match percentage
        return intval((count($common_words_arr) / count($words_arr)) * 100);
    }

    public static function TextToWords($text) : array
    {
        // Convert text to lowercase and remove punctuation and split text into words with at least 3 characters
        preg_match_all(
            '/\b[\w]{2,}|c\b/u',
            str_replace(['-','_','&','#','+'],'', strtolower($text)),
            $words
        );

        // Remove stop words
        return isset($words[0]) ? array_diff($words[0], self::STOP_WORDS) : array();
    }

    public static function validatePhoneNumber($phone) {
        // Simple regex for phone numbers that can include +, space, dash, and numbers
        $pattern = "/^\+?[0-9\s\-]+$/";
        
        if (preg_match($pattern, $phone)) {
            return true; // valid phone number
        } else {
            return false; // invalid phone number
        }
    }
}
