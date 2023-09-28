<?php

namespace App\Extensions\Support;

use Illuminate\Support\Arr as BaseArray;

class Arr extends BaseArray {

    /*
    |------------------------------------------------------------------------------
    | Converte o array para json
    |------------------------------------------------------------------------------
    */
    public static function toJson($array = []){
        return utf8_encode(json_encode($array));
    }

    /*
    |------------------------------------------------------------------------------
    | Retorna o próximo elemento do array se existir
    |------------------------------------------------------------------------------
    */
    public static function next($array, $index){

        $find = false;

        foreach ((array) $array as $key => $value) {

            if ($find == true) {
                return $value;
            }elseif($index == $key) {
                $find = true;
            }
        }

        return self::first($array);

    }

    /*
    |------------------------------------------------------------------------------
    | Converte os valores dos arrays em chaves
    | Suporte bi-dimensional
    |------------------------------------------------------------------------------
    */
    public static function rotate($array, $subkey = false){

        $copy = [];

        foreach ($array as $k => $v) {
            if(is_array($v)){
                $copy[$v[$subkey]] = $v;
            }else{
                $copy[$v] = $k;
            }
        }

        return $copy;

    }

    /*
    |------------------------------------------------------------------------------
    | Converte o array para objeto
    |------------------------------------------------------------------------------
    */
    public static function toObject($array){
        return json_decode(
            json_encode($array)
        );
    }

    /*
    |------------------------------------------------------------------------------
    | Navegação de elementos do array
    |------------------------------------------------------------------------------
    */
    public static function each($array, callable $callback){

        //cast
        $array = (is_array($array) and count($array)) ? $array : [];

        return array_walk($array, $callback);

    }

}