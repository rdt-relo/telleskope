<?php
exit();
require_once __DIR__.'/../affinity/head.php';
require_once __DIR__.'/../include/UserCatalog.php';

function seed_database()
{
    global $db;
    //$random_userid_array = range(100000, 225000); // 125K entries
    $random_userid_array = array_column(($db->get("SELECT `userid` FROM `users` WHERE `companyid`=1 AND `isactive` = 1")),'userid'); // 125K entries
    $catalogs = array();
    shuffle($random_userid_array);
    $catalogs['Department'] = [];
    $catalogs['Department']['Engineering'] = array_slice($random_userid_array, 0, 1 * 1000);
    $catalogs['Department']['Marketing'] = array_slice($random_userid_array, 1 * 1000, 2 * 1000);
    $catalogs['Department']['Sales'] = array_slice($random_userid_array, 2 * 1000, 3 * 1000);

    shuffle($random_userid_array);
    $catalogs['Country'] = [];
    $catalogs['Country']['USA'] = array_slice($random_userid_array, 0, 1 * 1000);
    $catalogs['Country']['Canada'] = array_slice($random_userid_array, 1 * 1000, 2 * 1000);
    $catalogs['Country']['UK'] = array_slice($random_userid_array, 2 * 1000, 3 * 1000);
    $catalogs['Country']['Germany'] = array_slice($random_userid_array, 4 * 1000, 4 * 1000);

    shuffle($random_userid_array);
    $catalogs['Gender'] = [];
    $catalogs['Gender']['M'] = array_slice($random_userid_array, 0, 1 * 1000);
    $catalogs['Gender']['F'] = array_slice($random_userid_array, 1 * 1000, 2 * 1000);
    $catalogs['Gender']['O'] = array_slice($random_userid_array, 3 * 1000, 4 * 1000);

    shuffle($random_userid_array);
    $catalogs['ManagementLevel'] = [];
    $catalogs['ManagementLevel']['1'] = array_slice($random_userid_array, 0, 1 * 1000);
    $catalogs['ManagementLevel']['2'] = array_slice($random_userid_array, 1 * 1000, 2 * 1000);
    $catalogs['ManagementLevel']['3'] = array_slice($random_userid_array, 3 * 1000, 3 * 1000);
    $catalogs['ManagementLevel']['4'] = array_slice($random_userid_array, 6 * 1000, 6 * 1000);
    $catalogs['ManagementLevel']['5'] = array_slice($random_userid_array, 12 * 1000, 12 * 1000);
    $catalogs['ManagementLevel']['6'] = array_slice($random_userid_array, 24 * 1000, 24 * 1000);
    $catalogs['ManagementLevel']['7'] = array_slice($random_userid_array, 48 * 1000, 24 * 1000);
    $catalogs['ManagementLevel']['8'] = array_slice($random_userid_array, 62 * 1000, 24 * 1000);
    $catalogs['ManagementLevel']['9'] = array_slice($random_userid_array, 84 * 1000, 14 * 1000);

    echo "<h2>Creating Sample Catalogs</h2>";
    foreach ($catalogs as $category => $catalog) {
        $category_internal_id = 'extendedprofile.'. slugify($catalog); // This is a fake internal id as it does not match user table columns or JSON columns.
        foreach ($catalog as $keyname => $userids) {
            if ($category == 'ManagementLevel')
                $keytype = 'int';
            else
                $keytype = 'string';

            echo "<br>$category - $keyname - " . count($userids);
            UserCatalog::DeleteAndSaveCatalog($category, $category_internal_id, $keyname, $keytype, $userids);
        }
    }
    //$_COMPANY->expireRedisCache("UCC:{$_COMPANY->id()}");
    echo "<br>Database seeding complete, rerun your operation";
    exit();
}

$catalog_categories = array_values(UserCatalog::GetAllCatalogCategories());
if (empty($catalog_categories)) {
    seed_database();
}

echo "The following categories can be shown for a match";
foreach ($catalog_categories as $catalog_category) {
    echo "<br>$catalog_category";
}

//$random_user_id = rand(100000,200000);
$random_user_id = $_USER->id();
$users_department_name = UserCatalog::GetCatalogKeynameForUser('Department',$random_user_id);
$users_country = UserCatalog::GetCatalogKeynameForUser('Country', $random_user_id);
$users_gender = UserCatalog::GetCatalogKeynameForUser('Gender', $random_user_id);
$users_management_level = UserCatalog::GetCatalogKeynameForUser('ManagementLevel', $random_user_id);
echo "<hr>";
echo "<br>User ($random_user_id) of management level {$users_management_level} in department={$users_department_name}, country={$users_country}, identifies as {$users_gender}";

echo "<hr>";
echo "Finding matches for user in other departments, same country and same gender and management level rank greater than or equal 2 levels";
$ttl = -microtime(true);
$other_departments = UserCatalog::GetUserCatalog('Department', $users_department_name, '!=');
$same_country = UserCatalog::GetUserCatalog('Country', $users_country, '==');
$same_gender = UserCatalog::GetUserCatalog('Gender', $users_gender, '==');
$greater2_management_level = UserCatalog::GetUserCatalog('Gender', $users_management_level+2, '>=');

// if ()
$matches = $other_departments
    ->intersect($same_country)
    ->intersect($same_gender)
    ->intersect($greater2_management_level);

$count_found = count($matches->getUserIds());
$ttl += microtime(true);
$random_10_start = $count_found-10;
echo "<br>Found {$count_found} matches in {$ttl} seconds, showing random 10";
echo "<pre>";print_r(array_slice($matches->getUserIds(), $random_10_start , 10));echo "</pre>";

echo "<hr><br>Set Operations<br>";
$users_in_india_or_ireland = UserCatalog::GetZoneUserCatalogBySet('Country', UserCatalog::SET_TYPE_OPERATOR__IN, ['India','Ireland']);
echo "<br><strong>Users in India or Ireland</strong><br>";
print_r($users_in_india_or_ireland->getUserIds());
echo "<br><br><strong>Users NOT in India or Ireland</strong><br>";
$users_not_in_india_or_ireland = UserCatalog::GetZoneUserCatalogBySet('Country', UserCatalog::SET_TYPE_OPERATOR__NOT_IN, ['India','Ireland']);
print_r($users_not_in_india_or_ireland->getUserIds());
echo "<br><br>End of Set Operations<br>";