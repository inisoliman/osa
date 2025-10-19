<?php
namespace OrsozoxDivineSEO;

class Autoloader {
    public static function register() {
        spl_autoload_register([__CLASS__, 'autoload']);
    }
    
    public static function autoload($class) {
        $prefix = 'OrsozoxDivineSEO\\';
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
        
        $relative = substr($class, strlen($prefix));
        $file = ODSE_PLUGIN_DIR . 'includes/' . str_replace('\\', '/', $relative) . '.php';
        
        if (file_exists($file)) require_once $file;
    }
}
