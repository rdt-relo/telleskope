<?php

class BudgetRequest extends Teleskope
{
    use TopicAttachmentTrait;
    use TopicCustomFieldsTrait;

    public static function GetBudgetRequest(int $id): ?BudgetRequest
    {
        $budget_request = Budget2::GetBudgetRequestDetail($id, true);
        $budget_request['isactive'] = $budget_request['is_active'];
        return self::Hydrate($id, $budget_request);
    }

    public static function GetTopicType(): string
    {
        return self::TOPIC_TYPES['BUDGET_REQUEST'];
    }

    public static function GetBudgetRequestsData( string $budget_year_start_date, string $budget_year_end_date, bool $includeGroupRequests, bool $includeChapterRequests, bool $resolvedRequestsOnly
    ): array
    {
        global $_ZONE, $_COMPANY;

        $groupOrChapterCondition = ' AND budget_requests.chapterid = -1'; // Set to some invalid value
        $groupOrChapterCondition = $includeGroupRequests ? ' AND budget_requests.chapterid = 0' : $groupOrChapterCondition;
        $groupOrChapterCondition = $includeChapterRequests ? ' AND budget_requests.chapterid != 0' : $groupOrChapterCondition;
        $groupOrChapterCondition = $includeGroupRequests && $includeChapterRequests ? '' : $groupOrChapterCondition;

        $resolvedRequestCondition = $resolvedRequestsOnly ? ' AND budget_requests.request_status != 1' : ' AND budget_requests.request_status = 1';

        return self::DBGet("
            SELECT budget_requests.*, requestor.firstname, requestor.lastname, groups.groupname, chapters.chaptername, approver.firstname as a_firstname, approver.lastname as a_lastname
            FROM budget_requests 
                LEFT JOIN users requestor ON requestor.userid = budget_requests.requested_by 
                LEFT JOIN users approver ON approver.userid = budget_requests.approved_by
                LEFT JOIN `groups` ON groups.groupid = budget_requests.groupid 
                LEFT JOIN chapters ON chapters.chapterid = budget_requests.chapterid
            WHERE budget_requests.companyid = '{$_COMPANY->id()}' 
                AND budget_requests.is_active = 1 
                AND budget_requests.need_by BETWEEN '{$budget_year_start_date}' AND '{$budget_year_end_date}' 
                AND budget_requests.zoneid = {$_ZONE->id()}
                {$resolvedRequestCondition} 
                {$groupOrChapterCondition}
            ");
    }    
}
