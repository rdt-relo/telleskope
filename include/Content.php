<?php

// Do no use require_once as this class is included in Company.php.

class Content extends Teleskope
{
    public static function GetAvailableContentTypes()
    {
        global $_COMPANY,$_ZONE;
        $availeContentTypes = array();
        if ($_COMPANY->getAppCustomization()['post']['enabled'] && $_COMPANY->getAppCustomization()['post']['show_in_home_feed']) {
            $availeContentTypes[] = 'post';
        }
        if ($_COMPANY->getAppCustomization()['event']['enabled'] && $_COMPANY->getAppCustomization()['event']['show_in_home_feed']) {
            $availeContentTypes[] = 'event';
            $availeContentTypes[] = 'upcomingEvents';
        }
        if ($_COMPANY->getAppCustomization()['newsletters']['enabled'] && $_COMPANY->getAppCustomization()['newsletters']['show_in_home_feed']) {
            $availeContentTypes[] = 'newsletter';
        }
        if ($_COMPANY->getAppCustomization()['discussions']['enabled'] && $_COMPANY->getAppCustomization()['discussions']['show_in_home_feed']) {
            $availeContentTypes[] = 'discussion';
        }
        if ($_COMPANY->getAppCustomization()['albums']['enabled'] && $_COMPANY->getAppCustomization()['albums']['show_in_home_feed']) {
            $availeContentTypes[] = 'album';
        }

        return  $availeContentTypes ;
    }

    public static function GetContent(array $groupIds,bool $globalOnly,int $page,int $limit, array $include_content_types):array
    {
        global $_COMPANY,$_ZONE;

        $max_items = $limit + 1; // We are adding 1 additional row to enable 'Show More to work'
        $start = (($page - 1) * $limit);

        $groupIds = array_map('intval', $groupIds);
        $groupIdList = implode(",", $groupIds);
        $group_condition = "AND content_groupids IN ({$groupIdList})";
        
        # Dirty fix to always show chapter level content for group_category=IG
        # This was done to show more content in Affinties IG
        $chapter_condition = '';
        if (!$_COMPANY->getAppCustomization()['group']['homepage']['show_chapter_content_in_global_feed'] && $globalOnly) {
            $chapter_condition = " AND content_chapterids='0'";
        }

        $channel_condition = '';
        if (!$_COMPANY->getAppCustomization()['group']['homepage']['show_channel_content_in_global_feed'] && $globalOnly) {
            $channel_condition = " AND content_channelids='0'";
        }

        $collaborating_zone_condition = '';
        if (!empty($_ZONE->val('collaborating_zoneids')) && in_array('event', $include_content_types) ) {
            // If there are collaborating zones, then the check will be for a zoneid match or match in collaborating zones ids for events which are collaborative i.e. where groupid=0
          $collaborating_zone_condition = "OR (zoneid IN ({$_ZONE->val('collaborating_zoneids')}) AND content_type = 'event' AND content_groupids = 0) ";
        }
        $zone_group_condition = "AND ( (zoneid={$_ZONE->id()} {$group_condition}) {$collaborating_zone_condition})" ;

//        // Set content which is needed
        $contentTypeFilter = '';
//        if(empty($collaborating_zone_condition) && !empty($include_content_types) && !$showUpcomingEventsOnly) {
//            // First lets make sure content types are valid
//            $valid_content_types = self::GetAvailableContentTypes();
//            $include_content_types = array_intersect($valid_content_types, $include_content_types);
//            $contentTypes = implode("','",$include_content_types) ?: '0';
//            $contentTypeFilter = " AND `content_type` IN ('$contentTypes')";
//        }

        $additionalFilter = '';
        $order_by = '';
        if(in_array('upcomingEvents', $include_content_types)) {
            $additionalFilter .= " AND (content_type='event' AND content_start > now())";
            $order_by .= "content_start ASC";
        } else {
            // If events is to be shown with any other content type, then check if past events are configured to be shown
            if (!$_COMPANY->getAppCustomization()['group']['homepage']['show_past_events_in_show_all_in_global_feed'] && count($include_content_types) > 1 && in_array('event', $include_content_types)) {
                $additionalFilter .= " AND (content_type != 'event' OR content_start > now())";
                // Show upcoming events in next 3 days after the pinned content.
                $order_by .= "content_pinned DESC, IF((content_type='event' AND content_start < now() + interval 3 day), `content_start`, '3000-12-31 00:00:00') ASC, content_date DESC";
            } else {
                $order_by .= "content_pinned DESC, content_date DESC";
            }

            $available_content_types = array_intersect($include_content_types, Content::GetAvailableContentTypes());
            $content_type_set_string = implode("','", $available_content_types) ?: 'invalid';
            $contentTypeFilter .= " AND (content_type  IN ('{$content_type_set_string}') )";
        }

        return self::DBROGet(
                                "SELECT * FROM `content` 
                                WHERE `companyid` = {$_COMPANY->id()}
                                    {$zone_group_condition}
                                    {$chapter_condition}
                                    {$channel_condition}
                                    {$contentTypeFilter}
                                    {$additionalFilter}
                                ORDER BY {$order_by}
                                LIMIT {$start}, {$max_items}"
                            );
    }


}
