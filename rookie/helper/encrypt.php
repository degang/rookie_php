<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieEncrypt
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieEncrypt 
{
    /**
     * @var string mode 类型
     */
    public static $mode = MCRYPT_MODE_CBC;

    /**
     * @var string $cipher 创建密文兼容AES
     */
    public static $cipher = MCRYPT_RIJNDAEL_128;

    /**
     * 加密函数
     *
     * @param string $string 加密字符串
     * @return string 
     */
    public static function encode($string)
    { //{{{
        //获取key
        $key = self::getKey(); 
        $ivSize = mcrypt_get_iv_size(self::$cipher, self::$mode);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        $stringUtf8 = utf8_encode($string);
        $cipherString = mcrypt_encrypt(self::$cipher, $key, 
            $stringUtf8, self::$mode, $iv);
        $ciphertext = $iv.$cipherString;

        return base64_encode($ciphertext);

    } //}}}

    /**
     * 解密
     *
     * @param string $string
     * @return string
     */
    public static function decode($string)
    { //{{{
        $key = self::getKey();
        $string = base64_decode($string); 
        $ivSize = mcrypt_get_iv_size(self::$cipher, self::$mode);
        $ivDec = substr($string, 0, $ivSize);
        $string = substr($string, $ivSize);

        $stringUtf8Dec = mcrypt_decrypt(self::$cipher, $key,
           $string, self::$mode,  $ivDec);

        $stringUtf8Dec = utf8_decode($stringUtf8Dec);
        
        //fuck
        return rtrim($stringUtf8Dec, "\0");
    } //}}} 

    /**
     * 获取key
     * @return string 
     */
    public static function getKey()
    { //{{{
        //key使用十六进制指定
        $key = RookieCore::$config['mcrypt']['key'];
        $key = pack('H*', $key);

        //配置
        isset(RookieCore::$config['mcrypt']['mode']) && 
            self::$mode = RookieCore::$config['mcrypt']['mode'];
        isset(RookieCore::$config['mcrypt']['cipher']) &&
            self::$cipher = RookieCore::$config['mcrypt']['cipher'];
        
        return $key;

    } //}}}

}
