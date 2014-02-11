<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieCacheApc
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieCacheApc
{

    public function __construct($conf = array()){}


    /**
     * 添加缓存 
     *
     * @param string $id 
     * @param mixed $data 
     * @param int $expire 
     * @return bool 
     */
    public function set($id, $data, $expire=0)
    { //{{{
        return apc_store($id, $data, $expire);
    } //}}}

    /**
     * 获取缓存 
     *
     * @param string|array $id 
     * @return mixed 
     */
    public function get($id)
    { //{{{
        return apc_fetch($id);
    } //}}}

    /**
     * 删除缓存 
     *
     * @param string $id 
     * @return bool 
     */
    public function flush($id)
    { //{{{
        return apc_delete($id);
    } //}}}

    /**
     * 删除所有缓存 
     *
     * @return bool 
     */
    public function flushAll()
    { //{{{
        return apc_clear_cache('user');
    } //}}}

}
