<?php
ini_set('max_execution_time', 10000);
header( 'Content-type: text/html; charset=utf-8' );
require_once __DIR__.'/../head.php';
require_once __DIR__ . '/../../include/Company.php'; //This file internally calls dbfunctions, Company etc.
require_once __DIR__ . '/../../include/User.php'; //This file internally calls dbfunctions, User etc.

$company = null;
$max_pairs = 0;
init_company_id();

global $_COMPANY;
global $_ZONE;

$_COMPANY = $company;


// Fetch all duplicate users by checking for common prefix
$get_duplicate_users = $_SUPER_ADMIN->super_get(<<<SQL
    SELECT dups.pre, group_concat(dups.userid) as userids 
    FROM (
        SELECT email,userid,substring_index(email,'@',1) as pre FROM users WHERE companyid={$_COMPANY->id()}
        ) dups 
    GROUP BY dups.pre 
    HAVING count(1) > 1;
SQL
);


echo "<h2>Updating for {$_COMPANY->val('subdomain')} </h2>";

$no_of_pairs = count($get_duplicate_users);
echo "<h5>There are {$no_of_pairs} user pairs that are eligible for merge, out of which {$max_pairs} will be merged</h5>";

echo "<pre>";
$json=[];

$number_done = 0;
foreach ($get_duplicate_users as $u) {

    $u_parts = explode(",", $u['userids']);

    $json['pre'] = $u['pre'];
    $json['userids'] = $u_parts;
    $json['results'] = null;

    if ($max_pairs) {
        if ($number_done >= $max_pairs) {
            echo "<br>Stopping after processing {$number_done} pairs<br>";
            break;
        } else {
            $json['results'] = User::MergeUsers($u_parts[0], $u_parts[1]);
            $number_done++;
        }
    }
    echo json_encode($json) . "<br>";
    flush();
}
echo "</pre>";
$_COMPANY = null;


function init_company_id() {

    global $company, $max_pairs;

    if (isset($_POST['migrate_company_id'])) {
        $company = Company::GetCompany(intval($_POST['migrate_company_id']));
        $max_pairs = intval($_POST['max_pairs']);
        $max_pairs = min(100, $max_pairs);
    }

    if (!$company) {
        echo <<< HTML
        <form href="" method="post">
            <p><strong>Enter company id: </strong></p>
            <input type="text" name="migrate_company_id">
          
            <p><strong>Enter Maximum pairs to merge:</strong></p>
            <input type="text" name="max_pairs" value="0">
            <br>Use 0 for dry run ... always do a dry run first as merge is a very destructive operation. Max at a time is 100
            <br>
            <br>
            <br>
            <input type="submit">
        </form>
        HTML;
        exit();
    }
}