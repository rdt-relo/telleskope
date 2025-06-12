<?php
// Include Base Class
require_once __DIR__ .'/Teleskope.php';
require_once __DIR__ .'/Sanitizer.php';
require_once __DIR__ .'/CompanyDictionary.php';

// Include Traits
require_once __DIR__ . '/TopicLikeTrait.php';
require_once __DIR__ . '/TopicCommentTrait.php';
require_once __DIR__ . '/TopicUsageLogsTrait.php';
require_once __DIR__ . '/CacheableTrait.php';
require_once __DIR__ . '/TopicAttachmentTrait.php';
require_once __DIR__ . '/TopicCustomFieldsTrait.php';
require_once __DIR__ . '/TopicApprovalTrait.php';

// Include Classes
require_once __DIR__ . '/Zone.php';
require_once __DIR__ .'/User.php';
require_once __DIR__ .'/Event.php';
require_once __DIR__ .'/Post.php';
require_once __DIR__ .'/Group.php';
require_once __DIR__ .'/jobs/Job.php';
require_once __DIR__ .'/reports/Report.php';
require_once __DIR__.'/Newsletter.php';
require_once __DIR__.'/Message.php';
require_once __DIR__.'/surveys/Survey2.php';
require_once __DIR__.'/surveys/GroupMemberSurvey.php';
require_once __DIR__.'/surveys/ZoneMemberSurvey.php';
require_once __DIR__.'/Analytics.php';
require_once __DIR__.'/Resource.php';
require_once __DIR__.'/Team.php';
require_once __DIR__.'/Budget2.php';
require_once __DIR__.'/AjaxResponse.php';
require_once __DIR__ . '/Comment.php';
require_once __DIR__ . '/HashtagHandle.php';
require_once __DIR__ . '/TrainingVideo.php';
require_once __DIR__ . '/UserCatalog.php';
require_once __DIR__ . '/Discussion.php';
require_once __DIR__ .'/Album.php';
require_once __DIR__ .'/AlbumMedia.php';
require_once __DIR__ .'/TeamEvent.php';
require_once __DIR__ .'/Disclaimer.php';
require_once __DIR__ .'/Recognition.php';
require_once __DIR__ .'/UserInbox.php';
require_once __DIR__ .'/points/Points.php';
require_once __DIR__ .'/utils/Csv.php';
require_once __DIR__ .'/GroupUserLogs.php';
require_once __DIR__ .'/CompanyEncKey.php';
require_once __DIR__ .'/Notification.php';
require_once __DIR__ .'/DynamicList.php';
require_once __DIR__ .'/TeleskopeMailingList.php';
require_once __DIR__ .'/Content.php';
require_once __DIR__ .'/Statistics.php';
require_once __DIR__ .'/EmailHelper.php';
require_once __DIR__ .'/Template.php';
require_once __DIR__ .'/CompanyPSKey.php';
require_once __DIR__ .'/EaiAccount.php';
require_once __DIR__ .'/TskpTemplate.php';
require_once __DIR__ .'/search/Typesense.php';
require_once __DIR__ .'/Organization.php';
require_once __DIR__ .'/Attachment.php';
require_once __DIR__ .'/ExpenseEntry.php';
require_once __DIR__ .'/BudgetRequest.php';
require_once __DIR__ .'/EphemeralTopic.php';
require_once __DIR__ . '/utils/ContentModerator.php';
require_once __DIR__ . '/BlockedKeyword.php';
require_once __DIR__ . '/TeamTask.php';
require_once __DIR__ . '/UserSchedule.php';
require_once __DIR__ . '/EventOfficeLocation.php';
require_once __DIR__ . '/utils/CalendarICS.php';
require_once __DIR__ . '/TopicApprovalConfiguration.php';
require_once __DIR__ . '/TopicApprovalLog.php';
require_once __DIR__ . '/TopicApprovalTask.php';
require_once __DIR__ . '/Approval.php';
require_once __DIR__ . '/EventSpeaker.php';
require_once __DIR__ . '/EventVolunteer.php';
require_once __DIR__ . '/DelegatedAccess.php';

class Company extends Teleskope {

    public const DEFAULT_PGP_KEY_NAME = "teleskope_uploader_2021";

    /**
     * TODO - This is a dummy company config
     */
    public const DEFAULT_COMPANY_SETTINGS = array(
        'company' => array(
            // Add any company global settings which cannot be overriden in the zones.
            'reports' => array (
                'report_file_format' => 'Confidential_Internal_Use_Only-[[subdomain]]-[[reportname]]-[[date]]-[[time]]',
            ),
            'user_field' => array (
                'externalid' => array (
                    'name' => 'Employee Number',
                ),
            ),
        ),
        'app' => array(
            // Add any zone app settings which can be allowed to be set at global level
            //  "group" => array( ... )
        ),
        'style' => array (
            // Add any zone app settings which can be allowed to be set at global level
        )
    );

    // Note only associative arrays are supported.
    public const DEFAULT_SETTINGS_AFFINITY = array(
        "app" => array(
            "group" => array(
                "name" => "ERG",
                "name-plural" => "ERGs",
                "name-short" => "ERG",
                "name-short-plural" => "ERGs",
                "groupname0" => "Admin",
                "group0_color" => 'rgb(80,80,80)',
                "group0_color2" => 'rgb(160,160,160)',
                "group_category_enabled" => false,

                "manage" => array (
                    "allow_update_groupleads" => true,
                    "allow_update_chapterleads" => true,
                    "allow_update_channelleads" => true,
                    "allow_add_members" => true,
                ),
                "tabs" => array(
                    "enabled" => false,
                    "yammer" => false,
                    "streams" => false,
                    "custom" => false,
                ),
                "memberlabel"=>"Member",
                "memberlabel_plural"=>"Members",
                "allow_anonymous_join"=>true,
                "homepage"=> array(
                    "show_member_count_in_tile" => true,
                    "show_chapter_channel_count_in_tile" => true,
                    "show_global_feed" => true,
                    "show_my_groups_option" => true,
                    "show_chapter_content_in_global_feed" => false,
                    "show_channel_content_in_global_feed" => false,
                    "show_past_events_in_show_all_in_global_feed" => true,
                ),
                "group_export" => false,
                "member_restrictions" => false,
                "allow_invite_members" => true,
                "qrcode"=>true,
            ),
            "linked-group" => array (
                "enabled" => false,
            ),
            "disclaimer_consent" => array (
                "enabled" => false,
            ),
            "chapter" => array(
                "name" => "Chapter",
                "name-plural" => "Chapters",
                "enabled" => false,
                "name-short" => "Chapter",
                "name-short-plural" => "Chapters",
                "chapter0" => "Default Chapter",
                "allow_create_from_app" => false,
                "join_button_help_text" => "",
            ),
            "channel" => array(
                "name" => "Channel",
                "name-plural" => "Channels",
                "enabled" => false,
                "name-short" => "Channel",
                "name-short-plural" => "Channels",
                "channel0" => "Default Channel",
                "allow_create_from_app" => false,
                "join_button_help_text" => "",
            ),
            "post" => array(
                "enabled" => true,
                "show_upcoming_event" => true,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
                "post_disclaimer" => array(
                    "enabled" => false,
                    "enabled_default"=> false,
                    "disclaimer" => "Disclaimer: ",
                ),
                "analytics" => false,
                "require_email_review_before_publish" => false,
                "disable_email_publish" => false,
                "show_global_posts_in_group_feed"=>true,
                "comments" => true,
                "likes" => true,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "alt_name" => array( "en" => ''),
                'attachments' => [
                    'enabled' => true,
                ],
                'publish' => array(
                    'preselect_email_publish' => true,
                ),
                'approvals' => array (
                    'enabled' => false,
                    'tasks' => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "aboutus" => array(
                "enabled" => true,
            ),
            "profile" => array(
                "enable_pronouns" => true,
                "enable_bio" => true,
                "allow_update_name" => true,
                "allow_update_pronouns" => true,
                "allow_meeting_link" => true,
                "allow_account_deletion" => true,
                'allow_delegated_access' => true,
            ),
            "helpvideos" => array(
                "enabled" => true,
            ),
            "donations" => array("enabled"=>false),
            "event" => array(
                "enabled" => true,
                "checkin" => true,
                'checkin_show_signin' => true,
                'speakers' => array (
                    'enabled' => false,
                    'approvals' => false
                ),
                "rsvp_link" => true,
                "checkin_default" => true,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => false,
                ),
                "analytics" => true,
                "budgets" => false,
                "calendar_loc_filter" => false,
                "photo_disclaimer" => array(
                    "enabled" => false,
                    "enabled_default"=> false,
                    "disclaimer" => "Disclaimer: Photos and videos will be recorded during this event. By joining the event you are providing your consent for your photos and videos to be recorded.",
                    "show_on_top_in_emails"=>true,
                ),
                "meeting_links" => array(
                    "msteams" => false,
                    "gmeet" => false,
                    "zoom" => false
                ),
                "cultural_observances" => array(
                    "enabled" => true,
                ),
                
                "web_conf_detail_message_override" => '',
                
                "require_email_review_before_publish" => false,
                "show_global_events_in_group_feed"=>true,
                "volunteers" => false,
                'external_volunteers' => true,
                "comments" => true,
                "likes" => true,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "enable_event_surveys"=>false,
                "my_events" => array(
                    "enabled" => false,
                    "event_submissions" => false,
                    "discover_events" => false
                ),
                "custom_fields" => array(
                    "enable_visible_only_if_logic" => false
                ),
                "partner_organizations" => array (
                    "enabled" => false,
                    "is_required" => false
                ),
                "is_description_required" => false,
                'attachments' => [
                    'enabled' => true,
                ],
                'event_form' => array (
                    'show_module_settings' => false,
                    'enable_participation_limit_by_default'=>false,
                ),
                'publish' => array(
                    'preselect_email_publish' => true,
                ),
                'approvals' => array (
                    'enabled' => false,
                    'tasks' => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
                'collaborations' => array(
                    'auto_approve' => false,
                ),
                'event_contributors' => array(
                    'enabled' => false,
                ),
                'disable_event_conflict_checks'=>false,
                "disable_email_publish" => false,
                'rsvp_display' => array (
                    'allow_updates' => true,
                    'default_value' => 2, // Valid values are 0,1,2,3 for not showing, showing count only,showing avataars + counts respectively
                ),
                'reconciliation' => array (
                    'enabled' => false
                ),
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "calendar" => array(
                "enabled" => true,
                "location_filter" => false,
                "allow_embed" => false,
                "enable_secure_embed" => true,
            ),
            "recruiting" => array(
                "enabled" => false,
            ),
            "referral" => array(
                "enabled" => false,
            ),
            "messaging" => array(
                "enabled" => true,
                'restrict_to_admin_only' => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
            ),
            "budgets" => array(
                "enabled" => false,
                "allow_grouplead_to_edit_expense" => true,
                "currency"=>"USD",
                "locale"=>"en_US",
                'other_funding' => true,
                "show_expense_budget"=>true,
                "enable_budget_requests" => true,
                "enable_budget_expenses" => true,
                "allocated_budget_definition" => '',
                "other_funding_definition" => '',
                'attachments' => [
                    'enabled' => true,
                ],
                'vendors' => array(
                    'enabled' => true,
                ),
            ),
            "resources" => array(
                "enabled" => false,
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "newsletters" => array(
                "enabled" => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
                "require_email_review_before_publish" => false,
                "disable_email_publish" => false,
                "show_global_newsletters_in_group_feed" => true,
                "comments" => true,
                "likes" => true,
                "show_in_home_feed" => true,
                'publish' => array(
                    'preselect_email_publish' => true,
                ),
                "enable_media_upload_on_comment"=>false,
                'approvals' => array (
                    'enabled' => false,
                    'tasks' => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "surveys" => array(
                "enabled" => false,
                "analytics" => false,
                "restrict_publish_to_admin_only" => false,
                "question_types_csv" => "text,comment,boolean,checkbox,radiogroup,dropdown,rating,ranking,imagepicker,matrix,matrixdropdown,html",
                "allow_create_chapter_scope" => true,
                "allow_create_channel_scope" => true,
                "default_anonymous_survey" => false,
                "approvals" => array (
                    "enabled" => false,
                    "tasks" => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
            ),
            "integrations" => array(
                "group" => array(
                    "enabled" => false,
                    "workplace" => false,
                    "yammer" => false,
                    "teams" => false,
                    "googlechat" => false,
                    "custom" => false,
                    "slack" => false,
                ),
                'analytics' => [
                    'adobe' => [
                        'enabled' => false,
                        'js_src' => '   ', #Intentionally set to three blanks, do not change
                    ],
                ],
            ),
            "communications" => array(
                "enabled" => true,
            ),
            "stats" => array(
                "enabled" => true,
                "grouplead_exec" => true,
                "grouplead_group" => true,
                "grouplead_region" => false,
                "grouplead_chapter" => true,
                "grouplead_channel" => true,
                "member_1" => true,
                "member_2" => true,
                "member_3" => true,
                "teams_active" => true,
                "teams_completed" => true,
                "teams_not_completed" => false,
                "teams_mentors_active" => true,
                "teams_mentors_completed" => true,
                "teams_mentors_not_completed" => false,
                "teams_mentors_registered" => true,
                "teams_mentees_active" => true,
                "teams_mentees_completed" => true,
                "teams_mentees_not_completed" => false,
                "teams_mentees_registered" => true,
            ),
            "policy" => array(
                "image_upload_notice" => "The images you attached may be copyrighted. Please only continue if you have rights to use the image."
            ),
            "teams" => array(
                "name" => "Team",
                "name-plural" => "Teams",
                "enabled" => false,
                "name-short" => "Team",
                "name-short-plural" => "Teams",
                "comments" => true,
                "likes" => false,
                "enable_media_upload_on_comment"=>true,
                "team_events" => [
                    "enabled" => false,
                    "event_list" => [
                        "enabled" => true,
                        "show_all" => false,
                    ],
                    "detailed_ics" => false,
                    'disable_event_types'=>false
                ],
                "teambuilder_enabled" => false,
                'teambuilder_debug' => false,
                'search' => false,
                'hashtag' => array (
                    "enabled" => true,
                    "can_create_realtime_on_userend" => false,
                ),
                'attachments' => [
                    'enabled' => true,
                ],
            ),
            "reports" => array(
                "analytics" => true,
            ),
            "booking" => array(
                "enabled" => false,
                "alt_name" => array( "en" => ''),
            ),
            "header" => array(
                "notifications" => false,
                "show_my_location_menu" => false,
            ),
            "footer" => array (
                "show_guides" => true,
                "show_release_notes" => true,
                "show_training_videos" => true,
                "show_feedback_link" => true,
                "show_support_link" => true,
                "show_mailing_list" => true,
            ),
            "comment" => array (
                "custom_terms_of_use" => array(
                    "enabled" => false,
                    "title" => 'Company specific Terms of Use for comments',
                    "html" => '<p>While commenting please note the following:</p><p><ul><li>Item1</li><li>Item2</li></ul></p>',
                ) ,
            ),
            "locales" => array(
                "enabled" => false,
                "languages_allowed" => array(
                    'en' => array("enabled" => true),
                    'es_ES' => array("enabled" => false),
                    'es_MX' => array("enabled" => false),
                    'fr_FR' => array("enabled" => false),
                    'fr_CA' => array("enabled" => false),
                    'de_DE' => array("enabled" => false),
                    'hi_IN' => array("enabled" => false),
                    'ja_JP' => array("enabled" => false),
                    'ms_MY' => array("enabled" => false),
                    'ko_KR' => array("enabled" => false),
                    'pt_PT' => array("enabled" => false),
                    'pt_BR' => array("enabled" => false),
                    'zh_CN' => array("enabled" => false),
                    'th_TH' => array("enabled" => false),
                    'it_IT' => array("enabled" => false),
                    'id_ID' => array("enabled" => false),
                    'fil_PH' => array("enabled" => false),
                    'vi_VN' => array("enabled" => false),
                )
            ),
            "discussions" => array(
                "enabled" => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
                "analytics" => false,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "anonymous_post_setting" => true,
                "disable_email_publish" => false,
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "albums" => array(
                "enabled" => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => false,
                ),
                "upload_media_disclaimer" => array(
                    "enabled" => false,
                    "disclaimer" => "Disclaimer: The Photos and videos you are uploading may be copyrighted. Please only continue if you have rights to use them.",
                ),
                "analytics" => false,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "comments" => true,
                "likes" => true,
            ),
            "recognition" => array(
                "enabled" => false,
                "alt_name" => array(
                    "en" => ''
                ),
                "comments" => true,
                "likes" => true,
                "enable_media_upload_on_comment"=>false,
            ),
            'mobileapp' =>array(
                "enabled" => true,
                "custom" => array (
                    "enabled" => false,
                    "mobile_app_name" => '',
                    "mobile_app_ios_url" => '',
                    "mobile_app_android_url" => '',
                    "support_link" => false
                ),
                "show_cta_banner_if_joined_group_less_than" => 3,
                "show_global_feed" => true
            ),
            'user_inbox' => array (
                'enabled' => false
            ),
            'dynamic_list' => array (
                'enabled' => false
            ),
            'points' => array (
                'enabled' => false,
                'frontend_enabled' => false,
                'enable_point_conversion_rate' => false,
            ),
            'emails' => array (
                'show_group_logo_instead_of_company_logo' => false,
                'generic_disclaimer_html' => "<div style='background-color: #ffb666;'></div>",
                'show_event_top_button_in_email' => true,
                'add_emailed_by_block' => true,
                'allow_send_to_external_emails' => false,
            ),
            'plugins' => array (
                'google_maps' => true
            ),
            'my_schedule' => array (
                'enabled' => false,
            ),
            'search' => array (
                'enabled' => true,
            ),
            'content_moderator' => array (
                'enabled' => false,
            ),
        ),
        "style" => array (
            "css" => array (
                "body" => array(
                    "background-color" => '#d3d3d1'
                ),
                "profile_initial_color" => array(
                    "color" => ''
                )
            ),
            "override_fonts_css_url" => "",
            "zone_tile_bg_image" => "",
            "zone_tile_compact_bg_image"=>"",
            "zone_tile_heading" => "",
            "zone_tile_sub_heading" => "",
        )
    );

    // Note only associative arrays are supported.
    public const DEFAULT_SETTINGS_OFFICERAVEN = array(
        "app" => array(
            "group" => array(
                "name" => "Location",
                "name-plural" => "Locations",
                "name-short" => "Location",
                "name-short-plural" => "Locations",
                "groupname0" => "Admin",
                "group0_color" => 'rgb(80,80,80)',
                "group0_color2" => 'rgb(160,160,160)',
                "group_category_enabled" => false,
                "manage" => array (
                    "allow_update_groupleads" => true,
                    "allow_update_chapterleads" => true,
                    "allow_update_channelleads" => true,
                    "allow_add_members" => true,
                ),
                "tabs" => array(
                    "enabled" => false,
                    "yammer" => false,
                    "streams" => false,
                    "custom" => false,
                ),
                "affinity_group_category_enabled" => true,
                "affinity_group_categories" => array(
                    "ERG" => "Resource Groups",
                    "IG" => "Interest Groups"
                ),
                "memberlabel" => "Employee",
                "memberlabel_plural"=>"Employees",
                "allow_anonymous_join"=>false,
                "homepage"=> array(
                    "show_member_count_in_tile" => true,
                    "show_chapter_channel_count_in_tile" => false,
                    "show_global_feed" => false,
                    "show_my_groups_option" => false,
                    "show_chapter_content_in_global_feed" => false,
                    "show_channel_content_in_global_feed" => false,
                    "show_past_events_in_show_all_in_global_feed" => true,
                ),
                "group_export" => false,
                "member_restrictions" => false,
                "allow_invite_members" => true,
                "qrcode"=>true,
            ),
            "linked-group" => array (
                "enabled" => true,
            ),
            "disclaimer_consent" => array (
                "enabled" => false,
            ),
            "chapter" => array(
                "name" => "Office",
                "name-plural" => "Offices",
                "enabled" => false,
                "name-short" => "Office",
                "name-short-plural" => "Offices",
                "chapter0" => "Undefined",
                "allow_create_from_app" => false,
                "join_button_help_text" => "",
            ),
            "channel" => array(
                "name" => "Channel",
                "name-plural" => "Channels",
                "enabled" => false,
                "name-short" => "Channel",
                "name-short-plural" => "Channels",
                "channel0" => "Undefined",
                "allow_create_from_app" => false,
                "join_button_help_text" => "",
            ),
            "post" => array(
                "enabled" => true,
                "show_upcoming_event" => true,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
                "post_disclaimer" => array(
                    "enabled" => false,
                    "enabled_default"=> false,
                    "disclaimer" => "Disclaimer: ",
                ),
                "require_email_review_before_publish" => false,
                "disable_email_publish" => false,
                "show_global_posts_in_group_feed"=>false,
                "comments" => true,
                "likes" => true,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "alt_name" => array( "en" => ''),
                'attachments' => [
                    'enabled' => true,
                ],
                'publish' => array(
                    'preselect_email_publish' => true,
                ),
                'approvals' => array (
                    'enabled' => false,
                    'tasks' => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "aboutus" => array(
                "enabled" => true,
            ),
            "profile" => array(
                "enable_pronouns" => true,
                "enable_bio" => true,
                "allow_update_name" => true,
                "allow_update_pronouns" => true,
                "allow_meeting_link" => true,
                "allow_account_deletion" => true,
                'allow_delegated_access' => true,
            ),
            "helpvideos" => array(
                "enabled" => true,
            ),
            "donations" => array("enabled"=>false),
            "event" => array(
                "enabled" => true,
                "checkin" => true,
                'checkin_show_signin' => true,
                'speakers' => array (
                    'enabled' => false,
                    'approvals' => false
                ),
                "rsvp_link" => true,
                "checkin_default" => true,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => false,
                ),
                "analytics" => true,
                "budgets" => false,
                "calendar_loc_filter" => false,
                "photo_disclaimer" => array(
                    "enabled" => false,
                    "enabled_default"=> false,
                    "disclaimer" => "Disclaimer: Photos and videos will be recorded during this event. By joining the event you are providing your consent for your photos and videos to be recorded.",
                    "show_on_top_in_emails"=>true,
                ),
                "meeting_links" => array(
                    "msteams" => false,
                    "gmeet" => false,
                    "zoom" => false
                ),
                "cultural_observances" => array(
                    "enabled" => true,
                ),

                "web_conf_detail_message_override" => '',

                "require_email_review_before_publish" => false,
                "show_global_events_in_group_feed"=>false,
                "volunteers" => false,
                'external_volunteers' => false,
                "comments" => true,
                "likes" => true,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "enable_event_surveys"=>false,
                "my_events" => array(
                    "enabled" => false,
                    "event_submissions" => false,
                    "discover_events" => false
                ),
                "custom_fields" => array(
                    "enable_visible_only_if_logic" => false
                ),
                "partner_organizations" => array (
                    "enabled" => false,
                    "is_required" => false
                ),
                "is_description_required" => false,
                'attachments' => [
                    'enabled' => true,
                ],
                'event_form' => array (
                    'show_module_settings' => false,
                    'enable_participation_limit_by_default'=>false
                ),
                'publish' => array(
                    'preselect_email_publish' => true,
                ),
                'approvals' => array (
                    'enabled' => false,
                    'tasks' => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
                'collaborations' => array(
                    'auto_approve' => false,
                ),
                'event_contributors' => array(
                    'enabled' => false,
                ),
                'disable_event_conflict_checks'=>false,
                'disable_email_publish' => false,
                'rsvp_display' => array (
                    'allow_updates' => true,
                    'default_value' => 2, // Valid values are 0,1,2,3 for not showing, showing count only,showing avataars + counts respectively
                ),
                'reconciliation' => array (
                    'enabled' => false
                ),
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "calendar" => array(
                "enabled" => true,
                "location_filter" => false,
                "allow_embed" => false,
                "enable_secure_embed" => true,
            ),
            "recruiting" => array(
                "enabled" => false,
            ),
            "referral" => array(
                "enabled" => false,
            ),
            "messaging" => array(
                "enabled" => true,
                'restrict_to_admin_only' => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => false,
                ),
                'attachments' => [
                    'enabled' => true,
                ],
            ),
            "budgets" => array(
                "enabled" => false,
                "allow_grouplead_to_edit_expense" => true,
                "currency"=>"USD",
                "locale"=>"en_US",
                'other_funding' => true,
                "show_expense_budget"=>true,
                "enable_budget_requests" => true,
                "enable_budget_expenses" => true,
                "allocated_budget_definition" => '',
                "other_funding_definition" => '',
                'attachments' => [
                    'enabled' => true,
                ],
                'vendors' => array(
                    'enabled' => true,
                ),
            ),
            "resources" => array(
                "enabled" => false,
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "newsletters" => array(
                "enabled" => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => false,
                ),
                "require_email_review_before_publish" => false,
                "disable_email_publish" => false,
                "show_global_newsletters_in_group_feed" => false,
                "comments" => true,
                "likes" => true,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                'publish' => array(
                    'preselect_email_publish' => true,
                ),
                'approvals' => array (
                    'enabled' => false,
                    'tasks' => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "surveys" => array(
                "enabled" => false,
                "analytics" => false,
                "restrict_publish_to_admin_only" => false,
                "question_types_csv" => "text,comment,boolean,checkbox,radiogroup,dropdown,rating,ranking,imagepicker,matrix,matrixdropdown,html",
                "allow_create_chapter_scope" => true,
                "allow_create_channel_scope" => true,
                "default_anonymous_survey" => false,
                "approvals" => array (
                    "enabled" => false,
                    "tasks" => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
            ),
            "integrations" => array(
                "group" => array(
                    "enabled" => false,
                    "workplace" => false,
                    "yammer" => false,
                    "teams" => false,
                    "googlechat" => false,
                    "slack" => false,
                ),
                'analytics' => [
                    'adobe' => [
                        'enabled' => false,
                        'js_src' => '   ', #Intentionally set to three blanks, do not change
                    ],
                ],
            ),
            "communications" => array(
                "enabled" => true,
            ),
            "stats" => array(
                "enabled" => false,
                "grouplead_exec" => true,
                "grouplead_group" => true,
                "grouplead_region" => false,
                "grouplead_chapter" => true,
                "grouplead_channel" => true,
                "member_1" => true,
                "member_2" => true,
                "member_3" => true,
                "teams_active" => true,
                "teams_completed" => true,
                "teams_not_completed" => false,
                "teams_mentors_active" => true,
                "teams_mentors_completed" => true,
                "teams_mentors_not_completed" => false,
                "teams_mentors_registered" => true,
                "teams_mentees_active" => true,
                "teams_mentees_completed" => true,
                "teams_mentees_not_completed" => false,
                "teams_mentees_registered" => true,
            ),
            "policy" => array(
                "image_upload_notice" => "The images you attached may be copyrighted. Please only continue if you have rights to use the image."
            ),
            "teams" => array(
                "name" => "Team",
                "name-plural" => "Teams",
                "enabled" => false,
                "name-short" => "Team",
                "name-short-plural" => "Teams",
                "comments" => true,
                "likes" => false,
                "enable_media_upload_on_comment"=>true,
                "team_events" => [
                    "enabled" => false,
                    "event_list" => [
                        "enabled" => true,
                        "show_all" => false,
                    ],
                    "detailed_ics" => false,
                    'disable_event_types'=>false
                ],
                "teambuilder_enabled" => false,
                'teambuilder_debug' => false,
                'search' => false,
                'hashtag' => array (
                    "enabled" => true,
                    "can_create_realtime_on_userend" => false,
                ),
                'attachments' => [
                    'enabled' => true,
                ],
            ),
            "reports" => array(
                "analytics" => true,
            ),
            "booking" => array(
                "enabled" => false,
                "alt_name" => array( "en" => ''),
            ),
            "header" => array(
                "notifications" => false,
                "show_my_location_menu" => true,
            ),
            "footer" => array (
                "show_guides" => true,
                "show_release_notes" => true,
                "show_training_videos" => true,
                "show_feedback_link" => true,
                "show_support_link" => true,
                "show_mailing_list" => true,
            ),
            "comment" => array (
                "custom_terms_of_use" => array(
                    "enabled" => false,
                    "title" => 'Company specific Terms of Use for comments',
                    "html" => '<p>While commenting please note the following:</p><p><ul><li>Item1</li><li>Item2</li></ul></p>',
                ) ,
            ),
            "locales" => array(
                "enabled" => false,
                "languages_allowed" => array(
                    'en' => array("enabled" => true),
                    'es_ES' => array("enabled" => false),
                    'es_MX' => array("enabled" => false),
                    'fr_FR' => array("enabled" => false),
                    'fr_CA' => array("enabled" => false),
                    'de_DE' => array("enabled" => false),
                    'hi_IN' => array("enabled" => false),
                    'ja_JP' => array("enabled" => false),
                    'ms_MY' => array("enabled" => false),
                    'ko_KR' => array("enabled" => false),
                    'pt_PT' => array("enabled" => false),
                    'pt_BR' => array("enabled" => false),
                    'zh_CN' => array("enabled" => false),
                    'th_TH' => array("enabled" => false),
                    'it_IT' => array("enabled" => false),
                    'id_ID' => array("enabled" => false),
                    'fil_PH' => array("enabled" => false),
                    'vi_VN' => array("enabled" => false),
                )
            ),
            "discussions" => array(
                "enabled" => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => false,
                ),
                "analytics" => false,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "anonymous_post_setting" => false,
                "disable_email_publish" => false,
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "albums" => array(
                "enabled" => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => false,
                ),
                "upload_media_disclaimer" => array(
                    "enabled" => false,
                    "disclaimer" => "Disclaimer: The Photos and videos you are uploading may be copyrighted. Please only continue if you have rights to use them.",
                ),
                "analytics" => false,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "comments" => true,
                "likes" => true,
            ),
            "recognition" => array(
                "enabled" => false,
                "alt_name" => array(
                    "en" => ''
                ),
                "comments" => false,
                "likes" => false,
                "enable_media_upload_on_comment"=>false,
            ),
            'mobileapp' =>array(
                "enabled" => false,
                "custom" => array (
                    "enabled" => false,
                    "mobile_app_name" => '',
                    "mobile_app_ios_url" => '',
                    "mobile_app_android_url" => '',
                    "support_link" => false
                ),
                "show_cta_banner_if_joined_group_less_than" => 0,
                "show_global_feed" => true
            ),
            'user_inbox' => array (
                'enabled' => false
            ),
            'dynamic_list' => array (
                'enabled' => false
            ),
            'points' => array (
                'enabled' => false,
                'frontend_enabled' => false,
                'enable_point_conversion_rate' => false,
            ),
            'emails' => array (
                'show_group_logo_instead_of_company_logo' => false,
                'generic_disclaimer_html' => "<div style='background-color: #ffb666;'></div>",
                'show_event_top_button_in_email' => true,
                'add_emailed_by_block' => true,
                'allow_send_to_external_emails' => false,
            ),
            'plugins' => array (
                'google_maps' => true
            ),
            'my_schedule' => array (
                'enabled' => false,
            ),
            'search' => array (
                'enabled' => true,
            ),
            'content_moderator' => array (
                'enabled' => false,
            ),
        ),
        "style" => array (
            "css" => array (
                "body" => array(
                    "background-color" => '#d3d3d1'
                ),
                "profile_initial_color" => array(
                    "color" => ''
                )
            ),
            "override_fonts_css_url" => "",
            "zone_tile_bg_image" => "",
            "zone_tile_compact_bg_image"=>"",
            "zone_tile_heading" => "",
            "zone_tile_sub_heading" => "",
        )
    );

    public const DEFAULT_SETTINGS_PEOPLEHERO = array(
        "app" => array(
            "group" => array(
                "name" => "Module",
                "name-plural" => "Modules",
                "name-short" => "Module",
                "name-short-plural" => "Modules",
                "groupname0" => "Admin",
                "group0_color" => 'rgb(80,80,80)',
                "group0_color2" => 'rgb(160,160,160)',
                "group_category_enabled" => false,
                "manage" => array (
                    "allow_update_groupleads" => true,
                    "allow_update_chapterleads" => true,
                    "allow_update_channelleads" => true,
                    "allow_add_members" => true,
                ),
                "tabs" => array(
                    "enabled" => false,
                    "yammer" => false,
                    "streams" => false,
                    "custom" => false,
                ),
                "memberlabel"=>"Member",
                "memberlabel_plural"=>"Members",
                "allow_anonymous_join"=>true,
                "homepage"=> array(
                    "show_member_count_in_tile" => true,
                    "show_chapter_channel_count_in_tile" => true,
                    "show_global_feed" => true,
                    "show_my_groups_option" => true,
                    "show_chapter_content_in_global_feed" => false,
                    "show_channel_content_in_global_feed" => false,
                    "show_past_events_in_show_all_in_global_feed" => true,
                ),
                "group_export" => false,
                "member_restrictions" => false,
                "allow_invite_members" => true,
                "qrcode"=>true,
            ),
            "linked-group" => array (
                "enabled" => false,
            ),
            "disclaimer_consent" => array (
                "enabled" => false,
            ),
            "chapter" => array(
                "name" => "Chapter",
                "name-plural" => "Chapters",
                "enabled" => false,
                "name-short" => "Chapter",
                "name-short-plural" => "Chapters",
                "chapter0" => "Default Chapter",
                "allow_create_from_app" => false,
                "join_button_help_text" => "",
            ),
            "channel" => array(
                "name" => "Channel",
                "name-plural" => "Channels",
                "enabled" => false,
                "name-short" => "Channel",
                "name-short-plural" => "Channels",
                "channel0" => "Default Channel",
                "allow_create_from_app" => false,
                "join_button_help_text" => "",
            ),
            "post" => array(
                "enabled" => true,
                "show_upcoming_event" => true,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
                "post_disclaimer" => array(
                    "enabled" => false,
                    "enabled_default"=> false,
                    "disclaimer" => "Disclaimer: ",
                ),
                "analytics" => false,
                "require_email_review_before_publish" => false,
                "disable_email_publish" => false,
                "show_global_posts_in_group_feed"=>true,
                "comments" => true,
                "likes" => true,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "alt_name" => array( "en" => ''),
                'attachments' => [
                    'enabled' => true,
                ],
                'publish' => array(
                    'preselect_email_publish' => true,
                ),
                'approvals' => array (
                    'enabled' => false,
                    'tasks' => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "aboutus" => array(
                "enabled" => true,
            ),
            "profile" => array(
                "enable_pronouns" => true,
                "enable_bio" => true,
                "allow_update_name" => true,
                "allow_update_pronouns" => true,
                "allow_meeting_link" => true,
                "allow_account_deletion" => true,
                'allow_delegated_access' => true,
            ),
            "helpvideos" => array(
                "enabled" => true,
            ),
            "donations" => array("enabled"=>false),
            "event" => array(
                "enabled" => true,
                "checkin" => true,
                'checkin_show_signin' => true,
                'speakers' => array (
                    'enabled' => false,
                    'approvals' => false
                ),
                "rsvp_link" => true,
                "checkin_default" => true,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => false,
                ),
                "analytics" => true,
                "budgets" => false,
                "calendar_loc_filter" => false,
                "photo_disclaimer" => array(
                    "enabled" => false,
                    "enabled_default"=> false,
                    "disclaimer" => "Disclaimer: Photos and videos will be recorded during this event. By joining the event you are providing your consent for your photos and videos to be recorded.",
                    "show_on_top_in_emails"=>true,
                ),
                "meeting_links" => array(
                    "msteams" => false,
                    "gmeet" => false,
                    "zoom" => false
                ),
                "cultural_observances" => array(
                    "enabled" => true,
                ),

                "web_conf_detail_message_override" => '',

                "require_email_review_before_publish" => false,
                "show_global_events_in_group_feed"=>true,
                "volunteers" => false,
                'external_volunteers' => false,
                "comments" => true,
                "likes" => true,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "enable_event_surveys"=>false,
                "my_events" => array(
                    "enabled" => false,
                    "event_submissions" => false,
                    "discover_events" => false
                ),
                "custom_fields" => array(
                    "enable_visible_only_if_logic" => false
                ),
                "partner_organizations" => array (
                    "enabled" => false,
                    "is_required" => false
                ),
                "is_description_required" => false,
                'attachments' => [
                    'enabled' => true,
                ],
                'event_form' => array (
                    'show_module_settings' => false,
                    'enable_participation_limit_by_default'=>false
                ),
                'publish' => array(
                    'preselect_email_publish' => true,
                ),
                'approvals' => array (
                    'enabled' => false,
                    'tasks' => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
                'collaborations' => array(
                    'auto_approve' => false,
                ),
                'event_contributors' => array(
                    'enabled' => false,
                ),
                'disable_event_conflict_checks'=>false,
                'disable_email_publish' => false,
                'rsvp_display' => array (
                    'allow_updates' => true,
                    'default_value' => 2, // Valid values are 0,1,2,3 for not showing, showing count only,showing avataars + counts respectively
                ),
                'reconciliation' => array (
                    'enabled' => false
                ),
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "calendar" => array(
                "enabled" => true,
                "location_filter" => false,
                "allow_embed" => false,
                "enable_secure_embed" => true,
            ),
            "recruiting" => array(
                "enabled" => false,
            ),
            "referral" => array(
                "enabled" => false,
            ),
            "messaging" => array(
                "enabled" => true,
                'restrict_to_admin_only' => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
                'attachments' => [
                    'enabled' => true,
                ],
            ),
            "budgets" => array(
                "enabled" => false,
                "allow_grouplead_to_edit_expense" => true,
                "currency"=>"USD",
                "locale"=>"en_US",
                'other_funding' => true,
                "show_expense_budget"=>true,
                "enable_budget_requests" => true,
                "enable_budget_expenses" => true,
                "allocated_budget_definition" => '',
                "other_funding_definition" => '',
                'attachments' => [
                    'enabled' => true,
                ],
                'vendors' => array(
                    'enabled' => true,
                ),
            ),
            "resources" => array(
                "enabled" => false,
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "newsletters" => array(
                "enabled" => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
                "require_email_review_before_publish" => false,
                "disable_email_publish" => false,
                "show_global_newsletters_in_group_feed" => true,
                "comments" => true,
                "likes" => true,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                'publish' => array(
                    'preselect_email_publish' => true,
                ),
                'approvals' => array (
                    'enabled' => false,
                    'tasks' => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "surveys" => array(
                "enabled" => false,
                "analytics" => false,
                "restrict_publish_to_admin_only" => false,
                "question_types_csv" => "text,comment,boolean,checkbox,radiogroup,dropdown,rating,ranking,imagepicker,matrix,matrixdropdown,html",
                "allow_create_chapter_scope" => true,
                "allow_create_channel_scope" => true,
                "default_anonymous_survey" => false,
                "approvals" => array (
                    "enabled" => false,
                    "tasks" => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
            ),
            "integrations" => array(
                "group" => array(
                    "enabled" => false,
                    "workplace" => false,
                    "yammer" => false,
                    "teams" => false,
                    "googlechat" => false,
                    "custom" => false,
                    "slack" => false,
                ),
                'analytics' => [
                    'adobe' => [
                        'enabled' => false,
                        'js_src' => '   ', #Intentionally set to three blanks, do not change
                    ],
                ],
            ),
            "communications" => array(
                "enabled" => true,
            ),
            "stats" => array(
                "enabled" => true,
                "grouplead_exec" => true,
                "grouplead_group" => true,
                "grouplead_region" => false,
                "grouplead_chapter" => true,
                "grouplead_channel" => true,
                "member_1" => true,
                "member_2" => true,
                "member_3" => true,
                "teams_active" => true,
                "teams_completed" => true,
                "teams_not_completed" => false,
                "teams_mentors_active" => true,
                "teams_mentors_completed" => true,
                "teams_mentors_not_completed" => false,
                "teams_mentors_registered" => true,
                "teams_mentees_active" => true,
                "teams_mentees_completed" => true,
                "teams_mentees_not_completed" => false,
                "teams_mentees_registered" => true,
            ),
            "policy" => array(
                "image_upload_notice" => "The images you attached may be copyrighted. Please only continue if you have rights to use the image."
            ),
            "teams" => array(
                "name" => "Team",
                "name-plural" => "Teams",
                "enabled" => false,
                "name-short" => "Team",
                "name-short-plural" => "Teams",
                "comments" => true,
                "likes" => false,
                "enable_media_upload_on_comment"=>true,
                "team_events" => [
                    "enabled" => false,
                    "event_list" => [
                        "enabled" => true,
                        "show_all" => false,
                    ],
                    "detailed_ics" => false,
                    'disable_event_types'=>false
                ],
                "teambuilder_enabled" => false,
                'teambuilder_debug' => false,
                'search' => false,
                'hashtag' => array (
                    "enabled" => true,
                    "can_create_realtime_on_userend" => false,
                ),
                'attachments' => [
                    'enabled' => true,
                ],
            ),
            "reports" => array(
                "analytics" => true,
            ),
            "booking" => array(
                "enabled" => false,
                "alt_name" => array( "en" => ''),
            ),
            "header" => array(
                "notifications" => false,
                "show_my_location_menu" => false,
            ),
            "footer" => array (
                "show_guides" => true,
                "show_release_notes" => true,
                "show_training_videos" => true,
                "show_feedback_link" => true,
                "show_support_link" => true,
                "show_mailing_list" => true,
            ),
            "comment" => array (
                "custom_terms_of_use" => array(
                    "enabled" => false,
                    "title" => 'Company specific Terms of Use for comments',
                    "html" => '<p>While commenting please note the following:</p><p><ul><li>Item1</li><li>Item2</li></ul></p>',
                ) ,
            ),
            "locales" => array(
                "enabled" => false,
                "languages_allowed" => array(
                    'en' => array("enabled" => true),
                    'es_ES' => array("enabled" => false),
                    'es_MX' => array("enabled" => false),
                    'fr_FR' => array("enabled" => false),
                    'fr_CA' => array("enabled" => false),
                    'de_DE' => array("enabled" => false),
                    'hi_IN' => array("enabled" => false),
                    'ja_JP' => array("enabled" => false),
                    'ms_MY' => array("enabled" => false),
                    'ko_KR' => array("enabled" => false),
                    'pt_PT' => array("enabled" => false),
                    'pt_BR' => array("enabled" => false),
                    'zh_CN' => array("enabled" => false),
                    'th_TH' => array("enabled" => false),
                    'it_IT' => array("enabled" => false),
                    'id_ID' => array("enabled" => false),
                    'fil_PH' => array("enabled" => false),
                    'vi_VN' => array("enabled" => false),
                )
            ),
            "discussions" => array(
                "enabled" => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
                "analytics" => false,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "anonymous_post_setting" => true,
                "disable_email_publish" => false,
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "albums" => array(
                "enabled" => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
                "upload_media_disclaimer" => array(
                    "enabled" => false,
                    "disclaimer" => "Disclaimer: The Photos and videos you are uploading may be copyrighted. Please only continue if you have rights to use them.",
                ),
                "analytics" => false,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "comments" => true,
                "likes" => true,
            ),
            "recognition" => array(
                "enabled" => false,
                "alt_name" => array(
                    "en" => ''
                ),
                "comments" => false,
                "likes" => false,
                "enable_media_upload_on_comment"=>false,
            ),
            'mobileapp' =>array(
                "enabled" => true,
                "custom" => array (
                    "enabled" => false,
                    "mobile_app_name" => '',
                    "mobile_app_ios_url" => '',
                    "mobile_app_android_url" => '',
                    "support_link" => false
                ),
                "show_cta_banner_if_joined_group_less_than" => 3,
                "show_global_feed" => true
            ),
            'user_inbox' => array (
                'enabled' => false
            ),
            'dynamic_list' => array (
                'enabled' => false
            ),
            'points' => array (
                'enabled' => false,
                'frontend_enabled' => false,
                'enable_point_conversion_rate' => false,
            ),
            'emails' => array (
                'show_group_logo_instead_of_company_logo' => false,
                'generic_disclaimer_html' => "<div style='background-color: #ffb666;'></div>",
                'show_event_top_button_in_email' => true,
                'add_emailed_by_block' => true,
                'allow_send_to_external_emails' => false,
            ),
            'plugins' => array (
                'google_maps' => true
            ),
            'my_schedule' => array (
                'enabled' => false,
            ),
            'search' => array (
                'enabled' => true,
            ),
            'content_moderator' => array (
                'enabled' => false,
            ),
        ),
        "style" => array (
            "css" => array (
                "body" => array(
                    "background-color" => '#d3d3d1'
                ),
                "profile_initial_color" => array(
                    "color" => ''
                )
            ),
            "override_fonts_css_url" => "",
            "zone_tile_bg_image" => "",
            "zone_tile_compact_bg_image"=>"",
            "zone_tile_heading" => "",
            "zone_tile_sub_heading" => "",
        )
    );

    // Note only associative arrays are supported.
    public const DEFAULT_SETTINGS_TALENTPEAK = array(
        "app" => array(
            "group" => array(
                "name" => "Development Program",
                "name-plural" => "Development Programs",
                "name-short" => "Program",
                "name-short-plural" => "Programs",
                "groupname0" => "Admin",
                "group0_color" => 'rgb(80,80,80)',
                "group0_color2" => 'rgb(160,160,160)',
                "group_category_enabled" => false,
                "manage" => array (
                    "allow_update_groupleads" => true,
                    "allow_update_chapterleads" => true,
                    "allow_update_channelleads" => true,
                    "allow_add_members" => true,
                ),
                "tabs" => array(
                    "enabled" => false,
                    "yammer" => false,
                    "streams" => false,
                    "custom" => false,
                ),
                "memberlabel" => "Employee",
                "memberlabel_plural"=>"Employees",
                "allow_anonymous_join"=>false,
                "homepage"=> array(
                    "show_member_count_in_tile" => true,
                    "show_chapter_channel_count_in_tile" => true,
                    "show_global_feed" => false,
                    "show_my_groups_option" => false,
                    "show_chapter_content_in_global_feed" => false,
                    "show_channel_content_in_global_feed" => false,
                    "show_past_events_in_show_all_in_global_feed" => true,
                ),
                "group_export" => false,
                "member_restrictions" => false,
                "allow_invite_members" => true,
                "qrcode"=>true,
            ),
            "linked-group" => array (
                "enabled" => false,
            ),
            "disclaimer_consent" => array (
                "enabled" => false,
            ),
            "chapter" => array(
                "name" => "Chapter",
                "name-plural" => "Chapters",
                "enabled" => false,
                "name-short" => "Chapter",
                "name-short-plural" => "Chapters",
                "chapter0" => "Undefined",
                "allow_create_from_app" => false,
                "join_button_help_text" => "",
            ),
            "channel" => array(
                "name" => "Channel",
                "name-plural" => "Channels",
                "enabled" => false,
                "name-short" => "Channel",
                "name-short-plural" => "Channels",
                "channel0" => "Undefined",
                "allow_create_from_app" => false,
                "join_button_help_text" => "",
            ),
            "post" => array(
                "enabled" => false,
                "show_upcoming_event" => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
                "post_disclaimer" => array(
                    "enabled" => false,
                    "enabled_default"=> false,
                    "disclaimer" => "Disclaimer: ",
                ),
                "analytics" => false,
                "require_email_review_before_publish" => false,
                "disable_email_publish" => false,
                "show_global_posts_in_group_feed"=>false,
                "comments" => true,
                "likes" => true,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "alt_name" => array( "en" => ''),
                'attachments' => [
                    'enabled' => true,
                ],
                'publish' => array(
                    'preselect_email_publish' => true,
                ),
                'approvals' => array (
                    'enabled' => false,
                    'tasks' => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "aboutus" => array(
                "enabled" => true,
            ),
            "profile" => array(
                "enable_pronouns" => true,
                "enable_bio" => true,
                "allow_update_name" => true,
                "allow_update_pronouns" => true,
                "allow_meeting_link" => true,
                "allow_account_deletion" => true,
                'allow_delegated_access' => true,
            ),
            "helpvideos" => array(
                "enabled" => true,
            ),
            "donations" => array("enabled"=>false),
            "event" => array(
                "enabled" => true,
                "checkin" => true,
                'checkin_show_signin' => true,
                'speakers' => array (
                    'enabled' => false,
                    'approvals' => false
                ),
                "rsvp_link" => true,
                "checkin_default" => true,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
                "analytics" => true,
                "budgets" => false,
                "calendar_loc_filter" => false,
                "photo_disclaimer" => array(
                    "enabled" => false,
                    "enabled_default"=> false,
                    "disclaimer" => "Disclaimer: Photos and videos will be recorded during this event. By joining the event you are providing your consent for your photos and videos to be recorded.",
                    "show_on_top_in_emails"=>true,
                ),
                "meeting_links" => array(
                    "msteams" => false,
                    "gmeet" => false,
                    "zoom" => false
                ),
                "cultural_observances" => array(
                    "enabled" => true,
                ),

                "web_conf_detail_message_override" => '',

                "require_email_review_before_publish" => false,
                "show_global_events_in_group_feed"=>false,
                "volunteers" => false,
                'external_volunteers' => false,
                "comments" => true,
                "likes" => true,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "enable_event_surveys"=>false,
                "my_events" => array(
                    "enabled" => false,
                    "event_submissions" => false,
                    "discover_events" => false
                ),
                "custom_fields" => array(
                    "enable_visible_only_if_logic" => false
                ),
                "partner_organizations" => array (
                    "enabled" => false,
                    "is_required" => false
                ),
                "is_description_required" => false,
                'attachments' => [
                    'enabled' => true,
                ],
                'event_form' => array (
                    'show_module_settings' => false,
                    'enable_participation_limit_by_default'=>false
                ),
                'publish' => array(
                    'preselect_email_publish' => true,
                ),
                'approvals' => array (
                    'enabled' => false,
                    'tasks' => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
                'collaborations' => array(
                    'auto_approve' => false,
                ),
                'event_contributors' => array(
                    'enabled' => false,
                ),
                'disable_event_conflict_checks'=>false,
                'disable_email_publish' => false,
                'rsvp_display' => array (
                    'allow_updates' => true,
                    'default_value' => 2, // Valid values are 0,1,2,3 for not showing, showing count only,showing avataars + counts respectively
                ),
                'reconciliation' => array (
                    'enabled' => false
                ),
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "calendar" => array(
                "enabled" => true,
                "location_filter" => false,
                "allow_embed" => false,
                "enable_secure_embed" => true,
            ),
            "recruiting" => array(
                "enabled" => false,
            ),
            "referral" => array(
                "enabled" => false,
            ),
            "messaging" => array(
                "enabled" => true,
                'restrict_to_admin_only' => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
                'attachments' => [
                    'enabled' => true,
                ],
            ),
            "budgets" => array(
                "enabled" => false,
                "allow_grouplead_to_edit_expense" => true,
                "currency"=>"USD",
                "locale"=>"en_US",
                'other_funding' => true,
                "show_expense_budget"=>true,
                "enable_budget_requests" => true,
                "enable_budget_expenses" => true,
                "allocated_budget_definition" => '',
                "other_funding_definition" => '',
                'attachments' => [
                    'enabled' => true,
                ],
                'vendors' => array(
                    'enabled' => true,
                ),
            ),
            "resources" => array(
                "enabled" => true,
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "newsletters" => array(
                "enabled" => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
                "require_email_review_before_publish" => false,
                "disable_email_publish" => false,
                "show_global_newsletters_in_group_feed" => false,
                "comments" => true,
                "likes" => true,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                'publish' => array(
                    'preselect_email_publish' => true,
                ),
                'approvals' => array (
                    'enabled' => false,
                    'tasks' => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "surveys" => array(
                "enabled" => true,
                "analytics" => true,
                "restrict_publish_to_admin_only" => false,
                "question_types_csv" => "text,comment,boolean,checkbox,radiogroup,dropdown,rating,ranking,imagepicker,matrix,matrixdropdown,html",
                "allow_create_chapter_scope" => true,
                "allow_create_channel_scope" => true,
                "default_anonymous_survey" => false,
                "approvals" => array (
                    "enabled" => false,
                    "tasks" => false,
                    'allow_update_title_after_approval_start' => true,
                    'allow_update_description_after_approval_start' => true,
                ),
            ),
            "integrations" => array(
                "group" => array(
                    "enabled" => false,
                    "workplace" => false,
                    "yammer" => false,
                    "teams" => false,
                    "googlechat" => false,
                    "slack" => false,
                ),
                'analytics' => [
                    'adobe' => [
                        'enabled' => false,
                        'js_src' => '   ', #Intentionally set to three blanks, do not change
                    ],
                ],
            ),
            "communications" => array(
                "enabled" => true,
            ),
            "stats" => array(
                "enabled" => false,
                "grouplead_exec" => true,
                "grouplead_group" => true,
                "grouplead_region" => false,
                "grouplead_chapter" => true,
                "grouplead_channel" => true,
                "member_1" => true,
                "member_2" => true,
                "member_3" => true,
                "teams_active" => true,
                "teams_completed" => true,
                "teams_not_completed" => false,
                "teams_mentors_active" => true,
                "teams_mentors_completed" => true,
                "teams_mentors_not_completed" => false,
                "teams_mentors_registered" => true,
                "teams_mentees_active" => true,
                "teams_mentees_completed" => true,
                "teams_mentees_not_completed" => false,
                "teams_mentees_registered" => true,
            ),
            "policy" => array(
                "image_upload_notice" => "The images you attached may be copyrighted. Please only continue if you have rights to use the image."
            ),
            "teams" => array(
                "name" => "Team",
                "name-plural" => "Teams",
                "enabled" => true,
                "name-short" => "Team",
                "name-short-plural" => "Teams",
                "comments" => true,
                "likes" => true,
                "enable_media_upload_on_comment"=>true,
                "team_events" => [
                    "enabled" => false,
                    "event_list" => [
                        "enabled" => true,
                        "show_all" => false,
                    ],
                    "detailed_ics" => false,
                    'disable_event_types'=>false
                ],
                "teambuilder_enabled" => false,
                'teambuilder_debug' => false,
                'search' => false,
                'hashtag' => array (
                    "enabled" => true,
                    "can_create_realtime_on_userend" => false,
                ),
                'attachments' => [
                    'enabled' => true,
                ],
            ),
            "reports" => array(
                "analytics" => true,
            ),
            "booking" => array(
                "enabled" => false,
                "alt_name" => array( "en" => ''),
            ),
            "header" => array(
                "notifications" => false,
                "show_my_location_menu" => false,
            ),
            "footer" => array (
                "show_guides" => true,
                "show_release_notes" => true,
                "show_training_videos" => true,
                "show_feedback_link" => true,
                "show_support_link" => true,
                "show_mailing_list" => true,
            ),
            "comment" => array (
                "custom_terms_of_use" => array(
                    "enabled" => false,
                    "title" => 'Company specific Terms of Use for comments',
                    "html" => '<p>While commenting please note the following:</p><p><ul><li>Item1</li><li>Item2</li></ul></p>',
                ) ,
            ),
            "locales" => array(
                "enabled" => false,
                "languages_allowed" => array(
                    'en' => array("enabled" => true),
                    'es_ES' => array("enabled" => false),
                    'es_MX' => array("enabled" => false),
                    'fr_FR' => array("enabled" => false),
                    'fr_CA' => array("enabled" => false),
                    'de_DE' => array("enabled" => false),
                    'hi_IN' => array("enabled" => false),
                    'ja_JP' => array("enabled" => false),
                    'ms_MY' => array("enabled" => false),
                    'ko_KR' => array("enabled" => false),
                    'pt_PT' => array("enabled" => false),
                    'pt_BR' => array("enabled" => false),
                    'zh_CN' => array("enabled" => false),
                    'th_TH' => array("enabled" => false),
                    'it_IT' => array("enabled" => false),
                    'id_ID' => array("enabled" => false),
                    'fil_PH' => array("enabled" => false),
                    'vi_VN' => array("enabled" => false),
                )
            ),
            "discussions" => array(
                "enabled" => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
                "analytics" => false,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "anonymous_post_setting" => false,
                "disable_email_publish" => false,
                'pinning' => [
                    'enabled' => true,
                ],
            ),
            "albums" => array(
                "enabled" => false,
                "email_tracking" => array (
                    "enabled" => true,
                    "track_urls" => true,
                ),
                "upload_media_disclaimer" => array(
                    "enabled" => false,
                    "disclaimer" => "Disclaimer: The Photos and videos you are uploading may be copyrighted. Please only continue if you have rights to use them.",
                ),
                "analytics" => false,
                "show_in_home_feed" => true,
                "enable_media_upload_on_comment"=>false,
                "comments" => true,
                "likes" => true,
            ),
            "recognition" => array(
                "enabled" => false,
                "alt_name" => array(
                    "en" => ''
                ),
                "comments" => false,
                "likes" => false,
                "enable_media_upload_on_comment"=>false,
            ),
            'mobileapp' =>array(
                "enabled" => false,
                "custom" => array (
                    "enabled" => false,
                    "mobile_app_name" => '',
                    "mobile_app_ios_url" => '',
                    "mobile_app_android_url" => '',
                    "support_link" => false
                ),
                "show_cta_banner_if_joined_group_less_than" => 1,
                "show_global_feed" => true
            ),
            'user_inbox' => array (
                'enabled' => false
            ),
            'dynamic_list' => array (
                'enabled' => false
            ),
            'points' => array (
                'enabled' => false,
                'frontend_enabled' => false,
                'enable_point_conversion_rate' => false,
            ),
            'emails' => array (
                'show_group_logo_instead_of_company_logo' => false,
                'generic_disclaimer_html' => "<div style='background-color: #ffb666;'></div>",
                'show_event_top_button_in_email' => true,
                'add_emailed_by_block' => true,
                'allow_send_to_external_emails' => false,
            ),
            'plugins' => array (
                'google_maps' => true
            ),
            'my_schedule' => array (
                'enabled' => false,
            ),
            'search' => array (
                'enabled' => true,
            ),
            'content_moderator' => array (
                'enabled' => false,
            ),
        ),
        "style" => array (
            "css" => array (
                "body" => array(
                    "background-color" => '#d3d3d1'
                ),
                "profile_initial_color" => array(
                    "color" => ''
                )
            ),
            "override_fonts_css_url" => "",
            "zone_tile_bg_image" => "",
            "zone_tile_compact_bg_image"=>"",
            "zone_tile_heading" => "",
            "zone_tile_sub_heading" => "",
        )
    );

    public const USER_LIFECYCLE_SETTING_DEFAULTS = [
        'allow_delete' => false,  // Delete only this value is set to true
        'delete_after_days' => 30, // Number of days for which a user should not be modified before it can be deleted
    ];

    public const APP_LABEL = array(
        'teleskope' => 'Teleskope',
        'affinities' => 'Affinities',
        'officeraven'  => 'Office Raven',
        'talentpeak' => 'Talent Peak',
        'peoplehero'  => 'People Hero',
    );

    public const DEFAULT_SECURITY_SETTINGS = array(
        'admin_inactivity_max' => 480,
        'admin_session_max'  => 1440,
        'admin_whitelist_ip' => '0.0.0.0/0',
        'apps_inactivity_max' => 2880,
        'apps_session_max' => 10080,
        'mobile_session_max'=>43200,
        'mobile_session_logout_time_in_min_utc' => 0,
        'company_admin_external_roles' => null,
        'zone_admin_external_roles' => null,
        'group_lead_external_roles' => null,
        'chapter_lead_external_roles' => null,
        'channel_lead_external_roles' => null,
    );

    public const S3_AREA = array(
        'ABOUT' => '/aboutus/',
        'POST' => '/post/',
        'EVENT' => '/event/',
        'EVENT_REMINDER' => '/event_reminder/',
        'EVENT_FOLLOWUP' => '/event_followup/',
        'EVENT_VOLUNTEER' => '/event_volunteer/',
        'MESSAGES' => '/message/',
        'NEWSLETTER' => '/newsletters/',
        'NEWSLETTER_ATTACH' => '/newsletter_attachments/',
        'GROUP' => '/group/',
        'RESUME' => '/resume/',
        'ZONE' => '/zone/',
        'USER' => '/user/',
        'USER_BIO' => '/user_bio/',
        'COMPANY' => '/company/',
        'TEAMTASKS' => '/teamtasks/',
        'RECOGNITION' => '/recognition/',
        'SURVEY' => '/survey/',
        'GROUP_TABS' => '/group_tabs/',
        'POINTS' => '/points/',
        'DISCUSSION' => '/discussion/',
        'TSKP_TEMPLATE' => '/tskp_template/',
        'TEMPLATE' => '/emailtemplate/',
        'TEAM' => '/team/',
        'ICON' => '/icon/',
    );
    public const S3_SAFE_AREA = array(
        'COMPANY_RESOURCE' => '/commonassets/',
        'GROUP_RESOURCE' => '/resource/',
        'ATTACHMENTS' => '/attachments/',
    );

    public const S3_COMMENTS_AREA = array(
        'COMMENT' => '/comments/'
    );
    public const S3_UPLOADER_AREA = array(
        'user-data-sync' => '/incoming/user-data-sync/',
        'user-data-delete' => '/incoming/user-data-delete/'
    );

	private $hot_links;
	private $branches = array();
	private $groups = array();
	private $departments = array();
	private $zones  = array();
	private $regions = array();
	private $groupleadtypes = array();
	private $emailSettings = null;
	private $securitySettings = null;
    private $userLifecycleSettings = null;
    private $baseStringForIdEncoding = 'UNDEF-Z'; // Do not change it
    private $customization = array();

	protected function __construct(int $id, array $fields, array $zones = null) {
		parent::__construct($id,$id,$fields);
		//declaring it protected so that no one can create it outside this class.

        // !!! VERY IMPORTANT - NEVER CHANGE THE FOLLOWING LINE !!!
        $this->baseStringForIdEncoding = strtoupper('C'.base_convert($id,10,36). '-' .'Z');
        // !!! VERY IMPORTANT - NEVER CHANGE THE ABOVE LINE !!!

        $minified_company_customization = (json_decode($this->val('customization') ?? '', true)) ?: [];
        $this->customization = Arr::Unminify($minified_company_customization, self::DEFAULT_COMPANY_SETTINGS);

        $company_settings_with_company_block = $this->customization;
        unset($company_settings_with_company_block['company']);

        if ($zones != null) {
            // Apply the transformation
            foreach ($zones as $zone) {
                // ### Note : Same logic also in Zone::GetZone
                $minified_zone_customization = $zone['customization'] ? json_decode($zone['customization'], true) : array();
                $zone_settings_template = Zone::GetZoneSettingsTemplate($zone['app_type']);
                // Step 1 - first add any company customization to zone customiztion template
                // Note any customization set at the company level will override the corresponding key set at the zone level default
                $zone_settings_template_with_company_customization = Arr::Unminify($company_settings_with_company_block, $zone_settings_template);
                // Step 2- updated zone customization template with zone customization
                // Note any customization set at the zone level will override the corresponding key set at the company level or zone default
                $zone['customization'] = Arr::Unminify($minified_zone_customization, $zone_settings_template_with_company_customization);
                $this->zones[$zone['zoneid']] = $zone;
            }
        }
	}

    public static function __set_state (array $properties ) : Company {
        $id = $properties['id'];
        $fields = $properties['fields'];
        $retVal = new Company($id,$fields);
        $retVal->timestamp = $properties['timestamp'];
        $retVal->securitySettings = $properties['securitySettings'];
        $retVal->userLifecycleSettings = $properties['userLifecycleSettings'];
        $retVal->zones = $properties['zones'];
        // Do not change the following
        $retVal->baseStringForIdEncoding = $properties['baseStringForIdEncoding'];
        $retVal->customization = $properties['customization'];
        return $retVal;
    }

    public static function GetDefaultSettingsForAffinities(): array
    {
        return self::DEFAULT_SETTINGS_AFFINITY;
    }

    public static function GetDefaultSettingsForOfficeRaven(): array
    {
        return self::DEFAULT_SETTINGS_OFFICERAVEN;
    }

    public static function GetDefaultSettingsForTalentPeak(): array
    {
        return self::DEFAULT_SETTINGS_TALENTPEAK;
    }

    // same as affinity at the moment
    public static function GetDefaultSettingsForPeopleHero(): array
    {
        return self::DEFAULT_SETTINGS_PEOPLEHERO;
    }

	public static function GetCompany(int $id, bool $forceLoad = false) {
        $obj = null;
        $cachekey = sprintf(".Company_%d_%d", $id,$id);
        // First look in the cache and validate cache has not expired (300 seconds)
        if ($forceLoad || ($obj = self::CacheGet($cachekey)) === null || (time() - $obj->timestamp) > 300) {
            if ($forceLoad) {
                sleep (1); // Add a bit of time for replica to catch up.
            }
            // Recreate the opcache
            $r1 = self::DBROGet("SELECT *,TIME_TO_SEC(TIMEDIFF(NOW(), UTC_TIMESTAMP)) as `mysql_utc_diff_secs` FROM companies WHERE companyid='{$id}' AND isactive='1' AND status='1'");
            $r2 = self::DBROGet("SELECT * FROM `company_zones` WHERE `companyid` = '{$id}' AND isactive=1");

            foreach ($r2 as &$item) {
                $item['footer_links'] = self::DBROGet("SELECT link_title, link_section, link_type, link FROM `company_footer_links` WHERE companyid = {$id} AND zoneid = {$item['zoneid']} AND isactive=1");
            }
            unset($item);

            if (!empty($r1))
            {
                // Create a map of valid email domains and if they are routable or not.
                $emailDomains = array($r1[0]['subdomain'].'.teleskope.io' => 0);
                $emailDomainRows = self::DBROGet("SELECT domain, routable FROM `company_email_domains` WHERE `companyid` = {$id}");
                foreach ($emailDomainRows as $emailDomainRow) {
                    $emailDomains[$emailDomainRow['domain']] = $emailDomainRow['routable'];
                }
                $r1[0]['email_domains'] = $emailDomains;

                $obj = new Company($id,$r1[0],$r2);
                $obj->getCompanySecurity();
                $obj->getUserLifecycleSettings();
            }

             self::CacheSet($cachekey, $obj);
             $obj = self::CacheGet($cachekey);
        }

		return $obj;
	}

	public static function GetCompanyByEmail(string $email) {

		//Get the domain from the provided email address
		$domain = @explode("@",$email)[1];
		if (empty($domain))
			return null;
		else
		    $domain = strtolower($domain);

		//Fetch matching companies
        $r1 = self::DBGetPS("SELECT companyid FROM company_email_domains WHERE domain=?",'s',$domain);

		//If a domain match happens, return the company object
		if (!empty($r1)) {
                return self::GetCompany($r1[0]['companyid']);
		}
		return null;
	}

    public static function GetCompanyBySubdomain(string $subdomain) {
        if (Config::Get('GR_TEST_MODE_ENABLED') == 1) {
            $subdomain = str_replace(Config::Get('GR_DOMAIN_PREFIX'), '', $subdomain);
        }

        if (($cid = CompanyDictionary::GetCompanyIdBySubdomain($subdomain))) {
            return self::GetCompany($cid);
        }
        return null;
    }

    public static function GetCompanyByUrl(string $url) {
        $urlhost = parse_url($url, PHP_URL_HOST);
        $subdomain = explode('.', $urlhost, 2)[0];
        return self::GetCompanyBySubdomain($subdomain);
    }

    public function __toString() {
		return "Company ";//.parent::__toString(). ", zones=".json_encode($this->zones);
	}

    /**
     * Converts given mysql datetime to unix timestamp by using mysql to utc deltas
     * @param string $dt
     * @return int timestamp in seconds
     */
	public function getMysqlDatetimeAsTimestamp(string $dt) : int {
	    // First get strtotime as UTC, then we will subtract the delta
	    if (!empty($dt) && ($dts = strtotime($dt. ' UTC'))) {
	        return $dts - (int)($this->val('mysql_utc_diff_secs'));
        }
        return 0;
    }

    /**
     * Gets all `groups`in the company.
     * @return array of Groups
     */
    public  function getAllGroups () {
        global $_ZONE;
        if (empty($this->groups))
            $this->groups = Group::GetAllGroupsByCompanyid($this->id, $_ZONE->id());
        return $this->groups;
    }

    /**
     * Gets all Active `groups`in the company.
     * @return array of Groups
     */
    public  function getAllActiveGroups () {
        global $_ZONE;
        $retVal = array();
        if (empty($this->groups))
            $this->groups = Group::GetAllGroupsByCompanyid($this->id, $_ZONE->id(), true);

        foreach ($this->groups as $g1) {
            if ((int)$g1->val('isactive') === self::STATUS_ACTIVE)
                $retVal[] = $g1;
        }
        return $retVal;
    }

    /**
     * Return the name of the group in the company.
     * If groupid === 0, then "All" is returned
     * @param int $groupid
     * @return string
     */
    public function getGroupName (int $groupid) {
        global $_ZONE;
        global $_COMPANY;
        if ($groupid === 0)
            return $_COMPANY->getAppCustomization()['group']['groupname0'] ?? 'Global';

        if (empty($this->groups))
            $this->groups = Group::GetAllGroupsByCompanyid($this->id, $_ZONE->id());

        foreach ($this->groups as $grp) {
            if ($grp->id() === $groupid)
                return $grp->val('groupname');
        }
        return "Unknown";
    }

    /**
     * Return the name of the group in the company.
     * If groupid === 0, then zone customization for group0_color is returned.
     * @param int $groupid
     * @return string
     */
    public function getGroupColor (int $groupid) {
        global $_ZONE;
        global $_COMPANY;

        if ($groupid === 0)
            return $_COMPANY->getAppCustomization()['group']['group0_color'] ?? '#a0a0a0';

        $group = Group::GetGroup($groupid);
        return $group ? $group->val('overlaycolor') : '';
    }

    /**
     * Returns all departments in the company, regardless of the isactive status
     * @return array
     */
    public function getAllDepartments () {
        if (empty($this->departments)) {
			$this->departments = self::DBGet("SELECT `departmentid`,`department`,`isactive` FROM `departments` WHERE `companyid` = '{$this->id}'");
		}
		return $this->departments;
	}

	public function getDepartmentName (int $departmentid) {
		if (empty($this->departments))
		    $this->getAllDepartments();

		foreach ($this->departments as $dept) {
			if ((int)$dept['departmentid'] === $departmentid)
				return $dept['department'];
		}
		return "";
	}

    /**
     * Returns department row for matched department name
     * If department is not found a new department is created
     * @param string $department to search for or create
     * @return int department id or 0
     */
    public function getOrCreateDepartment (string $department): int
    {
        if (empty($department))
            return 0;

        $row = self::DBGetPS("SELECT departmentid FROM departments WHERE companyid=? AND department=?",'is',$this->id,$department);
        if (count($row) > 0) {
            return (int)$row[0]['departmentid'];
        } else {
            global $_USER;
            /* @var User $_USER */
            $uid = 0;
            if ($_USER && ($_USER->cid() === $this->cid())) {
                $uid = $_USER->id();
            }
            $ins = self::DBInsertPS("INSERT INTO departments (companyid, department, addedby, date, isactive) VALUES (?,?,?,now(),1)",'isi',$this->id,$department,$uid);

            return is_int($ins) ? (int)$ins : -1;
        }
    }

    /**
     * A wrapper around getOrCreateDepartment method to make it memory optimized
     * @param string $department
     * @return int
     */
    public function getOrCreateDepartment__memoized (string $department): int
    {
        $memoize_key = __METHOD__ . ':' . $this->id() . ':' .serialize(func_get_args());
        if (!isset(self::$memoize_cache[$memoize_key]))
            self::$memoize_cache[$memoize_key] = $this->getOrCreateDepartment($department);

        return intval(self::$memoize_cache[$memoize_key] ?? 0);
    }

    public function createOrUpdateDepartment (int $departmentid, string $department): int
    {
        global $_COMPANY,$_ZONE,$_USER;
        if($departmentid){
            return self::DBUpdatePS("UPDATE `departments` SET `department`=? WHERE companyid=? AND `departmentid`=?",'xii',$department,$this->id(),$departmentid);
        } else {
            $exists = self::DBGetPS("SELECT departmentid FROM departments WHERE companyid=? AND department=?", 'ix',$_COMPANY->id(), $department);
            if (empty($exists))
                return self::DBInsertPS("INSERT INTO departments (companyid, department, addedby, `date`, isactive) VALUES (?,?,?,now(),1)",'ixi',$this->id,$department,$_USER->id());
            else
                return $exists[0]['departmentid'];
        }
    }

    /**
     * Returns a list of zones that can be Home Zones. Use this method when presenting a list of zones that user
     * can select.
     * @param string $app_type
     * @return array
     */
    public function getHomeZones(string $app_type)
    {
        return array_filter($this->zones, function ($value) use ($app_type) { return (($value['app_type'] === $app_type) && ($value['home_zone'] == 1)); });
    }

    /**
     * Returns all the zones regardless of the apps. Note only active zones are fetched.
     * @param string $app_type optional, if provided only the zones for that application will be provided
     * @return array
     */
    public function getZones(string $app_type='')
    {
        if ($app_type) {
            return array_filter($this->zones, function ($value) use ($app_type) { return ($value['app_type'] === $app_type); });
        }

        return $this->zones;
    }

    /**
     * returns an array of $zoneid => attributes_array pairs.
     * @param string $pathInDotNotation
     * @return array
     */
    public function  getZoneAttributesByPath (string $pathInDotNotation): array
    {
        $results = [];
        $keys = explode('.', $pathInDotNotation);

        foreach ($this->zones as $zone_entry) {
            $customization_value = $zone_entry['customization'];
            foreach ($keys as $key) {
                if (is_array($customization_value) && array_key_exists($key, $customization_value)) {
                    $customization_value = $customization_value[$key];
                } else {
                    $customization_value = null; // Path does not exist
                    break;
                }
            }
            $results[$zone_entry['zoneid']] = $customization_value;
        }

        return $results;
    }

    /**
     * This method returns a Zone class (subclass of Teleskope) for convenience
     * @param int $zoneid
     * @return Zone|null
     */
    public function getZone (int $zoneid) {
        if ($zoneid) { // One go through the search efforts if $zoneid is provided
            foreach ($this->getZones() as $zone) {
                if ((int)$zone['zoneid'] === $zoneid) {
                    return new Zone($zone['zoneid'], $this->id, $zone);
                }
            }
        }

        return null;
    }

    /**
     * This method can be used to get an empty zone for use in /user/module as the zone is not known at that time
     * @param string $app_type either 'affinities' or 'officeraven'
     * @return Zone|null
     */
    public function getEmptyZone(string $app_type) {
        $app_type = in_array($app_type, array('affinities','officeraven','talentpeak')) ? $app_type : 'teleskope';
        return new Zone(0,$this->id,array('email_settings'=>1, 'app_type'=>$app_type));
//        return (new class(0, $this->id, array('email_settings'=>1, 'app_type'=>$app_type)) extends Teleskope {
//            public function __construct(int $id, int $cid, array $fields)
//            {
//                parent::__construct($id, $cid, $fields);
//            }
//        });
    }

    /**
     * @deprecated - remove it
     */
    public  function GetPostComment(int $postcommentid)
    {
    }

    public function createZone (string $zonename, string $app_type)
    {
        global $_COMPANY;
        $zoneid = self::DBInsert("INSERT INTO `company_zones` (`companyid`, `zonename`, `app_type`) VALUES ({$_COMPANY->id()}, $zonename, $app_type)");
        // 2.1 Add a default category for the group category table
        $default_categoryid = self::DBInsert ("INSERT into `group_categories` (companyid,zoneid,category_label,category_name,is_default_category,createdon,modifiedon,isactive) VALUES ({$_COMPANY->id()},{$zoneid},'','ERG','1',NOW(),NOW(),'1')");
    }

    public function updateZone (int $zoneid, string $zonename)
    {
        global $_COMPANY;
        return self::DBInsert("UPDATE  `company_zones` SET `zonename` = '{$zonename}',`modifiedon`=now() WHERE `zoneid`={$zoneid} AND `companyid`={$_COMPANY->id()}");
    }

    /**
     * Use this method only when managing zones. For all other purposes use getZones as that is cached.
     * @return array
     */
    public function fetchAllZonesFromDB()
    {
        return self::DBGet("SELECT * FROM `company_zones` WHERE `companyid` = '{$this->id}'");
    }

    public function updateZoneStatus (int $zoneid, int $status)
    {
        global $_COMPANY;
        return self::DBInsert("UPDATE  `company_zones` SET `isactive` = '{$status}', `modifiedon`=now() WHERE `zoneid`={$zoneid} AND `companyid`={$_COMPANY->id()}");
    }

    /**
     * @return array of Regions filtered for provided zones
     */
    public function getRegionsByZones(array $zoneids): array
    {
        // Construct a $zoneRegionidsArray for list of regionids in the provided zoneids array
        $zoneRegionidsArray = array();
        $zoneids = Sanitizer::SanitizeIntegerArray($zoneids);
        foreach($zoneids as $zid) {
            $z = $this->getZone($zid);
            if ($z){
                $zoneRegionidsArray = array_merge($zoneRegionidsArray,explode(',',($z->val('regionids') ?? 0)));
            }
        }
        $zoneRegionidsArray = array_unique($zoneRegionidsArray);

        // return the zones that are valid by comparing to all the availalbe zones in the company.
        $availableRegions = $this->getAllRegions();
        return array_filter($availableRegions,
            function ($z) use ($zoneRegionidsArray) {
                return in_array($z['regionid'],$zoneRegionidsArray);
            });
    }

    /**
     * This method returns the customziation for the current zone. Note: It depends upon $_ZONE global.
     */
    public function getAppCustomization() {
        global $_ZONE;
        return $this->zones[$_ZONE->val('zoneid')]['customization']['app'];
    }

    /**
     * This method returns the customziation for the specified zone
     */
    public function getAppCustomizationForZone(int $zoneid) {
        return $this->zones[$zoneid]['customization']['app'];
    }

    /**
     * This method returns the app customization at the company level
     * It is same for all zones
     */
    public function getCompanyCustomization() {
        return $this->customization['company'];
    }

    /**
     * Returns an array of user life cylce settings
     * see saveUserLifecycleSettings
     * updated. See USER_LIFECYCLE_SETTING_DEFAULTS for template
     * @return array
     */
    public function getUserLifecycleSettings(): array
    {
        if ($this->userLifecycleSettings === null) {
            $this->userLifecycleSettings = Arr::Unminify(
                    $this->val_json2Array('user_lifecycle_settings') ?? [],
                    self::USER_LIFECYCLE_SETTING_DEFAULTS
            );
        }
        return $this->userLifecycleSettings;
    }

    /**
     * @TODO: We need to implement the user interface in Super Admin > Company Edit to allow user settings to be
     * updated. See USER_LIFECYCLE_SETTING_DEFAULTS for template
     * @return array
     */
    public function saveUserLifecycleSettings(array $settings)
    {
        $settings_minified = Arr::Minify($settings, self::USER_LIFECYCLE_SETTING_DEFAULTS);
        $json_settings = empty($settings_minified) ? json_encode($settings_minified) : null;
        self::DBUpdate("UPDATE companies SET user_lifecycle_settings = {$json_settings} WHERE companyid={$this->id()}");
    }

    public function getStyleCustomization() {
        global $_ZONE;
        return $this->zones[$_ZONE->val('zoneid')]['customization']['style'];
    }
    /**
     * Returns all regions in the company, regardless of the isactive status
     * @param bool $ignoreStatus, if true then all entries will be returned regardless of the isactive status
     * @return array
     */
    public function getAllRegions (bool $ignoreStatus = false) {
        $status_filter = '';
        if (!$ignoreStatus) {
            $status_filter = "AND `isactive`='1'";
        }
        if (empty($this->regions)) {
            $r = self::DBGet("SELECT `regionid`,`region`,`isactive` FROM `regions` WHERE `companyid` = '{$this->id}' {$status_filter}");
            usort($r, function($a, $b) {
                return $a['region'] <=> $b['region'];
            });
            $this->regions = $r;
        }
        return $this->regions;
    }

    public function getRegionName (string $regionid) {
        if (empty($this->regions))
            $this->getAllRegions();

        foreach ($this->regions as $region) {
            if ($region['regionid'] === $regionid)
                return $region['region'];
        }
        return "Unknown";
    }

    public function getRegionByName (string $region_name)
    {
        if (empty($this->regions))
            $this->getAllRegions();

        foreach ($this->regions as $region) {
            if ($region['region'] == $region_name){
                return $region['regionid'];
            }
        }
        return 0;
    }

    public function getRegionsByCSV (string $regionids) {
        if (empty($this->regions))
            $this->getAllRegions();

        $regions = array();
        $regionids = explode(',',$regionids);
        foreach ($this->regions as $region) {
            if (in_array($region['regionid'],$regionids)){
                $regions[] =  $region;
            }
        }
        return $regions;
    }

    public function getAllBranches () {
        if (empty($this->branches)) {
            $this->branches = self::DBROGet("SELECT `branchid`, `employees`, `branchname`, `branchtype`, `street`, `city`,
                                                `state`, `zipcode`, `country`, `regionid`, `isactive`
                                            FROM `companybranches` WHERE `companyid`='{$this->id}'");
        }
        return $this->branches;
    }

    public function getBranchName (int $branchid) {
        $branch = $this->getBranch($branchid);
        if ($branch)
            return $branch->val('branchname');
        else
            return '';
    }

    public function getBranch(int $branchid)
    {
        $branches = self::DBGet("SELECT * FROM `companybranches` WHERE `companyid`='{$this->id}' AND `branchid`='{$branchid}'");
        if (count($branches)>0){
            return (new class($branches[0]['branchid'],$this->id,$branches[0]) extends Teleskope
            {
                public function __construct(int $id, int $cid, array $fields)
                {
                    parent::__construct($id, $cid, $fields);
                    //Throw away class
                }
            });
        } else {
            return null;
        }
    }

    /**
     * Finds a branch that matches branchname, city (if provided), state (if provided), country (if provided)
     * @param string $branchName
     * @param string $city
     * @param string $state
     * @param string $country
     * @return Teleskope|__anonymous@35435|null
     */
    public function getBranchByName2 (string $branchName, string $city, string $state, string $country, int $regionId=0) {
        $filter = '';
        if (empty($city)) {
            $filter .= " AND (1 != ?)"; // Basically ignore the city parameter in prepared statement
        } else {
            $filter .= " AND (city='' OR city=?)";
        }

        if (empty($state)) {
            $filter .= " AND (1 != ?)"; // Basically ignore the state parameter in prepared statement
        } else {
            $filter .= " AND (state='' OR state=?)";
        }

        if (empty($country)) {
            $filter .= " AND (1 != ?)"; // Basically ignore the country parameter in prepared statement
        } else {
            $filter .= " AND (country='' OR country=?)";
        }

        if (empty($regionId)) {
            $filter .= " AND (1 != ?)"; // Basically ignore the country parameter in prepared statement
        } else {
            $filter .= " AND (regionid=0 OR regionid=?)";
        }

        $branches =  self::DBGetPS("SELECT * FROM companybranches WHERE companyid=? AND branchname=? {$filter}",'ixxxxi',$this->id,$branchName, $city, $state, $country,$regionId);

        if (!empty($branches)) {
            $branchid = 0;

            if (count($branches) == 1) { // Exact match
                $branchid = (int)$branches[0]['branchid'];
                $branch_arr = $branches[0];
            } elseif (count($branches) > 1) {
                // Sort branch that has the most matches to the top
                usort($branches,function($a,$b) {
                            return
                                intval(empty($a['city']) + empty($a['state']) + empty($a['country']) + empty($a['regionid']))
                                -
                                intval(empty($b['city']) + empty($b['state']) + empty($b['country']) + empty($b['regionid']));
                        });
                // Assign the most matched branch
                $branchid = (int)$branches[0]['branchid'];
                $branch_arr = $branches[0];
            } else {

                $branch_arr = array('branchid'=>0, 'companyid' => $this->id, 'branchname'=>$branchName, 'city'=>$city, 'state'=>$state,'country'=>$country);
            }

            return (new class($branchid, $this->id, $branch_arr) extends Teleskope {
                public function __construct(int $id, int $cid, array $fields)
                {
                    parent::__construct($id, $cid, $fields);
                    //Throw away class
                }
            });
        } else {
            return null;
        }
    }
    /**
     * @param string $branchName
     * @param string $street
     * @param string $city
     * @param string $state
     * @param string $zipcode
     * @param string $country
     * @param string $branchtype
     * @param int $regionid
     * @return int
     */
    public function createBranch(string $branchName, string $street, string $city, string $state, string $zipcode, string $country, string $branchtype, int $regionid): int
    {
        global $_USER; /* @var User $_USER */
        $uid = 0;
        if ($_USER && ($_USER->cid() === $this->cid())) {
            $uid = $_USER->id();
        }
        return self::DBInsertPS("INSERT INTO companybranches (companyid, userid, branchname, street, city, state, zipcode, country, branchtype, regionid, createdon, modified, isactive) values (?,?,?,?,?,?,?,?,?,?,now(),now(),1)",
            'iixxxxxxxi',
            $this->id,$uid, $branchName, $street, $city, $state, $zipcode, $country, $branchtype, $regionid);
    }

    /**
     * @param int $branchid
     * @param string $branchName
     * @param string $street
     * @param string $city
     * @param string $state
     * @param string $zipcode
     * @param string $country
     * @param string $branchtype
     * @param int $regionid
     * @return int
     */
    public function updateBranch (int $branchid, string $branchName, string $street, string $city, string $state, string $zipcode, string $country, string $branchtype, int $regionid): int {
        return self::DBMutatePS("UPDATE companybranches SET branchname=?, street=?, city=?, state=?, zipcode=?, country=?, branchtype=?,regionid=? WHERE branchid=? AND companyid=?", 'xxxxxxxiii', $branchName, $street,$city,$state,$zipcode,$country,$branchtype,$regionid,$branchid,$this->id());
    }

    /**
     * Returns branch that matches the branchname
     * If more than one branches match, then branchid=0 will be returned
     * All the non empty values are compared and if there is a difference the value is updated
     * If is branch is not found a new branch is created and all the non empty values are set
     * @param string $branchName to search for or create
     * @param string $city optional if set it is used when creating a new branch
     * @param string $state optional if set it is used when creating a new branch
     * @param string $country optional if set it is used when creating a new branch
     * @param string $branchtype
     * @param int $regionid
     * @param string $street
     * @param string $zipcode
     * @return array db record of branch or empty branch with at minimum branchid and regionid filled out to 0
     */
    public function getOrCreateOrUpdateBranch(string $branchName, string $city='', string $state='', string $country='', string $branchtype ='', int $regionid = 0, string $street='', string $zipcode=''): array
    {
        $retVal = array('branchid' => '0', 'regionid' => $regionid);
        if (empty($branchName))
            return $retVal;

        $branch = $this->getBranchByName2($branchName, $city, $state, $country, $regionid);
        if ($branch) {
            $updated = false;

            if (!$branch->id()) {
                return $retVal; // We will proceed only if it is a valid branch (valid branch is with id > 0)
            }
            // We will not use update branch here as we will be updating only if the values are provided and not matching
            if (!empty($city) && ($branch->val('city') != $city)) {
                self::DBMutatePS("UPDATE companybranches SET city=?, modified=now() WHERE companyid=? AND branchid=?", 'xii',$city,$this->id,$branch->id());
                $updated = true;
            }
            if (!empty($state) && ($branch->val('state') != $state)) {
                self::DBMutatePS("UPDATE companybranches SET state=?, modified=now() WHERE companyid=? AND branchid=?", 'xii',$state,$this->id,$branch->id());
                $updated = true;
            }
            if (!empty($country) && ($branch->val('country') != $country)) {
                self::DBMutatePS("UPDATE companybranches SET country=?, modified=now() WHERE companyid=? AND branchid=?", 'xii',$country,$this->id,$branch->id());
                $updated = true;
            }
            if (!empty($branchtype) && ($branch->val('branchtype') != $branchtype)) {
                self::DBMutatePS("UPDATE companybranches SET branchtype=?, modified=now() WHERE companyid=? AND branchid=?", 'xii',$branchtype,$this->id,$branch->id());
                $updated = true;
            }
            if (!empty($regionid) && ($branch->val('regionid') != $regionid)) {
                self::DBMutatePS("UPDATE companybranches SET regionid=?, modified=now() WHERE companyid=? AND branchid=?", 'iii',$regionid,$this->id,$branch->id());
                $updated = true;
            }
            if (!empty($street) && ($branch->val('street') != $street)) {
                self::DBMutatePS("UPDATE companybranches SET street=?, modified=now() WHERE companyid=? AND branchid=?", 'xii',$street,$this->id,$branch->id());
                $updated = true;
            }
            if (!empty($zipcode) && ($branch->val('zipcode') != $zipcode)) {
                self::DBMutatePS("UPDATE companybranches SET zipcode=?, modified=now() WHERE companyid=? AND branchid=?", 'xii',$zipcode,$this->id,$branch->id());
                $updated = true;
            }
            if ($updated) {
                $branch_row = self::DBGet("SELECT branchid,regionid FROM companybranches WHERE companyid={$this->id} AND branchid={$branch->id()}");
                return $branch_row[0];
            }
            $retVal['branchid'] = $branch->id();
            $retVal['regionid'] = $branch->val('regionid');
        } else {
            $retVal['branchid'] = $this->createBranch($branchName, $street, $city, $state, $zipcode, $country, $branchtype, $regionid);
            $retVal['regionid'] = $regionid;
        }

        return $retVal;
    }


    /**
     * A wrapper around getOrCreateOrUpdateBranch method to make it memory optimized
     * @param string $branchName
     * @param string $city
     * @param string $state
     * @param string $country
     * @param string $branchtype
     * @param int $regionid
     * @param string $street
     * @param string $zipcode
     * @return array
     */
    public function getOrCreateOrUpdateBranch__memoized (string $branchName, string $city='', string $state='', string $country='', string $branchtype ='', int $regionid = 0, string $street='', string $zipcode=''): array
    {
        $memoize_key = __METHOD__ . ':'  . $this->id() . ':' . serialize(func_get_args());
        if (!isset(self::$memoize_cache[$memoize_key]))
            self::$memoize_cache[$memoize_key] = $this->getOrCreateOrUpdateBranch($branchName, $city, $state, $country, $branchtype, $regionid, $street, $zipcode);

        return self::$memoize_cache[$memoize_key] ?? [];
    }



    //
    // Company groupleadtype functions
    //
    /**
     * Returns all the GroupLeadtypes in the company.
     * Does a lazy loading and caches.
     * @param bool $ignoreStatus - if true, only active rows are returned
     * @return array - db rows containing Grouplead types, or empty array
     */
    public function getAllGroupLeadtypes (bool $ignoreStatus=false)
    {
        global $_ZONE;
        if (empty($this->groupleadtypes)) {
            $status_filter = '';
            if (!$ignoreStatus) {
                $status_filter = "AND `isactive`='1'";
            }
            $t = self::DBGet("SELECT * FROM `grouplead_type` WHERE `companyid`='{$this->id}' AND `zoneid`='{$_ZONE->id()}' {$status_filter}");
            usort($t, function($a, $b) {
                return $a['type'] <=> $b['type'];
            });
            $this->groupleadtypes = $t;
        }
        return $this->groupleadtypes;
    }
    /**
     * Returns GroupLeadtypes who are executives
     * @return array of matches or empty array
     */
    public function getGroupLeadtypesOfExecutiveType () {
        return array_filter($this->getAllGroupleadTypes(), function ($value) { return ($value['sys_leadtype'] == 1); });
    }
    /**
     * Returns GroupLeadtypes who are Group Leads
     * @return array of matches or empty array
     */
    public function getGroupLeadtypesOfGroupType () {
        return array_filter($this->getAllGroupleadTypes(), function ($value) { return ($value['sys_leadtype'] == 2); });
    }
    /**
     * Returns GroupLeadtypes who are Regional Leads
     * @return array of matches or empty array
     */
    public function getGroupLeadtypesOfRegionalType () {
        return array_filter($this->getAllGroupleadTypes(), function ($value) { return ($value['sys_leadtype'] == 3); });
    }
    /**
     * Returns GroupLeadtypes who are Chapter Leads
     * @return array of matches or empty array
     */
    public function getGroupLeadtypesOfChapterType () {
        return array_filter($this->getAllGroupleadTypes(), function ($value) { return ($value['sys_leadtype'] == 4); });
    }
    /**
     * Returns GroupLeadtypes who are Channel Leads
     * @return array of matches or empty array
     */
    public function getGroupLeadtypesOfChannelType () {
        return array_filter($this->getAllGroupleadTypes(), function ($value) { return ($value['sys_leadtype'] == 5); });
    }
    /**
     * Returns GroupLeadtypes who are allowed to Create Content
     * @return array of matches or empty array
     */
    public function getGroupLeadtypesWhoCanCreateContent () {
        return array_filter($this->getAllGroupleadTypes(), function ($value) { return ($value['allow_create_content'] == 1); });
    }
    /**
     * Returns GroupLeadtypes who are allowed to Publish Content
     * @return array of matches or empty array
     */
    public function getGroupLeadtypesWhoCanPublishContent () {
        return array_filter($this->getAllGroupleadTypes(), function ($value) { return ($value['allow_publish_content'] == 1); });
    }
    /**
     * Returns GroupLeadtypes who are allowed to Manage
     * @return array of matches or empty array
     */
    public function getGroupLeadtypesWhoCanManage () {
        return array_filter(self::GetAllGroupleadTypes(), function ($value) { return ($value['allow_manage'] == 1); });
    }
    //
    //

    /**
     * @param string $scope affinities|officeraven|teleskope
     * Scope affinities is default and used in cron jobs
     * @return array|null
     */
    public function getO365Settings(string $scope)
    {
        // First initialize o365 settings if not set already
        $record = self::DBROGet("SELECT * FROM `company_login_settings` WHERE `companyid` = {$this->id} AND loginmethod='microsoft' AND scope='{$scope}' AND isactive=1");
        if (!empty($record)) {
            $record[0]['attributes'] = Arr::Json2Array($record[0]['attributes']); // No need to add backslashes for JSON coming from DB
            $record[0]['customization'] = Arr::Json2Array($record[0]['customization']); // No need to add backslashes for JSON coming from DB
            $record[0]['sync_days'] =  $record[0]['attributes']['sync_days'];
            $record[0]['tenantguid'] = $record[0]['attributes']['tenantguid'];;
            return $record[0];
        }
        return null;
    }

    /**
     * Generates a random email address such as usr_jkl4dqp9@{subdomain}.teleskope.io
     * @param string $salt e.g. external id
     * @return string
     */
    function generateTeleskopeEmailAddress(string $salt): string
    {
        return 'u_'.substr(md5($salt . time()), 2, 8) . '@' . $this->val('subdomain') . '.teleskope.io';
    }

    /**
     * Validates if the EmailDomain matches with one of the configured domains for the company
     * @param string|null $domain
     * @return bool
     */
    public function isValidEmailDomain(?string $domain): bool {
        if (!$domain) return false;
        $domain = strtolower($domain);
        return isset($this->fields['email_domains'][$domain]);
	}

    public function isValidAndRoutableEmailDomain(?string $domain): bool {
        if (!$domain) return false;
        $domain = strtolower($domain);
        return boolval($this->fields['email_domains'][$domain] ?? false);
    }

    /**
     * Checks is provided email is a valid email as per company domains set
     * @param string|null $email
     * @return bool true if email domain matches company domain
     */
    public function isValidEmail (?string $email):bool {
        if (!$email) return false;
        $atPos = strpos($email,'@');
        $domain = $atPos ? substr($email,$atPos+1) : '';
        return ($domain) ? $this->isValidEmailDomain ($domain) : false;

    }

    public function isValidAndRoutableEmail (?string $email):bool {
        if (!$email) return false;
        $atPos = strpos($email,'@');
        $domain = $atPos ? substr($email,$atPos+1) : '';
        return ($domain) ? $this->isValidAndRoutableEmailDomain($domain) : false;
    }

    /**
     * Checks if the provided email address is the one that was generated by Teleskope.
     * @param string|null $email
     * @return bool
     */
    public function isTeleskopeEmailAddress(?string $email) : bool
    {
        return str_ends_with(($email ?? ''), ('@' . $this->val('subdomain') . '.teleskope.io'));
    }

    public function isValidLanguage(string $language) {
        $language_list = $this->getAppCustomization()['locales']['languages_allowed'];
        if ($language == 'en') {
            return $language_list['en']['enabled'] ?? true;
        } elseif ($this->getAppCustomization()['locales']['enabled']) {
            if (in_array($language,array_keys($language_list))) {
                return $language_list[$language]['enabled'] ?? false;
            }
        }
        return false;
    }

    public function getValidLanguages()
    {
        $langLabel = array(
            'en' => 'english (default)',
            'de_DE' => 'deutsch (German)',
            'es_ES' => 'español (Spanish)',
            'es_MX' => 'español (Mexico)',
            'fr_CA' => 'français (French Canada)',
            'fr_FR' => 'français (French)',
            'hi_IN' => 'हिन्दी (Hindi)',
            'ja_JP' => '日本語 (Japanese)',
            'ko_KR' => '한국어 (Korean)',
            'ms_MY' => 'melayu (Malay)',
            'pt_BR' => 'português brazil (Portuguese Brazil)',
            'pt_PT' => 'português (Portuguese)',
            'th_TH' => 'ไทย (Thai)',
            'zh_CN' => '简体中文 (Chinese)',
            'it_IT' => 'Italian (Italy)',
            'id_ID' => 'Bahasa Indonesia (Indonesian)',
            'fil_PH' => 'Filipino (Philippines)',
            'vi_VN' => 'tiếng Việt (Vietnamese)',

        );
        $allowedLanguages = array('en'=>"English");
        if ($this->getAppCustomization()['locales']['enabled']){
            foreach ($this->getAppCustomization()['locales']['languages_allowed'] as $k => $v) {
                if (isset($v['enabled']) && $v['enabled']) {
                    $allowedLanguages[$k] = $langLabel[$k];
                }
            }
        }
        return $allowedLanguages;
    }

    public function getCalendarLanguage(string $userLang = '') : string
    {
        if (empty($userLang)) {
            $envLang = explode('.', Env::Get('LANG'));
            $userLang = !empty($envLang) ? $envLang[0] : 'en';
        }
        // Maps langauges that Affinities understands to what calendar understands
        $langMap = array(
            'de_DE' => 'de',
            'es_ES' => 'es',
            'es_MX' => 'es',
            'fr_CA' => 'fr-ca',
            'fr_FR' => 'fr',
            'hi_IN' => 'hi',
            'ja_JP' => 'ja',
            'ko_KR' => 'ko',
            'ms_MY' => 'ms-my',
            'pt_BR' => 'pt-br',
            'pt_PT' => 'pt',
            'th_TH' => 'th',
            'zh_CN' => 'zh-cn',
            'it_IT' => 'it',
            'id_ID' => 'id',
            'fil_PH' => 'fil',
            'vi_VN' => 'vi'
        );

        $retVal = "en";
        if (array_key_exists($userLang,$langMap)){
            $retVal = $langMap[$userLang];
        }

        return $retVal;
    }

    public function getSurveyLanguage(string $userLang = '') : string
    {
        if (empty($userLang)) {
            $envLang = explode('.', Env::Get('LANG'));
            $userLang = !empty($envLang) ? $envLang[0] : 'en';
        }
        // Maps langauges that Affinities understands to what survey understands
        $langMap = array(
            'de_DE' => 'de',
            'es_ES' => 'es',
            'es_MX' => 'es',
            'fr_CA' => 'fr',
            'fr_FR' => 'fr',
            'hi_IN' => 'hi',
            'ja_JP' => 'ja',
            'ko_KR' => 'ko',
            'ms_MY' => 'ms',
            'pt_BR' => 'pt-br',
            'pt_PT' => 'pt',
            'th_TH' => 'th',
            'zh_CN' => 'zh-cn',
            'it_IT' => 'it',
            'id_ID' => 'id',
            'vi_VN' => 'vi',
            'fil_PH' => 'fil'
        );

        $retVal = "en";
        if (array_key_exists($userLang,$langMap)){
            $retVal = $langMap[$userLang];
        }

        return $retVal;
    }

    public function getImperaviLanguage(string $userLang = '') : string
    {
        if (empty($userLang)) {
            $envLang = explode('.', Env::Get('LANG'));
            $userLang = !empty($envLang) ? $envLang[0] : 'en';
        }

        // Maps langauges that Affinities understands to what Redactor/Revolvapp understands
        $langMap = array(
        //           'de_DE' => 'de',
        //           'es_ES' => 'es',
        //           'es_MX' => 'es',
                    'fr_CA' => 'fr',
                    'fr_FR' => 'fr',
        //            'hi_IN' => 'hi',
                    'ja_JP' => 'ja',
                    'it_IT' => 'it',
                    'id_ID' => 'id',
                    'fil_PH' => 'fil',
                    'vi_VN' => 'vi'
        //            'ko_KR' => 'ko',
        //            'ms_MY' => 'ms',
        //            'pt_BR' => 'pt_br',
        //            'pt_PT' => 'pt_br',
        //            'th_TH' => 'th',
        //            'zh_CN' => 'zh_cn',
                    
       
        );

        $retVal = "en";
        if (array_key_exists($userLang,$langMap)){
            $retVal = $langMap[$userLang];
        }

        return $retVal;
    }

    public function getDatatableLanguage(string $userLang='') : string
    {
        if (empty($userLang)) {
            $envLang = explode('.', Env::Get('LANG'));
            $userLang = !empty($envLang) ? $envLang[0] : 'en';
        }

        // Maps langauges that Affinities understands to what Datatable understands
        $langMap = array(
            'de_DE' => 'de_de',
            'es_ES' => 'es_es',
            'es_MX' => 'es-mx',
            'fr_CA' => 'fr_fr',
            'fr_FR' => 'fr_fr',
            'hi_IN' => 'hi',
            'ja_JP' => 'ja',
            'ko_KR' => 'ko',
            'ms_MY' => 'ms',
            'pt_BR' => 'pt_br',
            'pt_PT' => 'pt_pt',
            'th_TH' => 'th',
            'zh_CN' => 'zh',
            'it_IT' => 'it_it',
            'id_ID' => 'id',
            'fil_PH' => 'fil',
            'vi_VN' => 'vi'
        );

        $retVal = "en-gb";
        if (array_key_exists($userLang,$langMap)){
            $retVal = $langMap[$userLang];
        }

        return $retVal;
    }
	public function deleteFile(string $filename) {
		//Instantiate the client.
		$s3 = Aws\S3\S3Client::factory([
            'version' => 'latest',
            'region' => S3_REGION

		]);

		$retVal = false;
		$s3name = $this->val('s3_folder');
		if (empty($s3name))
		    return $retVal;

		$dest_name = strstr($filename,$s3name); //Extract filename starting with the s3_folder name, including it

		if (empty($dest_name))
		    return $retVal;

		try {
			$s3->deleteObject([
				'Bucket' => S3_BUCKET,
				'Key' => $dest_name
			]);
			$retVal = true;
		}catch(Exception $e){
			Logger::Log("Caught Exception in Company->deleteFile while deleting s3 {$s3name}");
		}
		return $retVal;
	}

	public function saveFile(string $src_file, string $dest_name, string $s3_area) {
		//Instantiate the client.
        global $_ZONE;

		$s3 = Aws\S3\S3Client::factory([
            'version' => 'latest',
            'region' => S3_REGION

		]);

		$retVal = "";
        $dest_name = basename($dest_name); //Extract filename without any subfolder fragments seperated by
        $folder = self::S3_AREA[$s3_area] ?? '';

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $contentType = $finfo->file($src_file);

        //Build the name where to store the file
        if (empty($dest_name) || empty($folder))
            return $retVal;

        if (!($s3_area === 'USER' || $s3_area === 'TEMPLATE' || $s3_area === 'COMPANY' || $s3_area === 'USER_BIO'))
            $folder .= $_ZONE->id().'/';

        $s3name = $this->val('s3_folder').$folder.$dest_name;

		try{
			$s3->putObject([
			'Bucket'=>S3_BUCKET,
			'Key'=>$s3name,
			'Body'=>fopen($src_file,'rb'),
			'ACL'=>'public-read',
             'ContentType' => $contentType
			]);
			$retVal = "https://".S3_BUCKET.".s3.amazonaws.com/".$s3name;
		}catch(Exception $e){
			Logger::Log("Caught Exception in Company->saveFile while uploading {$src_file} as {$dest_name} to s3 {$s3name}");
		}
		return $retVal;
	}

    /**
     * Delete the specified file from safe area
     * @param string $filename
     * @param string $s3_area
     * @param bool $throw_exception
     * @return bool
     */
    public function deleteFileFromSafe(string $filename, string $s3_area, bool $throw_exception = false) {

        $retVal = false;

        //Instantiate the client.
        $s3 = Aws\S3\S3Client::factory([
            'version' => 'latest',
            'region' => S3_REGION

        ]);

        $dest_name = basename($filename);
        $folder = self::S3_SAFE_AREA[$s3_area] ?? '';

        if (empty($dest_name) || empty($folder))
            return $retVal;

        $s3name = $this->val('s3_folder').$folder.$dest_name;

        try {
            $s3->deleteObject([
                'Bucket' => S3_SAFE_BUCKET,
                'Key' => $s3name
            ]);
            $retVal = true;
        }catch(\Exception $e){
            if ($throw_exception) {
                throw $e;
            }
            Logger::Log("Fatal Error in Company->deleteFileFromSafe({$s3name}) ".$e->getMessage(), Logger::SEVERITY['FATAL_ERROR']);
        }
        return $retVal;
    }

    /**
     * Saves file in the safe area.
     * @param string $src_file
     * @param string $dest_name
     * @param string $s3_area, should match one of the Company::S3_SAFE_AREA values
     * @param bool $throw_exception
     * @return string
     */
    public function saveFileInSafe(string $src_file, string $dest_name, string $s3_area, bool $throw_exception = false): string {

        $retVal = "";
        global $_ZONE;

        //Instantiate the client.
        $s3 = Aws\S3\S3Client::factory([
            'version' => 'latest',
            'region' => S3_REGION

        ]);

        $dest_name = basename($dest_name); //Extract filename without any subfolder fragments seperated by
        $folder = self::S3_SAFE_AREA[$s3_area] ?? '';

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $contentType = $finfo->file($src_file);

        //Build the name where to store the file
        if (empty($dest_name) || empty($folder))
            return $retVal;

        $s3name = $this->val('s3_folder').$folder.$dest_name;

        $folder .= $_ZONE->id().'/';

        if (str_starts_with($src_file, 's3://')) {
            $s3->copyObject([
                'Bucket' => S3_SAFE_BUCKET,
                'Key' => $s3name,
                'CopySource' => str_replace('s3://', '', $src_file),
            ]);
            return $s3name;
        }

        try{
            $s3->putObject([
                'Bucket'=>S3_SAFE_BUCKET,
                'Key'=>$s3name,
                'Body'=>fopen($src_file,'rb'),
                'ContentType' => $contentType
            ]);
            $retVal = $s3name;
        }catch(Exception $e){
            if ($throw_exception) {
                throw $e;
            }

            Logger::Log("Caught Exception in Company->saveFileInSafe while uploading {$src_file} as {$dest_name} to s3 {$s3name}");
        }
        return $retVal;
    }

    /**
     * Stores file in the uploader bucket area. File is encrypted
     * @param string $src_file - the file to be stored
     * @param string $filename - the name under which it should be stored
     * @param string $s3_area - section, e.g. user-data-sync or user-data-delete
     * @return string - empty string on error, otherwise the filename
     */
    public function saveFileInUploader(string $src_file, string $filename, string $s3_area): string
    {

        $retVal = "";

        $filename = basename($filename); //Extract filename without any subfolder fragments seperated by
        $folder = self::S3_UPLOADER_AREA[$s3_area] ?? '';
        if (empty($filename) || empty($folder)) {
            return $retVal;
        }
        //Build the name where to store the file
        $s3name = $this->getRealm().$folder.$filename;

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $contentType = $finfo->file($src_file);

        try {
            CompanyEncKey::EncryptFileAndUploadToS3(
                filename: $src_file,
                s3_args: [
                    'Bucket' => S3_UPLOADER_BUCKET,
                    'Key' => $s3name,
                    'ContentType' => $contentType,
                ]
            );
            Logger::Log("S3 Uploader: Uploaded file {$s3name}", Logger::SEVERITY['INFO']);
            $retVal = $s3name;
        } catch (Exception $e) {
            Logger::Log("Caught Exception while uploading {$src_file} as {$filename} to s3 {$s3name}");
        }

        return $retVal;
    }

    /**
     * Gets the file from Uploader. If the file the pgp_encrypted, it will be decrypted before downloading
     * @param string $filename - the file name that you want to fetch
     * @param string $s3_area - section, e.g. user-data-sync or user-data-delete
     * @return string - the file is returned as a string
     */
    public function getFileFromUploader(string $filename, string $s3_area, bool $return_s3_file = false): string|array
    {

        $filename = basename($filename); //Extract filename without any subfolder fragments seperated by
        $folder = self::S3_UPLOADER_AREA[$s3_area] ?? '';
        if (empty($filename) || empty($folder)) {
            return '';
        }
        //Build the name where to download the file from
        $s3name = $this->getRealm().$folder.$filename;

        try {
            $result = CompanyEncKey::DownloadFromS3AndDecryptFile([
                'Bucket' => S3_UPLOADER_BUCKET,
                'Key' => $s3name
            ]);

            if ($return_s3_file) {
                return $result;
            }

            $body = file_get_contents($result['filename']) ?? '';
            return $body;
        } catch (Exception $e) {
            Logger::Log("Fatal Exception in Company->getFileFromUploader when downloading {$filename}. Exception = " . $e->getMessage(), Logger::SEVERITY['FATAL_ERROR']);
        }
        return '';
    }

    /**
     * Deletes the file from Uploader.
     * @param string $filename
     * @param string $s3_area
     * @return bool
     */
    public function deleteFileFromUploader(string $filename, string $s3_area) : bool
    {
        $filename = basename($filename); //Extract filename without any subfolder fragments seperated by
        $folder = self::S3_UPLOADER_AREA[$s3_area] ?? '';
        if (empty($filename) || empty($folder)) {
            return '';
        }
        //Build the name where to download the file from
        $s3name = $this->getRealm() . $folder . $filename;

        try {
            $s3 = Aws\S3\S3Client::factory([
                'version' => 'latest',
                'region' => S3_REGION,
            ]);

            $s3->deleteObject([
                'Bucket' => S3_UPLOADER_BUCKET,
                'Key' => $s3name
            ]);
            Logger::Log("S3 Uploader: Deleted file {$s3name}", Logger::SEVERITY['INFO']);
            return true;
        } catch (Exception $e) {
            Logger::Log("Caught Exception while deleting {$s3name}");
        }
        return false;
    }

	public function resizeImage(string $src_file, string $ext, int $max_width): string
    {
        $ext = strtolower($ext);
		$info 	 = getimagesize($src_file);
		$width 	 = $info[0];

		if($width>$max_width){
			$original_w    = $info[0];
			$original_h	   = $info[1];
			if($ext === 'png'){
				$original_img  = imagecreatefrompng($src_file);
			//}elseif($ext == 'bmp') {
		   //	$original_img  = imagecreatefrombmp($src_file);
            }elseif ($ext === 'jpg' || $ext === 'jpeg'){
				$original_img  = imagecreatefromjpeg($src_file);
			} else {
                return $src_file;
            }

			$thumb_w 	   = $max_width;
			$extra_w 	   = $original_w - $thumb_w;
			$ratio	   	   = $extra_w / $original_w;

			$thumb_h	   = round($original_h * $ratio);
			$thumb_h	   = round($original_h - $thumb_h);

			$thumb_img 	   = imagecreatetruecolor($thumb_w, $thumb_h);
			imagealphablending($thumb_img, false );
			imagesavealpha($thumb_img, true );

			if ($original_img === false) {
                Logger::Log("The original image is Invalid.", Logger::SEVERITY['WARNING_ERROR']);
				header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                exit(1);
			}
			imagecopyresampled($thumb_img, $original_img,
			                   0, 0,
			                   0, 0,
			                   $thumb_w, $thumb_h,
			                   $original_w, $original_h);
			if($ext === "png"){
				imagepng($thumb_img, $src_file);
			//}elseif($ext == "bmp"){
			//	imagebmp($thumb_img, $src_file);
            }elseif ($ext === 'jpg' || $ext === 'jpeg'){
				imagejpeg($thumb_img, $src_file);
			}
		}
		return $src_file;
	}

    /**
     * This function internally depends on resizeImage and saveFile functions
     */
	public function uploadImage (array $upfile, string $s3_area, int $max_width)  {

	    $retVal = array();
	    $errorCode = 400; // Default Error code

        try {

            // Undefined | Multiple Files | $_FILES Corruption Attack
            // If this request falls under any of them, treat it invalid.
            if (
                !isset($upfile['error']) ||
                is_array($upfile['error'])
            ) {
                throw new RuntimeException('Invalid parameters.');
            }

            // Check $upfile['error'] value.
            switch ($upfile['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new RuntimeException('No file sent.');
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new RuntimeException('Exceeded filesize limit.');
                default:
                    throw new RuntimeException('Unknown errors.');
            }

            // You should also check filesize here. Max filesize that cah be processed is 20MB
            if ($upfile['size'] > 20000000) {
                Logger::Log("Company:uploadImage failed to upload file due to size limit of 20MB ".json_encode($upfile));
                throw new RuntimeException('Exceeded filesize limit.');
            }

            // DO NOT TRUST $upfile['mime'] VALUE !!
            // Check MIME Type by yourself.
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if (false === $ext = array_search(
                    $finfo->file($upfile['tmp_name']),
                    array(
                        'jpg' => 'image/jpeg',
                        'png' => 'image/png',
                        //'gif' => 'image/gif',
                    ),
                    true
                )) {
                $errorCode = 105;
                throw new RuntimeException('Invalid file format. Only jpg and png type images are allowed');
            }

            $rand	 	=	random_int(100000,999999);
            $tmp = $upfile['tmp_name'];
            $s3_file 	=	strtolower($s3_area) .'_'.teleskope_uuid(). '.' .$ext;
            $tmp 	= $this->resizeImage($tmp, $ext, $max_width);

            // Check resized filesize here. Max filesize that cah be uploaded is 5MB
            if (filesize($tmp) > 5000000) {
                Logger::Log("Company:uploadImage failed to resize file to less than 5MB ".json_encode($upfile));
                throw new RuntimeException('Exceeded filesize limit.');
            }

            $retVal['msg'] = $this->saveFile($tmp, $s3_file, $s3_area);
            $retVal['status'] = 200;
        } catch (RuntimeException $e) {
            $retVal['msg'] = $e->getMessage();
            $retVal['status'] = $errorCode;
        }

        return $retVal;
    }

    /**
     * Provides the email settings for the company. It first looks up if a settings record exists in
     * company_email_settings table. If the settings record exist and custom_smtp has been set to true, then the
     * custom_smtp settings are validated. If the settings are invalid then an exception is thrown.
     * If company_email_settings record is not found or custom_smtp is false then settings are constructed using default
     * settings for deployment.
     * @return array
     * @throws Exception
     */
    public function getEmailSettings(): array
    {
        if ($this->emailSettings === null) {
            $result = self::DBGet("SELECT * FROM `company_email_settings` WHERE `companyid` = '{$this->id}' AND `isactive`='1'");
            if (count($result)) {
                $this->emailSettings = $result[0];
                // Note 'custom_smtp' attribute is part of database record
            } else {
                $this->emailSettings = array();
                $this->emailSettings['custom_smtp'] = 0;
            }

            if ($this->emailSettings['custom_smtp']) {
                // Validate settings
                if (empty( $this->emailSettings['smtp_host']) ||
                    empty($this->emailSettings['smtp_port']) ||
                    empty($this->emailSettings['smtp_username']) ||
                    empty($this->emailSettings['smtp_password']) ||
                    empty($this->emailSettings['smtp_secure']) ||
                    ($this->emailSettings['smtp_secure'] !== 'tls' && $this->emailSettings['smtp_secure'] !== 'ssl') ||
                    empty($this->emailSettings['smtp_from_email'])
                ) {
                    $this->emailSettings = null;
                    throw new Exception ('Company SMTP Settings Invalid');
                }
                // Decrypt the password
                $this->emailSettings['smtp_password'] = CompanyEncKey::Decrypt($this->emailSettings['smtp_password']);
            } else {
                // Set settings to default
                $this->emailSettings['smtp_host'] = SMTP_HOSTNAME;
                $this->emailSettings['smtp_port'] = SMTP_PORT;
                $this->emailSettings['smtp_username'] = SMTP_USERNAME;
                $this->emailSettings['smtp_password'] = SMTP_PASSWORD;
                $this->emailSettings['smtp_secure'] = 'tls';
                $this->emailSettings['smtp_from_email'] = FROM_EMAIL;
            }
        }

        return $this->emailSettings;
    }

    /**
     * Returns email template for non-member emails
     * @return string
     */
    public function getEmailTemplateForNonMemberEmails (): string {
        global $_COMPANY, $_ZONE;
        $appname = 'Affinities';
        $logo = $this->val('logo');
        $allApps = self::APP_LABEL;
        if (isset($_ZONE)) {
            $appname = array_key_exists($_ZONE->val('app_type'),$allApps) ? $allApps[$_ZONE->val('app_type')] : 'Affinities';
            $logo = $this->val('logo');
        }

        $template = file_get_contents(SITE_ROOT . '/email/template2.html');

        $footer = "This email is company confidential and is intended for internal distribution only. You are receiving this email either because an administrator of {$this->val('companyname')} {$appname} site sent you this message or you are a member/leader of some group of {$this->val('companyname')} {$appname} site.";

        return str_replace(array('[%COMPANY_LOGO%]','[%FOOTER%]'), array($logo, $footer), $template);
    }

    /**
     * Returns email template for non-member emails
     * @return string
     */
    public function getEmailTemplateForSurveyEmails (int $groupid ): string {
        global $_COMPANY, $_ZONE;
        $appname = '';
        $allApps = self::APP_LABEL;
        if (isset($_ZONE)) {
            $appname = array_key_exists($_ZONE->val('app_type'),$allApps) ? $allApps[$_ZONE->val('app_type')] : 'Affinities';
        }

        $logo = $this->val('logo');
        if ($_COMPANY->getAppCustomization()['emails']['show_group_logo_instead_of_company_logo'] && $groupid){
            $group = Group::GetGroup($groupid);
            if ($group && $group->val('groupicon')){
                $logo = $group->val('groupicon');
            }
        }

        $template = file_get_contents(SITE_ROOT . '/email/template2.html');

        $footer = "This email is company confidential and is intended for internal distribution only. You are receiving this email either because an administrator of {$this->val('companyname')} {$appname} site sent you this message or you are a member/leader of some group of {$this->val('companyname')} {$appname} site.";

        return str_replace(array('[%COMPANY_LOGO%]','[%FOOTER%]'), array($logo,$footer), $template);
    }

    /**
     * This new email  method allows you to customize from name, from address, replyto address. Also note it does not
     * use a template, raw message is sent.
     * @param string $from_name From Name. Typically set to groupname If not set then company level from name will be used
     * @param string|null $to_addr the email address of recipient; if null is sent we will immediately return.
     * @param string $subject subject of email
     * @param string $message message. Note no template is used. Caller must set the template
     * @param string $app_type can be to AFFINITIES or OFFICERAVEN or TALENTPEAK
     * @param string $reply_addr Reply email address, some customers have mailboxes for group emails
     * @param string $ical_str ical string if any
     * @param array $attachments optional
     * @param string $cc
     * @param int $zoneid (If email is sent by other zone setting)
     * @return bool
     */
    public function emailSend2(string $from_name, ?string $to_addr, string $subject, string $message, string $app_type,
                               string $reply_addr = '', string $ical_str = '', array $attachments = array(),string $cc = '', int $zoneid = 0):bool
    {
        global $_ZONE, $_LOGGER_META_MAIL;
        $retVal = false;

        $zone = $_ZONE;
        if ($zoneid && $zoneid!=$zone->id()) {
            $zone = $this->getZone($zoneid);
        }
        $_LOGGER_META_MAIL = null;

        try {
            // Load Email settings
            $this->getEmailSettings();
            if (($this->emailSettings['custom_smtp'])) {
                $from_addr = $zone->val('email_from') ?: $this->emailSettings['smtp_from_email']; // *** Force the from address set for custom smtp ***
            } else {
                $from_addr = $this->getFromEmailAddr($app_type);
            }

            // Initialize Logger Meta data
            $masked_toAddr = implode(',', array_map([$this,'getEmailMask'],explode(',', $to_addr)));
            $_LOGGER_META_MAIL = [
                'fromName' => $from_name,
                'fromAddr' => $from_addr,
                'toAddr' => $masked_toAddr,
                'subject' => $subject,
                'attachments' => count($attachments),
                'ical' => !empty($ical_str),
            ];

            if (empty($to_addr) && empty($cc)) {
                Logger::Log('Email - Skipped, empty address', Logger::SEVERITY['INFO']);
                return true; // Since empty email address is valid usecase, we will return true.
            }

            if ($zone->val('email_settings') < 1) {
                Logger::Log('Email - Skipped, send email disabled', Logger::SEVERITY['INFO']);
                return false; //Email is disabled for this company
            }

            if (empty($from_name))
                $from_name= $zone->val('email_from_label') ?: FROM_NAME; //Php 7 Elvis operator

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->XMailer = "Teleskope mailer";
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            //Server settings
            $mail->SMTPDebug = false;                                   // Donot print any debug info
            $mail->isSMTP();                                            // Set mailer to use SMTP
            $mail->Host = $this->emailSettings['smtp_host'];            // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                                     // Enable SMTP authentication
            $mail->Username = $this->emailSettings['smtp_username'];    // SMTP username
            $mail->Password = $this->emailSettings['smtp_password'];    // SMTP password
            $mail->SMTPSecure =  $this->emailSettings['smtp_secure'];   // Enable TLS encryption, `ssl` also accepted
            $mail->Port = $this->emailSettings['smtp_port'];            // TCP port to connect to

            if (!empty ($reply_addr)) { // First check if reply address was provided, e.g. group has reply address
                $mail->addReplyTo($reply_addr, $from_name);
                $_LOGGER_META_MAIL['replyAddr'] = $reply_addr;
            } elseif (!empty($zone->val('email_replyto'))) { // Next check if default reply to is provided at the zone level
                $mail->addReplyTo($zone->val('email_replyto'), $from_name);
                $_LOGGER_META_MAIL['replyAddr'] = $zone->val('email_replyto');
            } elseif (!empty($this->emailSettings['smtp_replyto_email'])) { // Or if one is provided as part of SMTP settings
                $mail->addReplyTo($this->emailSettings['smtp_replyto_email'], 'Teleskope');
            }

            //Recipients
            $mail->setFrom($from_addr, $from_name);

            $tos = explode(',',$to_addr);
            foreach ($tos as $toi) {
                $toi = html_entity_decode($toi); // Emails are stored after processing with html special characters. they need to be decoded.
                if (!empty($toi) && $this->isValidAndRoutableEmail($toi)) { // Domain check to send emails only to domains that are allowed.
                    $mail->addAddress($toi);
                } elseif ($this->isValidEmail($toi)) {
                    // This is a valid email domain but not a routable domain which means its probably a user with external email
                    if ($this->getAppCustomization()['emails']['allow_send_to_external_emails']) {
                        $external_email = User::GetUserByEmail($toi) ?-> val('external_email');
                        if ($external_email) {
                            $mail->addAddress($external_email);
                        } else {
                            $retVal = 0;
                        }
                    } elseif ($this->getAppCustomization()['user_inbox']['enabled']) {
                        // If Non Routable Email address, Send email to affinity inbox (For Mobile app User)
                        $retVal = UserInbox::SaveMessage($toi,$from_name,$from_addr,$subject,$message);
                        Logger::Log('Email - Routed to Inbox: '. $toi, Logger::SEVERITY['INFO']);
                    } else {
                        Logger::Log('Email - Skipped - CC To: '. $toi, Logger::SEVERITY['INFO']);
                    }
                } else {
                    Logger::Log('Email - Skipped', Logger::SEVERITY['INFO']);
                }
            }

            // CC
            if (!empty($cc)){
                $masked_ccAddr = implode(',', array_map([$this,'getEmailMask'],explode(',', $cc)));
                $_LOGGER_META_MAIL['ccAddr'] = $masked_ccAddr;
                $cc_array  = explode(',',$cc);
                foreach ($cc_array as $c) {
                    if (!empty($c) && $this->isValidAndRoutableEmail($c)) { // Domain check to send cc emails only to domains that are allowed.
                        $mail->addCC($c);
                    } elseif ($this->isValidEmail($c)) {
                        // This is a valid email domain but not a routable domain which means its probably a user with external email
                        if ($this->getAppCustomization()['emails']['allow_send_to_external_emails']) {
                            $external_email = User::GetUserByEmail($c) ?-> val('external_email');
                            if ($external_email) {
                                $mail->addCC($external_email);
                            } else {
                                $retVal = 0;
                            }
                        } elseif ($this->getAppCustomization()['user_inbox']['enabled']) {
                            // If Non Routable Email address, Send email to affinity inbox (For Mobile app User)
                            $retVal = UserInbox::SaveMessage($c,$from_name,$from_addr,$subject,$message);
                            Logger::Log('Email - Routed to Inbox: '. $c, Logger::SEVERITY['INFO']);
                        } else {
                            Logger::Log('Email - Skipped - CC To: '. $c, Logger::SEVERITY['INFO']);
                        }
                    } else {
                        Logger::Log('Email - Skipped', Logger::SEVERITY['INFO']);
                    }
                }
            }

            //Content
            $mail->Subject = $subject;
            $mail->isHTML(true);                   // Set email format to HTML
            $mail->Body = $message;
            $mail->AltBody = "This is a HTML message only";

            // For testing purposes
            //file_put_contents('/tmp/email.html', $message);
            //return true;

            if (!empty($ical_str)) {
                $mail->Ical = $ical_str;
                //$mail->addStringAttachment($ical_str,'invite.ics','base64','text/calendar');
            }

            foreach ($attachments as $attach) {
                $mail->addStringAttachment($attach['content'],$attach['filename']);
            }

            $max_retries = 5;
            $current_try = 0;
            while ($current_try++ < $max_retries) {
                try {
                    if ($mail->send()) {
                        $current_try = $max_retries;
                        Logger::Log('Email - Sent', Logger::SEVERITY['INFO']);
                        return true;
                    } else {
                        Logger::Log('Email - Fatal: Mailer Error for ' . $masked_toAddr . ' : Unknown Error during attempt ' . $current_try, Logger::SEVERITY['FATAL_ERROR']);
                    }
                } catch (Exception $sendException) {
                    if (strpos($sendException->getMessage(),'SMTP ') === 0) {
                        // SMTP errors handled are like of SMTP Error, SMTP connect
                        $retry_interval = $current_try * 2 + rand($current_try,$current_try+5);
                        Logger::Log('Email - Retry after ' . $retry_interval . 's due to exception ' . $sendException->getMessage(), Logger::SEVERITY['WARNING_ERROR']);
                        sleep($retry_interval);
                    } else {
                        throw ($sendException);
                    }
                }
            }
            Logger::Log('Email - Fatal: Mailer Error for '. $masked_toAddr . ' : Exhausted all retries', Logger::SEVERITY['FATAL_ERROR']);
            return false;
        } catch (Exception $e) {
            $severity = Logger::SEVERITY['FATAL_ERROR'];
            if (
                str_contains($e->getMessage(), 'must provide at least one recipient email address')
                ||
                str_contains($e->getMessage(), 'Invalid address:')
            ) {
                $severity = Logger::SEVERITY['WARNING_ERROR'];
            }
            Logger::Log('Email - Mailer Error: ' . $e->getMessage(), $severity);
            return false || $retVal;
        } finally {
            $_LOGGER_META_MAIL = null;
        }
    }

    /**
     * Creates a mask for email for logging purposes.
     * @param string $email
     * @return string
     */
    public function getEmailMask (string $email)
    {
        global $_COMPANY;
        $email_hash = hash('MD5', ($_COMPANY ?-> val('aes_suffix') ?: '_none_') . $email);
        return Str::GenerateMask($email) . '/' . $email_hash;
    }


    public function getRealm() : string {
        return $this->val('subdomain').'.teleskope.io';
    }

    public function getFQDNTeleskope() : string {
        return $this->val('subdomain').'.teleskope.io';
    }


    /**
     * @param string $app_type
     * @return string
     */
    public function getAppDomain(string $app_type): string
    {
        if (stripos($app_type, 'affinities') !== FALSE) {
            $email_domain = $this->val('subdomain') . '.affinities.io';

        } elseif (stripos($app_type, 'officeraven') !== FALSE) {
            $email_domain = $this->val('subdomain') . '.officeraven.io';

        } elseif (stripos($app_type, 'peoplehero') !== FALSE) {
            $email_domain = $this->val('subdomain') . '.peoplehero.io';

        } elseif (stripos($app_type, 'talentpeak') !== FALSE) {
            $email_domain = $this->val('subdomain') . '.talentpeak.io';

        } elseif (stripos($app_type, 'peoplehero') !== FALSE) {
            $email_domain = $this->val('subdomain') . '.peoplehero.io';

        } else {
            // Assume it is teleskope
            $email_domain = $this->val('subdomain') . '.teleskope.io';
        }
        return $email_domain;
    }

    /**
     * Returns an email From Address based on the application context.
     * @param string $app_type
     * @return string
     */
    public function getFromEmailAddr(string $app_type) : string {
        $email_prefix = $this->val('from_email_prefix') ?: 'noreply';
        $email_domain = $this->getAppDomain($app_type);
        return $email_prefix . '@' . $email_domain;
    }

    /**
     * Returns an email RSVP Address based on the application context.
     * @param string $app_type
     * @return string
     */
    public function getRsvpEmailAddr(string $app_type) : string {
        $email_domain = $this->getAppDomain($app_type);
        return 'rsvp@'.$email_domain;
    }

    /**
     * Return Admin website url for the company. Note: the URL ends with /
     * @return string
     */
    public function getAdminURL (): string
    {
        //$urlhost = parse_url($this->val('uniqueuri'), PHP_URL_HOST);
        //$subdomain = strtolower(explode('.', $urlhost, 3)[0]);
        $subdomain = $this->val('subdomain');
        $url = preg_replace('/\/\/\w*/','//'.$subdomain,BASEURL);
        return $url;
    }

    /**
     * Return Affinities or Office website url for the company. Note: the URL ends with /
     * @param $app_type
     * @return string affinties or officeraven url or empty string on error
     */
    public function getAppURL ($app_type): string
    {
        if ($app_type === 'affinities') {
            return 'https://'.$this->val('subdomain').'.affinities.io'.BASEDIR.'/affinity/';
        } elseif ($app_type === 'officeraven') {
            return 'https://'.$this->val('subdomain').'.officeraven.io'.BASEDIR.'/officeraven/';
        } elseif ($app_type === 'peoplehero') {
            return 'https://'.$this->val('subdomain').'.peoplehero.io'.BASEDIR.'/peoplehero/';
        } elseif ($app_type === 'talentpeak') {
            return 'https://'.$this->val('subdomain').'.talentpeak.io'.BASEDIR.'/talentpeak/';
        } elseif ($app_type === 'peoplehero') {
            return 'https://'.$this->val('subdomain').'.peoplehero.io'.BASEDIR.'/peoplehero/';
        }
        return '';
    }

    public function __getval_subdomain(string $what)
    {
        if (Config::Get('GR_TEST_MODE_ENABLED') == 1) {
            return Config::Get('GR_DOMAIN_PREFIX') . $this->val('subdomain', false);
        }

        return $this->val('subdomain', false);
    }

    public function getSurveyURL ($app_type) {
        $url = $this->getAppURL($app_type);
        if ($app_type === 'affinities') {
            return str_replace('/affinity/', '/survey/', $url);
        } elseif ($app_type === 'officeraven') {
            return str_replace('/officeraven/', '/survey/', $url);
        } elseif ($app_type === 'talentpeak') {
            return str_replace('/talentpeak/', '/survey/', $url);
        } elseif ($app_type === 'peoplehero') {
            return str_replace('/peoplehero/', '/survey/', $url);
        }
    }

    public function getiFrameURL ($app_type) {
        $url = $this->getAppURL($app_type);
        if ($app_type === 'affinities') {
            return str_replace('/affinity/', '/iframe/', $url);
        } elseif ($app_type === 'officeraven') {
            return str_replace('/officeraven/', '/iframe/', $url);
        } elseif ($app_type === 'talentpeak') {
            return str_replace('/talentpeak/', '/iframe/', $url);
        } elseif ($app_type === 'peoplehero') {
            return str_replace('/peoplehero/', '/iframe/', $url);
        }
    }

    /**
     * Gives the base url for the application
     * @param $app_type
     * @return string
     */
    public function getAppURLBase($app_type): string
    {
        $retVal = '';
        $appUrl = $this->getAppURL($app_type);
        if ($appUrl){
            $parsedUrl = parse_url($appUrl);
            $retVal = 'https://'.$parsedUrl['host'].'/';
        }
        return $retVal;
    }

    /**
     * This function is used for printing ids in reports or listing tables.
     * @param ?int $i
     * @return string
     */
    public function encodeIdForReport(?int $i):string
    {
        // Make function null-safe
        if (is_null($i)) {
            return '';
        }

        // Initially we considered the following function
        //return ($i < 0) ? 0 : ($this->id ^ $i);
        //return ($i < 0) ? '' : dechex($i);
        //return ($i < 0) ? '' : decoct($i);
        // But, in order to keep things simple we are returning our standard encoded id
        //return strtoupper(substr($this->encodeId($i), 3));
        return ($i < 0) ? '' : $this->baseStringForIdEncoding . strtoupper(dechex($i));
    }

    /**
     * This function can encode a comma-separated list of integer-ids
     * Output is the comma-separated list of encoded-ids
     * Input - "1,2,3"
     * Output - "C1-Z1,C1-Z2,C1-Z3"
     */
    public function encodeIdsInCSVForReport(?string $csv): string
    {
        // Make function null-safe
        if (!$csv) {
            return '';
        }

        return implode(',', array_map(array($this, 'encodeIdForReport'), explode(',', $csv)));
    }

    public function decodeIdForReport(string $id_str): ?int
    {
        if (str_starts_with($id_str, $this->baseStringForIdEncoding)) {
            $enc_id = substr($id_str, strlen($this->baseStringForIdEncoding));
            return intval(hexdec($enc_id));
        }
        return null;
    }
    /**
     * This function generates an encoded string from a given id. It is used to generate encoded strings before writing
     * ids in html
     * @param int $i
     * @return string
     */
    public function encodeId(int $i):string {
        //$i = rand(10000000,99999999).$i;
        if ($i < 0) return '';
        $i = (10000000+$this->id).$i;
        $i = base_convert($i,10,36);
        $s = 'f1_'.str_rot13($i);
        return $s;
    }

    /**
     * @param string $csv a csv of integers
     * @return string a csv of encoded integers
     */
    public function encodeIdsInCSV(string $csv):string {
        return implode(',', array_map(array($this, 'encodeId'), explode(',', $csv)));
    }

    /**
     * @param array $decodedIds
     * @return array of encodeds integers
     */
    public function encodeIdsInArray(array $decodedIds):array {
        return array_map(array($this, 'encodeId'), $decodedIds);
    }

    /**
     * This function generates and decoded id from encoded string. Used to generate ids from string ids recieved from
     * web browsers
     * @param string $s
     * @return int, -1 on error
     */
    public function decodeId(string $s):int {
        if (empty($s)) {
            return -1;
        } elseif (strpos($s,'f1_')===0) {
            $s = substr($s,3);
            $s = str_rot13($s);
            $s = base_convert($s,36,10);
            $c = (int)substr($s,0,8)-10000000;
            if ($c === $this->id)
                return (int)substr($s,8);
            else {
                Logger::Log("Security Error: Unable to decode id in company context id={$s}", Logger::SEVERITY['SECURITY_ERROR']);
                return -1;
            }
        } elseif ($s === 'undefined' || $s === 'null') {
            // Empty states in javascript are sent as 'undefined' string
            return -1;
        } else {
            //It was encoded using previous base64 encoding.
            Logger::Log("Error: Unknown id {$s} format encountered");
            return -1;
        }
    }

    /**
     * @param string $csv
     * @return string a CSV string with decoded Ids
     */
    public function decodeIdsInCSV(string $csv):string {
        return implode(',', array_map(array($this, 'decodeId'), explode(',', $csv)));
    }

    /**
     * @param array $encodedIds
     * @return array with decodedIds
     */
    public function decodeIdsInArray(array $encodedIds):array {
        return array_map(array($this, 'decodeId'), $encodedIds);
    }

    /**
     * Depricated
     */
    public static function deleteRegion(int $regionid)
    {
        global $_COMPANY,$_ZONE;
        $check = self::DBGet("SELECT `regionid` FROM `regions` WHERE `regionid`='{$regionid}' AND `companyid` = '{$_COMPANY->id()}'");
        if (count($check)>0) {
            //Update User
            self::DBMutate("UPDATE `users` SET `regionid`='0' WHERE `regionid`='{$regionid}' AND `companyid`='{$_COMPANY->id()}' ");
            // UPDATE GROUP
            $groups = self::DBGet("SELECT `groupid`, `regionid` FROM `groups` WHERE `companyid`='{$_COMPANY->id()}' AND FIND_IN_SET('{$regionid}',`regionid`)");
            foreach ($groups as $group) {
                $regions = explode (',', $group['regionid']);
                while(($i = array_search($regionid, $regions)) !== false) {
                    unset($regions[$i]);
                }
                $regions = array_unique($regions);
                if (count($regions))
                    $new_regionids = implode(',',$regions);
                else
                    $new_regionids = '0';
                self::DBMutate("UPDATE `groups` SET `regionid`= '{$new_regionids}' WHERE `groupid`={$group['groupid']}");
                $_COMPANY->expireRedisCache("GRP:{$group['groupid']}");
                $_COMPANY->expireRedisCache("GRP_CHP_LST:{$group['groupid']}");
            }
            // UPDATE CHAPTER
            $chapters = self::DBGet("SELECT `chapterid`, `regionids` FROM chapters LEFT JOIN `groups` USING (groupid) WHERE chapters.companyid='{$_COMPANY->id()}' AND ( chapters.zoneid='{$_ZONE->id()}' AND FIND_IN_SET('{$regionid}',`regionid`))");
            foreach ($chapters as $chapter) {
                $regions = explode (',', $chapter['regionids']);
                while(($i = array_search($regionid, $regions)) !== false) {
                    unset($regions[$i]);
                }
                $regions = array_unique($regions);
                if (count($regions))
                    $new_regionids = implode(',',$regions);
                else
                    $new_regionids = '0';
                self::DBMutate("UPDATE `chapters` SET `regionids`= '{$new_regionids}' WHERE chapters.companyid='{$_COMPANY->id()}' AND ( chapters.zoneid='{$_ZONE->id()}' AND `chapterid`='{$chapter['chapterid']}')");
            }
            //UPDATE GROUP LEAD
            $groupleads = self::DBGet("SELECT `leadid`, `regionids` FROM `groupleads` LEFT JOIN `groups` USING (groupid) WHERE `companyid`='{$_COMPANY->id()}' AND FIND_IN_SET('{$regionid}',`regionids`)");
            foreach ($groupleads as $grouplead) {
                $regions = explode (',', $grouplead['regionids']);
                while(($i = array_search($regionid, $regions)) !== false) {
                    unset($regions[$i]);
                }
                $regions = array_unique($regions);
                if (count($regions))
                    $new_regionids = implode(',',$regions);
                else
                    $new_regionids = '0';
                self::DBMutate("UPDATE `groupleads` SET `regionids`= '{$new_regionids}' WHERE `leadid`='{$grouplead['leadid']}");
            }
            //UPDATE COMPANY BRANCHES
            self::DBMutate("UPDATE `companybranches` SET `regionid`=0 WHERE `companyid`='{$_COMPANY->id()}' AND `regionid`='{$regionid}' ");
            // Delete Region
            self::DBMutate("DELETE FROM `regions` WHERE `companyid`='{$_COMPANY->id()}' AND `regionid`='{$regionid}'");
            return 1;
        }
        return 0;
    }

    public function getCompanyContact (string $contactrole) {
        $contact = null;
        $c  = self::DBGet("SELECT * FROM `company_contacts` WHERE `companyid`= '{$this->id}' AND `contactrole` ='{$contactrole}' ");

        if (count($c)){
            $contact = $c[0];
        }
		return $contact;
    }

    public function addCompanyContact (string $contactrole, string $firstname, string $lastname, string $email, string $phonenumber, string $title) {
        return self::DBInsertPS("INSERT INTO `company_contacts`(`companyid`, `contactrole`, `firstname`, `lastname`, `email`, `phonenumber`, `title`) VALUES (?,?,?,?,?,?,?)", 'issssss', $this->id,$contactrole, $firstname, $lastname, $email, $phonenumber, $title);
    }

    public function updateCompanyContact (string $contactrole, string $firstname, string $lastname, string $email, string $phonenumber, string $title) {
        return self::DBMutatePS("UPDATE `company_contacts` SET  `firstname`=?, `lastname`=?, `email`=?, `phonenumber`=?, `title`=?, modifiedon=now() WHERE  `companyid`=? AND `contactrole`=?", 'sssssis',  $firstname, $lastname, $email, $phonenumber, $title,$this->id,$contactrole);
    }

    public function getCompanySecurity () {
        if ($this->securitySettings === null) {
            $result = self::DBROGet("SELECT * FROM `company_security_settings` WHERE `companyid`= '{$this->id}'");
            if (count($result)) {
                $this->securitySettings = $result[0];
            } else {
                $this->securitySettings = self::DEFAULT_SECURITY_SETTINGS;
            }
        }
        return $this->securitySettings;
    }

    public function addOrUpdateCompanySecuritySetting (int $admin_inactivity_max,int $admin_session_max,string $admin_whitelist_ip, int $apps_inactivity_max, int $apps_session_max, int $mobile_session_max,int $mobile_session_logout_time_in_min_utc,
        $company_admin_external_roles,
        $zone_admin_external_roles,
        $group_lead_external_roles,
        $chapter_lead_external_roles,
        $channel_lead_external_roles
    ) {
        return self::DBUpdatePS("INSERT INTO `company_security_settings`(`companyid`, `admin_inactivity_max`, `admin_session_max`, apps_inactivity_max, `apps_session_max`, `mobile_session_max`,`admin_whitelist_ip`,`mobile_session_logout_time_in_min_utc`,
            `company_admin_external_roles`,
            `zone_admin_external_roles`,
            `group_lead_external_roles`,
            `chapter_lead_external_roles`,
            `channel_lead_external_roles`
        ) VALUES (?,?,?,?,?,?,?,?,
            ?,?,?,?,?
        ) ON DUPLICATE KEY UPDATE `admin_inactivity_max`=?, `admin_session_max`=?, `apps_inactivity_max`=?, `apps_session_max`=?, `mobile_session_max`=?, `admin_whitelist_ip`=?, `mobile_session_logout_time_in_min_utc`=?,
            `company_admin_external_roles` = ?,
            `zone_admin_external_roles` = ?,
            `group_lead_external_roles` = ?,
            `chapter_lead_external_roles` = ?,
            `channel_lead_external_roles` = ?
        ",
            'iiiiiiis'
            . 'sssss'
            . 'iiiiisi'
            . 'sssss',
            $this->id, $admin_inactivity_max, $admin_session_max, $apps_inactivity_max, $apps_session_max, $mobile_session_max, $admin_whitelist_ip,$mobile_session_logout_time_in_min_utc,
            $company_admin_external_roles,
            $zone_admin_external_roles,
            $group_lead_external_roles,
            $chapter_lead_external_roles,
            $channel_lead_external_roles,
            $admin_inactivity_max, $admin_session_max, $apps_inactivity_max, $apps_session_max, $mobile_session_max,$admin_whitelist_ip,$mobile_session_logout_time_in_min_utc,
            $company_admin_external_roles,
            $zone_admin_external_roles,
            $group_lead_external_roles,
            $chapter_lead_external_roles,
            $channel_lead_external_roles
        );
    }

    /**
     * Returns regionid or 0 if region was not specified
     * @param string $region
     * @return int
     */
    public function getOrCreateRegion(string $region): int
    {
        global $_USER;
        $retVal = 0;

        $uid = 0;
        if ($_USER && ($_USER->cid() === $this->cid())) {
            $uid = $_USER->id();
        }

        if (empty($region))
            return 0;

        $row= self::DBGetPS("select regionid from regions where companyid=? and region=?",'is',$this->id,$region);
        if(empty($row)){
            $retVal = self::DBInsertPS("insert into regions (companyid, region, userid, date, isactive) values (?,?,?,NOW(),1)",'isi',$this->id,$region,$uid);
        }else{
            $retVal = $row[0]['regionid'];
        }
        return is_int($retVal) ? (int)$retVal : 0;
    }

    /**
     * A wrapper around getOrCreateRegion method to make it memory optimized
     * @param string $region
     * @return int
     */
    public function getOrCreateRegion__memoized (string $region): int
    {
        $memoize_key = __METHOD__ . ':'  . $this->id() . ':' . serialize(func_get_args());
        if (!isset(self::$memoize_cache[$memoize_key]))
            self::$memoize_cache[$memoize_key] = $this->getOrCreateRegion($region);

        return intval(self::$memoize_cache[$memoize_key] ?? 0);
    }


    public function createOrUpdateRegion(int $regionid, string $region)
    {
        global $_USER; /* @var User $_USER */
        $uid = 0;

        if($regionid){
            return self::DBUpdatePS("UPDATE `regions` SET `region`=? WHERE `companyid`=? AND `regionid`= ?",'sii',$region,$this->id,$regionid);
        }else{
            $exists = self::DBGetPS("SELECT regionid AS region_exists FROM `regions` WHERE `companyid`=? AND `region`=?", 'is', $this->id, $region);
            if (!empty($exists)) {
                return $exists[0]['regionid'];
            }
            return self::DBInsertPS("INSERT into `regions` (companyid, region, userid, `date`, isactive) values (?,?,?,NOW(),1)",'isi',$this->id,$region,$_USER->id());
        }
    }

     /**
     * AddUpdateZoneTile Zone Tile...
     */

     public static function AddUpdateZoneTile(string $zone_tile_bg_image, string $zone_tile_heading, string $zone_tile_sub_heading,string $zone_tile_compact_bg_image)
     {
         global $_COMPANY;
         global $_ZONE;
         $setting = [];
         $setting['style']['zone_tile_bg_image'] = $zone_tile_bg_image;
         $setting['style']['zone_tile_compact_bg_image'] = $zone_tile_compact_bg_image;
         $setting['style']['zone_tile_heading'] = $zone_tile_heading;
         $setting['style']['zone_tile_sub_heading'] = $zone_tile_sub_heading;
         $_ZONE->updateZoneCustomizationKeyVal($setting);
     }

    /**
     * Update Zone Web Banner Title
     */

    public static function UpdateZoneBannerTitle(string $web_banner_title)
    {
        global $_COMPANY;
        global $_ZONE;

        return self::DBUpdatePS("UPDATE `company_zones` SET `banner_title`=? WHERE companyid=? AND zoneid=?",'sii',$web_banner_title,$_COMPANY->id(),$_ZONE->id());
    }

    /**
     * Update Zone Web Banner Sub Title
     */

    public static function UpdateZoneBannerSubTitle(string $web_banner_subtitle)
    {
        global $_COMPANY;
        global $_ZONE;

        return self::DBUpdatePS("UPDATE `company_zones` SET `banner_subtitle`=? WHERE companyid=? AND zoneid=? ",'sii',$web_banner_subtitle,$_COMPANY->id(),$_ZONE->id());
    }

    /**
     * Update Zone Hot link Placement
     */

    public static function UpdateZoneHotlinkPlacement(string $where)
    {
        global $_COMPANY;
        global $_ZONE;

        $where = ($where === 'header') ? 'header' : 'banner';
        return self::DBUpdatePS("UPDATE `company_zones` SET `hotlink_placement`=? WHERE companyid=? AND zoneid=? ",'xii',$where,$_COMPANY->id(),$_ZONE->id());
    }

    /**
     * Update Privacy policy
     */

    public function updateCompanyPrivacyPolicy (string $customer_privacy_link) {
        return self::DBMutatePS("UPDATE `companies` SET  `customer_privacy_link`=?,customer_policy_updated_on=now() WHERE `companyid`=?", 'si',  $customer_privacy_link,$this->id);
    }

    public function updateCompanyTermsOfService (string $customer_terms_of_service) {
        return self::DBMutatePS("UPDATE `companies` SET  `customer_tos_link`=?,customer_policy_updated_on=now() WHERE `companyid`=?", 'si',  $customer_terms_of_service,$this->id);
    }

    /**
     * Add/Update Landing Page Zone Headings
    */
    public function updateZoneSelectorSettings (string $zone_heading, string $zone_sub_heading) {
        return self::DBMutatePS("UPDATE `companies` SET  `zone_heading`=?,`zone_sub_heading`=? WHERE `companyid`=?", 'ssi', $zone_heading, $zone_sub_heading, $this->id);
    }

    /**
     * @param string $zoneids
     * @return int
     */
    public function updateZoneSelectorZoneIds (string $zoneids): int
    {
        $zoneids = Sanitizer::SanitizeIntegerCSV($zoneids);
        return self::DBMutatePS("UPDATE `companies` SET  `zone_selector_zoneids`=? WHERE `companyid`=?", 'xi', $zoneids, $this->id);
    }

    public function updateZoneSelectorZoneLayout (string $zone_selector_page_layout): int
    {
        return self::DBMutatePS("UPDATE `companies` SET  `zone_selector_page_layout`=? WHERE `companyid`=?", 'xi', $zone_selector_page_layout, $this->id);
    }
    public function enableOrDisableZoneSelector (bool $enable_disable)
    {
        global $_COMPANY;
        $enable_disable_val = $enable_disable ? 1 : 0;
        return self::DBMutate("UPDATE `companies` SET zone_selector={$enable_disable_val} WHERE `companyid`={$_COMPANY->id()}");
    }

    public function updateZoneRegions (string $regionids)
    {
        global $_COMPANY,$_ZONE;
        return self::DBMutatePS("UPDATE  `company_zones` SET `regionids` = ?,`modifiedon`=now() WHERE `companyid`=? AND `zoneid`=?",'sii',$regionids,$_COMPANY->id(),$_ZONE->id());

    }


    /**
     * Note this function fetches Footer links from database. If you are looking for footer links for zone for
     * display, use the $_ZONE->val('footer_links') property to get an array of cached links for the zone.
     * @param bool $ignoreStatus
     * @return array
     */
    public function getFooterLinks(bool $ignoreStatus = false){
        global $_ZONE;
        $status_filter = '';
        if (!$ignoreStatus) {
            $status_filter = "AND `isactive`='1'";
        }

        $footerlinks = self::DBGet("SELECT * FROM company_footer_links WHERE `companyid`={$this->id()} AND `zoneid`={$_ZONE->id()} {$status_filter}");
        usort($footerlinks, function($a, $b) {
            return $a['link_section'] <=> $b['link_section'];
        });
        return $footerlinks;
    }

    /**
     * @param int $link_id
     * @param string $link_title
     * @param string $link_section
     * @param int $link_type
     * @param string $link
     * @return int|string
     */
    public function addOrUpdateFooterLink (int $link_id, string $link_title, string $link_section, int $link_type, string $link) {
        global $_USER,$_ZONE;
        if ($link_id > 0){
            return self::DBMutatePS("UPDATE company_footer_links SET  link_title=?, `link`=?, link_type=?, link_section=?, modifiedby=?, modifiedon=now() WHERE companyid=? AND zoneid=? AND link_id=?", 'xxixiiii',$link_title,$link, $link_type,$link_section,$_USER->id(),$this->id,$_ZONE->id(),$link_id);
        } else {
            return self::DBInsertPS("INSERT INTO company_footer_links(`companyid`, `zoneid`, `modifiedon`, link_title, `link`, link_type, link_section,isactive) VALUES (?,?,?,?,?,?,?,?)", 'iiixxixi', $this->id,$_ZONE->id(),$_USER->id(),$link_title,$link, $link_type,$link_section,0);
        }
    }

    public function getFooterLinkDetail(int $link_id){
        global $_ZONE;
        $link = null;
        $footerlinks = self::DBGet("SELECT * FROM company_footer_links WHERE `companyid`={$this->id()} AND `zoneid`={$_ZONE->id()} AND link_id={$link_id}");
        if(count($footerlinks)){
            $link = $footerlinks[0];
        }
        return $link;
    }

    public function updateFooterLinkStatus (int $link_id, int $status) {
        global $_USER;
        return self::DBMutate("UPDATE company_footer_links SET  `isactive`={$status}, modifiedby={$_USER->id()}, `modifiedon`=NOW() WHERE companyid={$this->id} AND link_id={$link_id}");
    }

    public function deleteFooterLink (int $link_id)
    {
        return self::DBMutate(" DELETE FROM `company_footer_links` WHERE `link_id`={$link_id} AND `companyid`={$this->id()}");
    }

    public function updateCompanyZoneEmailSetting(int $email_settings, string $email_from_label, string $email_from, string $email_replyto){
        global $_ZONE;
        $email_from = $email_from ?: null;
        $email_replyto = $email_replyto ?: null;
        return self::DBMutatePS("UPDATE company_zones SET  email_from_label=?, `email_from`=?, `email_replyto`=?, `email_settings`=?, modifiedon=now() WHERE companyid=? AND zoneid=? ", 'xxxiii',$email_from_label,$email_from,$email_replyto,$email_settings,$this->id,$_ZONE->id());

    }

    public function getCompanyLoginMethods (string $scope,int $onlyActive = 0)
    {
        $scopeFilter = '';
        if($scope){
            $scopeFilter = " AND scope='{$scope}'";
        }
        $isactiveFilter= '';
        if($onlyActive){
            $isactiveFilter=  "AND `isactive`='1' ";
        }

        $key = "LOGINMETHODS:{$scope}";
        $data = array();
        if (($data = $this->getFromRedisCache($key)) === false) {
            $data = array(); // First reset data to empty array as it will be set to false by now.
            $records = self::DBGet("SELECT * FROM `company_login_settings` WHERE `companyid`={$this->id} {$scopeFilter}  {$isactiveFilter} ");
            if (!empty($records)) {
                foreach ($records as $record) {
                    $row = $record;
                    $row['customization'] = json_decode(str_replace('\\', '\\\\', $row['customization'] ?? ''), true);
                    $rattributes = json_decode($row['attributes'], true) ?? array();
                    unset($row['attributes']);

                    $row = array_merge($row, $rattributes);
                    $data[] = $row;
                }
                usort($data, function ($a, $b) {
                    //return by order (a) first saml2, (b) microsoft, (c) username
                    if ($a['loginmethod'] === 'saml2') {
                        return -100;
                    } elseif ($a['loginmethod'] === 'microsoft') {
                        return -90;
                    } elseif ($a['loginmethod'] === 'connect' || $a['loginmethod'] === 'otp') {
                        return -80;
                    } elseif ($a['loginmethod'] === 'username') {
                        return 100;
                    } else {
                        return strcmp($b['loginmethod'], $a['loginmethod']);
                    }
                });
            }
            $this->putInRedisCache($key, $data, 86400);
        }
        return $data;
    }

    public function getCompanyLoginMethodByScopeAndId (string $scope,int $id) {
        $companyLoginMethods = $this->getCompanyLoginMethods($scope, 1);
        foreach ($companyLoginMethods as $loginMethod) {
            if ($loginMethod['settingid'] == $id) {
                return $loginMethod;
            }
        }
    }

    public function isConnectEnabled()
    {
        return !empty($this->fields['connect_attribute']);
    }

    public function getCompanyLoginMethodOfConnectType(string $app_type)
    {
        global $_COMPANY;
        $companyLoginMethods = $_COMPANY->getCompanyLoginMethods($app_type, 1);
        foreach ($companyLoginMethods as $companyLoginMethod) {
            if ($companyLoginMethod['loginmethod'] == 'connect') {
                return $companyLoginMethod;
            }
        }
        return null;
    }

    /**
     * @param $filename the name of the file that needs to be operated (.enc is expected for files to be decrypted
     * and .dec is expected for files to be encrypted).
     * @param $action 1 for encryption, 2 for decryption
     * @return string name of the new file or empty string if file could not be written
     */
    public function encryptDecryptFile ($filename,$action ){
        $output_filename = '';
        $encrypt_method = "";
        $num_of_bytes = 0;
        $secret_key = 'X2gSgOxlN789'. $this->val('aes_suffix'). '4KsPEqvBJjXjqGVbgg8Q';
        $secret_iv = '0U0yd6krWQFuHG1G';
        // hash
        $key = hash('sha256', $secret_key);
        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        if($action == '1' ){ //encrypt
            $output_filename = $filename.'.enc';
            /** @noinspection EncryptionInitializationVectorRandomnessInspection */
            $num_of_bytes = file_put_contents($output_filename, openssl_encrypt(file_get_contents($filename), 'AES-256-CBC', $key, 0, $iv));
        } elseif($action == '2' && substr($filename,-4) === '.enc'){//decrypt only if filename ends with .enc
            $output_filename = str_replace('.enc','', $filename);
            $num_of_bytes = file_put_contents($output_filename, openssl_decrypt(file_get_contents($filename), 'AES-256-CBC', $key, 0, $iv));
        }
        if ($num_of_bytes) {
            unlink($filename);
            return $output_filename;
        } else {
            return '';
        }
    }

    public static function UpdateZoneCalendarPageBannerTitle(string $calendar_page_banner_title)
    {
        global $_COMPANY;
        global $_ZONE;

        return self::DBUpdatePS("UPDATE `company_zones` SET `calendar_page_banner_title`=? WHERE companyid=? AND zoneid=? ",'sii',$calendar_page_banner_title,$_COMPANY->id(),$_ZONE->id());
    }

    public static function UpdateZoneAdminContentPageTitle(string $admin_content_page_title)
    {
        global $_COMPANY;
        global $_ZONE;

        return self::DBUpdatePS("UPDATE `company_zones` SET `admin_content_page_title`=? WHERE companyid=? AND zoneid=? ",'sii',$admin_content_page_title,$_COMPANY->id(),$_ZONE->id());
    }

    /**
     * Returns all the zone ids which have intersecting regions
     * @param int $subject_zoneid the source zoneid
     * @return array of zoneids that have interescting regions. Or empty array.
     */
    public function getIntersectingZoneIds(int $subject_zoneid): array
    {
        $zones = $this->getZones();
        $subject_zone = $this->getZone($subject_zoneid);
        $retVal = array();

        $my_regionids = explode(',', $subject_zone->val('regionids'));
        foreach ($zones as $zone) {
            if ($zone['zoneid'] == $subject_zoneid) {
                continue; // Check to not find groups in my zone.
            }
            $zone_region_ids = explode(',', $zone['regionids']);
            if (!empty(array_intersect($my_regionids, $zone_region_ids))) {
                // If the zones have matching regionids
                $retVal[] = $zone['zoneid'];
            }
        }
        return $retVal;
    }

    public function getHotlinks(bool $activeOnly = true)
    {
        global $_ZONE, $_USER;

        $key = "HOTLINK:{$_ZONE->id()}";
        if (($value = $this->getFromRedisCache($key)) === false) {
            $hotlinkPriority = self::DBGet("SELECT `priority` FROM `hot_link` WHERE `companyid`={$this->id()} AND zoneid={$_ZONE->id()} ORDER BY `link_id` ASC LIMIT 1");
            $order = "";
            if (count($hotlinkPriority)) {
                $hotlinkPriority = $hotlinkPriority[0]['priority'];
                if ($hotlinkPriority) {
                    $order = " ORDER BY FIELD(link_id,{$hotlinkPriority})";
                } else {
                    $order = " ORDER BY link_id ASC";
                }
            }
            $value = self::DBGet("SELECT `link_id`, `companyid`, `userid`, `title`, `alternate_name`, `image`, `link_type`, `link`, `priority`, `updated_at`, `is_active` FROM `hot_link` WHERE `companyid`={$this->id()} AND zoneid={$_ZONE->id()}" . $order);
            $this->putInRedisCache($key, $value, 86400);
        }

        if ($activeOnly) {
            return array_filter($value, function ($v) {
                return ($v['is_active'] == 1);
            });
        }

        return $value;
    }

    public function getHotlink (int $linkid) {
        $hotlinks = $this->getHotlinks(false);
        return array_values(array_filter($hotlinks,function ($value) use ($linkid) {
            return ($value['link_id'] == $linkid);
        }));
    }

    public function createHotlink(string $title, string $alternate_name, string $image, int $link_type, string $link)
    {
        global $_COMPANY, $_ZONE, $_USER;
        self::DBInsertPS("INSERT INTO `hot_link`(`companyid`, `zoneid`, `userid`, `title`, `alternate_name`, `image`, `link_type`, `link`, `updated_at`, `is_active`) VALUES (?,?,?,?,?,?,?,?,NOW(),0)", 'iiixxxix', $this->id(), $_ZONE->id(), $_USER->id(), $title, $alternate_name, $image, $link_type, $link);
        $_COMPANY->expireRedisCache("HOTLINK:{$_ZONE->id()}"); // Expire all hotlinks in the zone.
    }

    public function updateHotlink(int $link_id, string $title, string $alternate_name, string $image, int $link_type, string $link)
    {
        global $_COMPANY, $_ZONE, $_USER;
        self::DBUpdatePS("UPDATE `hot_link` SET `userid`=?,`title`=?,`alternate_name`=?,`image`=?,`link_type`=?,`link`=?,`updated_at`=NOW() WHERE link_id=? AND companyid=? AND zoneid=?", 'ixxxixiii', $_USER->id(), $title, $alternate_name, $image, $link_type, $link, $link_id, $this->id, $_ZONE->id());
        $_COMPANY->expireRedisCache("HOTLINK:{$_ZONE->id()}"); // Expire all hotlinks in the zone.
    }

    public function updateHotLinkStatus (int $link_id, int $status){
        global $_COMPANY, $_ZONE, $_USER;
        $retVal = self::DBMutate("UPDATE hot_link SET  `is_active`={$status}, `userid`={$_USER->id()}, `updated_at`=NOW() WHERE companyid={$this->id} AND link_id={$link_id}");
        $_COMPANY->expireRedisCache("HOTLINK:{$_ZONE->id()}"); // Expire all hotlinks in the zone.
        return $retVal;
    }

    public function updateHotlinkPriority (string $priority) {
        global $_COMPANY, $_ZONE, $_USER;
        self::DBMutatePS("UPDATE hot_link SET `priority`=?,`updated_at`=NOW() WHERE `companyid`=? AND zoneid=?",'xii',$priority,$_COMPANY->id(), $_ZONE->id());
        $_COMPANY->expireRedisCache("HOTLINK:{$_ZONE->id()}"); // Expire all hotlinks in the zone.
    }

    public function deleteHotlink (int $link_id) {
        global $_COMPANY, $_ZONE, $_USER;
        self::DBMutate("DELETE FROM `hot_link` WHERE `link_id`={$link_id} AND `companyid`={$_COMPANY->id()} AND `zoneid`={$_ZONE->id()}");
        $_COMPANY->expireRedisCache("HOTLINK:{$_ZONE->id()}"); // Expire all hotlinks in the zone.
    }

    /**
     * @return Redis|RedisCluster
     * @throws RedisClusterException
     */
    private function GetRedis () {
        if (Config::Get('REDIS_WEBCACHE_HOST')) {
            if (Config::Get('REDIS_WEBCACHE_ISCLUSTER')) {
                $redis = new RedisCluster(NULL, array(Config::Get('REDIS_WEBCACHE_HOST').':'.Config::Get('REDIS_WEBCACHE_PORT')), 1.5, 1.5, true);

            } else {
                $redis = new Redis();
                $redis->connect(Config::Get('REDIS_WEBCACHE_HOST'),Config::Get('REDIS_WEBCACHE_PORT'));
            }
            return $redis;
        }
        return null;
    }

    /**
     * @param string $key
     * @return false|mixed
     */
    public function getFromRedisCache (string $key)
    {
        try {
            if ($redis = self::GetRedis()) {
                $key = $this->id.'::'.$key;
                return unserialize($redis->get($key));
            }
        } catch (Exception $e) {
            Logger::Log("Caught Redis Read Exception " . $e);
        }
        return false;
    }

    /**
     * @param string $key
     * @param $value
     * @param int $ttl
     * @return bool
     */
    public function putInRedisCache (string $key, $value, int $ttl=90)
    {
        try {
            if ($redis = self::GetRedis()) {
                $key = $this->id.'::'.$key;
                $redis->set($key, serialize($value));
                $redis->expire($key, $ttl);
                return true;
            }
        } catch (Exception $e) {
            Logger::Log("Caught Redis Write Exception " . $e);
        }
        return false;
    }

    public function incrRedisCache (string $key, int $incrBy = 1)
    {
        try {
            if ($redis = self::GetRedis()) {
                $key = $this->id.'::'.$key;
                $redis->incrBy($key, $incrBy);
                return true;
            }
        } catch (Exception $e) {
            Logger::Log("Caught Redis Write Exception " . $e);
        }
        return false;
    }

    public function decrRedisCache (string $key, int $decrBy = 1)
    {
        try {
            if ($redis = self::GetRedis()) {
                $key = $this->id.'::'.$key;
                $redis->decrBy($key, $decrBy);
                return true;
            }
        } catch (Exception $e) {
            Logger::Log("Caught Redis Write Exception " . $e);
        }
        return false;
    }

    public function expireRedisCache (string $key, int $ttl = 0)
    {
        try {
            if ($redis = self::GetRedis()) {
                $key = $this->id.'::'.$key;
                $redis->expire($key, $ttl);
                return true;
            }
        } catch (Exception $e) {
            Logger::Log("Caught Redis Expire Exception " . $e);
        }
        return false;
    }
    /**
     * Create or Update Group lead type
     */
    public function createOrUpdateGroupLeadType(int $typeid, string $type, int $sys_leadtype,string $welcome_message, int $allow_create_content, int $allow_publish_content, int $allow_manage, int $allow_manage_budget, int $show_on_aboutus, int $allow_manage_grant, int $allow_manage_support) {
        global $_ZONE, $_USER;
		if ($typeid>0){
           return self::DBUpdatePS("UPDATE `grouplead_type` SET `type`=?,`welcome_message`=?,`modifiedon`=NOW(),allow_create_content=?,allow_publish_content=?,allow_manage=?, show_on_aboutus=?,allow_manage_budget=?, allow_manage_grant = ?, allow_manage_support = ? WHERE companyid=? AND typeid=?",'xxiiiiiiiii',$type,$welcome_message,$allow_create_content,$allow_publish_content,$allow_manage,$show_on_aboutus,$allow_manage_budget, $allow_manage_grant, $allow_manage_support, $this->id,$typeid);
        } else {
            return self::DBInsertPS("INSERT INTO `grouplead_type`(`sys_leadtype`, `companyid`, `zoneid`, `type`,`welcome_message`,`modifiedon`, `isactive`,allow_create_content,allow_publish_content,allow_manage,show_on_aboutus,allow_manage_budget, allow_manage_grant,allow_manage_support) VALUES (?,?,?,?,?,NOW(),?,?,?,?,?,?,?,?)",'iiixxiiiiiiii',$sys_leadtype,$this->id,$_ZONE->id(),$type,$welcome_message,1,$allow_create_content,$allow_publish_content,$allow_manage,$show_on_aboutus,$allow_manage_budget, $allow_manage_grant,$allow_manage_support);
        }
	}

    public function getGroupLeadType(int $typeid) {
        global $_ZONE, $_USER;
		$leadType = null;

        $l = self::DBGet("SELECT * FROM grouplead_type WHERE companyid={$this->id} AND typeid={$typeid}");
        if(!empty($l)){
            $leadType = $l[0];
        }
        return $leadType;
	}

    /**
     * Saves file in the safe area.
     * @param string $src_file
     * @param string $dest_name
     * @param string $s3_area, should match one of the Company::S3_SAFE_AREA values
     * @return string
     */
    public function saveFileInCommentsArea(string $src_file, string $dest_name, string $s3_area): string {

        $retVal = "";
        global $_ZONE;

        //Instantiate the client.
        $s3 = Aws\S3\S3Client::factory([
            'version' => 'latest',
            'region' => S3_REGION

        ]);

        $dest_name = basename($dest_name); //Extract filename without any subfolder fragments seperated by
        $folder = self::S3_COMMENTS_AREA[$s3_area] ?? '';

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $contentType = $finfo->file($src_file);

        //Build the name where to store the file
        if (empty($dest_name) || empty($folder))
            return $retVal;

        $s3name = $this->val('s3_folder').$folder.$dest_name;

        $folder .= $_ZONE->id().'/';

        try{
            $s3->putObject([
                'Bucket'=>S3_COMMENT_BUCKET,
                'Key'=>$s3name,
                'Body'=>fopen($src_file,'rb'),
                'ContentType' => $contentType
            ]);
            $retVal = $s3name;
        }catch(Exception $e){
            Logger::Log("Caught Exception in Company->saveFileInSafe while uploading {$src_file} as {$dest_name} to s3 {$s3name}");
        }
        return $retVal;
    }

    /**
     * Delete the specified file from safe area
     * @param string $filename
     * @param string $s3_area
     * @return bool
     */
    public function deleteFileFromCommentsArea(string $filename, string $s3_area) {

        $retVal = false;

        //Instantiate the client.
        $s3 = Aws\S3\S3Client::factory([
            'version' => 'latest',
            'region' => S3_REGION

        ]);

        $dest_name = basename($filename);
        $folder = self::S3_COMMENTS_AREA[$s3_area] ?? '';

        if (empty($dest_name) || empty($folder))
            return $retVal;

        $s3name = $this->val('s3_folder').$folder.$dest_name;

        try {
            $s3->deleteObject([
                'Bucket' => S3_COMMENT_BUCKET,
                'Key' => $s3name
            ]);
            $retVal = true;
        }catch(\Exception $e){
            Logger::Log("Fatal Error in Company->deleteFileFromSafe({$s3name}) ".$e->getMessage(), Logger::SEVERITY['FATAL_ERROR']);
        }
        return $retVal;
    }
    /**
     * Get the specified file from safe area
     * @param string $filename
     * @param string $s3_area
     * @return array || null
     */
    public function getFileFromCommentsArea(string $filename, string $s3_area) {

        $result = null;

        $s3 = Aws\S3\S3Client::factory([
            'version' => 'latest',
            'region' => S3_REGION
        ]);

        $dest_name = basename($filename);
        $folder = self::S3_COMMENTS_AREA[$s3_area] ?? '';

        if (empty($dest_name) || empty($folder))
            return $result;

        $obj_name = $this->val('s3_folder') . $folder . $dest_name;

        try {
            $result = $s3->getObject([
                'Bucket' => S3_COMMENT_BUCKET,
                'Key' => $obj_name
            ]);
        } catch (Exception $e) {
            Logger::Log("{$e}");
        }

        return $result;
    }

    public function encryptArray2String (array $in): string
    {
        // Do not change the keys
        $aes_key = substr(TELESKOPE_GENERIC_KEY,12,20) . $this->val('aes_suffix');
        return aes_encrypt(json_encode($in), $aes_key, '6lDC9nhju2Tm6nOV1D2ldSai3kIyKEGDfmdYJIhT', false, true);
    }

    public function decryptString2Array (string $in): array
    {
        // Do not change the keys
        $aes_key = substr(TELESKOPE_GENERIC_KEY,12,20) . $this->val('aes_suffix');
        $decrypted_value = aes_encrypt($in, $aes_key, '6lDC9nhju2Tm6nOV1D2ldSai3kIyKEGDfmdYJIhT', true);
        return json_decode($decrypted_value,true) ?? array();
    }

    public function getGenericHash (string $val): string
    {
        return md5($val.TELESKOPE_GENERIC_SALT);
    }

    public function validateGenericHash (string $val, string $hash) : bool
    {
        return md5($val.TELESKOPE_GENERIC_SALT) == $hash;
    }

    public function getTemplates (int $activeOnly = 0): array
    {
        global $_ZONE;
        $activeCondition = "";
        if ($activeOnly){
            $activeCondition = " AND `isactive`='1'";
        }
        return self::DBGet("select * FROM templates WHERE `companyid`='{$this->id()}' AND `zoneid`='{$_ZONE->id()}' {$activeCondition}");
    }

    public function getTemplateDetail (int $templateid)
    {
        global $_ZONE;
        $row = null;
        $t =  self::DBGet("select * FROM templates WHERE `companyid`='{$this->id()}' AND `zoneid`='{$_ZONE->id()}' AND `templateid`={$templateid}");
        if (!empty($t)){
            $row = $t[0];
        }
        return $row;
    }

    public function addUpdateTemplate(int $templateid,string $templatename, int $templatetype,string $template){
        global $_ZONE,$_USER;
        if ($templateid) {
            self::DBUpdatePS("UPDATE `templates` SET `templatename`=?,`templatetype`=?,`template`=?,`createdby`=?,`modifiedon`=NOW() WHERE `companyid`=? AND `templateid`=?","xixiii",$templatename,$templatetype,$template,$_USER->id(),$this->id(),$templateid);
        } else {
            $templateid = self::DBInsertPS("INSERT INTO `templates`(`companyid`, `zoneid`, `templatename`, `templatetype`, `template`, `createdby`, `createdon`, `modifiedon`, `isactive`) VALUES (?,?,?,?,?,?,NOW(),NOW(),2)","iixixi",$this->id(),$_ZONE->id(),$templatename,$templatetype,$template,$_USER->id());
        }
        return $templateid;
    }

    public function updateTemplateStatus(int $templateid, int $status){
       return self::DBUpdatePS("UPDATE `templates` SET `isactive`=?,`modifiedon`=NOW() WHERE `companyid`=? AND `templateid`=?","iii",$status,$this->id(),$templateid);
    }

    public function getCurrency(int $zoneid): string
    {
        return $this->getAppCustomizationForZone($zoneid)['budgets']['currency'] ?: 'USD';
    }

    /**
     * GET Currency Symbol
     */
    public function getCurrencySymbol(?int $zoneid = null)
    {
        global $_ZONE;
        $zoneid = $zoneid ?? $_ZONE->id();
        $currency = $this->getCurrency($zoneid);
        $locale = $this->getAppCustomizationForZone($zoneid)['budgets']['locale'] ?? 'en_US';
        $fmt = new NumberFormatter($locale . "@currency=$currency", NumberFormatter::CURRENCY);
        //header("Content-Type: text/html; charset=UTF-8;");
        return $fmt->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    }

    public function emailSendExternal(string $from_name, ?string $to_addr, string $subject, string $message, string $app_type,string $reply_addr = '', string $ical_str = '', array $attachments = array(),string $cc = ''):bool
    {
        global $_LOGGER_META_MAIL;
        // Initialize Logger Meta data
        $masked_toAddr = implode(',', array_map([$this,'getEmailMask'],explode(',', $to_addr)));
        $_LOGGER_META_MAIL = [
            'fromName' => $from_name,
            'fromAddr' => '',
            'toAddr' => $masked_toAddr,
            'subject' => $subject,
            'attachments' => count($attachments),
            'ical' => !empty($ical_str),
        ];

        // Since this method can be called by function where $_ZONE is not set,
        // this method uses temporary variable zoneObject instead of global variable $_ZONE.
        // Don't declare $_ZONE

        $zoneObject = $GLOBALS["_ZONE"] ?? $this->getEmptyZone('teleskope');

        //file_put_contents('/var/tmp/mail.html',$message);return true;
        if (empty($to_addr)  && empty($cc)) {
            Logger::Log('Email - Skipped, empty address');
            return true; // Since empty email address is valid usecase, we will return true.
        }

        if ($zoneObject->val('email_settings') < 1) {
            Logger::Log('Email - Skipped, send email disabled');
            return false; //Email is disabled for this company
        }

        if (empty($from_name))
            $from_name= $zoneObject->val('email_from_label') ?: FROM_NAME; //Php 7 Elvis operator

        $this->getEmailSettings(); // Load Email settings

        if (($this->emailSettings['custom_smtp'])) {
            $from_addr = $zoneObject->val('email_from') ?: $this->emailSettings['smtp_from_email']; // *** Force the from address set for custom smtp ***
        } else {
            $from_addr = $this->getFromEmailAddr($app_type);
        }

        $_LOGGER_META_MAIL['fromAddr'] = $from_addr;

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->XMailer = "Teleskope mailer";
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            //Server settings
            $mail->SMTPDebug = false;                                   // Donot print any debug info
            $mail->isSMTP();                                            // Set mailer to use SMTP
            $mail->Host = $this->emailSettings['smtp_host'];            // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                                     // Enable SMTP authentication
            $mail->Username = $this->emailSettings['smtp_username'];    // SMTP username
            $mail->Password = $this->emailSettings['smtp_password'];    // SMTP password
            $mail->SMTPSecure =  $this->emailSettings['smtp_secure'];   // Enable TLS encryption, `ssl` also accepted
            $mail->Port = $this->emailSettings['smtp_port'];            // TCP port to connect to

            if (!empty ($reply_addr)) { // First check if reply address was provided, e.g. group has reply address
                $mail->addReplyTo($reply_addr, $from_name);
            } elseif (!empty($zoneObject->val('email_replyto'))) { // Next check if default reply to is provided at the zone level
                $mail->addReplyTo($zoneObject->val('email_replyto'), $from_name);
            } elseif (!empty($this->emailSettings['smtp_replyto_email'])) { // Or if one is provided as part of SMTP settings
                $mail->addReplyTo($this->emailSettings['smtp_replyto_email'], 'Teleskope');
            }

            //Recipients
            $mail->setFrom($from_addr, $from_name);


            $tos = explode(',',$to_addr);
            foreach ($tos as $toi) {
                if (!empty($toi) && filter_var($toi, FILTER_VALIDATE_EMAIL)) { // Domain check to send emails only to domains that are allowed.
                    $mail->addAddress($toi);
                } else {
                    Logger::Log('Email - Skipped, invalid email', Logger::SEVERITY['INFO']);
                }
            }

            // CC
            if (!empty($cc)){
                $masked_ccAddr = implode(',', array_map([$this,'getEmailMask'],explode(',', $cc)));
                $_LOGGER_META_MAIL['ccAddr'] = $masked_ccAddr;
                $cc_array  = explode(',',$cc);
                foreach ($cc_array as $c) {
                    if (!empty($c) && filter_var($c, FILTER_VALIDATE_EMAIL)) { // Domain check to send cc emails only to domains that are allowed.
                        $mail->addCC($c);
                    } else {
                        Logger::Log('Email - Skipped for CC : ' . $c, Logger::SEVERITY['INFO']);
                    }
                }
            }

            //Content
            $mail->Subject = $subject;
            $mail->isHTML(true);                   // Set email format to HTML
            $mail->Body = $message;
            $mail->AltBody = "This is a HTML message only";

            // For testing purposes
            //file_put_contents('/tmp/email.html', $message);
            //return true;

            if (!empty($ical_str)) {
                $mail->Ical = $ical_str;
                //$mail->addStringAttachment($ical_str,'invite.ics','base64','text/calendar');
            }

            foreach ($attachments as $attach) {
                $mail->addStringAttachment($attach['content'],$attach['filename']);
            }

            $max_retries = 5;
            $current_try = 0;
            while ($current_try++ < $max_retries) {
                try {
                    if ($mail->send()) {
                        $current_try = $max_retries;
                        Logger::Log('Email - Sent', Logger::SEVERITY['INFO']);
                        return true;
                    } else {
                        Logger::Log('Email - Fatal Mailer Error for ' . $masked_toAddr . ' : Unknown Error during attempt ' . $current_try, Logger::SEVERITY['FATAL_ERROR']);
                    }
                } catch (Exception $sendException) {
                    if (strpos($sendException->getMessage(),'SMTP ') === 0) {
                        // SMTP errors handled are like of SMTP Error, SMTP connect
                        $retry_interval = $current_try * 2 + rand($current_try,$current_try+5);
                        Logger::Log('Email - Retry after: ' .$retry_interval . 's due to exception ' . $sendException->getMessage(), Logger::SEVERITY['WARNING_ERROR']);
                        sleep($retry_interval);
                    } else {
                        throw ($sendException);
                    }
                }
            }
            Logger::Log('Email - Quitting : Exhausted all retries', Logger::SEVERITY['FATAL_ERROR']);
            return false;
        } catch (Exception $e) {
            $severity = Logger::SEVERITY['FATAL_ERROR'];
            if (
                str_contains($e->getMessage(), 'must provide at least one recipient email address')
                ||
                str_contains($e->getMessage(), 'Invalid address:')
            ) {
                $severity = Logger::SEVERITY['WARNING_ERROR'];
            }
            Logger::Log('Email - Mailer Error: ' . $e->getMessage(), $severity);
            return false;
        } finally {
            $_LOGGER_META_MAIL = null;
        }
    }

    /**
     * Encrypts the string with provided PGP key
     * @param string $body
     * @param string $keyname
     * @return string return empty string if encryption fails
     */
    private function encryptWithPGP(string $body, string $keyname): string
    {
        // Check include/README on how to ensure gnupg is installed and public/private keys are loaded
        Env::Put("GNUPGHOME=/opt/www/.gnupg");
        $gpg = new gnupg();
        $gpg->seterrormode(gnupg::ERROR_EXCEPTION);
        $info = $gpg->keyinfo($keyname);
        $gpg->addencryptkey($info[0]['subkeys'][0]['fingerprint']);
        $gpg->setarmor(0);
        return $gpg->encrypt($body) ?: '';
    }

    /**
     * Decrypts the string with provided PGP key
     * @param string $body
     * @param string $keyname
     * @return string return empty string if decryption fails
     */
    public function decryptWithPGP(string $body, string $keyname): string
    {
        if (!$body)
            return '';
        // Check include/README on how to ensure gnupg is installed and public/private keys are loaded
        Env::Put("GNUPGHOME=/opt/www/.gnupg");
        $gpg = new gnupg();
        $gpg->seterrormode(gnupg::ERROR_EXCEPTION);
        $info = $gpg->keyinfo($keyname);
        $gpg->adddecryptkey($info[0]['subkeys'][0]['fingerprint'], null);
        return $gpg->decrypt($body) ?: '';
    }

    public function deleteCompanyBranch(int $branchid){
        global $_ZONE;
        if (self::DBMutate("DELETE FROM `companybranches` WHERE `companyid`='{$this->id()}' AND `branchid`='{$branchid}'")){
            self::DBMutate("UPDATE `users` SET `homeoffice`='0' WHERE `homeoffice`='{$branchid}' AND `companyid`='{$this->id()}'");
            self::DBMutate("UPDATE `chapters` SET `branchids` = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', `branchids`, ','), ',{$branchid},', ',')) WHERE chapters.companyid='{$this->id()}' AND ( chapters.`zoneid`='{$_ZONE->id()}' AND FIND_IN_SET('{$branchid}', `branchids`))");
            return 1;
        }
        return 0;
    }

    public function deleteCompanyDepartment(int $departmentid){
        global $_ZONE;
        if (self::DBMutate("DELETE FROM `departments` WHERE `companyid`='{$this->id()}' AND `departmentid`='{$departmentid}'")){
            self::DBMutate("UPDATE `users` SET `department`='0' WHERE `companyid`='{$this->id()}' AND `department`='{$departmentid}'");
            return 1;
        }
        return 0;
    }

    public function updateCompanyBrandingMedia(string $logo, string $loginscreen_background){
        global $_ZONE;

        return self::DBMutate("UPDATE `companies` SET `logo`='{$logo }',`loginscreen_background`='{$loginscreen_background}', `modified`=NOW() WHERE `companyid`='{$this->id()}' ");
    }

    public function updateCompanyMyEventsMedia(string $my_events_background){
        global $_ZONE;

        return self::DBMutate("UPDATE `companies` SET `my_events_background`='{$my_events_background }',`modified`=NOW() WHERE `companyid`='{$this->id()}'");
    }

    public function updateCompanyZoneBrandingMedia(string $banner_background){
        global $_ZONE;

        return self::DBMutate("UPDATE `company_zones` SET `banner_background`='{$banner_background }',`modifiedon`=NOW() WHERE `companyid`='{$this->id()}' AND `zoneid`='{$_ZONE->id()}'");
    }

    /**
     * Updates company zone banner image
     * @param string $zone_banner_image
     * @return int
     */
    public function updateCompanyZoneBannerImage(string $zone_banner_image)
    {
        return self::DBMutatePS("UPDATE `companies` SET `zone_banner_image`=?,`modified`=NOW() WHERE `companyid`={$this->id()}", 'x', $zone_banner_image);
    }

    public function updateCompanyZoneSetting(int $show_group_overlay,string $group_landing_page){
        global $_ZONE;
        return self::DBMutate("UPDATE `company_zones` SET `show_group_overlay`='{$show_group_overlay }',`group_landing_page`='{$group_landing_page}',`modifiedon`=NOW() WHERE `companyid`='{$this->id()}' AND `zoneid`='{$_ZONE->id()}'");
    }

    public function updateCompanyInformation(string $companyname, string $contactperson, string $contact, string $address, string $city, string $state, string $country, string $zipcode){

        return self::DBMutatePS("UPDATE `companies` SET `companyname`=?,`contactperson`=?,`contact`=?, `address`=?,`city`=?,`state`=?,`country`=?,`zipcode`=?, `modified`=now() WHERE companyid=?" , "xxxxxxxxi", $companyname, $contactperson, $contact, $address, $city, $state, $country, $zipcode,$this->id() );
    }


    public function copyS3File(string $src_file, string $dest_name, string $s3_area) {
		//Instantiate the client.
        global $_ZONE;

		$s3 = Aws\S3\S3Client::factory([
            'version' => 'latest',
            'region' => S3_REGION

		]);

		$retVal = "";
        $dest_name = basename($dest_name); //Extract filename without any subfolder fragments seperated by
        $folder = self::S3_AREA[$s3_area] ?? '';


        //Build the name where to store the file
        if (empty($dest_name) || empty($folder))
            return $retVal;

        if (!($s3_area === 'USER' || $s3_area === 'TEMPLATE' || $s3_area === 'COMPANY' || $s3_area === 'USER_BIO'))
            $folder .= $_ZONE->id().'/';

        $s3name = $this->val('s3_folder').$folder.$dest_name;

        $src_file_parts = parse_url($src_file);
        $src_file_path = $src_file_parts['path'];

        try {
            $s3->copyObject([
                'Bucket' => S3_BUCKET,
                'CopySource' => S3_BUCKET.$src_file_path,
                'Key' => $s3name,
			    'ACL'=>'public-read',
            ]);
            $retVal = "https://".S3_BUCKET.".s3.amazonaws.com/".$s3name;
        } catch (Exception $exception) {
            Logger::Log("Caught Exception in Company->copyS3File while copying {$src_file} as {$dest_name} to s3 {$s3name} - error {$exception->getMessage()}");
        }
		return $retVal;
	}

    public function getWhyCannotDeleteIt(): string
    {
        if (!Env::IsSuperAdminDashboard()) {
            return ('Access Denied');
        }

        if ($this->val('isactive') != 2) {
            return ('You need to block the company before deleting it');
        }

        $zones = $this->fetchAllZonesFromDB();

        if (count($zones)) {
            return ('Please delete all zones in this company first');
        }

        $login_methods = $this->getLoginMethods();

        if (count($login_methods)) {
            return ('Please delete all login methods in this company first');
        }

        $jobs = $this->getScheduledJobs();

        $jobs = array_filter($jobs, function (array $job) {
            return (int) $job['status'] !== Job::STATUS_PROCESSED;
        });

        if (count($jobs)) {
            return ('Please delete all jobs in this company first');
        }

        $admin_users = $this->getAdminUsers();

        if (count($admin_users)) {
            return ('Please delete all admin users of this company first');
        }

        $regions = $this->getAllRegions();

        if (count($regions)) {
            return ('Please delete all regions of this company first');
        }

        $user_count = self::DBROGet("SELECT count(1) AS CC FROM users where companyid={$this->id()}")[0]['CC'];
        if ($user_count) {
            return ('Please delete all users of this company first');
        }

        // @Todo We need to implement deep deletion of company until then we will deliberately fail deletion
        // see delete_company.sql for deep deletion.
        return ('Deep deletion is pending');

        //return '';
    }

    public function deleteIt(): bool
    {
        global $_COMPANY;

        if ($this->getWhyCannotDeleteIt()) {
            return false;
        }

        self::LogObjectLifecycleAudit('delete', 'company', $this->id(), 0);

        self::DBMutate("DELETE FROM `companies` WHERE `companyid` = {$this->id()}");

        return true;
    }

    public function getLoginMethods(): array
    {
        return self::DBROGet("SELECT * FROM `company_login_settings` WHERE `companyid`= {$this->id()} ORDER BY `scope`");
    }

    public function getScheduledJobs(): array
    {
        $data1 = Job::GetJobsByType($this->id(), Job::TYPE_USERSYNC_FILE);
        $data2 = Job::GetJobsByType($this->id(), Job::TYPE_DATA_IMPORT);
        $data3 = Job::GetJobsByType($this->id(), Job::TYPE_DATA_EXPORT);
        return array_merge($data1, $data2, $data3);
    }

    public function getAdminUsers(): array
    {
        return self::DBROGet("SELECT * FROM company_admins JOIN users USING (userid) where company_admins.companyid={$this->id()} and users.accounttype > 1 and users.isactive=1");
    }


    /**
     * @return array|string[]
     */
    public function getFirebaseBearerTokenAndProjectId(): array
    {
        $key = "FBBTKN:{$this->id()}";

        if (($firebase_token_and_project_id = $this->getFromRedisCache($key)) === false) {
            $firebase_token_and_project_id = ['token' => '', 'projectid' => ''];
            // Load the service account credentials
            $credentials = json_decode(base64_decode(Config::Get('FIREBASE_SERVICE_KEY_BASE64') ?? ''), true);
            if (empty($credentials)) {
                return ['', ''];
            }
            $firebaseProjectId = $credentials['project_id'];

            // Get the JWT token from the service account file
            $now = time();
            $jwtHeader = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
            $jwtPayload = json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/cloud-platform',
                'aud' => $credentials['token_uri'],
                'iat' => $now,
                'exp' => $now + 3600,
            ]);

            $jwtBase64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($jwtHeader));
            $jwtBase64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($jwtPayload));

            $jwtSignatureInput = $jwtBase64UrlHeader . '.' . $jwtBase64UrlPayload;
            $jwtSignature = '';
            openssl_sign($jwtSignatureInput, $jwtSignature, openssl_pkey_get_private($credentials['private_key']), 'SHA256');
            $jwtBase64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($jwtSignature));

            $jwt = $jwtSignatureInput . '.' . $jwtBase64UrlSignature;

            // Exchange JWT for an OAuth 2.0 token
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $credentials['token_uri']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]));

            $response = curl_exec($ch);
            curl_close($ch);

            $token = json_decode($response, true)['access_token'];
            if (!empty($token)) {
                $firebase_token_and_project_id = ['token' => $token, 'projectid' => $firebaseProjectId];
                $this->putInRedisCache($key, $firebase_token_and_project_id, 3600);
            }
        }

        return [
            $firebase_token_and_project_id['token'],
            $firebase_token_and_project_id['projectid']
        ];
    }

    public function getExternalAdminRoles(string $name): array
    {
        $security = $this->getCompanySecurity();
        return array_filter(array_map('trim', explode(',', $security[$name] ?? '')));
    }

    /**
     * @param string $configuration_key, the configuration key can be in dot notation.
     * @return mixed
     */
    public function getCompanyAttributesKeyVal(string $configuration_key) : mixed
    {
        if (!$configuration_key)
            return null;

        $configuration_key_arr = explode('.', $configuration_key);
        if (empty($configuration_key))
            return null;

        $attributes = Arr::Json2Array($this->val('attributes'));

        foreach ($configuration_key_arr as $configuration_key_item) {
            if ($attributes && isset($attributes[$configuration_key_item])) {
                $attributes = $attributes[$configuration_key_item];
            } else {
                $attributes = null;
                break;
            }
        }

        return $attributes;
    }
    /**
     * Sets the key in attributes JSON column. The key can be in the dot notation, e.g. emails.approvals
     * ***** If $configuration_val is null, the the value is removed if it exists *****
     * @param string $configuration_key
     * @param mixed $configuration_val
     * @return int
     */
    public function updateCompanyAttributesKeyVal (string $configuration_key, mixed $configuration_val): int
    {
        global $_USER;
        $retVal = 0;

        // First construct a $configuration_array
        $configuration_array = array($configuration_key => $configuration_val);
        $json_doc = json_encode(Arr::Undot($configuration_array)); // $configuration_key may be in dot notation
        //$sql = "UPDATE configuration SET keyvals = JSON_MERGE_PATCH(keyvals, '{$." . implode(".$", $keys) . "': $value}')";
        $json_doc = json_encode(Arr::Undot(array($configuration_key=>$configuration_val)));
        $retVal = self::DBMutatePS("
            UPDATE `companies` 
            SET attributes=JSON_MERGE_PATCH(IFNULL(attributes,JSON_OBJECT()), ?)
            WHERE companyid=?",
            'xi',
            $json_doc, $this->id()
        );

        if ($retVal) {
            Company::GetCompany($this->id(), true); // Reload cache
            self::LogObjectLifecycleAudit('update', 'company', $this->id(), 0, $configuration_array);
        }

        return true;
    }

    public function updateCompanySettings(array $customization): int
    {
        $updated_customization = Arr::Minify($customization, self::DEFAULT_COMPANY_SETTINGS);

        $retVal = self::DBUpdatePS(
            "UPDATE `companies` SET `customization` = ?, `modified` = NOW() WHERE companyid = ?",
            'xi',
            json_encode($updated_customization, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
            $this->id()
        );

        Logger::AuditLog("config_change: new company config ", $updated_customization);

        Company::GetCompany($this->id(), true);

        return $retVal;
    }
}
