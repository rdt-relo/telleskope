<?php
/* This file requires $zoneIdsArray, $regionIdsArray, $groupCategoryArray, $groupIdsArray, $chapterIdsArray to be set  before we reach here */
$regionIdsArray ??= null;
$groupCategoryArray ??= [];
$showZoneName = count($zoneIdsArray) > 1;

// Zone Dropdown
// Build shared zone array including the current zone.
$allZones[] = $_ZONE->id();
if ($_ZONE->val('calendar_sharing_zoneids')) {
    array_push($allZones, ... Str::ConvertCSVToArray($_ZONE->val('calendar_sharing_zoneids')));
}

// Event Types Dropdown
$allEventTypes = Event::GetEventTypesByZones($zoneIdsArray, true);
usort($allEventTypes, function($a, $b) {
    return $a['type'] <=> $b['type'];
});
//$showAllEventType[] = [
//                'typeid' => '0',
//                'sys_eventtype' => '0',
//                'companyid' => $_COMPANY->id(),
//                'zoneid' => $_ZONE->id(),
//                'type' => 'Show all',
//                'attributes' => null,
//                'isactive' => 1
//            ];

// Region Dropdown
$allRegions = $_COMPANY->getRegionsByZones($zoneIdsArray);
$selectAllRegions = false;
if (!empty($regionIdsArray) && $regionIdsArray[0] == 'all') {
    $selectAllRegions = true;
}

$selectAllCategories = false;
if (empty($groupCategoryArray) || $groupCategoryArray[0] == 'all') {
    $selectAllCategories = true;
}

$group_chapter_rows = Group::GetGroupsAndChapterRows($zoneIdsArray, ($selectAllRegions ? null : $regionIdsArray), ($selectAllCategories ? null : $groupCategoryArray));
// Group Dropdown
$allGroups = Arr::KeepColumns($group_chapter_rows, ['groupid', 'groupname', 'group_zoneids']);
$allGroups = Arr::Unique($allGroups, 'groupid');
usort($allGroups, function($a, $b) {
    return $a['groupname'] <=> $b['groupname'];
});
$groups = $allGroups;

$selectAllGroups = false;
if (!empty($groupIdsArray) && $groupIdsArray[0] == 'all') {
    $selectAllGroups = true;
} 

// Chapter Dropdown
// Note: the following logic is also applied in ajax_events > getFilteredChapterList and in iframe/calendar.php
// Please co-ordinate changes if you change the following chapter related logic
$allChapters = Arr::KeepColumns($group_chapter_rows, ['chapterid', 'chaptername', 'groupid']);
usort($allChapters, function($a, $b) {
    return $a['chaptername'] <=> $b['chaptername'];
});

$allChapters = Arr::GroupBy($allChapters, 'chaptername');
//   ... keep only the chapters that have one of the selected groups unless all groups are selected.
$allChapters = array_filter($allChapters, function ($value, $key) use ($groupIdsArray) {
    return !empty($key) && ($groupIdsArray[0] == 'all' || !empty(array_intersect($groupIdsArray, array_column($value,'groupid'))));
}, ARRAY_FILTER_USE_BOTH);

$selectAllChapters = false;
if (!empty($chapterIdsArray) && $chapterIdsArray[0] == 'all') {
    $selectAllChapters = true;
}

?>

<div class="col-sm-4 by-zones">
    <label id="labelForZones" class="control-lable calendar-filter-lable" for="byZones"><?=sprintf(gettext('Filter by %s'), 'zone')?></label>
    <select class="form-control options-header-option" id="byZones" onchange="setCalendarFilterState('byZones');refreshCalendarDyanamicFilters(1, 'byZones');" multiple>
        <?php foreach ($allZones as $zid) {
            $sel = "";
            if (empty($zoneIdsArray) && $zid == $_ZONE->id()) {
                $sel = 'selected';
            } elseif(in_array($zid, $zoneIdsArray)){
                $sel = 'selected';
            }
            $z = $_COMPANY->getZone($zid);
            if (!$z) {
                continue;
            }
            ?>
            <option value="<?=$_COMPANY->encodeId($zid)?>" <?= $sel; ?> ><?=$z->val('zonename');?></option>
        <?php  } ?>
    </select>

</div>

<div  class="col-sm-4 by-region">
    <label id="labelForRegion" class="control-lable calendar-filter-lable" for="byregion"><?=sprintf(gettext('Filter by region'))?></label>
    <?php 	if(!empty($allRegions)){ ?>
        <select class=" form-control options-header-option" id="byregion" onchange="setCalendarFilterState('byregion');refreshCalendarDyanamicFilters(0, 'byRegion');" multiple>
            <?php 	foreach ($allRegions as $z){ ?>
            <option value="<?= $_COMPANY->encodeId($z['regionid']); ?>" <?= ($selectAllRegions || in_array($z['regionid'], $regionIdsArray)) ? 'selected' : ''?>><?= $z['region'] ?></option>
            <?php	} ?>
            <?php if (0) { // Commented no region ?>
            <option value="<?= $_COMPANY->encodeId(0); ?>" <?= ($selectAllRegions || in_array(0, $regionIdsArray)) ? 'selected' : ''?>><?= gettext('No Region'); ?></option>
            <?php } ?>
        </select>
    <?php	}else{ ?>
        <select class="form-control" id="byregion" style="color:#ababab;" multiple>
            <option value="<?=$_COMPANY->encodeId(0);?>" selected>-<?= gettext('No Region'); ?>-</option>
        </select>
    <?php	} ?>
</div>

<div class="col-sm-4 by-group">
    <label id="labelForGroup" class="control-lable calendar-filter-lable" for="bygroup"><?=sprintf(gettext('Filter by %s'), 'group (' . $_COMPANY->getAppCustomization()['group']['name-short']). ')'?></label>
    <select class="form-control options-header-option selectpicker" multiple id="bygroup" onchange="setCalendarFilterState('bygroup');getFilteredChapterList();">
        <option data-section="0" value="<?= $_COMPANY->encodeId(0); ?>" <?= ($selectAllGroups || in_array(0, $groupIdsArray)) ? 'selected' : '' ?>><?= $_COMPANY->getAppCustomization()['group']['groupname0']; ?></option>
        <?php 	foreach ($groups as $g0) { ?>
        <option data-section="1" value="<?= $_COMPANY->encodeId($g0['groupid']); ?>" <?= ($selectAllGroups || in_array($g0['groupid'],$groupIdsArray)) ? 'selected' :  '' ?>>&ensp;<?= $g0['groupname'] . ($showZoneName ? ' ('. $_COMPANY->getZone($g0['group_zoneids'])->val('zonename') .')' : '') ; ?></option>
        <?php	} ?>
    </select>
</div>

<div class="col-sm-4 by-distance">
    <?php if(false){ ?>
        <?php if ($_COMPANY->getAppCustomization()['calendar']['location_filter']) { ?>
        <label id="labelForDistance" class="control-lable calendar-filter-lable" for="bydistance"><?=sprintf(gettext('Filter by %s'), 'distance')?></label>
        <select class="form-control options-header-option" id="bydistance" onchange="setCalendarFilterState('bydistance');orderByDistance();">
                <option value="100"><?= gettext('Within any distance'); ?></option>
                <?php 	for ($step=5; $step <= 50; $step += 5){ ?>
                    <option value="<?=$step?>"><?= sprintf(gettext('Within %s miles'),$step)?> </option>
                <?php	if ($step > 25) $step +=5; } ?>
        </select>
        <div class="current_location">of <a href="javascript:showChangeLocation()"><span id="current_address"><?= htmlspecialchars($_SESSION['fullAddress']) ?: gettext('Not set') ; ?> </span></a></div>
        <?php } else { ?>
            <input type="hidden"  id="bydistance" val="100">
            <select class=" form-control options-header-option" disabled title="<?= gettext('This feature has been disabled in this zone.')?>" style="color:#ababab;">
                <option value="">Search by Location</option>
            </select>
        <?php } ?>
        <?php } ?>
</div>

<div class="col-sm-4 by-category">
    <label id="labelForCategory" class="control-lable calendar-filter-lable" for="byCategory"><?=sprintf(gettext('Filter by %s'), 'group category')?></label>
    <?php if (count($groupCategoryRows)>0 /* Note checking it against 0 is alaways true unless data migration issue */) { ?>
        <select class="form-control options-header-option" onchange="setCalendarFilterState('byCategory');refreshCalendarDyanamicFilters(0, 'byCategory');" id="byCategory" multiple>
            <?php foreach ($groupCategoryRows as $groupCategoryRow) { ?>
            <option value="<?=$groupCategoryRow['categoryid']?>" <?=   in_array($groupCategoryRow['categoryid'],$groupCategoryArray) ? 'selected' : ''  ?> ><?=$groupCategoryRow['category_name']?></option>
            <?php  } ?>
        </select>
    <?php } else { ?>
        <select class="form-control" id="byCategory" disabled title="<?= gettext('This feature has been disabled in this zone.')?>" style="color:#ababab;" multiple>
            <option value="0">Category feature not enabled</option>
        </select>
    <?php }  ?>
</div>

<div class="col-sm-4 by-event-type">
    <label id="labelForEventType" class="control-lable calendar-filter-lable" for="byEventType"><?=gettext('Filter by event type')?></label>
    <select class="form-control options-header-option" id="byEventType" onchange="setCalendarFilterState('byEventType');getGlobalCalendarEventsByFilters();" multiple>
        <?php foreach ($allEventTypes as $et) { 
            $selectedEventtype = '';
            if (!empty($eventTypeArray)) {
                if ($eventTypeArray[0] =='all' || in_array($et['typeid'],$eventTypeArray)) {
                    $selectedEventtype = 'selected';
                }
            }
        ?>
            <option value="<?=$_COMPANY->encodeId($et['typeid'])?>" <?= $selectedEventtype; ?> ><?=$et['type'];?></option>
        <?php  } ?>
    </select>

</div>

<div class="col-sm-4 by-chapter">
    <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
    <label id="labelForChapter" class="control-lable calendar-filter-lable" for="byChapter"><?=sprintf(gettext('Filter by %s'), 'chapter' . (strcasecmp('chapter',$_COMPANY->getAppCustomization()['chapter']['name-short']) ? ' (' . $_COMPANY->getAppCustomization()['chapter']['name-short'] . ')' : '' ))?></label>
    <select class="form-control options-header-option selectpicker" multiple id="byChapter" onchange="setCalendarFilterState('byChapter');getGlobalCalendarEventsByFilters();">
        <option value="<?= $_COMPANY->encodeId(0)?>" <?= ($selectAllChapters || in_array(0, $chapterIdsArray)) ? 'selected' : ''; ?> ><?=gettext("Include global events")?></option>
        <?php
        foreach($allChapters as $key => $row){
            $chapteridsArrayRows = array_column($row,'chapterid');
            $ids = array();
            $idsEnc = array();
            foreach($chapteridsArrayRows as $ch){
                $ids[] = $ch;
                $idsEnc[] = $_COMPANY->encodeId($ch);
            }
            $chapteridsString = implode(',',$idsEnc);
        ?>
        <option value="<?= $chapteridsString; ?>" <?= ($selectAllChapters || array_intersect($chapterIdsArray, $ids)) ? 'selected' : ''; ?>><?= $key; ?></option>
        <?php }  ?>
    </select>
    <?php } else { ?>
        <select style="display:none" multiple id="byChapter" title="<?= gettext('This feature has been disabled in this zone.')?>"></select>
    <?php } ?>
</div>


<script>
    $('#byZones').multiselect({
        nonSelectedText: "<?= gettext('Filter by zone')?>",
        numberDisplayed: 1,
        nSelectedText: "<?=gettext('zone(s) selected')?>",
        disableIfEmpty: true,
        allSelectedText: "<?= gettext('All zones selected')?>",
        selectAllText: '<?= gettext("Select all zones");?>',
        includeSelectAllOption: false,
        maxHeight: 400,
        templates: {option: '<div tabindex="0" class="multiselect-option dropdown-item calendar-zone-filter"></div>'},
    });

    $('#byCategory').multiselect({
        nonSelectedText: "<?= gettext('Select a category')?>",
        numberDisplayed: 1,
        nSelectedText: "<?=gettext('Category selected')?>",
        disableIfEmpty: true,
        allSelectedText: "<?= gettext('All category selected')?>",
        selectAllText: '<?= gettext("Select All");?>',
        includeSelectAllOption: true,
        includeSelectAllIfMoreThan: 1,
        maxHeight: 400,
        templates: {option: '<div tabindex="0" class="multiselect-option dropdown-item calendar-category-filter"></div>'},
    });

    $('#byregion').multiselect({
        nonSelectedText: "<?= sprintf(gettext("Select a %s"), gettext('region'));?>",
        numberDisplayed: 1,
        nSelectedText: "<?=gettext('Region selected')?>",
        disableIfEmpty: true,
        allSelectedText: "<?= gettext('All regions selected')?>",
        selectAllText: '<?= gettext("Select All");?>',
        includeSelectAllOption: true,
        includeSelectAllIfMoreThan: 1,
        maxHeight: 400,
        templates: {option: '<div tabindex="0" class="multiselect-option dropdown-item calendar-regoin-filter"></div>'},
    });

    $('#byEventType').multiselect({
        nonSelectedText: "<?= gettext('Filter by event types')?>",
        numberDisplayed: 1,
        nSelectedText: "<?=gettext('Event types selected')?>",
        disableIfEmpty: true,
        allSelectedText: "<?= gettext('All event types selected')?>",
        selectAllText: '<?= gettext("Select All");?>',
        includeSelectAllOption: true,
        includeSelectAllIfMoreThan: 1,
        maxHeight: 400,
        templates: {option: '<div tabindex="0" class="multiselect-option dropdown-item calendar-event-filter"></div>'},
    });

    $('#bygroup').multiselect({
        nonSelectedText: "<?= sprintf(gettext('Select a %s'), $_COMPANY->getAppCustomization()['group']['name-short'])?>",
        numberDisplayed: 1,
        nSelectedText: "<?= sprintf(gettext('%s selected'), $_COMPANY->getAppCustomization()['group']['name-short-plural'] )?>",
        disableIfEmpty: true,
        allSelectedText: "<?= sprintf(gettext('All %s selected'), $_COMPANY->getAppCustomization()['group']['name-short-plural'] )?>",
        selectAllText: '<?= gettext("Select All");?>',
        includeSelectAllOption: true,
        includeSelectAllIfMoreThan: 1,
        enableFiltering: true,
        enableCaseInsensitiveFiltering: true,
        maxHeight: 400,
        templates: {option: '<div tabindex="0" class="multiselect-option dropdown-item calendar-group-filter"></div>'},
    });

    <?php if ($_COMPANY->getAppCustomization()['chapter']['enabled']) { ?>
    $('#byChapter').multiselect({
        nonSelectedText: "<?= sprintf(gettext('Select a %s'), $_COMPANY->getAppCustomization()['chapter']['name-short'])?>",
        numberDisplayed: 1,
        nSelectedText: "<?= sprintf(gettext('%s selected'), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",
        disableIfEmpty: true,
        allSelectedText: "<?= sprintf(gettext('All %s selected'), $_COMPANY->getAppCustomization()['chapter']['name-short-plural'])?>",
        selectAllText: '<?= gettext("Select All");?>',
        includeSelectAllOption: true,
        includeSelectAllIfMoreThan: 1,
        enableFiltering: true,
        enableCaseInsensitiveFiltering: true,
        maxHeight: 400,
        selectAllValue: 'multiselect-all',
        templates: {option: '<div tabindex="0" class="multiselect-option dropdown-item calendar-chapter-filter"></div>'},
    });
    <?php } ?>

    $( document ).ready(function() {
        getGlobalCalendarEventsByFilters();
    });

    $(document).ready( function () {         
          
        $('.by-zones .multiselect-selected-text').attr({'id':"zoneSelected"});    
        $('.by-region .multiselect-selected-text').attr({'id':"regionsSelected"}); 
        $('.by-group .multiselect-selected-text').attr('id', 'groupSelected'); 
        $('.by-category .multiselect-selected-text').attr('id', 'categorySelected'); 
        $('.by-event-type .multiselect-selected-text').attr('id', 'eventTypeSelected'); 
        $('.by-chapter .multiselect-selected-text').attr('id', 'chapterSelected'); 
        $('.by-distance .multiselect-selected-text').attr('id', 'distanceSelected');

        $('.by-zones .multiselect').attr('aria-labelledby', 'labelForZones zoneSelected');     
        $('.by-region .multiselect').attr('aria-labelledby', 'labelForRegion regionsSelected');         
        $('.by-group .multiselect').attr('aria-labelledby', 'labelForGroup groupSelected');  
        $('.by-category .multiselect').attr('aria-labelledby', 'labelForCategory categorySelected');  
        $('.by-event-type .multiselect').attr('aria-labelledby', 'labelForEventType eventTypeSelected');  
        $('.by-chapter .multiselect').attr('aria-labelledby', 'labelForChapter chapterSelected');  
        $('.by-distance .multiselect').attr('aria-labelledby', 'labelForDistance distanceSelected');   
    });

$(document).ready(function () {
    $('.calendarSection .dropdown-item').on('keypress', function (event) {
        if (event.which === 13) {
            $(this).click();
        }
    });
});
</script>