<?php
session_start();
$h = explode('.',gethostname())[0];
$ip =  $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
$dt = gmdate('Y-m-d H:i:s');
$uid = $_SESSION['userid'] ?? $_SESSION['adminid'] ?? '-';
$cid = $_SESSION['companyid'] ?? '-';

echo <<< ENDOFSTR
<div style="background-color: lightyellow; width: 250px; padding: 10px 20px;border: 1px #000000 solid;">
<pre>
EC2  : $h
IP   : $ip
UTC  : $dt
UID  : $uid
CID  : $cid
</pre>
</div>
ENDOFSTR;

