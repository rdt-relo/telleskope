<?php

    /**
     * Dependencies
     * 
     * $groupid - int
     * $group - Group Object
     * $primary_parameters - Array
     * $searchAttributes - Array
     * $userPlaceHolder - String
     * $searchSubject - String
     * 
     */
    // Initialize
    $searchSubject ??= '';
?>
<!-- Search by Circle/team  -->
<style>
.multiselect:before{
	content: "\25be";
    float:right;
    color:gray;
}

</style>
<?php 
    $enable_search_circles = (
        $_COMPANY->getAppCustomization()['teams']['search']
        && $group->getTeamProgramType() === Team::TEAM_PROGRAM_TYPE['CIRCLES']
    );
?>

<?php if(!empty($primary_parameters) || !empty($customParameters)){ ?>
       
    <form id="filterByNameForm" action="javascript:void(0)">
        <div class="row row-no-gutters">
            <div class="col-12 col-sm-12 m-sm-0 px-sm-0">
        <?php if ($enable_search_circles) {
            $hashTagHandles = HashtagHandle::GetAllHashTagHandles();
        ?>
            <style>
                .select2-selection__rendered {
                    line-height: 18px !important;
                }
                .select2-container .select2-selection--single {
                    height: 18px !important;
                }
                .select2-selection__arrow {
                    height: 18px !important;
                }
                .select2-container--default .select2-selection--multiple {
                    border: 1px solid #ced4da !important;
                }
            </style>
                 <div class="col-12 col-sm-12 m-sm-0 px-sm-0">
                    <p><?= sprintf(gettext('Search By %s Name'), Team::GetTeamCustomMetaName($group->getTeamProgramType(),1)) ?></p>
                </div>
            
                <div class="col-12 col-sm-6 pb-3 px-md-1">
                    <input type="text" class="form-control" id="js-search-circle-input" placeholder="<?= gettext('Enter text to search') ?>" />
                </div>
            
                <div class="col-12 col-sm-6 pb-3 px-md-1">
                    <?php if ($hashTagHandles) { ?>
                        <select tabindex="-1" class="form-control" id="js-hashtag-ids" style="width: 100%;" multiple>
                            <?php foreach ($hashTagHandles as $tag) { ?>
                                <option value="<?= $tag['hashtagid'] ?>" <?= in_array($tag['hashtagid'], $hashtag_ids ?? []) ? 'selected' : '' ?>><?= $tag['handle'] ?></option>
                            <?php } ?>
                        </select>
                    <?php } ?>
                </div>

            <script>
                $('#js-hashtag-ids').multiselect({
                    nonSelectedText: "<?= gettext('Select hashtags'); ?>",
                    numberDisplayed: 5,
                    filterPlaceholder: "<?= gettext('Select hashtags'); ?>",
                    nSelectedText  : "<?= gettext('hashtags selected')?>",
                    disableIfEmpty:true,
                    allSelectedText: "<?= gettext('All hashtags selected')?>",
                    maxHeight:400,
                    includeSelectAllOption : true,
                    enableFiltering: true
                });
            </script>
        <?php } ?>
                <div class="col-12 col-sm-12 m-sm-0 px-sm-0">
                    <label for="name_keyword"><?= sprintf(gettext('Search By %s Name'), $searchSubject); ?></label>
                </div>
                <div class="col-12 col-sm-12 px-sm-1">
                    <input type="text"  id="name_keyword" name="name_keyword" class="form-control" placeholder="<?= $userPlaceHolder; ?>">
                </div>
            <?php if ($searchAttributes) { ?>
                <div class="col-12 col-sm-12 m-sm-0 px-sm-0">
                    <label class="pt-3"><?= sprintf(gettext('Search By %s Attributes'), $searchSubject); ?></label>
                </div>
                <div class="col-12 m-0 p-0">
                    <div class="col-12 col-sm-6 px-md-1">
                        <select name="primary_attribute[]" id="primary_attribute_1" aria-label="<?= gettext("Filter 1 - Select a Filter Attribute")?>"  class="form-control" onchange="getTeamAttributeValuesByKey('<?= $_COMPANY->encodeId($groupid); ?>',this.value,1)">
                            <option data-keyType='' value=""><?= gettext("Select a Filter")?></option>
                            <?php
                             $options = array();
                            foreach($primary_parameters as $key => $value){
                                if(!in_array($key,$searchAttributes['primary'])) {
                                    continue;
                                }
                              $options[] = ['key' => $key, 'value' => $key, 'keyType' => 'primary'];
                             } 

                             foreach($searchAttributes['custom'] as $key) {
                               $q = $group->getTeamCustomAttributesQuestion($key);
                                if (empty($q)) {
                                    continue;
                                }
                                #if ($q['type'] == 'comment') { continue; }
                                $options[] = ['key' => $key, 'value' => $q['title'] ?? $q['name'], 'keyType' => 'custom'];
                              } 
                                // Sort the combined options array by the 'value'
                            if (!empty($options)) {
                                 usort($options, function($a, $b) {
                                   return strcasecmp($a['value'], $b['value']);
                                 })
                            ?>
                            <!-- Display the sorted options -->
                            <?php foreach ($options as $option) { ?>
                            <option data-keyType='<?= $option['keyType']; ?>' value="<?= $option['key']; ?>"><?= htmlspecialchars($option['value']); ?></option>
                            <?php }} ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 px-md-1"  id="attribute_keyword_div_1">
                        <select name="attribute_keyword[]" id="attribute_keyword_1" aria-label="<?= gettext("Select Filter 1 Attribute First -")?>" class="form-control">
                            <option value=""><?= gettext("Select a Filter First")?></option>
                        </select>
                    </div>
                </div>
            <?php if((count($searchAttributes) + count($customParameters)) > 1){ ?>
                <input type="hidden" id="add_more_attributes_index" value="1">
                <div id="append_add_more_attributes"></div>
                <div class="col-12 p-0 m-0 mt-2" id="add_more_attributes">
                 <?php if(count($searchAttributes['primary'])+count($customParameters)>1){ ?>
                    <button class="btn btn-link px-0" id="add_more_attributes_btn" onclick="appendNewAttributeFilter('<?= $_COMPANY->encodeId($groupid); ?>');" type="button">[+ <?= gettext("Add more search attributes");?>]</button>
                 <?php }?>
                </div>
            </div>
            <?php } ?>
        <?php } ?>
               
                <div class="col-12 col-sm-12 px-1 pt-3">
                    <div class="col-12 col-sm-8 px-sm-1 mb-3">
                        <?php if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?>
                            <div class="custom-control custom-switch">
                                <!-- By default show active circles only -->
                                <input type="checkbox" role="switch" class="custom-control-input" value="1" <?= $_SESSION['showAvailableCapacityOnly'] ? 'checked' : ''; ?> name="showAvailableCapacityOnly" id="showAvailableCapacityOnly" onchange="filterCirclesBySearch('<?= $_COMPANY->encodeId($groupid); ?>')" >
                                <label class="custom-control-label" for="showAvailableCapacityOnly"><?= sprintf(gettext("Show Only Available %s"),Team::GetTeamCustomMetaName($group->getTeamProgramType(),1))?></label>
                            </div>
                        <?php } elseif ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER']) { ?>
                            <div class="custom-control custom-switch">
                                <!-- By default show active circles only -->
                                <input type="checkbox" role="switch" class="custom-control-input" value="1" name="showAvailableCapacityOnly" <?= $_SESSION['showAvailableCapacityOnly'] ? 'checked' : ''; ?> id="showAvailableCapacityOnly" onchange="discoverTeamMembers('<?= $_COMPANY->encodeId($groupid); ?>',1)" >
                                <label class="custom-control-label" for="showAvailableCapacityOnly"><?= gettext("Show Only with Available Capacity")?></label>
                            </div>
                        <?php } ?>
                    </div>
                
                    <div class="col-12 col-sm-4 px-sm-1  text-right">
                        <button class="btn btn-sm btn-affinity" id="filterBtn" type="button" disabled
                        <?php if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?>
                            onclick="discoverTeamMembers('<?= $_COMPANY->encodeId($groupid); ?>',1);"
                        <?php } else { ?>
                            onclick="$('#discoverCirclePageNumber').val(2); filterCirclesBySearch('<?= $_COMPANY->encodeId($groupid); ?>'),$('#filter_clear_button').show();"
                        <?php } ?>
                        ><?= gettext("Filter")?></button>
                    
                        <button class="btn btn-sm btn-affinity " id="filter_clear_button" style="display:none;" type="button"
                        <?php if ($group->getTeamProgramType() != Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?>
                            onclick="initDiscoverTeamMembers('<?= $_COMPANY->encodeId($groupid); ?>',0)"
                        <?php } else { ?>
                            onclick="clearDiscoverCircleFilter('<?= $_COMPANY->encodeId($groupid); ?>');"
                        <?php } ?>

                        ><?= gettext("Clear Filter")?></button>
                    </div>
                </div>
            </div>
        </div>
        </form>
        
        <hr class="linec">
        
        <script>

            function getTeamAttributeValuesByKey(g,v,i){
                validateInputs();
                if (v) {
                    var keyType = $("#primary_attribute_"+i).find(':selected').attr('data-keyType');
                    $.ajax({
                        url: 'ajax_talentpeak.php?getTeamAttributeValuesByKey=1',
                        type: "GET",
                        data: {'groupid':g, 'attributeKey':v,'keyType':keyType,'attributes_index':i},
                        success: function(data) {
                            $("#attribute_keyword_div_"+i).html(data);
                        }
                    });
                } else {
                    $("#attribute_keyword_div_"+i).html("<select class='form-control'> <option data-keyType='' value=''><?= gettext('Select a Filter First')?></option> </select>");
                }
            }
            function clearDiscoverCircleFilter(g) {
                $('#discoverTeamMembers').val(2);
                $('#filterByNameForm').get(0).reset();
                $('#js-hashtag-ids').multiselect('refresh');
                $('#filter_clear_button').hide();
                $("#discoverCirclePageNumber").val(2);
                discoverCircles(g);
                document.querySelector("#filterBtn").focus(); 
            }

            function appendNewAttributeFilter(g){
                let add_more_attributes_index = $("#add_more_attributes_index").val();
                let totalAttributes = <?= (count($searchAttributes['primary'])+count($customParameters)); ?>;
                let new_index = parseInt(add_more_attributes_index)+1;
                $.ajax({
                    url: 'ajax_talentpeak.php?appendNewAttributeFilter=1',
                    type: "GET",
                    data: {'groupid':g,'add_more_attributes_index':new_index},
                    success: function(data) {
                        $("#add_more_attributes_index").val(new_index);
                        $("#append_add_more_attributes").append(data);
                        if (new_index == totalAttributes) {
                            $("#add_more_attributes_btn").hide();
                        }
                    }
                });
            }

            function validateInputs() {
                $('#filterByNameForm input[type="text"], #filterByNameForm select').on('input change', function() {
                    let isFormFilled = false;

                    $('#filterByNameForm input[type="text"], #filterByNameForm select').each(function() {
                        if ($(this).val().length) {
                            isFormFilled = true;
                            return false; // Break out of each loop
                        }
                    });

                    $('#filterBtn').prop('disabled', !isFormFilled);
                });
            }

            $(document).ready(function() {
                validateInputs();
            });

            // Attach keypress event listeners to the input fields
            $('#js-search-circle-input, #name_keyword').on('keypress', function(e) {
                if (e.which === 13) { // Check if the Enter key is pressed
                    e.preventDefault(); // Prevent form submission
                    $("#filterBtn").trigger("click");
                }
            });
        </script>
<?php } ?>

<script>
//On Enter Key...
 $(function(){
       $("#showAvailableCapacityOnly").keypress(function (e) {
           if (e.keyCode == 13) {
               $(this).trigger("click");
           }
        });
    });

    $(document).ready(function() {
        $('.multiselect').attr( 'tabindex', '0' );
        $('.multiselect-search').attr( 'tabindex', '0' );
        $('.multiselect-clear-filter').attr( 'tabindex', '-1' );
        
    });
    
    $('.multiselect-native-select .multiselect').keydown(function(e) {   
        if (e.keyCode === 40) {  
            $('input.multiselect-search').focus();  
        }    
    });
    $('.multiselect-native-select .multiselect-all').keydown(function(e) { 
        if (e.key === "Tab" || e.keyCode === 38) { 
            $('.multiselect-search').focus();  
        }
    });   
    $('.multiselect-container li').keydown(function(e) {   
        if (e.keyCode === 27) { 
            $('.multiselect-container').removeClass('show');  
        }    
    });
</script>