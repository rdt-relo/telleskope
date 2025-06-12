<?php

######################################################
if (!Env::IsLocalEnv() && !Env::IsTestEnv()) exit();
######################################################

require_once __DIR__.'/head.php';
global $_USER; /* @var User $_USER */
global $_COMPANY; /* @var Company $_COMPANY */
global $_ZONE; /* @var Zone $_ZONE */

	// Function for update/delete data
	function main_db_sql($update){
        $dbrw = GlobalGetDBConnection();
		$query = mysqli_query($dbrw,$update) or (Logger::Log('Fatal Error Executing SQL', Logger::SEVERITY['FATAL_ERROR'], ['sql_err' => mysqli_error($dbrw), 'sql_stmt'=> $update]) and die(header(HTTP_INTERNAL_SERVER_ERROR). " *Internal Error: DBU-01*"));        

		if ($query) {
			if (mysqli_affected_rows($dbrw))
				return 1;
			else
				return -1;
		} else {
			return 0;
		}
	}//End

if (isset($_GET['admin'])) {
    $zoneid = $_ZONE->id();
    if (isset($_GET['global'])) {
        $zoneid = 0;
    }
    $budget = $_GET['budget'] ?? 0;
    $speaker = $_GET['speaker'] ?? 0;
    if ($_GET['admin'] === 'enable'){
        $_USER->assignAdminPermissions($zoneid, $budget, $speaker,1);
    } else {
        $_USER->revokeAdminPermissions($zoneid);
    }
    $_USER->clearSessionCache();
    Http::Redirect('./usrchk');
}

elseif (isset($_GET['grouplead_update']) ) {
    $gid = (int)$_GET['grouplead_update'];
    $a_rid = '';
    $d_rid = '';
    if (isset($_GET['add_regionid'])){
        $a_rid = raw2clean($_GET['add_regionid']);
    }
    if (isset($_GET['del_regionid'])){
        $d_rid = raw2clean($_GET['del_regionid']);
    }
    $glead = 0;

    $data = $db->get("SELECT leadid, grouplead_typeid, regionids FROM groupleads WHERE groupid='{$gid}' AND userid='{$_USER->id()}'");
    if (count($data)) {
        $glead = $data[0]['leadid'];
        $curr_regions = $data[0]['regionids'];
        $curr_typeid = $data[0]['grouplead_typeid'];
        $curr_regions_arr = explode(',',$curr_regions);
        $curr_regions_arr[] = $a_rid;
        if (($key = array_search($d_rid, $curr_regions_arr)) !== false) {
            unset($curr_regions_arr[$key]);
        }
        $curr_regions_arr = array_filter($curr_regions_arr);
        $new_regions = implode(',', $curr_regions_arr);
    } else{
        $new_regions = '';
    }



    if (isset($_REQUEST['typeid']) && $_REQUEST['typeid'] == 0) {
        // Delete the record.
        main_db_sql("DELETE FROM groupleads WHERE leadid='{$glead}'");
    } else {
        if (isset($_REQUEST['typeid'])) {
            $typeid = intval($_REQUEST['typeid']);
            $new_regions = '';
        } else {
            $typeid = intval($curr_typeid);
        }

        if ($glead) {
            main_db_sql("UPDATE groupleads SET grouplead_typeid='{$typeid}', regionids='{$new_regions}' WHERE leadid='{$glead}'");
        } else {
            main_db_sql("INSERT INTO groupleads (groupid, userid, regionids, grouplead_typeid, priority, assignedby, assigneddate, isactive) 
                                               VALUES ('{$gid}','{$_USER->id()}','',{$typeid},'',0,now(),1)");
        }
    }
    $_USER->clearSessionCache();
    Http::Redirect('./usrchk');
}

elseif (isset($_GET['chapterlead_update']) && isset($_GET['groupid'])) {
    $cid = (int)$_GET['chapterlead_update'];
    $gid = (int)$_GET['groupid'];
    $typeid = (int)$_GET['typeid'];
    $clead = '0';

    $data = $db->get("SELECT * FROM chapterleads WHERE groupid={$gid} and chapterid='{$cid}' AND userid='{$_USER->id()}'");


    if ($typeid == 0 && count($data)) {
        main_db_sql("DELETE from chapterleads WHERE chapterid='{$cid}' AND leadid='".$data[0]['leadid']."'");
    } elseif (count($data)) {
        main_db_sql("UPDATE chapterleads SET grouplead_typeid='{$typeid}' WHERE leadid='".$data[0]['leadid']."'");
    } else {
        main_db_sql("INSERT into chapterleads (chapterid, groupid, userid, grouplead_typeid, priority, assignedby, assigneddate) VALUES ('{$cid}','{$gid}','{$_USER->id()}','{$typeid}',0, 0, now())");
    }
    $_USER->clearSessionCache();
    Http::Redirect('./usrchk');
}

elseif (isset($_GET['channellead_update']) && isset($_GET['groupid'])) {
    $cid = (int)$_GET['channellead_update'];
    $gid = (int)$_GET['groupid'];
    $typeid = (int)$_GET['typeid'];
    $clead = '0';

    $data = $db->get("SELECT * FROM group_channel_leads WHERE groupid={$gid} and channelid='{$cid}' AND userid='{$_USER->id()}'");


    if ($typeid == 0 && count($data)) {
        main_db_sql("DELETE from group_channel_leads WHERE channelid='{$cid}' AND leadid='".$data[0]['leadid']."'");
    } elseif (count($data)) {
        main_db_sql("UPDATE group_channel_leads SET grouplead_typeid='{$typeid}' WHERE leadid='".$data[0]['leadid']."'");
    } else {
        main_db_sql("INSERT into group_channel_leads (channelid, groupid, userid, grouplead_typeid, priority, assignedby, assigneddate) VALUES ('{$cid}','{$gid}','{$_USER->id()}','{$typeid}',0, 0, now())");
    }
    $_USER->clearSessionCache();
    Http::Redirect('./usrchk');
}

elseif (isset($_GET['groupmember_update']) && isset($_GET['groupid'])) {
    $gid = (int)$_GET['groupid'];
    $cid = (int)($_GET['chapterid'] ?? 0);
    $channelid = (int)($_GET['channelid'] ?? 0);

    $data = $db->get("SELECT memberid,chapterid,channelids FROM groupmembers WHERE groupid='{$gid}' AND userid='{$_USER->id()}'");
    $c_arr = [0];
    $channel_arr = [0];

    if (count($data)) {
        if (!empty($data[0]['chapterid'])){
            $c_arr = explode(',',$data[0]['chapterid']);
            $channel_arr = explode(',',$data[0]['channelids']);
        }
    }

    if ($_GET['groupmember_update'] === 'enable') {
        if (!empty($cid)){
            $c_arr[] = $cid;
        }
        $chapters = implode(',',$c_arr);

        if (!empty($channelid)){
            $channel_arr[] = $channelid;
        }
        $channels = implode(',',$channel_arr);

        if (!count($data)){
            main_db_sql("INSERT INTO groupmembers (groupid, userid, chapterid, channelids,groupjoindate, notify_events, notify_posts, notify_news, isactive) 
                                                    VALUES ('{$gid}','{$_USER->id()}','{$chapters}','{$channels}',now(),1,1,1,1)");
            $_USER->addUserZone($_ZONE->id(), false, false);
        }else{
            main_db_sql("UPDATE  groupmembers SET chapterid='{$chapters}', channelids='{$channels}'WHERE memberid='{$data[0]['memberid']}'");
        }
    } elseif ($_GET['groupmember_update'] === 'disable') {
        if (!empty($cid)) {
            $index = array_search($cid, $c_arr);
            if($index !== false){
                unset($c_arr[$index]);
            }
        }
        $chapters = implode(',',$c_arr);

        if (!empty($channelid)) {
            $index = array_search($channelid, $channel_arr);
            if($index !== false){
                unset($channel_arr[$index]);
            }
        }
        $channels = implode(',',$channel_arr);

        if (count($data)) {
            if (empty($cid) && empty($channelid)){
                main_db_sql("DELETE FROM groupmembers WHERE memberid='{$data[0]['memberid']}'");
            }else{
                main_db_sql("UPDATE  groupmembers SET chapterid='{$chapters}',channelids='{$channels}' WHERE memberid='{$data[0]['memberid']}'");
            }
        }
    }
    $_USER->clearSessionCache();
    Http::Redirect('./usrchk');
}

elseif (isset($_GET['refresh_all_caches'])) {
    $_COMPANY = Company::GetCompany($_COMPANY->id(), true);
    $_USER->clearSessionCache();
}

elseif (isset($_GET['delete_me_logout'])) {
    $_USER->delete(forceDelete: true);
    setcookie(session_name(), '', time() - 3600, '/');
    $_SESSION = array();
    session_destroy();
    Http::Redirect('index');
} 
elseif (isset($_GET['purge_me_logout'])) {
    $_USER->purge();
    setcookie(session_name(), '', time() - 3600, '/');
    $_SESSION = array();
    session_destroy();
    Http::Redirect('index');
}
elseif(isset($_GET['enableDisableCompanyFeature'])){

    $status = $_GET['enableDisableCompanyFeature'] == 1 ? true : false;
    $section = $_GET['section'];
    $setting = [];
    
    if ($section =='chapter'){
        $setting['app']['chapter']['enabled'] = $status;
    } elseif($section =='channel'){
        $setting['app']['channel']['enabled'] = $status;
    } elseif($section =='list'){
        $setting['app']['dynamic_list']['enabled'] = $status;
    } elseif($section =='albums'){
        $setting['app']['albums']['enabled'] = $status;
    } elseif($section =='recognition'){
        $setting['app']['recognition']['enabled'] = $status;
    } elseif($section =='discussions'){
        $setting['app']['discussions']['enabled'] = $status;
    } elseif($section =='surveys'){
        $setting['app']['surveys']['enabled'] = $status;
    } elseif($section =='enablesurveyapprovals'){
        $setting['app']['surveys']['approvals']['enabled'] = $status;
        $setting['app']['surveys']['approvals']['tasks'] = $status;
    } elseif($section =='newsletters'){
        $setting['app']['newsletters']['enabled'] = $status;
    } elseif($section =='enablenewsletterapprovals'){
        $setting['app']['newsletters']['approvals']['enabled'] = $status;
        $setting['app']['newsletters']['approvals']['tasks'] = $status;
    } elseif($section =='budgets'){
        $setting['app']['budgets']['enabled'] = $status;
    } elseif($section =='budgetrequests'){
        $setting['app']['budgets']['enable_budget_requests'] = $status;
    } elseif($section =='event'){
        $setting['app']['event']['enabled'] = $status;
    } elseif($section =='post'){
        $setting['app']['post']['enabled'] = $status;
    } elseif($section =='enableannouncementapprovals'){
        $setting['app']['post']['approvals']['enabled'] = $status;
        $setting['app']['post']['approvals']['tasks'] = $status;
    } elseif($section =='resources'){
        $setting['app']['resources']['enabled'] = $status;
    } elseif($section =='disclaimer_consent'){
        $setting['app']['disclaimer_consent']['enabled'] = $status;
    } elseif($section =='enablehelpvideos'){
        $setting['app']['helpvideos']['enabled'] = $status;
    } elseif($section =='enablemyschedule'){
        $setting['app']['my_schedule']['enabled'] = $status;
    } elseif($section =='enablebooking'){
        $setting['app']['booking']['enabled'] = $status;
    } elseif($section =='enableeventreview'){
        $setting['app']['event']['require_email_review_before_publish'] = $status;
    } elseif($section =='enableeventapprovals'){
        $setting['app']['event']['approvals']['enabled'] = $status;
        $setting['app']['event']['approvals']['tasks'] = $status;
    } elseif($section =='my_events'){
        $setting['app']['event']['my_events']['enabled'] = $status;
    } elseif($section =='enableeventsurveys'){
        $setting['app']['event']['enable_event_surveys'] = $status;
    } elseif($section =='enableeventvolunteers') {
        $setting['app']['event']['volunteers'] = $status;
    } elseif($section =='enableeventspeakers') {
        $setting['app']['event']['speakers']['enabled'] = $status;
    }elseif($section =='enableeventspeakersapprovals') {
        $setting['app']['event']['speakers']['approvals'] = $status;
    } elseif($section =='enableeventbudgets') {
        $setting['app']['event']['budgets'] = $status;
    } elseif($section =='enableeventreconciliation') {
        $setting['app']['event']['reconciliation']['enabled'] = $status;
    } elseif($section =='enablepartnerorganizations') {
        $setting['app']['event']['partner_organizations']['enabled'] = $status;
    } elseif($section =='enabledmoduesettingsineventform') {
        $setting['app']['event']['event_form']['show_module_settings'] = $status;
    } elseif($section =='teams'){
        $setting['app']['teams']['enabled'] = $status;
    } elseif($section =='teams_teamevents'){
        $setting['app']['teams']['team_events']['enabled'] = $status;
    } elseif($section =='teams_teamevents_list'){
        $setting['app']['teams']['team_events']['event_list']['enabled'] = $status;
    } elseif($section =='teams_teambuilder'){
        $setting['app']['teams']['teambuilder_enabled'] = $status;
    } else{
        Http::Redirect('./usrchk');
    }

    $_ZONE->updateZoneCustomizationKeyVal($setting);
    $_USER->clearSessionCache();

    Http::Redirect('./usrchk');
}

?>
<style>

    tr:nth-child(even) {
        background-color: #efefef;
    }
    tr:nth-child(odd) {
        background-color: #dedede;
    }

    th {
        font-size: small;
        font-style: normal;
    }
    th, td {
        border-bottom: 1px solid #ddd;
        padding: 5px;
        text-align: left;
        color: #404040;
    }

    tr:hover {
        background-color: #fffcd4;
    }

    img {
        height: 40px;
        width: 40px;
        border-radius: 20px;
    }

    h3 {
        text-align: center;
    }
    tr.spaceUnder>td {
        padding: 2em 0;
    }
</style>

<body>
<div style="margin:10px;">
    <table style="margin: auto;border: 1px solid black;" summary="This table shows the user profile data">
        <tr>
            <td style="font-weight: bold;">
                <?php
                    $b= $db->get("SELECT regionid,branchname FROM companybranches WHERE branchid='{$_USER->val('homeoffice')}'");
                    if (empty($b)) {
                        $b = array('regionid'=>'0', 'branchname'=>'not set');
                    } else {
                        $b = $b[0];
                    }
                ?>
                <?= $_USER->val('firstname') ?> <?= $_USER->val('lastname') ?>
                <br><?= $_USER->val('email') ?>
                <br><span style="font-size: x-small">Account Type = [<?=$_USER->val('accounttype')?>]</span>
                <br><span style="font-size: x-small">Office Location = [<?=$b['branchname']?>(<?=$_USER->val('homeoffice')?>)]</span>
                <br><span style="font-size: x-small"> Regionid = [<?= $b['regionid']?>]</span>
            </td>
            <td style="">
                Mobile App Bearer Tokens
                <?php
                $sessions = $db->get("SELECT api_session_id FROM users_api_session WHERE companyid='{$_COMPANY->id()}' AND userid='{$_USER->id()}'");
                foreach ($sessions as $session) {
                    $token = encrypt_decrypt($_USER->cid() . ':' . $_USER->id() . ':' . $session['api_session_id'], 1);
                    echo "<br>{$token}";
                }
                ?>
            </td>
            <td style="background-color: #ffffff;">
                <a href="?refresh_all_caches=1" style="background-color:#008333;color: #ffffff;padding: 3px;font-size: small;">[Refresh All Caches]</a>
                <br/>
                <br/>
                <a href="?purge_me_logout" style="background-color:#cc9500;color: #ffffff;padding: 3px;font-size: small;"> [!!! Delete Me (Soft) !!!]</a>
                <br/>
                <br/>
                <a href="?delete_me_logout" style="background-color:#aa0000;color: #ffffff;padding: 3px;font-size: small;"> [!!! Delete Me !!!]</a>
            </td>
        </tr>
        <tr><td colspan="3">
                    Zone:<strong>
            <?php foreach ($_COMPANY->getZones() as $zz) { ?>
                    <?=($zz['zoneid']==$_ZONE->id())? $zz['zonename']: ''?>
            <?php } ?>
                </strong>... change in affinities
                . .. ... <a href='home'>back</a>
            </td>
        </tr>
    </table>

    <p>
    <div>
    Global Administrator:
        <?php if ($_USER->isCompanyAdmin()) { ?>
            <span style='color:green;'>Yes</span>&nbsp;...&nbsp;<a href="?admin=disable&global=1">disable</a>
        <?php } else { ?>
            <span style='color:red;'>No</span> &nbsp;...&nbsp;<a href="?admin=enable&global=1">enable</a>
        <?php } ?>
        <br>
        Zone Administrator:
        <?php if ($_USER->isZoneAdmin($_ZONE->id())) { ?>
            <span style='color:green;'>Yes</span>&nbsp;...&nbsp;<a href="?admin=disable">disable</a>
        <?php } else { ?>
            <span style='color:red;'>No</span> &nbsp;...&nbsp;Enable <a href="?admin=enable&budget=1&speaker=1">all_permissions</a>,
            <a href="?admin=enable&budget=1&speaker=0">budget_only</a>,
            <a href="?admin=enable&budget=0&speaker=1">events_only</a>
            <a href="?admin=enable&budget=0&speaker=0">none</a>
        <?php } ?>
    </div>
    <br>
    <table summary="This table display user's access levels">
        <tr>
            <td>
            <ul>
                <li>Is Active:<?= $_USER->isActive() ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></li>
                <li>Is Verified:<?= $_USER->isVerified() ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></li>
                <li>Can manage company settings:<?= $_USER->canManageCompanySettings() ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></li>
                <li>Can manage Affinities Budget:<?= $_USER->canManageZoneBudget() ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></li>
                <li>Can manage Affinities Events:<?= $_USER->canManageZoneSpeakers() ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></li>
                <li>Can manage Affinities Content:<?= $_USER->canManageAffinitiesContent() ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></li>
                <li>Can manage Affinities Users:<?= $_USER->canManageAffinitiesUsers() ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></li>
                <li>Can manage Company Surveys:<?= $_USER->canManageCompanySurveys() ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></li>

                <li>Is some manager:<?= $_USER->canManageCompanySomething() ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></li>
                <li>Is some creator:<?= $_USER->canCreateContentInCompanySomething() ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></li>
                <li>Is some publisher:<?= $_USER->canPublishContentInCompanySomething() ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></li>
                <li>Is some budget manager:<?= $_USER->canManageBudgetCompanySomething() ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></li>
                <li>Is some role grantor:<?= $_USER->canManageGrantCompanySomething() ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></li>
            </ul>
            <hr>
                Manage Company Zone Features:
                <table summary="This table display user's access levels" style="width: 100%;">
                    <tr>
                        <td>
                           <?= $_COMPANY->getAppCustomization()['chapter']['name-short']; ?>:
                        </td>
                        <td>
                        <input type="checkbox" id="enablechapter" name="enablechapter" value="1" <?= $_COMPANY->getAppCustomization()['chapter']['enabled'] ? 'checked' : ''; ?> >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?= $_COMPANY->getAppCustomization()['channel']['name-short']; ?>:
                        </td>
                        <td>
                        <input type="checkbox" id="enablechannel" name="enablechannel" value="1" <?= $_COMPANY->getAppCustomization()['channel']['enabled'] ? 'checked' : ''; ?> >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Dynamic List
                        </td>
                        <td>
                            <input type="checkbox" id="enablelist" name="enablelist" value="1" <?= $_COMPANY->getAppCustomization()['dynamic_list']['enabled'] ? 'checked' : ''; ?> >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Announcement:
                            <br> - Approvals & Tasks
                        </td>
                        <td>
                        <input type="checkbox" id="enablepost" name="enablepost" value="1" <?= $_COMPANY->getAppCustomization()['post']['enabled'] ? 'checked' : ''; ?> >
                        <br>
                        <input type="checkbox" id="enableannouncementapprovals" name="enableannouncementapprovals" value="1" <?= $_COMPANY->getAppCustomization()['post']['approvals']['enabled'] ? 'checked' : ''; ?> >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Event:
                            <br> - My Events:
                            <br> - Event Reviews:
                            <br> - Approvals & Tasks:
                            <br> - Event Surveys:
                            <br> - Event Volunteers:
                            <br> - Event Speakers:
                            <br> - Speakers Approvals:
                            <br> - Event Budgets:
                            <br> - Event Reconciliation:
                            <br> - Partner Organizations:
                            <br> - Module setting in form:
                        </td>
                        <td>
                            <input type="checkbox" id="enableevent" name="enableevent" value="1" <?= $_COMPANY->getAppCustomization()['event']['enabled'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enablemyevents" name="enablemyevents" value="1" <?= $_COMPANY->getAppCustomization()['event']['my_events']['enabled'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enableeventreview" name="enableeventreview" value="1" <?= $_COMPANY->getAppCustomization()['event']['require_email_review_before_publish'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enableeventapprovals" name="enableeventapprovals" value="1" <?= $_COMPANY->getAppCustomization()['event']['approvals']['enabled'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enableeventsurveys" name="enableeventsurveys" value="1" <?= $_COMPANY->getAppCustomization()['event']['enable_event_surveys'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enableeventvolunteers" name="enableeventvolunteers" value="1" <?= $_COMPANY->getAppCustomization()['event']['volunteers'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enableeventspeakers" name="enableeventspeakers" value="1" <?= $_COMPANY->getAppCustomization()['event']['speakers']['enabled'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enableeventspeakersapprovals" name="enableeventspeakersapprovals" value="1" <?= $_COMPANY->getAppCustomization()['event']['speakers']['approvals'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enableeventbudgets" name="enableeventbudgets" value="1" <?= $_COMPANY->getAppCustomization()['event']['budgets'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enableeventreconciliation" name="enableeventreconciliation" value="1" <?= $_COMPANY->getAppCustomization()['event']['reconciliation']['enabled'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enablepartnerorganizations" name="enablepartnerorganizations" value="1" <?= $_COMPANY->getAppCustomization()['event']['partner_organizations']['enabled'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enabledmoduesettingsineventform" name="enabledmoduesettingsineventform" value="1" <?= $_COMPANY->getAppCustomization()['event']['event_form']['show_module_settings'] ? 'checked' : ''; ?> >

                        </td>
                    </tr>
                    <tr>
                        <td>
                            Newsletter:
                            <br> - Approvals & Tasks:
                        </td>
                        <td>
                            <input type="checkbox" id="enablenewsletters" name="enablenewsletters" value="1" <?= $_COMPANY->getAppCustomization()['newsletters']['enabled'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enablenewsletterapprovals" name="enablenewsletterapprovals" value="1" <?= $_COMPANY->getAppCustomization()['newsletters']['approvals']['enabled'] ? 'checked' : ''; ?> >
                        </td>
                        <br>
                    </tr>
                    <tr>
                        <td>
                            Surveys:
                            <br> - Approvals & Tasks:
                        </td>
                        <td>
                            <input type="checkbox" id="enablesurveys" name="enablesurveys" value="1" <?= $_COMPANY->getAppCustomization()['surveys']['enabled'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enablesurveyapprovals" name="enablesurveyapprovals" value="1" <?= $_COMPANY->getAppCustomization()['surveys']['approvals']['enabled'] ? 'checked' : ''; ?> >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Album:
                        </td>
                        <td>
                            <input type="checkbox" id="enablealbums" name="enablealbums" value="1" <?= $_COMPANY->getAppCustomization()['albums']['enabled'] ? 'checked' : ''; ?> >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Recognition:
                        </td>
                        <td>
                            <input type="checkbox" id="enablerecognition" name="enablerecognition" value="1" <?= $_COMPANY->getAppCustomization()['recognition']['enabled'] ? 'checked' : ''; ?> >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Budgets:
                            <br>
                            Budget Requests
                        </td>
                        <td>
                            <input type="checkbox" id="enablebudgets" name="enablebudgets" value="1" <?= $_COMPANY->getAppCustomization()['budgets']['enabled'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enablebudgetrequests" name="enablebudgetrequests" value="1" <?= $_COMPANY->getAppCustomization()['budgets']['enable_budget_requests'] ? 'checked' : ''; ?> >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Discussions:
                        </td>
                        <td>
                            <input type="checkbox" id="enablediscussions" name="enablediscussions" value="1" <?= $_COMPANY->getAppCustomization()['discussions']['enabled'] ? 'checked' : ''; ?> >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Resources:
                        </td>
                        <td>
                            <input type="checkbox" id="enableresources" name="enableresources" value="1" <?= $_COMPANY->getAppCustomization()['resources']['enabled'] ? 'checked' : ''; ?> >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Disclaimers:
                        </td>
                        <td>
                            <input type="checkbox" id="enabledisclaimers" name="enabledisclaimers" value="1" <?= $_COMPANY->getAppCustomization()['disclaimer_consent']['enabled'] ? 'checked' : ''; ?> >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Help Videos:
                        </td>
                        <td>
                            <input type="checkbox" id="enablehelpvideos" name="enablehelpvideos" value="1" <?= $_COMPANY->getAppCustomization()['helpvideos']['enabled'] ? 'checked' : ''; ?> >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Teams:
                            <br>- Team Events:
                            <br>- Team Event List:
                            <br>- Teambuilder:
                        </td>
                        <td>
                            <input type="checkbox" id="enableteams" name="enableteams" value="1" <?= $_COMPANY->getAppCustomization()['teams']['enabled'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enableteams_teamevents" name="enableteams_teamevents" value="1" <?= $_COMPANY->getAppCustomization()['teams']['team_events']['enabled'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enableteams_teamevents_list" name="enableteams_teamevents_list" value="1" <?= $_COMPANY->getAppCustomization()['teams']['team_events']['event_list']['enabled'] ? 'checked' : ''; ?> >
                            <br>
                            <input type="checkbox" id="enableteams_teambuilder" name="enableteams_teambuilder" value="1" <?= $_COMPANY->getAppCustomization()['teams']['teambuilder_enabled'] ? 'checked' : ''; ?> >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            My Schedule:
                        </td>
                        <td>
                            <input type="checkbox" id="enablemyschedule" name="enablemyschedule" value="1" <?= $_COMPANY->getAppCustomization()['my_schedule']['enabled'] ? 'checked' : ''; ?> >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Bookings:
                        </td>
                        <td>
                            <input type="checkbox" id="enablebooking" name="enablebooking" value="1" <?= $_COMPANY->getAppCustomization()['booking']['enabled'] ? 'checked' : ''; ?> >
                        </td>
                    </tr>
                </table>
            </td>
            <td style="font-size: small;">Company Regions:
                <ul>
                    <?php foreach ($_COMPANY->getAllRegions() as $region) {?>
                        <li><?= $region['regionid']?> - <?= $region['region']?> (<?= $region['isactive']?>)</li>
                <?php } ?>
                </ul>
            </td>
            <td style="font-size: small;">Company Grouplead Types
                <table summary="This table display the company grouplead types">
                    <tr>
                    <th scope="col">Type</th>
                    <th scope="col">Label</th>
                    <th scope="col">Manage</th>
                    <th scope="col">Create</th>
                    <th scope="col">Publish</th>
                    <th scope="col">Budget</th>
                    <th scope="col">Grant</th>
                    <th scope="col">Support</th>
                    </tr>
                    <?php foreach ($_COMPANY->getGroupLeadtypesOfExecutiveType() as $gl1) {?>
                        <tr>
                            <td>Group-leader</td>
                            <td><?=htmlspecialchars(substr($gl1['type'],0,20).((strlen($gl1['type'])>20)?'...':''))?></td>
                            <td><?= ($gl1['allow_manage']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_create_content']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_publish_content']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_manage_budget']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_manage_grant']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_manage_support']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                        </tr>
                    <?php } ?>
                    <?php foreach ($_COMPANY->getGroupLeadtypesOfGroupType() as $gl1) {?>
                        <tr>
                            <td>Group-leader</td>
                            <td><?=htmlspecialchars(substr($gl1['type'],0,20).((strlen($gl1['type'])>20)?'...':''))?></td>
                            <td><?= ($gl1['allow_manage']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_create_content']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_publish_content']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_manage_budget']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_manage_grant']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_manage_support']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                        </tr>
                    <?php } ?>
                    <?php foreach ($_COMPANY->getGroupLeadtypesOfRegionalType() as $gl1) {?>
                        <tr>
                            <td>Regional-leader</td>
                            <td><?=htmlspecialchars(substr($gl1['type'],0,20).((strlen($gl1['type'])>20)?'...':''))?></td>
                            <td><?= ($gl1['allow_manage']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_create_content']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_publish_content']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_manage_budget']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_manage_grant']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_manage_support']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                        </tr>
                    <?php } ?>
                </table>
            </td>
            <td style="font-size: small;">Company Chapterlead Types
                <table summary="This table display the list of company chapter leader types">
                    <tr>
                        <th scope="col">Type</th>
                        <th scope="col">Label</th>
                        <th scope="col">Manage</th>
                        <th scope="col">Create</th>
                        <th scope="col">Publish</th>
                        <th scope="col">Budget</th>
                        <th scope="col">Grant</th>
                        <th scope="col">Support</th>
                    </tr>
                    <?php foreach ($_COMPANY->getGroupLeadtypesOfChapterType() as $gl1) {?>
                        <tr>
                            <td>Chapter-leader</td>
                            <td><?=htmlspecialchars(substr($gl1['type'],0,20).((strlen($gl1['type'])>20)?'...':''))?></td>
                            <td><?= ($gl1['allow_manage']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_create_content']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_publish_content']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_manage_budget']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_manage_grant']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_manage_support']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                        </tr>
                    <?php } ?>
                </table>
                <hr>
                Company Channellead Types
                <table summary="This table display the company channel leader type">
                    <tr>
                        <th scope="col">Type</th>
                        <th scope="col">Label</th>
                        <th scope="col">Manage</th>
                        <th scope="col">Create</th>
                        <th scope="col">Publish</th>
                        <th scope="col">Budget</th>
                        <th scope="col">Grant</th>
                        <th scope="col">Support</th>
                    </tr>
                    <?php foreach ($_COMPANY->getGroupLeadtypesOfChannelType() as $gl1) {?>
                        <tr>
                            <td>Channel-leader</td>
                            <td><?=htmlspecialchars(substr($gl1['type'],0,20).((strlen($gl1['type'])>20)?'...':''))?></td>
                            <td><?= ($gl1['allow_manage']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_create_content']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_publish_content']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_manage_budget']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_manage_grant']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                            <td><?= ($gl1['allow_manage_support']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>"?></td>
                        </tr>
                    <?php } ?>
                </table>
            </td>
        </tr>

    </table>
    </p>
    <?php $groups = Group::GetAllGroupsByCompanyid($_USER->cid(),$_ZONE->id(),true); ?>

    <table summary="This table display the user permissions" style="font-size: small;">
        <tr>
            <th style="width:20%;" scope="col">Group</th>
            <th style="width:40%;" scope="col">Chapters</th>
            <th style="width:40%;" scope="col">Channels</th>
        </tr>
        <?php foreach ($groups as $g) { ?>
            <tr class="spaceUnder">
                <td style="background-color: <?= $g->val('isactive') == 0 ? '#ffffce' : ($g->val('isactive') == 100 ? '#fde1e1' : 'inherit') ?>;">
                    <strong><?= $g->val('groupname'); ?></strong>
                    <br>(<?= $g->val('groupname_short'); ?>)
                    <br><span style="font-size: x-small">&emsp;Status = <?= $g->val('isactive') ?></span>
                    <br><span style="font-size: x-small">&emsp;Regions = <?= $g->val('regionid') ?></span>
                    <br><span style="font-size: x-small">&emsp;Chapter Assignment = <?= $g->val('chapter_assign_type') ?></span>

                <?php
                    $my_group_lead = $_USER->getMyGroupleadRecords($g->id());
                    if(count($my_group_lead))
                        $my_lead = array_pop($my_group_lead);
                    else
                        $my_lead = array();
                ?>
<hr>Member: <?= $_USER->isGroupMember($g->id()) ? "<span style='color:green;'>yes</span><a href='?groupmember_update=disable&groupid={$g->id()}&chapterid=0' style='font-size: x-small;'>&nbsp;...remove</a>" : "<span style='color:red;'>no</span><a href='?groupmember_update=enable&groupid={$g->id()}&chapterid=0' style='font-size: x-small;'>&nbsp;...add</a>" ?>
<hr>Group Leader: <?= $_USER->isGrouplead($g->id()) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
<br>&emsp;Manage: <?= $_USER->canManageGroup($g->id()) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
<br>&emsp;Create: <?= $_USER->canCreateContentInGroup($g->id()) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
<br>&emsp;Publish: <?= $_USER->canPublishContentInGroup($g->id()) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
<br>&emsp;Budget: <?= $_USER->canManageBudgetGroup($g->id()) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
<br>&emsp;Grant: <?= $_USER->canManageGrantGroup($g->id()) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
<br>&emsp;Support: <?= $_USER->canManageSupportGroup($g->id()) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
<br>
                    <br><span style="font-size: small">Change Grouplead Role</span>
                    <form name="assign-lead" action="" type="GET"?>
                        <input type="hidden" name="zoneid" value="<?=$_COMPANY->encodeId($_ZONE->id())?>">
                        <input type="hidden" name="grouplead_update" value="<?=$g->id()?>">
                    <select id="typeid" name="typeid" >
                        <option value="0">-None-</option>
                        <?php foreach ($_COMPANY->getGroupLeadtypesOfExecutiveType() as $l) { ?>
                            <option value="<?=$l['typeid']?>" <?= (isset($my_lead['grouplead_typeid']) && $my_lead['grouplead_typeid'] == $l['typeid'])?'selected':''?> ><?=htmlspecialchars($l['type'])?></option>
                        <?php }?>
                        <?php foreach ($_COMPANY->getGroupLeadtypesOfGroupType() as $l) { ?>
                            <option value="<?=$l['typeid']?>" <?= (isset($my_lead['grouplead_typeid']) && $my_lead['grouplead_typeid'] == $l['typeid'])?'selected':''?> ><?=htmlspecialchars($l['type'])?></option>
                        <?php }?>
                        <?php foreach ($_COMPANY->getGroupLeadtypesOfRegionalType() as $l) { ?>
                            <option value="<?=$l['typeid']?>" <?= (isset($my_lead['grouplead_typeid']) && $my_lead['grouplead_typeid'] == $l['typeid'])?'selected':''?> ><?=htmlspecialchars($l['type'])?></option>
                        <?php }?>
                    </select>
                        &nbsp;<input type="submit" value="update" class="prevent-multi-clicks"></input>
                    </form>
                    <?php
                        if (!empty($my_lead) && $my_lead['sys_leadtype'] == 3) {

                            //$leadregions_arr = explode(',', $my_lead['regionids']);
                            $groupregions_arr = explode(',', $g->val('regionid'));
                            if (count($groupregions_arr)) {
                                echo "<span style='font-size: small;'>Restrict to Region </span>";
                                foreach ($groupregions_arr as $group_region_id) {
                                    if ($_USER->isRegionallead($g->id(),$group_region_id) !== false) {// Regional Lead
                                        echo "<br><span style='font-size: x-small;color:green;'> {$group_region_id}</span><a href='?grouplead_update={$g->id()}&del_regionid={$group_region_id}' style='font-size: x-small;'>&nbsp;...unset</a>";
                                    } else {
                                        echo "<br><span style='font-size: x-small;color:red;'> {$group_region_id}</span><a href='?grouplead_update={$g->id()}&add_regionid={$group_region_id}' style='font-size: x-small;'>&nbsp;...set</a>";
                                    }
                                }
                            }
                        }
                     ?>
<hr>
<br>Some Manager: <?= $_USER->canManageGroupSomething($g->id()) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
<br>Some Creator: <?= $_USER->canCreateContentInGroupSomething($g->id()) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
<br>Some Publisher: <?= $_USER->canPublishContentInGroupSomething($g->id()) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
<br>Some Budget Mgr: <?= $_USER->canManageBudgetGroupSomething($g->id()) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
<br>Some Role Grant: <?= $_USER->canManageGrantGroupSomething($g->id()) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
<br>Some Support: <?= $_USER->canManageGrantGroupSomething($g->id()) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
<br>
<hr>
<pre>
<strong>C,[UD/UP],P,M,B,G </strong>: <?= $_USER->canCreateContentInScopeCSV($g->id(),0,0) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,[<?= $_USER->canUpdateContentInScopeCSV($g->id(),0,0, 2) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>/<?= $_USER->canUpdateContentInScopeCSV($g->id(),0,0, 1) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>],<?= $_USER->canPublishContentInScopeCSV($g->id(),0,0) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,<?= $_USER->canManageContentInScopeCSV($g->id(),0,0) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,<?= $_USER->canManageBudgetInScope($g->id(),0,0) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,NA
</pre>
                </td>
                <td>
                    <?php $chapters = Group::GetChapterList($g->id()); ?>
                    <?php if (isset($chapters) && count($chapters)) { $rrrr = 0; $chapter1=null; ?>
                        <table style="font-size: small" summary="This table display the list of chapters">
                            <tr>
                                <th scope="col">Chapter</th>
                                <th scope="col">Leader</th>
                                <th scope="col">Member</th>
                                <th scope="col">Mgr</th>
                                <th scope="col">Cre</th>
                                <th scope="col">Pub</th>
                                <th scope="col">Bgt</th>
                                <th scope="col">Gr</th>
                                <th scope="col">Su</th>
                            </tr>
                            <?php foreach ($chapters as $chapter) { if ($chapter1 === null) $chapter1=$chapter; ?>
                                <?php
                                $ccc = $_USER->getMyChapterleadRecords($chapter['groupid'], $chapter['chapterid']);
                                if(count($ccc))
                                    $my_chapter_lead = array_pop($ccc);
                                else
                                    $my_chapter_lead = array();
                                $rrrr = $chapter['regionids'];
                                ?>

                                <tr>
                                    <td style="background-color: <?= $chapter['isactive'] == 0 ? '#ffffce' : ($chapter['isactive'] == 100 ? '#fde1e1' : 'inherit') ?>;">
                                        <?= substr($chapter['chaptername'],0,20).((strlen($chapter['chaptername'])>20)?'...':'') ?>
                                        <br><span style="font-size: x-small">Status = <?= $chapter['isactive'] ?></span>
                                        <br><span style="font-size: x-small">Regiond = <?= $chapter['regionids'] ?></span>
                                        <hr>
<pre>
<strong>C,[UD/UP],P,M,B,G </strong>: <?= $_USER->canCreateContentInScopeCSV($g->id(),$chapter['chapterid'],0) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,[<?= $_USER->canUpdateContentInScopeCSV($g->id(),$chapter['chapterid'],0, 2) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>/<?= $_USER->canUpdateContentInScopeCSV($g->id(),$chapter['chapterid'],0, 1) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>],<?= $_USER->canPublishContentInScopeCSV($g->id(),$chapter['chapterid'],0) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,<?= $_USER->canManageContentInScopeCSV($g->id(),$chapter['chapterid'],0) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,<?= $_USER->canManageBudgetInScope($g->id(),$chapter['chapterid'],0) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,NA
</pre>
                                    <td>
                                        <?=$_COMPANY->getAppCustomization()['chapter']['name-short']?> Leader: <?= $_USER->isChapterlead($g->id(),$chapter['chapterid']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
                                        <form name="assign-chapter-lead" action="" type="GET">
                                            <input type="hidden" name="zoneid" value="<?=$_COMPANY->encodeId($_ZONE->id())?>">
                                            <input type="hidden" name="groupid" value="<?=$g->id()?>">
                                            <input type="hidden" name="chapterlead_update" value="<?=$chapter['chapterid']?>">
                                            <select id="typeid" name="typeid" >
                                                <option value="0">-None-</option>
                                                <?php foreach ($_COMPANY->getGroupLeadtypesOfChapterType() as $l) { ?>
                                                    <option value="<?=$l['typeid']?>" <?= (isset($my_chapter_lead['grouplead_typeid']) && $my_chapter_lead['grouplead_typeid'] == $l['typeid'])?'selected':''?> >
                                                        <?=htmlspecialchars(substr($l['type'],0,20).((strlen($l['type'])>20)?'...':''))?>
                                                    </option>
                                                <?php }?>
                                            </select>
                                            <input type="submit" value="update" class="prevent-multi-clicks"></input>
                                        </form>
                                    </td>
                                    <td><?= $_USER->isGroupMember($g->id(), $chapter['chapterid']) ? "<span style='color:green;'>yes</span><a href='?groupmember_update=disable&groupid={$g->id()}&chapterid={$chapter['chapterid']}' style='font-size: x-small;'>&nbsp;...remove</a>" : "<span style='color:red;'>no</span><a href='?groupmember_update=enable&groupid={$g->id()}&chapterid={$chapter['chapterid']}' style='font-size: x-small;'>&nbsp;...add</a>" ?> </td>
                                    <td><?= $_USER->canManageGroupChapter($g->id(), $chapter['regionids'], $chapter['chapterid']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                    <td><?= $_USER->canCreateContentInGroupChapter($g->id(), $chapter['regionids'], $chapter['chapterid']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                    <td><?= $_USER->canPublishContentInGroupChapter($g->id(), $chapter['regionids'], $chapter['chapterid']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                    <td><?= $_USER->canManageBudgetInScope($g->id(), $chapter['chapterid'],0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                    <td><?= $_USER->canManageGrantGroupChapter($g->id(), $chapter['regionids'], $chapter['chapterid']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                    <td><?= $_USER->canManageSupportGroupChapter($g->id(), $chapter['regionids'], $chapter['chapterid']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td style="background-color: <?= $chapter['isactive'] == 0 ? '#ffffce' : ($chapter['isactive'] == 100 ? '#fde1e1' : 'inherit') ?>;">Fake <?=$_COMPANY->getAppCustomization()['chapter']['name-short']?> (in group, region = <?= $rrrr; ?>)
                                <td>
                                    <?=$_COMPANY->getAppCustomization()['chapter']['name-short']?> Leader: <?= $_USER->isChapterlead($g->id(),0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
                                </td>
                                <td><?= $_USER->isGroupMember($g->id(),0) ? "<span style='color:green;'>yes</span>": "<span style='color:red;'>no</span>"?> </td>
                                <td><?= $_USER->canManageGroupChapter($g->id(),$rrrr, 0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                <td><?= $_USER->canCreateContentInGroupChapter($g->id(),$rrrr, 0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                <td><?= $_USER->canPublishContentInGroupChapter($g->id(),$rrrr, 0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                <td>-</td>
                                <td><?= $_USER->canManageGrantGroupChapter($g->id(),$rrrr, 0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                <td><?= $_USER->canManageSupportGroupChapter($g->id(),$rrrr, 0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                            </tr>
                            <tr>
                                <td style="background-color: <?= $chapter['isactive'] == 0 ? '#ffffce' : ($chapter['isactive'] == 100 ? '#fde1e1' : 'inherit') ?>;">Fake <?=$_COMPANY->getAppCustomization()['chapter']['name-short']?> (in group, region = 0)
                                <td>
                                    <?=$_COMPANY->getAppCustomization()['chapter']['name-short']?> Leader: <?= $_USER->isChapterlead($g->id(),0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
                                </td>
                                <td><?= $_USER->isGroupMember($g->id(),0) ? "<span style='color:green;'>yes</span>": "<span style='color:red;'>no</span>"?> </td>
                                <td><?= $_USER->canManageGroupChapter($g->id(),0, 0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                <td><?= $_USER->canCreateContentInGroupChapter($g->id(),0, 0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                <td><?= $_USER->canPublishContentInGroupChapter($g->id(),0, 0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                <td>-</td>
                                <td><?= $_USER->canManageGrantGroupChapter($g->id(),0, 0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                <td><?= $_USER->canManageSupportGroupChapter($g->id(),0, 0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                            </tr>
                        </table>
                    <?php } else { ?>
                     - No <?=$_COMPANY->getAppCustomization()['chapter']['name-short-plural']?> -
                    <?php } ?>
                </td>
                <td>
                    <?php $channels = Group::GetChannelList($g->id()); ?>
                    <?php if (isset($channels) && count($channels)) { $rrrr = 0; ?>
                        <table style="font-size: small" summary="This table display the list of channels">
                            <tr>
                                <th scope="col">Channel</th>
                                <th scope="col">Leader</th>
                                <th scope="col">Member</th>
                                <th scope="col">Mgr</th>
                                <th scope="col">Cre</th>
                                <th scope="col">Pub</th>
                                <th scope="col">Bgt</th>
                                <th scope="col">Gr</th>
                                <th scope="col">Su</th>
                            </tr>
                            <?php foreach ($channels as $channel) { ?>
                                <?php
                                $ccc = $_USER->getMyChannelleadRecords($channel['groupid'], $channel['channelid']);
                                if(count($ccc))
                                    $my_channel_lead = array_pop($ccc);
                                else
                                    $my_channel_lead = array();
                                ?>

                                <tr>
                                    <td style="background-color: <?= $channel['isactive'] == 0 ? '#ffffce' : ($channel['isactive'] == 100 ? '#fde1e1' : 'inherit') ?>;">
                                        <?=htmlspecialchars(substr($channel['channelname'],0,20).((strlen($channel['channelname'])>20)?'...':''))?>
                                        <br><span style="font-size: x-small">Status = <?= $channel['isactive'] ?></span>
                                        <hr>
<pre>
<strong>C,[UD/UP],P,M,B,G </strong>: <?= $_USER->canCreateContentInScopeCSV($g->id(),0,$channel['channelid']) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,[<?= $_USER->canUpdateContentInScopeCSV($g->id(),0,$channel['channelid'], 2) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>/<?= $_USER->canUpdateContentInScopeCSV($g->id(),0,$channel['channelid'], 1) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>],<?= $_USER->canPublishContentInScopeCSV($g->id(),0,$channel['channelid']) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,<?= $_USER->canManageContentInScopeCSV($g->id(),0,$channel['channelid']) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,<?= $_USER->canManageBudgetInScope($g->id(),0,$channel['channelid']) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,NA

<?php if ($chapter1) { ?>
Group + <?= $chapter1['chaptername'] ?> + <?= htmlspecialchars($channel['channelname']) ?>

<strong>C,[UD/UP],P,M,B,G </strong>: <?= $_USER->canCreateContentInScopeCSV($g->id(),$chapter1['chapterid'],$channel['channelid']) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,[<?= $_USER->canUpdateContentInScopeCSV($g->id(),$chapter1['chapterid'],$channel['channelid'], 2) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>/<?= $_USER->canUpdateContentInScopeCSV($g->id(),$chapter1['chapterid'],$channel['channelid'], 1) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>],<?= $_USER->canPublishContentInScopeCSV($g->id(),$chapter1['chapterid'],$channel['channelid']) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,<?= $_USER->canManageContentInScopeCSV($g->id(),$chapter1['chapterid'],$channel['channelid']) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,<?= $_USER->canManageBudgetInScope($g->id(),$chapter1['chapterid'],$channel['channelid']) ? "<span style='color:green;'>Y</span>": "<span style='color:red;'>n</span>"; ?>,NA
<?php } ?>
</pre>
                                    </td>
                                    <td>
                                        <?=$_COMPANY->getAppCustomization()['channel']['name-short']?> Leader: <?= $_USER->isChannellead($g->id(),$channel['channelid']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
                                        <form name="assign-channel-lead" action="" type="GET"?>
                                            <input type="hidden" name="zoneid" value="<?=$_COMPANY->encodeId($_ZONE->id())?>">
                                            <input type="hidden" name="groupid" value="<?=$g->id()?>">
                                            <input type="hidden" name="channellead_update" value="<?=$channel['channelid']?>">
                                            <select id="typeid" name="typeid" >
                                                <option value="0">-None-</option>
                                                <?php foreach ($_COMPANY->getGroupLeadtypesOfChannelType() as $l) { ?>
                                                    <option value="<?=$l['typeid']?>" <?= (isset($my_channel_lead['grouplead_typeid']) && $my_channel_lead['grouplead_typeid'] == $l['typeid'])?'selected':''?> >
                                                        <?=htmlspecialchars(substr($l['type'],0,20).((strlen($l['type'])>20)?'...':''))?>
                                                    </option>
                                                <?php }?>
                                            </select>
                                            <input type="submit" value="update" class="prevent-multi-clicks"></input>
                                        </form>
                                    </td>
                                    <td><?= $_USER->isGroupChannelMember($g->id(), $channel['channelid']) ? "<span style='color:green;'>yes</span><a href='?groupmember_update=disable&groupid={$g->id()}&channelid={$channel['channelid']}' style='font-size: x-small;'>&nbsp;...remove</a>" : "<span style='color:red;'>no</span><a href='?groupmember_update=enable&groupid={$g->id()}&channelid={$channel['channelid']}' style='font-size: x-small;'>&nbsp;...add</a>" ?> </td>
                                    <td><?= $_USER->canManageGroupChannel($g->id(), $channel['channelid']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                    <td><?= $_USER->canCreateContentInGroupChannel($g->id(), $channel['channelid']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                    <td><?= $_USER->canPublishContentInGroupChannel($g->id(),$channel['channelid']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                    <td>-</td>
                                    <td><?= $_USER->canManageGrantGroupChannel($g->id(),$channel['channelid']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></td>
                                    <td><?= $_USER->canManageSupportGroupChannel($g->id(),$channel['channelid']) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td style="background-color: <?= $channel['isactive'] == 0 ? '#ffffce' : ($channel['isactive'] == 100 ? '#fde1e1' : 'inherit') ?>;">Fake <?=$_COMPANY->getAppCustomization()['channel']['name-short']?>
                                <td>
                                    <?=$_COMPANY->getAppCustomization()['chapter']['name-short']?> Leader: <?= $_USER->isChannellead($g->id(),0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?>
                                </td>
                                <td><?= $_USER->isGroupMember($g->id(),0) ? "<span style='color:green;'>yes</span>": "<span style='color:red;'>no</span>"?> </td>
                                <td><?= $_USER->canManageGroupChannel($g->id(), 0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                <td><?= $_USER->canCreateContentInGroupChannel($g->id(), 0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                <td><?= $_USER->canPublishContentInGroupChannel($g->id(), 0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?> </td>
                                <td>-</td>
                                <td><?= $_USER->canManageGrantGroupChannel($g->id(),0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></td>
                                <td><?= $_USER->canManageSupportGroupChannel($g->id(),0) ? "<span style='color:green;'>yes</span>" : "<span style='color:red;'>no</span>" ?></td>
                            </tr>
                        </table>
                    <?php } else { ?>
                        - No <?=$_COMPANY->getAppCustomization()['chapter']['name-short-plural']?> -
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>

<script>
    //Chapter
    document.getElementById('enablechapter').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?enableDisableCompanyFeature=1&section=chapter";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=chapter";
        }
    })
    //Channel
    document.getElementById('enablechannel').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?enableDisableCompanyFeature=1&section=channel";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=channel";
        }
    })

    document.getElementById('enablelist').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?enableDisableCompanyFeature=1&section=list";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=list";
        }
    })

    //post
    document.getElementById('enablepost').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?enableDisableCompanyFeature=1&section=post";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=post";
        }
    })
    document.getElementById('enableannouncementapprovals').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enableannouncementapprovals";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enableannouncementapprovals";
        }
    })
    //event
    document.getElementById('enableevent').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?enableDisableCompanyFeature=1&section=event";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=event";
        }
    })
    //budgets
    document.getElementById('enablebudgets').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?enableDisableCompanyFeature=1&section=budgets";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=budgets";
        }
    })
    document.getElementById('enablebudgetrequests').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?enableDisableCompanyFeature=1&section=budgetrequests";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=budgetrequests";
        }
    })
    //newsletters
    document.getElementById('enablenewsletters').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?enableDisableCompanyFeature=1&section=newsletters";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=newsletters";
        }
    })
    document.getElementById('enablenewsletterapprovals').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enablenewsletterapprovals";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enablenewsletterapprovals";
        }
    })
    //surveys
    document.getElementById('enablesurveys').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?enableDisableCompanyFeature=1&section=surveys";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=surveys";
        }
    })
    document.getElementById('enablesurveyapprovals').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enablesurveyapprovals";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enablesurveyapprovals";
        }
    })
    //discussions
    document.getElementById('enablediscussions').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?enableDisableCompanyFeature=1&section=discussions";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=discussions";
        }
    })

    //albums
    document.getElementById('enablealbums').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?enableDisableCompanyFeature=1&section=albums";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=albums";
        }
    })
     //budgets
     document.getElementById('enablebudgets').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?enableDisableCompanyFeature=1&section=budgets";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=budgets";
        }
    })
    document.getElementById('enablebudgetrequests').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?enableDisableCompanyFeature=1&section=budgetrequests";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=budgetrequests";
        }
    })
    //budgets
    document.getElementById('enablerecognition').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?enableDisableCompanyFeature=1&section=recognition";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=recognition";
        }
    })
    //resources
    document.getElementById('enableresources').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=resources";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=resources";
        }
    })

    //disclaimers
    document.getElementById('enabledisclaimers').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=disclaimer_consent";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=disclaimer_consent";
        }
    })

    // helpvideos
    document.getElementById('enablehelpvideos').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enablehelpvideos";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enablehelpvideos";
        }
    })
    // myschedul
    document.getElementById('enablemyschedule').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enablemyschedule";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enablemyschedule";
        }
    })
    
    document.getElementById('enablebooking').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enablebooking";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enablebooking";
        }
    })
    

    //my events
    document.getElementById('enablemyevents').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=my_events";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=my_events";
        }
    })

    //event email review
    document.getElementById('enableeventreview').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enableeventreview";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enableeventreview";
        }
    })

    //my approvals
    document.getElementById('enableeventapprovals').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enableeventapprovals";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enableeventapprovals";
        }
    })
    document.getElementById('enableeventsurveys').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enableeventsurveys";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enableeventsurveys";
        }
    })

    document.getElementById('enableeventvolunteers').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enableeventvolunteers";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enableeventvolunteers";
        }
    })

    document.getElementById('enableeventbudgets').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enableeventbudgets";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enableeventbudgets";
        }
    })

    document.getElementById('enableeventreconciliation').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enableeventreconciliation";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enableeventreconciliation";
        }
    })

    document.getElementById('enablepartnerorganizations').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enablepartnerorganizations";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enablepartnerorganizations";
        }
    })

    document.getElementById('enableeventspeakers').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enableeventspeakers";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enableeventspeakers";
        }
    })

    document.getElementById('enableeventspeakersapprovals').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enableeventspeakersapprovals";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enableeventspeakersapprovals";
        }
    })

    document.getElementById('enabledmoduesettingsineventform').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=enabledmoduesettingsineventform";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=enabledmoduesettingsineventform";
        }
    })

    //teams
    document.getElementById('enableteams').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=teams";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=teams";
        }
    })
    document.getElementById('enableteams_teamevents').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=teams_teamevents";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=teams_teamevents";
        }
    })
    document.getElementById('enableteams_teamevents_list').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=teams_teamevents_list";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=teams_teamevents_list";
        }
    })
    document.getElementById('enableteams_teambuilder').addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            window.location.href = "usrchk?&enableDisableCompanyFeature=1&section=teams_teambuilder";
        } else {
            window.location.href = "usrchk?enableDisableCompanyFeature=0&section=teams_teambuilder";
        }
    })

</script>
</body>
