<?php

namespace App\Utils;

class Auth {

    public function __get($name){
        return self::field($name);
    }

    public static function __callStatic($name, $args){
        return self::field($name);
    }

    public function __call($name, $args){
        return self::field($name);
    }

    public static function logged(){
        return !empty($_SESSION[ APP_SIGLA_NAME ]['user']);
    }

    public static function session(){
        return unserialize($_SESSION[ APP_SIGLA_NAME ]['user']);
    }

    protected static function field($name){
        return unserialize($_SESSION[ APP_SIGLA_NAME ]['user'])[$name];
    }
}