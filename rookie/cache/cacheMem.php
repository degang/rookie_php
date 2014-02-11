<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieCacheMem
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 

class RookieCacheMem
{
    public static $_memcache;

    public static $_config;

    public static $_cache;
    
    public function __construct($conf=array()) 
    { //{{{
        if( ! class_exists('Memcached'))
            throw new RookieException(' Class \'Memcache\' not found' );
                
        self::$_memcache = new Memcached();
        
        if($conf!==Null)
        {
            self::$_memcache->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_PHP); 
            //设置key的hash算法
            self::$_memcache->setOption(Memcached::OPT_HASH, Memcached::HASH_DEFAULT);
            //self::$_memcache->setOption(Memcached::OPT_HASH, Memcached::HASH_CRC);  
            //余数分布算法
            // self::$_memcache->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_MODULA);
            //一致性分布算法,提供 了更好的分配策略并且在添加服务器到集群时可以最小化缓存丢失
            self::$_memcache->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT); 
            self::$_memcache->setOption(Memcached::OPT_BINARY_PROTOCOL, true);

            self::$_memcache->addServers($conf['servers']);
        }
        else
        {
            self::$_memcache->addServer('127.0.0.1', 11211);
        }
        return self::$_memcache;
    } //}}}

    /**
     * 添加缓存 
     *
     * @param string $id cache id 
     * @param mixed $data 
     * @param int $expire 
     * @param int $compressed 
     * @return bool 
     */
    public static function set($id, $data, $expire=3600)
    { //{{{
         return self::$_memcache->add($id, $data, $expire);
    } //}}}

    /**
     * 获取缓存 
     *
     * @param string $id 
     * @return mixed 
     */
    public static function get($id)
    { //{{{
        return self::$_memcache->get($id);
    } //}}}
    
    /**
     * 删除缓存 
     *
     * @param string $id 
     * @return bool 
     */
    public static function flush($id)
    { //{{{
        return self::$_memcache->delete($id);
    } //}}}

    /**
     * 删除所有缓 
     *
     * @return bool 
     */
    public static function flushAll()
    { //{{{
        return self::$_memcache->flush();
    } //}}}

     /**
     * 获取当前的实例
     *
     * @return object 
     */
    public static function getInit()
    { //{{{
        return self::$_memcache;
    } //}}}

    public static function replace($key, $val, $expire = 3600)
    {
        return self::$_memcache->replace($key, $val, $expire);
    }
}
