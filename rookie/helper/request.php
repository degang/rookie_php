<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieRequest
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieRequest
{

    /**
     * @var string $userAgent
     */
    public static $userAgent = '';

    /**
     * @var string $clientIp
     */
    public static $clientIp = '0.0.0.0';

    /**
     * @var string $trustedProxy 
     */
    public static $trustedProxies = array('127.0.0.1', 'localhost', 'localhost.localdomain');

    /**
     * 获取用户IP
     *
     * return string $clientIp
     */
    public static function getClientIp()
    { //{{{
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            AND isset($_SERVER['REMOTE_ADDR'])
            AND in_array($_SERVER['REMOTE_ADDR'], RookieRequest::$trustedProxies))
        {
            // Use the forwarded IP address, typically set when the
            // client is using a proxy server.
            // Format: "X-Forwarded-For: client1, proxy1, proxy2"
            $clientIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                    
            self::$clientIp = array_shift($clientIps);

            unset($clientIps);
        }
        elseif (isset($_SERVER['HTTP_CLIENT_IP'])
            AND isset($_SERVER['REMOTE_ADDR'])
            AND in_array($_SERVER['REMOTE_ADDR'], Request::$trustedProxies))
        {
            // Use the forwarded IP address, typically set when the
            // client is using a proxy server.
            $clientIps = explode(',', $_SERVER['HTTP_CLIENT_IP']);
                    
            self::$clientIp = array_shift($clientIps);

            unset($clientIps);
        }
        elseif (isset($_SERVER['REMOTE_ADDR']))
        {
            // The remote IP address
            self::$clientIp = $_SERVER['REMOTE_ADDR'];
        }
        return self::$clientIp;
    } //}}}

    /**
     * 获取用户代理信息
     * $info = Request::user_agent(array('browser', 'platform'));
     * $browser = Request::user_agent('browser');
     *
     * @param mixed $value return: browser, version, robot, mobile, platform; or array of values
     * @return string $userAgent
     */
    public static function getUserAgent($value)
    { //{{{ 
        
        if (isset($_SERVER['HTTP_USER_AGENT']))
        {
            // Browser type
            self::$userAgent = $_SERVER['HTTP_USER_AGENT'];

            if (is_array($value))
            {
                $agent = array();
                foreach ($value as $v)
                {
                    // Add each key to the set
                    $agent[$v] = self::getUserAgent($v);
                }

                return $agent;
            }

            static $info;

            if (isset($info[$value]))
            {
                // This value has already been found
                return $info[$value];
            }

            if ($value === 'browser' OR $value == 'version')
            {
                // Load browsers
                $browsers = require dirname(__FILE__).DS.'config'.DS.'userAgents.php';
                $browsers = $browsers[$value];

                foreach ($browsers as $search => $name)
                {
                    if (stripos(self::$userAgent, $search) !== FALSE)
                    {
                        // Set the browser name
                        $info['browser'] = $name;

                        if (preg_match('#'.preg_quote($search).'[^0-9.]*+([0-9.][0-9.a-z]*)#i', 
                            self::$userAgent, $matches))
                        {
                            // Set the version number
                            $info['version'] = $matches[1];
                        }
                        else
                        {
                            // No version number found
                            $info['version'] = FALSE;
                        }

                        return $info[$value];
                    }
                }
            }
            else
            {
                // Load the search group for this type
                $group = require dirname(__FILE__).DS.'config'.DS.'userAgents.php';
                $group = $group[$value];

                foreach ($group as $search => $name)
                {
                    if (stripos(self::$userAgent, $search) !== FALSE)
                    {
                        // Set the value name
                        return $info[$value] = $name;
                    }
                }
            }

            // The value requested could not be found
            return $info[$value] = FALSE; 
        }

    } //}}}


}
