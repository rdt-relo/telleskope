<?php

class ViewHelper
{
    /**
     * Deprecated
     * @param array $groupIdAry An array of groupids to use for searching events, announcements and newsletters. For
     * @param bool $myFeedsOnly if true then content matching chapter membership only will be shown
     * @param int $page page no start from 1
     * @param int $limit Default limit is 30
     * @param array $include_content_types  By default 'post','event','newsletter' are included feature is enabled
     * @return mixed
     * @throws Exception
     */
   
    public static function GetHomeFeeds(array $allGroupIdAry, bool $myFeedsOnly, int $page, int $limit, array $include_content_types = array())
    {
        global $_COMPANY;
        global $_USER;
        global $_ZONE;

        if (empty($include_content_types)){
            return array('feeds'=>array(),'contents_count_before_processing'=>0);
        }
        // Get post and events
        $utc_tz = new DateTimeZone('UTC');
        $local_tz = new DateTimeZone($_SESSION['timezone'] ?: 'UTC');

        $groupIdAry = array();
        foreach ($allGroupIdAry as $gid) {
            if ($_USER->canViewContent($gid)) {
                $groupIdAry[] = $gid;
            }
        }
        if (in_array(0,$allGroupIdAry)) {
            $groupIdAry[] = 0;
        }

        if (empty($groupIdAry)) {
            return array('feeds'=>array(),'contents_count_before_processing'=>0); // Return empty array
        }
        //$group_category = $_SESSION['selected_group_category'] ?? '';
        
        $joinedChapters = array();
        $joinedChannels = array();
        $globalOnly = false;
        if ($myFeedsOnly) {
            $joinedChapters = $_USER->getJoinedChapters();
            $joinedChannels = $_USER->getJoinedChannels();
        //} elseif (empty($group_category)) {
        //    $globalOnly = true;
        }
        
        $feeds = array();
        $contents = Content::GetContent($groupIdAry,$globalOnly, $page, $limit, $include_content_types);
        $contentsCount = count($contents);
        $skipLastContent = ($contentsCount > MAX_HOMEPAGE_FEED_ITERATOR_ITEMS);
        $index = 0;
        foreach($contents as $content){
            if ($skipLastContent && $index == MAX_HOMEPAGE_FEED_ITERATOR_ITEMS){ // Don't process last content because it is fetched only for pagination purpose
                break;
            }
            $index++;
            $row = array();
            $row['content_type'] = ''; // If row was skipped, e.g. chapter match did not happen then type is left as 0 to not match anything
            if ($myFeedsOnly) {
                // For my feeds section, if the content is chapter or channel specific then only show the rows that have chapter or channels that the user subscribes to
                // If we are requested to return content for joined chapters then we will filter out rows that have chapter id set but chapter id does not match
                if (!empty($content['content_chapterids']) && empty(array_intersect($joinedChapters, explode(',', $content['content_chapterids'])))) {
                    $feeds[] = $row; // Return empty row or empty content type so that we can still keep the correct count of rows for 'load more' to work
                    continue;
                }
                if (!empty($content['content_channelids']) && empty(array_intersect($joinedChannels, explode(',', $content['content_channelids'])))) {
                    $feeds[] = $row; // Return empty row or empty content type so that we can still keep the correct count of rows for 'load more' to work
                    continue;
                }
            }

            if ($content['content_groupids'] == 0) {
                $row['groupname'] = $_COMPANY->getAppCustomization()['group']['groupname0'];
                $row['groupname_short'] = $_COMPANY->getAppCustomization()['group']['groupname0'];
                $row['overlaycolor'] = '';
            } else {
                $group = Group::GetGroup($content['content_groupids']);
                $row['groupname'] =  $group->val('groupname');
                $row['groupname_short'] = $group->val('groupname_short');
                $row['overlaycolor'] = $group->val('overlaycolor');
            }
          
            if ($content['content_type'] == 'event'){
                $event = Event::GetEventFromCache($content['content_id']);
                if ($event){
                    if ($event->val("collaborating_groupids")){
                        $colleboratedIds = explode(',',$event->val("collaborating_groupids"));
                        $skip = true;
                        foreach($colleboratedIds as $colleboratedId){
                            if(in_array($colleboratedId,$groupIdAry)){
                                $skip = false;
                                break;
                            }
                        }
                        if ($skip){
                            continue; // Skip event not related to filtered groups
                        }
                    } elseif ($event->val('zoneid') != $_ZONE->id()) {
                        // FILTER_GLOBAL_EVENTS_OF_COLLABORATIVE_ZONES section
                        continue; // Skip events from other zones that are not collaborative, i.e. global events.
                    }

                    $row['eventid'] = $event->val('eventid');
                    $row['event_series_id'] = $event->val('event_series_id');
                    $row['groupid'] = $event->val('groupid');
                    $row['chapterid'] = $event->val('chapterid');
                    $row['channelid'] = $event->val('channelid');
                    $row['eventtitle'] = $event->val('eventtitle');
                    $row['eventvanue'] = $event->val('eventvanue');
                    $row['vanueaddress'] = $event->val('vanueaddress');
                    $row['event_description'] = $event->val('event_description');
                    $row['addedon'] = $event->val('publishdate');
                    $row['rsvp_display'] = $event->val('rsvp_display');
                    $row['start'] = $event->val('start');
                    $row['event_attendence_type'] = $event->val('event_attendence_type');
                    $row['web_conference_sp'] = $event->val('web_conference_sp');
                    $row['publishdate'] = $event->val('publishdate');
                    $row['isactive'] = $event->val('isactive');
                    $row['joinerCount'] = $event->getJoinersCount();
                    $row['pin_to_top'] = $event->val('pin_to_top');
                    $row['content_type'] = 'event';
                    $row['joinerData'] = $event->getRandomJoiners(12);
                    $row['localStart'] = (new DateTime($event->val('start'), $utc_tz))->setTimezone($local_tz);
                    $row['collaboratedWith'] = null;
                    if ($event->val('collaborating_groupids')) {
                        $row['collaboratedWith'] = Group::GetGroups(explode(',',$event->val('collaborating_groupids')),true,false);
                    }
                    $row['rsvp_enabled'] = $event->val('rsvp_enabled');
                }
            } elseif($content['content_type'] == 'post') {
                $post = Post::GetPostFromCache ($content['content_id']);
                if ($post){
                    if ($post->val('zoneid') != $_ZONE->id()) {
                        continue; // Skip Post from other zones that are not collaborative, i.e. global post.
                    }
                    $row['postid'] = $post->val('postid');
                    $row['groupid'] = $post->val('groupid');
                    $row['chapterid'] = $post->val('chapterid');
                    $row['channelid'] = $post->val('channelid');
                    $row['title'] = $post->val('title');
                    $row['post'] = $post->val('post');
                    $row['addedon'] = $post->val('publishdate');
                    $row['pin_to_top'] = $post->val('pin_to_top');
                    $row['isactive'] = $post->val('isactive');
                    $row['publishdate'] = $post->val('publishdate');
                    $row['content_type'] = 'post';
                }

            } elseif ($content['content_type'] == 'newsletter') {
                $newsletter = Newsletter::GetNewsletterFromCache($content['content_id']);
                if ($newsletter){
                    if ($newsletter->val('zoneid') != $_ZONE->id()) {
                        continue; // Skip Newsletter from other zones that are not collaborative, i.e. global newsletter.
                    }
                    $row['newsletterid'] = $newsletter->val('newsletterid');
                    $row['groupid'] = $newsletter->val('groupid');
                    $row['chapterid'] = $newsletter->val('chapterid');
                    $row['channelid'] = $newsletter->val('channelid');
                    $row['newslettername'] = $newsletter->val('newslettername');
                    $row['newsletter'] = $newsletter->val('newsletter');
                    $row['addedon'] = $newsletter->val('publishdate');                    
                    $row['pin_to_top'] = $newsletter->val('pin_to_top');
                    $row['content_type'] = 'newsletter';
                }
            } elseif ($content['content_type'] == 'discussion') {
                if (!in_array('discussion',$include_content_types)){
                    continue;
                }
                $discussions = Discussion::GetDiscussionFromCache($content['content_id']);
                if ($discussions){
                    if ($discussions->val('zoneid') != $_ZONE->id()) {
                        continue;
                    }
                    $row['discussionid'] = $discussions->val('discussionid');
                    $row['groupid'] = $discussions->val('groupid');
                    $row['chapterid'] = $discussions->val('chapterid');
                    $row['channelid'] = $discussions->val('channelid');
                    $row['title'] = $discussions->val('title');
                    $row['discussion'] = $discussions->val('discussion');
                    $row['addedon'] = $discussions->val('createdon');
                    $row['pin_to_top'] = $discussions->val('pin_to_top');
                    $row['content_type'] = 'discussion';
                }
            } elseif ($content['content_type'] == 'album') {
                if (!in_array('album',$include_content_types)){
                    continue;
                }
                $album = Album::GetAlbumFromCache($content['content_id']);

                if($album){
                    $row['albumid'] = $album->val('albumid');
                    $row['groupid'] = $album->val('groupid');
                    $row['chapterid'] = $album->val('chapterid');
                    $row['channelid'] = $album->val('channelid');
                    $row['title'] = $album->val('title');
                    $row['cover'] = $album->val('cover_mediaid');
                    $row['addedon'] = $album->val('addedon');
                    $mediaList = $album->getAlbumMediaListForFeed();
                    $row['media_ids_json'] = json_encode($_COMPANY->encodeIdsInArray(array_column($mediaList,'album_mediaid')));
                    $row['preview_urls'] = array_column($mediaList,'thumbnail_url');
                    $row['album_total_likes'] = $album->getAlbumTotalLikes();
                    $row['album_total_comments'] = $album->getAlbumTotalComments();
                    $row['pin_to_top'] = 0;
                    $row['content_type'] = 'albums';
                }
            }
            $feeds[] = $row;
        }

        return array('feeds'=>$feeds,'contents_count_before_processing'=>$contentsCount);
    }

    public static function GetDiscussionsViewData(int $globalChapterOnly, int $chapterid, int $globalChannelOnly, int $channelid, int $groupid, int $page, int $limit)
    {
        global $_COMPANY;
        global $_USER;
        global $_ZONE;
        global $db;

        $chapterCondition = " ";
        if ($globalChapterOnly) {
            $chapterCondition = " AND chapterid=0";
        } else {
            if ($chapterid > 0) {
                $chapterCondition = " AND FIND_IN_SET({$chapterid},`chapterid`)";
            }
        }

        $channelCondition = " ";
        if ($globalChannelOnly) {
            $channelCondition = " AND channelid=0";
        } else {
            if ($channelid > 0) {
                $channelCondition = " AND channelid='{$channelid}'";
            }
        }

        $active = " AND `isactive`=1";
        $inclueGlobal = '';
       
        
        $max_items = $limit + 1; // We are adding 1 additional row to enable 'Show More to work'
        $start = (($page - 1) * $limit);
        return $db->ro_get(
            "SELECT * FROM discussions WHERE companyid = {$_COMPANY->id()} AND discussions.zoneid={$_ZONE->id()} AND ((groupid={$groupid} $inclueGlobal) {$chapterCondition} {$channelCondition} {$active}) 
                ORDER BY pin_to_top DESC, modifiedon DESC, discussionid DESC
                LIMIT {$start}, {$max_items}"
        );
    }

    public static function GetRecognitionsViewData(int $groupid, int $recognition_type, int $page, int $limit)
    {
        global $_COMPANY;
        global $_USER;
        global $_ZONE;
        global $db;
        
        $max_items = $limit + 1; // We are adding 1 additional row to enable 'Show More to work'
        $start = (($page - 1) * $limit);
        return $db->ro_get(
            "SELECT * FROM `recognitions` WHERE `companyid`='{$_COMPANY->id()}' AND (`zoneid`='{$_ZONE->id()}' AND `groupid`='{$groupid}' AND `isactive`=1)
                ORDER BY modifiedon DESC, recognitionid DESC
                LIMIT {$start}, {$max_items}"
        );
    }

    /**
     * This method extracts extracts replyto email from $_POST['content_replyto_email']
     * if $_POST['content_replyto_email_checkbox'] is set. If validation fails it returns Ajax erro
     * @return mixed
     */
    public static function ValidateAndExtractReplytoEmailFromPostAttribute(): mixed
    {
        global $_COMPANY;
        $content_replyto_email = "";

        if (!empty($_POST['content_replyto_email_checkbox']) && !empty($_POST['content_replyto_email'])) {

            $content_replyto_email = $_POST['content_replyto_email'];
            if (!filter_var($content_replyto_email, FILTER_VALIDATE_EMAIL)) {
                AjaxResponse::SuccessAndExit_STRING(0, '', "The custom reply to email you have entered is not in a valid format!", 'Error!');
            }

            if (strlen($content_replyto_email) > 60) {
                AjaxResponse::SuccessAndExit_STRING(0, '', "Custom reply to email length must be less than 60 characters!", 'Error!');
            }

            if (!$_COMPANY->isValidAndRoutableEmail($content_replyto_email)) {
                AjaxResponse::SuccessAndExit_STRING(0, '', "Custom reply to email you have entered is not a valid and routable email!", 'Error!');
            }
        }
        return $content_replyto_email;
    }

    /**
     * Extracts Service Provider name from the web conference link
     * @param string $web_conference_link URL of web conference
     * @return string empty string is returned if $web_conference_link is empty, if $web_conference_link is invalid, exits with Ajax error
     */
    public static function ValidateAndExtractServiceProvider(string $web_conference_link): string
    {
        $web_conference_sp = '';

        if (!filter_var($web_conference_link, FILTER_VALIDATE_URL)) {
            AjaxResponse::SuccessAndExit_STRING(0,'', gettext("The Web Conference Link is invalid. Please update the Link and try again"), gettext('Error'));
        }

        if ($web_conference_link) {

            if (str_contains($web_conference_link, '.proofpoint.') ||
                str_contains($web_conference_link, '.safelinks.')
            ) {
                AjaxResponse::SuccessAndExit_STRING(2, '', gettext("The link provided in the conference link field seems to be invalid. In order to make sure that participants have success, please copy the link directly from the creator's calendar or from the online meeting service provider."), gettext('Error'));
            }

            $web_conference_sp = Event::GetWebConfSPName($web_conference_link);
        }
        return $web_conference_sp;
    }

    /**
     * Checks if the provided content contains external images, if it does function exits with Ajax error.
     * @param string|null $content
     * @return string updated content
     */
    public static function RedactorContentValidateAndCleanup(?string $content) : string
    {
        try {
            return Html::RedactorContentValidateAndCleanup($content);
        } catch (Exception $e) {
            $error_message = ($e->getCode() == -1)
                ? gettext('The content contains links to external images which may not display properly on all devices. In order to fix this, attach images from your computer.')
                : gettext('Unknown error.');
            AjaxResponse::SuccessAndExit_STRING(0, '', $error_message, gettext('Error'));
        }
        return '';
    }

    /**
     * Checks and validate Eventcustom fields provided in the POST, if validation fails function exits with Ajax error.
     * @return string
     */
    public static function ValidateAndExtractCustomFieldsFromPostAttribute(string $topictype = 'EVT'): string
    {
        global $_COMPANY;
        $topic_class = Teleskope::TOPIC_TYPE_CLASS_MAP[$topictype];
        $custom_fields = call_user_func([$topic_class, 'GetEventCustomFields']);
        $custom_fields_input = array();
        $required = '';
        $visiableIfFields = array();
        foreach ($custom_fields as $custom_field) {
        
            if (!$_COMPANY->getAppCustomization()['event']['custom_fields']['enable_visible_only_if_logic'] && !empty($custom_field['visible_if'])) {
                continue;
            }
            if (isset($_POST['custom_field_' . $custom_field['custom_field_id']]) && !empty($_POST['custom_field_' . $custom_field['custom_field_id']])) {
                // for checkbox, if it has values and dropdown
                if (($custom_field['custom_fields_type'] != 3) && ($custom_field['custom_fields_type'] != 4)) {
                    $mulipleInput = $_POST['custom_field_' . $custom_field['custom_field_id']];
                    
                    $cleanMulipleInput = array();
                    foreach ($mulipleInput as $rawInput) {
                        $cleanMulipleInput[] = intval($rawInput); // This is a ID
                    }
                    // This check is for dropdown and determines if the dorpdown is required and out of "visible if" field scope.
                    if ($custom_field['custom_fields_type'] == 1 && empty(array_filter($mulipleInput)) && empty($custom_field['visible_if']) && $custom_field['is_required'] == 1){  
                        $required .= $custom_field['custom_field_name'] . ', ';
                    }
                    $inputData = array('custom_field_id' => intval($custom_field['custom_field_id']), 'value' => $cleanMulipleInput);
                } else {
                    $inputData = array('custom_field_id' => intval($custom_field['custom_field_id']), 'value' => $_POST['custom_field_' . $custom_field['custom_field_id']]);
                }
            } else {
                // checkbox is added to $required if it's required and out of "visible if" field scope.
                if ($custom_field['is_required'] == 1 && empty($custom_field['visible_if'])) {
                    $required .= $custom_field['custom_field_name'] . ', ';
                }
                $inputData = array('custom_field_id' => intval($custom_field['custom_field_id']), 'value' => array());
            }
            $custom_fields_input[] = $inputData;

            // Custom fields are added to "visible if" fields here and then processed later in foreach loop 
            if (!empty($custom_field['visible_if'])) {
                $visiableIfFields[] = $custom_field;
            }
        }
        foreach($visiableIfFields as  $visiableIfField) { // Visiable if Fields validation
            $checkValue = Arr::SearchColumnReturnColumnVal($custom_fields_input,$visiableIfField['custom_field_id'],'custom_field_id','value');
            if (!is_array($checkValue)){
                $checkValue = array($checkValue); // If text value make is equivalent data type to match value
            }

            $visiableIf = $visiableIfField['visible_if'];
            // Get Parent value
            $getParentValue = Arr::SearchColumnReturnColumnVal($custom_fields_input,$visiableIf['custom_field_id'],'custom_field_id','value');

            if (!empty($getParentValue)) {
                if (!is_array($getParentValue)){
                    $getParentValue = array($getParentValue); // If text value make is equivalent data type to match value
                }
                $getParentValue = array_filter($getParentValue);
                // $checkValue array will be empty if the input field doesn't have data. for dropdown reset($checkValue) == 0 will work. 
                if ($getParentValue == $visiableIf['options'] &&
                    (empty($checkValue) || reset($checkValue) == 0) &&
                    $visiableIfField['is_required'] == 1){
                    $required .= $visiableIfField['custom_field_name'] . ', ';
                }
            }
            
        }

        $required = rtrim($required, ', ');
        
        if ($required) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"), $required), gettext('Error'));
        }

        return json_encode($custom_fields_input);
    }

    public static function ShowTrainingVideoButton(string $page_tags, int $font_size = 34)
    {
        global $_COMPANY;
        if (!$_COMPANY->getAppCustomization()['helpvideos']['enabled']) return '';

        if (TrainingVideo::DoTagsHaveVideos($page_tags)) {
            echo <<< EOMEOM
            <button aria-label="Help Training Video" class="btn btn-no-style"  style="min-width:0; margin: 0!important;padding: 0!important;"  onclick="startTrainingVideoModal('{$page_tags}');">
                <i style="cursor:pointer; font-size:{$font_size}px !important; background-color: #ffffff00; border-radius: 4px; color:#0077b5 !important;" class="fa fa-question-circle mobile-off"></i>
            </btton>
EOMEOM;
        }
    }

    public static function ValidateCircleRolesCapacityInput ()
    {
        $validRoleCapacity = array();
        if (!empty($_POST['circle_roleid'])) {
            $circleCapacity = $_POST['circle_role_capacity'];
            $index = 0;
            $error = "";
            
            foreach($_POST['circle_roleid'] as $id) {
                $role = Team::GetTeamRoleType($id);
                if ($role) {
                    $circleMaxCapacity = $circleCapacity[$index];
                    if (is_numeric($circleMaxCapacity)) {
                        $circleMaxCapacity = intval($circleMaxCapacity);
                        if ($circleMaxCapacity < $role['min_required'] || $circleMaxCapacity > $role['max_allowed']) {
                            $error = sprintf(gettext('Please input an integer value between or equal to %1$s and %2$s'),($role['min_required']> 1 ? $role['min_required'] : 1), $role['max_allowed']);
                            break;
                        }

                        $validRoleCapacity[$id] = array('role_max_capacity'=> $role['max_allowed'], 'circle_role_max_capacity' => $circleMaxCapacity);

                    } else {
                        $error = sprintf(gettext('Please input an integer value between or equal to %1$s and %2$s'),($role['min_required']> 1 ? $role['min_required'] : 1), $role['max_allowed']);
                        break;
                    }
                }
                $index++;
            }
            if ($error) {
                AjaxResponse::SuccessAndExit_STRING(0, '', $error, 'Error!');
            }
        }

        return $validRoleCapacity;
    }

    /**
     * Gets values for group_category_id based on $_USER->getUserPreference(UserPreferenceType::ZONE_SelectedGroupCategory) and $_GET['filter']
     * @return array [$groupCategoryRows, $groupCategoryIds, $group_category_id]
     */
    public static function InitGroupCategoryVariables(bool $includeDefaultRow=true): array
    {
        global $_USER, $_ZONE;
        // check for group categories
        $groupCategoryRows = Group::GetAllGroupCategoriesByZone($_ZONE, true, true);
        $groupCategoryIds = Arr::IntValues(array_column($groupCategoryRows, 'categoryid'));

        // If filter is explicitly set in the request use it
        if (isset($_GET['filter']) && (in_array($_GET['filter'], [0, 'all']) || in_array($_GET['filter'], $groupCategoryIds))) {
            if (in_array($_GET['filter'], [0, 'all'])) {
                $group_category_id = 0;
            } else {
                $group_category_id = (int)$_GET['filter'];
            }
            $_USER->setUserPreference(UserPreferenceType::ZONE_SelectedGroupCategory, $group_category_id);
        }
        // else try to get from user preferences if set.
        elseif ($_USER->getUserPreference(UserPreferenceType::ZONE_SelectedGroupCategory) !== null) { // Case when session exists
            $group_category_id = (int)$_USER->getUserPreference(UserPreferenceType::ZONE_SelectedGroupCategory);
            if(!in_array($group_category_id, [0, 'all']) && !in_array($group_category_id, $groupCategoryIds)){
                $group_category_id = (int)Group::GetDefaultGroupCategoryRow()['categoryid'];
            }
        }
        // else set it to all.
        else {
            $group_category_id = 0; //(int)Group::GetDefaultGroupCategoryRow()['categoryid'];
        }
        return [$groupCategoryRows, $groupCategoryIds, $group_category_id];
    }

    /**
     * Initializes values for in person and online capacity limits based on the following request paramters
     *  $_POST['max_inperson'], $_POST['max_online'], $_POST['max_inperson_waitlist'], $_POST['max_online_waitlist'],
     * $_POST['eventvanue'], $_POST['vanueaddress'], $_POST['web_conference_link'], $_POST['web_conference_detail'], etc
     * @return array
     */
    public static function GetEventWhereValues(?Event $event): array
    {
        global $db;
        global $_COMPANY;
        $event_attendence_type = (int)($_POST['event_attendence_type'] ?? 0);
        $eventvanue = '';
        $vanueaddress = '';
        $venue_info = '';
        $venue_room = '';
        $web_conference_link = '';
        $web_conference_detail = '';
        $checkin_enabled = $_COMPANY->getAppCustomization()['event']['checkin_default'] ? 1 : 0;
        $max_inperson = 0;
        $max_inperson_waitlist = 0;
        $max_online = 0;
        $max_online_waitlist = 0;
        $web_conference_sp = '';
        $calendar_blocks = (int)($_POST['calendar_blocks'] ?? 1);

        // Validation for In-Person Events
        if (in_array($event_attendence_type, [1, 3])) {
            $eventvanue = $_POST['eventvanue'] ?? '';
            $vanueaddress = $_POST['vanueaddress'] ?? '';
            $venue_info = trim($_POST['venue_info'] ?? '');
            $venue_room = trim($_POST['venue_room'] ?? '');
            if (empty($eventvanue)) {
                AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"), gettext('Venue')), gettext('Error'));
            }

            if (!empty($_POST['participation_onoff'])) {

                $max_inperson = (int)($_POST['max_inperson'] ?? 0);

                if (!empty($_POST['inperson_limit_unlimited'])) {
                    $max_inperson = Event::MAX_PARTICIPATION_LIMIT;
                }

                if ($event?->isParticipationLimitUnlimited('max_inperson') && $event?->isPublished()) {
                    $max_inperson = Event::MAX_PARTICIPATION_LIMIT;
                }

                if ($max_inperson < 1) {
                    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("In Person limit cannot be zero or negative"), gettext('Error'));
                }

                $max_inperson_waitlist = (int)($_POST['max_inperson_waitlist'] ?? 0);

                if (!empty($_POST['inperson_waitlist_unlimited'])) {
                    $max_inperson_waitlist = Event::MAX_PARTICIPATION_LIMIT;
                }

                if ($event?->isParticipationLimitUnlimited('max_inperson_waitlist') && $event?->isPublished()) {
                    $max_inperson_waitlist = Event::MAX_PARTICIPATION_LIMIT;
                }

                if ($max_inperson_waitlist < 0) {
                    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("In Person waitlist limit cannot be negative"), gettext('Error'));
                }
            }

            if ($event ?-> isPublished()) {

                // For published events there are certain constraints on changing venue type
                if ($event_attendence_type == 1) {
                    if ($event->val('event_attendence_type') == 2 && $event->isLimitedCapacity())
                        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Unable to change Online event to In Person event"), gettext('Error'));
                    elseif ($event->val('event_attendence_type') == 3 && $event->isLimitedCapacity())
                        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Unable to change In Person & Online event to In Person only event"), gettext('Error'));
                }

                $error = $event->validateAndGetError([
                    'max_inperson' => $max_inperson,
                ]);

                if ($error) {
                    AjaxResponse::SuccessAndExit_STRING(0, '', $error, gettext('Error'));
                }
            }
        }

        // Validation for Online Events
        if (in_array($event_attendence_type, [2, 3])) {
            $web_conference_link = $_POST['web_conference_link'] ?? '';
            $web_conference_sp = ViewHelper::ValidateAndExtractServiceProvider($web_conference_link);
            $checkin_enabled = (int)($_POST['checkin_enabled'] ?? 0);

            $web_conference_detail = $_POST['web_conference_detail'] ?? '';
            $web_conference_detail = str_replace('\n', '<br>', str_replace('\r\n', '<br>', $web_conference_detail));
            
            if (!empty($_POST['participation_onoff'])) {

                $max_online = (int)($_POST['max_online'] ?? 0);

                if (!empty($_POST['online_limit_unlimited'])) {
                    $max_online = Event::MAX_PARTICIPATION_LIMIT;
                }

                if ($event?->isParticipationLimitUnlimited('max_online') && $event?->isPublished()) {
                    $max_online = Event::MAX_PARTICIPATION_LIMIT;
                }

                if ($max_online < 1) {
                    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Online limit cannot be zero or negative"), gettext('Error'));
                }

                $max_online_waitlist = (int)($_POST['max_online_waitlist'] ?? 0);

                if (!empty($_POST['online_waitlist_unlimited'])) {
                    $max_online_waitlist = Event::MAX_PARTICIPATION_LIMIT;
                }

                if ($event?->isParticipationLimitUnlimited('max_online_waitlist') && $event?->isPublished()) {
                    $max_online_waitlist = Event::MAX_PARTICIPATION_LIMIT;
                }

                if ($max_online_waitlist < 0) {
                    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Online waitlist limit cannot be negative"), gettext('Error'));
                }
            }

            if ($event ?-> isPublished()) {
                // For published events there are certain constraints on changing venue type
                if ($event_attendence_type == 2) { // Business rule Change from In Person or Hybrid events to Online only is not allowed

                    if ($event->val('event_attendence_type') == 1 && $event->isLimitedCapacity()) {
                        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Unable to change In Person event to Online event"), gettext('Error'));
                    } elseif ($event->val('event_attendence_type') == 3 && $event->isLimitedCapacity()) {
                        AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Unable to change In Person & Online event to Online only event"), gettext('Error'));
                    }
                }

                $error = $event->validateAndGetError([
                    'max_online' => $max_online,
                ]);

                if ($error) {
                    AjaxResponse::SuccessAndExit_STRING(0, '', $error, gettext('Error'));
                }
            }
        }

        // Validation for Other Events
        if ($event_attendence_type == 4) {
            if ($event ?-> isPublished()) {
                // For published events there are certain constraints on changing venue type
                if ($event->val('event_attendence_type') != 4) {
                    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Unable to change event to Other Type"), gettext('Error'));
                }
            }
        }

        return array(
            $event_attendence_type,
            $eventvanue,
            $vanueaddress,
            $venue_info,
            $venue_room,             
            $web_conference_link,
            $web_conference_detail,
            $checkin_enabled,
            $max_inperson,
            $max_inperson_waitlist,
            $max_online,
            $max_online_waitlist,
            $web_conference_sp,
            $calendar_blocks,
        );
    }

    public static function GetEventWhenValues(): array
    {
        global $db;
        #Time zone
        $multiDayEvent = 0;
        $check = array();

        $check = array(
            gettext('Event Start Date') => @$_POST['eventdate'],
            gettext('Time Start Hours') => @$_POST['hour'],
            gettext('Timezone') => @$_POST['timezone'],
        );

        if (!empty($_POST['multiDayEvent'])) {
            $check = array_merge($check, array(
                    gettext('Event End Date') => @$_POST['end_date'],
                    gettext('Event End Time') => @$_POST['end_hour']
                )
            );
            $multiDayEvent = 1;
        } else {
            $check = array_merge($check, array(
                    gettext('Event Duration') => @$_POST['hour_duration']
                )
            );
        }

        $checkrequired = $db->checkRequired($check);
        if ($checkrequired) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"), $checkrequired), gettext('Error'));
        }

        $event_tz = null;
        if (isValidTimeZone($_POST['timezone'] ?? '')) {
            $event_tz = $_POST['timezone'];
        }
        if (empty($event_tz)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf (gettext('Invalid Timezone "%s" provided'), htmlspecialchars($_POST['timezone'])), gettext('Error'));
        }

        $current_timestamp = time();

        #Event Start Date time
        $eventdate = $_POST['eventdate'];
        $hour = $_POST['hour'] ?? '00';
        $minutes = $_POST['minutes'] ?? '00';
        $period = $_POST['period'];
        $startformat = $eventdate . " " . $hour . ":" . $minutes . " " . $period;
        $start = $db->covertLocaltoUTC("Y-m-d H:i:s", $startformat, $event_tz);

        #Event End Date time
        $allow_past_date_event = (int)$_POST['allow_past_date_event'] ?? 0;
        if ($multiDayEvent) {
            $end_date = $_POST['end_date'];
            $end_hour = $_POST['end_hour'] ?? '00';
            $end_minutes = $_POST['end_minutes'] ?? '00';
            $end_period = $_POST['end_period'];
            $endformat = $end_date . " " . $end_hour . ":" . $end_minutes . " " . $end_period;
            $start_timestamp = strtotime($startformat . ' ' . $event_tz);
            $end_timestamp = strtotime($endformat . ' ' . $event_tz);
            #Check if event start date and end date are valid

            if ($start_timestamp === false) {
                AjaxResponse::SuccessAndExit_STRING(-11, '', gettext("Start date time format is not correct!"), gettext('Error'));
            }

            if ($end_timestamp === false) {
                AjaxResponse::SuccessAndExit_STRING(-11, '', gettext("End date time format is not correct!"), gettext('Error'));
            }

            if (!$allow_past_date_event && (($start_timestamp < $current_timestamp) || ($end_timestamp < $current_timestamp))) {
                AjaxResponse::SuccessAndExit_STRING(-1, '', gettext("Start or End time cannot be in the past"), gettext('Error'));
            }

            if ($end_timestamp < $start_timestamp) {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Event end date time cannot be earlier than start date time"), gettext('Error'));
            }

            if ($end_timestamp - $start_timestamp <= 86400) {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Multi-day events duration must be more than 24 hours"), gettext('Error'));
            }
        } else {
            $hour_duration = $_POST['hour_duration'] ?? '00';
            $minutes_duration = $_POST['minutes_duration'] ?? '00';
            if ($hour_duration == '24') {
                $minutes_duration = '00';
            }
            $add_time = "+" . $hour_duration . " hour +" . $minutes_duration . " minutes";
            $endformat = date('Y-m-d H:i:s', strtotime($add_time, strtotime($startformat)));
            $start_timestamp = strtotime($startformat . ' ' . $event_tz);
            $end_timestamp = strtotime($add_time, $start_timestamp);

            if ($start_timestamp === false || $end_timestamp === false) {
                AjaxResponse::SuccessAndExit_STRING(-1, '', gettext("Start or End date time format is not correct!"), gettext('Error'));
            }

            #Check if event start date and end date are valid
            if (!$allow_past_date_event && (($start_timestamp < $current_timestamp) || ($end_timestamp < $current_timestamp))) {
                AjaxResponse::SuccessAndExit_STRING(-1, '', gettext("Start or End time cannot be in the past"), gettext('Error'));
            }
        }
        $end = $db->covertLocaltoUTC("Y-m-d H:i:s", $endformat, $event_tz);
        return array($event_tz, $start, $end);
    }

    public static function ValidateObjectVersionMatchesWithPostAttribute(?Object $object): void
    {
        global $_COMPANY;
        if ($object) {
            $version = (int)$_COMPANY->decodeId($_POST['version'] ?? '');
            $custom_name = $object::GetCustomName();
            if ($object->val('version') > $version) {
                AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Unable to save due to outdated version. To avoid losing your changes, copy them locally, re-edit "%s", and try again.'), $custom_name), gettext('Error'));
            }
        }
    }
    
    public static function ValidateEventFormModules(?Event $event)
    {
        global $_COMPANY;
        

        if (!$event) {
            return ;
        }

        // For Volunteers
        if (isset($_POST['volunteerSwitch']) && $_COMPANY->getAppCustomization()['event']['volunteers'])  {

            if (empty($event->getEventVolunteerRequests())) {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Volunteer roles are required because the volunteer setting is enabled. Please add volunteer requests or disable this setting."), gettext('Error'));
            }
        }

        // For Speakers
        if (isset($_POST['speakerSwitch']) && $_COMPANY->getAppCustomization()['event']['speakers']['enabled']) {
            if (empty($event->getEventSpeakers())) {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Event Speakers are required because the speakers setting is enabled. Please add speakers or disable this setting."), gettext('Error'));
            }
        }

        // For event budget
        if (isset($_POST['budgetSwitch']) && $_COMPANY->getAppCustomization()['event']['budgets'])  {
            if (!($event->getEventBudgetedDetail())) {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Event budget is required because the budget setting is enabled. Please add budget or disable this setting."), gettext('Error'));
            }
        }

        // For Partner Organization
        if ($_COMPANY->getAppCustomization()['event']['partner_organizations']['enabled']) {
            if ($_COMPANY->getAppCustomization()['event']['partner_organizations']['is_required'] && !$event->getAssociatedOrganization()) {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("An event partner organization is required. Please add at least one organization."), gettext('Error'));
            }
        }
    }

    public static function ValidateTopicCollaboration(string $topicType, int $topicId, int $groupid) 
    {
        global $_COMPANY,$_ZONE, $_USER;
        $collaboratingGroupIds = array();
        $collaboratingGroupIdsPending = array();
        $requestEmailApprovalGroups = array();

        $topic = null;
        if ($topicId){
            if ($topicType == 'EVT') {
                $topicObj = Event::GetEvent($topicId);
                $topic = $topicObj->toArray();
            }
        }

        $topicEnglish = strtolower(Teleskope::TOPIC_TYPES_ENGLISH[$topicType]);
        
        if (empty($_POST['collaborating_groupids']) || (count($_POST['collaborating_groupids'])+($groupid ? 1 : 0)) < 2) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('In order to create a collaborating %1$s, please add minimum of two %2$s to continue.'),$topicEnglish, $_COMPANY->getAppCustomization()['group']['name-short']), gettext('Error!'));
        }

        if ($groupid){
            if ( $_USER->canCreateContentInGroupOnly($groupid) || $_USER->canPublishContentInGroupOnly($groupid)){
                $collaboratingGroupIds[] = $groupid;
            } else { // Append $groupid to the post array to ensure data consistency.
                $_POST['collaborating_groupids'][] = $_COMPANY->encodeId($groupid);
            }
        }

        $existingCollaboratingGroupIds = $topic ? explode(',',$topic['collaborating_groupids']) : array();
        $collaborating_groupids_array = $_COMPANY->decodeIdsInArray($_POST['collaborating_groupids']);

        if ($topic) {
            $zoneid = $topic['zoneid'];
        } else {
            $zoneid = $_ZONE->id();
        }
        $groupIdsforCheckCurrentZone = $collaborating_groupids_array;
        if ($groupid){ // include current zone
            $groupIdsforCheckCurrentZone[] = $groupid;
        }
        if (!Group::IsGroupInCurrentZone($groupIdsforCheckCurrentZone,$zoneid)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('For %3$s collaboration, you must select at least one %1$s from your current zone\'s %2$s.'),$_COMPANY->getAppCustomization()['group']['name-short'], $_COMPANY->getAppCustomization()['group']['name-short-plural'], $topicEnglish), gettext('Error'));
        }
       
        // Get collaborating chapters
        [$collaborating_chapterids, $collaborating_chapterids_pending] = self::ValidateTopicFormCollaboratingChapters($topicType, $topic, $groupid);

        foreach($collaborating_groupids_array as $c_groupid) {
            if ((($cgroup = Group::GetGroup($c_groupid)) !== null)) {

                if (in_array($c_groupid,$existingCollaboratingGroupIds) && empty($collaborating_chapterids_pending)) {
                    $collaboratingGroupIds[] = $c_groupid;
                    continue; // Already accepted, do not invite again
                }

                /**
                 * Commiting out following logic because: NOW If any chapter is approved then auto approve parent group : Discussed in 27th Nov,2024 Engineering call

                if (!empty($collaborating_chapterids_pending)) { //if pending chapters approvel then mark parent group to need approvel

                    $groupChapters = Group::GetGroupChaptersFromChapterIdsCSV($c_groupid, implode(',',$collaborating_chapterids_pending) );
                   
                    if (!empty($groupChapters)){
                        $collaboratingGroupIdsPending[] = $c_groupid;
                        $requestEmailApprovalGroups[] = $cgroup;
                        continue;
                    }
                }
                **/

                if (
                    $_USER->isCompanyAdmin()
                    ||
                    $_USER->isZoneAdmin($cgroup->val('zoneid'))
                    ||
                    $_USER->canCreateContentInGroupOnly($c_groupid) 
                    ||
                    $_USER->canPublishContentInGroupOnly($c_groupid)
                    ||
                    $_USER->isRegionallead($c_groupid)
                ) {
                    $collaboratingGroupIds[] = $c_groupid;
                } else {
                    if (!empty($collaborating_chapterids) || !empty($collaborating_chapterids_pending)) {
                        $getGroupApprovedChapters = Group::GetGroupChaptersFromChapterIdsCSV($c_groupid, implode(',',$collaborating_chapterids));
                        $getGroupPendingApprovalChapters = Group::GetGroupChaptersFromChapterIdsCSV($c_groupid, implode(',',$collaborating_chapterids_pending));
                        
                        if (!empty($getGroupApprovedChapters )) { // If any chapter is approved then auto approve parent group : Discussed in 27th Nov,2024 Engineering call
                            $collaboratingGroupIds[] = $c_groupid;
                        } elseif(!empty($getGroupPendingApprovalChapters)){
                            $collaboratingGroupIdsPending[] = $c_groupid;
                            $requestEmailApprovalGroups[] = $cgroup;
                        } else {
                            if (!in_array($c_groupid,$existingCollaboratingGroupIds)){ // if this group not already approved then only add it to pending 
                                $collaboratingGroupIdsPending[] = $c_groupid;
                                $requestEmailApprovalGroups[] = $cgroup;
                            }
                        }

                    } else {
                        $collaboratingGroupIdsPending[] = $c_groupid;
                        $requestEmailApprovalGroups[] = $cgroup;
                    }
                }
            }
        }
        sort($collaboratingGroupIds);
        if ($topic) {
            [$collaboratingGroupIds, $collaboratingGroupIdsPending, $requestEmailApprovalGroups,$collaborating_chapterids, $collaborating_chapterids_pending] = self::FinalizeGroupChapterTopicCollaborationValidation($topicType, $topic, $groupid, $collaboratingGroupIds, $collaboratingGroupIdsPending, $requestEmailApprovalGroups,$collaborating_chapterids, $collaborating_chapterids_pending);
        }

        if (!in_array($groupid,array_merge($collaboratingGroupIds, $collaboratingGroupIdsPending))) {
            $gName = Group::GetGroupName($groupid);
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('%1$s %2$s cannot be removed from the %3$s collaboration.'), $gName , $_COMPANY->getAppCustomization()['group']['name-short'], $topicEnglish), gettext('Error!'));
        }

        return array($collaboratingGroupIds, $collaboratingGroupIdsPending, $requestEmailApprovalGroups,$collaborating_chapterids, $collaborating_chapterids_pending);
    }


    /**
     * This method should be called before saving topic updates for collaborating topics. The reason is
     * In case of chapter collaboration we save corresponding groupids in collaborating_groupids or
     * collaborating_groupids_pending section. Now if there was a group added to collaborating_groupids or
     * collaborating_groupids_pending as a result of someone adding a chapter for collaboration and not if that
     * chapter has been removed then we need remove that group from collaborating_groupids and
     * collaborating_groupids_pending column of the topic.
     * There are some known side effects e.g. where Group G_X, G_Y, and G_Z (chapter C_a) were collaborating and then
     * chapter C_b of Group G_Y was added and then chapter C_b was removed. The resulting state would by G_X, G_Z (C_a)
     *
     * @param  string $topicType
     * @param  array $topic
     * @param int $groupid
     * @param array $collaboratingGroupIds
     * @param array $collaboratingGroupIdsPending
     * @param array $requestEmailApprovalGroups
     * @param array $collaborating_chapterids
     * @param array $collaborating_chapterids_pending
     * @return array
     */
    public static function FinalizeGroupChapterTopicCollaborationValidation(string $topicType, ?array $topic, int $groupid, array $collaboratingGroupIds, array $collaboratingGroupIdsPending, array $requestEmailApprovalGroups, array $collaborating_chapterids, array $collaborating_chapterids_pending)
    {
        global $_COMPANY,$_ZONE, $_USER;
        $topicEnglish = strtolower(Teleskope::TOPIC_TYPES_ENGLISH[$topicType]);

        // Get a list of chapterids and collaborating_chapterids_pending already set in the topic
        $oldChapterids = array_filter(explode(',',$topic['chapterid']));
        $oldCollaborating_chapterids_pending = array_filter(explode(',',$topic['collaborating_chapterids_pending']??''));

        // If there are chapterids and collaborating_chapterids_pending already set in the topic,
        // then next we want to check if some of those chapters have been removed from the new list
        if (empty($collaborating_chapterids && empty($collaborating_chapterids_pending))) {
            ; // do nothing
        } elseif (!empty($oldChapterids) || !empty($oldCollaborating_chapterids_pending )) {
            $oldApprovedAndPendingCId = array_merge($oldChapterids, $oldCollaborating_chapterids_pending);
            $newApprovedAndPendingCId = array_merge($collaborating_chapterids, $collaborating_chapterids_pending);

            $removedChapterIds = array_diff($oldApprovedAndPendingCId, $newApprovedAndPendingCId); // Get Revmoved Chapters
            // If chapters were removed from the group, then we will remove the corresponidng group from
            // collaborating groupids and pending collaborating groupids if there are no other chapters for removed
            // groups in the new pending or collaborating chapters.
            $removedGroupName = array();
            $removedChaptersName = array();
            if (!empty($removedChapterIds)) {
                foreach($removedChapterIds as $chapterid) {
                    if ($chapterid){
                        $chapterDetail = Group::GetChapterNamesByChapteridsCsv($chapterid); // Get groupid of removed chapter
                        if ($chapterDetail){
                            $chapterGroupid = $chapterDetail[0]['groupid'];
                            $gName = $chapterDetail[0]['groupname'];
                            $cName = $chapterDetail[0]['chaptername'];
                            $getGroupApprovedChapters = Group::GetGroupChaptersFromChapterIdsCSV($chapterGroupid, implode(',',$newApprovedAndPendingCId)); // Check if there are no other chapter selected for this group
                            if (empty($getGroupApprovedChapters)) {

                                if ($chapterGroupid == $groupid) {
                                   AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Atleast one %2$s for %3$s %4$s is required for the collaboration. Please add a %2$s from the %4$s %3$s to continue."'),$cName,$_COMPANY->getAppCustomization()['chapter']['name-short'], $_COMPANY->getAppCustomization()['group']['name-short'], $gName), gettext('Error!'));

                                    break;
                                }
                               
                                if(($key = array_search($chapterGroupid, $collaboratingGroupIds)) !== false) {
                                    unset($collaboratingGroupIds[$key]);
                                    sort($collaboratingGroupIds);
                                    $removedGroupName[] = $gName;
                                    $removedChaptersName[] = $cName;
                                } elseif(($key = array_search($chapterGroupid, $collaboratingGroupIdsPending)) !== false) {
                                    unset($collaboratingGroupIdsPending[$key]);
                                    sort($collaboratingGroupIdsPending);
                                    $removedGroupName[] = $gName;
                                    $removedChaptersName[] = $cName;
                                }
                            }
                        }
                    }
                }
                // Recalculate email approval groups
                $requestEmailApprovalGroups = array();
                foreach($collaboratingGroupIdsPending as $gid) {
                    $requestEmailApprovalGroups[] = Group::GetGroup($gid);
                }
            }
        }

        if ((count($collaboratingGroupIds) + count($collaboratingGroupIdsPending)) < 2) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('As you have removed the [%1$s] %2$s from this %5$s collaboration, the parent [%4$s] %3$s also needs to be removed. Therefore, please remove these %3$s and ensure that, after their removal, at least two %3$s remain to continue with the %5$s collaboration.'), implode(', ',array_unique($removedChaptersName)),$_COMPANY->getAppCustomization()['chapter']['name-short'], $_COMPANY->getAppCustomization()['group']['name-short'], implode(', ', array_unique($removedGroupName)), $topicEnglish), gettext('Error!'));

        } elseif(!empty($removedGroupName)) {

            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('As you have removed the [%1$s] %2$s from this %5$s collaboration, the parent [%4$s] %3$s also needs to be removed. Therefore, please remove these %3$s and submit again.'), implode(', ',array_unique($removedChaptersName)),$_COMPANY->getAppCustomization()['chapter']['name-short'], $_COMPANY->getAppCustomization()['group']['name-short'], implode(', ', array_unique($removedGroupName)),$topicEnglish), gettext('Error!'));
        }
        return array($collaboratingGroupIds, $collaboratingGroupIdsPending, $requestEmailApprovalGroups, $collaborating_chapterids, $collaborating_chapterids_pending);
    }


    /**
     * Return a new list of chapter ids that are approved and pending. The list is based on newly requested collaborating chapters.
     * - Any chapter that was previously approved is added to the approved chapter list.
     * - Any chapter that can be approved based on signed in users permissions is added to the approved chapter list.
     * - Everything else is added to the pending chapter list.
     * @param  string $topicType
     * @param  array|null $topic
     * @param  int $groupid
     * @return []
     */    
   
    public static function ValidateTopicFormCollaboratingChapters(string $topicType, ?array $topic, int $groupid)
    {
        global $_COMPANY, $_USER;

        $topicEnglish = strtolower(Teleskope::TOPIC_TYPES_ENGLISH[$topicType]);

        $existingApprovedChapters = array();
        $groupidsArray = $_COMPANY->decodeIdsInArray($_POST['collaborating_groupids']);
        if ($topic) {
            $groupIdsCsv = implode(',', $groupidsArray);
            $existingApprovedChapters = explode(',', $topic['chapterid']);
        } else {
            $groupIdsCsv = $groupid;
        }

        // The return values
        $collaborating_chapterids = array();
        $collaborating_chapterids_pending = array();

        $collaborating_chapterids_array = $_COMPANY->decodeIdsInArray($_POST['collaborating_chapterids'] ?? array());
        if (((!$_USER->isOnlyChaptersLeadCSV($groupIdsCsv) && !$_USER->isRegionallead($groupid))) && empty($collaborating_chapterids_array)) { // Do not proccess chapter collaboration and pass validation
            return array($collaborating_chapterids, $collaborating_chapterids_pending);
        }

        if ($groupid) {
            $groupIdsCsv = $groupIdsCsv.','.$groupid; //Append the parent group ID to check if isOnlyChaptersLeadCSV applies, as the host group is in a read-only state on the select dropdown.
        }

        if ($topic && $_USER->isOnlyChaptersLeadCSV($groupIdsCsv) && empty($collaborating_chapterids_array)) { // 
            AjaxResponse::SuccessAndExit_STRING(-3, '', sprintf(gettext('Please select %1$s for collaboration, as you have permission to initiate %1$s level %2$s collaboration.'), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'], $topicEnglish), gettext('Error'));
        } elseif ($groupid && ($_USER->isOnlyChaptersLeadCSV($groupid))) { // Validate if user is only chapter lead in Host Group
            $chapters = Group::GetChapterList($groupid);
            $hostChapterValidated = false;
            foreach ($chapters as $chapter) {
                if (in_array($chapter['chapterid'], $collaborating_chapterids_array) && ($_USER->canCreateContentInGroupChapterV2($chapter['groupid'], $chapter['regionids'], $chapter['chapterid']) || $_USER->canPublishContentInGroupChapterV2($chapter['groupid'], $chapter['regionids'], $chapter['chapterid']))) {
                    $hostChapterValidated = true;
                    break;
                }
            }

            if (!$hostChapterValidated) {
                if ($_USER->isRegionallead($groupid)) {
                    AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('In order to create a collaborative %3$s, please add at least one %1$s for which you have \'Regional Lead Rights\' from the host %2$s to proceed.'), $_COMPANY->getAppCustomization()['chapter']['name-short'], $_COMPANY->getAppCustomization()['group']['name-short-plural'], $topicEnglish), gettext('Error!'));
                } else {
                    AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('In order to create a collaborative %3$s, please add at least one %1$s for which you have \'Can Create Rights\' from the host %2$s to proceed.'), $_COMPANY->getAppCustomization()['chapter']['name-short'], $_COMPANY->getAppCustomization()['group']['name-short-plural'],  $topicEnglish), gettext('Error!'));
                }
            }
        }
        $chaptersList = Group::GetChapterNamesByChapteridsCsv(implode(',', $collaborating_chapterids_array));

        foreach ($chaptersList as $chapter) {
            if (!$_USER->canCreateContentInGroupChapterV2($chapter['groupid'], $chapter['regionids'], $chapter['chapterid'])) {
                if (in_array($chapter['chapterid'], $existingApprovedChapters)) { // If already approved
                    $collaborating_chapterids[] = $chapter['chapterid'];
                } else {
                    $collaborating_chapterids_pending[] = $chapter['chapterid'];
                }
            } else {
                $collaborating_chapterids[] = $chapter['chapterid'];
            }
        }
        sort($collaborating_chapterids);
        sort($collaborating_chapterids_pending);
        return array($collaborating_chapterids, $collaborating_chapterids_pending);
    }

    public static function ValidateTopicGroupCollaborationApproverSelection(array $requestEmailApprovalGroups, array $requestEmailApprovalChapterids)
    {
        global $_COMPANY;
        $validationFailed  = false;
        foreach ($requestEmailApprovalGroups as $g) {

            $chapterLevelApproval = Group::GetGroupChaptersFromChapterIdsCSV($g->id(), implode(',',$requestEmailApprovalChapterids));

            if ( empty($chapterLevelApproval) && 
                !empty($g->getGroupApproversToAcceptTopicCollaborationInvites()) &&
                empty($_POST['approversEmails_'.$_COMPANY->encodeId($g->id())])
            ) {
                $validationFailed = true;
                break;
            }
        }

        if ($validationFailed ) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('To approve collaboration, ensure at least one approver is assigned to each %s where approvers are available.'), $_COMPANY->getAppCustomization()['group']['name-short']), gettext('Error'));
        }
        self::ValidateTopicChapterCollaborationApproverSelection($requestEmailApprovalChapterids);
    }

    public static function ValidateTopicChapterCollaborationApproverSelection(array $requestEmailApprovalChapterids)
    {
        global $_COMPANY;
        $validationFailed  = false;
        foreach ($requestEmailApprovalChapterids as $chapterid) {

            if (
                $chapterid &&
                !empty(Group::GetChaptersApproversToAcceptTopicCollaborationInvites($chapterid)) &&
                empty($_POST['approversEmails_chapter_'.$_COMPANY->encodeId($chapterid)])
            ) {
                $validationFailed = true;
                break;
            }
        }

        if ($validationFailed ) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('To approve %1$s collaboration, ensure at least one approver is assigned to each %1$s where approvers are available.'), $_COMPANY->getAppCustomization()['chapter']['name-short']), gettext('Error'));
        }
    }

    public static function SendTopicCollborationApprovalRequest(string $topicType,int $topicId, array $collaborating_groupids_pending, array $collaborating_chapterids_pending)
    {
        global $_COMPANY;

        $emailsSent = array();

        if (!empty($collaborating_groupids_pending)){
            foreach ($collaborating_groupids_pending as $g) {
                if (isset($_POST['approversEmails_'.$_COMPANY->encodeId($g->id())]) && !empty($_POST['approversEmails_'.$_COMPANY->encodeId($g->id())])){
                    $approversEmails = array_unique($_POST['approversEmails_'.$_COMPANY->encodeId($g->id())]);
                    if (!empty($approversEmails)) {
                        $emailsSent = array_merge($emailsSent, $approversEmails);
                        $g->inviteGroupToCollaborateOnTopic($topicType, $topicId, implode(',',$approversEmails));
                    }
                }
            }
        }
        if (!empty($collaborating_chapterids_pending)) {
            $chapters = Group::GetChapterNamesByChapteridsCsv(implode(',',$collaborating_chapterids_pending));
            foreach($chapters as $chapter) {
                if (isset($_POST['approversEmails_chapter_'.$_COMPANY->encodeId($chapter['chapterid'])]) && !empty($_POST['approversEmails_chapter_'.$_COMPANY->encodeId($chapter['chapterid'])])){
                    $chapterName = ' ('.$chapter['chaptername'].') ';
                    $g = Group::GetGroup($chapter['groupid']);
                    $approversEmails  = array_unique($_POST['approversEmails_chapter_'.$_COMPANY->encodeId($chapter['chapterid'])]);
                    if (!empty($approversEmails)) {
                        $emailsSent = array_merge($emailsSent, $approversEmails);
                        $g->inviteGroupToCollaborateOnTopic($topicType, $topicId, implode(',', $approversEmails), $chapterName);
                    }
                }
            }
        }

        return array_unique($emailsSent);
    }

    /**
     * Validates and processes collaboration denial for an event, ensuring the event remains valid after denial.
     * This function uses existing collaboration validation patterns to maintain system consistency.
     * 
     * @param int $eventid The event ID
     * @param array $newCollaboratingGroupIds Updated collaborating group IDs after denial
     * @param array $newPendingGroupIds Updated pending group IDs after denial
     * @param array $newPendingChapterIds Updated pending chapter IDs after denial
     * @return array Returns [isValid, updatedEvent, validationMessage, shouldReload]
     */
    public static function ValidateCollaborationAfterDenial(int $eventid, array $newCollaboratingGroupIds, array $newPendingGroupIds, array $newPendingChapterIds)
    {
        global $_COMPANY, $_ZONE, $_USER;
        
        $event = Event::GetEvent($eventid);
        if (!$event) {
            return [false, null, gettext("Event not found."), false];
        }

        // Get current event state
        $currentApprovedChapterIds = array_filter(explode(',', $event->val('chapterid') ?? ''));
        $hostGroupId = (int)$event->val('groupid');
        
        // Calculate total remaining groups (approved + pending)
        $totalRemainingGroups = count($newCollaboratingGroupIds) + count($newPendingGroupIds);
        
        // Scenario 1: Event converted to single-group event (less than 2 groups remaining)
        if ($totalRemainingGroups < 2) {
            if ($hostGroupId > 0) {
                // Convert to single-group event following existing patterns from ValidateEventFormInputs
                $updatedEvent = [
                    'event_scope' => 'group',
                    'collaborating_groupids' => $hostGroupId,
                    'collaborating_groupids_pending' => '',
                    'collaborating_chapterids_pending' => '', // Clear all pending chapters
                    'listids' => 0,
                    'invited_groups' => $hostGroupId,
                    'groupid' => $hostGroupId // Ensure primary group is set
                ];
                
                return [
                    true, 
                    $updatedEvent, 
                    gettext("Collaboration denied. Event has been converted to a single-group event."),
                    true // Suggest page reload for single-group conversion
                ];
            } else {
                // No valid host group - validate if we can determine a suitable host
                if (!empty($newCollaboratingGroupIds)) {
                    // Use first remaining approved group as host
                    $newHostGroupId = $newCollaboratingGroupIds[0];
                    $updatedEvent = [
                        'event_scope' => 'group',
                        'collaborating_groupids' => $newHostGroupId,
                        'collaborating_groupids_pending' => '',
                        'collaborating_chapterids_pending' => '',
                        'listids' => 0,
                        'invited_groups' => $newHostGroupId,
                        'groupid' => $newHostGroupId
                    ];
                    
                    return [
                        true, 
                        $updatedEvent, 
                        gettext("Collaboration denied. Event has been converted to a single-group event."),
                        true
                    ];
                } else {
                    // Cannot determine valid host group
                    return [
                        false, 
                        null, 
                        sprintf(gettext("Cannot deny this collaboration request. An event must have at least two %s for collaboration, or have a valid host group to convert to a single-group event."), $_COMPANY->getAppCustomization()['group']['name-short-plural']),
                        false
                    ];
                }
            }
        }
        
        // Scenario 2: Remains collaboration event - validate collaboration requirements
        
        // Zone validation: Ensure at least one group from current zone remains using existing pattern
        $allRemainingGroups = array_merge($newCollaboratingGroupIds, $newPendingGroupIds);
        if (!empty($allRemainingGroups) && !Group::IsGroupInCurrentZone($allRemainingGroups, $event->val('zoneid'))) {
            return [
                false, 
                null, 
                sprintf(gettext("Cannot deny this collaboration request. At least one %s from the current zone must remain in the collaboration."), $_COMPANY->getAppCustomization()['group']['name-short']),
                false
            ];
        }
        
        // Validate minimum collaboration requirements following ValidateTopicCollaboration pattern
        if (count($allRemainingGroups) < 2) {
            return [
                false, 
                null, 
                sprintf(gettext('An event must have minimum of two %s for collaboration.'), $_COMPANY->getAppCustomization()['group']['name-short']),
                false
            ];
        }
        
        // Validate chapter-group relationships following existing patterns
        $validatedPendingChapterIds = $newPendingChapterIds;
        
        // Remove orphaned pending chapters (chapters whose parent groups are not in collaboration)
        $validPendingChapterIds = [];
        foreach ($validatedPendingChapterIds as $chapterId) {
            if ($chapterId) {
                $chapterDetails = Group::GetChapterNamesByChapteridsCsv($chapterId);
                if (!empty($chapterDetails)) {
                    $parentGroupId = $chapterDetails[0]['groupid'];
                    // Keep chapter only if parent group is still in collaboration (approved or pending)
                    if (in_array($parentGroupId, $newCollaboratingGroupIds) || in_array($parentGroupId, $newPendingGroupIds)) {
                        $validPendingChapterIds[] = $chapterId;
                    }
                }
            }
        }
        
        // Build valid collaboration state following existing event collaboration patterns
        $updatedEvent = [
            'event_scope' => 'collaborating_groups',
            'collaborating_groupids' => implode(',', $newCollaboratingGroupIds),
            'collaborating_groupids_pending' => implode(',', $newPendingGroupIds),
            'collaborating_chapterids_pending' => implode(',', $validPendingChapterIds),
            'chapterid' => implode(',', $currentApprovedChapterIds), // Keep existing approved chapters
            'invited_groups' => implode(',', $newCollaboratingGroupIds), // Only approved groups in invited_groups
            'listids' => 0 // Collaboration events don't use dynamic lists
        ];
        
        return [
            true, 
            $updatedEvent, 
            gettext("Collaboration request denied successfully."),
            false
        ];
    }

    public static function ValidateEventFormInputs()
    {
        global $_COMPANY, $_ZONE, $_USER;
        //Data Validation
        $event = null;
        $eventid = 0;
        $collaborate = '';
        $chapterids = '0';
        $channelid = 0;
        $add_photo_disclaimer = 0;
        $collaboratingGroupIds = array();
        $collaboratingGroupIdsPending = array();
        $requestEmailApprovalGroups = array();
        $collaborating_chapterids = array();
        $collaborating_chapterids_pending = array();
        $action = $_POST['action'];
        if ($action !='add' && (isset($_POST['eventid'])
                && (($eventid = $_COMPANY->decodeId($_POST['eventid']))<1
                    || ($event=Event::GetEvent($eventid)) === null
                    || $event->isAwaiting() 
                    || ($event->isPublished() && !$event->areEventSpeakersApproved())
                )
            )
        ){
            if (($event && $event->isPublished() && !$event->areEventSpeakersApproved())) { // Do not allow published events with unapproved speakers to be updated
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("To publish the new updates on the event, you must first ensure that the event speakers are approved."), gettext('Error'));
            } else {
                header(HTTP_BAD_REQUEST);
                exit();
            }
        
        }

        ViewHelper::ValidateObjectVersionMatchesWithPostAttribute($event);

        if (isset($_POST['chapters'])){
            # Start - Get Chapterids & Validate the user can perform action in the given chapters and channels
            $encChapterids = $_POST['chapters'] ?? array($_COMPANY->encodeId(0));
            $chapterids = implode(',', $_COMPANY->decodeIdsInArray($encChapterids) ?: '0');
        }

        if (isset($_POST['channelid'])){
            $channelid = $_COMPANY->decodeId($_POST['channelid']);
        }
    
        $event_series_id = isset($_POST['event_series_id']) ? $_COMPANY->decodeId($_POST['event_series_id']) : 0;

        $decodedGroupid = $_COMPANY->decodeId(@$_POST['groupid']);
        $groupid = $decodedGroupid;
        $invited_groups = $groupid;
        $decodedParentGroupid = $_COMPANY->decodeId($_POST['parent_groupid']);

        if ($_POST['event_scope'] == 'group' && $groupid) {
            $validationError = self::ValidateTopicChapterChanelSelectionScope('EVT', $eventid, $decodedParentGroupid,$chapterids,$channelid,$_POST['event_scope']);
            if ($validationError){
                AjaxResponse::SuccessAndExit_STRING(0, '', $validationError, gettext('Error'));
            }
        }

        if (
            !$event_series_id && 
            ($_POST['event_scope'] == 'collaborating_groups'  || $_POST['event_scope'] == 'zone') && 
            (!$event || $event->isDraft() || $event->isUnderReview())
        ) {
                // Authorization for Admin section
                if (!$event && !$_USER->isAdmin() && !$_USER->canCreateContentInGroup($groupid) && !$_USER->isRegionallead($groupid) && !$_USER->isOnlyChaptersLeadCSV($decodedGroupid)) {
                    header(HTTP_FORBIDDEN);
                    exit();
                } elseif($event && !$event->loggedinUserCanUpdateEvent() ) {
                    header(HTTP_FORBIDDEN);
                    exit();
                }


                // Scope cant be empty for new events or unpublished events
                if (!isset($_POST['event_scope'])) {
                    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Scope can't be empty"), gettext('Error'));
                }

                $groupid = 0;
                $chapterids = '0';
                $channelid = 0;

                if ($_POST['event_scope'] === 'collaborating_groups') { // Collaboration Event
                    // Validate Host Group Chapter selection
                    if($decodedParentGroupid){
                        $collaborating_chapteridsStr = !empty($_POST['collaborating_chapterids']) ? implode(',', $_COMPANY->decodeIdsInArray($_POST['collaborating_chapterids'])) : '0';
                        $collaborating_chapteridsStr = Group::GetGroupChaptersFromChapterIdsCSV($decodedParentGroupid, $collaborating_chapteridsStr); // Get Host groups selected chapters only
                        $collaborating_chapteridsArray = explode(',',$collaborating_chapteridsStr);
                        $validationError = '';
                        foreach($collaborating_chapteridsArray as $chid){
                            $validationError = self::ValidateTopicChapterChanelSelectionScope('EVT', $eventid,$decodedParentGroupid,$chid,0,$_POST['event_scope']);
                            if (empty($validationError)) {
                                break;
                            }
                        }
                        if ($validationError){
                            AjaxResponse::SuccessAndExit_STRING(0, '', $validationError, gettext('Error'));
                        }
                    }
                    // Validate Collaboration
                    [
                        $collaboratingGroupIds, 
                        $collaboratingGroupIdsPending, 
                        $requestEmailApprovalGroups,
                        $collaborating_chapterids, 
                        $collaborating_chapterids_pending
                    ] = ViewHelper::ValidateTopicCollaboration('EVT',$eventid,$decodedParentGroupid);

                    $collaborate = implode(',',$collaboratingGroupIds);
                    if (count($collaboratingGroupIds)) {
                        $invited_groups = implode(',', $collaboratingGroupIds);
                    }

                } else { //Company level event, assume event_scope = 0

                    $allGroups = Group::GetAllGroupsByCompanyid($_COMPANY->id(),$_ZONE->id(),true);
                    $gids = array();
                    foreach($allGroups as $g){
                        $gids[] = $g->id();
                    }
                    sort($gids);
                    $invited_groups = implode(',',$gids);
                }
            
        }

        $use_and_chapter_connector = (bool)($_POST['use_and_chapter_connector'] ?? '0');

        $listids = 0;
        if (isset($_POST['event_scope']) && $_POST['event_scope'] == 'dynamic_list'){
            if($event){
                $listids = $event->isPublished() ? $event->val('listids') : ($_POST['list_scope'] ? implode(',',$_COMPANY->decodeIdsInArray($_POST['list_scope'])) : null);
            }else{                
                $listids = empty($_POST['list_scope']) ? null : implode(',',$_COMPANY->decodeIdsInArray($_POST['list_scope']));
            }
            
            if (!$listids) {
                AjaxResponse::SuccessAndExit_STRING(0, '', "Please select a dynamic list scope!", 'Error!');
            }
        }

        // *****************************************************
        // Event Title Section
        // *****************************************************
        $eventtitle = $_POST['eventtitle'] ?? '';
        if (empty($eventtitle)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"), gettext('Event Name')), gettext('Error'));
        }

        // *****************************************************
        // Event Date/Time Section
        // *****************************************************
        list(
            $event_tz,
            $start,
            $end
            ) = ViewHelper::GetEventWhenValues();

        // *****************************************************
        // Event Location Section
        // *****************************************************

        list(
            $event_attendence_type,
            $eventvanue,
            $vanueaddress,
            $venue_info,
            $venue_room,        
            $web_conference_link,
            $web_conference_detail,
            $checkin_enabled,
            $max_inperson,
            $max_inperson_waitlist,
            $max_online,
            $max_online_waitlist,
            $web_conference_sp,
            $calendar_blocks
            ) = ViewHelper::GetEventWhereValues($event);

        // *****************************************************
        // Event Contact Section
        // *****************************************************
        $event_contact = trim($_POST['event_contact'] ?? '');
        if ($event && empty($event_contact)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"), gettext('Event Contact')), gettext('Error'));
        }

        $event_contact_phone_number = trim($_POST['event_contact_phone_number'] ?? '');

        if ($event_contact_phone_number && !Str::validatePhoneNumber($event_contact_phone_number)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("Please provide a valid %s"), gettext('Event Contact Phone Number')), gettext('Error'));
        
        }
        
        // *****************************************************
        // Event Type Section
        // *****************************************************
        $eventtype = (int)($_POST['eventtype'] ?? 0);
        if ($event && empty($eventtype)) {
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"), gettext('Event Type')), gettext('Error'));
        }

        // *****************************************************
        // Event Details Section
        // *****************************************************
        $event_description = $event ? ViewHelper::RedactorContentValidateAndCleanup($_POST['event_description'] ?? '') : '';

        if ($event && $_COMPANY->getAppCustomization()['event']['is_description_required'] && empty($event_description)) { 
            AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext("%s can't be empty"), gettext('Description')), gettext('Error'));
        }

        // *****************************************************
        // Event Custom Fields Section
        // *****************************************************

        $custom_fields_input = json_encode(array());
        if ($event){
            if ($event->isActionDisabledDuringApprovalProcess()) {
                $custom_fields_input = $event->val('custom_fields');
            } else {
                $custom_fields_input = ViewHelper::ValidateAndExtractCustomFieldsFromPostAttribute();
            }
        }

        // *****************************************************
        // Event Other Settings Section
        // *****************************************************
        // RSVP enabled or not
        $rsvp_enabled              = !empty($_POST['rsvp_enabled']) ? 1 : 0;
        // Since our event create form is two stage, we need this check for first stage where rsvp_enabled is not set yet.
        if (!$event && empty($_POST['rsvp_enabled'])) {
            $rsvp_enabled = 1;
        }

        // Is private event
        $isprivate              = ($event && $event->val('listids')) != 0  ?  1 : (!empty($_POST['isprivate']) ? 1 : 0);
        // Set photo disclaimer to either the default zone value or the value provided in the form.
        // This $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['enabled']  setting should be considered only for forntend purpose only
        // $add_photo_disclaimer = $_COMPANY->getAppCustomization()['event']['photo_disclaimer']['enabled'] ? 1 : 0; 
       if (isset($_POST['add_photo_disclaimer'])) {
            $add_photo_disclaimer   = !empty($_POST['add_photo_disclaimer']) ? 1 : 0;
        }

        $content_replyto_email  = ViewHelper::ValidateAndExtractReplytoEmailFromPostAttribute();

        $disclaimerids = '';
        if (isset($_POST['disclaimerids'])){
            $disclaimerids = $_POST['disclaimerids'] ?? array($_COMPANY->encodeId(0));
            $disclaimerids = implode(',', $_COMPANY->decodeIdsInArray($disclaimerids) ?: '');
        }
    
        $event_contributors = array();
        $event_contributors[] = $_USER->id();
        if (!empty($_POST['event_contributors'])) {
            $contributors = $_COMPANY->decodeIdsInArray($_POST['event_contributors']);
            $event_contributors = array_merge($event_contributors,$contributors);
        }
        $event_contributors = implode(',',$event_contributors);

        ViewHelper::ValidateEventFormModules($event);

        return array(
                $event, $action, $eventid, $groupid, $chapterids, $eventtitle, $start, $end, $event_tz, $eventvanue, $vanueaddress, $event_description, $eventtype, $invited_groups, $max_inperson, $max_inperson_waitlist, $max_online, $max_online_waitlist, $event_attendence_type, $web_conference_link, $web_conference_detail, $web_conference_sp, $checkin_enabled, $collaborate, $channelid, $event_series_id, $custom_fields_input, $event_contact, $venue_info, $venue_room, $isprivate, $add_photo_disclaimer, $calendar_blocks, $content_replyto_email, $listids, $use_and_chapter_connector, $collaboratingGroupIds, $collaboratingGroupIdsPending, $collaborating_chapterids, $collaborating_chapterids_pending, $requestEmailApprovalGroups,$decodedGroupid,$disclaimerids,$rsvp_enabled, $event_contact_phone_number, $event_contributors
            );
    }

    public static function PassVariableToJavascriptInComment ($varName, $varValue, $varContext='') : string
    {
        return "<!-- {$varContext} :::::{$varName}={$varValue}::::: -->";
    }
    public static function ValidateExpenseSubItemData(float $budgeted_amount, float $usedamount){
        if (isset($_POST['item']) && !empty(array_filter($_POST['item']))){
            $item = $_POST['item'];
            $item_budgeted_amounts = $_POST['item_budgeted_amount'] ?? [];
            $item_used_amounts = $_POST['item_used_amount'];
            $expensetypeids = $_POST['expensetypeid'];
            $ispaidinforeigncurrencies = $_POST['ispaidinforeigncurrency'] ?? [];
            $foreigncurrencies = $_POST['foreigncurrency'] ?? [];
            $foreigncurrencyamounts = $_POST['foreigncurrencyamount'] ?? [];
            $currencyconversionrates = $_POST['currencyconversionrate'] ?? [];
            [$ispaidinforeigncurrencies,$foreigncurrencies,$foreigncurrencyamounts, $currencyconversionrates] = self::PrepareEnpenseEntryForeignCurrencyData($ispaidinforeigncurrencies,$foreigncurrencies,$foreigncurrencyamounts,$currencyconversionrates );
            $item_budgeted_amounts_total = 0;
            $item_used_amounts_total = 0;
            for($c=0;$c<count($item);$c++){
                if(empty(floatval($item_budgeted_amounts[$c])) && empty(floatval($item_used_amounts[$c])) && empty($expensetypeids[$c]) && empty($item[$c])){
                    continue; // Skip empty rows
                }
                if (empty($expensetypeids[$c])){
                    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please select subitem expense type"), gettext('Error'));
                }
                if (empty($item[$c])){
                    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Subitem expense detail cannot be empty"), gettext('Error'));
                }
                if(empty(floatval($item_budgeted_amounts[$c])) && empty(floatval($item_used_amounts[$c]))){
                    AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Please enter a valid value for subitem budget or expense amount"), gettext('Error'));
                }
                if ($ispaidinforeigncurrencies[$c]>0){
                    $error = [];
                    if (empty($foreigncurrencies[$c])){
                        $error[] = gettext('foreign currency');
                    }
                    if (empty(floatval($currencyconversionrates[$c]))){
                        $error[] = gettext('currency conversion rate');
                    }
                    if (empty(floatval($foreigncurrencyamounts[$c]))){
                        $error[] = gettext('foreign currency amount');
                        
                    }
                    if (!empty($error)){
                        $errors = implode(', ', $error);
                        AjaxResponse::SuccessAndExit_STRING(0, '', sprintf(gettext('Please fill out %s field(s).'),$errors), gettext('Error'));
                    }
                }
                $item_budgeted_amounts_total = $item_budgeted_amounts_total+floatval($item_budgeted_amounts[$c]) ;
                $item_used_amounts_total = $item_used_amounts_total+floatval($item_used_amounts[$c]); 
            }
            if ($item_budgeted_amounts_total > $budgeted_amount) {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("The sub item budget totals cannot be more than budgeted amount"), gettext('Error'));
            }
            if ($item_used_amounts_total != $usedamount) {
                AjaxResponse::SuccessAndExit_STRING(0, '', $item_used_amounts_total.gettext("The sub item expense totals do not match the expensed amount").$usedamount, gettext('Error'));
            }
            return [$ispaidinforeigncurrencies,$foreigncurrencies,$foreigncurrencyamounts, $currencyconversionrates];
        }
        return [[],[],[],[]];
    }

    /**
     * Since the expense items can be dynamically generated and only some of them have foreign currencies, we get a
     * incorrect number of items in arrays for $foreigncurrencies, $foreigncurrencyamounts, $currencyconversionrates.
     * however the array $ispaidinforeigncurrencies is correct.
     * e.g. if there are three expense items with item 2 in foreign currency, then we will get three items in
     * $ispaidinforeigncurrencies array and only item each in $foreigncurrencies, $foreigncurrencyamounts,
     * $currencyconversionrates
     * We need to update $foreigncurrencies, $foreigncurrencyamounts, $currencyconversionrates arrays to fill in the
     * holes for subitems that do not have $ispaidinforeigncurrencies.
     *
     * @param array $ispaidinforeigncurrencies
     * @param array $foreigncurrencies
     * @param array $foreigncurrencyamounts
     * @param array $currencyconversionrates
     * @return array
     */
    public static function PrepareEnpenseEntryForeignCurrencyData(array $ispaidinforeigncurrencies, array $foreigncurrencies, array $foreigncurrencyamounts, array $currencyconversionrates) {
        $modifiedForeigncurrencies = [];
        $modifiedForeigncurrencyamounts = [];
        $modifiedCurrencyconversionrates = [];
        $countIspaidinforeigncurrencies = count($ispaidinforeigncurrencies);

        // The count of items in $foreigncurrencies, $foreigncurrencyamounts and $currencyconversionrates array should
        // be the same.
        if (
            (count($foreigncurrencies) != count($foreigncurrencyamounts)) ||
            (count($foreigncurrencies) != count($currencyconversionrates))
        ) {
            Logger::Log('Received inconsistent foreign currency arguments');
            exit(0);
        }
        
        // For $foreigncurrencies

        $indexToMove = 0;  // To keep track of the position to move values to
        // Step through the $ispaidinforeigncurrency array
        for ($i = 0; $i < $countIspaidinforeigncurrencies; $i++) {
            if ($ispaidinforeigncurrencies[$i] == 1) {
                // If the value is 1, keep the value from $foreigncurrencies
                if (isset($foreigncurrencies[$indexToMove])) {
                    $modifiedForeigncurrencies[$i] = $foreigncurrencies[$indexToMove];
                    $modifiedForeigncurrencyamounts[$i] = $foreigncurrencyamounts[$indexToMove];
                    $modifiedCurrencyconversionrates[$i] = $currencyconversionrates[$indexToMove];
                    $indexToMove++;
                } else {
                    // If no value is available to move, keep it empty
                    $modifiedForeigncurrencies[$i] = '';
                    $modifiedForeigncurrencyamounts[$i] = 0;
                    $modifiedCurrencyconversionrates[$i] = 0;
                }
            } else {
                // If the value is 0, set ''
                $modifiedForeigncurrencies[$i] = '';
                $modifiedForeigncurrencyamounts[$i] = 0;
                $modifiedCurrencyconversionrates[$i] = 0;

            }
        }

        return array ($ispaidinforeigncurrencies,$modifiedForeigncurrencies,$modifiedForeigncurrencyamounts,$modifiedCurrencyconversionrates);

    }


    public static function ValidateTopicChapterChanelSelectionScope(string $topicType, int $topicId, int $groupid, string $chapterids, int $channelid,string $scope)
    {
        global $_COMPANY, $_ZONE, $_USER;
        $requiredContextScope = '';
        $topic = null;
        if ($topicId){
            if ($topicType == 'EVT') {
                $topic = Event::GetEvent($topicId);
            }
        }

        $topicEnglish = strtolower(Teleskope::TOPIC_TYPES_ENGLISH[$topicType]);
        if ($topic) {
            /**
             * If the content is already published, do not validate chapter/channel selection because, in a published event, we do not allow chapter/channel selection.
             */
            if (!$topic->isPublished() && !$_USER->canUpdateContentInScopeCSV($groupid,$chapterids, $channelid,$topic->val('isactive'))){
                if (($_COMPANY->getAppCustomization()['chapter']['enabled'] && empty($chapterids)) || ($_COMPANY->getAppCustomization()['channel']['enabled']) && empty($channelid)) {
                    $requiredContextScope = $_COMPANY->getAppCustomization()['channel']['name-short'];
                    if (empty($chapterids)){
                        $requiredContextScope = $_COMPANY->getAppCustomization()['chapter']['name-short'];
                    }
                } 
            }
            
        } else {
            if (!$_USER->canCreateContentInScopeCSV($groupid,$chapterids, $channelid)){
                if (($_COMPANY->getAppCustomization()['chapter']['enabled'] && empty($chapterids)) || ($_COMPANY->getAppCustomization()['channel']['enabled']) && empty($channelid)) {
                    $requiredContextScope = $_COMPANY->getAppCustomization()['channel']['name-short'];
                    if (empty($chapterids)){
                        $requiredContextScope = $_COMPANY->getAppCustomization()['chapter']['name-short'];
                    }
                }
            }
        }
        $message = '';
        if ($requiredContextScope){
            if ($scope == 'collaborating_groups') {
                $message = sprintf(gettext('To create a collaborative %3$s as a %1$s Lead, please select a %1$s in the host %2$s where you want to create the %3$s.'),$_COMPANY->getAppCustomization()['chapter']['name-short'],$_COMPANY->getAppCustomization()['group']['name-short'], $topicEnglish);
            } else {
                $message = sprintf(gettext("Please select a %s scope"),$requiredContextScope);
            }
        }

        return $message;
    }

    /**
     * @param mixed $newsletter_content
     * @return string
     */
    public static function FixRevolvAppContentForOutlook(string $newsletter_content): string
    {
        # Start - Outlook Conent Validation
        # (Outlook fix 1) We do not want to allow anny width=NNpx tags in HTML content
        $width_pattern = '/width="(\d+)px"/';
        $width_replace = 'width="$1"';
        $newsletter_content = preg_replace($width_pattern, $width_replace, $newsletter_content);

        # (Outlook fix 2) We will not allow <img> tags without width tag
        // Extract the image tags and validate if the width has been set, if not throw error
        preg_match_all('/<img[^>]+>/i', $newsletter_content, $match);
        foreach ($match[0] as $item) {
            if (!preg_match('/width="(\d+)"/', $item)) {
                AjaxResponse::SuccessAndExit_STRING(0, '', gettext("Missing 'Width' value for one of the images"), gettext('Error'));
            }
        }
        return $newsletter_content;
        # End - Outlook Conent Validation
    }

}