<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title><?= gettext("Teleskope - System Message");?></title>
    <style>
        body {
            font-family: "Open Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", Helvetica, Arial, sans-serif;
        }
    </style>
</head>

<body style="background: url('<?= empty($_COMPANY) ? '../image/login_background.png' : $_COMPANY->val('loginscreen_background')?>') no-repeat; background-size: cover;">

<script src="../vendor/js/sweetalert2/dist/sweetalert2.all.min.js"></script>
<!-- Optional: include a polyfill for ES6 Promises for IE11 -->
<script src="../vendor/js/promise-polyfill/dist/polyfill.min.js"></script>

<script>
    Swal.fire({
        icon : '<?=$success ? "success" : "error";?>',
        title: '<?=$success ? "Success" : "Error"; ?>',
        html: '<?=$message;?>',
        showConfirmButton: true,
        confirmButtonText: 'Go To Event'
    }).then(function(result) {
        window.location.href = "<?=$goToLink?>";

    });

</script>
</body>
</html>
