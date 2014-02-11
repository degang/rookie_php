<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieUriRouter ·������
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieUriRouter
{

    /**
     * ����url
     *
     * @param array  $routeArr
     * @param string $subfolder
     */
    public function execute($routeArr, $subfolder='/')
    { //{{{
        list ($params, $route) = $this->connect($routeArr, $subfolder);

        //�Զ�uri
        empty($route) && 
            list ($params, $route) = $this->autoConnect($routeArr, $subfolder);

        //��������
        $this->paramsHandling($params);
        $route = $this->routeHandling($route);

        return $route;
    } //}}}

    /**
     * �������� 
     *
     * @param array $params
     * @return boolean true
     */
    private function paramsHandling($params = array())
    { //{{{
        if( ! $params)
            return ;
        else
        {
            $type = strtolower($_SERVER['REQUEST_METHOD']);

            foreach($params as $key => $val)
            {
                if ($type === 'get')
                    $_GET[$key] = $val;
                elseif ($type === 'post')
                    $_POST[$key] = $val;
                else
                    $_REQUEST[$key] = $val;
            }
        }
        return true;
    } //}}}

    
    /**
     * �Զ�uri
     * 
     * @return array 
     */
    private function autoConnect($routes)
    { //{{{
        $requestedUri = $_SERVER['REQUEST_URI'];
        $routeData = array();
        $params = array();
        $routeString = '';

        $requestedUri = strtolower($requestedUri);
        if (false !== ($getPosition = strpos($requestedUri, '?')))
            $requestedUri = substr($requestedUri, 0, $getPosition);

        $requestedUri = rtrim($requestedUri, '\/');
        $routeData = explode('/', $requestedUri);
        unset($routeData[0]);

        if (count($routeData) <= 3)
            $routeString = implode('/', $routeData);
        else
            $routeString = $routes['default'];

        ! preg_match('/^[0-9a-zA-Z]+?/', $routeString) && 
            $routeString = $routes['default'];
        
        return array(null, $routeString);
    } //}}}

    /**
     * uri ���� 
     *
     * @param array $routes
     * @param string $subfolder
     */
    private function connect($routes, $subfolder)
    { //{{{
        $requestedUri = $_SERVER['REQUEST_URI'];
        $routeData = array();
        $params = array();

        //$requestedUri = strtolower($requestedUri);
        $requestedUri = ($requestedUri);

        if (false !== ($getPosition = strpos($requestedUri, '?')))
            $requestedUri = substr($requestedUri, 0, $getPosition);

        $requestedUri = substr($requestedUri, strlen($subfolder)-1);

        if (0 === strpos($requestedUri, '/index.php'))
        {
            $requestedUri = substr($requestedUri, 10);
            if ($requestedUri == '')
                $requestedUri = '/';
        }

        //���Ϊ��Ĭ�Ͽ�����
        if ($requestedUri === '/')
            return array(null, $routes['default']);

        if (isset($routes) && count($routes) > 1)
        {
            foreach ($routes as $key => $val)
            {
                //��ȡ���õ�����
                preg_match_all("/<\w+:[^>]+>/", $key, $match);
                $matchString = '';
                $routeVal = $val;

                if(count($match[0]) >= 1)
                {
                    foreach ($match[0] as $k => $val)
                    {
                        $routesParam = explode(':', $val); 
                        if (count($routesParam) == 2)
                        {
                            $key = str_replace('>', ')', $key);
                            $paramsKey = str_replace('<', '', $routesParam[0]);
                            $params[$k] = $paramsKey; 

                            $matchString = str_replace($routesParam[0].':', '(', $key);
                            $key = $matchString;
                        }
                        
                        $matchString = str_replace('/', '\/', $matchString);
                    }
                    
                    //ƥ��route
                    if (preg_match_all('#^\/'.$matchString.'\/?$#', $requestedUri, $paramUri))
                    {
                        unset($paramUri[0]);
                        $newParamUri = array();
                        foreach($paramUri as $key => $val)
                            $newParamUri[] = $val[0];

                        $newParams = array();
                        foreach ($params as $key => $val)
                            $newParams[$val] = $newParamUri[$key];

                        return array($newParams, $routeVal);
                    }

                }
                else
                {
                    if ('/'.$key == $requestedUri)
                        return array(null, $routes[$key]);
                }

            }

        }
        return ;
    } //}}}

    /**
     * ·�ɰ� 
     *
     * @param string $route
     * @return array $route 
     */
    private function  routeHandling($route)
    { //{{{
        $route = explode('/', $route);
        (count($route) == 1) && $route[1] = 'index';
        return $route;
    } //}}}

}
