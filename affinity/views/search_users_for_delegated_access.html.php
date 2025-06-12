<?php if (!count($activeusers)) { ?>
  <select class="form-control userdata" name="grantee_userid" id="user_search" required>
    <option value=""><?= gettext("No match found.");?></option>
  </select>
  <?php return; ?>
<?php } ?>

<select class="form-control userdata" name="grantee_userid" onchange="closeDropdown()" id="user_search" required >
  <option value="">
    <?= gettext('Select an user (maximum of 20 matches are shown below)') ?>
  </option>
  <?php foreach ($activeusers as $user) { ?>
    <option value="<?= $_COMPANY->encodeId($user['userid']); ?>" ><?= rtrim(($user['firstname']." ".$user['lastname'])," ")." (". $user['email'].") - ".$user['jobtitle']; ?></option>
  <?php } ?>
</select>
