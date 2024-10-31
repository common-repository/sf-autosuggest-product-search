<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SFPA_ValidatorClass{

    public function hexadecimal($val, $dafault = ''){
        if(!preg_match('/^#[a-f0-9]{6}$/i', $val)){
            return $dafault;
        }
        return $val;
    }

    public function checkbox($val){
        if ( $val != 'on') {
            return '';
        }
        return $val;
    }

    public function int($val, $dafault = ''){
        if ( !is_numeric($val)) {
            return $dafault;
        }
        return intval($val);
    }

    public function ids($val){
        $array_ids = explode(',', $val);
        $returned_val = array();
        foreach($array_ids as $id){
            $returned_val[] = $this->int($id);
       }
       $returned_val = implode(',', $returned_val );
       return $returned_val;
    }

    public function plaintext($val){
        $val = strip_tags($val);
        return $val;
    }

}