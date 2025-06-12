<?php
class Template extends Teleskope
{

    const TEMPLATE_TYPE_NEWSLETTER = 1;
    const TEMPLATE_TYPE_POST = 2;
    const TEMPLATE_TYPE_EVENT = 3;
    const TEMPLATE_TYPE_COMMUNICATION = 4;
    const TEMPLATE_TYPE_COMMUNICATION_ANNIVERSARY = 5;

    protected function __construct(int $id, int $cid, array $fields)
    {
        parent::__construct($id, $cid, $fields);
    }

    public static function GetTemplate(int $templateid)
    {
        global $_COMPANY, $_ZONE;
      
        $row = self::DBROGet("SELECT * FROM templates WHERE companyid={$_COMPANY->id()} AND (zoneid={$_ZONE->id()} AND templateid={$templateid})");
        if (!empty($row)) {
            return new Template($templateid,$_COMPANY->id(), $row[0]);
        }
        return null;

    }

    public static function GetTemplatesByTemplateTypes(int $templateType)
    {
        global $_COMPANY, $_ZONE;
        return self::DBROGet("SELECT * FROM `templates` WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND (`isactive`=1 AND `templatetype` = {$templateType})");
    }

}

