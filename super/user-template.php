<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Responsive Form</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- Bootstrap 5 CSS -->
<link href="../css/custom.css" rel="stylesheet" />
  <style>
    body {
      background-color: #f8f9fa;
    }
    .container-limited {
      width: 800px;
      margin: 0 auto;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .required:after {
      content:" *";
      color: red;
    }
  </style>
</head>
<body>

<div class="container-limited mt-5">
  <h3 class="mb-4">User Information Form</h3>
  <form id="userForm" method="POST" novalidate>

    <!-- Text Input -->
    <div class="mb-3">
      <label for="name" class="form-label required">Full Name</label>
      <input type="text" class="form-control" id="name" name="name" required>
      <div class="invalid-feedback">Please enter your name.</div>
    </div>

    <!-- Email -->
    <div class="mb-3">
      <label for="email" class="form-label required">Email</label>
      <input type="email" class="form-control" id="email" name="email" required>
      <div class="invalid-feedback">Please enter a valid email.</div>
    </div>

    <!-- Textarea -->
    <div class="mb-3">
      <label for="message" class="form-label">Message</label>
      <textarea class="form-control" id="message" name="message" rows="4"></textarea>
    </div>

    <!-- Number -->
    <div class="mb-3">
      <label for="age" class="form-label required">Age</label>
      <input type="number" class="form-control" id="age" name="age" required min="1" max="120">
      <div class="invalid-feedback">Enter a valid age.</div>
    </div>

    <!-- Date -->
    <div class="mb-3">
      <label for="dob" class="form-label required">Date of Birth</label>
      <input type="date" class="form-control" id="dob" name="dob" required>
      <div class="invalid-feedback">Please select your birth date.</div>
    </div>

    <!-- Time -->
    <div class="mb-3">
      <label for="time" class="form-label">Preferred Contact Time</label>
      <input type="time" class="form-control" id="time" name="time">
    </div>

    <!-- Dropdown -->
    <div class="mb-3">
      <label for="country" class="form-label required">Country</label>
      <select class="form-select" id="country" name="country" required>
        <option value="">Choose...</option>
        <option value="us">United States</option>
        <option value="uk">United Kingdom</option>
        <option value="in">India</option>
      </select>
      <div class="invalid-feedback">Please select your country.</div>
    </div>

    <!-- Radio Buttons -->
    <div class="mb-3">
      <label class="form-label required">Gender</label><br>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="gender" id="male" value="male" required>
        <label class="form-check-label" for="male">Male</label>
      </div>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="gender" id="female" value="female" required>
        <label class="form-check-label" for="female">Female</label>
      </div>
      <div class="invalid-feedback d-block">Please select a gender.</div>
    </div>

    <!-- Submit -->
    <button type="submit" class="btn btn-primary">Submit</button>
  </form>
</div>

<!-- jQuery and Bootstrap JS -->
<script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/jquery-3.5.1/dist/jquery.min.js"></script>
<script src="<?=TELESKOPE_CDN_STATIC?>/vendor/js/bootstrap-3.4.1/dist/js/bootstrap.min.js"></script>

<!-- Validation Script -->
<script>
$(function () {
  'use strict';

  // Bootstrap validation
  var form = $('#userForm');
  form.on('submit', function (event) {
    if (!this.checkValidity()) {
      event.preventDefault();
      event.stopPropagation();
    }
    form.addClass('was-validated');
  });
});
</script>

<script>
$(function () {
  const form = $('#userForm');
  const submitBtn = $('#userForm button[type="submit"]');

  // Initialize Select2
  $('#categories').select2({ placeholder: "Select expertise", width: '100%' });

  // Validate form and handle submission
  form.on('submit', function (event) {
    if (!this.checkValidity()) {
      event.preventDefault();
      event.stopPropagation();
      $(this).addClass('was-validated');
      return;
    }

    event.preventDefault(); // Prevent actual submission (for demo)

    // Disable and show spinner
    submitBtn.prop('disabled', true);
    submitBtn.html(`
      <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
      Submitting...
    `);

    // Simulate a network delay
    setTimeout(() => {
      // Re-enable button and reset text
      submitBtn.prop('disabled', false);
      submitBtn.html('Submit');
      alert('Form submitted successfully (demo)');
    }, 2000);
  });

  // Update progress bar
  $('input, select, textarea').on('change input', function () {
    const total = $('input, select, textarea').length;
    const filled = $('input, select, textarea').filter(function () {
      return $(this).val() !== '';
    }).length;
    const percent = Math.round((filled / total) * 100);
    $('#progressBar').css('width', percent + '%').text(percent + '%').attr('aria-valuenow', percent);
  });

  // On/off switch ARIA update
  $('#notifications').on('change', function () {
    $(this).attr('aria-checked', this.checked);
  });
});
</script>
</body>
</html>