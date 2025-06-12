<?php
use Defuse\Crypto\Key;

/**
 * CompanyPSKey provides a mechnism to generate and manage pre-shared keys with our enterprise partners. Pre-shared
 * keys can be used to pass encrypted tokens (any string) between the parties who have pre-shared keys.
 * Within our PHP framework, the keys can be used to encrypt as showh below
 *      $keyid = 1 ; // Substitue with a valid key id.
 *      $test_string = 'Hello';
 *      $cipher_text = CompanyPSKey::EncryptToken($test_string, $keyid);
 *      $output_string = CompanyPSKey::DecryptToken($cipher_text);
 *      echo "$dec"; // Should output 'Hello'
 *
 *  The tokens can also be created in Node.js using CryptoJS as shown below.
 *      var Crypto = require("crypto-js");
 *      const key ='def00000af4128fec9029e0b0a999c0b'; // Obtain key and keyid from teleskope
 *      const keyid = 1;
 *
 *      var iv = Crypto.lib.WordArray.random(8); // Generate a 16 character UTF8
 *      var epoch = Math.round(new Date().getTime() / 1000)+"";
 *      var data = {"time": epoch}
 *
 *      var cipherData = Crypto.AES.encrypt(
 *          JSON.stringify(data),
 *          Crypto.enc.Utf8.parse(key),
 *          {
 *              iv: Crypto.enc.Utf8.parse(iv),
 *              mode: Crypto.mode.CBC
 *          }
 *      ).toString(Crypto.format.Hex);
 *  var auth_token = "psk:"+keyid+":"+iv+"::"+cipherData;
 *  echo auth_token;
 *
 */

class CompanyPSKey extends Teleskope
{
    const AES_KEY_LENGTH = 32;
    const AES_BLOCK_SIZE = 16;
    const AES_IV_LENGTH = 16;
    const AES_CIPHER_ALGO = 'AES-256-CBC';

    protected function __construct($id,$cid,$fields) {
        parent::__construct($id,$cid,$fields);
        //declaring it protected so that no one can create it outside this class.
    }

    /**
     * Note the pskey is exposed only at the time of creating a key
     * @param string $key_purpose
     * @return array of 'pskeyid' and 'pskey'
     */
    public static function CreateCompanyPSKey(string $key_purpose) : array
    {
        global $_COMPANY;
        $key = Key::createNewRandomKey();
        $key_plaintext = strtr(substr(base64_encode($key->getRawBytes()),0,32), '+/=', 'tWZ'); // 256 bits only
        $key_ciphertext = CompanyEncKey::Encrypt($key_plaintext);

        $ps_key_id = self::DBInsertPS(
            "INSERT INTO company_pskeys (companyid, pskey, purpose) VALUES (?,?,?)",
            'ixx',
            $_COMPANY->id(), $key_ciphertext, $key_purpose
        );

        if ($ps_key_id) {
            return ['pskeyid' => $ps_key_id, 'pskey' => $key_plaintext];
        }
        return ['pskeyid' => '', 'pskey' => ''];
    }

    /**
     * @param int $ps_key_id
     * @return CompanyPSKey|null
     */
    public static function GetCompanyPSKey(int $ps_key_id): ?CompanyPSKey
    {
        global $_COMPANY;
        $key_rows = self::DBROGet("SELECT * FROM company_pskeys WHERE companyid={$_COMPANY->id()} AND pskeyid={$ps_key_id}");
        if (!empty($key_rows)) {
            $key_row = $key_rows[0];
            if (!empty($key_row['deletedon']))
                $key_row['pskey'] = '';

            return new CompanyPSKey($ps_key_id, $_COMPANY->id(), $key_row);
        }
        return null;
    }

    /**
     * @return array of CompanyPSKey objects
     */
    public static function GetAllCompanyPSKeys(): array
    {
        global $_COMPANY;
        $pskey_objects = [];
        $key_rows = self::DBROGet("SELECT * FROM company_pskeys WHERE companyid={$_COMPANY->id()}");
        foreach ($key_rows as $key_row) {
            if (!empty($key_row['deletedon']))
                $key_row['pskey'] = '';

            $pskey_objects[] =  new CompanyPSKey($key_row['pskeyid'], $_COMPANY->id(), $key_row);
        }
        return $pskey_objects;
    }

    /**
     * Checks if the key is valid, i.e. not deleted.
     * @return bool
     */
    public function isActive(): bool
    {
        return empty($this->val('deletedon'));
    }

    /**
     * Checks if the key is deleted
     * @return bool
     */
    public function isDeleted() : bool
    {
        return !empty($this->val('deletedon'));
    }

    /**
     * Soft deletes the PS Key by setting the deletedon date
     * @return void
     */
    public function delete()
    {
        global $_COMPANY;
        $result = self::DBMutate("UPDATE company_pskeys SET deletedon=NOW() WHERE companyid={$_COMPANY->id()} AND pskeyid={$this->id()}");
        if ($result) {
            $this->fields['deletedon'] = gmdate('Y-m-d H:i:s');
        }
    }

    /**
     * Soft deletes the PS Key by setting the deletedon date
     * @return void
     */
    public function updatePurpose(string $purpose)
    {
        global $_COMPANY;
        $result = self::DBMutatePS("UPDATE company_pskeys SET purpose=? WHERE companyid=? AND pskeyid=?", 'xii', $purpose, $_COMPANY->id(), $this->id());
        if ($result) {
            $this->fields['purpose'] = $purpose;
        }
    }

    /**
     * Get decrypted PS Key
     * @return string
     */
    private function getPSKey(): string
    {
        return CompanyEncKey::Decrypt($this->val('pskey'));
    }

    /**
     * Rotates the key cipher with a new key if available
     */
    public function reEncryptKey()
    {
        global $_COMPANY;
        $key_plaintext = $this->getPSKey();
        $new_key_ciphertext = CompanyEncKey::Encrypt($key_plaintext);

        $result = self::DBUpdate("UPDATE company_pskeys SET pskey='{$new_key_ciphertext}' WHERE companyid={$_COMPANY->id()} AND pskeyid={$this->id()}");
        if ($result) {
            $this->fields['pskey'] = $new_key_ciphertext;
        }
    }

    /**
     * This method encrypts using a CryptoJS compliant manner to allow data encrypted by PHP can be decrypted by CryptoJS (Node.js)
     *
     * @param string $plaintext
     * @return string returns a token that can be decrypted by CompanyPSKey::DecryptToken
     * @throws Exception
     */
    public static function EncryptToken (string $plaintext, int $pskeyid): string
    {
        $pskey = self::GetCompanyPSKey($pskeyid);

        if (!$pskey || empty($plaintext)) {
            return ''; // invalid key or value
        }

        // Generate a new iv
        $iv = substr(md5(random_bytes(8)), 0, self::AES_IV_LENGTH);

        // To make encryption compliant with CryptoJS, we need to pad the text as below.
        // Do not change the following padding block
        $textlen = strlen($plaintext);
        $pad = self::AES_BLOCK_SIZE - ($textlen % self::AES_BLOCK_SIZE);
        $padded_plaintext = str_pad($plaintext, $textlen + $pad, chr($pad));
        // End of padding block.

        /** @noinspection EncryptionInitializationVectorRandomnessInspection */
        $ciphertext = openssl_encrypt(
                $padded_plaintext,
                self::AES_CIPHER_ALGO,
                $pskey->getPSKey(),
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                $iv);

        $hex_ciphertext = bin2hex($ciphertext);
        return "psk:{$pskey->id()}:{$iv}::{$hex_ciphertext}";
    }

    public static function DecryptToken(string $encrypted_token): string
    {
        if (!str_starts_with($encrypted_token, 'psk:')) {
            return ''; // invalid token
        }

        $pos = strpos($encrypted_token, '::');

        [$key_type, $key_id, $iv] = explode(':', substr($encrypted_token, 0, $pos));
        $cipher_text = hex2bin(substr($encrypted_token, $pos + 2));

        if (empty($key_type) || empty($key_id) || empty($iv) || empty($cipher_text)) {
            return ''; // Tampered or incorrect token
        }

        $ps_key = self::GetCompanyPSKey($key_id);

        if (!$ps_key || $ps_key->isDeleted()) {
            return ''; // Invalid or expired PS key
        }

        return openssl_decrypt($cipher_text, self::AES_CIPHER_ALGO, $ps_key->getPSKey(), true, $iv);
    }
}
