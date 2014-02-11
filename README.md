rookie_php
==========

RookiePhp 是一个基于组件的高性能 PHP 框架，用于快速开发大型 Web 应用。它使Web开发中的 可复用度最大化，可以显著提高你的Web应用开发速度。

基本配置

创建config目录

创建main.php配置文件内容可以自定义

举个栗子

return array(

    //基本配置文件
	'base' => array(
		'isDebug'	     => false,
		'exception'	     => false,
        'charset'        => 'utf-8',
        'isCli'          => false,
        'shutdownErrors' => array( E_ERROR,  E_USER_ERROR ),
        'safeMode'       => false,
        'log'            => true,
        'logPath'        => dirname(__FILE__).'/../../cache/log/',
        'isRunStatic'    => false,
        'viewDefault'    => 'default',
        'siteTitle'      => 'www.xxx.com',
        'userUploadTmp'  => '/tmp/www/user_img/', 
        'userPostUploadTmp' => '/tmp/www/post_img/',
        'searchIni'      => dirname(__FILE__).'/search.ini',
	),
    
    //路由控制
    'route' => include 'route.php',
    
    //加密
    'mcrypt' => array(
        'key' => 'bcb04b7e103a0cd8b54763051cef08bc55abe029fdebae5e1d417e2ffb2a00a3',
        'mode'  => MCRYPT_MODE_CBC, //ecb, cbc, cfb, ofb, nofb, stream 
        'cipher' => MCRYPT_RIJNDAEL_128,
    ),
    
    'image' => array(
        'thumbEnable' => true,  //是否启用缩略图
        'watermarkEnable' => true, //是否开启水印
        'watermarkImg'  => '', //水印图片地址
        'watermarkPos' => '4', //水印图片位置
        'watermarkMinWidth' => '30',    //最小宽度
        'watermarkMinHeight' => '20', //最小高度
        'watermarkQuality'  => '80', //图片质量
        'watermarkPct'      => '70', //水印透明度
        
    ),

    //404页面
    '404'   => 'http://www.xxx.com/error',

    //自动加载自定义类
    'import' => array(
        'web.components.*',
        'web.components.test',
    ),
    
      'mongoGridFS' => array(
        'className' => 'RookieMongodb',
        'servers' => array(
            array(
                'host' => 'xxx.xxx.xxx.xxx',
                'port' => xxxx,
                'timeout' => 0,
                'db' => 'file',
                'user' => 'test',
                'pass' => 'xxx',
                'auth' => false,
            ),    
        ),    
    ),
)

2.接下来创建各种目录
举个栗子
mkdir api cache cli components controller model modules statics view  webroot  widget

3.接下来是入口文件index.php
header("X-Powered-By:rookie.com");
header('Content-type: text/html; charset=utf-8');

!version_compare( PHP_VERSION, "5.0" ) && 
 	exit( "To make things right, you must install PHP5" );

$config = include '../config'.DS.ENVIRONMENT.DS.'main.php';
include '../sys'.DS.'Rookie.php';
RookieCore::run( $config );
  
RookieUri::$route = $config['route'];

RookieUri::run();

END
lehu8_com@163.com
