<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieUri ����url��ַ 
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieUri
{

    /**
     * @var array $route ·�ɹ���
     */
    public static $route = array();

    /**
     * @var string $modules ��ǰ���ڵ�ģ��
     */
    public static $modules;

    /**
     * @var string $controller ��ǰ���ڵĿ��Ʋ�
     */
    public static $controller;
    
    /**
     * @var string $action ��ǰ���ڵķ���
     */
    public static $action;

    /**
     * @var string $viewPath
     */
    public static $viewPath;

    /**
     * @var string $layout
     */
    public static $layout;

    /**
     * @var string $layoutPath
     */
    public static $layoutPath;


    /**
     * ����webӦ�ó������
     */
    public static function run()
    { //{{{
        self::routeTo();
    } //}}}

    /**
     * ����·�ɹ���
     *
     * @return mixed ��404���ض���URL��HTTP״̬��
     */
    public static function routeTo()
    { //{{{
        $router = new RookieUriRouter;

        if ( !isset(self::$route['default']))
            self::$route['default'] = null;
        $router = $router->execute(self::$route);

        if ($router[0] == '')
            throw new RookieHttpException('I\'m sorry you are looking page does not exist', array(),404);

        //view base path
        $viewBasePath = WEBPATH.'view'.DS.RookieCore::$config['base']['viewDefault'].DS;
        if (count($router) == 3)
        {
            self::$modules = $router[0];
            self::$controller = $router[1].'Controller';
            self::$action = $router[2].'Action';
            self::$viewPath = $viewBasePath.self::$modules.DS.$router[1];
        }
        else
        {
            self::$controller = $router[0].'Controller';
            self::$action = $router[1].'Action';
            self::$viewPath =$viewBasePath.$router[0];
        }

        if (isset(RookieCore::$config['subdomain']))
        {
            $subdomainModulesAll = array_values(RookieCore::$config['subdomain']);
            if ( isset(RookieCore::$config['subdomain'][$_SERVER['HTTP_HOST']]))
            {
                $subdomainModules = RookieCore::$config['subdomain'][$_SERVER['HTTP_HOST']];
                if (self::$modules !== $subdomainModules)
                    throw new RookieHttpException('I\'m sorry you are looking page does not exist', 
                    array(),404);
            }
            else
            {
                if (in_array(self::$modules, $subdomainModulesAll))
                {
                throw new RookieHttpException('I\'m sorry you are looking page does not exist', 
                array(), 404);
                }
            }
        }

        $modulesPath = self::$modules ? 'modules'.DS.self::$modules.DS : '';
        $fileName = WEBPATH.$modulesPath.'controller'.DS.self::$controller.'.php';

        if (file_exists($fileName))
            require_once $fileName;
        else
            throw new RookieHttpException("Control layer does not exist", array(), 404);
        
        if (class_exists(self::$controller))
            $controller = new self::$controller;
        else
            throw new RookieHttpException("Class does not exist", array(), 404);

        //layout 
        if (isset($controller->layout))
        {
            self::$layout = $controller->layout;
            self::$layoutPath = $viewBasePath.DS.'layouts'.DS;
        }

        $methods = get_class_methods(self::$controller);
        if (in_array(self::$action, $methods))
            $routeRs = $controller->{self::$action}();
        else
            throw new RookieHttpException("Method does not exist", array(), 404);
        
        self::$viewPath .= DS.str_replace('Action','',self::$action).'.php';
        $controller->viewName == NULL && $controller->autoView();

        $controller->beforeRun();
        $controller->afterRun($routeRs);
        return $routeRs;
        
    } //}}}


}
