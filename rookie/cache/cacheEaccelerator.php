<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieCacheEaccelerator
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieCacheEaccelerator
{

    public function setInit($param = '') {}
    
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
        return eaccelerator_put($id, $data, $expire);
    } //}}}

    /**
     * 获取缓存
     *
     * @param string $id 
     * @return mixed 
     */
    public function get($id)
    { //{{{
        return eaccelerator_get($id);
    } //}}}

    /**
     * 删除缓存
     *
     * @param string $id 
     * @return bool 
     */
    public function flush($id)
    { //{{{
        return eaccelerator_rm($id);
    } //}}}

    /**
     * 删除所有缓存
     * @return bool
     */
    public function flushAll()
    { //{{{
        //delete expired content then delete all
        eaccelerator_gc();

        $idkeys = eaccelerator_list_keys();

        foreach($idkeys as $k)
            $this->flush(substr($k['name'], 1));
    } //}}}

}

