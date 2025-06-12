<?php
require_once __DIR__.'/head.php';

// Authorization Check
if (!$_USER->canManageAffinitiesContent()) {
	header("HTTP/1.1 403 Forbidden (Access Denied)");
	exit();
}

$topictype = $_GET['topictype'] ?? 'EVT';
$topicEnglishName = Teleskope::TOPIC_TYPES_ENGLISH[$topictype];

$pagetitle = sprintf("Add New %s Custom Field", $topicEnglishName);

$topic_class = Teleskope::TOPIC_TYPE_CLASS_MAP[$topictype];
$customFields = call_user_func([$topic_class, 'GetEventCustomFields'], false);

$custom_field_id =0;
$edit = null;
if(isset($_GET['edit'])){
    $custom_field_id = $_COMPANY->decodeId(($_GET['edit']));
    $pagetitle = "Update Custom Field";
    $edit = Event::GetEventCustomFieldDetail($custom_field_id);
}

if(isset($_POST['submit'])){
	$check = $db->checkRequired(array('Field Name'=>$_POST['custom_field_name']));	
	if($check){
		if(!isset($_GET['edit'])){
			$_SESSION['error_msg'] =  "Error: Not a valid input on $check.";
			$_SESSION['error'] = time();
			Http::Redirect("new_event_custom_field?topictype={$topictype}&msg=$msg");
		}else{
			$_SESSION['error_msg'] =  "Error: Not a valid input on $check.";
			$_SESSION['error'] = time();
			Http::Redirect("event_custom_fields?topictype={$topictype}&msg=$msg");
		}
    }
	$custom_field_name 	=	trim($_POST['custom_field_name']);
	//$custom_field_name = htmlspecialchars_decode($custom_field_name);
	$custom_fields_type	=	(int)$_POST['custom_fields_type'];
	$is_required = 0;
	if (isset($_POST['is_required'])){
		$is_required = (int)$_POST['is_required'];
	}
    $custom_fields_options = array();
	$custom_fields_options_note = array();
	$custom_field_option_ids = array();
	if (($custom_fields_type !== 3) && ($custom_fields_type !== 4)) {
		$custom_fields_options = $_POST['custom_fields_options']??array();
		$custom_fields_options_note = $_POST['custom_fields_options_note']??array();
		$enc_custom_field_option_ids = $_POST['custom_field_option_id']??array();
		$cleanOptions = [];
		$cleanOptionsNotes = [];
		$i=0;
		foreach($custom_fields_options as $option){
			$cleanOptions[] = $option;
			$cleanOptionsNotes[] = $custom_fields_options_note[$i];
			$custom_field_option_ids[] = $_COMPANY->decodeId($enc_custom_field_option_ids[$i]);
			$i++;
        }
        $custom_fields_options = $cleanOptions;
		$custom_fields_options_note = $cleanOptionsNotes;
	}

	if ((($custom_fields_type !== 3) && ($custom_fields_type !== 4)) && empty($custom_fields_options)) {
		$_SESSION['error_msg'] = "Error: Options are required fields.";
		$_SESSION['error'] = time();
		Http::Redirect("event_custom_fields?topictype={$topictype}");
	}
	$custom_field_note	=	$_POST['custom_field_note'];
	$visibleIfLogic = array();
	if (isset($_POST['visible_if'])) {
		$if_field_selected = $_COMPANY->decodeId($_POST['if_field_selected']);
		$selectedField = Event::GetEventCustomFieldDetail($if_field_selected);
		if ($selectedField){
			$select_option = $_POST['select_option'];
			$clean_select_option = array();
			if (($selectedField['custom_fields_type'] != 3) && ($selectedField['custom_fields_type'] != 4)) {
				foreach($select_option as $sOption) {
					$id = $_COMPANY->decodeId($sOption);
					$clean_select_option[] = $id;
				}
			} else {
				$clean_select_option[] = trim($select_option[0]);
			}

			if ($if_field_selected > 0 && count($clean_select_option)>0){
				$visibleIfLogic = array (
					'custom_field_id' => $if_field_selected,
					'options' => $clean_select_option
				);
			}
		}
	}
	$visibleIfLogic = json_encode($visibleIfLogic);

	$topictype = $_POST['topictype'] ?? 'EVT';

	Event::AddUpdateEventCustomField($custom_field_id,$custom_field_name, $custom_fields_type, $is_required, $custom_field_note,$visibleIfLogic,$custom_field_option_ids,$custom_fields_options,$custom_fields_options_note, $topictype);
	if ($custom_field_id){
		$_SESSION['updated'] = time();
	} else{
		$_SESSION['added'] = time();
	}

	Http::Redirect("event_custom_fields?topictype={$topictype}");

}
include(__DIR__ . '/views/header.html');
include(__DIR__ . '/views/new_event_custom_field.html');
?>
