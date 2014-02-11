<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * Rookie核心类
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieCore
{
	
	/**
	 * @var array $config 网站配置文件 
	 */
	public static $config;
	
    /**
     * @var object $cache
     */
    public static $cache;

	/**
	 * @var object $_init 初始化
	 */
	private static $_init;
	

    /**
     * @var object $_app
     */
    private static $_app;

    /**
     * @var array $_paths 文件路径
     */
    private static $_paths = array();

	/**
	 * 框架初始化
	 * @param array $config 配置文件 
	 * @return mixed
	 */
	public static function run($config)
	{ //{{{
		if (self::$_init)
			return ;
		
		self::$_init = true;
		
		self::$config = $config;
		
		//是否错误
		if (ENVIRONMENT == 'dev')	
		{
			ini_set('display_errors' , 'On');
			error_reporting(E_ALL | E_STRICT);
		}
		else
			error_reporting(0x00);

        header('Content-type: text/html; charset='.self::$config['base']['charset']);     

        //加载系统类
        self::loadSysClass();

		//自动注册机
		spl_autoload_register(array('RookieCore', 'autoLoad'));
		ini_set('unserialize_callback_func', 'spl_autoload_call');
		
		//是否开启异常处理
		if (self::$config['base']['exception'])
		{
			//加入异常及错误信息处理类
			set_exception_handler(array('RookieException', 'handler'));
			set_error_handler(array('RookieDebug', 'errorHandler'));			
		}

		// 启用rookie的关机处理程序，它捕获E_FATAL的错误。
		register_shutdown_function(array('RookieCore', 'shutdownHandler'));

		//register_globals过滤
		ini_get('register_globals') && RookieCore::globals();

		//如果在运行的命令行环境
		self::$config['base']['isCli'] = (PHP_SAPI === 'cli');

		// 确定是否在安全模式下运行
		self::$config['base']['safeMode'] = (bool) ini_get( 'safe_mode' );
		
		//设置程序运行时间 
		(function_exists("set_time_limit") == TRUE AND @ini_get("safe_mode") == 0) &&
			@set_time_limit(10);
	
		// MB扩展编码设置相同的字符集
        function_exists('mb_internal_encoding') && 
            mb_internal_encoding(self::$config['base']['charset']);

		//加载用户自定义类
		self::import();

        //过滤
        $_GET      = self::filterParam($_GET);
        $_POST     = self::filterParam($_POST);
        $_REQUEST  = self::filterParam($_REQUEST);
        $_COOKIE   = self::filterParam($_COOKIE);
        $_SERVER   = self::filterParam($_SERVER);
		
	} //}}}
	
    /**
     * auto load class	
     *
     * $param string $className
     * @return mixed
     */
	public static function autoLoad( $className )
	{ //{{{
		$fileName = strtolower(str_replace("Rookie", '', $className));
		$classNew = $className;
		try 
		{
            if ($path = self::findFile($fileName.DS.$fileName, $className))
                $path && require $path;
            elseif ($path = self::findFile('helper'.DS.$fileName, $className))
                $path && require $path;
            else
                return true;
		}
		catch (Exception $e)
		{
			exit(RookieException::handler($e));
		}
		
	} //}}} 

	/**
	 * 查询文件是否存在
	 * @param String $path
	 * @param String $className
	 * @param String $realPath
	 */
	public static function findFile($path, $className, $ext = '.php')
	{ //{{{
		$className = strtolower( $className );
		$realPath = dirname( __FILE__ ).DS.$path.$ext;

        if ( in_array($realPath, self::$_paths))
            return false;
        else
        {
            if (file_exists($realPath))
            {
                self::$_paths[] = $realPath;
                return $realPath;
            }
        }
        return false;
	} //}}}
	
	/**
	 * 捕获没有陷入错误处理程序，如E_PARSE的的错误。
	 *
	 * @uses    RookieException::handler
	 * @return  void
	 */
	public static function shutdownHandler()
	{ //{{{
		if ( ! self::$_init)
			return;

        if ($error = error_get_last() AND in_array($error['type'], 
            self::$config['base']['shutdownErrors']))
		{
			// 清洁的输出缓冲区
			ob_get_level() and ob_clean();

			RookieException::handler(new ErrorException($error['message'], $error['type'], 0, 
				$error['file'], $error['line']));

			exit(1);
		}
	} //}}}

    /**
     * load sys class
     *
     * @return mixed
     */
    public static function loadSysClass()
    { //{{{
        $sysClass = array(
            'exception'     => 'exception/exception',
            'httpException' => 'exception/httpException',
            'debug'         => 'debug/debug',
            'log'           => 'log/log',
            'appModule'     => 'app/module',
            'uri'           => 'uri/uri',
            'uriRouter'     => 'uri/uriRouter',
            'session'       => 'session/session',
            'sessionMem'    => 'session/sessionMem',
            'sessionNat'    => 'session/sessionNative',
            'controller'    => 'controller/controller',
            'mongo'         => 'mongodb/mongo',
            'server'        => 'mongodb/server',
            'query'         => 'mongodb/query',
            'collection'    => 'mongodb/collection',
            'mongodb'       => 'mongodb/mongodb',
            'model'         => 'mongodb/model',
            'validator'     => 'helper/validator',
            'widget'        => 'helper/widget',
        );

        if (self::$config['base']['isCli'])
            $sysClass['controllerCli'] = 'controller/controllerCli.php';

        foreach ($sysClass as $className => $filePath)
        {
            $file = self::findFile($filePath, $className);
            $file && require $file; 
        }
        return ;
    } //}}}

    /**
     * 安全过滤
     *
     * @return void 
     */
    public static function globals()
    { //{{{
        if (isset($_REQUEST['GLOBALS']) OR isset($_FILES['GLOBALS']))
        {
            // 防止恶意GLOBALS过载攻击
            echo "Global variable overload attack detected! Request aborted.\n";
            exit(1);
        }

        // 获取所有的全局变量名
        $globalVariables = array_keys($GLOBALS);

        // 从列表中删除标准的全局变量
        $globalVariables = array_diff($globalVariables, array(
            '_COOKIE',
            '_ENV',
            '_GET',
            '_FILES',
            '_POST',
            '_REQUEST',
            '_SERVER',
            '_SESSION',
            'GLOBALS',
        ));

        // 取消设置的全局变量，有效register_globals的关闭
        foreach ($globalVariables as $name)
            unset($GLOBALS[$name]);  

    } //}}}

    /**
     * 过滤数组 
     *
     * @param string $param 
     * @param boolean $filter 
     * @return mixed
     */
    public static function filterParam($param, $filter = true) 
    { //{{{
        if (!is_array($param) && !is_object($param)) 
        {
            $param = stripslashes($param);
            return $filter ? htmlspecialchars(trim($param)) : $param;
        }
        foreach ($param as $key => $value) 
            $param[$key] = self::filterParam($value, $filter);
        return $param;
    } //}}}

    /**
     * load user class
     *
     * @param mixed $path 
     * @return void 
     */
    public static function import($path = '')
    { //{{{
        if ($path && !is_array($path))
            $path = array($path);

        if (isset(self::$config['import']))
        {
            foreach (self::$config['import'] as $key => $val)
            {
                $classPath = ltrim($val, 'web.');
                $classPathArr = explode('.', $classPath);
                $classPath = WEBPATH.str_replace('.', DS, $classPath);
                if ($classPathArr[count($classPathArr)-1] == '*')
                {   
                    $classPath = rtrim($classPath, '*'); 
                    if ( is_dir($classPath))
                    {
                        $dir = dir($classPath);
                        while (($file = $dir->read()) !== false)
                        {
                            if ($file == '.' || $file == '..' || is_dir($classPath.$file)) continue;
                            require $classPath.$file;
                        }
                        $dir->close();
                    }
                }
                else
                    file_exists($classPath.'.php') && 
                        require $classPath.'.php';
            }
        }
        return;
    } //}}}

    /**
     * load model
     *
     * @param string $className
     * @return object 
     */
    public static function loadModel($className, $modules = 0)
    { //{{{
        $modules = $modules ? RookieUri::$modules : '';
        $classPath = WEBPATH.$modules.DS.'model'.DS.$className.'.php';
        if (isset(self::$_paths[$className]))
            return self::$_paths[$className];
        
        if (file_exists($classPath))
        {
            require $classPath;
            self::$_paths[$className] = new $className;
            return self::$_paths[$className];
        }
    } //}}}

    /**
     * load helper class
     *
     * @param string $name
     * @return boolean 
     */
    public static function loadHelper($name)
    { //{{{
        $helperPath = dirname(__FILE__).DS.'helper'.DS.$name.'.php';
        if (file_exists($helperPath))
            require $helperPath;
        else
            return false;
    } //}}}

    /**
     * widget 
     *
     * @param string $widgetName
     * @param array  $param
     * @return mixed
     */
    public static function widget($widgetName, $param = array())
    { //{{{
        RookieWidget::init($widgetName, $param);
    } //}}}    

    /**
     * 加载应用程序 cliApp
     *
     * @param string $className
     * @param integer $type 
     * return mixed 
     */
    public static function app($className, $type = 1)
    { //{{{
        self::run(self::$config);
        if ($type === 1)
        {
            if (isset(self::$_app[md5($className)]) && !empty(self::$_app[md5($className)]))
                return self::$_app[md5($className)];
            $moduleRunner = new RookieModuleRunner();
            self::$_app[md5($className)] = $moduleRunner->init(self::$config[$className]);
            return self::$_app[md5($className)]; 
        }
        else
        {
            if (self::$_app == null)
            {
                require dirname(__FILE__).'/app/'.$className.'.php';
                $className = 'Rookie'.ucfirst($className);
                self::$_app = new $className;
            }
            return self::$_app;
        }
    } //}}}
}
