<?php
  unset($_ZONE);
  if (!Env::IsLocalEnv()) {
    exit(0);
  }
?>

<html>
  <head>
    <title>Old URL Deprecated Screen</title>
  </head>
  <body>
    <h3>Old URLs are deprecated</h3>
    <p>
      This is a legacy URL and has been deprecated.
      <br><br>
      <a href="<?= $_GET['rurl_new'] ?>">Go to new URL (Recommended)</a>
      <br><br>
      <a href="<?= $_GET['rurl_old'] ?>">Continue with old URL</a>
    </p>
  </body>
</html>
