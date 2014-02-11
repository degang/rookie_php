<?php 	defined('ROOKIE') or die('No direct script access.');

/**
 * RookieValidator 
 *
 * @author 		shendegang phpshen@gmail.com
 * @copyright   (c) 2011-2015 shendegang
 * @license 	https://github.com/shendegang/work
 */ 
class RookieValidator 
{
    const CHECK_ALL = 'all';

    const CHECK_ALL_ONE = 'all_one';

    const CHECK_SKIP = 'skip';

    const REQ_MODE_NULL_EMPTY = 'nullempty';

    const REQ_MODE_NULL_ONLY = 'null';
    
    const REQ_MSG_FIELDNAME = 'fieldname';
    
    const REQ_MSG_THIS = 'this';
    
    const REQ_MSG_UNDERSCORE_TO_SPACE = 'underscore';
    
    const REQ_MSG_CAMELCASE_TO_SPACE = 'camelcase';

    public $checkMode = 'skip';

    public $requireMode = 'nullempty';
    
    public $requiredMsgDefaultMethod = 'camelcase';
    
    public $requiredMsgDefaultSuffix = '';

    public function trimValues(&$data, $maxDepth = 5) {
        foreach($data as $k=>&$v) {
            if (is_array($v)) {
                if ($maxDepth > 0) {
                    $this->trimValues($v, $maxDepth - 1);
                }
            } else {
                $v = trim($v);
            }
        }
    }

    public static function getAvailableRules(){
        return array(
            'alpha', 'alphaNumeric', 'between', 'betweenInclusive','colorHex', 'custom', 'date', 
            'dateBetween', 'datetime', 'digit', 'email', 'equal', 'equalAs', 'float','greaterThan', 
            'greaterThanOrEqual', 'ip', 'integer', 'lessThan', 'lessThanOrEqual', 'lowercase',
            'max','maxlength', 'min', 'minlength', 'notEmpty', 'notEqual', 'notNull', 'password', 
            'passwordComplex', 'price', 'regex','uppercase', 'url', 'username','dbExist',
            'dbNotExist','alphaSpace','notInList','inList'
        );
    }

    public static function dbDataTypeToRules($type)
    { 
        $dataType = array(
                        //integers
                        'tinyint'=>'integer',
                        'smallint'=>'integer',
                        'mediumint'=>'integer',
                        'int'=>'integer',
                        'bigint'=>'integer',

                        //float
                        'float'=>'float',
                        'double'=>'float',
                        'decimal'=>'float',

                        //datetime
                        'date'=>'date',
                        'datetime'=>'datetime',
                        'timestamp'=>'datetime',
                        'time'=>'datetime'
                    );
        if(isset($dataType[$type]))
            return $dataType[$type];
    }

    public function validate($data=null, $rules=null){
        //$data = array('username'=>'leng s', 'pwd'=>'234231dfasd', 'email'=>'asdb12@#asd.com.my');
        //$rules = array('username'=>array('username'), 'pwd'=>array('password',6,32), 'email'=>array('email'));
    
        $optErrorRemove = array();

        foreach($data as $dk=>$dv){
            if($this->requireMode == RookieValidator::REQ_MODE_NULL_EMPTY && ($dv === null || $dv === '') ||
               $this->requireMode == RookieValidator::REQ_MODE_NULL_ONLY  && $dv === null){
                unset($data[$dk]);
            }
        }

        if($missingKey = array_diff_key($rules, $data) ){
                $fieldnames = array_keys($missingKey);
                $customRequireMsg = null;

                foreach($fieldnames as $fieldname){
                    if(isset($missingKey[$fieldname])){
                        if( in_array('required', $missingKey[$fieldname]) ){
                            $customRequireMsg = $missingKey[$fieldname][1];
                        }
                        else if(is_array($missingKey[$fieldname][0])){
                            foreach($missingKey[$fieldname] as $f)
                                if($f[0]=='required'){
                                    if(isset($f[1]))
                                        $customRequireMsg = $f[1];
                                    break;
                                }
                        }
                    }

                    if(is_array($missingKey[$fieldname][0])){
                       foreach($missingKey[$fieldname] as $innerArrayRules){
                           if($innerArrayRules[0]=='optional'){
                               //echo $fieldname.' - 1 this is not set and optional, should be removed from error';
                               $optErrorRemove[] = $fieldname;
                               break;
                           }
                       }
                    }
                    if($this->checkMode==RookieValidator::CHECK_ALL){
                        if($customRequireMsg!==null)
                            $errors[$fieldname] = $customRequireMsg;
                        else
                            $errors[$fieldname] = $this->getRequiredFieldDefaultMsg($fieldname);
                    }else if($this->checkMode==RookieValidator::CHECK_SKIP){
                        if(in_array($fieldname, $optErrorRemove))
                            continue;
                        if($customRequireMsg!==null)
                            return $customRequireMsg;
                        return $this->getRequiredFieldDefaultMsg($fieldname);
                    }else if($this->checkMode==RookieValidator::CHECK_ALL_ONE){
                        if($customRequireMsg!==null)
                            $errors[$fieldname] = $customRequireMsg;
                        else
                            $errors[$fieldname] = $this->getRequiredFieldDefaultMsg($fieldname);
                    }
                }
        }
        foreach($data as $k=>$v){
            if(!isset($rules[$k])) continue;
            $cRule = $rules[$k];
            foreach($cRule as $v2){
                if(is_array($v2)){
                    $vv = array_merge(array($v),array_slice($v2, 1));

                    $vIsEmpty = ($this->requireMode == RookieValidator::REQ_MODE_NULL_EMPTY && ($v === null || $v === '') ||
                                 $this->requireMode == RookieValidator::REQ_MODE_NULL_ONLY  && $v === null) ? true : false;

                    if($vIsEmpty && $v2[0]=='optional'){
                        //echo $k.' - this is not set and optional, should be removed from error';
                        $optErrorRemove[] = $k;
                    }
                    if($err = call_user_func_array(array(&$this, 'test'.$v2[0]), $vv) ){
                        if($this->checkMode==RookieValidator::CHECK_ALL)
                            $errors[$k][$v2[0]] = $err;
                        else if($this->checkMode==RookieValidator::CHECK_SKIP && !$vIsEmpty && $v2[0]!='optional'){
                            return $err;
                        }else if($this->checkMode==RookieValidator::CHECK_ALL_ONE)
                            $errors[$k] = $err;
                    }
                }
                else if(is_string($cRule[0])){
                    if(sizeof($cRule)>1){
                        $vv = array_merge(array($v),array_slice($cRule, 1));

                        if($err = call_user_func_array(array(&$this, 'test'.$cRule[0]), $vv) ){
                            if($this->checkMode==RookieValidator::CHECK_ALL || $this->checkMode==RookieValidator::CHECK_ALL_ONE)
                                $errors[$k] = $err;
                            else if($this->checkMode==RookieValidator::CHECK_SKIP){
                                return $err;
                            }
                        }
                    }else{
                        if($err = $this->{'test'.$cRule[0]}($v) ){
                            if($this->checkMode==RookieValidator::CHECK_ALL || $this->checkMode==RookieValidator::CHECK_ALL_ONE)
                                $errors[$k] = $err;
                            else if($this->checkMode==RookieValidator::CHECK_SKIP){
                                return $err;
                            }
                        }
                    }
                    continue 2;
                }
            }
        }
        if(isset($errors)){
            if(sizeof($optErrorRemove)>0){
                foreach($errors as $ek=>$ev){
                    if(in_array($ek, $optErrorRemove)){
                        unset($errors[$ek]);
                    }
                }
            }
            return $errors;
        }
    }
    
    public function setRequiredFieldDefaults( $displayMethod = RookieValidator::REQ_MSG_UNDERSCORE_TO_SPACE, $suffix = ' field is required'){
        $this->requiredMsgDefaultMethod = $displayMethod;
        $this->requiredMsgDefaultSuffix = $suffix;
    }
    
    public function getRequiredFieldDefaultMsg($fieldname){
        if($this->requiredMsgDefaultMethod==RookieValidator::REQ_MSG_UNDERSCORE_TO_SPACE)
            return ucfirst(str_replace('_', ' ', $fieldname)) . $this->requiredMsgDefaultSuffix;

        if($this->requiredMsgDefaultMethod==RookieValidator::REQ_MSG_THIS)
            return 'This ' . $this->requiredMsgDefaultSuffix;        
        
        if($this->requiredMsgDefaultMethod==RookieValidator::REQ_MSG_CAMELCASE_TO_SPACE)
            return ucfirst(strtolower(preg_replace('/([A-Z])/', ' $1', $fieldname))) . $this->requiredMsgDefaultSuffix;
        
        if($this->requiredMsgDefaultMethod==RookieValidator::REQ_MSG_FIELDNAME)
            return $fieldname . $this->requiredMsgDefaultSuffix;
    }

    public function testOptional($value){}
    public function testRequired($value, $msg){
        if ($this->requireMode == RookieValidator::REQ_MODE_NULL_EMPTY && ($value === null || $value === '') ||
            $this->requireMode == RookieValidator::REQ_MODE_NULL_ONLY  && $value === null) {

            if($msg!==null) return $msg;
            return 'This field is required!';
        }
    }

    public function testCustom($value, $function, $options=null ,$msg=null){
        if($options==null){
            if($err = call_user_func($function, $value)){
                if($err!==true){
                    if($msg!==null) return $msg;
                    return $err;
                }
            }
        }else{
            if($err = call_user_func_array($function, array_merge(array($value), $options)) ){
                if($err!==true){
                    if($msg!==null) return $msg;
                    return $err;
                }
            }
        }
    }

    public function testRegex($value, $regex, $msg=null){
        if(!preg_match($regex, $value) ){
            if($msg!==null) return $msg;
            return 'Error in field.';
        }
    }

    public function testUsername($value, $minLength=4, $maxLength=12, $msg=null){
        if(!preg_match('/^[a-zA-Z][a-zA-Z.0-9_-]{'. ($minLength-1) .','.$maxLength.'}$/i', $value)){
            if($msg!==null) return $msg;
            return "User name must be $minLength-$maxLength characters. Only characters, dots, digits, underscore & hyphen are allowed.";
        }
        else if(strpos($value, '..')!==False){
            if($msg!==null) return $msg;
            return "User name cannot consist of 2 continuous dots.";
        }
        else if(strpos($value, '__')!==False){
            if($msg!==null) return $msg;
            return "User name cannot consist of 2 continuous underscore.";
        }
        else if(strpos($value, '--')!==False){
            if($msg!==null) return $msg;
            return "User name cannot consist of 2 continuous dash.";
        }
        else if(strpos($value, '.-')!==False || strpos($value, '-.')!==False ||
                strpos($value, '._')!==False || strpos($value, '_.')!==False ||
                strpos($value, '_-')!==False || strpos($value, '-_')!==False){
            if($msg!==null) return $msg;
            return "User name cannot consist of 2 continuous punctuation.";
        }
        else if(ctype_punct($value[0])){
            if($msg!==null) return $msg;
            return "User name cannot start with a punctuation.";
        }
        else if(ctype_punct( substr($value, strlen($value)-1) )){
            if($msg!==null) return $msg;
            return "User name cannot end with a punctuation.";
        }
    }

    public function testPassword($value, $minLength=6, $maxLength=32, $msg=null){
        if(!preg_match('/^[\w~!@#$%^&*-_]{'.$minLength.','.$maxLength.'}$/i', $value)){
            if($msg!==null) return $msg;
            return "Only characters, dots, digits, underscore & hyphen are allowed. Password must be at least $minLength characters long.";
        }
    }

    public function testPasswordComplex($value, $msg=null){
        if(!preg_match('A(?=[-_a-zA-Z0-9]*?[A-Z])(?=[-_a-zA-Z0-9]*?[a-z])(?=[-_a-zA-Z0-9]*?[0-9])[-_a-zA-Z0-9]{6,32}z', $value)){
            if($msg!==null) return $msg;
            return 'Password must contain at least one upper case letter, one lower case letter and one digit. It must consists of 6 or more letters, digits, underscores and hyphens.';
        }
    }

    public function testEmail($value, $msg=null){
        // Regex based on best solution from here: http://fightingforalostcause.net/misc/2006/compare-email-regex.php
        if(!preg_match('/^([\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+\.)*[\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+@((((([a-z0-9]{1}[a-z0-9\-]{0,62}[a-z0-9]{1})|[a-z])\.)+[a-z]{2,6})|(\d{1,3}\.){3}\d{1,3}(\:\d{1,5})?)$/i', $value) ||
            strpos($value, '--')!==False || strpos($value, '-.')!==False
        ){
            if($msg!==null) return $msg;
            return 'Invalid email format!';
        }
    }

    public function testUrl($value, $msg=null){
        if(!preg_match('/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $value)){
            if($msg!==null) return $msg;
            return 'Invalid URL!';
        }
    }

    public function testIP($value, $msg=null){
        if (!preg_match('/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/',$value)) {
            if($msg!==null) return $msg;
            return 'Invalid IP address!';
        }
    }

 

    public function testColorHex($value, $msg=null){
        //#ff0000
        if (!preg_match('/^#([0-9a-f]{1,2}){3}$/i', $value)) {
            if($msg!==null) return $msg;
            return 'Invalid color code!';
        }
    }

    public function testDateTime($value, $msg=null){
        $rs = strtotime($value);

        if ($rs===false || $rs===-1){
            if($msg!==null) return $msg;
            return 'Invalid date time format!';
        }
    }

    public function testDate($value, $format='yyyy/mm/dd', $msg=null, $forceYearLength=false){
        //Date yyyy-mm-dd, yyyy/mm/dd, yyyy.mm.dd
        //1900-01-01 through 2099-12-31

        $yearFormat = "(19|20)?[0-9]{2}";
        if ($forceYearLength == true) {
            if (strpos($format, 'yyyy') !== false) {
                $yearFormat = "(19|20)[0-9]{2}";
            } else {
                $yearFormat = "[0-9]{2}";
            }
        }

        switch($format){
            case 'dd/mm/yy':
                $format = "/^\b(0?[1-9]|[12][0-9]|3[01])[- \/.](0?[1-9]|1[012])[- \/.]{$yearFormat}\b$/";
                break;
            case 'mm/dd/yy':
                $format = "/^\b(0?[1-9]|1[012])[- \/.](0?[1-9]|[12][0-9]|3[01])[- \/.]{$yearFormat}\b$/";
                break;
            case 'mm/dd/yyyy':
                $format = "/^(0[1-9]|1[012])[- \/.](0[1-9]|[12][0-9]|3[01])[- \/.]{$yearFormat}$/";
                break;
            case 'dd/mm/yyyy':
                $format = "/^(0[1-9]|[12][0-9]|3[01])[- \/.](0[1-9]|1[012])[- \/.]{$yearFormat}$/";
                break;
            case 'yy/mm/dd':
                $format = "/^\b{$yearFormat}[- \/.](0?[1-9]|1[012])[- \/.](0?[1-9]|[12][0-9]|3[01])\b$/";
                break;
            case 'yyyy/mm/dd':
            default:
                $format = "/^\b{$yearFormat}[- \/.](0?[1-9]|1[012])[- \/.](0?[1-9]|[12][0-9]|3[01])\b$/";
        }

        if (!preg_match($format, $value)) {
            if($msg!==null) return $msg;
            return 'Invalid date format!';
        }
    }

    public function testDateBetween($value, $dateStart, $dateEnd, $msg=null){
        $value = strtotime($value);
        if(!( $value > strtotime($dateStart) && $value < strtotime($dateEnd) ) ) {
            if($msg!==null) return $msg;
            return "Date must be between $dateStart and $dateEnd";
        }
    }
    public function testInteger($value, $msg=null){
        if(intval($value)!=$value || strlen(intval($value))!=strlen($value)){
            if($msg!==null) return $msg;
            return 'Input is not an integer.';
        }
    }

    public function testPrice($value, $msg=null){
        // 2 decimal
        if (!preg_match('/^[0-9]*\\.?[0-9]{0,2}$/', $value)){
            if($msg!==null) return $msg;
            return 'Input is not a valid price amount.';
        }
    }

    public function testFloat($value, $decimal='', $msg=null){
        // any amount of decimal
        if (!preg_match('/^[0-9]*\\.?[0-9]{0,'.$decimal.'}$/', $value)){
            if($msg!==null) return $msg;
            return 'Input is not a valid float value.';
        }
    }

    public function testDigit($value, $msg=null){
        if(!ctype_digit($value)){
            if($msg!==null) return $msg;
            return 'Input is not a digit.';
        }
    }

    public function testAlphaNumeric($value, $msg=null){
        if(!ctype_alnum($value)){
            if($msg!==null) return $msg;
            return 'Input can only consist of letters or digits.';
        }
    }

    public function testAlpha($value, $msg=null){
        if(!ctype_alpha($value)){
            if($msg!==null) return $msg;
            return 'Input can only consist of letters.';
        }
    }

    public function testAlphaSpace($value, $msg=null){
        if(!ctype_alpha(str_replace(' ','',$value))){
            if($msg!==null) return $msg;
            return 'Input can only consist of letters and spaces.';
        }
    }


    public function testLowercase($value, $msg=null){
        if(!ctype_lower($value)){
            if($msg!==null) return $msg;
            return 'Input can only consists of lowercase letters.';
        }
    }

    public function testUppercase($value, $msg=null){
        if(!ctype_upper($value)){
            if($msg!==null) return $msg;
            return 'Input can only consists of uppercase letters.';
        }
    }

    public function testNotEmpty($value, $msg=null){
        if(empty($value)){
            if($msg!==null) return $msg;
            return 'Value cannot be empty!';
        }
    }

    public function testMaxLength($value, $length=0, $msg=null){
        if(mb_strlen($value) > $length){
            if($msg!==null) return $msg;
            return "Input cannot be longer than the $length characters.";
        }
    }
    public function testMinLength($value, $length=0, $msg=null){
        if(strlen($value) < $length){
            if($msg!==null) return $msg;
            return "Input cannot be shorter than the $length characters.";
        }
    }

    public function testNotNull($value, $msg=null){
        if(is_null($value)){
            if($msg!==null) return $msg;
            return 'Value cannot be null.';
        }
    }

    public function testMin($value, $min, $msg=null){
        if( $value < $min){
            if($msg!==null) return $msg;
            return "Value cannot be less than $min";
        }
    }

    public function testMax($value, $max, $msg=null){
        if( $value > $max){
            if($msg!==null) return $msg;
            return "Value cannot be more than $max";
        }
    }
    public function testBetweenInclusive($value, $min, $max, $msg=null){
        if( $value < $min || $value > $max ){
            if($msg!==null) return $msg;
            return "Value must be between $min and $max inclusively.";
        }
    }

    public function testBetween($value, $min, $max, $msg=null){
        if( $value < $min+1 || $value > $max-1 ){
            if($msg!==null) return $msg;
            return "Value must be between $min and $max.";
        }
    }

    public function testGreaterThan($value, $number, $msg=null){
        if( !($value > $number)){
            if($msg!==null) return $msg;
            return "Value must be greater than $number.";
        }
    }

    public function testGreaterThanOrEqual($value, $number, $msg=null){
        if( !($value >= $number)){
            if($msg!==null) return $msg;
            return "Value must be greater than or equal to $number.";
        }
    }

    public function testLessThan($value, $number, $msg=null){
        if( !($value < $number)){
            if($msg!==null) return $msg;
            return "Value must be less than $number.";
        }
    }

    public function testLessThanOrEqual($value, $number, $msg=null){
        if( !($value <= $number)){
            if($msg!==null) return $msg;
            return "Value must be less than $number.";
        }
    }

    public function testEqual($value, $equalValue, $msg=null){
        if(!($value==$equalValue && strlen($value)==strlen($equalValue))){
            if($msg!==null) return $msg;
            return 'Both values must be the same.';
        }
    }

    public function testNotEqual($value, $equalValue, $msg=null){
        if( $value==$equalValue && strlen($value)==strlen($equalValue) ){
            if($msg!==null) return $msg;
            return 'Both values must be different.';
        }
    }

    public function testDbExist($value, $table, $field, $msg=null) {
        $result = Rookie::db()->fetchRow("SELECT COUNT($field) AS count FROM " . $table . ' WHERE '.$field.' = ? LIMIT 1', array($value));
        if ((!isset($result['count'])) || ($result['count'] < 1)) {
            if($msg!==null) return $msg;
            return 'Value does not exist in database.';
        }
    }
    public function testDbNotExist($value, $table, $field, $msg=null) {
        $result = Rookie::db()->fetchRow("SELECT COUNT($field) AS count FROM " . $table . ' WHERE '.$field.' = ? LIMIT 1', array($value));
        if ((isset($result['count'])) && ($result['count'] > 0)) {
            if($msg!==null) return $msg;
            return 'Same value exists in database.';
        }
    }



    public function testInList($value, $valueList, $msg=null){
        if(!(in_array($value, $valueList))){
            if($msg!==null) return $msg;
            return 'Unmatched value.';
        }
    }
    public function testNotInList($value, $valueList, $msg=null){
        if(in_array($value, $valueList)){
            if($msg!==null) return $msg;
            return 'Unmatched value.';
        }
    }

    public function testEqualAs($value, $method, $field, $msg=null) {
        if ($method == "get") {
          $method = $_GET;
        } else if ($method == "post") {
          $method = $_POST;
        } else {
          $method = $_POST;
        }
        if (!isset($method[$field]) || $value != $method[$field]) {
            if($msg!==null) return $msg;
            return 'Value '.$value.' is not equal with "'.$field.'".';
        }
    }

}

