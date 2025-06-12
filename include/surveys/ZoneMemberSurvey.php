<?php

class ZoneMemberSurvey extends Survey2
{

    public static function CreateNewSurvey(string $surveyname, bool $anonymous, string $survey_json, int $surveytrigger,int $is_required=0, int $allow_multiple=0)
    {
        $rowid = parent::_CreateNewSurvey($surveyname, 0, 0, 0, self::SURVEY_TYPE['ZONE_MEMBER'], $surveytrigger, $anonymous, $survey_json,'',$is_required,$allow_multiple);
        $row = parent::_GetSurveyRec($rowid);
        if (!empty($row)) {
            return new GroupMemberSurvey((int)$row['surveyid'], (int)$row['companyid'], $row);
        } else {
            return null;
        }
    }

    public static function GetAllSurveys(int $surveytrigger = 0, bool $activeOnly = false)
    {
        $row = parent::_GetSurveyRecsMatchingScopeAndType(0, 0, 0, self::SURVEY_TYPE['ZONE_MEMBER'], $surveytrigger);
        $zoneMemberSurveys = array();
        foreach ($row as $item) {
            if ($activeOnly && ($item['isactive'] != self::STATUS_ACTIVE)) {
                continue;
            }
            $zoneMemberSurveys[] = new ZoneMemberSurvey((int)$item['surveyid'], (int)$item['companyid'], $item);
        }
        return $zoneMemberSurveys;
    }

    public static function GetActiveLoginSurvey()
    {
        return self::GetAllSurveys(self::SURVEY_TRIGGER['ON_LOGIN'], true);
    }
}