<?php
require_once __DIR__ . '/Integration.php';
require_once __DIR__ . '/externaltype/FbWorkplaceIntegrationExternalType.php';
require_once __DIR__ . '/externaltype/YammerIntegrationExternalType.php';
require_once __DIR__ . '/externaltype/SlackIntegrationExternalType.php';
require_once __DIR__ . '/externaltype/TeamsIntegrationExternalType.php';
require_once __DIR__ . '/externaltype/GoogleChatsIntegrationExternalType.php';
/**
 * This class encapsulates everything needs to integrate Teleskope group with external systems e.g. FB Workplace or
 * Teams. Teleskope Group can publish Events, Announcements or Newsletters to external systems. What gets published
 * is dependent upon the integration type.
 * Class GroupIntegration
 */
class GroupIntegration extends Integration
{
    const ALLOW_ALL = array('events' => true, 'post' => true, 'newsletter' => true);

    protected $groupid;
    protected $chapterid;
    protected $channelid;
    protected $external_type_obj;

    protected function __construct(int $id, int $cid, array $fields)
    {
        parent::__construct($id, $cid, $fields);
        $toks = explode('_', $this->integration_topic);
        if (count($toks) >= 4)
            $this->groupid = (int)$toks[1];
        $this->chapterid = (int)$toks[2];
        $this->channelid = (int)$toks[3];

        if ($this->external_type === self::EXTERNAL_TYPES['workplace']) {
            $this->external_type_obj = new FbWorkplaceIntegrationExternalType($this->integration_arr);
        } elseif ($this->external_type === self::EXTERNAL_TYPES['yammer']) {
            $this->external_type_obj = new YammerIntegrationExternalType($this->integration_arr);
        } elseif($this->external_type === self::EXTERNAL_TYPES['slack']){
            $this->external_type_obj = new SlackIntegrationExternalType($this->integration_arr);
        }elseif($this->external_type === self::EXTERNAL_TYPES['teams']){
            $this->external_type_obj = new TeamsIntegrationExternalType($this->integration_arr);
         }elseif($this->external_type === self::EXTERNAL_TYPES['googlechat']){
            $this->external_type_obj = new GoogleChatsIntegrationExternalType($this->integration_arr);
         } else {
            $this->external_type_obj = null;
        }
    }

    /**
     * A common functiom that can be called for creating various integration of subtype, e.g. for FB or Teams.
     * Integration topic will be created based on groupid, chapterid and channelid
     * @param string $integration_name
     * @param int $groupid
     * @param int $chapterid
     * @param int $channelid
     * @param string $integration_json this is specific to the subtype and opaque to this method.
     * @param int $external_type e.g. FB Workplace or Teams
     * @return GroupIntegration|null
     */
    protected static function CreateNewIntegration(string $integration_name, int $groupid, int $chapterid, int $channelid, string $integration_json, int $external_type)
    {
        if ($chapterid && $channelid) {
            return null; // Either chapterid or channel id can be set.
        }

        $integration_topic = "GRP_{$groupid}_{$chapterid}_{$channelid}";
        $rowid = parent::_CreateNewIntegration($integration_topic, self::INT_INTERNAL_TYPE_GRP, $external_type, $integration_json, $integration_name);
        $row = parent::_GetIntegrationRec($rowid);
        if (!empty($row)) {
            return new GroupIntegration((int)$row['integrationid'], (int)$row['companyid'], $row);
        } else {
            return null;
        }
    }

    /**
     * Creates a new integration of FB Type
     * @param string $integration_name
     * @param int $groupid
     * @param int $chapterid
     * @param int $channelid
     * @param string $access_token - access token from FB Workplace platform. Needs to be token of application integration typ
     * @param string $fb_groupid - groupid as provided by FB Workplace platform.
     * @param bool $link_unfurling if integration supports Link Unfurling then set this paramter to true else false.
     * @param array $group_perm
     * @param array $chapter_perm
     * @param array $channel_perm
     * @return GroupIntegration|null
     */
    public static function CreateNewFbWorkplaceIntegration(string $integration_name, int $groupid, int $chapterid, int $channelid, string $access_token, string $fb_groupid, bool $link_unfurling, array $group_perm=self::ALLOW_ALL, array $chapter_perm=self::ALLOW_ALL, array $channel_perm=self::ALLOW_ALL, bool $publish_option_pre_selected=false)
    {
        $integration_arr = array(
            'external' => array(
                'access_token' => CompanyEncKey::Encrypt($access_token),
                'fb_groupid' => $fb_groupid,
                'link_unfurling' => $link_unfurling
            ),
            'group' => $group_perm,
            'chapter' => $chapter_perm,
            'channel' => $channel_perm,
            'publish_option_pre_selected'=>$publish_option_pre_selected
        );
        $integration_json = json_encode($integration_arr);
        return self::CreateNewIntegration($integration_name, $groupid, $chapterid, $channelid, $integration_json, self::EXTERNAL_TYPES['workplace']);
    }

    /**
     * Creates a new integration of Yammer Type
     * @param string $integration_name
     * @param int $groupid
     * @param int $chapterid
     * @param int $channelid
     * @param string $auth_token - auth token from Yammer platform. Needs to be token of application integration typ
     * @param string $yammer_groupid - groupid as provided by Yammer admin.
     * @param array $group_perm
     * @param array $chapter_perm
     * @param array $channel_perm
     * @return GroupIntegration|null
     */
    public static function CreateNewYammerIntegration(string $integration_name, int $groupid, int $chapterid, int $channelid, string $auth_token, string $yammer_groupid, array $group_perm=self::ALLOW_ALL, array $chapter_perm=self::ALLOW_ALL, array $channel_perm=self::ALLOW_ALL, bool $publish_option_pre_selected=false)
    {
        $integration_arr = array(
            'external' => array(
                'access_token' => CompanyEncKey::Encrypt($auth_token),
                'yammer_groupid' => $yammer_groupid
            ),
            'group' => $group_perm,
            'chapter' => $chapter_perm,
            'channel' => $channel_perm,
            'publish_option_pre_selected'=>$publish_option_pre_selected
        );
        $integration_json = json_encode($integration_arr);
        return self::CreateNewIntegration($integration_name, $groupid, $chapterid, $channelid, $integration_json, self::EXTERNAL_TYPES['yammer']);
    }

    /**
     * Creates a new integration of Slack Type
     * @param string $integration_name
     * @param int $groupid
     * @param int $chapterid
     * @param int $channelid
     * @param string $auth_token - auth token from Slack platform. Needs to be token of application integration typ
     * @param string $slack_groupid - groupid as provided by Slack admin.
     * @param array $group_perm
     * @param array $chapter_perm
     * @param array $channel_perm
     * @return GroupIntegration|null
     */
    public static function CreateNewSlackIntegration(string $integration_name, int $groupid, int $chapterid, int $channelid, string $auth_token, string $slack_groupid, array $group_perm, array $chapter_perm, array $channel_perm, bool $link_unfurling, bool $publish_option_pre_selected=false)
    {
        $integration_arr = array(
            'external' => array(
                'access_token' => CompanyEncKey::Encrypt($auth_token),
                'slack_groupid' => $slack_groupid,
                'link_unfurling' => $link_unfurling
            ),
            'group' => $group_perm,
            'chapter' => $chapter_perm,
            'channel' => $channel_perm,
            'publish_option_pre_selected'=>$publish_option_pre_selected
        );
        $integration_json = json_encode($integration_arr);
        return self::CreateNewIntegration($integration_name, $groupid, $chapterid, $channelid, $integration_json, self::EXTERNAL_TYPES['slack']);
    }
    
    /**
     * Creates a new integration of Teams Type
     * @param string $integration_name
     * @param int $groupid
     * @param int $chapterid
     * @param int $channelid
     * @param string $access_token ... for teams Team Group > Channel > Incoming webhook access_url is the access_token
     * @param array $group_perm
     * @param array $chapter_perm
     * @param array $channel_perm
     * @return GroupIntegration|null
     */
    public static function CreateNewTeamsIntegration(string $integration_name, int $groupid, int $chapterid, int $channelid, string $access_token, array $group_perm=self::ALLOW_ALL, array $chapter_perm=self::ALLOW_ALL, array $channel_perm=self::ALLOW_ALL, bool $publish_option_pre_selected=false)
    {
        $integration_arr = array(
            'external' => array(
                'access_token' => CompanyEncKey::Encrypt($access_token),
            ),
            'group' => $group_perm,
            'chapter' => $chapter_perm,
            'channel' => $channel_perm,
            'publish_option_pre_selected'=>$publish_option_pre_selected
        );
        $integration_json = json_encode($integration_arr);
        return self::CreateNewIntegration($integration_name, $groupid, $chapterid, $channelid, $integration_json, self::EXTERNAL_TYPES['teams']);
    }

    /**
     * Creates a new integration of Google Chats Type
     * @param string $integration_name
     * @param int $groupid
     * @param int $chapterid
     * @param int $channelid
     * @param string $access_token ... for teams Team Group > Channel > Incoming webhook access_url is the access_token
     * @param array $group_perm
     * @param array $chapter_perm
     * @param array $channel_perm
     * @return GroupIntegration|null
     */
    public static function CreateNewGoogleChatIntegration(string $integration_name, int $groupid, int $chapterid, int $channelid, string $access_token, array $group_perm=self::ALLOW_ALL, array $chapter_perm=self::ALLOW_ALL, array $channel_perm=self::ALLOW_ALL, bool $publish_option_pre_selected=false)
    {
        $integration_arr = array(
            'external' => array(
                'access_token' => CompanyEncKey::Encrypt($access_token),
            ),
            'group' => $group_perm,
            'chapter' => $chapter_perm,
            'channel' => $channel_perm,
            'publish_option_pre_selected'=>$publish_option_pre_selected
        );
        $integration_json = json_encode($integration_arr);
        return self::CreateNewIntegration($integration_name, $groupid, $chapterid, $channelid, $integration_json, self::EXTERNAL_TYPES['googlechat']);
    }    

    /**
     * This method is used to get GroupIntegration record by id. E.g. this method can be used to Object to set it active
     * or to delete it.
     * @param int $integrationid
     * @return GroupIntegration
     */
    public static function GetGroupIntegration(int $integrationid)
    {
        $item = parent::_GetIntegrationRec($integrationid);
        return new GroupIntegration((int)$item['integrationid'], (int)$item['companyid'], $item);
    }

    /**
     * Gets the group integrations that matching topic exactly. This method is useful for getting all integrations that
     * match a topic id for creating a list of integrations on the admin panel.
     *
     * @param int $groupid
     * @param int $chapterid
     * @param int $channelid
     * @param int $external_type
     * @param bool $activeOnly
     * @return array of GroupIntegration object type
     */
    public static function GetGroupIntegrationsByExactScope(int $groupid, int $chapterid, int $channelid, int $external_type = 0, bool $activeOnly = false)
    {
        $groupIntegrations = array();

        // First get all the integrations that match groupid and chapterid=0 and channelid=0
        $partialTopicId = "GRP_{$groupid}_{$chapterid}_{$channelid}";
        $row = parent::_GetIntegrationRecsMatchingTopic($partialTopicId, self::INT_INTERNAL_TYPE_GRP, $external_type);
        foreach ($row as $item) {
            if ($activeOnly && ($item['isactive'] != self::STATUS_ACTIVE)) {
                continue;
            }
            $groupIntegrations[] = new GroupIntegration((int)$item['integrationid'], (int)$item['companyid'], $item);
        }
        return $groupIntegrations;
    }

    /**
     * Gets all the group integrations applicable for scope. This method is useful for getting all integrations that
     * match a topic id (Note: topic id is created using groupid,chapterid and channelid) and then run the function of
     * interest (e.g. processCreateEvent) on all the returned integrations.
     * @param int $groupid
     * @param string $chapterids
     * @param string $channelids
     * @param int $external_type
     * @param bool $activeOnly
     * @return array of GroupIntegration object type
     */
    public static function GetGroupIntegrationsApplicableForScopeCSV(int $groupid, string $chapterids, string $channelids, int $external_type = 0, bool $activeOnly = false)
    {
        $groupIntegrations = array();

        // First get all the integrations that match groupid and chapterid=0 and channelid=0
        $partialTopicId = "GRP_{$groupid}_0_0";
        $row = parent::_GetIntegrationRecsMatchingTopic($partialTopicId, self::INT_INTERNAL_TYPE_GRP, $external_type);
        foreach ($row as $item) {
            if ($activeOnly && ($item['isactive'] != self::STATUS_ACTIVE)) {
                continue;
            }
            $groupIntegrations[] = new GroupIntegration((int)$item['integrationid'], (int)$item['companyid'], $item);
        }

        //Next all the integrations that match groupid/chapterid/channelid combo if chapterid is set
        if ($chapterids) {
            $chapteridsArray = explode(',',$chapterids);
            foreach($chapteridsArray as $chapterid){
                if ($chapterid){
                    $partialTopicId = "GRP_{$groupid}_{$chapterid}_0";
                    $row = parent::_GetIntegrationRecsMatchingTopic($partialTopicId, self::INT_INTERNAL_TYPE_GRP, $external_type);
                    foreach ($row as $item) {
                        if ($activeOnly && ($item['isactive'] != self::STATUS_ACTIVE)) {
                            continue;
                        }
                        $groupIntegrations[] = new GroupIntegration((int)$item['integrationid'], (int)$item['companyid'], $item);
                    }
                }
            }
        }
       // Next all the integrations that match groupid/chapterid/channelid combo if channelid is set
        if ($channelids) {
            $channelidsArray = explode(',',$channelids);
            foreach($channelidsArray as $channelid){
                if ($channelid){
                    $partialTopicId = "GRP_{$groupid}_0_{$channelid}";
                    $row = parent::_GetIntegrationRecsMatchingTopic($partialTopicId, self::INT_INTERNAL_TYPE_GRP, $external_type);
                    foreach ($row as $item) {
                        if ($activeOnly && ($item['isactive'] != self::STATUS_ACTIVE)) {
                            continue;
                        }
                        $groupIntegrations[] = new GroupIntegration((int)$item['integrationid'], (int)$item['companyid'], $item);
                    }
                }
            }
        }
        return $groupIntegrations;
    }

    /**
     * Updateintegration of FB Type
     * @param string $integration_name
     * @param string $access_token - access token from FB Workplace platform. Needs to be token of application integration typ
     * @param string $fb_groupid - groupid as provided by FB Workplace platform.
     * @param bool $link_unfurling if integration supports Link Unfurling then set this paramter to true else false.
     * @param array $group_perm
     * @param array $chapter_perm
     * @param array $channel_perm
     * @return int
     */
    public function updateFbWorkplaceIntegration(string $integration_name,string $access_token, string $fb_groupid, bool $link_unfurling, array $group_perm=self::ALLOW_ALL, array $chapter_perm=self::ALLOW_ALL, array $channel_perm=self::ALLOW_ALL,bool $publish_option_pre_selected = false)
    {
        $integration_arr = array(
            'external' => array(
                'access_token' => CompanyEncKey::Encrypt($access_token),
                'fb_groupid' => $fb_groupid,
                'link_unfurling' => $link_unfurling
            ),
            'group' => $group_perm,
            'chapter' => $chapter_perm,
            'channel' => $channel_perm,
            'publish_option_pre_selected'=>$publish_option_pre_selected
        );
        $integration_json = json_encode($integration_arr);
        return parent::update($integration_name,$integration_json);
    }

    /**
     * Update integration of Yammer Type
     * @param string $integration_name
     * @param string $auth_token - auth token from Yammer platform. Needs to be token of application integration typ
     * @param string $yammer_groupid - groupid as provided by Yammer admin.
     * @param array $group_perm
     * @param array $chapter_perm
     * @param array $channel_perm
     * @return int
     */
    public function updateYammerIntegration(string $integration_name, string $auth_token, string $yammer_groupid, array $group_perm=self::ALLOW_ALL, array $chapter_perm=self::ALLOW_ALL, array $channel_perm=self::ALLOW_ALL,bool $publish_option_pre_selected = false)
    {
        $integration_arr = array(
            'external' => array(
                'access_token' => CompanyEncKey::Encrypt($auth_token),
                'yammer_groupid' => $yammer_groupid
                
            ),
            'group' => $group_perm,
            'chapter' => $chapter_perm,
            'channel' => $channel_perm,
            'publish_option_pre_selected'=>$publish_option_pre_selected
        );
        $integration_json = json_encode($integration_arr);
        return parent::update($integration_name, $integration_json);
    }

    /**
     * Update integration of Slack Type
     * @param string $integration_name
     * @param string $auth_token - auth token from Slack platform. Needs to be token of application integration typ
     * @param string $slack_groupid - groupid as provided by Slack admin.
     * @param array $group_perm
     * @param array $chapter_perm
     * @param array $channel_perm
     * @return int
     */
    public function updateSlackIntegration(string $integration_name, string $auth_token, string $slack_groupid, array $group_perm, array $chapter_perm, array $channel_perm, bool $link_unfurling,bool $publish_option_pre_selected = false)
    {
        $integration_arr = array(
            'external' => array(
                'access_token' => CompanyEncKey::Encrypt($auth_token),
                'slack_groupid' => $slack_groupid,
                'link_unfurling' => $link_unfurling
            ),
            'group' => $group_perm,
            'chapter' => $chapter_perm,
            'channel' => $channel_perm,
            'publish_option_pre_selected'=>$publish_option_pre_selected
        );
        $integration_json = json_encode($integration_arr);
        return parent::update($integration_name,  $integration_json);
    }

    
     /**
     * Update integration of Teams Type
     * @param string $integration_name
     * @param string $access_token ... for teams Team Group > Channel > Incoming webhook access_url is the access_token
     * @param array $group_perm
     * @param array $chapter_perm
     * @param array $channel_perm
     * @return int
     */
    public function updateTeamsIntegration(string $integration_name, string $access_token, array $group_perm=self::ALLOW_ALL, array $chapter_perm=self::ALLOW_ALL, array $channel_perm=self::ALLOW_ALL,bool $publish_option_pre_selected = false)
    {
        $integration_arr = array(
            'external' => array(
                'access_token' => CompanyEncKey::Encrypt($access_token),
            ),
            'group' => $group_perm,
            'chapter' => $chapter_perm,
            'channel' => $channel_perm,
            'publish_option_pre_selected'=>$publish_option_pre_selected
        );
        $integration_json = json_encode($integration_arr);
        return parent::update($integration_name, $integration_json);
    }


     /**
     * Update integration of Google Type
     * @param string $integration_name
     * @param string $access_token ... for Google Team Group > Channel > Incoming webhook access_url is the access_token
     * @param array $group_perm
     * @param array $chapter_perm
     * @param array $channel_perm
     * @return int
     */
    public function updateGoogleChatIntegration(string $integration_name, string $access_token, array $group_perm=self::ALLOW_ALL, array $chapter_perm=self::ALLOW_ALL, array $channel_perm=self::ALLOW_ALL,bool $publish_option_pre_selected = false)
    {
        $integration_arr = array(
            'external' => array(
                'access_token' => CompanyEncKey::Encrypt($access_token),
            ),
            'group' => $group_perm,
            'chapter' => $chapter_perm,
            'channel' => $channel_perm,
            'publish_option_pre_selected'=>$publish_option_pre_selected
        );
        $integration_json = json_encode($integration_arr);
        return parent::update($integration_name, $integration_json);
    }

    

    public function processCreateEvent(Event $event)
    {
        if ($event->isPrivateEvent()) {
            Logger::Log("Integration: Skipping (Private Event) GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processCreateEvent({$event->id()})", Logger::SEVERITY['INFO']);
            return;
        }

        if ($event->isDynamicListEvent()) {
            Logger::Log("Integration: Skipping (Dynamic List Event) GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processCreateEvent({$event->id()})", Logger::SEVERITY['INFO']);
            return;
        }

       if ($this->isPublishAllowed($event, 'events')) {

            $print_result = 'Processed';
            if ($this->external_type_obj) {
                // First check if for some reason this event was already published, first delete it
                $key = 'EVT_' . $event->id();
                $external_id = $this->getExternalIdFromIntegrationRecord($key);
                list($hosted_by, $title, $description, $url, $when, $where) = self::_GetEventAttributes($event);
                list ($body, $title, $url) = $this->external_type_obj::BuildEventMessage(0,$hosted_by, $title, $description, $url, $when, $where);
                if (!empty($external_id)) {
                    $this->external_type_obj->deleteMessage($external_id,$title,$body);
                    $this->setExternalIdInIntegrationRecord($key, '');
                }

                $result = $this->external_type_obj->createMessage($body, $title, $url);

                if (empty($result)) {
                    $print_result = 'Fatal Error';
                } else {
                    $this->setExternalIdInIntegrationRecord($key, $result);
                }
            }
            
            Logger::Log("Integration: {$print_result} GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processCreateEvent({$event->id()})", Logger::SEVERITY['INFO']);
        }
    }

    public function processUpdateEvent(Event $event)
    {
        if ($event->isPrivateEvent()) {
            Logger::Log("Integration: Skipping (Private Event) GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processUpdateEvent({$event->id()})", Logger::SEVERITY['INFO']);
            return;
        }

        if ($event->isDynamicListEvent()) {
            Logger::Log("Integration: Skipping (Dynamic List Event) GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processUpdateEvent({$event->id()})", Logger::SEVERITY['INFO']);
            return;
        }

        if ($this->isPublishAllowed($event, 'events')) {
            $print_result = 'Processed';
            if ($this->external_type_obj) {
                $key = 'EVT_' . $event->id();
                $external_id = $this->getExternalIdFromIntegrationRecord($key);
                list($hosted_by, $title, $description, $url, $when, $where) = self::_GetEventAttributes($event);
                list ($body, $title, $url) = $this->external_type_obj::BuildEventMessage(1,$hosted_by, $title, $description, $url, $when, $where);

                if (!empty($external_id)) {
                    $result = $this->external_type_obj->updateMessage($external_id, $body, $title, $url);
                } else {
                    // In case messageid was not found, then create a new message
                    $result = $this->external_type_obj->createMessage($body, $title, $url);
                }

                if (empty($result)) {
                    $print_result = 'Fatal Error';
                } else {
                    $this->setExternalIdInIntegrationRecord($key, $result);
                }
            }

            Logger::Log("Integration: {$print_result} GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processUpdateEvent({$event->id()})", Logger::SEVERITY['INFO']);
        }
    }

    public function processDeleteEvent(Event $event)
    {

        if ($event->isPrivateEvent()) {
            Logger::Log("Integration: Skipping (Private Event) GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processDeleteEvent({$event->id()})", Logger::SEVERITY['INFO']);
            return;
        }

        if ($event->isDynamicListEvent()) {
            Logger::Log("Integration: Skipping (Dynamic List Event) GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processDeleteEvent({$event->id()})", Logger::SEVERITY['INFO']);
            return;
        }

        if ($this->isPublishAllowed($event, 'events')) {
            $print_result = 'Processed';
            if ($this->external_type_obj) {
                $key = 'EVT_' . $event->id();
                $external_id = $this->getExternalIdFromIntegrationRecord($key);
                if (!empty($external_id)) {
                    list($hosted_by, $title, $description, $url, $when, $where) = self::_GetEventAttributes($event);
                    list ($body, $title, $url) = $this->external_type_obj::BuildEventMessage(-1,$hosted_by, $title, $description, $url, $when, $where);
                    $result = $this->external_type_obj->deleteMessage($external_id,$title,$body);

                    if (empty($result)) {
                        $print_result = 'Fatal Error';
                    } else {
                        $this->setExternalIdInIntegrationRecord($key, '');
                    }
                } else {
                    $this->setExternalIdInIntegrationRecord($key, '');
                    $print_result = 'Not Found';
                }
            }

            Logger::Log("Integration: {$print_result} GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processDeleteEvent({$event->id()})", Logger::SEVERITY['INFO']);
        }
    }

    public function processCreatePost(Post $post)
    {
        if ($post->isDynamicListPost()) {
            Logger::Log("Integration: Skipping (Dynamic List Post) GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processCreatePost({$post->id()})", Logger::SEVERITY['INFO']);
            return;
        }

        if ($this->isPublishAllowed($post, 'post')) {

            $print_result = 'Processed';
            if ($this->external_type_obj) {
                // First check if for some reason this event was already published, first delete it
                $key = 'POS_' . $post->id();
                $external_id = $this->getExternalIdFromIntegrationRecord($key);
                list($posted_by, $title, $description, $url) = self::_GetPostAttributes($post);
                list ($body, $title, $url) = $this->external_type_obj::BuildPostMessage(0, $posted_by, $title, $description, $url);
                if (!empty($external_id)) {
                    $this->external_type_obj->deleteMessage($external_id,$title,$body);
                    $this->setExternalIdInIntegrationRecord($key, '');
                }

                $result = $this->external_type_obj->createMessage($body, $title, $url);

                if (empty($result)) {
                    $print_result = 'Fatal Error';
                } else {
                    $this->setExternalIdInIntegrationRecord($key, $result);
                }
            }

            Logger::Log("Integration: {$print_result} GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processCreatePost({$post->id()})", Logger::SEVERITY['INFO']);
        }
    }

    public function processUpdatePost(Post $post)
    {
        if ($post->isDynamicListPost()) {
            Logger::Log("Integration: Skipping (Dynamic List Post) GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processUpdatePost({$post->id()})", Logger::SEVERITY['INFO']);
            return;
        }

        if ($this->isPublishAllowed($post, 'post')) {
            $print_result = 'Processed';

            if ($this->external_type_obj) {
                $key = 'POS_' . $post->id();
                $external_id = $this->getExternalIdFromIntegrationRecord($key);
                list($posted_by, $title, $description, $url) = self::_GetPostAttributes($post);
                list ($body, $title, $url) = $this->external_type_obj::BuildPostMessage(1, $posted_by, $title, $description, $url);
                if (!empty($external_id)) {
                    $result = $this->external_type_obj->updateMessage($external_id, $body, $title, $url);
                } else {
                    // In case messageid was not found, then create a new message
                    $result = $this->external_type_obj->createMessage($body, $title, $url);
                }

                if (empty($result)) {
                    $print_result = 'Fatal Error';
                } else {
                    $this->setExternalIdInIntegrationRecord($key, $result);
                }

            }

            Logger::Log("Integration: {$print_result} GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processUpdatePost({$post->id()})", Logger::SEVERITY['INFO']);
        }
    }

    public function processDeletePost(Post $post)
    {
        if ($post->isDynamicListPost()) {
            Logger::Log("Integration: Skipping (Dynamic List Post) GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processDeletePost({$post->id()})", Logger::SEVERITY['INFO']);
            return;
        }

        if ($this->isPublishAllowed($post, 'post')) {
            $print_result = 'Processed';
            if ($this->external_type_obj) {
                $key = 'POS_' . $post->id();

                $external_id = $this->getExternalIdFromIntegrationRecord($key);
                if (!empty($external_id)) {
                    list($posted_by, $title, $description, $url) = self::_GetPostAttributes($post);
                    list ($body, $title, $url) = $this->external_type_obj::BuildPostMessage(-1, $posted_by, $title, $description, $url);
                    $result = $this->external_type_obj->deleteMessage($external_id,$title,$body);

                    if (empty($result)) {
                        $print_result = 'Fatal Error';
                    } else {
                        $this->setExternalIdInIntegrationRecord($key, '');
                    }
                } else {
                    $this->setExternalIdInIntegrationRecord($key, '');
                    $print_result = 'Not Found';
                }
            }

            Logger::Log("Integration: {$print_result} GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processDeletePost({$post->id()})", Logger::SEVERITY['INFO']);
        }
    }

    public function processCreateNewsletter(Newsletter $newsletter)
    {
        if ($newsletter->isDynamicListNewsletter()) {
            Logger::Log("Integration: Skipping (Dynamic List Post) GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processCreateNewsletter({$newsletter->id()})", Logger::SEVERITY['INFO']);
            return;
        }

        if ($this->isPublishAllowed($newsletter, 'newsletter')) {

            $print_result = 'Processed';
            if ($this->external_type_obj) {
                // First check if for some reason this event was already published, first delete it
                $key = 'NWS_' . $newsletter->id();
                $external_id = $this->getExternalIdFromIntegrationRecord($key);
                list($posted_by, $title, $description, $url) = self::_GetNewsletterAttributes($newsletter);
                list ($body, $title, $url) = $this->external_type_obj::BuildNewsletterMessage(0, $posted_by, $title, $description, $url);
                if (!empty($external_id)) {
                    $this->external_type_obj->deleteMessage($external_id,$title,$body);
                    $this->setExternalIdInIntegrationRecord($key, '');
                }
                
                $result = $this->external_type_obj->createMessage($body, $title, $url);

                if (empty($result)) {
                    $print_result = 'Fatal Error';
                } else {
                    $this->setExternalIdInIntegrationRecord($key, $result);
                }
            }

            Logger::Log("Integration: {$print_result} GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processCreateNewsletter({$newsletter->id()})", Logger::SEVERITY['INFO']);
        }
    }

    public function processUpdateNewsletter(Newsletter $newsletter)
    {
        if ($newsletter->isDynamicListNewsletter()) {
            Logger::Log("Integration: Skipping (Dynamic List Post) GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processUpdateNewsletter({$newsletter->id()})", Logger::SEVERITY['INFO']);
            return;
        }

        if ($this->isPublishAllowed($newsletter, 'newsletter')) {
            $print_result = 'Processed';
            if ($this->external_type_obj) {
                $key = 'NWS_' . $newsletter->id();
                $external_id = $this->getExternalIdFromIntegrationRecord($key);
                list($posted_by, $title, $description, $url) = self::_GetNewsletterAttributes($newsletter);
                list ($body, $title, $url) = $this->external_type_obj::BuildNewsletterMessage(1, $posted_by, $title, $description, $url);
                if (!empty($external_id)) {
                    $result = $this->external_type_obj->updateMessage($external_id, $body, $title, $url);
                } else {
                    // In case messageid was not found, then create a new message
                    $result = $this->external_type_obj->createMessage($body, $title, $url);
                }

                if (empty($result)) {
                    $print_result = 'Fatal Error';
                } else {
                    $this->setExternalIdInIntegrationRecord($key, $result);
                }
            }

            Logger::Log("Integration: {$print_result} GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processUpdatePost({$newsletter->id()})", Logger::SEVERITY['INFO']);
        }
    }

    public function processDeleteNewsletter(Newsletter $newsletter)
    {
        if ($newsletter->isDynamicListNewsletter()) {
            Logger::Log("Integration: Skipping (Dynamic List Post) GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processDeleteNewsletter({$newsletter->id()})", Logger::SEVERITY['INFO']);
            return;
        }

        if ($this->isPublishAllowed($newsletter, 'newsletter')) {
            $print_result = 'Processed';
            if ($this->external_type_obj) {
                $key = 'NWS_' . $newsletter->id();
                $external_id = $this->getExternalIdFromIntegrationRecord($key);
                if (!empty($external_id)) {
                    list($posted_by, $title, $description, $url) = self::_GetNewsletterAttributes($newsletter);
                    list ($body, $title, $url) = $this->external_type_obj::BuildNewsletterMessage(-1, $posted_by, $title, $description, $url);
                    $result = $this->external_type_obj->deleteMessage($external_id,$title,$body);

                    if (empty($result)) {
                        $print_result = 'Fatal Error';
                    } else {
                        $this->setExternalIdInIntegrationRecord($key, '');
                    }
                } else {
                    $this->setExternalIdInIntegrationRecord($key, '');
                    $print_result = 'Not Found';
                }
            }

            Logger::Log("Integration: {$print_result} GroupIntegration({$this->getIntegrationExternalName()},{$this->integration_topic},{$this->external_type},{$this->id})->processDeletePost({$newsletter->id()})", Logger::SEVERITY['INFO']);
        }
    }

    /**
     * @param string $key eg. 'EVT_{id} or POS_{id}'
     * @param string $externalId message id of FB or yammer
     * @param string $errorMessage or error message
     * @return int
     */
    private function setExternalIdInIntegrationRecord(string $key, string $externalId)
    {
        $record_value_arr  = array();
        if (!empty($externalId)) {
            $record_value_arr['id'] = $externalId;
        }
        $value = json_encode($record_value_arr);
        return self::DBUpdatePS("INSERT INTO integration_records (integrationid, companyid, record_key, record_value, updatedon) VALUES (?,?,?,?,now()) ON DUPLICATE KEY UPDATE record_value=VALUES(record_value),updatedon=now()",
            'iixx',
            $this->id, $this->cid(), $key, $value);
    }

    /**
     * @param string $key
     * @return string returns id string
     */
    private function getExternalIdFromIntegrationRecord(string $key):string
    {
        $rec = '';
        $row = self::DBGet("SELECT record_value FROM integration_records WHERE integrationid={$this->id()} AND record_key='{$key}' AND companyid={$this->cid()}");
        if (!empty($row) && !empty($row[0]['record_value'])) {
            $rec =  $row[0]['record_value'];
            return strval(json_decode($rec, true)['id'] ?? '');
        }
        return '';
    }

    /**
     * @param Teleskope $object
     * @param string $type , either 'events' or 'post'
     * @return bool
     */
    private function isPublishAllowed(Teleskope $object, string $type): bool
    {
        return (($object->val('chapterid') && $this->integration_arr['chapter'][$type])
                || ($object->val('channelid') && $this->integration_arr['channel'][$type])
                || (!$object->val('chapterid') && !$object->val('channelid') && $this->integration_arr['group'][$type]))
            && ((int)$object->val('groupid') === $this->groupid)
            && ((int)$object->val('companyid') === $this->cid);
    }

    private static function _GetEventAttributes(Event $event): array
    {
        global $_COMPANY, $_ZONE;
        try {
            $tz_utc = new DateTimeZone('UTC');
            $creator_tz = new DateTimeZone($event->val('timezone'));
            $creator_start = (new DateTime($event->val('start'), $tz_utc))->setTimezone($creator_tz);
            $creator_end = (new DateTime($event->val('end'), $tz_utc))->setTimezone($creator_tz);
            if ($event->isSeriesEventHead()){
                $totalEventsInSeries = count($event->GetEventsInSeries($event->id()));
                $when =  $totalEventsInSeries." events in series, starting ". $creator_start->format('M jS, Y @g:i a')." and ending on ". $creator_end->format('M jS, Y g:i a T');

            } else{
                $when = $creator_start->format('M jS, Y @g:i a') . ' - ' . ($event->getDurationInSeconds() > 86400 ? $creator_end->format('M jS, Y g:i a T') : $creator_end->format('g:i a T'));
            }
        } catch (Exception $e) {
            $when = 'Not Available';
        }
        $title = html_entity_decode(trim($event->val('eventtitle')));
        $description = convertHTML2PlainText($event->val('event_description'), 200);

        $url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'eventview?id=' . $_COMPANY->encodeId($event->id());

        if ($event->val('event_attendence_type') == 1) {
            $where = $event->val('eventvanue');
        } elseif ($event->val('event_attendence_type') == 2) {
            $where = $event->val('web_conference_sp');
        } elseif ($event->val('event_attendence_type') == 3) {
            $where = $event->val('web_conference_sp') . ' & ' . $event->val('eventvanue');
        } else {
            $where = '';
        }
        $where = html_entity_decode(trim($where));

        $hosted_by = $event->getFormatedEventCollaboratedGroupsOrChapters();
       
        if (!empty($event->val('chapterid'))) {
            $chapter_ids = explode(',', $event->val('chapterid'));
            foreach ($chapter_ids as $chapter_id) {
                $chapter = Group::GetChapterName($chapter_id, $event->val('groupid'));
                if (!empty($chapter['chaptername'])) {
                    $hosted_by .= ', ' . $chapter['chaptername'] . ' ' . $_COMPANY->getAppCustomization()['chapter']['name-short'];
                }
            }
        }

        if (!empty($event->val('channelid'))) {
            $channel = Group::GetChannelName($event->val('channelid'), $event->val('groupid'));
            if (!empty($channel['channelname'])) {
                $hosted_by .= ', ' . $channel['channelname'] . ' ' . $_COMPANY->getAppCustomization()['channel']['name-short'];
            }
        }
        $hosted_by = html_entity_decode(trim($hosted_by));

        return array ($hosted_by, $title, $description, $url, $when, $where);
    }

    private static function _GetPostAttributes(Post $post): array
    {
        global $_COMPANY, $_ZONE;
        $title = html_entity_decode(trim($post->val('title')));
        $description = convertHTML2PlainText($post->val('post'), 200);

        $url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'viewpost?id=' . $_COMPANY->encodeId($post->id());
        $posted_by = Group::GetGroupName($post->val('groupid'));
        
        if (!empty($post->val('chapterid'))) {
            $chapter_ids = explode(',', $post->val('chapterid'));
            foreach ($chapter_ids as $chapter_id) {
                $chapter = Group::GetChapterName($chapter_id, $post->val('groupid'));
                if (!empty($chapter['chaptername'])) {
                    $posted_by .= ', ' . $chapter['chaptername'] . ' ' . $_COMPANY->getAppCustomization()['chapter']['name-short'];
                }
            }
        }
        if (!empty($post->val('channelid'))) {
            $channel = Group::GetChannelName($post->val('channelid'), $post->val('groupid'));
            if (!empty($channel['channelname'])) {
                $posted_by .= ', ' . $channel['channelname'] . ' ' . $_COMPANY->getAppCustomization()['channel']['name-short'];
            }
        }
        $posted_by = html_entity_decode(trim($posted_by));

        return array ($posted_by, $title, $description, $url);
    }

    private static function _GetNewsletterAttributes(Newsletter $newsletter): array
    {
        global $_COMPANY, $_ZONE;
        $title = html_entity_decode(trim($newsletter->val('newslettername')));
        $description = $newsletter->val('newsletter_summary') ?? $newsletter->val('newslettername');

        $url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'newsletter?id=' . $_COMPANY->encodeId($newsletter->val('newsletterid'));

        $posted_by = Group::GetGroupName($newsletter->val('groupid'));
        $groupid = $newsletter->val('groupid');
        if (!empty($newsletter->val('chapterid'))) {
            $chapter_ids = explode(',', $newsletter->val('chapterid'));
            foreach ($chapter_ids as $chapter_id) {
                $chapter = Group::GetChapterName($chapter_id, $groupid);
                if (!empty($chapter['chaptername'])) {
                    $posted_by .= ', ' . $chapter['chaptername'] . ' ' . $_COMPANY->getAppCustomization()['chapter']['name-short'];
                }
            }
        }
        if (!empty($newsletter->val('channelid'))) {
            $channel_ids = explode(',', $newsletter->val('channelid'));
            foreach ($channel_ids as $channel_id) {
                $channel = Group::GetChannelName($channel_id, $groupid);
                if (!empty($channel['channelname'])) {
                    $posted_by .= ', ' . $channel['channelname'] . ' ' . $_COMPANY->getAppCustomization()['channel']['name-short'];
                }
            }
        }

        $posted_by = html_entity_decode(trim($posted_by));

        return array ($posted_by, $title, $description, $url);
    }


    public static function GetIntegrationidsByRecordKey(string $record_key){
        global $_COMPANY, $_ZONE;
        $data = null;
       
        $r = self::DbGet("SELECT DISTINCT(`integrationid`) as `integrationid` FROM `integration_records` WHERE `companyid`='{$_COMPANY->id()}' AND `record_key`='{$record_key}' AND json_length(record_value)!=0");

        if (!empty($r)){
            $data =  array_column($r,'integrationid');
        }
        return $data;
    }

    public function isPublishPreSelectOn() : bool
    {
        return isset($this->integration_arr['publish_option_pre_selected']) && $this->integration_arr['publish_option_pre_selected'];
    }

    public static function GetUniqueGroupIntegrationsByExternalType(int $groupid, string $chapterids = '0', string $channelids = '0', array $published_integrations = array(), string $type = '')
    {
        global $_COMPANY, $_ZONE;
        $uniqueIntegrations = array();
        $uniqueExternalType = array();
        $tempObject =   (
                            new class(0,$_COMPANY->id(),array('companyid'=>$_COMPANY->id(),'groupid'=>$groupid,'chapterid'=>$chapterids,'channelid'=>$channelids)) extends Teleskope
                                {
                                    public function __construct(int $id, int $cid, array $fields)
                                    {
                                        parent::__construct($id, $cid, $fields);
                                        //Throw away class
                                    }
                                }
                        );


        $allIntegrations = self::GetGroupIntegrationsApplicableForScopeCSV($groupid,$chapterids,$channelids,0,true);
        foreach($allIntegrations as $integration){
            if (!in_array($integration->val('external_type'),$uniqueExternalType)){
                if ($integration->isPublishAllowed($tempObject, $type)) {
                    $uniqueExternalType[] = $integration->val('external_type');
                }
            }
        }

        foreach( $uniqueExternalType as $externalType){
            $externalItegrations = self::GetGroupIntegrationsApplicableForScopeCSV($groupid,$chapterids,$channelids,$externalType,true);
            $checked = '';
           
            if (!empty($published_integrations)){
                foreach($externalItegrations as $externalItegration) {
                    if (in_array($externalItegration->id(),$published_integrations)){
                        $checked = 'checked';
                        break;
                    }
                }
            }
            $publish_option_pre_selected = false;
            foreach($externalItegrations as $externalItegration) {
                if ($externalItegration->isPublishPreSelectOn()){ // Match at least once have ture
                    $publish_option_pre_selected = true;
                    break;
                }
            }
            $external_name = ucfirst(array_flip(self::EXTERNAL_TYPES)[$externalType] ?? 'undefined');
            $external_name = ($external_name === 'Yammer' ? 'Viva Engage' : $external_name);

            $uniqueIntegrations[] = array(
                'externalId'=>$externalType,
                'externalName'=>$external_name,
                'checked'=> $checked,
                'publish_option_pre_selected'=>$publish_option_pre_selected
            );
        }
        return $uniqueIntegrations;
    }

    public function __getval_integration_json(string $integration_json): string|null
    {
        $json = $this->val('integration_json', false);

        if (is_null($json)) {
            return null;
        }

        $integration_json = json_decode($json, true);
        $access_token = $integration_json['external']['access_token'] ?? null;

        if (!$access_token) {
            return $json;
        }

        $integration_json['external']['access_token'] = CompanyEncKey::Decrypt($access_token);
        return json_encode($integration_json);
    }
}