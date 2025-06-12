<?php
include __DIR__.'/../../include/dbfunctions.php';
$db = new Hems();
$companies = $_SUPER_ADMIN->super_get("SELECT companyid FROM companies where isactive=1");
foreach ($companies as $company) {
    $companyid = $company["companyid"];
    if (1) {
        $events_data = $_SUPER_ADMIN->super_get("SELECT eventid,attributes from events WHERE companyid={$companyid} AND attributes IS NOT NULL");

        // Reset the data correctly
        if(!empty($events_data)){
            $updated_data = array();
            foreach($events_data as $attribute_data){
                $new_event_attributes = array();
                $event_id = $attribute_data['eventid'];
                // decode the attribute
               $attributeData = json_decode($attribute_data['attributes'],true);


                   foreach ($attributeData['event_volunteer_requests'] as $event_attribute) {
                       $new_event_attributes[] = array(
                           'volunteertypeid' => intval($event_attribute['volunteertypeid']),
                           'volunteer_needed_count' => intval($event_attribute['volunteer_needed_count']),
                           'cc_email' => $event_attribute['ccEmail'] ?? '',
                       );
                   }


                // Convert back to JSON.
                $attributeData['event_volunteer_requests'] = $new_event_attributes;
                $event_attributes_json = json_encode($attributeData);
                // add eventid and json data to an array. Use it to later update the db
                $new_valid_data =  array('eventid'=>$event_id,'attributes'=>$event_attributes_json);
                $updated_data[] = $new_valid_data;
            }
        }

        if(!empty($updated_data)){
            foreach($updated_data as $data){
                $json_data = $data['attributes'];
            // update all data
                $retVal = $_SUPER_ADMIN->super_update("UPDATE `events` SET `attributes`= '{$json_data}' WHERE`companyid`={$companyid} AND `eventid`={$data['eventid']}");
            }
        }
    }
}