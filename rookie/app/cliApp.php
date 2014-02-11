<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookiecliApp 
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieCliApp 
{
	const STREAM_STD_IN = "STD_IN";
	const STREAM_STD_OUT = "STD_OUT";

	public static $streamStdIn = false;

	public $route = array();

    public function __construct() 
    { //{{{
        //打开一个流只支持读
		self::$streamStdIn = fopen('php://stdin', 'r');
	} //}}}

    public function  __destruct() 
    { //{{{
		fclose(self::$streamStdIn);
	} //}}}

    public function run($args) 
    { //{{{

		$arguments = $this->readArguments($args);

        $_SERVER['HTTP_HOST'] = 'http://localhost/';
        $_SERVER['REQUEST_URI'] = $arguments['r'];
		
        if (isset($arguments['r']) && strpos($arguments['r'], ':') !== false) 
        {
			// Allow user to run specified controller using format
			// php run.php -r=MyController:functionName

			$parts = explode(':', $arguments['r']);
			return $this->runController($parts[0], $parts[1], $arguments);
		}

		
		self::exitApp(1, "\nExiting DooPHP CLI Application");
	} //}}}
	

	/**
	 * 退出程序
     *
	 * @param int $code 退出code 只能从 0 到 255
	 * @param string $message 显示的信息 
	 */
    public static function exitApp($code=0, $message=null) 
    { //{{{
        if ($message !== null) 
			self::writeLine($message);

        if ($code < 0 || $code > 255) 
			$code = 1;

		exit($code);
	} //}}}

	/**
	 * 清空控制台 
	 */
    public static function clearScreen() 
    { ///{{{
		system('clear');
	} //}}}

	/**
	 * 在指定的输出流输出指定的文本 
     * 
	 * @param string $text 
	 * @param string $stream 
	 */
    public static function write($text, $stream='STD_OUT') 
    { //{{{
		echo $text;
	} //}}}

	/**
	 * 在指定的输出流输出指定的文本有换行的 
     *
	 * @param string $text 
	 * @param string $stream 
	 */
    public static function writeLine($text, $stream='STD_OUT') 
    { //{{{
		self::write($text . "\n", $stream);
	} //}}}

	/**
	 * 显示控制台标题 
     *
	 * @param string $title 
	 * @param bool $clearScreen 
	 * @param int $width 
	 */
    public static function displayTitle($title, $clearScreen=true, $width=80, $char='=') 
    { //{{{
		if ($clearScreen) {
			self::clearScreen();
		}
		$lines = str_repeat($char, $width);
		$titlePadding = str_repeat(' ', ($width / 2 - strlen($title) / 2));
		self::writeLine($lines);
		self::writeLine($titlePadding . $title);
		self::writeLine($lines);
	} //}}}

	/**
	 * 运行命命 
	 *
	 * @param string $cmd 
	 * @param string $input 
	 * @return array 
	 */
    public static function runCommandLineTask($command, $input='') 
    { //{{{

		$pipes = null;

		$process = proc_open($command, array(
										0 => array('pipe','r'),
										1 => array('pipe','w'),
										2 => array('pipe','w'))
							, $pipes);

		fwrite($pipes[0], $input);
		fclose($pipes[0]);

		$stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		$rtn = proc_close($process);

		return array(
			'stdout'=>$stdout,
			'stderr'=>$stderr,
			'return'=>$rtn
		);
	} //}}}

	

	/**
     * 运行controller
     * 
	 * @param string $controller 
	 * @param string $action 
	 * @param array $arguments 
	 * @return mixed  
	 */
    private function runController($controller, $action, $arguments) 
    { //{{{
		$controllerFile = WEBPATH."cli/controller/{$controller}.php";

		if (file_exists($controllerFile)) {
			require_once dirname(__FILE__)."/../controller/controllerCli.php";
			require_once($controllerFile);
			$controller = new $controller;

			if (method_exists($controller, $action)) {
				$controller->arguments = $arguments;
				return $controller->$action();
			} else {
				$this->exitApp(1, "Could not find specified action");
			}
		} else {
			$this->exitApp(1, "Could not find specified controller");
		}
	} //}}}

	/**
     * 读取参数
	 * Flags ie. --foo=bar will come out as $out['foo'] = 'bar'
	 * Switches ie. -ab will come out as $out['a'] = true, $out['b'] = true
	 *		  OR IF -a=123 will come out as $out['a'] = 123
	 * Other Args ie. one.txt two.txt will come out as $out[0] = 'one.txt', $out[1] = 'two.txt'
	 *
	 * Function from : http://www.php.net/manual/en/features.commandline.php#93086
	 *
	 * @param array $args The command arguments from PHP's $argv variable
	 * @return array The arguments in a formatted array
	 */
    private function readArguments($args) 
    { //{{{
		array_shift($args); // Remove the file name
		$out = array();
		foreach ($args as $arg){
			if (substr($arg, 0, 2) == '--') {	// Got a 'switch' (ie. --DEBUG_MODE=false OR --verbose)
				$eqPos = strpos($arg, '=');  // Has a value
				if ($eqPos === false){
					$key = substr($arg, 2);
					$out[$key] = isset($out[$key]) ? $out[$key] : true;
				} else {
					$key = substr($arg, 2, $eqPos-2);
					$out[$key] = substr($arg, $eqPos+1);
				}
			} else if (substr($arg, 0, 1) == '-') { // Got an argument (ie. -h OR -cfvr [shorthand for -c -f -v -r] OR -i=123)
				if (substr($arg, 2, 1) == '='){
					$key = substr($arg, 1, 1);
					$out[$key] = substr($arg, 3);
				} else {
					$chars = str_split(substr($arg, 1));
					foreach ($chars as $char){
						$key = $char;
						$out[$key] = isset($out[$key]) ? $out[$key] : true;
					}
				}
			} else {	// Just an argument ie (foo bar me.txt)
				$out[] = $arg;
			}
		}
		return $out;
	} //}}}
	
}

