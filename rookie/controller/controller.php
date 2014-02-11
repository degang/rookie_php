<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieController 
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 

class RookieController
{
    /**
     * @var array $puts 客户端发送的参数转成变量
     */
    public $puts;

    public $autoView = true;

    public $data = array();

    public $view;

    public $viewName;

    protected $_load;

    protected $_view;


    /**
     * 设置PUT请求变量
     *
     * @return mixed
     */
    public function initPutVars()
    { //{{{
        parse_str(file_get_contents('php://input'), $this->puts);
    } //}}}

    public function beforeRun(){}

    /**
     * 视图
     *
     * return mixed
     */
    public function view($viewName, $data = array())
    { //{{{
        $data = $this->data;
        $this->viewName = $viewName;
        $viewPath = RookieUri::$viewPath.DS.$viewName.'.php';

        if (file_exists($viewPath))
        {
            if ($this->includeLayout($viewPath, $data) === false)
                require $viewPath; 
        }
    } //}}}

    /**
     * 加载布局文件  
     *
     * @param string $viewPath
     * @param array $data
     * @return mixed
     */
    public function includeLayout($viewPath, $data)
    { //{{{
        if (RookieUri::$layout)
        {
            ob_start();
            require $viewPath; 
            $content = ob_get_clean();

            $layoutPath = RookieUri::$layoutPath.RookieUri::$layout.'.php';
            (file_exists($layoutPath)) && require $layoutPath; 
        }
        else
            return false;
    } //}}}
    
    /**
     * auto view
     * 
     * return mixed
     */
    public function autoView($data = array())
    { //{{{
        $data = $this->data;
        if (file_exists(RookieUri::$viewPath))
        {
            if ($this->includeLayout(RookieUri::$viewPath, $data) === false)
                require RookieUri::$viewPath;
        }
    } //}}}


    /**
     * 加载i18n
     *
     * return void
     */
    public function language()
    { //{{{

    } //}}}


    /**
     * 获取header头 
     *
     * @return string Client accept type
     */
    public function acceptType()
    { //{{{
        $type = array(
            '*/*'=>'*',
            'html'=>'text/html,application/xhtml+xml',
            'xml'=>'application/xml,text/xml,application/x-xml',
            'json'=>'application/json,text/x-json,application/jsonrequest,text/json',
            'js'=>'text/javascript,application/javascript,application/x-javascript',
            'css'=>'text/css',
            'rss'=>'application/rss+xml',
            'yaml'=>'application/x-yaml,text/yaml',
            'atom'=>'application/atom+xml',
            'pdf'=>'application/pdf',
            'text'=>'text/plain',
            'png'=>'image/png',
            'jpg'=>'image/jpg,image/jpeg,image/pjpeg',
            'gif'=>'image/gif',
            'form'=>'multipart/form-data',
            'url-form'=>'application/x-www-form-urlencoded',
            'csv'=>'text/csv'
        );

        $matches = array();

        foreach($type as $k=>$v)
        {
            if(strpos($v,',')!==FALSE)
            {
                $tv = explode(',', $v);
                foreach($tv as $k2=>$v2)
                {
                    if (stristr($_SERVER["HTTP_ACCEPT"], $v2))
                    {
                        if(isset($matches[$k]))
                            $matches[$k] = $matches[$k]+1;
                        else
                            $matches[$k]=1;
                    }
                }
            }
            else
            {
                if (stristr($_SERVER["HTTP_ACCEPT"], $v))
                {
                    if(isset($matches[$k]))
                        $matches[$k] = $matches[$k]+1;
                    else
                        $matches[$k]=1;
                }
            }
        }

        if(sizeof($matches)<1)
            return NULL;

        arsort($matches);

        foreach ($matches as $k=>$v)
            return ($k==='*/*')?'html':$k;
    } //}}}

    /**
     * 设置header头 
     *
     * @param string $type 类型 
     * @param string $charset 编码类型 
     */
    public function setContentType($type, $charset='utf-8')
    { //{{{
        if(headers_sent())return;

        $extensions = array('html'=>'text/html',
                            'xml'=>'application/xml',
                            'json'=>'application/json',
                            'js'=>'application/javascript',
                            'css'=>'text/css',
                            'rss'=>'application/rss+xml',
                            'yaml'=>'text/yaml',
                            'atom'=>'application/atom+xml',
                            'pdf'=>'application/pdf',
                            'text'=>'text/plain',
                            'png'=>'image/png',
                            'jpg'=>'image/jpeg',
                            'gif'=>'image/gif',
                            'csv'=>'text/csv'
                        );
        if(isset($extensions[$type]))
            header("Content-Type: {$extensions[$type]}; charset=$charset");
    } //}}}

    /**
     * 获取客户端IP 
     *
     * @return string
     */
    public function clientIP()
    { //{{{
        if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) 
        {
            return getenv('HTTP_CLIENT_IP');
        } 
        elseif(getenv('HTTP_X_FORWARDED_FOR') && 
            strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) 
        {
            return getenv('HTTP_X_FORWARDED_FOR');
        }
        elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) 
        {
            return getenv('REMOTE_ADDR');
        } 
        elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && 
            strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) 
        {
            return $_SERVER['REMOTE_ADDR'];
        }
    } //}}}

    /**
     * 结束执行 
     *
     * @param mixed $routeResult 
     */    
    public function afterRun($routeResult) 
    { //{{{
        //是否开启运行统计
        if(RookieCore::$config['base']['isRunStatic'] === TRUE)
        {
            $content  = '<div><p><b>性能显示：</b><br>运行时间：<font color=red>' .
                 (microtime()-SYS_START_TIME) . '</font>&nbsp;&nbsp;使用内存：<font color=red>' .
                 round(ROOKIE_START_MEMORY / 1048576 * 100) / 100 . ' mb'.'</font><br>';
        
            $included = get_included_files();
            $include_count = count($included);
            $content .= '<BR><B>加载文件的个数：</B><font color=red>'.$include_count.'</font><br>';
            
            foreach ($included as $key => $file_name)
            {
                $content .= $file_name . '<br>';
            }
            $content .= '</div>';
            echo $content;
        }
    } //}}}

    /**
     * 判断是否是ajax请求
     *
     * return boolean 
     */
    public function isAjax()
    { //{{{
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    } //}}}

    /**
     * 判断是否是ssl连接 
     * @return bool 
     */
    public function isSSL()
    { //{{{
        if(!isset($_SERVER['HTTPS']))
            return FALSE;

        //Apache
        if($_SERVER['HTTPS'] === 1) 
        {
            return TRUE;
        }
        //IIS
        elseif ($_SERVER['HTTPS'] === 'on') 
        {
            return TRUE;
        }
        //other servers
        elseif ($_SERVER['SERVER_PORT'] == 443)
        {
            return TRUE;
        }
        return FALSE;
    } //}}}
    
    /**
     * 将DB返回数据转成xml
     *
     * @param mixed $result 
     * @param bool $output 
     * @param bool $setXMLContentType 
     * @param string $encoding 
     * @return string XML string
     */
    public function toXML($result, $output=false, $setXMLContentType=false, $encoding='utf-8')
    { //{{{
        $str = '<?xml version="1.0" encoding="utf-8"?><result>';
        foreach($result as $kk=>$vv)
        {
            $cls = get_class($vv);
            $str .= '<' . $cls . '>';
            foreach($vv as $k=>$v)
            {
                if($k!='_table' && $k!='_fields' && $k!='_primarykey')
                {
                    if(is_array($v))
                    {
                        $str .= '<' . $k . '>';
                        foreach($v as $v0)
                        {
                            $str .= '<data>';
                            foreach($v0 as $k1=>$v1)
                            {
                                if($k1!='_table' && $k1!='_fields' && $k1!='_primarykey')
                                {
                                    if(is_array($v1))
                                    {
                                        $str .= '<' . $k1 . '>';
                                        foreach($v1 as $v2)
                                        {
                                            $str .= '<data>';
                                            foreach($v2 as $k3=>$v3)
                                            {
                                                if($k3!='_table' && $k3!='_fields' && 
                                                    $k3!='_primarykey')
                                                {
                                                    $str .= '<'. $k3 . '><![CDATA[' . $v3 .
                                                        ']]></'. $k3 . '>';
                                                }
                                            }
                                            $str .= '</data>';
                                        }
                                        $str .= '</' . $k1 . '>';
                                    }
                                    else
                                    {
                                        $str .= '<'. $k1 . '><![CDATA[' . $v1 . ']]></'. $k1 . '>';
                                    }
                                }
                            }
                            $str .= '</data>';
                        }
                        $str .= '</' . $k . '>';

                    }
                    else
                    {
                        $str .= '<'. $k . '>' . $v . '</'. $k . '>';
                    }
                }
            }
            $str .= '</' . $cls . '>';
        }
        $str .= '</result>';
        if($setXMLContentType===true)
            $this->setContentType('xml', $encoding);
        if($output===true)
            echo $str;
        return $str;
    } //}}}

    /**
     * 将DB返回的数据转成json
     *
     * @param mixed $result 
     * @param bool $output 
     * @param bool $removeNullField 
     * @param array $exceptField 删除字段是空的 
     * @param array $mustRemoveFieldList 在此列表中删除字段 
     * @param bool $setJSONContentType 
     * @param string $encoding 
     * @return string JSON string
     */
    public function toJSON($result, $output=false, $removeNullField=false, $exceptField=null,
        $mustRemoveFieldList=null, $setJSONContentType=true, $encoding='utf-8')
    { //{{{
        $rs = preg_replace(array('/\,\"\_table\"\:\".*\"/U', '/\,\"\_primarykey\"\:\".*\"/U',
             '/\,\"\_fields\"\:\[\".*\"\]/U'), '', json_encode($result));
        if($removeNullField)
        {
            if($exceptField===null)
                $rs = preg_replace(array('/\,\"[^\"]+\"\:null/U', '/\{\"[^\"]+\"\:null\,/U'),
                    array('','{'), $rs);
            else
            {
                $funca1 =  create_function('$matches',
                    'if(in_array($matches[1], array(\''. implode("','",$exceptField) .'\'))===false){
                                return "";
                            }
                            return $matches[0];');

                $funca2 =  create_function('$matches',
                    'if(in_array($matches[1], array(\''. implode("','",$exceptField) .'\'))===false){
                                return "{";
                            }
                            return $matches[0];');

                $rs = preg_replace_callback('/\,\"([^\"]+)\"\:null/U', $funca1, $rs);
                $rs = preg_replace_callback('/\{\"([^\"]+)\"\:null\,/U', $funca2, $rs);
            }
        }

        if($mustRemoveFieldList!==null)
        {
            $funcb1 =  create_function('$matches',
                        'if(in_array($matches[1], array(\''. implode("','",$mustRemoveFieldList) .'\'))){
                            return "";
                        }
                        return $matches[0];');

            $funcb2 =  create_function('$matches',
                        'if(in_array($matches[1], array(\''. implode("','",$mustRemoveFieldList) .'\'))){
                            return "{";
                        }
                        return $matches[0];');
            
            $rs = preg_replace_callback(array('/\,\"([^\"]+)\"\:\".*\"/U', '/\,\"([^\"]+)\"\:\{.*\}/U',
                '/\,\"([^\"]+)\"\:\[.*\]/U', '/\,\"([^\"]+)\"\:([false|true|0-9|\.\-|null]+)/'), 
                $funcb1, $rs);

            $rs = preg_replace_callback(array(
                '/\{\"([^\"]+)\"\:\".*\"\,/U','/\{\"([^\"]+)\"\:\{.*\}\,/U'), $funcb2, $rs);

            preg_match('/(.*)(\[\{.*)\"('. implode('|',$mustRemoveFieldList) .')\"\:\[(.*)/', $rs, $m);
            
            if($m)
            {
                if( $pos = strpos($m[4], '"}],"') )
                {
                    if($pos2 = strpos($m[4], '"}]},{'))
                    {
                        $d = substr($m[4], $pos2+5);
                        if(substr($m[2],-1)==',')
                            $m[2] = substr_replace($m[2], '},', -1);
                    }
                    else if(strpos($m[4], ']},{')!==false)
                    {
                        $d = substr($m[4], strpos($m[4], ']},{')+3);  
                        if(substr($m[2],-1)==',')
                            $m[2] = substr_replace($m[2], '},', -1);
                    }
                    else if(strpos($m[4], '],"')===0)
                    {
                        $d = substr($m[4], strpos($m[4], '],"')+2);  
                    }                    
                    else if(strpos($m[4], '}],"')!==false)
                    {
                        $d = substr($m[4], strpos($m[4], '],"')+2);  
                    }
                    else
                    {
                        $d = substr($m[4], $pos+4);
                    }
                }
                else
                {
                    $rs = preg_replace('/(\[\{.*)\"('. implode('|',$mustRemoveFieldList) .
                        ')\"\:\[.*\]\}(\,)?/U', '$1}', $rs);
                    $rs = preg_replace('/(\".*\"\:\".*\")\,\}(\,)?/U', '$1}$2', $rs);
                }

                if(isset($d))
                {
                    $rs = $m[1].$m[2].$d;
                }
            }
        }
        
        if($output===true)
        {
            if($setJSONContentType===true)
                $this->setContentType('json', $encoding);
            echo $rs;
        }
        return $rs;
    } //}}}

    /**
     * 对象转数组
     *
     * @param object $e
     * @return array
     */
    public function objectToArray($e)
    { //{{{
        $e=(array)$e;
        foreach($e as $k=>$v)
        {
            if( gettype($v)=='resource' ) return;
            if( gettype($v)=='object' || gettype($v)=='array' )
                $e[$k]=(array)$this->objectToArray($v);
        }
        return $e;
    } //}}} 

    /**
     * 验证码
     */
    public function codeAction()
    { //{{{
        if (isset($this->code))
            RookieCaptcha::code();
        else
            throw new RookieException('I\'m sorry you are looking page does not exist');
    } //}}}

    /**
     * 检查验证码
     * @param  string $code
     * @return boolean 
     */
    public function checkCode($code)
    { //{{{
        if ($code)
        {   
            $session = RookieSession::instance();
            if ($code == $session->get('captcha'.RookieUri::$controller))
                return true;
            else
                return false;
        }
        return false;
    } //}}}
}
