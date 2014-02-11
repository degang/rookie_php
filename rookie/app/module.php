<?php
/**
 * RookieModule class file
 * <pre>
 * 'cacheFile' => array(
 *      'className' => 'RookieCache',
 *      'param'     => array()
 *  ),
 * </pre>
 * @author shendegang
 */

interface RookieModule
{
    function setInit($param = array());
}


class RookieModuleRunner
{
    /**
     * @var array $configData 配置文件
     */
    private $configData = array(); 

    /**
     * @var array $modules 模块 
     */
    private $modules = array();

    /**
     * 初始化
     * 
     * @param array $configData
     * @throws RookieException
     */
    public function init($configData)
    { //{{{
        $this->configData = array($configData['className'] => array('init' => $configData['param']));
        $interface = new ReflectionClass('RookieModule');
        foreach ($this->configData as $modulename => $params)
        {
            //加载模块
            $classFileName = strtolower(str_replace('Rookie', '', $modulename));
            $classFile = RookieCore::findFile($classFileName.DS.$classFileName, $classFileName);
            $classFile && require $classFile;

            //报告了一个类的有关信息
            $moduleClass = new ReflectionClass( $modulename );
            
            if (!$moduleClass->isSubclassOf($interface))
                throw new RookieException("Unknown modules type:$modulename");

            $module = $moduleClass->newInstance();
            foreach ($moduleClass->getMethods() as $method)
                $this->handleMethod($module, $method, $params);

            return $module;
        }

    } //}}} 

    /**
     * 执行方法
     *
     * @param Module $module
     * @param ReflectionMethod $method
     * @param array $params
     */
    public function handleMethod(RookieModule $module, ReflectionMethod $method, $params)
    { //{{{
        $name = $method->getName();
        $args = $method->getParameters();
        
        if(count($args) != 1 ||
            substr($name, 0,3) != 'set') 
        {
            return false;       
        }

        $property = strtolower(substr($name, 3));
        if(!isset($params[$property]))
        {
            return false;
        }
        $argClass = $args[0]->getClass();
        if(empty($argClass))
        {
            $method->invoke($module, $params[$property]);
        }else{
            $method->invoke($module, $argClass->newInstance($params[$property]));
        }
    } //}}}

}
