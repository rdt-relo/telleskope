<?php
##
##
##
## comment out the exit to run unit test
##
##
##
exit();
##
##
##
##
##
##

require_once __DIR__.'/../affinity/head.php';


/*
* Lets first configure the approval object.
 * Step 1: Delete all past configuration rows
 * Step 2: Add some new configurtion rows
 * Step 3: Print the newly configured configuration rows
 */

#Step 1
$rows = Event::GetAllApprovalConfigurationRows();
foreach ($rows as $configRow) {
    Event::DeleteApprovalConfiguration($configRow['approval_config_id']);
}
#Step 2
$id11=Event::CreateApprovalConfiguration(0,0,0,1,'47497','policy@teleskope.io');
$id12=Event::CreateApprovalConfiguration(0,0,0,2,'150753','policy2@teleskope.io');
$id1=Event::CreateApprovalConfiguration(3,0,0,1,'150753,326,1411','policy@teleskope.io');
$id2=Event::CreateApprovalConfiguration(3,2,0,1,'150753,47497,1411','policy@teleskope.io');
$id3=Event::CreateApprovalConfiguration(3,0,14,1,'150753,47813,1411','policy@teleskope.io');
Event::AddApproverToApprovalConfiguration($id1,47813);
Event::DeleteApproverFromApprovalConfiguration($id1,150753);
Event::UpdateApproverCCEmailsInApprovalConfiguration($id1,'policy@teleskope.io,policy2@teleskope.io');
$id4=Event::CreateApprovalConfiguration(3,0,0,2,'150753,115643,115640','policy@teleskope.io');
#Step 3
$rows = Event::GetAllApprovalConfigurationRows();
foreach ($rows as $configRow) {
    echo json_encode($configRow)."<br>";
}
echo "<hr>";

/*
 * In this UnitTest we will imlpement Approval Object on Event listed above.
 * We will test all tasks related to creating approval to changing the status.
 */
function printStatus(object $approval=null) {
    echo "<p>";
    if ($approval) {
        echo
            "Stage = " . $approval->val('approval_stage') .
            ", Requested=" . $approval->isApprovalStatusRequested() .
            ", Approved=" . $approval->isApprovalStatusApproved() .
            ", Denied=" . $approval->isApprovalStatusDenied() .
            ", Reset=" . $approval->isApprovalStatusReset() .
            ", Processing=" . $approval->isApprovalStatusProcessing().
            ", Assgined=" . $approval->isApprovalAssigned();
    } else {
        echo "Approval Object is null";
    }
    echo "</p>";
}

$eventid=327; // Note this event groupid = 3, chapterid = 5222,2 and channelid=14
$event = Event::GetEvent($eventid);
echo "Getting existing approvals for {$event->val('eventtitle')}<br>";
$approval = $event->getApprovalObject();
if ($approval) {
    printStatus($approval);
    echo "<hr>";
}
if ($approval) {
    echo "Deleting past approvals for {$event->val('eventtitle')}<br>";
    printStatus($approval);
    echo "<hr>";
    $approval->delete();
}
echo "<hr>";
echo "Request new approval for {$event->val('eventtitle')}<br>";
$approval = $event->requestNewApproval('Please approve my event'); // This one will create a new approval request
foreach ($approval->getApprovalLogs() as $l) {
    echo "<br>";
    echo json_encode($l);
    echo "<br>";
}
echo "<hr>";

echo "Print all approvers by stage<br>";
//$approvers = Event::GetAllApproversByStage(2, 3,11,13);
$approvers = Event::GetAllApproversByStage(1, 3, 2, 14);
echo "<br><br>Stage 1: " . json_encode($approvers) . "<br><br>";
$approvers = Event::GetAllApproversByStage(2, 0, 0, 0);
echo "<br><br>Stage 2: " . json_encode($approvers) . "<br><br>";
echo "<hr>";

echo "Make duplicate request for approval for {$event->val('eventtitle')}<br>";
$approval = $event->requestNewApproval('Please approve my event -duplicate'); // This one will do nothing
foreach ($approval->getApprovalLogs() as $l) {
    echo "<br>";
    echo json_encode($l);
    echo "<br>";
}
echo "<hr>";

printStatus($approval);
$approval->assignTo(47497, 'Hi 47497, please approve');
printStatus($approval);
$approval->assignTo(47813, 'Hi 47813, please approve');
printStatus($approval);
$approval->approve('Approved');
printStatus($approval);
$approval->deny('Denied');
printStatus($approval);
$approval->reset('Reset');
printStatus($approval);
$approval->assignTo(1411, 'Hi 1411, please approve');
printStatus($approval);
echo "<hr>";

foreach ($approval->getApprovalLogs() as $l) {
    echo "<br>".json_encode($l)."<br>";
}
echo "<hr>";
