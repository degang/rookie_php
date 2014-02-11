<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieDebug 
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieDebug
{

    public static function errorHandler($code, $error, $file = NULL, $line = NULL)
    { //{{{
        if (error_reporting() & $code)
        {
            // 这个错误是不抑制当前的错误报告设置,转换成一个ErrorException错误
            throw new ErrorException($error, $code, 0, $file, $line);
        }

        // 不执行PHP的错误处理程序
        return TRUE;
    } //}}}
    
    /**
     * 加载文件内容
     *
     * @param  string  $file         
     * @param  integer $lineNumber
     * @param  integer $padding
     * @return mixed 
     */
    public static function source($file, $lineNumber, $padding = 8)
    { //{{{
        if ( ! $file && ! is_readable($file))
            return false;

        $file = fopen($file, 'r');
        $line = 0;

        // 设置的阅读范围
        $range = array(
            'start' => $lineNumber - $padding, 
            'end' => $lineNumber + $padding
        );

        // 设置行号的零填充量
        $format = '% '.strlen($range['end']).'d';

        $source = '';
        while (($row = fgets($file)) !== FALSE)
        {
            // 递增的行号
            if (++$line > $range['end'])
                break;

            if ($line >= $range['start'])
            {
                // 使行输出安全
                $row = htmlspecialchars($row, ENT_NOQUOTES, RookieCore::$config['base']['charset']);

                // 修剪空白
                $row = '<span class="number">'.sprintf($format, $line).'</span> '.$row;

                if ($line === $lineNumber)
                {
                    // 应用突出显示此行
                    $row = '<span class="line highlight" style="color:red" >'.$row.'</span>';
                }
                else
                    $row = '<span class="line">'.$row.'</span>';

                // 添加到捕获源
                $source .= $row;
            }
        }

        // 关闭文件
        fclose($file);

        return '<pre class="source"><code>'.$source.'</code></pre>';

    } //}}}
    
    /**
     * 返回一个代表回溯中的每一步的HTML字符串数组。
     *
     * @param   string  path to debug
     * @return  string
     */
    public static function trace(array $trace = NULL)
    { //{{{
        if ($trace === NULL)
        {
            // 启动一个新的跟踪
            $trace = debug_backtrace();
        }

        // 非标准的函数调用
        $statements = array('include', 'include_once', 'require', 'require_once');

        $output = array();
        foreach ($trace as $step)
        {
            if ( ! isset($step['function']))
            {
                // 无效的跟踪步骤
                continue;
            }

            if (isset($step['file']) && isset($step['line']))
            {
                // 这一步包含的源代码
                $source = RookieDebug::source($step['file'], $step['line']);
            }

            if (isset($step['file']))
            {
                $file = $step['file'];

                if (isset($step['line']))
                {
                    $line = $step['line'];
                }
            }

            // function()
            $function = $step['function'];

            if (in_array($step['function'], $statements))
            {
                if (empty($step['args']))
                {
                    // No arguments
                    $args = array();
                }
                else
                {
                    $args = array($step['args'][0]);
                }
            }
            elseif (isset($step['args']))
            {
                if ( ! function_exists($step['function']) && 
                        strpos($step['function'], '{closure}') !== FALSE)
                {
                    $params = NULL;
                }
                else
                {
                    if (isset($step['class']))
                    {
                        if (method_exists($step['class'], $step['function']))
                        {
                            $reflection = new ReflectionMethod($step['class'], $step['function']);
                        }
                        else
                        {
                            $reflection = new ReflectionMethod($step['class'], '__call');
                        }
                    }
                    else
                    {
                        $reflection = new ReflectionFunction($step['function']);
                    }

                    // 获取函数的参数
                    $params = $reflection->getParameters();
                }

                $args = array();

                foreach ($step['args'] as $i => $arg)
                {
                    if (isset($params[$i]))
                    {
                        // 指定参数名称参数
                        $args[$params[$i]->name] = $arg;
                    }
                    else
                    {
                        // 分配数量的参数
                        $args[$i] = $arg;
                    }
                }
            }

            if (isset($step['class']))
            {
                // Class->method() or Class::method()
                $function = $step['class'].$step['type'].$step['function'];
            }

            $output[] = array(
                'function' => $function,
                'args'     => isset($args)   ? $args : NULL,
                'file'     => isset($file)   ? $file : NULL,
                'line'     => isset($line)   ? $line : NULL,
                'source'   => isset($source) ? $source : NULL,
            );

            unset($function, $args, $file, $line, $source);
        }

        return $output;
    } //}}}

    /**
     * 对任意数量的调试信息返回HTML字符串变量，每个包裹在一个"pre"的标签：
     *
     *     // Displays the type and value of each variable
     *     //显示每个变量的类型和价值
     *     echo RookieDebug::vars($foo, $bar, $baz);
     *
     * @param   mixed   variable to debug
     * @param   ...
     * @return  string
     */
    public static function vars()
    { //{{{
        if (func_num_args() === 0)
            return;

        // 获取所有传递的变量
        $variables = func_get_args();

        $output = array();
        foreach ($variables as $var)
        {
            $output[] = RookieDebug::_dump($var, 1024);
        }

        return '<pre class="debug">'.implode("\n", $output).'</pre>';
    } //}}}

    /**
     * 返回HTML字符串中的单个变量的信息。
     *
     * 大量借鉴Debug类的概念 [Nette](http://nettephp.com/).
     *
     * @param   mixed    variable to dump
     * @param   integer  maximum length of strings
     * @param   integer  recursion limit
     * @return  string
     */
    public static function dump($value, $length = 128, $level_recursion = 10)
    { //{{{
        return RookieDebug::_dump($value, $length, $level_recursion);
    } //}}}

    /**
     * Helper for RookieDebug::dump(), 处理数组和对象的递归。
     *
     * @param   mixed    variable to dump
     * @param   integer  maximum length of strings
     * @param   integer  recursion limit
     * @param   integer  当前递归级别(internal usage only!)
     * @return  string
     */
    protected static function _dump( & $var, $length = 128, $limit = 10, $level = 0)
    { //{{{
        if ($var === NULL)
        {
            return '<small>NULL</small>';
        }
        elseif (is_bool($var))
        {
            return '<small>bool</small> '.($var ? 'TRUE' : 'FALSE');
        }
        elseif (is_float($var))
        {
            return '<small>float</small> '.$var;
        }
        elseif (is_resource($var))
        {
            if (($type = get_resource_type($var)) === 'stream' && $meta = stream_get_meta_data($var))
            {
                $meta = stream_get_meta_data($var);

                if (isset($meta['uri']))
                {
                    $file = $meta['uri'];

                    if (function_exists('stream_is_local'))
                    {
                        // 只存在于 PHP >= 5.2.4
                        if (stream_is_local($file))
                        {
                            $file = RookieDebug::path($file);
                        }
                    }

                    return '<small>resource</small><span>('.$type.')</span> '.
                            htmlspecialchars($file, ENT_NOQUOTES, Kohana::$charset);
                }
            }
            else
            {
                return '<small>resource</small><span>('.$type.')</span>';
            }
        }
        elseif (is_string($var))
        {
            if (strlen($var) > $length)
            {
                // 截断的字符串进行编码
                $str = htmlspecialchars(substr($var, 0, $length), ENT_NOQUOTES, 
                    RookieCore::$config['base']['charset']).'&nbsp;&hellip;';
            }
            else
            {
                // 编码字符串
                $str = htmlspecialchars($var, ENT_NOQUOTES, RookieCore::$config['base']['charset']);
            }

            return '<small>string</small><span>('.strlen($var).')</span> "'.$str.'"';
        }
        elseif (is_array($var))
        {
            $output = array();

            // 对这个变量的压痕
            $space = str_repeat($s = '    ', $level);

            static $marker;

            if ($marker === NULL)
            {
                //做一个独特的标记
                $marker = uniqid("\x00");
            }

            if (empty($var))
            {
                //什么都不做
            }
            elseif (isset($var[$marker]))
            {
                $output[] = "(\n$space$s*RECURSION*\n$space)";
            }
            elseif ($level < $limit)
            {
                $output[] = "<span>(";

                $var[$marker] = TRUE;
                foreach ($var as $key => & $val)
                {
                    if ($key === $marker) continue;
                    if ( ! is_int($key))
                    {
                        $key = '"'.htmlspecialchars($key, ENT_NOQUOTES, 
                            RookieCore::$config['base']['charset']).'"';
                    }

                    $output[] = "$space$s$key => ".RookieDebug::_dump($val, $length, $limit, $level+1);
                }
                unset($var[$marker]);

                $output[] = "$space)</span>";
            }
            else
            {
                // 深度过大
                $output[] = "(\n$space$s...\n$space)";
            }

            return '<small>array</small><span>('.count($var).')</span> '.implode("\n", $output);
        }
        elseif (is_object($var))
        {
            // 作为一个数组复制的对象
            $array = (array) $var;

            $output = array();

            // 对这个变量的压痕
            $space = str_repeat($s = '    ', $level);

            $hash = spl_object_hash($var);

            // 正在倾倒的对象
            static $objects = array();

            if (empty($var))
            {
                // 什么都不做
            }
            elseif (isset($objects[$hash]))
            {
                $output[] = "{\n$space$s*RECURSION*\n$space}";
            }
            elseif ($level < $limit)
            {
                $output[] = "<code>{";

                $objects[$hash] = TRUE;
                foreach ($array as $key => & $val)
                {
                    if ($key[0] === "\x00")
                    {
                        // 确定是否是受保护的或受保护的访问
                        $access = '<small>'.(($key[1] === '*') ? 'protected' : 'private').'</small>';

                        // 从变量名中删除的访问级别
                        $key = substr($key, strrpos($key, "\x00") + 1);
                    }
                    else
                    {
                        $access = '<small>public</small>';
                    }

                    $output[] = "$space$s$access $key => ".
                        RookieDebug::_dump($val, $length, $limit, $level + 1);
                }
                unset($objects[$hash]);

                $output[] = "$space}</code>";
            }
            else
            {
                // 深度过大
                $output[] = "{\n$space$s...\n$space}";
            }

            return '<small>object</small> <span>'.get_class($var).'('.
                count($array).')</span> '.implode("\n", $output);
        }
        else
        {
            return '<small>'.gettype($var).'</small> '.htmlspecialchars(print_r($var, TRUE), 
                ENT_NOQUOTES, RookieCore::$config['base']['charset']);
        }
    } //}}}


}
