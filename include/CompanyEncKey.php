<?php

use Defuse\Crypto\Key;
use Defuse\Crypto\File;
use Defuse\Crypto\Crypto;
use Aws\S3\S3Client;
use Aws\Kms\KmsClient;
use Aws\Result;

class CompanyEncKey extends Teleskope
{
    const STATIC_PART_OF_IV = '9uMHkkWI';
    public static function Encrypt(string $plaintext): string
    {
        if (empty($plaintext)) {
            return '';
        }
        $key_id = self::GetLatestCompanyEncKey();
        $key = Key::loadFromAsciiSafeString(self::GetCompanyEncKey($key_id));

        //$ciphertext = Crypto::encrypt($plaintext, $key);
        // Crypto::encrypt generates a very long string and takes a very long time... so we are using openssl directly.

        $iv = substr(md5(random_bytes(4)), 0, 8);
        /** @noinspection EncryptionInitializationVectorRandomnessInspection */
        $ciphertext = base64_url_encode(openssl_encrypt($plaintext, "AES-256-CBC", $key->getRawBytes(), OPENSSL_RAW_DATA, $iv . self::STATIC_PART_OF_IV));

        return "kms:{$key_id}:{$iv}::{$ciphertext}";
    }

    public static function Decrypt(string $ciphertext, ...$args): string
    {
        if (empty($ciphertext)) {
            return '';
        }
        if (str_starts_with($ciphertext, 'kms:')) {
            $pos = strpos($ciphertext, '::');

            [$key_type, $key_id, $iv] = explode(':', substr($ciphertext, 0, $pos));
            $key = Key::loadFromAsciiSafeString(self::GetCompanyEncKey($key_id));

            $ciphertext = base64_url_decode(substr($ciphertext, $pos + 2));
            // Crypto::decrypt  takes a very long time... so we are using openssl directly.
            //return Crypto::decrypt($ciphertext, $key);
            return openssl_decrypt($ciphertext, "AES-256-CBC", $key->getRawBytes(), true, $iv . self::STATIC_PART_OF_IV) ?: '';
        }

        if ($args) {
            return aes_encrypt($ciphertext, ...$args);
        }

        return $ciphertext;
    }

    public static function EncryptFileAndUploadToS3(string $filename, array $s3_args): Result
    {
        global $_COMPANY;

        $s3 = new S3Client([
            'version' => 'latest',
            'region' => S3_REGION,
        ]);

        $metadata = $s3_args['Metadata'] ?? [];

        $tmpfilename = TmpFileUtils::GetTemporaryFile($_COMPANY->val('subdomain').'_');

        $key_id = self::GetLatestCompanyEncKey();
        $key = Key::loadFromAsciiSafeString(self::GetCompanyEncKey($key_id));
        File::encryptFile($filename, $tmpfilename, $key);

        $filename = $tmpfilename;
        $metadata['aws_kms_key_id'] = $key_id;

        return $s3->putObject([
            ...$s3_args,
            'Body' => fopen($filename, 'r'),
            'Metadata' => $metadata,
        ]);
    }

    public static function DownloadFromS3AndDecryptFile(array $s3_args): array
    {
        global $_COMPANY;

        $s3 = new S3Client([
            'version' => 'latest',
            'region' => S3_REGION,
        ]);

        $tmpfilename = TmpFileUtils::GetTemporaryFile($_COMPANY->val('subdomain').'_');

        $result = $s3->getObject([
            ...$s3_args,
            'SaveAs' => $tmpfilename,
        ]);

        if (isset($result['Metadata']['aws_kms_key_id'])) {
            $tmpfile_decrypted_path = TmpFileUtils::GetTemporaryFile($_COMPANY->val('subdomain').'_');

            $key = Key::loadFromAsciiSafeString(self::GetCompanyEncKey((int) $result['Metadata']['aws_kms_key_id']));
            File::decryptFile($tmpfilename, $tmpfile_decrypted_path, $key);

            $tmpfilename = $tmpfile_decrypted_path;
        }

        return [
            'filename' => $tmpfilename,
            'aws_result' => $result,
        ];
    }

    /**
     * This method initializes company AWS KMS keys. Company is identified by subdomain
     * @param string $subdomain
     * @return void
     */
    public static function CreateCompanyAwsKmsKey(string $subdomain): void
    {
        $client = new KmsClient([
            'version' => 'latest',
            'region' => S3_REGION,
        ]);

        $result = $client->createKey([
            'Description' => 'Company-specific AWS KMS Key',
            'MultiRegion' => true,
        ]);

        $client->createAlias([
            'AliasName' => 'alias/' . $subdomain,
            'TargetKeyId' => $result['KeyMetadata']['KeyId'],
        ]);

        $result = $client->replicateKey([
            'KeyId' => $result['KeyMetadata']['KeyId'],
            'ReplicaRegion' => 'us-west-2',
        ]);

        $client = new KmsClient([
            'version' => 'latest',
            'region' => 'us-west-2',
        ]);

        $client->createAlias([
            'AliasName' => 'alias/' . $subdomain,
            'TargetKeyId' => $result['ReplicaKeyMetadata']['KeyId'],
        ]);
    }

    private static function GetCompanyEncKey(int $key_id): string
    {
        if ($aws_kms_key = self::CacheEncKey($key_id)) {
            return $aws_kms_key;
        }

        $company_enckey = self::FetchCompanyAwsKmsKeyFromDB($key_id);
        if (!$company_enckey) {
            return '';
        }

        return self::CacheEncKey($key_id, self::KmsDecrypt($company_enckey['enckey']));
    }

    private static function CacheEncKey(int $key_id, string $updated_enc_key = ''): string
    {
        global $_COMPANY;

        $apcu_key = $_COMPANY->val('subdomain') . '/AWS_KMS_KEYS';
        $ttl = 3600 * 24;

        $aws_kms_keys = apcu_fetch($apcu_key) ?: [];

        if (!$updated_enc_key) {
            return $aws_kms_keys[$key_id] ?? '';
        }

        $aws_kms_keys[$key_id] = $updated_enc_key;
        apcu_store($apcu_key, $aws_kms_keys, $ttl);

        return $updated_enc_key;
    }

    private static function GetLatestCompanyEncKey(): int
    {
        global $_COMPANY;

        $key = 'KMS:LATEST_ENC_KEY_ID';

        $enc_key_id = $_COMPANY->getFromRedisCache($key);

        if ($enc_key_id) {
            return $enc_key_id;
        }

        $results = self::DBROGet("SELECT `enckeyid` FROM `company_enckeys` WHERE `companyid` = {$_COMPANY->id()} ORDER BY `createdon` DESC LIMIT 1");

        if (isset($results[0])) {
            $_COMPANY->putInRedisCache($key, $results[0]['enckeyid'], 3600 * 24);
            return $results[0]['enckeyid'];
        }

        $enc_key_id = self::CreateNewCompanyEncKey();
        $_COMPANY->putInRedisCache($key, $enc_key_id, 3600 * 24);
        return $enc_key_id;
    }

    public static function CreateNewCompanyEncKey(): int
    {
        global $_COMPANY;

        $key = Key::createNewRandomKey();
        $key_plaintext = $key->saveToAsciiSafeString();

        $key_id = self::DBInsertPS(
            'INSERT INTO `company_enckeys` (`companyid`, `enckey`) VALUES (?, ?)',
            'is',
            $_COMPANY->id(),
            self::KmsEncrypt($key_plaintext)
        );

        self::CacheEncKey($key_id, $key_plaintext);
        return $key_id;
    }

    private static function FetchCompanyAwsKmsKeyFromDB(int $key_id): array
    {
        global $_COMPANY;

        $results = self::DBROGetPS(
            'SELECT * FROM `company_enckeys` WHERE `companyid` = ? AND `enckeyid` = ?',
            'ii',
            $_COMPANY->id(),
            $key_id
        );

        return $results[0] ?? [];
    }

    private static function KmsEncrypt(string $plaintext): string
    {
        global $_COMPANY;

        $client = new KmsClient([
            'version' => 'latest',
            'region' => S3_REGION,
        ]);

        $result = $client->encrypt([
            'KeyId' => 'alias/' . $_COMPANY->val('subdomain'),
            'Plaintext' => $plaintext,
        ]);

        return base64_encode($result['CiphertextBlob']);
    }

    private static function KmsDecrypt(string $ciphertext): string
    {
        global $_COMPANY;

        $client = new KmsClient([
            'version' => 'latest',
            'region' => S3_REGION,
        ]);

        $result = $client->decrypt([
            'KeyId' => 'alias/' . $_COMPANY->val('subdomain'),
            'CiphertextBlob' => base64_decode($ciphertext),
        ]);

        return $result['Plaintext'];
    }
}
