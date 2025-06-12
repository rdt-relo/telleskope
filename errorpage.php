<?php
$code = (int)$_GET['code'];
$errorCode = '500';
$errorMessage = 'Unknown';
$validErrors = array(
				400 => 'Bad Request (Missing or malformed parameters)',
				401 => 'Unauthorized (Please Sign in)',
				403 => 'Forbidden (Access Denied)',
				404 => 'Not found',
				500 =>'Internal server error. Please try again.',
                );
if (in_array($code,array_keys($validErrors))) {
    $errorMessage = $validErrors[$code];
    $errorCode = $code;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $errorCode ?> Error</title>
</head>
<body>

</body>
</html>

<style>
    * {
        -webkit-box-sizing: border-box;
        box-sizing: border-box;
    }

    body {
        padding: 0;
        margin: 0;
    }

    .notfound {
        position: absolute;
        left: 50%;
        top: 50%;
        -webkit-transform: translate(-50%, -50%);
        -ms-transform: translate(-50%, -50%);
        transform: translate(-50%, -50%);
        max-width: 520px;
        width: 100%;
        line-height: 1.4;
        text-align: center;
    }

    .notfound .notfound-404 {
        position: relative;
        height: 240px;
    }

    .notfound .notfound-404 h1 {
        position: absolute;
        left: 50%;
        top: 50%;
        -webkit-transform: translate(-50%, -50%);
        -ms-transform: translate(-50%, -50%);
        transform: translate(-50%, -50%);
        font-size: 252px;
        font-weight: 900;
        margin: 0px;
        color: #640000;
        text-transform: uppercase;
        letter-spacing: -10px;
        margin-left: -20px;
    }

    .notfound .notfound-404 h1>span {
        text-shadow: -8px 0px 0px #fff;
    }

    .notfound h2 {
        font-size: 20px;
        font-weight: 400;
        text-transform: uppercase;
        color: #000;
        margin-top: 0px;
        margin-bottom: 25px;
    }

    @media only screen and (max-width: 767px) {
        .notfound .notfound-404 {
            height: 200px;
        }
        .notfound .notfound-404 h1 {
            font-size: 200px;
        }
    }

    @media only screen and (max-width: 480px) {
        .notfound .notfound-404 {
            height: 162px;
        }
        .notfound .notfound-404 h1 {
            font-size: 162px;
            height: 150px;
            line-height: 162px;
        }
        .notfound h2 {
            font-size: 16px;
        }
    }


</style>
<body>
    <div class="notfound">
        <div class="notfound-404">
            <h1><span><?=$errorCode?></span></h1>
        </div>
        <h2><?=$errorMessage?></h2>
        <?php if ($custom_message ?? '') { ?>
          <p style="font-size: 1.2em;">
            <?= $custom_message ?? '' ?>
          </p>
        <?php } ?>
    </div>

</body>
</html>
