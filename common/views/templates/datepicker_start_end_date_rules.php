<?php
/* The following interface values can be set in the calling file. */
$start_date_id ??= 'start_date';
$end_date_id ??= 'end_date';
$max_month_diff ??= 12;
$allowStartDateLaterThanNow = false;
$allowEndDateLaterThanNow = false;
$initialStartDateIsEmpty = false;
$initialEndDateIsEmpty = false;
?>
<script>
// JavaScript / jQuery
$(function () {
    const maxMonthsDiff = <?= $max_month_diff ?>;
    const dateFormat = 'yy-mm-dd';
    const allowEndDateLaterThanNow = <?= $allowEndDateLaterThanNow ? 'true' : 'false' ?>;

    function getDate(str) {
        try {
            return $.datepicker.parseDate(dateFormat, str);
        } catch (e) {
            return null;
        }
    }

    // Set initial end date to today
    const today = new Date();
    const initialEndDateStr =
    <?php if($initialEndDateIsEmpty) { ?>
        '';
    <?php } else { ?>
        $.datepicker.formatDate(dateFormat, today);
    <?php } ?>
    $('#<?=$end_date_id?>').val(initialEndDateStr);

    const yesterday = new Date(today).setDate(today.getDate() - 1);

    // Set initial start date to today - 12 months
    const initialStartDate = new Date(today);
    initialStartDate.setMonth(today.getMonth() - maxMonthsDiff);
    initialStartDate.setDate(initialStartDate.getDate() + 1);

    const initialStartDateStr = <?php if($initialStartDateIsEmpty) { ?>
        '';
    <?php } else { ?>
        $.datepicker.formatDate(dateFormat, initialStartDate);
    <?php } ?>
    $('#<?=$start_date_id?>').val(initialStartDateStr);

    // Initialize start date picker
    $('#<?=$start_date_id?>').datepicker({
         prevText: "click for previous months",
         nextText: "click for next months",
         showOtherMonths: true,
         selectOtherMonths: true,
         changeMonth: true,
         changeYear: true,
         dateFormat: dateFormat,
         defaultDate: initialStartDate,
         <?php if (!$allowStartDateLaterThanNow) { ?>
         maxDate: today,
         <?php } ?>
         onSelect: function (selectedDate) {
        const start = getDate(selectedDate);
        if (!start) return;

        const maxEnd = new Date(start);
        maxEnd.setMonth(start.getMonth() + maxMonthsDiff);
        maxEnd.setDate(maxEnd.getDate() - 1);

        $('#<?=$end_date_id?>').datepicker('option', 'minDate', start);
        $('#<?=$end_date_id?>').datepicker('option', 'maxDate',  allowEndDateLaterThanNow ? maxEnd : (maxEnd > today ? today : maxEnd));

        const currentEnd = getDate($('#<?=$end_date_id?>').val());
        if (currentEnd && (currentEnd < start || currentEnd > maxEnd)) {
            $('#<?=$end_date_id?>').val('');
        }
    }
     });

     // Initialize end date picker
     $('#<?=$end_date_id?>').datepicker({
         prevText: "click for previous months",
         nextText: "click for next months",
         showOtherMonths: true,
         selectOtherMonths: true,
         changeMonth: true,
         changeYear: true,
         dateFormat: dateFormat,
         defaultDate: today,
         minDate: initialStartDate,
         maxDate: today,
         onSelect: function (selectedDate) {
        const end = getDate(selectedDate);
        const start = getDate($('#<?=$start_date_id?>').val());

        if (!start) return;

        const maxEnd = new Date(start);
        maxEnd.setMonth(start.getMonth() + maxMonthsDiff);
        maxEnd.setDate(maxEnd.getDate() - 1);

        if (end < start || end > maxEnd) {
            alert("End date must be after start date and within " + maxMonthsDiff + " months.");
            $('#<?=$end_date_id?>').val('');
        }
    }
     });
 });

</script>