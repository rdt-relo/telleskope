<?php
session_name('super');
session_start();
require_once __DIR__.'/../include/Company.php';
require __DIR__ . "/Authenticator.php";
require_once __DIR__ . '/../include/SuperAdminFunctions.php';

$db	= new Hems();
$_SUPER_ADMIN = new SuperAdminFunctions();

if (isset($_POST["enable_google_auth"]) && !empty($_SESSION['auth_secret']) && !empty($_SESSION['verifyid'])) {
    $auth_secret = $_SESSION['auth_secret'];
    $_SUPER_ADMIN->super_update("update admin set google_auth_code='{$auth_secret}' where superid={$_SESSION['verifyid']}");
    $_SESSION['google_auth_code'] = $_SESSION['auth_secret'];
    unset($_SESSION['auth_secret']);
    header("location:google_auth?2fa_enabled_successfully");
    exit;
} elseif (isset($_POST["verify_google_auth"])) {
    $Authenticator = new Authenticator();
    $checkResult = $Authenticator->verifyCode($_SESSION['google_auth_code'], $_POST['code'], 2);    // 2 = 2*30sec clock tolerance

    if (!$checkResult) {
        $_SESSION['google_auth_attempts']= $_SESSION['google_auth_attempts']+1;
        if ($_SESSION['google_auth_attempts'] < 5) {
            sleep($_SESSION['google_auth_attempts']);
            header("location:google_auth?2fa_verification_failed");
            exit;
        } else {
            header("Location:logout.php");
            exit;
        }
    } else {
        $_SESSION['superid'] = $_SESSION['verifyid'];
        $_SESSION['manage_companyids'] = $_SESSION['verify_manage_companyids'];
        $_SESSION['manage_super'] = $_SESSION['verify_manage_companyids'] === '-1' ? true : false;
        $_SESSION['manage_accounts'] = ($_SESSION['verify_manage_companyids'] === '-1') ? true : false;
        if (!$_SESSION['manage_accounts']) { // See if there is a valid companyid
            $cids = explode(',',$_SESSION['verify_manage_companyids']);
            foreach ($cids as $k => $v) {
                if ($v > 0) {
                    $_SESSION['manage_accounts'] = true;
                }
            }
        }
        unset($_SESSION['google_auth_attempts']);
        unset($_SESSION['verifyid']);
        unset($_SESSION['verify_manage_companyids']);
        header("Location:index.php");
        exit;
    }

} else {
    $Authenticator = new Authenticator();
    $siteusernamestr = $_SESSION['email'] . '@' . $_SERVER['HTTP_HOST'];
    if (empty($_SESSION['google_auth_code'])) {
        $_SESSION['auth_secret'] = $Authenticator->generateRandomSecret();
        $qrCodeUrl = $Authenticator->getQR($siteusernamestr, $_SESSION['auth_secret']);
    }
?>
    <html>
    <body>
    <script src="../vendor/js/jquery-3.5.1/dist/jquery.min.js"></script>
    <script type="text/javascript" src="../vendor/js/jquery-qrcode-master/jquery.qrcode.min.js"></script>
    <form class="form-horizontal" role="form" method="post" enctype="multipart/form-data" action="">

        <?php if (empty($_SESSION['google_auth_code'])) { ?>
            <p>This application requires 2FA and it supports only Google Authenticator based 2FA.
                You can enable 2FA by scanning the barcode below in Google Authenticator application on your Mobile Phone.
                After successfully adding this application click <strong>Enable Google Authenticator 2FA</strong> button below
            </p>
            <p>Setting up Google Authenticator for <br><br><i><?=$siteusernamestr?></i></p>
            <div id="qrcode"></div>
            <br>
            <button type="submit" style="background-color: orange" name="enable_google_auth">
                Enable Google Authenticator 2FA
            </button>
        <?php } else { ?>
            <?php if (isset($_GET['2fa_enabled_successfully'])) { ?>
                <p style="background-color: green; padding:10px;">2FA Enabled Succesfully</p>
            <?php } elseif (isset($_GET['2fa_verification_failed'])) { ?>
                <p style="background-color: red; padding:10px;">Verification Failed = <?=$_SESSION['google_auth_attempts']?> times</p>
            <?php } ?>
            <br>
            <p>Enter Google Authenticator 2FA token for <br><br><i><?=$siteusernamestr?></i></p>
            <br>
            <input class="form-control" type="text" name="code"  placeholder="Verify Code">

            <button type="submit" style="background-color: orange" name="verify_google_auth">
                Submit
            </button>
        <?php } ?>
    </form>
    </body>

    <?php if ($qrCodeUrl) { ?>
    <script>
        alert('<?=$qrCodeUrl?>');
        $('#qrcode').qrcode("<?= $qrCodeUrl; ?>");
    </script>
    <?php } ?>

    </html>
    <?php
}
?>