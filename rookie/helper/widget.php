<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieWidget 
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieWidget extends RookieController
{
    /**
     * init
     *
     * @param string $widgetName
     * @param array $param
     * @return mixed
     */
    public static function init($widgetName, $param)
    { //{{{
        $widgetPath = WEBPATH.'widget'.DS.$widgetName.'.php';
        $widgetPath = str_replace('config/../', '', $widgetPath);
        if (file_exists($widgetPath))
        {
            if ( ! in_array($widgetPath,  get_included_files()))
                require $widgetPath;
            $widget = new $widgetName($param);
            $widget->beforeRun();
            $methods = get_class_methods($widget);
            $widgetFun = key($param);
            if (in_array($widgetFun, $methods))
            {
                $classVars = get_class_vars(get_class($widget));
                foreach($param[$widgetFun] as $key => $val)
                    in_array($key, $classVars) && $widget->$key = $val;

                $widget->{$widgetFun}();
                $widget->afterRun($widget);
            }
            else
                throw new RookieHttpException("Method does not exist", array(), 404);
        }
    } //}}}

    /**
     * load view
     *
     * @param string $viewName
     * @param array $data
     * @return  boolean  
     */
    public function view($viewName, $data = array())
    { //{{{
        $data = $this->data;
        $viewPath = WEBPATH.DS.'widget'.DS.'view'.DS.$viewName.'.php';
        if (file_exists($viewPath))
            require $viewPath;
        return false;
    } //}}}

    public function beforeRun(){}

    public function afterRun($routeResult){}
}
