<?php
//---------DB Class------------//

class SuperAdminFunctions extends Teleskope { // Class for common fucntions

    public const S3_SUPER_AREA = array(
        'SYSTEM_MESSAGES' => '/system_messages/',
        'GUIDES' => '/guides/',
    );


    /**
     * @throws Exception
     */
    public function __construct() {
        if (
            !str_contains($_SERVER['HTTP_HOST'], 'teleskope.io') ||
            !str_contains($_SERVER['REQUEST_URI'], '/super/')
        ) {
            $mesg = 'Cannot instantiate Super Admin Functions outside super admin scope.';
            exit($mesg);
        }
    }

    /**
     * To send email
     */
	public function superEmail($from_name, $to, $subject, $message) {

        // Do not use template as super messages are already templatized.
        //		$emesg  = file_get_contents(SITE_ROOT.'/email/template1.html');
        //		$emesg	= str_replace('#messagehere#',$message,$emesg);
        $emesg = $message;

        $from_name= $from_name ?: 'The Teleskope Team';
        $base_host = parse_url(BASEURL,PHP_URL_HOST);
        $from_email = 'noreply@'.$base_host;
        $retVal = false;

        global $_LOGGER_META_MAIL;

        // Initialize Logger Meta data
        $_LOGGER_META_MAIL = [
            'fromName' => $from_name,
            'fromAddr' => $from_email,
            'toAddr' => $to,
            'subject' => $subject,
        ];

		try {
			$mail = new PHPMailer\PHPMailer\PHPMailer(true);
			$mail->CharSet = 'UTF-8';
			$mail->Encoding = 'base64';
			//Server settings
			$mail->SMTPDebug = false;                   // Donot print any debug info
			$mail->isSMTP();                            // Set mailer to use SMTP
			$mail->Host = SMTP_HOSTNAME;                // Specify main and backup SMTP servers
			$mail->SMTPAuth = true;                     // Enable SMTP authentication
			$mail->Username = SMTP_USERNAME;            // SMTP username
			$mail->Password = SMTP_PASSWORD;            // SMTP password
			$mail->SMTPSecure = 'tls';                  // Enable TLS encryption, `ssl` also accepted
			$mail->Port = SMTP_PORT;                    // TCP port to connect to

			//Recipients
			$mail->setFrom($from_email, $from_name);

			if (strpos($to,',') == false) {
				$mail->addAddress($to);                              // Add a recipient
			} else {
				$tos = explode(',',$to);
				$tos_count = 0;
				while ($tos_count < count($tos)) {
					$mail->addAddress($tos[$tos_count]);
					$tos_count = $tos_count+1;
				}
			}

			//Content
			$mail->Subject = $subject;
			$mail->isHTML(true);                        // Set email format to HTML
			$mail->Body = $emesg;
			$mail->AltBody = "This is a HTML message only";

			if (!empty($ical_str)) {
				$mail->Ical = $ical_str;
				$mail->addStringAttachment($ical_str,'invite.ics','base64','application/ics');
			}

			if ($mail->send()) {
                Logger::Log("Emailed To:" . $to . " with Subject: \"" . $subject . "\" and From Name: " . $from_name, Logger::SEVERITY['INFO']);
                $retVal = true;
            }
		} catch (Exception $e) {
            $severity = Logger::SEVERITY['FATAL_ERROR'];
            if (
                str_contains($e->getMessage(), 'must provide at least one recipient email address')
                ||
                str_contains($e->getMessage(), 'Invalid address')
            ) {
                $severity = Logger::SEVERITY['WARNING_ERROR'];
            }
			Logger::Log('Mailer Error: ' . $e->getMessage(), $severity);
		} finally {
            $_LOGGER_META_MAIL = null;
        }
        return $retVal;
	}

	public function saveFile(string $src_file, string $dest_name, string $s3_area) {
		//Instantiate the client.
   
		$s3 = Aws\S3\S3Client::factory([
            'version' => 'latest',
            'region' => S3_REGION
			
		]);

		$retVal = "";
        $dest_name = basename($dest_name); //Extract filename without any subfolder fragments seperated by
        $folder = self::S3_SUPER_AREA[$s3_area] ?? '';

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $contentType = $finfo->file($src_file);

        //Build the name where to store the file
        if (empty($dest_name) || empty($folder))
            return $retVal;

        $folder_suffix = ($s3_area == 'SYSTEM_MESSAGES') ? date("Y-m-d").'/' : '';
        $s3name = 'teleskope'.$folder.$folder_suffix.$dest_name;

		try{
			$s3->putObject([
			'Bucket'=>S3_BUCKET,
			'Key'=>$s3name,
			'Body'=>fopen($src_file,'rb'),
			'ACL'=>'public-read',
             'ContentType' => $contentType
			]);
			$retVal = "https://".S3_BUCKET.".s3.amazonaws.com/".$s3name;
		}catch(Exception $e){
			Logger::Log("Caught Exception in SuperAdminFunctions->saveFile while uploading {$src_file} as {$dest_name} to s3 {$s3name}");
		}
		return $retVal;
	}

    public function resizeImage(string $src_file, string $ext, int $max_width): string
    {
        $ext = strtolower($ext);
        $info 	 = getimagesize($src_file);
        $width 	 = $info[0];

        if($width>$max_width){
            $original_w    = $info[0];
            $original_h	   = $info[1];
            if($ext === 'png'){
                $original_img  = imagecreatefrompng($src_file);
            }elseif ($ext === 'jpg' || $ext === 'jpeg'){
                $original_img  = imagecreatefromjpeg($src_file);
            } else {
                return $src_file;
            }

            $thumb_w 	   = $max_width;
            $extra_w 	   = $original_w - $thumb_w;
            $ratio	   	   = $extra_w / $original_w;

            $thumb_h	   = $original_h * $ratio;
            $thumb_h	   = $original_h - $thumb_h;

            $thumb_img 	   = imagecreatetruecolor($thumb_w, $thumb_h);
            imagealphablending($thumb_img, false );
            imagesavealpha($thumb_img, true );
            imagecopyresampled($thumb_img, $original_img,
                0, 0,
                0, 0,
                $thumb_w, $thumb_h,
                $original_w, $original_h);
            if($ext === "png"){
                imagepng($thumb_img, $src_file);
                //}elseif($ext == "bmp"){
                //	imagebmp($thumb_img, $src_file);
            }elseif ($ext === 'jpg' || $ext === 'jpeg'){
                imagejpeg($thumb_img, $src_file);
            }
        }
        return $src_file;
    }

    /**
     * @param string $sql
     * @return int
     */
    public function super_update(string $sql)
    {
        return self::DBUpdate($sql);
    }

    /**
     * @param string $sql
     * @return int|string|void
     */
    public function super_insert(string $sql)
    {
        return self::DBInsert($sql);
    }

    public function super_insert_ps(...$args)
    {
        return self::DBInsertPS(...$args);
    }

    /**
     * @param string $sql
     * @return array
     */
    public function super_get(...$args)
    {
        return self::DBGet(...$args);
    }

    /**
     * @param string $sql
     * @return array
     */
    public function super_roget(...$args)
    {
        return self::DBROGet(...$args);
    }

    /**
     * @param string $sql
     * @return array
     */
    public function super_getps(...$args)
    {
        return self::DBGetPS(...$args);
    }

    public function super_update_ps(...$args)
    {
        return self::DBUpdatePS(...$args);
    }

    public function getSuperAdmin(int $super_id): ?array
    {
        $super_admin = $this->super_get("
            SELECT
                `superid`, `username`, `email`, `manage_companyids`, `permissions`, `failed_login_attempts`, `isactive`, IF(expiry_date < now(), 1, 0) as `is_expired`,
                `google_auth_code`, `password`
            FROM
                `admin`
            WHERE `superid` = {$super_id}
        ");

        if (empty($super_admin)) {
            return null;
        }

        $super_admin = $super_admin[0];

        $super_admin['is_super_super_admin'] = ($super_admin['manage_companyids'] === '-1');
        $super_admin['manage_companyids'] = explode(',', $super_admin['manage_companyids']);
        $super_admin['permissions'] = (json_decode($super_admin['permissions'] ?? '', true)) ?: [];
        $super_admin['is_blocked'] = ($super_admin['failed_login_attempts'] >= 3);

        return $super_admin;
    }

    public function  getAllSuperAdmins() : array
    {
        $super_admins = $this->super_get('
            SELECT
                `superid`
            FROM
                `admin`
        ');

        $super_admin_list = [];
        foreach ($super_admins as $super_admin) {
            $super_admin_list[] = $this->getSuperAdmin($super_admin['superid']);
        }
        return $super_admin_list;
    }

}
