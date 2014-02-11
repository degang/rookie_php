<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieLog
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieLog 
{
    /**
     * @var array $logLevels 日志级别
     */
    public static $logLevels = array(
        'EMERGENCY',
        'ALERT',
        'CRITICAL',
        'ERROR',
        'WARNING',
        'NOTICE',
        'INFO',
        'DEBUG',
    );

    protected static $_directory;

    /**
     * 写入日志
     *
     * @param string  $message 
     * @param string  $module
     * @param integer $level 
     * @param string  $directory
     */
    public static function write($message, $module = null, $level = 0, $directory = null)
    { //{{{
        if (isset(RookieLog::$logLevels[$level]))
            $level = RookieLog::$logLevels[$level];

        if ($directory === null)
            RookieLog::$_directory = RookieCore::$config['base']['logPath']; 
        else
            RookieLog::$_directory = $directory;

        if ($module) 
        {
            RookieLog::$_directory = RookieLog::$_directory.$module.DS;

            if ( ! is_dir(RookieLog::$_directory))
            {
                mkdir(RookieLog::$_directory, 02777);
                chmod(RookieLog::$_directory, 02777);
            }
        }
                
        if ( ! is_dir(RookieLog::$_directory) && ! is_writable($directory))
        {
            throw new RookieException('Directory :dir must be writable', 
                array(':dir' => RookieLog::$_directory));
        }

        $directory = RookieLog::$_directory.date('Y');
        if ( ! is_dir($directory))
        {
            mkdir($directory, 02777);
            chmod($directory, 02777);
        }

        $directory .= DS.date('m');
        if ( ! is_dir($directory))
        {
            mkdir($directory, 02777);
            chmod($directory, 02777);
        }

        $fileName = $directory.DS.date('d').'----'.md5(date('d')).'.php';
        
        if ( ! file_exists($fileName))
        {
            file_put_contents($fileName, 
                "<?php defined('SYSPATH') or die('No direct script access.'); ?>".PHP_EOL);
            chmod($fileName, 0666);
        }

        $logInfo = array(
            'time'  => date("Y-m-d H:i:s", time()),
            'level' => $level,
            'body'  => $message,
            'url'   => 'http://'.$_SERVER['HTTP_HOST'].@$_SERVER['REQUEST_URI']
        );

        file_put_contents($fileName, PHP_EOL.$logInfo['time'].' ---- '.
            $logInfo['level'].': '.$logInfo['body'].' url: '.$logInfo['url'], FILE_APPEND);

    } //}}}


}
