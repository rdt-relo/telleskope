<div class="approval-heading"><strong><?=gettext("Survey Type")?>: </strong> <span><?= $topicTypeObj->getSurveyTriggerLabel(); ?></span></div>

<div class="approval-heading"><strong><?=gettext("Is Anonymous")?>: </strong> <span><?= $topicTypeObj->val('anonymous') ? gettext("Yes") : gettext("No"); ?></span></div>

<div class="approval-heading"><strong><?=gettext("Responses required")?>: </strong> <span><?= $topicTypeObj->val('is_required') ? gettext("Yes") : gettext("No"); ?></span></div>

<div class="approval-heading"><strong><?=gettext("Allows Multiple Responses")?>: </strong> <span><?= $topicTypeObj->val('allow_multiple') ? gettext("Yes") : gettext("No"); ?></span></div>

<div class="approval-heading"><strong><?=gettext("Email Notification Email Addresses")?>:</strong> <?= $topicTypeObj->val('send_email_notification_to') ?> <span> </span></div>