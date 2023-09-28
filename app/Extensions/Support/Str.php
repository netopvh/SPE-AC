<?php

namespace App\Extensions\Support;

use Illuminate\Support\Str as BaseString;

class Str extends BaseString {

    /*
    |--------------------------------------------------------------------------
    | UCWords com suporte a UTF-8
    |--------------------------------------------------------------------------
    */
    public static function ucwords($str){
        return mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
    }

    /*
    |--------------------------------------------------------------------------
    | xprint
    |--------------------------------------------------------------------------
    */
    public static function print($string, $default = ' - '){
        return (!empty($string)) ? $string : $default;
    }

    /*
    |------------------------------------------------------------------------------
    | Retorna somente os números da string
    |------------------------------------------------------------------------------
    */
    public static function nums($str){
        return preg_replace("/[^0-9]/", "", $str);
    }

    /*
    |--------------------------------------------------------------------------
    | Aplica uma mascara na string
    |--------------------------------------------------------------------------
    */
    public static function mask($val, $mask){

        $maskared = ''; $k = 0;

        for($i = 0; $i <= strlen($mask)-1; $i++) {
            if($mask[$i] == '#') {
                if(isset($val[$k])){
                    $maskared .= $val[$k++];
                }
            }else{
            if(isset($mask[$i]))
                $maskared .= $mask[$i];
            }
        }

        return $maskared;
    }

    /*
    |--------------------------------------------------------------------------
    | Incrementa uma string e adiciona zeros ate um determinado tamanho
    |--------------------------------------------------------------------------
    */
    public static function pad($number, $length, $pad = '0'){
        return str_pad((intval($number) + 1), $length, $pad, STR_PAD_LEFT);;
    }

}