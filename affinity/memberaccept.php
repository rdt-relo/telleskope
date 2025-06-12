<?php
require_once __DIR__.'/head.php';
/* @var Company $_COMPANY */
/* @var User $_USER */
$showmessage = '';
$showerror = '';
$next_link = 'home';
$title = gettext('Accept Membership Invitation');

if(isset($_GET['id'])){ 
	$id = $_COMPANY->decodeId($_GET['id']);
	// fetch details
	$check  = $db->get("SELECT * FROM `memberinvites` WHERE `companyid`='{$_COMPANY->id()}' AND (`memberinviteid`='{$id}')");
	
	if (count($check)) {

		$groupid = (int)$check[0]['groupid'];
		$chapterid = (int)$check[0]['chapterid'];
		$channelid = (int)$check[0]['channelid'];
		$group = Group::GetGroup($groupid);

		if (strtolower($_USER->val('email')) != strtolower($check[0]['email'])) {
			if ($group?->val('group_type') != Group::GROUP_TYPE_OPEN_MEMBERSHIP) {

				// This is not an open group. Only users who match the email address will be allowed.
                $showerror = sprintf(gettext("Error: your email does not match the email to which the invitation was sent. You can still join a %s by selecting the 'Join' button on the %s page. Click 'Continue' to go to the homepage."),$_COMPANY->getAppCustomization()['group']['name-short'],$_COMPANY->getAppCustomization()['group']['name-short']);
				include(__DIR__ . "/views/showmsg_and_continue.html");
				exit();
			} else {
				// Just log the email who also use this invitation
				$other_data_arr = json_decode($check[0]['email']);
				$other_data_arr['emails'][] = $_USER->val('email');
				$other_data = json_encode($other_data_arr);
                $_USER->updateMemberInvitesOtherData($id, $other_data);
			}
		}

		// Things look ok, lets make this user join the group
        $joinLeaveHash = '';
        $all_chapter = Group::GetChapterList($groupid);
		if ($group && ($check[0]['status'] == "1")) {
            $group_chapter_assignment = '';

            if ($chapterid || $channelid) { // If explicitly provided
                $retVal = $_USER->joinGroup($groupid, $chapterid, $channelid);
            } elseif ($_COMPANY->getAppCustomization()['chapter']['enabled'] && !empty($all_chapter)) {
                if ($group->val('chapter_assign_type') == 'auto') {
                    $autoAssign = array();
                    // Go through each chapter and if the homeoffice matches list of branchids of chapter then assign chapter
                    foreach ($all_chapter as $chapter) {
                        $branchids = explode(',', $chapter['branchids']);
                        if (in_array($_USER->val('homeoffice'), $branchids)) {
                            $retVal = $_USER->joinGroup($groupid, $chapter['chapterid'], 0);
                            $autoAssign[] = $chapter['chaptername'];
                        }
                    }

                    // If no chapter was assigned then make the user join just the group.
                    if (count($autoAssign) === 0) {
                        $retVal = $_USER->joinGroup($groupid, 0, 0); // No matching chapter was found, so assign default group chapter
                        $autoAssign[] = 'a default';
                    }
                    $group_chapter_assignment = sprintf(gettext('You have joined the %1$s and you have been assigned %2$s %3$s.'), $group->val('groupname'), implode(',', $autoAssign), $_COMPANY->getAppCustomization()['chapter']['name-short']);

                } else {
                    // For all other usecases where chapter assignment is not auto, make user join group only and allow
                    // user to update chapter assignment on next screen, i.e. show #join_leave modal.

                    $retVal = $_USER->joinGroup($groupid, 0, 0);
                    $group_chapter_assignment = sprintf(gettext('You have joined the %1$s. Next, choose a %2$s to join'), $group->val('groupname'), $_COMPANY->getAppCustomization()['chapter']['name-short']);
                    $joinLeaveHash = '#join_leave'; // Show the join_leave modal
                }
            } else {
                $retVal = $_USER->joinGroup($groupid, 0, 0);
                $group_chapter_assignment = sprintf(gettext('You have joined the %1$s.'), $group->val('groupname'), $_COMPANY->getAppCustomization()['chapter']['name-short']);
            }

            if($retVal===false){
                $showerror = sprintf(gettext('You cannot join %1$s %2$s as you do not meet its membership requirements.' ), $group->val('groupname'), $_COMPANY->getAppCustomization()['group']['name-short']);
                include(__DIR__ . "/views/showmsg_and_continue.html");
				exit();
            }
            $showmessage  = gettext('Thank you for accepting the invitation.');
			$showmessage .= "<br>";
			$showmessage .= "<br>";
			$showmessage .= $group_chapter_assignment;
			$showmessage .= "<br>";
			$showmessage .= "<br>";
			$next_link = './detail?id='.$_COMPANY->encodeId($groupid).$joinLeaveHash;
		} else {
			$showerror = gettext("Error: You are trying to access an expired or incorrect URL. Click 'Continue' to go to the homepage");
		}
	} else {
		$showerror = gettext("Error: You are trying to access an expired or incorrect URL. Click 'Continue' to go to the homepage");
	}
include(__DIR__ . "/views/showmsg_and_continue.html");
}

