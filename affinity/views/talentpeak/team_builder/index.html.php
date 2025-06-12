<div id="team-builder-container"></div>

<script>
  function renderTeamBuilderForm() {
    var labelTemplate = `
    <label class="ml-3">
      <input
        type="checkbox" id="<%= userFields[i].replace(/[^a-zA-Z0-9]/g, ""); %>"
        name="user-fields[]"
        value="<%= userFields[i] %>"
        onchange="onInputChange(event)"
        <%= formData.user_fields.includes(userFields[i]) ? 'checked' : '' %>
      >
      &nbsp;

      <%= userFields[i] %>
      <% if (teamSetup.checkboxAttributesToMatch.includes(userFields[i])) { %>
        <small>(Best Effort Match)</small>
        </label>
      <% } else if (teamSetup.attributesToMatch.includes(userFields[i])) { %>
        <small>(Match)</small>
        </label>
      <% } else if (teamSetup.attributesToNotMatch.includes(userFields[i])) { %>
        <small>(Not Match)</small>
        </label>
      <% } else if (teamSetup.numericAttributesGreaterThan.includes(userFields[i])) { %>
        <small>(Comparator)</small>
        </label>
      <% } else if (teamSetup.numericAttributesLessThan.includes(userFields[i])) { %>
        <small>(Comparator)</small>
        </label>
      <% } else if (teamSetup.numericAttributesGreaterThanEq.includes(userFields[i])) { %>
        <small>(Comparator)</small>
        </label>
      <% } else if (teamSetup.numericAttributesLessThanEq.includes(userFields[i])) { %>
        <small>(Comparator)</small>
        </label>
      <% } else { %>
        <small>(Anti-Cluster)</small>
        </label>
      <% } %>
    <br>
    `;

    containerElement.html(ejs.render(`
      <form
        class="form-horizontal"
        action="ajax_talentpeak_team_builder.php?getSuggestedTeams=1&groupid=<?= $_COMPANY->encodeId($groupid); ?>"
        method="POST"
        role="form"
        style="display: block;width:100% !important"
        id="form-team-builder"
      >
        <input type="hidden" name="csrf_token" value="<?= Session::GetInstance()->csrf; ?>">
        <div class="mt-3" tyle="padding: 0 50px; border:1px solid rgb(223, 223, 223); padding-top:10px;">
        <div class="col-md-12 p-0">
          <h1><?= gettext(sprintf('%s Builder ', $companyTeamName)); ?></h1>
        </div>
        <hr class="lineb">
        <input type="hidden" name="userid" value="<?= $_COMPANY->encodeId(0); ?>">
        <input type="hidden" name="roleid" value="<?= $_COMPANY->encodeId(0); ?>">

        <div class="form-row form-group-emphasis p-3 my-3">
            <div class="form-group px-3">
                <label>Team Size: <span style="color:red;">*</span>&nbsp;</label>
                <input id="teamSize" placeholder="Team Size" class="form-control" type="number" name="team-size" required value="<%= formData.team_size %>" onchange="onInputChange(event)">
            </div>

            <div class="form-group px-3">
                <div class="col-md-6 pl-0 pr-3">
                <label><?=gettext('Team Name Prefix:')?> <span style="color:red;">*</span>&nbsp;  </label>
                <input id="teamNamePrefix" placeholder="Team Name Prefix" class="form-control" name="team-name-prefix" required value="<%= formData.team_name_prefix %>" onchange="onInputChange(event)">
                </div>
                <div class="col-md-6 pl-3 pr-0">
                <label><?=gettext('Starting Team Number:')?> <span style="color:red;">*</span>&nbsp; </label>
                <input id="teamNumber" placeholder="Starting Team Number" class="form-control" type="number" name="starting-team-number" required value="<%= formData.starting_team_number %>" onchange="onInputChange(event)">
                </div>
            </div>
        </div>

        <div class="form-row form-group-emphasis p-3 my-3">
        <div class="form-group px-3">
          <h2><?= gettext('Select attributes to use for assigning teams:'); ?></h2>
          <br>
          <br>
          <% var userFields = Object.keys(userFieldValues); %>
          <% var primaryAttributeAdded, customAttributeAdded = false; %>
          <fieldset>
          <% for (var i = 0; i < userFields.length; i++) { %>
            <% if (teamSetup.selectablePrimaryAttributes.includes(userFields[i])) { %>
             
              <% if (!primaryAttributeAdded) { %>
                <% primaryAttributeAdded = true; %>
                <legend>Primary Attributes</legend>
              <% } %>
              ${labelTemplate}
              
            <% } %>
          <% } %>
          </fieldset>
          <br>
          <fieldset>
          <% for (var i = 0; i < userFields.length; i++) { %>
            <% if (teamSetup.selectableCustomAttributes.includes(userFields[i])) { %>
              <% if (!customAttributeAdded) { %>
                <% customAttributeAdded = true; %>
                <legend>Custom Attributes</legend>
              <% } %>
              ${labelTemplate}
            <% } %>
          <% } %>
          </fieldset>
          
          <hr>
          <strong><?=gettext('Note about matching techniques:')?></strong>
          <ul>
           <li>
           <?= gettext('<b>Anti-Clustering:</b> Anti-Clustering is a grouping technique that minimizes the similarity within teams for the selected criteria. By default, all attributes are anti-clustered. ')?>
           <?= gettext('To make an attribute as a matching-criteria OR non-matching criteria, please go to Teams > Configuration > Matching Algorithm and configure the matching criteria for the desired attribute.')?>
           </li>
           <li>
           <?= gettext('<b>Match:</b> In a team, all team members will have same value for the chosen attribute')?>
           </li>
           <li>
           <?= gettext('<b>Not Match:</b> In a team, all team members will have different value for the chosen attribute')?>
           </li>
           <li>
           <?= gettext("<b>Comparator Matching:</b> In a team, all team members of role type 'Mentor' would have a value greater than or less than all team members of role type 'Mentee' for the chosen attribute")?>
           </li>
           <li>
           <?= gettext('<b>Best Effort Match:</b> For Multiple-Choice Questions, Team Builder does a best effort matching to maximise overlap of selections within a team')?>
           </li>
          </ul>
          </div>
        </div>

        <div class="form-row form-group-emphasis p-3 my-3">
        <div class="form-group px-3">
          <label>
          <input id="applyMinRule"
            type="checkbox"
            name="apply-min-rule"
            value="1"
            onchange="onInputChange(event)"
            <%= formData.apply_min_rule ? 'checked' : '' %>
          >
          &nbsp;
          <?= gettext('Tailor your Teams with additional constraints:'); ?>
          </label>
        </div>

<% if (formData.apply_min_rule) { %>
            <div class="form-row row p-3">
            <p>
                <?=gettext('In this section, you can refine your team composition by imposing specific requirements, such as ensuring that each team has at least a certain number of members with a particular attribute. This optional feature allows you to create teams that not only possess the necessary skills and experience but also meet your specific criteria. An Example of Additional Constraint: Ensure that each team has atleast one Female member.')?>
            </p>
            </div>
            <div class="form-row row p-3">
                <div class="form-group col-md-6 pl-0 pr-3">


                    <label><?= gettext('Min Members Count:'); ?> <span style="color:red;">*</span>&nbsp;</label>
                    <input id="minRuleCount" placeholder="Min Members Count" class="form-control" type="number" name="min-rule-count" required value="<%= formData.min_rule_count %>" onchange="onInputChange(event)">
                </div>
                <div class="form-group col-md-6 pl-3 pr-0">
                    <label><?= gettext('Select User Field:'); ?> <span style="color:red;">*</span>&nbsp;</label>
                    <select id="minRuleField" class="form-control" name="min-rule-field" onchange="onInputChange(event)">
                      <% if (!formData.min_rule_field) { %>
                        <option value="" selected>Select a field</option>
                      <% } %>
                      <% for (var i = 0; i < formData.user_fields.length; i++) { %>
                        <% if (formData.user_fields[i] === 'Chapter') {
                          continue;
                        } %>
                        <% if (
                            teamSetup.checkboxAttributesToMatch.includes(formData.user_fields[i])
                            || teamSetup.attributesToMatch.includes(formData.user_fields[i])
                            || teamSetup.attributesToNotMatch.includes(formData.user_fields[i])
                          ) {
                          continue;
                        } %>

                        <option
                          value="<%= formData.user_fields[i] %>"
                          <%= formData.user_fields[i] === formData.min_rule_field ? 'selected' : '' %>
                        >
                          <%= formData.user_fields[i] %>
                        </option>
                      <% } %>
                    </select>
                </div>

                <div class="form-group col-md-12">
                    Select Values: <span style="color:red;">*</span>&nbsp;
                    <% var minRuleFieldValues = userFieldValues[formData.min_rule_field] || []; %>
                    <% for (var i = 0; i < minRuleFieldValues.length; i++) { %>
                      <br>
                      <label>
                        <input id="<%= String(minRuleFieldValues[i]).replace(/[^a-zA-Z0-9]/g,"") || 'Empty value'.replace(/[^a-zA-Z0-9]/g,""); %>"
                          type="checkbox"
                          name="min-rule-field-values[]"
                          value="<%= minRuleFieldValues[i] %>"
                          onchange="onInputChange(event)"
                          <%= formData.min_rule_field_values.includes(minRuleFieldValues[i].toString()) ? 'checked' : '' %>
                        >
                        &nbsp;
                        <%= minRuleFieldValues[i] || 'Empty value' %>
                      </label>
                    <% } %>
                </div>
            </div>
          <% } %>
      </div>

    <div class="form-row form-group-emphasis mb-5 p-5">
    <div class="form-group col-md-12">
          <strong><?=gettext('Applied Constraints:')?></strong>
          <ul>
            <li>
              Each team can have atmost <%= formData.team_size %> members
            </li>
            <?php
              foreach ($teamRoles as $roleName => $teamRole) {
                echo '<li>'
                  . gettext(sprintf(
                    'Each team must have atleast %d members of role %s',
                    (int) $teamRole['min_required'],
                    $roleName
                  ))
                  . '</li>';

                echo '<li>'
                  . gettext(sprintf(
                    'Each team can have atmost %d members of role %s',
                    (int) $teamRole['max_allowed'],
                    $roleName
                  ))
                  . '</li>';
              }
            ?>
            <?php
              foreach ($teamRoles as $roleName => $teamRole) {
                if ($teamRole['role_capacity'] == 0) {
                    echo '<li>'
                        . gettext(sprintf(
                            '%s can be in unlimited number of teams',
                            $roleName
                        ))
                        . '</li>';
                } else {
                    echo '<li>'
                        . gettext(sprintf(
                            '1 %s can be in atmost %s',
                            $roleName,
                            (int)$teamRole['role_capacity'] == 1 ? '1 team' : $teamRole['role_capacity'] . ' teams'
                        ))
                        . '</li>';
                }
              }
            ?>
            <% if (formData.apply_min_rule) { %>
              <li>
                Each team must have atleast <%= formData.min_rule_count %> members with "<%= formData.min_rule_field %>" of "<%= (formData.min_rule_field_values || []).join('" OR "')%>"
              </li>
            <% } %>

            <% for (var i = 0; i < formData.user_fields.length; i++) { %>
              <% if (teamSetup.checkboxAttributesToMatch.includes(formData.user_fields[i])) { %>
                <li>
                  In a team, all team members should have atleast 1 common selection of '<%= formData.user_fields[i] %>' (Best Effort Match)
                </li>
              <% } else if (teamSetup.attributesToMatch.includes(formData.user_fields[i])) { %>
                <li>
                  In a team, all team members will have same '<%= formData.user_fields[i] %>'
                </li>
              <% } %>
            <% } %>

            <% for (var i = 0; i < teamSetup.attributesToNotMatch.length; i++) { %>
              <% if (formData.user_fields.includes(teamSetup.attributesToNotMatch[i])) { %>
                <li>
                  In a team, all team members will have different '<%= teamSetup.attributesToNotMatch[i] %>'
                </li>
              <% } %>
            <% } %>

            <% for (var i = 0; i < teamSetup.numericAttributesGreaterThan.length; i++) { %>
              <% if (formData.user_fields.includes(teamSetup.numericAttributesGreaterThan[i])) { %>
                <li>
                  In a team, all team members of role type 'Mentor' will have '<%= teamSetup.numericAttributesGreaterThan[i] %>' greater than all team members of role type 'Mentee'
                </li>
              <% } %>
            <% } %>

            <% for (var i = 0; i < teamSetup.numericAttributesGreaterThanEq.length; i++) { %>
              <% if (formData.user_fields.includes(teamSetup.numericAttributesGreaterThanEq[i])) { %>
                <li>
                  In a team, all team members of role type 'Mentor' will have '<%= teamSetup.numericAttributesGreaterThanEq[i] %>' greater than or equal to all team members of role type 'Mentee'
                </li>
              <% } %>
            <% } %>

            <% for (var i = 0; i < teamSetup.numericAttributesLessThan.length; i++) { %>
              <% if (formData.user_fields.includes(teamSetup.numericAttributesLessThan[i])) { %>
                <li>
                  In a team, all team members of role type 'Mentor' will have '<%= teamSetup.numericAttributesLessThan[i] %>' less than all team members of role type 'Mentee'
                </li>
              <% } %>
            <% } %>

            <% for (var i = 0; i < teamSetup.numericAttributesLessThanEq.length; i++) { %>
              <% if (formData.user_fields.includes(teamSetup.numericAttributesLessThanEq[i])) { %>
                <li>
                  In a team, all team members of role type 'Mentor' will have '<%= teamSetup.numericAttributesLessThanEq[i] %>' less than or equal to all team members of role type 'Mentee'
                </li>
              <% } %>
            <% } %>
          </ul>
          </div>
          </div>

          <div class="form-row form-group-emphasis mb-5 p-5">
            <div class="form-group col-md-12">
              <strong><?= gettext('Next Steps:') ?></strong>
              <ul>
              <?php
              $import_teams_anchor = '<a href="javascript:void(0)" onclick=\'importTeams("' . $_COMPANY->encodeId($groupid) . '")\'>'.gettext('Here').'</a>';
              ?>

              <li><?= gettext('Click on the "Get Suggested Teams" button below to download your Team Builder Report') ?></li>
              <li><?= gettext('You can open the report in either Excel or Google Sheets') ?></li>
              <li><?= gettext('The report will provide the recommended Teams based on the constraints you set') ?></li>
              <li><?= sprintf(gettext('If you are satisfied with the Team recommendations, you can import the Teams by clicking %s or by going to Manage Teams and click on New Teams and then clicking Import. You can also edit the Team names before importing.'), $import_teams_anchor) ?></li>
              <li><?= gettext('After importing the Teams will be in Draft mode, to activate use the "Select Bulk Action" button and select "Change all Draft to Active"') ?></li>
              </ul>
            </div>
          </div>

        </div>
        <div class="form-group mt-2">
          <div class="text-center">
            <button type="submit" name="submit" class="btn btn-primary" <%= isFormValid() ? '' : 'disabled="disabled"' %>">
              <?= gettext('Get Suggested Teams'); ?>
            </button>
            <button type="button" class="btn btn-primary" onclick='closeModal()'><?= gettext('Close');?></button>
            <hr>
          </div>
        </div>
      </form>
    `));

    containerElement.find('[data-toggle="tooltip"]').tooltip();
  }

  function onInputChange(event) {
    updateFormData(event);
    renderTeamBuilderForm();
    
    var inputId = event.currentTarget.id;    
    $('#'+inputId).focus();     
  }

  function updateFormData(event) {
    if (event.target.name === 'team-size') {
      formData.team_size = event.target.value;
      return;
    }

    if (event.target.name === 'team-name-prefix') {
      formData.team_name_prefix = event.target.value;
      return;
    }

    if (event.target.name === 'starting-team-number') {
      formData.starting_team_number = event.target.value;
      return;
    }

    if (event.target.name === 'user-fields[]') {
      field = event.target.value;
      if (event.target.checked) {
        formData.user_fields.push(field);
      } else {
        index = formData.user_fields.indexOf(field);
        formData.user_fields.splice(index, 1);
      }

      formData.min_rule_count = '';
      formData.min_rule_field = '';
      formData.min_rule_field_values = [];
      return;
    }

    if (event.target.name === 'min-rule-count') {
      formData.min_rule_count = event.target.value;
      return;
    }

    if (event.target.name === 'min-rule-field') {
      formData.min_rule_field = event.target.value;
      formData.min_rule_field_values = [];
      return;
    }

    if (event.target.name === 'min-rule-field-values[]') {
      field = event.target.value;
      if (event.target.checked) {
        formData.min_rule_field_values.push(field);
      } else {
        index = formData.min_rule_field_values.indexOf(field);
        formData.min_rule_field_values.splice(index, 1);
      }
      return;
    }

    if (event.target.name === 'apply-min-rule') {
      formData.apply_min_rule = !formData.apply_min_rule;
      formData.min_rule_count = '';
      formData.min_rule_field = '';
      formData.min_rule_field_values = [];
      return;
    }
  }

  function isFormValid() {
    if (
      !formData.team_size
      || formData.team_size < 1
      || formData.starting_team_number < 1
      || !formData.team_name_prefix
    ) {
      return false;
    }

    if (formData.apply_min_rule) {
      if (
        !formData.min_rule_count
        || !formData.min_rule_field
        || !formData.min_rule_field_values.length
      ) {
        return false;
      }
    }

    return true;
  }

  function closeModal() {
    $('#reportDownLoadOptions').slideUp('slow', function() {
      $('#reportDownLoadOptions').html('');
    });
  }

  var userFieldValues = <?= json_encode($userFieldValues); ?>;
  var teamSetup = <?= json_encode($teamSetup) ?>;

  var formData = {
    'team_size': '',
    'team_name_prefix': '<?= $_COMPANY->getAppCustomization()['teams']['name'] ?>',
    'starting_team_number': 1,
    'user_fields': [],
    'apply_min_rule': false,
    'min_rule_count': '',
    'min_rule_field': '',
    'min_rule_field_values': [],
  };

  var containerElement = $('#team-builder-container');

  if (typeof ejs === 'undefined') {
    $.ajaxSetup({cache: true});
    $.getScript('<?=TELESKOPE_CDN_STATIC?>/vendor/js/ejs-3.1.10/ejs.min.js', renderTeamBuilderForm);
  } else {
    renderTeamBuilderForm();
  }
</script>
