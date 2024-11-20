<?php
/**
 * Licensed under Creative Commons Attribution by str at maphpia dot com
 */
class Autoloader
{
    public static function register()
    {
        spl_autoload_register(function ($class) {
            $file = str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
            $file = __DIR__ . substr($file, 4);
            if (file_exists($file)) {
                require $file;
                //~ echo " [loaded ". $file ."]<br>";
                return true;
            }
            //~ echo " [not loaded ". $file ."]<br>";
            return false;
        });
    }
}
Autoloader::register();
