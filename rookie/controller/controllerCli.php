<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieControllerCli
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieControllerCli {

	const STREAM_STD_IN = "STD_IN";
	const STREAM_STD_OUT = "STD_OUT";

	/**
	 * 注释行参数 
	 */
	public $arguments = array();


	/**
	 * 显示标题 
     *
	 * @param string $title 
	 * @param bool $clearScreen 
	 * @param int $width 
	 */
    protected function displayTitle($title, $clearScreen=true, $width=80, $char='=') 
    { //{{{
		if ($clearScreen) {
			$this->clearScreen();
		}
		$lines = str_repeat($char, $width);
		$titlePadding = str_repeat(' ', ($width / 2 - strlen($title) / 2));
		$this->writeLine($lines);
		$this->writeLine($titlePadding . $title);
		$this->writeLine($lines);
	} //}}}

	/**
	 * 显示菜单  
	 * $options['h'] = "View Help";
	 *
	 * @param string $question 
	 * @param array $options 
	 * @param bool $useNumericalValueSelection 
	 * @param bool $clearScreenBeforeDisplay 
	 * @param bool $clearScreenAfterDisplay 
	 * @return mixed The users choice (a key in the options array)
	 */
	protected function displayMenu($question, $options, $useNumericalValueSelection = false,
					 $clearScreenBeforeDisplay = false,
                     $clearScreenAfterDisplay = false) 
    { //{{{

		if ($clearScreenBeforeDisplay) {
			$this->clearScreen();
		}

		// If we are displaying numerical option keys convert options and store the old keys for later
		$origKeys = array();
		if ($useNumericalValueSelection) {
			$i=0;
			$temp = array();
			foreach($options as $key=>$value) {
				$temp[$i] = $value;
				$origKeys[$i] = $key;
				$i++;
			}
			$options = $temp;
		}
		
		// Display the menu options
		$this->writeLine($question);
		foreach($options as $key=>$answer) {
			$this->writeLine("   {$key}) {$answer}");
		}
		$this->write("Please select an option: ");

		// Get the users choice
		$choice = '';
		$commands = array_keys($options);
		do {
			if ($choice != '')
				$this->write("\nUnknown Option. Please select another option: ");
			$choice = trim(fgets(RookieCliApp::$streamStdIn));
		} while ($choice == '' || !in_array($choice, $commands));

		if ($clearScreenAfterDisplay) {
			$this->clearScreen();
		}

		// If we gave numerical options convert it back to actual key
		if ($useNumericalValueSelection) {
			return $origKeys[$choice];
		} else {
			return $choice;
		}
	} //}}}

	protected function displayMultiSelectMenu($question, $options,
					 $clearScreenBeforeDisplay = false,
                     $clearScreenAfterDisplay = false) 
    { //{{{ 
		if ($clearScreenBeforeDisplay) {
			$this->clearScreen();
		}

		// If we are displaying numerical option keys convert options and store the old keys for later
		$origKeys = array();

		// We let user make multiple selections using numerical references
		$i=0;
		$temp = array();
		foreach($options as $key=>$value) {
			$temp[$i] = $value;
			$origKeys[$i] = $key;
			$i++;
		}
		$options = $temp;


		// Display the menu options
		$this->writeLine($question);
		foreach($options as $key=>$answer) {
			$this->writeLine("   {$key}) {$answer}");
		}
		$this->write("Please select an option(s): ");

		// Get the users choice
		$choices = array();
		$allPass = false;
		$commands = array_keys($options);
		do {
			if (!empty($choices))
				$this->write("\nUnknown or Invalid Option(s). Please select another option(s): ");
			$choices = explode(',', trim(fgets(RookieCliApp::$streamStdIn)));
			
			$allPass = true;
			foreach($choices as $choice) {
				$allPass = $allPass && in_array($choice, $commands);
			}
		} while (empty($choices) || $allPass == false);

		if ($clearScreenAfterDisplay) {
			$this->clearScreen();
		}

		$selection = array();
		foreach($choices as $choice) {
			$selection[] = $origKeys[$choice];
		}

		return $selection;
	} //}}}

    protected function getConfirmationToContinue($message="Are you sure you want to continue?") 
    { //{{{
		return $this->displayMenu($message, array(
			'y' => "Yes - Continue...",
			'n' => "No - Cancel what I'm doing..."
		));
	} //}}}

	/**
     * 向用户提出疑问,并获得响应。如果默认是不假的,问题是可选的，用户可以使用预的响应  
	 *
	 * @param string $question 
	 * @param string $default 
	 */
    protected function askQuestion($question, $default=false) 
    { //{{{

		if ($default === false) {
			$this->writeLine("{$question}:");
		} else {
			$this->writeLine($question);
			$this->writeLine("OR <enter> to use default ({$default}):");
		}

		while(true) {
			$choice = trim(fgets(RookieCliApp::$streamStdIn));
			if ($choice == '' && $default !== false) {
				return $default;
			} elseif ($choice != '') {
				return $choice;
			} else {
				$this->writeLine("You must enter a value. Please try again:");
			}
		}
	} //}}}

	/**
	 * 暂停执行，等待用户按回车 
	 * 
	 * @param string $message 
	 * @param bool $clearScreenAfter 
	 */
    protected function pause($message='Please press <enter> to continue', $clearScreenAfter = true) 
    { ///{{{
		$this->writeLine($message);
		fgets(RookieCliApp::$streamStdIn);
		if ($clearScreenAfter) {
			$this->clearScreen();
		}
	} //}}}

	/**
	 * 清除控制台 
	 */
    protected function clearScreen() 
    { //{{{
		RookieCliApp::clearScreen();
	} //}}}

	/**
	 * 退出程序 
     *
	 * @param int $code code  0 到 255
	 * @param string $message 
	 */
    protected function exitApp($code=0, $message=null) 
    { //{{{
		RookieCliApp::exitApp($code, $message);
	} //}}}

	/**
	 * 指定的输出流输出指定的文本  
     *
	 * @param string $text 
	 * @param string 
	 */
    protected function write($text='', $stream='STD_OUT') 
    { //{{{
		RookieCliApp::write($text, $stream);
	} //}}}

	/**
	 * 输出指定的字符串，并在前面加上一个新行结束  
     *
	 * @param string $text 
	 * @param string 
	 */
    protected function writeLine($text='', $stream='STD_OUT') 
    { //{{{
		RookieCliApp::writeLine($text, $stream);
	} //}}}

	/**
	 * 运行命令行任务 
	 *
	 * @param string $cmd 
	 * @param string $input 
	 * @return array 
	 */
    public function runCommandLineTask($command, $input='') 
    { //{{{
		RookieCliApp::runCommandLineTask($command, $input);
	} //}}}

}
