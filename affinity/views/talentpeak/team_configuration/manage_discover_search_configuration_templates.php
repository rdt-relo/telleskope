<style>
    .fa-check{
        display: none;
    }
</style>
<style>
    .switch {
      position: relative;
      display: inline-block;
      width: 40px;
      height: 22px;
    }

    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      -webkit-transition: .4s;
      transition: .4s;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 13px;
      width: 13px;
      left: 7px;
      bottom: 4px;
      background-color: white;
      -webkit-transition: .4s;
      transition: .4s;
    }

    input:checked + .slider {
      background-color: red;
    }

    input:focus + .slider {
      box-shadow: 0 0 1px red;
    }

    input:checked + .slider:before {
      -webkit-transform: translateX(13px);
      -ms-transform: translateX(13px);
      transform: translateX(13px);
    }

    /* Rounded sliders */
    .slider.round {
      border-radius: 17px;
    }

    .slider.round:before {
      border-radius: 50%;
    }
    </style>
<div class="row mb-4">

    <form class="form-group" id="searchConfigurationForm">
<?php if ($group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['PEER_2_PEER'] || $group->getTeamProgramType() == Team::TEAM_PROGRAM_TYPE['CIRCLES']){ ?>
        <div class="col-12 form-group-emphasis">
        <div class="p-3">
            <p class="form-group-emphasis-heading-text">
                <strong><?= gettext('Select which primary attributes you want to enable in Discover Tab Filter'); ?></strong>
                <br>
                <small><?= gettext('If no attribute is selected then search attributes will not be shown in the Discover Tab Filter'); ?></small>
            </p>
            <table class="table table-sm display" role="presentation">
                <col style="width:60%;">
                <col style="width:40%;">
            <?php foreach($availableAttributes as $attribute){ ?>
                <tr>
                    <td><?= $attribute; ?></td>
                    <td>
                        <div class="form-check">
                        <input aria-label="<?= $attribute; ?>" class="form-check-input" type="checkbox" name="primary_attributes[]" value="<?= $attribute; ?>" <?= in_array($attribute,$selectedAttributes['primary']) ? 'checked' : ''; ?>  onchange="saveDiscoverSearchAttributes('<?= $_COMPANY->encodeId($groupid); ?>',this);">
                        </div>
                    </td>
                </tr>
            <?php } ?>

            <?php foreach($surveyQuestions as $question){ 
               if ($question['type'] == 'html'){ continue; }
              if ($question['type'] == 'comment') { continue; }
            ?>
                <tr>
                    <td><?= $question['title']??$question['name']; ?></td>
                    <td>
                        <div class="form-check">
                        <input aria-label="<?= $question['title']??$question['name']; ?>" class="form-check-input" type="checkbox" name="custom_attributes[]" value="<?= $question['name']; ?>" <?= in_array($question['name'],$selectedAttributes['custom']) ? 'checked' : ''; ?>  onchange="saveDiscoverSearchAttributes('<?= $_COMPANY->encodeId($groupid); ?>',this);">
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </table>
        </div>
        </div>

        <div class="col-12 form-group-emphasis">
            <div class="p-3">
                <p class="form-group-emphasis-heading-text">
                    <strong><?= gettext('Other Settings'); ?></strong>
                </p>
                <table class="table table-sm display">
                    <col style="width:60%;">
                    <col style="width:40%;">

                    <tr>
                        <td><?= gettext("By default, enable 'Show Only with Available Capacity' option on the Discover Tab") ?></td>
                        <td>
                            <div class="form-check">
                                <input aria-label="<?= gettext("By default, enable 'Show Only with Available Capacity' option on the Discover Tab") ?>" class="form-check-input" type="checkbox" name="default_for_show_only_with_available_capacity" id="default_for_show_only_with_available_capacity" value="1" <?= ($selectedAttributes['default_for_show_only_with_available_capacity'] ?? 0) == 1 ? 'checked' : ''; ?>  onchange="saveDiscoverSearchAttributes('<?= $_COMPANY->encodeId($groupid); ?>',this);">
                            </div>
                        </td>
                    </tr>

                </table>
            </div>
        </div>
    <?php } ?>
    </form>


</div>

<script>

    function saveDiscoverSearchAttributes(g,e){

        const primary_attributes = Array
        .from(document.querySelectorAll('input[type="checkbox"][name^="primary_attributes"]'))
        .filter((checkbox) => checkbox.checked)
        .map((checkbox) => checkbox.value);

        const custom_attributes = Array
            .from(document.querySelectorAll('input[type="checkbox"][name^="custom_attributes"]'))
            .filter((checkbox) => checkbox.checked)
            .map((checkbox) => checkbox.value);


        let default_for_show_only_with_available_capacity = 0;
        if ($('#default_for_show_only_with_available_capacity').is(":checked")) {
            default_for_show_only_with_available_capacity = 1;
        }

        $.ajax({
            url: 'ajax_talentpeak.php?saveDiscoverSearchAttributes=1',
            type: "POST",
            data: {'groupid':g,'primary_attributes':primary_attributes, 'custom_attributes':custom_attributes, 'default_for_show_only_with_available_capacity':default_for_show_only_with_available_capacity},
            success: function(data) {
                try {
                    let jsonData = JSON.parse(data);
                    swal.fire({title: jsonData.title,text:jsonData.message});
                } catch(e) { swal.fire({title: 'Error', text: "Unknown error."}); }
            }
        });

    }
   
</script>
