<?php
require_once __DIR__.'/head.php';
global $_COMPANY; /* @var Company $_COMPANY */
global $_USER; /* @var User $_USER */

//$job = new DataExportJob();
//$job->delay=100;
//$job->saveAsSftpDelivery('localhost','amanbrar','mani13','DailyReport.csv', 1, $reportid,Teleskope::FILE_FORMAT_CSV,false);

// $job = new DataImportJob();
// $job->delay=100;
// //$job->saveAsSftpGet('localhost','amanbrar','aman','Downloads','DailyReport.csv',1,'TeleOne.csv',false);
// $job->saveAsHttpsGet('https://dev.teleskope.io/api_example.php','jonas','foobar',1,'TeleOne_by_HTTPS.csv',false);

// $meta = array(
//    'Fields' => array(
//        'externalid' => array('ename'=>'ExternalID'),
//        'firstname' => array('ename'=>'First Name'),
//        'lastname' => array('ename'=>'Last Name','pattern'=>array('/(\w).* (\w).*/','/(\w).*/'),'replace'=>array('$1$2','Aman $1')),
//        'email' => array('ename'=>'email'),
//        'jobtitle' => array('ename'=>'jobTitle'),
//        'employeetype' => array('ename'=>'employeeType'),
//        'department' => array('ename'=>'department'),
//        'branchname' => array('ename'=>'officeLocation'),
//        'city' => array('ename'=>'city'),
//        'state' => array('ename'=>'state'),
//        'country' => array('ename'=>'country'),
//        'opco' => array('ename'=>'opco'),
//        'extended' => array(
//            'MU' => array('ename'=>'Market Unit Location'),
//            'Initials' => array('ename'=>'Firstname','pattern'=>'/(\w).*/','replace'=>'I$1'),
//            'OU1' => array('ename'=>'Org Unit Level 1'),
//        )
//    ),
//    'AssignGroups' => array(
//        array(
//            'Filters' => array('Northeast'=>array('ename'=>'Market Unit Location'),'North America'=>array('ename'=>'Market Location')),
//            'groupname'=>array('ename'=>'Metro City'),
//            'zoneid' => 3
//        ),
//        #array(
//        #    'Filters' => array('south'=>array('ename'=>'Market Unit Location')),
//        #    'groupname'=>array('ename'=>'Metro City'),
//        #    'zoneid' => 2
//        #)
//    ),
//    'AddUsers' => array (
//        array(
//            'Filters' => array('1' => array('ename' => 'Email', 'pattern'=>'/.*@.*/','replace'=>'1')),
//            'zoneid' => 2
//        ),
//    ),
// );

// $usersyncjob = new UserSyncFileJob();
// $usersyncjob->delay = 30;

// $usersyncjob->saveAsUserDataSyncType('File_A.csv.pgp',Teleskope::FILE_FORMAT_CSV, 1, $meta, false, '', 7 );

// $meta = array(
//    'Fields' => array(
//        'externalid' => array('ename'=>'ExternalID')
//    ));

// $usersyncjob = new UserSyncFileJob();
// $usersyncjob->delay = 105;
// $usersyncjob->saveAsUserDataDeleteType('File2.json.pgp',Teleskope::FILE_FORMAT_JSON, 1, $meta, false, 'Report_Entry');




echo memory_get_peak_usage();
