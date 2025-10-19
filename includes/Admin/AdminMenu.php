<?php
namespace OrsozoxDivineSEO\Admin;

/**
 * Admin Menu Class
 */
class AdminMenu {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
    }
    
    public function add_menu_pages() {
        // Main menu
        add_menu_page(
            'Orsozox Divine SEO',
            'Divine SEO',
            'manage_options',
            'orsozox-divine-seo',
            [$this, 'render_dashboard'],
            'dashicons-networking',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'orsozox-divine-seo',
            __('Dashboard', 'orsozox-divine-seo'),
            __('Dashboard', 'orsozox-divine-seo'),
            'manage_options',
            'orsozox-divine-seo',
            [$this, 'render_dashboard']
        );
        
        // AI Settings
        add_submenu_page(
            'orsozox-divine-seo',
            __('AI Settings', 'orsozox-divine-seo'),
            __('AI Settings', 'orsozox-divine-seo'),
            'manage_options',
            'orsozox-divine-seo-ai',
            [$this, 'render_ai_settings']
        );
        
        // Keyword Map
        add_submenu_page(
            'orsozox-divine-seo',
            __('Keyword Map', 'orsozox-divine-seo'),
            __('Keyword Map', 'orsozox-divine-seo'),
            'manage_options',
            'orsozox-divine-seo-keywords',
            [$this, 'render_keywords']
        );
        
        // Site Architecture
        add_submenu_page(
            'orsozox-divine-seo',
            __('Site Architecture', 'orsozox-divine-seo'),
            __('Architecture', 'orsozox-divine-seo'),
            'manage_options',
            'orsozox-divine-seo-architecture',
            [$this, 'render_architecture']
        );
    }
    
    public function render_dashboard() {
        include ODSE_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }
    
    public function render_ai_settings() {
        include ODSE_PLUGIN_DIR . 'templates/admin/ai-settings.php';
    }
    
    public function render_keywords() {
        include ODSE_PLUGIN_DIR . 'templates/admin/keywords.php';
    }
    
    public function render_architecture() {
        include ODSE_PLUGIN_DIR . 'templates/admin/architecture.php';
    }
}
