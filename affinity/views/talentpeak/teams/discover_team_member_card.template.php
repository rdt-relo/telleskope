 <div class="col-md-4 speaker_card_container content<?= $_COMPANY->encodeId($joinRequest['roleid']); ?>" id="<?= $_COMPANY->encodeId($matchedUser['userid']); ?>">
    <div class="card pt-0 mb-3 rounded" style="width:100%">
        <div class="row">
            <div class="col-md-12 text-center">
                <div class="col-md-12 <?= $canAcceptRequest ? 'bg-success': 'bg-danger'?> py-2 mb-2 rounded-top text-white" style="line-height:15px;">
                    <small>
                        <strong><?= $bannerHeading; ?></strong>
                        <br><?= $bannerSubHeading; ?>
                        <?php if (0) { ?>
                        <br><?=gettext('Role Capacity')?>:<?= $roleSetCapacity; ?>
                        <br><?=gettext('Used Capacity')?>:<?= $roleUsedCapacity; ?>
                        <br><?=gettext('Pending Requests')?>:<?= $pendingSentOrReceivedRequestCount; ?>
                        <br><?=gettext('Available Request Capacity')?>:<?= $roleAvailableRequestCapacity; ?>
                        <?php } ?>
                    </small>
                </div>
                <br>                                   
            <?= User::BuildProfilePictureImgTag($matchedUser['firstname'], $matchedUser['lastname'], $matchedUser['picture'], 'memberpic','', $matchedUser['userid'], 'profile_full')?>
                <br>
                <strong class="pt-3"><?= $matchedUser['firstname'].' '.$matchedUser['lastname']; ?></strong>
                <br>
                <?= $matchingPercentage; ?>% <?= gettext("Match");?>
            <?php if (!empty($parameterWiseMatchingPercentage)){ ?>
                <span
                    style="cursor: pointer;"
                    role="button"
                    title='Match Detail'
                    data-html="true"
                    data-trigger="focus"
                    data-toggle="popover"
                    tabindex="0"
                    data-content="<div>
                                    <p><strong><?= gettext("Match with");?> : </strong><?= $matchedUser['firstname'] . ' ' . $matchedUser['lastname']; ?></p>
                                    <br/>
                                    <table class='table-sm'>
                                        <tr><td class='text-nowrap'><strong><?=gettext("Parameter")?></strong></td><td>&nbsp;</td><td><strong><?= gettext("Matching")?> %</strong></td></tr>
                                <?php foreach($parameterWiseMatchingPercentage as $k =>$v){
                                            $showPercentage = 'show';
                                            $showValue = 'hide';
                                            if ($v['attributeType']){
                                                $showPercentage = $group->getTeamMatchingAttributeKeyVisibilitySetting($v['attributeType'],$k,Group::MATCHING_ATTRIBUTES_VISIBILITY_KEYS['show_matchp_users']);
                                                $showValue = $group->getTeamMatchingAttributeKeyVisibilitySetting($v['attributeType'],$k,Group::MATCHING_ATTRIBUTES_VISIBILITY_KEYS['show_value_users']);

                                                $matchedUserValues = $v['value'];
                                                if (!empty($v['allValuesOfMatchedUserArr'])) {
                                                    $matchedUserValues = '';
                                                    foreach ($v['allValuesOfMatchedUserArr'] as $matchedUserValueRow) {
                                                        if ($matchedUserValueRow['is_matched']) {
                                                            $matchedUserValues = $matchedUserValues . '' . $matchedUserValueRow['value']. ', ';
                                                        } else {
                                                            $matchedUserValues = $matchedUserValues . '<em>' . $matchedUserValueRow['value']. '</em>, ';
                                                        }
                                                    }
                                                    $matchedUserValues = rtrim($matchedUserValues, ', ');
                                                }
                                            }
                                        ?>
                                    <?php if($showPercentage == 'show' || $showValue == 'show'){ ?>
                                        <tr style='font-weight: <?=  (!empty($mandatory) && $mandatory[$k]['is_required'] == 1) ? "bold": "normal" ;?>'>
                                            <td class='td-matching-values'><?= $v['title']; ?><?php if ($showValue == 'show'){ echo '<br>[ ' . ($matchedUserValues ?: '') . ' ]'; } ?></td>
                                            <td>:</td>
                                            <td><?= ($showPercentage == 'show') ? $v['percentage'].'%' : ''; ?></td>
                                        </tr>
                                    <?php } ?>

                                <?php } ?>
                                    </table>
                                </div>"
                >
                <i aria-label="<?= gettext("Match with ");?> <?= $matchedUser['firstname'] . ' ' . $matchedUser['lastname']; ?>" class="fa fa-info-circle btn-no-style" style="text-decoration:none;" ></i>
                </span>
            <?php } ?>
                <br>
                <small><strong><?=$joinRequest['oppositRolesType']?></strong> </small>
                <br>
                <small><?=$matchedUser['jobtitle']?> </small>
                <br>
                <small><?=$matchedUser['email']?> </small>
                <br>
                <small><?=$matchedUser['department']?></small>


                <?php
                if (
                        $_COMPANY->id() == 3700 /* Added by Aman - we want to remove the View Profile from this tile. The only customer using it is JPMC (companyid 3700), once they agree to not keep it, remove this entire if statement */
                        && $_COMPANY->getAppCustomization()['profile']['enable_bio']
                ) {
                ?>
                <br>
                <small><button class="btn btn-sm btn-link"  onclick="getProfileDetailedView(this,{'userid':'<?= $_COMPANY->encodeId($matchedUser['userid']); ?>', profile_detail_level: 'profile_full'})"><?= gettext('View Profile'); ?></button></small>
                <?php } ?>

            </div>
        </div>
        <div style="margin: 10px 0;">
        <?php if ($requestDetail && $requestDetail['status'] == 1){ ?>

            <a role="button" aria-label="<?= gettext("Delete Request For");?> <?= $matchedUser['firstname'] . ' ' . $matchedUser['lastname']; ?>" class="confirm btn btn-affinity" href="javascript:void(0);"  style="background-color:rgb(156, 156, 156);" onclick="deleteTeamRequest('<?= $_COMPANY->encodeId($groupid); ?>','<?= $_COMPANY->encodeId($requestDetail['team_request_id']); ?>','discover')" title="<?= gettext("Are you sure you want to delete this request?");?>" ><?= gettext("Delete Request");?></a>
        <?php } else { ?>
            <?php if ($canSendRequest && $canAcceptRequest){ ?>
            <a role="button" aria-label="<?= gettext("Send Request to");?> <?= $matchedUser['firstname'] . ' ' . $matchedUser['lastname']; ?>" class="btn btn-affinity" href="javascript:void(0);" onclick="openSendDiscoverPairRequestModal('<?= $_COMPANY->encodeId($groupid)?>','<?= $_COMPANY->encodeId($matchedUser['userid'])?>','<?= $_COMPANY->encodeId($joinRequest['oppositRoleId'])?>','<?= $_COMPANY->encodeId($joinRequest['roleid'])?>')" ><?= gettext("Send Request");?></a>
        <?php } else { ?>

            <a role="button" aria-label="<?= gettext("Send Request to");?> <?= $matchedUser['firstname'] . ' ' . $matchedUser['lastname']; ?>" class="btn btn-affinity-disabled"
                tabindex="0"
                <?php if($bannerHoverText || $bannerHoverTextSenderCapacity){ ?>
                    title=''
                    data-html="true"
                    data-trigger="focus hover"
                    data-toggle="popover"
                    data-placement="top"
                    data-content="<p class='py-2' style='max-width:250px !important'><?= $bannerHoverTextSenderCapacity ?: $bannerHoverText; ?></p>"
                <?php } ?>
                    href="javascript:void(0);" ><?= gettext("Send Request");?></a>
        <?php } ?>
        <?php } ?>
        </div>
    </div>
</div>