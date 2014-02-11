<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieMongodb
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 

class RookieMongodb 
{
    private static $_config; 

    private static $_mongo;

    private static $_path = array();

    public function setInit($config = array())
    {
        self::$_config = $config;
        $className = $config['className'];
        if ( ! in_array($className, self::$_path))
        {

            $loadClassPath = dirname(__FILE__).DS.ucfirst($config['type']).'.php';
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
        return self::$_mongo;
    }




}
