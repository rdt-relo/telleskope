<?php
    $excludeFromView ??= array(); // Initialize if not already set.
?>


<?php if (empty($latestOrgData)) { ?>
<div class="approval-heading">
    <strong><?=gettext($topicTypeLabel." Organization Data")?>: </strong>
    [<?=gettext("Not set")?>]
</div>

<?php } else { ?>
<div class="approval-heading">
    <strong><?=gettext($topicTypeLabel." Organization Data")?>: </strong>
    <button class="btn-link btn-no-style org-data-open-close-js" name="org_details" id="org_details">[<?=gettext("View")?>]</button>
</div>
<div class="approval-section">
<?php foreach($latestOrgData as $orgData){
    if ($orgData['organization_id'] == null) {
        $orgData['organization_name'] = 'Error: Unable to fetch organization name';
        $orgData['organization_taxid'] = 'Error: Unable to fetch organization taxid';
        $orgData['organization_id'] = 0;
    }

    $enc_org_id = $_COMPANY->encodeId($orgData['organization_id']);
    $fields = ['custom_fields' => json_encode($orgData['custom_fields'])];
    $orgObj = Organization::Hydrate($orgData['company_org_id'], $fields);
    // Show all ORG data ?>                
    <div class="org-details approval-section-sub-block p-3 my-3 " style="display: none;">
        <div>
            <p><strong><?=gettext("Organization Name")?>:</strong> <?= $orgData['organization_name'] ?></p>
            <p><strong><?=gettext("Organization Tax ID")?>:</strong><?= $orgData['organization_taxid'] ?></p>
            <p><strong><?=gettext("Organization Address")?>:</strong> <?= ($orgData['street'] ?? '') .', '. ($orgData['city'] ?? '') .', '. ($orgData['state'] ?? '') .', '. ($orgData['country'] ?? '') .', '. ($orgData['zipcode'] ?? '') ?></p>
            <p><strong><?=gettext("Organization Website/URL")?>: </strong> <a href="<?= $orgData['org_url']?>" target="_blank"><?= $orgData['org_url'] ?? '' ?></a></p>
            <p><strong><?= gettext("CEO") ?>:</strong><?= !empty($orgData['ceo_firstname']) ? " {$orgData['ceo_firstname']} {$orgData['ceo_lastname']}" : " No information"; ?>
            <?= "(DOB - " . (!empty($orgData['ceo_dob']) ? $orgData['ceo_dob'] : "No information") . ")"; ?></p>
            <p><strong><?= gettext("CFO") ?>:</strong><?= !empty($orgData['cfo_firstname']) ? " {$orgData['cfo_firstname']} {$orgData['cfo_lastname']}" : " No information"; ?>
            <?= "(DOB - " . (!empty($orgData['cfo_dob']) ? $orgData['cfo_dob'] : "No information") . ")"; ?></p>
                <?php if($orgData['number_of_board_members']){ ?>
                <p><strong><?=gettext("Number of Board Members")?>:</strong> <?= $orgData['number_of_board_members']; ?></p>
                <?php } ?>
                <?php if($orgData['bm1_firstname']){ ?>
                <p><strong><?=gettext("Board Member 1")?>:</strong> <?= $orgData['bm1_firstname'] .' '.($orgData['bm1_lastname'] ?? '') ?>
                    <?= ($orgData['bm1_dob'] ?? '') ? "(DOB - {$orgData['bm1_dob']})" : ''?>
                </p>
                <?php } ?>
                <?php if($orgData['bm2_firstname']){ ?>
                <p><strong><?=gettext("Board Member 2")?>:</strong> <?= $orgData['bm2_firstname'] .' '.($orgData['bm2_lastname'] ?? '') ?>
                    <?= ($orgData['bm2_dob'] ?? '') ? "(DOB - {$orgData['bm2_dob']})" : ''?>
                </p>
                <?php } ?>
                <?php if($orgData['bm3_firstname']){ ?>
                <p><strong><?=gettext("Board Member 3")?>:</strong> <?= $orgData['bm3_firstname'] .' '.($orgData['bm3_lastname'] ?? '') ?>
                    <?= ($orgData['bm3_dob'] ?? '') ? "(DOB - {$orgData['bm3_dob']})" : ''?>
                </p>
                <?php } ?>
                <?php if($orgData['bm4_firstname']){ ?>
                <p><strong><?=gettext("Board Member 4")?>:</strong> <?= $orgData['bm4_firstname'] .' '.($orgData['bm4_lastname'] ?? '') ?>
                    <?= ($orgData['bm4_dob'] ?? '') ? "(DOB - {$orgData['bm4_dob']})" : ''?>
                </p>
                <?php } ?>
                <?php if($orgData['bm5_firstname']){ ?>
                <p><strong><?=gettext("Board Member 5")?>:</strong> <?= $orgData['bm5_firstname'] .' '.($orgData['bm5_lastname'] ?? '') ?>
                    <?= ($orgData['bm5_dob'] ?? '') ? "(DOB - {$orgData['bm5_dob']})" : ''?>
                </p>
                <?php } ?>
                <?php if($orgData['company_organization_notes']){ ?>
                <p><strong><?=gettext("Notes about this Organization")?>:</strong> <?= $orgData['company_organization_notes'] ?></p>
                <?php } ?>
                <?php if($orgData['organization_mission_statement']){ ?>
                <p><strong><?=gettext("Organization Mission")?>:</strong> <?= $orgData['organization_mission_statement'] ?></p>
                <?php } ?>

                <?= $orgObj->renderCustomFieldsComponent('v7'); ?>

                    <div class="org-status">
                    <strong><?=gettext("Organization Confirmation Status")?>:</strong>  
                    <?php
                        switch($orgData['last_confirmation_status']){

                        case Organization::ORGANIZATION_CONFIRMATION_STATUS['CONFIRMED']:  // confirmed
                        echo '<span class="label px-2 mx-2" style="background-color: darkgreen; color: white; border-radius: 3px;">Confirmed</span> <small>confirmed on '. $_USER->formatUTCDatetimeForDisplayInLocalTimezone($orgData['last_confirmation_date'],true,true,true) .'</small>';
                        break;

                        case Organization::ORGANIZATION_CONFIRMATION_STATUS['PENDING_CONFIRMATION']: // in progress, email sent
                            echo '<span class="label px-2 mx-2" style="background-color: darkblue; color: white; border-radius: 3px;">Pending Confirmation</span> <small>email sent on '. $_USER->formatUTCDatetimeForDisplayInLocalTimezone($orgData['last_confirmation_date'],true,true,true) .'</small>';
                            break;

                        //case Organization::ORGANIZATION_SCREENING_STATUS['NEW_ORGANIZATION']: //  Not-Approved
                        default: // reset, no data available for new organization
                            echo '<span class="label px-2 mx-2" style="background-color:#767676; color: white; border-radius: 3px;">'.gettext("New Organization").'</span>';
                    }
                    ?>                  

                <?php if (!isset($excludeFromView['organization_approvals']) && ($orgData['last_confirmation_status'] != Organization::ORGANIZATION_CONFIRMATION_STATUS['CONFIRMED'])) { ?>
                        <div class="ml-5">
                        <label for="org-status-select-<?=$enc_org_id?>" class="ml-2"><?=gettext("Update Status")?>:</label>
                        <?php $approval ??= null; ?>
                        <select class="ml-1 org-status-select" name="org-status-select" id="org-status-select-<?=$enc_org_id?>" data-approvalid="<?= $_COMPANY->encodeId($approval?->id() ?? 0) ?>" data-company-org-id="<?= $_COMPANY->encodeId($orgData['company_org_id']) ?>" data-company-org-status="ConfirmationStatus">
                            <option value=""><?=gettext('-- Select Status --')?></option>

                            <?php if($orgData['last_confirmation_status'] != Organization::ORGANIZATION_CONFIRMATION_STATUS['CONFIRMED']){ ?>
                            <option value="1" data-api-org-id="<?=$enc_org_id?>"><?=gettext('Confirmed')?></option>
                            <?php } ?>
                            <?php if ($orgData['last_confirmation_status'] != Organization::ORGANIZATION_CONFIRMATION_STATUS['PENDING_CONFIRMATION']){ // Not confirmed,one step back Pending Confirmation ?>
                            <option value="2" data-api-org-id="<?=$enc_org_id?>"><?=gettext('Pending Confirmation')?></option>
                            <?php } ?>
                        </select>
                        </div>
                <?php } ?>
                    </div>
                    <div class="org-status">
                        <strong><?=gettext("Organization Approval Status")?>:</strong>  <?php
                        switch($orgData['isactive']){
                            case Organization::STATUS_ACTIVE:  // Approvad
                                echo '<span class="label px-2 mx-2" style="background-color: darkgreen; color: white; border-radius: 3px;">Approved</span>';
                                break;
                            case Organization::STATUS_DRAFT: // in Draft
                                echo '<span class="label px-2 mx-2" style="background-color: darkblue; color: white; border-radius: 3px;">Draft</span>';
                                break;
                            case Organization::STATUS_INACTIVE: // in Draf
                                echo '<span class="label px-2 mx-2" style="background-color:darkred; color: white; border-radius: 3px;">Not Approved</span>';
                                break;

                        }
                        ?>
                <?php if (!isset($excludeFromView['organization_approvals'])) { ?>
                        <div class="ml-5">
                        <label for="org-status-select-<?=$enc_org_id?>" class="ml-2"><?=gettext("Update Status")?>:</label>
                        <?php $approval ??= null; ?>
                        <select class="ml-1 org-status-select" name="org-approval-status-select" id="org-approval-status-select-<?=$enc_org_id?>" data-approvalid="<?= $_COMPANY->encodeId($approval?->id() ?? 0) ?>" data-company-org-id="<?= $_COMPANY->encodeId($orgData['company_org_id']) ?>" data-company-org-status="ApprovalStatus">
                            <option value=""><?=gettext('-- Select Status --')?></option>

                            <?php if($orgData['isactive'] != Organization::STATUS_INACTIVE){ ?>
                            <option value="0" data-api-org-id="<?=$enc_org_id?>"><?=gettext('Not Approved')?></option>
                            <?php }  ?>
                            <?php if ($orgData['isactive'] != Organization::STATUS_ACTIVE){ // Not confirmed,one step back Pending Confirmation ?>
                            <option value="1" data-api-org-id="<?=$enc_org_id?>"><?=gettext('Approved')?></option>
                            <?php } ?>
                        </select>
                        </div>
                <?php } ?>

                    </div>
                
            </p>
        </div>
        <?php if (!empty($contactsArr = $orgData['additional_contacts']) || !empty($orgData['contact_email'])) { 
            $orgContactData = array(
                'firstname' => $orgData['contact_firstname'],
                'lastname' => $orgData['contact_lastname'],
                'email' => $orgData['contact_email']
            );
            array_unshift($contactsArr, $orgContactData);
            ?>
            <div class="org_details mt-3 ml-2" style="display: none;">
                <strong><?=gettext("Organization Contacts")?>:</strong>
                <table class="table table-bordered display bg-white">
                    <thead>
                        <tr>
                            <th class="p-1"><?=gettext("First Name")?></th>
                            <th class="p-1"><?=gettext("Last Name")?></th>
                            <th class="p-1"><?=gettext("Email")?></th>
                            <?php if (!isset($excludeFromView['organization_send_emails_to_contacts'])) { ?>
                            <th class="p-1"><?=gettext("Send Email to")?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>

                        <?php foreach ($contactsArr as $contactData) { $i=0; $i++;?>
                            <tr>
                                <td class="p-1"><?= $contactData['firstname'] ?></td>
                                <td class="p-1"><?= $contactData['lastname'] ?></td>
                                <td class="p-1"><?= $contactData['email'] ?></td>
                                <?php if (!isset($excludeFromView['organization_send_emails_to_contacts'])) { ?>
                                <td class="p-1 text-center">
                                    <input type="checkbox" name="contact-checkbox" class="contact-checkbox" data-email="<?= $contactData['email'] ?>" data-org-id="<?= $orgData['organization_id'] ?>">
                                </td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <?php if (!isset($excludeFromView['organization_send_emails_to_contacts'])) { ?>
                <div class="text-center">
                    <button class="btn btn-primary btn-sm org-send-emails" data-api-org-id="<?= $orgData['organization_id'] ?>" data-org-name="<?= $orgData['organization_name'] ?>" data-company-org-id="<?= $_COMPANY->encodeId($orgData['company_org_id']) ?>">Send Notification</button>
                </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
<?php } #end of foreach ?>
</div>
<?php } #end of if ?>

<!-- ORG data if it exists ends -->