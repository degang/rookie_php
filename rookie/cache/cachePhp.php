<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieCachePhp
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieCachePhp 
{

    public static $_directory;

    public static $hashing = false;
        
    public function __construct($config)
    { //{{{
        self::$_directory = $config['directory'];
        self::$hashing = $config['hash'];
    } //}}}

    /**
     * »ñÈ¡»º´æid 
     *
     * @param string $id 
     * @return mixed 
     */
    public static function get($id) 
    { //{{{
        if(self::$hashing===true)
            $cfile = self::$_directory . md5($id) . '.php';
        else
            $cfile = self::$_directory . $id . '.php';

        if (file_exists($cfile)){
            include $cfile ;
            if(time() < $data[0]){
                return $data[1];
            }else{
                unlink($cfile);
            }
        }
    } //}}}

     /**
      * Ìí¼Ó»º´æ 
      *
      * @param string $id 
      * @param mixed $value 
      * @param int $expire 
      * @return bool
      */
    public static function set($id, $value, $expire=0) 
    { //{{{
        if($expire===0)
            $expire = time()+31536000;
        else
            $expire = time()+$expire;

        if(self::$hashing===true)
            return file_put_contents(self::$_directory . md5($id) . '.php', 
                '<?php defined(\'ROOKIE\') or die(\'No direct script access.\'); '.PHP_EOL.
                ' $data = array('.$expire.', '. var_export($value, true) . '); ?>', LOCK_EX);
        
        return file_put_contents(self::$_directory . $id . '.php', 
            '<?php defined(\'ROOKIE\') or die(\'No direct script access.\'); '.PHP_EOL.
            ' $data = array('.$expire.', '. var_export($value, true) . '); ?>', LOCK_EX);
    } //}}}

    /**
     * É¾³ý»º´æ
     *
     * @param $id 
     * @return mixed
     */
    public static function flush($id) 
    { //{{{
        if(self::$hashing===true)
            $cfile = self::$_directory.md5($id).'.php';
        else
            $cfile = self::$_directory.$id.'.php';

        if (file_exists($cfile)) {
            unlink($cfile);
            return true;
        }
        return false;
    } //}}}

    /**
     * É¾³ýËùÓÐ»º´æ
     *
     * @return bool
     */
    public static function flushAll() 
    { //{{{
        $handle = opendir(self::$_directory);

        while(($file = readdir($handle)) !== false) {
            if (is_file($file))
                unlink($file);
        }
        return true;
    } //}}}

}
