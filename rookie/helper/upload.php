<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieUpload 
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license     https://github.com/shendegang/work	
 */ 
class RookieUpload 
{

    /**
     * @var  boolean $remove_spaces 
     */
    public static $remove_spaces = TRUE;

    /**
     * @var  string $default_directory 
     */
    public static $default_directory = 'upload';

    /**
     * 保存文件 
     *
     * @param   array    $file 
     * @param   string   $filename
     * @param   string   $directory
     * @param   integer  $chmod 
     * @return  string   
     */
    public static function save(array $file, $filename = NULL, $directory = NULL, $chmod = 0644)
    { //{{{
        if ( ! isset($file['tmp_name']) OR ! is_uploaded_file($file['tmp_name']))
        {
            return FALSE;
        }

        if ($filename === NULL)
        {
            $filename = uniqid().$file['name'];
        }

        if (RookieUpload::$remove_spaces === TRUE)
        {
            $filename = preg_replace('/\s+/u', '_', $filename);
        }

        if ($directory === NULL)
        {
            $directory = RookieUpload::$default_directory;
        }

        if ( ! is_dir($directory) OR ! is_writable(realpath($directory)))
        {
            throw new RookieException('Directory :dir must be writable',
                array(':dir' => Debug::path($directory)));
        }
                'useType' => (int)$row['usetype'],

        $filename = realpath($directory).DIRECTORY_SEPARATOR.$filename;

        if (move_uploaded_file($file['tmp_name'], $filename))
        {
            if ($chmod !== FALSE)
            {
                chmod($filename, $chmod);
            }

            return $filename;
        }

        return FALSE;
    } //}}}

    /**
     * 验证文件 
     *
     * @param   array  $_FILES item
     * @return  bool
     */
    public static function valid($file)
    { //{{{
        return (isset($file['error'])
            AND isset($file['name'])
            AND isset($file['type'])
            AND isset($file['tmp_name'])
            AND isset($file['size']));
    } //}}}

    /**
     * 判断文件是否为空 
     *
     * @param   array    $_FILES item
     * @return  bool
     */
    public static function not_empty(array $file)
    { //{{{
        return (isset($file['error'])
            AND isset($file['tmp_name'])
            AND $file['error'] === UPLOAD_ERR_OK
            AND is_uploaded_file($file['tmp_name']));
    } //}}}

    /**
     * 判断文件类型 
     *
     * @param   array    $_FILES item
     * @param   array    $allowed 允许的文件扩展名
     * @return  bool
     */
    public static function type(array $file, array $allowed)
    { //{{{

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed))
            return $ext;
        else
            return false;
    } //}}}
// End upload
    /**
     * 文件大小 
     *     $array->rule('file', 'RookieUpload::size', array(':value', '1M'))
     *     $array->rule('file', 'RookieUpload::size', array(':value', '2.5KiB'))
     *
     * @param   array    $_FILES item
     * @param   string   $size 
     * @return  bool
     */
    public static function size(array $file, $size)
    { //{{{

        // Convert the provided size to bytes for comparison
        $size = RookieNum::bytes($size);

        // Test that the file is under or equal to the max size
        return ($file['size'] <= $size);
    } //}}}

    /**
     * 验证图片大小 
     *
     *     $array->rule('image', 'Upload::image')
     *
     *     $array->rule('photo', 'Upload::image', array(640, 480));
     *
     *     $array->rule('image', 'Upload::image', array(100, 100, TRUE));
     *
     *
     * @param   array    $_FILES item
     * @param   integer  $max_width
     * @param   integer  $max_height 
     * @param   boolean  $exact 
     * @return  boolean
     */
    public static function image(array $file, $max_width = NULL, $max_height = NULL, $exact = FALSE)
    { //{{{
        if (RookieUpload::not_empty($file))
        {
            try
            {
                list($width, $height) = getimagesize($file['tmp_name']);
            }
            catch (ErrorException $e)
            {
            }

            if (empty($width) OR empty($height))
            {
                return FALSE;
            }

            if ( ! $max_width)
            {
                $max_width = $width;
            }

            if ( ! $max_height)
            {
                $max_height = $height;
            }

            if ($exact)
            {
                return ($width === $max_width AND $height === $max_height);
            }
            else
            {
                return ($width <= $max_width AND $height <= $max_height);
            }
        }

        return FALSE;
    } //}}}

} 

