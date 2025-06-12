<?php
class GroupMemberSurvey extends Survey2
{
    public static function CreateNewSurvey(int $groupid, int $chapterid, int $channelid, string $surveyname, bool $anonymous, string $survey_json, int $surveytrigger, int $is_required=0,int $allow_multiple=0)
    {
        $rowid = parent::_CreateNewSurvey($surveyname, $groupid, $chapterid, $channelid, self::SURVEY_TYPE['GROUP_MEMBER'], $surveytrigger, $anonymous, $survey_json, '', $is_required, $allow_multiple);
        $row = parent::_GetSurveyRec($rowid);
        if (!empty($row)) {
            return new GroupMemberSurvey((int)$row['surveyid'], (int)$row['companyid'], $row);
        } else {
            return null;
        }
    }

    public static function GetAllSurveys(int $groupid, int $chapterid, int $channelid, int $surveytrigger = 0, bool $activeOnly = false)
    {
        global $_COMPANY, $_ZONE;
        $row = parent::_GetSurveyRecsMatchingScopeAndType($groupid, $chapterid, $channelid, self::SURVEY_TYPE['GROUP_MEMBER'], $surveytrigger);
        $groupMemberSurveys = array();
        foreach ($row as $item) {
            if ($activeOnly && ($item['isactive'] != self::STATUS_ACTIVE)) {
                continue;
            }
            $groupMemberSurveys[] = new GroupMemberSurvey((int)$item['surveyid'], (int)$item['companyid'], $item);
        }
        return $groupMemberSurveys;
    }

    public static function GetActiveGroupMemberSurveyForGroupJoin(int $groupid, int $chapterid = 0, int $channelid = 0)
    {
        return self::GetAllSurveys($groupid, $chapterid, $channelid, self::SURVEY_TRIGGER['ON_JOIN'], true);
    }

    public static function GetActiveGroupMemberSurveyForGroupLeave(int $groupid, int $chapterid = 0, int $channelid = 0)
    {
        return self::GetAllSurveys($groupid, $chapterid, $channelid, self::SURVEY_TRIGGER['ON_LEAVE'], true);
    }
}