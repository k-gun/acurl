<?php
namespace ACurl;

final class Autoload
{
    final public function register()
    {
        return spl_autoload_register(function($className) {
            if (strpos($className, __namespace__) !== 0) {
                return;
            }

            $classBase = substr($className, strlen(__namespace__) + 1);
            $classFile = sprintf('%s/%s.php', __dir__, str_replace('\\', '/', $classBase));
            require $classFile;
        });
    }
}
