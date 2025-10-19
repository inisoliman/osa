<?php
namespace OrsozoxDivineSEO\Admin;

class AdminMenu {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
    }
    
    public function add_menu() {
        add_menu_page(
            'Orsozox Divine SEO',
            'Divine SEO',
            'manage_options',
            'orsozox-divine-seo',
            [$this, 'dashboard'],
            'dashicons-networking',
            30
        );
        
        add_submenu_page(
            'orsozox-divine-seo',
            'AI Settings',
            'AI Settings',
            'manage_options',
            'orsozox-divine-seo-settings',
            [$this, 'settings']
        );
    }
    
    public function dashboard() {
        include ODSE_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }
    
    public function settings() {
        include ODSE_PLUGIN_DIR . 'templates/admin/settings.php';
    }
}
