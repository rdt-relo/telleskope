<?php
define('AJAX_CALL',1); // Define AJAX call for error handling
require_once __DIR__.'/../affinity/head.php';//Common head.php includes destroying Session for timezone so defining INDEX_PAGE. Need head for logging and protecting

require_once __DIR__ . '/../include/dblog/EmailLog.php';

global $_COMPANY;
global $_USER;
global $_ZONE;

/* @var Company $_COMPANY */
/* @var User $_USER */

###### All Ajax Calls ##########

if (isset($_GET['getEmailLogstatistics'])  && $_SERVER['REQUEST_METHOD'] === 'GET'){
  
    if (
        ($groupid = $_COMPANY->decodeId($_GET['groupid']))<0 ||
        ($group = Group::GetGroup($groupid)) === null ||
        ($id = $_COMPANY->decodeId($_GET['id']))<0 ||
        ($section = $_COMPANY->decodeId($_GET['section']))<0
        ) {
            header(HTTP_BAD_REQUEST);
            exit();
    }

    $allowed = false;
    $urlTrackingEnabled = false;
    if ($section === 1) { // Announcement
        $post = Post::GetPost($id);
        $pageTitle = sprintf(gettext("Email Statistics for %s"), $post->val('title'));
        $urlTrackingEnabled = $_COMPANY->getAppCustomization()['post']['email_tracking']['track_urls'] ?: false;
        // Authorization Check
        $allowed = $_USER->canPublishOrManageContentInScopeCSV($post->val('groupid'), $post->val('chapterid'),$post->val('channelid'));
    } elseif ($section === 2) { // Event
        $event = Event::GetEvent($id);
        $pageTitle = sprintf(gettext("Email Statistics for %s"), $event->val('eventtitle'));
        $urlTrackingEnabled = $_COMPANY->getAppCustomization()['event']['email_tracking']['track_urls'] ?: false;
        $allowed = $event->loggedinUserCanManageEvent() || $event->loggedinUserCanPublishEvent();
    } elseif ($section === 3) { // Newsletter
        $newsletter = Newsletter::GetNewsletter($id);
        $pageTitle = sprintf(gettext("Email Statistics for %s"), $newsletter->val('newslettername'));
        $urlTrackingEnabled = $_COMPANY->getAppCustomization()['newsletters']['email_tracking']['track_urls'] ?: false;
        $allowed = $_USER->canPublishOrManageContentInScopeCSV($newsletter->val('groupid'), $newsletter->val('chapterid'), $newsletter->val('channelid'));
    } elseif ($section === 4) { // Message
        $message = Message::GetMessage($id);
        $pageTitle = sprintf(gettext("Email Statistics for %s"), $message->val('subject'));
        $urlTrackingEnabled = $_COMPANY->getAppCustomization()['messaging']['email_tracking']['track_urls'] ?: false;
        if ($_USER->isAdmin()) {
          $allowed = true;
        } else {
          $allowed = $_USER->canPublishOrManageContentInScopeCSV($message->val('groupids'), $message->val('chapterids'), $message->val('channelids'));
        }
    }

    if (!$allowed) {
        header(HTTP_FORBIDDEN);
        exit();
    }

    $domain = $_COMPANY->getAppDomain($_ZONE->val('app_type'));
    $emailLogs = Emaillog::GetAllEmailLogsSummary($domain,$section,$id);
    include(__DIR__ . "/views/email_log/get_email_log_statistics.template.php");
}

else {
    Logger::Log("Nothing to do ...");
    header("HTTP/1.1 501 Not Implemented (Bad Request)");
    exit;
}
