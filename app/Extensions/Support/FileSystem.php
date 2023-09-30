<?php

namespace App\Extensions\Support;

class FileSystem
{
    public static function directory($path, $make = false){
        $path = str_replace(['~/', '\\', '/'], [APP_DIR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $path);
        if ($make && !file_exists($path) && !is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    public static function makeStorageDir($path, $make = false){
        $path = str_replace(['~/', '\\', '/'], [STORAGE_DIR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $path);
        $realPath = STORAGE_DIR . $path;
        if ($make && !file_exists($path) && !is_dir($realPath)) {
            mkdir($realPath, 0777, true);
        }
        return $realPath;
    }
    
    public static function autoload($base, &$container)
    {
        $dir = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, APP_DIR . $base);
        if (is_dir($dir) && $handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if (strpos($file, ".php")) {
                    $callable  = str_replace('.php', '', last(explode('/', $dir . "/" . $file)));
                    $namespace = ucfirst($base . $callable);

                    $container->set($callable, function ($container) use ($namespace) {
                        return new $namespace($container);
                    });
                }
            }
            closedir($handle);
        }       
    }
}
