<?php
namespace OrsozoxDivineSEO\Admin;

class AjaxHandlers {
    public function __construct() {
        add_action('wp_ajax_odse_test_ai', [$this, 'test_ai']);
    }
    
    public function test_ai() {
        check_ajax_referer('odse_nonce', 'nonce');
        
        $engine = new \OrsozoxDivineSEO\AI\Engine();
        $result = $engine->test_connection();
        
        wp_send_json($result);
    }
}
