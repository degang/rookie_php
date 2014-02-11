<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieUrl
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieUrl {

    /**
     * 获取当前的url地址 
     *
     * @return string 
     */
    public static function getUrl()
    { //{{{
        $sysProtocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 
            'https://' : 'http://';
        $phpSelf = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relateUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 
            $phpSelf.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $pathInfo);

        return $sysProtocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relateUrl;
    } //}}}

    /**
     * 获当前域名
     *
     * @return string
     */
    public static function getBaseUrl()
    { //{{{
        $sysProtocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 
            'https://' : 'http://';
        $phpSelf = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];

        return $sysProtocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').'/';
    } //}}}


    /**
     * 合并当前的GET参数,返回结果查询字符串。
     * // Returns "?sort=title&limit=10" combined with any existing GET values
     * $query = RookieUrl::query(array('sort' => 'title', 'limit' => 10));
     *
     * @param   array    $params   Array of GET parameters
     * @param   boolean  $use_get  Include current request GET parameters
     * @return  string
     */
    public static function query(array $params = NULL, $use_get = TRUE)
    { //{{{
        if ($use_get)
        {
            if ($params === NULL)
                $params = $_GET;
            else
                $params = array_merge($_GET, $params);
        }

        if (empty($params))
            return '';

        $query = http_build_query($params, '', '&');
        return ($query === '') ? '' : ('?'.$query);
    } //}}}

    /**
     * 创建URL地址
     *
     * @param string $url
     * @param array  $params
     * @return string
     */
    public static function createUrl($url, $params = array())
    { //{{{
        $route = RookieUri::$route;
        $createUrl = '';
        $m = isset($params['m']) ? $params['m'] : '';
        if ($m)
        {
            $routeM = array_flip(RookieCore::$config['subdomain']);
            if (isset($routeM[$m]))
            {
                $route = RookieCore::$config[$routeM[$m]];
                $params['m'] = $routeM[$m];
                $routeM[$m] == 'route' && $routeM[$m] = 'www.lehu8.com';
                $baseUrl = 'http://';
            }


        }
        else
            $baseUrl = self::getBaseUrl();

        foreach ($route as $key => $val)
        {
            if ($key == 'default')
            {
                if ($url != 'default' && strstr($routeM[$m], '.'))
                {
                    $createUrl = $baseUrl.$routeM[$m].'/'.$url;
                    return $createUrl;
                }
                return BASEURL;
            }

            if ($url == $val)
            {
                $count = count($params);
                $paramsM = $params['m'];
                unset($params['m']);
                preg_match_all('/<\w+:[^>]+>/', $key, $match);
                if (isset($match[0][0]) && !empty($match[0][0]))
                {
                    $createUrl = $key;
                    $i = 0;
                    foreach ($params as $k => $p)
                    {
                        $createUrl = str_replace($match[0][$i], $p, $createUrl);
                        $i++;
                    }
                }
                else
                    $createUrl .= $routeM[$m].'/'.$key;

                $count == 1 && $paramsM = '';
                if ($paramsM)
                    return $baseUrl.$paramsM.'/'.$createUrl;
                else 
                    return $baseUrl.$createUrl;
            }

        }
        $createUrl = $baseUrl.$url.self::query($params);
        return $createUrl;
    } //}}}

} 
