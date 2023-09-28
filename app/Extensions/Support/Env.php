<?php

namespace App\Extensions\Support;

use App\Utils\Auth;
use Sail\Parser\Simple;
use Sail\Useragent;

class Env {

    /*
    |--------------------------------------------------------------------------
    | Retorna o valor de uma constante ou um valor padrão definido
    |--------------------------------------------------------------------------
    */
    public static function get($constant){
        return constant(strtoupper($constant));
    }

    /*
    |--------------------------------------------------------------------------
    | Retorna o IP do usuário corrente
    |--------------------------------------------------------------------------
    */
    public static function ip(){
        return Arr::get($_SERVER, "HTTP_CF_CONNECTING_IP", Arr::get($_SERVER, "REMOTE_ADDR"));
    }

    public static function session(){

        if(Auth::logged()){

            $user = !empty($_SESSION['app']['user']) ? unserialize($_SESSION['app']['user']) : [];
            $old  = !empty($_SESSION['app']['old'])  ? unserialize($_SESSION['app']['old'])  : [];

            return [
                'user' => $user,
                'old'  => $old
            ];

        }else{
            return [];
        }

    }


    public static function browser(){

        $ua = new Useragent($_SERVER['HTTP_USER_AGENT']);
        $ua->pushParser(new Simple());
        return $ua->getInfo();

    }

    /*
    |--------------------------------------------------------------------------
    | Força o cliente a fazer um novo request baseado em HTTPS.
    |--------------------------------------------------------------------------
    */
    public static function https(){
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "http") {
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $redirect);
            exit();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Verifica se o navegador em uso é o Internet Explorer
    |--------------------------------------------------------------------------
    */
    public static function msie(){
        preg_match('/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches);
        if(count($matches)<2){
            preg_match('/Trident\/\d{1,2}.\d{1,2}; rv:([0-9]*)/', $_SERVER['HTTP_USER_AGENT'], $matches);
        }
        return (count($matches)>1) ? true : false;
    }


}