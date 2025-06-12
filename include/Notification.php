<?php

// Do no use require_once as this class is included in Company.php.

class Notification extends Teleskope
{

    protected function __construct($id, $cid, $fields)
    {
        parent::__construct($id, $cid, $fields);
    }


    public static function GetNotification(int $notificationid): ?Notification
    {
        global $_COMPANY, $_ZONE, $_USER;

        $row = self::DBGet("SELECT * FROM `notifications` WHERE `notificationid` = '{$notificationid}' AND `userid`='{$_USER->id()}' ");
        if (!empty($row)) {
            return new Notification($notificationid,$_COMPANY->id(), $row[0]);
        }
        return null;
    }


    public function readNotification():int
    {
        global $_COMPANY, $_ZONE, $_USER;
        return self::DBUpdate("UPDATE `notifications` SET `isread`='1' WHERE `notificationid`='{$this->id()}' AND `userid`='{$_USER->id()}' ");
    }

    public static function ReadAllNotifications():int
    {
        global $_COMPANY, $_ZONE, $_USER;
        return self::DBUpdate("UPDATE `notifications` SET `isread`='1' WHERE `userid`='{$_USER->id()}' ");
    }

    public function deleteNotification():int
    {
        global $_COMPANY, $_ZONE, $_USER;
        return self::DBUpdate("DELETE FROM `notifications` WHERE `notificationid`='{$this->id()}' AND `userid`='{$_USER->id()}' ");
    }

    public static function DeleteAllNotifications():int
    {
        global $_COMPANY, $_ZONE, $_USER;
        return self::DBUpdate("DELETE FROM `notifications` WHERE `userid`='{$_USER->id()}' ");
    }
   

}
