<?php
define ("INDEX_PAGE",1);
require_once __DIR__.'/head.php';

session_unset();
session_destroy();
header("location:index.php");
?>
