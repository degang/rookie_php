<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieCookie
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieCookie {

    /**
     * @var string $salt 加密COOKIE 
     */
    public static $salt = 'dasfsadfioovckfdjjgfd';

    /**
     * @var integer $expiration 过期时间 
     */
    public static $expiration = 0;

    /**
     * @var string $path 地址 
     */
    public static $path = '/';

    /**
     * @var string $domain cookie 所在的域 
     */
    public static $domain = NULL;

    /**
     * @var boolean $secure 通过安全连接传输 
     */
    public static $secure = FALSE;

    /**
     * @var boolean $httponly 只有通过HTTP传输，禁用JavaScript访问
     */
    public static $httponly = FALSE;

    /**
     * 获取cookie
     * $cookie = RookieCookie::get('theme', 'blue');
     *
     * @param   string  $key cookie 名称 
     * @param   mixed    
     * @return  string
     */
    public static function get($key, $default = NULL)
    { //{{{
        if ( ! isset($_COOKIE[$key]))
            return $default;

        $cookie = $_COOKIE[$key];

        $split = strlen(RookieCookie::salt($key, NULL));

        if (isset($cookie[$split]) AND $cookie[$split] === '~')
        {
            list ($hash, $value) = explode('~', $cookie, 2);

            if (RookieCookie::salt($key, $value) === $hash)
                return $value;

            RookieCookie::delete($key);
        }

        return $default;
    } //}}}

    /**
     * 设置cookie
     * Cookie::set('theme', 'red');
     *
     * @param   string   $name name of cookie
     * @param   string   $value value of cookie
     * @param   integer  $expiration lifetime in seconds
     * @return  boolean
     */
    public static function set($name, $value, $expiration = NULL)
    { //{{{
        if ($expiration === NULL)
            $expiration = RookieCookie::$expiration;

        if ($expiration !== 0)
            $expiration += time();

        if ($value)
            $value = RookieCookie::salt($name, $value).'~'.$value;
        else 
            $value = '';

        return setcookie($name, $value, $expiration, RookieCookie::$path, RookieCookie::$domain, 
            RookieCookie::$secure, RookieCookie::$httponly);
    } //}}}

    /**
     * 删除cookie
     * RookieCookie::delete('theme');
     *
     * @param   string  $name cookie name
     * @return  boolean
     */
    public static function delete($name)
    { //{{{
        unset($_COOKIE[$name]);

        return setcookie($name, NULL, -86400, RookieCookie::$path, RookieCookie::$domain, 
            RookieCookie::$secure, RookieCookie::$httponly);
    } //}}}

    /**
     * //加密
     * $salt = RookieCookie::salt('theme', 'red');
     *
     * @param   string  $name cookie name 
     * @param   string  $value 
     * @return  string
     */
    public static function salt($name, $value)
    { //{{{
        // Require a valid salt
        if ( ! RookieCookie::$salt)
        {
            throw new RookieException('Please set RookieCookie::$salt.');
        }

        // Determine the user agent
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : 
            'unknown';

        return sha1($agent.$name.$value.RookieCookie::$salt);
    } //}}}

} 

