<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieSession
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
abstract class RookieSession
{

    /**
     * @var  string  默认的会话适配器
     */
    public static $default = 'native';

    /**
     * @var  array  会话实例
     */
    public static $instances = array();

    /**
     * 创建一个给定类型的单身会议
     * $session = Session::instance();
     * [!!] [Session::write] 请求结束时将自动被调用。
     *
     * @param   string   type of session (native)
     * @param   string   session identifier
     * @return  Session
     * @uses    Kohana::$config
     */
    public static function instance($type = NULL, $id = NULL)
    { //{{{
        if ($type === NULL) 
        {
            // 使用默认的类型
            $type = RookieCore::$config['session']['session_storage'];
        }

        if ( ! isset(RookieSession::$instances[$type]))
        {
            // 这种类型的加载配置
            $config = RookieCore::$config['session']['session_storage'];

            // 设置会话类的名称
            $class = 'RookieSession'.ucfirst($type);

            if (RookieCore::$config['session']['session_type'] == 'memcached')
            {
                ini_set('session.save_handler', 'memcached');
                ini_set('session.save_path', RookieCore::$config['session']['session_save_path']);
            }

            // 创建一个新的会话实例
            RookieSession::$instances[$type] = $session = new $class();

            register_shutdown_function(array($session, 'write'));
        }

        return RookieSession::$instances[$type];
    } //}}}

    /**
     * @var  string  cookie name
     */
    protected $_name = 'session';

    /**
     * @var  int  cookie的生命周期
     */
    protected $_lifetime = 0;

    /**
     * @var  bool  会话数据加密
     */
    protected $_encrypted = FALSE;

    /**
     * @var  array  session data
     */
    protected $_data = array();

    /**
     * @var  bool  session 
     */
    protected $_destroyed = FALSE;

    /**
     * 重载一些设置
     *
     * @param   array   configuration
     * @param   string  session id
     * @return  void
     * @uses    Session::read
     */
    public function __construct(array $config = NULL, $id = NULL)
    { //{{{
        $config = RookieCore::$config['session'];
        
        if (isset($config['session_name']))
        {
            $this->_name = (string) $config['session_name'];
        }

        if (isset($config['session_lifetime']))
        {
            $this->_lifetime = (int) $config['session_lifetime'];
        }

        if (isset($config['session_encrypted']))
        {
            if ($config['session_encrypted'] === TRUE)
            {
                $config['session_encrypted'] = 'default';
            }

            $this->_encrypted = $config['session_encrypted'];
        }

        $this->read($id);
    } //}}}

    /**
     * 会话对象呈现一个序列化的字符串。如果加密
     * 启用，该会话将被加密。如果没有，输出字编码使用base64_encode
     *  echo $session;
     *
     * @return  string
     * @uses    Encrypt::encode
     */
    public function __toString()
    { //{{{
        $data = serialize($this->_data);

        if ($this->_encrypted)
        {
            $data = $RookieEncrypt::encode(data);
        }
        else
        {
            $data = base64_encode($data);
        }

        return $data;
    } //}}}

    /**
     * //返回当前的session信息
     * $data = $session->as_array();
     * $data =& $session->as_array();
     *
     * @return  array
     */
    public function & as_array()
    { //{{{
        return $this->_data;
    } //}}}

    /**
     * 获取当前session id 
     * $id = $session->id();
     *
     * @return  string
     */
    public function id()
    { //{{{
        return NULL;
    } //}}}

    /**
     * 获取当前session cookie名字 
     * $name = $session->name();
     *
     * @return  string
     */
    public function name()
    { //{{{
        return $this->_name;
    } //}}}

    /**
     * 获取session 
     * $foo = $session->get('foo');
     *
     * @param   string   variable name
     * @param   mixed    default value to return
     * @return  mixed
     */
    public function get($key, $default = NULL)
    { //{{{
        return array_key_exists($key, $this->_data) ? $this->_data[$key] : $default;
    } //}}}

    /**
     * 获取过后删除 
     * $bar = $session->get_once('bar');
     *
     * @param   string  variable name
     * @param   mixed   default value to return
     * @return  mixed
     */
    public function get_once($key, $default = NULL)
    { //{{{
        $value = $this->get($key, $default);

        unset($this->_data[$key]);

        return $value;
    } //}}}

    /**
     * 设置session值
     * $session->set('foo', 'bar');
     *
     * @param   string   variable name
     * @param   mixed    value
     * @return  $this
     */
    public function set($key, $value)
    { //{{{
        $this->_data[$key] = $value;

        return $this;
    } //}}}

    /**
     * 绑定一个变量 
     * $session->bind('foo', $foo);
     *
     * @param   string  variable name
     * @param   mixed   referenced value
     * @return  $this
     */
    public function bind($key, & $value)
    { //{{{
        $this->_data[$key] =& $value;

        return $this;
    } //}}}

    /**
     * 删除session 
     * $session->delete('foo');
     *
     * @param   string  variable name
     * @param   ...
     * @return  $this
     */
    public function delete($key)
    { //{{{
        $args = func_get_args();

        foreach ($args as $key)
        {
            unset($this->_data[$key]);
        }

        return $this;
    } //}}}

    /**
     * 加载session data 
     * $session->read();
     *
     * @param   string   session id
     * @return  void
     */
    public function read($id = NULL)
    { //{{{
        $data = NULL;
        try
        {
            if (is_string($data = $this->_read($id)))
            {
                if ($this->_encrypted)
                {
                    $data = RookieEncrypt::decode($data);
                }
                else
                {
                    $data = base64_decode($data);
                }

                $data = unserialize($data);
            }
            else
            {

            }
        }
        catch (Exception $e)
        {
            throw new RookieException('Error reading session data.');
        }

        if (is_array($data))
        {
            $this->_data = $data;
        }
    } //}}}

    /**
     * 生成一个新的会话ID，并返回 
     * $id = $session->regenerate();
     *
     * @return  string
     */
    public function regenerate()
    { //{{{
        return $this->_regenerate();
    } //}}}

    /**
     * 设置last_active的时间戳，并将会话保
     * $session->write();
     *
     * @return  boolean
     * @uses    Kohana::$log
     */
    public function write()
    { //{{{
        if (headers_sent() OR $this->_destroyed)
        {
            return FALSE;
        }

        $this->_data['last_active'] = time();

        try
        {
            return $this->_write();
        }
        catch (Exception $e)
        {
            RookieLog::write(RookieException::message($e));

            return FALSE;
        }
    } //}}}

    /**
     * 彻底摧毁当前会话
     * $success = $session->destroy();
     *
     * @return  boolean
     */
    public function destroy()
    { //{{{
        if ($this->_destroyed === FALSE)
        {
            if ($this->_destroyed = $this->_destroy())
            {
                $this->_data = array();
            }
        }

        return $this->_destroyed;
    } //}}}

    /**
     * 重启session 
     * $success = $session->restart();
     *
     * @return  boolean
     */
    public function restart()
    { //{{{
        if ($this->_destroyed === FALSE)
        {
            // Wipe out the current session.
            $this->destroy();
        }

        // Allow the new session to be saved
        $this->_destroyed = FALSE;

        return $this->_restart();
    } //}}}

    /**
     * 加载原始的会话数据串，并返回 
     *
     * @param   string   session id
     * @return  string
     */
    abstract protected function _read($id = NULL);

    /**
     * 生成一个新的会话ID，并返回
     *
     * @return  string
     */
    abstract protected function _regenerate();

    /**
     * 写入当前会话 
     *
     * @return  boolean
     */
    abstract protected function _write();

    /**
     * 销毀当前会话 
     *
     * @return  boolean
     */
    abstract protected function _destroy();

    /**
     * 重启会话 
     *
     * @return  boolean
     */
    abstract protected function _restart();
}
