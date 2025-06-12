<?php
exit();
// This file is not used anymore
//
//require_once __DIR__ . '/Company.php';
//date_default_timezone_set("UTC");
//
///* connect to gmail */
//$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
//
//if (empty(Config::Get('HELLO_EMAIL_PASSWORD'))) exit;
//
//$username = 'hello@teleskope.io';
//$password = Config::Get('HELLO_EMAIL_PASSWORD');
//
///* try to connect */
//$inbox = imap_open($hostname, $username, $password) or die('Cannot connect to Gmail: ' . imap_last_error());
//
///* grab emails */
//$emails = imap_search($inbox, 'ALL');
//
//if ($emails) {
//
//    /* put the newest emails on top */
//    rsort($emails);
//
//    foreach ($emails as $email_number) {
//        $header = imap_headerinfo($inbox, $email_number);
//        $body = imap_fetchstructure($inbox, $email_number);
//        $from = $header->from[0]->mailbox . "@" . $header->from[0]->host;
//        $to = $header->to[0]->mailbox . "@" . $header->to[0]->host;
//        $subject = mb_decode_mimeheader($header->subject);
//        $udate = $header->udate;
//        $processed = 0;
//
//        if ($to !== "hello@teleskope.io")
//            continue;
//
//        if (stripos($subject, "Automatic reply:") === 0
//            || stripos($subject, "Automatic_reply:") === 0
//            || stripos($subject, "Out of Office") === 0 ){
//            Logger::Log("EmailBot: Deleting (Auto-Reply) {$subject} [{$from} | {$udate}]");
//            imap_delete($inbox, $email_number);
//        } else {
//            if (!$body->parts)  // simple
//                $processed = processPartRecursively($inbox, $from, $udate, $subject, $email_number, $body, 0);  // pass 0 as part-number
//            else {  // multipart: cycle through each part
//                foreach ($body->parts as $partno0 => $p) {
//                    if (($processed = processPartRecursively($inbox, $from, $udate, $subject, $email_number, $p, $partno0 + 1)))
//                        break;  // Process no further once we get true for processPartsRecursively, means all done
//                }
//            }
//            if ($processed == 1) {
//                Logger::Log("EmailBot: Deleting (Processed) {$subject} [{$from} | {$udate}]");
//                imap_delete($inbox, $email_number);
//            } elseif ($processed == -1 || $processed == -2 ) {
//                Logger::Log("EmailBot: Deleting (Not Found Err={$processed}) {$subject} [{$from} | {$udate}]");
//                imap_delete($inbox, $email_number);
//            } elseif ($processed == -3 ) {
//                Logger::Log("EmailBot: Deleting (Denied={$processed}) {$subject} [{$from} | {$udate}]");
//                imap_delete($inbox, $email_number);
//            } else {
//                Logger::Log("EmailBot: Moving (Unprocessed) {$subject} [{$from} | {$udate}]");
//                imap_mail_move($inbox, $email_number, 'Unprocessed');
//            }
//        }
//    }
//}
//
///* close the connection */
//imap_expunge($inbox);
//imap_close($inbox);
//
//// Recursive call to process parts until a calendar response is found. Returns >0 when calendar response is processed, <0 on error
//
//function processPartRecursively($mbox, $from, $udate, $subject, $mid, $p, $partno): int
//{
//    // $partno = '0','0.1', '0.2', '0.2.1', '0.2.1.3', etc for multipart, 0 if simple
//    //global $htmlmsg,$plainmsg,$charset,$attachments;
//    $rsvp = '';
//
//    // DECODE DATA
//    $data = ($partno) ?
//        imap_fetchbody($mbox, $mid, $partno) :  // multipart
//        imap_body($mbox, $mid);  // simple
//
//    // Any part may be encoded, even plain text messages, so check everything.
//    if ($p->encoding == 3) {
//        $data = base64_decode($data);
//    } elseif ($p->encoding == 4) {
//        $data = quoted_printable_decode($data);
//    }
//
//    // PARAMETERS
//    // get all parameters, like charset, filenames of attachments, etc.
//
//    $params = array();
//    if (@$p->parameters)
//        foreach ($p->parameters as $x)
//            $params[strtolower($x->attribute)] = $x->value;
//    if (@$p->dparameters)
//        foreach ($p->dparameters as $x)
//            $params[strtolower($x->attribute)] = $x->value;
//
//    if ($p->subtype == "CALENDAR"
//        && $params['method'] === 'REPLY') {
//
//        preg_match("/.*PARTSTAT=(\w+);.*/i", $data, $r);
//        preg_match("/.*[\r|\n]UID:([^\r|\n]+).*/i", $data, $u);
//        $rsvp = strtoupper($r[1]);
//        $uid = $u[1];
//
//        $rsvp_status = Event::RSVP_TYPE['RSVP_NO'];
//        if ($rsvp === 'TENTATIVE') {
//            $rsvp_status = Event::RSVP_TYPE['RSVP_MAYBE'];
//        } elseif ($rsvp === 'ACCEPTED') {
//            $rsvp_status = Event::RSVP_TYPE['RSVP_YES'];
//        }
//
//        return Event::ProcessRsvpForBot('EmailBot', $from, $udate, $subject, $uid, $rsvp_status);
//    }
//
//
//    // ATTACHMENT
//    // Any part with a filename is an attachment,
//    // so an attached text file (type 0) is not mistaken as the message.
//
//    //if ($params['filename'] || $params['name']) {
//    //    // filename may be given as 'Filename' or 'Name' or both
//    //    $filename = ($params['filename'])? $params['filename'] : $params['name'];
//    //    // filename may be encoded, so see imap_mime_header_decode()
//    //    $attachments[$filename] = $data;  // this is a problem if two files have same name
//    //}
//
//    // TEXT
//
//    //if ($p->type==0 && $data) {
//    //    // Messages may be split in different parts because of inline attachments,
//    //    // so append parts together with blank row.
//    //    if (strtolower($p->subtype)=='plain')
//    //        $plainmsg. = trim($data) ."\n\n";
//    //    else
//    //        $htmlmsg. = $data ."<br><br>";
//    //    $charset = $params['charset'];  // assume all parts are same charset
//    //}
//
//    // EMBEDDED MESSAGE
//    // Many bounce notifications embed the original message as type 2,
//    // but AOL uses type 1 (multipart), which is not handled here.
//    // There are no PHP functions to parse embedded messages,
//    // so this just appends the raw source to the main message.
//
//    //elseif ($p->type==2 && $data) {
//    //    $plainmsg. = $data."\n\n";
//    //}
//
//    // SUBPART RECURSION
//    if (@$p->parts) {
//        foreach ($p->parts as $partno0 => $p2) {
//            $retVal = processPartRecursively($mbox, $from, $udate, $subject, $mid, $p2, $partno . '.' . ($partno0 + 1));  // 1.2, 1.2.1, etc.
//            if ($retVal)
//                return $retVal; // Exit out of the foreach & recursion if part was processed successfully, i.e. calendar part was found.
//        }
//    }
//
//    // Could not find any matches so far, last try using the subject
//
//    $rsvp_status = Event::RSVP_TYPE['RSVP_NO'];
//    if (stripos($subject, 'Accepted:') === 0) {
//        $rsvp_status = Event::RSVP_TYPE['RSVP_YES'];
//        $subject = trim(substr($subject, 9));
//    } elseif (stripos($subject, 'Tentative:') === 0) {
//        $rsvp_status = Event::RSVP_TYPE['RSVP_MAYBE'];
//        $subject = trim(substr($subject, 10));
//    } elseif (stripos($subject, 'Declined:') === 0) {
//        $rsvp_status = Event::RSVP_TYPE['RSVP_NO'];
//        $subject = trim(substr($subject, 9));
//    }
//
//    while (stripos($subject, 'Invitation:') === 0 || stripos($subject, 'RE:') === 0)
//        $subject = trim(substr($subject, 3));
//
//    if (stripos($subject, 'Updated:') === 0)
//        $subject = trim(substr($subject, 8));
//
//    if ($rsvp !== '') {
//        // Lets try with the subject
//        $uid = '';
//        return Event::ProcessRsvpForBot('EmailBot', $from, $udate, $subject, $uid, $rsvp_status);
//    }
//
//    return 0;
//}
