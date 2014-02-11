<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * Rookie exception
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieException extends exception
{
    //������������
    public static $errorViewContentType = 'text/html';

    //��������
    protected $code = 0;
    
    //���������Ϣ
    public static $phpErrors = array( //{{{
        E_ERROR             => 'Fatal Error',       //��������
        E_USER_ERROR        => 'User Error',        //�û�����
        E_PARSE             => 'Parse Error',       //��������
        E_WARNING           => 'Warning',           //����
        E_USER_WARNING      => 'User Warning',      //�û�����
        E_STRICT            => 'Strict',            //�ѹ�ʱ�ĺ�������ʾ,E_ALL������ E_STRICT�������Ĭ��δ����
        E_NOTICE            => 'Notice',            //��Դ����п��ܳ��ֵ�bug��������
        E_USER_NOTICE       => 'User notice',       //�û����ɵ�֪ͨ��Ϣ
        E_CORE_ERROR        => 'Core Error',        //PHP�ĳ�ʼ���������з�������������
        E_CORE_WARNING      => 'Core Warning',      //PHP�ĳ�ʼ���������з����ľ��棨����������
        E_COMPILE_ERROR     => 'Compile Error',     //�����ı���ʱ����
        E_COMPILE_WARNING   => 'Compile warning',   //�����ı���ʱ����
        E_RECOVERABLE_ERROR => 'Recoverable Error', //���������������������Σ�յĴ���������û���뿪���������ڲ��ȶ���״̬��
    ); //}}}

    /**
     * ����һ���µ��쳣
     *
     * @param  string $message
     * @param  array  $variables 
     * @param  mixed  $code
     * @return void
     */
    public function __construct($message, array $variables= NULL, $code = 0)
    { //{{{
        if (defined('E_DEPRECATED'))
        {
            // E_DEPRECATEDֻ������ PHP >= 5.3.0
            RookieException::$phpErrors[E_USER_DEPRECATED ] = 'user deprecated';
            RookieException::$phpErrors[E_DEPRECATED] = 'Deprecated';
        }
        
        count($variables) && $message = RookieException::setMessage($message, $variables);

        // ������Ϣ��������������
        parent::__construct($message, (int) $code);

        //����δ�޸ĵĴ���
        $this->code = $code;
    } //}}}

    /**
     * �쳣����
     *
     * @param object Exception
     * @return mixed 
     */
    public static function handler(Exception $e)
    { //{{{
        try{
            $type = get_class($e);
            $code = $e->getCode();
            $message = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();

            //��ȡ�쳣��˷
            $trace = $e->getTrace();

            //����Ƿ񱻼̳У������쳣
            if ($e instanceof ErrorException)
            {
                if (isset(self::$phpErrors[$code]))
                    $code = self::$phpErrors[$code];
                
                //����ErrorException�޸�
                if (version_compare(phpversion(), '5.3', '<'))
                {
                    for ($i = count($trace) - 1; $i > 0; --$i) 
                    {
                        if (isset($trace[$i - 1]['args']))
                        {
                            $trace[$i]['args'] = $trace[$i - 1]['args'];
                            unset($trace[$i - 1]['args']);
                        }
                    }
                } 
            }

            //����һ���쳣�Ĵ����ְ�
            $error = RookieException::message($e);

            //exception log
            if (RookieCore::$config['base']['log'])
                RookieLog::write($error, 'sys');
            
            if (RookieCore::$config['base']['isCli'])
            {
                echo "\n{$error}\n";
                exit(1);
            }

            //ȷ�������ʵ���HTTP��ͷ
            if ( ! headers_sent() )
            {
                $httpHeaderStatus = ($e instanceof RookieHttpException) ? $code : 500;
                                
                header('Content-Type: '.RookieException::$errorViewContentType.'; 
                    charset='.RookieCore::$config['base']['charset'], true, $httpHeaderStatus);
            }

            //ָ��404ҳ
            if (isset(RookieCore::$config['404']))
            {
                if (strstr( $_SERVER['REQUEST_URI'], RookieCore::$config['404']))
                    exit();
                header('location: '.RookieCore::$config['404']);
            }

            ob_get_clean();
            ob_start();

            //�����ajax
            if (isset($_SERVER['HTTP_REQUEST_TYPE']) && ($_SERVER['HTTP_REQUEST_TYPE'] == 'ajax'))
            {
                echo "\n{$error}\n";
                exit(1);
            }

            //�ж�debug view �Ƿ����
            $debugViewFile = dirname(__FILE__).'/../debug/debugView.php';
            if (file_exists( $debugViewFile ))
                require $debugViewFile; 
            else
                echo $error;

            //���������������
            echo ob_get_clean();
            exit(1);
        }
        catch(Exception $e)
        {
            ob_get_level() && ob_clean();
            echo RookieException::message($e), '\n';
            exit(1);
        }
    } //}}}

    /**
     * set message
     *
     * @param string $message
     * @param array  $variables
     * @return string $message
     */
    public static function setMessage($message, $variables)
    { //{{{
        foreach ($variables as $key => $val)
            $message = str_replace($key, $val, $message);

        return $message;
    } //}}}

    /**
     * ��ȡ�û���ǰ�Ĵ�������
     * 
     * @return void
     */
    public static function getUserReporting()
    { //{{{
        $errorReporting = ini_get('error_reporting');
        for ($i = 0; $i < 15; $i++)
        {
            $errorKey = $errorReporting & pow(2, $i);
            if (! isset(self::$phpErrors[$errorKey])) 
                unset(self::$phpErrors[$errorKey]);
        }
    } //}}}

    /**
     * ��дtoString()����
     *
     * @return string
     */
    public function __toString()
    { //{{{
        return RookieException::message($this);
    } //}}}

    /**
     * ��ȡ���е����ֱ�ʾ�쳣
     *
     * @param object Exception
     * @return string
     */
    public static function message(Exception $e)
    { //{{{
        //��ʽ�����
        return sprintf('%s [ %s ]: %s ~ %s [ %d ]',
            get_class($e), $e->getCode(), strip_tags($e->getMessage()), $e->getFile(), $e->getLine());
    } //}}}

}
