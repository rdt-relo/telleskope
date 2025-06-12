<?php
  if ($page !== 1) {
    require __DIR__ . '/home/feed_rows.template.php';
    return;
  }

  require __DIR__ . '/header_html.php';
  require __DIR__ . '/home_html.php';
?>

<script>
  window.tskp ||= {};
  window.tskp.pagination ||= {};
  window.tskp.pagination.url = 'search';
  window.tskp.pagination.payload = {
    q: (new URLSearchParams(window.location.search)).get('q'),
  }

  $(function () {
    var url = new URL(window.location.href);
    if (!url.searchParams.get('q')) {
      $('#ajaxreplace').hide();
    }
  });


  $("#home-h").removeClass("active-1").addClass('home_nav');
  $("#home-mh").removeClass("active-1").addClass('home_nav');
  $("#home-c").removeClass("active-1").addClass('home_nav');
  $("#home-a").removeClass("active-1").addClass('home_nav');
  $("#home-s-icon").removeClass('home_nav');

</script>

<?php
require __DIR__ . '/footer_html.php';
?>
