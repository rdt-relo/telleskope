<?php
/**
 * This is a base class that defines integration with Integration table. Also defines key context/attributes that are
 * common to all integrations.
 * Class Integration
 */
class Integration extends Teleskope
{
    // Define Integration Internal types - i.e. what are we integrating on our side
    const INT_INTERNAL_TYPE_GRP = 1;
    const INT_INTERNAL_TYPE_USER = 2;

    // Define Integration External types - i.e. what are we integrtating with on their side
    const EXTERNAL_TYPES = array (
        'workplace' => 1,
        'yammer'    => 2,
        'teams'     => 3,
        'slack'     => 4,
        'googlechat' => 5,
    );

    protected $integration_topic = '';
    protected $internal_type = 0;
    protected $external_type = 0;
    protected $integration_arr = array();

    protected function __construct(int $id, int $cid, array $fields)
    {
        parent::__construct($id, $cid, $fields);
        $this->integration_topic = $this->val('integration_topic');
        $this->internal_type = (int)$this->val('internal_type');
        $this->external_type = (int)$this->val('external_type');
        $this->integration_arr = json_decode($this->val('integration_json'), true);
    }

    protected static function _CreateNewIntegration(string $integration_topic, int $internal_type, int $external_type, string $integration_json, string $integration_name)
    {
        global $_COMPANY, $_USER;
        return self::DBInsertPS("INSERT INTO integrations  (companyid, integration_topic, internal_type, external_type, integration_json, integration_name, createdby) VALUES (?,?,?,?,?,?,?)",
            'isiixsi',
            $_COMPANY->id(), $integration_topic, $internal_type, $external_type, $integration_json, $integration_name, $_USER->id());
    }

    protected static function _GetIntegrationRec(int $integrationid): array
    {
        global $_COMPANY;
        $rows = self::DBROGet("SELECT * FROM integrations WHERE companyid={$_COMPANY->id()} AND integrationid={$integrationid}");
        if (count($rows)) {
            return $rows[0];
        }
        return array();
    }

    protected static function _GetIntegrationRecsMatchingTopic(string $partialTopicId, int $internal_type = 0, int $external_type = 0): array
    {
        global $_COMPANY;

        $internalTypeFilter = '';
        if ($internal_type) {
            $internalTypeFilter = " AND internal_type={$internal_type}";
        }

        $externalTypeFilter = '';
        if ($external_type) {
            $externalTypeFilter = " AND external_type={$external_type}";
        }

        return self::DBROGet("SELECT * FROM integrations WHERE companyid={$_COMPANY->id()} AND (integration_topic like '{$partialTopicId}%' {$internalTypeFilter} {$externalTypeFilter})");
    }

    public function deleteIt(): bool
    {
        global $_COMPANY;
        $retVal = self::DBMutate("DELETE FROM integrations WHERE `companyid`={$_COMPANY->id()} AND integrationid={$this->id}");
        $retVal = $retVal && self::DBMutate("DELETE FROM integration_records WHERE `companyid`={$_COMPANY->id()} AND integrationid={$this->id}");

        if ($retVal) {
            self::LogObjectLifecycleAudit('delete', 'group_integration',$this->id, 0);
        }
        return $retVal;
    }

    public function setActive()
    {
        $status = self::STATUS_ACTIVE;
        return self::DBMutate("UPDATE integrations SET isactive='{$status}',modifiedon=now() WHERE integrationid='{$this->id}'");
    }

    public function setInactive()
    {
       $status = self::STATUS_INACTIVE;
       
        return self::DBMutate("UPDATE integrations SET isactive='{$status}',modifiedon=now() WHERE integrationid='{$this->id}'");
    }   

    protected function update(string $integration_name, string $integration_json)
    {
        // For a survey the only field that can be updated is survey_json
        return self::DBUpdatePS("UPDATE integrations  SET integration_name=?,integration_json=? WHERE integrationid=?",
            'sxi',
            $integration_name, $integration_json, $this->id());
    }

    public function getIntegrationExternalName() {
        return ucfirst(array_flip(self::EXTERNAL_TYPES)[$this->external_type] ?? 'undefined');
    }

    public function getGroupId(){
        $toks = explode('_', $this->integration_topic);
        if (count($toks) >= 4){
            return $toks[1];
        }
        return 0;
    }
    public function getChapterId(){
        $toks = explode('_', $this->integration_topic);
        if (count($toks) >= 4){
            return $toks[2];
        }
        return 0;
    }
    public function getChannelId(){
        $toks = explode('_', $this->integration_topic);
        if (count($toks) >= 4){
            return $toks[3];
        }
        return 0;
    }

}

