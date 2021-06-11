<?php

class DataFilter{
    private $_requiredKeys;
    private $_requiredFields;
    private $_error; 
    private $_allowedChars;
    private $ruleArray;
    function __construct()
    {
        $this->_error = [];
        $this->_allowedChars = [];    
    }
    public function setAllowedSpecialChars($charArray){
        $this->_allowedChars = $charArray;
    }
    public function getError(){
        return $this->_error;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }
    public function __get($value){
        return $this->$value;
    }

    public function setRequiredKeys($keys){
        $this->_requiredKeys = $keys;
    }
    public function setRequiredFields($fields){
        $this->_requiredFields = $fields;
    }
    public function checkRequiredKeys($data){
        foreach ($data as $key => $value) {
            if(!in_array($key,$this->_requiredKeys)){
                array_push($this->_error , ["error"=>"key [$key] does not exist!"]);
                return false;
            }
        }
        return true;
    }
    public function checkRequiredFields($data){
        foreach ($this->_requiredFields as $key) {
            if(empty($data[$key])){
                array_push($this->_error,["error"=>"field [$key] is empty!"]);
                return false;
            }
        }
        return true;
    }
    public function validateEmail($email){
        if(filter_var($email, FILTER_VALIDATE_EMAIL)){
            return true;
        }else{
            array_push($this->_error,["error"=>"email is not valid"]);
            return false;
        }
    }
    public function isAlphaNumeric($data){
        foreach ($this->_allowedChars as $value) {
            $data = str_replace($value,"",$data);
        }
        return ctype_alnum($data);
    }
    public function isNumeric($data,$key=""){
        if(!is_numeric($data)){
            array_push($this->_error, ["error","Field->[$key] numbers are allowed"]);
            return false;
        }
        return true;
    }

    function xss_clean($data)
    {
        // Fix &entity\n;
        $data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

        // Remove any attribute starting with "on" or xmlns
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

        // Remove javascript: and vbscript: protocols
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

        // Remove namespaced elements (we do not need them)
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

        do
        {
                // Remove really unwanted tags
                $old_data = $data;
                $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
        }
        while ($old_data !== $data);

        // we are done...
        return htmlspecialchars($data);
    }
    //[key,rule,title,length]
    public function validatePost($var,$data){
        $validatingKeysModel = $this->$var;
        foreach ($validatingKeysModel as $row) {
            $rules = array_unique(explode("|",$row["rule"]));
            foreach ($rules as $value) {
                switch ($value) {
                    case 'numeric':
                        if($this->isNumeric($data[$row["key"]],$row["title"])){
                            if(isset($row["range"])){
                                $lowerBound = current(explode("-",$row["range"]));
                                $upperBound = next(explode("-",$row["range"]));
                                if( $data[$row["key"]] >= $lowerBound && $data[$row["key"]] <= $upperBound ){}
                                else{
                                    array_push($this->_error, ["error","Field->[".$row['title']."] must be in range ($lowerBound,$upperBound)"]);        
                                }
                            }
                        }
                        break;
                    case 'alpha_numeric':
                        if(!$this->isAlphaNumeric($data[$row["key"]])){
                            array_push($this->_error, ["error","Field->[".$row['title']."] alpha numeric characters are allowed"]);
                        }
                        break;
                    case 'email':
                        $this->validateEmail($data[$row["key"]]);
                        break;
                    case 'bool':
                        if(!empty($data[$row["key"]])){
                            if($data[$row["key"]] !='1' && $data[$row["key"]] != '0' && $data[$row["key"]] != 'true' && $data[$row["key"]] != 'false')
                                $data[$row["key"]]=0;
                            break;        
                        }
                        $data[$row["key"]]=0;
                    break;
                    case 'xss_clean' :
                        $data[$row["key"]] = $this->xss_clean($data[$row["key"]]);
                        break;
                    case 'char_length' :
                        if(strlen($data[$row["key"]])>$row["length"])
                            array_push($this->_error, ["error","Field->[".$row['title']."] must be less than ".$row["length"]." characters"]);
                        break;
                    case 'required':
                        if(empty($data[$row["key"]])){
                            array_push($this->_error, ["error","Field->[".$row['title']."] is empty"]);
                        }
                        break;
                    
                    default:
                        # code...
                        break;
                }
            }
        }
        return $data;
    }

}

?>