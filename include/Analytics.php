<?php
// Do no use require_once as this class is included in Company.php.
date_default_timezone_set('UTC');

class Analytics extends Teleskope
{
    const ANALYTIC_TYPE_AFFINITIES_EVENT = 1;
    const ANALYTIC_TYPE_AFFINITIES_SURVEY = 2;
    const ANALYTIC_TYPE_EMAIL_LOG_AFFINITIES_POST = 3;
    const ANALYTIC_TYPE_EMAIL_LOG_AFFINITIES_EVENT = 4;
    const ANALYTIC_TYPE_EMAIL_LOG_AFFINITIES_NEWSLETTER = 5;

    protected $analyze_type = 0;

    protected function __construct(int $cid, array $fields)
    {
        parent::__construct(-1, $cid, $fields);
    }

    public function __destruct()
    {
    }

    protected function _generateAnalytic (): array
    {
        // Will be implmented in subclass
    }

    public function generateAnalytics() : array
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        // Security Check
        // - validate the global Company context matches the company for the analytic
        // - or the analytic is for global use i.e. $this->cid() == 0
        if (!$_COMPANY || ($this->cid() && ($_COMPANY->id() != $this->cid()))) {
            Logger::Log("Fatal: Cannot generate analytic {$this->id} that does not belong to the company {$_COMPANY->id()}");
            return array();
        }

        if (empty($this->fields))
            return array();

        return $this->_generateAnalytic();
    }

    public static function GetAnalyticRec(string $analyticid): array
    {
        global $_COMPANY; /* @var Company $_COMPANY */

        return self::DBGetPS("SELECT * FROM `company_analytics` WHERE companyid=? AND (analyticid=? AND isactive=1) LIMIT 1",'ix',$_COMPANY->id(), $analyticid);
    }

    /**
     * Creates analytic record. Generates a unique analytic id as well.
     * @param string $analyticname
     * @param string $analyticdescription
     * @param int $analytictype
     * @param string $analyticmeta
     * @return int
     */
    public static function CreateAnalyticRec (string $analyticname, string $analyticdescription, int $analytictype, string $purpose, string $analyticmeta) : string
    {
        global $_COMPANY,$_ZONE; /* @var Company $_COMPANY */
        global $_USER; /* @var User $_USER */

        $analyticid = $_COMPANY->val('subdomain')."_".time();
        

        $q = self::DBUpdatePS("INSERT INTO `company_analytics`(`analyticid`, `companyid`, `zoneid`, `analyticname`, `analyticdescription`, `analytictype`, `analyticmeta`, `purpose`, `createdby`) VALUE (?,?,?,?,?,?,?,?,?)",'xiissixsi',$analyticid,$_COMPANY->id(),$_ZONE->id(),$analyticname,$analyticdescription,$analytictype,$analyticmeta,$purpose, $_USER->id());
        if ($q) {
            return $analyticid;
        } else {
            return '';
        }
    }

    /**
     * Updates the analytic record. Note companyid and analytictype cannot be updated. Also isactive has been left out as we do not want to use this method for isactive update
     * @param string $analyticid
     * @param string $analyticname
     * @param string $analyticdescription
     * @param string $analyticmeta
     * @return int
     */
    public static function UpdateAnalyticRec (string $analyticid, string $analyticname, string $analyticdescription, int $analytictype, string $purpose, string $analyticmeta) : int
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        return self::DBUpdatePS("UPDATE company_analytics SET analyticname=?, analyticdescription=?, analytictype=?, purpose=?, analyticmeta=? WHERE companyid=? AND (analyticid=?)",'ssisxis',$analyticname,$analyticdescription,$analytictype,$purpose,$analyticmeta,$_COMPANY->id(), $analyticid);
    }

    /**
     * Marks the analytic for deletion
     * @param string $analyticid
     * @return int
     */
    public static function DeleteAnalyticRec (string $analyticid) : int
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        return self::DBUpdatePS("UPDATE company_analytics SET isactive=100 WHERE companyid=? AND (analyticid=?)",'ix',$_COMPANY->id(),$analyticid);
    }
}

class AnalyticAffinitiesEvent extends Analytics {
    public const META = array (
        'Fields' => array(),
        'Options' => array(
        ),
        'Filters' => array(
            'groupid' => 0,
            'eventid' => 0,
            'month' => ''  // Format : Y-m 2021-03
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
        $this->analyze_type = self::ANALYTIC_TYPE_AFFINITIES_EVENT;
    }

    protected function _generateAnalytic (): array
    {
        global $_COMPANY, $_ZONE, $db;
        $dbc = GlobalGetDBROConnection();
        $meta = $this->fields;
        $rows = array();
        $groupid = 0;
        $eventid = 0;
        $month = date("Y-m");
        $eventArray = array();
        $eventsStats = array();
        $departments = array();
        $branches = array();
        $chapters = array();
        $channels = array();

        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupid'])) {
            $groupid = $meta['Filters']['groupid'];
        }
        
        if (!empty($meta['Filters']) && !empty($meta['Filters']['eventid'])) {
            $eventid = $meta['Filters']['eventid'];
        }

        if (!empty($meta['Filters']) && !empty($meta['Filters']['month'])) {
            $month = $meta['Filters']['month'];
        }
        $_SESSION['analytic_month']  = $month;
       
        if ($eventid){
            $rows[] = Event::GetEvent($eventid);
        } else {
            $rows = Event:: GetAllEventsInGroupByMonth($groupid,$month);
        }
        
        $rsvpOptionChoosed = array();
        $attendenceOptionChoosed = array();
        $chaptersArray = array();
        $channelArray = array();
        $departmentsArray = array();
        $branchOfficesArray = array();
        $attendenceOptions = array();
        $i = 0;
        foreach($rows as $event){ # Loop for Create answers
            #1 Answer - RSVPS
            $rsvpsList = $event->getEventRSVPsList();
            //echo "<pre>";print_r($rsvpsList);
            foreach($rsvpsList as $rsvp){
                $eventsStatsRow = array();
                $label = Event::GetRSVPLabel($rsvp['joinstatus']);
                if($label){
                    $eventsStatsRow['rsvps'] = $label;
                }
                # Ansers departments and office locations
               
                #4 Answers Departments
                $departmentName = $rsvp['department'] ?: 'Unknown';
                if (($d = array_search($departmentName, $departments)) === FALSE) {
                    $d = array_push($departments, $departmentName) - 1;
                }
                $eventsStatsRow['departments'] = 'item' . $d;
                #5 Answers Office Locations
                $branchName = $rsvp['branchname'] ?: 'Unknown';
                if (($b = array_search($branchName, $branches)) === FALSE) {
                    $b = array_push($branches, $branchName) - 1;
                }
                $eventsStatsRow['officeLocation'] = 'item' . $b;

                #2 Answer - Attendences
                $attendence = empty($rsvp['checkedin_date']) ? 'Not Attended' : 'Attended';
                if(!in_array($attendence,$attendenceOptions)){
                    $attendenceOptions[] = $attendence;
                }
                $eventsStatsRow['attendence'] = $attendence;
                array_push($eventsStats,$eventsStatsRow);
            }

            ## TODO NOT Chapter and Channel dimension
            ## This dimension is out of scope ##

            #3 Answer Chapters and Channels
            // if multiple events selected then chapter and channel analytics considered
            // if (($_COMPANY->getAppCustomization()['chapter']['enabled'] || $_COMPANY->getAppCustomization()['channel']['enabled'])){
            //     #3 Answers-Chapters
            //     if($event->val('chaptername')){
            //         if (($chpt = array_search($event->val('chaptername'), $chapters)) === FALSE) {
            //             $chpt = array_push($chapters, $event->val('chaptername')) - 1;
            //         }
            //         $chaptersArray[] = 'item' . $chpt;
            //     }
            //     #4 Answers-Channels
            //     if($event->val('channelname')){
            //         if (($chnl = array_search($event->val('channelname'), $channels)) === FALSE) {
            //             $chnl = array_push($channels, $event->val('channelname')) - 1;
            //         }
            //         $channelArray[] = 'item' . $chnl;
            //     }
               
            // }
            $i++;
        }

        ### Questions Section
        $eventInput = array();
        $eventInput['name'] = 'Page';
        #1 RSVP
        $rspvsOptions = Event::RSVP_TYPE;
        foreach($rspvsOptions as $option => $value){
            $label = Event::GetRSVPLabel($value);
            if ($label){
                $rspvsOptionsArray[] = $label;
            }
            
        }
        $analytics = array();
        $analytics['name'] = 'rsvps';
        $analytics['title'] = 'RSVPs';
        $analytics['type'] = 'radiogroup';
        $analytics['choices'] = $rspvsOptionsArray;
        $eventInput['elements'][] = $analytics;
        
        #2 Attendences
        if ($event->isPublished() && $event->hasEnded()) {
            $analytics = array();
            $analytics['name'] = 'attendence';
            $analytics['title'] = 'Attendance'; // This is the correct spelling
            $analytics['type'] = 'radiogroup';
            $analytics['choices'] = $attendenceOptions;
            $eventInput['elements'][] = $analytics;
        }

        #3 Chapters and Channels if Multiple Events
        // if (!$eventid && $_COMPANY->getAppCustomization()['chapter']['enabled'] && $_COMPANY->getAppCustomization()['channel']['enabled']){ 
           
        //     #3.1 Chapters
        //     if (count($chapters)){
        //         $chapterQuestion = array();
        //         $chapterQuestion['name'] = 'chapters';
        //         $chapterQuestion['title'] = $_COMPANY->getAppCustomization()['chapter']['name-short'];
        //         $chapterQuestion['type'] = 'radiogroup';
        //         $choices = array();
        //         for ($x = 0; $x < count($chapters); $x++) {
        //             $choices[] = array('value' => 'item' . $x, 'text' => $chapters[$x]);
        //         }
        //         $chapterQuestion['choices'] = $choices;
        //         $eventInput['elements'][] = $chapterQuestion;
        //         #Chapters Answers
        //         //$eventsStats['chapters'] = $chaptersArray;
        //     }
        //     # 3.2 Channels
        //     if (count($channels)){
        //         $channelQuestion = array();
        //         $channelQuestion['name'] = 'channels';
        //         $channelQuestion['title'] = $_COMPANY->getAppCustomization()['channel']['name-short'];
        //         $channelQuestion['type'] = 'radiogroup';
        //         $choices = array();
        //         for ($x = 0; $x < count($channels); $x++) {
        //             $choices[] = array('value' => 'item' . $x, 'text' => $channels[$x]);
        //         }
        //         $channelQuestion['choices'] = $choices;
        //         $eventInput['elements'][] = $channelQuestion;
        //         #Channel Answers
        //         //$eventsStats['channels'] = $channelArray;
        //     }
        // }
        
        #4 Departments
        if (count($departments)){
            $departmentQuestion = array();
            $departmentQuestion['name'] = 'departments';
            $departmentQuestion['title'] = 'Departments';
            $departmentQuestion['type'] = 'radiogroup';
            $choices = array();
            for ($x = 0; $x < count($departments); $x++) {
                $choices[] = array('value' => 'item' . $x, 'text' => $departments[$x]);
            }
            $departmentQuestion['choices'] = $choices;
            $eventInput['elements'][] = $departmentQuestion;
        }
       
        #5 Office Locations
        if(count($branches)){
            $branchesQuestion = array();
            $branchesQuestion['name'] = 'officeLocation';
            $branchesQuestion['title'] = 'Office Locations';
            $branchesQuestion['type'] = 'radiogroup';
            $choices = array();
            for ($x = 0; $x < count($branches); $x++) {
                $choices[] = array('value' => 'item' . $x, 'text' => $branches[$x]);
            }
            $branchesQuestion['choices'] = $choices;
            $eventInput['elements'][] = $branchesQuestion;
        }
        
        #Final Questions
        $eventArray[] = $eventInput;

        return array('questions'=>array('completedHtml' => 'Thanks', 'pages' => $eventArray),'answers'=>$eventsStats);
        
    }

    public static function GetDefaultAnalyticMeta(): array
    {
        global $_COMPANY; /* @var Company $_COMPANY */
        global $_ZONE;
        $analyticmeta = null;
        $row = self::DBGet("SELECT * FROM company_analytics WHERE companyid={$_COMPANY->id()} AND (zoneid={$_ZONE->id()} AND isactive=1) LIMIT 1");
        if(count($row) && $row[0]['analyticmeta']){
            $analyticmeta = json_decode($row[0]['analyticmeta'],true);
        } else {
            $analyticmeta = self::META;
        }
        return $analyticmeta;
    }
}

class EmailLogAnalytic extends Analytics {
    public const META = array (
        'Fields' => array(),
        'Options' => array(
        ),
        'Filters' => array(
            'groupid' => 0,
            'sectionid' => 0,
            'section'=>'POST', // can be EVENT or NEWSLETTER
            'month' => ''  // Format : Y-m 2021-03
        )
    );

    public function __construct(int $cid, array $fields)
    {
        parent::__construct($cid, $fields);
        $this->analyze_type = self::ANALYTIC_TYPE_EMAIL_LOG_AFFINITIES_POST;
    }

    protected function _generateAnalytic (): array
    {
        global $_COMPANY, $_ZONE, $db;
        $dblog = GlobalGetDBLOGConnection();
        $meta = $this->fields;
        $meta = $meta['fields'];
        $data = array();
        $analyticsArray = array();
        $analyticsStats = array();
        $groupid = 0;
        $sectionid = 0;
        $section = '1';

        $type = array('1'=>'POST','2'=>'EVENT','3'=>'NEWSLETTER');

        if (!empty($meta['Filters']) && !empty($meta['Filters']['groupid'])) {
            $groupid = $meta['Filters']['groupid'];
        }
        
        if (!empty($meta['Filters']) && !empty($meta['Filters']['sectionid'])) {
            $sectionid = $meta['Filters']['sectionid'];
        }

        if (!empty($meta['Filters']) && !empty($meta['Filters']['section'])) {
            $section = $meta['Filters']['section'];
        }
        if (!empty($meta['Filters']) && !empty($meta['Filters']['month'])) {
            $month = $meta['Filters']['month'];
        }
        $_SESSION['analytic_month']  = $month;
        $section = $type[$section] ?: 'POST';

        if ($section == "POST"){
            if ($sectionid){
                $data[] = Post::GetPost($sectionid);
            } else {
                $data = Post:: GetAllPostInGroupByMonth($groupid,$month);
            }
        } elseif ($section == 'EVENT'){
            if ($sectionid){
                $data[] = Event::GetEvent($sectionid);
            } else {
                $data = Event:: GetAllEventsInGroupByMonth($groupid,$month);
            }

        } elseif ($section == "NEWSLETTER"){
            if ($sectionid){
                $data[] = Newsletter::GetNewsletter($sectionid);
            } else {
                $data = Newsletter:: GetAllNewsletterInGroupByMonth($groupid,$month);
            }
        }
        $i = 0;
        $readArray = array();
        $logType = Job::EMAIL_LOG_TYPE;
        $section = $logType[$section];
        foreach($data as $object){ # Loop for Create answers
            $emailLosResource = mysqli_query($dblog,"SELECT `zoneid`, `userid`, `sectionid`, `section`, `version`, `sent`, `read`, `readcount` FROM `email_logs` WHERE `zoneid`='{$_ZONE->id()}' AND `sectionid`='{$object->id()}' AND `section`='{$section}'");
            while (@$rows = mysqli_fetch_assoc($emailLosResource)) {
                if($rows['readcount']>0){
                    $readArray[] = 1;
                } else {
                    $readArray[] = 0;
                }
            }
            $i++;
        }

        ### Questions Section
        $postInput = array();
        $postInput['name'] = 'Page';
        
        $analytics = array();
        $analytics['name'] = 'trackingLog';
        $analytics['title'] = 'Email Tracking';
        $analytics['type'] = 'radiogroup';
        $analytics['choices'] = array(array('value'=>1,'text'=>'Email read'),array('value'=>0,'text'=>'Email Not Read'));
        $postInput['elements'][] = $analytics;
        # Tracking Answers
        $analyticsStats['trackingLog'] = $readArray;
        #Final Questions
        $analyticsArray[] = $postInput;
        return array('questions'=>array('completedHtml' => 'Thanks', 'pages' => $analyticsArray),'answers'=>array($analyticsStats));
        
    }

}
