<?php
require_once __DIR__ .'/init.php';
mysqli_report(MYSQLI_REPORT_OFF); // Default to pre PHP 8.x behavior

$dbrw_conn = null;
$dbro_conn = null;

/**
 * Returns a Read Connection on the main database
 * @return mysqli
 */
function GlobalGetDBConnection() : mysqli
{
    global $dbrw_conn;
    if (!$dbrw_conn) {
        $dbrw_conn = mysqli_connect(HOST,DBUSER,DBPASS) or die(mysqli_connect_error());
        mysqli_select_db($dbrw_conn,DB) or die(mysqli_connect_error());
        $ctx =  isset($_SESSION) ? (($_SESSION['companyid'] ?? '') . '|' . ($_SESSION['context_userid'] ?? '')) : '';
        mysqli_query($dbrw_conn, "SET SESSION sql_mode = ''; #W C= ".$ctx);
    }
    return $dbrw_conn;
}

/**
 * Returns a Read Only connection on the main database
 * @return mysqli
 */
function GlobalGetDBROConnection() : mysqli
{
    global $dbro_conn;
    if (!$dbro_conn) {
        $dbro_conn = mysqli_connect(DB_RO_HOST, DBUSER, DBPASS) or die(mysqli_connect_error());
        mysqli_select_db($dbro_conn, DB) or die(mysqli_connect_error());
        $ctx =  isset($_SESSION) ? (($_SESSION['companyid'] ?? '') . '|' . ($_SESSION['context_userid'] ?? '')) : '';
        mysqli_query($dbro_conn, "SET SESSION sql_mode = ''; #R C= ".$ctx);
    }
    return $dbro_conn;
}

class Hems{ // Class for common fucntions
	private $nowTimeFromDb = '';

	// Function for cleanup data
	public function cleanInputs($data) {
        if (is_array($data)) {
            $clean_input = array();
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->cleanInputs($v);
            }
        } else {
			$clean_input = trim($data);
            //$clean_input = trim(strip_tags($clean_input));
			//$clean_input = addslashes($clean_input);
        }
        return $clean_input;
    }//end

	//Function for Fetch data from database
	public function get($select){
        $dbrw = GlobalGetDBConnection();
		$query = mysqli_query($dbrw,"/*qc=on*/".$select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR). " *Internal Error: DBR-01*"));
		
        $results = array();
        if (!is_bool($query)) { // Query result can be bool in which case we cannot use mysqli function
            while (@$rows = mysqli_fetch_assoc($query)) {
                $results[] = $rows;
            }
        }
		return ($results);

	}// end

    //Function for Fetch data from database from Read Only instance
    public function ro_get($select){
	    $dbro = GlobalGetDBROConnection();
        $query = mysqli_query($dbro,"/*qc=on*/".$select) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbro), 'sql_stmt'=> $select]) and die(header(HTTP_INTERNAL_SERVER_ERROR). " *Internal Error: DBRO-01*"));
	
        $results = array();
        if (!is_bool($query)) { // Query result can be bool in which case we cannot use mysqli function
            while (@$rows = mysqli_fetch_assoc($query)) {
                $results[] = $rows;
            }
        }

        return ($results);
    }// end

	public function getPS (string $sql,string $types='',...$argv)
	{
        $dbrw = GlobalGetDBROConnection();
		$stmt = mysqli_prepare($dbrw, "/*qc=on*/".$sql) or (Logger::Log('Fatal Error Preparing SQL:', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $sql]) and die(header(HTTP_INTERNAL_SERVER_ERROR). " *Internal Error: DBRP-01*"));		

		$bind_params = array();
		$argc = count($argv);
		if ($argc) {
			// First escape the  HTML code from strings
			$type_array =  str_split($types);
			for ($c = 0; $c < $argc; $c++) {
				if ($type_array[$c] === 's') {
					$argv[$c] = strip_tags($argv[$c]);
				} elseif ($type_array[$c] === 'm') {
					$type_array[$c] = 's';
					$allowed_tags = /** @lang text */
						"<p><strong><img><ol><ul><li><a><hr><em><s><blockquote><span><u><del>";
					$argv[$c] = preg_replace( '/[^[:print:]]/', ' ',strip_tags($argv[$c],$allowed_tags)); //remove non printable characters that CKEDITOR sometimes adds

				} elseif ($type_array[$c] === 'x') {
					$type_array[$c] = 's';
				}
			}
			$types = implode ('',$type_array);

			// If there are arguments, bind them
			$bind_params[] = $stmt;
			$bind_params[] = $types;

			for ($i = 0; $i < $argc; $i++)
				$bind_params[] = &$argv[$i];

			call_user_func_array('mysqli_stmt_bind_param', $bind_params) or (Logger::Log('Fatal Error Binding SQL:', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $sql]) and die(header(HTTP_INTERNAL_SERVER_ERROR). " *Internal Error: DBRP-02*"));
			
		}

		mysqli_stmt_execute($stmt) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $sql]) and die(header(HTTP_INTERNAL_SERVER_ERROR). " *Internal Error: DBRP-03*"));
		$query = mysqli_stmt_get_result($stmt) or (Logger::Log("Fatal Error getting: {$sql} | server error ".mysqli_error($dbrw)) and die(header(HTTP_INTERNAL_SERVER_ERROR). " *Internal Error: DBRP-03*"));

        $results = array();
        if (!is_bool($query)) { // Query result can be bool in which case we cannot use mysqli function
            while (@$rows = mysqli_fetch_assoc($query)) {
                $results[] = $rows;
            }
        }
		// Close statement
		mysqli_stmt_close($stmt);

		return ($results);
	}// end

	//Code genrate
	public function Codegenerate() {
		$charPool = "1234567890";
		$pass = array();
		$length = strlen($charPool) -1 ;
		for($i=0;$i<5;$i++){
			$n = rand(0, $length);
			$pass[] = $charPool[$n];
		}
		$final = rand(0,9).implode($pass);
		return $final;
	}// end

	//Code genrate
	public function codegenerate2() {
		$charPool1 = "1234567893";
		$charPool2 = "abcdefghwdklmnapqrstuvwxyz";
		$charPool4 = "ABCDEFGHZUKLMNAPQRSTUVWXYZ";
		$charPool3 = "$!@#-";
		$pass = array();
		$pass[] = $charPool4[rand(0,25)];
		$pass[] = $charPool4[rand(0,25)];
		$pass[] = $charPool2[rand(0,25)];
		$pass[] = $charPool3[rand(0,4)];
		$pass[] = $charPool2[rand(0,25)];
		$pass[] = $charPool2[rand(0,25)];
		$pass[] = $charPool1[rand(0,9)];
		$pass[] = $charPool2[rand(0,25)];
		$final = implode($pass);
		return $final;
	}// end

	public function getExtension($str){
        return get_safe_extension($str);
	}

	//Function confert time to second // 24 hrs format
	function convertTimetoSec($str_time){
		$str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", $str_time);
		sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);
		$time_seconds = $hours * 3600 + $minutes * 60 + $seconds;
		return $time_seconds;
	}



	//Function confert time to second
	function convertSecondsToTime($seconds){
		$hours = floor($seconds / 3600);
		$mins = floor($seconds / 60 % 60);
		$secs = floor($seconds % 60);
		//$timeFormat = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
		$timeFormat = sprintf('%02d:%02d', $hours, $mins);
		return $timeFormat;
	}

	// my Event Join Status
	public function myEventJoinStatus($userid,$eventid){
		$check = $this->ro_get("SELECT eventid,joinstatus FROM `eventjoiners` WHERE `eventid`='".$eventid."' and `userid`='".$userid."' and `joinstatus` in (1,2,3,11,12,21)");

		if(count($check)>0){
			return $check[0]['joinstatus'];
		}else{
			//return '3';
			return '0';
		}
	}

	// my Joined evets
	public function myJoinStatus($userid){
		$rsvpTypes = Event::RSVP_TYPE;
		$check = $this->ro_get("SELECT eventid FROM `eventjoiners` WHERE userid='".$userid."' and `joinstatus` IN({$rsvpTypes['RSVP_YES']},{$rsvpTypes['RSVP_INPERSON_YES']},{$rsvpTypes['RSVP_ONLINE_YES']} )");
		if(count($check)>0){
			return implode(',',array_column($check,'eventid'));
		}else{
			return '0';
		}

	}

	// Check required fields
	public function checkRequired($array){
		$requried = "";
		foreach($array as $key => $value) {
            $value = trim($value??'');
			if ($value==''){
				$requried .= $key.', ';
			}
		}
		if($requried!=""){
			return rtrim($requried,', ');
		}else{
			return false;
		}
	}

	// Get lat long // by zip or address
	public function getLatLong($address){
		$url = "https://maps.googleapis.com/maps/api/geocode/json?key=".GOOGLE_MAPS_API_KEY."&address=".urlencode($address)."&sensor=false";
		$result = json_decode(get_curl($url),true);
//		$result1[]=@$result['results'][0];
//		$result2[]=@$result1[0]['geometry'];
//		$result3[]=@$result2[0]['location'];
//		return @$result3[0];
//      //get lat lng by $return['lat'],$return['lng']
        if ($result['status'] == "OK" && isset($result['results'][0]['geometry']['location'])) {
            return $result['results'][0]['geometry']['location'];
        }
        return array('lat' => '', 'lng' => '');
	}

	function timeago($date) {
		if(is_numeric($date)){
			$timestamp = $date;
			$currentTime = time();
		}else{
			$timestamp = strtotime($date.'UTC');
			if ($this->nowTimeFromDb === '') {
				$this->nowTimeFromDb = strtotime($this->ro_get("SELECT NOW() nowTime")[0]['nowTime'] . 'UTC');
			}
			$currentTime = $this->nowTimeFromDb;

		}
		$strTime = array(gettext("sec"), gettext("min"), gettext("hour"), gettext("day"), gettext("month"), gettext("year"));
		$strTimes = array(gettext("secs"), gettext("mins"), gettext("hours"), gettext("days"), gettext("months"), gettext("years"));
		$length = array("60","60","24","30","12","10");

		if($currentTime >= $timestamp) {
			$diff     = $currentTime - $timestamp;
			for($i = 0; $diff >= $length[$i] && $i < count($length)-1; $i++) {
			$diff = $diff / $length[$i];
			}

			$diff = round($diff);

			$tms=$strTimes[$i];
			if($diff == 1) $tms=$strTime[$i];

			return $diff . " " . $tms;
	   }
	}

	//************** ADMIN PANEL*************************//

	// UTC TO Local
	public function covertUTCtoLocal($format,$datetime,$timezone,$locale = null,$showDefaultPattern = false){
		if(empty($timezone)){
			$timezone	=	"UTC";
		}
		$date = new DateTime($datetime, new DateTimeZone('UTC'));
		$date->setTimezone(new DateTimeZone($timezone));
		$customPattern = $format.' T(P)';
		if (null !==$locale){
			if ($showDefaultPattern){
				$customPattern = null;
			}
			return self::GetLocalizedDateTime($date->format("Y-m-d H:i:s"),null,null,$timezone,$locale,$customPattern);
		} else {
			return $date->format($customPattern);
		}
	}
	// UTC to Local Advanced
	public function covertUTCtoLocalAdvance($format,$extra,$datetime,$timezone,$locale = null,$showDefaultPattern = false){
        if (empty($datetime)) {
            return '';
        }
		if(empty($timezone)){
			$timezone	=	"UTC";
		}
		$date = new DateTime($datetime, new DateTimeZone('UTC'));
		$date->setTimezone(new DateTimeZone($timezone));
		$customPattern = $format.$extra;
		if (null !==$locale){
			if ($showDefaultPattern){
				$customPattern = null;
			}
			return self::GetLocalizedDateTime($date->format("Y-m-d H:i:s"),null,null,$timezone,$locale,$customPattern);
		} else {
			return $date->format($customPattern);
		}
	}

    /**
     * @param $format
     * @param $datetime
     * @param $timezone
     * @return string
     * @throws Exception
     */
	public function covertLocaltoUTC($format,$datetime,$timezone): string
    {
        try {
            $date = new DateTime($datetime, new DateTimeZone($timezone));
            $date->setTimezone(new DateTimeZone('UTC'));
            return $date->format($format);
        } catch (Exception $e) {
            global $_USER;
            $meta = [];
            $meta['session_timezone'] = $_SESSION['timezone'];
            $meta['profile_timezone'] = $_USER?->val('timezone');
            Logger::Log('Caught exception ' . $e->getMessage(), Logger::SEVERITY['WARNING_ERROR'], $meta);
        }
        return '';
	}

	### Round of time format
	public function roundTrimTimeDiff($startdatetime,$enddatetime){
		$start  = new DateTime($startdatetime);
		$end  	= new DateTime($enddatetime);
		$interval = $end->diff($start);
		$format =  explode(":",$interval->format('%d:%H:%i'));
		$h = ($format[0] === '1') ? '24' : ltrim($format[1],'0');
		$m = ($format[0] === '1') ? '0' : ltrim($format[2],'0');
		return array($h, $m);
	}

	public static function GetLocalizedDateTime($datetime, $dateFormat = null, $timeFormat = null, $timezone = null, $locale = null, $customPattern = null){
		$dt = null;
		$tz = null;
		if (null !== $timezone) {
			$tz = is_string($timezone) ? new \DateTimeZone($timezone) : $timezone;
		}
		if ($datetime instanceof \DateTimeImmutable) {
			$dt = new \DateTime($datetime->format('Y-m-d H:i:s'), $tz);
		} elseif (!$datetime instanceof \DateTime) {
			$dt = new \DateTime($datetime, $tz);
		} else {
			$dt = clone $datetime;
		}
		if (null === $tz) {
			$tz = $dt->getTimezone();
		}
		if (null === $locale) {
			$locale = \Locale::getDefault();
		}
		if (null === $dateFormat) {
			$dateFormat = \IntlDateFormatter::LONG;
		}
		if (null === $timeFormat) {
			$timeFormat = \IntlDateFormatter::FULL;
		}
		if (null !== $customPattern){
			$customPattern = self::ConvertDatetimePatternToICUPattern($customPattern);
		}
		$currentLocale = \Locale::getDefault();
		\Locale::setDefault($locale);
		$formatter = new \IntlDateFormatter($locale, $dateFormat, $timeFormat, $tz);
		if (null !== $customPattern){
			$formatter->setPattern($customPattern);
		}
		$result = $formatter->format($dt);
		\Locale::setDefault($currentLocale);
		return $result;
	}

	public static function ConvertDatetimePatternToICUPattern($patterns){
		$patterns = (preg_split(' /([-\s:\s,\s(\s)])/', $patterns,-1,PREG_SPLIT_DELIM_CAPTURE));
		$icuFormatArray = array();
		foreach ($patterns as $index => $val) {
			switch ($val) {
				case 'Y':
					$icuFormatArray[$val] = 'y';
					break;
				case 'y':
					$icuFormatArray[$val] = 'yy';
					break;
				case 'M':
					$icuFormatArray[$val] = 'MMM';
					break;
				case 'm':
					$icuFormatArray[$val] = 'MM';
					break;
				case 'F':
					$icuFormatArray[$val] = 'MMMM';
					break;
				case 'D':
					$icuFormatArray[$val] = 'E';
					break;
				case 'd':
					$icuFormatArray[$val] = 'dd';
					break;
				case 'j':
					$icuFormatArray[$val] = 'd';
					break;
				case 'l':
					$icuFormatArray[$val] = 'EEEE';
					break;
				case 'H':
					 $icuFormatArray[$val] = 'HH';
					 break;
				case 'h':
					$icuFormatArray[$val] = 'hh';
					break;
				case 'g':
					$icuFormatArray[$val] = 'hh';
					break;
				case 'i':
					$icuFormatArray[$val] = 'mm';
					break;
				case 's':
					$icuFormatArray[$val] = 'ss';
					break;
				case 'a':
					$icuFormatArray[$val] = 'a';
					break;
				case 'A':
					$icuFormatArray[$val] = 'a';
					break;
				case 'T':
					$icuFormatArray[$val] = 'zzzz';
					break;
				case 'P':
					$icuFormatArray[$val] = 'ZZZZZ';
					break;
			}
		}
		$icuFormatFinalArray = str_replace(array_keys($icuFormatArray), array_values($icuFormatArray), $patterns);
		$icuPatterns = null;
		if (!empty($icuFormatFinalArray)){
			$icuPatterns = implode('',$icuFormatFinalArray);
		}
		return $icuPatterns;
	}

}// End of Class



//-------------Some common functions-----------------------------//
//function for today date
function today(){
	date_default_timezone_set("UTC");
	$date = array();
	$date[0] = date('Y-m-d H:i:s', time());
	$date[1] = strtotime(date('Y-m-d H:i:s', time()));
	return $date;

}//end
// Errer/Success message functions
function getmsg($msg){
	$ID = "";
	if($msg > 0){
		switch($msg){
			case 1:
				$ID = "Email/Password do not match <br />or the account does not exist!";
				break;
			case 2:
				$ID = "Welcome! Sign In successfull!";
				break;
			case 3:
				$ID = "Password changed";
				break;
			case 4:
				$ID = "We emailed you a temporary password. Please check your inbox";
				break;
			case 5:
				$ID = "Account not exist! Please try again.";
				break;
			case 6:
				$ID = "Data added.";
				break;
			case 7:
				$ID = "Data updated.";
				break;
			case 8:
				$ID = "Something went wrong! Please try again.";
				break;
			case 9:
				$ID = "We emailed you a password reset link to your registered email address. Please check your mailbox. This link will expire in 24 hrs.";
				break;
			case 10:
				$ID = "Account does not exist!";
				break;
			case 11:
				$ID = "Company approved.";
				break;
			default:
				$ID = "";
				break;
		}
	}
	return $ID;
}
// Limit
$endLIMIT = 10;

//  function for add suffix numbers

function sufixNumber($number) {
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number%100) <= 13)){
        return $number. 'th';
    }else{
        return $number. $ends[$number % 10];
	}
}

function base64_url_encode($input) {
 return strtr(base64_encode($input), '+/=', '._-');
}

function base64_url_decode($input) {
 return base64_decode(strtr($input, '._-', '+/='));
}

function raw2clean($input, $html=false, $dbescape=true) {
    $dbrw = GlobalGetDBConnection();
	$r = $input;
	if ($html) {
		$allowed_tags = "<p><strong><img><ol><ul><li><a><hr><br><em><s><blockquote><span><u><i><del><figure><figcaption>";
		$r = strip_tags($r,$allowed_tags); //Strip all HTML tags except for the allowed ones.
		//$r = preg_replace( '/[^[:print:]]/', ' ',$r); //remove non printable characters that CKEDITOR sometimes adds
	} else {
		$r = htmlspecialchars($r); //Escape HTML characters like <,>,& etc.
	}
	if ($dbescape)
		$r = mysqli_real_escape_string ($dbrw, $r); //Escape characters that are unsafe for MySQL.

	return $r;
}

function b64_clean_decode($input) {
	$v = base64_decode($input);
	if (is_numeric($v)) {
		return (int)$v;
	} else {
		return -1;
	}
}

function aes_encrypt(string $what, string $secret_key, string $secret_iv, bool $decrypt, bool $add_crc=false, bool $use_aws_kms = false)
{
    global $_COMPANY;

    $stack_trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    if (isset($_COMPANY) && !str_ends_with($stack_trace[0]['file'], 'CompanyEncKey.php')) {
        if ($decrypt) {
	        return CompanyEncKey::Decrypt(...func_get_args());
        }

        if ($use_aws_kms) {
	        return CompanyEncKey::Encrypt($what);
        }
    }

	$output = "";
	// hash
	$key = hash('sha256', $secret_key);
	// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
	$iv = substr(hash('sha256', $secret_iv), 0, 16);

	if($decrypt ){ //encrypt
        $what = str_replace('%3A',':',$what); // Since : can get encoded to %3A
        $what_parts = explode(':',$what);
		$output = openssl_decrypt(base64_url_decode($what_parts[0]), "AES-256-CBC", $key, 0, $iv);
		if (!empty($what_parts[1]) && ($what_parts[1] != crc32($output))) {
		    $output = ""; // The hash did not match
        }
	} else {
		/** @noinspection EncryptionInitializationVectorRandomnessInspection */
		$output = base64_url_encode(openssl_encrypt($what, "AES-256-CBC", $key, 0, $iv));
		if ($add_crc) {
            $output .= ':'. crc32($what);
        }
	}
	return $output;
}

function get_curl($url,$headers=array()){
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url);
	curl_setopt( $ch, CURLOPT_POST, false );
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	$var= curl_exec($ch);
	curl_close($ch);
	return $var;
}

function post_curl($url,$headers,$fields){
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url);
	curl_setopt( $ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	$var= curl_exec($ch);
	curl_close($ch);
    return $var;
}

function delete_curl($url,$headers,$fields){
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt( $ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $var= curl_exec($ch);
	curl_close($ch);
    return $var;
}

function patch_curl($url,$headers,$fields){
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	//curl_setopt( $ch, CURLOPT_POST, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $var= curl_exec($ch);
	curl_close($ch);
    return $var;
}

function generateRandomToken($length = 50) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function verify_recaptcha(): bool
{
	$response = $_REQUEST["g-recaptcha-response"];
	$url = 'https://www.google.com/recaptcha/api/siteverify';
	$data = array(
		'secret' => RECAPTCHA_SECRET_KEY,
		'response' => $response
	);
	$head = array();

	$retVal = json_decode(post_curl($url,$head,$data),true);
	return $retVal['success'];
}

function slugify($string){
	return strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $string), '_'));
}

function teleskope_uuid() {
	$data = random_bytes(16);
	$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
	$data[8] = chr(ord($data[8]) & 0x3f | 0x80);
	return vsprintf('%s%s_%s_%s_%s_%s%s%s', str_split(bin2hex($data), 4)).'_'.dechex(time());
}

function rgb_to_hex( string $rgba ) : string {
	if ( strpos( $rgba, '#' ) === 0 ) {
		return $rgba;
	}
	preg_match( '/^rgba?[\s+]?\([\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?,[\s+]?(\d+)[\s+]?/i', $rgba, $by_color );
	return sprintf( '#%02x%02x%02x', $by_color[1], $by_color[2], $by_color[3] );
}

function sendCommonPushNotification($token,$title,$body,$badge,$section,$tableid,$notificationid,$zoneid, $bearerToken='', $version = 'v0', $firebaseProjectId=''){

	if ($version == 'v1') {
		sendCommonPushNotificationV1($token,$title,$body,$badge,$section,$tableid,$notificationid,$zoneid, $bearerToken,$firebaseProjectId);
	} else {

		$notification = array('title' => $title, 'body' => $body,'content_available'=>true);

		$datakey = array('click_action'=>'FLUTTER_NOTIFICATION_CLICK', 'sound' => 'default', 'badge' => $badge,'section'=>$section,'tableid'=>$tableid,'notificationid'=>$notificationid,'zoneid'=>$zoneid);

		$arrayToSend = array('to' => $token, 'notification' => $notification,'data'=>$datakey, 'priority'=>'high');
		$data = json_encode($arrayToSend);
		//FCM API end-point
		$url = 'https://fcm.googleapis.com/fcm/send';
		//header with content_type api key
		$headers = array(
			'Content-Type:application/json',
			'Authorization:key='.Config::Get('FIREBASE_API_KEY') //api_key in Firebase Console -> Project Settings -> CLOUD MESSAGING -> Server key
		);
		//CURL request to route notification to FCM connection server (provided by Google)
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result = curl_exec($ch);
		if ($result === FALSE) {
			Logger::Log('Notification FCM Fatal Error: ' . curl_error($ch));
		}
		curl_close($ch);
		return array('data'=>$data,'result'=>$result);
	}
}

function sendCommonPushNotificationV1($deviceToken,$title,$body,$badge,$section,$tableid,$notificationid,$zoneid,$bearerToken,$firebaseProjectId){
	//validate
	if (empty($deviceToken)) {
		return;
	}
	//Bearer Token
	if (!$bearerToken){
		Logger::Log('Error sending message: Firebase Bearer Token not found');
		return;
	}
	
	// The notification payload
	$notification = [
		'title' => $title,
		'body' => $body,
	];

	// The data payload
	$data = array('click_action'=>'FLUTTER_NOTIFICATION_CLICK', 'sound' => 'default', 'badge' => (string)$badge,'section'=>(string)$section,'tableid'=>(string)$tableid,'notificationid'=>(string)$notificationid,'zoneid'=>(string)$zoneid);

	// Construct the message
	$message = [
		'message' => [
			'token' => $deviceToken,
			'notification' => $notification,
			'data' => $data,
			"apns" => array(
				"payload" => array(
					"aps" => array(
						"content-available" => 1,
						"alert" => array(
							"title" => $title,
							"body" => $body
						)
					)
				)
			),
			"android" => array(
				"priority" => "high",
				"data" => array(
					"content_available" => "true",
					"message" => $title
				)
			)
		]
	];

	// The API endpoint
	$url = 'https://fcm.googleapis.com/v1/projects/'.$firebaseProjectId.'/messages:send';

	// Set up the CURL request
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Authorization: Bearer ' . $bearerToken,
		'Content-Type: application/json'
	]);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));

	// Execute the request
	$response = curl_exec($ch);

	if ($response === FALSE) {
		die('FCM Send Error: ' . curl_error($ch));
	}
	curl_close($ch);
	// Decode the response
	$responseData = json_decode($response, true);
	// Check if the request was successful
	if (isset($responseData['name'])) {
		return array('data'=>$data,'result'=>$responseData);
	} else {
		// If device token is not valid and got error on sending pushnotification, delete that app staled sassion
		if (!User::CleanUserAppSessionByDeviceToken($deviceToken)){ // Log error only if session not cleared
			Logger::Log('Error sending notification', Logger::SEVERITY['FATAL_ERROR'], $responseData);
		}
		return;
	}
}


function convertAllHrefOfHtmlToBaseurlQueryParameter($apiUrl,$html,$parameters = ''){
	$dom = new DOMDocument();
	$dom->loadHTML($html);
	foreach($dom->getElementsByTagName('a') as $anchor) {
		$link = $anchor->getAttribute('href');
		$link = $apiUrl.'updateHrefEmailLog?parameters='.$parameters.'&url='.urlencode($link);
		$anchor->setAttribute('href', $link);
	}
	return $dom->saveHTML();
}

function convertBytesToReadableSize($size){
	if ($size<1){
		return '0B';
	}
	$base = log($size) / log(1024);
	$suffix = array("B", "KB", "MB", "GB", "TB");
	$f_base = floor($base);
	return round(pow(1024, $base - floor($base)), 1) . ' '. $suffix[$f_base];
}

function getDistanceBetweenTwoLatLong( $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo){
	// convert from degrees to radians
	$theta = $longitudeFrom - $longitudeTo;
	$dist = sin(deg2rad($latitudeFrom)) * sin(deg2rad($latitudeTo)) +  cos(deg2rad($latitudeFrom)) * cos(deg2rad($latitudeTo)) * cos(deg2rad($theta));
	$dist = acos($dist);
	$dist = rad2deg($dist);
	return $miles = $dist * 60 * 1.1515;
}

/**
 * Extract all email addresses from any string and returns an array
 * @param $string
 * @return array
 */
function extractEmailsFrom($string){
    preg_match_all("/[\._a-zA-Z0-9-\+']+@[\._a-zA-Z0-9-]+/i", $string, $matches);
    return array_values(array_unique($matches[0]));
}

function encrypt_decrypt($string,$action){

    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key = 'yEsHIm9x69WsrwUpX14vMRxHHZ4gDof8';
    $secret_iv = '0U0yd6krTKFuHG9M';
    // hash
    $key = hash('sha256', $secret_key);
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);

    if($action == '1' ){ //encrypt
        /** @noinspection EncryptionInitializationVectorRandomnessInspection */
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
        //echo $output;
    }elseif($action == '2' ){//decrypt
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}

/**
 * Checks if the provided string is a valid timezone string
 * @param string $tz
 * @return int 1 -> true, 0 -> false
 */

function isValidTimeZone(string $tz) : int
{
    try {
        $tzone = new DateTimeZone($tz);
        return 1;
    } catch (Exception $e) {
        return 0;
    }
}

// Gets non null timezone value
function getNonNullTimeZoneValue(?string $tz): string
{
    if (!empty($tz)) {
        return $tz;
    } elseif (!empty($_SESSION['timezone'])) {
        return $_SESSION['timezone'];
    } else {
        return 'UTC';
    }
}
/**
 * This function returns options for Timezone with curr_tz selected
 * @param string $curr_tz
 * @param bool $include_preffered if set to false the preffered timezones are excluded
 * @param bool $curr_tz_label_only if on then a string label for $curr_tz is returned
 * @return string
 */
function getTimeZonesAsHtmlSelectOptions(string $curr_tz, bool $include_preffered = true, bool $curr_tz_label_only = false) : string
{
    $curr_tz = TskpTime::OUTDATED_TIMEZONE_MAP[$curr_tz] ?? $curr_tz;

    $retVal = '<option value="">Select a Timezone</option>';

    $preferred_timezones_values = array();
    $selected_label = '';

	// Fetch preferred timezones
    if ($include_preffered) {
        $preferred_timezones = Event::GetAllPreferredTimezones();
        if (!empty($preferred_timezones)) {
            usort($preferred_timezones,function($a,$b) {
                return strcmp($a['timezone_system_value'], $b['timezone_system_value']);
            });
            $retVal .= "<optgroup label='Preferred Time Zones'>";
            foreach ($preferred_timezones as $timezone) {
                $preferred_timezones_values[] = $timezone['timezone_system_value'];
                $sel = ($curr_tz == $timezone['timezone_system_value']) ? 'selected' : '';
                $display_label = $timezone['timezone_system_value'];
                if ($timezone['timezone_system_value'] != $timezone['timezone_display_name']) {
                    $display_label .= ' (' . $timezone['timezone_display_name'] . ')';
                }
                $retVal .= "<option value=\"{$timezone['timezone_system_value']}\"{$sel}>{$display_label}</option>";

                if ($sel) {
                    $selected_label = $display_label;
                }
            }
            $retVal .= '</optgroup>';
        }
    }

    $tz_group = '';

    foreach (timezone_identifiers_list() as $tz_value) {
		// Outdated ICU (International Components for Unicode) Version handling
		//$tz_value = TskpTime::OUTDATED_TIMEZONE_MAP[$tz_value] ?? $tz_value;
		
		if(in_array($tz_value, $preferred_timezones_values)){
			continue;
		}
        $tz_value_parts = explode('/', $tz_value);
        if ($tz_group != $tz_value_parts[0]) {
            if (!empty($tz_group)) {
                $retVal .= '</optgroup>';
            }
            $retVal .= "<optgroup label='{$tz_value_parts[0]}'>";
        }
        $sel = ($curr_tz == $tz_value) ? 'selected' : '';
        $tz_label = $tz_value;

        switch ($tz_value) {
            case 'America/New_York':    $tz_label .= ' (ET / US Eastern Time)'; break;
            case 'America/Chicago':     $tz_label .= ' (CT / US Central Time)'; break;
            case 'America/Denver':     $tz_label .= ' (MT / US Mountain Time)'; break;
            case 'America/Los_Angeles': $tz_label .= ' (PT / US Pacific Time)'; break;
            case 'Asia/Kolkata':        $tz_label .= ' (IST / Indian Standard Time)'; break;
        }

        $retVal .= "<option value='{$tz_value}' {$sel}>{$tz_label}</option>";
        $tz_group = $tz_value_parts[0];

        if ($sel) {
            $selected_label = $tz_label;
        }
    }
    if (!empty($tz_group)) {
        $retVal .= '</optgroup>';
    }

    if ($curr_tz_label_only) {
        return $selected_label;
    }

    return $retVal;
}

function getTimeHoursAsHtmlSelectOptions (string $selectedHour = '')
{
    $hlist = ['01','02','03','04','05','06','07','08','09','10','11','12'];
    echo '<option value="">' . gettext('Hour') . '</option>';
    foreach ($hlist as $h) {
        echo '<option value="' . $h . '" ' . ($selectedHour== $h ? 'selected' : '') . '>&nbsp;' . $h . '</option>';
    }
}

function getTimeMinutesAsHtmlSelectOptions (string $selectedMinute='')
{
    $mlist = ['00','05','10','15','20','25','30','35','40','45','50','55'];
    foreach ($mlist as $m) {
        echo '<option value="' . $m . '" ' . ($selectedMinute== $m ? 'selected' : '') . '>&nbsp;' . $m . '</option>';
    }
}

function randomPasswordGenerator($length) {
    $password = '';
    $passwordSets = ['1234567890-', 'ABCDEFGHJKLMNPQRSTUVWXYZ-', 'abcdefghjkmnpqrstuvwxyz-'];

    //Get random character from the array
    foreach ($passwordSets as $passwordSet) {
        $password .= $passwordSet[array_rand(str_split($passwordSet))];
    }
    while (strlen($password) < $length) {
        $randomSet = $passwordSets[array_rand($passwordSets)];
        $password .= $randomSet[array_rand(str_split($randomSet))];
    }

	return $password;
}

/**
 * Converty html to plain text string, converts multiple consective whitespaces with single space and multiple
 * consective newlines with a single newline.
 * @param string $html
 * @return string
 */
function convertHTML2PlainText (string $html, int $maxLen = 0): string
{
    $search = array("/&nbsp;/","/&emsp;/", "/\r\n/", "/\n\s+/", "/[\n]+/", "/[ ]+/");
    $replace = array(" ", " ", "\n","\n", "\n"," ");
    $retVal = preg_replace($search, $replace, rtrim(strip_tags($html)));
    if ($maxLen && strlen($retVal) > $maxLen) {
        return substr($retVal,0,$maxLen) . '...';
    }
    return $retVal;
}

function get_safe_extension (string $str) : string
{
    $path_parts = pathinfo($str);
    $ext = $path_parts['extension'] ?? '';
    $ext = preg_replace('/[^a-z0-9].*/', '', strtolower($ext));
    return substr($ext, 0, 4);
}