<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * 文件的辅助类。
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieFile {

    /**
     * 尝试从文件的MIME类型
     *
     *     $mime = RookieFile::mime($file);
     *
     * @param   string  file name or path
     * @return  string  mime type on success
     * @return  FALSE   on failure
     */
    public static function mime($filename)
    { //{{{
        // 获取文件的完整路径
        $filename = realpath($filename);

        // 从文件名扩展
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (preg_match('/^(?:jpe?g|png|[gt]if|bmp|swf)$/', $extension))
        {
            // 使用和getimagesize找到图像的MIME类型
            $file = getimagesize($filename);

            if (isset($file['mime']))
                return $file['mime'];
        }

        if (class_exists('finfo', FALSE))
        {
            if ($info = new finfo(defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME))
            {
                return $info->file($filename);
            }
        }

        if (ini_get('mime_magic.magicfile') AND function_exists('mime_content_type'))
        {
            // mime_content_type功能是只与一个神奇的文件有用
            return mime_content_type($filename);
        }

        if ( ! empty($extension))
        {
            return RookieFile::mimeByExt($extension);
        }

        // 找不到MIME类型
        return FALSE;
    } //}}}

    /**
     * 返回的MIME类型扩展。
     *
     *     $mime = RookieFile::mimeByExt('png'); // "image/png"
     *
     * @param   string  extension: php, pdf, txt, etc
     * @return  string  mime type on success
     * @return  FALSE   on failure
     */
    public static function mimeByExt($extension)
    { //{{{
        // 负载的所有MIME类型
        $mimes = require dirname(__FILE__).DS.'config'.DS.'mimes.php';

        return isset($mimes[$extension]) ? $mimes[$extension][0] : FALSE;
    } //}}}

    /**
     * 查找文件的MIME类型
     *
     * @see Kohana_RookieFile::mimeByExt()
     * @param string $extension Extension to lookup
     * @return array Array of MIMEs associated with the specified extension
     */
    public static function mimesByExt($extension)
    { //{{{
        // 负载的所有MIME类型
        $mimes = require dirname(__FILE__).DS.'config'.DS.'mimes.php';

        return isset($mimes[$extension]) ? ( (array) $mimes[$extension]) : array();
    } //}}}

    /**
     * 查找MIME类型的文件扩展名
     *
     * @param   string  $type 文件的MIME类型
     * @return  array   File  MIME类型的扩展匹配
     */
    public static function extsByMime($type)
    { //{{{
        static $types = array();

        // 填补了静态数组
        if (empty($types))
        {
            foreach (Kohana::$config->load('mimes') as $ext => $mimes)
            {
                foreach ($mimes as $mime)
                {
                    if ($mime == 'application/octet-stream')
                    {
                        // 八位字节流是一个通用的二进制
                        continue;
                    }

                    if ( ! isset($types[$mime]))
                    {
                        $types[$mime] = array( (string) $ext);
                    }
                    elseif ( ! in_array($ext, $types[$mime]))
                    {
                        $types[$mime][] = (string) $ext;
                    }
                }
            }
        }

        return isset($types[$type]) ? $types[$type] : FALSE;
    } //}}}

    /**
     * 查找MIME类型单一的文件扩展名。
     *
     * @param   string  $type  MIME类型来查找
     * @return  mixed          第一个文件扩展名匹配或假
     */
    public static function extByMime($type)
    { //{{{
        return current(RookieFile::extsByMime($type));
    } //}}}

    /**
     * 分割成小片匹配一个特定大小的文件。当你需要使用
     * 大文件分割成便于传输的较小的部分。
     *
     *     $count = RookieFile::split($file);
     *
     * @param   string   要分割文件
     * @param   string   目录，默认输出到同一目录中的文件
     * @param   integer  大小，以MB为单位，每件
     * @return  integer  所创建的件数
     */
    public static function split($filename, $piece_size = 10)
    { //{{{
        // 打开输入文件
        $file = fopen($filename, 'rb');

        // 更改一块大小为字节
        $piece_size = floor($piece_size * 1024 * 1024);

        // 在8K块写入文件
        $block_size = 1024 * 8;

        // 总数的peices
        $peices = 0;

        while ( ! feof($file))
        {
            // 创建另一片
            $peices += 1;

            // 创建一个新文件的碎片
            $piece = str_pad($peices, 3, '0', STR_PAD_LEFT);
            $piece = fopen($filename.'.'.$piece, 'wb+');

            // 读取的字节数
            $read = 0;

            do
            {
                // 在块传输的数据
                fwrite($piece, fread($file, $block_size));

                // 另一个块已读
                $read += $block_size;
            }
            while ($read < $piece_size);

            // 关闭件
            fclose($piece);
        }

        //关闭文件
        fclose($file);

        return $peices;
    } //}}}

    /**
     * 加入到整个文件分割文件。是否反向 [RookieFile::split].
     *
     *     $count = RookieFile::join($file);
     *
     * @param   string   分割的文件名，没有.000扩展
     * @param   string   输出文件名，如果不同，则文件名
     * @return  integer  加入的件数。
     */
    public static function join($filename)
    { //{{{
        // 打开文件
        $file = fopen($filename, 'wb+');

        // 在8K块读文件
        $block_size = 1024 * 8;

        // 总数的peices
        $pieces = 0;

        while (is_file($piece = $filename.'.'.str_pad($pieces + 1, 3, '0', STR_PAD_LEFT)))
        {
            // 阅读另一片
            $pieces += 1;

            // 打开一块读
            $piece = fopen($piece, 'rb');

            while ( ! feof($piece))
            {
                // 在块传输的数据
                fwrite($file, fread($piece, $block_size));
            }

            // 关闭件
            fclose($piece);
        }

        return $pieces;
    } //}}}

} 
 
