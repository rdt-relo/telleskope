<?php

class TeamTask extends Teleskope
{
    private $team_obj = null;

    public static function GetTopicType():string {return self::TOPIC_TYPES['TEAMTASKS'];}
    use TopicLikeTrait;
    use TopicCommentTrait;
    use TopicAttachmentTrait;

    public static function GetTeamTask(int $id, bool $allow_cross_zone_fetch = false): ?TeamTask
    {
        global $_COMPANY, $_ZONE;

        $results = self::DBGet("
            SELECT * FROM `team_tasks`
            WHERE `taskid` = {$id}
            AND `companyid` = {$_COMPANY->id()}
        ");

        if (empty($results[0])) {
            return null;
        }

        if (!$allow_cross_zone_fetch && ((int) $results[0]['zoneid'] !== $_ZONE->id())) {
            return null;
        }

        return new TeamTask($id, $_COMPANY->id(), $results[0]);
    }

    /**
     * Used by points-module when a new comment is added to a team-task
     * Points-module is group-aware and wants to know the groupID of the trigger
     * Used in Points::GetTriggerContext, NEW_COMMENT
     */
    public function __getval_groupid(): int
    {
        $team_obj = $this->getTeamObj();
        return $team_obj?->val('groupid') ?: 0;
    }

    /**
     * Used by points-module when a new comment is added to a team-task
     * Points-module is group-aware and wants to know the groupID of the trigger
     * Used in Points::GetTriggerContext, NEW_COMMENT
     */
    public function __getval_collaborating_groupids(): string
    {
        $team_obj = $this->getTeamObj();
        return $team_obj?->val('collaborating_groupids') ?: '';
    }

    private function getTeamObj(): ?Team
    {
        if ($this->team_obj) {
            return $this->team_obj;
        }

        $this->team_obj = Team::GetTeam($this->val('teamid'), true);
        return $this->team_obj;
    }
}
