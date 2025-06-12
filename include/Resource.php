<?php
// Do no use require_once as this class is included in Company.php.

class Resource extends Teleskope {

  public static function GetTopicType():string {return self::TOPIC_TYPES['RESOURCE'];}
  use TopicUsageLogsTrait;

    public const RESOURCE_TYPE = array(
        'RESOURCE_UNDEFINED'    => 0,
        'RESOURCE_LINK'         => 1,
        'RESOURCE_FILE'		    => 2,
        'RESOURCE_FOLDER'       => 3,
    );

	protected function __construct(int $id,int $cid,array $fields) {
		parent::__construct($id,$cid,$fields);
		//declaring it protected so that no one can create it outside this class.
	}

	public function isLink() {
	    return (int)$this->val('resource_type') === self::RESOURCE_TYPE['RESOURCE_LINK'];
    }

    public function isFile() {
        return (int)$this->val('resource_type') === self::RESOURCE_TYPE['RESOURCE_FILE'];
    }

    public function isFolder() {
        return (int)$this->val('resource_type') === self::RESOURCE_TYPE['RESOURCE_FOLDER'];
    }

    public function getAllParents () {
	    global $_COMPANY,$_ZONE;
	    $parents = array();
	    $parentid = $this->val('parent_id');

	    while ($parentid) {
            $rows = self::DBGet("SELECT resource_id, parent_id, resource_name, chapterid, channelid FROM group_resources WHERE `group_resources`.`companyid`={$_COMPANY->id()} AND `group_resources`.`zoneid`='{$_ZONE->id()}' AND resource_id={$parentid}");
            if (!empty($rows)) {
                $parentid = $rows[0]['parent_id'];
                $parents[] = $rows[0];
            } else {
                $parentid = 0;
            }
        }
        // Add root parent
        return array_reverse($parents);
    }

    /**
     * Returns an array of subfolder resources
     * @return array if no subfolders were found an empty array is returned.
     */
    public function getAllSubfolderResources () {

        $subfolders = array();
        global $_COMPANY,$_ZONE; /* @var Company $_COMPANY */

        $subfolder_rows = self::DBGet("SELECT * FROM group_resources WHERE `group_resources`.`companyid`='{$_COMPANY->id()}' AND `group_resources`.`zoneid`='{$_ZONE->id()}' AND  parent_id={$this->id}");

        foreach ($subfolder_rows as $row) {
            $subfolders[] = new Resource($row['resource_id'], $_COMPANY->id(), $row);
        }

        return $subfolders;
    }

    public function download()
    {
        global $_COMPANY;
        $dest_name = basename($this->val('resource'));
        $download_file_name = slugify($this->val('resource_name')) . '.' . $this->val('extention');

        $result = null;

        $s3 = Aws\S3\S3Client::factory([
            'version' => 'latest',
            'region' => S3_REGION
        ]);

        $obj_name = $_COMPANY->val('s3_folder') . Company::S3_SAFE_AREA['GROUP_RESOURCE'] . $dest_name;

        $result = $s3->getObject([
            'Bucket' => S3_SAFE_BUCKET,
            'Key' => $obj_name
        ]);

        $result['DownloadFilename'] = $download_file_name;

        return $result;
    }


	public static function GetResource(int $id, bool $ignoreZone=false) {
		$obj = null;
        global $_COMPANY,$_ZONE; /* @var Company $_COMPANY */

        if ($ignoreZone) {
            $zoneFilter = '';
        } else {
            $zoneFilter = " AND `group_resources`.`zoneid`='{$_ZONE->id()}'";
        }

		$r1 = self::DBGet("SELECT * FROM group_resources WHERE `group_resources`.`companyid`='{$_COMPANY->id()}' {$zoneFilter} AND resource_id={$id}");

		if (count($r1)) {
			$obj = new Resource($id, $_COMPANY->id(), $r1[0]);
		}
		return $obj;
	}

    public static function ConvertDBRecToResource (array $rec): ?Resource
    {
        global $_COMPANY;
        $obj = null;
        $gr = (int)$rec['resource_id'];
        $c = (int)$rec['companyid'];
        if ($gr && $c && $c === $_COMPANY->id())
            $obj = new Resource($gr, $c, $rec);
        return $obj;
    }

  public static function GetResourceData(int $id)
  {
    global $_COMPANY, $_ZONE;

    $row = self::DBGet("SELECT * FROM group_resources WHERE `group_resources`.`companyid`='{$_COMPANY->id()}' AND `group_resources`.`zoneid`='{$_ZONE->id()}' AND resource_id={$id}");
    if (sizeof($row) > 0) {
      return $row[0];
    } else {
      return null;
    }
  }
    /**
     * @param int $groupid
     * @param int $parent_id
     * @param int|null $chapterid filter is applied only if chapterid is not null
     * @param int|null $channelid filter is applied only if channelid is not null
     * @return array of all the direct children resources
     */
    public static function GetResourcesForGroup(int $groupid, int $parent_id, ?int $chapterid, ?int $channelid,int $is_resource_lead_content = 0, string $sortBy = 'default'): array
    {
        global $_COMPANY,$_ZONE;
        $condition = '';

        if (!$parent_id){
            if ($chapterid !== null) {
                $condition .= " AND group_resources.`chapterid`='{$chapterid}'";
            }

            if ($channelid !== null) {
                $condition .= " AND group_resources.`channelid`='{$channelid}'";
            }
        }

        $orderBy = " ORDER BY group_resources.pin_to_top DESC, group_resources.modifiedon DESC";
        if ($sortBy && $sortBy!='default'){
            if ($sortBy == 'name') {
                $orderBy = " ORDER BY group_resources.resource_name";
            } elseif($sortBy == 'size') {
                $orderBy = " ORDER BY group_resources.size DESC";
            } elseif($sortBy == 'type') {
                $orderBy = " ORDER BY group_resources.extention";
            } elseif($sortBy == 'created') {
                $orderBy = " ORDER BY group_resources.createdon DESC";
            } elseif($sortBy == 'modified') {
                $orderBy = " ORDER BY group_resources.modifiedon DESC";
            } 

        }

        return self::DBGet("SELECT group_resources.*,chapters.chaptername,chapters.colour as chapterColor,group_channels.channelname, group_channels.colour as channelColor FROM `group_resources` LEFT JOIN chapters ON chapters.chapterid= group_resources.chapterid LEFT JOIN group_channels ON group_channels.channelid=group_resources.channelid WHERE `group_resources`.`companyid`='{$_COMPANY->id()}' AND `group_resources`.`zoneid`='{$_ZONE->id()}' AND  group_resources.`groupid`={$groupid} AND group_resources.is_lead_content='{$is_resource_lead_content}' AND group_resources.`parent_id`={$parent_id} {$condition} AND group_resources.`isactive`=1 {$orderBy}");
    }

       /**
     * @param int $groupid
     * @param int $parent_id
     * @param int $chapterid filter is applied only if chapterid is not null
     * @param int $channelid filter is applied only if channelid is not null
     * @param $start
     * @param $end
     * @return array of all the direct children resources
     */
    public static function GetResourcesForGroupMobileApi(int $groupid, int $parent_id, int $chapterid, int $channelid, int $start = 0, int $end = 30, int $is_resource_lead_content = 0): array
    {
        global $_COMPANY,$_ZONE;
        $condition = '';

        if (!$parent_id){
            if ($chapterid > 0) {
                $condition .= " AND group_resources.`chapterid`='{$chapterid}'";
            }

            if ($channelid > 0) {
                $condition .= " AND group_resources.`channelid`='{$channelid}'";
            }
        }

        return self::DBGet("SELECT group_resources.*,chapters.chaptername,chapters.colour as chapterColor,group_channels.channelname, group_channels.colour as channelColor FROM `group_resources` LEFT JOIN chapters ON chapters.chapterid= group_resources.chapterid LEFT JOIN group_channels ON group_channels.channelid=group_resources.channelid WHERE `group_resources`.`companyid`='{$_COMPANY->id()}' AND `group_resources`.`zoneid`='{$_ZONE->id()}' AND  group_resources.`groupid`={$groupid} AND group_resources.is_lead_content='{$is_resource_lead_content}' AND group_resources.`parent_id`={$parent_id} {$condition} AND group_resources.`isactive`=1 ORDER BY group_resources.pin_to_top DESC, group_resources.modifiedon DESC LIMIT {$start},{$end}");
    }


    /**
     * Permanently delete the resource
     * @return int 1 on success, 0 on failure
     */
    public function deleteIt(): int {
        global $_COMPANY,$_ZONE;       

        $continue = true;
        if ($this->isFile()) {
            $continue = $_COMPANY->deleteFileFromSafe($this->val('resource'), 'GROUP_RESOURCE');
        } elseif ($this->isFolder()) {
            $no_of_child_rows = self::DBGet("SELECT count(1) AS cnt FROM `group_resources` WHERE `group_resources`.`companyid`='{$_COMPANY->id()}' AND `group_resources`.`zoneid`='{$_ZONE->id()}' AND parent_id={$this->id}")[0]['cnt'];
            if ($no_of_child_rows > 0) {
                return -1;
            }
        }

        if ($continue) {
            $retVal = self::DBMutate("DELETE FROM `group_resources` WHERE `group_resources`.`companyid`='{$_COMPANY->id()}' AND `group_resources`.`zoneid`='{$_ZONE->id()}' AND resource_id={$this->id}");

            if ($retVal) {	
                self::LogObjectLifecycleAudit('delete', 'resource', $this->id(), 0); 		
                                
            }
            return $retVal;
        }
        
        return 0;
    }

	public function __toString() {
		return "Resource ". parent::__toString();
	}

	/**
	 * Pin/Un-pin resource
	 */
	public function pinUnpinResource(int $type) {
		global $_COMPANY,$_ZONE;
		$pin_to_top = ($type == 1) ? 1 : 0;
		$retVal = self::DBUpdate("UPDATE `group_resources` SET `pin_to_top`={$pin_to_top} where `group_resources`.`companyid`='{$_COMPANY->id()}' AND `group_resources`.`zoneid`='{$_ZONE->id()}' AND groupid={$this->val('groupid')} AND  `resource_id`={$this->id}");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'resource', $this->id(), 0, ['pint_to_top'=>$pin_to_top]);
        }
	}

    /**
     * Moves the resource to new parent. If the new parent has different chapter or channel scope then the moved folder
     * along with all of its children will be moved to the new scope
     * @param int $parent_id
     * @return int -1 on error if the resource cannot be moved to the new scope.
     */
	public function moveResourceIntoFolder(int $parent_id) : int
    {
        global $_COMPANY,$_ZONE;

        $existing_scope_str = $this->val('chapterid').'_'.$this->val('channelid');

        // Calculate new scope string
        $new_chapterid = 0;
        $new_channelid = 0;
        if ($parent_id >0) {
            $parent_resource = Resource::GetResource($parent_id);
            $new_chapterid = (int)$parent_resource->val('chapterid');
            $new_channelid = (int)$parent_resource->val('channelid');
        }
        $new_scope_str = $new_chapterid.'_'.$new_channelid;

        // Move the folder if scope change is allowed
        if (($existing_scope_str == '0_0' || $new_scope_str == '0_0' || $existing_scope_str == $new_scope_str)) {
            $retVal = self::DBUpdate("UPDATE `group_resources` SET `parent_id`={$parent_id},`pin_to_top`='0' where `group_resources`.`companyid`='{$_COMPANY->id()}' AND `group_resources`.`zoneid`='{$_ZONE->id()}' AND groupid={$this->val('groupid')} AND  `resource_id`={$this->id}");
            if ($retVal) {
                self::LogObjectLifecycleAudit('state_change', 'resource', $this->id(), 0, ['parentid'=>$parent_id]);
                $this->updateResourceScope($new_chapterid, $new_channelid);
            }
        } else {
            $retVal = -1;
        }
	    return $retVal;
	}

    public function updateFolderIcon(array $file)
    {
        global $_COMPANY, $_ZONE;
        global $db;

        if (empty($file)) {
            return 0;
        }

        $tmp = $file['tmp_name'];
        $extension = $db->getExtension(basename($file['name']));

        // We need only 50x50 pixes image, but for saving perspective we will save a max of 100 pixes.
        $tmp 	= $_COMPANY->resizeImage($tmp, $extension, 100);

        // Check resized filesize here. Max filesize that cah be uploaded is 5MB
        if (filesize($tmp) > 1024*1000) {
            Logger::Log("Resource::updateFolderIcon Unable to upload the folder icon as it is over 1MB ");
            return 0;
        }

        $actual_name = "link_cover_" . teleskope_uuid() . "." . $extension;
        $resource = $_COMPANY->saveFile($tmp, $actual_name, 'ICON');

        if (empty($resource)) {
            Logger::Log('Resource::updateFolderIcon Unable to upload the folder icon file');
            return 0;
        }

        // Folder icon is saved as the resource since folder does not have any value for `resource`
        $retVal = self::DBUpdatePS("UPDATE `group_resources` SET `resource`=?, `extention`=?, modifiedon=NOW() WHERE `companyid`=? AND `zoneid`=? AND resource_id=?", 'ssiii', $resource, $extension, $_COMPANY->id(), $_ZONE->id(), $this->id());

        if ($retVal) {
            self::LogObjectLifecycleAudit('update', 'resource', $this->id(), 0);
        }
        return $retVal;
    }

    public function updateResource (string $resource_name, string $resource_description,string $resource, ?array $file = null){
        global $_COMPANY,$_ZONE;
        global $_USER, $db;

        if ($this->isLink()) {
            // Resource can be changed only for the link types
            $retVal = self::DBUpdatePS("UPDATE `group_resources` SET `resource_name`=?, `resource_description`=?, `resource`=?, modifiedon=NOW() WHERE `companyid`=? AND `zoneid`=? AND  resource_id=?", 'xxxiii', $resource_name, $resource_description, $resource,$_COMPANY->id(),$_ZONE->id(), $this->id());

        } else {

            $s3name = $this->val('resource');
            $extension = $this->val('extention');
            $size = $this->val('size');
            if ($file) {
                $tmp = $file['tmp_name'];
                $extension = $db->getExtension(basename($file['name']));
                $s3name = 'resource_' . teleskope_uuid() . '.' . $extension;
                $size = $file['size'];
                $_COMPANY->saveFileInSafe($tmp, $s3name, 'GROUP_RESOURCE');
            }

            // For folder and files only the name and description can be changed
            $retVal = self::DBUpdatePS("UPDATE `group_resources` SET `resource_name`=?, `resource_description`=?, `resource` = ?, `extention` = ?, `size` = ?, modifiedon=NOW() WHERE `companyid`=? AND `zoneid`=? AND resource_id=?", 'xxssiiii', $resource_name, $resource_description, $s3name, $extension, $size, $_COMPANY->id(), $_ZONE->id(),$this->id());
          
            if ($file) {
                $_COMPANY->deleteFileFromSafe($this->val('resource'), 'GROUP_RESOURCE');
            }
        }
        if ($retVal) {			
            self::LogObjectLifecycleAudit('update', 'resource', $this->id(), 0);
        }
        return $retVal;
    }
    
    public function updateResourceScope (int $chapterid, int $channelid){
	    global $_COMPANY,$_ZONE;
        if ($this->val('chapterid') == $chapterid && $this->val('channelid') == $channelid) {
            return 1;
        }

        $retVal = self::DBUpdate("UPDATE `group_resources` SET `chapterid`={$chapterid}, `channelid`={$channelid}, modifiedon=NOW() WHERE `companyid`='{$_COMPANY->id()}' AND `zoneid`='{$_ZONE->id()}' AND resource_id={$this->id}");
        if ($retVal) {
            self::LogObjectLifecycleAudit('state_change', 'resource', $this->id(), 0, ['scope_chapterid'=>$chapterid, 'scope_channelid' => $channelid]);
        }
        if ($this->isFolder()) {
            $subfolders = $this->getAllSubfolderResources();
            foreach ($subfolders as $subfolder) {
                $subfolder->updateResourceScope($chapterid,$channelid);
            }
        }
        return 1;
    }

    /**
     * @param int $groupid
     * @param int $resource_type
     * @param string $resource_name
     * @param string $resource
     * @param string $resource_description
     * @param string $extension
     * @param int $parent_id
     * @param int $size
     * @return int resourceid on success or 0 on error
     */
    private static function _CreateNewResource(int $groupid, int $resource_type, string $resource_name, string $resource, string $resource_description, string $extension, int $parent_id, int $size, int $chapterid, int $channelid,int $is_lead_content)
    {
        global $_COMPANY,$_ZONE;
        global $_USER;
       
        if (!in_array($resource_type, array_values(self::RESOURCE_TYPE)))
            return 0; // Only valid resource types can be created

        $retVal = (int)self::DBInsertPS("INSERT INTO `group_resources`(`groupid`, `companyid`, `zoneid`, `userid`, `resource_name`, `resource_type`, `resource`, `resource_description`, `extention`,`parent_id`,`size`, `chapterid`, `channelid`,`is_lead_content`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)", 'iiiixixxxiiiii', $groupid, $_COMPANY->id(), $_ZONE->id(), $_USER->id, $resource_name, $resource_type, $resource, $resource_description, $extension, $parent_id, $size,$chapterid,$channelid,$is_lead_content);

        if ($retVal) {			
            self::LogObjectLifecycleAudit('create', 'resource', $retVal, 0); 
                       
        }
        return $retVal;
    }

    /**
     * @param int $groupid
     * @param string $resource_name
     * @param string $resource_description
     * @param int $parent_id if there is any parent provide parent id, else 0
     * @param string $link the URL of the link
     * @return int resourceid on success, 0 on failure
     */
    public static function CreateNewLink(int $groupid, string $resource_name, string $resource_description, int $parent_id, string $link,int $chapterid, int $channelid,int $is_lead_content=0) :int
    {
        $resource_type = self::RESOURCE_TYPE['RESOURCE_LINK'];
        $extension = 'link';
        $size = 0;

        return self::_CreateNewResource($groupid, $resource_type, $resource_name, $link, $resource_description, $extension, $parent_id, $size, $chapterid, $channelid,$is_lead_content);
    }

    /**
     * @param int $groupid
     * @param string $resource_name
     * @param string $resource_description
     * @param int $parent_id if there is any parent provide parent id, else 0
     * @param array $file value of $FILES['filename'] that you want to upload
     * @return int resourceid on success, 0 on failure
     */
    public static function CreateNewFile(int $groupid, string $resource_name, string $resource_description, int $parent_id, array $file, int $chapterid, int $channelid,int $is_resource_lead_content=0) :int
    {
        global $db;
        global $_COMPANY;
        $resource_type = self::RESOURCE_TYPE['RESOURCE_FILE'];

        if (empty($file)) {
            return 0;
        }

        $tmp 		=	$file['tmp_name'];
        $extension	=	$db->getExtension(basename($file['name']));
        $s3name     =   'resource_'.teleskope_uuid().'.'.$extension;
        $size       =   $file['size'];
        $resource   =   $_COMPANY->saveFileInSafe($tmp,$s3name,'GROUP_RESOURCE');

        if (empty($resource)) {
            Logger::Log('Resource::CreateNewFile Unable to upload the resource file');
            return 0;
        }

        return self::_CreateNewResource($groupid, $resource_type, $resource_name, $s3name, $resource_description, $extension, $parent_id, $size, $chapterid, $channelid,$is_resource_lead_content);
    }

    /**
     * @param int $groupid
     * @param string $resource_name
     * @param string $resource_description
     * @param int $parent_id if there is any parent provide parent id, else 0
     * @return int resourceid on success, 0 on failure
     */
    public static function CreateNewFolder(int $groupid, string $resource_name, string $resource_description, int $parent_id, int $chapterid, int $channelid, int $is_lead_content=0) :int
    {
        $resource_type = self::RESOURCE_TYPE['RESOURCE_FOLDER'];
        $resource = '';
        $extension = 'folder';
        $size = 0;

        return self::_CreateNewResource($groupid, $resource_type, $resource_name, $resource, $resource_description, $extension, $parent_id, $size, $chapterid, $channelid,$is_lead_content);
    }

    /**
     * Archives orphaneds resource files
     */
    public static function S3Cleanup()
    {
        $s3 = Aws\S3\S3Client::factory([
            'version' => 'latest',
            'region' => S3_REGION
        ]);

        $allCompanies = self::DBGet("SELECT companyid, s3_folder FROM companies WHERE isactive=1");

        foreach ($allCompanies as $c) {
            $noOfOrphans = 0;
            $noValidated = 0;
            $folder = $c['s3_folder'] . '/resource/';
            $results = array(); // Reset results object as we are running a loop

            try {
                $results = $s3->getPaginator('ListObjects', [
                    'Bucket' => S3_SAFE_BUCKET,
                    'Prefix' => $folder
                ]);

                foreach ($results as $result) {
                    if ($result['Contents']) {
                        foreach ($result['Contents'] as $object) {

                            if (time() - strtotime($object['LastModified']) < 3600) {
                               continue; // Do not process objects that were added less than one hour ago.
                            }

                            $file_parts = explode('/',$object['Key']);
                            $file = end($file_parts);
                            $checkResource = self::DBROGetPS("SELECT `resource_id` FROM `group_resources` WHERE `companyid`={$c['companyid']} AND `resource_type`=2 AND `resource`=?",'x',$file);

                            $tagset = array();
                            if (empty($checkResource)) {
                                $noOfOrphans++;
                                // Add deletion timestamp but do not delete yet
                                $tagset[] = array('Key' => 'tksp_del_time', 'Value' => time());
                            } else {
                                $noValidated++;
                                // Add validation timestamp, unset del timestamp
                                $tagset[] = array('Key' => 'tksp_del_time', 'Value' => '');
                                $tagset[] = array('Key' => 'tksp_val_time', 'Value' => time());
                            }
                            // Add validation time tag.
                            try {
                                $result = $s3->putObjectTagging([
                                    'Bucket' => S3_SAFE_BUCKET,
                                    'Key' => $object['Key'],
                                    'Tagging' => ['TagSet' => $tagset],
                                ]);
                            } catch (\Exception $e) {
                                Logger::Log("Error Resource::S3Cleanup, exception while tagging" . $e->getMessage());
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Logger::Log("Fatal Error Resource::S3Cleanup, ListObjects exception " . $e->getMessage());
            }
            Logger::Log("Resource::S3Cleanup finished for company={$c['companyid']}, update validation tag for {$noValidated}, added delete tag for {$noOfOrphans}", Logger::SEVERITY['INFO']);
        }
    }

}

