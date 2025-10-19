<?php
namespace OrsozoxDivineSEO;

/**
 * Autoloader Class
 * Automatically loads classes based on namespace
 */
class Autoloader {
    
    /**
     * Register autoloader
     */
    public static function register() {
        spl_autoload_register([__CLASS__, 'autoload']);
    }
    
    /**
     * Autoload classes
     * 
     * @param string $class Full class name with namespace
     */
    public static function autoload($class) {
        // Check if class belongs to our namespace
        $prefix = 'OrsozoxDivineSEO\\';
        $len = strlen($prefix);
        
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        // Get relative class name
        $relative_class = substr($class, $len);
        
        // Convert namespace to file path
        $file = ODSE_PLUGIN_DIR . 'includes/' . str_replace('\\', '/', $relative_class) . '.php';
        
        // Load class file if exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
