<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieUTF8
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieUTF8 
{
    /**
     * 判断一个字符串是否是7个字节的ASCII码
     * $ascii = RookieUTF8::is_ascii($str);
     *
     * @param   mixed    string or array of strings to check
     * @return  boolean
     */
    public static function isAscii($str)
    { //{{{
        if (is_array($str))
        {
            $str = implode($str);
        }

        return ! preg_match('/[^\x00-\x7F]/S', $str);
    } //}}}

    /**
     * 带出所有非7位ASCII字节 
     * $str = RookieUTF8::stripNonAscii($str);
     *
     * @param   string  string to clean
     * @return  string
     */
    public static function stripNonAscii($str)
    { //{{{
        return preg_replace('/[^\x00-\x7F]+/S', '', $str);
    } //}}}

    /**
     * 返回字符串的长度
     * $length = RookieUTF8::strlen($str);
     *
     * @param   string   string being measured for length
     * @return  integer
     * @uses    RookieUTF8::$server_utf8
     */
    public static function strlen($str)
    { //{{{
        return mb_strlen($str, RookieCore::$config['base']['charset']);
    } //}}}

    /**
     * 查找字符所在的位置
     * $position = RookieUTF8::strpos($str, $search);
     *
     * @param   string   haystack
     * @param   string   needle
     * @param   integer  offset from which character in haystack to start searching
     * @return  integer  position of needle
     * @return  boolean  FALSE if the needle is not found
     * @uses    RookieUTF8::$server_utf8
     */
    public static function strpos($str, $search, $offset = 0)
    { //{{{
        return mb_strpos($str, $search, $offset, RookieCore::$config['base']['charset']);
    } //}}}

    /**
     * 查找一个UTF-8字符串中最后出现的一个字符的位置
     * $position = RookieUTF8::strrpos($str, $search);
     *
     * @param   string   haystack
     * @param   string   needle
     * @param   integer  offset from which character in haystack to start searching
     * @return  integer  position of needle
     * @return  boolean  FALSE if the needle is not found
     * @uses    RookieUTF8::$server_utf8
     */
    public static function strrpos($str, $search, $offset = 0)
    { //{{{
        return mb_strrpos($str, $search, $offset, RookieCore::$config['base']['charset']);
    } //}}}

    /**
     * 截取字符串
     * $sub = RookieUTF8::substr($str, $offset);
     *
     * @param   string   input string
     * @param   integer  offset
     * @param   integer  length limit
     * @return  string
     * @uses    RookieUTF8::$server_utf8
     * @uses    Kohana::$charset
     */
    public static function substr($str, $offset, $length = NULL)
    { //{{{
        $charset = RookieCore::$config['base']['charset'];
        return ($length === NULL)
            ? mb_substr($str, $offset, mb_strlen($str), $charset)
            : mb_substr($str, $offset, $length, $charset);
    } //}}}

    /**
     * 转成小写
     * $str = RookieUTF8::strtolower($str);
     *
     * @param   string   mixed case string
     * @return  string
     * @uses    RookieUTF8::$server_utf8
     */
    public static function strtolower($str)
    { //{{{
        return mb_strtolower($str,  RookieCore::$config['base']['charset']);
    } //}}}

    /**
     * 转成大写
     *
     * @param   string   mixed case string
     * @return  string
     * @uses    RookieUTF8::$server_utf8
     * @uses    Kohana::$charset
     */
    public static function strtoupper($str)
    { //{{{
        return mb_strtoupper($str, RookieCore::$config['base']['charset']);        
    } //}}}

} 
if (!extension_loaded('mbstring'))
    throw new RookieException('Mbstring extension module is not installed');

