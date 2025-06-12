<!-- Basic Details-->
<div class="approval-section-heading">
            <strong><?=  ($topicTypeObj->val('event_series_id')) ?  gettext($topicTypeLabel." Series Details") : gettext($topicTypeLabel." Details") ?> </strong>
        </div>

        <div class="approval-heading">
            <td>  <strong><?= gettext($topicTypeLabel." ID") ?>:</strong> <span><?= $_COMPANY->encodeIdForReport($topicTypeObj->id());?></span></td>
        </div>
        
        <div class="approval-heading"> 
                <strong><?= sprintf(gettext("%s Name"), $_COMPANY->getAppCustomization()['group']["name-short"]) ?>: </strong><span><?= $topicGroupName ?></span>
        </div>
        <?php if( $listsNameCsv ){ ?>
                <div class="approval-heading">
                    <strong><?= gettext("Dynamic List") ?>: </strong><span><?= $listsNameCsv ?></span>
                </div>
        <?php } ?>

        <?php if(!empty($topicChapterName)){ ?>
            <div class="approval-heading"><strong> <?= sprintf(gettext("%s Name"), $_COMPANY->getAppCustomization()['chapter']["name-short"]) ?>: </strong> <span><?= $topicChapterName ?></span></div>
        <?php } ?>
        <?php if(!empty($topicChannelName)){ ?>
            <div class="approval-heading"><strong> <?= sprintf(gettext("%s Name"), $_COMPANY->getAppCustomization()['channel']["name-short"]) ?>: </strong> <span><?= $topicChannelName ?></span></div>
        <?php } ?>

        <div class="approval-heading"><strong><?=  ($topicTypeObj->val('event_series_id')) ?  gettext($topicTypeLabel." Series Title") : gettext($topicTypeLabel." Title") ?>: </strong> <span><?= $topicTypeObj->getTopicTitle(); ?></span></div>
        <div class="approval-heading"><strong><?=  ($topicTypeObj->val('event_series_id')) ? gettext($topicTypeLabel." Series Status") : gettext($topicTypeLabel." Status")?>: </strong> <span><?= $topicTypeObj->val('isactive') == 1 ? 'Published' : 'Not Published'; ?></span></div>

        <?php
        // URL
        $topicType_url = $_COMPANY->getAppURL($_ZONE->val('app_type')) . $viewPath.'?id=' . $_COMPANY->encodeId($topicTypeObj->id()) . '&approval_review=1';
        ?>
       <div class="approval-heading"><strong><?= ($topicTypeObj->val('event_series_id')) ? gettext($topicTypeLabel." Series Link") : gettext($topicTypeLabel." Link")?>: </strong> 
                <span style="font-size:1rem;">
                    <?php if($topicType == TELESKOPE::TOPIC_TYPES['SURVEY']){ ?>
                        <a rel="noopener" onclick="previewSurvey('<?= $encGroupId; ?>','<?= $enc_topictype_id; ?>')" href="javascript:void(0)">
                        <?= gettext("View Survey")?><br></a>
                    <?php }else{ ?>
                        <a href="<?= $topicType_url ?>" target="_blank" rel="noopener"><?= $topicType_url ?></a>
                    <?php } ?>
                </span>
        </div>