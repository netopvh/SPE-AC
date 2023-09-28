<?php

namespace App\Extensions\Support;

use App\Utils\Auth;
use App\Extensions\Support\Str;

class Routing {

    public static function in($module, $compareLast = false)
    {
        if(!$compareLast){
            $route = $_SERVER['REQUEST_URI'];
            $route = explode('/painel', $route);
            $route = @$route[1];
            if($route){
                foreach ((array) $module as $url){
                    $inRoute = false;
                    $inRoute = (strpos($route, $url) === 0);
                    if($inRoute){
                        return true;
                    }
                }
                return false;
            }else{
                return false;
            }
        }else{
            return last(explode('/', $_SERVER['REQUEST_URI'])) == $module;
        }
    }

    public static function out($request, $routes)
    {
        if($request->getUri()->getPath() != '/'){
            foreach ($routes as $k => $url){
                if(strpos($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], $url)){
                    return true;
                }
            }
            return false;
        }else{
            return true;
        }
    }

    public static function autoload($shell = false)
    {
        //includes das rotas
        $includes   = []; 

        //resolve o caminho completo do diretório
        $baseRoutes = APP_DIR . '/routes';

        //callback de autoloading
        $callback = function ($dir) use(&$includes) {
            if (is_dir($dir) && $handle = opendir($dir)) {
                while (false !== ($file = readdir($handle))) {
                    if(strpos($file,".php")){
                        $includes[] = $dir."/".$file;
                    }
                }

                closedir($handle);
            }
        };

        //inclui as rotas base
        $callback($baseRoutes);

        return $includes;
    }
}