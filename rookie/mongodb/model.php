<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieModel
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieModel
{
    public static $config = array();
    
    public static $hostIndex = 0;

    public $db;

    public $collection;

    public $dbConfig;

    protected $_server;

    protected $_mongo;

    protected $_logQuery = false;

    protected $_query;

    public function __construct()
    { //{{{
        if (!empty($this->dbConfig))
            self::$config = $this->dbConfig;     
        else
            self::$config = RookieCore::$config['mongodb']['servers'];     

        $server = new RookieServer(self::$config);

        $this->_server = RookieServer::serverWithIndex(self::$hostIndex);
        $this->_mongo = $server->mongo();
        $this->db = self::$config[self::$hostIndex]['db'];
        $this->_query = new RookieQuery($this->_mongo, $this->db, $this->collection);
        //log query 
        if (isset(self::$config['logQuery']) && self::$config['logQuery'] == 'on')
            $this->_logQuery = true;

    } //}}}

    public function mongo()
    { //{{{
        return $this->_mongo;
    } //}}}

    public function server()
    { //{{{
        return $this->_server;
    } //}}} 

    public function __call($name, $args)
    { //{{{
        $method = get_class_methods($this);
        $argString = '';
        $comma = ''; 
        for ($i = 0; $i < count($args); $i ++) 
        {
            $argString .= $comma . "\$args[$i]";
            $comma = ', ';
        } 

        if (in_array($name, $method))
            return $this->$name($args);
        else
        {
            
            $reulst = '';
            if (in_array($name, get_class_methods('RookieQuery')))
            {
                @eval("\$result = \$this->_query->\$name($argString);");
                return $result;
            }
            else
                throw new RookieException('Function does not exist');
        }
    } //}}}

    /**
     * Construct a real ID from a mixed ID
     *
     * @param mixed $id id in mixed type
     */
    function rockRealId($id) 
    { //{{{
        if (is_object($id)) {
            return $id;
        }
        if (preg_match("/^rid_(\\w+):(.+)$/", $id, $match)) {
            $type = $match[1];
            $value = $match[2];
            switch ($type) {
                case "string":
                    return $value;
                case "float":
                    return floatval($value);
                case "double":
                    return doubleval($value);
                case "boolean":
                    return (bool)$value;
                case "integer":
                    return intval($value);
                case "long":
                    return doubleval($value);
                case "object":
                    return new MongoId($value);
                case "MongoInt32":
                    return new MongoInt32($value);
                case "MongoInt64":
                    return new MongoInt64($value);
                case "mixed":
                    $eval = new VarEval(base64_decode($value));
                    $realId = $eval->execute();
                    return $realId;
            }
            return;
        }
        
        if (is_numeric($id)) {
            return floatval($id);
        }
        if (preg_match("/^[0-9a-z]{24}$/i", $id)) {
            return new MongoId($id);
        }
        return $id;
    } //}}}
    
    /**
     * Format ID to string
     *
     * @param mixed $id object ID
     */
    function rockIdString($id) 
    { //{{{
        if (is_object($id) && $id instanceof MongoId) {
            return "rid_object:" . $id->__toString();
        }
        if (is_object($id)) {
            return "rid_" . get_class($id) . ":" . $id->__toString();
        }
        if (is_scalar($id)) {
            return "rid_" . gettype($id) . ":" . $id;
        }
        return "rid_mixed:" . base64_encode(var_export($id, true));
    } //}}}
    
    /**
     * Get real value from one string
     *
     * @param MongoDB $mongodb current mongodb
     * @param integer $dataType data type
     * @param string $format data format
     * @param string $value value in string format
     * @return mixed
     * @throws Exception
     * @since 1.1.0
     */
    function rockRealValue($mongodb, $dataType, $format, $value) 
    { //{{{
        $realValue = null;
        switch ($dataType) {
            case "integer":
            case "float":
            case "double":
                $realValue = doubleval($value);
                break;
            case "string":
                $realValue = $value;
                break;
            case "boolean":
                $realValue = ($value == "true");
                break;
            case "null":
                $realValue = NULL;
                break;
            case "mixed":
                $eval = new VarEval($value, $format, $mongodb);
                $realValue = $eval->execute();
                if ($realValue === false) {
                    throw new RookieException("Unable to parse mixed value, just check syntax!");
                }
                break;
        }
        return $realValue;
    } //}}}

}
