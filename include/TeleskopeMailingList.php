<?php

class TeleskopeMailingList extends Teleskope
{
    public static function AddOrUpdateUserMailingList(int $userid, int $join_product_list, int $join_training_list, int $join_webinar_list)
    {
        global $_COMPANY, $_USER;

        if ($join_product_list || $join_training_list || $join_webinar_list) {
            // get previous mailing list subscription
            $pastRecord = self::GetMyMailingList();
            if ($pastRecord) {
                return self::DBUpdatePS("UPDATE `teleskope_mailing_list` SET `join_product_list`=?, `join_training_list`=?, `join_webinar_list`=? , `modifiedon`=NOW() WHERE companyid=? AND userid=? ", 'iiiii', $join_product_list, $join_training_list, $join_webinar_list, $_COMPANY->id(), $_USER->id());
            } else {
                return self::DBInsertPS("INSERT INTO `teleskope_mailing_list`( `companyid`, `userid`, `join_product_list`, `join_training_list`,`join_webinar_list`,`createdon`,`modifiedon`) VALUES (?,?,?,?,?,NOW(),NOW())", "iiiii", $_COMPANY->id(), $_USER->id(), $join_product_list, $join_training_list, $join_webinar_list);
            }


        }
        //   if $join_webinar_list,  $join_product_list and $join_training_list are turned off (0) then delete the record.
        return self::DBMutate("DELETE FROM teleskope_mailing_list WHERE teleskope_mailing_list.companyid={$_COMPANY->id()} AND teleskope_mailing_list.userid={$_USER->id()}");
    }


    public static function GetMyMailingList(): array
    {
        global $_COMPANY, $_USER;
        //   .... empty array if no users were found.
        return self::DBGet("SELECT * FROM `teleskope_mailing_list` WHERE `companyid`='{$_COMPANY->id()}' AND `userid`='{$_USER->id()}'");


    }

    // These methods will be called from super admin... so you will not use global $_COMPANY and $_USER
    public static function GetProductMailingList(): array
    {
        return self::DBGet("SELECT email from users join teleskope_mailing_list using (companyid,userid) where users.isactive=1 and join_product_list=1");
    }

    // These methods will be called from super admin... so you will not use global $_COMPANY and $_USER
    public static function GetTrainingMailingList(): array
    {
        return self::DBGet("SELECT email from users join teleskope_mailing_list using (companyid,userid) where users.isactive=1 and join_training_list=1");
    }

    public static function GetWebinarMailingList(): array
    {
        return self::DBGet("SELECT email from users join teleskope_mailing_list using (companyid,userid) where users.isactive=1 and join_webinar_list=1");
    }

}