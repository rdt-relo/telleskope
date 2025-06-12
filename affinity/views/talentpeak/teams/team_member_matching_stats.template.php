<div id="memberMatchingStatsModal" class="modal fade" tabindex="-1">
    <div aria-label="<?= $pageTitle; ?>" class="modal-dialog" aria-modal="true" role="dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="modal-title" class="modal-title"><?= $pageTitle; ?></h4>
                <button type="button" id="btn_close" class="close" aria-label="Close Modal Box" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
				<div class="col-md-12">
                        <div class="col-12 text-center">
                            <?= User::BuildProfilePictureImgTag($matchedUser['firstname'],$matchedUser['lastname'], $matchedUser['picture']??'','memberpic mb-2', 'User Profile Picture', $matchedUser['userid'], 'profile_full'); ?>
                        
                            <table class="table table-sm text-right">
                                <tr>
                                    <th class="text-left"><?= gettext('Name'); ?>:</th>
                                    <td><?= $matchedUser['firstname'].' '.$matchedUser['lastname']; ?></td>
                                </tr>
                                <tr>
                                    <th class="text-left"><?= gettext('Email'); ?>:</th>
                                    <td><?= $matchedUser['email']; ?></td>
                                </tr>
                                <tr>
                                    <th class="text-left"><?= gettext('Job Title'); ?>:</th>
                                    <td><?= $matchedUser['jobtitle']; ?></td>
                                </tr>
                                <tr>
                                    <th class="text-left"><?= gettext('Department'); ?>:</th>
                                    <td><?= $matchedUser['department']; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-12">
                            <table class='table table-sm'>
                                <tr><td class='text-nowrap'><strong><?=gettext("Parameter")?></strong></td><td>&nbsp;</td><td class="text-right"><strong><?= gettext("Matching")?> %</strong></td></tr>

                        <?php if($statsMatched){ ?>
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
                                <tr style='font-weight:normal'>
                                    <td class='td-matching-values'><?= $v['title']; ?><?= '<br>[ ' . ($matchedUserValues ?: '') . ' ]'; ?></td>
                                    <td>:</td>
                                    <td class="text-right"><?=$v['percentage'].'%'; ?></td>
                                </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr style='font-weight:normal'>
                            <td class='td-matching-values'>Matched based on role request</td>
                            <td>:</td>
                            <td class="text-right">0%</td>
                        </tr>

                    <?php } ?>
                            </table>
                        </div>
                    </div>
                
            </div>
            <div class="modal-footer text-center" style='text-align:center !important;display: block;'>
                <button type="submit" class="btn btn-affinity"  aria-hidden="true" data-dismiss="modal" ><?= gettext("Close");?></button>
            </div>
        </div>
    </div>
</div>
<script>
    $('#memberMatchingStatsModal').on('shown.bs.modal', function () {
    $('#btn_close').trigger('focus')
    });

    retainFocus('#memberMatchingStatsModal');
</script>
