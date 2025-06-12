<?php
require_once __DIR__.'/../head.php';
ini_set('max_execution_time', 10000);


//
//
//
//
//
$check2 = date('Ymd');
if ($check2 > '20230904') {
    echo "<p>Migration expired</p>";
    echo "<p>Exiting</p>";
    exit();
}
//
//
//
//
//




$db	= new Hems();

ini_set('max_execution_time', 6000);

$pagetitle = "Event custom field data migration";
$error = null;
$success = null;
$successCount = 0;
$successEventCount = 0;


if (isset($_POST['submit'])){

    $companies = $_SUPER_ADMIN->super_get("SELECT `companyid` from `companies` WHERE 1");
    foreach($companies as $company){
        $zones = $_SUPER_ADMIN->super_get("SELECT `zoneid` FROM `company_zones` WHERE `companyid`={$company['companyid']} ");
        foreach($zones as $zone){
            // Migrate Custom Fields
            $customFields = $_SUPER_ADMIN->super_get("SELECT * FROM `event_custom_fields` WHERE `companyid`={$company['companyid']} AND `zoneid`={$zone['zoneid']} AND `custom_fields_type`!=3");
            
            foreach($customFields as $customField) {
                $custom_fields_options = $customField['custom_fields_options'];
                if (!empty($custom_fields_options)) {
                    $custom_fields_options = json_decode($custom_fields_options, true);
                    foreach($custom_fields_options as $custom_fields_option) {
                        $_SUPER_ADMIN->super_update_ps("INSERT INTO `event_custom_field_options`( `custom_field_id`, `custom_field_option`, `custom_field_option_note`, `createdon`, `modifiedon`) VALUES (?,?,?,NOW(),NOW())",'ixx',$customField['custom_field_id'],$custom_fields_option,'');
                        $successCount++;
                    }
                }
            }
        }

        // Migrate Events
        $events = $_SUPER_ADMIN->super_get("SELECT `eventid`, `custom_fields` FROM `events` WHERE `companyid`={$company['companyid']} AND `custom_fields` IS NOT NULL;");

        foreach($events as $event){
            $custom_fields = json_decode($event['custom_fields'],true);
            if (empty($custom_fields)) {
                continue;
            }
            $eventCustomField = array();
            foreach($custom_fields as $custom_field){
                $custom_field_id = intval($custom_field['custom_field_id']);
                $value = $custom_field['value'];
                if (!empty($value)){
                    $customFieldOptions = getCustomFieldOptions($custom_field_id);
                    

                    if (!empty($customFieldOptions)) {
                        if (is_array($value)){
                            $valueIds = array();
                            foreach($value as $v){
                                $optionId = Arr::SearchColumnReturnColumnVal($customFieldOptions,$v,'custom_field_option','custom_field_option_id');
                                $valueIds[] = $optionId;
                            }
                            $valueIds = array_map('intval', $valueIds);
                            $eventCustomField[] = array('custom_field_id'=>$custom_field_id, 'value'=> $valueIds);

                        } else {
                            $optionId = Arr::SearchColumnReturnColumnVal($customFieldOptions,$value,'custom_field_option','custom_field_option_id');
                            $optionId = intval($optionId);
                            $eventCustomField[] = array('custom_field_id'=>$custom_field_id, 'value'=> array($optionId));
                        }
                    } else {
                        $eventCustomField[] = array('custom_field_id'=>$custom_field_id, 'value'=> $value);
                    }
                } else {
                    $eventCustomField[] = array('custom_field_id'=>$custom_field_id, 'value'=> $value);
                }
            }

            $eventCustomField = json_encode($eventCustomField);

            $_SUPER_ADMIN->super_update_ps("UPDATE `events` SET custom_fields=? WHERE `eventid`={$event['eventid']}",'x', $eventCustomField);
            $successEventCount++;
           
        }
    }
    if ($successCount>0 || $successEventCount > 0){
        $success = 'Total events custom fileds - '.$successCount. ' AND Total events - '.$successEventCount. ' updated.';
    }

}


function getCustomFieldOptions($custom_field_id) {
    global $_SUPER_ADMIN;
    return $_SUPER_ADMIN->super_get("SELECT `custom_field_option_id`, `custom_field_id`, `custom_field_option`, `custom_field_option_note` FROM `event_custom_field_options` WHERE `custom_field_id`='{$custom_field_id}'");
}

?>
<html><head></head>
<body>
    <h3>Event Custom Fields Data Migration</h3>

    <?php if(!empty($error)){ ?>
    <p style="color:red;"><strong><?= $error;?></strong> </p>
    <?php } ?>
    <?php if(!empty($success)){ ?>
    <p style="color:green;"><strong><?= $success;?></strong> </p>
    <?php } ?>


    <form class="form-horizontal" method="post" action="" enctype="multipart/form-data">
        <p style="color:rebeccapurple;font-weight: bold;">Warning: Please run this page once. This code will migrate all companies event custom fields data once.</p>
        <button type="submit" name="submit" class="btn btn-primary">Start Data Migration</button>
    </form>

</body>
</html>





