<?php

// Do no use require_once as this class is included in Company.php.

class UserInbox extends Teleskope
{
    const USER_INBOX_FOLDERS = [
        'INBOX' => 'INBOX',
        'TRASH' => 'TRASH'
    ];

    public static function SaveMessage(string $toEmail, string $fromName, string $fromEmail, string $subject, string $message)
    {
        global $_COMPANY;
        global $_ZONE;

        if (!$_COMPANY->getAppCustomization()['user_inbox']['enabled']) {
            return 0;
        }

        $u = User::GetUserByEmail($toEmail);
        if ($u && $u->isUserInboxEnabled()) {

            $messageSaved =  self::DBInsertPS("INSERT INTO user_inbox(companyid, zoneid, userid, from_name, from_email, message_subject, message) VALUES (?,?,?,?,?,?,?)", 'iiixxxx', $_COMPANY->id(), $_ZONE->id(), $u->id(), $fromName, $fromEmail, $subject, $message);

            if ($messageSaved){
                // Push notifications
                $user = self::DBGet("SELECT api_session_id, `userid`, `devicetype`, `devicetoken` FROM users_api_session WHERE `userid`='" . $u->id() . "' and devicetoken!=''");
                if (count($user) > 0) {
                    $badge = 1;
                    [$bearerToken, $firebaseProjectId] = $_COMPANY->getFirebaseBearerTokenAndProjectId();
                    for ($i = 0; $i < count($user); $i++) {
                        sendCommonPushNotification($user[$i]['devicetoken'], 'You have a new Message!', $subject, $badge, self::PUSH_NOTIFICATIONS_STATUS['USER_INBOX'], $messageSaved, 1, $_ZONE->id(),$bearerToken,'v1',$firebaseProjectId);
                    }
                }

                // Email notifications: send to external email if the user has external email
                $externalEmail = $u->val('external_email');
                if (!empty($externalEmail)) {
                    $personName = $u->getFullName();
                    $companyName = $_COMPANY->val('companyname');
                    $applicationName = $_COMPANY->val('companyname') . $_COMPANY->getAppCustomization()['group']['name'];
                    $inboxLink = $_COMPANY->getAppURL($_ZONE->val('app_type')).'my_inbox';

                    $external_notification = <<<EOMEOM
<p>Dear {$personName},</p>
<br>
<p>You have a received a new message from {$fromName} with subject {$subject} in your {$applicationName} application inbox.</p>
<br>
<p>To retrieve the message, please go to your {$applicationName} application <a href="{$inboxLink}">inbox</a>.</p>
<br>
Please note that messages will be deleted from your {$applicationName} inbox after 30 days.
<br>
<p>If you wish to unsubscribe from notifications, you can do so by going to {$applicationName} > Profile > Update notifications against group membership.</p>
<br>
<p>Sincerely,</p>
<p>The {$applicationName} Team</p>                  
EOMEOM;

                    $_COMPANY->emailSendExternal($fromName, $externalEmail, $subject, $external_notification, $_ZONE->val('app_type'));
                }
            }

            return $messageSaved;    
        }
        return 0;
    }

    /**
     * @param string $folder valid values are: INBOX, TRASH, see UserInbox::USER_INBOX_FOLDERS values
     * @param int $start
     * @param int $end
     * @return array
     */
    public static function GetMyMessages(string $folder, int $start, int $end) : array
    {
        global $_COMPANY;
        global $_ZONE;
        global $_USER;

        if (!$_USER->isUserInboxEnabled()) {
            return array();
        }

        $folderFilter = ' AND false';
        $folder = strtoupper($folder);
        if ($folder == 'INBOX') {
            $folderFilter = 'AND deletedon IS NULL';
        } elseif ($folder == 'TRASH') {
            $folderFilter = 'AND deletedon IS NOT NULL';
        }

        $rows = self::DBGet("SELECT * FROM user_inbox WHERE companyid={$_COMPANY->id()} AND zoneid={$_ZONE->id()} AND userid='{$_USER->id()}' {$folderFilter} ORDER BY messageid DESC LIMIT {$start},{$end}");
        if (!empty($rows)) {
            return $rows;
        }
        return array();
    }

    /**
     * @param int $messageid
     */
    public static function GetMessage(int $messageid)
    {
        global $_COMPANY;
        global $_ZONE;
        global $_USER;

        if (!$_USER->isUserInboxEnabled()) {
            return null;
        }

        $rows = self::DBGet("SELECT * FROM user_inbox WHERE companyid={$_COMPANY->id()} AND (zoneid={$_ZONE->id()} AND messageid={$messageid} AND userid={$_USER->id()})");
        if (!empty($rows)) {
            return $rows[0];
        }
        return null;
    }

    /**
     * @param int $messageid
     *
    */
    public static function ReadInboxMessage(int $messageid)
    {
        global $_COMPANY;
        global $_ZONE;
        global $_USER;

        if (!$_USER->isUserInboxEnabled()) {
            return 0;
        }

        return self::DBUpdatePS("UPDATE user_inbox SET readon=now() WHERE companyid={$_COMPANY->id()} AND (zoneid={$_ZONE->id()} AND messageid={$messageid} AND user_inbox.userid={$_USER->id()} AND readon IS NULL)");
    }

    /**
     * @param int $messageid
     * @param int $deletePermanently
     * @return int
     */
    public static function DeleteInboxMessage(int $messageid, bool $deletePermanently=false): int
    {
        global $_COMPANY;
        global $_ZONE;
        global $_USER;

        if (!$_USER->isUserInboxEnabled()) {
            return 0;
        }

        if ($deletePermanently) {
            return self::DBUpdatePS("DELETE FROM user_inbox WHERE companyid={$_COMPANY->id()} AND (zoneid={$_ZONE->id()} AND messageid={$messageid} AND userid={$_USER->id()})");
        } else {
            return self::DBUpdatePS("UPDATE user_inbox SET deletedon=now() WHERE companyid={$_COMPANY->id()} AND (zoneid={$_ZONE->id()} AND messageid={$messageid} AND userid={$_USER->id()})");
        }
    }

    /**
     * This method will delete all user inbox messages that are older than 30 days, regardless of the company.
     * @return void
     */
    public static function DeleteOldUserInboxMessages()
    {
        self::DBMutate("DELETE FROM user_inbox WHERE createdon > NOW() - interval 30 day");
    }
}
