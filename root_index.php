<?php
$server = $_SERVER['SERVER_NAME'];

/**
 * This method gets subdomains from .CompanyDictionary opcache.
 * The reason we are doing it this way rather than calling CompanyDictionary directly is becuase this root_index file
 * is written in a way to have no impact on DB in case there is a DDOS attack. In case of DDOS attack at root level,
 * only the CPU resources of the FE servers will be expended instead of DB or other aspects of the system.
 */
function getValidSubdomainsFromCompanyDictionary(): array
{
    $dictionary_file = rtrim(sys_get_temp_dir(),'/').'/.CompanyDictionary';

    if (!file_exists($dictionary_file)) {
        // Then first seed the file by calling cache_services ... do not call CompanyDictionary directly.
        $ch = curl_init('http://localhost/1/internal/cache_services?seed_company_dictionary=initialize');
        curl_exec($ch);
        curl_close($ch);
    }

    if (!file_exists($dictionary_file)) {
        return [];
    }

    @include $dictionary_file;
    //
    // Note: if the file .CompanyDictionary exists it will load the variable $val
    //
    // If the CompanyDictionary is not yet created, then seed it by making a call to http link that can seed it.
    //
    return $val ?? [];
}

$needle = explode('.',$server)[0];

if (empty(getValidSubdomainsFromCompanyDictionary()[$needle])) {
    header("HTTP/1.1 403 Subdomain Access Denied");
    die();
}

if (strpos($server,'teleskope.io')==true) {
	header('location: /1/admin/');
} elseif (strpos($server,'affinities.io')==true) {
	header('location: /1/affinity/');
} elseif (strpos($server,'officeraven.io')==true) {
	header('location: /1/officeraven/');
} elseif (strpos($server,'talentpeak.io')==true) {
	header('location: /1/talentpeak/');
}elseif (strpos($server,'peoplehero.io')==true) {
	header('location: /1/peoplehero/');
} elseif (strpos($server,'mentorlink.io')==true) {
	$parts = explode('.',$server);
	$parts[1] = 'talentpeak';
	header('location: https://'.implode('.',$parts).'/1/talentpeak/');
}
