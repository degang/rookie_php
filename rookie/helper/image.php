<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieImage
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieImage 
{
    public $w_pct = 100;

    public $w_quality = 80;

    public $w_minwidth = 300;

    public $w_minheight = 300;

    public $thumb_enable;

    public $w_img;

    public $watermark_enable;

    public $interlace = 0;

    public static $_init;
    
    public $image;

    public $type;

    public static function init() 
    { //{{{
        if (! extension_loaded('gd')) 
            throw new RookieException('gd extension is not installed');

        if (self::$_init === NULL)
            self::$_init = new RookieImage; 
        self::$_init->setConfig();
        return self::$_init;
    } //}}}

    /**
     * set config
     *
     * @return mixed
     */
    public function setConfig()
    { //{{{
        $imageConfig = RookieCore::$config['image'];
        $this->thumb_enable = $imageConfig['thumbEnable'];
        $this->watermark_enable = $imageConfig['watermarkEnable'];
        $this->w_img = $imageConfig['watermarkImg'];

        //设置水印
        $this->set($imageConfig['watermarkImg'], $imageConfig['watermarkPos'],
            $imageConfig['watermarkMinWidth'], $imageConfig['watermarkMinHeight'],
            $imageConfig['watermarkQuality'], $imageConfig['watermarkPct']
        );
    } //}}}

    /**
     * 设置水印图片
     */
    public function set($w_img, $w_pos, $w_minwidth = 300, $w_minheight = 300, 
        $w_quality = 80, $w_pct = 100) 
    { //{{{
        $this->w_img = $w_img;
        $this->w_pos = $w_pos;
        $this->w_minwidth = $w_minwidth;
        $this->w_minheight = $w_minheight;
        $this->w_quality = $w_quality;
        $this->w_pct = $w_pct;
    } ///}}}

    /**
     * 获取图片信息
     *
     * @param string $img
     * @return array $info
     */
    public function info($img) 
    { //{{{
        $imageinfo = getimagesize($img);
        if($imageinfo === false) return false;
        $imagetype = strtolower(substr(image_type_to_extension($imageinfo[2]),1));
        if (!file_exists($img))
            throw new RookieException('Picture does not exist');
        $imagesize = filesize($img);
        $info = array(
                'width'=>$imageinfo[0],
                'height'=>$imageinfo[1],
                'type'=>$imagetype,
                'size'=>$imagesize,
                'mime'=>$imageinfo['mime']
                );
        return $info;
    } //}}}
    
    /**
     * 比例缩放
     */
    public function getpercent($srcwidth,$srcheight,$dstw,$dsth) 
    { //{{{
        if (empty($srcwidth) || empty($srcheight) || ($srcwidth <= $dstw && $srcheight <= $dsth)) $w = $srcwidth ;$h = $srcheight;
        if ((empty($dstw) || $dstw == 0)  && $dsth > 0 && $srcheight > $dsth) {
            $h = $dsth;
            $w = round($dsth / $srcheight * $srcwidth);
        } elseif ((empty($dsth) || $dsth == 0) && $dstw > 0 && $srcwidth > $dstw) {
            $w = $dstw;
            $h = round($dstw / $srcwidth * $srcheight);
        } elseif ($dstw > 0 && $dsth > 0) {
            if (($srcwidth / $dstw) < ($srcheight / $dsth)) {
                    $w = round($dsth / $srcheight * $srcwidth);
                    $h = $dsth;
            } elseif (($srcwidth / $dstw) > ($srcheight / $dsth)) {
                    $w = $dstw;
                    $h = round($dstw / $srcwidth * $srcheight );
            } else {
                $h = $dstw;
                $w = $dsth;
            }
        }
        $array['w']  = $w;
        $array['h']  = $h;
        return $array;
    } //}}} 

    /**
     * 缩略图
     *
     * @param string $image         图片地址
     * @param string $filename      文件名
     * @param integer maxwdith 
     * @param integer maxheight
     * @param string $suffix        后缀名
     * @param integer $autocut
     * @param integer $ftp
     * @return mixed
     */
    public function thumb($image, $filename = '', $maxwidth = 0, $maxheight = 0, 
        $suffix='', $autocut = 0, $ftp = 0) 
    { //{{{
        if(!$this->check($image)) return false;
        $info  = $this->info($image);
        if($info === false) return false;
        $srcwidth  = $info['width'];
        $srcheight = $info['height'];
        $pathinfo = pathinfo($image);
        $type =  $pathinfo['extension'];
        if(!$type) $type = $info['type'];
        $type = strtolower($type);
        unset($info);

        $creat_arr = $this->getpercent($srcwidth,$srcheight,$maxwidth,$maxheight);
        $createwidth = $width = $creat_arr['w'];
        $createheight = $height = $creat_arr['h'];

        $psrc_x = $psrc_y = 0;
        if($autocut && $maxwidth > 0 && $maxheight > 0) {
            if($maxwidth/$maxheight<$srcwidth/$srcheight && $maxheight>=$height) {
                $width = $maxheight/$height*$width;
                $height = $maxheight;
            }elseif($maxwidth/$maxheight>$srcwidth/$srcheight && $maxwidth>=$width) {
                $height = $maxwidth/$width*$height;
                $width = $maxwidth;
            }
            $createwidth = $maxwidth;
            $createheight = $maxheight;
        }
        $createfun = 'imagecreatefrom'.($type=='jpg' ? 'jpeg' : $type);
        $srcimg = $createfun($image);
        if($type != 'gif' && function_exists('imagecreatetruecolor'))
            $thumbimg = imagecreatetruecolor($createwidth, $createheight); 
        else
            $thumbimg = imagecreate($width, $height); 

        if(function_exists('imagecopyresampled'))
        {
            imagecopyresampled($thumbimg, $srcimg, 0, 0, $psrc_x, $psrc_y, $width, $height, $srcwidth, $srcheight); 
        }
        else
            imagecopyresized($thumbimg, $srcimg, 0, 0, $psrc_x, $psrc_y, $width, $height,  $srcwidth, $srcheight); 
        if($type=='gif' || $type=='png') {
            //$background_color  =  imagecolorallocate($thumbimg,  0, 255, 0);  //  指派一个绿色  
            //imagecolortransparent($thumbimg, $background_color);  //  设置为透明色，若注释掉该行则输出绿色的图 
        }
        if($type=='jpg' || $type=='jpeg') imageinterlace($thumbimg, $this->interlace);
        $imagefun = 'image'.($type=='jpg' ? 'jpeg' : $type);
        if(empty($filename)) $filename  = substr($image, 0, strrpos($image, '.')).$suffix.'.'.$type;
        touch($filename);
        if ( !is_writable($filename))
            throw new RookieException('File is not writable:'.$filename);
        $imagefun($thumbimg, $filename);
        imagedestroy($thumbimg);
        imagedestroy($srcimg);
        if($ftp) {
            @unlink($image);
        }
        return $filename;
    } //}}}

    /**
     * 水印
     * @param string $source 源文件
     * @param string $target 保存的文件地址
     * @param string $w_pos 参数1-10
     * @param string 水印图片地址
     * @param string $w_text
     * @param string $w_font
     * @param string $w_color
     * @return mixed
     */
    public function watermark($source, $target = '', $w_pos = '', $w_img = '', 
        $w_text = 'lehu8.com',$w_font = 8, $w_color = 'FFFFFF') 
    { //{{{
        $w_pos = $w_pos ? $w_pos : $this->w_pos;
        $w_img = $w_img ? $w_img : $this->w_img;
        if(!$this->watermark_enable || !$this->check($source)) return false;
        if(!$target) $target = $source;
        $source_info = getimagesize($source);
        $source_w    = $source_info[0];
        $source_h    = $source_info[1];     
        if($source_w < $this->w_minwidth || $source_h < $this->w_minheight) return false;
        switch($source_info[2]) {
            case 1 :
                $source_img = imagecreatefromgif($source);
                break;
            case 2 :
                $source_img = imagecreatefromjpeg($source);
                break;
            case 3 :
                $source_img = imagecreatefrompng($source);
                break;
            default :
                return false;
        }
        if(!empty($w_img) && file_exists($w_img)) {
            $ifwaterimage = 1;
            $water_info   = getimagesize($w_img);
            $width        = $water_info[0];
            $height       = $water_info[1];
            switch($water_info[2]) {
                case 1 :
                    $water_img = imagecreatefromgif($w_img);
                    break;
                case 2 :
                    $water_img = imagecreatefromjpeg($w_img);
                    break;
                case 3 :
                    $water_img = imagecreatefrompng($w_img);
                    break;
                default :
                    return;
            }
        } else {        
            $ifwaterimage = 0;
            $temp = imagettfbbox(ceil($w_font*2.5), 0, 
                dirname(__FILE__).'/captcha/Duality.ttf', $w_text);
            $width = $temp[2] - $temp[6];
            $height = $temp[3] - $temp[7];
            unset($temp);
        }
        switch($w_pos) {
            case 1:
                $wx = 5;
                $wy = 5;
                break;
            case 2:
                $wx = ($source_w - $width) / 2;
                $wy = 0;
                break;
            case 3:
                $wx = $source_w - $width;
                $wy = 0;
                break;
            case 4:
                $wx = 0;
                $wy = ($source_h - $height) / 2;
                break;
            case 5:
                $wx = ($source_w - $width) / 2;
                $wy = ($source_h - $height) / 2;
                break;
            case 6:
                $wx = $source_w - $width;
                $wy = ($source_h - $height) / 2;
                break;
            case 7:
                $wx = 0;
                $wy = $source_h - $height;
                break;
            case 8:
                $wx = ($source_w - $width) / 2;
                $wy = $source_h - $height;
                break;
            case 9:
                $wx = $source_w - $width;
                $wy = $source_h - $height;
                break;
            case 10:
                $wx = rand(0,($source_w - $width));
                $wy = rand(0,($source_h - $height));
                break;              
            default:
                $wx = rand(0,($source_w - $width));
                $wy = rand(0,($source_h - $height));
                break;
        }
        if($ifwaterimage) {
            if($water_info[2] == 3) {
                imagecopy($source_img, $water_img, $wx, $wy, 0, 0, $width, $height);
            } else {
                imagecopymerge($source_img, $water_img, $wx, $wy, 0, 0, $width, $height, $this->w_pct);
            }
        } else {
               # $r = hexdec(substr($w_color,1,2));
               # $g = hexdec(substr($w_color,3,2));
               # $b = hexdec(substr($w_color,5));
            $r = 255; $g = 255; $b = 255;

            $foreColor = 0x2040A0;
	        $foreColor = imagecolorallocate($source_img, 255, 255, 255);

			$fontFile = dirname(__FILE__) . '/captcha/Duality.ttf';
			imagettftext($source_img,20,0,$wx-40,$wy,$foreColor,$fontFile,$w_text);
            //imagestring($source_img,$w_font,$wx,$wy,$w_text,imagecolorallocate($source_img,$r,$g,$b));
        }
        
        switch($source_info[2]) {
            case 1 :
                imagegif($source_img, $target);
                break;
            case 2 :
                imagejpeg($source_img, $target, $this->w_quality);
                break;
            case 3 :
                imagepng($source_img, $target);
                break;
            default :
                return;
        }

        if(isset($water_info)) {
            unset($water_info);
        }
        if(isset($water_img)) {
            imagedestroy($water_img);
        }
        unset($source_info);
        imagedestroy($source_img);
        return true;
    } //}}}

    public function createTextImagickDraw($fontSize=12, $fillColor='', $underColor='', $font='')
    { //{{{
        $font = dirname(__FILE__) . '/captcha/Duality.ttf';
        $draw = new ImagickDraw();
        $draw->setFont($font);
        $draw->setFontSize($fontSize);
        //$draw->setGravity(Imagick::GRAVITY_SOUTHEAST);//设置水印位置
        if(!empty($underColor)) $draw->setTextUnderColor(new ImagickPixel($underColor));
        if(!empty($fillColor)) $draw->setFillColor(new ImagickPixel($fillColor));
        return $draw;
    } //}}}

    public function createWaterImagickDraw($waterImg='',$x=10,$y=85,$width=16,$height=16)
    { //{{{
        $water = new Imagick($waterImg);
        //$second->setImageOpacity (0.4);//设置透明度
        $draw = new ImagickDraw();
        //$draw->setGravity(Imagick::GRAVITY_CENTER);//设置位置
        $draw->composite($water->getImageCompose(), $x, $y, $width, $height,$water);
        return $draw;
    } //}}}

    /**
     * gif缩略图
     * 
     * @param string $imagePath 
     * @param string $outImagePath
     * @return mixed 
     */
    public function gifThumb($imagePath, $outImagePath)
    { //{{{
        set_time_limit(0);
        $image = new Imagick($imagePath);
        $animation = new Imagick();
        $animation->setFormat( "gif" );
        $image = $image->coalesceImages();
        $unitl = $image->getNumberImages();

        $source_info = getimagesize($imagePath);
        $source_w    = $source_info[0];
        $source_h    = $source_info[1];     
        $x = $source_w - 70;
        $y = $source_h - 5;
        for ($i=0; $i<$unitl; $i++) 
        {
            //$image->setImageIndex($i);
            $image->setIteratorIndex($i);
            $thisimage = new Imagick();
            $thisimage->readImageBlob($image);
            $delay = $thisimage->getImageDelay();
            $thisimage->annotateImage($this->createTextImagickDraw(14, 'white'), $x, $y, 0, 'lehu8.com');
            $animation->addImage($thisimage);
            $animation->setImageDelay( $delay );
        }

        $animation->setImageCompressionQuality(97);
        $animation->writeImages($outImagePath, true);
    } //}}}

    /**
     * 检查图片
     *
     * @param string $image
     * @return mixed
     */
    public function check($image) 
    { //{{{
        return extension_loaded('gd') && preg_match("/\.(jpg|jpeg|gif|png)/i", $image, $m) &&
            file_exists($image) && function_exists('imagecreatefrom'.($m[1] == 'jpg' ? 'jpeg' : $m[1]));
    } //}}}

    /*
    * 更改图像大小
    $fit: 适应大小方式
    'force': 把图片强制变形成 $width X $height 大小
    'scale': 按比例在安全框 $width X $height 内缩放图片, 输出缩放后图像大小 不完全等于 $width X $height
    'scale_fill': 按比例在安全框 $width X $height 内缩放图片，安全框内没有像素的地方填充色, 使用此参数时可设置背景填充色 $bg_color = array(255,255,255)(红,绿,蓝, 透明度) 透明度(0不透明-127完全透明))
    其它: 智能模能 缩放图像并载取图像的中间部分 $width X $height 像素大小
    $fit = 'force','scale','scale_fill' 时： 输出完整图像
    $fit = 图像方位值 时, 输出指定位置部分图像 
    字母与图像的对应关系如下:
    
    north_west   north   north_east
    
    west         center        east
    
    south_west   south   south_east
    
    */
    public function gifResize($path, $width = 100, $height = 100, $fit = 'center', $fill_color = array(255,255,255,0) )
    { //{{{
        $this->image = new Imagick( $path );
        if($this->image)
        {
            $this->type = strtolower($this->image->getImageFormat());
        }

        switch($fit)
        {
            case 'force':
                if($this->type=='gif')
                {
                    $image = $this->image;
                    $canvas = new Imagick();
                    
                    $images = $image->coalesceImages();
                    foreach($images as $frame){
                        $img = new Imagick();
                        $img->readImageBlob($frame);
                        $img->thumbnailImage( $width, $height, false );

                        $canvas->addImage( $img );
                        $canvas->setImageDelay( $img->getImageDelay() );
                    }
                    $image->destroy();
                    $this->image = $canvas;
                }
                else
                {
                    $this->image->thumbnailImage( $width, $height, false );
                }
                break;
            case 'scale':
                if($this->type=='gif')
                {
                    $image = $this->image;
                    $images = $image->coalesceImages();
                    $canvas = new Imagick();
                    foreach($images as $frame){
                        $img = new Imagick();
                        $img->readImageBlob($frame);
                        $img->thumbnailImage( $width, $height, true );

                        $canvas->addImage( $img );
                        $canvas->setImageDelay( $img->getImageDelay() );
                    }
                    $image->destroy();
                    $this->image = $canvas;

                    $data = $this->image->getImagesBlob();     
                    @file_put_contents($path, $data);
                }
                else
                {
                    $this->image->thumbnailImage( $width, $height, true );
                }
                break;
            case 'scale_fill':
                $size = $this->image->getImagePage(); 
                $src_width = $size['width'];
                $src_height = $size['height'];
                
                $x = 0;
                $y = 0;
                
                $dst_width = $width;
                $dst_height = $height;

                if($src_width*$height > $src_height*$width)
                {
                    $dst_height = intval($width*$src_height/$src_width);
                    $y = intval( ($height-$dst_height)/2 );
                }
                else
                {
                    $dst_width = intval($height*$src_width/$src_height);
                    $x = intval( ($width-$dst_width)/2 );
                }

                $image = $this->image;
                $canvas = new Imagick();
                
                $color = 'rgba('.$fill_color[0].','.$fill_color[1].','.$fill_color[2].','.$fill_color[3].')';
                if($this->type=='gif')
                {
                    $images = $image->coalesceImages();
                    foreach($images as $frame)
                    {
                        $frame->thumbnailImage( $width, $height, true );

                        $draw = new ImagickDraw();
                        $draw->composite($frame->getImageCompose(), $x, $y, $dst_width, $dst_height, $frame);

                        $img = new Imagick();
                        $img->newImage($width, $height, $color, 'gif');
                        $img->drawImage($draw);

                        $canvas->addImage( $img );
                        $canvas->setImageDelay( $img->getImageDelay() );
                        $canvas->setImagePage($width, $height, 0, 0);
                    }
                }
                else
                {
                    $image->thumbnailImage( $width, $height, true );
                    
                    $draw = new ImagickDraw();
                    $draw->composite($image->getImageCompose(), $x, $y, $dst_width, $dst_height, $image);
                    
                    $canvas->newImage($width, $height, $color, $this->get_type() );
                    $canvas->drawImage($draw);
                    $canvas->setImagePage($width, $height, 0, 0);
                }
                $image->destroy();
                $this->image = $canvas;
                break;
            default:
                $size = $this->image->getImagePage(); 
                $src_width = $size['width'];
                $src_height = $size['height'];
                
                $crop_x = 0;
                $crop_y = 0;
                
                $crop_w = $src_width;
                $crop_h = $src_height;
                
                if($src_width*$height > $src_height*$width)
                {
                    $crop_w = intval($src_height*$width/$height);
                }
                else
                {
                    $crop_h = intval($src_width*$height/$width);
                }
                
                switch($fit)
                {
                    case 'north_west':
                        $crop_x = 0;
                        $crop_y = 0;
                        break;
                    case 'north':
                        $crop_x = intval( ($src_width-$crop_w)/2 );
                        $crop_y = 0;
                        break;
                    case 'north_east':
                        $crop_x = $src_width-$crop_w;
                        $crop_y = 0;
                        break;
                    case 'west':
                        $crop_x = 0;
                        $crop_y = intval( ($src_height-$crop_h)/2 );
                        break;
                    case 'center':
                        $crop_x = intval( ($src_width-$crop_w)/2 );
                        $crop_y = intval( ($src_height-$crop_h)/2 );
                        break;
                    case 'east':
                        $crop_x = $src_width-$crop_w;
                        $crop_y = intval( ($src_height-$crop_h)/2 );
                        break;
                    case 'south_west':
                        $crop_x = 0;
                        $crop_y = $src_height-$crop_h;
                        break;
                    case 'south':
                        $crop_x = intval( ($src_width-$crop_w)/2 );
                        $crop_y = $src_height-$crop_h;
                        break;
                    case 'south_east':
                        $crop_x = $src_width-$crop_w;
                        $crop_y = $src_height-$crop_h;
                        break;
                    default:
                        $crop_x = intval( ($src_width-$crop_w)/2 );
                        $crop_y = intval( ($src_height-$crop_h)/2 );
                }
                
                $image = $this->image;
                $canvas = new Imagick();
                
                if($this->type=='gif')
                {
                    $images = $image->coalesceImages();
                    foreach($images as $frame){
                        $img = new Imagick();
                        $img->readImageBlob($frame);
                        $img->cropImage($crop_w, $crop_h, $crop_x, $crop_y);
                        $img->thumbnailImage( $width, $height, true );
                        
                        $canvas->addImage( $img );
                        $canvas->setImageDelay( $img->getImageDelay() );
                        $canvas->setImagePage($width, $height, 0, 0);
                    }
                }
                else
                {
                    $image->cropImage($crop_w, $crop_h, $crop_x, $crop_y);
                    $image->thumbnailImage( $width, $height, true );
                    $canvas->addImage( $image );
                    $canvas->setImagePage($width, $height, 0, 0);
                }
                $image->destroy();
                $this->image = $canvas;
        }
        
    } //}}}
    
}
?>
