<?php
// /*Disabled Talent Peak scope on 09/25/22*/
//class TeamMemberSurvey extends Survey2
//{
//    public static function CreateNewSurvey(int $groupid, string $surveyname, bool $anonymous, string $survey_json, string $options, int $is_required = 0)
//    {
//        $rowid = parent::_CreateNewSurvey($surveyname, $groupid, 0, 0, self::SURVEY_TYPE['TEAM_MEMBER'], self::SURVEY_TRIGGER['FOLLOWUP'], $anonymous, $survey_json, $options, $is_required);
//        $row = parent::_GetSurveyRec($rowid);
//        if (!empty($row)) {
//            return new TeamMemberSurvey((int)$row['surveyid'], (int)$row['companyid'], $row);
//        } else {
//            return null;
//        }
//    }
//
//    public static function GetAllSurveys(int $groupid, int $surveytrigger = 0, bool $activeOnly = false)
//    {
//        $row = parent::_GetSurveyRecsMatchingScopeAndType($groupid, 0, 0, self::SURVEY_TYPE['TEAM_MEMBER'], $surveytrigger);
//        $teamMemberSurveys = array();
//        foreach ($row as $item) {
//            if ($activeOnly && ($item['isactive'] != self::STATUS_ACTIVE)) {
//                continue;
//            }
//            $teamMemberSurveys[] = new TeamMemberSurvey((int)$item['surveyid'], (int)$item['companyid'], $item);
//        }
//        return $teamMemberSurveys;
//    }
//
//    public static function GetActiveTeamMemberSurvey(int $groupid)
//    {
//        return self::GetAllSurveys($groupid, self::SURVEY_TRIGGER['FOLLOWUP'], true);
//    }
//}