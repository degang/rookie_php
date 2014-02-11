<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieText
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieText 
{

    /**
     * @var  array 单位和文字等值 
     */
    public static $units = array(
        1000000000 => 'billion',
        1000000    => 'million',
        1000       => 'thousand',
        100        => 'hundred',
        90 => 'ninety',
        80 => 'eighty',
        70 => 'seventy',
        60 => 'sixty',
        50 => 'fifty',
        40 => 'fourty',
        30 => 'thirty',
        20 => 'twenty',
        19 => 'nineteen',
        18 => 'eighteen',
        17 => 'seventeen',
        16 => 'sixteen',
        15 => 'fifteen',
        14 => 'fourteen',
        13 => 'thirteen',
        12 => 'twelve',
        11 => 'eleven',
        10 => 'ten',
        9  => 'nine',
        8  => 'eight',
        7  => 'seven',
        6  => 'six',
        5  => 'five',
        4  => 'four',
        3  => 'three',
        2  => 'two',
        1  => 'one',
    );

    /**
     * 限制到一定数目的字短语 
     * $text = RookieText::limitWords($text);
     *
     * @param   string   phrase to limit words of
     * @param   integer  number of words to limit to
     * @param   string   end character or entity
     * @return  string
     */
    public static function limitWords($str, $limit = 100, $end_char = NULL)
    { //{{{
        $limit = (int) $limit;
        $end_char = ($end_char === NULL) ? '…' : $end_char;

        if (trim($str) === '')
            return $str;

        if ($limit <= 0)
            return $end_char;

        preg_match('/^\s*+(?:\S++\s*+){1,'.$limit.'}/u', $str, $matches);

        // Only attach the end character if the matched string is shorter
        // than the starting string.
        return rtrim($matches[0]).((strlen($matches[0]) === strlen($str)) ? '' : $end_char);
    } //}}}

    /**
     * 一个给定的字符数限制的短语 
     * $text = RookieText::limitChars($text);
     *
     * @param   string   phrase to limit characters of
     * @param   integer  number of characters to limit to
     * @param   string   end character or entity
     * @param   boolean  enable or disable the preservation of words while limiting
     * @return  string
     * @uses    RookieUTF8::strlen
     */
    public static function limitChars($str, $limit = 100, $end_char = NULL, $preserve_words = FALSE)
    { //{{{
        $end_char = ($end_char === NULL) ? '…' : $end_char;

        $limit = (int) $limit;

        if (trim($str) === '' OR RookieUTF8::strlen($str) <= $limit)
            return $str;

        if ($limit <= 0)
            return $end_char;

        if ($preserve_words === FALSE)
            return rtrim(RookieUTF8::substr($str, 0, $limit)).$end_char;

        // Don't preserve words. The limit is considered the top limit.
        // No strings with a length longer than $limit should be returned.
        if ( ! preg_match('/^.{0,'.$limit.'}\s/us', $str, $matches))
            return $end_char;

        return rtrim($matches[0]).((strlen($matches[0]) === strlen($str)) ? '' : $end_char);
    } //}}}

    /**
     * 生成一个随机字符串，一个给定的类型和长  
     * $str = RookieText::random(); // 8 character random string
     *
     * alnum
     * :  Upper and lower case a-z, 0-9 (default)
     *
     * alpha
     * :  Upper and lower case a-z
     *
     * hexdec
     * :  Hexadecimal characters a-f, 0-9
     *
     * distinct
     * :  Uppercase characters and numbers that cannot be confused
     *
     * You can also create a custom type by providing the "pool" of characters
     * as the type.
     *
     * @param   string   a type of pool, or a string of characters to use as the pool
     * @param   integer  length of string to return
     * @return  string
     * @uses    RookieUTF8::split
     */
    public static function random($type = NULL, $length = 8)
    { //{{{
        if ($type === NULL)
        {
            // Default is to generate an alphanumeric string
            $type = 'alnum';
        }

        $utf8 = FALSE;

        switch ($type)
        {
            case 'alnum':
                $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
            case 'alpha':
                $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
            case 'hexdec':
                $pool = '0123456789abcdef';
            break;
            case 'numeric':
                $pool = '0123456789';
            break;
            case 'nozero':
                $pool = '123456789';
            break;
            case 'distinct':
                $pool = '2345679ACDEFHJKLMNPRSTUVWXYZ';
            break;
            default:
                $pool = (string) $type;
                $utf8 = ! RookieUTF8::isAscii($pool);
            break;
        }

        // Split the pool into an array of characters
        $pool = ($utf8 === TRUE) ? RookieUTF8::str_split($pool, 1) : str_split($pool, 1);

        // Largest pool key
        $max = count($pool) - 1;

        $str = '';
        for ($i = 0; $i < $length; $i++)
        {
            // Select a random character from the pool and add it to the string
            $str .= $pool[mt_rand(0, $max)];
        }

        // Make sure alnum strings contain at least one letter and one digit
        if ($type === 'alnum' AND $length > 1)
        {
            if (ctype_alpha($str))
            {
                // Add a random digit
                $str[mt_rand(0, $length - 1)] = chr(mt_rand(48, 57));
            }
            elseif (ctype_digit($str))
            {
                // Add a random letter
                $str[mt_rand(0, $length - 1)] = chr(mt_rand(65, 90));
            }
        }

        return $str;
    } //}}}

    /**
     * 大写的字没有用空格分隔，使用自定义  
     * $str = RookieText::ucfirst('content-type'); // returns "Content-Type" 
     *
     * @param   string    string to transform
     * @param   string    delemiter to use
     * @return  string
     */
    public static function ucfirst($string, $delimiter = '-')
    { //{{{
        // Put the keys back the Case-Convention expected
        return implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
    } //}}}

    /**
     * 降低多个斜线字符串中的单斜杠 
     * $str = RookieText::reduceSlashes('foo//bar/baz'); // "foo/bar/baz"
     *
     * @param   string  string to reduce slashes of
     * @return  string
     */
    public static function reduceSlashes($str)
    { //{{{
        return preg_replace('#(?<!:)//+#', '/', $str);
    } //}}}

    /**
     * 查找一组单词之间相似的文本 
     * $match = RookieText::similar(array('fred', 'fran', 'free'); // "fr"
     *
     * @param   array   words to find similar text of
     * @return  string
     */
    public static function similar(array $words)
    { //{{{
        // First word is the word to match against
        $word = current($words);

        for ($i = 0, $max = strlen($word); $i < $max; ++$i)
        {
            foreach ($words as $w)
            {
                // Once a difference is found, break out of the loops
                if ( ! isset($w[$i]) OR $w[$i] !== $word[$i])
                    break 2;
            }
        }

        // Return the similar text
        return substr($word, 0, $i);
    } //}}}

    /**
     * 自动适用于p和br标记文本
     * echo RookieText::autoP($text);
     *
     * @param   string   subject
     * @param   boolean  convert single linebreaks to <br />
     * @return  string
     */
    public static function autoP($str, $br = TRUE)
    { //{{{
        // Trim whitespace
        if (($str = trim($str)) === '')
            return '';

        // Standardize newlines
        $str = str_replace(array("\r\n", "\r"), "\n", $str);

        // Trim whitespace on each line
        $str = preg_replace('~^[ \t]+~m', '', $str);
        $str = preg_replace('~[ \t]+$~m', '', $str);

        // The following regexes only need to be executed if the string contains html
        if ($html_found = (strpos($str, '<') !== FALSE))
        {
            // Elements that should not be surrounded by p tags
            $no_p = '(?:p|div|h[1-6r]|ul|ol|li|blockquote|d[dlt]|pre|t[dhr]|t(?:able|body|foot|head)|c(?:aption|olgroup)|form|s(?:elect|tyle)|a(?:ddress|rea)|ma(?:p|th))';

            // Put at least two linebreaks before and after $no_p elements
            $str = preg_replace('~^<'.$no_p.'[^>]*+>~im', "\n$0", $str);
            $str = preg_replace('~</'.$no_p.'\s*+>$~im', "$0\n", $str);
        }

        // Do the <p> magic!
        $str = '<p>'.trim($str).'</p>';
        $str = preg_replace('~\n{2,}~', "</p>\n\n<p>", $str);

        // The following regexes only need to be executed if the string contains html
        if ($html_found !== FALSE)
        {
            // Remove p tags around $no_p elements
            $str = preg_replace('~<p>(?=</?'.$no_p.'[^>]*+>)~i', '', $str);
            $str = preg_replace('~(</?'.$no_p.'[^>]*+>)</p>~i', '$1', $str);
        }

        // Convert single linebreaks to <br />
        if ($br === TRUE)
        {
            $str = preg_replace('~(?<!\n)\n(?!\n)~', "<br />\n", $str);
        }

        return $str;
    } //}}}

    /**
     * 返回可读的尺寸 
     * echo RookieText::bytes(filesize($file));
     *
     * @param   integer  size in bytes
     * @param   string   a definitive unit
     * @param   string   the return string format
     * @param   boolean  whether to use SI prefixes or IEC
     * @return  string
     */
    public static function bytes($bytes, $force_unit = NULL, $format = NULL, $si = TRUE)
    { //{{{
        // Format string
        $format = ($format === NULL) ? '%01.2f %s' : (string) $format;

        // IEC prefixes (binary)
        if ($si == FALSE OR strpos($force_unit, 'i') !== FALSE)
        {
            $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
            $mod   = 1024;
        }
        // SI prefixes (decimal)
        else
        {
            $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
            $mod   = 1000;
        }

        // Determine unit to use
        if (($power = array_search( (string) $force_unit, $units)) === FALSE)
        {
            $power = ($bytes > 0) ? floor(log($bytes, $mod)) : 0;
        }

        return sprintf($format, $bytes / pow($mod, $power), $units[$power]);
    } //}}}

    /**
     * 可读的文本格式的数字 
     * echo RookieText::number(1024);
     * // Display: five million, six hundred and thirty-two
     * echo RookieText::number(5000632);
     *
     * @param   integer   number to format
     * @return  string
     */
    public static function number($number)
    { //{{{
        // The number must always be an integer
        $number = (int) $number;

        // Uncompiled text version
        $text = array();

        // Last matched unit within the loop
        $last_unit = NULL;

        // The last matched item within the loop
        $last_item = '';

        foreach (RookieText::$units as $unit => $name)
        {
            if ($number / $unit >= 1)
            {
                // $value = the number of times the number is divisble by unit
                $number -= $unit * ($value = (int) floor($number / $unit));
                // Temporary var for textifying the current unit
                $item = '';

                if ($unit < 100)
                {
                    if ($last_unit < 100 AND $last_unit >= 20)
                    {
                        $last_item .= '-'.$name;
                    }
                    else
                    {
                        $item = $name;
                    }
                }
                else
                {
                    $item = RookieText::number($value).' '.$name;
                }

                // In the situation that we need to make a composite number (i.e. twenty-three)
                // then we need to modify the previous entry
                if (empty($item))
                {
                    array_pop($text);

                    $item = $last_item;
                }

                $last_item = $text[] = $item;
                $last_unit = $unit;
            }
        }

        if (count($text) > 1)
        {
            $and = array_pop($text);
        }

        $text = implode(', ', $text);

        if (isset($and))
        {
            $text .= ' and '.$and;
        }

        return $text;
    } //}}}
}

