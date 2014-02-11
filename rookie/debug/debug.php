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
            // ��������ǲ����Ƶ�ǰ�Ĵ��󱨸�����,ת����һ��ErrorException����
            throw new ErrorException($error, $code, 0, $file, $line);
        }

        // ��ִ��PHP�Ĵ��������
        return TRUE;
    } //}}}
    
    /**
     * �����ļ�����
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

        // ���õ��Ķ���Χ
        $range = array(
            'start' => $lineNumber - $padding, 
            'end' => $lineNumber + $padding
        );

        // �����кŵ��������
        $format = '% '.strlen($range['end']).'d';

        $source = '';
        while (($row = fgets($file)) !== FALSE)
        {
            // �������к�
            if (++$line > $range['end'])
                break;

            if ($line >= $range['start'])
            {
                // ʹ�������ȫ
                $row = htmlspecialchars($row, ENT_NOQUOTES, RookieCore::$config['base']['charset']);

                // �޼��հ�
                $row = '<span class="number">'.sprintf($format, $line).'</span> '.$row;

                if ($line === $lineNumber)
                {
                    // Ӧ��ͻ����ʾ����
                    $row = '<span class="line highlight" style="color:red" >'.$row.'</span>';
                }
                else
                    $row = '<span class="line">'.$row.'</span>';

                // ��ӵ�����Դ
                $source .= $row;
            }
        }

        // �ر��ļ�
        fclose($file);

        return '<pre class="source"><code>'.$source.'</code></pre>';

    } //}}}
    
    /**
     * ����һ����������е�ÿһ����HTML�ַ������顣
     *
     * @param   string  path to debug
     * @return  string
     */
    public static function trace(array $trace = NULL)
    { //{{{
        if ($trace === NULL)
        {
            // ����һ���µĸ���
            $trace = debug_backtrace();
        }

        // �Ǳ�׼�ĺ�������
        $statements = array('include', 'include_once', 'require', 'require_once');

        $output = array();
        foreach ($trace as $step)
        {
            if ( ! isset($step['function']))
            {
                // ��Ч�ĸ��ٲ���
                continue;
            }

            if (isset($step['file']) && isset($step['line']))
            {
                // ��һ��������Դ����
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

                    // ��ȡ�����Ĳ���
                    $params = $reflection->getParameters();
                }

                $args = array();

                foreach ($step['args'] as $i => $arg)
                {
                    if (isset($params[$i]))
                    {
                        // ָ���������Ʋ���
                        $args[$params[$i]->name] = $arg;
                    }
                    else
                    {
                        // ���������Ĳ���
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
     * �����������ĵ�����Ϣ����HTML�ַ���������ÿ��������һ��"pre"�ı�ǩ��
     *
     *     // Displays the type and value of each variable
     *     //��ʾÿ�����������ͺͼ�ֵ
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

        // ��ȡ���д��ݵı���
        $variables = func_get_args();

        $output = array();
        foreach ($variables as $var)
        {
            $output[] = RookieDebug::_dump($var, 1024);
        }

        return '<pre class="debug">'.implode("\n", $output).'</pre>';
    } //}}}

    /**
     * ����HTML�ַ����еĵ�����������Ϣ��
     *
     * �������Debug��ĸ��� [Nette](http://nettephp.com/).
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
     * Helper for RookieDebug::dump(), ��������Ͷ���ĵݹ顣
     *
     * @param   mixed    variable to dump
     * @param   integer  maximum length of strings
     * @param   integer  recursion limit
     * @param   integer  ��ǰ�ݹ鼶��(internal usage only!)
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
                        // ֻ������ PHP >= 5.2.4
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
                // �ضϵ��ַ������б���
                $str = htmlspecialchars(substr($var, 0, $length), ENT_NOQUOTES, 
                    RookieCore::$config['base']['charset']).'&nbsp;&hellip;';
            }
            else
            {
                // �����ַ���
                $str = htmlspecialchars($var, ENT_NOQUOTES, RookieCore::$config['base']['charset']);
            }

            return '<small>string</small><span>('.strlen($var).')</span> "'.$str.'"';
        }
        elseif (is_array($var))
        {
            $output = array();

            // �����������ѹ��
            $space = str_repeat($s = '    ', $level);

            static $marker;

            if ($marker === NULL)
            {
                //��һ�����صı��
                $marker = uniqid("\x00");
            }

            if (empty($var))
            {
                //ʲô������
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
                // ��ȹ���
                $output[] = "(\n$space$s...\n$space)";
            }

            return '<small>array</small><span>('.count($var).')</span> '.implode("\n", $output);
        }
        elseif (is_object($var))
        {
            // ��Ϊһ�����鸴�ƵĶ���
            $array = (array) $var;

            $output = array();

            // �����������ѹ��
            $space = str_repeat($s = '    ', $level);

            $hash = spl_object_hash($var);

            // �����㵹�Ķ���
            static $objects = array();

            if (empty($var))
            {
                // ʲô������
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
                        // ȷ���Ƿ����ܱ����Ļ��ܱ����ķ���
                        $access = '<small>'.(($key[1] === '*') ? 'protected' : 'private').'</small>';

                        // �ӱ�������ɾ���ķ��ʼ���
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
                // ��ȹ���
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
