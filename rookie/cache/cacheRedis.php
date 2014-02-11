<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieCacheRedis
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 

class RookieCacheRedis
{

    public static $_redis;

    public static $_config;

    public static $_cache;
    
    public function __construct($conf = array())
    { //{{{
        if ( ! class_exists('Redis'))
            throw new RookieException(' Class \'redis\' not found');

        self::$_redis = new Redis();

        if ($conf !== NULL)
        {
            //最好情部配置一下twemproxy中间代理
            self::$_redis->connect($conf['servers'][0][0], $conf['servers'][0][1]); 
        }
        else
            self::$_redis->connect('127.0.0.1', 6379);

        return self::$_redis;
    }  //}}}

    
    /**
     * 添加缓存 
     *
     * @param string $id cache id 
     * @param mixed $data 
     * @param int $expire 
     * @param int $compressed 
     * @return bool 
     */
    public static function set($id, $data, $expire = 0)
    { //{{{
        if ($expire)
            return self::$_redis->setex($id, $expire, $data);
        else
            return self::$_redis->set($id, $data);
    } //}}}

    /**
     * 获取缓存 
     *
     * @param string $id 
     * @return mixed 
     */
    public static function get($id)
    { //{{{
        return self::$_redis->get($id);
    } //}}}
    
    /**
     * 删除缓存 
     *
     * @param string $id 
     * @return bool 
     */
    public static function flush($id)
    { //{{{
        return self::$_redis->delete($id);
    } //}}}

    /**
     * 删除所有缓 
     *
     * @return bool 
     */
    public static function flushAll()
    { //{{{
        return self::$_redis->flushAll();
    } //}}}

    /**
     * 获取key过期时间
     */
    public static function ttl($key)
    { //{{{
        return self::$_redis->ttl($key);
    } //}}}


    /**
     * 获取当前的实例
     *
     * @return object 
     */
    public static function getInit()
    { //{{{
        return self::$_redis;
    } //}}}

}
