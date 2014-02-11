<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieDate
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieDate 
{

    //对应的秒数
    const YEAR   = 31556926;
    const MONTH  = 2629744;
    const WEEK   = 604800;
    const DAY    = 86400;
    const HOUR   = 3600;
    const MINUTE = 60;

    //可用格式化RookieDate::months()
    const MONTHS_LONG  = '%B';
    const MONTHS_SHORT = '%b';

    /**
     * @var string 默认格式化时间 
     */
    public static $timestamp_format = 'Y-m-d H:i:s';

    /**
     * Timezone for formattedTime
     * @link http://uk2.php.net/manual/en/timezones.php
     * @var  string
     */
    public static $timezone;

    /**
     * 设置时区
     * $seconds = RookieDate::offset('America/Chicago', 'GMT');
     *
     * @param   string   timezone that to find the offset of
     * @param   string   timezone used as the baseline
     * @param   mixed    UNIX timestamp or date string
     * @return  integer
     */
    public static function offset($remote, $local = NULL, $now = NULL)
    { //{{{
        if ($local === NULL)
            $local = date_default_timezone_get();

        if (is_int($now))
            $now = date(DateTime::RFC2822, $now);

        $zone_remote = new DateTimeZone($remote);
        $zone_local  = new DateTimeZone($local);

        $time_remote = new DateTime($now, $zone_remote);
        $time_local  = new DateTime($now, $zone_local);

        $offset = $zone_remote->getOffset($time_remote) - $zone_local->getOffset($time_local);

        return $offset;
    } //}}}

    /**
     * 秒数
     * $seconds = RookieDate::seconds(); // 01, 02, 03, ..., 58, 59, 60
     *
     * @param   integer  amount to increment each step by, 1 to 30
     * @param   integer  start value
     * @param   integer  end value
     * @return  array    A mirrored (foo => foo) array from 1-60.
     */
    public static function seconds($step = 1, $start = 0, $end = 60)
    { //{{{
        $step = (int) $step;
        $seconds = array();

        for ($i = $start; $i < $end; $i += $step)
            $seconds[$i] = sprintf('%02d', $i);

        return $seconds;
    } //}}}

    /**
     * 分钟
     * $minutes = RookieDate::minutes(); // 05, 10, 15, ..., 50, 55, 60
     *
     * @uses    RookieDate::seconds
     * @param   integer  amount to increment each step by, 1 to 30 步长
     * @return  array    A mirrored (foo => foo) array from 1-60.
     */
    public static function minutes($step = 5)
    { //{{{
        return RookieDate::seconds($step);
    } //}}}

    /**
     * 小时
     * $hours = RookieDate::hours(); // 01, 02, 03, ..., 10, 11, 12
     *
     * @param   integer  amount to increment each step by
     * @param   boolean  use 24-hour time
     * @param   integer  the hour to start at
     * @return  array    A mirrored (foo => foo) array from start-12 or start-23.
     */
    public static function hours($step = 1, $long = FALSE, $start = NULL)
    { //{{{
        // Default values
        $step = (int) $step;
        $long = (bool) $long;
        $hours = array();

        // Set the default start if none was specified.
        if ($start === NULL)
        {
            $start = ($long === FALSE) ? 1 : 0;
        }

        $hours = array();

        // 24-hour time has 24 hours, instead of 12
        $size = ($long === TRUE) ? 23 : 12;

        for ($i = $start; $i <= $size; $i += $step)
        {
            $hours[$i] = (string) $i;
        }

        return $hours;
    } //}}}

    /**
     * 返回上午或下午，根据一个给定的小时（24小时制）
     * $type = RookieDate::ampm(12); // PM
     * $type = RookieDate::ampm(1);  // AM
     *
     * @param   integer  number of the hour
     * @return  string
     */
    public static function ampm($hour)
    { //{{{
        $hour = (int) $hour;
        return ($hour > 11) ? 'PM' : 'AM';
    } //}}}

    /**
     * 调整非24小时到24小时的数字
     * $hour = RookieDate::adjust(3, 'pm'); // 15
     *
     * @param   integer  hour to adjust
     * @param   string   AM or PM
     * @return  string
     */
    public static function adjust($hour, $ampm)
    { //{{{
        $hour = (int) $hour;
        $ampm = strtolower($ampm);

        switch ($ampm)
        {
            case 'am':
                if ($hour == 12)
                {
                    $hour = 0;
                }
            break;
            case 'pm':
                if ($hour < 12)
                {
                    $hour += 12;
                }
            break;
        }

        return sprintf('%02d', $hour);
    } //}}}

    /**
     * 给定一个年月的时候，生成月的天数
     *
     * RookieDate::days(4, 2010); // 1, 2, 3, ..., 28, 29, 30
     *
     * @param   integer  number of month
     * @param   integer  number of year to check month, defaults to the current year
     * @return  array    A mirrored (foo => foo) array of the days.
     */
    public static function days($month, $year = FALSE)
    { //{{{
        static $months;

        if ($year === FALSE)
        {
            // Use the current year by default
            $year = date('Y');
        }

        // Always integers
        $month = (int) $month;
        $year  = (int) $year;

        // We use caching for months, because time functions are used
        if (empty($months[$year][$month]))
        {
            $months[$year][$month] = array();

            // Use date to find the number of days in the given month
            $total = date('t', mktime(1, 0, 0, $month, 1, $year)) + 1;

            for ($i = 1; $i < $total; $i++)
            {
                $months[$year][$month][$i] = (string) $i;
            }
        }

        return $months[$year][$month];
    } //}}}

    /**
     * 月数的一年,通常用作生成一个快捷方式的列表
     * 
     * By default a mirrored array of $month_number => $month_number is returned
     *
     *     RookieDate::months();
     *     // aray(1 => 1, 2 => 2, 3 => 3, ..., 12 => 12)
     *
     * But you can customise this by passing in either RookieDate::MONTHS_LONG
     *
     *     RookieDate::months(RookieDate::MONTHS_LONG);
     *     // array(1 => 'January', 2 => 'February', ..., 12 => 'December')
     *
     * Or RookieDate::MONTHS_SHORT
     *
     *     RookieDate::months(RookieDate::MONTHS_SHORT);
     *     // array(1 => 'Jan', 2 => 'Feb', ..., 12 => 'Dec')
     *
     * @uses    RookieDate::hours
     * @param   string The format to use for months
     * @return  array  An array of months based on the specified format
     */
    public static function months($format = NULL)
    { //{{{
        $months = array();

        if ($format === RookieDate::MONTHS_LONG OR $format === RookieDate::MONTHS_SHORT)
        {
            for ($i = 1; $i <= 12; ++$i)
            {
                $months[$i] = strftime($format, mktime(0, 0, 0, $i, 1));
            }
        }
        else
        {
            $months = RookieDate::hours();
        }

        return $months;
    } //}}}

    /**
     * 返回一个数组年之间的开始和结束的一年。默认情况下，
     * 本年度 - 5和本年度+5将被使用。通常使用的
     * 作为快捷方式中的一种形式，可用于生成一个列表。
     * $years = RookieDate::years(2000, 2010); // 2000, 2001, ..., 2009, 2010
     *
     * @param   integer  starting year (default is current year - 5)
     * @param   integer  ending year (default is current year + 5)
     * @return  array
     */
    public static function years($start = FALSE, $end = FALSE)
    { //{{{
        // Default values
        $start = ($start === FALSE) ? (date('Y') - 5) : (int) $start;
        $end   = ($end   === FALSE) ? (date('Y') + 5) : (int) $end;

        $years = array();

        for ($i = $start; $i <= $end; $i++)
        {
            $years[$i] = (string) $i;
        }

        return $years;
    } //}}}

    /**
     * 返回两个时间戳之间的时间差
     * $span = RookieDate::span(60, 182, 'minutes,seconds'); // array('minutes' => 2, 'seconds' => 2)
     * $span = RookieDate::span(60, 182, 'minutes'); // 2
     *
     * @param   integer  timestamp to find the span of
     * @param   integer  timestamp to use as the baseline
     * @param   string   formatting string
     * @return  string   when only a single output is requested
     * @return  array    associative list of all outputs requested
     */
    public static function span($remote, $local = NULL, $output = 'years,months,weeks,days,hours,minutes,seconds')
    { //{{{
        // Normalize output
        $output = trim(strtolower( (string) $output));

        if ( ! $output)
        {
            // Invalid output
            return FALSE;
        }

        // Array with the output formats
        $output = preg_split('/[^a-z]+/', $output);

        // Convert the list of outputs to an associative array
        $output = array_combine($output, array_fill(0, count($output), 0));

        // Make the output values into keys
        extract(array_flip($output), EXTR_SKIP);

        if ($local === NULL)
        {
            // Calculate the span from the current time
            $local = time();
        }

        // Calculate timespan (seconds)
        $timespan = abs($remote - $local);

        if (isset($output['years']))
        {
            $timespan -= RookieDate::YEAR * ($output['years'] = (int) floor($timespan / RookieDate::YEAR));
        }

        if (isset($output['months']))
        {
            $timespan -= RookieDate::MONTH * ($output['months'] = (int) floor($timespan / RookieDate::MONTH));
        }

        if (isset($output['weeks']))
        {
            $timespan -= RookieDate::WEEK * ($output['weeks'] = (int) floor($timespan / RookieDate::WEEK));
        }

        if (isset($output['days']))
        {
            $timespan -= RookieDate::DAY * ($output['days'] = (int) floor($timespan / RookieDate::DAY));
        }

        if (isset($output['hours']))
        {
            $timespan -= RookieDate::HOUR * ($output['hours'] = (int) floor($timespan / RookieDate::HOUR));
        }

        if (isset($output['minutes']))
        {
            $timespan -= RookieDate::MINUTE * ($output['minutes'] = (int) floor($timespan / RookieDate::MINUTE));
        }

        // Seconds ago, 1
        if (isset($output['seconds']))
        {
            $output['seconds'] = $timespan;
        }

        if (count($output) === 1)
        {
            // Only a single output was requested, return it
            return array_pop($output);
        }

        // Return array
        return $output;
    } //}}}

    /**
     * 返回的时间和现在之间的差异
     *     $span = RookieDate::fuzzySpan(time() - 10); // "moments ago"
     *     $span = RookieDate::fuzzySpan(time() + 20); // "in moments"
     *
     * A second parameter is available to manually set the "local" timestamp,
     * however this parameter shouldn't be needed in normal usage and is only
     * included for unit tests
     *
     * @param   integer  "remote" timestamp
     * @param   integer  "local" timestamp, defaults to time()
     * @return  string
     */
    public static function fuzzySpan($timestamp, $local_timestamp = NULL)
    { //{{{
        $local_timestamp = ($local_timestamp === NULL) ? time() : (int) $local_timestamp;

        // Determine the difference in seconds
        $offset = abs($local_timestamp - $timestamp);
        if ($offset <= RookieDate::MINUTE)
        {
            $span =  $offset.'秒';
        }
        elseif ($offset < (RookieDate::MINUTE * 20))
        {
            $span = floor($offset/60);
            $span .= '分钟';
        }
        elseif ($offset < RookieDate::HOUR)
        {
            $span = '一小时';
        }
        elseif ($offset < (RookieDate::HOUR * 4))
        {
            $span = '几小时';
        }
        elseif ($offset < RookieDate::DAY)
        {
            $span = '一天';
        }
        elseif ($offset < (RookieDate::DAY * 2))
        {
            $span = '一天';
        }
        elseif ($offset < (RookieDate::DAY * 4))
        {
            $span = '几天';
        }
        elseif ($offset < RookieDate::WEEK)
        {
            $span = '一周';
        }
        elseif ($offset < (RookieDate::WEEK * 2))
        {
            $span = '一星期左右';
        }
        elseif ($offset < RookieDate::MONTH)
        {
            $span = '一个月前';
        }
        elseif ($offset < (RookieDate::MONTH * 2))
        {
            $span = '大约一个月';
        }
        elseif ($offset < (RookieDate::MONTH * 4))
        {
            $span = '两个月';
        }
        elseif ($offset < RookieDate::YEAR)
        {
            $span = '不到一年';
        }
        elseif ($offset < (RookieDate::YEAR * 2))
        {
            $span = '大约一年';
        }
        elseif ($offset < (RookieDate::YEAR * 4))
        {
            $span = '几年';
        }
        elseif ($offset < (RookieDate::YEAR * 8))
        {
            $span = '几年';
        }
        elseif ($offset < (RookieDate::YEAR * 12))
        {
            $span = '大约十年';
        }
        elseif ($offset < (RookieDate::YEAR * 24))
        {
            $span = '几十年';
        }
        elseif ($offset < (RookieDate::YEAR * 64))
        {
            $span = '几十年后';
        }
        else
        {
            $span = '很久';
        }

        if ($timestamp <= $local_timestamp)
        {
            // This is in the past
            if ($span != '刚刚')
                return $span.' 之前';
            else
                return $span;
        }
        else
        {
            // This in the future
            return 'in '.$span;
        }
    } //}}}

    /**
     * UNIX时间戳转换为DOS格式
     * $dos = RookieDate::unix2dos($unix);
     *
     * @param   integer  UNIX timestamp
     * @return  integer
     */
    public static function unix2dos($timestamp = FALSE)
    { //{{{
        $timestamp = ($timestamp === FALSE) ? getdate() : getdate($timestamp);

        if ($timestamp['year'] < 1980)
        {
            return (1 << 21 | 1 << 16);
        }

        $timestamp['year'] -= 1980;

        // What voodoo is this? I have no idea... Geert can explain it though,
        // and that's good enough for me.
        return ($timestamp['year']    << 25 | $timestamp['mon']     << 21 |
                $timestamp['mday']    << 16 | $timestamp['hours']   << 11 |
                $timestamp['minutes'] << 5  | $timestamp['seconds'] >> 1);
    } //}}}

    /**
     * 将一个DOS时间戳UNIX format
     * $unix = RookieDate::dos2unix($dos);
     *
     * @param   integer  DOS timestamp
     * @return  integer
     */
    public static function dos2unix($timestamp = FALSE)
    { //{{{
        $sec  = 2 * ($timestamp & 0x1f);
        $min  = ($timestamp >>  5) & 0x3f;
        $hrs  = ($timestamp >> 11) & 0x1f;
        $day  = ($timestamp >> 16) & 0x1f;
        $mon  = ($timestamp >> 21) & 0x0f;
        $year = ($timestamp >> 25) & 0x7f;

        return mktime($hrs, $min, $sec, $mon, $day, $year + 1980);
    } //}}}

    /**
     * 返回一个指定的时间戳格式的日期/时间字符串
     * $time = RookieDate::formattedTime('5 minutes ago');
     *
     * @see     http://php.net/manual/en/datetime.construct.php
     * @param   string  datetime_str     datetime string
     * @param   string  timestamp_format timestamp format
     * @return  string
     */
    public static function formattedTime($datetime_str = 'now', $timestamp_format = NULL, 
        $timezone = NULL)
    { //{{{
        $timestamp_format = ($timestamp_format == NULL) ? RookieDate::$timestamp_format : $timestamp_format;
        $timezone         = ($timezone === NULL) ? RookieDate::$timezone : $timezone;

        $time = new DateTime($datetime_str, new DateTimeZone(
            $timezone ? $timezone : date_default_timezone_get()
        ));

        return $time->format($timestamp_format);
    } //}}}

}  
