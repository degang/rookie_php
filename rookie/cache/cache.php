<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieCache
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 

class RookieCache implements RookieModule
{

    private static $_config;

    private static $_cache;

    private static $_path = array();

    public function setInit($config = array())
    { //{{{
        self::$_config = $config;
        $className = 'RookieCache'.ucfirst($config['type']);
        if ( ! in_array($className, self::$_path))
        {

            $loadClassPath = dirname(__FILE__).DS.'cache'.ucfirst($config['type']).'.php';
            if (file_exists($loadClassPath))
            {
                require $loadClassPath;
                self::$_cache = new $className ($config);
                self::$_path[] = $className;
                return self::$_cache;
            }
            else
                throw new RookieException('File does not exist');
        }
        return self::$_cache;
    } //}}}

    public static function set($id, $value, $expire = 3600)
    {
        return self::$_cache->set($id, $value, $expire);
    }

    public static function get($id)
    {
        return self::$_cache->get($id);
    }

    public static function flush($id)
    {
        return self::$_cache->flush($id);
    }

    public static function flushAll()
    {
        return self::$_cache->flushAll();
    }

    public static function init()
    {
        return self::$_cache->getInit(); 
    }

    public static function ttl($key)
    { 
        //获取key过期时间
        return self::$_cache->ttl($key);
    }

    public static function replace($key, $val, $expire = 3600)
    {
        return self::$_cache->replace($key, $val, $expire);
    }

   
}
