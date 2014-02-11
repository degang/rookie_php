<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieSessionNative
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieSessionNative extends RookieSession {

    /**
     * @return  string
     */
    public function id()
    {
        return session_id();
    }

    /**
     * @param   string  $id  session id
     * @return  null
     */
    protected function _read($id = NULL)
    { //{{{
        //ͬ���Ự��cookie�����ò��� 
        session_set_cookie_params($this->_lifetime, RookieCookie::$path, '.lehu8.com', RookieCookie::$secure, RookieCookie::$httponly);

        //��Ҫ��PHP���͵�Cache-Controlͷ  
        session_cache_limiter(FALSE);

        //����cookie name
        session_name($this->_name);

        if ($id)
        {
            //����session id 
            session_id($id);
        }

        session_start();

        $this->_data =& $_SESSION;

        return NULL;
    } //}}}

    /**
     * @return  string
     */
    protected function _regenerate()
    { //{{{
        session_regenerate_id();

        return session_id();
    } //}}}

    /**
     * @return  bool
     */
    protected function _write()
    { //{{{
        session_write_close();

        return TRUE;
    } //}}}

    /**
     * @return  bool
     */
    protected function _restart()
    { //{{{
        $status = session_start();

        $this->_data =& $_SESSION;

        return $status;
    } //}}}

    /**
     * @return  bool
     */
    protected function _destroy()
    { //{{{
        session_destroy();

        $status = ! session_id();

        if ($status)
        {
            RookieCookie::delete($this->_name);
        }

        return $status;
    } //}}}

} 
