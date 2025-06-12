<?php

/**
 * Utity function for date
 */
class Date
{
    public static function IsDateBetween(string $date_to_check, string $start_date, string $end_date): bool
    {
        $date_to_check = new DateTime($date_to_check);
        $start_date = new DateTime($start_date);
        $end_date = new DateTime($end_date);
    
        return $date_to_check >= $start_date && $date_to_check <= $end_date;
    }
    
    public static function GetDatesFromRange(string $start_date, string $end_date, string $date_format = 'Y-m-d'): array
    {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end = $end->modify('+1 day'); // To include the end date in the range

        $interval = new DateInterval('P1D'); // Period of 1 day
        $date_range = new DatePeriod($start, $interval, $end);
        $dates_list = array();
        foreach ($date_range as $date) {
            $dates_list[] =  $date->format($date_format);
        }
        return $dates_list;
    }

    public static function GetTimeDifference(string $datetime1, string $datetime2): array // 24 hrs format
    {
        // Convert time strings to DateTime objects
        $time1_dt = DateTime::createFromFormat('Y-m-d H:i', $datetime1);
        $time2_dt = DateTime::createFromFormat('Y-m-d H:i', $datetime2);
        // Calculate the difference
        $interval = $time1_dt->diff($time2_dt);

        // Get the difference in hours and minutes
        $hours = $interval->format('%h');
        $minutes = $interval->format('%i');
        return array ($hours, $minutes);
    }

    public static function ConvertDatetimeTimezone(string $datetime, string $from_timezone, string $to_timezone): DateTime
    {
        $dateObject = new DateTime($datetime, new DateTimeZone($from_timezone));
        // Convert the timezone to $desiredTimeZone
        $dateObject->setTimezone(new DateTimeZone($to_timezone));
        
        return $dateObject;
    }


    public static function IncrementDatetime (string $datetime, string $datetime_tz, int $hours_to_add, int $minutes_to_add): DateTime
    {
        $desiredDatetime = new DateTime($datetime, new DateTimeZone($datetime_tz));
        // Create a new DateInterval object
        $interval = new DateInterval('PT' . $hours_to_add . 'H' . $minutes_to_add . 'M');
        // Add the interval to the desired datetime and return
        return $desiredDatetime->add($interval);
    }

    public static function ConvertMinutesToHoursMinutes($minutes): array
    {
        // Calculate hours
        $hours = floor($minutes / 60);
        // Calculate remaining minutes
        $remainingMinutes = $minutes % 60;
        return [$hours, $remainingMinutes];
    }

    public static function IsDateTimeInArray(DateTime $needle_date_time, array $haystack_datetime_array): bool
    {
        foreach ($haystack_datetime_array as $dt) {
            if ($dt == $needle_date_time) {
                return true;
            }
        }
        return false;
    }

    public static function HasDateConflict(string $start_date, string $end_date, array $existing_dates): bool
    {
        // Ensure all dates are valid timestamps
        $start_date = strtotime($start_date);
        $end_date = strtotime($end_date);
      
        foreach ($existing_dates as $existing) {
            $existingStart = strtotime($existing['start']);
            $existingEnd = strtotime($existing['end']);
        
            // Check for any overlap scenario
            if (($start_date >= $existingStart && $start_date <= $existingEnd) ||
                ($end_date >= $existingStart && $end_date <= $existingEnd) ||
                ($existingStart >= $start_date && $existingStart <= $end_date) ||
                ($existingEnd >= $start_date && $existingEnd <= $end_date)) {
                return true; // Conflict found
            }
        }
      
        return false; // No conflict
      }

    /**
     * https://sarathlal.com/split-time-into-time-slots-php/
     */
    public static function PrepareTimeSlots($starttime, $endtime, $duration): array
    {
        $time_slots = [];
        $start_time = strtotime($starttime);
        $end_time = strtotime($endtime);

        $add_mins = $duration * 60;

        while ($start_time < $end_time)
        {
            $slot_start_time = date('H:i', $start_time);
            $slot_end_time = min($start_time + $add_mins, $end_time);

            $time_slots[] = [
                'slot_start_time' => date('H:i', $start_time),
                'slot_end_time' => date('H:i', $slot_end_time)
            ];

            $start_time = $slot_end_time;
        }

        return $time_slots;
    }
    
    /**
     * Returns absolute differnce between two datetimes
     * @param  string $dateTime1
     * @param  string $dateTime2
     * @param  string $differenceIn s = Seconds, m  = minutes , h=hours
     * @return int
     */
    public static function GetDateDifference(string $dateTime1, string $dateTime2, string $diffIn='h') 
    {
        $ts1 = strtotime($dateTime1);
        $ts2 = strtotime($dateTime2);

        $diffInSeconds = abs($ts2 - $ts1);

        switch (strtolower($diffIn)) {
            case 's':
                return $diffInSeconds;
            case 'm':
                return $diffInSeconds / 60;
            case 'h':
            default:
                return $diffInSeconds / 3600;
        }
    }
}
