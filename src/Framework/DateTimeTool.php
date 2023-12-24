<?php

namespace Energy;

class DateTimeTool
{

    /**
     * @var string Date format
     */
    const DATE_FORMAT = 'Ymd';

    /**
     * @var string Time format
     */
    const TIME_FORMAT = 'His';

    /**
     * @var string DateTime format
     */
    const DATETIME_FORMAT = 'YmdHis';


    /**
     * Сonvert the date and time object into a timestamp
     * @param array DateTime object
     */
    public static function convertToTimestamp(array $dateTimeObject): int
    {
        $timestamp = 0;
        if ($dateTimeObject && !empty($dateTimeObject['normalized']))
            $timestamp = strtotime($dateTimeObject['normalized']);

        return $timestamp;
    }


    /**
     * Normalize the date according to the format (YmdHis)
     * @param int|string Year
     * @param int|string Month
     * @param int|string Day
     * @param int|string Hour
     * @param int|string Minute
     * @param int|string Second
     */

    public static function getNormalizedDateTime($year, $month = 0, $day = 0, $hour = 0, $minute = 0, $second = 0): array
    {

        $dateTime = [];

        if ($dateObject = self::getNormalizedDate($year, $month, $day)) {

            $timeObject = self::getNormalizedTime($hour, $minute, $second);
            $normalized =  $dateObject['normalized'] . $timeObject['normalized'];
            $dateTime = array_merge($dateObject, $timeObject);
            $dateTime['normalized'] = $normalized;
        }

        return $dateTime;
    }


    /**
     * Normalize the date according to the format (Ymd)
     * @param int|string Year
     * @param int|string Month
     * @param int|string Day
     */

    public static function getNormalizedDate($year, $month = 0, $day = 0): array
    {

        $date = [];

        if (self::getRightYear($year)) {

            if (!empty($month) && $month != '0') {

                $month = self::getRightMonth($month);
                $m = intval($month) < 10 ? '0' . $month : $month;
                $d = self::compareMaxDaysInMonth($day, $month, $year);

                if (intval($d) <= 0)
                    $d = 1;

                if ($d < 10)
                    $d = '0' . $d;

                $date = $year . $m . $d;

                $date = [
                    'year' => strval($year),
                    'month' => strval($m),
                    'day' => strval($d),
                    'normalized' => $year . $m . $d
                ];
            }
        }

        return $date;
    }


    /**
     * Normalize the time according to the format (His)
     * @param int|string Hour
     * @param int|string Minute
     * @param int|string Second
     */

    public static function getNormalizedTime($hour = 0, $minute = 0, $second = 0): array
    {

        $h = self::getRightHour($hour);
        $m = self::getRightSm($minute);
        $s = self::getRightSm($second);

        if ($h < 10)
            $h = '0' . $h;

        if ($m < 10)
            $m = '0' . $m;

        if ($s < 10)
            $s = '0' . $s;

        $time = [
            'hour' => strval($h),
            'minute' => strval($m),
            'second' => strval($s),
            'normalized' => $h . $m . $s
        ];

        return $time;
    }


    /**
     * Get the right year
     * @param int|string Year
     */
    public static function getRightYear($year): int
    {
        return (mb_strlen(intval($year)) === 4) ? intval($year) : 0;
    }


    /**
     * Get the right month
     * @param int|string Month
     */

    public static function getRightMonth($month): int
    {
        $m = 1;

        if (intval($month) > 12)
            $m = 12;
        elseif (intval($month) <= 0)
            $m = 1;
        else
            $m = $month;

        return intval($m);
    }


    /**
     * Get the right hour
     * @param int|string Hour
     */

    public static function getRightHour($hour): int
    {

        $h = intval($hour);

        if ($h > 23)
            $h = 23;
        elseif ($h < 0)
            $h = 0;

        return $h;
    }


    /**
     * Get the right minute or second
     * @param int|string Minute|Second
     */

    public static function getRightSm($sm): int
    {
        $x = intval($sm);

        if ($x > 59)
            $x = 59;
        elseif ($x < 0)
            $x = 0;

        return $x;
    }


    /** Get the number of days in a month, taking into account the leap year
     * @param int|string Month
     * @param int|string Year
     */

    public static function getDaysInMonth($month, $year): int
    {
        $month = self::getRightMonth($month);
        $year = self::getRightYear($year);

        if (!$year)
            return 0;

        return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
    }


    /** If a leap year
     * @param int|string Year
     * @return bool
     */

    public static function isLeapYear($year): bool
    {
        return $year % 4 == 0;
    }


    /**
     * Comparing a number with the maximum number of days in a month
     * @param int|string Any integer
     * @param int|string Month
     * @param int|string Year
     */

    public static function compareMaxDaysInMonth($number, $month, $year): int
    {
        $maxDays = self::getDaysInMonth(intval($month), intval($year));

        if (intval($maxDays) < $number)
            $number = $maxDays;

        return intval($number);
    }


    /**
     * Get the date with the converted month
     * @param string|int Timestamp
     * @param bool If the year is equal to the current one, it will be hidden
     * @param string Time format. If the time format is specified, the time will be included in the general string
     * @param string Time format prefix
     */

    public static function getDateWithConvertedMonth($timestamp, bool $isHiddenCurrentYear = false, $timeFormat = '', $timeFormatPrefix = ''): string
    {

        $date = date(
            'd.m.Y' . $timeFormat,
            intval($timestamp)
        );

        $list = array(
            ".01." => Languages::get('common', 'datetime', '_january_'),
            ".02." => Languages::get('common', 'datetime', '_february_'),
            ".03." => Languages::get('common', 'datetime', '_march_'),
            ".04." => Languages::get('common', 'datetime', '_april_'),
            ".05." => Languages::get('common', 'datetime', '_may_'),
            ".06." => Languages::get('common', 'datetime', '_june_'),
            ".07." => Languages::get('common', 'datetime', '_july_'),
            ".08." => Languages::get('common', 'datetime', '_august_'),
            ".09." => Languages::get('common', 'datetime', '_september_'),
            ".10." => Languages::get('common', 'datetime', '_october_'),
            ".11." => Languages::get('common', 'datetime', '_november_'),
            ".12." => Languages::get('common', 'datetime', '_december_'),
        );

        $day = date("d", strtotime($date));
        $month = date(".m.", strtotime($date));
        $Y = date("Y", strtotime($date));
        $year = ($isHiddenCurrentYear && $Y == date('Y')) ? '' : ' ' . $Y;
        $time = '';

        if ($timeFormat)
            $time = ' ' . ($timeFormatPrefix ? $timeFormatPrefix : Languages::get('common', 'datetime', 'in')) . ' ' . date($timeFormat, strtotime($date));

        return  $day . ' ' . $list[$month] . $year . $time;
    }
}
