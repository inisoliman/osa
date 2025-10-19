<?php
namespace OrsozoxDivineSEO\Core;

class Plugin {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        if (is_admin()) {
            new \OrsozoxDivineSEO\Admin\AdminMenu();
            new \OrsozoxDivineSEO\Admin\AjaxHandlers();
        }
        new \OrsozoxDivineSEO\InternalLinking\AutoLinker();
    }
    
    public function run() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin']);
    }
    
    public function enqueue_admin($hook) {
        if (strpos($hook, 'orsozox-divine-seo') === false) return;
        
        wp_enqueue_style('odse-admin', ODSE_PLUGIN_URL . 'assets/css/admin-style.css', [], ODSE_VERSION);
        wp_enqueue_script('odse-admin', ODSE_PLUGIN_URL . 'assets/js/admin-script.js', ['jquery'], ODSE_VERSION, true);
        
        wp_localize_script('odse-admin', 'odseAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('odse_nonce'),
        ]);
    }
}
