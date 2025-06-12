<?php

require_once __DIR__ . '/head.php';
use Aws\Kms\KmsClient;

$db = new Hems();

$company_subdomains = $_SUPER_ADMIN->super_get('SELECT `subdomain` FROM `companies` WHERE `isactive` != 3');
$company_subdomains = array_column($company_subdomains, 'subdomain');

$client = new KmsClient([
    'version' => 'latest',
    'region' => S3_REGION,
]);

$result = $client->listAliases();

$key_aliases = array_column($result['Aliases'], 'AliasName');
$company_subdomains_with_key = array_map(function (string $key_alias) {
    return str_replace('alias/', '', $key_alias);
}, $key_aliases);

$company_subdomains_without_key = array_diff($company_subdomains, $company_subdomains_with_key);

foreach ($company_subdomains_without_key as $subdomain) {
    CompanyEncKey::CreateCompanyAwsKmsKey($subdomain);
    echo "Created AWS KMS key for company subdomain - {$subdomain} \n";
}
