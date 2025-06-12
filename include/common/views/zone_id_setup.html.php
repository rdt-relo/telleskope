<?php
// Note: This file inclusion has been disabled. We do not need to set zoneid in the URL parameters anymore.
// In the future, we will use this code for setting the tabid . A unique tabid should be created whenever the user opens
// teleskope urls in a new tab and tabid should be passed back as URL attribute. The purpose of tabid is server side
// logging to identify issues related to multiple tabs.

return;

//
//
//
if (!$_ZONE?->id()) {
  return;
}
?>
<script>
  (function () {
    function init() {
      sessionStorage.setItem('zoneid', '<?= $_COMPANY->encodeId($_ZONE->id()) ?>')
      addZoneIdToAllPageUrls()

      $(document).on('ajaxSend', function(event, jqxhr, settings) {
        settings.url = addZoneIdToUrl(settings.url)
      })

      $(document).on('ajaxComplete', function() {
        addZoneIdToAllPageUrls()
      })

      $(function() {
        addZoneIdToAllPageUrls()
      })
    }

    function addZoneIdToUrl(input) {
      if (!input) {
        return input
      }

      var url = new URL(input, location.href)

      if (url.hostname !== window.location.hostname) {
        return input
      }

      if (url.searchParams.get('zoneid')) {
        return input
      }

      url.searchParams.set('zoneid', sessionStorage.getItem('zoneid'))
      return url.href
    }

    function addZoneIdToAllPageUrls() {
      $('a').each(function () {
        var $this = $(this)
        $this.attr(
          'href',
          addZoneIdToUrl($this.attr('href'))
        );

        $this.attr('data-href') && $this.attr(
              'data-href',
              addZoneIdToUrl($this.attr('data-href'))
        );
      })

      $('form').each(function () {
        var $this = $(this)
        $this.attr(
          'action',
          addZoneIdToUrl($this.attr('action'))
        )
      })
    }

    init()
  })()
</script>
