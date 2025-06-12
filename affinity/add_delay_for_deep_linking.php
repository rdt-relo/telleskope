
<?php
$appendHash = "";
if (isset($_GET['hash'])){ // Hash has logic for redirecting user to right context page
    $appendHash = "#".$_GET['hash'];
}
if ((strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "mobile") !== false) && !isset($_GET['stage2'])) {
    $redirect_url = $_SERVER['REQUEST_URI'].'&stage2=1'.$appendHash;
    echo <<<EOMEOM
    <html>
        <head>
            <meta http-equiv="refresh" content="0.01;url={$redirect_url}" />
        </head>
        <body>
            <p>Loading ...</p>
        </body>
    </html>
EOMEOM;
    exit();
}