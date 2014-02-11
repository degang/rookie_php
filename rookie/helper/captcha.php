<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieCaptcha
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieCaptcha 
{
	const REFRESH_GET_VAR='refresh';

	/**
	 * @var integer 验证码字符个数默认3 
	 */
	public $testLimit = 3;

	/**
	 * @var integer 长度 
	 */
	public $width = 240;

	/**
	 * @var integer 高度 
	 */
	public $height = 57;

	/**
	 * @var integer 填充 
	 */
	public $padding = 2;

	/**
	 * @var integer 背景颜色 
	 */
	public $backColor = 0xFFFFFF;

	/**
	 * @var integer 字体颜色 
	 */
	public $foreColor = 0x2040A0;

	/**
	 * @var boolean 是否使用透明背景
	 */
	public $transparent = false;

	/**
	 * @var integer 最小长度 
	 */
	public $minLength = 6;

	/**
	 * @var integer 最大长度 
	 */
	public $maxLength = 7;

	/**
	 * @var integer 在字符之间的偏移量 
	 * @since 1.1.7
	 */
	public $offset = -2;

	/**
	 * @var string 字体路径 
	 */
	public $fontFile;

	/**
	 * @var string 随机生成验证码 
	 * @since 1.1.4
	 */
	public $fixedVerifyCode;

	/**
	 * @var string 扩展gd, imagick 默认是null
	 * @since 1.1.13
	 */
	public $backend;

    private static $_init;

	/**
	 * 运行 
	 */
	public static function code()
	{ //{{{
        if (self::$_init === NULL)
            self::$_init = new RookieCaptcha;

		 return self::$_init->renderImage(self::$_init->getVerifyCode());
	} //}}}

	/**
     * 生成哈希代码，可用于客户端验证
	 * @param string $code 
	 * @return string 
	 * @since 1.1.7
	 */
	public function generateValidationHash($code)
	{ //{{{
		for($h=0,$i=strlen($code)-1;$i>=0;--$i)
			$h+=ord($code[$i]);
		return $h;
	} //}}}

	/**
	 * 获取验证码 
	 * @param boolean $regenerate 
	 * @return string 
	 */
	public function getVerifyCode($regenerate=false)
	{ //{{{
		if($this->fixedVerifyCode !== null)
			return $this->fixedVerifyCode;

        $session = RookieSession::instance();
		$name = $this->getSessionKey();
	    //if($session->get($name) === null || $regenerate)
	   // {
		    $session->set($name, $this->generateVerifyCode());
			//$session->set($name . 'count', 1);
		//}
	    return $session->get($name);
	} //}}}
    

	/**
	 * 输入验证 
	 * @param string $input 
	 * @param boolean $caseSensitive 
	 * @return boolean 
	 */
	public function validate($input,$caseSensitive)
	{ //{{{
		$code = $this->getVerifyCode();
		$valid = $caseSensitive ? ($input === $code) : strcasecmp($input,$code)===0;
		$session = Yii::app()->session;
		$session->open();
		$name = $this->getSessionKey() . 'count';
		$session[$name] = $session[$name] + 1;
		if($session[$name] > $this->testLimit && $this->testLimit > 0)
			$this->getVerifyCode(true);
		return $valid;
	} //}}}

	/**
     * 生成新的验证码
	 * @return string 
	 */
	protected function generateVerifyCode()
	{ //{{{
		if($this->minLength < 3)
			$this->minLength = 3;
		if($this->maxLength > 20)
			$this->maxLength = 20;
		if($this->minLength > $this->maxLength)
			$this->maxLength = $this->minLength;
		$length = mt_rand($this->minLength,$this->maxLength);

		$letters = 'bcdfghjklmnpqrstvwxyz';
		$vowels = 'aeiou';
		$code = '';
		for($i = 0; $i < $length; ++$i)
		{
			if($i % 2 && mt_rand(0,10) > 2 || !($i % 2) && mt_rand(0,10) > 9)
				$code.=$vowels[mt_rand(0,4)];
			else
				$code.=$letters[mt_rand(0,20)];
		}

		return $code;
	} //}}}

	/**
	 * 获取session key 
	 * @return string 
	 */
	protected function getSessionKey()
	{ //{{{
		return 'captcha'.RookieUri::$controller; 
	} //}}}

	/**
     * 显示图片
	 * @param string $code 
	 */
	protected function renderImage($code)
	{ //{{{
        if($this->backend===null && RookieCaptcha::checkRequirements('imagick') 
            || $this->backend==='imagick')
			$this->renderImageImagick($code);
		else if($this->backend===null && RookieCaptcha::checkRequirements('gd') || $this->backend==='gd')
			$this->renderImageGD($code);
	} //}}}

	/**
	 * 使用gd库，显示图片 
	 * @param string $code 
	 * @since 1.1.13
	 */
	protected function renderImageGD($code)
	{ //{{{
		$image = imagecreatetruecolor($this->width,$this->height);

		$backColor = imagecolorallocate($image,
				(int)($this->backColor % 0x1000000 / 0x10000),
				(int)($this->backColor % 0x10000 / 0x100),
				$this->backColor % 0x100);
		imagefilledrectangle($image,0,0,$this->width,$this->height,$backColor);
		imagecolordeallocate($image,$backColor);

		if($this->transparent)
			imagecolortransparent($image,$backColor);

		$foreColor = imagecolorallocate($image,
				(int)($this->foreColor % 0x1000000 / 0x10000),
				(int)($this->foreColor % 0x10000 / 0x100),
				$this->foreColor % 0x100);

		if($this->fontFile === null)
			$this->fontFile = dirname(__FILE__) . '/captcha/Duality.ttf';

		$length = strlen($code);
		$box = imagettfbbox(30,0,$this->fontFile,$code);
		$w = $box[4] - $box[0] + $this->offset * ($length - 1);
		$h = $box[1] - $box[5];
		$scale = min(($this->width - $this->padding * 2) / $w,($this->height - $this->padding * 2) / $h);
		$x = 10;
		$y = round($this->height * 27 / 40);
		for($i = 0; $i < $length; ++$i)
		{
			$fontSize = (int)(rand(26,32) * $scale * 0.8);
			$angle = rand(-10,10);
			$letter = $code[$i];
			$box = imagettftext($image,$fontSize,$angle,$x,$y,$foreColor,$this->fontFile,$letter);
			$x = $box[2] + $this->offset;
		}

		imagecolordeallocate($image,$foreColor);

		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Transfer-Encoding: binary');
		header("Content-type: image/png");
		imagepng($image);
		imagedestroy($image);
	} //}}}


	/**
	 * 显示ImageMagick库，显示图片
	 * @param string $code 
	 * @since 1.1.13
	 */
	protected function renderImageImagick($code)
	{ //{{{
		$backColor=new ImagickPixel('#'.dechex($this->backColor));
		$foreColor=new ImagickPixel('#'.dechex($this->foreColor));

		$image=new Imagick();
		$image->newImage($this->width,$this->height,$backColor);

		if($this->fontFile===null)
			$this->fontFile=dirname(__FILE__).'/captcha/Duality.ttf';

		$draw=new ImagickDraw();
		$draw->setFont($this->fontFile);
		$draw->setFontSize(30);
		$fontMetrics=$image->queryFontMetrics($draw,$code);

		$length=strlen($code);
		$w=(int)($fontMetrics['textWidth'])-8+$this->offset*($length-1);
		$h=(int)($fontMetrics['textHeight'])-8;
		$scale=min(($this->width-$this->padding*2)/$w,($this->height-$this->padding*2)/$h);
		$x=10;
		$y=round($this->height*27/40);
		for($i=0; $i<$length; ++$i)
		{
			$draw=new ImagickDraw();
			$draw->setFont($this->fontFile);
			$draw->setFontSize((int)(rand(26,32)*$scale*0.8));
			$draw->setFillColor($foreColor);
			$image->annotateImage($draw,$x,$y,rand(-10,10),$code[$i]);
			$fontMetrics=$image->queryFontMetrics($draw,$code[$i]);
			$x+=(int)($fontMetrics['textWidth'])+$this->offset;
		}

		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Transfer-Encoding: binary');
		header("Content-type: image/png");
		$image->setImageFormat('png');
		echo $image;
	} //}}}
    
	/**
     * 检查图片扩展gd,imagick
	 * @param string extension 
	 * @return boolean true 
	 * @since 1.1.5
	 */
	public static function checkRequirements($extension=null)
	{ //{{{
		if(extension_loaded('imagick'))
		{
			$imagick=new Imagick();
			$imagickFormats=$imagick->queryFormats('PNG');
		}
		if(extension_loaded('gd'))
		{
			$gdInfo=gd_info();
		}
		if($extension===null)
		{
			if(isset($imagickFormats) && in_array('PNG',$imagickFormats))
				return true;
			if(isset($gdInfo) && $gdInfo['FreeType Support'])
				return true;
		}
		elseif($extension=='imagick' && isset($imagickFormats) && in_array('PNG',$imagickFormats))
			return true;
		elseif($extension=='gd' && isset($gdInfo) && $gdInfo['FreeType Support'])
			return true;
		return false;
	} //}}}
}
