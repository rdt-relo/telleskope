<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Teams SSO Landing</title>
  <!-- MS Teams JS -->
  <script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/MicrosoftTeams.min.js"></script>
</head>
<body>
  <div id="status">Authenticating with Microsoft Teams...</div>

  <script>
   microsoftTeams.app.initialize().then(() => {
  microsoftTeams.authentication.getAuthToken({
    successCallback: (token) => {
      // Redirect to backend endpoint with token as POST payload
      const form = document.createElement("form");
      form.method = "POST";
      form.action = "/1/msteam/teams_sso_token_exchange.php";

      const input = document.createElement("input");
      input.type = "hidden";
      input.name = "token";
      input.value = token;
      form.appendChild(input);

      document.body.appendChild(form);
      form.submit();
    },
    failureCallback: (err) => {
      console.error("getAuthToken error:", err);
    }
  });
});
  </script>
</body>
</html>
