<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * Rookie exception
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieException extends exception
{
    //错误内容类型
    public static $errorViewContentType = 'text/html';

    //错误类型
    protected $code = 0;
    
    //定义错误信息
    public static $phpErrors = array( //{{{
        E_ERROR             => 'Fatal Error',       //致命错误
        E_USER_ERROR        => 'User Error',        //用户错误
        E_PARSE             => 'Parse Error',       //解析错误
        E_WARNING           => 'Warning',           //警告
        E_USER_WARNING      => 'User Warning',      //用户警告
        E_STRICT            => 'Strict',            //已过时的函数会提示,E_ALL不包括 E_STRICT，因此其默认未激活
        E_NOTICE            => 'Notice',            //会对代码中可能出现的bug给出警告
        E_USER_NOTICE       => 'User notice',       //用户生成的通知消息
        E_CORE_ERROR        => 'Core Error',        //PHP的初始启动过程中发生的致命错误。
        E_CORE_WARNING      => 'Core Warning',      //PHP的初始启动过程中发生的警告（非致命错误）
        E_COMPILE_ERROR     => 'Compile Error',     //致命的编译时错误
        E_COMPILE_WARNING   => 'Compile warning',   //致命的编译时警告
        E_RECOVERABLE_ERROR => 'Recoverable Error', //捕致命错误。这表明可能是危险的错误发生，但没有离开发动机处于不稳定的状态。
    ); //}}}

    /**
     * 创建一个新的异常
     *
     * @param  string $message
     * @param  array  $variables 
     * @param  mixed  $code
     * @return void
     */
    public function __construct($message, array $variables= NULL, $code = 0)
    { //{{{
        if (defined('E_DEPRECATED'))
        {
            // E_DEPRECATED只存在于 PHP >= 5.3.0
            RookieException::$phpErrors[E_USER_DEPRECATED ] = 'user deprecated';
            RookieException::$phpErrors[E_DEPRECATED] = 'Deprecated';
        }
        
        count($variables) && $message = RookieException::setMessage($message, $variables);

        // 传递消息到父和整数代码
        parent::__construct($message, (int) $code);

        //保存未修改的代码
        $this->code = $code;
    } //}}}

    /**
     * 异常处理
     *
     * @param object Exception
     * @return mixed 
     */
    public static function handler(Exception $e)
    { //{{{
        try{
            $type = get_class($e);
            $code = $e->getCode();
            $message = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();

            //获取异常回朔
            $trace = $e->getTrace();

            //检查是否被继承，错误异常
            if ($e instanceof ErrorException)
            {
                if (isset(self::$phpErrors[$code]))
                    $code = self::$phpErrors[$code];
                
                //回溯ErrorException修复
                if (version_compare(phpversion(), '5.3', '<'))
                {
                    for ($i = count($trace) - 1; $i > 0; --$i) 
                    {
                        if (isset($trace[$i - 1]['args']))
                        {
                            $trace[$i]['args'] = $trace[$i - 1]['args'];
                            unset($trace[$i - 1]['args']);
                        }
                    }
                } 
            }

            //创建一个异常的纯文字版
            $error = RookieException::message($e);

            //exception log
            if (RookieCore::$config['base']['log'])
                RookieLog::write($error, 'sys');
            
            if (RookieCore::$config['base']['isCli'])
            {
                echo "\n{$error}\n";
                exit(1);
            }

            //确保发送适当的HTTP标头
            if ( ! headers_sent() )
            {
                $httpHeaderStatus = ($e instanceof RookieHttpException) ? $code : 500;
                                
                header('Content-Type: '.RookieException::$errorViewContentType.'; 
                    charset='.RookieCore::$config['base']['charset'], true, $httpHeaderStatus);
            }

            //指定404页
            if (isset(RookieCore::$config['404']))
            {
                if (strstr( $_SERVER['REQUEST_URI'], RookieCore::$config['404']))
                    exit();
                header('location: '.RookieCore::$config['404']);
            }

            ob_get_clean();
            ob_start();

            //如果是ajax
            if (isset($_SERVER['HTTP_REQUEST_TYPE']) && ($_SERVER['HTTP_REQUEST_TYPE'] == 'ajax'))
            {
                echo "\n{$error}\n";
                exit(1);
            }

            //判断debug view 是否存在
            $debugViewFile = dirname(__FILE__).'/../debug/debugView.php';
            if (file_exists( $debugViewFile ))
                require $debugViewFile; 
            else
                echo $error;

            //输出缓冲区的内容
            echo ob_get_clean();
            exit(1);
        }
        catch(Exception $e)
        {
            ob_get_level() && ob_clean();
            echo RookieException::message($e), '\n';
            exit(1);
        }
    } //}}}

    /**
     * set message
     *
     * @param string $message
     * @param array  $variables
     * @return string $message
     */
    public static function setMessage($message, $variables)
    { //{{{
        foreach ($variables as $key => $val)
            $message = str_replace($key, $val, $message);

        return $message;
    } //}}}

    /**
     * 获取用户当前的错误配置
     * 
     * @return void
     */
    public static function getUserReporting()
    { //{{{
        $errorReporting = ini_get('error_reporting');
        for ($i = 0; $i < 15; $i++)
        {
            $errorKey = $errorReporting & pow(2, $i);
            if (! isset(self::$phpErrors[$errorKey])) 
                unset(self::$phpErrors[$errorKey]);
        }
    } //}}}

    /**
     * 重写toString()方法
     *
     * @return string
     */
    public function __toString()
    { //{{{
        return RookieException::message($this);
    } //}}}

    /**
     * 获取单行的文字表示异常
     *
     * @param object Exception
     * @return string
     */
    public static function message(Exception $e)
    { //{{{
        //格式化输出
        return sprintf('%s [ %s ]: %s ~ %s [ %d ]',
            get_class($e), $e->getCode(), strip_tags($e->getMessage()), $e->getFile(), $e->getLine());
    } //}}}

}
