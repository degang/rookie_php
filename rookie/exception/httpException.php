<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * Rookie httpException
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 

class RookieHttpException extends RookieException
{

    protected $_code = 0;

    public function __construct($message = NULL, array $variables = NULL, $code = 0)
    { //{{{
        if ($code == 0)
            $code = $this->_code;
        parent::__construct($message, $variables, $code);

    } //}}}
}
