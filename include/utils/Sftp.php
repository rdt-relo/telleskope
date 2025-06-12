<?php

class Sftp
{
    /**
     * @param string $localFilename local file with path that needs to be put
     * @param string $sftpServer hostname of the sftp server
     * @param string $sftpUsername
     * @param string $sftpPassword
     * @param string $sftpFolder foldername on sftp server where the file will be placed, can be empty
     * @param string $sftpFileName filename to use on sftp server for the put file
     * @param int $sftpPort default 22 but can be overriden by providing a value.
     * @return array  returns an array with following values
                                'status': false if put failed, true otherwise,
                                'bytes': number of bytes transfered,
                                'message': message for logging purposes,
                                'verbose': error details in case of error,

     */
    public static function Put (string $localFilename, string $sftpServer, string $sftpUsername, string $sftpPassword, string $sftpFolder, string $sftpFileName, int $sftpPort=22) : array
    {
        $retVal = [
            'status' => false,
            'bytes' => 0,
            'message' => '',
            'verbose' => ''
        ];

        $sftpFolder = trim($sftpFolder, '/');
        $sftpFileName = trim($sftpFileName, '/');
        $sftpFolderAndFileName = $sftpFolder ? "/{$sftpFolder}/{$sftpFileName}" : "/{$sftpFileName}";

        $ch = curl_init('sftp://' . $sftpServer . ':' . $sftpPort . $sftpFolderAndFileName);

        $fh = fopen($localFilename, 'r');

        if (!$fh) {
            $retVal['message'] = 'Sftp: Unable to read file ' . $localFilename;
        } else {
            $fileSize = filesize($localFilename);
            curl_setopt($ch, CURLOPT_USERPWD, $sftpUsername . ':' . $sftpPassword);
            curl_setopt($ch, CURLOPT_UPLOAD, true);
            curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_SFTP);
            curl_setopt($ch, CURLOPT_INFILE, $fh);
            curl_setopt($ch, CURLOPT_INFILESIZE, $fileSize);
            curl_setopt($ch, CURLOPT_VERBOSE, true);

            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($response) {
                $retVal['bytes'] = $fileSize;
                $retVal['status'] = true;
                $retVal['message'] = "Sftp: Saved local {$localFilename} into remote {$sftpFolderAndFileName}";

            } else {
                rewind($verbose);
                $retVal['message'] = $error;
                $retVal['verbose'] = stream_get_contents($verbose);
            }
        }
        return $retVal;
    }

    /**
     * @param string $localFilename local file with path that needs to be put
     * @param string $sftpServer
     * @param string $sftpUsername
     * @param string $sftpPassword
     * @param string $sftpFolder foldername on sftp server where the file will be placed, can be empty
     * @param string $sftpFileName filename to use on sftp server for the put file
     * @param int $sftpPort default 22 but can be overriden by providing a value.
     * @param bool $deleteOnGet default is true; if true the file will be deleted from remote server on successful get
     * @return array  returns an array with following values
                'status': false if get failed, true otherwise,
                'bytes': number of bytes transfered,
                'message': message for logging purposes,
                'verbose': error details in case of error,
     */
    public static function Get (string $localFilename, string $sftpServer, string $sftpUsername, string $sftpPassword, string $sftpFolder, string $sftpFileName, int $sftpPort=22, bool $deleteOnGet = true) : array
    {
        $retVal = [
            'status' => false,
            'bytes' => 0,
            'message' => '',
            'verbose' => ''
        ];

        $sftpFolder = trim($sftpFolder, '/');
        $sftpFileName = trim($sftpFileName, '/');
        $sftpFolderAndFileName = $sftpFolder ? "/{$sftpFolder}/{$sftpFileName}" : "/{$sftpFileName}";

        $ch = curl_init('sftp://' . $sftpServer . ':' . $sftpPort . $sftpFolderAndFileName);

        curl_setopt($ch, CURLOPT_USERPWD, $sftpUsername . ':' . $sftpPassword);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_SFTP);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $local_file = fopen ($localFilename, 'w+');
        curl_setopt($ch, CURLOPT_FILE, $local_file);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($local_file);

        if ($response && !$error) {
            //$res = file_put_contents($localFilename, $response);
            //if ($res === false) {
            //    $retVal['message'] = 'Unable to write file ' . $localFilename;
            //}
            $retVal['bytes'] = filesize($localFilename);
            $retVal['status'] = true;
            $retVal['message'] = "Sftp: Saved remote {$sftpFolderAndFileName} into local {$localFilename}";

            if ($deleteOnGet) {
                curl_setopt($ch, CURLOPT_QUOTE, array("rm ".escapeshellarg($sftpFolderAndFileName)));
                $res2 = curl_exec($ch);
                $retVal['message'] .= ', deleted remote ' . $res2;
                curl_close($ch);
            }
        } else {
            rewind($verbose);
            $retVal['message'] = $error;
            $retVal['verbose'] = stream_get_contents($verbose);
        }

        return $retVal;
    }
}

class PhpSecLibSftp
{
    /**
     * @param string $localFilename local file with path that needs to be put
     * @param string $sftpServer hostname of the sftp server
     * @param string $sftpUsername
     * @param string $sftpPassword
     * @param string $sftpFolder foldername on sftp server where the file will be placed, can be empty
     * @param string $sftpFileName filename to use on sftp server for the put file
     * @param int $sftpPort default 22 but can be overriden by providing a value.
     * @return array  returns an array with following values
    'status': false if put failed, true otherwise,
    'bytes': number of bytes transfered,
    'message': message for logging purposes,
    'verbose': error details in case of error,

     */
    public static function Put (string $localFilename, string $sftpServer, string $sftpUsername, string $sftpPassword, string $sftpFolder, string $sftpFileName, int $sftpPort=22, string $sftp_auth_method = 'sftp_password') : array
    {
        $retVal = [
            'status' => false,
            'bytes' => 0,
            'message' => '',
            'verbose' => ''
        ];

        $sftpFolder = trim($sftpFolder, '/');
        $sftpFileName = trim($sftpFileName, '/');
        $fileSize = filesize($localFilename);
        $sftpFolderAndFileName = $sftpFolder ? "/{$sftpFolder}/{$sftpFileName}" : "/{$sftpFileName}";

        $sftp = new phpseclib3\Net\SFTP($sftpServer, $sftpPort);

        if ($sftp_auth_method === 'sftp_ssh_key') {
            $key_str = Config::Get('SFTP_SSH_PRIVATE_KEY_1');
            $key = \phpseclib3\Crypt\PublicKeyLoader::loadPrivateKey($key_str);
            $login_args = [$sftpUsername, $key];
        } else {
            $login_args = [$sftpUsername, $sftpPassword];
        }

        if (!$sftp->login(...$login_args)) {
            Logger::Log("PhpSecLibSftp: Unable to log into {$sftpUsername}@{$sftpServer}:{$sftpPort}");
            $retVal['message'] = "PhpSecLibSftp: Unable to log into {$sftpUsername}@{$sftpServer}:{$sftpPort}";
        } else {
            //$sftp->put($sftp_filename, 'If sending a string then use this');
            $sftp->chdir($sftpFolder);
            $sftp->put($sftpFileName, $localFilename, phpseclib3\Net\SFTP::SOURCE_LOCAL_FILE);
            $sftpInfo = $sftp->rawlist()[$sftpFileName];
            $retVal['bytes'] = $fileSize;
            $retVal['status'] = true;
            $retVal['message'] = "PhpSecLibSftp: Saved local {$localFilename} into remote {$sftpFolderAndFileName}";
            $retVal['verbose'] = json_encode($sftpInfo);
        }
        return $retVal;
    }

    /**
     * @param string $localFilename local file with path that needs to be put
     * @param string $sftpServer
     * @param string $sftpUsername
     * @param string $sftpPassword
     * @param string $sftpFolder foldername on sftp server where the file will be placed, can be empty
     * @param string $sftpFileName filename to use on sftp server for the put file
     * @param int $sftpPort default 22 but can be overriden by providing a value.
     * @param bool $deleteOnGet default is true; if true the file will be deleted from remote server on successful get
     * @return array  returns an array with following values
    'status': false if get failed, true otherwise,
    'bytes': number of bytes transfered,
    'message': message for logging purposes,
    'verbose': error details in case of error,
     */
    public static function Get (string $localFilename, string $sftpServer, string $sftpUsername, string $sftpPassword, string $sftpFolder, string $sftpFileName, int $sftpPort=22, bool $deleteOnGet = true, string $sftp_auth_method = 'sftp_password') : array
    {
        $retVal = [
            'status' => false,
            'bytes' => 0,
            'message' => '',
            'verbose' => ''
        ];

        $sftpFolder = trim($sftpFolder, '/');
        $sftpFileName = trim($sftpFileName, '/');
        $sftpFolderAndFileName = $sftpFolder ? "/{$sftpFolder}/{$sftpFileName}" : "/{$sftpFileName}";

        $sftp = new phpseclib3\Net\SFTP($sftpServer, $sftpPort);

        if ($sftp_auth_method === 'sftp_ssh_key') {
            # In future when key needs to be update change it to SFTP_SSH_PRIVATE_KEY_BASE64_2 and so on
            $key_str = base64_decode(Config::Get('SFTP_SSH_PRIVATE_KEY_BASE64_1'));
            try {
                $key = \phpseclib3\Crypt\PublicKeyLoader::loadPrivateKey($key_str);
            } catch (Exception $e) {
                Logger::Log("PhpSecLibSftp: Unable to load private key ". $e->getMessage());
                return $retVal;
            }
            if (!empty($sftpPassword)) {
                $login_args = [$sftpUsername, $sftpPassword, $key];
            }
        } else {
            $login_args = [$sftpUsername, $sftpPassword];
        }

        if (!$sftp->login(...$login_args)) {
            Logger::Log("PhpSecLibSftp: Unable to log into {$sftpUsername}@{$sftpServer}:{$sftpPort}");
            $retVal['message'] = "PhpSecLibSftp: Unable to log into {$sftpUsername}@{$sftpServer}:{$sftpPort}";
        } else {
            $sftp->chdir($sftpFolder);
            $fileContents = $sftp->get($sftpFileName);

            if (empty($fileContents)) {
                Logger::Log("PhpSecLibSftp: Unable to fetch file {$sftpFolderAndFileName} from {$sftpServer}", Logger::SEVERITY['WARNING_ERROR']);

                $retVal['message'] = "PhpSecLibSftp: Unable to fetch file {$sftpFolderAndFileName} from {$sftpServer}";

            } else {
                file_put_contents($localFilename, $fileContents);
                $retVal['bytes'] = strlen($fileContents);
                $retVal['status'] = true;
                $retVal['message'] = "PhpSecLibSftp: Saved remote {$sftpFolderAndFileName} into local {$localFilename}";

                if ($deleteOnGet) {
                    $sftp->delete($sftpFileName, false); // Non recursive delete
                    $retVal['message'] .= ', deleted remote ';
                }
            }
        }

        return $retVal;
    }
}